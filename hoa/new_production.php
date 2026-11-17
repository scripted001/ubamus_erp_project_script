
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

$companyid = (int)($_SESSION['company_id'] ?? 0);

if ($companyid <= 0) {
    echo "<script>
        alert('Invalid company session.');
        window.location.href='production.php';
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
| COMPANY ABBREVIATION
|--------------------------------------------------------------------------
*/

$words = preg_split('/\s+/', trim($company_name));

$company_abbr = '';

foreach ($words as $word) {

    if ($word !== '') {
        $company_abbr .= strtoupper($word[0]);
    }
}


/*
|--------------------------------------------------------------------------
| SINGLE WORD COMPANY
|--------------------------------------------------------------------------
*/

if (count($words) === 1) {

    $company_abbr = strtoupper(
        substr($company_name, 0, 3)
    );

}


/*
|--------------------------------------------------------------------------
| LOCATIONS
|--------------------------------------------------------------------------
*/

$locationStmt = $pdo->prepare("
    SELECT
        id,
        name,
        type
    FROM locations
    WHERE company_id = ?
    ORDER BY name ASC
");

$locationStmt->execute([$companyid]);

$locations = $locationStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PRODUCTION CATEGORIES
|--------------------------------------------------------------------------
*/

$stk_type = "Production Only";

$categoryStmt = $pdo->prepare("
    SELECT
        id,
        name
    FROM product_categories
    WHERE company_id = ?
      AND dept = ?
    ORDER BY name ASC
");

$categoryStmt->execute([
    $companyid,
    $stk_type
]);

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <?= htmlspecialchars($company_name) ?> | Production Intelligence
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
   PRODUCTION INTELLIGENCE TERMINAL
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

.production-terminal,
.production-terminal *,
.production-terminal *::before,
.production-terminal *::after {

    box-sizing: border-box;

}


.production-terminal {

    width: 100%;

    min-height: 100vh;

    color: var(--pi-text);

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

    position: relative;

    overflow: hidden;

}


/* =========================================================
   FUTURISTIC GRID
========================================================= */

.production-terminal::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

    opacity: .55;

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

    position: relative;

    z-index: 2;

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

    position: relative;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 24px;

    padding:
        26px 28px;

    margin-bottom: 20px;

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

    overflow: hidden;

}


.pi-command::before {

    content: "";

    position: absolute;

    left: 0;

    top: 0;

    bottom: 0;

    width: 4px;

    background:
        linear-gradient(
            180deg,
            var(--pi-cyan),
            var(--pi-blue),
            var(--pi-purple)
        );

}


.pi-command::after {

    content: "";

    position: absolute;

    width: 280px;

    height: 280px;

    right: -120px;

    top: -170px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(14,165,233,.13),
            transparent 68%
        );

    pointer-events: none;

}


.pi-command-left {

    display: flex;

    align-items: center;

    gap: 17px;

    min-width: 0;

}


.pi-command-icon {

    width: 62px;

    height: 62px;

    flex:
        0 0 62px;

    display: grid;

    place-items: center;

    border-radius: 18px;

    color: var(--pi-blue);

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

    font-size: 25px;

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

    margin: 0;

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

    0%,100% {

        opacity: 1;

        transform:
            scale(1);

    }

    50% {

        opacity: .55;

        transform:
            scale(.82);

    }

}


/* =========================================================
   FORM SHELL
========================================================= */

.pi-form-shell {

    position: relative;

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
        105px;

    padding:
        12px 13px;

    resize:
        vertical;

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


.pi-input[readonly] {

    background:
        #f8fafc;

    color:
        #475569;

    cursor:
        default;

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
   DATA TABLES
========================================================= */

.pi-data-card {

    border:
        1px solid
        rgba(15,23,42,.075);

    border-radius:
        16px;

    background:
        rgba(248,250,252,.65);

    overflow:
        hidden;

}


.pi-data-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    padding:
        14px 15px;

    border-bottom:
        1px solid
        rgba(15,23,42,.07);

    background:
        rgba(255,255,255,.72);

}


.pi-data-title {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    color:
        var(--pi-heading);

    font-size:
        12px;

    font-weight:
        850;

}


.pi-data-title i {

    color:
        var(--pi-cyan);

}


.pi-data-hint {

    color:
        #94a3b8;

    font-size:
        9px;

}


.pi-table-scroll {

    width:
        100%;

    overflow-x:
        auto;

}


.pi-data-table {

    width:
        100%;

    min-width:
        760px;

    border-collapse:
        collapse;

}


.pi-data-table th {

    padding:
        11px 12px;

    text-align:
        left;

    color:
        #64748b;

    background:
        rgba(241,245,249,.72);

    border-bottom:
        1px solid
        rgba(15,23,42,.065);

    font-size:
        9px;

    font-weight:
        850;

    letter-spacing:
        .07em;

    text-transform:
        uppercase;

    white-space:
        nowrap;

}


.pi-data-table td {

    padding:
        10px 12px;

    border-bottom:
        1px solid
        rgba(15,23,42,.055);

}


.pi-data-table tr:last-child td {

    border-bottom:
        0;

}


.pi-data-table input {

    width:
        100%;

    min-width:
        0;

    height:
        38px;

    padding:
        0 10px;

    border:
        1px solid
        rgba(15,23,42,.10);

    border-radius:
        9px;

    outline:
        none;

    background:
        #fff;

    color:
        var(--pi-heading);

    font-size:
        11px;

}


.pi-data-table input:focus {

    border-color:
        rgba(14,165,233,.35);

    box-shadow:
        0 0 0 3px
        rgba(14,165,233,.06);

}


.pi-data-table input[readonly] {

    background:
        #f1f5f9;

    color:
        #64748b;

}


.pi-remove {

    height:
        36px;

    padding:
        0 11px;

    border:
        1px solid
        rgba(220,38,38,.13);

    border-radius:
        9px;

    background:
        rgba(220,38,38,.055);

    color:
        #dc2626;

    cursor:
        pointer;

    font-size:
        10px;

    font-weight:
        800;

    transition:
        .2s ease;

}


.pi-remove:hover {

    background:
        rgba(220,38,38,.10);

    border-color:
        rgba(220,38,38,.24);

    transform:
        translateY(-1px);

}


/* =========================================================
   ADD BUTTON
========================================================= */

.pi-add-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    min-height:
        42px;

    margin-top:
        12px;

    padding:
        0 16px;

    border:
        1px solid
        rgba(14,165,233,.16);

    border-radius:
        11px;

    background:
        linear-gradient(
            135deg,
            rgba(14,165,233,.09),
            rgba(8,145,178,.05)
        );

    color:
        var(--pi-blue);

    cursor:
        pointer;

    font-size:
        10px;

    font-weight:
        850;

    transition:
        .2s ease;

}


.pi-add-button:hover {

    background:
        linear-gradient(
            135deg,
            rgba(14,165,233,.14),
            rgba(8,145,178,.08)
        );

    transform:
        translateY(-2px);

    box-shadow:
        0 9px 22px
        rgba(14,165,233,.09);

}


.pi-add-button.service {

    color:
        var(--pi-purple);

    border-color:
        rgba(124,58,237,.15);

    background:
        rgba(124,58,237,.055);

}


.pi-add-button.service:hover {

    background:
        rgba(124,58,237,.10);

}


/* =========================================================
   COST INTELLIGENCE
========================================================= */

.pi-intelligence-summary {

    display:
        grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    gap:
        12px;

    margin-top:
        15px;

}


.pi-cost-metric {

    position:
        relative;

    padding:
        16px;

    border:
        1px solid
        rgba(15,23,42,.07);

    border-radius:
        14px;

    background:
        #fff;

    overflow:
        hidden;

}


.pi-cost-metric::after {

    content:
        "";

    position:
        absolute;

    width:
        75px;

    height:
        75px;

    right:
        -35px;

    bottom:
        -35px;

    border-radius:
        50%;

    background:
        radial-gradient(
            circle,
            rgba(14,165,233,.10),
            transparent 68%
        );

}


.pi-cost-label {

    color:
        #94a3b8;

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        .06em;

    text-transform:
        uppercase;

}


.pi-cost-value {

    display:
        block;

    margin-top:
        6px;

    color:
        var(--pi-heading);

    font-size:
        20px;

    font-weight:
        850;

    letter-spacing:
        -.025em;

}


.pi-cost-value.production {

    color:
        var(--pi-blue);

}


.pi-cost-value.service {

    color:
        var(--pi-purple);

}


.pi-cost-value.total {

    color:
        var(--pi-green);

}


/* =========================================================
   PRICING GRID
========================================================= */

.pi-pricing-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    gap:
        14px;

    margin-top:
        14px;

}


.pi-price-box {

    padding:
        16px;

    border:
        1px solid
        rgba(15,23,42,.075);

    border-radius:
        14px;

    background:
        linear-gradient(
            145deg,
            #fff,
            #f8fafc
        );

}


.pi-price-box label {

    display:
        block;

    margin-bottom:
        7px;

    color:
        #64748b;

    font-size:
        9px;

    font-weight:
        800;

    text-transform:
        uppercase;

    letter-spacing:
        .06em;

}


.pi-price-box input {

    width:
        100%;

    height:
        42px;

    padding:
        0 11px;

    border:
        1px solid
        rgba(15,23,42,.09);

    border-radius:
        9px;

    outline:
        none;

    background:
        #fff;

    color:
        var(--pi-heading);

    font-size:
        13px;

    font-weight:
        750;

}


.pi-price-box input:focus {

    border-color:
        rgba(14,165,233,.35);

    box-shadow:
        0 0 0 3px
        rgba(14,165,233,.06);

}


.pi-price-box input[readonly] {

    background:
        #f1f5f9;

}


/* =========================================================
   STATUS
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
   EMPTY TABLE STATE
========================================================= */

.pi-empty-row {

    padding:
        25px;

    text-align:
        center;

    color:
        #94a3b8;

    font-size:
        10px;

}


/* =========================================================
   MOBILE ROW MODE
========================================================= */

@media (max-width: 760px) {

    .pi-data-table {

        min-width:
            0;

    }

    .pi-data-table thead {

        display:
            none;

    }

    .pi-data-table,
    .pi-data-table tbody,
    .pi-data-table tr,
    .pi-data-table td {

        display:
            block;

        width:
            100%;

    }

    .pi-data-table tr {

        padding:
            13px;

        border-bottom:
            1px solid
            rgba(15,23,42,.065);

    }

    .pi-data-table tr:last-child {

        border-bottom:
            0;

    }

    .pi-data-table td {

        display:
            grid;

        grid-template-columns:
            110px minmax(0,1fr);

        gap:
            10px;

        align-items:
            center;

        padding:
            5px 0;

        border:
            0;

    }

    .pi-data-table td::before {

        color:
            #64748b;

        font-size:
            9px;

        font-weight:
            800;

        text-transform:
            uppercase;

        letter-spacing:
            .05em;

    }

    .pi-data-table td:nth-child(1)::before {

        content:
            "Material";

    }

    .pi-data-table td:nth-child(2)::before {

        content:
            "Quantity";

    }

    .pi-data-table td:nth-child(3)::before {

        content:
            "Unit Cost";

    }

    .pi-data-table td:nth-child(4)::before {

        content:
            "Total";

    }

    .pi-data-table td:nth-child(5)::before {

        content:
            "Action";

    }

    .pi-data-table td:nth-child(1) input,
    .pi-data-table td:nth-child(2) input,
    .pi-data-table td:nth-child(3) input,
    .pi-data-table td:nth-child(4) input {

        width:
            100%;

    }

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

    .pi-intelligence-summary {

        grid-template-columns:
            1fr;

    }

    .pi-pricing-grid {

        grid-template-columns:
            1fr;

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

.production-terminal {

    color-scheme:
        light;

}


/* Prevent inherited dark utility classes from changing
   this production interface */

.production-terminal .dark\:bg-dark-900,
.production-terminal .dark\:bg-dark-800,
.production-terminal .dark\:text-white,
.production-terminal .dark\:text-gray-400 {

    background:
        transparent !important;

    color:
        inherit !important;

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
    "
    :class="{}"
>


<div class="flex h-screen overflow-hidden">


    <?php include('sidebar.php'); ?>


    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <main>


            <?php include('header.php'); ?>


            <section class="production-terminal">


                <div class="pi-workspace">


                    <!-- =================================================
                         INTELLIGENCE COMMAND HEADER
                    ================================================== -->

                    <section class="pi-command">


                        <div class="pi-command-left">


                            <div class="pi-command-icon">

                                <i class="fa-solid fa-microchip"></i>

                            </div>


                            <div>

                                <p class="pi-command-eyebrow">
                                    Production Intelligence System
                                </p>

                                <h1 class="pi-command-title">
                                    Production Renderer
                                </h1>

                                <p class="pi-command-description">

                                    Configure production parameters,
                                    resource consumption, service costs
                                    and commercial intelligence before
                                    rendering the production record.

                                </p>

                            </div>


                        </div>


                        <div class="pi-system-status">

                            <span class="pi-status-light"></span>

                            Intelligence Online

                        </div>


                    </section>



                    <!-- =================================================
                         FORM
                    ================================================== -->

                    <form
                        method="POST"
                        action="insert_production.php"
                        id="production_form"
                        class="pi-form-shell"
                    >


                        <!-- FORM HEADER -->

                        <div class="pi-form-topline">


                            <div class="pi-form-heading">


                                <div class="pi-form-heading-icon">

                                    <i class="fa-solid fa-sliders"></i>

                                </div>


                                <div>

                                    <h2>
                                        Production Configuration
                                    </h2>

                                    <p>
                                        Define the production intelligence
                                        parameters for this render.
                                    </p>

                                </div>


                            </div>


                            <div class="pi-terminal-badge">

                                <i class="fa-solid fa-satellite-dish"></i>

                                Render Terminal

                            </div>


                        </div>



                        <div class="pi-form-content">


                            <!-- =================================================
                                 SECTION 01
                            ================================================== -->

                            <section class="pi-section">


                                <div class="pi-section-head">

                                    <div class="pi-section-number">
                                        01
                                    </div>

                                    <div>

                                        <h3 class="pi-section-title">
                                            Production Identity
                                        </h3>

                                        <p class="pi-section-subtitle">
                                            Establish where and what is being produced.
                                        </p>

                                    </div>

                                </div>


                                <div class="pi-field-grid">


                                    <!-- LOCATION -->

                                    <div class="pi-field">

                                        <label class="pi-label">

                                            <i class="fa-solid fa-location-dot"></i>

                                            Select Location

                                        </label>


                                        <select
                                            name="location_id"
                                            id="location_id"
                                            class="pi-select"
                                        >

                                            <option value="">
                                                -- Select Location --
                                            </option>


                                            <?php foreach ($locations as $location): ?>

                                                <option
                                                    value="<?= (int)$location['id'] ?>"
                                                >

                                                    <?= htmlspecialchars($location['name']) ?>

                                                    <?php if (!empty($location['type'])): ?>

                                                        (<?= htmlspecialchars($location['type']) ?>)

                                                    <?php endif; ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>



                                    <!-- CATEGORY -->

                                    <div class="pi-field">

                                        <label class="pi-label">

                                            <i class="fa-solid fa-layer-group"></i>

                                            Select Category

                                        </label>


                                        <select
                                            name="category_id"
                                            id="category_id"
                                            class="pi-select"
                                        >

                                            <option value="">
                                                -- Select Category --
                                            </option>


                                            <?php foreach ($categories as $category): ?>

                                                <option
                                                    value="<?= (int)$category['id'] ?>"
                                                >

                                                    <?= htmlspecialchars($category['name']) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>



                                    <!-- DEPARTMENT -->

                                    <div class="pi-field">

                                        <label class="pi-label">

                                            <i class="fa-solid fa-building"></i>

                                            Product Department

                                        </label>


                                        <select
                                            name="dept"
                                            class="pi-select"
                                        >

                                            <option value="">
                                                -- Select Department --
                                            </option>

                                            <option value="Sales Only">
                                                Sales
                                            </option>

                                            <option value="Service Only">
                                                Services
                                            </option>

                                        </select>

                                    </div>



                                    <!-- PRODUCT NAME -->

                                    <div class="pi-field">

                                        <label class="pi-label">

                                            <i class="fa-solid fa-cube"></i>

                                            New Product Name

                                        </label>


                                        <input
                                            type="text"
                                            name="product_name"
                                            id="new_product_name"
                                            placeholder="Enter new product name"
                                            class="pi-input"
                                        >


                                        <p class="pi-helper">

                                            Leave blank to select an
                                            existing batch/product.

                                        </p>

                                    </div>


                                </div>

                            </section>



                            <!-- =================================================
                                 SECTION 02
                            ================================================== -->

                            <section class="pi-section">


                                <div class="pi-section-head">

                                    <div class="pi-section-number">
                                        02
                                    </div>

                                    <div>

                                        <h3 class="pi-section-title">
                                            Batch Intelligence
                                        </h3>

                                        <p class="pi-section-subtitle">
                                            Identify the production run and expected output.
                                        </p>

                                    </div>

                                </div>


                                <div class="pi-field-grid">


                                    <!-- BATCH -->

                                    <div class="pi-field">

                                        <label class="pi-label">

                                            <i class="fa-solid fa-barcode"></i>

                                            Batch Number

                                        </label>


                                        <input
                                            type="text"
                                            name="batch_number"
                                            id="batch_number"
                                            placeholder="Generating..."
                                            readonly
                                            class="pi-input"
                                        >


                                        <p class="pi-helper">

                                            Automatically generated by the
                                            production intelligence engine.

                                        </p>

                                    </div>



                                    <!-- QUANTITY -->

                                    <div class="pi-field">

                                        <label class="pi-label">

                                            <i class="fa-solid fa-boxes-stacked"></i>

                                            Quantity Produced

                                        </label>


                                        <input
                                            type="number"
                                            name="quantity_produced"
                                            id="quantity_produced"
                                            min="0"
                                            step="any"
                                            placeholder="Enter production quantity"
                                            class="pi-input"
                                        >

                                    </div>



                                    <!-- DESCRIPTION -->

                                    <div class="pi-field full">

                                        <label class="pi-label">

                                            <i class="fa-solid fa-align-left"></i>

                                            Product Description

                                        </label>


                                        <textarea
                                            name="description"
                                            class="pi-textarea"
                                            placeholder="Describe the production output, specification or relevant operational information..."
                                        ></textarea>

                                    </div>


                                </div>

                            </section>



                            <!-- =================================================
                                 SECTION 03
                            ================================================== -->

                            <section class="pi-section">


                                <div class="pi-section-head">

                                    <div class="pi-section-number">
                                        03
                                    </div>

                                    <div>

                                        <h3 class="pi-section-title">
                                            Raw Material Intelligence
                                        </h3>

                                        <p class="pi-section-subtitle">
                                            Capture every material consumed by this production cycle.
                                        </p>

                                    </div>

                                </div>


                                <div class="pi-data-card">


                                    <div class="pi-data-header">

                                        <div class="pi-data-title">

                                            <i class="fa-solid fa-flask"></i>

                                            Material Consumption Matrix

                                        </div>


                                        <span class="pi-data-hint">
                                            Cost calculated automatically
                                        </span>

                                    </div>


                                    <div class="pi-table-scroll">

                                        <table class="pi-data-table">

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
                                                    Action
                                                </th>

                                            </tr>

                                            </thead>


                                            <tbody id="raw_materials_body">

                                            </tbody>

                                        </table>

                                    </div>


                                </div>


                                <button
                                    type="button"
                                    id="add_raw_material"
                                    class="pi-add-button"
                                >

                                    <i class="fa-solid fa-plus"></i>

                                    Add Raw Material

                                </button>


                            </section>



                            <!-- =================================================
                                 SECTION 04
                            ================================================== -->

                            <section class="pi-section">


                                <div class="pi-section-head">

                                    <div class="pi-section-number">
                                        04
                                    </div>

                                    <div>

                                        <h3 class="pi-section-title">
                                            Production Services
                                        </h3>

                                        <p class="pi-section-subtitle">
                                            Register external or internal services contributing to production cost.
                                        </p>

                                    </div>

                                </div>


                                <div class="pi-data-card">


                                    <div class="pi-data-header">

                                        <div class="pi-data-title">

                                            <i class="fa-solid fa-gears"></i>

                                            Service Cost Matrix

                                        </div>


                                        <span class="pi-data-hint">
                                            Operational service expenditure
                                        </span>

                                    </div>


                                    <div class="pi-table-scroll">

                                        <table class="pi-data-table">

                                            <thead>

                                            <tr>

                                                <th>
                                                    Service
                                                </th>

                                                <th>
                                                    Cost
                                                </th>

                                                <th>
                                                    Action
                                                </th>

                                            </tr>

                                            </thead>


                                            <tbody id="services_body">

                                            </tbody>

                                        </table>

                                    </div>


                                </div>


                                <button
                                    type="button"
                                    id="add_service"
                                    class="pi-add-button service"
                                >

                                    <i class="fa-solid fa-plus"></i>

                                    Add Production Service

                                </button>


                            </section>



                            <!-- =================================================
                                 SECTION 05
                            ================================================== -->

                            <section class="pi-section">


                                <div class="pi-section-head">

                                    <div class="pi-section-number">
                                        05
                                    </div>

                                    <div>

                                        <h3 class="pi-section-title">
                                            Cost Intelligence
                                        </h3>

                                        <p class="pi-section-subtitle">
                                            Real-time production economics generated from your inputs.
                                        </p>

                                    </div>

                                </div>


                                <div class="pi-data-card">


                                    <div class="pi-data-header">

                                        <div class="pi-data-title">

                                            <i class="fa-solid fa-chart-line"></i>

                                            Production Economics

                                        </div>


                                        <span class="pi-data-hint">
                                            Live calculation engine
                                        </span>

                                    </div>


                                    <div style="padding:15px;">


                                        <div class="pi-intelligence-summary">


                                            <div class="pi-cost-metric">

                                                <span class="pi-cost-label">
                                                    Raw Materials
                                                </span>

                                                <strong
                                                    id="total_materials_cost"
                                                    class="pi-cost-value production"
                                                >
                                                    0.00
                                                </strong>

                                            </div>


                                            <div class="pi-cost-metric">

                                                <span class="pi-cost-label">
                                                    Services
                                                </span>

                                                <strong
                                                    id="total_services_cost"
                                                    class="pi-cost-value service"
                                                >
                                                    0.00
                                                </strong>

                                            </div>


                                            <div class="pi-cost-metric">

                                                <span class="pi-cost-label">
                                                    Total Production
                                                </span>

                                                <strong
                                                    id="total_production_cost"
                                                    class="pi-cost-value total"
                                                >
                                                    0.00
                                                </strong>

                                            </div>


                                        </div>



                                        <div class="pi-pricing-grid">


                                            <div class="pi-price-box">

                                                <label>
                                                    Unit Cost Price
                                                </label>

                                                <input
                                                    type="text"
                                                    name="unit_cost"
                                                    id="unit_cost"
                                                    readonly
                                                    value="0.00"
                                                >

                                            </div>



                                            <div class="pi-price-box">

                                                <label>
                                                    Profit Margin (%)
                                                </label>

                                                <input
                                                    type="number"
                                                    name="profit_margin"
                                                    id="profit_margin"
                                                    value="20"
                                                    min="0"
                                                    step="0.01"
                                                >

                                            </div>



                                            <div class="pi-price-box">

                                                <label>
                                                    Unit Selling Price
                                                </label>

                                                <input
                                                    type="text"
                                                    name="unit_selling_price"
                                                    id="unit_selling_price"
                                                    readonly
                                                    value="0.00"
                                                >

                                            </div>


                                        </div>


                                    </div>


                                </div>


                            </section>



                            <!-- =================================================
                                 SECTION 06
                            ================================================== -->

                            <section class="pi-section">


                                <div class="pi-section-head">

                                    <div class="pi-section-number">
                                        06
                                    </div>

                                    <div>

                                        <h3 class="pi-section-title">
                                            Production State
                                        </h3>

                                        <p class="pi-section-subtitle">
                                            Define the current lifecycle state of this production.
                                        </p>

                                    </div>

                                </div>


                                <div class="pi-status-zone">


                                    <label class="pi-label">

                                        <i class="fa-solid fa-circle-nodes"></i>

                                        Production Status

                                    </label>


                                    <select
                                        name="production_status"
                                        id="production_status"
                                        class="pi-select"
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


                                    <p class="pi-status-description">

                                        When production is marked
                                        <strong>In Progress</strong>,
                                        profit margin is automatically
                                        suspended until the production
                                        reaches a completed state.

                                    </p>


                                </div>


                            </section>



                            <!-- =================================================
                                 FINAL RENDER
                            ================================================== -->

                            <div class="pi-submit-area">


                                <div class="pi-submit-info">


                                    <div class="pi-submit-info-icon">

                                        <i class="fa-solid fa-shield-halved"></i>

                                    </div>


                                    <div>

                                        <strong>
                                            Ready for Production Render
                                        </strong>

                                        <span>
                                            Verify your configuration before committing the production record.
                                        </span>

                                    </div>


                                </div>


                                <button
                                    type="submit"
                                    name="finalize_production"
                                    class="pi-render-button"
                                >

                                    <i class="fa-solid fa-cubes"></i>

                                    Render Production

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

const COMPANY_ABBR =
    <?= json_encode($company_abbr) ?>;


/* =========================================================
   PRODUCTION COUNTERS
========================================================= */

let rawMaterialsCount = 0;

let servicesCount = 0;


/* =========================================================
   SAFE NUMBER
========================================================= */

function safeNumber(value) {

    const number =
        parseFloat(value);

    return Number.isFinite(number)
        ? number
        : 0;

}


/* =========================================================
   RECALCULATE PRODUCTION INTELLIGENCE
========================================================= */

function recalcTotals() {


    let totalMaterials = 0;


    document
        .querySelectorAll('.rm_total')
        .forEach(el => {

            totalMaterials +=
                safeNumber(el.value);

        });


    const materialsDisplay =
        document.getElementById(
            'total_materials_cost'
        );


    if (materialsDisplay) {

        materialsDisplay.innerText =
            totalMaterials.toFixed(2);

    }



    let totalServices = 0;


    document
        .querySelectorAll('.srv_cost')
        .forEach(el => {

            totalServices +=
                safeNumber(el.value);

        });


    const servicesDisplay =
        document.getElementById(
            'total_services_cost'
        );


    if (servicesDisplay) {

        servicesDisplay.innerText =
            totalServices.toFixed(2);

    }



    const totalProduction =
        totalMaterials +
        totalServices;


    const productionDisplay =
        document.getElementById(
            'total_production_cost'
        );


    if (productionDisplay) {

        productionDisplay.innerText =
            totalProduction.toFixed(2);

    }



    const quantityInput =
        document.getElementById(
            'quantity_produced'
        );


    let qty =
        safeNumber(
            quantityInput
                ? quantityInput.value
                : 0
        );


    if (qty <= 0) {

        qty = 1;

    }



    const unitCost =
        totalProduction / qty;


    const unitCostInput =
        document.getElementById(
            'unit_cost'
        );


    if (unitCostInput) {

        unitCostInput.value =
            unitCost.toFixed(2);

    }



    const statusInput =
        document.getElementById(
            'production_status'
        );


    const status =
        statusInput
            ? statusInput.value
            : '';



    let margin =
        safeNumber(
            document.getElementById(
                'profit_margin'
            )?.value
        );


    /*
    |--------------------------------------------------------------------------
    | IN PROGRESS = NO PROFIT
    |--------------------------------------------------------------------------
    */

    if (status === 'In Progress') {

        margin = 0;

    }



    const unitSellingPrice =
        unitCost *
        (
            1 +
            (
                margin /
                100
            )
        );


    const sellingInput =
        document.getElementById(
            'unit_selling_price'
        );


    if (sellingInput) {

        sellingInput.value =
            unitSellingPrice.toFixed(2);

    }

}


/* =========================================================
   PRODUCTION STATUS INTELLIGENCE
========================================================= */

function handleProductionStatus() {


    const statusInput =
        document.getElementById(
            'production_status'
        );


    const marginInput =
        document.getElementById(
            'profit_margin'
        );


    if (!statusInput || !marginInput) {

        return;

    }


    const status =
        statusInput.value;


    if (status === 'In Progress') {


        marginInput.value =
            0;


        marginInput.readOnly =
            true;


        marginInput.style.backgroundColor =
            '#f1f5f9';


        marginInput.style.color =
            '#94a3b8';


        marginInput.style.cursor =
            'not-allowed';


    } else {


        marginInput.readOnly =
            false;


        marginInput.style.backgroundColor =
            '';


        marginInput.style.color =
            '';


        marginInput.style.cursor =
            '';

    }


    recalcTotals();

}


/* =========================================================
   AUTO-GENERATE BATCH NUMBER
========================================================= */

function generateBatchNumber() {


    const today =
        new Date();


    const yy =
        String(
            today.getFullYear()
        ).slice(-2);


    const mm =
        String(
            today.getMonth() + 1
        ).padStart(2, '0');


    const dd =
        String(
            today.getDate()
        ).padStart(2, '0');


    const random =
        Math.floor(
            Math.random() *
            900 +
            100
        );


    const batchNumber =
        `${COMPANY_ABBR}-B${yy}${mm}${dd}-${random}`;


    const batchInput =
        document.getElementById(
            'batch_number'
        );


    if (batchInput) {

        batchInput.value =
            batchNumber;

    }

}


/* =========================================================
   REINDEX RAW MATERIALS
========================================================= */

function reindexRawMaterials() {


    const tbody =
        document.getElementById(
            'raw_materials_body'
        );


    if (!tbody) {

        return;

    }


    Array
        .from(tbody.children)
        .forEach(
            (row, index) => {


                const name =
                    row.querySelector(
                        'input[name*="[name]"]'
                    );


                const qty =
                    row.querySelector(
                        'input[name*="[qty]"]'
                    );


                const unit =
                    row.querySelector(
                        'input[name*="[unitCost]"]'
                    );


                if (name) {

                    name.name =
                        `raw_materials[${index}][name]`;

                }


                if (qty) {

                    qty.name =
                        `raw_materials[${index}][qty]`;

                }


                if (unit) {

                    unit.name =
                        `raw_materials[${index}][unitCost]`;

                }

            }
        );

}


/* =========================================================
   ADD RAW MATERIAL
========================================================= */

function addRawMaterial() {


    const tbody =
        document.getElementById(
            'raw_materials_body'
        );


    if (!tbody) {

        return;

    }


    const index =
        tbody.children.length;


    const row =
        document.createElement('tr');


    row.innerHTML = `

        <td>

            <input
                type="text"
                name="raw_materials[${index}][name]"
                placeholder="Material name"
            >

        </td>


        <td>

            <input
                type="number"
                name="raw_materials[${index}][qty]"
                class="rm_qty"
                value="0"
                min="0"
                step="any"
            >

        </td>


        <td>

            <input
                type="number"
                name="raw_materials[${index}][unitCost]"
                class="rm_unit"
                value="0"
                min="0"
                step="any"
            >

        </td>


        <td>

            <input
                type="number"
                class="rm_total"
                value="0.00"
                readonly
            >

        </td>


        <td>

            <button
                type="button"
                class="pi-remove remove-btn"
            >

                <i class="fa-solid fa-trash-can"></i>

                Remove

            </button>

        </td>

    `;


    tbody.appendChild(row);


    const inputs =
        row.querySelectorAll(
            'input'
        );


    inputs.forEach(
        input => {


            input.addEventListener(
                'input',
                function() {


                    const currentRow =
                        input.closest('tr');


                    const qty =
                        safeNumber(
                            currentRow
                                .querySelector('.rm_qty')
                                .value
                        );


                    const unit =
                        safeNumber(
                            currentRow
                                .querySelector('.rm_unit')
                                .value
                        );


                    const total =
                        qty * unit;


                    currentRow
                        .querySelector('.rm_total')
                        .value =
                        total.toFixed(2);


                    recalcTotals();

                }
            );

        }
    );


    row
        .querySelector('.remove-btn')
        .addEventListener(
            'click',
            function() {


                row.remove();


                reindexRawMaterials();


                recalcTotals();

            }
        );


    rawMaterialsCount++;


    recalcTotals();

}


/* =========================================================
   REINDEX SERVICES
========================================================= */

function reindexServices() {


    const tbody =
        document.getElementById(
            'services_body'
        );


    if (!tbody) {

        return;

    }


    Array
        .from(tbody.children)
        .forEach(
            (row, index) => {


                const name =
                    row.querySelector(
                        'input[name*="[name]"]'
                    );


                const cost =
                    row.querySelector(
                        'input[name*="[cost]"]'
                    );


                if (name) {

                    name.name =
                        `services[${index}][name]`;

                }


                if (cost) {

                    cost.name =
                        `services[${index}][cost]`;

                }

            }
        );

}


/* =========================================================
   ADD SERVICE
========================================================= */

function addService() {


    const tbody =
        document.getElementById(
            'services_body'
        );


    if (!tbody) {

        return;

    }


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
                placeholder="Service name"
            >

        </td>


        <td>

            <input
                type="number"
                name="services[${index}][cost]"
                class="srv_cost"
                value="0"
                min="0"
                step="any"
            >

        </td>


        <td>

            <button
                type="button"
                class="pi-remove remove-btn"
            >

                <i class="fa-solid fa-trash-can"></i>

                Remove

            </button>

        </td>

    `;


    tbody.appendChild(row);


    row
        .querySelectorAll('input')
        .forEach(
            input => {

                input.addEventListener(
                    'input',
                    recalcTotals
                );

            }
        );


    row
        .querySelector('.remove-btn')
        .addEventListener(
            'click',
            function() {


                row.remove();


                reindexServices();


                recalcTotals();

            }
        );


    servicesCount++;


    recalcTotals();

}


/* =========================================================
   DOM READY
========================================================= */

window.addEventListener(
    'DOMContentLoaded',
    function() {


        generateBatchNumber();


        handleProductionStatus();


        recalcTotals();


        const rawMaterialButton =
            document.getElementById(
                'add_raw_material'
            );


        const serviceButton =
            document.getElementById(
                'add_service'
            );


        const marginInput =
            document.getElementById(
                'profit_margin'
            );


        const quantityInput =
            document.getElementById(
                'quantity_produced'
            );


        const statusInput =
            document.getElementById(
                'production_status'
            );


        if (rawMaterialButton) {

            rawMaterialButton.addEventListener(
                'click',
                addRawMaterial
            );

        }


        if (serviceButton) {

            serviceButton.addEventListener(
                'click',
                addService
            );

        }


        if (marginInput) {

            marginInput.addEventListener(
                'input',
                recalcTotals
            );

        }


        if (quantityInput) {

            quantityInput.addEventListener(
                'input',
                recalcTotals
            );

        }


        if (statusInput) {

            statusInput.addEventListener(
                'change',
                handleProductionStatus
            );

        }


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
                function(e) {


                    const status =
                        document.getElementById(
                            'production_status'
                        )?.value || '';


                    const margin =
                        document.getElementById(
                            'profit_margin'
                        )?.value.trim() || '';


                    const quantity =
                        safeNumber(
                            document.getElementById(
                                'quantity_produced'
                            )?.value
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | QUANTITY VALIDATION
                    |--------------------------------------------------------------------------
                    */

                    if (quantity <= 0) {

                        alert(
                            'Please enter a valid production quantity.'
                        );

                        document
                            .getElementById(
                                'quantity_produced'
                            )
                            ?.focus();

                        e.preventDefault();

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | STATUS VALIDATION
                    |--------------------------------------------------------------------------
                    */

                    if (!status) {

                        alert(
                            'Please select a production status.'
                        );

                        document
                            .getElementById(
                                'production_status'
                            )
                            ?.focus();

                        e.preventDefault();

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | FINISHED PRODUCTION REQUIRES MARGIN
                    |--------------------------------------------------------------------------
                    */

                    if (status === 'Finished') {


                        if (
                            margin === '' ||
                            parseFloat(margin) <= 0
                        ) {


                            alert(
                                'Please enter a valid profit margin before completing production.'
                            );


                            document
                                .getElementById(
                                    'profit_margin'
                                )
                                ?.focus();


                            e.preventDefault();


                            return;

                        }

                    }


                }
            );

        }


    }
);

</script>


</body>

</html>
