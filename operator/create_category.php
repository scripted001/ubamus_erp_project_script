<?php
include('../config/db.php'); // Your PDO connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get user input
    $company_id = $_POST['company_id'];
    $name = trim($_POST['name']);
    $parent_id = $_POST['parent_id'] ?? null;
    $unit_type = $_POST['unit_type'] ?? null;
    $unit_id = $_POST['unit_id'] ?? null;
    $stock_type = $_POST['stock_type'] ?? null;
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'] ?? null;

    // Automatically set parent_id to null (user doesn't have to choose)
    if(empty($parent_id)){
        $parent_id = null;
    }
    

    // Automatically determine level
    $level = 1; // Main category for now

    // Generate a unique, URL-friendly slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    // Ensure slug is unique within the company
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE slug = ? AND company_id = ?");
    $stmt->execute([$slug, $company_id]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $slug .= '-' . time(); // Append timestamp if duplicate
    }
    // 🔒 Prevent duplicate category (same name + stock_type in same company)
$stmt = $pdo->prepare("
    SELECT id FROM product_categories 
    WHERE company_id = ? 
    AND name = ? 
    AND stock_type = ?
");
$stmt->execute([$company_id, $name, $stock_type]);

if ($stmt->rowCount() > 0) {
    echo "<script>alert('Category already exists for this stock type'); window.history.back();</script>";
    exit;
}

    
    // 🔹 Insert category (UPDATED STRUCTURE)
    $stmt = $pdo->prepare("
        INSERT INTO product_categories 
        (company_id, name, slug, parent_id, unit_type, unit_id, stock_type, description, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    $result = $stmt->execute([
        $company_id,
        $name,
        $slug,
        $parent_id,
        $unit_type,
        $unit_id,
        $stock_type,
        $description,
        $created_by
    ]);

    if ($result) {
        echo "<script>alert('Category created successfully'); window.location.href='categories.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error creating category'); window.history.back();</script>";
        exit;
    }
}
?>
