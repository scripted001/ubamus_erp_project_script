<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'] ?? null;


/*
|--------------------------------------------------------------------------
| FETCH PRIVACY POLICY
|--------------------------------------------------------------------------
|
| One privacy policy per company.
|
*/

$privacy = null;

if ($company_id) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM privacy_policy
        WHERE company_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([$company_id]);

    $privacy = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <?= htmlspecialchars($company_name ?? 'Company'); ?> | Privacy Policy
</title>


<!-- =========================================================
     FONT AWESOME
     ========================================================= -->

<link
    rel="stylesheet"
    href="../fontawesome/css/all.css"
>


<!-- =========================================================
     EXISTING STYLES
     ========================================================= -->

<link
    href="style.css"
    rel="stylesheet"
>

<link
    href="main.css"
    rel="stylesheet"
>


<style>

    /* =========================================================
       GLOBAL
       ========================================================= */

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    html,
    body {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }


    /* =========================================================
       PAGE
       ========================================================= */

    .privacy-page {
        width: 100%;
        min-width: 0;
    }

    .privacy-container {
        width: 100%;
        max-width: 1500px;
        margin: 0 auto;
        padding: 24px;
    }


    /* =========================================================
       PAGE HEADER
       ========================================================= */

    .privacy-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 20px;

        margin-bottom: 22px;
    }

    .privacy-header-left {
        display: flex;
        align-items: center;

        gap: 12px;
    }

    .privacy-header-icon {
        width: 48px;
        height: 48px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 13px;

        background: #f3f4f6;
        color: #02356e;

        font-size: 19px;
    }

    .privacy-header-text h1 {
        margin: 0;

        color: #111827;

        font-size: 22px;
        font-weight: 700;
    }

    .privacy-header-text p {
        margin: 4px 0 0;

        color: #9ca3af;

        font-size: 12px;
    }


    /* =========================================================
       ACTION BUTTON
       ========================================================= */

    .privacy-action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        gap: 8px;

        min-height: 44px;

        padding: 0 17px;

        border-radius: 9px;

        background: #02356e;
        color: #ffffff;

        text-decoration: none;

        font-size: 12px;
        font-weight: 600;

        transition:
            background .2s ease,
            transform .2s ease;
    }

    .privacy-action-button:hover {
        background: #012a58;
    }

    .privacy-action-button:active {
        transform: scale(.98);
    }


    /* =========================================================
       MAIN PANEL
       ========================================================= */

    .privacy-panel {

        width: 100%;

        border: 1px solid #e5e7eb;
        border-radius: 16px;

        background: #ffffff;

        overflow: hidden;

    }


    /* =========================================================
       PANEL HEADER
       ========================================================= */

    .privacy-panel-header {

        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 19px 22px;

        border-bottom: 1px solid #f3f4f6;

    }

    .privacy-panel-title {

        display: flex;
        align-items: center;

        gap: 10px;

    }

    .privacy-panel-title-icon {

        width: 40px;
        height: 40px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 10px;

        background: #f3f4f6;
        color: #6b7280;

    }

    .privacy-panel-title h2 {

        margin: 0;

        color: #374151;

        font-size: 15px;
        font-weight: 700;

    }

    .privacy-panel-title p {

        margin: 4px 0 0;

        color: #9ca3af;

        font-size: 11px;

    }


    /* =========================================================
       STATUS BADGE
       ========================================================= */

    .privacy-status {

        display: inline-flex;
        align-items: center;

        gap: 6px;

        padding: 7px 10px;

        border-radius: 8px;

        background: #f0fdf4;
        color: #15803d;

        font-size: 11px;
        font-weight: 600;

    }


    /* =========================================================
       CONTENT
       ========================================================= */

    .privacy-content {

        padding: 28px;

        color: #4b5563;

        font-size: 14px;
        line-height: 1.8;

        overflow-wrap: anywhere;

    }

    .privacy-content h1,
    .privacy-content h2,
    .privacy-content h3,
    .privacy-content h4 {

        color: #111827;

    }

    .privacy-content img {

        max-width: 100%;
        height: auto;

    }

    .privacy-content table {

        max-width: 100%;

    }


    /* =========================================================
       FOOTER META
       ========================================================= */

    .privacy-meta {

        display: flex;
        align-items: center;

        gap: 8px;

        padding: 15px 22px;

        border-top: 1px solid #f3f4f6;

        color: #9ca3af;

        font-size: 11px;

    }


    /* =========================================================
       EMPTY STATE
       ========================================================= */

    .privacy-empty {

        display: flex;
        flex-direction: column;

        align-items: center;
        justify-content: center;

        min-height: 350px;

        padding: 40px 25px;

        text-align: center;

    }

    .privacy-empty-icon {

        width: 70px;
        height: 70px;

        display: flex;
        align-items: center;
        justify-content: center;

        margin-bottom: 17px;

        border-radius: 50%;

        background: #f3f4f6;
        color: #9ca3af;

        font-size: 27px;

    }

    .privacy-empty h3 {

        margin: 0;

        color: #374151;

        font-size: 16px;

    }

    .privacy-empty p {

        max-width: 400px;

        margin: 8px 0 20px;

        color: #9ca3af;

        font-size: 12px;
        line-height: 1.6;

    }


    /* =========================================================
       DARK MODE
       ========================================================= */

    .dark .privacy-header-text h1 {
        color: #f3f4f6;
    }

    .dark .privacy-header-text p {
        color: #6b7280;
    }

    .dark .privacy-header-icon,
    .dark .privacy-panel-title-icon,
    .dark .privacy-empty-icon {
        background: #1f2937;
        color: #9ca3af;
    }

    .dark .privacy-panel {
        background: #111827;
        border-color: #1f2937;
    }

    .dark .privacy-panel-header,
    .dark .privacy-meta {
        border-color: #1f2937;
    }

    .dark .privacy-panel-title h2 {
        color: #e5e7eb;
    }

    .dark .privacy-panel-title p {
        color: #6b7280;
    }

    .dark .privacy-content {
        color: #d1d5db;
    }

    .dark .privacy-content h1,
    .dark .privacy-content h2,
    .dark .privacy-content h3,
    .dark .privacy-content h4 {
        color: #f3f4f6;
    }

    .dark .privacy-empty h3 {
        color: #e5e7eb;
    }


    /* =========================================================
       TABLET
       ========================================================= */

    @media (max-width: 768px) {

        .privacy-container {
            padding: 18px;
        }

    }


    /* =========================================================
       MOBILE
       ========================================================= */

    @media (max-width: 639px) {

        .privacy-container {
            padding: 14px;
        }

        .privacy-page-header {

            align-items: flex-start;
            flex-direction: column;

        }

        .privacy-header-left {
            width: 100%;
        }

        .privacy-header-icon {

            width: 43px;
            height: 43px;

            font-size: 17px;

        }

        .privacy-header-text h1 {
            font-size: 18px;
        }

        .privacy-header-text p {
            font-size: 10.5px;
        }

        .privacy-action-button {
            width: 100%;
        }

        .privacy-panel-header {
            padding: 15px;
        }

        .privacy-content {
            padding: 18px 15px;

            font-size: 13px;
            line-height: 1.75;
        }

        .privacy-meta {
            padding: 13px 15px;
        }

    }


    /* =========================================================
       SMALL PHONES
       ========================================================= */

    @media (max-width: 360px) {

        .privacy-container {
            padding: 10px;
        }

        .privacy-panel {
            border-radius: 13px;
        }

        .privacy-content {
            padding: 15px 12px;

            font-size: 12px;
        }

    }

</style>


</head>

<body
    x-data="{
        page: 'privacy',
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

<div class="flex h-screen overflow-hidden">


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<?php include('sidebar.php'); ?>


<!-- =========================================================
     CONTENT AREA
     ========================================================= -->

<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


    <main class="privacy-page">


        <!-- =====================================================
             HEADER
             ===================================================== -->

        <?php include('header.php'); ?>


        <!-- =====================================================
             PAGE CONTENT
             ===================================================== -->

        <div class="privacy-container">


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <div class="privacy-page-header">


                <div class="privacy-header-left">


                    <div class="privacy-header-icon">

                        <i class="fa-solid fa-shield-halved"></i>

                    </div>


                    <div class="privacy-header-text">

                        <h1>
                            Privacy Policy
                        </h1>

                        <p>
                            Manage your company's privacy policy
                        </p>

                    </div>


                </div>


                <!-- =============================================
                     ADD OR EDIT BUTTON
                     ============================================= -->

                <?php if ($privacy): ?>

                    <a
                        href="edit_privacy.php?id=<?= htmlspecialchars(
                            (string)$privacy['id']
                        ); ?>"
                        class="privacy-action-button"
                    >

                        <i class="fa-solid fa-pen"></i>

                        <span>
                            Edit Privacy Policy
                        </span>

                    </a>

                <?php else: ?>

                    <a
                        href="edit_privacy.php"
                        class="privacy-action-button"
                    >

                        <i class="fa-solid fa-plus"></i>

                        <span>
                            Add Privacy Policy
                        </span>

                    </a>

                <?php endif; ?>


            </div>


            <!-- =================================================
                 PRIVACY PANEL
                 ================================================= -->

            <section class="privacy-panel">


                <?php if ($privacy): ?>


                    <!-- =========================================
                         PANEL HEADER
                         ========================================= -->

                    <div class="privacy-panel-header">


                        <div class="privacy-panel-title">


                            <div class="privacy-panel-title-icon">

                                <i class="fa-solid fa-file-shield"></i>

                            </div>


                            <div>

                                <h2>

                                    <?= htmlspecialchars(
                                        $privacy['title']
                                        ?? 'Privacy Policy'
                                    ); ?>

                                </h2>

                                <p>
                                    Your currently published privacy policy
                                </p>

                            </div>


                        </div>


                        <div class="privacy-status">

                            <i class="fa-solid fa-circle-check"></i>

                            Active

                        </div>


                    </div>


                    <!-- =========================================
                         PRIVACY CONTENT
                         ========================================= -->

                    <div class="privacy-content">

                        <?= $privacy['content'] ?? ''; ?>

                    </div>


                    <!-- =========================================
                         META
                         ========================================= -->

                    <?php if (!empty($privacy['updated_at'])): ?>

                        <div class="privacy-meta">

                            <i class="fa-solid fa-clock"></i>

                            Last updated:

                            <?= htmlspecialchars(
                                date(
                                    'F j, Y g:i A',
                                    strtotime(
                                        $privacy['updated_at']
                                    )
                                )
                            ); ?>

                        </div>

                    <?php endif; ?>


                <?php else: ?>


                    <!-- =========================================
                         EMPTY STATE
                         ========================================= -->

                    <div class="privacy-empty">


                        <div class="privacy-empty-icon">

                            <i class="fa-solid fa-shield-halved"></i>

                        </div>


                        <h3>
                            No Privacy Policy Yet
                        </h3>


                        <p>
                            Your company has not created a privacy policy yet.
                            Create one now to define how your website or
                            platform handles user information and privacy.
                        </p>


                        <a
                            href="create_privacy.php"
                            class="privacy-action-button"
                        >

                            <i class="fa-solid fa-plus"></i>

                            <span>
                                Create Privacy Policy
                            </span>

                        </a>


                    </div>


                <?php endif; ?>


            </section>


        </div>


    </main>


</div>


</div>

<!-- =========================================================
     FOOTER
     ========================================================= -->

<?php include('footer.php'); ?>

<!-- =========================================================
     BUNDLE
     ========================================================= -->

<script
    defer
    src="bundle.js"
></script>

</body>

</html>
