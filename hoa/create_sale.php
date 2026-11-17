<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/smtp.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
require '../vendor/fpdf/fpdf.php';

date_default_timezone_set('Africa/Lagos');

function saleFail(string $message): never
{
    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $safe = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    echo "<script>alert({$safe}); window.history.back();</script>";
    exit;
}

function saleMoney(float $amount): float
{
    return round($amount + 0.0000001, 2);
}

function saleIsValidImei(string $imei): bool
{
    if (!preg_match('/^[0-9]{15}$/', $imei)) return false;
    $sum = 0;
    for ($i = 0; $i < 15; $i++) {
        $digit = (int)$imei[$i];
        if ($i % 2 === 1) {
            $digit *= 2;
            if ($digit > 9) $digit -= 9;
        }
        $sum += $digit;
    }
    return $sum % 10 === 0;
}

function saleFormatNigerianPhone(string $phone): string
{
    $cleaned = preg_replace('/\\D/', '', $phone) ?? '';
    if (str_starts_with($cleaned, '0')) $cleaned = '234' . substr($cleaned, 1);
    return $cleaned;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    saleFail('Invalid sale submission. Please return to the sales page and try again.');
}

// Login/session validation is already handled centrally by validation.php.
// Read the authenticated context established by the shared includes without
// performing a second login check or redirecting/logging the user out.
$user_id = (int)($_SESSION['user_id'] ?? 0);
$company_id = (int)($_SESSION['company_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);

$cart_payload = (string)($_POST['cart_payload'] ?? '');
$cartData = json_decode($cart_payload, true);
if (!is_array($cartData) || empty($cartData['items']) || !is_array($cartData['items'])) {
    saleFail('Add at least one product to cart.');
}
$items = $cartData['items'];
if (count($items) > 100) saleFail('Too many cart lines. Please split this sale into smaller transactions.');

$customer_type = (string)($_POST['customer_type'] ?? 'new');
$payment_status = (string)($_POST['payment_status'] ?? 'full');
if (!in_array($payment_status, ['full', 'partial'], true)) saleFail('Invalid payment status.');
$sale_note = trim((string)($_POST['sale_note'] ?? ''));
$due_date_input = trim((string)($_POST['due_date'] ?? ''));
$amountPaidRaw = $_POST['amount_paid'] ?? '0';
if (!is_scalar($amountPaidRaw) || !is_numeric((string)$amountPaidRaw)) {
    saleFail('Enter a valid payment amount.');
}
$amount_paid_input = (float)$amountPaidRaw;
if (!is_finite($amount_paid_input) || $amount_paid_input < 0) {
    saleFail('Enter a valid payment amount.');
}

// Load and verify the signed-in user's active location. The invoice office
// address is always taken from this location, not the company's default address.
$locStmt = $pdo->prepare("
    SELECT id, name, type, address_line1, city, state
    FROM locations
    WHERE id = ?
      AND company_id = ?
      AND COALESCE(status, 0) = 1
    LIMIT 1
");
$locStmt->execute([$location_id, $company_id]);
$activeLocation = $locStmt->fetch(PDO::FETCH_ASSOC);
if (!$activeLocation) {
    saleFail('The selected location is not valid for this company.');
}

$officeAddressParts = [
    trim((string)($activeLocation['name'] ?? '')),
    trim((string)($activeLocation['type'] ?? '')),
    trim((string)($activeLocation['address_line1'] ?? '')),
    trim((string)($activeLocation['city'] ?? '')),
    trim((string)($activeLocation['state'] ?? '')),
];
$officeAddressParts = array_values(array_unique(array_filter(
    $officeAddressParts,
    static fn(string $part): bool => $part !== ''
)));
$locationOfficeAddress = implode(', ', $officeAddressParts);
if ($locationOfficeAddress === '') {
    saleFail('The active location does not have enough address information to print on the invoice. Please update the location details.');
}

// Resolve customer input before opening the transaction; all customer writes still occur inside it.
if ($customer_type === 'new') {
    $customer_name = trim((string)($_POST['customer_name'] ?? ''));
    $customer_phone = trim((string)($_POST['customer_phone'] ?? ''));
    $customer_email = trim((string)($_POST['customer_email_new'] ?? ''));
    if ($customer_name === '' || $customer_phone === '') saleFail('Enter the new customer name and phone number.');
    if ($customer_email !== '' && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) saleFail('Enter a valid customer email address.');
    $customer_id = 0;
} elseif ($customer_type === 'existing') {
    $customer_id = (int)($_POST['existing_customer_id'] ?? 0);
    if (!$customer_id) saleFail('Select an existing customer.');
    $custStmt = $pdo->prepare("SELECT fullname, email, phone FROM customers WHERE id = ? AND company_id = ? AND location_id = ? LIMIT 1");
    $custStmt->execute([$customer_id, $company_id, $location_id]);
    $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) saleFail('The selected customer could not be found at this company location.');
    $customer_name = (string)$customer['fullname'];
    $customer_email = (string)($customer['email'] ?? '');
    $customer_phone = (string)($customer['phone'] ?? '');
} else {
    saleFail('Invalid customer selection.');
}

// Validate cart structure and calculate all totals from database prices, never browser totals.
$validatedItems = [];
$grand_total = 0.0;
$seenLines = [];
$seenIdentifiers = [];

foreach ($items as $index => $item) {
    if (!is_array($item)) saleFail('A cart item is invalid.');

    $product_id = (int)($item['product_id'] ?? 0);
    $variant_id = (int)($item['variant_id'] ?? 0);
    $qty = (float)($item['quantity'] ?? 0);

    if ($product_id <= 0 || !is_finite($qty) || $qty <= 0 || $qty > 100000) {
        saleFail('A cart item has an invalid product or quantity.');
    }

    $lineKey = $product_id . ':' . $variant_id;
    if (isset($seenLines[$lineKey])) {
        saleFail('A product/variant appears more than once in the cart. Remove the duplicate line and try again.');
    }
    $seenLines[$lineKey] = true;

    $productStmt = $pdo->prepare("
        SELECT
            id, name, stock_type, selling_price, unit_id, has_variants,
            CASE
                WHEN identifier_tracking IN ('imei','serial','vin') THEN identifier_tracking
                WHEN COALESCE(imei_tracking, 0) = 1 THEN 'imei'
                ELSE 'none'
            END AS identifier_tracking
        FROM products
        WHERE id = ? AND company_id = ?
        LIMIT 1
    ");
    $productStmt->execute([$product_id, $company_id]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product['stock_type'] !== 'Sales Only') {
        saleFail('A selected product is not available for sale in this company.');
    }
    if (empty($product['unit_id'])) {
        saleFail('Product unit is missing for ' . $product['name'] . '.');
    }

    $effectiveName = (string)$product['name'];
    $databasePrice = $product['selling_price'];
    $trackingType = (string)$product['identifier_tracking'];
    $variant = null;

    if ((int)$product['has_variants'] === 1) {
        if ($variant_id <= 0) {
            saleFail('Select a variant for ' . $product['name'] . '.');
        }

        $variantStmt = $pdo->prepare("
            SELECT id, variant_name, selling_price, tracking_policy
            FROM product_variants
            WHERE id = ?
              AND product_id = ?
              AND company_id = ?
              AND is_active = 1
            LIMIT 1
        ");
        $variantStmt->execute([$variant_id, $product_id, $company_id]);
        $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);

        if (!$variant) {
            saleFail('The selected variant is inactive or does not belong to this product.');
        }

        $variantPolicy = strtolower(trim((string)$variant['tracking_policy']));
        if ($variantPolicy === '' || $variantPolicy === 'inherit') {
            // "inherit" uses the parent product's identifier tracking policy.
            $trackingType = (string)$product['identifier_tracking'];
        } else {
            $trackingType = $variantPolicy;
        }

        if (!in_array($trackingType, ['none', 'imei', 'serial', 'vin'], true)) {
            saleFail('The selected variant has an invalid identifier tracking policy.');
        }

        $effectiveName .= ' — ' . (string)$variant['variant_name'];

        // A variant price overrides the parent price only when explicitly configured.
        if ($variant['selling_price'] !== null && $variant['selling_price'] !== '') {
            $databasePrice = $variant['selling_price'];
        }
    } elseif ($variant_id > 0) {
        saleFail('A variant was submitted for a product that does not use variants. Refresh the sales page.');
    }

    if (!is_numeric((string)$databasePrice)) {
        saleFail('A valid selling price is not configured for ' . $effectiveName . '.');
    }

    // Never accept a browser-supplied price.
    $unit_price = saleMoney((float)$databasePrice);
    if (!is_finite($unit_price) || $unit_price < 0) {
        saleFail('Invalid selling price configured for ' . $effectiveName . '.');
    }

    if (!in_array($trackingType, ['none', 'imei', 'serial', 'vin'], true)) {
        $trackingType = 'none';
    }

    $submittedType = (string)($item['identifier_type'] ?? $item['identifier_tracking'] ?? '');
    $identifiers = $item['identifiers'] ?? null;

    // Backward compatibility for older IMEI-only cart payloads.
    if ($identifiers === null && $trackingType === 'imei' && isset($item['imeis'])) {
        $identifiers = $item['imeis'];
    }
    if ($identifiers === null) {
        $identifiers = [];
    }

    if ($trackingType === 'none') {
        if (!empty($identifiers) || !empty($item['imeis'])) {
            saleFail('Identifiers were submitted for a product/variant configured for no tracking.');
        }
        $identifiers = [];
    } else {
        if ($submittedType !== '' && $submittedType !== $trackingType) {
            saleFail('The selected identifier type does not match the product/variant configuration for ' . $effectiveName . '. Refresh the sales page.');
        }

        if (abs($qty - round($qty)) > 0.00001) {
            saleFail('Identifier-tracked products must be sold in whole-number quantities: ' . $effectiveName . '.');
        }
        $qty = (int)round($qty);

        if (!is_array($identifiers) || count($identifiers) !== $qty) {
            $label = $trackingType === 'imei' ? 'IMEI' : ($trackingType === 'vin' ? 'VIN' : 'serial number');
            saleFail('Select exactly ' . $qty . ' ' . $label . '(s) for ' . $effectiveName . '.');
        }

        $identifiers = array_map(static fn($value) => trim((string)$value), $identifiers);
        if (in_array('', $identifiers, true) || count(array_unique($identifiers)) !== count($identifiers)) {
            saleFail('An identifier is blank or selected more than once for ' . $effectiveName . '.');
        }

        foreach ($identifiers as $identifier) {
            if (isset($seenIdentifiers[$identifier])) {
                saleFail('Identifier ' . $identifier . ' was selected more than once in this sale.');
            }
            $seenIdentifiers[$identifier] = true;

            if ($trackingType === 'imei' && !saleIsValidImei($identifier)) {
                saleFail('An IMEI for ' . $effectiveName . ' must be a valid 15-digit IMEI.');
            }

            if (in_array($trackingType, ['serial', 'vin'], true) &&
                (mb_strlen($identifier) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $identifier))) {
                saleFail(($trackingType === 'vin' ? 'A VIN' : 'A serial number') . ' for ' . $effectiveName . ' must be 1–100 characters and cannot contain control characters.');
            }
        }
    }

    $lineTotal = saleMoney($unit_price * $qty);
    $validatedItems[] = [
        'product_id' => $product_id,
        'variant_id' => $variant_id > 0 ? $variant_id : null,
        'name' => $effectiveName,
        'quantity' => $qty,
        'unit_price' => $unit_price,
        'total_price' => $lineTotal,
        'identifier_tracking' => $trackingType,
        'identifier_type' => $trackingType,
        'identifiers' => $identifiers,
        'serial_numbers' => []
    ];

    $grand_total += $lineTotal;
}
$grand_total = saleMoney($grand_total);

$discount_percent = (float)($cartData['discount_percent'] ?? 0);
if (!is_finite($discount_percent) || $discount_percent < 0 || $discount_percent > 100) saleFail('Discount percentage must be between 0 and 100.');
$discount_percent = (int)round($discount_percent);
$discount_amount = saleMoney($grand_total * $discount_percent / 100);
$final_total = saleMoney(max(0, $grand_total - $discount_amount));

if ($payment_status === 'full') {
    $amount_paid = $final_total;
    $balance = 0.0;
    $due_date = '0';
} else {
    $amount_paid = saleMoney($amount_paid_input);
    if ($amount_paid <= 0 || $amount_paid >= $final_total) saleFail('For a part payment, enter an amount greater than zero and less than the net total.');
    $balance = saleMoney($final_total - $amount_paid);
    if ($due_date_input === '') saleFail('Enter a due date for the outstanding balance.');
    $dateObj = DateTime::createFromFormat('Y-m-d', $due_date_input);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $due_date_input) saleFail('Enter a valid payment due date.');
    $due_date = $due_date_input;
}

// Build the invoice prefix from the verified company's registered name,
// not from the website/domain. Multi-word names use their initials; a
// single-word name uses its first three letters.
$companyStmt = $pdo->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
$companyStmt->execute([$company_id]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);
if (!$company) saleFail('Company details could not be loaded for the invoice.');

// Use the verified active location as the invoice's office address.
$company['company_office_address'] = $locationOfficeAddress;

$companyName = trim((string)($company['company_name'] ?? ''));
preg_match_all('/[A-Za-z0-9]+/', $companyName, $companyNameWords);
$companyWords = $companyNameWords[0] ?? [];
if (count($companyWords) > 1) {
    $companyAbbreviation = '';
    foreach ($companyWords as $word) $companyAbbreviation .= strtoupper(substr($word, 0, 1));
} elseif (count($companyWords) === 1) {
    $companyAbbreviation = strtoupper(substr($companyWords[0], 0, 3));
} else {
    $companyAbbreviation = 'INV';
}

$invoice_file = '';
$invoice_relative = '';
$committed = false;
$invoiceLockAcquired = false;
$invoiceLockName = 'ubamus_sales_invoice_sequence';
$invoicePrefix = $companyAbbreviation . '-SALES-' . date('Ymd') . '-';
$created_at = date('Y-m-d H:i:s');
$date = $created_at;
$payment = $payment_status === 'full' ? 'Full Payment' : 'Part Payment';
$currency = '₦';

try {
    /*
     * Serialize invoice-number allocation across sales requests. GET_LOCK is
     * connection-scoped, so acquire it before beginning the transaction and
     * release it after commit or rollback.
     */
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
    $lockStmt->execute([$invoiceLockName]);
    $invoiceLockAcquired = ((int)$lockStmt->fetchColumn() === 1);
    if (!$invoiceLockAcquired) {
        throw new RuntimeException('The invoice sequence is busy. Please retry the sale.');
    }

    $pdo->beginTransaction();

    // Lock the correct stock bucket for each line: product stock or variant stock.
    $stockRowsByProduct = [];
    $variantStockByLine = [];
    $serialRowsByLine = [];

    foreach ($validatedItems as $item) {
        $pid = (int)$item['product_id'];
        $variantId = (int)($item['variant_id'] ?? 0);
        $lineKey = $pid . ':' . $variantId;

        if ($variantId > 0) {
            $variantStockStmt = $pdo->prepare("
                SELECT id, quantity
                FROM product_variant_stocks
                WHERE variant_id = ?
                  AND product_id = ?
                  AND company_id = ?
                  AND location_id = ?
                LIMIT 1
                FOR UPDATE
            ");
            $variantStockStmt->execute([$variantId, $pid, $company_id, $location_id]);
            $variantStock = $variantStockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$variantStock) {
                saleFail('The selected variant has no stock record at this location: ' . $item['name'] . '.');
            }

            $available = (float)$variantStock['quantity'];
            if ($available + 0.00001 < (float)$item['quantity']) {
                saleFail('Insufficient variant stock for ' . $item['name'] . '. Available: ' . $available . ', requested: ' . $item['quantity'] . '.');
            }
            $variantStockByLine[$lineKey] = $variantStock;
        } else {
            $stockStmt = $pdo->prepare("
                SELECT id, quantity
                FROM stocks
                WHERE product_id = ?
                  AND company_id = ?
                  AND location_id = ?
                ORDER BY id ASC
                FOR UPDATE
            ");
            $stockStmt->execute([$pid, $company_id, $location_id]);
            $stockRows = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

            $available = 0.0;
            foreach ($stockRows as $row) {
                $available += (float)$row['quantity'];
            }

            if ($available + 0.00001 < (float)$item['quantity']) {
                saleFail('Insufficient stock for ' . $item['name'] . '. Available: ' . $available . ', requested: ' . $item['quantity'] . '.');
            }
            $stockRowsByProduct[$pid] = $stockRows;
        }

        if ($item['identifier_tracking'] !== 'none') {
            $placeholders = implode(',', array_fill(0, count($item['identifiers']), '?'));
            $variantCondition = $variantId > 0
                ? ' AND variant_id = ?'
                : ' AND variant_id IS NULL';

            $identifierSql = "
                SELECT id, identifier_type, identifier_value, stock_id, location_id, status, variant_id
                FROM product_serials
                WHERE company_id = ?
                  AND product_id = ?
                  AND location_id = ?
                  AND identifier_type = ?
                  {$variantCondition}
                  AND identifier_value IN ({$placeholders})
                FOR UPDATE
            ";

            $identifierParams = [$company_id, $pid, $location_id, $item['identifier_type']];
            if ($variantId > 0) {
                $identifierParams[] = $variantId;
            }

            $identifierStmt = $pdo->prepare($identifierSql);
            $identifierStmt->execute(array_merge($identifierParams, $item['identifiers']));
            $identifierRows = $identifierStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($identifierRows) !== count($item['identifiers'])) {
                saleFail('One or more selected identifiers do not belong to this product, variant, or location. Refresh the sales page and select again.');
            }

            $byIdentifier = [];
            foreach ($identifierRows as $identifierRow) {
                $byIdentifier[(string)$identifierRow['identifier_value']] = $identifierRow;
            }

            foreach ($item['identifiers'] as $identifier) {
                if (!isset($byIdentifier[$identifier]) || $byIdentifier[$identifier]['status'] !== 'in_stock') {
                    saleFail('Identifier ' . $identifier . ' is no longer available. Refresh the sales page and select another unit.');
                }

                // Legacy product-level tracked units must point to a base stock row.
                // Variant serials are tied to product_variant_stocks and may have stock_id = NULL.
                if ($variantId <= 0 && empty($byIdentifier[$identifier]['stock_id'])) {
                    saleFail('Identifier ' . $identifier . ' is not linked to a stock record. Please reconcile this unit before selling.');
                }
            }

            $serialRowsByLine[$lineKey] = $byIdentifier;
        }
    }

    if ($customer_type === 'new') {
        if ($customer_email !== '') {
            $dupStmt = $pdo->prepare("SELECT id FROM customers WHERE company_id = ? AND email = ? AND status = '1' LIMIT 1 FOR UPDATE");
            $dupStmt->execute([$company_id, $customer_email]);
            if ($dupStmt->fetchColumn()) saleFail('A customer with this email address already exists. Select the existing customer instead.');
        }
        $stmtCust = $pdo->prepare('INSERT INTO customers (company_id, location_id, fullname, email, phone, created_at, time_created, created_by, status) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?, 1)');
        $stmtCust->execute([$company_id, $location_id, $customer_name, $customer_email, $customer_phone, $user_id]);
        $customer_id = (int)$pdo->lastInsertId();
    }

    // Continue the existing invoice format using the highest suffix for this
    // domain/date prefix, protected by the named lock above.
    $sequenceStmt = $pdo->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_number, ?) AS UNSIGNED)), 0)
        FROM sales
        WHERE invoice_number LIKE ?
    ");
    $sequenceStmt->execute([strlen($invoicePrefix) + 1, $invoicePrefix . '%']);
    $nextSequence = (int)$sequenceStmt->fetchColumn() + 1;
    $invoice_number = $invoicePrefix . str_pad((string)$nextSequence, 6, '0', STR_PAD_LEFT);

    $saleStmt = $pdo->prepare('INSERT INTO sales (invoice_number, customer_id, company_id, location_id, user_id, customer_name, customer_phone, customer_email, grand_total, discount_percent, discount_amount, final_total, notes, created_at, payment_status, amount_paid, balance, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)');
    $saleStmt->execute([$invoice_number, $customer_id, $company_id, $location_id, $user_id, $customer_name, $customer_phone, $customer_email, $grand_total, $discount_percent, $discount_amount, $final_total, $sale_note, $payment_status, $amount_paid, $balance, $due_date]);
    $sale_id = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, variant_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)');
    $stockUpdate = $pdo->prepare('UPDATE stocks SET quantity = quantity - ? WHERE id = ? AND company_id = ? AND location_id = ? AND product_id = ? AND quantity >= ?');
    $variantStockUpdate = $pdo->prepare('UPDATE product_variant_stocks SET quantity = quantity - ? WHERE id = ? AND variant_id = ? AND product_id = ? AND company_id = ? AND location_id = ? AND quantity >= ?');
    $serialLink = $pdo->prepare('INSERT INTO sale_item_serials (sale_item_id, sale_id, serial_id, identifier_type, identifier_value, imei) VALUES (?, ?, ?, ?, ?, ?)');
    $serialUpdate = $pdo->prepare("
        UPDATE product_serials
        SET status = 'sold', current_sale_item_id = ?, updated_at = NOW()
        WHERE id = ?
          AND company_id = ?
          AND product_id = ?
          AND location_id = ?
          AND identifier_type = ?
          AND identifier_value = ?
          AND (variant_id <=> ?)
          AND status = 'in_stock'
    ");
    $eventStmt = $pdo->prepare("INSERT INTO product_serial_events (serial_id, company_id, product_id, event_type, from_stock_id, to_stock_id, from_location_id, to_location_id, sale_id, sale_item_id, reference, notes, performed_by, created_at) VALUES (?, ?, ?, 'sold', ?, NULL, ?, NULL, ?, ?, ?, ?, ?, NOW())");

    foreach ($validatedItems as $item) {
        $variantId = (int)($item['variant_id'] ?? 0);
        $lineKey = (int)$item['product_id'] . ':' . $variantId;

        $itemStmt->execute([
            $sale_id,
            (int)$item['product_id'],
            $variantId > 0 ? $variantId : null,
            $item['quantity'],
            $item['unit_price'],
            $item['total_price']
        ]);
        $sale_item_id = (int)$pdo->lastInsertId();

        if ($variantId > 0) {
            // Variant stock is stored separately from the product's legacy stocks table.
            $variantStock = $variantStockByLine[$lineKey] ?? null;
            if (!$variantStock) {
                throw new RuntimeException('Variant stock record could not be found for ' . $item['name'] . '.');
            }

            $variantStockUpdate->execute([
                (float)$item['quantity'],
                (int)$variantStock['id'],
                $variantId,
                (int)$item['product_id'],
                $company_id,
                $location_id,
                (float)$item['quantity']
            ]);

            if ($variantStockUpdate->rowCount() !== 1) {
                throw new RuntimeException('Variant stock changed while completing the sale. Please retry.');
            }
        } elseif ($item['identifier_tracking'] !== 'none') {
            // Each tracked product unit deducts from the precise stock row linked to its identifier.
            $byIdentifier = $serialRowsByLine[$lineKey] ?? [];
            foreach ($item['identifiers'] as $identifier) {
                $serialForStock = $byIdentifier[$identifier] ?? null;
                if (!$serialForStock || $serialForStock['status'] !== 'in_stock' || empty($serialForStock['stock_id'])) {
                    throw new RuntimeException('Identifier ' . $identifier . ' is no longer available or has no stock link.');
                }

                $stockUpdate->execute([
                    1,
                    (int)$serialForStock['stock_id'],
                    $company_id,
                    $location_id,
                    (int)$item['product_id'],
                    1
                ]);

                if ($stockUpdate->rowCount() !== 1) {
                    throw new RuntimeException('The stock quantity linked to identifier ' . $identifier . ' is insufficient. Reconcile stock before completing this sale.');
                }
            }
        } else {
            // Ordinary non-variant products may be distributed across multiple stock-in rows.
            $remaining = (float)$item['quantity'];

            foreach ($stockRowsByProduct[(int)$item['product_id']] ?? [] as $stockRow) {
                if ($remaining <= 0.00001) break;

                $rowQty = (float)$stockRow['quantity'];
                if ($rowQty <= 0) continue;

                $deduct = min($remaining, $rowQty);
                $stockUpdate->execute([
                    $deduct,
                    (int)$stockRow['id'],
                    $company_id,
                    $location_id,
                    (int)$item['product_id'],
                    $deduct
                ]);

                if ($stockUpdate->rowCount() !== 1) {
                    throw new RuntimeException('Stock changed while completing the sale. Please retry.');
                }

                $remaining -= $deduct;
            }

            if ($remaining > 0.00001) {
                throw new RuntimeException('Could not allocate the full quantity from stock. Please retry.');
            }
        }

        if ($item['identifier_tracking'] !== 'none') {
            $byIdentifier = $serialRowsByLine[$lineKey] ?? [];

            foreach ($item['identifiers'] as $identifier) {
                $serial = $byIdentifier[$identifier] ?? null;
                if (!$serial || $serial['status'] !== 'in_stock') {
                    throw new RuntimeException('Identifier ' . $identifier . ' is no longer available.');
                }

                $serialUpdate->execute([
                    $sale_item_id,
                    (int)$serial['id'],
                    $company_id,
                    (int)$item['product_id'],
                    $location_id,
                    $item['identifier_type'],
                    $identifier,
                    $variantId > 0 ? $variantId : null
                ]);

                if ($serialUpdate->rowCount() !== 1) {
                    throw new RuntimeException('Identifier ' . $identifier . ' was sold by another transaction or no longer matches this product/variant.');
                }

                $legacyImei = $item['identifier_type'] === 'imei' ? $identifier : null;
                $serialLink->execute([
                    $sale_item_id,
                    $sale_id,
                    (int)$serial['id'],
                    $item['identifier_type'],
                    $identifier,
                    $legacyImei
                ]);

                $eventStmt->execute([
                    (int)$serial['id'],
                    $company_id,
                    (int)$item['product_id'],
                    $serial['stock_id'] !== null ? (int)$serial['stock_id'] : null,
                    $location_id,
                    $sale_id,
                    $sale_item_id,
                    $invoice_number,
                    'Sold through sales invoice ' . $invoice_number,
                    $user_id
                ]);
            }
        }
    }

    if ($payment_status === 'partial' && $balance > 0) {
        $debtStmt = $pdo->prepare("INSERT INTO customer_debts (transaction_id, invoice_number, debt_by, customer_id, company_id, location_id, debt_type, total_amount, amount_paid, balance, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Sales', ?, ?, ?, ?, 'Unpaid', NOW())");
        $debtStmt->execute([$sale_id, $invoice_number, $user_id, $customer_id, $company_id, $location_id, $final_total, $amount_paid, $balance, $due_date]);
    }

    $invoice_dir = __DIR__ . '/../invoices';
    if (!is_dir($invoice_dir) && !mkdir($invoice_dir, 0775, true) && !is_dir($invoice_dir)) throw new RuntimeException('Invoice directory could not be created.');
    $invoice_file = $invoice_dir . '/invoice_' . $invoice_number . '.pdf';
    $invoice_relative = '../invoices/invoice_' . $invoice_number . '.pdf';

    // FPDF's built-in Arial font uses Windows-1252, not UTF-8. Convert all
    // dynamic text before drawing it so en/em dashes and variant labels render
    // correctly instead of becoming sequences such as "â€“".
    $invoicePdfText = static function ($value): string {
        $value = (string)$value;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
            if ($converted !== false) return $converted;
        }
        return preg_replace('/[^\\x20-\\x7E\\xA0-\\xFF]/', '', $value) ?? '';
    };

    class InvoicePDF extends FPDF {
        public $company = [];
        public $invoiceNumber = '';
        public $invoiceDate = '';
        public $paymentLabel = '';
        public $statusLabel = '';
        public $textConverter;

        function RoundedRect($x, $y, $w, $h, $r, $style = '') {
            // Rounded-card visual fallback using a clean rectangle (FPDF core).
            $this->Rect($x, $y, $w, $h, $style);
        }

        function T($value): string {
            return is_callable($this->textConverter) ? ($this->textConverter)($value) : (string)$value;
        }

        function Header() {
            $navy = [18, 35, 62];
            $teal = [16, 132, 133];
            $this->SetFillColor($navy[0], $navy[1], $navy[2]);
            $this->Rect(0, 0, 210, 39, 'F');
            $this->SetFillColor($teal[0], $teal[1], $teal[2]);
            $this->Rect(0, 39, 210, 2, 'F');

            $logoPath = __DIR__ . '/../uploads/logos/' . ($this->company['company_logo'] ?? '');
            if (!empty($this->company['company_logo']) && is_file($logoPath)) {
                $this->Image($logoPath, 12, 8, 25, 0, '', '');
                $companyX = 43;
                $companyWidth = 105;
            } else {
                $companyX = 13;
                $companyWidth = 125;
            }

            $this->SetTextColor(255, 255, 255);
            $this->SetXY($companyX, 8);
            $this->SetFont('Arial', 'B', 15);
            $this->Cell($companyWidth, 7, $this->T($this->company['company_name'] ?? 'Company'), 0, 1, 'L');
            $this->SetX($companyX);
            $this->SetFont('Arial', '', 8);
            $address = trim((string)($this->company['company_office_address'] ?? ''));
            if ($address !== '') $this->MultiCell($companyWidth, 4, $this->T($address), 0, 'L');
            $contact = trim((string)($this->company['company_mobile'] ?? ''));
            $email = trim((string)($this->company['company_email'] ?? ''));
            $this->SetX($companyX);
            $this->Cell($companyWidth, 4, $this->T(implode('  |  ', array_filter([$contact, $email]))), 0, 0, 'L');

            $this->SetXY(151, 7);
            $this->SetFont('Arial', 'B', 23);
            $this->Cell(47, 10, 'INVOICE', 0, 1, 'R');
            $this->SetX(151);
            $this->SetFont('Arial', 'B', 8);
            $this->SetTextColor(190, 211, 224);
            $this->Cell(47, 5, $this->T($this->statusLabel), 0, 1, 'R');
            $this->SetX(151);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(47, 5, $this->T($this->invoiceNumber), 0, 1, 'R');
            $this->SetX(151);
            $this->Cell(47, 5, $this->T($this->invoiceDate), 0, 0, 'R');
            $this->SetTextColor(30, 41, 59);
            $this->SetY(47);
        }

        function Footer() {
            $this->SetY(-17);
            $this->SetDrawColor(220, 227, 234);
            $this->Line(12, $this->GetY(), 198, $this->GetY());
            $this->SetY(-14);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(105, 119, 136);
            $this->Cell(125, 6, 'Thank you for your business.', 0, 0, 'L');
            $this->Cell(61, 6, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
        }

        function NbLines($w, $txt) {
            $cw = &$this->CurrentFont['cw'];
            if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
            $s = str_replace("\r", '', (string)$txt);
            $nb = strlen($s);
            if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
            $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
            while ($i < $nb) {
                $c = $s[$i];
                if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
                if ($c == ' ') $sep = $i;
                $l += $cw[$c] ?? 0;
                if ($l > $wmax) {
                    if ($sep == -1) { if ($i == $j) $i++; }
                    else $i = $sep + 1;
                    $sep = -1; $j = $i; $l = 0; $nl++;
                } else $i++;
            }
            return $nl;
        }

        function SectionHeading($x, $y, $width, $heading, $subheading = '') {
            $this->SetXY($x, $y);
            $this->SetFont('Arial', 'B', 9);
            $this->SetTextColor(16, 132, 133);
            $this->Cell($width, 5, strtoupper($this->T($heading)), 0, 1, 'L');
            if ($subheading !== '') {
                $this->SetX($x);
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(75, 88, 105);
                $this->MultiCell($width, 4.3, $this->T($subheading), 0, 'L');
            }
        }
    }

    $pdf = new InvoicePDF('P', 'mm', 'A4');
    $pdf->company = $company;
    $pdf->invoiceNumber = $invoice_number;
    $pdf->invoiceDate = date('d M Y, h:i A', strtotime($created_at));
    $pdf->paymentLabel = $payment;
    $pdf->statusLabel = $payment_status === 'full' ? 'PAID IN FULL' : 'PAYMENT OUTSTANDING';
    $pdf->textConverter = $invoicePdfText;
    $pdf->AliasNbPages();
    $pdf->SetMargins(12, 47, 12);
    $pdf->SetAutoPageBreak(true, 22);
    $pdf->AddPage();

    // Seller and buyer information cards.
    $cardY = 47;
    $cardH = 37;
    $pdf->SetDrawColor(224, 231, 238);
    $pdf->SetFillColor(249, 251, 253);
    $pdf->RoundedRect(12, $cardY, 90, $cardH, 2, 'DF');
    $pdf->RoundedRect(108, $cardY, 90, $cardH, 2, 'DF');
    $pdf->SectionHeading(17, 51, 80, 'From', (string)($company['company_name'] ?? ''));
    $pdf->SetXY(17, 63);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(65, 78, 94);
    $sellerAddress = trim((string)($company['company_office_address'] ?? ''));
    if ($sellerAddress !== '') $pdf->MultiCell(80, 4, $invoicePdfText($sellerAddress), 0, 'L');
    $sellerContact = trim((string)($company['company_mobile'] ?? ''));
    $sellerEmail = trim((string)($company['company_email'] ?? ''));
    $pdf->SetX(17);
    $pdf->Cell(80, 4, $invoicePdfText(implode('  |  ', array_filter([$sellerContact, $sellerEmail]))), 0, 0, 'L');

    $pdf->SectionHeading(113, 51, 80, 'Bill to', (string)$customer_name);
    $pdf->SetXY(113, 63);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(65, 78, 94);
    if ($customer_email !== '') $pdf->Cell(80, 4, $invoicePdfText('Email: ' . $customer_email), 0, 1, 'L');
    $pdf->SetX(113);
    if ($customer_phone !== '') $pdf->Cell(80, 4, $invoicePdfText('Phone: ' . $customer_phone), 0, 1, 'L');

    $pdf->SetY(91);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(18, 35, 62);
    $pdf->Cell(0, 6, 'ITEMISED PURCHASE', 0, 1, 'L');

    // Premium item table. Variant labels are printed in the same UTF-8-safe path
    // as all other descriptions; tracked identifiers remain individually listed.
    $w_sn = 9; $w_desc = 62; $w_serial = 45; $w_qty = 14; $w_unit = 28; $w_total = 28;
    $drawInvoiceTableHeader = function() use ($pdf, $w_sn, $w_desc, $w_serial, $w_qty, $w_unit, $w_total) {
        $pdf->SetX(12);
        $pdf->SetFillColor(18, 35, 62);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($w_sn, 8, 'No.', 0, 0, 'C', true);
        $pdf->Cell($w_desc, 8, 'PRODUCT / VARIANT', 0, 0, 'L', true);
        $pdf->Cell($w_serial, 8, 'IMEI / SERIAL / VIN', 0, 0, 'C', true);
        $pdf->Cell($w_qty, 8, 'QTY', 0, 0, 'C', true);
        $pdf->Cell($w_unit, 8, 'UNIT PRICE', 0, 0, 'R', true);
        $pdf->Cell($w_total, 8, 'AMOUNT', 0, 1, 'R', true);
        $pdf->SetTextColor(30, 41, 59);
    };
    $drawInvoiceTableHeader();

    $subtotal = 0.0;
    foreach ($validatedItems as $idx => $item) {
        $subtotal += (float)$item['total_price'];
        $description = $invoicePdfText($item['name']);
        $serialText = ($item['identifier_tracking'] ?? 'none') !== 'none'
            ? $invoicePdfText(implode("\n", $item['identifiers'])) : '';
        $pdf->SetFont('Arial', '', 8.5);
        $descLines = $pdf->NbLines($w_desc - 5, $description);
        $serialLines = $serialText === '' ? 1 : $pdf->NbLines($w_serial - 4, $serialText);
        $rowHeight = max(11, 4.8 * max($descLines, $serialLines));

        if ($pdf->GetY() + $rowHeight > 263) {
            $pdf->AddPage();
            $drawInvoiceTableHeader();
        }
        $x = 12;
        $y = $pdf->GetY();
        $pdf->SetFillColor(($idx % 2) ? 255 : 247, ($idx % 2) ? 255 : 250, ($idx % 2) ? 255 : 252);
        $pdf->Rect($x, $y, 186, $rowHeight, 'F');
        $pdf->SetDrawColor(229, 235, 241);
        $pdf->Line($x, $y + $rowHeight, $x + 186, $y + $rowHeight);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(90, 105, 122);
        $pdf->SetXY($x, $y + max(1, ($rowHeight - 5) / 2));
        $pdf->Cell($w_sn, 5, (string)($idx + 1), 0, 0, 'C');
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetXY($x + $w_sn + 2, $y + 2);
        $pdf->MultiCell($w_desc - 4, 4.8, $description, 0, 'L');
        if ($serialText !== '') {
            $pdf->SetFont('Arial', '', 7.5);
            $pdf->SetTextColor(75, 88, 105);
            $pdf->SetXY($x + $w_sn + $w_desc + 2, $y + 2);
            $pdf->MultiCell($w_serial - 4, 4.5, $serialText, 0, 'C');
        }
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetXY($x + $w_sn + $w_desc + $w_serial, $y + max(1, ($rowHeight - 5) / 2));
        $pdf->Cell($w_qty, 5, (string)$item['quantity'], 0, 0, 'C');
        $pdf->Cell($w_unit, 5, number_format((float)$item['unit_price'], 2), 0, 0, 'R');
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->Cell($w_total, 5, number_format((float)$item['total_price'], 2), 0, 1, 'R');
        $pdf->SetY($y + $rowHeight);
    }

    // Summary panel, aligned to the right for a clear financial hierarchy.
    $pdf->Ln(7);
    $summaryX = 112;
    $summaryW = 86;
    $summaryY = $pdf->GetY();
    if ($summaryY + 48 > 265) { $pdf->AddPage(); $summaryY = $pdf->GetY(); }
    $pdf->SetDrawColor(224, 231, 238);
    $pdf->SetFillColor(249, 251, 253);
    $pdf->RoundedRect($summaryX, $summaryY, $summaryW, 47, 2, 'DF');
    $pdf->SetXY($summaryX + 6, $summaryY + 4);
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->SetTextColor(90, 105, 122);
    $pdf->Cell(48, 6, 'Subtotal', 0, 0, 'L');
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(26, 6, 'NGN ' . number_format($grand_total, 2), 0, 1, 'R');
    $pdf->SetX($summaryX + 6);
    $pdf->SetTextColor(90, 105, 122);
    $pdf->Cell(48, 6, 'Discount (' . number_format($discount_percent, 0) . '%)', 0, 0, 'L');
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(26, 6, '- NGN ' . number_format($discount_amount, 2), 0, 1, 'R');
    $pdf->SetDrawColor(220, 227, 234);
    $pdf->Line($summaryX + 6, $pdf->GetY() + 1, $summaryX + $summaryW - 6, $pdf->GetY() + 1);
    $pdf->SetXY($summaryX + 6, $pdf->GetY() + 3);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(18, 35, 62);
    $pdf->Cell(48, 7, 'NET TOTAL', 0, 0, 'L');
    $pdf->Cell(26, 7, 'NGN ' . number_format($final_total, 2), 0, 1, 'R');
    $pdf->SetX($summaryX + 6);
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->SetTextColor(90, 105, 122);
    $pdf->Cell(48, 6, 'Amount paid', 0, 0, 'L');
    $pdf->SetTextColor(16, 132, 133);
    $pdf->Cell(26, 6, 'NGN ' . number_format($amount_paid, 2), 0, 1, 'R');
    $pdf->SetX($summaryX + 6);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor($balance > 0 ? 170 : 16, $balance > 0 ? 75 : 132, $balance > 0 ? 55 : 133);
    $pdf->Cell(48, 6, 'BALANCE DUE', 0, 0, 'L');
    $pdf->Cell(26, 6, 'NGN ' . number_format($balance, 2), 0, 1, 'R');

    $contentBottom = max($pdf->GetY(), $summaryY + 47);
    if ($payment_status === 'partial') {
        $pdf->SetXY(12, $contentBottom + 3);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(170, 75, 55);
        $pdf->Cell(90, 5, $invoicePdfText('Payment due: ' . date('d M Y', strtotime($due_date))), 0, 1, 'L');
    }

    $notesY = max($pdf->GetY() + 6, $contentBottom + 8);
    if ($notesY + 23 > 270) { $pdf->AddPage(); $notesY = $pdf->GetY(); }
    $pdf->SetXY(12, $notesY);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor(16, 132, 133);
    $pdf->Cell(80, 5, 'NOTES', 0, 1, 'L');
    $pdf->SetX(12);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(75, 88, 105);
    $noteText = $sale_note !== '' ? $sale_note : 'Thank you for your purchase. We appreciate your business.';
    $pdf->MultiCell(186, 4.5, $invoicePdfText($noteText), 0, 'L');

    // A clear validation notice is printed on every invoice, regardless of any
    // optional sale note entered by the cashier.
    $pdf->Ln(2);
    $pdf->SetX(12);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(170, 75, 55);
    $pdf->Cell(186, 5, 'OFFICIAL VALIDATION NOTICE', 0, 1, 'L');
    $pdf->SetX(12);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(75, 88, 105);
    $stampNotice = 'For verification and transaction security, this invoice is valid only when it bears the official stamp of the issuing office. Any invoice presented without the authorised office stamp must not be accepted.';
    $pdf->MultiCell(186, 4.5, $invoicePdfText($stampNotice), 0, 'L');

    $signatureY = max($pdf->GetY() + 8, $notesY + 16);
    if ($signatureY + 15 > 275) { $pdf->AddPage(); $signatureY = $pdf->GetY() + 5; }
    $pdf->SetXY(12, $signatureY);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(75, 5, 'CUSTOMER ACKNOWLEDGEMENT', 0, 1, 'L');
    $pdf->SetXY(12, $signatureY + 12);
    $pdf->SetDrawColor(150, 163, 178);
    $pdf->Line(12, $signatureY + 10, 82, $signatureY + 10);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetTextColor(105, 119, 136);
    $pdf->Cell(75, 4, 'Customer signature', 0, 0, 'L');
    $pdf->Output('F', $invoice_file);

    $pdo->commit();
    $committed = true;

    if ($invoiceLockAcquired) {
        try {
            $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $releaseStmt->execute([$invoiceLockName]);
        } catch (Throwable $lockError) {
            // The sale is already committed; a lock-release warning must not
            // make the user believe the transaction failed.
            error_log('Invoice lock release failed after commit: ' . $lockError->getMessage());
        }
        $invoiceLockAcquired = false;
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($invoice_file !== '' && is_file($invoice_file)) @unlink($invoice_file);

    if ($invoiceLockAcquired) {
        try {
            $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $releaseStmt->execute([$invoiceLockName]);
        } catch (Throwable $lockError) {
            error_log('Invoice lock release failed: ' . $lockError->getMessage());
        }
        $invoiceLockAcquired = false;
    }

    error_log('Sale processing failed: ' . $e->getMessage());
    saleFail('The sale could not be completed. Please check the details and try again. If the problem continues, contact your administrator.');
}

// External notifications happen only after the database commit; a mail/SMS failure does not undo the sale.
try {
    $smtp_username = $_SESSION['customercare'] ?? '';
    $smtp_password = $_SESSION['customer_care_password'] ?? '';
    if ($smtp_username !== '' && $smtp_password !== '' && $customer_email !== '') {
        $mail = new PHPMailer(true);
        $mail->isSMTP(); $mail->Host = 'smtp.hostinger.com'; $mail->SMTPAuth = true;
        $mail->Username = $smtp_username; $mail->Password = $smtp_password;
        $mail->SMTPSecure = 'ssl'; $mail->Port = 465;
        $mail->setFrom($smtp_username, (string)$company['company_name']);
        $mail->addAddress($customer_email);
        if (!empty($company['company_email'])) $mail->addAddress((string)$company['company_email']);
        $mail->Subject = 'Invoice ' . $invoice_number;
        $mail->Body = "Dear {$customer_name},\n\nThank you for your patronage. Please find attached your invoice {$invoice_number} for your recent purchase with {$company['company_name']}.\n\nKind regards,\n{$company['company_name']} Team";
        $mail->addAttachment($invoice_file);
        $mail->send();
    }
} catch (Throwable $e) {
    error_log('Sale invoice email failed: ' . $e->getMessage());
}

try {
    $formatted_phone = saleFormatNigerianPhone((string)$customer_phone);
    $termii_api_key = 'YOUR_TERMII_API_KEY';
    $sender_id = 'YOUR_BRAND';
    if ($formatted_phone !== '' && $termii_api_key !== 'YOUR_TERMII_API_KEY') {
        $invoice_url = 'https://' . $active_domain . '/invoices/invoice_' . $invoice_number . '.pdf';
        $sms_message = "Hello {$customer_name}, thanks for your purchase at {$company['company_name']}. Invoice #{$invoice_number} for NGN " . number_format($final_total, 2) . " has been issued. View: {$invoice_url}";
        $payload = ['to' => $formatted_phone, 'from' => $sender_id, 'sms' => $sms_message, 'type' => 'plain', 'channel' => 'generic', 'api_key' => $termii_api_key];
        $ch = curl_init('https://api.ng.termii.com/api/sms/send');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($payload)]);
        curl_exec($ch); curl_close($ch);
    }
} catch (Throwable $e) {
    error_log('Sale SMS notification failed: ' . $e->getMessage());
}

$invoiceUrlJs = json_encode($invoice_relative, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
echo "<script>alert('Sale created successfully. Invoice generated.'); window.open({$invoiceUrlJs}, '_blank'); window.location.href='sales.php';</script>";
exit;
?>
