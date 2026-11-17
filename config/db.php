<?php
session_start();
date_default_timezone_set('Africa/Lagos');

$domain = $_SERVER['HTTP_HOST'];

// Remove "www." only if it appears at the beginning
$domain = preg_replace('/^www\./i', '', $domain);

$_SESSION['domain'] = $domain;


// echo $domain;

// Enable error reporting temporarily for debugging
ini_set('display_errors', 0);            
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ob_start(); // Start output buffering

// Database configuration
$host = '127.0.0.1:3306'; // or use the host provided by Hostinger, e.g., mysql.hostinger.com
$port = 3306;        // MySQL default port
$dbname = 'ubamus';
$username = 'henry';
$password = 'Emmanuel10!!';

date_default_timezone_set('Africa/Lagos');
$date = date("D-M-Y");
$_SESSION['date'] = $date;



try {
    // Create a new PDO connection with host and port separated
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
);

    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 

} catch (PDOException $e) {
    // Handle connection error
    die("Database connection failed: " . $e->getMessage());

}

?>
