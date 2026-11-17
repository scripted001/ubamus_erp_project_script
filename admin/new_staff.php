
<?php


include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_id    = (int)($_SESSION['user_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| ACTIVE BRANCH
|--------------------------------------------------------------------------
| Staff created through this page automatically inherit the manager's
| active location. Never use a branch ID supplied by the browser.
*/
$location_id = (int)($_SESSION['location_id'] ?? 0);

if ($company_id <= 0 || $user_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Access denied. Your active company or branch session is missing. Please log in again.');
}

/*
|--------------------------------------------------------------------------
| VERIFY ACTIVE LOCATION BELONGS TO COMPANY
|--------------------------------------------------------------------------
*/
$locationStmt = $pdo->prepare("
    SELECT id, name
    FROM locations
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");
$locationStmt->execute([$location_id, $company_id]);
$activeLocation = $locationStmt->fetch(PDO::FETCH_ASSOC);

if (!$activeLocation) {
    http_response_code(403);
    exit('Access denied. Your active branch does not belong to this company.');
}

$location_name = $activeLocation['name'];

/*
|--------------------------------------------------------------------------
| COMPANY INDUSTRY
|--------------------------------------------------------------------------
*/
$industryStmt = $pdo->prepare("
    SELECT company_industry
    FROM companies
    WHERE id = ?
    LIMIT 1
");
$industryStmt->execute([$company_id]);

$company = $industryStmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    http_response_code(403);
    exit('Company record could not be verified.');
}

$company_industry = $company['company_industry'] ?? null;

/*
|--------------------------------------------------------------------------
| INDUSTRIES ELIGIBLE FOR PRODUCTION MANAGER
|--------------------------------------------------------------------------
*/
$productionIndustries = [
    "Production Company",
    "Warehousing / Supermarkets"
];

/*
|--------------------------------------------------------------------------
| COMPANY NAME
|--------------------------------------------------------------------------
*/
$displayCompanyName = $company_name ?? 'Company';

function staffEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="content-type" content="text/html;charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">

<title><?= staffEscape($displayCompanyName); ?> | Create Staff</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">

<style>
:root {
    --staff-bg:#f5f7fc;
    --staff-surface:rgba(255,255,255,.72);
    --staff-border:rgba(255,255,255,.94);
    --staff-border-soft:rgba(119,132,157,.16);
    --staff-text:#182235;
    --staff-muted:#78859a;
    --staff-primary:#6954ff;
    --staff-primary-dark:#4937d1;
    --staff-cyan:#08a9d4;
    --staff-green:#25b96f;
    --staff-red:#e85b68;
    --staff-shadow:0 22px 60px rgba(47,61,91,.10);
    --staff-shadow-small:0 10px 30px rgba(47,61,91,.08);
}

.staff-page {
    position:relative;
    min-height:100%;
    overflow:hidden;
    background:
        radial-gradient(circle at 5% 8%,rgba(105,84,255,.13),transparent 26%),
        radial-gradient(circle at 95% 12%,rgba(8,169,212,.11),transparent 25%),
        radial-gradient(circle at 50% 105%,rgba(105,84,255,.08),transparent 32%),
        linear-gradient(135deg,#f7f9fd 0%,#f1f4fa 100%);
}

.staff-glass {
    background:var(--staff-surface);
    border:1px solid var(--staff-border);
    backdrop-filter:blur(25px);
    -webkit-backdrop-filter:blur(25px);
    box-shadow:var(--staff-shadow);
}

/* HERO */
.staff-hero {
    position:relative;
    overflow:hidden;
    border-radius:30px;
    padding:30px;
    margin-bottom:20px;
}
.staff-hero::before {
    content:"";
    position:absolute;
    width:260px;
    height:260px;
    right:-100px;
    top:-120px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(105,84,255,.18),transparent);
}
.staff-hero::after {
    content:"";
    position:absolute;
    width:190px;
    height:190px;
    left:45%;
    bottom:-140px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(8,169,212,.13),transparent 70%);
}
.staff-hero-content {
    position:relative;
    z-index:2;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:25px;
}
.staff-hero-left {min-width:0}
.staff-eyebrow {
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:7px 12px;
    border-radius:999px;
    background:rgba(105,84,255,.08);
    border:1px solid rgba(105,84,255,.13);
    color:var(--staff-primary);
    font-size:10px;
    font-weight:900;
    letter-spacing:.14em;
    text-transform:uppercase;
}
.staff-live-dot {
    width:7px;
    height:7px;
    border-radius:50%;
    background:var(--staff-green);
    box-shadow:0 0 0 4px rgba(37,185,111,.10),0 0 15px rgba(37,185,111,.50);
}
.staff-title {
    margin-top:13px;
    color:var(--staff-text);
    font-size:clamp(26px,3vw,39px);
    font-weight:300;
    line-height:1.05;
    letter-spacing:-.045em;
}
.staff-subtitle {
    margin-top:9px;
    max-width:680px;
    color:var(--staff-muted);
    font-size:13px;
    line-height:1.7;
}
.staff-hero-badge {
    flex-shrink:0;
    min-width:175px;
    padding:18px;
    border-radius:20px;
    background:linear-gradient(135deg,rgba(105,84,255,.10),rgba(8,169,212,.07));
    border:1px solid rgba(105,84,255,.13);
}
.staff-hero-badge-label {
    color:var(--staff-muted);
    font-size:9px;
    font-weight:900;
    letter-spacing:.11em;
    text-transform:uppercase;
}
.staff-hero-badge-value {
    margin-top:5px;
    color:var(--staff-text);
    font-size:15px;
    font-weight:900;
}

/* FORM */
.staff-form-shell {
    border-radius:28px;
    padding:20px;
}
.staff-section {
    padding:22px;
    border-radius:22px;
    background:rgba(255,255,255,.53);
    border:1px solid rgba(255,255,255,.82);
    box-shadow:var(--staff-shadow-small);
}
.staff-section + .staff-section {margin-top:16px}
.staff-section-header {
    display:flex;
    align-items:flex-start;
    gap:13px;
    margin-bottom:20px;
}
.staff-section-icon {
    width:42px;
    height:42px;
    flex-shrink:0;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    color:var(--staff-primary);
    background:rgba(105,84,255,.09);
    font-size:15px;
}
.staff-section-title {
    color:var(--staff-text);
    font-size:15px;
    font-weight:900;
    letter-spacing:-.02em;
}
.staff-section-description {
    margin-top:3px;
    color:var(--staff-muted);
    font-size:10px;
    line-height:1.6;
}
.staff-form-grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:15px;
}
.staff-field {min-width:0}
.staff-field-full {grid-column:1/-1}
.staff-label {
    display:flex;
    align-items:center;
    gap:6px;
    margin-bottom:7px;
    color:#718096;
    font-size:10px;
    font-weight:900;
    letter-spacing:.07em;
    text-transform:uppercase;
}
.staff-label i {color:var(--staff-primary);font-size:10px}
.staff-required {color:var(--staff-red);font-size:9px}
.staff-input {
    width:100%;
    min-width:0;
    height:46px;
    padding:0 14px;
    border-radius:14px;
    border:1px solid var(--staff-border-soft);
    background:rgba(255,255,255,.72);
    color:var(--staff-text);
    font-size:12px;
    font-weight:600;
    outline:none;
    transition:border .2s,box-shadow .2s,background .2s,transform .2s;
}
.staff-input:hover {border-color:rgba(105,84,255,.28)}
.staff-input:focus {
    border-color:rgba(105,84,255,.55);
    background:rgba(255,255,255,.90);
    box-shadow:0 0 0 4px rgba(105,84,255,.07),0 8px 22px rgba(105,84,255,.07);
}
.staff-input::placeholder {color:#a1aaba}
select.staff-input {cursor:pointer}
.staff-input-wrap {position:relative}
.staff-input-wrap>i {
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#929db0;
    font-size:12px;
    pointer-events:none;
}
.staff-input-wrap .staff-input {padding-left:38px}

/* READ-ONLY BRANCH */
.staff-branch-display {
    min-height:46px;
    display:flex;
    align-items:center;
    gap:11px;
    padding:8px 13px;
    border-radius:14px;
    border:1px solid rgba(37,185,111,.20);
    background:rgba(37,185,111,.055);
}
.staff-branch-icon {
    width:30px;
    height:30px;
    flex-shrink:0;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    color:#159b59;
    background:rgba(37,185,111,.12);
}
.staff-branch-name {
    color:var(--staff-text);
    font-size:12px;
    font-weight:800;
}
.staff-branch-status {
    margin-left:auto;
    color:#159b59;
    font-size:9px;
    font-weight:900;
    letter-spacing:.06em;
    text-transform:uppercase;
    white-space:nowrap;
}
.staff-branch-note {
    margin-top:7px;
    color:var(--staff-muted);
    font-size:10px;
    line-height:1.5;
}

/* FILE INPUT */
.staff-file {
    width:100%;
    min-height:46px;
    padding:8px 10px;
    border-radius:14px;
    border:1px dashed rgba(105,84,255,.25);
    background:rgba(105,84,255,.035);
    color:var(--staff-text);
    font-size:11px;
    cursor:pointer;
}
.staff-file::file-selector-button {
    border:0;
    padding:8px 13px;
    margin-right:10px;
    border-radius:10px;
    background:rgba(105,84,255,.10);
    color:var(--staff-primary);
    font-size:10px;
    font-weight:900;
    cursor:pointer;
}

/* PROFILE */
.staff-profile-layout {
    display:grid;
    grid-template-columns:125px minmax(0,1fr);
    gap:20px;
    align-items:center;
}
.staff-profile-preview {
    width:125px;
    height:125px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    border-radius:25px;
    background:linear-gradient(135deg,rgba(105,84,255,.10),rgba(8,169,212,.08));
    border:1px solid rgba(105,84,255,.13);
    color:var(--staff-primary);
    font-size:30px;
}
.staff-profile-preview img {
    width:100%;
    height:100%;
    object-fit:cover;
    display:none;
}
.staff-profile-note {
    margin-top:7px;
    color:var(--staff-muted);
    font-size:10px;
    line-height:1.6;
}

/* REFEREES */
.staff-referee {
    padding:16px;
    border-radius:18px;
    background:rgba(248,249,253,.65);
    border:1px solid rgba(117,132,155,.09);
}
.staff-referee + .staff-referee {margin-top:12px}
.staff-referee-header {
    display:flex;
    align-items:center;
    gap:9px;
    margin-bottom:13px;
}
.staff-referee-number {
    width:30px;
    height:30px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    background:rgba(105,84,255,.09);
    color:var(--staff-primary);
    font-size:11px;
    font-weight:900;
}
.staff-referee-title {
    color:var(--staff-text);
    font-size:12px;
    font-weight:900;
}

/* ACTIONS */
.staff-actions {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin-top:17px;
    padding:18px 20px;
    border-radius:20px;
    background:rgba(255,255,255,.60);
    border:1px solid rgba(255,255,255,.85);
}
.staff-actions-note {
    display:flex;
    align-items:center;
    gap:8px;
    color:var(--staff-muted);
    font-size:10px;
    font-weight:700;
}
.staff-actions-note i {color:var(--staff-green)}
.staff-action-buttons {display:flex;align-items:center;gap:9px}
.staff-button {
    min-height:44px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:0 17px;
    border-radius:13px;
    font-size:11px;
    font-weight:900;
    text-decoration:none;
    cursor:pointer;
    transition:transform .2s,box-shadow .2s,background .2s;
}
.staff-button:hover {transform:translateY(-2px)}
.staff-button-primary {
    border:0;
    color:#fff!important;
    background:linear-gradient(135deg,var(--staff-primary),var(--staff-primary-dark));
    box-shadow:0 10px 24px rgba(105,84,255,.20);
}
.staff-button-primary:hover {box-shadow:0 14px 30px rgba(105,84,255,.28)}
.staff-button-secondary {
    color:#657186;
    background:rgba(255,255,255,.80);
    border:1px solid rgba(117,132,155,.18);
}
.staff-button-secondary:hover {background:#fff;color:var(--staff-text)}

/* RESPONSIVE */
@media(max-width:767px) {
    .staff-hero {padding:21px;border-radius:23px}
    .staff-hero-content {align-items:flex-start;flex-direction:column}
    .staff-hero-badge {width:100%;min-width:0}
    .staff-form-shell {padding:12px;border-radius:22px}
    .staff-section {padding:16px;border-radius:19px}
    .staff-section-header {margin-bottom:16px}
    .staff-form-grid {grid-template-columns:1fr;gap:12px}
    .staff-field-full {grid-column:auto}
    .staff-profile-layout {grid-template-columns:1fr;text-align:center}
    .staff-profile-preview {margin:0 auto}
    .staff-profile-content {text-align:left}
    .staff-actions {align-items:stretch;flex-direction:column;padding:15px}
    .staff-action-buttons {width:100%;display:grid;grid-template-columns:1fr 1fr}
    .staff-button {width:100%}
}
@media(max-width:420px) {
    .staff-title {font-size:26px}
    .staff-subtitle {font-size:11px}
    .staff-section {padding:13px}
    .staff-section-title {font-size:14px}
    .staff-input {height:44px;font-size:11px}
    .staff-action-buttons {grid-template-columns:1fr}
    .staff-profile-preview {width:105px;height:105px}
    .staff-branch-status {font-size:8px}
}

/* DARK MODE */
.dark .staff-page {
    background:
        radial-gradient(circle at 5% 8%,rgba(105,84,255,.13),transparent 26%),
        radial-gradient(circle at 95% 12%,rgba(8,169,212,.09),transparent 25%),
        #111827;
}
.dark .staff-glass {
    background:rgba(17,24,39,.70);
    border-color:rgba(255,255,255,.08);
}
.dark .staff-section {
    background:rgba(17,24,39,.50);
    border-color:rgba(255,255,255,.07);
}
.dark .staff-title,
.dark .staff-section-title,
.dark .staff-referee-title,
.dark .staff-hero-badge-value,
.dark .staff-branch-name {color:#f8fafc}
.dark .staff-subtitle,
.dark .staff-section-description,
.dark .staff-profile-note,
.dark .staff-actions-note,
.dark .staff-branch-note {color:#94a3b8}
.dark .staff-input {
    background:rgba(15,23,42,.70);
    border-color:rgba(148,163,184,.18);
    color:#f8fafc;
}
.dark .staff-input:focus {background:rgba(15,23,42,.90)}
.dark .staff-referee {
    background:rgba(15,23,42,.55);
    border-color:rgba(148,163,184,.10);
}
.dark .staff-actions {
    background:rgba(15,23,42,.55);
    border-color:rgba(255,255,255,.07);
}
.dark .staff-button-secondary {
    color:#cbd5e1;
    background:rgba(15,23,42,.70);
    border-color:rgba(148,163,184,.18);
}
.dark .staff-branch-display {
    background:rgba(37,185,111,.07);
    border-color:rgba(37,185,111,.18);
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

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>

            <div class="staff-page">

                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                    <!-- HERO -->
                    <section class="staff-glass staff-hero">
                        <div class="staff-hero-content">

                            <div class="staff-hero-left">

                                <div class="staff-eyebrow">
                                    <span class="staff-live-dot"></span>
                                    Staff Management
                                </div>

                                <h1 class="staff-title">Create New Staff</h1>

                                <p class="staff-subtitle">
                                    Register a new member of your workforce,
                                    assign their operational role and capture
                                    the required employment reference information.
                                    Every new staff member will automatically
                                    belong to your active branch.
                                </p>

                            </div>

                            <div class="staff-hero-badge">
                                <div class="staff-hero-badge-label">
                                    Registration Status
                                </div>
                                <div class="staff-hero-badge-value">
                                    <i class="fa-solid fa-user-plus"></i>
                                    New Staff Profile
                                </div>
                            </div>

                        </div>
                    </section>

                    <!-- FORM -->
                    <section class="staff-glass staff-form-shell">

                        <form
                            method="POST"
                            action="create_staff.php"
                            enctype="multipart/form-data"
                            autocomplete="on"
                        >

                            <!-- PERSONAL INFORMATION -->
                            <div class="staff-section">

                                <div class="staff-section-header">

                                    <div class="staff-section-icon">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                    <div>
                                        <div class="staff-section-title">
                                            Personal Information
                                        </div>
                                        <div class="staff-section-description">
                                            Enter the staff member's basic identity
                                            and contact details.
                                        </div>
                                    </div>

                                </div>

                                <div class="staff-form-grid">

                                    <div class="staff-field">
                                        <label class="staff-label" for="firstname">
                                            <i class="fa-solid fa-id-card"></i>
                                            First Name
                                            <span class="staff-required">*</span>
                                        </label>

                                        <div class="staff-input-wrap">
                                            <i class="fa-solid fa-user"></i>
                                            <input
                                                type="text"
                                                id="firstname"
                                                name="firstname"
                                                placeholder="Enter first name"
                                                class="staff-input"
                                                autocomplete="given-name"
                                                maxlength="100"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="staff-field">
                                        <label class="staff-label" for="lastname">
                                            <i class="fa-solid fa-id-card"></i>
                                            Last Name
                                            <span class="staff-required">*</span>
                                        </label>

                                        <div class="staff-input-wrap">
                                            <i class="fa-solid fa-user"></i>
                                            <input
                                                type="text"
                                                id="lastname"
                                                name="lastname"
                                                placeholder="Enter last name"
                                                class="staff-input"
                                                autocomplete="family-name"
                                                maxlength="100"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="staff-field">
                                        <label class="staff-label" for="email">
                                            <i class="fa-solid fa-envelope"></i>
                                            Email Address
                                            <span class="staff-required">*</span>
                                        </label>

                                        <div class="staff-input-wrap">
                                            <i class="fa-solid fa-envelope"></i>
                                            <input
                                                type="email"
                                                id="email"
                                                name="email"
                                                placeholder="example@mail.com"
                                                class="staff-input"
                                                autocomplete="email"
                                                maxlength="190"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="staff-field">
                                        <label class="staff-label" for="phone">
                                            <i class="fa-solid fa-phone"></i>
                                            Mobile Number
                                            <span class="staff-required">*</span>
                                        </label>

                                        <div class="staff-input-wrap">
                                            <i class="fa-solid fa-phone"></i>
                                            <input
                                                type="tel"
                                                id="phone"
                                                name="phone"
                                                placeholder="Enter mobile number"
                                                class="staff-input"
                                                autocomplete="tel"
                                                inputmode="tel"
                                                maxlength="30"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="staff-field staff-field-full">
                                        <label class="staff-label" for="address">
                                            <i class="fa-solid fa-location-dot"></i>
                                            Residential / Contact Address
                                            <span class="staff-required">*</span>
                                        </label>

                                        <div class="staff-input-wrap">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <input
                                                type="text"
                                                id="address"
                                                name="address"
                                                placeholder="Enter staff address"
                                                class="staff-input"
                                                autocomplete="street-address"
                                                maxlength="500"
                                                required
                                            >
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ROLE AND BRANCH -->
                            <div class="staff-section">

                                <div class="staff-section-header">

                                    <div class="staff-section-icon">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </div>

                                    <div>
                                        <div class="staff-section-title">
                                            Role & Operational Assignment
                                        </div>
                                        <div class="staff-section-description">
                                            Define the staff member's responsibilities
                                            and confirm the branch they will operate from.
                                        </div>
                                    </div>

                                </div>

                                <div class="staff-form-grid">

                                    <div class="staff-field">
                                        <label class="staff-label" for="role">
                                            <i class="fa-solid fa-user-shield"></i>
                                            Staff Role
                                            <span class="staff-required">*</span>
                                        </label>

                                        <div class="staff-input-wrap">
                                            <i class="fa-solid fa-user-shield"></i>

                                            <select
                                                id="role"
                                                name="role"
                                                class="staff-input"
                                                required
                                            >
                                                <option value="">Select Staff Role</option>
                                                <option value="Cashier">Cashier</option>
                                                <option value="Branch Manager">Branch Manager</option>
                                                <option value="Head of Administrator">Head of Administrator</option>
                                                <option value="Operation Manager">Operation Manager</option>

                                                <?php if (
                                                    in_array(
                                                        $company_industry,
                                                        $productionIndustries,
                                                        true
                                                    )
                                                ): ?>
                                                    <option value="Production Manager">
                                                        Production Manager
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- ACTIVE BRANCH: READ ONLY -->
                                    <div class="staff-field">

                                        <label class="staff-label">
                                            <i class="fa-solid fa-map-location-dot"></i>
                                            Assigned Branch
                                        </label>

                                        <div class="staff-branch-display">
                                            <div class="staff-branch-icon">
                                                <i class="fa-solid fa-building"></i>
                                            </div>

                                            <div class="staff-branch-name">
                                                <?= staffEscape($location_name); ?>
                                            </div>

                                            <div class="staff-branch-status">
                                                <i class="fa-solid fa-lock"></i>
                                                Active
                                            </div>
                                        </div>

                                        <div class="staff-branch-note">
                                            This staff member will automatically
                                            be assigned to your currently active branch.
                                        </div>

                                        <!-- Informational only; backend must use session location_id. -->
                                        <input
                                            type="hidden"
                                            name="location_id"
                                            value="<?= (int)$location_id; ?>"
                                        >

                                    </div>

                                </div>
                            </div>

                            <!-- STAFF PROFILE -->
                            <div class="staff-section">

                                <div class="staff-section-header">

                                    <div class="staff-section-icon">
                                        <i class="fa-solid fa-camera"></i>
                                    </div>

                                    <div>
                                        <div class="staff-section-title">
                                            Staff Profile
                                        </div>
                                        <div class="staff-section-description">
                                            Upload a clear profile picture for
                                            identification within the system.
                                        </div>
                                    </div>

                                </div>

                                <div class="staff-profile-layout">

                                    <div class="staff-profile-preview" id="profilePreview">
                                        <i
                                            class="fa-solid fa-user"
                                            id="profilePlaceholder"
                                        ></i>

                                        <img
                                            id="profilePreviewImage"
                                            src=""
                                            alt="Staff profile preview"
                                        >
                                    </div>

                                    <div class="staff-profile-content">

                                        <label class="staff-label" for="profile">
                                            <i class="fa-solid fa-image"></i>
                                            Profile Picture
                                            <span class="staff-required">*</span>
                                        </label>

                                        <input
                                            type="file"
                                            id="profile"
                                            name="profile"
                                            accept="image/jpeg,image/png,image/webp"
                                            class="staff-file"
                                            required
                                        >

                                        <div class="staff-profile-note">
                                            <i class="fa-solid fa-circle-info"></i>
                                            Select a JPG, PNG or WebP image.
                                            The preview will appear automatically
                                            before submission.
                                        </div>

                                    </div>

                                </div>
                            </div>

                            <!-- EMPLOYMENT REFEREES -->
                            <div class="staff-section">

                                <div class="staff-section-header">

                                    <div class="staff-section-icon">
                                        <i class="fa-solid fa-users"></i>
                                    </div>

                                    <div>
                                        <div class="staff-section-title">
                                            Employment Referees
                                        </div>
                                        <div class="staff-section-description">
                                            Provide two referee contacts for
                                            staff verification and records.
                                        </div>
                                    </div>

                                </div>

                                <!-- REFEREE 1 -->
                                <div class="staff-referee">

                                    <div class="staff-referee-header">
                                        <div class="staff-referee-number">01</div>
                                        <div class="staff-referee-title">
                                            Primary Referee
                                        </div>
                                    </div>

                                    <div class="staff-form-grid">

                                        <div class="staff-field">
                                            <label class="staff-label" for="refree1name">
                                                <i class="fa-solid fa-user"></i>
                                                Referee Name
                                                <span class="staff-required">*</span>
                                            </label>

                                            <div class="staff-input-wrap">
                                                <i class="fa-solid fa-user"></i>
                                                <input
                                                    type="text"
                                                    id="refree1name"
                                                    name="refree1name"
                                                    placeholder="Enter referee name"
                                                    class="staff-input"
                                                    maxlength="150"
                                                    required
                                                >
                                            </div>
                                        </div>

                                        <div class="staff-field">
                                            <label class="staff-label" for="refree1phone">
                                                <i class="fa-solid fa-phone"></i>
                                                Referee Phone
                                                <span class="staff-required">*</span>
                                            </label>

                                            <div class="staff-input-wrap">
                                                <i class="fa-solid fa-phone"></i>
                                                <input
                                                    type="tel"
                                                    id="refree1phone"
                                                    name="refree1phone"
                                                    placeholder="Enter referee phone"
                                                    class="staff-input"
                                                    inputmode="tel"
                                                    maxlength="30"
                                                    required
                                                >
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- REFEREE 2 -->
                                <div class="staff-referee">

                                    <div class="staff-referee-header">
                                        <div class="staff-referee-number">02</div>
                                        <div class="staff-referee-title">
                                            Secondary Referee
                                        </div>
                                    </div>

                                    <div class="staff-form-grid">

                                        <div class="staff-field">
                                            <label class="staff-label" for="refree2name">
                                                <i class="fa-solid fa-user"></i>
                                                Referee Name
                                                <span class="staff-required">*</span>
                                            </label>

                                            <div class="staff-input-wrap">
                                                <i class="fa-solid fa-user"></i>
                                                <input
                                                    type="text"
                                                    id="refree2name"
                                                    name="refree2name"
                                                    placeholder="Enter referee name"
                                                    class="staff-input"
                                                    maxlength="150"
                                                    required
                                                >
                                            </div>
                                        </div>

                                        <div class="staff-field">
                                            <label class="staff-label" for="refree2phone">
                                                <i class="fa-solid fa-phone"></i>
                                                Referee Phone
                                                <span class="staff-required">*</span>
                                            </label>

                                            <div class="staff-input-wrap">
                                                <i class="fa-solid fa-phone"></i>
                                                <input
                                                    type="tel"
                                                    id="refree2phone"
                                                    name="refree2phone"
                                                    placeholder="Enter referee phone"
                                                    class="staff-input"
                                                    inputmode="tel"
                                                    maxlength="30"
                                                    required
                                                >
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <!-- ACTIONS -->
                            <div class="staff-actions">

                                <div class="staff-actions-note">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    All required staff information must be
                                    completed before registration.
                                </div>

                                <div class="staff-action-buttons">

                                    <a
                                        href="index.php"
                                        class="staff-button staff-button-secondary"
                                    >
                                        <i class="fa-solid fa-arrow-left"></i>
                                        Cancel
                                    </a>

                                    <button
                                        type="submit"
                                        class="staff-button staff-button-primary"
                                    >
                                        <i class="fa-solid fa-user-plus"></i>
                                        Create Staff
                                    </button>

                                </div>
                            </div>

                        </form>

                    </section>

                </div>
            </div>

        </main>

    </div>
</div>

<?php include('footer.php'); ?>

<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script defer src="bundle.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const profileInput = document.getElementById('profile');
    const profileImage = document.getElementById('profilePreviewImage');
    const profilePlaceholder = document.getElementById('profilePlaceholder');

    if (!profileInput || !profileImage || !profilePlaceholder) {
        return;
    }

    let currentObjectUrl = null;

    profileInput.addEventListener('change', function () {

        const file = this.files && this.files[0];

        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        }

        if (!file) {
            profileImage.src = '';
            profileImage.style.display = 'none';
            profilePlaceholder.style.display = 'block';
            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {
            alert('Please select a JPG, PNG or WebP image.');
            this.value = '';
            profileImage.src = '';
            profileImage.style.display = 'none';
            profilePlaceholder.style.display = 'block';
            return;
        }

        currentObjectUrl = URL.createObjectURL(file);
        profileImage.src = currentObjectUrl;
        profileImage.style.display = 'block';
        profilePlaceholder.style.display = 'none';
    });

    window.addEventListener('beforeunload', function () {
        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
        }
    });

});
</script>

</body>
</html>
