<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $job_id      = $_POST['job_id'] ?? null;
    $company_id  = $_POST['company_id'] ?? null;
    $location_id = $_POST['location_id'] ?? null;

    if (!$job_id || !$company_id || !$location_id) {
        echo "<script>alert('Missing required data');history.back();</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        // ================================
        // 1. CHECK IF CATALOGUE IS USED
        // ================================
        $checkUsage = $pdo->prepare("
            SELECT COUNT(*) 
            FROM service_items 
            WHERE service_id = ?
            LIMIT 1
        ");
        $checkUsage->execute([$job_id]);
        $usedCount = $checkUsage->fetchColumn();

        if ($usedCount > 0) {
            $pdo->rollBack();
            echo "<script>
                alert('This service has already been rendered and cannot be deleted');
                history.back();
            </script>";
            exit;
        }

        // ================================
        // 2. GET CATALOGUE DETAILS
        // ================================
        $stmt = $pdo->prepare("
            SELECT *
            FROM services
            WHERE id = ? AND company_id = ? AND location_id = ?
            LIMIT 1
        ");
        $stmt->execute([$job_id, $company_id, $location_id]);
        $catalogue = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$catalogue) {
            $pdo->rollBack();
            echo "<script>alert('Service not found');history.back();</script>";
            exit;
        }

        $material_id  = $catalogue['material_id'];
        $material_qty = $catalogue['material-quantity'];

        // ================================
        // 3. RETURN MATERIAL TO STOCK
        // ================================
        if ($material_id && $material_qty > 0) {
            $updateMaterial = $pdo->prepare("
                UPDATE materials 
                SET quantity = quantity + ?
                WHERE id = ? AND company_id = ? AND location_id = ?
                LIMIT 1
            ");
            $updateMaterial->execute([
                $material_qty,
                $material_id,
                $company_id,
                $location_id
            ]);
        }

        // ================================
        // 4. DELETE CATALOGUE
        // ================================
        $delete = $pdo->prepare("
            DELETE FROM job_catalogue
            WHERE id = ? AND company_id = ? AND location_id = ?
            LIMIT 1
        ");
        $delete->execute([$job_id, $company_id, $location_id]);

        if ($delete->rowCount() > 0) {
            $pdo->commit();
            echo "<script>
                alert('Catalogue deleted successfully and material returned to stock');
                window.location.href='job-catalogs.php';
            </script>";
        } else {
            $pdo->rollBack();
            echo "<script>alert('Delete failed');history.back();</script>";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('System error occurred');history.back();</script>";
    }

} else {
    echo "<script>alert('Invalid request');history.back();</script>";
    exit;
}
?>
