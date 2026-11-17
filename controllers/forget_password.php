<?php
include('../config/db.php'); // Make sure this sets up $pdo

$invalid = ''; // Initialize empty error message

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Prepare and execute query
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() === 0) {
            // Email not found
            $error = "This email does not exist in our records.";
            include('../terminal/forget_password.php');
        } else {
            // Email exists - proceed with password recovery
            // e.g., redirect to send OTP page or handle recovery
            $error = ''; // no error
            // header("Location: next_step.php");  // Or your next step
        }
    } else {
        $error = "Please enter your email.";
        include('../terminal/forget_password.php');

    }
}
?>