<?php
include('../config/db.php');


header('Content-Type: application/json');

$productId   = intval($_GET['product_id'] ?? 0);
$company_id  = $_SESSION['company_id'] ?? 0;
$location_id = $_SESSION['location_id'] ?? 0;

if (!$productId || !$company_id || !$location_id) {
    echo json_encode([
        'selling_price' => 0,
        'quantity' => 0
    ]);
    exit;
}

try {

    /* -----------------------------
       PRODUCT PRICE
    ------------------------------*/
    $stmt = $pdo->prepare("
        SELECT selling_price
        FROM products
        WHERE id = ?
        AND company_id = ?
        LIMIT 1
    ");

    $stmt->execute([$productId, $company_id]);

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
        $productId,
        $company_id,
        $location_id
    ]);

    $qty = (float)($stmt->fetchColumn() ?: 0);

    echo json_encode([
        'selling_price' => $price,
        'quantity'      => $qty
    ]);

} catch (Exception $e) {

    echo json_encode([
        'selling_price' => 0,
        'quantity'      => 0
    ]);
}