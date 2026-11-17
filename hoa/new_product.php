<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$companyid = $_SESSION['company_id'];
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
        <?php echo $company_name; ?> | Add Product
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>

        /* =========================================================
           PRODUCT PAGE
        ========================================================= */

        .product-page {
            position: relative;
            overflow: hidden;
        }

        /* =========================================================
           ABSTRACT BACKGROUND
        ========================================================= */

        .product-bg-shape {
            position: absolute;
            pointer-events: none;
            z-index: 0;
            opacity: .55;
        }

        .product-bg-circle {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background:
                radial-gradient(
                    circle at 30% 30%,
                    rgba(52,24,147,.18),
                    rgba(52,24,147,.03) 65%,
                    transparent 70%
                );
            top: -110px;
            right: -80px;
        }

        .product-bg-circle-2 {
            width: 230px;
            height: 230px;
            border-radius: 50%;
            background:
                radial-gradient(
                    circle,
                    rgba(14,165,233,.12),
                    transparent 70%
                );
            bottom: 100px;
            left: -100px;
        }

        .product-bg-grid {
            position: absolute;
            top: 0;
            right: 0;
            width: 260px;
            height: 260px;
            opacity: .25;
            background-image:
                linear-gradient(rgba(52,24,147,.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(52,24,147,.15) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: linear-gradient(to bottom left, black, transparent);
            -webkit-mask-image: linear-gradient(to bottom left, black, transparent);
            pointer-events: none;
        }

        /* =========================================================
           PAGE INTRO
        ========================================================= */

        .product-intro {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
            padding: 24px;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(148,163,184,.18);
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.96),
                    rgba(248,250,252,.92)
                );
            box-shadow: 0 15px 45px rgba(15,23,42,.07);
        }

        .product-intro-content {
            position: relative;
            z-index: 2;
        }

        .product-kicker {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #341893;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .product-intro h1 {
            margin: 0;
            color: #111827;
            font-size: clamp(24px, 4vw, 34px);
            font-weight: 800;
            line-height: 1.15;
        }

        .product-intro p {
            margin: 8px 0 0;
            max-width: 650px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .product-intro-icon {
            position: relative;
            z-index: 2;
            width: 76px;
            height: 76px;
            min-width: 76px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: linear-gradient(135deg, #341893, #5b32d6);
            color: #fff;
            font-size: 31px;
            box-shadow: 0 16px 35px rgba(52,24,147,.28);
            transform: rotate(3deg);
        }

        .product-intro-icon::before,
        .product-intro-icon::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 50%;
        }

        .product-intro-icon::before {
            width: 105px;
            height: 105px;
        }

        .product-intro-icon::after {
            width: 135px;
            height: 135px;
        }

        /* =========================================================
           FORM CARD
        ========================================================= */

        .product-form-card {
            position: relative;
            z-index: 2;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 18px 50px rgba(15,23,42,.08);
        }

        .product-form-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 24px;
            border-bottom: 1px solid #eef2f7;
        }

        .product-form-header-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: #f0ebff;
            color: #341893;
            font-size: 18px;
        }

        .product-form-header h2 {
            margin: 0;
            color: #111827;
            font-size: 17px;
            font-weight: 800;
        }

        .product-form-header p {
            margin: 2px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .product-form-body {
            padding: 24px;
        }

        /* =========================================================
           FORM SECTIONS
        ========================================================= */

        .product-section {
            position: relative;
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #edf0f5;
            border-radius: 18px;
            background: #fff;
        }

        .product-section:last-child {
            margin-bottom: 0;
        }

        .product-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 14px;
            font-weight: 800;
        }

        .product-section-title i {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f5f3ff;
            color: #341893;
        }

        .product-section-title span {
            display: block;
        }

        .product-section-title small {
            display: block;
            margin-top: 2px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 500;
        }

        /* =========================================================
           INPUTS
        ========================================================= */

        .product-field {
            position: relative;
        }

        .product-field label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
        }

        .product-field label i {
            color: #341893;
            font-size: 12px;
        }

        .product-field input,
        .product-field select,
        .product-field textarea {
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .product-field input:focus,
        .product-field select:focus,
        .product-field textarea:focus {
            border-color: #8b72dc !important;
            box-shadow: 0 0 0 4px rgba(52,24,147,.08) !important;
        }

        .product-field textarea {
            min-height: 110px;
            resize: vertical;
        }

        /* =========================================================
           AUTO INFO
        ========================================================= */

        .auto-info {
            position: relative;
            overflow: hidden;
        }

        .auto-info::after {
            content: "";
            position: absolute;
            width: 130px;
            height: 130px;
            right: -55px;
            bottom: -70px;
            border-radius: 50%;
            border: 18px solid rgba(52,24,147,.035);
            pointer-events: none;
        }

        .auto-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #f5f3ff;
            color: #341893;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        /* =========================================================
           IMAGE UPLOAD
        ========================================================= */

        .image-upload-box {
            position: relative;
            padding: 20px;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            background: #f8fafc;
        }

        .image-upload-box:hover {
            border-color: #8b72dc;
            background: #faf9ff;
        }

        .image-upload-icon {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            border-radius: 13px;
            background: #eee9ff;
            color: #341893;
            font-size: 18px;
        }

        .product-media-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .product-media-preview:empty {
            display: none;
        }

        .product-media-preview-item {
            min-width: 0;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }

        .product-media-preview-item img,
        .product-media-preview-item video {
            display: block;
            width: 100%;
            height: 105px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }

        .product-media-preview-item strong {
            display: block;
            margin-top: 7px;
            overflow-wrap: anywhere;
            color: #334155;
            font-size: 11px;
        }

        .product-media-preview-item small {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 10px;
        }

        .dark .product-media-preview-item {
            background: #1f2937;
            border-color: #4b5563;
        }

        .dark .product-media-preview-item strong {
            color: #e5e7eb;
        }

        /* =========================================================
           IDENTIFIER TRACKING CONFIGURATION
        ========================================================= */

        .imei-config {
            display: none;
            border: 1px solid #ddd6fe;
            background: linear-gradient(135deg, #faf9ff, #ffffff);
        }

        .imei-config.is-visible {
            display: block;
        }

        .imei-config-description {
            margin: -8px 0 18px 44px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .identifier-choice {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .identifier-choice label {
            margin-bottom: 0;
        }

        .identifier-choice select {
            width: 100%;
            min-height: 46px;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #1f2937;
            font-size: 13px;
            outline: none;
        }

        .identifier-choice select:focus {
            border-color: #8b72dc;
            box-shadow: 0 0 0 4px rgba(52,24,147,.08);
        }

        .identifier-help {
            margin-top: 10px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
        }

        .dark .imei-config {
            background: #1f2937;
            border-color: #4b3b83;
        }

        .dark .identifier-choice select {
            background: #111827;
            border-color: #4b5563;
            color: #f9fafb;
        }

        .dark .identifier-help {
            color: #9ca3af;
        }

        /* =========================================================
           ACTION AREA
        ========================================================= */

        .product-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eef2f7;
        }

        .product-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-width: 165px;
            padding: 12px 20px;
            border: 0;
            border-radius: 11px;
            background: linear-gradient(135deg, #341893, #5128c5);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 9px 20px rgba(52,24,147,.20);
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .product-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 13px 25px rgba(52,24,147,.28);
        }

        .product-cancel {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 110px;
            padding: 12px 18px;
            border: 1px solid #dbe1e8;
            border-radius: 11px;
            background: #fff;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: background .2s ease, border-color .2s ease;
        }

        .product-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        .dark .product-intro {
            background:
                linear-gradient(
                    135deg,
                    rgba(17,24,39,.96),
                    rgba(31,41,55,.92)
                );
            border-color: #374151;
        }

        .dark .product-intro h1 {
            color: #fff;
        }

        .dark .product-intro p {
            color: #9ca3af;
        }

        .dark .product-form-card {
            background: rgba(17,24,39,.96);
            border-color: #374151;
        }

        .dark .product-form-header {
            border-color: #374151;
        }

        .dark .product-form-header h2,
        .dark .product-section-title {
            color: #fff;
        }

        .dark .product-section {
            background: rgba(17,24,39,.65);
            border-color: #374151;
        }

        .dark .product-field label {
            color: #d1d5db;
        }

        .dark .image-upload-box {
            background: #111827;
            border-color: #4b5563;
        }

        .dark .product-actions {
            border-color: #374151;
        }

        .dark .product-cancel {
            background: #1f2937;
            border-color: #4b5563;
            color: #d1d5db;
        }

        .dark .imei-config {
            background: #1f2937;
            border-color: #4b3b83;
        }

        .dark .imei-switch-row {
            background: #111827;
            border-color: #374151;
        }

        .dark .imei-switch-info strong {
            color: #fff;
        }

        .dark .imei-switch-info small,
        .dark .imei-status {
            color: #9ca3af;
        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 767px) {

            .product-intro {
                padding: 18px;
                border-radius: 18px;
            }

            .product-intro-icon {
                width: 58px;
                height: 58px;
                min-width: 58px;
                border-radius: 17px;
                font-size: 23px;
            }

            .product-intro p {
                font-size: 13px;
            }

            .product-form-body {
                padding: 14px;
            }

            .product-form-header {
                padding: 18px;
            }

            .product-section {
                padding: 15px;
                border-radius: 15px;
            }

            .product-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .product-submit,
            .product-cancel {
                width: 100%;
            }

            .product-bg-circle {
                right: -150px;
            }
        }

        @media (max-width: 480px) {
            .imei-switch-row {
                align-items: flex-start;
            }

            .imei-config-description {
                margin-left: 0;
            }
        }

    
        .variant-toggle{display:flex;align-items:center;gap:12px;padding:15px;border:1px solid #cbd5e1;border-radius:12px;background:#f8fafc;cursor:pointer}
        .variant-toggle input{width:19px;height:19px;accent-color:#4f46e5}
        .variant-toggle span{display:grid;gap:3px}.variant-toggle small,.variant-note{font-size:12px;color:#64748b}
        #variantEditor{margin-top:18px}#variantEditor[hidden]{display:none}
        .variant-note{padding:12px 14px;border-radius:10px;background:#eef2ff;margin-bottom:15px;line-height:1.6}
        .variant-attribute{display:grid;grid-template-columns:1.1fr 1.4fr .8fr auto;gap:10px;align-items:end;padding:13px;border:1px solid #e2e8f0;border-radius:12px;margin:10px 0;background:#fff}
        .variant-attribute label,.variant-cell label{display:grid;gap:6px;font-size:12px;font-weight:600;color:#475569}
        .variant-attribute input,.variant-attribute select,.variant-cell input,.variant-cell select{width:100%;min-width:0;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;background:#fff;color:#0f172a;font-size:13px}
        .variant-remove{border:0;border-radius:8px;padding:10px;background:#fee2e2;color:#991b1b;cursor:pointer}
        .variant-add-attribute{margin:8px 0 18px;border:1px solid #6366f1;color:#4338ca;background:#eef2ff;border-radius:9px;padding:10px 14px;font-weight:600;cursor:pointer}
        .variant-table-wrap{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
        .variant-table-heading{display:flex;justify-content:space-between;gap:12px;padding:13px 15px;background:#f8fafc}
        .variant-table-heading span{font-size:12px;color:#64748b}
        .variant-rows{overflow-x:auto}.variant-grid{min-width:900px;width:100%;border-collapse:collapse}
        .variant-grid th,.variant-grid td{padding:9px;border-top:1px solid #e2e8f0;text-align:left;vertical-align:top;font-size:12px}
        .variant-grid th{background:#f8fafc;color:#475569}.variant-grid input,.variant-grid select{width:100%;min-width:90px;border:1px solid #cbd5e1;border-radius:7px;padding:7px}
        .variant-name{font-weight:650;color:#0f172a}.variant-empty{padding:18px;color:#64748b;font-size:13px}
        @media(max-width:700px){.variant-attribute{grid-template-columns:1fr 1fr}.variant-attribute .variant-remove{grid-column:2}}

</style>

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

                <div class="product-page mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                    <!-- ABSTRACT BACKGROUND -->

                    <div class="product-bg-shape product-bg-circle"></div>
                    <div class="product-bg-shape product-bg-circle-2"></div>
                    <div class="product-bg-grid"></div>


                    <!-- =====================================================
                         PAGE INTRO
                    ====================================================== -->

                    <div class="product-intro">

                        <div class="product-intro-content">

                            <div class="product-kicker">
                                <i class="fa-solid fa-cubes-stacked"></i>
                                Product Management
                            </div>

                            <h1>Add New Product</h1>

                            <p>
                                Create a product, configure its inventory and identifier tracking,
                                set pricing, and add product images or videos.
                            </p>

                        </div>

                        <div class="product-intro-icon">
                            <i class="fa-solid fa-box-open"></i>
                        </div>

                    </div>


                    <!-- =====================================================
                         FORM CARD
                    ====================================================== -->

                    <div class="product-form-card">

                        <div class="product-form-header">

                            <div class="product-form-header-icon">
                                <i class="fa-solid fa-square-plus"></i>
                            </div>

                            <div>
                                <h2>Product Information</h2>

                                <p>
                                    Enter the information required to add this product
                                    to your inventory.
                                </p>
                            </div>

                        </div>


                        <div class="product-form-body">

                            <form
                                method="POST"
                                action="create_product.php"
                                enctype="multipart/form-data"
                            >

                                <!-- Hidden Fields -->

                                <input
                                    type="hidden"
                                    name="company_id"
                                    value="<?= htmlspecialchars($_SESSION['company_id']) ?>"
                                >


                                <!-- =================================================
                                     BASIC INFORMATION
                                ================================================== -->

                                <div class="product-section">

                                    <div class="product-section-title">

                                        <i class="fa-solid fa-box"></i>

                                        <span>
                                            Basic Product Information
                                            <small>
                                                Identify and categorize your product.
                                            </small>
                                        </span>

                                    </div>


                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                        <!-- Product Name -->

                                        <div class="product-field">

                                            <label>
                                                <i class="fa-solid fa-tag"></i>
                                                Product Name
                                            </label>

                                            <input
                                                type="text"
                                                name="name"
                                                placeholder="e.g. Men's Leather Jacket"
                                                required
                                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                            >

                                        </div>


                                        <!-- Category -->

                                        <div class="product-field">

                                            <label>
                                                <i class="fa-solid fa-layer-group"></i>
                                                Category
                                            </label>

                                            <select
                                                name="category_id"
                                                id="categorySelect"
                                                required
                                                class="w-full px-4 py-3 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90"
                                            >

                                                <option value="">
                                                    -- Select Product Category --
                                                </option>

                                                <?php

                                                $stmt = $pdo->prepare("
                                                    SELECT
                                                        pc.*,
                                                        u.unit_name AS category_unit_name
                                                    FROM product_categories pc
                                                    LEFT JOIN units u
                                                        ON u.id = pc.unit_id
                                                        AND u.company_id = pc.company_id
                                                    WHERE pc.company_id = ?
                                                    ORDER BY pc.name ASC
                                                ");

                                                $stmt->execute([
                                                    $_SESSION['company_id']
                                                ]);

                                                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


                                                function renderCategoryOptions(
                                                    $categories,
                                                    $parentId = null,
                                                    $prefix = ''
                                                ) {

                                                    foreach ($categories as $cat) {

                                                        if ($cat['parent_id'] == $parentId) {

                                                            echo '<option value="' .
                                                                htmlspecialchars($cat['id']) .
                                                                '" data-unit-id="' .
                                                                htmlspecialchars($cat['unit_id'] ?? '') .
                                                                '" data-unit-name="' .
                                                                htmlspecialchars($cat['category_unit_name'] ?? '') .
                                                                '" data-unit-type="' .
                                                                htmlspecialchars($cat['unit_type'] ?? '') .
                                                                '" data-stock-type="' .
                                                                htmlspecialchars($cat['stock_type'] ?? '') .
                                                                '">' .
                                                                htmlspecialchars($prefix . $cat['name']) .
                                                                '</option>';

                                                            renderCategoryOptions(
                                                                $categories,
                                                                $cat['id'],
                                                                $prefix . '— '
                                                            );
                                                        }
                                                    }
                                                }

                                                renderCategoryOptions($categories);

                                                ?>

                                            </select>

                                        </div>

                                    </div>

                                </div>


                                <!-- =================================================
                                     INVENTORY CONFIGURATION
                                ================================================== -->

                                <div class="product-section auto-info">

                                    <div class="product-section-title">

                                        <i class="fa-solid fa-sliders"></i>

                                        <span>
                                            Inventory Configuration
                                            <small>
                                                These values are automatically determined from the selected category.
                                            </small>
                                        </span>

                                    </div>


                                    <div class="auto-badge">

                                        <i class="fa-solid fa-wand-magic-sparkles"></i>

                                        Automatic

                                    </div>


                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                        <!-- Unit -->

                                        <div class="product-field">

                                            <label>

                                                <i class="fa-solid fa-ruler"></i>

                                                Unit Name

                                            </label>

                                            <input
                                                type="text"
                                                name="unit_name"
                                                id="autoUnit"
                                                placeholder="Product Unit will appear here"
                                                readonly
                                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                            >

                                            <input
                                                type="hidden"
                                                name="unit_id"
                                                id="autoUnitID"
                                            >

                                        </div>


                                        <!-- Stock Type -->

                                        <div class="product-field">

                                            <label>

                                                <i class="fa-solid fa-boxes-stacked"></i>

                                                Stock Type

                                            </label>

                                            <input
                                                type="text"
                                                name="stock_type"
                                                id="autoStockType"
                                                placeholder="Stock type will appear here"
                                                readonly
                                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                            >

                                        </div>

                                    </div>

                                </div>


                                <!-- =================================================
                                     IMEI / SERIAL NUMBER TRACKING
                                ================================================== -->

                                <div class="product-section imei-config" id="imeiConfigSection">

                                    <div class="product-section-title">
                                        <i class="fa-solid fa-barcode"></i>
                                        <span>
                                            Product Identifier Tracking
                                            <small>
                                                Set the default identifier policy for this product. Individual variants can override it.
                                            </small>
                                        </span>
                                    </div>

                                    <p class="imei-config-description">
                                        This is the parent product's default policy. A variant such as Black, Red
                                        or Blue can inherit this policy or choose its own. During stock-in, each
                                        tracked physical unit must receive its own IMEI, serial number or VIN.
                                    </p>

                                    <div class="identifier-choice">
                                        <label for="identifierTracking">
                                            <i class="fa-solid fa-fingerprint"></i>
                                            Default Tracking Policy
                                        </label>

                                        <select
                                            name="identifier_tracking"
                                            id="identifierTracking"
                                            aria-describedby="identifierTrackingHelp"
                                        >
                                            <option value="none" selected>None — Quantity-based stock</option>
                                            <option value="imei">IMEI — Unique 15-digit identifier per unit</option>
                                            <option value="serial">Serial Number — Unique identifier per unit</option>
                                            <option value="vin">VIN — Unique 17-character vehicle identifier</option>
                                        </select>
                                    </div>

                                    <div class="identifier-help" id="identifierTrackingHelp">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span id="identifierTrackingStatus">
                                            No individual identifiers will be required during stock-in.
                                        </span>
                                    </div>

                                </div>



                                <!-- =================================================
                                     PRODUCT VARIANTS (OPTIONAL)
                                ================================================== -->
                                <div class="product-section" id="variantBuilder">
                                    <div class="product-section-title">
                                        <i class="fa-solid fa-layer-group"></i>
                                        <span>
                                            Product Variants
                                            <small>Enable this for products with options such as colour, size, RAM or storage. Each combination becomes a separately managed item.</small>
                                        </span>
                                    </div>
                                    <label class="variant-toggle">
                                        <input type="checkbox" id="hasVariantsToggle" name="has_variants" value="1">
                                        <span><strong>This product has variants</strong><small>Leave switched off for ordinary products.</small></span>
                                    </label>
                                    <div id="variantEditor" hidden>
                                        <div class="variant-note">
                                            Add attributes such as Colour, Storage or Size and enter their values separated by commas.
                                            Each generated combination is its own variant. Set its tracking policy to <strong>Inherit</strong>
                                            to use the parent product's default, or override it with <strong>None, IMEI, Serial or VIN</strong>.
                                            When tracked stock is received, every physical unit must be registered against its exact variant
                                            (for example, each Black iPhone gets its own IMEI).
                                        </div>
                                        <div id="variantAttributes"></div>
                                        <button type="button" id="addVariantAttribute" class="variant-add-attribute">+ Add attribute</button>
                                        <div class="variant-table-wrap">
                                            <div class="variant-table-heading"><strong>Generated variants</strong><span id="variantCount">0 variants</span></div>
                                            <div id="variantRows" class="variant-rows"><p class="variant-empty">Add attributes and values to generate combinations.</p></div>
                                        </div>
                                        <input type="hidden" name="variant_config" id="variantConfig" value="">
                                    </div>
                                </div>

                                <!-- =================================================
                                     PRICING
                                ================================================== -->

                                <div class="product-section">

                                    <div class="product-section-title">

                                        <i class="fa-solid fa-money-bill-trend-up"></i>

                                        <span>
                                            Product Pricing
                                            <small>
                                                Define the product's cost and selling price.
                                            </small>
                                        </span>

                                    </div>


                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                        <!-- Cost -->

                                        <div class="product-field">

                                            <label>

                                                <i class="fa-solid fa-coins"></i>

                                                Cost Price (₦)

                                            </label>

                                            <input
                                                type="number"
                                                name="cost_price"
                                                step="0.01"
                                                min="0"
                                                placeholder="e.g. 5000"
                                                required
                                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                            >

                                        </div>


                                        <!-- Selling -->

                                        <div class="product-field">

                                            <label>

                                                <i class="fa-solid fa-naira-sign"></i>

                                                Selling Price (₦)

                                            </label>

                                            <input
                                                type="number"
                                                name="selling_price"
                                                step="0.01"
                                                min="0"
                                                placeholder="e.g. 6500"
                                                required
                                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                            >

                                        </div>

                                    </div>

                                </div>


                                <!-- =================================================
                                     DESCRIPTION
                                ================================================== -->

                                <div class="product-section">

                                    <div class="product-section-title">

                                        <i class="fa-solid fa-align-left"></i>

                                        <span>
                                            Product Description
                                            <small>
                                                Add useful information about the product.
                                            </small>
                                        </span>

                                    </div>


                                    <div class="product-field">

                                        <label>

                                            <i class="fa-solid fa-file-lines"></i>

                                            Description

                                        </label>

                                        <textarea
                                            name="description"
                                            rows="4"
                                            placeholder="Enter product details, features, specifications, etc."
                                            class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        ></textarea>

                                    </div>

                                </div>


                                <!-- =================================================
                                     IMAGE
                                ================================================== -->

                                <div class="product-section">

                                    <div class="product-section-title">

                                        <i class="fa-solid fa-image"></i>

                                        <span>
                                            Product Media
                                            <small>
                                                Add a featured image, plus any additional product images or videos.
                                            </small>
                                        </span>

                                    </div>


                                    <div class="image-upload-box">

                                        <div class="image-upload-icon">

                                            <i class="fa-solid fa-cloud-arrow-up"></i>

                                        </div>

                                        <div class="product-field">

                                            <label>

                                                <i class="fa-solid fa-camera"></i>

                                                Featured Image

                                            </label>

                                            <input
                                                type="file"
                                                name="featured_img"
                                                accept="image/*"
                                                required
                                                class="dark:bg-dark-900 h-11 w-full border border-gray-300 px-4 py-2.5 text-sm text-gray-800 file:mr-4 file:rounded-full file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                            >

                                        </div>

                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">

                                            <i class="fa-solid fa-circle-info mr-1"></i>

                                            Choose a clear product image. This remains the featured image for compatibility with the current product creation handler.

                                        </p>

                                        <div class="product-field mt-5">
                                            <label>
                                                <i class="fa-solid fa-photo-film"></i>
                                                Additional Images &amp; Videos
                                            </label>

                                            <input
                                                type="file"
                                                name="product_media[]"
                                                id="productMedia"
                                                accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                                                multiple
                                                class="dark:bg-dark-900 h-auto min-h-11 w-full border border-gray-300 px-4 py-2.5 text-sm text-gray-800 file:mr-4 file:rounded-full file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                            >

                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                You can select multiple images and videos. Supported formats: JPG, PNG, WEBP, GIF, MP4, WEBM and MOV. Upload limits are controlled by your server.
                                            </p>

                                            <div id="productMediaPreview" class="product-media-preview" aria-live="polite"></div>
                                        </div>

                                    </div>

                                </div>


                                <!-- =================================================
                                     ACTIONS
                                ================================================== -->

                                <div class="product-actions">

                                    <button
                                        type="submit"
                                        class="product-submit"
                                    >

                                        <i class="fa-solid fa-plus"></i>

                                        Add Product

                                    </button>


                                    <a
                                        href="products.php"
                                        class="product-cancel"
                                    >

                                        <i class="fa-solid fa-arrow-left"></i>

                                        Cancel

                                    </a>

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


    <!-- footer session begins -->

    <?php include('footer.php'); ?>

    <!-- footer session ends -->


    <!-- Scripts -->

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
        data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
        crossorigin="anonymous"
    ></script>


    <!-- =========================================================
         CATEGORY AUTO-FILL + IDENTIFIER TRACKING VISIBILITY
         Existing Alpine.js, sidebar.php and header.php are untouched.
    ========================================================== -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categorySelect = document.getElementById('categorySelect');
            const autoUnit = document.getElementById('autoUnit');
            const autoUnitID = document.getElementById('autoUnitID');
            const autoStockType = document.getElementById('autoStockType');

            const identifierSection = document.getElementById('imeiConfigSection');
            const identifierSelect = document.getElementById('identifierTracking');
            const identifierStatus = document.getElementById('identifierTrackingStatus');

            // Client-side preview for additional product images and videos.
            const mediaInput = document.getElementById('productMedia');
            const mediaPreview = document.getElementById('productMediaPreview');

            if (mediaInput && mediaPreview) {
                mediaInput.addEventListener('change', function () {
                    mediaPreview.innerHTML = '';

                    Array.from(mediaInput.files || []).forEach(function (file) {
                        const item = document.createElement('div');
                        item.className = 'product-media-preview-item';

                        const isVideo = file.type.startsWith('video/');
                        const preview = document.createElement(isVideo ? 'video' : 'img');
                        preview.src = URL.createObjectURL(file);

                        if (isVideo) {
                            preview.controls = true;
                            preview.muted = true;
                        } else {
                            preview.alt = file.name;
                        }

                        const name = document.createElement('strong');
                        name.textContent = file.name;

                        const details = document.createElement('small');
                        details.textContent =
                            (isVideo ? 'Video' : 'Image') + ' · ' +
                            (file.size / (1024 * 1024)).toFixed(2) + ' MB';

                        item.appendChild(preview);
                        item.appendChild(name);
                        item.appendChild(details);
                        mediaPreview.appendChild(item);
                    });
                });
            }

            if (!categorySelect) {
                return;
            }

            function clearAutoFields() {
                if (autoUnit) autoUnit.value = '';
                if (autoUnitID) autoUnitID.value = '';
                if (autoStockType) autoStockType.value = '';
            }

            function updateIdentifierStatus() {
                if (!identifierSelect || !identifierStatus) return;

                const method = identifierSelect.value;

                if (method === 'imei') {
                    identifierStatus.textContent =
                        'Every received unit of this product or variant must be registered with its own valid 15-digit IMEI.';
                } else if (method === 'serial') {
                    identifierStatus.textContent =
                        'Every received unit of this product or variant must be registered with its own unique serial number.';
                } else if (method === 'vin') {
                    identifierStatus.textContent =
                        'Every received vehicle unit must be registered with its own unique 17-character VIN.';
                } else {
                    identifierStatus.textContent =
                        'No individual identifiers will be required during stock-in. Stock is managed by quantity.';
                }
            }

            function updateIdentifierVisibility(stockType) {
                const normalizedType = String(stockType || '').trim().toLowerCase();
                const eligible = normalizedType === 'sales only';

                if (identifierSection) {
                    identifierSection.classList.toggle('is-visible', eligible);
                }

                // Services and other non-physical categories cannot use identifiers.
                if (!eligible && identifierSelect) {
                    identifierSelect.value = 'none';
                }

                updateIdentifierStatus();
            }

            function fillAutoFields() {
                const selectedOption = categorySelect.options[categorySelect.selectedIndex];

                if (!selectedOption || !selectedOption.value) {
                    clearAutoFields();
                    updateIdentifierVisibility('');
                    return;
                }

                const unitId = selectedOption.getAttribute('data-unit-id') || '';
                const unitName = selectedOption.getAttribute('data-unit-name') || '';
                const stockType = selectedOption.getAttribute('data-stock-type') || '';

                if (autoUnit) autoUnit.value = unitName;
                if (autoUnitID) autoUnitID.value = unitId;
                if (autoStockType) autoStockType.value = stockType;

                updateIdentifierVisibility(stockType);
            }

            categorySelect.addEventListener('change', fillAutoFields);

            if (identifierSelect) {
                identifierSelect.addEventListener('change', updateIdentifierStatus);
            }

            fillAutoFields();
        });
    </script>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('hasVariantsToggle');
    const editor = document.getElementById('variantEditor');
    const attributesHost = document.getElementById('variantAttributes');
    const rowsHost = document.getElementById('variantRows');
    const configInput = document.getElementById('variantConfig');
    const countLabel = document.getElementById('variantCount');
    const addButton = document.getElementById('addVariantAttribute');
    if (!toggle || !editor || !attributesHost || !rowsHost || !configInput) return;

    let attributes = [];
    let variants = [];
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    function addAttribute(name='', values='', inputType='select') {
        const wrap = document.createElement('div');
        wrap.className = 'variant-attribute';
        wrap.innerHTML = `<label>Attribute name<input class="va-name" maxlength="100" placeholder="e.g. Colour" value="${esc(name)}"></label>
          <label>Values (comma separated)<input class="va-values" placeholder="Black, Red, Grey" value="${esc(values)}"></label>
          <label>Display style<select class="va-type"><option value="select">Dropdown</option><option value="swatch">Colour swatch</option></select></label>
          <button type="button" class="variant-remove" aria-label="Remove attribute">Remove</button>`;
        wrap.querySelector('.va-type').value = inputType === 'swatch' ? 'swatch' : 'select';
        wrap.querySelectorAll('input,select').forEach(el => el.addEventListener('input', rebuild));
        wrap.querySelector('.variant-remove').addEventListener('click', () => { wrap.remove(); rebuild(); });
        attributesHost.appendChild(wrap);
        rebuild();
    }
    function readAttributes() {
        return [...attributesHost.querySelectorAll('.variant-attribute')].map(row => ({
            name: row.querySelector('.va-name').value.trim(),
            input_type: row.querySelector('.va-type').value,
            values: [...new Set(row.querySelector('.va-values').value.split(',').map(v => v.trim()).filter(Boolean))]
        })).filter(a => a.name && a.values.length);
    }
    function cartesian(lists) {
        return lists.reduce((acc, list) => acc.flatMap(prefix => list.map(value => [...prefix, value])), [[]]);
    }
    function rebuild() {
        attributes = readAttributes();
        if (!toggle.checked || !attributes.length) {
            variants = [];
            rowsHost.innerHTML = '<p class="variant-empty">Add attributes and values to generate combinations.</p>';
            countLabel.textContent = '0 variants';
            configInput.value = '';
            return;
        }
        const combos = cartesian(attributes.map(a => a.values));
        if (combos.length > 300) {
            rowsHost.innerHTML = '<p class="variant-empty">This creates more than 300 combinations. Reduce the values before continuing.</p>';
            countLabel.textContent = combos.length + ' combinations (limit 300)';
            configInput.value = '';
            return;
        }
        const old = new Map(variants.map(v => [JSON.stringify(v.values), v]));
        variants = combos.map(combo => {
            const values = Object.fromEntries(attributes.map((a,i) => [a.name, combo[i]]));
            const prior = old.get(JSON.stringify(values)) || {};
            return {values, sku:prior.sku||'', barcode:prior.barcode||'', cost_price:prior.cost_price??'', selling_price:prior.selling_price??'', tracking_policy:prior.tracking_policy||'inherit', is_default:prior.is_default||false};
        });
        let html = '<table class="variant-grid"><thead><tr><th>Variant</th><th>SKU (optional)</th><th>Barcode (optional)</th><th>Cost price</th><th>Selling price</th><th>Unit tracking policy</th><th>Default</th></tr></thead><tbody>';
        variants.forEach((v,i) => {
            const name = attributes.map(a => a.name+': '+v.values[a.name]).join(' / ');
            html += `<tr><td class="variant-name">${esc(name)}</td>
            <td><input data-i="${i}" data-k="sku" value="${esc(v.sku)}" maxlength="100"></td>
            <td><input data-i="${i}" data-k="barcode" value="${esc(v.barcode)}" maxlength="100"></td>
            <td><input data-i="${i}" data-k="cost_price" type="number" min="0" step=".01" placeholder="Inherit" value="${esc(v.cost_price)}"></td>
            <td><input data-i="${i}" data-k="selling_price" type="number" min="0" step=".01" placeholder="Inherit" value="${esc(v.selling_price)}"></td>
            <td><select data-i="${i}" data-k="tracking_policy"><option value="inherit">Inherit parent</option><option value="none">None</option><option value="imei">IMEI</option><option value="serial">Serial number</option><option value="vin">VIN</option></select></td>
            <td><input data-i="${i}" data-k="is_default" type="radio" name="defaultVariant" ${v.is_default?'checked':''}></td></tr>`;
        });
        html += '</tbody></table>';
        rowsHost.innerHTML = html;
        const stockTypeField = document.getElementById('autoStockType');
        const identifierEligible = String(stockTypeField?.value || '').trim().toLowerCase() === 'sales only';
        rowsHost.querySelectorAll('select[data-k="tracking_policy"]').forEach(select => {
            select.querySelectorAll('option').forEach(option => {
                if (['imei', 'serial', 'vin'].includes(option.value)) {
                    option.disabled = !identifierEligible;
                }
            });
            if (!identifierEligible && ['imei', 'serial', 'vin'].includes(select.value)) {
                select.value = 'inherit';
                const index = Number(select.dataset.i);
                if (variants[index]) variants[index].tracking_policy = 'inherit';
            }
        });
        rowsHost.querySelectorAll('[data-i]').forEach(el => {
            const i = Number(el.dataset.i), key = el.dataset.k;
            if (key === 'tracking_policy') el.value = variants[i][key];
            if (key === 'is_default') el.addEventListener('change', () => { variants.forEach((v,j)=>v.is_default=j===i); sync(); });
            else el.addEventListener('input', () => { variants[i][key]=el.value; sync(); });
        });
        if (!variants.some(v => v.is_default)) variants[0].is_default = true;
        countLabel.textContent = variants.length + (variants.length === 1 ? ' variant' : ' variants');
        sync();
    }
    function sync() {
        configInput.value = JSON.stringify({attributes, variants});
    }
    toggle.addEventListener('change', () => { editor.hidden = !toggle.checked; rebuild(); });
    addButton.addEventListener('click', () => addAttribute());
    const form = toggle.closest('form');
    if (form) form.addEventListener('submit', function(e) {
        if (toggle.checked && (!attributes.length || !variants.length || !configInput.value)) {
            e.preventDefault();
            alert('Please add at least one attribute and valid values. Keep the generated combinations at or below 300.');
        }
    });
});
</script>

</body>

</html>
