
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


$companyid = $_SESSION['company_id'];


/* ============================================================
   GET CUSTOMER CARE CREDENTIALS
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        company_customer_care,
        customer_care_password
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$companyid]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);


$company_customer_care =
    $company['company_customer_care'] ?? '';

$customer_care_password =
    $company['customer_care_password'] ?? '';

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
        <?= htmlspecialchars($company_name) ?> | Staff Area
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
         CUSTOMER CARE PAGE STYLES
    ========================================================= -->

    <style>

        /* =====================================================
           PAGE CONTAINER
        ===================================================== */

        .customer-care-page {
            width: 100%;
            max-width: 1536px;
            margin: 0 auto;
            padding: 24px;
            box-sizing: border-box;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .customer-care-header {
            width: 100%;
            margin-bottom: 22px;
        }


        .customer-care-title {
            display: flex;
            align-items: flex-start;
            gap: 13px;
        }


        .customer-care-title-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(52, 24, 147, 0.10);
            color: #341893;
            font-size: 18px;
        }


        .customer-care-title-content {
            min-width: 0;
        }


        .customer-care-title-content h3 {
            margin: 0;
            color: #1f2937;
            font-size: 22px;
            line-height: 1.3;
            font-weight: 700;
        }


        .dark .customer-care-title-content h3 {
            color: rgba(255, 255, 255, 0.92);
        }


        .customer-care-description {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }


        .dark .customer-care-description {
            color: #9ca3af;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .customer-care-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
            font-size: 13px;
        }


        .customer-care-breadcrumb a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #6b7280;
            text-decoration: none;
            transition: 0.2s ease;
        }


        .customer-care-breadcrumb a:hover {
            color: #341893;
        }


        .dark .customer-care-breadcrumb a {
            color: #9ca3af;
        }


        .customer-care-breadcrumb .separator {
            color: #9ca3af;
            font-size: 10px;
        }


        .customer-care-breadcrumb .current {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #374151;
            font-weight: 500;
        }


        .dark .customer-care-breadcrumb .current {
            color: rgba(255, 255, 255, 0.82);
        }


        /* =====================================================
           MAIN CARD
        ===================================================== */

        .customer-care-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        }


        .dark .customer-care-card {
            border-color: #1f2937;
            background: rgba(255, 255, 255, 0.03);
            box-shadow: none;
        }


        /* =====================================================
           CARD HEADER
        ===================================================== */

        .customer-care-card-header {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
        }


        .dark .customer-care-card-header {
            border-color: #1f2937;
        }


        .customer-care-card-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: rgba(52, 24, 147, 0.10);
            color: #341893;
            font-size: 17px;
        }


        .customer-care-card-heading {
            min-width: 0;
        }


        .customer-care-card-heading h4 {
            margin: 0;
            color: #1f2937;
            font-size: 16px;
            font-weight: 700;
        }


        .dark .customer-care-card-heading h4 {
            color: rgba(255, 255, 255, 0.92);
        }


        .customer-care-card-heading p {
            margin: 3px 0 0;
            color: #9ca3af;
            font-size: 12px;
        }


        /* =====================================================
           FORM BODY
        ===================================================== */

        .customer-care-form-body {
            padding: 24px;
        }


        .customer-care-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }


        .customer-care-field {
            width: 100%;
            min-width: 0;
        }


        /* =====================================================
           LABEL
        ===================================================== */

        .customer-care-label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }


        .dark .customer-care-label {
            color: #d1d5db;
        }


        .customer-care-label i {
            width: 17px;
            text-align: center;
            color: #341893;
            font-size: 13px;
        }


        /* =====================================================
           INPUT WRAPPER
        ===================================================== */

        .customer-care-input-wrapper {
            position: relative;
            width: 100%;
        }


        .customer-care-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
            pointer-events: none;
            z-index: 1;
        }


        /* =====================================================
           INPUT
        ===================================================== */

        .customer-care-input {
            width: 100%;
            min-height: 47px;
            box-sizing: border-box;
            padding: 11px 42px 11px 40px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #f9fafb;
            color: #1f2937;
            font-size: 14px;
            outline: none;
        }


        .customer-care-input:focus {
            border-color: #341893;
            box-shadow: 0 0 0 3px rgba(52, 24, 147, 0.10);
        }


        .dark .customer-care-input {
            border-color: #374151;
            background: #111827;
            color: rgba(255, 255, 255, 0.9);
        }


        /* =====================================================
           READONLY BADGE
        ===================================================== */

        .readonly-indicator {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 13px;
        }


        /* =====================================================
           FIELD HELP
        ===================================================== */

        .customer-care-help {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-top: 7px;
            color: #9ca3af;
            font-size: 11px;
            line-height: 1.5;
        }


        .customer-care-help i {
            margin-top: 2px;
            color: #341893;
        }


        /* =====================================================
           SECURITY NOTICE
        ===================================================== */

        .customer-care-security {
            grid-column: 1 / -1;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
        }


        .dark .customer-care-security {
            border-color: #374151;
            background: rgba(17, 24, 39, 0.65);
        }


        .customer-care-security-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: rgba(52, 24, 147, 0.10);
            color: #341893;
        }


        .customer-care-security-content {
            min-width: 0;
        }


        .customer-care-security-content strong {
            display: block;
            margin-bottom: 3px;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }


        .dark .customer-care-security-content strong {
            color: #d1d5db;
        }


        .customer-care-security-content span {
            display: block;
            color: #9ca3af;
            font-size: 11px;
            line-height: 1.5;
        }


        /* =====================================================
           FORM FOOTER
        ===================================================== */

        .customer-care-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #f1f5f9;
        }


        .dark .customer-care-footer {
            border-color: #1f2937;
        }


        .customer-care-footer-note {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #9ca3af;
            font-size: 12px;
        }


        .customer-care-footer-note i {
            color: #341893;
        }


        .customer-care-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .customer-care-btn {
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


        .customer-care-btn:hover {
            transform: translateY(-1px);
        }


        .customer-care-btn-primary {
            background: #341893;
            border-color: #341893;
            color: #ffffff;
        }


        .customer-care-btn-primary:hover {
            background: #281275;
            border-color: #281275;
        }


        .customer-care-btn-secondary {
            background: #ffffff;
            border-color: #d1d5db;
            color: #374151;
        }


        .customer-care-btn-secondary:hover {
            background: #f9fafb;
            color: #1f2937;
        }


        .dark .customer-care-btn-secondary {
            background: #1f2937;
            border-color: #374151;
            color: #d1d5db;
        }


        .dark .customer-care-btn-secondary:hover {
            background: #111827;
            color: #ffffff;
        }


        /* =====================================================
           MOBILE / TABLET
        ===================================================== */

        @media (max-width: 768px) {

            .customer-care-page {
                padding: 16px;
            }


            .customer-care-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }


            .customer-care-security {
                grid-column: auto;
            }


            .customer-care-footer {
                flex-direction: column;
                align-items: stretch;
            }


            .customer-care-footer-note {
                width: 100%;
            }


            .customer-care-actions {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }


            .customer-care-btn {
                width: 100%;
            }

        }


        /* =====================================================
           SMALL PHONES
        ===================================================== */

        @media (max-width: 480px) {

            .customer-care-page {
                padding: 10px;
            }


            .customer-care-title {
                gap: 10px;
            }


            .customer-care-title-icon {
                width: 39px;
                height: 39px;
                min-width: 39px;
                font-size: 16px;
            }


            .customer-care-title-content h3 {
                font-size: 18px;
            }


            .customer-care-description {
                font-size: 12px;
            }


            .customer-care-breadcrumb {
                font-size: 12px;
            }


            .customer-care-card {
                border-radius: 13px;
            }


            .customer-care-card-header {
                padding: 15px;
            }


            .customer-care-card-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
                font-size: 16px;
            }


            .customer-care-card-heading h4 {
                font-size: 14px;
            }


            .customer-care-form-body {
                padding: 15px;
            }


            .customer-care-label {
                font-size: 12px;
            }


            .customer-care-input {
                min-height: 45px;
                font-size: 13px;
            }


            .customer-care-security {
                padding: 13px;
            }


            .customer-care-actions {
                grid-template-columns: 1fr;
            }

        }


        /* =====================================================
           VERY SMALL PHONES
        ===================================================== */

        @media (max-width: 360px) {

            .customer-care-page {
                padding: 7px;
            }


            .customer-care-card-header {
                padding: 13px;
            }


            .customer-care-form-body {
                padding: 13px;
            }


            .customer-care-title-content h3 {
                font-size: 16px;
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


            <div class="customer-care-page">


                <!-- =================================================
                     PAGE HEADER
                ================================================= -->

                <div class="customer-care-header">

                    <div class="customer-care-title">

                        <div class="customer-care-title-icon">

                            <i class="fa-solid fa-headset"></i>

                        </div>


                        <div class="customer-care-title-content">

                            <h3>
                                Customer Service Email
                            </h3>


                            <span class="customer-care-description">

                                Configure your customer support email
                                credentials to enable reliable communication
                                with your customers.

                            </span>


                            <!-- Breadcrumb -->

                            <nav class="customer-care-breadcrumb">

                                <a href="index.php">

                                    <i class="fa-solid fa-house"></i>

                                    Home

                                </a>


                                <span class="separator">

                                    <i class="fa-solid fa-chevron-right"></i>

                                </span>


                                <span class="current">

                                    <i class="fa-solid fa-headset"></i>

                                    Customer Service Email

                                </span>

                            </nav>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     MAIN CARD
                ================================================= -->

                <div class="customer-care-card">


                    <!-- =================================================
                         CARD HEADER
                    ================================================= -->

                    <div class="customer-care-card-header">

                        <div class="customer-care-card-icon">

                            <i class="fa-solid fa-envelope-circle-check"></i>

                        </div>


                        <div class="customer-care-card-heading">

                            <h4>
                                Customer Care Credentials
                            </h4>

                            <p>
                                Review and update your customer service email configuration.
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         FORM BODY
                    ================================================= -->

                    <div class="customer-care-form-body">

                        <form
                            method="POST"
                            action="edit_customer_care.php"
                        >


                            <div class="customer-care-grid">


                                <!-- =====================================
                                     CUSTOMER CARE EMAIL
                                ====================================== -->

                                <div class="customer-care-field">

                                    <label
                                        class="customer-care-label"
                                        for="company_customer_care"
                                    >

                                        <i class="fa-solid fa-envelope"></i>

                                        Customer Care Email

                                    </label>


                                    <div class="customer-care-input-wrapper">

                                        <i
                                            class="fa-solid fa-at customer-care-input-icon"
                                        ></i>


                                        <input
                                            type="email"
                                            id="company_customer_care"
                                            name="company_customer_care"
                                            value="<?= htmlspecialchars($company_customer_care) ?>"
                                            class="customer-care-input"
                                            readonly
                                        >


                                        <span class="readonly-indicator">

                                            <i class="fa-solid fa-lock"></i>

                                        </span>

                                    </div>


                                    <div class="customer-care-help">

                                        <i class="fa-solid fa-circle-info"></i>

                                        <span>
                                            This is the email account used for customer support communication.
                                        </span>

                                    </div>

                                </div>


                                <!-- =====================================
                                     CUSTOMER CARE PASSWORD
                                ====================================== -->

                                <div class="customer-care-field">

                                    <label
                                        class="customer-care-label"
                                        for="customer_care_password"
                                    >

                                        <i class="fa-solid fa-key"></i>

                                        Customer Care Password

                                    </label>


                                    <div class="customer-care-input-wrapper">

                                        <i
                                            class="fa-solid fa-shield-halved customer-care-input-icon"
                                        ></i>


                                        <input
                                            type="text"
                                            id="customer_care_password"
                                            name="customer_care_password"
                                            value="<?= htmlspecialchars($customer_care_password) ?>"
                                            class="customer-care-input"
                                            readonly
                                        >


                                        <span class="readonly-indicator">

                                            <i class="fa-solid fa-lock"></i>

                                        </span>

                                    </div>


                                    <div class="customer-care-help">

                                        <i class="fa-solid fa-circle-info"></i>

                                        <span>
                                            The current customer care email password configured for the company.
                                        </span>

                                    </div>

                                </div>


                                <!-- =====================================
                                     SECURITY NOTICE
                                ====================================== -->

                                <div class="customer-care-security">

                                    <div class="customer-care-security-icon">

                                        <i class="fa-solid fa-shield-halved"></i>

                                    </div>


                                    <div class="customer-care-security-content">

                                        <strong>
                                            Customer Support Configuration
                                        </strong>


                                        <span>
                                            These credentials are currently displayed as read-only.
                                            Use the Edit/Update button below to make changes to the
                                            customer care configuration.
                                        </span>

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

                            <div class="customer-care-footer">


                                <div class="customer-care-footer-note">

                                    <i class="fa-solid fa-circle-info"></i>

                                    <span>
                                        Customer support credentials are managed securely.
                                    </span>

                                </div>


                                <div class="customer-care-actions">


                                    <!-- CANCEL -->

                                    <a
                                        href="categories.php"
                                        class="customer-care-btn customer-care-btn-secondary"
                                    >

                                        <i class="fa-solid fa-arrow-left"></i>

                                        Cancel

                                    </a>


                                    <!-- UPDATE -->

                                    <button
                                        type="submit"
                                        class="customer-care-btn customer-care-btn-primary"
                                    >

                                        <i class="fa-solid fa-pen-to-square"></i>

                                        Edit / Update

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

