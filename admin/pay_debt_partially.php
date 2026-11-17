
<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$companyid = $_SESSION['company_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['debt_id'])) {
    header("Location: customer_debts.php");
    exit;
}

$debt_id = (int) $_POST['debt_id'];

$stmt = $pdo->prepare("
    SELECT 
        id,
        total_amount,
        amount_paid,
        balance
    FROM customer_debts
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");

$stmt->execute([$debt_id, $companyid]);
$debt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$debt) {
    die("Debt record not found.");
}

$total_amount = (float) $debt['total_amount'];
$amount_paid  = (float) $debt['amount_paid'];
$balance      = (float) $debt['balance'];

if ($balance <= 0) {
    header("Location: customer_debts.php");
    exit;
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
        <?= htmlspecialchars($company_name) ?> | Pay Debt Installment
    </title>

    <link rel="icon" href="src/images/icons/favicon.png">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <!-- Existing application stylesheet -->
    <link
        href="style.css"
        rel="stylesheet"
    >

    <style>

        /* =========================================================
           FUTURISTIC LIGHT GLASS DEBT PAYMENT PAGE
        ========================================================= */

        :root {
            --glass-bg: rgba(255, 255, 255, 0.76);
            --glass-bg-strong: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(148, 163, 184, 0.25);
            --text-main: #172033;
            --text-muted: #64748b;
            --text-soft: #94a3b8;
            --accent: #2563eb;
            --accent-dark: #1d4ed8;
            --success: #059669;
            --danger: #dc2626;
            --shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(37, 99, 235, 0.08),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 20%,
                    rgba(99, 102, 241, 0.07),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 50% 100%,
                    rgba(14, 165, 233, 0.06),
                    transparent 35%
                ),
                #f8fafc;

            color: var(--text-main);
        }

        /* =========================================================
           PAGE BACKGROUND
        ========================================================= */

        .debt-payment-page {
            position: relative;
            min-height: 100%;
            overflow: hidden;
        }

        .debt-payment-page::before {
            content: "";
            position: fixed;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.045);
            filter: blur(5px);
            top: -160px;
            right: -100px;
            pointer-events: none;
        }

        .debt-payment-page::after {
            content: "";
            position: fixed;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.035);
            bottom: -140px;
            left: -100px;
            pointer-events: none;
        }

        /* =========================================================
           MAIN CONTAINER
        ========================================================= */

        .payment-shell {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }

        /* =========================================================
           FUTURISTIC HERO
        ========================================================= */

        .payment-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 26px;
            padding: 30px;
            margin-bottom: 24px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.90),
                    rgba(248,250,252,0.72)
                );

            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);

            box-shadow: var(--shadow);
        }

        .payment-hero::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border: 1px solid rgba(37,99,235,0.08);
            border-radius: 50%;
            right: -100px;
            top: -170px;
        }

        .payment-hero::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border: 1px solid rgba(37,99,235,0.07);
            border-radius: 50%;
            right: 20px;
            top: -90px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
        }

        .hero-icon {
            width: 54px;
            height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 16px;

            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.14);

            color: var(--accent);
            font-size: 22px;

            margin-bottom: 16px;
        }

        .hero-title {
            margin: 0;
            font-size: clamp(25px, 4vw, 38px);
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #172033;
        }

        .hero-subtitle {
            margin-top: 10px;
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
            max-width: 650px;
        }

        .hero-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            padding: 8px 13px;

            border-radius: 999px;

            background: rgba(5, 150, 105, 0.07);
            border: 1px solid rgba(5, 150, 105, 0.14);

            color: #047857;
            font-size: 12px;
            font-weight: 700;
        }

        .hero-status i {
            font-size: 9px;
        }

        /* =========================================================
           BREADCRUMB
        ========================================================= */

        .payment-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;

            margin-bottom: 20px;

            font-size: 13px;
        }

        .payment-breadcrumb a {
            color: #64748b;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .payment-breadcrumb a:hover {
            color: var(--accent);
        }

        .payment-breadcrumb .separator {
            color: #cbd5e1;
        }

        .payment-breadcrumb .current {
            color: #334155;
            font-weight: 600;
        }

        /* =========================================================
           GLASS CARD
        ========================================================= */

        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;

            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);

            box-shadow: var(--shadow);
        }

        /* =========================================================
           BALANCE OVERVIEW
        ========================================================= */

        .balance-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            padding: 22px;
        }

        .balance-box {
            position: relative;
            overflow: hidden;

            padding: 20px;

            border-radius: 18px;

            background: rgba(255,255,255,0.60);
            border: 1px solid rgba(148,163,184,0.17);
        }

        .balance-box::after {
            content: "";
            position: absolute;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            right: -45px;
            bottom: -45px;

            border: 1px solid rgba(37,99,235,0.06);
        }

        .balance-label {
            display: flex;
            align-items: center;
            gap: 8px;

            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .balance-label i {
            color: #64748b;
        }

        .balance-value {
            margin-top: 8px;

            font-size: clamp(20px, 3vw, 27px);
            line-height: 1.2;
            font-weight: 800;

            color: #172033;
        }

        .balance-box.outstanding {
            background: rgba(37,99,235,0.055);
            border-color: rgba(37,99,235,0.12);
        }

        .balance-box.outstanding .balance-label i {
            color: var(--accent);
        }

        .balance-box.outstanding .balance-value {
            color: #1d4ed8;
        }

        .balance-box.paid .balance-label i {
            color: #059669;
        }

        .balance-box.paid .balance-value {
            color: #047857;
        }

        /* =========================================================
           FORM SECTION
        ========================================================= */

        .form-card {
            margin-top: 20px;
            padding: 26px;
        }

        .section-heading {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding-bottom: 20px;
            margin-bottom: 22px;
            border-bottom: 1px solid rgba(148,163,184,0.16);
        }

        .section-heading-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: rgba(37,99,235,0.07);
            color: var(--accent);
            border: 1px solid rgba(37,99,235,0.12);
        }

        .section-heading h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #172033;
        }

        .section-heading p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        /* =========================================================
           FORM GRID
        ========================================================= */

        .payment-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-field {
            min-width: 0;
        }

        .form-field.full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 8px;

            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .form-label i {
            color: #64748b;
            font-size: 13px;
        }

        .field-wrapper {
            position: relative;
        }

        .field-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);

            color: #94a3b8;
            pointer-events: none;
        }

        .payment-input {
            width: 100%;
            height: 52px;

            border: 1px solid #dbe2ea;
            border-radius: 14px;

            background: rgba(255,255,255,0.78);

            color: #172033;

            padding: 0 16px 0 44px;

            font-size: 15px;
            font-weight: 600;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .payment-input:focus {
            background: #ffffff;
            border-color: rgba(37,99,235,0.45);

            box-shadow:
                0 0 0 4px rgba(37,99,235,0.08);
        }

        .payment-input[readonly] {
            background: rgba(241,245,249,0.72);
            color: #475569;
            cursor: not-allowed;
        }

        .amount-input {
            font-size: 18px;
            color: #172033;
        }

        .field-hint {
            display: flex;
            align-items: center;
            gap: 6px;

            margin-top: 7px;

            color: #94a3b8;
            font-size: 11px;
        }

        /* =========================================================
           PAYMENT PREVIEW
        ========================================================= */

        .payment-preview {
            margin-top: 22px;
            padding: 18px;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    rgba(248,250,252,0.92),
                    rgba(255,255,255,0.66)
                );

            border: 1px solid rgba(148,163,184,0.17);
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;

            margin-bottom: 15px;
        }

        .preview-title {
            display: flex;
            align-items: center;
            gap: 8px;

            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        .preview-title i {
            color: var(--accent);
        }

        .preview-status {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }

        .remaining-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .remaining-label {
            color: #64748b;
            font-size: 13px;
        }

        .remaining-value {
            color: #172033;
            font-size: 20px;
            font-weight: 800;
        }

        .progress-track {
            width: 100%;
            height: 7px;
            margin-top: 14px;

            overflow: hidden;
            border-radius: 999px;

            background: #e2e8f0;
        }

        .progress-bar {
            height: 100%;
            width: 0%;

            border-radius: inherit;

            background: linear-gradient(
                90deg,
                #2563eb,
                #3b82f6
            );

            transition: width 0.25s ease;
        }

        /* =========================================================
           ACTIONS
        ========================================================= */

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;

            gap: 10px;

            margin-top: 25px;
            padding-top: 22px;

            border-top: 1px solid rgba(148,163,184,0.16);
        }

        .payment-btn,
        .cancel-btn {
            min-height: 48px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            padding: 0 20px;

            border-radius: 13px;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;
            cursor: pointer;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .payment-btn {
            border: 1px solid rgba(37,99,235,0.18);

            background: #2563eb;
            color: #ffffff;

            box-shadow:
                0 8px 22px rgba(37,99,235,0.16);
        }

        .payment-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow:
                0 12px 28px rgba(37,99,235,0.22);
        }

        .cancel-btn {
            border: 1px solid #dbe2ea;
            background: rgba(255,255,255,0.75);
            color: #475569;
        }

        .cancel-btn:hover {
            background: #ffffff;
            color: #172033;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {

            .balance-grid {
                grid-template-columns: 1fr;
            }

            .payment-form-grid {
                grid-template-columns: 1fr;
            }

            .form-field.full {
                grid-column: auto;
            }

            .payment-hero {
                padding: 24px;
            }

            .form-card {
                padding: 22px;
            }

        }

        @media (max-width: 640px) {

            .payment-shell {
                width: 100%;
            }

            .payment-hero {
                border-radius: 20px;
                padding: 20px;
                margin-bottom: 18px;
            }

            .hero-icon {
                width: 48px;
                height: 48px;
                border-radius: 14px;
            }

            .hero-title {
                font-size: 25px;
            }

            .hero-subtitle {
                font-size: 13px;
                line-height: 1.6;
            }

            .payment-breadcrumb {
                margin-bottom: 15px;
            }

            .glass-card {
                border-radius: 20px;
            }

            .balance-grid {
                padding: 14px;
                gap: 10px;
            }

            .balance-box {
                padding: 16px;
            }

            .balance-value {
                font-size: 22px;
            }

            .form-card {
                padding: 17px;
                margin-top: 14px;
            }

            .section-heading {
                margin-bottom: 18px;
                padding-bottom: 17px;
            }

            .section-heading h2 {
                font-size: 16px;
            }

            .section-heading p {
                font-size: 12px;
            }

            .payment-input {
                height: 50px;
            }

            .payment-preview {
                padding: 15px;
            }

            .remaining-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }

            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .payment-btn,
            .cancel-btn {
                width: 100%;
                min-height: 50px;
            }

        }

        @media (max-width: 400px) {

            .payment-hero {
                padding: 17px;
            }

            .hero-title {
                font-size: 22px;
            }

            .form-card {
                padding: 14px;
            }

            .balance-value {
                font-size: 20px;
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
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
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

        <main>

            <?php include('header.php'); ?>


            <!-- =================================================
                 PAGE CONTENT
            ================================================= -->

            <div class="debt-payment-page">

                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                    <div class="payment-shell">


                        <!-- =================================================
                             BREADCRUMB
                        ================================================= -->

                        <div class="payment-breadcrumb">

                            <a href="index.php">
                                <i class="fa-solid fa-house"></i>
                                Home
                            </a>

                            <span class="separator">
                                <i class="fa-solid fa-chevron-right"></i>
                            </span>

                            <a href="customer_debts.php">
                                Customer Debts
                            </a>

                            <span class="separator">
                                <i class="fa-solid fa-chevron-right"></i>
                            </span>

                            <span class="current">
                                Installment Payment
                            </span>

                        </div>


                        <!-- =================================================
                             FUTURISTIC HERO
                        ================================================= -->

                        <section class="payment-hero">

                            <div class="hero-content">

                                <div class="hero-icon">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>

                                <h1 class="hero-title">
                                    Pay Debt Installmentally
                                </h1>

                                <p class="hero-subtitle">
                                    Apply a partial payment to the outstanding
                                    customer debt. Enter the amount you want
                                    to pay and the remaining balance will be
                                    calculated automatically.
                                </p>

                                <div class="hero-status">
                                    <i class="fa-solid fa-circle"></i>
                                    Debt payment channel active
                                </div>

                            </div>

                        </section>


                        <!-- =================================================
                             BALANCE OVERVIEW
                        ================================================= -->

                        <section class="glass-card">

                            <div class="balance-grid">

                                <!-- TOTAL -->
                                <div class="balance-box">

                                    <div class="balance-label">
                                        <i class="fa-solid fa-receipt"></i>
                                        Total Transaction
                                    </div>

                                    <div class="balance-value">
                                        ₦<?= number_format($total_amount, 2) ?>
                                    </div>

                                </div>


                                <!-- PAID -->
                                <div class="balance-box paid">

                                    <div class="balance-label">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Already Paid
                                    </div>

                                    <div class="balance-value">
                                        ₦<?= number_format($amount_paid, 2) ?>
                                    </div>

                                </div>


                                <!-- OUTSTANDING -->
                                <div class="balance-box outstanding">

                                    <div class="balance-label">
                                        <i class="fa-solid fa-hourglass-half"></i>
                                        Outstanding Balance
                                    </div>

                                    <div
                                        class="balance-value"
                                        id="outstandingBalance"
                                        data-balance="<?= htmlspecialchars($balance) ?>"
                                    >
                                        ₦<?= number_format($balance, 2) ?>
                                    </div>

                                </div>

                            </div>

                        </section>


                        <!-- =================================================
                             PAYMENT FORM
                        ================================================= -->

                        <section class="glass-card form-card">

                            <div class="section-heading">

                                <div class="section-heading-icon">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </div>

                                <div>

                                    <h2>
                                        Record Installment Payment
                                    </h2>

                                    <p>
                                        Enter the amount received from the customer.
                                    </p>

                                </div>

                            </div>


                            <form
                                method="POST"
                                action="proceed_pay_debt_partially.php"
                                id="partialPaymentForm"
                            >

                                <div class="payment-form-grid">


                                    <!-- =================================================
                                         BALANCE
                                    ================================================= -->

                                    <div class="form-field">

                                        <label class="form-label">

                                            <i class="fa-solid fa-scale-balanced"></i>

                                            Outstanding Balance

                                        </label>

                                        <div class="field-wrapper">

                                            <i class="fa-solid fa-naira-sign field-icon"></i>

                                            <input
                                                type="text"
                                                value="<?= number_format($balance, 2) ?>"
                                                class="payment-input"
                                                readonly
                                            >

                                        </div>

                                        <div class="field-hint">

                                            <i class="fa-solid fa-lock"></i>

                                            Current debt balance

                                        </div>

                                    </div>


                                    <!-- =================================================
                                         PAYMENT AMOUNT
                                    ================================================= -->

                                    <div class="form-field">

                                        <label
                                            for="paymentAmount"
                                            class="form-label"
                                        >

                                            <i class="fa-solid fa-hand-holding-dollar"></i>

                                            Payment Amount

                                        </label>

                                        <div class="field-wrapper">

                                            <i class="fa-solid fa-naira-sign field-icon"></i>

                                            <input
                                                type="number"
                                                name="amount"
                                                id="paymentAmount"
                                                min="0.01"
                                                max="<?= htmlspecialchars($balance) ?>"
                                                step="0.01"
                                                placeholder="Enter amount paid"
                                                class="payment-input amount-input"
                                                required
                                                autocomplete="off"
                                            >

                                        </div>

                                        <div
                                            class="field-hint"
                                            id="amountHint"
                                        >

                                            <i class="fa-solid fa-circle-info"></i>

                                            Maximum payment:
                                            ₦<?= number_format($balance, 2) ?>

                                        </div>

                                    </div>


                                    <!-- =================================================
                                         HIDDEN DATA
                                    ================================================= -->

                                    <input
                                        type="hidden"
                                        name="balance"
                                        value="<?= htmlspecialchars($balance) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="debt_id"
                                        value="<?= htmlspecialchars($debt['id']) ?>"
                                    >


                                </div>


                                <!-- =================================================
                                     PAYMENT PREVIEW
                                ================================================= -->

                                <div class="payment-preview">

                                    <div class="preview-header">

                                        <div class="preview-title">

                                            <i class="fa-solid fa-chart-line"></i>

                                            Payment Projection

                                        </div>

                                        <div
                                            class="preview-status"
                                            id="previewStatus"
                                        >
                                            Awaiting amount
                                        </div>

                                    </div>


                                    <div class="remaining-row">

                                        <span class="remaining-label">
                                            Remaining balance after payment
                                        </span>

                                        <span
                                            class="remaining-value"
                                            id="remainingBalance"
                                        >
                                            ₦<?= number_format($balance, 2) ?>
                                        </span>

                                    </div>


                                    <div class="progress-track">

                                        <div
                                            class="progress-bar"
                                            id="paymentProgress"
                                        ></div>

                                    </div>

                                </div>


                                <!-- =================================================
                                     ACTION BUTTONS
                                ================================================= -->

                                <div class="form-actions">

                                    <a
                                        href="customer_debts.php"
                                        class="cancel-btn"
                                    >

                                        <i class="fa-solid fa-arrow-left"></i>

                                        Cancel

                                    </a>


                                    <button
                                        type="submit"
                                        class="payment-btn"
                                        id="submitPayment"
                                    >

                                        <i class="fa-solid fa-check"></i>

                                        Record Payment

                                    </button>

                                </div>

                            </form>

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


<!-- Existing application JS -->
<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script
    defer
    src="bundle.js"
></script>


<!-- =========================================================
     PAYMENT CALCULATOR
========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const amountInput =
        document.getElementById('paymentAmount');

    const remainingBalance =
        document.getElementById('remainingBalance');

    const paymentProgress =
        document.getElementById('paymentProgress');

    const previewStatus =
        document.getElementById('previewStatus');

    const submitButton =
        document.getElementById('submitPayment');

    const amountHint =
        document.getElementById('amountHint');

    const outstandingElement =
        document.getElementById('outstandingBalance');


    if (
        !amountInput ||
        !remainingBalance ||
        !paymentProgress ||
        !previewStatus ||
        !submitButton ||
        !outstandingElement
    ) {
        return;
    }


    const balance =
        parseFloat(
            outstandingElement.dataset.balance
        ) || 0;


    function formatCurrency(value) {

        return new Intl.NumberFormat(
            'en-NG',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(value);

    }


    function updatePaymentPreview() {

        let amount =
            parseFloat(amountInput.value) || 0;


        /*
         * Prevent negative values
         */
        if (amount < 0) {

            amount = 0;
            amountInput.value = '';

        }


        /*
         * Don't allow amount greater than balance
         */
        if (amount > balance) {

            amount = balance;

            amountInput.value =
                balance.toFixed(2);

        }


        const remaining =
            Math.max(balance - amount, 0);


        const percentage =
            balance > 0
                ? Math.min((amount / balance) * 100, 100)
                : 0;


        remainingBalance.textContent =
            '₦' + formatCurrency(remaining);


        paymentProgress.style.width =
            percentage + '%';


        /*
         * Status
         */

        if (amount <= 0) {

            previewStatus.textContent =
                'Awaiting amount';

            submitButton.disabled = true;
            submitButton.style.opacity = '0.55';
            submitButton.style.cursor = 'not-allowed';

        } else if (amount >= balance) {

            previewStatus.textContent =
                'Full balance will be cleared';

            submitButton.disabled = false;
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';

        } else {

            previewStatus.textContent =
                'Installment payment ready';

            submitButton.disabled = false;
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';

        }


        /*
         * Update helper text
         */

        if (amount > balance) {

            amountHint.innerHTML =
                '<i class="fa-solid fa-triangle-exclamation"></i>' +
                ' Amount cannot exceed the outstanding balance.';

        } else {

            amountHint.innerHTML =
                '<i class="fa-solid fa-circle-info"></i>' +
                ' Maximum payment: ₦' +
                formatCurrency(balance);

        }

    }


    amountInput.addEventListener(
        'input',
        updatePaymentPreview
    );


    amountInput.addEventListener(
        'change',
        updatePaymentPreview
    );


    /*
     * Final validation before submission
     */

    document
        .getElementById('partialPaymentForm')
        .addEventListener('submit', function (event) {

            const amount =
                parseFloat(amountInput.value) || 0;


            if (amount <= 0) {

                event.preventDefault();

                alert(
                    'Please enter a valid payment amount.'
                );

                amountInput.focus();

                return;
            }


            if (amount > balance) {

                event.preventDefault();

                alert(
                    'Payment cannot be greater than the outstanding balance.'
                );

                amountInput.focus();

                return;
            }


            /*
             * Prevent accidental double submission
             */

            submitButton.disabled = true;

            submitButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i>' +
                ' Processing...';

        });


    /*
     * Initial state
     */

    updatePaymentPreview();

});

</script>


</body>

</html>
