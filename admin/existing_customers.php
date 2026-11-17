<?php

include('../config/db.php');
include('../config/validation.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'] ?? null;
$location_id = $_SESSION['location_id'] ?? null;

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="content-type" content="text/html;charset=utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?= htmlspecialchars($company_name ?? 'Company'); ?> | Staff Area
    </title>


    <!-- =========================================================
         EXISTING CSS
         ========================================================= -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >

    <link
        href="main.css"
        rel="stylesheet"
    >


    <!-- =========================================================
         RESPONSIVE CUSTOMER PAGE CSS
         ALPINE.JS IS NOT TOUCHED
         ========================================================= -->

    <style>

        /* =====================================================
           GLOBAL RESPONSIVE SAFETY
           ===================================================== */

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

        button,
        input,
        select,
        textarea {
            font: inherit;
            max-width: 100%;
        }


        /* =====================================================
           PAGE
           ===================================================== */

        .customer-page {
            width: 100%;
            min-width: 0;
        }

        .customer-wrapper {
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 16px;
        }

        .customer-content {
            width: 100%;
            min-width: 0;
        }


        /* =====================================================
           CUSTOMER HEADER PANEL
           ===================================================== */

        .customer-panel {
            width: 100%;
            overflow: hidden;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .customer-panel-header {
            width: 100%;
            padding: 20px 24px 14px;
        }

        .customer-title {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.5;
            color: #1f2937;
        }

        .customer-title i {
            font-size: 16px;
        }


        /* =====================================================
           SEARCH AREA
           ===================================================== */

        .customer-search-area {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 24px 22px;
        }

        .customer-search-field {
            position: relative;
            min-width: 0;
            flex: 1 1 auto;
        }

        .customer-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            pointer-events: none;
            color: #9ca3af;
            font-size: 14px;
        }

        .customer-search-select,
        .customer-search-input {
            height: 44px;
            min-height: 44px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            background: #ffffff;
            color: #374151;
            font-size: 14px;
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .customer-search-select {
            width: 180px;
            min-width: 180px;
            padding: 8px 12px;
            cursor: pointer;
        }

        .customer-search-input {
            width: 100%;
            padding: 8px 14px 8px 40px;
        }

        .customer-search-select:focus,
        .customer-search-input:focus {
            border-color: #9ca3af;
            box-shadow: 0 0 0 3px rgba(156, 163, 175, .12);
        }


        /* =====================================================
           RESULTS SECTION
           ===================================================== */

        .customer-results-section {
            width: 100%;
            padding: 20px 24px 28px;
            border-top: 1px solid #f3f4f6;
        }

        .customer-results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .customer-results-heading {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .customer-results-heading i {
            color: #6b7280;
        }


        /* =====================================================
           CUSTOMER RESULTS
           ===================================================== */

        .customer-results {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }


        /* =====================================================
           CUSTOMER CARD
           ===================================================== */

        .customer-card {
            width: 100%;
            min-width: 0;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }

        .customer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
            border-color: #d1d5db;
        }


        /* =====================================================
           CUSTOMER CARD TOP
           ===================================================== */

        .customer-card-top {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 16px 12px;
        }

        .customer-avatar {
            width: 44px;
            height: 44px;
            min-width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 17px;
        }

        .customer-card-name {
            min-width: 0;
            flex: 1;
        }

        .customer-card-name strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }

        .customer-card-name span {
            display: block;
            margin-top: 2px;
            font-size: 12px;
            color: #9ca3af;
        }


        /* =====================================================
           CUSTOMER INFORMATION
           ===================================================== */

        .customer-info {
            width: 100%;
            padding: 4px 16px 10px;
        }

        .customer-row {
            width: 100%;
            min-width: 0;
            display: grid;
            grid-template-columns: 90px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            padding: 9px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .customer-row:last-child {
            border-bottom: none;
        }

        .customer-label {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
        }

        .customer-label i {
            width: 14px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }

        .customer-value {
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
            font-size: 13px;
            line-height: 1.5;
            color: #374151;
        }


        /* =====================================================
           CUSTOMER ACTIONS
           ===================================================== */

        .customer-actions {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 12px 16px 16px;
            border-top: 1px solid #f3f4f6;
        }

        .customer-delete-form {
            width: 100%;
            margin: 0;
        }

        .customer-delete-button {
            width: 100%;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 14px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fff;
            color: #ef4444;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition:
                background .2s ease,
                border-color .2s ease,
                color .2s ease;
        }

        .customer-delete-button:hover {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #dc2626;
        }

        .customer-delete-button i {
            font-size: 13px;
        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .customer-empty {
            width: 100%;
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 220px;
            padding: 30px 20px;
            border: 1px dashed #d1d5db;
            border-radius: 14px;
            background: #fafafa;
            text-align: center;
        }

        .customer-empty-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            border-radius: 50%;
            background: #f3f4f6;
            color: #9ca3af;
            font-size: 20px;
        }

        .customer-empty strong {
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
        }

        .customer-empty span {
            margin-top: 5px;
            font-size: 13px;
            color: #9ca3af;
        }


        /* =====================================================
           DARK MODE
           ===================================================== */

        .dark .customer-panel,
        .dark .customer-card {
            background: #111827;
            border-color: #1f2937;
        }

        .dark .customer-panel {
            border-color: #1f2937;
        }

        .dark .customer-title,
        .dark .customer-card-name strong,
        .dark .customer-results-heading {
            color: #f3f4f6;
        }

        .dark .customer-search-select,
        .dark .customer-search-input {
            background: #111827;
            border-color: #374151;
            color: #e5e7eb;
        }

        .dark .customer-search-input::placeholder {
            color: #6b7280;
        }

        .dark .customer-results-section {
            border-color: #1f2937;
        }

        .dark .customer-row {
            border-color: #1f2937;
        }

        .dark .customer-label {
            color: #9ca3af;
        }

        .dark .customer-value {
            color: #e5e7eb;
        }

        .dark .customer-avatar {
            background: #1f2937;
            color: #9ca3af;
        }

        .dark .customer-actions {
            border-color: #1f2937;
        }

        .dark .customer-delete-button {
            background: transparent;
            border-color: #7f1d1d;
            color: #f87171;
        }

        .dark .customer-delete-button:hover {
            background: #450a0a;
            border-color: #991b1b;
        }

        .dark .customer-empty {
            background: #111827;
            border-color: #374151;
        }

        .dark .customer-empty-icon {
            background: #1f2937;
        }

        .dark .customer-empty strong {
            color: #d1d5db;
        }


        /* =====================================================
           LARGE DESKTOP
           ===================================================== */

        @media (min-width: 1280px) {

            .customer-wrapper {
                padding: 28px 32px;
            }

            .customer-results {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 18px;
            }

            .customer-card-top {
                padding: 18px 18px 13px;
            }

            .customer-info {
                padding-left: 18px;
                padding-right: 18px;
            }

            .customer-actions {
                padding-left: 18px;
                padding-right: 18px;
            }

        }


        /* =====================================================
           DESKTOP / LAPTOP
           ===================================================== */

        @media (min-width: 900px) and (max-width: 1279px) {

            .customer-results {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

        }


        /* =====================================================
           TABLET
           ===================================================== */

        @media (min-width: 640px) and (max-width: 899px) {

            .customer-wrapper {
                padding: 20px;
            }

            .customer-results {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .customer-search-select {
                width: 190px;
                min-width: 190px;
            }

        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 639px) {

            .customer-wrapper {
                padding: 12px;
            }

            .customer-panel-header {
                padding: 17px 16px 12px;
            }

            .customer-search-area {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 0 16px 17px;
            }

            .customer-search-select {
                width: 100%;
                min-width: 0;
            }

            .customer-search-field {
                width: 100%;
            }

            .customer-results-section {
                padding: 17px 16px 22px;
            }

            .customer-results {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .customer-card {
                border-radius: 12px;
            }

        }


        /* =====================================================
           SMALL PHONES
           ===================================================== */

        @media (max-width: 480px) {

            .customer-wrapper {
                padding: 9px;
            }

            .customer-title {
                font-size: 15px;
            }

            .customer-search-select,
            .customer-search-input {
                height: 43px;
                min-height: 43px;
            }

            .customer-results-section {
                padding: 15px 12px 20px;
            }

            .customer-card-top {
                padding: 14px;
            }

            .customer-info {
                padding-left: 14px;
                padding-right: 14px;
            }

            .customer-row {
                display: flex;
                flex-direction: column;
                gap: 3px;
                padding: 9px 0;
            }

            .customer-label {
                font-size: 12px;
            }

            .customer-value {
                font-size: 13px;
            }

            .customer-actions {
                padding: 11px 14px 14px;
            }

        }


        /* =====================================================
           VERY SMALL PHONES
           ===================================================== */

        @media (max-width: 360px) {

            .customer-wrapper {
                padding: 7px;
            }

            .customer-panel-header {
                padding-left: 13px;
                padding-right: 13px;
            }

            .customer-search-area {
                padding-left: 13px;
                padding-right: 13px;
            }

            .customer-results-section {
                padding-left: 10px;
                padding-right: 10px;
            }

            .customer-card-top {
                padding: 12px;
            }

            .customer-info {
                padding-left: 12px;
                padding-right: 12px;
            }

            .customer-actions {
                padding-left: 12px;
                padding-right: 12px;
            }

            .customer-avatar {
                width: 40px;
                height: 40px;
                min-width: 40px;
            }

        }

    </style>

</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>


<!-- =========================================================
     PAGE WRAPPER
     ========================================================= -->

<div class="flex h-screen overflow-hidden">


    <?php include('sidebar.php'); ?>


    <!-- =====================================================
         CONTENT AREA
         ===================================================== -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <main class="customer-page">


            <?php include('header.php'); ?>


            <!-- =================================================
                 MAIN CONTENT
                 ================================================= -->

            <div class="customer-wrapper">


                <div class="customer-content">


                    <!-- =================================================
                         CUSTOMER PANEL
                         ================================================= -->

                    <div class="customer-panel">


                        <!-- =================================================
                             HEADER
                             ================================================= -->

                        <div class="customer-panel-header">

                            <h3 class="customer-title">

                                <i class="fa-solid fa-users"></i>

                                <span>
                                    Company Registered Customers
                                </span>

                            </h3>

                        </div>


                        <!-- =================================================
                             SEARCH
                             ================================================= -->

                        <div class="customer-search-area">


                            <!-- FILTER -->

                            <select
                                id="search_option"
                                class="customer-search-select"
                            >

                                <option value="fullname">
                                    Fullname
                                </option>

                                <option value="phone">
                                    Mobile
                                </option>

                                <option value="email">
                                    Email
                                </option>

                            </select>


                            <!-- SEARCH INPUT -->

                            <div class="customer-search-field">

                                <i
                                    class="fa-solid fa-magnifying-glass customer-search-icon"
                                ></i>

                                <input
                                    type="text"
                                    id="search_value"
                                    class="customer-search-input"
                                    placeholder="Search customer..."
                                    autocomplete="off"
                                >

                            </div>


                        </div>


                        <!-- =================================================
                             RESULTS
                             ================================================= -->

                        <div class="customer-results-section" >


                            <div class="customer-results-header">

                                <h4 class="customer-results-heading">

                                    <i class="fa-solid fa-address-book"></i>

                                    <span>
                                        Customers
                                    </span>

                                </h4>

                            </div>


                            <div
                                id="staffResults"
                                class="customer-results"
                            >


                                <?php

                                $stmt = $pdo->prepare("
                                    SELECT *
                                    FROM customers
                                    WHERE company_id = ? AND location_id = ?
                                    ORDER BY fullname ASC
                                ");

                                $stmt->execute([
                                    $company_id, $location_id
                                ]);

                                $customersFound = false;


                                while (
                                    $branch = $stmt->fetch(PDO::FETCH_ASSOC)
                                ):

                                    $customersFound = true;

                                ?>


                                    <!-- =================================================
                                         CUSTOMER CARD
                                         ================================================= -->

                                    <div class="customer-card">


                                        <!-- =================================================
                                             CUSTOMER HEADER
                                             ================================================= -->

                                        <div class="customer-card-top">


                                            <div class="customer-avatar">

                                                <i class="fa-solid fa-user"></i>

                                            </div>


                                            <div class="customer-card-name">

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $branch['fullname'] ?? ''
                                                    ) ?>
                                                </strong>

                                                <span>
                                                    Registered Customer
                                                </span>

                                            </div>


                                        </div>


                                        <!-- =================================================
                                             CUSTOMER INFORMATION
                                             ================================================= -->

                                        <div class="customer-info">


                                            <!-- FULLNAME -->

                                            <div class="customer-row">

                                                <span class="customer-label">

                                                    <i class="fa-solid fa-user"></i>

                                                    Fullname

                                                </span>

                                                <span class="customer-value">

                                                    <?= htmlspecialchars(
                                                        $branch['fullname'] ?? ''
                                                    ) ?>

                                                </span>

                                            </div>


                                            <!-- EMAIL -->

                                            <div class="customer-row">

                                                <span class="customer-label">

                                                    <i class="fa-solid fa-envelope"></i>

                                                    Email

                                                </span>

                                                <span class="customer-value">

                                                    <?= htmlspecialchars(
                                                        $branch['email'] ?? ''
                                                    ) ?>

                                                </span>

                                            </div>


                                            <!-- MOBILE -->

                                            <div class="customer-row">

                                                <span class="customer-label">

                                                    <i class="fa-solid fa-phone"></i>

                                                    Mobile

                                                </span>

                                                <span class="customer-value">

                                                    <?= htmlspecialchars(
                                                        $branch['phone'] ?? ''
                                                    ) ?>

                                                </span>

                                            </div>


                                        </div>


                                        <!-- =================================================
                                             ACTIONS
                                             ================================================= -->

                                        <div class="customer-actions">


                                            <form
                                                method="POST"
                                                action="delete_customer.php"
                                                class="customer-delete-form"
                                                onsubmit="return confirm('Do you want to proceed deleting this customer?');"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="staff_id"
                                                    value="<?= htmlspecialchars(
                                                        $branch['id'] ?? ''
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="staff_email"
                                                    value="<?= htmlspecialchars(
                                                        $branch['email'] ?? ''
                                                    ) ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="submit"
                                                    value="submit"
                                                    class="customer-delete-button"
                                                >

                                                    <i class="fa-solid fa-trash"></i>

                                                    <span>
                                                        Delete Customer
                                                    </span>

                                                </button>


                                            </form>


                                        </div>


                                    </div>


                                <?php endwhile; ?>


                                <?php if (!$customersFound): ?>


                                    <div class="customer-empty">

                                        <div class="customer-empty-icon">

                                            <i class="fa-solid fa-users-slash"></i>

                                        </div>

                                        <strong>
                                            No customers found
                                        </strong>

                                        <span>
                                            There are currently no registered customers.
                                        </span>

                                    </div>


                                <?php endif; ?>


                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </main>


    </div>


</div>


<!-- =========================================================
     FOOTER
     ========================================================= -->

<?php include('footer.php'); ?>


<!-- =========================================================
     EXISTING SCRIPTS
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
    data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfExtPri":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
    crossorigin="anonymous"
></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<!-- =========================================================
     EXISTING AJAX FILTER
     KEPT INTACT
     ========================================================= -->

<script>

$(document).ready(function(){

    function searchStaff(){

        var option = $("#search_option").val();
        var value  = $("#search_value").val();

        $.ajax({

            url: "ajax_search_customer.php",

            method: "POST",

            data: {

                search_option: option,
                search_value: value

            },

            success: function(data){

                $("#staffResults").html(data);

            }

        });

    }


    $("#search_value").keyup(function(){

        searchStaff();

    });


    $("#search_option").change(function(){

        searchStaff();

    });

});

</script>


</body>

</html>
