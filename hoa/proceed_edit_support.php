<?php

include('../config/db.php');
include('../config/validation.php');

date_default_timezone_set('Africa/Lagos');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: support.php");
    exit;
}

$company_id = trim($_POST['company_id']);
$new_email = trim($_POST['company_support_email']);
$new_password = trim($_POST['support_email_password']);

if (empty($company_id)) {
    $_SESSION['error'] = "Invalid company.";
    header("Location: support.php");
    exit;
}

try {

    // Fetch current details
    $stmt = $pdo->prepare("
        SELECT company_support_email, support_email_password
        FROM companies
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        $_SESSION['error'] = "Company not found.";
        header("Location: support.php");
        exit;
    }

    // Keep old value if field is left empty
    $email = !empty($new_email)
        ? $new_email
        : $company['company_support_email'];

    $password = !empty($new_password)
        ? $new_password
        : $company['support_email_password'];

    // Update
    $stmt = $pdo->prepare("
        UPDATE companies
        SET
        company_support_email = ?,
            support_email_password = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $email,
        $password,
        $company_id
    ]);

    $_SESSION['success'] = "Support service email details updated successfully.";

} catch (PDOException $e) {

    $_SESSION['error'] = "Database Error: " . $e->getMessage();

}

header("Location: support.php");
exit;