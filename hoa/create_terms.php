<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


/*
|--------------------------------------------------------------------------
| COMPANY SESSION
|--------------------------------------------------------------------------
*/

$companyid = (int)($_SESSION['company_id'] ?? 0);

if ($companyid <= 0) {

    echo "<script>
        alert('Invalid company session.');
        window.location.href='terms.php';
    </script>";

    exit;

}


/*
|--------------------------------------------------------------------------
| COMPANY INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT company_name
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$companyid]);

$company_name_db = $stmt->fetchColumn();

$company_name = $company_name_db ?: ($company_name ?? 'Company');


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$created_by = (int)($_SESSION['user_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| CHECK EXISTING TERMS & CONDITIONS
|--------------------------------------------------------------------------
|
| One company should normally have one Terms & Conditions record.
| If one already exists, redirect the user back to the Terms page.
|
*/

$termsCheck = $pdo->prepare("
    SELECT id
    FROM terms_conditions
    WHERE company_id = ?
    LIMIT 1
");

$termsCheck->execute([$companyid]);

$existing_terms_id = $termsCheck->fetchColumn();

if ($existing_terms_id) {

    echo "<script>
        alert('Terms & Conditions already exist for this company. Please edit the existing Terms & Conditions instead.');
        window.location.href='terms.php';
    </script>";

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

<?= htmlspecialchars($company_name) ?>

|

Terms & Conditions Manager

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
   TERMS & CONDITIONS INTELLIGENCE TERMINAL
   LIGHT FUTURISTIC SYSTEM
========================================================= */

:root {

    --pi-bg: #f4f8fc;

    --pi-white: #ffffff;

    --pi-surface:
        rgba(255,255,255,.82);

    --pi-surface-solid:
        #ffffff;

    --pi-border:
        rgba(15,23,42,.09);

    --pi-border-blue:
        rgba(14,165,233,.20);

    --pi-text:
        #172033;

    --pi-heading:
        #0f172a;

    --pi-muted:
        #64748b;

    --pi-soft:
        #f8fafc;

    --pi-blue:
        #0284c7;

    --pi-cyan:
        #0891b2;

    --pi-purple:
        #7c3aed;

    --pi-green:
        #059669;

    --pi-amber:
        #d97706;

    --pi-red:
        #dc2626;

    --pi-shadow:
        0 18px 55px rgba(15,23,42,.08);

    --pi-shadow-hover:
        0 25px 70px rgba(14,165,233,.13);

}


/* =========================================================
   RESET
========================================================= */

.privacy-terminal,
.privacy-terminal *,
.privacy-terminal *::before,
.privacy-terminal *::after {

    box-sizing:
        border-box;

}


.privacy-terminal {

    width:
        100%;

    min-height:
        100vh;

    color:
        var(--pi-text);

    background:

        radial-gradient(
            circle at 8% 5%,
            rgba(14,165,233,.11),
            transparent 25%
        ),

        radial-gradient(
            circle at 92% 8%,
            rgba(124,58,237,.08),
            transparent 24%
        ),

        radial-gradient(
            circle at 50% 100%,
            rgba(6,182,212,.07),
            transparent 32%
        ),

        linear-gradient(
            180deg,
            #f8fbff 0%,
            #f2f7fc 100%
        );

    position:
        relative;

    overflow:
        hidden;

}


/* =========================================================
   FUTURISTIC GRID
========================================================= */

.privacy-terminal::before {

    content:
        "";

    position:
        fixed;

    inset:
        0;

    pointer-events:
        none;

    opacity:
        .55;

    background-image:

        linear-gradient(
            rgba(14,165,233,.035) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(14,165,233,.035) 1px,
            transparent 1px
        );

    background-size:
        42px 42px;

    mask-image:

        linear-gradient(
            to bottom,
            black 0%,
            transparent 90%
        );

}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.pi-workspace {

    position:
        relative;

    z-index:
        2;

    width:

        min(
            1750px,
            calc(100% - 32px)
        );

    margin:
        0 auto;

    padding:
        24px 0 55px;

}


/* =========================================================
   COMMAND HEADER
========================================================= */

.pi-command {

    position:
        relative;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        24px;

    padding:
        26px 28px;

    margin-bottom:
        20px;

    border:
        1px solid var(--pi-border-blue);

    border-radius:
        24px;

    background:

        linear-gradient(
            135deg,
            rgba(255,255,255,.94),
            rgba(247,252,255,.80)
        );

    box-shadow:
        var(--pi-shadow);

    backdrop-filter:
        blur(25px);

    -webkit-backdrop-filter:
        blur(25px);

    overflow:
        hidden;

}


.pi-command::before {

    content:
        "";

    position:
        absolute;

    left:
        0;

    top:
        0;

    bottom:
        0;

    width:
        4px;

    background:

        linear-gradient(
            180deg,
            var(--pi-cyan),
            var(--pi-blue),
            var(--pi-purple)
        );

}


.pi-command::after {

    content:
        "";

    position:
        absolute;

    width:
        280px;

    height:
        280px;

    right:
        -120px;

    top:
        -170px;

    border-radius:
        50%;

    background:

        radial-gradient(
            circle,
            rgba(14,165,233,.13),
            transparent 68%
        );

    pointer-events:
        none;

}


.pi-command-left {

    display:
        flex;

    align-items:
        center;

    gap:
        17px;

    min-width:
        0;

}


.pi-command-icon {

    width:
        62px;

    height:
        62px;

    flex:
        0 0 62px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        18px;

    color:
        var(--pi-blue);

    background:

        linear-gradient(
            145deg,
            rgba(14,165,233,.12),
            rgba(124,58,237,.08)
        );

    border:

        1px solid
        rgba(14,165,233,.18);

    box-shadow:

        0 12px 30px
        rgba(14,165,233,.10),

        inset 0 1px
        rgba(255,255,255,.9);

    font-size:
        25px;

}


.pi-command-eyebrow {

    margin:
        0 0 5px;

    color:
        var(--pi-blue);

    font-size:
        10px;

    font-weight:
        800;

    letter-spacing:
        .20em;

    text-transform:
        uppercase;

}


.pi-command-title {

    margin:
        0;

    color:
        var(--pi-heading);

    font-size:

        clamp(
            24px,
            3vw,
            37px
        );

    line-height:
        1.05;

    font-weight:
        850;

    letter-spacing:
        -.045em;

}


.pi-command-description {

    margin:
        8px 0 0;

    max-width:
        760px;

    color:
        var(--pi-muted);

    font-size:
        13px;

    line-height:
        1.65;

}


.pi-system-status {

    position:
        relative;

    z-index:
        2;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        9px;

    padding:
        10px 14px;

    white-space:
        nowrap;

    border:

        1px solid
        rgba(5,150,105,.16);

    border-radius:
        999px;

    background:
        rgba(5,150,105,.055);

    color:
        #047857;

    font-size:
        11px;

    font-weight:
        800;

}


.pi-status-light {

    width:
        8px;

    height:
        8px;

    border-radius:
        50%;

    background:
        #10b981;

    box-shadow:

        0 0 0 5px
        rgba(16,185,129,.09),

        0 0 15px
        rgba(16,185,129,.42);

    animation:
        piStatusPulse 2s infinite;

}


@keyframes piStatusPulse {

    0%,
    100% {

        opacity:
            1;

        transform:
            scale(1);

    }

    50% {

        opacity:
            .55;

        transform:
            scale(.82);

    }

}


/* =========================================================
   FORM SHELL
========================================================= */

.pi-form-shell {

    position:
        relative;

    border:

        1px solid
        var(--pi-border);

    border-radius:
        24px;

    background:
        rgba(255,255,255,.86);

    box-shadow:
        var(--pi-shadow);

    backdrop-filter:
        blur(25px);

    -webkit-backdrop-filter:
        blur(25px);

    overflow:
        hidden;

}


.pi-form-topline {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        20px 25px;

    border-bottom:

        1px solid
        rgba(15,23,42,.07);

    background:
        rgba(248,250,252,.72);

}


.pi-form-heading {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

}


.pi-form-heading-icon {

    width:
        38px;

    height:
        38px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        11px;

    color:
        var(--pi-blue);

    background:
        rgba(14,165,233,.08);

    border:

        1px solid
        rgba(14,165,233,.14);

}


.pi-form-heading h2 {

    margin:
        0;

    color:
        var(--pi-heading);

    font-size:
        15px;

    font-weight:
        850;

}


.pi-form-heading p {

    margin:
        3px 0 0;

    color:
        var(--pi-muted);

    font-size:
        10px;

}


.pi-terminal-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    padding:
        7px 10px;

    border:

        1px solid
        rgba(14,165,233,.13);

    border-radius:
        8px;

    color:
        var(--pi-blue);

    background:
        rgba(14,165,233,.055);

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        .08em;

    text-transform:
        uppercase;

}


/* =========================================================
   FORM CONTENT
========================================================= */

.pi-form-content {

    padding:
        25px;

}


.pi-section {

    margin-bottom:
        28px;

}


.pi-section:last-child {

    margin-bottom:
        0;

}


.pi-section-head {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    margin-bottom:
        15px;

}


.pi-section-number {

    width:
        28px;

    height:
        28px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        9px;

    color:
        var(--pi-blue);

    background:
        rgba(14,165,233,.075);

    border:

        1px solid
        rgba(14,165,233,.14);

    font-size:
        10px;

    font-weight:
        900;

}


.pi-section-title {

    margin:
        0;

    color:
        var(--pi-heading);

    font-size:
        13px;

    font-weight:
        850;

}


.pi-section-subtitle {

    margin:
        2px 0 0;

    color:
        #94a3b8;

    font-size:
        10px;

}


/* =========================================================
   FORM GRID
========================================================= */

.pi-field-grid {

    display:
        grid;

    grid-template-columns:

        repeat(
            2,
            minmax(0,1fr)
        );

    gap:
        16px;

}


.pi-field {

    min-width:
        0;

}


.pi-field.full {

    grid-column:
        1 / -1;

}


.pi-label {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    margin-bottom:
        7px;

    color:
        #475569;

    font-size:
        10px;

    font-weight:
        800;

    letter-spacing:
        .055em;

    text-transform:
        uppercase;

}


.pi-label i {

    color:
        var(--pi-blue);

    font-size:
        10px;

}


.pi-input,
.pi-select,
.pi-textarea {

    width:
        100%;

    border:

        1px solid
        rgba(15,23,42,.11);

    border-radius:
        12px;

    outline:
        none;

    background:
        rgba(255,255,255,.90);

    color:
        var(--pi-heading);

    box-shadow:

        inset 0 1px 2px
        rgba(15,23,42,.025);

    transition:

        border-color .2s ease,
        box-shadow .2s ease,
        transform .2s ease;

}


.pi-input,
.pi-select {

    min-height:
        46px;

    padding:
        0 13px;

}


.pi-textarea {

    min-height:
        150px;

    padding:
        12px 13px;

    resize:
        vertical;

    line-height:
        1.7;

}


.pi-input::placeholder,
.pi-textarea::placeholder {

    color:
        #a8b3c2;

}


.pi-input:focus,
.pi-select:focus,
.pi-textarea:focus {

    border-color:
        rgba(14,165,233,.45);

    box-shadow:

        0 0 0 4px
        rgba(14,165,233,.075),

        0 8px 25px
        rgba(14,165,233,.055);

}


.pi-helper {

    margin:
        6px 0 0;

    color:
        #94a3b8;

    font-size:
        10px;

    line-height:
        1.5;

}


/* =========================================================
   STATUS ZONE
========================================================= */

.pi-status-zone {

    padding:
        17px;

    border:

        1px solid
        rgba(15,23,42,.07);

    border-radius:
        15px;

    background:

        linear-gradient(
            135deg,
            rgba(248,250,252,.95),
            rgba(255,255,255,.78)
        );

}


.pi-status-description {

    margin:
        7px 0 0;

    color:
        #94a3b8;

    font-size:
        10px;

    line-height:
        1.5;

}


/* =========================================================
   POLICY PREVIEW
========================================================= */

.pi-preview-card {

    padding:
        18px;

    border:

        1px solid
        rgba(14,165,233,.12);

    border-radius:
        16px;

    background:

        linear-gradient(
            135deg,
            rgba(14,165,233,.035),
            rgba(124,58,237,.025)
        );

}


.pi-preview-row {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        12px;

}


.pi-preview-icon {

    width:
        42px;

    height:
        42px;

    flex:
        0 0 42px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        12px;

    color:
        var(--pi-purple);

    background:
        rgba(124,58,237,.07);

    border:

        1px solid
        rgba(124,58,237,.12);

}


.pi-preview-card strong {

    display:
        block;

    color:
        var(--pi-heading);

    font-size:
        12px;

    font-weight:
        850;

}


.pi-preview-card span {

    display:
        block;

    margin-top:
        4px;

    color:
        #94a3b8;

    font-size:
        10px;

    line-height:
        1.6;

}


/* =========================================================
   SUBMIT AREA
========================================================= */

.pi-submit-area {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    margin-top:
        28px;

    padding:
        20px;

    border:

        1px solid
        rgba(14,165,233,.10);

    border-radius:
        17px;

    background:

        linear-gradient(
            135deg,
            rgba(14,165,233,.045),
            rgba(124,58,237,.035)
        );

}


.pi-submit-info {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

}


.pi-submit-info-icon {

    width:
        38px;

    height:
        38px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        10px;

    color:
        var(--pi-green);

    background:
        rgba(5,150,105,.07);

    border:

        1px solid
        rgba(5,150,105,.13);

}


.pi-submit-info strong {

    display:
        block;

    color:
        var(--pi-heading);

    font-size:
        11px;

    font-weight:
        850;

}


.pi-submit-info span {

    display:
        block;

    margin-top:
        3px;

    color:
        #94a3b8;

    font-size:
        9px;

}


.pi-render-button {

    position:
        relative;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        10px;

    min-width:
        235px;

    min-height:
        49px;

    padding:
        0 22px;

    border:
        0;

    border-radius:
        12px;

    color:
        #fff;

    background:

        linear-gradient(
            135deg,
            #0284c7,
            #0891b2,
            #7c3aed
        );

    box-shadow:

        0 13px 28px
        rgba(14,165,233,.20);

    cursor:
        pointer;

    font-size:
        11px;

    font-weight:
        850;

    letter-spacing:
        .025em;

    overflow:
        hidden;

    transition:

        transform .2s ease,
        box-shadow .2s ease;

}


.pi-render-button::before {

    content:
        "";

    position:
        absolute;

    inset:
        0;

    background:

        linear-gradient(
            110deg,
            transparent 25%,
            rgba(255,255,255,.25) 50%,
            transparent 75%
        );

    transform:
        translateX(-120%);

    transition:
        transform .65s ease;

}


.pi-render-button:hover {

    transform:
        translateY(-2px);

    box-shadow:

        0 17px 38px
        rgba(14,165,233,.26);

}


.pi-render-button:hover::before {

    transform:
        translateX(120%);

}


.pi-render-button:active {

    transform:
        translateY(0);

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 1000px) {

    .pi-workspace {

        width:

            min(
                100% - 24px,
                900px
            );

        padding-top:
            15px;

    }


    .pi-command {

        padding:
            22px;

    }


    .pi-form-content {

        padding:
            20px;

    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .pi-workspace {

        width:
            calc(100% - 20px);

        padding:
            10px 0 35px;

    }


    .pi-command {

        flex-direction:
            column;

        align-items:
            flex-start;

        padding:
            20px 18px;

        border-radius:
            19px;

    }


    .pi-command-left {

        align-items:
            flex-start;

    }


    .pi-command-icon {

        width:
            49px;

        height:
            49px;

        flex-basis:
            49px;

        border-radius:
            14px;

        font-size:
            19px;

    }


    .pi-command-title {

        font-size:
            24px;

    }


    .pi-command-description {

        font-size:
            11px;

    }


    .pi-system-status {

        font-size:
            10px;

    }


    .pi-form-shell {

        border-radius:
            18px;

    }


    .pi-form-topline {

        padding:
            15px;

    }


    .pi-form-content {

        padding:
            15px;

    }


    .pi-terminal-badge {

        display:
            none;

    }


    .pi-field-grid {

        grid-template-columns:
            1fr;

        gap:
            13px;

    }


    .pi-field.full {

        grid-column:
            auto;

    }


    .pi-submit-area {

        flex-direction:
            column;

        align-items:
            stretch;

    }


    .pi-submit-info {

        align-items:
            flex-start;

    }


    .pi-render-button {

        width:
            100%;

        min-width:
            0;

    }

}


/* =========================================================
   VERY SMALL DEVICES
========================================================= */

@media (max-width: 420px) {

    .pi-command-left {

        gap:
            11px;

    }


    .pi-command-icon {

        width:
            43px;

        height:
            43px;

        flex-basis:
            43px;

        border-radius:
            12px;

        font-size:
            17px;

    }


    .pi-command-title {

        font-size:
            21px;

    }


    .pi-section-title {

        font-size:
            12px;

    }


    .pi-section-subtitle {

        font-size:
            9px;

    }


    .pi-form-heading p {

        font-size:
            9px;

    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {

        animation-duration:
            .01ms !important;

        animation-iteration-count:
            1 !important;

        transition-duration:
            .01ms !important;

    }

}


/* =========================================================
   FORCE LIGHT APPEARANCE
========================================================= */

.privacy-terminal {

    color-scheme:
        light;

}


.privacy-terminal .dark\:bg-dark-900,
.privacy-terminal .dark\:bg-dark-800,
.privacy-terminal .dark\:text-white,
.privacy-terminal .dark\:text-gray-400 {

    background:
        transparent !important;

    color:
        inherit !important;

}


</style>

</head>

<body
    x-data="{
        page: 'terms',
        loaded: true,
        darkMode: false,
        stickyMenu: false,
        sidebarToggle: false,
        scrollTop: false
    }"
    x-init="
        darkMode = false;
        localStorage.setItem('darkMode', JSON.stringify(false));
    "
    :class="{}"
>

<div class="flex h-screen overflow-hidden">

<?php include('sidebar.php'); ?>

<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

<main>

<?php include('header.php'); ?>

<section class="privacy-terminal">

<div class="pi-workspace">

<!-- =================================================
     TERMS COMMAND HEADER
================================================== -->

<section class="pi-command">

<div class="pi-command-left">

<div class="pi-command-icon">

<i class="fa-solid fa-scale-balanced"></i>

</div>

<div>

<p class="pi-command-eyebrow">

Legal Agreement System

</p>

<h1 class="pi-command-title">

Terms & Conditions Publisher

</h1>

<p class="pi-command-description">

Configure and publish your company's Terms & Conditions,
defining the rules, responsibilities, acceptable use and
legal conditions governing the use of your company's
website, services and digital platforms.

</p>

</div>

</div>

<div class="pi-system-status">

<span class="pi-status-light"></span>

Terms System Online

</div>

</section>

<!-- =================================================
     TERMS & CONDITIONS FORM
================================================== -->

<form
    method="POST"
    action="insert_terms.php"
    id="terms_form"
    class="pi-form-shell"
>

<!-- FORM HEADER -->

<div class="pi-form-topline">

<div class="pi-form-heading">

<div class="pi-form-heading-icon">

<i class="fa-solid fa-file-contract"></i>

</div>

<div>

<h2>

Terms & Conditions Configuration

</h2>

<p>

Define every section of your company's legal terms
before publishing them.

</p>

</div>

</div>

<div class="pi-terminal-badge">

<i class="fa-solid fa-scale-balanced"></i>

Legal Terminal

</div>

</div>

<div class="pi-form-content">

<!-- =================================================
     SECTION 01
     TERMS IDENTITY
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

01

</div>

<div>

<h3 class="pi-section-title">

Terms Identity

</h3>

<p class="pi-section-subtitle">

Establish the primary identity and introduction
of your Terms & Conditions.

</p>

</div>

</div>

<div class="pi-field-grid">

<!-- EYEBROW -->

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-tag"></i>

Terms Eyebrow

</label>

<input
type="text"
name="eyebrow"
id="eyebrow"
class="pi-input"
placeholder="e.g. Legal Information"
maxlength="255"

>

<p class="pi-helper">

A short introductory label displayed above the main title.

</p>

</div>

<!-- TITLE -->

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-heading"></i>

Terms Title

</label>

<input
type="text"
name="title"
id="title"
class="pi-input"
placeholder="e.g. Terms & Conditions"
maxlength="255"
required

>

</div>

<!-- INTRO -->

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-align-left"></i>

Terms Introduction

</label>

<textarea
    name="intro"
    id="intro"
    class="pi-textarea"
    placeholder="Introduce your Terms & Conditions and explain the agreement between your company and users..."
    required
></textarea>

<p class="pi-helper">

Explain the purpose of these Terms & Conditions and
the agreement users enter into when accessing your
website or services.

</p>

</div>

</div>

</section>

<!-- =================================================
     SECTION 02
     ACCEPTANCE OF TERMS
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

02

</div>

<div>

<h3 class="pi-section-title">

Acceptance of Terms

</h3>

<p class="pi-section-subtitle">

Define how users agree to and accept your Terms & Conditions.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-check-double"></i>

Section Title

</label>

<input
type="text"
name="acceptance_title"
class="pi-input"
value="Acceptance of Terms"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-handshake"></i>

</div>

<div>

<strong>

User Agreement

</strong>

<span>

Explain that users agree to comply with these
Terms & Conditions when accessing or using
your company's website or services.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-file-signature"></i>

Acceptance Content

</label>

<textarea
    name="acceptance_content"
    class="pi-textarea"
    placeholder="Explain how users accept these Terms & Conditions..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 03
     WEBSITE USE
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

03

</div>

<div>

<h3 class="pi-section-title">

Website & Service Use

</h3>

<p class="pi-section-subtitle">

Define the rules governing acceptable use of your website
and company services.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-globe"></i>

Section Title

</label>

<input
type="text"
name="website_use_title"
class="pi-input"
value="Use of Our Website and Services"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-laptop"></i>

</div>

<div>

<strong>

Acceptable Use

</strong>

<span>

Define the rules, responsibilities and restrictions
that apply when users access your website and
services.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-list-check"></i>

Website Use Content

</label>

<textarea
    name="website_use_content"
    class="pi-textarea"
    placeholder="Explain the acceptable and prohibited use of your website and services..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 04
     INTELLECTUAL PROPERTY
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

04

</div>

<div>

<h3 class="pi-section-title">

Intellectual Property

</h3>

<p class="pi-section-subtitle">

Explain ownership and protection of your company's
content, trademarks and other intellectual property.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-copyright"></i>

Section Title

</label>

<input
type="text"
name="intellectual_property_title"
class="pi-input"
value="Intellectual Property Rights"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-lightbulb"></i>

</div>

<div>

<strong>

Ownership Notice

</strong>

<span>

Clarify ownership of company content, branding,
logos, designs, documents and other materials.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-copyright"></i>

Intellectual Property Content

</label>

<textarea
    name="intellectual_property_content"
    class="pi-textarea"
    placeholder="Explain your company's intellectual property rights and restrictions on use..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 05
     WEBSITE INFORMATION
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

05

</div>

<div>

<h3 class="pi-section-title">

Website Information

</h3>

<p class="pi-section-subtitle">

Explain the nature, accuracy and availability of information
provided through your website or services.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-circle-info"></i>

Section Title

</label>

<input
type="text"
name="website_information_title"
class="pi-input"
value="Website Information"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-newspaper"></i>

</div>

<div>

<strong>

Information Disclaimer

</strong>

<span>

Clarify how information published on your website
should be understood and whether it may change
over time.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-circle-info"></i>

Website Information Content

</label>

<textarea
    name="website_information_content"
    class="pi-textarea"
    placeholder="Explain the nature, accuracy and availability of information provided on your website..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 06
     LIABILITY
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

06

</div>

<div>

<h3 class="pi-section-title">

Limitation of Liability

</h3>

<p class="pi-section-subtitle">

Define the limits of your company's responsibility
in connection with the use of your website or services.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-shield-halved"></i>

Section Title

</label>

<input
type="text"
name="liability_title"
class="pi-input"
value="Limitation of Liability"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-scale-balanced"></i>

</div>

<div>

<strong>

Liability Notice

</strong>

<span>

Explain the limits and conditions of your company's
responsibility relating to website and service use.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-shield-halved"></i>

Liability Content

</label>

<textarea
    name="liability_content"
    class="pi-textarea"
    placeholder="Explain any limitations of liability relating to your company's website or services..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 07
     THIRD PARTY SERVICES
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

07

</div>

<div>

<h3 class="pi-section-title">

Third-Party Services & Links

</h3>

<p class="pi-section-subtitle">

Explain your company's position regarding external
websites, services and third-party resources.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-link"></i>

Section Title

</label>

<input
type="text"
name="third_party_title"
class="pi-input"
value="Third-Party Services and Links"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-arrow-up-right-from-square"></i>

</div>

<div>

<strong>

External Resources

</strong>

<span>

Explain how users should understand links,
services and content provided by third parties.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-link"></i>

Third-Party Content

</label>

<textarea
    name="third_party_content"
    class="pi-textarea"
    placeholder="Explain your company's position regarding third-party websites, services and external links..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 08
     CHANGES TO TERMS
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

08

</div>

<div>

<h3 class="pi-section-title">

Changes to These Terms

</h3>

<p class="pi-section-subtitle">

Explain how changes and updates to these Terms & Conditions
will be communicated.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-arrows-rotate"></i>

Section Title

</label>

<input
type="text"
name="changes_title"
class="pi-input"
value="Changes to These Terms and Conditions"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-clock-rotate-left"></i>

</div>

<div>

<strong>

Terms Updates

</strong>

<span>

Define how future revisions to these Terms &
Conditions may be published or communicated.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-arrows-rotate"></i>

Changes Content

</label>

<textarea
    name="changes_content"
    class="pi-textarea"
    placeholder="Explain how updates or changes to these Terms & Conditions will be communicated..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 09
     CONTACT
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

09

</div>

<div>

<h3 class="pi-section-title">

Contact Information

</h3>

<p class="pi-section-subtitle">

Provide information on how users can contact your company
regarding these Terms & Conditions.

</p>

</div>

</div>

<div class="pi-field-grid">

<div class="pi-field">

<label class="pi-label">

<i class="fa-solid fa-envelope"></i>

Section Title

</label>

<input
type="text"
name="contact_title"
class="pi-input"
value="Contact Us"
placeholder="Enter section title"
required

>

</div>

<div class="pi-field">

<div class="pi-preview-card">

<div class="pi-preview-row">

<div class="pi-preview-icon">

<i class="fa-solid fa-headset"></i>

</div>

<div>

<strong>

Legal Support

</strong>

<span>

Tell users how they can contact your company
with questions about these Terms & Conditions.

</span>

</div>

</div>

</div>

</div>

<div class="pi-field full">

<label class="pi-label">

<i class="fa-solid fa-headset"></i>

Contact Content

</label>

<textarea
    name="contact_content"
    class="pi-textarea"
    placeholder="Provide instructions for contacting your company regarding these Terms & Conditions..."
    required
></textarea>

</div>

</div>

</section>

<!-- =================================================
     SECTION 10
     TERMS STATUS
================================================== -->

<section class="pi-section">

<div class="pi-section-head">

<div class="pi-section-number">

10

</div>

<div>

<h3 class="pi-section-title">

Publication State

</h3>

<p class="pi-section-subtitle">

Control the availability of these Terms & Conditions.

</p>

</div>

</div>

<div class="pi-status-zone">

<label class="pi-label">

<i class="fa-solid fa-circle-nodes"></i>

Terms & Conditions Status

</label>

<select
name="status"
id="status"
class="pi-select"
required

>

<option value="">

-- Select Terms Status --

</option>

<option value="Draft">

Draft

</option>

<option value="Active">

Active / Published

</option>

<option value="Inactive">

Inactive

</option>

</select>

<p class="pi-status-description">

<strong>Draft</strong> keeps the Terms & Conditions
unpublished. <strong>Active</strong> makes them
available on your company's platform. <strong>Inactive</strong> disables the Terms &
Conditions without deleting the stored record.

</p>

</div>

</section>

<!-- =================================================
     FINAL PUBLISH
================================================== -->

<div class="pi-submit-area">

<div class="pi-submit-info">

<div class="pi-submit-info-icon">

<i class="fa-solid fa-scale-balanced"></i>

</div>

<div>

<strong>

Ready for Terms Publication

</strong>

<span>

Review all legal sections before committing
the Terms & Conditions to your company record.

</span>

</div>

</div>

<button
type="submit"
name="create_terms"
class="pi-render-button"

>

<i class="fa-solid fa-file-circle-check"></i>

Create Terms & Conditions

<i class="fa-solid fa-arrow-right"></i>

</button>

</div>

</div>

</form>

</div>

</section>

</main>

</div>

</div>

<?php include('footer.php'); ?>

<script defer src="bundle.js"></script>

<script>


/* =========================================================
   TERMS & CONDITIONS FORM VALIDATION
========================================================= */

window.addEventListener(
    'DOMContentLoaded',
    function() {


        const form =
            document.getElementById(
                'terms_form'
            );


        if (!form) {

            return;

        }


        form.addEventListener(
            'submit',
            function(e) {


                const requiredFields = [

                    {
                        id: 'title',
                        message:
                            'Please enter the Terms & Conditions title.'
                    },

                    {
                        id: 'intro',
                        message:
                            'Please enter the Terms & Conditions introduction.'
                    },

                    {
                        id: 'status',
                        message:
                            'Please select the Terms & Conditions status.'
                    }

                ];


                for (
                    const field of requiredFields
                ) {


                    const input =
                        document.getElementById(
                            field.id
                        );


                    if (
                        !input ||
                        !input.value.trim()
                    ) {


                        alert(
                            field.message
                        );


                        input?.focus();


                        e.preventDefault();


                        return;

                    }


                }


                const textareas =
                    form.querySelectorAll(
                        'textarea[required]'
                    );


                for (
                    const textarea of textareas
                ) {


                    if (
                        !textarea.value.trim()
                    ) {


                        alert(
                            'Please complete all required Terms & Conditions content sections.'
                        );


                        textarea.focus();


                        e.preventDefault();


                        return;

                    }


                }


            }
        );


    }
);


</script>

</body>

</html>
