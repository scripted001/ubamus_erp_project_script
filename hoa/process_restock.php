<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: restock.php');
    exit;
}

$company_id  = (int)($_SESSION['company_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);
$recorded_by = (int)($_SESSION['user_id'] ?? 0);

if ($company_id <= 0 || $location_id <= 0 || $recorded_by <= 0) {
    processRestockFail('Your session has expired. Please sign in again.');
}

$stock_id = filter_var($_POST['stock_id'] ?? null, FILTER_VALIDATE_INT);
$product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$variantRaw = trim((string)($_POST['variant_id'] ?? ''));
$variant_id = $variantRaw === '' ? 0 : filter_var($variantRaw, FILTER_VALIDATE_INT);
$add_quantity = filter_var($_POST['add_quantity'] ?? null, FILTER_VALIDATE_INT);

if (!$stock_id || !$product_id || $variant_id === false || $variant_id < 0) {
    processRestockFail('Invalid stock record. Refresh the inventory page and try again.');
}
if (!$add_quantity || $add_quantity <= 0 || $add_quantity > 500) {
    processRestockFail('Please enter a quantity between 1 and 500 units.');
}

function processRestockFail(string $message): void
{
    echo '<script>alert(' . json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ');window.history.back();</script>';
    exit;
}

function processRestockValidImei(string $imei): bool
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
    return ($sum % 10) === 0;
}

$invoicePath = null;
$absoluteInvoicePath = null;
$originalName = null;
$mimeType = null;
$upload = null;

try {
    // Optional supplier invoice upload.
    $invoiceWasSubmitted = isset($_FILES['stock_invoice'])
        && (($_FILES['stock_invoice']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

    if ($invoiceWasSubmitted) {
        $upload = $_FILES['stock_invoice'];
        if (!isset($upload['error']) || is_array($upload['error'])) {
            throw new RuntimeException('Invalid invoice upload.');
        }
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The invoice exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'The invoice exceeds the form upload limit.',
                UPLOAD_ERR_PARTIAL => 'The invoice was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server is missing its temporary upload folder.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded invoice.',
                UPLOAD_ERR_EXTENSION => 'The server blocked the invoice upload.',
            ];
            throw new RuntimeException($uploadErrors[$upload['error']] ?? 'The invoice upload failed.');
        }
        if (!is_uploaded_file($upload['tmp_name'])) {
            throw new RuntimeException('The uploaded invoice could not be verified.');
        }
        if ((int)$upload['size'] <= 0 || (int)$upload['size'] > 20 * 1024 * 1024) {
            throw new RuntimeException('Invoice must be a non-empty file no larger than 20 MB.');
        }

        $originalName = basename((string)$upload['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedMimeTypes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
        ];
        if (!isset($allowedMimeTypes[$extension]) || !class_exists('finfo')) {
            throw new RuntimeException('Invoice must be a PDF, JPG, JPEG or PNG file.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($upload['tmp_name']);
        if (!in_array($mimeType, $allowedMimeTypes[$extension], true)) {
            throw new RuntimeException('The invoice file contents do not match its file type.');
        }

        $uploadDirectory = dirname(__DIR__) . '/uploads/stock_in_invoices';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Could not create the invoice upload directory.');
        }
        if (!is_writable($uploadDirectory)) {
            throw new RuntimeException('The invoice upload directory is not writable.');
        }
        $htaccessPath = $uploadDirectory . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            @file_put_contents(
                $htaccessPath,
                "Options -Indexes\n<FilesMatch \"\\.(php[0-9]?|phtml|phar|cgi|pl|sh)$\">\n    Require all denied\n</FilesMatch>\n"
            );
        }
        $storedFileName = bin2hex(random_bytes(24)) . '.' . $extension;
        $absoluteInvoicePath = $uploadDirectory . '/' . $storedFileName;
        $invoicePath = '../uploads/stock_in_invoices/' . $storedFileName;
        if (!move_uploaded_file($upload['tmp_name'], $absoluteInvoicePath)) {
            $absoluteInvoicePath = null;
            $invoicePath = null;
            throw new RuntimeException('Could not save the invoice file.');
        }
        @chmod($absoluteInvoicePath, 0640);
    }

    $pdo->beginTransaction();

    // Lock and validate the parent stock row and product ownership.
    $baseStmt = $pdo->prepare("
        SELECT
            s.id AS stock_id, s.product_id, s.company_id, s.location_id,
            s.quantity AS parent_quantity, s.supplier,
            p.name AS product_name, p.cost_price,
            p.identifier_tracking, p.imei_tracking, p.has_variants,
            LOWER(TRIM(COALESCE(pc.stock_type, ''))) AS product_stock_type
        FROM stocks s
        INNER JOIN products p ON p.id = s.product_id AND p.company_id = s.company_id
        LEFT JOIN product_categories pc ON pc.id = p.category_id
        WHERE s.id = ? AND s.product_id = ?
          AND s.company_id = ? AND s.location_id = ? AND p.company_id = ?
        LIMIT 1 FOR UPDATE
    ");
    $baseStmt->execute([$stock_id, $product_id, $company_id, $location_id, $company_id]);
    $baseStock = $baseStmt->fetch(PDO::FETCH_ASSOC);
    if (!$baseStock) {
        throw new RuntimeException('Stock record not found or you do not have access to it.');
    }

    $variant = null;
    if ($variant_id > 0) {
        if ((int)$baseStock['has_variants'] !== 1) {
            throw new RuntimeException('This product does not use variants. Refresh the inventory page.');
        }

        $variantStmt = $pdo->prepare("
            SELECT
                pv.id AS variant_id, pv.product_id, pv.company_id,
                pv.variant_name, pv.sku, pv.barcode, pv.cost_price,
                pv.tracking_policy, pv.is_active,
                pvs.quantity AS variant_quantity
            FROM product_variants pv
            INNER JOIN product_variant_stocks pvs
                ON pvs.variant_id = pv.id
               AND pvs.product_id = pv.product_id
               AND pvs.company_id = pv.company_id
            WHERE pv.id = ? AND pv.product_id = ? AND pv.company_id = ?
              AND pv.is_active = 1 AND pvs.location_id = ?
            LIMIT 1 FOR UPDATE
        ");
        $variantStmt->execute([$variant_id, $product_id, $company_id, $location_id]);
        $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);
        if (!$variant) {
            throw new RuntimeException('The selected variant stock was not found at this location or is no longer active.');
        }
        $trackingMode = strtolower(trim((string)$baseStock['identifier_tracking']));
        if ($trackingMode === '' || $trackingMode === 'none') {
            if ((int)$baseStock['imei_tracking'] === 1) $trackingMode = 'imei';
            else $trackingMode = 'none';
        }
        $variantPolicy = strtolower(trim((string)$variant['tracking_policy']));
        if ($variantPolicy !== '' && $variantPolicy !== 'inherit') $trackingMode = $variantPolicy;
        $current_quantity = (int)$variant['variant_quantity'];
        $supplier = (string)$baseStock['supplier'];
        $productDisplayName = (string)$baseStock['product_name'] . ' — ' . (string)$variant['variant_name'];
    } else {
        if ((int)$baseStock['has_variants'] === 1) {
            throw new RuntimeException('Select a specific variant before restocking this product.');
        }
        $trackingMode = strtolower(trim((string)$baseStock['identifier_tracking']));
        if ($trackingMode === '' || $trackingMode === 'none') {
            if ((int)$baseStock['imei_tracking'] === 1) $trackingMode = 'imei';
            else $trackingMode = 'none';
        }
        $current_quantity = (int)$baseStock['parent_quantity'];
        $supplier = (string)$baseStock['supplier'];
        $productDisplayName = (string)$baseStock['product_name'];
    }

    if (!in_array($trackingMode, ['none', 'imei', 'serial', 'vin'], true)) {
        throw new RuntimeException('This product or variant has an invalid identifier tracking setting.');
    }
    if ($trackingMode !== 'none' && strtolower(trim((string)$baseStock['product_stock_type'])) !== 'sales only') {
        throw new RuntimeException('Identifier tracking is only supported for Sales Only products.');
    }

    // Validate one unique identifier per incoming tracked unit.
    $identifiers = [];
    if ($trackingMode !== 'none') {
        $postedIdentifiers = $_POST['identifiers'] ?? [];
        if (!is_array($postedIdentifiers) || count($postedIdentifiers) !== $add_quantity) {
            $label = ['imei' => 'IMEI', 'serial' => 'serial number', 'vin' => 'VIN'][$trackingMode];
            throw new RuntimeException('Enter exactly one ' . $label . ' for every unit being restocked.');
        }
        $seen = [];
        foreach ($postedIdentifiers as $index => $rawIdentifier) {
            if (!is_string($rawIdentifier) && !is_numeric($rawIdentifier)) {
                throw new RuntimeException('Invalid identifier at unit #' . ($index + 1) . '.');
            }
            $identifier = trim((string)$rawIdentifier);
            if ($trackingMode === 'imei') {
                if (!processRestockValidImei($identifier)) {
                    throw new RuntimeException('IMEI #' . ($index + 1) . ' must be a valid 15-digit IMEI.');
                }
            } elseif ($trackingMode === 'vin') {
                $identifier = strtoupper($identifier);
                if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $identifier)) {
                    throw new RuntimeException('VIN #' . ($index + 1) . ' must contain exactly 17 valid characters (I, O and Q are not allowed).');
                }
            } else {
                if ($identifier === '' || preg_match_all('/./us', $identifier, $chars) === false
                    || count($chars[0]) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $identifier)) {
                    throw new RuntimeException('Serial number #' . ($index + 1) . ' must contain 1–100 characters.');
                }
            }
            $key = $trackingMode === 'vin' ? strtoupper($identifier) : $identifier;
            if (isset($seen[$key])) {
                throw new RuntimeException('The same identifier was entered more than once: ' . $identifier);
            }
            $seen[$key] = true;
            $identifiers[] = $identifier;
        }

        $placeholders = implode(',', array_fill(0, count($identifiers), '?'));
        if ($trackingMode === 'imei') {
            $duplicateStmt = $pdo->prepare("
                SELECT identifier_value FROM product_serials
                WHERE identifier_type = 'imei' AND identifier_value IN ($placeholders)
                LIMIT 1
            ");
            $duplicateStmt->execute($identifiers);
        } else {
            $duplicateStmt = $pdo->prepare("
                SELECT identifier_value FROM product_serials
                WHERE company_id = ? AND identifier_type = ?
                  AND identifier_value IN ($placeholders)
                LIMIT 1
            ");
            $duplicateStmt->execute(array_merge([$company_id, $trackingMode], $identifiers));
        }
        $duplicate = $duplicateStmt->fetchColumn();
        if ($duplicate !== false) {
            $label = ['imei' => 'IMEI', 'serial' => 'serial number', 'vin' => 'VIN'][$trackingMode];
            throw new RuntimeException('This ' . $label . ' is already registered: ' . $duplicate);
        }
    }

    $new_quantity = $current_quantity + $add_quantity;

    if ($variant_id > 0) {
        // Variant quantity is authoritative; maintain the legacy parent stock as an aggregate.
        $updateVariant = $pdo->prepare("
            UPDATE product_variant_stocks
            SET quantity = COALESCE(quantity, 0) + ?, updated_at = NOW()
            WHERE company_id = ? AND product_id = ? AND variant_id = ? AND location_id = ?
            LIMIT 1
        ");
        $updateVariant->execute([$add_quantity, $company_id, $product_id, $variant_id, $location_id]);
        if ($updateVariant->rowCount() !== 1) {
            throw new RuntimeException('Unable to update the selected variant quantity.');
        }

        $updateParent = $pdo->prepare("
            UPDATE stocks SET quantity = COALESCE(quantity, 0) + ?
            WHERE id = ? AND product_id = ? AND company_id = ? AND location_id = ?
            LIMIT 1
        ");
        $updateParent->execute([$add_quantity, $stock_id, $product_id, $company_id, $location_id]);
        if ($updateParent->rowCount() !== 1) {
            throw new RuntimeException('Unable to update the parent inventory aggregate.');
        }
    } else {
        $update = $pdo->prepare("
            UPDATE stocks SET quantity = COALESCE(quantity, 0) + ?
            WHERE id = ? AND product_id = ? AND company_id = ? AND location_id = ?
            LIMIT 1
        ");
        $update->execute([$add_quantity, $stock_id, $product_id, $company_id, $location_id]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('Unable to update the stock quantity.');
        }
    }

    $transfer_id = 'RESTOCK-' . $stock_id . '-' . date('YmdHis') . ($variant_id > 0 ? '-V' . $variant_id : '');
    $stock_type = 'Restock';
    $created_at = date('Y-m-d H:i:s');

    $insertStockIn = $pdo->prepare("
        INSERT INTO stock_in (
            product_id, variant_id, stock_id, company_id, location_id,
            transfer_ID, quantity, type, supplier, created_at, recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStockIn->execute([
        $product_id,
        $variant_id > 0 ? $variant_id : null,
        $stock_id,
        $company_id,
        $location_id,
        $transfer_id,
        $add_quantity,
        $stock_type,
        $supplier,
        $created_at,
        $recorded_by
    ]);
    $stockInId = (int)$pdo->lastInsertId();

    // Register tracked units against the exact variant, when applicable.
    if ($trackingMode !== 'none') {
        $insertSerial = $pdo->prepare("
            INSERT INTO product_serials (
                product_id, variant_id, company_id, identifier_type, identifier_value,
                imei, stock_id, location_id, status, received_at, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'in_stock', NOW(), ?)
        ");
        $insertEvent = $pdo->prepare("
            INSERT INTO product_serial_events (
                serial_id, company_id, product_id, event_type,
                to_stock_id, to_location_id, reference, notes, performed_by, created_at
            ) VALUES (?, ?, ?, 'restock', ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($identifiers as $identifier) {
            $insertSerial->execute([
                $product_id,
                $variant_id > 0 ? $variant_id : null,
                $company_id,
                $trackingMode,
                $identifier,
                $trackingMode === 'imei' ? $identifier : null,
                $stock_id,
                $location_id,
                $recorded_by
            ]);
            $serialId = (int)$pdo->lastInsertId();
            $label = ['imei' => 'IMEI', 'serial' => 'serial number', 'vin' => 'VIN'][$trackingMode];
            $eventNotes = strtoupper($label) . ' registered during restock.';
            if ($variant_id > 0) $eventNotes .= ' Variant ID: ' . $variant_id . '.';
            $insertEvent->execute([
                $serialId,
                $company_id,
                $product_id,
                $stock_id,
                $location_id,
                $transfer_id,
                $eventNotes,
                $recorded_by
            ]);
        }
    }

    if ($invoicePath !== null && $absoluteInvoicePath !== null) {
        $insertInvoice = $pdo->prepare("
            INSERT INTO stock_in_invoices (
                stock_in_id, company_id, file_path, original_filename,
                mime_type, file_size, uploaded_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insertInvoice->execute([
            $stockInId, $company_id, $invoicePath, $originalName,
            $mimeType, (int)$upload['size'], $recorded_by
        ]);
    }

    $pdo->commit();

    $safeName = htmlspecialchars($productDisplayName, ENT_QUOTES, 'UTF-8');
    echo '<script>alert(' . json_encode(
        "Restock successful!\n\nProduct: " . $productDisplayName
        . "\nPrevious Stock: " . $current_quantity
        . "\nAdded Quantity: " . $add_quantity
        . "\nNew Stock: " . $new_quantity,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ');window.location.href="stock_transaction.php";</script>';
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    error_log('Restock Error: ' . $e->getMessage());

    if ($absoluteInvoicePath !== null && is_file($absoluteInvoicePath)) {
        @unlink($absoluteInvoicePath);
    }

    $userMessage = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'An error occurred while processing the restock. No stock changes were made.';

    processRestockFail($userMessage);
}
?>
