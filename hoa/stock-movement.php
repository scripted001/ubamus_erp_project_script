<?php
/*
 * UBAMUS Stock Movement Ledger
 * Reads the stock_movements schema in ubamus (23).sql.
 * Transfer records are written by process-stock-transfer.php, including variant_id.
 */

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

$company_id = (int)($_SESSION['company_id'] ?? 0);
if ($company_id <= 0) {
    http_response_code(403);
    exit('Your session does not contain a valid company. Please sign in again.');
}

if (!function_exists('movement_h')) {
    function movement_h($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$dateFrom = trim((string)($_GET['from'] ?? ''));
$dateTo = trim((string)($_GET['to'] ?? ''));
$movementType = trim((string)($_GET['movement_type'] ?? ''));
$productId = trim((string)($_GET['product_id'] ?? ''));
$fromLocationId = trim((string)($_GET['from_location_id'] ?? ''));
$toLocationId = trim((string)($_GET['to_location_id'] ?? ''));
$locationId = trim((string)($_GET['location_id'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));

// Accept only valid date values; invalid query-string dates are ignored.
$dateCheck = static function (string $value): bool {
    if ($value === '') return true;
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
};
if (!$dateCheck($dateFrom)) $dateFrom = '';
if (!$dateCheck($dateTo)) $dateTo = '';
if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}
if ($productId !== '' && !ctype_digit($productId)) $productId = '';
if ($fromLocationId !== '' && !ctype_digit($fromLocationId)) $fromLocationId = '';
if ($toLocationId !== '' && !ctype_digit($toLocationId)) $toLocationId = '';
if ($locationId !== '' && !ctype_digit($locationId)) $locationId = '';

$where = ['sm.company_id = ?'];
$params = [$company_id];
if ($dateFrom !== '') {
    $where[] = 'sm.created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[] = 'sm.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
    $params[] = $dateTo . ' 00:00:00';
}
if ($movementType !== '') {
    $where[] = 'LOWER(TRIM(sm.movement_type)) = LOWER(TRIM(?))';
    $params[] = $movementType;
}
if ($productId !== '') {
    $where[] = 'sm.product_id = ?';
    $params[] = (int)$productId;
}
if ($fromLocationId !== '') {
    $where[] = 'sm.from_location_id = ?';
    $params[] = (int)$fromLocationId;
}
if ($toLocationId !== '') {
    $where[] = 'sm.to_location_id = ?';
    $params[] = (int)$toLocationId;
}
// Backward-compatible combined location filter used by older links.
if ($locationId !== '') {
    $where[] = '(sm.from_location_id = ? OR sm.to_location_id = ?)';
    $params[] = (int)$locationId;
    $params[] = (int)$locationId;
}
if ($search !== '') {
    $where[] = "(
        p.name LIKE ? OR p.barcode LIKE ? OR pv.variant_name LIKE ?
        OR sm.transfer_ID LIKE ? OR sm.reference LIKE ?
        OR CAST(sm.stock_id AS CHAR) LIKE ?
    )";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

$sql = "
    SELECT
        sm.id,
        sm.company_id,
        sm.product_id,
        sm.variant_id,
        sm.stock_id,
        sm.from_location_id,
        sm.to_location_id,
        sm.transfer_ID,
        sm.quantity,
        sm.movement_type,
        sm.reference,
        sm.created_by,
        sm.created_at,
        COALESCE(p.name, CONCAT('Product #', sm.product_id)) AS product_name,
        COALESCE(p.barcode, '') AS barcode,
        COALESCE(pv.variant_name, '') AS variant_name,
        COALESCE(fl.name, CASE WHEN sm.from_location_id IS NULL THEN '—' ELSE CONCAT('Location #', sm.from_location_id) END) AS from_location_name,
        COALESCE(tl.name, CASE WHEN sm.to_location_id IS NULL THEN '—' ELSE CONCAT('Location #', sm.to_location_id) END) AS to_location_name,
        COALESCE(u.username, CASE WHEN sm.created_by IS NULL THEN 'System' ELSE CONCAT('User #', sm.created_by) END) AS created_by_name
    FROM stock_movements sm
    LEFT JOIN products p
        ON p.id = sm.product_id AND p.company_id = sm.company_id
    LEFT JOIN product_variants pv
        ON pv.id = sm.variant_id
       AND pv.product_id = sm.product_id
       AND pv.company_id = sm.company_id
    LEFT JOIN locations fl
        ON fl.id = sm.from_location_id AND fl.company_id = sm.company_id
    LEFT JOIN locations tl
        ON tl.id = sm.to_location_id AND tl.company_id = sm.company_id
    LEFT JOIN users u
        ON u.id = sm.created_by AND u.company_id = sm.company_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY sm.created_at DESC, sm.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stock_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalMovements = count($stock_movements);
$totalQuantityMoved = 0.0;
$totalTransfers = 0;
$totalStockIn = 0.0;
$totalStockOut = 0.0;
foreach ($stock_movements as $movement) {
    $qty = (float)$movement['quantity'];
    $totalQuantityMoved += $qty;
    $type = strtolower(trim((string)$movement['movement_type']));
    if (in_array($type, ['transfer', 'stock transfer'], true)) $totalTransfers++;
    if (in_array($type, ['in', 'stock_in', 'stock in', 'stock-in'], true)) $totalStockIn += $qty;
    if (in_array($type, ['out', 'stock_out', 'stock out', 'stock-out'], true)) $totalStockOut += $qty;
}

$productsStmt = $pdo->prepare("
    SELECT DISTINCT p.id, p.name
    FROM products p
    INNER JOIN stock_movements sm
        ON sm.product_id = p.id AND sm.company_id = p.company_id
    WHERE p.company_id = ?
    ORDER BY p.name
");
$productsStmt->execute([$company_id]);
$movementProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

$locationsStmt = $pdo->prepare('SELECT id, name FROM locations WHERE company_id = ? ORDER BY name');
$locationsStmt->execute([$company_id]);
$movementLocations = $locationsStmt->fetchAll(PDO::FETCH_ASSOC);

$typesStmt = $pdo->prepare("SELECT DISTINCT movement_type FROM stock_movements WHERE company_id = ? AND movement_type IS NOT NULL AND movement_type <> '' ORDER BY movement_type");
$typesStmt->execute([$company_id]);
$movementTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

$filterQuery = $_GET;
unset($filterQuery['page']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= movement_h($company_name ?? 'Inventory') ?> | Stock Movements</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<style>
:root{--mv-bg:#080d18;--mv-card:#101827;--mv-border:rgba(148,163,184,.16);--mv-text:#f4f7fb;--mv-muted:#94a3b8;--mv-accent:#f59e0b;--mv-green:#22c55e;--mv-blue:#38bdf8}
.stock-movement-page{min-height:100%;padding:20px;background:radial-gradient(circle at 12% 0%,rgba(245,158,11,.08),transparent 28%),var(--mv-bg);color:var(--mv-text)}
.mv-wrap{max-width:1800px;margin:auto}.mv-hero,.mv-card{background:var(--mv-card);border:1px solid var(--mv-border);border-radius:18px;box-shadow:0 12px 35px rgba(0,0,0,.16)}.mv-hero{padding:22px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:16px}.mv-title{margin:0;font-size:clamp(21px,3vw,29px);font-weight:850}.mv-subtitle{margin:5px 0 0;color:var(--mv-muted);font-size:13px}.mv-live{white-space:nowrap;border-radius:999px;padding:8px 12px;background:rgba(34,197,94,.09);color:#86efac;font-size:11px;font-weight:800}.mv-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:18px}.mv-kpi{padding:17px;background:var(--mv-card);border:1px solid var(--mv-border);border-radius:15px}.mv-kpi-label{font-size:10px;color:var(--mv-muted);font-weight:800;text-transform:uppercase;letter-spacing:.08em}.mv-kpi-value{font-size:27px;font-weight:850;margin-top:8px;font-variant-numeric:tabular-nums}.mv-filters{padding:17px;margin-bottom:18px}.mv-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.mv-filter-grid input,.mv-filter-grid select{width:100%;min-width:0;height:43px;padding:0 11px;border-radius:10px;border:1px solid var(--mv-border);background:#080f1d;color:var(--mv-text);font-size:12px}.mv-filter-actions{display:flex;gap:8px;margin-top:10px}.mv-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 14px;border:0;border-radius:9px;background:#b77912;color:white;font-weight:800;font-size:12px;text-decoration:none;cursor:pointer}.mv-btn.secondary{background:transparent;border:1px solid var(--mv-border);color:var(--mv-muted)}.mv-table-card{overflow:hidden}.mv-table-head{display:flex;align-items:center;justify-content:space-between;padding:15px 17px;border-bottom:1px solid var(--mv-border);gap:10px}.mv-table-head strong{font-size:13px}.mv-table-scroll{overflow:auto}.mv-table{width:100%;min-width:1350px;border-collapse:collapse}.mv-table th{position:sticky;top:0;background:#0c1423;color:#91a0b5;text-align:left;font-size:9px;letter-spacing:.08em;text-transform:uppercase;padding:13px 12px;white-space:nowrap}.mv-table td{padding:13px 12px;border-top:1px solid rgba(148,163,184,.09);font-size:11px;white-space:nowrap;vertical-align:middle}.mv-table tbody tr:hover{background:rgba(245,158,11,.045)}.mv-product{font-weight:800;color:var(--mv-text)}.mv-variant{color:#c4a3ff;font-size:10px;margin-top:3px}.mv-muted{color:var(--mv-muted)}.mv-qty{display:inline-block;padding:5px 9px;border-radius:8px;background:rgba(245,158,11,.1);color:#fbbf24;font-weight:850}.mv-type{display:inline-block;padding:5px 9px;border-radius:999px;background:rgba(56,189,248,.08);color:#7dd3fc;font-size:10px;font-weight:850}.mv-ref{font-family:ui-monospace,monospace;color:#aab7c8}.mv-empty{padding:45px 20px!important;text-align:center;color:var(--mv-muted);white-space:normal!important}.mv-footnote{padding:12px 16px;color:var(--mv-muted);font-size:10px;border-top:1px solid var(--mv-border)}
@media(max-width:1000px){.mv-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.mv-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.stock-movement-page{padding:12px}.mv-hero{align-items:flex-start;flex-direction:column;padding:17px}.mv-live{white-space:normal}.mv-kpis{gap:8px}.mv-kpi{padding:13px}.mv-kpi-value{font-size:21px}.mv-filter-grid{grid-template-columns:1fr}.mv-filter-actions{flex-direction:column}.mv-btn{width:100%}}
body:not(.dark) .stock-movement-page{--mv-bg:#f3f6fb;--mv-card:#fff;--mv-border:#e3e9f2;--mv-text:#172033;--mv-muted:#718096;background:#f3f6fb;color:var(--mv-text)}
body:not(.dark) .mv-filter-grid input,body:not(.dark) .mv-filter-grid select{background:#fff;color:#172033}body:not(.dark) .mv-table th{background:#f3f6fb;color:#64748b}body:not(.dark) .mv-table td{color:#334155}body:not(.dark) .mv-product{color:#172033}
</style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}" x-init="darkMode=JSON.parse(localStorage.getItem('darkMode')||'false');$watch('darkMode',value=>localStorage.setItem('darkMode',JSON.stringify(value)))" :class="{'dark bg-gray-900':darkMode===true}">
<div class="flex h-screen overflow-hidden">
<?php include('sidebar.php'); ?>
<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"><main>
<?php include('header.php'); ?>
<div class="stock-movement-page"><div class="mv-wrap">
<section class="mv-hero"><div><h1 class="mv-title"><i class="fa-solid fa-arrow-right-arrow-left" style="color:#f59e0b;margin-right:8px"></i>Stock Movement Intelligence</h1><p class="mv-subtitle">Transfers and inventory movement records across your company locations, including product variants.</p></div><span class="mv-live"><i class="fa-solid fa-circle" style="font-size:7px"></i> Company inventory ledger</span></section>
<section class="mv-kpis">
<div class="mv-kpi"><div class="mv-kpi-label">Movement Records</div><div class="mv-kpi-value" id="totalMovements"><?= number_format($totalMovements) ?></div></div>
<div class="mv-kpi"><div class="mv-kpi-label">Quantity Moved</div><div class="mv-kpi-value" id="totalQuantityMoved"><?= number_format($totalQuantityMoved,2) ?></div></div>
<div class="mv-kpi"><div class="mv-kpi-label">Transfers</div><div class="mv-kpi-value"><?= number_format($totalTransfers) ?></div></div>
<div class="mv-kpi"><div class="mv-kpi-label">Stock In / Out</div><div class="mv-kpi-value" style="font-size:20px"><?= number_format($totalStockIn,2) ?> / <?= number_format($totalStockOut,2) ?></div></div>
</section>
<section class="mv-card mv-filters">
<form method="get" action="">
<div class="mv-filter-grid">
<input type="search" name="q" value="<?= movement_h($search) ?>" placeholder="Search product, barcode, variant, transfer ID...">
<input type="date" name="from" value="<?= movement_h($dateFrom) ?>" aria-label="From date">
<input type="date" name="to" value="<?= movement_h($dateTo) ?>" aria-label="To date">
<select name="movement_type"><option value="">All movement types</option><?php foreach($movementTypes as $type): ?><option value="<?= movement_h($type) ?>" <?= $movementType===$type?'selected':'' ?>><?= movement_h(ucwords(str_replace('_',' ',$type))) ?></option><?php endforeach; ?></select>
<select name="product_id"><option value="">All products</option><?php foreach($movementProducts as $product): ?><option value="<?= (int)$product['id'] ?>" <?= $productId===(string)$product['id']?'selected':'' ?>><?= movement_h($product['name']) ?></option><?php endforeach; ?></select>
<select name="from_location_id"><option value="">All source locations</option><?php foreach($movementLocations as $location): ?><option value="<?= (int)$location['id'] ?>" <?= $fromLocationId===(string)$location['id']?'selected':'' ?>><?= movement_h($location['name']) ?></option><?php endforeach; ?></select>
<select name="to_location_id"><option value="">All destination locations</option><?php foreach($movementLocations as $location): ?><option value="<?= (int)$location['id'] ?>" <?= $toLocationId===(string)$location['id']?'selected':'' ?>><?= movement_h($location['name']) ?></option><?php endforeach; ?></select>
<select name="location_id"><option value="">Either location (optional)</option><?php foreach($movementLocations as $location): ?><option value="<?= (int)$location['id'] ?>" <?= $locationId===(string)$location['id']?'selected':'' ?>><?= movement_h($location['name']) ?></option><?php endforeach; ?></select>
</div>
<div class="mv-filter-actions"><button class="mv-btn" type="submit"><i class="fa-solid fa-filter"></i> Apply filters</button><a class="mv-btn secondary" href="<?= movement_h(basename($_SERVER['PHP_SELF'] ?? 'stock-movements.php')) ?>"><i class="fa-solid fa-rotate-left"></i> Clear filters</a></div>
</form>
</section>
<section class="mv-card mv-table-card">
<div class="mv-table-head"><strong><i class="fa-solid fa-clock-rotate-left" style="color:#f59e0b;margin-right:7px"></i>Inventory Movement Ledger</strong><span class="mv-muted" style="font-size:11px"><?= number_format($totalMovements) ?> matching record(s)</span></div>
<div class="mv-table-scroll"><table class="mv-table"><thead><tr><th>ID</th><th>Product / Variant</th><th>Barcode</th><th>Source Stock ID</th><th>From Location</th><th>To Location</th><th>Transfer ID</th><th>Quantity</th><th>Movement Type</th><th>Reference</th><th>Created By</th><th>Date</th></tr></thead><tbody>
<?php if ($stock_movements): foreach($stock_movements as $row): ?>
<tr>
<td class="mv-muted">#<?= (int)$row['id'] ?></td>
<td><div class="mv-product"><?= movement_h($row['product_name']) ?></div><?php if($row['variant_name']!==''): ?><div class="mv-variant"><i class="fa-solid fa-tags"></i> <?= movement_h($row['variant_name']) ?> · Variant #<?= (int)$row['variant_id'] ?></div><?php elseif($row['variant_id']!==null): ?><div class="mv-variant">Variant #<?= (int)$row['variant_id'] ?></div><?php endif; ?></td>
<td class="mv-muted"><?= movement_h($row['barcode'] ?: '—') ?></td>
<td><?= (int)$row['stock_id'] ?></td>
<td><?= movement_h($row['from_location_name']) ?></td>
<td><?= movement_h($row['to_location_name']) ?></td>
<td class="mv-ref"><?= movement_h($row['transfer_ID'] ?: '—') ?></td>
<td><span class="mv-qty"><?= number_format((float)$row['quantity'],2) ?></span></td>
<td><span class="mv-type"><?= movement_h($row['movement_type']) ?></span></td>
<td class="mv-ref"><?= movement_h($row['reference'] ?: '—') ?></td>
<td><?= movement_h($row['created_by_name']) ?></td>
<td class="mv-muted"><?= movement_h($row['created_at'] ?: '—') ?></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="12" class="mv-empty"><i class="fa-solid fa-box-open" style="font-size:25px;color:#f59e0b;display:block;margin-bottom:10px"></i><strong>No stock movements found for this company and filter selection.</strong><br><span>Clear the filters or confirm that the transfer was saved under the currently signed-in company.</span></td></tr>
<?php endif; ?>
</tbody></table></div>
<div class="mv-footnote">Variant movements are matched using stock_movements.variant_id and displayed with the corresponding product_variants.variant_name. The ledger is company-scoped using the signed-in session.</div>
</section>
</div></div>
</main></div></div>
<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
</body>
</html>
