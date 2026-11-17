<?php
session_start();

include('../config/db.php');
include('../config/validation.php');

date_default_timezone_set('Africa/Lagos');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: customer_care.php");
    exit;
}

$company_id = trim($_POST['company_id']);
$new_email = trim($_POST['company_customer_care']);
$new_password = trim($_POST['customer_care_password']);

if (empty($company_id)) {
    $_SESSION['error'] = "Invalid company.";
    header("Location: customer_care.php");
    exit;
}

try {

    // Fetch current details
    $stmt = $pdo->prepare("
        SELECT company_customer_care, customer_care_password
        FROM companies
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        $_SESSION['error'] = "Company not found.";
        header("Location: customer_care.php");
        exit;
    }

    // Keep old value if field is left empty
    $email = !empty($new_email)
        ? $new_email
        : $company['company_customer_care'];

    $password = !empty($new_password)
        ? $new_password
        : $company['customer_care_password'];

    // Update
    $stmt = $pdo->prepare("
        UPDATE companies
        SET
            company_customer_care = ?,
            customer_care_password = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $email,
        $password,
        $company_id
    ]);

    $_SESSION['success'] = "Customer service email details updated successfully.";

} catch (PDOException $e) {

    $_SESSION['error'] = "Database Error: " . $e->getMessage();

}

header("Location: customer_care.php");
exit;