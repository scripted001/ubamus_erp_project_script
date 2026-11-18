<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get POST data
    $category_id = $_POST['category_id'] ?? null;
    $company_id  = $_POST['company_id'] ?? null;
    $location_id = $_POST['location_id'] ?? null;

    // Validate required fields
    if (!$category_id || !$company_id || !$location_id) {
        echo "<script>alert('Missing required data');history.back();</script>";
        exit;
    }

    try {
        // Delete the category matching id, company_id, and location_id
        $stmt = $pdo->prepare("
            DELETE FROM job_categories
            WHERE id = ? AND company_id = ? AND location_id = ?
            LIMIT 1
        ");
        $stmt->execute([$category_id, $company_id, $location_id]);

        if ($stmt->rowCount() > 0) {
            echo "<script>
                alert('Category deleted successfully');
                window.location.href = 'service-categories.php';
            </script>";
        } else {
            echo "<script>
                alert('Category not found or already deleted');
                history.back();
            </script>";
        }
    } catch (Exception $e) {
        echo "<script>
            alert('Error deleting category. Please try again');
            history.back();
        </script>";
    }
} else {
    echo "<script>alert('Invalid request');history.back();</script>";
    exit;
}


?>