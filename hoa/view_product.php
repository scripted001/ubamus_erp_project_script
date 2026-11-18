<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


// ============================================================
// COMPANY + PRODUCT
// ============================================================


$company_id = $_SESSION['company_id'] ?? null;
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id < 1) {
    echo "Invalid product ID.";
    exit;
}


// ============================================================
// PRODUCT IMAGE ACTIONS (COMPANY-SCOPED + CSRF PROTECTED)
// ============================================================

if (empty($_SESSION['product_image_csrf'])) {
    $_SESSION['product_image_csrf'] = bin2hex(random_bytes(32));
}

if (!function_exists('productMediaDeleteFileIfUnused')) {
    function productMediaDeleteFileIfUnused(PDO $pdo, string $storedPath): void
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') return;

        $usageStmt = $pdo->prepare("
            SELECT
                (SELECT COUNT(*) FROM product_media WHERE media_path = ? OR thumbnail_path = ?) +
                (SELECT COUNT(*) FROM product_images WHERE image_path = ?)
        ");
        $usageStmt->execute([$storedPath, $storedPath, $storedPath]);
        if ((int)$usageStmt->fetchColumn() > 0) return;

        $relativePath = str_replace('\\', '/', $storedPath);
        $relativePath = preg_replace('#^(?:\.\./)+#', '', $relativePath);
        $relativePath = preg_replace('#^/?uploads/products/#', '', $relativePath);
        $relativePath = preg_replace('#^products/#', '', $relativePath);
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || strpos($relativePath, "\0") !== false) return;

        $uploadRoot = realpath(__DIR__ . '/../uploads/products');
        if (!$uploadRoot) return;
        $candidate = realpath($uploadRoot . DIRECTORY_SEPARATOR . $relativePath);
        $rootPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($candidate && strpos($candidate, $rootPrefix) === 0 && is_file($candidate)) {
            @unlink($candidate);
        }
    }
}

$productImageNotice = $_SESSION['product_image_notice'] ?? null;
unset($_SESSION['product_image_notice']);

$deleteAction = (string)($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($deleteAction, ['delete_product_image', 'delete_product_media'], true)) {

    $postedProductId = (int)($_POST['product_id'] ?? 0);
    $imageId = (int)($_POST['image_id'] ?? 0);
    $mediaId = (int)($_POST['media_id'] ?? 0);
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    $pathsToCheck = [];

    try {
        if (!hash_equals((string)$_SESSION['product_image_csrf'], $postedToken)) {
            throw new RuntimeException('Security check failed. Please refresh the page and try again.');
        }
        if ($postedProductId !== (int)$product_id) {
            throw new RuntimeException('Invalid product media request.');
        }

        if ($deleteAction === 'delete_product_media') {
            if ($mediaId < 1) throw new RuntimeException('Invalid media selection.');

            $mediaStmt = $pdo->prepare("
                SELECT pm.id, pm.media_path, pm.thumbnail_path, pm.legacy_image_id
                FROM product_media pm
                INNER JOIN products p ON p.id = pm.product_id
                WHERE pm.id = ? AND pm.product_id = ? AND pm.company_id = p.company_id
                  AND p.company_id = ?
                LIMIT 1
            ");
            $mediaStmt->execute([$mediaId, $product_id, $company_id]);
            $mediaToDelete = $mediaStmt->fetch(PDO::FETCH_ASSOC);
            if (!$mediaToDelete) {
                throw new RuntimeException('Media not found or you do not have permission to delete it.');
            }

            $pathsToCheck[] = (string)$mediaToDelete['media_path'];
            if (!empty($mediaToDelete['thumbnail_path'])) $pathsToCheck[] = (string)$mediaToDelete['thumbnail_path'];

            $pdo->beginTransaction();
            $deleteMediaStmt = $pdo->prepare('DELETE FROM product_media WHERE id = ? AND product_id = ? AND company_id = ?');
            $deleteMediaStmt->execute([$mediaId, $product_id, $company_id]);
            if ($deleteMediaStmt->rowCount() !== 1) {
                throw new RuntimeException('The media could not be deleted. Please try again.');
            }

            $legacyImageId = (int)($mediaToDelete['legacy_image_id'] ?? 0);
            if ($legacyImageId > 0) {
                $legacyStmt = $pdo->prepare('SELECT image_path FROM product_images WHERE id = ? AND product_id = ? LIMIT 1');
                $legacyStmt->execute([$legacyImageId, $product_id]);
                $legacyPath = $legacyStmt->fetchColumn();
                if ($legacyPath !== false) {
                    $pathsToCheck[] = (string)$legacyPath;
                    $deleteLegacy = $pdo->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?');
                    $deleteLegacy->execute([$legacyImageId, $product_id]);
                }
            }
            $pdo->commit();
            foreach (array_unique($pathsToCheck) as $pathToCheck) {
                productMediaDeleteFileIfUnused($pdo, (string)$pathToCheck);
            }
            $_SESSION['product_image_notice'] = [
                'type' => 'success',
                'message' => 'Product media deleted successfully.'
            ];
        } else {
            if ($imageId < 1) throw new RuntimeException('Invalid product image request.');

            $imageStmt = $pdo->prepare("
                SELECT pi.id, pi.image_path
                FROM product_images pi
                INNER JOIN products p ON p.id = pi.product_id
                WHERE pi.id = ? AND pi.product_id = ? AND p.company_id = ?
                LIMIT 1
            ");
            $imageStmt->execute([$imageId, $product_id, $company_id]);
            $imageToDelete = $imageStmt->fetch(PDO::FETCH_ASSOC);
            if (!$imageToDelete) {
                throw new RuntimeException('Image not found or you do not have permission to delete it.');
            }

            $pathsToCheck[] = (string)$imageToDelete['image_path'];
            $pdo->beginTransaction();

            // Remove the media-library row linked to this legacy image, if one exists.
            $linkedMediaStmt = $pdo->prepare('SELECT media_path, thumbnail_path FROM product_media WHERE legacy_image_id = ? AND product_id = ? AND company_id = ?');
            $linkedMediaStmt->execute([$imageId, $product_id, $company_id]);
            foreach ($linkedMediaStmt->fetchAll(PDO::FETCH_ASSOC) as $linkedMedia) {
                if (!empty($linkedMedia['media_path'])) $pathsToCheck[] = (string)$linkedMedia['media_path'];
                if (!empty($linkedMedia['thumbnail_path'])) $pathsToCheck[] = (string)$linkedMedia['thumbnail_path'];
            }
            $deleteLinkedMedia = $pdo->prepare('DELETE FROM product_media WHERE legacy_image_id = ? AND product_id = ? AND company_id = ?');
            $deleteLinkedMedia->execute([$imageId, $product_id, $company_id]);

            $deleteStmt = $pdo->prepare("
                DELETE pi
                FROM product_images pi
                INNER JOIN products p ON p.id = pi.product_id
                WHERE pi.id = ? AND pi.product_id = ? AND p.company_id = ?
            ");
            $deleteStmt->execute([$imageId, $product_id, $company_id]);
            if ($deleteStmt->rowCount() !== 1) {
                throw new RuntimeException('The image could not be deleted. Please try again.');
            }

            $pdo->commit();
            foreach (array_unique($pathsToCheck) as $pathToCheck) {
                productMediaDeleteFileIfUnused($pdo, (string)$pathToCheck);
            }
            $_SESSION['product_image_notice'] = [
                'type' => 'success',
                'message' => 'Product image deleted successfully.'
            ];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['product_image_notice'] = [
            'type' => 'error',
            'message' => $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to delete this media right now. Please try again.'
        ];
    }

    header('Location: ' . basename($_SERVER['PHP_SELF'] ?? 'products.php') . '?id=' . (int)$product_id);
    exit;
}


// ============================================================
// 1. PRODUCT CORE DETAILS
// ============================================================

$sql = "
SELECT 
    p.*,
    pc.name AS category_name,
    pc.stock_type AS category_stock_type,
    co.company_name AS company_name,
    u.email AS created_by_email
FROM products p
LEFT JOIN product_categories pc 
    ON p.category_id = pc.id
LEFT JOIN companies co 
    ON p.company_id = co.id
LEFT JOIN users u 
    ON p.created_by = u.id
WHERE p.company_id = ?
AND p.id = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $company_id,
    $product_id
]);

$single_product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$single_product) {
    echo "Product not found.";
    exit;
}


// ============================================================
// 2. TOTAL STOCK
// ============================================================

$stmt = $pdo->prepare("
    SELECT SUM(quantity) AS total_stock
    FROM stocks
    WHERE product_id = ?
    AND company_id = ?
");

$stmt->execute([
    $product_id,
    $company_id
]);

$total_stock = (float)($stmt->fetchColumn() ?? 0);


// ============================================================
// 3. ASSIGNED STOCK
// ============================================================

$stmt = $pdo->prepare("
    SELECT SUM(s.quantity) AS assigned_stock
    FROM stocks s
    JOIN stocks_assigned sa 
        ON sa.stock_id = s.id
    WHERE s.product_id = ?
    AND s.company_id = ?
");

$stmt->execute([
    $product_id,
    $company_id
]);

$assigned_stock = (float)($stmt->fetchColumn() ?? 0);


// Prevent negative available stock

$unassigned_stock = max(
    0,
    $total_stock - $assigned_stock
);


// ============================================================
// 4. PRODUCT PRICING
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        cost_price,
        selling_price,
        unit_id,
        stock_type
    FROM products
    WHERE id = ?
    AND company_id = ?
    LIMIT 1
");

$stmt->execute([
    $product_id,
    $company_id
]);

$productDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];

$cost_price = (float)($productDetails['cost_price'] ?? 0);

$selling_price = (float)($productDetails['selling_price'] ?? 0);


// ============================================================
// 5. STOCK VALUES
// ============================================================

$total_stock_value =
    $total_stock * $selling_price;

$assigned_value =
    $assigned_stock * $selling_price;

$unassigned_value =
    $unassigned_stock * $selling_price;


// ============================================================
// 6. PRODUCT FEATURES
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        feature_name,
        feature_value
    FROM product_features
    WHERE product_id = ?
");

$stmt->execute([
    $product_id
]);

$features = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// 7. PRODUCT IMAGES
// ============================================================

$hasVariants = !empty($single_product['has_variants']);
$variants = [];
if ($hasVariants) {
    $variantStmt = $pdo->prepare("
        SELECT id, variant_name, sku, is_default, is_active
        FROM product_variants
        WHERE product_id = ? AND company_id = ?
        ORDER BY is_default DESC, id ASC
    ");
    $variantStmt->execute([$product_id, $company_id]);
    $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pull all current media rows and append only legacy product_images that have
// not already been represented by product_media. Legacy rows are parent-level.
$mediaStmt = $pdo->prepare("
    SELECT media_rows.*
    FROM (
        SELECT
            pm.id AS media_id,
            pm.variant_id,
            pm.media_type COLLATE utf8mb4_unicode_ci AS media_type,
            pm.media_path COLLATE utf8mb4_unicode_ci AS media_path,
            pm.media_path COLLATE utf8mb4_unicode_ci AS image_path,
            pm.thumbnail_path COLLATE utf8mb4_unicode_ci AS thumbnail_path,
            pm.media_name COLLATE utf8mb4_unicode_ci AS media_name,
            pm.legacy_image_id,
            pm.display_order,
            pm.is_primary,
            NULL AS legacy_id
        FROM product_media pm
        WHERE pm.product_id = ? AND pm.company_id = ?

        UNION ALL

        SELECT
            NULL AS media_id,
            NULL AS variant_id,
            'image' COLLATE utf8mb4_unicode_ci AS media_type,
            pi.image_path COLLATE utf8mb4_unicode_ci AS media_path,
            pi.image_path COLLATE utf8mb4_unicode_ci AS image_path,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS thumbnail_path,
            pi.image_path COLLATE utf8mb4_unicode_ci AS media_name,
            NULL AS legacy_image_id,
            pi.id AS display_order,
            0 AS is_primary,
            pi.id AS legacy_id
        FROM product_images pi
        LEFT JOIN product_media linked_pm
            ON linked_pm.legacy_image_id = pi.id
           AND linked_pm.product_id = pi.product_id
           AND linked_pm.company_id = ?
        WHERE pi.product_id = ? AND linked_pm.id IS NULL
    ) AS media_rows
    ORDER BY (media_rows.variant_id IS NOT NULL) ASC, media_rows.variant_id ASC, media_rows.display_order ASC, media_rows.media_id ASC, media_rows.legacy_id ASC
");
$mediaStmt->execute([$product_id, $company_id, $company_id, $product_id]);
$images = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
$imageCount = count(array_filter($images, static fn($item) => ($item['media_type'] ?? 'image') === 'image'));
$videoCount = count(array_filter($images, static fn($item) => ($item['media_type'] ?? '') === 'video'));

$resolveProductMediaUrl = static function (?string $storedPath): string {
    $path = trim((string)$storedPath);
    if ($path === '') return '';
    $clean = ltrim(str_replace('\\', '/', $path), '/');
    if (strpos($clean, 'uploads/products/') === 0) return '../' . $clean;
    if (strpos($clean, '../uploads/products/') === 0) return $clean;
    if (strpos($clean, 'products/') === 0) return '../uploads/' . $clean;
    return '../uploads/products/' . $clean;
};


// ============================================================
// 8. PRODUCT IMAGE RESOLVER
//
// The supplied database schema does not contain products.featured_img.
// The main product image is therefore taken from the first image in
// product_images. If that image is deleted, the next remaining image
// automatically becomes the main image on page reload.
// ============================================================

$featuredImage = null;
$featuredCandidates = array_values(array_filter($images, static function ($item) {
    return ($item['media_type'] ?? 'image') === 'image' && !empty($item['image_path']);
}));
if ($featuredCandidates) {
    usort($featuredCandidates, static function ($a, $b) {
        $aParent = empty($a['variant_id']) ? 0 : 1;
        $bParent = empty($b['variant_id']) ? 0 : 1;
        if ($aParent !== $bParent) return $aParent <=> $bParent;
        $aPrimary = !empty($a['is_primary']) ? 0 : 1;
        $bPrimary = !empty($b['is_primary']) ? 0 : 1;
        if ($aPrimary !== $bPrimary) return $aPrimary <=> $bPrimary;
        return ((int)($a['display_order'] ?? 0)) <=> ((int)($b['display_order'] ?? 0));
    });
    $featuredImage = trim((string)$featuredCandidates[0]['image_path']);
}


// ------------------------------------------------------------
// Build final browser image URL
// ------------------------------------------------------------

$productImageUrl = null;

if ($featuredImage) {

    /*
     * Remove accidental leading slashes.
     */

    $cleanImage = ltrim(
        str_replace('\\', '/', $featuredImage),
        '/'
    );


    /*
     * If database already contains uploads/products/,
     * don't duplicate it.
     */

    if (
        strpos($cleanImage, 'uploads/products/') === 0
    ) {

        $productImageUrl =
            '../' . $cleanImage;

    }

    /*
     * If database contains ../uploads/products/
     */

    elseif (
        strpos($cleanImage, '../uploads/products/') === 0
    ) {

        $productImageUrl =
            $cleanImage;

    }

    /*
     * If database contains products/filename
     */

    elseif (
        strpos($cleanImage, 'products/') === 0
    ) {

        $productImageUrl =
            '../uploads/' . $cleanImage;

    }

    /*
     * Otherwise assume only the filename was stored.
     */

    else {

        $productImageUrl =
            '../uploads/products/' . $cleanImage;

    }
}


// ============================================================
// 9. STOCK STATUS
// ============================================================

if ($total_stock <= 0) {

    $stockStatus = 'Out of Stock';
    $stockClass = 'danger';
    $stockIcon = 'fa-box-open';

} elseif ($total_stock <= 10) {

    $stockStatus = 'Low Stock';
    $stockClass = 'warning';
    $stockIcon = 'fa-triangle-exclamation';

} else {

    $stockStatus = 'In Stock';
    $stockClass = 'success';
    $stockIcon = 'fa-circle-check';

}


// ============================================================
// 10. PROFIT
// ============================================================

$unitProfit =
    $selling_price - $cost_price;

$profitMargin =
    $selling_price > 0
        ? (($unitProfit / $selling_price) * 100)
        : 0;


// ============================================================
// 11. ASSIGNED STOCK PERCENTAGE
// ============================================================

$assignedPercentage =
    $total_stock > 0
        ? min(
            100,
            max(
                0,
                ($assigned_stock / $total_stock) * 100
            )
        )
        : 0;


// ============================================================
// 12. CATALOGUE
// ============================================================

$cataloguePath =
    "../uploads/products/catalogues/" .
    ($single_product['slug'] ?? '') .
    ".pdf";

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    http-equiv="content-type"
    content="text/html;charset=utf-8"
>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    http-equiv="X-UA-Compatible"
    content="ie=edge"
>

<title>
    <?= htmlspecialchars($company_name ?? $single_product['company_name'] ?? 'Company'); ?>
    |
    Product Intelligence
</title>

<link
    rel="icon"
    href="src/images/icons/favicon.png"
>

<link
    rel="stylesheet"
    href="../fontawesome/css/all.css"
>

<link
    href="style.css"
    rel="stylesheet"
>

<link
    href="modal.css"
    rel="stylesheet"
>


<style>

/* ============================================================
   PRODUCT INTELLIGENCE
   PREMIUM LIGHT GLASS SYSTEM
============================================================ */

:root {

    --pi-bg: #f4f7fb;

    --pi-surface:
        rgba(255,255,255,.78);

    --pi-surface-solid:
        #ffffff;

    --pi-border:
        rgba(15,23,42,.09);

    --pi-text:
        #142033;

    --pi-muted:
        #6b778c;

    --pi-primary:
        #4f46e5;

    --pi-primary-dark:
        #3730a3;

    --pi-cyan:
        #0891b2;

    --pi-green:
        #059669;

    --pi-orange:
        #d97706;

    --pi-red:
        #dc2626;

    --pi-shadow:
        0 20px 55px rgba(15,23,42,.08);

    --pi-shadow-soft:
        0 10px 30px rgba(15,23,42,.055);
}


* {
    box-sizing: border-box;
}


body {

    background:

        radial-gradient(
            circle at 8% 5%,
            rgba(79,70,229,.08),
            transparent 26%
        ),

        radial-gradient(
            circle at 92% 10%,
            rgba(8,145,178,.07),
            transparent 25%
        ),

        linear-gradient(
            135deg,
            #f8fafc 0%,
            #f1f5f9 100%
        );

    color:
        var(--pi-text);
}


/* ============================================================
   MAIN CONTAINER
============================================================ */

.pi-container {

    width:
        min(1500px, 100%);

    margin:
        0 auto;

    padding:
        18px;
}


@media (min-width: 768px) {

    .pi-container {
        padding:
            28px;
    }

}


@media (min-width: 1280px) {

    .pi-container {
        padding:
            32px;
    }

}


/* ============================================================
   BREADCRUMB
============================================================ */

.pi-breadcrumb {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        22px;

    color:
        var(--pi-muted);

    font-size:
        13px;

    flex-wrap:
        wrap;
}


.pi-breadcrumb a {

    color:
        var(--pi-muted);

    text-decoration:
        none;

    transition:
        .2s ease;
}


.pi-breadcrumb a:hover {

    color:
        var(--pi-primary);
}


.pi-breadcrumb i {

    font-size:
        9px;

    opacity:
        .55;
}


/* ============================================================
   HERO
============================================================ */

.pi-hero {

    position:
        relative;

    overflow:
        hidden;

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr);

    gap:
        24px;

    padding:
        24px;

    margin-bottom:
        22px;

    border:
        1px solid var(--pi-border);

    border-radius:
        26px;

    background:
        var(--pi-surface);

    backdrop-filter:
        blur(20px);

    -webkit-backdrop-filter:
        blur(20px);

    box-shadow:
        var(--pi-shadow);
}


.pi-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        280px;

    height:
        280px;

    right:
        -110px;

    top:
        -150px;

    border-radius:
        50%;

    background:
        rgba(79,70,229,.11);

    filter:
        blur(5px);

    pointer-events:
        none;
}


.pi-hero::after {

    content:
        "";

    position:
        absolute;

    width:
        180px;

    height:
        180px;

    left:
        -100px;

    bottom:
        -120px;

    border-radius:
        50%;

    background:
        rgba(8,145,178,.08);

    pointer-events:
        none;
}


@media (min-width: 900px) {

    .pi-hero {

        grid-template-columns:
            minmax(0, 1fr) auto;

        align-items:
            center;

        padding:
            30px;
    }

}


/* ============================================================
   HERO IDENTITY
============================================================ */

.pi-identity {

    position:
        relative;

    z-index:
        2;

    display:
        flex;

    align-items:
        center;

    gap:
        18px;

    min-width:
        0;
}


/* ============================================================
   FEATURED PRODUCT IMAGE
============================================================ */

.pi-product-avatar {

    width:
        96px;

    height:
        96px;

    flex:
        0 0 96px;

    border-radius:
        24px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        linear-gradient(
            145deg,
            #eef2ff,
            #ffffff
        );

    border:
        1px solid
        rgba(79,70,229,.14);

    box-shadow:

        0 12px 30px
        rgba(79,70,229,.13),

        inset 0 1px 0
        rgba(255,255,255,.9);

    position:
        relative;

    isolation:
        isolate;
}


/*
 * Futuristic image glow
 */

.pi-product-avatar::before {

    content:
        "";

    position:
        absolute;

    inset:
        5px;

    border-radius:
        19px;

    background:
        linear-gradient(
            135deg,
            rgba(79,70,229,.12),
            rgba(6,182,212,.08),
            transparent
        );

    z-index:
        -1;

    pointer-events:
        none;
}


.pi-product-avatar::after {

    content:
        "";

    position:
        absolute;

    inset:
        0;

    border-radius:
        inherit;

    box-shadow:
        inset 0 0 0 1px
        rgba(255,255,255,.45);

    pointer-events:
        none;

    z-index:
        3;
}


/*
 * ACTUAL PRODUCT IMAGE
 */

.pi-product-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    object-position:
        center;

    display:
        block;

    transition:
        transform .35s ease,
        filter .35s ease;
}


.pi-product-avatar:hover img {

    transform:
        scale(1.07);

    filter:
        saturate(1.08);
}


/*
 * FALLBACK ICON
 */

.pi-product-avatar i {

    font-size:
        30px;

    color:
        var(--pi-primary);

    opacity:
        .9;
}


@media (max-width: 520px) {

    .pi-product-avatar {

        width:
            68px;

        height:
            68px;

        flex-basis:
            68px;

        border-radius:
            18px;
    }

    .pi-product-avatar::before {

        border-radius:
            14px;
    }

}


/* ============================================================
   HERO TEXT
============================================================ */

.pi-eyebrow {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    color:
        var(--pi-primary);

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        .12em;

    text-transform:
        uppercase;

    margin-bottom:
        5px;
}


.pi-title {

    margin:
        0;

    font-size:
        clamp(
            21px,
            3vw,
            31px
        );

    line-height:
        1.15;

    font-weight:
        800;

    letter-spacing:
        -.025em;

    color:
        #111827;

    word-break:
        break-word;
}


.pi-category {

    margin-top:
        8px;

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        7px;

    color:
        var(--pi-muted);

    font-size:
        13px;
}


.pi-category i {

    color:
        var(--pi-primary);
}


/* ============================================================
   STATUS
============================================================ */

.pi-status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    padding:
        7px 11px;

    border-radius:
        999px;

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        .02em;
}


.pi-status.success {

    color:
        #047857;

    background:
        #ecfdf5;

    border:
        1px solid #a7f3d0;
}


.pi-status.warning {

    color:
        #b45309;

    background:
        #fffbeb;

    border:
        1px solid #fde68a;
}


.pi-status.danger {

    color:
        #b91c1c;

    background:
        #fef2f2;

    border:
        1px solid #fecaca;
}


/* ============================================================
   HERO ACTIONS
============================================================ */

.pi-actions {

    position:
        relative;

    z-index:
        3;

    display:
        flex;

    gap:
        9px;

    flex-wrap:
        wrap;
}


.pi-btn {

    min-height:
        43px;

    padding:
        0 15px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    border-radius:
        13px;

    border:
        1px solid var(--pi-border);

    background:
        rgba(255,255,255,.82);

    color:
        #334155;

    font-size:
        12px;

    font-weight:
        750;

    text-decoration:
        none;

    cursor:
        pointer;

    box-shadow:
        0 7px 18px
        rgba(15,23,42,.05);

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;
}


.pi-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 24px
        rgba(15,23,42,.09);

    border-color:
        rgba(79,70,229,.2);
}


.pi-btn-primary {

    color:
        #fff;

    border-color:
        transparent;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #6366f1
        );

    box-shadow:
        0 10px 24px
        rgba(79,70,229,.22);
}


.pi-btn-primary:hover {

    color:
        #fff;
}


.pi-btn i {

    font-size:
        13px;
}


/* ============================================================
   SECTION
============================================================ */

.pi-section {

    margin-bottom:
        22px;
}


.pi-section-heading {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    margin-bottom:
        13px;
}


.pi-section-title {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    margin:
        0;

    color:
        #182235;

    font-size:
        16px;

    font-weight:
        800;
}


.pi-section-title-icon {

    width:
        34px;

    height:
        34px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        11px;

    color:
        var(--pi-primary);

    background:
        #eef2ff;

    border:
        1px solid #e0e7ff;
}


/* ============================================================
   KPI GRID
============================================================ */

.pi-kpis {

    display:
        grid;

    grid-template-columns:
        1fr;

    gap:
        13px;
}


@media (min-width: 550px) {

    .pi-kpis {

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );
    }

}


@media (min-width: 1000px) {

    .pi-kpis {

        grid-template-columns:
            repeat(
                4,
                minmax(0,1fr)
            );
    }

}


.pi-kpi {

    position:
        relative;

    overflow:
        hidden;

    padding:
        19px;

    border:
        1px solid var(--pi-border);

    border-radius:
        20px;

    background:
        var(--pi-surface);

    backdrop-filter:
        blur(18px);

    -webkit-backdrop-filter:
        blur(18px);

    box-shadow:
        var(--pi-shadow-soft);
}


.pi-kpi::after {

    content:
        "";

    position:
        absolute;

    width:
        80px;

    height:
        80px;

    right:
        -35px;

    bottom:
        -35px;

    border-radius:
        50%;

    background:
        rgba(79,70,229,.06);
}


.pi-kpi-top {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;
}


.pi-kpi-icon {

    width:
        40px;

    height:
        40px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        13px;

    color:
        var(--pi-primary);

    background:
        #eef2ff;
}


.pi-kpi-label {

    margin-top:
        15px;

    color:
        var(--pi-muted);

    font-size:
        11px;

    font-weight:
        700;

    text-transform:
        uppercase;

    letter-spacing:
        .07em;
}


.pi-kpi-value {

    margin-top:
        5px;

    color:
        #111827;

    font-size:
        clamp(
            21px,
            3vw,
            28px
        );

    line-height:
        1.1;

    font-weight:
        850;

    letter-spacing:
        -.03em;

    word-break:
        break-word;
}


.pi-kpi-sub {

    margin-top:
        7px;

    color:
        var(--pi-muted);

    font-size:
        11px;
}


/* ============================================================
   MAIN INFORMATION GRID
============================================================ */

.pi-layout {

    display:
        grid;

    grid-template-columns:
        minmax(0,1fr);

    gap:
        20px;
}


@media (min-width: 1050px) {

    .pi-layout {

        grid-template-columns:
            minmax(0,1.4fr)
            minmax(320px,.6fr);

        align-items:
            start;
    }

}


/* ============================================================
   GLASS CARD
============================================================ */

.pi-card {

    border:
        1px solid var(--pi-border);

    border-radius:
        22px;

    background:
        var(--pi-surface);

    backdrop-filter:
        blur(18px);

    -webkit-backdrop-filter:
        blur(18px);

    box-shadow:
        var(--pi-shadow-soft);

    overflow:
        hidden;
}


.pi-card-head {

    padding:
        19px 20px;

    border-bottom:
        1px solid
        rgba(15,23,42,.06);

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;
}


.pi-card-head h3 {

    margin:
        0;

    color:
        #172033;

    font-size:
        15px;

    font-weight:
        800;
}


.pi-card-head p {

    margin:
        4px 0 0;

    color:
        var(--pi-muted);

    font-size:
        11px;
}


.pi-card-body {

    padding:
        20px;
}


/* ============================================================
   INFO GRID
============================================================ */

.pi-info-grid {

    display:
        grid;

    grid-template-columns:
        1fr;

    gap:
        0;
}


@media (min-width: 650px) {

    .pi-info-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );

        column-gap:
            26px;
    }

}


.pi-info-item {

    padding:
        15px 0;

    border-bottom:
        1px solid
        rgba(15,23,42,.055);
}


.pi-info-item:last-child {

    border-bottom:
        0;
}


@media (min-width: 650px) {

    .pi-info-item:nth-last-child(-n+2) {

        border-bottom:
            0;
    }

}


.pi-info-label {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    color:
        var(--pi-muted);

    font-size:
        10px;

    font-weight:
        800;

    letter-spacing:
        .065em;

    text-transform:
        uppercase;
}


.pi-info-label i {

    color:
        var(--pi-primary);

    font-size:
        11px;
}


.pi-info-value {

    margin-top:
        7px;

    color:
        #172033;

    font-size:
        13px;

    font-weight:
        700;

    line-height:
        1.45;

    word-break:
        break-word;
}


/* ============================================================
   PRICE PANEL
============================================================ */

.pi-price-stack {

    display:
        grid;

    gap:
        12px;
}


.pi-price-row {

    padding:
        16px;

    border:
        1px solid
        rgba(15,23,42,.07);

    border-radius:
        15px;

    background:
        rgba(255,255,255,.65);
}


.pi-price-row-top {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;
}


.pi-price-label {

    color:
        var(--pi-muted);

    font-size:
        11px;

    font-weight:
        700;
}


.pi-price-value {

    margin-top:
        5px;

    color:
        #111827;

    font-size:
        20px;

    font-weight:
        850;
}


.pi-price-meta {

    margin-top:
        4px;

    color:
        var(--pi-muted);

    font-size:
        10px;
}


.pi-profit {

    border-color:
        rgba(5,150,105,.14);

    background:
        linear-gradient(
            135deg,
            rgba(236,253,245,.9),
            rgba(255,255,255,.72)
        );
}


.pi-profit .pi-price-value {

    color:
        #047857;
}


/* ============================================================
   STOCK DISTRIBUTION
============================================================ */

.pi-stock-box {

    margin-top:
        18px;
}


.pi-stock-bar {

    height:
        9px;

    overflow:
        hidden;

    border-radius:
        999px;

    background:
        #e5e7eb;
}


.pi-stock-fill {

    height:
        100%;

    width:
        <?= $assignedPercentage ?>%;

    border-radius:
        inherit;

    background:
        linear-gradient(
            90deg,
            #4f46e5,
            #06b6d4
        );
}


.pi-stock-legend {

    display:
        flex;

    justify-content:
        space-between;

    gap:
        12px;

    margin-top:
        10px;

    color:
        var(--pi-muted);

    font-size:
        10px;
}


.pi-stock-legend strong {

    color:
        #334155;
}


/* ============================================================
   FEATURES
============================================================ */

.pi-feature-grid {

    display:
        grid;

    grid-template-columns:
        1fr;

    gap:
        10px;
}


@media (min-width: 600px) {

    .pi-feature-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );
    }

}


.pi-feature {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        12px;

    padding:
        14px;

    border:
        1px solid
        rgba(15,23,42,.07);

    border-radius:
        15px;

    background:
        rgba(255,255,255,.58);
}


.pi-feature-icon {

    width:
        34px;

    height:
        34px;

    flex:
        0 0 34px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        10px;

    color:
        var(--pi-primary);

    background:
        #eef2ff;
}


.pi-feature-name {

    color:
        var(--pi-muted);

    font-size:
        10px;

    font-weight:
        800;

    text-transform:
        uppercase;

    letter-spacing:
        .05em;
}


.pi-feature-value {

    margin-top:
        4px;

    color:
        #172033;

    font-size:
        12px;

    line-height:
        1.4;

    font-weight:
        700;

    word-break:
        break-word;
}


/* ============================================================
   GALLERY
============================================================ */

.pi-gallery {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap:
        10px;
}


@media (min-width: 650px) {

    .pi-gallery {

        grid-template-columns:
            repeat(
                3,
                minmax(0,1fr)
            );
    }

}


@media (min-width: 1000px) {

    .pi-gallery {

        grid-template-columns:
            repeat(
                4,
                minmax(0,1fr)
            );
    }

}


.pi-gallery-item {

    position:
        relative;

    aspect-ratio:
        1 / 1;

    overflow:
        hidden;

    border-radius:
        17px;

    border:
        1px solid
        rgba(15,23,42,.08);

    background:
        #f8fafc;
}


.pi-gallery-item img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    transition:
        transform .35s ease;
}


.pi-gallery-item:hover img {

    transform:
        scale(1.06);
}


.pi-gallery-overlay {

    position:
        absolute;

    inset:
        auto 0 0;

    padding:
        25px 10px 10px;

    background:
        linear-gradient(
            transparent,
            rgba(15,23,42,.55)
        );

    color:
        #fff;

    font-size:
        10px;

    opacity:
        0;

    transition:
        .25s ease;
}


.pi-gallery-item:hover
.pi-gallery-overlay {

    opacity:
        1;
}



/* ============================================================
   PRODUCT GALLERY ACTIONS
============================================================ */
.pi-gallery-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}
.pi-gallery-count {
    color: var(--pi-muted);
    font-size: 12px;
    font-weight: 700;
}
.pi-gallery-item { isolation: isolate; }
.pi-gallery-item img { display: block; width: 100%; height: 100%; }
.pi-gallery-delete {
    position: absolute;
    z-index: 5;
    top: 8px;
    right: 8px;
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255,255,255,.72);
    border-radius: 12px;
    color: #fff;
    background: rgba(220,38,38,.94);
    box-shadow: 0 5px 16px rgba(127,29,29,.22);
    cursor: pointer;
    transition: transform .2s ease, background .2s ease;
}
.pi-gallery-delete:hover { transform: translateY(-2px); background: #b91c1c; }
.pi-gallery-delete:focus-visible, .pi-btn:focus-visible {
    outline: 3px solid rgba(79,70,229,.35);
    outline-offset: 3px;
}
.pi-notice {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 14px 16px;
    margin-bottom: 18px;
    border: 1px solid;
    border-radius: 15px;
    font-size: 13px;
    font-weight: 650;
}
.pi-notice.success { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
.pi-notice.error { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
.pi-gallery-add { white-space: nowrap; }

.pi-gallery-item video {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: #0f172a;
}
.pi-gallery-item a { display: block; width: 100%; height: 100%; }
.pi-media-group-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin: 24px 0 12px;
    padding: 13px 15px;
    border: 1px solid rgba(99,91,255,.13);
    border-radius: 14px;
    background: rgba(99,91,255,.045);
}
.pi-media-group-heading h4 {
    margin: 0 0 4px;
    color: #172033;
    font-size: 13px;
    font-weight: 850;
}
.pi-media-group-heading small {
    display: block;
    margin-top: 3px;
    color: var(--pi-muted);
    font-size: 10px;
}
.pi-media-empty { margin-bottom: 8px; padding: 20px 12px; }

@media (max-width: 520px) {
    .pi-gallery-toolbar { align-items: stretch; }
    .pi-gallery-add { width: 100%; }
    .pi-gallery-delete { width: 38px; height: 38px; top: 7px; right: 7px; }
}

/* ============================================================
   EMPTY STATE
============================================================ */

.pi-empty {

    padding:
        30px 15px;

    text-align:
        center;

    color:
        var(--pi-muted);
}


.pi-empty-icon {

    width:
        52px;

    height:
        52px;

    margin:
        0 auto 12px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        16px;

    background:
        #f1f5f9;

    color:
        #94a3b8;

    font-size:
        19px;
}


.pi-empty strong {

    display:
        block;

    color:
        #475569;

    font-size:
        13px;
}


.pi-empty span {

    display:
        block;

    margin-top:
        4px;

    font-size:
        11px;
}


/* ============================================================
   RESPONSIVE ACTIONS
============================================================ */

@media (max-width: 520px) {

    .pi-container {

        padding:
            12px;
    }


    .pi-hero {

        padding:
            17px;

        border-radius:
            20px;
    }


    .pi-identity {

        align-items:
            flex-start;

        gap:
            12px;
    }


    .pi-title {

        font-size:
            20px;
    }


    .pi-actions {

        width:
            100%;
    }


    .pi-actions form {

        flex:
            1 1
            calc(50% - 6px);
    }


    .pi-btn {

        flex:
            1 1
            calc(50% - 6px);

        min-width:
            0;

        padding:
            0 10px;
    }


    .pi-card-body {

        padding:
            15px;
    }


    .pi-card-head {

        padding:
            16px;
    }

}


/* ============================================================
   MICRO INTERACTIONS
============================================================ */

.pi-card,
.pi-kpi,
.pi-hero {

    animation:
        piRise .45s ease both;
}


@keyframes piRise {

    from {

        opacity:
            0;

        transform:
            translateY(8px);
    }

    to {

        opacity:
            1;

        transform:
            translateY(0);
    }

}

</style>

</head>


<body

x-data="{
    page: 'ecommerce',
    loaded: true,
    darkMode: false,
    stickyMenu: false,
    sidebarToggle: false,
    scrollTop: false
}"

x-init="
    darkMode = JSON.parse(localStorage.getItem('darkMode'));
    $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
"

:class="{'dark bg-gray-900': darkMode === true}"
>


<!-- ============================================================
     PAGE WRAPPER
============================================================ -->

<div class="flex h-screen overflow-hidden">


    <?php include('sidebar.php'); ?>


    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <main>


            <?php include('header.php'); ?>


            <div class="pi-container">

                <?php if (!empty($productImageNotice) && is_array($productImageNotice)): ?>
                    <div class="pi-notice <?= ($productImageNotice['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status">
                        <i class="fas <?= ($productImageNotice['type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                        <span><?= htmlspecialchars((string)($productImageNotice['message'] ?? '')) ?></span>
                    </div>
                <?php endif; ?>

                <!-- =================================================
                     BREADCRUMB
                ================================================== -->

                <div class="pi-breadcrumb">

                    <a href="index.php">

                        <i class="fas fa-house"></i>

                        Home

                    </a>


                    <i class="fas fa-chevron-right"></i>


                    <a href="products.php">

                        Products

                    </a>


                    <i class="fas fa-chevron-right"></i>


                    <span>

                        Product Intelligence

                    </span>

                </div>


                <!-- =================================================
                     PRODUCT HERO
                ================================================== -->

                <section class="pi-hero">


                    <div class="pi-identity">


                        <!-- =========================================
                             PRODUCT IMAGE
                        ========================================== -->

                        <div class="pi-product-avatar">


                            <?php if ($productImageUrl): ?>


                                <img
                                    src="<?= htmlspecialchars($productImageUrl) ?>"
                                    alt="<?= htmlspecialchars($single_product['name'] ?? 'Product') ?>"
                                    loading="eager"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >


                                <!--
                                    Hidden fallback icon.
                                    It becomes visible only if the
                                    product image fails to load.
                                -->

                                <i
                                    class="fas fa-box-open"
                                    style="
                                        display:none;
                                        align-items:center;
                                        justify-content:center;
                                    "
                                ></i>


                            <?php else: ?>


                                <i
                                    class="fas fa-box-open"
                                ></i>


                            <?php endif; ?>


                        </div>


                        <!-- =========================================
                             PRODUCT IDENTITY
                        ========================================== -->

                        <div>


                            <div class="pi-eyebrow">

                                <i class="fas fa-microchip"></i>

                                Product Intelligence

                            </div>


                            <h1 class="pi-title">

                                <?= htmlspecialchars(
                                    $single_product['name'] ?? 'Unnamed Product'
                                ) ?>

                            </h1>


                            <div class="pi-category">


                                <i class="fas fa-layer-group"></i>


                                <span>

                                    <?= htmlspecialchars(
                                        $single_product['category_name']
                                        ?? 'No Category'
                                    ) ?>

                                </span>


                                <span>
                                    •
                                </span>


                                <span>

                                    <?= htmlspecialchars(
                                        $single_product['stock_type']
                                        ?? 'Standard Stock'
                                    ) ?>

                                </span>


                                <span>
                                    •
                                </span>


                                <span
                                    class="pi-status <?= htmlspecialchars($stockClass) ?>"
                                >

                                    <i
                                        class="fas <?= htmlspecialchars($stockIcon) ?>"
                                    ></i>

                                    <?= htmlspecialchars($stockStatus) ?>

                                </span>


                            </div>


                        </div>


                    </div>


                    <!-- =================================================
                         ACTIONS
                    ================================================== -->

                    <div class="pi-actions">


                        <!-- EDIT PRODUCT -->

                        <form
                            action="edit_product.php"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= htmlspecialchars(
                                    $single_product['id']
                                ) ?>"
                            >


                            <button
                                type="submit"
                                class="pi-btn pi-btn-primary"
                            >

                                <i
                                    class="fas fa-pen-to-square"
                                ></i>

                                Edit Product

                            </button>

                        </form>


                        <!-- CATALOGUE -->

                        <?php if (file_exists($cataloguePath)): ?>


                            <a
                                href="<?= htmlspecialchars(
                                    $cataloguePath
                                ) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="pi-btn"
                            >

                                <i
                                    class="fas fa-file-pdf"
                                ></i>

                                Catalogue

                            </a>


                        <?php else: ?>


                            <button
                                type="button"
                                class="pi-btn"
                                disabled
                                style="
                                    opacity:.55;
                                    cursor:not-allowed;
                                "
                            >

                                <i
                                    class="fas fa-file-circle-xmark"
                                ></i>

                                No Catalogue

                            </button>


                        <?php endif; ?>


                    </div>


                </section>


                <!-- =================================================
                     INVENTORY INTELLIGENCE
                ================================================== -->

                <section class="pi-section">


                    <div class="pi-section-heading">


                        <h2 class="pi-section-title">


                            <span
                                class="pi-section-title-icon"
                            >

                                <i
                                    class="fas fa-chart-line"
                                ></i>

                            </span>


                            Inventory Intelligence


                        </h2>


                    </div>


                    <div class="pi-kpis">


                        <!-- TOTAL STOCK -->

                        <div class="pi-kpi">


                            <div class="pi-kpi-top">


                                <div class="pi-kpi-icon">

                                    <i
                                        class="fas fa-cubes-stacked"
                                    ></i>

                                </div>


                                <span
                                    class="pi-status <?= htmlspecialchars($stockClass) ?>"
                                >

                                    <?= htmlspecialchars($stockStatus) ?>

                                </span>


                            </div>


                            <div class="pi-kpi-label">

                                Total Stock

                            </div>


                            <div class="pi-kpi-value">

                                <?= number_format(
                                    $total_stock
                                ) ?>

                            </div>


                            <div class="pi-kpi-sub">

                                Current inventory quantity

                            </div>


                        </div>


                        <!-- ASSIGNED -->

                        <div class="pi-kpi">


                            <div class="pi-kpi-top">


                                <div class="pi-kpi-icon">

                                    <i
                                        class="fas fa-location-dot"
                                    ></i>

                                </div>


                            </div>


                            <div class="pi-kpi-label">

                                Assigned Stock

                            </div>


                            <div class="pi-kpi-value">

                                <?= number_format(
                                    $assigned_stock
                                ) ?>

                            </div>


                            <div class="pi-kpi-sub">

                                Inventory already assigned

                            </div>


                        </div>


                        <!-- AVAILABLE -->

                        <div class="pi-kpi">


                            <div class="pi-kpi-top">


                                <div class="pi-kpi-icon">

                                    <i
                                        class="fas fa-box"
                                    ></i>

                                </div>


                            </div>


                            <div class="pi-kpi-label">

                                Available Stock

                            </div>


                            <div class="pi-kpi-value">

                                <?= number_format(
                                    $unassigned_stock
                                ) ?>

                            </div>


                            <div class="pi-kpi-sub">

                                Unassigned inventory

                            </div>


                        </div>


                        <!-- VALUE -->

                        <div class="pi-kpi">


                            <div class="pi-kpi-top">


                                <div class="pi-kpi-icon">

                                    <i
                                        class="fas fa-naira-sign"
                                    ></i>

                                </div>


                            </div>


                            <div class="pi-kpi-label">

                                Stock Value

                            </div>


                            <div class="pi-kpi-value">

                                ₦<?= number_format(
                                    $total_stock_value,
                                    2
                                ) ?>

                            </div>


                            <div class="pi-kpi-sub">

                                Based on selling price

                            </div>


                        </div>


                    </div>


                </section>


                <!-- =================================================
                     MAIN INFORMATION LAYOUT
                ================================================== -->

                <div class="pi-layout">


                    <div>


                        <!-- =========================================
                             PRODUCT INFORMATION
                        ========================================== -->

                        <section
                            class="pi-card pi-section"
                        >


                            <div
                                class="pi-card-head"
                            >

                                <div>

                                    <h3>
                                        Product Information
                                    </h3>

                                    <p>
                                        Core identity and classification data
                                    </p>

                                </div>


                                <div
                                    class="pi-section-title-icon"
                                >

                                    <i
                                        class="fas fa-fingerprint"
                                    ></i>

                                </div>


                            </div>


                            <div
                                class="pi-card-body"
                            >


                                <div
                                    class="pi-info-grid"
                                >


                                    <!-- BARCODE -->

                                    <div
                                        class="pi-info-item"
                                    >

                                        <div
                                            class="pi-info-label"
                                        >

                                            <i
                                                class="fas fa-barcode"
                                            ></i>

                                            Barcode

                                        </div>


                                        <div
                                            class="pi-info-value"
                                        >

                                            <?= htmlspecialchars(
                                                $single_product['barcode']
                                                ?? 'Not assigned'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- NAME -->

                                    <div
                                        class="pi-info-item"
                                    >

                                        <div
                                            class="pi-info-label"
                                        >

                                            <i
                                                class="fas fa-box"
                                            ></i>

                                            Product Name

                                        </div>


                                        <div
                                            class="pi-info-value"
                                        >

                                            <?= htmlspecialchars(
                                                $single_product['name']
                                                ?? 'N/A'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- CATEGORY -->

                                    <div
                                        class="pi-info-item"
                                    >

                                        <div
                                            class="pi-info-label"
                                        >

                                            <i
                                                class="fas fa-layer-group"
                                            ></i>

                                            Category

                                        </div>


                                        <div
                                            class="pi-info-value"
                                        >

                                            <?= htmlspecialchars(
                                                $single_product['category_name']
                                                ?? 'No Category'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- STOCK PURPOSE -->

                                    <div
                                        class="pi-info-item"
                                    >

                                        <div
                                            class="pi-info-label"
                                        >

                                            <i
                                                class="fas fa-bullseye"
                                            ></i>

                                            Stock Purpose

                                        </div>


                                        <div
                                            class="pi-info-value"
                                        >

                                            <?= htmlspecialchars(
                                                $single_product['stock_type']
                                                ?? 'N/A'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- COMPANY -->

                                    <div
                                        class="pi-info-item"
                                    >

                                        <div
                                            class="pi-info-label"
                                        >

                                            <i
                                                class="fas fa-building"
                                            ></i>

                                            Company

                                        </div>


                                        <div
                                            class="pi-info-value"
                                        >

                                            <?= htmlspecialchars(
                                                $single_product['company_name']
                                                ?? 'N/A'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- CREATED BY -->

                                    <div
                                        class="pi-info-item"
                                    >

                                        <div
                                            class="pi-info-label"
                                        >

                                            <i
                                                class="fas fa-user-shield"
                                            ></i>

                                            Created By

                                        </div>


                                        <div
                                            class="pi-info-value"
                                        >

                                            <?= htmlspecialchars(
                                                $single_product['created_by_email']
                                                ?? 'N/A'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- CREATED AT -->

                                    <div
                                        class="pi-info-item"
                                    >

                                        <div
                                            class="pi-info-label"
                                        >

                                            <i
                                                class="fas fa-calendar-plus"
                                            ></i>

                                            Created At

                                        </div>


                                        <div
                                            class="pi-info-value"
                                        >

                                            <?= htmlspecialchars(
                                                $single_product['created_at']
                                                ?? 'N/A'
                                            ) ?>

                                        </div>

                                    </div>


                                </div>


                            </div>


                        </section>


                        <!-- =========================================
                             FEATURES
                        ========================================== -->

                        <section
                            class="pi-card pi-section"
                        >


                            <div
                                class="pi-card-head"
                            >

                                <div>

                                    <h3>
                                        Product Features
                                    </h3>

                                    <p>
                                        Configured product characteristics
                                    </p>

                                </div>


                                <div
                                    class="pi-section-title-icon"
                                >

                                    <i
                                        class="fas fa-sliders"
                                    ></i>

                                </div>


                            </div>


                            <div
                                class="pi-card-body"
                            >


                                <?php if (count($features) > 0): ?>


                                    <div
                                        class="pi-feature-grid"
                                    >


                                        <?php foreach (
                                            $features
                                            as $feature
                                        ): ?>


                                            <div
                                                class="pi-feature"
                                            >


                                                <div
                                                    class="pi-feature-icon"
                                                >

                                                    <i
                                                        class="fas fa-sparkles"
                                                    ></i>

                                                </div>


                                                <div>


                                                    <div
                                                        class="pi-feature-name"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $feature['feature_name']
                                                        ) ?>

                                                    </div>


                                                    <div
                                                        class="pi-feature-value"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $feature['feature_value']
                                                        ) ?>

                                                    </div>


                                                </div>


                                            </div>


                                        <?php endforeach; ?>


                                    </div>


                                <?php else: ?>


                                    <div
                                        class="pi-empty"
                                    >

                                        <div
                                            class="pi-empty-icon"
                                        >

                                            <i
                                                class="fas fa-sliders"
                                            ></i>

                                        </div>


                                        <strong>
                                            No product features yet
                                        </strong>


                                        <span>
                                            Product characteristics will appear here when configured.
                                        </span>


                                    </div>


                                <?php endif; ?>


                            </div>


                        </section>


                        <!-- =========================================
                             GALLERY
                        ========================================== -->

                        <section class="pi-card pi-section">
    <div class="pi-card-head">
        <div>
            <h3>Product Media Library</h3>
            <p>Shared product media and media assigned to each variant</p>
        </div>
        <div class="pi-section-title-icon"><i class="fas fa-images"></i></div>
    </div>

    <div class="pi-card-body">
        <?php
        $galleryGroups = [[
            'variant_id' => null,
            'label' => 'Parent product · Shared media',
            'items' => array_values(array_filter($images, static fn($item) => empty($item['variant_id'])))
        ]];
        foreach ($variants as $galleryVariant) {
            $galleryGroups[] = [
                'variant_id' => (int)$galleryVariant['id'],
                'label' => (string)($galleryVariant['variant_name'] ?: 'Variant #' . $galleryVariant['id']),
                'sku' => (string)($galleryVariant['sku'] ?? ''),
                'items' => array_values(array_filter($images, static fn($item) => (int)($item['variant_id'] ?? 0) === (int)$galleryVariant['id']))
            ];
        }
        ?>

        <div class="pi-gallery-toolbar">
            <span class="pi-gallery-count">
                <i class="fas fa-layer-group"></i>
                <?= (int)$imageCount ?> image<?= $imageCount === 1 ? '' : 's' ?> · <?= (int)$videoCount ?> video<?= $videoCount === 1 ? '' : 's' ?> across all media groups
            </span>
            <a href="product_image.php?id=<?= (int)$product_id ?>" class="pi-btn pi-btn-primary pi-gallery-add">
                <i class="fas fa-plus"></i> Add Shared Media
            </a>
        </div>

        <?php foreach ($galleryGroups as $galleryGroup): ?>
            <div class="pi-media-group-heading">
                <div>
                    <h4><?= htmlspecialchars($galleryGroup['label'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <?php if (!empty($galleryGroup['sku'])): ?><small>SKU: <?= htmlspecialchars($galleryGroup['sku'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    <small><?= count($galleryGroup['items']) ?> file<?= count($galleryGroup['items']) === 1 ? '' : 's' ?> attached</small>
                </div>
                <a href="product_image.php?id=<?= (int)$product_id ?><?= $galleryGroup['variant_id'] !== null ? '&amp;variant_id=' . (int)$galleryGroup['variant_id'] : '' ?>" class="pi-btn pi-gallery-add">
                    <i class="fas fa-plus"></i> Add <?= $galleryGroup['variant_id'] !== null ? 'Variant' : 'Product' ?> Media
                </a>
            </div>

            <?php if (!empty($galleryGroup['items'])): ?>
                <div class="pi-gallery">
                    <?php foreach ($galleryGroup['items'] as $index => $img):
                        $mediaType = (string)($img['media_type'] ?? 'image');
                        $mediaPath = (string)($img['media_path'] ?? $img['image_path'] ?? '');
                        $thumbnailPath = (string)($img['thumbnail_path'] ?? '');
                        $mediaUrl = $resolveProductMediaUrl($mediaPath);
                        $thumbnailUrl = $resolveProductMediaUrl($thumbnailPath !== '' ? $thumbnailPath : $mediaPath);
                        $mediaLabel = trim((string)($img['media_name'] ?? ''));
                        if ($mediaLabel === '' || $mediaLabel === $mediaPath) $mediaLabel = ucfirst($mediaType) . ' ' . ($index + 1);
                        $isMediaLibraryRow = !empty($img['media_id']);
                    ?>
                        <div class="pi-gallery-item">
                            <?php if ($mediaType === 'video'): ?>
                                <video controls preload="metadata" playsinline aria-label="<?= htmlspecialchars($mediaLabel, ENT_QUOTES, 'UTF-8') ?>">
                                    <source src="<?= htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8') ?>">
                                    Your browser does not support video playback.
                                </video>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <img src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($mediaLabel, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                </a>
                            <?php endif; ?>

                            <form method="POST" action="?id=<?= (int)$product_id ?>" onsubmit="return confirm('Delete this product media? This action cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="action" value="<?= $isMediaLibraryRow ? 'delete_product_media' : 'delete_product_image' ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
                                <?php if ($isMediaLibraryRow): ?>
                                    <input type="hidden" name="media_id" value="<?= (int)$img['media_id'] ?>">
                                <?php else: ?>
                                    <input type="hidden" name="image_id" value="<?= (int)($img['legacy_id'] ?? 0) ?>">
                                <?php endif; ?>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['product_image_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="pi-gallery-delete" aria-label="Delete <?= htmlspecialchars($mediaLabel, ENT_QUOTES, 'UTF-8') ?>" title="Delete this media">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </form>

                            <div class="pi-gallery-overlay">
                                <i class="fas <?= $mediaType === 'video' ? 'fa-video' : 'fa-image' ?>"></i>
                                <?= htmlspecialchars($mediaLabel, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="pi-empty pi-media-empty">
                    <div class="pi-empty-icon"><i class="fas fa-images"></i></div>
                    <strong>No media for this group yet</strong>
                    <span>Upload images or videos to keep this gallery up to date.</span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>


                    </div>


                    <!-- =================================================
                         RIGHT INTELLIGENCE PANEL
                    ================================================== -->

                    <aside>


                        <!-- =========================================
                             PRICING INTELLIGENCE
                        ========================================== -->

                        <section
                            class="pi-card pi-section"
                        >


                            <div
                                class="pi-card-head"
                            >

                                <div>

                                    <h3>
                                        Pricing Intelligence
                                    </h3>

                                    <p>
                                        Current product economics
                                    </p>

                                </div>


                                <div
                                    class="pi-section-title-icon"
                                >

                                    <i
                                        class="fas fa-chart-pie"
                                    ></i>

                                </div>


                            </div>


                            <div
                                class="pi-card-body"
                            >


                                <div
                                    class="pi-price-stack"
                                >


                                    <!-- COST -->

                                    <div
                                        class="pi-price-row"
                                    >

                                        <div
                                            class="pi-price-row-top"
                                        >

                                            <span
                                                class="pi-price-label"
                                            >
                                                Cost Price
                                            </span>


                                            <i
                                                class="fas fa-arrow-down"
                                                style="color:#64748b;"
                                            ></i>

                                        </div>


                                        <div
                                            class="pi-price-value"
                                        >

                                            ₦<?= number_format(
                                                $cost_price,
                                                2
                                            ) ?>

                                        </div>


                                        <div
                                            class="pi-price-meta"
                                        >

                                            Acquisition / production cost

                                        </div>


                                    </div>


                                    <!-- SELLING -->

                                    <div
                                        class="pi-price-row"
                                    >

                                        <div
                                            class="pi-price-row-top"
                                        >

                                            <span
                                                class="pi-price-label"
                                            >
                                                Selling Price
                                            </span>


                                            <i
                                                class="fas fa-arrow-up"
                                                style="color:#059669;"
                                            ></i>

                                        </div>


                                        <div
                                            class="pi-price-value"
                                        >

                                            ₦<?= number_format(
                                                $selling_price,
                                                2
                                            ) ?>

                                        </div>


                                        <div
                                            class="pi-price-meta"
                                        >

                                            Current unit selling price

                                        </div>


                                    </div>


                                    <!-- PROFIT -->

                                    <div
                                        class="pi-price-row pi-profit"
                                    >

                                        <div
                                            class="pi-price-row-top"
                                        >

                                            <span
                                                class="pi-price-label"
                                            >
                                                Unit Profit
                                            </span>


                                            <i
                                                class="fas fa-coins"
                                                style="color:#059669;"
                                            ></i>

                                        </div>


                                        <div
                                            class="pi-price-value"
                                        >

                                            ₦<?= number_format(
                                                $unitProfit,
                                                2
                                            ) ?>

                                        </div>


                                        <div
                                            class="pi-price-meta"
                                        >

                                            <?= number_format(
                                                $profitMargin,
                                                1
                                            ) ?>%
                                            estimated margin

                                        </div>


                                    </div>


                                    <!-- TOTAL VALUE -->

                                    <div
                                        class="pi-price-row"
                                    >

                                        <div
                                            class="pi-price-row-top"
                                        >

                                            <span
                                                class="pi-price-label"
                                            >
                                                Total Stock Value
                                            </span>


                                            <i
                                                class="fas fa-vault"
                                                style="color:#4f46e5;"
                                            ></i>

                                        </div>


                                        <div
                                            class="pi-price-value"
                                        >

                                            ₦<?= number_format(
                                                $total_stock_value,
                                                2
                                            ) ?>

                                        </div>


                                        <div
                                            class="pi-price-meta"
                                        >

                                            Inventory × selling price

                                        </div>


                                    </div>


                                </div>


                            </div>


                        </section>


                        <!-- =========================================
                             STOCK DISTRIBUTION
                        ========================================== -->

                        <section
                            class="pi-card pi-section"
                        >


                            <div
                                class="pi-card-head"
                            >

                                <div>

                                    <h3>
                                        Stock Distribution
                                    </h3>

                                    <p>
                                        Assigned vs available inventory
                                    </p>

                                </div>


                                <div
                                    class="pi-section-title-icon"
                                >

                                    <i
                                        class="fas fa-chart-simple"
                                    ></i>

                                </div>


                            </div>


                            <div
                                class="pi-card-body"
                            >


                                <div
                                    class="pi-price-row"
                                >

                                    <div
                                        class="pi-price-row-top"
                                    >

                                        <span
                                            class="pi-price-label"
                                        >
                                            Assigned
                                        </span>


                                        <strong
                                            style="color:#4f46e5;"
                                        >

                                            <?= number_format(
                                                $assigned_stock
                                            ) ?>

                                        </strong>

                                    </div>

                                </div>


                                <div
                                    class="pi-stock-box"
                                >


                                    <div
                                        class="pi-stock-bar"
                                    >

                                        <div
                                            class="pi-stock-fill"
                                        ></div>

                                    </div>


                                    <div
                                        class="pi-stock-legend"
                                    >

                                        <span>

                                            Assigned:

                                            <strong>

                                                <?= number_format(
                                                    $assigned_stock
                                                ) ?>

                                            </strong>

                                        </span>


                                        <span>

                                            Available:

                                            <strong>

                                                <?= number_format(
                                                    $unassigned_stock
                                                ) ?>

                                            </strong>

                                        </span>


                                    </div>


                                </div>


                            </div>


                        </section>


                        <!-- =========================================
                             SYSTEM METADATA
                        ========================================== -->

                        <section
                            class="pi-card pi-section"
                        >


                            <div
                                class="pi-card-head"
                            >

                                <div>

                                    <h3>
                                        System Metadata
                                    </h3>

                                    <p>
                                        Product identification
                                    </p>

                                </div>


                                <div
                                    class="pi-section-title-icon"
                                >

                                    <i
                                        class="fas fa-database"
                                    ></i>

                                </div>


                            </div>


                            <div
                                class="pi-card-body"
                            >


                                <!-- PRODUCT ID -->

                                <div
                                    class="pi-info-item"
                                >

                                    <div
                                        class="pi-info-label"
                                    >

                                        <i
                                            class="fas fa-hashtag"
                                        ></i>

                                        Product ID

                                    </div>


                                    <div
                                        class="pi-info-value"
                                    >

                                        #<?= htmlspecialchars(
                                            $single_product['id']
                                        ) ?>

                                    </div>

                                </div>


                                <!-- SLUG -->

                                <div
                                    class="pi-info-item"
                                >

                                    <div
                                        class="pi-info-label"
                                    >

                                        <i
                                            class="fas fa-link"
                                        ></i>

                                        Product Slug

                                    </div>


                                    <div
                                        class="pi-info-value"
                                    >

                                        <?= htmlspecialchars(
                                            $single_product['slug']
                                            ?? 'N/A'
                                        ) ?>

                                    </div>

                                </div>


                                <!-- COMPANY ID -->

                                <div
                                    class="pi-info-item"
                                >

                                    <div
                                        class="pi-info-label"
                                    >

                                        <i
                                            class="fas fa-building"
                                        ></i>

                                        Company ID

                                    </div>


                                    <div
                                        class="pi-info-value"
                                    >

                                        <?= htmlspecialchars(
                                            $company_id
                                        ) ?>

                                    </div>

                                </div>


                            </div>


                        </section>


                    </aside>


                </div>


            </div>


        </main>


    </div>


</div>


<?php include('footer.php'); ?>


<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js">
</script>


<script
    defer
    src="bundle.js">
</script>


<script
    defer
    src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015"
    integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ=="
    data-cf-beacon='{"rayId":"95aebaa31b75b6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
    crossorigin="anonymous">
</script>


</body>

</html>