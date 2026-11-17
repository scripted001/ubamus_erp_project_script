<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

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
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?php echo htmlspecialchars($company_name); ?> | Staff Area
    </title>

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

    <style>

        /* ============================================================
           GLOBAL RESPONSIVE FOUNDATION
        ============================================================ */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        body {
            min-width: 0;
        }


        /* ============================================================
           HEADER MUST STAY BEHIND SIDEBAR
        ============================================================ */

        header {
            position: sticky !important;
            top: 0;
            z-index: 1000 !important;
        }

        /*
         * Your sidebar uses z-9999.
         * This intentionally keeps the sidebar above the header.
         */
        .futuristic-sidebar,
        aside.sidebar {
            z-index: 9999 !important;
        }


        /* ============================================================
           MAIN APPLICATION AREA
        ============================================================ */

        .page-shell {
            width: 100%;
            min-width: 0;
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
        }

        .main-content-area {
            width: 100%;
            min-width: 0;
        }
        .add-product-btn {
            position:relative;
            z-index:2;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:9px;min-height:43px;
            padding:11px 16px;
            border-radius:10px;
            background:#341893;
            color:#fff;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
            white-space:nowrap;
            box-shadow:0 7px 16px rgba(52,24,147,.18);
            transition:all .2s ease;
        }
        .add-product-btn:hover {
            background:#26106f;
            color:#fff;
            transform:translateY(-1px);
            box-shadow:0 10px 20px rgba(52,24,147,.24);
        }


        /* ============================================================
           FUTURISTIC PAGE BACKGROUND
        ============================================================ */

        .vendor-page {
            position: relative;
            width: 100%;
            min-height: calc(100vh - 80px);
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 8% 5%,
                    rgba(214,168,79,.08),
                    transparent 24%
                ),
                radial-gradient(
                    circle at 92% 15%,
                    rgba(59,130,246,.045),
                    transparent 25%
                );
        }

        .dark .vendor-page {
            background:
                radial-gradient(
                    circle at 8% 5%,
                    rgba(214,168,79,.075),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 90% 10%,
                    rgba(59,130,246,.045),
                    transparent 25%
                ),
                linear-gradient(
                    180deg,
                    #05070b 0%,
                    #070a10 50%,
                    #05070b 100%
                );
        }


        /* ============================================================
           ABSTRACT SYMBOLIC BACKGROUND
        ============================================================ */

        .vendor-page::before {
            content: "";
            position: absolute;
            width: 430px;
            height: 430px;
            top: -210px;
            right: -180px;
            border: 1px solid rgba(214,168,79,.10);
            border-radius: 50%;
            box-shadow:
                0 0 0 35px rgba(214,168,79,.025),
                0 0 0 70px rgba(214,168,79,.018);
            pointer-events: none;
        }

        .vendor-page::after {
            content: "◇  ⬡  ∞  ⊕  ⌘";
            position: absolute;
            right: 25px;
            bottom: 25px;
            color: rgba(214,168,79,.06);
            font-size: 42px;
            letter-spacing: 15px;
            pointer-events: none;
            transform: rotate(-8deg);
            white-space: nowrap;
        }


        /* ============================================================
           CONTENT WRAPPER
        ============================================================ */

        .vendor-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 24px;
        }


        /* ============================================================
           PAGE HERO
        ============================================================ */

        .vendor-hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
            padding: 24px;
            overflow: hidden;

            border: 1px solid rgba(148,163,184,.14);
            border-radius: 22px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.92),
                    rgba(248,250,252,.78)
                );

            box-shadow:
                0 18px 50px rgba(15,23,42,.06);
        }

        .dark .vendor-hero {
            border-color: rgba(255,255,255,.075);

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.035),
                    rgba(255,255,255,.012)
                );

            box-shadow:
                0 20px 60px rgba(0,0,0,.25);
        }

        .vendor-hero::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            right: -60px;
            top: -80px;
            border-radius: 50%;
            border: 1px solid rgba(214,168,79,.13);
            box-shadow:
                0 0 0 25px rgba(214,168,79,.025),
                0 0 0 50px rgba(214,168,79,.015);
            pointer-events: none;
        }

        .vendor-hero-left {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .vendor-hero-icon {
            flex: 0 0 52px;
            width: 52px;
            height: 52px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 15px;

            color: #d6a84f;

            background:
                linear-gradient(
                    135deg,
                    rgba(214,168,79,.16),
                    rgba(214,168,79,.045)
                );

            border: 1px solid rgba(214,168,79,.15);

            box-shadow:
                0 10px 35px rgba(214,168,79,.08);

            font-size: 20px;
        }

        .vendor-hero-title {
            margin: 0;
            color: #111827;
            font-size: clamp(19px, 2vw, 26px);
            font-weight: 750;
            letter-spacing: -.02em;
        }

        .dark .vendor-hero-title {
            color: #f8fafc;
        }

        .vendor-hero-subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .dark .vendor-hero-subtitle {
            color: #7f8da3;
        }

        .vendor-status {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 999px;

            color: #a87920;
            background: rgba(214,168,79,.08);
            border: 1px solid rgba(214,168,79,.13);

            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .vendor-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #d6a84f;
            box-shadow: 0 0 12px rgba(214,168,79,.7);
        }


        /* ============================================================
           MAIN VENDOR PANEL
        ============================================================ */

        .vendor-panel {
            position: relative;
            width: 100%;
            min-width: 0;

            border: 1px solid rgba(148,163,184,.15);
            border-radius: 22px;

            background: rgba(255,255,255,.94);

            overflow: hidden;

            box-shadow:
                0 20px 60px rgba(15,23,42,.055);
        }

        .dark .vendor-panel {
            border-color: rgba(255,255,255,.07);
            background:
                linear-gradient(
                    180deg,
                    rgba(255,255,255,.025),
                    rgba(255,255,255,.012)
                );

            box-shadow:
                0 25px 70px rgba(0,0,0,.22);
        }


        /* ============================================================
           PANEL HEADER
        ============================================================ */

        .vendor-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding: 18px 20px;

            border-bottom: 1px solid rgba(148,163,184,.12);
        }

        .dark .vendor-panel-header {
            border-bottom-color: rgba(255,255,255,.065);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .panel-heading-icon {
            color: #d6a84f;
            font-size: 14px;
        }

        .panel-heading-text {
            color: #1f2937;
            font-size: 14px;
            font-weight: 700;
        }

        .dark .panel-heading-text {
            color: #f8fafc;
        }


        /* ============================================================
           SEARCH AREA
        ============================================================ */

        .vendor-search-area {
            display: flex;
            align-items: stretch;
            gap: 10px;
            flex-wrap: wrap;

            padding: 16px 20px;

            background:
                linear-gradient(
                    90deg,
                    rgba(148,163,184,.035),
                    transparent
                );

            border-bottom: 1px solid rgba(148,163,184,.10);
        }

        .dark .vendor-search-area {
            border-bottom-color: rgba(255,255,255,.055);

            background:
                linear-gradient(
                    90deg,
                    rgba(214,168,79,.025),
                    transparent
                );
        }

        .search-control {
            min-width: 0;
            height: 43px;

            border: 1px solid rgba(148,163,184,.25);
            border-radius: 11px;

            outline: none;

            background: #fff;
            color: #374151;

            font-size: 13px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .dark .search-control {
            border-color: rgba(255,255,255,.10);
            background: rgba(255,255,255,.035);
            color: #e5e7eb;
        }

        .search-control:focus {
            border-color: rgba(214,168,79,.55);

            box-shadow:
                0 0 0 3px rgba(214,168,79,.08);
        }

        .search-select {
            flex: 0 0 180px;
            width: 180px;
            padding: 0 12px;
        }

        .search-input-wrapper {
            position: relative;
            flex: 1 1 300px;
            min-width: 200px;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);

            color: #94a3b8;
            font-size: 13px;

            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 0 14px 0 39px;
        }


        /* ============================================================
           RESULTS AREA
        ============================================================ */

        .vendor-results-wrapper {
            width: 100%;
            min-width: 0;
            padding: 20px;
        }

        #staffResults {
            width: 100%;
            min-width: 0;
        }


        /* ============================================================
           VENDOR CARD
        ============================================================ */

        .vendor-card {
            position: relative;
            min-width: 0;

            padding: 18px;

            border: 1px solid rgba(148,163,184,.13);
            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.82)
                );

            transition:
                transform .2s ease,
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .dark .vendor-card {
            border-color: rgba(255,255,255,.065);

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.035),
                    rgba(255,255,255,.012)
                );
        }

        .vendor-card:hover {
            transform: translateY(-2px);

            border-color: rgba(214,168,79,.22);

            box-shadow:
                0 18px 45px rgba(15,23,42,.08);
        }

        .dark .vendor-card:hover {
            box-shadow:
                0 20px 50px rgba(0,0,0,.25),
                0 0 0 1px rgba(214,168,79,.035);
        }


        /* ============================================================
           VENDOR CARD TOP
        ============================================================ */

        .vendor-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;

            margin-bottom: 15px;
        }

        .vendor-identity {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
        }

        .vendor-avatar {
            flex: 0 0 42px;

            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            color: #d6a84f;

            background:
                linear-gradient(
                    135deg,
                    rgba(214,168,79,.15),
                    rgba(214,168,79,.035)
                );

            border: 1px solid rgba(214,168,79,.13);
        }

        .vendor-name {
            min-width: 0;

            color: #1f2937;
            font-size: 14px;
            font-weight: 700;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .vendor-name {
            color: #f8fafc;
        }

        .vendor-email {
            margin-top: 3px;

            color: #94a3b8;
            font-size: 11px;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        /* ============================================================
           STATUS BADGE
        ============================================================ */

        .vendor-badge {
            flex: 0 0 auto;

            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 5px 8px;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;

            background: rgba(148,163,184,.08);
            color: #64748b;
        }

        .dark .vendor-badge {
            background: rgba(255,255,255,.045);
            color: #94a3b8;
        }


        /* ============================================================
           VENDOR INFORMATION
        ============================================================ */

        .vendor-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .vendor-info-item {
            min-width: 0;
            padding: 10px;

            border-radius: 11px;

            background: rgba(148,163,184,.045);
            border: 1px solid rgba(148,163,184,.07);
        }

        .dark .vendor-info-item {
            background: rgba(255,255,255,.025);
            border-color: rgba(255,255,255,.045);
        }

        .vendor-info-label {
            display: flex;
            align-items: center;
            gap: 6px;

            margin-bottom: 4px;

            color: #94a3b8;

            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .vendor-info-label i {
            color: #d6a84f;
            font-size: 9px;
        }

        .vendor-info-value {
            color: #374151;

            font-size: 11px;
            line-height: 1.45;

            word-break: break-word;
        }

        .dark .vendor-info-value {
            color: #cbd5e1;
        }


        /* ============================================================
           VENDOR ACTIONS
        ============================================================ */

        .vendor-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;

            margin-top: 15px;
            padding-top: 14px;

            border-top: 1px solid rgba(148,163,184,.10);
        }

        .dark .vendor-actions {
            border-top-color: rgba(255,255,255,.055);
        }

        .vendor-action-btn {
            width: 100%;
            min-height: 38px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            padding: 7px 8px;

            border: 1px solid transparent;
            border-radius: 9px;

            background: transparent;

            font-size: 10px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background .2s ease,
                color .2s ease,
                border-color .2s ease,
                transform .2s ease;
        }

        .vendor-action-btn:hover {
            transform: translateY(-1px);
        }

        .vendor-action-suspend {
            color: #ef4444;
            border-color: rgba(239,68,68,.13);
            background: rgba(239,68,68,.035);
        }

        .vendor-action-suspend:hover {
            background: rgba(239,68,68,.09);
        }

        .vendor-action-activate {
            color: #10b981;
            border-color: rgba(16,185,129,.13);
            background: rgba(16,185,129,.035);
        }

        .vendor-action-activate:hover {
            background: rgba(16,185,129,.09);
        }

        .vendor-action-delete {
            color: #94a3b8;
            border-color: rgba(148,163,184,.13);
            background: rgba(148,163,184,.035);
        }

        .vendor-action-delete:hover {
            color: #ef4444;
            border-color: rgba(239,68,68,.15);
            background: rgba(239,68,68,.06);
        }


        /* ============================================================
           DESKTOP RESULTS GRID
        ============================================================ */

        .vendor-results-grid {
            display: grid;
            grid-template-columns: repeat(
                3,
                minmax(0, 1fr)
            );
            gap: 16px;
        }


        /* ============================================================
           EMPTY STATE
        ============================================================ */

        .vendor-empty {
            width: 100%;
            padding: 60px 20px;
            text-align: center;
        }

        .vendor-empty-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 18px;

            color: #d6a84f;
            background: rgba(214,168,79,.07);
            border: 1px solid rgba(214,168,79,.12);

            font-size: 24px;
        }

        .vendor-empty-title {
            margin: 0;
            color: #374151;
            font-size: 14px;
            font-weight: 700;
        }

        .dark .vendor-empty-title {
            color: #e5e7eb;
        }

        .vendor-empty-text {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 11px;
        }


        /* ============================================================
           MOBILE
        ============================================================ */

        @media (max-width: 1023px) {

            /*
             * No overlay.
             * Sidebar simply sits above the header.
             */
            header {
                z-index: 1000 !important;
            }

            .vendor-container {
                padding: 14px;
            }

            .vendor-hero {
                padding: 18px;
                border-radius: 18px;
            }

            .vendor-hero-icon {
                width: 44px;
                height: 44px;
                flex-basis: 44px;
                font-size: 17px;
                border-radius: 12px;
            }

            .vendor-status {
                display: none;
            }

            .vendor-panel {
                border-radius: 18px;
            }

            .vendor-panel-header {
                padding: 15px;
            }

            .vendor-search-area {
                padding: 14px;
                gap: 8px;
            }

            .search-select,
            .search-input-wrapper {
                width: 100%;
                flex-basis: 100%;
            }

            .vendor-results-wrapper {
                padding: 14px;
            }

            .vendor-results-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

        }


        /* ============================================================
           SMALL PHONES
        ============================================================ */

        @media (max-width: 480px) {

            .vendor-container {
                padding: 10px;
            }

            .vendor-hero {
                padding: 15px;
                margin-bottom: 12px;
            }

            .vendor-hero-left {
                gap: 10px;
            }

            .vendor-hero-title {
                font-size: 17px;
            }

            .vendor-hero-subtitle {
                font-size: 10px;
            }

            .vendor-panel-header {
                padding: 13px;
            }

            .panel-heading-text {
                font-size: 12px;
            }

            .vendor-search-area {
                padding: 12px;
            }

            .vendor-results-wrapper {
                padding: 10px;
            }

            .vendor-card {
                padding: 14px;
                border-radius: 15px;
            }

            .vendor-info-grid {
                grid-template-columns: 1fr;
                gap: 7px;
            }

            .vendor-actions {
                grid-template-columns: 1fr;
                gap: 7px;
            }

            .vendor-action-btn {
                min-height: 40px;
            }

        }


        /* ============================================================
           TABLET / SMALL DESKTOP
        ============================================================ */

        @media (min-width: 768px) and (max-width: 1199px) {

            .vendor-results-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        /* ============================================================
           LARGE DESKTOP
        ============================================================ */

        @media (min-width: 1400px) {

            .vendor-container {
                padding: 30px;
            }

            .vendor-results-grid {
                grid-template-columns:
                    repeat(4, minmax(0, 1fr));
            }

        }


        /* ============================================================
           VERY LARGE SCREENS
        ============================================================ */

        @media (min-width: 1700px) {

            .vendor-results-grid {
                grid-template-columns:
                    repeat(4, minmax(0, 1fr));
            }

        }


        /* ============================================================
           REDUCED MOTION
        ============================================================ */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition: none !important;
                animation: none !important;
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


<!-- ================================================================
     PAGE WRAPPER
================================================================ -->

<div class="flex min-h-screen w-full overflow-hidden">


    <!-- ============================================================
         SIDEBAR
    ============================================================ -->

    <?php include('sidebar.php'); ?>


    <!-- ============================================================
         CONTENT AREA
    ============================================================ -->

    <div class="page-shell relative min-w-0 flex-1">


        <!-- ========================================================
             MAIN CONTENT
        ======================================================== -->

        <main class="main-content-area min-w-0">


            <!-- ====================================================
                 HEADER
            ==================================================== -->

            <?php include('header.php'); ?>


            <!-- ====================================================
                 VENDOR PAGE
            ==================================================== -->

            <section class="vendor-page">


                <div class="vendor-container">


                    <!-- =================================================
                         HERO
                    ================================================== -->

                    <div class="vendor-hero">

                        <div class="vendor-hero-left">

                            <div class="vendor-hero-icon">

                                <i class="fa-solid fa-truck-fast"></i>

                            </div>


                            <div>

                                <h1 class="vendor-hero-title">
                                    Registered Vendors
                                </h1>

                                <p class="vendor-hero-subtitle">
                                    Manage your company's vendor network,
                                    relationships and operational partners.
                                </p>

                            </div>

                        </div>


                        <div class="vendor-status">

                            <span class="vendor-status-dot"></span>

                            Vendor Network

                        </div>

                    </div>


                    <!-- =================================================
                         MAIN PANEL
                    ================================================== -->

                    <div class="vendor-panel">


                        <!-- =============================================
                             PANEL HEADER
                        ============================================== -->

                        <div class="vendor-panel-header">

                            <div class="panel-heading">

                                <i
                                    class="fa-solid fa-database panel-heading-icon"
                                ></i>

                                <span class="panel-heading-text">
                                    Company Registered Vendors
                                </span>

                                <a href="new_suppliers.php" class="add-product-btn"><span>Register</span></a>

                            </div>

                        </div>


                        <!-- =============================================
                             SEARCH
                        ============================================== -->

                        <div class="vendor-search-area">


                            <select
                                id="search_option"
                                class="search-control search-select"
                            >

                                <option value="business_name">
                                    Business Name
                                </option>

                                <option value="mobile">
                                    Mobile
                                </option>

                                <option value="email">
                                    Email
                                </option>

                            </select>


                            <div class="search-input-wrapper">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="text"
                                    id="search_value"
                                    class="search-control search-input"
                                    placeholder="Search vendors..."
                                    autocomplete="off"
                                >

                            </div>


                        </div>


                        <!-- =============================================
                             RESULTS
                        ============================================== -->

                        <div class="vendor-results-wrapper">

                            <div id="staffResults">

                                <div class="vendor-results-grid">

                                    <?php

                                    $stmt = $pdo->prepare("
                                        SELECT *
                                        FROM vendors
                                        WHERE company_id = ?
                                        ORDER BY id DESC
                                    ");

                                    $stmt->execute([
                                        $_SESSION['company_id']
                                    ]);

                                    $vendors = $stmt->fetchAll(
                                        PDO::FETCH_ASSOC
                                    );


                                    if (!empty($vendors)):

                                        foreach ($vendors as $branch):

                                            $fullName = trim(
                                                ($branch['firstname'] ?? '') .
                                                ' ' .
                                                ($branch['business_name'] ?? '')
                                            );

                                            if ($fullName === '') {
                                                $fullName = 'Unnamed Vendor';
                                            }

                                    ?>

                                    <!-- =================================
                                         VENDOR CARD
                                    ================================== -->

                                    <div class="vendor-card">


                                        <!-- =============================
                                             CARD TOP
                                        ============================== -->

                                        <div class="vendor-card-top">

                                            <div class="vendor-identity">


                                                <div class="vendor-avatar">

                                                    <i class="fa-solid fa-building"></i>

                                                </div>


                                                <div class="min-w-0">

                                                    <div class="vendor-name">

                                                        <?= htmlspecialchars(
                                                            $fullName
                                                        ) ?>

                                                    </div>


                                                    <div class="vendor-email">

                                                        <?= htmlspecialchars(
                                                            $branch['email'] ?? 'No email'
                                                        ) ?>

                                                    </div>

                                                </div>

                                            </div>


                                            <div class="vendor-badge">

                                                <i class="fa-solid fa-circle"></i>

                                                <?= htmlspecialchars(
                                                    $branch['status'] ?? 'Unknown'
                                                ) ?>

                                            </div>

                                        </div>


                                        <!-- =============================
                                             INFORMATION
                                        ============================== -->

                                        <div class="vendor-info-grid">


                                            <!-- ADDRESS -->

                                            <div class="vendor-info-item">

                                                <div class="vendor-info-label">

                                                    <i class="fa-solid fa-location-dot"></i>

                                                    Office Address

                                                </div>

                                                <div class="vendor-info-value">

                                                    <?= htmlspecialchars(
                                                        $branch['office_address'] ?? 'Not provided'
                                                    ) ?>

                                                </div>

                                            </div>


                                            <!-- MOBILE -->

                                            <div class="vendor-info-item">

                                                <div class="vendor-info-label">

                                                    <i class="fa-solid fa-phone"></i>

                                                    Mobile

                                                </div>

                                                <div class="vendor-info-value">

                                                    <?= htmlspecialchars(
                                                        $branch['mobile'] ?? 'Not provided'
                                                    ) ?>

                                                </div>

                                            </div>


                                            <!-- DESCRIPTION -->

                                            <div class="vendor-info-item">

                                                <div class="vendor-info-label">

                                                    <i class="fa-solid fa-align-left"></i>

                                                    Description

                                                </div>

                                                <div class="vendor-info-value">

                                                    <?= htmlspecialchars(
                                                        $branch['description'] ?? 'No description'
                                                    ) ?>

                                                </div>

                                            </div>


                                            <!-- EMAIL -->

                                            <div class="vendor-info-item">

                                                <div class="vendor-info-label">

                                                    <i class="fa-solid fa-envelope"></i>

                                                    Email

                                                </div>

                                                <div class="vendor-info-value">

                                                    <?= htmlspecialchars(
                                                        $branch['email'] ?? 'Not provided'
                                                    ) ?>

                                                </div>

                                            </div>


                                        </div>


                                        <!-- =============================
                                             ACTIONS
                                        ============================== -->

                                        <div class="vendor-actions">


                                            <!-- SUSPEND -->

                                            <form
                                                method="POST"
                                                action="suspend-vendor.php"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="vendor_id"
                                                    value="<?= htmlspecialchars(
                                                        $branch['id']
                                                    ) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="vendor_email"
                                                    value="<?= htmlspecialchars(
                                                        $branch['email'] ?? ''
                                                    ) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="submit"
                                                    class="vendor-action-btn vendor-action-suspend"
                                                >

                                                    <i class="fa-solid fa-pause"></i>

                                                    Suspend

                                                </button>

                                            </form>


                                            <!-- ACTIVATE -->

                                            <form
                                                method="POST"
                                                action="activate-vendor.php"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="vendor_id"
                                                    value="<?= htmlspecialchars(
                                                        $branch['id']
                                                    ) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="vendor_email"
                                                    value="<?= htmlspecialchars(
                                                        $branch['email'] ?? ''
                                                    ) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="submit"
                                                    class="vendor-action-btn vendor-action-activate"
                                                >

                                                    <i class="fa-solid fa-power-off"></i>

                                                    Activate

                                                </button>

                                            </form>


                                            <!-- DELETE -->

                                            <form
                                                method="POST"
                                                action="delete_vendor.php"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="vendor_id"
                                                    value="<?= htmlspecialchars(
                                                        $branch['id']
                                                    ) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="vendor_email"
                                                    value="<?= htmlspecialchars(
                                                        $branch['email'] ?? ''
                                                    ) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="submit"
                                                    class="vendor-action-btn vendor-action-delete"
                                                >

                                                    <i class="fa-solid fa-trash-can"></i>

                                                    Delete

                                                </button>

                                            </form>


                                        </div>


                                    </div>

                                    <?php

                                        endforeach;

                                    else:

                                    ?>

                                    <!-- =================================
                                         EMPTY STATE
                                    ================================== -->

                                    <div class="vendor-empty">

                                        <div class="vendor-empty-icon">

                                            <i class="fa-solid fa-truck-fast"></i>

                                        </div>

                                        <h3 class="vendor-empty-title">

                                            No vendors registered

                                        </h3>

                                        <p class="vendor-empty-text">

                                            Your company has not registered
                                            any vendors yet.

                                        </p>

                                    </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>


                    </div>

                </div>

            </section>


        </main>

    </div>

</div>


<!-- ================================================================
     FOOTER
================================================================ -->

<?php include('footer.php'); ?>


<!-- ================================================================
     JAVASCRIPT
================================================================ -->

<script
    defer
    src="bundle.js"
></script>


<script
    src="https://code.jquery.com/jquery-3.6.0.min.js"
></script>


<script>

$(document).ready(function () {


    /* ============================================================
       VENDOR SEARCH
    ============================================================ */

    function searchStaff() {

        var option = $("#search_option").val();

        var value = $("#search_value").val();


        $.ajax({

            url: "ajax_search_suppliers.php",

            method: "POST",

            data: {

                search_option: option,

                search_value: value

            },

            success: function (data) {

                $("#staffResults").html(data);

            },

            error: function () {

                $("#staffResults").html(`

                    <div class="vendor-empty">

                        <div class="vendor-empty-icon">

                            <i class="fa-solid fa-triangle-exclamation"></i>

                        </div>

                        <h3 class="vendor-empty-title">
                            Unable to load vendors
                        </h3>

                        <p class="vendor-empty-text">
                            Please try your search again.
                        </p>

                    </div>

                `);

            }

        });

    }


    /* ============================================================
       SEARCH INPUT
    ============================================================ */

    $("#search_value").on("keyup", function () {

        searchStaff();

    });


    /* ============================================================
       SEARCH OPTION
    ============================================================ */

    $("#search_option").on("change", function () {

        searchStaff();

    });


});

</script>


</body>

</html>