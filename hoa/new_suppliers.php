
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$companyid = (int)($_SESSION['company_id'] ?? 0);

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
        <?php echo htmlspecialchars($company_name); ?> | Register Vendor
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

        /* =========================================================
           VENDOR REGISTRATION
           FUTURISTIC GLASSMORPHIC INTERFACE
           LIGHT-FIRST / RESPONSIVE
           ========================================================= */

        :root {

            --vendor-primary: #6839eb;
            --vendor-primary-dark: #5226cf;
            --vendor-primary-light: #8b5cf6;

            --vendor-blue: #3b82f6;
            --vendor-green: #10b981;

            --vendor-bg: #f6f8fc;

            --vendor-text: #111827;
            --vendor-heading: #0f172a;
            --vendor-muted: #64748b;
            --vendor-soft: #94a3b8;

            --vendor-border: rgba(15, 23, 42, 0.09);

            --vendor-glass: rgba(255, 255, 255, 0.78);
            --vendor-glass-strong: rgba(255, 255, 255, 0.90);

            --vendor-radius: 26px;

            --vendor-shadow:
                0 30px 80px rgba(15, 23, 42, 0.09),
                0 8px 30px rgba(15, 23, 42, 0.04);

        }


        /* =========================================================
           RESET
           ========================================================= */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            min-height: 100vh;
            background:

                radial-gradient(
                    circle at 5% 5%,
                    rgba(104, 57, 235, 0.11),
                    transparent 28%
                ),

                radial-gradient(
                    circle at 95% 10%,
                    rgba(59, 130, 246, 0.08),
                    transparent 26%
                ),

                radial-gradient(
                    circle at 70% 90%,
                    rgba(16, 185, 129, 0.045),
                    transparent 25%
                ),

                var(--vendor-bg);

            color: var(--vendor-text);

        }


        /* =========================================================
           PAGE
           ========================================================= */

        .vendor-page {

            width: 100%;
            max-width: 1500px;

            margin: 0 auto;

            padding:
                18px
                0
                45px;

        }


        /* =========================================================
           MAIN GLASS SHELL
           ========================================================= */

        .vendor-shell {

            position: relative;

            width: 100%;

            overflow: hidden;

            border:
                1px solid
                rgba(255, 255, 255, 0.92);

            border-radius: var(--vendor-radius);

            background:
                linear-gradient(
                    135deg,
                    rgba(255, 255, 255, 0.86),
                    rgba(255, 255, 255, 0.67)
                );

            backdrop-filter:
                blur(30px)
                saturate(140%);

            -webkit-backdrop-filter:
                blur(30px)
                saturate(140%);

            box-shadow: var(--vendor-shadow);

        }


        /* =========================================================
           DECORATIVE ORBS
           ========================================================= */

        .vendor-shell::before {

            content: "";

            position: absolute;

            width: 330px;
            height: 330px;

            top: -210px;
            right: -120px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(104, 57, 235, 0.17),
                    rgba(104, 57, 235, 0.03) 60%,
                    transparent 72%
                );

            pointer-events: none;

        }


        .vendor-shell::after {

            content: "";

            position: absolute;

            width: 280px;
            height: 280px;

            bottom: -190px;
            left: -120px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(59, 130, 246, 0.10),
                    transparent 68%
                );

            pointer-events: none;

        }


        /* =========================================================
           HEADER
           ========================================================= */

        .vendor-header {

            position: relative;
            z-index: 2;

            padding:
                30px
                32px
                27px;

            border-bottom:
                1px solid
                rgba(15, 23, 42, 0.07);

        }


        .vendor-header-top {

            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 25px;

        }


        .vendor-title-area {

            display: flex;

            align-items: center;

            gap: 16px;

            min-width: 0;

        }


        /* =========================================================
           MAIN ICON
           ========================================================= */

        .vendor-icon {

            position: relative;

            width: 58px;
            height: 58px;

            flex:
                0 0
                58px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 18px;

            background:

                linear-gradient(
                    135deg,
                    rgba(104, 57, 235, 0.18),
                    rgba(104, 57, 235, 0.06)
                );

            border:
                1px solid
                rgba(104, 57, 235, 0.20);

            color:
                var(--vendor-primary);

            font-size: 22px;

            box-shadow:

                0 14px 35px
                rgba(104, 57, 235, 0.13);

        }


        .vendor-icon::after {

            content: "";

            position: absolute;

            width: 7px;
            height: 7px;

            top: 7px;
            right: 7px;

            border-radius: 50%;

            background:
                var(--vendor-green);

            box-shadow:
                0 0 0 4px
                rgba(16, 185, 129, 0.10);

        }


        /* =========================================================
           TITLES
           ========================================================= */

        .vendor-title {

            margin: 0;

            color:
                var(--vendor-heading);

            font-size:
                clamp(
                    1.35rem,
                    2vw,
                    1.9rem
                );

            line-height: 1.15;

            font-weight: 800;

            letter-spacing: -0.04em;

        }


        .vendor-subtitle {

            margin:
                7px
                0
                0;

            color:
                var(--vendor-muted);

            font-size:
                0.88rem;

            line-height: 1.5;

        }


        /* =========================================================
           STATUS
           ========================================================= */

        .vendor-status {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            flex-shrink: 0;

            padding:
                10px
                14px;

            border-radius: 999px;

            background:
                rgba(16, 185, 129, 0.07);

            border:
                1px solid
                rgba(16, 185, 129, 0.16);

            color:
                #047857;

            font-size:
                0.72rem;

            font-weight:
                800;

            letter-spacing:
                0.02em;

        }


        .vendor-status-dot {

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background:
                #10b981;

            box-shadow:
                0 0 0 4px
                rgba(16, 185, 129, 0.10);

        }


        /* =========================================================
           BREADCRUMB
           ========================================================= */

        .vendor-breadcrumb {

            position: relative;
            z-index: 2;

            padding:
                13px
                32px;

            background:
                rgba(248, 250, 252, 0.55);

            border-bottom:
                1px solid
                rgba(15, 23, 42, 0.06);

        }


        .vendor-breadcrumb ol {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin: 0;
            padding: 0;

            list-style: none;

        }


        .vendor-breadcrumb a {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            color:
                #64748b;

            text-decoration: none;

            font-size:
                0.75rem;

            font-weight:
                600;

            transition:
                color 0.2s ease;

        }


        .vendor-breadcrumb a:hover {

            color:
                var(--vendor-primary);

        }


        .vendor-breadcrumb-current {

            color:
                #1e293b;

            font-size:
                0.75rem;

            font-weight:
                750;

        }


        /* =========================================================
           FORM AREA
           ========================================================= */

        .vendor-form-area {

            position: relative;
            z-index: 2;

            padding:
                30px
                32px
                34px;

        }


        /* =========================================================
           INTRO PANEL
           ========================================================= */

        .vendor-intro {

            position: relative;

            margin-bottom:
                28px;

            padding:
                19px
                20px;

            border-radius:
                18px;

            background:

                linear-gradient(
                    135deg,
                    rgba(104, 57, 235, 0.075),
                    rgba(255, 255, 255, 0.72)
                );

            border:
                1px solid
                rgba(104, 57, 235, 0.11);

            box-shadow:
                0 10px 30px
                rgba(104, 57, 235, 0.035);

        }


        .vendor-intro-row {

            display: flex;

            align-items: flex-start;

            gap: 13px;

        }


        .vendor-intro-icon {

            width: 38px;
            height: 38px;

            flex:
                0 0
                38px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background:
                rgba(104, 57, 235, 0.11);

            color:
                var(--vendor-primary);

            font-size:
                14px;

        }


        .vendor-intro strong {

            display: block;

            margin-bottom:
                4px;

            color:
                #1e293b;

            font-size:
                0.87rem;

            font-weight:
                800;

        }


        .vendor-intro span {

            display: block;

            color:
                #64748b;

            font-size:
                0.78rem;

            line-height:
                1.6;

        }


        /* =========================================================
           FORM GRID
           ========================================================= */

        .vendor-form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap:
                22px;

        }


        .vendor-field {

            min-width: 0;

        }


        .vendor-field.full {

            grid-column:
                1 / -1;

        }


        /* =========================================================
           FIELD LABEL
           ========================================================= */

        .vendor-label {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom:
                8px;

            color:
                #334155;

            font-size:
                0.76rem;

            font-weight:
                800;

            letter-spacing:
                0.01em;

        }


        .vendor-label i {

            width: 17px;

            color:
                var(--vendor-primary);

            font-size:
                11px;

            text-align:
                center;

        }


        .required-mark {

            color:
                #ef4444;

            margin-left:
                1px;

        }


        /* =========================================================
           INPUTS
           ========================================================= */

        .vendor-control {

            width: 100%;

            min-height:
                50px;

            display: block;

            border:
                1px solid
                rgba(148, 163, 184, 0.35);

            border-radius:
                14px;

            background:
                rgba(255, 255, 255, 0.72);

            color:
                #172033;

            padding:
                13px
                15px;

            outline: none;

            font-family:
                inherit;

            font-size:
                0.84rem;

            font-weight:
                550;

            transition:

                border-color
                0.2s ease,

                box-shadow
                0.2s ease,

                background
                0.2s ease,

                transform
                0.2s ease;

        }


        .vendor-control::placeholder {

            color:
                #94a3b8;

            font-weight:
                450;

        }


        .vendor-control:hover {

            border-color:
                rgba(104, 57, 235, 0.28);

            background:
                rgba(255, 255, 255, 0.84);

        }


        .vendor-control:focus {

            border-color:
                rgba(104, 57, 235, 0.60);

            background:
                rgba(255, 255, 255, 0.98);

            box-shadow:

                0 0 0 4px
                rgba(104, 57, 235, 0.08),

                0 12px 28px
                rgba(15, 23, 42, 0.045);

        }


        /* =========================================================
           INPUT WITH ICON
           ========================================================= */

        .vendor-input-wrap {

            position: relative;

        }


        .vendor-input-wrap .vendor-control {

            padding-left:
                43px;

        }


        .vendor-input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform:
                translateY(-50%);

            color:
                #94a3b8;

            font-size:
                12px;

            pointer-events:
                none;

            transition:
                color 0.2s ease;

        }


        .vendor-input-wrap:focus-within
        .vendor-input-icon {

            color:
                var(--vendor-primary);

        }


        /* =========================================================
           MOBILE NUMBER
           ========================================================= */

        input[type="tel"] {

            appearance: none;
            -webkit-appearance: none;

        }


        /* =========================================================
           LOCATION
           VENDOR LOCATION IS A TEXT INPUT
           ========================================================= */

        .location-field {

            position: relative;

        }


        .location-input-wrap {

            position: relative;

        }


        .location-input-wrap .vendor-control {

            padding-left:
                45px;

        }


        .location-icon {

            position: absolute;

            left: 16px;

            top: 50%;

            transform:
                translateY(-50%);

            color:
                var(--vendor-primary);

            font-size:
                13px;

            pointer-events:
                none;

        }


        .location-helper {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-top:
                8px;

            color:
                #94a3b8;

            font-size:
                0.69rem;

            line-height:
                1.45;

        }


        .location-helper i {

            color:
                var(--vendor-primary);

            font-size:
                10px;

        }


        /* =========================================================
           TEXTAREA
           ========================================================= */

        textarea.vendor-control {

            min-height:
                125px;

            resize:
                vertical;

            line-height:
                1.6;

        }


        /* =========================================================
           SECTION DIVIDER
           ========================================================= */

        .vendor-section-label {

            grid-column:
                1 / -1;

            display: flex;

            align-items: center;

            gap: 10px;

            margin-top:
                2px;

            margin-bottom:
                -5px;

            color:
                #94a3b8;

            font-size:
                0.67rem;

            font-weight:
                800;

            letter-spacing:
                0.12em;

            text-transform:
                uppercase;

        }


        .vendor-section-label::after {

            content: "";

            height: 1px;

            flex: 1;

            background:
                rgba(148, 163, 184, 0.18);

        }


        /* =========================================================
           ACTION AREA
           ========================================================= */

        .vendor-actions {

            grid-column:
                1 / -1;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-top:
                5px;

            padding-top:
                25px;

            border-top:
                1px solid
                rgba(15, 23, 42, 0.07);

        }


        .vendor-action-left {

            display: flex;

            align-items: center;

            gap: 8px;

            color:
                #94a3b8;

            font-size:
                0.7rem;

            line-height:
                1.5;

        }


        .vendor-action-left i {

            color:
                var(--vendor-green);

        }


        .vendor-action-buttons {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        /* =========================================================
           BUTTONS
           ========================================================= */

        .vendor-btn {

            min-height:
                47px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 8px;

            border-radius:
                13px;

            padding:
                12px
                20px;

            font-family:
                inherit;

            font-size:
                0.78rem;

            font-weight:
                800;

            text-decoration:
                none;

            border:
                1px solid
                transparent;

            cursor:
                pointer;

            transition:

                transform
                0.2s ease,

                box-shadow
                0.2s ease,

                background
                0.2s ease,

                border-color
                0.2s ease;

        }


        .vendor-btn:hover {

            transform:
                translateY(-2px);

        }


        .vendor-btn:active {

            transform:
                translateY(0);

        }


        /* PRIMARY */

        .vendor-btn-primary {

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #6839eb,
                    #5226cf
                );

            box-shadow:

                0 12px 30px
                rgba(104, 57, 235, 0.23);

        }


        .vendor-btn-primary:hover {

            box-shadow:

                0 16px 38px
                rgba(104, 57, 235, 0.31);

        }


        .vendor-btn-primary i {

            font-size:
                11px;

        }


        /* SECONDARY */

        .vendor-btn-secondary {

            color:
                #475569;

            background:
                rgba(255, 255, 255, 0.70);

            border-color:
                rgba(148, 163, 184, 0.30);

        }


        .vendor-btn-secondary:hover {

            color:
                #334155;

            background:
                #ffffff;

            border-color:
                rgba(104, 57, 235, 0.22);

        }


        /* =========================================================
           SECURITY BADGE
           ========================================================= */

        .vendor-security {

            display: flex;

            align-items: center;

            gap: 8px;

            padding:
                9px
                12px;

            border-radius:
                11px;

            background:
                rgba(16, 185, 129, 0.055);

            border:
                1px solid
                rgba(16, 185, 129, 0.10);

            color:
                #047857;

            font-size:
                0.67rem;

            font-weight:
                700;

        }


        .vendor-security i {

            font-size:
                10px;

        }


        /* =========================================================
           DARK MODE SUPPORT
           ALPINE STATE PRESERVED
           ========================================================= */

        .dark .vendor-shell {

            background:
                rgba(17, 24, 39, 0.82);

            border-color:
                rgba(255, 255, 255, 0.06);

        }


        .dark .vendor-title {

            color:
                #f8fafc;

        }


        .dark .vendor-subtitle {

            color:
                #94a3b8;

        }


        .dark .vendor-breadcrumb {

            background:
                rgba(15, 23, 42, 0.45);

            border-color:
                rgba(255, 255, 255, 0.05);

        }


        .dark .vendor-breadcrumb-current {

            color:
                #e2e8f0;

        }


        .dark .vendor-intro {

            background:

                linear-gradient(
                    135deg,
                    rgba(104, 57, 235, 0.13),
                    rgba(15, 23, 42, 0.45)
                );

            border-color:
                rgba(104, 57, 235, 0.15);

        }


        .dark .vendor-intro strong {

            color:
                #f1f5f9;

        }


        .dark .vendor-intro span {

            color:
                #94a3b8;

        }


        .dark .vendor-label {

            color:
                #cbd5e1;

        }


        .dark .vendor-control {

            background:
                rgba(15, 23, 42, 0.72);

            border-color:
                rgba(71, 85, 105, 0.60);

            color:
                #f8fafc;

        }


        .dark .vendor-control:focus {

            background:
                rgba(15, 23, 42, 0.94);

        }


        .dark .vendor-control::placeholder {

            color:
                #64748b;

        }


        .dark .vendor-actions {

            border-color:
                rgba(255, 255, 255, 0.06);

        }


        .dark .vendor-action-left {

            color:
                #64748b;

        }


        .dark .vendor-btn-secondary {

            background:
                rgba(15, 23, 42, 0.72);

            color:
                #cbd5e1;

            border-color:
                rgba(71, 85, 105, 0.55);

        }


        .dark .vendor-btn-secondary:hover {

            background:
                rgba(30, 41, 59, 0.88);

            color:
                #f8fafc;

        }


        /* =========================================================
           TABLET
           ========================================================= */

        @media (max-width: 1100px) {

            .vendor-page {

                padding-top:
                    12px;

            }


            .vendor-shell {

                border-radius:
                    22px;

            }


            .vendor-header,
            .vendor-breadcrumb,
            .vendor-form-area {

                padding-left:
                    24px;

                padding-right:
                    24px;

            }

        }


        /* =========================================================
           MOBILE
           ========================================================= */

        @media (max-width: 768px) {

            .vendor-page {

                padding:
                    8px
                    0
                    30px;

            }


            .vendor-shell {

                border-radius:
                    18px;

                box-shadow:

                    0 18px 50px
                    rgba(15, 23, 42, 0.08);

            }


            .vendor-header {

                padding:
                    20px
                    18px;

            }


            .vendor-header-top {

                align-items:
                    flex-start;

            }


            .vendor-title-area {

                gap:
                    11px;

            }


            .vendor-icon {

                width:
                    45px;

                height:
                    45px;

                flex-basis:
                    45px;

                border-radius:
                    13px;

                font-size:
                    17px;

            }


            .vendor-title {

                font-size:
                    1.27rem;

            }


            .vendor-subtitle {

                margin-top:
                    5px;

                font-size:
                    0.73rem;

            }


            .vendor-status {

                display:
                    none;

            }


            .vendor-breadcrumb {

                padding:
                    11px
                    18px;

            }


            .vendor-form-area {

                padding:
                    20px
                    18px
                    24px;

            }


            .vendor-intro {

                padding:
                    15px;

                margin-bottom:
                    21px;

                border-radius:
                    15px;

            }


            .vendor-intro-icon {

                width:
                    34px;

                height:
                    34px;

                flex-basis:
                    34px;

            }


            .vendor-intro strong {

                font-size:
                    0.80rem;

            }


            .vendor-intro span {

                font-size:
                    0.71rem;

            }


            .vendor-form-grid {

                grid-template-columns:
                    1fr;

                gap:
                    18px;

            }


            .vendor-field.full {

                grid-column:
                    auto;

            }


            .vendor-section-label {

                grid-column:
                    auto;

            }


            .vendor-actions {

                grid-column:
                    auto;

                flex-direction:
                    column;

                align-items:
                    stretch;

            }


            .vendor-action-left {

                justify-content:
                    center;

                text-align:
                    center;

                order:
                    2;

            }


            .vendor-action-buttons {

                width:
                    100%;

                display:
                    grid;

                grid-template-columns:
                    1fr 1fr;

            }


            .vendor-btn {

                width:
                    100%;

                padding-left:
                    12px;

                padding-right:
                    12px;

            }


            .vendor-security {

                justify-content:
                    center;

            }

        }


        /* =========================================================
           SMALL MOBILE
           ========================================================= */

        @media (max-width: 480px) {

            .vendor-header {

                padding:
                    17px
                    14px;

            }


            .vendor-breadcrumb {

                padding:
                    10px
                    14px;

            }


            .vendor-form-area {

                padding:
                    17px
                    14px
                    21px;

            }


            .vendor-title {

                font-size:
                    1.13rem;

            }


            .vendor-subtitle {

                font-size:
                    0.68rem;

            }


            .vendor-icon {

                width:
                    42px;

                height:
                    42px;

                flex-basis:
                    42px;

            }


            .vendor-control {

                min-height:
                    47px;

                border-radius:
                    12px;

                padding:
                    11px
                    13px;

                font-size:
                    0.81rem;

            }


            .vendor-input-wrap .vendor-control,
            .location-input-wrap .vendor-control {

                padding-left:
                    41px;

            }


            textarea.vendor-control {

                min-height:
                    112px;

            }


            .vendor-action-buttons {

                grid-template-columns:
                    1fr;

            }


            .vendor-btn {

                min-height:
                    45px;

            }

        }


        /* =========================================================
           VERY SMALL DEVICES
           ========================================================= */

        @media (max-width: 350px) {

            .vendor-title-area {

                gap:
                    8px;

            }


            .vendor-icon {

                width:
                    38px;

                height:
                    38px;

                flex-basis:
                    38px;

                font-size:
                    14px;

            }


            .vendor-title {

                font-size:
                    1rem;

            }


            .vendor-subtitle {

                font-size:
                    0.63rem;

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


<!-- =========================================================
     PAGE WRAPPER
     ========================================================= -->

<div class="flex h-screen overflow-hidden">


    <?php include('sidebar.php'); ?>


    <!-- =====================================================
         CONTENT AREA
         ===================================================== -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <!-- =================================================
             MAIN
             ================================================= -->

        <main>


            <?php include('header.php'); ?>


            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">


                <div class="vendor-page">


                    <!-- =================================================
                         GLASS SHELL
                         ================================================= -->

                    <section class="vendor-shell">


                        <!-- =================================================
                             HEADER
                             ================================================= -->

                        <header class="vendor-header">


                            <div class="vendor-header-top">


                                <div class="vendor-title-area">


                                    <div class="vendor-icon">

                                        <i class="fas fa-store-alt"></i>

                                    </div>


                                    <div>

                                        <h1 class="vendor-title">

                                            Register Vendor

                                        </h1>


                                        <p class="vendor-subtitle">

                                            Add a trusted vendor to your company's
                                            procurement and inventory network.

                                        </p>

                                    </div>


                                </div>


                                <div class="vendor-status">

                                    <span class="vendor-status-dot"></span>

                                    Vendor Network

                                </div>


                            </div>


                        </header>


                        <!-- =================================================
                             BREADCRUMB
                             ================================================= -->

                        <div class="vendor-breadcrumb">


                            <nav aria-label="Breadcrumb">


                                <ol>


                                    <li>

                                        <a href="index.php">

                                            <i class="fas fa-house"></i>

                                            Home

                                        </a>

                                    </li>


                                    <li>

                                        <i
                                            class="fas fa-chevron-right"
                                            style="
                                                font-size:8px;
                                                color:#94a3b8;
                                            "
                                        ></i>

                                    </li>


                                    <li class="vendor-breadcrumb-current">

                                        Register Vendor

                                    </li>


                                </ol>


                            </nav>


                        </div>


                        <!-- =================================================
                             FORM AREA
                             ================================================= -->

                        <div class="vendor-form-area">


                            <!-- =================================================
                                 INTRO
                                 ================================================= -->

                            <div class="vendor-intro">


                                <div class="vendor-intro-row">


                                    <div class="vendor-intro-icon">

                                        <i class="fas fa-handshake"></i>

                                    </div>


                                    <div>

                                        <strong>

                                            Aaaayah! Product Vendors,
                                            thanks for coming onboard.....

                                        </strong>


                                        <span>

                                            Register accurate vendor information
                                            so your purchasing, inventory and
                                            production records remain properly
                                            connected.

                                        </span>

                                    </div>


                                </div>


                            </div>


                            <!-- =================================================
                                 FORM
                                 ================================================= -->

                            <form
                                method="POST"
                                action="register_vendor.php"
                                autocomplete="off"
                            >


                                <div class="vendor-form-grid">


                                    <!-- =========================================
                                         SECTION
                                         ========================================= -->

                                    <div class="vendor-section-label">

                                        <span>Vendor Identity</span>

                                    </div>


                                    <!-- =========================================
                                         BUSINESS NAME
                                         ========================================= -->

                                    <div class="vendor-field">


                                        <label class="vendor-label">

                                            <i class="fas fa-building"></i>

                                            Business Name

                                            <span class="required-mark">*</span>

                                        </label>


                                        <div class="vendor-input-wrap">


                                            <i
                                                class="fas fa-store vendor-input-icon"
                                            ></i>


                                            <input

                                                type="text"

                                                name="business_name"

                                                placeholder="e.g. Male Fashion Ltd."

                                                class="vendor-control"

                                                maxlength="255"

                                                required

                                            >

                                        </div>


                                    </div>


                                    <!-- =========================================
                                         OFFICE ADDRESS
                                         ========================================= -->

                                    <div class="vendor-field">


                                        <label class="vendor-label">

                                            <i class="fas fa-map-location-dot"></i>

                                            Office Address

                                            <span class="required-mark">*</span>

                                        </label>


                                        <div class="vendor-input-wrap">


                                            <i
                                                class="fas fa-location-dot vendor-input-icon"
                                            ></i>


                                            <input

                                                type="text"

                                                name="office_address"

                                                placeholder="Enter vendor office address"

                                                class="vendor-control"

                                                maxlength="500"

                                                required

                                            >

                                        </div>


                                    </div>


                                    <!-- =========================================
                                         EMAIL
                                         ========================================= -->

                                    <div class="vendor-field">


                                        <label class="vendor-label">

                                            <i class="fas fa-envelope"></i>

                                            Email Address

                                            <span class="required-mark">*</span>

                                        </label>


                                        <div class="vendor-input-wrap">


                                            <i
                                                class="fas fa-at vendor-input-icon"
                                            ></i>


                                            <input

                                                type="email"

                                                name="email"

                                                placeholder="vendor@example.com"

                                                class="vendor-control"

                                                maxlength="255"

                                                required

                                            >

                                        </div>


                                    </div>


                                    <!-- =========================================
                                         MOBILE
                                         ========================================= -->

                                    <div class="vendor-field">


                                        <label class="vendor-label">

                                            <i class="fas fa-phone"></i>

                                            Mobile Number

                                            <span class="required-mark">*</span>

                                        </label>


                                        <div class="vendor-input-wrap">


                                            <i
                                                class="fas fa-mobile-screen-button vendor-input-icon"
                                            ></i>


                                            <input

                                                type="tel"

                                                name="mobile"

                                                placeholder="e.g. 08012345678"

                                                class="vendor-control"

                                                maxlength="30"

                                                inputmode="tel"

                                                required

                                            >

                                        </div>


                                    </div>


                                    <!-- =========================================
                                         VENDOR LOCATION
                                         ========================================= -->

                                    <div class="vendor-field full location-field">


                                        <label class="vendor-label">

                                            <i class="fas fa-map-marker-alt"></i>

                                            Vendor Location

                                            <span class="required-mark">*</span>

                                        </label>


                                        <div class="location-input-wrap">


                                            <i
                                                class="fas fa-earth-africa location-icon"
                                            ></i>


                                            <input

                                                type="text"

                                                name="location"

                                                placeholder="e.g. Onitsha, Anambra State, Nigeria"

                                                class="vendor-control"

                                                maxlength="255"

                                                autocomplete="address-level2"

                                                required

                                            >

                                        </div>


                                        <div class="location-helper">

                                            <i class="fas fa-circle-info"></i>

                                            Enter the physical location or city/state
                                            where this vendor operates.

                                        </div>


                                    </div>


                                    <!-- =========================================
                                         DESCRIPTION
                                         ========================================= -->

                                    <div class="vendor-field full">


                                        <label class="vendor-label">

                                            <i class="fas fa-align-left"></i>

                                            Vendor Description

                                        </label>


                                        <textarea

                                            name="description"

                                            placeholder="Briefly describe this vendor, the products they supply, or any important procurement information..."

                                            class="vendor-control"

                                            maxlength="2000"

                                        ></textarea>


                                    </div>


                                    <!-- =========================================
                                         COMPANY ID
                                         ========================================= -->

                                    <input

                                        type="hidden"

                                        name="company_id"

                                        value="<?= htmlspecialchars(
                                            (string)$companyid,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"

                                    >


                                    <!-- =========================================
                                         SECURITY INFORMATION
                                         ========================================= -->

                                    <div class="vendor-actions">


                                        <div class="vendor-action-left">


                                            <div class="vendor-security">

                                                <i class="fas fa-shield-halved"></i>

                                                Company-linked vendor record

                                            </div>


                                        </div>


                                        <!-- =====================================
                                             BUTTONS
                                             ===================================== -->

                                        <div class="vendor-action-buttons">


                                            <a

                                                href="categories.php"

                                                class="vendor-btn vendor-btn-secondary"

                                            >

                                                <i class="fas fa-arrow-left"></i>

                                                Cancel

                                            </a>


                                            <button

                                                type="submit"

                                                class="vendor-btn vendor-btn-primary"

                                            >

                                                <i class="fas fa-plus"></i>

                                                Register Vendor

                                            </button>


                                        </div>


                                    </div>


                                </div>


                            </form>


                        </div>


                    </section>


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
     EXISTING APPLICATION BUNDLE
     ========================================================= -->

<script
    defer
    src="bundle.js"
></script>


</body>

</html>
