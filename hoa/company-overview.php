<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


// ============================================================
// COMPANY OVERVIEW
// ============================================================

// Get the currently authenticated user
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "Unauthorized access.";
    exit;
}


// ------------------------------------------------------------
// Get the user's company ID
// ------------------------------------------------------------
$stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$company_id = $user['company_id'] ?? null;

if (!$company_id) {
    echo "No company is associated with this account.";
    exit;
}


// ------------------------------------------------------------
// Fetch company information
// ------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo "Company information could not be found.";
    exit;
}


// ------------------------------------------------------------
// Company fields
// ------------------------------------------------------------
$company_name            = $company['company_name'] ?? 'Not Available';
$company_slug            = $company['company_slug'] ?? 'Not Available';
$company_logo            = $company['company_logo'] ?? '';
$company_domain          = $company['company_domain'] ?? 'Not Available';
$company_industry        = $company['company_industry'] ?? 'Not Available';
$company_office_address  = $company['company_office_address'] ?? 'Not Available';
$company_customer_care   = $company['company_customer_care'] ?? 'Not Available';
$company_support_email   = $company['company_support_email'] ?? 'Not Available';
$company_email           = $company['company_email'] ?? 'Not Available';
$company_mobile          = $company['company_mobile'] ?? 'Not Available';
$company_head_office     = $company['company_head_office'] ?? 'Not Available';
$company_state           = $company['company_state'] ?? 'Not Available';
$company_city            = $company['company_city'] ?? 'Not Available';
$location_id             = $company['location_id'] ?? 'Not Available';
$status                  = $company['status'] ?? 'Not Available';
$date_created            = $company['date_created'] ?? 'Not Available';
$time_created            = $company['time_created'] ?? 'Not Available';
$creator                 = $company['creator'] ?? 'Not Available';


// ------------------------------------------------------------
// Company status styling
// ------------------------------------------------------------
$status_lower = strtolower(trim($status));

if ($status_lower === 'active') {
    $status_class = 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400';
    $status_dot   = 'bg-success-500';
} elseif ($status_lower === 'inactive') {
    $status_class = 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400';
    $status_dot   = 'bg-error-500';
} else {
    $status_class = 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400';
    $status_dot   = 'bg-warning-500';
}


// ------------------------------------------------------------
// Logo fallback
// ------------------------------------------------------------
$logo_path = '';

if (!empty($company_logo)) {
    $logo_path = '../uploads/logos/' . ltrim($company_logo, '/');
}


// ------------------------------------------------------------
// Company initials fallback
// ------------------------------------------------------------
$company_initials = '';

$name_parts = preg_split('/\s+/', trim($company_name));

foreach ($name_parts as $part) {
    if (!empty($part)) {
        $company_initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($company_initials) >= 2) {
        break;
    }
}

if (empty($company_initials)) {
    $company_initials = 'CO';
}

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8" />

    <meta http-equiv="content-type"
          content="text/html;charset=utf-8" />

    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>

    <meta http-equiv="X-UA-Compatible"
          content="ie=edge" />

    <title><?= htmlspecialchars($company_name); ?> | Company Overview</title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">

    <link href="../company/style.css" rel="stylesheet">

    <link href="../company/modal.css" rel="stylesheet">

</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>


    <!-- ===== Page Wrapper Start ===== -->

    <div class="flex h-screen overflow-hidden">


        <?php include('sidebar.php'); ?>


        <!-- ===== Content Area Start ===== -->

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


            <!-- ===== Main Content Start ===== -->

            <main>

                <?php include('header.php'); ?>


                <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">


                    <!-- =====================================================
                         BREADCRUMB
                    ====================================================== -->

                    <div x-data="{ pageName: `Company Overview`}">

                        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">

                            <h2
                                class="text-xl font-semibold text-gray-800 dark:text-white/90"
                                x-text="pageName">
                            </h2>

                            <nav>

                                <ol class="flex items-center gap-1.5">

                                    <li>

                                        <a
                                            class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                                            href="index.php">

                                            Home

                                            <svg
                                                class="stroke-current"
                                                width="17"
                                                height="16"
                                                viewBox="0 0 17 16"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg">

                                                <path
                                                    d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                                    stroke=""
                                                    stroke-width="1.2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round" />

                                            </svg>

                                        </a>

                                    </li>


                                    <li
                                        class="text-sm text-gray-800 dark:text-white/90"
                                        x-text="pageName">
                                    </li>

                                </ol>

                            </nav>

                        </div>

                    </div>


                    <!-- =====================================================
                         COMPANY HERO
                    ====================================================== -->

                    <div
                        class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6"
                    >

                        <!-- Futuristic background -->

                        <div
                            class="absolute inset-0 pointer-events-none"
                            style="background:
                                radial-gradient(circle at 85% 15%, rgba(59,130,246,0.12), transparent 30%),
                                radial-gradient(circle at 15% 85%, rgba(99,102,241,0.08), transparent 30%);
                            "
                        ></div>


                        <div class="relative p-5 lg:p-7">

                            <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">


                                <!-- Company Identity -->

                                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">


                                    <!-- Logo -->

                                    <div
                                        class="relative flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                                    >

                                        <?php if (!empty($logo_path)): ?>

                                            <img
                                                src="<?= htmlspecialchars($logo_path); ?>"
                                                alt="<?= htmlspecialchars($company_name); ?> Logo"
                                                class="h-full w-full object-contain p-3"
                                            >

                                        <?php else: ?>

                                            <span
                                                class="text-2xl font-bold text-gray-500 dark:text-gray-300"
                                            >
                                                <?= htmlspecialchars($company_initials); ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <!-- Company Name -->

                                    <div>

                                        <div class="flex flex-wrap items-center gap-3">

                                            <h1
                                                class="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90"
                                            >
                                                <?= htmlspecialchars($company_name); ?>
                                            </h1>


                                            <!-- Status -->

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold <?= $status_class; ?>"
                                            >

                                                <span
                                                    class="h-1.5 w-1.5 rounded-full <?= $status_dot; ?>"
                                                ></span>

                                                <?= htmlspecialchars(ucfirst($status)); ?>

                                            </span>

                                        </div>


                                        <p
                                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            <?= htmlspecialchars($company_industry); ?>
                                        </p>


                                        <div
                                            class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-500 dark:text-gray-400"
                                        >

                                            <span class="inline-flex items-center gap-1.5">

                                                <i class="fa-solid fa-location-dot"></i>

                                                <?= htmlspecialchars($company_city); ?>,
                                                <?= htmlspecialchars($company_state); ?>

                                            </span>


                                            <?php if ($company_domain !== 'Not Available'): ?>

                                                <span class="inline-flex items-center gap-1.5">

                                                    <i class="fa-solid fa-globe"></i>

                                                    <?= htmlspecialchars($company_domain); ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>


                                <!-- Action -->

                                <div>

                                    <a
                                        href="company-information.php"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.05]"
                                    >

                                        <i class="fa-solid fa-pen-to-square"></i>

                                        Manage Company

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>



                    <!-- =====================================================
                         QUICK COMPANY METRICS
                    ====================================================== -->

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">


                        <!-- Industry -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
                        >

                            <div class="flex items-center justify-between">

                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400"
                                >
                                    <i class="fa-solid fa-industry text-lg"></i>
                                </div>

                                <span class="text-xs text-gray-400">
                                    Industry
                                </span>

                            </div>

                            <div class="mt-4">

                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Business Sector
                                </p>

                                <h3
                                    class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90"
                                >
                                    <?= htmlspecialchars($company_industry); ?>
                                </h3>

                            </div>

                        </div>


                        <!-- Location -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
                        >

                            <div class="flex items-center justify-between">

                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400"
                                >
                                    <i class="fa-solid fa-map-location-dot text-lg"></i>
                                </div>

                                <span class="text-xs text-gray-400">
                                    Location
                                </span>

                            </div>

                            <div class="mt-4">

                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Operating City
                                </p>

                                <h3
                                    class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90"
                                >
                                    <?= htmlspecialchars($company_city); ?>
                                </h3>

                            </div>

                        </div>


                        <!-- Contact -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
                        >

                            <div class="flex items-center justify-between">

                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                >
                                    <i class="fa-solid fa-phone text-lg"></i>
                                </div>

                                <span class="text-xs text-gray-400">
                                    Contact
                                </span>

                            </div>

                            <div class="mt-4">

                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Main Mobile
                                </p>

                                <h3
                                    class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90"
                                >
                                    <?= htmlspecialchars($company_mobile); ?>
                                </h3>

                            </div>

                        </div>


                        <!-- Created -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
                        >

                            <div class="flex items-center justify-between">

                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400"
                                >
                                    <i class="fa-solid fa-calendar-plus text-lg"></i>
                                </div>

                                <span class="text-xs text-gray-400">
                                    Since
                                </span>

                            </div>

                            <div class="mt-4">

                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Registered
                                </p>

                                <h3
                                    class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90"
                                >
                                    <?= htmlspecialchars($date_created); ?>
                                </h3>

                            </div>

                        </div>

                    </div>



                    <!-- =====================================================
                         MAIN INFORMATION GRID
                    ====================================================== -->

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">


                        <!-- =================================================
                             COMPANY INFORMATION
                        ================================================== -->

                        <div
                            class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
                        >

                            <div class="mb-6 flex items-center justify-between">

                                <div>

                                    <h3
                                        class="text-lg font-semibold text-gray-800 dark:text-white/90"
                                    >
                                        Company Information
                                    </h3>

                                    <p
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Core identity and registration details.
                                    </p>

                                </div>


                                <a
                                    href="company-information.php"
                                    class="text-sm font-medium text-brand-500 hover:text-brand-600"
                                >
                                    Edit
                                </a>

                            </div>


                            <div
                                class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2"
                            >

                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Company Name
                                    </p>

                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                        <?= htmlspecialchars($company_name); ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Company Slug
                                    </p>

                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        <?= htmlspecialchars($company_slug); ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Industry
                                    </p>

                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        <?= htmlspecialchars($company_industry); ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Company Status
                                    </p>

                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        <?= htmlspecialchars(ucfirst($status)); ?>
                                    </p>

                                </div>


                                <div class="sm:col-span-2">

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Company Domain
                                    </p>

                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        <?= htmlspecialchars($company_domain); ?>
                                    </p>

                                </div>

                            </div>

                        </div>



                        <!-- =================================================
                             SYSTEM PROFILE
                        ================================================== -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
                        >

                            <div class="mb-6">

                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-white/90"
                                >
                                    System Profile
                                </h3>

                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Ubamus company identity.
                                </p>

                            </div>


                            <div class="space-y-5">


                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Company ID
                                    </p>

                                    <div
                                        class="rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        #<?= htmlspecialchars($company_id); ?>
                                    </div>

                                </div>


                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Location ID
                                    </p>

                                    <div
                                        class="rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        <?= htmlspecialchars($location_id); ?>
                                    </div>

                                </div>


                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Created By
                                    </p>

                                    <div
                                        class="text-sm font-medium text-gray-800 dark:text-white/90"
                                    >
                                        <?= htmlspecialchars($creator); ?>
                                    </div>

                                </div>


                                <div>

                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Created On
                                    </p>

                                    <div
                                        class="text-sm font-medium text-gray-800 dark:text-white/90"
                                    >
                                        <?= htmlspecialchars($date_created); ?>
                                        <span class="text-gray-400">
                                            at
                                        </span>
                                        <?= htmlspecialchars($time_created); ?>
                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- =================================================
                             ADDRESS
                        ================================================== -->

                        <div
                            class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
                        >

                            <div class="mb-6 flex items-center justify-between">

                                <div>

                                    <h3
                                        class="text-lg font-semibold text-gray-800 dark:text-white/90"
                                    >
                                        Address & Location
                                    </h3>

                                    <p
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Where this company operates.
                                    </p>

                                </div>


                                <a
                                    href="company-information.php"
                                    class="text-sm font-medium text-brand-500 hover:text-brand-600"
                                >
                                    Manage
                                </a>

                            </div>


                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">


                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                                >

                                    <div class="mb-3 flex items-center gap-3">

                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            <i class="fa-solid fa-location-dot"></i>
                                        </div>

                                        <div>

                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Office Address
                                            </p>

                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                Primary Office
                                            </p>

                                        </div>

                                    </div>


                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        <?= nl2br(htmlspecialchars($company_office_address)); ?>
                                    </p>

                                </div>


                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                                >

                                    <div class="mb-3 flex items-center gap-3">

                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            <i class="fa-solid fa-building"></i>
                                        </div>

                                        <div>

                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Head Office
                                            </p>

                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                Registered Location
                                            </p>

                                        </div>

                                    </div>


                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        <?= nl2br(htmlspecialchars($company_head_office)); ?>
                                    </p>

                                </div>

                            </div>

                        </div>



                        <!-- =================================================
                             CONTACT SNAPSHOT
                        ================================================== -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
                        >

                            <div class="mb-6">

                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-white/90"
                                >
                                    Contact Channels
                                </h3>

                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Primary communication points.
                                </p>

                            </div>


                            <div class="space-y-4">


                                <div class="flex items-start gap-3">

                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400"
                                    >
                                        <i class="fa-solid fa-envelope"></i>
                                    </div>

                                    <div class="min-w-0">

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Company Email
                                        </p>

                                        <p class="mt-1 break-all text-sm font-medium text-gray-800 dark:text-white/90">
                                            <?= htmlspecialchars($company_email); ?>
                                        </p>

                                    </div>

                                </div>


                                <div class="flex items-start gap-3">

                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                    >
                                        <i class="fa-solid fa-phone"></i>
                                    </div>

                                    <div>

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Mobile
                                        </p>

                                        <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
                                            <?= htmlspecialchars($company_mobile); ?>
                                        </p>

                                    </div>

                                </div>


                                <div class="flex items-start gap-3">

                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400"
                                    >
                                        <i class="fa-solid fa-headset"></i>
                                    </div>

                                    <div class="min-w-0">

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Customer Care
                                        </p>

                                        <p class="mt-1 break-all text-sm font-medium text-gray-800 dark:text-white/90">
                                            <?= htmlspecialchars($company_customer_care); ?>
                                        </p>

                                    </div>

                                </div>


                                <div class="flex items-start gap-3">

                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400"
                                    >
                                        <i class="fa-solid fa-life-ring"></i>
                                    </div>

                                    <div class="min-w-0">

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Support Email
                                        </p>

                                        <p class="mt-1 break-all text-sm font-medium text-gray-800 dark:text-white/90">
                                            <?= htmlspecialchars($company_support_email); ?>
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="mt-6">

                                <a
                                    href="company-information.php"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.04]"
                                >

                                    <i class="fa-solid fa-address-book"></i>

                                    Manage Contacts

                                </a>

                            </div>

                        </div>



                        <!-- =================================================
                             COMPANY SETUP
                        ================================================== -->

                        <div
                            class="xl:col-span-3 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6"
                        >

                            <div class="mb-6">

                                <h3
                                    class="text-lg font-semibold text-gray-800 dark:text-white/90"
                                >
                                    Company Setup
                                </h3>

                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Manage the different layers of your company's Ubamus profile.
                                </p>

                            </div>


                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">


                                <a
                                    href="company-information.php"
                                    class="group rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]"
                                >

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400"
                                        >
                                            <i class="fa-solid fa-building"></i>
                                        </div>

                                        <div>

                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                Company Information
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Identity & industry
                                            </p>

                                        </div>

                                    </div>

                                </a>


                                <a
                                    href="company-information.php"
                                    class="group rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]"
                                >

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                        >
                                            <i class="fa-solid fa-phone"></i>
                                        </div>

                                        <div>

                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                Contact Information
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Communication channels
                                            </p>

                                        </div>

                                    </div>

                                </a>


                                <a
                                    href="company-information.php"
                                    class="group rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]"
                                >

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400"
                                        >
                                            <i class="fa-solid fa-map-location-dot"></i>
                                        </div>

                                        <div>

                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                Address & Location
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Offices & locations
                                            </p>

                                        </div>

                                    </div>

                                </a>


                                <a
                                    href="company-information.php"
                                    class="group rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]"
                                >

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400"
                                        >
                                            <i class="fa-solid fa-palette"></i>
                                        </div>

                                        <div>

                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                Branding
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Logo & identity
                                            </p>

                                        </div>

                                    </div>

                                </a>

                            </div>

                        </div>


                    </div>


                </div>

            </main>

            <!-- ===== Main Content End ===== -->

        </div>

        <!-- ===== Content Area End ===== -->

    </div>

    <!-- ===== Page Wrapper End ===== -->


    <?php include('footer.php'); ?>


    <script
        data-cfasync="false"
        src="../company/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js">
    </script>

    <script
        defer
        src="../company/bundle.js">
    </script>


</body>

</html>