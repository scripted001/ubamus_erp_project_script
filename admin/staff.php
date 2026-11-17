<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');


// ============================================================
// FETCH STAFF DATA
// ============================================================

if (isset($_POST['submit'])) {

    // Sanitize input
    $company_id = $_POST['staff_id'] ?? null;

    // Step 1: Fetch staff data
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    $staff_id        = $company['id']          ?? 'Not Found';
    $company_id      = $company['company_id'] ?? 'Not Found';
    $location_id     = $company['location_id'] ?? 'Not Found';
    $firstname       = $company['firstname']  ?? 'Not Found';
    $lastname        = $company['lastname']   ?? 'Not Found';
    $email           = $company['email']      ?? 'Not Found';
    $phone           = $company['phone']      ?? 'Not Found';
    $role            = $company['role']       ?? 'Not Found';
    $address         = $company['address']    ?? 'Not Found';
    $profile         = $company['profile']    ?? 'Not Found';
    $refree1name     = $company['refree1name']  ?? 'Not Found';
    $refree1phone    = $company['refree1phone'] ?? 'Not Found';
    $refree2name     = $company['refree2name']  ?? 'Not Found';
    $refree2phone    = $company['refree2phone'] ?? 'Not Found';
    $created_at      = $company['created_at'] ?? 'Not Found';
    $status          = $company['status']     ?? 'Not Found';


    // Stop if no staff was found
    if (!$company) {
        echo "No staff found with this ID.";
        exit;
    }


    // ============================================================
    // FETCH COMPANY DATA
    // ============================================================

    try {

        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);

        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $company_name = $user_data['company_name'] ?? 'Not Found';

    } catch (PDOException $e) {

        echo "Company data fetch error: " . $e->getMessage();

    }


    // ============================================================
    // FETCH LOCATION DATA
    // ============================================================

    try {

        $stmt = $pdo->prepare("
            SELECT *
            FROM locations
            WHERE id = ?
            AND company_id = ?
        ");

        $stmt->execute([
            $location_id,
            $company_id
        ]);

        $location_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $location_name    = $location_data['name'] ?? 'Not Found';
        $location_address = $location_data['address_line1'] ?? 'Not Found';
        $location_type    = $location_data['type'] ?? 'Not Found';

    } catch (PDOException $e) {

        echo "Location data fetch error: " . $e->getMessage();

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
    <?= htmlspecialchars($app_name ?? 'Staff') ?>
    |
    <?= htmlspecialchars($firstname ?? 'Staff') ?>
    Profile
</title>


<!-- Favicon -->
<link
    rel="icon"
    href="src/images/icons/favicon.png"
>


<!-- Font Awesome -->
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
    href="modal.css"
    rel="stylesheet"
>


<!-- ============================================================
     RESPONSIVE STAFF PROFILE STYLES
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
       MAIN LAYOUT
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
        margin-left: auto;
        margin-right: auto;
        min-width: 0;
    }


    /* ============================================================
       PROFILE CARDS
       ============================================================ */

    .staff-card {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }


    .staff-section {
        width: 100%;
        min-width: 0;
    }


    /* ============================================================
       PROFILE IMAGE
       ============================================================ */

    .staff-profile-image-wrapper {
        width: 88px;
        height: 88px;
        min-width: 88px;
        border-radius: 50%;
        overflow: hidden;
    }


    .staff-profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }


    /* ============================================================
       INFORMATION VALUES
       ============================================================ */

    .staff-info-item {
        min-width: 0;
        width: 100%;
    }


    .staff-info-value {
        min-width: 0;
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.55;
    }


    /* ============================================================
       SECTION ICON
       ============================================================ */

    .staff-section-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }


    .staff-section-icon i {
        font-size: 17px;
    }


    /* ============================================================
       EDIT BUTTON
       ============================================================ */

    .staff-edit-button {
        min-height: 44px;
        white-space: nowrap;
    }


    /* ============================================================
       MOBILE
       ============================================================ */

    @media (max-width: 767px) {

        .staff-container {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }


        .staff-card {
            border-radius: 14px !important;
            padding: 16px !important;
        }


        .staff-section {
            padding: 16px !important;
            border-radius: 14px !important;
        }


        .staff-profile-image-wrapper {
            width: 72px;
            height: 72px;
            min-width: 72px;
        }


        .staff-profile-header {
            display: flex;
            flex-direction: column;
            align-items: stretch !important;
            gap: 18px !important;
        }


        .staff-profile-details {
            width: 100%;
            min-width: 0;
        }


        .staff-profile-name {
            font-size: 17px !important;
            line-height: 1.35;
            text-align: left !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }


        .staff-profile-meta {
            align-items: flex-start !important;
            text-align: left !important;
            flex-direction: column !important;
            gap: 6px !important;
        }


        .staff-edit-form {
            width: 100%;
        }


        .staff-edit-button {
            width: 100% !important;
            justify-content: center !important;
        }


        .staff-section-header {
            margin-bottom: 20px !important;
        }


        .staff-section-title {
            font-size: 16px !important;
        }


        .staff-info-grid {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 18px !important;
        }


        .staff-breadcrumb {
            width: 100%;
            overflow-x: auto;
        }


        .staff-breadcrumb ol {
            flex-wrap: wrap;
        }


        .staff-page-heading {
            font-size: 18px !important;
        }

    }


    /* ============================================================
       TABLET
       ============================================================ */

    @media (min-width: 768px) and (max-width: 1023px) {

        .staff-container {
            padding-left: 20px !important;
            padding-right: 20px !important;
        }


        .staff-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }


        .staff-profile-image-wrapper {
            width: 80px;
            height: 80px;
            min-width: 80px;
        }

    }


    /* ============================================================
       DESKTOP
       ============================================================ */

    @media (min-width: 1024px) {

        .staff-container {
            padding-left: 24px !important;
            padding-right: 24px !important;
        }

    }


    /* ============================================================
       LARGE DESKTOP
       ============================================================ */

    @media (min-width: 1280px) {

        .staff-info-grid {
            column-gap: 80px;
        }

    }


    /* ============================================================
       DARK MODE
       ============================================================ */

    .dark .staff-section-icon {
        background: rgba(255,255,255,0.05);
    }


    /* ============================================================
       ICON ALIGNMENT
       ============================================================ */

    .staff-label {
        display: flex;
        align-items: center;
        gap: 7px;
    }


    .staff-label i {
        width: 14px;
        text-align: center;
        font-size: 10px;
    }


    /* ============================================================
       STATUS
       ============================================================ */

    .staff-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }


    .staff-status i {
        font-size: 9px;
    }


    /* ============================================================
       PREVENT LONG EMAILS / ADDRESSES FROM BREAKING LAYOUT
       ============================================================ */

    .staff-break-word {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

</style>
```

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

```
x-init="
    darkMode = JSON.parse(localStorage.getItem('darkMode'));
    $watch('darkMode', value =>
        localStorage.setItem('darkMode', JSON.stringify(value))
    )
"

:class="{'dark bg-gray-900': darkMode === true}"
```

>

```
<!-- ============================================================
     PAGE WRAPPER
     ============================================================ -->

<div class="staff-page-wrapper flex h-screen w-full overflow-hidden">


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

            <div class="staff-container p-3 sm:p-4 md:p-6">


                <!-- =================================================
                     PAGE TITLE / BREADCRUMB
                     ================================================= -->

                <div
                    x-data="{ pageName: 'Staff Profile' }"
                    class="mb-5 sm:mb-6"
                >

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
                    >


                        <!-- Page Title -->

                        <h2
                            class="staff-page-heading text-lg font-semibold text-gray-800 dark:text-white/90 sm:text-xl"
                            x-text="pageName"
                        ></h2>


                        <!-- Breadcrumb -->

                        <nav class="staff-breadcrumb">

                            <ol
                                class="flex items-center gap-1.5 text-sm"
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
                                    class="truncate text-gray-800 dark:text-white/90"
                                    x-text="pageName"
                                ></li>

                            </ol>

                        </nav>

                    </div>

                </div>


                <!-- =================================================
                     MAIN PROFILE CARD
                     ================================================= -->

                <div
                    class="staff-card rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5 lg:p-6"
                >


                    <!-- =================================================
                         PROFILE HEADER
                         ================================================= -->

                    <section
                        class="staff-section mb-5 rounded-2xl border border-gray-200 p-4 dark:border-gray-800 sm:p-5 lg:p-6"
                    >

                        <div
                            class="staff-profile-header flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                        >


                            <!-- PROFILE INFORMATION -->

                            <div
                                class="flex min-w-0 flex-1 items-center gap-4 sm:gap-5"
                            >


                                <!-- Profile Image -->

                                <div
                                    class="staff-profile-image-wrapper shrink-0 border-2 border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800"
                                >

                                    <img
                                        class="staff-profile-image"
                                        src="../<?= htmlspecialchars($profile) ?>"
                                        alt="<?= htmlspecialchars($firstname) ?> profile photo"
                                        onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-500\'><i class=\'fa-solid fa-user text-2xl\'></i></div>';"
                                    >

                                </div>


                                <!-- Name / Role / Company -->

                                <div
                                    class="staff-profile-details min-w-0 flex-1"
                                >

                                    <h3
                                        class="staff-profile-name mb-1 text-lg font-semibold text-gray-800 dark:text-white/90 sm:text-xl"
                                    >

                                        <?= htmlspecialchars($firstname) ?>
                                        <?= htmlspecialchars($lastname) ?>

                                    </h3>


                                    <div
                                        class="staff-profile-meta flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3"
                                    >

                                        <!-- Role -->

                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >

                                            <i
                                                class="fa-solid fa-user-shield text-xs"
                                                aria-hidden="true"
                                            ></i>

                                            <span>
                                                <?= htmlspecialchars($role) ?>
                                            </span>

                                        </span>


                                        <!-- Separator -->

                                        <span
                                            class="hidden text-gray-300 dark:text-gray-700 sm:inline"
                                        >
                                            •
                                        </span>


                                        <!-- Company -->

                                        <span
                                            class="inline-flex min-w-0 items-center gap-1.5"
                                        >

                                            <i
                                                class="fa-solid fa-building text-xs"
                                                aria-hidden="true"
                                            ></i>

                                            <span
                                                class="truncate"
                                            >
                                                <?= htmlspecialchars($company_name) ?>
                                            </span>

                                        </span>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                                 EDIT BUTTON
                                 ================================================= -->

                            <form
                                action="edit_staff.php"
                                method="POST"
                                class="staff-edit-form w-full lg:w-auto"
                            >

                                <input
                                    type="hidden"
                                    name="staff_id"
                                    value="<?= htmlspecialchars($staff_id) ?>"
                                >


                                <button
                                    type="submit"
                                    name="submit"
                                    @click="isProfileInfoModal = true"
                                    class="staff-edit-button inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition duration-200 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 lg:w-auto"
                                >

                                    <i
                                        class="fa-solid fa-pen-to-square"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Edit Staff
                                    </span>

                                </button>

                            </form>

                        </div>

                    </section>


                    <!-- =================================================
                         PERSONAL INFORMATION
                         ================================================= -->

                    <section
                        class="staff-section mb-5 rounded-2xl border border-gray-200 p-4 dark:border-gray-800 sm:p-5 lg:p-6"
                    >


                        <!-- Section Header -->

                        <div
                            class="staff-section-header mb-5 flex items-center gap-3 sm:mb-6"
                        >

                            <span
                                class="staff-section-icon bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            >

                                <i
                                    class="fa-solid fa-id-card"
                                    aria-hidden="true"
                                ></i>

                            </span>


                            <div>

                                <h4
                                    class="staff-section-title text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg"
                                >
                                    Personal Information
                                </h4>

                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Staff contact and registration details
                                </p>

                            </div>

                        </div>


                        <!-- Information Grid -->

                        <div
                            class="staff-info-grid grid grid-cols-1 gap-5 sm:grid-cols-2 lg:gap-x-10 xl:gap-x-20"
                        >


                            <!-- Full Name -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-user"
                                        aria-hidden="true"
                                    ></i>

                                    Full Name

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($firstname) ?>
                                    <?= htmlspecialchars($lastname) ?>

                                </p>

                            </div>


                            <!-- Email -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-envelope"
                                        aria-hidden="true"
                                    ></i>

                                    Email

                                </p>

                                <p
                                    class="staff-info-value staff-break-word text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($email) ?>

                                </p>

                            </div>


                            <!-- Mobile -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-phone"
                                        aria-hidden="true"
                                    ></i>

                                    Mobile

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($phone) ?>

                                </p>

                            </div>


                            <!-- Address -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-house"
                                        aria-hidden="true"
                                    ></i>

                                    Residential Address

                                </p>

                                <p
                                    class="staff-info-value staff-break-word text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($address) ?>

                                </p>

                            </div>


                            <!-- Status -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-circle-check"
                                        aria-hidden="true"
                                    ></i>

                                    Status

                                </p>

                                <p
                                    class="staff-status text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <i
                                        class="fa-solid fa-circle text-[8px]"
                                        aria-hidden="true"
                                    ></i>

                                    <?= htmlspecialchars($status) ?>

                                </p>

                            </div>


                            <!-- Date Registered -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-calendar-days"
                                        aria-hidden="true"
                                    ></i>

                                    Date Registered

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($created_at) ?>

                                </p>

                            </div>

                        </div>

                    </section>


                    <!-- =================================================
                         ASSIGNED LOCATION
                         ================================================= -->

                    <section
                        class="staff-section mb-5 rounded-2xl border border-gray-200 p-4 dark:border-gray-800 sm:p-5 lg:p-6"
                    >


                        <!-- Section Header -->

                        <div
                            class="staff-section-header mb-5 flex items-center gap-3 sm:mb-6"
                        >

                            <span
                                class="staff-section-icon bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            >

                                <i
                                    class="fa-solid fa-location-dot"
                                    aria-hidden="true"
                                ></i>

                            </span>


                            <div>

                                <h4
                                    class="staff-section-title text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg"
                                >
                                    Assigned Location
                                </h4>

                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Company and staff work location
                                </p>

                            </div>

                        </div>


                        <!-- Location Grid -->

                        <div
                            class="staff-info-grid grid grid-cols-1 gap-5 sm:grid-cols-2 lg:gap-x-10 xl:gap-x-20"
                        >


                            <!-- Company -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-building"
                                        aria-hidden="true"
                                    ></i>

                                    Company Name

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($company_name) ?>

                                </p>

                            </div>


                            <!-- Location -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-location-dot"
                                        aria-hidden="true"
                                    ></i>

                                    Location

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($location_name) ?>

                                </p>

                            </div>


                            <!-- Location Type -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-tag"
                                        aria-hidden="true"
                                    ></i>

                                    Location Type

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($location_type) ?>

                                </p>

                            </div>


                            <!-- Location Address -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-map-location-dot"
                                        aria-hidden="true"
                                    ></i>

                                    Location Address

                                </p>

                                <p
                                    class="staff-info-value staff-break-word text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($location_address) ?>

                                </p>

                            </div>

                        </div>

                    </section>


                    <!-- =================================================
                         REFEREE DETAILS
                         ================================================= -->

                    <section
                        class="staff-section rounded-2xl border border-gray-200 p-4 dark:border-gray-800 sm:p-5 lg:p-6"
                    >


                        <!-- Section Header -->

                        <div
                            class="staff-section-header mb-5 flex items-center gap-3 sm:mb-6"
                        >

                            <span
                                class="staff-section-icon bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            >

                                <i
                                    class="fa-solid fa-users"
                                    aria-hidden="true"
                                ></i>

                            </span>


                            <div>

                                <h4
                                    class="staff-section-title text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg"
                                >
                                    Referee Details
                                </h4>

                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Registered referee information
                                </p>

                            </div>

                        </div>


                        <!-- Referee Grid -->

                        <div
                            class="staff-info-grid grid grid-cols-1 gap-5 sm:grid-cols-2 lg:gap-x-10 xl:gap-x-20"
                        >


                            <!-- Referee 1 Name -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-user-check"
                                        aria-hidden="true"
                                    ></i>

                                    Referee 1 Name

                                </p>

                                <p
                                    class="staff-info-value staff-break-word text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($refree1name) ?>

                                </p>

                            </div>


                            <!-- Referee 1 Mobile -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-phone"
                                        aria-hidden="true"
                                    ></i>

                                    Referee 1 Mobile

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($refree1phone) ?>

                                </p>

                            </div>


                            <!-- Referee 2 Name -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-user-check"
                                        aria-hidden="true"
                                    ></i>

                                    Referee 2 Name

                                </p>

                                <p
                                    class="staff-info-value staff-break-word text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($refree2name) ?>

                                </p>

                            </div>


                            <!-- Referee 2 Mobile -->

                            <div class="staff-info-item">

                                <p
                                    class="staff-label mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >

                                    <i
                                        class="fa-solid fa-phone"
                                        aria-hidden="true"
                                    ></i>

                                    Referee 2 Mobile

                                </p>

                                <p
                                    class="staff-info-value text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($refree2phone) ?>

                                </p>

                            </div>

                        </div>

                    </section>


                </div>
                <!-- END MAIN PROFILE CARD -->


            </div>
            <!-- END PAGE CONTAINER -->


        </main>
        <!-- END MAIN -->


    </div>
    <!-- END CONTENT AREA -->


</div>
<!-- END PAGE WRAPPER -->


<!-- ============================================================
     FOOTER
     ============================================================ -->

<?php include('footer.php'); ?>


<!-- Existing JavaScript -->
<script
    defer
    src="bundle.js"
></script>


</body>

</html>
