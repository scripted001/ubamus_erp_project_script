<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

// define deleted_at
date_default_timezone_set('Africa/Lagos');
$deleted_at = date("Y-m-d H:i:s");



$company_id  = $_SESSION['company_id'] ?? 1;
$user_id     = $_SESSION['user_id'] ?? null;
// selecting deleted by 
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userdetails = $stmt->fetch(PDO::FETCH_ASSOC);

$deleted_by    = $userdetails['email']   ?? 'Not Found'; 



if($stock_id = $_POST['stock_id']) {

// stocks database operation
    // Selecting data from stocks
    $stmt = $pdo->prepare("SELECT * FROM stocks WHERE id = ?");
    $stmt->execute([$stock_id]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    
    $stock_product_id    = $stock['product_id']   ?? 'Not Found'; 
    $stock_company_id    = $stock['company_id']   ?? 'Not Found'; 
    $stock_location_id    = $stock['location_id']   ?? 'Not Found'; 
    $stock_quantity    = $stock['quantity']   ?? 'Not Found'; 
    $stock_supplier    = $stock['supplier']   ?? 'Not Found'; 
    $stock_created_at    = $stock['created_at']   ?? 'Not Found'; 
    $stock_stocked_by    = $stock['stocked_by']   ?? 'Not Found'; 

    // insert into deleted stocks database table
    
        $pdo->prepare("
            INSERT INTO deleted_stocks
            (stock_id, product_id, company_id, location_id, quantity, supplier, created_at, stocked_by, deleted_by, deleted_at)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $stock_id,
            $stock_product_id,
            $stock_company_id,
            $stock_location_id,
            $stock_quantity,
            $stock_supplier,
            $stock_created_at,
            $stock_stocked_by,
            $deleted_by,
            $deleted_at
        ]);

// End of stock database operation


// Begin stock_in database table operations
    // Selecting data from stock_in
    $stmt = $pdo->prepare("SELECT * FROM stock_in WHERE stock_id = ?");
    $stmt->execute([$stock_id]);
    $stock_in = $stmt->fetch(PDO::FETCH_ASSOC);

    
    $stock_in_id    = $stock_in['id']   ?? 'Not Found'; 
    $stock_in_product_id    = $stock_in['product_id']   ?? 'Not Found'; 
    $stock_in_stock_id    = $stock_in['stock_id']   ?? 'Not Found'; 
    $stock_in_company_id    = $stock_in['company_id']   ?? 'Not Found'; 
    $stock_in_location_id    = $stock_in['location_id']   ?? 'Not Found'; 
    $stock_in_transfer_ID    = $stock_in['transfer_ID']   ?? 'Not Found'; 
    $stock_in_quantity    = $stock_in['quantity']   ?? 'Not Found'; 
    $stock_in_type    = $stock_in['type']   ?? 'Not Found'; 
    $stock_in_supplier    = $stock_in['supplier']   ?? 'Not Found'; 
    $stock_in_created_at    = $stock_in['created_at']   ?? 'Not Found'; 
    $stock_in_recorded_by    = $stock_in['recorded_by']   ?? 'Not Found'; 

    // insert into deleted_stock_in database table
    
    $pdo->prepare("
        INSERT INTO deleted_stock_in
        (stock_in_id, product_id, stock_id, company_id, location_id, transfer_ID, quantity, type, supplier, created_at, recorded_by, deleted_by, deleted_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $stock_in_id,
        $stock_in_product_id,
        $stock_in_stock_id,
        $stock_in_company_id,
        $stock_in_location_id,
        $stock_in_transfer_ID,
        $stock_in_quantity,
        $stock_in_type,
        $stock_in_supplier,
        $stock_in_created_at,
        $stock_in_recorded_by,
        $deleted_by,
        $deleted_at
    ]);

// end of stock_in database table operations

// Begin stock_movement database table operations
    
    // Selecting data from stock_movements
    $stmt = $pdo->prepare("SELECT * FROM stock_movements WHERE stock_id = ?");
    $stmt->execute([$stock_id]);
    $stock_mov = $stmt->fetch(PDO::FETCH_ASSOC);

    
    $stock_mov_id    = $stock_mov['id']   ?? 'Not Found'; 
    $stock_mov_company_id    = $stock_mov['company_id']   ?? 'Not Found'; 
    $stock_mov_product_id    = $stock_mov['product_id']   ?? 'Not Found'; 
    $stock_mov_stock_id    = $stock_mov['stock_id']   ?? 'Not Found'; 
    $stock_mov_from_location_id    = $stock_mov['from_location_id']   ?? 'Not Found'; 
    $stock_mov_to_location_id    = $stock_mov['to_location_id']   ?? 'Not Found'; 
    $stock_mov_transfer_ID    = $stock_mov['transfer_ID']   ?? 'Not Found'; 
    $stock_mov_quantity    = $stock_mov['quantity']   ?? 'Not Found'; 
    $stock_mov_movement_type    = $stock_mov['movement_type']   ?? 'Not Found'; 
    $stock_mov_reference    = $stock_mov['reference']   ?? 'Not Found'; 
    $stock_mov_created_by    = $stock_mov['created_by']   ?? 'Not Found'; 
    $stock_mov_created_at    = $stock_mov['created_at']   ?? 'Not Found'; 

    $pdo->prepare("
    INSERT INTO deleted_stock_movement
    (stock_movement_id, company_id, product_id, stock_id, from_location_id, to_location_id, transfer_ID, quantity, movement_type, reference, created_by, created_at, deleted_by, deleted_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
    $stock_mov_id,
    $stock_mov_company_id,
    $stock_mov_product_id,
    $stock_mov_stock_id,
    $stock_mov_from_location_id,
    $stock_mov_to_location_id,
    $stock_mov_transfer_ID,
    $stock_mov_quantity,
    $stock_mov_movement_type,
    $stock_mov_reference,
    $stock_mov_created_by,
    $stock_mov_created_at,
    $deleted_by,
    $deleted_at
        ]);

// end stock_movement database table operations

// deleting feature from selected database tables begin

    // deleting stock 
    $stock_delete = $pdo->prepare("DELETE FROM stocks WHERE id = ?");
    $stock_delete->execute([$stock_id]);

    // deleting stock_in
    $stock_in_delete = $pdo->prepare("DELETE FROM stock_in WHERE stock_id = ?");
    $stock_in_delete->execute([$stock_id]);

    // deleting stock_movements
    $stock_mov_delete = $pdo->prepare("DELETE FROM stock_movements WHERE stock_id = ?");
    $stock_mov_delete->execute([$stock_id]);

// deleting feature ends

echo "<script>
alert('Stock successfully archieved!');
window.location.href='stocks.php';
</script>";
exit;


    
}else{
    echo "<script>
        alert('Something went wrong!');
        window.location.href='stocks.php';
        </script>";
exit;
}




?>