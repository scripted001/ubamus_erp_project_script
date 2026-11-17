<?php

include('../config/db.php');
session_start();

header('Content-Type: application/json');

$catId = intval($_GET['category_id'] ?? 0);
$company_id = $_SESSION['company_id'] ?? 0;

if (!$catId || !$company_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        name
    FROM product_categories
    WHERE parent_id = ?
    AND company_id = ?
    ORDER BY name ASC
");

$stmt->execute([
    $catId,
    $company_id
]);

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);