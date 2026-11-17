
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


$company_id  = (int) $_SESSION['company_id'];
$location_id = (int) $_SESSION['location_id'];


// ---------------------------------------------------------
// Only allow POST request
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}


// ---------------------------------------------------------
// Get stock ID
// ---------------------------------------------------------
$stock_id = isset($_POST['stock_id'])
    ? (int) $_POST['stock_id']
    : 0;


if ($stock_id <= 0) {
    die('Invalid stock selected.');
}


// ---------------------------------------------------------
// 1. Derive product_id from stocks
// ---------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT product_id
    FROM stocks
    WHERE id = ?
      AND company_id = ?
      AND location_id = ?
    LIMIT 1
");

$stmt->execute([
    $stock_id,
    $company_id,
    $location_id
]);

$stock = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$stock) {
    die('Stock record not found.');
}


$product_id = (int) $stock['product_id'];


// ---------------------------------------------------------
// 2. Find production record using product_id
// ---------------------------------------------------------
$stmtProduction = $pdo->prepare("
    SELECT id
    FROM stock_production
    WHERE product_id = ?
      AND company_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmtProduction->execute([
    $product_id,
    $company_id
]);

$production = $stmtProduction->fetch(PDO::FETCH_ASSOC);


if (!$production) {

    echo "<script>
        alert('No production record was found for this product.');
        window.history.back();
    </script>";

    exit;
}


// ---------------------------------------------------------
// 3. Get production ID
// ---------------------------------------------------------
$production_id = (int) $production['id'];


// ---------------------------------------------------------
// 4. POST production_id to relab.php
// ---------------------------------------------------------
?>

<form id="reproduceForm" method="POST" action="relab.php">

    <input
        type="hidden"
        name="production_id"
        value="<?= htmlspecialchars($production_id) ?>"
    >

</form>


<script>
    document.getElementById('reproduceForm').submit();
</script>

