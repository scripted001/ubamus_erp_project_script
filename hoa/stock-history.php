<?php
/**
 * Stock intelligence / stock-in history page.
 * Database-aligned with stocks, stock_in, stock_movements, product_variants,
 * product_variant_stocks and the company/location schema supplied by the user.
 */
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);
$stock_id = filter_input(INPUT_POST, 'stock_id', FILTER_VALIDATE_INT);
$stock_id = $stock_id ? (int)$stock_id : 0;
$variant_id = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
$variant_id = $variant_id ? (int)$variant_id : 0;

function si_h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function si_money($v): string { return number_format((float)$v, 2); }

if ($company_id <= 0 || $stock_id <= 0) {
    http_response_code(400);
    exit('Invalid stock request.');
}

// Resolve the parent stock row first; this enforces company ownership and
// supplies the product/location context for both legacy and variant stock.
$baseStmt = $pdo->prepare("SELECT s.id, s.product_id, s.company_id, s.location_id, s.quantity AS legacy_quantity,
                                  s.supplier AS stock_supplier, s.created_at AS stock_created_at,
                                  p.name, p.barcode, p.category_id, p.has_variants,
                                  p.cost_price AS product_cost_price, p.selling_price AS product_selling_price,
                                  p.identifier_tracking, l.name AS location_name
                           FROM stocks s
                           INNER JOIN products p ON p.id = s.product_id AND p.company_id = s.company_id
                           LEFT JOIN locations l ON l.id = s.location_id AND l.company_id = s.company_id
                           WHERE s.id = ? AND s.company_id = ? LIMIT 1");
$baseStmt->execute([$stock_id, $company_id]);
$base = $baseStmt->fetch(PDO::FETCH_ASSOC);
if (!$base) { http_response_code(404); exit('Stock record not found or you do not have access to it.'); }
$product_id = (int)$base['product_id'];
$location_id = (int)$base['location_id'];
$has_variants = (int)$base['has_variants'] === 1;

$selectedVariant = null;
if ($variant_id > 0) {
    $variantStmt = $pdo->prepare("SELECT id, variant_name, sku, barcode, cost_price, selling_price, tracking_policy
                                  FROM product_variants
                                  WHERE id = ? AND product_id = ? AND company_id = ? AND is_active = 1 LIMIT 1");
    $variantStmt->execute([$variant_id, $product_id, $company_id]);
    $selectedVariant = $variantStmt->fetch(PDO::FETCH_ASSOC);
    if (!$selectedVariant) { http_response_code(404); exit('Variant not found or you do not have access to it.'); }
}

$costPrice = $selectedVariant && $selectedVariant['cost_price'] !== null
    ? (float)$selectedVariant['cost_price'] : (float)$base['product_cost_price'];
$sellingPrice = $selectedVariant && $selectedVariant['selling_price'] !== null
    ? (float)$selectedVariant['selling_price'] : (float)$base['product_selling_price'];

// Current quantity: variant-specific when requested; otherwise sum all active
// variant balances for a variant parent, or use the legacy stocks quantity.
if ($selectedVariant) {
    $qtyStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM product_variant_stocks
                              WHERE company_id = ? AND product_id = ? AND variant_id = ? AND location_id = ?");
    $qtyStmt->execute([$company_id, $product_id, $variant_id, $location_id]);
} elseif ($has_variants) {
    $qtyStmt = $pdo->prepare("SELECT COALESCE(SUM(pvs.quantity),0)
                              FROM product_variant_stocks pvs
                              INNER JOIN product_variants pv ON pv.id = pvs.variant_id
                                  AND pv.product_id = pvs.product_id AND pv.company_id = pvs.company_id
                              WHERE pvs.company_id = ? AND pvs.product_id = ? AND pvs.location_id = ?
                                AND pv.is_active = 1");
    $qtyStmt->execute([$company_id, $product_id, $location_id]);
} else {
    $qtyStmt = $pdo->prepare("SELECT COALESCE(quantity,0) FROM stocks WHERE id = ? AND company_id = ?");
    $qtyStmt->execute([$stock_id, $company_id]);
}
$currentQuantity = (float)$qtyStmt->fetchColumn();

$whereVariant = $variant_id > 0 ? ' AND si.variant_id = ?' : '';
$stockInParams = [$company_id, $stock_id];
if ($variant_id > 0) { $stockInParams[] = $variant_id; }

$kpiStmt = $pdo->prepare("SELECT COUNT(*) AS record_count, COALESCE(SUM(si.quantity),0) AS units_received,
                                 MAX(si.created_at) AS latest_received
                          FROM stock_in si
                          WHERE si.company_id = ? AND si.stock_id = ? {$whereVariant}");
$kpiStmt->execute($stockInParams);
$stockInKpi = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$movementSql = "SELECT COUNT(*) FROM stock_movements sm WHERE sm.company_id = ? AND sm.stock_id = ?";
$movementParams = [$company_id, $stock_id];
if ($variant_id > 0) { $movementSql .= ' AND sm.variant_id = ?'; $movementParams[] = $variant_id; }
$movementStmt = $pdo->prepare($movementSql);
$movementStmt->execute($movementParams);
$movementCount = (int)$movementStmt->fetchColumn();

$stockInValue = (float)($stockInKpi['units_received'] ?? 0) * $costPrice;
$currentCostValue = $currentQuantity * $costPrice;
$currentSalesValue = $currentQuantity * $sellingPrice;
$potentialProfit = $currentQuantity * ($sellingPrice - $costPrice);
$margin = $sellingPrice > 0 ? (($sellingPrice - $costPrice) / $sellingPrice) * 100 : 0;

// Transaction ledger. Variant-specific pricing overrides parent pricing;
// supplier names are resolved only within the same company.
$rowsSql = "SELECT si.id, si.stock_id, si.variant_id, si.location_id, si.transfer_ID,
                   si.quantity, si.type, si.created_at, si.recorded_by, si.supplier,
                   p.name AS product_name, p.barcode AS product_barcode,
                   p.cost_price AS parent_cost, p.selling_price AS parent_selling,
                   pv.variant_name, pv.sku AS variant_sku, pv.barcode AS variant_barcode,
                   pv.cost_price AS variant_cost, pv.selling_price AS variant_selling,
                   u.email AS recorder_email, u.username AS recorder_username,
                   l.name AS location_name,
                   v.business_name AS vendor_name
            FROM stock_in si
            INNER JOIN products p ON p.id = si.product_id AND p.company_id = si.company_id
            LEFT JOIN product_variants pv ON pv.id = si.variant_id AND pv.product_id = si.product_id AND pv.company_id = si.company_id
            LEFT JOIN users u ON u.id = si.recorded_by AND u.company_id = si.company_id
            LEFT JOIN locations l ON l.id = si.location_id AND l.company_id = si.company_id
            LEFT JOIN vendors v ON v.id = CAST(si.supplier AS UNSIGNED) AND v.company_id = si.company_id
            WHERE si.company_id = ? AND si.stock_id = ?";
if ($variant_id > 0) { $rowsSql .= ' AND si.variant_id = ?'; }
$rowsSql .= ' ORDER BY si.id DESC';
$rowsStmt = $pdo->prepare($rowsSql);
$rowsStmt->execute($stockInParams);
$stockRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

// Category chart uses one balance source per product: variant balances for
// variant-enabled products, legacy stocks for non-variant products.
$categorySql = "SELECT pc.name AS category, COALESCE(SUM(balance.qty),0) AS total
                FROM product_categories pc
                LEFT JOIN (
                    SELECT p.category_id, p.company_id, SUM(pvs.quantity) AS qty
                    FROM products p
                    INNER JOIN product_variants pv ON pv.product_id = p.id AND pv.company_id = p.company_id AND pv.is_active = 1
                    INNER JOIN product_variant_stocks pvs ON pvs.product_id = p.id AND pvs.company_id = p.company_id AND pvs.variant_id = pv.id
                    WHERE p.company_id = ? AND p.has_variants = 1
                    GROUP BY p.category_id, p.company_id
                    UNION ALL
                    SELECT p.category_id, p.company_id, SUM(s.quantity) AS qty
                    FROM products p
                    INNER JOIN stocks s ON s.product_id = p.id AND s.company_id = p.company_id
                    WHERE p.company_id = ? AND (p.has_variants = 0 OR p.has_variants IS NULL)
                    GROUP BY p.category_id, p.company_id
                ) balance ON balance.category_id = pc.id AND balance.company_id = pc.company_id
                WHERE pc.company_id = ?
                GROUP BY pc.id, pc.name
                ORDER BY total DESC, pc.name ASC";
$categoryStmt = $pdo->prepare($categorySql);
$categoryStmt->execute([$company_id, $company_id, $company_id]);
$categoryRows = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Recent intake trend for this stock item (or selected variant), not a
// misleading company-wide sum of current stock snapshots.
$trendSql = "SELECT DATE(si.created_at) AS day, COALESCE(SUM(si.quantity),0) AS units
             FROM stock_in si
             WHERE si.company_id = ? AND si.stock_id = ?
               AND si.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$trendParams = [$company_id, $stock_id];
if ($variant_id > 0) { $trendSql .= ' AND si.variant_id = ?'; $trendParams[] = $variant_id; }
$trendSql .= ' GROUP BY DATE(si.created_at) ORDER BY DATE(si.created_at) ASC';
$trendStmt = $pdo->prepare($trendSql);
$trendStmt->execute($trendParams);
$trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$locationsStmt = $pdo->prepare('SELECT id, name FROM locations WHERE company_id = ? ORDER BY name ASC');
$locationsStmt->execute([$company_id]);
$locations = $locationsStmt->fetchAll(PDO::FETCH_ASSOC);

$currencyLabel = '₦';
$displayName = (string)$base['name'] . ($selectedVariant ? ' — ' . $selectedVariant['variant_name'] : '');
$displayBarcode = $selectedVariant['variant_barcode'] ?? $selectedVariant['sku'] ?? $base['barcode'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= si_h($company_name ?? 'Company') ?> | Stock Intelligence</title>
<link rel="stylesheet" href="../fontawesome/css/all.css"><link href="style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--si-brand:#4f46e5;--si-ink:#172033;--si-muted:#64748b;--si-line:#e8edf5;--si-bg:#f8fafc;--si-green:#059669;--si-red:#dc2626;--si-shadow:0 14px 40px rgba(15,23,42,.06)}
.stock-intelligence{max-width:1600px;margin:auto}.si-hero{padding:26px;margin-bottom:20px;border:1px solid #e0e7ff;border-radius:22px;background:radial-gradient(circle at 88% 5%,rgba(99,102,241,.13),transparent 32%),linear-gradient(135deg,#fff,#f8faff);box-shadow:var(--si-shadow)}
.si-hero-top{display:flex;justify-content:space-between;align-items:flex-start;gap:18px}.si-eyebrow{font-size:10px;letter-spacing:.15em;font-weight:800;color:var(--si-brand);text-transform:uppercase}.si-title{margin:8px 0;color:var(--si-ink);font-size:clamp(24px,3vw,36px);font-weight:850;letter-spacing:-.04em}.si-desc{max-width:720px;color:var(--si-muted);font-size:13px;line-height:1.65}.si-pill{padding:10px 14px;border-radius:12px;background:#eef2ff;color:#4338ca;font-size:12px;font-weight:800;white-space:nowrap}
.si-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.si-card,.si-panel{background:#fff;border:1px solid var(--si-line);border-radius:18px;box-shadow:var(--si-shadow)}.si-card{padding:17px;min-width:0}.si-kpi-head{display:flex;justify-content:space-between;align-items:center;gap:8px;color:var(--si-muted);font-size:11px;font-weight:750}.si-icon{width:34px;height:34px;display:grid;place-items:center;border-radius:11px;background:#eef2ff;color:var(--si-brand)}.si-kpi-value{margin-top:12px;color:var(--si-ink);font-size:clamp(20px,2vw,28px);font-weight:850;letter-spacing:-.035em;overflow-wrap:anywhere}.si-kpi-sub{margin-top:5px;color:#94a3b8;font-size:10px}.si-card.green .si-icon{background:#ecfdf5;color:var(--si-green)}.si-card.green .si-kpi-value{color:var(--si-green)}
.si-panel{overflow:hidden;margin-bottom:18px}.si-panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:17px 19px;border-bottom:1px solid var(--si-line)}.si-panel-title{margin:0;color:var(--si-ink);font-size:14px;font-weight:850}.si-muted{color:#94a3b8;font-size:11px}.si-filters{display:grid;grid-template-columns:minmax(220px,2fr) repeat(3,minmax(130px,1fr));gap:10px;padding:16px}.si-filter{height:42px;width:100%;min-width:0;padding:0 12px;border:1px solid #e2e8f0;border-radius:11px;background:#fbfdff;color:#334155;font-size:12px;outline:none}.si-filter:focus{border-color:#818cf8;box-shadow:0 0 0 3px #eef2ff}.si-table-wrap{overflow-x:auto}.si-table{width:100%;min-width:1180px;border-collapse:collapse}.si-table th{padding:12px 13px;background:#f8fafc;color:#64748b;font-size:9px;letter-spacing:.07em;text-transform:uppercase;text-align:left;white-space:nowrap}.si-table td{padding:13px;border-top:1px solid #f1f5f9;color:#475569;font-size:11px;white-space:nowrap}.si-table tbody tr:hover{background:#fafbff}.si-product{font-weight:800;color:#1e293b!important}.si-subline{display:block;margin-top:3px;color:#94a3b8;font-size:10px}.si-type{display:inline-block;padding:5px 8px;border-radius:20px;background:#eef2ff;color:#4338ca;font-size:9px;font-weight:800}.si-positive{color:var(--si-green)!important;font-weight:800}.si-negative{color:var(--si-red)!important;font-weight:800}.si-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.si-chart{padding:18px}.si-chart-area{height:290px;position:relative}.si-empty{padding:30px;text-align:center;color:#94a3b8;font-size:12px}.si-footnote{padding:0 17px 14px;color:#94a3b8;font-size:10px}
@media(max-width:1100px){.si-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.si-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.si-charts{grid-template-columns:1fr}}
@media(max-width:640px){.si-hero{padding:19px}.si-hero-top{flex-direction:column}.si-pill{white-space:normal}.si-kpis{gap:9px}.si-card{padding:13px}.si-kpi-value{font-size:20px}.si-filters{grid-template-columns:1fr}.si-panel-head{align-items:flex-start;flex-direction:column}.si-chart-area{height:250px}}
</style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}" x-init="darkMode=JSON.parse(localStorage.getItem('darkMode')||'false');$watch('darkMode',value=>localStorage.setItem('darkMode',JSON.stringify(value)))" :class="{'dark bg-gray-900':darkMode===true}">
<div class="flex h-screen overflow-hidden">
<?php include('sidebar.php'); ?>
<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"><main>
<?php include('header.php'); ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6"><div class="stock-intelligence">
<section class="si-hero"><div class="si-hero-top"><div><div class="si-eyebrow"><i class="fa-solid fa-wave-square"></i> Inventory performance overview</div><h1 class="si-title">Stock Intelligence</h1><p class="si-desc">A focused view of receipts, current stock value, margin and movement history. Balances are read from the correct legacy or variant stock table.</p><div class="si-muted">Product: <strong><?= si_h($displayName) ?></strong> · Barcode/SKU: <?= si_h($displayBarcode ?: '—') ?> · Location: <?= si_h($base['location_name'] ?: '—') ?></div></div><div class="si-pill"><i class="fa-solid fa-boxes-stacked"></i> Stock #<?= (int)$stock_id ?><?= $selectedVariant ? ' · Variant #'.(int)$variant_id : '' ?></div></div></section>
<section class="si-kpis">
<div class="si-card"><div class="si-kpi-head">Current quantity <span class="si-icon"><i class="fa-solid fa-cubes"></i></span></div><div class="si-kpi-value"><?= number_format($currentQuantity,2) ?></div><div class="si-kpi-sub">Current balance at <?= si_h($base['location_name'] ?: 'this location') ?></div></div>
<div class="si-card"><div class="si-kpi-head">Unit selling price <span class="si-icon"><i class="fa-solid fa-tag"></i></span></div><div class="si-kpi-value"><?= $currencyLabel.si_money($sellingPrice) ?></div><div class="si-kpi-sub">Variant price where one is defined; otherwise parent price</div></div>
<div class="si-card"><div class="si-kpi-head">Current sales value <span class="si-icon"><i class="fa-solid fa-chart-line"></i></span></div><div class="si-kpi-value"><?= $currencyLabel.si_money($currentSalesValue) ?></div><div class="si-kpi-sub">Current quantity × selling price</div></div>
<div class="si-card"><div class="si-kpi-head">Current capital value <span class="si-icon"><i class="fa-solid fa-wallet"></i></span></div><div class="si-kpi-value"><?= $currencyLabel.si_money($currentCostValue) ?></div><div class="si-kpi-sub">Current quantity × cost price</div></div>
<div class="si-card green"><div class="si-kpi-head">Potential gross profit <span class="si-icon"><i class="fa-solid fa-arrow-trend-up"></i></span></div><div class="si-kpi-value"><?= $currencyLabel.si_money($potentialProfit) ?></div><div class="si-kpi-sub">Estimated margin <?= number_format($margin,1) ?>%</div></div>
<div class="si-card"><div class="si-kpi-head">Stock-in records <span class="si-icon"><i class="fa-solid fa-arrow-down"></i></span></div><div class="si-kpi-value"><?= number_format((int)($stockInKpi['record_count'] ?? 0)) ?></div><div class="si-kpi-sub"><?= number_format((float)($stockInKpi['units_received'] ?? 0),2) ?> units received in recorded receipts</div></div>
<div class="si-card"><div class="si-kpi-head">Units received value <span class="si-icon"><i class="fa-solid fa-receipt"></i></span></div><div class="si-kpi-value"><?= $currencyLabel.si_money($stockInValue) ?></div><div class="si-kpi-sub">Recorded received units × current unit cost</div></div>
<div class="si-card"><div class="si-kpi-head">Stock movements <span class="si-icon"><i class="fa-solid fa-right-left"></i></span></div><div class="si-kpi-value"><?= number_format($movementCount) ?></div><div class="si-kpi-sub">Movement records linked to this stock<?= $selectedVariant ? ' and variant' : '' ?></div></div>
</section>
<section class="si-panel"><div class="si-panel-head"><h2 class="si-panel-title"><i class="fa-solid fa-sliders"></i> Transaction filters</h2><span class="si-muted" id="siCount"><?= count($stockRows) ?> record(s)</span></div><div class="si-filters"><input class="si-filter" id="siSearch" type="search" placeholder="Search product, variant, SKU, supplier or transaction ID"><input class="si-filter" id="siDate" type="date" aria-label="Filter by date"><select class="si-filter" id="siLocation"><option value="">All locations</option><?php foreach($locations as $loc): ?><option value="<?= si_h($loc['name']) ?>" <?= (int)$loc['id']===$location_id?'selected':'' ?>><?= si_h($loc['name']) ?></option><?php endforeach; ?></select><select class="si-filter" id="siType"><option value="">All transaction types</option><?php $types=[]; foreach($stockRows as $r){$t=(string)$r['type'];if($t!==''&&!in_array($t,$types,true))$types[]=$t;} sort($types); foreach($types as $t): ?><option value="<?= si_h($t) ?>"><?= si_h($t) ?></option><?php endforeach; ?></select></div></section>
<section class="si-panel"><div class="si-panel-head"><h2 class="si-panel-title">Stock receipt ledger</h2><span class="si-muted">Newest records first · <?= count($stockRows) ?> total</span></div><div class="si-table-wrap"><table class="si-table"><thead><tr><th>#</th><th>Product / Variant</th><th>Barcode / SKU</th><th>Quantity</th><th>Type</th><th>Transaction ID</th><th>Supplier</th><th>Location</th><th>Unit cost</th><th>Unit selling</th><th>Capital value</th><th>Sales value</th><th>Gross profit</th><th>Recorded by</th><th>Date & time</th></tr></thead><tbody id="siRows">
<?php if (!$stockRows): ?><tr><td colspan="15" class="si-empty">No stock-in records were found for this stock item<?= $selectedVariant ? ' and variant' : '' ?>.</td></tr><?php else: $n=1; foreach($stockRows as $row):
 $q=(float)$row['quantity']; $c=$row['variant_cost']!==null?(float)$row['variant_cost']:(float)$row['parent_cost']; $sp=$row['variant_selling']!==null?(float)$row['variant_selling']:(float)$row['parent_selling']; $cap=$q*$c; $sales=$q*$sp; $profit=$sales-$cap;
 $supplierRaw=(string)$row['supplier']; $supplier=($supplierRaw==='General Merchant'||$supplierRaw==='Production')?$supplierRaw:($row['vendor_name']?:($supplierRaw!==''?'Vendor #'.$supplierRaw:'—'));
 $recordedBy=$row['recorder_username']?:($row['recorder_email']?:('User #'.(int)$row['recorded_by'])); $variantLabel=$row['variant_name']?:''; $barcode=$row['variant_barcode']?:($row['variant_sku']?:$row['product_barcode']);
?><tr data-search="<?= si_h(strtolower($row['product_name'].' '.$variantLabel.' '.$barcode.' '.$supplier.' '.$row['transfer_ID'].' '.$recordedBy)) ?>" data-date="<?= si_h(substr((string)$row['created_at'],0,10)) ?>" data-location="<?= si_h($row['location_name']) ?>" data-type="<?= si_h($row['type']) ?>">
<td><?= $n++ ?></td><td class="si-product"><?= si_h($row['product_name']) ?><?php if($variantLabel): ?><span class="si-subline"><?= si_h($variantLabel) ?></span><?php endif; ?></td><td><?= si_h($barcode?:'—') ?><?php if($row['variant_sku']): ?><span class="si-subline">SKU: <?= si_h($row['variant_sku']) ?></span><?php endif; ?></td><td><?= number_format($q,2) ?></td><td><span class="si-type"><?= si_h($row['type']) ?></span></td><td><?= si_h($row['transfer_ID']?:('SI-'.$row['id'])) ?></td><td><?= si_h($supplier) ?></td><td><?= si_h($row['location_name']?:'—') ?></td><td><?= $currencyLabel.si_money($c) ?></td><td><?= $currencyLabel.si_money($sp) ?></td><td><?= $currencyLabel.si_money($cap) ?></td><td><?= $currencyLabel.si_money($sales) ?></td><td class="<?= $profit>=0?'si-positive':'si-negative' ?>"><?= $currencyLabel.si_money($profit) ?></td><td><?= si_h($recordedBy) ?></td><td><?= si_h($row['created_at']) ?></td></tr><?php endforeach; endif; ?>
</tbody></table></div><div class="si-footnote">Financial values are estimates based on the current product/variant prices; historical prices are not stored on the stock-in record in the supplied schema.</div></section>
<section class="si-charts"><div class="si-panel si-chart"><div class="si-panel-head" style="padding:0 0 14px;border:0"><h3 class="si-panel-title">Company inventory by category</h3><span class="si-muted">Variant-aware balances</span></div><div class="si-chart-area"><canvas id="siCategory"></canvas></div></div><div class="si-panel si-chart"><div class="si-panel-head" style="padding:0 0 14px;border:0"><h3 class="si-panel-title">30-day stock-in trend</h3><span class="si-muted">This stock<?= $selectedVariant?' / variant':'' ?></span></div><div class="si-chart-area"><canvas id="siTrend"></canvas></div></div></section>
</div></div></main></div></div>
<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const rows=[...document.querySelectorAll('#siRows tr[data-search]')];
 const el=id=>document.getElementById(id);
 const filters=['siSearch','siDate','siLocation','siType'].map(el);
 function applyFilters(){const [search,date,location,type]=filters.map(x=>(x?.value||'').trim().toLowerCase());let shown=0;rows.forEach(row=>{const ok=(!search||row.dataset.search.includes(search))&&(!date||row.dataset.date===date)&&(!location||row.dataset.location.toLowerCase()===location)&&(!type||row.dataset.type.toLowerCase()===type);row.hidden=!ok;if(ok)shown++;});const count=el('siCount');if(count)count.textContent=`${shown} of ${rows.length} record(s)`;}
 filters.forEach(x=>x?.addEventListener(x.type==='select-one'?'change':'input',applyFilters));
 if(typeof Chart!=='undefined'){
  const cat=el('siCategory');if(cat)new Chart(cat,{type:'doughnut',data:{labels:<?= json_encode(array_column($categoryRows,'category'),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>,datasets:[{data:<?= json_encode(array_map('floatval',array_column($categoryRows,'total'))) ?>}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:16,font:{size:10}}}}}});
  const trend=el('siTrend');if(trend)new Chart(trend,{type:'line',data:{labels:<?= json_encode(array_column($trendRows,'day'),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>,datasets:[{label:'Units received',data:<?= json_encode(array_map('floatval',array_column($trendRows,'units'))) ?>,fill:true,tension:.35,borderWidth:2,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{maxTicksLimit:7}},y:{beginAtZero:true}}}});
 }
 applyFilters();
});
</script>
</body></html>
