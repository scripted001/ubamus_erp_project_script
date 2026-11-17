<?php
include('../config/db.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $staff_id = $_POST['staff_id'];
    $staff_email = $_POST['staff_email'];

    // Optional: fetch staff to confirm existence
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo "<script>alert('Staff not found.'); window.location.href = 'staffs.php';</script>";
        exit;
    }

    // Begin transaction to ensure both deletions succeed
    $pdo->beginTransaction();
    try {
        // Delete staff record
        $stmt = $pdo->prepare("DELETE FROM staffs WHERE id = ?");
        $stmt->execute([$staff_id]);

        // Delete corresponding user record from users table
        $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$staff_email]);

        // Commit transaction
        $pdo->commit();

        echo "<script>alert('Staff and associated user deleted successfully.'); window.location.href = 'staffs.php';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error deleting staff: " . addslashes($e->getMessage()) . "'); window.location.href = 'staffs.php';</script>";
        exit;
    }

} else {
    echo "<script>alert('Invalid Request.'); window.location.href = 'staffs.php';</script>";
    exit;
}
?>
