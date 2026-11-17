<?php
include('../config/db.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['warehouse_id'])) {
    $warehouse_id = $_POST['warehouse_id'];

    // Optional: fetch staff to confirm existence
    $stmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = ?");
    $stmt->execute([$warehouse_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
       echo "<script>alert('Warehouse not found.'); window.location.href = 'new_warehouse.php';</script>";
        exit;
    }

    // Delete staff record
    $stmt = $pdo->prepare("DELETE FROM warehouses WHERE id = ?");
    $stmt->execute([$warehouse_id]);

   echo "<script>alert('Warehouse Deleted Successfully.'); window.location.href = 'warehouse.php';</script>";
    exit;
} else {
    echo "<script>alert('Invalid Request.'); window.location.href = 'warehouse.php';</script>";
    exit;
}
?>
