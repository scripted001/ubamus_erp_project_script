
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


/* ============================================================
   COMPANY
============================================================ */

$companyid = $_SESSION['company_id'] ?? 0;


/* ============================================================
   VALIDATE COMPANY ID
============================================================ */

if (empty($companyid)) {
    header("Location: index.php");
    exit;
}


/* ============================================================
   FETCH SUPPORT EMAIL DATA
============================================================ */

$stmt = $pdo->prepare("
    SELECT
        company_support_email,
        support_email_password
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $companyid
]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);


/* ============================================================
   DEFAULT VALUES
============================================================ */

$company_support_email =
    $company['company_support_email'] ?? '';

$support_email_password =
    $company['support_email_password'] ?? '';

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
        <?= htmlspecialchars($company_name ?? 'Company'); ?> | Staff Area
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
         RESPONSIVE PAGE STYLES
    ========================================================= -->

    <style>

        /* =====================================================
           PAGE CONTAINER
        ===================================================== */

        .support-page-container {
            width: 100%;
            max-width: 1536px;
            margin: 0 auto;
            padding: 24px;
            box-sizing: border-box;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .support-page-header {
            width: 100%;
            margin-bottom: 24px;
        }


        .support-page-title {
            margin: 0;
            font-size: 22px;
            line-height: 1.35;
            font-weight: 600;
            color: #1f2937;
            overflow-wrap: anywhere;
        }


        .dark .support-page-title {
            color: rgba(255, 255, 255, 0.92);
        }


        .support-page-description {
            display: block;
            margin-top: 8px;
            font-size: 14px;
            line-height: 1.6;
            color: #6b7280;
        }


        .dark .support-page-description {
            color: #9ca3af;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .support-breadcrumb {
            margin-top: 14px;
        }


        .support-breadcrumb ol {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }


        .support-breadcrumb a {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            text-decoration: none;
            color: #6b7280;
        }


        .support-breadcrumb a:hover {
            color: #374151;
        }


        .dark .support-breadcrumb a {
            color: #9ca3af;
        }


        .dark .support-breadcrumb a:hover {
            color: #e5e7eb;
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .support-form-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
            box-sizing: border-box;
        }


        .dark .support-form-card {
            border-color: #1f2937;
            background: rgba(255, 255, 255, 0.03);
        }


        /* =====================================================
           FORM CARD BODY
        ===================================================== */

        .support-form-body {
            padding: 24px;
        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .support-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            width: 100%;
        }


        /* =====================================================
           FORM GROUP
        ===================================================== */

        .support-form-group {
            width: 100%;
            min-width: 0;
        }


        .support-form-group-full {
            grid-column: 1 / -1;
        }


        /* =====================================================
           LABEL
        ===================================================== */

        .support-form-label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            line-height: 1.4;
            font-weight: 500;
            color: #374151;
        }


        .dark .support-form-label {
            color: #9ca3af;
        }


        /* =====================================================
           INPUT
        ===================================================== */

        .support-form-input {
            display: block;
            width: 100%;
            min-height: 46px;
            padding: 11px 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: transparent;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.4;
            box-sizing: border-box;
            outline: none;
            transition:
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }


        .support-form-input:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.10);
        }


        .dark .support-form-input {
            border-color: #374151;
            color: rgba(255, 255, 255, 0.9);
            background: rgba(17, 24, 39, 0.5);
        }


        .dark .support-form-input:focus {
            border-color: #4b5563;
        }


        /* =====================================================
           READONLY INPUT
        ===================================================== */

        .support-form-input[readonly] {
            cursor: default;
            background-color: #f9fafb;
        }


        .dark .support-form-input[readonly] {
            background-color: rgba(31, 41, 55, 0.5);
        }


        /* =====================================================
           PASSWORD FIELD
        ===================================================== */

        .support-password-wrapper {
            position: relative;
            width: 100%;
        }


        .support-password-input {
            padding-right: 14px;
        }


        /* =====================================================
           BUTTON AREA
        ===================================================== */

        .support-form-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 2px;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .support-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            line-height: 1.2;
            font-weight: 500;
            box-sizing: border-box;
            cursor: pointer;
            border: 0;
            transition:
                background-color 0.15s ease,
                color 0.15s ease,
                border-color 0.15s ease;
        }


        .support-submit-button {
            color: #ffffff;
            background: #3b82f6;
        }


        .support-submit-button:hover {
            background: #2563eb;
        }


        .support-cancel-button {
            color: #374151;
            background: #ffffff;
            border: 1px solid #d1d5db;
        }


        .support-cancel-button:hover {
            background: #f9fafb;
            color: #111827;
        }


        .dark .support-cancel-button {
            color: #9ca3af;
            background: #1f2937;
            border-color: #374151;
        }


        .dark .support-cancel-button:hover {
            color: #e5e7eb;
            background: rgba(255, 255, 255, 0.05);
        }


        /* =====================================================
           HELPER TEXT
        ===================================================== */

        .support-helper-text {
            margin-top: 7px;
            font-size: 12px;
            line-height: 1.5;
            color: #9ca3af;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 900px) {

            .support-page-container {
                padding: 18px;
            }


            .support-form-body {
                padding: 20px;
            }


            .support-form-grid {
                gap: 18px;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 640px) {

            .support-page-container {
                padding: 10px;
            }


            .support-page-header {
                margin-bottom: 18px;
            }


            .support-page-title {
                font-size: 18px;
            }


            .support-page-description {
                margin-top: 6px;
                font-size: 13px;
                line-height: 1.55;
            }


            .support-breadcrumb {
                margin-top: 12px;
            }


            .support-breadcrumb a {
                font-size: 12px;
            }


            .support-form-card {
                border-radius: 12px;
            }


            .support-form-body {
                padding: 16px;
            }


            /*
             * One column on mobile
             */

            .support-form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }


            .support-form-group-full {
                grid-column: auto;
            }


            .support-form-label {
                font-size: 13px;
            }


            .support-form-input {
                min-height: 45px;
                font-size: 14px;
            }


            /*
             * Stack buttons
             */

            .support-form-actions {
                display: grid;
                grid-template-columns: 1fr;
                width: 100%;
                gap: 10px;
            }


            .support-action-button {
                width: 100%;
                min-height: 46px;
            }

        }


        /* =====================================================
           VERY SMALL PHONES
        ===================================================== */

        @media (max-width: 380px) {

            .support-page-container {
                padding: 6px;
            }


            .support-page-title {
                font-size: 17px;
            }


            .support-page-description {
                font-size: 12px;
            }


            .support-form-body {
                padding: 13px;
            }


            .support-form-input {
                min-height: 44px;
                padding-left: 12px;
                padding-right: 12px;
                font-size: 13px;
            }


            .support-action-button {
                font-size: 13px;
            }

        }


        /* =====================================================
           PREVENT LONG VALUES FROM BREAKING MOBILE LAYOUT
        ===================================================== */

        .support-form-input {
            overflow: hidden;
            text-overflow: ellipsis;
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

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
    >


        <!-- ====================================================
             MAIN
        ==================================================== -->

        <main>


            <!-- =================================================
                 HEADER
            ================================================= -->

            <?php include('header.php'); ?>


            <!-- =================================================
                 PAGE CONTENT
            ================================================= -->

            <div class="support-page-container">


                <!-- =============================================
                     PAGE HEADER
                ============================================= -->

                <div class="support-page-header">


                    <h3 class="support-page-title">
                        Edit Support Service Email Data
                    </h3>


                    <i class="support-page-description">
                        Configure your support service email credentials
                        to enable reliable communication with your
                        users/clients.
                    </i>


                    <!-- =========================================
                         BREADCRUMB
                    ========================================= -->

                    <nav class="support-breadcrumb">

                        <ol>

                            <li>

                                <a href="index.php">

                                    Home

                                    <svg
                                        class="stroke-current"
                                        width="17"
                                        height="16"
                                        viewBox="0 0 17 16"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >

                                        <path
                                            d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                            stroke=""
                                            stroke-width="1.2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />

                                    </svg>

                                </a>

                            </li>

                            <li
                                class="text-sm text-gray-800 dark:text-white/90"
                                x-data="{ pageName: '' }"
                                x-text="pageName"
                            ></li>

                        </ol>

                    </nav>

                </div>


                <!-- =============================================
                     FORM CARD
                ============================================= -->

                <div class="support-form-card">


                    <div class="support-form-body">


                        <!-- =====================================
                             FORM
                        ===================================== -->

                        <form
                            method="POST"
                            action="proceed_edit_support.php"
                        >


                            <div class="support-form-grid">


                                <!-- =================================
                                     EMAIL
                                ================================= -->

                                <div class="support-form-group">

                                    <label
                                        for="company_support_email"
                                        class="support-form-label"
                                    >
                                        New Email
                                    </label>


                                    <input
                                        type="email"
                                        id="company_support_email"
                                        name="company_support_email"
                                        value="<?= htmlspecialchars(
                                            $company_support_email
                                        ) ?>"
                                        class="support-form-input"
                                        autocomplete="email"
                                        required
                                    >


                                    <div class="support-helper-text">
                                        Enter the support email address
                                        that should be used for customer
                                        communication.
                                    </div>

                                </div>


                                <!-- =================================
                                     PASSWORD
                                ================================= -->

                                <div class="support-form-group">

                                    <label
                                        for="support_email_password"
                                        class="support-form-label"
                                    >
                                        New Password
                                    </label>


                                    <div class="support-password-wrapper">

                                        <input
                                            type="text"
                                            id="support_email_password"
                                            name="support_email_password"
                                            value="<?= htmlspecialchars(
                                                $support_email_password
                                            ) ?>"
                                            class="support-form-input support-password-input"
                                            autocomplete="off"
                                            required
                                        >

                                    </div>


                                    <div class="support-helper-text">
                                        Enter the password associated with
                                        the support email account.
                                    </div>

                                </div>


                                <!-- =================================
                                     HIDDEN COMPANY ID
                                ================================= -->

                                <input
                                    type="hidden"
                                    name="company_id"
                                    value="<?= htmlspecialchars(
                                        $_SESSION['company_id']
                                    ) ?>"
                                >


                                <!-- =================================
                                     ACTIONS
                                ================================= -->

                                <div
                                    class="support-form-group support-form-group-full"
                                >

                                    <div class="support-form-actions">


                                        <!-- SUBMIT -->

                                        <button
                                            type="submit"
                                            class="support-action-button support-submit-button"
                                        >
                                            <i class="fa-solid fa-save"></i>

                                            Update Support Email
                                        </button>


                                        <!-- CANCEL -->

                                        <a
                                            href="support.php"
                                            class="support-action-button support-cancel-button"
                                        >
                                            <i class="fa-solid fa-arrow-left"></i>

                                            Cancel
                                        </a>


                                    </div>

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
    data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfExtPrI":true,"cfCacheStatus":{"cfExtPri":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
    crossorigin="anonymous"
></script>


</body>

</html>

