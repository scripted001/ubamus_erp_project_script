<?php
include('../config/db.php');


header('Content-Type: application/json');
$location_id = $_SESSION['location_id'];
$user_id = $_SESSION['user_id'];

$category_id = intval($_GET['category_id'] ?? 0);
$company_id  = $_SESSION['company_id'] ?? 0;

if (!$category_id || !$company_id) {
    echo json_encode([
        'category_id' => $category_id,
        'company_id' => $company_id
    ]);
    // echo $location_id;
    exit;
   
}


$stmt = $pdo->prepare("
    SELECT id, name
    FROM products
    WHERE category_id = ?
    AND company_id = ?
    ORDER BY name ASC
");

$stmt->execute([
    $category_id,
    $company_id
]);

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);