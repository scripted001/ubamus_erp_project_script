
<?php
  include('../config/db.php');
  include('../config/validation.php');
  include('user_data.php');
  include('../company_data.php');

  if (isset($_GET['id']) && is_numeric($_GET['id'])) {
      $product_id = (int) $_GET['id'];
  } else {
      echo "<script>
          alert('Invalid or missing product ID.');
          window.location.href='products.php';
      </script>";
      exit;
  }
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    >

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>
        <?php echo htmlspecialchars($company_name); ?> | Add Product Feature
    </title>

    <link rel="icon" href="src/images/icons/favicon.png">
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>

        /* =========================================================
           PRODUCT FEATURE TERMINAL
           ========================================================= */

        :root {
            --pf-bg: #f5f7fb;
            --pf-surface: rgba(255,255,255,.72);
            --pf-surface-strong: rgba(255,255,255,.9);
            --pf-border: rgba(15,23,42,.08);
            --pf-text: #111827;
            --pf-muted: #64748b;
            --pf-brand: #635bff;
            --pf-brand-2: #8b5cf6;
            --pf-success: #10b981;
            --pf-shadow: 0 24px 70px rgba(15,23,42,.10);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at 10% 10%, rgba(99,91,255,.10), transparent 28%),
                radial-gradient(circle at 90% 15%, rgba(139,92,246,.08), transparent 25%),
                radial-gradient(circle at 70% 90%, rgba(16,185,129,.06), transparent 25%),
                var(--pf-bg);
            color: var(--pf-text);
        }

        /* =========================================================
           MAIN CONTAINER
           ========================================================= */

        .feature-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 28px 24px 60px;
        }

        /* =========================================================
           HERO
           ========================================================= */

        .feature-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(99,91,255,.13);
            border-radius: 28px;
            padding: 30px;
            margin-bottom: 22px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.88),
                    rgba(255,255,255,.62)
                );

            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);

            box-shadow: var(--pf-shadow);
        }

        .feature-hero::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            top: -150px;
            right: -80px;

            background: rgba(99,91,255,.13);
            filter: blur(8px);
            pointer-events: none;
        }

        .feature-hero::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            bottom: -120px;
            left: 20%;

            background: rgba(139,92,246,.08);
            filter: blur(8px);
            pointer-events: none;
        }

        .feature-hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .hero-icon {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 19px;

            color: white;
            font-size: 23px;

            background:
                linear-gradient(
                    135deg,
                    var(--pf-brand),
                    var(--pf-brand-2)
                );

            box-shadow:
                0 12px 30px rgba(99,91,255,.27),
                inset 0 1px 0 rgba(255,255,255,.35);
        }

        .hero-eyebrow {
            margin: 0 0 5px;

            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;

            color: var(--pf-brand);
        }

        .hero-title {
            margin: 0;

            font-size: clamp(23px, 3vw, 34px);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .hero-description {
            margin: 8px 0 0;
            max-width: 620px;

            color: var(--pf-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .product-id-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 10px 14px;
            border-radius: 999px;

            background: rgba(255,255,255,.72);
            border: 1px solid rgba(15,23,42,.08);

            color: #475569;
            font-size: 12px;
            font-weight: 700;

            box-shadow: 0 8px 25px rgba(15,23,42,.06);
            white-space: nowrap;
        }

        .product-id-chip i {
            color: var(--pf-brand);
        }

        /* =========================================================
           WORKSPACE
           ========================================================= */

        .feature-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 22px;
            align-items: start;
        }

        /* =========================================================
           FORM CARD
           ========================================================= */

        .feature-card {
            position: relative;
            overflow: hidden;

            border: 1px solid var(--pf-border);
            border-radius: 28px;

            background: var(--pf-surface);

            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);

            box-shadow: var(--pf-shadow);
        }

        .feature-card::before {
            content: "";
            position: absolute;
            inset: 0;

            background:
                linear-gradient(
                    120deg,
                    rgba(255,255,255,.25),
                    transparent 35%
                );

            pointer-events: none;
        }

        .card-header {
            position: relative;
            z-index: 2;

            padding: 25px 28px 21px;

            border-bottom: 1px solid rgba(15,23,42,.07);
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .card-title-group {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .mini-icon {
            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            color: var(--pf-brand);
            background: rgba(99,91,255,.09);
            border: 1px solid rgba(99,91,255,.12);
        }

        .card-title {
            margin: 0;

            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .card-subtitle {
            margin: 4px 0 0;

            color: var(--pf-muted);
            font-size: 12px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 8px 11px;
            border-radius: 999px;

            font-size: 11px;
            font-weight: 800;

            color: #047857;
            background: rgba(16,185,129,.09);
            border: 1px solid rgba(16,185,129,.14);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--pf-success);
            box-shadow: 0 0 0 4px rgba(16,185,129,.10);
        }

        /* =========================================================
           FORM
           ========================================================= */

        .feature-form {
            position: relative;
            z-index: 2;
            padding: 28px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .field {
            min-width: 0;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .field-label {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 8px;

            color: #334155;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .field-required {
            color: #ef4444;
            font-size: 11px;
        }

        .field-input-wrap {
            position: relative;
        }

        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;
            font-size: 14px;

            pointer-events: none;
        }

        .feature-input {
            width: 100%;
            min-height: 52px;

            padding: 13px 16px 13px 45px;

            border: 1px solid rgba(15,23,42,.10);
            border-radius: 15px;

            outline: none;

            color: #0f172a;
            background: rgba(255,255,255,.74);

            font-family: inherit;
            font-size: 14px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                transform .2s ease;
        }

        .feature-input::placeholder {
            color: #94a3b8;
        }

        .feature-input:hover {
            background: rgba(255,255,255,.9);
            border-color: rgba(99,91,255,.18);
        }

        .feature-input:focus {
            background: rgba(255,255,255,.96);
            border-color: rgba(99,91,255,.55);

            box-shadow:
                0 0 0 4px rgba(99,91,255,.09),
                0 10px 30px rgba(99,91,255,.08);

            transform: translateY(-1px);
        }

        /* =========================================================
           PRODUCT CONTEXT
           ========================================================= */

        .product-context {
            display: flex;
            align-items: center;
            gap: 14px;

            min-height: 72px;
            padding: 13px 15px;

            border-radius: 17px;

            border: 1px solid rgba(99,91,255,.13);

            background:
                linear-gradient(
                    135deg,
                    rgba(99,91,255,.055),
                    rgba(139,92,246,.035)
                );
        }

        .context-icon {
            width: 43px;
            height: 43px;
            flex: 0 0 43px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            color: var(--pf-brand);
            background: white;

            border: 1px solid rgba(99,91,255,.10);
            box-shadow: 0 7px 18px rgba(15,23,42,.06);
        }

        .context-label {
            margin: 0 0 3px;

            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .13em;
            font-weight: 800;

            color: #94a3b8;
        }

        .context-value {
            margin: 0;

            font-size: 13px;
            font-weight: 800;
            color: #334155;
        }

        .context-value span {
            color: var(--pf-brand);
        }

        /* =========================================================
           ACTION BAR
           ========================================================= */

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;

            margin-top: 28px;
            padding-top: 23px;

            border-top: 1px solid rgba(15,23,42,.07);
        }

        .action-info {
            display: flex;
            align-items: center;
            gap: 9px;

            color: #64748b;
            font-size: 11px;
        }

        .action-info i {
            color: var(--pf-success);
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            min-height: 46px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            padding: 0 18px;

            border-radius: 13px;

            font-family: inherit;
            font-size: 13px;
            font-weight: 800;

            text-decoration: none;
            cursor: pointer;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-cancel {
            color: #475569;

            background: rgba(255,255,255,.72);
            border: 1px solid rgba(15,23,42,.10);
        }

        .btn-cancel:hover {
            background: white;
            box-shadow: 0 9px 25px rgba(15,23,42,.08);
        }

        .btn-primary {
            color: white;

            border: 0;

            background:
                linear-gradient(
                    135deg,
                    var(--pf-brand),
                    var(--pf-brand-2)
                );

            box-shadow:
                0 12px 25px rgba(99,91,255,.22),
                inset 0 1px 0 rgba(255,255,255,.25);
        }

        .btn-primary:hover {
            box-shadow:
                0 16px 32px rgba(99,91,255,.28),
                inset 0 1px 0 rgba(255,255,255,.25);
        }

        /* =========================================================
           INFORMATION PANEL
           ========================================================= */

        .info-card {
            position: relative;
            overflow: hidden;

            padding: 24px;

            border: 1px solid rgba(99,91,255,.11);
            border-radius: 25px;

            background: rgba(255,255,255,.65);

            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);

            box-shadow: 0 20px 55px rgba(15,23,42,.08);
        }

        .info-card::after {
            content: "";
            position: absolute;

            width: 130px;
            height: 130px;

            right: -65px;
            top: -65px;

            border-radius: 50%;

            background: rgba(99,91,255,.08);
        }

        .info-heading {
            position: relative;
            z-index: 2;

            display: flex;
            align-items: center;
            gap: 11px;

            margin-bottom: 20px;
        }

        .info-heading-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            color: var(--pf-brand);
            background: rgba(99,91,255,.08);
        }

        .info-heading h3 {
            margin: 0;

            font-size: 14px;
            font-weight: 800;
        }

        .info-heading p {
            margin: 3px 0 0;

            color: #94a3b8;
            font-size: 10px;
        }

        .info-list {
            position: relative;
            z-index: 2;

            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .info-item {
            display: flex;
            gap: 11px;
            align-items: flex-start;
        }

        .info-number {
            width: 26px;
            height: 26px;
            flex: 0 0 26px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            color: var(--pf-brand);
            background: rgba(99,91,255,.08);

            font-size: 10px;
            font-weight: 900;
        }

        .info-item strong {
            display: block;

            margin-bottom: 2px;

            color: #334155;
            font-size: 11px;
        }

        .info-item span {
            display: block;

            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        /* =========================================================
           RESPONSIVE
           ========================================================= */

        @media (max-width: 1050px) {

            .feature-workspace {
                grid-template-columns: 1fr;
            }

            .info-card {
                display: none;
            }
        }

        @media (max-width: 760px) {

            .feature-page {
                padding: 18px 14px 40px;
            }

            .feature-hero {
                padding: 22px;
                border-radius: 23px;
            }

            .feature-hero-content {
                align-items: flex-start;
                flex-direction: column;
            }

            .product-id-chip {
                align-self: flex-start;
            }

            .hero-icon {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
                border-radius: 16px;
            }

            .hero-description {
                font-size: 13px;
            }

            .feature-card {
                border-radius: 23px;
            }

            .card-header {
                padding: 21px;
            }

            .feature-form {
                padding: 21px;
            }

            .field-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .field-full {
                grid-column: auto;
            }

            .action-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .action-info {
                justify-content: center;
                text-align: center;
            }

            .action-buttons {
                display: grid;
                grid-template-columns: 1fr 1.3fr;
            }

            .btn {
                width: 100%;
                padding: 0 12px;
            }
        }

        @media (max-width: 430px) {

            .feature-page {
                padding-left: 10px;
                padding-right: 10px;
            }

            .feature-hero {
                padding: 18px;
            }

            .hero-left {
                gap: 13px;
            }

            .hero-title {
                font-size: 22px;
            }

            .card-header-row {
                align-items: flex-start;
            }

            .status-pill {
                display: none;
            }

            .feature-form {
                padding: 17px;
            }

            .product-context {
                min-height: 64px;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }

        /* =========================================================
           DARK MODE SUPPORT
           ========================================================= */

        .dark .feature-page {
            --pf-bg: #0b1020;
            --pf-surface: rgba(17,24,39,.68);
            --pf-surface-strong: rgba(17,24,39,.88);
            --pf-border: rgba(255,255,255,.08);
            --pf-text: #f8fafc;
            --pf-muted: #94a3b8;
        }

        .dark .feature-hero,
        .dark .feature-card,
        .dark .info-card {
            background:
                linear-gradient(
                    135deg,
                    rgba(17,24,39,.82),
                    rgba(15,23,42,.66)
                );
            border-color: rgba(255,255,255,.08);
        }

        .dark .card-header,
        .dark .action-bar {
            border-color: rgba(255,255,255,.07);
        }

        .dark .hero-title,
        .dark .card-title {
            color: #f8fafc;
        }

        .dark .field-label,
        .dark .context-value,
        .dark .info-item strong {
            color: #e2e8f0;
        }

        .dark .feature-input,
        .dark .product-id-chip,
        .dark .btn-cancel {
            color: #e2e8f0;
            background: rgba(15,23,42,.68);
            border-color: rgba(255,255,255,.09);
        }

        .dark .feature-input:focus {
            background: rgba(15,23,42,.88);
        }

        .dark .product-context {
            background: rgba(99,91,255,.08);
            border-color: rgba(99,91,255,.16);
        }

        .dark .context-icon,
        .dark .btn-cancel {
            background: rgba(255,255,255,.05);
        }

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

                <div class="feature-page">

                    <!-- =====================================================
                         HERO
                         ===================================================== -->

                    <section class="feature-hero">

                        <div class="feature-hero-content">

                            <div class="hero-left">

                                <div class="hero-icon">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>

                                <div>

                                    <p class="hero-eyebrow">
                                        Product Intelligence
                                    </p>

                                    <h1 class="hero-title">
                                        Add Product Feature
                                    </h1>

                                    <p class="hero-description">
                                        Define a characteristic of this product such as
                                        color, material, size, specification, or any other
                                        attribute your inventory system needs.
                                    </p>

                                </div>

                            </div>

                            <div class="product-id-chip">
                                <i class="fa-solid fa-cube"></i>
                                Product #<?= htmlspecialchars((string)$product_id) ?>
                            </div>

                        </div>

                    </section>


                    <!-- =====================================================
                         WORKSPACE
                         ===================================================== -->

                    <div class="feature-workspace">

                        <!-- =================================================
                             FORM CARD
                             ================================================= -->

                        <section class="feature-card">

                            <div class="card-header">

                                <div class="card-header-row">

                                    <div class="card-title-group">

                                        <div class="mini-icon">
                                            <i class="fa-solid fa-plus"></i>
                                        </div>

                                        <div>
                                            <h2 class="card-title">
                                                Feature Configuration
                                            </h2>

                                            <p class="card-subtitle">
                                                Create an attribute and assign its value.
                                            </p>
                                        </div>

                                    </div>

                                    <div class="status-pill">
                                        <span class="status-dot"></span>
                                        Ready
                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                                 FORM
                                 ================================================= -->

                            <form
                                method="POST"
                                action="create_product_feature.php"
                                class="feature-form"
                            >

                                <!-- IMPORTANT:
                                     product_id comes from the previous page.
                                     No product selector is required.
                                -->

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= htmlspecialchars((string)$product_id) ?>"
                                >


                                <div class="field-grid">

                                    <!-- PRODUCT CONTEXT -->

                                    <div class="field field-full">

                                        <label class="field-label">
                                            Product Context
                                        </label>

                                        <div class="product-context">

                                            <div class="context-icon">
                                                <i class="fa-solid fa-box"></i>
                                            </div>

                                            <div>

                                                <p class="context-label">
                                                    Feature will be attached to
                                                </p>

                                                <p class="context-value">
                                                    Product
                                                    <span>
                                                        #<?= htmlspecialchars((string)$product_id) ?>
                                                    </span>
                                                </p>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- FEATURE NAME -->

                                    <div class="field">

                                        <label
                                            class="field-label"
                                            for="feature_name"
                                        >
                                            Feature Name

                                            <span class="field-required">
                                                Required
                                            </span>
                                        </label>

                                        <div class="field-input-wrap">

                                            <i class="field-icon fa-solid fa-tag"></i>

                                            <input
                                                id="feature_name"
                                                type="text"
                                                name="feature_name"
                                                placeholder="e.g. Color, Material, Size"
                                                autocomplete="off"
                                                required
                                                class="feature-input"
                                            >

                                        </div>

                                    </div>


                                    <!-- FEATURE VALUE -->

                                    <div class="field">

                                        <label
                                            class="field-label"
                                            for="feature_value"
                                        >
                                            Feature Value

                                            <span class="field-required">
                                                Required
                                            </span>
                                        </label>

                                        <div class="field-input-wrap">

                                            <i class="field-icon fa-solid fa-list"></i>

                                            <input
                                                id="feature_value"
                                                type="text"
                                                name="feature_value"
                                                placeholder="e.g. Black, Leather, Large"
                                                autocomplete="off"
                                                required
                                                class="feature-input"
                                            >

                                        </div>

                                    </div>

                                </div>


                                <!-- =================================================
                                     ACTIONS
                                     ================================================= -->

                                <div class="action-bar">

                                    <div class="action-info">

                                        <i class="fa-solid fa-circle-check"></i>

                                        <span>
                                            This feature will be linked to Product #<?= htmlspecialchars((string)$product_id) ?>
                                        </span>

                                    </div>

                                    <div class="action-buttons">

                                        <a
                                            href="products.php"
                                            class="btn btn-cancel"
                                        >
                                            <i class="fa-solid fa-arrow-left"></i>
                                            Cancel
                                        </a>

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            <i class="fa-solid fa-plus"></i>
                                            Add Feature
                                        </button>

                                    </div>

                                </div>

                            </form>

                        </section>


                        <!-- =================================================
                             INFORMATION PANEL
                             ================================================= -->

                        <aside class="info-card">

                            <div class="info-heading">

                                <div class="info-heading-icon">
                                    <i class="fa-solid fa-lightbulb"></i>
                                </div>

                                <div>

                                    <h3>
                                        Feature Intelligence
                                    </h3>

                                    <p>
                                        Keep product data structured
                                    </p>

                                </div>

                            </div>


                            <div class="info-list">

                                <div class="info-item">

                                    <div class="info-number">
                                        01
                                    </div>

                                    <div>
                                        <strong>Feature Name</strong>

                                        <span>
                                            Define what characteristic you are
                                            describing.
                                        </span>
                                    </div>

                                </div>


                                <div class="info-item">

                                    <div class="info-number">
                                        02
                                    </div>

                                    <div>
                                        <strong>Feature Value</strong>

                                        <span>
                                            Enter the specific value associated
                                            with that characteristic.
                                        </span>
                                    </div>

                                </div>


                                <div class="info-item">

                                    <div class="info-number">
                                        03
                                    </div>

                                    <div>
                                        <strong>Product Linking</strong>

                                        <span>
                                            The current product is already
                                            identified by Product ID.
                                        </span>
                                    </div>

                                </div>


                                <div class="info-item">

                                    <div class="info-number">
                                        04
                                    </div>

                                    <div>
                                        <strong>Examples</strong>

                                        <span>
                                            Color → Black<br>
                                            Material → Leather<br>
                                            Size → Large
                                        </span>
                                    </div>

                                </div>

                            </div>

                        </aside>

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
        data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfCacheStatus":true,"cfL4":true,"cfSpeedBrain":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
        crossorigin="anonymous"
    ></script>

</body>

</html>

