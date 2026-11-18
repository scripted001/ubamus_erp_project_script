<?php
session_start();
include('../config/db.php'); // assumes $pdo is initialized

// Set timezone to Lagos/Abuja
date_default_timezone_set('Africa/Lagos');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "<script>alert('User not logged in.'); window.location.href='login.php';</script>";
    exit;
}

// Fetch current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('User not found.'); window.location.href='login.php';</script>";
    exit;
}

$created_by = $user_id; // Set created_by
$created_at = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize input
        $business_name = trim($_POST['business_name'] ?? '');
        $office_address = trim($_POST['office_address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $location_id = $_SESSION['location_id'];
        $company_id = $_SESSION['company_id'];
        $status = ($_POST['status'] ?? 1) == 1 ? 'active' : 'suspended';

        // Check for duplicate staff email in company
        $checkStmt = $pdo->prepare("SELECT id FROM suppliers WHERE email = ? AND company_id = ?");
        $checkStmt->execute([$email, $company_id]);
        if ($checkStmt->rowCount() > 0) {
            echo "<script>alert('A supplier with this email already exists in this company.'); window.location.href='new_suppliers.php';</script>";
            exit;
        }

        // Password hash
        $password = password_hash($mobile, PASSWORD_DEFAULT);
        $role = "supplier";
        $status = "active";

       $profile = "suppliers.jpg";
        // Insert supplier into database
        $stmt = $pdo->prepare("
            INSERT INTO suppliers 
            (profile, business_name, office_address, email, mobile, created_by, created_at, company_id, location_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $profile, $business_name, $office_address, $email, $mobile, $created_by, $created_at,
            $company_id, $location_id, $status
        ]);

        // Store session info
        $_SESSION['staff_username'] = $business_name;
        $_SESSION['email'] = $email;
        $_SESSION['staff_role'] = $role;
        $_SESSION['company_id'] = $company_id;
        $_SESSION['staff_location_id'] = $location_id;
        $_SESSION['status'] = $status;

        echo "<script>alert('Supplier created successfully.'); window.location.href='user_staffs.php';</script>";
        exit;

    } catch (Exception $e) {
        echo "<script>alert('Failed to create supplier: " . addslashes($e->getMessage()) . "'); window.location.href='new_suppliers.php';</script>";
        exit;
    }
} else {
    header("Location: new_suppliers.php");
    exit;
}
?>
