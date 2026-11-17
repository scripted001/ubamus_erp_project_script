<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['expense_id'])) {

    $expense_id = $_POST['expense_id'];

    echo $expense_id;
    // Delete expense
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    
    if ($stmt->execute([$expense_id])) {
        echo "<script>
                alert('Expense deleted successfully!');
                window.location.href = 'expenses.php';
              </script>";
    } else {
        echo "<script>
                alert('Failed to delete expense.');
                window.location.href = 'expenses.php';
              </script>";
    }

} else {
    echo "<script>
            alert('Invalid request!');
            window.location.href = 'expenses.php';
          </script>";
}
?>
