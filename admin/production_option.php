<?php
include('../config/db.php');
session_start();

$company_id = $_SESSION['company_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$company_id || !$user_id) {
    die("Company or User not set in session.");
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Collect form data
$production_stage = $_POST['production_stage'] ?? '';

// echo $production_stage;

if($production_stage == "New") {
    header("location: new_production.php");
    exit();
}
if($production_stage == "Existing") {
    header("location: next.php");
    exit();
}
if($production_stage == "Produced") {
    header("location: next_produced.php");
    exit();
}






?>
