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
$location_id    = $_SESSION['location_id'];
// Collect form data

$business_name = $_POST['business_name'] ?? null;
$office_address = trim($_POST['office_address']);
$email = trim($_POST['email']);
$mobile = trim($_POST['mobile']);
$description = trim($_POST['description']);


$created_at = date('Y-m-d H:i:s');
$status = 'active';


// Get user email

$userStmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
$created_by = $user['email'] ?? 'Unknown';
$added_by_username = $user['username'] ?? 'Unknown';


/* ======== GET LOCATION NAME ======== */
$locationStmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
$locationStmt->execute([$location_id]);
$location = $locationStmt->fetch(PDO::FETCH_ASSOC)['name'] ?? 'N/A';

// Vendor validation
$stmt = $pdo->prepare("SELECT business_name, office_address FROM vendors WHERE business_name = ? AND office_address = ?");
$stmt->execute([$business_name, $office_address]);
$business_data = $stmt->fetch();

if($business_data == true) {

   echo "<script>alert('Vendor already exist...'); window.history.back();</script>";
        exit();
}else{
    
// ===============================
// INSERT Vendor
// ===============================

$stmt = $pdo->prepare("
    INSERT INTO vendors 
        (company_id, location_id, business_name, office_address, email, mobile, description, created_by, created_at, status)
    VALUES 
        (:company_id, :location_id, :business_name, :office_address, :email, :mobile, :description, :created_by, :created_at, :status)
");

$stmt->execute([
    ':company_id'  => $company_id,
    ':location_id' => $location_id,
    ':business_name'    => $business_name,
    ':office_address'       => $office_address,
    ':email'       => $email,
    ':mobile'     => $mobile,
    ':description'    => $description,
    ':created_by'  => $created_by,
    ':created_at'  => $created_at,
    ':status'      => $status
]);

$customer_id = $pdo->lastInsertId();

// ===============================
// FETCH COMPANY DETAILS
// ===============================
$companyStmt = $pdo->prepare("SELECT company_name, company_email FROM companies WHERE id = ?");
$companyStmt->execute([$company_id]);
$company = $companyStmt->fetch();
$company_email = $company['company_email'];
$company_name = $company['company_name'];


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
    $mail->addAddress($company_email);

    $mail->isHTML(true);
    $mail->Subject = "New Vendor Registered: $business_name";

    $mailBody = "
        <h2>New Vendor/Supplier Added to {$company_name}</h2>
        <p><strong>Vendor Business Name:</strong> {$business_name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Phone:</strong> {$mobile}</p>
        <p><strong>Office Address:</strong> {$office_address}</p>
        <p><strong>Brief Discription:</strong> {$description}</p>
        <p><strong>Date Added:</strong> {$created_at}</p>
        <p><strong>Added By:</strong> {$added_by_username}</p>
        <p><strong>Location Added:</strong> {$location}</p>
    ";

    $mail->Body = $mailBody;
    $mail->send();
    // Success alert + redirect
        echo "<script>alert('Vendor created email sent successfully!'); window.location.href='existing_suppliers.php';</script>";
        exit;

} catch (Exception $e) {
    error_log("Customer email could not be sent. Mailer Error: {$mail->ErrorInfo}");
}

echo "<script>alert('Vendor created successfully! but email not sent'); window.location.href='existing_suppliers.php';</script>";
exit;

}

?>