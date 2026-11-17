<?php
declare(strict_types=1);

/**
 * BRANCH MANAGER — RESTOCK PROCESSOR
 *
 * Security rules:
 * - Only an active Branch Manager can submit this action.
 * - Company, location and user identity are loaded from the users table.
 * - The assigned location is verified against the locations table.
 * - Stock and product records must belong to that exact company/location.
 * - POST company_id, product_id and location_id are never trusted.
 * - CSRF protection is required (restock_csrf_token).
 * - Stock quantity, stock-in history, tracked identifiers and invoice metadata
 *   are committed in one database transaction.
 */

date_default_timezone_set('Africa/Lagos');



require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/validation.php';
require_once __DIR__ . '/user_data.php';

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
function restockRespond(string $message, string $destination = ''): void
{
    $safeMessage = json_encode(
        $message,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );

    if ($destination !== '') {
        $safeDestination = json_encode(
            $destination,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );
        echo "<script>alert($safeMessage);window.location.href=$safeDestination;</script>";
    } else {
        echo "<script>alert($safeMessage);window.history.back();</script>";
    }
    exit;
}

function restockValidImei(string $imei): bool
{
    if (!preg_match('/^[0-9]{15}$/', $imei)) {
        return false;
    }

    $sum = 0;
    for ($i = 0; $i < 15; $i++) {
        $digit = (int)$imei[$i];
        if (($i % 2) === 1) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
    }

    return ($sum % 10) === 0;
}

function restockRemoveUploadedFile(?string $path): void
{
    if ($path !== null && is_file($path)) {
        @unlink($path);
    }
}

/* ---------------------------------------------------------
   Request and database validation
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    restockRespond('Invalid request method.', 'restock.php');
}

if (!isset($pdo) || !$pdo instanceof PDO) {
    error_log('Restock processor: PDO connection is unavailable.');
    restockRespond('Unable to process this restock at the moment.', 'stocks.php');
}

$authenticatedUserId = (int)($_SESSION['user_id'] ?? ($user_id ?? 0));
if ($authenticatedUserId <= 0) {
    restockRespond('Your session has expired. Please sign in again.', '../login.php');
}

/* ---------------------------------------------------------
   Verify Branch Manager from database — not from POST/session scope
--------------------------------------------------------- */
try {
    $userStmt = $pdo->prepare("\n        SELECT id, role, status, company_id, location_id\n        FROM users\n        WHERE id = ?\n        LIMIT 1\n    ");
    $userStmt->execute([$authenticatedUserId]);
    $branchUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    $role = strtolower(trim((string)($branchUser['role'] ?? '')));
    $status = strtolower(trim((string)($branchUser['status'] ?? '')));

    if (
        !$branchUser ||
        $role !== 'branch manager' ||
        !in_array($status, ['active', '1', 'enabled'], true)
    ) {
        http_response_code(403);
        restockRespond('Access denied. Only active Branch Managers can process this restock.', 'stocks.php');
    }

    $company_id = (int)$branchUser['company_id'];
    $location_id = (int)$branchUser['location_id'];
    $recorded_by = (int)$branchUser['id'];

    if ($company_id <= 0 || $location_id <= 0) {
        http_response_code(403);
        restockRespond('Your account does not have a valid assigned branch.', 'stocks.php');
    }

    $locationStmt = $pdo->prepare("\n        SELECT id\n        FROM locations\n        WHERE id = ? AND company_id = ?\n        LIMIT 1\n    ");
    $locationStmt->execute([$location_id, $company_id]);
    if (!$locationStmt->fetchColumn()) {
        http_response_code(403);
        restockRespond('Your assigned branch could not be verified.', 'stocks.php');
    }

    /* -----------------------------------------------------
       CSRF protection
       The restock form must contain:
       <input type="hidden" name="csrf_token" value="...">
       and the page must initialize $_SESSION['restock_csrf_token'].
    ----------------------------------------------------- */
    $sessionCsrf = (string)($_SESSION['restock_csrf_token'] ?? '');
    $postedCsrf = (string)($_POST['csrf_token'] ?? '');
    if (
        $sessionCsrf === '' ||
        $postedCsrf === '' ||
        !hash_equals($sessionCsrf, $postedCsrf)
    ) {
        restockRespond('Your form session has expired. Refresh the page and try again.', 'restock.php');
    }

    $stock_id = filter_var($_POST['stock_id'] ?? null, FILTER_VALIDATE_INT);
    $quantityInput = filter_var($_POST['add_quantity'] ?? null, FILTER_VALIDATE_INT);

    if ($stock_id === false || $stock_id === null || $stock_id <= 0) {
        restockRespond('Invalid stock record.');
    }
    if ($quantityInput === false || $quantityInput === null || $quantityInput < 1 || $quantityInput > 500) {
        restockRespond('Please enter a quantity between 1 and 500 units.');
    }
    $stock_id = (int)$stock_id;
    $add_quantity = (int)$quantityInput;

    /* -----------------------------------------------------
       Optional invoice upload — validate before moving
    ----------------------------------------------------- */
    $invoicePath = null;
    $absoluteInvoicePath = null;
    $originalName = null;
    $mimeType = null;
    $upload = null;

    $invoiceWasSubmitted = isset($_FILES['stock_invoice']) &&
        (($_FILES['stock_invoice']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

    if ($invoiceWasSubmitted) {
        $upload = $_FILES['stock_invoice'];

        if (!isset($upload['error']) || is_array($upload['error'])) {
            throw new RuntimeException('Invalid invoice upload.');
        }

        if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'The invoice exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'The invoice exceeds the form upload limit.',
                UPLOAD_ERR_PARTIAL    => 'The invoice was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server is missing its temporary upload folder.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded invoice.',
                UPLOAD_ERR_EXTENSION => 'The server blocked the invoice upload.',
            ];
            throw new RuntimeException($uploadErrors[(int)$upload['error']] ?? 'The invoice upload failed.');
        }

        if (!isset($upload['tmp_name']) || !is_uploaded_file((string)$upload['tmp_name'])) {
            throw new RuntimeException('The uploaded invoice could not be verified.');
        }

        $maxFileSize = 20 * 1024 * 1024;
        $actualSize = (int)($upload['size'] ?? 0);
        if ($actualSize <= 0 || $actualSize > $maxFileSize) {
            throw new RuntimeException('Invoice must be a non-empty file no larger than 20 MB.');
        }

        $originalName = basename((string)($upload['name'] ?? 'invoice'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedMimeTypes = [
            'pdf'  => ['application/pdf'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
        ];
        if (!isset($allowedMimeTypes[$extension])) {
            throw new RuntimeException('Invoice must be a PDF, JPG, JPEG or PNG file.');
        }
        if (!class_exists('finfo')) {
            throw new RuntimeException('The server cannot validate uploaded file types.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file((string)$upload['tmp_name']);
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

        // Best-effort Apache protection. Prefer storing uploads outside the web root where possible.
        $htaccessPath = $uploadDirectory . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            @file_put_contents(
                $htaccessPath,
                "Options -Indexes\n<FilesMatch \"\\.(php[0-9]?|phtml|phar|cgi|pl|sh)$\">\nRequire all denied\n</FilesMatch>\n"
            );
        }

        $storedFileName = bin2hex(random_bytes(24)) . '.' . $extension;
        $absoluteInvoicePath = $uploadDirectory . '/' . $storedFileName;
        $invoicePath = '../uploads/stock_in_invoices/' . $storedFileName;

        if (!move_uploaded_file((string)$upload['tmp_name'], $absoluteInvoicePath)) {
            $absoluteInvoicePath = null;
            $invoicePath = null;
            throw new RuntimeException('Could not save the invoice file.');
        }
        @chmod($absoluteInvoicePath, 0640);
    }

    /* -----------------------------------------------------
       Begin transaction and lock the exact branch stock row
    ----------------------------------------------------- */
    $pdo->beginTransaction();

    $stockStmt = $pdo->prepare("\n        SELECT\n            s.id AS stock_id, s.product_id, s.company_id, s.location_id,\n            s.quantity, s.supplier, p.name AS product_name, p.cost_price,\n            COALESCE(NULLIF(p.identifier_tracking, ''), 'none') AS identifier_tracking,\n            COALESCE(p.imei_tracking, 0) AS imei_tracking,\n            LOWER(TRIM(COALESCE(pc.stock_type, ''))) AS product_stock_type\n        FROM stocks s\n        INNER JOIN products p\n            ON p.id = s.product_id AND p.company_id = s.company_id\n        LEFT JOIN product_categories pc\n            ON pc.id = p.category_id AND pc.company_id = p.company_id\n        WHERE s.id = ? AND s.company_id = ? AND s.location_id = ?\n        LIMIT 1\n        FOR UPDATE\n    ");
    $stockStmt->execute([$stock_id, $company_id, $location_id]);
    $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock) {
        throw new RuntimeException('Stock record not found or you do not have access to this branch stock.');
    }

    /* -----------------------------------------------------
       Validate tracking configuration and incoming identifiers
    ----------------------------------------------------- */
    $trackingMode = strtolower(trim((string)($stock['identifier_tracking'] ?? 'none')));
    if ($trackingMode === 'none' && (int)($stock['imei_tracking'] ?? 0) === 1) {
        $trackingMode = 'imei';
    }
    if (!in_array($trackingMode, ['none', 'imei', 'serial', 'vin'], true)) {
        throw new RuntimeException('This product has an invalid identifier tracking setting.');
    }

    $isTracked = $trackingMode !== 'none';
    if ($isTracked && strtolower(trim((string)($stock['product_stock_type'] ?? ''))) !== 'sales only') {
        throw new RuntimeException('Identifier tracking is only supported for Sales Only products.');
    }

    $identifiers = [];
    if ($isTracked) {
        $postedIdentifiers = $_POST['identifiers'] ?? null;
        if (!is_array($postedIdentifiers) || count($postedIdentifiers) !== $add_quantity) {
            $label = ['imei' => 'IMEI', 'serial' => 'serial number', 'vin' => 'VIN'][$trackingMode];
            throw new RuntimeException('Enter exactly one ' . $label . ' for every unit being restocked.');
        }

        $seen = [];
        foreach (array_values($postedIdentifiers) as $index => $rawIdentifier) {
            if (!is_string($rawIdentifier) && !is_numeric($rawIdentifier)) {
                throw new RuntimeException('Invalid identifier at unit #' . ($index + 1) . '.');
            }
            $identifier = trim((string)$rawIdentifier);

            if ($trackingMode === 'imei') {
                if (!restockValidImei($identifier)) {
                    throw new RuntimeException('IMEI #' . ($index + 1) . ' must be a valid 15-digit IMEI.');
                }
            } elseif ($trackingMode === 'vin') {
                $identifier = strtoupper($identifier);
                if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $identifier)) {
                    throw new RuntimeException('VIN #' . ($index + 1) . ' must contain exactly 17 valid characters (I, O and Q are not allowed).');
                }
            } else {
                $charCount = preg_match_all('/./us', $identifier, $unused);
                if (
                    $identifier === '' ||
                    $charCount === false ||
                    $charCount > 100 ||
                    preg_match('/[\\x00-\\x1F\\x7F]/u', $identifier)
                ) {
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
            // IMEI uniqueness remains global, matching the existing system's rule.
            $duplicateStmt = $pdo->prepare("\n                SELECT identifier_value FROM product_serials\n                WHERE identifier_type = 'imei' AND identifier_value IN ($placeholders)\n                LIMIT 1\n                FOR UPDATE\n            ");
            $duplicateStmt->execute($identifiers);
        } else {
            $duplicateStmt = $pdo->prepare("\n                SELECT identifier_value FROM product_serials\n                WHERE company_id = ? AND identifier_type = ?\n                  AND identifier_value IN ($placeholders)\n                LIMIT 1\n                FOR UPDATE\n            ");
            $duplicateStmt->execute(array_merge([$company_id, $trackingMode], $identifiers));
        }
        $duplicate = $duplicateStmt->fetchColumn();
        if ($duplicate !== false) {
            $label = ['imei' => 'IMEI', 'serial' => 'serial number', 'vin' => 'VIN'][$trackingMode];
            throw new RuntimeException('This ' . $label . ' is already registered: ' . (string)$duplicate);
        }
    }

    $current_quantity = (int)$stock['quantity'];
    if ($current_quantity < 0 || $current_quantity > (PHP_INT_MAX - $add_quantity)) {
        throw new RuntimeException('The stock quantity is invalid or exceeds the supported limit.');
    }
    $new_quantity = $current_quantity + $add_quantity;

    $updateStmt = $pdo->prepare("\n        UPDATE stocks\n        SET quantity = ?\n        WHERE id = ? AND company_id = ? AND location_id = ?\n        LIMIT 1\n    ");
    $updateStmt->execute([$new_quantity, $stock_id, $company_id, $location_id]);
    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException('Unable to update the stock quantity.');
    }

    /* -----------------------------------------------------
       Record stock-in history
    ----------------------------------------------------- */
    $transfer_id = 'RESTOCK-' . $stock_id . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $stock_type = 'Restock';
    $supplier = $stock['supplier'];
    $created_at = date('Y-m-d H:i:s');

    $insertStockIn = $pdo->prepare("\n        INSERT INTO stock_in (\n            product_id, stock_id, company_id, location_id, transfer_ID,\n            quantity, type, supplier, created_at, recorded_by\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n    ");
    $insertStockIn->execute([
        (int)$stock['product_id'],
        $stock_id,
        $company_id,
        $location_id,
        $transfer_id,
        $add_quantity,
        $stock_type,
        $supplier,
        $created_at,
        $recorded_by,
    ]);
    $stockInId = (int)$pdo->lastInsertId();
    if ($stockInId <= 0) {
        throw new RuntimeException('Could not create the stock-in history record.');
    }

    /* -----------------------------------------------------
       Register tracked units and lifecycle events
    ----------------------------------------------------- */
    if ($isTracked) {
        $insertSerial = $pdo->prepare("\n            INSERT INTO product_serials (\n                product_id, company_id, identifier_type, identifier_value,\n                imei, stock_id, location_id, status, received_at, created_by\n            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'in_stock', NOW(), ?)\n        ");
        $insertEvent = $pdo->prepare("\n            INSERT INTO product_serial_events (\n                serial_id, company_id, product_id, event_type,\n                to_stock_id, to_location_id, reference, notes, performed_by, created_at\n            ) VALUES (?, ?, ?, 'restock', ?, ?, ?, ?, ?, NOW())\n        ");

        $label = ['imei' => 'IMEI', 'serial' => 'serial number', 'vin' => 'VIN'][$trackingMode];
        foreach ($identifiers as $identifier) {
            $legacyImei = $trackingMode === 'imei' ? $identifier : null;
            $insertSerial->execute([
                (int)$stock['product_id'],
                $company_id,
                $trackingMode,
                $identifier,
                $legacyImei,
                $stock_id,
                $location_id,
                $recorded_by,
            ]);
            $serialId = (int)$pdo->lastInsertId();
            if ($serialId <= 0) {
                throw new RuntimeException('Could not register a tracked product identifier.');
            }

            $insertEvent->execute([
                $serialId,
                $company_id,
                (int)$stock['product_id'],
                $stock_id,
                $location_id,
                $transfer_id,
                strtoupper($label) . ' registered during restock.',
                $recorded_by,
            ]);
        }
    }

    /* -----------------------------------------------------
       Link optional invoice to stock-in history
    ----------------------------------------------------- */
    if ($invoicePath !== null && $absoluteInvoicePath !== null && $upload !== null) {
        $insertInvoice = $pdo->prepare("\n            INSERT INTO stock_in_invoices (\n                stock_in_id, company_id, file_path, original_filename,\n                mime_type, file_size, uploaded_by, created_at\n            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())\n        ");
        $insertInvoice->execute([
            $stockInId,
            $company_id,
            $invoicePath,
            $originalName,
            $mimeType,
            (int)$upload['size'],
            $recorded_by,
        ]);
    }

    $pdo->commit();

    // Rotate the token after a successful submission to reduce replay risk.
    $_SESSION['restock_csrf_token'] = bin2hex(random_bytes(32));

    $productName = (string)$stock['product_name'];
    $successMessage =
        "Restock successful!\n\n" .
        "Product: {$productName}\n" .
        "Previous Stock: {$current_quantity}\n" .
        "Added Quantity: {$add_quantity}\n" .
        "New Stock: {$new_quantity}";

    restockRespond($successMessage, 'stock_transaction.php');

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // An uploaded invoice must not remain orphaned if any DB operation fails.
    restockRemoveUploadedFile($absoluteInvoicePath ?? null);

    error_log('Branch Manager restock error: ' . $e->getMessage());

    $userMessage = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'An error occurred while processing the restock. No stock changes were made.';

    restockRespond($userMessage);
}
