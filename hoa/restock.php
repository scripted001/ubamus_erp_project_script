
<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id  = (int)($_SESSION['company_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);

if ($company_id <= 0 || $location_id <= 0) {
    die('Your session has expired or your stock location is unavailable. Please sign in again.');
}

/*
|--------------------------------------------------------------------------
| Get stock identifiers from the inventory overview
|--------------------------------------------------------------------------
| For variant products, stock_id is the parent compatibility stock row;
| the authoritative quantity is stored in product_variant_stocks.
*/
$stock_id = isset($_POST['stock_id']) ? (int)$_POST['stock_id'] : 0;
$variant_id = isset($_POST['variant_id']) && trim((string)$_POST['variant_id']) !== ''
    ? (int)$_POST['variant_id']
    : 0;
$posted_product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($stock_id <= 0 || ($variant_id < 0)) {
    die('Invalid stock selected.');
}

/*
|--------------------------------------------------------------------------
| Get stock + product information, validating company and location
|--------------------------------------------------------------------------
*/
if ($variant_id > 0) {
    $stmt = $pdo->prepare("
        SELECT
            s.id AS stock_id,
            s.product_id,
            s.company_id,
            s.location_id,
            COALESCE(pvs.quantity, 0) AS quantity,
            s.supplier,
            p.name AS product_name,
            COALESCE(pv.cost_price, p.cost_price) AS cost_price,
            pv.variant_name,
            pv.sku AS variant_sku,
            pv.barcode AS variant_barcode,
            pv.tracking_policy,
            p.identifier_tracking,
            p.imei_tracking,
            p.has_variants,
            LOWER(TRIM(COALESCE(pc.stock_type, ''))) AS product_stock_type
        FROM stocks s
        INNER JOIN products p
            ON p.id = s.product_id AND p.company_id = s.company_id
        INNER JOIN product_variants pv
            ON pv.product_id = p.id
           AND pv.company_id = p.company_id
           AND pv.id = ?
           AND pv.is_active = 1
        INNER JOIN product_variant_stocks pvs
            ON pvs.company_id = s.company_id
           AND pvs.product_id = s.product_id
           AND pvs.variant_id = pv.id
           AND pvs.location_id = s.location_id
        LEFT JOIN product_categories pc
            ON pc.id = p.category_id
        WHERE s.id = ?
          AND s.company_id = ?
          AND s.location_id = ?
          AND p.company_id = ?
          AND p.has_variants = 1
          AND (? = 0 OR p.id = ?)
        LIMIT 1
    ");
    $stmt->execute([
        $variant_id, $stock_id, $company_id, $location_id,
        $company_id, $posted_product_id, $posted_product_id
    ]);
} else {
    $stmt = $pdo->prepare("
        SELECT
            s.id AS stock_id,
            s.product_id,
            s.company_id,
            s.location_id,
            s.quantity,
            s.supplier,
            p.name AS product_name,
            p.cost_price,
            '' AS variant_name,
            '' AS variant_sku,
            '' AS variant_barcode,
            'inherit' AS tracking_policy,
            p.identifier_tracking,
            p.imei_tracking,
            p.has_variants,
            LOWER(TRIM(COALESCE(pc.stock_type, ''))) AS product_stock_type
        FROM stocks s
        INNER JOIN products p
            ON p.id = s.product_id AND p.company_id = s.company_id
        LEFT JOIN product_categories pc
            ON pc.id = p.category_id
        WHERE s.id = ?
          AND s.company_id = ?
          AND s.location_id = ?
          AND p.company_id = ?
          AND COALESCE(p.has_variants, 0) = 0
          AND (? = 0 OR p.id = ?)
        LIMIT 1
    ");
    $stmt->execute([
        $stock_id, $company_id, $location_id,
        $company_id, $posted_product_id, $posted_product_id
    ]);
}

$stock = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stock) {
    die('Stock record not found or you do not have access to it. Refresh the inventory page and try again.');
}

/*
|--------------------------------------------------------------------------
| Values For Form
|--------------------------------------------------------------------------
*/
$product_id             = (int)$stock['product_id'];
$product_name           = (string)$stock['product_name'];
$variant_name           = (string)($stock['variant_name'] ?? '');
$current_cost_price     = $stock['cost_price'];
$current_stock_quantity = (int)$stock['quantity'];
$tracking_type = strtolower(trim((string)($stock['identifier_tracking'] ?? 'none')));
if ($tracking_type === '' || $tracking_type === 'none') {
    if (!empty($stock['imei_tracking'])) $tracking_type = 'imei';
}
if ($variant_id > 0) {
    $variantPolicy = strtolower(trim((string)($stock['tracking_policy'] ?? 'inherit')));
    if ($variantPolicy !== '' && $variantPolicy !== 'inherit') $tracking_type = $variantPolicy;
}
if (!in_array($tracking_type, ['none', 'imei', 'serial', 'vin'], true)) $tracking_type = 'none';
if ($tracking_type !== 'none' && strtolower(trim((string)($stock['product_stock_type'] ?? ''))) !== 'sales only') {
    die('Identifier tracking is only supported for Sales Only products.');
}
$tracking_label = [
    'imei' => 'IMEI',
    'serial' => 'Serial Number',
    'vin' => 'VIN'
][$tracking_type] ?? '';

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
        <?= htmlspecialchars($company_name) ?> | Restock Terminal
    </title>

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >


<style>

/* ============================================================
   FUTURISTIC RESTOCK TERMINAL
   ============================================================ */

:root {

    --restock-primary: #6839eb;
    --restock-primary-dark: #5227c9;
    --restock-primary-soft: rgba(104, 57, 235, .10);

    --restock-blue: #2563eb;
    --restock-green: #059669;
    --restock-orange: #f59e0b;

    --restock-text: #172033;
    --restock-heading: #0f172a;
    --restock-muted: #64748b;

    --restock-border: rgba(148, 163, 184, .25);

    --restock-glass:
        rgba(255,255,255,.78);

    --restock-glass-strong:
        rgba(255,255,255,.91);

    --restock-shadow:
        0 25px 80px rgba(15,23,42,.09);

    --restock-radius: 26px;
}


/* ============================================================
   RESET
   ============================================================ */

.restock-terminal *,
.restock-terminal *::before,
.restock-terminal *::after {
    box-sizing: border-box;
}


/* ============================================================
   PAGE BACKGROUND
   ============================================================ */

.restock-page {

    width: 100%;
    min-height: 100%;

    padding: 8px 0 40px;

    position: relative;
}

.restock-page::before {

    content: "";

    position: fixed;

    width: 420px;
    height: 420px;

    top: 80px;
    right: -180px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(104,57,235,.10),
            transparent 68%
        );

    pointer-events: none;

    z-index: 0;
}

.restock-page::after {

    content: "";

    position: fixed;

    width: 360px;
    height: 360px;

    bottom: -160px;
    left: -140px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(37,99,235,.07),
            transparent 68%
        );

    pointer-events: none;

    z-index: 0;
}


/* ============================================================
   MAIN TERMINAL
   ============================================================ */

.restock-terminal {

    width: 100%;
    max-width: 1450px;

    margin: 0 auto;

    position: relative;
    z-index: 2;

    overflow: hidden;

    border-radius: var(--restock-radius);

    border: 1px solid rgba(255,255,255,.90);

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.86),
            rgba(248,250,252,.72)
        );

    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);

    box-shadow: var(--restock-shadow);
}


/* Decorative glow */

.restock-terminal::before {

    content: "";

    position: absolute;

    width: 340px;
    height: 340px;

    top: -190px;
    right: -110px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(104,57,235,.12),
            transparent 70%
        );

    pointer-events: none;
}

.restock-terminal::after {

    content: "";

    position: absolute;

    width: 250px;
    height: 250px;

    bottom: -160px;
    left: -100px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(37,99,235,.07),
            transparent 70%
        );

    pointer-events: none;
}


/* ============================================================
   HEADER
   ============================================================ */

.restock-header {

    position: relative;
    z-index: 3;

    padding: 28px 30px 25px;

    border-bottom: 1px solid rgba(148,163,184,.14);
}


.restock-header-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;
}


/* Title */

.restock-heading {

    display: flex;

    align-items: center;

    gap: 15px;

    min-width: 0;
}


.restock-heading-icon {

    width: 58px;
    height: 58px;

    flex: 0 0 58px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 18px;

    color: var(--restock-primary);

    background:
        linear-gradient(
            145deg,
            rgba(104,57,235,.15),
            rgba(104,57,235,.045)
        );

    border: 1px solid rgba(104,57,235,.15);

    box-shadow:
        0 12px 35px rgba(104,57,235,.10);

    font-size: 23px;
}


.restock-title {

    margin: 0;

    color: var(--restock-heading);

    font-size:
        clamp(
            1.35rem,
            2.3vw,
            1.9rem
        );

    line-height: 1.15;

    font-weight: 800;

    letter-spacing: -.045em;
}


.restock-subtitle {

    margin: 6px 0 0;

    color: var(--restock-muted);

    font-size: .84rem;

    line-height: 1.5;
}


/* Status */

.restock-system-status {

    display: inline-flex;

    align-items: center;

    gap: 9px;

    padding: 10px 14px;

    border-radius: 999px;

    background:
        rgba(5,150,105,.07);

    border:
        1px solid rgba(5,150,105,.14);

    color: #047857;

    font-size: .73rem;

    font-weight: 800;

    white-space: nowrap;
}


.restock-status-dot {

    width: 8px;
    height: 8px;

    border-radius: 50%;

    background: #10b981;

    box-shadow:
        0 0 0 5px rgba(16,185,129,.09);
}


/* ============================================================
   BREADCRUMB
   ============================================================ */

.restock-breadcrumb {

    position: relative;
    z-index: 3;

    padding: 13px 30px;

    background:
        rgba(248,250,252,.55);

    border-bottom:
        1px solid rgba(148,163,184,.12);
}


.restock-breadcrumb ol {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 8px;

    margin: 0;

    padding: 0;

    list-style: none;
}


.restock-breadcrumb a {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    color: #64748b;

    text-decoration: none;

    font-size: .76rem;

    transition: .2s ease;
}


.restock-breadcrumb a:hover {

    color: var(--restock-primary);
}


.restock-breadcrumb-current {

    color: #1e293b;

    font-size: .76rem;

    font-weight: 750;
}


/* ============================================================
   CONTENT
   ============================================================ */

.restock-content {

    position: relative;

    z-index: 3;

    padding: 30px;
}


/* ============================================================
   PRODUCT HERO
   ============================================================ */

.product-hero {

    display: grid;

    grid-template-columns:
        minmax(0, 1.5fr)
        minmax(220px, .7fr);

    gap: 20px;

    margin-bottom: 22px;
}


.product-card {

    position: relative;

    overflow: hidden;

    padding: 23px;

    border-radius: 21px;

    background:
        linear-gradient(
            135deg,
            rgba(104,57,235,.085),
            rgba(255,255,255,.70)
        );

    border:
        1px solid rgba(104,57,235,.12);

    box-shadow:
        0 12px 35px rgba(15,23,42,.035);
}


.product-card::after {

    content: "";

    position: absolute;

    width: 170px;
    height: 170px;

    right: -90px;
    top: -90px;

    border-radius: 50%;

    background:
        rgba(104,57,235,.07);
}


.product-card-top {

    display: flex;

    align-items: center;

    gap: 14px;
}


.product-icon {

    width: 48px;
    height: 48px;

    display: flex;

    align-items: center;
    justify-content: center;

    flex: 0 0 48px;

    border-radius: 15px;

    background:
        rgba(255,255,255,.72);

    border:
        1px solid rgba(104,57,235,.12);

    color: var(--restock-primary);

    font-size: 19px;
}


.product-label {

    margin: 0 0 4px;

    color: var(--restock-muted);

    font-size: .68rem;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: .10em;
}


.product-name {

    margin: 0;

    color: var(--restock-heading);

    font-size:
        clamp(
            1.05rem,
            2vw,
            1.35rem
        );

    font-weight: 800;

    line-height: 1.25;

    word-break: break-word;
}


/* Cost card */

.cost-card {

    display: flex;

    flex-direction: column;

    justify-content: center;

    padding: 22px;

    border-radius: 21px;

    background:
        rgba(255,255,255,.68);

    border:
        1px solid rgba(148,163,184,.20);

    box-shadow:
        0 12px 35px rgba(15,23,42,.035);
}


.cost-label {

    display: flex;

    align-items: center;

    gap: 7px;

    color: var(--restock-muted);

    font-size: .68rem;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: .08em;
}


.cost-label i {

    color: var(--restock-primary);
}


.cost-value {

    margin-top: 7px;

    color: var(--restock-heading);

    font-size:
        clamp(
            1.25rem,
            2.5vw,
            1.65rem
        );

    font-weight: 850;

    letter-spacing: -.035em;
}


/* ============================================================
   STOCK FLOW
   ============================================================ */

.stock-flow {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 15px;

    margin-bottom: 24px;
}


.stock-stat {

    position: relative;

    overflow: hidden;

    min-height: 135px;

    padding: 19px;

    border-radius: 19px;

    background:
        rgba(255,255,255,.72);

    border:
        1px solid rgba(148,163,184,.19);

    box-shadow:
        0 10px 30px rgba(15,23,42,.035);

    transition:
        transform .22s ease,
        box-shadow .22s ease;
}


.stock-stat:hover {

    transform: translateY(-2px);

    box-shadow:
        0 16px 35px rgba(15,23,42,.07);
}


.stock-stat-icon {

    width: 35px;
    height: 35px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 11px;

    margin-bottom: 13px;

    font-size: 13px;
}


.stock-stat.current
.stock-stat-icon {

    background:
        rgba(37,99,235,.09);

    color: #2563eb;
}


.stock-stat.incoming
.stock-stat-icon {

    background:
        rgba(245,158,11,.10);

    color: #d97706;
}


.stock-stat.projected
.stock-stat-icon {

    background:
        rgba(5,150,105,.10);

    color: #059669;
}


.stock-stat-label {

    color: var(--restock-muted);

    font-size: .68rem;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: .075em;
}


.stock-stat-value {

    margin-top: 5px;

    color: var(--restock-heading);

    font-size: 1.35rem;

    font-weight: 850;

    letter-spacing: -.035em;
}


.stock-stat-description {

    margin-top: 4px;

    color: #94a3b8;

    font-size: .67rem;
}


/* ============================================================
   FORM PANEL
   ============================================================ */

.restock-form-panel {

    padding: 26px;

    border-radius: 23px;

    background:
        rgba(255,255,255,.70);

    border:
        1px solid rgba(148,163,184,.20);

    box-shadow:
        0 15px 40px rgba(15,23,42,.045);
}


.form-panel-heading {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20px;

    padding-bottom: 20px;

    margin-bottom: 23px;

    border-bottom:
        1px solid rgba(148,163,184,.15);
}


.form-panel-title {

    display: flex;

    align-items: center;

    gap: 11px;

    margin: 0;

    color: var(--restock-heading);

    font-size: 1rem;

    font-weight: 800;
}


.form-panel-title-icon {

    width: 34px;
    height: 34px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 10px;

    color: var(--restock-primary);

    background:
        var(--restock-primary-soft);

    font-size: 13px;
}


.form-panel-caption {

    margin: 4px 0 0;

    color: var(--restock-muted);

    font-size: .72rem;

    line-height: 1.5;
}


.secure-badge {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 8px 10px;

    border-radius: 9px;

    background:
        rgba(5,150,105,.07);

    color: #047857;

    font-size: .64rem;

    font-weight: 750;

    white-space: nowrap;
}


/* ============================================================
   FORM GRID
   ============================================================ */

.restock-form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 20px;
}


.restock-field {

    min-width: 0;
}


.restock-field.full {

    grid-column: 1 / -1;
}


.restock-label {

    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 8px;

    color: #334155;

    font-size: .75rem;

    font-weight: 750;
}


.restock-label i {

    width: 15px;

    color: var(--restock-primary);

    text-align: center;

    font-size: 11px;
}


/* ============================================================
   INPUTS
   ============================================================ */

.restock-control {

    width: 100%;

    min-height: 49px;

    padding: 12px 14px;

    border-radius: 13px;

    border:
        1px solid rgba(148,163,184,.30);

    background:
        rgba(255,255,255,.72);

    color: var(--restock-text);

    outline: none;

    font-family: inherit;

    font-size: .84rem;

    font-weight: 550;

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease,
        transform .2s ease;
}


.restock-control::placeholder {

    color: #94a3b8;
}


.restock-control:hover {

    border-color:
        rgba(104,57,235,.25);
}


.restock-control:focus {

    border-color:
        rgba(104,57,235,.58);

    background:
        rgba(255,255,255,.95);

    box-shadow:
        0 0 0 4px rgba(104,57,235,.08),
        0 8px 24px rgba(15,23,42,.035);
}


/* readonly */

.restock-control.readonly {

    background:
        rgba(241,245,249,.68);

    color:
        #334155;

    cursor:
        not-allowed;
}


.restock-control.stock-input {

    font-weight: 800;

    color:
        #1d4ed8;
}


/* ============================================================
   QUANTITY INPUT
   ============================================================ */

.quantity-wrapper {

    position: relative;
}


.quantity-wrapper .restock-control {

    padding-right: 48px;

    font-size: 1rem;

    font-weight: 800;
}


.quantity-suffix {

    position: absolute;

    top: 50%;
    right: 14px;

    transform: translateY(-50%);

    color: #94a3b8;

    font-size: .68rem;

    font-weight: 750;

    pointer-events: none;
}


/* ============================================================
   PROJECTION
   ============================================================ */

.stock-projection {

    margin-top: 18px;

    padding: 17px;

    border-radius: 15px;

    background:
        linear-gradient(
            135deg,
            rgba(5,150,105,.07),
            rgba(255,255,255,.60)
        );

    border:
        1px solid rgba(5,150,105,.12);
}


.projection-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}


.projection-side {

    min-width: 0;
}


.projection-label {

    display: block;

    margin-bottom: 4px;

    color: #64748b;

    font-size: .64rem;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: .06em;
}


.projection-number {

    color: #047857;

    font-size: 1.25rem;

    font-weight: 850;
}


.projection-arrow {

    width: 35px;
    height: 35px;

    display: flex;

    align-items: center;
    justify-content: center;

    flex: 0 0 35px;

    border-radius: 50%;

    background:
        rgba(5,150,105,.09);

    color: #059669;

    font-size: 12px;
}


/* ============================================================
   ACTION AREA
   ============================================================ */

.restock-actions {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-top: 25px;

    padding-top: 22px;

    border-top:
        1px solid rgba(148,163,184,.15);
}


.action-note {

    display: flex;

    align-items: center;

    gap: 9px;

    color: #94a3b8;

    font-size: .69rem;

    line-height: 1.45;
}


.action-note i {

    color: var(--restock-primary);

    font-size: 13px;
}


.action-buttons {

    display: flex;

    align-items: center;

    gap: 10px;
}


.restock-btn {

    min-height: 46px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 11px 19px;

    border-radius: 12px;

    border: 1px solid transparent;

    font-size: .76rem;

    font-weight: 800;

    text-decoration: none;

    cursor: pointer;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}


.restock-btn:hover {

    transform: translateY(-2px);
}


.restock-btn-primary {

    color: #fff;

    background:
        linear-gradient(
            135deg,
            #6839eb,
            #5427c8
        );

    box-shadow:
        0 12px 28px rgba(104,57,235,.20);
}


.restock-btn-primary:hover {

    box-shadow:
        0 17px 35px rgba(104,57,235,.28);
}


.restock-btn-secondary {

    color: #475569;

    background:
        rgba(255,255,255,.76);

    border-color:
        rgba(148,163,184,.27);
}


.restock-btn-secondary:hover {

    color: #1e293b;

    background:
        #fff;

    border-color:
        rgba(104,57,235,.20);
}


/* ============================================================
   RESPONSIVE — TABLET
   ============================================================ */

@media (max-width: 1100px) {

    .product-hero {

        grid-template-columns:
            1fr 1fr;
    }

    .restock-content {

        padding: 24px;
    }

    .restock-header,
    .restock-breadcrumb {

        padding-left: 24px;
        padding-right: 24px;
    }
}


/* ============================================================
   RESPONSIVE — MOBILE
   ============================================================ */

@media (max-width: 768px) {

    .restock-page {

        padding:
            5px 0 25px;
    }


    .restock-terminal {

        border-radius: 19px;

        box-shadow:
            0 15px 45px rgba(15,23,42,.07);
    }


    .restock-header {

        padding:
            20px 17px;
    }


    .restock-header-row {

        align-items: flex-start;
    }


    .restock-heading {

        gap: 11px;
    }


    .restock-heading-icon {

        width: 45px;
        height: 45px;

        flex-basis: 45px;

        border-radius: 13px;

        font-size: 17px;
    }


    .restock-title {

        font-size:
            1.18rem;
    }


    .restock-subtitle {

        font-size:
            .71rem;
    }


    .restock-system-status {

        display: none;
    }


    .restock-breadcrumb {

        padding:
            10px 17px;
    }


    .restock-content {

        padding:
            18px 14px 22px;
    }


    .product-hero {

        grid-template-columns:
            1fr;

        gap: 12px;

        margin-bottom: 15px;
    }


    .product-card,
    .cost-card {

        padding:
            17px;
    }


    .stock-flow {

        grid-template-columns:
            1fr;

        gap: 10px;

        margin-bottom: 15px;
    }


    .stock-stat {

        min-height:
            auto;

        padding:
            16px;

        display:
            grid;

        grid-template-columns:
            38px 1fr;

        column-gap:
            12px;
    }


    .stock-stat-icon {

        grid-row:
            span 2;

        margin:
            0;

        width: 38px;
        height: 38px;
    }


    .stock-stat-label {

        align-self:
            end;
    }


    .stock-stat-value {

        margin-top:
            2px;
    }


    .stock-stat-description {

        grid-column:
            2;

        margin-top:
            1px;
    }


    .restock-form-panel {

        padding:
            18px 15px;
    }


    .form-panel-heading {

        flex-direction:
            column;

        gap: 10px;

        padding-bottom:
            16px;

        margin-bottom:
            18px;
    }


    .secure-badge {

        align-self:
            flex-start;
    }


    .restock-form-grid {

        grid-template-columns:
            1fr;

        gap:
            17px;
    }


    .restock-field.full {

        grid-column:
            auto;
    }


    .restock-actions {

        flex-direction:
            column;

        align-items:
            stretch;

        gap:
            14px;
    }


    .action-note {

        justify-content:
            center;

        text-align:
            center;
    }


    .action-buttons {

        width:
            100%;

        display:
            grid;

        grid-template-columns:
            1fr 1fr;
    }


    .restock-btn {

        width:
            100%;
    }
}


/* ============================================================
   SMALL MOBILE
   ============================================================ */

@media (max-width: 480px) {

    .restock-header {

        padding:
            17px 13px;
    }


    .restock-breadcrumb {

        padding:
            9px 13px;
    }


    .restock-content {

        padding:
            14px 11px 19px;
    }


    .restock-heading-icon {

        width: 42px;
        height: 42px;

        flex-basis:
            42px;
    }


    .restock-title {

        font-size:
            1.08rem;
    }


    .restock-subtitle {

        font-size:
            .67rem;
    }


    .product-card,
    .cost-card {

        border-radius:
            16px;

        padding:
            15px;
    }


    .product-name {

        font-size:
            1rem;
    }


    .stock-stat {

        border-radius:
            15px;
    }


    .restock-form-panel {

        border-radius:
            17px;

        padding:
            15px 12px;
    }


    .restock-control {

        min-height:
            46px;

        border-radius:
            11px;

        padding:
            11px 12px;

        font-size:
            .8rem;
    }


    .restock-label {

        font-size:
            .71rem;
    }


    .projection-row {

        gap:
            9px;
    }


    .projection-number {

        font-size:
            1.05rem;
    }


    .projection-arrow {

        width:
            30px;

        height:
            30px;

        flex-basis:
            30px;
    }


    .action-buttons {

        grid-template-columns:
            1fr;
    }


    .restock-btn {

        min-height:
            45px;
    }
}


/* ============================================================
   EXISTING ALPINE DARK MODE COMPATIBILITY
   ============================================================ */

.dark .restock-terminal {

    background:
        rgba(17,24,39,.82);

    border-color:
        rgba(255,255,255,.06);
}


.dark .restock-title {

    color:
        #f8fafc;
}


.dark .restock-subtitle {

    color:
        #94a3b8;
}


.dark .restock-breadcrumb {

    background:
        rgba(15,23,42,.42);

    border-color:
        rgba(255,255,255,.05);
}


.dark .restock-breadcrumb-current {

    color:
        #e2e8f0;
}


.dark .product-card {

    background:
        linear-gradient(
            135deg,
            rgba(104,57,235,.14),
            rgba(15,23,42,.40)
        );

    border-color:
        rgba(104,57,235,.15);
}


.dark .product-name,
.dark .cost-value,
.dark .stock-stat-value,
.dark .form-panel-title {

    color:
        #f8fafc;
}


.dark .cost-card,
.dark .stock-stat,
.dark .restock-form-panel {

    background:
        rgba(15,23,42,.62);

    border-color:
        rgba(71,85,105,.48);
}


.dark .restock-label {

    color:
        #cbd5e1;
}


.dark .restock-control {

    background:
        rgba(15,23,42,.72);

    border-color:
        rgba(71,85,105,.60);

    color:
        #f8fafc;
}


.dark .restock-control.readonly {

    background:
        rgba(30,41,59,.65);

    color:
        #e2e8f0;
}


.dark .restock-control:focus {

    background:
        rgba(15,23,42,.92);
}


.dark .restock-btn-secondary {

    background:
        rgba(15,23,42,.70);

    color:
        #cbd5e1;

    border-color:
        rgba(71,85,105,.55);
}


.dark .stock-projection {

    background:
        linear-gradient(
            135deg,
            rgba(5,150,105,.12),
            rgba(15,23,42,.45)
        );
}


/* ============================================================
   ACCESSIBILITY
   ============================================================ */

.restock-control:focus-visible,
.restock-btn:focus-visible {

    outline:
        3px solid rgba(104,57,235,.18);

    outline-offset:
        2px;
}


  /* =========================================================
           STOCK TERMINAL — RESPONSIVE UI
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
            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.98)
                );
            box-shadow:
                0 20px 50px rgba(15,23,42,.08),
                0 2px 8px rgba(15,23,42,.04);
        }

        .stock-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(59,130,246,.09),
                    transparent 34%
                ),
                radial-gradient(
                    circle at bottom left,
                    rgba(16,185,129,.06),
                    transparent 30%
                );
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
            background:
                linear-gradient(
                    135deg,
                    #111827,
                    #334155
                );
            color: #fff;
            box-shadow:
                0 10px 25px rgba(15,23,42,.2);
        }

        .stock-title-icon i {
            font-size: 21px;
        }

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

        .stock-field {
            min-width: 0;
        }

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

        .stock-input-wrap {
            position: relative;
        }

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
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                transform .2s ease;
            box-sizing: border-box;
        }

        .stock-input::placeholder {
            color: #9ca3af;
        }

        .stock-input:hover,
        .stock-select:hover {
            border-color: #b8c3d1;
        }

        .stock-input:focus,
        .stock-select:focus {
            border-color: #6366f1;
            background: #fff;
            box-shadow:
                0 0 0 4px rgba(99,102,241,.09),
                0 8px 20px rgba(15,23,42,.05);
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

        .stock-help {
            margin-top: 7px;
            font-size: 11px;
            color: #94a3b8;
        }

        .stock-submit-area {
            grid-column: 1 / -1;
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
            background:
                linear-gradient(
                    135deg,
                    #111827,
                    #1e293b
                );
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .01em;
            cursor: pointer;
            box-shadow:
                0 12px 28px rgba(15,23,42,.18);
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                filter .2s ease;
        }

        .stock-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow:
                0 16px 34px rgba(15,23,42,.23);
        }

        .stock-submit:active {
            transform: translateY(0);
        }

        .stock-submit i {
            font-size: 15px;
        }

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

        .stock-footer-note i {
            font-size: 10px;
        }


        /* =========================================================
           DARK MODE
        ========================================================= */

        .dark .stock-shell {
            background:
                linear-gradient(
                    145deg,
                    rgba(17,24,39,.98),
                    rgba(15,23,42,.98)
                );
            border-color: rgba(71,85,105,.45);
            box-shadow:
                0 20px 50px rgba(0,0,0,.28);
        }

        .dark .stock-header {
            background: rgba(15,23,42,.55);
            border-color: rgba(71,85,105,.35);
        }

        .dark .stock-title {
            color: #f8fafc;
        }

        .dark .stock-subtitle {
            color: #94a3b8;
        }

        .dark .stock-label {
            color: #d1d5db;
        }

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

        .dark .stock-input:focus,
        .dark .stock-select:focus {
            background: #0f172a;
            border-color: #6366f1;
        }

        .dark .stock-submit-area {
            border-color: #334155;
        }


        /* =========================================================
           TABLET
        ========================================================= */

        @media (max-width: 900px) {

            .stock-form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stock-submit-area {
                grid-column: auto;
            }

            .stock-header {
                padding: 22px;
            }

            .stock-form-area {
                padding: 22px;
            }

        }


        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 640px) {

            .stock-page {
                width: 100%;
            }

            .stock-shell {
                border-radius: 18px;
            }

            .stock-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 20px 18px;
                gap: 15px;
            }

            .stock-title-wrap {
                width: 100%;
            }

            .stock-title-icon {
                width: 46px;
                height: 46px;
                flex-basis: 46px;
                border-radius: 13px;
            }

            .stock-title-icon i {
                font-size: 18px;
            }

            .stock-title {
                font-size: 18px;
            }

            .stock-subtitle {
                font-size: 12px;
                line-height: 1.5;
            }

            .stock-status {
                align-self: flex-start;
                font-size: 11px;
            }

            .stock-form-area {
                padding: 20px 16px 22px;
            }

            .stock-form-grid {
                gap: 18px;
            }

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

            .stock-submit {
                min-height: 54px;
            }

        }


        /* =========================================================
           VERY SMALL PHONES
        ========================================================= */

        @media (max-width: 380px) {

            .stock-header {
                padding: 17px 14px;
            }

            .stock-form-area {
                padding: 17px 13px 20px;
            }

            .stock-title {
                font-size: 16px;
            }

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


<!-- ============================================================
     PAGE WRAPPER
     ============================================================ -->

<div class="flex h-screen overflow-hidden">


    <?php include('sidebar.php'); ?>


    <!-- ========================================================
         CONTENT AREA
         ======================================================== -->

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
    >


        <main>


            <?php include('header.php'); ?>


            <div
                class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6"
            >


                <div class="restock-page">


                    <!-- =================================================
                         FUTURISTIC RESTOCK TERMINAL
                         ================================================= -->

                    <section class="restock-terminal">


                        <!-- =================================================
                             HEADER
                             ================================================= -->

                        <header class="restock-header">

                            <div class="restock-header-row">


                                <div class="restock-heading">

                                    <div class="restock-heading-icon">

                                        <i class="fas fa-boxes-stacked"></i>

                                    </div>


                                    <div>

                                        <h1 class="restock-title">

                                            Restock Terminal

                                        </h1>


                                        <p class="restock-subtitle">

                                            Replenish inventory and synchronize
                                            your stock levels in real time.

                                        </p>

                                    </div>

                                </div>


                                <div class="restock-system-status">

                                    <span class="restock-status-dot"></span>

                                    Inventory System Active

                                </div>


                            </div>

                        </header>


                        <!-- =================================================
                             BREADCRUMB
                             ================================================= -->

                        <div class="restock-breadcrumb">

                            <nav aria-label="Breadcrumb">

                                <ol>

                                    <li>

                                        <a href="index.php">

                                            <i class="fas fa-house"></i>

                                            Home

                                        </a>

                                    </li>


                                    <li>

                                        <i
                                            class="fas fa-chevron-right"
                                            style="
                                                font-size:8px;
                                                color:#94a3b8;
                                            "
                                        ></i>

                                    </li>


                                    <li class="restock-breadcrumb-current">

                                        Restock Inventory

                                    </li>

                                </ol>

                            </nav>

                        </div>


                        <!-- =================================================
                             MAIN CONTENT
                             ================================================= -->

                        <div class="restock-content">


                            <!-- =================================================
                                 PRODUCT INFORMATION
                                 ================================================= -->

                            <div class="product-hero">


                                <div class="product-card">

                                    <div class="product-card-top">


                                        <div class="product-icon">

                                            <i class="fas fa-box-open"></i>

                                        </div>


                                        <div>

                                            <p class="product-label">

                                                Target Product

                                            </p>


                                            <h2 class="product-name">

                                                <?= htmlspecialchars($product_name) ?>

                                            </h2>

                                        </div>


                                    </div>

                                </div>


                                <div class="cost-card">

                                    <div class="cost-label">

                                        <i class="fas fa-naira-sign"></i>

                                        Current Unit Cost

                                    </div>


                                    <div class="cost-value">

                                        ₦<?= number_format(
                                            (float)$current_cost_price,
                                            2
                                        ) ?>

                                    </div>

                                </div>


                            </div>


                            <!-- =================================================
                                 STOCK INTELLIGENCE
                                 ================================================= -->

                            <div class="stock-flow">


                                <!-- Current -->

                                <div class="stock-stat current">

                                    <div class="stock-stat-icon">

                                        <i class="fas fa-layer-group"></i>

                                    </div>


                                    <div class="stock-stat-label">

                                        Current Stock

                                    </div>


                                    <div
                                        class="stock-stat-value"
                                        id="currentStockDisplay"
                                    >

                                        <?= number_format(
                                            $current_stock_quantity
                                        ) ?>

                                    </div>


                                    <div class="stock-stat-description">

                                        Available before restock

                                    </div>

                                </div>


                                <!-- Incoming -->

                                <div class="stock-stat incoming">

                                    <div class="stock-stat-icon">

                                        <i class="fas fa-arrow-trend-up"></i>

                                    </div>


                                    <div class="stock-stat-label">

                                        Incoming Quantity

                                    </div>


                                    <div
                                        class="stock-stat-value"
                                        id="incomingDisplay"
                                    >

                                        0

                                    </div>


                                    <div class="stock-stat-description">

                                        Units being added

                                    </div>

                                </div>


                                <!-- Projected -->

                                <div class="stock-stat projected">

                                    <div class="stock-stat-icon">

                                        <i class="fas fa-chart-line"></i>

                                    </div>


                                    <div class="stock-stat-label">

                                        Projected Stock

                                    </div>


                                    <div
                                        class="stock-stat-value"
                                        id="projectedDisplay"
                                    >

                                        <?= number_format(
                                            $current_stock_quantity
                                        ) ?>

                                    </div>


                                    <div class="stock-stat-description">

                                        Expected inventory after restock

                                    </div>

                                </div>


                            </div>


                            <!-- =================================================
                                 RESTOCK FORM
                                 ================================================= -->

                            <div class="restock-form-panel">


                                <div class="form-panel-heading">


                                    <div>

                                        <h3 class="form-panel-title">

                                            <span
                                                class="form-panel-title-icon"
                                            >

                                                <i class="fas fa-sliders"></i>

                                            </span>

                                            Inventory Adjustment

                                        </h3>


                                        <p class="form-panel-caption">

                                            Enter the quantity you are receiving
                                            into the selected stock record.

                                        </p>

                                    </div>


                                    <div class="secure-badge">

                                        <i class="fas fa-shield-halved"></i>

                                        Secure Inventory Action

                                    </div>


                                </div>


                                <!-- =================================================
                                     FORM
                                     ================================================= -->

                                <form
                                    method="POST"
                                    action="process_restock.php"
                                    enctype="multipart/form-data"
                                >


                                    <div class="restock-form-grid">


                                        <!-- PRODUCT -->

                                        <div class="restock-field">

                                            <label class="restock-label">

                                                <i class="fas fa-box"></i>

                                                Product Name

                                            </label>


                                            <input
                                                type="text"
                                                value="<?= htmlspecialchars(
                                                    $product_name . ($variant_name !== '' ? ' — ' . $variant_name : '')
                                                ) ?>"
                                                readonly
                                                class="restock-control readonly"
                                            >

                                        </div>


                                        <!-- COST -->

                                        <div class="restock-field">

                                            <label class="restock-label">

                                                <i class="fas fa-money-bill-wave"></i>

                                                Current Unit Cost

                                            </label>


                                            <input
                                                type="text"
                                                value="₦<?= number_format(
                                                    (float)$current_cost_price,
                                                    2
                                                ) ?>"
                                                readonly
                                                class="restock-control readonly"
                                            >

                                        </div>


                                        <!-- CURRENT STOCK -->

                                        <div class="restock-field">

                                            <label class="restock-label">

                                                <i class="fas fa-cubes"></i>

                                                Current Stock Quantity

                                            </label>


                                            <input
                                                type="text"
                                                id="current_stock"
                                                value="<?= number_format(
                                                    $current_stock_quantity
                                                ) ?>"
                                                readonly
                                                class="restock-control readonly stock-input"
                                            >

                                        </div>


                                        <!-- ADD QUANTITY -->

                                        <div class="restock-field">

                                            <label class="restock-label">

                                                <i class="fas fa-plus-circle"></i>

                                                Add Quantity

                                            </label>


                                            <div class="quantity-wrapper">

                                                <input
                                                    type="number"
                                                    id="add_quantity"
                                                    name="add_quantity"
                                                    min="1"
                                                    max="500"
                                                    step="1"
                                                    inputmode="numeric"
                                                    placeholder="Enter quantity to add"
                                                    class="restock-control"
                                                    required
                                                >


                                                <span class="quantity-suffix">

                                                    UNITS

                                                </span>

                                            </div>

                                        </div>


                                        <!-- INDIVIDUAL UNIT IDENTIFIERS -->

                                        <?php if ($tracking_type !== 'none'): ?>
                                        <div class="restock-field full" id="restockIdentifiersPanel">
                                            <label class="restock-label" for="restock_identifier_1">
                                                <i class="fas fa-barcode"></i>
                                                <?= htmlspecialchars($tracking_label) ?> values for incoming units
                                            </label>
                                            <p class="form-panel-caption">
                                                Enter one unique <?= htmlspecialchars($tracking_label) ?> for every unit being added.
                                                The number of fields will match the restock quantity.
                                            </p>
                                            <div id="restockIdentifiers" class="restock-form-grid" style="margin-top:14px;">
                                                <p class="form-panel-caption">Enter the quantity above to generate identifier fields.</p>
                                            </div>
                                            <div id="restockIdentifierError" role="alert" style="display:none;color:#dc2626;font-size:.78rem;margin-top:10px;"></div>
                                        </div>
                                        <?php endif; ?>


                                        <!-- PROJECTION -->

                                        <div
                                            class="restock-field full"
                                        >

                                            <div class="stock-projection">

                                                <div class="projection-row">


                                                    <div
                                                        class="projection-side"
                                                    >

                                                        <span
                                                            class="projection-label"
                                                        >

                                                            Current

                                                        </span>


                                                        <span
                                                            class="projection-number"
                                                            id="projectionCurrent"
                                                        >

                                                            <?= number_format(
                                                                $current_stock_quantity
                                                            ) ?>

                                                        </span>

                                                    </div>


                                                    <div class="projection-arrow">

                                                        <i
                                                            class="fas fa-arrow-right"
                                                        ></i>

                                                    </div>


                                                    <div
                                                        class="projection-side"
                                                    >

                                                        <span
                                                            class="projection-label"
                                                        >

                                                            Incoming

                                                        </span>


                                                        <span
                                                            class="projection-number"
                                                            id="projectionIncoming"
                                                        >

                                                            +0

                                                        </span>

                                                    </div>


                                                    <div class="projection-arrow">

                                                        <i
                                                            class="fas fa-equals"
                                                        ></i>

                                                    </div>


                                                    <div
                                                        class="projection-side"
                                                    >

                                                        <span
                                                            class="projection-label"
                                                        >

                                                            After Restock

                                                        </span>


                                                        <span
                                                            class="projection-number"
                                                            id="projectionTotal"
                                                        >

                                                            <?= number_format(
                                                                $current_stock_quantity
                                                            ) ?>

                                                        </span>

                                                    </div>


                                                </div>

                                            </div>

                                        </div>


                                        <!-- =================================================
                                             HIDDEN FIELDS
                                             ================================================= -->

                                        <input
                                            type="hidden"
                                            name="stock_id"
                                            value="<?= htmlspecialchars(
                                                $stock_id
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= htmlspecialchars(
                                                $product_id
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="variant_id"
                                            value="<?= (int)$variant_id ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?= htmlspecialchars(
                                                $_SESSION['company_id']
                                            ) ?>"
                                        >

                                                                  <!-- STOCK INVOICE — OPTIONAL -->

<div class="stock-field">

    <label class="stock-label" for="stock_invoice">
        <i class="fa-solid fa-file-invoice"></i>
        <span>Stock Invoice</span>
        <span style="color:#94a3b8;font-size:11px;">Optional</span>
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
        Attach the supplier's invoice for this stock entry.
        PDF, JPG or PNG; maximum 20 MB.
    </div>

</div>


                                        <!-- =================================================
                                             ACTIONS
                                             ================================================= -->

                                        <div
                                            class="restock-field full"
                                        >

                                            <div class="restock-actions">


                                                <div class="action-note">

                                                    <i
                                                        class="fas fa-circle-info"
                                                    ></i>

                                                    Stock quantity will be
                                                    updated after submission.

                                                </div>


                                                <div class="action-buttons">


                                                    <a
                                                        href="categories.php"
                                                        class="restock-btn restock-btn-secondary"
                                                    >

                                                        <i
                                                            class="fas fa-arrow-left"
                                                        ></i>

                                                        Cancel

                                                    </a>


                                                    <button
                                                        type="submit"
                                                        class="restock-btn restock-btn-primary"
                                                    >

                                                        <i
                                                            class="fas fa-boxes-stacked"
                                                        ></i>

                                                        Restock Inventory

                                                    </button>


                                                </div>


                                            </div>

                                        </div>


                                    </div>


                                </form>


                            </div>


                        </div>


                    </section>


                </div>

            </div>


        </main>


    </div>

</div>


<!-- ============================================================
     FOOTER
     ============================================================ -->

<?php include('footer.php'); ?>


<!-- ============================================================
     EXISTING BUNDLE
     ============================================================ -->

<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>

<script
    defer
    src="bundle.js"
></script>


<!-- ============================================================
     CLOUDFLARE
     ============================================================ -->

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


<!-- ============================================================
     LIVE STOCK PROJECTION
     Existing functionality preserved and expanded
     ============================================================ -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const currentStockInput =
            document.getElementById('current_stock');

        const addQuantityInput =
            document.getElementById('add_quantity');


        const currentStockDisplay =
            document.getElementById('currentStockDisplay');

        const incomingDisplay =
            document.getElementById('incomingDisplay');

        const projectedDisplay =
            document.getElementById('projectedDisplay');


        const projectionCurrent =
            document.getElementById('projectionCurrent');

        const projectionIncoming =
            document.getElementById('projectionIncoming');

        const projectionTotal =
            document.getElementById('projectionTotal');


        /*
        |--------------------------------------------------------------------------
        | Original stock from PHP
        |--------------------------------------------------------------------------
        */

        const originalStock =
            <?= (int)$current_stock_quantity ?>;


        /*
        |--------------------------------------------------------------------------
        | Format number
        |--------------------------------------------------------------------------
        */

        function formatNumber(number) {

            return Number(number).toLocaleString();

        }


        /*
        |--------------------------------------------------------------------------
        | Update stock projection
        |--------------------------------------------------------------------------
        */

        function updateStockProjection() {


            const addQuantity =
                parseInt(
                    addQuantityInput.value,
                    10
                ) || 0;


            const projectedStock =
                originalStock + addQuantity;


            /*
            |----------------------------------------------------------
            | Main stock field
            |----------------------------------------------------------
            */

            currentStockInput.value =
                formatNumber(projectedStock);


            /*
            |----------------------------------------------------------
            | Intelligence cards
            |----------------------------------------------------------
            */

            currentStockDisplay.textContent =
                formatNumber(originalStock);


            incomingDisplay.textContent =
                formatNumber(addQuantity);


            projectedDisplay.textContent =
                formatNumber(projectedStock);


            /*
            |----------------------------------------------------------
            | Projection terminal
            |----------------------------------------------------------
            */

            projectionCurrent.textContent =
                formatNumber(originalStock);


            projectionIncoming.textContent =
                '+' + formatNumber(addQuantity);


            projectionTotal.textContent =
                formatNumber(projectedStock);

        }


        /*
        |--------------------------------------------------------------------------
        | Listen for quantity changes
        |--------------------------------------------------------------------------
        */

        addQuantityInput.addEventListener(
            'input',
            updateStockProjection
        );

        // Tracked products require one identifier for each incoming unit.
        const trackingType = <?= json_encode($tracking_type) ?>;
        const identifierContainer = document.getElementById('restockIdentifiers');
        const identifierError = document.getElementById('restockIdentifierError');
        const restockForm = document.querySelector('form[action="process_restock.php"]');

        function buildIdentifierFields() {
            if (!identifierContainer || trackingType === 'none') return;

            const quantity = Math.max(0, Math.min(500, parseInt(addQuantityInput.value, 10) || 0));
            identifierContainer.replaceChildren();

            if (quantity === 0) {
                const note = document.createElement('p');
                note.className = 'form-panel-caption';
                note.textContent = 'Enter the quantity above to generate identifier fields.';
                identifierContainer.appendChild(note);
                return;
            }

            for (let i = 0; i < quantity; i++) {
                const field = document.createElement('div');
                field.className = 'restock-field';

                const label = document.createElement('label');
                label.className = 'restock-label';
                label.htmlFor = 'restock_identifier_' + (i + 1);
                label.textContent = <?= json_encode($tracking_label) ?> + ' #' + (i + 1);

                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'identifiers[]';
                input.id = 'restock_identifier_' + (i + 1);
                input.className = 'restock-control';
                input.required = true;
                input.autocomplete = 'off';
                input.maxLength = trackingType === 'vin' ? 17 : (trackingType === 'imei' ? 15 : 100);
                input.placeholder = trackingType === 'vin' ? '17-character VIN' :
                    (trackingType === 'imei' ? '15-digit IMEI' : 'Enter serial number');

                if (trackingType === 'vin') {
                    input.minLength = 17;
                    input.maxLength = 17;
                    input.pattern = '[A-HJ-NPR-Z0-9]{17}';
                    input.style.textTransform = 'uppercase';
                    input.addEventListener('input', () => {
                        input.value = input.value.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, '').slice(0, 17);
                    });
                } else if (trackingType === 'imei') {
                    input.minLength = 15;
                    input.maxLength = 15;
                    input.inputMode = 'numeric';
                    input.pattern = '[0-9]{15}';
                }

                field.append(label, input);
                identifierContainer.appendChild(field);
            }
        }

        if (identifierContainer) {
            addQuantityInput.addEventListener('input', buildIdentifierFields);
            restockForm.addEventListener('submit', function (event) {
                const inputs = identifierContainer.querySelectorAll('input[name="identifiers[]"]');
                const values = Array.from(inputs, input => input.value.trim());
                const quantity = parseInt(addQuantityInput.value, 10) || 0;
                const normalized = values.map(value => value.toUpperCase());

                if (values.length !== quantity || values.some(value => !value)) {
                    event.preventDefault();
                    identifierError.textContent = 'Enter one identifier for each unit being restocked.';
                    identifierError.style.display = 'block';
                    return;
                }

                if (new Set(normalized).size !== normalized.length) {
                    event.preventDefault();
                    identifierError.textContent = 'Duplicate identifiers were entered. Each unit must have a different identifier.';
                    identifierError.style.display = 'block';
                    return;
                }

                identifierError.style.display = 'none';
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Initial state
        |--------------------------------------------------------------------------
        */

        updateStockProjection();


    }

);

</script>


</body>

</html>
