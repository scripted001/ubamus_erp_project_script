<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


// ============================================================
// SESSION / COMPANY
// ============================================================

$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_id    = $_SESSION['user_id'] ?? null;

if ($company_id <= 0) {
    echo "<script>
        alert('Invalid company session.');
        window.location.href='production.php';
    </script>";
    exit;
}


// ============================================================
// PRODUCTION ID
// ============================================================

$production_id = 0;

if (isset($_POST['production_id'])) {
    $production_id = (int)$_POST['production_id'];
}

if (isset($_GET['production_id'])) {
    $production_id = (int)$_GET['production_id'];
}

if ($production_id <= 0) {
    echo "<script>
        alert('Invalid production record.');
        window.location.href='production.php';
    </script>";
    exit;
}


// ============================================================
// VERIFY PRODUCTION BELONGS TO COMPANY
// ============================================================

$verifyStmt = $pdo->prepare("
    SELECT id
    FROM stock_production
    WHERE id = ?
    AND company_id = ?
    LIMIT 1
");

$verifyStmt->execute([
    $production_id,
    $company_id
]);

if (!$verifyStmt->fetchColumn()) {

    echo "<script>
        alert('Production record not found.');
        window.location.href='production.php';
    </script>";

    exit;
}


// ============================================================
// KPI — PRODUCTION UPGRADES / STAGES
// ============================================================

$totalProductionsStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM stock_production_stages
    WHERE company_id = ?
    AND stock_production_id = ?
");

$totalProductionsStmt->execute([
    $company_id,
    $production_id
]);

$totalProductions = (int)$totalProductionsStmt->fetchColumn();


// ============================================================
// KPI — QUANTITY PRODUCED
// ============================================================

$totalQuantityStmt = $pdo->prepare("
    SELECT COALESCE(quantity_produced, 0)
    FROM stock_production
    WHERE company_id = ?
    AND id = ?
    LIMIT 1
");

$totalQuantityStmt->execute([
    $company_id,
    $production_id
]);

$totalQuantity = (float)$totalQuantityStmt->fetchColumn();


// ============================================================
// KPI — PRODUCTION COST
// ============================================================

$totalProductionCostStmt = $pdo->prepare("
    SELECT COALESCE(total_cost, 0)
    FROM stock_production
    WHERE company_id = ?
    AND id = ?
    LIMIT 1
");

$totalProductionCostStmt->execute([
    $company_id,
    $production_id
]);

$totalProductionCost = (float)$totalProductionCostStmt->fetchColumn();


// ============================================================
// KPI — PRODUCTION VALUE
// ============================================================

$totalProductionValueStmt = $pdo->prepare("
    SELECT
        COALESCE(
            quantity_produced * unit_selling_price,
            0
        )
    FROM stock_production
    WHERE company_id = ?
    AND id = ?
    LIMIT 1
");

$totalProductionValueStmt->execute([
    $company_id,
    $production_id
]);

$totalProductionValue = (float)$totalProductionValueStmt->fetchColumn();


// ============================================================
// KPI — EXPECTED PROFIT
// ============================================================

$totalProfitStmt = $pdo->prepare("
    SELECT
        COALESCE(
            (
                unit_selling_price - unit_cost
            ) * quantity_produced,
            0
        )
    FROM stock_production
    WHERE company_id = ?
    AND id = ?
    LIMIT 1
");

$totalProfitStmt->execute([
    $company_id,
    $production_id
]);

$totalProductionProfit = (float)$totalProfitStmt->fetchColumn();


// ============================================================
// KPI — CATEGORY COUNT
// ============================================================

$totalCategoriesStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT category_id)
    FROM stock_production
    WHERE company_id = ?
    AND id = ?
    AND category_id IS NOT NULL
");

$totalCategoriesStmt->execute([
    $company_id,
    $production_id
]);

$totalCategories = (int)$totalCategoriesStmt->fetchColumn();


// ============================================================
// PRODUCTION MASTER RECORD
// ============================================================

$masterStmt = $pdo->prepare("
    SELECT
        sp.*,
        pc.name AS category_name,
        l.name AS location_name

    FROM stock_production sp

    LEFT JOIN product_categories pc
        ON pc.id = sp.category_id

    LEFT JOIN locations l
        ON l.id = sp.location_id

    WHERE sp.id = ?
    AND sp.company_id = ?

    LIMIT 1
");

$masterStmt->execute([
    $production_id,
    $company_id
]);

$masterProduction = $masterStmt->fetch(PDO::FETCH_ASSOC);


// ============================================================
// PRODUCTION STAGES / HISTORY
// ============================================================

$productionStmt = $pdo->prepare("
    SELECT

        sp.id,
        sp.stock_production_id,
        sp.product_id,

        s.product_name,

        sp.batch_number,
        sp.report_number,

        sp.previous_quantity_produced,
        sp.quantity_produced,

        sp.previous_unit_cost,
        sp.unit_cost,

        sp.previous_total_cost,
        sp.total_cost,

        sp.previous_profit_margin,
        sp.profit_margin,

        sp.previous_unit_selling_price,
        sp.unit_selling_price,

        sp.previous_expenses_break_down,
        sp.expenses_break_down,

        sp.status,

        sp.created_at,
        sp.updated_at,

        sp.location_id,
        l.name AS location_name

    FROM stock_production_stages sp

    LEFT JOIN locations l
        ON l.id = sp.location_id

    LEFT JOIN stock_production s
        ON s.id = sp.stock_production_id

    WHERE sp.company_id = ?
    AND sp.stock_production_id = ?

    ORDER BY sp.id ASC
");

$productionStmt->execute([
    $company_id,
    $production_id
]);

$productions = $productionStmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// CATEGORY DATA
// Only selected production
// ============================================================

$categoriesStmt = $pdo->prepare("
    SELECT

        COALESCE(pc.name, 'Uncategorized') AS category,

        COALESCE(
            SUM(sp.quantity_produced),
            0
        ) AS total

    FROM stock_production sp

    LEFT JOIN product_categories pc
        ON pc.id = sp.category_id

    WHERE sp.company_id = ?
    AND sp.id = ?

    GROUP BY pc.id, pc.name

    ORDER BY category ASC
");

$categoriesStmt->execute([
    $company_id,
    $production_id
]);

$categoriesDataArr = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$categoriesLabels = array_column(
    $categoriesDataArr,
    'category'
);

$categoriesData = array_map(
    'floatval',
    array_column(
        $categoriesDataArr,
        'total'
    )
);


// ============================================================
// PRODUCTION TREND
// Last 30 days for this production
// ============================================================

$trendStmt = $pdo->prepare("
    SELECT

        DATE(created_at) AS date,

        COALESCE(
            SUM(quantity_produced),
            0
        ) AS total

    FROM stock_production_stages

    WHERE company_id = ?
    AND stock_production_id = ?

    AND DATE(created_at)
        >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)

    GROUP BY DATE(created_at)

    ORDER BY DATE(created_at)
");

$trendStmt->execute([
    $company_id,
    $production_id
]);

$trendDataArr = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$trendLabels = array_column(
    $trendDataArr,
    'date'
);

$trendData = array_map(
    'floatval',
    array_column(
        $trendDataArr,
        'total'
    )
);


// ============================================================
// PRODUCTION STATUS
// ============================================================

$productionStatus = $masterProduction['status'] ?? 'Unknown';


// ============================================================
// REPORT NUMBER
// ============================================================

$currentReportNumber = '';

if (!empty($productions)) {
    $lastProduction = end($productions);
    $currentReportNumber = $lastProduction['report_number'] ?? '';
}


// ============================================================
// SAFE VALUES
// ============================================================

$productName = $masterProduction['product_name'] ?? 'Production';

$batchNumber = $masterProduction['batch_number'] ?? 'N/A';

$locationName = $masterProduction['location_name'] ?? 'N/A';

$categoryName = $masterProduction['category_name'] ?? 'N/A';

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
    <?= htmlspecialchars($company_name) ?> | Production Intelligence
</title>

<link
    rel="stylesheet"
    href="../fontawesome/css/all.css"
>

<link
    href="style.css"
    rel="stylesheet"
>

<style>

/* ============================================================
   FUTURISTIC PRODUCTION INTELLIGENCE
   LIGHT GLASS INTERFACE
============================================================ */

:root {

    --pi-purple: #6839eb;
    --pi-purple-light: #8b5cf6;

    --pi-blue: #2563eb;
    --pi-cyan: #0891b2;

    --pi-text: #172033;
    --pi-muted: #718096;

    --pi-border: rgba(104, 57, 235, .12);

    --pi-glass:
        rgba(255, 255, 255, .78);

    --pi-shadow:
        0 18px 45px rgba(30, 41, 59, .08);

}


/* ============================================================
   PAGE
============================================================ */

.production-intelligence {

    width: 100%;

    min-height: 100vh;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(104,57,235,.07),
            transparent 30%
        ),

        radial-gradient(
            circle at 90% 20%,
            rgba(37,99,235,.06),
            transparent 28%
        ),

        #f7f8fc;

}


/* ============================================================
   HEADER
============================================================ */

.pi-header {

    position: relative;

    overflow: hidden;

    padding: 24px;

    margin-bottom: 24px;

    border: 1px solid var(--pi-border);

    border-radius: 24px;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.92),
            rgba(248,247,255,.78)
        );

    backdrop-filter: blur(18px);

    -webkit-backdrop-filter: blur(18px);

    box-shadow: var(--pi-shadow);

}


.pi-header::before {

    content: "";

    position: absolute;

    width: 180px;
    height: 180px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(104,57,235,.12),
            transparent 70%
        );

    right: -70px;
    top: -90px;

}


.pi-header-content {

    position: relative;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

}


.pi-eyebrow {

    display: flex;

    align-items: center;

    gap: 8px;

    color: var(--pi-purple);

    font-size: 11px;

    font-weight: 800;

    letter-spacing: .15em;

    text-transform: uppercase;

}


.pi-live-dot {

    width: 8px;
    height: 8px;

    border-radius: 50%;

    background: #22c55e;

    box-shadow:
        0 0 0 5px rgba(34,197,94,.10),
        0 0 15px rgba(34,197,94,.45);

}


.pi-title {

    margin: 7px 0 4px;

    font-size: clamp(24px, 4vw, 36px);

    line-height: 1.1;

    font-weight: 800;

    letter-spacing: -.035em;

    color: var(--pi-text);

}


.pi-subtitle {

    margin: 0;

    color: var(--pi-muted);

    font-size: 14px;

}


.pi-status {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 10px 15px;

    border-radius: 999px;

    border: 1px solid rgba(104,57,235,.12);

    background: rgba(255,255,255,.72);

    color: var(--pi-purple);

    font-size: 12px;

    font-weight: 800;

    white-space: nowrap;

}


/* ============================================================
   KPI GRID
============================================================ */

.pi-kpi-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 16px;

    margin-bottom: 20px;

}


.pi-kpi {

    position: relative;

    overflow: hidden;

    min-width: 0;

    padding: 20px;

    border-radius: 20px;

    border: 1px solid var(--pi-border);

    background:
        rgba(255,255,255,.82);

    backdrop-filter: blur(16px);

    -webkit-backdrop-filter: blur(16px);

    box-shadow:
        0 12px 30px rgba(30,41,59,.06);

    transition:
        transform .25s ease,
        box-shadow .25s ease;

}


.pi-kpi:hover {

    transform: translateY(-4px);

    box-shadow:
        0 20px 42px rgba(30,41,59,.10);

}


.pi-kpi::after {

    content: "";

    position: absolute;

    width: 90px;
    height: 90px;

    right: -35px;
    bottom: -40px;

    border-radius: 50%;

    background:
        rgba(104,57,235,.06);

}


.pi-kpi-label {

    color: var(--pi-muted);

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .08em;

}


.pi-kpi-value {

    margin-top: 8px;

    color: var(--pi-text);

    font-size: clamp(24px, 3vw, 32px);

    line-height: 1;

    font-weight: 800;

    letter-spacing: -.04em;

    word-break: break-word;

}


.pi-kpi-value.purple {

    color: var(--pi-purple);

}


.pi-kpi-value.blue {

    color: var(--pi-blue);

}


.pi-kpi-value.green {

    color: #059669;

}


.pi-kpi-meta {

    margin-top: 10px;

    color: #94a3b8;

    font-size: 11px;

}


/* ============================================================
   INFORMATION GRID
============================================================ */

.pi-info-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 14px;

    margin-bottom: 20px;

}


.pi-info {

    padding: 15px 17px;

    border-radius: 16px;

    border: 1px solid var(--pi-border);

    background:
        rgba(255,255,255,.70);

    backdrop-filter: blur(14px);

}


.pi-info-label {

    color: #94a3b8;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .08em;

    font-weight: 800;

}


.pi-info-value {

    margin-top: 5px;

    color: var(--pi-text);

    font-size: 14px;

    font-weight: 700;

    overflow-wrap: anywhere;

}


/* ============================================================
   CHART GRID
============================================================ */

.pi-chart-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;

    margin-bottom: 20px;

}


.pi-panel {

    min-width: 0;

    padding: 20px;

    border-radius: 22px;

    border: 1px solid var(--pi-border);

    background:
        rgba(255,255,255,.78);

    backdrop-filter: blur(16px);

    -webkit-backdrop-filter: blur(16px);

    box-shadow:
        0 15px 40px rgba(30,41,59,.06);

}


.pi-panel-heading {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 12px;

    margin-bottom: 15px;

}


.pi-panel-title {

    margin: 0;

    color: var(--pi-text);

    font-size: 15px;

    font-weight: 800;

}


.pi-panel-badge {

    padding: 6px 10px;

    border-radius: 999px;

    background: rgba(104,57,235,.07);

    color: var(--pi-purple);

    font-size: 10px;

    font-weight: 800;

}


.pi-chart-wrap {

    position: relative;

    width: 100%;

    height: 250px;

}


/* ============================================================
   PRODUCTION TABLE
============================================================ */

.pi-table-panel {

    padding: 0;

    overflow: hidden;

}


.pi-table-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    padding: 20px;

    border-bottom: 1px solid rgba(148,163,184,.12);

}


.pi-table-title {

    color: var(--pi-text);

    font-size: 16px;

    font-weight: 800;

}


.pi-table-subtitle {

    margin-top: 3px;

    color: #94a3b8;

    font-size: 11px;

}


.pi-table-scroll {

    width: 100%;

    overflow-x: auto;

    overflow-y: hidden;

    -webkit-overflow-scrolling: touch;

}


.pi-table-scroll::-webkit-scrollbar {

    height: 8px;

}


.pi-table-scroll::-webkit-scrollbar-track {

    background: #f1f5f9;

}


.pi-table-scroll::-webkit-scrollbar-thumb {

    background: #cbd5e1;

    border-radius: 999px;

}


.pi-table {

    width: 100%;

    min-width: 1450px;

    border-collapse: separate;

    border-spacing: 0;

}


.pi-table thead th {

    padding: 13px 15px;

    background:
        #f8fafc;

    color: #64748b;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .06em;

    text-align: left;

    white-space: nowrap;

    border-bottom: 1px solid #e2e8f0;

}


.pi-table tbody td {

    padding: 15px;

    color: #334155;

    font-size: 12px;

    vertical-align: top;

    border-bottom: 1px solid #eef2f7;

}


.pi-table tbody tr {

    transition:
        background .2s ease;

}


.pi-table tbody tr:hover {

    background:
        rgba(104,57,235,.025);

}


.pi-table .main-value {

    color: #172033;

    font-weight: 750;

}


.pi-table .muted-value {

    color: #94a3b8;

}


.pi-table .money {

    font-variant-numeric: tabular-nums;

    white-space: nowrap;

}


.pi-table .expense-cell {

    width: 230px;

    max-width: 230px;

    white-space: normal;

    line-height: 1.55;

    color: #64748b;

}


/* ============================================================
   STATUS
============================================================ */

.pi-status-badge {

    display: inline-flex;

    align-items: center;

    padding: 6px 10px;

    border-radius: 999px;

    font-size: 10px;

    font-weight: 800;

    white-space: nowrap;

}


.pi-status-finished {

    color: #047857;

    background: #ecfdf5;

}


.pi-status-progress {

    color: #b45309;

    background: #fffbeb;

}


.pi-status-cancelled {

    color: #b91c1c;

    background: #fef2f2;

}


.pi-status-default {

    color: #475569;

    background: #f1f5f9;

}


/* ============================================================
   REPORT BUTTON
============================================================ */

.pi-report-button {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 8px 12px;

    border-radius: 9px;

    color: #fff !important;

    background:
        linear-gradient(
            135deg,
            #6839eb,
            #7c3aed
        );

    font-size: 11px;

    font-weight: 800;

    text-decoration: none;

    box-shadow:
        0 7px 18px rgba(104,57,235,.20);

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.pi-report-button:hover {

    transform: translateY(-2px);

    box-shadow:
        0 10px 24px rgba(104,57,235,.28);

}


.pi-not-found {

    color: #94a3b8;

    font-size: 11px;

}


/* ============================================================
   EMPTY STATE
============================================================ */

.pi-empty {

    padding: 55px 20px;

    text-align: center;

    color: #94a3b8;

}


.pi-empty-icon {

    width: 55px;
    height: 55px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 15px;

    border-radius: 16px;

    background: rgba(104,57,235,.07);

    color: var(--pi-purple);

    font-size: 22px;

}


.pi-empty-title {

    color: #475569;

    font-size: 14px;

    font-weight: 800;

}


.pi-empty-text {

    margin-top: 5px;

    font-size: 12px;

}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 1100px) {

    .pi-kpi-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

    .pi-info-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

}


@media (max-width: 800px) {

    .pi-chart-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 640px) {

    .production-intelligence {

        padding-bottom: 20px;

    }

    .pi-header {

        padding: 18px;

        border-radius: 18px;

    }

    .pi-header-content {

        align-items: flex-start;

        flex-direction: column;

    }

    .pi-status {

        align-self: flex-start;

    }

    .pi-kpi-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 10px;

    }

    .pi-kpi {

        padding: 15px;

        border-radius: 16px;

    }

    .pi-kpi-label {

        font-size: 9px;

    }

    .pi-kpi-value {

        font-size: 22px;

    }

    .pi-kpi-meta {

        font-size: 9px;

    }

    .pi-info-grid {

        grid-template-columns: 1fr 1fr;

        gap: 10px;

    }

    .pi-info {

        padding: 12px;

    }

    .pi-info-value {

        font-size: 12px;

    }

    .pi-panel {

        padding: 15px;

        border-radius: 18px;

    }

    .pi-chart-wrap {

        height: 220px;

    }

    .pi-table-header {

        padding: 15px;

        align-items: flex-start;

        flex-direction: column;

    }

}


@media (max-width: 400px) {

    .pi-kpi-grid {

        grid-template-columns: 1fr;

    }

    .pi-info-grid {

        grid-template-columns: 1fr;

    }

}


/* ============================================================
   DARK MODE COMPATIBILITY
   Keeps existing Alpine dark mode functional
============================================================ */

.dark .production-intelligence {

    background: #0f172a;

}

.dark .pi-header,
.dark .pi-kpi,
.dark .pi-info,
.dark .pi-panel {

    background:
        rgba(15,23,42,.78);

    border-color:
        rgba(148,163,184,.10);

}

.dark .pi-title,
.dark .pi-kpi-value,
.dark .pi-panel-title,
.dark .pi-table-title,
.dark .pi-info-value {

    color: #f8fafc;

}

.dark .pi-subtitle,
.dark .pi-kpi-label,
.dark .pi-kpi-meta,
.dark .pi-info-label {

    color: #94a3b8;

}

.dark .pi-table thead th {

    background: #111827;

    color: #94a3b8;

    border-color: #1e293b;

}

.dark .pi-table tbody td {

    color: #cbd5e1;

    border-color: #1e293b;

}

.dark .pi-table tbody tr:hover {

    background:
        rgba(104,57,235,.07);

}

.dark .pi-table .main-value {

    color: #f8fafc;

}

.dark .pi-table .muted-value {

    color: #64748b;

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


    <!-- ========================================================
         CONTENT AREA
    ========================================================= -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <main>

            <?php include('header.php'); ?>


            <div class="mx-auto max-w-(--breakpoint-2xl) p-3 sm:p-4 md:p-6">


                <section class="production-intelligence">


                    <!-- ==================================================
                         PRODUCTION HEADER
                    ================================================== -->

                    <div class="pi-header">

                        <div class="pi-header-content">

                            <div>

                                <div class="pi-eyebrow">

                                    <span class="pi-live-dot"></span>

                                    Production Intelligence

                                </div>


                                <h1 class="pi-title">

                                    <?= htmlspecialchars($productName) ?>

                                </h1>


                                <p class="pi-subtitle">

                                    Production lifecycle, cost intelligence
                                    and upgrade history.

                                </p>

                            </div>


                            <div class="pi-status">

                                <i class="fa-solid fa-circle-nodes"></i>

                                <?= htmlspecialchars($productionStatus) ?>

                            </div>

                        </div>

                    </div>


                    <!-- ==================================================
                         KPI CARDS
                    ================================================== -->

                    <div class="pi-kpi-grid">


                        <!-- Production stages -->

                        <div class="pi-kpi">

                            <div class="pi-kpi-label">

                                Production Upgrades

                            </div>

                            <div
                                class="pi-kpi-value purple"
                                id="totalProductions"
                            >
                                0
                            </div>

                            <div class="pi-kpi-meta">

                                Lifecycle stages

                            </div>

                        </div>


                        <!-- Quantity -->

                        <div class="pi-kpi">

                            <div class="pi-kpi-label">

                                Units Produced

                            </div>

                            <div
                                class="pi-kpi-value"
                                id="totalQuantity"
                            >
                                0
                            </div>

                            <div class="pi-kpi-meta">

                                Total production output

                            </div>

                        </div>


                        <!-- Cost -->

                        <div class="pi-kpi">

                            <div class="pi-kpi-label">

                                Production Cost

                            </div>

                            <div
                                class="pi-kpi-value"
                                id="totalProductionCost"
                            >
                                ₦0
                            </div>

                            <div class="pi-kpi-meta">

                                Total production expenditure

                            </div>

                        </div>


                        <!-- Value -->

                        <div class="pi-kpi">

                            <div class="pi-kpi-label">

                                Production Value

                            </div>

                            <div
                                class="pi-kpi-value blue"
                                id="totalProductionValue"
                            >
                                ₦0
                            </div>

                            <div class="pi-kpi-meta">

                                Expected sales value

                            </div>

                        </div>

                    </div>


                    <!-- ==================================================
                         PRODUCTION INFORMATION
                    ================================================== -->

                    <div class="pi-info-grid">


                        <div class="pi-info">

                            <div class="pi-info-label">
                                Batch
                            </div>

                            <div class="pi-info-value">
                                <?= htmlspecialchars($batchNumber) ?>
                            </div>

                        </div>


                        <div class="pi-info">

                            <div class="pi-info-label">
                                Location
                            </div>

                            <div class="pi-info-value">
                                <?= htmlspecialchars($locationName) ?>
                            </div>

                        </div>


                        <div class="pi-info">

                            <div class="pi-info-label">
                                Category
                            </div>

                            <div class="pi-info-value">
                                <?= htmlspecialchars($categoryName) ?>
                            </div>

                        </div>


                        <div class="pi-info">

                            <div class="pi-info-label">
                                Expected Profit
                            </div>

                            <div class="pi-info-value">
                                ₦<?= number_format($totalProductionProfit, 2) ?>
                            </div>

                        </div>


                    </div>


                    <!-- ==================================================
                         INTELLIGENCE CHARTS
                    ================================================== -->

                    <div class="pi-chart-grid">


                        <!-- CATEGORY -->

                        <div class="pi-panel">

                            <div class="pi-panel-heading">

                                <h2 class="pi-panel-title">

                                    Production Composition

                                </h2>

                                <span class="pi-panel-badge">

                                    CATEGORY

                                </span>

                            </div>


                            <div class="pi-chart-wrap">

                                <canvas id="categoryChart"></canvas>

                            </div>

                        </div>


                        <!-- TREND -->

                        <div class="pi-panel">

                            <div class="pi-panel-heading">

                                <h2 class="pi-panel-title">

                                    Production Activity

                                </h2>

                                <span class="pi-panel-badge">

                                    30 DAYS

                                </span>

                            </div>


                            <div class="pi-chart-wrap">

                                <canvas id="stockTrendChart"></canvas>

                            </div>

                        </div>


                    </div>


                    <!-- ==================================================
                         PRODUCTION HISTORY
                    ================================================== -->

                    <div class="pi-panel pi-table-panel">


                        <div class="pi-table-header">

                            <div>

                                <div class="pi-table-title">

                                    Production Evolution

                                </div>

                                <div class="pi-table-subtitle">

                                    Historical production upgrades,
                                    pricing changes and cost movements.

                                </div>

                            </div>


                            <div class="pi-panel-badge">

                                <?= count($productions) ?> RECORDS

                            </div>

                        </div>


                        <?php if (!empty($productions)): ?>


                        <div class="pi-table-scroll">


                            <table class="pi-table">


                                <thead>

                                <tr>

                                    <th>S/N</th>

                                    <th>Product</th>

                                    <th>Batch No.</th>

                                    <th>Report No.</th>

                                    <th>Status</th>

                                    <th>Previous Qty</th>

                                    <th>Quantity</th>

                                    <th>Previous Cost</th>

                                    <th>Cost Price</th>

                                    <th>Previous Total</th>

                                    <th>Total Cost</th>

                                    <th>Previous Margin</th>

                                    <th>Margin</th>

                                    <th>Previous Selling</th>

                                    <th>Selling Price</th>

                                    <th>Previous Expenses</th>

                                    <th>Expenses</th>

                                    <th>Updated</th>

                                    <th>Report</th>

                                </tr>

                                </thead>


                                <tbody id="stockTableBody">


                                <?php

                                $count = 1;

                                foreach ($productions as $row):


                                    $status =
                                        trim(
                                            $row['status'] ?? ''
                                        );


                                    $statusClass =
                                        'pi-status-default';


                                    if (
                                        strtolower($status)
                                        === 'finished'
                                    ) {

                                        $statusClass =
                                            'pi-status-finished';

                                    } elseif (
                                        strtolower($status)
                                        === 'in progress'
                                    ) {

                                        $statusClass =
                                            'pi-status-progress';

                                    } elseif (
                                        strtolower($status)
                                        === 'cancelled'
                                    ) {

                                        $statusClass =
                                            'pi-status-cancelled';

                                    }


                                    $pdf_file =
                                        "../production_reports/Production_Report_"
                                        . $row['report_number']
                                        . ".pdf";


                                    $pdf_exists =
                                        file_exists($pdf_file);

                                ?>


                                <tr>


                                    <!-- S/N -->

                                    <td>

                                        <?= $count++ ?>

                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <span class="main-value">

                                            <?= htmlspecialchars(
                                                $row['product_name']
                                                ?? 'N/A'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- BATCH -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['batch_number']
                                            ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- REPORT -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['report_number']
                                            ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="pi-status-badge <?= $statusClass ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $status ?: 'Unknown'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PREVIOUS QUANTITY -->

                                    <td class="muted-value">

                                        <?= number_format(
                                            (float)(
                                                $row[
                                                    'previous_quantity_produced'
                                                ] ?? 0
                                            )
                                        ) ?>

                                    </td>


                                    <!-- QUANTITY -->

                                    <td>

                                        <span class="main-value">

                                            <?= number_format(
                                                (float)(
                                                    $row[
                                                        'quantity_produced'
                                                    ] ?? 0
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PREVIOUS COST -->

                                    <td class="money muted-value">

                                        ₦<?= number_format(
                                            (float)(
                                                $row[
                                                    'previous_unit_cost'
                                                ] ?? 0
                                            ),
                                            2
                                        ) ?>

                                    </td>


                                    <!-- COST -->

                                    <td class="money">

                                        <span class="main-value">

                                            ₦<?= number_format(
                                                (float)(
                                                    $row[
                                                        'unit_cost'
                                                    ] ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PREVIOUS TOTAL -->

                                    <td class="money muted-value">

                                        ₦<?= number_format(
                                            (float)(
                                                $row[
                                                    'previous_total_cost'
                                                ] ?? 0
                                            ),
                                            2
                                        ) ?>

                                    </td>


                                    <!-- TOTAL -->

                                    <td class="money">

                                        <span class="main-value">

                                            ₦<?= number_format(
                                                (float)(
                                                    $row[
                                                        'total_cost'
                                                    ] ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PREVIOUS MARGIN -->

                                    <td class="muted-value">

                                        <?= number_format(
                                            (float)(
                                                $row[
                                                    'previous_profit_margin'
                                                ] ?? 0
                                            ),
                                            2
                                        ) ?>%

                                    </td>


                                    <!-- MARGIN -->

                                    <td>

                                        <span class="main-value">

                                            <?= number_format(
                                                (float)(
                                                    $row[
                                                        'profit_margin'
                                                    ] ?? 0
                                                ),
                                                2
                                            ) ?>%

                                        </span>

                                    </td>


                                    <!-- PREVIOUS SELLING -->

                                    <td class="money muted-value">

                                        ₦<?= number_format(
                                            (float)(
                                                $row[
                                                    'previous_unit_selling_price'
                                                ] ?? 0
                                            ),
                                            2
                                        ) ?>

                                    </td>


                                    <!-- SELLING -->

                                    <td class="money">

                                        <span class="main-value">

                                            ₦<?= number_format(
                                                (float)(
                                                    $row[
                                                        'unit_selling_price'
                                                    ] ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PREVIOUS EXPENSES -->

                                    <td class="expense-cell">

                                        <?= !empty(
                                            $row[
                                                'previous_expenses_break_down'
                                            ]
                                        )
                                            ? htmlspecialchars(
                                                $row[
                                                    'previous_expenses_break_down'
                                                ]
                                            )
                                            : '—'
                                        ?>

                                    </td>


                                    <!-- CURRENT EXPENSES -->

                                    <td class="expense-cell">

                                        <?= !empty(
                                            $row[
                                                'expenses_break_down'
                                            ]
                                        )
                                            ? htmlspecialchars(
                                                $row[
                                                    'expenses_break_down'
                                                ]
                                            )
                                            : '—'
                                        ?>

                                    </td>


                                    <!-- UPDATED -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['updated_at']
                                            ?? $row['created_at']
                                            ?? 'N/A'
                                        ) ?>

                                    </td>


                                    <!-- REPORT -->

                                    <td>

                                        <?php if ($pdf_exists): ?>

                                            <a
                                                href="<?= htmlspecialchars($pdf_file) ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="pi-report-button"
                                            >

                                                <i class="fa-solid fa-file-pdf"></i>

                                                View

                                            </a>

                                        <?php else: ?>

                                            <span class="pi-not-found">

                                                Not found

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                                <?php endforeach; ?>


                                </tbody>

                            </table>


                        </div>


                        <?php else: ?>


                        <div class="pi-empty">

                            <div class="pi-empty-icon">

                                <i class="fa-solid fa-industry"></i>

                            </div>

                            <div class="pi-empty-title">

                                No production history found

                            </div>

                            <div class="pi-empty-text">

                                There are currently no upgrade records
                                attached to this production.

                            </div>

                        </div>


                        <?php endif; ?>


                    </div>


                </section>


            </div>

        </main>


    </div>


</div>


<!-- ============================================================
     FOOTER
============================================================ -->

<?php include('footer.php'); ?>


<!-- ============================================================
     BUNDLE
============================================================ -->

<script defer src="bundle.js"></script>


<!-- ============================================================
     CHART.JS
============================================================ -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

/* ============================================================
   PHP DATA
============================================================ */

const productionData = {

    productions:
        <?= json_encode(
            $totalProductions,
            JSON_NUMERIC_CHECK
        ) ?>,

    quantity:
        <?= json_encode(
            $totalQuantity,
            JSON_NUMERIC_CHECK
        ) ?>,

    cost:
        <?= json_encode(
            $totalProductionCost,
            JSON_NUMERIC_CHECK
        ) ?>,

    value:
        <?= json_encode(
            $totalProductionValue,
            JSON_NUMERIC_CHECK
        ) ?>,

    profit:
        <?= json_encode(
            $totalProductionProfit,
            JSON_NUMERIC_CHECK
        ) ?>,

    categories:
        <?= json_encode(
            $categoriesLabels,
            JSON_UNESCAPED_UNICODE
        ) ?>,

    categoryValues:
        <?= json_encode(
            $categoriesData,
            JSON_NUMERIC_CHECK
        ) ?>,

    trendLabels:
        <?= json_encode(
            $trendLabels,
            JSON_UNESCAPED_UNICODE
        ) ?>,

    trendValues:
        <?= json_encode(
            $trendData,
            JSON_NUMERIC_CHECK
        ) ?>

};


/* ============================================================
   NUMBER FORMATTERS
============================================================ */

const numberFormatter =
    new Intl.NumberFormat(
        'en-NG',
        {
            maximumFractionDigits: 0
        }
    );


const moneyFormatter =
    new Intl.NumberFormat(
        'en-NG',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );


/* ============================================================
   ANIMATED KPI
============================================================ */

function animateNumber(
    elementId,
    target,
    prefix = '',
    decimals = 0
) {

    const element =
        document.getElementById(elementId);

    if (!element) return;

    const duration = 900;

    const startTime = performance.now();

    function update(currentTime) {

        const elapsed =
            currentTime - startTime;

        const progress =
            Math.min(
                elapsed / duration,
                1
            );


        const eased =
            1 -
            Math.pow(
                1 - progress,
                3
            );


        const value =
            target * eased;


        let formatted;


        if (decimals > 0) {

            formatted =
                moneyFormatter.format(value);

        } else {

            formatted =
                numberFormatter.format(value);

        }


        element.textContent =
            prefix + formatted;


        if (progress < 1) {

            requestAnimationFrame(update);

        } else {

            if (decimals > 0) {

                element.textContent =
                    prefix +
                    moneyFormatter.format(target);

            } else {

                element.textContent =
                    prefix +
                    numberFormatter.format(target);

            }

        }

    }


    requestAnimationFrame(update);

}


/* ============================================================
   RUN KPI ANIMATIONS
============================================================ */

animateNumber(
    'totalProductions',
    Number(productionData.productions)
);


animateNumber(
    'totalQuantity',
    Number(productionData.quantity)
);


animateNumber(
    'totalProductionCost',
    Number(productionData.cost),
    '₦',
    2
);


animateNumber(
    'totalProductionValue',
    Number(productionData.value),
    '₦',
    2
);


/* ============================================================
   CATEGORY CHART
============================================================ */

const categoryCanvas =
    document.getElementById(
        'categoryChart'
    );


if (
    categoryCanvas &&
    productionData.categories.length
) {

    new Chart(
        categoryCanvas,
        {

            type: 'doughnut',

            data: {

                labels:
                    productionData.categories,

                datasets: [

                    {

                        data:
                            productionData.categoryValues,

                        backgroundColor: [

                            '#6839eb',
                            '#2563eb',
                            '#0891b2',
                            '#10b981',
                            '#f59e0b',
                            '#ec4899',
                            '#8b5cf6',
                            '#14b8a6'

                        ],

                        borderWidth: 0,

                        hoverOffset: 8

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                cutout: '68%',

                plugins: {

                    legend: {

                        position: 'bottom',

                        labels: {

                            usePointStyle: true,

                            padding: 15,

                            font: {

                                size: 10

                            }

                        }

                    }

                },

                animation: {

                    animateScale: true,

                    duration: 1000

                }

            }

        }
    );

}


/* ============================================================
   TREND CHART
============================================================ */

const trendCanvas =
    document.getElementById(
        'stockTrendChart'
    );


if (trendCanvas) {

    new Chart(
        trendCanvas,
        {

            type: 'line',

            data: {

                labels:
                    productionData.trendLabels,

                datasets: [

                    {

                        label:
                            'Production Output',

                        data:
                            productionData.trendValues,

                        fill: true,

                        backgroundColor:
                            'rgba(104,57,235,.08)',

                        borderColor:
                            '#6839eb',

                        borderWidth: 2,

                        pointRadius: 3,

                        pointHoverRadius: 6,

                        tension: .4

                    }

                ]

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

                        grid: {

                            display: false

                        },

                        ticks: {

                            font: {

                                size: 9

                            },

                            color: '#94a3b8'

                        }

                    },

                    y: {

                        beginAtZero: true,

                        grid: {

                            color:
                                'rgba(148,163,184,.10)'

                        },

                        ticks: {

                            font: {

                                size: 9

                            },

                            color: '#94a3b8'

                        }

                    }

                },

                animation: {

                    duration: 1000

                }

            }

        }
    );

}


/* ============================================================
   TABLE MOBILE SCROLL HINT
============================================================ */

const tableScroll =
    document.querySelector(
        '.pi-table-scroll'
    );


if (tableScroll) {

    tableScroll.addEventListener(
        'scroll',
        function() {

            this.classList.add(
                'is-scrolling'
            );

        },
        {
            passive: true
        }
    );

}


/* ============================================================
   PREVENT BROKEN TABLE FILTER REFERENCES
============================================================ */

const searchInput =
    document.getElementById(
        'searchInput'
    );

const dateFilter =
    document.getElementById(
        'dateFilter'
    );

const locationFilter =
    document.getElementById(
        'locationFilter'
    );

const statusFilter =
    document.getElementById(
        'statusFilter'
    );


/*
|--------------------------------------------------------------------------
| Optional filtering support
|--------------------------------------------------------------------------
|
| These controls may exist in your header on some versions
| of the page. We safely detect them before attaching listeners.
|
*/

function filterProductionTable() {

    const rows =
        document.querySelectorAll(
            '#stockTableBody tr'
        );


    const search =
        searchInput
            ? searchInput.value
                .toLowerCase()
                .trim()
            : '';


    const date =
        dateFilter
            ? dateFilter.value
            : '';


    const location =
        locationFilter
            ? locationFilter.value
            : '';


    const status =
        statusFilter
            ? statusFilter.value
                .toLowerCase()
            : '';


    rows.forEach(
        row => {

            const text =
                row.textContent
                    .toLowerCase();


            let show = true;


            if (
                search &&
                !text.includes(search)
            ) {

                show = false;

            }


            if (
                status &&
                !text.includes(status)
            ) {

                show = false;

            }


            if (
                date
            ) {

                const rowText =
                    row.textContent;

                if (
                    !rowText.includes(date)
                ) {

                    show = false;

                }

            }


            if (
                location &&
                !text.includes(
                    location.toLowerCase()
                )
            ) {

                show = false;

            }


            row.style.display =
                show
                    ? ''
                    : 'none';

        }
    );

}


if (searchInput) {

    searchInput.addEventListener(
        'input',
        filterProductionTable
    );

}


if (dateFilter) {

    dateFilter.addEventListener(
        'change',
        filterProductionTable
    );

}


if (locationFilter) {

    locationFilter.addEventListener(
        'change',
        filterProductionTable
    );

}


if (statusFilter) {

    statusFilter.addEventListener(
        'change',
        filterProductionTable
    );

}

</script>


</body>

</html>