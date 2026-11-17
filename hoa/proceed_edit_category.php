<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');

if (isset($_POST['submit'])) {
    $categoryID = $_POST['category_id'] ?? null;

    if (!$categoryID) {
        echo "Category ID is missing.";
        exit;
    }

    // Step 1: Fetch existing data
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
        $stmt->execute([$categoryID]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Fetch error: " . $e->getMessage();
        exit;
    }

    if (!$existing) {
        echo "Category not found.";
        exit;
    }

    // Step 2: Sanitize and fall back to existing
    $name        = !empty($_POST['name'])        ? $_POST['name']        : $existing['name'];
    $parent_id   = isset($_POST['parent_id']) && $_POST['parent_id'] !== "" ? $_POST['parent_id'] : $existing['parent_id'];
    $level       = !empty($_POST['level'])       ? $_POST['level']       : $existing['level'];
    $description = !empty($_POST['description']) ? $_POST['description'] : $existing['description'];
    $status      = !empty($_POST['status'])      ? $_POST['status']      : $existing['status'];
    $company_id  = $_SESSION['company_id'] ?? $existing['company_id'];

    // Handle icon
    $icon = $existing['icon'];
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $iconName = time() . '_' . basename($_FILES['icon']['name']);
        $iconTmp = $_FILES['icon']['tmp_name'];
        $uploadDir = 'uploads/category_icons/'; // Save relative path
        $uploadPath = '../' . $uploadDir;

        // Create folder if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Move file
        if (move_uploaded_file($iconTmp, $uploadPath . $iconName)) {
            $icon = $uploadDir . $iconName; // Save clean path to DB
        } else {
            echo "Icon upload failed.";
            exit;
        }
    }

    $updated_at = date('Y-m-d H:i:s');

    // Step 3: Update DB
    try {
        $stmt = $pdo->prepare("
            UPDATE product_categories 
            SET company_id = ?, name = ?, parent_id = ?, level = ?, description = ?, icon = ?, status = ?, updated_at = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $company_id, $name, $parent_id,
            $level, $description, $icon, $status,
            $updated_at, $categoryID
        ]);

        echo "<script>alert('Category updated successfully.'); window.location.href = 'categories.php';</script>";
        exit;
    } catch (PDOException $e) {
        echo "Update error: " . $e->getMessage();
        exit;
    }
}
?>
