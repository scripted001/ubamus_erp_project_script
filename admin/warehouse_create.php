<?php
include('../config/db.php');
include('../app_data.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $company_id = $_POST['company_id'] ?? '';
        $name       = $_POST['name'] ?? '';
        $location   = $_POST['location'] ?? '';

        // Check for duplicate warehouse name under the same company
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM warehouses WHERE name = :name AND company_id = :company_id");
        $checkStmt->execute([
            ':name' => $name,
            ':company_id' => $company_id
        ]);

        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            echo "<script>alert('Warehouse name already exists for this company!'); window.location.href = 'new_warehouse.php';</script>";
            exit;
        }

        // Insert data into the database using prepared statements
        $stmt = $pdo->prepare("INSERT INTO warehouses 
            (name, location, company_id) 
            VALUES 
            (:name, :location, :company_id)");

        $stmt->execute([
            ':name' => $name,
            ':location' => $location,
            ':company_id' => $company_id
        ]);

        echo "<script>alert('Warehouse successfully created!'); window.location.href = 'warehouse.php';</script>";

    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        echo "<script>alert('An error occurred while creating the warehouse.');</script>";
    }
}
?>
