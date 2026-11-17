<?php
include('../config/db.php');


header('Content-Type: application/json');
$location_id = $_SESSION['location_id'];
$user_id = $_SESSION['user_id'];

$category_id = intval($_GET['category_id'] ?? 0);
$company_id  = $_SESSION['company_id'] ?? 0;

if (!$category_id || !$company_id) {
    echo json_encode([]);
    exit;
}


$stmt = $pdo->prepare("
    SELECT id, service_name, service_number 
    FROM services
    WHERE category_id = ?
    AND company_id = ?
    ORDER BY service_name ASC
");

$stmt->execute([
    $category_id,
    $company_id
]);

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);