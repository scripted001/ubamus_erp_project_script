<?php
include('../config/db.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $staff_id = $_POST['staff_id'];
    $staff_email = $_POST['staff_email'];

    // Optional: fetch staff to confirm existence
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo "<script>alert('Customer not found.'); window.location.href = 'existing_customers.php';</script>";
        exit;
    }

    // Begin transaction to ensure both deletions succeed
    $pdo->beginTransaction();
    try {
        // Delete staff record
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$staff_id]);


        // Commit transaction
        $pdo->commit();

        echo "<script>alert('Customer account deleted successfully.'); window.location.href = 'existing_customers.php';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error deleting staff: " . addslashes($e->getMessage()) . "'); window.location.href = 'existing_customers.php';</script>";
        exit;
    }

} else {
    echo "<script>alert('Invalid Request.'); window.location.href = 'existing_customers.php';</script>";
    exit;
}
?>
