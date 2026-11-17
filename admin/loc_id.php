<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


$companyid = $_SESSION['company_id'];
$user_id = $_SESSION['user_id'];
// echo $user_id;
$user_id = $_SESSION['user_id'];

$location_id = 25;
// =====================
// TOTAL STOCK QTY & VALUE
// =====================
$stmt = $pdo->prepare("
    SELECT 

        COALESCE(SUM(s.quantity), 0) AS total_qty,
        COALESCE(SUM(s.quantity * pu.selling_price), 0) AS total_value
    FROM stocks s
    INNER JOIN products p ON p.id = s.product_id,
   
    WHERE s.location_id = ?
");
$stmt->execute([$location_id]);
$stockData = $stmt->fetch(PDO::FETCH_ASSOC);

$totalStock = (int)($stockData['total_qty'] ?? 0);
$totalStockValue = (float)($stockData['total_value'] ?? 0);

?>