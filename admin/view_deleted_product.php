<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


// ============================================================
// COMPANY / PRODUCT ID
// ============================================================

$company_id = (int)($_SESSION['company_id'] ?? 0);
$product_id = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($company_id <= 0 || $product_id <= 0) {
    echo "<script>
        alert('Invalid product request.');
        window.location.href='deleted_products.php';
    </script>";
    exit;
}


// ============================================================
// HELPER
// ============================================================

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDateValue($value, $format = 'd M Y, h:i A'): string
{
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime((string)$value);

    return $timestamp !== false
        ? date($format, $timestamp)
        : e($value);
}

function moneyValue($value): string
{
    if ($value === null || $value === '') {
        return '0.00';
    }

    return number_format((float)$value, 2);
}


// ============================================================
// LOAD ARCHIVED PRODUCT
// ============================================================

try {

    $stmt = $pdo->prepare("
        SELECT
            dp.*,
            pc.name AS category_name,
            u.username AS deleted_by_username,
            u.email AS deleted_by_email
        FROM deleted_products dp
        LEFT JOIN product_categories pc
            ON pc.id = dp.category_id
        LEFT JOIN users u
            ON u.id = CAST(dp.deleted_by AS UNSIGNED)
            AND u.company_id = dp.company_id
        WHERE dp.product_id = ?
        AND dp.company_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "<script>
            alert('Archived product not found.');
            window.location.href='deleted_products.php';
        </script>";
        exit;
    }


// ============================================================
// LOAD UNIT NAME
// ============================================================

    $unit_name = '-';

    try {

        $unitStmt = $pdo->prepare("
            SELECT name
            FROM product_units
            WHERE id = ?
            LIMIT 1
        ");

        $unitStmt->execute([
            (int)$product['unit_id']
        ]);

        $unit_name = $unitStmt->fetchColumn() ?: '-';

    } catch (Throwable $e) {
        $unit_name = (string)$product['unit_id'];
    }


// ============================================================
// INVENTORY COUNTS
// ============================================================

    // Number of stock records
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS record_count,
            COALESCE(SUM(quantity), 0) AS total_quantity
        FROM deleted_stocks
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $stockSummary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'record_count' => 0,
        'total_quantity' => 0
    ];


    // Number of stock-in records
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS record_count,
            COALESCE(SUM(quantity), 0) AS total_quantity
        FROM deleted_stock_in
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $stockInSummary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'record_count' => 0,
        'total_quantity' => 0
    ];


    // Number of movement records
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS record_count,
            COALESCE(SUM(quantity), 0) AS total_quantity
        FROM deleted_stock_movement
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $movementSummary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'record_count' => 0,
        'total_quantity' => 0
    ];


// ============================================================
// LOAD STOCK HISTORY
// ============================================================

    $stmt = $pdo->prepare("
        SELECT
            ds.*,
            l.name AS location_name
        FROM deleted_stocks ds
        LEFT JOIN locations l
            ON l.id = ds.location_id
        WHERE ds.product_id = ?
        AND ds.company_id = ?
        ORDER BY ds.deleted_at DESC, ds.id DESC
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $stockHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// LOAD STOCK-IN HISTORY
// ============================================================

    $stmt = $pdo->prepare("
        SELECT
            dsi.*,
            l.name AS location_name
        FROM deleted_stock_in dsi
        LEFT JOIN locations l
            ON l.id = dsi.location_id
        WHERE dsi.product_id = ?
        AND dsi.company_id = ?
        ORDER BY dsi.deleted_at DESC, dsi.id DESC
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $stockInHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// LOAD MOVEMENT HISTORY
// ============================================================

    $stmt = $pdo->prepare("
        SELECT
            dsm.*,
            fl.name AS from_location_name,
            tl.name AS to_location_name
        FROM deleted_stock_movement dsm
        LEFT JOIN locations fl
            ON fl.id = dsm.from_location_id
        LEFT JOIN locations tl
            ON tl.id = dsm.to_location_id
        WHERE dsm.product_id = ?
        AND dsm.company_id = ?
        ORDER BY dsm.deleted_at DESC, dsm.id DESC
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $movementHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// LOAD DELETED USER DETAILS
// ============================================================

    $deletedByUsername = $product['deleted_by_username'] ?? '';
    $deletedByEmail = $product['deleted_by_email'] ?? '';


// ============================================================
// CALCULATED VALUES
// ============================================================

    $stockRecords = (int)$stockSummary['record_count'];
    $stockQuantity = (int)$stockSummary['total_quantity'];

    $stockInRecords = (int)$stockInSummary['record_count'];
    $stockInQuantity = (int)$stockInSummary['total_quantity'];

    $movementRecords = (int)$movementSummary['record_count'];
    $movementQuantity = (int)$movementSummary['total_quantity'];

} catch (Throwable $e) {

    die(
        "<div style='font-family:Arial;padding:30px;'>" .
        "<h3>Error loading archived product:</h3>" .
        "<p>" . e($e->getMessage()) . "</p>" .
        "</div>"
    );
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

    <title>
        <?= e($company_name) ?> | Archived Product
    </title>


    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >

    <link
        href="main.css"
        rel="stylesheet"
    >

    <link
        href="product-table.css"
        rel="stylesheet"
    >


<style>

/* ============================================================
   GLOBAL
============================================================ */

html,
body {
    max-width: 100%;
    overflow-x: hidden;
}

*,
*::before,
*::after {
    box-sizing: border-box;
}

.archive-profile-page {
    width: 100%;
    min-width: 0;
}


/* ============================================================
   TOP ACTIONS
============================================================ */

.profile-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.profile-topbar-left {
    min-width: 0;
}

.profile-topbar h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
}

.profile-topbar p {
    margin: 5px 0 0;
    color: #9ca3af;
    font-size: 13px;
}

.profile-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}


/* ============================================================
   BUTTONS
============================================================ */

.profile-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 38px;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    border: 0;
    cursor: pointer;
    white-space: nowrap;
}

.profile-btn.back {
    background: #f3f4f6;
    color: #374151;
}

.profile-btn.back:hover {
    background: #e5e7eb;
}

.profile-btn.recover {
    background: #16a34a;
    color: #fff;
}

.profile-btn.recover:hover {
    background: #15803d;
}


/* ============================================================
   PROFILE HERO
============================================================ */

.profile-hero {
    position: relative;
    display: grid;
    grid-template-columns: 90px minmax(0, 1fr) auto;
    gap: 20px;
    align-items: center;
    padding: 25px;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    background: #fff;
    margin-bottom: 20px;
    min-width: 0;
}

.profile-icon {
    width: 90px;
    height: 90px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef2f2;
    color: #dc2626;
    font-size: 36px;
    flex-shrink: 0;
}

.profile-hero-content {
    min-width: 0;
}

.profile-hero-content h1 {
    margin: 0;
    font-size: 28px;
    line-height: 1.2;
    font-weight: 750;
    color: #111827;
    word-break: break-word;
}

.profile-subtitle {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
    color: #6b7280;
    font-size: 13px;
}

.archive-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(220, 38, 38, .10);
    color: #dc2626;
    font-size: 12px;
    font-weight: 700;
}

.profile-hero-side {
    text-align: right;
    min-width: 130px;
}

.profile-hero-side-label {
    color: #9ca3af;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.profile-hero-side-value {
    margin-top: 3px;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
}


/* ============================================================
   SECTION CARD
============================================================ */

.profile-card {
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    background: #fff;
    margin-bottom: 20px;
    overflow: hidden;
    min-width: 0;
}

.profile-card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #f0f1f3;
}

.profile-card-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 16px;
    font-weight: 700;
}

.profile-card-header p {
    margin: 4px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.profile-card-body {
    padding: 20px;
}


/* ============================================================
   PRODUCT DETAILS
============================================================ */

.details-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1px;
    background: #eef0f2;
    border: 1px solid #eef0f2;
    border-radius: 12px;
    overflow: hidden;
}

.detail-item {
    background: #fff;
    padding: 16px;
    min-width: 0;
}

.detail-label {
    display: block;
    margin-bottom: 6px;
    color: #9ca3af;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.detail-value {
    color: #1f2937;
    font-size: 14px;
    font-weight: 600;
    word-break: break-word;
}

.detail-value.price {
    font-size: 16px;
}

.description-box {
    margin-top: 15px;
    padding: 16px;
    border-radius: 12px;
    background: #f9fafb;
}

.description-box .detail-value {
    font-weight: 400;
    line-height: 1.7;
    white-space: pre-wrap;
}


/* ============================================================
   STAT CARDS
============================================================ */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.stat-card {
    padding: 18px;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #fff;
    min-width: 0;
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #6b7280;
}

.stat-number {
    margin-top: 15px;
    color: #111827;
    font-size: 26px;
    font-weight: 750;
    line-height: 1;
}

.stat-label {
    margin-top: 6px;
    color: #6b7280;
    font-size: 12px;
}

.stat-total {
    margin-top: 9px;
    color: #9ca3af;
    font-size: 12px;
}


/* ============================================================
   ARCHIVE INFORMATION
============================================================ */

.archive-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.archive-info {
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    min-width: 0;
}

.archive-info-label {
    color: #9ca3af;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.archive-info-value {
    margin-top: 7px;
    color: #1f2937;
    font-size: 14px;
    font-weight: 600;
    word-break: break-word;
}

.deleted-person {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.deleted-person-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #6b7280;
    flex-shrink: 0;
}

.deleted-person-details {
    min-width: 0;
}

.deleted-person-email {
    color: #6b7280;
    font-size: 12px;
    font-weight: 400;
    overflow-wrap: anywhere;
}


/* ============================================================
   HISTORY SECTION
============================================================ */

.history-summary {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.history-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 8px;
    background: #f3f4f6;
    color: #4b5563;
    font-size: 12px;
    font-weight: 600;
}


/* ============================================================
   HISTORY CARDS
============================================================ */

.history-list {
    display: grid;
    gap: 10px;
}

.history-item {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr) auto;
    align-items: center;
    gap: 13px;
    padding: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    min-width: 0;
}

.history-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #6b7280;
}

.history-main {
    min-width: 0;
}

.history-title {
    color: #1f2937;
    font-size: 13px;
    font-weight: 700;
}

.history-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 7px 14px;
    margin-top: 5px;
    color: #6b7280;
    font-size: 11px;
}

.history-meta span {
    max-width: 100%;
    overflow-wrap: anywhere;
}

.history-quantity {
    text-align: right;
    white-space: nowrap;
}

.history-quantity strong {
    display: block;
    color: #111827;
    font-size: 16px;
}

.history-quantity small {
    color: #9ca3af;
    font-size: 10px;
}

.no-history {
    padding: 28px 15px;
    text-align: center;
    color: #9ca3af;
    border: 1px dashed #d1d5db;
    border-radius: 12px;
}

.no-history i {
    display: block;
    margin-bottom: 8px;
    font-size: 25px;
}


/* ============================================================
   DARK MODE
============================================================ */

.dark .profile-hero,
.dark .profile-card,
.dark .stat-card {
    background: rgba(255,255,255,.03);
    border-color: #374151;
}

.dark .profile-topbar h2,
.dark .profile-hero-content h1,
.dark .profile-card-header h3,
.dark .detail-value,
.dark .archive-info-value,
.dark .history-title,
.dark .history-quantity strong,
.dark .profile-hero-side-value,
.dark .stat-number {
    color: #f9fafb;
}

.dark .details-grid {
    background: #374151;
    border-color: #374151;
}

.dark .detail-item {
    background: #111827;
}

.dark .description-box,
.dark .stat-icon,
.dark .deleted-person-icon,
.dark .history-icon,
.dark .history-pill {
    background: rgba(255,255,255,.07);
}

.dark .archive-info,
.dark .history-item {
    border-color: #374151;
}

.dark .profile-btn.back {
    background: rgba(255,255,255,.08);
    color: #e5e7eb;
}


/* ============================================================
   TABLET
============================================================ */

@media (max-width: 900px) {

    .profile-hero {
        grid-template-columns: 75px minmax(0, 1fr);
    }

    .profile-icon {
        width: 75px;
        height: 75px;
        font-size: 30px;
    }

    .profile-hero-side {
        grid-column: 2;
        text-align: left;
    }

    .details-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .stats-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 640px) {

    .profile-topbar {
        align-items: stretch;
    }

    .profile-topbar-left {
        width: 100%;
    }

    .profile-topbar h2 {
        font-size: 19px;
    }

    .profile-actions {
        width: 100%;
    }

    .profile-actions .profile-btn {
        flex: 1;
    }

    .profile-hero {
        grid-template-columns: 60px minmax(0, 1fr);
        gap: 13px;
        padding: 18px;
        border-radius: 14px;
    }

    .profile-icon {
        width: 60px;
        height: 60px;
        border-radius: 13px;
        font-size: 24px;
    }

    .profile-hero-content h1 {
        font-size: 21px;
    }

    .profile-hero-side {
        grid-column: 1 / -1;
        padding-top: 12px;
        border-top: 1px solid #eef0f2;
    }

    .profile-card {
        border-radius: 14px;
    }

    .profile-card-header {
        padding: 15px;
    }

    .profile-card-body {
        padding: 15px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 15px;
    }

    .archive-info-grid {
        grid-template-columns: 1fr;
    }

    .history-item {
        grid-template-columns: 42px minmax(0, 1fr);
    }

    .history-quantity {
        grid-column: 2;
        text-align: left;
    }

    .history-meta {
        flex-direction: column;
        gap: 4px;
    }

    .profile-btn {
        min-height: 42px;
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
        darkMode = JSON.parse(localStorage.getItem('darkMode'));

        $watch(
            'darkMode',
            value => localStorage.setItem(
                'darkMode',
                JSON.stringify(value)
            )
        )
    "

    :class="{
        'dark bg-gray-900': darkMode === true
    }"
>


<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>


    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
        style="min-width:0;"
    >

        <main style="width:100%; min-width:0;">

            <?php include('header.php'); ?>


            <div
                class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6 archive-profile-page"
            >


                <!-- ========================================================
                     TOP BAR
                ======================================================== -->

                <div class="profile-topbar">

                    <div class="profile-topbar-left">

                        <h2>
                            Archived Product Profile
                        </h2>

                        <p>
                            Complete information and inventory history
                        </p>

                    </div>


                    <div class="profile-actions">

                        <a
                            href="deleted_products.php"
                            class="profile-btn back"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Back to Archive
                        </a>


                        <form
                            action="recover_product.php"
                            method="POST"
                            style="margin:0;"
                        >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= $product_id ?>"
                            >

                            <button
                                type="submit"
                                class="profile-btn recover"
                                onclick="return confirm('Are you sure you want to recover this product and all of its archived inventory history?')"
                            >
                                <i class="fa-solid fa-rotate-left"></i>
                                Recover Product
                            </button>

                        </form>

                    </div>

                </div>


                <!-- ========================================================
                     PRODUCT HERO
                ======================================================== -->

                <div class="profile-hero">

                    <div class="profile-icon">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>


                    <div class="profile-hero-content">

                        <h1>
                            <?= e($product['name']) ?>
                        </h1>

                        <div class="profile-subtitle">

                            <span>
                                Product ID #<?= (int)$product['product_id'] ?>
                            </span>

                            <span>•</span>

                            <span>
                                <?= e($product['category_name'] ?? '-') ?>
                            </span>

                            <span class="archive-status">
                                <i class="fa-solid fa-box-archive"></i>
                                Archived
                            </span>

                        </div>

                    </div>


                    <div class="profile-hero-side">

                        <div class="profile-hero-side-label">
                            Deleted
                        </div>

                        <div class="profile-hero-side-value">
                            <?= formatDateValue($product['deleted_at']) ?>
                        </div>

                    </div>

                </div>


                <!-- ========================================================
                     INVENTORY SUMMARY
                ======================================================== -->

                <div class="profile-card">

                    <div class="profile-card-header">

                        <h3>
                            Inventory History Summary
                        </h3>

                        <p>
                            Activity recorded before the product was archived
                        </p>

                    </div>


                    <div class="profile-card-body">

                        <div class="stats-grid">


                            <!-- STOCK -->

                            <div class="stat-card">

                                <div class="stat-top">

                                    <div>
                                        <strong>Stock</strong>
                                    </div>

                                    <div class="stat-icon">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                    </div>

                                </div>

                                <div class="stat-number">
                                    <?= number_format($stockRecords) ?>
                                </div>

                                <div class="stat-label">
                                    Stock records
                                </div>

                                <div class="stat-total">
                                    Total quantity:
                                    <strong>
                                        <?= number_format($stockQuantity) ?>
                                    </strong>
                                </div>

                            </div>


                            <!-- STOCK IN -->

                            <div class="stat-card">

                                <div class="stat-top">

                                    <div>
                                        <strong>Stock In</strong>
                                    </div>

                                    <div class="stat-icon">
                                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    </div>

                                </div>

                                <div class="stat-number">
                                    <?= number_format($stockInRecords) ?>
                                </div>

                                <div class="stat-label">
                                    Stock-in records
                                </div>

                                <div class="stat-total">
                                    Total quantity:
                                    <strong>
                                        <?= number_format($stockInQuantity) ?>
                                    </strong>
                                </div>

                            </div>


                            <!-- MOVEMENTS -->

                            <div class="stat-card">

                                <div class="stat-top">

                                    <div>
                                        <strong>Movements</strong>
                                    </div>

                                    <div class="stat-icon">
                                        <i class="fa-solid fa-right-left"></i>
                                    </div>

                                </div>

                                <div class="stat-number">
                                    <?= number_format($movementRecords) ?>
                                </div>

                                <div class="stat-label">
                                    Stock movement records
                                </div>

                                <div class="stat-total">
                                    Total quantity moved:
                                    <strong>
                                        <?= number_format($movementQuantity) ?>
                                    </strong>
                                </div>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- ========================================================
                     PRODUCT INFORMATION
                ======================================================== -->

                <div class="profile-card">

                    <div class="profile-card-header">

                        <h3>
                            Product Information
                        </h3>

                        <p>
                            Original product details
                        </p>

                    </div>


                    <div class="profile-card-body">

                        <div class="details-grid">


                            <div class="detail-item">

                                <span class="detail-label">
                                    Product Name
                                </span>

                                <div class="detail-value">
                                    <?= e($product['name']) ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Product ID
                                </span>

                                <div class="detail-value">
                                    #<?= (int)$product['product_id'] ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Slug
                                </span>

                                <div class="detail-value">
                                    <?= e($product['slug']) ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Barcode
                                </span>

                                <div class="detail-value">
                                    <?= e($product['barcode']) ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Category
                                </span>

                                <div class="detail-value">
                                    <?= e($product['category_name'] ?? '-') ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Stock Type
                                </span>

                                <div class="detail-value">
                                    <?= e($product['stock_type']) ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Unit
                                </span>

                                <div class="detail-value">
                                    <?= e($unit_name) ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Cost Price
                                </span>

                                <div class="detail-value price">
                                    <?= moneyValue($product['cost_price']) ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Selling Price
                                </span>

                                <div class="detail-value price">
                                    <?= moneyValue($product['selling_price']) ?>
                                </div>

                            </div>


                        </div>


                        <div class="description-box">

                            <span class="detail-label">
                                Description
                            </span>

                            <div class="detail-value">
                                <?= !empty($product['description'])
                                    ? e($product['description'])
                                    : 'No description was provided for this product.'
                                ?>
                            </div>

                        </div>

                    </div>

                </div>


                <!-- ========================================================
                     ARCHIVE INFORMATION
                ======================================================== -->

                <div class="profile-card">

                    <div class="profile-card-header">

                        <h3>
                            Archive Information
                        </h3>

                        <p>
                            Information about when and by whom the product was deleted
                        </p>

                    </div>


                    <div class="profile-card-body">

                        <div class="archive-info-grid">


                            <div class="archive-info">

                                <div class="archive-info-label">
                                    Product Created
                                </div>

                                <div class="archive-info-value">
                                    <?= formatDateValue($product['created_at']) ?>
                                </div>

                            </div>


                            <div class="archive-info">

                                <div class="archive-info-label">
                                    Product Deleted
                                </div>

                                <div class="archive-info-value">
                                    <?= formatDateValue($product['deleted_at']) ?>
                                </div>

                            </div>


                            <div class="archive-info">

                                <div class="archive-info-label">
                                    Deleted By
                                </div>


                                <div class="archive-info-value">

                                    <div class="deleted-person">

                                        <div class="deleted-person-icon">
                                            <i class="fa-solid fa-user"></i>
                                        </div>


                                        <div class="deleted-person-details">

                                            <div>
                                                <?= e(
                                                    $deletedByUsername ?: 'User unavailable'
                                                ) ?>
                                            </div>


                                            <?php if ($deletedByEmail): ?>

                                                <div
                                                    class="deleted-person-email"
                                                >
                                                    <?= e($deletedByEmail) ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <div class="archive-info">

                                <div class="archive-info-label">
                                    Deleted User ID
                                </div>

                                <div class="archive-info-value">
                                    <?= e($product['deleted_by']) ?>
                                </div>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- ========================================================
                     STOCK HISTORY
                ======================================================== -->

                <div class="profile-card">

                    <div class="profile-card-header">

                        <h3>
                            Stocking History
                        </h3>

                        <p>
                            Every archived stock record associated with this product
                        </p>

                    </div>


                    <div class="profile-card-body">


                        <div class="history-summary">

                            <span class="history-pill">
                                <i class="fa-solid fa-boxes-stacked"></i>
                                <?= number_format($stockRecords) ?> records
                            </span>

                            <span class="history-pill">
                                <i class="fa-solid fa-cubes"></i>
                                <?= number_format($stockQuantity) ?> total quantity
                            </span>

                        </div>


                        <?php if ($stockHistory): ?>

                            <div class="history-list">

                                <?php foreach ($stockHistory as $item): ?>

                                    <div class="history-item">

                                        <div class="history-icon">
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                        </div>


                                        <div class="history-main">

                                            <div class="history-title">

                                                Stock Record
                                                #<?= (int)$item['stock_id'] ?>

                                            </div>


                                            <div class="history-meta">

                                                <span>
                                                    <strong>Supplier:</strong>
                                                    <?= e($item['supplier'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Location:</strong>
                                                    <?= e($item['location_name'] ?? $item['location_id'] ?? '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Stocked by:</strong>
                                                    <?= e($item['stocked_by'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Date:</strong>
                                                    <?= formatDateValue($item['created_at']) ?>
                                                </span>

                                            </div>

                                        </div>


                                        <div class="history-quantity">

                                            <strong>
                                                <?= number_format((int)$item['quantity']) ?>
                                            </strong>

                                            <small>
                                                quantity
                                            </small>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="no-history">

                                <i class="fa-solid fa-box-open"></i>

                                No archived stock records were found for this product.

                            </div>

                        <?php endif; ?>


                    </div>

                </div>


                <!-- ========================================================
                     STOCK-IN HISTORY
                ======================================================== -->

                <div class="profile-card">

                    <div class="profile-card-header">

                        <h3>
                            Stock-In History
                        </h3>

                        <p>
                            Every stock-in transaction associated with this product
                        </p>

                    </div>


                    <div class="profile-card-body">


                        <div class="history-summary">

                            <span class="history-pill">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                <?= number_format($stockInRecords) ?> stock-in records
                            </span>

                            <span class="history-pill">
                                <i class="fa-solid fa-cubes"></i>
                                <?= number_format($stockInQuantity) ?> total quantity
                            </span>

                        </div>


                        <?php if ($stockInHistory): ?>

                            <div class="history-list">

                                <?php foreach ($stockInHistory as $item): ?>

                                    <div class="history-item">

                                        <div class="history-icon">
                                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                        </div>


                                        <div class="history-main">

                                            <div class="history-title">

                                                Stock-In
                                                #<?= (int)$item['stock_in_id'] ?>

                                            </div>


                                            <div class="history-meta">

                                                <span>
                                                    <strong>Transfer:</strong>
                                                    <?= e($item['transfer_ID'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Type:</strong>
                                                    <?= e($item['type'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Supplier:</strong>
                                                    <?= e($item['supplier'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Location:</strong>
                                                    <?= e($item['location_name'] ?? $item['location_id'] ?? '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Recorded by:</strong>
                                                    <?= e($item['recorded_by']) ?>
                                                </span>

                                                <span>
                                                    <strong>Date:</strong>
                                                    <?= formatDateValue($item['created_at']) ?>
                                                </span>

                                            </div>

                                        </div>


                                        <div class="history-quantity">

                                            <strong>
                                                <?= number_format((int)$item['quantity']) ?>
                                            </strong>

                                            <small>
                                                quantity
                                            </small>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="no-history">

                                <i class="fa-solid fa-arrow-right-to-bracket"></i>

                                No archived stock-in records were found for this product.

                            </div>

                        <?php endif; ?>


                    </div>

                </div>


                <!-- ========================================================
                     STOCK MOVEMENT HISTORY
                ======================================================== -->

                <div class="profile-card">

                    <div class="profile-card-header">

                        <h3>
                            Stock Movement History
                        </h3>

                        <p>
                            Every archived movement associated with this product
                        </p>

                    </div>


                    <div class="profile-card-body">


                        <div class="history-summary">

                            <span class="history-pill">
                                <i class="fa-solid fa-right-left"></i>
                                <?= number_format($movementRecords) ?> movement records
                            </span>

                            <span class="history-pill">
                                <i class="fa-solid fa-cubes"></i>
                                <?= number_format($movementQuantity) ?> total quantity moved
                            </span>

                        </div>


                        <?php if ($movementHistory): ?>

                            <div class="history-list">

                                <?php foreach ($movementHistory as $item): ?>

                                    <div class="history-item">

                                        <div class="history-icon">
                                            <i class="fa-solid fa-right-left"></i>
                                        </div>


                                        <div class="history-main">

                                            <div class="history-title">

                                                Movement
                                                #<?= (int)$item['stock_movement_id'] ?>

                                            </div>


                                            <div class="history-meta">

                                                <span>
                                                    <strong>From:</strong>
                                                    <?= e($item['from_location_name'] ?? $item['from_location_id'] ?? '-') ?>
                                                </span>

                                                <span>
                                                    <strong>To:</strong>
                                                    <?= e($item['to_location_name'] ?? $item['to_location_id'] ?? '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Transfer:</strong>
                                                    <?= e($item['transfer_ID'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Type:</strong>
                                                    <?= e($item['movement_type'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Reference:</strong>
                                                    <?= e($item['reference'] ?: '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Created by:</strong>
                                                    <?= e($item['created_by'] ?? '-') ?>
                                                </span>

                                                <span>
                                                    <strong>Date:</strong>
                                                    <?= formatDateValue($item['created_at']) ?>
                                                </span>

                                            </div>

                                        </div>


                                        <div class="history-quantity">

                                            <strong>
                                                <?= number_format((int)$item['quantity']) ?>
                                            </strong>

                                            <small>
                                                quantity moved
                                            </small>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="no-history">

                                <i class="fa-solid fa-right-left"></i>

                                No archived stock movements were found for this product.

                            </div>

                        <?php endif; ?>


                    </div>

                </div>


                <!-- ========================================================
                     FINAL RECOVER AREA
                ======================================================== -->

                <div class="profile-card">

                    <div class="profile-card-body">

                        <div
                            style="
                                display:flex;
                                justify-content:space-between;
                                align-items:center;
                                gap:15px;
                                flex-wrap:wrap;
                            "
                        >

                            <div>

                                <strong
                                    style="
                                        display:block;
                                        color:#1f2937;
                                        font-size:15px;
                                    "
                                >
                                    Product is currently archived
                                </strong>

                                <span
                                    style="
                                        display:block;
                                        margin-top:4px;
                                        color:#9ca3af;
                                        font-size:12px;
                                    "
                                >
                                    Recovering it will restore the archived
                                    product and its associated inventory records.
                                </span>

                            </div>


                            <form
                                action="recover_product.php"
                                method="POST"
                                style="margin:0;"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $product_id ?>"
                                >

                                <button
                                    type="submit"
                                    class="profile-btn recover"
                                    onclick="return confirm('Are you sure you want to recover this product and all of its archived inventory history?')"
                                >

                                    <i class="fa-solid fa-rotate-left"></i>

                                    Recover Product

                                </button>

                            </form>

                        </div>

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

<script
    defer
    src="bundle.js"
></script>

</body>

</html>
