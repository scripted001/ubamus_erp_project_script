<?php
include('../config/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['staff_id'])) {
        echo "<script>alert('Invalid request.'); window.location.href='staffs.php';</script>";
        exit;
    }

    $staff_id = $_POST['staff_id'];

    // Fetch staff
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo "<script>alert('Staff not found.'); window.location.href='staffs.php';</script>";
        exit;
    }

    $staff_email = $staff['email'];

    // Suspend staff in STAFFS table
    $stmt = $pdo->prepare("UPDATE staffs SET status = 'active' WHERE id = ?");
    $stmt->execute([$staff_id]);

    // Suspend corresponding user in USERS table (using email match)
    $stmt2 = $pdo->prepare("UPDATE users SET status = 'active' WHERE email = ?");
    $stmt2->execute([$staff_email]);

    echo "<script>
            alert('Staff has been successfully activated.');
            window.location.href = 'staffs.php';
          </script>";
    exit;
}

echo "<script>alert('Invalid method.'); window.location.href='staffs.php';</script>";
exit;
?>
