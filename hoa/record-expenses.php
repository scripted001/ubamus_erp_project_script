
<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


$company_id = $_SESSION['company_id'];
$user_email = $_SESSION['user_email'];
$approved_by = "Not Approved!";


// echo $companyid;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />

    <meta
        name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    />

    <meta http-equiv="X-UA-Compatible" content="ie=edge" />

    <title>
        <?php echo htmlspecialchars($company_name); ?> | Create Expense
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>

        /* =========================================================
           EXPENSE PAGE — LIGHT GLASSMORPHISM
        ========================================================= */

        :root {
            --expense-primary: #2563eb;
            --expense-primary-dark: #1d4ed8;
            --expense-text: #172033;
            --expense-muted: #64748b;
            --expense-border: rgba(148, 163, 184, 0.25);
            --expense-card: rgba(255, 255, 255, 0.82);
            --expense-input: rgba(255, 255, 255, 0.72);
        }

        body {
            background:
                radial-gradient(
                    circle at 8% 10%,
                    rgba(37, 99, 235, 0.09),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 92% 18%,
                    rgba(14, 165, 233, 0.09),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 50% 100%,
                    rgba(99, 102, 241, 0.07),
                    transparent 35%
                ),
                #f8fafc;
        }

        /* Futuristic background decoration */

        .expense-page-background {
            position: relative;
        }

        .expense-page-background::before {
            content: "";
            position: fixed;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            border: 1px solid rgba(37, 99, 235, 0.08);
            top: 100px;
            right: -120px;
            pointer-events: none;
            z-index: 0;
        }

        .expense-page-background::after {
            content: "";
            position: fixed;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            border: 1px solid rgba(14, 165, 233, 0.08);
            bottom: 50px;
            left: -100px;
            pointer-events: none;
            z-index: 0;
        }

        /* Main content */

        .expense-content {
            position: relative;
            z-index: 1;
        }

        /* Header */

        .expense-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 7px;
        }

        .expense-heading-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(
                135deg,
                rgba(37, 99, 235, 0.12),
                rgba(14, 165, 233, 0.08)
            );

            border: 1px solid rgba(37, 99, 235, 0.16);
            color: #2563eb;

            box-shadow:
                0 10px 25px rgba(37, 99, 235, 0.08),
                inset 0 1px 0 rgba(255,255,255,0.8);
        }

        .expense-heading-icon i {
            font-size: 20px;
        }

        .expense-title {
            margin: 0;
            color: #172033 !important;
            font-size: 25px;
            font-weight: 750;
            letter-spacing: -0.02em;
        }

        .expense-subtitle {
            margin: 2px 0 0;
            color: #64748b !important;
            font-size: 13px;
        }

        /* Breadcrumb */

        .expense-breadcrumb {
            margin-top: 18px;
            margin-bottom: 22px;
        }

        .expense-breadcrumb a {
            color: #64748b !important;
            transition: 0.2s ease;
        }

        .expense-breadcrumb a:hover {
            color: #2563eb !important;
        }

        .expense-breadcrumb-current {
            color: #334155 !important;
        }

        /* Main glass card */

        .expense-glass-card {
            position: relative;
            overflow: hidden;

            background: var(--expense-card) !important;

            border: 1px solid rgba(148, 163, 184, 0.25) !important;

            border-radius: 24px !important;

            box-shadow:
                0 20px 50px rgba(15, 23, 42, 0.07),
                0 4px 15px rgba(15, 23, 42, 0.035),
                inset 0 1px 0 rgba(255,255,255,0.9);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .expense-glass-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;

            background: linear-gradient(
                90deg,
                #2563eb,
                #0ea5e9,
                #6366f1
            );

            opacity: 0.8;
        }

        .expense-card-content {
            padding: 28px;
        }

        /* Form top */

        .expense-form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding-bottom: 20px;
            margin-bottom: 24px;

            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }

        .expense-form-title {
            display: flex;
            align-items: center;
            gap: 11px;

            color: #172033 !important;
            font-size: 17px;
            font-weight: 700;
        }

        .expense-form-title-icon {
            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: rgba(37, 99, 235, 0.09);
            color: #2563eb;
        }

        .expense-form-description {
            margin: 4px 0 0 47px;
            color: #64748b !important;
            font-size: 12px;
        }

        /* Field */

        .expense-field {
            position: relative;
        }

        .expense-label {
            display: flex !important;
            align-items: center;
            gap: 7px;

            margin-bottom: 8px !important;

            color: #334155 !important;
            font-size: 13px !important;
            font-weight: 650 !important;
        }

        .expense-label i {
            width: 16px;
            color: #2563eb;
            font-size: 12px;
        }

        .expense-input,
        .expense-select,
        .expense-textarea {
            width: 100%;

            border: 1px solid rgba(148, 163, 184, 0.38) !important;

            background: rgba(255,255,255,0.78) !important;

            color: #172033 !important;

            border-radius: 12px !important;

            box-shadow:
                0 3px 12px rgba(15, 23, 42, 0.025),
                inset 0 1px 1px rgba(255,255,255,0.8);

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease,
                transform 0.2s ease;
        }

        .expense-input,
        .expense-select {
            height: 48px !important;
            padding: 0 15px !important;
        }

        .expense-textarea {
            min-height: 125px !important;
            padding: 13px 15px !important;
            resize: vertical;
        }

        .expense-input::placeholder,
        .expense-textarea::placeholder {
            color: #94a3b8 !important;
        }

        .expense-input:hover,
        .expense-select:hover,
        .expense-textarea:hover {
            border-color: rgba(37, 99, 235, 0.35) !important;
            background: rgba(255,255,255,0.95) !important;
        }

        .expense-input:focus,
        .expense-select:focus,
        .expense-textarea:focus {
            outline: none !important;

            border-color: rgba(37, 99, 235, 0.65) !important;

            background: #ffffff !important;

            box-shadow:
                0 0 0 4px rgba(37, 99, 235, 0.08),
                0 8px 20px rgba(37, 99, 235, 0.06);

            transform: translateY(-1px);
        }

        /* File input */

        .expense-file {
            padding: 7px !important;
            cursor: pointer;
        }

        .expense-file::file-selector-button {
            border: none;
            border-radius: 8px;

            background: rgba(37, 99, 235, 0.10);
            color: #2563eb;

            padding: 7px 12px;
            margin-right: 10px;

            font-size: 12px;
            font-weight: 650;

            cursor: pointer;
        }

        /* Select */

        .expense-select {
            cursor: pointer;
        }

        .expense-select option {
            color: #172033;
            background: #ffffff;
        }

        /* Section grouping */

        .expense-section {
            position: relative;

            padding: 18px;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    rgba(248,250,252,0.72),
                    rgba(255,255,255,0.58)
                );

            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .expense-section-title {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 17px;

            color: #334155;
            font-size: 12px;
            font-weight: 750;

            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .expense-section-title i {
            color: #2563eb;
        }

        /* Buttons */

        .expense-actions {
            display: flex;
            align-items: center;
            gap: 12px;

            padding-top: 8px;
            margin-top: 8px;

            border-top: 1px solid rgba(148, 163, 184, 0.15);
        }

        .expense-create-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            min-height: 46px;

            padding: 0 20px;

            border: 1px solid rgba(37, 99, 235, 0.3);
            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff !important;

            font-size: 13px;
            font-weight: 700;

            box-shadow:
                0 10px 24px rgba(37, 99, 235, 0.18);

            transition: all 0.2s ease;
        }

        .expense-create-btn:hover {
            transform: translateY(-2px);

            background:
                linear-gradient(
                    135deg,
                    #1d4ed8,
                    #1e40af
                );

            box-shadow:
                0 14px 28px rgba(37, 99, 235, 0.24);
        }

        .expense-create-btn:active {
            transform: translateY(0);
        }

        .expense-cancel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            min-height: 46px;

            padding: 0 18px;

            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;

            background: rgba(255,255,255,0.72);

            color: #475569 !important;

            font-size: 13px;
            font-weight: 650;

            transition: all 0.2s ease;
        }

        .expense-cancel-btn:hover {
            background: #ffffff;
            color: #172033 !important;

            border-color: rgba(100,116,139,0.45);

            transform: translateY(-1px);
        }

        /* Responsive */

        @media (max-width: 767px) {

            .expense-card-content {
                padding: 18px;
            }

            .expense-title {
                font-size: 21px;
            }

            .expense-heading-icon {
                width: 42px;
                height: 42px;
                min-width: 42px;
            }

            .expense-form-header {
                align-items: flex-start;
            }

            .expense-form-description {
                margin-left: 0;
                margin-top: 7px;
            }

            .expense-section {
                padding: 14px;
            }

            .expense-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .expense-create-btn,
            .expense-cancel-btn {
                width: 100%;
            }
        }

        /* Extra small screens */

        @media (max-width: 480px) {

            .expense-card-content {
                padding: 14px;
            }

            .expense-heading {
                align-items: flex-start;
            }

            .expense-heading-icon {
                width: 40px;
                height: 40px;
                min-width: 40px;
            }

            .expense-title {
                font-size: 19px;
            }

            .expense-subtitle {
                font-size: 11px;
            }

            .expense-input,
            .expense-select {
                height: 46px !important;
            }

            .expense-textarea {
                min-height: 110px !important;
            }
        }

    </style>

</head>


<body
    class="expense-page-background"
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
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
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


            <div class="expense-content mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">


                <!-- ================= PAGE HEADING ================= -->

                <div class="expense-heading">

                    <div class="expense-heading-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <div>

                        <h1 class="expense-title">
                            Create Expense
                        </h1>

                        <p class="expense-subtitle">
                            Record and submit a new business expense
                        </p>

                    </div>

                </div>


                <!-- ================= BREADCRUMB ================= -->

                <div class="expense-breadcrumb">

                    <nav>

                        <ol class="flex items-center gap-1.5">

                            <li>

                                <a
                                    class="inline-flex items-center gap-1.5 text-sm"
                                    href="index.php"
                                >
                                    <i class="fa-solid fa-house text-xs"></i>
                                    Home

                                    <svg
                                        class="stroke-current"
                                        width="17"
                                        height="16"
                                        viewBox="0 0 17 16"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >

                                        <path
                                            d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                            stroke=""
                                            stroke-width="1.2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />

                                    </svg>

                                </a>

                            </li>

                            <li class="expense-breadcrumb-current text-sm">

                                <i class="fa-solid fa-receipt mr-1"></i>

                                Create Expense

                            </li>

                        </ol>

                    </nav>

                </div>


                <!-- ================= GLASS CARD ================= -->

                <div class="expense-glass-card">


                    <div class="expense-card-content">


                        <!-- Form heading -->

                        <div class="expense-form-header">

                            <div>

                                <div class="expense-form-title">

                                    <span class="expense-form-title-icon">

                                        <i class="fa-solid fa-file-circle-plus"></i>

                                    </span>

                                    Expense Information

                                </div>

                                <p class="expense-form-description">

                                    Enter the details below to register this expense.

                                </p>

                            </div>

                        </div>


                        <!-- ================= FORM ================= -->

                        <form
                            method="POST"
                            action="proceed-expenses.php"
                            enctype="multipart/form-data"
                        >


                            <!-- Hidden Fields -->

                            <input
                                type="hidden"
                                name="company_id"
                                value="<?= htmlspecialchars($company_id) ?>"
                            >

                            <input
                                type="hidden"
                                name="created_by"
                                value="<?= htmlspecialchars($user_email) ?>"
                            >

                            <input
                                type="hidden"
                                name="approved_by"
                                value="<?= htmlspecialchars($approved_by) ?>"
                            >


                            <div class="space-y-5">


                                <!-- ================= BASIC INFORMATION ================= -->

                                <div class="expense-section">

                                    <div class="expense-section-title">

                                        <i class="fa-solid fa-circle-info"></i>

                                        Basic Information

                                    </div>


                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">


                                        <!-- Title -->

                                        <div class="expense-field">

                                            <label class="expense-label">

                                                <i class="fa-solid fa-heading"></i>

                                                Expense Title

                                            </label>

                                            <input
                                                type="text"
                                                name="expense_title"
                                                class="expense-input"
                                                placeholder="Enter expense title"
                                                required
                                            >

                                        </div>


                                        <!-- Amount -->

                                        <div class="expense-field">

                                            <label class="expense-label">

                                                <i class="fa-solid fa-naira-sign"></i>

                                                Amount

                                            </label>

                                            <input
                                                type="text"
                                                name="amount"
                                                class="expense-input"
                                                placeholder="Enter amount"
                                                required
                                            >

                                        </div>


                                        <!-- Description -->

                                        <div class="expense-field xl:col-span-2">

                                            <label class="expense-label">

                                                <i class="fa-solid fa-align-left"></i>

                                                Description

                                            </label>

                                            <textarea
                                                name="description"
                                                class="expense-textarea"
                                                placeholder="Describe what this expense was for..."
                                                required
                                            ></textarea>

                                        </div>


                                    </div>

                                </div>


                                <!-- ================= EXPENSE DETAILS ================= -->

                                <div class="expense-section">

                                    <div class="expense-section-title">

                                        <i class="fa-solid fa-sliders"></i>

                                        Expense Details

                                    </div>


                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">


                                        <!-- Receipt -->

                                        <div class="expense-field">

                                            <label class="expense-label">

                                                <i class="fa-solid fa-file-invoice"></i>

                                                Receipt

                                                <span
                                                    style="
                                                        color:#94a3b8;
                                                        font-size:10px;
                                                        font-weight:500;
                                                        text-transform:none;
                                                        letter-spacing:0;
                                                    "
                                                >
                                                    Optional
                                                </span>

                                            </label>

                                            <input
                                                type="file"
                                                name="receipt"
                                                class="expense-input expense-file"
                                            >

                                        </div>


                                        <!-- Location -->

                                        <div class="expense-field">

                                            <label class="expense-label">

                                                <i class="fa-solid fa-location-dot"></i>

                                                Location

                                            </label>

                                            <select
                                                name="location_id"
                                                required
                                                class="expense-select"
                                            >

                                                <option value="">
                                                    -- Select Location --
                                                </option>

                                                <?php

                                                // Fetch all locations belonging to this company

                                                $stmt = $pdo->prepare("
                                                    SELECT
                                                        id,
                                                        name,
                                                        city,
                                                        state,
                                                        type
                                                    FROM locations
                                                    WHERE company_id = ?
                                                ");

                                                $stmt->execute([$company_id]);

                                                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);


                                                foreach ($locations as $loc) {

                                                    $id = $loc['id'];

                                                    $name = ucfirst($loc['name']);
                                                    $city = ucfirst($loc['city']);
                                                    $state = ucfirst($loc['state']);
                                                    $type = ucfirst($loc['type']);

                                                    ?>

                                                    <option value="<?= htmlspecialchars($id) ?>">

                                                        <?= htmlspecialchars($name) ?>
                                                        (<?= htmlspecialchars($type) ?>)
                                                        —
                                                        <?= htmlspecialchars($city) ?>,
                                                        <?= htmlspecialchars($state) ?>

                                                    </option>

                                                    <?php
                                                }

                                                ?>

                                            </select>

                                        </div>


                                        <!-- Expenses By -->

                                        <div class="expense-field xl:col-span-2">

                                            <label class="expense-label">

                                                <i class="fa-solid fa-user-tie"></i>

                                                Expenses By

                                            </label>

                                            <select
                                                name="staff_responsible"
                                                required
                                                class="expense-select"
                                            >

                                                <option value="">
                                                    -- Select Staff --
                                                </option>

                                                <?php

                                                // Fetch all staff belonging to this company

                                                $stmt = $pdo->prepare("
                                                    SELECT
                                                        id,
                                                        firstname,
                                                        lastname,
                                                        role
                                                    FROM staffs
                                                    WHERE company_id = ?
                                                ");

                                                $stmt->execute([$company_id]);

                                                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


                                                foreach ($users as $user) {

                                                    $staff_ID = $user['id'];
                                                    $firstname = ucfirst($user['firstname']);
                                                    $lastname = ucfirst($user['lastname']);
                                                    $role = ucfirst($user['role']);

                                                    ?>

                                                    <option value="<?= htmlspecialchars($staff_ID) ?>">

                                                        <?= htmlspecialchars($firstname) ?>

                                                        <?php if (!empty($lastname)): ?>

                                                            <?= htmlspecialchars($lastname) ?>

                                                        <?php endif; ?>

                                                        —
                                                        <?= htmlspecialchars($role) ?>

                                                    </option>

                                                    <?php
                                                }

                                                ?>

                                            </select>

                                        </div>


                                    </div>

                                </div>


                                <!-- ================= ACTIONS ================= -->

                                <div class="expense-actions">

                                    <button
                                        type="submit"
                                        name="submit"
                                        class="expense-create-btn"
                                    >

                                        <i class="fa-solid fa-circle-check"></i>

                                        Create Expense

                                    </button>


                                    <a
                                        href="index.php"
                                        class="expense-cancel-btn"
                                    >

                                        <i class="fa-solid fa-xmark"></i>

                                        Cancel

                                    </a>

                                </div>


                            </div>

                        </form>

                    </div>

                </div>


            </div>

        </main>

        <!-- ===== Main Content End ===== -->


    </div>

    <!-- ===== Content Area End ===== -->


</div>

<!-- ===== Page Wrapper End ===== -->


<!-- footer session begins -->

<?php include('footer.php'); ?>

<!-- footer session ends -->


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


</body>

</html>

