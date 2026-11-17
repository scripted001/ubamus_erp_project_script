<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_id    = (int)($_SESSION['user_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| BRANCH MANAGER ACCESS SCOPE
|--------------------------------------------------------------------------
| Branch Managers can only see stock records and stock figures belonging
| to their assigned location. Other company roles retain company-wide access.
| Role and location are read from the database, not trusted from the browser.
*/
$userAccessStmt = $pdo->prepare("
    SELECT role, location_id
    FROM users
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");
$userAccessStmt->execute([$user_id, $company_id]);
$currentUserAccess = $userAccessStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUserAccess) {
    http_response_code(403);
    exit('Access denied: user account could not be verified.');
}

$isBranchManager = strtolower(trim((string)$currentUserAccess['role'])) === 'branch manager';
$userLocationId  = (int)($currentUserAccess['location_id'] ?? 0);

if ($isBranchManager && $userLocationId <= 0) {
    http_response_code(403);
    exit('Access denied: your account is not assigned to a valid branch location.');
}

/*
|--------------------------------------------------------------------------
| KPI / TABLE DATA
|--------------------------------------------------------------------------
| All KPI values below are calculated from the same stock rows rendered
| into the table. This makes the JavaScript filters able to recalculate
| the KPIs immediately without changing your existing UI structure.
|--------------------------------------------------------------------------
*/


// Total Stock In Count
$totalStockin = $pdo->prepare("
    SELECT COUNT(*)
    FROM stock_in
    WHERE company_id = ?
      AND (? = 0 OR location_id = ?)
");
$totalStockin->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId]);
$totalStockinCount = (int) $totalStockin->fetchColumn();


// Total Stock Movement Count
$totalStockMv = $pdo->prepare("
    SELECT COUNT(*)
    FROM stock_movements
    WHERE company_id = ?
      AND (? = 0 OR from_location_id = ? OR to_location_id = ?)
");
$totalStockMv->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId, $userLocationId]);
$StockMvCount = (int) $totalStockMv->fetchColumn();


// Total Quantity
$totalQuantityStmt = $pdo->prepare("
    SELECT COALESCE(SUM(quantity), 0)
    FROM stocks
    WHERE company_id = ?
      AND (? = 0 OR location_id = ?)
");
$totalQuantityStmt->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId]);
$totalQuantity = (float) $totalQuantityStmt->fetchColumn();


// Total Stock Sales Value
$totalStockValueStmt = $pdo->prepare("
    SELECT COALESCE(SUM(s.quantity * p.selling_price), 0)
    FROM stocks s
    INNER JOIN products p
        ON s.product_id = p.id
    WHERE s.company_id = ?
      AND (? = 0 OR s.location_id = ?)
");
$totalStockValueStmt->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId]);
$totalStockValue = (float) $totalStockValueStmt->fetchColumn();


// Total Stock Profit
$totalStockProfitStmt = $pdo->prepare("
    SELECT COALESCE(SUM((p.selling_price - p.cost_price) * s.quantity), 0)
    FROM stocks s
    INNER JOIN products p
        ON s.product_id = p.id
    WHERE s.company_id = ?
      AND (? = 0 OR s.location_id = ?)
");
$totalStockProfitStmt->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId]);
$totalStockProfit = (float) $totalStockProfitStmt->fetchColumn();


// Average / latest selling price shown by the original KPI.
// Kept compatible with the existing KPI, but made deterministic.
$stockUnitPriceStmt = $pdo->prepare("
    SELECT COALESCE(MAX(p.selling_price), 0)
    FROM stocks s
    INNER JOIN products p
        ON s.product_id = p.id
    WHERE s.company_id = ?
      AND (? = 0 OR s.location_id = ?)
");
$stockUnitPriceStmt->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId]);
$stock_unit_price = (float) $stockUnitPriceStmt->fetchColumn();


// Total product categories
$totalCategoriesStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM product_categories
    WHERE company_id = ?
");
$totalCategoriesStmt->execute([$company_id]);
$totalCategories = (int) $totalCategoriesStmt->fetchColumn();



// ----------------------
// SUPPLIERS / VENDORS
// ----------------------
$vendorsStmt = $pdo->prepare("
    SELECT id, business_name
    FROM vendors
    WHERE company_id = ?
    ORDER BY business_name ASC
");
$vendorsStmt->execute([$company_id]);
$vendors = $vendorsStmt->fetchAll(PDO::FETCH_ASSOC);


// ----------------------
// STOCK TABLE
// ----------------------
// Vendor lookup is done once here instead of querying vendors inside
// every table row.
$vendorMap = [];
foreach ($vendors as $vendor) {
    $vendorMap[(string)$vendor['id']] = $vendor['business_name'];
}

// ----------------------
// STOCK TABLE
// ----------------------
// Fetch the latest invoice attached to each stock-in record.
// A correlated subquery prevents duplicate stock rows where
// multiple invoice records exist for one stock-in transaction.

$vendorMap = [];

foreach ($vendors as $vendor) {
    $vendorMap[(string)$vendor['id']] = $vendor['business_name'];
}

$stocksStmt = $pdo->prepare("
    SELECT
        si.id,
        si.stock_id,
        si.product_id,
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

        l.name AS location_name,

        (
            SELECT sti.file_path
            FROM stock_in_invoices sti
            WHERE sti.stock_in_id = si.id
              AND sti.company_id = si.company_id
              AND sti.file_path IS NOT NULL
              AND sti.file_path != ''
            ORDER BY sti.id DESC
            LIMIT 1
        ) AS file_path

    FROM stock_in si

    INNER JOIN products p
        ON si.product_id = p.id
        AND p.company_id = si.company_id

    LEFT JOIN locations l
        ON si.location_id = l.id
        AND l.company_id = si.company_id

    LEFT JOIN users us
        ON si.recorded_by = us.id

    WHERE si.company_id = ?
      AND (? = 0 OR si.location_id = ?)

    ORDER BY si.id DESC
");

$stocksStmt->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId]);

$stockData = $stocksStmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// STOCK-IN INVOICE HELPERS
// ----------------------

function getStockInvoiceDetails($storedPath): array
{
    $storedPath = trim((string)($storedPath ?? ''));

    if ($storedPath === '') {
        return [
            'exists' => false,
            'url'    => ''
        ];
    }

    // Normalize Windows-style paths.
    $normalizedPath = str_replace('\\', '/', $storedPath);

    // Reject URLs and paths containing traversal outside the expected
    // application upload directory.
    if (
        preg_match('/^(https?:)?\/\//i', $normalizedPath) ||
        strpos($normalizedPath, "\0") !== false
    ) {
        return [
            'exists' => false,
            'url'    => ''
        ];
    }

    // Possible filesystem locations.
    $candidates = [
        $normalizedPath,
        __DIR__ . '/' . ltrim($normalizedPath, '/'),
        dirname(__DIR__) . '/' . ltrim($normalizedPath, '/')
    ];

    $existingFile = null;

    foreach ($candidates as $candidate) {
        $realPath = realpath($candidate);

        if (
            $realPath !== false &&
            is_file($realPath) &&
            is_readable($realPath)
        ) {
            $existingFile = $realPath;
            break;
        }
    }

    if ($existingFile === null) {
        return [
            'exists' => false,
            'url'    => ''
        ];
    }

    // Convert the stored relative path into a browser-friendly URL.
    $urlPath = ltrim($normalizedPath, '/');

    // Encode individual path segments without encoding directory separators.
    $urlPath = implode(
        '/',
        array_map('rawurlencode', explode('/', $urlPath))
    );

    return [
        'exists' => true,
        'url'    => $urlPath
    ];
}





// ----------------------
// CATEGORY CHART DATA
// ----------------------
$categoriesStmt = $pdo->prepare("
    SELECT
        c.name AS category,
        COALESCE(SUM(s.quantity), 0) AS total
    FROM product_categories c

    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.company_id = ?

    LEFT JOIN stocks s
        ON s.product_id = p.id
        AND s.company_id = ?
        AND (? = 0 OR s.location_id = ?)

    WHERE c.company_id = ?

    GROUP BY c.id, c.name
    ORDER BY c.name ASC
");

$categoriesStmt->execute([
    $company_id,
    $company_id,
    $isBranchManager ? 1 : 0,
    $userLocationId,
    $company_id
]);

$categoriesDataArr = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$categoriesLabels = array_column($categoriesDataArr, 'category');
$categoriesData   = array_column($categoriesDataArr, 'total');


// ----------------------
// STOCK TREND
// ----------------------
$trendStmt = $pdo->prepare("
    SELECT
        DATE(s.created_at) AS date,
        COALESCE(SUM(s.quantity), 0) AS total
    FROM stocks s
    WHERE s.company_id = ?
      AND (? = 0 OR s.location_id = ?)
      AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(s.created_at)
    ORDER BY DATE(s.created_at) ASC
");

$trendStmt->execute([$company_id, $isBranchManager ? 1 : 0, $userLocationId]);
$trendDataArr = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$trendLabels = array_column($trendDataArr, 'date');
$trendData   = array_column($trendDataArr, 'total');

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
        <?php echo htmlspecialchars($company_name); ?> | Stock Intelligence
    </title>

    <!-- ============================================================
         FONT AWESOME
    ============================================================ -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <!-- ============================================================
         EXISTING SITE STYLE
    ============================================================ -->

    <link
        href="style.css"
        rel="stylesheet"
    >


    <style>

        /* ============================================================
           GLOBAL
        ============================================================ */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            width: 100%;
            max-width: 100%;
            margin: 0;
            overflow-x: hidden;
        }

        button,
        input,
        select {
            font: inherit;
        }

        input,
        select,
        button {
            -webkit-tap-highlight-color: transparent;
        }


        /* ============================================================
           MAIN CONTENT
        ============================================================ */

        .stock-dashboard {
            width: 100%;
            min-width: 0;
            padding: 20px;
        }

        .stock-dashboard-inner {
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            min-width: 0;
        }


        /* ============================================================
           HERO / PAGE HEADER
        ============================================================ */

        .stock-hero {
            position: relative;
            width: 100%;
            min-width: 0;
            padding: 26px;
            margin-bottom: 22px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 24px;
            overflow: hidden;

            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(245, 158, 11, 0.12),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 15% 85%,
                    rgba(99, 102, 241, 0.10),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    rgba(15, 23, 42, 0.98),
                    rgba(17, 24, 39, 0.98)
                );

            box-shadow:
                0 18px 50px rgba(15, 23, 42, 0.18),
                inset 0 1px 0 rgba(255,255,255,0.05);
        }

        .stock-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;

            background:
                linear-gradient(
                    90deg,
                    transparent 49.5%,
                    rgba(245,158,11,0.04) 50%,
                    transparent 50.5%
                ),
                linear-gradient(
                    transparent 49.5%,
                    rgba(245,158,11,0.04) 50%,
                    transparent 50.5%
                );

            background-size: 40px 40px;
            opacity: .35;
        }

        .stock-hero-content {
            position: relative;
            z-index: 2;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .stock-hero-left {
            min-width: 0;
        }

        .stock-eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 8px;

            color: #f59e0b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .stock-eyebrow i {
            font-size: 13px;
        }

        .stock-hero-title {
            margin: 0;

            color: #f8fafc;
            font-size: clamp(24px, 3vw, 38px);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -0.035em;
        }

        .stock-hero-description {
            max-width: 760px;
            margin: 10px 0 0;

            color: #94a3b8;
            font-size: 14px;
            line-height: 1.7;
        }

        .stock-system-status {
            flex-shrink: 0;

            display: inline-flex;
            align-items: center;
            gap: 9px;

            padding: 10px 14px;

            border: 1px solid rgba(34,197,94,.22);
            border-radius: 999px;

            background: rgba(34,197,94,.08);

            color: #86efac;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .stock-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 12px rgba(34,197,94,.8);
        }


        /* ============================================================
           KPI GRID
        ============================================================ */

        .stock-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            width: 100%;
            margin-bottom: 20px;
        }

        .stock-kpi {
            position: relative;
            min-width: 0;
            min-height: 148px;

            padding: 20px;

            border: 1px solid rgba(148,163,184,.16);
            border-radius: 20px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.94)
                );

            box-shadow:
                0 10px 30px rgba(15,23,42,.07),
                inset 0 1px 0 rgba(255,255,255,.8);

            overflow: hidden;

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }

        .stock-kpi:hover {
            transform: translateY(-4px);

            border-color: rgba(245,158,11,.30);

            box-shadow:
                0 18px 45px rgba(15,23,42,.11),
                0 0 0 1px rgba(245,158,11,.05);
        }

        .stock-kpi::after {
            content: "";
            position: absolute;

            right: -30px;
            bottom: -40px;

            width: 120px;
            height: 120px;

            border-radius: 50%;

            background: rgba(245,158,11,.06);
            pointer-events: none;
        }

        .stock-kpi-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .stock-kpi-label {
            min-width: 0;

            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.45;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .stock-kpi-icon {
            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            width: 42px;
            height: 42px;

            border: 1px solid rgba(245,158,11,.18);
            border-radius: 12px;

            background: rgba(245,158,11,.09);

            color: #d97706;
            font-size: 17px;
        }

        .stock-kpi-value {
            position: relative;
            z-index: 2;

            margin-top: 16px;

            color: #0f172a;
            font-size: clamp(22px, 2.2vw, 31px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.025em;

            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stock-kpi-value.profit {
            color: #16a34a;
        }


        /* ============================================================
           FILTER PANEL
        ============================================================ */

        .stock-filter-card {
            width: 100%;
            min-width: 0;
            margin-bottom: 18px;

            border: 1px solid rgba(148,163,184,.16);
            border-radius: 22px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.95)
                );

            box-shadow:
                0 10px 35px rgba(15,23,42,.07);

            overflow: hidden;
        }

        .stock-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding: 17px 20px;

            border-bottom: 1px solid rgba(148,163,184,.13);
        }

        .stock-filter-title-wrap {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
        }

        .stock-filter-title-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 36px;
            height: 36px;

            flex-shrink: 0;

            border-radius: 10px;

            background: rgba(245,158,11,.10);
            color: #d97706;
        }

        .stock-filter-title {
            margin: 0;

            color: #1e293b;
            font-size: 14px;
            font-weight: 800;
        }

        .stock-filter-subtitle {
            margin: 2px 0 0;

            color: #94a3b8;
            font-size: 11px;
        }

        .stock-filter-body {
            padding: 18px 20px 20px;
        }

        .stock-filter-grid {
            display: grid;
            grid-template-columns:
                minmax(200px, 2fr)
                minmax(150px, 1fr)
                minmax(150px, 1fr)
                minmax(130px, .8fr)
                minmax(130px, .8fr);

            gap: 11px;
        }

        .stock-filter-field {
            position: relative;
            min-width: 0;
        }

        .stock-filter-field.full {
            grid-column: span 2;
        }

        .stock-filter-field i {
            position: absolute;
            left: 13px;
            top: 50%;
            z-index: 2;

            transform: translateY(-50%);

            color: #94a3b8;
            font-size: 13px;

            pointer-events: none;
        }

        .stock-filter-input,
        .stock-filter-select {
            width: 100%;
            height: 45px;

            padding: 0 13px 0 38px;

            border: 1px solid #e2e8f0;
            border-radius: 11px;

            outline: none;

            background: #f8fafc;

            color: #334155;

            font-size: 13px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .stock-filter-input::placeholder {
            color: #94a3b8;
        }

        .stock-filter-input:focus,
        .stock-filter-select:focus {
            border-color: rgba(245,158,11,.65);

            background: #fff;

            box-shadow:
                0 0 0 4px rgba(245,158,11,.09);
        }

        .stock-filter-select {
            cursor: pointer;
        }

        .stock-filter-button {
            height: 45px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 0 16px;

            border: 1px solid #e2e8f0;
            border-radius: 11px;

            background: #f8fafc;
            color: #475569;

            font-size: 12px;
            font-weight: 800;

            cursor: pointer;

            transition:
                transform .2s ease,
                border-color .2s ease,
                background .2s ease;
        }

        .stock-filter-button:hover {
            transform: translateY(-1px);

            border-color: rgba(245,158,11,.35);
            background: #fff;
        }


        /* ============================================================
           SECONDARY FILTER ROW
        ============================================================ */

        .stock-filter-secondary {
            display: grid;
            grid-template-columns:
                minmax(180px, 1fr)
                minmax(180px, 1fr)
                minmax(180px, 1fr)
                auto;

            gap: 11px;
            margin-top: 11px;
        }


        /* ============================================================
           FILTER COUNT
        ============================================================ */

        .stock-result-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            margin-bottom: 12px;
            padding: 0 3px;

            color: #64748b;
            font-size: 12px;
        }

        .stock-result-count {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .stock-result-count i {
            color: #f59e0b;
        }


        /* ============================================================
           TABLE CARD
        ============================================================ */

        .stock-table-card {
            width: 100%;
            min-width: 0;

            border: 1px solid rgba(148,163,184,.16);
            border-radius: 22px;

            background: #fff;

            box-shadow:
                0 12px 40px rgba(15,23,42,.08);

            overflow: hidden;
        }

        .stock-table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding: 17px 20px;

            border-bottom: 1px solid rgba(148,163,184,.13);
        }

        .stock-table-heading-left {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
        }

        .stock-table-heading-icon {
            width: 37px;
            height: 37px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 10px;

            background: rgba(99,102,241,.08);
            color: #6366f1;
        }

        .stock-table-heading h3 {
            margin: 0;

            color: #1e293b;
            font-size: 14px;
            font-weight: 800;
        }

        .stock-table-heading p {
            margin: 2px 0 0;

            color: #94a3b8;
            font-size: 11px;
        }

        .stock-table-live {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 7px 10px;

            border-radius: 8px;

            background: #f8fafc;

            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }


        /* ============================================================
           TABLE SCROLLING
        ============================================================ */

        #top-scroll,
        #bottom-scroll {
            width: 100%;
            max-width: 100%;

            overflow-x: auto;
            overflow-y: hidden;

            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        #top-scroll {
            height: 10px;

            background:
                linear-gradient(
                    90deg,
                    rgba(245,158,11,.04),
                    rgba(99,102,241,.04),
                    rgba(245,158,11,.04)
                );

            border-bottom: 1px solid rgba(148,163,184,.08);
        }

        #top-scroll > div {
            height: 1px !important;
        }

        #bottom-scroll {
            max-height: 650px;
        }

        #top-scroll::-webkit-scrollbar,
        #bottom-scroll::-webkit-scrollbar {
            height: 8px;
        }

        #top-scroll::-webkit-scrollbar-track,
        #bottom-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        #top-scroll::-webkit-scrollbar-thumb,
        #bottom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 99px;
        }

        #top-scroll::-webkit-scrollbar-thumb:hover,
        #bottom-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }


        /* ============================================================
           TABLE
        ============================================================ */

        #bottom-scroll table {
            width: max-content;
            min-width: 1750px;

            border-collapse: separate;
            border-spacing: 0;
        }

        #bottom-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 5;

            padding: 13px 15px;

            border-bottom: 1px solid #e2e8f0;

            background:
                linear-gradient(
                    180deg,
                    #f8fafc,
                    #f1f5f9
                );

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .075em;
            text-transform: uppercase;

            white-space: nowrap;
        }

        #bottom-scroll tbody td {
            padding: 14px 15px;

            border-bottom: 1px solid #eef2f7;

            color: #475569;

            font-size: 12px;

            white-space: nowrap;

            vertical-align: middle;
        }

        #bottom-scroll tbody tr {
            transition:
                background .18s ease,
                box-shadow .18s ease;
        }

        #bottom-scroll tbody tr:hover {
            background:
                linear-gradient(
                    90deg,
                    rgba(245,158,11,.035),
                    rgba(99,102,241,.025)
                );
        }

        #bottom-scroll tbody tr:last-child td {
            border-bottom: 0;
        }


        /* ============================================================
           TABLE PRODUCT
        ============================================================ */

        .stock-product-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stock-product-icon {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 9px;

            background: rgba(245,158,11,.09);
            color: #d97706;

            font-size: 13px;
        }

        .stock-product-name {
            color: #1e293b;
            font-weight: 700;
        }


        /* ============================================================
           TABLE BADGES
        ============================================================ */

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            min-height: 27px;

            padding: 0 9px;

            border-radius: 7px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }

        .stock-badge i {
            font-size: 9px;
        }

        .stock-badge.type {
            background: rgba(99,102,241,.08);
            color: #6366f1;
        }

        .stock-badge.production {
            background: rgba(34,197,94,.09);
            color: #16a34a;
        }

        .stock-badge.transfer {
            background: rgba(14,165,233,.09);
            color: #0284c7;
        }

        .stock-badge.neutral {
            background: #f1f5f9;
            color: #64748b;
        }


        /* ============================================================
           TABLE VALUES
        ============================================================ */

        .stock-money {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            white-space: nowrap;
        }

        .stock-money.cost {
            color: #475569;
        }

        .stock-money.selling {
            color: #334155;
        }

        .stock-money.capital {
            color: #2563eb;
        }

        .stock-money.profit {
            color: #16a34a;
        }

        .stock-money.loss {
            color: #dc2626;
        }

        .stock-quantity-value {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 48px;

            padding: 6px 9px;

            border-radius: 7px;

            background: #f8fafc;

            color: #334155;

            font-weight: 800;

            font-variant-numeric: tabular-nums;
        }


        /* ============================================================
           EMPTY TABLE
        ============================================================ */

        .stock-empty {
            padding: 65px 25px !important;

            text-align: center !important;

            color: #94a3b8 !important;
        }

        .stock-empty-icon {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 14px;

            border-radius: 16px;

            background: rgba(245,158,11,.08);
            color: #d97706;

            font-size: 23px;
        }

        .stock-empty strong {
            display: block;

            color: #475569;

            font-size: 14px;
        }

        .stock-empty span {
            display: block;

            margin-top: 5px;

            font-size: 12px;
        }


        /* ============================================================
           MOBILE FILTER TOOLS
        ============================================================ */

        .mobile-filter-scroll {
            width: 100%;
            overflow-x: auto;
        }


        /* ============================================================
           DARK MODE
        ============================================================ */

        .dark .stock-kpi,
        .dark .stock-filter-card,
        .dark .stock-table-card {
            border-color: rgba(148,163,184,.13);

            background:
                linear-gradient(
                    145deg,
                    rgba(17,24,39,.96),
                    rgba(15,23,42,.96)
                );

            box-shadow:
                0 14px 40px rgba(0,0,0,.24);
        }

        .dark .stock-kpi-label {
            color: #94a3b8;
        }

        .dark .stock-kpi-value {
            color: #f8fafc;
        }

        .dark .stock-kpi-icon {
            background: rgba(245,158,11,.10);
            color: #fbbf24;
        }

        .dark .stock-filter-header,
        .dark .stock-table-heading {
            border-color: rgba(148,163,184,.10);
        }

        .dark .stock-filter-title,
        .dark .stock-table-heading h3 {
            color: #f8fafc;
        }

        .dark .stock-filter-input,
        .dark .stock-filter-select,
        .dark .stock-filter-button {
            border-color: rgba(148,163,184,.18);

            background: rgba(15,23,42,.78);

            color: #e2e8f0;
        }

        .dark .stock-filter-input:focus,
        .dark .stock-filter-select:focus {
            background: rgba(15,23,42,.95);
        }

        .dark .stock-filter-input::placeholder {
            color: #64748b;
        }

        .dark .stock-filter-button:hover {
            background: rgba(30,41,59,.9);
        }

        .dark .stock-result-bar {
            color: #94a3b8;
        }

        .dark .stock-table-live {
            background: rgba(30,41,59,.65);
            color: #94a3b8;
        }

        .dark #top-scroll {
            background: rgba(15,23,42,.8);
            border-color: rgba(148,163,184,.08);
        }

        .dark #top-scroll::-webkit-scrollbar-track,
        .dark #bottom-scroll::-webkit-scrollbar-track {
            background: #111827;
        }

        .dark #top-scroll::-webkit-scrollbar-thumb,
        .dark #bottom-scroll::-webkit-scrollbar-thumb {
            background: #475569;
        }

        .dark #bottom-scroll thead th {
            background:
                linear-gradient(
                    180deg,
                    #172033,
                    #111827
                );

            color: #94a3b8;

            border-color: rgba(148,163,184,.10);
        }

        .dark #bottom-scroll tbody td {
            color: #cbd5e1;
            border-color: rgba(148,163,184,.08);
        }

        .dark #bottom-scroll tbody tr:hover {
            background:
                linear-gradient(
                    90deg,
                    rgba(245,158,11,.055),
                    rgba(99,102,241,.04)
                );
        }

        .dark .stock-product-name {
            color: #f1f5f9;
        }

        .dark .stock-quantity-value,
        .dark .stock-badge.neutral {
            background: rgba(255,255,255,.06);
            color: #cbd5e1;
        }

        .dark .stock-money.cost,
        .dark .stock-money.selling {
            color: #cbd5e1;
        }

        .dark .stock-empty strong {
            color: #cbd5e1;
        }


        /* ============================================================
           TABLET
        ============================================================ */

        @media (max-width: 1200px) {

            .stock-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .stock-filter-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

            .stock-filter-field.full {
                grid-column: span 2;
            }

            .stock-filter-secondary {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        /* ============================================================
           SMALL TABLET
        ============================================================ */

        @media (max-width: 900px) {

            .stock-dashboard {
                padding: 15px;
            }

            .stock-hero {
                padding: 21px;
                border-radius: 19px;
            }

            .stock-hero-content {
                align-items: flex-start;
                flex-direction: column;
            }

            .stock-kpi-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 12px;
            }

            .stock-filter-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .stock-filter-field.full {
                grid-column: span 2;
            }

            .stock-filter-secondary {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        /* ============================================================
           MOBILE
        ============================================================ */

        @media (max-width: 640px) {

            .stock-dashboard {
                padding: 10px;
            }

            .stock-hero {
                margin-bottom: 13px;
                padding: 18px;

                border-radius: 17px;
            }

            .stock-hero-title {
                font-size: 24px;
            }

            .stock-hero-description {
                font-size: 12px;
                line-height: 1.6;
            }

            .stock-system-status {
                width: 100%;
                justify-content: center;
            }


            /* KPI */

            .stock-kpi-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 9px;

                margin-bottom: 13px;
            }

            .stock-kpi {
                min-height: 125px;
                padding: 13px;

                border-radius: 15px;
            }

            .stock-kpi-label {
                font-size: 9px;
            }

            .stock-kpi-icon {
                width: 32px;
                height: 32px;

                border-radius: 9px;

                font-size: 13px;
            }

            .stock-kpi-value {
                margin-top: 13px;
                font-size: 19px;
            }


            /* FILTER */

            .stock-filter-card {
                border-radius: 16px;
                margin-bottom: 13px;
            }

            .stock-filter-header {
                padding: 13px 14px;
            }

            .stock-filter-body {
                padding: 13px;
            }

            .stock-filter-title-icon {
                width: 32px;
                height: 32px;
            }

            .stock-filter-title {
                font-size: 12px;
            }

            .stock-filter-subtitle {
                display: none;
            }

            .stock-filter-grid {
                grid-template-columns:
                    minmax(0, 1fr);

                gap: 8px;
            }

            .stock-filter-field.full {
                grid-column: auto;
            }

            .stock-filter-input,
            .stock-filter-select,
            .stock-filter-button {
                height: 43px;
            }

            .stock-filter-secondary {
                grid-template-columns:
                    minmax(0, 1fr);

                gap: 8px;
                margin-top: 8px;
            }


            /* RESULT */

            .stock-result-bar {
                margin-bottom: 8px;
                padding: 0 2px;

                font-size: 10px;
            }


            /* TABLE */

            .stock-table-card {
                border-radius: 16px;
            }

            .stock-table-heading {
                padding: 13px 14px;
            }

            .stock-table-heading h3 {
                font-size: 12px;
            }

            .stock-table-heading p {
                display: none;
            }

            .stock-table-live {
                padding: 6px 8px;
                font-size: 9px;
            }

            #bottom-scroll {
                max-height: 520px;
            }

            #bottom-scroll table {
                min-width: 1750px;
            }

            #bottom-scroll thead th {
                padding: 11px 12px;
                font-size: 9px;
            }

            #bottom-scroll tbody td {
                padding: 11px 12px;
                font-size: 11px;
            }

            .stock-product-icon {
                width: 30px;
                height: 30px;
            }

        }


        /* ============================================================
           VERY SMALL PHONES
        ============================================================ */

        @media (max-width: 380px) {

            .stock-dashboard {
                padding: 7px;
            }

            .stock-kpi {
                min-height: 115px;
                padding: 11px;
            }

            .stock-kpi-label {
                font-size: 8px;
            }

            .stock-kpi-icon {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }

            .stock-kpi-value {
                font-size: 17px;
            }

            .stock-hero-title {
                font-size: 21px;
            }

        }

    </style>

</head>


<body

    x-data="{
        page: 'ecommerce',
        'loaded': true,
        'darkMode': false,
        'stickyMenu': false,
        'sidebarToggle': false,
        'scrollTop': false
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


<!-- ============================================================
     PAGE WRAPPER
============================================================ -->

<div class="flex h-screen overflow-hidden">


    <!-- ========================================================
         SIDEBAR
    ======================================================== -->

    <?php include('sidebar.php'); ?>


    <!-- ========================================================
         CONTENT AREA
    ======================================================== -->

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
        style="min-width:0;"
    >


        <!-- ====================================================
             HEADER
        ==================================================== -->

        <?php include('header.php'); ?>


        <!-- ====================================================
             MAIN
        ==================================================== -->

        <main
            style="
                width:100%;
                min-width:0;
            "
        >


            <div class="stock-dashboard">

                <div class="stock-dashboard-inner">


                    <!-- ==================================================
                         HERO
                    ================================================== -->

                    <section class="stock-hero">

                        <div class="stock-hero-content">

                            <div class="stock-hero-left">

                                <div class="stock-eyebrow">

                                    <i class="fa-solid fa-cubes-stacked"></i>

                                    Inventory Intelligence

                                </div>

                                <h1 class="stock-hero-title">

                                    Stock Control Center

                                </h1>

                                <p class="stock-hero-description">

                                    Monitor stock quantities, inventory values,
                                    suppliers, locations, transfers and
                                    profitability from one centralized workspace.

                                </p>

                            </div>


                            <div class="stock-system-status">

                                <span class="stock-status-dot"></span>

                                Inventory System Online

                            </div>

                        </div>

                    </section>



                    <!-- ==================================================
                         KPI CARDS
                    ================================================== -->

                    <section class="stock-kpi-grid">


                        <!-- TOTAL QUANTITY -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Total Stock Quantity
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-cubes"></i>

                                </div>

                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalQuantity"
                            >

                                <?= number_format($totalQuantity, 2) ?>

                            </div>

                        </div>


                        <!-- UNIT PRICE -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Stock Unit Price
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-tag"></i>

                                </div>

                            </div>

                            <div
                                class="stock-kpi-value"
                                id="unit_price"
                            >

                                ₦<?= number_format($stock_unit_price, 2) ?>

                            </div>

                        </div>


                        <!-- STOCK VALUE -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Total Stock Sales Value
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-naira-sign"></i>

                                </div>

                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalStockValue"
                            >

                                ₦<?= number_format($totalStockValue, 2) ?>

                            </div>

                        </div>


                        <!-- TRANSFERS -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Total Stock Transfer
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-right-left"></i>

                                </div>

                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalStockTransfer"
                            >

                                <?= number_format($StockMvCount) ?>

                            </div>

                        </div>


                        <!-- PROFIT -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Total Stock Net Profit
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-chart-line"></i>

                                </div>

                            </div>

                            <div
                                class="stock-kpi-value profit"
                                id="totalStockProfit"
                            >

                                ₦<?= number_format($totalStockProfit, 2) ?>

                            </div>

                        </div>


                        <!-- STOCK-IN -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Total Stock-In Records
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-boxes-stacked"></i>

                                </div>

                            </div>

                            <div
                                class="stock-kpi-value"
                                id="totalStockIn"
                            >

                                <?= number_format($totalStockinCount) ?>

                            </div>

                        </div>


                        <!-- SYSTEM -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Product Categories
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-layer-group"></i>

                                </div>

                            </div>

                            <div class="stock-kpi-value">

                                <?= number_format($totalCategories) ?>

                            </div>

                        </div>


                        <!-- MOVEMENTS -->

                        <div class="stock-kpi">

                            <div class="stock-kpi-top">

                                <div class="stock-kpi-label">
                                    Stock Movements
                                </div>

                                <div class="stock-kpi-icon">

                                    <i class="fa-solid fa-arrows-rotate"></i>

                                </div>

                            </div>

                            <div class="stock-kpi-value">

                                <?= number_format($StockMvCount) ?>

                            </div>

                        </div>


                    </section>



                    <!-- ==================================================
                         FILTER CARD
                    ================================================== -->

                    <section class="stock-filter-card">


                        <div class="stock-filter-header">

                            <div class="stock-filter-title-wrap">

                                <div class="stock-filter-title-icon">

                                    <i class="fa-solid fa-sliders"></i>

                                </div>

                                <div>

                                    <h2 class="stock-filter-title">
                                        Inventory Filters
                                    </h2>

                                    <p class="stock-filter-subtitle">
                                        Refine the stock intelligence view
                                    </p>

                                </div>

                            </div>


                            <button
                                type="button"
                                id="clearStockFilters"
                                class="stock-filter-button"
                            >

                                <i class="fa-solid fa-rotate-left"></i>

                                Clear Filters

                            </button>

                        </div>


                        <div class="stock-filter-body">


                            <!-- PRIMARY FILTERS -->

                            <div class="stock-filter-grid">


                                <!-- SEARCH -->

                                <div class="stock-filter-field full">

                                    <i class="fa-solid fa-magnifying-glass"></i>

                                    <input
                                        type="text"
                                        id="searchInput"
                                        placeholder="Search product, barcode or transaction ID..."
                                        class="stock-filter-input"
                                        autocomplete="off"
                                    >

                                </div>


                                <!-- DATE -->

                                <div class="stock-filter-field">

                                    <i class="fa-solid fa-calendar-days"></i>

                                    <input
                                        type="date"
                                        id="dateFilter"
                                        class="stock-filter-input"
                                    >

                                </div>
<!-- MIN PRICE -->

                                <div class="stock-filter-field">

                                    <i class="fa-solid fa-arrow-down-1-9"></i>

                                    <input
                                        type="number"
                                        id="minPrice"
                                        placeholder="Minimum Cost Price"
                                        class="stock-filter-input"
                                        step="0.01"
                                    >

                                </div>


                                <!-- MAX PRICE -->

                                <div class="stock-filter-field">

                                    <i class="fa-solid fa-arrow-up-1-9"></i>

                                    <input
                                        type="number"
                                        id="maxPrice"
                                        placeholder="Maximum Cost Price"
                                        class="stock-filter-input"
                                        step="0.01"
                                    >

                                </div>


                            </div>


                            <!-- SECONDARY FILTERS -->

                            <div class="stock-filter-secondary">


                                <!-- SUPPLIER -->

                                <div class="stock-filter-field">

                                    <i class="fa-solid fa-truck"></i>

                                    <select
                                        id="supplierFilter"
                                        class="stock-filter-select"
                                    >

                                        <option value="">
                                            All Suppliers
                                        </option>

                                        <option value="Production">
                                            Production
                                        </option>

                                        <?php foreach ($vendors as $vendor): ?>

                                            <option
                                                value="<?= htmlspecialchars($vendor['business_name']) ?>"
                                            >

                                                <?= htmlspecialchars($vendor['business_name']) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>


                                <!-- STOCK LEVEL -->

                                <div class="stock-filter-field">

                                    <i class="fa-solid fa-chart-simple"></i>

                                    <select
                                        id="stock_level"
                                        class="stock-filter-select"
                                    >

                                        <option value="">
                                            All Stock Levels
                                        </option>

                                        <option value="low">
                                            Low Stock Below 10
                                        </option>

                                        <option value="zero">
                                            Out of Stock
                                        </option>

                                        <option value="above">
                                            Above 10
                                        </option>

                                    </select>

                                </div>


                            </div>

                        </div>

                    </section>



                    <!-- ==================================================
                         RESULT COUNT
                    ================================================== -->

                    <div class="stock-result-bar">

                        <div
                            id="stockFilterCount"
                            class="stock-result-count"
                        >

                            <i class="fa-solid fa-database"></i>

                            Showing
                            <?= count($stockData) ?>
                            of
                            <?= count($stockData) ?>
                            records

                        </div>

                    </div>



                    <!-- ==================================================
                         TABLE CARD
                    ================================================== -->

                    <section class="stock-table-card">


                        <div class="stock-table-heading">

                            <div class="stock-table-heading-left">

                                <div class="stock-table-heading-icon">

                                    <i class="fa-solid fa-table-list"></i>

                                </div>

                                <div>

                                    <h3>
                                        Stock Records
                                    </h3>

                                    <p>
                                        Detailed inventory transaction ledger
                                    </p>

                                </div>

                            </div>


                            <div class="stock-table-live">

                                <i class="fa-solid fa-circle"></i>

                                Live Data

                            </div>

                        </div>



                        <!-- ==================================================
                             TOP SCROLL
                        ================================================== -->

                        <div id="top-scroll">

                            <div
                                style="
                                    width:2000px;
                                    height:1px;
                                "
                            ></div>

                        </div>



                        <!-- ==================================================
                             TABLE SCROLL
                        ================================================== -->

                        <div id="bottom-scroll">


                            <table>


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
                                            <i class="fa-solid fa-cubes"></i>
                                            Quantity
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-shuffle"></i>
                                            Type
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-fingerprint"></i>
                                            Transaction ID
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-truck"></i>
                                            Supplier / Vendor
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-location-dot"></i>
                                            Location
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-user"></i>
                                            Sender
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-money-bill"></i>
                                            Cost Price
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-tag"></i>
                                            Selling Price
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-coins"></i>
                                            Capital Value
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-naira-sign"></i>
                                            Sales Value
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-chart-line"></i>
                                            Profit
                                        </th>

                                        
                                        <th>
                                            <i class="fa-solid fa-chart-line"></i>
                                            Invoice
                                        </th>

                                        <th>
                                            <i class="fa-solid fa-clock"></i>
                                            Date & Time
                                        </th>

                                    </tr>

                                </thead>


                                <tbody id="stockTableBody">


                                <?php if (!empty($stockData)): ?>


                                    <?php

                                    $count = 1;

                                    $lowStockThreshold = 10;

                                    ?>


                                    <?php foreach ($stockData as $row): ?>


                                        <?php

                                        $quantity =
                                            (float)($row['quantity'] ?? 0);

                                        $costPrice =
                                            (float)($row['cost_price'] ?? 0);

                                        $sellingPrice =
                                            (float)($row['selling_price'] ?? 0);

                                        $totalCostValue =
                                            $quantity * $costPrice;

                                        $totalSellingValue =
                                            $quantity * $sellingPrice;

                                        $profitValue =
                                            ($sellingPrice - $costPrice)
                                            * $quantity;

                                        $supplierValue =
                                            $row['supplier'] ?? '';

                                        if (
                                            $supplierValue === 'General Merchant'
                                            ||
                                            $supplierValue === ''
                                        ) {

                                            $supplier =
                                                $supplierValue !== ''
                                                    ? $supplierValue
                                                    : '—';

                                        } elseif (
                                            isset(
                                                $vendorMap[
                                                    (string)$supplierValue
                                                ]
                                            )
                                        ) {

                                            $supplier =
                                                $vendorMap[
                                                    (string)$supplierValue
                                                ];

                                        } else {

                                            $supplier =
                                                $supplierValue;

                                        }

                                        $locationName =
                                            $row['location_name'] ?? '';

                                        $stockLevelValue =
                                            $quantity <= 0
                                                ? 'zero'
                                                : (
                                                    $quantity <=
                                                    $lowStockThreshold
                                                        ? 'low'
                                                        : 'above'
                                                );

                                        ?>


                                        <tr

                                            data-stock-level="<?= htmlspecialchars($stockLevelValue) ?>"

                                            data-product="<?= htmlspecialchars($row['name'] ?? '') ?>"

                                            data-barcode="<?= htmlspecialchars($row['barcode'] ?? '') ?>"

                                            data-quantity="<?= htmlspecialchars($quantity) ?>"

                                            data-transaction="<?= htmlspecialchars($row['transfer_ID'] ?? '') ?>"

                                            data-supplier="<?= htmlspecialchars($supplier) ?>"

                                            data-location="<?= htmlspecialchars($locationName) ?>"

                                            data-cost-price="<?= htmlspecialchars($costPrice) ?>"

                                            data-selling-price="<?= htmlspecialchars($sellingPrice) ?>"

                                            data-total-cost="<?= htmlspecialchars($totalCostValue) ?>"

                                            data-total-selling="<?= htmlspecialchars($totalSellingValue) ?>"

                                            data-profit="<?= htmlspecialchars($profitValue) ?>"

                                            data-date="<?= htmlspecialchars(substr((string)($row['created_at'] ?? ''), 0, 10)) ?>"

                                        >


                                            <!-- S/N -->

                                            <td>

                                                <?= htmlspecialchars($count++) ?>

                                            </td>


                                            <!-- PRODUCT -->

                                            <td>

                                                <div class="stock-product-cell">

                                                    <div class="stock-product-icon">

                                                        <i class="fa-solid fa-box"></i>

                                                    </div>

                                                    <span class="stock-product-name">

                                                        <?= htmlspecialchars(
                                                            $row['name']
                                                            ?? 'Unknown Product'
                                                        ) ?>

                                                    </span>

                                                </div>

                                            </td>


                                            <!-- BARCODE -->

                                            <td>

                                                <span class="stock-badge neutral">

                                                    <i class="fa-solid fa-barcode"></i>

                                                    <?= htmlspecialchars(
                                                        $row['barcode']
                                                        ?? '—'
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- QUANTITY -->

                                            <td>

                                                <span
                                                    class="stock-quantity-value"
                                                    title="Stock quantity"
                                                >

                                                    <?= number_format(
                                                        $quantity,
                                                        2
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- TYPE -->

                                            <td>

                                                <?php

                                                $rowType =
                                                    strtolower(
                                                        (string)(
                                                            $row['type']
                                                            ?? ''
                                                        )
                                                    );

                                                $isProduction =
                                                    strpos(
                                                        $rowType,
                                                        'production'
                                                    ) !== false;

                                                ?>

                                                <span
                                                    class="stock-badge <?= $isProduction ? 'production' : 'type' ?>"
                                                >

                                                    <i class="fa-solid <?= $isProduction
                                                        ? 'fa-industry'
                                                        : 'fa-arrow-right-arrow-left'
                                                    ?>"></i>

                                                    <?= htmlspecialchars(
                                                        $row['type']
                                                        ?? '—'
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- TRANSACTION ID -->

                                            <td>

                                                <span class="stock-badge neutral">

                                                    <i class="fa-solid fa-fingerprint"></i>

                                                    <?= htmlspecialchars(
                                                        $row['transfer_ID']
                                                        ?? '—'
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- SUPPLIER -->

                                            <td>

                                                <span>

                                                    <i
                                                        class="fa-solid fa-truck"
                                                        style="
                                                            margin-right:6px;
                                                            color:#94a3b8;
                                                        "
                                                    ></i>

                                                    <?= htmlspecialchars(
                                                        $supplier
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- LOCATION -->

                                            <td>

                                                <?php if (
                                                    !empty($locationName)
                                                ): ?>

                                                    <span>

                                                        <i
                                                            class="fa-solid fa-location-dot"
                                                            style="
                                                                margin-right:5px;
                                                                color:#94a3b8;
                                                            "
                                                        ></i>

                                                        <?= htmlspecialchars(
                                                            $locationName
                                                        ) ?>

                                                    </span>

                                                <?php else: ?>

                                                    <span
                                                        style="
                                                            color:#94a3b8;
                                                        "
                                                    >
                                                        —
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- SENDER -->

                                            <td>

                                                <span>

                                                    <i
                                                        class="fa-solid fa-user"
                                                        style="
                                                            margin-right:6px;
                                                            color:#94a3b8;
                                                        "
                                                    ></i>

                                                    <?= htmlspecialchars(
                                                        $row['email']
                                                        ?? '—'
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- COST -->

                                            <td>

                                                <span class="stock-money cost">

                                                    ₦<?= number_format(
                                                        $costPrice,
                                                        2
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- SELLING -->

                                            <td>

                                                <span class="stock-money selling">

                                                    ₦<?= number_format(
                                                        $sellingPrice,
                                                        2
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- CAPITAL -->

                                            <td>

                                                <span class="stock-money capital">

                                                    ₦<?= number_format(
                                                        $totalCostValue,
                                                        2
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- SALES -->

                                            <td>

                                                <span class="stock-money selling">

                                                    ₦<?= number_format(
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
                                                            ? 'profit'
                                                            : 'loss'
                                                        ?>
                                                    "
                                                >

                                                    <i
                                                        class="fa-solid
                                                            <?= $profitValue >= 0
                                                                ? 'fa-arrow-trend-up'
                                                                : 'fa-arrow-trend-down'
                                                            ?>"
                                                        style="
                                                            margin-right:4px;
                                                        "
                                                    ></i>

                                                    ₦<?= number_format(
                                                        $profitValue,
                                                        2
                                                    ) ?>

                                                </span>

                                            </td>

                                            <!-- invoice -->
                                            <!-- INVOICE -->

<td>

    <?php

    $invoiceDetails = getStockInvoiceDetails(
        $row['file_path'] ?? ''
    );

    if ($invoiceDetails['exists']):

        $invoiceUrl = $invoiceDetails['url'];

    ?>

        <div style="
            display:flex;
            align-items:center;
            gap:8px;
        ">

            <!-- VIEW INVOICE -->

            <a
                href="<?= htmlspecialchars(
                    $invoiceUrl,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                target="_blank"
                rel="noopener noreferrer"
                style="
                    display:inline-flex;
                    align-items:center;
                    gap:6px;
                    padding:7px 10px;
                    border-radius:7px;
                    background:rgba(59,130,246,.09);
                    color:#2563eb;
                    font-size:11px;
                    font-weight:800;
                    text-decoration:none;
                "
                title="View invoice"
            >

                <i class="fa-regular fa-eye"></i>

                View

            </a>


            <!-- DOWNLOAD INVOICE -->

            <a
                href="<?= htmlspecialchars(
                    $invoiceUrl,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                download
                style="
                    display:inline-flex;
                    align-items:center;
                    gap:6px;
                    padding:7px 10px;
                    border-radius:7px;
                    background:rgba(34,197,94,.09);
                    color:#16a34a;
                    font-size:11px;
                    font-weight:800;
                    text-decoration:none;
                "
                title="Download invoice"
            >

                <i class="fa-solid fa-download"></i>

                Download

            </a>

        </div>

    <?php else: ?>

        <span style="
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:7px 10px;
            border-radius:7px;
            background:rgba(148,163,184,.10);
            color:#94a3b8;
            font-size:11px;
            font-weight:700;
        ">

            <i class="fa-regular fa-file-circle-xmark"></i>

            Not found

        </span>

    <?php endif; ?>

</td>

                                            <!-- CREATED -->

                                            <td>

                                                <span>

                                                    <i
                                                        class="fa-regular fa-clock"
                                                        style="
                                                            margin-right:6px;
                                                            color:#94a3b8;
                                                        "
                                                    ></i>

                                                    <?= htmlspecialchars(
                                                        $row['created_at']
                                                        ?? '-'
                                                    ) ?>

                                                </span>

                                            </td>


                                        </tr>


                                    <?php endforeach; ?>


                                <?php else: ?>


                                    <tr>

                                        <td
                                            colspan="15"
                                            class="stock-empty"
                                        >

                                            <div class="stock-empty-icon">

                                                <i class="fa-solid fa-box-open"></i>

                                            </div>

                                            <strong>
                                                No stock records found
                                            </strong>

                                            <span>
                                                Try changing your filters or
                                                adding a new stock record.
                                            </span>

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


<!-- ============================================================
     FOOTER
============================================================ -->

<?php include('footer.php'); ?>


<!-- ============================================================
     EXISTING SCRIPTS
============================================================ -->

<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script
    defer
    src="bundle.js"
></script>


<!-- ============================================================
     YOUR EXISTING FILTER JAVASCRIPT
     
     KEEP YOUR CURRENT FILTER SCRIPT EXACTLY AS-IS HERE.
     NOTHING IN THE FILTER LOGIC NEEDS TO CHANGE.
============================================================ -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const tableBody =
        document.getElementById('stockTableBody');

    const searchInput =
        document.getElementById('searchInput');

    const dateFilter =
        document.getElementById('dateFilter');

    const minPrice =
        document.getElementById('minPrice');

    const maxPrice =
        document.getElementById('maxPrice');

    const supplierFilter =
        document.getElementById('supplierFilter');

    const stockLevel =
        document.getElementById('stock_level');

    const clearButton =
        document.getElementById('clearStockFilters');


    const totalQuantityEl =
        document.getElementById('totalQuantity');

    const unitPriceEl =
        document.getElementById('unit_price');

    const totalStockValueEl =
        document.getElementById('totalStockValue');

    const totalStockTransferEl =
        document.getElementById('totalStockTransfer');

    const totalStockProfitEl =
        document.getElementById('totalStockProfit');

    const totalStockInEl =
        document.getElementById('totalStockIn');


    const filterCountEl =
        document.getElementById('stockFilterCount');


    if (!tableBody) {

        console.error(
            'Stock table body #stockTableBody was not found.'
        );

        return;

    }


    function normalize(value) {

        return String(value ?? '')
            .trim()
            .toLowerCase();

    }


    function getNumber(value) {

        const number = parseFloat(
            String(value ?? '')
                .replace(/₦/g, '')
                .replace(/,/g, '')
                .replace(/\s/g, '')
        );

        return Number.isFinite(number)
            ? number
            : 0;

    }


    function getRows() {

        return Array.from(
            tableBody.querySelectorAll(
                'tr[data-product]'
            )
        );

    }


    function formatNumber(
        value,
        decimals = 2
    ) {

        return new Intl.NumberFormat(
            'en-NG',
            {
                minimumFractionDigits:
                    decimals,

                maximumFractionDigits:
                    decimals
            }
        ).format(value);

    }


    function updateKPIs(visibleRows) {

        let quantity = 0;

        let totalCostValue = 0;

        let totalSellingValue = 0;

        let totalProfit = 0;

        let sellingPrices = [];


        visibleRows.forEach(
            function (row) {

                const rowQuantity =
                    getNumber(
                        row.dataset.quantity
                    );

                const rowCostPrice =
                    getNumber(
                        row.dataset.costPrice
                    );

                const rowSellingPrice =
                    getNumber(
                        row.dataset.sellingPrice
                    );


                const rowTotalCost =
                    getNumber(
                        row.dataset.totalCost
                    ) ||
                    (
                        rowQuantity *
                        rowCostPrice
                    );


                const rowTotalSelling =
                    getNumber(
                        row.dataset.totalSelling
                    ) ||
                    (
                        rowQuantity *
                        rowSellingPrice
                    );


                const rowProfit =
                    getNumber(
                        row.dataset.profit
                    ) ||
                    (
                        (
                            rowSellingPrice -
                            rowCostPrice
                        ) *
                        rowQuantity
                    );


                quantity += rowQuantity;

                totalCostValue +=
                    rowTotalCost;

                totalSellingValue +=
                    rowTotalSelling;

                totalProfit +=
                    rowProfit;


                if (
                    rowSellingPrice > 0
                ) {

                    sellingPrices.push(
                        rowSellingPrice
                    );

                }

            }
        );


        let unitPrice = 0;


        if (
            sellingPrices.length > 0
        ) {

            unitPrice =
                sellingPrices.reduce(
                    (
                        sum,
                        price
                    ) =>
                        sum + price,
                    0
                ) /
                sellingPrices.length;

        }


        const visibleCount =
            visibleRows.length;


        if (totalQuantityEl) {

            totalQuantityEl.textContent =
                formatNumber(
                    quantity,
                    2
                );

        }


        if (unitPriceEl) {

            unitPriceEl.textContent =
                '₦' +
                formatNumber(
                    unitPrice,
                    2
                );

        }


        if (totalStockValueEl) {

            totalStockValueEl.textContent =
                '₦' +
                formatNumber(
                    totalSellingValue,
                    2
                );

        }


        if (totalStockTransferEl) {

            totalStockTransferEl.textContent =
                formatNumber(
                    visibleCount,
                    0
                );

        }


        if (totalStockProfitEl) {

            totalStockProfitEl.textContent =
                '₦' +
                formatNumber(
                    totalProfit,
                    2
                );

            totalStockProfitEl.classList.remove(
                'text-green-600',
                'text-red-600'
            );

            totalStockProfitEl.classList.add(
                totalProfit >= 0
                    ? 'text-green-600'
                    : 'text-red-600'
            );

        }


        if (totalStockInEl) {

            totalStockInEl.textContent =
                formatNumber(
                    visibleCount,
                    0
                );

        }

    }


    function updateResultCount(
        visible,
        total
    ) {

        if (filterCountEl) {

            filterCountEl.innerHTML =
                '<i class="fa-solid fa-database"></i>' +
                ' Showing ' +
                visible +
                ' of ' +
                total +
                ' records';

        }

    }


    function filterTable() {

        const rows =
            getRows();


        const search =
            normalize(
                searchInput?.value
            );


        const date =
            dateFilter?.value ||
            '';


        const supplier =
            normalize(
                supplierFilter?.value
            );


        const stock =
            stockLevel?.value ||
            '';


        const min =
            minPrice?.value !== ''
                ? getNumber(
                    minPrice.value
                )
                : null;


        const max =
            maxPrice?.value !== ''
                ? getNumber(
                    maxPrice.value
                )
                : null;


        const visibleRows = [];


        rows.forEach(
            function (row) {

                const product =
                    normalize(
                        row.dataset.product
                    );


                const barcode =
                    normalize(
                        row.dataset.barcode
                    );


                const transaction =
                    normalize(
                        row.dataset.transaction
                    );


                const supplierName =
                    normalize(
                        row.dataset.supplier
                    );


                const quantity =
                    getNumber(
                        row.dataset.quantity
                    );


                const costPrice =
                    getNumber(
                        row.dataset.costPrice
                    );


                const rowDate =
                    String(
                        row.dataset.date ||
                        ''
                    ).substring(
                        0,
                        10
                    );


                const searchMatch =
                    search === '' ||
                    product.includes(search) ||
                    barcode.includes(search) ||
                    transaction.includes(search);


                const dateMatch =
                    date === '' ||
                    rowDate === date;


                const supplierMatch =
                    supplier === '' ||
                    supplierName === supplier;


                let rowStockLevel;


                if (quantity <= 0) {

                    rowStockLevel =
                        'zero';

                } else if (
                    quantity <= 10
                ) {

                    rowStockLevel =
                        'low';

                } else {

                    rowStockLevel =
                        'above';

                }


                const stockMatch =
                    stock === '' ||
                    rowStockLevel === stock;


                const minPriceMatch =
                    min === null ||
                    costPrice >= min;


                const maxPriceMatch =
                    max === null ||
                    costPrice <= max;


                const show =
                    searchMatch &&
                    dateMatch &&
                    supplierMatch &&
                    stockMatch &&
                    minPriceMatch &&
                    maxPriceMatch;


                row.style.display =
                    show
                        ? ''
                        : 'none';


                if (show) {

                    visibleRows.push(row);

                }

            }
        );


        updateResultCount(
            visibleRows.length,
            rows.length
        );


        updateKPIs(
            visibleRows
        );

    }


    function clearFilters() {

        if (searchInput) {
            searchInput.value = '';
        }

        if (dateFilter) {
            dateFilter.value = '';
        }

        if (minPrice) {
            minPrice.value = '';
        }

        if (maxPrice) {
            maxPrice.value = '';
        }

        if (supplierFilter) {
            supplierFilter.value = '';
        }

        if (stockLevel) {
            stockLevel.value = '';
        }


        filterTable();

    }


    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterTable
        );

    }


    if (dateFilter) {

        dateFilter.addEventListener(
            'change',
            filterTable
        );

    }


    if (minPrice) {

        minPrice.addEventListener(
            'input',
            filterTable
        );

    }


    if (maxPrice) {

        maxPrice.addEventListener(
            'input',
            filterTable
        );

    }


    if (supplierFilter) {

        supplierFilter.addEventListener(
            'change',
            filterTable
        );

    }


    if (stockLevel) {

        stockLevel.addEventListener(
            'change',
            filterTable
        );

    }


    if (clearButton) {

        clearButton.addEventListener(
            'click',
            clearFilters
        );

    }


    filterTable();

});

</script>


<!-- ============================================================
     TABLE TOP SCROLLBAR
============================================================ -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const topScroll =
            document.getElementById(
                'top-scroll'
            );

        const bottomScroll =
            document.getElementById(
                'bottom-scroll'
            );


        if (
            !topScroll ||
            !bottomScroll
        ) {

            return;

        }


        let syncing = false;


        topScroll.addEventListener(
            'scroll',
            function () {

                if (syncing) return;

                syncing = true;

                bottomScroll.scrollLeft =
                    topScroll.scrollLeft;

                requestAnimationFrame(
                    function () {

                        syncing = false;

                    }
                );

            }
        );


        bottomScroll.addEventListener(
            'scroll',
            function () {

                if (syncing) return;

                syncing = true;

                topScroll.scrollLeft =
                    bottomScroll.scrollLeft;

                requestAnimationFrame(
                    function () {

                        syncing = false;

                    }
                );

            }
        );


        function syncScrollWidth() {

            const table =
                bottomScroll.querySelector(
                    'table'
                );

            const spacer =
                topScroll.firstElementChild;


            if (
                !table ||
                !spacer
            ) {

                return;

            }


            spacer.style.width =
                table.scrollWidth + 'px';

        }


        window.addEventListener(
            'load',
            syncScrollWidth
        );


        window.addEventListener(
            'resize',
            syncScrollWidth
        );


        syncScrollWidth();

    }
);

</script>


</body>

</html>
