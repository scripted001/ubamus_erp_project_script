<?php
include('../config/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['submit'])) {
        echo "<script>alert('Invalid request.'); window.location.href='existing_suppliers.php';</script>";
        exit;
    }

    $vendor_id = $_POST['vendor_id'];

    // Fetch staff
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vendor) {
        echo "<script>alert('Vendor not found.'); window.location.href='existing_suppliers.php';</script>";
        exit;
    }

    $vendor_email = $vendor['email'];

    // Suspend staff in STAFFS table
    $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);



    echo "<script>
            alert('This vendor has been deleted.');
            window.location.href = 'existing_suppliers.php';
          </script>";
    exit;
}

echo "<script>alert('Invalid method.'); window.location.href='existing_suppliers.php';</script>";
exit;
?>
