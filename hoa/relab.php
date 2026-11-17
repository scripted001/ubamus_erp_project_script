<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');

include('../company_data.php');
$companyid = $_SESSION['company_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $production_id  = $_POST['production_id'];


    // SELECT ALL FROM STOCK PRODUCTION
    $stmt = $pdo->prepare("SELECT * FROM stock_production WHERE id=? AND company_id=?");
    $stmt->execute([$production_id, $company_id]);
    $production = $stmt->fetch();

    $previous_product_id  = $production['product_id'];
    $previous_company_id  = $production['company_id'];
    $previous_location_id  = $production['location_id'];
    $previous_batch_number  = $production['batch_number'];
    $previous_product_name  = $production['product_name'];
    $previous_report_number  = $production['report_number'];
    $previous_category_id  = $production['category_id'];
    $previous_quantity_produced  = $production['quantity_produced'];
    $previous_description = $production['description'];
    $previous_unit_cost  = $production['unit_cost'];
    $previous_total_production_cost  = $production['total_cost'];
    $previous_profit_margin  = $production['profit_margin'];
    $previous_unit_selling_price  = $production['unit_selling_price'];
    $previous_expenses_breakdown  = $production['expenses_breakdown'];
    $previous_production_status  = $production['status'];
    $created_by  = $production['created_by'];

    $_SESSION['previous_product_id'] = $previous_product_id;
    $_SESSION['previous_company_id'] = $previous_company_id;
    $_SESSION['previous_location_id'] = $previous_location_id;
    $_SESSION['previous_batch_number'] = $previous_batch_number;
    $_SESSION['previous_product_name'] = $previous_product_name;
    $_SESSION['previous_report_number'] = $previous_report_number;
    $_SESSION['previous_category_id'] = $previous_category_id;
    $_SESSION['previous_quantity_produced'] = $previous_quantity_produced;
    $_SESSION['previous_description'] = $previous_description;
    $_SESSION['previous_unit_cost'] = $previous_unit_cost;
    $_SESSION['previous_total_production_cost'] = $previous_total_production_cost;
    $_SESSION['previous_profit_margin'] = $previous_profit_margin;
    $_SESSION['previous_unit_selling_price'] = $previous_unit_selling_price;
    $_SESSION['previous_expenses_breakdown'] = $previous_expenses_breakdown;
    $_SESSION['previous_production_status'] = $previous_production_status;
    $_SESSION['created_by'] = $created_by;



    // echo $previous_report_number;



}else{
  $production_id = $_SESSION['production_id'];
  
    // SELECT ALL FROM STOCK PRODUCTION
    $stmt = $pdo->prepare("SELECT * FROM stock_production WHERE id=? AND company_id=?");
    $stmt->execute([$production_id, $company_id]);
    $production = $stmt->fetch();

    $previous_product_id  = $production['product_id'];
    $previous_company_id  = $production['company_id'];
    $previous_location_id  = $production['location_id'];
    $previous_batch_number  = $production['batch_number'];
    $previous_product_name  = $production['product_name'];
    $previous_report_number  = $production['report_number'];
    $previous_category_id  = $production['category_id'];
    $previous_quantity_produced  = $production['quantity_produced'];
    $previous_description = $production['description'];
    $previous_unit_cost  = $production['unit_cost'];
    $previous_total_production_cost  = $production['total_cost'];
    $previous_profit_margin  = $production['profit_margin'];
    $previous_unit_selling_price  = $production['unit_selling_price'];
    $previous_expenses_breakdown  = $production['expenses_breakdown'];
    $previous_production_status  = $production['status'];
    $created_by  = $production['created_by'];

    $_SESSION['previous_product_id'] = $previous_product_id;
    $_SESSION['previous_company_id'] = $previous_company_id;
    $_SESSION['previous_location_id'] = $previous_location_id;
    $_SESSION['previous_batch_number'] = $previous_batch_number;
    $_SESSION['previous_product_name'] = $previous_product_name;
    $_SESSION['previous_report_number'] = $previous_report_number;
    $_SESSION['previous_category_id'] = $previous_category_id;
    $_SESSION['previous_quantity_produced'] = $previous_quantity_produced;
    $_SESSION['previous_description'] = $previous_description;
    $_SESSION['previous_unit_cost'] = $previous_unit_cost;
    $_SESSION['previous_total_production_cost'] = $previous_total_production_cost;
    $_SESSION['previous_profit_margin'] = $previous_profit_margin;
    $_SESSION['previous_unit_selling_price'] = $previous_unit_selling_price;
    $_SESSION['previous_expenses_breakdown'] = $previous_expenses_breakdown;
    $_SESSION['previous_production_status'] = $previous_production_status;
    $_SESSION['created_by'] = $created_by;



    // echo $previous_report_number;


}
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

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>
        <?php echo $company_name; ?> | Staff Area
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>

        /* =========================================================
           PRODUCTION INTELLIGENCE / FUTURISTIC UI
           Presentation only — PHP logic untouched
        ========================================================= */

        :root {
            --pi-purple: #6839eb;
            --pi-purple-soft: rgba(104, 57, 235, .10);
            --pi-purple-glow: rgba(104, 57, 235, .22);
            --pi-border: rgba(104, 57, 235, .14);
            --pi-text: #172033;
            --pi-muted: #667085;
            --pi-surface: rgba(255,255,255,.82);
            --pi-surface-strong: #ffffff;
            --pi-bg: #f7f8fc;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            background:
                radial-gradient(
                    circle at 15% 10%,
                    rgba(104,57,235,.07),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 85%,
                    rgba(104,57,235,.055),
                    transparent 28%
                ),
                var(--pi-bg);

            color: var(--pi-text);
        }

        /* Main page shell */

        .production-page {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 20px;
        }

        .production-shell {
            width: 100%;
            position: relative;
            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.94),
                    rgba(255,255,255,.76)
                );

            border: 1px solid rgba(104,57,235,.10);

            border-radius: 28px;

            box-shadow:
                0 25px 80px rgba(15,23,42,.08),
                0 4px 18px rgba(104,57,235,.05);

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .production-shell::before {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            right: -120px;
            top: -140px;

            background: rgba(104,57,235,.07);

            border-radius: 50%;
            filter: blur(5px);

            pointer-events: none;
        }

        .production-shell::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            left: -130px;
            bottom: -120px;

            background: rgba(104,57,235,.045);

            border-radius: 50%;
            filter: blur(5px);

            pointer-events: none;
        }

        /* Header */

        .production-intro {
            position: relative;
            z-index: 2;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

            padding: 28px 30px;

            border-bottom: 1px solid rgba(104,57,235,.09);
        }

        .production-intro-left {
            min-width: 0;
        }

        .production-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 8px;

            color: var(--pi-purple);

            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .production-kicker::before {
            content: "";
            width: 7px;
            height: 7px;

            background: var(--pi-purple);

            border-radius: 50%;

            box-shadow:
                0 0 0 5px rgba(104,57,235,.10),
                0 0 16px rgba(104,57,235,.35);
        }

        .production-title {
            margin: 0;

            color: #151b2b;

            font-size: clamp(25px, 3vw, 38px);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .production-description {
            margin: 10px 0 0;
            max-width: 650px;

            color: var(--pi-muted);

            font-size: 14px;
            line-height: 1.7;
        }

        .production-status-badge {
            flex: 0 0 auto;

            display: inline-flex;
            align-items: center;
            gap: 9px;

            padding: 11px 15px;

            background: rgba(104,57,235,.07);
            border: 1px solid rgba(104,57,235,.12);

            color: var(--pi-purple);

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .production-status-badge i {
            font-size: 10px;
        }

        /* Form */

        .production-form {
            position: relative;
            z-index: 2;

            padding: 30px;
        }

        .production-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(260px, 340px);

            gap: 22px;
        }

        .production-card {
            min-width: 0;

            background: rgba(255,255,255,.78);

            border: 1px solid rgba(104,57,235,.10);

            border-radius: 22px;

            padding: 24px;

            box-shadow:
                0 10px 35px rgba(15,23,42,.045);

            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .production-card.full {
            grid-column: 1 / -1;
        }

        .production-card-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 20px;
        }

        .production-card-heading-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .production-icon {
            width: 42px;
            height: 42px;

            display: grid;
            place-items: center;

            flex: 0 0 42px;

            color: var(--pi-purple);

            background: rgba(104,57,235,.08);

            border: 1px solid rgba(104,57,235,.11);

            border-radius: 13px;
        }

        .production-card-title {
            margin: 0;

            color: #202638;

            font-size: 17px;
            font-weight: 800;
        }

        .production-card-subtitle {
            margin: 3px 0 0;

            color: #8992a5;

            font-size: 12px;
        }

        /* Batch information */

        .batch-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 15px;
        }

        .field {
            min-width: 0;
        }

        .field label {
            display: block;

            margin-bottom: 7px;

            color: #657084;

            font-size: 12px;
            font-weight: 700;
        }

        .field input,
        .field select {

            width: 100%;
            min-height: 48px;

            padding: 12px 14px;

            border: 1px solid rgba(104,57,235,.13);

            border-radius: 13px;

            background: rgba(255,255,255,.85);

            color: #1c2435;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }

        .field input:focus,
        .field select:focus {

            border-color: rgba(104,57,235,.45);

            box-shadow:
                0 0 0 4px rgba(104,57,235,.08),
                0 8px 25px rgba(104,57,235,.06);
        }

        .field input[readonly] {
            background: rgba(104,57,235,.035);
            color: #394156;
        }

        /* Tables */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;

            border: 1px solid rgba(104,57,235,.08);

            border-radius: 17px;

            background: rgba(255,255,255,.65);

            -webkit-overflow-scrolling: touch;
        }

        .production-table {
            width: 100%;
            min-width: 650px;

            border-collapse: separate;
            border-spacing: 0;
        }

        .production-table th {

            padding: 13px 14px;

            background: rgba(104,57,235,.045);

            color: #687287;

            border-bottom: 1px solid rgba(104,57,235,.08);

            font-size: 11px;
            font-weight: 800;

            text-align: left;
            text-transform: uppercase;

            letter-spacing: .07em;

            white-space: nowrap;
        }

        .production-table td {

            padding: 10px;

            border-bottom: 1px solid rgba(104,57,235,.06);

            vertical-align: middle;
        }

        .production-table tr:last-child td {
            border-bottom: none;
        }

        .production-table input {

            width: 100%;
            min-height: 42px;

            padding: 9px 11px;

            border: 1px solid rgba(104,57,235,.10);

            border-radius: 10px;

            background: #fff;

            outline: none;
        }

        .production-table input:focus {

            border-color: rgba(104,57,235,.4);

            box-shadow:
                0 0 0 3px rgba(104,57,235,.07);
        }

        /* Buttons */

        .production-button {

            border: 0;
            outline: none;

            min-height: 44px;

            padding: 10px 17px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            border-radius: 12px;

            background: linear-gradient(
                135deg,
                #6839eb,
                #7650ed
            );

            color: #fff;

            font-size: 13px;
            font-weight: 750;

            cursor: pointer;

            box-shadow:
                0 9px 24px rgba(104,57,235,.18);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .production-button:hover {

            transform: translateY(-2px);

            box-shadow:
                0 13px 30px rgba(104,57,235,.25);
        }

        .production-button:active {
            transform: translateY(0);
        }

        .production-button.remove {

            background: rgba(224,58,58,.08);

            color: #dc3434;

            border: 1px solid rgba(224,58,58,.12);

            box-shadow: none;
        }

        .production-button.remove:hover {

            background: rgba(224,58,58,.12);

            box-shadow: none;
        }

        .add-button {
            margin-top: 14px;
        }

        /* Summary */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 13px;

            margin-bottom: 18px;
        }

        .summary-stat {

            padding: 17px;

            border-radius: 15px;

            background:
                linear-gradient(
                    145deg,
                    rgba(104,57,235,.055),
                    rgba(104,57,235,.018)
                );

            border: 1px solid rgba(104,57,235,.09);
        }

        .summary-stat-label {

            display: block;

            margin-bottom: 7px;

            color: #7a8498;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .05em;
        }

        .summary-stat-value {

            color: #202638;

            font-size: 21px;
            font-weight: 800;
        }

        .financial-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 15px;
        }

        /* Status */

        .status-field {

            margin-top: 20px;
        }

        .status-select {

            width: 100%;

            min-height: 50px;

            padding: 12px 14px;

            border: 1px solid rgba(104,57,235,.13);

            border-radius: 13px;

            background: #fff;

            color: #202638;

            outline: none;
        }

        /* Final action */

        .production-action {

            margin-top: 22px;

            display: flex;

            justify-content: flex-end;
        }

        .render-button {

            min-width: 230px;
            min-height: 52px;

            font-size: 14px;

            border-radius: 15px;
        }

        /* Empty table */

        .empty-production {

            padding: 28px;

            text-align: center;

            color: #8a93a5;

            font-size: 13px;
        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 1000px) {

            .production-grid {
                grid-template-columns: 1fr;
            }

            .production-card.full {
                grid-column: auto;
            }

            .summary-grid,
            .financial-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 700px) {

            .production-page {
                padding: 10px;
            }

            .production-shell {
                border-radius: 20px;
            }

            .production-intro {

                align-items: flex-start;

                flex-direction: column;

                padding: 22px 18px;
            }

            .production-status-badge {
                width: 100%;
                justify-content: center;
            }

            .production-form {
                padding: 14px;
            }

            .production-card {
                padding: 17px;

                border-radius: 18px;
            }

            .batch-grid,
            .summary-grid,
            .financial-grid {

                grid-template-columns: 1fr;
            }

            .production-card-heading {
                align-items: flex-start;
            }

            .production-icon {
                width: 38px;
                height: 38px;
                flex-basis: 38px;
            }

            .production-card-title {
                font-size: 15px;
            }

            .production-description {
                font-size: 13px;
            }

            .table-wrapper {
                border-radius: 13px;
            }

            .production-table {
                min-width: 600px;
            }

            .production-action {
                width: 100%;
            }

            .render-button {
                width: 100%;
                min-width: 0;
            }

        }

        @media (max-width: 420px) {

            .production-page {
                padding: 7px;
            }

            .production-form {
                padding: 9px;
            }

            .production-card {
                padding: 14px;
            }

            .production-title {
                font-size: 25px;
            }

            .production-kicker {
                font-size: 10px;
            }

            .production-button {
                width: 100%;
            }

            .add-button {
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
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

    <!-- ===== Page Wrapper Start ===== -->

    <div class="flex h-screen overflow-hidden">

        <?php include('sidebar.php'); ?>

        <!-- ===== Content Area Start ===== -->

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

            <main>

                <?php include('header.php'); ?>

                <div class="production-page">

                    <section class="production-shell">

                        <!-- =================================================
                             PRODUCTION HEADER
                        ================================================== -->

                        <div class="production-intro">

                            <div class="production-intro-left">

                                <div class="production-kicker">
                                    Production Intelligence
                                </div>

                                <h1 class="production-title">
                                    Production Dashboard
                                </h1>

                                <p class="production-description">
                                    Continue and render an existing production
                                    batch. Review quantity, materials, services,
                                    costing and production status before
                                    finalizing the batch.
                                </p>

                            </div>

                            <div class="production-status-badge">
                                <i class="fa-solid fa-circle"></i>
                                Production Workspace
                            </div>

                        </div>


                        <!-- =================================================
                             FORM
                        ================================================== -->

                        <form
                            method="POST"
                            action="update_production.php"
                            id="production_form"
                            class="production-form"
                        >

                            <div class="production-grid">

                                <!-- =================================================
                                     BATCH INFORMATION
                                ================================================== -->

                                <section class="production-card">

                                    <div class="production-card-heading">

                                        <div class="production-card-heading-left">

                                            <div class="production-icon">
                                                <i class="fa-solid fa-layer-group"></i>
                                            </div>

                                            <div>

                                                <h2 class="production-card-title">
                                                    Batch Information
                                                </h2>

                                                <p class="production-card-subtitle">
                                                    Current production identity
                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="batch-grid">

                                        <div class="field">

                                            <label>
                                                Batch Number
                                            </label>

                                            <input
                                                type="text"
                                                name="batch_number"
                                                value="<?php echo htmlspecialchars($previous_batch_number); ?>"
                                                readonly
                                            >

                                        </div>


                                        <div class="field">

                                            <label>
                                                Product Name
                                            </label>

                                            <input
                                                type="text"
                                                name="product_name"
                                                value="<?php echo htmlspecialchars($previous_product_name); ?>"
                                                readonly
                                            >

                                        </div>


                                        <div class="field">

                                            <label>
                                                Additional Quantity
                                            </label>

                                            <input
                                                type="number"
                                                name="new_quantity_produced"
                                                id="quantity_produced"
                                                placeholder="Enter quantity if increasing production"
                                                min="0"
                                                step="any"
                                            >

                                        </div>

                                    </div>

                                </section>


                                <!-- =================================================
                                     PRODUCTION STATUS
                                ================================================== -->

                                <section class="production-card">

                                    <div class="production-card-heading">

                                        <div class="production-card-heading-left">

                                            <div class="production-icon">
                                                <i class="fa-solid fa-satellite-dish"></i>
                                            </div>

                                            <div>

                                                <h2 class="production-card-title">
                                                    Production State
                                                </h2>

                                                <p class="production-card-subtitle">
                                                    Current batch lifecycle
                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="field">

                                        <label>
                                            Production Status
                                        </label>

                                        <select
                                            name="production_status"
                                            id="production_status"
                                            class="status-select"
                                        >

                                            <option value="">
                                                -- Select Production Status --
                                            </option>

                                            <option value="In Progress">
                                                In Progress
                                            </option>

                                            <option value="Finished">
                                                Finished
                                            </option>

                                            <option value="Cancelled">
                                                Cancelled
                                            </option>

                                        </select>

                                    </div>

                                </section>


                                <!-- =================================================
                                     RAW MATERIALS
                                ================================================== -->

                                <section class="production-card full">

                                    <div class="production-card-heading">

                                        <div class="production-card-heading-left">

                                            <div class="production-icon">
                                                <i class="fa-solid fa-cubes-stacked"></i>
                                            </div>

                                            <div>

                                                <h2 class="production-card-title">
                                                    Raw Materials
                                                </h2>

                                                <p class="production-card-subtitle">
                                                    Materials consumed during production
                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="table-wrapper">

                                        <table class="production-table">

                                            <thead>

                                                <tr>

                                                    <th>
                                                        Material
                                                    </th>

                                                    <th>
                                                        Quantity Used
                                                    </th>

                                                    <th>
                                                        Unit Cost
                                                    </th>

                                                    <th>
                                                        Total Cost
                                                    </th>

                                                    <th>
                                                        Actions
                                                    </th>

                                                </tr>

                                            </thead>

                                            <tbody id="raw_materials_body">

                                                <!-- JS will populate -->

                                            </tbody>

                                        </table>

                                    </div>


                                    <button
                                        type="button"
                                        id="add_raw_material"
                                        class="production-button add-button"
                                    >
                                        <i class="fa-solid fa-plus"></i>
                                        Add Raw Material
                                    </button>

                                </section>


                                <!-- =================================================
                                     SERVICES
                                ================================================== -->

                                <section class="production-card full">

                                    <div class="production-card-heading">

                                        <div class="production-card-heading-left">

                                            <div class="production-icon">
                                                <i class="fa-solid fa-gears"></i>
                                            </div>

                                            <div>

                                                <h2 class="production-card-title">
                                                    Production Services
                                                </h2>

                                                <p class="production-card-subtitle">
                                                    External and operational services
                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="table-wrapper">

                                        <table class="production-table">

                                            <thead>

                                                <tr>

                                                    <th>
                                                        Service
                                                    </th>

                                                    <th>
                                                        Cost
                                                    </th>

                                                    <th>
                                                        Actions
                                                    </th>

                                                </tr>

                                            </thead>

                                            <tbody id="services_body">

                                                <!-- JS will populate -->

                                            </tbody>

                                        </table>

                                    </div>


                                    <button
                                        type="button"
                                        id="add_service"
                                        class="production-button add-button"
                                    >
                                        <i class="fa-solid fa-plus"></i>
                                        Add Service
                                    </button>

                                </section>


                                <!-- =================================================
                                     COST SUMMARY
                                ================================================== -->

                                <section class="production-card full">

                                    <div class="production-card-heading">

                                        <div class="production-card-heading-left">

                                            <div class="production-icon">
                                                <i class="fa-solid fa-chart-line"></i>
                                            </div>

                                            <div>

                                                <h2 class="production-card-title">
                                                    Cost Intelligence
                                                </h2>

                                                <p class="production-card-subtitle">
                                                    Live production costing
                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="summary-grid">

                                        <div class="summary-stat">

                                            <span class="summary-stat-label">
                                                Raw Materials
                                            </span>

                                            <strong
                                                class="summary-stat-value"
                                                id="total_materials_cost"
                                            >
                                                0
                                            </strong>

                                        </div>


                                        <div class="summary-stat">

                                            <span class="summary-stat-label">
                                                Services
                                            </span>

                                            <strong
                                                class="summary-stat-value"
                                                id="total_services_cost"
                                            >
                                                0
                                            </strong>

                                        </div>


                                        <div class="summary-stat">

                                            <span class="summary-stat-label">
                                                Total Production
                                            </span>

                                            <strong
                                                class="summary-stat-value"
                                                id="total_production_cost"
                                            >
                                                0
                                            </strong>

                                        </div>

                                    </div>


                                    <div class="financial-grid">

                                        <div class="field">

                                            <label>
                                                Unit Cost Price
                                            </label>

                                            <input
                                                type="text"
                                                name="unit_cost"
                                                id="unit_cost"
                                                readonly
                                            >

                                        </div>


                                        <div class="field">

                                            <label>
                                                Profit Margin (%)
                                            </label>

                                            <input
                                                type="number"
                                                name="profit_margin"
                                                id="profit_margin"
                                                value="20"
                                                step="0.01"
                                            >

                                        </div>


                                        <div class="field">

                                            <label>
                                                Unit Selling Price
                                            </label>

                                            <input
                                                type="text"
                                                name="unit_selling_price"
                                                id="unit_selling_price"
                                                readonly
                                            >

                                        </div>

                                    </div>

                                </section>


                            </div>


                            <!-- =================================================
                                 FINAL ACTION
                            ================================================== -->

                            <div class="production-action">

                                <button
                                    type="submit"
                                    name="finalize_production"
                                    class="production-button render-button"
                                >

                                    <i class="fa-solid fa-wand-magic-sparkles"></i>

                                    Render Production

                                </button>

                            </div>

                        </form>

                    </section>

                </div>

            </main>

        </div>

    </div>


    <!-- footer -->

    <?php include('footer.php'); ?>


    <script
        data-cfasync="false"
        src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
    ></script>

    <script defer src="bundle.js"></script>


    <!-- =========================================================
         YOUR EXISTING PRODUCTION JAVASCRIPT
         LEFT FUNCTIONALLY INTACT
    ========================================================== -->

    <script>

    let rawMaterialsCount = 0;
    let servicesCount = 0;

    function recalcTotals() {

        let totalMaterials = 0;

        document.querySelectorAll('.rm_total').forEach(el => {

            totalMaterials += parseFloat(el.value || 0);

        });

        document.getElementById('total_materials_cost').innerText =
            totalMaterials.toFixed(2);


        let totalServices = 0;

        document.querySelectorAll('.srv_cost').forEach(el => {

            totalServices += parseFloat(el.value || 0);

        });

        document.getElementById('total_services_cost').innerText =
            totalServices.toFixed(2);


        let totalProduction =
            totalMaterials + totalServices;

        document.getElementById('total_production_cost').innerText =
            totalProduction.toFixed(2);


        let qty =
            parseFloat(
                document.getElementById('quantity_produced').value || 1
            );

        if (qty <= 0) qty = 1;


        let unitCost =
            totalProduction / qty;


        document.getElementById('unit_cost').value =
            unitCost.toFixed(2);


        const status =
            document.getElementById('production_status').value;


        let margin =
            parseFloat(
                document.getElementById('profit_margin').value || 0
            );


        if (status === "In Progress") {

            margin = 0;

        }


        let unitSellingPrice =
            unitCost * (1 + (margin / 100));


        document.getElementById('unit_selling_price').value =
            unitSellingPrice.toFixed(2);

    }


    function handleProductionStatus() {

        const status =
            document.getElementById('production_status').value;

        const marginInput =
            document.getElementById('profit_margin');


        if (status === "In Progress") {

            marginInput.value = 0;

            marginInput.readOnly = true;

            marginInput.style.backgroundColor = "#f3f4f6";

            marginInput.style.cursor = "not-allowed";

        } else {

            marginInput.readOnly = false;

            marginInput.style.backgroundColor = "";

            marginInput.style.cursor = "";

        }


        recalcTotals();

    }


    function addRawMaterial() {

        const tbody =
            document.getElementById('raw_materials_body');

        const index =
            tbody.children.length;


        const row =
            document.createElement('tr');


        row.innerHTML = `

            <td>

                <input
                    type="text"
                    name="raw_materials[${index}][name]"
                    placeholder="Material Name"
                >

            </td>

            <td>

                <input
                    type="number"
                    name="raw_materials[${index}][qty]"
                    class="rm_qty"
                    value="0"
                >

            </td>

            <td>

                <input
                    type="number"
                    name="raw_materials[${index}][unitCost]"
                    class="rm_unit"
                    value="0"
                >

            </td>

            <td>

                <input
                    type="number"
                    class="rm_total"
                    value="0"
                    readonly
                >

            </td>

            <td>

                <button
                    type="button"
                    class="production-button remove remove-btn"
                >

                    <i class="fa-solid fa-trash"></i>

                    Remove

                </button>

            </td>

        `;


        tbody.appendChild(row);


        row.querySelectorAll('input').forEach(inp => {

            inp.addEventListener('input', () => {

                const tr =
                    inp.closest('tr');


                const qty =
                    parseFloat(
                        tr.querySelector('.rm_qty').value || 0
                    );


                const unit =
                    parseFloat(
                        tr.querySelector('.rm_unit').value || 0
                    );


                tr.querySelector('.rm_total').value =
                    (qty * unit).toFixed(2);


                recalcTotals();

            });

        });


        row.querySelector('.remove-btn')
            .addEventListener('click', () => {

                row.remove();

                recalcTotals();


                Array.from(tbody.children)
                    .forEach((r, i) => {

                        r.querySelector(
                            'input[name*="[name]"]'
                        ).name =
                            `raw_materials[${i}][name]`;


                        r.querySelector(
                            'input[name*="[qty]"]'
                        ).name =
                            `raw_materials[${i}][qty]`;


                        r.querySelector(
                            'input[name*="[unitCost]"]'
                        ).name =
                            `raw_materials[${i}][unitCost]`;

                    });

            });

    }


    function addService() {

        const tbody =
            document.getElementById('services_body');

        const index =
            tbody.children.length;


        const row =
            document.createElement('tr');


        row.innerHTML = `

            <td>

                <input
                    type="text"
                    name="services[${index}][name]"
                    class="srv_name"
                    placeholder="Service Name"
                >

            </td>

            <td>

                <input
                    type="number"
                    name="services[${index}][cost]"
                    class="srv_cost"
                    value="0"
                >

            </td>

            <td>

                <button
                    type="button"
                    class="production-button remove remove-btn"
                >

                    <i class="fa-solid fa-trash"></i>

                    Remove

                </button>

            </td>

        `;


        tbody.appendChild(row);


        row.querySelectorAll('input')
            .forEach(inp =>
                inp.addEventListener(
                    'input',
                    recalcTotals
                )
            );


        row.querySelector('.remove-btn')
            .addEventListener('click', () => {

                row.remove();

                recalcTotals();


                Array.from(tbody.children)
                    .forEach((r, i) => {

                        r.querySelector(
                            'input[name*="[name]"]'
                        ).name =
                            `services[${i}][name]`;


                        r.querySelector(
                            'input[name*="[cost]"]'
                        ).name =
                            `services[${i}][cost]`;

                    });

            });

    }


    document
        .getElementById('add_raw_material')
        .addEventListener(
            'click',
            addRawMaterial
        );


    document
        .getElementById('add_service')
        .addEventListener(
            'click',
            addService
        );


    document
        .getElementById('profit_margin')
        .addEventListener(
            'input',
            recalcTotals
        );


    document
        .getElementById('quantity_produced')
        .addEventListener(
            'input',
            recalcTotals
        );


    document
        .getElementById('production_status')
        .addEventListener(
            'change',
            handleProductionStatus
        );


    document
        .getElementById('production_form')
        .addEventListener(
            'submit',
            function(e) {

                const status =
                    document.getElementById(
                        'production_status'
                    ).value;


                const margin =
                    document.getElementById(
                        'profit_margin'
                    ).value.trim();


                if (status === "Finished") {

                    if (
                        margin === "" ||
                        parseFloat(margin) <= 0
                    ) {

                        alert(
                            "Please enter a valid profit margin before completing production."
                        );


                        document
                            .getElementById(
                                'profit_margin'
                            )
                            .focus();


                        e.preventDefault();

                        return;

                    }

                }

            }
        );

    </script>

</body>
</html>

