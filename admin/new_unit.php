
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$companyid = $_SESSION['company_id'];

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
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?php echo htmlspecialchars($company_name); ?> | Staff Area
    </title>


    <!-- ========================================================
         FONT AWESOME
    ========================================================= -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >


    <!-- ========================================================
         EXISTING STYLES
    ========================================================= -->

    <link
        href="style.css"
        rel="stylesheet"
    >


    <!-- ========================================================
         RESPONSIVE UNIT PAGE STYLES
    ========================================================= -->

    <style>

        /* =====================================================
           PAGE CONTAINER
        ===================================================== */

        .unit-page-container {
            width: 100%;
            max-width: 1536px;
            margin: 0 auto;
            padding: 24px;
            box-sizing: border-box;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .unit-page-header {
            width: 100%;
            margin-bottom: 20px;
        }


        .unit-header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }


        .unit-header-title {
            min-width: 0;
        }


        .unit-header-title h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-size: 22px;
            line-height: 1.3;
            font-weight: 700;
            color: #1f2937;
        }


        .dark .unit-header-title h3 {
            color: rgba(255, 255, 255, 0.92);
        }


        .unit-header-title h3 i {
            color: #341893;
            font-size: 20px;
        }


        .unit-talk {
            display: block;
            margin-top: 7px;
            font-size: 13px;
            font-style: italic;
            color: #6b7280;
        }


        .dark .unit-talk {
            color: #9ca3af;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .unit-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 14px;
            font-size: 13px;
        }


        .unit-breadcrumb a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            color: #6b7280;
            transition: 0.2s ease;
        }


        .unit-breadcrumb a:hover {
            color: #341893;
        }


        .dark .unit-breadcrumb a {
            color: #9ca3af;
        }


        .unit-breadcrumb .separator {
            color: #9ca3af;
            font-size: 10px;
        }


        .unit-breadcrumb .current {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #374151;
            font-weight: 500;
        }


        .dark .unit-breadcrumb .current {
            color: rgba(255, 255, 255, 0.8);
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .unit-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        }


        .dark .unit-card {
            border-color: #1f2937;
            background: rgba(255, 255, 255, 0.03);
            box-shadow: none;
        }


        /* =====================================================
           CARD HEADER
        ===================================================== */

        .unit-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
        }


        .dark .unit-card-header {
            border-color: #1f2937;
        }


        .unit-card-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: rgba(52, 24, 147, 0.10);
            color: #341893;
            font-size: 18px;
        }


        .unit-card-header-text {
            min-width: 0;
        }


        .unit-card-header-text h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
        }


        .dark .unit-card-header-text h4 {
            color: rgba(255, 255, 255, 0.92);
        }


        .unit-card-header-text p {
            margin: 3px 0 0;
            font-size: 12px;
            color: #9ca3af;
        }


        /* =====================================================
           FORM BODY
        ===================================================== */

        .unit-form-body {
            padding: 24px;
        }


        .unit-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }


        .unit-form-group {
            width: 100%;
            min-width: 0;
        }


        .unit-form-group.full {
            grid-column: 1 / -1;
        }


        /* =====================================================
           LABELS
        ===================================================== */

        .unit-label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }


        .dark .unit-label {
            color: #d1d5db;
        }


        .unit-label i {
            width: 16px;
            text-align: center;
            color: #341893;
            font-size: 13px;
        }


        .required-star {
            color: #dc2626;
            margin-left: 1px;
        }


        /* =====================================================
           INPUT / SELECT
        ===================================================== */

        .unit-input,
        .unit-select {
            width: 100%;
            min-height: 46px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            color: #1f2937;
            padding: 11px 13px;
            font-size: 14px;
            outline: none;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }


        .unit-input:focus,
        .unit-select:focus {
            border-color: #341893;
            box-shadow: 0 0 0 3px rgba(52, 24, 147, 0.10);
        }


        .dark .unit-input,
        .dark .unit-select {
            border-color: #374151;
            background: #111827;
            color: rgba(255, 255, 255, 0.9);
        }


        .dark .unit-input::placeholder {
            color: #6b7280;
        }


        .dark .unit-select option {
            background: #111827;
            color: #ffffff;
        }


        /* =====================================================
           INPUT WRAPPER
        ===================================================== */

        .unit-input-wrapper {
            position: relative;
            width: 100%;
        }


        .unit-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            font-size: 14px;
        }


        .unit-input-wrapper .unit-input {
            padding-left: 40px;
        }


        /* =====================================================
           SELECT WRAPPER
        ===================================================== */

        .unit-select-wrapper {
            position: relative;
        }


        .unit-select-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            font-size: 14px;
            z-index: 1;
        }


        .unit-select-wrapper .unit-select {
            padding-left: 40px;
        }


        /* =====================================================
           HELP TEXT
        ===================================================== */

        .unit-help {
            margin-top: 6px;
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.5;
        }


        /* =====================================================
           FORM FOOTER
        ===================================================== */

        .unit-form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #f1f5f9;
        }


        .dark .unit-form-footer {
            border-color: #1f2937;
        }


        .unit-footer-note {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #9ca3af;
        }


        .unit-footer-note i {
            color: #341893;
        }


        .unit-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .unit-btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 17px;
            border-radius: 8px;
            border: 1px solid transparent;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-sizing: border-box;
            transition:
                transform 0.15s ease,
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease;
        }


        .unit-btn:hover {
            transform: translateY(-1px);
        }


        .unit-btn-primary {
            background: #341893;
            color: #ffffff;
            border-color: #341893;
        }


        .unit-btn-primary:hover {
            background: #281275;
            border-color: #281275;
        }


        .unit-btn-secondary {
            background: #ffffff;
            color: #374151;
            border-color: #d1d5db;
        }


        .unit-btn-secondary:hover {
            background: #f9fafb;
            color: #1f2937;
        }


        .dark .unit-btn-secondary {
            background: #1f2937;
            color: #d1d5db;
            border-color: #374151;
        }


        .dark .unit-btn-secondary:hover {
            background: #111827;
            color: #ffffff;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .unit-page-container {
                padding: 16px;
            }


            .unit-header-top {
                gap: 10px;
            }


            .unit-header-title h3 {
                font-size: 19px;
            }


            .unit-card {
                border-radius: 13px;
            }


            .unit-card-header {
                padding: 17px;
            }


            .unit-form-body {
                padding: 17px;
            }


            .unit-form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }


            .unit-form-group.full {
                grid-column: auto;
            }


            .unit-form-footer {
                align-items: stretch;
                flex-direction: column;
            }


            .unit-footer-note {
                width: 100%;
            }


            .unit-actions {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }


            .unit-btn {
                width: 100%;
            }

        }


        /* =====================================================
           SMALL PHONES
        ===================================================== */

        @media (max-width: 480px) {

            .unit-page-container {
                padding: 10px;
            }


            .unit-header-title h3 {
                font-size: 17px;
            }


            .unit-header-title h3 i {
                font-size: 17px;
            }


            .unit-talk {
                font-size: 12px;
            }


            .unit-breadcrumb {
                margin-top: 11px;
                font-size: 12px;
            }


            .unit-card-header {
                padding: 15px;
            }


            .unit-card-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
                font-size: 16px;
            }


            .unit-card-header-text h4 {
                font-size: 14px;
            }


            .unit-form-body {
                padding: 15px;
            }


            .unit-label {
                font-size: 12px;
            }


            .unit-input,
            .unit-select {
                min-height: 45px;
                font-size: 13px;
            }


            .unit-actions {
                grid-template-columns: 1fr;
            }

        }


        /* =====================================================
           VERY SMALL PHONES
        ===================================================== */

        @media (max-width: 360px) {

            .unit-page-container {
                padding: 7px;
            }


            .unit-card-header {
                padding: 13px;
            }


            .unit-form-body {
                padding: 13px;
            }


            .unit-form-grid {
                gap: 16px;
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
    ========================================================= -->

    <?php include('sidebar.php'); ?>


    <!-- ========================================================
         CONTENT AREA
    ========================================================= -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <!-- ====================================================
             MAIN
        ==================================================== -->

        <main>

            <?php include('header.php'); ?>


            <div class="unit-page-container">


                <!-- =================================================
                     PAGE HEADER
                ================================================= -->

                <div class="unit-page-header">

                    <div class="unit-header-top">

                        <div class="unit-header-title">

                            <h3>

                                <i class="fa-solid fa-ruler-combined"></i>

                                New Product Category Unit

                            </h3>


                            <span class="unit-talk">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                Aaaayah! Let's get this installation rolling.....
                            </span>


                            <nav class="unit-breadcrumb">

                                <a href="index.php">

                                    <i class="fa-solid fa-house"></i>

                                    Home

                                </a>


                                <span class="separator">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </span>


                                <span class="current">

                                    <i class="fa-solid fa-ruler-combined"></i>

                                    New Unit

                                </span>

                            </nav>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     UNIT CARD
                ================================================= -->

                <div class="unit-card">


                    <!-- =================================================
                         CARD HEADER
                    ================================================= -->

                    <div class="unit-card-header">

                        <div class="unit-card-icon">

                            <i class="fa-solid fa-boxes-stacked"></i>

                        </div>


                        <div class="unit-card-header-text">

                            <h4>
                                Create Product Unit
                            </h4>

                            <p>
                                Define the measurement or packaging unit used for your products.
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         FORM BODY
                    ================================================= -->

                    <div class="unit-form-body">

                        <form
                            method="POST"
                            action="create_unit.php"
                        >

                            <div class="unit-form-grid">


                                <!-- =====================================
                                     UNIT NAME
                                ====================================== -->

                                <div class="unit-form-group">

                                    <label
                                        class="unit-label"
                                        for="unit_name"
                                    >

                                        <i class="fa-solid fa-tag"></i>

                                        Unit Name

                                        <span class="required-star">*</span>

                                    </label>


                                    <div class="unit-input-wrapper">

                                        <i class="fa-solid fa-cube"></i>

                                        <input
                                            type="text"
                                            id="unit_name"
                                            name="unit_name"
                                            placeholder="e.g. Piece, Carton, Kilogram"
                                            class="unit-input"
                                            required
                                        >

                                    </div>


                                    <div class="unit-help">

                                        Enter the name customers and staff will see.

                                    </div>

                                </div>


                                <!-- =====================================
                                     UNIT TYPE
                                ====================================== -->

                                <div class="unit-form-group">

                                    <label
                                        class="unit-label"
                                        for="unitTypeSelect"
                                    >

                                        <i class="fa-solid fa-layer-group"></i>

                                        Type of Unit

                                        <span class="required-star">*</span>

                                    </label>


                                    <div class="unit-select-wrapper">

                                        <i class="fa-solid fa-list"></i>

                                        <select
                                            name="unit_type"
                                            id="unitTypeSelect"
                                            class="unit-select"
                                            required
                                        >

                                            <option value="">
                                                -- Select Unit Type --
                                            </option>

                                            <option value="Bulk Packaging">
                                                Bulk Packaging
                                            </option>

                                            <option value="Fabric / Textile Style Units">
                                                Fabric / Textile Style Units
                                            </option>

                                            <option value="Foodstuff-Specific">
                                                Foodstuff-Specific
                                            </option>

                                            <option value="Bottling">
                                                Bottling Products
                                            </option>

                                            <option value="Container">
                                                Container
                                            </option>

                                            <option value="General / Universal Units">
                                                General / Universal Units
                                            </option>

                                            <option value="Logistics / Large Supply Units">
                                                Logistics / Large Supply Units
                                            </option>

                                            <option value="Paper / Flat Items">
                                                Paper / Flat Items
                                            </option>

                                            <option value="Retail-Wholesale Hybrid Units">
                                                Retail-Wholesale Hybrid Units
                                            </option>

                                        </select>

                                    </div>


                                    <div class="unit-help">

                                        Choose the category that best describes this unit.

                                    </div>

                                </div>


                                <!-- =====================================
                                     HIDDEN COMPANY ID
                                ====================================== -->

                                <input
                                    type="hidden"
                                    name="company_id"
                                    value="<?= htmlspecialchars($_SESSION['company_id']) ?>"
                                >


                            </div>


                            <!-- =================================================
                                 FORM FOOTER
                            ================================================= -->

                            <div class="unit-form-footer">


                                <div class="unit-footer-note">

                                    <i class="fa-solid fa-circle-info"></i>

                                    <span>
                                        Fields marked with
                                        <span class="required-star">*</span>
                                        are required.
                                    </span>

                                </div>


                                <div class="unit-actions">


                                    <!-- CANCEL -->

                                    <a
                                        href="categories.php"
                                        class="unit-btn unit-btn-secondary"
                                    >

                                        <i class="fa-solid fa-arrow-left"></i>

                                        Cancel

                                    </a>


                                    <!-- CREATE -->

                                    <button
                                        type="submit"
                                        class="unit-btn unit-btn-primary"
                                    >

                                        <i class="fa-solid fa-plus"></i>

                                        Create Unit

                                    </button>


                                </div>

                            </div>


                        </form>

                    </div>

                </div>

            </div>

        </main>


        <!-- ====================================================
             FOOTER
        ===================================================== -->

        <?php include('footer.php'); ?>


    </div>

</div>


<!-- ============================================================
     EXISTING JAVASCRIPT
============================================================ -->

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
    data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
    crossorigin="anonymous"
></script>


</body>

</html>

