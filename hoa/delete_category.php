<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');

// Only proceed if a valid POST request is made
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
    $categoryID = $_POST['category_id'];
    $companyID  = $_SESSION['company_id'] ?? null;

    if (!$companyID) {
        echo "Unauthorized access. Company ID missing.";
        exit;
    }

    try {
        // Optional: Check if category exists and belongs to the current company
        $check = $pdo->prepare("SELECT * FROM product_categories WHERE id = ? AND company_id = ?");
        $check->execute([$categoryID, $companyID]);
        $category = $check->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            echo "Category not found or access denied.";
            exit;
        }

        // Delete category
        $stmt = $pdo->prepare("DELETE FROM product_categories WHERE id = ? AND company_id = ?");
        $stmt->execute([$categoryID, $companyID]);

        echo "<script>alert('Category deleted successfully.'); window.location.href = 'categories.php';</script>";
        exit;
    } catch (PDOException $e) {
        echo "Delete error: " . $e->getMessage();
        exit;
    }
} else {
    echo "Invalid request.";
    exit;
}
?>
