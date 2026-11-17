
<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = (int)($_SESSION['company_id'] ?? 0);

if ($company_id <= 0) {
    echo "<script>alert('Invalid company session.'); window.location.href='index.php';</script>";
    exit;
}

if (!isset($_POST['stock_id']) || (int)$_POST['stock_id'] <= 0) {
    echo "<script>alert('No stock selected for transfer.'); window.history.back();</script>";
    exit;
}

$stock_id = (int)$_POST['stock_id'];


/*
|--------------------------------------------------------------------------
| FETCH STOCK
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        s.id AS stock_id,
        s.product_id,
        s.company_id,
        s.location_id,
        s.quantity,
        p.name AS product_name,
        p.barcode,
        p.cost_price,
        p.selling_price,
        l.name AS current_location
    FROM stocks s

    INNER JOIN products p
        ON p.id = s.product_id
        AND p.company_id = s.company_id

    LEFT JOIN locations l
        ON l.id = s.location_id
        AND l.company_id = s.company_id

    WHERE s.id = ?
      AND s.company_id = ?

    LIMIT 1
");

$stmt->execute([
    $stock_id,
    $company_id
]);

$stock = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stock) {
    echo "<script>alert('Stock record not found or you do not have access to it.'); window.history.back();</script>";
    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH COMPANY LOCATIONS
|--------------------------------------------------------------------------
*/
$stmtLoc = $pdo->prepare("
    SELECT
        id,
        name
    FROM locations
    WHERE company_id = ?
      AND id != ?
    ORDER BY name ASC
");

$stmtLoc->execute([
    $company_id,
    (int)$stock['location_id']
]);

$locations = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VALUES
|--------------------------------------------------------------------------
*/
$product_name   = $stock['product_name'];
$barcode        = $stock['barcode'];
$current_stock  = (int)$stock['quantity'];
$current_location = $stock['current_location'] ?? 'Unassigned';

$cost_price    = (float)$stock['cost_price'];
$selling_price = (float)$stock['selling_price'];

$stock_value = $current_stock * $cost_price;
$sales_value = $current_stock * $selling_price;
$potential_profit = $sales_value - $stock_value;

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
        <?= htmlspecialchars($company_name) ?> | Transfer Stock
    </title>

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >

    <style>

        /* ============================================================
           FUTURISTIC STOCK TRANSFER
        ============================================================ */

        :root {
            --transfer-primary: #4f46e5;
            --transfer-primary-dark: #3730a3;
            --transfer-blue: #2563eb;
            --transfer-cyan: #0891b2;
            --transfer-green: #059669;
            --transfer-text: #111827;
            --transfer-muted: #64748b;
            --transfer-border: rgba(148,163,184,.22);
            --transfer-glass: rgba(255,255,255,.78);
        }


        * {
            box-sizing: border-box;
        }


        body {
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(79,70,229,.07),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 20%,
                    rgba(6,182,212,.07),
                    transparent 25%
                ),
                #f8fafc;
        }


        .transfer-page {
            width: 100%;
            max-width: 1480px;
            margin: 0 auto;
        }


        /* ============================================================
           HERO
        ============================================================ */

        .transfer-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(148,163,184,.20);
            border-radius: 26px;
            padding: 28px;
            margin-bottom: 22px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.94),
                    rgba(248,250,252,.78)
                );

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);

            box-shadow:
                0 18px 45px rgba(15,23,42,.07),
                inset 0 1px 0 rgba(255,255,255,.85);
        }


        .transfer-hero::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(79,70,229,.08);
            filter: blur(4px);
            top: -150px;
            right: -80px;
        }


        .transfer-hero::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(6,182,212,.06);
            bottom: -110px;
            left: -70px;
        }


        .hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }


        .hero-left {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }


        .hero-icon {
            flex: 0 0 62px;
            width: 62px;
            height: 62px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 20px;

            color: white;
            font-size: 25px;

            background:
                linear-gradient(
                    135deg,
                    #4f46e5,
                    #2563eb
                );

            box-shadow:
                0 12px 30px rgba(79,70,229,.25);
        }


        .hero-text {
            min-width: 0;
        }


        .hero-eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 5px;

            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;

            color: var(--transfer-primary);
        }


        .hero-eyebrow span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 5px rgba(34,197,94,.10);
        }


        .hero-title {
            margin: 0;

            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -.035em;

            color: var(--transfer-text);
        }


        .hero-description {
            margin: 8px 0 0;

            max-width: 700px;

            font-size: 14px;
            line-height: 1.65;

            color: var(--transfer-muted);
        }


        .hero-status {
            flex: 0 0 auto;

            display: flex;
            align-items: center;
            gap: 10px;

            padding: 10px 14px;

            border: 1px solid rgba(79,70,229,.12);
            border-radius: 14px;

            background: rgba(255,255,255,.72);

            font-size: 12px;
            font-weight: 700;
            color: #475569;
        }


        .hero-status i {
            color: #22c55e;
        }


        /* ============================================================
           BREADCRUMB
        ============================================================ */

        .transfer-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 18px;

            font-size: 13px;
            color: #64748b;
        }


        .transfer-breadcrumb a {
            color: #64748b;
            text-decoration: none;
            transition: .2s ease;
        }


        .transfer-breadcrumb a:hover {
            color: var(--transfer-primary);
        }


        .transfer-breadcrumb i {
            font-size: 9px;
            color: #94a3b8;
        }


        .transfer-breadcrumb strong {
            color: #334155;
        }


        /* ============================================================
           MAIN GRID
        ============================================================ */

        .transfer-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 370px;
            gap: 22px;
            align-items: start;
        }


        /* ============================================================
           CARD
        ============================================================ */

        .transfer-card {
            position: relative;

            border: 1px solid var(--transfer-border);
            border-radius: 24px;

            background: var(--transfer-glass);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            box-shadow:
                0 16px 40px rgba(15,23,42,.06),
                inset 0 1px 0 rgba(255,255,255,.90);

            overflow: hidden;
        }


        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding: 22px 24px;

            border-bottom: 1px solid rgba(148,163,184,.14);
        }


        .card-header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .card-header-icon {
            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            color: var(--transfer-primary);

            background: rgba(79,70,229,.09);
        }


        .card-header h2 {
            margin: 0;

            font-size: 17px;
            font-weight: 800;

            color: #1e293b;
        }


        .card-header p {
            margin: 3px 0 0;

            font-size: 12px;
            color: #94a3b8;
        }


        .card-body {
            padding: 24px;
        }


        /* ============================================================
           PRODUCT SUMMARY
        ============================================================ */

        .product-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;

            padding: 18px;

            border: 1px solid rgba(79,70,229,.10);
            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    rgba(248,250,252,.90),
                    rgba(255,255,255,.72)
                );

            margin-bottom: 24px;
        }


        .product-label {
            margin-bottom: 5px;

            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;

            color: #94a3b8;
        }


        .product-name {
            margin: 0;

            font-size: 21px;
            line-height: 1.25;
            font-weight: 800;

            color: #0f172a;

            word-break: break-word;
        }


        .product-barcode {
            margin-top: 7px;

            font-size: 12px;
            color: #64748b;
        }


        .stock-badge {
            align-self: center;

            min-width: 115px;

            padding: 12px;

            border-radius: 15px;

            text-align: center;

            background: rgba(34,197,94,.08);
            border: 1px solid rgba(34,197,94,.14);
        }


        .stock-badge span {
            display: block;

            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;

            color: #16a34a;
        }


        .stock-badge strong {
            display: block;

            margin-top: 2px;

            font-size: 23px;
            font-weight: 800;

            color: #166534;
        }


        /* ============================================================
           LOCATION FLOW
        ============================================================ */

        .location-flow {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 54px minmax(0, 1fr);
            align-items: end;
            gap: 14px;

            margin-bottom: 25px;
        }


        .field-label {
            display: block;

            margin-bottom: 8px;

            font-size: 12px;
            font-weight: 700;

            color: #475569;
        }


        .field-label .required {
            color: #ef4444;
        }


        .location-box {
            min-height: 50px;

            display: flex;
            align-items: center;
            gap: 10px;

            padding: 0 14px;

            border: 1px solid #e2e8f0;
            border-radius: 13px;

            background: #f8fafc;

            color: #334155;
            font-size: 14px;
            font-weight: 650;
        }


        .location-box i {
            color: #64748b;
        }


        .flow-arrow {
            width: 46px;
            height: 46px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 2px;

            border-radius: 14px;

            color: var(--transfer-primary);

            background: rgba(79,70,229,.08);
            border: 1px solid rgba(79,70,229,.12);
        }


        .transfer-select,
        .quantity-input {
            width: 100%;
            min-height: 50px;

            padding: 0 14px;

            border: 1px solid #dbe2ea;
            border-radius: 13px;

            outline: none;

            background: rgba(255,255,255,.90);

            color: #1e293b;

            font-size: 14px;
            font-weight: 600;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }


        .transfer-select:focus,
        .quantity-input:focus {
            border-color: rgba(79,70,229,.55);

            box-shadow:
                0 0 0 4px rgba(79,70,229,.09);
        }


        .transfer-select:hover,
        .quantity-input:hover {
            border-color: #cbd5e1;
        }


        /* ============================================================
           QUANTITY SECTION
        ============================================================ */

        .quantity-section {
            padding: 18px;

            border: 1px solid rgba(148,163,184,.17);
            border-radius: 18px;

            background: rgba(248,250,252,.62);
        }


        .quantity-top {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 9px;
        }


        .quantity-top .field-label {
            margin: 0;
        }


        .available-text {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }


        .quantity-input-wrap {
            position: relative;
        }


        .quantity-input {
            height: 58px;
            padding-right: 80px;

            font-size: 20px;
            font-weight: 800;
        }


        .quantity-suffix {
            position: absolute;
            top: 50%;
            right: 15px;

            transform: translateY(-50%);

            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;

            color: #94a3b8;
        }


        .quantity-progress {
            width: 100%;
            height: 6px;

            margin-top: 13px;

            overflow: hidden;

            border-radius: 99px;

            background: #e2e8f0;
        }


        .quantity-progress-bar {
            width: 100%;
            height: 100%;

            border-radius: inherit;

            background:
                linear-gradient(
                    90deg,
                    #4f46e5,
                    #2563eb,
                    #06b6d4
                );

            transition: width .25s ease;
        }


        .quantity-helper {
            display: flex;
            justify-content: space-between;

            margin-top: 7px;

            font-size: 11px;
            color: #94a3b8;
        }


        /* ============================================================
           ACTIONS
        ============================================================ */

        .transfer-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            margin-top: 24px;
            padding-top: 20px;

            border-top: 1px solid rgba(148,163,184,.14);
        }


        .cancel-button,
        .transfer-button {
            min-height: 48px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            padding: 0 20px;

            border-radius: 13px;

            font-size: 13px;
            font-weight: 800;

            text-decoration: none;

            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }


        .cancel-button {
            color: #475569;

            background: #fff;

            border: 1px solid #dbe2ea;
        }


        .cancel-button:hover {
            background: #f8fafc;
        }


        .transfer-button {
            border: 0;

            color: #fff;

            min-width: 190px;

            background:
                linear-gradient(
                    135deg,
                    #4f46e5,
                    #2563eb
                );

            box-shadow:
                0 10px 24px rgba(79,70,229,.22);
        }


        .transfer-button:hover {
            transform: translateY(-2px);

            box-shadow:
                0 15px 30px rgba(79,70,229,.28);
        }


        .transfer-button:active {
            transform: translateY(0);
        }


        /* ============================================================
           SIDE INTELLIGENCE PANEL
        ============================================================ */

        .intel-panel {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }


        .intel-card {
            padding: 20px;

            border: 1px solid var(--transfer-border);
            border-radius: 20px;

            background: rgba(255,255,255,.76);

            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);

            box-shadow:
                0 12px 30px rgba(15,23,42,.05);
        }


        .intel-heading {
            display: flex;
            align-items: center;
            gap: 10px;

            margin-bottom: 17px;
        }


        .intel-heading-icon {
            width: 35px;
            height: 35px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            color: var(--transfer-primary);

            background: rgba(79,70,229,.08);
        }


        .intel-heading h3 {
            margin: 0;

            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
        }


        .intel-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding: 12px 0;

            border-bottom: 1px solid #f1f5f9;
        }


        .intel-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }


        .intel-item:first-of-type {
            padding-top: 0;
        }


        .intel-item-label {
            font-size: 11px;
            color: #94a3b8;
        }


        .intel-item-value {
            text-align: right;

            font-size: 13px;
            font-weight: 800;

            color: #334155;
        }


        .intel-item-value.green {
            color: #059669;
        }


        .intel-item-value.blue {
            color: #2563eb;
        }


        /* ============================================================
           TRANSFER VISUAL
        ============================================================ */

        .transfer-visual {
            position: relative;

            min-height: 185px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            border-radius: 20px;

            background:
                radial-gradient(
                    circle at center,
                    rgba(79,70,229,.10),
                    rgba(248,250,252,.55) 55%,
                    rgba(255,255,255,.8)
                );

            border: 1px solid rgba(79,70,229,.10);
        }


        .visual-ring {
            width: 105px;
            height: 105px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            border: 1px solid rgba(79,70,229,.20);

            box-shadow:
                0 0 0 12px rgba(79,70,229,.035),
                0 0 0 24px rgba(79,70,229,.025);
        }


        .visual-ring i {
            font-size: 30px;
            color: var(--transfer-primary);
        }


        .visual-line {
            position: absolute;

            width: 170px;
            height: 1px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(79,70,229,.25),
                    transparent
                );

            animation: transferPulse 2s ease-in-out infinite;
        }


        @keyframes transferPulse {
            0%, 100% {
                opacity: .3;
                transform: scaleX(.7);
            }

            50% {
                opacity: 1;
                transform: scaleX(1);
            }
        }


        /* ============================================================
           SECURITY NOTICE
        ============================================================ */

        .security-note {
            display: flex;
            align-items: flex-start;
            gap: 11px;

            padding: 14px;

            border-radius: 15px;

            background: rgba(34,197,94,.06);
            border: 1px solid rgba(34,197,94,.11);
        }


        .security-note i {
            margin-top: 2px;

            color: #16a34a;
        }


        .security-note p {
            margin: 0;

            font-size: 11px;
            line-height: 1.6;

            color: #64748b;
        }


        /* ============================================================
           RESPONSIVE
        ============================================================ */

        @media (max-width: 1100px) {

            .transfer-layout {
                grid-template-columns: 1fr;
            }

            .intel-panel {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .transfer-visual {
                min-height: 220px;
            }
        }


        @media (max-width: 760px) {

            .transfer-hero {
                padding: 20px;
                border-radius: 20px;
            }

            .hero-content {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-status {
                width: 100%;
                justify-content: center;
            }

            .hero-icon {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
                border-radius: 16px;
            }

            .transfer-breadcrumb {
                margin-bottom: 13px;
            }

            .card-header {
                padding: 18px;
            }

            .card-body {
                padding: 18px;
            }

            .product-summary {
                grid-template-columns: 1fr;
            }

            .stock-badge {
                width: 100%;
            }

            .location-flow {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .flow-arrow {
                margin: 0 auto;
                transform: rotate(90deg);
            }

            .intel-panel {
                grid-template-columns: 1fr;
            }

            .transfer-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .cancel-button,
            .transfer-button {
                width: 100%;
            }

        }


        @media (max-width: 480px) {

            .hero-left {
                gap: 12px;
            }

            .hero-title {
                font-size: 23px;
            }

            .hero-description {
                font-size: 13px;
            }

            .product-name {
                font-size: 18px;
            }

            .transfer-card {
                border-radius: 18px;
            }

            .intel-card {
                border-radius: 17px;
                padding: 17px;
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


<!-- ================================================================
     PAGE WRAPPER
================================================================ -->

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>


    <!-- ============================================================
         CONTENT AREA
    ============================================================= -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>


            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                <div class="transfer-page">


                    <!-- ==================================================
                         BREADCRUMB
                    ================================================== -->

                    <div class="transfer-breadcrumb">

                        <a href="index.php">
                            Home
                        </a>

                        <i class="fa-solid fa-chevron-right"></i>

                        <a href="stocks.php">
                            Inventory
                        </a>

                        <i class="fa-solid fa-chevron-right"></i>

                        <strong>
                            Transfer Stock
                        </strong>

                    </div>


                    <!-- ==================================================
                         HERO
                    ================================================== -->

                    <section class="transfer-hero">

                        <div class="hero-content">

                            <div class="hero-left">

                                <div class="hero-icon">
                                    <i class="fa-solid fa-right-left"></i>
                                </div>

                                <div class="hero-text">

                                    <div class="hero-eyebrow">
                                        <span></span>
                                        Inventory Intelligence
                                    </div>

                                    <h1 class="hero-title">
                                        Transfer Stock
                                    </h1>

                                    <p class="hero-description">
                                        Move inventory securely between company
                                        locations while maintaining accurate
                                        stock records and transaction history.
                                    </p>

                                </div>

                            </div>


                            <div class="hero-status">
                                <i class="fa-solid fa-shield-check"></i>
                                Controlled Inventory Transfer
                            </div>

                        </div>

                    </section>


                    <!-- ==================================================
                         MAIN CONTENT
                    ================================================== -->

                    <div class="transfer-layout">


                        <!-- ==================================================
                             TRANSFER FORM
                        ================================================== -->

                        <section class="transfer-card">

                            <div class="card-header">

                                <div class="card-header-title">

                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                    </div>

                                    <div>

                                        <h2>
                                            Transfer Configuration
                                        </h2>

                                        <p>
                                            Define where and how much stock to move.
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="card-body">

                                <form
                                    method="POST"
                                    action="process-stock-transfer.php"
                                    id="transferForm"
                                >


                                    <!-- ======================================
                                         PRODUCT
                                    ======================================= -->

                                    <div class="product-summary">

                                        <div>

                                            <div class="product-label">
                                                Selected Inventory
                                            </div>

                                            <h2 class="product-name">
                                                <?= htmlspecialchars($product_name) ?>
                                            </h2>

                                            <?php if (!empty($barcode)): ?>

                                                <div class="product-barcode">
                                                    <i class="fa-solid fa-barcode"></i>
                                                    <?= htmlspecialchars($barcode) ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>


                                        <div class="stock-badge">

                                            <span>
                                                Available
                                            </span>

                                            <strong id="availableStock">
                                                <?= number_format($current_stock) ?>
                                            </strong>

                                        </div>

                                    </div>


                                    <!-- ======================================
                                         LOCATION FLOW
                                    ======================================= -->

                                    <div class="location-flow">


                                        <!-- Current Location -->

                                        <div>

                                            <label class="field-label">
                                                Current Location
                                            </label>

                                            <div class="location-box">

                                                <i class="fa-solid fa-location-dot"></i>

                                                <span>
                                                    <?= htmlspecialchars($current_location) ?>
                                                </span>

                                            </div>

                                        </div>


                                        <!-- Arrow -->

                                        <div class="flow-arrow">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </div>


                                        <!-- Destination -->

                                        <div>

                                            <label
                                                for="to_location_id"
                                                class="field-label"
                                            >
                                                Transfer To
                                                <span class="required">*</span>
                                            </label>

                                            <select
                                                name="to_location_id"
                                                id="to_location_id"
                                                required
                                                class="transfer-select"
                                            >

                                                <option
                                                    value=""
                                                    selected
                                                    disabled
                                                >
                                                    Select destination
                                                </option>

                                                <?php foreach ($locations as $loc): ?>

                                                    <option
                                                        value="<?= (int)$loc['id'] ?>"
                                                    >
                                                        <?= htmlspecialchars($loc['name']) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                    </div>


                                    <!-- ======================================
                                         QUANTITY
                                    ======================================= -->

                                    <div class="quantity-section">

                                        <div class="quantity-top">

                                            <label
                                                for="quantity"
                                                class="field-label"
                                            >
                                                Transfer Quantity
                                                <span class="required">*</span>
                                            </label>

                                            <span class="available-text">
                                                Max:
                                                <?= number_format($current_stock) ?>
                                            </span>

                                        </div>


                                        <div class="quantity-input-wrap">

                                            <input
                                                type="number"
                                                name="quantity"
                                                id="quantity"
                                                min="1"
                                                max="<?= $current_stock ?>"
                                                value="<?= $current_stock ?>"
                                                required
                                                class="quantity-input"
                                            >

                                            <span class="quantity-suffix">
                                                Units
                                            </span>

                                        </div>


                                        <div class="quantity-progress">

                                            <div
                                                class="quantity-progress-bar"
                                                id="quantityProgress"
                                                style="width:100%;"
                                            ></div>

                                        </div>


                                        <div class="quantity-helper">

                                            <span>
                                                1 unit minimum
                                            </span>

                                            <span id="remainingText">
                                                <?= number_format($current_stock) ?>
                                                units selected
                                            </span>

                                        </div>

                                    </div>


                                    <!-- ======================================
                                         HIDDEN FIELDS
                                    ======================================= -->

                                    <input
                                        type="hidden"
                                        name="stock_id"
                                        value="<?= (int)$stock['stock_id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="company_id"
                                        value="<?= $company_id ?>"
                                    >


                                    <!-- ======================================
                                         ACTIONS
                                    ======================================= -->

                                    <div class="transfer-actions">

                                        <a
                                            href="stocks.php"
                                            class="cancel-button"
                                        >
                                            <i class="fa-solid fa-arrow-left"></i>
                                            Cancel
                                        </a>


                                        <button
                                            type="submit"
                                            class="transfer-button"
                                        >
                                            <i class="fa-solid fa-paper-plane"></i>
                                            Transfer Stock
                                        </button>

                                    </div>


                                </form>

                            </div>

                        </section>


                        <!-- ==================================================
                             INTELLIGENCE PANEL
                        ================================================== -->

                        <aside class="intel-panel">


                            <!-- ==============================================
                                 VISUAL
                            =============================================== -->

                            <div class="intel-card transfer-visual">

                                <div class="visual-line"></div>

                                <div class="visual-ring">

                                    <i class="fa-solid fa-arrows-left-right"></i>

                                </div>

                            </div>


                            <!-- ==============================================
                                 STOCK INTELLIGENCE
                            =============================================== -->

                            <div class="intel-card">

                                <div class="intel-heading">

                                    <div class="intel-heading-icon">
                                        <i class="fa-solid fa-chart-simple"></i>
                                    </div>

                                    <h3>
                                        Stock Intelligence
                                    </h3>

                                </div>


                                <div class="intel-item">

                                    <span class="intel-item-label">
                                        Available Units
                                    </span>

                                    <span class="intel-item-value blue">
                                        <?= number_format($current_stock) ?>
                                    </span>

                                </div>


                                <div class="intel-item">

                                    <span class="intel-item-label">
                                        Unit Cost
                                    </span>

                                    <span class="intel-item-value">
                                        ₦<?= number_format($cost_price, 2) ?>
                                    </span>

                                </div>


                                <div class="intel-item">

                                    <span class="intel-item-label">
                                        Unit Selling Price
                                    </span>

                                    <span class="intel-item-value">
                                        ₦<?= number_format($selling_price, 2) ?>
                                    </span>

                                </div>


                                <div class="intel-item">

                                    <span class="intel-item-label">
                                        Current Capital Value
                                    </span>

                                    <span class="intel-item-value">
                                        ₦<?= number_format($stock_value, 2) ?>
                                    </span>

                                </div>


                                <div class="intel-item">

                                    <span class="intel-item-label">
                                        Potential Profit
                                    </span>

                                    <span class="intel-item-value green">
                                        ₦<?= number_format($potential_profit, 2) ?>
                                    </span>

                                </div>

                            </div>


                            <!-- ==============================================
                                 SECURITY
                            =============================================== -->

                            <div class="intel-card">

                                <div class="security-note">

                                    <i class="fa-solid fa-circle-check"></i>

                                    <p>
                                        Transfers are recorded against your
                                        company's inventory and can be traced
                                        through the stock movement history.
                                    </p>

                                </div>

                            </div>

                        </aside>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


<?php include('footer.php'); ?>


<!-- ================================================================
     EXISTING BUNDLE
================================================================ -->

<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script
    defer
    src="bundle.js"
></script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const quantityInput =
        document.getElementById('quantity');

    const progressBar =
        document.getElementById('quantityProgress');

    const remainingText =
        document.getElementById('remainingText');

    const maxQuantity =
        <?= (int)$current_stock ?>;


    function updateQuantityUI() {

        let quantity =
            parseInt(quantityInput.value, 10) || 0;


        /*
        |--------------------------------------------------------------------------
        | Keep quantity inside valid range
        |--------------------------------------------------------------------------
        */

        if (quantity < 1) {
            quantity = 1;
            quantityInput.value = 1;
        }

        if (quantity > maxQuantity) {
            quantity = maxQuantity;
            quantityInput.value = maxQuantity;
        }


        /*
        |--------------------------------------------------------------------------
        | Progress
        |--------------------------------------------------------------------------
        */

        const percentage =
            maxQuantity > 0
                ? (quantity / maxQuantity) * 100
                : 0;


        progressBar.style.width =
            percentage + '%';


        /*
        |--------------------------------------------------------------------------
        | Remaining / selected text
        |--------------------------------------------------------------------------
        */

        remainingText.textContent =
            quantity.toLocaleString() +
            ' unit' +
            (quantity === 1 ? '' : 's') +
            ' selected';

    }


    quantityInput.addEventListener(
        'input',
        updateQuantityUI
    );


    quantityInput.addEventListener(
        'change',
        updateQuantityUI
    );


    updateQuantityUI();


    /*
    |--------------------------------------------------------------------------
    | Prevent same-location destination
    |--------------------------------------------------------------------------
    */

    const destination =
        document.getElementById('to_location_id');

    const currentLocation =
        <?= (int)$stock['location_id'] ?>;


    destination.addEventListener('change', function () {

        if (
            parseInt(this.value, 10) === currentLocation
        ) {

            alert(
                'The destination location must be different from the current location.'
            );

            this.value = '';

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Submit protection
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById('transferForm');

    const submitButton =
        form.querySelector('.transfer-button');


    form.addEventListener('submit', function (event) {

        const quantity =
            parseInt(quantityInput.value, 10) || 0;

        const destinationValue =
            destination.value;


        if (!destinationValue) {

            event.preventDefault();

            alert(
                'Please select a destination location.'
            );

            destination.focus();

            return;

        }


        if (quantity < 1 || quantity > maxQuantity) {

            event.preventDefault();

            alert(
                'Transfer quantity must be between 1 and ' +
                maxQuantity +
                ' units.'
            );

            quantityInput.focus();

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Prevent accidental double submission
        |--------------------------------------------------------------------------
        */

        submitButton.disabled = true;

        submitButton.style.opacity = '0.7';

        submitButton.style.cursor = 'not-allowed';

        submitButton.innerHTML =
            '<i class="fa-solid fa-circle-notch fa-spin"></i>' +
            ' Processing Transfer';

    });

});

</script>

</body>

</html>
