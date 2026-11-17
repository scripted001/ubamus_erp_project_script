<?php
header('Content-Type: application/json');

// Include your database connection
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');



try {
    // Get POST data
    $company_id   = $_SESSION['company_id'];
    $name         = trim($_POST['name']);
    $bulk_price   = floatval($_POST['bulk_price']);
    $quantity     = floatval($_POST['quantity']);

    if (!$company_id || !$name || !$bulk_price || !$quantity) {
        throw new Exception("All fields are required");
    }

    // Calculate cost price (unit price = bulk_price ÷ quantity)
    $unit_price = $bulk_price / $quantity;

    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    // Insert into materials table
    $stmt = $pdo->prepare("INSERT INTO materials (company_id, name, unit_price, cost_price, quantity, created_at, updated_at)
                           VALUES (:company_id, :name, :unit_price, :cost_price, :quantity, :created_at, :updated_at)");

    $stmt->execute([
        ':company_id' => $company_id,
        ':name'       => $name,
        ':unit_price' => $unit_price,
        ':cost_price' => $bulk_price,
        ':quantity'   => $quantity,
        ':created_at' => $created_at,
        ':updated_at' => $updated_at
    ]);

    $material_id = $pdo->lastInsertId();

    // Return JSON
    echo json_encode([
        'success' => true,
        'material' => [
            'id'       => $material_id,
            'name'     => $name,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'cost_price' => $bulk_price
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
