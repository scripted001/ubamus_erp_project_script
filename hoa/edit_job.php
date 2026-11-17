<?php
session_start();
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $job_id = trim($_POST['job_id'] ?? '');
    if (empty($job_id)) {
        // echo "<script>alert('Invalid Job ID.'); window.history.back();</script>";
        exit;
    }

    $updated_at = date('Y-m-d H:i:s');

    // Collect fields to update
    $fields = [];
    $values = [];

    // echo $job_id;

    if (isset($_POST['name']) && $_POST['name'] !== '') {
        $fields[] = "name = ?";
        $values[] = trim($_POST['name']);
    }
    if (isset($_POST['category']) && $_POST['category'] !== '') {
        $fields[] = "category = ?";
        $values[] = trim($_POST['category']);
    }
    if (isset($_POST['unit_price']) && $_POST['unit_price'] !== '') {
        $unit_price = trim($_POST['unit_price']);
        if (!is_numeric($unit_price) || $unit_price < 0) {
            echo "<script>alert('Invalid unit price.'); window.history.back();</script>";
            exit;
        }
        $fields[] = "unit_price = ?";
        $values[] = $unit_price;
    }
    if (isset($_POST['description']) && $_POST['description'] !== '') {
        $fields[] = "description = ?";
        $values[] = trim($_POST['description']);
    }

    if (empty($fields)) {
        echo "<script>alert('No fields to update.'); window.history.back();</script>";
        exit;
    }

    // Always update updated_at
    $fields[] = "updated_at = ?";
    $values[] = $updated_at;

    // Add job_id for WHERE clause
    $values[] = $job_id;

    $sql = "UPDATE job_catalogue SET " . implode(", ", $fields) . " WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($values)) {
        echo "<script>
                alert('Job updated successfully!');
                window.location.href='job-catalogs.php';
              </script>";
        exit;
    } else {
        echo "<script>
                alert('Failed to update job. Please check the data.');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header('Location: job-catalogs.php');
    exit;
}
