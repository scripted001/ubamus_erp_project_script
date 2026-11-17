<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

/*
 * BRANCH MANAGER STOCK INTELLIGENCE
 * All authorization scope is derived from the authenticated users row.
 * Never trust company_id or location_id supplied by the browser.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
if ($sessionUserId <= 0) {
    http_response_code(401);
    exit('Your session has expired. Please sign in again.');
}

$authStmt = $pdo->prepare("
    SELECT
        u.id AS user_id,
        u.company_id,
        u.location_id,
        u.role,
        u.status AS user_status,
        l.name AS branch_name,
        l.status AS location_status
    FROM users u
    INNER JOIN locations l
        ON l.id = u.location_id
       AND l.company_id = u.company_id
    WHERE u.id = ?
    LIMIT 1
");
$authStmt->execute([$sessionUserId]);
$auth = $authStmt->fetch(PDO::FETCH_ASSOC);

if (
    !$auth ||
    strcasecmp(trim((string)$auth['role']), 'Branch Manager') !== 0 ||
    strcasecmp(trim((string)$auth['user_status']), 'active') !== 0 ||
    (int)$auth['company_id'] <= 0 ||
    (int)$auth['location_id'] <= 0
) {
    http_response_code(403);
    exit('Access denied. An active Branch Manager account and active assigned branch are required.');
}

$company_id = (int)$auth['company_id'];
$location_id = (int)$auth['location_id'];
$user_id = (int)$auth['user_id'];
$branch_name = (string)$auth['branch_name'];

$stock_id = filter_input(INPUT_POST, 'stock_id', FILTER_VALIDATE_INT);
if (!$stock_id || $stock_id <= 0) {
    http_response_code(400);
    exit('Invalid stock request.');
}
$stock_id = (int)$stock_id;

/* Verify that this exact stock record belongs to this company and branch. */
$stockStmt = $pdo->prepare("
    SELECT
        s.id,
        s.product_id,
        s.quantity,
        s.location_id,
        p.name,
        p.barcode,
        p.cost_price,
        p.selling_price
    FROM stocks s
    INNER JOIN products p
        ON p.id = s.product_id
       AND p.company_id = s.company_id
    WHERE s.id = ?
      AND s.company_id = ?
      AND s.location_id = ?
    LIMIT 1
");
$stockStmt->execute([$stock_id, $company_id, $location_id]);
$stockRecord = $stockStmt->fetch(PDO::FETCH_ASSOC);

if (!$stockRecord) {
    http_response_code(404);
    exit('This stock record was not found in your assigned branch.');
}

$totalQuantity = (float)$stockRecord['quantity'];
$stock_unit_price = (float)$stockRecord['selling_price'];
$totalStockValue = $totalQuantity * $stock_unit_price;
$totalStockProfit = ($stock_unit_price - (float)$stockRecord['cost_price']) * $totalQuantity;

/* Stock-in records: branch-scoped and tied to the verified stock record. */
$totalStockinStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM stock_in
    WHERE company_id = ?
      AND location_id = ?
      AND stock_id = ?
");
$totalStockinStmt->execute([$company_id, $location_id, $stock_id]);
$totalStockinCount = (int)$totalStockinStmt->fetchColumn();

/* Only movements touching this branch are visible in this branch view. */
$totalStockMvStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM stock_movements
    WHERE company_id = ?
      AND stock_id = ?
      AND (from_location_id = ? OR to_location_id = ?)
");
$totalStockMvStmt->execute([$company_id, $stock_id, $location_id, $location_id]);
$StockMvCount = (int)$totalStockMvStmt->fetchColumn();

/* Stock-in ledger is restricted to this branch. */
$stocksStmt = $pdo->prepare("
    SELECT
        si.id,
        si.stock_id,
        si.location_id,
        si.transfer_ID,
        si.quantity,
        si.type,
        si.created_at,
        si.recorded_by,
        si.supplier,
        p.name,
        p.barcode,
        p.cost_price,
        p.selling_price,
        us.email,
        l.name AS location_name
    FROM stock_in si
    INNER JOIN products p
        ON p.id = si.product_id
       AND p.company_id = si.company_id
    INNER JOIN locations l
        ON l.id = si.location_id
       AND l.company_id = si.company_id
    LEFT JOIN users us
        ON us.id = si.recorded_by
       AND us.company_id = si.company_id
    WHERE si.company_id = ?
      AND si.location_id = ?
      AND si.stock_id = ?
    ORDER BY si.id ASC
");
$stocksStmt->execute([$company_id, $location_id, $stock_id]);
$stockData = $stocksStmt->fetchAll(PDO::FETCH_ASSOC);

/* Category chart: quantities held at this branch only. */
$categoriesStmt = $pdo->prepare("
    SELECT
        c.name AS category,
        COALESCE(SUM(s.quantity), 0) AS total
    FROM product_categories c
    LEFT JOIN products p
        ON p.category_id = c.id
       AND p.company_id = c.company_id
    LEFT JOIN stocks s
        ON s.product_id = p.id
       AND s.company_id = p.company_id
       AND s.location_id = ?
    WHERE c.company_id = ?
    GROUP BY c.id, c.name
    ORDER BY total DESC, c.name ASC
");
$categoriesStmt->execute([$location_id, $company_id]);
$categoriesDataArr = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
$categoriesLabels = array_column($categoriesDataArr, 'category');
$categoriesData = array_map('floatval', array_column($categoriesDataArr, 'total'));

/* Thirty-day trend: this branch only. */
$trendStmt = $pdo->prepare("
    SELECT
        DATE(s.created_at) AS date,
        COALESCE(SUM(s.quantity), 0) AS total
    FROM stocks s
    WHERE s.company_id = ?
      AND s.location_id = ?
      AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(s.created_at)
    ORDER BY DATE(s.created_at) ASC
");
$trendStmt->execute([$company_id, $location_id]);
$trendDataArr = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
$trendLabels = array_column($trendDataArr, 'date');
$trendData = array_map('floatval', array_column($trendDataArr, 'total'));

function si_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function si_money($value): string {
    return number_format((float)$value, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= si_h($company_name ?? 'Ubamus') ?> | Stock Intelligence</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root{--si-brand:#6366f1;--si-brand-dark:#4f46e5;--si-cyan:#06b6d4;--si-green:#10b981;--si-red:#ef4444;--si-orange:#f59e0b;--si-border:rgba(148,163,184,.22);--si-glass:rgba(255,255,255,.78);--si-shadow:0 20px 50px rgba(15,23,42,.08),0 4px 14px rgba(15,23,42,.04)}
        .stock-intelligence{width:100%;max-width:1600px;margin:0 auto}
        .si-hero{position:relative;overflow:hidden;padding:28px;margin-bottom:24px;border-radius:24px;border:1px solid rgba(99,102,241,.15);background:radial-gradient(circle at 85% 10%,rgba(99,102,241,.15),transparent 30%),radial-gradient(circle at 10% 90%,rgba(6,182,212,.10),transparent 32%),rgba(255,255,255,.72);backdrop-filter:blur(20px);box-shadow:var(--si-shadow)}
        .si-hero-content{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:20px}
        .si-eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:8px;color:var(--si-brand);font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
        .si-live-dot{width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 5px rgba(16,185,129,.10);animation:siPulse 2s infinite}
        @keyframes siPulse{0%,100%{opacity:1}50%{opacity:.45}}
        .si-title{margin:0;color:#111827;font-size:clamp(24px,3vw,38px);font-weight:800;letter-spacing:-.04em}
        .si-description{margin-top:8px;max-width:650px;color:#64748b;font-size:14px;line-height:1.6}
        .si-stock-badge{display:flex;align-items:center;gap:10px;padding:12px 16px;border:1px solid rgba(99,102,241,.15);border-radius:14px;background:rgba(255,255,255,.62);color:#475569;font-size:12px;font-weight:700;white-space:nowrap}
        .si-stock-badge i{color:var(--si-brand)}
        .si-kpi-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:24px}
        .si-kpi{position:relative;overflow:hidden;min-width:0;padding:20px;border:1px solid var(--si-border);border-radius:20px;background:var(--si-glass);backdrop-filter:blur(18px);box-shadow:var(--si-shadow);transition:.25s}
        .si-kpi:hover{transform:translateY(-4px);border-color:rgba(99,102,241,.25)}
        .si-kpi-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
        .si-kpi-label{color:#64748b;font-size:12px;font-weight:700}
        .si-kpi-icon{display:flex;align-items:center;justify-content:center;width:38px;height:38px;flex:0 0 38px;border-radius:12px;background:rgba(99,102,241,.09);color:var(--si-brand)}
        .si-kpi-value{margin-top:14px;color:#111827;font-size:clamp(23px,2.2vw,31px);font-weight:800;letter-spacing:-.04em;overflow-wrap:anywhere}
        .si-kpi-sub{margin-top:6px;color:#94a3b8;font-size:11px}
        .si-kpi.profit .si-kpi-icon{background:rgba(16,185,129,.10);color:var(--si-green)}.si-kpi.profit .si-kpi-value{color:#059669}
        .si-kpi.transfer .si-kpi-icon{background:rgba(6,182,212,.10);color:var(--si-cyan)}.si-kpi.stock-in .si-kpi-icon{background:rgba(245,158,11,.10);color:var(--si-orange)}
        .si-panel{border:1px solid var(--si-border);border-radius:22px;background:rgba(255,255,255,.76);backdrop-filter:blur(18px);box-shadow:var(--si-shadow)}
        .si-filter-panel{padding:18px;margin-bottom:20px}
        .si-section-title{display:flex;align-items:center;gap:9px;margin-bottom:14px;color:#1e293b;font-size:14px;font-weight:800}.si-section-title i{color:var(--si-brand)}
        .si-filters{display:grid;grid-template-columns:minmax(180px,2fr) repeat(3,minmax(130px,1fr));gap:10px}
        .si-filter{width:100%;min-width:0;height:44px;padding:0 13px;border:1px solid #e2e8f0;border-radius:12px;outline:none;background:rgba(248,250,252,.85);color:#334155;font-size:13px}
        .si-filter:focus{border-color:rgba(99,102,241,.55);box-shadow:0 0 0 4px rgba(99,102,241,.08);background:#fff}
        .si-table-panel{overflow:hidden;margin-bottom:24px}.si-table-header{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:18px 20px;border-bottom:1px solid #eef2f7}
        .si-table-title{margin:0;color:#1e293b;font-size:15px;font-weight:800}.si-table-meta{color:#94a3b8;font-size:11px;font-weight:600}
        .si-table-scroll{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}.si-table{width:100%;min-width:1250px;border-collapse:collapse}
        .si-table th{padding:13px 14px;background:rgba(248,250,252,.92);color:#64748b;font-size:10px;font-weight:800;letter-spacing:.07em;text-align:left;text-transform:uppercase;white-space:nowrap}
        .si-table td{padding:14px;border-top:1px solid #f1f5f9;color:#475569;font-size:12px;white-space:nowrap}.si-table tbody tr:hover{background:rgba(99,102,241,.035)}
        .si-product{color:#1e293b!important;font-weight:750}.si-barcode{font-family:monospace;color:#64748b}.si-type{display:inline-flex;padding:5px 9px;border-radius:999px;background:rgba(99,102,241,.08);color:var(--si-brand-dark);font-size:10px;font-weight:800}
        .si-profit-positive{color:#059669!important;font-weight:800}.si-profit-negative{color:#dc2626!important;font-weight:800}.si-money{font-variant-numeric:tabular-nums}
        .si-chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.si-chart-card{min-width:0;padding:20px}
        .si-chart-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:16px}.si-chart-title{margin:0;color:#1e293b;font-size:14px;font-weight:800}
        .si-chart-description{margin-top:4px;color:#94a3b8;font-size:11px}.si-chart-icon{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:11px;background:rgba(99,102,241,.08);color:var(--si-brand)}
        .si-chart-wrapper{position:relative;width:100%;height:330px}.si-scroll-hint{display:none;padding:9px 15px;border-top:1px solid #eef2f7;color:#94a3b8;font-size:10px;text-align:center}
        @media(max-width:1200px){.si-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.si-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:900px){.si-hero-content{align-items:flex-start;flex-direction:column}.si-chart-grid{grid-template-columns:1fr}}
        @media(max-width:640px){.si-hero{padding:20px;border-radius:18px}.si-title{font-size:25px}.si-description{font-size:13px}.si-kpi-grid{gap:10px}.si-kpi{padding:15px;border-radius:16px}.si-kpi-icon{width:32px;height:32px;flex-basis:32px}.si-kpi-value{font-size:21px}.si-kpi-label{font-size:10px}.si-filters{grid-template-columns:1fr 1fr}.si-filter:first-child{grid-column:1/-1}.si-table-header{align-items:flex-start;flex-direction:column;padding:15px}.si-chart-card{padding:15px}.si-chart-wrapper{height:280px}}
        @media(max-width:430px){.si-kpi-grid,.si-filters{grid-template-columns:1fr}.si-filter:first-child{grid-column:auto}.si-kpi{min-height:110px}}
        @media(max-width:768px){.si-scroll-hint{display:block}}
    </style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}"
      x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
      :class="{'dark bg-gray-900':darkMode===true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="stock-intelligence">
                    <section class="si-hero">
                        <div class="si-hero-content">
                            <div>
                                <div class="si-eyebrow"><span class="si-live-dot"></span> BRANCH INVENTORY INTELLIGENCE</div>
                                <h1 class="si-title">Stock Intelligence</h1>
                                <p class="si-description">Monitor inventory performance, stock movement, capital value and profitability for your assigned branch.</p>
                            </div>
                            <div class="si-stock-badge"><i class="fa-solid fa-boxes-stacked"></i> <?= si_h($branch_name) ?> · Stock #<?= si_h($stock_id) ?></div>
                        </div>
                    </section>

                    <section class="si-kpi-grid">
                        <div class="si-kpi"><div class="si-kpi-head"><span class="si-kpi-label">Total Stock Quantity</span><span class="si-kpi-icon"><i class="fa-solid fa-cubes"></i></span></div><div class="si-kpi-value" id="totalQuantity"><?= number_format($totalQuantity) ?></div><div class="si-kpi-sub">Current inventory units in this branch</div></div>
                        <div class="si-kpi"><div class="si-kpi-head"><span class="si-kpi-label">Stock Unit Price</span><span class="si-kpi-icon"><i class="fa-solid fa-naira-sign"></i></span></div><div class="si-kpi-value" id="unit_price"><?= si_money($stock_unit_price) ?></div><div class="si-kpi-sub">Current selling price</div></div>
                        <div class="si-kpi"><div class="si-kpi-head"><span class="si-kpi-label">Total Sales Value</span><span class="si-kpi-icon"><i class="fa-solid fa-chart-line"></i></span></div><div class="si-kpi-value" id="totalStockValue"><?= si_money($totalStockValue) ?></div><div class="si-kpi-sub">Estimated inventory sales value</div></div>
                        <div class="si-kpi transfer"><div class="si-kpi-head"><span class="si-kpi-label">Stock Transfers</span><span class="si-kpi-icon"><i class="fa-solid fa-right-left"></i></span></div><div class="si-kpi-value" id="totalStockTransfer"><?= number_format($StockMvCount) ?></div><div class="si-kpi-sub">Movements involving this branch</div></div>
                        <div class="si-kpi profit"><div class="si-kpi-head"><span class="si-kpi-label">Net Stock Profit</span><span class="si-kpi-icon"><i class="fa-solid fa-arrow-trend-up"></i></span></div><div class="si-kpi-value" id="totalStockProfit"><?= si_money($totalStockProfit) ?></div><div class="si-kpi-sub">Potential gross profit</div></div>
                        <div class="si-kpi stock-in"><div class="si-kpi-head"><span class="si-kpi-label">Stock In Records</span><span class="si-kpi-icon"><i class="fa-solid fa-arrow-down"></i></span></div><div class="si-kpi-value" id="totalStockIn"><?= number_format($totalStockinCount) ?></div><div class="si-kpi-sub">Intake records for this branch</div></div>
                    </section>

                    <section class="si-panel si-filter-panel">
                        <div class="si-section-title"><i class="fa-solid fa-sliders"></i> Inventory Filters</div>
                        <div class="si-filters">
                            <input type="text" id="searchInput" placeholder="Search product or barcode..." class="si-filter">
                            <input type="date" id="dateFilter" class="si-filter">
                            <select id="locationFilter" class="si-filter"><option value="<?= si_h($branch_name) ?>"><?= si_h($branch_name) ?></option></select>
                            <input type="number" id="minPrice" placeholder="Min cost price" class="si-filter">
                            <input type="number" id="maxPrice" placeholder="Max cost price" class="si-filter">
                        </div>
                    </section>

                    <section class="si-panel si-table-panel">
                        <div class="si-table-header">
                            <div><h2 class="si-table-title">Stock Transaction Intelligence</h2><div class="si-table-meta">Detailed inventory intake records for <?= si_h($branch_name) ?></div></div>
                            <div class="si-table-meta" id="stockRecordCount"><?= count($stockData) ?> record(s)</div>
                        </div>
                        <div class="si-table-scroll">
                            <table class="si-table">
                                <thead><tr><th>S/N</th><th>Product</th><th>Barcode</th><th>Quantity</th><th>Type</th><th>Transaction ID</th><th>Supplier / Vendor</th><th>Location</th><th>Cost Price</th><th>Selling Price</th><th>Capital Value</th><th>Sales Value</th><th>Profit Value</th><th>Date &amp; Time</th></tr></thead>
                                <tbody id="stockTableBody">
                                <?php $count = 1; foreach ($stockData as $row):
                                    $quantity = (float)$row['quantity'];
                                    $costPrice = (float)$row['cost_price'];
                                    $sellingPrice = (float)$row['selling_price'];
                                    $totalCostValue = $quantity * $costPrice;
                                    $totalSellingValue = $quantity * $sellingPrice;
                                    $profitValue = ($sellingPrice - $costPrice) * $quantity;
                                    $supplier = 'General Merchant';
                                    if ((string)$row['supplier'] !== 'General Merchant' && (string)$row['supplier'] !== '') {
                                        /* A vendor is disclosed only when assigned to this branch. */
                                        $vendorStmt = $pdo->prepare("
                                            SELECT business_name
                                            FROM vendors
                                            WHERE id = ?
                                              AND company_id = ?
                                              AND location_id = ?
                                            LIMIT 1
                                        ");
                                        $vendorStmt->execute([(int)$row['supplier'], $company_id, $location_id]);
                                        $vendorRow = $vendorStmt->fetch(PDO::FETCH_ASSOC);
                                        $supplier = $vendorRow['business_name'] ?? 'Branch vendor';
                                    }
                                ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td class="si-product"><?= si_h($row['name']) ?></td>
                                        <td class="si-barcode"><?= si_h($row['barcode']) ?></td>
                                        <td><?= number_format($quantity) ?></td>
                                        <td><span class="si-type"><?= si_h($row['type']) ?></span></td>
                                        <td><?= si_h($row['transfer_ID']) ?></td>
                                        <td><?= si_h($supplier) ?></td>
                                        <td><?= si_h($branch_name) ?></td>
                                        <td class="si-money"><?= si_money($costPrice) ?></td>
                                        <td class="si-money"><?= si_money($sellingPrice) ?></td>
                                        <td class="si-money"><?= si_money($totalCostValue) ?></td>
                                        <td class="si-money"><?= si_money($totalSellingValue) ?></td>
                                        <td class="si-money <?= $profitValue >= 0 ? 'si-profit-positive' : 'si-profit-negative' ?>"><?= si_money($profitValue) ?></td>
                                        <td><?= si_h($row['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$stockData): ?><tr><td colspan="14" style="text-align:center;padding:28px;color:#94a3b8">No stock-in transactions have been recorded for this stock at your branch.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="si-scroll-hint"><i class="fa-solid fa-arrows-left-right"></i> Swipe horizontally to view all transaction columns</div>
                    </section>

                    <section class="si-chart-grid">
                        <div class="si-panel si-chart-card">
                            <div class="si-chart-head"><div><h3 class="si-chart-title">Inventory Distribution</h3><p class="si-chart-description">Branch quantity distribution across categories</p></div><span class="si-chart-icon"><i class="fa-solid fa-chart-pie"></i></span></div>
                            <div class="si-chart-wrapper"><canvas id="categoryChart"></canvas></div>
                        </div>
                        <div class="si-panel si-chart-card">
                            <div class="si-chart-head"><div><h3 class="si-chart-title">Stock Activity Trend</h3><p class="si-chart-description">Branch inventory activity over the last 30 days</p></div><span class="si-chart-icon"><i class="fa-solid fa-chart-area"></i></span></div>
                            <div class="si-chart-wrapper"><canvas id="stockTrendChart"></canvas></div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = Array.from(document.querySelectorAll('#stockTableBody tr')).filter(r => r.querySelectorAll('td').length >= 14);
    const $ = id => document.getElementById(id);
    const searchInput = $('searchInput'), dateFilter = $('dateFilter'), minPriceInput = $('minPrice'), maxPriceInput = $('maxPrice');
    const recordCount = $('stockRecordCount');
    const money = new Intl.NumberFormat('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2});
    const nums = new Intl.NumberFormat('en-NG', {maximumFractionDigits:2});
    const initial = {
        quantity: <?= json_encode($totalQuantity) ?>,
        unit: <?= json_encode($stock_unit_price) ?>,
        value: <?= json_encode($totalStockValue) ?>,
        movement: <?= json_encode($StockMvCount) ?>,
        profit: <?= json_encode($totalStockProfit) ?>,
        stockIn: <?= json_encode($totalStockinCount) ?>
    };
    function cellText(row, index) { return (row.cells[index]?.textContent || '').trim(); }
    function numeric(row, index) { const n = parseFloat(cellText(row,index).replace(/[^0-9.-]/g,'')); return Number.isFinite(n) ? n : 0; }
    function set(id, value, isMoney=false) { const el=$(id); if(el) el.textContent=isMoney?money.format(value):nums.format(value); }
    function filterTable() {
        const q=(searchInput.value||'').trim().toLowerCase(), date=dateFilter.value||'';
        const minRaw=minPriceInput.value.trim(), maxRaw=maxPriceInput.value.trim();
        const min=minRaw===''?-Infinity:Number(minRaw), max=maxRaw===''?Infinity:Number(maxRaw);
        const active=!!(q||date||minRaw||maxRaw);
        const visible=[];
        rows.forEach(row=>{
            const product=cellText(row,1).toLowerCase(), barcode=cellText(row,2).toLowerCase();
            const cost=numeric(row,8), rowDate=cellText(row,13).substring(0,10);
            const match=(!q||product.includes(q)||barcode.includes(q)) &&
                (!date||rowDate===date) && cost>=min && cost<=max;
            row.style.display=match?'':'none';
            if(match) visible.push(row);
        });
        if(!active) {
            set('totalQuantity',initial.quantity); set('unit_price',initial.unit,true);
            set('totalStockValue',initial.value,true); set('totalStockTransfer',initial.movement);
            set('totalStockProfit',initial.profit,true); set('totalStockIn',initial.stockIn);
            recordCount.textContent=`${rows.length} record(s)`;
            return;
        }
        let quantity=0, sales=0, profit=0;
        visible.forEach(row=>{quantity+=numeric(row,3);sales+=numeric(row,11);profit+=numeric(row,12);});
        set('totalQuantity',quantity); set('unit_price',quantity?sales/quantity:0,true);
        set('totalStockValue',sales,true); set('totalStockProfit',profit,true);
        set('totalStockIn',visible.length);
        /* Movement KPI is a branch-level movement count, not a stock-in row count. */
        set('totalStockTransfer',initial.movement);
        recordCount.textContent=`${visible.length} of ${rows.length} record(s)`;
    }
    searchInput.addEventListener('input',filterTable);
    dateFilter.addEventListener('change',filterTable);
    minPriceInput.addEventListener('input',filterTable);
    maxPriceInput.addEventListener('input',filterTable);
    if(typeof Chart!=='undefined') {
        const categoryCanvas=$('categoryChart');
        if(categoryCanvas) new Chart(categoryCanvas,{type:'doughnut',data:{labels:<?= json_encode($categoriesLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>,datasets:[{data:<?= json_encode($categoriesData) ?>}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{padding:18,usePointStyle:true,font:{size:11}}}},animation:{duration:700}}});
        const trendCanvas=$('stockTrendChart');
        if(trendCanvas) new Chart(trendCanvas,{type:'line',data:{labels:<?= json_encode($trendLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>,datasets:[{label:'Stock Quantity',data:<?= json_encode($trendData) ?>,fill:true,tension:.42,pointRadius:3,pointHoverRadius:6,borderWidth:2,backgroundColor:'rgba(99,102,241,.10)',borderColor:'#6366f1'}]},options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{maxTicksLimit:7,font:{size:10}}},y:{beginAtZero:true,ticks:{font:{size:10}}}},animation:{duration:700}}});
    }
    filterTable();
});
</script>
</body>
</html>
