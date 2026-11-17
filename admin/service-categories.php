<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


// ============================================================
// FETCH LOCATIONS
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM locations
    WHERE company_id = ?
    ORDER BY name ASC
");
$stmt->execute([$company_id]);
$locationdetail = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// COMPANY / SESSION
// ============================================================

$company_id  = $_SESSION['company_id'] ?? 0;
$location_id = $_SESSION['location_id'] ?? 0;


// ============================================================
// FETCH JOB CATEGORIES
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        jc.*,
        c.company_name,
        l.name AS location_name
    FROM job_categories jc
    JOIN companies c
        ON jc.company_id = c.id
    JOIN locations l
        ON jc.location_id = l.id
    WHERE jc.company_id = ?
    ORDER BY jc.date DESC
");
$stmt->execute([$company_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <?php echo htmlspecialchars($company_name); ?> | Categories
</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">


<style>

    /* ============================================================
       FUTURISTIC CATEGORY MANAGEMENT UI
       LIGHT THEME
    ============================================================ */

    :root {
        --neo-bg: #f5f7fb;
        --neo-card: rgba(255, 255, 255, 0.92);
        --neo-border: #e5e9f2;
        --neo-text: #111827;
        --neo-muted: #6b7280;
        --neo-primary: #111827;
        --neo-accent: #6366f1;
        --neo-accent-2: #8b5cf6;
        --neo-danger: #ef4444;
        --neo-shadow: 0 18px 50px rgba(15, 23, 42, .08);
    }

    body {
        background:
            radial-gradient(
                circle at 10% 0%,
                rgba(99, 102, 241, .08),
                transparent 28%
            ),
            radial-gradient(
                circle at 90% 10%,
                rgba(139, 92, 246, .07),
                transparent 30%
            ),
            var(--neo-bg);
    }


    /* ============================================================
       PAGE CONTAINER
    ============================================================ */

    .neo-page {
        width: 100%;
        padding: 24px;
    }


    /* ============================================================
       HERO
    ============================================================ */

    .neo-hero {
        position: relative;
        overflow: hidden;

        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 20px;

        padding: 28px 30px;

        margin-bottom: 22px;

        border: 1px solid rgba(99, 102, 241, .12);

        border-radius: 24px;

        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,.96),
                rgba(248,250,255,.96)
            );

        box-shadow: var(--neo-shadow);
    }


    .neo-hero::before {
        content: "";

        position: absolute;

        width: 240px;
        height: 240px;

        right: -100px;
        top: -130px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(99,102,241,.16),
                transparent 68%
            );

        pointer-events: none;
    }


    .neo-hero::after {
        content: "";

        position: absolute;

        width: 160px;
        height: 160px;

        left: -100px;
        bottom: -100px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(139,92,246,.10),
                transparent 70%
            );

        pointer-events: none;
    }


    .neo-title-wrap {
        position: relative;
        z-index: 2;
    }


    .neo-kicker {
        display: flex;
        align-items: center;
        gap: 8px;

        margin-bottom: 7px;

        color: var(--neo-accent);

        font-size: 11px;
        font-weight: 800;

        letter-spacing: .14em;

        text-transform: uppercase;
    }


    .neo-kicker-dot {
        width: 7px;
        height: 7px;

        border-radius: 50%;

        background: var(--neo-accent);

        box-shadow:
            0 0 0 5px rgba(99,102,241,.10),
            0 0 16px rgba(99,102,241,.30);
    }


    .neo-title {
        margin: 0;

        color: #111827;

        font-size: clamp(22px, 3vw, 32px);

        font-weight: 800;

        letter-spacing: -.035em;
    }


    .neo-description {
        margin: 7px 0 0;

        max-width: 650px;

        color: #6b7280;

        font-size: 14px;

        line-height: 1.65;
    }


    /* ============================================================
       NEW CATEGORY BUTTON
    ============================================================ */

    .neo-primary-btn {
        position: relative;
        z-index: 3;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        gap: 9px;

        min-height: 46px;

        padding: 0 18px;

        border: 0;

        border-radius: 13px;

        background:
            linear-gradient(
                135deg,
                #111827,
                #374151
            );

        color: #fff;

        font-size: 13px;
        font-weight: 700;

        cursor: pointer;

        box-shadow:
            0 12px 25px rgba(17,24,39,.16);

        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }


    .neo-primary-btn:hover {
        transform: translateY(-2px);

        box-shadow:
            0 16px 30px rgba(17,24,39,.22);
    }


    .neo-primary-btn i {
        font-size: 13px;
    }


    /* ============================================================
       STATS STRIP
    ============================================================ */

    .neo-stats {
        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 15px;

        margin-bottom: 20px;
    }


    .neo-stat {
        position: relative;

        overflow: hidden;

        padding: 18px 20px;

        border: 1px solid var(--neo-border);

        border-radius: 18px;

        background: rgba(255,255,255,.90);

        box-shadow:
            0 8px 25px rgba(15,23,42,.045);
    }


    .neo-stat::after {
        content: "";

        position: absolute;

        width: 80px;
        height: 80px;

        right: -30px;
        top: -30px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(99,102,241,.09),
                transparent 70%
            );
    }


    .neo-stat-label {
        color: #6b7280;

        font-size: 11px;

        font-weight: 700;

        letter-spacing: .08em;

        text-transform: uppercase;
    }


    .neo-stat-value {
        margin-top: 5px;

        color: #111827;

        font-size: 25px;

        font-weight: 800;

        letter-spacing: -.03em;
    }


    /* ============================================================
       TABLE CARD
    ============================================================ */

    .neo-card {
        overflow: hidden;

        border: 1px solid var(--neo-border);

        border-radius: 24px;

        background: var(--neo-card);

        box-shadow: var(--neo-shadow);
    }


    .neo-card-header {
        display: flex;

        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 21px 24px;

        border-bottom: 1px solid #edf0f5;
    }


    .neo-card-title {
        display: flex;

        align-items: center;

        gap: 11px;

        color: #111827;

        font-size: 15px;

        font-weight: 800;
    }


    .neo-icon-box {
        display: inline-flex;

        align-items: center;
        justify-content: center;

        width: 35px;
        height: 35px;

        border-radius: 10px;

        background:
            linear-gradient(
                135deg,
                rgba(99,102,241,.11),
                rgba(139,92,246,.08)
            );

        color: var(--neo-accent);
    }


    .neo-status {
        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 7px 10px;

        border-radius: 999px;

        background: #f8fafc;

        color: #64748b;

        font-size: 11px;

        font-weight: 700;
    }


    .neo-status-dot {
        width: 6px;
        height: 6px;

        border-radius: 50%;

        background: #22c55e;

        box-shadow:
            0 0 0 4px rgba(34,197,94,.10);
    }


    /* ============================================================
       TABLE
    ============================================================ */

    .neo-table-wrapper {
        width: 100%;

        overflow-x: auto;
    }


    .neo-table {
        width: 100%;

        min-width: 900px;

        border-collapse: separate;

        border-spacing: 0;
    }


    .neo-table thead th {
        padding: 15px 20px;

        border-bottom: 1px solid #edf0f5;

        background: #fafbfe;

        color: #6b7280;

        font-size: 10px;

        font-weight: 800;

        letter-spacing: .09em;

        text-align: left;

        text-transform: uppercase;

        white-space: nowrap;
    }


    .neo-table tbody td {
        padding: 17px 20px;

        border-bottom: 1px solid #f0f2f6;

        color: #374151;

        font-size: 13px;

        vertical-align: middle;
    }


    .neo-table tbody tr {
        transition:
            background .18s ease,
            transform .18s ease;
    }


    .neo-table tbody tr:hover {
        background:
            linear-gradient(
                90deg,
                rgba(99,102,241,.035),
                rgba(139,92,246,.015)
            );
    }


    .neo-index {
        display: inline-flex;

        align-items: center;
        justify-content: center;

        width: 30px;
        height: 30px;

        border-radius: 9px;

        background: #f3f4f6;

        color: #6b7280;

        font-size: 11px;

        font-weight: 800;
    }


    .neo-category-name {
        color: #111827;

        font-weight: 750;
    }


    .neo-location {
        display: inline-flex;

        align-items: center;

        gap: 7px;

        color: #4b5563;
    }


    .neo-location i {
        color: var(--neo-accent);

        font-size: 11px;
    }


    .neo-user {
        color: #64748b;

        font-size: 12px;
    }


    .neo-date {
        color: #64748b;

        font-size: 12px;

        font-variant-numeric: tabular-nums;
    }


    /* ============================================================
       DELETE BUTTON
    ============================================================ */

    .neo-delete-btn {
        display: inline-flex;

        align-items: center;
        justify-content: center;

        gap: 7px;

        min-height: 35px;

        padding: 0 11px;

        border: 1px solid #fee2e2;

        border-radius: 10px;

        background: #fff7f7;

        color: #dc2626;

        font-size: 11px;

        font-weight: 750;

        cursor: pointer;

        transition:
            background .2s ease,
            border-color .2s ease,
            transform .2s ease;
    }


    .neo-delete-btn:hover {
        background: #fee2e2;

        border-color: #fecaca;

        transform: translateY(-1px);
    }


    /* ============================================================
       EMPTY STATE
    ============================================================ */

    .neo-empty {
        padding: 70px 25px;

        text-align: center;
    }


    .neo-empty-icon {
        display: flex;

        align-items: center;
        justify-content: center;

        width: 60px;
        height: 60px;

        margin: 0 auto 14px;

        border-radius: 18px;

        background: #f3f4f6;

        color: #9ca3af;

        font-size: 22px;
    }


    .neo-empty-title {
        color: #374151;

        font-size: 15px;

        font-weight: 750;
    }


    .neo-empty-text {
        margin-top: 5px;

        color: #9ca3af;

        font-size: 12px;
    }


    /* ============================================================
       MODAL
    ============================================================ */

    .neo-modal {
        position: fixed;

        inset: 0;

        z-index: 9999;

        display: none;

        align-items: center;
        justify-content: center;

        padding: 20px;

        background:
            rgba(15,23,42,.38);

        backdrop-filter: blur(10px);

        -webkit-backdrop-filter: blur(10px);
    }


    .neo-modal.active {
        display: flex;
    }


    .neo-modal-box {
        position: relative;

        width: 100%;

        max-width: 520px;

        overflow: hidden;

        border: 1px solid rgba(255,255,255,.80);

        border-radius: 25px;

        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.98),
                rgba(248,250,255,.98)
            );

        box-shadow:
            0 35px 90px rgba(15,23,42,.25);

        animation:
            neoModalIn .25s ease;
    }


    @keyframes neoModalIn {

        from {
            opacity: 0;
            transform: translateY(15px) scale(.97);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

    }


    .neo-modal-top {
        display: flex;

        align-items: center;
        justify-content: space-between;

        padding: 23px 24px;

        border-bottom: 1px solid #edf0f5;
    }


    .neo-modal-heading {
        display: flex;

        align-items: center;

        gap: 12px;
    }


    .neo-modal-heading-icon {
        display: flex;

        align-items: center;
        justify-content: center;

        width: 40px;
        height: 40px;

        border-radius: 12px;

        background:
            linear-gradient(
                135deg,
                #111827,
                #374151
            );

        color: white;
    }


    .neo-modal-title {
        color: #111827;

        font-size: 16px;

        font-weight: 800;
    }


    .neo-modal-subtitle {
        margin-top: 2px;

        color: #9ca3af;

        font-size: 11px;
    }


    .neo-close {
        display: flex;

        align-items: center;
        justify-content: center;

        width: 34px;
        height: 34px;

        border: 0;

        border-radius: 10px;

        background: #f3f4f6;

        color: #6b7280;

        cursor: pointer;

        transition: .2s ease;
    }


    .neo-close:hover {
        background: #fee2e2;

        color: #dc2626;
    }


    .neo-form {
        padding: 24px;
    }


    .neo-field {
        margin-bottom: 19px;
    }


    .neo-label {
        display: block;

        margin-bottom: 8px;

        color: #374151;

        font-size: 12px;

        font-weight: 750;
    }


    .neo-input,
    .neo-select {
        width: 100%;

        height: 47px;

        padding: 0 14px;

        border: 1px solid #dfe4ec;

        border-radius: 12px;

        outline: none;

        background: #fff;

        color: #111827;

        font-size: 13px;

        transition:
            border-color .2s ease,
            box-shadow .2s ease;
    }


    .neo-input:focus,
    .neo-select:focus {
        border-color: rgba(99,102,241,.65);

        box-shadow:
            0 0 0 4px rgba(99,102,241,.09);
    }


    .neo-form-hint {
        margin-top: 6px;

        color: #9ca3af;

        font-size: 10px;
    }


    .neo-modal-actions {
        display: flex;

        justify-content: flex-end;

        gap: 10px;

        padding-top: 7px;
    }


    .neo-cancel {
        height: 43px;

        padding: 0 17px;

        border: 1px solid #e5e7eb;

        border-radius: 11px;

        background: #fff;

        color: #6b7280;

        font-size: 12px;

        font-weight: 700;

        cursor: pointer;

        transition: .2s ease;
    }


    .neo-cancel:hover {
        background: #f9fafb;

        color: #374151;
    }


    .neo-save {
        height: 43px;

        padding: 0 19px;

        border: 0;

        border-radius: 11px;

        background:
            linear-gradient(
                135deg,
                #111827,
                #374151
            );

        color: #fff;

        font-size: 12px;

        font-weight: 750;

        cursor: pointer;

        box-shadow:
            0 8px 20px rgba(17,24,39,.15);

        transition: .2s ease;
    }


    .neo-save:hover {
        transform: translateY(-1px);

        box-shadow:
            0 12px 25px rgba(17,24,39,.20);
    }


    /* ============================================================
       MOBILE
    ============================================================ */

    @media (max-width: 768px) {

        .neo-page {
            padding: 14px;
        }


        .neo-hero {
            align-items: flex-start;

            flex-direction: column;

            padding: 21px;

            border-radius: 20px;
        }


        .neo-primary-btn {
            width: 100%;
        }


        .neo-stats {
            grid-template-columns: 1fr;
        }


        .neo-card {
            border-radius: 19px;
        }


        .neo-card-header {
            align-items: flex-start;

            flex-direction: column;

            padding: 18px;
        }


        .neo-status {
            width: fit-content;
        }


        .neo-table {
            min-width: 820px;
        }


        .neo-modal {
            padding: 12px;
        }


        .neo-modal-box {
            max-height: 94vh;

            overflow-y: auto;

            border-radius: 21px;
        }


        .neo-form {
            padding: 20px;
        }


        .neo-modal-actions {
            flex-direction: column-reverse;
        }


        .neo-cancel,
        .neo-save {
            width: 100%;
        }

    }


    /* ============================================================
       SMALL MOBILE
    ============================================================ */

    @media (max-width: 480px) {

        .neo-title {
            font-size: 23px;
        }


        .neo-description {
            font-size: 12px;
        }


        .neo-stat-value {
            font-size: 22px;
        }


        .neo-modal-top {
            padding: 18px;
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
    $watch('darkMode', value =>
        localStorage.setItem('darkMode', JSON.stringify(value))
    )
"

:class="{
    'dark bg-gray-900': darkMode === true
}"


>

<!-- ============================================================
     PAGE WRAPPER
============================================================ -->

<div class="flex h-screen overflow-hidden">


<?php include('sidebar.php'); ?>


<!-- ========================================================
     CONTENT
======================================================== -->

<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


    <main>

        <?php include('header.php'); ?>


        <div class="neo-page">


            <!-- ====================================================
                 HERO HEADER
            ==================================================== -->

            <section class="neo-hero">

                <div class="neo-title-wrap">

                    <div class="neo-kicker">
                        <span class="neo-kicker-dot"></span>
                        PRODUCT SYSTEM
                    </div>

                    <h1 class="neo-title">
                        Category Command Center
                    </h1>

                    <p class="neo-description">
                        Organize your product architecture, assign categories
                        to locations, and maintain a clean operational
                        structure across your company.
                    </p>

                </div>


                <button
                    type="button"
                    onclick="openCategoryModal()"
                    class="neo-primary-btn"
                >
                    <i class="fa-solid fa-plus"></i>
                    New Category
                </button>

            </section>


            <!-- ====================================================
                 STATS
            ==================================================== -->

            <section class="neo-stats">

                <div class="neo-stat">

                    <div class="neo-stat-label">
                        Total Categories
                    </div>

                    <div class="neo-stat-value">
                        <?= count($categories); ?>
                    </div>

                </div>


                <div class="neo-stat">

                    <div class="neo-stat-label">
                        Locations
                    </div>

                    <div class="neo-stat-value">
                        <?= count($locationdetail); ?>
                    </div>

                </div>


                <div class="neo-stat">

                    <div class="neo-stat-label">
                        System Status
                    </div>

                    <div class="neo-stat-value"
                         style="font-size:17px; padding-top:5px;">

                        Operational

                    </div>

                </div>

            </section>


            <!-- ====================================================
                 CATEGORY CARD
            ==================================================== -->

            <section class="neo-card">


                <!-- HEADER -->

                <div class="neo-card-header">

                    <div class="neo-card-title">

                        <span class="neo-icon-box">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>

                        Product Categories

                    </div>


                    <div class="neo-status">

                        <span class="neo-status-dot"></span>

                        Live Database

                    </div>

                </div>


                <!-- TABLE -->

                <div class="neo-table-wrapper">

                    <table class="neo-table">

                        <thead>

                            <tr>

                                <th>S/N</th>

                                <th>Category</th>

                                <th>Company</th>

                                <th>Location</th>

                                <th>Created By</th>

                                <th>Date Created</th>

                                <th style="text-align:center;">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($categories): ?>

                            <?php foreach ($categories as $index => $cat): ?>

                                <tr>


                                    <!-- NUMBER -->

                                    <td>

                                        <span class="neo-index">
                                            <?= $index + 1 ?>
                                        </span>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <div class="neo-category-name">

                                            <?= htmlspecialchars(
                                                $cat['name']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- COMPANY -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $cat['company_name']
                                        ) ?>

                                    </td>


                                    <!-- LOCATION -->

                                    <td>

                                        <span class="neo-location">

                                            <i class="fa-solid fa-location-dot"></i>

                                            <?= htmlspecialchars(
                                                $cat['location_name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- CREATED BY -->

                                    <td>

                                        <span class="neo-user">

                                            <?= htmlspecialchars(
                                                $cat['created_by']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <span class="neo-date">

                                            <?= date(
                                                'Y-m-d',
                                                strtotime($cat['date'])
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td style="text-align:center;">

                                        <form
                                            method="POST"
                                            action="delete_cat.php"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this category?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="category_id"
                                                value="<?= $cat['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="company_id"
                                                value="<?= $cat['company_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="location_id"
                                                value="<?= $cat['location_id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="neo-delete-btn"
                                            >

                                                <i class="fa-regular fa-trash-can"></i>

                                                Delete

                                            </button>

                                        </form>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="7"
                                    style="border-bottom:0;"
                                >

                                    <div class="neo-empty">

                                        <div class="neo-empty-icon">

                                            <i class="fa-solid fa-layer-group"></i>

                                        </div>

                                        <div class="neo-empty-title">
                                            No categories found
                                        </div>

                                        <div class="neo-empty-text">
                                            Create your first product category
                                            to begin organizing your catalogue.
                                        </div>

                                    </div>

                                </td>

                            </tr>


                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>


        </div>

    </main>

</div>


</div>

<!-- ============================================================
     FOOTER
============================================================ -->

<?php include('footer.php'); ?>

<!-- ============================================================
     CREATE CATEGORY MODAL
============================================================ -->

<div
    id="categoryModal"
    class="neo-modal"
    onclick="handleModalOutsideClick(event)"
>


<div
    class="neo-modal-box"
    onclick="event.stopPropagation()"
>


    <!-- MODAL HEADER -->

    <div class="neo-modal-top">

        <div class="neo-modal-heading">

            <div class="neo-modal-heading-icon">

                <i class="fa-solid fa-layer-group"></i>

            </div>


            <div>

                <div class="neo-modal-title">
                    Create New Category
                </div>

                <div class="neo-modal-subtitle">
                    Add a category to your product architecture
                </div>

            </div>

        </div>


        <button
            type="button"
            onclick="closeCategoryModal()"
            class="neo-close"
            aria-label="Close"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>


    <!-- FORM -->

    <form
        method="POST"
        action="proceed-create-category.php"
        class="neo-form"
    >


        <!-- CATEGORY NAME -->

        <div class="neo-field">

            <label class="neo-label">
                Category Name
            </label>

            <input
                type="text"
                name="name"
                class="neo-input"
                placeholder="e.g. Male Fashion"
                required
            >

            <div class="neo-form-hint">
                Enter a clear name for this product category.
            </div>

        </div>


        <!-- LOCATION -->

        <div class="neo-field">

            <label class="neo-label">
                Assign Location
            </label>

            <select
                name="location_id"
                class="neo-select"
                required
            >

                <option value="">
                    Select Location
                </option>


                <?php if (!empty($locationdetail)): ?>

                    <?php foreach ($locationdetail as $locationD): ?>

                        <option
                            value="<?= $locationD['id']; ?>"
                        >

                            <?= htmlspecialchars(
                                $locationD['name']
                            ); ?>

                        </option>

                    <?php endforeach; ?>


                <?php else: ?>

                    <option value="" disabled>
                        No locations available
                    </option>

                <?php endif; ?>

            </select>

            <div class="neo-form-hint">
                Select the company location where this category applies.
            </div>

        </div>


        <!-- HIDDEN SYSTEM FIELDS -->

        <input
            type="hidden"
            name="company_id"
            value="<?= htmlspecialchars(
                $_SESSION['company_id'] ?? ''
            ); ?>"
        >


        <input
            type="hidden"
            name="created_by"
            value="<?= htmlspecialchars(
                $_SESSION['user_email'] ?? ''
            ); ?>"
        >


        <!-- ACTIONS -->

        <div class="neo-modal-actions">

            <button
                type="button"
                onclick="closeCategoryModal()"
                class="neo-cancel"
            >
                Cancel
            </button>


            <button
                type="submit"
                class="neo-save"
            >

                <i class="fa-solid fa-check"></i>

                &nbsp;

                Create Category

            </button>

        </div>


    </form>

</div>


</div>

<!-- ============================================================
     SCRIPTS
============================================================ -->

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
        "rayId":"95aeba634cdfb6e3",
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

<script>


/* ============================================================
   MODAL CONTROL
============================================================ */


function openCategoryModal() {

    const modal =
        document.getElementById('categoryModal');

    modal.classList.add('active');

    document.body.style.overflow = 'hidden';

}


function closeCategoryModal() {

    const modal =
        document.getElementById('categoryModal');

    modal.classList.remove('active');

    document.body.style.overflow = '';

}


function handleModalOutsideClick(event) {

    if (
        event.target.id === 'categoryModal'
    ) {

        closeCategoryModal();

    }

}


/* ============================================================
   ESCAPE KEY
============================================================ */

document.addEventListener(
    'keydown',
    function(event) {

        if (
            event.key === 'Escape'
        ) {

            closeCategoryModal();

        }

    }
);


/* ============================================================
   LEGACY EDIT FUNCTION
============================================================ */

function editCategory(id) {

    window.location.href =
        "edit-category.php?id=" + id;

}

</script>

</body>

</html>
