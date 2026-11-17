<?php
include('../config/db.php');

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'No category ID provided']);
    exit;
}

$category_id = $_GET['id'];
$company_id = $_SESSION['company_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT 
        pc.unit_id,
        pc.unit_type,
        pc.stock_type,
        u.unit_name
    FROM product_categories pc
    LEFT JOIN units u ON pc.unit_id = u.id
    WHERE pc.id = ? AND pc.company_id = ?
");

$stmt->execute([$category_id, $company_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode(['error' => 'Category not found']);
    exit;
}

echo json_encode([
    'unit_id' => $data['unit_id'],
    'unit_type' => $data['unit_type'],
    'stock_type' => $data['stock_type'],
    'unit_name' => $data['unit_name'] ?? 'Not set'
]);

?>