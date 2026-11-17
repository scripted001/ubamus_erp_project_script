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
    />

    <meta
        name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    />

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    />

    <title>
        <?php echo $company_name; ?> | Staff Area
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>

        /* ============================================================
           PRODUCTION INTELLIGENCE — FUTURISTIC UI
        ============================================================ */

        :root {
            --pi-purple: #6839eb;
            --pi-purple-dark: #5125d4;
            --pi-purple-soft: rgba(104, 57, 235, .10);
            --pi-blue: #2563eb;
            --pi-text: #111827;
            --pi-muted: #6b7280;
            --pi-border: rgba(17, 24, 39, .08);
            --pi-glass: rgba(255, 255, 255, .78);
            --pi-shadow: 0 25px 70px rgba(31, 41, 55, .10);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(104, 57, 235, .08),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 20%,
                    rgba(37, 99, 235, .07),
                    transparent 28%
                ),
                #f7f8fc;
        }

        /* ============================================================
           MAIN INTELLIGENCE CONTAINER
        ============================================================ */

        .production-page {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }

        .production-shell {
            position: relative;
            overflow: hidden;
            width: 100%;
            min-height: 520px;
            border: 1px solid var(--pi-border);
            border-radius: 28px;
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.92),
                    rgba(255,255,255,.70)
                );
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: var(--pi-shadow);
        }

        .production-shell::before {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            top: -150px;
            right: -100px;
            border-radius: 50%;
            background: rgba(104, 57, 235, .08);
            filter: blur(5px);
            pointer-events: none;
        }

        .production-shell::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            bottom: -130px;
            left: -90px;
            border-radius: 50%;
            background: rgba(37, 99, 235, .06);
            pointer-events: none;
        }

        /* ============================================================
           TOP HEADER
        ============================================================ */

        .production-header {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 28px 32px;
            border-bottom: 1px solid rgba(17, 24, 39, .07);
        }

        .production-heading {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .production-icon {
            flex: 0 0 auto;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            color: #fff;
            font-size: 21px;
            background:
                linear-gradient(
                    135deg,
                    var(--pi-purple),
                    var(--pi-blue)
                );
            box-shadow:
                0 12px 30px rgba(104, 57, 235, .25);
        }

        .production-title {
            margin: 0;
            font-size: clamp(20px, 2vw, 28px);
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -.03em;
            color: var(--pi-text);
        }

        .production-subtitle {
            margin: 6px 0 0;
            color: var(--pi-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .production-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            padding: 9px 13px;
            border: 1px solid rgba(104, 57, 235, .12);
            border-radius: 999px;
            color: var(--pi-purple);
            background: rgba(104, 57, 235, .07);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .production-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, .10);
        }

        /* ============================================================
           CONTENT
        ============================================================ */

        .production-content {
            position: relative;
            z-index: 2;
            padding: 34px;
        }

        .production-intro {
            max-width: 760px;
            margin-bottom: 28px;
        }

        .production-intro-label {
            margin-bottom: 8px;
            color: var(--pi-purple);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .production-intro-title {
            margin: 0;
            color: #1f2937;
            font-size: clamp(22px, 3vw, 34px);
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .production-intro-text {
            margin: 10px 0 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.7;
        }

        /* ============================================================
           SELECTION CARD
        ============================================================ */

        .production-selector-card {
            max-width: 900px;
            padding: 26px;
            border: 1px solid rgba(17, 24, 39, .07);
            border-radius: 22px;
            background: rgba(255, 255, 255, .72);
            box-shadow:
                0 15px 45px rgba(17, 24, 39, .06);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .selector-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
        }

        .selector-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #374151;
            font-size: 13px;
            font-weight: 750;
        }

        .selector-label i {
            color: var(--pi-purple);
        }

        .selector-helper {
            color: #9ca3af;
            font-size: 11px;
            white-space: nowrap;
        }

        /* ============================================================
           SELECT
        ============================================================ */

        .production-select-wrapper {
            position: relative;
        }

        .production-select {
            width: 100%;
            min-height: 58px;
            padding: 0 52px 0 17px;
            border: 1px solid rgba(17, 24, 39, .10);
            border-radius: 15px;
            outline: none;
            color: #1f2937;
            background: rgba(249, 250, 251, .92);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                transform .2s ease;
        }

        .production-select:hover {
            background: #fff;
            border-color: rgba(104, 57, 235, .25);
        }

        .production-select:focus {
            background: #fff;
            border-color: rgba(104, 57, 235, .60);
            box-shadow:
                0 0 0 4px rgba(104, 57, 235, .10);
        }

        .production-select-arrow {
            position: absolute;
            top: 50%;
            right: 19px;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--pi-purple);
            font-size: 14px;
        }

        /* ============================================================
           ACTION AREA
        ============================================================ */

        .production-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-top: 24px;
        }

        .production-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            max-width: 520px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.6;
        }

        .production-note i {
            margin-top: 2px;
            color: var(--pi-purple);
        }

        .production-next-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 180px;
            min-height: 52px;
            padding: 0 24px;
            border: 0;
            border-radius: 14px;
            color: #fff;
            background:
                linear-gradient(
                    135deg,
                    #6839eb,
                    #5425d2
                );
            box-shadow:
                0 14px 30px rgba(104, 57, 235, .25);
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            overflow: hidden;
            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .production-next-button::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(
                    110deg,
                    transparent 20%,
                    rgba(255,255,255,.20) 50%,
                    transparent 80%
                );
            transform: translateX(-120%);
            transition: transform .55s ease;
        }

        .production-next-button:hover {
            transform: translateY(-2px);
            box-shadow:
                0 18px 38px rgba(104, 57, 235, .32);
        }

        .production-next-button:hover::before {
            transform: translateX(120%);
        }

        .production-next-button span,
        .production-next-button i {
            position: relative;
            z-index: 1;
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */

        @media (max-width: 900px) {

            .production-header {
                padding: 24px;
            }

            .production-content {
                padding: 24px;
            }

            .production-shell {
                border-radius: 23px;
            }

            .production-selector-card {
                max-width: 100%;
            }

        }

        @media (max-width: 640px) {

            body {
                background:
                    radial-gradient(
                        circle at 20% 5%,
                        rgba(104, 57, 235, .08),
                        transparent 35%
                    ),
                    #f7f8fc;
            }

            .production-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 16px;
                padding: 20px;
            }

            .production-heading {
                width: 100%;
            }

            .production-icon {
                width: 46px;
                height: 46px;
                border-radius: 14px;
                font-size: 18px;
            }

            .production-title {
                font-size: 21px;
            }

            .production-subtitle {
                font-size: 12px;
            }

            .production-status {
                align-self: flex-start;
            }

            .production-content {
                padding: 20px;
            }

            .production-intro {
                margin-bottom: 20px;
            }

            .production-intro-title {
                font-size: 25px;
            }

            .production-intro-text {
                font-size: 13px;
            }

            .production-selector-card {
                padding: 18px;
                border-radius: 18px;
            }

            .selector-label-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }

            .selector-helper {
                white-space: normal;
            }

            .production-select {
                min-height: 54px;
                font-size: 13px;
            }

            .production-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .production-note {
                max-width: none;
            }

            .production-next-button {
                width: 100%;
                min-height: 54px;
            }

        }

        @media (max-width: 380px) {

            .production-content,
            .production-header {
                padding: 16px;
            }

            .production-selector-card {
                padding: 15px;
            }

            .production-heading {
                gap: 11px;
            }

            .production-icon {
                width: 42px;
                height: 42px;
            }

            .production-title {
                font-size: 19px;
            }

            .production-intro-title {
                font-size: 22px;
            }

        }

    </style>

</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>

    <!-- ===== Page Wrapper Start ===== -->

    <div class="flex h-screen overflow-hidden">

        <?php include('sidebar.php'); ?>

        <!-- ===== Content Area Start ===== -->

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

            <!-- ===== Main Content Start ===== -->

            <main>

                <?php include('header.php'); ?>

                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                    <div class="production-page">

                        <form
                            method="POST"
                            action="relab.php"
                            id="production_form"
                        >

                            <div class="production-shell">

                                <!-- ====================================================
                                     HEADER
                                ===================================================== -->

                                <div class="production-header">

                                    <div class="production-heading">

                                        <div class="production-icon">
                                            <i class="fa-solid fa-industry"></i>
                                        </div>

                                        <div>

                                            <h1 class="production-title">
                                                Production Intelligence
                                            </h1>

                                            <p class="production-subtitle">
                                                Resume and continue a production process currently in progress.
                                            </p>

                                        </div>

                                    </div>

                                    <div class="production-status">

                                        <span class="production-status-dot"></span>

                                        Production Terminal

                                    </div>

                                </div>


                                <!-- ====================================================
                                     CONTENT
                                ===================================================== -->

                                <div class="production-content">

                                    <div class="production-intro">

                                        <div class="production-intro-label">
                                            Production Control
                                        </div>

                                        <h2 class="production-intro-title">
                                            Pending Production
                                        </h2>

                                        <p class="production-intro-text">
                                            Select an active production batch below to continue
                                            the production workflow and proceed to the next stage.
                                        </p>

                                    </div>


                                    <!-- ====================================================
                                         SELECTION CARD
                                    ===================================================== -->

                                    <div class="production-selector-card">

                                        <div class="selector-label-row">

                                            <label
                                                for="production_id"
                                                class="selector-label"
                                            >
                                                <i class="fa-solid fa-layer-group"></i>

                                                Select Production Batch
                                            </label>

                                            <span class="selector-helper">
                                                Only productions currently in progress are displayed
                                            </span>

                                        </div>


                                        <div class="production-select-wrapper">

                                            <select
                                                name="production_id"
                                                id="production_id"
                                                class="production-select"
                                                required
                                            >

                                                <option value="">
                                                    -- Select Batch Number --
                                                </option>

                                                <?php

                                                    $status = "In Progress";

                                                    $stmt = $pdo->prepare("SELECT * FROM stock_production WHERE company_id = ? AND status = ?");

                                                    $stmt->execute([$companyid, $status]);

                                                    while($production = $stmt->fetch(PDO::FETCH_ASSOC)){

                                                        echo '<option value="'.$production['id'].'">'.$production['product_name'].' ('.$production['batch_number'].')</option>';

                                                    }

                                                ?>

                                            </select>

                                            <span class="production-select-arrow">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </span>

                                        </div>


                                        <!-- ====================================================
                                             ACTION
                                        ===================================================== -->

                                        <div class="production-actions">

                                            <div class="production-note">

                                                <i class="fa-solid fa-circle-info"></i>

                                                <span>
                                                    Choose the production batch you want to continue.
                                                    The selected production will be submitted to the
                                                    existing <strong>relab.php</strong> workflow.
                                                </span>

                                            </div>


                                            <button
                                                type="submit"
                                                name="submit"
                                                class="production-next-button"
                                            >

                                                <span>
                                                    Continue Production
                                                </span>

                                                <i class="fa-solid fa-arrow-right"></i>

                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            </main>

            <!-- ===== Main Content End ===== -->

        </div>

        <!-- ===== Content Area End ===== -->

    </div>

    <!-- ===== Page Wrapper End ===== -->


    <!-- footer session begins -->

    <?php include('footer.php'); ?>

    <!-- footer session ends -->


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
        data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":{"cfExtPri":true},"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
        crossorigin="anonymous"
    ></script>

</body>

</html>