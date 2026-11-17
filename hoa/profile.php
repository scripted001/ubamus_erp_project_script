<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    echo "<script>
        alert('Invalid user session.');
        window.location.href='index.php';
    </script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH USER ACCOUNT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        email,
        password,
        role,
        company_id,
        location_id,
        status,
        created_at,
        changed_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    echo "<script>
        alert('User account could not be found.');
        window.location.href='index.php';
    </script>";
    exit;
}

$username   = $userData['username'] ?? 'Not Found';
$email      = $userData['email'] ?? 'Not Found';
$role       = $userData['role'] ?? 'Not Found';
$company_id = (int)($userData['company_id'] ?? 0);
$location_id = (int)($userData['location_id'] ?? 0);
$status     = $userData['status'] ?? 'Not Found';
$created_at = $userData['created_at'] ?? 'Not Found';
$changed_at = $userData['changed_at'] ?? 'Not Found';

/*
|--------------------------------------------------------------------------
| FETCH STAFF INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        company_id,
        location_id,
        firstname,
        lastname,
        email,
        phone,
        role,
        address,
        profile,
        refree1name
    FROM staffs
    WHERE email = ?
    LIMIT 1
");

$stmt->execute([$email]);

$staffdata = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| STAFF FALLBACKS
|--------------------------------------------------------------------------
*/

$staff_company_id = (int)($staffdata['company_id'] ?? $company_id);
$location_id      = (int)($staffdata['location_id'] ?? $location_id);

$firstname  = $staffdata['firstname'] ?? 'Not Found';
$lastname   = $staffdata['lastname'] ?? 'Not Found';
$staffemail = $staffdata['email'] ?? $email;
$phone      = $staffdata['phone'] ?? 'Not Found';
$staffrole  = $staffdata['role'] ?? $role;
$address    = $staffdata['address'] ?? 'Not Found';
$profile    = $staffdata['profile'] ?? '';
$refree1name = $staffdata['refree1name'] ?? 'Not Found';

/*
|--------------------------------------------------------------------------
| VALIDATE COMPANY
|--------------------------------------------------------------------------
*/

if ($staff_company_id <= 0) {
    echo "<script>
        alert('No company is associated with this user.');
        window.location.href='index.php';
    </script>";
    exit;
}

$company_id = $staff_company_id;

/*
|--------------------------------------------------------------------------
| FETCH LOCATION
|--------------------------------------------------------------------------
*/

$location_name = 'Not Assigned';
$location_city = '';
$location_state = '';

if ($location_id > 0) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            city,
            state,
            type
        FROM locations
        WHERE id = ?
        AND company_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $location_id,
        $company_id
    ]);

    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($location) {

        $location_name  = $location['name'] ?? 'Not Assigned';
        $location_city  = $location['city'] ?? '';
        $location_state = $location['state'] ?? '';
    }
}

/*
|--------------------------------------------------------------------------
| DISPLAY HELPERS
|--------------------------------------------------------------------------
*/

$fullName = trim($firstname . ' ' . $lastname);

if ($fullName === '') {
    $fullName = $username;
}

/*
|--------------------------------------------------------------------------
| PROFILE IMAGE
|--------------------------------------------------------------------------
*/

$profileImage = '';

if (!empty($profile)) {
    $profileImage = '../' . ltrim($profile, '/');
}

/*
|--------------------------------------------------------------------------
| INITIALS
|--------------------------------------------------------------------------
*/

$initials = '';

if (!empty($firstname)) {
    $initials .= strtoupper(substr($firstname, 0, 1));
}

if (!empty($lastname)) {
    $initials .= strtoupper(substr($lastname, 0, 1));
}

if ($initials === '') {
    $initials = strtoupper(substr($username, 0, 2));
}

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$statusLower = strtolower((string)$status);

$isActive = in_array(
    $statusLower,
    ['active', '1', 'enabled', 'approved'],
    true
);

/*
|--------------------------------------------------------------------------
| SAFE DISPLAY VALUES
|--------------------------------------------------------------------------
*/

$safeFullName    = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
$safeFirstname   = htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8');
$safeLastname    = htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8');
$safeEmail       = htmlspecialchars($staffemail, ENT_QUOTES, 'UTF-8');
$safePhone       = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$safeAddress     = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
$safeRole        = htmlspecialchars($staffrole, ENT_QUOTES, 'UTF-8');
$safeCompany     = htmlspecialchars($company_name ?? 'Company', ENT_QUOTES, 'UTF-8');
$safeLocation    = htmlspecialchars($location_name, ENT_QUOTES, 'UTF-8');
$safeUsername    = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$safeStatus      = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
$safeInitials    = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
$safeProfile     = htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8');

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
    <?= $safeCompany; ?> | Staff Profile
</title>

<link
    rel="stylesheet"
    href="../fontawesome/css/all.css"
>

<link
    href="../company/style.css"
    rel="stylesheet"
>

<link
    href="../company/modal.css"
    rel="stylesheet"
>


<style>

/* ============================================================
   STAFF PROFILE — FUTURISTIC GLASS INTERFACE
   ============================================================ */

:root {

    --profile-purple: #6839eb;
    --profile-purple-dark: #5226cf;

    --profile-blue: #3b82f6;
    --profile-cyan: #06b6d4;

    --profile-green: #10b981;

    --profile-text: #111827;
    --profile-heading: #0f172a;
    --profile-muted: #64748b;
    --profile-soft: #94a3b8;

    --profile-border: rgba(15, 23, 42, 0.09);

    --profile-glass:
        rgba(255, 255, 255, 0.72);

    --profile-glass-strong:
        rgba(255, 255, 255, 0.86);

    --profile-shadow:
        0 25px 80px rgba(15, 23, 42, 0.09);

    --profile-radius: 26px;
}

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
            circle at 8% 5%,
            rgba(104, 57, 235, 0.10),
            transparent 28%
        ),

        radial-gradient(
            circle at 92% 12%,
            rgba(59, 130, 246, 0.08),
            transparent 28%
        ),

        radial-gradient(
            circle at 50% 100%,
            rgba(6, 182, 212, 0.055),
            transparent 35%
        ),

        #f8fafc;

    color: var(--profile-text);
}


/* ============================================================
   PAGE
   ============================================================ */

.profile-page {

    width: 100%;
    max-width: 1500px;

    margin: 0 auto;

    padding:
        12px
        0
        45px;
}


/* ============================================================
   BREADCRUMB
   ============================================================ */

.profile-breadcrumb {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 18px;

    padding:
        5px
        3px;
}

.profile-breadcrumb-left {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 8px;
}

.profile-breadcrumb a {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #64748b;

    text-decoration: none;

    font-size: 0.78rem;

    transition: 0.2s ease;
}

.profile-breadcrumb a:hover {
    color: var(--profile-purple);
}

.profile-breadcrumb-current {

    color: #1e293b;

    font-size: 0.78rem;

    font-weight: 700;
}

.breadcrumb-chevron {

    color: #cbd5e1;

    font-size: 9px;
}


/* ============================================================
   MAIN GLASS CONTAINER
   ============================================================ */

.profile-shell {

    position: relative;

    overflow: hidden;

    border:
        1px solid
        rgba(255, 255, 255, 0.90);

    border-radius: var(--profile-radius);

    background:
        var(--profile-glass);

    backdrop-filter:
        blur(28px);

    -webkit-backdrop-filter:
        blur(28px);

    box-shadow:
        var(--profile-shadow);

}


/* ============================================================
   DECORATIVE ORBS
   ============================================================ */

.profile-shell::before {

    content: "";

    position: absolute;

    width: 340px;
    height: 340px;

    top: -210px;
    right: -100px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(104, 57, 235, 0.15),
            transparent 70%
        );

    pointer-events: none;
}

.profile-shell::after {

    content: "";

    position: absolute;

    width: 260px;
    height: 260px;

    bottom: -170px;
    left: -100px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(6, 182, 212, 0.09),
            transparent 70%
        );

    pointer-events: none;
}


/* ============================================================
   HERO
   ============================================================ */

.profile-hero {

    position: relative;

    z-index: 2;

    padding:
        34px;

    border-bottom:
        1px solid
        rgba(15, 23, 42, 0.07);
}

.profile-hero-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        auto;

    align-items: center;

    gap: 30px;
}


/* ============================================================
   PROFILE IDENTITY
   ============================================================ */

.profile-identity {

    display: flex;

    align-items: center;

    gap: 22px;

    min-width: 0;
}

.profile-avatar {

    position: relative;

    width: 104px;
    height: 104px;

    flex: 0 0 104px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    border-radius: 28px;

    background:
        linear-gradient(
            135deg,
            rgba(104, 57, 235, 0.17),
            rgba(59, 130, 246, 0.10)
        );

    border:
        1px solid
        rgba(104, 57, 235, 0.18);

    box-shadow:
        0 18px 45px
        rgba(104, 57, 235, 0.14);
}

.profile-avatar img {

    width: 100%;
    height: 100%;

    object-fit: cover;
}

.profile-avatar-initials {

    font-size: 1.8rem;

    font-weight: 800;

    color: var(--profile-purple);

    letter-spacing: -0.05em;
}

.profile-online {

    position: absolute;

    right: 6px;
    bottom: 6px;

    width: 15px;
    height: 15px;

    border-radius: 50%;

    background: var(--profile-green);

    border:
        3px solid
        #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(16, 185, 129, 0.13);
}


/* ============================================================
   IDENTITY TEXT
   ============================================================ */

.profile-identity-content {

    min-width: 0;
}

.profile-eyebrow {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 7px;

    color: var(--profile-purple);

    font-size: 0.69rem;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.12em;
}

.profile-eyebrow i {
    font-size: 9px;
}

.profile-name {

    margin: 0;

    color: var(--profile-heading);

    font-size:
        clamp(
            1.55rem,
            3vw,
            2.35rem
        );

    line-height: 1.05;

    font-weight: 800;

    letter-spacing: -0.045em;
}

.profile-meta {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 9px;

    margin-top: 10px;
}

.profile-role {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        7px
        11px;

    border-radius: 999px;

    background:
        rgba(104, 57, 235, 0.08);

    border:
        1px solid
        rgba(104, 57, 235, 0.12);

    color: #5b2fd1;

    font-size: 0.72rem;

    font-weight: 750;
}

.profile-company {

    color: var(--profile-muted);

    font-size: 0.76rem;
}

.profile-company i {

    margin-right: 5px;

    color: var(--profile-purple);
}


/* ============================================================
   STATUS / EDIT
   ============================================================ */

.profile-hero-actions {

    display: flex;

    align-items: center;

    gap: 12px;
}

.profile-status {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        10px
        14px;

    border-radius: 999px;

    background:
        rgba(16, 185, 129, 0.075);

    border:
        1px solid
        rgba(16, 185, 129, 0.14);

    color: #047857;

    font-size: 0.72rem;

    font-weight: 800;
}

.profile-status-dot {

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: var(--profile-green);

    box-shadow:
        0 0 0 4px
        rgba(16, 185, 129, 0.10);
}

.profile-edit-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    min-height: 43px;

    padding:
        10px
        16px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            var(--profile-purple),
            var(--profile-purple-dark)
        );

    color: #ffffff;

    border: 0;

    text-decoration: none;

    font-size: 0.76rem;

    font-weight: 750;

    box-shadow:
        0 10px 25px
        rgba(104, 57, 235, 0.19);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}

.profile-edit-button:hover {

    color: #ffffff;

    transform:
        translateY(-2px);

    box-shadow:
        0 15px 30px
        rgba(104, 57, 235, 0.27);
}


/* ============================================================
   CONTENT
   ============================================================ */

.profile-content {

    position: relative;

    z-index: 2;

    padding: 30px;
}


/* ============================================================
   SECTION HEADER
   ============================================================ */

.profile-section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 18px;
}

.profile-section-title {

    display: flex;

    align-items: center;

    gap: 11px;
}

.profile-section-icon {

    width: 38px;
    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    background:
        rgba(104, 57, 235, 0.09);

    border:
        1px solid
        rgba(104, 57, 235, 0.11);

    color: var(--profile-purple);

    font-size: 14px;
}

.profile-section-title h3 {

    margin: 0;

    color: var(--profile-heading);

    font-size: 1rem;

    font-weight: 800;

    letter-spacing: -0.02em;
}

.profile-section-title p {

    margin: 2px 0 0;

    color: var(--profile-muted);

    font-size: 0.7rem;
}


/* ============================================================
   INFORMATION GRID
   ============================================================ */

.profile-info-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap: 14px;

    margin-bottom: 30px;
}


/* ============================================================
   INFO CARD
   ============================================================ */

.profile-info-card {

    position: relative;

    display: flex;

    align-items: center;

    gap: 14px;

    min-width: 0;

    padding:
        17px;

    border:
        1px solid
        rgba(148, 163, 184, 0.19);

    border-radius: 17px;

    background:
        rgba(255, 255, 255, 0.58);

    backdrop-filter:
        blur(15px);

    -webkit-backdrop-filter:
        blur(15px);

    transition:
        transform .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;
}

.profile-info-card:hover {

    transform:
        translateY(-2px);

    border-color:
        rgba(104, 57, 235, 0.20);

    box-shadow:
        0 14px 35px
        rgba(15, 23, 42, 0.06);
}

.profile-info-icon {

    width: 42px;
    height: 42px;

    flex: 0 0 42px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            rgba(104, 57, 235, 0.11),
            rgba(59, 130, 246, 0.07)
        );

    border:
        1px solid
        rgba(104, 57, 235, 0.10);

    color: var(--profile-purple);

    font-size: 14px;
}

.profile-info-copy {

    min-width: 0;
}

.profile-info-label {

    margin: 0 0 4px;

    color: #94a3b8;

    font-size: 0.65rem;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: 0.07em;
}

.profile-info-value {

    margin: 0;

    color: #1e293b;

    font-size: 0.82rem;

    font-weight: 700;

    line-height: 1.45;

    overflow-wrap: anywhere;
}


/* ============================================================
   ACCOUNT INTELLIGENCE
   ============================================================ */

.profile-intelligence {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 14px;

    margin-bottom: 30px;
}

.profile-stat {

    position: relative;

    overflow: hidden;

    padding: 18px;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            rgba(104, 57, 235, 0.075),
            rgba(255, 255, 255, 0.56)
        );

    border:
        1px solid
        rgba(104, 57, 235, 0.10);
}

.profile-stat::after {

    content: "";

    position: absolute;

    width: 90px;
    height: 90px;

    right: -35px;
    top: -40px;

    border-radius: 50%;

    background:
        rgba(104, 57, 235, 0.07);
}

.profile-stat-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 13px;
}

.profile-stat-icon {

    width: 34px;
    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background:
        rgba(255, 255, 255, 0.72);

    color: var(--profile-purple);

    font-size: 12px;
}

.profile-stat-label {

    color: #64748b;

    font-size: 0.68rem;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.06em;
}

.profile-stat-value {

    position: relative;

    z-index: 1;

    color: var(--profile-heading);

    font-size: 0.87rem;

    font-weight: 800;

    overflow-wrap: anywhere;
}


/* ============================================================
   LOCATION FEATURE
   ============================================================ */

.profile-location {

    position: relative;

    overflow: hidden;

    margin-top: 4px;

    padding: 21px;

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            rgba(104, 57, 235, 0.09),
            rgba(59, 130, 246, 0.045),
            rgba(255, 255, 255, 0.55)
        );

    border:
        1px solid
        rgba(104, 57, 235, 0.12);
}

.profile-location-content {

    display: flex;

    align-items: center;

    gap: 15px;
}

.profile-location-icon {

    width: 48px;
    height: 48px;

    flex: 0 0 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    background:
        rgba(255, 255, 255, 0.75);

    color: var(--profile-purple);

    font-size: 16px;

    box-shadow:
        0 8px 22px
        rgba(104, 57, 235, 0.08);
}

.profile-location-label {

    margin: 0 0 4px;

    color: #94a3b8;

    font-size: 0.64rem;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .08em;
}

.profile-location-name {

    margin: 0;

    color: var(--profile-heading);

    font-size: 0.95rem;

    font-weight: 800;
}

.profile-location-meta {

    margin: 3px 0 0;

    color: var(--profile-muted);

    font-size: 0.7rem;
}


/* ============================================================
   FOOTER NOTE
   ============================================================ */

.profile-security-note {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-top: 20px;

    padding:
        13px
        15px;

    border-radius: 13px;

    background:
        rgba(16, 185, 129, 0.055);

    border:
        1px solid
        rgba(16, 185, 129, 0.10);

    color: #64748b;

    font-size: 0.69rem;

    line-height: 1.5;
}

.profile-security-note i {

    color: var(--profile-green);

    font-size: 12px;
}


/* ============================================================
   DARK MODE — PRESERVED FOR EXISTING ALPINE STATE
   ============================================================ */

.dark body {

    background:
        #0f172a;
}

.dark .profile-shell {

    background:
        rgba(15, 23, 42, 0.78);

    border-color:
        rgba(255, 255, 255, 0.06);
}

.dark .profile-hero {

    border-color:
        rgba(255, 255, 255, 0.06);
}

.dark .profile-name,
.dark .profile-section-title h3,
.dark .profile-info-value,
.dark .profile-stat-value,
.dark .profile-location-name {

    color: #f8fafc;
}

.dark .profile-company,
.dark .profile-info-label,
.dark .profile-stat-label,
.dark .profile-location-meta,
.dark .profile-security-note {

    color: #94a3b8;
}

.dark .profile-info-card {

    background:
        rgba(15, 23, 42, 0.58);

    border-color:
        rgba(71, 85, 105, 0.45);
}

.dark .profile-stat {

    background:
        linear-gradient(
            135deg,
            rgba(104, 57, 235, 0.12),
            rgba(15, 23, 42, 0.50)
        );

    border-color:
        rgba(104, 57, 235, 0.16);
}

.dark .profile-location {

    background:
        linear-gradient(
            135deg,
            rgba(104, 57, 235, 0.14),
            rgba(15, 23, 42, 0.55)
        );
}


/* ============================================================
   LARGE TABLETS
   ============================================================ */

@media (max-width: 1100px) {

    .profile-hero {
        padding: 27px;
    }

    .profile-content {
        padding: 27px;
    }

    .profile-intelligence {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }
}


/* ============================================================
   TABLET
   ============================================================ */

@media (max-width: 850px) {

    .profile-hero-grid {

        grid-template-columns: 1fr;

        align-items: flex-start;
    }

    .profile-hero-actions {

        justify-content: flex-start;

        flex-wrap: wrap;
    }

    .profile-info-grid {

        grid-template-columns: 1fr;
    }

    .profile-intelligence {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}


/* ============================================================
   MOBILE
   ============================================================ */

@media (max-width: 600px) {

    .profile-page {

        padding:
            4px
            0
            28px;
    }

    .profile-shell {

        border-radius: 20px;
    }

    .profile-breadcrumb {

        margin-bottom: 12px;

        padding:
            2px;
    }

    .profile-hero {

        padding:
            22px
            17px;
    }

    .profile-identity {

        align-items: flex-start;

        gap: 14px;
    }

    .profile-avatar {

        width: 72px;
        height: 72px;

        flex-basis: 72px;

        border-radius: 20px;
    }

    .profile-avatar-initials {

        font-size: 1.3rem;
    }

    .profile-online {

        width: 12px;
        height: 12px;

        right: 4px;
        bottom: 4px;

        border-width: 2px;
    }

    .profile-name {

        font-size: 1.42rem;
    }

    .profile-meta {

        margin-top: 7px;

        gap: 6px;
    }

    .profile-role {

        font-size: 0.65rem;

        padding:
            6px
            9px;
    }

    .profile-company {

        width: 100%;

        font-size: 0.7rem;
    }

    .profile-hero-actions {

        width: 100%;

        display: grid;

        grid-template-columns:
            1fr
            1fr;

        gap: 8px;
    }

    .profile-status,
    .profile-edit-button {

        width: 100%;

        justify-content: center;
    }

    .profile-content {

        padding:
            21px
            17px
            23px;
    }

    .profile-section-title {

        align-items: flex-start;
    }

    .profile-section-icon {

        width: 34px;
        height: 34px;

        flex-basis: 34px;
    }

    .profile-section-title h3 {

        font-size: 0.91rem;
    }

    .profile-section-title p {

        font-size: 0.65rem;
    }

    .profile-info-grid {

        gap: 10px;

        margin-bottom: 23px;
    }

    .profile-info-card {

        padding:
            14px;

        border-radius: 14px;
    }

    .profile-info-icon {

        width: 38px;
        height: 38px;

        flex-basis: 38px;

        border-radius: 10px;

        font-size: 12px;
    }

    .profile-info-label {

        font-size: 0.59rem;
    }

    .profile-info-value {

        font-size: 0.76rem;
    }

    .profile-intelligence {

        grid-template-columns: 1fr;

        gap: 10px;

        margin-bottom: 23px;
    }

    .profile-stat {

        padding:
            15px;
    }

    .profile-location {

        padding:
            16px;

        border-radius: 16px;
    }

    .profile-location-content {

        align-items: flex-start;

        gap: 12px;
    }

    .profile-location-icon {

        width: 42px;
        height: 42px;

        flex-basis: 42px;

        border-radius: 12px;
    }

}


/* ============================================================
   SMALL MOBILE
   ============================================================ */

@media (max-width: 390px) {

    .profile-hero {

        padding:
            18px
            14px;
    }

    .profile-content {

        padding:
            18px
            14px
            20px;
    }

    .profile-avatar {

        width: 62px;
        height: 62px;

        flex-basis: 62px;

        border-radius: 17px;
    }

    .profile-name {

        font-size: 1.25rem;
    }

    .profile-identity {

        gap: 11px;
    }

    .profile-hero-actions {

        grid-template-columns: 1fr;
    }

    .profile-info-card {

        gap: 11px;

        padding: 12px;
    }

}

</style>

<script
    data-cfasync="false"
    nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86"
>
try {

    (function(w,d){

        !function(fv,fw,fx,fy){

            if(fv.zaraz)

                console.error("zaraz is loaded twice");

            else {

                fv[fx]=fv[fx]||[];

                fv[fx].executed=[];

                fv.zaraz={
                    deferred:[],
                    listeners:[]
                };

                fv.zaraz._v="5858";

                fv.zaraz._n="ff1f9a24-18a4-4614-8917-7c27c5c96a86";

                fv.zaraz.q=[];

                fv.zaraz._f=function(fz){

                    return async function(){

                        var fA =
                            Array.prototype.slice.call(arguments);

                        fv.zaraz.q.push({
                            m:fz,
                            a:fA
                        });

                    }

                };

                for(
                    const fB of
                    ["track","set","debug"]
                )

                    fv.zaraz[fB]=fv.zaraz._f(fB);

                fv.zaraz.init=()=>{

                    var fC =
                        fw.getElementsByTagName(fy)[0],

                        fD =
                            fw.createElement(fy),

                        fE =
                            fw.getElementsByTagName("title")[0];

                    fE &&
                    (
                        fv[fx].t =
                        fw.getElementsByTagName("title")[0].text
                    );

                    fv[fx].x=Math.random();

                    fv[fx].w=fv.screen.width;

                    fv[fx].h=fv.screen.height;

                    fv[fx].j=fv.innerHeight;

                    fv[fx].e=fv.innerWidth;

                    fv[fx].l=fv.location.href;

                    fv[fx].r=fw.referrer;

                    fv[fx].k=fv.screen.colorDepth;

                    fv[fx].n=fw.characterSet;

                    fv[fx].o=
                        (new Date).getTimezoneOffset();

                    if(fv.dataLayer)

                        for(
                            const fF of
                            Object.entries(
                                Object.entries(dataLayer)
                                .reduce(
                                    ((fG,fH)=>({
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
                                {
                                    scope:"page"
                                }
                            );

                    fv[fx].q=[];

                    for(;fv.zaraz.q.length;){

                        const fI =
                            fv.zaraz.q.shift();

                        fv[fx].q.push(fI);

                    }

                    fD.defer=!0;

                    for(
                        const fJ of
                        [localStorage,sessionStorage]
                    )

                        Object.keys(fJ||{})

                        .filter(
                            fL =>
                            fL.startsWith("_zaraz_")
                        )

                        .forEach(fK=>{

                            try{

                                fv[fx]["z_"+fK.slice(7)] =
                                    JSON.parse(
                                        fJ.getItem(fK)
                                    );

                            }catch{

                                fv[fx]["z_"+fK.slice(7)] =
                                    fJ.getItem(fK);

                            }

                        });

                    fD.referrerPolicy="origin";

                    fD.src =
                        "cdn-cgi/zaraz/sd0d9.js?z="+
                        btoa(
                            encodeURIComponent(
                                JSON.stringify(fv[fx])
                            )
                        );

                    fC.parentNode.insertBefore(
                        fD,
                        fC
                    );

                };

                ["complete","interactive"]
                    .includes(fw.readyState)

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
            new Promise(eD=>{

                if(eC){

                    eC.e &&
                    eC.e.forEach(eE=>{

                        try{

                            const eF =
                                d.querySelector(
                                    "script[nonce]"
                                );

                            const eG =
                                eF?.nonce ||
                                eF?.getAttribute("nonce");

                            const eH =
                                d.createElement("script");

                            eG &&
                            (eH.nonce=eG);

                            eH.innerHTML=eE;

                            eH.onload=()=>{

                                d.head.removeChild(eH);

                            };

                            d.head.appendChild(eH);

                        }catch(eI){

                            console.error(
                                `Error executing script: ${eE}\n`,
                                eI
                            );

                        }

                    });

                    Promise.allSettled(
                        (eC.f||[])
                        .map(
                            eJ =>
                            fetch(eJ[0],eJ[1])
                        )
                    );

                }

                eD();

            });

        zaraz._p({
            "e":[
                "(function(w,d){})(window,document)"
            ]
        });

    })(window,document);

}catch(e){

    throw fetch("/cdn-cgi/zaraz/t"),e;

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
    $watch(
        'darkMode',
        value =>
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

<!-- ============================================================
     PAGE WRAPPER
     ============================================================ -->

<div class="flex h-screen overflow-hidden">


<?php include('sidebar.php'); ?>


<!-- ========================================================
     CONTENT AREA
     ======================================================== -->

<div
    class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
>


    <!-- HEADER -->

    <?php include('header.php'); ?>


    <!-- ====================================================
         MAIN
         ==================================================== -->

    <main>

        <div
            class="
                mx-auto
                max-w-(--breakpoint-2xl)
                p-4
                md:p-6
            "
        >

            <div class="profile-page">


                <!-- ==================================================
                     BREADCRUMB
                     ================================================== -->

                <div class="profile-breadcrumb">

                    <div class="profile-breadcrumb-left">

                        <a href="index.php">

                            <i class="fas fa-house"></i>

                            Home

                        </a>

                        <i
                            class="fas fa-chevron-right breadcrumb-chevron"
                        ></i>

                        <span class="profile-breadcrumb-current">

                            Staff Profile

                        </span>

                    </div>

                </div>


                <!-- ==================================================
                     PROFILE SHELL
                     ================================================== -->

                <section class="profile-shell">


                    <!-- ==================================================
                         HERO
                         ================================================== -->

                    <header class="profile-hero">

                        <div class="profile-hero-grid">


                            <!-- IDENTITY -->

                            <div class="profile-identity">


                                <div class="profile-avatar">

                                    <?php if (!empty($profileImage)): ?>

                                        <img
                                            src="<?= $safeProfile ?>"
                                            alt="Staff Profile"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        >

                                        <span
                                            class="profile-avatar-initials"
                                            style="display:none;"
                                        >
                                            <?= $safeInitials ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="profile-avatar-initials">
                                            <?= $safeInitials ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if ($isActive): ?>

                                        <span
                                            class="profile-online"
                                            title="Active"
                                        ></span>

                                    <?php endif; ?>

                                </div>


                                <div class="profile-identity-content">


                                    <div class="profile-eyebrow">

                                        <i class="fas fa-shield-halved"></i>

                                        Staff Identity

                                    </div>


                                    <h1 class="profile-name">

                                        <?= $safeFullName ?>

                                    </h1>


                                    <div class="profile-meta">

                                        <span class="profile-role">

                                            <i class="fas fa-user-tie"></i>

                                            <?= $safeRole ?>

                                        </span>


                                        <span class="profile-company">

                                            <i class="fas fa-building"></i>

                                            <?= $safeCompany ?>

                                        </span>

                                    </div>

                                </div>

                            </div>


                            <!-- ACTIONS -->

                            <div class="profile-hero-actions">


                                <div class="profile-status">

                                    <span class="profile-status-dot"></span>

                                    <?= $isActive ? 'Active Account' : $safeStatus ?>

                                </div>


                                <form
                                    action="edit_admin.php"
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="company_id"
                                        value="<?= htmlspecialchars($company_id, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="submit"
                                        class="profile-edit-button"
                                    >

                                        <i class="fas fa-pen-to-square"></i>

                                        Edit Profile

                                    </button>

                                </form>


                            </div>

                        </div>

                    </header>


                    <!-- ==================================================
                         CONTENT
                         ================================================== -->

                    <div class="profile-content">


                        <!-- ==================================================
                             INFORMATION
                             ================================================== -->

                        <div class="profile-section-header">

                            <div class="profile-section-title">

                                <div class="profile-section-icon">

                                    <i class="fas fa-id-card"></i>

                                </div>

                                <div>

                                    <h3>
                                        My Information
                                    </h3>

                                    <p>
                                        Personal and professional identity
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="profile-info-grid">


                            <!-- FIRST NAME -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-user"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        First Name
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safeFirstname ?>
                                    </p>

                                </div>

                            </div>


                            <!-- LAST NAME -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-user-tag"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        Last Name
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safeLastname ?>
                                    </p>

                                </div>

                            </div>


                            <!-- EMAIL -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-envelope"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        Email Address
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safeEmail ?>
                                    </p>

                                </div>

                            </div>


                            <!-- PHONE -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-phone"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        Mobile
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safePhone ?>
                                    </p>

                                </div>

                            </div>


                            <!-- ADDRESS -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-house"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        Residential Address
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safeAddress ?>
                                    </p>

                                </div>

                            </div>


                            <!-- ROLE -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-user-shield"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        Role
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safeRole ?>
                                    </p>

                                </div>

                            </div>


                            <!-- COMPANY -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-building"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        Company
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safeCompany ?>
                                    </p>

                                </div>

                            </div>


                            <!-- USERNAME -->

                            <div class="profile-info-card">

                                <div class="profile-info-icon">

                                    <i class="fas fa-at"></i>

                                </div>

                                <div class="profile-info-copy">

                                    <p class="profile-info-label">
                                        Username
                                    </p>

                                    <p class="profile-info-value">
                                        <?= $safeUsername ?>
                                    </p>

                                </div>

                            </div>

                        </div>


                        <!-- ==================================================
                             ACCOUNT INTELLIGENCE
                             ================================================== -->

                        <div class="profile-section-header">

                            <div class="profile-section-title">

                                <div class="profile-section-icon">

                                    <i class="fas fa-chart-line"></i>

                                </div>

                                <div>

                                    <h3>
                                        Account Intelligence
                                    </h3>

                                    <p>
                                        Current staff account context
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="profile-intelligence">


                            <!-- ACCOUNT STATUS -->

                            <div class="profile-stat">

                                <div class="profile-stat-top">

                                    <span class="profile-stat-label">
                                        Account Status
                                    </span>

                                    <div class="profile-stat-icon">

                                        <i class="fas fa-circle-check"></i>

                                    </div>

                                </div>

                                <div class="profile-stat-value">

                                    <?= $isActive ? 'Operational' : $safeStatus ?>

                                </div>

                            </div>


                            <!-- USER ROLE -->

                            <div class="profile-stat">

                                <div class="profile-stat-top">

                                    <span class="profile-stat-label">
                                        Access Role
                                    </span>

                                    <div class="profile-stat-icon">

                                        <i class="fas fa-key"></i>

                                    </div>

                                </div>

                                <div class="profile-stat-value">

                                    <?= $safeRole ?>

                                </div>

                            </div>


                            <!-- COMPANY -->

                            <div class="profile-stat">

                                <div class="profile-stat-top">

                                    <span class="profile-stat-label">
                                        Organisation
                                    </span>

                                    <div class="profile-stat-icon">

                                        <i class="fas fa-network-wired"></i>

                                    </div>

                                </div>

                                <div class="profile-stat-value">

                                    <?= $safeCompany ?>

                                </div>

                            </div>

                        </div>


                        <!-- ==================================================
                             LOCATION
                             ================================================== -->

                        <div class="profile-section-header">

                            <div class="profile-section-title">

                                <div class="profile-section-icon">

                                    <i class="fas fa-location-dot"></i>

                                </div>

                                <div>

                                    <h3>
                                        Assigned Location
                                    </h3>

                                    <p>
                                        Current operational location
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="profile-location">

                            <div class="profile-location-content">


                                <div class="profile-location-icon">

                                    <i class="fas fa-map-location-dot"></i>

                                </div>


                                <div>

                                    <p class="profile-location-label">
                                        Acting Location
                                    </p>

                                    <p class="profile-location-name">
                                        <?= $safeLocation ?>
                                    </p>

                                    <?php if (
                                        !empty($location_city) ||
                                        !empty($location_state)
                                    ): ?>

                                        <p class="profile-location-meta">

                                            <i class="fas fa-location-arrow"></i>

                                            <?= htmlspecialchars(
                                                trim(
                                                    $location_city .
                                                    (
                                                        !empty($location_city) &&
                                                        !empty($location_state)
                                                        ? ', '
                                                        : ''
                                                    ) .
                                                    $location_state
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </p>

                                    <?php else: ?>

                                        <p class="profile-location-meta">

                                            No additional location details available.

                                        </p>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>


                        <!-- ==================================================
                             SECURITY NOTE
                             ================================================== -->

                        <div class="profile-security-note">

                            <i class="fas fa-shield-halved"></i>

                            <span>
                                Your staff identity is securely associated with
                                <?= $safeCompany ?> and your assigned operational location.
                            </span>

                        </div>


                    </div>

                </section>

            </div>

        </div>

    </main>


    <!-- ========================================================
         FOOTER
         ======================================================== -->

    <?php include('footer.php'); ?>


</div>

</div>

<!-- ============================================================
     EXISTING BUNDLE
     ============================================================ -->

<script
    data-cfasync="false"
    src="../company/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script
    defer
    src="../company/bundle.js"
></script>

<!-- ============================================================
     CLOUDFLARE
     ============================================================ -->

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
