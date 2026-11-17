<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


/* =========================================================
   COMPANY
   ========================================================= */

$company_id = $_SESSION['company_id'] ?? null;


/* =========================================================
   SEARCH PARAMETERS
   ========================================================= */

$search_by = $_GET['search_by'] ?? 'name';
$search_value = trim($_GET['search_value'] ?? '');


/* =========================================================
   BASE QUERY
   ========================================================= */

$sql = "
    SELECT
        id,
        name,
        description,
        stock_type
    FROM product_categories
    WHERE company_id = :company_id
";

$params = [
    ':company_id' => $company_id
];


/* =========================================================
   SEARCH BY CATEGORY NAME
   ========================================================= */

if ($search_value !== '') {

    if ($search_by === 'name') {

        $sql .= "
            AND name LIKE :search_value
        ";

        $params[':search_value'] =
            '%' . $search_value . '%';

    }


    /* =====================================================
       SEARCH BY TRACKING ID
       ===================================================== */

    elseif ($search_by === 'id') {

        /*
         * Make sure the ID is numeric.
         */

        if (ctype_digit($search_value)) {

            $sql .= "
                AND id = :search_value
            ";

            $params[':search_value'] =
                (int)$search_value;

        } else {

            /*
             * Invalid tracking ID search.
             * Return no records.
             */

            $sql .= "
                AND 1 = 0
            ";

        }

    }

}


/* =========================================================
   ORDER
   ========================================================= */

$sql .= "
    ORDER BY id DESC
";


/* =========================================================
   EXECUTE
   ========================================================= */

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$categories =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   NO RESULTS
   ========================================================= */

if (empty($categories)) {

    echo '
        <div class="category-empty">

            <div class="category-empty-icon">

                <i class="fa-solid fa-folder-open"></i>

            </div>

            <strong>
                No categories found
            </strong>

            <span>
                No category matched your search.
            </span>

        </div>
    ';

    exit;
}


/* =========================================================
   RETURN CATEGORY CARDS
   ========================================================= */

foreach ($categories as $category) {

    $id =
        (string)($category['id'] ?? '');

    $name =
        $category['name'] ?? '';

    $description =
        $category['description'] ?? '';

    $stock_type =
        $category['stock_type'] ?? '';


    ?>


<div class="category-card">


    <!-- =================================================
         CATEGORY
         ================================================= -->

    <div class="category-card-row">

        <div class="category-card-label">

            <i class="fa-solid fa-tag"></i>

            <span>
                Category
            </span>

        </div>

        <div class="category-card-value">

            <?= htmlspecialchars(
                $name,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>

    </div>


    <!-- =================================================
         TRACKING ID
         ================================================= -->

    <div class="category-card-row">

        <div class="category-card-label">

            <i class="fa-solid fa-hashtag"></i>

            <span>
                Tracking ID
            </span>

        </div>

        <div class="category-card-value">

            #<?= htmlspecialchars(
                $id,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>

    </div>


    <!-- =================================================
         DESCRIPTION
         ================================================= -->

    <div class="category-card-row">

        <div class="category-card-label">

            <i class="fa-solid fa-align-left"></i>

            <span>
                Description
            </span>

        </div>

        <div class="category-card-value">

            <?= htmlspecialchars(
                $description !== ''
                    ? $description
                    : 'No description',
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>

    </div>


    <!-- =================================================
         STOCK TYPE
         ================================================= -->

    <div class="category-card-row">

        <div class="category-card-label">

            <i class="fa-solid fa-box"></i>

            <span>
                Stock Type
            </span>

        </div>

        <div class="category-card-value">

            <?= htmlspecialchars(
                $stock_type !== ''
                    ? $stock_type
                    : 'N/A',
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>

    </div>


    <!-- =================================================
         ACTIONS
         ================================================= -->

    <div class="category-card-actions">


        <!-- DELETE -->

        <form
            method="POST"
            action="delete_category.php"
            class="category-delete-form"
            onsubmit="
                return confirm(
                    'Are you sure you want to delete this category?'
                );
            "
        >

            <input
                type="hidden"
                name="category_id"
                value="<?= htmlspecialchars(
                    $id,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >

            <button
                type="submit"
                name="submit"
                class="category-delete-button"
                title="Delete Category"
            >

                <i class="fa-solid fa-trash"></i>

            </button>

        </form>


    </div>


</div>


<?php


}
?>
