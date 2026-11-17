
<?php
include('../config/db.php');

// Use the application's existing session context; this page does not add
// a separate login/authentication gate.


$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id || $product_id < 1) {
    echo "<script>alert('Invalid or missing product ID.'); window.location.href='products.php';</script>";
    exit;
}

$company_id = filter_var($_SESSION['company_id'] ?? null, FILTER_VALIDATE_INT);
$productStmt = $pdo->prepare('SELECT id, name, has_variants FROM products WHERE id = ? AND company_id = ? LIMIT 1');
$productStmt->execute([$product_id, $company_id]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

if (empty($_SESSION['product_media_upload_token'])) {
    $_SESSION['product_media_upload_token'] = bin2hex(random_bytes(32));
}

$variants = [];
if (!empty($product['has_variants'])) {
    if ($company_id) {
        $variantStmt = $pdo->prepare('SELECT id, variant_name, sku, is_default FROM product_variants WHERE product_id = ? AND company_id = ? ORDER BY is_default DESC, id ASC');
        $variantStmt->execute([$product_id, $company_id]);
    } else {
        $variantStmt = $pdo->prepare('SELECT id, variant_name, sku, is_default FROM product_variants WHERE product_id = ? AND company_id = ? ORDER BY is_default DESC, id ASC');
        $variantStmt->execute([$product_id, $company_id]);
    }
    $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
}

$requestedVariant = filter_input(INPUT_GET, 'variant_id', FILTER_VALIDATE_INT);
$selectedVariant = '';
if ($requestedVariant && $requestedVariant > 0) {
    foreach ($variants as $variant) {
        if ((int)$variant['id'] === (int)$requestedVariant) {
            $selectedVariant = (string)$variant['id'];
            break;
        }
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

    <title>Ubamus Global | Product Media</title>

    <link
        rel="icon"
        href="src/images/icons/favicon.png"
    >

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >

    <style>

        /* =========================================================
           PRODUCT MEDIA TERMINAL
           ========================================================= */

        :root {
            --pm-bg: #f5f7fb;
            --pm-card: rgba(255,255,255,.76);
            --pm-card-solid: #ffffff;
            --pm-border: rgba(15,23,42,.09);
            --pm-text: #0f172a;
            --pm-muted: #64748b;
            --pm-accent: #635bff;
            --pm-accent-2: #8b5cf6;
            --pm-success: #10b981;
            --pm-shadow:
                0 24px 70px rgba(15,23,42,.09),
                0 4px 18px rgba(15,23,42,.04);
        }

        * {
            box-sizing: border-box;
        }

        .pm-page {
            position: relative;
            min-height: calc(100vh - 80px);
            padding: 24px;
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 8% 12%,
                    rgba(99,91,255,.09),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 92% 82%,
                    rgba(139,92,246,.08),
                    transparent 28%
                ),
                var(--pm-bg);
        }

        .pm-orb {
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(2px);
            opacity: .45;
        }

        .pm-orb-one {
            top: -140px;
            right: 8%;
            background: radial-gradient(
                circle,
                rgba(99,91,255,.18),
                transparent 68%
            );
        }

        .pm-orb-two {
            bottom: -160px;
            left: 5%;
            background: radial-gradient(
                circle,
                rgba(139,92,246,.15),
                transparent 68%
            );
        }

        .pm-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }

        /* =========================================================
           HEADER
           ========================================================= */

        .pm-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 22px;
        }

        .pm-heading-left {
            min-width: 0;
        }

        .pm-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--pm-accent);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .pm-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--pm-success);
            box-shadow: 0 0 0 5px rgba(16,185,129,.10);
        }

        .pm-title {
            margin: 0;
            color: var(--pm-text);
            font-size: clamp(24px, 4vw, 36px);
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .pm-subtitle {
            max-width: 650px;
            margin: 8px 0 0;
            color: var(--pm-muted);
            font-size: 14px;
            line-height: 1.65;
        }

        .pm-product-id {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 13px;
            border: 1px solid var(--pm-border);
            border-radius: 14px;
            background: rgba(255,255,255,.62);
            box-shadow: 0 8px 25px rgba(15,23,42,.04);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            color: var(--pm-muted);
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pm-product-id strong {
            color: var(--pm-text);
            font-size: 12px;
        }

        /* =========================================================
           MAIN GLASS CARD
           ========================================================= */

        .pm-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.82);
            border-radius: 28px;
            background: var(--pm-card);
            box-shadow: var(--pm-shadow);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .pm-card::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.65),
                    transparent 36%
                );
        }

        .pm-card-top {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 22px 24px;
            border-bottom: 1px solid rgba(15,23,42,.07);
        }

        .pm-icon {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            border: 1px solid rgba(99,91,255,.16);
            border-radius: 15px;
            background:
                linear-gradient(
                    145deg,
                    rgba(99,91,255,.13),
                    rgba(139,92,246,.08)
                );
            color: var(--pm-accent);
            font-size: 19px;
        }

        .pm-card-heading {
            min-width: 0;
        }

        .pm-card-title {
            margin: 0;
            color: var(--pm-text);
            font-size: 16px;
            font-weight: 800;
        }

        .pm-card-description {
            margin: 3px 0 0;
            color: var(--pm-muted);
            font-size: 12px;
        }

        /* =========================================================
           UPLOAD AREA
           ========================================================= */

        .pm-content {
            position: relative;
            padding: 24px;
        }

        .pm-dropzone {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 310px;
            padding: 32px 22px;
            border: 1.5px dashed rgba(99,91,255,.28);
            border-radius: 22px;
            background:
                linear-gradient(
                    145deg,
                    rgba(99,91,255,.035),
                    rgba(139,92,246,.025)
                );
            text-align: center;
            cursor: pointer;
            transition:
                border-color .25s ease,
                background .25s ease,
                transform .25s ease,
                box-shadow .25s ease;
        }

        .pm-dropzone:hover,
        .pm-dropzone.is-dragging {
            border-color: rgba(99,91,255,.60);
            background:
                linear-gradient(
                    145deg,
                    rgba(99,91,255,.075),
                    rgba(139,92,246,.045)
                );
            box-shadow:
                0 18px 45px rgba(99,91,255,.08);
        }

        .pm-dropzone.is-dragging {
            transform: scale(1.008);
        }

        .pm-upload-icon {
            display: grid;
            place-items: center;
            width: 72px;
            height: 72px;
            margin-bottom: 18px;
            border: 1px solid rgba(99,91,255,.16);
            border-radius: 22px;
            background: rgba(255,255,255,.72);
            box-shadow:
                0 12px 28px rgba(99,91,255,.10);
            color: var(--pm-accent);
            font-size: 28px;
        }

        .pm-drop-title {
            margin: 0;
            color: var(--pm-text);
            font-size: 17px;
            font-weight: 800;
        }

        .pm-drop-text {
            max-width: 500px;
            margin: 7px auto 0;
            color: var(--pm-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .pm-browse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            padding: 11px 16px;
            border: 0;
            border-radius: 12px;
            background:
                linear-gradient(
                    135deg,
                    var(--pm-accent),
                    var(--pm-accent-2)
                );
            box-shadow:
                0 10px 24px rgba(99,91,255,.20);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            pointer-events: none;
        }

        .pm-input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        /* =========================================================
           FILE INFORMATION
           ========================================================= */

        .pm-file-status {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            padding: 13px 15px;
            border: 1px solid rgba(15,23,42,.07);
            border-radius: 15px;
            background: rgba(255,255,255,.65);
        }

        .pm-file-status.visible {
            display: flex;
        }

        .pm-file-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .pm-file-info-icon {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 10px;
            background: rgba(16,185,129,.10);
            color: var(--pm-success);
        }

        .pm-file-name {
            overflow: hidden;
            color: var(--pm-text);
            font-size: 12px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pm-file-count {
            flex-shrink: 0;
            color: var(--pm-muted);
            font-size: 11px;
            font-weight: 700;
        }

        /* =========================================================
           FOOTER ACTIONS
           ========================================================= */

        .pm-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 20px;
        }

        .pm-note {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            color: var(--pm-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .pm-note i {
            margin-top: 2px;
            color: var(--pm-accent);
        }

        .pm-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pm-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 45px;
            padding: 0 17px;
            border-radius: 13px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .pm-btn:hover {
            transform: translateY(-1px);
        }

        .pm-btn-cancel {
            border: 1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.72);
            color: #475569;
        }

        .pm-btn-cancel:hover {
            background: #fff;
            box-shadow: 0 8px 20px rgba(15,23,42,.06);
        }

        .pm-btn-upload {
            border: 0;
            background:
                linear-gradient(
                    135deg,
                    var(--pm-accent),
                    var(--pm-accent-2)
                );
            box-shadow:
                0 12px 26px rgba(99,91,255,.20);
            color: #fff;
            cursor: pointer;
        }

        .pm-btn-upload:hover {
            box-shadow:
                0 15px 32px rgba(99,91,255,.27);
        }

        .pm-btn-upload:disabled {
            opacity: .48;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* =========================================================
           SECURITY STRIP
           ========================================================= */

        .pm-security {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 16px;
        }

        .pm-security-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 13px;
            border: 1px solid rgba(15,23,42,.06);
            border-radius: 13px;
            background: rgba(255,255,255,.45);
        }

        .pm-security-item i {
            color: var(--pm-accent);
            font-size: 12px;
        }

        .pm-security-item span {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
        }

        /* =========================================================
           MOBILE
           ========================================================= */

        @media (max-width: 900px) {

            .pm-page {
                padding: 18px;
            }

            .pm-heading {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
            }

            .pm-product-id {
                width: 100%;
                justify-content: space-between;
            }

            .pm-card {
                border-radius: 23px;
            }

            .pm-security {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {

            .pm-page {
                min-height: calc(100vh - 60px);
                padding: 12px;
            }

            .pm-title {
                font-size: 25px;
            }

            .pm-subtitle {
                font-size: 12px;
            }

            .pm-card-top {
                padding: 17px;
            }

            .pm-content {
                padding: 14px;
            }

            .pm-icon {
                width: 41px;
                height: 41px;
                border-radius: 13px;
                font-size: 17px;
            }

            .pm-card-title {
                font-size: 14px;
            }

            .pm-card-description {
                font-size: 11px;
            }

            .pm-dropzone {
                min-height: 270px;
                padding: 25px 16px;
                border-radius: 18px;
            }

            .pm-upload-icon {
                width: 61px;
                height: 61px;
                margin-bottom: 15px;
                border-radius: 18px;
                font-size: 24px;
            }

            .pm-drop-title {
                font-size: 15px;
            }

            .pm-drop-text {
                font-size: 11px;
            }

            .pm-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .pm-note {
                order: 2;
            }

            .pm-buttons {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .pm-btn {
                width: 100%;
                padding: 0 10px;
            }
        }

        @media (max-width: 390px) {

            .pm-buttons {
                grid-template-columns: 1fr;
            }

            .pm-btn {
                min-height: 43px;
            }

            .pm-dropzone {
                min-height: 250px;
            }
        }

        /* =========================================================
           DARK MODE COMPATIBILITY
           Keeps compatibility with your existing Alpine dark mode.
           ========================================================= */

        .dark .pm-page {
            --pm-bg: #090d18;
            --pm-card: rgba(15,23,42,.72);
            --pm-card-solid: #0f172a;
            --pm-border: rgba(255,255,255,.08);
            --pm-text: #f8fafc;
            --pm-muted: #94a3b8;
            background:
                radial-gradient(
                    circle at 8% 12%,
                    rgba(99,91,255,.14),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 92% 82%,
                    rgba(139,92,246,.10),
                    transparent 28%
                ),
                var(--pm-bg);
        }

        .dark .pm-product-id,
        .dark .pm-file-status,
        .dark .pm-upload-icon,
        .dark .pm-security-item,
        .dark .pm-btn-cancel {
            background: rgba(15,23,42,.70);
            border-color: rgba(255,255,255,.08);
        }

        .dark .pm-card {
            border-color: rgba(255,255,255,.07);
        }

        .dark .pm-card-top {
            border-color: rgba(255,255,255,.07);
        }

        .dark .pm-dropzone {
            background: rgba(99,91,255,.035);
        }

    
        .pm-variant-field {
            position: relative;
            margin: 0 0 18px;
            padding: 16px 18px;
            border: 1px solid rgba(99,91,255,.16);
            border-radius: 16px;
            background: rgba(99,91,255,.045);
        }
        .pm-variant-field label {
            display: block;
            margin-bottom: 8px;
            color: var(--pm-text);
            font-size: 12px;
            font-weight: 800;
        }
        .pm-variant-field select {
            width: 100%;
            min-height: 44px;
            padding: 9px 12px;
            border: 1px solid rgba(100,116,139,.28);
            border-radius: 10px;
            background: #fff;
            color: #172033;
            font: inherit;
        }
        .pm-variant-field small {
            display: block;
            margin-top: 8px;
            color: var(--pm-muted);
            font-size: 11px;
            line-height: 1.6;
        }

    </style>

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
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

    <!-- =========================================================
         PAGE WRAPPER
         ========================================================= -->

    <div class="flex h-screen overflow-hidden">

        <?php include('sidebar.php'); ?>

        <!-- =====================================================
             CONTENT AREA
             ===================================================== -->

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

            <main>

                <?php include('header.php'); ?>


                <!-- =================================================
                     PRODUCT MEDIA PAGE
                     ================================================= -->

                <section class="pm-page">

                    <div class="pm-orb pm-orb-one"></div>
                    <div class="pm-orb pm-orb-two"></div>


                    <div class="pm-container">

                        <!-- =================================================
                             PAGE HEADING
                             ================================================= -->

                        <div class="pm-heading">

                            <div class="pm-heading-left">

                                <div class="pm-eyebrow">
                                    <span class="pm-status-dot"></span>
                                    Product Media Terminal
                                </div>

                                <h1 class="pm-title">
                                    Add Product Media
                                </h1>

                                <p class="pm-subtitle">
                                    Upload visual assets for this product.
                                    Images and videos will be associated with the product or
                                    selected variant for the product identified below.
                                </p>

                            </div>


                            <div class="pm-product-id">

                                <i class="fa-solid fa-cube"></i>

                                <span>
                                    PRODUCT ID
                                </span>

                                <strong>
                                    #<?= htmlspecialchars((string)$product_id, ENT_QUOTES, 'UTF-8') ?>
                                </strong>

                            </div>

                        </div>


                        <!-- =================================================
                             MAIN GLASS CARD
                             ================================================= -->

                        <div class="pm-card">

                            <div class="pm-card-top">

                                <div class="pm-icon">
                                    <i class="fa-solid fa-images"></i>
                                </div>

                                <div class="pm-card-heading">

                                    <h2 class="pm-card-title">
                                        Product Media Upload
                                    </h2>

                                    <p class="pm-card-description">
                                        Select one or more product images or videos
                                        from your device.
                                    </p>

                                </div>

                            </div>


                            <div class="pm-content">

                                <!-- =================================================
                                     IMPORTANT:
                                     Form action, method, enctype, hidden product_id
                                     and input name are intentionally preserved.
                                     ================================================= -->

                                <form
                                    method="POST"
                                    action="upload_product_images.php"
                                    enctype="multipart/form-data"
                                    id="productImageForm"
                                >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= htmlspecialchars((string)$product_id, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['product_media_upload_token'], ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <?php if (!empty($product['has_variants']) && !empty($variants)): ?>
                                        <div class="pm-variant-field">
                                            <label for="variant_id">Attach media to</label>
                                            <select name="variant_id" id="variant_id">
                                                <option value="" <?= $selectedVariant === '' ? 'selected' : '' ?>>Parent product — shared media</option>
                                                <?php foreach ($variants as $variant): ?>
                                                    <option value="<?= (int)$variant['id'] ?>" <?= $selectedVariant === (string)$variant['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($variant['variant_name'] ?: 'Variant #' . $variant['id']), ENT_QUOTES, 'UTF-8') ?><?= !empty($variant['sku']) ? ' · ' . htmlspecialchars((string)$variant['sku'], ENT_QUOTES, 'UTF-8') : '' ?><?= !empty($variant['is_default']) ? ' · Default' : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small>Choose a variant to keep these files in its own gallery, or choose the parent product for shared media.</small>
                                        </div>
                                    <?php endif; ?>


                                    <!-- =================================================
                                         DROPZONE
                                         ================================================= -->

                                    <label
                                        for="productImages"
                                        class="pm-dropzone"
                                        id="dropZone"
                                    >

                                        <div class="pm-upload-icon">

                                            <i class="fa-solid fa-cloud-arrow-up"></i>

                                        </div>


                                        <h3 class="pm-drop-title">
                                            Drop product images and videos here
                                        </h3>


                                        <p class="pm-drop-text">
                                            Drag and drop your images or videos into this
                                            area or browse your device to select
                                            the product media you want to upload.
                                        </p>


                                        <span class="pm-browse">

                                            <i class="fa-solid fa-folder-open"></i>

                                            Browse Media

                                        </span>


                                        <input
                                            type="file"
                                            name="product_images[]"
                                            id="productImages"
                                            class="pm-input"
                                            multiple
                                            accept="image/*,video/*"
                                            required
                                        >

                                    </label>


                                    <!-- =================================================
                                         FILE STATUS
                                         ================================================= -->

                                    <div
                                        class="pm-file-status"
                                        id="fileStatus"
                                    >

                                        <div class="pm-file-info">

                                            <div class="pm-file-info-icon">
                                                <i class="fa-solid fa-check"></i>
                                            </div>

                                            <div
                                                class="pm-file-name"
                                                id="fileName"
                                            >
                                                No media selected
                                            </div>

                                        </div>


                                        <div
                                            class="pm-file-count"
                                            id="fileCount"
                                        >
                                            0 files
                                        </div>

                                    </div>


                                    <!-- =================================================
                                         SECURITY / CAPABILITY STRIP
                                         ================================================= -->

                                    <div class="pm-security">

                                        <div class="pm-security-item">

                                            <i class="fa-solid fa-image"></i>

                                            <span>
                                                IMAGE FILES
                                            </span>

                                        </div>


                                        <div class="pm-security-item">

                                            <i class="fa-solid fa-layer-group"></i>

                                            <span>
                                                MULTIPLE SELECTION
                                            </span>

                                        </div>


                                        <div class="pm-security-item">

                                            <i class="fa-solid fa-link"></i>

                                            <span>
                                                PRODUCT LINKED
                                            </span>

                                        </div>

                                    </div>


                                    <!-- =================================================
                                         ACTIONS
                                         ================================================= -->

                                    <div class="pm-actions">

                                        <div class="pm-note">

                                            <i class="fa-solid fa-circle-info"></i>

                                            <span>
                                                Images are submitted directly to
                                                the existing upload processor.
                                                The product ID remains attached
                                                to this submission.
                                            </span>

                                        </div>


                                        <div class="pm-buttons">

                                            <a
                                                href="products.php"
                                                class="pm-btn pm-btn-cancel"
                                            >

                                                <i class="fa-solid fa-arrow-left"></i>

                                                Cancel

                                            </a>


                                            <button
                                                type="submit"
                                                class="pm-btn pm-btn-upload"
                                                id="uploadButton"
                                                disabled
                                            >

                                                <i class="fa-solid fa-cloud-arrow-up"></i>

                                                Upload Media

                                            </button>

                                        </div>

                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                </section>

            </main>

        </div>

    </div>


    <!-- =========================================================
         FOOTER
         ========================================================= -->

    <?php include('footer.php'); ?>


    <!-- =========================================================
         EXISTING APPLICATION SCRIPTS
         ========================================================= -->

    <script
        data-cfasync="false"
        src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
    ></script>

    <script
        defer
        src="bundle.js"
    ></script>


    <!-- =========================================================
         UPLOAD UI INTERACTION
         Does NOT modify your PHP processing.
         ========================================================= -->

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const fileInput = document.getElementById('productImages');
            const dropZone = document.getElementById('dropZone');
            const fileStatus = document.getElementById('fileStatus');
            const fileName = document.getElementById('fileName');
            const fileCount = document.getElementById('fileCount');
            const uploadButton = document.getElementById('uploadButton');
            const form = document.getElementById('productImageForm');


            if (!fileInput || !dropZone || !form) {
                return;
            }


            function updateFileDisplay(files) {

                if (!files || files.length === 0) {

                    fileStatus.classList.remove('visible');

                    fileName.textContent = 'No media selected';

                    fileCount.textContent = '0 files';

                    uploadButton.disabled = true;

                    return;
                }


                const count = files.length;

                let displayName = files[0].name;

                if (count > 1) {

                    displayName += ' +' + (count - 1) + ' more';

                }


                fileName.textContent = displayName;

                fileCount.textContent =
                    count === 1
                        ? '1 file selected'
                        : count + ' files selected';


                fileStatus.classList.add('visible');

                uploadButton.disabled = false;

            }


            fileInput.addEventListener('change', function () {

                updateFileDisplay(this.files);

            });


            /* =====================================================
               DRAG ENTER
               ===================================================== */

            ['dragenter', 'dragover'].forEach(function (eventName) {

                dropZone.addEventListener(eventName, function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    dropZone.classList.add('is-dragging');

                });

            });


            /* =====================================================
               DRAG LEAVE
               ===================================================== */

            ['dragleave', 'drop'].forEach(function (eventName) {

                dropZone.addEventListener(eventName, function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    dropZone.classList.remove('is-dragging');

                });

            });


            /* =====================================================
               DROP
               ===================================================== */

            dropZone.addEventListener('drop', function (event) {

                const files = event.dataTransfer.files;

                if (!files || files.length === 0) {
                    return;
                }

                try {

                    fileInput.files = files;

                    updateFileDisplay(files);

                } catch (error) {

                    console.warn(
                        'Browser prevented direct file assignment.',
                        error
                    );

                }

            });


            /* =====================================================
               PREVENT EMPTY SUBMISSION
               ===================================================== */

            form.addEventListener('submit', function (event) {

                if (!fileInput.files || fileInput.files.length === 0) {

                    event.preventDefault();

                    alert('Please select at least one product image or video.');

                    return;

                }

                uploadButton.disabled = true;

                uploadButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';

            });

        });

    </script>


    <!-- =========================================================
         CLOUDFLARE INSIGHTS
         ========================================================= -->

    <script
        defer
        src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015"
        integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ=="
        data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain","cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
        crossorigin="anonymous"
    ></script>

</body>

</html>