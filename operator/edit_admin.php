<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


/* ============================================================
   COMPANY / USER CONTEXT
   ============================================================ */

$company_id = $_SESSION['company_id'] ?? '';

if (isset($_POST['submit'])) {
    $company_id = $_POST['company_id'] ?? $company_id;
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
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?php echo htmlspecialchars($company_name ?? 'Company'); ?> | Edit Profile
    </title>


    <!-- =========================================================
         ICONS / EXISTING STYLES
         ========================================================= -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >


    <!-- =========================================================
         FUTURISTIC PROFILE EDITOR UI
         ========================================================= -->

    <style>

        /* =========================================================
           ROOT
           ========================================================= */

        :root {

            --profile-primary: #6839eb;
            --profile-primary-dark: #5226cf;

            --profile-blue: #3b82f6;
            --profile-cyan: #06b6d4;

            --profile-text: #111827;
            --profile-text-soft: #334155;
            --profile-muted: #64748b;

            --profile-border: rgba(148, 163, 184, .28);

            --profile-surface: rgba(255, 255, 255, .78);

            --profile-surface-strong: rgba(255, 255, 255, .92);

            --profile-shadow:
                0 25px 80px rgba(15, 23, 42, .09),
                0 5px 20px rgba(15, 23, 42, .04);
        }


        /* =========================================================
           GLOBAL
           ========================================================= */

        * {
            box-sizing: border-box;
        }


        html,
        body {
            min-height: 100%;
        }


        body {

            background:

                radial-gradient(
                    circle at 5% 5%,
                    rgba(104, 57, 235, .09),
                    transparent 28%
                ),

                radial-gradient(
                    circle at 95% 10%,
                    rgba(59, 130, 246, .07),
                    transparent 26%
                ),

                radial-gradient(
                    circle at 80% 90%,
                    rgba(6, 182, 212, .045),
                    transparent 25%
                ),

                #f8fafc;
        }


        /* =========================================================
           PROFILE PAGE
           ========================================================= */

        .profile-page {

            width: 100%;

            max-width: 1500px;

            margin: 0 auto;

            padding:
                18px
                0
                45px;
        }


        /* =========================================================
           MAIN GLASS CONTAINER
           ========================================================= */

        .profile-shell {

            position: relative;

            overflow: hidden;

            border-radius: 28px;

            border:
                1px solid rgba(255, 255, 255, .85);

            background:
                rgba(255, 255, 255, .70);

            backdrop-filter:
                blur(26px);

            -webkit-backdrop-filter:
                blur(26px);

            box-shadow:
                var(--profile-shadow);
        }


        .profile-shell::before {

            content: "";

            position: absolute;

            width: 360px;
            height: 360px;

            top: -230px;
            right: -100px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(104, 57, 235, .13),
                    transparent 68%
                );

            pointer-events: none;
        }


        .profile-shell::after {

            content: "";

            position: absolute;

            width: 280px;
            height: 280px;

            bottom: -190px;
            left: -100px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(59, 130, 246, .08),
                    transparent 68%
                );

            pointer-events: none;
        }


        /* =========================================================
           TOP HEADER
           ========================================================= */

        .profile-header {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding:
                28px
                30px;

            border-bottom:
                1px solid rgba(15, 23, 42, .07);
        }


        .profile-header-left {

            display: flex;

            align-items: center;

            gap: 15px;

            min-width: 0;
        }


        .profile-header-icon {

            width: 54px;
            height: 54px;

            flex: 0 0 54px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 17px;

            background:

                linear-gradient(
                    135deg,
                    rgba(104, 57, 235, .15),
                    rgba(59, 130, 246, .07)
                );

            border:
                1px solid rgba(104, 57, 235, .17);

            color:
                var(--profile-primary);

            font-size: 21px;

            box-shadow:
                0 12px 35px rgba(104, 57, 235, .10);
        }


        .profile-header-title {

            margin: 0;

            color:
                var(--profile-text);

            font-size:
                clamp(
                    1.35rem,
                    2vw,
                    1.85rem
                );

            line-height: 1.15;

            font-weight: 800;

            letter-spacing:
                -.04em;
        }


        .profile-header-subtitle {

            margin:
                5px
                0
                0;

            color:
                var(--profile-muted);

            font-size:
                .84rem;

            line-height:
                1.5;
        }


        /* =========================================================
           SYSTEM STATUS
           ========================================================= */

        .profile-system-status {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding:
                9px
                13px;

            border-radius: 999px;

            background:
                rgba(16, 185, 129, .07);

            border:
                1px solid rgba(16, 185, 129, .15);

            color:
                #047857;

            font-size:
                .72rem;

            font-weight:
                750;

            white-space:
                nowrap;
        }


        .profile-system-dot {

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background:
                #10b981;

            box-shadow:
                0 0 0 4px
                rgba(16, 185, 129, .10);
        }


        /* =========================================================
           BREADCRUMB
           ========================================================= */

        .profile-breadcrumb {

            position: relative;

            z-index: 2;

            padding:
                13px
                30px;

            background:
                rgba(248, 250, 252, .55);

            border-bottom:
                1px solid rgba(15, 23, 42, .06);
        }


        .profile-breadcrumb-list {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin: 0;

            padding: 0;

            list-style: none;
        }


        .profile-breadcrumb a {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            color:
                #64748b;

            text-decoration: none;

            font-size:
                .76rem;

            transition:
                color .2s ease;
        }


        .profile-breadcrumb a:hover {

            color:
                var(--profile-primary);
        }


        .profile-breadcrumb-current {

            color:
                #1e293b;

            font-size:
                .76rem;

            font-weight:
                700;
        }


        .breadcrumb-arrow {

            color:
                #94a3b8;

            font-size:
                8px;
        }


        /* =========================================================
           CONTENT
           ========================================================= */

        .profile-content {

            position: relative;

            z-index: 2;

            padding:
                30px;
        }


        /* =========================================================
           INTRO / IDENTITY BANNER
           ========================================================= */

        .profile-intro {

            position: relative;

            overflow: hidden;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding:
                20px
                22px;

            margin-bottom:
                24px;

            border-radius:
                20px;

            background:

                linear-gradient(
                    135deg,
                    rgba(104, 57, 235, .08),
                    rgba(255, 255, 255, .76)
                );

            border:
                1px solid
                rgba(104, 57, 235, .11);
        }


        .profile-intro-left {

            display: flex;

            align-items: center;

            gap: 13px;
        }


        .profile-intro-icon {

            width: 40px;
            height: 40px;

            flex: 0 0 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius:
                12px;

            background:
                rgba(104, 57, 235, .10);

            color:
                var(--profile-primary);

            font-size:
                15px;
        }


        .profile-intro-title {

            display: block;

            margin-bottom:
                3px;

            color:
                #1e293b;

            font-size:
                .87rem;

            font-weight:
                750;
        }


        .profile-intro-description {

            display: block;

            color:
                #64748b;

            font-size:
                .77rem;

            line-height:
                1.5;
        }


        .profile-intro-badge {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding:
                8px
                11px;

            border-radius:
                10px;

            background:
                rgba(255, 255, 255, .65);

            border:
                1px solid
                rgba(148, 163, 184, .22);

            color:
                #475569;

            font-size:
                .68rem;

            font-weight:
                700;

            white-space:
                nowrap;
        }


        /* =========================================================
           FORM CARD
           ========================================================= */

        .profile-form-card {

            border:
                1px solid
                rgba(148, 163, 184, .20);

            border-radius:
                23px;

            background:
                rgba(255, 255, 255, .60);

            box-shadow:
                0 12px 40px rgba(15, 23, 42, .035);
        }


        .profile-form-header {

            display: flex;

            align-items: center;

            gap: 13px;

            padding:
                20px
                22px;

            border-bottom:
                1px solid
                rgba(15, 23, 42, .065);
        }


        .profile-form-header-icon {

            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius:
                11px;

            background:
                rgba(59, 130, 246, .09);

            color:
                #2563eb;

            font-size:
                14px;
        }


        .profile-form-header h3 {

            margin: 0;

            color:
                #1e293b;

            font-size:
                .95rem;

            font-weight:
                750;
        }


        .profile-form-header p {

            margin:
                3px
                0
                0;

            color:
                #94a3b8;

            font-size:
                .7rem;
        }


        /* =========================================================
           FORM BODY
           ========================================================= */

        .profile-form-body {

            padding:
                25px;
        }


        .profile-form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap:
                21px;
        }


        .profile-field {

            min-width:
                0;
        }


        .profile-field.full {

            grid-column:
                1 / -1;
        }


        /* =========================================================
           LABEL
           ========================================================= */

        .profile-label {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-bottom:
                8px;

            color:
                #334155;

            font-size:
                .76rem;

            font-weight:
                750;
        }


        .profile-label i {

            width: 14px;

            color:
                var(--profile-primary);

            font-size:
                11px;

            text-align:
                center;
        }


        /* =========================================================
           INPUT WRAPPER
           ========================================================= */

        .profile-input-wrap {

            position: relative;
        }


        .profile-input-icon {

            position: absolute;

            left: 14px;

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
                color .2s ease;
        }


        .profile-control {

            width:
                100%;

            min-height:
                49px;

            padding:
                12px
                14px
                12px
                39px;

            border:
                1px solid
                rgba(148, 163, 184, .35);

            border-radius:
                13px;

            background:
                rgba(255, 255, 255, .72);

            color:
                #1e293b;

            outline:
                none;

            font-size:
                .83rem;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                transform .2s ease;
        }


        .profile-control::placeholder {

            color:
                #a1aaba;
        }


        .profile-control:hover {

            border-color:
                rgba(104, 57, 235, .27);
        }


        .profile-control:focus {

            border-color:
                rgba(104, 57, 235, .60);

            background:
                rgba(255, 255, 255, .95);

            box-shadow:

                0 0 0 4px
                rgba(104, 57, 235, .08),

                0 10px 25px
                rgba(15, 23, 42, .045);
        }


        .profile-input-wrap:focus-within
        .profile-input-icon {

            color:
                var(--profile-primary);
        }


        /* =========================================================
           FILE INPUT
           ========================================================= */

        .profile-file {

            padding:
                8px
                10px
                8px
                12px;

            cursor:
                pointer;
        }


        .profile-file::file-selector-button {

            margin-right:
                10px;

            padding:
                7px
                12px;

            border:
                0;

            border-radius:
                8px;

            background:
                rgba(104, 57, 235, .09);

            color:
                var(--profile-primary);

            font-size:
                .72rem;

            font-weight:
                700;

            cursor:
                pointer;

            transition:
                background .2s ease;
        }


        .profile-file::file-selector-button:hover {

            background:
                rgba(104, 57, 235, .15);
        }


        /* =========================================================
           HELPER TEXT
           ========================================================= */

        .profile-helper {

            display: flex;

            align-items: center;

            gap: 6px;

            margin-top:
                7px;

            color:
                #94a3b8;

            font-size:
                .67rem;
        }


        .profile-helper i {

            color:
                var(--profile-primary);
        }


        /* =========================================================
           ACTION AREA
           ========================================================= */

        .profile-actions {

            grid-column:
                1 / -1;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-top:
                4px;

            padding-top:
                23px;

            border-top:
                1px solid
                rgba(15, 23, 42, .07);
        }


        .profile-security-note {

            display: flex;

            align-items: center;

            gap: 9px;

            color:
                #94a3b8;

            font-size:
                .69rem;

            line-height:
                1.5;
        }


        .profile-security-note i {

            color:
                #10b981;

            font-size:
                12px;
        }


        .profile-buttons {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        /* =========================================================
           BUTTONS
           ========================================================= */

        .profile-btn {

            min-height:
                46px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding:
                11px
                19px;

            border-radius:
                12px;

            font-size:
                .76rem;

            font-weight:
                750;

            text-decoration:
                none;

            border:
                1px solid transparent;

            cursor:
                pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                border-color .2s ease;
        }


        .profile-btn:hover {

            transform:
                translateY(-1px);
        }


        .profile-btn-primary {

            background:

                linear-gradient(
                    135deg,
                    #6839eb,
                    #5529d0
                );

            color:
                #ffffff;

            box-shadow:
                0 10px 26px
                rgba(104, 57, 235, .20);
        }


        .profile-btn-primary:hover {

            box-shadow:
                0 14px 32px
                rgba(104, 57, 235, .28);
        }


        .profile-btn-secondary {

            background:
                rgba(255, 255, 255, .72);

            color:
                #475569;

            border-color:
                rgba(148, 163, 184, .30);
        }


        .profile-btn-secondary:hover {

            background:
                #ffffff;

            border-color:
                rgba(104, 57, 235, .22);

            color:
                #334155;
        }


        /* =========================================================
           SECURITY STRIP
           ========================================================= */

        .profile-security-strip {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );

            gap:
                12px;

            margin-top:
                20px;
        }


        .profile-security-item {

            display: flex;

            align-items: center;

            gap: 10px;

            padding:
                13px
                14px;

            border-radius:
                14px;

            background:
                rgba(248, 250, 252, .65);

            border:
                1px solid
                rgba(148, 163, 184, .17);
        }


        .profile-security-icon {

            width: 30px;
            height: 30px;

            flex: 0 0 30px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius:
                9px;

            background:
                rgba(16, 185, 129, .08);

            color:
                #059669;

            font-size:
                11px;
        }


        .profile-security-item strong {

            display: block;

            color:
                #334155;

            font-size:
                .68rem;

            font-weight:
                750;
        }


        .profile-security-item span {

            display: block;

            margin-top:
                2px;

            color:
                #94a3b8;

            font-size:
                .61rem;
        }


        /* =========================================================
           RESPONSIVE — TABLET
           ========================================================= */

        @media (max-width: 1024px) {

            .profile-page {

                padding-top:
                    12px;
            }


            .profile-shell {

                border-radius:
                    22px;
            }


            .profile-header,
            .profile-breadcrumb,
            .profile-content {

                padding-left:
                    22px;

                padding-right:
                    22px;
            }


            .profile-security-strip {

                grid-template-columns:
                    1fr;
            }
        }


        /* =========================================================
           RESPONSIVE — MOBILE
           ========================================================= */

        @media (max-width: 768px) {

            .profile-page {

                padding:
                    8px
                    0
                    28px;
            }


            .profile-shell {

                border-radius:
                    18px;
            }


            .profile-header {

                padding:
                    20px
                    18px;
            }


            .profile-header-left {

                gap:
                    11px;
            }


            .profile-header-icon {

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


            .profile-header-title {

                font-size:
                    1.25rem;
            }


            .profile-header-subtitle {

                font-size:
                    .72rem;
            }


            .profile-system-status {

                display:
                    none;
            }


            .profile-breadcrumb {

                padding:
                    11px
                    18px;
            }


            .profile-content {

                padding:
                    19px
                    18px
                    25px;
            }


            .profile-intro {

                align-items:
                    flex-start;

                padding:
                    16px;

                margin-bottom:
                    18px;
            }


            .profile-intro-badge {

                display:
                    none;
            }


            .profile-form-card {

                border-radius:
                    18px;
            }


            .profile-form-header {

                padding:
                    17px;
            }


            .profile-form-body {

                padding:
                    18px;
            }


            .profile-form-grid {

                grid-template-columns:
                    1fr;

                gap:
                    17px;
            }


            .profile-field.full {

                grid-column:
                    auto;
            }


            .profile-actions {

                grid-column:
                    auto;

                flex-direction:
                    column;

                align-items:
                    stretch;

                gap:
                    15px;
            }


            .profile-security-note {

                justify-content:
                    center;

                text-align:
                    center;
            }


            .profile-buttons {

                width:
                    100%;

                display:
                    grid;

                grid-template-columns:
                    1fr 1fr;
            }


            .profile-btn {

                width:
                    100%;
            }
        }


        /* =========================================================
           RESPONSIVE — SMALL PHONES
           ========================================================= */

        @media (max-width: 480px) {

            .profile-header {

                padding:
                    17px
                    14px;
            }


            .profile-breadcrumb {

                padding:
                    10px
                    14px;
            }


            .profile-content {

                padding:
                    16px
                    14px
                    21px;
            }


            .profile-header-title {

                font-size:
                    1.13rem;
            }


            .profile-header-subtitle {

                font-size:
                    .67rem;
            }


            .profile-intro {

                padding:
                    14px;
            }


            .profile-intro-title {

                font-size:
                    .79rem;
            }


            .profile-intro-description {

                font-size:
                    .68rem;
            }


            .profile-form-body {

                padding:
                    15px;
            }


            .profile-control {

                min-height:
                    46px;

                border-radius:
                    11px;

                font-size:
                    .79rem;
            }


            .profile-buttons {

                grid-template-columns:
                    1fr;
            }


            .profile-security-item {

                padding:
                    12px;
            }
        }


        /* =========================================================
           DARK MODE — ALPINE STATE PRESERVED
           ========================================================= */

        .dark .profile-shell {

            background:
                rgba(15, 23, 42, .80);

            border-color:
                rgba(255, 255, 255, .06);
        }


        .dark .profile-header {

            border-color:
                rgba(255, 255, 255, .06);
        }


        .dark .profile-header-title {

            color:
                #f8fafc;
        }


        .dark .profile-header-subtitle {

            color:
                #94a3b8;
        }


        .dark .profile-breadcrumb {

            background:
                rgba(15, 23, 42, .45);

            border-color:
                rgba(255, 255, 255, .05);
        }


        .dark .profile-breadcrumb-current {

            color:
                #e2e8f0;
        }


        .dark .profile-intro {

            background:

                linear-gradient(
                    135deg,
                    rgba(104, 57, 235, .12),
                    rgba(15, 23, 42, .45)
                );

            border-color:
                rgba(104, 57, 235, .15);
        }


        .dark .profile-intro-title {

            color:
                #f1f5f9;
        }


        .dark .profile-intro-description {

            color:
                #94a3b8;
        }


        .dark .profile-intro-badge {

            background:
                rgba(15, 23, 42, .55);

            border-color:
                rgba(71, 85, 105, .50);

            color:
                #cbd5e1;
        }


        .dark .profile-form-card {

            background:
                rgba(15, 23, 42, .42);

            border-color:
                rgba(71, 85, 105, .45);
        }


        .dark .profile-form-header {

            border-color:
                rgba(255, 255, 255, .06);
        }


        .dark .profile-form-header h3 {

            color:
                #f1f5f9;
        }


        .dark .profile-label {

            color:
                #cbd5e1;
        }


        .dark .profile-control {

            background:
                rgba(15, 23, 42, .72);

            border-color:
                rgba(71, 85, 105, .60);

            color:
                #f8fafc;
        }


        .dark .profile-control:focus {

            background:
                rgba(15, 23, 42, .95);
        }


        .dark .profile-control::placeholder {

            color:
                #64748b;
        }


        .dark .profile-actions {

            border-color:
                rgba(255, 255, 255, .06);
        }


        .dark .profile-btn-secondary {

            background:
                rgba(15, 23, 42, .70);

            color:
                #cbd5e1;

            border-color:
                rgba(71, 85, 105, .55);
        }


        .dark .profile-btn-secondary:hover {

            background:
                rgba(30, 41, 59, .85);

            color:
                #f8fafc;
        }


        .dark .profile-security-item {

            background:
                rgba(15, 23, 42, .48);

            border-color:
                rgba(71, 85, 105, .40);
        }


        .dark .profile-security-item strong {

            color:
                #cbd5e1;
        }

    </style>


    <!-- =========================================================
         EXISTING ZARAZ SCRIPT
         ========================================================= -->

    <script
        data-cfasync="false"
        nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86"
    >

        try {

            (function(w,d){

                !function(fv,fw,fx,fy){

                    if(fv.zaraz)

                        console.error(
                            "zaraz is loaded twice"
                        );

                    else {

                        fv[fx] =
                            fv[fx] ||
                            {};

                        fv[fx].executed = [];

                        fv.zaraz = {

                            deferred: [],
                            listeners: []

                        };

                        fv.zaraz._v = "5858";

                        fv.zaraz._n =
                            "ff1f9a24-18a4-4614-8917-7c27c5c96a86";

                        fv.zaraz.q = [];

                        fv.zaraz._f =
                            function(fz){

                                return async function(){

                                    var fA =
                                        Array.prototype.slice.call(
                                            arguments
                                        );

                                    fv.zaraz.q.push({

                                        m: fz,
                                        a: fA

                                    });

                                }

                            };

                        for(
                            const fB
                            of ["track","set","debug"]
                        )

                            fv.zaraz[fB] =
                                fv.zaraz._f(fB);


                        fv.zaraz.init = () => {

                            var fC =
                                fw.getElementsByTagName(fy)[0],

                                fD =
                                fw.createElement(fy),

                                fE =
                                fw.getElementsByTagName(
                                    "title"
                                )[0];


                            fE &&
                            (
                                fv[fx].t =
                                fw.getElementsByTagName(
                                    "title"
                                )[0].text
                            );


                            fv[fx].x =
                                Math.random();

                            fv[fx].w =
                                fv.screen.width;

                            fv[fx].h =
                                fv.screen.height;

                            fv[fx].j =
                                fv.innerHeight;

                            fv[fx].e =
                                fv.innerWidth;

                            fv[fx].l =
                                fv.location.href;

                            fv[fx].r =
                                fw.referrer;

                            fv[fx].k =
                                fv.screen.colorDepth;

                            fv[fx].n =
                                fw.characterSet;

                            fv[fx].o =
                                (new Date)
                                .getTimezoneOffset();


                            if(fv.dataLayer)

                                for(
                                    const fF
                                    of Object.entries(
                                        Object.entries(
                                            dataLayer
                                        ).reduce(
                                            ((fG,fH) => ({
                                                ...fG[1],
                                                ...fH[1]
                                            })),
                                            {}
                                        )
                                    )
                                )

                                    zaraz.set(
                                        fF[0],
                                        fF[1],
                                        {scope:"page"}
                                    );


                            fv[fx].q = [];


                            for(
                                ;
                                fv.zaraz.q.length;
                            ){

                                const fI =
                                    fv.zaraz.q.shift();

                                fv[fx].q.push(fI);

                            }


                            fD.defer = !0;


                            for(
                                const fJ
                                of [
                                    localStorage,
                                    sessionStorage
                                ]
                            )

                                Object.keys(fJ || {})
                                    .filter(
                                        fL =>
                                        fL.startsWith(
                                            "_zaraz_"
                                        )
                                    )
                                    .forEach(
                                        fK => {

                                            try {

                                                fv[fx][
                                                    "z_" +
                                                    fK.slice(7)
                                                ] =
                                                JSON.parse(
                                                    fJ.getItem(fK)
                                                );

                                            }

                                            catch {

                                                fv[fx][
                                                    "z_" +
                                                    fK.slice(7)
                                                ] =
                                                fJ.getItem(fK);

                                            }

                                        }
                                    );


                            fD.referrerPolicy =
                                "origin";


                            fD.src =
                                "cdn-cgi/zaraz/sd0d9.js?z=" +
                                btoa(
                                    encodeURIComponent(
                                        JSON.stringify(
                                            fv[fx]
                                        )
                                    )
                                );


                            fC.parentNode.insertBefore(
                                fD,
                                fC
                            );

                        };


                        ["complete","interactive"]
                            .includes(
                                fw.readyState
                            )

                            ? zaraz.init()

                            : fv.addEventListener(
                                "DOMContentLoaded",
                                zaraz.init
                            );

                    }

                }(
                    w,
                    d,
                    "zarazData",
                    "script"
                );


                window.zaraz._p =
                    async eC =>
                    new Promise(
                        eD => {

                            if(eC){

                                eC.e &&
                                eC.e.forEach(
                                    eE => {

                                        try {

                                            const eF =
                                                d.querySelector(
                                                    "script[nonce]"
                                                );

                                            const eG =
                                                eF?.nonce ||
                                                eF?.getAttribute(
                                                    "nonce"
                                                );

                                            const eH =
                                                d.createElement(
                                                    "script"
                                                );

                                            eG &&
                                            (
                                                eH.nonce =
                                                eG
                                            );

                                            eH.innerHTML =
                                                eE;

                                            eH.onload =
                                                () => {

                                                    d.head.removeChild(
                                                        eH
                                                    );

                                                };

                                            d.head.appendChild(
                                                eH
                                            );

                                        }

                                        catch(eI){

                                            console.error(
                                                `Error executing script: ${eE}\n`,
                                                eI
                                            );

                                        }

                                    }
                                );


                                Promise.allSettled(
                                    (eC.f || [])
                                        .map(
                                            eJ =>
                                            fetch(
                                                eJ[0],
                                                eJ[1]
                                            )
                                        )
                                );

                            }

                            eD();

                        }
                    );


                zaraz._p({

                    "e":[
                        "(function(w,d){})(window,document)"
                    ]

                });

            })(window,document);

        }

        catch(e){

            throw fetch(
                "/cdn-cgi/zaraz/t"
            ),e;

        }

    </script>

</head>


<body

    x-data="{
        page: 'ecommerce',
        'loaded': true,
        'darkMode': false,
        'stickyMenu': false,
        'sidebarToggle': false,
        'scrollTop': false
    }"

    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode'));
        $watch('darkMode', value =>
            localStorage.setItem(
                'darkMode',
                JSON.stringify(value)
            )
        )
    "

    :class="{
        'dark bg-gray-900':
        darkMode === true
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

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
    >


        <!-- =================================================
             MAIN
             ================================================= -->

        <main>


            <?php include('header.php'); ?>


            <div
                class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6"
            >


                <div class="profile-page">


                    <section class="profile-shell">


                        <!-- =====================================
                             HEADER
                             ===================================== -->

                        <header class="profile-header">


                            <div class="profile-header-left">


                                <div class="profile-header-icon">

                                    <i class="fas fa-user-gear"></i>

                                </div>


                                <div>

                                    <h1 class="profile-header-title">

                                        Edit Profile

                                    </h1>


                                    <p class="profile-header-subtitle">

                                        Manage your identity,
                                        contact information and
                                        account profile.

                                    </p>

                                </div>


                            </div>


                            <div class="profile-system-status">

                                <span
                                    class="profile-system-dot"
                                ></span>

                                Identity System Active

                            </div>


                        </header>



                        <!-- =====================================
                             BREADCRUMB
                             ===================================== -->

                        <div class="profile-breadcrumb">

                            <nav aria-label="Breadcrumb">

                                <ol
                                    class="profile-breadcrumb-list"
                                >

                                    <li>

                                        <a href="index.php">

                                            <i
                                                class="fas fa-house"
                                            ></i>

                                            Home

                                        </a>

                                    </li>


                                    <li>

                                        <i
                                            class="fas fa-chevron-right breadcrumb-arrow"
                                        ></i>

                                    </li>


                                    <li
                                        class="profile-breadcrumb-current"
                                    >

                                        Edit Profile

                                    </li>

                                </ol>

                            </nav>

                        </div>



                        <!-- =====================================
                             CONTENT
                             ===================================== -->

                        <div class="profile-content">


                            <!-- =================================
                                 INTRO
                                 ================================= -->

                            <div class="profile-intro">


                                <div class="profile-intro-left">


                                    <div
                                        class="profile-intro-icon"
                                    >

                                        <i
                                            class="fas fa-fingerprint"
                                        ></i>

                                    </div>


                                    <div>

                                        <strong
                                            class="profile-intro-title"
                                        >

                                            Personal Identity Core

                                        </strong>


                                        <span
                                            class="profile-intro-description"
                                        >

                                            Update your profile
                                            information carefully.
                                            These details are used
                                            across your company
                                            workspace.

                                        </span>

                                    </div>


                                </div>


                                <div
                                    class="profile-intro-badge"
                                >

                                    <i
                                        class="fas fa-shield-halved"
                                    ></i>

                                    Protected Profile

                                </div>


                            </div>



                            <!-- =================================
                                 FORM CARD
                                 ================================= -->

                            <div class="profile-form-card">


                                <!-- FORM HEADER -->

                                <div class="profile-form-header">


                                    <div
                                        class="profile-form-header-icon"
                                    >

                                        <i
                                            class="fas fa-sliders"
                                        ></i>

                                    </div>


                                    <div>

                                        <h3>

                                            Profile Configuration

                                        </h3>


                                        <p>

                                            Modify the information
                                            associated with your
                                            staff identity.

                                        </p>

                                    </div>


                                </div>



                                <!-- FORM BODY -->

                                <div class="profile-form-body">


                                    <form
                                        method="POST"
                                        action="proceed-admin-user.php"
                                        enctype="multipart/form-data"
                                    >


                                        <div
                                            class="profile-form-grid"
                                        >


                                            <!-- =====================
                                                 PROFILE IMAGE
                                                 ===================== -->

                                            <div
                                                class="profile-field"
                                            >

                                                <label
                                                    class="profile-label"
                                                >

                                                    <i
                                                        class="fas fa-camera"
                                                    ></i>

                                                    Change Profile Picture

                                                </label>


                                                <div
                                                    class="profile-input-wrap"
                                                >

                                                    <i
                                                        class="fas fa-image profile-input-icon"
                                                    ></i>


                                                    <input
                                                        type="file"
                                                        name="profile"
                                                        accept="image/*"
                                                        class="profile-control profile-file"
                                                    >

                                                </div>


                                                <div
                                                    class="profile-helper"
                                                >

                                                    <i
                                                        class="fas fa-circle-info"
                                                    ></i>

                                                    Use a clear
                                                    professional image.

                                                </div>

                                            </div>



                                            <!-- =====================
                                                 FIRST NAME
                                                 ===================== -->

                                            <div
                                                class="profile-field"
                                            >

                                                <label
                                                    class="profile-label"
                                                >

                                                    <i
                                                        class="fas fa-id-card"
                                                    ></i>

                                                    First Name

                                                </label>


                                                <div
                                                    class="profile-input-wrap"
                                                >

                                                    <i
                                                        class="fas fa-user profile-input-icon"
                                                    ></i>


                                                    <input
                                                        type="text"
                                                        name="firstname"
                                                        placeholder="Enter first name"
                                                        autocomplete="given-name"
                                                        class="profile-control"
                                                    >

                                                </div>


                                                <input
                                                    type="hidden"
                                                    name="company_id"
                                                    value="<?= htmlspecialchars($company_id) ?>"
                                                >

                                            </div>



                                            <!-- =====================
                                                 LAST NAME
                                                 ===================== -->

                                            <div
                                                class="profile-field"
                                            >

                                                <label
                                                    class="profile-label"
                                                >

                                                    <i
                                                        class="fas fa-user-tag"
                                                    ></i>

                                                    Last Name

                                                </label>


                                                <div
                                                    class="profile-input-wrap"
                                                >

                                                    <i
                                                        class="fas fa-signature profile-input-icon"
                                                    ></i>


                                                    <input
                                                        type="text"
                                                        name="lastname"
                                                        placeholder="Enter last name"
                                                        autocomplete="family-name"
                                                        class="profile-control"
                                                    >

                                                </div>

                                            </div>



                                            <!-- =====================
                                                 EMAIL
                                                 ===================== -->

                                            <div
                                                class="profile-field"
                                            >

                                                <label
                                                    class="profile-label"
                                                >

                                                    <i
                                                        class="fas fa-envelope"
                                                    ></i>

                                                    Email Address

                                                </label>


                                                <div
                                                    class="profile-input-wrap"
                                                >

                                                    <i
                                                        class="fas fa-at profile-input-icon"
                                                    ></i>


                                                    <input
                                                        type="email"
                                                        name="email"
                                                        placeholder="name@example.com"
                                                        autocomplete="email"
                                                        class="profile-control"
                                                    >

                                                </div>

                                            </div>



                                            <!-- =====================
                                                 PHONE
                                                 ===================== -->

                                            <div
                                                class="profile-field"
                                            >

                                                <label
                                                    class="profile-label"
                                                >

                                                    <i
                                                        class="fas fa-mobile-screen-button"
                                                    ></i>

                                                    Mobile Number

                                                </label>


                                                <div
                                                    class="profile-input-wrap"
                                                >

                                                    <i
                                                        class="fas fa-phone profile-input-icon"
                                                    ></i>


                                                    <input
                                                        type="tel"
                                                        name="phone"
                                                        placeholder="Enter mobile number"
                                                        autocomplete="tel"
                                                        class="profile-control"
                                                    >

                                                </div>

                                            </div>



                                            <!-- =====================
                                                 ADDRESS
                                                 ===================== -->

                                            <div
                                                class="profile-field full"
                                            >

                                                <label
                                                    class="profile-label"
                                                >

                                                    <i
                                                        class="fas fa-location-dot"
                                                    ></i>

                                                    Residential Address

                                                </label>


                                                <div
                                                    class="profile-input-wrap"
                                                >

                                                    <i
                                                        class="fas fa-map-location-dot profile-input-icon"
                                                    ></i>


                                                    <input
                                                        type="text"
                                                        name="address"
                                                        placeholder="Enter residential address"
                                                        autocomplete="street-address"
                                                        class="profile-control"
                                                    >

                                                </div>

                                            </div>



                                            <!-- =====================
                                                 ACTIONS
                                                 ===================== -->

                                            <div
                                                class="profile-actions"
                                            >


                                                <div
                                                    class="profile-security-note"
                                                >

                                                    <i
                                                        class="fas fa-circle-check"
                                                    ></i>

                                                    <span>

                                                        Your profile
                                                        information is
                                                        securely linked
                                                        to your company
                                                        account.

                                                    </span>

                                                </div>


                                                <div
                                                    class="profile-buttons"
                                                >


                                                    <a
                                                        href="index.php"
                                                        class="profile-btn profile-btn-secondary"
                                                    >

                                                        <i
                                                            class="fas fa-arrow-left"
                                                        ></i>

                                                        Cancel

                                                    </a>


                                                    <button
                                                        type="submit"
                                                        name="submit"
                                                        class="profile-btn profile-btn-primary"
                                                    >

                                                        <i
                                                            class="fas fa-floppy-disk"
                                                        ></i>

                                                        Save Changes

                                                    </button>


                                                </div>


                                            </div>


                                        </div>


                                    </form>


                                </div>


                            </div>



                            <!-- =================================
                                 SECURITY / SYSTEM STRIP
                                 ================================= -->

                            <div
                                class="profile-security-strip"
                            >


                                <div
                                    class="profile-security-item"
                                >

                                    <div
                                        class="profile-security-icon"
                                    >

                                        <i
                                            class="fas fa-lock"
                                        ></i>

                                    </div>


                                    <div>

                                        <strong>
                                            Secure Identity
                                        </strong>

                                        <span>
                                            Profile data protected
                                        </span>

                                    </div>

                                </div>



                                <div
                                    class="profile-security-item"
                                >

                                    <div
                                        class="profile-security-icon"
                                    >

                                        <i
                                            class="fas fa-building"
                                        ></i>

                                    </div>


                                    <div>

                                        <strong>
                                            Company Linked
                                        </strong>

                                        <span>
                                            Connected to workspace
                                        </span>

                                    </div>

                                </div>



                                <div
                                    class="profile-security-item"
                                >

                                    <div
                                        class="profile-security-icon"
                                    >

                                        <i
                                            class="fas fa-cloud-arrow-up"
                                        ></i>

                                    </div>


                                    <div>

                                        <strong>
                                            Profile Sync
                                        </strong>

                                        <span>
                                            Changes sync on save
                                        </span>

                                    </div>

                                </div>


                            </div>


                        </div>


                    </section>


                </div>


            </div>


        </main>


        <!-- ================================================
             MAIN CONTENT END
             ================================================ -->


    </div>


    <!-- =====================================================
         CONTENT AREA END
         ===================================================== -->


</div>


<!-- =========================================================
     FOOTER
     ========================================================= -->

<?php include('footer.php'); ?>



<!-- =========================================================
     EXISTING SCRIPTS
     ========================================================= -->

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
    data-cf-beacon='{
        "rayId":"95aebaa31b75b6e3",
        "version":"2025.6.2",
        "r":1,
        "serverTiming":{
            "name":{
                "cfExtPri":true,
                "cfEdge":true,
                "cfOrigin":true,
                "cfL4":true,
                "cfSpeedBrain":true,
                "cfCacheStatus":true
            }
        },
        "token":"67f7a278e3374824ae6dd92295d38f77",
        "b":1
    }'
    crossorigin="anonymous"
></script>


</body>

</html>