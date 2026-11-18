<?php

include('../config/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$company_id = $_SESSION['company_id'] ?? 0;

$option = $_POST['search_option'] ?? '';
$value  = trim($_POST['search_value'] ?? '');

$sql = "
    SELECT
        p.*,
        c.name AS category_name,

        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            LIMIT 1
        ) AS product_image

    FROM products p

    LEFT JOIN product_categories c
        ON p.category_id = c.id

    WHERE p.company_id = :company_id
      AND p.stock_type = 'Service Only'
";

$params = [
    'company_id' => $company_id
];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($value !== '') {

    /*
    |--------------------------------------------------------------------------
    | SEARCH BY PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    if ($option === 'name') {

        $sql .= "
            AND LOWER(p.name) LIKE LOWER(:value)
        ";

        $params['value'] = '%' . $value . '%';
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH BY CATEGORY
    |--------------------------------------------------------------------------
    */

    elseif ($option === 'category') {

        $sql .= "
            AND LOWER(c.name) LIKE LOWER(:value)
        ";

        $params['value'] = '%' . $value . '%';
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH BY DATE
    |--------------------------------------------------------------------------
    |
    | Supports:
    |
    | 2026
    | 2026-09
    | 2026-09-02
    | 02 Sep 2026
    |
    */

    elseif ($option === 'date') {

        $sql .= "
            AND (
                DATE_FORMAT(p.created_at, '%Y-%m-%d') LIKE :value
                OR DATE_FORMAT(p.created_at, '%d %b %Y') LIKE :value
                OR DATE_FORMAT(p.created_at, '%M %Y') LIKE :value
                OR DATE_FORMAT(p.created_at, '%Y') LIKE :value
            )
        ";

        $params['value'] = '%' . $value . '%';
    }
}


$sql .= "
    ORDER BY p.created_at DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| OUTPUT DESKTOP TABLE ROWS
|--------------------------------------------------------------------------
*/

if (!empty($products)) {

    foreach ($products as $index => $product) {

        $productId = htmlspecialchars(
            (string)($product['id'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );

        $productName = htmlspecialchars(
            (string)($product['name'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );

        $categoryName = htmlspecialchars(
            (string)($product['category_name'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );

        $image = htmlspecialchars(
            (string)($product['product_image'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );

        $createdDate = '-';

        if (!empty($product['created_at'])) {

            $timestamp = strtotime($product['created_at']);

            if ($timestamp) {
                $createdDate = date('d M Y', $timestamp);
            }
        }
        ?>

        <tr
            data-product-id="<?= $productId ?>"
            data-product-name="<?= $productName ?>"
            data-category-name="<?= $categoryName ?>"
            data-created-date="<?= htmlspecialchars(
                $createdDate,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            data-product-image="<?= $image ?>"
        >

            <!-- INDEX -->

            <td>

                <span class="service-index">

                    <?= $index + 1 ?>

                </span>

            </td>


            <!-- PRODUCT -->

            <td>

                <div class="service-product-name">

                    <?php if (!empty($image)): ?>

                        <img
                            src="../uploads/products/<?= $image ?>"
                            class="service-product-thumb"
                            alt="<?= $productName ?>"
                            loading="lazy"
                        >

                    <?php else: ?>

                        <div class="service-product-placeholder">

                            <i class="fa-solid fa-briefcase"></i>

                        </div>

                    <?php endif; ?>


                    <div>

                        <div class="service-product-title">

                            <?= $productName ?>

                        </div>

                    </div>

                </div>

            </td>


            <!-- CATEGORY -->

            <td>

                <?php if (!empty($categoryName)): ?>

                    <span class="service-category-badge">

                        <i
                            class="fa-solid fa-tag"
                            style="margin-right:6px;"
                        ></i>

                        <?= $categoryName ?>

                    </span>

                <?php else: ?>

                    <span class="text-gray-400">

                        Uncategorized

                    </span>

                <?php endif; ?>

            </td>


            <!-- CREATED -->

            <td>

                <span class="service-date">

                    <i
                        class="fa-regular fa-calendar"
                        style="margin-right:5px;"
                    ></i>

                    <?= htmlspecialchars(
                        $createdDate,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </td>


            <!-- ACTION -->

            <td>

                <a
                    href="view_product.php?id=<?= urlencode($product['id']) ?>"
                    class="service-view-button"
                >

                    <i class="fa-solid fa-eye"></i>

                    View

                </a>

            </td>

        </tr>

        <?php
    }

} else {

    /*
    |--------------------------------------------------------------------------
    | NO RESULTS
    |--------------------------------------------------------------------------
    */

    ?>

    <tr>

        <td colspan="5">

            <div class="service-empty-state">

                <div class="service-empty-icon">

                    <i class="fa-solid fa-magnifying-glass"></i>

                </div>


                <div class="service-empty-title">

                    No service products found

                </div>


                <div class="service-empty-text">

                    No Service Only product matches your search.

                </div>

            </div>

        </td>

    </tr>

    <?php
}
?>