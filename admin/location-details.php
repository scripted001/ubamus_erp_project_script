<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'] ?? null;
$user_email = $_SESSION['user_email'] ?? '';
$approved_by = "Not Approved!";


// ============================================================
// LOCATION
// ============================================================

$location_id = $_GET['location_id'] ?? null;

if (!$location_id) {
    echo "<p style='padding:30px;color:#dc2626;'>No location selected.</p>";
    exit;
}


// ============================================================
// FETCH LOCATION
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM locations
    WHERE id = ?
    AND company_id = ?
    LIMIT 1
");

$stmt->execute([
    $location_id,
    $company_id
]);

$location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    echo "<p style='padding:30px;color:#dc2626;'>Location not found.</p>";
    exit;
}


// ============================================================
// TOTAL STAFF
// ============================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM staffs
    WHERE location_id = ?
");

$stmt->execute([$location_id]);

$totalStaff = (int)$stmt->fetchColumn();


// ============================================================
// TOTAL STOCK QTY & VALUE
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(s.quantity), 0) AS total_qty,
        COALESCE(
            SUM(s.quantity * p.selling_price),
            0
        ) AS total_value
    FROM stocks s
    INNER JOIN products p
        ON p.id = s.product_id
    WHERE s.location_id = ?
");

$stmt->execute([$location_id]);

$stockData = $stmt->fetch(PDO::FETCH_ASSOC);

$totalStock = (int)($stockData['total_qty'] ?? 0);

$totalStockValue = (float)(
    $stockData['total_value'] ?? 0
);


// ============================================================
// TOTAL SALES
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(si.total_price), 0)
    FROM sales s
    INNER JOIN sale_items si
        ON si.sale_id = s.id
    WHERE s.location_id = ?
");

$stmt->execute([$location_id]);

$totalSales = (float)$stmt->fetchColumn();


// ============================================================
// STAFF
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM staffs
    WHERE location_id = ?
    ORDER BY id DESC
");

$stmt->execute([$location_id]);

$staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// STOCKS
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        s.*,
        p.name AS product_name,
        p.selling_price
    FROM stocks s
    LEFT JOIN products p
        ON s.product_id = p.id
    WHERE s.location_id = ?
    ORDER BY s.id DESC
");

$stmt->execute([$location_id]);

$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// SALES
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM sales
    WHERE location_id = ?
    ORDER BY id DESC
");

$stmt->execute([$location_id]);

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <?= htmlspecialchars($company_name ?? 'Company'); ?>
        | Location Details
    </title>


    <!-- ========================================================
         FONT AWESOME
         ======================================================== -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >


    <!-- ========================================================
         EXISTING STYLES
         ======================================================== -->

    <link
        href="style.css"
        rel="stylesheet"
    >


    <style>

        /* ========================================================
           GLOBAL
           ======================================================== */

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


        /* ========================================================
           MAIN CONTENT
           ======================================================== */

        .location-page {
            width: 100%;
            min-width: 0;
        }

        .location-container {
            width: 100%;
            max-width: 1700px;
            margin: 0 auto;
            padding: 24px;
        }


        /* ========================================================
           LOCATION HEADER
           ======================================================== */

        .location-header {
            display: flex;
            align-items: center;
            gap: 13px;

            margin-bottom: 22px;

            min-width: 0;
        }

        .location-header-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: #f3f4f6;
            color: #374151;

            font-size: 19px;
        }

        .location-header-content {
            min-width: 0;
        }

        .location-header-content h1 {
            margin: 0;

            color: #111827;

            font-size: 21px;
            font-weight: 700;

            line-height: 1.3;

            overflow-wrap: anywhere;
        }

        .location-header-content p {
            margin: 4px 0 0;

            color: #9ca3af;

            font-size: 12px;
        }


        /* ========================================================
           DARK MODE HEADER
           ======================================================== */

        .dark .location-header-icon {
            background: #1f2937;
            color: #d1d5db;
        }

        .dark .location-header-content h1 {
            color: #f3f4f6;
        }

        .dark .location-header-content p {
            color: #9ca3af;
        }


        /* ========================================================
           OVERVIEW GRID
           ======================================================== */

        .location-overview {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;

            width: 100%;
        }


        /* ========================================================
           STAT CARD
           ======================================================== */

        .location-stat-card {
            position: relative;

            min-width: 0;

            padding: 20px;

            border: 1px solid #e5e7eb;
            border-radius: 16px;

            background: #ffffff;

            overflow: hidden;

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .location-stat-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 8px 24px rgba(0,0,0,.06);
        }

        .location-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;

            margin-bottom: 15px;
        }

        .location-stat-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: #f3f4f6;
            color: #6b7280;

            font-size: 15px;
        }

        .location-stat-label {
            margin: 0;

            color: #6b7280;

            font-size: 12px;
            font-weight: 600;
        }

        .location-stat-value {
            margin: 0;

            color: #111827;

            font-size: 25px;
            font-weight: 700;

            line-height: 1.2;

            overflow-wrap: anywhere;
        }


        /* ========================================================
           DARK MODE STAT CARD
           ======================================================== */

        .dark .location-stat-card {
            background: #111827;
            border-color: #1f2937;
        }

        .dark .location-stat-icon {
            background: #1f2937;
            color: #9ca3af;
        }

        .dark .location-stat-label {
            color: #9ca3af;
        }

        .dark .location-stat-value {
            color: #f3f4f6;
        }


        /* ========================================================
           TABS PANEL
           ======================================================== */

        .location-tabs-panel {
            width: 100%;

            margin-top: 22px;

            border: 1px solid #e5e7eb;
            border-radius: 16px;

            background: #ffffff;

            overflow: hidden;
        }

        .dark .location-tabs-panel {
            background: #111827;
            border-color: #1f2937;
        }


        /* ========================================================
           TAB NAVIGATION
           ======================================================== */

        .location-tabs {
            display: flex;
            align-items: stretch;

            width: 100%;

            border-bottom: 1px solid #e5e7eb;

            overflow-x: auto;

            scrollbar-width: none;
        }

        .location-tabs::-webkit-scrollbar {
            display: none;
        }

        .dark .location-tabs {
            border-color: #374151;
        }

        .location-tab {
            position: relative;

            flex: 0 0 auto;

            min-width: 120px;

            padding: 15px 20px;

            border: none;
            border-bottom: 2px solid transparent;

            background: transparent;

            color: #6b7280;

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;

            white-space: nowrap;

            transition:
                color .2s ease,
                background .2s ease,
                border-color .2s ease;
        }

        .location-tab i {
            margin-right: 7px;
        }

        .location-tab:hover {
            color: #02356e;
            background: #f9fafb;
        }

        .location-tab.active {
            color: #02356e;

            border-bottom-color: #02356e;

            background: #f9fafb;
        }

        .dark .location-tab {
            color: #9ca3af;
        }

        .dark .location-tab:hover,
        .dark .location-tab.active {
            color: #60a5fa;
            background: #172033;
        }

        .dark .location-tab.active {
            border-bottom-color: #60a5fa;
        }


        /* ========================================================
           TAB CONTENT
           ======================================================== */

        .location-tab-content {
            display: none;

            width: 100%;

            padding: 20px;
        }

        .location-tab-content.active {
            display: block;
        }


        /* ========================================================
           SECTION HEADER
           ======================================================== */

        .location-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 15px;
        }

        .location-section-title {
            display: flex;
            align-items: center;

            gap: 9px;

            color: #374151;

            font-size: 14px;
            font-weight: 700;
        }

        .location-section-title i {
            color: #9ca3af;
        }

        .dark .location-section-title {
            color: #e5e7eb;
        }


        /* ========================================================
           TABLE WRAPPER
           ======================================================== */

        .location-table-wrapper {
            width: 100%;

            overflow-x: auto;
            overflow-y: hidden;

            border: 1px solid #e5e7eb;
            border-radius: 12px;

            -webkit-overflow-scrolling: touch;
        }

        .dark .location-table-wrapper {
            border-color: #374151;
        }


        /* ========================================================
           DESKTOP TABLE
           ======================================================== */

        .location-table {
            width: 100%;

            min-width: 650px;

            border-collapse: collapse;

            text-align: left;
        }

        .location-table th {
            padding: 13px 16px;

            background: #f9fafb;

            border-bottom: 1px solid #e5e7eb;

            color: #6b7280;

            font-size: 11px;
            font-weight: 700;

            white-space: nowrap;
        }

        .location-table th i {
            margin-right: 5px;

            color: #9ca3af;
        }

        .location-table td {
            padding: 14px 16px;

            border-bottom: 1px solid #f3f4f6;

            color: #4b5563;

            font-size: 12px;

            vertical-align: middle;

            overflow-wrap: anywhere;
        }

        .location-table tbody tr:last-child td {
            border-bottom: none;
        }

        .location-table tbody tr {
            transition: background .15s ease;
        }

        .location-table tbody tr:hover {
            background: #f9fafb;
        }

        .dark .location-table th {
            background: #1f2937;
            border-color: #374151;
            color: #9ca3af;
        }

        .dark .location-table td {
            background: #111827;
            border-color: #1f2937;
            color: #d1d5db;
        }

        .dark .location-table tbody tr:hover td {
            background: #172033;
        }


        /* ========================================================
           MOBILE CARDS
           ======================================================== */

        .mobile-records {
            display: none;
        }

        .mobile-record-card {
            width: 100%;

            margin-bottom: 11px;

            padding: 15px;

            border: 1px solid #edf0f2;
            border-radius: 13px;

            background: #fafafa;
        }

        .mobile-record-card:last-child {
            margin-bottom: 0;
        }

        .mobile-record-row {
            display: flex;
            align-items: flex-start;

            gap: 10px;

            width: 100%;

            margin-bottom: 11px;
        }

        .mobile-record-row:last-child {
            margin-bottom: 0;
        }

        .mobile-record-label {
            width: 105px;
            min-width: 105px;

            display: flex;
            align-items: center;

            gap: 6px;

            color: #6b7280;

            font-size: 10.5px;
            font-weight: 700;
        }

        .mobile-record-label i {
            width: 14px;

            text-align: center;

            color: #9ca3af;
        }

        .mobile-record-value {
            flex: 1 1 auto;

            min-width: 0;

            color: #374151;

            font-size: 12px;

            line-height: 1.5;

            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .dark .mobile-record-card {
            background: #172033;
            border-color: #263244;
        }

        .dark .mobile-record-label {
            color: #9ca3af;
        }

        .dark .mobile-record-value {
            color: #e5e7eb;
        }


        /* ========================================================
           BADGES
           ======================================================== */

        .location-badge {
            display: inline-flex;
            align-items: center;

            gap: 5px;

            padding: 5px 8px;

            border-radius: 7px;

            background: #f3f4f6;

            color: #6b7280;

            font-size: 10px;
            font-weight: 600;
        }

        .dark .location-badge {
            background: #1f2937;
            color: #d1d5db;
        }


        /* ========================================================
           EMPTY STATE
           ======================================================== */

        .location-empty {
            display: flex;
            flex-direction: column;

            align-items: center;
            justify-content: center;

            min-height: 180px;

            padding: 30px;

            color: #9ca3af;

            text-align: center;
        }

        .location-empty-icon {
            width: 50px;
            height: 50px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 10px;

            border-radius: 50%;

            background: #f3f4f6;

            color: #9ca3af;

            font-size: 18px;
        }

        .location-empty strong {
            color: #6b7280;

            font-size: 13px;
        }

        .location-empty span {
            margin-top: 5px;

            font-size: 11px;
        }

        .dark .location-empty-icon {
            background: #1f2937;
        }

        .dark .location-empty strong {
            color: #d1d5db;
        }


        /* ========================================================
           LARGE DESKTOP
           ======================================================== */

        @media (min-width: 1280px) {

            .location-container {
                padding: 30px 32px;
            }

            .location-overview {
                gap: 18px;
            }

            .location-stat-card {
                padding: 22px;
            }

            .location-tab-content {
                padding: 22px;
            }

        }


        /* ========================================================
           TABLET
           ======================================================== */

        @media (max-width: 1100px) {

            .location-overview {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        /* ========================================================
           SMALL TABLET
           ======================================================== */

        @media (max-width: 767px) {

            .location-container {
                padding: 16px;
            }

            .location-header {
                margin-bottom: 17px;
            }

            .location-overview {
                gap: 12px;
            }

            .location-stat-card {
                padding: 16px;
                border-radius: 13px;
            }

            .location-stat-icon {
                width: 36px;
                height: 36px;
                min-width: 36px;

                font-size: 13px;
            }

            .location-stat-label {
                font-size: 10.5px;
            }

            .location-stat-value {
                font-size: 20px;
            }

            .location-tabs-panel {
                margin-top: 16px;
                border-radius: 13px;
            }

            .location-tab {
                min-width: 105px;

                padding: 13px 15px;

                font-size: 12px;
            }

            .location-tab-content {
                padding: 15px;
            }

            .location-table-wrapper {
                display: none;
            }

            .mobile-records {
                display: block;
            }

        }


        /* ========================================================
           MOBILE
           ======================================================== */

        @media (max-width: 639px) {

            .location-container {
                padding: 13px;
            }

            .location-header {
                gap: 10px;
            }

            .location-header-icon {
                width: 42px;
                height: 42px;
                min-width: 42px;

                border-radius: 11px;

                font-size: 16px;
            }

            .location-header-content h1 {
                font-size: 18px;
            }

            .location-header-content p {
                font-size: 10px;
            }

            .location-overview {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 10px;
            }

            .location-stat-card {
                padding: 13px;

                border-radius: 12px;
            }

            .location-stat-top {
                margin-bottom: 11px;
            }

            .location-stat-icon {
                width: 33px;
                height: 33px;
                min-width: 33px;

                border-radius: 8px;

                font-size: 12px;
            }

            .location-stat-label {
                font-size: 9.5px;
            }

            .location-stat-value {
                font-size: 17px;
            }

            .location-tabs {
                width: 100%;
            }

            .location-tab {
                min-width: 33.333%;

                padding: 13px 10px;

                text-align: center;

                font-size: 11px;
            }

            .location-tab i {
                margin-right: 4px;
            }

            .location-tab-content {
                padding: 13px;
            }

            .location-section-header {
                margin-bottom: 11px;
            }

            .location-section-title {
                font-size: 12px;
            }

            .mobile-record-card {
                padding: 13px;
                border-radius: 11px;
            }

            .mobile-record-label {
                width: 92px;
                min-width: 92px;

                font-size: 10px;
            }

            .mobile-record-value {
                font-size: 11.5px;
            }

        }


        /* ========================================================
           SMALL PHONES
           ======================================================== */

        @media (max-width: 420px) {

            .location-container {
                padding: 11px;
            }

            .location-overview {
                gap: 8px;
            }

            .location-stat-card {
                padding: 11px;
            }

            .location-stat-label {
                font-size: 9px;
            }

            .location-stat-value {
                font-size: 16px;
            }

            .location-tab {
                min-width: 33.333%;

                padding-left: 7px;
                padding-right: 7px;
            }

            .location-tab i {
                display: block;

                margin: 0 0 4px;
            }

            .mobile-record-label {
                width: 85px;
                min-width: 85px;
            }

        }


        /* ========================================================
           VERY SMALL PHONES
           ======================================================== */

        @media (max-width: 360px) {

            .location-container {
                padding: 9px;
            }

            .location-header-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
            }

            .location-header-content h1 {
                font-size: 16px;
            }

            .location-header-content p {
                font-size: 9px;
            }

            .location-stat-card {
                padding: 10px;
            }

            .location-stat-value {
                font-size: 15px;
            }

            .location-tab {
                font-size: 10px;
            }

            .location-tab-content {
                padding: 10px;
            }

            .mobile-record-card {
                padding: 11px;
            }

            .mobile-record-label {
                width: 80px;
                min-width: 80px;

                font-size: 9px;
            }

            .mobile-record-value {
                font-size: 10.5px;
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


<!-- =========================================================
     PAGE WRAPPER
     ========================================================= -->

<div class="flex h-screen overflow-hidden">


    <!-- =========================================================
         SIDEBAR
         ========================================================= -->

    <?php include('sidebar.php'); ?>


    <!-- =========================================================
         CONTENT AREA
         ========================================================= -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <!-- =====================================================
             HEADER
             ===================================================== -->

        <?php include('header.php'); ?>


        <!-- =====================================================
             MAIN
             ===================================================== -->

        <main class="location-page">

            <div class="location-container">


                <!-- =================================================
                     LOCATION HEADER
                     ================================================= -->

                <div class="location-header">

                    <div class="location-header-icon">

                        <i class="fa-solid fa-location-dot"></i>

                    </div>

                    <div class="location-header-content">

                        <h1>
                            <?= htmlspecialchars(
                                $location['name'] ?? 'Location Details'
                            ); ?>
                        </h1>

                        <p>
                            Location overview, staff, inventory and sales
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     OVERVIEW CARDS
                     ================================================= -->

                <div class="location-overview">


                    <!-- STAFF -->

                    <div class="location-stat-card">

                        <div class="location-stat-top">

                            <div class="location-stat-icon">

                                <i class="fa-solid fa-users"></i>

                            </div>

                        </div>

                        <p class="location-stat-label">
                            Total Staff
                        </p>

                        <h4 class="location-stat-value">
                            <?= number_format($totalStaff); ?>
                        </h4>

                    </div>


                    <!-- STOCK -->

                    <div class="location-stat-card">

                        <div class="location-stat-top">

                            <div class="location-stat-icon">

                                <i class="fa-solid fa-boxes-stacked"></i>

                            </div>

                        </div>

                        <p class="location-stat-label">
                            Total Stock
                        </p>

                        <h4 class="location-stat-value">
                            <?= number_format($totalStock); ?>
                        </h4>

                    </div>


                    <!-- STOCK VALUE -->

                    <div class="location-stat-card">

                        <div class="location-stat-top">

                            <div class="location-stat-icon">

                                <i class="fa-solid fa-money-bill-trend-up"></i>

                            </div>

                        </div>

                        <p class="location-stat-label">
                            Total Stock Value
                        </p>

                        <h4 class="location-stat-value">
                            <?= number_format(
                                $totalStockValue,
                                2
                            ); ?>
                        </h4>

                    </div>


                    <!-- SALES -->

                    <div class="location-stat-card">

                        <div class="location-stat-top">

                            <div class="location-stat-icon">

                                <i class="fa-solid fa-chart-line"></i>

                            </div>

                        </div>

                        <p class="location-stat-label">
                            Total Sales Value
                        </p>

                        <h4 class="location-stat-value">
                            <?= number_format(
                                $totalSales,
                                2
                            ); ?>
                        </h4>

                    </div>


                </div>


                <!-- =================================================
                     TABS PANEL
                     ================================================= -->

                <section class="location-tabs-panel">


                    <!-- =================================================
                         TAB BUTTONS
                         ================================================= -->

                    <div class="location-tabs">


                        <button
                            type="button"
                            class="location-tab active"
                            data-tab="staff"
                        >

                            <i class="fa-solid fa-users"></i>

                            Staff

                        </button>


                        <button
                            type="button"
                            class="location-tab"
                            data-tab="stocks"
                        >

                            <i class="fa-solid fa-boxes-stacked"></i>

                            Stocks

                        </button>


                        <button
                            type="button"
                            class="location-tab"
                            data-tab="sales"
                        >

                            <i class="fa-solid fa-receipt"></i>

                            Sales

                        </button>


                    </div>


                    <!-- =================================================
                         STAFF
                         ================================================= -->

                    <div
                        id="staff"
                        class="location-tab-content active"
                    >

                        <div class="location-section-header">

                            <div class="location-section-title">

                                <i class="fa-solid fa-user-group"></i>

                                Staff Members

                            </div>

                        </div>


                        <?php if (!empty($staffs)): ?>


                            <!-- DESKTOP TABLE -->

                            <div class="location-table-wrapper">

                                <table class="location-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                <i class="fa-solid fa-user"></i>
                                                Name
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
                                                <i class="fa-solid fa-user-shield"></i>
                                                Role
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach ($staffs as $staff): ?>

                                            <tr>

                                                <td>
                                                    <?= htmlspecialchars(
                                                        trim(
                                                            ($staff['firstname'] ?? '') .
                                                            ' ' .
                                                            ($staff['lastname'] ?? '')
                                                        )
                                                    ); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars(
                                                        $staff['email'] ?? ''
                                                    ); ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars(
                                                        $staff['phone'] ?? ''
                                                    ); ?>
                                                </td>

                                                <td>

                                                    <span class="location-badge">

                                                        <i class="fa-solid fa-user-tag"></i>

                                                        <?= htmlspecialchars(
                                                            $staff['role'] ?? 'N/A'
                                                        ); ?>

                                                    </span>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>


                            <!-- MOBILE CARDS -->

                            <div class="mobile-records">

                                <?php foreach ($staffs as $staff): ?>

                                    <div class="mobile-record-card">


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-user"></i>

                                                Name

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= htmlspecialchars(
                                                    trim(
                                                        ($staff['firstname'] ?? '') .
                                                        ' ' .
                                                        ($staff['lastname'] ?? '')
                                                    )
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-envelope"></i>

                                                Email

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= htmlspecialchars(
                                                    $staff['email'] ?? 'N/A'
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-phone"></i>

                                                Phone

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= htmlspecialchars(
                                                    $staff['phone'] ?? 'N/A'
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-user-shield"></i>

                                                Role

                                            </div>

                                            <div class="mobile-record-value">

                                                <span class="location-badge">

                                                    <i class="fa-solid fa-user-tag"></i>

                                                    <?= htmlspecialchars(
                                                        $staff['role'] ?? 'N/A'
                                                    ); ?>

                                                </span>

                                            </div>

                                        </div>


                                    </div>

                                <?php endforeach; ?>

                            </div>


                        <?php else: ?>

                            <div class="location-empty">

                                <div class="location-empty-icon">

                                    <i class="fa-solid fa-users-slash"></i>

                                </div>

                                <strong>
                                    No staff members
                                </strong>

                                <span>
                                    No staff are currently assigned to this location.
                                </span>

                            </div>

                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                         STOCKS
                         ================================================= -->

                    <div
                        id="stocks"
                        class="location-tab-content"
                    >

                        <div class="location-section-header">

                            <div class="location-section-title">

                                <i class="fa-solid fa-boxes-stacked"></i>

                                Location Stock

                            </div>

                        </div>


                        <?php if (!empty($stocks)): ?>


                            <!-- DESKTOP TABLE -->

                            <div class="location-table-wrapper">

                                <table class="location-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                <i class="fa-solid fa-box"></i>
                                                Product
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-cubes"></i>
                                                Quantity
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-tag"></i>
                                                Selling Price
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-money-bill"></i>
                                                Total Value
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach ($stocks as $stock): ?>

                                            <?php

                                            $quantity = (float)(
                                                $stock['quantity'] ?? 0
                                            );

                                            $sellingPrice = (float)(
                                                $stock['selling_price'] ?? 0
                                            );

                                            $stockValue =
                                                $quantity *
                                                $sellingPrice;

                                            ?>

                                            <tr>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $stock['product_name'] ??
                                                        'Unknown Product'
                                                    ); ?>

                                                </td>

                                                <td>

                                                    <?= number_format(
                                                        $quantity
                                                    ); ?>

                                                </td>

                                                <td>

                                                    <?= number_format(
                                                        $sellingPrice,
                                                        2
                                                    ); ?>

                                                </td>

                                                <td>

                                                    <strong>

                                                        <?= number_format(
                                                            $stockValue,
                                                            2
                                                        ); ?>

                                                    </strong>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>


                            <!-- MOBILE CARDS -->

                            <div class="mobile-records">

                                <?php foreach ($stocks as $stock): ?>

                                    <?php

                                    $quantity = (float)(
                                        $stock['quantity'] ?? 0
                                    );

                                    $sellingPrice = (float)(
                                        $stock['selling_price'] ?? 0
                                    );

                                    $stockValue =
                                        $quantity *
                                        $sellingPrice;

                                    ?>

                                    <div class="mobile-record-card">


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-box"></i>

                                                Product

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= htmlspecialchars(
                                                    $stock['product_name'] ??
                                                    'Unknown Product'
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-cubes"></i>

                                                Quantity

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= number_format(
                                                    $quantity
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-tag"></i>

                                                Selling Price

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= number_format(
                                                    $sellingPrice,
                                                    2
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-money-bill"></i>

                                                Total Value

                                            </div>

                                            <div class="mobile-record-value">

                                                <strong>

                                                    <?= number_format(
                                                        $stockValue,
                                                        2
                                                    ); ?>

                                                </strong>

                                            </div>

                                        </div>


                                    </div>

                                <?php endforeach; ?>

                            </div>


                        <?php else: ?>

                            <div class="location-empty">

                                <div class="location-empty-icon">

                                    <i class="fa-solid fa-box-open"></i>

                                </div>

                                <strong>
                                    No stock available
                                </strong>

                                <span>
                                    There is currently no stock assigned to this location.
                                </span>

                            </div>

                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                         SALES
                         ================================================= -->

                    <div
                        id="sales"
                        class="location-tab-content"
                    >

                        <div class="location-section-header">

                            <div class="location-section-title">

                                <i class="fa-solid fa-chart-line"></i>

                                Sales Transactions

                            </div>

                        </div>


                        <?php if (!empty($sales)): ?>


                            <!-- DESKTOP TABLE -->

                            <div class="location-table-wrapper">

                                <table class="location-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                <i class="fa-solid fa-file-invoice"></i>
                                                Invoice
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-user"></i>
                                                Customer
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-money-bill-wave"></i>
                                                Total
                                            </th>

                                            <th>
                                                <i class="fa-solid fa-calendar"></i>
                                                Date
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach ($sales as $sale): ?>

                                            <tr>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $sale['invoice_number'] ??
                                                        'N/A'
                                                    ); ?>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $sale['customer_name'] ??
                                                        'Walk-in Customer'
                                                    ); ?>

                                                </td>

                                                <td>

                                                    <strong>

                                                        <?= number_format(
                                                            (float)(
                                                                $sale['final_total'] ??
                                                                0
                                                            ),
                                                            2
                                                        ); ?>

                                                    </strong>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $sale['created_at'] ??
                                                        'N/A'
                                                    ); ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>


                            <!-- MOBILE CARDS -->

                            <div class="mobile-records">

                                <?php foreach ($sales as $sale): ?>

                                    <div class="mobile-record-card">


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-file-invoice"></i>

                                                Invoice

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= htmlspecialchars(
                                                    $sale['invoice_number'] ??
                                                    'N/A'
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-user"></i>

                                                Customer

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= htmlspecialchars(
                                                    $sale['customer_name'] ??
                                                    'Walk-in Customer'
                                                ); ?>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-money-bill-wave"></i>

                                                Total

                                            </div>

                                            <div class="mobile-record-value">

                                                <strong>

                                                    <?= number_format(
                                                        (float)(
                                                            $sale['final_total'] ??
                                                            0
                                                        ),
                                                        2
                                                    ); ?>

                                                </strong>

                                            </div>

                                        </div>


                                        <div class="mobile-record-row">

                                            <div class="mobile-record-label">

                                                <i class="fa-solid fa-calendar"></i>

                                                Date

                                            </div>

                                            <div class="mobile-record-value">

                                                <?= htmlspecialchars(
                                                    $sale['created_at'] ??
                                                    'N/A'
                                                ); ?>

                                            </div>

                                        </div>


                                    </div>

                                <?php endforeach; ?>

                            </div>


                        <?php else: ?>

                            <div class="location-empty">

                                <div class="location-empty-icon">

                                    <i class="fa-solid fa-receipt"></i>

                                </div>

                                <strong>
                                    No sales found
                                </strong>

                                <span>
                                    There are currently no sales transactions for this location.
                                </span>

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


<!-- =========================================================
     CLOUDFLARE
     ========================================================= -->

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
     TABS
     ========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const buttons =
            document.querySelectorAll(
                '.location-tab'
            );

        const contents =
            document.querySelectorAll(
                '.location-tab-content'
            );


        buttons.forEach(function(button) {

            button.addEventListener(
                'click',
                function() {

                    const target =
                        button.dataset.tab;


                    /* REMOVE ACTIVE */

                    buttons.forEach(
                        function(btn) {

                            btn.classList.remove(
                                'active'
                            );

                        }
                    );


                    contents.forEach(
                        function(content) {

                            content.classList.remove(
                                'active'
                            );

                        }
                    );


                    /* ACTIVATE */

                    button.classList.add(
                        'active'
                    );


                    const targetContent =
                        document.getElementById(
                            target
                        );


                    if (targetContent) {

                        targetContent.classList.add(
                            'active'
                        );

                    }

                }
            );

        });

    }
);

</script>


</body>

</html>
