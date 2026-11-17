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
        window.location.href='products.php';
    </script>";
    exit;
}


// ============================================================
// FETCH ARCHIVED PRODUCTS
// ============================================================

try {

    $stmt = $pdo->prepare("
        SELECT
            dp.*,

            pc.name AS category_name,

            u.email AS deleted_by_email,

            /* Number of archived stock records */
            (
                SELECT COUNT(*)
                FROM deleted_stocks ds
                WHERE ds.product_id = dp.product_id
                AND ds.company_id = dp.company_id
            ) AS total_stock,

            /* Number of archived stock-in records */
            (
                SELECT COUNT(*)
                FROM deleted_stock_in dsi
                WHERE dsi.product_id = dp.product_id
                AND dsi.company_id = dp.company_id
            ) AS total_stock_in,

            /* Number of archived stock movement records */
            (
                SELECT COUNT(*)
                FROM deleted_stock_movement dsm
                WHERE dsm.product_id = dp.product_id
                AND dsm.company_id = dp.company_id
            ) AS total_movements

        FROM deleted_products dp

        LEFT JOIN product_categories pc
            ON dp.category_id = pc.id

        LEFT JOIN users u
            ON u.id = CAST(dp.deleted_by AS UNSIGNED)
            AND u.company_id = dp.company_id

        WHERE dp.company_id = ?

        ORDER BY dp.deleted_at DESC
    ");

    $stmt->execute([$company_id]);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    die(
        "<h3>Error loading archived products:</h3>" .
        htmlspecialchars($e->getMessage())
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
        <?= htmlspecialchars($company_name) ?> | Archived Products
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >


    <!-- Existing styles -->

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
           IMPORTANT GLOBAL RESPONSIVE FIX
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
           CONTENT WIDTH FIX
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
           PRODUCT ID
        ============================================================ */

        .product-id {
            margin-top: 3px;

            font-size: 12px;

            color: #9ca3af;

            white-space: nowrap;
        }


        /* ============================================================
           STAT BADGES
        ============================================================ */

        .archive-stat {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-width: 36px;
            height: 28px;

            padding: 0 8px;

            border-radius: 7px;

            background: #f3f4f6;

            color: #374151;

            font-size: 12px;
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

            max-width: 220px;

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
           SEARCH AREA
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
           TABLE SCROLL CONTAINER
           
           THIS IS THE MOST IMPORTANT PART
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
           
           Do NOT allow the table to squeeze itself on mobile.
           Instead, give it a minimum width and let the wrapper
           scroll horizontally.
        ============================================================ */

        .archive-table-wrapper .product-table {

            width: 100%;

            min-width: 1250px;

            border-collapse: collapse;

            table-layout: auto;
        }


        /* ============================================================
           TABLE CELLS
        ============================================================ */

        .archive-table-wrapper .product-table th,
        .archive-table-wrapper .product-table td {

            white-space: nowrap;

            vertical-align: middle;
        }


        /* ============================================================
           PRODUCT NAME
        ============================================================ */

        .archive-product-name {

            min-width: 170px;

            max-width: 220px;

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
           PRODUCT ICON
        ============================================================ */

        .archive-product-icon {

            width: 45px;

            height: 45px;

            border-radius: 8px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f3f4f6;

            overflow: hidden;
        }


        .archive-product-icon i {

            color: #9ca3af;

            font-size: 18px;
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

        .dark .archive-stat {

            background: rgba(255,255,255,0.08);

            color: #e5e7eb;
        }


        .dark .archive-product-icon {

            background: rgba(255,255,255,0.08);
        }


        .dark .product-id {

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


    <!-- SIDEBAR -->

    <?php include('sidebar.php'); ?>


    <!-- ========================================================
         CONTENT AREA
    ======================================================== -->

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
        style="min-width:0;"
    >


        <!-- ====================================================
             MAIN CONTENT
        ==================================================== -->

        <main style="min-width:0; width:100%;">


            <!-- HEADER -->

            <?php include('header.php'); ?>


            <!-- ==================================================
                 PAGE CONTAINER
            ================================================== -->

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
                             ARCHIVED PRODUCTS CARD
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

                                            Archived Products

                                        </h3>


                                        <p
                                            style="
                                                margin-top:4px;
                                                font-size:13px;
                                                color:#9ca3af;
                                            "
                                        >

                                            Products deleted from your company

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


                                    <!-- BACK TO PRODUCTS -->

                                    <a
                                        href="products.php"
                                        class="btn-action view"
                                    >

                                        <i
                                            class="fa-solid fa-boxes-stacked"
                                        ></i>

                                        Products

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

                                        <option value="name">
                                            Search by Name
                                        </option>

                                        <option value="category">
                                            Search by Category
                                        </option>

                                        <option value="date">
                                            Search by Deleted Date
                                        </option>

                                        <option value="email">
                                            Search by Deleted By
                                        </option>

                                    </select>


                                    <input
                                        type="text"
                                        id="search_value"
                                        placeholder="Search archived products..."
                                        class="form-control"
                                    >


                                </div>


                                <!-- ==================================================
                                     RESPONSIVE TABLE WRAPPER
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

                                                <th>Product</th>

                                                <th>Name</th>

                                                <th>Category</th>

                                                <th>Stock</th>

                                                <th>Stock In</th>

                                                <th>Movements</th>

                                                <th>Created</th>

                                                <th>Deleted</th>

                                                <th>Deleted By</th>

                                                <th>Actions</th>

                                            </tr>

                                        </thead>


                                        <tbody
                                            id="archivedProductTable"
                                        >


                                        <?php if ($products): ?>


                                            <?php foreach (
                                                $products
                                                as $index => $product
                                            ): ?>


                                                <tr
                                                    class="archive-row"

                                                    data-name="<?= htmlspecialchars(
                                                        strtolower(
                                                            $product['name']
                                                        )
                                                    ) ?>"

                                                    data-category="<?= htmlspecialchars(
                                                        strtolower(
                                                            $product['category_name'] ?? ''
                                                        )
                                                    ) ?>"

                                                    data-date="<?= htmlspecialchars(
                                                        strtolower(
                                                            $product['deleted_at']
                                                        )
                                                    ) ?>"

                                                    data-email="<?= htmlspecialchars(
                                                        strtolower(
                                                            $product['deleted_by_email'] ?? ''
                                                        )
                                                    ) ?>"
                                                >


                                                    <!-- NUMBER -->

                                                    <td>

                                                        <?= $index + 1 ?>

                                                    </td>


                                                    <!-- PRODUCT -->

                                                    <td>

                                                        <div
                                                            class="archive-product-icon"
                                                        >

                                                            <i
                                                                class="fa-solid fa-box-archive"
                                                            ></i>

                                                        </div>

                                                    </td>


                                                    <!-- NAME -->

                                                    <td
                                                        class="archive-product-name"
                                                    >

                                                        <strong>

                                                            <?= htmlspecialchars(
                                                                $product['name']
                                                            ) ?>

                                                        </strong>


                                                        <div
                                                            class="product-id"
                                                        >

                                                            Product ID:
                                                            <?= (int)$product['product_id'] ?>

                                                        </div>

                                                    </td>


                                                    <!-- CATEGORY -->

                                                    <td
                                                        class="archive-category"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $product['category_name'] ?? '-'
                                                        ) ?>

                                                    </td>


                                                    <!-- STOCK -->

                                                    <td>

                                                        <span
                                                            class="archive-stat"
                                                            title="Archived stock records"
                                                        >

                                                            <?= (int)$product['total_stock'] ?>

                                                        </span>

                                                    </td>


                                                    <!-- STOCK IN -->

                                                    <td>

                                                        <span
                                                            class="archive-stat"
                                                            title="Archived stock-in records"
                                                        >

                                                            <?= (int)$product['total_stock_in'] ?>

                                                        </span>

                                                    </td>


                                                    <!-- MOVEMENTS -->

                                                    <td>

                                                        <span
                                                            class="archive-stat"
                                                            title="Archived stock movements"
                                                        >

                                                            <?= (int)$product['total_movements'] ?>

                                                        </span>

                                                    </td>


                                                    <!-- CREATED -->

                                                    <td>

                                                        <?php

                                                        $createdDate = '-';

                                                        if (
                                                            !empty(
                                                                $product['created_at']
                                                            )
                                                        ) {

                                                            $timestamp =
                                                                strtotime(
                                                                    $product['created_at']
                                                                );

                                                            if (
                                                                $timestamp !== false
                                                            ) {

                                                                $createdDate =
                                                                    date(
                                                                        'd M Y',
                                                                        $timestamp
                                                                    );

                                                            }

                                                        }

                                                        ?>

                                                        <?= htmlspecialchars(
                                                            $createdDate
                                                        ) ?>

                                                    </td>


                                                    <!-- DELETED -->

                                                    <td>

                                                        <?php

                                                        $deletedDate = '-';

                                                        if (
                                                            !empty(
                                                                $product['deleted_at']
                                                            )
                                                        ) {

                                                            $timestamp =
                                                                strtotime(
                                                                    $product['deleted_at']
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

                                                        ?>

                                                        <?= htmlspecialchars(
                                                            $deletedDate
                                                        ) ?>

                                                    </td>


                                                    <!-- DELETED BY -->

                                                    <td>

                                                        <?php if (
                                                            !empty(
                                                                $product['deleted_by_email']
                                                            )
                                                        ): ?>


                                                            <span
                                                                class="deleted-user"
                                                                title="<?= htmlspecialchars(
                                                                    $product['deleted_by_email']
                                                                ) ?>"
                                                            >

                                                                <i
                                                                    class="fa-solid fa-user"
                                                                ></i>

                                                                <?= htmlspecialchars(
                                                                    $product['deleted_by_email']
                                                                ) ?>

                                                            </span>


                                                        <?php else: ?>


                                                            <span
                                                                style="color:#9ca3af;"
                                                            >

                                                                User unavailable

                                                            </span>


                                                        <?php endif; ?>

                                                    </td>


                                                    <!-- ACTIONS -->

                                                    <td
                                                        class="archive-actions"
                                                    >


                                                        <!-- VIEW -->

                                                        <a
                                                            href="view_deleted_product.php?id=<?= (int)$product['product_id'] ?>"
                                                            class="btn-action view"
                                                        >

                                                            <i
                                                                class="fa-solid fa-eye"
                                                            ></i>

                                                            View

                                                        </a>


                                                        <!-- RECOVER -->

                                                        <form
                                                            action="recover_product.php"
                                                            method="POST"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="product_id"
                                                                value="<?= (int)$product['product_id'] ?>"
                                                            >


                                                            <button
                                                                type="submit"
                                                                class="btn-action recover"
                                                                onclick="return confirm('Are you sure you want to recover this product and all its archived stock records?')"
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
                                                        No archived products
                                                    </strong>


                                                    <div
                                                        style="margin-top:5px;"
                                                    >

                                                        Deleted products will
                                                        appear here.

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
     SCRIPTS
============================================================ -->

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
            "name":"cfExtPri"
        }
    }'
    crossorigin="anonymous"
></script>


<!-- ============================================================
     SEARCH
============================================================ -->

<script>

document.addEventListener('DOMContentLoaded', function () {


    const searchOption =
        document.getElementById('search_option');


    const searchValue =
        document.getElementById('search_value');


    const rows =
        document.querySelectorAll('.archive-row');


    function filterArchivedProducts() {


        const option =
            searchOption.value;


        const value =
            searchValue.value
                .toLowerCase()
                .trim();


        rows.forEach(function (row) {


            let searchText = '';


            if (option === 'name') {

                searchText =
                    row.dataset.name || '';

            }


            else if (option === 'category') {

                searchText =
                    row.dataset.category || '';

            }


            else if (option === 'date') {

                searchText =
                    row.dataset.date || '';

            }


            else if (option === 'email') {

                searchText =
                    row.dataset.email || '';

            }


            if (
                value === '' ||
                searchText.includes(value)
            ) {

                row.style.display = '';

            } else {

                row.style.display = 'none';

            }

        });

    }


    searchValue.addEventListener(
        'input',
        filterArchivedProducts
    );


    searchOption.addEventListener(
        'change',
        filterArchivedProducts
    );


});

</script>


</body>

</html>