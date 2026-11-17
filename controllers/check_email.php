<?php

// INCLUDE DB CONNECTION
require_once '../config/db_connect.php'; 

// Get email from AJAX
$email = $_POST['email'] ?? '';
$email = trim($email);

// Return not_exists if empty
if ($email === "") {
    echo "not_exists";
    exit();
}

try {
    $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    // Return clean, consistent responses for JS
    if ($stmt->fetch()) {
        echo "exists";        // Email found
    } else {
        echo "not_exists";    // Email not found
    }

} catch (PDOException $e) {
    echo "error";             // DB problem
}
?>
