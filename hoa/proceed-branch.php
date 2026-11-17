<?php

include('../config/db.php');
include('../config/validation.php'); // optional validation functions
include('user_data.php'); // ensures $user_id or session user

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name']);
    $address = trim($_POST['address']);
    $city    = trim($_POST['city']);
    $state   = trim($_POST['state']);

    // Basic validation
    if (empty($name) || empty($address) || empty($city) || empty($state)) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
        exit;
    }

    // Get company_id from logged-in user
    $stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $company_id = $stmt->fetchColumn();

    if (!$company_id) {
        echo "<script>alert('Company not found for this user'); window.history.back();</script>";
        exit;
    }

    // Insert branch
    $stmt2 = $pdo->prepare("INSERT INTO branches (company_id, name, address, city, state) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt2->execute([$company_id, $name, $address, $city, $state]);

    if ($result) {
        echo "<script>alert('Branch added successfully'); window.location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error adding branch'); window.history.back();</script>";
        exit;
    }
} else {
    echo "<script>alert('Invalid request'); window.location.href='index.php';</script>";
    exit;
}
