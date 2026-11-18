<?php
include('../config/db.php'); // PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form values
    $company_id   = $_SESSION['company_id'];
    $created_by   = $_SESSION['user_id'];
    $unit_type         = trim($_POST['unit_type']);
    $unit_name         = trim($_POST['unit_name']);
   

//    echo $unit_name;

    try {
        // Start transaction
        $pdo->beginTransaction();

        // 1. Insert into products table
        $stmt = $pdo->prepare("
            INSERT INTO units
            (company_id, unit_name, unit_type, created_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$company_id, $unit_name, $unit_type, $created_by]);
        $pdo->commit();
        
        echo "<script>alert('Unit created successfully'); window.location.href='categories.php';</script>";
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<script>alert('Error creating product: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
        


} else {
    echo "<script>alert('Invalid request'); window.history.back();</script>";
    exit;
}
?>
