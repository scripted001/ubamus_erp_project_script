<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


// ============================================================
// COMPANY
// ============================================================

$company_id = (int)($_SESSION['company_id'] ?? 0);

if ($company_id <= 0) {

    echo "<script>
        alert('Invalid company session.');
        window.location.href='stocks.php';
    </script>";

    exit;
}


// ============================================================
// FETCH ARCHIVED STOCKS
// ============================================================

try {

    $stmt = $pdo->prepare("
        SELECT

            /* ====================================================
               DELETED STOCK RECORD
            ==================================================== */

            ds.id AS deleted_stock_id,

            ds.stock_id,

            ds.product_id,

            ds.company_id,

            ds.location_id,

            ds.quantity,

            ds.supplier,

            ds.created_at,

            ds.stocked_by,

            ds.deleted_by,

            ds.deleted_at,


            /* ====================================================
               PRODUCT

               First try current products.
               If product was deleted, use deleted_products.
            ==================================================== */

            COALESCE(
                p.name,
                dp.name
            ) AS product_name,


            /* ====================================================
               CATEGORY
            ==================================================== */

            COALESCE(
                pc.name,
                dpc.name
            ) AS category_name,


            /* ====================================================
               LOCATION
            ==================================================== */

            l.name AS location_name,


            /* ====================================================
               SUPPLIER / VENDOR
            ==================================================== */

            v.business_name AS supplier_name,


            /* ====================================================
               USER WHO DELETED / ARCHIVED THE STOCK
            ==================================================== */

            u.id AS deleted_user_id,

            u.email AS deleted_by_email,

            u.username AS deleted_by_username


        FROM deleted_stocks ds


        /* ========================================================
           CURRENT PRODUCT
        ======================================================== */

        LEFT JOIN products p

            ON p.id = ds.product_id

            AND p.company_id = ds.company_id


        /* ========================================================
           DELETED PRODUCT
        ======================================================== */

        LEFT JOIN deleted_products dp

            ON dp.product_id = ds.product_id

            AND dp.company_id = ds.company_id


        /* ========================================================
           CURRENT PRODUCT CATEGORY
        ======================================================== */

        LEFT JOIN product_categories pc

            ON pc.id = p.category_id


        /* ========================================================
           DELETED PRODUCT CATEGORY
        ======================================================== */

        LEFT JOIN product_categories dpc

            ON dpc.id = dp.category_id


        /* ========================================================
           LOCATION
        ======================================================== */

        LEFT JOIN locations l

            ON l.id = ds.location_id

            AND l.company_id = ds.company_id


        /* ========================================================
           SUPPLIER / VENDOR

           deleted_stocks.supplier is stored as VARCHAR.

           It normally contains the vendor ID.
        ======================================================== */

        LEFT JOIN vendors v

            ON v.id = CAST(ds.supplier AS UNSIGNED)

            AND v.company_id = ds.company_id


        /* ========================================================
           USER WHO DELETED THE STOCK

           deleted_by is VARCHAR(100).

           To make the archive robust, we support:

           1. User ID
           2. User email
           3. Username

           Therefore the page will still identify the user
           regardless of which value your delete script stored.
        ======================================================== */

        LEFT JOIN users u

            ON u.company_id = ds.company_id

            AND (

                u.id = CAST(ds.deleted_by AS UNSIGNED)

                OR

                u.email = ds.deleted_by

                OR

                u.username = ds.deleted_by

            )


        /* ========================================================
           COMPANY SECURITY
        ======================================================== */

        WHERE ds.company_id = ?


        /* ========================================================
           NEWEST ARCHIVED RECORD FIRST
        ======================================================== */

        ORDER BY

            CASE

                WHEN NULLIF(ds.deleted_at, '') IS NOT NULL

                THEN ds.deleted_at

                ELSE CONCAT(
                    '1900-01-01 ',
                    LPAD(ds.id, 10, '0')
                )

            END DESC,

            ds.id DESC

    ");

    $stmt->execute([
        $company_id
    ]);

    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (Throwable $e) {

    die(

        "<h3>Error loading archived stocks:</h3>" .

        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )

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
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>

        <?= htmlspecialchars(
            $company_name ?? 'Company',
            ENT_QUOTES,
            'UTF-8'
        ) ?>

        | Archived Stocks

    </title>


    <!-- ========================================================
         FONT AWESOME
    ========================================================= -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >


    <!-- ========================================================
         EXISTING STYLES
    ========================================================= -->

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
           GLOBAL RESPONSIVE FIX
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


        /* ============================================================
           PAGE WIDTH
        ============================================================ */

        .archive-page-container {

            width: 100%;

            max-width: 100%;

            min-width: 0;

        }


        .archive-card {

            width: 100%;

            max-width: 100%;

            min-width: 0;

            overflow: hidden;

        }


        .archive-content {

            width: 100%;

            max-width: 100%;

            min-width: 0;

        }


        /* ============================================================
           ARCHIVE BADGE
        ============================================================ */

        .archive-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 5px 10px;

            border-radius: 999px;

            background: rgba(220, 38, 38, 0.10);

            color: #dc2626;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;

        }


        /* ============================================================
           STOCK ID
        ============================================================ */

        .stock-id {

            margin-top: 3px;

            font-size: 12px;

            color: #9ca3af;

            white-space: nowrap;

            line-height: 1.6;

        }


        /* ============================================================
           QUANTITY BADGE
        ============================================================ */

        .stock-quantity {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 55px;

            height: 30px;

            padding: 0 10px;

            border-radius: 7px;

            background: #f3f4f6;

            color: #374151;

            font-size: 13px;

            font-weight: 600;

            white-space: nowrap;

        }


        /* ============================================================
           DELETED USER
        ============================================================ */

        .deleted-user {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            max-width: 240px;

            font-size: 13px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .deleted-user i {

            color: #9ca3af;

            flex-shrink: 0;

        }


        /* ============================================================
           DELETED USER DETAILS
        ============================================================ */

        .deleted-user-details {

            display: flex;

            flex-direction: column;

            gap: 2px;

            min-width: 0;

        }


        .deleted-user-name {

            font-size: 13px;

            font-weight: 600;

            color: inherit;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .deleted-user-email {

            font-size: 11px;

            color: #9ca3af;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        /* ============================================================
           PRODUCT NAME
        ============================================================ */

        .archive-product-name {

            min-width: 180px;

            max-width: 240px;

            white-space: normal;

            word-break: break-word;

        }


        /* ============================================================
           SUPPLIER
        ============================================================ */

        .archive-supplier {

            min-width: 150px;

            max-width: 220px;

            white-space: normal;

            word-break: break-word;

        }


        /* ============================================================
           LOCATION
        ============================================================ */

        .archive-location {

            min-width: 130px;

            max-width: 180px;

            white-space: normal;

            word-break: break-word;

        }


        /* ============================================================
           CATEGORY
        ============================================================ */

        .archive-category {

            min-width: 120px;

            max-width: 180px;

            white-space: normal;

            word-break: break-word;

        }


        /* ============================================================
           ACTIONS
        ============================================================ */

        .archive-actions {

            min-width: 190px;

            white-space: nowrap;

        }


        .archive-actions form {

            display: inline-block;

            margin: 0;

        }


        /* ============================================================
           STOCK ICON
        ============================================================ */

        .archive-stock-icon {

            width: 45px;

            height: 45px;

            border-radius: 8px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f3f4f6;

            overflow: hidden;

        }


        .archive-stock-icon i {

            color: #9ca3af;

            font-size: 18px;

        }


        /* ============================================================
           RECOVER BUTTON
        ============================================================ */

        .btn-action.recover {

            background: #16a34a;

            color: #ffffff;

            border: none;

            cursor: pointer;

            white-space: nowrap;

        }


        .btn-action.recover:hover {

            background: #15803d;

        }


        /* ============================================================
           SEARCH
        ============================================================ */

        .archive-search {

            width: 100%;

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 15px;

            flex-wrap: wrap;

        }


        .archive-search select {

            width: 220px;

            max-width: 100%;

        }


        .archive-search input {

            flex: 1;

            min-width: 220px;

            max-width: 100%;

        }


        /* ============================================================
           TABLE WRAPPER
        ============================================================ */

        .archive-table-wrapper {

            width: 100%;

            max-width: 100%;

            min-width: 0;

            overflow-x: auto;

            overflow-y: hidden;

            -webkit-overflow-scrolling: touch;

            overscroll-behavior-x: contain;

            scrollbar-width: thin;

            border-radius: 10px;

        }


        /* ============================================================
           TABLE
        ============================================================ */

        .archive-table-wrapper .product-table {

            width: 100%;

            min-width: 1250px;

            border-collapse: collapse;

            table-layout: auto;

        }


        /* ============================================================
           CELLS
        ============================================================ */

        .archive-table-wrapper .product-table th,
        .archive-table-wrapper .product-table td {

            white-space: nowrap;

            vertical-align: middle;

        }


        /* ============================================================
           EMPTY STATE
        ============================================================ */

        .empty-archive {

            padding: 50px 20px;

            text-align: center;

            color: #9ca3af;

        }


        .empty-archive i {

            display: block;

            font-size: 40px;

            margin-bottom: 12px;

        }


        /* ============================================================
           SCROLLBAR
        ============================================================ */

        .archive-table-wrapper::-webkit-scrollbar {

            height: 8px;

        }


        .archive-table-wrapper::-webkit-scrollbar-track {

            background: #f1f1f1;

            border-radius: 10px;

        }


        .archive-table-wrapper::-webkit-scrollbar-thumb {

            background: #c5c5c5;

            border-radius: 10px;

        }


        .archive-table-wrapper::-webkit-scrollbar-thumb:hover {

            background: #999;

        }


        /* ============================================================
           DARK MODE
        ============================================================ */

        .dark .stock-quantity {

            background: rgba(255,255,255,0.08);

            color: #e5e7eb;

        }


        .dark .archive-stock-icon {

            background: rgba(255,255,255,0.08);

        }


        .dark .stock-id {

            color: #9ca3af;

        }


        .dark .deleted-user-email {

            color: #9ca3af;

        }


        .dark .archive-table-wrapper::-webkit-scrollbar-track {

            background: #1f2937;

        }


        .dark .archive-table-wrapper::-webkit-scrollbar-thumb {

            background: #4b5563;

        }


        /* ============================================================
           TABLET
        ============================================================ */

        @media (max-width: 900px) {

            .archive-table-wrapper .product-table {

                min-width: 1150px;

            }

        }


        /* ============================================================
           MOBILE
        ============================================================ */

        @media (max-width: 640px) {

            .archive-search {

                flex-direction: column;

                align-items: stretch;

                gap: 8px;

            }


            .archive-search select,
            .archive-search input {

                width: 100%;

                min-width: 0;

                max-width: 100%;

            }


            .archive-table-wrapper {

                margin-left: 0;

                margin-right: 0;

                width: 100%;

                border-radius: 8px;

            }


            .archive-table-wrapper .product-table {

                min-width: 1100px;

            }


            .deleted-user {

                max-width: 180px;

            }


            .archive-product-name {

                max-width: 180px;

            }


            .archive-card {

                border-radius: 14px;

            }

        }


        /* ============================================================
           VERY SMALL PHONES
        ============================================================ */

        @media (max-width: 400px) {

            .archive-table-wrapper .product-table {

                min-width: 1050px;

            }


            .archive-badge {

                margin-top: 5px;

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


<!-- ============================================================
     PAGE WRAPPER
============================================================ -->

<div class="flex h-screen overflow-hidden">


    <!-- ============================================================
         SIDEBAR
    ============================================================ -->

    <?php include('sidebar.php'); ?>


    <!-- ============================================================
         CONTENT AREA
    ============================================================ -->

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
        style="min-width:0;"
    >


        <!-- ========================================================
             MAIN
        ======================================================== -->

        <main style="min-width:0; width:100%;">


            <!-- ====================================================
                 HEADER
            ==================================================== -->

            <?php include('header.php'); ?>


            <!-- ====================================================
                 PAGE CONTAINER
            ==================================================== -->

            <div
                class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6 archive-page-container"
            >


                <div
                    class="grid grid-cols-6 gap-4 md:gap-6"
                    style="min-width:0;"
                >


                    <div
                        class="col-span-12 space-y-6 xl:col-span-7"
                        style="min-width:0;"
                    >


                        <!-- ==================================================
                             ARCHIVED STOCKS CARD
                        ================================================== -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] archive-card"
                        >


                            <!-- ==================================================
                                 HEADER
                            ================================================== -->

                            <div
                                class="px-5 py-4 sm:px-6 sm:py-5"
                            >


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


                                        <h3
                                            class="text-base font-medium text-gray-800 dark:text-white/90"
                                        >

                                            Archived Stocks

                                        </h3>


                                        <p
                                            style="
                                                margin-top:4px;
                                                font-size:13px;
                                                color:#9ca3af;
                                            "
                                        >

                                            Stock records deleted from your company


                                            <span
                                                class="archive-badge"
                                            >

                                                <i
                                                    class="fa-solid fa-box-archive"
                                                ></i>

                                                Archive

                                            </span>

                                        </p>


                                    </div>


                                    <!-- ==================================================
                                         BACK TO STOCKS
                                    ================================================== -->

                                    <a
                                        href="stocks.php"
                                        class="btn-action view"
                                    >

                                        <i
                                            class="fa-solid fa-boxes-stacked"
                                        ></i>

                                        Stocks

                                    </a>


                                </div>


                            </div>


                            <!-- ==================================================
                                 CONTENT
                            ================================================== -->

                            <div
                                class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800 archive-content"
                            >


                                <!-- ==================================================
                                     SEARCH
                                ================================================== -->

                                <div
                                    class="archive-search"
                                >


                                    <select
                                        id="search_option"
                                        class="form-control"
                                    >

                                        <option value="product">
                                            Search by Product
                                        </option>

                                        <option value="category">
                                            Search by Category
                                        </option>

                                        <option value="supplier">
                                            Search by Supplier
                                        </option>

                                        <option value="location">
                                            Search by Location
                                        </option>

                                        <option value="date">
                                            Search by Archived Date
                                        </option>

                                        <option value="email">
                                            Search by Archived By
                                        </option>

                                    </select>


                                    <input
                                        type="text"
                                        id="search_value"
                                        placeholder="Search archived stocks..."
                                        class="form-control"
                                    >


                                </div>


                                <!-- ==================================================
                                     TABLE
                                ================================================== -->

                                <div
                                    class="archive-table-wrapper"
                                >


                                    <table
                                        class="product-table"
                                    >


                                        <thead>

                                            <tr>

                                                <th>#</th>

                                                <th>Stock</th>

                                                <th>Product</th>

                                                <th>Category</th>

                                                <th>Quantity</th>

                                                <th>Supplier</th>

                                                <th>Location</th>

                                                <th>Stocked</th>

                                                <th>Archived</th>

                                                <th>Archived By</th>

                                                <th>Actions</th>

                                            </tr>

                                        </thead>


                                        <tbody
                                            id="archivedStockTable"
                                        >


                                        <?php if ($stocks): ?>


                                            <?php foreach (
                                                $stocks
                                                as $index => $stock
                                            ): ?>


                                                <?php

                                                // ==================================================
                                                // PRODUCT
                                                // ==================================================

                                                $productName =
                                                    trim(
                                                        (string)(
                                                            $stock['product_name']
                                                            ?? ''
                                                        )
                                                    );

                                                if ($productName === '') {

                                                    $productName =
                                                        'Unknown Product';

                                                }


                                                // ==================================================
                                                // CATEGORY
                                                // ==================================================

                                                $categoryName =
                                                    trim(
                                                        (string)(
                                                            $stock['category_name']
                                                            ?? ''
                                                        )
                                                    );


                                                // ==================================================
                                                // SUPPLIER
                                                // ==================================================

                                                $supplier =
                                                    trim(
                                                        (string)(
                                                            $stock['supplier_name']
                                                            ?? ''
                                                        )
                                                    );


                                                // ==================================================
                                                // LOCATION
                                                // ==================================================

                                                $locationName =
                                                    trim(
                                                        (string)(
                                                            $stock['location_name']
                                                            ?? ''
                                                        )
                                                    );


                                                // ==================================================
                                                // DELETED BY
                                                // ==================================================

                                                $deletedEmail =
                                                    trim(
                                                        (string)(
                                                            $stock['deleted_by_email']
                                                            ?? ''
                                                        )
                                                    );


                                                $deletedUsername =
                                                    trim(
                                                        (string)(
                                                            $stock['deleted_by_username']
                                                            ?? ''
                                                        )
                                                    );


                                                $deletedByRaw =
                                                    trim(
                                                        (string)(
                                                            $stock['deleted_by']
                                                            ?? ''
                                                        )
                                                    );


                                                /*
                                                 * Display priority:
                                                 *
                                                 * username + email
                                                 * email
                                                 * username
                                                 * raw deleted_by value
                                                 */

                                                if (
                                                    $deletedUsername !== ''
                                                    &&
                                                    $deletedEmail !== ''
                                                ) {

                                                    $deletedByDisplay =
                                                        $deletedUsername;

                                                    $deletedBySubtext =
                                                        $deletedEmail;

                                                }

                                                elseif (
                                                    $deletedEmail !== ''
                                                ) {

                                                    $deletedByDisplay =
                                                        $deletedEmail;

                                                    $deletedBySubtext =
                                                        '';

                                                }

                                                elseif (
                                                    $deletedUsername !== ''
                                                ) {

                                                    $deletedByDisplay =
                                                        $deletedUsername;

                                                    $deletedBySubtext =
                                                        '';

                                                }

                                                elseif (
                                                    $deletedByRaw !== ''
                                                ) {

                                                    $deletedByDisplay =
                                                        'User ID: ' .
                                                        $deletedByRaw;

                                                    $deletedBySubtext =
                                                        '';

                                                }

                                                else {

                                                    $deletedByDisplay =
                                                        'User unavailable';

                                                    $deletedBySubtext =
                                                        '';

                                                }


                                                // ==================================================
                                                // STOCKED DATE
                                                // ==================================================

                                                $stockedDate = '-';


                                                if (
                                                    !empty(
                                                        $stock['created_at']
                                                    )
                                                ) {

                                                    $timestamp =
                                                        strtotime(
                                                            $stock['created_at']
                                                        );


                                                    if (
                                                        $timestamp !== false
                                                    ) {

                                                        $stockedDate =
                                                            date(
                                                                'd M Y',
                                                                $timestamp
                                                            );

                                                    }

                                                }


                                                // ==================================================
                                                // ARCHIVED DATE
                                                // ==================================================

                                                $deletedDate = '-';


                                                if (
                                                    !empty(
                                                        $stock['deleted_at']
                                                    )
                                                ) {

                                                    $timestamp =
                                                        strtotime(
                                                            $stock['deleted_at']
                                                        );


                                                    if (
                                                        $timestamp !== false
                                                    ) {

                                                        $deletedDate =
                                                            date(
                                                                'd M Y',
                                                                $timestamp
                                                            );

                                                    }

                                                }


                                                // ==================================================
                                                // SEARCH DATE
                                                // ==================================================

                                                $searchDate =
                                                    strtolower(
                                                        (string)(
                                                            $stock['deleted_at']
                                                            ?? ''
                                                        )
                                                    );


                                                // ==================================================
                                                // SEARCH USER
                                                // ==================================================

                                                $searchEmail =
                                                    strtolower(
                                                        $deletedEmail
                                                        . ' '
                                                        .
                                                        $deletedUsername
                                                        . ' '
                                                        .
                                                        $deletedByRaw
                                                    );

                                                ?>


                                                <tr

                                                    class="archive-row"


                                                    data-product="<?= htmlspecialchars(
                                                        strtolower(
                                                            $productName
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"


                                                    data-category="<?= htmlspecialchars(
                                                        strtolower(
                                                            $categoryName
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"


                                                    data-supplier="<?= htmlspecialchars(
                                                        strtolower(
                                                            $supplier
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"


                                                    data-location="<?= htmlspecialchars(
                                                        strtolower(
                                                            $locationName
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"


                                                    data-date="<?= htmlspecialchars(
                                                        $searchDate,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"


                                                    data-email="<?= htmlspecialchars(
                                                        $searchEmail,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"

                                                >


                                                    <!-- ==================================================
                                                         NUMBER
                                                    ================================================== -->

                                                    <td>

                                                        <?= $index + 1 ?>

                                                    </td>


                                                    <!-- ==================================================
                                                         STOCK ICON
                                                    ================================================== -->

                                                    <td>

                                                        <div
                                                            class="archive-stock-icon"
                                                        >

                                                            <i
                                                                class="fa-solid fa-box-archive"
                                                            ></i>

                                                        </div>

                                                    </td>


                                                    <!-- ==================================================
                                                         PRODUCT
                                                    ================================================== -->

                                                    <td
                                                        class="archive-product-name"
                                                    >

                                                        <strong>

                                                            <?= htmlspecialchars(
                                                                $productName,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>

                                                        </strong>


                                                        <div
                                                            class="stock-id"
                                                        >

                                                            Archive ID:
                                                            <?= (int)$stock['deleted_stock_id'] ?>


                                                            <br>


                                                            Stock ID:
                                                            <?= (int)$stock['stock_id'] ?>


                                                            <br>


                                                            Product ID:
                                                            <?= (int)$stock['product_id'] ?>

                                                        </div>

                                                    </td>


                                                    <!-- ==================================================
                                                         CATEGORY
                                                    ================================================== -->

                                                    <td
                                                        class="archive-category"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $categoryName ?: '-',
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </td>


                                                    <!-- ==================================================
                                                         QUANTITY
                                                    ================================================== -->

                                                    <td>

                                                        <span
                                                            class="stock-quantity"
                                                            title="Archived stock quantity"
                                                        >

                                                            <?= number_format(
                                                                (int)(
                                                                    $stock['quantity']
                                                                    ?? 0
                                                                )
                                                            ) ?>

                                                        </span>

                                                    </td>


                                                    <!-- ==================================================
                                                         SUPPLIER
                                                    ================================================== -->

                                                    <td
                                                        class="archive-supplier"
                                                    >


                                                        <?php if (
                                                            !empty(
                                                                $supplier
                                                            )
                                                        ): ?>


                                                            <span
                                                                title="<?= htmlspecialchars(
                                                                    $supplier,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                                            >

                                                                <i
                                                                    class="fa-solid fa-truck"
                                                                    style="margin-right:5px;color:#9ca3af;"
                                                                ></i>


                                                                <?= htmlspecialchars(
                                                                    $supplier,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>


                                                            </span>


                                                        <?php else: ?>


                                                            <span
                                                                style="color:#9ca3af;"
                                                            >

                                                                Supplier unavailable

                                                            </span>


                                                        <?php endif; ?>


                                                    </td>


                                                    <!-- ==================================================
                                                         LOCATION
                                                    ================================================== -->

                                                    <td
                                                        class="archive-location"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $locationName ?: '-',
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </td>


                                                    <!-- ==================================================
                                                         STOCKED DATE
                                                    ================================================== -->

                                                    <td>

                                                        <?= htmlspecialchars(
                                                            $stockedDate,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </td>


                                                    <!-- ==================================================
                                                         ARCHIVED DATE
                                                    ================================================== -->

                                                    <td>

                                                        <?= htmlspecialchars(
                                                            $deletedDate,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </td>


                                                    <!-- ==================================================
                                                         ARCHIVED BY
                                                    ================================================== -->

                                                    <td>


                                                        <span
                                                            class="deleted-user"
                                                            title="<?= htmlspecialchars(
                                                                $deletedBySubtext !== ''
                                                                    ? $deletedByDisplay . ' - ' . $deletedBySubtext
                                                                    : $deletedByDisplay,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                                        >


                                                            <i
                                                                class="fa-solid fa-user"
                                                            ></i>


                                                            <span
                                                                class="deleted-user-details"
                                                            >


                                                                <span
                                                                    class="deleted-user-name"
                                                                >

                                                                    <?= htmlspecialchars(
                                                                        $deletedByDisplay,
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>

                                                                </span>


                                                                <?php if (
                                                                    $deletedBySubtext !== ''
                                                                ): ?>


                                                                    <span
                                                                        class="deleted-user-email"
                                                                    >

                                                                        <?= htmlspecialchars(
                                                                            $deletedBySubtext,
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ) ?>

                                                                    </span>


                                                                <?php endif; ?>


                                                            </span>


                                                        </span>


                                                    </td>


                                                    <!-- ==================================================
                                                         ACTIONS
                                                    ================================================== -->

                                                    <td
                                                        class="archive-actions"
                                                    >


                                                        <!-- ==================================================
                                                             RECOVER
                                                        ================================================== -->

                                                        <form
                                                            action="stock_recover.php"
                                                            method="POST"
                                                        >


                                                            <!--
                                                                IMPORTANT:

                                                                This is deleted_stocks.id

                                                                NOT stocks.id / stock_id.

                                                                deleted_stocks.id is the primary key of
                                                                the archive record that must be recovered.
                                                            -->

                                                            <input
                                                                type="hidden"
                                                                name="deleted_id"
                                                                value="<?= (int)$stock['deleted_stock_id'] ?>"
                                                            >


                                                            <button
                                                                type="submit"
                                                                class="btn-action recover"

                                                                onclick="return confirm('Are you sure you want to recover this archived stock record?')"
                                                            >


                                                                <i
                                                                    class="fa-solid fa-rotate-left"
                                                                ></i>


                                                                Recover


                                                            </button>


                                                        </form>


                                                    </td>


                                                </tr>


                                            <?php endforeach; ?>


                                        <?php else: ?>


                                            <tr>


                                                <td
                                                    colspan="11"
                                                    class="empty-archive"
                                                >


                                                    <i
                                                        class="fa-solid fa-box-open"
                                                    ></i>


                                                    <strong>

                                                        No archived stocks

                                                    </strong>


                                                    <div
                                                        style="margin-top:5px;"
                                                    >

                                                        Deleted stock records
                                                        will appear here.

                                                    </div>


                                                </td>


                                            </tr>


                                        <?php endif; ?>


                                        </tbody>


                                    </table>


                                </div>


                            </div>


                        </div>


                    </div>


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
     CLOUDFLARE EMAIL DECODE
============================================================ -->

<script
    data-cfasync="false"
    src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
></script>


<!-- ============================================================
     BUNDLE
============================================================ -->

<script
    defer
    src="bundle.js"
></script>


<!-- ============================================================
     CLOUDFLARE INSIGHTS
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
            "name":"cfExtPri"
        }
    }'

    crossorigin="anonymous"
></script>


<!-- ============================================================
     SEARCH
============================================================ -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const searchOption =
            document.getElementById(
                'search_option'
            );


        const searchValue =
            document.getElementById(
                'search_value'
            );


        const rows =
            document.querySelectorAll(
                '.archive-row'
            );


        /* ========================================================
           FILTER ARCHIVED STOCKS
        ======================================================== */

        function filterArchivedStocks() {


            const option =
                searchOption.value;


            const value =
                searchValue.value
                    .toLowerCase()
                    .trim();


            rows.forEach(
                function (row) {


                    let searchText = '';


                    /* ==================================================
                       PRODUCT
                    ================================================== */

                    if (
                        option === 'product'
                    ) {

                        searchText =
                            row.dataset.product
                            || '';

                    }


                    /* ==================================================
                       CATEGORY
                    ================================================== */

                    else if (
                        option === 'category'
                    ) {

                        searchText =
                            row.dataset.category
                            || '';

                    }


                    /* ==================================================
                       SUPPLIER
                    ================================================== */

                    else if (
                        option === 'supplier'
                    ) {

                        searchText =
                            row.dataset.supplier
                            || '';

                    }


                    /* ==================================================
                       LOCATION
                    ================================================== */

                    else if (
                        option === 'location'
                    ) {

                        searchText =
                            row.dataset.location
                            || '';

                    }


                    /* ==================================================
                       DATE
                    ================================================== */

                    else if (
                        option === 'date'
                    ) {

                        searchText =
                            row.dataset.date
                            || '';

                    }


                    /* ==================================================
                       ARCHIVED BY
                    ================================================== */

                    else if (
                        option === 'email'
                    ) {

                        searchText =
                            row.dataset.email
                            || '';

                    }


                    /* ==================================================
                       DISPLAY
                    ================================================== */

                    if (

                        value === '' ||

                        searchText.includes(
                            value
                        )

                    ) {

                        row.style.display = '';

                    }

                    else {

                        row.style.display = 'none';

                    }

                }
            );

        }


        /* ========================================================
           SEARCH INPUT
        ======================================================== */

        searchValue.addEventListener(
            'input',
            filterArchivedStocks
        );


        /* ========================================================
           SEARCH OPTION
        ======================================================== */

        searchOption.addEventListener(
            'change',
            filterArchivedStocks
        );


    }
);

</script>


</body>

</html>
