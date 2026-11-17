
<?php


include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');



$companyid  = (int)($_SESSION['company_id'] ?? 0);
$user_id    = (int)($_SESSION['user_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);

if ($companyid <= 0 || $user_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Your session or active branch is invalid. Please sign in again.');
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function stock_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| VALIDATE LOGGED-IN USER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, email
    FROM users
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");
$stmt->execute([$user_id, $companyid]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    exit('Your user account could not be validated.');
}

$user_email = $user['email'] ?? null;

/*
|--------------------------------------------------------------------------
| VALIDATE ACTIVE BRANCH
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, name
    FROM locations
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");
$stmt->execute([$location_id, $companyid]);

$active_location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$active_location) {
    http_response_code(403);
    exit('Your active branch is invalid or does not belong to your company.');
}

/*
|--------------------------------------------------------------------------
| FETCH COMPANY PRODUCTS AVAILABLE IN ACTIVE BRANCH
|
| IMPORTANT:
| A product stocked in another branch remains available here.
| Only products already represented in this branch's stocks
| table are excluded from the initial stock-in selection.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        p.stock_type,
        p.category_id,

        CASE
            WHEN COALESCE(NULLIF(p.identifier_tracking, ''), 'none') = 'none'
                 AND COALESCE(p.imei_tracking, 0) = 1
            THEN 'imei'
            ELSE COALESCE(NULLIF(p.identifier_tracking, ''), 'none')
        END AS identifier_tracking,

        COALESCE(p.imei_tracking, 0) AS imei_tracking

    FROM products p

    INNER JOIN product_categories pc
        ON pc.id = p.category_id

    LEFT JOIN stocks s
        ON s.product_id = p.id
       AND s.company_id = p.company_id
       AND s.location_id = ?

    WHERE p.company_id = ?
      AND s.product_id IS NULL
      AND pc.dept != 'Production Only'

    ORDER BY p.name ASC
");

$stmt->execute([$location_id, $companyid]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| FETCH COMPANY VENDORS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, business_name
    FROM vendors
    WHERE company_id = ?
    ORDER BY business_name ASC
");

$stmt->execute([$companyid]);
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?= stock_h($company_name) ?> | Staff Area</title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>
        /* =========================================================
           STOCK TERMINAL — RESPONSIVE BRANCH INVENTORY
        ========================================================= */

        .stock-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }

        .stock-shell {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            border: 1px solid rgba(148,163,184,.25);
            background: linear-gradient(145deg,rgba(255,255,255,.98),rgba(248,250,252,.98));
            box-shadow: 0 20px 50px rgba(15,23,42,.08),0 2px 8px rgba(15,23,42,.04);
        }

        .stock-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at top right,rgba(59,130,246,.09),transparent 34%),
                radial-gradient(circle at bottom left,rgba(16,185,129,.06),transparent 30%);
        }

        .stock-header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 26px 30px;
            border-bottom: 1px solid rgba(148,163,184,.18);
            background: rgba(255,255,255,.55);
            backdrop-filter: blur(14px);
        }

        .stock-title-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .stock-title-icon {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: linear-gradient(135deg,#111827,#334155);
            color: #fff;
            box-shadow: 0 10px 25px rgba(15,23,42,.2);
        }

        .stock-title-icon i { font-size: 21px; }

        .stock-title {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
            font-weight: 800;
            color: #111827;
            letter-spacing: -.02em;
        }

        .stock-subtitle {
            margin: 5px 0 0;
            font-size: 13px;
            color: #64748b;
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(16,185,129,.08);
            color: #059669;
            border: 1px solid rgba(16,185,129,.16);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .stock-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 4px rgba(16,185,129,.10);
        }

        .stock-form-area {
            position: relative;
            padding: 30px;
        }

        .stock-form-grid {
            display: grid;
            grid-template-columns: repeat(2,minmax(0,1fr));
            gap: 24px;
        }

        .stock-field { min-width: 0; }

        .stock-label {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 9px;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
        }

        .stock-label i {
            width: 25px;
            height: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
        }

        .stock-required {
            color: #ef4444;
            font-size: 11px;
            margin-left: 1px;
        }

        .stock-input-wrap { position: relative; }

        .stock-input-wrap > i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }

        .stock-input,
        .stock-select {
            width: 100%;
            min-height: 50px;
            padding: 13px 15px 13px 44px;
            border: 1px solid #dbe2ea;
            border-radius: 13px;
            background: rgba(255,255,255,.92);
            color: #111827;
            font-size: 14px;
            outline: none;
            transition: border-color .2s,box-shadow .2s,background .2s;
            box-sizing: border-box;
        }

        .stock-input::placeholder { color: #9ca3af; }

        .stock-input:hover,
        .stock-select:hover { border-color: #b8c3d1; }

        .stock-input:focus,
        .stock-select:focus {
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99,102,241,.09),0 8px 20px rgba(15,23,42,.05);
        }

        .stock-select {
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            padding-right: 42px;
        }

        .stock-select-wrap::after {
            content: "\f078";
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #94a3b8;
            pointer-events: none;
            font-size: 11px;
        }

        .stock-input[readonly] {
            background: #f8fafc;
            cursor: not-allowed;
            font-weight: 700;
            color: #334155;
        }

        .stock-help {
            margin-top: 7px;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .stock-submit-area {
            grid-column: 1/-1;
            margin-top: 4px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .stock-submit {
            width: 100%;
            min-height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg,#111827,#1e293b);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .01em;
            cursor: pointer;
            box-shadow: 0 12px 28px rgba(15,23,42,.18);
            transition: transform .2s,box-shadow .2s,filter .2s;
        }

        .stock-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 16px 34px rgba(15,23,42,.23);
        }

        .stock-submit:active { transform: translateY(0); }

        .stock-submit i { font-size: 15px; }

        .stock-footer-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 12px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }

        /* IMEI / SERIAL / VIN PANEL */

        .imei-panel {
            display: none;
            grid-column: 1/-1;
            padding: 20px;
            border: 1px solid rgba(59,130,246,.22);
            border-radius: 17px;
            background: rgba(239,246,255,.72);
        }

        .imei-panel.is-visible { display: block; }

        .imei-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 15px;
        }

        .imei-panel-title {
            margin: 0;
            color: #1e3a8a;
            font-size: 14px;
            font-weight: 800;
        }

        .imei-panel-description {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }

        .imei-count {
            flex: 0 0 auto;
            padding: 7px 10px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
        }

        .imei-grid {
            display: grid;
            grid-template-columns: repeat(2,minmax(0,1fr));
            gap: 12px;
        }

        .imei-item { min-width: 0; }

        .imei-item label {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 11px;
            font-weight: 800;
        }

        .imei-item input {
            width: 100%;
            height: 46px;
            padding: 0 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            font-size: 14px;
            letter-spacing: .04em;
            box-sizing: border-box;
            outline: none;
        }

        .imei-item input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }

        .imei-item input.invalid {
            border-color: #ef4444;
            background: #fff7f7;
        }

        .imei-empty {
            padding: 16px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            color: #64748b;
            font-size: 12px;
            text-align: center;
            grid-column: 1/-1;
        }

        .imei-warning {
            margin-top: 12px;
            padding: 11px 13px;
            border-radius: 10px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 11px;
            line-height: 1.5;
        }

        .dark .stock-shell {
            background: linear-gradient(145deg,rgba(17,24,39,.98),rgba(15,23,42,.98));
            border-color: rgba(71,85,105,.45);
            box-shadow: 0 20px 50px rgba(0,0,0,.28);
        }

        .dark .stock-header {
            background: rgba(15,23,42,.55);
            border-color: rgba(71,85,105,.35);
        }

        .dark .stock-title { color: #f8fafc; }
        .dark .stock-subtitle { color: #94a3b8; }
        .dark .stock-label { color: #d1d5db; }

        .dark .stock-label i {
            background: #1e293b;
            color: #94a3b8;
        }

        .dark .stock-input,
        .dark .stock-select {
            background: rgba(15,23,42,.8);
            border-color: #334155;
            color: #f8fafc;
        }

        .dark .stock-input[readonly] {
            background: #1e293b;
            color: #cbd5e1;
        }

        .dark .stock-input:focus,
        .dark .stock-select:focus {
            background: #0f172a;
            border-color: #6366f1;
        }

        .dark .stock-submit-area { border-color: #334155; }

        .dark .imei-panel {
            background: rgba(30,41,59,.72);
            border-color: rgba(96,165,250,.3);
        }

        .dark .imei-panel-title { color: #bfdbfe; }

        .dark .imei-panel-description,
        .dark .imei-item label { color: #cbd5e1; }

        .dark .imei-count {
            background: #1e3a8a;
            color: #dbeafe;
        }

        .dark .imei-item input {
            background: #0f172a;
            border-color: #475569;
            color: #f8fafc;
        }

        .dark .imei-empty {
            border-color: #475569;
            color: #94a3b8;
        }

        .dark .imei-warning {
            background: rgba(124,45,18,.25);
            color: #fed7aa;
        }

        @media(max-width:900px) {
            .stock-form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stock-submit-area { grid-column: auto; }
            .stock-header { padding: 22px; }
            .stock-form-area { padding: 22px; }
        }

        @media(max-width:640px) {
            .stock-page { width: 100%; }
            .stock-shell { border-radius: 18px; }

            .stock-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 20px 18px;
                gap: 15px;
            }

            .stock-title-wrap { width: 100%; }

            .stock-title-icon {
                width: 46px;
                height: 46px;
                flex-basis: 46px;
                border-radius: 13px;
            }

            .stock-title-icon i { font-size: 18px; }
            .stock-title { font-size: 18px; }

            .stock-subtitle {
                font-size: 12px;
                line-height: 1.5;
            }

            .stock-status {
                align-self: flex-start;
                font-size: 11px;
            }

            .stock-form-area { padding: 20px 16px 22px; }
            .stock-form-grid { gap: 18px; }

            .stock-label {
                font-size: 12px;
                margin-bottom: 8px;
            }

            .stock-input,
            .stock-select {
                min-height: 52px;
                font-size: 14px;
                border-radius: 12px;
            }

            .stock-submit { min-height: 54px; }
            .imei-panel { padding: 15px; }
            .imei-grid { grid-template-columns: 1fr; gap: 10px; }

            .imei-panel-head {
                flex-direction: column;
            }

            .imei-count { align-self: flex-start; }
        }

        @media(max-width:380px) {
            .stock-header { padding: 17px 14px; }
            .stock-form-area { padding: 17px 13px 20px; }
            .stock-title { font-size: 16px; }

            .stock-title-icon {
                width: 42px;
                height: 42px;
                flex-basis: 42px;
            }

            .stock-input,
            .stock-select {
                min-height: 50px;
                padding-left: 41px;
            }
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
        darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false');
        $watch(
            'darkMode',
            value => localStorage.setItem('darkMode', JSON.stringify(value))
        )
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>

            <div class="mx-auto max-w-(--breakpoint-2xl) p-3 sm:p-4 md:p-6">

                <div class="stock-page">

                    <div class="stock-shell">

                        <!-- HEADER -->

                        <div class="stock-header">

                            <div class="stock-title-wrap">

                                <div class="stock-title-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>

                                <div>
                                    <h1 class="stock-title">Add New Stock</h1>

                                    <p class="stock-subtitle">
                                        Register inventory into your active branch.
                                    </p>
                                </div>

                            </div>

                            <div class="stock-status">
                                <span class="stock-status-dot"></span>
                                Branch Inventory Terminal
                            </div>

                        </div>

                        <!-- FORM -->

                        <div class="stock-form-area">

                            <form
                                method="POST"
                                action="create-stock.php"
                                enctype="multipart/form-data"
                                id="stockInForm"
                            >

                                <!-- SESSION-BASED IDENTIFIERS -->

                                <input
                                    type="hidden"
                                    name="company_id"
                                    value="<?= stock_h($companyid) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="recorded_by"
                                    value="<?= stock_h($user_id) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="stocked_by"
                                    value="<?= stock_h($user_id) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="location_id"
                                    value="<?= stock_h($location_id) ?>"
                                >

                                <div class="stock-form-grid">

                                    <!-- PRODUCT -->

                                    <div class="stock-field">

                                        <label class="stock-label">
                                            <i class="fa-solid fa-cube"></i>
                                            <span>Select Product</span>
                                            <span class="stock-required">Required</span>
                                        </label>

                                        <div class="stock-input-wrap stock-select-wrap">

                                            <i class="fa-solid fa-box"></i>

                                            <select
                                                name="product_id"
                                                id="stockProduct"
                                                required
                                                class="stock-select"
                                            >

                                                <option value="">-- Select Product --</option>

                                                <?php foreach ($products as $product): ?>

                                                    <option
                                                        value="<?= stock_h($product['id']) ?>"
                                                        data-identifier-tracking="<?= stock_h($product['identifier_tracking']) ?>"
                                                        data-stock-type="<?= stock_h($product['stock_type']) ?>"
                                                    >
                                                        <?= stock_h($product['stock_type']) ?> :
                                                        <?= stock_h($product['name']) ?>

                                                        <?php
                                                        if ($product['identifier_tracking'] === 'imei') {
                                                            echo ' (IMEI tracked)';
                                                        } elseif ($product['identifier_tracking'] === 'serial') {
                                                            echo ' (Serial tracked)';
                                                        } elseif ($product['identifier_tracking'] === 'vin') {
                                                            echo ' (VIN tracked)';
                                                        }
                                                        ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <div class="stock-help">
                                            Choose a company product not yet introduced into this branch's inventory.
                                        </div>

                                    </div>

                                    <!-- ACTIVE BRANCH -->

                                    <div class="stock-field">

                                        <label class="stock-label">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <span>Active Stock Branch</span>
                                        </label>

                                        <div class="stock-input-wrap">

                                            <i class="fa-solid fa-location-crosshairs"></i>

                                            <input
                                                type="text"
                                                value="<?= stock_h($active_location['name']) ?>"
                                                readonly
                                                class="stock-input"
                                            >

                                        </div>

                                        <div class="stock-help">
                                            Your inventory entry will automatically be assigned to this branch.
                                            Branch selection is controlled by your active session.
                                        </div>

                                    </div>

                                    <!-- QUANTITY -->

                                    <div class="stock-field">

                                        <label class="stock-label">
                                            <i class="fa-solid fa-layer-group"></i>
                                            <span>Quantity</span>
                                            <span class="stock-required">Required</span>
                                        </label>

                                        <div class="stock-input-wrap">

                                            <i class="fa-solid fa-hashtag"></i>

                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                name="quantity"
                                                id="stockQuantity"
                                                required
                                                placeholder="Enter stock quantity"
                                                class="stock-input"
                                            >

                                        </div>

                                        <div class="stock-help" id="quantityHelp">
                                            Enter the quantity being added to this branch.
                                        </div>

                                    </div>

                                    <!-- INVOICE -->

                                    <div class="stock-field">

                                        <label class="stock-label" for="stock_invoice">

                                            <i class="fa-solid fa-file-invoice"></i>

                                            <span>Stock Invoice</span>

                                            <span style="color:#94a3b8;font-size:11px;">
                                                Optional
                                            </span>

                                        </label>

                                        <div class="stock-input-wrap">

                                            <i class="fa-solid fa-paperclip"></i>

                                            <input
                                                type="file"
                                                name="stock_invoice"
                                                id="stock_invoice"
                                                class="stock-input"
                                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                                style="padding-left:44px;"
                                            >

                                        </div>

                                        <div class="stock-help">
                                            Attach the supplier's invoice.
                                            PDF, JPG or PNG; maximum 20 MB.
                                        </div>

                                    </div>

                                    <!-- SUPPLIER -->

                                    <div class="stock-field">

                                        <label class="stock-label">

                                            <i class="fa-solid fa-truck-field"></i>

                                            <span>Vendor / Supplier</span>

                                            <span class="stock-required">Required</span>

                                        </label>

                                        <div class="stock-input-wrap stock-select-wrap">

                                            <i class="fa-solid fa-truck"></i>

                                            <select
                                                name="supplier"
                                                required
                                                class="stock-select"
                                            >

                                                <option value="">
                                                    -- Select Product Supplier --
                                                </option>

                                                <?php foreach ($vendors as $vendor): ?>

                                                    <option value="<?= stock_h($vendor['id']) ?>">
                                                        <?= stock_h($vendor['business_name']) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <div class="stock-help">
                                            Select the company supplier responsible for this stock entry.
                                        </div>

                                    </div>

                                    <!-- IDENTIFIER REGISTRATION -->

                                    <section
                                        class="imei-panel"
                                        id="imeiPanel"
                                        aria-live="polite"
                                        aria-hidden="true"
                                    >

                                        <div class="imei-panel-head">

                                            <div>

                                                <h2
                                                    class="imei-panel-title"
                                                    id="identifierPanelTitle"
                                                >
                                                    <i class="fa-solid fa-barcode"></i>
                                                    Device Identifier Registration
                                                </h2>

                                                <p
                                                    class="imei-panel-description"
                                                    id="identifierPanelDescription"
                                                >
                                                    Enter one unique identifier for every physical unit being received.
                                                    The number of fields will automatically match the quantity above.
                                                </p>

                                            </div>

                                            <span class="imei-count" id="imeiCount">
                                                0 identifiers required
                                            </span>

                                        </div>

                                        <div class="imei-grid" id="imeiFields">

                                            <div class="imei-empty">
                                                Select an IMEI-, serial- or VIN-tracked product and enter its quantity.
                                            </div>

                                        </div>

                                        <div class="imei-warning" id="identifierWarning">

                                            <strong>Important:</strong>
                                            Every unit must have its own unique identifier.
                                            Duplicate identifiers are not allowed.

                                        </div>

                                    </section>

                                    <!-- SUBMIT -->

                                    <div class="stock-submit-area">

                                        <button type="submit" class="stock-submit">

                                            <i class="fa-solid fa-plus"></i>

                                            <span>Add Stock to Inventory</span>

                                            <i class="fa-solid fa-arrow-right"></i>

                                        </button>

                                        <div class="stock-footer-note">

                                            <i class="fa-solid fa-shield-halved"></i>

                                            Inventory record will be registered under
                                            <?= stock_h($company_name) ?>
                                            — <?= stock_h($active_location['name']) ?>.

                                        </div>

                                    </div>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>

<?php include('footer.php'); ?>

<script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
<script defer src="bundle.js"></script>

<script
    defer
    src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015"
    integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ=="
    data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
    crossorigin="anonymous"
></script>

<script>
(function () {
    'use strict';

    const form = document.getElementById('stockInForm');
    const productSelect = document.getElementById('stockProduct');
    const quantityInput = document.getElementById('stockQuantity');
    const quantityHelp = document.getElementById('quantityHelp');

    const imeiPanel = document.getElementById('imeiPanel');
    const imeiFields = document.getElementById('imeiFields');
    const imeiCount = document.getElementById('imeiCount');

    const panelTitle = document.getElementById('identifierPanelTitle');
    const panelDescription = document.getElementById('identifierPanelDescription');
    const identifierWarning = document.getElementById('identifierWarning');

    if (!form || !productSelect || !quantityInput || !imeiPanel || !imeiFields) {
        return;
    }

    let trackingMode = 'none';

    function selectedTrackingMode() {

        const option = productSelect.options[productSelect.selectedIndex];

        const mode = option && option.value !== ''
            ? option.dataset.identifierTracking
            : 'none';

        return ['imei', 'serial', 'vin'].includes(mode)
            ? mode
            : 'none';
    }

    function normalizeQuantity() {

        const raw = quantityInput.value.trim();

        if (raw === '') return 0;

        const value = Number(raw);

        if (!Number.isFinite(value) || value <= 0) return 0;

        return trackingMode === 'none'
            ? value
            : Math.floor(value);
    }

    function makeIdentifierField(index) {

        const isImei = trackingMode === 'imei';
        const isVin = trackingMode === 'vin';

        const labelText = isImei
            ? 'IMEI'
            : (isVin ? 'VIN' : 'Serial Number');

        const wrapper = document.createElement('div');
        wrapper.className = 'imei-item';

        const label = document.createElement('label');
        label.htmlFor = 'identifier_' + index;
        label.textContent = 'Unit ' + index + ' — ' + labelText;

        const input = document.createElement('input');

        input.type = 'text';
        input.name = 'identifiers[]';
        input.id = 'identifier_' + index;
        input.autocomplete = 'off';
        input.required = true;

        input.maxLength = isImei ? 15 : (isVin ? 17 : 100);

        input.placeholder = isImei
            ? 'Enter 15-digit IMEI'
            : (isVin
                ? 'Enter 17-character VIN'
                : 'Enter unique serial number');

        if (isImei) {

            input.inputMode = 'numeric';
            input.minLength = 15;
            input.pattern = '[0-9]{15}';

        } else if (isVin) {

            input.inputMode = 'text';
            input.minLength = 17;
            input.pattern = '[A-HJ-NPR-Z0-9]{17}';
            input.autocapitalize = 'characters';

        } else {

            input.inputMode = 'text';

        }

        input.addEventListener('input', function () {

            if (isImei) {

                this.value = this.value.replace(/\D/g, '').slice(0, 15);

            } else if (isVin) {

                this.value = this.value
                    .toUpperCase()
                    .replace(/[^A-HJ-NPR-Z0-9]/g, '')
                    .slice(0, 17);

            } else {

                this.value = this.value.slice(0, 100);

            }

            this.classList.remove('invalid');
        });

        wrapper.appendChild(label);
        wrapper.appendChild(input);

        return wrapper;
    }

    function renderIdentifierFields() {

        const quantity = normalizeQuantity();

        imeiFields.innerHTML = '';

        if (trackingMode === 'none') {

            imeiPanel.classList.remove('is-visible');
            imeiPanel.setAttribute('aria-hidden', 'true');

            imeiCount.textContent = '0 identifiers required';

            quantityInput.step = '0.01';
            quantityInput.min = '0.01';
            quantityInput.removeAttribute('max');

            quantityHelp.textContent =
                'Enter the quantity being added to this branch.';

            return;
        }

        const isImei = trackingMode === 'imei';
        const isVin = trackingMode === 'vin';

        imeiPanel.classList.add('is-visible');
        imeiPanel.setAttribute('aria-hidden', 'false');

        quantityInput.step = '1';
        quantityInput.min = '1';
        quantityInput.max = '500';

        quantityHelp.textContent = isImei
            ? 'IMEI-tracked stock must be a whole number. One valid 15-digit IMEI is required for each unit.'
            : (isVin
                ? 'VIN-tracked stock must be a whole number. One valid 17-character VIN is required for each unit.'
                : 'Serial-tracked stock must be a whole number. One unique serial number is required for each unit.');

        panelTitle.innerHTML =
            '<i class="fa-solid fa-barcode"></i> ' +
            (isImei
                ? 'Device IMEI Registration'
                : (isVin
                    ? 'Vehicle VIN Registration'
                    : 'Serial Number Registration'));

        panelDescription.textContent = isImei
            ? 'Enter the unique 15-digit IMEI for every physical unit being received. The number of fields will automatically match the quantity above.'
            : (isVin
                ? 'Enter the unique 17-character VIN for every vehicle being received. VINs cannot contain I, O or Q.'
                : 'Enter the unique serial number for every physical unit being received.');

        imeiCount.textContent =
            quantity + (quantity === 1
                ? ' identifier required'
                : ' identifiers required');

        identifierWarning.innerHTML = isImei
            ? '<strong>Important:</strong> Each unit must have its own valid 15-digit IMEI. Duplicate IMEIs are not allowed. Check the device label or packaging before submitting.'
            : (isVin
                ? '<strong>Important:</strong> Each vehicle must have its own 17-character VIN using letters A–H, J–N, P, R–Z and digits 0–9. Duplicate VINs are not allowed.'
                : '<strong>Important:</strong> Each unit must have its own unique serial number (1–100 characters). Duplicate serial numbers are not allowed.');

        if (quantity <= 0) {

            const empty = document.createElement('div');

            empty.className = 'imei-empty';
            empty.textContent =
                'Enter a whole-number quantity above to generate the corresponding identifier fields.';

            imeiFields.appendChild(empty);

            return;
        }

        if (quantity > 500) {

            const warning = document.createElement('div');

            warning.className = 'imei-empty';
            warning.textContent =
                'For safety and usability, this form supports up to 500 tracked units in one stock entry.';

            imeiFields.appendChild(warning);

            return;
        }

        for (let i = 1; i <= quantity; i++) {
            imeiFields.appendChild(makeIdentifierField(i));
        }
    }

    productSelect.addEventListener('change', function () {

        trackingMode = selectedTrackingMode();

        if (trackingMode !== 'none') {

            const current = Number(quantityInput.value);

            if (
                quantityInput.value !== '' &&
                (!Number.isInteger(current) || current < 1)
            ) {
                quantityInput.value =
                    current > 0
                        ? String(Math.max(1, Math.floor(current)))
                        : '';
            }
        }

        renderIdentifierFields();
    });

    quantityInput.addEventListener('input', function () {

        if (
            trackingMode !== 'none' &&
            this.value !== '' &&
            Number(this.value) > 0
        ) {
            this.value = String(Math.floor(Number(this.value)));
        }

        renderIdentifierFields();
    });

    form.addEventListener('submit', function (event) {

        if (trackingMode === 'none') return;

        const quantity = Number(quantityInput.value);

        if (!Number.isInteger(quantity) || quantity < 1) {

            event.preventDefault();

            alert('For a tracked product, quantity must be a whole number greater than zero.');

            quantityInput.focus();

            return;
        }

        if (quantity > 500) {

            event.preventDefault();

            alert('A maximum of 500 tracked units can be entered in one stock-in operation.');

            quantityInput.focus();

            return;
        }

        const inputs = Array.from(
            imeiFields.querySelectorAll('input[name="identifiers[]"]')
        );

        if (inputs.length !== quantity) {

            event.preventDefault();

            alert('The number of identifier fields does not match the stock quantity. Please review the quantity.');

            return;
        }

        const seen = new Set();

        let firstInvalid = null;

        for (const input of inputs) {

            const value = input.value.trim();

            const normalizedValue = trackingMode === 'vin'
                ? value.toUpperCase()
                : value;

            const valid = trackingMode === 'imei'
                ? /^[0-9]{15}$/.test(value)
                : (trackingMode === 'vin'
                    ? /^[A-HJ-NPR-Z0-9]{17}$/.test(normalizedValue)
                    : value.length >= 1 &&
                      value.length <= 100 &&
                      !/[\x00-\x1F\x7F]/.test(value));

            const duplicateKey = normalizedValue;

            if (!valid || seen.has(duplicateKey)) {

                input.classList.add('invalid');

                if (!firstInvalid) {
                    firstInvalid = input;
                }

            } else {

                seen.add(duplicateKey);
                input.classList.remove('invalid');

            }
        }

        if (firstInvalid) {

            event.preventDefault();

            alert(
                trackingMode === 'imei'
                    ? 'Enter a unique 15-digit IMEI for every unit. Duplicate or incomplete IMEIs must be corrected.'
                    : (trackingMode === 'vin'
                        ? 'Enter a unique 17-character VIN for every vehicle. VINs cannot contain I, O or Q. Duplicate or incomplete VINs must be corrected.'
                        : 'Enter a unique serial number (1–100 characters) for every unit. Duplicate or incomplete serial numbers must be corrected.')
            );

            firstInvalid.focus();
        }
    });

    trackingMode = selectedTrackingMode();

    renderIdentifierFields();

})();
</script>

</body>
</html>
