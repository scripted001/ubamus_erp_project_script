<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../config/db.php');

echo "DB included successfully.<br>";

// Test PDO
try {
    $stmt = $pdo->query("SELECT NOW() AS `current_time`"); // use backticks
    $row = $stmt->fetch();
    echo "DB connection works. Current time: " . $row['current_time'] . "<br>";
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
