<?php
// ============================================================
// BRANCH MANAGER STOCK TERMINAL
// Strictly restricted to the authenticated user's company/location.
// ============================================================


include('../config/db.php');
include('../config/validation.php');
include('user_data.php');

$authenticatedUserId = (int)($_SESSION['user_id'] ?? ($user_id ?? 0));
if ($authenticatedUserId <= 0) {
    http_response_code(403);
    exit('Access denied. Please sign in again.');
}

$userStmt = $pdo->prepare("\n    SELECT id, role, status, company_id, location_id\n    FROM users\n    WHERE id = ?\n    LIMIT 1\n");
$userStmt->execute([$authenticatedUserId]);
$branchUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$branchUser) {
    http_response_code(403);
    exit('Access denied. User account was not found.');
}

$role = strtolower(trim((string)($branchUser['role'] ?? '')));
$status = strtolower(trim((string)($branchUser['status'] ?? '')));
if ($role !== 'branch manager' || !in_array($status, ['active', '1', 'enabled'], true)) {
    http_response_code(403);
    exit('Access denied. This page is for active Branch Managers only.');
}

$company_id = (int)($branchUser['company_id'] ?? 0);
$location_id = (int)($branchUser['location_id'] ?? 0);
$user_id = (int)$branchUser['id'];

if ($company_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Access denied. Your account does not have a valid assigned branch.');
}

$locationStmt = $pdo->prepare("\n    SELECT id, name\n    FROM locations\n    WHERE id = ? AND company_id = ?\n    LIMIT 1\n");
$locationStmt->execute([$location_id, $company_id]);
$assignedLocation = $locationStmt->fetch(PDO::FETCH_ASSOC);
if (!$assignedLocation) {
    http_response_code(403);
    exit('Access denied. Your assigned branch could not be verified.');
}
$branch_location_name = (string)$assignedLocation['name'];

include('../company_data.php');

// ----------------------
// KPI CALCULATIONS
// ----------------------
$totalProductsStmt = $pdo->prepare("\n    SELECT COUNT(DISTINCT p.id)\n    FROM products p\n    INNER JOIN stocks s ON s.product_id = p.id\n    WHERE p.company_id = ? AND s.company_id = ? AND s.location_id = ?\n");
$totalProductsStmt->execute([$company_id, $company_id, $location_id]);
$totalProducts = $totalProductsStmt->fetchColumn();

$totalQuantityStmt = $pdo->prepare("\n    SELECT COALESCE(SUM(quantity),0)\n    FROM stocks\n    WHERE company_id = ? AND location_id = ?\n");
$totalQuantityStmt->execute([$company_id, $location_id]);
$totalQuantity = $totalQuantityStmt->fetchColumn();

$totalStockValueStmt = $pdo->prepare("\n    SELECT COALESCE(SUM(s.quantity * p.cost_price),0)\n    FROM stocks s\n    INNER JOIN products p ON s.product_id = p.id AND p.company_id = s.company_id\n    WHERE s.company_id = ? AND s.location_id = ?\n");
$totalStockValueStmt->execute([$company_id, $location_id]);
$totalStockValue = $totalStockValueStmt->fetchColumn();

$totalStockProfitStmt = $pdo->prepare("\n    SELECT COALESCE(SUM((p.selling_price - p.cost_price) * s.quantity),0)\n    FROM stocks s\n    INNER JOIN products p ON s.product_id = p.id AND p.company_id = s.company_id\n    WHERE s.company_id = ? AND s.location_id = ?\n");
$totalStockProfitStmt->execute([$company_id, $location_id]);
$totalStockProfit = $totalStockProfitStmt->fetchColumn();

$totalCategoriesStmt = $pdo->prepare("\n    SELECT COUNT(DISTINCT p.category_id)\n    FROM stocks s\n    INNER JOIN products p ON p.id = s.product_id AND p.company_id = s.company_id\n    WHERE s.company_id = ? AND s.location_id = ? AND p.category_id IS NOT NULL\n");
$totalCategoriesStmt->execute([$company_id, $location_id]);
$totalCategories = $totalCategoriesStmt->fetchColumn();

// ----------------------
// STOCK TABLE — assigned branch only
// ----------------------
$stocksStmt = $pdo->prepare("\n    SELECT\n        s.id, s.product_id, p.name, p.barcode, p.category_id,\n        c.name AS category_name, s.quantity, s.created_at, s.location_id,\n        s.supplier, p.cost_price, p.selling_price, p.stock_type,\n        u.unit_name, l.name AS location_name,\n        COALESCE(si.quantity, 0) AS last_stock_in_quantity,\n        COALESCE(si.quantity, 0) * 0.30 AS thirty_percent_stock,\n        CASE WHEN s.supplier = 'Production' THEN 'Production' ELSE v.business_name END AS supplier_name\n    FROM stocks s\n    INNER JOIN products p ON s.product_id = p.id AND p.company_id = s.company_id\n    LEFT JOIN product_categories c ON p.category_id = c.id AND c.company_id = s.company_id\n    INNER JOIN locations l ON s.location_id = l.id AND l.company_id = s.company_id\n    LEFT JOIN units u ON p.unit_id = u.id\n    LEFT JOIN vendors v ON CAST(s.supplier AS UNSIGNED) = v.id AND v.company_id = s.company_id\n    LEFT JOIN stock_in si ON si.id = (\n        SELECT si2.id\n        FROM stock_in si2\n        WHERE si2.stock_id = s.id\n          AND si2.company_id = s.company_id\n          AND si2.location_id = s.location_id\n        ORDER BY si2.id DESC\n        LIMIT 1\n    )\n    WHERE s.company_id = ? AND s.location_id = ?\n    ORDER BY p.name ASC\n");
$stocksStmt->execute([$company_id, $location_id]);
$stocks = $stocksStmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// CATEGORY CHART — assigned branch only
// ----------------------
$categoriesStmt = $pdo->prepare("\n    SELECT c.name AS category, COALESCE(SUM(s.quantity),0) AS total\n    FROM product_categories c\n    INNER JOIN products p ON p.category_id = c.id AND p.company_id = c.company_id\n    INNER JOIN stocks s ON s.product_id = p.id AND s.company_id = p.company_id\n    WHERE c.company_id = ? AND s.location_id = ?\n    GROUP BY c.id, c.name\n");
$categoriesStmt->execute([$company_id, $location_id]);
$categoriesDataArr = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
$categoriesLabels = array_column($categoriesDataArr, 'category');
$categoriesData = array_column($categoriesDataArr, 'total');

// ----------------------
// STOCK TREND — assigned branch only
// ----------------------
$trendStmt = $pdo->prepare("\n    SELECT DATE(s.created_at) AS date, COALESCE(SUM(s.quantity),0) AS total\n    FROM stocks s\n    WHERE s.company_id = ? AND s.location_id = ?\n      AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)\n    GROUP BY DATE(s.created_at)\n    ORDER BY DATE(s.created_at) DESC\n");
$trendStmt->execute([$company_id, $location_id]);
$trendDataArr = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
$trendLabels = array_column($trendDataArr, 'date');
$trendData = array_column($trendDataArr, 'total');
?>

<!doctype html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    http-equiv="content-type"
    content="text/html;charset=utf-8"
/>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, maximum-scale=1.0"
/>

<meta
    http-equiv="X-UA-Compatible"
    content="ie=edge"
/>

<title>
    <?php echo htmlspecialchars($company_name); ?> | Stock Terminal
</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">

<script data-cfasync="false" nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86">
try{
    (function(w,d){
        !function(fv,fw,fx,fy){
            if(fv.zaraz) console.error("zaraz is loaded twice");
            else {
                fv[fx]=fv[fx]||{};
                fv[fx].executed=[];
                fv.zaraz={
                    deferred:[],
                    listeners:[]
                };
                fv.zaraz._v="5858";
                fv.zaraz._n="ff1f9a24-18a4-4614-8917-7c27c5c96a86";
                fv.zaraz.q=[];
                fv.zaraz._f=function(fz){
                    return async function(){
                        var fA=Array.prototype.slice.call(arguments);
                        fv.zaraz.q.push({m:fz,a:fA})
                    }
                };
                for(const fB of["track","set","debug"])
                    fv.zaraz[fB]=fv.zaraz._f(fB);

                fv.zaraz.init=()=>new Promise((eD=>{eD()}));

                ["complete","interactive"].includes(fw.readyState)
                    ? zaraz.init()
                    : fv.addEventListener("DOMContentLoaded",zaraz.init)
            }
        }(w,document,"zarazData","script");
    })(window,document)
}catch(e){
    throw fetch("/cdn-cgi/zaraz/t"),e;
};
</script>


<style>

/* ============================================================
   FUTURISTIC STOCK TERMINAL
   ============================================================ */

:root {
    --stock-bg: #f5f7fb;
    --stock-card: #ffffff;
    --stock-border: #e5e7eb;
    --stock-text: #111827;
    --stock-muted: #6b7280;
    --stock-accent: #6366f1;
    --stock-accent-2: #8b5cf6;
    --stock-success: #10b981;
    --stock-danger: #ef4444;
    --stock-warning: #f59e0b;
    --stock-blue: #3b82f6;
}

.dark {
    --stock-bg: #080b14;
    --stock-card: #0e1320;
    --stock-border: rgba(148,163,184,.13);
    --stock-text: #f8fafc;
    --stock-muted: #94a3b8;
}


/* PAGE */

.stock-page {
    width: 100%;
    max-width: 1800px;
    margin: 0 auto;
}

.stock-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
}

.stock-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.stock-terminal-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
        linear-gradient(
            135deg,
            rgba(99,102,241,.18),
            rgba(139,92,246,.10)
        );
    border: 1px solid rgba(99,102,241,.22);
    color: var(--stock-accent);
    font-size: 20px;
    box-shadow: 0 10px 30px rgba(99,102,241,.10);
}

.stock-header h1 {
    margin: 0;
    color: var(--stock-text);
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -.02em;
}

.stock-header p {
    margin: 3px 0 0;
    color: var(--stock-muted);
    font-size: 13px;
}


/* KPI GRID */

.stock-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
}

.stock-kpi {
    position: relative;
    overflow: hidden;
    min-height: 142px;
    padding: 20px;
    border-radius: 20px;
    border: 1px solid var(--stock-border);
    background: var(--stock-card);
    box-shadow: 0 10px 30px rgba(15,23,42,.06);
    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;
}

.dark .stock-kpi {
    box-shadow:
        0 15px 45px rgba(0,0,0,.25),
        inset 0 1px 0 rgba(255,255,255,.025);
}

.stock-kpi:hover {
    transform: translateY(-4px);
    border-color: rgba(99,102,241,.25);
    box-shadow: 0 18px 40px rgba(15,23,42,.10);
}

.stock-kpi::after {
    content: "";
    position: absolute;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    right: -45px;
    bottom: -55px;
    background: rgba(99,102,241,.08);
    pointer-events: none;
}

.stock-kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    font-size: 17px;
}

.stock-kpi-icon.blue {
    background: rgba(59,130,246,.12);
    color: #3b82f6;
}

.stock-kpi-icon.green {
    background: rgba(16,185,129,.12);
    color: #10b981;
}

.stock-kpi-icon.purple {
    background: rgba(139,92,246,.12);
    color: #8b5cf6;
}

.stock-kpi-icon.amber {
    background: rgba(245,158,11,.12);
    color: #f59e0b;
}

.stock-kpi-label {
    color: var(--stock-muted);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
}

.stock-kpi-value {
    margin-top: 5px;
    color: var(--stock-text);
    font-size: clamp(22px, 2.2vw, 31px);
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -.03em;
    overflow-wrap: anywhere;
}

.stock-kpi-profit {
    color: var(--stock-success);
}


/* FILTER PANEL */

.stock-filter-panel {
    margin-top: 22px;
    padding: 18px;
    border: 1px solid var(--stock-border);
    background: var(--stock-card);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(15,23,42,.05);
}

.dark .stock-filter-panel {
    box-shadow:
        0 15px 40px rgba(0,0,0,.20),
        inset 0 1px 0 rgba(255,255,255,.025);
}

.stock-filter-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 15px;
}

.stock-filter-heading {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--stock-text);
    font-size: 14px;
    font-weight: 750;
}

.stock-filter-heading i {
    color: var(--stock-accent);
}

.stock-filter-count {
    color: var(--stock-muted);
    font-size: 12px;
    font-weight: 600;
}

.stock-filter-grid {
    display: grid;
    grid-template-columns:
        minmax(220px, 2fr)
        repeat(2, minmax(130px, 1fr))
        minmax(170px, 1.2fr)
        repeat(2, minmax(120px, .8fr));
    gap: 10px;
}

.stock-filter-grid.secondary {
    grid-template-columns:
        minmax(200px, 1fr)
        minmax(200px, 1fr)
        minmax(180px, 1fr)
        auto;
    margin-top: 10px;
}

.stock-field {
    position: relative;
}

.stock-field i {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 13px;
    pointer-events: none;
    z-index: 2;
}

.stock-field input,
.stock-field select {
    width: 100%;
    height: 44px;
    border-radius: 11px;
    border: 1px solid var(--stock-border);
    background: var(--stock-bg);
    color: var(--stock-text);
    outline: none;
    padding: 0 13px 0 37px;
    font-size: 13px;
    transition: .2s ease;
}

.stock-field select {
    cursor: pointer;
}

.stock-field input::placeholder {
    color: #9ca3af;
}

.stock-field input:focus,
.stock-field select:focus {
    border-color: rgba(99,102,241,.55);
    box-shadow: 0 0 0 4px rgba(99,102,241,.09);
}

.stock-clear-btn {
    height: 44px;
    padding: 0 17px;
    border: 1px solid var(--stock-border);
    border-radius: 11px;
    background: transparent;
    color: var(--stock-muted);
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: .2s ease;
    white-space: nowrap;
}

.stock-clear-btn:hover {
    background: rgba(239,68,68,.08);
    color: #ef4444;
    border-color: rgba(239,68,68,.25);
}


/* TABLE WRAPPER */

.stock-table-card {
    margin-top: 22px;
    border: 1px solid var(--stock-border);
    border-radius: 20px;
    overflow: hidden;
    background: var(--stock-card);
    box-shadow: 0 10px 35px rgba(15,23,42,.06);
}

.dark .stock-table-card {
    box-shadow:
        0 18px 55px rgba(0,0,0,.25),
        inset 0 1px 0 rgba(255,255,255,.025);
}

.stock-table-topbar {
    min-height: 58px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--stock-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.stock-table-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--stock-text);
    font-weight: 750;
    font-size: 14px;
}

.stock-table-title i {
    color: var(--stock-accent);
}

.stock-table-hint {
    color: var(--stock-muted);
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 6px;
}


/* SCROLL */

.stock-scroll {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

#top-scroll {
    height: 9px;
    overflow-x: auto;
    overflow-y: hidden;
    background: var(--stock-bg);
}

#top-scroll > div {
    height: 1px;
}

#bottom-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

#bottom-scroll::-webkit-scrollbar,
#top-scroll::-webkit-scrollbar {
    height: 7px;
}

#bottom-scroll::-webkit-scrollbar-track,
#top-scroll::-webkit-scrollbar-track {
    background: transparent;
}

#bottom-scroll::-webkit-scrollbar-thumb,
#top-scroll::-webkit-scrollbar-thumb {
    background: rgba(100,116,139,.35);
    border-radius: 20px;
}


/* TABLE */

.stock-table {
    width: 100%;
    min-width: 1550px;
    border-collapse: separate;
    border-spacing: 0;
}

.stock-table thead th {
    height: 52px;
    padding: 0 15px;
    background: var(--stock-bg);
    color: var(--stock-muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 800;
    white-space: nowrap;
    text-align: left;
    border-bottom: 1px solid var(--stock-border);
}

.stock-table tbody td {
    padding: 14px 15px;
    color: var(--stock-text);
    font-size: 13px;
    white-space: nowrap;
    border-bottom: 1px solid var(--stock-border);
    vertical-align: middle;
}

.stock-table tbody tr {
    transition: background .2s ease;
}

.stock-table tbody tr:hover {
    background: rgba(99,102,241,.035);
}

.stock-table tbody tr:last-child td {
    border-bottom: 0;
}


/* PRODUCT */

.stock-product {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 170px;
}

.stock-product-icon {
    flex: 0 0 34px;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(99,102,241,.10);
    color: var(--stock-accent);
}

.stock-product-name {
    font-weight: 700;
}

.stock-barcode {
    color: var(--stock-muted);
    font-family: monospace;
    font-size: 12px;
}


/* QUANTITY */

.stock-quantity {
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.stock-money {
    font-variant-numeric: tabular-nums;
    font-weight: 650;
}

.stock-money.blue {
    color: var(--stock-blue);
}

.stock-money.green {
    color: var(--stock-success);
}

.stock-money.red {
    color: var(--stock-danger);
}


/* BADGES */

.stock-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 9px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.stock-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.stock-status.low {
    color: #dc2626;
    background: rgba(239,68,68,.10);
}

.stock-status.low::before {
    background: #ef4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,.10);
}

.stock-status.zero {
    color: #6b7280;
    background: rgba(107,114,128,.10);
}

.stock-status.zero::before {
    background: #9ca3af;
}

.stock-status.above {
    color: #059669;
    background: rgba(16,185,129,.10);
}

.stock-status.above::before {
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16,185,129,.10);
}


/* ACTIONS */

.stock-actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.stock-action-form {
    margin: 0;
}

.stock-action {
    height: 36px;
    min-width: 90px;
    padding: 0 11px;
    border: 1px solid transparent;
    border-radius: 9px;
    color: #fff;
    font-size: 11px;
    font-weight: 750;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    cursor: pointer;
    transition:
        transform .2s ease,
        box-shadow .2s ease,
        opacity .2s ease;
}

.stock-action:hover {
    transform: translateY(-2px);
}

.stock-action.history {
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    box-shadow: 0 7px 18px rgba(79,70,229,.20);
}

.stock-action.restock {
    background: linear-gradient(135deg,#0ea5e9,#2563eb);
    box-shadow: 0 7px 18px rgba(37,99,235,.18);
}

.stock-action.reproduce {
    background: linear-gradient(135deg,#8b5cf6,#7c3aed);
    box-shadow: 0 7px 18px rgba(124,58,237,.18);
}

.stock-action.transfer {
    background: linear-gradient(135deg,#10b981,#059669);
    box-shadow: 0 7px 18px rgba(5,150,105,.18);
}

.stock-action.delete {
    background: linear-gradient(135deg,#ef4444,#dc2626);
    box-shadow: 0 7px 18px rgba(220,38,38,.16);
}


/* EMPTY STATE */

.stock-empty {
    padding: 65px 20px !important;
    text-align: center;
    color: var(--stock-muted) !important;
}

.stock-empty-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 15px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(99,102,241,.08);
    color: var(--stock-accent);
    font-size: 25px;
}

.stock-empty-title {
    color: var(--stock-text);
    font-weight: 800;
    font-size: 16px;
}

.stock-empty-text {
    margin-top: 5px;
    font-size: 12px;
}


/* CHART AREA */

.stock-chart-grid {
    display: grid;
    grid-template-columns: repeat(2,minmax(0,1fr));
    gap: 18px;
    margin-top: 22px;
}

.stock-chart-card {
    min-height: 390px;
    padding: 18px;
    border: 1px solid var(--stock-border);
    border-radius: 20px;
    background: var(--stock-card);
    box-shadow: 0 10px 35px rgba(15,23,42,.05);
}

.dark .stock-chart-card {
    box-shadow:
        0 18px 50px rgba(0,0,0,.23),
        inset 0 1px 0 rgba(255,255,255,.025);
}

.stock-chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}

.stock-chart-title {
    color: var(--stock-text);
    font-size: 14px;
    font-weight: 800;
}

.stock-chart-subtitle {
    color: var(--stock-muted);
    font-size: 11px;
    margin-top: 3px;
}

.stock-chart-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(99,102,241,.10);
    color: var(--stock-accent);
}

.stock-chart-wrapper {
    position: relative;
    height: 310px;
}


/* FILTER RESULT */

.stock-no-results {
    display: none;
    padding: 45px 20px;
    text-align: center;
    color: var(--stock-muted);
}

.stock-no-results.show {
    display: block;
}

.stock-no-results i {
    font-size: 27px;
    margin-bottom: 10px;
    color: var(--stock-accent);
}

.stock-no-results strong {
    display: block;
    color: var(--stock-text);
    margin-bottom: 4px;
}


/* RESPONSIVE */

@media (max-width: 1280px) {

    .stock-kpi-grid {
        grid-template-columns: repeat(3,minmax(0,1fr));
    }

    .stock-filter-grid {
        grid-template-columns:
            repeat(3,minmax(0,1fr));
    }

    .stock-filter-grid .search-field {
        grid-column: span 3;
    }

    .stock-filter-grid.secondary {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

    .stock-clear-btn {
        width: 100%;
    }

}


@media (max-width: 900px) {

    .stock-header {
        align-items: flex-start;
    }

    .stock-kpi-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .stock-filter-grid,
    .stock-filter-grid.secondary {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .stock-filter-grid .search-field {
        grid-column: span 2;
    }

    .stock-chart-grid {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 640px) {

    .stock-page {
        width: 100%;
    }

    .stock-header {
        margin-bottom: 15px;
    }

    .stock-terminal-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
    }

    .stock-header h1 {
        font-size: 18px;
    }

    .stock-header p {
        font-size: 11px;
    }

    .stock-kpi-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
        gap: 10px;
    }

    .stock-kpi {
        min-height: 125px;
        padding: 14px;
        border-radius: 16px;
    }

    .stock-kpi-icon {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .stock-kpi-label {
        font-size: 9px;
    }

    .stock-kpi-value {
        font-size: 20px;
    }

    .stock-filter-panel {
        margin-top: 15px;
        padding: 13px;
        border-radius: 16px;
    }

    .stock-filter-title {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }

    .stock-filter-grid,
    .stock-filter-grid.secondary {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .stock-filter-grid .search-field {
        grid-column: auto;
    }

    .stock-field input,
    .stock-field select,
    .stock-clear-btn {
        height: 43px;
    }

    .stock-table-card {
        margin-top: 15px;
        border-radius: 16px;
    }

    .stock-table-topbar {
        padding: 12px 13px;
        min-height: 50px;
    }

    .stock-table-hint {
        display: none;
    }

    .stock-table {
        min-width: 1500px;
    }

    .stock-table thead th {
        height: 46px;
        padding: 0 11px;
    }

    .stock-table tbody td {
        padding: 11px;
        font-size: 12px;
    }

    .stock-chart-grid {
        margin-top: 15px;
        gap: 15px;
    }

    .stock-chart-card {
        min-height: 340px;
        padding: 14px;
        border-radius: 16px;
    }

    .stock-chart-wrapper {
        height: 260px;
    }

}


@media (max-width: 380px) {

    .stock-kpi-grid {
        grid-template-columns: 1fr;
    }

    .stock-kpi {
        min-height: 110px;
    }

}


/* DARK MODE INPUT FIX */

.dark .stock-field input,
.dark .stock-field select {
    color-scheme: dark;
}

.dark .stock-table thead th {
    background: #0b101b;
}

.dark .stock-table tbody tr:hover {
    background: rgba(99,102,241,.06);
}


/* MOBILE TOUCH */

button,
select,
input {
    -webkit-tap-highlight-color: transparent;
}

.stock-action:active,
.stock-clear-btn:active {
    transform: scale(.97);
}


/* REDUCED MOTION */

@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        transition: none !important;
        animation: none !important;
    }

}

</style>

</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>


<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php');?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>


            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                <div class="stock-page">


                    <!-- ==================================================
                         PAGE HEADER
                    ================================================== -->

                    <div class="stock-header">

                        <div class="stock-header-left">

                            <div class="stock-terminal-icon">
                                <i class="fa-solid fa-cubes-stacked"></i>
                            </div>

                            <div>

                                <h1>
                                    Stock Terminal
                                </h1>

                                <p>
                                    Inventory intelligence & stock management
                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- ==================================================
                         KPI CARDS
                    ================================================== -->

                    <div class="stock-kpi-grid">


                        <!-- TOTAL STOCK -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-icon blue">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>

                            <div class="stock-kpi-label">
                                Total Stock
                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalQuantity"
                            >
                                <?= number_format($totalQuantity) ?>
                            </div>

                        </div>


                        <!-- STOCK VALUE -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-icon green">
                                <i class="fa-solid fa-naira-sign"></i>
                            </div>

                            <div class="stock-kpi-label">
                                Stock Value
                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalStockValue"
                            >
                                <?= number_format($totalStockValue, 2) ?>
                            </div>

                        </div>


                        <!-- PRODUCTS -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-icon purple">
                                <i class="fa-solid fa-box-open"></i>
                            </div>

                            <div class="stock-kpi-label">
                                Products Stocked
                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalProducts"
                            >
                                <?= number_format($totalProducts) ?>
                            </div>

                        </div>


                        <!-- CATEGORIES -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-icon amber">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>

                            <div class="stock-kpi-label">
                                Categories
                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalCategories"
                            >
                                <?= number_format($totalCategories) ?>
                            </div>

                        </div>


                        <!-- PROFIT -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-icon green">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>

                            <div class="stock-kpi-label">
                                Net Stock Profit
                            </div>

                            <div
                                class="stock-kpi-value stock-kpi-profit"
                                id="totalStockProfit"
                            >
                                <?= number_format($totalStockProfit, 2) ?>
                            </div>

                        </div>


                    </div>


                    <!-- ==================================================
                         FILTER PANEL
                    ================================================== -->

                    <div class="stock-filter-panel">


                        <div class="stock-filter-title">

                            <div class="stock-filter-heading">

                                <i class="fa-solid fa-sliders"></i>

                                <span>
                                    Inventory Filters
                                </span>

                            </div>

                            <div
                                class="stock-filter-count"
                                id="stockFilterCount"
                            >
                                Showing <?= count($stocks) ?> of <?= count($stocks) ?> records
                            </div>

                        </div>


                        <!-- FIRST FILTER ROW -->

                        <div class="stock-filter-grid">


                            <!-- SEARCH -->

                            <div class="stock-field search-field">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="text"
                                    id="searchInput"
                                    placeholder="Search product or barcode..."
                                    autocomplete="off"
                                >

                            </div>


                            <!-- DATE -->

                            <div class="stock-field">

                                <i class="fa-regular fa-calendar"></i>

                                <input
                                    type="date"
                                    id="dateFilter"
                                >

                            </div>
                            <!-- ASSIGNED BRANCH (READ ONLY) -->
                            <div class="stock-field">
                                <i class="fa-solid fa-location-dot"></i>
                                <input
                                    type="text"
                                    value="<?= htmlspecialchars($branch_location_name, ENT_QUOTES, 'UTF-8') ?>"
                                    aria-label="Your assigned branch"
                                    readonly
                                    title="Your inventory view is restricted to this branch"
                                >
                            </div>

<!-- MIN PRICE -->

                            <div class="stock-field">

                                <i class="fa-solid fa-arrow-down-1-9"></i>

                                <input
                                    type="number"
                                    id="minPrice"
                                    placeholder="Min cost price"
                                    min="0"
                                    step="any"
                                >

                            </div>


                            <!-- MAX PRICE -->

                            <div class="stock-field">

                                <i class="fa-solid fa-arrow-up-1-9"></i>

                                <input
                                    type="number"
                                    id="maxPrice"
                                    placeholder="Max cost price"
                                    min="0"
                                    step="any"
                                >

                            </div>


                            <!-- SUPPLIER -->

                            <div class="stock-field">

                                <i class="fa-solid fa-truck-field"></i>

                                <select id="supplierFilter">

                                    <option value="">
                                        All Suppliers
                                    </option>

                                    <option value="Production">
                                        Production
                                    </option>

                                    <?php

                                    $stmt = $pdo->prepare("
                                        SELECT business_name
                                        FROM vendors
                                        WHERE company_id = ?
                                        ORDER BY business_name
                                    ");

                                    $stmt->execute([$company_id]);

                                    while (
                                        $vendor =
                                        $stmt->fetch(PDO::FETCH_ASSOC)
                                    ) {

                                    ?>

                                        <option
                                            value="<?= htmlspecialchars($vendor['business_name']) ?>"
                                        >
                                            <?= htmlspecialchars($vendor['business_name']) ?>
                                        </option>

                                    <?php

                                    }

                                    ?>

                                </select>

                            </div>


                        </div>


                        <!-- SECOND FILTER ROW -->

                        <div class="stock-filter-grid secondary">


                            <!-- STOCK LEVEL -->

                            <div class="stock-field">

                                <i class="fa-solid fa-signal"></i>

                                <select
                                    name="stock_level"
                                    id="stock_level"
                                >

                                    <option value="">
                                        All Stock Levels
                                    </option>

                                    <option value="low">
                                        Low Stock Below 30%
                                    </option>

                                    <option value="zero">
                                        Out of Stock
                                    </option>

                                    <option value="above">
                                        Above 30%
                                    </option>

                                </select>

                            </div>


                            <!-- RESULT STATUS -->

                            <div
                                class="stock-field"
                                style="
                                    display:flex;
                                    align-items:center;
                                    padding-left:12px;
                                "
                            >

                                <div
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:8px;
                                        color:var(--stock-muted);
                                        font-size:12px;
                                    "
                                >

                                    <i
                                        class="fa-solid fa-circle-info"
                                        style="color:var(--stock-accent);"
                                    ></i>

                                    Filters use combined matching.

                                </div>

                            </div>


                            <!-- CLEAR -->

                            <div></div>

                            <button
                                type="button"
                                id="clearStockFilters"
                                class="stock-clear-btn"
                            >

                                <i class="fa-solid fa-rotate-left"></i>

                                Reset Filters

                            </button>


                        </div>


                    </div>


                    <!-- ==================================================
                         STOCK TABLE
                    ================================================== -->

                    <div class="stock-table-card">


                        <div class="stock-table-topbar">

                            <div class="stock-table-title">

                                <i class="fa-solid fa-table-list"></i>

                                <span>
                                    Current Inventory
                                </span>

                            </div>


                            <div class="stock-table-hint">

                                <i class="fa-solid fa-arrows-left-right"></i>

                                Swipe horizontally to view all columns

                            </div>

                        </div>


                        <!-- TOP SCROLL -->

                        <div id="top-scroll">

                            <div style="width:2000px;height:1px;"></div>

                        </div>


                        <!-- TABLE -->

                        <div id="bottom-scroll">

                            <table class="stock-table">


                                <thead>

                                <tr>

                                    <th>s/n</th>

                                    <th>
                                        Product
                                    </th>

                                    <th>
                                        Barcode
                                    </th>

                                    <th>
                                        Stock History
                                    </th>

                                    <th>
                                        Unit
                                    </th>

                                    <th>
                                        Stock Type
                                    </th>

                                    <th>
                                        Vendor
                                    </th>

                                    <th>
                                        Quantity
                                    </th>

                                    <th>
                                        Location
                                    </th>

                                    <th>
                                        Cost Price
                                    </th>

                                    <th>
                                        Selling Price
                                    </th>

                                    <th>
                                        Total Stock Value
                                    </th>

                                    <th>
                                        Profit
                                    </th>

                                    <th>
                                        Availability
                                    </th>

                                    <th>
                                        Date & Time
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                                </thead>


                                <tbody id="stockTableBody">


                                <?php

                                $count = 1;

                                foreach($stocks as $row):


                                    $totalCostValue =
                                        $row['quantity']
                                        *
                                        $row['cost_price'];


                                    $totalSellingValue =
                                        $row['quantity']
                                        *
                                        $row['selling_price'];


                                    $profitValue =
                                        (
                                            $row['selling_price']
                                            -
                                            $row['cost_price']
                                        )
                                        *
                                        $row['quantity'];


                                    $currentStock =
                                        (float)$row['quantity'];


                                    $lastStockIn =
                                        (float)$row['last_stock_in_quantity'];


                                    $stockLevel = 'above';


                                    if($currentStock == 0){

                                        $stockLevel = 'zero';

                                    } elseif(
                                        $lastStockIn > 0 &&
                                        $currentStock < ($lastStockIn * 0.30)
                                    ){

                                        $stockLevel = 'low';

                                    } else {

                                        $stockLevel = 'above';

                                    }

                                ?>


                                <tr
                                    data-stock-level="<?= htmlspecialchars($stockLevel) ?>"
                                    data-product-id="<?= htmlspecialchars($row['product_id']) ?>"
                                    data-category="<?= htmlspecialchars($row['category_name'] ?? '') ?>"
                                    data-quantity="<?= htmlspecialchars($row['quantity']) ?>"
                                    data-cost-value="<?= htmlspecialchars($totalCostValue) ?>"
                                    data-profit-value="<?= htmlspecialchars($profitValue) ?>"
                                >


                                    <!-- S/N -->

                                    <td>
                                        <?= htmlspecialchars($count++) ?>
                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="stock-product">

                                            <div class="stock-product-icon">

                                                <i class="fa-solid fa-box"></i>

                                            </div>

                                            <div class="stock-product-name">

                                                <?= htmlspecialchars($row['name']) ?>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- BARCODE -->

                                    <td>

                                        <span class="stock-barcode">

                                            <?= htmlspecialchars($row['barcode']) ?>

                                        </span>

                                    </td>


                                    <!-- HISTORY -->

                                    <td>

                                        <div class="stock-actions">


                                            <form
                                                method="POST"
                                                action="stock-history.php"
                                                class="stock-action-form"
                                                onsubmit="return confirm('Are you certain you want to proceed to view the historical data of your preferred stock?.');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="stock_id"
                                                    value="<?= $row['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="stock-action history"
                                                >

                                                    <i class="fa-solid fa-clock-rotate-left"></i>

                                                    History

                                                </button>

                                            </form>


                                            <?php if($row['supplier'] === 'Production'): ?>


                                                <form
                                                    method="POST"
                                                    action="reproduce.php"
                                                    class="stock-action-form"
                                                    onsubmit="return confirm('Are you certain you want to reproduce this product?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="stock_id"
                                                        value="<?= $row['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="stock-action reproduce"
                                                    >

                                                        <i class="fa-solid fa-arrows-rotate"></i>

                                                        Reproduce

                                                    </button>

                                                </form>


                                            <?php else: ?>


                                                <form
                                                    method="POST"
                                                    action="restock.php"
                                                    class="stock-action-form"
                                                    onsubmit="return confirm('Are you certain you want to restock this product?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="stock_id"
                                                        value="<?= $row['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="stock-action restock"
                                                    >

                                                        <i class="fa-solid fa-arrows-rotate"></i>

                                                        Restock

                                                    </button>

                                                </form>


                                            <?php endif; ?>


                                        </div>

                                    </td>


                                    <!-- UNIT -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['unit_name'] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- STOCK TYPE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['stock_type'] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- VENDOR -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['supplier_name'] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- QUANTITY -->

                                    <td>

                                        <span class="stock-quantity">

                                            <?= htmlspecialchars(
                                                $row['quantity']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- LOCATION -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['location_name']
                                        ) ?>

                                    </td>


                                    <!-- COST -->

                                    <td>

                                        <span class="stock-money">

                                            <?= number_format(
                                                $row['cost_price'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- SELLING -->

                                    <td>

                                        <span class="stock-money">

                                            <?= number_format(
                                                $row['selling_price'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- VALUE -->

                                    <td>

                                        <span class="stock-money blue">

                                            <?= number_format(
                                                $totalSellingValue,
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PROFIT -->

                                    <td>

                                        <span
                                            class="
                                                stock-money
                                                <?= $profitValue >= 0
                                                    ? 'green'
                                                    : 'red'
                                                ?>
                                            "
                                        >

                                            <?= number_format(
                                                $profitValue,
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- AVAILABILITY -->

                                    <td>

                                        <?php if($stockLevel === 'low'): ?>

                                            <span class="stock-status low">

                                                Low Stock

                                            </span>


                                        <?php elseif($stockLevel === 'zero'): ?>

                                            <span class="stock-status zero">

                                                Out of Stock

                                            </span>


                                        <?php else: ?>

                                            <span class="stock-status above">

                                                In Stock

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['created_at']
                                        ) ?>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="stock-actions">
                                            <span class="stock-status above" title="Branch Managers cannot transfer or delete stock">
                                                <i class="fa-solid fa-lock"></i>
                                                Restricted
                                            </span>
                                        </div>
                                    </td>


                                </tr>


                                <?php endforeach; ?>


                                </tbody>

                            </table>


                            <!-- NO FILTER RESULTS -->

                            <div
                                id="stockNoResults"
                                class="stock-no-results"
                            >

                                <i class="fa-solid fa-filter-circle-xmark"></i>

                                <strong>
                                    No matching stock records
                                </strong>

                                <span>
                                    Try adjusting or clearing your filters.
                                </span>

                            </div>


                        </div>

                    </div>


                    <!-- ==================================================
                         CHARTS
                    ================================================== -->

                    <div class="stock-chart-grid">


                        <!-- CATEGORY CHART -->

                        <div class="stock-chart-card">

                            <div class="stock-chart-header">

                                <div>

                                    <div class="stock-chart-title">

                                        Stock Distribution

                                    </div>

                                    <div class="stock-chart-subtitle">

                                        Quantity distribution by category

                                    </div>

                                </div>

                                <div class="stock-chart-icon">

                                    <i class="fa-solid fa-chart-pie"></i>

                                </div>

                            </div>


                            <div class="stock-chart-wrapper">

                                <canvas id="categoryChart"></canvas>

                            </div>

                        </div>


                        <!-- TREND CHART -->

                        <div class="stock-chart-card">

                            <div class="stock-chart-header">

                                <div>

                                    <div class="stock-chart-title">

                                        Stock Activity

                                    </div>

                                    <div class="stock-chart-subtitle">

                                        30-day stock movement trend

                                    </div>

                                </div>

                                <div class="stock-chart-icon">

                                    <i class="fa-solid fa-chart-line"></i>

                                </div>

                            </div>


                            <div class="stock-chart-wrapper">

                                <canvas id="stockTrendChart"></canvas>

                            </div>

                        </div>


                    </div>


                </div>

            </div>

        </main>

    </div>

</div>


<?php include('footer.php'); ?>


<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script defer src="bundle.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

/* ============================================================
   STOCK TERMINAL JS
   ============================================================ */


function animateValue(
    id,
    start,
    end,
    duration,
    decimals = 0
) {

    const obj = document.getElementById(id);

    if (!obj) return;

    let startTime = null;


    function animation(timestamp) {

        if (!startTime) {
            startTime = timestamp;
        }


        const progress =
            Math.min(
                (timestamp - startTime) / duration,
                1
            );


        const eased =
            1 -
            Math.pow(
                1 - progress,
                3
            );


        const current =
            start +
            (end - start) *
            eased;


        obj.textContent =
            Number(current).toLocaleString(
                'en-NG',
                {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }
            );


        if (progress < 1) {

            requestAnimationFrame(animation);

        }

    }


    requestAnimationFrame(animation);

}


/* ============================================================
   INITIAL KPI ANIMATION
   ============================================================ */


animateValue(
    "totalQuantity",
    0,
    <?= json_encode((float)$totalQuantity) ?>,
    600
);


animateValue(
    "totalStockValue",
    0,
    <?= json_encode((float)$totalStockValue) ?>,
    600,
    2
);


animateValue(
    "totalProducts",
    0,
    <?= json_encode((float)$totalProducts) ?>,
    600
);


animateValue(
    "totalCategories",
    0,
    <?= json_encode((float)$totalCategories) ?>,
    600
);


animateValue(
    "totalStockProfit",
    0,
    <?= json_encode((float)$totalStockProfit) ?>,
    600,
    2
);


/* ============================================================
   CHART DEFAULTS
   ============================================================ */


const chartTextColor =
    document.body.classList.contains('dark')
        ? '#94a3b8'
        : '#6b7280';


const chartGridColor =
    document.body.classList.contains('dark')
        ? 'rgba(148,163,184,.08)'
        : 'rgba(100,116,139,.10)';


/* ============================================================
   CATEGORY CHART
   ============================================================ */


new Chart(
    document.getElementById('categoryChart'),
    {

        type: 'doughnut',

        data: {

            labels:
                <?= json_encode($categoriesLabels) ?>,

            datasets: [{

                data:
                    <?= json_encode($categoriesData) ?>,

                backgroundColor: [
                    '#6366f1',
                    '#8b5cf6',
                    '#06b6d4',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#3b82f6',
                    '#ec4899'
                ],

                borderWidth: 0,

                hoverOffset: 8

            }]

        },


        options: {

            responsive: true,

            maintainAspectRatio: false,

            cutout: '68%',

            plugins: {

                legend: {

                    position: 'bottom',

                    labels: {

                        color: chartTextColor,

                        usePointStyle: true,

                        pointStyle: 'circle',

                        padding: 15,

                        font: {

                            size: 11

                        }

                    }

                }

            },

            animation: {

                animateScale: true,

                animateRotate: true,

                duration: 1100

            }

        }

    }
);


/* ============================================================
   STOCK TREND CHART
   ============================================================ */


new Chart(
    document.getElementById('stockTrendChart'),
    {

        type: 'line',

        data: {

            labels:
                <?= json_encode(array_reverse($trendLabels)) ?>,

            datasets: [{

                label: 'Stock Quantity',

                data:
                    <?= json_encode(array_reverse($trendData)) ?>,

                fill: true,

                backgroundColor:
                    'rgba(99,102,241,.10)',

                borderColor:
                    '#6366f1',

                borderWidth: 2,

                pointRadius: 3,

                pointHoverRadius: 6,

                tension: .4

            }]

        },


        options: {

            responsive: true,

            maintainAspectRatio: false,

            interaction: {

                intersect: false,

                mode: 'index'

            },

            plugins: {

                legend: {

                    display: false

                }

            },

            scales: {

                x: {

                    ticks: {

                        color: chartTextColor,

                        maxTicksLimit: 7,

                        font: {

                            size: 10

                        }

                    },

                    grid: {

                        display: false

                    }

                },

                y: {

                    beginAtZero: true,

                    ticks: {

                        color: chartTextColor,

                        font: {

                            size: 10

                        }

                    },

                    grid: {

                        color: chartGridColor

                    }

                }

            },

            animation: {

                duration: 1200,

                easing: 'easeOutQuart'

            }

        }

    }
);


/* ============================================================
   FILTER + KPI ENGINE
   ============================================================ */


document.addEventListener(
    'DOMContentLoaded',
    function () {


        const tableBody =
            document.getElementById(
                'stockTableBody'
            );


        const rows =
            Array.from(
                tableBody.querySelectorAll(
                    'tr[data-product-id]'
                )
            );


        const searchInput =
            document.getElementById(
                'searchInput'
            );


        const dateFilter =
            document.getElementById(
                'dateFilter'
            );
const supplierFilter =
            document.getElementById(
                'supplierFilter'
            );


        const minPrice =
            document.getElementById(
                'minPrice'
            );


        const maxPrice =
            document.getElementById(
                'maxPrice'
            );


        const stockLevel =
            document.getElementById(
                'stock_level'
            );


        const clearButton =
            document.getElementById(
                'clearStockFilters'
            );


        const filterCount =
            document.getElementById(
                'stockFilterCount'
            );


        const noResults =
            document.getElementById(
                'stockNoResults'
            );


        const totalRows =
            rows.length;


        function formatNumber(
            value,
            decimals = 0
        ) {

            return Number(value)
                .toLocaleString(
                    'en-NG',
                    {
                        minimumFractionDigits:
                            decimals,

                        maximumFractionDigits:
                            decimals
                    }
                );

        }


        /* ========================================================
           KPI UPDATE
        ======================================================== */


        function updateKPIs(
            visibleRows
        ) {


            let totalQuantity = 0;

            let totalStockValue = 0;

            let totalStockProfit = 0;

            const products =
                new Set();

            const categories =
                new Set();


            visibleRows.forEach(
                row => {


                    const quantity =
                        parseFloat(
                            row.dataset.quantity
                        ) || 0;


                    const costValue =
                        parseFloat(
                            row.dataset.costValue
                        ) || 0;


                    const profitValue =
                        parseFloat(
                            row.dataset.profitValue
                        ) || 0;


                    const productId =
                        row.dataset.productId;


                    const category =
                        row.dataset.category;


                    totalQuantity +=
                        quantity;


                    totalStockValue +=
                        costValue;


                    totalStockProfit +=
                        profitValue;


                    if(productId){

                        products.add(
                            productId
                        );

                    }


                    if(
                        category &&
                        category !== '-'
                    ){

                        categories.add(
                            category
                        );

                    }

                }
            );


            document.getElementById(
                'totalQuantity'
            ).textContent =
                formatNumber(
                    totalQuantity
                );


            document.getElementById(
                'totalStockValue'
            ).textContent =
                formatNumber(
                    totalStockValue,
                    2
                );


            document.getElementById(
                'totalProducts'
            ).textContent =
                formatNumber(
                    products.size
                );


            document.getElementById(
                'totalCategories'
            ).textContent =
                formatNumber(
                    categories.size
                );


            document.getElementById(
                'totalStockProfit'
            ).textContent =
                formatNumber(
                    totalStockProfit,
                    2
                );

        }


        /* ========================================================
           FILTER
        ======================================================== */


        function filterTable() {


            const searchVal =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const dateVal =
                dateFilter.value;
const supplierVal =
                supplierFilter.value
                    .toLowerCase()
                    .trim();


            const stockLevelVal =
                stockLevel.value
                    .toLowerCase()
                    .trim();


            const min =
                minPrice.value !== ''
                    ? parseFloat(
                        minPrice.value
                    )
                    : -Infinity;


            const max =
                maxPrice.value !== ''
                    ? parseFloat(
                        maxPrice.value
                    )
                    : Infinity;


            const visibleRows = [];


            rows.forEach(
                row => {


                    const cells =
                        row.querySelectorAll(
                            'td'
                        );


                    if(
                        cells.length < 16
                    ){

                        return;

                    }


                    const product =
                        cells[1]
                            ?.textContent
                            .toLowerCase()
                            .trim()
                        || '';


                    const barcode =
                        cells[2]
                            ?.textContent
                            .toLowerCase()
                            .trim()
                        || '';


                    const supplier =
                        cells[6]
                            ?.textContent
                            .toLowerCase()
                            .trim()
                        || '';


                    const location =
                        cells[8]
                            ?.textContent
                            .toLowerCase()
                            .trim()
                        || '';


                    const costPrice =
                        parseFloat(
                            cells[9]
                                ?.textContent
                                .replace(
                                    /,/g,
                                    ''
                                )
                        ) || 0;


                    const lastUpdated =
                        cells[14]
                            ?.textContent
                            .trim()
                        || '';


                    const rowStockLevel =
                        row.dataset.stockLevel
                        || '';


                    const matchesSearch =
                        !searchVal
                        ||
                        product.includes(
                            searchVal
                        )
                        ||
                        barcode.includes(
                            searchVal
                        );


                    const matchesSupplier =
                        !supplierVal
                        ||
                        supplier ===
                            supplierVal;
const matchesPrice =
                        costPrice >= min
                        &&
                        costPrice <= max;


                    const matchesDate =
                        !dateVal
                        ||
                        lastUpdated.substring(
                            0,
                            10
                        ) === dateVal;


                    const matchesStockLevel =
                        !stockLevelVal
                        ||
                        rowStockLevel ===
                            stockLevelVal;


                    const matches =
                        matchesSearch
                        &&
                        matchesSupplier
                        &&
                        matchesPrice
                        &&
                        matchesDate
                        &&
                        matchesStockLevel;


                    row.style.display =
                        matches
                            ? ''
                            : 'none';


                    if(matches){

                        visibleRows.push(
                            row
                        );

                    }

                }
            );


            updateKPIs(
                visibleRows
            );


            filterCount.textContent =
                `Showing ${visibleRows.length} of ${totalRows} records`;


            if(
                noResults
            ){

                noResults.classList.toggle(
                    'show',
                    visibleRows.length === 0
                );

            }

        }


        /* ========================================================
           CLEAR FILTERS
        ======================================================== */


        function clearFilters() {


            searchInput.value = '';

            dateFilter.value = '';
supplierFilter.value = '';

            minPrice.value = '';

            maxPrice.value = '';

            stockLevel.value = '';


            filterTable();

        }


        /* ========================================================
           EVENTS
        ======================================================== */


        searchInput.addEventListener(
            'input',
            filterTable
        );


        dateFilter.addEventListener(
            'change',
            filterTable
        );
supplierFilter.addEventListener(
            'change',
            filterTable
        );


        minPrice.addEventListener(
            'input',
            filterTable
        );


        maxPrice.addEventListener(
            'input',
            filterTable
        );


        stockLevel.addEventListener(
            'change',
            filterTable
        );


        clearButton.addEventListener(
            'click',
            clearFilters
        );


        /* INITIAL FILTER */

        filterTable();

    }
);


/* ============================================================
   SYNCHRONIZED TABLE SCROLL
   ============================================================ */


const topScroll =
    document.getElementById(
        'top-scroll'
    );


const bottomScroll =
    document.getElementById(
        'bottom-scroll'
    );


if(
    topScroll &&
    bottomScroll
){

    let syncingTop = false;

    let syncingBottom = false;


    topScroll.addEventListener(
        'scroll',
        () => {

            if(syncingTop){

                syncingTop = false;

                return;

            }

            syncingBottom = true;

            bottomScroll.scrollLeft =
                topScroll.scrollLeft;

        }
    );


    bottomScroll.addEventListener(
        'scroll',
        () => {

            if(syncingBottom){

                syncingBottom = false;

                return;

            }

            syncingTop = true;

            topScroll.scrollLeft =
                bottomScroll.scrollLeft;

        }
    );


    function syncTableWidth(){

        const table =
            bottomScroll.querySelector(
                'table'
            );


        const spacer =
            topScroll.firstElementChild;


        if(
            table &&
            spacer
        ){

            spacer.style.width =
                table.scrollWidth +
                'px';

        }

    }


    window.addEventListener(
        'load',
        syncTableWidth
    );


    window.addEventListener(
        'resize',
        syncTableWidth
    );

}

</script>


</body>
</html>
