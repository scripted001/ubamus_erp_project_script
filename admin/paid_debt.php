

<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

/*
|--------------------------------------------------------------------------
| BRANCH MANAGER AUTHORIZATION
|--------------------------------------------------------------------------
| The branch scope is verified against the authenticated database user.
| Company/location values supplied by the browser are never trusted.
*/

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    exit('User not logged in.');
}

$authStmt = $pdo->prepare("
    SELECT company_id, location_id, role, status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$authStmt->execute([$user_id]);
$loggedInUser = $authStmt->fetch(PDO::FETCH_ASSOC);

if (
    !$loggedInUser
    || strtolower(trim((string)($loggedInUser['role'] ?? ''))) !== 'branch manager'
    || strtolower(trim((string)($loggedInUser['status'] ?? ''))) !== 'active'
    || (int)($loggedInUser['company_id'] ?? 0) <= 0
    || (int)($loggedInUser['location_id'] ?? 0) <= 0
    || (int)($_SESSION['company_id'] ?? 0) !== (int)$loggedInUser['company_id']
    || (int)($_SESSION['location_id'] ?? 0) !== (int)$loggedInUser['location_id']
) {
    http_response_code(403);
    exit('Access denied. An active Branch Manager account and assigned branch are required.');
}

$company_id = (int)$loggedInUser['company_id'];
$location_id = (int)$loggedInUser['location_id'];
$user_email = $_SESSION['user_email'] ?? '';

$branchCheck = $pdo->prepare("
    SELECT id
    FROM locations
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");
$branchCheck->execute([$location_id, $company_id]);

if (!$branchCheck->fetchColumn()) {
    http_response_code(403);
    exit('Access denied. Your assigned branch is inactive or unavailable.');
}

/*
|--------------------------------------------------------------------------
| DATE FILTERS
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

$from = $_GET['from'] ?? $today;
$to   = $_GET['to'] ?? $today;

/*
|--------------------------------------------------------------------------
| VALIDATE DATES
|--------------------------------------------------------------------------
*/

$validDate = static function ($value): bool {
    if (!is_string($value)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date !== false
        && $date->format('Y-m-d') === $value;
};

if (!$validDate($from)) {
    $from = $today;
}

if (!$validDate($to)) {
    $to = $today;
}

if ($from > $to) {
    [$from, $to] = [$to, $from];
}

/*
|--------------------------------------------------------------------------
| DATE RANGE BOUNDARIES
|--------------------------------------------------------------------------
*/

$startDate = $from . ' 00:00:00';

$endDate = (new DateTime($to))
    ->modify('+1 day')
    ->format('Y-m-d') . ' 00:00:00';

/*
|--------------------------------------------------------------------------
| FETCH FILTERED PAID DEBTS
|--------------------------------------------------------------------------
| Supports both stored formats:
| YYYY-MM-DD HH:MM:SS
| YYYY:MM:DD HH:MM:SS
|
| Restricts results to the current company and active location.
|--------------------------------------------------------------------------
*/

$customer_debts = [];

if ($company_id > 0 && $location_id > 0) {

    $stmt = $pdo->prepare("
        SELECT
            pd.*
        FROM paid_dept pd
        WHERE pd.company_id = :company_id
          AND pd.location_id = :location_id
          AND COALESCE(
                STR_TO_DATE(
                    REPLACE(
                        LEFT(pd.pdcreated_at, 19),
                        ':',
                        '-'
                    ),
                    '%Y-%m-%d %H:%i:%s'
                ),
                STR_TO_DATE(
                    REPLACE(
                        LEFT(pd.pdcreated_at, 10),
                        ':',
                        '-'
                    ),
                    '%Y-%m-%d'
                )
              ) >= :start_date
          AND COALESCE(
                STR_TO_DATE(
                    REPLACE(
                        LEFT(pd.pdcreated_at, 19),
                        ':',
                        '-'
                    ),
                    '%Y-%m-%d %H:%i:%s'
                ),
                STR_TO_DATE(
                    REPLACE(
                        LEFT(pd.pdcreated_at, 10),
                        ':',
                        '-'
                    ),
                    '%Y-%m-%d'
                )
              ) < :end_date
        ORDER BY pd.id DESC
    ");

    $stmt->execute([
        ':company_id'  => $company_id,
        ':location_id' => $location_id,
        ':start_date'  => $startDate,
        ':end_date'    => $endDate
    ]);

    $customer_debts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| KPIs — MATCH THE FILTERED RECORDS
|--------------------------------------------------------------------------
*/

$debt_count = count($customer_debts);

$total_paid_amount = 0.00;

foreach ($customer_debts as $debt) {
    $total_paid_amount += (float)($debt['pdbalance'] ?? 0);
}
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
    <?= htmlspecialchars((string)$company_name, ENT_QUOTES, 'UTF-8') ?> | Paid Debt Report
</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">

<style>

    /* =========================================================
       LIGHT FUTURISTIC GLASS UI
    ========================================================= */

    :root {
        --glass-bg: rgba(255, 255, 255, 0.72);
        --glass-bg-strong: rgba(255, 255, 255, 0.88);
        --glass-border: rgba(15, 23, 42, 0.08);

        --text-main: #172033;
        --text-secondary: #596579;
        --text-muted: #7b8798;

        --accent: #315efb;
        --accent-dark: #2147d6;

        --success: #087443;
        --success-bg: rgba(16, 185, 129, 0.10);

        --shadow:
            0 18px 50px rgba(31, 41, 55, 0.08);

        --shadow-soft:
            0 8px 30px rgba(31, 41, 55, 0.06);
    }

    * {
        box-sizing: border-box;
    }

    body {
        background:
            radial-gradient(
                circle at 10% 5%,
                rgba(49, 94, 251, 0.07),
                transparent 28%
            ),
            radial-gradient(
                circle at 90% 10%,
                rgba(14, 165, 233, 0.06),
                transparent 25%
            ),
            #f6f8fc !important;

        color: var(--text-main);
    }

    /* =========================================================
       PAGE HEADER
    ========================================================= */

    .paid-page-header {
        position: relative;
        overflow: hidden;

        padding: 28px;
        margin-bottom: 22px;

        border: 1px solid var(--glass-border);
        border-radius: 24px;

        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,.88),
                rgba(255,255,255,.62)
            );

        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);

        box-shadow: var(--shadow-soft);
    }

    .paid-page-header::before {
        content: "";

        position: absolute;

        width: 220px;
        height: 220px;

        right: -90px;
        top: -110px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(49,94,251,.14),
                transparent 68%
            );

        pointer-events: none;
    }

    .paid-page-header::after {
        content: "";

        position: absolute;

        width: 150px;
        height: 150px;

        right: 120px;
        bottom: -100px;

        border-radius: 50%;

        border: 1px solid rgba(49,94,251,.10);

        pointer-events: none;
    }

    .paid-header-content {
        position: relative;
        z-index: 2;
    }

    .paid-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;

        margin-bottom: 8px;

        font-size: 11px;
        font-weight: 800;

        letter-spacing: .13em;
        text-transform: uppercase;

        color: var(--accent);
    }

    .paid-title {
        margin: 0;

        font-size: clamp(24px, 3vw, 34px);
        line-height: 1.15;

        font-weight: 800;

        color: #172033;
    }

    .paid-subtitle {
        margin-top: 9px;

        max-width: 700px;

        font-size: 14px;
        line-height: 1.7;

        color: var(--text-secondary);
    }

    .paid-breadcrumb {
        display: flex;
        align-items: center;
        gap: 7px;

        margin-top: 18px;

        font-size: 13px;
        color: var(--text-muted);
    }

    .paid-breadcrumb a {
        color: var(--accent);
        font-weight: 600;
        text-decoration: none;
    }

    /* =========================================================
       ANALYTICS
    ========================================================= */

    .paid-stat-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 16px;

        margin-bottom: 22px;
    }

    .paid-stat-card {
        position: relative;
        overflow: hidden;

        padding: 20px;

        border-radius: 20px;

        border: 1px solid var(--glass-border);

        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.88),
                rgba(255,255,255,.60)
            );

        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);

        box-shadow: var(--shadow-soft);

        transition:
            transform .25s ease,
            box-shadow .25s ease;
    }

    .paid-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .paid-stat-card::after {
        content: "";

        position: absolute;

        width: 100px;
        height: 100px;

        right: -45px;
        top: -45px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(49,94,251,.10),
                transparent 70%
            );
    }

    .paid-stat-icon {
        width: 42px;
        height: 42px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 13px;

        background: rgba(49,94,251,.08);

        color: var(--accent);

        font-size: 17px;

        margin-bottom: 14px;
    }

    .paid-stat-label {
        display: block;

        font-size: 12px;
        font-weight: 700;

        color: var(--text-secondary);

        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .paid-stat-value {
        margin-top: 6px;

        font-size: clamp(24px, 3vw, 30px);

        font-weight: 800;

        color: #172033;
    }

    /* =========================================================
       FILTER
    ========================================================= */

    .paid-filter-card {
        padding: 20px;

        margin-bottom: 22px;

        border: 1px solid var(--glass-border);
        border-radius: 20px;

        background: var(--glass-bg);

        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);

        box-shadow: var(--shadow-soft);
    }

    .paid-filter-heading {
        display: flex;
        align-items: center;
        gap: 10px;

        margin-bottom: 16px;

        font-size: 15px;
        font-weight: 800;

        color: var(--text-main);
    }

    .paid-filter-heading i {
        color: var(--accent);
    }

    .paid-filter-form {
        display: grid;

        grid-template-columns:
            minmax(0, 1fr)
            minmax(0, 1fr)
            auto;

        align-items: end;

        gap: 14px;
    }

    .paid-field label {
        display: block;

        margin-bottom: 7px;

        font-size: 12px;
        font-weight: 700;

        color: var(--text-secondary);
    }

    .paid-field-input {
        width: 100%;
        height: 46px;

        padding: 0 14px;

        border-radius: 12px;

        border: 1px solid #dce2ec;

        background: rgba(255,255,255,.82);

        color: #172033;

        outline: none;

        font-size: 14px;

        transition:
            border-color .2s ease,
            box-shadow .2s ease;
    }

    .paid-field-input:focus {
        border-color: rgba(49,94,251,.50);

        box-shadow:
            0 0 0 4px rgba(49,94,251,.08);
    }

    .paid-filter-button {
        height: 46px;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;

        padding: 0 20px;

        border: 0;
        border-radius: 12px;

        background:
            linear-gradient(
                135deg,
                #315efb,
                #254bd8
            );

        color: #fff;

        font-size: 13px;
        font-weight: 700;

        cursor: pointer;

        box-shadow:
            0 8px 20px rgba(49,94,251,.20);

        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }

    .paid-filter-button:hover {
        transform: translateY(-1px);

        box-shadow:
            0 12px 26px rgba(49,94,251,.25);
    }

    /* =========================================================
       RECORD CONTAINER
    ========================================================= */

    .paid-record-container {
        overflow: hidden;

        border: 1px solid var(--glass-border);
        border-radius: 24px;

        background:
            rgba(255,255,255,.68);

        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);

        box-shadow: var(--shadow);
    }

    .paid-record-header {
        display: flex;

        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 20px 22px;

        border-bottom: 1px solid rgba(15,23,42,.07);

        background:
            rgba(248,250,252,.68);
    }

    .paid-record-heading {
        display: flex;
        align-items: center;
        gap: 10px;

        font-size: 17px;
        font-weight: 800;

        color: #172033;
    }

    .paid-record-heading i {
        color: var(--accent);
    }

    .paid-record-count {
        display: inline-flex;
        align-items: center;

        min-height: 28px;

        padding: 0 10px;

        border-radius: 999px;

        background: rgba(49,94,251,.08);

        color: var(--accent);

        font-size: 12px;
        font-weight: 800;
    }

    .paid-record-list {
        padding: 18px;
    }

    /* =========================================================
       PAID DEBT CARD
    ========================================================= */

    .paid-debt-card {
        position: relative;

        display: flex;

        align-items: stretch;

        gap: 22px;

        padding: 22px;

        margin-bottom: 15px;

        border: 1px solid rgba(15,23,42,.07);

        border-radius: 20px;

        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.90),
                rgba(248,250,252,.70)
            );

        box-shadow:
            0 7px 24px rgba(31,41,55,.045);

        transition:
            transform .25s ease,
            box-shadow .25s ease,
            border-color .25s ease;
    }

    .paid-debt-card:last-child {
        margin-bottom: 0;
    }

    .paid-debt-card:hover {
        transform: translateY(-2px);

        border-color: rgba(49,94,251,.15);

        box-shadow:
            0 16px 38px rgba(31,41,55,.08);
    }

    .paid-debt-main {
        flex: 1;
        min-width: 0;
    }

    .paid-debt-top {
        display: flex;

        align-items: flex-start;
        justify-content: space-between;

        gap: 15px;

        margin-bottom: 18px;
    }

    .paid-invoice-label {
        display: flex;
        align-items: center;
        gap: 10px;

        font-size: 16px;
        font-weight: 800;

        color: #172033;

        word-break: break-word;
    }

    .paid-invoice-label i {
        color: var(--accent);
    }

    .paid-status {
        flex-shrink: 0;

        display: inline-flex;
        align-items: center;
        gap: 7px;

        padding: 7px 11px;

        border-radius: 999px;

        background: var(--success-bg);

        color: var(--success);

        font-size: 11px;
        font-weight: 800;

        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .paid-status-dot {
        width: 7px;
        height: 7px;

        border-radius: 50%;

        background: var(--success);
    }

    .paid-info-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 12px;
    }

    .paid-info-item {
        min-width: 0;

        padding: 13px 14px;

        border-radius: 13px;

        background:
            rgba(241,245,249,.68);

        border: 1px solid rgba(15,23,42,.045);
    }

    .paid-info-label {
        display: flex;
        align-items: center;
        gap: 7px;

        margin-bottom: 5px;

        font-size: 11px;
        font-weight: 700;

        color: var(--text-muted);

        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .paid-info-label i {
        color: var(--accent);
    }

    .paid-info-value {
        font-size: 14px;
        font-weight: 700;

        color: #273247;

        word-break: break-word;
    }

    .paid-balance-value {
        color: var(--success);
    }

    /* =========================================================
       INVOICE ACTIONS
    ========================================================= */

    .paid-actions {
        width: 190px;

        flex-shrink: 0;

        display: flex;

        flex-direction: column;

        justify-content: center;

        gap: 10px;

        padding-left: 20px;

        border-left: 1px solid rgba(15,23,42,.07);
    }

    .paid-action-button {
        width: 100%;

        min-height: 42px;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        gap: 8px;

        padding: 10px 14px;

        border-radius: 11px;

        font-size: 12px;
        font-weight: 800;

        text-decoration: none;

        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }

    .paid-action-button:hover {
        transform: translateY(-1px);
    }

    .paid-action-primary {
        background:
            rgba(49,94,251,.08);

        color: #254bd8;

        border: 1px solid rgba(49,94,251,.12);
    }

    .paid-action-primary:hover {
        box-shadow:
            0 8px 18px rgba(49,94,251,.10);
    }

    .paid-action-success {
        background:
            rgba(8,116,67,.08);

        color: #087443;

        border: 1px solid rgba(8,116,67,.12);
    }

    .paid-no-invoice {
        display: flex;

        align-items: center;
        justify-content: center;

        gap: 7px;

        min-height: 42px;

        border-radius: 11px;

        background:
            rgba(100,116,139,.07);

        color: #7b8798;

        font-size: 12px;
        font-weight: 700;
    }

    /* =========================================================
       EMPTY STATE
    ========================================================= */

    .paid-empty {
        padding: 65px 20px;

        text-align: center;
    }

    .paid-empty-icon {
        width: 64px;
        height: 64px;

        display: flex;
        align-items: center;
        justify-content: center;

        margin: 0 auto 15px;

        border-radius: 20px;

        background:
            rgba(49,94,251,.07);

        color: var(--accent);

        font-size: 25px;
    }

    .paid-empty h4 {
        margin: 0;

        font-size: 17px;
        font-weight: 800;

        color: #172033;
    }

    .paid-empty p {
        margin: 7px auto 0;

        max-width: 430px;

        color: var(--text-secondary);

        font-size: 13px;
        line-height: 1.7;
    }

    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 900px) {

        .paid-filter-form {
            grid-template-columns:
                1fr 1fr;
        }

        .paid-filter-button {
            width: 100%;
        }

        .paid-debt-card {
            flex-direction: column;
        }

        .paid-actions {
            width: 100%;

            padding-left: 0;
            padding-top: 16px;

            border-left: 0;
            border-top: 1px solid rgba(15,23,42,.07);

            flex-direction: row;
        }

        .paid-action-button,
        .paid-no-invoice {
            flex: 1;
        }

    }

    @media (max-width: 640px) {

        .paid-page-header {
            padding: 21px;
            border-radius: 20px;
        }

        .paid-title {
            font-size: 25px;
        }

        .paid-subtitle {
            font-size: 13px;
        }

        .paid-stat-grid {
            grid-template-columns: 1fr;
        }

        .paid-filter-card {
            padding: 16px;
            border-radius: 18px;
        }

        .paid-filter-form {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .paid-record-header {
            padding: 17px;

            align-items: flex-start;

            flex-direction: column;
        }

        .paid-record-list {
            padding: 12px;
        }

        .paid-debt-card {
            padding: 16px;

            border-radius: 17px;
        }

        .paid-debt-top {
            flex-direction: column;

            gap: 10px;
        }

        .paid-info-grid {
            grid-template-columns: 1fr;
        }

        .paid-actions {
            flex-direction: column;
        }

        .paid-action-button,
        .paid-no-invoice {
            width: 100%;
        }

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
            localStorage.setItem('darkMode', JSON.stringify(value))
        )
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

<!-- =========================================================
     PAGE WRAPPER
========================================================= -->

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>


            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                <!-- =================================================
                     FUTURISTIC HEADER
                ================================================= -->

                <section class="paid-page-header">

                    <div class="paid-header-content">

                        <div class="paid-kicker">
                            <i class="fa-solid fa-circle-check"></i>
                            Payment Intelligence
                        </div>

                        <h1 class="paid-title">
                            Paid Debt Report
                        </h1>

                        <p class="paid-subtitle">
                            A clear record of customer debts that have been
                            settled, including payment status and invoice history.
                        </p>

                        <div class="paid-breadcrumb">

                            <a href="index.php">
                                <i class="fa-solid fa-house"></i>
                                Home
                            </a>

                            <i class="fa-solid fa-chevron-right"></i>

                            <span>
                                Paid Debts
                            </span>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     ANALYTICS
                ================================================= -->

                <div class="paid-stat-grid">

                    <div class="paid-stat-card">

                        <div class="paid-stat-icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>

                        <span class="paid-stat-label">
                            Paid Debt Records
                        </span>

                        <div class="paid-stat-value">
                            <?= number_format($debt_count) ?>
                        </div>

                    </div>


                    <div class="paid-stat-card">

                        <div class="paid-stat-icon">
                            <i class="fa-solid fa-naira-sign"></i>
                        </div>

                        <span class="paid-stat-label">
                            Recorded Paid Balance
                        </span>

                        <div class="paid-stat-value">
                            ₦<?= number_format($total_paid_amount, 2) ?>
                        </div>

                    </div>

                </div>


                <!-- =================================================
                     FILTER
                ================================================= -->

                <section class="paid-filter-card">

                    <div class="paid-filter-heading">

                        <i class="fa-solid fa-sliders"></i>

                        <span>
                            Report Period
                        </span>

                    </div>


                    <form
                        method="GET"
                        id="dateFilterForm"
                        class="paid-filter-form"
                    >

                        <div class="paid-field">

                            <label for="fromDate">
                                From Date
                            </label>

                            <input
                                type="date"
                                name="from"
                                id="fromDate"
                                value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>"
                                class="paid-field-input"
                            >

                        </div>


                        <div class="paid-field">

                            <label for="toDate">
                                To Date
                            </label>

                            <input
                                type="date"
                                name="to"
                                id="toDate"
                                value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>"
                                class="paid-field-input"
                            >

                        </div>


                        <button
                            type="submit"
                            class="paid-filter-button"
                        >

                            <i class="fa-solid fa-filter"></i>

                            Apply Filter

                        </button>

                    </form>

                </section>


                <!-- =================================================
                     RECORDS
                ================================================= -->

                <section class="paid-record-container">

                    <div class="paid-record-header">

                        <div class="paid-record-heading">

                            <i class="fa-solid fa-layer-group"></i>

                            <span>
                                Settled Debt Records
                            </span>

                        </div>

                        <span class="paid-record-count">
                            <?= number_format($debt_count) ?> Records
                        </span>

                    </div>


                    <div class="paid-record-list">

                        <?php if (!empty($customer_debts)): ?>

                            <?php foreach ($customer_debts as $debt): ?>

                                <?php

                                $previousInvoiceNumber =
                                    $debt['pdprevious_invoice_number'] ?? '';

                                $paidInvoiceNumber =
                                    $debt['pdpaid_invoice_number'] ?? '';

                                $previousInvoice =
                                    $previousInvoiceNumber . '.pdf';

                                $finalInvoice =
                                    $paidInvoiceNumber . '.pdf';

                                $preinvoicePath =
                                    '../invoices/invoice_' . $previousInvoice;

                                $fininvoicePath =
                                    '../invoices/invoice_' . $finalInvoice;

                                $status =
                                    $debt['pdstatus'] ?? 'Paid';

                                $balance =
                                    (float)($debt['pdbalance'] ?? 0);

                                $createdBy =
                                    $debt['pdcreated_by'] ?? 'Unknown';

                                $createdAt =
                                    $debt['pdcreated_at'] ?? '';

                                ?>


                                <!-- =====================================
                                     DEBT CARD
                                ====================================== -->

                                <article class="paid-debt-card">

                                    <div class="paid-debt-main">

                                        <div class="paid-debt-top">

                                            <div class="paid-invoice-label">

                                                <i class="fa-solid fa-file-invoice"></i>

                                                <span>
                                                    Invoice
                                                    <?= htmlspecialchars(
                                                        $previousInvoiceNumber ?: 'N/A'
                                                    ) ?>
                                                </span>

                                            </div>


                                            <div class="paid-status">

                                                <span class="paid-status-dot"></span>

                                                <?= htmlspecialchars($status) ?>

                                            </div>

                                        </div>


                                        <div class="paid-info-grid">

                                            <!-- Balance -->

                                            <div class="paid-info-item">

                                                <div class="paid-info-label">

                                                    <i class="fa-solid fa-naira-sign"></i>

                                                    Balance

                                                </div>

                                                <div class="paid-info-value paid-balance-value">

                                                    ₦<?= number_format(
                                                        $balance,
                                                        2
                                                    ) ?>

                                                </div>

                                            </div>


                                            <!-- Recorded By -->

                                            <div class="paid-info-item">

                                                <div class="paid-info-label">

                                                    <i class="fa-solid fa-user"></i>

                                                    Recorded By

                                                </div>

                                                <div class="paid-info-value">

                                                    <?= htmlspecialchars(
                                                        $createdBy
                                                    ) ?>

                                                </div>

                                            </div>


                                            <!-- Date -->

                                            <div class="paid-info-item">

                                                <div class="paid-info-label">

                                                    <i class="fa-regular fa-calendar"></i>

                                                    Payment Date

                                                </div>

                                                <div class="paid-info-value">

                                                    <?= htmlspecialchars(
                                                        $createdAt
                                                    ) ?>

                                                </div>

                                            </div>


                                            <!-- Final Invoice -->

                                            <div class="paid-info-item">

                                                <div class="paid-info-label">

                                                    <i class="fa-solid fa-file-circle-check"></i>

                                                    Final Invoice

                                                </div>

                                                <div class="paid-info-value">

                                                    <?= htmlspecialchars(
                                                        $paidInvoiceNumber ?: 'N/A'
                                                    ) ?>

                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- =================================
                                         INVOICE ACTIONS
                                    ================================== -->

                                    <div class="paid-actions">

                                        <?php if (
                                            !empty($previousInvoiceNumber)
                                            && file_exists($preinvoicePath)
                                        ): ?>

                                            <a
                                                href="<?= htmlspecialchars($preinvoicePath) ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="paid-action-button paid-action-primary"
                                            >

                                                <i class="fa-solid fa-file-pdf"></i>

                                                Initial Invoice

                                            </a>

                                        <?php else: ?>

                                            <div class="paid-no-invoice">

                                                <i class="fa-regular fa-file"></i>

                                                No Initial Invoice

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty($paidInvoiceNumber)
                                            && file_exists($fininvoicePath)
                                        ): ?>

                                            <a
                                                href="<?= htmlspecialchars($fininvoicePath) ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="paid-action-button paid-action-success"
                                            >

                                                <i class="fa-solid fa-file-circle-check"></i>

                                                Final Invoice

                                            </a>

                                        <?php else: ?>

                                            <div class="paid-no-invoice">

                                                <i class="fa-regular fa-file"></i>

                                                No Final Invoice

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <!-- =====================================
                                 EMPTY STATE
                            ====================================== -->

                            <div class="paid-empty">

                                <div class="paid-empty-icon">

                                    <i class="fa-solid fa-receipt"></i>

                                </div>

                                <h4>
                                    No paid debts found
                                </h4>

                                <p>
                                    There are no paid debt records for the
                                    selected date range. Try selecting a
                                    different reporting period.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </section>

            </div>

        </main>

    </div>

</div>


<!-- =========================================================
     FOOTER
========================================================= -->

<?php include('footer.php'); ?>


<!-- =========================================================
     EXISTING APPLICATION JS
========================================================= -->

<script defer src="bundle.js"></script>


<!-- =========================================================
     DATE FILTER — MANUAL APPLY, NO PREMATURE SUBMISSION
========================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('dateFilterForm');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');

    if (!form || !fromDate || !toDate) {
        return;
    }

    /*
     * IMPORTANT:
     * Do not submit when either date changes.
     * The user must finish selecting both dates and click
     * the Apply Filter button.
     */

    form.addEventListener('submit', function (event) {
        const from = fromDate.value;
        const to = toDate.value;

        if (!from || !to) {
            event.preventDefault();
            alert('Please select both the start date and end date.');
            (!from ? fromDate : toDate).focus();
            return;
        }

        /*
         * ISO date format (YYYY-MM-DD) allows reliable
         * chronological comparison.
         */
        if (from > to) {
            event.preventDefault();
            alert('The end date cannot be earlier than the start date.');
            toDate.focus();
        }
    });
});
</script>


</body>

</html>
