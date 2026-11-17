<?php
include('../config/db.php');
include('user_data.php');
include('../company_data.php');

$location_id = $_SESSION['location_id'] ?? null;
$company_id  = $_SESSION['company_id'] ?? null;
$user_id     = $_SESSION['user_id'] ?? null;

if (!$user_id || !$company_id || !$location_id) {
    http_response_code(403);
    exit("Unauthorized access. Please sign in again.");
}

/*
|--------------------------------------------------------------------------
| BRANCH ADMIN ACCESS CONTROL
|--------------------------------------------------------------------------
| The existing database role is "Branch Manager". It is treated as
| Branch Admin in the application; no new role is created.
| The authenticated user, company, active location and staff record must
| all agree. This also prevents cross-company / cross-branch URL access.
|--------------------------------------------------------------------------
*/
$accessStmt = $pdo->prepare("
    SELECT u.email, u.role AS user_role, s.id AS staff_id
    FROM users u
    INNER JOIN staffs s
        ON s.email = u.email
       AND s.company_id = u.company_id
       AND s.location_id = u.location_id
    INNER JOIN locations l
        ON l.id = u.location_id
       AND l.company_id = u.company_id
    WHERE u.id = ?
      AND u.company_id = ?
      AND u.location_id = ?
      AND LOWER(TRIM(COALESCE(u.role, ''))) = 'branch manager'
      AND LOWER(TRIM(COALESCE(s.role, ''))) = 'branch manager'
      AND LOWER(TRIM(COALESCE(u.status, ''))) = 'active'
      AND LOWER(TRIM(COALESCE(s.status, ''))) = 'active'
      AND COALESCE(l.status, 0) = 1
    LIMIT 1
");
$accessStmt->execute([(int)$user_id, (int)$company_id, (int)$location_id]);
$branchAdmin = $accessStmt->fetch(PDO::FETCH_ASSOC);

if (!$branchAdmin) {
    http_response_code(403);
    exit("Access denied. This page is available to Branch Admin accounts only.");
}

$user_email = (string)$branchAdmin['email'];
$staff_id = (int)$branchAdmin['staff_id'];

/*
|--------------------------------------------------------------------------
| CUSTOMERS
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT id, fullname, phone, email
    FROM customers
    WHERE company_id = ?
      AND location_id = ?
    ORDER BY fullname ASC
");

$stmt->execute([$company_id, $location_id]);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| SALES CATEGORIES
|--------------------------------------------------------------------------
*/
$stock_type = "Sales Only";

$stmt = $pdo->prepare("
    SELECT *
    FROM product_categories
    WHERE parent_id IS NULL
      AND company_id = ?
      AND stock_type = ?
    ORDER BY name ASC
");

$stmt->execute([$company_id, $stock_type]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| SALES PRODUCTS (company + active location scoped)
|--------------------------------------------------------------------------
| Image paths are stored as filenames in product_images.image_path.
| The public image directory supplied for this page is ../uploads/products/.
*/
$productStmt = $pdo->prepare("
    SELECT
        p.id, p.name, p.description, p.barcode, p.category_id,
        p.selling_price, p.cost_price, p.has_variants,
        CASE
            WHEN p.identifier_tracking IN ('imei', 'serial', 'vin') THEN p.identifier_tracking
            WHEN COALESCE(p.imei_tracking, 0) = 1 THEN 'imei'
            ELSE 'none'
        END AS identifier_tracking,
        COALESCE(u.unit_name, 'Unit') AS unit_name,
        COALESCE(st.available_quantity, 0) AS quantity,
        pi.image_path
    FROM products p
    LEFT JOIN units u
        ON u.id = p.unit_id AND u.company_id = p.company_id
    LEFT JOIN (
        SELECT product_id, company_id, location_id, SUM(COALESCE(quantity, 0)) AS available_quantity
        FROM stocks
        WHERE company_id = ? AND location_id = ?
        GROUP BY product_id, company_id, location_id
    ) st
        ON st.product_id = p.id
       AND st.company_id = p.company_id
       AND st.location_id = ?
    LEFT JOIN (
        SELECT product_id, MIN(id) AS first_image_id
        FROM product_images
        GROUP BY product_id
    ) first_pi ON first_pi.product_id = p.id
    LEFT JOIN product_images pi ON pi.id = first_pi.first_image_id
    WHERE p.company_id = ?
      AND p.stock_type = 'Sales Only'
    ORDER BY p.name ASC
");
$productStmt->execute([$company_id, $location_id, $location_id, $company_id]);
$salesProducts = $productStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($salesProducts as &$salesProduct) {
    $imageFile = trim((string)($salesProduct['image_path'] ?? ''));
    $salesProduct['image_url'] = $imageFile !== '' ? '../uploads/products/' . rawurlencode(basename($imageFile)) : '';
    $salesProduct['variants'] = [];
}
unset($salesProduct);

// Load active variants and location-specific variant stock from the current schema.
$variantStmt = $pdo->prepare("\n    SELECT v.id, v.product_id, v.variant_name, v.selling_price, v.tracking_policy, v.is_default,\n           COALESCE(vs.quantity, 0) AS quantity\n    FROM product_variants v\n    LEFT JOIN product_variant_stocks vs\n      ON vs.variant_id = v.id AND vs.product_id = v.product_id\n     AND vs.company_id = v.company_id AND vs.location_id = ?\n    WHERE v.company_id = ? AND v.is_active = 1\n    ORDER BY v.product_id, v.is_default DESC, v.variant_name\n");
$variantStmt->execute([$location_id, $company_id]);
$variantsByProduct = [];
while ($variantRow = $variantStmt->fetch(PDO::FETCH_ASSOC)) {
    $variantsByProduct[(string)$variantRow['product_id']][] = [
        'id' => (int)$variantRow['id'], 'variant_name' => (string)$variantRow['variant_name'],
        'selling_price' => $variantRow['selling_price'] === null ? null : (float)$variantRow['selling_price'],
        'tracking_policy' => (string)$variantRow['tracking_policy'], 'is_default' => (int)$variantRow['is_default'],
        'quantity' => (float)$variantRow['quantity'], 'available_identifiers' => []
    ];
}

$identifierInventoryAvailable = true;
$availableIdentifiersByProductVariant = [];
try {
    $identifierStmt = $pdo->prepare("SELECT product_id, variant_id, identifier_type, identifier_value FROM product_serials WHERE company_id = ? AND location_id = ? AND status = 'in_stock' ORDER BY identifier_value ASC");
    $identifierStmt->execute([$company_id, $location_id]);
    while ($identifierRow = $identifierStmt->fetch(PDO::FETCH_ASSOC)) {
        $key = (string)$identifierRow['product_id'] . ':' . (string)($identifierRow['variant_id'] ?? 0) . ':' . (string)$identifierRow['identifier_type'];
        $availableIdentifiersByProductVariant[$key][] = (string)$identifierRow['identifier_value'];
    }
} catch (Throwable $e) {
    $identifierInventoryAvailable = false;
    error_log('Branch Admin sale identifier lookup failed: ' . $e->getMessage());
}

foreach ($salesProducts as &$salesProduct) {
    $pid = (string)$salesProduct['id'];
    $salesProduct['identifier_tracking'] = (string)($salesProduct['identifier_tracking'] ?? 'none');
    $salesProduct['identifier_inventory_available'] = $identifierInventoryAvailable;
    $salesProduct['variants'] = $variantsByProduct[$pid] ?? [];
    foreach ($salesProduct['variants'] as &$variant) {
        $policy = strtolower(trim((string)$variant['tracking_policy']));
        $effective = ($policy === '' || $policy === 'inherit') ? $salesProduct['identifier_tracking'] : $policy;
        $variant['identifier_tracking'] = in_array($effective, ['none','imei','serial','vin'], true) ? $effective : 'none';
        $key = $pid . ':' . (string)$variant['id'] . ':' . $variant['identifier_tracking'];
        $variant['available_identifiers'] = in_array($variant['identifier_tracking'], ['imei','serial','vin'], true) ? ($availableIdentifiersByProductVariant[$key] ?? []) : [];
        if ($variant['identifier_tracking'] !== 'none' && $identifierInventoryAvailable) $variant['quantity'] = min((float)$variant['quantity'], count($variant['available_identifiers']));
    }
    unset($variant);
    $baseKey = $pid . ':0:' . $salesProduct['identifier_tracking'];
    $salesProduct['available_identifiers'] = in_array($salesProduct['identifier_tracking'], ['imei','serial','vin'], true) ? ($availableIdentifiersByProductVariant[$baseKey] ?? []) : [];
    if (empty($salesProduct['has_variants']) && $salesProduct['identifier_tracking'] !== 'none' && $identifierInventoryAvailable) {
        $salesProduct['quantity'] = min((float)$salesProduct['quantity'], count($salesProduct['available_identifiers']));
    }
}
unset($salesProduct);
?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>
        <?= htmlspecialchars($company_name ?? 'Company') ?> | New Sale
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <link href="cart.css" rel="stylesheet">

    <style>

        :root {
            --sale-primary: #5b5ce2;
            --sale-primary-2: #8b5cf6;
            --sale-cyan: #0891b2;
            --sale-green: #059669;
            --sale-orange: #ea580c;
            --sale-red: #dc2626;

            --sale-bg: #f6f8fc;
            --sale-surface: rgba(255,255,255,.72);
            --sale-surface-strong: rgba(255,255,255,.88);
            --sale-border: rgba(148,163,184,.20);
            --sale-border-strong: rgba(99,102,241,.20);

            --sale-text: #172033;
            --sale-muted: #64748b;
            --sale-soft: #f8fafc;

            --sale-shadow:
                0 24px 70px rgba(15,23,42,.075),
                0 5px 18px rgba(15,23,42,.035);

            --sale-shadow-sm:
                0 12px 32px rgba(15,23,42,.055),
                0 2px 8px rgba(15,23,42,.025);

            --sale-radius-xl: 24px;
            --sale-radius-lg: 20px;
            --sale-radius-md: 15px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background:
                radial-gradient(circle at 0% 0%, rgba(99,102,241,.10), transparent 26%),
                radial-gradient(circle at 100% 8%, rgba(6,182,212,.085), transparent 23%),
                radial-gradient(circle at 75% 100%, rgba(139,92,246,.055), transparent 25%),
                var(--sale-bg);
            color: var(--sale-text);
        }

        .sales-page {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }

        /* =========================================================
           ALERTS
        ========================================================= */

        .sale-alert {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 17px;
            margin-bottom: 18px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 750;
            box-shadow: var(--sale-shadow-sm);
            overflow: hidden;
            animation: saleSlideDown .45s ease both;
        }

        .sale-alert::before {
            content: "";
            width: 4px;
            align-self: stretch;
            border-radius: 999px;
            background: currentColor;
            opacity: .7;
        }

        .sale-alert i {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 30px;
            border-radius: 10px;
            background: rgba(255,255,255,.68);
            box-shadow: 0 5px 15px rgba(15,23,42,.05);
        }

        .sale-alert-success {
            color: #047857;
            background: rgba(236,253,245,.78);
            border: 1px solid rgba(16,185,129,.20);
            backdrop-filter: blur(14px);
        }

        .sale-alert-danger {
            color: #b91c1c;
            background: rgba(254,242,242,.82);
            border: 1px solid rgba(239,68,68,.20);
            backdrop-filter: blur(14px);
        }

        /* =========================================================
           PAGE HERO
        ========================================================= */

        .sale-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            padding: 27px;
            margin-bottom: 20px;
            border-radius: var(--sale-radius-xl);
            border: 1px solid rgba(255,255,255,.92);
            background:
                linear-gradient(135deg,
                    rgba(255,255,255,.92),
                    rgba(255,255,255,.67));
            box-shadow: var(--sale-shadow);
            backdrop-filter: blur(24px) saturate(125%);
            -webkit-backdrop-filter: blur(24px) saturate(125%);
        }

        .sale-hero::before {
            content: "";
            position: absolute;
            z-index: -1;
            width: 360px;
            height: 360px;
            right: -135px;
            top: -195px;
            border-radius: 50%;
            background: radial-gradient(circle,
                rgba(99,102,241,.16),
                rgba(99,102,241,.035) 55%,
                transparent 72%);
            pointer-events: none;
        }

        .sale-hero::after {
            content: "";
            position: absolute;
            z-index: -1;
            width: 230px;
            height: 230px;
            right: 18%;
            bottom: -165px;
            border-radius: 50%;
            background: radial-gradient(circle,
                rgba(6,182,212,.11),
                transparent 70%);
            pointer-events: none;
        }

        .sale-hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .sale-hero-title {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .sale-hero-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 60px;
            position: relative;
            border-radius: 18px;
            color: #fff;
            font-size: 21px;
            background: linear-gradient(135deg,
                var(--sale-primary),
                var(--sale-primary-2));
            box-shadow:
                0 16px 32px rgba(91,92,226,.22),
                inset 0 1px 0 rgba(255,255,255,.32);
        }

        .sale-hero-icon::after {
            content: "";
            position: absolute;
            inset: 1px;
            border-radius: inherit;
            border: 1px solid rgba(255,255,255,.20);
            pointer-events: none;
        }

        .sale-hero h1 {
            margin: 0;
            color: var(--sale-text);
            font-size: clamp(21px, 2vw, 26px);
            line-height: 1.15;
            font-weight: 850;
            letter-spacing: -.03em;
        }

        .sale-hero p {
            margin: 6px 0 0;
            color: var(--sale-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .sale-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            color: #047857;
            background: rgba(236,253,245,.72);
            border: 1px solid rgba(16,185,129,.22);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .sale-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 4px rgba(16,185,129,.12);
            animation: salePulse 2s infinite;
        }

        /* =========================================================
           SALE WORKSPACE
        ========================================================= */

        .sale-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 370px;
            gap: 20px;
            align-items: start;
        }

        .sale-card {
            position: relative;
            margin-bottom: 20px;
            padding: 23px;
            border-radius: var(--sale-radius-lg);
            border: 1px solid var(--sale-border);
            background: var(--sale-surface);
            box-shadow: var(--sale-shadow-sm);
            backdrop-filter: blur(20px) saturate(120%);
            -webkit-backdrop-filter: blur(20px) saturate(120%);
            overflow: hidden;
        }

        .sale-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg,
                transparent,
                rgba(255,255,255,.95),
                transparent);
            pointer-events: none;
        }

        .sale-card:last-child {
            margin-bottom: 0;
        }

        .sale-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .sale-card-title {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
        }

        .sale-card-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 40px;
            border-radius: 12px;
            color: var(--sale-primary);
            background: linear-gradient(145deg,
                rgba(238,242,255,.92),
                rgba(224,231,255,.62));
            border: 1px solid rgba(99,102,241,.10);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.9),
                0 7px 18px rgba(79,70,229,.07);
        }

        .sale-card-icon i {
            font-size: 14px;
        }

        .sale-card-title h2 {
            margin: 0;
            color: var(--sale-text);
            font-size: 16px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -.015em;
        }

        .sale-card-title p {
            margin: 3px 0 0;
            color: var(--sale-muted);
            font-size: 11px;
            line-height: 1.4;
        }

        /* =========================================================
           CUSTOMER TYPE
        ========================================================= */

        .customer-type-box {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 11px;
            margin-bottom: 20px;
        }

        .customer-type-option,
        .payment-option {
            position: relative;
        }

        .customer-type-option input,
        .payment-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .customer-type-label {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 70px;
            padding: 14px;
            border-radius: 15px;
            border: 1px solid rgba(148,163,184,.20);
            background: rgba(248,250,252,.68);
            cursor: pointer;
            transition: transform .22s ease,
                        border-color .22s ease,
                        box-shadow .22s ease,
                        background .22s ease;
        }

        .customer-type-label::after,
        .payment-option label::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.85);
        }

        .customer-type-label:hover,
        .payment-option label:hover {
            border-color: rgba(79,70,229,.28);
            transform: translateY(-1px);
            box-shadow: 0 9px 22px rgba(15,23,42,.045);
        }

        .customer-radio-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 40px;
            border-radius: 12px;
            color: var(--sale-primary);
            background: rgba(238,242,255,.86);
            border: 1px solid rgba(99,102,241,.10);
        }

        .customer-type-text {
            min-width: 0;
        }

        .customer-type-text strong {
            display: block;
            color: var(--sale-text);
            font-size: 12px;
            font-weight: 800;
        }

        .customer-type-text span {
            display: block;
            margin-top: 3px;
            color: var(--sale-muted);
            font-size: 10px;
            line-height: 1.4;
        }

        .customer-type-option input:checked + .customer-type-label {
            border-color: rgba(79,70,229,.42);
            background: linear-gradient(135deg,
                rgba(238,242,255,.92),
                rgba(255,255,255,.76));
            box-shadow:
                0 10px 26px rgba(79,70,229,.08),
                inset 0 0 0 1px rgba(99,102,241,.035);
        }

        .customer-type-option input:checked + .customer-type-label
        .customer-radio-icon {
            color: #fff;
            background: linear-gradient(135deg,
                var(--sale-primary),
                var(--sale-primary-2));
            box-shadow: 0 8px 18px rgba(79,70,229,.18);
        }

        /* =========================================================
           FORM
        ========================================================= */

        .sale-form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0,1fr));
            gap: 15px;
        }

        .sale-field {
            min-width: 0;
        }

        .sale-field-12 { grid-column: span 12; }
        .sale-field-6 { grid-column: span 6; }
        .sale-field-4 { grid-column: span 4; }
        .sale-field-3 { grid-column: span 3; }
        .sale-field-2 { grid-column: span 2; }

        .sale-label {
            display: flex;
            align-items: center;
            gap: 6px;
            min-height: 16px;
            margin-bottom: 7px;
            color: #475569;
            font-size: 11px;
            font-weight: 750;
            letter-spacing: .01em;
        }

        .sale-label i {
            width: 15px;
            color: var(--sale-primary);
            font-size: 10px;
            text-align: center;
        }

        .sale-input,
        .sale-select,
        .sale-textarea {
            width: 100%;
            border: 1px solid rgba(148,163,184,.30);
            border-radius: 13px;
            background: rgba(255,255,255,.78);
            color: var(--sale-text);
            font-size: 13px;
            outline: none;
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                transform .2s ease;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.82);
        }

        .sale-input,
        .sale-select {
            height: 46px;
            padding: 0 13px;
        }

        .sale-textarea {
            min-height: 100px;
            padding: 12px 13px;
            resize: vertical;
            line-height: 1.55;
        }

        .sale-input::placeholder,
        .sale-textarea::placeholder {
            color: #94a3b8;
        }

        .sale-input:hover,
        .sale-select:hover,
        .sale-textarea:hover {
            border-color: rgba(100,116,139,.35);
        }

        .sale-input:focus,
        .sale-select:focus,
        .sale-textarea:focus {
            border-color: rgba(79,70,229,.48);
            background: rgba(255,255,255,.94);
            box-shadow:
                0 0 0 4px rgba(79,70,229,.075),
                0 8px 20px rgba(15,23,42,.035);
        }

        .sale-input[readonly] {
            background: rgba(248,250,252,.78);
            color: #475569;
            cursor: default;
        }

        /* =========================================================
           CUSTOMER PANELS
        ========================================================= */

        .customer-panel {
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 16px;
            border: 1px solid rgba(148,163,184,.16);
            background:
                linear-gradient(135deg,
                    rgba(248,250,252,.65),
                    rgba(255,255,255,.54));
        }

        /* =========================================================
           PRODUCT BUILDER
        ========================================================= */

        .product-builder {
            position: relative;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid rgba(79,70,229,.13);
            background:
                linear-gradient(135deg,
                    rgba(238,242,255,.72),
                    rgba(255,255,255,.76));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.9),
                0 10px 28px rgba(79,70,229,.035);
        }

        .builder-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 15px;
            color: var(--sale-text);
            font-size: 12px;
            font-weight: 800;
        }

        .builder-title i {
            width: 29px;
            height: 29px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            color: var(--sale-primary);
            background: rgba(255,255,255,.72);
            border: 1px solid rgba(99,102,241,.10);
        }

        .add-cart-btn {
            width: 100%;
            min-height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 13px;
            color: #fff;
            background:
                linear-gradient(135deg,
                    var(--sale-primary),
                    var(--sale-primary-2));
            box-shadow:
                0 12px 25px rgba(79,70,229,.20),
                inset 0 1px 0 rgba(255,255,255,.20);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s ease,
                        box-shadow .2s ease,
                        filter .2s ease;
        }

        .add-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow:
                0 16px 30px rgba(79,70,229,.24),
                inset 0 1px 0 rgba(255,255,255,.22);
            filter: saturate(1.06);
        }

        .add-cart-btn:active {
            transform: translateY(0);
        }

        .add-cart-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }

        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 7px;
            min-height: 16px;
            margin-top: 6px;
            color: var(--sale-muted);
            font-size: 10px;
        }

        .stock-indicator i {
            color: var(--sale-cyan);
        }

        /* =========================================================
           CART
        ========================================================= */

        .cart-section {
            margin-top: 21px;
        }

        .cart-table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid rgba(148,163,184,.16);
            background: rgba(255,255,255,.48);
            -webkit-overflow-scrolling: touch;
        }

        #cartTable {
            width: 100%;
            min-width: 620px;
            border-collapse: separate;
            border-spacing: 0;
        }

        #cartTable th {
            padding: 13px;
            color: #64748b;
            background: rgba(248,250,252,.84);
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            text-align: left;
            white-space: nowrap;
        }

        #cartTable th:first-child {
            border-top-left-radius: 15px;
        }

        #cartTable th:last-child {
            border-top-right-radius: 15px;
        }

        #cartTable th:not(:first-child),
        #cartTable td:not(:first-child) {
            text-align: center;
        }

        #cartTable td {
            padding: 13px;
            color: var(--sale-text);
            background: rgba(255,255,255,.48);
            border-bottom: 1px solid #eef2f7;
            font-size: 12px;
        }

        #cartTable tbody tr {
            transition: background .18s ease;
        }

        #cartTable tbody tr:hover td {
            background: rgba(248,250,255,.82);
        }

        #cartTable tbody tr:last-child td {
            border-bottom: 0;
        }

        .btn-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: 1px solid rgba(239,68,68,.12);
            border-radius: 10px;
            color: #dc2626;
            background: rgba(254,242,242,.80);
            cursor: pointer;
            font-size: 11px;
            transition: transform .18s ease,
                        background .18s ease,
                        box-shadow .18s ease;
        }

        .btn-remove:hover {
            transform: translateY(-1px);
            background: #fff;
            box-shadow: 0 7px 16px rgba(220,38,38,.10);
        }

        #cartTable tfoot td {
            padding: 13px;
            background: rgba(250,251,255,.78);
            font-weight: 750;
        }

        .cart-total-label {
            color: var(--sale-muted);
            font-size: 11px;
        }

        .cart-total-value {
            color: var(--sale-text);
            font-size: 14px;
            font-weight: 850;
        }

        .cart-final-row td {
            color: var(--sale-primary) !important;
            background:
                linear-gradient(90deg,
                    rgba(238,242,255,.94),
                    rgba(245,243,255,.82)) !important;
            border-top: 1px solid rgba(99,102,241,.10);
            font-size: 14px !important;
            font-weight: 900 !important;
        }

        #discount {
            width: 100%;
            max-width: 120px;
            height: 36px;
            border: 1px solid rgba(148,163,184,.28);
            border-radius: 10px;
            padding: 0 9px;
            outline: none;
            background: rgba(255,255,255,.80);
            color: var(--sale-text);
            text-align: center;
            transition: .2s ease;
        }

        #discount:focus {
            border-color: rgba(79,70,229,.45);
            box-shadow: 0 0 0 4px rgba(79,70,229,.07);
        }

        /* =========================================================
           RIGHT SUMMARY
        ========================================================= */

        .sale-summary {
            position: sticky;
            top: 20px;
            min-width: 0;
        }

        .summary-total {
            position: relative;
            overflow: hidden;
            padding: 21px;
            margin-bottom: 13px;
            border-radius: 18px;
            color: #fff;
            background:
                radial-gradient(circle at 100% 0%, rgba(255,255,255,.17), transparent 35%),
                linear-gradient(135deg,
                    #5657df,
                    #7c3aed);
            box-shadow:
                0 20px 42px rgba(79,70,229,.21),
                inset 0 1px 0 rgba(255,255,255,.22);
        }

        .summary-total::after {
            content: "";
            position: absolute;
            width: 130px;
            height: 130px;
            right: -55px;
            bottom: -70px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.13);
            box-shadow: 0 0 0 18px rgba(255,255,255,.025);
        }

        .summary-total small {
            position: relative;
            z-index: 1;
            display: block;
            opacity: .78;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .08em;
        }

        .summary-total strong {
            position: relative;
            z-index: 1;
            display: block;
            margin-top: 5px;
            font-size: 29px;
            line-height: 1.15;
            font-weight: 900;
            letter-spacing: -.035em;
            overflow-wrap: anywhere;
        }

        .summary-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 46px;
            padding: 11px 0;
            border-bottom: 1px solid rgba(148,163,184,.13);
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .summary-label {
            color: var(--sale-muted);
            font-size: 11px;
        }

        .summary-value {
            color: var(--sale-text);
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            overflow-wrap: anywhere;
        }

        /* =========================================================
           PAYMENT
        ========================================================= */

        .payment-status-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 10px;
        }

        .payment-option label {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 67px;
            padding: 13px;
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 14px;
            background: rgba(248,250,252,.68);
            cursor: pointer;
            transition: transform .2s ease,
                        border-color .2s ease,
                        background .2s ease,
                        box-shadow .2s ease;
        }

        .payment-option input:checked + label {
            border-color: rgba(79,70,229,.40);
            background:
                linear-gradient(135deg,
                    rgba(238,242,255,.92),
                    rgba(255,255,255,.78));
            box-shadow:
                0 9px 24px rgba(79,70,229,.075),
                inset 0 1px 0 rgba(255,255,255,.88);
        }

        .payment-option-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 36px;
            border-radius: 11px;
            color: var(--sale-primary);
            background: rgba(255,255,255,.82);
            border: 1px solid rgba(99,102,241,.10);
            box-shadow: 0 5px 12px rgba(15,23,42,.045);
        }

        .payment-option input:checked + label .payment-option-icon {
            color: #fff;
            background: linear-gradient(135deg,
                var(--sale-primary),
                var(--sale-primary-2));
        }

        .payment-option strong {
            display: block;
            color: var(--sale-text);
            font-size: 11px;
            font-weight: 800;
        }

        .payment-option span {
            display: block;
            margin-top: 3px;
            color: var(--sale-muted);
            font-size: 9px;
            line-height: 1.35;
        }

        .partial-box {
            margin-top: 13px;
            padding: 15px;
            border-radius: 15px;
            border: 1px solid rgba(234,88,12,.14);
            background:
                linear-gradient(135deg,
                    rgba(255,247,237,.88),
                    rgba(255,255,255,.60));
        }

        /* =========================================================
           SUBMIT
        ========================================================= */

        .sale-submit-area {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .complete-sale-btn,
        .cancel-sale-btn {
            min-height: 47px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 13px;
            padding: 0 18px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: transform .2s ease,
                        box-shadow .2s ease,
                        background .2s ease;
        }

        .complete-sale-btn {
            flex: 1;
            border: 0;
            color: #fff;
            background:
                linear-gradient(135deg,
                    #059669,
                    #047857);
            box-shadow:
                0 12px 26px rgba(5,150,105,.19),
                inset 0 1px 0 rgba(255,255,255,.20);
        }

        .cancel-sale-btn {
            border: 1px solid rgba(148,163,184,.28);
            color: #475569;
            background: rgba(255,255,255,.78);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.9);
        }

        .complete-sale-btn:hover,
        .cancel-sale-btn:hover {
            transform: translateY(-2px);
        }

        .complete-sale-btn:hover {
            box-shadow:
                0 16px 30px rgba(5,150,105,.23),
                inset 0 1px 0 rgba(255,255,255,.22);
        }

        .cancel-sale-btn:hover {
            box-shadow: 0 9px 20px rgba(15,23,42,.06);
        }

        .complete-sale-btn:disabled {
            opacity: .72;
            cursor: wait;
            transform: none;
        }

        /* =========================================================
           ACCESSIBILITY / FOCUS
        ========================================================= */

        .sale-input:focus-visible,
        .sale-select:focus-visible,
        .sale-textarea:focus-visible,
        .add-cart-btn:focus-visible,
        .complete-sale-btn:focus-visible,
        .cancel-sale-btn:focus-visible,
        .btn-remove:focus-visible,
        .customer-type-label:focus-visible,
        .payment-option label:focus-visible {
            outline: 3px solid rgba(79,70,229,.16);
            outline-offset: 2px;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1250px) {
            .sale-workspace {
                grid-template-columns: minmax(0,1fr) 330px;
            }

            .sale-card {
                padding: 20px;
            }
        }

        @media (max-width: 1050px) {
            .sale-workspace {
                grid-template-columns: 1fr;
            }

            .sale-summary {
                position: static;
            }

            .sale-summary .sale-card {
                margin-bottom: 16px;
            }

            .sale-summary {
                display: grid;
                grid-template-columns: repeat(2, minmax(0,1fr));
                gap: 16px;
            }

            .sale-summary .sale-card {
                margin-bottom: 0;
            }
        }

        @media (max-width: 800px) {
            .sale-hero-content {
                align-items: flex-start;
                flex-direction: column;
            }

            .sale-status {
                align-self: flex-start;
            }

            .sale-field-6,
            .sale-field-4,
            .sale-field-3 {
                grid-column: span 6;
            }

            .sale-field-2 {
                grid-column: span 3;
            }

            .sale-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            body {
                background:
                    radial-gradient(circle at 0% 0%, rgba(99,102,241,.08), transparent 32%),
                    var(--sale-bg);
            }

            .sales-page {
                width: 100%;
            }

            .sale-hero {
                padding: 19px;
                margin-bottom: 15px;
                border-radius: 19px;
            }

            .sale-hero-title {
                gap: 12px;
                align-items: flex-start;
            }

            .sale-hero-icon {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
                border-radius: 14px;
                font-size: 17px;
            }

            .sale-hero h1 {
                font-size: 20px;
            }

            .sale-hero p {
                font-size: 11px;
            }

            .sale-status {
                width: 100%;
                justify-content: center;
            }

            .sale-card {
                padding: 16px;
                margin-bottom: 15px;
                border-radius: 17px;
            }

            .sale-card-header {
                margin-bottom: 16px;
            }

            .sale-card-icon {
                width: 36px;
                height: 36px;
                flex-basis: 36px;
                border-radius: 10px;
            }

            .sale-card-title h2 {
                font-size: 15px;
            }

            .sale-card-title p {
                font-size: 10px;
            }

            .customer-type-box,
            .payment-status-grid {
                grid-template-columns: 1fr;
            }

            .customer-type-label {
                min-height: 64px;
            }

            .sale-form-grid {
                gap: 12px;
            }

            .sale-field-6,
            .sale-field-4,
            .sale-field-3,
            .sale-field-2 {
                grid-column: span 12;
            }

            .sale-input,
            .sale-select {
                height: 45px;
            }

            .product-builder {
                padding: 14px;
            }

            .cart-table-wrapper {
                margin-left: -2px;
                margin-right: -2px;
            }

            #cartTable {
                min-width: 590px;
            }

            .sale-submit-area {
                flex-direction: column-reverse;
            }

            .cancel-sale-btn,
            .complete-sale-btn {
                width: 100%;
            }

            .summary-total {
                padding: 18px;
            }

            .summary-total strong {
                font-size: 24px;
            }
        }

        @media (max-width: 380px) {
            .sale-hero,
            .sale-card {
                border-radius: 15px;
            }

            .sale-hero-title {
                gap: 10px;
            }

            .sale-hero h1 {
                font-size: 18px;
            }

            .sale-card {
                padding: 14px;
            }

            .customer-type-label,
            .payment-option label {
                padding: 11px;
            }

            .customer-radio-icon {
                width: 36px;
                height: 36px;
                flex-basis: 36px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }

        @keyframes saleSlideDown {
            from {
                opacity: 0;
                transform: translateY(-7px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes salePulse {
            0%, 100% {
                box-shadow: 0 0 0 4px rgba(16,185,129,.12);
            }
            50% {
                box-shadow: 0 0 0 7px rgba(16,185,129,.035);
            }
        }



        /* Searchable sales selectors + product imagery */
        .sale-native-select-hidden { display:none !important; }
        .sale-search-wrap { position:relative; width:100%; }
        .sale-search-input { width:100%; min-height:48px; border:1px solid rgba(148,163,184,.28); border-radius:13px; padding:12px 42px 12px 14px; background:rgba(255,255,255,.88); color:#172033; outline:none; font:inherit; box-shadow:0 5px 18px rgba(15,23,42,.035); transition:.2s ease; }
        .sale-search-input:focus { border-color:rgba(91,92,226,.62); box-shadow:0 0 0 4px rgba(91,92,226,.10); }
        .sale-search-caret { position:absolute; right:15px; top:50%; transform:translateY(-50%); color:#64748b; pointer-events:none; }
        .sale-search-menu { display:none; position:fixed; z-index:2147483000; left:0; top:0; width:auto; max-height:min(360px, calc(100vh - 24px)); overflow-y:auto; overflow-x:hidden; overscroll-behavior:contain; -webkit-overflow-scrolling:touch; padding:7px; border:1px solid rgba(148,163,184,.25); border-radius:15px; background:rgba(255,255,255,.98); box-shadow:0 20px 55px rgba(15,23,42,.16); backdrop-filter:blur(18px); }
        .sale-search-menu.is-open { display:block; animation:saleSlideDown .16s ease; }
        .sale-search-option { display:flex; align-items:center; gap:11px; width:100%; border:0; border-radius:10px; padding:9px; text-align:left; background:transparent; color:#172033; cursor:pointer; transition:background .15s ease; }
        .sale-search-option:hover,.sale-search-option:focus { background:#f1f5ff; outline:none; }
        .sale-search-option img,.sale-product-thumb { width:43px; height:43px; flex:0 0 43px; object-fit:cover; border-radius:10px; background:#eef2f7; border:1px solid rgba(148,163,184,.18); }
        .sale-search-option .option-copy { min-width:0; flex:1; }
        .sale-search-option strong { display:block; font-size:13px; line-height:1.35; overflow-wrap:anywhere; }
        .sale-search-option small { display:block; margin-top:3px; color:#64748b; font-size:11px; }
        .sale-search-empty { padding:15px 12px; color:#64748b; font-size:13px; text-align:center; }
        .sale-product-preview { display:none; align-items:center; gap:14px; margin-top:12px; padding:13px; border:1px solid rgba(148,163,184,.20); border-radius:15px; background:linear-gradient(135deg,rgba(255,255,255,.92),rgba(248,250,252,.82)); box-shadow:0 8px 25px rgba(15,23,42,.045); }
        .sale-product-preview.is-visible { display:flex; }
        .sale-product-preview img { width:76px; height:76px; flex:0 0 76px; object-fit:cover; border-radius:13px; background:#eef2f7; }
        .sale-product-preview-copy { min-width:0; }
        .sale-product-preview-copy strong { display:block; color:#172033; font-size:14px; }
        .sale-product-preview-copy p { margin:4px 0 0; color:#64748b; font-size:12px; line-height:1.5; overflow-wrap:anywhere; }
        .cart-product-cell { display:flex; align-items:center; gap:10px; min-width:180px; }
        .cart-product-cell .sale-product-thumb { width:46px; height:46px; flex-basis:46px; }
        .cart-product-cell strong { display:block; }
        @media(max-width:640px) { .sale-search-menu { max-height:min(300px, calc(100vh - 24px)); } .sale-product-preview { align-items:flex-start; } .sale-product-preview img { width:62px;height:62px;flex-basis:62px; } .cart-product-cell { min-width:150px; } }



        /* Identifier selection dialog for IMEI-, serial-, and VIN-tracked devices. */
        .imei-picker-backdrop{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.58);backdrop-filter:blur(7px)}
        .imei-picker-backdrop.is-open{display:flex}
        .imei-picker{width:min(620px,100%);max-height:min(82vh,760px);display:flex;flex-direction:column;overflow:hidden;border:1px solid rgba(255,255,255,.8);border-radius:22px;background:#fff;box-shadow:0 30px 90px rgba(15,23,42,.28)}
        .imei-picker-head{padding:20px 22px;border-bottom:1px solid #e8edf5;display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
        .imei-picker-head h3{margin:0;color:#172033;font-size:17px;font-weight:850}.imei-picker-head p{margin:5px 0 0;color:#64748b;font-size:12px}
        .imei-picker-close{width:34px;height:34px;border:0;border-radius:10px;background:#f1f5f9;color:#334155;cursor:pointer}
        .imei-picker-tools{padding:14px 22px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #edf1f6}
        .imei-picker-search{width:100%;height:42px;padding:0 12px;border:1px solid #dbe3ef;border-radius:11px;outline:none;color:#172033}
        .imei-picker-count{white-space:nowrap;padding:8px 11px;border-radius:10px;background:#eef2ff;color:#4338ca;font-size:11px;font-weight:800}
        .imei-picker-list{padding:12px 22px;overflow:auto;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;min-height:120px}
        .imei-picker-option{display:flex;align-items:center;gap:9px;padding:11px;border:1px solid #e2e8f0;border-radius:11px;background:#fbfdff;color:#334155;font-size:12px;font-weight:700;cursor:pointer;min-width:0}
        .imei-picker-option input{accent-color:#4f46e5;width:16px;height:16px;flex:0 0 16px}.imei-picker-option span{overflow-wrap:anywhere}
        .imei-picker-option:has(input:checked){border-color:#818cf8;background:#eef2ff}
        .imei-picker-empty{grid-column:1/-1;padding:28px 10px;text-align:center;color:#64748b;font-size:13px}
        .imei-picker-foot{padding:15px 22px;border-top:1px solid #e8edf5;display:flex;justify-content:flex-end;gap:10px}
        .imei-picker-btn{min-height:42px;padding:0 17px;border:0;border-radius:11px;font-size:12px;font-weight:800;cursor:pointer}
        .imei-picker-cancel{background:#f1f5f9;color:#334155}.imei-picker-confirm{background:#3730a3;color:#fff}.imei-picker-confirm:disabled{opacity:.45;cursor:not-allowed}
        .cart-imei-note{display:block;margin-top:4px;color:#64748b;font-size:10px;line-height:1.45;overflow-wrap:anywhere}
        @media(max-width:560px){.imei-picker-list{grid-template-columns:1fr}.imei-picker-tools{align-items:stretch;flex-direction:column}.imei-picker-head,.imei-picker-tools,.imei-picker-list,.imei-picker-foot{padding-left:15px;padding-right:15px}}
    </style>

</head>


<body
    x-data="{ page: 'ecommerce', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>

            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                <div class="sales-page">

                    <!-- =====================================================
                         ALERTS
                    ====================================================== -->

                    <?php if (!empty($_SESSION['sale_success'])): ?>

                        <div class="sale-alert sale-alert-success">
                            <i class="fa-solid fa-circle-check"></i>

                            <span>
                                <?= htmlspecialchars($_SESSION['sale_success']); ?>
                            </span>

                            <?php unset($_SESSION['sale_success']); ?>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($_SESSION['sale_error'])): ?>

                        <div class="sale-alert sale-alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i>

                            <span>
                                <?= htmlspecialchars($_SESSION['sale_error']); ?>
                            </span>

                            <?php unset($_SESSION['sale_error']); ?>

                        </div>

                    <?php endif; ?>


                    <!-- =====================================================
                         HERO
                    ====================================================== -->

                    <section class="sale-hero">

                        <div class="sale-hero-content">

                            <div class="sale-hero-title">

                                <div class="sale-hero-icon">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </div>

                                <div>

                                    <h1>Create New Sale</h1>

                                    <p>
                                        Build a transaction, manage payment and complete the sale.
                                    </p>

                                </div>

                            </div>

                            <div class="sale-status">

                                <span class="sale-status-dot"></span>

                                Sales Terminal Ready

                            </div>

                        </div>

                    </section>


                    <!-- =====================================================
                         WORKSPACE
                    ====================================================== -->

                    <div class="sale-workspace">

                        <!-- =================================================
                             MAIN FORM
                        ================================================== -->

                        <div>

                            <form
                                id="saleForm"
                                method="POST"
                                action="create_sale.php"
                            >

                                <!-- CUSTOMER -->

                                <section class="sale-card">

                                    <div class="sale-card-header">

                                        <div class="sale-card-title">

                                            <div class="sale-card-icon">
                                                <i class="fa-solid fa-user-group"></i>
                                            </div>

                                            <div>
                                                <h2>Customer</h2>
                                                <p>Select or create the customer for this transaction</p>
                                            </div>

                                        </div>

                                    </div>


                                    <div class="customer-type-box">

                                        <div class="customer-type-option">

                                            <input
                                                type="radio"
                                                name="customer_type"
                                                value="new"
                                                checked
                                                id="newCustomerRadio"
                                            >

                                            <label
                                                for="newCustomerRadio"
                                                class="customer-type-label"
                                            >

                                                <div class="customer-radio-icon">
                                                    <i class="fa-solid fa-user-plus"></i>
                                                </div>

                                                <div class="customer-type-text">

                                                    <strong>New Customer</strong>

                                                    <span>
                                                        Create customer details for this sale
                                                    </span>

                                                </div>

                                            </label>

                                        </div>


                                        <div class="customer-type-option">

                                            <input
                                                type="radio"
                                                name="customer_type"
                                                value="existing"
                                                id="existingCustomerRadio"
                                            >

                                            <label
                                                for="existingCustomerRadio"
                                                class="customer-type-label"
                                            >

                                                <div class="customer-radio-icon">
                                                    <i class="fa-solid fa-address-book"></i>
                                                </div>

                                                <div class="customer-type-text">

                                                    <strong>Existing Customer</strong>

                                                    <span>
                                                        Use a registered customer
                                                    </span>

                                                </div>

                                            </label>

                                        </div>

                                    </div>


                                    <!-- NEW CUSTOMER -->

                                    <div
                                        id="newCustomerFields"
                                        class="customer-panel"
                                    >

                                        <div class="sale-form-grid">

                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-user"></i>
                                                    Customer Name
                                                </label>

                                                <input
                                                    type="text"
                                                    name="customer_name"
                                                    id="customer_name"
                                                    placeholder="e.g. John Doe"
                                                    class="sale-input"
                                                >

                                            </div>


                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-phone"></i>
                                                    Customer Mobile
                                                </label>

                                                <input
                                                    type="tel"
                                                    name="customer_phone"
                                                    id="customer_phone"
                                                    placeholder="e.g. 08012345678"
                                                    class="sale-input"
                                                >

                                            </div>


                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-envelope"></i>
                                                    Customer Email
                                                </label>

                                                <input
                                                    type="email"
                                                    name="customer_email_new"
                                                    id="customer_email_new"
                                                    placeholder="e.g. customer@example.com"
                                                    class="sale-input"
                                                >

                                            </div>

                                        </div>

                                    </div>


                                    <!-- EXISTING CUSTOMER -->

                                    <div
                                        id="existingCustomerFields"
                                        class="customer-panel"
                                        style="display:none;"
                                    >

                                        <div class="sale-form-grid">

                                            <div class="sale-field sale-field-6">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-address-book"></i>
                                                    Select Existing Customer
                                                </label>

                                                <div class="sale-search-wrap" id="customerSearchWrap">
                                                    <input
                                                        type="search"
                                                        id="customerSearchInput"
                                                        class="sale-search-input"
                                                        placeholder="Search customers by name, phone or email..."
                                                        autocomplete="off"
                                                        aria-label="Search existing customers"
                                                        aria-expanded="false"
                                                        aria-controls="customerSearchMenu"
                                                    >
                                                    <i class="fa-solid fa-chevron-down sale-search-caret"></i>
                                                    <div class="sale-search-menu" id="customerSearchMenu" role="listbox" aria-label="Matching customers"></div>
                                                </div>

                                                <select
                                                    id="existingCustomerSelect"
                                                    class="sale-select sale-native-select-hidden"
                                                    aria-hidden="true"
                                                    tabindex="-1"
                                                >

                                                    <option value="">
                                                        -- Choose Customer --
                                                    </option>

                                                    <?php foreach ($customers as $cus): ?>

                                                        <option
                                                            value="<?= htmlspecialchars($cus['id']) ?>"
                                                            data-name="<?= htmlspecialchars($cus['fullname']) ?>"
                                                            data-phone="<?= htmlspecialchars($cus['phone']) ?>"
                                                            data-email="<?= htmlspecialchars($cus['email']) ?>"
                                                        >
                                                            <?= htmlspecialchars($cus['fullname']) ?>
                                                            —
                                                            <?= htmlspecialchars($cus['phone']) ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </div>


                                            <div class="sale-field sale-field-3">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-user"></i>
                                                    Name
                                                </label>

                                                <input
                                                    type="text"
                                                    id="ex_name"
                                                    readonly
                                                    class="sale-input"
                                                >

                                            </div>


                                            <div class="sale-field sale-field-3">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-phone"></i>
                                                    Phone
                                                </label>

                                                <input
                                                    type="tel"
                                                    id="ex_phone"
                                                    readonly
                                                    class="sale-input"
                                                >

                                            </div>


                                            <div class="sale-field sale-field-6">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-envelope"></i>
                                                    Email
                                                </label>

                                                <input
                                                    type="email"
                                                    id="ex_email"
                                                    readonly
                                                    class="sale-input"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="customer_email"
                                                    id="hidden_customer_email"
                                                >

                                            </div>

                                        </div>


                                        <input
                                            type="hidden"
                                            name="existing_customer_id"
                                            id="customer_id"
                                        >

                                    </div>

                                </section>


                                <!-- PRODUCT BUILDER -->

                                <section class="sale-card">

                                    <div class="sale-card-header">

                                        <div class="sale-card-title">

                                            <div class="sale-card-icon">
                                                <i class="fa-solid fa-box-open"></i>
                                            </div>

                                            <div>
                                                <h2>Add Products</h2>
                                                <p>Select products and build the customer's cart</p>
                                            </div>

                                        </div>

                                    </div>


                                    <div class="product-builder">

                                        <div class="builder-title">

                                            <i class="fa-solid fa-wand-magic-sparkles"></i>

                                            Product Line Builder

                                        </div>


                                        <div class="sale-form-grid">

                                            <!-- CATEGORY -->

                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-layer-group"></i>
                                                    Category
                                                </label>

                                                <div class="sale-search-wrap" id="categorySearchWrap">
                                                    <input type="search" id="categorySearchInput" class="sale-search-input" placeholder="Search or select a category..." autocomplete="off" aria-label="Search categories" aria-expanded="false">
                                                    <i class="fa-solid fa-chevron-down sale-search-caret"></i>
                                                    <div class="sale-search-menu" id="categorySearchMenu" role="listbox">
                                                        <?php foreach ($categories as $cat): ?>
                                                            <button type="button" class="sale-search-option category-search-option" data-id="<?= (int)$cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <span class="option-copy"><strong><?= htmlspecialchars($cat['name']) ?></strong><small>Product category</small></span>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <select id="categorySelect" name="category_id" class="sale-select sale-native-select-hidden" aria-hidden="true" tabindex="-1">
                                                    <option value="">-- Select Category --</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>

                                            </div>


                                            <!-- PRODUCT -->

                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-cube"></i>
                                                    Product
                                                </label>

                                                <div class="sale-search-wrap" id="productSearchWrap">
                                                    <input type="search" id="productSearchInput" class="sale-search-input" placeholder="Choose a category first..." autocomplete="off" aria-label="Search products" aria-expanded="false" disabled>
                                                    <i class="fa-solid fa-chevron-down sale-search-caret"></i>
                                                    <div class="sale-search-menu" id="productSearchMenu" role="listbox"></div>
                                                </div>
                                                <select id="productSelect" class="sale-select sale-native-select-hidden" aria-hidden="true" tabindex="-1">
                                                    <option value="">-- Select Product --</option>
                                                </select>
                                                <div class="sale-product-preview" id="selectedProductPreview">
                                                    <img id="selectedProductImage" src="" alt="Selected product">
                                                    <div class="sale-product-preview-copy">
                                                        <strong id="selectedProductName"></strong>
                                                        <p id="selectedProductMeta"></p>
                                                        <p id="selectedProductDescription"></p>
                                                    </div>
                                                </div>
                                                <div class="sale-field" id="variantField" style="display:none;margin-top:12px;">
                                                    <label class="sale-label" for="variantSelect"><i class="fa-solid fa-layer-group"></i> Product Variant</label>
                                                    <select id="variantSelect" class="sale-select"><option value="">Select variant</option></select>
                                                    <small id="variantTrackingHint" style="display:block;margin-top:6px;color:#64748b;font-size:11px;"></small>
                                                </div>

                                            </div>


                                            <!-- UNIT PRICE -->

                                            <div class="sale-field sale-field-2">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-naira-sign"></i>
                                                    Unit Price
                                                </label>

                                                <input
                                                    type="text"
                                                    id="unitPrice"
                                                    readonly
                                                    class="sale-input"
                                                    placeholder="0.00"
                                                >

                                            </div>


                                            <!-- QUANTITY -->

                                            <div class="sale-field sale-field-2">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-hashtag"></i>
                                                    Quantity
                                                </label>

                                                <input
                                                    type="number"
                                                    id="qty"
                                                    min="1"
                                                    value="1"
                                                    class="sale-input"
                                                >

                                                <div class="stock-indicator">
                                                    <i class="fa-solid fa-boxes-stacked"></i>
                                                    <span id="stockText">
                                                        Select a product
                                                    </span>
                                                </div>

                                            </div>


                                            <!-- LINE TOTAL -->

                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-calculator"></i>
                                                    Line Total
                                                </label>

                                                <input
                                                    type="text"
                                                    id="lineTotal"
                                                    readonly
                                                    class="sale-input"
                                                    placeholder="0.00"
                                                >

                                            </div>


                                            <!-- ADD -->

                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    &nbsp;
                                                </label>

                                                <button
                                                    type="button"
                                                    id="addToCartBtn"
                                                    class="add-cart-btn"
                                                >

                                                    <i class="fa-solid fa-cart-plus"></i>

                                                    Add To Cart

                                                </button>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- CART -->

                                    <div class="cart-section">

                                        <div class="sale-card-header">

                                            <div class="sale-card-title">

                                                <div class="sale-card-icon">
                                                    <i class="fa-solid fa-cart-shopping"></i>
                                                </div>

                                                <div>
                                                    <h2>Customer Cart</h2>
                                                    <p>Review items before completing the transaction</p>
                                                </div>

                                            </div>

                                        </div>


                                        <div class="cart-table-wrapper">

                                            <table id="cartTable">

                                                <thead>

                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Qty</th>
                                                        <th>Unit</th>
                                                        <th>Total</th>
                                                        <th></th>
                                                    </tr>

                                                </thead>

                                                <tbody></tbody>

                                                <tfoot>

                                                    <tr>
                                                        <td colspan="3" class="cart-total-label">
                                                            Grand Total
                                                        </td>

                                                        <td id="grandTotal">
                                                            0.00
                                                        </td>

                                                        <td></td>
                                                    </tr>


                                                    <tr>

                                                        <td colspan="3" class="cart-total-label">
                                                            Discount (%)
                                                        </td>

                                                        <td>

                                                            <input
                                                                type="number"
                                                                id="discount"
                                                                name="discount"
                                                                min="0"
                                                                max="100"
                                                                value="0"
                                                                placeholder="%"
                                                            >

                                                        </td>

                                                        <td></td>

                                                    </tr>


                                                    <tr>

                                                        <td colspan="3" class="cart-total-label">
                                                            Discount Amount
                                                        </td>

                                                        <td id="discountAmount">
                                                            0.00
                                                        </td>

                                                        <td></td>

                                                    </tr>


                                                    <tr class="cart-final-row">

                                                        <td colspan="3">
                                                            Final Total
                                                        </td>

                                                        <td id="finalTotal">
                                                            0.00
                                                        </td>

                                                        <td></td>

                                                    </tr>

                                                </tfoot>

                                            </table>

                                        </div>

                                    </div>


                                    <!-- HIDDEN DATA -->

                                    <input
                                        type="hidden"
                                        name="location_id"
                                        value="<?= htmlspecialchars($location_id) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= htmlspecialchars($user_id) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="company_id"
                                        value="<?= htmlspecialchars($company_id) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="cart_payload"
                                        id="cart_payload"
                                    >

                                </section>


                                <!-- PAYMENT -->

                                <section class="sale-card">

                                    <div class="sale-card-header">

                                        <div class="sale-card-title">

                                            <div class="sale-card-icon">
                                                <i class="fa-solid fa-money-check-dollar"></i>
                                            </div>

                                            <div>
                                                <h2>Payment</h2>
                                                <p>Define how this transaction will be settled</p>
                                            </div>

                                        </div>

                                    </div>


                                    <div class="payment-status-grid">

                                        <div class="payment-option">

                                            <input
                                                type="radio"
                                                name="payment_status"
                                                value="full"
                                                id="paymentFull"
                                            >

                                            <label for="paymentFull">

                                                <div class="payment-option-icon">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </div>

                                                <div>
                                                    <strong>Full Payment</strong>
                                                    <span>Customer pays the entire amount</span>
                                                </div>

                                            </label>

                                        </div>


                                        <div class="payment-option">

                                            <input
                                                type="radio"
                                                name="payment_status"
                                                value="partial"
                                                id="paymentPartial"
                                            >

                                            <label for="paymentPartial">

                                                <div class="payment-option-icon">
                                                    <i class="fa-solid fa-clock"></i>
                                                </div>

                                                <div>
                                                    <strong>Part Payment</strong>
                                                    <span>Record payment with outstanding balance</span>
                                                </div>

                                            </label>

                                        </div>

                                    </div>


                                    <select
                                        id="payment_status"
                                        name="payment_status"
                                        style="display:none;"
                                    >

                                        <option value="">
                                            -- Select Payment Status --
                                        </option>

                                        <option value="full">
                                            Full Payment
                                        </option>

                                        <option value="partial">
                                            Part Payment
                                        </option>

                                    </select>


                                    <div
                                        id="partialPaymentBox"
                                        class="partial-box"
                                        style="display:none;"
                                    >

                                        <div class="sale-form-grid">

                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-money-bill"></i>
                                                    Amount Paid
                                                </label>

                                                <input
                                                    type="number"
                                                    id="amount_paid"
                                                    name="amount_paid"
                                                    min="0"
                                                    step="0.01"
                                                    class="sale-input"
                                                >

                                            </div>


                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-scale-balanced"></i>
                                                    Balance
                                                </label>

                                                <input
                                                    type="text"
                                                    id="balance"
                                                    name="balance"
                                                    readonly
                                                    class="sale-input"
                                                >

                                            </div>


                                            <div class="sale-field sale-field-4">

                                                <label class="sale-label">
                                                    <i class="fa-solid fa-calendar-days"></i>
                                                    Due Date
                                                </label>

                                                <input
                                                    type="date"
                                                    id="due_date"
                                                    name="due_date"
                                                    class="sale-input"
                                                >

                                            </div>

                                        </div>

                                    </div>

                                </section>


                                <!-- NOTE -->

                                <section class="sale-card">

                                    <div class="sale-card-header">

                                        <div class="sale-card-title">

                                            <div class="sale-card-icon">
                                                <i class="fa-solid fa-note-sticky"></i>
                                            </div>

                                            <div>
                                                <h2>Sale Note</h2>
                                                <p>Add an optional internal note</p>
                                            </div>

                                        </div>

                                    </div>


                                    <textarea
                                        name="sale_note"
                                        id="sale_note"
                                        class="sale-textarea"
                                        placeholder="Enter any notes for this sale..."
                                    ></textarea>


                                    <div class="sale-submit-area">

                                        <button
                                            type="submit"
                                            id="submitSaleBtn"
                                            class="complete-sale-btn"
                                        >

                                            <i class="fa-solid fa-check-double"></i>

                                            Complete Sale

                                        </button>


                                        <a
                                            href="sales.php"
                                            class="cancel-sale-btn"
                                        >

                                            <i class="fa-solid fa-xmark"></i>

                                            Cancel

                                        </a>

                                    </div>

                                </section>

                            </form>

                        </div>


                        <!-- =================================================
                             RIGHT SUMMARY
                        ================================================== -->

                        <aside class="sale-summary">

                            <section class="sale-card">

                                <div class="sale-card-header">

                                    <div class="sale-card-title">

                                        <div class="sale-card-icon">
                                            <i class="fa-solid fa-receipt"></i>
                                        </div>

                                        <div>
                                            <h2>Sale Summary</h2>
                                            <p>Live transaction overview</p>
                                        </div>

                                    </div>

                                </div>


                                <div class="summary-total">

                                    <small>
                                        FINAL TRANSACTION VALUE
                                    </small>

                                    <strong>
                                        ₦<span id="summaryFinalTotal">0.00</span>
                                    </strong>

                                </div>


                                <div class="summary-list">

                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Cart Items
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="summaryCartItems"
                                        >
                                            0
                                        </span>

                                    </div>


                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Total Quantity
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="summaryQuantity"
                                        >
                                            0
                                        </span>

                                    </div>


                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Subtotal
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="summarySubtotal"
                                        >
                                            ₦0.00
                                        </span>

                                    </div>


                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Discount
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="summaryDiscount"
                                        >
                                            ₦0.00
                                        </span>

                                    </div>


                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Payment
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="summaryPayment"
                                        >
                                            Not selected
                                        </span>

                                    </div>

                                </div>

                            </section>


                            <section class="sale-card">

                                <div class="sale-card-header">

                                    <div class="sale-card-title">

                                        <div class="sale-card-icon">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </div>

                                        <div>
                                            <h2>Transaction Status</h2>
                                            <p>Pre-submission checks</p>
                                        </div>

                                    </div>

                                </div>


                                <div class="summary-list">

                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Customer
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="checkCustomer"
                                        >
                                            Pending
                                        </span>

                                    </div>


                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Products
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="checkProducts"
                                        >
                                            Pending
                                        </span>

                                    </div>


                                    <div class="summary-row">

                                        <span class="summary-label">
                                            Payment
                                        </span>

                                        <span
                                            class="summary-value"
                                            id="checkPayment"
                                        >
                                            Pending
                                        </span>

                                    </div>

                                </div>

                            </section>

                        </aside>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


<?php include('footer.php'); ?>

<script defer src="bundle.js"></script>



<div class="imei-picker-backdrop" id="imeiPickerBackdrop" aria-hidden="true">
    <section class="imei-picker" role="dialog" aria-modal="true" aria-labelledby="imeiPickerTitle">
        <div class="imei-picker-head">
            <div><h3 id="imeiPickerTitle">Select product identifiers</h3><p id="imeiPickerDescription">Choose the exact units being sold.</p></div>
            <button type="button" class="imei-picker-close" id="imeiPickerClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="imei-picker-tools">
            <input type="search" class="imei-picker-search" id="imeiPickerSearch" placeholder="Search identifier..." autocomplete="off">
            <span class="imei-picker-count" id="imeiPickerCount">0 selected</span>
        </div>
        <div class="imei-picker-list" id="imeiPickerList"></div>
        <div class="imei-picker-foot">
            <button type="button" class="imei-picker-btn imei-picker-cancel" id="imeiPickerCancel">Cancel</button>
            <button type="button" class="imei-picker-btn imei-picker-confirm" id="imeiPickerConfirm" disabled>Confirm selection</button>
        </div>
    </section>
</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
       ELEMENTS
    ========================================================= */

    const categorySelect = document.getElementById('categorySelect');
    const productSelect = document.getElementById('productSelect');

    const unitPriceInput = document.getElementById('unitPrice');
    const qtyInput = document.getElementById('qty');
    const lineTotalInput = document.getElementById('lineTotal');

    const addToCartBtn = document.getElementById('addToCartBtn');

    const cartTableBody =
        document.querySelector('#cartTable tbody');

    const grandTotalEl =
        document.getElementById('grandTotal');

    const finalTotalEl =
        document.getElementById('finalTotal');

    const discountInput =
        document.getElementById('discount');

    const discountAmountEl =
        document.getElementById('discountAmount');

    const cartPayloadInput =
        document.getElementById('cart_payload');

    const stockText =
        document.getElementById('stockText');

    let availableStock = 0;

    let cart = [];


    /* =========================================================
       SUMMARY ELEMENTS
    ========================================================= */

    const summaryFinalTotal =
        document.getElementById('summaryFinalTotal');

    const summaryCartItems =
        document.getElementById('summaryCartItems');

    const summaryQuantity =
        document.getElementById('summaryQuantity');

    const summarySubtotal =
        document.getElementById('summarySubtotal');

    const summaryDiscount =
        document.getElementById('summaryDiscount');

    const summaryPayment =
        document.getElementById('summaryPayment');

    const checkProducts =
        document.getElementById('checkProducts');

    const checkPayment =
        document.getElementById('checkPayment');

    const checkCustomer =
        document.getElementById('checkCustomer');


    /* =========================================================
       FORMAT
    ========================================================= */

    function formatNumber(value) {

        return Number(value || 0).toLocaleString(
            'en-NG',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    }


    /* =========================================================
       SEARCHABLE CATEGORY + PRODUCT PICKERS
    ========================================================= */

    const salesProducts = <?= json_encode($salesProducts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
    const categorySearchInput = document.getElementById('categorySearchInput');
    const categorySearchMenu = document.getElementById('categorySearchMenu');
    const productSearchInput = document.getElementById('productSearchInput');
    const productSearchMenu = document.getElementById('productSearchMenu');
    const productPreview = document.getElementById('selectedProductPreview');
    const selectedProductImage = document.getElementById('selectedProductImage');
    const selectedProductName = document.getElementById('selectedProductName');
    const selectedProductMeta = document.getElementById('selectedProductMeta');
    const selectedProductDescription = document.getElementById('selectedProductDescription');
    let selectedProduct = null;
    let selectedVariant = null;
    const variantField = document.getElementById('variantField');
    const variantSelect = document.getElementById('variantSelect');
    const variantTrackingHint = document.getElementById('variantTrackingHint');
    function activeSaleProduct() {
        if (!selectedProduct) return null;
        return selectedVariant ? { ...selectedProduct, ...selectedVariant, parent_name: selectedProduct.name, name: selectedProduct.name + ' — ' + selectedVariant.variant_name, variant_id: selectedVariant.id } : { ...selectedProduct, variant_id: 0 };
    }
    function applySelectedVariant(variant) {
        selectedVariant = variant || null;
        const active = activeSaleProduct();
        if (!active) return;
        availableStock = parseFloat(active.quantity) || 0;
        const price = active.selling_price !== null && active.selling_price !== undefined && active.selling_price !== '' ? active.selling_price : selectedProduct.selling_price;
        unitPriceInput.value = formatNumber(price);
        stockText.textContent = `${formatNumber(availableStock)} ${selectedProduct.unit_name || 'unit(s)'} available`;
        selectedProductMeta.textContent = `${selectedProduct.unit_name || 'Unit'} · ${active.variant_name || selectedProduct.barcode || 'No barcode'} · ₦${formatNumber(price)}`;
        const policy = String(active.identifier_tracking || 'none').toUpperCase();
        variantTrackingHint.textContent = `Tracking: ${policy === 'NONE' ? 'None' : policy === 'IMEI' ? 'IMEI' : policy === 'VIN' ? 'VIN' : 'Serial Number'}`;
        updateLineTotal();
    }

    // Identifier picker: shared by IMEI-, serial-, and VIN-tracked products.
    const imeiBackdrop = document.getElementById('imeiPickerBackdrop');
    const imeiList = document.getElementById('imeiPickerList');
    const imeiSearch = document.getElementById('imeiPickerSearch');
    const imeiCount = document.getElementById('imeiPickerCount');
    const imeiConfirm = document.getElementById('imeiPickerConfirm');
    const imeiDescription = document.getElementById('imeiPickerDescription');
    const imeiClose = document.getElementById('imeiPickerClose');
    const imeiCancel = document.getElementById('imeiPickerCancel');
    const imeiPickerReady = !!(imeiBackdrop && imeiList && imeiSearch && imeiCount && imeiConfirm && imeiDescription && imeiClose && imeiCancel);
    let identifierPickerType = 'imei';
    let imeiPickerRequired = 0;
    let imeiPickerAvailable = [];
    let imeiPickerSelected = new Set();
    let imeiPickerCallback = null;

    function closeImeiPicker() {
        if (!imeiPickerReady) return;
        imeiBackdrop.classList.remove('is-open');
        imeiBackdrop.setAttribute('aria-hidden', 'true');
        imeiPickerCallback = null;
    }

    function updateImeiPickerCount() {
        if (!imeiPickerReady) return;
        imeiCount.textContent = `${imeiPickerSelected.size} / ${imeiPickerRequired} selected`;
        imeiConfirm.disabled = imeiPickerSelected.size !== imeiPickerRequired;
    }

    function renderImeiPickerList(filter = '') {
        if (!imeiPickerReady) return;
        const needle = filter.trim().toLowerCase();
        const matches = imeiPickerAvailable.filter(imei => imei.toLowerCase().includes(needle));
        imeiList.innerHTML = '';
        if (!matches.length) {
            imeiList.innerHTML = '<div class="imei-picker-empty">No available identifiers match your search.</div>';
            return;
        }
        matches.forEach(imei => {
            const label = document.createElement('label');
            label.className = 'imei-picker-option';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = imei;
            checkbox.checked = imeiPickerSelected.has(imei);
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    if (imeiPickerSelected.size >= imeiPickerRequired) { checkbox.checked = false; return; }
                    imeiPickerSelected.add(imei);
                } else { imeiPickerSelected.delete(imei); }
                updateImeiPickerCount();
            });
            const span = document.createElement('span');
            span.textContent = imei;
            label.append(checkbox, span);
            imeiList.appendChild(label);
        });
    }

    function openImeiPicker(required, available, alreadySelected, callback, type = 'imei') {
        identifierPickerType = type;
        if (!imeiPickerReady) { alert('The identifier selection panel could not be initialized. Please refresh the sales page.'); return; }
        imeiPickerRequired = required;
        imeiPickerAvailable = available.filter(imei => !alreadySelected.includes(imei));
        imeiPickerSelected = new Set();
        imeiPickerCallback = callback;
        imeiDescription.textContent = `Select exactly ${required} ${identifierPickerType === 'imei' ? 'IMEI number(s)' : (identifierPickerType === 'vin' ? 'VIN number(s)' : 'serial number(s)')} for this sale. Only units currently in stock at this location are listed.`;
        imeiSearch.value = '';
        renderImeiPickerList();
        updateImeiPickerCount();
        imeiBackdrop.classList.add('is-open');
        imeiBackdrop.setAttribute('aria-hidden', 'false');
        if (imeiPickerAvailable.length < required) {
            const label = identifierPickerType === 'imei' ? 'IMEI number(s)' : (identifierPickerType === 'vin' ? 'VIN number(s)' : 'serial number(s)');
            imeiList.innerHTML = `<div class="imei-picker-empty">Only ${imeiPickerAvailable.length} unselected ${label} are available. Reduce the quantity or refresh stock records.</div>`;
        }
    }

    if (imeiPickerReady) {
        imeiSearch.addEventListener('input', () => renderImeiPickerList(imeiSearch.value));
        imeiClose.addEventListener('click', closeImeiPicker);
        imeiCancel.addEventListener('click', closeImeiPicker);
        imeiBackdrop.addEventListener('click', event => { if (event.target === imeiBackdrop) closeImeiPicker(); });
        imeiConfirm.addEventListener('click', () => {
            if (imeiPickerSelected.size !== imeiPickerRequired || typeof imeiPickerCallback !== 'function') return;
            const chosen = Array.from(imeiPickerSelected);
            const callback = imeiPickerCallback;
            closeImeiPicker();
            callback(chosen);
        });
    }

    function setMenuOpen(menu, input, open) {
        menu.classList.toggle('is-open', open);
        input.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function safeImage(url) {
        return url ? url : '';
    }

    function renderProductOptions(filter = '') {
        const categoryId = categorySelect.value;
        const needle = filter.trim().toLocaleLowerCase();
        const matches = salesProducts.filter(product =>
            String(product.category_id) === String(categoryId) &&
            [product.name, product.barcode, product.description, product.unit_name]
                .some(value => String(value || '').toLocaleLowerCase().includes(needle))
        );
        productSearchMenu.innerHTML = '';
        if (!matches.length) {
            productSearchMenu.innerHTML = '<div class="sale-search-empty">No matching products in this category.</div>';
            return;
        }
        matches.forEach(product => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'sale-search-option';
            option.dataset.id = product.id;
            const image = document.createElement('img');
            image.alt = '';
            image.src = safeImage(product.image_url);
            image.onerror = function () { this.style.visibility = 'hidden'; };
            const copy = document.createElement('span');
            copy.className = 'option-copy';
            const name = document.createElement('strong');
            name.textContent = product.name;
            const meta = document.createElement('small');
            meta.textContent = `${product.unit_name || 'Unit'} · ${product.variants?.length ? product.variants.length + ' variants' : '₦' + formatNumber(product.selling_price)} · Stock: ${formatNumber(product.quantity)}`;
            copy.append(name, meta);
            option.append(image, copy);
            option.addEventListener('click', () => selectProduct(product));
            productSearchMenu.appendChild(option);
        });
    }

    function selectProduct(product) {
        selectedProduct = product;
        selectedVariant = null;
        productSelect.innerHTML = '';
        const selectedOption = document.createElement('option');
        selectedOption.value = String(product.id);
        selectedOption.textContent = product.name;
        selectedOption.selected = true;
        productSelect.appendChild(selectedOption);
        productSearchInput.value = product.name;
        setMenuOpen(productSearchMenu, productSearchInput, false);
        resetInputs();
        selectedProductName.textContent = product.name;
        selectedProductDescription.textContent = product.description || 'No product description available.';
        if (product.image_url) { selectedProductImage.src = product.image_url; selectedProductImage.style.visibility = 'visible'; selectedProductImage.onerror = function () { this.style.visibility = 'hidden'; }; }
        else { selectedProductImage.removeAttribute('src'); selectedProductImage.style.visibility = 'hidden'; }
        productPreview.classList.add('is-visible');
        const variants = Array.isArray(product.variants) ? product.variants : [];
        if (Number(product.has_variants) === 1 || variants.length) {
            variantField.style.display = '';
            variantSelect.innerHTML = '<option value="">Select product variant...</option>';
            variants.forEach(v => { const opt = document.createElement('option'); opt.value = String(v.id); opt.textContent = `${v.variant_name} · Stock: ${formatNumber(v.quantity)}`; variantSelect.appendChild(opt); });
            const preferred = variants.find(v => Number(v.is_default) === 1) || (variants.length === 1 ? variants[0] : null);
            if (preferred) { variantSelect.value = String(preferred.id); applySelectedVariant(preferred); }
            else { selectedVariant = null; availableStock = 0; unitPriceInput.value = ''; stockText.textContent = 'Select a variant'; variantTrackingHint.textContent = 'Choose a variant to see its tracking policy.'; }
        } else {
            variantField.style.display = 'none';
            variantSelect.innerHTML = '<option value="">Select variant</option>';
            applySelectedVariant(null);
        }
    }

    variantSelect.addEventListener('change', function () {
        const variants = selectedProduct?.variants || [];
        const variant = variants.find(v => String(v.id) === String(this.value));
        if (!variant) { selectedVariant = null; availableStock = 0; unitPriceInput.value = ''; stockText.textContent = 'Select a variant'; return; }
        applySelectedVariant(variant);
    });

    categorySearchInput.addEventListener('focus', () => setMenuOpen(categorySearchMenu, categorySearchInput, true));
    categorySearchInput.addEventListener('input', function () {
        const needle = this.value.trim().toLocaleLowerCase();
        document.querySelectorAll('.category-search-option').forEach(option => {
            option.style.display = option.dataset.name.toLocaleLowerCase().includes(needle) ? 'flex' : 'none';
        });
        setMenuOpen(categorySearchMenu, categorySearchInput, true);
    });
    categorySearchMenu.addEventListener('click', function (event) {
        const option = event.target.closest('.category-search-option');
        if (!option) return;
        categorySelect.value = option.dataset.id;
        categorySearchInput.value = option.dataset.name;
        setMenuOpen(categorySearchMenu, categorySearchInput, false);
        productSelect.innerHTML = '<option value="">-- Select Product --</option>';
        productSearchInput.disabled = false;
        productSearchInput.value = '';
        productSearchInput.placeholder = 'Search products by name, barcode or description...';
        selectedProduct = null;
        selectedVariant = null;
        variantField.style.display = 'none';
        productPreview.classList.remove('is-visible');
        resetInputs();
        renderProductOptions('');
    });

    productSearchInput.addEventListener('focus', function () {
        if (!this.disabled) {
            renderProductOptions(this.value === (selectedProduct?.name || '') ? '' : this.value);
            setMenuOpen(productSearchMenu, productSearchInput, true);
        }
    });
    productSearchInput.addEventListener('input', function () {
        if (!this.disabled) {
            productSelect.value = '';
            selectedProduct = null;
            selectedVariant = null;
            variantField.style.display = 'none';
            productPreview.classList.remove('is-visible');
            resetInputs();
            renderProductOptions(this.value);
            setMenuOpen(productSearchMenu, productSearchInput, true);
        }
    });

    document.addEventListener('click', function (event) {
        if (!document.getElementById('categorySearchWrap').contains(event.target)) setMenuOpen(categorySearchMenu, categorySearchInput, false);
        if (!document.getElementById('productSearchWrap').contains(event.target)) setMenuOpen(productSearchMenu, productSearchInput, false);
    });

    /* =========================================================
       LINE TOTAL
    ========================================================= */

    function updateLineTotal() {

        let qty =
            parseFloat(qtyInput.value) || 0;

        if (
            availableStock >= 0 &&
            qty > availableStock
        ) {

            if (availableStock > 0) {

                qty = availableStock;

                qtyInput.value = availableStock;

            }

        }

        const price =
            parseFloat(
                unitPriceInput.value.replace(/,/g, '')
            ) || 0;

        const total = qty * price;

        lineTotalInput.value =
            formatNumber(total);

    }


    qtyInput.addEventListener(
        'input',
        updateLineTotal
    );


    /* =========================================================
       ADD TO CART
    ========================================================= */

    addToCartBtn.addEventListener('click', function () {
        const productId = productSelect.value;
        const productText = selectedProduct?.name || productSearchInput.value || '';
        const qty = parseFloat(qtyInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value.replace(/,/g, '')) || 0;

        if (!productId) { alert('Please select a product.'); return; }
        if (qty <= 0) { alert('Please enter a valid quantity.'); return; }
        if (availableStock <= 0) { alert('This product is currently out of stock.'); return; }

        const product = activeSaleProduct();
        if (selectedProduct?.variants?.length && !selectedVariant) { alert('Please select a product variant.'); return; }
        const variantId = Number(product?.variant_id || 0);
        const existing = cart.find(item => String(item.product_id) === String(productId) && Number(item.variant_id || 0) === variantId);
        const existingQty = existing ? parseFloat(existing.quantity) : 0;
        const totalRequested = existingQty + qty;
        if (totalRequested > availableStock) {
            alert(`Only ${availableStock} unit(s) available.\n\nAlready in cart: ${existingQty}\nRequested: ${qty}`);
            return;
        }

        const trackingType = String(product?.identifier_tracking || 'none');
        if (['imei', 'serial', 'vin'].includes(trackingType)) {
            if (!product.identifier_inventory_available) { alert('Product identifier inventory could not be loaded. Please refresh the page or contact support.'); return; }
            if (!Number.isInteger(qty)) { alert('Identifier-tracked products must be sold in whole-number quantities.'); return; }
            const alreadySelected = existing?.identifiers || [];
            const remainingAvailable = (product.available_identifiers || []).filter(identifier => !alreadySelected.includes(identifier));
            if (remainingAvailable.length < qty) { alert(`Only ${remainingAvailable.length} additional ${trackingType === 'imei' ? 'IMEI' : (trackingType === 'vin' ? 'VIN' : 'serial')} number(s) are available for this product.`); return; }
            openImeiPicker(qty, remainingAvailable, alreadySelected, function (chosenIdentifiers) {
                if (existing) {
                    existing.quantity = totalRequested;
                    existing.identifiers = [...alreadySelected, ...chosenIdentifiers];
                    existing.total_price = existing.quantity * existing.unit_price;
                } else {
                    cart.push({
                        product_id: productId, variant_id: variantId, name: product?.name || productText,
                        image_url: product?.image_url || '',
                        unit_name: product?.unit_name || 'Unit',
                        quantity: qty, unit_price: unitPrice, total_price: qty * unitPrice,
                        identifier_tracking: trackingType,
                        identifier_type: trackingType,
                        identifiers: chosenIdentifiers
                    });
                }
                finishAddingProduct();
            }, trackingType);
            return;
        }

        if (existing) {
            existing.quantity = totalRequested;
            existing.total_price = existing.quantity * existing.unit_price;
        } else {
            cart.push({
                product_id: productId, variant_id: variantId, name: product?.name || productText,
                image_url: product?.image_url || '',
                unit_name: product?.unit_name || 'Unit',
                quantity: qty, unit_price: unitPrice, total_price: qty * unitPrice,
                identifier_tracking: 'none', identifier_type: 'none', identifiers: []
            });
        }
        finishAddingProduct();
    });

    function finishAddingProduct() {
        renderCart();
        productSelect.value = '';
        productSearchInput.value = '';
        selectedProduct = null;
        selectedVariant = null;
        variantField.style.display = 'none';
        productPreview.classList.remove('is-visible');
        resetInputs();
    }


    /* =========================================================
       RENDER CART
    ========================================================= */

    function renderCart() {

        cartTableBody.innerHTML = '';

        let grand = 0;

        let totalQuantity = 0;


        cart.forEach((item, index) => {

            grand +=
                parseFloat(item.total_price) || 0;

            totalQuantity +=
                parseFloat(item.quantity) || 0;


            const tr =
                document.createElement('tr');


            tr.innerHTML = `

                <td>
                    <div class="cart-product-cell">
                        ${item.image_url ? `<img class="sale-product-thumb" src="${escapeHtml(item.image_url)}" alt="" onerror="this.style.visibility='hidden'">` : '<span class="sale-product-thumb"></span>'}
                        <div><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.unit_name || 'Unit')}</small>${item.identifier_tracking && item.identifier_tracking !== 'none' ? `<small class=\"cart-imei-note\">${item.identifiers.length} ${item.identifier_tracking === 'imei' ? 'IMEI' : (item.identifier_tracking === 'vin' ? 'VIN' : 'Serial')} number(s): ${item.identifiers.map(escapeHtml).join(', ')}</small>` : ''}</div>
                    </div>
                </td>

                <td>
                    ${item.quantity}
                </td>

                <td>
                    ₦${formatNumber(item.unit_price)}
                </td>

                <td>
                    <strong>
                        ₦${formatNumber(item.total_price)}
                    </strong>
                </td>

                <td>
                    <button
                        type="button"
                        data-index="${index}"
                        class="btn-remove"
                        title="Remove item"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>

            `;

            cartTableBody.appendChild(tr);

        });


        const discountPercent =
            Math.min(
                Math.max(
                    parseFloat(discountInput.value) || 0,
                    0
                ),
                100
            );


        discountInput.value =
            discountPercent;


        const discountAmount =
            (grand * discountPercent) / 100;


        const finalTotal =
            Math.max(
                grand - discountAmount,
                0
            );


        grandTotalEl.textContent =
            formatNumber(grand);

        discountAmountEl.textContent =
            formatNumber(discountAmount);

        finalTotalEl.textContent =
            formatNumber(finalTotal);


        /* PAYLOAD */

        cartPayloadInput.value =
            JSON.stringify({

                items: cart,

                grand_total: grand,

                discount_percent:
                    discountPercent,

                discount_amount:
                    discountAmount,

                final_total:
                    finalTotal

            });


        /* SUMMARY */

        summaryFinalTotal.textContent =
            formatNumber(finalTotal);

        summaryCartItems.textContent =
            cart.length;

        summaryQuantity.textContent =
            totalQuantity;

        summarySubtotal.textContent =
            '₦' + formatNumber(grand);

        summaryDiscount.textContent =
            '₦' + formatNumber(discountAmount);


        checkProducts.textContent =
            cart.length > 0
                ? 'Ready'
                : 'Pending';


        checkProducts.style.color =
            cart.length > 0
                ? '#059669'
                : '';


        updatePaymentSummary();

    }


    /* =========================================================
       REMOVE CART ITEM
    ========================================================= */

    cartTableBody.addEventListener(
        'click',
        function (event) {

            const button =
                event.target.closest('.btn-remove');

            if (!button) {
                return;
            }

            const index =
                parseInt(
                    button.dataset.index,
                    10
                );

            if (
                Number.isInteger(index) &&
                index >= 0 &&
                index < cart.length
            ) {

                cart.splice(index, 1);

                renderCart();

            }

        }
    );


    /* =========================================================
       DISCOUNT
    ========================================================= */

    discountInput.addEventListener(
        'input',
        function () {

            let value =
                parseFloat(this.value) || 0;

            if (value > 100) {
                value = 100;
            }

            if (value < 0) {
                value = 0;
            }

            this.value = value;

            renderCart();

        }
    );


    /* =========================================================
       PAYMENT
    ========================================================= */

    const paymentFull =
        document.getElementById('paymentFull');

    const paymentPartial =
        document.getElementById('paymentPartial');

    const paymentStatus =
        document.getElementById('payment_status');

    const partialBox =
        document.getElementById('partialPaymentBox');

    const amountPaid =
        document.getElementById('amount_paid');

    const balanceInput =
        document.getElementById('balance');


    function setPaymentStatus(status) {

        paymentStatus.value =
            status;


        if (status === 'partial') {

            partialBox.style.display =
                'block';

            amountPaid.value = '';

            updateBalance();

        } else if (status === 'full') {

            partialBox.style.display =
                'none';

            const total =
                parseFloat(
                    finalTotalEl.textContent
                        .replace(/,/g, '')
                ) || 0;

            amountPaid.value =
                total.toFixed(2);

            balanceInput.value =
                '0.00';

        } else {

            partialBox.style.display =
                'none';

            amountPaid.value = '';

            balanceInput.value = '';

        }

        updatePaymentSummary();

    }


    paymentFull.addEventListener(
        'change',
        function () {

            if (this.checked) {
                setPaymentStatus('full');
            }

        }
    );


    paymentPartial.addEventListener(
        'change',
        function () {

            if (this.checked) {
                setPaymentStatus('partial');
            }

        }
    );


    function updateBalance() {

        const total =
            parseFloat(
                finalTotalEl.textContent
                    .replace(/,/g, '')
            ) || 0;

        const paid =
            parseFloat(amountPaid.value) || 0;


        const balance =
            Math.max(
                total - paid,
                0
            );


        balanceInput.value =
            balance.toFixed(2);

    }


    amountPaid.addEventListener(
        'input',
        function () {

            const total =
                parseFloat(
                    finalTotalEl.textContent
                        .replace(/,/g, '')
                ) || 0;

            let paid =
                parseFloat(this.value) || 0;


            if (paid < 0) {
                paid = 0;
            }


            if (
                paymentStatus.value === 'partial' &&
                paid >= total &&
                total > 0
            ) {

                paid =
                    Math.max(
                        total - 0.01,
                        0
                    );

                this.value =
                    paid.toFixed(2);

            }


            updateBalance();

        }
    );


    function updatePaymentSummary() {

        let status =
            paymentStatus.value;


        if (!status) {

            summaryPayment.textContent =
                'Not selected';

            checkPayment.textContent =
                'Pending';

            checkPayment.style.color = '';

            return;

        }


        if (status === 'full') {

            summaryPayment.textContent =
                'Full Payment';

            checkPayment.textContent =
                'Ready';

        } else {

            summaryPayment.textContent =
                'Part Payment';

            checkPayment.textContent =
                'Ready';

        }


        checkPayment.style.color =
            '#059669';

    }


    /* =========================================================
       CUSTOMER SWITCH
    ========================================================= */

    const newRadio =
        document.getElementById(
            'newCustomerRadio'
        );

    const existingRadio =
        document.getElementById(
            'existingCustomerRadio'
        );

    const newFields =
        document.getElementById(
            'newCustomerFields'
        );

    const existingFields =
        document.getElementById(
            'existingCustomerFields'
        );


    newRadio.addEventListener(
        'change',
        function () {

            if (!this.checked) {
                return;
            }

            newFields.style.display =
                'block';

            existingFields.style.display =
                'none';

            clearExistingCustomer();

            checkCustomer.textContent =
                'Ready';

            checkCustomer.style.color =
                '#059669';

        }
    );


    existingRadio.addEventListener(
        'change',
        function () {

            if (!this.checked) {
                return;
            }

            newFields.style.display =
                'none';

            existingFields.style.display =
                'block';

            document.getElementById(
                'customer_name'
            ).value = '';

            document.getElementById(
                'customer_phone'
            ).value = '';

            document.getElementById(
                'customer_email_new'
            ).value = '';

            checkCustomer.textContent =
                'Select customer';

            checkCustomer.style.color =
                '#ea580c';

        }
    );


    /* =========================================================
       EXISTING CUSTOMER AUTOFILL
    ========================================================= */

    const existingSelect =
        document.getElementById(
            'existingCustomerSelect'
        );

    /* =========================================================
       SEARCHABLE EXISTING CUSTOMER SELECTOR
    ========================================================= */

    const customerSearchWrap = document.getElementById('customerSearchWrap');
    const customerSearchInput = document.getElementById('customerSearchInput');
    const customerSearchMenu = document.getElementById('customerSearchMenu');

    // Detach the customer list from modal/card containers so overflow and
    // stacking contexts cannot crop it. Position it against the viewport.
    document.body.appendChild(customerSearchMenu);
    let customerMenuPositionFrame = null;

    function positionCustomerMenu() {
        if (!customerSearchMenu.classList.contains('is-open')) return;
        const rect = customerSearchInput.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        const gap = 7;
        const edge = 12;
        const below = Math.max(0, viewportHeight - rect.bottom - gap - edge);
        const above = Math.max(0, rect.top - gap - edge);
        const placeAbove = below < 150 && above > below;
        const available = Math.max(100, (placeAbove ? above : below));
        const menuHeight = Math.min(360, available);
        const width = Math.min(rect.width, viewportWidth - edge * 2);
        const left = Math.max(edge, Math.min(rect.left, viewportWidth - width - edge));

        customerSearchMenu.style.position = 'fixed';
        customerSearchMenu.style.left = `${left}px`;
        customerSearchMenu.style.width = `${width}px`;
        customerSearchMenu.style.right = 'auto';
        customerSearchMenu.style.maxHeight = `${menuHeight}px`;
        customerSearchMenu.style.top = placeAbove
            ? `${Math.max(edge, rect.top - gap - menuHeight)}px`
            : `${Math.min(viewportHeight - edge - menuHeight, rect.bottom + gap)}px`;
    }

    function scheduleCustomerMenuPosition() {
        if (customerMenuPositionFrame !== null) cancelAnimationFrame(customerMenuPositionFrame);
        customerMenuPositionFrame = requestAnimationFrame(() => {
            customerMenuPositionFrame = null;
            positionCustomerMenu();
        });
    }

    function setCustomerMenuOpen(open) {
        customerSearchMenu.classList.toggle('is-open', open);
        customerSearchInput.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) scheduleCustomerMenuPosition();
    }

    window.addEventListener('resize', scheduleCustomerMenuPosition);
    window.addEventListener('scroll', scheduleCustomerMenuPosition, true);

    function renderCustomerOptions(searchTerm = '') {
        const term = searchTerm.trim().toLocaleLowerCase();
        customerSearchMenu.innerHTML = '';

        const matchingCustomers = Array.from(existingSelect.options).filter(option => {
            if (!option.value) return false;
            const searchable = [
                option.dataset.name || '',
                option.dataset.phone || '',
                option.dataset.email || '',
                option.textContent || ''
            ].join(' ').toLocaleLowerCase();
            return !term || searchable.includes(term);
        });

        if (!matchingCustomers.length) {
            const empty = document.createElement('div');
            empty.className = 'sale-search-empty';
            empty.textContent = 'No customers match your search.';
            customerSearchMenu.appendChild(empty);
            return;
        }

        matchingCustomers.forEach(option => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'sale-search-option customer-search-option';
            button.setAttribute('role', 'option');
            button.dataset.id = option.value;

            const icon = document.createElement('span');
            icon.style.cssText = 'width:42px;height:42px;flex:0 0 42px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:#eef2ff;color:#5558d9;font-size:16px;';
            const iconElement = document.createElement('i');
            iconElement.className = 'fa-solid fa-user';
            icon.appendChild(iconElement);

            const copy = document.createElement('span');
            copy.className = 'option-copy';
            const name = document.createElement('strong');
            name.textContent = option.dataset.name || 'Unnamed customer';
            const details = document.createElement('small');
            details.textContent = [option.dataset.phone, option.dataset.email].filter(Boolean).join(' · ') || 'No contact details';
            copy.append(name, details);

            button.append(icon, copy);
            customerSearchMenu.appendChild(button);
        });
        scheduleCustomerMenuPosition();
    }

    renderCustomerOptions();

    customerSearchInput.addEventListener('focus', () => {
        renderCustomerOptions(customerSearchInput.value);
        setCustomerMenuOpen(true);
    });

    customerSearchInput.addEventListener('input', () => {
        renderCustomerOptions(customerSearchInput.value);
        setCustomerMenuOpen(true);
    });

    customerSearchMenu.addEventListener('click', event => {
        const optionButton = event.target.closest('.customer-search-option');
        if (!optionButton) return;

        existingSelect.value = optionButton.dataset.id;
        const selectedOption = existingSelect.options[existingSelect.selectedIndex];
        customerSearchInput.value = selectedOption ? (selectedOption.dataset.name || selectedOption.textContent.trim()) : '';
        setCustomerMenuOpen(false);
        existingSelect.dispatchEvent(new Event('change', { bubbles: true }));
    });

    document.addEventListener('click', event => {
        if (!customerSearchWrap.contains(event.target) && !customerSearchMenu.contains(event.target)) setCustomerMenuOpen(false);
    });

    customerSearchInput.addEventListener('keydown', event => {
        if (event.key === 'Escape') setCustomerMenuOpen(false);
        if (event.key === 'Enter') {
            const firstOption = customerSearchMenu.querySelector('.customer-search-option');
            if (firstOption && customerSearchMenu.classList.contains('is-open')) {
                event.preventDefault();
                firstOption.click();
            }
        }
    });

    existingSelect.addEventListener(
        'change',
        function () {

            const selected =
                this.options[
                    this.selectedIndex
                ];


            const name =
                selected.dataset.name || '';

            const phone =
                selected.dataset.phone || '';

            const email =
                selected.dataset.email || '';

            const id =
                selected.value;

            if (customerSearchInput) {
                customerSearchInput.value = id ? (selected.dataset.name || selected.textContent.trim()) : '';
            }

            document.getElementById(
                'ex_name'
            ).value = name;


            document.getElementById(
                'ex_phone'
            ).value = phone;


            document.getElementById(
                'ex_email'
            ).value = email;


            document.getElementById(
                'hidden_customer_email'
            ).value = email;


            document.getElementById(
                'customer_id'
            ).value = id;


            if (id) {

                checkCustomer.textContent =
                    'Ready';

                checkCustomer.style.color =
                    '#059669';

            } else {

                checkCustomer.textContent =
                    'Select customer';

                checkCustomer.style.color =
                    '#ea580c';

            }

        }
    );


    function clearExistingCustomer() {

        existingSelect.value = '';
        if (customerSearchInput) customerSearchInput.value = '';
        if (customerSearchMenu) setCustomerMenuOpen(false);

        document.getElementById(
            'ex_name'
        ).value = '';

        document.getElementById(
            'ex_phone'
        ).value = '';

        document.getElementById(
            'ex_email'
        ).value = '';

        document.getElementById(
            'hidden_customer_email'
        ).value = '';

        document.getElementById(
            'customer_id'
        ).value = '';

    }


    /* =========================================================
       RESET PRODUCT
    ========================================================= */

    function resetInputs() {

        unitPriceInput.value =
            '';

        qtyInput.value =
            '1';

        lineTotalInput.value =
            '';

        availableStock =
            0;

        stockText.textContent =
            'Select a product';

    }


    /* =========================================================
       FORM SUBMISSION
    ========================================================= */

    document.getElementById(
        'saleForm'
    ).addEventListener(
        'submit',
        function (event) {

            const customerType =
                document.querySelector(
                    'input[name="customer_type"]:checked'
                )?.value;


            /* CART */

            if (cart.length === 0) {

                event.preventDefault();

                alert(
                    'Please add at least one product to the cart.'
                );

                return;

            }


            /* CUSTOMER */

            if (customerType === 'new') {

                const customerName =
                    document.getElementById(
                        'customer_name'
                    ).value.trim();


                if (!customerName) {

                    event.preventDefault();

                    alert(
                        'Please enter the customer name.'
                    );

                    return;

                }

            }


            if (
                customerType === 'existing' &&
                !document.getElementById(
                    'customer_id'
                ).value
            ) {

                event.preventDefault();

                alert(
                    'Please select an existing customer.'
                );

                return;

            }


            /* PAYMENT */

            const status =
                paymentStatus.value;


            if (!status) {

                event.preventDefault();

                alert(
                    'Please select a payment status.'
                );

                return;

            }


            /* PART PAYMENT */

            if (status === 'partial') {

                const total =
                    parseFloat(
                        finalTotalEl.textContent
                            .replace(/,/g, '')
                    ) || 0;

                const paid =
                    parseFloat(
                        amountPaid.value
                    ) || 0;

                const dueDate =
                    document.getElementById(
                        'due_date'
                    ).value;


                if (paid <= 0) {

                    event.preventDefault();

                    alert(
                        'Please enter the amount paid.'
                    );

                    return;

                }


                if (paid >= total) {

                    event.preventDefault();

                    alert(
                        'Part payment must be less than the final total.'
                    );

                    return;

                }


                if (!dueDate) {

                    event.preventDefault();

                    alert(
                        'Please select a due date.'
                    );

                    return;

                }

            }


            /* PREVENT DOUBLE SUBMISSION */

            const submitButton =
                document.getElementById(
                    'submitSaleBtn'
                );


            submitButton.disabled =
                true;

            submitButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Processing Sale...';

        }
    );


    /* =========================================================
       HTML ESCAPE
    ========================================================= */

    function escapeHtml(value) {

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }


    /* =========================================================
       INITIAL STATE
    ========================================================= */

    renderCart();

});

</script>

</body>
</html>