<?php

// ============================================================
// UBAMUS — OVERALL ADMIN COMPANY INTELLIGENCE
// ============================================================
// ROLE:
// Overall Administrator / Company Administrator
//
// SECURITY SCOPE:
// company_id ONLY
//
// IMPORTANT:
// This dashboard is NOT restricted by location_id.
// The administrator sees the complete company's operations
// across every registered location.
// ============================================================


// ============================================================
// INITIAL STATE
// ============================================================

$companyId = null;
$userId    = $_SESSION['user_id'] ?? null;


// ------------------------------------------------------------
// WORKFORCE
// ------------------------------------------------------------

$totalStaffCount       = 0;
$activeStaffCount      = 0;
$suspendedStaffCount   = 0;
$pendingStaffCount     = 0;


// ------------------------------------------------------------
// COMPANY STRUCTURE
// ------------------------------------------------------------

$locationsCount        = 0;
$productsCount         = 0;
$stockedProducts       = 0;
$totalStockQuantity    = 0;

$totalCustomers        = 0;
$totalVendors          = 0;
$totalServices         = 0;


// ------------------------------------------------------------
// SALES
// ------------------------------------------------------------

$totalSales            = 0;
$totalSalesValue       = 0;
$totalCollected        = 0;
$totalOutstanding      = 0;


// ------------------------------------------------------------
// JOBS
// ------------------------------------------------------------

$totalJobs             = 0;
$totalJobValue         = 0;
$totalJobCollected     = 0;
$totalJobOutstanding   = 0;


// ------------------------------------------------------------
// PRODUCTION
// ------------------------------------------------------------

$totalProductionRuns   = 0;
$totalProductionValue  = 0;
$totalProductionProfit = 0;


// ------------------------------------------------------------
// INTELLIGENCE
// ------------------------------------------------------------

$staffUtilization      = 0;
$productCoverage       = 0;
$companyCollected      = 0;
$companyReceivables    = 0;
$companyRevenue        = 0;
$collectionRate        = 0;
$productionMargin      = 0;


// ------------------------------------------------------------
// SYSTEM SIGNAL
// ------------------------------------------------------------

$companySignal         = 'System Ready';
$companySignalClass    = 'stable';


// ============================================================
// VERIFY LOGGED-IN USER AND COMPANY
// ============================================================

if ($userId) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            company_id,
            role,
            status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($adminUser && !empty($adminUser['company_id'])) {

        $companyId = (int)$adminUser['company_id'];


        // ====================================================
        // STAFF INTELLIGENCE
        // ====================================================

        $stmtStaff = $pdo->prepare("
            SELECT

                COUNT(*) AS total_staff,

                SUM(
                    CASE
                        WHEN LOWER(status) = 'active'
                        THEN 1
                        ELSE 0
                    END
                ) AS active_staff,

                SUM(
                    CASE
                        WHEN LOWER(status) = 'suspended'
                        THEN 1
                        ELSE 0
                    END
                ) AS suspended_staff,

                SUM(
                    CASE
                        WHEN LOWER(status) = 'pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS pending_staff

            FROM users

            WHERE company_id = ?
        ");

        $stmtStaff->execute([$companyId]);

        $staffData = $stmtStaff->fetch(PDO::FETCH_ASSOC);


        $totalStaffCount =
            (int)($staffData['total_staff'] ?? 0);

        $activeStaffCount =
            (int)($staffData['active_staff'] ?? 0);

        $suspendedStaffCount =
            (int)($staffData['suspended_staff'] ?? 0);

        $pendingStaffCount =
            (int)($staffData['pending_staff'] ?? 0);


        // ====================================================
        // LOCATIONS
        // ====================================================

        $stmtLocations = $pdo->prepare("
            SELECT COUNT(*)
            FROM locations
            WHERE company_id = ?
        ");

        $stmtLocations->execute([$companyId]);

        $locationsCount =
            (int)$stmtLocations->fetchColumn();


        // ====================================================
        // PRODUCTS
        // ====================================================

        $stmtProducts = $pdo->prepare("
            SELECT COUNT(*)
            FROM products
            WHERE company_id = ?
        ");

        $stmtProducts->execute([$companyId]);

        $productsCount =
            (int)$stmtProducts->fetchColumn();


        // ====================================================
        // INVENTORY
        // ====================================================

        try {

            $stmtStock = $pdo->prepare("
                SELECT

                    COUNT(DISTINCT product_id)
                    AS stocked_products,

                    COALESCE(
                        SUM(quantity),
                        0
                    )
                    AS stock_quantity

                FROM stocks

                WHERE company_id = ?
            ");

            $stmtStock->execute([$companyId]);

            $stockData =
                $stmtStock->fetch(PDO::FETCH_ASSOC);


            $stockedProducts =
                (int)($stockData['stocked_products'] ?? 0);

            $totalStockQuantity =
                (int)($stockData['stock_quantity'] ?? 0);

        } catch (Throwable $e) {

            $stockedProducts    = 0;
            $totalStockQuantity = 0;
        }


        // ====================================================
        // CUSTOMERS
        // ====================================================

        $stmtCustomers = $pdo->prepare("
            SELECT COUNT(*)
            FROM customers
            WHERE company_id = ?
        ");

        $stmtCustomers->execute([$companyId]);

        $totalCustomers =
            (int)$stmtCustomers->fetchColumn();


        // ====================================================
        // VENDORS
        // ====================================================

        $stmtVendors = $pdo->prepare("
            SELECT COUNT(*)
            FROM vendors
            WHERE company_id = ?
        ");

        $stmtVendors->execute([$companyId]);

        $totalVendors =
            (int)$stmtVendors->fetchColumn();


        // ====================================================
        // SERVICES
        // ====================================================

        $stmtServices = $pdo->prepare("
            SELECT COUNT(*)
            FROM services
            WHERE company_id = ?
        ");

        $stmtServices->execute([$companyId]);

        $totalServices =
            (int)$stmtServices->fetchColumn();


        // ====================================================
        // SALES INTELLIGENCE
        // ====================================================

        $stmtSales = $pdo->prepare("
            SELECT

                COUNT(*) AS total_sales,

                COALESCE(
                    SUM(final_total),
                    0
                ) AS sales_value,

                COALESCE(
                    SUM(amount_paid),
                    0
                ) AS collected,

                COALESCE(
                    SUM(balance),
                    0
                ) AS outstanding

            FROM sales

            WHERE company_id = ?
        ");

        $stmtSales->execute([$companyId]);

        $salesData =
            $stmtSales->fetch(PDO::FETCH_ASSOC);


        $totalSales =
            (int)($salesData['total_sales'] ?? 0);

        $totalSalesValue =
            (float)($salesData['sales_value'] ?? 0);

        $totalCollected =
            (float)($salesData['collected'] ?? 0);

        $totalOutstanding =
            (float)($salesData['outstanding'] ?? 0);


        // ====================================================
        // JOB / SERVICE OPERATIONS
        // ====================================================

        $stmtJobs = $pdo->prepare("
            SELECT

                COUNT(*) AS total_jobs,

                COALESCE(
                    SUM(final_total),
                    0
                ) AS job_value,

                COALESCE(
                    SUM(amount_paid),
                    0
                ) AS collected,

                COALESCE(
                    SUM(balance),
                    0
                ) AS outstanding

            FROM jobs

            WHERE company_id = ?
        ");

        $stmtJobs->execute([$companyId]);

        $jobData =
            $stmtJobs->fetch(PDO::FETCH_ASSOC);


        $totalJobs =
            (int)($jobData['total_jobs'] ?? 0);

        $totalJobValue =
            (float)($jobData['job_value'] ?? 0);

        $totalJobCollected =
            (float)($jobData['collected'] ?? 0);

        $totalJobOutstanding =
            (float)($jobData['outstanding'] ?? 0);


        // ====================================================
        // PRODUCTION INTELLIGENCE
        // ====================================================

        try {

            $stmtProduction = $pdo->prepare("
                SELECT

                    COUNT(*) AS production_runs,

                    COALESCE(
                        SUM(total_cost),
                        0
                    ) AS production_value,

                    COALESCE(
                        SUM(total_profit),
                        0
                    ) AS production_profit

                FROM stock_production

                WHERE company_id = ?
            ");

            $stmtProduction->execute([$companyId]);

            $productionData =
                $stmtProduction->fetch(PDO::FETCH_ASSOC);


            $totalProductionRuns =
                (int)(
                    $productionData['production_runs']
                    ?? 0
                );

            $totalProductionValue =
                (float)(
                    $productionData['production_value']
                    ?? 0
                );

            $totalProductionProfit =
                (float)(
                    $productionData['production_profit']
                    ?? 0
                );

        } catch (Throwable $e) {

            $totalProductionRuns   = 0;
            $totalProductionValue  = 0;
            $totalProductionProfit = 0;
        }


        // ====================================================
        // WORKFORCE RATIO
        // ====================================================

        if ($totalStaffCount > 0) {

            $staffUtilization = round(
                (
                    $activeStaffCount /
                    $totalStaffCount
                ) * 100
            );

        } else {

            $staffUtilization = 0;
        }


        // ====================================================
        // PRODUCT COVERAGE
        // ====================================================

        if ($productsCount > 0) {

            $productCoverage = round(
                (
                    $stockedProducts /
                    $productsCount
                ) * 100
            );

        } else {

            $productCoverage = 0;
        }


        // ====================================================
        // COMPANY FINANCIAL POSITION
        // ====================================================

        $companyRevenue =
            $totalSalesValue +
            $totalJobValue;


        $companyCollected =
            $totalCollected +
            $totalJobCollected;


        $companyReceivables =
            $totalOutstanding +
            $totalJobOutstanding;


        // ====================================================
        // COLLECTION RATE
        // ====================================================

        if ($companyRevenue > 0) {

            $collectionRate = round(
                (
                    $companyCollected /
                    $companyRevenue
                ) * 100
            );

        } else {

            $collectionRate = 0;
        }


        // ====================================================
        // PRODUCTION MARGIN
        // ====================================================

        if ($totalProductionValue > 0) {

            $productionMargin = round(
                (
                    $totalProductionProfit /
                    $totalProductionValue
                ) * 100
            );

        } else {

            $productionMargin = 0;
        }


        // ====================================================
        // COMPANY HEALTH SIGNAL
        // ====================================================

        $companySignal      = 'Healthy';
        $companySignalClass = 'stable';


        // Setup state

        if (
            $totalStaffCount === 0 &&
            $productsCount === 0 &&
            $locationsCount === 0
        ) {

            $companySignal      = 'Company Setup In Progress';
            $companySignalClass = 'setup';
        }


        // Pending personnel

        elseif ($pendingStaffCount > 0) {

            $companySignal      = 'Personnel Attention';
            $companySignalClass = 'attention';
        }


        // Suspended accounts

        elseif ($suspendedStaffCount > 0) {

            $companySignal      = 'Account Review';
            $companySignalClass = 'attention';
        }


        // Receivables

        elseif ($companyReceivables > 0) {

            $companySignal      = '';
            $companySignalClass = 'attention';
        }
    }
}


// ============================================================
// DISPLAY HELPERS
// ============================================================

function intelligenceNumber($number)
{
    return number_format(
        (int)$number
    );
}


function intelligenceMoney($number)
{
    return '₦' . number_format(
        (float)$number,
        2
    );
}


function intelligenceCompactMoney($number)
{
    $number = (float)$number;


    if ($number >= 1000000000) {

        return '₦' .
            number_format(
                $number / 1000000000,
                1
            ) .
            'B';
    }


    if ($number >= 1000000) {

        return '₦' .
            number_format(
                $number / 1000000,
                1
            ) .
            'M';
    }


    if ($number >= 1000) {

        return '₦' .
            number_format(
                $number / 1000,
                1
            ) .
            'K';
    }


    return '₦' .
        number_format(
            $number,
            2
        );
}


function intelligencePercent($number)
{
    return number_format(
        (float)$number,
        0
    ) . '%';
}

?>

<style>

/* ============================================================
   UBAMUS — OVERALL ADMIN INTELLIGENCE
   ============================================================ */

.company-intelligence {

    --ci-text: #111827;
    --ci-muted: #64748b;
    --ci-soft: #94a3b8;

    --ci-border: rgba(226,232,240,.85);

    --ci-blue: #4f46e5;
    --ci-cyan: #0891b2;
    --ci-green: #16a34a;
    --ci-orange: #ea580c;
    --ci-red: #dc2626;

    width: 100%;
    margin: 0;
    padding: 0;
}


/* ============================================================
   HEADER
   ============================================================ */

.ci-intelligence-header {

    position: relative;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    width: 100%;

    margin-bottom: 14px;

    padding: 20px 22px;

    overflow: hidden;

    border: 1px solid rgba(255,255,255,.95);

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.94),
            rgba(248,250,252,.82)
        );

    box-shadow:
        0 18px 45px rgba(15,23,42,.06),
        inset 0 1px 0 rgba(255,255,255,.95);

    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}


.ci-intelligence-header::before {

    content: "";

    position: absolute;

    width: 270px;
    height: 270px;

    right: -125px;
    top: -165px;

    border-radius: 50%;

    background: rgba(79,70,229,.08);

    pointer-events: none;
}


.ci-intelligence-header::after {

    content: "";

    position: absolute;

    width: 130px;
    height: 130px;

    right: 120px;
    bottom: -100px;

    border-radius: 50%;

    background: rgba(56,189,248,.045);

    pointer-events: none;
}


.ci-header-left {

    position: relative;

    z-index: 2;

    min-width: 0;
}


.ci-eyebrow {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 6px;

    color: var(--ci-blue);

    font-size: 10px;

    font-weight: 800;

    letter-spacing: .14em;

    text-transform: uppercase;
}


.ci-eyebrow i {

    font-size: 10px;
}


.ci-title {

    margin: 0;

    color: var(--ci-text);

    font-size:
        clamp(20px, 2.5vw, 28px);

    font-weight: 800;

    line-height: 1.1;

    letter-spacing: -.04em;
}


.ci-description {

    max-width: 780px;

    margin: 7px 0 0;

    color: var(--ci-muted);

    font-size: 12px;

    line-height: 1.55;
}


/* ============================================================
   COMPANY SCOPE BADGE
   ============================================================ */

.ci-scope-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-top: 12px;

    padding: 7px 10px;

    border: 1px solid rgba(79,70,229,.12);

    border-radius: 999px;

    background: rgba(99,102,241,.055);

    color: #4f46e5;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: .04em;
}


.ci-scope-badge i {

    font-size: 9px;
}


/* ============================================================
   SYSTEM SIGNAL
   ============================================================ */

.ci-system-signal {

    position: relative;

    z-index: 3;

    display: inline-flex;

    align-items: center;

    gap: 8px;

    flex-shrink: 0;

    padding: 9px 13px;

    border: 1px solid rgba(22,163,74,.15);

    border-radius: 999px;

    background: rgba(240,253,244,.82);

    color: #15803d;

    font-size: 10px;

    font-weight: 800;

    white-space: nowrap;
}


.ci-system-signal.attention {

    border-color:
        rgba(234,88,12,.16);

    background: #fff7ed;

    color: #c2410c;
}


.ci-system-signal.setup {

    border-color:
        rgba(100,116,139,.16);

    background: #f8fafc;

    color: #475569;
}


.ci-signal-dot {

    width: 7px;
    height: 7px;

    flex-shrink: 0;

    border-radius: 50%;

    background: #22c55e;

    box-shadow:
        0 0 0 4px rgba(34,197,94,.10);
}


.ci-system-signal.attention
.ci-signal-dot {

    background: #f97316;

    box-shadow:
        0 0 0 4px rgba(249,115,22,.10);
}


/* ============================================================
   KPI GRID
   ============================================================ */

.ci-kpi-grid {

    display: grid;

    grid-template-columns:
        repeat(5,minmax(0,1fr));

    gap: 11px;

    width: 100%;
}


/* ============================================================
   KPI CARD
   ============================================================ */

.ci-kpi-card {

    position: relative;

    min-width: 0;

    overflow: hidden;

    padding: 16px;

    border:
        1px solid var(--ci-border);

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.94),
            rgba(248,250,252,.78)
        );

    box-shadow:
        0 10px 30px
        rgba(15,23,42,.045),

        inset 0 1px 0
        rgba(255,255,255,.96);

    backdrop-filter: blur(16px);

    -webkit-backdrop-filter: blur(16px);

    transition:
        transform .22s ease,
        box-shadow .22s ease,
        border-color .22s ease;
}


.ci-kpi-card:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(79,70,229,.18);

    box-shadow:
        0 18px 38px
        rgba(15,23,42,.075);
}


.ci-kpi-card::after {

    content: "";

    position: absolute;

    width: 105px;
    height: 105px;

    right: -53px;
    bottom: -58px;

    border-radius: 50%;

    background:
        rgba(79,70,229,.045);

    pointer-events: none;
}


/* ============================================================
   CARD TOP
   ============================================================ */

.ci-card-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 8px;
}


.ci-card-icon {

    width: 40px;
    height: 40px;

    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid rgba(79,70,229,.11);

    border-radius: 12px;

    background:
        rgba(99,102,241,.08);

    color: var(--ci-blue);

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.8);
}


.ci-card-icon i {

    font-size: 15px;
}


/* ============================================================
   MINI SIGNAL
   ============================================================ */

.ci-mini-signal {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 7px;

    border-radius: 999px;

    background: #f8fafc;

    color: var(--ci-soft);

    font-size: 8px;

    font-weight: 800;

    white-space: nowrap;
}


.ci-mini-signal.positive {

    background: #f0fdf4;

    color: #15803d;
}


.ci-mini-signal.warning {

    background: #fff7ed;

    color: #c2410c;
}


.ci-mini-signal.danger {

    background: #fef2f2;

    color: #b91c1c;
}


/* ============================================================
   CARD CONTENT
   ============================================================ */

.ci-card-content {

    position: relative;

    z-index: 2;

    margin-top: 15px;
}


.ci-card-label {

    display: block;

    margin-bottom: 5px;

    color: var(--ci-muted);

    font-size: 10px;

    font-weight: 650;

    line-height: 1.35;
}


.ci-card-value {

    display: block;

    margin: 0;

    color: var(--ci-text);

    font-size:
        clamp(21px,2.15vw,28px);

    font-weight: 800;

    letter-spacing: -.045em;

    line-height: 1;

    word-break: break-word;
}


.ci-card-description {

    display: block;

    margin-top: 7px;

    color: var(--ci-soft);

    font-size: 8.5px;

    line-height: 1.45;
}


/* ============================================================
   PROGRESS
   ============================================================ */

.ci-progress {

    width: 100%;

    height: 4px;

    margin-top: 13px;

    overflow: hidden;

    border-radius: 999px;

    background: #eef2f7;
}


.ci-progress span {

    display: block;

    height: 100%;

    min-width: 2px;

    border-radius: inherit;

    background:
        linear-gradient(
            90deg,
            #6366f1,
            #38bdf8
        );
}


/* ============================================================
   EXECUTIVE INTELLIGENCE STRIP
   ============================================================ */

.ci-intelligence-strip {

    display: grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap: 11px;

    margin-top: 11px;
}


/* ============================================================
   INSIGHT CARD
   ============================================================ */

.ci-insight {

    position: relative;

    min-width: 0;

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 14px 15px;

    border:
        1px solid var(--ci-border);

    border-radius: 16px;

    background:
        rgba(255,255,255,.78);

    box-shadow:
        0 8px 25px
        rgba(15,23,42,.035);

    backdrop-filter: blur(14px);

    -webkit-backdrop-filter: blur(14px);
}


.ci-insight-icon {

    width: 37px;
    height: 37px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    background: #f8fafc;

    color: #64748b;
}


.ci-insight-icon i {

    font-size: 14px;
}


.ci-insight-content {

    min-width: 0;
}


.ci-insight-label {

    display: block;

    color: var(--ci-soft);

    font-size: 8px;

    font-weight: 800;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.ci-insight-value {

    display: block;

    margin-top: 3px;

    color: var(--ci-text);

    font-size: 12px;

    font-weight: 750;

    line-height: 1.3;
}


.ci-insight-description {

    display: block;

    margin-top: 2px;

    color: var(--ci-muted);

    font-size: 8.5px;

    line-height: 1.4;
}


/* ============================================================
   DARK MODE
   ============================================================ */

.dark .company-intelligence {

    --ci-text: #f8fafc;
    --ci-muted: #94a3b8;
    --ci-soft: #64748b;

    --ci-border:
        rgba(51,65,85,.85);
}


.dark .ci-intelligence-header,
.dark .ci-kpi-card {

    background:
        linear-gradient(
            145deg,
            rgba(17,24,39,.92),
            rgba(15,23,42,.84)
        );

    border-color: #1f2937;
}


.dark .ci-insight {

    background:
        rgba(17,24,39,.80);

    border-color: #1f2937;
}


.dark .ci-scope-badge {

    background:
        rgba(99,102,241,.10);

    color: #818cf8;
}


.dark .ci-mini-signal {

    background: #111827;

    color: #64748b;
}


.dark .ci-mini-signal.positive {

    background:
        rgba(22,163,74,.10);

    color: #4ade80;
}


.dark .ci-mini-signal.warning {

    background:
        rgba(234,88,12,.10);

    color: #fb923c;
}


.dark .ci-mini-signal.danger {

    background:
        rgba(220,38,38,.10);

    color: #f87171;
}


.dark .ci-progress {

    background: #1e293b;
}


.dark .ci-insight-icon {

    background: #111827;
}


/* ============================================================
   TABLET
   ============================================================ */

@media (max-width:1200px) {

    .ci-kpi-grid {

        grid-template-columns:
            repeat(3,minmax(0,1fr));
    }


    .ci-intelligence-strip {

        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }
}


/* ============================================================
   SMALL DESKTOP / TABLET
   ============================================================ */

@media (max-width:850px) {

    .ci-intelligence-header {

        align-items: flex-start;

        flex-direction: column;
    }


    .ci-kpi-grid {

        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }


    .ci-system-signal {

        align-self: flex-start;
    }
}


/* ============================================================
   MOBILE
   ============================================================ */

@media (max-width:600px) {

    .ci-intelligence-header {

        padding: 17px;

        border-radius: 18px;
    }


    .ci-title {

        font-size: 21px;
    }


    .ci-description {

        font-size: 11px;
    }


    .ci-kpi-grid {

        grid-template-columns:
            repeat(2,minmax(0,1fr));

        gap: 8px;
    }


    .ci-kpi-card {

        padding: 13px;

        border-radius: 15px;
    }


    .ci-card-icon {

        width: 35px;
        height: 35px;

        border-radius: 10px;
    }


    .ci-card-icon i {

        font-size: 13px;
    }


    .ci-mini-signal {

        padding: 4px 6px;

        font-size: 7px;
    }


    .ci-card-content {

        margin-top: 13px;
    }


    .ci-card-value {

        font-size: 22px;
    }


    .ci-card-description {

        font-size: 8px;
    }


    .ci-intelligence-strip {

        grid-template-columns: 1fr;

        gap: 8px;
    }


    .ci-insight {

        padding: 13px;
    }
}


/* ============================================================
   SMALL PHONES
   ============================================================ */

@media (max-width:390px) {

    .ci-kpi-grid {

        grid-template-columns: 1fr;
    }


    .ci-kpi-card {

        display: grid;

        grid-template-columns:
            auto 1fr;

        column-gap: 12px;

        align-items: center;
    }


    .ci-card-top {

        display: contents;
    }


    .ci-card-icon {

        grid-row: span 2;
    }


    .ci-card-content {

        margin-top: 0;
    }


    .ci-mini-signal {

        position: absolute;

        top: 13px;

        right: 13px;
    }


    .ci-progress {

        grid-column: 1 / -1;

        margin-top: 12px;
    }
}

</style>


<!-- ============================================================
     COMPANY INTELLIGENCE DASHBOARD
     ============================================================ -->

<section class="company-intelligence">


    <!-- ========================================================
         EXECUTIVE HEADER
         ======================================================== -->

    <div class="ci-intelligence-header">

        <div class="ci-header-left">


            <div class="ci-eyebrow">

                <i class="fa-solid fa-brain"></i>

                Executive Company Intelligence

            </div>


            <h3 class="ci-title">

                Company Command Centre

            </h3>


            <p class="ci-description">

                Company-wide intelligence across workforce,
                locations, customers, products, inventory,
                sales, service operations and production.

            </p>


            <div class="ci-scope-badge">

                <i class="fa-solid fa-building"></i>

                Entire Company · All Locations

            </div>


        </div>


        <div class="ci-system-signal <?= htmlspecialchars($companySignalClass); ?>">

            <span class="ci-signal-dot"></span>

            <?= htmlspecialchars($companySignal); ?>

        </div>

    </div>



    <!-- ========================================================
         PRIMARY KPI GRID
         ======================================================== -->

    <div class="ci-kpi-grid">


        <!-- ====================================================
             ACTIVE WORKFORCE
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div class="ci-card-icon">

                    <i class="fa-solid fa-user-check"></i>

                </div>


                <span class="ci-mini-signal positive">

                    <i class="fa-solid fa-circle"></i>

                    Active

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Active Workforce

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceNumber(
                        $activeStaffCount
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Active personnel company-wide.

                </span>


                <div class="ci-progress">

                    <span
                        style="
                            width:
                            <?= min(
                                100,
                                $staffUtilization
                            ); ?>%;
                        "
                    ></span>

                </div>

            </div>

        </article>



        <!-- ====================================================
             CUSTOMERS
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div class="ci-card-icon">

                    <i class="fa-solid fa-users"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Customer Base

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Customers

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceNumber(
                        $totalCustomers
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Registered customers across all locations.

                </span>

            </div>

        </article>



        <!-- ====================================================
             PRODUCT REGISTRY
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div class="ci-card-icon">

                    <i class="fa-solid fa-boxes-stacked"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Registry

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Product Registry

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceNumber(
                        $productsCount
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Total products registered company-wide.

                </span>


                <div class="ci-progress">

                    <span
                        style="
                            width:
                            <?= min(
                                100,
                                $productCoverage
                            ); ?>%;
                        "
                    ></span>

                </div>

            </div>

        </article>



        <!-- ====================================================
             LOCATIONS
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div
                    class="ci-card-icon"
                    style="
                        color:#0891b2;
                        background:rgba(8,145,178,.07);
                        border-color:rgba(8,145,178,.10);
                    "
                >

                    <i class="fa-solid fa-location-dot"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Network

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Operational Locations

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceNumber(
                        $locationsCount
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Registered company operating locations.

                </span>

            </div>

        </article>



        <!-- ====================================================
             SALES VALUE
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div
                    class="ci-card-icon"
                    style="
                        color:#16a34a;
                        background:rgba(22,163,74,.07);
                        border-color:rgba(22,163,74,.10);
                    "
                >

                    <i class="fa-solid fa-chart-column"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Revenue

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Sales Value

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceCompactMoney(
                        $totalSalesValue
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Aggregate sales value company-wide.

                </span>

            </div>

        </article>



        <!-- ====================================================
             SERVICE JOB VALUE
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div
                    class="ci-card-icon"
                    style="
                        color:#7c3aed;
                        background:rgba(124,58,237,.07);
                        border-color:rgba(124,58,237,.10);
                    "
                >

                    <i class="fa-solid fa-briefcase"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Operations

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Service Job Value

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceCompactMoney(
                        $totalJobValue
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Aggregate service value company-wide.

                </span>

            </div>

        </article>



        <!-- ====================================================
             INVENTORY
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div
                    class="ci-card-icon"
                    style="
                        color:#0891b2;
                        background:rgba(8,145,178,.07);
                        border-color:rgba(8,145,178,.10);
                    "
                >

                    <i class="fa-solid fa-layer-group"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Inventory

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Inventory Units

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceNumber(
                        $totalStockQuantity
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Aggregate stock units across all locations.

                </span>

            </div>

        </article>



        <!-- ====================================================
             PRODUCTION
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div
                    class="ci-card-icon"
                    style="
                        color:#ea580c;
                        background:rgba(234,88,12,.07);
                        border-color:rgba(234,88,12,.10);
                    "
                >

                    <i class="fa-solid fa-industry"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Production

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Production Runs

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceNumber(
                        $totalProductionRuns
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Production records across the company.

                </span>

            </div>

        </article>



        <!-- ====================================================
             RECEIVABLES
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div
                    class="ci-card-icon"
                    style="
                        color:#dc2626;
                        background:rgba(220,38,38,.07);
                        border-color:rgba(220,38,38,.10);
                    "
                >

                    <i class="fa-solid fa-money-bill-transfer"></i>

                </div>


                <span
                    class="
                        ci-mini-signal
                        <?= $companyReceivables > 0
                            ? 'warning'
                            : 'positive'; ?>
                    "
                >

                    <?= $companyReceivables > 0
                        ? 'Attention'
                        : 'Clear'; ?>

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Company Receivables

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceCompactMoney(
                        $companyReceivables
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Outstanding sales and service balances.

                </span>

            </div>

        </article>



        <!-- ====================================================
             PRODUCTION PROFIT
             ==================================================== -->

        <article class="ci-kpi-card">

            <div class="ci-card-top">

                <div
                    class="ci-card-icon"
                    style="
                        color:#16a34a;
                        background:rgba(22,163,74,.07);
                        border-color:rgba(22,163,74,.10);
                    "
                >

                    <i class="fa-solid fa-arrow-trend-up"></i>

                </div>


                <span class="ci-mini-signal positive">

                    Profit

                </span>

            </div>


            <div class="ci-card-content">

                <span class="ci-card-label">

                    Production Profit

                </span>


                <strong class="ci-card-value">

                    <?= intelligenceCompactMoney(
                        $totalProductionProfit
                    ); ?>

                </strong>


                <span class="ci-card-description">

                    Recorded production profit company-wide.

                </span>

            </div>

        </article>

    </div>



    <!-- ========================================================
         EXECUTIVE INTELLIGENCE
         ======================================================== -->

    <div class="ci-intelligence-strip">


        <!-- WORKFORCE -->

        <div class="ci-insight">

            <div class="ci-insight-icon">

                <i class="fa-solid fa-users-gear"></i>

            </div>


            <div class="ci-insight-content">

                <span class="ci-insight-label">

                    Workforce Health

                </span>


                <strong class="ci-insight-value">

                    <?= intelligencePercent(
                        $staffUtilization
                    ); ?>

                    active

                </strong>


                <span class="ci-insight-description">

                    <?= intelligenceNumber(
                        $activeStaffCount
                    ); ?>

                    active out of

                    <?= intelligenceNumber(
                        $totalStaffCount
                    ); ?>

                    personnel.

                </span>

            </div>

        </div>



        <!-- COLLECTION -->

        <div class="ci-insight">

            <div
                class="ci-insight-icon"
                style="
                    color:#16a34a;
                    background:rgba(22,163,74,.07);
                "
            >

                <i class="fa-solid fa-hand-holding-dollar"></i>

            </div>


            <div class="ci-insight-content">

                <span class="ci-insight-label">

                    Collection Efficiency

                </span>


                <strong class="ci-insight-value">

                    <?= intelligencePercent(
                        $collectionRate
                    ); ?>

                </strong>


                <span class="ci-insight-description">

                    Company-wide collection against sales
                    and service value.

                </span>

            </div>

        </div>



        <!-- INVENTORY -->

        <div class="ci-insight">

            <div
                class="ci-insight-icon"
                style="
                    color:#0891b2;
                    background:rgba(8,145,178,.07);
                "
            >

                <i class="fa-solid fa-box-open"></i>

            </div>


            <div class="ci-insight-content">

                <span class="ci-insight-label">

                    Inventory Coverage

                </span>


                <strong class="ci-insight-value">

                    <?= intelligencePercent(
                        $productCoverage
                    ); ?>

                </strong>


                <span class="ci-insight-description">

                    <?= intelligenceNumber(
                        $stockedProducts
                    ); ?>

                    of

                    <?= intelligenceNumber(
                        $productsCount
                    ); ?>

                    products have stock records.

                </span>

            </div>

        </div>



        <!-- BUSINESS ECOSYSTEM -->

        <div class="ci-insight">

            <div
                class="ci-insight-icon"
                style="
                    color:#7c3aed;
                    background:rgba(124,58,237,.07);
                "
            >

                <i class="fa-solid fa-network-wired"></i>

            </div>


            <div class="ci-insight-content">

                <span class="ci-insight-label">

                    Business Ecosystem

                </span>


                <strong class="ci-insight-value">

                    <?= intelligenceNumber(
                        $totalCustomers
                    ); ?>

                    customers ·

                    <?= intelligenceNumber(
                        $totalVendors
                    ); ?>

                    vendors

                </strong>


                <span class="ci-insight-description">

                    Registered external business relationships.

                </span>

            </div>

        </div>


    </div>


</section>