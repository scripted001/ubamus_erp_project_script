
<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'] ?? null;
$location_id = $_SESSION['location_id'] ?? null;

// ===== Date Filter =====
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

// ===== Helper Functions =====
function medalColor($rank) {
    return match($rank) {
        1 => 'award-gold',
        2 => 'award-silver',
        3 => 'award-bronze',
        default => 'award-default',
    };
}

function medalIcon($rank) {
    return match($rank) {
        1 => '<i class="fa-solid fa-trophy"></i>',
        2 => '<i class="fa-solid fa-medal"></i>',
        3 => '<i class="fa-solid fa-medal"></i>',
        default => '<span class="rank-number">' . $rank . '</span>',
    };
}

function formatAmount($amount, $fallback = 'No Activity') {
    return is_numeric($amount) && $amount > 0
        ? '₦' . number_format($amount, 2)
        : $fallback;
}

function formatCount($count, $fallback = 'None') {
    return is_numeric($count) && $count > 0
        ? $count
        : $fallback;
}

// ===== TOP 50 CUSTOMERS BY SALES =====
$salesStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,
           SUM(sa.final_total) AS total_sales,
           COUNT(sa.id) AS num_sales
    FROM customers c
    LEFT JOIN sales sa 
        ON c.id = sa.customer_id 
        AND sa.company_id = :company_id_sales
        AND sa.location_id = :location_id_sales
        AND sa.created_at BETWEEN :start_date_sales AND :end_date_sales
    WHERE c.company_id = :company_id_main_sales
      AND c.location_id = :location_id_main_sales
    GROUP BY c.id
    ORDER BY total_sales DESC, num_sales DESC
    LIMIT 50
");

$salesStmt->execute([
    ':company_id_sales' => $company_id,
    ':location_id_sales' => $location_id,
    ':start_date_sales' => $start_date . ' 00:00:00',
    ':end_date_sales' => $end_date . ' 23:59:59',
    ':company_id_main_sales' => $company_id,
    ':location_id_main_sales' => $location_id
]);

$topSalesCustomers = $salesStmt->fetchAll(PDO::FETCH_ASSOC);


// ===== TOP 50 CUSTOMERS BY JOBS =====
$jobStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,
           SUM(j.final_total) AS total_jobs,
           COUNT(j.id) AS num_jobs
    FROM customers c
    LEFT JOIN jobs j
        ON c.id = j.customer_id
        AND j.company_id = :company_id_jobs
        AND j.location_id = :location_id_jobs
        AND j.created_at BETWEEN :start_date_jobs AND :end_date_jobs
    WHERE c.company_id = :company_id_main_jobs
      AND c.location_id = :location_id_main_jobs
    GROUP BY c.id
    ORDER BY total_jobs DESC, num_jobs DESC
    LIMIT 50
");

$jobStmt->execute([
    ':company_id_jobs' => $company_id,
    ':location_id_jobs' => $location_id,
    ':start_date_jobs' => $start_date . ' 00:00:00',
    ':end_date_jobs' => $end_date . ' 23:59:59',
    ':company_id_main_jobs' => $company_id,
    ':location_id_main_jobs' => $location_id
]);

$topJobCustomers = $jobStmt->fetchAll(PDO::FETCH_ASSOC);


// ===== TOP 50 OVERALL CUSTOMERS =====
$overallStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,
           COALESCE(sales.total_sales,0) AS total_sales,
           COALESCE(jobs.total_jobs,0) AS total_jobs,
           COALESCE(sales.total_sales,0) + COALESCE(jobs.total_jobs,0) AS overall_score
    FROM customers c

    LEFT JOIN (
        SELECT sa.customer_id, SUM(sa.final_total) AS total_sales
        FROM sales sa
        WHERE sa.company_id = :company_id_sales
          AND sa.location_id = :location_id_sales
          AND sa.created_at BETWEEN :start_date_sales AND :end_date_sales
        GROUP BY sa.customer_id
    ) sales ON c.id = sales.customer_id

    LEFT JOIN (
        SELECT j.customer_id, SUM(j.final_total) AS total_jobs
        FROM jobs j
        WHERE j.company_id = :company_id_jobs
          AND j.location_id = :location_id_jobs
          AND j.created_at BETWEEN :start_date_jobs AND :end_date_jobs
        GROUP BY j.customer_id
    ) jobs ON c.id = jobs.customer_id

    WHERE c.company_id = :company_id_main
      AND c.location_id = :location_id_main

    ORDER BY overall_score DESC
    LIMIT 50
");

$overallStmt->execute([
    ':company_id_sales' => $company_id,
    ':location_id_sales' => $location_id,
    ':start_date_sales' => $start_date . ' 00:00:00',
    ':end_date_sales' => $end_date . ' 23:59:59',

    ':company_id_jobs' => $company_id,
    ':location_id_jobs' => $location_id,
    ':start_date_jobs' => $start_date . ' 00:00:00',
    ':end_date_jobs' => $end_date . ' 23:59:59',

    ':company_id_main' => $company_id,
    ':location_id_main' => $location_id
]);

$topOverallCustomers = $overallStmt->fetchAll(PDO::FETCH_ASSOC);


// ===== FULL CUSTOMER LIST =====
$fullStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,

           COALESCE(sales.total_sales,0) AS total_sales,
           COUNT(sa.id) AS num_sales,

           COALESCE(jobs.total_jobs,0) AS total_jobs,
           COUNT(j.id) AS num_jobs,

           COALESCE(sales.total_sales,0) +
           COALESCE(jobs.total_jobs,0) AS overall_score

    FROM customers c

    LEFT JOIN sales sa 
        ON c.id = sa.customer_id 
        AND sa.company_id = :company_id1
        AND sa.location_id = :location_id1
        AND sa.created_at BETWEEN :start_date1 AND :end_date1

    LEFT JOIN (
        SELECT sa.customer_id, SUM(sa.final_total) AS total_sales
        FROM sales sa
        WHERE sa.company_id = :company_id2
          AND sa.location_id = :location_id2
          AND sa.created_at BETWEEN :start_date2 AND :end_date2
        GROUP BY sa.customer_id
    ) sales ON c.id = sales.customer_id

    LEFT JOIN jobs j 
        ON c.id = j.customer_id 
        AND j.company_id = :company_id3
        AND j.location_id = :location_id3
        AND j.created_at BETWEEN :start_date3 AND :end_date3

    LEFT JOIN (
        SELECT j.customer_id, SUM(j.final_total) AS total_jobs
        FROM jobs j
        WHERE j.company_id = :company_id4
          AND j.location_id = :location_id4
          AND j.created_at BETWEEN :start_date4 AND :end_date4
        GROUP BY j.customer_id
    ) jobs ON c.id = jobs.customer_id

    WHERE c.company_id = :company_id_main
      AND c.location_id = :location_id_main

    GROUP BY c.id
    ORDER BY overall_score DESC
");

$fullStmt->execute([
    ':company_id1' => $company_id,
    ':location_id1' => $location_id,
    ':start_date1' => $start_date . ' 00:00:00',
    ':end_date1' => $end_date . ' 23:59:59',

    ':company_id2' => $company_id,
    ':location_id2' => $location_id,
    ':start_date2' => $start_date . ' 00:00:00',
    ':end_date2' => $end_date . ' 23:59:59',

    ':company_id3' => $company_id,
    ':location_id3' => $location_id,
    ':start_date3' => $start_date . ' 00:00:00',
    ':end_date3' => $end_date . ' 23:59:59',

    ':company_id4' => $company_id,
    ':location_id4' => $location_id,
    ':start_date4' => $start_date . ' 00:00:00',
    ':end_date4' => $end_date . ' 23:59:59',

    ':company_id_main' => $company_id,
    ':location_id_main' => $location_id
]);

$allCustomers = $fullStmt->fetchAll(PDO::FETCH_ASSOC);
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
        <?= htmlspecialchars($company_name ?? 'Company'); ?> | Customer Awards
    </title>

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        rel="stylesheet"
        href="awards.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >


    <style>

        /* =========================================================
           GLOBAL
           ========================================================= */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        body {
            min-width: 0;
        }

        img,
        svg,
        video,
        iframe {
            max-width: 100%;
        }

        input,
        button,
        select {
            font: inherit;
        }


        /* =========================================================
           MAIN PAGE
           ========================================================= */

        .awards-page {
            width: 100%;
            min-width: 0;
        }

        .awards-wrapper {
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
        }

        .awards-content {
            width: 100%;
            min-width: 0;
        }


        /* =========================================================
           FILTER CARD
           ========================================================= */

        .award-filter-card {
            width: 100%;
            margin-bottom: 24px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .05);
        }

        .award-filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .award-filter-header-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f3f4f6;
            color: #374151;
        }

        .award-filter-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
        }

        .award-filter-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
            align-items: end;
            gap: 14px;
            width: 100%;
        }

        .award-field {
            min-width: 0;
        }

        .award-field label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
        }

        .award-date-input {
            width: 100%;
            height: 44px;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            outline: none;
            background: #ffffff;
            color: #374151;
            transition: .2s ease;
        }

        .award-date-input:focus {
            border-color: #6b7280;
            box-shadow: 0 0 0 3px rgba(107, 114, 128, .10);
        }

        .award-filter-button {
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 20px;
            border: 0;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: .2s ease;
            white-space: nowrap;
        }

        .award-filter-button:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }


        /* =========================================================
           SECTION HEADERS
           ========================================================= */

        .award-section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 16px;
            font-size: 17px;
            font-weight: 700;
            color: #1f2937;
        }

        .award-section-title i {
            color: #6b7280;
            font-size: 15px;
        }


        /* =========================================================
           LEADERBOARD GRID
           ========================================================= */

        .leaderboard-grid {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .leaderboard-section {
            min-width: 0;
        }

        .leaderboard-card {
            width: 100%;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }

        .leaderboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, .08);
            border-color: #d1d5db;
        }

        .leaderboard-icon {
            width: 45px;
            height: 45px;
            min-width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 19px;
        }

        .award-gold {
            background: #fef3c7;
            color: #b45309;
        }

        .award-silver {
            background: #f3f4f6;
            color: #4b5563;
        }

        .award-bronze {
            background: #ffedd5;
            color: #9a3412;
        }

        .award-default {
            background: #f3f4f6;
            color: #6b7280;
        }

        .rank-number {
            font-size: 14px;
            font-weight: 700;
        }

        .leaderboard-info {
            min-width: 0;
            flex: 1;
        }

        .leaderboard-name {
            margin: 0 0 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }

        .leaderboard-detail {
            margin: 3px 0 0;
            overflow-wrap: anywhere;
            font-size: 12px;
            line-height: 1.5;
            color: #6b7280;
        }

        .leaderboard-value {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
        }


        /* =========================================================
           EMPTY LEADERBOARD
           ========================================================= */

        .award-empty {
            padding: 30px 20px;
            border: 1px dashed #d1d5db;
            border-radius: 14px;
            text-align: center;
            color: #9ca3af;
        }

        .award-empty i {
            display: block;
            margin-bottom: 9px;
            font-size: 22px;
        }

        .award-empty span {
            font-size: 13px;
        }


        /* =========================================================
           CUSTOMER TABLE SECTION
           ========================================================= */

        .customer-table-section {
            width: 100%;
            margin-top: 34px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .05);
        }

        .customer-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .customer-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 8px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .customer-table-scroll {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            -webkit-overflow-scrolling: touch;
        }

        .customer-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .customer-table thead th {
            padding: 13px 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 11px;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        .customer-table tbody td {
            padding: 13px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #374151;
            white-space: nowrap;
        }

        .customer-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .customer-table tbody tr:hover {
            background: #f8fafc;
        }

        .table-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 28px;
            padding: 0 7px;
            border-radius: 7px;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 11px;
            font-weight: 700;
        }

        .table-customer {
            font-weight: 700;
            color: #1f2937;
        }

        .table-money {
            font-weight: 600;
        }

        .table-combined {
            font-weight: 800;
            color: #2563eb !important;
        }


        /* =========================================================
           DARK MODE
           ========================================================= */

        .dark .award-filter-card,
        .dark .leaderboard-card,
        .dark .customer-table-section {
            background: #111827;
            border-color: #1f2937;
        }

        .dark .award-filter-header h2,
        .dark .award-section-title,
        .dark .leaderboard-name,
        .dark .table-customer {
            color: #f3f4f6;
        }

        .dark .award-filter-header-icon,
        .dark .customer-count,
        .dark .award-default {
            background: #1f2937;
        }

        .dark .award-filter-header-icon {
            color: #d1d5db;
        }

        .dark .award-field label,
        .dark .leaderboard-detail {
            color: #9ca3af;
        }

        .dark .award-date-input {
            background: #111827;
            border-color: #374151;
            color: #e5e7eb;
        }

        .dark .award-section-title i {
            color: #9ca3af;
        }

        .dark .leaderboard-card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
        }

        .dark .leaderboard-value {
            color: #d1d5db;
        }

        .dark .customer-table-scroll {
            border-color: #1f2937;
        }

        .dark .customer-table thead th {
            background: #1f2937;
            border-color: #374151;
            color: #d1d5db;
        }

        .dark .customer-table tbody td {
            border-color: #1f2937;
            color: #d1d5db;
        }

        .dark .customer-table tbody tr:hover {
            background: #172033;
        }

        .dark .table-rank {
            background: #1f2937;
            color: #d1d5db;
        }

        .dark .table-combined {
            color: #60a5fa !important;
        }

        .dark .customer-count {
            color: #9ca3af;
        }

        .dark .award-empty {
            border-color: #374151;
        }


        /* =========================================================
           LARGE DESKTOP
           ========================================================= */

        @media (min-width: 1280px) {

            .awards-wrapper {
                padding: 28px 32px;
            }

            .leaderboard-grid {
                gap: 22px;
            }

            .customer-table-section,
            .award-filter-card {
                padding: 24px;
            }

        }


        /* =========================================================
           TABLET
           ========================================================= */

        @media (min-width: 640px) and (max-width: 999px) {

            .awards-wrapper {
                padding: 18px;
            }

            .leaderboard-grid {
                grid-template-columns: 1fr 1fr;
            }

            .leaderboard-section:last-child {
                grid-column: 1 / -1;
            }

            .award-filter-form {
                grid-template-columns: 1fr 1fr;
            }

            .award-filter-button {
                width: 100%;
            }

        }


        /* =========================================================
           MOBILE
           ========================================================= */

        @media (max-width: 639px) {

            .awards-wrapper {
                padding: 10px;
            }

            .award-filter-card {
                padding: 15px;
                margin-bottom: 18px;
                border-radius: 13px;
            }

            .award-filter-header {
                margin-bottom: 15px;
            }

            .award-filter-header-icon {
                width: 34px;
                height: 34px;
                border-radius: 9px;
            }

            .award-filter-header h2 {
                font-size: 14px;
            }

            .award-filter-form {
                grid-template-columns: 1fr;
                gap: 11px;
            }

            .award-filter-button {
                width: 100%;
            }

            .award-section-title {
                margin-bottom: 12px;
                font-size: 15px;
            }

            .leaderboard-grid {
                grid-template-columns: 1fr;
                gap: 22px;
            }

            .leaderboard-card {
                margin-bottom: 9px;
                padding: 13px;
                border-radius: 12px;
            }

            .leaderboard-icon {
                width: 42px;
                height: 42px;
                min-width: 42px;
            }

            .customer-table-section {
                margin-top: 25px;
                padding: 14px;
                border-radius: 13px;
            }

            .customer-table-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 9px;
            }

            .customer-table-scroll {
                border-radius: 10px;
            }

            .customer-table {
                min-width: 950px;
            }

        }


        /* =========================================================
           SMALL PHONES
           ========================================================= */

        @media (max-width: 380px) {

            .awards-wrapper {
                padding: 7px;
            }

            .award-filter-card {
                padding: 12px;
            }

            .leaderboard-card {
                gap: 10px;
            }

            .leaderboard-icon {
                width: 39px;
                height: 39px;
                min-width: 39px;
                font-size: 16px;
            }

            .leaderboard-name {
                font-size: 13px;
            }

            .leaderboard-detail,
            .leaderboard-value {
                font-size: 11px;
            }

            .customer-table-section {
                padding: 11px;
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

    <?php include('sidebar.php'); ?>


    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main class="awards-page">

            <?php include('header.php'); ?>


            <div class="awards-wrapper">

                <div class="awards-content">


                    <!-- =================================================
                         DATE FILTER
                         ================================================= -->

                    <div class="award-filter-card">

                        <div class="award-filter-header">

                            <div class="award-filter-header-icon">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>

                            <h2>
                                Customer Award Period
                            </h2>

                        </div>


                        <form
                            method="get"
                            class="award-filter-form"
                            id="form-bal"
                        >

                            <div class="award-field">

                                <label for="start_date">
                                    <i class="fa-solid fa-calendar-plus"></i>
                                    Start Date
                                </label>

                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    value="<?= htmlspecialchars($start_date) ?>"
                                    class="award-date-input"
                                >

                            </div>


                            <div class="award-field">

                                <label for="end_date">
                                    <i class="fa-solid fa-calendar-check"></i>
                                    End Date
                                </label>

                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    value="<?= htmlspecialchars($end_date) ?>"
                                    class="award-date-input"
                                >

                            </div>


                            <button
                                type="submit"
                                class="award-filter-button"
                                id="filter-btn"
                            >

                                <i class="fa-solid fa-filter"></i>

                                <span>
                                    Apply Filter
                                </span>

                            </button>

                        </form>

                    </div>


                    <!-- =================================================
                         LEADERBOARDS
                         ================================================= -->

                    <div class="leaderboard-grid">


                        <!-- =================================================
                             SALES
                             ================================================= -->

                        <div class="leaderboard-section">

                            <h2 class="award-section-title">

                                <i class="fa-solid fa-cart-shopping"></i>

                                <span>
                                    Top Customers by Sales
                                </span>

                            </h2>


                            <?php if (!empty($topSalesCustomers)): ?>

                                <?php foreach($topSalesCustomers as $i => $cust): ?>

                                    <div class="leaderboard-card">

                                        <div class="leaderboard-icon <?= medalColor($i + 1) ?>">

                                            <?= medalIcon($i + 1) ?>

                                        </div>


                                        <div class="leaderboard-info">

                                            <h3 class="leaderboard-name">

                                                <?= htmlspecialchars(
                                                    $cust['fullname'] ?? ''
                                                ) ?>

                                            </h3>

                                            <p class="leaderboard-detail">

                                                <i class="fa-solid fa-money-bill-wave"></i>

                                                <?= formatAmount(
                                                    $cust['total_sales'],
                                                    'No Sales'
                                                ) ?>

                                            </p>

                                            <p class="leaderboard-detail">

                                                <i class="fa-solid fa-bag-shopping"></i>

                                                <?= formatCount(
                                                    $cust['num_sales'],
                                                    'No Sales'
                                                ) ?>

                                                purchase(s)

                                            </p>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="award-empty">

                                    <i class="fa-solid fa-cart-shopping"></i>

                                    <span>
                                        No sales activity for this period.
                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- =================================================
                             JOBS
                             ================================================= -->

                        <div class="leaderboard-section">

                            <h2 class="award-section-title">

                                <i class="fa-solid fa-screwdriver-wrench"></i>

                                <span>
                                    Top Customers by Jobs
                                </span>

                            </h2>


                            <?php if (!empty($topJobCustomers)): ?>

                                <?php foreach($topJobCustomers as $i => $cust): ?>

                                    <div class="leaderboard-card">

                                        <div class="leaderboard-icon <?= medalColor($i + 1) ?>">

                                            <?= medalIcon($i + 1) ?>

                                        </div>


                                        <div class="leaderboard-info">

                                            <h3 class="leaderboard-name">

                                                <?= htmlspecialchars(
                                                    $cust['fullname'] ?? ''
                                                ) ?>

                                            </h3>

                                            <p class="leaderboard-detail">

                                                <i class="fa-solid fa-naira-sign"></i>

                                                <?= formatAmount(
                                                    $cust['total_jobs'],
                                                    'No Jobs'
                                                ) ?>

                                            </p>

                                            <p class="leaderboard-detail">

                                                <i class="fa-solid fa-briefcase"></i>

                                                <?= formatCount(
                                                    $cust['num_jobs'],
                                                    'No Jobs'
                                                ) ?>

                                                job(s)

                                            </p>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="award-empty">

                                    <i class="fa-solid fa-screwdriver-wrench"></i>

                                    <span>
                                        No job activity for this period.
                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- =================================================
                             OVERALL
                             ================================================= -->

                        <div class="leaderboard-section">

                            <h2 class="award-section-title">

                                <i class="fa-solid fa-crown"></i>

                                <span>
                                    Top Customers Overall
                                </span>

                            </h2>


                            <?php if (!empty($topOverallCustomers)): ?>

                                <?php foreach($topOverallCustomers as $i => $cust): ?>

                                    <div class="leaderboard-card">

                                        <div class="leaderboard-icon <?= medalColor($i + 1) ?>">

                                            <?= medalIcon($i + 1) ?>

                                        </div>


                                        <div class="leaderboard-info">

                                            <h3 class="leaderboard-name">

                                                <?= htmlspecialchars(
                                                    $cust['fullname'] ?? ''
                                                ) ?>

                                            </h3>

                                            <p class="leaderboard-detail">

                                                <i class="fa-solid fa-chart-line"></i>

                                                Combined Score

                                            </p>

                                            <div class="leaderboard-value">

                                                <?= formatAmount(
                                                    $cust['overall_score'],
                                                    'No Activity'
                                                ) ?>

                                            </div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="award-empty">

                                    <i class="fa-solid fa-users-slash"></i>

                                    <span>
                                        No customer activity for this period.
                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>


                    </div>


                    <!-- =================================================
                         ALL CUSTOMERS
                         ================================================= -->

                    <div class="customer-table-section">


                        <div class="customer-table-header">

                            <h2 class="award-section-title">

                                <i class="fa-solid fa-address-book"></i>

                                <span>
                                    All Customers
                                </span>

                            </h2>


                            <div class="customer-count">

                                <i class="fa-solid fa-users"></i>

                                <?= count($allCustomers) ?> Customers

                            </div>

                        </div>


                        <?php if (!empty($allCustomers)): ?>

                            <div class="customer-table-scroll">

                                <table class="customer-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                Rank
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-user"></i>
                                                Full Name
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-envelope"></i>
                                                Email
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-phone"></i>
                                                Phone
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-cart-shopping"></i>
                                                Total Sales
                                            </th>

                                            <th>
                                                Purchases
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-screwdriver-wrench"></i>
                                                Total Jobs
                                            </th>

                                            <th>
                                                Jobs
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-chart-line"></i>
                                                Combined
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach($allCustomers as $rank => $cust): ?>

                                            <tr>

                                                <td>

                                                    <span class="table-rank">

                                                        <?= $rank + 1 ?>

                                                    </span>

                                                </td>


                                                <td class="table-customer">

                                                    <?= htmlspecialchars(
                                                        $cust['fullname'] ?? ''
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $cust['email'] ?? ''
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $cust['phone'] ?? ''
                                                    ) ?>

                                                </td>


                                                <td class="table-money">

                                                    <?= formatAmount(
                                                        $cust['total_sales'],
                                                        'No Sales'
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= formatCount(
                                                        $cust['num_sales'],
                                                        'No Sales'
                                                    ) ?>

                                                </td>


                                                <td class="table-money">

                                                    <?= formatAmount(
                                                        $cust['total_jobs'],
                                                        'No Jobs'
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= formatCount(
                                                        $cust['num_jobs'],
                                                        'No Jobs'
                                                    ) ?>

                                                </td>


                                                <td class="table-combined">

                                                    <?= formatAmount(
                                                        $cust['overall_score'],
                                                        'No Activity'
                                                    ) ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div class="award-empty">

                                <i class="fa-solid fa-users-slash"></i>

                                <span>
                                    No customers found for the selected period.
                                </span>

                            </div>

                        <?php endif; ?>


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

<script
    defer
    src="bundle.js"
></script>

<script
    defer
    src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015"
    integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ=="
    data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
    crossorigin="anonymous"
></script>


</body>

</html>
