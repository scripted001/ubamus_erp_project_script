<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/smtp.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

date_default_timezone_set('Africa/Lagos');

function transferFail(string $message): void
{
    echo '<script>alert(' . json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ');window.history.back();</script>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stocks.php');
    exit;
}

$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);
$stock_id = filter_var($_POST['stock_id'] ?? null, FILTER_VALIDATE_INT);
$variantRaw = trim((string)($_POST['variant_id'] ?? ''));
$variant_id = $variantRaw === '' ? 0 : filter_var($variantRaw, FILTER_VALIDATE_INT);
$destination_id = filter_var($_POST['to_location_id'] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);

if ($company_id <= 0 || $user_id <= 0) transferFail('Your session has expired. Please sign in again.');
if (!$stock_id || $stock_id <= 0 || $variant_id === false || $variant_id < 0) transferFail('Invalid stock or variant selection.');
if (!$destination_id || $destination_id <= 0) transferFail('Please select a valid destination location.');
if (!$quantity || $quantity <= 0) transferFail('Transfer quantity must be at least one unit.');

$rawSerialIds = $_POST['serial_ids'] ?? [];
if (!is_array($rawSerialIds)) transferFail('Invalid tracked-unit selection.');
$serialIds = [];
foreach ($rawSerialIds as $rawId) {
    $id = filter_var($rawId, FILTER_VALIDATE_INT);
    if (!$id || $id <= 0) transferFail('Invalid tracked-unit selection. Refresh and try again.');
    $serialIds[] = $id;
}
if (count($serialIds) !== count(array_unique($serialIds))) transferFail('A tracked unit was selected more than once.');

try {
    $pdo->beginTransaction();

    // Lock and validate the source inventory row and product ownership.
    $sourceStmt = $pdo->prepare("
        SELECT s.id AS stock_id, s.product_id, s.company_id, s.location_id,
               COALESCE(s.quantity, 0) AS parent_quantity, s.supplier, s.stocked_by,
               p.name AS product_name, p.has_variants, p.identifier_tracking,
               p.imei_tracking, p.stock_type
        FROM stocks s
        INNER JOIN products p ON p.id = s.product_id AND p.company_id = s.company_id
        WHERE s.id = ? AND s.company_id = ?
        LIMIT 1 FOR UPDATE
    ");
    $sourceStmt->execute([$stock_id, $company_id]);
    $source = $sourceStmt->fetch(PDO::FETCH_ASSOC);
    if (!$source) throw new RuntimeException('Source stock record was not found or is not accessible.');

    $product_id = (int)$source['product_id'];
    $source_location_id = (int)$source['location_id'];
    if ($destination_id === $source_location_id) throw new RuntimeException('The destination must be different from the source location.');

    // Lock both location rows in a stable order so concurrent transfers do not
    // create duplicate destination stock rows for the same company/location.
    $lockLocations = $pdo->prepare('SELECT id FROM locations WHERE company_id = ? AND id IN (?, ?) ORDER BY id FOR UPDATE');
    $lockLocations->execute([$company_id, min($source_location_id, $destination_id), max($source_location_id, $destination_id)]);
    if (count($lockLocations->fetchAll(PDO::FETCH_COLUMN)) !== 2) {
        throw new RuntimeException('The source or destination location is no longer available to this company.');
    }

    $destinationStmt = $pdo->prepare('SELECT id, name FROM locations WHERE id = ? AND company_id = ? LIMIT 1');
    $destinationStmt->execute([$destination_id, $company_id]);
    $destination = $destinationStmt->fetch(PDO::FETCH_ASSOC);
    if (!$destination) throw new RuntimeException('The selected destination does not belong to your company.');

    if ((int)$source['has_variants'] === 1 && $variant_id <= 0) {
        throw new RuntimeException('Select a specific product variant before transferring this inventory.');
    }
    if ((int)$source['has_variants'] !== 1 && $variant_id > 0) {
        throw new RuntimeException('This product does not use variants. Refresh the transfer page.');
    }

    $parentTracking = strtolower(trim((string)($source['identifier_tracking'] ?? 'none')));
    if ($parentTracking === '' || $parentTracking === 'none') {
        $parentTracking = (int)$source['imei_tracking'] === 1 ? 'imei' : 'none';
    }
    $trackingMode = $parentTracking;
    $currentQuantity = (int)$source['parent_quantity'];
    $variantName = '';

    if ($variant_id > 0) {
        $variantStmt = $pdo->prepare("
            SELECT pv.id, pv.variant_name, pv.tracking_policy,
                   pvs.quantity AS variant_quantity
            FROM product_variants pv
            INNER JOIN product_variant_stocks pvs
                ON pvs.company_id = pv.company_id
               AND pvs.product_id = pv.product_id
               AND pvs.variant_id = pv.id
            WHERE pv.id = ? AND pv.product_id = ? AND pv.company_id = ?
              AND pv.is_active = 1 AND pvs.location_id = ?
            LIMIT 1 FOR UPDATE
        ");
        $variantStmt->execute([$variant_id, $product_id, $company_id, $source_location_id]);
        $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);
        if (!$variant) throw new RuntimeException('The selected variant is inactive or has no stock record at the source location.');
        $variantName = (string)$variant['variant_name'];
        $variantPolicy = strtolower(trim((string)($variant['tracking_policy'] ?? 'inherit')));
        $trackingMode = ($variantPolicy !== '' && $variantPolicy !== 'inherit') ? $variantPolicy : $parentTracking;
        $currentQuantity = (int)floor((float)$variant['variant_quantity']);
    }

    if (!in_array($trackingMode, ['none', 'imei', 'serial', 'vin'], true)) {
        throw new RuntimeException('The product or variant has an unsupported identifier tracking policy.');
    }
    if ($trackingMode !== 'none' && strtolower(trim((string)$source['stock_type'])) !== 'sales only') {
        throw new RuntimeException('Identifier tracking is only enabled for Sales Only products.');
    }
    if ($quantity > $currentQuantity) {
        throw new RuntimeException('Insufficient stock at the source location. Available quantity: ' . $currentQuantity . '.');
    }

    // For tracked products, the submitted quantity must equal the exact selected units.
    $lockedSerials = [];
    if ($trackingMode !== 'none') {
        if (count($serialIds) !== $quantity) {
            throw new RuntimeException('Select exactly one ' . strtoupper($trackingMode) . ' for every unit being transferred.');
        }
        $placeholders = implode(',', array_fill(0, count($serialIds), '?'));
        $serialSql = "
            SELECT id, identifier_type, identifier_value
            FROM product_serials
            WHERE id IN ($placeholders)
              AND company_id = ? AND product_id = ?
              AND location_id = ? AND stock_id = ?
              AND status = 'in_stock' AND identifier_type = ?
              AND " . ($variant_id > 0 ? 'variant_id = ?' : 'variant_id IS NULL') . "
            FOR UPDATE
        ";
        $serialParams = array_merge($serialIds, [$company_id, $product_id, $source_location_id, $stock_id, $trackingMode]);
        if ($variant_id > 0) $serialParams[] = $variant_id;
        $serialStmt = $pdo->prepare($serialSql);
        $serialStmt->execute($serialParams);
        $lockedSerials = $serialStmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($lockedSerials) !== $quantity) {
            throw new RuntimeException('One or more selected identifiers are no longer available at this source location. Refresh the page and select the units again.');
        }
    } elseif (count($serialIds) > 0) {
        throw new RuntimeException('This product or variant is not identifier-tracked. Remove the tracked-unit selection and try again.');
    }

    // Find or create the destination's parent stock row.
    $destStockStmt = $pdo->prepare("
        SELECT id, quantity FROM stocks
        WHERE company_id = ? AND product_id = ? AND location_id = ?
        ORDER BY id ASC LIMIT 1 FOR UPDATE
    ");
    $destStockStmt->execute([$company_id, $product_id, $destination_id]);
    $destStock = $destStockStmt->fetch(PDO::FETCH_ASSOC);
    if (!$destStock) {
        $insertDestStock = $pdo->prepare("
            INSERT INTO stocks (product_id, company_id, location_id, quantity, supplier, created_at, stocked_by)
            VALUES (?, ?, ?, 0, 'Transfer', ?, ?)
        ");
        $insertDestStock->execute([$product_id, $company_id, $destination_id, date('Y-m-d H:i:s'), (string)$user_id]);
        $destination_stock_id = (int)$pdo->lastInsertId();
    } else {
        $destination_stock_id = (int)$destStock['id'];
    }

    if ($variant_id <= 0) {
        // Non-variant products use stocks.quantity as their authoritative balance.
        $debitParent = $pdo->prepare('UPDATE stocks SET quantity = COALESCE(quantity, 0) - ? WHERE id = ? AND company_id = ? AND product_id = ? AND location_id = ? AND COALESCE(quantity, 0) >= ? LIMIT 1');
        $debitParent->execute([$quantity, $stock_id, $company_id, $product_id, $source_location_id, $quantity]);
        if ($debitParent->rowCount() !== 1) throw new RuntimeException('Could not safely deduct the source stock quantity.');

        $creditParent = $pdo->prepare('UPDATE stocks SET quantity = COALESCE(quantity, 0) + ? WHERE id = ? AND company_id = ? AND product_id = ? AND location_id = ? LIMIT 1');
        $creditParent->execute([$quantity, $destination_stock_id, $company_id, $product_id, $destination_id]);
        if ($creditParent->rowCount() !== 1) throw new RuntimeException('Could not update the destination stock quantity.');
    } else {
        $debitVariant = $pdo->prepare("
            UPDATE product_variant_stocks
            SET quantity = quantity - ?, updated_at = NOW()
            WHERE company_id = ? AND product_id = ? AND variant_id = ? AND location_id = ? AND quantity >= ?
            LIMIT 1
        ");
        $debitVariant->execute([$quantity, $company_id, $product_id, $variant_id, $source_location_id, $quantity]);
        if ($debitVariant->rowCount() !== 1) throw new RuntimeException('Could not safely deduct the selected variant stock.');

        $destVariantStmt = $pdo->prepare("
            SELECT id FROM product_variant_stocks
            WHERE company_id = ? AND product_id = ? AND variant_id = ? AND location_id = ?
            LIMIT 1 FOR UPDATE
        ");
        $destVariantStmt->execute([$company_id, $product_id, $variant_id, $destination_id]);
        $destVariantId = $destVariantStmt->fetchColumn();
        if ($destVariantId !== false) {
            $creditVariant = $pdo->prepare('UPDATE product_variant_stocks SET quantity = quantity + ?, updated_at = NOW() WHERE id = ? LIMIT 1');
            $creditVariant->execute([$quantity, (int)$destVariantId]);
            if ($creditVariant->rowCount() !== 1) throw new RuntimeException('Could not update the destination variant balance.');
        } else {
            $insertVariant = $pdo->prepare("
                INSERT INTO product_variant_stocks (company_id, product_id, variant_id, location_id, quantity, reorder_level, updated_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            $insertVariant->execute([$company_id, $product_id, $variant_id, $destination_id, $quantity]);
        }

        // Keep each parent stocks row as the sum of its variant balances.
        $recalcParent = $pdo->prepare("
            UPDATE stocks s
            SET quantity = (
                SELECT COALESCE(SUM(pvs.quantity), 0)
                FROM product_variant_stocks pvs
                WHERE pvs.company_id = s.company_id
                  AND pvs.product_id = s.product_id
                  AND pvs.location_id = s.location_id
            )
            WHERE s.id = ? AND s.company_id = ? AND s.product_id = ? AND s.location_id = ?
            LIMIT 1
        ");
        $recalcParent->execute([$stock_id, $company_id, $product_id, $source_location_id]);
        if ($recalcParent->rowCount() !== 1) throw new RuntimeException('Could not refresh the source variant aggregate.');
        $recalcParent->execute([$destination_stock_id, $company_id, $product_id, $destination_id]);
        if ($recalcParent->rowCount() !== 1) throw new RuntimeException('Could not refresh the destination variant aggregate.');
    }

    $transferRef = 'TRANSFER-' . $stock_id . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
    $movementStmt = $pdo->prepare("
        INSERT INTO stock_movements (
            company_id, product_id, variant_id, stock_id,
            from_location_id, to_location_id, transfer_ID,
            quantity, movement_type, reference, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Transfer', ?, ?, NOW())
    ");
    $movementStmt->execute([
        $company_id, $product_id, $variant_id > 0 ? $variant_id : null,
        $stock_id, $source_location_id, $destination_id, $transferRef,
        $quantity, $transferRef, $user_id
    ]);

    // Preserve the two stock-in history entries used by the original workflow.
    // The destination entry records the incoming quantity; the source entry
    // preserves the legacy balance snapshot and label used by existing reports.
    $sourceBalanceAfter = $currentQuantity - $quantity;
    $stockInStmt = $pdo->prepare("
        INSERT INTO stock_in (
            product_id, variant_id, stock_id, company_id, location_id,
            transfer_ID, quantity, type, supplier, created_at, recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    $historyVariantId = $variant_id > 0 ? $variant_id : null;
    $historySupplier = (string)($source['supplier'] ?? 'Transfer');
    $stockInStmt->execute([
        $product_id, $historyVariantId, $destination_stock_id, $company_id,
        $destination_id, $transferRef, $quantity, 'Transfer Destination',
        $historySupplier, $user_id
    ]);
    $stockInStmt->execute([
        $product_id, $historyVariantId, $stock_id, $company_id,
        $source_location_id, $transferRef, $sourceBalanceAfter,
        'Inventory Restocked (via Transfer)', $historySupplier, $user_id
    ]);

    if ($trackingMode !== 'none') {
        $moveSerial = $pdo->prepare("
            UPDATE product_serials
            SET stock_id = ?, location_id = ?, updated_at = NOW()
            WHERE id = ? AND company_id = ? AND product_id = ?
              AND stock_id = ? AND location_id = ? AND status = 'in_stock'
        ");
        $eventStmt = $pdo->prepare("
            INSERT INTO product_serial_events (
                serial_id, company_id, product_id, event_type,
                from_stock_id, to_stock_id, from_location_id, to_location_id,
                reference, notes, performed_by, created_at
            ) VALUES (?, ?, ?, 'transfer', ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        foreach ($lockedSerials as $serial) {
            $serialId = (int)$serial['id'];
            $moveSerial->execute([$destination_stock_id, $destination_id, $serialId, $company_id, $product_id, $stock_id, $source_location_id]);
            if ($moveSerial->rowCount() !== 1) throw new RuntimeException('A tracked unit changed while the transfer was being processed. No transfer was saved.');
            $notes = strtoupper($trackingMode) . ' ' . (string)$serial['identifier_value'] . ' transferred';
            if ($variant_id > 0) $notes .= ' with variant ' . $variantName . ' (ID ' . $variant_id . ')';
            $eventStmt->execute([
                $serialId, $company_id, $product_id,
                $stock_id, $destination_stock_id,
                $source_location_id, $destination_id,
                $transferRef, $notes, $user_id
            ]);
        }
    }

    $pdo->commit();

    $itemName = (string)$source['product_name'] . ($variantName !== '' ? ' — ' . $variantName : '');
    $fromNameStmt = $pdo->prepare('SELECT name FROM locations WHERE id = ? AND company_id = ? LIMIT 1');
    $fromNameStmt->execute([$source_location_id, $company_id]);
    $fromLocationName = (string)($fromNameStmt->fetchColumn() ?: ('Location #' . $source_location_id));
    $userStmt = $pdo->prepare('SELECT username FROM users WHERE id = ? AND company_id = ? LIMIT 1');
    $userStmt->execute([$user_id, $company_id]);
    $actorName = (string)($userStmt->fetchColumn() ?: ('User #' . $user_id));
    $companyStmt = $pdo->prepare('SELECT company_email, company_name FROM companies WHERE id = ? LIMIT 1');
    $companyStmt->execute([$company_id]);
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

    // Email failure must never undo a transfer that has already committed.
    $emailWarning = '';
    $smtpUsername = (string)($_SESSION['customercare'] ?? '');
    $smtpPassword = (string)($_SESSION['customer_care_password'] ?? '');
    if ($company && filter_var((string)$company['company_email'], FILTER_VALIDATE_EMAIL)
        && $smtpUsername !== '' && $smtpPassword !== '') {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($smtpUsername, (string)$company['company_name']);
            $mail->addAddress((string)$company['company_email'], (string)$company['company_name']);
            $mail->isHTML(true);
            $mail->Subject = 'Stock Transfer Notification';
            $safeCompany = htmlspecialchars((string)$company['company_name'], ENT_QUOTES, 'UTF-8');
            $safeItem = htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8');
            $safeFrom = htmlspecialchars($fromLocationName, ENT_QUOTES, 'UTF-8');
            $safeTo = htmlspecialchars((string)$destination['name'], ENT_QUOTES, 'UTF-8');
            $safeActor = htmlspecialchars($actorName, ENT_QUOTES, 'UTF-8');
            $safeRef = htmlspecialchars($transferRef, ENT_QUOTES, 'UTF-8');
            $mail->Body = "<p>Dear {$safeCompany},</p><p>A stock transfer has been completed successfully.</p>"
                . "<p><b>Product:</b> {$safeItem}<br><b>Quantity Transferred:</b> " . (int)$quantity
                . "<br><b>From Location:</b> {$safeFrom}<br><b>To Location:</b> {$safeTo}"
                . "<br><b>Transferred By:</b> {$safeActor}<br><b>Transfer Reference:</b> {$safeRef}</p>"
                . "<p>Thank you,<br>Yours faithfully, {$safeCompany}</p>";
            $mail->send();
        } catch (MailerException $mailError) {
            error_log('Stock Transfer Email Error: ' . $mailError->getMessage());
            $emailWarning = "\n\nThe transfer was saved, but the notification email could not be sent.";
        } catch (Throwable $mailError) {
            error_log('Stock Transfer Email Error: ' . $mailError->getMessage());
            $emailWarning = "\n\nThe transfer was saved, but the notification email could not be sent.";
        }
    } elseif ($company) {
        $emailWarning = "\n\nThe transfer was saved, but email credentials or company email were unavailable.";
    }

    $success = "Stock transfer completed.\n\nItem: " . $itemName
        . "\nQuantity: " . $quantity
        . "\nFrom: " . $fromLocationName
        . "\nTo: " . (string)$destination['name']
        . "\nReference: " . $transferRef . $emailWarning;
    echo '<script>alert(' . json_encode($success, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ');window.location.href="stocks.php";</script>';
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Stock Transfer Error: ' . $e->getMessage());
    $message = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'The transfer could not be completed. No stock changes were saved.';
    transferFail($message);
}
?>
