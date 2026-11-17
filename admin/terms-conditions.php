<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'] ?? null;


/*
|--------------------------------------------------------------------------
| FETCH TERMS & CONDITIONS
|--------------------------------------------------------------------------
|
| One Terms & Conditions record per company.
|
*/

$terms = null;

if ($company_id) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM terms_conditions
        WHERE company_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([$company_id]);

    $terms = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <?= htmlspecialchars($company_name ?? 'Company'); ?> | Terms & Conditions
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

    .terms-page {
        width: 100%;
        min-width: 0;
    }

    .terms-container {
        width: 100%;
        max-width: 1500px;

        margin: 0 auto;

        padding: 24px;
    }


    /* =========================================================
       PAGE HEADER
       ========================================================= */

    .terms-page-header {
        display: flex;

        align-items: center;
        justify-content: space-between;

        gap: 20px;

        margin-bottom: 22px;
    }

    .terms-header-left {
        display: flex;

        align-items: center;

        gap: 12px;
    }

    .terms-header-icon {
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

    .terms-header-text h1 {
        margin: 0;

        color: #111827;

        font-size: 22px;
        font-weight: 700;
    }

    .terms-header-text p {
        margin: 4px 0 0;

        color: #9ca3af;

        font-size: 12px;
    }


    /* =========================================================
       ACTION BUTTON
       ========================================================= */

    .terms-action-button {
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

    .terms-action-button:hover {
        background: #012a58;
    }

    .terms-action-button:active {
        transform: scale(.98);
    }


    /* =========================================================
       MAIN PANEL
       ========================================================= */

    .terms-panel {

        width: 100%;

        border: 1px solid #e5e7eb;
        border-radius: 16px;

        background: #ffffff;

        overflow: hidden;

    }


    /* =========================================================
       PANEL HEADER
       ========================================================= */

    .terms-panel-header {

        display: flex;

        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 19px 22px;

        border-bottom: 1px solid #f3f4f6;

    }

    .terms-panel-title {

        display: flex;

        align-items: center;

        gap: 10px;

    }

    .terms-panel-title-icon {

        width: 40px;
        height: 40px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 10px;

        background: #f3f4f6;
        color: #6b7280;

    }

    .terms-panel-title h2 {

        margin: 0;

        color: #374151;

        font-size: 15px;
        font-weight: 700;

    }

    .terms-panel-title p {

        margin: 4px 0 0;

        color: #9ca3af;

        font-size: 11px;

    }


    /* =========================================================
       STATUS BADGE
       ========================================================= */

    .terms-status {

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

    .terms-status.inactive {

        background: #fef2f2;
        color: #dc2626;

    }


    /* =========================================================
       CONTENT
       ========================================================= */

    .terms-content {

        padding: 28px;

        color: #4b5563;

        font-size: 14px;
        line-height: 1.8;

        overflow-wrap: anywhere;

    }


    /* =========================================================
       EYEBROW
       ========================================================= */

    .terms-eyebrow {

        margin-bottom: 8px;

        color: #02356e;

        font-size: 11px;
        font-weight: 700;

        letter-spacing: .08em;
        text-transform: uppercase;

    }


    /* =========================================================
       MAIN TITLE
       ========================================================= */

    .terms-main-title {

        margin: 0 0 15px;

        color: #111827;

        font-size: 28px;
        font-weight: 700;

        line-height: 1.3;

    }


    /* =========================================================
       INTRO
       ========================================================= */

    .terms-intro {

        margin-bottom: 30px;

        color: #6b7280;

        font-size: 14px;

        line-height: 1.8;

    }


    /* =========================================================
       TERMS SECTION
       ========================================================= */

    .terms-section {

        margin-top: 30px;

        padding-top: 25px;

        border-top: 1px solid #f3f4f6;

    }

    .terms-section:first-of-type {

        margin-top: 0;

    }

    .terms-section h3 {

        margin: 0 0 12px;

        color: #111827;

        font-size: 17px;
        font-weight: 700;

    }

    .terms-section-content {

        color: #4b5563;

        line-height: 1.8;

    }


    /* =========================================================
       HTML CONTENT SUPPORT
       ========================================================= */

    .terms-content h1,
    .terms-content h2,
    .terms-content h3,
    .terms-content h4 {

        color: #111827;

    }

    .terms-content img {

        max-width: 100%;
        height: auto;

    }

    .terms-content table {

        max-width: 100%;

    }


    /* =========================================================
       FOOTER META
       ========================================================= */

    .terms-meta {

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

    .terms-empty {

        display: flex;

        flex-direction: column;

        align-items: center;
        justify-content: center;

        min-height: 350px;

        padding: 40px 25px;

        text-align: center;

    }

    .terms-empty-icon {

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

    .terms-empty h3 {

        margin: 0;

        color: #374151;

        font-size: 16px;

    }

    .terms-empty p {

        max-width: 400px;

        margin: 8px 0 20px;

        color: #9ca3af;

        font-size: 12px;
        line-height: 1.6;

    }


    /* =========================================================
       DARK MODE
       ========================================================= */

    .dark .terms-header-text h1 {
        color: #f3f4f6;
    }

    .dark .terms-header-text p {
        color: #6b7280;
    }

    .dark .terms-header-icon,
    .dark .terms-panel-title-icon,
    .dark .terms-empty-icon {

        background: #1f2937;
        color: #9ca3af;

    }

    .dark .terms-panel {

        background: #111827;
        border-color: #1f2937;

    }

    .dark .terms-panel-header,
    .dark .terms-meta,
    .dark .terms-section {

        border-color: #1f2937;

    }

    .dark .terms-panel-title h2 {
        color: #e5e7eb;
    }

    .dark .terms-panel-title p {
        color: #6b7280;
    }

    .dark .terms-content {
        color: #d1d5db;
    }

    .dark .terms-main-title,
    .dark .terms-section h3 {

        color: #f3f4f6;

    }

    .dark .terms-intro,
    .dark .terms-section-content {

        color: #d1d5db;

    }

    .dark .terms-empty h3 {
        color: #e5e7eb;
    }


    /* =========================================================
       TABLET
       ========================================================= */

    @media (max-width: 768px) {

        .terms-container {
            padding: 18px;
        }

    }


    /* =========================================================
       MOBILE
       ========================================================= */

    @media (max-width: 639px) {

        .terms-container {
            padding: 14px;
        }

        .terms-page-header {

            align-items: flex-start;
            flex-direction: column;

        }

        .terms-header-left {
            width: 100%;
        }

        .terms-header-icon {

            width: 43px;
            height: 43px;

            font-size: 17px;

        }

        .terms-header-text h1 {
            font-size: 18px;
        }

        .terms-header-text p {
            font-size: 10.5px;
        }

        .terms-action-button {
            width: 100%;
        }

        .terms-panel-header {

            align-items: flex-start;

            flex-direction: column;

            padding: 15px;

        }

        .terms-content {

            padding: 20px 15px;

            font-size: 13px;
            line-height: 1.75;

        }

        .terms-main-title {

            font-size: 22px;

        }

        .terms-section h3 {

            font-size: 15px;

        }

        .terms-meta {
            padding: 13px 15px;
        }

    }


    /* =========================================================
       SMALL PHONES
       ========================================================= */

    @media (max-width: 360px) {

        .terms-container {
            padding: 10px;
        }

        .terms-panel {
            border-radius: 13px;
        }

        .terms-content {

            padding: 15px 12px;

            font-size: 12px;

        }

        .terms-main-title {

            font-size: 20px;

        }

    }

</style>

</head>

<body

x-data="{
    page: 'terms',
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


<main class="terms-page">


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <?php include('header.php'); ?>


    <!-- =====================================================
         PAGE CONTENT
         ===================================================== -->

    <div class="terms-container">


        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <div class="terms-page-header">


            <div class="terms-header-left">


                <div class="terms-header-icon">

                    <i class="fa-solid fa-file-contract"></i>

                </div>


                <div class="terms-header-text">

                    <h1>
                        Terms & Conditions
                    </h1>

                    <p>
                        Manage your company's terms and conditions
                    </p>

                </div>


            </div>


            <!-- =============================================
                 EDIT BUTTON
                 ============================================= -->

            <?php if ($terms): ?>

                <a
                    href="edit_terms.php?id=<?= htmlspecialchars(
                        (string)$terms['id']
                    ); ?>"
                    class="terms-action-button"
                >

                    <i class="fa-solid fa-pen"></i>

                    <span>
                        Edit Terms & Conditions
                    </span>

                </a>

            <?php else: ?>

                <a
                    href="edit_terms.php"
                    class="terms-action-button"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Edit Terms & Conditions
                    </span>

                </a>

            <?php endif; ?>


        </div>


        <!-- =================================================
             TERMS PANEL
             ================================================= -->

        <section class="terms-panel">


            <?php if ($terms): ?>


                <!-- =========================================
                     PANEL HEADER
                     ========================================= -->

                <div class="terms-panel-header">


                    <div class="terms-panel-title">


                        <div class="terms-panel-title-icon">

                            <i class="fa-solid fa-file-contract"></i>

                        </div>


                        <div>

                            <h2>

                                <?= htmlspecialchars(
                                    $terms['title']
                                    ?? 'Terms & Conditions'
                                ); ?>

                            </h2>


                            <p>
                                Your company's current terms and conditions
                            </p>


                        </div>


                    </div>


                    <?php

                    $status = strtolower(
                        $terms['status'] ?? 'active'
                    );

                    ?>


                    <div
                        class="terms-status <?= $status !== 'active'
                            ? 'inactive'
                            : '';
                        ?>"
                    >

                        <i
                            class="fa-solid <?= $status === 'active'
                                ? 'fa-circle-check'
                                : 'fa-circle-xmark';
                            ?>"
                        ></i>

                        <?= htmlspecialchars(
                            ucfirst($status)
                        ); ?>

                    </div>


                </div>


                <!-- =========================================
                     TERMS CONTENT
                     ========================================= -->

                <div class="terms-content">


                    <!-- =====================================
                         EYEBROW
                         ===================================== -->

                    <?php if (!empty($terms['eyebrow'])): ?>

                        <div class="terms-eyebrow">

                            <?= htmlspecialchars(
                                $terms['eyebrow']
                            ); ?>

                        </div>

                    <?php endif; ?>


                    <!-- =====================================
                         TITLE
                         ===================================== -->

                    <?php if (!empty($terms['title'])): ?>

                        <h1 class="terms-main-title">

                            <?= htmlspecialchars(
                                $terms['title']
                            ); ?>

                        </h1>

                    <?php endif; ?>


                    <!-- =====================================
                         INTRO
                         ===================================== -->

                    <?php if (!empty($terms['intro'])): ?>

                        <div class="terms-intro">

                            <?= nl2br(
                                htmlspecialchars(
                                    $terms['intro']
                                )
                            ); ?>

                        </div>

                    <?php endif; ?>


                    <!-- =====================================
                         ACCEPTANCE
                         ===================================== -->

                    <?php if (
                        !empty($terms['acceptance_title'])
                        ||
                        !empty($terms['acceptance_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['acceptance_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['acceptance_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['acceptance_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['acceptance_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                    <!-- =====================================
                         WEBSITE USE
                         ===================================== -->

                    <?php if (
                        !empty($terms['website_use_title'])
                        ||
                        !empty($terms['website_use_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['website_use_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['website_use_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['website_use_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['website_use_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                    <!-- =====================================
                         INTELLECTUAL PROPERTY
                         ===================================== -->

                    <?php if (
                        !empty($terms['intellectual_property_title'])
                        ||
                        !empty($terms['intellectual_property_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['intellectual_property_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['intellectual_property_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['intellectual_property_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['intellectual_property_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                    <!-- =====================================
                         WEBSITE INFORMATION
                         ===================================== -->

                    <?php if (
                        !empty($terms['website_information_title'])
                        ||
                        !empty($terms['website_information_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['website_information_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['website_information_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['website_information_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['website_information_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                    <!-- =====================================
                         LIABILITY
                         ===================================== -->

                    <?php if (
                        !empty($terms['liability_title'])
                        ||
                        !empty($terms['liability_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['liability_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['liability_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['liability_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['liability_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                    <!-- =====================================
                         THIRD PARTY
                         ===================================== -->

                    <?php if (
                        !empty($terms['third_party_title'])
                        ||
                        !empty($terms['third_party_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['third_party_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['third_party_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['third_party_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['third_party_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                    <!-- =====================================
                         CHANGES
                         ===================================== -->

                    <?php if (
                        !empty($terms['changes_title'])
                        ||
                        !empty($terms['changes_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['changes_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['changes_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['changes_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['changes_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                    <!-- =====================================
                         CONTACT
                         ===================================== -->

                    <?php if (
                        !empty($terms['contact_title'])
                        ||
                        !empty($terms['contact_content'])
                    ): ?>

                        <section class="terms-section">

                            <?php if (!empty(
                                $terms['contact_title']
                            )): ?>

                                <h3>

                                    <?= htmlspecialchars(
                                        $terms['contact_title']
                                    ); ?>

                                </h3>

                            <?php endif; ?>


                            <?php if (!empty(
                                $terms['contact_content']
                            )): ?>

                                <div class="terms-section-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $terms['contact_content']
                                        )
                                    ); ?>

                                </div>

                            <?php endif; ?>


                        </section>

                    <?php endif; ?>


                </div>


                <!-- =========================================
                     META
                     ========================================= -->

                <?php if (!empty($terms['updated_at'])): ?>

                    <div class="terms-meta">

                        <i class="fa-solid fa-clock"></i>

                        Last updated:

                        <?= htmlspecialchars(
                            date(
                                'F j, Y g:i A',
                                strtotime(
                                    $terms['updated_at']
                                )
                            )
                        ); ?>

                    </div>

                <?php endif; ?>


            <?php else: ?>


                <!-- =========================================
                     EMPTY STATE
                     ========================================= -->

                <div class="terms-empty">


                    <div class="terms-empty-icon">

                        <i class="fa-solid fa-file-contract"></i>

                    </div>


                    <h3>
                        No Terms & Conditions Yet
                    </h3>


                    <p>
                        Your company has not created its terms and
                        conditions yet. Add your terms and conditions
                        to define the rules and guidelines for using
                        your website and services.
                    </p>


                    <a
                        href="create_terms.php"
                        class="terms-action-button"
                    >

                        <i class="fa-solid fa-plus"></i>

                        <span>
                            Add Terms & Conditions
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
