<?php
session_start();

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request.');
}

// Assign form data
$company_id  = $_POST['company_id'] ?? null;
$product_id  = $_POST['product_id'] ?? null;
$location_id = $_POST['location_id'] ?? null;
$supplier    = $_POST['supplier'] ?? null;
$quantity    = (float) ($_POST['quantity'] ?? 0);
$stocked_by  = $_POST['stocked_by'] ?? null;


// Validation
if (
    empty($company_id) ||
    empty($product_id) ||
    empty($location_id) ||
    empty($stocked_by) ||
    $quantity <= 0
) {
    echo "<script>
        alert('Please fill all required fields correctly.');
        window.history.back();
    </script>";
    exit;
}

try {

    $pdo->beginTransaction();

    // Check if stock already exists
    $stmt = $pdo->prepare("
        SELECT *
        FROM stocks
        WHERE product_id = ?
          AND company_id = ?
          AND location_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id,
        $location_id
    ]);

    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stock) {

        // Existing stock found
        $stock_id = $stock['id'];

        $stmt = $pdo->prepare("
            UPDATE stocks
            SET
                quantity = quantity + ?,
                supplier = ?,
                stocked_by = ?,
                created_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $quantity,
            $supplier,
            $stocked_by,
            $stock_id
        ]);

        $type = "Restock";

        // Record stock-in history
        $stmt = $pdo->prepare("
            INSERT INTO stock_in
            (
                product_id,
                stock_id,
                company_id,
                location_id,
                quantity,
                type,
                supplier,
                created_at,
                recorded_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
    
        $stmt->execute([
            $product_id,
            $stock_id,
            $company_id,
            $location_id,
            $quantity,
            $type,
            $supplier,
            $stocked_by
        ]);


        $message = "Stock updated successfully.";

    } else {

        // Insert new stock
        $stmt = $pdo->prepare("
            INSERT INTO stocks
            (
                product_id,
                company_id,
                location_id,
                quantity,
                supplier,
                created_at,
                stocked_by
            )
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");

        $stmt->execute([
            $product_id,
            $company_id,
            $location_id,
            $quantity,
            $supplier,
            $stocked_by
        ]);

        $stock_id = $pdo->lastInsertId();
        $type = "Initial Stock Entry";

      

        // Record stock-in history
        $stmt = $pdo->prepare("
            INSERT INTO stock_in
            (
                product_id,
                stock_id,
                company_id,
                location_id,
                quantity,
                type,
                supplier,
                created_at,
                recorded_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
    
        $stmt->execute([
            $product_id,
            $stock_id,
            $company_id,
            $location_id,
            $quantity,
            $type,
            $supplier,
            $stocked_by
        ]);

        $message = "Stock inserted successfully.";
    }

  

    $pdo->commit();

    echo "<script>
        alert('$message');
        window.location.href='stocks.php';
    </script>";
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<script>
        alert('Error: " . addslashes($e->getMessage()) . "');
        window.history.back();
    </script>";
    exit;
}
?>