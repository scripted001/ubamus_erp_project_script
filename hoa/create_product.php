<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}

function cp_fail(string $message): void
{
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Product creation</title>';
    echo '<body style="font-family:Arial,sans-serif;padding:32px;color:#1f2937">';
    echo '<h2>Unable to create product</h2><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="javascript:history.back()">Go back and correct the form</a></p></body></html>';
    exit;
}
function cp_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') ?: 'product';
}
function cp_barcode(PDO $pdo, int $companyId): string
{
    for ($i = 0; $i < 20; $i++) {
        $code = 'UBA' . random_int(100000, 999999);
        $q = $pdo->prepare('SELECT COUNT(*) FROM products WHERE company_id = ? AND barcode = ?');
        $q->execute([$companyId, $code]);
        if ((int)$q->fetchColumn() === 0) return $code;
    }
    throw new RuntimeException('Could not generate a unique product barcode.');
}
function cp_upload_one(array $file, string $directory, array $allowed, int $maxBytes): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('A media upload failed. Please check the file size and try again.');
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) {
        throw new RuntimeException('A selected media file is empty or exceeds the allowed upload size.');
    }
    $original = (string)($file['name'] ?? 'file');
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']) ?: 'application/octet-stream';
    if (!isset($allowed[$extension]) || !in_array($mime, $allowed[$extension], true)) {
        throw new RuntimeException('Unsupported media format: ' . $original);
    }
    if ((substr($mime, 0, 6) === 'image/') && @getimagesize((string)$file['tmp_name']) === false) {
        throw new RuntimeException('An uploaded image is invalid.');
    }
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The product upload folder could not be created.');
    }
    $stored = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = $directory . DIRECTORY_SEPARATOR . $stored;
    if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
        throw new RuntimeException('A media file could not be saved. Check folder permissions.');
    }
    return [
        'name' => $stored,
        'original' => $original,
        'mime' => $mime,
        'size' => (int)$file['size'],
        'type' => (substr($mime, 0, 6) === 'video/') ? 'video' : 'image',
        'path' => $destination
    ];
}
function cp_normalize_variant_config(string $raw, float $parentCost, float $parentSelling, string $parentTracking, bool $trackingAllowed): array
{
    $config = json_decode($raw, true);
    if (!is_array($config) || !isset($config['attributes'], $config['variants']) ||
        !is_array($config['attributes']) || !is_array($config['variants'])) {
        throw new RuntimeException('The variant configuration is invalid. Please rebuild the variant combinations.');
    }
    $attributes = [];
    $seenNames = [];
    foreach ($config['attributes'] as $a) {
        $name = trim((string)($a['name'] ?? ''));
        $type = (string)($a['input_type'] ?? 'select');
        $values = $a['values'] ?? [];
        if ($name === '' || mb_strlen($name) > 100 || !in_array($type, ['select', 'swatch'], true) || !is_array($values)) {
            throw new RuntimeException('Each variant attribute needs a valid name and values.');
        }
        $key = mb_strtolower($name);
        if (isset($seenNames[$key])) throw new RuntimeException('Attribute names must be unique.');
        $seenNames[$key] = true;
        $cleanValues = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '' && !in_array($value, $cleanValues, true)) $cleanValues[] = $value;
        }
        if (!$cleanValues || count($cleanValues) > 100) throw new RuntimeException('Each attribute must have between 1 and 100 unique values.');
        $attributes[] = ['name' => $name, 'input_type' => $type, 'values' => $cleanValues];
    }
    if (!$attributes) throw new RuntimeException('Add at least one variant attribute.');
    $combos = [[]];
    foreach ($attributes as $attribute) {
        $next = [];
        foreach ($combos as $combo) foreach ($attribute['values'] as $value) {
            $next[] = [...$combo, $value];
            if (count($next) > 300) throw new RuntimeException('A product may have no more than 300 generated variants.');
        }
        $combos = $next;
    }
    if (count($config['variants']) !== count($combos)) {
        throw new RuntimeException('The generated variant list does not match the selected attribute values. Please regenerate it.');
    }
    $bySignature = [];
    foreach ($config['variants'] as $v) {
        if (!is_array($v) || !is_array($v['values'] ?? null)) throw new RuntimeException('A variant row is incomplete.');
        $parts = [];
        foreach ($attributes as $a) {
            $value = trim((string)($v['values'][$a['name']] ?? ''));
            if (!in_array($value, $a['values'], true)) throw new RuntimeException('A variant contains an invalid attribute value.');
            $parts[] = $value;
        }
        $sig = json_encode($parts, JSON_UNESCAPED_UNICODE);
        if (isset($bySignature[$sig])) throw new RuntimeException('Duplicate variant combinations were submitted.');
        $policy = (string)($v['tracking_policy'] ?? 'inherit');
        if (!in_array($policy, ['inherit','none','imei','serial','vin'], true)) {
            throw new RuntimeException('Invalid variant tracking policy.');
        }
        if (!$trackingAllowed && $policy !== 'inherit' && $policy !== 'none') {
            throw new RuntimeException('Identifier tracking is only available for Sales Only product categories.');
        }
        // Keep the selected policy (including 'inherit') in the database, while
        // also resolving the effective policy for any downstream stock workflow.
        $effectiveTracking = $policy === 'inherit' ? $parentTracking : $policy;
        if (!in_array($effectiveTracking, ['none','imei','serial','vin'], true)) {
            throw new RuntimeException('The effective variant tracking policy is invalid.');
        }
        $sku = trim((string)($v['sku'] ?? ''));
        $barcode = trim((string)($v['barcode'] ?? ''));
        if (mb_strlen($sku) > 100 || mb_strlen($barcode) > 100) {
            throw new RuntimeException('Variant SKU and barcode values cannot exceed 100 characters.');
        }
        $cost = trim((string)($v['cost_price'] ?? ''));
        $selling = trim((string)($v['selling_price'] ?? ''));
        foreach ([$cost, $selling] as $price) {
            if ($price !== '' && (!is_numeric($price) || (float)$price < 0)) throw new RuntimeException('Variant prices must be valid non-negative numbers.');
        }
        $bySignature[$sig] = [
            'parts' => $parts,
            'sku' => $sku,
            'barcode' => $barcode,
            'cost_price' => $cost === '' ? null : (float)$cost,
            'selling_price' => $selling === '' ? null : (float)$selling,
            'tracking_policy' => $policy,
            'effective_tracking_policy' => $effectiveTracking,
            'is_default' => !empty($v['is_default']) ? 1 : 0
        ];
    }
    $ordered = [];
    foreach ($combos as $combo) {
        $sig = json_encode($combo, JSON_UNESCAPED_UNICODE);
        if (!isset($bySignature[$sig])) throw new RuntimeException('One or more generated combinations are missing.');
        $ordered[] = $bySignature[$sig];
    }
    $defaults = count(array_filter($ordered, fn($v) => $v['is_default'] === 1));
    if ($defaults !== 1) throw new RuntimeException('Select exactly one default variant.');
    return [$attributes, $ordered];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: new_product.php');
    exit;
}
$companyId = (int)($_SESSION['company_id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

$name = trim((string)($_POST['name'] ?? ''));
$categoryId = (int)($_POST['category_id'] ?? 0);
$unitIdPosted = (int)($_POST['unit_id'] ?? 0);
$description = trim((string)($_POST['description'] ?? ''));
$costPrice = trim((string)($_POST['cost_price'] ?? ''));
$sellingPrice = trim((string)($_POST['selling_price'] ?? ''));
$tracking = (string)($_POST['identifier_tracking'] ?? 'none');
$hasVariants = isset($_POST['has_variants']) && (string)$_POST['has_variants'] === '1';

if ($name === '' || mb_strlen($name) > 100 || $categoryId < 1) cp_fail('Enter a product name and select a category.');
if (!is_numeric($costPrice) || (float)$costPrice < 0 || !is_numeric($sellingPrice) || (float)$sellingPrice < 0) cp_fail('Enter valid non-negative cost and selling prices.');
if (!in_array($tracking, ['none','imei','serial','vin'], true)) cp_fail('Select a valid identifier tracking method.');

$categoryStmt = $pdo->prepare('SELECT id, stock_type, unit_id FROM product_categories WHERE id = ? AND company_id = ? LIMIT 1');
$categoryStmt->execute([$categoryId, $companyId]);
$category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
if (!$category) cp_fail('The selected category is invalid for your company.');
$stockType = trim((string)$category['stock_type']);
$trackingAllowed = strcasecmp($stockType, 'Sales Only') === 0;
// Only physical, stock-managed Sales Only categories can require per-unit identifiers.
if (!$trackingAllowed) $tracking = 'none';
$unitId = (int)($category['unit_id'] ?? 0);
if ($unitId <= 0) $unitId = $unitIdPosted;
if ($unitId <= 0) cp_fail('The selected category does not have a valid unit. Please update the category first.');

$duplicate = $pdo->prepare('SELECT id FROM products WHERE company_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
$duplicate->execute([$companyId, $name]);
if ($duplicate->fetchColumn()) cp_fail('A product with this name already exists in your company.');

$variantAttributes = [];
$variants = [];
if ($hasVariants) {
    try {
        [$variantAttributes, $variants] = cp_normalize_variant_config(
            (string)($_POST['variant_config'] ?? ''),
            (float)$costPrice,
            (float)$sellingPrice,
            $tracking,
            $trackingAllowed
        );
    } catch (Throwable $e) {
        cp_fail($e->getMessage());
    }
}

$uploadDirectory = __DIR__ . '/../uploads/products';
$relativeUploadPath = 'uploads/products/';
$allowed = [
    'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
    'webp' => ['image/webp'], 'gif' => ['image/gif'],
    'mp4' => ['video/mp4'], 'webm' => ['video/webm'],
    'mov' => ['video/quicktime']
];
$maxFileBytes = 128 * 1024 * 1024;
$uploadedFiles = [];
try {
    if (!isset($_FILES['featured_img'])) throw new RuntimeException('Please choose a featured product image.');
    $featured = cp_upload_one($_FILES['featured_img'], $uploadDirectory, [
        'jpg'=>$allowed['jpg'], 'jpeg'=>$allowed['jpeg'], 'png'=>$allowed['png'], 'webp'=>$allowed['webp'], 'gif'=>$allowed['gif']
    ], $maxFileBytes);
    if (!$featured) throw new RuntimeException('Please choose a featured product image.');
    $uploadedFiles[] = $featured;

    $additional = [];
    if (isset($_FILES['product_media']) && is_array($_FILES['product_media']['name'] ?? null)) {
        $files = $_FILES['product_media'];
        $count = count($files['name']);
        if ($count > 20) throw new RuntimeException('You can upload up to 20 additional media files at a time.');
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $file = [
                'name'=>$files['name'][$i], 'type'=>$files['type'][$i] ?? '',
                'tmp_name'=>$files['tmp_name'][$i], 'error'=>$files['error'][$i],
                'size'=>$files['size'][$i]
            ];
            $item = cp_upload_one($file, $uploadDirectory, $allowed, $maxFileBytes);
            if ($item) { $additional[] = $item; $uploadedFiles[] = $item; }
        }
    }
} catch (Throwable $e) {
    foreach ($uploadedFiles as $file) if (is_file($file['path'])) @unlink($file['path']);
    cp_fail($e->getMessage());
}

try {
    $pdo->beginTransaction();

    $slugBase = cp_slug($name);
    $slug = $slugBase;
    $suffix = 2;
    $slugCheck = $pdo->prepare('SELECT COUNT(*) FROM products WHERE company_id = ? AND slug = ?');
    while (true) {
        $slugCheck->execute([$companyId, $slug]);
        if ((int)$slugCheck->fetchColumn() === 0) break;
        $slug = $slugBase . '-' . $suffix++;
    }
    $barcode = cp_barcode($pdo, $companyId);
    $createdAt = date('Y-m-d H:i:s');

    $insertProduct = $pdo->prepare("
        INSERT INTO products
        (company_id, name, slug, barcode, category_id, stock_type, imei_tracking,
         unit_id, cost_price, selling_price, description, created_at, created_by,
         identifier_tracking, has_variants)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertProduct->execute([
        $companyId, $name, $slug, $barcode, $categoryId, $stockType,
        $tracking === 'imei' ? 1 : 0, $unitId, $costPrice, $sellingPrice,
        $description, $createdAt, (string)$userId, $tracking, $hasVariants ? 1 : 0
    ]);
    $productId = (int)$pdo->lastInsertId();

    $imageInsert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, created_at) VALUES (?, ?, NOW())');
    $mediaInsert = $pdo->prepare("
        INSERT INTO product_media
        (product_id, variant_id, company_id, media_type, media_path, thumbnail_path,
         media_name, mime_type, file_size, display_order, is_primary, legacy_image_id,
         created_by, created_at, updated_at)
        VALUES (?, NULL, ?, ?, ?, NULL, ?, ?, ?, ?, ?, NULL, ?, NOW(), NOW())
    ");

    $imageOrder = 0;
    foreach (array_merge([$featured], $additional) as $index => $file) {
        $legacyImageId = null;
        if ($file['type'] === 'image') {
            $imageInsert->execute([$productId, $file['name']]);
            $legacyImageId = (int)$pdo->lastInsertId();
        }
        $mediaInsert->execute([
            $productId, $companyId, $file['type'], $file['name'], $file['original'],
            $file['mime'], $file['size'], $index, $index === 0 ? 1 : 0, $userId ?: null
        ]);
    }

    if ($hasVariants) {
        $attributeIds = [];
        $attributeInsert = $pdo->prepare("
            INSERT INTO product_attributes
            (company_id, product_id, attribute_name, input_type, is_required, display_order, status, created_at)
            VALUES (?, ?, ?, ?, 1, ?, 1, NOW())
        ");
        $valueInsert = $pdo->prepare("
            INSERT INTO product_attribute_values
            (attribute_id, value_name, value_code, color_code, display_order, status, created_at)
            VALUES (?, ?, NULL, NULL, ?, 1, NOW())
        ");
        foreach ($variantAttributes as $index => $attribute) {
            $attributeInsert->execute([$companyId, $productId, $attribute['name'], $attribute['input_type'], $index]);
            $attributeId = (int)$pdo->lastInsertId();
            $attributeIds[] = ['id'=>$attributeId, 'name'=>$attribute['name']];
            foreach ($attribute['values'] as $valueIndex => $valueName) {
                $valueInsert->execute([$attributeId, $valueName, $valueIndex]);
                $attributeIds[$index]['values'][$valueName] = (int)$pdo->lastInsertId();
            }
        }

        $variantInsert = $pdo->prepare("
            INSERT INTO product_variants
            (company_id, product_id, sku, barcode, variant_name, cost_price, selling_price,
             tracking_policy, is_default, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $variantValueInsert = $pdo->prepare("
            INSERT INTO product_variant_values (variant_id, attribute_id, attribute_value_id)
            VALUES (?, ?, ?)
        ");
        $seenSku = $seenVariantBarcode = [];
        foreach ($variants as $variant) {
            $sku = $variant['sku'] !== '' ? $variant['sku'] : null;
            $variantBarcode = $variant['barcode'] !== '' ? $variant['barcode'] : null;
            foreach ([['sku',$sku], ['barcode',$variantBarcode]] as [$field,$value]) {
                if ($value === null) continue;
                $normalized = strtolower($value);
                if ($field === 'sku' && isset($seenSku[$normalized])) throw new RuntimeException('Variant SKUs must be unique.');
                if ($field === 'barcode' && isset($seenVariantBarcode[$normalized])) throw new RuntimeException('Variant barcodes must be unique.');
                $check = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE company_id = ? AND `$field` = ?");
                $check->execute([$companyId, $value]);
                if ((int)$check->fetchColumn() > 0) throw new RuntimeException("The variant $field is already in use by another variant.");
                if ($field === 'sku') $seenSku[$normalized] = true;
                else $seenVariantBarcode[$normalized] = true;
            }
            $variantName = implode(' / ', array_map(
                fn($attribute, $i) => $attribute['name'] . ': ' . $variant['parts'][$i],
                $variantAttributes, array_keys($variantAttributes)
            ));
            $variantInsert->execute([
                $companyId, $productId, $sku, $variantBarcode, $variantName,
                $variant['cost_price'], $variant['selling_price'],
                $variant['tracking_policy'], $variant['is_default']
            ]);
            $variantId = (int)$pdo->lastInsertId();
            foreach ($attributeIds as $i => $attribute) {
                $valueId = $attribute['values'][$variant['parts'][$i]] ?? 0;
                if ($valueId <= 0) throw new RuntimeException('A variant attribute mapping could not be created.');
                $variantValueInsert->execute([$variantId, $attribute['id'], $valueId]);
            }
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($uploadedFiles as $file) if (is_file($file['path'])) @unlink($file['path']);
    error_log('Product creation failed: ' . $e->getMessage());
    cp_fail($e instanceof RuntimeException ? $e->getMessage() : 'The product could not be saved. Please check your database setup and try again.');
}

header('Location: products.php?created=1');
exit;
