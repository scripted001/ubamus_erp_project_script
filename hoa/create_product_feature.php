<?php
include('../config/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $product_id    = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $feature_name  = trim($_POST['feature_name'] ?? '');
        $feature_value = trim($_POST['feature_value'] ?? '');
        $created_at    = date('Y-m-d H:i:s');

        // Validate input
        if ($product_id <= 0 || empty($feature_name) || empty($feature_value)) {
            throw new Exception("Missing required fields.");
        }

        // Insert feature into the database
        $stmt = $pdo->prepare("INSERT INTO product_features (product_id, feature_name, feature_value, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $feature_name, $feature_value, $created_at]);

        echo "<script>alert('Feature added successfully.'); window.location.href = 'products.php?id={$product_id}';</script>";
        exit;

    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        echo "<script>alert('Database error occurred.'); window.history.back();</script>";
    } catch (Exception $e) {
        echo "<script>alert('" . $e->getMessage() . "'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request method.'); window.history.back();</script>";
}
?>
