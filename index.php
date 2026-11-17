<?php

include('config/db.php');
include('company_data.php');


/*
|--------------------------------------------------------------------------
| SESSION / COMPANY DATA
|--------------------------------------------------------------------------
*/

$_SESSION['customercare']    = $customercare ?? '';
$_SESSION['company_email']   = $companyEmail ?? '';
$_SESSION['company_mobile']  = $companyMobile ?? '';

$company_customer_care_email = $_SESSION['customercare'];
$company_email               = $_SESSION['company_email'];
$company_customer_mobile     = $_SESSION['company_mobile'];


/*
|--------------------------------------------------------------------------
| SAFE ASSET PATH
|--------------------------------------------------------------------------
*/

function slideAssetPath($path)
{
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    $path = str_replace('\\', '/', $path);

    if (
        str_starts_with($path, 'http://') ||
        str_starts_with($path, 'https://') ||
        str_starts_with($path, '//')
    ) {
        return $path;
    }

    $path = ltrim($path, '/');

    if (str_starts_with($path, 'uploads/')) {
        return $path;
    }

    return 'uploads/' . $path;
}


/*
|--------------------------------------------------------------------------
| PRIVACY POLICY
|--------------------------------------------------------------------------
*/

$privacyPolicy = [];

if (!empty($company_id)) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM privacy_policy
        WHERE company_id = :company_id
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([
        ':company_id' => $company_id
    ]);

    $privacyPolicy = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}


/*
|--------------------------------------------------------------------------
| TERMS & CONDITIONS
|--------------------------------------------------------------------------
*/

$termsConditions = [];

if (!empty($company_id)) {

    $stmt = $pdo->prepare("\n        SELECT *\n        FROM terms_conditions\n        WHERE company_id = :company_id\n        ORDER BY id DESC\n        LIMIT 1\n    ");

    $stmt->execute([
        ':company_id' => $company_id
    ]);

    $termsConditions = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}


/*
|--------------------------------------------------------------------------
| ABOUT
|--------------------------------------------------------------------------
*/

$about = [
    'id' => 0
];

$aboutSections = [];
$displayAboutSections = [];

if (!empty($company_id)) {
    // Find this company's About Profile record.
    $stmt = $pdo->prepare("
        SELECT id
        FROM about
        WHERE company_id = :company_id
        LIMIT 1
    ");
    $stmt->execute([':company_id' => $company_id]);
    $about = $stmt->fetch(PDO::FETCH_ASSOC) ?: $about;

    // Public About Us content comes exclusively from the dynamic section builder.
    if (!empty($about['id'])) {
        $sectionStmt = $pdo->prepare("
            SELECT section_title, section_content
            FROM about_sections
            WHERE company_id = :company_id
              AND about_id = :about_id
            ORDER BY display_order ASC, id ASC
        ");
        $sectionStmt->execute([
            ':company_id' => $company_id,
            ':about_id' => $about['id']
        ]);
        $aboutSections = $sectionStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Render every saved section; do not add fixed or legacy About content.
foreach ($aboutSections as $aboutSection) {
    $sectionTitle = trim((string)($aboutSection['section_title'] ?? ''));
    $sectionContent = trim((string)($aboutSection['section_content'] ?? ''));
    if ($sectionTitle === '' || $sectionContent === '') {
        continue;
    }
    $displayAboutSections[] = [
        'title' => $sectionTitle,
        'content' => $sectionContent
    ];
}

// Select a familiar Font Awesome icon for common headings; custom headings
// receive a neutral section icon. This affects presentation only.
function publicAboutSectionIcon($title)
{
    $key = strtolower(trim((string)$title));

    $icons = [
        'our vision' => 'fa-eye',
        'vision' => 'fa-eye',
        'our mission' => 'fa-bullseye',
        'mission' => 'fa-bullseye',
        'our values' => 'fa-gem',
        'values' => 'fa-gem',
        'our history' => 'fa-landmark',
        'history' => 'fa-landmark',
        'our services' => 'fa-gears',
        'services' => 'fa-gears',
        'what we do' => 'fa-gears',
        'our team' => 'fa-people-group',
        'team' => 'fa-people-group',
        'leadership' => 'fa-user-tie',
        'our leadership' => 'fa-user-tie',
        'our strategy' => 'fa-chess',
        'strategy' => 'fa-chess',
        'our goals' => 'fa-bullseye',
        'goals' => 'fa-bullseye',
        'our future' => 'fa-rocket',
        'future' => 'fa-rocket',
        'why choose us' => 'fa-star',
        'why us' => 'fa-star'
    ];

    return $icons[$key] ?? 'fa-layer-group';
}


/*
|--------------------------------------------------------------------------
| CONTACT / COMPANY INFORMATION
|--------------------------------------------------------------------------
*/

$companyContact = [
    'company_office_address' => '',
    'company_customer_care'  => '',
    'company_support_email'  => '',
    'company_email'          => '',
    'company_mobile'         => '',
    'company_head_office'    => '',
    'company_state'          => '',
    'company_city'           => '',
    'company_domain'         => ''
];

if (!empty($company_id)) {

    $stmt = $pdo->prepare("
        SELECT
            company_office_address,
            company_customer_care,
            company_support_email,
            company_email,
            company_mobile,
            company_domain,
            company_head_office,
            company_state,
            company_city
        FROM companies
        WHERE id = :company_id
        LIMIT 1
    ");

    $stmt->execute([
        ':company_id' => $company_id
    ]);

    $companyRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($companyRow) {
        $companyContact = array_merge($companyContact, $companyRow);
    }
}


/*
|--------------------------------------------------------------------------
| CONTACT VARIABLES
|--------------------------------------------------------------------------
*/

$company_customer_care_email =
    $companyContact['company_customer_care'] !== ''
        ? $companyContact['company_customer_care']
        : ($customercare ?? '');

$company_email =
    $companyContact['company_email'] !== ''
        ? $companyContact['company_email']
        : ($companyEmail ?? '');

$company_customer_mobile =
    $companyContact['company_mobile'] !== ''
        ? $companyContact['company_mobile']
        : ($companyMobile ?? '');

$company_support_email =
    $companyContact['company_support_email'] !== ''
        ? $companyContact['company_support_email']
        : $company_email;

$company_office_address =
    $companyContact['company_office_address'];

$company_head_office =
    $companyContact['company_head_office'];

$company_state =
    $companyContact['company_state'];

$company_city =
    $companyContact['company_city'];

$company_domain =
    $companyContact['company_domain'] ?? '';


$_SESSION['customercare']   = $company_customer_care_email;
$_SESSION['company_email']  = $company_email;
$_SESSION['company_mobile'] = $company_customer_mobile;


/*
|--------------------------------------------------------------------------
| SLIDES
|--------------------------------------------------------------------------
*/

$slides = [];

if (!empty($company_id)) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM slides
        WHERE company_id = :company_id
        ORDER BY slide_order ASC
    ");

    $stmt->execute([
        ':company_id' => $company_id
    ]);

    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalSlides = count($slides);


/*
|--------------------------------------------------------------------------
| COMPANY LOGO
|--------------------------------------------------------------------------
*/

$logoPath = '';

if (!empty($company_logo)) {

    $logoPath = 'uploads/logos/' . $company_logo;

}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars($company_name ?? 'Company'); ?>
    | Official Website
</title>


<!-- FONT AWESOME -->

<link
    rel="stylesheet"
    href="fontawesome/css/all.css"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* ============================================================
   FUTURISTIC ENTERPRISE WEBSITE
   ============================================================ */

:root {

    --black: #05070a;
    --black-2: #080c12;
    --glass: rgba(8, 13, 20, .68);
    --glass-light: rgba(255,255,255,.07);

    --white: #ffffff;
    --muted: rgba(255,255,255,.68);

    --cyan: #00e5ff;
    --blue: #4d7cff;
    --violet: #8b5cf6;
    --gold: #c9a45c;

    --border: rgba(255,255,255,.12);

    --shadow:
        0 30px 80px rgba(0,0,0,.55);

    --radius: 24px;

}


/* ============================================================
   RESET
   ============================================================ */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    height: 100%;
}

body {

    background: var(--black);

    color: var(--white);

    font-family:
        Inter,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    overflow: hidden;

}

button,
input,
textarea,
select {
    font: inherit;
}

button,
a {
    -webkit-tap-highlight-color: transparent;
}

a {
    color: inherit;
}


/* ============================================================
   MAIN APPLICATION
   ============================================================ */

#wrapper {

    width: 100%;
    height: 100vh;

    position: relative;

    overflow: hidden;

    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(0,229,255,.07),
            transparent 35%
        ),
        radial-gradient(
            circle at 80% 80%,
            rgba(139,92,246,.08),
            transparent 35%
        ),
        #05070a;

}


/* ============================================================
   HERO / SLIDER
   ============================================================ */

.slider {

    position: relative;

    width: 100%;
    height: 100vh;

    overflow: hidden;

}


/* ============================================================
   SLIDES TRACK
   ============================================================ */

.slides {

    display: flex;

    width: <?= max($totalSlides, 1) * 100; ?>%;

    height: 100%;

    transition:
        transform 1.1s
        cubic-bezier(.77,0,.18,1);

}


/* ============================================================
   INDIVIDUAL SLIDE
   ============================================================ */

.slide {

    position: relative;

    flex: 0 0 <?= $totalSlides > 0 ? (100 / $totalSlides) : 100; ?>%;

    width: <?= $totalSlides > 0 ? (100 / $totalSlides) : 100; ?>%;

    height: 100%;

    background-image: var(--desktop-bg);

    background-size: cover;

    background-position: center;

    background-repeat: no-repeat;

    overflow: hidden;

}


/* ============================================================
   MOBILE IMAGE
   ============================================================ */

@media (max-width: 768px) {

    .slide {

        background-image:
            var(--mobile-bg);

        background-position:
            center center;

    }

}


/* ============================================================
   IMAGE ATMOSPHERE
   ============================================================ */

.slide::before {

    content: "";

    position: absolute;

    inset: 0;

    background:

        linear-gradient(
            90deg,
            rgba(0,0,0,.84) 0%,
            rgba(0,0,0,.53) 42%,
            rgba(0,0,0,.18) 100%
        );

    z-index: 1;

}


.slide::after {

    content: "";

    position: absolute;

    inset: 0;

    background:

        radial-gradient(
            circle at 75% 40%,
            rgba(0,229,255,.08),
            transparent 30%
        ),

        linear-gradient(
            to top,
            rgba(0,0,0,.85),
            transparent 45%
        );

    z-index: 2;

}


/* ============================================================
   HERO CONTENT
   ============================================================ */

.slideoverlay {

    position: relative;

    z-index: 4;

    width: 100%;
    height: 100%;

    display: flex;

    flex-direction: column;

    justify-content: center;

    align-items: flex-start;

    padding:

        160px
        clamp(28px, 9vw, 150px)
        100px;

}


/* ============================================================
   SMALL HERO LABEL
   ============================================================ */

.hero-kicker {

    display: inline-flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 22px;

    padding: 8px 14px;

    border:
        1px solid
        rgba(0,229,255,.3);

    background:
        rgba(0,229,255,.06);

    border-radius: 999px;

    color: var(--cyan);

    font-size: .72rem;

    font-weight: 700;

    letter-spacing: .16em;

    text-transform: uppercase;

    backdrop-filter: blur(12px);

}

.hero-kicker i {
    font-size: .55rem;
}


/* ============================================================
   HEADLINE
   ============================================================ */

#headline {

    max-width: 900px;

}

#headline h1 {

    font-size:
        clamp(
            2.4rem,
            5.7vw,
            6rem
        );

    line-height: .98;

    letter-spacing: -.045em;

    font-weight: 800;

    color: #fff;

    text-shadow:
        0 10px 50px rgba(0,0,0,.5);

}


/* ============================================================
   TAGLINE
   ============================================================ */

#tagline {

    max-width: 650px;

    margin-top: 28px;

}

#tagline p {

    font-size:
        clamp(
            .95rem,
            1.4vw,
            1.2rem
        );

    line-height: 1.75;

    color: rgba(255,255,255,.76);

}


/* ============================================================
   HERO ACTIONS
   ============================================================ */

.hero-actions {

    display: flex;

    align-items: center;

    gap: 14px;

    margin-top: 35px;

}

.hero-action {

    display: inline-flex;

    align-items: center;

    gap: 10px;

    padding: 13px 19px;

    border-radius: 12px;

    border: 1px solid
        rgba(255,255,255,.18);

    background:
        rgba(255,255,255,.07);

    color: white;

    cursor: pointer;

    transition:
        .3s ease;

    backdrop-filter: blur(15px);

}

.hero-action:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(0,229,255,.55);

    background:
        rgba(0,229,255,.1);

}

.hero-action.primary {

    background:
        linear-gradient(
            135deg,
            #00d9ff,
            #4d7cff
        );

    color: #020508;

    border: none;

    font-weight: 800;

}


/* ============================================================
   TOP NAVIGATION
   ============================================================ */

.navigation {

    position: fixed;

    top: 0;
    left: 0;

    width: 100%;

    height: 88px;

    z-index: 100;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 clamp(18px, 4vw, 60px);

    background:
        linear-gradient(
            to bottom,
            rgba(0,0,0,.72),
            rgba(0,0,0,.08)
        );

    backdrop-filter:
        blur(16px);

}


/* ============================================================
   LOGO
   ============================================================ */

.brand {

    display: flex;

    align-items: center;

    gap: 14px;

    min-width: 0;

}

.logo {

    width: 52px;
    height: 52px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 15px;

    background:
        rgba(255,255,255,.08);

    border:
        1px solid
        rgba(255,255,255,.14);

    backdrop-filter:
        blur(15px);

    overflow: hidden;

}

.logo img {

    max-width: 90%;

    max-height: 90%;

    object-fit: contain;

}

.brand-name {

    max-width: 260px;

    overflow: hidden;

    white-space: nowrap;

    text-overflow: ellipsis;

    font-size: .95rem;

    font-weight: 700;

    letter-spacing: .02em;

}


/* ============================================================
   DESKTOP NAV
   ============================================================ */

.desktop-nav {

    display: flex;

    align-items: center;

    gap: 7px;

}

.nav-link {

    position: relative;

    border: 0;

    background: transparent;

    color:
        rgba(255,255,255,.72);

    padding:
        10px 14px;

    border-radius: 10px;

    cursor: pointer;

    font-size: .86rem;

    transition: .25s ease;

}

.nav-link i {

    margin-right: 7px;

    font-size: .75rem;
    color: #fff;

}

.nav-link:hover {

    color: #fff;

    background:
        rgba(255,255,255,.08);

}

.nav-link.active {

    color:
        var(--cyan);

}

/* Marketplace link behaves like the other desktop navigation items. */
.marketplace-nav-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}

/* Marketplace link in the mobile dropdown. */
.mobile-marketplace-link {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 15px;
    margin-bottom: 5px;
    border-radius: 13px;
    color: rgba(255,255,255,.78);
    text-decoration: none;
    transition: .25s ease;
}

.mobile-marketplace-link:hover {
    background: rgba(255,255,255,.07);
    color: var(--cyan);
}

.mobile-marketplace-link i {
    width: 16px;
    text-align: center;
}



/* ============================================================
   NAV LOGIN
   ============================================================ */

.nav-login {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        11px 17px;

    border-radius: 12px;

    border: 1px solid
        rgba(0,229,255,.35);

    background:
        rgba(0,229,255,.07);

    color:
        var(--cyan);

    cursor: pointer;

    font-weight: 700;

    transition: .3s ease;

}

.nav-login:hover {

    background:
        rgba(0,229,255,.16);

    transform:
        translateY(-2px);

}


/* ============================================================
   MOBILE MENU BUTTON
   ============================================================ */

.mobile-menu-btn {

    display: none;

    width: 44px;
    height: 44px;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    border:
        1px solid
        rgba(255,255,255,.15);

    background:
        rgba(255,255,255,.07);

    color: white;

    cursor: pointer;

}


/* ============================================================
   MOBILE MENU
   ============================================================ */

.mobile-nav {

    position: fixed;

    top: 76px;

    right: 18px;

    width:
        min(320px, calc(100% - 36px));

    z-index: 120;

    padding: 12px;

    border-radius: 22px;

    background:
        rgba(7,11,17,.94);

    border:
        1px solid
        rgba(255,255,255,.12);

    box-shadow:
        0 30px 80px
        rgba(0,0,0,.6);

    backdrop-filter:
        blur(25px);

    opacity: 0;

    visibility: hidden;

    transform:
        translateY(-10px)
        scale(.98);

    transition: .25s ease;

}

.mobile-nav.open {

    opacity: 1;

    visibility: visible;

    transform:
        translateY(0)
        scale(1);

}

.mobile-nav button {

    width: 100%;

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 15px;

    margin-bottom: 5px;

    border: 0;

    border-radius: 13px;

    background: transparent;

    color: rgba(255,255,255,.78);

    cursor: pointer;

    text-align: left;

}

.mobile-nav button:hover {

    background:
        rgba(255,255,255,.07);

    color:
        var(--cyan);

}


/* ============================================================
   SLIDE COUNTER
   ============================================================ */

.slide-counter {

    position: absolute;

    z-index: 15;

    right:
        clamp(20px, 5vw, 70px);

    bottom: 48px;

    display: flex;

    align-items: center;

    gap: 12px;

    color: rgba(255,255,255,.7);

    font-size: .8rem;

    letter-spacing: .1em;

}

.slide-current {

    color: white;

    font-size: 1.3rem;

    font-weight: 800;

}

.counter-line {

    width: 65px;

    height: 1px;

    background:
        rgba(255,255,255,.35);

}


/* ============================================================
   SLIDE CONTROLS
   ============================================================ */

.slide-controls {

    position: absolute;

    z-index: 20;

    left:
        clamp(20px, 5vw, 70px);

    bottom: 73px;

    display: flex;

    gap: 8px;

}

.slide-control {

    width: 44px;
    height: 44px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    border:
        1px solid
        rgba(255,255,255,.18);

    background:
        rgba(0,0,0,.25);

    color: white;

    cursor: pointer;

    backdrop-filter:
        blur(15px);

    transition: .25s ease;

}

.slide-control:hover {

    background:
        rgba(0,229,255,.13);

    border-color:
        rgba(0,229,255,.5);

    color:
        var(--cyan);

}


/* ============================================================
   SLIDE PROGRESS
   ============================================================ */

.slide-progress {

    position: absolute;

    left: 0;
    bottom: 0;

    width: 100%;

    height: 3px;

    z-index: 30;

    background:
        rgba(255,255,255,.1);

}

.slide-progress-bar {

    width: 0%;

    height: 100%;

    background:
        linear-gradient(
            90deg,
            var(--cyan),
            var(--blue)
        );

}


/* ============================================================
   FLOATING LOGIN COMMAND
   ============================================================ */

.floating-login {

    position: fixed;

    right:
        clamp(18px, 4vw, 55px);

    bottom:
        clamp(90px, 10vh, 110px);

    z-index: 50;

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        13px 18px;

    border-radius: 999px;

    border:
        1px solid
        rgba(0,229,255,.35);

    background:
        rgba(4,9,14,.72);

    color: white;

    cursor: pointer;

    box-shadow:
        0 15px 45px
        rgba(0,0,0,.4);

    backdrop-filter:
        blur(18px);

    transition: .3s ease;

}

.floating-login i {

    color:
        var(--cyan);

}

.floating-login:hover {

    transform:
        translateY(-4px);

    border-color:
        var(--cyan);

    box-shadow:
        0 15px 45px
        rgba(0,229,255,.18);

}


/* ============================================================
   LEGAL LINKS BAR - NEW
   ============================================================ */

.legal-links-bar {

    position: fixed;

    left: 50%;

    bottom: 16px;

    transform: translateX(-50%);

    z-index: 60;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 14px;

    padding: 9px 16px;

    border-radius: 999px;

    border:
        1px solid
        rgba(255,255,255,.12);

    background:
        rgba(5,7,10,.52);

    backdrop-filter:
        blur(18px);

    -webkit-backdrop-filter:
        blur(18px);

    box-shadow:
        0 12px 35px
        rgba(0,0,0,.28);

    white-space: nowrap;

}

.legal-link {

    border: 0;

    background: transparent;

    color:
        rgba(255,255,255,.62);

    font-size: .72rem;

    cursor: pointer;

    padding: 3px 2px;

    transition: .25s ease;

}

.legal-link:hover {

    color:
        var(--cyan);

}

.legal-separator {

    width: 4px;
    height: 4px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.35);

}


/* ============================================================
   PAGE SECTIONS
   ============================================================ */

.page-section {

    position: fixed;

    inset: 0;

    z-index: 200;

    overflow-y: auto;

    overflow-x: hidden;

    background:

        radial-gradient(
            circle at 15% 15%,
            rgba(0,229,255,.08),
            transparent 30%
        ),

        radial-gradient(
            circle at 85% 75%,
            rgba(139,92,246,.09),
            transparent 35%
        ),

        #05080d;

    opacity: 0;

    visibility: hidden;

    transform:
        translateY(30px)
        scale(.985);

    transition:
        opacity .35s ease,
        transform .45s ease,
        visibility .35s;

}

.page-section.active {

    opacity: 1;

    visibility: visible;

    transform:
        translateY(0)
        scale(1);

}


/* ============================================================
   SECTION HEADER
   ============================================================ */

.section-bar {

    position: sticky;

    top: 0;

    z-index: 10;

    min-height: 82px;

    padding:
        0 clamp(20px, 5vw, 70px);

    display: flex;

    align-items: center;

    justify-content: space-between;

    background:
        rgba(4,8,13,.75);

    border-bottom:
        1px solid
        rgba(255,255,255,.09);

    backdrop-filter:
        blur(25px);

}

.section-title {

    display: flex;

    align-items: center;

    gap: 12px;

    font-size: 1rem;

    font-weight: 700;

}

.section-title i {

    color:
        var(--cyan);

}

.close-btn {

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    border:
        1px solid
        rgba(255,255,255,.15);

    background:
        rgba(255,255,255,.06);

    color: white;

    cursor: pointer;

    transition: .25s ease;

}

.close-btn:hover {

    color:
        var(--cyan);

    border-color:
        rgba(0,229,255,.5);

    transform:
        rotate(90deg);

}


/* ============================================================
   SECTION CONTENT
   ============================================================ */

.section-content {

    width: min(1200px, 100%);

    margin: auto;

    padding:
        70px clamp(20px, 5vw, 70px)
        100px;

}


/* ============================================================
   ABOUT
   ============================================================ */

.about-intro {

    max-width: 850px;

    margin-bottom: 55px;

}

.eyebrow {

    color:
        var(--cyan);

    text-transform:
        uppercase;

    font-size: .72rem;

    font-weight: 800;

    letter-spacing: .18em;

    margin-bottom: 15px;

}

.about-intro h1 {

    font-size:
        clamp(2.3rem, 5vw, 5rem);

    line-height: 1;

    letter-spacing: -.04em;

    margin-bottom: 25px;

}

.about-intro p {

    color:
        var(--muted);

    line-height: 1.8;

    font-size: 1rem;

}


/* ============================================================
   ABOUT CARDS
   ============================================================ */

.about-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

}

.about-card {

    min-height: 270px;

    padding: 30px;

    border-radius:
        var(--radius);

    border:
        1px solid
        var(--border);

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.07),
            rgba(255,255,255,.025)
        );

    backdrop-filter:
        blur(18px);

    box-shadow:
        var(--shadow);

}

.about-icon {

    width: 52px;
    height: 52px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 15px;

    background:
        rgba(0,229,255,.08);

    color:
        var(--cyan);

    margin-bottom: 24px;

}

.about-card h3 {

    font-size: 1.1rem;

    margin-bottom: 15px;

}

.about-card p {

    color:
        rgba(255,255,255,.63);

    line-height: 1.75;

    font-size: .92rem;

}


/* ============================================================
   CONTACT
   ============================================================ */

.contact-heading {

    max-width: 700px;

    margin-bottom: 45px;

}

.contact-heading h1 {

    font-size:
        clamp(2.3rem, 5vw, 4.5rem);

    line-height: 1;

    margin-bottom: 18px;

}

.contact-heading p {

    color:
        var(--muted);

    line-height: 1.7;

}

.contact-grid {

    display: grid;

    grid-template-columns:
        1.2fr .8fr;

    gap: 24px;

}

.contact-form,
.contact-info {

    border:
        1px solid
        var(--border);

    border-radius:
        var(--radius);

    background:
        rgba(255,255,255,.045);

    backdrop-filter:
        blur(20px);

    box-shadow:
        var(--shadow);

}

.contact-form {

    padding: 32px;

}

.contact-form input,
.contact-form select,
.contact-form textarea {

    width: 100%;

    border:
        1px solid
        rgba(255,255,255,.1);

    border-radius: 13px;

    padding:
        14px 16px;

    background:
        rgba(0,0,0,.25);

    color: white;

    outline: none;

    margin-bottom: 14px;

    transition: .25s ease;

}

.contact-form select option {

    background:
        #0b1118;

}

.contact-form input::placeholder,
.contact-form textarea::placeholder {

    color:
        rgba(255,255,255,.38);

}

.contact-form input:focus,
.contact-form select:focus,
.contact-form textarea:focus {

    border-color:
        rgba(0,229,255,.55);

    box-shadow:
        0 0 0 3px
        rgba(0,229,255,.07);

}

.contact-submit {

    width: 100%;

    border: 0;

    border-radius: 13px;

    padding: 15px;

    background:
        linear-gradient(
            135deg,
            var(--cyan),
            var(--blue)
        );

    color: #020508;

    font-weight: 800;

    cursor: pointer;

    transition: .3s ease;

}

.contact-submit:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 15px 35px
        rgba(0,229,255,.18);

}


/* ============================================================
   CONTACT INFO
   ============================================================ */

.contact-info {

    padding: 32px;

}

.contact-info h3 {

    margin-bottom: 25px;

}

.contact-item {

    display: flex;

    gap: 14px;

    padding: 18px 0;

    border-bottom:
        1px solid
        rgba(255,255,255,.07);

}

.contact-item:last-child {

    border-bottom: 0;

}

.contact-item-icon {

    flex: 0 0 42px;

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background:
        rgba(0,229,255,.07);

    color:
        var(--cyan);

}

.contact-item small {

    display: block;

    color:
        rgba(255,255,255,.4);

    margin-bottom: 4px;

}

.contact-item strong {

    display: block;

    font-size: .9rem;

    word-break: break-word;

}

.response-time {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-top: 20px;

    color:
        #6ee7b7;

    font-size: .78rem;

}


/* ============================================================
   LOGIN
   ============================================================ */

.login-wrapper {

    min-height:
        calc(100vh - 82px);

    display: flex;

    align-items: center;

    justify-content: center;

}

.login-card {

    width:
        min(440px, 100%);

    padding: 38px;

    border:
        1px solid
        rgba(0,229,255,.17);

    border-radius:
        28px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.075),
            rgba(255,255,255,.025)
        );

    box-shadow:
        0 40px 100px
        rgba(0,0,0,.55);

    backdrop-filter:
        blur(25px);

}

.login-brand {

    width: 62px;
    height: 62px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 18px;

    background:
        rgba(0,229,255,.08);

    color:
        var(--cyan);

    font-size: 1.5rem;

    margin-bottom: 22px;

}

.login-card h1 {

    font-size: 2rem;

    margin-bottom: 8px;

}

.login-card > p {

    color:
        rgba(255,255,255,.55);

    margin-bottom: 28px;

}

.input-group {

    position: relative;

    margin-bottom: 16px;

}

.input-group input {

    width: 100%;

    padding:
        15px
        45px
        15px
        46px;

    border:
        1px solid
        rgba(255,255,255,.1);

    border-radius: 13px;

    background:
        rgba(0,0,0,.25);

    color: white;

    outline: none;

}

.input-group input:focus {

    border-color:
        rgba(0,229,255,.5);

}

.input-icon {

    position: absolute;

    left: 16px;

    top: 50%;

    transform:
        translateY(-50%);

    color:
        var(--cyan);

}

.toggle-password {

    position: absolute;

    right: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    color:
        rgba(255,255,255,.5);

    cursor: pointer;

}

.login-submit {

    width: 100%;

    padding: 15px;

    margin-top: 5px;

    border: 0;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            var(--cyan),
            var(--blue)
        );

    color: #020508;

    font-weight: 900;

    cursor: pointer;

    transition: .3s ease;

}

.login-submit:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 15px 35px
        rgba(0,229,255,.18);

}

.login-footer {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    margin-top: 18px;

}

.login-footer a {

    color:
        rgba(255,255,255,.58);

    text-decoration: none;

    font-size: .8rem;

}

.login-footer a:hover {

    color:
        var(--cyan);

}


/* ============================================================
   LEGAL / POLICY PAGES - NEW
   ============================================================ */

.policy-container {

    width:
        min(950px, 100%);

    margin:
        0 auto;

}

.policy-hero {

    margin-bottom: 45px;

}

.policy-hero h1 {

    font-size:
        clamp(
            2.3rem,
            5vw,
            4.8rem
        );

    line-height: 1;

    letter-spacing:
        -.04em;

    margin-bottom: 20px;

}

.policy-hero p {

    max-width: 760px;

    color:
        var(--muted);

    line-height: 1.8;

}

.policy-card {

    padding:
        clamp(24px, 4vw, 45px);

    margin-bottom: 22px;

    border-radius:
        var(--radius);

    border:
        1px solid
        var(--border);

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.065),
            rgba(255,255,255,.02)
        );

    backdrop-filter:
        blur(20px);

    box-shadow:
        var(--shadow);

}

.policy-card h2 {

    display: flex;

    align-items: center;

    gap: 12px;

    font-size:
        clamp(1.15rem, 2vw, 1.45rem);

    margin-bottom: 18px;

}

.policy-card h2 i {

    color:
        var(--cyan);

}

.policy-card p {

    color:
        rgba(255,255,255,.68);

    line-height: 1.85;

    margin-bottom: 14px;

}

.policy-card p:last-child {

    margin-bottom: 0;

}

.policy-card ul {

    padding-left: 20px;

    color:
        rgba(255,255,255,.68);

    line-height: 1.85;

}

.policy-card li {

    margin-bottom: 8px;

}

.policy-contact-box {

    padding: 28px;

    border-radius: 20px;

    border:
        1px solid
        rgba(0,229,255,.18);

    background:
        rgba(0,229,255,.045);

}

.policy-contact-box strong {

    display: block;

    margin-bottom: 8px;

}

.policy-contact-box a {

    color:
        var(--cyan);

    text-decoration: none;

}


/* ============================================================
   EMPTY SLIDE STATE
   ============================================================ */

.no-slides {

    width: 100%;
    height: 100%;

    display: flex;

    align-items: center;
    justify-content: center;

    text-align: center;

    padding: 30px;

    background:
        radial-gradient(
            circle,
            rgba(0,229,255,.1),
            transparent 40%
        );

}

.no-slides-content {

    max-width: 600px;

}

.no-slides-content i {

    font-size: 3rem;

    color:
        var(--cyan);

    margin-bottom: 20px;

}

.no-slides-content h1 {

    font-size:
        clamp(2rem,5vw,4rem);

    margin-bottom: 15px;

}

.no-slides-content p {

    color:
        var(--muted);

}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 900px) {

    .desktop-nav {

        display: none;

    }

    .mobile-menu-btn {

        display: flex;

    }

    .about-grid {

        grid-template-columns:
            1fr;

    }

    .contact-grid {

        grid-template-columns:
            1fr;

    }

    .about-card {

        min-height: auto;

    }

}


@media (max-width: 768px) {

    .navigation {

        height: 76px;

    }

    .brand-name {

        max-width: 170px;

        font-size: .82rem;

    }

    .logo {

        width: 44px;
        height: 44px;

    }

    .slideoverlay {

        justify-content:
            flex-end;

        padding:
            0 22px 245px;

    }

    .slide::before {

        background:
            linear-gradient(
                to top,
                rgba(0,0,0,.92),
                rgba(0,0,0,.35) 60%,
                rgba(0,0,0,.2)
            );

    }

    #headline h1 {

        font-size:
            clamp(
                2.2rem,
                10vw,
                4rem
            );

    }

    #tagline {

        margin-top: 18px;

    }

    #tagline p {

        font-size: .9rem;

        line-height: 1.6;

    }

    .hero-actions {

        margin-top: 24px;

        flex-wrap: wrap;

    }

    .hero-action {

        padding:
            11px 14px;

        font-size: .8rem;

    }

    .slide-controls {

        left: 20px;

        bottom: 154px;

    }

    .slide-counter {

        right: 20px;

        bottom: 35px;

    }

    .counter-line {

        width: 35px;

    }

    .floating-login {

        right: 18px;

        bottom: 85px;

        padding:
            12px 15px;

        font-size: .8rem;

        margin-bottom: 30px;

    }


    /* LEGAL LINKS MOBILE */

    .legal-links-bar {

        bottom: 10px;

        padding:
            8px 13px;

        gap: 10px;

        width:
            calc(100% - 36px);

    }

    .legal-link {

        font-size:
            .66rem;

    }

    .section-content {

        padding:
            45px 20px 80px;

    }

    .section-bar {

        min-height: 76px;

    }

    .login-card {

        padding: 28px 20px;

    }

}


@media (max-width: 480px) {

    .brand-name {

        max-width: 125px;

    }

    .hero-actions {

        width: 100%;

    }

    .hero-action {

        flex: 1;

        justify-content: center;

    }

    .slide-control {

        width: 40px;
        height: 40px;

    }

    .legal-links-bar {

        gap: 7px;

        padding:
            7px 10px;

    }

    .legal-link {

        font-size:
            .61rem;

    }

}


/* ============================================================
   SCROLLBAR
   ============================================================ */

.page-section::-webkit-scrollbar {

    width: 6px;

}

.page-section::-webkit-scrollbar-track {

    background: transparent;

}

.page-section::-webkit-scrollbar-thumb {

    background:
        rgba(255,255,255,.15);

    border-radius: 20px;

}


/* ============================================================
   FORCE ALL FONT AWESOME ICONS TO WHITE
   ============================================================ */

i[class^="fa-"],
i[class*=" fa-"],
i.fas,
i.far,
i.fal,
i.fad,
i.fab,
i.fat,
i.fa-solid,
i.fa-regular,
i.fa-light,
i.fa-thin,
i.fa-duotone,
i.fa-brands {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}

.about-icon,
.contact-item-icon,
.section-title,
.login-brand,
.input-icon,
.toggle-password,
.floating-login,
.nav-link,
.nav-login,
.mobile-nav button,
.slide-control,
.hero-kicker,
.hero-action,
.close-btn,
.no-slides-content,
.policy-card h2 {
    color: #ffffff !important;
}

.about-icon i,
.contact-item-icon i,
.section-title i,
.login-brand i,
.input-icon,
.toggle-password,
.floating-login i,
.nav-link i,
.nav-login i,
.mobile-nav button i,
.slide-control i,
.hero-kicker i,
.hero-action i,
.close-btn i,
.no-slides-content i,
.policy-card h2 i {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}

</style>

</head>


<body>

<div id="wrapper">


<!-- =========================================================
     MAIN SLIDER
     ========================================================= -->

<div class="slider">


<!-- =========================================================
     MAIN NAVIGATION
     ========================================================= -->

<header class="navigation">

    <div class="brand">

        <div class="logo">

            <?php if (!empty($logoPath)): ?>

                <img
                    src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?= htmlspecialchars($company_name ?? 'Company'); ?>"
                >

            <?php else: ?>

                <i class="fa-solid fa-building"></i>

            <?php endif; ?>

        </div>

        <div class="brand-name">

            <?= htmlspecialchars($company_name ?? 'Company'); ?>

        </div>

    </div>


    <nav class="desktop-nav">

        <button
            class="nav-link active"
            onclick="goHome()"
        >
            <i class="fa-solid fa-house"></i>
            Home
        </button>

        <button
            class="nav-link"
            onclick="openSection('aboutus')"
        >
            <i class="fa-solid fa-circle-info"></i>
            About
        </button>

        <button
            class="nav-link"
            onclick="openSection('contactus')"
        >
            <i class="fa-solid fa-envelope"></i>
            Contact
        </button>

        <a
            class="nav-link marketplace-nav-link"
            href="marketplace.php"
        >
            <i class="fa-solid fa-store"></i>
            Marketplace
        </a>

        <button
            class="nav-login"
            onclick="openSection('login')"
        >
            <i class="fa-solid fa-right-to-bracket"></i>
            Login
        </button>

    </nav>


    <button
        class="mobile-menu-btn"
        id="mobileMenuBtn"
        aria-label="Open navigation"
    >
        <i class="fa-solid fa-bars"></i>
    </button>

</header>


<!-- =========================================================
     MOBILE NAVIGATION
     ========================================================= -->

<nav
    class="mobile-nav"
    id="mobileNav"
>

    <button onclick="goHome()">

        <i class="fa-solid fa-house"></i>

        <span>Home</span>

    </button>


    <button onclick="openSection('aboutus')">

        <i class="fa-solid fa-circle-info"></i>

        <span>About Us</span>

    </button>


    <button onclick="openSection('contactus')">

        <i class="fa-solid fa-envelope"></i>

        <span>Contact Us</span>

    </button>


    <a
        class="mobile-marketplace-link"
        href="marketplace.php"
    >

        <i class="fa-solid fa-store"></i>

        <span>Marketplace</span>

    </a>


    <button onclick="openSection('login')">

        <i class="fa-solid fa-user-shield"></i>

        <span>Login</span>

    </button>

</nav>


<!-- =========================================================
     SLIDES
     ========================================================= -->

<div class="slides home-section">


<?php if ($totalSlides > 0): ?>


<?php foreach ($slides as $index => $slide): ?>

<?php

$desktopBg = slideAssetPath(
    $slide['desktop_image'] ?? ''
);

$mobileBg = slideAssetPath(
    $slide['mobile_image'] ?? ''
);

if ($mobileBg === '') {
    $mobileBg = $desktopBg;
}

?>

<div
    class="slide"
    data-index="<?= $index; ?>"
    style="
        --desktop-bg: url('<?= htmlspecialchars($desktopBg, ENT_QUOTES, 'UTF-8'); ?>');
        --mobile-bg: url('<?= htmlspecialchars($mobileBg, ENT_QUOTES, 'UTF-8'); ?>');
    "
>


<div class="slideoverlay">


    <div class="hero-kicker">

        <i class="fa-solid fa-circle"></i>

        <?= htmlspecialchars($company_name ?? 'Company'); ?>

    </div>


    <div id="headline">

        <h1>
            <?= htmlspecialchars(
                $slide['headline'] ?? ''
            ); ?>
        </h1>

    </div>


    <div id="tagline">

        <p>
            <?= htmlspecialchars(
                $slide['description'] ?? ''
            ); ?>
        </p>

    </div>


    <div class="hero-actions">

        <button
            class="hero-action primary"
            onclick="openSection('aboutus')"
        >

            <i class="fa-solid fa-arrow-right"></i>

            Discover More

        </button>


        <button
            class="hero-action"
            onclick="openSection('contactus')"
        >

            <i class="fa-solid fa-paper-plane"></i>

            Contact Us

        </button>

    </div>


</div>

</div>

<?php endforeach; ?>


<?php else: ?>


<div class="no-slides">

    <div class="no-slides-content">

        <i class="fa-solid fa-layer-group"></i>

        <h1>
            <?= htmlspecialchars(
                $company_name ?? 'Welcome'
            ); ?>
        </h1>

        <p>
            Welcome to our official digital presence.
            More information will be available soon.
        </p>

    </div>

</div>


<?php endif; ?>


</div>


<!-- =========================================================
     SLIDE CONTROLS
     ========================================================= -->

<?php if ($totalSlides > 1): ?>

<div class="slide-controls">

    <button
        class="slide-control"
        id="prevSlide"
        aria-label="Previous slide"
    >

        <i class="fa-solid fa-arrow-left"></i>

    </button>


    <button
        class="slide-control"
        id="nextSlide"
        aria-label="Next slide"
    >

        <i class="fa-solid fa-arrow-right"></i>

    </button>

</div>


<div class="slide-counter">

    <span
        class="slide-current"
        id="slideCurrent"
    >
        01
    </span>

    <span class="counter-line"></span>

    <span>
        <?= str_pad($totalSlides, 2, '0', STR_PAD_LEFT); ?>
    </span>

</div>


<div class="slide-progress">

    <div
        class="slide-progress-bar"
        id="slideProgress"
    ></div>

</div>

<?php endif; ?>


</div>


<!-- =========================================================
     FLOATING STAFF LOGIN
     ========================================================= -->

<button
    class="floating-login"
    onclick="openSection('login')">

    <i class="fa-solid fa-user-lock"></i>

    <span>Login Portal</span>

</button>


<!-- =========================================================
     LEGAL LINKS AT BOTTOM - NEW
     ========================================================= -->
<?php
// =========================================================
// ACTIVE DOMAIN
// =========================================================

$active_domain = $_SESSION['domain'] ?? '';

// Normalize the active domain
$active_domain = trim(strtolower($active_domain));

// Remove protocol if present
$active_domain = preg_replace('#^https?://#', '', $active_domain);

// Remove www.
$active_domain = preg_replace('#^www\.#', '', $active_domain);

// Remove path, query string, or fragment
$active_domain = explode('/', $active_domain)[0];
$active_domain = explode('?', $active_domain)[0];
$active_domain = explode('#', $active_domain)[0];


// =========================================================
// DEBUG — SHOW ACTIVE DOMAIN
// Remove this section when everything is confirmed
// =========================================================



// =========================================================
// LEGAL LINKS — UBAMUS.COM ONLY
// =========================================================

if ($active_domain === 'ubamus.com'):
?>

<!-- =========================================================
     LEGAL LINKS AT BOTTOM - UBAMUS ONLY
     ========================================================= -->

<div class="legal-links-bar">

    <button
        class="legal-link"
        onclick="openSection('privacy')"
    >
        Privacy Policy
    </button>

    <span class="legal-separator"></span>

    <button
        class="legal-link"
        onclick="openSection('terms')"
    >
        Terms & Conditions
    </button>
    <span class="legal-separator"></span>
    <button
        class="legal-link"
       onclick="openSection('contactus')"
    >
        Contact Us
    </button>

</div>

<?php endif; ?>
<!-- =========================================================
     ABOUT SECTION
     ========================================================= -->

<section
    id="aboutus"
    class="page-section"
>


<div class="section-bar">

    <div class="section-title">

        <i class="fa-solid fa-circle-info"></i>

        <span>About Us</span>

    </div>


    <button
        class="close-btn"
        onclick="closeSection()"
        aria-label="Close"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>

</div>


<div class="section-content">


    <?php if (!empty($displayAboutSections)): ?>

    <div class="about-grid">

        <?php foreach ($displayAboutSections as $profileSection): ?>

        <article class="about-card">
            <div class="about-icon">
                <i class="fa-solid <?= htmlspecialchars(publicAboutSectionIcon($profileSection['title']), ENT_QUOTES, 'UTF-8'); ?>"></i>
            </div>
            <h3><?= htmlspecialchars($profileSection['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?= nl2br(htmlspecialchars($profileSection['content'], ENT_QUOTES, 'UTF-8')); ?></p>
        </article>

        <?php endforeach; ?>

    </div>

    <?php endif; ?>

    </div>


</div>

</section>


<!-- =========================================================
     CONTACT SECTION
     ========================================================= -->

<section
    id="contactus"
    class="page-section"
>

<div class="section-bar">


<div class="section-title">

    <i class="fa-solid fa-paper-plane"></i>

    <span>Contact Us</span>

</div>


<button
    class="close-btn"
    onclick="closeSection()"
>

    <i class="fa-solid fa-xmark"></i>

</button>


</div>

<div class="section-content">


<div class="contact-heading">

    <div class="eyebrow">
        Let's Connect
    </div>

    <h1>
        Start a conversation.
    </h1>

    <p>
        Reach our team for support,
        onboarding, partnerships,
        sales enquiries or general business
        enquiries.
    </p>

</div>


<div class="contact-grid">


    <form
        class="contact-form"
        method="POST"
        action="proceed-contact.php"
    >

        <input
            type="hidden"
            name="company_id"
            value="<?= htmlspecialchars(
                $company_id ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
        >


        <input
            type="text"
            name="fullname"
            placeholder="Full Name"
            maxlength="150"
            autocomplete="name"
            required
        >


        <input
            type="email"
            name="email"
            placeholder="Email Address"
            maxlength="150"
            autocomplete="email"
            required
        >


        <input
            type="text"
            name="business_name"
            placeholder="Company / Business Name"
            maxlength="200"
            autocomplete="organization"
        >


        <select
            name="subject"
            required
        >

            <option value="">
                Select Subject
            </option>

            <option value="Employment Opportunity Enquiry">
                Employment Opportunity Enquiry
            </option>

            <option value="General Inquiry">
                General Enquiry
            </option>

            <option value="General Sales Enquiries">
                General Sales Enquiries
            </option>

            <option value="Partnership / Collaboration Enquiries">
                Partnership / Collaboration Enquiries
            </option>

            <option value="Technical Support Required">
                Technical Support Required
            </option>

        </select>


        <textarea
            name="message"
            rows="6"
            maxlength="5000"
            placeholder="Tell us how we can help..."
            required
        ></textarea>


        <button
            type="submit"
            class="contact-submit"
        >

            <i class="fa-solid fa-paper-plane"></i>

            Send Message

        </button>


    </form>


    <div class="contact-info">


        <h3>
            Other ways to reach us
        </h3>


        <div class="contact-item">

            <div class="contact-item-icon">

                <i class="fa-solid fa-headset"></i>

            </div>


            <div>

                <small>
                    Customer Care
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $company_customer_care_email ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </div>

        </div>


        <div class="contact-item">

            <div class="contact-item-icon">

                <i class="fa-solid fa-envelope"></i>

            </div>


            <div>

                <small>
                    Business Email
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $company_email ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </div>

        </div>


        <div class="contact-item">

            <div class="contact-item-icon">

                <i class="fa-brands fa-whatsapp"></i>

            </div>


            <div>

                <small>
                    WhatsApp / Mobile
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $company_customer_mobile ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </div>

        </div>


        <span class="response-time">

            <i class="fa-solid fa-circle-check"></i>

            Typically responds within 24 hours

        </span>


    </div>


</div>


</div>

</section>


<!-- =========================================================
     LOGIN SECTION
     ========================================================= -->

<section
    id="login"
    class="page-section"
>


<div class="section-bar">

    <div class="section-title">

        <i class="fa-solid fa-user-shield"></i>

        <span>Login Portal</span>

    </div>


    <button
        class="close-btn"
        onclick="closeSection()"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>

</div>


<div class="section-content login-wrapper">


<div class="login-card">


    <div class="login-brand">

        <i class="fa-solid fa-user-shield"></i>

    </div>


    <h1>
        Control Area
    </h1>


    <p>
        Secure access to your company workspace.
    </p>


    <form
        method="POST"
        id="loginForm"
        action="controllers/auth.php"
    >


        <div class="input-group">

            <i
                class="fa-solid fa-envelope input-icon"
            ></i>

            <input
                type="email"
                name="email"
                placeholder="Email Address"
                autocomplete="username"
                required
            >

        </div>


        <div class="input-group">

            <i
                class="fa-solid fa-lock input-icon"
            ></i>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Password"
                autocomplete="current-password"
                required
            >

            <i
                class="fa-solid fa-eye toggle-password"
                id="togglePassword"
            ></i>

        </div>


        <button
            type="submit"
            class="login-submit"
        >

            <i class="fa-solid fa-right-to-bracket"></i>

            Sign In

        </button>


        <div class="login-footer">

            <a href="terminal/forget_password.php">

                <i class="fa-solid fa-key"></i>

                Forgot Password?

            </a>


            <?php if (
                isset($company_domain) &&
                strtolower(trim($company_domain)) === 'ubamus.com'
            ): ?>

                <a href="controllers/registration.php">

                    <i class="fa-solid fa-user-plus"></i>

                    Register

                </a>

            <?php endif; ?>

        </div>


    </form>


</div>


</div>

</section>


<!-- =========================================================
     PRIVACY POLICY SECTION - NEW
     ========================================================= -->

<section
    id="privacy"
    class="page-section"
>

<div class="section-bar">

    <div class="section-title">

        <i class="fa-solid fa-shield-halved"></i>

        <span>Privacy Policy</span>

    </div>

    <button
        class="close-btn"
        onclick="closeSection()"
        aria-label="Close"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>

</div>


<div class="section-content">

<div class="policy-container">


    <div class="policy-hero">

        <div class="eyebrow">
            <?= htmlspecialchars($privacyPolicy['eyebrow'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <h1>
            <?= htmlspecialchars($privacyPolicy['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h1>

        <p>
            <?= nl2br(htmlspecialchars($privacyPolicy['intro'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </p>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-circle-info"></i>
            <?= htmlspecialchars($privacyPolicy['information_collect_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars($privacyPolicy['information_collect_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-gears"></i>
            <?= htmlspecialchars($privacyPolicy['information_use_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars($privacyPolicy['information_use_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-lock"></i>
            <?= htmlspecialchars($privacyPolicy['security_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars($privacyPolicy['security_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-share-nodes"></i>
            <?= htmlspecialchars($privacyPolicy['sharing_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars($privacyPolicy['sharing_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-cookie-bite"></i>
            <?= htmlspecialchars($privacyPolicy['cookies_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars($privacyPolicy['cookies_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-rotate"></i>
            <?= htmlspecialchars($privacyPolicy['changes_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars($privacyPolicy['changes_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-envelope"></i>
            <?= htmlspecialchars($privacyPolicy['contact_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div class="policy-contact-box">

            <?= nl2br(htmlspecialchars($privacyPolicy['contact_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>

        </div>

    </div>


</div>

</div>

</section>


<!-- =========================================================
     TERMS & CONDITIONS SECTION - NEW
     ========================================================= -->

<section
    id="terms"
    class="page-section"
>

<div class="section-bar">

    <div class="section-title">

        <i class="fa-solid fa-file-contract"></i>

        <span>Terms & Conditions</span>

    </div>

    <button
        class="close-btn"
        onclick="closeSection()"
        aria-label="Close"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>

</div>


<div class="section-content">

<div class="policy-container">


<?php if (!empty($termsConditions)): ?>


    <div class="policy-hero">

        <div class="eyebrow">
            <?= htmlspecialchars(
                $termsConditions['eyebrow'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </div>

        <h1>
            <?= htmlspecialchars(
                $termsConditions['title'] ?? 'Terms & Conditions',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h1>

        <p>
            <?= nl2br(htmlspecialchars(
                $termsConditions['intro'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </p>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-check"></i>
            <?= htmlspecialchars(
                $termsConditions['acceptance_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars(
                $termsConditions['acceptance_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-display"></i>
            <?= htmlspecialchars(
                $termsConditions['website_use_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars(
                $termsConditions['website_use_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-copyright"></i>
            <?= htmlspecialchars(
                $termsConditions['intellectual_property_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars(
                $termsConditions['intellectual_property_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?= htmlspecialchars(
                $termsConditions['website_information_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars(
                $termsConditions['website_information_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-scale-balanced"></i>
            <?= htmlspecialchars(
                $termsConditions['liability_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars(
                $termsConditions['liability_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-link"></i>
            <?= htmlspecialchars(
                $termsConditions['third_party_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars(
                $termsConditions['third_party_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-pen-to-square"></i>
            <?= htmlspecialchars(
                $termsConditions['changes_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div>
            <?= nl2br(htmlspecialchars(
                $termsConditions['changes_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>
        </div>

    </div>


    <div class="policy-card">

        <h2>
            <i class="fa-solid fa-envelope"></i>
            <?= htmlspecialchars(
                $termsConditions['contact_title'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </h2>

        <div class="policy-contact-box">

            <?= nl2br(htmlspecialchars(
                $termsConditions['contact_content'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )); ?>

        </div>

    </div>


<?php else: ?>


    <div class="policy-hero">

        <div class="eyebrow">
            Website Terms
        </div>

        <h1>
            Terms & Conditions
        </h1>

        <p>
            Terms and Conditions information is not available at the moment.
        </p>

    </div>


<?php endif; ?>


</div>

</div>

</section>


</div>


<?php if (isset($_SESSION['error'])): ?>

<script>

alert(
    <?= json_encode($_SESSION['error']); ?>
);

</script>

<?php

unset($_SESSION['error']);

endif;

?>


<script>

/* ============================================================
   FUTURISTIC WEBSITE CONTROLLER
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {


    const slidesContainer =
        document.querySelector(".slides");

    const slides =
        document.querySelectorAll(".slide");

    const totalSlides =
        slides.length;

    const prevButton =
        document.getElementById("prevSlide");

    const nextButton =
        document.getElementById("nextSlide");

    const slideCurrent =
        document.getElementById("slideCurrent");

    const progress =
        document.getElementById("slideProgress");

    const mobileMenuBtn =
        document.getElementById("mobileMenuBtn");

    const mobileNav =
        document.getElementById("mobileNav");

    const togglePassword =
        document.getElementById("togglePassword");

    const password =
        document.getElementById("password");


    let currentIndex = 0;

    const SLIDE_INTERVAL = 15000;

    let interval = null;


    function pad(number) {

        return String(number).padStart(2, "0");

    }


    function updateCounter() {

        if (slideCurrent) {

            slideCurrent.textContent =
                pad(currentIndex + 1);

        }

    }


    function resetProgress() {

        if (!progress) {
            return;
        }

        progress.style.transition = "none";

        progress.style.width = "0%";

        requestAnimationFrame(function () {

            requestAnimationFrame(function () {

                progress.style.transition =
                    `width ${SLIDE_INTERVAL}ms linear`;

                progress.style.width = "100%";

            });

        });

    }


    function goToSlide(index) {

        if (
            !slidesContainer ||
            totalSlides <= 0
        ) {
            return;
        }


        currentIndex =
            (index + totalSlides) %
            totalSlides;


        const percentage =
            (currentIndex * 100) /
            totalSlides;


        slidesContainer.style.transform =
            `translateX(-${percentage}%)`;


        updateCounter();

        resetProgress();

    }


    function nextSlide() {

        goToSlide(
            currentIndex + 1
        );

    }


    function prevSlide() {

        goToSlide(
            currentIndex - 1
        );

    }


    function startSlider() {

        if (totalSlides <= 1) {
            return;
        }

        clearInterval(interval);

        interval =
            setInterval(
                nextSlide,
                SLIDE_INTERVAL
            );

        resetProgress();

    }


    function restartSlider() {

        clearInterval(interval);

        startSlider();

    }


    if (totalSlides > 1) {

        goToSlide(0);

        startSlider();

    }


    if (nextButton) {

        nextButton.addEventListener(
            "click",
            function () {

                nextSlide();

                restartSlider();

            }
        );

    }


    if (prevButton) {

        prevButton.addEventListener(
            "click",
            function () {

                prevSlide();

                restartSlider();

            }
        );

    }


    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "ArrowRight") {

                nextSlide();

                restartSlider();

            }

            if (event.key === "ArrowLeft") {

                prevSlide();

                restartSlider();

            }

            if (event.key === "Escape") {

                closeSection();

            }

        }
    );


    /* MOBILE NAVIGATION */

    if (mobileMenuBtn) {

        mobileMenuBtn.addEventListener(
            "click",
            function () {

                mobileNav.classList.toggle(
                    "open"
                );


                const icon =
                    mobileMenuBtn.querySelector("i");


                if (
                    mobileNav.classList.contains(
                        "open"
                    )
                ) {

                    icon.className =
                        "fa-solid fa-xmark";

                } else {

                    icon.className =
                        "fa-solid fa-bars";

                }

            }
        );

    }


    /* PASSWORD TOGGLE */

    if (
        togglePassword &&
        password
    ) {

        togglePassword.addEventListener(
            "click",
            function () {

                const isPassword =
                    password.type === "password";


                password.type =
                    isPassword
                        ? "text"
                        : "password";


                this.classList.toggle(
                    "fa-eye"
                );

                this.classList.toggle(
                    "fa-eye-slash"
                );

            }
        );

    }


    /* CLOSE MOBILE MENU WHEN CLICKING OUTSIDE */

    document.addEventListener(
        "click",
        function (event) {

            if (
                mobileNav &&
                mobileMenuBtn &&
                !mobileNav.contains(event.target) &&
                !mobileMenuBtn.contains(event.target)
            ) {

                mobileNav.classList.remove(
                    "open"
                );


                const icon =
                    mobileMenuBtn.querySelector("i");


                if (icon) {

                    icon.className =
                        "fa-solid fa-bars";

                }

            }

        }
    );


});


/* ============================================================
   OPEN SECTION
   ============================================================ */

function openSection(id) {

    const section =
        document.getElementById(id);


    if (!section) {
        return;
    }


    document
        .querySelectorAll(".page-section")
        .forEach(function (item) {

            item.classList.remove(
                "active"
            );

        });


    section.classList.add(
        "active"
    );


    section.scrollTop = 0;


    const mobileNav =
        document.getElementById("mobileNav");

    const mobileMenuBtn =
        document.getElementById("mobileMenuBtn");


    if (mobileNav) {

        mobileNav.classList.remove(
            "open"
        );

    }


    if (mobileMenuBtn) {

        const icon =
            mobileMenuBtn.querySelector("i");

        if (icon) {

            icon.className =
                "fa-solid fa-bars";

        }

    }


    document
        .querySelectorAll(".nav-link")
        .forEach(function (link) {

            link.classList.remove(
                "active"
            );

        });


    if (id === "aboutus") {

        const aboutLink =
            document.querySelector(
                '.nav-link[onclick*="aboutus"]'
            );

        if (aboutLink) {

            aboutLink.classList.add(
                "active"
            );

        }

    }


    if (id === "contactus") {

        const contactLink =
            document.querySelector(
                '.nav-link[onclick*="contactus"]'
            );

        if (contactLink) {

            contactLink.classList.add(
                "active"
            );

        }

    }

}


/* ============================================================
   CLOSE SECTION
   ============================================================ */

function closeSection() {

    document
        .querySelectorAll(".page-section")
        .forEach(function (section) {

            section.classList.remove(
                "active"
            );

        });


    setHomeNavigation();

}


/* ============================================================
   RETURN TO HOME
   ============================================================ */

function goHome() {

    closeSection();

    setHomeNavigation();

}


/* ============================================================
   HOME NAVIGATION STATE
   ============================================================ */

function setHomeNavigation() {

    document
        .querySelectorAll(".nav-link")
        .forEach(function (link) {

            link.classList.remove(
                "active"
            );

        });


    const home =
        document.querySelector(
            '.nav-link[onclick="goHome()"]'
        );


    if (home) {

        home.classList.add(
            "active"
        );

    }


    const mobileNav =
        document.getElementById("mobileNav");

    const mobileMenuBtn =
        document.getElementById("mobileMenuBtn");


    if (mobileNav) {

        mobileNav.classList.remove(
            "open"
        );

    }


    if (mobileMenuBtn) {

        const icon =
            mobileMenuBtn.querySelector("i");

        if (icon) {

            icon.className =
                "fa-solid fa-bars";

        }

    }

}

</script>

</body>
</html>
