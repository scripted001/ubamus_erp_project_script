
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
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?php echo htmlspecialchars($company_name); ?> | New Product Category
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

        /* ==========================================================
           FUTURISTIC LIGHT UI
           ========================================================== */

        :root {

            --fx-bg: #f5f7fb;
            --fx-white: #ffffff;
            --fx-surface: rgba(255, 255, 255, .82);
            --fx-surface-solid: #ffffff;
            --fx-border: #e6e9ef;
            --fx-border-light: #eef0f4;
            --fx-border-gold: rgba(217, 150, 20, .42);
            --fx-navy: #111827;
            --fx-navy-soft: #263247;
            --fx-muted: #697386;
            --fx-muted-light: #9aa3b2;
            --fx-gold: #d99a16;
            --fx-gold-dark: #b77905;
            --fx-gold-soft: #fff8e7;
            --fx-blue: #5267d8;
            --fx-green: #16a36a;

            --fx-shadow:
                0 20px 55px rgba(30, 41, 59, .07);

            --fx-shadow-small:
                0 8px 25px rgba(30, 41, 59, .055);
        }


        /* ==========================================================
           GLOBAL
           ========================================================== */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }


        html {
            width: 100%;
            min-height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
        }


        body {

            width: 100%;
            min-height: 100vh;
            margin: 0;

            background:

                radial-gradient(
                    circle at 5% 5%,
                    rgba(217, 154, 22, .08),
                    transparent 24%
                ),

                radial-gradient(
                    circle at 95% 10%,
                    rgba(82, 103, 216, .07),
                    transparent 25%
                ),

                radial-gradient(
                    circle at 50% 100%,
                    rgba(217, 154, 22, .045),
                    transparent 30%
                ),

                var(--fx-bg);

            color: var(--fx-navy);

            overflow-x: hidden;
        }


        /* ==========================================================
           IMPORTANT SCROLL FIX
           ========================================================== */

        /*
         * The dashboard shell must occupy the viewport,
         * while the content column becomes the actual
         * vertical scrolling area.
         */

        .fx-app-shell {

            display: flex;

            width: 100%;
            height: 100vh;
            min-height: 100vh;

            overflow: hidden;
        }


        .fx-content-scroll {

            position: relative;

            display: flex;
            flex-direction: column;

            flex: 1 1 auto;

            min-width: 0;
            min-height: 0;

            width: 100%;
            height: 100vh;

            overflow-x: hidden;
            overflow-y: auto;

            /*
             * Smooth scrolling on phones/tablets.
             */
            -webkit-overflow-scrolling: touch;

            /*
             * Make sure the browser always recognizes
             * this element as the scrolling container.
             */
            overscroll-behavior-y: contain;

            scrollbar-width: auto;
        }


        /*
         * Visible desktop scrollbar.
         */

        .fx-content-scroll::-webkit-scrollbar {
            width: 9px;
        }


        .fx-content-scroll::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, .035);
        }


        .fx-content-scroll::-webkit-scrollbar-thumb {

            background: rgba(110, 120, 138, .35);

            border-radius: 20px;

            border: 2px solid transparent;

            background-clip: padding-box;
        }


        .fx-content-scroll::-webkit-scrollbar-thumb:hover {

            background: rgba(110, 120, 138, .55);

            border: 2px solid transparent;

            background-clip: padding-box;
        }


        /*
         * Mobile scrollbar.
         */

        @media (max-width: 900px) {

            .fx-content-scroll::-webkit-scrollbar {
                width: 6px;
            }

            .fx-content-scroll::-webkit-scrollbar-thumb {
                background: rgba(110, 120, 138, .45);
            }
        }


        /* ==========================================================
           PAGE BACKGROUND GRID
           ========================================================== */

        .fx-page {

            position: relative;

            width: 100%;

            min-height: max-content;

            overflow: visible;
        }


        .fx-page::before {

            content: "";

            position: fixed;

            inset: 0;

            pointer-events: none;

            opacity: .42;

            background-image:

                linear-gradient(
                    rgba(15, 23, 42, .018) 1px,
                    transparent 1px
                ),

                linear-gradient(
                    90deg,
                    rgba(15, 23, 42, .018) 1px,
                    transparent 1px
                );

            background-size: 40px 40px;

            mask-image:
                linear-gradient(
                    to bottom,
                    black,
                    transparent 90%
                );

            z-index: 0;
        }


        .fx-container {

            position: relative;

            z-index: 1;

            width: 100%;

            max-width: 1480px;

            margin: 0 auto;

            padding: 28px;

            /*
             * Ensures there is enough room after the
             * last form element for comfortable scrolling.
             */
            padding-bottom: 40px;
        }


        /* ==========================================================
           HERO
           ========================================================== */

        .fx-hero {

            position: relative;

            overflow: hidden;

            border: 1px solid var(--fx-border);

            border-radius: 24px;

            padding: 32px;

            margin-bottom: 22px;

            background:

                linear-gradient(
                    135deg,
                    rgba(255,255,255,.96),
                    rgba(255,255,255,.82)
                );

            backdrop-filter: blur(18px);

            box-shadow: var(--fx-shadow);
        }


        .fx-hero::before {

            content: "";

            position: absolute;

            left: 0;

            top: 0;

            width: 100%;

            height: 3px;

            background:

                linear-gradient(
                    90deg,
                    transparent,
                    var(--fx-gold),
                    var(--fx-blue),
                    transparent
                );
        }


        .fx-hero::after {

            content: "";

            position: absolute;

            width: 260px;

            height: 260px;

            right: -100px;

            top: -150px;

            border-radius: 50%;

            background:
                rgba(217,154,22,.09);

            filter: blur(45px);

            pointer-events: none;
        }


        .fx-kicker {

            display: flex;

            align-items: center;

            gap: 9px;

            margin-bottom: 12px;

            color: var(--fx-gold-dark);

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .18em;

            text-transform: uppercase;
        }


        .fx-kicker-dot {

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: var(--fx-gold);

            box-shadow:
                0 0 0 4px rgba(217,154,22,.11),
                0 0 16px rgba(217,154,22,.38);
        }


        .fx-title {

            margin: 0;

            color: var(--fx-navy);

            font-size: clamp(
                27px,
                4vw,
                42px
            );

            font-weight: 850;

            letter-spacing: -.045em;

            line-height: 1.04;
        }


        .fx-description {

            max-width: 760px;

            margin-top: 13px;

            color: var(--fx-muted);

            font-size: 13px;

            line-height: 1.75;
        }


        /* ==========================================================
           BREADCRUMB
           ========================================================== */

        .fx-breadcrumb {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 9px;

            margin-top: 22px;

            color: var(--fx-muted-light);

            font-size: 11px;
        }


        .fx-breadcrumb a {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            color: var(--fx-muted);

            text-decoration: none;

            transition: .2s ease;
        }


        .fx-breadcrumb a:hover {
            color: var(--fx-gold-dark);
        }


        .fx-breadcrumb i {

            font-size: 8px;

            color: #c2c8d2;
        }


        .fx-current {
            color: var(--fx-navy-soft);
        }


        /* ==========================================================
           FORM CARD
           ========================================================== */

        .fx-form-shell {

            position: relative;

            overflow: visible;

            border: 1px solid var(--fx-border);

            border-radius: 24px;

            background:
                rgba(255,255,255,.86);

            backdrop-filter: blur(18px);

            box-shadow: var(--fx-shadow);
        }


        .fx-form-shell::before {

            content: "";

            position: absolute;

            left: 0;

            right: 0;

            top: 0;

            height: 1px;

            background:

                linear-gradient(
                    90deg,
                    transparent,
                    rgba(217,154,22,.65),
                    transparent
                );
        }


        .fx-form {
            padding: 32px;
        }


        /* ==========================================================
           FORM SECTIONS
           ========================================================== */

        .fx-section {
            margin-bottom: 34px;
        }


        .fx-section:last-of-type {
            margin-bottom: 0;
        }


        .fx-section-header {

            display: flex;

            align-items: center;

            gap: 13px;

            padding-bottom: 17px;

            margin-bottom: 22px;

            border-bottom:
                1px solid var(--fx-border-light);
        }


        .fx-section-icon {

            width: 40px;

            height: 40px;

            flex: 0 0 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid rgba(217,154,22,.22);

            border-radius: 12px;

            background:

                linear-gradient(
                    145deg,
                    #fffaf0,
                    #fff4d8
                );

            color: var(--fx-gold-dark);

            box-shadow:
                0 5px 15px rgba(217,154,22,.08);
        }


        .fx-section-title {

            margin: 0;

            color: var(--fx-navy);

            font-size: 14px;

            font-weight: 800;

            letter-spacing: -.01em;
        }


        .fx-section-subtitle {

            margin: 3px 0 0;

            color: var(--fx-muted-light);

            font-size: 10px;
        }


        /* ==========================================================
           FORM GRID
           ========================================================== */

        .fx-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 21px;
        }


        .fx-full {
            grid-column: 1 / -1;
        }


        /* ==========================================================
           LABELS
           ========================================================== */

        .fx-label {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-bottom: 8px;

            color: #344054;

            font-size: 11px;

            font-weight: 750;
        }


        .fx-required {

            color: var(--fx-gold-dark);

            font-size: 11px;
        }


        .fx-optional {

            color: var(--fx-muted-light);

            font-size: 9px;

            font-weight: 500;
        }


        /* ==========================================================
           INPUTS
           ========================================================== */

        .fx-input,
        .fx-select,
        .fx-textarea {

            width: 100%;

            color: var(--fx-navy);

            background:
                rgba(248,250,252,.72);

            border:
                1px solid #e1e5eb;

            outline: none;

            border-radius: 12px;

            box-shadow:
                inset 0 1px 2px rgba(15,23,42,.025);

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                transform .2s ease;
        }


        .fx-input,
        .fx-select {

            height: 49px;

            padding:
                0 15px;

            font-size: 12px;
        }


        .fx-textarea {

            min-height: 125px;

            padding: 14px 15px;

            resize: vertical;

            font-size: 12px;

            line-height: 1.65;
        }


        .fx-input::placeholder,
        .fx-textarea::placeholder {
            color: #a5adba;
        }


        .fx-input:hover,
        .fx-select:hover,
        .fx-textarea:hover {

            border-color: #ccd2dc;

            background:
                #ffffff;
        }


        .fx-input:focus,
        .fx-select:focus,
        .fx-textarea:focus {

            border-color:
                rgba(217,154,22,.7);

            background:
                #ffffff;

            box-shadow:

                0 0 0 3px
                    rgba(217,154,22,.09),

                0 7px 25px
                    rgba(217,154,22,.055);
        }


        /* ==========================================================
           SELECT
           ========================================================== */

        .fx-select {

            appearance: none;

            cursor: pointer;

            padding-right: 42px;

            background-image:

                linear-gradient(
                    45deg,
                    transparent 50%,
                    #8d96a5 50%
                ),

                linear-gradient(
                    135deg,
                    #8d96a5 50%,
                    transparent 50%
                );

            background-position:

                calc(100% - 18px) 21px,
                calc(100% - 13px) 21px;

            background-size:
                5px 5px,
                5px 5px;

            background-repeat: no-repeat;
        }


        .fx-select option {

            background: #ffffff;

            color: #111827;
        }


        /* ==========================================================
           FIELD INFORMATION
           ========================================================== */

        .fx-note {

            display: flex;

            align-items: center;

            gap: 6px;

            margin-top: 7px;

            color: #8a94a5;

            font-size: 9px;

            line-height: 1.5;
        }


        .fx-note i {

            color: var(--fx-green);

            font-size: 9px;
        }


        /* ==========================================================
           ACTION AREA
           ========================================================== */

        .fx-actions {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-top: 35px;

            padding-top: 25px;

            border-top:
                1px solid var(--fx-border-light);
        }


        .fx-security {

            display: flex;

            align-items: center;

            gap: 10px;

            color: #8a94a5;

            font-size: 9px;

            line-height: 1.5;
        }


        .fx-security-icon {

            width: 31px;

            height: 31px;

            flex: 0 0 31px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background:
                #f1fbf6;

            border:
                1px solid #d8f2e4;

            color:
                var(--fx-green);
        }


        .fx-buttons {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .fx-btn {

            min-height: 46px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding:
                0 19px;

            border-radius: 11px;

            font-size: 11px;

            font-weight: 800;

            text-decoration: none;

            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease,
                background .2s ease;
        }


        .fx-btn:hover {
            transform:
                translateY(-1px);
        }


        .fx-btn-cancel {

            color: #536071;

            background:
                #ffffff;

            border:
                1px solid #dfe3e9;
        }


        .fx-btn-cancel:hover {

            color: #263247;

            border-color:
                #c9cfd8;

            box-shadow:
                0 7px 20px
                rgba(30,41,59,.06);
        }


        .fx-btn-create {

            color: #ffffff;

            border:
                1px solid #d08e0b;

            background:

                linear-gradient(
                    135deg,
                    #e5ab2d,
                    #c98708
                );

            box-shadow:

                0 8px 22px
                rgba(217,154,22,.22),

                inset 0 1px
                rgba(255,255,255,.3);
        }


        .fx-btn-create:hover {

            box-shadow:

                0 12px 30px
                rgba(217,154,22,.3),

                0 0 24px
                rgba(217,154,22,.09);
        }


        /* ==========================================================
           SMALL STATUS BADGE
           ========================================================== */

        .fx-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            margin-top: 9px;

            padding:
                5px 8px;

            border-radius: 7px;

            background:
                #f5f8ff;

            border:
                1px solid #e5eafb;

            color:
                #6b76a1;

            font-size: 8px;

            font-weight: 700;
        }


        .fx-status-dot {

            width: 5px;

            height: 5px;

            border-radius: 50%;

            background:
                var(--fx-blue);

            box-shadow:
                0 0 7px
                rgba(82,103,216,.45);
        }


        /* ==========================================================
           MOBILE
           ========================================================== */

        @media (max-width: 900px) {

            /*
             * On mobile the content remains independently scrollable.
             */
            .fx-app-shell {

                height: 100vh;
                min-height: 100vh;

                overflow: hidden;
            }


            .fx-content-scroll {

                width: 100%;

                height: 100vh;
                min-height: 0;

                overflow-x: hidden;
                overflow-y: auto;

                -webkit-overflow-scrolling: touch;
            }


            .fx-container {

                padding: 20px;

                padding-bottom: 35px;
            }


            .fx-grid {

                grid-template-columns: 1fr;
            }


            .fx-full {
                grid-column: auto;
            }


            .fx-hero,
            .fx-form-shell {

                border-radius: 19px;
            }


            .fx-form {
                padding: 23px;
            }


            .fx-actions {

                flex-direction: column;

                align-items: stretch;
            }


            .fx-security {

                align-items: flex-start;
            }


            .fx-buttons {

                width: 100%;
            }


            .fx-btn {
                flex: 1;
            }
        }


        @media (max-width: 560px) {

            .fx-container {

                padding: 12px;

                padding-bottom: 30px;
            }


            .fx-hero {

                padding: 23px 18px;
            }


            .fx-form {

                padding: 19px 15px;
            }


            .fx-title {
                font-size: 28px;
            }


            .fx-description {

                font-size: 11px;

                line-height: 1.7;
            }


            .fx-section {
                margin-bottom: 28px;
            }


            .fx-section-header {
                margin-bottom: 18px;
            }


            .fx-buttons {

                flex-direction: column-reverse;
            }


            .fx-btn {

                width: 100%;

                min-height: 48px;
            }


            .fx-breadcrumb {
                margin-top: 18px;
            }
        }


        /*
         * Extra protection for very small phones.
         */
        @media (max-width: 380px) {

            .fx-container {
                padding-left: 8px;
                padding-right: 8px;
            }

            .fx-form {
                padding-left: 12px;
                padding-right: 12px;
            }

            .fx-hero {
                padding-left: 15px;
                padding-right: 15px;
            }
        }

    </style>

</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>


<!-- ==========================================================
     FIXED DASHBOARD SHELL
     ========================================================== -->

<div class="fx-app-shell flex">


    <?php include('sidebar.php'); ?>


    <!-- ==========================================================
         SCROLLABLE CONTENT AREA
         ========================================================== -->

    <div class="fx-content-scroll relative flex flex-1 flex-col">


        <?php include('header.php'); ?>


        <!-- ======================================================
             PAGE
             ====================================================== -->

        <main class="fx-page">


            <div class="fx-container">


                <!-- ==================================================
                     HERO
                     ================================================== -->

                <section class="fx-hero">

                    <div class="fx-kicker">

                        <span class="fx-kicker-dot"></span>

                        Inventory Configuration

                    </div>


                    <h1 class="fx-title">

                        New Product Category

                    </h1>


                    <p class="fx-description">

                        Create a structured product category and define how
                        products within it are measured, sourced and managed
                        throughout your inventory ecosystem.

                    </p>


                    <div class="fx-breadcrumb">

                        <a href="index.php">

                            <i class="fa-solid fa-house"></i>

                            Home

                        </a>


                        <i class="fa-solid fa-chevron-right"></i>


                        <span>

                            Product Categories

                        </span>


                        <i class="fa-solid fa-chevron-right"></i>


                        <span class="fx-current">

                            New Category

                        </span>

                    </div>

                </section>


                <!-- ==================================================
                     FORM CONTAINER
                     ================================================== -->

                <section class="fx-form-shell">


                    <form
                        method="POST"
                        action="create_category.php"
                        class="fx-form"
                    >


                        <!-- ==================================================
                             CATEGORY IDENTITY
                             ================================================== -->

                        <div class="fx-section">


                            <div class="fx-section-header">

                                <div class="fx-section-icon">

                                    <i class="fa-solid fa-layer-group"></i>

                                </div>


                                <div>

                                    <h2 class="fx-section-title">

                                        Category Identity

                                    </h2>


                                    <p class="fx-section-subtitle">

                                        Define the category and its hierarchy.

                                    </p>

                                </div>

                            </div>


                            <div class="fx-grid">


                                <!-- CATEGORY NAME -->

                                <div>

                                    <label class="fx-label">

                                        Category Name

                                        <span class="fx-required">*</span>

                                    </label>


                                    <input
                                        type="text"
                                        name="name"
                                        placeholder="e.g. Male Fashion"
                                        class="fx-input"
                                        required
                                    >


                                    <div class="fx-note">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Use a clear and recognizable category name.

                                    </div>

                                </div>


                                <!-- PARENT CATEGORY -->

                                <div>

                                    <label class="fx-label">

                                        Parent Category

                                        <span class="fx-optional">
                                            Optional
                                        </span>

                                    </label>


                                    <select
                                        name="parent_id"
                                        class="fx-select"
                                    >

                                        <option value="">
                                            -- None / Main Category --
                                        </option>


                                        <?php

                                        $stmt = $pdo->prepare("
                                            SELECT *
                                            FROM product_categories
                                            WHERE company_id = ?
                                            ORDER BY name ASC
                                        ");

                                        $stmt->execute([
                                            $_SESSION['company_id']
                                        ]);

                                        $categories =
                                            $stmt->fetchAll(
                                                PDO::FETCH_ASSOC
                                            );


                                        function renderCategoryOptions(
                                            $categories,
                                            $parentId = null,
                                            $prefix = ''
                                        ) {

                                            foreach (
                                                $categories
                                                as $cat
                                            ) {

                                                if (
                                                    $cat['parent_id']
                                                    == $parentId
                                                ) {

                                                    echo
                                                        '<option value="' .
                                                        htmlspecialchars(
                                                            $cat['id']
                                                        ) .
                                                        '">' .
                                                        htmlspecialchars(
                                                            $prefix .
                                                            $cat['name']
                                                        ) .
                                                        '</option>';


                                                    renderCategoryOptions(
                                                        $categories,
                                                        $cat['id'],
                                                        $prefix . '— '
                                                    );
                                                }
                                            }
                                        }


                                        renderCategoryOptions(
                                            $categories
                                        );

                                        ?>

                                    </select>


                                    <div class="fx-note">

                                        <i class="fa-solid fa-sitemap"></i>

                                        Organize this category beneath an
                                        existing category if required.

                                    </div>

                                </div>


                            </div>

                        </div>


                        <!-- ==================================================
                             UNIT CONFIGURATION
                             ================================================== -->

                        <div class="fx-section">


                            <div class="fx-section-header">

                                <div class="fx-section-icon">

                                    <i class="fa-solid fa-cubes-stacked"></i>

                                </div>


                                <div>

                                    <h2 class="fx-section-title">

                                        Unit Configuration

                                    </h2>


                                    <p class="fx-section-subtitle">

                                        Define how products are measured.

                                    </p>

                                </div>

                            </div>


                            <div class="fx-grid">


                                <!-- UNIT TYPE -->

                                <div>

                                    <label class="fx-label">

                                        Type of Unit

                                        <span class="fx-required">*</span>

                                    </label>


                                    <select
                                        name="unit_type"
                                        id="unitTypeSelect"
                                        required
                                        class="fx-select"
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


                                    <div class="fx-note">

                                        <i class="fa-solid fa-bolt"></i>

                                        Available units will update automatically.

                                    </div>

                                </div>


                                <!-- PRODUCT UNIT -->

                                <div>

                                    <label class="fx-label">

                                        Product Unit

                                        <span class="fx-required">*</span>

                                    </label>


                                    <select
                                        name="unit_id"
                                        id="unitSelect"
                                        class="fx-select"
                                        required
                                    >

                                        <option value="">
                                            -- Select Unit First --
                                        </option>

                                    </select>


                                    <div class="fx-note">

                                        <i class="fa-solid fa-ruler-combined"></i>

                                        This measurement unit will be attached
                                        to products in this category.

                                    </div>

                                </div>


                            </div>

                        </div>


                        <!-- ==================================================
                             INVENTORY BEHAVIOUR
                             ================================================== -->

                        <div class="fx-section">


                            <div class="fx-section-header">

                                <div class="fx-section-icon">

                                    <i class="fa-solid fa-chart-line"></i>

                                </div>


                                <div>

                                    <h2 class="fx-section-title">

                                        Inventory Behaviour

                                    </h2>


                                    <p class="fx-section-subtitle">

                                        Configure stock and sourcing behaviour.

                                    </p>

                                </div>

                            </div>


                            <div class="fx-grid">


                                <!-- STOCK TYPE -->

                                <div>

                                    <label class="fx-label">

                                        Stock Type

                                        <span class="fx-required">*</span>

                                    </label>


                                    <select
                                        name="stock_type"
                                        class="fx-select"
                                        required
                                    >

                                        <option value="">
                                            -- Select Stock Type --
                                        </option>


                                        <option value="Sales Only">
                                            Sales Only
                                        </option>


                                        <option value="Service Only">
                                            Service Only
                                        </option>

                                    </select>

                                </div>


                                <!-- SOURCE DEPARTMENT -->

                                <div>

                                    <label class="fx-label">

                                        Source Department

                                        <span class="fx-required">*</span>

                                    </label>


                                    <select
                                        name="dept"
                                        class="fx-select"
                                        required
                                    >

                                        <option value="">
                                            -- Choose Source --
                                        </option>


                                        <option value="Production Only">
                                            Production
                                        </option>


                                        <option value="Purchase Only">
                                            Purchase
                                        </option>

                                    </select>

                                </div>


                            </div>

                        </div>


                        <!-- ==================================================
                             DESCRIPTION
                             ================================================== -->

                        <div class="fx-section">


                            <div class="fx-section-header">

                                <div class="fx-section-icon">

                                    <i class="fa-solid fa-align-left"></i>

                                </div>


                                <div>

                                    <h2 class="fx-section-title">

                                        Category Description

                                    </h2>


                                    <p class="fx-section-subtitle">

                                        Add useful information about this category.

                                    </p>

                                </div>

                            </div>


                            <div>

                                <label class="fx-label">

                                    Description

                                    <span class="fx-optional">
                                        Optional
                                    </span>

                                </label>


                                <textarea
                                    name="description"
                                    placeholder="Brief description of this category..."
                                    class="fx-textarea"
                                ></textarea>

                            </div>

                        </div>


                        <!-- ==================================================
                             COMPANY ID
                             ================================================== -->

                        <input
                            type="hidden"
                            name="company_id"
                            value="<?= htmlspecialchars($_SESSION['company_id']) ?>"
                        >


                        <!-- ==================================================
                             ACTIONS
                             ================================================== -->

                        <div class="fx-actions">


                            <div class="fx-security">

                                <div class="fx-security-icon">

                                    <i class="fa-solid fa-shield-halved"></i>

                                </div>


                                <span>

                                    Category configuration is securely
                                    associated with your company.

                                </span>

                            </div>


                            <div class="fx-buttons">


                                <a
                                    href="categories.php"
                                    class="fx-btn fx-btn-cancel"
                                >

                                    <i class="fa-solid fa-arrow-left"></i>

                                    Cancel

                                </a>


                                <button
                                    type="submit"
                                    class="fx-btn fx-btn-create"
                                >

                                    <i class="fa-solid fa-plus"></i>

                                    Create Category

                                </button>

                            </div>


                        </div>


                    </form>


                </section>


            </div>


        </main>


        <!-- ==========================================================
             FOOTER IS NOW INSIDE THE SCROLLABLE AREA
             ========================================================== -->

        <?php include('footer.php'); ?>


    </div>

</div>


<!-- ==========================================================
     EXISTING AJAX LOGIC — UNCHANGED
     ========================================================== -->

<script>

document
    .getElementById('unitTypeSelect')
    .addEventListener('change', function () {

        let unitType = this.value;

        let xhr = new XMLHttpRequest();

        xhr.open(
            "POST",
            "fetch_units_by_type.php",
            true
        );

        xhr.setRequestHeader(
            "Content-Type",
            "application/x-www-form-urlencoded"
        );

        xhr.onload = function () {

            if (this.status === 200) {

                document
                    .getElementById('unitSelect')
                    .innerHTML = this.responseText;

            }

        };

        xhr.send(
            "unit_type=" +
            encodeURIComponent(unitType)
        );

    });

</script>


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
    data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":"cfExtPri","cfExtPri":"true","cfSpeedBrain":"true","cfCacheStatus":"true"}}'
    crossorigin="anonymous"
></script>


</body>

</html>
