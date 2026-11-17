<?php

// ============================================================
// UBAMUS — BUSINESS DATA FEED / OPERATIONS INTELLIGENCE
// ============================================================
//
// LOCATION GOVERNANCE
// ------------------------------------------------------------
// Financial + operational activity is scoped to:
//      company_id + current location_id
//
// Company-wide reference data:
//      products
//      vendors
//
// Location-specific:
//      jobs
//      sales
//      stocks
//      staffs
//
// ============================================================


/*
|--------------------------------------------------------------------------
| SESSION CONTEXT
|--------------------------------------------------------------------------
*/

$userId     = $_SESSION['user_id'] ?? null;
$locationId = $_SESSION['location_id'] ?? null;
$companyId  = $_SESSION['company_id'] ?? null;


/*
|--------------------------------------------------------------------------
| FALLBACK COMPANY RESOLUTION
|--------------------------------------------------------------------------
*/

if ($userId) {

    $stmt = $pdo->prepare("
        SELECT company_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $resolvedCompanyId = $stmt->fetchColumn();

    if ($resolvedCompanyId) {
        $companyId = $resolvedCompanyId;
    }
}


/*
|--------------------------------------------------------------------------
| DEFAULT INTELLIGENCE VALUES
|--------------------------------------------------------------------------
*/

$totalServicesRevenue = 0;
$totalSalesRevenue    = 0;
$totalRevenue         = 0;

$totalServicesCount  = 0;
$totalSalesCount     = 0;
$totalTransactions   = 0;

$totalAmountPaid     = 0;
$totalOutstanding    = 0;

$totalProducts       = 0;
$totalStocks         = 0;
$totalStockQuantity  = 0;

$totalStaffs         = 0;
$totalVendors        = 0;

$averageTransaction   = 0;
$collectionRate       = 0;

$servicePercentage    = 0;
$salesPercentage      = 0;

$stockPerProduct      = 0;
$revenuePerStaff      = 0;
$transactionsPerStaff = 0;

$locationName = 'Operating Location';
$locationType = '';

$businessScore  = 0;
$businessStatus = 'Awaiting operational data';

$prioritySignal = 'No immediate signal';
$revenueStatus  = 'No revenue recorded';
$inventoryStatus = 'No inventory data';
$workforceStatus = 'No staff registered';
$cashStatus      = 'No collection data';
$activityStatus  = 'No transaction activity';

$largestRevenueSource = 'None';
$primaryAttention     = 'Monitoring';

$healthSignals = [];


/*
|--------------------------------------------------------------------------
| FETCH LOCATION
|--------------------------------------------------------------------------
*/

if ($companyId && $locationId) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            type
        FROM locations
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $locationId,
        $companyId
    ]);

    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($location) {

        $locationName = $location['name'] ?? 'Operating Location';
        $locationType = $location['type'] ?? '';
    }
}


/*
|--------------------------------------------------------------------------
| CORE FINANCIAL + OPERATIONAL INTELLIGENCE
|--------------------------------------------------------------------------
*/

if ($companyId && $locationId) {

    $stmt = $pdo->prepare("

        SELECT

            /* ==================================================
               SERVICE REVENUE
               ================================================== */

            (
                SELECT COALESCE(SUM(final_total), 0)
                FROM jobs
                WHERE company_id = ?
                  AND location_id = ?
            ) AS services_revenue,

            (
                SELECT COUNT(*)
                FROM jobs
                WHERE company_id = ?
                  AND location_id = ?
            ) AS services_count,

            (
                SELECT COALESCE(SUM(amount_paid), 0)
                FROM jobs
                WHERE company_id = ?
                  AND location_id = ?
            ) AS services_paid,

            (
                SELECT COALESCE(SUM(balance), 0)
                FROM jobs
                WHERE company_id = ?
                  AND location_id = ?
            ) AS services_balance,


            /* ==================================================
               SALES REVENUE
               ================================================== */

            (
                SELECT COALESCE(SUM(final_total), 0)
                FROM sales
                WHERE company_id = ?
                  AND location_id = ?
            ) AS sales_revenue,

            (
                SELECT COUNT(*)
                FROM sales
                WHERE company_id = ?
                  AND location_id = ?
            ) AS sales_count,

            (
                SELECT COALESCE(SUM(amount_paid), 0)
                FROM sales
                WHERE company_id = ?
                  AND location_id = ?
            ) AS sales_paid,

            (
                SELECT COALESCE(SUM(balance), 0)
                FROM sales
                WHERE company_id = ?
                  AND location_id = ?
            ) AS sales_balance,


            /* ==================================================
               COMPANY-WIDE PRODUCT CATALOGUE
               ================================================== */

            (
                SELECT COUNT(*)
                FROM products
                WHERE company_id = ?
            ) AS products_count,


            /* ==================================================
               LOCATION INVENTORY
               ================================================== */

            (
                SELECT COUNT(*)
                FROM stocks
                WHERE company_id = ?
                  AND location_id = ?
            ) AS stocks_count,

            (
                SELECT COALESCE(SUM(quantity), 0)
                FROM stocks
                WHERE company_id = ?
                  AND location_id = ?
            ) AS stock_quantity,


            /* ==================================================
               LOCATION STAFF
               ================================================== */

            (
                SELECT COUNT(*)
                FROM staffs
                WHERE company_id = ?
                  AND location_id = ?
            ) AS staffs_count,


            /* ==================================================
               COMPANY-WIDE VENDORS
               ================================================== */

            (
                SELECT COUNT(*)
                FROM vendors
                WHERE company_id = ?
            ) AS vendors_count

    ");

    $stmt->execute([

        // SERVICES
        $companyId,
        $locationId,

        $companyId,
        $locationId,

        $companyId,
        $locationId,

        $companyId,
        $locationId,

        // SALES
        $companyId,
        $locationId,

        $companyId,
        $locationId,

        $companyId,
        $locationId,

        $companyId,
        $locationId,

        // PRODUCTS
        $companyId,

        // STOCK RECORDS
        $companyId,
        $locationId,

        $companyId,
        $locationId,

        // STAFF
        $companyId,
        $locationId,

        // VENDORS
        $companyId
    ]);

    $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kpi) {

        $totalServicesRevenue = (float)($kpi['services_revenue'] ?? 0);
        $totalServicesCount   = (int)($kpi['services_count'] ?? 0);

        $servicesPaid         = (float)($kpi['services_paid'] ?? 0);
        $servicesBalance      = (float)($kpi['services_balance'] ?? 0);

        $totalSalesRevenue    = (float)($kpi['sales_revenue'] ?? 0);
        $totalSalesCount      = (int)($kpi['sales_count'] ?? 0);

        $salesPaid            = (float)($kpi['sales_paid'] ?? 0);
        $salesBalance         = (float)($kpi['sales_balance'] ?? 0);

        $totalProducts        = (int)($kpi['products_count'] ?? 0);

        $totalStocks          = (int)($kpi['stocks_count'] ?? 0);
        $totalStockQuantity   = (float)($kpi['stock_quantity'] ?? 0);

        $totalStaffs          = (int)($kpi['staffs_count'] ?? 0);
        $totalVendors         = (int)($kpi['vendors_count'] ?? 0);


        /*
        |--------------------------------------------------------------------------
        | FINANCIAL AGGREGATES
        |--------------------------------------------------------------------------
        */

        $totalRevenue =
            $totalServicesRevenue +
            $totalSalesRevenue;

        $totalAmountPaid =
            $servicesPaid +
            $salesPaid;

        $totalOutstanding =
            $servicesBalance +
            $salesBalance;


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION VOLUME
        |--------------------------------------------------------------------------
        */

        $totalTransactions =
            $totalServicesCount +
            $totalSalesCount;


        /*
        |--------------------------------------------------------------------------
        | AVERAGE TRANSACTION
        |--------------------------------------------------------------------------
        */

        if ($totalTransactions > 0) {

            $averageTransaction =
                $totalRevenue / $totalTransactions;
        }


        /*
        |--------------------------------------------------------------------------
        | REVENUE MIX
        |--------------------------------------------------------------------------
        */

        if ($totalRevenue > 0) {

            $servicePercentage =
                ($totalServicesRevenue / $totalRevenue) * 100;

            $salesPercentage =
                ($totalSalesRevenue / $totalRevenue) * 100;
        }


        /*
        |--------------------------------------------------------------------------
        | COLLECTION RATE
        |--------------------------------------------------------------------------
        */

        if ($totalRevenue > 0) {

            $collectionRate =
                ($totalAmountPaid / $totalRevenue) * 100;

            $collectionRate =
                max(0, min(100, $collectionRate));
        }


        /*
        |--------------------------------------------------------------------------
        | INVENTORY DENSITY
        |--------------------------------------------------------------------------
        */

        if ($totalProducts > 0) {

            $stockPerProduct =
                $totalStocks / $totalProducts;
        }


        /*
        |--------------------------------------------------------------------------
        | WORKFORCE PRODUCTIVITY SIGNAL
        |--------------------------------------------------------------------------
        */

        if ($totalStaffs > 0) {

            $revenuePerStaff =
                $totalRevenue / $totalStaffs;

            $transactionsPerStaff =
                $totalTransactions / $totalStaffs;
        }
    }
}


/*
|--------------------------------------------------------------------------
| REVENUE ENGINE STATUS
|--------------------------------------------------------------------------
*/

if ($totalRevenue <= 0) {

    $revenueStatus = 'No revenue recorded';

} elseif ($servicePercentage >= 65) {

    $revenueStatus = 'Service-led';
    $largestRevenueSource = 'Services';

} elseif ($salesPercentage >= 65) {

    $revenueStatus = 'Sales-led';
    $largestRevenueSource = 'Sales';

} else {

    $revenueStatus = 'Balanced';
    $largestRevenueSource = 'Mixed';
}


/*
|--------------------------------------------------------------------------
| INVENTORY STATUS
|--------------------------------------------------------------------------
*/

if ($totalProducts <= 0) {

    $inventoryStatus = 'No catalogue';

} elseif ($totalStockQuantity <= 0) {

    $inventoryStatus = 'Stock attention';

} elseif ($stockPerProduct >= 5) {

    $inventoryStatus = 'Stock-rich';

} elseif ($stockPerProduct >= 1) {

    $inventoryStatus = 'Operational';

} else {

    $inventoryStatus = 'Lean inventory';
}


/*
|--------------------------------------------------------------------------
| WORKFORCE STATUS
|--------------------------------------------------------------------------
*/

if ($totalStaffs <= 0) {

    $workforceStatus = 'No staff registered';

} elseif ($totalStaffs >= 10) {

    $workforceStatus = 'Large workforce';

} elseif ($totalStaffs >= 5) {

    $workforceStatus = 'Active workforce';

} else {

    $workforceStatus = 'Lean workforce';
}


/*
|--------------------------------------------------------------------------
| CASH / COLLECTION STATUS
|--------------------------------------------------------------------------
*/

if ($totalRevenue <= 0) {

    $cashStatus = 'Awaiting transactions';

} elseif ($collectionRate >= 90) {

    $cashStatus = 'Excellent collection';

} elseif ($collectionRate >= 70) {

    $cashStatus = 'Healthy collection';

} elseif ($collectionRate >= 50) {

    $cashStatus = 'Collection watch';

} else {

    $cashStatus = 'Cash exposure';
}


/*
|--------------------------------------------------------------------------
| ACTIVITY STATUS
|--------------------------------------------------------------------------
*/

if ($totalTransactions <= 0) {

    $activityStatus = 'No transaction activity';

} elseif ($totalTransactions >= 100) {

    $activityStatus = 'High activity';

} elseif ($totalTransactions >= 30) {

    $activityStatus = 'Active operation';

} else {

    $activityStatus = 'Moderate activity';
}


/*
|--------------------------------------------------------------------------
| BUSINESS INTELLIGENCE SCORE
|--------------------------------------------------------------------------
*/

$score = 0;


/*
|--------------------------------------------------------------------------
| REVENUE ACTIVITY
|--------------------------------------------------------------------------
*/

if ($totalRevenue > 0) {
    $score += 25;
}

if ($totalTransactions > 0) {
    $score += 10;
}


/*
|--------------------------------------------------------------------------
| COLLECTION PERFORMANCE
|--------------------------------------------------------------------------
*/

if ($collectionRate >= 90) {

    $score += 25;

} elseif ($collectionRate >= 70) {

    $score += 18;

} elseif ($collectionRate >= 50) {

    $score += 10;
}


/*
|--------------------------------------------------------------------------
| INVENTORY READINESS
|--------------------------------------------------------------------------
*/

if ($totalProducts > 0 && $totalStockQuantity > 0) {
    $score += 15;
}

if ($stockPerProduct >= 1) {
    $score += 5;
}


/*
|--------------------------------------------------------------------------
| WORKFORCE
|--------------------------------------------------------------------------
*/

if ($totalStaffs > 0) {
    $score += 10;
}


/*
|--------------------------------------------------------------------------
| OPERATIONAL BALANCE
|--------------------------------------------------------------------------
*/

if ($totalRevenue > 0 && $totalTransactions > 0) {
    $score += 10;
}

$businessScore = min(100, $score);


/*
|--------------------------------------------------------------------------
| BUSINESS STATUS
|--------------------------------------------------------------------------
*/

if ($businessScore >= 85) {

    $businessStatus = 'Strong operating signal';

} elseif ($businessScore >= 70) {

    $businessStatus = 'Healthy operating signal';

} elseif ($businessScore >= 50) {

    $businessStatus = 'Stable with attention areas';

} elseif ($businessScore > 0) {

    $businessStatus = 'Needs operational attention';

} else {

    $businessStatus = 'Awaiting operational data';
}


/*
|--------------------------------------------------------------------------
| PRIORITY SIGNAL ENGINE
|--------------------------------------------------------------------------
*/

if ($totalRevenue <= 0) {

    $prioritySignal =
        'Activate revenue-generating operations';

    $primaryAttention =
        'Revenue';

} elseif ($totalOutstanding > 0 && $collectionRate < 60) {

    $prioritySignal =
        'Outstanding receivables require attention';

    $primaryAttention =
        'Collections';

} elseif ($totalProducts > 0 && $totalStockQuantity <= 0) {

    $prioritySignal =
        'Catalogue exists but inventory is inactive';

    $primaryAttention =
        'Inventory';

} elseif ($totalStaffs <= 0 && $totalTransactions > 0) {

    $prioritySignal =
        'Transactions exist without location staff data';

    $primaryAttention =
        'Workforce';

} elseif ($totalTransactions <= 0) {

    $prioritySignal =
        'Operational activity has not started';

    $primaryAttention =
        'Activity';

} else {

    $prioritySignal =
        'Core operating indicators are active';

    $primaryAttention =
        'Monitor';
}


/*
|--------------------------------------------------------------------------
| AUTOMATED INTELLIGENCE SIGNALS
|--------------------------------------------------------------------------
*/

$healthSignals = [];


/* Revenue signal */

if ($totalRevenue > 0) {

    $healthSignals[] = [
        'type'  => 'positive',
        'icon'  => 'fa-chart-line',
        'title' => 'Revenue engine active',
        'text'  => intelligenceMoney($totalRevenue) .
                   ' recorded across service and sales operations.'
    ];

} else {

    $healthSignals[] = [
        'type'  => 'warning',
        'icon'  => 'fa-chart-line',
        'title' => 'Revenue engine waiting',
        'text'  => 'No service or sales revenue has been recorded for this operating location.'
    ];
}


/* Collection signal */

if ($totalRevenue > 0) {

    if ($collectionRate >= 90) {

        $healthSignals[] = [
            'type'  => 'positive',
            'icon'  => 'fa-circle-check',
            'title' => 'Strong cash realization',
            'text'  => number_format($collectionRate, 0) .
                       '% of recorded revenue has been collected.'
        ];

    } elseif ($collectionRate >= 60) {

        $healthSignals[] = [
            'type'  => 'neutral',
            'icon'  => 'fa-clock',
            'title' => 'Collection monitoring',
            'text'  => number_format($collectionRate, 0) .
                       '% collection rate with ' .
                       intelligenceMoney($totalOutstanding) .
                       ' currently outstanding.'
        ];

    } else {

        $healthSignals[] = [
            'type'  => 'warning',
            'icon'  => 'fa-triangle-exclamation',
            'title' => 'Cash exposure detected',
            'text'  => intelligenceMoney($totalOutstanding) .
                       ' remains outstanding against recorded revenue.'
        ];
    }
}


/* Revenue composition */

if ($totalRevenue > 0) {

    if ($servicePercentage >= 65) {

        $healthSignals[] = [
            'type'  => 'neutral',
            'icon'  => 'fa-briefcase',
            'title' => 'Services dominate',
            'text'  => 'Service operations contribute ' .
                       number_format($servicePercentage, 0) .
                       '% of operating revenue.'
        ];

    } elseif ($salesPercentage >= 65) {

        $healthSignals[] = [
            'type'  => 'neutral',
            'icon'  => 'fa-cash-register',
            'title' => 'Sales dominate',
            'text'  => 'Product sales contribute ' .
                       number_format($salesPercentage, 0) .
                       '% of operating revenue.'
        ];

    } else {

        $healthSignals[] = [
            'type'  => 'positive',
            'icon'  => 'fa-scale-balanced',
            'title' => 'Dual revenue engine',
            'text'  => 'Services and sales are contributing meaningfully to the location.'
        ];
    }
}


/* Inventory signal */

if ($totalProducts > 0 && $totalStockQuantity <= 0) {

    $healthSignals[] = [
        'type'  => 'warning',
        'icon'  => 'fa-box-open',
        'title' => 'Inventory attention',
        'text'  => 'Products are registered but no active stock quantity is recorded at this location.'
    ];

} elseif ($totalStockQuantity > 0) {

    $healthSignals[] = [
        'type'  => 'positive',
        'icon'  => 'fa-boxes-stacked',
        'title' => 'Inventory active',
        'text'  => intelligenceNumber($totalStockQuantity) .
                   ' units are represented across ' .
                   intelligenceNumber($totalStocks) .
                   ' location stock records.'
    ];
}


/* Workforce signal */

if ($totalStaffs > 0 && $totalRevenue > 0) {

    $healthSignals[] = [
        'type'  => 'positive',
        'icon'  => 'fa-users',
        'title' => 'Workforce connected',
        'text'  => intelligenceNumber($totalStaffs) .
                   ' staff support operations at this location.'
    ];
}


/*
|--------------------------------------------------------------------------
| SAFE DISPLAY HELPERS
|--------------------------------------------------------------------------
*/

function intelligenceNumber($value)
{
    return number_format((float)$value);
}


function intelligenceMoney($value)
{
    return '₦' . number_format((float)$value, 2);
}


function intelligencePercent($value)
{
    return number_format((float)$value, 0) . '%';
}


function intelligenceClass($type)
{
    switch ($type) {

        case 'positive':
            return 'feed-positive';

        case 'warning':
            return 'feed-warning';

        default:
            return 'feed-neutral';
    }
}

?>


<style>

/* ============================================================
   UBAMUS BUSINESS INTELLIGENCE FEED
   LARGER READABLE TYPOGRAPHY
   ============================================================ */

.business-intelligence-feed {

    width: 100%;
    min-width: 0;

    position: relative;

    padding: 26px;

    border-radius: 28px;

    border: 1px solid rgba(255,255,255,.82);

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.91),
            rgba(248,250,252,.72)
        );

    box-shadow:
        0 28px 80px rgba(15,23,42,.09),
        inset 0 1px 0 rgba(255,255,255,.95);

    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);

    overflow: hidden;
}


/* ============================================================
   AMBIENT LIGHT
   ============================================================ */

.business-intelligence-feed::before {

    content: "";

    position: absolute;

    width: 360px;
    height: 360px;

    top: -220px;
    right: -120px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(99,102,241,.15),
            transparent 68%
        );

    pointer-events: none;
}


.business-intelligence-feed::after {

    content: "";

    position: absolute;

    width: 300px;
    height: 300px;

    bottom: -220px;
    left: -130px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(14,165,233,.11),
            transparent 68%
        );

    pointer-events: none;
}


/* ============================================================
   HEADER
   ============================================================ */

.bi-feed-header {

    position: relative;
    z-index: 2;

    display: flex;

    align-items: flex-start;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 22px;
}


.bi-feed-brand {

    display: flex;

    align-items: center;

    gap: 15px;

    min-width: 0;
}


.bi-feed-icon {

    width: 54px;
    height: 54px;

    flex: 0 0 54px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 16px;

    color: #6366f1;

    background:
        linear-gradient(
            135deg,
            rgba(99,102,241,.14),
            rgba(14,165,233,.08)
        );

    border: 1px solid rgba(99,102,241,.13);

    box-shadow:
        0 12px 30px rgba(99,102,241,.08);

    font-size: 19px;
}


.bi-feed-eyebrow {

    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 5px;

    color: #6366f1;

    font-size: 11px;

    font-weight: 850;

    letter-spacing: .14em;

    text-transform: uppercase;
}


.bi-feed-title {

    margin: 0;

    color: #111827;

    font-size: 25px;

    font-weight: 850;

    letter-spacing: -.035em;

    line-height: 1.2;
}


.bi-feed-description {

    margin: 7px 0 0;

    color: #7b8798;

    font-size: 13px;

    line-height: 1.55;

    max-width: 760px;
}


/* ============================================================
   LOCATION
   ============================================================ */

.bi-location-pill {

    display: inline-flex;

    align-items: center;

    gap: 9px;

    padding: 11px 15px;

    border-radius: 999px;

    background: rgba(240,253,244,.75);

    border: 1px solid rgba(34,197,94,.15);

    color: #15803d;

    font-size: 12px;

    font-weight: 800;

    white-space: nowrap;
}


.bi-location-dot {

    width: 8px;
    height: 8px;

    border-radius: 50%;

    background: #22c55e;

    box-shadow:
        0 0 0 4px rgba(34,197,94,.10);
}


/* ============================================================
   COMMAND GRID
   ============================================================ */

.bi-command-grid {

    position: relative;
    z-index: 2;

    display: grid;

    grid-template-columns:
        minmax(240px, 1.6fr)
        repeat(3, minmax(150px, 1fr));

    gap: 12px;

    margin-bottom: 17px;
}


.bi-command-card {

    padding: 16px 18px;

    border-radius: 16px;

    border: 1px solid rgba(229,231,235,.82);

    background: rgba(255,255,255,.58);
}


.bi-command-label {

    display: block;

    margin-bottom: 7px;

    color: #8b95a5;

    font-size: 10px;

    font-weight: 850;

    letter-spacing: .10em;

    text-transform: uppercase;
}


.bi-command-value {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #1f2937;

    font-size: 13px;

    font-weight: 800;

    line-height: 1.4;
}


.bi-command-value i {

    color: #6366f1;

    font-size: 12px;
}


/* ============================================================
   EXECUTIVE GRID
   ============================================================ */

.bi-executive-grid {

    position: relative;
    z-index: 2;

    display: grid;

    grid-template-columns:
        minmax(0, 1.65fr)
        minmax(280px, .8fr);

    gap: 15px;

    margin-bottom: 15px;
}


/* ============================================================
   REVENUE MASTER
   ============================================================ */

.bi-revenue-master {

    position: relative;

    padding: 24px;

    border-radius: 22px;

    border: 1px solid rgba(99,102,241,.12);

    background:
        linear-gradient(
            135deg,
            rgba(99,102,241,.10),
            rgba(255,255,255,.82) 56%,
            rgba(14,165,233,.06)
        );

    overflow: hidden;
}


.bi-revenue-master::after {

    content: "";

    position: absolute;

    width: 170px;
    height: 170px;

    right: -55px;
    top: -55px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(99,102,241,.11),
            transparent 68%
        );
}


.bi-card-kicker {

    color: #7b8798;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.bi-revenue-number {

    display: block;

    margin-top: 7px;

    color: #111827;

    font-size: clamp(32px, 3.4vw, 48px);

    font-weight: 900;

    line-height: 1.08;

    letter-spacing: -.055em;
}


.bi-revenue-caption {

    margin-top: 7px;

    color: #7b8798;

    font-size: 11px;
}


/* ============================================================
   REVENUE SOURCES
   ============================================================ */

.bi-revenue-sources {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;

    margin-top: 20px;
}


.bi-revenue-source {

    padding: 13px;

    border-radius: 13px;

    background: rgba(255,255,255,.62);

    border: 1px solid rgba(255,255,255,.78);
}


.bi-source-label {

    display: block;

    color: #8b95a5;

    font-size: 10px;

    font-weight: 750;
}


.bi-source-value {

    display: block;

    margin-top: 4px;

    color: #374151;

    font-size: 13px;

    font-weight: 850;
}


.bi-source-bar {

    width: 100%;

    height: 5px;

    margin-top: 9px;

    border-radius: 99px;

    background: rgba(229,231,235,.75);

    overflow: hidden;
}


.bi-source-bar span {

    display: block;

    height: 100%;

    border-radius: inherit;

    background: #6366f1;
}


/* ============================================================
   BUSINESS SCORE
   ============================================================ */

.bi-score-card {

    position: relative;

    padding: 24px;

    border-radius: 22px;

    border: 1px solid rgba(229,231,235,.82);

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.84),
            rgba(248,250,252,.66)
        );
}


.bi-score-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;
}


.bi-score-title {

    color: #6b7280;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .09em;
}


.bi-score-value {

    display: block;

    margin-top: 10px;

    color: #111827;

    font-size: 45px;

    font-weight: 900;

    line-height: 1;

    letter-spacing: -.05em;
}


.bi-score-max {

    color: #8b95a5;

    font-size: 12px;
}


.bi-score-track {

    height: 8px;

    margin-top: 16px;

    border-radius: 99px;

    background: #eef2f7;

    overflow: hidden;
}


.bi-score-fill {

    height: 100%;

    border-radius: inherit;

    background:
        linear-gradient(
            90deg,
            #6366f1,
            #0ea5e9
        );

    transition: width .5s ease;
}


.bi-score-status {

    display: flex;

    align-items: center;

    gap: 7px;

    margin-top: 13px;

    color: #6366f1;

    font-size: 12px;

    font-weight: 800;

    line-height: 1.4;
}


.bi-score-status i {

    font-size: 8px;
}


/* ============================================================
   KPI GRID
   ============================================================ */

.bi-kpi-grid {

    position: relative;
    z-index: 2;

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 13px;

    margin-bottom: 15px;
}


.bi-kpi {

    min-width: 0;

    padding: 18px;

    border-radius: 19px;

    border: 1px solid rgba(255,255,255,.82);

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.84),
            rgba(248,250,252,.66)
        );

    box-shadow:
        0 10px 28px rgba(15,23,42,.045),
        inset 0 1px 0 rgba(255,255,255,.95);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}


.bi-kpi:hover {

    transform: translateY(-2px);

    box-shadow:
        0 17px 35px rgba(15,23,42,.07);
}


.bi-kpi-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;
}


.bi-kpi-icon {

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    color: #6366f1;

    background: rgba(99,102,241,.09);

    font-size: 15px;
}


.bi-kpi-tag {

    color: #8b95a5;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .06em;
}


.bi-kpi-label {

    display: block;

    margin-top: 15px;

    color: #7b8798;

    font-size: 11px;

    font-weight: 750;
}


.bi-kpi-value {

    display: block;

    margin-top: 5px;

    color: #111827;

    font-size: clamp(21px, 2vw, 29px);

    font-weight: 900;

    line-height: 1.15;

    letter-spacing: -.04em;

    overflow-wrap: anywhere;
}


.bi-kpi-meta {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-top: 10px;

    color: #7b8798;

    font-size: 10px;

    font-weight: 650;

    line-height: 1.4;
}


.bi-kpi-meta i {

    color: #22c55e;

    font-size: 9px;
}


/* ============================================================
   LOWER INTELLIGENCE GRID
   ============================================================ */

.bi-intelligence-grid {

    position: relative;
    z-index: 2;

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 15px;
}


/* ============================================================
   PANEL
   ============================================================ */

.bi-panel {

    padding: 20px;

    border-radius: 21px;

    border: 1px solid rgba(229,231,235,.82);

    background: rgba(255,255,255,.57);
}


.bi-panel-heading {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 15px;
}


.bi-panel-title {

    display: flex;

    align-items: center;

    gap: 9px;

    color: #374151;

    font-size: 13px;

    font-weight: 850;
}


.bi-panel-title i {

    color: #6366f1;

    font-size: 14px;
}


.bi-panel-badge {

    padding: 6px 10px;

    border-radius: 999px;

    background: rgba(99,102,241,.07);

    color: #6366f1;

    font-size: 9px;

    font-weight: 850;

    text-transform: uppercase;

    letter-spacing: .05em;
}


/* ============================================================
   INTELLIGENCE SIGNALS
   ============================================================ */

.bi-feed-signal {

    display: flex;

    align-items: flex-start;

    gap: 11px;

    padding: 12px 0;

    border-bottom: 1px solid rgba(229,231,235,.58);
}


.bi-feed-signal:last-child {

    border-bottom: 0;

    padding-bottom: 0;
}


.bi-feed-signal:first-child {

    padding-top: 0;
}


.feed-signal-icon {

    width: 34px;
    height: 34px;

    flex: 0 0 34px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 10px;

    font-size: 12px;
}


.feed-positive .feed-signal-icon {

    color: #16a34a;

    background: rgba(34,197,94,.09);
}


.feed-warning .feed-signal-icon {

    color: #d97706;

    background: rgba(245,158,11,.10);
}


.feed-neutral .feed-signal-icon {

    color: #6366f1;

    background: rgba(99,102,241,.09);
}


.feed-signal-content {

    min-width: 0;
}


.feed-signal-title {

    color: #374151;

    font-size: 12px;

    font-weight: 850;

    line-height: 1.35;
}


.feed-signal-text {

    margin-top: 4px;

    color: #7b8798;

    font-size: 11px;

    line-height: 1.55;
}


/* ============================================================
   OPERATING MATRIX
   ============================================================ */

.bi-matrix {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 10px;
}


.bi-matrix-item {

    padding: 13px;

    border-radius: 13px;

    background: rgba(248,250,252,.72);

    border: 1px solid rgba(229,231,235,.7);
}


.bi-matrix-label {

    display: block;

    color: #8b95a5;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: .07em;

    text-transform: uppercase;
}


.bi-matrix-value {

    display: block;

    margin-top: 5px;

    color: #374151;

    font-size: 14px;

    font-weight: 850;

    line-height: 1.3;
}


.bi-matrix-sub {

    display: block;

    margin-top: 4px;

    color: #8b95a5;

    font-size: 9px;

    line-height: 1.45;
}


/* ============================================================
   PRIORITY SIGNAL
   ============================================================ */

.bi-priority {

    display: flex;

    align-items: center;

    gap: 11px;

    margin-top: 12px;

    padding: 13px;

    border-radius: 14px;

    border: 1px solid rgba(99,102,241,.10);

    background:
        linear-gradient(
            90deg,
            rgba(99,102,241,.06),
            rgba(14,165,233,.035)
        );
}


.bi-priority-icon {

    width: 36px;
    height: 36px;

    flex: 0 0 36px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: rgba(99,102,241,.09);

    color: #6366f1;

    font-size: 12px;
}


.bi-priority-label {

    color: #8b95a5;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .07em;
}


.bi-priority-text {

    margin-top: 3px;

    color: #374151;

    font-size: 11px;

    font-weight: 800;

    line-height: 1.4;
}


/* ============================================================
   DARK MODE
   ============================================================ */

.dark .business-intelligence-feed {

    background:
        linear-gradient(
            135deg,
            rgba(17,24,39,.93),
            rgba(15,23,42,.85)
        );

    border-color: rgba(55,65,81,.85);
}


.dark .bi-feed-title,
.dark .bi-revenue-number,
.dark .bi-score-value,
.dark .bi-kpi-value,
.dark .bi-source-value,
.dark .bi-command-value,
.dark .bi-panel-title,
.dark .feed-signal-title,
.dark .bi-matrix-value,
.dark .bi-priority-text {

    color: #f9fafb;
}


.dark .bi-feed-description,
.dark .bi-card-kicker,
.dark .bi-revenue-caption,
.dark .bi-kpi-label,
.dark .bi-kpi-meta,
.dark .bi-source-label,
.dark .bi-score-max,
.dark .feed-signal-text,
.dark .bi-matrix-label,
.dark .bi-matrix-sub,
.dark .bi-command-label {

    color: #9ca3af;
}


.dark .bi-command-card,
.dark .bi-kpi,
.dark .bi-score-card,
.dark .bi-panel {

    background:
        linear-gradient(
            145deg,
            rgba(17,24,39,.78),
            rgba(15,23,42,.68)
        );

    border-color: rgba(55,65,81,.75);
}


.dark .bi-revenue-master {

    background:
        linear-gradient(
            135deg,
            rgba(99,102,241,.12),
            rgba(17,24,39,.82) 56%,
            rgba(14,165,233,.07)
        );
}


.dark .bi-revenue-source,
.dark .bi-matrix-item {

    background: rgba(31,41,55,.56);

    border-color: rgba(55,65,81,.6);
}


.dark .bi-source-value,
.dark .bi-matrix-value {

    color: #e5e7eb;
}


.dark .bi-score-track,
.dark .bi-source-bar {

    background: rgba(55,65,81,.75);
}


.dark .bi-feed-signal {

    border-bottom-color: rgba(55,65,81,.6);
}


.dark .bi-priority {

    background:
        linear-gradient(
            90deg,
            rgba(99,102,241,.11),
            rgba(14,165,233,.06)
        );

    border-color: rgba(99,102,241,.17);
}


/* ============================================================
   LARGE TABLET
   ============================================================ */

@media (max-width: 1100px) {

    .bi-command-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }


    .bi-kpi-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }


    .bi-executive-grid {

        grid-template-columns: 1fr;
    }
}


/* ============================================================
   MOBILE
   ============================================================ */

@media (max-width: 700px) {

    .business-intelligence-feed {

        padding: 17px;

        border-radius: 22px;
    }


    .bi-feed-header {

        flex-direction: column;

        margin-bottom: 17px;
    }


    .bi-feed-brand {

        align-items: flex-start;
    }


    .bi-feed-icon {

        width: 48px;
        height: 48px;

        flex-basis: 48px;
    }


    .bi-location-pill {

        align-self: flex-start;

        font-size: 11px;
    }


    .bi-feed-eyebrow {

        font-size: 10px;
    }


    .bi-feed-title {

        font-size: 21px;
    }


    .bi-feed-description {

        font-size: 12px;
    }


    .bi-command-grid {

        grid-template-columns:
            1fr 1fr;

        gap: 9px;
    }


    .bi-command-card:first-child {

        grid-column: span 2;
    }


    .bi-command-card {

        padding: 12px;
    }


    .bi-command-label {

        font-size: 9px;
    }


    .bi-command-value {

        font-size: 11px;
    }


    .bi-kpi-grid {

        grid-template-columns:
            1fr 1fr;

        gap: 10px;
    }


    .bi-kpi {

        padding: 14px;

        border-radius: 16px;
    }


    .bi-kpi-icon {

        width: 37px;
        height: 37px;

        font-size: 13px;
    }


    .bi-kpi-tag {

        font-size: 8px;
    }


    .bi-kpi-label {

        font-size: 10px;
    }


    .bi-kpi-value {

        font-size: 20px;
    }


    .bi-kpi-meta {

        font-size: 9px;
    }


    .bi-intelligence-grid {

        grid-template-columns: 1fr;
    }


    .bi-revenue-master,
    .bi-score-card {

        padding: 18px;
    }


    .bi-revenue-number {

        font-size: 31px;
    }


    .bi-source-value {

        font-size: 12px;
    }


    .bi-panel {

        padding: 17px;
    }


    .bi-panel-title {

        font-size: 12px;
    }


    .feed-signal-title {

        font-size: 11px;
    }


    .feed-signal-text {

        font-size: 10px;
    }


    .bi-matrix-value {

        font-size: 13px;
    }
}


/* ============================================================
   SMALL PHONES
   ============================================================ */

@media (max-width: 390px) {

    .business-intelligence-feed {

        padding: 13px;
    }


    .bi-kpi-grid {

        grid-template-columns: 1fr;
    }


    .bi-command-grid {

        grid-template-columns: 1fr;
    }


    .bi-command-card:first-child {

        grid-column: span 1;
    }


    .bi-revenue-sources {

        grid-template-columns: 1fr;
    }


    .bi-matrix {

        grid-template-columns: 1fr;
    }


    .bi-feed-title {

        font-size: 19px;
    }


    .bi-feed-description {

        font-size: 11px;
    }


    .bi-revenue-number {

        font-size: 28px;
    }


    .bi-kpi-value {

        font-size: 22px;
    }
}

</style>


<!-- ============================================================
     UBAMUS BUSINESS INTELLIGENCE FEED
     ============================================================ -->

<section class="business-intelligence-feed">


    <!-- ========================================================
         HEADER
         ======================================================== -->

    <div class="bi-feed-header">

        <div class="bi-feed-brand">

            <div class="bi-feed-icon">

                <i class="fa-solid fa-brain"></i>

            </div>

            <div>

                <div class="bi-feed-eyebrow">

                    <i class="fa-solid fa-sparkles"></i>

                    Business Intelligence

                </div>

                <h3 class="bi-feed-title">

                    Operations Intelligence Feed

                </h3>

                <p class="bi-feed-description">

                    Consolidated intelligence across revenue, transactions,
                    inventory, workforce and supply operations.

                </p>

            </div>

        </div>


        <div class="bi-location-pill">

            <span class="bi-location-dot"></span>

            <?= htmlspecialchars($locationName) ?>

        </div>

    </div>



    <!-- ========================================================
         COMMAND BAR
         ======================================================== -->

    <div class="bi-command-grid">


        <div class="bi-command-card">

            <span class="bi-command-label">

                Operating Environment

            </span>

            <div class="bi-command-value">

                <i class="fa-solid fa-location-crosshairs"></i>

                <?= htmlspecialchars($locationName) ?>

                <?php if ($locationType): ?>

                    · <?= htmlspecialchars($locationType) ?>

                <?php endif; ?>

            </div>

        </div>


        <div class="bi-command-card">

            <span class="bi-command-label">

                Revenue Model

            </span>

            <div class="bi-command-value">

                <i class="fa-solid fa-chart-pie"></i>

                <?= htmlspecialchars($revenueStatus) ?>

            </div>

        </div>


        <div class="bi-command-card">

            <span class="bi-command-label">

                Cash Signal

            </span>

            <div class="bi-command-value">

                <i class="fa-solid fa-wallet"></i>

                <?= htmlspecialchars($cashStatus) ?>

            </div>

        </div>


        <div class="bi-command-card">

            <span class="bi-command-label">

                Operating Activity

            </span>

            <div class="bi-command-value">

                <i class="fa-solid fa-wave-square"></i>

                <?= htmlspecialchars($activityStatus) ?>

            </div>

        </div>

    </div>



    <!-- ========================================================
         EXECUTIVE INTELLIGENCE
         ======================================================== -->

    <div class="bi-executive-grid">


        <!-- ====================================================
             TOTAL REVENUE
             ==================================================== -->

        <article class="bi-revenue-master">

            <span class="bi-card-kicker">

                Total Operating Revenue

            </span>

            <strong class="bi-revenue-number">

                <?= intelligenceMoney($totalRevenue) ?>

            </strong>

            <div class="bi-revenue-caption">

                Current operating location · Services + Sales

            </div>


            <div class="bi-revenue-sources">


                <!-- SERVICES -->

                <div class="bi-revenue-source">

                    <span class="bi-source-label">

                        Services

                    </span>

                    <span class="bi-source-value">

                        <?= intelligenceMoney($totalServicesRevenue) ?>

                        · <?= intelligencePercent($servicePercentage) ?>

                    </span>

                    <div class="bi-source-bar">

                        <span
                            style="width: <?= min(100, max(0, $servicePercentage)) ?>%;"
                        ></span>

                    </div>

                </div>


                <!-- SALES -->

                <div class="bi-revenue-source">

                    <span class="bi-source-label">

                        Sales

                    </span>

                    <span class="bi-source-value">

                        <?= intelligenceMoney($totalSalesRevenue) ?>

                        · <?= intelligencePercent($salesPercentage) ?>

                    </span>

                    <div class="bi-source-bar">

                        <span
                            style="width: <?= min(100, max(0, $salesPercentage)) ?>%;"
                        ></span>

                    </div>

                </div>

            </div>

        </article>



        <!-- ====================================================
             BUSINESS SCORE
             ==================================================== -->

        <article class="bi-score-card">

            <div class="bi-score-top">

                <span class="bi-score-title">

                    Operating Health

                </span>

                <i class="fa-solid fa-gauge-high text-gray-400"></i>

            </div>


            <strong class="bi-score-value">

                <?= intelligenceNumber($businessScore) ?>

            </strong>

            <span class="bi-score-max">

                / 100 intelligence score

            </span>


            <div class="bi-score-track">

                <div
                    class="bi-score-fill"
                    style="width: <?= min(100, max(0, $businessScore)) ?>%;"
                ></div>

            </div>


            <div class="bi-score-status">

                <i class="fa-solid fa-circle"></i>

                <?= htmlspecialchars($businessStatus) ?>

            </div>

        </article>

    </div>



    <!-- ========================================================
         KPI MATRIX
         ======================================================== -->

    <div class="bi-kpi-grid">


        <!-- REVENUE -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-chart-line"></i>

                </div>

                <span class="bi-kpi-tag">

                    Finance

                </span>

            </div>

            <span class="bi-kpi-label">

                Revenue

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceMoney($totalRevenue) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-layer-group"></i>

                <?= intelligenceNumber($totalTransactions) ?> transactions

            </div>

        </article>



        <!-- COLLECTION -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-money-bill-transfer"></i>

                </div>

                <span class="bi-kpi-tag">

                    Cash

                </span>

            </div>

            <span class="bi-kpi-label">

                Collected

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceMoney($totalAmountPaid) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-percent"></i>

                <?= intelligencePercent($collectionRate) ?> realization

            </div>

        </article>



        <!-- OUTSTANDING -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-hourglass-half"></i>

                </div>

                <span class="bi-kpi-tag">

                    Exposure

                </span>

            </div>

            <span class="bi-kpi-label">

                Outstanding

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceMoney($totalOutstanding) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-triangle-exclamation"></i>

                Collection exposure

            </div>

        </article>



        <!-- TRANSACTIONS -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>

                <span class="bi-kpi-tag">

                    Activity

                </span>

            </div>

            <span class="bi-kpi-label">

                Transactions

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceNumber($totalTransactions) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-arrow-trend-up"></i>

                <?= intelligenceMoney($averageTransaction) ?> average

            </div>

        </article>



        <!-- PRODUCTS -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-box"></i>

                </div>

                <span class="bi-kpi-tag">

                    Catalogue

                </span>

            </div>

            <span class="bi-kpi-label">

                Products

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceNumber($totalProducts) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-database"></i>

                Company-wide catalogue

            </div>

        </article>



        <!-- INVENTORY -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-boxes-stacked"></i>

                </div>

                <span class="bi-kpi-tag">

                    Location

                </span>

            </div>

            <span class="bi-kpi-label">

                Stock Quantity

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceNumber($totalStockQuantity) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-cubes"></i>

                <?= intelligenceNumber($totalStocks) ?> stock records

            </div>

        </article>



        <!-- STAFF -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

                <span class="bi-kpi-tag">

                    Workforce

                </span>

            </div>

            <span class="bi-kpi-label">

                Location Staff

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceNumber($totalStaffs) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-user-group"></i>

                <?= htmlspecialchars($workforceStatus) ?>

            </div>

        </article>



        <!-- VENDORS -->

        <article class="bi-kpi">

            <div class="bi-kpi-top">

                <div class="bi-kpi-icon">

                    <i class="fa-solid fa-truck-fast"></i>

                </div>

                <span class="bi-kpi-tag">

                    Supply

                </span>

            </div>

            <span class="bi-kpi-label">

                Vendors

            </span>

            <strong class="bi-kpi-value">

                <?= intelligenceNumber($totalVendors) ?>

            </strong>

            <div class="bi-kpi-meta">

                <i class="fa-solid fa-network-wired"></i>

                Company-wide network

            </div>

        </article>

    </div>



    <!-- ========================================================
         INTELLIGENCE PANELS
         ======================================================== -->

    <div class="bi-intelligence-grid">


        <!-- ====================================================
             AUTOMATED SIGNALS
             ==================================================== -->

        <div class="bi-panel">

            <div class="bi-panel-heading">

                <div class="bi-panel-title">

                    <i class="fa-solid fa-wand-magic-sparkles"></i>

                    Automated Intelligence

                </div>

                <span class="bi-panel-badge">

                    Live Feed

                </span>

            </div>


            <?php foreach ($healthSignals as $signal): ?>

                <div class="bi-feed-signal <?= intelligenceClass($signal['type']) ?>">

                    <div class="feed-signal-icon">

                        <i class="fa-solid <?= htmlspecialchars($signal['icon']) ?>"></i>

                    </div>

                    <div class="feed-signal-content">

                        <div class="feed-signal-title">

                            <?= htmlspecialchars($signal['title']) ?>

                        </div>

                        <div class="feed-signal-text">

                            <?= htmlspecialchars($signal['text']) ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>


            <!-- PRIORITY -->

            <div class="bi-priority">

                <div class="bi-priority-icon">

                    <i class="fa-solid fa-crosshairs"></i>

                </div>

                <div>

                    <div class="bi-priority-label">

                        Primary Attention

                    </div>

                    <div class="bi-priority-text">

                        <?= htmlspecialchars($prioritySignal) ?>

                    </div>

                </div>

            </div>

        </div>



        <!-- ====================================================
             OPERATING MATRIX
             ==================================================== -->

        <div class="bi-panel">

            <div class="bi-panel-heading">

                <div class="bi-panel-title">

                    <i class="fa-solid fa-chart-simple"></i>

                    Operating Matrix

                </div>

                <span class="bi-panel-badge">

                    Location View

                </span>

            </div>


            <div class="bi-matrix">


                <!-- REVENUE PER STAFF -->

                <div class="bi-matrix-item">

                    <span class="bi-matrix-label">

                        Revenue / Staff

                    </span>

                    <strong class="bi-matrix-value">

                        <?= intelligenceMoney($revenuePerStaff) ?>

                    </strong>

                    <span class="bi-matrix-sub">

                        Operating revenue divided by location staff

                    </span>

                </div>


                <!-- TRANSACTIONS PER STAFF -->

                <div class="bi-matrix-item">

                    <span class="bi-matrix-label">

                        Transactions / Staff

                    </span>

                    <strong class="bi-matrix-value">

                        <?= number_format($transactionsPerStaff, 1) ?>

                    </strong>

                    <span class="bi-matrix-sub">

                        Transaction activity per staff member

                    </span>

                </div>


                <!-- STOCK DENSITY -->

                <div class="bi-matrix-item">

                    <span class="bi-matrix-label">

                        Stock / Product

                    </span>

                    <strong class="bi-matrix-value">

                        <?= number_format($stockPerProduct, 1) ?>

                    </strong>

                    <span class="bi-matrix-sub">

                        Location stock records relative to catalogue

                    </span>

                </div>


                <!-- REVENUE ENGINE -->

                <div class="bi-matrix-item">

                    <span class="bi-matrix-label">

                        Revenue Engine

                    </span>

                    <strong class="bi-matrix-value">

                        <?= htmlspecialchars($largestRevenueSource) ?>

                    </strong>

                    <span class="bi-matrix-sub">

                        Dominant operating revenue source

                    </span>

                </div>


                <!-- SERVICE ACTIVITY -->

                <div class="bi-matrix-item">

                    <span class="bi-matrix-label">

                        Service Jobs

                    </span>

                    <strong class="bi-matrix-value">

                        <?= intelligenceNumber($totalServicesCount) ?>

                    </strong>

                    <span class="bi-matrix-sub">

                        Current location service transactions

                    </span>

                </div>


                <!-- SALES ACTIVITY -->

                <div class="bi-matrix-item">

                    <span class="bi-matrix-label">

                        Sales Orders

                    </span>

                    <strong class="bi-matrix-value">

                        <?= intelligenceNumber($totalSalesCount) ?>

                    </strong>

                    <span class="bi-matrix-sub">

                        Current location sales transactions

                    </span>

                </div>

            </div>


            <!-- OPERATING STATUS -->

            <div class="bi-priority">

                <div class="bi-priority-icon">

                    <i class="fa-solid fa-location-dot"></i>

                </div>

                <div>

                    <div class="bi-priority-label">

                        Operating Scope

                    </div>

                    <div class="bi-priority-text">

                        <?= htmlspecialchars($locationName) ?>

                        · <?= htmlspecialchars($activityStatus) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>