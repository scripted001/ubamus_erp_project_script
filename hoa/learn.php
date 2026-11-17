<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

echo $company_id;


// Fetch Job Catalogue by Company with Material Names
// Fetch Job Catalogue by Company with Material Names and Category Names
$dST = "service";
$jobQuery = $pdo->prepare("
    SELECT 
        pu.id AS product_unit_id, 
        pu.product_id, 
        pu.selling_price AS unit_price,
        st.quantity AS material_quantity
    FROM product_units pu
    LEFT JOIN stocks st ON st.product_id = pu.product_id
    WHERE st.company_id = ? AND pu.stock_type = ?
    ORDER BY pu.id ASC
");
$jobQuery->execute([$company_id, $dST]);
$jobs = $jobQuery->fetchAll(PDO::FETCH_ASSOC);

echo $jobs['unit_price'];




?>
