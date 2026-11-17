<?php
include('../config/db.php');


header('Content-Type: application/json');

$service_id   = intval($_GET['service_id'] ?? 0);
$company_id  = $_SESSION['company_id'] ?? 0;
$location_id = $_SESSION['location_id'] ?? 0;




// $service_id = 11;


if (!$service_id || !$company_id || !$location_id) {
    echo json_encode([
        'total_cost' => 0,
        'quantity_used' => 0,
        'product_id' => 0
    ]);
    exit;
}

try {




// SELECT SERVICE MATERIAL PRODUCT ID
$stmt = $pdo->prepare("SELECT product_id FROM service_items WHERE service_id=?");
$stmt->execute([$service_id]);
$prod = $stmt->fetch();

$product_id = $prod['product_id'];

    /* -----------------------------
       PRODUCT PRICE
    ------------------------------*/
    $stmt = $pdo->prepare("
        SELECT total_cost
        FROM services
        WHERE id = ?
        AND company_id = ?
        LIMIT 1
    ");

    $stmt->execute([$service_id, $company_id]);

    $price = (float)($stmt->fetchColumn() ?: 0);

   


    /* -----------------------------
       AVAILABLE STOCK
    ------------------------------*/
    $stmt = $pdo->prepare("
        SELECT COALESCE(quantity,0)
        FROM stocks
        WHERE product_id = ?
        AND company_id = ?
        AND location_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id,
        $location_id
    ]);

    $qty = (float)($stmt->fetchColumn() ?: 0);

    echo json_encode([
        'total_cost' => $price,
        'quantity'      => $qty
    ]);

} catch (Exception $e) {

    echo json_encode([
        'total_cost' => 0,
        'quantity'      => 0
    ]);
}