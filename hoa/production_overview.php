<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_id    = $_SESSION['user_id'] ?? null;

/*
|--------------------------------------------------------------------------
| COMPANY-LEVEL PRODUCTION DATA
|--------------------------------------------------------------------------
| The dashboard uses stock_production as the primary production table.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTION RECORDS
|--------------------------------------------------------------------------
*/
$totalProductionsStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM stock_production
    WHERE company_id = ?
");
$totalProductionsStmt->execute([$company_id]);
$totalProductions = (int)$totalProductionsStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL QUANTITY PRODUCED
|--------------------------------------------------------------------------
*/
$totalQuantityStmt = $pdo->prepare("
    SELECT COALESCE(SUM(quantity_produced), 0)
    FROM stock_production
    WHERE company_id = ?
");
$totalQuantityStmt->execute([$company_id]);
$totalQuantity = (float)$totalQuantityStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTION COST
|--------------------------------------------------------------------------
*/
$totalProductionCostStmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_cost), 0)
    FROM stock_production
    WHERE company_id = ?
");
$totalProductionCostStmt->execute([$company_id]);
$totalProductionCost = (float)$totalProductionCostStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTION VALUE
|--------------------------------------------------------------------------
*/
$totalProductionValueStmt = $pdo->prepare("
    SELECT COALESCE(
        SUM(quantity_produced * unit_selling_price),
        0
    )
    FROM stock_production
    WHERE company_id = ?
");
$totalProductionValueStmt->execute([$company_id]);
$totalProductionValue = (float)$totalProductionValueStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTION PROFIT
|--------------------------------------------------------------------------
*/
$totalProductionProfitStmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_profit), 0)
    FROM stock_production
    WHERE company_id = ?
");
$totalProductionProfitStmt->execute([$company_id]);
$totalProductionProfit = (float)$totalProductionProfitStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL CATEGORIES
|--------------------------------------------------------------------------
*/
$totalCategoriesStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT category_id)
    FROM stock_production
    WHERE company_id = ?
      AND category_id IS NOT NULL
      AND category_id > 0
");
$totalCategoriesStmt->execute([$company_id]);
$totalCategories = (int)$totalCategoriesStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| PRODUCTION RECORDS
|--------------------------------------------------------------------------
| stock_production is used as the primary source.
|--------------------------------------------------------------------------
*/
$productionStmt = $pdo->prepare("
    SELECT
        sp.id,
        sp.product_id,
        sp.product_name,
        sp.batch_number,
        sp.report_number,

        0 AS previous_quantity_produced,
        COALESCE(sp.quantity_produced, 0) AS quantity_produced,

        0 AS previous_unit_cost,
        COALESCE(sp.unit_cost, 0) AS unit_cost,

        0 AS previous_total_cost,
        COALESCE(sp.total_cost, 0) AS total_cost,

        0 AS previous_profit_margin,
        COALESCE(sp.profit_margin, 0) AS profit_margin,

        0 AS previous_unit_selling_price,
        COALESCE(sp.unit_selling_price, 0) AS unit_selling_price,

        '' AS previous_expenses_break_down,
        COALESCE(sp.expenses_breakdown, '') AS expenses_break_down,

        sp.status,
        sp.created_at,
        sp.updated_at,

        l.name AS location_name,
        c.name AS category_name

    FROM stock_production sp

    LEFT JOIN locations l
        ON l.id = sp.location_id
        AND l.company_id = sp.company_id

    LEFT JOIN product_categories c
        ON c.id = sp.category_id
        AND c.company_id = sp.company_id

    WHERE sp.company_id = ?

    ORDER BY sp.id DESC
");

$productionStmt->execute([$company_id]);
$productions = $productionStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CATEGORY CHART
|--------------------------------------------------------------------------
*/
$categoriesStmt = $pdo->prepare("
    SELECT
        c.name AS category,
        COALESCE(SUM(sp.quantity_produced), 0) AS total

    FROM product_categories c

    LEFT JOIN stock_production sp
        ON sp.category_id = c.id
        AND sp.company_id = ?

    WHERE c.company_id = ?

    GROUP BY c.id, c.name

    HAVING total > 0

    ORDER BY total DESC, c.name ASC
");

$categoriesStmt->execute([
    $company_id,
    $company_id
]);

$categoriesDataArr = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$categoriesLabels = array_column(
    $categoriesDataArr,
    'category'
);

$categoriesData = array_map(
    'floatval',
    array_column($categoriesDataArr, 'total')
);


/*
|--------------------------------------------------------------------------
| 30-DAY PRODUCTION TREND
|--------------------------------------------------------------------------
*/
$trendStmt = $pdo->prepare("
    SELECT
        DATE(created_at) AS date,
        COALESCE(SUM(quantity_produced), 0) AS total

    FROM stock_production

    WHERE company_id = ?
      AND DATE(created_at) >= DATE_SUB(
          CURDATE(),
          INTERVAL 30 DAY
      )

    GROUP BY DATE(created_at)

    ORDER BY DATE(created_at) ASC
");

$trendStmt->execute([$company_id]);

$trendDataArr = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$trendLabels = array_column(
    $trendDataArr,
    'date'
);

$trendData = array_map(
    'floatval',
    array_column($trendDataArr, 'total')
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        http-equiv="content-type"
        content="text/html;charset=utf-8"
    >

    <meta
        name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?php echo htmlspecialchars($company_name); ?> | Staff Area
    </title>

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >

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

        $watch(
            'darkMode',
            value => localStorage.setItem(
                'darkMode',
                JSON.stringify(value)
            )
        )
    "

    :class="{
        'dark bg-gray-900': darkMode === true
    }"
>


<!-- =========================================================
     PAGE WRAPPER
========================================================= -->

<div class="flex h-screen overflow-hidden">


    <!-- SIDEBAR -->

    <?php include('sidebar.php'); ?>


    <!-- CONTENT AREA -->

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
    >


        <!-- MAIN -->

        <main>

            <?php include('header.php'); ?>


            <style>

                /* =========================================================
                   PRODUCTION DASHBOARD
                   ========================================================= */

                .production-dashboard{

                    --pd-bg:#f5f7fb;
                    --pd-card:rgba(255,255,255,.72);
                    --pd-card-solid:#ffffff;
                    --pd-border:rgba(15,23,42,.075);
                    --pd-text:#0f172a;
                    --pd-muted:#64748b;
                    --pd-blue:#2563eb;
                    --pd-green:#059669;
                    --pd-purple:#7c3aed;
                    --pd-orange:#d97706;

                    width:100%;
                    min-height:100%;

                    padding:24px;

                    background:
                        radial-gradient(
                            circle at 8% 0%,
                            rgba(37,99,235,.075),
                            transparent 25%
                        ),

                        radial-gradient(
                            circle at 96% 8%,
                            rgba(124,58,237,.06),
                            transparent 24%
                        ),

                        var(--pd-bg);
                }


                .production-dashboard *{
                    box-sizing:border-box;
                }


                .pd-container{

                    width:100%;
                    max-width:1700px;

                    margin:0 auto;
                }


                /* =========================================================
                   HERO
                ========================================================= */

                .pd-hero{

                    position:relative;
                    overflow:hidden;

                    display:flex;
                    align-items:center;
                    justify-content:space-between;

                    gap:20px;

                    min-height:145px;

                    padding:28px 30px;

                    margin-bottom:18px;

                    border:1px solid rgba(255,255,255,.85);

                    border-radius:24px;

                    background:
                        linear-gradient(
                            135deg,
                            rgba(255,255,255,.86),
                            rgba(255,255,255,.58)
                        );

                    box-shadow:
                        0 18px 50px rgba(15,23,42,.07);

                    backdrop-filter:blur(22px);
                    -webkit-backdrop-filter:blur(22px);
                }


                .pd-hero:before{

                    content:"";

                    position:absolute;

                    width:250px;
                    height:250px;

                    right:-90px;
                    top:-145px;

                    border-radius:50%;

                    background:
                        rgba(37,99,235,.08);
                }


                .pd-hero-content{

                    position:relative;
                    z-index:1;
                }


                .pd-eyebrow{

                    display:flex;
                    align-items:center;

                    gap:8px;

                    color:var(--pd-blue);

                    font-size:10px;
                    line-height:1;

                    font-weight:800;

                    letter-spacing:.15em;

                    text-transform:uppercase;
                }


                .pd-eyebrow i{
                    font-size:12px;
                }


                .pd-title{

                    margin:9px 0 5px;

                    color:var(--pd-text);

                    font-size:clamp(24px,3vw,36px);

                    line-height:1.08;

                    font-weight:850;

                    letter-spacing:-.045em;
                }


                .pd-subtitle{

                    margin:0;

                    color:var(--pd-muted);

                    font-size:13px;
                }


                .pd-actions{

                    position:relative;
                    z-index:2;

                    display:flex;
                    align-items:center;

                    gap:9px;
                }


                .pd-action{

                    width:43px;
                    height:43px;

                    border:1px solid var(--pd-border);

                    border-radius:13px;

                    background:rgba(255,255,255,.78);

                    color:#475569;

                    cursor:pointer;

                    box-shadow:
                        0 8px 22px rgba(15,23,42,.05);

                    transition:.2s ease;
                }


                .pd-action:hover{

                    transform:translateY(-2px);

                    background:#fff;

                    color:var(--pd-blue);
                }


                /* =========================================================
                   KPI CARDS
                ========================================================= */

                .pd-kpis{

                    display:grid;

                    grid-template-columns:
                        repeat(4,minmax(0,1fr));

                    gap:14px;

                    margin-bottom:18px;
                }


                .pd-kpi{

                    position:relative;

                    min-height:142px;

                    padding:19px;

                    overflow:hidden;

                    border:1px solid var(--pd-border);

                    border-radius:20px;

                    background:var(--pd-card);

                    box-shadow:
                        0 16px 42px rgba(15,23,42,.065);

                    backdrop-filter:blur(20px);

                    -webkit-backdrop-filter:blur(20px);

                    transition:.22s ease;
                }


                .pd-kpi:hover{

                    transform:translateY(-3px);

                    box-shadow:
                        0 23px 52px rgba(15,23,42,.10);
                }


                .pd-kpi:after{

                    content:"";

                    position:absolute;

                    width:90px;
                    height:90px;

                    right:-35px;
                    bottom:-40px;

                    border-radius:50%;

                    background:
                        rgba(37,99,235,.045);
                }


                .pd-kpi-top{

                    display:flex;

                    align-items:center;

                    justify-content:space-between;

                    margin-bottom:18px;
                }


                .pd-kpi-icon{

                    width:40px;
                    height:40px;

                    display:grid;
                    place-items:center;

                    border-radius:12px;

                    background:#eff6ff;

                    color:var(--pd-blue);

                    font-size:15px;
                }


                .pd-kpi:nth-child(2)
                .pd-kpi-icon{

                    background:#ecfdf5;
                    color:var(--pd-green);
                }


                .pd-kpi:nth-child(3)
                .pd-kpi-icon{

                    background:#fff7ed;
                    color:var(--pd-orange);
                }


                .pd-kpi:nth-child(4)
                .pd-kpi-icon{

                    background:#f5f3ff;
                    color:var(--pd-purple);
                }


                .pd-kpi-label{

                    color:var(--pd-muted);

                    font-size:11px;

                    font-weight:700;
                }


                .pd-kpi-value{

                    margin-top:5px;

                    color:var(--pd-text);

                    font-size:clamp(20px,2vw,27px);

                    line-height:1.15;

                    font-weight:850;

                    letter-spacing:-.035em;

                    white-space:nowrap;

                    transition:
                        opacity .2s ease;
                }


                .pd-kpi-value.blue{

                    color:var(--pd-blue);
                }


                .pd-kpi-note{

                    margin-top:7px;

                    color:#94a3b8;

                    font-size:10px;
                }


                /* =========================================================
                   CARDS / ANALYTICS
                ========================================================= */

                .pd-insight-grid{

                    display:grid;

                    grid-template-columns:
                        minmax(0,1.35fr)
                        minmax(300px,.75fr);

                    gap:18px;

                    margin-bottom:18px;
                }


                .pd-card{

                    overflow:hidden;

                    border:1px solid var(--pd-border);

                    border-radius:20px;

                    background:var(--pd-card);

                    box-shadow:
                        0 16px 42px rgba(15,23,42,.065);

                    backdrop-filter:blur(20px);

                    -webkit-backdrop-filter:blur(20px);
                }


                .pd-card-head{

                    display:flex;

                    align-items:center;

                    justify-content:space-between;

                    gap:12px;

                    padding:19px 20px 0;
                }


                .pd-card-title{

                    display:flex;

                    align-items:center;

                    gap:9px;

                    color:var(--pd-text);

                    font-size:13px;

                    font-weight:800;
                }


                .pd-card-title i{

                    color:var(--pd-blue);
                }


                .pd-badge{

                    display:inline-flex;

                    align-items:center;

                    gap:6px;

                    padding:6px 9px;

                    border:1px solid var(--pd-border);

                    border-radius:999px;

                    background:rgba(255,255,255,.7);

                    color:#64748b;

                    font-size:10px;

                    font-weight:700;

                    white-space:nowrap;
                }


                .pd-chart{

                    height:275px;

                    padding:13px 18px 18px;
                }


                /* =========================================================
                   TOOLBAR
                ========================================================= */

                .pd-table-card{

                    margin-top:0;
                }


                .pd-toolbar{

                    display:flex;

                    align-items:center;

                    justify-content:space-between;

                    gap:10px;

                    flex-wrap:wrap;

                    padding:15px 16px;

                    margin-top:15px;

                    border-top:1px solid var(--pd-border);

                    border-bottom:1px solid var(--pd-border);
                }


                .pd-search{

                    position:relative;

                    flex:1;

                    min-width:260px;
                }


                .pd-search i{

                    position:absolute;

                    left:13px;
                    top:50%;

                    transform:translateY(-50%);

                    color:#94a3b8;

                    font-size:12px;
                }


                .pd-search input,
                .pd-filter{

                    height:41px;

                    border:1px solid var(--pd-border);

                    border-radius:12px;

                    outline:none;

                    background:rgba(255,255,255,.8);

                    color:#334155;

                    transition:.2s ease;
                }


                .pd-search input{

                    width:100%;

                    padding:
                        0
                        13px
                        0
                        37px;

                    font-size:12px;
                }


                .pd-filter{

                    min-width:145px;

                    padding:0 11px;

                    font-size:11px;
                }


                .pd-search input:focus,
                .pd-filter:focus{

                    border-color:
                        rgba(37,99,235,.28);

                    box-shadow:
                        0 0 0 4px
                        rgba(37,99,235,.07);
                }


                .pd-filters{

                    display:flex;

                    align-items:center;

                    gap:7px;

                    flex-wrap:wrap;
                }


                .pd-clear{

                    width:41px;
                    height:41px;

                    border:1px solid var(--pd-border);

                    border-radius:12px;

                    background:#fff;

                    color:#64748b;

                    cursor:pointer;
                }


                .pd-clear:hover{

                    color:#dc2626;
                }


                /* =========================================================
                   TABLE
                ========================================================= */

                .pd-table-wrap{

                    width:100%;

                    overflow:auto;

                    max-height:610px;
                }


                .pd-table{

                    width:100%;

                    min-width:1550px;

                    border-collapse:separate;

                    border-spacing:0;
                }


                .pd-table thead th{

                    position:sticky;

                    top:0;

                    z-index:3;

                    padding:12px 13px;

                    border-bottom:
                        1px solid var(--pd-border);

                    background:
                        rgba(248,250,252,.96);

                    color:#64748b;

                    font-size:9px;

                    font-weight:800;

                    letter-spacing:.06em;

                    text-align:left;

                    text-transform:uppercase;

                    white-space:nowrap;
                }


                .pd-table tbody td{

                    padding:13px;

                    border-bottom:
                        1px solid rgba(15,23,42,.055);

                    color:#475569;

                    font-size:11px;

                    white-space:nowrap;
                }


                .pd-table tbody tr{

                    transition:.16s ease;
                }


                .pd-table tbody tr:hover{

                    background:
                        rgba(239,246,255,.55);
                }


                .pd-index{

                    display:inline-grid;

                    width:27px;
                    height:27px;

                    place-items:center;

                    border-radius:9px;

                    background:#eff6ff;

                    color:var(--pd-blue);

                    font-size:10px;

                    font-weight:800;
                }


                .pd-product{

                    color:#0f172a;

                    font-weight:750;
                }


                .pd-number{

                    color:#334155;

                    font-variant-numeric:
                        tabular-nums;
                }


                .pd-money{

                    color:#0f172a;

                    font-weight:700;

                    font-variant-numeric:
                        tabular-nums;
                }


                .pd-margin{

                    display:inline-flex;

                    padding:5px 8px;

                    border-radius:999px;

                    background:#ecfdf5;

                    color:#047857;

                    font-size:10px;

                    font-weight:800;
                }


                .pd-report{

                    display:inline-flex;

                    align-items:center;

                    gap:6px;

                    color:var(--pd-blue);

                    font-weight:750;
                }


                .pd-report:hover{

                    text-decoration:underline;
                }


                .pd-missing{

                    display:inline-flex;

                    align-items:center;

                    gap:6px;

                    color:#94a3b8;
                }


                .pd-empty{

                    padding:45px 20px;

                    text-align:center;

                    color:#94a3b8;

                    font-size:12px;
                }


                /* =========================================================
                   RESPONSIVE
                ========================================================= */

                @media(max-width:1200px){

                    .pd-kpis{

                        grid-template-columns:
                            repeat(2,1fr);
                    }


                    .pd-insight-grid{

                        grid-template-columns:1fr;
                    }
                }


                @media(max-width:700px){

                    .production-dashboard{

                        padding:12px;
                    }


                    .pd-hero{

                        min-height:125px;

                        padding:20px 18px;

                        border-radius:18px;
                    }


                    .pd-actions{

                        display:none;
                    }


                    .pd-title{

                        font-size:25px;
                    }


                    .pd-subtitle{

                        font-size:11px;

                        max-width:90%;
                    }


                    .pd-kpis{

                        grid-template-columns:
                            1fr 1fr;

                        gap:9px;
                    }


                    .pd-kpi{

                        min-height:123px;

                        padding:14px;

                        border-radius:16px;
                    }


                    .pd-kpi-top{

                        margin-bottom:12px;
                    }


                    .pd-kpi-icon{

                        width:35px;
                        height:35px;

                        border-radius:10px;
                    }


                    .pd-kpi-value{

                        font-size:18px;
                    }


                    .pd-kpi-note{

                        font-size:9px;
                    }


                    .pd-card{

                        border-radius:17px;
                    }


                    .pd-chart{

                        height:235px;

                        padding:10px;
                    }


                    .pd-card-head{

                        padding:15px 15px 0;
                    }


                    .pd-toolbar{

                        padding:11px;
                    }


                    .pd-search{

                        min-width:100%;
                    }


                    .pd-filter{

                        flex:1;

                        min-width:0;
                    }
                }


                @media(max-width:420px){

                    .pd-kpis{

                        grid-template-columns:1fr;
                    }


                    .pd-kpi{

                        min-height:112px;
                    }
                }


                @media print{

                    .production-dashboard{

                        padding:0;

                        background:#fff;
                    }


                    .pd-actions,
                    .pd-toolbar{

                        display:none!important;
                    }


                    .pd-card,
                    .pd-kpi,
                    .pd-hero{

                        box-shadow:none;
                    }
                }

            </style>


            <!-- =========================================================
                 DASHBOARD
            ========================================================= -->

            <div class="production-dashboard">

                <div class="pd-container">


                    <!-- =====================================================
                         HERO
                    ===================================================== -->

                    <section class="pd-hero">

                        <div class="pd-hero-content">

                            <div class="pd-eyebrow">

                                <i class="fa-solid fa-chart-simple"></i>

                                Production Intelligence

                            </div>


                            <h1 class="pd-title">

                                <?= htmlspecialchars($company_name) ?>

                            </h1>


                            <p class="pd-subtitle">

                                Monitor production output, costs,
                                commercial value and profitability
                                from one operational view.

                            </p>

                        </div>


                        <div class="pd-actions">

                            <button
                                type="button"
                                class="pd-action"
                                title="Refresh"
                                onclick="location.reload()"
                            >

                                <i
                                    class="fa-solid fa-arrows-rotate"
                                ></i>

                            </button>


                            <button
                                type="button"
                                class="pd-action"
                                title="Print"
                                onclick="window.print()"
                            >

                                <i
                                    class="fa-solid fa-print"
                                ></i>

                            </button>

                        </div>

                    </section>


                    <!-- =====================================================
                         KPI CARDS
                    ===================================================== -->

                    <section class="pd-kpis">


                        <!-- PRODUCTION RECORDS -->

                        <div class="pd-kpi">

                            <div class="pd-kpi-top">

                                <div class="pd-kpi-icon">

                                    <i
                                        class="fa-solid fa-layer-group"
                                    ></i>

                                </div>


                                <span class="pd-badge">

                                    <i
                                        class="fa-solid fa-database"
                                    ></i>

                                    Live

                                </span>

                            </div>


                            <div class="pd-kpi-label">

                                Production Records

                            </div>


                            <div
                                class="pd-kpi-value"
                                id="totalProductions"
                                data-current-value="<?= (float)$totalProductions ?>"
                            >

                                <?= number_format($totalProductions) ?>

                            </div>


                            <div class="pd-kpi-note">

                                Recorded production entries

                            </div>

                        </div>


                        <!-- UNITS PRODUCED -->

                        <div class="pd-kpi">

                            <div class="pd-kpi-top">

                                <div class="pd-kpi-icon">

                                    <i
                                        class="fa-solid fa-boxes-stacked"
                                    ></i>

                                </div>


                                <span class="pd-badge">

                                    <i
                                        class="fa-solid fa-cubes"
                                    ></i>

                                    Output

                                </span>

                            </div>


                            <div class="pd-kpi-label">

                                Units Produced

                            </div>


                            <div
                                class="pd-kpi-value"
                                id="totalQuantity"
                                data-current-value="<?= (float)$totalQuantity ?>"
                            >

                                <?= number_format($totalQuantity) ?>

                            </div>


                            <div class="pd-kpi-note">

                                Total production quantity

                            </div>

                        </div>


                        <!-- PRODUCTION COST -->

                        <div class="pd-kpi">

                            <div class="pd-kpi-top">

                                <div class="pd-kpi-icon">

                                    <i
                                        class="fa-solid fa-coins"
                                    ></i>

                                </div>


                                <span class="pd-badge">

                                    <i
                                        class="fa-solid fa-calculator"
                                    ></i>

                                    Cost

                                </span>

                            </div>


                            <div class="pd-kpi-label">

                                Production Cost

                            </div>


                            <div class="pd-kpi-value">

                                ₦<span
                                    id="totalProductionCost"
                                    data-current-value="<?= (float)$totalProductionCost ?>"
                                ><?= number_format($totalProductionCost, 2) ?></span>

                            </div>


                            <div class="pd-kpi-note">

                                Current recorded cost base

                            </div>

                        </div>


                        <!-- PRODUCTION VALUE -->

                        <div class="pd-kpi">

                            <div class="pd-kpi-top">

                                <div class="pd-kpi-icon">

                                    <i
                                        class="fa-solid fa-chart-line"
                                    ></i>

                                </div>


                                <span class="pd-badge">

                                    <i
                                        class="fa-solid fa-arrow-up-right-dots"
                                    ></i>

                                    Value

                                </span>

                            </div>


                            <div class="pd-kpi-label">

                                Production Value

                            </div>


                            <div class="pd-kpi-value blue">

                                ₦<span
                                    id="totalProductionValue"
                                    data-current-value="<?= (float)$totalProductionValue ?>"
                                ><?= number_format($totalProductionValue, 2) ?></span>

                            </div>


                            <div class="pd-kpi-note">

                                Expected selling value

                            </div>

                        </div>


                    </section>


                    <!-- =====================================================
                         ANALYTICS
                    ===================================================== -->

                    <section class="pd-insight-grid">


                        <!-- TREND -->

                        <div class="pd-card">

                            <div class="pd-card-head">

                                <div class="pd-card-title">

                                    <i
                                        class="fa-solid fa-chart-area"
                                    ></i>

                                    30-Day Production Trend

                                </div>


                                <span class="pd-badge">

                                    <i
                                        class="fa-regular fa-clock"
                                    ></i>

                                    Last 30 days

                                </span>

                            </div>


                            <div class="pd-chart">

                                <canvas
                                    id="stockTrendChart"
                                ></canvas>

                            </div>

                        </div>


                        <!-- CATEGORY -->

                        <div class="pd-card">

                            <div class="pd-card-head">

                                <div class="pd-card-title">

                                    <i
                                        class="fa-solid fa-chart-pie"
                                    ></i>

                                    Production by Category

                                </div>


                                <span class="pd-badge">

                                    Portfolio mix

                                </span>

                            </div>


                            <div class="pd-chart">

                                <canvas
                                    id="categoryChart"
                                ></canvas>

                            </div>

                        </div>

                    </section>


                    <!-- =====================================================
                         PRODUCTION RECORDS
                    ===================================================== -->

                    <section
                        class="pd-card pd-table-card"
                    >


                        <div class="pd-card-head">

                            <div class="pd-card-title">

                                <i
                                    class="fa-solid fa-table-list"
                                ></i>

                                Production Records

                            </div>


                            <span
                                class="pd-badge"
                                id="productionRecordBadge"
                            >

                                <i
                                    class="fa-solid fa-list-check"
                                ></i>

                                <?= count($productions) ?> records

                            </span>

                        </div>


                        <!-- =================================================
                             FILTER TOOLBAR
                        ================================================= -->

                        <div class="pd-toolbar">


                            <!-- SEARCH -->

                            <div class="pd-search">

                                <i
                                    class="fa-solid fa-magnifying-glass"
                                ></i>


                                <input
                                    id="searchInput"
                                    type="search"
                                    placeholder="Search product, batch or report..."
                                    autocomplete="off"
                                >

                            </div>


                            <div class="pd-filters">


                                <!-- LOCATION -->

                                <select
                                    id="locationFilter"
                                    class="pd-filter"
                                >

                                    <option value="">
                                        All locations
                                    </option>


                                    <?php

                                    $pdLocations = array_unique(
                                        array_filter(
                                            array_column(
                                                $productions,
                                                'location_name'
                                            )
                                        )
                                    );

                                    foreach(
                                        $pdLocations
                                        as $pdLocation
                                    ):

                                    ?>

                                        <option
                                            value="<?= htmlspecialchars($pdLocation) ?>"
                                        >

                                            <?= htmlspecialchars($pdLocation) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <!-- DATE -->

                                <input
                                    id="dateFilter"
                                    class="pd-filter"
                                    type="date"
                                    title="Filter by date"
                                >


                                <!-- CLEAR -->

                                <button
                                    type="button"
                                    class="pd-clear"
                                    id="clearFilters"
                                    title="Clear filters"
                                >

                                    <i
                                        class="fa-solid fa-filter-circle-xmark"
                                    ></i>

                                </button>

                            </div>

                        </div>


                        <!-- =================================================
                             TABLE
                        ================================================= -->

                        <div class="pd-table-wrap">

                            <table class="pd-table">


                                <thead>

                                    <tr>

                                        <th>S/N</th>

                                        <th>Product</th>

                                        <th>Batch No.</th>

                                        <th>Report No.</th>

                                        <th>Previous Quantity</th>

                                        <th>Quantity</th>

                                        <th>Previous Cost Price</th>

                                        <th>Cost Price</th>

                                        <th>Previous Total Cost</th>

                                        <th>Total Cost</th>

                                        <th>Previous Profit Margin</th>

                                        <th>Profit Margin</th>

                                        <th>Previous Selling Price</th>

                                        <th>Selling Price</th>

                                        <th>Previous Expenses Breakdown</th>

                                        <th>Expenses Breakdown</th>

                                        <th>Updated</th>

                                        <th>Reports</th>

                                    </tr>

                                </thead>


                                <tbody id="stockTableBody">

                                <?php

                                $count = 1;


                                foreach(
                                    $productions
                                    as $row
                                ):


                                    $productionValue =
                                        (float)$row['quantity_produced']
                                        *
                                        (float)$row['unit_selling_price'];


                                    $profit =
                                        (
                                            (float)$row['unit_selling_price']
                                            -
                                            (float)$row['unit_cost']
                                        )
                                        *
                                        (float)$row['quantity_produced'];


                                    /*
                                     * Use updated_at for date filtering.
                                     */
                                    $rowDate = '';

                                    if (
                                        !empty(
                                            $row['updated_at']
                                        )
                                    ) {

                                        $rowDate =
                                            substr(
                                                $row['updated_at'],
                                                0,
                                                10
                                            );
                                    }

                                ?>

                                <tr
                                    class="hover:bg-gray-50 transition"

                                    data-location="<?= htmlspecialchars(
                                        $row['location_name'] ?? ''
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        $row['status'] ?? ''
                                    ) ?>"

                                    data-date="<?= htmlspecialchars(
                                        $rowDate
                                    ) ?>"

                                    /*
                                     * These values are used by the
                                     * JavaScript KPI calculator.
                                     */

                                    data-quantity="<?= (float)$row['quantity_produced'] ?>"

                                    data-cost="<?= (float)$row['total_cost'] ?>"

                                    data-value="<?= (float)$productionValue ?>"
                                >


                                    <!-- S/N -->

                                    <td>

                                        <span class="pd-index">

                                            <?= $count++ ?>

                                        </span>

                                    </td>


                                    <!-- PRODUCT -->

                                    <td class="pd-product">

                                        <?= htmlspecialchars(
                                            $row['product_name'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- BATCH -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['batch_number'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- REPORT -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['report_number'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- PREVIOUS QUANTITY -->

                                    <td class="pd-number">

                                        <?= number_format(
                                            (float)$row['previous_quantity_produced']
                                        ) ?>

                                    </td>


                                    <!-- QUANTITY -->

                                    <td class="pd-number">

                                        <strong>

                                            <?= number_format(
                                                (float)$row['quantity_produced']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- PREVIOUS UNIT COST -->

                                    <td class="pd-money">

                                        ₦<?= number_format(
                                            (float)$row['previous_unit_cost'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- UNIT COST -->

                                    <td class="pd-money">

                                        ₦<?= number_format(
                                            (float)$row['unit_cost'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- PREVIOUS TOTAL COST -->

                                    <td class="pd-money">

                                        ₦<?= number_format(
                                            (float)$row['previous_total_cost'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- TOTAL COST -->

                                    <td class="pd-money">

                                        ₦<?= number_format(
                                            (float)$row['total_cost'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- PREVIOUS PROFIT MARGIN -->

                                    <td>

                                        <?= number_format(
                                            (float)$row['previous_profit_margin']
                                        ) ?>%

                                    </td>


                                    <!-- PROFIT MARGIN -->

                                    <td>

                                        <span class="pd-margin">

                                            <?= number_format(
                                                (float)$row['profit_margin']
                                            ) ?>%

                                        </span>

                                    </td>


                                    <!-- PREVIOUS SELLING PRICE -->

                                    <td class="pd-money">

                                        ₦<?= number_format(
                                            (float)$row['previous_unit_selling_price'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- SELLING PRICE -->

                                    <td class="pd-money">

                                        ₦<?= number_format(
                                            (float)$row['unit_selling_price'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- PREVIOUS EXPENSES -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['previous_expenses_break_down'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- EXPENSES -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['expenses_break_down'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- UPDATED -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['updated_at'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- REPORT -->

                                    <td>

                                        <?php

                                        $pdf_file =
                                            "../production_reports/Production_Report_"
                                            .
                                            $row['report_number']
                                            .
                                            ".pdf";


                                        if (
                                            file_exists($pdf_file)
                                        ):

                                        ?>

                                            <a
                                                href="<?= htmlspecialchars(
                                                    $pdf_file
                                                ) ?>"

                                                target="_blank"

                                                rel="noopener"

                                                class="pd-report"
                                            >

                                                <i
                                                    class="fa-regular fa-file-pdf"
                                                ></i>

                                                View

                                            </a>

                                        <?php else: ?>

                                            <span
                                                class="pd-missing"
                                            >

                                                <i
                                                    class="fa-regular fa-file-circle-xmark"
                                                ></i>

                                                Not found

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                                <?php endforeach; ?>

                                </tbody>

                            </table>


                            <?php if(empty($productions)): ?>

                                <div class="pd-empty">

                                    <i
                                        class="fa-regular fa-folder-open"
                                        style="
                                            font-size:28px;
                                            margin-bottom:10px;
                                        "
                                    ></i>


                                    <div>

                                        No production records found.

                                    </div>

                                </div>

                            <?php endif; ?>


                        </div>

                    </section>

                </div>

            </div>

        </main>


        <!-- MAIN CONTENT END -->

    </div>


    <!-- CONTENT AREA END -->

</div>


<!-- FOOTER -->

<?php include('footer.php'); ?>


<!-- =========================================================
     EXISTING BUNDLE
========================================================= -->

<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>


<script
    defer
    src="bundle.js"
></script>


<script
    defer
    src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015"
    integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ=="
    data-cf-beacon='{
        "rayId":"95aeba634cdfb6e3",
        "version":"2025.6.2",
        "r":1,
        "serverTiming":{
            "name":{
                "cfExtPri":true,
                "cfEdge":true,
                "cfOrigin":true,
                "cfL4":true,
                "cfSpeedBrain":true,
                "cfCacheStatus":true
            }
        },
        "token":"67f7a278e3374824ae6dd92295d38f77",
        "b":1
    }'
    crossorigin="anonymous"
></script>


<!-- =========================================================
     CHART.JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /* =====================================================
           KPI ELEMENTS
        ===================================================== */

        const totalProductionsEl =
            document.getElementById(
                'totalProductions'
            );


        const totalQuantityEl =
            document.getElementById(
                'totalQuantity'
            );


        const totalProductionCostEl =
            document.getElementById(
                'totalProductionCost'
            );


        const totalProductionValueEl =
            document.getElementById(
                'totalProductionValue'
            );


        const recordBadge =
            document.getElementById(
                'productionRecordBadge'
            );


        /* =====================================================
           INITIAL KPI VALUES
        ===================================================== */

        const initialKPIs = {

            productions:
                <?= (float)$totalProductions ?>,

            quantity:
                <?= (float)$totalQuantity ?>,

            cost:
                <?= (float)$totalProductionCost ?>,

            value:
                <?= (float)$totalProductionValue ?>

        };


        /* =====================================================
           NUMBER FORMATTER
        ===================================================== */

        function formatNumber(
            value,
            decimals = 0
        ) {

            return Number(
                value || 0
            ).toLocaleString(
                'en-NG',
                {
                    minimumFractionDigits:
                        decimals,

                    maximumFractionDigits:
                        decimals
                }
            );

        }


        /* =====================================================
           KPI ANIMATION
        ===================================================== */

        function animateNumber(
            element,
            target,
            decimals = 0
        ) {

            if (!element) {
                return;
            }


            const currentValue =
                parseFloat(
                    element.dataset.currentValue || 0
                );


            const targetValue =
                Number(target || 0);


            /*
             * If there is no change, simply
             * format the current value.
             */

            if (
                Math.abs(
                    currentValue - targetValue
                ) < 0.00001
            ) {

                element.textContent =
                    formatNumber(
                        targetValue,
                        decimals
                    );

                element.dataset.currentValue =
                    targetValue;

                return;
            }


            const duration = 350;

            const startTime =
                performance.now();


            function animate(
                currentTime
            ) {

                const elapsed =
                    currentTime -
                    startTime;


                const progress =
                    Math.min(
                        elapsed / duration,
                        1
                    );


                /*
                 * Ease-out effect.
                 */

                const eased =
                    1 -
                    Math.pow(
                        1 - progress,
                        3
                    );


                const current =
                    currentValue +
                    (
                        (targetValue - currentValue)
                        *
                        eased
                    );


                element.textContent =
                    formatNumber(
                        current,
                        decimals
                    );


                if (
                    progress < 1
                ) {

                    requestAnimationFrame(
                        animate
                    );

                } else {

                    element.textContent =
                        formatNumber(
                            targetValue,
                            decimals
                        );


                    element.dataset.currentValue =
                        targetValue;

                }

            }


            requestAnimationFrame(
                animate
            );

        }


        /* =====================================================
           GET ALL PRODUCTION ROWS
        ===================================================== */

        function getProductionRows() {

            return document.querySelectorAll(
                '#stockTableBody tr'
            );

        }


        /* =====================================================
           UPDATE KPIs BASED ON VISIBLE ROWS
        ===================================================== */

        function updateKPIs() {


            const rows =
                getProductionRows();


            let productionRecords = 0;

            let quantityProduced = 0;

            let productionCost = 0;

            let productionValue = 0;


            rows.forEach(
                function (row) {


                    /*
                     * Only calculate from rows
                     * that passed the filters.
                     */

                    if (
                        row.style.display === 'none'
                    ) {

                        return;

                    }


                    productionRecords++;


                    quantityProduced +=
                        parseFloat(
                            row.dataset.quantity || 0
                        );


                    productionCost +=
                        parseFloat(
                            row.dataset.cost || 0
                        );


                    productionValue +=
                        parseFloat(
                            row.dataset.value || 0
                        );

                }
            );


            /* =================================================
               UPDATE KPI 1
            ================================================= */

            animateNumber(
                totalProductionsEl,
                productionRecords,
                0
            );


            /* =================================================
               UPDATE KPI 2
            ================================================= */

            animateNumber(
                totalQuantityEl,
                quantityProduced,
                0
            );


            /* =================================================
               UPDATE KPI 3
            ================================================= */

            animateNumber(
                totalProductionCostEl,
                productionCost,
                2
            );


            /* =================================================
               UPDATE KPI 4
            ================================================= */

            animateNumber(
                totalProductionValueEl,
                productionValue,
                2
            );


            /* =================================================
               UPDATE RECORD BADGE
            ================================================= */

            if (recordBadge) {

                recordBadge.innerHTML =
                    '<i class="fa-solid fa-list-check"></i> '
                    +
                    productionRecords
                    +
                    ' records';

            }

        }


        /* =====================================================
           FILTER TABLE
        ===================================================== */

        function filterTable() {


            const searchInput =
                document.getElementById(
                    'searchInput'
                );


            const locationFilter =
                document.getElementById(
                    'locationFilter'
                );


            const dateFilter =
                document.getElementById(
                    'dateFilter'
                );


            const search =
                (
                    searchInput?.value || ''
                )
                .toLowerCase()
                .trim();


            const location =
                (
                    locationFilter?.value || ''
                )
                .toLowerCase()
                .trim();


            const date =
                dateFilter?.value || '';


            const rows =
                getProductionRows();


            rows.forEach(
                function (row) {


                    const cells =
                        row.querySelectorAll(
                            'td'
                        );


                    /*
                     * Product
                     */

                    const product =
                        (
                            cells[1]?.textContent
                            ||
                            ''
                        )
                        .toLowerCase()
                        .trim();


                    /*
                     * Batch
                     */

                    const batch =
                        (
                            cells[2]?.textContent
                            ||
                            ''
                        )
                        .toLowerCase()
                        .trim();


                    /*
                     * Report
                     */

                    const report =
                        (
                            cells[3]?.textContent
                            ||
                            ''
                        )
                        .toLowerCase()
                        .trim();


                    /*
                     * Location
                     */

                    const rowLocation =
                        (
                            row.dataset.location
                            ||
                            ''
                        )
                        .toLowerCase()
                        .trim();


                    /*
                     * Date
                     */

                    const rowDate =
                        (
                            row.dataset.date
                            ||
                            ''
                        )
                        .trim();


                    let show = true;


                    /* =========================================
                       SEARCH
                    ========================================= */

                    if (search) {


                        const matchesSearch =

                            product.includes(
                                search
                            )

                            ||

                            batch.includes(
                                search
                            )

                            ||

                            report.includes(
                                search
                            );


                        if (
                            !matchesSearch
                        ) {

                            show = false;

                        }

                    }


                    /* =========================================
                       LOCATION
                    ========================================= */

                    if (
                        location
                        &&
                        rowLocation !== location
                    ) {

                        show = false;

                    }


                    /* =========================================
                       DATE
                    ========================================= */

                    if (
                        date
                        &&
                        rowDate !== date
                    ) {

                        show = false;

                    }


                    /*
                     * Show / hide row.
                     */

                    row.style.display =
                        show
                            ? ''
                            : 'none';

                }
            );


            /*
             * VERY IMPORTANT:
             *
             * Recalculate the KPIs after
             * the rows have been filtered.
             */

            updateKPIs();

        }


        /* =====================================================
           EXPOSE FILTER FUNCTION
           ===================================================== */

        window.filterTable =
            filterTable;


        /* =====================================================
           SEARCH EVENT
        ===================================================== */

        const searchInput =
            document.getElementById(
                'searchInput'
            );


        if (searchInput) {

            searchInput.addEventListener(
                'input',
                filterTable
            );

        }


        /* =====================================================
           LOCATION EVENT
        ===================================================== */

        const locationFilter =
            document.getElementById(
                'locationFilter'
            );


        if (locationFilter) {

            locationFilter.addEventListener(
                'change',
                filterTable
            );

        }


        /* =====================================================
           DATE EVENT
        ===================================================== */

        const dateFilter =
            document.getElementById(
                'dateFilter'
            );


        if (dateFilter) {

            dateFilter.addEventListener(
                'change',
                filterTable
            );

        }


        /* =====================================================
           CLEAR FILTERS
        ===================================================== */

        const clearFilters =
            document.getElementById(
                'clearFilters'
            );


        if (clearFilters) {

            clearFilters.addEventListener(
                'click',
                function () {


                    if (searchInput) {

                        searchInput.value =
                            '';

                    }


                    if (locationFilter) {

                        locationFilter.value =
                            '';

                    }


                    if (dateFilter) {

                        dateFilter.value =
                            '';

                    }


                    /*
                     * Re-run filter.
                     *
                     * Because all filters are now
                     * empty, every row becomes
                     * visible and the KPIs return
                     * to company-wide totals.
                     */

                    filterTable();

                }
            );

        }


        /* =====================================================
           CATEGORY CHART
        ===================================================== */

        const categoryCanvas =
            document.getElementById(
                'categoryChart'
            );


        if (
            categoryCanvas
            &&
            typeof Chart !== 'undefined'
        ) {


            new Chart(
                categoryCanvas,
                {

                    type:'doughnut',

                    data:{

                        labels:
                            <?= json_encode(
                                $categoriesLabels
                            ) ?>,


                        datasets:[{

                            data:
                                <?= json_encode(
                                    $categoriesData
                                ) ?>,


                            backgroundColor:[

                                '#4ade80',

                                '#facc15',

                                '#f87171',

                                '#60a5fa',

                                '#a78bfa'

                            ]

                        }]

                    },


                    options:{

                        responsive:true,

                        maintainAspectRatio:false,

                        animation:{
                            animateScale:true
                        }

                    }

                }
            );

        }


        /* =====================================================
           30-DAY TREND CHART
        ===================================================== */

        const trendCanvas =
            document.getElementById(
                'stockTrendChart'
            );


        if (
            trendCanvas
            &&
            typeof Chart !== 'undefined'
        ) {


            new Chart(
                trendCanvas,
                {

                    type:'line',

                    data:{

                        labels:
                            <?= json_encode(
                                $trendLabels
                            ) ?>,


                        datasets:[{

                            label:
                                'Production',


                            data:
                                <?= json_encode(
                                    $trendData
                                ) ?>,


                            fill:true,


                            backgroundColor:
                                'rgba(96,165,250,0.2)',


                            borderColor:
                                '#3b82f6',


                            tension:.4

                        }]

                    },


                    options:{

                        responsive:true,

                        maintainAspectRatio:false,

                        animation:{
                            duration:1200
                        }

                    }

                }
            );

        }


        /* =====================================================
           INITIAL KPI STATE
        ===================================================== */

        /*
         * Set initial values from PHP.
         */

        totalProductionsEl.dataset.currentValue =
            initialKPIs.productions;


        totalQuantityEl.dataset.currentValue =
            initialKPIs.quantity;


        totalProductionCostEl.dataset.currentValue =
            initialKPIs.cost;


        totalProductionValueEl.dataset.currentValue =
            initialKPIs.value;


        /*
         * Make sure the dashboard starts
         * with all rows visible and the
         * correct company totals.
         */

        updateKPIs();


    }
);

</script>


</body>

</html>