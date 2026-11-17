
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


// ============================================================
// TIMEZONE
// ============================================================

date_default_timezone_set('Africa/Lagos');


// ============================================================
// USER / COMPANY
// ============================================================

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die('User not logged in.');
}


// Get company through the logged-in user.
// This prevents a user from viewing another company's expenses
// simply by manipulating session/company parameters.

$stmtCompany = $pdo->prepare("
    SELECT company_id
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmtCompany->execute([$user_id]);

$userCompany = $stmtCompany->fetch(PDO::FETCH_ASSOC);

if (!$userCompany) {
    die('User account not found.');
}

$company_id = (int) $userCompany['company_id'];

if ($company_id <= 0) {
    die('Invalid company.');
}


// ============================================================
// DATE FILTERS
// ============================================================

$default_from = date('Y-m-d');
$default_to   = date('Y-m-d');

$from = trim((string) ($_GET['from'] ?? $default_from));
$to   = trim((string) ($_GET['to'] ?? $default_to));


// ============================================================
// SEARCH FILTER
// ============================================================

$search = trim((string) ($_GET['search'] ?? ''));


// ============================================================
// LOCATION FILTER
// ============================================================

$location_id = trim((string) ($_GET['location_id'] ?? ''));


// ============================================================
// DATE VALIDATION
// ============================================================

function validateExpenseDate(string $date): bool
{
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    return (
        $dateObject !== false &&
        $dateObject->format('Y-m-d') === $date
    );
}


if (!validateExpenseDate($from)) {
    $from = $default_from;
}


if (!validateExpenseDate($to)) {
    $to = $default_to;
}


// If dates are reversed, automatically correct them.

if ($from > $to) {
    [$from, $to] = [$to, $from];
}


// ============================================================
// EXCLUSIVE END DATE
// ============================================================
//
// Using:
//      created_at >= from
//      created_at <  day_after_to
//
// makes the selected "to" date fully inclusive while still
// allowing a database index on created_at to be used.
//

$to_exclusive = date(
    'Y-m-d',
    strtotime($to . ' +1 day')
);


// ============================================================
// BUILD SHARED EXPENSE FILTER
// ============================================================
//
// This filter is reused for:
// - all expenses
// - approved expenses
// - analytics
// - chart data
//
// This guarantees that every part of the dashboard represents
// the exact same filtered dataset.
//

function buildExpenseFilter(
    int $companyId,
    string $fromDate,
    string $toExclusive,
    string $searchValue,
    string $locationValue,
    string $alias = 'e'
): array {

    $where = [
        "{$alias}.company_id = ?",
        "{$alias}.created_at >= ?",
        "{$alias}.created_at < ?"
    ];

    $params = [
        $companyId,
        $fromDate,
        $toExclusive
    ];


    // --------------------------------------------------------
    // LOCATION
    // --------------------------------------------------------

    if ($locationValue !== '' && ctype_digit($locationValue)) {

        $where[] = "{$alias}.location_id = ?";

        $params[] = (int) $locationValue;
    }


    // --------------------------------------------------------
    // SEARCH
    // --------------------------------------------------------

    if ($searchValue !== '') {

        $where[] = "(
            {$alias}.expense_title LIKE ?
            OR {$alias}.description LIKE ?
            OR {$alias}.type LIKE ?
            OR {$alias}.staff_responsible LIKE ?
            OR {$alias}.created_by LIKE ?
            OR CAST({$alias}.amount AS CHAR) LIKE ?
        )";

        $searchTerm = '%' . $searchValue . '%';

        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }


    return [$where, $params];
}


// ============================================================
// BUILD CURRENT FILTER
// ============================================================

[$expenseWhere, $expenseParams] = buildExpenseFilter(
    $company_id,
    $from,
    $to_exclusive,
    $search,
    $location_id,
    'e'
);

$expenseWhereSql = implode("\n AND ", $expenseWhere);


// ============================================================
// FETCH EXPENSES
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        e.*,

        l.name AS location_name,
        l.type AS location_type,
        l.city AS location_city,
        l.state AS location_state

    FROM expenses e

    LEFT JOIN locations l
        ON l.id = e.location_id

    WHERE
        {$expenseWhereSql}

    ORDER BY e.created_at DESC
");

$stmt->execute($expenseParams);

$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// FETCH APPROVED EXPENSES
// ============================================================
//
// Approved expenses use exactly the same date/search/location
// filters as the main expense list.
//

$approvedWhere = $expenseWhere;
$approvedParams = $expenseParams;

$approvedWhere[] = "e.stat = ?";
$approvedParams[] = 1;

$approvedWhereSql = implode("\n AND ", $approvedWhere);


$stmtex = $pdo->prepare("
    SELECT
        e.*,

        l.name AS location_name,
        l.type AS location_type,
        l.city AS location_city,
        l.state AS location_state

    FROM expenses e

    LEFT JOIN locations l
        ON l.id = e.location_id

    WHERE
        {$approvedWhereSql}

    ORDER BY e.created_at DESC
");

$stmtex->execute($approvedParams);

$expenses_appr = $stmtex->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// ANALYTICS
// ============================================================

$total_expenses    = 0;
$approved_expenses = 0;
$expense_count     = count($expenses);


foreach ($expenses as $exp) {

    $total_expenses += (float) ($exp['amount'] ?? 0);
}


foreach ($expenses_appr as $expr) {

    $approved_expenses += (float) ($expr['amount'] ?? 0);
}


// ============================================================
// UNAPPROVED EXPENSES
// ============================================================

$pending_expenses = $total_expenses - $approved_expenses;

if ($pending_expenses < 0) {
    $pending_expenses = 0;
}


// ============================================================
// CHART DATA
// ============================================================
//
// The original page prepared chart data but never displayed it.
// We retain the data generation here so the page remains ready
// for future chart usage.
//

$stmtChart = $pdo->prepare("
    SELECT
        DATE(e.created_at) AS day,
        COALESCE(SUM(e.amount), 0) AS total

    FROM expenses e

    WHERE
        {$expenseWhereSql}

    GROUP BY DATE(e.created_at)

    ORDER BY DATE(e.created_at)
");

$stmtChart->execute($expenseParams);

$chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// COMPANY LOCATIONS
// ============================================================

$stmtLocation = $pdo->prepare("
    SELECT
        id,
        name

    FROM locations

    WHERE company_id = ?

    ORDER BY name ASC
");

$stmtLocation->execute([$company_id]);

$locations = $stmtLocation->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// CHART ARRAYS
// ============================================================

$chartMap = [];

foreach ($chartData as $row) {

    $chartMap[$row['day']] = (float) $row['total'];
}


$chartLabels = [];
$chartValues = [];
$chartDates  = [];

$chartCursor = new DateTime($from);
$chartEnd    = new DateTime($to);


while ($chartCursor <= $chartEnd) {

    $currentChartDate = $chartCursor->format('Y-m-d');

    $chartDates[] = $currentChartDate;

    // For short periods, show day names.
    // For longer periods, show month/day.

    $fromTimestamp = strtotime($from);
    $toTimestamp   = strtotime($to);

    $selectedDays = (int) floor(
        ($toTimestamp - $fromTimestamp) / 86400
    ) + 1;


    if ($selectedDays <= 7) {

        $chartLabels[] = $chartCursor->format('D');

    } else {

        $chartLabels[] = $chartCursor->format('M d');
    }


    $chartValues[] = (float) (
        $chartMap[$currentChartDate] ?? 0
    );


    $chartCursor->modify('+1 day');
}


// ============================================================
// PAGE LABEL
// ============================================================

$isTodayOnly = (
    $from === date('Y-m-d') &&
    $to === date('Y-m-d')
);


$periodLabel = $isTodayOnly
    ? 'Today'
    : 'Selected Period';


// ============================================================
// SAFE DISPLAY VALUES
// ============================================================

$safeSearch = htmlspecialchars(
    $search,
    ENT_QUOTES,
    'UTF-8'
);

$safeFrom = htmlspecialchars(
    $from,
    ENT_QUOTES,
    'UTF-8'
);

$safeTo = htmlspecialchars(
    $to,
    ENT_QUOTES,
    'UTF-8'
);

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
        <?= htmlspecialchars($company_name) ?> | Expenses
    </title>


    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >


    <link
        href="style.css"
        rel="stylesheet"
    >


    <!-- ========================================================
         FUTURISTIC LIGHT GLASS UI
    ========================================================= -->

    <style>

        :root {
            --glass-bg: rgba(255, 255, 255, 0.78);
            --glass-strong: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(15, 23, 42, 0.08);

            --text-main: #172033;
            --text-secondary: #596579;
            --text-muted: #788396;

            --accent: #3157d5;
            --accent-dark: #203ea6;

            --success: #087443;
            --danger: #b42318;

            --shadow:
                0 18px 50px rgba(15, 23, 42, 0.08);

            --shadow-hover:
                0 25px 65px rgba(15, 23, 42, 0.13);
        }


        /* =====================================================
           PAGE BACKGROUND
        ===================================================== */

        body {
            background:
                radial-gradient(
                    circle at 8% 8%,
                    rgba(49, 87, 213, 0.08),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 92% 18%,
                    rgba(15, 118, 110, 0.06),
                    transparent 26%
                ),
                #f5f7fb;

            color: var(--text-main);
        }


        /* =====================================================
           MAIN CONTENT
        ===================================================== */

        .expense-page {
            position: relative;
            min-height: 100%;
        }


        /* =====================================================
           FUTURISTIC HERO
        ===================================================== */

        .expense-hero {
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
            padding: 26px;
            border-radius: 24px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.94),
                    rgba(247,249,255,.78)
                );

            border: 1px solid rgba(49,87,213,.10);

            box-shadow: var(--shadow);

            isolation: isolate;
        }


        .expense-hero::before {
            content: "";

            position: absolute;

            width: 300px;
            height: 300px;

            right: -90px;
            top: -150px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(49,87,213,.16),
                    rgba(49,87,213,0)
                );

            z-index: -1;
        }


        .expense-hero::after {
            content: "";

            position: absolute;

            width: 220px;
            height: 220px;

            left: 45%;
            bottom: -170px;

            border-radius: 50%;

            border: 1px solid rgba(49,87,213,.08);

            box-shadow:
                0 0 0 30px rgba(49,87,213,.025),
                0 0 0 60px rgba(49,87,213,.018);

            z-index: -1;
        }


        .hero-content {
            position: relative;

            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 24px;

            z-index: 2;
        }


        .hero-title-wrap {
            display: flex;

            align-items: center;

            gap: 16px;
        }


        .hero-icon {
            width: 54px;
            height: 54px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 17px;

            background:
                linear-gradient(
                    145deg,
                    rgba(49,87,213,.12),
                    rgba(49,87,213,.04)
                );

            border: 1px solid rgba(49,87,213,.12);

            color: var(--accent);

            font-size: 22px;

            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.9),
                0 10px 25px rgba(49,87,213,.08);
        }


        .hero-title {
            margin: 0;

            font-size: 25px;

            font-weight: 800;

            letter-spacing: -.02em;

            color: var(--text-main);
        }


        .hero-subtitle {
            margin: 5px 0 0;

            color: var(--text-secondary);

            font-size: 14px;

            line-height: 1.6;
        }


        .hero-badge {
            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 9px 13px;

            border-radius: 999px;

            background: rgba(255,255,255,.76);

            border: 1px solid rgba(15,23,42,.07);

            color: var(--text-secondary);

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;
        }


        .hero-badge i {
            color: var(--accent);
        }


        /* =====================================================
           CREATE EXPENSE BUTTON
        ===================================================== */

        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
        }

        .create-expense-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 11px 17px;
            border: 1px solid rgba(49,87,213,.18);
            border-radius: 13px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 10px 22px rgba(49,87,213,.18);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .create-expense-button:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(49,87,213,.25);
        }

        .create-expense-button:focus-visible {
            outline: 3px solid rgba(49,87,213,.25);
            outline-offset: 3px;
        }

        @media (max-width: 850px) {
            .hero-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 480px) {
            .create-expense-button {
                width: 100%;
            }
        }


        /* =====================================================
           ANALYTICS
        ===================================================== */

        .analytics-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 22px;
        }


        .analytics-card {
            position: relative;

            overflow: hidden;

            padding: 21px;

            border-radius: 20px;

            background: var(--glass-bg);

            border: 1px solid var(--glass-border);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            box-shadow: var(--shadow);

            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }


        .analytics-card:hover {
            transform: translateY(-3px);

            box-shadow: var(--shadow-hover);
        }


        .analytics-card::after {
            content: "";

            position: absolute;

            width: 110px;
            height: 110px;

            right: -45px;
            bottom: -55px;

            border-radius: 50%;

            background: rgba(49,87,213,.055);
        }


        .metric-icon {
            width: 42px;
            height: 42px;

            display: flex;

            align-items: center;
            justify-content: center;

            margin-bottom: 15px;

            border-radius: 13px;

            background: rgba(49,87,213,.08);

            color: var(--accent);

            font-size: 17px;
        }


        .metric-label {
            display: block;

            margin-bottom: 7px;

            color: var(--text-secondary);

            font-size: 13px;

            font-weight: 700;

            letter-spacing: .01em;
        }


        .metric-value {
            margin: 0;

            color: var(--text-main);

            font-size: 24px;

            font-weight: 800;

            letter-spacing: -.02em;
        }


        /* =====================================================
           FILTER GLASS
        ===================================================== */

        .filter-card {
            position: relative;

            margin-bottom: 22px;

            padding: 20px;

            border-radius: 20px;

            background: var(--glass-bg);

            border: 1px solid var(--glass-border);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            box-shadow: var(--shadow);
        }


        .filter-header {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 16px;
        }


        .filter-header i {
            color: var(--accent);

            font-size: 15px;
        }


        .filter-title {
            margin: 0;

            color: var(--text-main);

            font-size: 15px;

            font-weight: 800;
        }


        .filter-grid {
            display: grid;

            grid-template-columns:
                minmax(180px, 1.4fr)
                minmax(150px, 1fr)
                minmax(150px, 1fr)
                minmax(180px, 1fr);

            gap: 12px;
        }


        .filter-field {
            position: relative;
        }


        .filter-field i {
            position: absolute;

            left: 14px;
            top: 50%;

            transform: translateY(-50%);

            color: #718096;

            font-size: 13px;

            pointer-events: none;

            z-index: 2;
        }


        .filter-input,
        .filter-select {
            width: 100%;

            min-height: 46px;

            padding:
                11px 14px 11px 40px;

            border-radius: 13px;

            border: 1px solid rgba(15,23,42,.10);

            background: rgba(255,255,255,.82);

            color: var(--text-main);

            outline: none;

            font-size: 13px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }


        .filter-input::placeholder {
            color: #8a94a6;
        }


        .filter-input:focus,
        .filter-select:focus {
            border-color: rgba(49,87,213,.35);

            background: #fff;

            box-shadow:
                0 0 0 4px rgba(49,87,213,.07);
        }


        /* =====================================================
           EXPENSES CONTAINER
        ===================================================== */

        .expenses-container {
            border-radius: 22px;

            background: rgba(255,255,255,.68);

            border: 1px solid var(--glass-border);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        .expenses-header {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 20px 22px;

            border-bottom: 1px solid rgba(15,23,42,.07);

            background: rgba(255,255,255,.58);
        }


        .expenses-heading {
            display: flex;

            align-items: center;

            gap: 11px;
        }


        .expenses-heading-icon {
            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: rgba(49,87,213,.08);

            color: var(--accent);
        }


        .expenses-heading h3 {
            margin: 0;

            color: var(--text-main);

            font-size: 17px;

            font-weight: 800;
        }


        .record-count {
            padding: 7px 11px;

            border-radius: 999px;

            background: rgba(15,23,42,.045);

            color: var(--text-secondary);

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;
        }


        .expenses-list {
            padding: 18px;
        }


        /* =====================================================
           EXPENSE CARD
        ===================================================== */

        .expense-card {
            position: relative;

            display: flex;

            align-items: stretch;
            justify-content: space-between;

            gap: 22px;

            padding: 21px;

            margin-bottom: 14px;

            border-radius: 19px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.90),
                    rgba(248,250,253,.74)
                );

            border: 1px solid rgba(15,23,42,.075);

            box-shadow:
                0 10px 28px rgba(15,23,42,.045);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }


        .expense-card:last-child {
            margin-bottom: 0;
        }


        .expense-card:hover {
            transform: translateY(-2px);

            border-color: rgba(49,87,213,.15);

            box-shadow:
                0 18px 40px rgba(15,23,42,.08);
        }


        .expense-main {
            flex: 1;

            min-width: 0;
        }


        .expense-title-row {
            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 10px;

            margin-bottom: 16px;
        }


        .expense-title {
            margin: 0;

            color: var(--text-main);

            font-size: 17px;

            font-weight: 800;

            line-height: 1.4;
        }


        .expense-type {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .04em;

            text-transform: uppercase;
        }


        .expense-type.capital {
            background: rgba(49,87,213,.09);

            color: #2948aa;
        }


        .expense-type.operational {
            background: rgba(8,116,67,.09);

            color: #087443;
        }


        .expense-details {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 11px;
        }


        .detail-item {
            display: flex;

            align-items: flex-start;

            gap: 9px;

            min-width: 0;

            padding: 10px;

            border-radius: 12px;

            background: rgba(255,255,255,.55);

            border: 1px solid rgba(15,23,42,.045);
        }


        .detail-icon {
            flex-shrink: 0;

            width: 28px;
            height: 28px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: rgba(15,23,42,.045);

            color: #667085;

            font-size: 11px;
        }


        .detail-content {
            min-width: 0;
        }


        .detail-label {
            display: block;

            margin-bottom: 2px;

            color: #8a94a6;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .04em;
        }


        .detail-value {
            display: block;

            color: #334155;

            font-size: 12px;

            font-weight: 600;

            line-height: 1.5;

            word-break: break-word;
        }


        .amount-value {
            color: var(--success);

            font-size: 14px;

            font-weight: 800;
        }


        /* =====================================================
           ACTION COLUMN
        ===================================================== */

        .expense-actions {
            width: 150px;

            flex-shrink: 0;

            display: flex;

            flex-direction: column;

            justify-content: center;

            gap: 9px;

            padding-left: 20px;

            border-left: 1px solid rgba(15,23,42,.07);
        }


        .action-button {
            width: 100%;

            min-height: 40px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 8px;

            padding: 9px 12px;

            border-radius: 11px;

            border: 1px solid transparent;

            font-size: 12px;

            font-weight: 800;

            text-decoration: none;

            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }


        .action-button:hover {
            transform: translateY(-1px);
        }


        .receipt-button {
            color: #2948aa;

            background: rgba(49,87,213,.08);

            border-color: rgba(49,87,213,.10);
        }


        .receipt-button:hover {
            background: rgba(49,87,213,.14);

            box-shadow:
                0 8px 20px rgba(49,87,213,.10);
        }


        .delete-button {
            color: #a5231c;

            background: rgba(180,35,24,.065);

            border-color: rgba(180,35,24,.09);
        }


        .delete-button:hover {
            background: rgba(180,35,24,.11);

            box-shadow:
                0 8px 20px rgba(180,35,24,.08);
        }


        .approve-button {
            color: #087443;

            background: rgba(8,116,67,.07);

            border-color: rgba(8,116,67,.09);
        }


        .approve-button:hover {
            background: rgba(8,116,67,.12);

            box-shadow:
                0 8px 20px rgba(8,116,67,.08);
        }


        .approved-state {
            display: flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            width: 100%;

            padding: 10px;

            border-radius: 11px;

            background: rgba(8,116,67,.065);

            color: #087443;

            border: 1px solid rgba(8,116,67,.08);

            font-size: 11px;

            font-weight: 700;

            text-align: center;
        }


        .no-receipt {
            display: flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            min-height: 40px;

            color: #8a94a6;

            font-size: 11px;

            font-weight: 600;
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            padding: 55px 20px;

            text-align: center;

            color: var(--text-secondary);
        }


        .empty-icon {
            width: 58px;
            height: 58px;

            display: flex;

            align-items: center;
            justify-content: center;

            margin: 0 auto 15px;

            border-radius: 17px;

            background: rgba(49,87,213,.07);

            color: var(--accent);

            font-size: 22px;
        }


        .empty-state h4 {
            margin: 0 0 5px;

            color: var(--text-main);

            font-size: 16px;

            font-weight: 800;
        }


        .empty-state p {
            margin: 0;

            font-size: 13px;

            color: var(--text-muted);
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .filter-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .expense-details {
                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width: 850px) {

            .analytics-grid {
                grid-template-columns:
                    1fr;
            }


            .hero-content {
                align-items: flex-start;

                flex-direction: column;
            }


            .hero-badge {
                align-self: flex-start;
            }


            .expense-card {
                flex-direction: column;
            }


            .expense-actions {
                width: 100%;

                padding-left: 0;

                padding-top: 15px;

                border-left: 0;

                border-top: 1px solid rgba(15,23,42,.07);

                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 640px) {

            .expense-hero {
                padding: 20px;

                border-radius: 19px;
            }


            .hero-icon {
                width: 46px;
                height: 46px;

                border-radius: 14px;
            }


            .hero-title {
                font-size: 21px;
            }


            .hero-subtitle {
                font-size: 13px;
            }


            .filter-card {
                padding: 15px;

                border-radius: 17px;
            }


            .filter-grid {
                grid-template-columns:
                    1fr;
            }


            .expenses-header {
                align-items: flex-start;

                flex-direction: column;

                padding: 17px;
            }


            .expenses-list {
                padding: 12px;
            }


            .expense-card {
                padding: 15px;

                border-radius: 16px;
            }


            .expense-title {
                font-size: 16px;
            }


            .expense-actions {
                grid-template-columns:
                    1fr;
            }


            .detail-item {
                padding: 9px;
            }


            .metric-value {
                font-size: 22px;
            }

        }


        @media (max-width: 420px) {

            .hero-title-wrap {
                align-items: flex-start;
            }


            .hero-title {
                font-size: 19px;
            }


            .expense-details {
                gap: 8px;
            }


            .detail-value {
                font-size: 11px;
            }

        }
        /* APPLY FILTER BUTTON */
.filter-submit {
    width: 100%;
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 11px 16px;
    border: 1px solid rgba(49, 87, 213, .18);
    border-radius: 13px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 10px 22px rgba(49, 87, 213, .14);
    transition: transform .2s ease, box-shadow .2s ease;
}

.filter-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(49, 87, 213, .22);
}

.filter-submit:focus-visible {
    outline: 3px solid rgba(49, 87, 213, .25);
    outline-offset: 3px;
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

        $watch('darkMode', value =>
            localStorage.setItem(
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


    <?php include('sidebar.php'); ?>


    <!-- ========================================================
         CONTENT AREA
    ========================================================= -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <main>

            <?php include('header.php'); ?>


            <div class="expense-page">

                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">


                    <!-- ====================================================
                         HERO
                    ==================================================== -->

                    <section class="expense-hero">

                        <div class="hero-content">


                            <div class="hero-title-wrap">

                                <div class="hero-icon">

                                    <i class="fa-solid fa-chart-line"></i>

                                </div>


                                <div>

                                    <h1 class="hero-title">

                                        Expense Intelligence

                                    </h1>


                                    <p class="hero-subtitle">

                                        Monitor company spending,
                                        approvals and financial activity.

                                    </p>

                                </div>

                            </div>


                            <div class="hero-actions">

                                <div class="hero-badge">

                                    <i class="fa-solid fa-calendar-day"></i>

                                    <?= $safeFrom ?>

                                    <span>→</span>

                                    <?= $safeTo ?>

                                </div>

                                <!-- Create Expense -->
                                <a
                                    href="record-expenses.php"
                                    class="create-expense-button"
                                    aria-label="Create a new expense"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Create Expense</span>
                                </a>

                            </div>


                        </div>

                    </section>


                    <!-- ====================================================
                         ANALYTICS
                    ==================================================== -->

                    <section class="analytics-grid">


                        <!-- APPROVED -->

                        <div class="analytics-card">

                            <div class="metric-icon">

                                <i class="fa-solid fa-circle-check"></i>

                            </div>


                            <span class="metric-label">

                                Approved Expenses

                            </span>


                            <h3 class="metric-value">

                                ₦<?= number_format(
                                    $approved_expenses,
                                    2
                                ) ?>

                            </h3>

                        </div>


                        <!-- COUNT -->

                        <div class="analytics-card">

                            <div class="metric-icon">

                                <i class="fa-solid fa-receipt"></i>

                            </div>


                            <span class="metric-label">

                                Number of Expenses

                            </span>


                            <h3 class="metric-value">

                                <?= number_format(
                                    $expense_count
                                ) ?>

                            </h3>

                        </div>


                        <!-- TOTAL -->

                        <div class="analytics-card">

                            <div class="metric-icon">

                                <i class="fa-solid fa-wallet"></i>

                            </div>


                            <span class="metric-label">

                                Total Expenses

                            </span>


                            <h3 class="metric-value">

                                ₦<?= number_format(
                                    $total_expenses,
                                    2
                                ) ?>

                            </h3>

                        </div>


                    </section>


                    <!-- ====================================================
                         FILTER
                    ==================================================== -->

                    <section class="filter-card">


                        <div class="filter-header">

                            <i class="fa-solid fa-sliders"></i>


                            <h3 class="filter-title">

                                Refine Expenses

                            </h3>

                        </div>


                        <form
                            id="filterForm"
                            class="filter-grid"
                            method="GET"
                        >


                            <!-- SEARCH -->

                            <div class="filter-field">

                                <i class="fa-solid fa-magnifying-glass"></i>


                                <input
                                    type="text"
                                    name="search"
                                    value="<?= $safeSearch ?>"
                                    placeholder="Search expenses..."
                                    class="filter-input"
                                    autocomplete="off"
                                >

                            </div>


                            <!-- FROM -->

                            <div class="filter-field">

                                <i class="fa-regular fa-calendar"></i>


                                <input
                                    type="date"
                                    name="from"
                                    value="<?= $safeFrom ?>"
                                    class="filter-input"
                                >

                            </div>


                            <!-- TO -->

                            <div class="filter-field">

                                <i class="fa-regular fa-calendar-check"></i>


                                <input
                                    type="date"
                                    name="to"
                                    value="<?= $safeTo ?>"
                                    class="filter-input"
                                >

                            </div>


                            <!-- LOCATION -->

                            <div class="filter-field">

                                <i class="fa-solid fa-location-dot"></i>


                                <select
                                    name="location_id"
                                    class="filter-select"
                                >

                                    <option value="">

                                        All Locations

                                    </option>


                                    <?php foreach ($locations as $loc): ?>

                                        <?php
                                        $locId = (string) $loc['id'];

                                        $selected =
                                            ($location_id === $locId)
                                                ? 'selected'
                                                : '';
                                        ?>


                                        <option
                                            value="<?= htmlspecialchars(
                                                $locId,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            <?= $selected ?>
                                        >

                                            <?= htmlspecialchars(
                                                $loc['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </option>


                                    <?php endforeach; ?>


                                </select>

                            </div>
                                    <!-- APPLY FILTERS -->
<div class="filter-field">
    <button type="submit" class="filter-submit">
        <i class="fa-solid fa-filter"></i>
        <span>Apply Filters</span>
    </button>
</div>

                        </form>

                    </section>


                    <!-- ====================================================
                         EXPENSES
                    ==================================================== -->

                    <section class="expenses-container">


                        <!-- HEADER -->

                        <div class="expenses-header">


                            <div class="expenses-heading">

                                <div class="expenses-heading-icon">

                                    <i class="fa-solid fa-list-check"></i>

                                </div>


                                <h3>

                                    All Expenses

                                </h3>

                            </div>


                            <span
                                class="record-count"
                                id="expenseRecordCount"
                            >

                                <?= number_format(
                                    $expense_count
                                ) ?>

                                Records

                            </span>


                        </div>


                        <!-- LIST -->

                        <div class="expenses-list">

                            <div id="expensesResults">


                                <?php if (empty($expenses)): ?>


                                    <div class="empty-state">


                                        <div class="empty-icon">

                                            <i class="fa-solid fa-receipt"></i>

                                        </div>


                                        <h4>

                                            No expenses found

                                        </h4>


                                        <p>

                                            There are no expenses matching
                                            the selected filters.

                                        </p>


                                    </div>


                                <?php else: ?>


                                    <?php foreach ($expenses as $exp): ?>


                                        <?php

                                        // ------------------------------------------------
                                        // EXPENSE TYPE
                                        // ------------------------------------------------

                                        $expenseType =
                                            strtolower(
                                                trim(
                                                    (string) (
                                                        $exp['type'] ?? ''
                                                    )
                                                )
                                            );


                                        $isCapital =
                                            $expenseType === 'capital';


                                        // ------------------------------------------------
                                        // RECEIPT
                                        // ------------------------------------------------

                                        $receiptFile =
                                            trim(
                                                (string) (
                                                    $exp['receipt'] ?? ''
                                                )
                                            );


                                        $receiptPath =
                                            '../uploads/receipts/' .
                                            $receiptFile;


                                        // ------------------------------------------------
                                        // SAFE VALUES
                                        // ------------------------------------------------

                                        $expenseTitle =
                                            $exp['expense_title'] ??
                                            'Untitled Expense';


                                        $expenseTypeDisplay =
                                            $exp['type'] ??
                                            'Operational';


                                        $locationName =
                                            $exp['location_name'] ??
                                            'N/A';


                                        $staffResponsible =
                                            $exp['staff_responsible'] ??
                                            'N/A';


                                        $createdBy =
                                            $exp['created_by'] ??
                                            'N/A';


                                        $createdAt =
                                            $exp['created_at'] ??
                                            'N/A';


                                        $description =
                                            trim(
                                                (string) (
                                                    $exp['description'] ?? ''
                                                )
                                            );


                                        ?>


                                        <!-- ====================================================
                                             EXPENSE CARD
                                        ==================================================== -->

                                        <article class="expense-card">


                                            <!-- ================================================
                                                 MAIN INFORMATION
                                            ================================================= -->

                                            <div class="expense-main">


                                                <div class="expense-title-row">


                                                    <h4 class="expense-title">

                                                        <?= htmlspecialchars(
                                                            $expenseTitle,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </h4>


                                                    <span
                                                        class="expense-type
                                                        <?= $isCapital
                                                            ? 'capital'
                                                            : 'operational'
                                                        ?>"
                                                    >

                                                        <i class="fa-solid fa-circle"></i>


                                                        <?= htmlspecialchars(
                                                            $expenseTypeDisplay,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </span>


                                                </div>


                                                <div class="expense-details">


                                                    <!-- AMOUNT -->

                                                    <div class="detail-item">

                                                        <div class="detail-icon">

                                                            <i class="fa-solid fa-naira-sign"></i>

                                                        </div>


                                                        <div class="detail-content">

                                                            <span class="detail-label">

                                                                Amount

                                                            </span>


                                                            <span class="detail-value amount-value">

                                                                ₦<?= number_format(
                                                                    (float) (
                                                                        $exp['amount'] ?? 0
                                                                    ),
                                                                    2
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    </div>


                                                    <!-- LOCATION -->

                                                    <div class="detail-item">

                                                        <div class="detail-icon">

                                                            <i class="fa-solid fa-location-dot"></i>

                                                        </div>


                                                        <div class="detail-content">

                                                            <span class="detail-label">

                                                                Location

                                                            </span>


                                                            <span class="detail-value">

                                                                <?= htmlspecialchars(
                                                                    $locationName,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>


                                                                <?php if (
                                                                    !empty(
                                                                        $exp['location_type']
                                                                    )
                                                                ): ?>

                                                                    ·

                                                                    <?= htmlspecialchars(
                                                                        $exp['location_type'],
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>

                                                                <?php endif; ?>


                                                            </span>

                                                        </div>

                                                    </div>


                                                    <!-- STAFF -->

                                                    <div class="detail-item">

                                                        <div class="detail-icon">

                                                            <i class="fa-solid fa-user"></i>

                                                        </div>


                                                        <div class="detail-content">

                                                            <span class="detail-label">

                                                                Responsible

                                                            </span>


                                                            <span class="detail-value">

                                                                <?= htmlspecialchars(
                                                                    $staffResponsible,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    </div>


                                                    <!-- DATE -->

                                                    <div class="detail-item">

                                                        <div class="detail-icon">

                                                            <i class="fa-regular fa-clock"></i>

                                                        </div>


                                                        <div class="detail-content">

                                                            <span class="detail-label">

                                                                Registered

                                                            </span>


                                                            <span class="detail-value">

                                                                <?= htmlspecialchars(
                                                                    $createdAt,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    </div>


                                                    <!-- CREATED BY -->

                                                    <div class="detail-item">

                                                        <div class="detail-icon">

                                                            <i class="fa-solid fa-user-pen"></i>

                                                        </div>


                                                        <div class="detail-content">

                                                            <span class="detail-label">

                                                                Registered By

                                                            </span>


                                                            <span class="detail-value">

                                                                <?= htmlspecialchars(
                                                                    $createdBy,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>

                                                            </span>

                                                        </div>

                                                    </div>


                                                    <!-- DESCRIPTION -->

                                                    <div class="detail-item">

                                                        <div class="detail-icon">

                                                            <i class="fa-solid fa-align-left"></i>

                                                        </div>


                                                        <div class="detail-content">

                                                            <span class="detail-label">

                                                                Description

                                                            </span>


                                                            <span class="detail-value">

                                                                <?= $description !== ''
                                                                    ? htmlspecialchars(
                                                                        $description,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    )
                                                                    : 'No description provided'
                                                                ?>

                                                            </span>

                                                        </div>

                                                    </div>


                                                </div>


                                            </div>


                                            <!-- ================================================
                                                 ACTIONS
                                            ================================================= -->

                                            <div class="expense-actions">


                                                <!-- RECEIPT -->

                                                <?php if (
                                                    $receiptFile !== '' &&
                                                    file_exists($receiptPath)
                                                ): ?>


                                                    <a
                                                        href="<?= htmlspecialchars(
                                                            $receiptPath,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="action-button receipt-button"
                                                    >

                                                        <i class="fa-solid fa-file-invoice"></i>

                                                        View Receipt

                                                    </a>


                                                <?php else: ?>


                                                    <div class="no-receipt">

                                                        <i class="fa-regular fa-file"></i>

                                                        No receipt

                                                    </div>


                                                <?php endif; ?>


                                                <!-- DELETE -->

                                                <form
                                                    method="POST"
                                                    action="delete-expenses.php"
                                                    onsubmit="
                                                        return confirm(
                                                            'Delete this expense?'
                                                        );
                                                    "
                                                    style="margin:0;"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="expense_id"
                                                        value="<?= htmlspecialchars(
                                                            (string) (
                                                                $exp['id'] ?? ''
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="action-button delete-button"
                                                    >

                                                        <i class="fa-solid fa-trash-can"></i>

                                                        Delete

                                                    </button>

                                                </form>


                                                <!-- APPROVAL -->

                                                <?php

                                                $approvedBy =
                                                    trim(
                                                        (string) (
                                                            $exp['approved_by']
                                                            ?? ''
                                                        )
                                                    );


                                                $isApproved =
                                                    $approvedBy !== '' &&
                                                    $approvedBy !== 'Not Approved!';

                                                ?>


                                                <?php if (!$isApproved): ?>


                                                    <form
                                                        method="POST"
                                                        action="approve_expense.php"
                                                        style="margin:0;"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="expense_id"
                                                            value="<?= htmlspecialchars(
                                                                (string) (
                                                                    $exp['id']
                                                                    ?? ''
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="action-button approve-button"
                                                        >

                                                            <i class="fa-solid fa-check"></i>

                                                            Approve

                                                        </button>

                                                    </form>


                                                <?php else: ?>


                                                    <div class="approved-state">

                                                        <i class="fa-solid fa-circle-check"></i>


                                                        <span>

                                                            Approved by:

                                                            <?= htmlspecialchars(
                                                                $approvedBy,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>

                                                        </span>

                                                    </div>


                                                <?php endif; ?>


                                            </div>


                                        </article>


                                    <?php endforeach; ?>


                                <?php endif; ?>


                            </div>

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
     EXISTING BUNDLE
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
     EXPENSE FILTER — MANUAL APPLY
     Date fields never trigger premature page reloads.
============================================================ -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filterForm');

    if (!form) {
        return;
    }

    const searchInput = form.querySelector('input[name="search"]');
    const fromInput = form.querySelector('input[name="from"]');
    const toInput = form.querySelector('input[name="to"]');
    const locationInput = form.querySelector('select[name="location_id"]');

    // Do not submit automatically when a date, location, or
    // search value changes. The user applies filters explicitly.
    form.addEventListener('submit', function (event) {
        const from = fromInput ? fromInput.value : '';
        const to = toInput ? toInput.value : '';

        if (!from || !to) {
            event.preventDefault();

            alert('Please select both the start date and end date.');

            if (!from && fromInput) {
                fromInput.focus();
            } else if (toInput) {
                toInput.focus();
            }

            return;
        }

        // HTML date values use YYYY-MM-DD, so string comparison
        // correctly checks chronological order.
        if (from > to) {
            event.preventDefault();

            alert('The end date cannot be earlier than the start date.');

            if (toInput) {
                toInput.focus();
            }

            return;
        }
    });

    // Prevent Enter in the search field from bypassing validation
    // or causing unexpected submission behavior.
    if (searchInput) {
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                form.requestSubmit();
            }
        });
    }
});
</script>
</body>

</html>
