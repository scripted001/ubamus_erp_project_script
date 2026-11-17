<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');

include('../company_data.php');
$companyid = $_SESSION['company_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $production_id  = $_POST['production_id'];


    // SELECT ALL FROM STOCK PRODUCTION
    $stmt = $pdo->prepare("SELECT * FROM stock_production WHERE product_id=? AND company_id=?");
    $stmt->execute([$production_id, $company_id]);
    $production = $stmt->fetch();

    // echo $production['batch_number'];
    $_SESSION['production_id'] = $production['id'];
    header('location: relab.php');


}


?>