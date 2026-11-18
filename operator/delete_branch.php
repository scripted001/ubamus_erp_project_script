<?php
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $branch_id = $_POST['branch_id'] ?? '';

        if (empty($branch_id)) {
            echo "<script>alert('Branch ID is required to delete.'); window.history.back();</script>";
            exit;
        }

        // Optional: Confirm branch exists before deletion
        $checkStmt = $pdo->prepare("SELECT id FROM branches WHERE id = ?");
        $checkStmt->execute([$branch_id]);

        if ($checkStmt->rowCount() === 0) {
            echo "<script>alert('Branch not found.'); window.history.back();</script>";
            exit;
        }

        // Proceed to delete
        $deleteStmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
        $deleteStmt->execute([$branch_id]);

        echo "<script>alert('Branch deleted successfully.'); window.location.href = 'branches.php';</script>";
        
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        echo "<script>alert('Error deleting branch.');</script>";
    }
}
?>
