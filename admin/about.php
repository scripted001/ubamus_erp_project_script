<?php
declare(strict_types=1);
date_default_timezone_set('Africa/Lagos');

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_email = $_SESSION['user_email'] ?? '';
$approved_by = "Not Approved!";

if ($company_id <= 0) {
    http_response_code(403);
    exit('Your company session could not be verified. Please sign in again.');
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('The PDO connection ($pdo) was not found in config/db.php.');
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (empty($_SESSION['about_profile_csrf'])) {
    $_SESSION['about_profile_csrf'] = bin2hex(random_bytes(32));
}

$aboutStmt = $pdo->prepare('SELECT id, who_we_are, our_vision, our_mission FROM about WHERE company_id = :company_id LIMIT 1');
$aboutStmt->execute([':company_id' => $company_id]);
$aboutRecord = $aboutStmt->fetch(PDO::FETCH_ASSOC) ?: null;
$sections = [];

if ($aboutRecord) {
    $sectionStmt = $pdo->prepare('SELECT id, section_title, section_content, display_order FROM about_sections WHERE company_id = :company_id AND about_id = :about_id ORDER BY display_order ASC, id ASC');
    $sectionStmt->execute([':company_id' => $company_id, ':about_id' => (int)$aboutRecord['id']]);
    $sections = $sectionStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Migrate the existing fixed About fields into the new editor view without deleting them.
if (!$sections && $aboutRecord) {
    $legacySections = [
        ['title' => 'Who We Are', 'content' => (string)($aboutRecord['who_we_are'] ?? '')],
        ['title' => 'Our Vision', 'content' => (string)($aboutRecord['our_vision'] ?? '')],
        ['title' => 'Our Mission', 'content' => (string)($aboutRecord['our_mission'] ?? '')],
    ];
    foreach ($legacySections as $legacy) {
        if (trim($legacy['content']) !== '') {
            $sections[] = ['section_title' => $legacy['title'], 'section_content' => $legacy['content']];
        }
    }
}
if (!$sections) {
    $sections = [['section_title' => 'Company Overview', 'section_content' => '']];
}

$flashMessage = (string)($_SESSION['about_profile_flash'] ?? '');
$flashType = (string)($_SESSION['about_profile_flash_type'] ?? 'success');
unset($_SESSION['about_profile_flash'], $_SESSION['about_profile_flash_type']);

if (!function_exists('about_profile_h')) {
    function about_profile_h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
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
    <?php echo htmlspecialchars($company_name); ?> | Staff Area
</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">

<style>

    /* =========================================================
       ABOUT US PAGE
    ========================================================= */

    .about-page {
        position: relative;
        width: 100%;
        overflow: hidden;
    }

    /* =========================================================
       ABSTRACT BACKGROUND
    ========================================================= */

    .about-decoration {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
        z-index: 0;
    }

    .about-orb {
        position: absolute;
        border-radius: 9999px;
        filter: blur(1px);
        opacity: .35;
    }

    .about-orb.one {
        width: 260px;
        height: 260px;
        top: -100px;
        right: -80px;
        background: linear-gradient(
            135deg,
            rgba(52, 24, 147, .18),
            rgba(6, 182, 212, .12)
        );
    }

    .about-orb.two {
        width: 190px;
        height: 190px;
        bottom: 80px;
        left: -90px;
        background: linear-gradient(
            135deg,
            rgba(6, 182, 212, .14),
            rgba(52, 24, 147, .15)
        );
    }

    .about-ring {
        position: absolute;
        border: 1px solid rgba(52, 24, 147, .10);
        border-radius: 9999px;
    }

    .about-ring.one {
        width: 360px;
        height: 360px;
        top: 40px;
        right: -170px;
    }

    .about-ring.two {
        width: 240px;
        height: 240px;
        bottom: -100px;
        left: -100px;
    }

    .about-grid {
        position: absolute;
        width: 320px;
        height: 220px;
        top: 20px;
        left: -40px;
        opacity: .18;
        background-image:
            linear-gradient(
                rgba(52, 24, 147, .25) 1px,
                transparent 1px
            ),
            linear-gradient(
                90deg,
                rgba(52, 24, 147, .25) 1px,
                transparent 1px
            );
        background-size: 28px 28px;
        transform: rotate(-8deg);
    }

    /* =========================================================
       PAGE CONTENT
    ========================================================= */

    .about-content {
        position: relative;
        z-index: 2;
    }

    /* =========================================================
       PAGE HEADER
    ========================================================= */

    .about-header {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(229, 231, 235, .9);
        background: rgba(255, 255, 255, .88);
        backdrop-filter: blur(12px);
        border-radius: 22px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 12px 35px rgba(15, 23, 42, .06);
    }

    .about-header-content {
        position: relative;
        z-index: 2;
        max-width: 760px;
    }

    .about-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #341893;
        margin-bottom: 8px;
    }

    .about-eyebrow-dot {
        width: 8px;
        height: 8px;
        border-radius: 9999px;
        background: #341893;
        box-shadow: 0 0 0 5px rgba(52, 24, 147, .08);
    }

    .about-title {
        margin: 0;
        font-size: clamp(1.55rem, 3vw, 2.25rem);
        line-height: 1.15;
        font-weight: 800;
        color: #111827;
    }

    .about-subtitle {
        margin-top: 10px;
        max-width: 680px;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.7;
    }

    .about-header-shape {
        position: absolute;
        right: -30px;
        top: -50px;
        width: 190px;
        height: 190px;
        border: 18px solid rgba(52, 24, 147, .045);
        border-radius: 50%;
    }

    .about-header-shape::after {
        content: "";
        position: absolute;
        width: 70px;
        height: 70px;
        right: 25px;
        bottom: 10px;
        border-radius: 20px;
        border: 1px solid rgba(6, 182, 212, .15);
        transform: rotate(25deg);
    }

    /* =========================================================
       FORM CARD
    ========================================================= */

    .about-form-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(229, 231, 235, .9);
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(12px);
        border-radius: 22px;
        padding: clamp(18px, 3vw, 30px);
        box-shadow: 0 15px 40px rgba(15, 23, 42, .06);
    }

    .about-form-decoration {
        position: absolute;
        top: 0;
        right: 0;
        width: 180px;
        height: 180px;
        pointer-events: none;
    }

    .about-form-decoration::before,
    .about-form-decoration::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(52, 24, 147, .08);
    }

    .about-form-decoration::before {
        width: 150px;
        height: 150px;
        right: -75px;
        top: -75px;
    }

    .about-form-decoration::after {
        width: 90px;
        height: 90px;
        right: 5px;
        top: -45px;
    }

    .about-form-inner {
        position: relative;
        z-index: 2;
    }

    /* =========================================================
       FORM GROUP
    ========================================================= */

    .about-form-group {
        margin-bottom: 24px;
    }

    .about-label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 9px;
        color: #374151;
        font-size: 14px;
        font-weight: 700;
    }

    .about-label-icon {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: rgba(52, 24, 147, .07);
        color: #341893;
        flex-shrink: 0;
    }

    .about-label small {
        margin-left: auto;
        font-weight: 500;
        color: #9ca3af;
        font-size: 11px;
    }

    .about-input {
        display: block;
        width: 100%;
        min-height: 50px;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        background: #f9fafb;
        padding: 13px 15px;
        color: #1f2937;
        font-size: 14px;
        line-height: 1.6;
        resize: vertical;
        transition:
            border-color .2s ease,
            box-shadow .2s ease,
            background .2s ease;
        box-sizing: border-box;
    }

    .about-input:hover {
        border-color: #b8b9c0;
    }

    .about-input:focus {
        outline: none;
        background: #fff;
        border-color: #341893;
        box-shadow: 0 0 0 4px rgba(52, 24, 147, .08);
    }

    .about-textarea-vision,
    .about-textarea-mission {
        min-height: 145px;
    }

    .about-textarea-bio {
        min-height: 190px;
    }

    /* =========================================================
       SECTION DIVIDER
    ========================================================= */

    .about-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 4px 0 24px;
    }

    .about-divider-line {
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }

    .about-divider-text {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #9ca3af;
        white-space: nowrap;
    }

    /* =========================================================
       SUBMIT
    ========================================================= */

    .about-submit-area {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding-top: 4px;
    }

    .about-save-note {
        display: flex;
        align-items: center;
        gap: 9px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.5;
    }

    .about-save-note i {
        color: #341893;
    }

    .about-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-width: 190px;
        min-height: 48px;
        padding: 12px 22px;
        border: none;
        border-radius: 11px;
        background: #341893;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition:
            transform .2s ease,
            background .2s ease,
            box-shadow .2s ease;
        box-shadow: 0 8px 18px rgba(52, 24, 147, .18);
    }

    .about-submit:hover {
        background: #2b147a;
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(52, 24, 147, .22);
    }

    .about-submit:active {
        transform: translateY(0);
    }

    /* =========================================================
       DARK MODE
    ========================================================= */

    .dark .about-header,
    .dark .about-form-card {
        background: rgba(17, 24, 39, .82);
        border-color: rgba(55, 65, 81, .8);
        box-shadow: none;
    }

    .dark .about-title {
        color: #f9fafb;
    }

    .dark .about-subtitle {
        color: #9ca3af;
    }

    .dark .about-label {
        color: #d1d5db;
    }

    .dark .about-input {
        background: rgba(17, 24, 39, .75);
        border-color: #374151;
        color: #f9fafb;
    }

    .dark .about-input:focus {
        background: #111827;
        border-color: #341893;
    }

    .dark .about-divider-line {
        background: #374151;
    }

    .dark .about-save-note {
        color: #9ca3af;
    }

    /* =========================================================
       TABLET
    ========================================================= */

    @media (max-width: 768px) {

        .about-header {
            padding: 20px;
            border-radius: 18px;
        }

        .about-form-card {
            border-radius: 18px;
            padding: 20px;
        }

        .about-header-shape {
            opacity: .5;
            transform: scale(.8);
        }

        .about-submit-area {
            align-items: stretch;
            flex-direction: column;
        }

        .about-save-note {
            justify-content: center;
            text-align: center;
        }

        .about-submit {
            width: 100%;
        }

    }

    /* =========================================================
       MOBILE
    ========================================================= */

    @media (max-width: 480px) {

        .about-header {
            padding: 18px;
            margin-bottom: 16px;
            border-radius: 16px;
        }

        .about-title {
            font-size: 1.45rem;
        }

        .about-subtitle {
            font-size: 13px;
            line-height: 1.6;
        }

        .about-form-card {
            padding: 16px;
            border-radius: 16px;
        }

        .about-form-group {
            margin-bottom: 20px;
        }

        .about-label {
            font-size: 13px;
        }

        .about-label small {
            display: none;
        }

        .about-input {
            min-height: 48px;
            font-size: 13px;
            padding: 12px;
            border-radius: 10px;
        }

        .about-textarea-vision,
        .about-textarea-mission {
            min-height: 125px;
        }

        .about-textarea-bio {
            min-height: 165px;
        }

        .about-divider {
            margin-bottom: 20px;
        }

        .about-divider-text {
            font-size: 10px;
        }

        .about-orb.one {
            width: 180px;
            height: 180px;
        }

        .about-orb.two {
            width: 130px;
            height: 130px;
        }

        .about-grid {
            width: 220px;
            height: 170px;
        }

    }


    /* Dynamic company-profile editor additions (scoped to this page only). */
    .about-dynamic-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin:0 0 20px; padding:14px 16px; border:1px solid rgba(52,24,147,.12); border-radius:14px; background:rgba(52,24,147,.035); }
    .about-dynamic-toolbar strong { display:block; color:#341893; font-size:13px; }
    .about-dynamic-toolbar span { display:block; margin-top:4px; color:#7b8190; font-size:11px; line-height:1.5; }
    .about-add-section { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:42px; padding:0 15px; border:1px solid rgba(52,24,147,.2); border-radius:10px; background:#fff; color:#341893; font-size:12px; font-weight:800; cursor:pointer; transition:.2s; white-space:nowrap; }
    .about-add-section:hover { background:#f5f2ff; border-color:#341893; transform:translateY(-1px); }
    .about-section-editor { position:relative; margin-bottom:17px; padding:17px; border:1px solid #e5e7eb; border-radius:15px; background:rgba(255,255,255,.78); transition:border-color .2s,box-shadow .2s; }
    .about-section-editor:focus-within { border-color:rgba(52,24,147,.45); box-shadow:0 0 0 4px rgba(52,24,147,.055); }
    .about-section-editor.is-dragging { opacity:.45; border-style:dashed; }
    .about-section-editor.is-drop-target { border-color:#06b6d4; box-shadow:0 0 0 3px rgba(6,182,212,.12); }
    .about-section-editor-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:15px; }
    .about-section-editor-title { display:flex; align-items:center; gap:10px; color:#341893; font-size:11px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; }
    .about-section-editor-title .section-number { display:grid; place-items:center; width:27px; height:27px; border-radius:8px; background:rgba(52,24,147,.08); }
    .about-section-controls { display:flex; align-items:center; gap:5px; }
    .about-section-control { display:grid; place-items:center; width:32px; height:32px; border:1px solid #e5e7eb; border-radius:9px; background:#fff; color:#64748b; cursor:pointer; transition:.18s; }
    .about-section-control:hover { color:#341893; border-color:#c8bce9; background:#f7f4ff; }
    .about-section-control.remove:hover { color:#dc3545; border-color:#f1c4ca; background:#fff5f6; }
    .about-section-control:disabled { opacity:.35; cursor:not-allowed; }
    .about-section-editor .about-form-group { margin-bottom:14px; }
    .about-section-editor .about-form-group:last-child { margin-bottom:0; }
    .about-section-editor .about-input { width:100%; }
    .about-section-editor .about-section-heading-input { min-height:45px; resize:vertical; }
    .about-section-editor .about-section-content-input { min-height:145px; }
    .about-section-hint { display:flex; justify-content:space-between; gap:10px; margin-top:6px; color:#9ca3af; font-size:10px; }
    .about-profile-alert { margin:0 0 18px; padding:13px 15px; border-radius:12px; font-size:13px; line-height:1.5; }
    .about-profile-alert.success { color:#166534; background:#f0fdf4; border:1px solid #bbf7d0; }
    .about-profile-alert.error { color:#991b1b; background:#fef2f2; border:1px solid #fecaca; }
    .dark .about-section-editor { background:rgba(17,24,39,.55); border-color:#374151; }
    .dark .about-section-editor-title,.dark .about-dynamic-toolbar strong { color:#c4b5fd; }
    .dark .about-dynamic-toolbar { background:rgba(52,24,147,.13); border-color:#4b3a78; }
    .dark .about-dynamic-toolbar span { color:#9ca3af; }
    .dark .about-add-section,.dark .about-section-control { background:#1f2937; border-color:#4b5563; color:#c4b5fd; }
    .dark .about-section-control.remove { color:#fca5a5; }
    .dark .about-section-hint { color:#9ca3af; }
    /* The existing content column remains the only vertical scroll container. */
    .flex.h-screen.overflow-hidden > .relative.flex.flex-1.flex-col.overflow-x-hidden.overflow-y-auto { min-height:0; min-width:0; }
    .about-page { overflow:visible; }
    @media(max-width:640px) {
        .about-dynamic-toolbar { align-items:stretch; flex-direction:column; }
        .about-add-section { width:100%; }
        .about-section-editor { padding:13px; }
        .about-section-editor-head { align-items:flex-start; }
        .about-section-controls { flex-wrap:wrap; justify-content:flex-end; }
        .about-section-control { width:29px; height:29px; }
        .about-section-editor .about-section-content-input { min-height:125px; }
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
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

<!-- =========================================================
     PAGE WRAPPER
========================================================= -->

<div class="flex h-screen overflow-hidden">

<!-- Sidebar -->
<?php include('sidebar.php'); ?>


<!-- =====================================================
     CONTENT AREA
====================================================== -->

<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


    <!-- Header -->
    <?php include('header.php'); ?>


    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="about-page">

        <!-- Abstract background -->
        <div class="about-decoration">

            <div class="about-orb one"></div>

            <div class="about-orb two"></div>

            <div class="about-ring one"></div>

            <div class="about-ring two"></div>

            <div class="about-grid"></div>

        </div>


        <!-- Content -->
        <div class="about-content mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <section class="about-header">

                <div class="about-header-content">

                    <div class="about-eyebrow">

                        <span class="about-eyebrow-dot"></span>

                        Company Identity

                    </div>


                    <h1 class="about-title">

                        About Us

                    </h1>


                    <p class="about-subtitle">

                        Define what your company stands for, where it is
                        going, and the story behind the organisation.
                        These details can be used to communicate your
                        company's identity clearly to your customers,
                        partners, and team.

                    </p>

                </div>


                <div class="about-header-shape"></div>

            </section>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action="save_aboutus.php"
                class="about-form-card"
                id="aboutProfileForm"
                autocomplete="off"
            >
                <div class="about-form-decoration"></div>
                <div class="about-form-inner">
                    <?php if ($flashMessage !== ''): ?>
                        <div class="about-profile-alert <?php echo $flashType === 'error' ? 'error' : 'success'; ?>" role="status">
                            <?php echo about_profile_h($flashMessage); ?>
                        </div>
                    <?php endif; ?>

                    <div class="about-dynamic-toolbar">
                        <div>
                            <strong><i class="fa-solid fa-layer-group"></i> Build your company profile</strong>
                            <span>Add as many sections as your company needs. Give each section its own heading and content.</span>
                        </div>
                        <button type="button" class="about-add-section" id="addAboutSectionTop">
                            <i class="fa-solid fa-plus"></i> Add Section
                        </button>
                    </div>

                    <div id="aboutSectionsContainer" aria-live="polite">
                        <?php foreach ($sections as $index => $section): ?>
                            <section class="about-section-editor" draggable="true">
                                <div class="about-section-editor-head">
                                    <div class="about-section-editor-title">
                                        <span class="section-number"><?php echo (int)$index + 1; ?></span>
                                        <span class="section-label">Profile Section</span>
                                    </div>
                                    <div class="about-section-controls">
                                        <button type="button" class="about-section-control drag-section" title="Drag to reorder" aria-label="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></button>
                                        <button type="button" class="about-section-control move-up" title="Move section up" aria-label="Move section up"><i class="fa-solid fa-arrow-up"></i></button>
                                        <button type="button" class="about-section-control move-down" title="Move section down" aria-label="Move section down"><i class="fa-solid fa-arrow-down"></i></button>
                                        <button type="button" class="about-section-control remove remove-section" title="Remove section" aria-label="Remove section"><i class="fa-solid fa-trash-can"></i></button>
                                    </div>
                                </div>
                                <div class="about-form-group">
                                    <label class="about-label" for="section_title_<?php echo (int)$index; ?>">
                                        <span class="about-label-icon"><i class="fa-solid fa-heading"></i></span>
                                        <span>Section Heading</span>
                                        <small>What is this section about?</small>
                                    </label>
                                    <input type="text" id="section_title_<?php echo (int)$index; ?>" name="section_title[]" maxlength="255" required
                                        placeholder="e.g. Our Vision, Our Values, What We Do"
                                        class="about-input about-section-heading-input"
                                        value="<?php echo about_profile_h($section['section_title'] ?? ''); ?>">
                                </div>
                                <div class="about-form-group">
                                    <label class="about-label" for="section_content_<?php echo (int)$index; ?>">
                                        <span class="about-label-icon"><i class="fa-solid fa-align-left"></i></span>
                                        <span>Section Content</span>
                                        <small>Tell the story</small>
                                    </label>
                                    <textarea id="section_content_<?php echo (int)$index; ?>" name="section_content[]" rows="6" required
                                        placeholder="Write the information you want people to know about this part of your company..."
                                        class="about-input about-section-content-input"><?php echo about_profile_h($section['section_content'] ?? ''); ?></textarea>
                                    <div class="about-section-hint"><span>Write clearly and keep the information accurate.</span><span class="about-character-count">0 characters</span></div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="about-add-section" id="addAboutSectionBottom" style="width:100%; margin-top:4px;">
                        <i class="fa-solid fa-circle-plus"></i> Add Another Section
                    </button>

                    <div class="about-divider">
                        <span class="about-divider-line"></span>
                        <span class="about-divider-text">Save Company Profile</span>
                        <span class="about-divider-line"></span>
                    </div>

                    <div class="about-submit-area">
                        <div class="about-save-note">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Your sections will be saved in the order shown above. You can return at any time to update them.</span>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo about_profile_h($_SESSION['about_profile_csrf']); ?>">
                        <button type="submit" class="about-submit">
                            <i class="fa-solid fa-floppy-disk"></i> Save Company Profile
                        </button>
                    </div>
                </div>
            </form>


        </div>

    </main>


    <!-- Footer -->
    <?php include('footer.php'); ?>


</div>

</div>

<!-- =========================================================
     SCRIPTS
========================================================= -->

<script defer src="bundle.js"></script>
<script>
(function () {
    'use strict';
    const container = document.getElementById('aboutSectionsContainer');
    const form = document.getElementById('aboutProfileForm');
    if (!container || !form) return;

    let dragSource = null;
    const addButtons = [document.getElementById('addAboutSectionTop'), document.getElementById('addAboutSectionBottom')];

    function updateSections() {
        const cards = Array.from(container.querySelectorAll('.about-section-editor'));
        cards.forEach((card, index) => {
            const number = card.querySelector('.section-number');
            if (number) number.textContent = String(index + 1);
            const up = card.querySelector('.move-up');
            const down = card.querySelector('.move-down');
            if (up) up.disabled = index === 0;
            if (down) down.disabled = index === cards.length - 1;
            const title = card.querySelector('input[name="section_title[]"]');
            const label = card.querySelector('.section-label');
            if (label) label.textContent = title && title.value.trim() ? title.value.trim() : 'Profile Section';
            updateCount(card);
        });
    }

    function updateCount(card) {
        const textarea = card.querySelector('textarea[name="section_content[]"]');
        const counter = card.querySelector('.about-character-count');
        if (textarea && counter) counter.textContent = textarea.value.length.toLocaleString() + ' characters';
    }

    function createSection() {
        const card = document.createElement('section');
        card.className = 'about-section-editor';
        card.draggable = true;
        const uid = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
        card.innerHTML = `
            <div class="about-section-editor-head">
                <div class="about-section-editor-title"><span class="section-number"></span><span class="section-label">Profile Section</span></div>
                <div class="about-section-controls">
                    <button type="button" class="about-section-control drag-section" title="Drag to reorder" aria-label="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></button>
                    <button type="button" class="about-section-control move-up" title="Move section up" aria-label="Move section up"><i class="fa-solid fa-arrow-up"></i></button>
                    <button type="button" class="about-section-control move-down" title="Move section down" aria-label="Move section down"><i class="fa-solid fa-arrow-down"></i></button>
                    <button type="button" class="about-section-control remove remove-section" title="Remove section" aria-label="Remove section"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>
            <div class="about-form-group">
                <label class="about-label" for="title_${uid}"><span class="about-label-icon"><i class="fa-solid fa-heading"></i></span><span>Section Heading</span><small>What is this section about?</small></label>
                <input type="text" id="title_${uid}" name="section_title[]" maxlength="255" required placeholder="e.g. Our Values, What We Do, Our History" class="about-input about-section-heading-input">
            </div>
            <div class="about-form-group">
                <label class="about-label" for="content_${uid}"><span class="about-label-icon"><i class="fa-solid fa-align-left"></i></span><span>Section Content</span><small>Tell the story</small></label>
                <textarea id="content_${uid}" name="section_content[]" rows="6" required placeholder="Write the information you want people to know about this part of your company..." class="about-input about-section-content-input"></textarea>
                <div class="about-section-hint"><span>Write clearly and keep the information accurate.</span><span class="about-character-count">0 characters</span></div>
            </div>`;
        return card;
    }

    function addSection() {
        const card = createSection();
        container.appendChild(card);
        updateSections();
        const input = card.querySelector('input[name="section_title[]"]');
        if (input) input.focus();
        card.scrollIntoView({behavior: 'smooth', block: 'center'});
    }

    addButtons.forEach(button => button && button.addEventListener('click', addSection));

    container.addEventListener('click', function (event) {
        const button = event.target.closest('button');
        if (!button) return;
        const card = button.closest('.about-section-editor');
        if (!card) return;
        if (button.classList.contains('remove-section')) {
            const cards = container.querySelectorAll('.about-section-editor');
            if (cards.length <= 1) {
                const title = card.querySelector('input[name="section_title[]"]');
                const content = card.querySelector('textarea[name="section_content[]"]');
                if (title) title.value = '';
                if (content) content.value = '';
            } else {
                card.remove();
            }
            updateSections();
        } else if (button.classList.contains('move-up') && card.previousElementSibling) {
            container.insertBefore(card, card.previousElementSibling);
            updateSections();
        } else if (button.classList.contains('move-down') && card.nextElementSibling) {
            container.insertBefore(card.nextElementSibling, card);
            updateSections();
        }
    });

    container.addEventListener('input', function (event) {
        const card = event.target.closest('.about-section-editor');
        if (!card) return;
        if (event.target.matches('input[name="section_title[]"]')) {
            const label = card.querySelector('.section-label');
            if (label) label.textContent = event.target.value.trim() || 'Profile Section';
        }
        updateCount(card);
    });

    container.addEventListener('dragstart', function (event) {
        const card = event.target.closest('.about-section-editor');
        if (!card) return;
        dragSource = card;
        card.classList.add('is-dragging');
        if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    });
    container.addEventListener('dragover', function (event) {
        if (!dragSource) return;
        event.preventDefault();
        const target = event.target.closest('.about-section-editor');
        container.querySelectorAll('.is-drop-target').forEach(el => el.classList.remove('is-drop-target'));
        if (target && target !== dragSource) target.classList.add('is-drop-target');
    });
    container.addEventListener('drop', function (event) {
        if (!dragSource) return;
        event.preventDefault();
        const target = event.target.closest('.about-section-editor');
        if (target && target !== dragSource) {
            const rect = target.getBoundingClientRect();
            const insertAfter = event.clientY > rect.top + rect.height / 2;
            container.insertBefore(dragSource, insertAfter ? target.nextSibling : target);
        }
        finishDrag();
        updateSections();
    });
    container.addEventListener('dragend', finishDrag);
    function finishDrag() {
        if (dragSource) dragSource.classList.remove('is-dragging');
        container.querySelectorAll('.is-drop-target').forEach(el => el.classList.remove('is-drop-target'));
        dragSource = null;
    }

    form.addEventListener('submit', function (event) {
        const cards = Array.from(container.querySelectorAll('.about-section-editor'));
        if (!cards.length) {
            event.preventDefault();
            alert('Please add at least one company profile section.');
            return;
        }
        for (const card of cards) {
            const title = card.querySelector('input[name="section_title[]"]');
            const content = card.querySelector('textarea[name="section_content[]"]');
            if (!title || !content || !title.value.trim() || !content.value.trim()) {
                event.preventDefault();
                alert('Please complete both the heading and content for every section, or remove the empty section.');
                if (title && !title.value.trim()) title.focus(); else if (content) content.focus();
                return;
            }
        }
    });
    updateSections();
})();
</script>

</body>

</html>
