<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

/*
|--------------------------------------------------------------------------
| BRANCH MANAGER ACCESS CONTROL
|--------------------------------------------------------------------------
| The authenticated users row is the authority for company_id/location_id.
| Never accept branch scope from GET, POST, or browser-side values.
*/
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$session_user_id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

if (!$session_user_id || $session_user_id < 1) {
    http_response_code(403);
    exit('Access denied. Please sign in again.');
}

$authStmt = $pdo->prepare("
    SELECT
        u.id,
        u.company_id,
        u.location_id,
        u.role,
        u.status,
        l.name AS branch_name,
        l.status AS location_status
    FROM users u
    INNER JOIN locations l
        ON l.id = u.location_id
       AND l.company_id = u.company_id
    WHERE u.id = ?
    LIMIT 1
");
$authStmt->execute([$session_user_id]);
$branchManager = $authStmt->fetch(PDO::FETCH_ASSOC);

if (
    !$branchManager ||
    strcasecmp(trim((string)($branchManager['role'] ?? '')), 'Branch Manager') !== 0 ||
    strcasecmp(trim((string)($branchManager['status'] ?? '')), 'active') !== 0 ||
    (int)$branchManager['company_id'] < 1 ||
    (int)$branchManager['location_id'] < 1
) {
    http_response_code(403);
    exit('Access denied. This page is available only to an active Branch Manager assigned to an active branch.');
}

/* Trusted scope: always taken from the authenticated database record. */
$company_id = (int)$branchManager['company_id'];
$location_id = (int)$branchManager['location_id'];
$user_id = (int)$branchManager['id'];
$user_email = (string)($_SESSION['user_email'] ?? '');

$branch_name = (string)$branchManager['branch_name'];

/*
|--------------------------------------------------------------------------
| BRANCH-SCOPED MOVEMENT LEDGER
|--------------------------------------------------------------------------
| A movement is visible only when this manager's assigned branch is its
| source or destination. The opposite endpoint is retained as transaction
| context; it does not grant access to that other branch's records.
*/
$sql = "
SELECT
    sm.id,
    sm.company_id,
    sm.product_id,
    sm.stock_id,
    sm.from_location_id,
    sm.to_location_id,
    sm.transfer_ID,
    sm.quantity,
    sm.movement_type,
    sm.reference,
    sm.created_by,
    sm.created_at,
    p.name AS product_name,
    p.barcode,
    fl.name AS from_location_name,
    tl.name AS to_location_name,
    u.username AS created_by_name
FROM stock_movements sm
LEFT JOIN products p
    ON p.id = sm.product_id
   AND p.company_id = sm.company_id
LEFT JOIN locations fl
    ON fl.id = sm.from_location_id
   AND fl.company_id = sm.company_id
LEFT JOIN locations tl
    ON tl.id = sm.to_location_id
   AND tl.company_id = sm.company_id
LEFT JOIN users u
    ON u.id = sm.created_by
   AND u.company_id = sm.company_id
WHERE sm.company_id = ?
  AND (
        sm.from_location_id = ?
        OR sm.to_location_id = ?
  )
ORDER BY sm.created_at DESC, sm.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$company_id, $location_id, $location_id]);
$stock_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* KPIs are calculated only from movements visible to this branch. */
$total_movements = count($stock_movements);
$total_quantity_moved = 0.0;
$total_transfers = 0;
$total_stock_in = 0.0;
$total_stock_out = 0.0;

foreach ($stock_movements as $movement) {
    $quantity = (float)$movement['quantity'];
    $total_quantity_moved += $quantity;
    $type = strtolower(trim((string)($movement['movement_type'] ?? '')));

    if (in_array($type, ['stock transfer', 'transfer'], true)) {
        $total_transfers++;
    }
    if (in_array($type, ['in', 'stock_in', 'stock in'], true)) {
        $total_stock_in += $quantity;
    }
    if (in_array($type, ['out', 'stock_out', 'stock out'], true)) {
        $total_stock_out += $quantity;
    }
}

/* Only products with movement activity involving this branch are selectable. */
$stmtProducts = $pdo->prepare("
    SELECT DISTINCT p.id, p.name
    FROM products p
    INNER JOIN stock_movements sm
        ON sm.product_id = p.id
       AND sm.company_id = p.company_id
    WHERE p.company_id = ?
      AND (
            sm.from_location_id = ?
            OR sm.to_location_id = ?
      )
    ORDER BY p.name ASC
");
$stmtProducts->execute([$company_id, $location_id, $location_id]);
$movement_products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

/* Do not disclose the company's other branches in Branch Manager filters. */
$movement_locations = [
    [
        'id' => $location_id,
        'name' => $branch_name
    ]
];

/* Movement types are also limited to this branch's ledger. */
$stmtTypes = $pdo->prepare("
    SELECT DISTINCT movement_type
    FROM stock_movements
    WHERE company_id = ?
      AND (
            from_location_id = ?
            OR to_location_id = ?
      )
      AND movement_type IS NOT NULL
      AND movement_type != ''
    ORDER BY movement_type ASC
");
$stmtTypes->execute([$company_id, $location_id, $location_id]);
$movement_types = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);
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
    content="width=device-width, initial-scale=1.0"
/>

<meta
    http-equiv="X-UA-Compatible"
    content="ie=edge"
/>

<title>
    <?php echo htmlspecialchars($company_name); ?> | Stock Movements
</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">

<script data-cfasync="false" nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86">
try{
(function(w,d){
!function(fv,fw,fx,fy){
if(fv.zaraz)console.error("zaraz is loaded twice");
else{
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
fv.zaraz.q.push({
m:fz,
a:fA
})
}
};
for(const fB of["track","set","debug"])
fv.zaraz[fB]=fv.zaraz._f(fB);
fv.zaraz.init=()=>{};
}
}(w,d,"zarazData","script")
})(window,document)
}catch(e){}
</script>


<style>

/* =========================================================
   FUTURISTIC STOCK MOVEMENT UI
   UI ONLY — NO DATABASE LOGIC
   ========================================================= */

:root {
    --fm-bg: #070a12;
    --fm-panel: rgba(13, 18, 31, .88);
    --fm-panel-soft: rgba(18, 25, 42, .78);
    --fm-border: rgba(148, 163, 184, .14);
    --fm-border-bright: rgba(245, 158, 11, .28);
    --fm-text: #f8fafc;
    --fm-muted: #94a3b8;
    --fm-gold: #f59e0b;
    --fm-gold-light: #fbbf24;
    --fm-green: #22c55e;
    --fm-blue: #38bdf8;
    --fm-red: #ef4444;
}

/* ---------------------------------------------------------
   PAGE BACKGROUND
   --------------------------------------------------------- */

.stock-movement-page {
    min-height: 100%;
    background:
        radial-gradient(
            circle at 15% 10%,
            rgba(245, 158, 11, .07),
            transparent 28%
        ),
        radial-gradient(
            circle at 85% 20%,
            rgba(56, 189, 248, .06),
            transparent 28%
        ),
        linear-gradient(
            135deg,
            #05070d 0%,
            #090d18 50%,
            #05070d 100%
        );
}

/* ---------------------------------------------------------
   PAGE HEADER
   --------------------------------------------------------- */

.movement-hero {
    position: relative;
    overflow: hidden;
    margin-bottom: 24px;
    padding: 24px;
    border: 1px solid var(--fm-border);
    border-radius: 22px;
    background:
        linear-gradient(
            135deg,
            rgba(15, 23, 42, .94),
            rgba(8, 12, 23, .96)
        );
    box-shadow:
        0 20px 60px rgba(0, 0, 0, .28),
        inset 0 1px 0 rgba(255,255,255,.035);
}

.movement-hero::before {
    content: "";
    position: absolute;
    width: 240px;
    height: 240px;
    right: -100px;
    top: -120px;
    border-radius: 50%;
    background: rgba(245, 158, 11, .08);
    filter: blur(5px);
}

.movement-hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.movement-title-wrap {
    display: flex;
    align-items: center;
    gap: 15px;
}

.movement-title-icon {
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    color: #111827;
    background:
        linear-gradient(
            135deg,
            var(--fm-gold-light),
            var(--fm-gold)
        );
    box-shadow:
        0 10px 35px rgba(245, 158, 11, .18);
    font-size: 21px;
}

.movement-title {
    margin: 0;
    color: var(--fm-text);
    font-size: clamp(20px, 3vw, 28px);
    font-weight: 800;
    letter-spacing: -.03em;
}

.movement-subtitle {
    margin: 5px 0 0;
    color: var(--fm-muted);
    font-size: 13px;
}

.movement-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 13px;
    border: 1px solid rgba(34,197,94,.18);
    border-radius: 999px;
    background: rgba(34,197,94,.07);
    color: #86efac;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.movement-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--fm-green);
    box-shadow: 0 0 12px rgba(34,197,94,.8);
}

/* ---------------------------------------------------------
   KPI GRID
   --------------------------------------------------------- */

.movement-kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}

.movement-kpi {
    position: relative;
    overflow: hidden;
    min-width: 0;
    padding: 21px;
    border: 1px solid var(--fm-border);
    border-radius: 20px;
    background:
        linear-gradient(
            145deg,
            rgba(17, 24, 39, .96),
            rgba(8, 12, 23, .96)
        );
    box-shadow:
        0 15px 45px rgba(0,0,0,.22),
        inset 0 1px 0 rgba(255,255,255,.025);
    transition:
        transform .25s ease,
        border-color .25s ease,
        box-shadow .25s ease;
}

.movement-kpi:hover {
    transform: translateY(-4px);
    border-color: var(--fm-border-bright);
    box-shadow:
        0 20px 55px rgba(0,0,0,.32),
        0 0 35px rgba(245,158,11,.05);
}

.movement-kpi::after {
    content: "";
    position: absolute;
    width: 100px;
    height: 100px;
    right: -45px;
    bottom: -50px;
    border-radius: 50%;
    background: rgba(245,158,11,.055);
}

.kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.kpi-label {
    color: var(--fm-muted);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.kpi-icon {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: var(--fm-gold-light);
    background: rgba(245,158,11,.09);
    border: 1px solid rgba(245,158,11,.12);
}

.kpi-value {
    margin-top: 13px;
    color: #f8fafc;
    font-size: clamp(24px, 4vw, 34px);
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.04em;
}

.kpi-foot {
    margin-top: 10px;
    color: #64748b;
    font-size: 11px;
}

/* ---------------------------------------------------------
   FILTER PANEL
   --------------------------------------------------------- */

.movement-filter-panel {
    margin-bottom: 22px;
    padding: 20px;
    border: 1px solid var(--fm-border);
    border-radius: 20px;
    background:
        linear-gradient(
            145deg,
            rgba(15,23,42,.92),
            rgba(8,12,23,.95)
        );
    box-shadow:
        0 15px 45px rgba(0,0,0,.20);
}

.filter-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.filter-heading-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-heading-icon {
    color: var(--fm-gold);
    font-size: 15px;
}

.filter-heading-title {
    color: #e2e8f0;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.filter-heading-note {
    color: #64748b;
    font-size: 11px;
}

.filter-grid {
    display: grid;
    grid-template-columns:
        minmax(230px, 1.7fr)
        repeat(2, minmax(150px, 1fr))
        auto;
    gap: 10px;
}

.filter-grid-secondary {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(180px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.movement-control {
    position: relative;
    min-width: 0;
}

.movement-control-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    z-index: 2;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 13px;
    pointer-events: none;
}

.movement-control input,
.movement-control select {
    width: 100%;
    min-height: 46px;
    padding: 0 14px 0 39px;
    border: 1px solid rgba(148,163,184,.15);
    border-radius: 12px;
    outline: none;
    background: rgba(2,6,23,.68);
    color: #e2e8f0;
    font-size: 13px;
    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

.movement-control select {
    cursor: pointer;
}

.movement-control input::placeholder {
    color: #64748b;
}

.movement-control input:focus,
.movement-control select:focus {
    border-color: rgba(245,158,11,.55);
    background: rgba(2,6,23,.9);
    box-shadow:
        0 0 0 3px rgba(245,158,11,.08),
        0 0 25px rgba(245,158,11,.04);
}

.movement-control input[type="date"] {
    color-scheme: dark;
}

.clear-filter-btn {
    min-height: 46px;
    padding: 0 17px;
    border: 1px solid rgba(239,68,68,.18);
    border-radius: 12px;
    background: rgba(239,68,68,.07);
    color: #fca5a5;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    transition: all .2s ease;
    white-space: nowrap;
}

.clear-filter-btn:hover {
    border-color: rgba(239,68,68,.35);
    background: rgba(239,68,68,.12);
    transform: translateY(-1px);
}

.filter-count {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
    color: #64748b;
    font-size: 12px;
}

.filter-count i {
    color: var(--fm-gold);
}

/* ---------------------------------------------------------
   TABLE CONTAINER
   --------------------------------------------------------- */

.movement-table-shell {
    overflow: hidden;
    border: 1px solid var(--fm-border);
    border-radius: 20px;
    background:
        linear-gradient(
            145deg,
            rgba(15,23,42,.94),
            rgba(5,8,16,.98)
        );
    box-shadow:
        0 20px 60px rgba(0,0,0,.25);
}

.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 17px 20px;
    border-bottom: 1px solid var(--fm-border);
}

.table-toolbar-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #e2e8f0;
    font-size: 13px;
    font-weight: 800;
}

.table-toolbar-title i {
    color: var(--fm-gold);
}

.table-toolbar-badge {
    padding: 6px 9px;
    border: 1px solid rgba(56,189,248,.12);
    border-radius: 999px;
    background: rgba(56,189,248,.06);
    color: #7dd3fc;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.movement-top-scroll,
.movement-bottom-scroll {
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(245,158,11,.35) transparent;
}

.movement-top-scroll::-webkit-scrollbar,
.movement-bottom-scroll::-webkit-scrollbar {
    height: 8px;
}

.movement-top-scroll::-webkit-scrollbar-track,
.movement-bottom-scroll::-webkit-scrollbar-track {
    background: rgba(255,255,255,.02);
}

.movement-top-scroll::-webkit-scrollbar-thumb,
.movement-bottom-scroll::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgba(245,158,11,.35);
}

.movement-top-spacer {
    width: 2000px;
    height: 1px;
}

/* ---------------------------------------------------------
   TABLE
   --------------------------------------------------------- */

.movement-table {
    width: 100%;
    min-width: 1250px;
    border-collapse: separate;
    border-spacing: 0;
}

.movement-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    padding: 14px 15px;
    border-bottom: 1px solid rgba(148,163,184,.12);
    background:
        linear-gradient(
            180deg,
            #111827,
            #0d1422
        );
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .09em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.movement-table thead th:first-child {
    border-top-left-radius: 3px;
}

.movement-table tbody td {
    padding: 14px 15px;
    border-bottom: 1px solid rgba(148,163,184,.07);
    color: #cbd5e1;
    font-size: 12px;
    white-space: nowrap;
    vertical-align: middle;
}

.movement-table tbody tr {
    background: rgba(8,12,23,.48);
    transition:
        background .2s ease,
        box-shadow .2s ease;
}

.movement-table tbody tr:hover {
    background:
        linear-gradient(
            90deg,
            rgba(245,158,11,.045),
            rgba(15,23,42,.75)
        );
    box-shadow:
        inset 3px 0 0 rgba(245,158,11,.65);
}

.movement-table tbody tr:last-child td {
    border-bottom: 0;
}

.sn-cell {
    color: #64748b !important;
    font-weight: 700;
}

.product-cell {
    color: #f1f5f9 !important;
    font-weight: 700;
}

.barcode-cell {
    color: #94a3b8 !important;
    font-family: monospace;
    letter-spacing: .04em;
}

.transfer-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 9px;
    border: 1px solid rgba(56,189,248,.13);
    border-radius: 8px;
    background: rgba(56,189,248,.06);
    color: #7dd3fc;
    font-family: monospace;
    font-size: 11px;
}

.location-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #cbd5e1;
}

.location-badge i {
    color: var(--fm-gold);
    font-size: 10px;
}

.quantity-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 65px;
    padding: 6px 9px;
    border-radius: 8px;
    background: rgba(245,158,11,.07);
    color: #fbbf24;
    font-weight: 800;
}

.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 9px;
    border: 1px solid rgba(34,197,94,.13);
    border-radius: 999px;
    background: rgba(34,197,94,.06);
    color: #86efac;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.reference-cell {
    color: #94a3b8 !important;
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.created-by {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #cbd5e1;
}

.created-by i {
    color: #64748b;
}

.date-cell {
    color: #94a3b8 !important;
    font-family: monospace;
    font-size: 11px !important;
}

/* ---------------------------------------------------------
   EMPTY STATE
   --------------------------------------------------------- */

.movement-empty {
    padding: 70px 20px !important;
    text-align: center !important;
    white-space: normal !important;
}

.empty-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    background: rgba(245,158,11,.07);
    border: 1px solid rgba(245,158,11,.12);
    color: var(--fm-gold);
    font-size: 25px;
}

.empty-title {
    color: #e2e8f0;
    font-size: 16px;
    font-weight: 800;
}

.empty-text {
    margin-top: 6px;
    color: #64748b;
    font-size: 12px;
}

/* ---------------------------------------------------------
   RESPONSIVE
   --------------------------------------------------------- */

@media (max-width: 1100px) {

    .filter-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .filter-grid .search-control {
        grid-column: 1 / -1;
    }

    .filter-grid .clear-filter-btn {
        width: 100%;
    }
}

@media (max-width: 768px) {

    .movement-hero {
        padding: 18px;
        border-radius: 17px;
    }

    .movement-hero-content {
        align-items: flex-start;
        flex-direction: column;
    }

    .movement-status {
        align-self: flex-start;
    }

    .movement-title-icon {
        width: 44px;
        height: 44px;
        flex-basis: 44px;
        border-radius: 12px;
    }

    .movement-kpi-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .movement-kpi {
        padding: 17px;
        border-radius: 16px;
    }

    .kpi-value {
        font-size: 28px;
    }

    .movement-filter-panel {
        padding: 15px;
        border-radius: 16px;
    }

    .filter-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }

    .filter-grid,
    .filter-grid-secondary {
        grid-template-columns: 1fr;
    }

    .filter-grid .search-control {
        grid-column: auto;
    }

    .movement-control input,
    .movement-control select,
    .clear-filter-btn {
        min-height: 48px;
    }

    .movement-table-shell {
        border-radius: 16px;
    }

    .table-toolbar {
        padding: 14px 15px;
    }

    .table-toolbar-badge {
        display: none;
    }

    .movement-bottom-scroll {
        -webkit-overflow-scrolling: touch;
    }

}

@media (max-width: 480px) {

    .movement-title {
        font-size: 20px;
    }

    .movement-subtitle {
        font-size: 11px;
    }

    .movement-status {
        width: 100%;
        justify-content: center;
    }

    .movement-kpi {
        padding: 15px;
    }

    .kpi-label {
        font-size: 10px;
    }

    .kpi-value {
        font-size: 25px;
    }

    .filter-heading-note {
        display: none;
    }

    .table-toolbar-title {
        font-size: 11px;
    }

}

/* ---------------------------------------------------------
   LIGHT MODE SAFETY
   --------------------------------------------------------- */

body:not(.dark) .stock-movement-page {
    background:
        radial-gradient(
            circle at 10% 0%,
            rgba(245,158,11,.07),
            transparent 25%
        ),
        #f4f7fb;
}

body:not(.dark) .movement-hero,
body:not(.dark) .movement-kpi,
body:not(.dark) .movement-filter-panel,
body:not(.dark) .movement-table-shell {
    background:
        linear-gradient(
            145deg,
            #ffffff,
            #f8fafc
        );
}

body:not(.dark) .movement-title,
body:not(.dark) .kpi-value {
    color: #111827;
}

body:not(.dark) .movement-table thead th {
    background: #f8fafc;
    color: #64748b;
}

body:not(.dark) .movement-table tbody tr {
    background: #ffffff;
}

body:not(.dark) .movement-table tbody td {
    color: #334155;
}

body:not(.dark) .movement-control input,
body:not(.dark) .movement-control select {
    background: #ffffff;
    color: #334155;
}

</style>

</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>

<div class="flex h-screen overflow-hidden">

<?php include('sidebar.php'); ?>


<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

<main>

<?php include('header.php'); ?>


<div class="stock-movement-page">

<div class="mx-auto max-w-(--breakpoint-2xl) p-3 sm:p-4 md:p-6">


<!-- =====================================================
     HERO
     ===================================================== -->

<section class="movement-hero">

    <div class="movement-hero-content">

        <div class="movement-title-wrap">

            <div class="movement-title-icon">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
            </div>

            <div>

                <h1 class="movement-title">
                    Stock Movement Intelligence
                </h1>

                <p class="movement-subtitle">
                    Monitor inventory distribution, transfers and movement activity
                </p>

            </div>

        </div>


        <div class="movement-status">

            <span class="movement-status-dot"></span>

            <span>
                Inventory Network Active
            </span>

        </div>

    </div>

</section>


<!-- =====================================================
     KPI CARDS
     ===================================================== -->

<section class="movement-kpi-grid">


    <!-- TOTAL DISTRIBUTION -->

    <div class="movement-kpi">

        <div class="kpi-top">

            <div class="kpi-label">
                Total Distribution
            </div>

            <div class="kpi-icon">
                <i class="fa-solid fa-share-nodes"></i>
            </div>

        </div>

        <div
            class="kpi-value"
            id="totalMovements"
        >
            <?= number_format($total_movements) ?>
        </div>

        <div class="kpi-foot">
            Recorded movement events
        </div>

    </div>


    <!-- TOTAL QUANTITY -->

    <div class="movement-kpi">

        <div class="kpi-top">

            <div class="kpi-label">
                Total Quantity Moved
            </div>

            <div class="kpi-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>

        </div>

        <div
            class="kpi-value"
            id="totalQuantityMoved"
        >
            <?= number_format($total_quantity_moved, 2) ?>
        </div>

        <div class="kpi-foot">
            Units processed through movements
        </div>

    </div>


</section>


<!-- =====================================================
     FILTER PANEL
     ===================================================== -->

<section class="movement-filter-panel">

    <div class="filter-heading">

        <div class="filter-heading-left">

            <i class="fa-solid fa-sliders filter-heading-icon"></i>

            <span class="filter-heading-title">
                Movement Filters
            </span>

        </div>

        <span class="filter-heading-note">
            Combine filters to isolate specific inventory activity
        </span>

    </div>


    <!-- PRIMARY FILTERS -->

    <div class="filter-grid">


        <!-- SEARCH -->

        <div class="movement-control search-control">

            <i class="fa-solid fa-magnifying-glass movement-control-icon"></i>

            <input
                type="text"
                id="searchInput"
                placeholder="Search product, barcode, transfer ID or reference..."
                autocomplete="off"
            >

        </div>


        <!-- DATE -->

        <div class="movement-control">

            <i class="fa-regular fa-calendar movement-control-icon"></i>

            <input
                type="date"
                id="dateFilter"
            >

        </div>


        <!-- FROM -->

        <div class="movement-control">

            <i class="fa-solid fa-location-dot movement-control-icon"></i>

            <select id="fromLocationFilter">

                <option value="">
                    This Branch — Outgoing
                </option>

                <?php foreach ($movement_locations as $location): ?>

                    <option
                        value="<?= htmlspecialchars($location['name']) ?>"
                    >
                        <?= htmlspecialchars($location['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- TO -->

        <div class="movement-control">

            <i class="fa-solid fa-location-arrow movement-control-icon"></i>

            <select id="toLocationFilter">

                <option value="">
                    This Branch — Incoming
                </option>

                <?php foreach ($movement_locations as $location): ?>

                    <option
                        value="<?= htmlspecialchars($location['name']) ?>"
                    >
                        <?= htmlspecialchars($location['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- CLEAR -->

        <button
            type="button"
            id="clearStockFilters"
            class="clear-filter-btn"
        >

            <i class="fa-solid fa-rotate-left"></i>

            <span>
                Clear Filters
            </span>

        </button>

    </div>


    <!-- SECONDARY FILTERS -->

    <div class="filter-grid-secondary">


        <!-- PRODUCT -->

        <div class="movement-control">

            <i class="fa-solid fa-box movement-control-icon"></i>

            <select id="productFilter">

                <option value="">
                    All Products
                </option>

                <?php foreach ($movement_products as $product): ?>

                    <option
                        value="<?= (int)$product['id'] ?>"
                    >
                        <?= htmlspecialchars($product['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- MOVEMENT TYPE -->

        <div class="movement-control">

            <i class="fa-solid fa-right-left movement-control-icon"></i>

            <select id="movementTypeFilter">

                <option value="">
                    All Movement Types
                </option>

                <?php foreach ($movement_types as $movementType): ?>

                    <option
                        value="<?= htmlspecialchars($movementType) ?>"
                    >
                        <?= htmlspecialchars(
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $movementType
                                )
                            )
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


    </div>


    <!-- FILTER RESULT COUNT -->

    <div
        id="movementFilterCount"
        class="filter-count"
    >

        <i class="fa-solid fa-chart-simple"></i>

        Showing
        <?= count($stock_movements) ?>
        of
        <?= count($stock_movements) ?>
        stock movements

    </div>

</section>


<!-- =====================================================
     TABLE
     ===================================================== -->

<section class="movement-table-shell">


    <!-- TABLE TOOLBAR -->

    <div class="table-toolbar">

        <div class="table-toolbar-title">

            <i class="fa-solid fa-clock-rotate-left"></i>

            <span>
                Inventory Movement Ledger
            </span>

        </div>


        <div class="table-toolbar-badge">

            <i class="fa-solid fa-bolt"></i>

            Live Dataset

        </div>

    </div>


    <!-- TOP SCROLL -->

    <div
        id="top-scroll"
        class="movement-top-scroll"
    >

        <div
            class="movement-top-spacer"
        ></div>

    </div>


    <!-- TABLE SCROLL -->

    <div
        id="bottom-scroll"
        class="movement-bottom-scroll"
    >

        <table
            class="movement-table"
        >

            <thead>

                <tr>

                    <th>
                        <i class="fa-solid fa-hashtag"></i>
                        S/N
                    </th>

                    <th>
                        <i class="fa-solid fa-box"></i>
                        Product
                    </th>

                    <th>
                        <i class="fa-solid fa-barcode"></i>
                        Barcode
                    </th>

                    <th>
                        <i class="fa-solid fa-location-dot"></i>
                        From Location
                    </th>

                    <th>
                        <i class="fa-solid fa-location-arrow"></i>
                        To Location
                    </th>

                    <th>
                        <i class="fa-solid fa-fingerprint"></i>
                        Transfer ID
                    </th>

                    <th>
                        <i class="fa-solid fa-cubes"></i>
                        Quantity
                    </th>

                    <th>
                        <i class="fa-solid fa-right-left"></i>
                        Movement Type
                    </th>

                    <th>
                        <i class="fa-solid fa-link"></i>
                        Reference
                    </th>

                    <th>
                        <i class="fa-solid fa-user"></i>
                        Created By
                    </th>

                    <th>
                        <i class="fa-regular fa-clock"></i>
                        Date
                    </th>

                </tr>

            </thead>


            <tbody id="stockTableBody">


            <?php if (!empty($stock_movements)): ?>


                <?php $sn = 1; ?>

                <?php foreach ($stock_movements as $row): ?>


                <tr
                    data-product-id="<?= (int)($row['product_id'] ?? 0) ?>"
                    data-movement-type="<?= htmlspecialchars($row['movement_type'] ?? '') ?>"
                    data-from-location="<?= htmlspecialchars($row['from_location_name'] ?? '') ?>"
                    data-to-location="<?= htmlspecialchars($row['to_location_name'] ?? '') ?>"
                    data-created-at="<?= htmlspecialchars($row['created_at'] ?? '') ?>"
                    data-quantity="<?= htmlspecialchars((float)$row['quantity']) ?>"
                >


                    <!-- S/N -->

                    <td class="sn-cell">

                        <?= htmlspecialchars($sn++) ?>

                    </td>


                    <!-- PRODUCT -->

                    <td class="product-cell">

                        <i
                            class="fa-solid fa-cube"
                            style="color:#f59e0b;margin-right:7px;"
                        ></i>

                        <?= htmlspecialchars(
                            $row['product_name']
                            ?? 'Unknown Product'
                        ) ?>

                    </td>


                    <!-- BARCODE -->

                    <td class="barcode-cell">

                        <?= htmlspecialchars(
                            $row['barcode']
                            ?? '-'
                        ) ?>

                    </td>


                    <!-- FROM LOCATION -->

                    <td>

                        <?php if (!empty($row['from_location_name'])): ?>

                            <span class="location-badge">

                                <i class="fa-solid fa-location-dot"></i>

                                <?= htmlspecialchars(
                                    $row['from_location_name']
                                ) ?>

                            </span>

                        <?php else: ?>

                            <span style="color:#475569;">
                                —
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- TO LOCATION -->

                    <td>

                        <?php if (!empty($row['to_location_name'])): ?>

                            <span class="location-badge">

                                <i class="fa-solid fa-location-arrow"></i>

                                <?= htmlspecialchars(
                                    $row['to_location_name']
                                ) ?>

                            </span>

                        <?php else: ?>

                            <span style="color:#475569;">
                                —
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- TRANSFER ID -->

                    <td>

                        <?php if (!empty($row['transfer_ID'])): ?>

                            <span class="transfer-badge">

                                <i class="fa-solid fa-fingerprint"></i>

                                <?= htmlspecialchars(
                                    $row['transfer_ID']
                                ) ?>

                            </span>

                        <?php else: ?>

                            <span style="color:#475569;">
                                —
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- QUANTITY -->

                    <td>

                        <span class="quantity-badge">

                            <?= number_format(
                                (float)$row['quantity'],
                                2
                            ) ?>

                        </span>

                    </td>


                    <!-- MOVEMENT TYPE -->

                    <td>

                        <span class="type-badge">

                            <i class="fa-solid fa-circle"></i>

                            <?= htmlspecialchars(
                                $row['movement_type']
                                ?? '-'
                            ) ?>

                        </span>

                    </td>


                    <!-- REFERENCE -->

                    <td class="reference-cell">

                        <?php if (!empty($row['reference'])): ?>

                            <?= htmlspecialchars(
                                $row['reference']
                            ) ?>

                        <?php else: ?>

                            <span style="color:#475569;">
                                —
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- CREATED BY -->

                    <td>

                        <span class="created-by">

                            <i class="fa-solid fa-user-circle"></i>

                            <?= htmlspecialchars(
                                $row['created_by_name']
                                ?? 'Unknown'
                            ) ?>

                        </span>

                    </td>


                    <!-- DATE -->

                    <td class="date-cell">

                        <i
                            class="fa-regular fa-clock"
                            style="margin-right:5px;color:#64748b;"
                        ></i>

                        <?= htmlspecialchars(
                            $row['created_at']
                            ?? '-'
                        ) ?>

                    </td>


                </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="11"
                        class="movement-empty"
                    >

                        <div class="empty-icon">

                            <i class="fa-solid fa-boxes-stacked"></i>

                        </div>

                        <div class="empty-title">

                            No Stock Movements Found

                        </div>

                        <div class="empty-text">

                            Try changing your filters or clearing the current search.

                        </div>

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>

</section>


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

<script
    defer
    src="bundle.js"
></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<!-- =========================================================
     EXISTING FILTER LOGIC — PRESERVED
     ========================================================= -->

<script>

/*
|--------------------------------------------------------------------------
| FILTER-AWARE KPI ENGINE
|--------------------------------------------------------------------------
| Every filter uses AND logic. KPIs are calculated ONLY from rows that
| remain visible after ALL active filters are applied.
|--------------------------------------------------------------------------
*/

function normalize(value) {
    return String(value ?? '').trim().toLowerCase();
}

function getMovementRows() {
    return Array.from(
        document.querySelectorAll('#stockTableBody tr[data-product-id]')
    );
}

function animateMovementKPI(id, end, decimals = 0) {
    const obj = document.getElementById(id);
    if (!obj) return;

    const target = Number(end) || 0;
    const start = parseFloat(obj.textContent.replace(/,/g, '')) || 0;
    const duration = 300;
    const startTime = performance.now();

    function frame(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const value = start + ((target - start) * eased);

        obj.textContent = value.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });

        if (progress < 1) {
            requestAnimationFrame(frame);
        }
    }

    requestAnimationFrame(frame);
}

function updateMovementKPIs(visibleRows) {
    let totalMovements = visibleRows.length;
    let totalQuantityMoved = 0;

    visibleRows.forEach(row => {
        totalQuantityMoved += parseFloat(row.dataset.quantity || '0') || 0;
    });

    animateMovementKPI('totalMovements', totalMovements, 0);
    animateMovementKPI('totalQuantityMoved', totalQuantityMoved, 2);
}

function updateMovementFilterCount(visible, total) {
    let counter = document.getElementById('movementFilterCount');

    if (!counter) {
        counter = document.createElement('div');
        counter.id = 'movementFilterCount';
        counter.className = 'filter-count';

        const firstFilter = document.getElementById('searchInput');
        const filterRow = firstFilter?.closest('.flex');

        if (filterRow) {
            filterRow.parentNode.insertBefore(counter, filterRow.nextSibling);
        }
    }

    if (counter) {
        counter.innerHTML =
            `<i class="fa-solid fa-chart-simple"></i>
             Showing ${visible} of ${total} stock movements`;
    }
}

function filterMovementTable() {
    const rows = getMovementRows();

    const search = normalize(document.getElementById('searchInput')?.value);
    const date = document.getElementById('dateFilter')?.value || '';
    const fromLocation = normalize(document.getElementById('fromLocationFilter')?.value);
    const toLocation = normalize(document.getElementById('toLocationFilter')?.value);
    const productId = document.getElementById('productFilter')?.value || '';
    const movementType = normalize(document.getElementById('movementTypeFilter')?.value);

    const visibleRows = [];

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 11) return;

        const product = normalize(cells[1].textContent);
        const barcode = normalize(cells[2].textContent);
        const rowFrom = normalize(row.dataset.fromLocation || cells[3].textContent);
        const rowTo = normalize(row.dataset.toLocation || cells[4].textContent);
        const transferId = normalize(cells[5].textContent);
        const rowType = normalize(row.dataset.movementType || cells[7].textContent);
        const reference = normalize(cells[8].textContent);
        const rowDate = (row.dataset.createdAt || cells[10].textContent.trim()).substring(0, 10);
        const rowProductId = row.dataset.productId || '';

        const matchesSearch =
            !search ||
            product.includes(search) ||
            barcode.includes(search) ||
            transferId.includes(search) ||
            reference.includes(search);

        const matchesDate = !date || rowDate === date;
        const matchesFrom = !fromLocation || rowFrom === fromLocation;
        const matchesTo = !toLocation || rowTo === toLocation;
        const matchesProduct = !productId || rowProductId === productId;
        const matchesType = !movementType || rowType === movementType;

        const show =
            matchesSearch &&
            matchesDate &&
            matchesFrom &&
            matchesTo &&
            matchesProduct &&
            matchesType;

        row.style.display = show ? '' : 'none';

        if (show) visibleRows.push(row);
    });

    updateMovementKPIs(visibleRows);
    updateMovementFilterCount(visibleRows.length, rows.length);
}

function clearMovementFilters() {
    [
        'searchInput',
        'dateFilter',
        'fromLocationFilter',
        'toLocationFilter',
        'productFilter',
        'movementTypeFilter'
    ].forEach(id => {
        const element = document.getElementById(id);
        if (element) element.value = '';
    });

    filterMovementTable();
}

function initializeMovementFilters() {
    const input = document.getElementById('searchInput');
    const clear = document.getElementById('clearStockFilters');

    if (input) input.addEventListener('input', filterMovementTable);
    if (clear) clear.addEventListener('click', clearMovementFilters);

    [
        'dateFilter',
        'fromLocationFilter',
        'toLocationFilter',
        'productFilter',
        'movementTypeFilter'
    ].forEach(id => {
        const element = document.getElementById(id);
        if (element) element.addEventListener('change', filterMovementTable);
    });

    filterMovementTable();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMovementFilters);
} else {
    initializeMovementFilters();
}

</script>


<!-- =========================================================
     TABLE TOP SCROLL — PRESERVED
     ========================================================= -->

<script>

const topScroll = document.getElementById('top-scroll');
const bottomScroll = document.getElementById('bottom-scroll');

if (topScroll && bottomScroll) {

    let syncing = false;

    topScroll.addEventListener('scroll', () => {

        if (syncing) return;

        syncing = true;

        bottomScroll.scrollLeft = topScroll.scrollLeft;

        requestAnimationFrame(() => {
            syncing = false;
        });

    });


    bottomScroll.addEventListener('scroll', () => {

        if (syncing) return;

        syncing = true;

        topScroll.scrollLeft = bottomScroll.scrollLeft;

        requestAnimationFrame(() => {
            syncing = false;
        });

    });


    function syncMovementScrollWidth() {

        const table = bottomScroll.querySelector('table');
        const spacer = topScroll.firstElementChild;

        if (table && spacer) {
            spacer.style.width = table.scrollWidth + 'px';
        }

    }


    window.addEventListener(
        'load',
        syncMovementScrollWidth
    );


    window.addEventListener(
        'resize',
        syncMovementScrollWidth
    );


    syncMovementScrollWidth();

}

</script>


</body>

</html>
