<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = (int)($_SESSION['company_id'] ?? 1);
$user_id = $_SESSION['user_id'] ?? null;

if (!function_exists('stock_h')) {
    function stock_h($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/*
 * Inventory source rules:
 * - Legacy/non-variant products are read from stocks.
 * - Variant products are read from product_variant_stocks.
 * - The parent stocks row for a variant product is an aggregate and is not
 *   rendered or added to totals, preventing double counting.
 */

$legacySql = "
    SELECT
        s.id AS stock_id,
        s.product_id,
        NULL AS variant_id,
        p.name AS product_name,
        '' AS variant_name,
        p.barcode,
        '' AS sku,
        p.category_id,
        COALESCE(c.name, 'Uncategorised') AS category_name,
        CAST(COALESCE(s.quantity, 0) AS DECIMAL(14,2)) AS quantity,
        s.created_at,
        s.location_id,
        COALESCE(l.name, 'Unknown location') AS location_name,
        s.supplier,
        CAST(p.cost_price AS DECIMAL(14,2)) AS cost_price,
        CAST(p.selling_price AS DECIMAL(14,2)) AS selling_price,
        p.stock_type,
        u.unit_name,
        COALESCE(si.quantity, 0) AS last_stock_in_quantity,
        CASE WHEN s.supplier = 'Production' THEN 'Production' ELSE v.business_name END AS supplier_name,
        CASE
            WHEN COALESCE(p.identifier_tracking, 'none') <> 'none' THEN p.identifier_tracking
            WHEN COALESCE(p.imei_tracking, 0) = 1 THEN 'imei'
            ELSE 'none'
        END AS tracking_type,
        COALESCE(serials.identifier_count, 0) AS identifier_count,
        COALESCE(serials.identifiers, '') AS identifiers
    FROM stocks s
    INNER JOIN products p
        ON p.id = s.product_id AND p.company_id = s.company_id
    LEFT JOIN product_categories c
        ON c.id = p.category_id AND c.company_id = p.company_id
    LEFT JOIN locations l
        ON l.id = s.location_id AND l.company_id = s.company_id
    LEFT JOIN units u ON u.id = p.unit_id
    LEFT JOIN vendors v
        ON CAST(s.supplier AS UNSIGNED) = v.id AND v.company_id = s.company_id
    LEFT JOIN stock_in si
        ON si.id = (
            SELECT si2.id
            FROM stock_in si2
            WHERE si2.stock_id = s.id
              AND si2.company_id = s.company_id
              AND si2.location_id = s.location_id
              AND si2.variant_id IS NULL
            ORDER BY si2.id DESC LIMIT 1
        )
    LEFT JOIN (
        SELECT company_id, product_id, COALESCE(variant_id, 0) AS variant_key,
               COALESCE(location_id, 0) AS location_key,
               COUNT(*) AS identifier_count,
               GROUP_CONCAT(identifier_value ORDER BY identifier_value SEPARATOR ', ') AS identifiers
        FROM product_serials
        WHERE status = 'in_stock'
        GROUP BY company_id, product_id, COALESCE(variant_id, 0), COALESCE(location_id, 0)
    ) serials
        ON serials.company_id = s.company_id
       AND serials.product_id = s.product_id
       AND serials.variant_key = 0
       AND serials.location_key = s.location_id
    WHERE s.company_id = ?
      AND COALESCE(p.has_variants, 0) = 0
    ORDER BY p.name, l.name
";
$legacyStmt = $pdo->prepare($legacySql);
$legacyStmt->execute([$company_id]);
$legacyStocks = $legacyStmt->fetchAll(PDO::FETCH_ASSOC);

$variantSql = "
    SELECT
        parent_stock.id AS stock_id,
        p.id AS product_id,
        pv.id AS variant_id,
        p.name AS product_name,
        pv.variant_name,
        COALESCE(NULLIF(pv.barcode, ''), p.barcode) AS barcode,
        COALESCE(pv.sku, '') AS sku,
        p.category_id,
        COALESCE(c.name, 'Uncategorised') AS category_name,
        CAST(COALESCE(pvs.quantity, 0) AS DECIMAL(14,2)) AS quantity,
        COALESCE(pvs.updated_at, pv.created_at) AS created_at,
        pvs.location_id,
        COALESCE(l.name, 'No stock location yet') AS location_name,
        COALESCE(si.supplier, '') AS supplier,
        CAST(COALESCE(pv.cost_price, p.cost_price) AS DECIMAL(14,2)) AS cost_price,
        CAST(COALESCE(pv.selling_price, p.selling_price) AS DECIMAL(14,2)) AS selling_price,
        p.stock_type,
        u.unit_name,
        COALESCE(si.quantity, 0) AS last_stock_in_quantity,
        CASE
            WHEN si.supplier = 'Production' THEN 'Production'
            ELSE v.business_name
        END AS supplier_name,
        CASE
            WHEN pv.tracking_policy <> 'inherit' THEN pv.tracking_policy
            WHEN COALESCE(p.identifier_tracking, 'none') <> 'none' THEN p.identifier_tracking
            WHEN COALESCE(p.imei_tracking, 0) = 1 THEN 'imei'
            ELSE 'none'
        END AS tracking_type,
        COALESCE(serials.identifier_count, 0) AS identifier_count,
        COALESCE(serials.identifiers, '') AS identifiers
    FROM product_variants pv
    INNER JOIN products p
        ON p.id = pv.product_id AND p.company_id = pv.company_id
    LEFT JOIN product_variant_stocks pvs
        ON pvs.company_id = pv.company_id
       AND pvs.product_id = pv.product_id
       AND pvs.variant_id = pv.id
    LEFT JOIN stocks parent_stock
        ON parent_stock.company_id = pv.company_id
       AND parent_stock.product_id = pv.product_id
       AND parent_stock.location_id = pvs.location_id
    LEFT JOIN product_categories c
        ON c.id = p.category_id AND c.company_id = p.company_id
    LEFT JOIN locations l
        ON l.id = pvs.location_id AND l.company_id = pv.company_id
    LEFT JOIN units u ON u.id = p.unit_id
    LEFT JOIN stock_in si
        ON si.id = (
            SELECT si2.id
            FROM stock_in si2
            WHERE si2.company_id = pv.company_id
              AND si2.product_id = pv.product_id
              AND si2.variant_id = pv.id
              AND si2.location_id = pvs.location_id
            ORDER BY si2.id DESC LIMIT 1
        )
    LEFT JOIN vendors v
        ON CAST(si.supplier AS UNSIGNED) = v.id AND v.company_id = pv.company_id
    LEFT JOIN (
        SELECT company_id, product_id, COALESCE(variant_id, 0) AS variant_key,
               COALESCE(location_id, 0) AS location_key,
               COUNT(*) AS identifier_count,
               GROUP_CONCAT(identifier_value ORDER BY identifier_value SEPARATOR ', ') AS identifiers
        FROM product_serials
        WHERE status = 'in_stock'
        GROUP BY company_id, product_id, COALESCE(variant_id, 0), COALESCE(location_id, 0)
    ) serials
        ON serials.company_id = pv.company_id
       AND serials.product_id = pv.product_id
       AND serials.variant_key = pv.id
       AND serials.location_key = COALESCE(pvs.location_id, 0)
    WHERE pv.company_id = ?
      AND p.has_variants = 1
      AND pv.is_active = 1
    ORDER BY p.name, pv.variant_name, l.name
";
$variantStmt = $pdo->prepare($variantSql);
$variantStmt->execute([$company_id]);
$variantStocks = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

$stocks = array_merge($legacyStocks, $variantStocks);
usort($stocks, static function ($a, $b) {
    $productCompare = strcasecmp((string)$a['product_name'], (string)$b['product_name']);
    if ($productCompare !== 0) return $productCompare;
    $variantCompare = strcasecmp((string)$a['variant_name'], (string)$b['variant_name']);
    if ($variantCompare !== 0) return $variantCompare;
    return strcasecmp((string)$a['location_name'], (string)$b['location_name']);
});

/* Totals are calculated from the same non-overlapping inventory sources as the table. */
$totalQuantity = 0.0;
$totalStockValue = 0.0;
$totalStockProfit = 0.0;
$stockedProducts = [];
foreach ($stocks as $stock) {
    $qty = (float)$stock['quantity'];
    $cost = (float)$stock['cost_price'];
    $selling = (float)$stock['selling_price'];
    $totalQuantity += $qty;
    $totalStockValue += $qty * $cost;
    $totalStockProfit += $qty * ($selling - $cost);
    if ($qty > 0) $stockedProducts[(int)$stock['product_id']] = true;
}
$totalProducts = count($stockedProducts);
$categoriesStmt = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE company_id = ?");
$categoriesStmt->execute([$company_id]);
$totalCategories = (int)$categoriesStmt->fetchColumn();

/* Category chart combines legacy and variant stock without including parent aggregates. */
$categoriesStmt = $pdo->prepare("
    SELECT category, SUM(quantity) AS total
    FROM (
        SELECT COALESCE(c.name, 'Uncategorised') AS category, SUM(COALESCE(s.quantity,0)) AS quantity
        FROM stocks s
        INNER JOIN products p ON p.id = s.product_id AND p.company_id = s.company_id
        LEFT JOIN product_categories c ON c.id = p.category_id AND c.company_id = p.company_id
        WHERE s.company_id = ? AND COALESCE(p.has_variants,0) = 0
        GROUP BY c.id, c.name
        UNION ALL
        SELECT COALESCE(c.name, 'Uncategorised') AS category, SUM(COALESCE(pvs.quantity,0)) AS quantity
        FROM product_variant_stocks pvs
        INNER JOIN products p ON p.id = pvs.product_id AND p.company_id = pvs.company_id
        LEFT JOIN product_categories c ON c.id = p.category_id AND c.company_id = p.company_id
        WHERE pvs.company_id = ? AND p.has_variants = 1
        GROUP BY c.id, c.name
    ) category_totals
    GROUP BY category
    ORDER BY total DESC
");
$categoriesStmt->execute([$company_id, $company_id]);
$categoriesDataArr = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
$categoriesLabels = array_column($categoriesDataArr, 'category');
$categoriesData = array_map('floatval', array_column($categoriesDataArr, 'total'));

/* Receipts trend uses stock_in records, including variant receipts. */
$trendStmt = $pdo->prepare("
    SELECT DATE(created_at) AS date, SUM(quantity) AS total
    FROM stock_in
    WHERE company_id = ?
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");
$trendStmt->execute([$company_id]);
$trendDataArr = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
$trendLabels = array_column($trendDataArr, 'date');
$trendData = array_map('floatval', array_column($trendDataArr, 'total'));

$locationsStmt = $pdo->prepare("SELECT id, name FROM locations WHERE company_id = ? ORDER BY name");
$locationsStmt->execute([$company_id]);
$locations = $locationsStmt->fetchAll(PDO::FETCH_ASSOC);
$vendorsStmt = $pdo->prepare("SELECT business_name FROM vendors WHERE company_id = ? ORDER BY business_name");
$vendorsStmt->execute([$company_id]);
$vendors = $vendorsStmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= stock_h($company_name ?? 'Inventory') ?> | Stock Terminal</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<style>
:root{--st-bg:#f5f7fb;--st-card:#fff;--st-border:#e5eaf2;--st-text:#172033;--st-muted:#718096;--st-accent:#5b5ce2;--st-green:#0f9f76;--st-red:#dc4c4c;--st-amber:#d58a16;--st-blue:#2875cf}
.dark{--st-bg:#080d18;--st-card:#101827;--st-border:rgba(148,163,184,.16);--st-text:#f4f7fb;--st-muted:#9aa8bc}
.stock-page{width:100%;max-width:1800px;margin:0 auto;color:var(--st-text)}
.stock-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:22px}.stock-heading{display:flex;align-items:center;gap:14px}.stock-heading-icon{width:48px;height:48px;border-radius:15px;display:grid;place-items:center;background:linear-gradient(135deg,rgba(91,92,226,.17),rgba(139,92,246,.1));color:var(--st-accent);font-size:20px}.stock-header h1{font-size:22px;font-weight:800;margin:0;letter-spacing:-.03em;color:var(--st-text)}.stock-header p{margin:4px 0 0;font-size:13px;color:var(--st-muted)}
.stock-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}.stock-kpi,.stock-panel,.stock-chart-card{background:var(--st-card);border:1px solid var(--st-border);border-radius:18px;box-shadow:0 9px 28px rgba(15,23,42,.045)}.stock-kpi{padding:18px;min-height:125px;position:relative;overflow:hidden}.stock-kpi-icon{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;margin-bottom:12px;background:rgba(91,92,226,.1);color:var(--st-accent)}.stock-kpi-label{font-size:10px;font-weight:800;letter-spacing:.075em;text-transform:uppercase;color:var(--st-muted)}.stock-kpi-value{font-size:clamp(20px,2vw,29px);font-weight:850;line-height:1.2;margin-top:5px;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}.stock-kpi-value.profit{color:var(--st-green)}
.stock-panel{margin-top:20px;padding:17px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.panel-title{font-size:14px;font-weight:800;display:flex;align-items:center;gap:9px}.panel-title i{color:var(--st-accent)}.muted-small{font-size:11px;color:var(--st-muted)}.filter-grid{display:grid;grid-template-columns:minmax(220px,2fr) repeat(5,minmax(125px,1fr));gap:9px}.filter-field{position:relative}.filter-field>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#98a3b4;font-size:12px;pointer-events:none}.filter-field input,.filter-field select{width:100%;height:43px;border:1px solid var(--st-border);border-radius:10px;background:var(--st-bg);color:var(--st-text);padding:0 11px 0 34px;font-size:12px;outline:none}.filter-field input:focus,.filter-field select:focus{border-color:var(--st-accent);box-shadow:0 0 0 3px rgba(91,92,226,.1)}.filter-grid.second{grid-template-columns:repeat(3,minmax(150px,1fr)) auto;margin-top:9px}.clear-filter{height:43px;padding:0 15px;border:1px solid var(--st-border);border-radius:10px;background:transparent;color:var(--st-muted);font-weight:750;font-size:12px;cursor:pointer}.clear-filter:hover{color:var(--st-red);border-color:rgba(220,76,76,.35)}
.stock-table-card{margin-top:20px;overflow:hidden}.table-top{display:flex;justify-content:space-between;align-items:center;padding:15px 17px;border-bottom:1px solid var(--st-border);gap:12px}.table-top .panel-title{margin:0}.table-scroll{overflow:auto;-webkit-overflow-scrolling:touch}.stock-table{width:100%;min-width:1850px;border-collapse:separate;border-spacing:0}.stock-table th{position:sticky;top:0;background:var(--st-bg);color:var(--st-muted);font-size:9px;letter-spacing:.07em;text-transform:uppercase;font-weight:850;text-align:left;padding:13px 12px;white-space:nowrap;border-bottom:1px solid var(--st-border)}.stock-table td{padding:12px;border-bottom:1px solid var(--st-border);font-size:11px;color:var(--st-text);white-space:nowrap;vertical-align:middle}.stock-table tbody tr:hover{background:rgba(91,92,226,.035)}.stock-table tbody tr:last-child td{border-bottom:0}.product-cell{display:flex;align-items:center;gap:9px;min-width:210px}.product-icon{width:33px;height:33px;border-radius:10px;display:grid;place-items:center;background:rgba(91,92,226,.1);color:var(--st-accent);flex:0 0 33px}.product-name{font-weight:800}.variant-name{font-size:10px;color:var(--st-accent);margin-top:3px}.sku{font-family:ui-monospace,monospace;color:var(--st-muted)}.qty{font-weight:850;font-variant-numeric:tabular-nums}.money{font-weight:700;font-variant-numeric:tabular-nums}.money.blue{color:var(--st-blue)}.money.green{color:var(--st-green)}.money.red{color:var(--st-red)}.status{display:inline-flex;align-items:center;gap:6px;padding:5px 8px;border-radius:30px;font-size:9px;font-weight:850}.status:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.status.low{background:rgba(220,76,76,.1);color:var(--st-red)}.status.zero{background:rgba(113,128,150,.12);color:var(--st-muted)}.status.ok{background:rgba(15,159,118,.1);color:var(--st-green)}.tracking{display:flex;flex-direction:column;gap:4px;max-width:210px;white-space:normal}.tracking-type{font-size:9px;font-weight:850;text-transform:uppercase;color:var(--st-muted)}.tracking-count{font-size:10px;color:var(--st-green);font-weight:800}.identifier-list{font-family:ui-monospace,monospace;font-size:9px;color:var(--st-muted);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.action-stack{display:flex;align-items:center;gap:5px}.action-form{margin:0}.action-btn{height:31px;padding:0 9px;border:0;border-radius:8px;color:#fff;font-size:9px;font-weight:800;display:inline-flex;align-items:center;gap:5px;cursor:pointer;text-decoration:none}.action-btn.history{background:#5558d9}.action-btn.restock{background:#2379d2}.action-btn.produce{background:#7b4bd2}.action-btn.transfer{background:#0f9f76}.action-btn.delete{background:#d94a4a}.action-btn.disabled{opacity:.45;cursor:not-allowed}.empty-state{padding:55px 20px;text-align:center;color:var(--st-muted)}.empty-state i{font-size:28px;color:var(--st-accent);margin-bottom:10px}.empty-state strong{display:block;color:var(--st-text);font-size:14px;margin-bottom:4px}.no-results{display:none;padding:35px 15px;text-align:center;color:var(--st-muted)}.no-results.show{display:block}.chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:20px}.stock-chart-card{padding:17px;min-height:350px}.chart-title{font-size:13px;font-weight:850}.chart-subtitle{font-size:10px;color:var(--st-muted);margin-top:3px}.chart-wrap{height:280px;margin-top:12px}
@media(max-width:1250px){.stock-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.filter-grid .search{grid-column:span 3}}
@media(max-width:850px){.stock-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-grid,.filter-grid.second{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-grid .search{grid-column:span 2}.chart-grid{grid-template-columns:1fr}}
@media(max-width:560px){.stock-header h1{font-size:18px}.stock-header p{font-size:11px}.stock-kpis{gap:9px}.stock-kpi{padding:13px;min-height:112px;border-radius:14px}.stock-kpi-value{font-size:19px}.stock-kpi-label{font-size:9px}.stock-panel{padding:12px;border-radius:15px}.filter-grid,.filter-grid.second{grid-template-columns:1fr}.filter-grid .search{grid-column:auto}.panel-head{align-items:flex-start;flex-direction:column}.table-top{padding:12px}.chart-grid{gap:12px}.stock-chart-card{padding:13px;min-height:315px}.chart-wrap{height:245px}}
.dark .filter-field input,.dark .filter-field select{color-scheme:dark}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important;animation:none!important}}
</style>
</head>
<body x-data="{ page: 'ecommerce', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode === true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="stock-page">
                    <div class="stock-header">
                        <div class="stock-heading">
                            <div class="stock-heading-icon"><i class="fa-solid fa-cubes-stacked"></i></div>
                            <div><h1>Stock Terminal</h1><p>Inventory intelligence across products, variants and locations</p></div>
                        </div>
                    </div>

                    <div class="stock-kpis">
                        <div class="stock-kpi"><div class="stock-kpi-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div class="stock-kpi-label">Total Stock Units</div><div class="stock-kpi-value" id="totalQuantity"><?= number_format($totalQuantity, 2) ?></div></div>
                        <div class="stock-kpi"><div class="stock-kpi-icon"><i class="fa-solid fa-naira-sign"></i></div><div class="stock-kpi-label">Stock Cost Value</div><div class="stock-kpi-value" id="totalStockValue"><?= number_format($totalStockValue, 2) ?></div></div>
                        <div class="stock-kpi"><div class="stock-kpi-icon"><i class="fa-solid fa-box-open"></i></div><div class="stock-kpi-label">Products Stocked</div><div class="stock-kpi-value" id="totalProducts"><?= number_format($totalProducts) ?></div></div>
                        <div class="stock-kpi"><div class="stock-kpi-icon"><i class="fa-solid fa-layer-group"></i></div><div class="stock-kpi-label">Categories</div><div class="stock-kpi-value" id="totalCategories"><?= number_format($totalCategories) ?></div></div>
                        <div class="stock-kpi"><div class="stock-kpi-icon"><i class="fa-solid fa-chart-line"></i></div><div class="stock-kpi-label">Potential Stock Profit</div><div class="stock-kpi-value profit" id="totalStockProfit"><?= number_format($totalStockProfit, 2) ?></div></div>
                    </div>

                    <section class="stock-panel">
                        <div class="panel-head"><div class="panel-title"><i class="fa-solid fa-sliders"></i> Inventory Filters</div><div class="muted-small" id="stockFilterCount">Showing <?= count($stocks) ?> of <?= count($stocks) ?> records</div></div>
                        <div class="filter-grid">
                            <div class="filter-field search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="searchInput" placeholder="Search product, variant, SKU or barcode..." autocomplete="off"></div>
                            <div class="filter-field"><i class="fa-regular fa-calendar"></i><input type="date" id="dateFilter" aria-label="Stock date"></div>
                            <div class="filter-field"><i class="fa-solid fa-location-dot"></i><select id="locationFilter"><option value="">All Locations</option><?php foreach ($locations as $loc): ?><option value="<?= (int)$loc['id'] ?>"><?= stock_h($loc['name']) ?></option><?php endforeach; ?></select></div>
                            <div class="filter-field"><i class="fa-solid fa-arrow-down-1-9"></i><input type="number" id="minPrice" placeholder="Min cost price" min="0" step="any"></div>
                            <div class="filter-field"><i class="fa-solid fa-arrow-up-1-9"></i><input type="number" id="maxPrice" placeholder="Max cost price" min="0" step="any"></div>
                            <div class="filter-field"><i class="fa-solid fa-truck-field"></i><select id="supplierFilter"><option value="">All Suppliers</option><option value="Production">Production</option><?php foreach ($vendors as $vendor): ?><option value="<?= stock_h($vendor) ?>"><?= stock_h($vendor) ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="filter-grid second">
                            <div class="filter-field"><i class="fa-solid fa-signal"></i><select id="stock_level"><option value="">All Stock Levels</option><option value="low">Low Stock</option><option value="zero">Out of Stock</option><option value="above">In Stock</option></select></div>
                            <div class="filter-field"><i class="fa-solid fa-barcode"></i><select id="trackingFilter"><option value="">All Tracking Types</option><option value="imei">IMEI</option><option value="serial">Serial Number</option><option value="vin">VIN</option><option value="none">Not Tracked</option></select></div>
                            <div class="filter-field"><i class="fa-solid fa-cubes"></i><select id="itemTypeFilter"><option value="">Products & Variants</option><option value="product">Products without variants</option><option value="variant">Product variants</option></select></div>
                            <button type="button" id="clearStockFilters" class="clear-filter"><i class="fa-solid fa-rotate-left"></i> Reset Filters</button>
                        </div>
                    </section>

                    <section class="stock-panel stock-table-card">
                        <div class="table-top"><div class="panel-title"><i class="fa-solid fa-table-list"></i> Current Inventory</div><div class="muted-small">Swipe horizontally to view all columns</div></div>
                        <div class="table-scroll" id="bottom-scroll">
                            <table class="stock-table">
                                <thead><tr>
                                    <th>S/N</th><th>Product / Variant</th><th>SKU</th><th>Barcode</th><th>Stock History</th><th>Unit</th><th>Stock Type</th><th>Vendor</th><th>Quantity</th><th>Location</th><th>Cost Price</th><th>Selling Price</th><th>Stock Cost Value</th><th>Potential Profit</th><th>Tracking / Identifiers</th><th>Availability</th><th>Last Updated</th><th>Actions</th>
                                </tr></thead>
                                <tbody id="stockTableBody">
                                <?php if (!$stocks): ?>
                                    <tr><td colspan="18" class="empty-state"><i class="fa-solid fa-box-open"></i><strong>No inventory records yet</strong>Stock entries and product variants will appear here once inventory is received.</td></tr>
                                <?php else: ?>
                                <?php $rowNumber = 0; foreach ($stocks as $row):
                                    $rowNumber++;
                                    $qty = (float)$row['quantity'];
                                    $cost = (float)$row['cost_price'];
                                    $selling = (float)$row['selling_price'];
                                    $costValue = $qty * $cost;
                                    $profitValue = $qty * ($selling - $cost);
                                    $lastIn = (float)$row['last_stock_in_quantity'];
                                    $level = $qty <= 0 ? 'zero' : (($lastIn > 0 && $qty < ($lastIn * .30)) ? 'low' : 'above');
                                    $isVariant = !empty($row['variant_id']);
                                    $trackingType = strtolower((string)$row['tracking_type']);
                                    $stockDate = substr((string)$row['created_at'], 0, 10);
                                    $searchText = trim($row['product_name'].' '.$row['variant_name'].' '.$row['sku'].' '.$row['barcode']);
                                ?>
                                    <tr data-product-id="<?= (int)$row['product_id'] ?>"
                                        data-variant-id="<?= $isVariant ? (int)$row['variant_id'] : '' ?>"
                                        data-item-type="<?= $isVariant ? 'variant' : 'product' ?>"
                                        data-search="<?= stock_h(strtolower($searchText)) ?>"
                                        data-supplier="<?= stock_h(strtolower((string)($row['supplier_name'] ?? ''))) ?>"
                                        data-location-id="<?= (int)($row['location_id'] ?? 0) ?>"
                                        data-cost-price="<?= stock_h($cost) ?>"
                                        data-quantity="<?= stock_h($qty) ?>"
                                        data-cost-value="<?= stock_h($costValue) ?>"
                                        data-profit-value="<?= stock_h($profitValue) ?>"
                                        data-category="<?= stock_h($row['category_name']) ?>"
                                        data-stock-level="<?= stock_h($level) ?>"
                                        data-tracking="<?= stock_h($trackingType ?: 'none') ?>"
                                        data-date="<?= stock_h($stockDate) ?>">
                                        <td><?= $rowNumber ?></td>
                                        <td><div class="product-cell"><div class="product-icon"><i class="fa-solid <?= $isVariant ? 'fa-tags' : 'fa-box' ?>"></i></div><div><div class="product-name"><?= stock_h($row['product_name']) ?></div><?php if ($isVariant): ?><div class="variant-name"><?= stock_h($row['variant_name']) ?></div><?php endif; ?></div></div></td>
                                        <td class="sku"><?= stock_h($row['sku'] ?: '—') ?></td>
                                        <td class="sku"><?= stock_h($row['barcode'] ?: '—') ?></td>
                                        <td><div class="action-stack"><?php if (!empty($row['stock_id'])): ?><form method="POST" action="stock-history.php" class="action-form" onsubmit="return confirm('View stock history for this inventory record?');"><input type="hidden" name="stock_id" value="<?= (int)$row['stock_id'] ?>"><input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>"><input type="hidden" name="variant_id" value="<?= $isVariant ? (int)$row['variant_id'] : '' ?>"><button type="submit" class="action-btn history"><i class="fa-solid fa-clock-rotate-left"></i> History</button></form><?php else: ?><span class="muted-small">No history yet</span><?php endif; ?></div></td>
                                        <td><?= stock_h($row['unit_name'] ?: '—') ?></td>
                                        <td><?= stock_h($row['stock_type'] ?: '—') ?></td>
                                        <td><?= stock_h($row['supplier_name'] ?: '—') ?></td>
                                        <td class="qty"><?= number_format($qty, 2) ?></td>
                                        <td><?= stock_h($row['location_name']) ?></td>
                                        <td class="money"><?= number_format($cost, 2) ?></td>
                                        <td class="money"><?= number_format($selling, 2) ?></td>
                                        <td class="money blue"><?= number_format($costValue, 2) ?></td>
                                        <td class="money <?= $profitValue >= 0 ? 'green' : 'red' ?>"><?= number_format($profitValue, 2) ?></td>
                                        <td><div class="tracking"><span class="tracking-type"><?= $trackingType === 'none' ? 'Not tracked' : stock_h(strtoupper($trackingType)) ?></span><span class="tracking-count"><?= (int)$row['identifier_count'] ?> in-stock identifier(s)</span><?php if (!empty($row['identifiers'])): ?><span class="identifier-list" title="<?= stock_h($row['identifiers']) ?>"><?= stock_h($row['identifiers']) ?></span><?php endif; ?></div></td>
                                        <td><?php if ($level === 'zero'): ?><span class="status zero">Out of Stock</span><?php elseif ($level === 'low'): ?><span class="status low">Low Stock</span><?php else: ?><span class="status ok">In Stock</span><?php endif; ?></td>
                                        <td><?= stock_h($row['created_at'] ?: '—') ?></td>
                                        <td><div class="action-stack">
                                            <?php if (!empty($row['stock_id'])): ?>
                                                <?php if (($row['supplier'] ?? '') === 'Production'): ?>
                                                    <form method="POST" action="reproduce.php" class="action-form" onsubmit="return confirm('Reproduce this product?');"><input type="hidden" name="stock_id" value="<?= (int)$row['stock_id'] ?>"><input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>"><input type="hidden" name="variant_id" value="<?= $isVariant ? (int)$row['variant_id'] : '' ?>"><button class="action-btn produce" type="submit"><i class="fa-solid fa-arrows-rotate"></i> Produce</button></form>
                                                <?php else: ?>
                                                    <form method="POST" action="restock.php" class="action-form" onsubmit="return confirm('Continue to restock this inventory item?');"><input type="hidden" name="stock_id" value="<?= (int)$row['stock_id'] ?>"><input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>"><input type="hidden" name="variant_id" value="<?= $isVariant ? (int)$row['variant_id'] : '' ?>"><input type="hidden" name="location_id" value="<?= (int)($row['location_id'] ?? 0) ?>"><button class="action-btn restock" type="submit"><i class="fa-solid fa-plus"></i> Restock</button></form>
                                                <?php endif; ?>
                                                <form method="POST" action="transfer-stock.php" class="action-form" onsubmit="return confirm('Transfer this stock to another location?');"><input type="hidden" name="stock_id" value="<?= (int)$row['stock_id'] ?>"><input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>"><input type="hidden" name="variant_id" value="<?= $isVariant ? (int)$row['variant_id'] : '' ?>"><input type="hidden" name="location_id" value="<?= (int)($row['location_id'] ?? 0) ?>"><button class="action-btn transfer" type="submit"><i class="fa-solid fa-right-left"></i> Transfer</button></form>
                                                <form method="POST" action="stock_delete.php" class="action-form" onsubmit="return confirm('Delete this stock record? This may affect inventory history.');"><input type="hidden" name="stock_id" value="<?= (int)$row['stock_id'] ?>"><input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>"><input type="hidden" name="variant_id" value="<?= $isVariant ? (int)$row['variant_id'] : '' ?>"><input type="hidden" name="location_id" value="<?= (int)($row['location_id'] ?? 0) ?>"><button class="action-btn delete" type="submit"><i class="fa-solid fa-trash-can"></i></button></form>
                                            <?php else: ?>
                                                <a class="action-btn restock" href="stock-in.php?product_id=<?= (int)$row['product_id'] ?><?= $isVariant ? '&amp;variant_id=' . (int)$row['variant_id'] : '' ?>"><i class="fa-solid fa-boxes-stacked"></i> Stock In</a>
                                            <?php endif; ?>
                                        </div></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                            <div id="stockNoResults" class="no-results"><i class="fa-solid fa-filter-circle-xmark"></i><br><strong>No matching inventory records</strong><br>Adjust or reset your filters.</div>
                        </div>
                    </section>

                    <div class="chart-grid">
                        <section class="stock-chart-card"><div class="chart-title">Stock Distribution</div><div class="chart-subtitle">Quantity by product category, including variants</div><div class="chart-wrap"><canvas id="categoryChart"></canvas></div></section>
                        <section class="stock-chart-card"><div class="chart-title">Stock Receipts</div><div class="chart-subtitle">Quantity received over the last 30 days</div><div class="chart-wrap"><canvas id="stockTrendChart"></canvas></div></section>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    const money = n => Number(n || 0).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});
    const whole = n => Number(n || 0).toLocaleString('en-NG',{maximumFractionDigits:2});
    const chartDark = document.body.classList.contains('dark');
    const textColor = chartDark ? '#9aa8bc' : '#718096';
    const gridColor = chartDark ? 'rgba(148,163,184,.10)' : 'rgba(100,116,139,.12)';

    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas && window.Chart) {
        new Chart(categoryCanvas,{type:'doughnut',data:{labels:<?= json_encode($categoriesLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>,datasets:[{data:<?= json_encode($categoriesData) ?>,backgroundColor:['#5b5ce2','#8b5cf6','#06b6d4','#0f9f76','#e5a12b','#dc4c4c','#2875cf','#db62a5'],borderWidth:0,hoverOffset:7}]},options:{responsive:true,maintainAspectRatio:false,cutout:'66%',plugins:{legend:{position:'bottom',labels:{color:textColor,usePointStyle:true,pointStyle:'circle',padding:13,font:{size:10}}}}}});
    }
    const trendCanvas = document.getElementById('stockTrendChart');
    if (trendCanvas && window.Chart) {
        new Chart(trendCanvas,{type:'line',data:{labels:<?= json_encode($trendLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>,datasets:[{label:'Quantity received',data:<?= json_encode($trendData) ?>,fill:true,backgroundColor:'rgba(91,92,226,.10)',borderColor:'#5b5ce2',borderWidth:2,pointRadius:3,tension:.35}]},options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{display:false}},scales:{x:{ticks:{color:textColor,maxTicksLimit:7,font:{size:9}},grid:{display:false}},y:{beginAtZero:true,ticks:{color:textColor,font:{size:9}},grid:{color:gridColor}}}}});
    }

    const body = document.getElementById('stockTableBody');
    if (!body) return;
    const rows = Array.from(body.querySelectorAll('tr[data-product-id]'));
    const fields = {
        search:document.getElementById('searchInput'), date:document.getElementById('dateFilter'),
        location:document.getElementById('locationFilter'), min:document.getElementById('minPrice'),
        max:document.getElementById('maxPrice'), supplier:document.getElementById('supplierFilter'),
        level:document.getElementById('stock_level'), tracking:document.getElementById('trackingFilter'),
        itemType:document.getElementById('itemTypeFilter')
    };
    const count = document.getElementById('stockFilterCount');
    const noResults = document.getElementById('stockNoResults');

    function updateKpis(visible){
        let quantity=0,value=0,profit=0;const products=new Set(),categories=new Set();
        visible.forEach(row=>{
            quantity += Number(row.dataset.quantity||0);
            value += Number(row.dataset.costValue||0);
            profit += Number(row.dataset.profitValue||0);
            products.add(row.dataset.productId);
            if(row.dataset.category) categories.add(row.dataset.category);
        });
        document.getElementById('totalQuantity').textContent=whole(quantity);
        document.getElementById('totalStockValue').textContent=money(value);
        document.getElementById('totalProducts').textContent=whole(products.size);
        document.getElementById('totalCategories').textContent=whole(categories.size);
        document.getElementById('totalStockProfit').textContent=money(profit);
    }
    function applyFilters(){
        const search=(fields.search.value||'').trim().toLowerCase();
        const date=fields.date.value;
        const location=fields.location.value;
        const min=fields.min.value===''?-Infinity:Number(fields.min.value);
        const max=fields.max.value===''?Infinity:Number(fields.max.value);
        const supplier=(fields.supplier.value||'').trim().toLowerCase();
        const level=fields.level.value;
        const tracking=fields.tracking.value;
        const itemType=fields.itemType.value;
        const visible=[];
        rows.forEach(row=>{
            const matches = (!search || (row.dataset.search||'').includes(search))
                && (!date || row.dataset.date===date)
                && (!location || row.dataset.locationId===location)
                && (Number(row.dataset.costPrice||0)>=min && Number(row.dataset.costPrice||0)<=max)
                && (!supplier || (row.dataset.supplier||'')===supplier)
                && (!level || row.dataset.stockLevel===level)
                && (!tracking || row.dataset.tracking===tracking)
                && (!itemType || row.dataset.itemType===itemType);
            row.hidden=!matches;
            if(matches) visible.push(row);
        });
        updateKpis(visible);
        if(count) count.textContent=`Showing ${visible.length} of ${rows.length} records`;
        if(noResults) noResults.classList.toggle('show',rows.length>0 && visible.length===0);
    }
    Object.values(fields).forEach(field=>{if(field) field.addEventListener(field.tagName==='INPUT' && field.type!=='date' ? 'input':'change',applyFilters);});
    document.getElementById('clearStockFilters')?.addEventListener('click',()=>{Object.values(fields).forEach(field=>{if(field) field.value='';});applyFilters();});
    applyFilters();
})();
</script>
</body>
</html>
