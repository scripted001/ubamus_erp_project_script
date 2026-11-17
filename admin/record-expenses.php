<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BRANCH MANAGER — RECORD EXPENSE
|--------------------------------------------------------------------------
| SECURITY:
| 1. Only active Branch Managers can access this page.
| 2. Company and branch are obtained from the authenticated users record.
| 3. No location selector is provided.
| 4. Staff are restricted to the manager's assigned company and branch.
| 5. No company, branch, creator or approval values are trusted from forms.
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Africa/Lagos');


require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/validation.php';
require_once __DIR__ . '/user_data.php';
require_once __DIR__ . '/../company_data.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}

/*
|--------------------------------------------------------------------------
| AUTHENTICATE LOGGED-IN BRANCH MANAGER
|--------------------------------------------------------------------------
*/

$sessionUserId = (int)($_SESSION['user_id'] ?? ($user_id ?? 0));
$sessionEmail  = trim((string)($_SESSION['user_email'] ?? ''));

if ($sessionUserId <= 0 && $sessionEmail === '') {
    http_response_code(403);
    exit('Access denied. Please sign in again.');
}

if ($sessionUserId > 0) {
    $authStmt = $pdo->prepare("
        SELECT
            id,
            email,
            role,
            company_id,
            location_id,
            status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $authStmt->execute([$sessionUserId]);
} else {
    $authStmt = $pdo->prepare("
        SELECT
            id,
            email,
            role,
            company_id,
            location_id,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $authStmt->execute([$sessionEmail]);
}

$branchManager = $authStmt->fetch(PDO::FETCH_ASSOC);

$managerRole = trim((string)($branchManager['role'] ?? ''));
$managerStatus = strtolower(trim((string)($branchManager['status'] ?? '')));

if (
    !$branchManager ||
    strcasecmp($managerRole, 'Branch Manager') !== 0 ||
    !in_array($managerStatus, ['active', '1', 'enabled'], true)
) {
    http_response_code(403);
    exit('Access denied. This page is restricted to active Branch Managers.');
}

/*
|--------------------------------------------------------------------------
| TRUSTED ACCOUNT DETAILS
|--------------------------------------------------------------------------
*/

$authenticatedUserId = (int)$branchManager['id'];
$company_id = (int)$branchManager['company_id'];
$location_id = (int)$branchManager['location_id'];
$user_email = trim((string)$branchManager['email']);

$approved_by = 'Not Approved!';

if ($company_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Access denied. Your account has no valid assigned branch.');
}

/*
|--------------------------------------------------------------------------
| VERIFY THAT THE ASSIGNED BRANCH BELONGS TO THE COMPANY
|--------------------------------------------------------------------------
*/

$locationStmt = $pdo->prepare("
    SELECT
        id,
        name,
        city,
        state,
        type
    FROM locations
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");

$locationStmt->execute([
    $location_id,
    $company_id
]);

$assignedBranch = $locationStmt->fetch(PDO::FETCH_ASSOC);

if (!$assignedBranch) {
    http_response_code(403);
    exit('Access denied. Your assigned branch could not be verified.');
}

/*
|--------------------------------------------------------------------------
| FETCH STAFF FROM THIS BRANCH ONLY
|--------------------------------------------------------------------------
*/

$branchStaffStmt = $pdo->prepare("
    SELECT
        id,
        firstname,
        lastname,
        role
    FROM staffs
    WHERE company_id = ?
      AND location_id = ?
    ORDER BY firstname ASC, lastname ASC
");

$branchStaffStmt->execute([
    $company_id,
    $location_id
]);

$branchStaff = $branchStaffStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| BRANCH DISPLAY NAME
|--------------------------------------------------------------------------
*/

$branchName = trim((string)($assignedBranch['name'] ?? ''));
$branchType = trim((string)($assignedBranch['type'] ?? ''));
$branchCity = trim((string)($assignedBranch['city'] ?? ''));
$branchState = trim((string)($assignedBranch['state'] ?? ''));

$branchDisplayName = $branchName;

if ($branchType !== '') {
    $branchDisplayName .= ' (' . $branchType . ')';
}

if ($branchCity !== '') {
    $branchDisplayName .= ' — ' . $branchCity;
}

if ($branchState !== '') {
    $branchDisplayName .= ', ' . $branchState;
}

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="content-type" content="text/html;charset=utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>
        <?= $escape($company_name ?? 'Company') ?> | Create Expense
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>

        :root {
            --expense-primary: #2563eb;
            --expense-primary-dark: #1d4ed8;
            --expense-text: #172033;
            --expense-muted: #64748b;
            --expense-border: rgba(148, 163, 184, 0.25);
            --expense-card: rgba(255, 255, 255, 0.82);
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
                #f8fafc;
        }

        .expense-page-background {
            position: relative;
        }

        .expense-content {
            position: relative;
            z-index: 1;
        }

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

        .expense-field {
            position: relative;
            min-width: 0;
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
                background 0.2s ease;
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
        }

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

        .expense-select {
            cursor: pointer;
        }

        .expense-select option {
            color: #172033;
            background: #ffffff;
        }

        .expense-section {
            position: relative;
            padding: 18px;
            border-radius: 17px;
            background: linear-gradient(
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

        .expense-branch-display {
            min-height: 48px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            border: 1px solid rgba(37, 99, 235, 0.20);
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.055);
            color: #1e40af;
            font-size: 13px;
            font-weight: 650;
            overflow-wrap: anywhere;
        }

        .expense-field-hint {
            display: block;
            margin-top: 7px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
        }

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
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff !important;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.18);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .expense-create-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
        }

        .expense-create-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        .expense-empty-staff {
            margin-top: 9px;
            padding: 12px;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
        }

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
        loaded: true,
        darkMode: false,
        stickyMenu: false,
        sidebarToggle: false,
        scrollTop: false
    }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false');
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>

            <div class="expense-content mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                <!-- PAGE HEADING -->

                <div class="expense-heading">

                    <div class="expense-heading-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <div>
                        <h1 class="expense-title">Create Expense</h1>
                        <p class="expense-subtitle">
                            Record and submit a new business expense
                        </p>
                    </div>

                </div>

                <!-- BREADCRUMB -->

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

                <!-- MAIN CARD -->

                <div class="expense-glass-card">

                    <div class="expense-card-content">

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

                        <!-- EXPENSE FORM -->

                        <form
                            method="POST"
                            action="proceed-expenses.php"
                            enctype="multipart/form-data"
                            autocomplete="off"
                        >

                            <div class="space-y-5">

                                <!-- BASIC INFORMATION -->

                                <div class="expense-section">

                                    <div class="expense-section-title">
                                        <i class="fa-solid fa-circle-info"></i>
                                        Basic Information
                                    </div>

                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

                                        <!-- EXPENSE TITLE -->

                                        <div class="expense-field">

                                            <label class="expense-label" for="expense_title">
                                                <i class="fa-solid fa-heading"></i>
                                                Expense Title
                                            </label>

                                            <input
                                                type="text"
                                                id="expense_title"
                                                name="expense_title"
                                                class="expense-input"
                                                placeholder="Enter expense title"
                                                maxlength="200"
                                                required
                                            >

                                        </div>

                                        <!-- AMOUNT -->

                                        <div class="expense-field">

                                            <label class="expense-label" for="amount">
                                                <i class="fa-solid fa-naira-sign"></i>
                                                Amount
                                            </label>

                                            <input
                                                type="number"
                                                id="amount"
                                                name="amount"
                                                class="expense-input"
                                                placeholder="Enter amount"
                                                min="0.01"
                                                step="0.01"
                                                inputmode="decimal"
                                                required
                                            >

                                        </div>

                                        <!-- DESCRIPTION -->

                                        <div class="expense-field xl:col-span-2">

                                            <label class="expense-label" for="description">
                                                <i class="fa-solid fa-align-left"></i>
                                                Description
                                            </label>

                                            <textarea
                                                id="description"
                                                name="description"
                                                class="expense-textarea"
                                                placeholder="Describe what this expense was for..."
                                                required
                                            ></textarea>

                                        </div>

                                    </div>

                                </div>

                                <!-- EXPENSE DETAILS -->

                                <div class="expense-section">

                                    <div class="expense-section-title">
                                        <i class="fa-solid fa-sliders"></i>
                                        Expense Details
                                    </div>

                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

                                        <!-- RECEIPT -->

                                        <div class="expense-field">

                                            <label class="expense-label" for="receipt">
                                                <i class="fa-solid fa-file-invoice"></i>
                                                Receipt

                                                <span style="
                                                    color:#94a3b8;
                                                    font-size:10px;
                                                    font-weight:500;
                                                    text-transform:none;
                                                    letter-spacing:0;
                                                ">
                                                    Optional
                                                </span>
                                            </label>

                                            <input
                                                type="file"
                                                id="receipt"
                                                name="receipt"
                                                class="expense-input expense-file"
                                                accept=".jpg,.jpeg,.png,.pdf"
                                            >

                                            <small class="expense-field-hint">
                                                Accepted formats: JPG, PNG and PDF.
                                                Maximum file size: 5MB.
                                            </small>

                                        </div>

                                        <!-- ASSIGNED BRANCH: READ ONLY -->

                                        <div class="expense-field">

                                            <label class="expense-label">
                                                <i class="fa-solid fa-location-dot"></i>
                                                Assigned Branch
                                            </label>

                                            <div
                                                class="expense-branch-display"
                                                role="status"
                                                aria-readonly="true"
                                            >
                                                <i class="fa-solid fa-building"></i>

                                                <?= $escape($branchDisplayName) ?>
                                            </div>

                                            <small class="expense-field-hint">
                                                This expense will automatically be recorded
                                                under your assigned branch. You cannot
                                                change the branch from this page.
                                            </small>

                                        </div>

                                        <!-- STAFF RESPONSIBLE -->

                                        <div class="expense-field xl:col-span-2">

                                            <label
                                                class="expense-label"
                                                for="staff_responsible"
                                            >
                                                <i class="fa-solid fa-user-tie"></i>
                                                Expenses By
                                            </label>

                                            <select
                                                name="staff_responsible"
                                                id="staff_responsible"
                                                required
                                                class="expense-select"
                                                <?= empty($branchStaff) ? 'disabled' : '' ?>
                                            >

                                                <option value="">
                                                    -- Select Staff In Your Branch --
                                                </option>

                                                <?php foreach ($branchStaff as $staff): ?>

                                                    <?php
                                                    $staffId = (int)$staff['id'];

                                                    $staffName = trim(
                                                        (string)$staff['firstname'] . ' ' .
                                                        (string)$staff['lastname']
                                                    );

                                                    $staffRole = trim(
                                                        (string)($staff['role'] ?? '')
                                                    );
                                                    ?>

                                                    <option value="<?= $staffId ?>">
                                                        <?= $escape($staffName) ?>

                                                        <?php if ($staffRole !== ''): ?>
                                                            — <?= $escape($staffRole) ?>
                                                        <?php endif; ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                            <?php if (empty($branchStaff)): ?>

                                                <div class="expense-empty-staff">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>

                                                    No staff members are currently assigned
                                                    to your branch. You cannot submit an
                                                    expense until staff are assigned.
                                                </div>

                                            <?php else: ?>

                                                <small class="expense-field-hint">
                                                    Only staff members assigned to your
                                                    branch are available for selection.
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                                <!-- ACTIONS -->

                                <div class="expense-actions">

                                    <button
                                        type="submit"
                                        name="submit"
                                        class="expense-create-btn"
                                        <?= empty($branchStaff) ? 'disabled' : '' ?>
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

    </div>

</div>

<?php include('footer.php'); ?>

<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script defer src="bundle.js"></script>

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