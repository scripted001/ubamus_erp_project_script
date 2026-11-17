<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$companyid = $_SESSION['company_id'] ?? 0;

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
        <?= htmlspecialchars($company_name ?? 'Company'); ?> | Production Intelligence
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
           PRODUCTION INTELLIGENCE GATEWAY
           LIGHT FUTURISTIC SYSTEM
        ========================================================= */

        :root {

            --pi-bg: #f4f8fc;

            --pi-surface: rgba(255,255,255,.82);
            --pi-surface-solid: #ffffff;

            --pi-border: rgba(15,23,42,.09);

            --pi-text: #0f172a;
            --pi-heading: #07111f;
            --pi-muted: #64748b;
            --pi-soft: #94a3b8;

            --pi-cyan: #0891b2;
            --pi-blue: #2563eb;
            --pi-indigo: #4f46e5;
            --pi-purple: #7c3aed;
            --pi-green: #059669;

            --pi-shadow:
                0 25px 70px rgba(15,23,42,.08);

            --pi-shadow-soft:
                0 12px 40px rgba(15,23,42,.06);

            --pi-radius: 24px;

        }


        /* =========================================================
           BASE
        ========================================================= */

        html,
        body {

            margin: 0;
            padding: 0;

            min-height: 100%;

            background: var(--pi-bg);

        }


        body {

            color: var(--pi-text);

        }


        * {

            box-sizing: border-box;

        }


        button,
        input,
        select {

            font: inherit;

        }


        /* =========================================================
           INTELLIGENCE PAGE
        ========================================================= */

        .production-gateway {

            position: relative;

            min-height: calc(100vh - 1px);

            overflow: hidden;

            background:

                radial-gradient(
                    circle at 5% 5%,
                    rgba(8,145,178,.11),
                    transparent 27%
                ),

                radial-gradient(
                    circle at 95% 12%,
                    rgba(37,99,235,.10),
                    transparent 28%
                ),

                radial-gradient(
                    circle at 55% 100%,
                    rgba(124,58,237,.07),
                    transparent 35%
                ),

                linear-gradient(
                    135deg,
                    #f8fbff 0%,
                    #f2f7fc 48%,
                    #eef4fa 100%
                );

        }


        /* =========================================================
           TECH GRID
        ========================================================= */

        .production-gateway::before {

            content: "";

            position: absolute;

            inset: 0;

            pointer-events: none;

            opacity: .45;

            background-image:

                linear-gradient(
                    rgba(15,23,42,.035) 1px,
                    transparent 1px
                ),

                linear-gradient(
                    90deg,
                    rgba(15,23,42,.035) 1px,
                    transparent 1px
                );

            background-size: 42px 42px;

            mask-image:
                linear-gradient(
                    to bottom,
                    black,
                    transparent 88%
                );

        }


        /* =========================================================
           AMBIENT ORBS
        ========================================================= */

        .pi-orb {

            position: absolute;

            border-radius: 50%;

            pointer-events: none;

            filter: blur(2px);

        }


        .pi-orb-one {

            width: 330px;
            height: 330px;

            top: -180px;
            right: 8%;

            background:
                radial-gradient(
                    circle,
                    rgba(34,211,238,.15),
                    transparent 68%
                );

        }


        .pi-orb-two {

            width: 260px;
            height: 260px;

            bottom: -130px;
            left: 12%;

            background:
                radial-gradient(
                    circle,
                    rgba(99,102,241,.11),
                    transparent 68%
                );

        }


        /* =========================================================
           MAIN CONTAINER
        ========================================================= */

        .pi-gateway-container {

            position: relative;

            z-index: 2;

            width: min(
                1500px,
                calc(100% - 32px)
            );

            margin: 0 auto;

            padding:
                clamp(20px, 3vw, 42px)
                0
                60px;

        }


        /* =========================================================
           TOP INTELLIGENCE BAR
        ========================================================= */

        .pi-system-bar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;

            padding:
                10px 14px;

            border:
                1px solid rgba(15,23,42,.075);

            border-radius: 14px;

            background:
                rgba(255,255,255,.66);

            backdrop-filter:
                blur(18px);

            -webkit-backdrop-filter:
                blur(18px);

            box-shadow:
                0 8px 30px rgba(15,23,42,.035);

        }


        .pi-system-status {

            display: flex;

            align-items: center;

            gap: 9px;

            color: #475569;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .12em;

            text-transform: uppercase;

        }


        .pi-system-dot {

            width: 8px;
            height: 8px;

            border-radius: 50%;

            background:
                #10b981;

            box-shadow:
                0 0 0 5px rgba(16,185,129,.10),
                0 0 14px rgba(16,185,129,.45);

            animation:
                gatewayPulse 2s infinite;

        }


        @keyframes gatewayPulse {

            0%,
            100% {

                opacity: 1;

                transform: scale(1);

            }

            50% {

                opacity: .55;

                transform: scale(.82);

            }

        }


        .pi-system-code {

            color: #94a3b8;

            font-family:
                "SFMono-Regular",
                Consolas,
                monospace;

            font-size: 9px;

            letter-spacing: .08em;

        }


        /* =========================================================
           HERO
        ========================================================= */

        .pi-gateway-hero {

            position: relative;

            display: grid;

            grid-template-columns:
                minmax(0, 1.25fr)
                minmax(300px, .75fr);

            gap: 24px;

            min-height: 400px;

            padding:
                clamp(25px, 4vw, 48px);

            border:
                1px solid rgba(15,23,42,.085);

            border-radius: 30px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.94),
                    rgba(248,251,255,.78)
                );

            box-shadow:
                var(--pi-shadow);

            backdrop-filter:
                blur(30px);

            -webkit-backdrop-filter:
                blur(30px);

            overflow: hidden;

        }


        .pi-gateway-hero::before {

            content: "";

            position: absolute;

            width: 500px;
            height: 500px;

            right: -240px;
            top: -270px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(34,211,238,.12),
                    transparent 65%
                );

            pointer-events: none;

        }


        .pi-gateway-hero::after {

            content: "";

            position: absolute;

            left: 0;
            right: 0;
            bottom: 0;

            height: 1px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(8,145,178,.25),
                    rgba(79,70,229,.18),
                    transparent
                );

        }


        /* =========================================================
           HERO LEFT
        ========================================================= */

        .pi-hero-content {

            position: relative;

            z-index: 2;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .pi-intelligence-label {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            width: fit-content;

            margin-bottom: 16px;

            padding:
                7px 11px;

            border:
                1px solid rgba(8,145,178,.14);

            border-radius: 999px;

            color: var(--pi-cyan);

            background:
                rgba(8,145,178,.055);

            font-size: 10px;

            font-weight: 900;

            letter-spacing: .14em;

            text-transform: uppercase;

        }


        .pi-intelligence-label i {

            font-size: 9px;

        }


        .pi-main-title {

            max-width: 720px;

            margin: 0;

            color: var(--pi-heading);

            font-size:
                clamp(34px, 5vw, 64px);

            line-height: .98;

            font-weight: 900;

            letter-spacing: -.055em;

        }


        .pi-main-title span {

            display: block;

            margin-top: 7px;

            background:
                linear-gradient(
                    100deg,
                    #0891b2,
                    #2563eb 50%,
                    #6366f1
                );

            -webkit-background-clip: text;

            background-clip: text;

            color: transparent;

        }


        .pi-hero-description {

            max-width: 650px;

            margin:
                22px 0 0;

            color: #64748b;

            font-size:
                clamp(13px, 1.4vw, 15px);

            line-height: 1.75;

        }


        /* =========================================================
           SIGNALS
        ========================================================= */

        .pi-signals {

            display: flex;

            flex-wrap: wrap;

            gap: 9px;

            margin-top: 25px;

        }


        .pi-signal {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding:
                8px 11px;

            border:
                1px solid rgba(15,23,42,.075);

            border-radius: 10px;

            background:
                rgba(255,255,255,.72);

            color: #475569;

            font-size: 10px;

            font-weight: 700;

        }


        .pi-signal i {

            color: var(--pi-blue);

        }


        /* =========================================================
           HERO VISUAL / NODE
        ========================================================= */

        .pi-command-visual {

            position: relative;

            min-height: 310px;

            display: grid;

            place-items: center;

        }


        .pi-core-ring {

            position: relative;

            width:
                clamp(190px, 23vw, 280px);

            aspect-ratio: 1;

            display: grid;

            place-items: center;

            border:
                1px solid rgba(37,99,235,.15);

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(255,255,255,.96) 0%,
                    rgba(239,248,255,.82) 42%,
                    rgba(219,239,250,.45) 62%,
                    transparent 70%
                );

            box-shadow:
                0 25px 80px rgba(37,99,235,.10);

        }


        .pi-core-ring::before {

            content: "";

            position: absolute;

            inset: 17px;

            border:
                1px dashed rgba(8,145,178,.28);

            border-radius: 50%;

            animation:
                coreRotate 22s linear infinite;

        }


        .pi-core-ring::after {

            content: "";

            position: absolute;

            inset: 43px;

            border:
                1px solid rgba(99,102,241,.18);

            border-radius: 50%;

            animation:
                coreRotateReverse 15s linear infinite;

        }


        @keyframes coreRotate {

            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }

        }


        @keyframes coreRotateReverse {

            from {
                transform: rotate(360deg);
            }

            to {
                transform: rotate(0deg);
            }

        }


        .pi-core {

            position: relative;

            z-index: 3;

            width: 92px;
            height: 92px;

            display: grid;

            place-items: center;

            border:
                1px solid rgba(8,145,178,.24);

            border-radius: 27px;

            color: #0e7490;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.98),
                    rgba(225,247,252,.90)
                );

            box-shadow:
                0 15px 45px rgba(8,145,178,.12),
                inset 0 1px rgba(255,255,255,1);

        }


        .pi-core i {

            font-size: 34px;

        }


        .pi-core-label {

            position: absolute;

            bottom: 18px;

            left: 50%;

            transform: translateX(-50%);

            z-index: 5;

            white-space: nowrap;

            color: #475569;

            font-size: 8px;

            font-weight: 900;

            letter-spacing: .18em;

            text-transform: uppercase;

        }


        /* =========================================================
           FLOATING DATA NODES
        ========================================================= */

        .pi-node {

            position: absolute;

            z-index: 6;

            display: flex;

            align-items: center;

            gap: 7px;

            padding:
                7px 9px;

            border:
                1px solid rgba(15,23,42,.08);

            border-radius: 10px;

            background:
                rgba(255,255,255,.88);

            box-shadow:
                0 12px 30px rgba(15,23,42,.08);

            color: #475569;

            font-size: 9px;

            font-weight: 800;

            backdrop-filter:
                blur(12px);

        }


        .pi-node i {

            color: var(--pi-cyan);

        }


        .pi-node-one {

            top: 14%;

            right: 4%;

        }


        .pi-node-two {

            bottom: 13%;

            left: 1%;

        }


        .pi-node-three {

            top: 48%;

            right: -2%;

        }


        /* =========================================================
           SELECTION AREA
        ========================================================= */

        .pi-selection-panel {

            position: relative;

            margin-top: 20px;

            padding:
                clamp(20px, 3vw, 30px);

            border:
                1px solid rgba(15,23,42,.085);

            border-radius: 24px;

            background:
                rgba(255,255,255,.82);

            box-shadow:
                var(--pi-shadow-soft);

            backdrop-filter:
                blur(25px);

            -webkit-backdrop-filter:
                blur(25px);

        }


        .pi-selection-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;

        }


        .pi-selection-title {

            margin: 0;

            color: var(--pi-heading);

            font-size: 17px;

            font-weight: 850;

            letter-spacing: -.02em;

        }


        .pi-selection-description {

            margin:
                5px 0 0;

            color: var(--pi-muted);

            font-size: 11px;

            line-height: 1.6;

        }


        .pi-step {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #94a3b8;

            font-size: 9px;

            font-weight: 800;

            letter-spacing: .10em;

            text-transform: uppercase;

        }


        .pi-step-number {

            width: 26px;
            height: 26px;

            display: grid;

            place-items: center;

            border-radius: 9px;

            color: #2563eb;

            background:
                rgba(37,99,235,.075);

            border:
                1px solid rgba(37,99,235,.12);

        }


        /* =========================================================
           STAGE SELECTOR
        ========================================================= */

        .pi-stage-selector {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 12px;

        }


        .pi-stage-option {

            position: relative;

            cursor: pointer;

        }


        .pi-stage-option input {

            position: absolute;

            opacity: 0;

            pointer-events: none;

        }


        .pi-stage-card {

            position: relative;

            display: flex;

            align-items: center;

            gap: 14px;

            min-height: 100px;

            padding:
                16px;

            border:
                1px solid rgba(15,23,42,.09);

            border-radius: 17px;

            background:
                rgba(248,250,252,.72);

            transition:
                .25s ease;

        }


        .pi-stage-card:hover {

            transform: translateY(-2px);

            border-color:
                rgba(37,99,235,.20);

            background:
                #ffffff;

            box-shadow:
                0 14px 35px rgba(37,99,235,.07);

        }


        .pi-stage-option input:checked
        + .pi-stage-card {

            border-color:
                rgba(8,145,178,.38);

            background:
                linear-gradient(
                    135deg,
                    rgba(236,254,255,.96),
                    rgba(239,246,255,.96)
                );

            box-shadow:
                0 15px 40px rgba(8,145,178,.10),
                inset 0 0 0 1px rgba(8,145,178,.05);

        }


        .pi-stage-card::after {

            content: "";

            position: absolute;

            top: 11px;
            right: 11px;

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background:
                #cbd5e1;

        }


        .pi-stage-option input:checked
        + .pi-stage-card::after {

            background:
                #10b981;

            box-shadow:
                0 0 0 4px rgba(16,185,129,.10);

        }


        .pi-stage-icon {

            width: 48px;
            height: 48px;

            flex: 0 0 48px;

            display: grid;

            place-items: center;

            border:
                1px solid rgba(15,23,42,.075);

            border-radius: 14px;

            color: #64748b;

            background:
                #ffffff;

            box-shadow:
                0 6px 18px rgba(15,23,42,.045);

        }


        .pi-stage-option input:checked
        + .pi-stage-card
        .pi-stage-icon {

            color: #0891b2;

            border-color:
                rgba(8,145,178,.16);

            background:
                rgba(8,145,178,.07);

        }


        .pi-stage-content {

            min-width: 0;

        }


        .pi-stage-name {

            display: block;

            color: #1e293b;

            font-size: 13px;

            font-weight: 850;

        }


        .pi-stage-description {

            display: block;

            margin-top: 4px;

            color: #94a3b8;

            font-size: 9px;

            line-height: 1.45;

        }


        /* =========================================================
           ACTION AREA
        ========================================================= */

        .pi-action-area {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-top: 18px;

            padding-top: 18px;

            border-top:
                1px solid rgba(15,23,42,.065);

        }


        .pi-security-note {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #94a3b8;

            font-size: 9px;

        }


        .pi-security-note i {

            color: #10b981;

        }


        .pi-next-button {

            position: relative;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 10px;

            min-width: 190px;

            min-height: 48px;

            padding:
                12px 18px;

            border: 0;

            border-radius: 13px;

            color: #ffffff;

            background:
                linear-gradient(
                    110deg,
                    #0891b2,
                    #2563eb 55%,
                    #4f46e5
                );

            box-shadow:
                0 12px 28px rgba(37,99,235,.20);

            cursor: pointer;

            font-size: 11px;

            font-weight: 850;

            letter-spacing: .05em;

            transition:
                transform .2s ease,
                box-shadow .2s ease;

            overflow: hidden;

        }


        .pi-next-button::before {

            content: "";

            position: absolute;

            top: 0;
            bottom: 0;

            width: 80px;

            left: -100px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(255,255,255,.35),
                    transparent
                );

            transform: skewX(-20deg);

            transition:
                left .55s ease;

        }


        .pi-next-button:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 18px 38px rgba(37,99,235,.25);

        }


        .pi-next-button:hover::before {

            left: 130%;

        }


        .pi-next-button i {

            transition:
                transform .2s ease;

        }


        .pi-next-button:hover i {

            transform:
                translateX(4px);

        }


        /* =========================================================
           INTELLIGENCE FOOTER
        ========================================================= */

        .pi-intelligence-footer {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

            margin-top: 14px;

        }


        .pi-footer-metric {

            display: flex;

            align-items: center;

            gap: 11px;

            padding:
                13px 15px;

            border:
                1px solid rgba(15,23,42,.065);

            border-radius: 13px;

            background:
                rgba(255,255,255,.58);

        }


        .pi-footer-icon {

            width: 31px;
            height: 31px;

            display: grid;

            place-items: center;

            flex: 0 0 31px;

            border-radius: 9px;

            color: #2563eb;

            background:
                rgba(37,99,235,.065);

            font-size: 11px;

        }


        .pi-footer-label {

            display: block;

            color: #94a3b8;

            font-size: 8px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .08em;

        }


        .pi-footer-value {

            display: block;

            margin-top: 2px;

            color: #475569;

            font-size: 10px;

            font-weight: 800;

        }


        /* =========================================================
           TABLET
        ========================================================= */

        @media (max-width: 1050px) {

            .pi-gateway-hero {

                grid-template-columns:
                    1fr;

                min-height: auto;

            }


            .pi-command-visual {

                min-height: 270px;

            }


            .pi-core-ring {

                width: 230px;

            }


            .pi-node-three {

                right: 5%;

            }

        }


        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 720px) {

            .pi-gateway-container {

                width:
                    calc(100% - 20px);

                padding:
                    12px 0 35px;

            }


            .pi-system-bar {

                padding:
                    9px 11px;

                margin-bottom: 10px;

            }


            .pi-system-code {

                display: none;

            }


            .pi-gateway-hero {

                padding:
                    24px 18px 12px;

                border-radius: 22px;

                gap: 5px;

            }


            .pi-main-title {

                font-size:
                    clamp(34px, 11vw, 48px);

            }


            .pi-hero-description {

                margin-top: 16px;

                font-size: 12px;

                line-height: 1.65;

            }


            .pi-signals {

                margin-top: 18px;

            }


            .pi-signal {

                padding:
                    7px 9px;

                font-size: 8px;

            }


            .pi-command-visual {

                min-height: 250px;

                margin-top: 2px;

            }


            .pi-core-ring {

                width: 210px;

            }


            .pi-core {

                width: 78px;
                height: 78px;

                border-radius: 22px;

            }


            .pi-core i {

                font-size: 28px;

            }


            .pi-node {

                font-size: 8px;

                padding:
                    6px 8px;

            }


            .pi-node-one {

                top: 7%;

                right: 2%;

            }


            .pi-node-two {

                bottom: 7%;

                left: 0;

            }


            .pi-node-three {

                top: 46%;

                right: -3%;

            }


            .pi-selection-panel {

                margin-top: 10px;

                padding:
                    18px 14px;

                border-radius: 20px;

            }


            .pi-selection-header {

                margin-bottom: 14px;

            }


            .pi-selection-title {

                font-size: 15px;

            }


            .pi-selection-description {

                font-size: 10px;

            }


            .pi-step {

                display: none;

            }


            .pi-stage-selector {

                grid-template-columns:
                    1fr;

                gap: 8px;

            }


            .pi-stage-card {

                min-height: 78px;

                padding:
                    12px;

            }


            .pi-stage-icon {

                width: 42px;
                height: 42px;

                flex-basis: 42px;

                border-radius: 12px;

            }


            .pi-stage-name {

                font-size: 12px;

            }


            .pi-stage-description {

                font-size: 8px;

            }


            .pi-action-area {

                align-items: stretch;

                flex-direction: column;

                gap: 12px;

            }


            .pi-security-note {

                justify-content: center;

            }


            .pi-next-button {

                width: 100%;

            }


            .pi-intelligence-footer {

                grid-template-columns:
                    1fr;

                gap: 7px;

            }

        }


        /* =========================================================
           SMALL MOBILE
        ========================================================= */

        @media (max-width: 390px) {

            .pi-main-title {

                font-size: 32px;

            }


            .pi-intelligence-label {

                font-size: 8px;

            }


            .pi-command-visual {

                min-height: 225px;

            }


            .pi-core-ring {

                width: 185px;

            }


            .pi-node-three {

                right: -8px;

            }

        }


        /* =========================================================
           REDUCED MOTION
        ========================================================= */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {

                animation-duration: .01ms !important;

                animation-iteration-count: 1 !important;

                transition-duration: .01ms !important;

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
        darkMode = false;
        localStorage.setItem('darkMode', JSON.stringify(false));
        $watch(
            'darkMode',
            value => {
                if (value === true) {
                    darkMode = false;
                }
                localStorage.setItem('darkMode', JSON.stringify(false));
            }
        )
    "
    :class="{
        'dark bg-gray-900': false
    }"
>


    <!-- =========================================================
         PAGE WRAPPER
    ========================================================== -->

    <div class="flex h-screen overflow-hidden">


        <?php include('sidebar.php'); ?>


        <!-- =====================================================
             CONTENT AREA
        ====================================================== -->

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


            <main>


                <?php include('header.php'); ?>


                <!-- =================================================
                     PRODUCTION GATEWAY
                ================================================== -->

                <section class="production-gateway">


                    <div class="pi-orb pi-orb-one"></div>

                    <div class="pi-orb pi-orb-two"></div>


                    <div class="pi-gateway-container">


                        <!-- =================================================
                             SYSTEM BAR
                        ================================================== -->

                        <div class="pi-system-bar">


                            <div class="pi-system-status">

                                <span class="pi-system-dot"></span>

                                Production Intelligence Online

                            </div>


                            <div class="pi-system-code">

                                SYS / PRODUCTION / INITIALIZATION

                            </div>


                        </div>



                        <!-- =================================================
                             HERO
                        ================================================== -->

                        <section class="pi-gateway-hero">


                            <!-- =================================================
                                 HERO CONTENT
                            ================================================== -->

                            <div class="pi-hero-content">


                                <div class="pi-intelligence-label">

                                    <i class="fa-solid fa-sparkles"></i>

                                    Intelligent Production Gateway

                                </div>


                                <h1 class="pi-main-title">

                                    Initialize Your

                                    <span>
                                        Production Command.
                                    </span>

                                </h1>


                                <p class="pi-hero-description">

                                    Configure the production intelligence
                                    pathway before entering the operational
                                    workspace. Your selection determines how
                                    the production workflow is initialized.

                                </p>


                                <div class="pi-signals">


                                    <div class="pi-signal">

                                        <i class="fa-solid fa-brain"></i>

                                        Workflow Intelligence

                                    </div>


                                    <div class="pi-signal">

                                        <i class="fa-solid fa-diagram-project"></i>

                                        Process Mapping

                                    </div>


                                    <div class="pi-signal">

                                        <i class="fa-solid fa-shield-halved"></i>

                                        Controlled Execution

                                    </div>


                                </div>


                            </div>



                            <!-- =================================================
                                 INTELLIGENCE CORE
                            ================================================== -->

                            <div class="pi-command-visual">


                                <div class="pi-core-ring">


                                    <div class="pi-core">

                                        <i class="fa-solid fa-microchip"></i>

                                    </div>


                                    <div class="pi-core-label">

                                        Intelligence Core

                                    </div>


                                </div>



                                <div class="pi-node pi-node-one">

                                    <i class="fa-solid fa-bolt"></i>

                                    READY

                                </div>


                                <div class="pi-node pi-node-two">

                                    <i class="fa-solid fa-network-wired"></i>

                                    WORKFLOW

                                </div>


                                <div class="pi-node pi-node-three">

                                    <i class="fa-solid fa-chart-line"></i>

                                    ANALYSIS

                                </div>


                            </div>


                        </section>



                        <!-- =================================================
                             PRODUCTION STAGE SELECTION
                        ================================================== -->

                        <form
                            method="POST"
                            action="production_option.php"
                            id="production_form"
                        >


                            <section class="pi-selection-panel">


                                <div class="pi-selection-header">


                                    <div>

                                        <h2 class="pi-selection-title">

                                            Select Production Pathway

                                        </h2>


                                        <p class="pi-selection-description">

                                            Choose the operational state that
                                            best describes this production cycle.

                                        </p>

                                    </div>


                                    <div class="pi-step">

                                        <span class="pi-step-number">
                                            01
                                        </span>

                                        Configuration

                                    </div>


                                </div>



                                <!-- =================================================
                                     STAGE OPTIONS
                                ================================================== -->

                                <div class="pi-stage-selector">


                                    <!-- NEW -->
                                    <label class="pi-stage-option">

                                        <input
                                            type="radio"
                                            name="production_stage"
                                            value="New"
                                            required
                                        >


                                        <div class="pi-stage-card">


                                            <div class="pi-stage-icon">

                                                <i class="fa-solid fa-plus"></i>

                                            </div>


                                            <div class="pi-stage-content">

                                                <span class="pi-stage-name">

                                                    New Production

                                                </span>


                                                <span class="pi-stage-description">

                                                    Initialize a completely new
                                                    production workflow.

                                                </span>

                                            </div>


                                        </div>

                                    </label>



                                    <!-- EXISTING -->
                                    <label class="pi-stage-option">

                                        <input
                                            type="radio"
                                            name="production_stage"
                                            value="Existing"
                                        >


                                        <div class="pi-stage-card">


                                            <div class="pi-stage-icon">

                                                <i class="fa-solid fa-rotate"></i>

                                            </div>


                                            <div class="pi-stage-content">

                                                <span class="pi-stage-name">

                                                    Existing Production

                                                </span>


                                                <span class="pi-stage-description">

                                                    Continue or operate against
                                                    an existing production flow.

                                                </span>

                                            </div>


                                        </div>

                                    </label>



                                    <!-- REPRODUCTION -->
                                    <label class="pi-stage-option">

                                        <input
                                            type="radio"
                                            name="production_stage"
                                            value="Produced"
                                        >


                                        <div class="pi-stage-card">


                                            <div class="pi-stage-icon">

                                                <i class="fa-solid fa-arrows-rotate"></i>

                                            </div>


                                            <div class="pi-stage-content">

                                                <span class="pi-stage-name">

                                                    Reproduction

                                                </span>


                                                <span class="pi-stage-description">

                                                    Reproduce an established
                                                    production configuration.

                                                </span>

                                            </div>


                                        </div>

                                    </label>


                                </div>



                                <!-- =================================================
                                     ACTION AREA
                                ================================================== -->

                                <div class="pi-action-area">


                                    <div class="pi-security-note">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Production configuration is validated
                                        before execution.

                                    </div>


                                    <button
                                        type="submit"
                                        name="finalize_production"
                                        class="pi-next-button"
                                    >

                                        Continue to Production

                                        <i class="fa-solid fa-arrow-right"></i>

                                    </button>


                                </div>


                            </section>


                        </form>



                        <!-- =================================================
                             INTELLIGENCE STATUS
                        ================================================== -->

                        <div class="pi-intelligence-footer">


                            <div class="pi-footer-metric">

                                <div class="pi-footer-icon">

                                    <i class="fa-solid fa-brain"></i>

                                </div>


                                <div>

                                    <span class="pi-footer-label">
                                        Intelligence
                                    </span>

                                    <span class="pi-footer-value">
                                        Operational
                                    </span>

                                </div>

                            </div>



                            <div class="pi-footer-metric">

                                <div class="pi-footer-icon">

                                    <i class="fa-solid fa-shield-halved"></i>

                                </div>


                                <div>

                                    <span class="pi-footer-label">
                                        Workflow
                                    </span>

                                    <span class="pi-footer-value">
                                        Controlled
                                    </span>

                                </div>

                            </div>



                            <div class="pi-footer-metric">

                                <div class="pi-footer-icon">

                                    <i class="fa-solid fa-satellite-dish"></i>

                                </div>


                                <div>

                                    <span class="pi-footer-label">
                                        System
                                    </span>

                                    <span class="pi-footer-value">
                                        Connected
                                    </span>

                                </div>

                            </div>


                        </div>


                    </div>

                </section>


            </main>


        </div>


    </div>


    <?php include('footer.php'); ?>


    <!-- =========================================================
         YOUR EXISTING BUNDLE
    ========================================================== -->

    <script defer src="bundle.js"></script>


    <!-- =========================================================
         PRODUCTION GATEWAY JS
    ========================================================== -->

    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {


                /*
                |--------------------------------------------------------------------------
                | FORCE LIGHT INTERFACE
                |--------------------------------------------------------------------------
                */

                try {

                    localStorage.setItem(
                        'darkMode',
                        JSON.stringify(false)
                    );

                } catch (error) {

                    console.warn(
                        'Unable to update localStorage.',
                        error
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | STAGE SELECTION VISUAL FEEDBACK
                |--------------------------------------------------------------------------
                */

                const stageInputs =
                    document.querySelectorAll(
                        'input[name="production_stage"]'
                    );


                stageInputs.forEach(
                    function (input) {


                        input.addEventListener(
                            'change',
                            function () {


                                stageInputs.forEach(
                                    function (item) {

                                        const card =
                                            item.nextElementSibling;

                                        if (!card) return;

                                        card.setAttribute(
                                            'aria-selected',
                                            item.checked
                                        );

                                    }
                                );


                            }
                        );


                    }
                );


                /*
                |--------------------------------------------------------------------------
                | FORM VALIDATION
                |--------------------------------------------------------------------------
                */

                const form =
                    document.getElementById(
                        'production_form'
                    );


                if (form) {


                    form.addEventListener(
                        'submit',
                        function (event) {


                            const selected =
                                document.querySelector(
                                    'input[name="production_stage"]:checked'
                                );


                            if (!selected) {

                                event.preventDefault();


                                alert(
                                    'Please select a production pathway before continuing.'
                                );


                                return false;

                            }


                        }
                    );


                }


            }
        );

    </script>


</body>

</html>