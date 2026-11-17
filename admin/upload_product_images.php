<?php
include('../config/db.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function uploadResponse(string $message, bool $success = false, ?int $productId = null): void
{
    $safeMessage = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $destination = $success && $productId ? 'view_product.php?id=' . $productId : 'javascript:history.back()';
    $safeDestination = json_encode($destination, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    echo "<script>alert({$safeMessage}); window.location.href={$safeDestination};</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    uploadResponse('Invalid request.');
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$companyId = filter_var($_SESSION['company_id'] ?? null, FILTER_VALIDATE_INT);
$csrfToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['product_media_upload_token'] ?? '');

if (!$productId || !$companyId) {
    uploadResponse('Invalid product or company.');
}
if ($sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    uploadResponse('Your session token is invalid. Please refresh the page and try again.');
}
if (!isset($_FILES['product_images']) || !is_array($_FILES['product_images']['name'] ?? null)) {
    uploadResponse('Please select at least one image or video.');
}

// Confirm the product belongs to the signed-in company before accepting uploads.
$productStmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND company_id = ? LIMIT 1');
$productStmt->execute([$productId, $companyId]);
if (!$productStmt->fetch(PDO::FETCH_ASSOC)) {
    uploadResponse('Product not found or access denied.');
}

$files = $_FILES['product_images'];
$fileCount = count($files['name']);
if ($fileCount < 1 || $fileCount > 20) {
    uploadResponse('Select between 1 and 20 files per upload.');
}

$allowed = [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png' => ['png'],
    'image/gif' => ['gif'],
    'image/webp' => ['webp'],
    'video/mp4' => ['mp4'],
    'video/webm' => ['webm'],
    'video/quicktime' => ['mov'],
];
$maxFileSize = 100 * 1024 * 1024; // 100 MB per file; server PHP limits still apply.
$uploadDir = __DIR__ . '/../uploads/products/';
$relativeDir = '../uploads/products/';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    uploadResponse('The product media upload directory could not be created.');
}
if (!is_writable($uploadDir)) {
    uploadResponse('The product media upload directory is not writable.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$storedFiles = [];
$uploadedCount = 0;

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(display_order), -1) FROM product_media WHERE product_id = ? AND company_id = ?');
    $orderStmt->execute([$productId, $companyId]);
    $nextOrder = (int)$orderStmt->fetchColumn() + 1;

    $legacyStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, created_at) VALUES (?, ?, NOW())');
    $mediaStmt = $pdo->prepare(
        'INSERT INTO product_media
         (product_id, company_id, media_type, media_path, thumbnail_path, media_name, mime_type, file_size, display_order, is_primary, legacy_image_id, created_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, 0, ?, ?, NOW(), NOW())'
    );

    for ($i = 0; $i < $fileCount; $i++) {
        $error = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('One of the files failed to upload (upload error ' . $error . ').');
        }

        $tmpName = $files['tmp_name'][$i] ?? '';
        $originalName = (string)($files['name'][$i] ?? '');
        $fileSize = (int)($files['size'][$i] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName) || $fileSize < 1 || $fileSize > $maxFileSize) {
            throw new RuntimeException('A selected file is empty, invalid, or larger than 100 MB.');
        }

        $mimeType = $finfo->file($tmpName) ?: '';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset($allowed[$mimeType]) || !in_array($extension, $allowed[$mimeType], true)) {
            throw new RuntimeException('Unsupported file type: ' . basename($originalName));
        }

        $mediaType = str_starts_with($mimeType, 'image/') ? 'image' : 'video';
        $safeOriginalName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($originalName));
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $uploadDir . $storedName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Could not save ' . $safeOriginalName . '.');
        }
        $storedFiles[] = $targetPath;

        $legacyImageId = null;
        if ($mediaType === 'image') {
            $legacyStmt->execute([$productId, $storedName]);
            $legacyImageId = (int)$pdo->lastInsertId();
        }

        $createdBy = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $mediaStmt->execute([
            $productId,
            $companyId,
            $mediaType,
            $storedName,
            $safeOriginalName,
            $mimeType,
            $fileSize,
            $nextOrder++,
            $legacyImageId,
            $createdBy,
        ]);
        $uploadedCount++;
    }

    if ($uploadedCount < 1) {
        throw new RuntimeException('Please select at least one valid image or video.');
    }

    $pdo->commit();
    unset($_SESSION['product_media_upload_token']);
    uploadResponse($uploadedCount . ' product media file(s) uploaded successfully.', true, $productId);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($storedFiles as $storedFile) {
        if (is_file($storedFile)) {
            @unlink($storedFile);
        }
    }
    error_log('Product media upload failed: ' . $exception->getMessage());
    uploadResponse($exception instanceof RuntimeException ? $exception->getMessage() : 'The media upload could not be completed. Please try again.');
}
?>
