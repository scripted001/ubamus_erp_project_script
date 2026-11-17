<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


// ============================================================
// AUTHENTICATED USER
// ============================================================

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "Unauthorized access.";
    exit;
}


// ============================================================
// GET COMPANY ID FROM AUTHENTICATED USER
// ============================================================

$stmt = $pdo->prepare("
    SELECT company_id
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$company_id = $user['company_id'] ?? null;

if (!$company_id) {
    echo "No company is associated with this account.";
    exit;
}


// ============================================================
// FETCH COMPANY
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$company_id]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo "Company information could not be found.";
    exit;
}


// ============================================================
// FETCH COMPANY LOCATIONS
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        company_id,
        type,
        name,
        address_line1,
        address_line2,
        city,
        state,
        lga,
        status
    FROM locations
    WHERE company_id = ?
    AND status = 1
    ORDER BY name ASC
");

$stmt->execute([$company_id]);

$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// CURRENT LOCATION
// ============================================================

$selected_location_id = !empty($company['location_id'])
    ? (int)$company['location_id']
    : null;


// ============================================================
// UPDATE VARIABLES
// ============================================================

$success_message = '';
$error_message   = '';


// ============================================================
// UPDATE COMPANY
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {

    // --------------------------------------------------------
    // Basic information
    // --------------------------------------------------------

    $company_name     = trim($_POST['company_name'] ?? '');
    $company_slug     = trim($_POST['company_slug'] ?? '');
    $company_domain   = trim($_POST['company_domain'] ?? '');
    $company_industry = trim($_POST['company_industry'] ?? '');

    // --------------------------------------------------------
    // Contact information
    // --------------------------------------------------------

    $company_customer_care = trim(
        $_POST['company_customer_care'] ?? ''
    );

    $company_support_email = trim(
        $_POST['company_support_email'] ?? ''
    );

    $company_email = trim(
        $_POST['company_email'] ?? ''
    );

    $company_mobile = trim(
        $_POST['company_mobile'] ?? ''
    );

    // --------------------------------------------------------
    // LOCATION
    // --------------------------------------------------------

    $location_id = !empty($_POST['location_id'])
        ? (int)$_POST['location_id']
        : null;

    // --------------------------------------------------------
    // Status
    // --------------------------------------------------------

    $status = trim($_POST['status'] ?? '');


    // --------------------------------------------------------
    // Optional credentials
    // --------------------------------------------------------

    $customer_care_password = trim(
        $_POST['customer_care_password'] ?? ''
    );

    $support_email_password = trim(
        $_POST['support_email_password'] ?? ''
    );


    // ========================================================
    // VALIDATION
    // ========================================================

    if ($company_name === '') {

        $error_message = "Company name is required.";

    } elseif ($company_industry === '') {

        $error_message = "Company industry is required.";

    } elseif ($company_email === '') {

        $error_message = "Company email is required.";

    } elseif ($company_mobile === '') {

        $error_message = "Company mobile number is required.";

    } elseif (!$location_id) {

        $error_message = "Please select a company location.";

    } else {

        try {

            $pdo->beginTransaction();


            // =================================================
            // VERIFY LOCATION BELONGS TO THIS COMPANY
            // =================================================

            $stmt = $pdo->prepare("
                SELECT id
                FROM locations
                WHERE id = ?
                AND company_id = ?
                AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $location_id,
                $company_id
            ]);

            $valid_location = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$valid_location) {

                throw new Exception(
                    "The selected location does not belong to this company."
                );
            }


            // =================================================
            // EXISTING LOGO
            // =================================================

            $company_logo = $company['company_logo'] ?? '';


            // =================================================
            // COMPANY LOGO UPLOAD
            // =================================================

            if (
                isset($_FILES['company_logo']) &&
                $_FILES['company_logo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES['company_logo']['error'] !==
                    UPLOAD_ERR_OK
                ) {

                    throw new Exception(
                        "There was a problem uploading the company logo."
                    );
                }


                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];


                $file_name = $_FILES['company_logo']['name'];
                $file_tmp  = $_FILES['company_logo']['tmp_name'];
                $file_size = $_FILES['company_logo']['size'];

                $extension = strtolower(
                    pathinfo(
                        $file_name,
                        PATHINFO_EXTENSION
                    )
                );


                // Maximum 5MB

                if ($file_size > 5 * 1024 * 1024) {

                    throw new Exception(
                        "Company logo must not be larger than 5MB."
                    );
                }


                if (
                    !in_array(
                        $extension,
                        $allowed_extensions,
                        true
                    )
                ) {

                    throw new Exception(
                        "Invalid logo format. Please upload JPG, JPEG, PNG or WEBP."
                    );
                }


                // Verify image

                $image_info = getimagesize($file_tmp);

                if ($image_info === false) {

                    throw new Exception(
                        "The uploaded file is not a valid image."
                    );
                }


                // Upload directory

                $upload_directory =
                    '../uploads/company_logos/';


                if (!is_dir($upload_directory)) {

                    if (
                        !mkdir(
                            $upload_directory,
                            0755,
                            true
                        )
                    ) {

                        throw new Exception(
                            "Unable to create the company logo directory."
                        );
                    }
                }


                // Generate unique filename

                $new_file_name =
                    'company_' .
                    $company_id .
                    '_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $extension;


                $upload_path =
                    $upload_directory .
                    $new_file_name;


                if (
                    !move_uploaded_file(
                        $file_tmp,
                        $upload_path
                    )
                ) {

                    throw new Exception(
                        "Unable to save the company logo."
                    );
                }


                $company_logo =
                    'uploads/company_logos/' .
                    $new_file_name;
            }


            // =================================================
            // UPDATE COMPANY
            // =================================================

            if (
                $customer_care_password !== '' &&
                $support_email_password !== ''
            ) {

                $sql = "
                    UPDATE companies
                    SET
                        company_name = ?,
                        company_slug = ?,
                        company_logo = ?,
                        company_domain = ?,
                        company_industry = ?,
                        company_customer_care = ?,
                        customer_care_password = ?,
                        company_support_email = ?,
                        support_email_password = ?,
                        company_email = ?,
                        company_mobile = ?,
                        location_id = ?,
                        status = ?
                    WHERE id = ?
                ";

                $params = [
                    $company_name,
                    $company_slug,
                    $company_logo,
                    $company_domain !== ''
                        ? $company_domain
                        : null,
                    $company_industry,
                    $company_customer_care,
                    $customer_care_password,
                    $company_support_email,
                    $support_email_password,
                    $company_email,
                    $company_mobile,
                    $location_id,
                    $status,
                    $company_id
                ];

            } elseif ($customer_care_password !== '') {

                $sql = "
                    UPDATE companies
                    SET
                        company_name = ?,
                        company_slug = ?,
                        company_logo = ?,
                        company_domain = ?,
                        company_industry = ?,
                        company_customer_care = ?,
                        customer_care_password = ?,
                        company_support_email = ?,
                        company_email = ?,
                        company_mobile = ?,
                        location_id = ?,
                        status = ?
                    WHERE id = ?
                ";

                $params = [
                    $company_name,
                    $company_slug,
                    $company_logo,
                    $company_domain !== ''
                        ? $company_domain
                        : null,
                    $company_industry,
                    $company_customer_care,
                    $customer_care_password,
                    $company_support_email,
                    $company_email,
                    $company_mobile,
                    $location_id,
                    $status,
                    $company_id
                ];

            } elseif ($support_email_password !== '') {

                $sql = "
                    UPDATE companies
                    SET
                        company_name = ?,
                        company_slug = ?,
                        company_logo = ?,
                        company_domain = ?,
                        company_industry = ?,
                        company_customer_care = ?,
                        company_support_email = ?,
                        support_email_password = ?,
                        company_email = ?,
                        company_mobile = ?,
                        location_id = ?,
                        status = ?
                    WHERE id = ?
                ";

                $params = [
                    $company_name,
                    $company_slug,
                    $company_logo,
                    $company_domain !== ''
                        ? $company_domain
                        : null,
                    $company_industry,
                    $company_customer_care,
                    $company_support_email,
                    $support_email_password,
                    $company_email,
                    $company_mobile,
                    $location_id,
                    $status,
                    $company_id
                ];

            } else {

                $sql = "
                    UPDATE companies
                    SET
                        company_name = ?,
                        company_slug = ?,
                        company_logo = ?,
                        company_domain = ?,
                        company_industry = ?,
                        company_customer_care = ?,
                        company_support_email = ?,
                        company_email = ?,
                        company_mobile = ?,
                        location_id = ?,
                        status = ?
                    WHERE id = ?
                ";

                $params = [
                    $company_name,
                    $company_slug,
                    $company_logo,
                    $company_domain !== ''
                        ? $company_domain
                        : null,
                    $company_industry,
                    $company_customer_care,
                    $company_support_email,
                    $company_email,
                    $company_mobile,
                    $location_id,
                    $status,
                    $company_id
                ];
            }


            $stmt = $pdo->prepare($sql);

            $stmt->execute($params);


            $pdo->commit();


            $success_message =
                "Company information has been updated successfully.";


            // Refresh company data

            $stmt = $pdo->prepare("
                SELECT *
                FROM companies
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$company_id]);

            $company = $stmt->fetch(PDO::FETCH_ASSOC);

            $selected_location_id =
                !empty($company['location_id'])
                    ? (int)$company['location_id']
                    : null;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error_message = $e->getMessage();
        }
    }
}


// ============================================================
// COMPANY VARIABLES
// ============================================================

$company_name =
    $company['company_name'] ?? '';

$company_slug =
    $company['company_slug'] ?? '';

$company_logo =
    $company['company_logo'] ?? '';

$company_domain =
    $company['company_domain'] ?? '';

$company_industry =
    $company['company_industry'] ?? '';

$company_customer_care =
    $company['company_customer_care'] ?? '';

$company_support_email =
    $company['company_support_email'] ?? '';

$company_email =
    $company['company_email'] ?? '';

$company_mobile =
    $company['company_mobile'] ?? '';

$status =
    $company['status'] ?? '';

$creator =
    $company['creator'] ?? '';

$date_created =
    $company['date_created'] ?? '';

$time_created =
    $company['time_created'] ?? '';


// ============================================================
// CURRENT LOCATION DETAILS
// ============================================================

$current_location = null;

if ($selected_location_id) {

    foreach ($locations as $location) {

        if ((int)$location['id'] === $selected_location_id) {

            $current_location = $location;
            break;

        }
    }
}


// ============================================================
// LOGO
// ============================================================

$logo_path = '';

if (!empty($company_logo)) {

    $logo_path =
        '../uploads/logos/' . ltrim($company_logo, '/');
}


// ============================================================
// COMPANY INITIALS
// ============================================================

$company_initials = '';

$name_parts = preg_split(
    '/\s+/',
    trim($company_name)
);

foreach ($name_parts as $part) {

    if (!empty($part)) {

        $company_initials .= strtoupper(
            substr($part, 0, 1)
        );
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

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($company_name); ?>
        | Company Information
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
        $watch(
            'darkMode',
            value => localStorage.setItem(
                'darkMode',
                JSON.stringify(value)
            )
        )
    "

    :class="{'dark bg-gray-900': darkMode === true}"
>


<div class="flex h-screen overflow-hidden">


    <?php include('sidebar.php'); ?>


    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


        <main>


            <?php include('header.php'); ?>


            <div
                class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6"
            >


                <!-- =====================================================
                     PAGE HEADER
                ====================================================== -->

                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">


                    <div>

                        <h2
                            class="text-xl font-semibold text-gray-800 dark:text-white/90"
                        >

                            Company Information

                        </h2>

                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >

                            Manage your company's core profile information.

                        </p>

                    </div>


                    <a
                        href="company-overview.php"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Overview

                    </a>

                </div>



                <!-- =====================================================
                     SUCCESS
                ====================================================== -->

                <?php if (!empty($success_message)): ?>

                    <div
                        class="mb-6 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-4 dark:border-success-500/20 dark:bg-success-500/10"
                    >

                        <i
                            class="fa-solid fa-circle-check text-success-600"
                        ></i>

                        <p
                            class="text-sm font-medium text-success-700 dark:text-success-400"
                        >

                            <?= htmlspecialchars($success_message); ?>

                        </p>

                    </div>

                <?php endif; ?>



                <!-- =====================================================
                     ERROR
                ====================================================== -->

                <?php if (!empty($error_message)): ?>

                    <div
                        class="mb-6 flex items-center gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-4 dark:border-error-500/20 dark:bg-error-500/10"
                    >

                        <i
                            class="fa-solid fa-circle-exclamation text-error-600"
                        ></i>

                        <p
                            class="text-sm font-medium text-error-700 dark:text-error-400"
                        >

                            <?= htmlspecialchars($error_message); ?>

                        </p>

                    </div>

                <?php endif; ?>



                <!-- =====================================================
                     COMPANY HEADER
                ====================================================== -->

                <div
                    class="relative mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
                >

                    <div
                        class="absolute inset-0 pointer-events-none"
                        style="
                            background:
                            radial-gradient(
                                circle at 90% 10%,
                                rgba(59,130,246,0.12),
                                transparent 30%
                            ),
                            radial-gradient(
                                circle at 10% 90%,
                                rgba(99,102,241,0.08),
                                transparent 30%
                            );
                        "
                    ></div>


                    <div class="relative p-6">

                        <div
                            class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
                        >


                            <div class="flex items-center gap-4">


                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"
                                >

                                    <?php if (!empty($logo_path)): ?>

                                        <img 
                                            src="<?= htmlspecialchars($logo_path); ?>" 
                                            alt="Company Logo" 
                                            class="block object-contain" style="width: 100px; height: auto;"
                                        >

                                    <?php else: ?>

                                        <span
                                            class="text-xl font-bold text-gray-500 dark:text-gray-300"
                                        >

                                            <?= htmlspecialchars($company_initials); ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div>

                                    <h1
                                        class="text-xl font-bold text-gray-800 dark:text-white/90"
                                    >

                                        <?= htmlspecialchars($company_name); ?>

                                    </h1>

                                    <p
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >

                                        Company ID #<?= htmlspecialchars($company_id); ?>

                                    </p>

                                </div>


                            </div>


                            <div>

                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold
                                    <?= strtolower($status) === 'active'
                                        ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                    ?>"
                                >

                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-current"
                                    ></span>

                                    <?= htmlspecialchars(
                                        ucfirst($status ?: 'Unknown')
                                    ); ?>

                                </span>

                            </div>


                        </div>

                    </div>

                </div>



                <!-- =====================================================
                     FORM
                ====================================================== -->

                <form
                    method="POST"
                    action="proceed-edit-company-data.php"
                    enctype="multipart/form-data"
                >


                    <!-- =================================================
                         BASIC INFORMATION
                    ================================================== -->

                    <div
                        class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
                    >

                        <div class="mb-6">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10"
                                >

                                    <i class="fa-solid fa-building"></i>

                                </div>

                                <div>

                                    <h3
                                        class="font-semibold text-gray-800 dark:text-white/90"
                                    >

                                        Basic Information

                                    </h3>

                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >

                                        Core identity of your company.

                                    </p>

                                </div>

                            </div>

                        </div>


                        <div
                            class="grid grid-cols-1 gap-6 md:grid-cols-2"
                        >


                            <!-- Company Name -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Company Name
                                    <span class="text-error-500">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="company_name"
                                    value="<?= htmlspecialchars($company_name); ?>"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <!-- Company Slug -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Company Slug

                                </label>

                                <input
                                    type="text"
                                    name="company_slug"
                                    value="<?= htmlspecialchars($company_slug); ?>"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <!-- Industry -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Industry
                                    <span class="text-error-500">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="company_industry"
                                    value="<?= htmlspecialchars($company_industry); ?>"

                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <!-- Domain -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Company Domain

                                </label>

                                <input
                                    type="text"
                                    name="company_domain"
                                    value="<?= htmlspecialchars($company_domain); ?>"
                                    placeholder="example.com"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <!-- Status -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Company Status

                                </label>

                                <select
                                    name="status"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                                    <option
                                        value="active"
                                        <?= strtolower($status) === 'active' ? 'selected' : ''; ?>
                                    >
                                        Active
                                    </option>

                                    <option
                                        value="inactive"
                                        <?= strtolower($status) === 'inactive' ? 'selected' : ''; ?>
                                    >
                                        Inactive
                                    </option>

                                </select>

                            </div>


                        </div>

                    </div>



                    <!-- =================================================
                         BRANDING
                    ================================================== -->

                    <div
                        class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
                    >

                        <div class="mb-6">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10"
                                >

                                    <i class="fa-solid fa-image"></i>

                                </div>

                                <div>

                                    <h3
                                        class="font-semibold text-gray-800 dark:text-white/90"
                                    >

                                        Company Branding

                                    </h3>

                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >

                                        Upload or replace the company logo.

                                    </p>

                                </div>

                            </div>

                        </div>


                        <div
                            class="flex flex-col gap-5 sm:flex-row sm:items-center"
                        >

                            <div
                                class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"
                            >

                                <?php if (!empty($logo_path)): ?>

                                    <img
                                        src="<?= htmlspecialchars($logo_path); ?>"
                                        alt="Company Logo"
                                        class="block object-contain" style="width: 100px; height: auto;"
                                    >

                                <?php else: ?>

                                    <span
                                        class="text-2xl font-bold text-gray-400"
                                    >

                                        <?= htmlspecialchars($company_initials); ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="flex-1">

                                <input
                                    type="file"
                                    name="company_logo"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    class="block w-full rounded-xl border border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:border-0 file:bg-gray-50 file:px-4 file:py-3 file:text-sm file:font-medium dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:file:bg-gray-800"
                                >

                                <p
                                    class="mt-2 text-xs text-gray-400"
                                >

                                    JPG, JPEG, PNG or WEBP · Maximum 5MB

                                </p>

                            </div>

                        </div>

                    </div>



                    <!-- =================================================
                         CONTACT INFORMATION
                    ================================================== -->

                    <div
                        class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
                    >

                        <div class="mb-6">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10"
                                >

                                    <i class="fa-solid fa-address-book"></i>

                                </div>

                                <div>

                                    <h3
                                        class="font-semibold text-gray-800 dark:text-white/90"
                                    >

                                        Contact Information

                                    </h3>

                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >

                                        Primary communication channels.

                                    </p>

                                </div>

                            </div>

                        </div>


                        <div
                            class="grid grid-cols-1 gap-6 md:grid-cols-2"
                        >


                            <!-- Company Email -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Company Email
                                    <span class="text-error-500">*</span>

                                </label>

                                <input
                                    type="email"
                                    name="company_email"
                                    value="<?= htmlspecialchars($company_email); ?>"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <!-- Mobile -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Company Mobile
                                    <span class="text-error-500">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="company_mobile"
                                    value="<?= htmlspecialchars($company_mobile); ?>"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <!-- Customer Care -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Customer Care

                                </label>

                                <input
                                    type="text"
                                    name="company_customer_care"
                                    value="<?= htmlspecialchars($company_customer_care); ?>"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <!-- Support Email -->

                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Support Email

                                </label>

                                <input
                                    type="email"
                                    name="company_support_email"
                                    value="<?= htmlspecialchars($company_support_email); ?>"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                        </div>

                    </div>



                    <!-- =================================================
                         LOCATION
                    ================================================== -->

                    <div
                        class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
                        x-data="{
                            selectedLocation: <?= $selected_location_id ?: 'null' ?>,

                            locations: <?= htmlspecialchars(
                                json_encode(
                                    $locations,
                                    JSON_HEX_TAG |
                                    JSON_HEX_APOS |
                                    JSON_HEX_AMP |
                                    JSON_HEX_QUOT
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>,

                            get currentLocation() {
                                return this.locations.find(
                                    location =>
                                        parseInt(location.id) ===
                                        parseInt(this.selectedLocation)
                                ) || null;
                            }
                        }"
                    >


                        <div class="mb-6">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10"
                                >

                                    <i class="fa-solid fa-location-dot"></i>

                                </div>

                                <div>

                                    <h3
                                        class="font-semibold text-gray-800 dark:text-white/90"
                                    >

                                        Company Location

                                    </h3>

                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >

                                        Select one of your company's registered locations.

                                    </p>

                                </div>

                            </div>

                        </div>


                        <?php if (empty($locations)): ?>

                            <div
                                class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-500/20 dark:bg-warning-500/10"
                            >

                                <div class="flex gap-3">

                                    <i
                                        class="fa-solid fa-triangle-exclamation mt-0.5 text-warning-600"
                                    ></i>

                                    <div>

                                        <p
                                            class="text-sm font-semibold text-warning-700 dark:text-warning-400"
                                        >

                                            No active locations found.

                                        </p>

                                        <p
                                            class="mt-1 text-xs text-warning-600 dark:text-warning-400"
                                        >

                                            Create a company location before assigning one here.

                                        </p>

                                    </div>

                                </div>

                            </div>

                        <?php else: ?>


                            <!-- Location Selector -->

                            <div class="mb-6">

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Select Location
                                    <span class="text-error-500">*</span>

                                </label>


                                <select
                                    name="location_id"
                                    x-model="selectedLocation"
                                    class="h-12 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm font-medium text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                                    <option value="">
                                        Select a company location
                                    </option>


                                    <?php foreach ($locations as $location): ?>

                                        <option
                                            value="<?= (int)$location['id']; ?>"
                                            <?= (
                                                $selected_location_id ===
                                                (int)$location['id']
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= htmlspecialchars(
                                                $location['name']
                                            ); ?>

                                            <?php if (!empty($location['type'])): ?>

                                                —
                                                <?= htmlspecialchars(
                                                    $location['type']
                                                ); ?>

                                            <?php endif; ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>



                            <!-- Location Details -->

                            <div
                                x-show="currentLocation"
                                x-cloak
                            >

                                <div
                                    class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/50"
                                >


                                    <div
                                        class="mb-5 flex items-center justify-between"
                                    >

                                        <div>

                                            <p
                                                class="text-xs font-medium uppercase tracking-wider text-gray-400"
                                            >

                                                Selected Location

                                            </p>

                                            <h4
                                                class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90"
                                                x-text="currentLocation?.name"
                                            ></h4>

                                        </div>


                                        <span
                                            class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"
                                            x-text="currentLocation?.type"
                                        ></span>

                                    </div>


                                    <div
                                        class="grid grid-cols-1 gap-5 md:grid-cols-2"
                                    >


                                        <!-- Address Line 1 -->

                                        <div class="md:col-span-2">

                                            <p
                                                class="mb-1 text-xs text-gray-400"
                                            >

                                                Address Line 1

                                            </p>

                                            <p
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                x-text="currentLocation?.address_line1 || 'Not provided'"
                                            ></p>

                                        </div>


                                        <!-- Address Line 2 -->

                                        <div class="md:col-span-2">

                                            <p
                                                class="mb-1 text-xs text-gray-400"
                                            >

                                                Address Line 2

                                            </p>

                                            <p
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                x-text="currentLocation?.address_line2 || 'Not provided'"
                                            ></p>

                                        </div>


                                        <!-- City -->

                                        <div>

                                            <p
                                                class="mb-1 text-xs text-gray-400"
                                            >

                                                City

                                            </p>

                                            <p
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                x-text="currentLocation?.city || 'Not provided'"
                                            ></p>

                                        </div>


                                        <!-- State -->

                                        <div>

                                            <p
                                                class="mb-1 text-xs text-gray-400"
                                            >

                                                State

                                            </p>

                                            <p
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                x-text="currentLocation?.state || 'Not provided'"
                                            ></p>

                                        </div>


                                        <!-- LGA -->

                                        <div>

                                            <p
                                                class="mb-1 text-xs text-gray-400"
                                            >

                                                LGA

                                            </p>

                                            <p
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                x-text="currentLocation?.lga || 'Not provided'"
                                            ></p>

                                        </div>


                                        <!-- Location ID -->

                                        <div>

                                            <p
                                                class="mb-1 text-xs text-gray-400"
                                            >

                                                Location ID

                                            </p>

                                            <p
                                                class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                                x-text="'#' + currentLocation?.id"
                                            ></p>

                                        </div>


                                    </div>

                                </div>

                            </div>


                        <?php endif; ?>


                    </div>



                    <!-- =================================================
                         COMMUNICATION CREDENTIALS
                    ================================================== -->

                    <div
                        class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
                    >

                        <div class="mb-6">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/10"
                                >

                                    <i class="fa-solid fa-key"></i>

                                </div>

                                <div>

                                    <h3
                                        class="font-semibold text-gray-800 dark:text-white/90"
                                    >

                                        Communication Credentials

                                    </h3>

                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >

                                        Leave these fields empty when no credential change is required.

                                    </p>

                                </div>

                            </div>

                        </div>


                        <div
                            class="grid grid-cols-1 gap-6 md:grid-cols-2"
                        >


                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Customer Care Password

                                </label>

                                <input
                                    type="password"
                                    name="customer_care_password"
                                    autocomplete="new-password"
                                    placeholder="Leave blank to keep current password"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                            <div>

                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >

                                    Support Email Password

                                </label>

                                <input
                                    type="password"
                                    name="support_email_password"
                                    autocomplete="new-password"
                                    placeholder="Leave blank to keep current password"
                                    class="h-11 w-full rounded-xl border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >

                            </div>


                        </div>

                    </div>



                    <!-- =================================================
                         SYSTEM INFORMATION
                    ================================================== -->

                    <div
                        class="mb-6 rounded-2xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/40"
                    >

                        <div class="mb-5">

                            <h3
                                class="font-semibold text-gray-800 dark:text-white/90"
                            >

                                System Information

                            </h3>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >

                                System-generated company information.

                            </p>

                        </div>


                        <div
                            class="grid grid-cols-2 gap-5 md:grid-cols-4"
                        >

                            <div>

                                <p class="text-xs text-gray-400">
                                    Company ID
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90"
                                >

                                    #<?= htmlspecialchars($company_id); ?>

                                </p>

                            </div>


                            <div>

                                <p class="text-xs text-gray-400">
                                    Created By
                                </p>

                                <p
                                    class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($creator); ?>

                                </p>

                            </div>


                            <div>

                                <p class="text-xs text-gray-400">
                                    Date Created
                                </p>

                                <p
                                    class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($date_created); ?>

                                </p>

                            </div>


                            <div>

                                <p class="text-xs text-gray-400">
                                    Time Created
                                </p>

                                <p
                                    class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90"
                                >

                                    <?= htmlspecialchars($time_created); ?>

                                </p>

                            </div>

                        </div>

                    </div>



                    <!-- =================================================
                         ACTIONS
                    ================================================== -->

                    <div
                        class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-center sm:justify-between"
                    >

                        <div>

                            <p
                                class="text-sm font-medium text-gray-800 dark:text-white/90"
                            >

                                Save Company Changes

                            </p>

                            <p
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >

                                Your selected location will be linked to this company.

                            </p>

                        </div>


                        <div
                            class="flex flex-col gap-3 sm:flex-row"
                        >

                            <a
                                href="company-overview.php"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >

                                Cancel

                            </a>


                            <button
                                type="submit"
                                name="update_company"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-600"
                            >

                                <i class="fa-solid fa-floppy-disk"></i>

                                Save Changes

                            </button>

                        </div>

                    </div>


                </form>


            </div>

        </main>

    </div>

</div>


<?php include('footer.php'); ?>


<script
    defer
    src="../company/bundle.js"
></script>


</body>

</html>