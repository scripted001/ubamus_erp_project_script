<?php
// approve_expense.php
session_start();
include('../config/db.php'); // your PDO connection
include('../config/validation.php'); // optional, if you use validation functions
include('user_data.php'); // to get current user info

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo "<script>alert('You must be logged in to approve expenses!'); window.location.href='expenses.php';</script>";
    exit;
}

// Check if expense_id is sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['expense_id'])) {
    $expense_id = $_POST['expense_id'];
    $approved_by = $_SESSION['user_email']; // Admin who approves
    $stat = 1;

    try {
        // Update the expense as approved
        $stmt = $pdo->prepare("UPDATE expenses SET approved_by = :approved_by WHERE id = :expense_id");
        $stmt->execute([
            ':approved_by' => $approved_by,
            ':expense_id' => $expense_id
        ]);

        $stmt2 = $pdo->prepare("UPDATE expenses SET stat = :stat WHERE id = :expense_id");
        $stmt2->execute([
            ':stat' => $stat,
            ':expense_id' => $expense_id
        ]);

        echo "<script>alert('Expense approved successfully!'); window.location.href='expenses.php';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Error approving expense: " . addslashes($e->getMessage()) . "'); window.location.href='expenses.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Invalid request!'); window.location.href='expenses.php';</script>";
    exit;
}
