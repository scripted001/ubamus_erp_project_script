
<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function stockInFail(string $message): void
{
    echo '<script>alert(' .
        json_encode(
            $message,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) .
        ');window.history.back();</script>';
    exit;
}

function isValidImei(string $imei): bool
{
    if (!preg_match('/^[0-9]{15}$/', $imei)) {
        return false;
    }

    $sum = 0;

    for ($i = 0; $i < 15; $i++) {
        $digit = (int)$imei[$i];

        if ($i % 2 === 1) {
            $digit *= 2;

            if ($digit > 9) {
                $digit -= 9;
            }
        }

        $sum += $digit;
    }

    return ($sum % 10) === 0;
}

/**
 * Insert an event using the columns present in the installed event table.
 */
function insertSerialEvent(PDO $pdo, array $event): void
{
    static $columns = null;

    if ($columns === null) {
        $columns = [];

        $stmt = $pdo->query("SHOW COLUMNS FROM product_serial_events");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[$column['Field']] = true;
        }
    }

    $aliases = [
        'product_serial_id' => ['product_serial_id', 'serial_id'],
        'serial_id'         => ['serial_id', 'product_serial_id'],
        'product_id'        => ['product_id'],
        'company_id'        => ['company_id'],
        'event_type'        => ['event_type', 'action', 'event', 'type'],
        'from_status'       => ['from_status', 'old_status'],
        'to_status'         => ['to_status', 'new_status'],
        'from_stock_id'     => ['from_stock_id', 'previous_stock_id'],
        'to_stock_id'       => ['to_stock_id', 'stock_id'],
        'from_location_id'  => ['from_location_id', 'previous_location_id'],
        'to_location_id'    => ['to_location_id', 'location_id'],
        'reference_type'    => ['reference_type'],
        'reference_id'      => ['reference_id', 'stock_in_id'],
        'notes'             => ['notes', 'description'],
        'performed_by'      => ['performed_by', 'created_by', 'user_id'],
        'created_at'        => ['created_at'],
    ];

    $insert = [];

    foreach ($event as $key => $value) {
        foreach ($aliases[$key] ?? [$key] as $candidate) {
            if (isset($columns[$candidate])) {
                $insert[$candidate] = $value;
                break;
            }
        }
    }

    if (!$insert) {
        throw new RuntimeException(
            'The product serial event table does not contain compatible event columns.'
        );
    }

    $fieldSql = implode(
        ', ',
        array_map(
            static fn($field) => '`' . str_replace('`', '', $field) . '`',
            array_keys($insert)
        )
    );

    $placeholders = implode(', ', array_fill(0, count($insert), '?'));

    $stmt = $pdo->prepare(
        "INSERT INTO product_serial_events ($fieldSql) VALUES ($placeholders)"
    );

    $stmt->execute(array_values($insert));
}

/*
|--------------------------------------------------------------------------
| REQUEST / SESSION VALIDATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stockInFail('Invalid request.');
}

$companyId = (int)($_SESSION['company_id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);
$activeLocationId = (int)($_SESSION['location_id'] ?? 0);

if ($companyId <= 0 || $userId <= 0) {
    stockInFail('Your session has expired. Please log in again.');
}

if ($activeLocationId <= 0) {
    stockInFail('No active branch is selected. Please select a branch and try again.');
}

/*
|--------------------------------------------------------------------------
| VALIDATE POSTED DATA
|--------------------------------------------------------------------------
*/

$productId = filter_var(
    $_POST['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

$postedLocationId = filter_var(
    $_POST['location_id'] ?? null,
    FILTER_VALIDATE_INT
);

$supplierRaw = trim((string)($_POST['supplier'] ?? ''));

$supplierId = filter_var(
    $supplierRaw,
    FILTER_VALIDATE_INT
);

$quantityRaw = trim((string)($_POST['quantity'] ?? ''));

if (!$productId || $productId <= 0) {
    stockInFail('Please select a valid product.');
}

/*
 * Never trust the branch ID submitted by the browser.
 * The active branch in the authenticated session is authoritative.
 */
if (
    !$postedLocationId ||
    $postedLocationId <= 0 ||
    $postedLocationId !== $activeLocationId
) {
    stockInFail(
        'Your active branch has changed or the submitted branch is invalid. Refresh the page and try again.'
    );
}

$locationId = $activeLocationId;

if (!$supplierId || $supplierId <= 0) {
    stockInFail('Please select a valid supplier.');
}

if ($quantityRaw === '' || !is_numeric($quantityRaw)) {
    stockInFail('Please enter a valid stock quantity.');
}

$quantity = (float)$quantityRaw;

if (!is_finite($quantity) || $quantity <= 0) {
    stockInFail('Please enter a valid stock quantity greater than zero.');
}

$invoicePath = null;
$absoluteInvoicePath = null;
$transactionStarted = false;

try {

    /*
    |--------------------------------------------------------------------------
    | VERIFY USER, COMPANY AND ACTIVE BRANCH
    |--------------------------------------------------------------------------
    */

    $userStmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");

    $userStmt->execute([$userId, $companyId]);

    if (!$userStmt->fetchColumn()) {
        throw new RuntimeException(
            'Your user account could not be verified for this company.'
        );
    }

    $locationStmt = $pdo->prepare("
        SELECT id
        FROM locations
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");

    $locationStmt->execute([$locationId, $companyId]);

    if (!$locationStmt->fetchColumn()) {
        throw new RuntimeException(
            'Your active branch is invalid or does not belong to this company.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY SUPPLIER OWNERSHIP
    |--------------------------------------------------------------------------
    */

    $supplierStmt = $pdo->prepare("
        SELECT id
        FROM vendors
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");

    $supplierStmt->execute([$supplierId, $companyId]);

    if (!$supplierStmt->fetchColumn()) {
        throw new RuntimeException(
            'The selected supplier could not be found in your company records.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY PRODUCT OWNERSHIP AND STOCK TYPE
    |--------------------------------------------------------------------------
    */

    $productStmt = $pdo->prepare("
        SELECT
            p.id,
            p.company_id,
            COALESCE(NULLIF(p.identifier_tracking, ''), 'none') AS identifier_tracking,
            COALESCE(p.imei_tracking, 0) AS imei_tracking,
            pc.stock_type
        FROM products p
        LEFT JOIN product_categories pc
            ON pc.id = p.category_id
        WHERE p.id = ?
          AND p.company_id = ?
        LIMIT 1
    ");

    $productStmt->execute([$productId, $companyId]);

    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new RuntimeException(
            'The selected product could not be found in your company.'
        );
    }

    $trackingMode = strtolower(
        trim((string)($product['identifier_tracking'] ?? 'none'))
    );

    // Backward compatibility for older products.
    if (
        $trackingMode === 'none' &&
        (int)$product['imei_tracking'] === 1
    ) {
        $trackingMode = 'imei';
    }

    if (!in_array($trackingMode, ['none', 'imei', 'serial', 'vin'], true)) {
        throw new RuntimeException(
            'The selected product has an invalid identifier tracking setting.'
        );
    }

    $isTracked = $trackingMode !== 'none';

    /*
     * Initialize this before any identifier validation.
     * This prevents the previous undefined-variable issue.
     */
    $identifierLabels = [
        'imei'   => 'IMEI',
        'serial' => 'serial number',
        'vin'    => 'VIN',
    ];

    $identifierLabel = $identifierLabels[$trackingMode] ?? 'identifier';

    $stockType = strtolower(
        trim((string)($product['stock_type'] ?? ''))
    );

    if ($isTracked && $stockType !== 'sales only') {
        throw new RuntimeException(
            'IMEI, serial-number or VIN tracking is only permitted for physical products in the Sales Only category. Please correct the product settings.'
        );
    }

    if (
        $stockType === 'service only' ||
        $stockType === 'production only'
    ) {
        throw new RuntimeException(
            'This product category cannot be stocked through this page.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | IMEI / SERIAL NUMBER / VIN VALIDATION
    |--------------------------------------------------------------------------
    */

    $identifiers = [];

    if ($isTracked) {

        if (abs($quantity - round($quantity)) > 0.000001) {
            throw new RuntimeException(
                'Tracked stock must be entered as a whole number of units.'
            );
        }

        $unitCount = (int)round($quantity);

        if ($unitCount > 500) {
            throw new RuntimeException(
                'You can receive a maximum of 500 tracked units in one stock-in transaction.'
            );
        }

        $postedIdentifiers = $_POST['identifiers'] ?? ($_POST['imeis'] ?? []);

        if (
            !is_array($postedIdentifiers) ||
            count($postedIdentifiers) !== $unitCount
        ) {
            throw new RuntimeException(
                'Enter exactly one ' . $identifierLabel .
                ' for every unit being received.'
            );
        }

        $seen = [];

        foreach ($postedIdentifiers as $index => $rawIdentifier) {

            if (!is_scalar($rawIdentifier)) {
                throw new RuntimeException(
                    $identifierLabel . ' #' . ($index + 1) . ' is invalid.'
                );
            }

            $identifier = trim((string)$rawIdentifier);

            if ($trackingMode === 'imei') {

                if (!isValidImei($identifier)) {
                    throw new RuntimeException(
                        'IMEI #' . ($index + 1) .
                        ' is invalid. Enter a valid 15-digit IMEI number.'
                    );
                }

            } elseif ($trackingMode === 'vin') {

                $identifier = strtoupper($identifier);

                if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $identifier)) {
                    throw new RuntimeException(
                        'VIN #' . ($index + 1) .
                        ' is invalid. Enter exactly 17 characters using letters A–H, J–N, P, R–Z and digits 0–9. I, O and Q are not allowed.'
                    );
                }

            } else {

                $characterCount = preg_match_all(
                    '/./us',
                    $identifier,
                    $serialCharacters
                );

                if (
                    $identifier === '' ||
                    $characterCount === false ||
                    $characterCount > 100 ||
                    preg_match('/[\x00-\x1F\x7F]/u', $identifier)
                ) {
                    throw new RuntimeException(
                        'Serial number #' . ($index + 1) .
                        ' is invalid. Enter 1–100 characters without control characters.'
                    );
                }
            }

            $duplicateKey = $trackingMode === 'vin'
                ? strtoupper($identifier)
                : $identifier;

            if (isset($seen[$duplicateKey])) {
                throw new RuntimeException(
                    'The same ' . $identifierLabel .
                    ' was entered more than once: ' . $identifier
                );
            }

            $seen[$duplicateKey] = true;
            $identifiers[] = $identifier;
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($identifiers), '?')
        );

        if ($trackingMode === 'imei') {

            // IMEIs retain the original global uniqueness rule.
            $duplicateStmt = $pdo->prepare("
                SELECT identifier_value
                FROM product_serials
                WHERE identifier_type = 'imei'
                  AND identifier_value IN ($placeholders)
                LIMIT 1
            ");

            $duplicateStmt->execute($identifiers);

        } else {

            // Serial numbers and VINs remain unique per company and type.
            $duplicateStmt = $pdo->prepare("
                SELECT identifier_value
                FROM product_serials
                WHERE company_id = ?
                  AND identifier_type = ?
                  AND identifier_value IN ($placeholders)
                LIMIT 1
            ");

            $duplicateStmt->execute(
                array_merge(
                    [$companyId, $trackingMode],
                    $identifiers
                )
            );
        }

        $duplicateIdentifier = $duplicateStmt->fetchColumn();

        if ($duplicateIdentifier !== false) {
            throw new RuntimeException(
                'This ' . $identifierLabel .
                ' has already been registered in inventory: ' .
                $duplicateIdentifier
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OPTIONAL INVOICE UPLOAD
    |--------------------------------------------------------------------------
    */

    $invoiceWasSubmitted =
        isset($_FILES['stock_invoice']) &&
        (
            ($_FILES['stock_invoice']['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_NO_FILE
        );

    if ($invoiceWasSubmitted) {

        $upload = $_FILES['stock_invoice'];

        if (
            !isset($upload['error']) ||
            is_array($upload['error'])
        ) {
            throw new RuntimeException('Invalid invoice upload.');
        }

        if ($upload['error'] !== UPLOAD_ERR_OK) {

            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'The invoice exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'The invoice exceeds the form upload limit.',
                UPLOAD_ERR_PARTIAL    => 'The invoice was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server is missing its temporary upload folder.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded invoice.',
                UPLOAD_ERR_EXTENSION => 'The server blocked the invoice upload.',
            ];

            throw new RuntimeException(
                $uploadErrors[$upload['error']] ?? 'The invoice upload failed.'
            );
        }

        if (!is_uploaded_file($upload['tmp_name'])) {
            throw new RuntimeException(
                'The uploaded invoice could not be verified.'
            );
        }

        $maxFileSize = 20 * 1024 * 1024;

        if (
            (int)$upload['size'] <= 0 ||
            (int)$upload['size'] > $maxFileSize
        ) {
            throw new RuntimeException(
                'Invoice must be a non-empty file no larger than 20 MB.'
            );
        }

        $originalName = basename((string)$upload['name']);

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException(
                'Invoice must be a PDF, JPG, JPEG or PNG file.'
            );
        }

        if (!class_exists('finfo')) {
            throw new RuntimeException(
                'The server cannot validate uploaded file types.'
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($upload['tmp_name']);

        $allowedMimeTypes = [
            'pdf'  => ['application/pdf'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
        ];

        if (
            !isset($allowedMimeTypes[$extension]) ||
            !in_array($mimeType, $allowedMimeTypes[$extension], true)
        ) {
            throw new RuntimeException(
                'The invoice file contents do not match its file type.'
            );
        }

        $uploadDirectory = dirname(__DIR__) .
            '/uploads/stock_in_invoices';

        if (
            !is_dir($uploadDirectory) &&
            !mkdir($uploadDirectory, 0750, true) &&
            !is_dir($uploadDirectory)
        ) {
            throw new RuntimeException(
                'Could not create the invoice upload directory.'
            );
        }

        if (!is_writable($uploadDirectory)) {
            throw new RuntimeException(
                'The invoice upload directory is not writable.'
            );
        }

        $htaccessPath = $uploadDirectory . '/.htaccess';

        if (!file_exists($htaccessPath)) {
            @file_put_contents(
                $htaccessPath,
                "Options -Indexes\n" .
                "<FilesMatch \"\\.(php[0-9]?|phtml|phar|cgi|pl|sh)$\">\n" .
                "    Require all denied\n" .
                "</FilesMatch>\n"
            );
        }

        $storedFileName = bin2hex(random_bytes(24)) . '.' . $extension;

        $absoluteInvoicePath = $uploadDirectory . '/' . $storedFileName;

        $invoicePath =
            '../uploads/stock_in_invoices/' . $storedFileName;

        if (!move_uploaded_file(
            $upload['tmp_name'],
            $absoluteInvoicePath
        )) {
            $absoluteInvoicePath = null;
            $invoicePath = null;

            throw new RuntimeException(
                'Could not save the invoice file.'
            );
        }

        @chmod($absoluteInvoicePath, 0640);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK + STOCK-IN TRANSACTION
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();
    $transactionStarted = true;

    /*
     * Every stock lookup is scoped to the authenticated active branch.
     */
    $stockStmt = $pdo->prepare("
        SELECT id, quantity
        FROM stocks
        WHERE product_id = ?
          AND company_id = ?
          AND location_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stockStmt->execute([
        $productId,
        $companyId,
        $locationId
    ]);

    $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

    $transferId =
        'STOCK-IN-' .
        date('YmdHis') .
        '-' .
        strtoupper(bin2hex(random_bytes(4)));

    if ($stock) {

        $stockId = (int)$stock['id'];

        $updateStock = $pdo->prepare("
            UPDATE stocks
            SET quantity = quantity + ?,
                supplier = ?,
                stocked_by = ?,
                created_at = NOW()
            WHERE id = ?
              AND company_id = ?
              AND location_id = ?
        ");

        $updateStock->execute([
            $quantity,
            $supplierId,
            $userId,
            $stockId,
            $companyId,
            $locationId
        ]);

        $type = 'Restock';
        $message = 'Stock updated successfully.';

    } else {

        $insertStock = $pdo->prepare("
            INSERT INTO stocks
                (
                    product_id,
                    company_id,
                    location_id,
                    quantity,
                    supplier,
                    created_at,
                    stocked_by
                )
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");

        $insertStock->execute([
            $productId,
            $companyId,
            $locationId,
            $quantity,
            $supplierId,
            $userId
        ]);

        $stockId = (int)$pdo->lastInsertId();

        $type = 'Initial Stock Entry';
        $message = 'Stock inserted successfully.';
    }

    /*
     * Record the stock-in transaction against the same branch.
     */
    $insertStockIn = $pdo->prepare("
        INSERT INTO stock_in
            (
                product_id,
                stock_id,
                company_id,
                location_id,
                transfer_ID,
                quantity,
                type,
                supplier,
                created_at,
                recorded_by
            )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    $insertStockIn->execute([
        $productId,
        $stockId,
        $companyId,
        $locationId,
        $transferId,
        $quantity,
        $type,
        $supplierId,
        $userId
    ]);

    $stockInId = (int)$pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | REGISTER EACH TRACKED DEVICE / UNIT
    |--------------------------------------------------------------------------
    */

    if ($isTracked) {

        $insertSerial = $pdo->prepare("
            INSERT INTO product_serials
                (
                    product_id,
                    company_id,
                    identifier_type,
                    identifier_value,
                    imei,
                    stock_id,
                    location_id,
                    status,
                    current_sale_item_id,
                    received_at,
                    created_by,
                    updated_at
                )
            VALUES (
                ?, ?, ?, ?, ?, ?, ?, 'in_stock', NULL, NOW(), ?, NOW()
            )
        ");

        foreach ($identifiers as $identifier) {

            $legacyImei = $trackingMode === 'imei'
                ? $identifier
                : null;

            $insertSerial->execute([
                $productId,
                $companyId,
                $trackingMode,
                $identifier,
                $legacyImei,
                $stockId,
                $locationId,
                $userId
            ]);

            $serialId = (int)$pdo->lastInsertId();

            insertSerialEvent($pdo, [
                'product_serial_id' => $serialId,
                'product_id'        => $productId,
                'company_id'        => $companyId,
                'event_type'        => 'stock_in',
                'from_status'       => null,
                'to_status'         => 'in_stock',
                'from_stock_id'     => null,
                'to_stock_id'       => $stockId,
                'from_location_id'  => null,
                'to_location_id'    => $locationId,
                'reference_type'    => 'stock_in',
                'reference_id'      => $stockInId,
                'notes'             => $identifierLabel .
                    ' registered during stock-in.',
                'performed_by'      => $userId,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LINK OPTIONAL INVOICE TO STOCK-IN HISTORY
    |--------------------------------------------------------------------------
    */

    if (
        $invoicePath !== null &&
        $absoluteInvoicePath !== null
    ) {

        $insertInvoice = $pdo->prepare("
            INSERT INTO stock_in_invoices
                (
                    stock_in_id,
                    company_id,
                    file_path,
                    original_filename,
                    mime_type,
                    file_size,
                    uploaded_by,
                    created_at
                )
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $insertInvoice->execute([
            $stockInId,
            $companyId,
            $invoicePath,
            $originalName,
            $mimeType,
            (int)$upload['size'],
            $userId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $pdo->commit();
    $transactionStarted = false;

    $successMessage = $isTracked
        ? $message . ' ' .
            count($identifiers) . ' ' .
            $identifierLabel . '(s) registered successfully.'
        : $message;

    echo '<script>alert(' .
        json_encode(
            $successMessage,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) .
        ');window.location.href="stock_transaction.php";</script>';

    exit;

} catch (Throwable $e) {

    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
     * Remove uploaded invoice if the database transaction failed.
     */
    if (
        $absoluteInvoicePath !== null &&
        is_file($absoluteInvoicePath)
    ) {
        @unlink($absoluteInvoicePath);
    }

    error_log('Stock-in error: ' . $e->getMessage());

    stockInFail(
        'Stock-in could not be completed: ' . $e->getMessage()
    );
}
?>
