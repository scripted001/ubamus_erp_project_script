
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');

date_default_timezone_set('Africa/Lagos');


// ============================================================
// DEFAULT VALUES
// ============================================================

$debt_id         = '';
$customer_name   = '';
$customer_mobile = '';
$customer_email  = '';
$attendant       = '';

$debt = [
    'debt_type'    => '',
    'total_amount' => 0,
    'amount_paid'  => 0,
    'balance'      => 0,
    'due_date'     => '',
    'created_at'   => ''
];


// ============================================================
// HANDLE POST DATA
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $debt_id         = $_POST['debt_id'] ?? '';
    $customer_name   = $_POST['customer_name'] ?? '';
    $customer_mobile = $_POST['customer_mobile'] ?? '';
    $customer_email  = $_POST['customer_email'] ?? '';
    $attendant       = $_POST['attendant'] ?? '';

    // --------------------------------------------------------
    // Fetch debt details
    // --------------------------------------------------------

    if (!empty($debt_id)) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM customer_debts
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$debt_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $debt = $result;
        }
    }
}


// ============================================================
// FETCH COMPANY ID FROM CURRENT USER
// ============================================================

$userId = $_SESSION['user_id'] ?? null;

$companyId = $_SESSION['company_id'] ?? null;

if ($userId && empty($companyId)) {

    $stmt = $pdo->prepare("
        SELECT company_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $companyId = $stmt->fetchColumn();
}


// ============================================================
// VALUES
// ============================================================

$debtType = $debt['debt_type'] ?? '';

$totalAmount = (float)($debt['total_amount'] ?? 0);

$amountPaid = (float)($debt['amount_paid'] ?? 0);

$balance = (float)($debt['balance'] ?? 0);

$dueDate = $debt['due_date'] ?? '';

$createdAt = $debt['created_at'] ?? '';


// ============================================================
// BALANCE STATUS
// ============================================================

if ($balance <= 0) {

    $balanceStatus = 'Cleared';
    $balanceStatusIcon = 'fa-circle-check';

} else {

    $balanceStatus = 'Outstanding';
    $balanceStatusIcon = 'fa-clock';
}


// ============================================================
// PAGE TITLE
// ============================================================

$pageTitle = !empty($company_name)
    ? $company_name . ' | Customer Debt Reminder'
    : 'Customer Debt Reminder';

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
        <?= htmlspecialchars($pageTitle) ?>
    </title>


    <!-- ======================================================
         FONT AWESOME
    ======================================================= -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >


    <!-- ======================================================
         MAIN SYSTEM CSS
    ======================================================= -->

    <link
        href="style.css"
        rel="stylesheet"
    >


    <!-- ======================================================
         PAGE-SPECIFIC FUTURISTIC GLASS UI
    ======================================================= -->

    <style>

        /* =====================================================
           BASE
        ===================================================== */

        :root {

            --glass-bg: rgba(255,255,255,0.78);
            --glass-bg-strong: rgba(255,255,255,0.92);

            --glass-border: rgba(15,23,42,0.08);

            --text-main: #172033;
            --text-secondary: #526071;
            --text-muted: #718096;

            --accent: #2563eb;
            --accent-dark: #1d4ed8;

            --success: #047857;

            --danger: #dc2626;

            --shadow:
                0 18px 55px rgba(15,23,42,0.08);

            --shadow-small:
                0 8px 30px rgba(15,23,42,0.06);

            --radius: 24px;
        }


        /* =====================================================
           PAGE BACKGROUND
        ===================================================== */

        .debt-reminder-page {

            position: relative;

            min-height: 100%;

            background:
                radial-gradient(
                    circle at 8% 10%,
                    rgba(37,99,235,0.07),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 92% 18%,
                    rgba(14,165,233,0.06),
                    transparent 28%
                ),
                linear-gradient(
                    135deg,
                    #f8fafc 0%,
                    #f4f7fb 50%,
                    #eef3f9 100%
                );

            overflow: hidden;
        }


        /* =====================================================
           ABSTRACT BACKGROUND ORBS
        ===================================================== */

        .debt-reminder-page::before {

            content: "";

            position: absolute;

            width: 420px;
            height: 420px;

            top: 80px;
            right: -180px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(37,99,235,0.10),
                    rgba(37,99,235,0.025) 50%,
                    transparent 72%
                );

            pointer-events: none;
        }


        .debt-reminder-page::after {

            content: "";

            position: absolute;

            width: 320px;
            height: 320px;

            bottom: 60px;
            left: -160px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(14,165,233,0.07),
                    transparent 70%
                );

            pointer-events: none;
        }


        /* =====================================================
           CONTENT WRAPPER
        ===================================================== */

        .reminder-container {

            position: relative;

            z-index: 2;

            width: 100%;

            max-width: 1280px;

            margin: 0 auto;

            padding: 24px;
        }


        /* =====================================================
           FUTURISTIC HERO
        ===================================================== */

        .reminder-hero {

            position: relative;

            overflow: hidden;

            border-radius: 28px;

            padding: 28px;

            margin-bottom: 22px;

            color: var(--text-main);

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.88),
                    rgba(255,255,255,0.64)
                );

            border: 1px solid
                rgba(255,255,255,0.95);

            box-shadow: var(--shadow);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);
        }


        .reminder-hero::before {

            content: "";

            position: absolute;

            width: 240px;
            height: 240px;

            right: -80px;
            top: -100px;

            border-radius: 50%;

            border: 1px solid
                rgba(37,99,235,0.10);

            box-shadow:
                0 0 0 30px rgba(37,99,235,0.025),
                0 0 0 60px rgba(37,99,235,0.018);
        }


        .reminder-hero::after {

            content: "";

            position: absolute;

            width: 180px;
            height: 1px;

            right: 80px;
            bottom: 34px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(37,99,235,0.30),
                    transparent
                );

            transform: rotate(-25deg);
        }


        .hero-inner {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 24px;
        }


        .hero-copy {

            min-width: 0;
        }


        .hero-kicker {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 7px 11px;

            margin-bottom: 12px;

            border-radius: 999px;

            background: rgba(37,99,235,0.07);

            border: 1px solid rgba(37,99,235,0.10);

            color: #1d4ed8;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 0.12em;

            text-transform: uppercase;
        }


        .hero-title {

            margin: 0;

            font-size: clamp(25px, 4vw, 38px);

            line-height: 1.1;

            font-weight: 800;

            letter-spacing: -0.03em;

            color: #172033;
        }


        .hero-description {

            max-width: 680px;

            margin-top: 10px;

            color: #5d6878;

            font-size: 14px;

            line-height: 1.7;
        }


        .hero-icon {

            flex: 0 0 auto;

            width: 92px;
            height: 92px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 26px;

            color: #2563eb;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,0.95),
                    rgba(241,245,249,0.72)
                );

            border: 1px solid
                rgba(37,99,235,0.10);

            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.9),
                0 15px 35px rgba(37,99,235,0.08);

            font-size: 36px;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .reminder-breadcrumb {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 7px;

            margin-top: 18px;

            color: #6b7280;

            font-size: 13px;
        }


        .reminder-breadcrumb a {

            color: #2563eb;

            text-decoration: none;

            font-weight: 600;
        }


        .reminder-breadcrumb i {

            font-size: 9px;

            color: #94a3b8;
        }


        /* =====================================================
           MAIN GRID
        ===================================================== */

        .reminder-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 0.9fr)
                minmax(0, 1.1fr);

            gap: 22px;

            align-items: start;
        }


        /* =====================================================
           GLASS CARDS
        ===================================================== */

        .glass-card {

            position: relative;

            overflow: hidden;

            border-radius: var(--radius);

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,0.88),
                    rgba(255,255,255,0.68)
                );

            border: 1px solid
                rgba(255,255,255,0.95);

            box-shadow: var(--shadow);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);
        }


        .glass-card::before {

            content: "";

            position: absolute;

            top: 0;
            left: 0;
            right: 0;

            height: 1px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(255,255,255,0.95),
                    transparent
                );
        }


        .card-header {

            padding: 22px 24px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            border-bottom: 1px solid
                rgba(15,23,42,0.06);
        }


        .card-heading {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .card-heading-icon {

            width: 42px;
            height: 42px;

            flex: 0 0 auto;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 13px;

            color: #2563eb;

            background: rgba(37,99,235,0.07);

            border: 1px solid
                rgba(37,99,235,0.10);
        }


        .card-heading h2 {

            margin: 0;

            color: #172033;

            font-size: 17px;

            font-weight: 800;
        }


        .card-heading p {

            margin: 3px 0 0;

            color: #718096;

            font-size: 12px;
        }


        /* =====================================================
           STATUS BADGE
        ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 10px;

            border-radius: 999px;

            font-size: 11px;

            font-weight: 800;

            color: #047857;

            background: rgba(16,185,129,0.08);

            border: 1px solid
                rgba(16,185,129,0.12);

            white-space: nowrap;
        }


        .status-badge.outstanding {

            color: #b45309;

            background: rgba(245,158,11,0.09);

            border-color: rgba(245,158,11,0.13);
        }


        /* =====================================================
           DEBT DETAILS
        ===================================================== */

        .debt-details {

            padding: 24px;
        }


        .customer-profile {

            display: flex;

            align-items: center;

            gap: 14px;

            padding: 16px;

            margin-bottom: 18px;

            border-radius: 18px;

            background:
                rgba(248,250,252,0.78);

            border: 1px solid
                rgba(15,23,42,0.05);
        }


        .customer-avatar {

            width: 52px;
            height: 52px;

            flex: 0 0 auto;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 16px;

            color: #2563eb;

            background:
                rgba(37,99,235,0.08);

            font-size: 20px;
        }


        .customer-profile h3 {

            margin: 0;

            color: #172033;

            font-size: 16px;

            font-weight: 800;
        }


        .customer-profile p {

            margin: 3px 0 0;

            color: #687588;

            font-size: 12px;

            word-break: break-word;
        }


        /* =====================================================
           INFORMATION ROWS
        ===================================================== */

        .detail-list {

            display: grid;

            gap: 9px;
        }


        .detail-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 16px;

            padding: 12px 13px;

            border-radius: 14px;

            background:
                rgba(248,250,252,0.64);

            border: 1px solid
                rgba(15,23,42,0.045);
        }


        .detail-label {

            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;

            color: #667085;

            font-size: 12px;

            font-weight: 600;
        }


        .detail-label i {

            width: 18px;

            text-align: center;

            color: #64748b;
        }


        .detail-value {

            max-width: 60%;

            text-align: right;

            color: #172033;

            font-size: 13px;

            font-weight: 700;

            word-break: break-word;
        }


        /* =====================================================
           BALANCE HIGHLIGHT
        ===================================================== */

        .balance-box {

            margin-top: 18px;

            padding: 18px;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    rgba(37,99,235,0.065),
                    rgba(255,255,255,0.70)
                );

            border: 1px solid
                rgba(37,99,235,0.10);
        }


        .balance-label {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            color: #64748b;

            font-size: 12px;

            font-weight: 700;
        }


        .balance-value {

            margin-top: 5px;

            color: #172033;

            font-size: clamp(24px, 4vw, 31px);

            line-height: 1.2;

            font-weight: 850;

            letter-spacing: -0.025em;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-body {

            padding: 24px;
        }


        .form-intro {

            padding: 15px 16px;

            margin-bottom: 20px;

            border-radius: 16px;

            background:
                rgba(37,99,235,0.045);

            border: 1px solid
                rgba(37,99,235,0.08);

            color: #526071;

            font-size: 13px;

            line-height: 1.6;
        }


        .form-intro i {

            color: #2563eb;

            margin-right: 7px;
        }


        .field {

            margin-bottom: 18px;
        }


        .field label {

            display: block;

            margin-bottom: 8px;

            color: #374151;

            font-size: 12px;

            font-weight: 800;

            letter-spacing: 0.01em;
        }


        .field label i {

            margin-right: 6px;

            color: #64748b;
        }


        .glass-input {

            width: 100%;

            min-height: 46px;

            box-sizing: border-box;

            padding: 12px 14px;

            border-radius: 13px;

            outline: none;

            color: #172033;

            background:
                rgba(255,255,255,0.72);

            border: 1px solid
                rgba(15,23,42,0.10);

            box-shadow:
                inset 0 1px 2px rgba(15,23,42,0.025);

            font-family: inherit;

            font-size: 13px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }


        .glass-input::placeholder {

            color: #9aa4b2;
        }


        .glass-input:focus {

            background: rgba(255,255,255,0.95);

            border-color:
                rgba(37,99,235,0.38);

            box-shadow:
                0 0 0 4px
                rgba(37,99,235,0.07);
        }


        textarea.glass-input {

            min-height: 220px;

            resize: vertical;

            line-height: 1.7;
        }


        /* =====================================================
           FORM ACTIONS
        ===================================================== */

        .form-actions {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 6px;

            padding-top: 18px;

            border-top: 1px solid
                rgba(15,23,42,0.06);
        }


        .action-btn {

            min-height: 46px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 18px;

            border-radius: 13px;

            border: 1px solid transparent;

            font-family: inherit;

            font-size: 13px;

            font-weight: 800;

            text-decoration: none;

            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }


        .action-btn:hover {

            transform: translateY(-1px);
        }


        .send-btn {

            color: #fff;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            box-shadow:
                0 10px 25px
                rgba(37,99,235,0.18);
        }


        .send-btn:hover {

            box-shadow:
                0 14px 30px
                rgba(37,99,235,0.24);
        }


        .cancel-btn {

            color: #475569;

            background:
                rgba(255,255,255,0.72);

            border-color:
                rgba(15,23,42,0.10);
        }


        .cancel-btn:hover {

            background: #fff;

            color: #172033;
        }


        /* =====================================================
           SMALL SECURITY / SYSTEM FOOTNOTE
        ===================================================== */

        .system-note {

            display: flex;

            align-items: flex-start;

            gap: 9px;

            margin-top: 16px;

            color: #7a8697;

            font-size: 11px;

            line-height: 1.6;
        }


        .system-note i {

            margin-top: 2px;

            color: #64748b;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1024px) {

            .reminder-grid {

                grid-template-columns: 1fr;
            }

            .hero-icon {

                width: 78px;
                height: 78px;

                font-size: 30px;
            }
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 640px) {

            .reminder-container {

                padding: 13px;
            }


            .reminder-hero {

                padding: 20px;

                border-radius: 21px;

                margin-bottom: 15px;
            }


            .hero-inner {

                align-items: flex-start;
            }


            .hero-icon {

                width: 54px;
                height: 54px;

                border-radius: 16px;

                font-size: 21px;
            }


            .hero-title {

                font-size: 25px;
            }


            .hero-description {

                font-size: 12px;

                line-height: 1.6;
            }


            .hero-kicker {

                font-size: 9px;

                padding: 6px 9px;
            }


            .reminder-grid {

                gap: 15px;
            }


            .glass-card {

                border-radius: 20px;
            }


            .card-header {

                padding: 17px;
            }


            .card-heading-icon {

                width: 37px;
                height: 37px;

                border-radius: 11px;
            }


            .card-heading h2 {

                font-size: 15px;
            }


            .card-heading p {

                font-size: 11px;
            }


            .status-badge {

                padding: 6px 8px;

                font-size: 9px;
            }


            .debt-details,
            .form-body {

                padding: 17px;
            }


            .customer-profile {

                padding: 13px;

                border-radius: 15px;
            }


            .customer-avatar {

                width: 45px;
                height: 45px;

                border-radius: 13px;
            }


            .detail-row {

                align-items: flex-start;

                flex-direction: column;

                gap: 5px;

                padding: 11px;
            }


            .detail-value {

                max-width: 100%;

                text-align: left;

                font-size: 12px;
            }


            .balance-box {

                padding: 15px;
            }


            .balance-value {

                font-size: 25px;
            }


            .form-actions {

                flex-direction: column;
            }


            .action-btn {

                width: 100%;
            }


            textarea.glass-input {

                min-height: 200px;
            }


            .reminder-breadcrumb {

                font-size: 11px;
            }
        }


        /* =====================================================
           VERY SMALL DEVICES
        ===================================================== */

        @media (max-width: 380px) {

            .hero-icon {

                display: none;
            }

            .hero-title {

                font-size: 22px;
            }

            .hero-description {

                font-size: 11px;
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


<!-- =========================================================
     PAGE WRAPPER
========================================================= -->

<div class="flex h-screen overflow-hidden">


    <?php include('sidebar.php'); ?>


    <!-- =====================================================
         CONTENT AREA
    ====================================================== -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <main>


            <?php include('header.php'); ?>


            <!-- =================================================
                 FUTURISTIC PAGE
            ================================================== -->

            <div class="debt-reminder-page">


                <div class="reminder-container">


                    <!-- =================================================
                         HERO
                    ================================================== -->

                    <section class="reminder-hero">

                        <div class="hero-inner">


                            <div class="hero-copy">


                                <div class="hero-kicker">

                                    <i class="fa-solid fa-shield-halved"></i>

                                    Debt Management System

                                </div>


                                <h1 class="hero-title">

                                    Customer Debt Reminder

                                </h1>


                                <p class="hero-description">

                                    Review the outstanding account details
                                    and prepare a clear, professional payment
                                    reminder for the customer.

                                </p>


                                <div class="reminder-breadcrumb">

                                    <a href="index.php">

                                        <i class="fa-solid fa-house"></i>
                                        Home

                                    </a>

                                    <i class="fa-solid fa-chevron-right"></i>

                                    <span>
                                        Customer Debts
                                    </span>

                                    <i class="fa-solid fa-chevron-right"></i>

                                    <span>
                                        Reminder
                                    </span>

                                </div>


                            </div>


                            <div class="hero-icon">

                                <i class="fa-solid fa-envelope-open-text"></i>

                            </div>


                        </div>

                    </section>



                    <!-- =================================================
                         MAIN CONTENT GRID
                    ================================================== -->

                    <div class="reminder-grid">


                        <!-- =============================================
                             DEBT DETAILS
                        ============================================== -->

                        <section class="glass-card">


                            <div class="card-header">


                                <div class="card-heading">

                                    <div class="card-heading-icon">

                                        <i class="fa-solid fa-file-invoice-dollar"></i>

                                    </div>


                                    <div>

                                        <h2>
                                            Debt Details
                                        </h2>

                                        <p>
                                            Account information
                                        </p>

                                    </div>

                                </div>


                                <div
                                    class="status-badge <?= $balance > 0 ? 'outstanding' : '' ?>"
                                >

                                    <i class="fa-solid <?= $balanceStatusIcon ?>"></i>

                                    <?= htmlspecialchars($balanceStatus) ?>

                                </div>


                            </div>


                            <div class="debt-details">


                                <!-- CUSTOMER -->

                                <div class="customer-profile">


                                    <div class="customer-avatar">

                                        <i class="fa-solid fa-user"></i>

                                    </div>


                                    <div>

                                        <h3>

                                            <?= htmlspecialchars(
                                                $customer_name ?: 'Customer'
                                            ) ?>

                                        </h3>


                                        <p>

                                            <?= htmlspecialchars(
                                                $customer_email ?: 'No email supplied'
                                            ) ?>

                                        </p>

                                    </div>


                                </div>


                                <!-- DETAILS -->

                                <div class="detail-list">


                                    <!-- MOBILE -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-phone"></i>

                                            Mobile

                                        </div>

                                        <div class="detail-value">

                                            <?= htmlspecialchars(
                                                $customer_mobile ?: 'Not supplied'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- EMAIL -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-envelope"></i>

                                            Email

                                        </div>

                                        <div class="detail-value">

                                            <?= htmlspecialchars(
                                                $customer_email ?: 'Not supplied'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- TRANSACTION -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-receipt"></i>

                                            Transaction

                                        </div>

                                        <div class="detail-value">

                                            <?= htmlspecialchars(
                                                $debtType ?: 'Not specified'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- TOTAL -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-naira-sign"></i>

                                            Transaction Amount

                                        </div>

                                        <div class="detail-value">

                                            ₦<?= number_format(
                                                $totalAmount,
                                                2
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- PAID -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-circle-check"></i>

                                            Deposited

                                        </div>

                                        <div class="detail-value">

                                            ₦<?= number_format(
                                                $amountPaid,
                                                2
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- ATTENDANT -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-user-tie"></i>

                                            Attendant

                                        </div>

                                        <div class="detail-value">

                                            <?= htmlspecialchars(
                                                $attendant ?: 'Not specified'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- DUE DATE -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-calendar-days"></i>

                                            Due Date

                                        </div>

                                        <div class="detail-value">

                                            <?= htmlspecialchars(
                                                $dueDate ?: 'Not specified'
                                            ) ?>

                                        </div>

                                    </div>


                                    <!-- REGISTERED -->

                                    <div class="detail-row">

                                        <div class="detail-label">

                                            <i class="fa-solid fa-clock-rotate-left"></i>

                                            Registered

                                        </div>

                                        <div class="detail-value">

                                            <?= htmlspecialchars(
                                                $createdAt ?: 'Not specified'
                                            ) ?>

                                        </div>

                                    </div>


                                </div>


                                <!-- BALANCE -->

                                <div class="balance-box">


                                    <div class="balance-label">

                                        <span>

                                            <i class="fa-solid fa-wallet"></i>

                                            Outstanding Balance

                                        </span>


                                        <span>

                                            <?= $balance > 0
                                                ? 'Payment Required'
                                                : 'Account Cleared'
                                            ?>

                                        </span>

                                    </div>


                                    <div class="balance-value">

                                        ₦<?= number_format(
                                            $balance,
                                            2
                                        ) ?>

                                    </div>


                                </div>


                            </div>


                        </section>



                        <!-- =============================================
                             EMAIL COMPOSER
                        ============================================== -->

                        <section class="glass-card">


                            <div class="card-header">


                                <div class="card-heading">

                                    <div class="card-heading-icon">

                                        <i class="fa-solid fa-paper-plane"></i>

                                    </div>


                                    <div>

                                        <h2>
                                            Send Reminder
                                        </h2>

                                        <p>
                                            Compose customer notification
                                        </p>

                                    </div>

                                </div>


                            </div>


                            <div class="form-body">


                                <div class="form-intro">

                                    <i class="fa-solid fa-circle-info"></i>

                                    Write a clear and professional reminder
                                    regarding the customer's outstanding
                                    balance. The message will be sent to the
                                    email address supplied for this account.

                                </div>


                                <form
                                    method="POST"
                                    action="proceed_reminder.php"
                                >


                                    <!-- CUSTOMER NAME -->

                                    <input
                                        type="hidden"
                                        name="customer_name"
                                        value="<?= htmlspecialchars(
                                            $customer_name
                                        ) ?>"
                                    >


                                    <!-- CUSTOMER EMAIL -->

                                    <input
                                        type="hidden"
                                        name="customer_email"
                                        value="<?= htmlspecialchars(
                                            $customer_email
                                        ) ?>"
                                    >


                                    <!-- COMPANY -->

                                    <input
                                        type="hidden"
                                        name="company_id"
                                        value="<?= htmlspecialchars(
                                            $companyId ?? ''
                                        ) ?>"
                                    >


                                    <!-- DEBT -->

                                    <input
                                        type="hidden"
                                        name="debt_id"
                                        value="<?= htmlspecialchars(
                                            $debt_id
                                        ) ?>"
                                    >


                                    <!-- SUBJECT -->

                                    <div class="field">


                                        <label for="subject">

                                            <i class="fa-solid fa-heading"></i>

                                            Email Subject

                                        </label>


                                        <input
                                            type="text"
                                            name="subject"
                                            id="subject"
                                            class="glass-input"
                                            placeholder="Enter reminder subject..."
                                            required
                                        >

                                    </div>


                                    <!-- BODY -->

                                    <div class="field">


                                        <label for="body">

                                            <i class="fa-solid fa-align-left"></i>

                                            Message

                                        </label>


                                        <textarea
                                            name="body"
                                            id="body"
                                            class="glass-input"
                                            placeholder="Write your customer debt reminder here..."
                                            required
                                        ></textarea>


                                    </div>


                                    <!-- ACTIONS -->

                                    <div class="form-actions">


                                        <button
                                            type="submit"
                                            class="action-btn send-btn"
                                        >

                                            <i class="fa-solid fa-paper-plane"></i>

                                            Send Reminder

                                        </button>


                                        <a
                                            href="index.php"
                                            class="action-btn cancel-btn"
                                        >

                                            <i class="fa-solid fa-arrow-left"></i>

                                            Cancel

                                        </a>


                                    </div>


                                    <div class="system-note">

                                        <i class="fa-solid fa-lock"></i>

                                        <span>

                                            Customer information is being
                                            handled through your existing
                                            authenticated staff session.

                                        </span>

                                    </div>


                                </form>


                            </div>


                        </section>


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
     EXISTING SYSTEM JAVASCRIPT
========================================================= -->

<script
    defer
    src="bundle.js"
></script>


</body>

</html>