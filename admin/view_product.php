<?php
// Branch Manager Product Intelligence — branch-scoped hybrid legacy/variant inventory view.
// Existing sidebar.php, header.php, footer.php, alpine.js and bundle.js are intentionally untouched.

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$company_id = $_SESSION['company_id'] ?? null;
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$branchLocationId = (int)($_SESSION['location_id'] ?? ($_SESSION['branch_location_id'] ?? ($location_id ?? ($user_location_id ?? 0))));
if (!$company_id || $product_id < 1 || $branchLocationId < 1) {
    http_response_code(400);
    exit('Unauthorized access, invalid product ID, or branch location is missing from the manager session.');
}

// Load and authorize the parent product before processing any product-scoped action.
$productStmt = $pdo->prepare("SELECT p.*, pc.name AS category_name, pc.stock_type AS category_stock_type,
    co.company_name AS company_name, u.email AS created_by_email
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    LEFT JOIN companies co ON p.company_id = co.id
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.company_id = ? AND p.id = ? LIMIT 1");
$productStmt->execute([$company_id, $product_id]);
$single_product = $productStmt->fetch(PDO::FETCH_ASSOC);
if (!$single_product) {
    http_response_code(404);
    exit('Product not found.');
}

$hasVariants = !empty($single_product['has_variants']);

// Branch managers have a read-only view of company-owned product media.
// Product media upload/deletion remains under company-administrator workflows.

$e = static function ($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};
$money = static function ($value): string {
    return '₦' . number_format((float)$value, 2);
};

// Variant records and their attribute-value labels.
$variants = [];
$variantIds = [];
if ($hasVariants) {
    $variantStmt = $pdo->prepare("SELECT pv.id, pv.sku, pv.barcode, pv.variant_name, pv.cost_price,
        pv.selling_price, pv.tracking_policy, pv.is_default, pv.is_active,
        COALESCE(SUM(pvs.quantity), 0) AS total_stock
        FROM product_variants pv
        LEFT JOIN product_variant_stocks pvs ON pvs.variant_id = pv.id
            AND pvs.company_id = pv.company_id AND pvs.product_id = pv.product_id
            AND pvs.location_id = ?
        WHERE pv.company_id = ? AND pv.product_id = ?
        GROUP BY pv.id, pv.sku, pv.barcode, pv.variant_name, pv.cost_price, pv.selling_price,
            pv.tracking_policy, pv.is_default, pv.is_active
        ORDER BY pv.is_default DESC, pv.id ASC");
    $variantStmt->execute([$branchLocationId, $company_id, $product_id]);
    $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($variants as &$variant) {
        $variant['attributes'] = [];
        $variant['total_stock'] = (float)$variant['total_stock'];
        $variantIds[] = (int)$variant['id'];
    }
    unset($variant);
    if ($variantIds) {
        $valueStmt = $pdo->prepare("SELECT pvv.variant_id, pa.attribute_name, pav.value_name
            FROM product_variant_values pvv
            INNER JOIN product_attributes pa ON pa.id = pvv.attribute_id
            INNER JOIN product_attribute_values pav ON pav.id = pvv.attribute_value_id
            WHERE pa.company_id = ? AND pa.product_id = ?
            ORDER BY pa.display_order ASC, pav.display_order ASC, pa.id ASC");
        $valueStmt->execute([$company_id, $product_id]);
        $variantIndex = [];
        foreach ($variants as $index => $variant) $variantIndex[(int)$variant['id']] = $index;
        foreach ($valueStmt->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $id = (int)$value['variant_id'];
            if (isset($variantIndex[$id])) {
                $variants[$variantIndex[$id]]['attributes'][] = $value['attribute_name'] . ': ' . $value['value_name'];
            }
        }
    }
}

// Legacy stock remains authoritative for non-variant products. Variant products
// use the dedicated per-variant/per-location stock ledger.
if ($hasVariants) {
    $total_stock = array_sum(array_column($variants, 'total_stock'));
    $assigned_stock = 0.0; // Variant stock is location-based; the legacy staff-assignment ledger is not variant-aware.
    $unassigned_stock = $total_stock;
    $stockAllocationLabel = 'This branch location';
} else {
    $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE product_id = ? AND company_id = ? AND location_id = ?");
    $stockStmt->execute([$product_id, $company_id, $branchLocationId]);
    $total_stock = (float)$stockStmt->fetchColumn();
    $assignedStmt = $pdo->prepare("SELECT COALESCE(SUM(sa.quantity), 0) FROM stocks_assigned sa
        INNER JOIN stocks s ON s.id = sa.stock_id AND s.company_id = sa.company_id
        WHERE s.product_id = ? AND s.company_id = ? AND s.location_id = ? AND sa.location_id = ?");
    $assignedStmt->execute([$product_id, $company_id, $branchLocationId, $branchLocationId]);
    $assigned_stock = (float)$assignedStmt->fetchColumn();
    $unassigned_stock = max(0, $total_stock - $assigned_stock);
    $stockAllocationLabel = 'Branch assigned stock';
}

$parentCost = (float)($single_product['cost_price'] ?? 0);
$parentSelling = (float)($single_product['selling_price'] ?? 0);
$cost_price = $parentCost;
$selling_price = $parentSelling;
$total_stock_value = $total_stock * $selling_price;
$variantPriceValues = [];
$variantCostValues = [];
if ($hasVariants && $variants) {
    $total_stock_value = 0.0;
    foreach ($variants as $variant) {
        $vcost = $variant['cost_price'] !== null ? (float)$variant['cost_price'] : $parentCost;
        $vsell = $variant['selling_price'] !== null ? (float)$variant['selling_price'] : $parentSelling;
        $variantPriceValues[] = $vsell;
        $variantCostValues[] = $vcost;
        $total_stock_value += (float)$variant['total_stock'] * $vsell;
    }
    if ($variantPriceValues) {
        $selling_price = min($variantPriceValues);
        $cost_price = min($variantCostValues);
    }
}
$assigned_value = $assigned_stock * $selling_price;
$unassigned_value = $unassigned_stock * $selling_price;
$unitProfit = $selling_price - $cost_price;
$profitMargin = $selling_price > 0 ? ($unitProfit / $selling_price) * 100 : 0;
$assignedPercentage = $total_stock > 0 ? min(100, max(0, ($assigned_stock / $total_stock) * 100)) : 0;

$featureStmt = $pdo->prepare("SELECT feature_name, feature_value FROM product_features WHERE product_id = ?");
$featureStmt->execute([$product_id]);
$features = $featureStmt->fetchAll(PDO::FETCH_ASSOC);

// Unified media includes parent-level and variant-level assets. Legacy images
// without a product_media link are appended for backward compatibility.
$mediaStmt = $pdo->prepare("SELECT id, variant_id, media_type, media_path, thumbnail_path, media_name,
    mime_type, display_order, is_primary, legacy_image_id
    FROM product_media WHERE product_id = ? AND company_id = ?
    ORDER BY (variant_id IS NULL) DESC, is_primary DESC, display_order ASC, id ASC");
$mediaStmt->execute([$product_id, $company_id]);
$mediaRows = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
$legacyStmt = $pdo->prepare("SELECT pi.id, pi.image_path FROM product_images pi
    WHERE pi.product_id = ? AND NOT EXISTS (
        SELECT 1 FROM product_media pm WHERE pm.legacy_image_id = pi.id
        AND pm.product_id = ? AND pm.company_id = ?)
    ORDER BY pi.id ASC");
$legacyStmt->execute([$product_id, $product_id, $company_id]);
foreach ($legacyStmt->fetchAll(PDO::FETCH_ASSOC) as $legacyImage) {
    $mediaRows[] = ['id' => null, 'legacy_id' => (int)$legacyImage['id'], 'variant_id' => null,
        'media_type' => 'image', 'media_path' => $legacyImage['image_path'], 'thumbnail_path' => null,
        'media_name' => 'Product image', 'mime_type' => null, 'display_order' => 999999,
        'is_primary' => 0, 'legacy_image_id' => (int)$legacyImage['id']];
}
$images = $mediaRows;
$resolveProductMediaUrl = static function ($path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    $clean = str_replace('\\', '/', $path);
    if (strpos($clean, '../') === 0) return $clean;
    $clean = ltrim($clean, '/');
    if (strpos($clean, 'uploads/products/') === 0 || strpos($clean, 'uploads/') === 0) return '../' . $clean;
    if (strpos($clean, 'products/') === 0) return '../uploads/' . $clean;
    return '../uploads/products/' . $clean;
};
$featuredImage = null;
foreach ($images as $mediaItem) {
    if (($mediaItem['media_type'] ?? 'image') === 'image' && empty($mediaItem['variant_id'])) {
        $featuredImage = $mediaItem['thumbnail_path'] ?: $mediaItem['media_path'];
        break;
    }
}
if (!$featuredImage) {
    foreach ($images as $mediaItem) {
        if (($mediaItem['media_type'] ?? 'image') === 'image') {
            $featuredImage = $mediaItem['thumbnail_path'] ?: $mediaItem['media_path'];
            break;
        }
    }
}
$productImageUrl = $featuredImage ? $resolveProductMediaUrl($featuredImage) : null;

// Effective identifier policy: variant override unless it inherits the parent.
$parentTracking = strtolower((string)($single_product['identifier_tracking'] ?? 'none'));
if (!in_array($parentTracking, ['none', 'imei', 'serial', 'vin'], true)) $parentTracking = 'none';
$effectiveTrackingByVariant = [];
foreach ($variants as $variant) {
    $policy = strtolower((string)($variant['tracking_policy'] ?? 'inherit'));
    if ($policy === 'inherit') $policy = $parentTracking;
    if (!in_array($policy, ['none', 'imei', 'serial', 'vin'], true)) $policy = 'none';
    $effectiveTrackingByVariant[(int)$variant['id']] = $policy;
}
$trackingEnabled = $hasVariants ? (bool)array_filter($effectiveTrackingByVariant, static fn($p) => $p !== 'none') : ($parentTracking !== 'none');
$trackedUnits = [];
$trackedCounts = [];
if ($trackingEnabled) {
    $serialStmt = $pdo->prepare("SELECT ps.id, ps.variant_id, ps.identifier_type, ps.identifier_value, ps.status,
        ps.stock_id, ps.location_id, ps.received_at, ps.updated_at, pv.variant_name, pv.sku AS variant_sku
        FROM product_serials ps LEFT JOIN product_variants pv ON pv.id = ps.variant_id
        WHERE ps.product_id = ? AND ps.company_id = ? AND ps.location_id = ? ORDER BY ps.id DESC");
    $serialStmt->execute([$product_id, $company_id, $branchLocationId]);
    $allTracked = $serialStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allTracked as $unit) {
        $type = strtolower((string)$unit['identifier_type']);
        $variantId = (int)($unit['variant_id'] ?? 0);
        $allowed = $hasVariants ? (($variantId > 0 && ($effectiveTrackingByVariant[$variantId] ?? 'none') === $type) || ($variantId === 0 && $parentTracking === $type)) : ($parentTracking === $type);
        if (!$allowed) continue;
        $trackedUnits[] = $unit;
        $state = (string)$unit['status'];
        $trackedCounts[$state] = ($trackedCounts[$state] ?? 0) + 1;
    }
}

if ($total_stock <= 0) {
    $stockStatus = 'Out of Stock'; $stockClass = 'danger'; $stockIcon = 'fa-box-open';
} elseif ($total_stock <= 10) {
    $stockStatus = 'Low Stock'; $stockClass = 'warning'; $stockIcon = 'fa-triangle-exclamation';
} else {
    $stockStatus = 'In Stock'; $stockClass = 'success'; $stockIcon = 'fa-circle-check';
}
$cataloguePath = '../uploads/products/catalogues/' . ($single_product['slug'] ?? '') . '.pdf';
$imageCount = count(array_filter($images, static fn($m) => ($m['media_type'] ?? 'image') === 'image'));
$videoCount = count(array_filter($images, static fn($m) => ($m['media_type'] ?? 'image') === 'video'));
$variantStockStatus = static function ($qty): array {
    if ((float)$qty <= 0) return ['Out of stock', 'danger'];
    if ((float)$qty <= 10) return ['Low stock', 'warning'];
    return ['In stock', 'success'];
};
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= $e($company_name ?? $single_product['company_name'] ?? 'Company') ?> | Branch Product Intelligence</title>
<link rel="icon" href="src/images/icons/favicon.png">
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<link href="modal.css" rel="stylesheet">
<style>
:root{--pi-bg:#f4f7fb;--pi-surface:rgba(255,255,255,.86);--pi-border:rgba(15,23,42,.09);--pi-text:#142033;--pi-muted:#6b778c;--pi-primary:#4f46e5;--pi-green:#059669;--pi-orange:#d97706;--pi-red:#dc2626;--pi-shadow:0 20px 55px rgba(15,23,42,.08);--pi-soft:0 10px 30px rgba(15,23,42,.055)}
*{box-sizing:border-box}body{background:radial-gradient(circle at 8% 5%,rgba(79,70,229,.08),transparent 26%),radial-gradient(circle at 92% 10%,rgba(8,145,178,.07),transparent 25%),linear-gradient(135deg,#f8fafc,#f1f5f9);color:var(--pi-text)}
.pi-container{width:min(1500px,100%);margin:auto;padding:18px}@media(min-width:768px){.pi-container{padding:28px}}@media(min-width:1280px){.pi-container{padding:32px}}
.pi-breadcrumb{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:22px;color:var(--pi-muted);font-size:13px}.pi-breadcrumb a{color:inherit;text-decoration:none}.pi-breadcrumb a:hover{color:var(--pi-primary)}.pi-breadcrumb i{font-size:9px;opacity:.55}
.pi-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr);gap:24px;padding:24px;margin-bottom:22px;border:1px solid var(--pi-border);border-radius:26px;background:var(--pi-surface);backdrop-filter:blur(20px);box-shadow:var(--pi-shadow)}.pi-hero:before{content:"";position:absolute;width:280px;height:280px;right:-110px;top:-150px;border-radius:50%;background:rgba(79,70,229,.11);pointer-events:none}@media(min-width:900px){.pi-hero{grid-template-columns:minmax(0,1fr) auto;align-items:center;padding:30px}}
.pi-identity{position:relative;z-index:2;display:flex;align-items:center;gap:18px;min-width:0}.pi-product-avatar{width:96px;height:96px;flex:0 0 96px;border-radius:24px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#eef2ff,#fff);border:1px solid rgba(79,70,229,.14);box-shadow:0 12px 30px rgba(79,70,229,.13);color:var(--pi-primary);font-size:30px}.pi-product-avatar img{width:100%;height:100%;object-fit:cover}.pi-eyebrow{display:flex;align-items:center;gap:8px;color:var(--pi-primary);font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:5px}.pi-title{margin:0;font-size:clamp(21px,3vw,31px);line-height:1.15;font-weight:800;letter-spacing:-.025em;color:#111827;overflow-wrap:anywhere}.pi-category{margin-top:8px;display:flex;align-items:center;flex-wrap:wrap;gap:7px;color:var(--pi-muted);font-size:13px}
.pi-status{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border-radius:999px;font-size:11px;font-weight:800}.pi-status.success{color:#047857;background:#ecfdf5;border:1px solid #a7f3d0}.pi-status.warning{color:#b45309;background:#fffbeb;border:1px solid #fde68a}.pi-status.danger{color:#b91c1c;background:#fef2f2;border:1px solid #fecaca}
.pi-actions{position:relative;z-index:3;display:flex;gap:9px;flex-wrap:wrap}.pi-btn{min-height:43px;padding:0 15px;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:13px;border:1px solid var(--pi-border);background:#fff;color:#334155;font-size:12px;font-weight:750;text-decoration:none;cursor:pointer;box-shadow:0 7px 18px rgba(15,23,42,.05)}.pi-btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(15,23,42,.09)}.pi-btn-primary{color:#fff;border-color:transparent;background:linear-gradient(135deg,#4f46e5,#6366f1);box-shadow:0 10px 24px rgba(79,70,229,.22)}.pi-btn-primary:hover{color:#fff}
.pi-section{margin-bottom:22px}.pi-section-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}.pi-section-title{display:flex;align-items:center;gap:10px;margin:0;color:#182235;font-size:16px;font-weight:800}.pi-section-title-icon{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:11px;color:var(--pi-primary);background:#eef2ff;border:1px solid #e0e7ff}
.pi-kpis{display:grid;grid-template-columns:1fr;gap:13px}@media(min-width:550px){.pi-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(min-width:1000px){.pi-kpis{grid-template-columns:repeat(4,minmax(0,1fr))}}.pi-kpi{position:relative;overflow:hidden;padding:19px;border:1px solid var(--pi-border);border-radius:20px;background:var(--pi-surface);box-shadow:var(--pi-soft)}.pi-kpi-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.pi-kpi-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:13px;color:var(--pi-primary);background:#eef2ff}.pi-kpi-label{margin-top:15px;color:var(--pi-muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em}.pi-kpi-value{margin-top:5px;color:#111827;font-size:clamp(21px,3vw,28px);line-height:1.1;font-weight:850;overflow-wrap:anywhere}.pi-kpi-sub{margin-top:7px;color:var(--pi-muted);font-size:11px}
.pi-layout{display:grid;grid-template-columns:minmax(0,1fr);gap:20px}@media(min-width:1050px){.pi-layout{grid-template-columns:minmax(0,1.4fr) minmax(320px,.6fr);align-items:start}}.pi-card{border:1px solid var(--pi-border);border-radius:22px;background:var(--pi-surface);backdrop-filter:blur(18px);box-shadow:var(--pi-soft);overflow:hidden}.pi-card-head{padding:19px 20px;border-bottom:1px solid rgba(15,23,42,.06);display:flex;align-items:center;justify-content:space-between;gap:15px}.pi-card-head h3{margin:0;color:#172033;font-size:15px;font-weight:800}.pi-card-head p{margin:4px 0 0;color:var(--pi-muted);font-size:11px}.pi-card-body{padding:20px}
.pi-info-grid{display:grid;grid-template-columns:1fr;gap:0}@media(min-width:650px){.pi-info-grid{grid-template-columns:repeat(2,minmax(0,1fr));column-gap:26px}}.pi-info-item{padding:15px 0;border-bottom:1px solid rgba(15,23,42,.055)}.pi-info-label{display:flex;align-items:center;gap:7px;color:var(--pi-muted);font-size:10px;font-weight:800;letter-spacing:.065em;text-transform:uppercase}.pi-info-label i{color:var(--pi-primary)}.pi-info-value{margin-top:7px;color:#172033;font-size:13px;font-weight:700;line-height:1.45;overflow-wrap:anywhere}
.pi-price-stack{display:grid;gap:12px}.pi-price-row{padding:16px;border:1px solid rgba(15,23,42,.07);border-radius:15px;background:rgba(255,255,255,.7)}.pi-price-row-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.pi-price-label{color:var(--pi-muted);font-size:11px;font-weight:700}.pi-price-value{margin-top:5px;color:#111827;font-size:20px;font-weight:850}.pi-price-meta{margin-top:4px;color:var(--pi-muted);font-size:10px}.pi-profit{border-color:rgba(5,150,105,.14);background:linear-gradient(135deg,rgba(236,253,245,.9),rgba(255,255,255,.72))}.pi-profit .pi-price-value{color:#047857}
.pi-stock-box{margin-top:18px}.pi-stock-bar{height:9px;overflow:hidden;border-radius:999px;background:#e5e7eb}.pi-stock-fill{height:100%;border-radius:inherit;background:linear-gradient(90deg,#4f46e5,#06b6d4)}.pi-stock-legend{display:flex;justify-content:space-between;gap:12px;margin-top:10px;color:var(--pi-muted);font-size:10px}.pi-stock-legend strong{color:#334155}
.pi-feature-grid{display:grid;grid-template-columns:1fr;gap:10px}@media(min-width:600px){.pi-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}.pi-feature{display:flex;align-items:flex-start;gap:12px;padding:14px;border:1px solid rgba(15,23,42,.07);border-radius:15px;background:rgba(255,255,255,.58)}.pi-feature-icon{width:34px;height:34px;flex:0 0 34px;display:flex;align-items:center;justify-content:center;border-radius:10px;color:var(--pi-primary);background:#eef2ff}.pi-feature-name{color:var(--pi-muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}.pi-feature-value{margin-top:4px;color:#172033;font-size:12px;line-height:1.4;font-weight:700;overflow-wrap:anywhere}
.pi-gallery-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px}.pi-gallery-count{color:var(--pi-muted);font-size:12px;font-weight:700}.pi-gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}@media(min-width:650px){.pi-gallery{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(min-width:1000px){.pi-gallery{grid-template-columns:repeat(4,minmax(0,1fr))}}.pi-gallery-item{position:relative;aspect-ratio:1;overflow:hidden;border-radius:17px;border:1px solid rgba(15,23,42,.08);background:#f8fafc;isolation:isolate}.pi-gallery-item img,.pi-gallery-item video{display:block;width:100%;height:100%;object-fit:cover}.pi-gallery-item img{transition:transform .35s}.pi-gallery-item:hover img{transform:scale(1.06)}.pi-gallery-overlay{position:absolute;inset:auto 0 0;padding:25px 10px 10px;background:linear-gradient(transparent,rgba(15,23,42,.65));color:#fff;font-size:10px;opacity:0;transition:.25s;pointer-events:none}.pi-gallery-item:hover .pi-gallery-overlay{opacity:1}.pi-video-open{position:relative;display:block;width:100%;height:100%;padding:0;border:0;background:#0f172a;cursor:pointer;overflow:hidden}.pi-video-open video{pointer-events:none}.pi-gallery-video-mark{position:absolute;left:10px;bottom:10px;color:#fff;background:rgba(15,23,42,.72);padding:6px 9px;border-radius:9px;font-size:10px;font-weight:800;pointer-events:none}
.pi-table-wrap{overflow-x:auto}.pi-table{width:100%;border-collapse:collapse;font-size:12px;min-width:620px}.pi-table th,.pi-table td{padding:11px 9px;text-align:left;border-bottom:1px solid rgba(15,23,42,.07);vertical-align:middle}.pi-table th{color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.05em}.pi-unit-status{display:inline-block;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:10px;font-weight:800}.pi-variant-card{border:1px solid rgba(15,23,42,.08);border-radius:17px;background:rgba(255,255,255,.72);padding:15px;margin-bottom:11px}.pi-variant-card:last-child{margin-bottom:0}.pi-variant-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}.pi-variant-name{font-size:14px;font-weight:850;color:#172033}.pi-variant-meta{margin-top:5px;color:var(--pi-muted);font-size:11px;line-height:1.6}.pi-variant-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:9px;margin-top:13px}.pi-variant-stat{padding:10px;border-radius:12px;background:#f8fafc;border:1px solid rgba(15,23,42,.05)}.pi-variant-stat span{display:block;color:var(--pi-muted);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}.pi-variant-stat strong{display:block;margin-top:4px;color:#172033;font-size:13px;overflow-wrap:anywhere}.pi-empty{padding:30px 15px;text-align:center;color:var(--pi-muted)}.pi-empty-icon{width:52px;height:52px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;border-radius:16px;background:#f1f5f9;color:#94a3b8;font-size:19px}.pi-empty strong{display:block;color:#475569;font-size:13px}.pi-empty span{display:block;margin-top:4px;font-size:11px}.pi-notice{display:flex;align-items:flex-start;gap:10px;padding:14px 16px;margin-bottom:18px;border:1px solid;border-radius:15px;font-size:13px;font-weight:650}.pi-notice.success{color:#047857;background:#ecfdf5;border-color:#a7f3d0}.pi-notice.error{color:#b91c1c;background:#fef2f2;border-color:#fecaca}.pi-video-modal{position:fixed;inset:0;z-index:100000;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(2,6,23,.88);backdrop-filter:blur(7px)}.pi-video-modal.is-open{display:flex}.pi-video-modal-panel{position:relative;width:min(100%,1000px);padding:18px;border:1px solid rgba(255,255,255,.15);border-radius:18px;background:#111827;box-shadow:0 25px 80px rgba(0,0,0,.5)}.pi-video-modal-title{margin:0 52px 14px 0;color:#fff;font-size:15px;font-weight:750}.pi-video-modal video{display:block;width:100%;max-height:75vh;background:#000;border-radius:10px}.pi-video-modal-close{position:absolute;top:12px;right:12px;width:38px;height:38px;border:0;border-radius:10px;color:#fff;background:#374151;font-size:22px;cursor:pointer}body.pi-video-modal-active{overflow:hidden}.pi-btn:focus-visible{outline:3px solid rgba(79,70,229,.35);outline-offset:3px}
@media(max-width:520px){.pi-container{padding:12px}.pi-hero{padding:17px;border-radius:20px}.pi-identity{align-items:flex-start;gap:12px}.pi-product-avatar{width:68px;height:68px;flex-basis:68px;border-radius:18px}.pi-title{font-size:20px}.pi-actions{width:100%}.pi-actions form{flex:1}.pi-btn{width:100%;padding:0 10px}.pi-card-body{padding:15px}.pi-card-head{padding:16px}.pi-gallery-toolbar{align-items:stretch}.pi-gallery-add{width:100%}}
</style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}" x-init="darkMode=JSON.parse(localStorage.getItem('darkMode'));$watch('darkMode',value=>localStorage.setItem('darkMode',JSON.stringify(value)))" :class="{'dark bg-gray-900':darkMode===true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="pi-container">
                <div class="pi-breadcrumb"><a href="index.php"><i class="fas fa-house"></i> Home</a><i class="fas fa-chevron-right"></i><a href="products.php">Products</a><i class="fas fa-chevron-right"></i><span>Branch Product Intelligence</span></div>
                <section class="pi-hero">
                    <div class="pi-identity">
                        <div class="pi-product-avatar">
                            <?php if ($productImageUrl): ?><img src="<?= $e($productImageUrl) ?>" alt="<?= $e($single_product['name'] ?? 'Product') ?>" onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fas fa-box-open\'></i>'">
                            <?php else: ?><i class="fas fa-box-open"></i><?php endif; ?>
                        </div>
                        <div>
                            <div class="pi-eyebrow"><i class="fas fa-microchip"></i> Branch Product Intelligence <?= $hasVariants ? '· Variant product' : '' ?></div>
                            <h1 class="pi-title"><?= $e($single_product['name'] ?? 'Unnamed Product') ?></h1>
                            <div class="pi-category"><i class="fas fa-location-dot"></i><span>Branch location #<?= (int)$branchLocationId ?></span><span>•</span><i class="fas fa-layer-group"></i><span><?= $e($single_product['category_name'] ?? 'No Category') ?></span><span>•</span><span><?= $e($single_product['stock_type'] ?? 'Standard Stock') ?></span><span>•</span><span class="pi-status <?= $e($stockClass) ?>"><i class="fas <?= $e($stockIcon) ?>"></i><?= $e($stockStatus) ?></span></div>
                        </div>
                    </div>
                    <div class="pi-actions">
                        <?php if (is_file($cataloguePath)): ?><a href="<?= $e($cataloguePath) ?>" target="_blank" rel="noopener noreferrer" class="pi-btn"><i class="fas fa-file-pdf"></i> Catalogue</a><?php else: ?><button type="button" class="pi-btn" disabled style="opacity:.55;cursor:not-allowed"><i class="fas fa-file-circle-xmark"></i> No Catalogue</button><?php endif; ?>
                    </div>
                </section>

                <section class="pi-section">
                    <div class="pi-section-heading"><h2 class="pi-section-title"><span class="pi-section-title-icon"><i class="fas fa-chart-line"></i></span> Inventory Intelligence</h2></div>
                    <div class="pi-kpis">
                        <div class="pi-kpi"><div class="pi-kpi-top"><div class="pi-kpi-icon"><i class="fas fa-cubes-stacked"></i></div><span class="pi-status <?= $e($stockClass) ?>"><?= $e($stockStatus) ?></span></div><div class="pi-kpi-label">Total Stock</div><div class="pi-kpi-value"><?= number_format($total_stock) ?></div><div class="pi-kpi-sub"><?= $hasVariants ? 'Combined stock for this branch location' : 'Current inventory quantity' ?></div></div>
                        <div class="pi-kpi"><div class="pi-kpi-top"><div class="pi-kpi-icon"><i class="fas fa-location-dot"></i></div></div><div class="pi-kpi-label"><?= $hasVariants ? 'Variants Configured' : 'Assigned Stock' ?></div><div class="pi-kpi-value"><?= $hasVariants ? number_format(count($variants)) : number_format($assigned_stock) ?></div><div class="pi-kpi-sub"><?= $hasVariants ? 'Sellable variant combinations' : 'Inventory already assigned' ?></div></div>
                        <div class="pi-kpi"><div class="pi-kpi-top"><div class="pi-kpi-icon"><i class="fas fa-box"></i></div></div><div class="pi-kpi-label"><?= $hasVariants ? 'Available Variant Stock' : 'Available Stock' ?></div><div class="pi-kpi-value"><?= number_format($unassigned_stock) ?></div><div class="pi-kpi-sub"><?= $hasVariants ? 'Variant quantity at this branch' : 'Unassigned inventory' ?></div></div>
                        <div class="pi-kpi"><div class="pi-kpi-top"><div class="pi-kpi-icon"><i class="fas fa-naira-sign"></i></div></div><div class="pi-kpi-label">Stock Value</div><div class="pi-kpi-value"><?= $money($total_stock_value) ?></div><div class="pi-kpi-sub">Based on applicable selling prices</div></div>
                    </div>
                </section>

                <?php if ($hasVariants): ?>
                <section class="pi-card pi-section">
                    <div class="pi-card-head"><div><h3>Product Variants</h3><p>Independent sellable options, pricing, tracking and stock at this branch</p></div><div class="pi-section-title-icon"><i class="fas fa-swatchbook"></i></div></div>
                    <div class="pi-card-body">
                        <?php if ($variants): foreach ($variants as $variant):
                            $vCost = $variant['cost_price'] !== null ? (float)$variant['cost_price'] : $parentCost;
                            $vSell = $variant['selling_price'] !== null ? (float)$variant['selling_price'] : $parentSelling;
                            $vPolicy = $effectiveTrackingByVariant[(int)$variant['id']] ?? 'none';
                            [$vStatus, $vClass] = $variantStockStatus($variant['total_stock']);
                        ?>
                            <article class="pi-variant-card">
                                <div class="pi-variant-top"><div><div class="pi-variant-name"><?= $e($variant['variant_name'] ?: 'Variant #' . $variant['id']) ?> <?php if (!empty($variant['is_default'])): ?><span class="pi-status success">Default</span><?php endif; ?></div><div class="pi-variant-meta">SKU: <?= $e($variant['sku'] ?: 'Not assigned') ?> · Barcode: <?= $e($variant['barcode'] ?: 'Not assigned') ?><br><?= $e($variant['attributes'] ? implode(' · ', $variant['attributes']) : 'No attribute values linked') ?></div></div><span class="pi-status <?= $e($vClass) ?>"><?= $e($vStatus) ?></span></div>
                                <div class="pi-variant-stats"><div class="pi-variant-stat"><span>Stock</span><strong><?= number_format((float)$variant['total_stock']) ?></strong></div><div class="pi-variant-stat"><span>Cost price</span><strong><?= $money($vCost) ?></strong></div><div class="pi-variant-stat"><span>Selling price</span><strong><?= $money($vSell) ?></strong></div><div class="pi-variant-stat"><span>Tracking</span><strong><?= $e(strtoupper($vPolicy)) ?></strong></div><div class="pi-variant-stat"><span>Availability</span><strong><?= !empty($variant['is_active']) ? 'Active' : 'Inactive' ?></strong></div></div>
                            </article>
                        <?php endforeach; else: ?><div class="pi-empty"><div class="pi-empty-icon"><i class="fas fa-swatchbook"></i></div><strong>No variants configured</strong><span>This product is marked as variant-based, but no variant combinations were found.</span></div><?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <div class="pi-layout">
                    <div>
                        <section class="pi-card pi-section">
                            <div class="pi-card-head"><div><h3>Product Information</h3><p>Core identity and classification data</p></div><div class="pi-section-title-icon"><i class="fas fa-fingerprint"></i></div></div>
                            <div class="pi-card-body"><div class="pi-info-grid">
                                <?php foreach ([['Barcode',$single_product['barcode'] ?? 'Not assigned','fa-barcode'],['Product Name',$single_product['name'] ?? 'N/A','fa-box'],['Category',$single_product['category_name'] ?? 'No Category','fa-layer-group'],['Stock Purpose',$single_product['stock_type'] ?? 'N/A','fa-bullseye'],['Company',$single_product['company_name'] ?? 'N/A','fa-building'],['Created By',$single_product['created_by_email'] ?? 'N/A','fa-user-shield'],['Created At',$single_product['created_at'] ?? 'N/A','fa-calendar-plus']] as $info): ?><div class="pi-info-item"><div class="pi-info-label"><i class="fas <?= $e($info[2]) ?>"></i><?= $e($info[0]) ?></div><div class="pi-info-value"><?= $e($info[1]) ?></div></div><?php endforeach; ?>
                                <div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-tags"></i>Inventory Model</div><div class="pi-info-value"><?= $hasVariants ? 'Variant-based inventory' : 'Standard product inventory' ?></div></div>
                            </div></div>
                        </section>

                        <?php if ($trackingEnabled): ?>
                        <section class="pi-card pi-section">
                            <div class="pi-card-head"><div><h3>Individual Unit Tracking</h3><p>IMEI, serial number or VIN records linked to this product and its variants</p></div><div class="pi-section-title-icon"><i class="fas fa-barcode"></i></div></div>
                            <div class="pi-card-body">
                                <div class="pi-kpis" style="grid-template-columns:repeat(auto-fit,minmax(115px,1fr));margin-bottom:15px"><div class="pi-kpi"><div class="pi-kpi-label">Total tracked</div><div class="pi-kpi-value"><?= number_format(count($trackedUnits)) ?></div></div>
                                <?php foreach (['in_stock'=>'In stock','reserved'=>'Reserved','sold'=>'Sold','under_repair'=>'Repair','damaged'=>'Damaged','written_off'=>'Written off','returned_pending_inspection'=>'Inspection'] as $state => $label): ?><?php if (!empty($trackedCounts[$state])): ?><div class="pi-kpi"><div class="pi-kpi-label"><?= $e($label) ?></div><div class="pi-kpi-value"><?= number_format($trackedCounts[$state]) ?></div></div><?php endif; ?><?php endforeach; ?></div>
                                <?php if ($trackedUnits): ?><div class="pi-table-wrap"><table class="pi-table"><thead><tr><th>Identifier</th><th>Type</th><?php if ($hasVariants): ?><th>Variant</th><?php endif; ?><th>Status</th><th>Stock ID</th><th>Location ID</th><th>Received</th></tr></thead><tbody><?php foreach ($trackedUnits as $unit): ?><tr><td><strong><?= $e($unit['identifier_value']) ?></strong></td><td><?= $e(strtoupper($unit['identifier_type'])) ?></td><?php if ($hasVariants): ?><td><?= $e($unit['variant_name'] ?: 'Parent / legacy record') ?></td><?php endif; ?><td><span class="pi-unit-status"><?= $e(ucwords(str_replace('_',' ',$unit['status']))) ?></span></td><td><?= $unit['stock_id'] === null ? '—' : (int)$unit['stock_id'] ?></td><td><?= $unit['location_id'] === null ? '—' : (int)$unit['location_id'] ?></td><td><?= $e($unit['received_at'] ?? '—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="pi-empty"><div class="pi-empty-icon"><i class="fas fa-barcode"></i></div><strong>No individual identifiers registered yet</strong><span>Identifiers will appear here after tracked units are received into stock.</span></div><?php endif; ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <section class="pi-card pi-section">
                            <div class="pi-card-head"><div><h3>Product Features</h3><p>Configured product characteristics</p></div><div class="pi-section-title-icon"><i class="fas fa-sliders"></i></div></div>
                            <div class="pi-card-body"><?php if ($features): ?><div class="pi-feature-grid"><?php foreach ($features as $feature): ?><div class="pi-feature"><div class="pi-feature-icon"><i class="fas fa-sparkles"></i></div><div><div class="pi-feature-name"><?= $e($feature['feature_name']) ?></div><div class="pi-feature-value"><?= $e($feature['feature_value']) ?></div></div></div><?php endforeach; ?></div><?php else: ?><div class="pi-empty"><div class="pi-empty-icon"><i class="fas fa-sliders"></i></div><strong>No product features yet</strong><span>Product characteristics will appear here when configured.</span></div><?php endif; ?></div>
                        </section>

                        <section class="pi-card pi-section">
                            <div class="pi-card-head"><div><h3>Product Gallery</h3><p>Parent and variant images and videos</p></div><div class="pi-section-title-icon"><i class="fas fa-images"></i></div></div>
                            <div class="pi-card-body">
                                <div class="pi-gallery-toolbar"><span class="pi-gallery-count"><i class="fas fa-layer-group"></i> <?= $imageCount ?> image(s) · <?= $videoCount ?> video(s)</span><span class="pi-gallery-count">Read-only branch view</span></div>
                                <?php if ($images): ?><div class="pi-gallery">
                                    <?php foreach ($images as $index => $img):
                                        $mediaType = (string)($img['media_type'] ?? 'image');
                                        $mediaPath = (string)($img['media_path'] ?? '');
                                        $thumbPath = (string)($img['thumbnail_path'] ?? '');
                                        $mediaUrl = $resolveProductMediaUrl($mediaPath);
                                        $thumbUrl = $resolveProductMediaUrl($thumbPath ?: $mediaPath);
                                        $mediaLabel = trim((string)($img['media_name'] ?? '')) ?: ucfirst($mediaType) . ' ' . ($index + 1);
                                        $variantLabel = '';
                                        if (!empty($img['variant_id'])) {
                                            foreach ($variants as $v) if ((int)$v['id'] === (int)$img['variant_id']) { $variantLabel = (string)$v['variant_name']; break; }
                                        }
                                    ?>
                                    <div class="pi-gallery-item">
                                        <?php if ($mediaType === 'video'): ?><button type="button" class="pi-video-open" data-video-src="<?= $e($mediaUrl) ?>" data-video-title="<?= $e($mediaLabel) ?>" aria-label="Play video: <?= $e($mediaLabel) ?>"><video muted autoplay loop playsinline preload="metadata" aria-hidden="true" tabindex="-1"><source src="<?= $e($mediaUrl) ?>"></video><span class="pi-gallery-video-mark"><i class="fas fa-play"></i> PLAY VIDEO</span></button>
                                        <?php else: ?><a href="<?= $e($mediaUrl) ?>" target="_blank" rel="noopener"><img src="<?= $e($thumbUrl) ?>" alt="<?= $e($mediaLabel) ?>" loading="lazy"></a><?php endif; ?>
                                        <div class="pi-gallery-overlay"><i class="fas <?= $mediaType === 'video' ? 'fa-video' : 'fa-image' ?>"></i> <?= $e($mediaLabel) ?><?= $variantLabel !== '' ? ' · ' . $e($variantLabel) : '' ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div><?php else: ?><div class="pi-empty"><div class="pi-empty-icon"><i class="fas fa-image"></i></div><strong>No product media uploaded</strong><span>Add product images or videos to build the visual catalogue.</span></div><?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <aside>
                        <section class="pi-card pi-section">
                            <div class="pi-card-head"><div><h3>Pricing Intelligence</h3><p><?= $hasVariants ? 'Variant price range and inventory economics' : 'Current product economics' ?></p></div><div class="pi-section-title-icon"><i class="fas fa-chart-pie"></i></div></div>
                            <div class="pi-card-body"><div class="pi-price-stack">
                                <div class="pi-price-row"><div class="pi-price-row-top"><span class="pi-price-label">Cost Price</span><i class="fas fa-arrow-down" style="color:#64748b"></i></div><div class="pi-price-value"><?php if ($hasVariants && $variantCostValues): ?><?= $money(min($variantCostValues)) ?> – <?= $money(max($variantCostValues)) ?><?php else: ?><?= $money($cost_price) ?><?php endif; ?></div><div class="pi-price-meta"><?= $hasVariants ? 'Lowest to highest configured variant cost' : 'Acquisition / production cost' ?></div></div>
                                <div class="pi-price-row"><div class="pi-price-row-top"><span class="pi-price-label">Selling Price</span><i class="fas fa-arrow-up" style="color:#059669"></i></div><div class="pi-price-value"><?php if ($hasVariants && $variantPriceValues): ?><?= $money(min($variantPriceValues)) ?> – <?= $money(max($variantPriceValues)) ?><?php else: ?><?= $money($selling_price) ?><?php endif; ?></div><div class="pi-price-meta"><?= $hasVariants ? 'Lowest to highest configured variant selling price' : 'Current unit selling price' ?></div></div>
                                <?php if (!$hasVariants): ?><div class="pi-price-row pi-profit"><div class="pi-price-row-top"><span class="pi-price-label">Unit Profit</span><i class="fas fa-coins" style="color:#059669"></i></div><div class="pi-price-value"><?= $money($unitProfit) ?></div><div class="pi-price-meta"><?= number_format($profitMargin,1) ?>% estimated margin</div></div><?php endif; ?>
                                <div class="pi-price-row"><div class="pi-price-row-top"><span class="pi-price-label">Total Stock Value</span><i class="fas fa-vault" style="color:#4f46e5"></i></div><div class="pi-price-value"><?= $money($total_stock_value) ?></div><div class="pi-price-meta">Inventory quantity × applicable selling price</div></div>
                            </div></div>
                        </section>
                        <section class="pi-card pi-section">
                            <div class="pi-card-head"><div><h3>Stock Distribution</h3><p><?= $hasVariants ? 'Stock held at this branch' : 'Assigned vs available inventory' ?></p></div><div class="pi-section-title-icon"><i class="fas fa-chart-simple"></i></div></div>
                            <div class="pi-card-body">
                                <?php if ($hasVariants): ?><div class="pi-price-row"><div class="pi-price-row-top"><span class="pi-price-label">Variants with stock</span><strong style="color:#4f46e5"><?= count(array_filter($variants, static fn($v) => (float)$v['total_stock'] > 0)) ?> / <?= count($variants) ?></strong></div></div><div class="pi-stock-box"><div class="pi-stock-bar"><div class="pi-stock-fill" style="width:<?= $total_stock > 0 ? 100 : 0 ?>%"></div></div><div class="pi-stock-legend"><span>Total variant stock: <strong><?= number_format($total_stock) ?></strong></span><span>Branch location #<?= (int)$branchLocationId ?></span></div></div>
                                <?php else: ?><div class="pi-price-row"><div class="pi-price-row-top"><span class="pi-price-label">Assigned</span><strong style="color:#4f46e5"><?= number_format($assigned_stock) ?></strong></div></div><div class="pi-stock-box"><div class="pi-stock-bar"><div class="pi-stock-fill" style="width:<?= $assignedPercentage ?>%"></div></div><div class="pi-stock-legend"><span>Assigned: <strong><?= number_format($assigned_stock) ?></strong></span><span>Available: <strong><?= number_format($unassigned_stock) ?></strong></span></div></div><?php endif; ?>
                            </div>
                        </section>
                        <section class="pi-card pi-section">
                            <div class="pi-card-head"><div><h3>System Metadata</h3><p>Product identification</p></div><div class="pi-section-title-icon"><i class="fas fa-database"></i></div></div>
                            <div class="pi-card-body"><div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-hashtag"></i> Product ID</div><div class="pi-info-value">#<?= (int)$single_product['id'] ?></div></div><div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-link"></i> Product Slug</div><div class="pi-info-value"><?= $e($single_product['slug'] ?? 'N/A') ?></div></div><div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-building"></i> Company ID</div><div class="pi-info-value"><?= $e($company_id) ?></div></div><div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-location-dot"></i> Branch Location ID</div><div class="pi-info-value">#<?= (int)$branchLocationId ?></div></div><div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-barcode"></i> Tracking Policy</div><div class="pi-info-value"><?= $hasVariants ? 'Variant override / parent inheritance' : strtoupper($e($parentTracking)) ?></div></div></div>
                        </section>
                    </aside>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>
<div class="pi-video-modal" id="piVideoModal" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Product video player"><div class="pi-video-modal-panel"><button type="button" class="pi-video-modal-close" id="piVideoModalClose" aria-label="Close video">&times;</button><h3 class="pi-video-modal-title" id="piVideoModalTitle">Product video</h3><video id="piVideoModalPlayer" controls playsinline preload="metadata">Your browser does not support video playback.</video></div></div>
<script>
(function(){const modal=document.getElementById('piVideoModal'),player=document.getElementById('piVideoModalPlayer'),title=document.getElementById('piVideoModalTitle'),closeButton=document.getElementById('piVideoModalClose');if(!modal||!player||!closeButton)return;function closeVideoModal(){player.pause();player.removeAttribute('src');player.load();modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('pi-video-modal-active')}document.addEventListener('click',function(event){const trigger=event.target.closest('.pi-video-open');if(trigger){event.preventDefault();player.src=trigger.dataset.videoSrc||'';title.textContent=trigger.dataset.videoTitle||'Product video';modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('pi-video-modal-active');player.load();const promise=player.play();if(promise&&typeof promise.catch==='function')promise.catch(function(){});closeButton.focus();return}if(event.target===modal||event.target.closest('#piVideoModalClose'))closeVideoModal()});document.addEventListener('keydown',function(event){if(event.key==='Escape'&&modal.classList.contains('is-open'))closeVideoModal()})})();
</script>
<script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
<script defer src="bundle.js"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"95aebaa31b75b6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfSpeedBrain":true,"cfCacheStatus":true}}}' crossorigin="anonymous"></script>
</body>
</html>
