
<?php

/**
 * ============================================================
 * AJAX PRODUCT SEARCH
 * ============================================================
 *
 * Returns product search results as JSON.
 *
 * Supported:
 *  - Name
 *  - Category
 *  - Date
 *
 * The JavaScript on the products page uses this data to render
 * both the desktop table and the mobile product cards.
 * ============================================================
 */

include('../config/db.php');


// ============================================================
// START SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// GET COMPANY
// ============================================================

$company_id = (int) ($_SESSION['company_id'] ?? 0);


// ============================================================
// INVALID COMPANY
// ============================================================

if ($company_id <= 0) {

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'message' => 'Invalid company session.',
        'products' => []
    ]);

    exit;
}


// ============================================================
// GET SEARCH VALUES
// ============================================================

$option = $_POST['search_option'] ?? 'name';

$value = trim(
    $_POST['search_value'] ?? ''
);


// ============================================================
// ALLOWED SEARCH OPTIONS
// ============================================================

$allowedOptions = [
    'name',
    'category',
    'date'
];


if (!in_array($option, $allowedOptions, true)) {

    $option = 'name';

}


// ============================================================
// BASE QUERY
// ============================================================

$sql = "

    SELECT

        p.id,
        p.company_id,
        p.name,
        p.category_id,
        p.created_at,

        c.name AS category_name,

        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.id ASC
            LIMIT 1
        ) AS image

    FROM products p

    LEFT JOIN product_categories c
        ON p.category_id = c.id

    WHERE p.company_id = :company_id

";


$params = [

    ':company_id' => $company_id

];


// ============================================================
// SEARCH BY NAME
// ============================================================

if ($value !== '' && $option === 'name') {

    $sql .= "

        AND LOWER(p.name)
        LIKE LOWER(:search_value)

    ";

    $params[':search_value'] =
        '%' . $value . '%';

}


// ============================================================
// SEARCH BY CATEGORY
// ============================================================

elseif ($value !== '' && $option === 'category') {

    $sql .= "

        AND LOWER(c.name)
        LIKE LOWER(:search_value)

    ";

    $params[':search_value'] =
        '%' . $value . '%';

}


// ============================================================
// SEARCH BY DATE
// ============================================================

elseif ($value !== '' && $option === 'date') {

    /*
     * Allows searches such as:
     *
     * 2026
     * 2026-08
     * 2026-08-29
     *
     */

    $sql .= "

        AND DATE_FORMAT(
            p.created_at,
            '%Y-%m-%d'
        ) LIKE :search_value

    ";

    $params[':search_value'] =
        '%' . $value . '%';

}


// ============================================================
// ORDER
// ============================================================

$sql .= "

    ORDER BY p.created_at DESC

";


// ============================================================
// EXECUTE
// ============================================================

try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ========================================================
    // RETURN JSON
    // ========================================================

    header(
        'Content-Type: application/json; charset=utf-8'
    );


    echo json_encode([

        'success' => true,

        'count' => count($products),

        'products' => $products

    ]);

    exit;


} catch (Throwable $e) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    http_response_code(500);


    echo json_encode([

        'success' => false,

        'message' => 'Unable to search products.',

        'products' => []

    ]);

    exit;

}

?>