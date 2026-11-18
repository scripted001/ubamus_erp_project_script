<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../config/db.php');
include('user_data.php'); // Contains session user info
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

date_default_timezone_set('Africa/Lagos');

// ===============================
// VALIDATION
// ===============================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request');
}

if (!isset($_SESSION['company_id'], $_SESSION['user_id'])) {
    exit('Unauthorized');
}

// ===============================
// FORM DATA
// ===============================
$company_id = $_SESSION['company_id'];
$user_id    = $_SESSION['user_id'];
// Collect form data

$location_id = $_POST['location_id'] ?? null;
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);

$created_at = date('Y-m-d H:i:s');
$status = 'active';
// Get user email
$userStmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
$added_by_email = $user['email'] ?? 'Unknown';
$added_by_username = $user['username'] ?? 'Unknown';


/* ======== GET LOCATION NAME ======== */
$locationStmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
$locationStmt->execute([$location_id]);
$location = $locationStmt->fetch(PDO::FETCH_ASSOC)['name'] ?? 'N/A';


// ===============================
// INSERT CUSTOMER
// ===============================

$stmt = $pdo->prepare("
    INSERT INTO customers 
        (company_id, location_id, fullname, email, phone, address, added_by, created_at, updated_at, status)
    VALUES 
        (:company_id, :location_id, :fullname, :email, :phone, :address, :added_by, :created_at, :updated_at, :status)
");

$stmt->execute([
    ':company_id'  => $company_id,
    ':location_id' => $location_id,
    ':fullname'    => $fullname,
    ':email'       => $email,
    ':phone'       => $phone,
    ':address'     => $address,
    ':added_by'    => $added_by_email,
    ':created_at'  => $created_at,
    ':updated_at'  => $created_at,
    ':status'      => $status
]);

$customer_id = $pdo->lastInsertId();

// ===============================
// FETCH COMPANY DETAILS
// ===============================
$companyStmt = $pdo->prepare("SELECT company_name, admin_email FROM companies WHERE id = ?");
$companyStmt->execute([$company_id]);
$company = $companyStmt->fetch();
$admin_email = $company['admin_email'];
$company_name = $company['company_name'];

// ===============================
// FETCH STAFF NAME
// ===============================
$staffStmt = $pdo->prepare("SELECT firstname, lastname FROM staffs WHERE id = ?");
$staffStmt->execute([$user_id]);
$staff = $staffStmt->fetch();
$staff_name = $staff['firstname'] . ' ' . $staff['lastname'];

// ===============================
// SEND EMAIL TO ADMIN
// ===============================
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@ubamus.com';
    $mail->Password   = 'UniverseScript001!!';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('support@ubamus.com', $company_name);
    $mail->addAddress($admin_email);

    $mail->isHTML(true);
    $mail->Subject = "New Customer Added: $fullname";

    $mailBody = "
        <h2>New Customer Added to {$company_name}</h2>
        <p><strong>Customer Name:</strong> {$fullname}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Phone:</strong> {$phone}</p>
        <p><strong>Address:</strong> {$address}</p>
        <p><strong>Date Added:</strong> {$created_at}</p>
        <p><strong>Added By:</strong> {$added_by_username}</p>
        <p><strong>Location Added:</strong> {$location}</p>
    ";

    $mail->Body = $mailBody;
    $mail->send();
} catch (Exception $e) {
    error_log("Customer email could not be sent. Mailer Error: {$mail->ErrorInfo}");
}

// ===============================
// REDIRECT BACK
// ===============================
echo "<script>
    alert('Customer added successfully!');
    window.location.href='register-job.php';
</script>";


?>