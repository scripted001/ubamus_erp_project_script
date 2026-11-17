<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

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
    <?= htmlspecialchars($company_name ?? 'Company') ?> | Staff Area
</title>


<!-- ============================================================
     FONT AWESOME
     ============================================================ -->

<link
    rel="stylesheet"
    href="../fontawesome/css/all.css"
>


<!-- Existing Styles -->

<link
    href="style.css"
    rel="stylesheet"
>

<link
    href="main.css"
    rel="stylesheet"
>


<!-- ============================================================
     RESPONSIVE STAFF PAGE STYLES
     ============================================================ -->

<style>

    * {
        box-sizing: border-box;
    }


    html,
    body {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }


    body {
        min-height: 100vh;
    }


    /* ============================================================
       PAGE LAYOUT
       ============================================================ */

    .staff-page-wrapper {
        width: 100%;
        min-width: 0;
    }


    .staff-content-area {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }


    .staff-main {
        width: 100%;
        min-width: 0;
    }


    .staff-container {
        width: 100%;
        max-width: 1536px;
        min-width: 0;
        margin: 0 auto;
    }


    /* ============================================================
       MAIN STAFF CARD
       ============================================================ */

    .staff-main-card {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }


    /* ============================================================
       SEARCH AREA
       ============================================================ */

    .staff-search-area {
        width: 100%;
        min-width: 0;
    }


    .staff-search-grid {
        display: grid;
        grid-template-columns: 180px minmax(0, 1fr);
        gap: 10px;
        width: 100%;
    }


    .staff-search-select,
    .staff-search-input {
        width: 100%;
        min-width: 0;
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: white;
        padding: 0 13px;
        font-size: 14px;
        outline: none;
        transition: all 0.2s ease;
    }


    .staff-search-select:focus,
    .staff-search-input:focus {
        border-color: #9ca3af;
        box-shadow: 0 0 0 3px rgba(156, 163, 175, 0.12);
    }


    .staff-search-input-wrapper {
        position: relative;
        width: 100%;
    }


    .staff-search-icon {
        position: absolute;
        top: 50%;
        left: 14px;
        transform: translateY(-50%);
        color: #9ca3af;
        pointer-events: none;
        font-size: 13px;
    }


    .staff-search-input {
        padding-left: 40px;
    }


    /* ============================================================
       STAFF RESULTS
       ============================================================ */

    #staffResults {
        width: 100%;
        min-width: 0;
    }


    .staff-results-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        width: 100%;
    }


    /* ============================================================
       STAFF CARD
       ============================================================ */

    .staff-member-card {
        width: 100%;
        min-width: 0;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        padding: 18px;
        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease,
            border-color 0.2s ease;
    }


    .staff-member-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }


    /* ============================================================
       STAFF CARD HEADER
       ============================================================ */

    .staff-member-header {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 1px solid #f0f1f3;
    }


    .staff-avatar {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #6b7280;
        font-size: 18px;
    }


    .staff-member-name-area {
        min-width: 0;
        flex: 1;
    }


    .staff-member-name {
        margin: 0;
        color: #1f2937;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }


    .staff-member-role {
        margin-top: 3px;
        color: #6b7280;
        font-size: 12px;
        overflow-wrap: anywhere;
    }


    /* ============================================================
       STAFF INFORMATION
       ============================================================ */

    .staff-member-info {
        display: flex;
        flex-direction: column;
        gap: 11px;
    }


    .staff-info-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 0;
    }


    .staff-info-row-icon {
        width: 18px;
        min-width: 18px;
        margin-top: 2px;
        text-align: center;
        color: #9ca3af;
        font-size: 12px;
    }


    .staff-info-content {
        min-width: 0;
        flex: 1;
    }


    .staff-info-label {
        display: block;
        margin-bottom: 2px;
        color: #9ca3af;
        font-size: 11px;
        font-weight: 500;
    }


    .staff-info-value {
        display: block;
        color: #374151;
        font-size: 13px;
        line-height: 1.45;
        overflow-wrap: anywhere;
        word-break: break-word;
    }


    /* ============================================================
       STATUS
       ============================================================ */

    .staff-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }


    .staff-status-dot {
        width: 7px;
        height: 7px;
        min-width: 7px;
        border-radius: 50%;
        background: #9ca3af;
    }


    /* ============================================================
       ACTIONS
       ============================================================ */

    .staff-actions {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 7px;
        margin-top: 17px;
        padding-top: 15px;
        border-top: 1px solid #f0f1f3;
    }


    .staff-action-form {
        min-width: 0;
    }


    .staff-action-button {
        width: 100%;
        min-height: 38px;
        border-radius: 9px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 8px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }


    .staff-action-button i {
        font-size: 11px;
    }


    .staff-action-button:hover {
        background: #f9fafb;
        transform: translateY(-1px);
    }


    .staff-action-view {
        color: #374151;
    }


    .staff-action-view:hover {
        border-color: #9ca3af;
    }


    .staff-action-suspend {
        color: #b45309;
    }


    .staff-action-suspend:hover {
        background: #fffbeb;
        border-color: #fcd34d;
    }


    .staff-action-activate {
        color: #047857;
    }


    .staff-action-activate:hover {
        background: #ecfdf5;
        border-color: #6ee7b7;
    }


    .staff-action-delete {
        color: #dc2626;
    }


    .staff-action-delete:hover {
        background: #fef2f2;
        border-color: #fca5a5;
    }


    /* ============================================================
       EMPTY STATE
       ============================================================ */

    .staff-empty-state {
        width: 100%;
        padding: 55px 20px;
        text-align: center;
        border: 1px dashed #d1d5db;
        border-radius: 14px;
    }


    .staff-empty-icon {
        width: 55px;
        height: 55px;
        margin: 0 auto 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f3f4f6;
        color: #9ca3af;
        font-size: 21px;
    }


    /* ============================================================
       TABLET
       ============================================================ */

    @media (max-width: 1199px) {

        .staff-results-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

    }


    /* ============================================================
       MOBILE
       ============================================================ */

    @media (max-width: 767px) {

        .staff-container {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }


        .staff-main-card {
            border-radius: 14px !important;
        }


        .staff-search-grid {
            grid-template-columns: 1fr;
            gap: 9px;
        }


        .staff-search-select,
        .staff-search-input {
            height: 44px;
        }


        .staff-results-grid {
            grid-template-columns: 1fr;
            gap: 13px;
        }


        .staff-member-card {
            padding: 15px;
            border-radius: 13px;
        }


        .staff-member-header {
            gap: 12px;
        }


        .staff-avatar {
            width: 44px;
            height: 44px;
            min-width: 44px;
            font-size: 16px;
        }


        .staff-member-name {
            font-size: 14px;
        }


        .staff-member-role {
            font-size: 11px;
        }


        .staff-action-button {
            min-height: 40px;
            padding: 7px 4px;
            font-size: 10px;
        }


        .staff-action-button span {
            display: none;
        }


        .staff-action-button i {
            font-size: 13px;
        }


        .staff-page-title {
            font-size: 18px !important;
        }

    }


    /* ============================================================
       VERY SMALL PHONES
       ============================================================ */

    @media (max-width: 380px) {

        .staff-member-card {
            padding: 13px;
        }


        .staff-actions {
            gap: 5px;
        }


        .staff-action-button {
            min-height: 38px;
        }

    }


    /* ============================================================
       DARK MODE
       ============================================================ */

    .dark .staff-search-select,
    .dark .staff-search-input {
        background: #111827;
        border-color: #374151;
        color: rgba(255,255,255,0.9);
    }


    .dark .staff-search-input::placeholder {
        color: rgba(255,255,255,0.35);
    }


    .dark .staff-member-card {
        background: #111827;
        border-color: #1f2937;
    }


    .dark .staff-member-header,
    .dark .staff-actions {
        border-color: #1f2937;
    }


    .dark .staff-avatar {
        background: #1f2937;
        color: #9ca3af;
    }


    .dark .staff-member-name {
        color: rgba(255,255,255,0.9);
    }


    .dark .staff-member-role,
    .dark .staff-info-label {
        color: #9ca3af;
    }


    .dark .staff-info-value {
        color: rgba(255,255,255,0.75);
    }


    .dark .staff-action-button {
        background: #111827;
        border-color: #374151;
    }


    .dark .staff-action-button:hover {
        background: #1f2937;
    }


    .dark .staff-empty-state {
        border-color: #374151;
    }


    .dark .staff-empty-icon {
        background: #1f2937;
    }

</style>


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
    $watch('darkMode', value =>
        localStorage.setItem('darkMode', JSON.stringify(value))
    )
"

:class="{'dark bg-gray-900': darkMode === true}"


>


<!-- ============================================================
     PAGE WRAPPER
     ============================================================ -->

<div
    class="staff-page-wrapper flex h-screen w-full overflow-hidden"
>


    <!-- ========================================================
         SIDEBAR
         ======================================================== -->

    <?php include('sidebar.php'); ?>


    <!-- ========================================================
         CONTENT AREA
         ======================================================== -->

    <div
        class="staff-content-area relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
    >


        <!-- ====================================================
             MAIN
             ==================================================== -->

        <main class="staff-main">


            <!-- Header -->

            <?php include('header.php'); ?>


            <!-- ==================================================
                 PAGE CONTAINER
                 ================================================== -->

            <div
                class="staff-container p-3 sm:p-4 md:p-6"
            >


                <!-- =================================================
                     PAGE TITLE
                     ================================================= -->

                <div
                    x-data="{ pageName: 'Staff Area' }"
                    class="mb-5 sm:mb-6"
                >

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >

                        <div>

                            <h2
                                class="staff-page-title text-lg font-semibold text-gray-800 dark:text-white/90 sm:text-xl"
                                x-text="pageName"
                            ></h2>

                            <p
                                class="mt-1 text-xs text-gray-500 dark:text-gray-400 sm:text-sm"
                            >
                                Manage your company's registered staff
                            </p>

                        </div>


                        <!-- Breadcrumb -->

                        <nav>

                            <ol
                                class="flex flex-wrap items-center gap-1.5 text-sm"
                            >

                                <li>

                                    <a
                                        href="index.php"
                                        class="inline-flex items-center gap-1.5 text-gray-500 transition hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"
                                    >

                                        <i
                                            class="fa-solid fa-house text-xs"
                                            aria-hidden="true"
                                        ></i>

                                        <span>
                                            Home
                                        </span>

                                        <i
                                            class="fa-solid fa-chevron-right text-[9px]"
                                            aria-hidden="true"
                                        ></i>

                                    </a>

                                </li>


                                <li
                                    class="text-gray-800 dark:text-white/90"
                                >
                                    Staff
                                </li>

                            </ol>

                        </nav>

                    </div>

                </div>


                <!-- =================================================
                     STAFF MAIN CARD
                     ================================================= -->

                <div
                    class="staff-main-card rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
                >


                    <!-- =================================================
                         CARD HEADER
                         ================================================= -->

                    <div
                        class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-6 sm:py-5"
                    >

                        <div
                            class="flex flex-col gap-4"
                        >

                            <div
                                class="flex items-center gap-3"
                            >

                                <span
                                    class="flex h-10 w-10 min-w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                >

                                    <i
                                        class="fa-solid fa-users"
                                        aria-hidden="true"
                                    ></i>

                                </span>


                                <div>

                                    <h3
                                        class="text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg"
                                    >
                                        Company Registered Staff
                                    </h3>

                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Search and manage staff accounts
                                    </p>

                                </div>

                            </div>


                            <!-- =================================================
                                 SEARCH
                                 ================================================= -->

                            <div
                                class="staff-search-area"
                            >

                                <div
                                    class="staff-search-grid"
                                >


                                    <!-- Search Option -->

                                    <div>

                                        <label
                                            for="search_option"
                                            class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Search by
                                        </label>

                                        <div
                                            class="relative"
                                        >

                                            

                                            <select
                                                id="search_option"
                                                class="staff-search-select pl-9 dark:text-white/90"
                                            >

                                                <option value="fullname">
                                                    Fullname
                                                </option>

                                                <option value="firstname">
                                                    Firstname
                                                </option>

                                                <option value="lastname">
                                                    Lastname
                                                </option>

                                                <option value="phone">
                                                    Mobile
                                                </option>

                                                <option value="email">
                                                    Email
                                                </option>

                                            </select>

                                        </div>

                                    </div>


                                    <!-- Search Input -->

                                    <div>

                                        <label
                                            for="search_value"
                                            class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Search staff
                                        </label>

                                        <div
                                            class="staff-search-input-wrapper"
                                        >

                                            <i
                                                class="staff-search-icon fa-solid fa-magnifying-glass"
                                                aria-hidden="true"
                                            ></i>

                                            <input
                                                type="text"
                                                id="search_value"
                                                class="staff-search-input dark:text-white/90"
                                                placeholder="Search staff by name, phone or email..."
                                                autocomplete="off"
                                            >

                                        </div>

                                    </div>


                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         STAFF RESULTS
                         ================================================= -->

                    <div
                        class="p-4 sm:p-5 lg:p-6"
                    >

                        <div
                            id="staffResults"
                            class="staff-results-grid"
                        >

                            <?php

                            $stmt = $pdo->prepare(
                                "SELECT *
                                 FROM staffs
                                 WHERE company_id = ? AND location_id = ?
                                 ORDER BY id DESC"
                            );

                            $stmt->execute([
                                $_SESSION['company_id'], $_SESSION['location_id']
                            ]);


                            $staff_found = false;


                            while (
                                $branch = $stmt->fetch(PDO::FETCH_ASSOC)
                            ):

                                $staff_found = true;

                            ?>

                                <!-- =================================================
                                     STAFF MEMBER CARD
                                     ================================================= -->

                                <div
                                    class="staff-member-card"
                                >


                                    <!-- Staff Header -->

                                    <div
                                        class="staff-member-header"
                                    >

                                        <div
                                            class="staff-avatar"
                                        >

                                            <i
                                                class="fa-solid fa-user"
                                                aria-hidden="true"
                                            ></i>

                                        </div>


                                        <div
                                            class="staff-member-name-area"
                                        >

                                            <h4
                                                class="staff-member-name"
                                            >

                                                <?= htmlspecialchars(
                                                    $branch['firstname']
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $branch['lastname']
                                                ) ?>

                                            </h4>


                                            <p
                                                class="staff-member-role"
                                            >

                                                <i
                                                    class="fa-solid fa-user-shield mr-1"
                                                    aria-hidden="true"
                                                ></i>

                                                <?= htmlspecialchars(
                                                    $branch['role']
                                                ) ?>

                                            </p>

                                        </div>

                                    </div>


                                    <!-- Staff Information -->

                                    <div
                                        class="staff-member-info"
                                    >


                                        <!-- Address -->

                                        <div
                                            class="staff-info-row"
                                        >

                                            <div
                                                class="staff-info-row-icon"
                                            >

                                                <i
                                                    class="fa-solid fa-location-dot"
                                                    aria-hidden="true"
                                                ></i>

                                            </div>


                                            <div
                                                class="staff-info-content"
                                            >

                                                <span
                                                    class="staff-info-label"
                                                >
                                                    Address
                                                </span>

                                                <span
                                                    class="staff-info-value"
                                                >

                                                    <?= htmlspecialchars(
                                                        $branch['address']
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>


                                        <!-- Phone -->

                                        <div
                                            class="staff-info-row"
                                        >

                                            <div
                                                class="staff-info-row-icon"
                                            >

                                                <i
                                                    class="fa-solid fa-phone"
                                                    aria-hidden="true"
                                                ></i>

                                            </div>


                                            <div
                                                class="staff-info-content"
                                            >

                                                <span
                                                    class="staff-info-label"
                                                >
                                                    Mobile
                                                </span>

                                                <span
                                                    class="staff-info-value"
                                                >

                                                    <?= htmlspecialchars(
                                                        $branch['phone']
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>


                                        <!-- Email -->

                                        <div
                                            class="staff-info-row"
                                        >

                                            <div
                                                class="staff-info-row-icon"
                                            >

                                                <i
                                                    class="fa-solid fa-envelope"
                                                    aria-hidden="true"
                                                ></i>

                                            </div>


                                            <div
                                                class="staff-info-content"
                                            >

                                                <span
                                                    class="staff-info-label"
                                                >
                                                    Email
                                                </span>

                                                <span
                                                    class="staff-info-value"
                                                >

                                                    <?= htmlspecialchars(
                                                        $branch['email']
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>


                                        <!-- Status -->

                                        <div
                                            class="staff-info-row"
                                        >

                                            <div
                                                class="staff-info-row-icon"
                                            >

                                                <i
                                                    class="fa-solid fa-circle-check"
                                                    aria-hidden="true"
                                                ></i>

                                            </div>


                                            <div
                                                class="staff-info-content"
                                            >

                                                <span
                                                    class="staff-info-label"
                                                >
                                                    Account Status
                                                </span>

                                                <span
                                                    class="staff-info-value"
                                                >

                                                    <span
                                                        class="staff-status"
                                                    >

                                                        <span
                                                            class="staff-status-dot"
                                                        ></span>

                                                        <?= htmlspecialchars(
                                                            $branch['status']
                                                        ) ?>

                                                    </span>

                                                </span>

                                            </div>

                                        </div>


                                    </div>


                                    <!-- =================================================
                                         ACTION BUTTONS
                                         ================================================= -->

                                    <div
                                        class="staff-actions"
                                    >


                                        <!-- VIEW -->

                                        <form
                                            method="POST"
                                            action="staff.php"
                                            class="staff-action-form"
                                            onclick="return confirm('Are you sure you want to view this staff details?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="staff_id"
                                                value="<?= htmlspecialchars(
                                                    $branch['id']
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="staff_email"
                                                value="<?= htmlspecialchars(
                                                    $branch['email']
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="submit"
                                                class="staff-action-button staff-action-view"
                                                title="View staff details"
                                            >

                                                <i
                                                    class="fa-solid fa-eye"
                                                    aria-hidden="true"
                                                ></i>

                                                <span>
                                                    View
                                                </span>

                                            </button>

                                        </form>


                                       

                                    </div>


                                </div>


                            <?php endwhile; ?>


                            <?php if (!$staff_found): ?>

                                <div
                                    class="staff-empty-state col-span-full"
                                >

                                    <div
                                        class="staff-empty-icon"
                                    >

                                        <i
                                            class="fa-solid fa-users-slash"
                                            aria-hidden="true"
                                        ></i>

                                    </div>


                                    <h4
                                        class="mb-1 text-sm font-semibold text-gray-700 dark:text-white/90"
                                    >
                                        No Staff Found
                                    </h4>


                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        There are currently no registered staff accounts.
                                    </p>

                                </div>

                            <?php endif; ?>


                        </div>

                    </div>


                </div>
                <!-- END STAFF MAIN CARD -->


            </div>
            <!-- END PAGE CONTAINER -->


        </main>


    </div>
    <!-- END CONTENT AREA -->


</div>
<!-- END PAGE WRAPPER -->


<!-- ============================================================
     FOOTER
     ============================================================ -->

<?php include('footer.php'); ?>


<!-- ============================================================
     EXISTING JAVASCRIPT
     ============================================================ -->

<script
    defer
    src="bundle.js"
></script>


<!-- ============================================================
     JQUERY
     ============================================================ -->

<script
    src="https://code.jquery.com/jquery-3.6.0.min.js"
></script>


<!-- ============================================================
     STAFF SEARCH AJAX
     ============================================================ -->

<script>

    $(document).ready(function () {

        function searchStaff() {

            var option = $("#search_option").val();

            var value = $("#search_value").val();


            $.ajax({

                url: "ajax_search_staff.php",

                method: "POST",

                data: {

                    search_option: option,

                    search_value: value

                },

                success: function (data) {

                    $("#staffResults").html(data);

                },

                error: function () {

                    $("#staffResults").html(

                        '<div class="staff-empty-state col-span-full">' +

                            '<div class="staff-empty-icon">' +

                                '<i class="fa-solid fa-triangle-exclamation"></i>' +

                            '</div>' +

                            '<h4 class="mb-1 text-sm font-semibold text-gray-700 dark:text-white/90">' +

                                'Unable to load staff' +

                            '</h4>' +

                            '<p class="text-xs text-gray-500 dark:text-gray-400">' +

                                'Please try again.' +

                            '</p>' +

                        '</div>'

                    );

                }

            });

        }


        /*
         * Search while typing
         */

        $("#search_value").on("keyup", function () {

            searchStaff();

        });


        /*
         * Search when changing search option
         */

        $("#search_option").on("change", function () {

            searchStaff();

        });

    });

</script>


</body>

</html>
