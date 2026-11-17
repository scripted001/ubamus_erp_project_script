<?php

include('../config/db.php');
include('user_data.php'); // Contains session user info

date_default_timezone_set('Africa/Lagos');

// ===============================
// VALIDATION
// ===============================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    exit('Invalid request');
}

if (!isset($_SESSION['company_id'], $_SESSION['user_id'])) {
    exit('Unauthorized');
}

// ===============================
// INPUT VALIDATION
// ===============================
if (!isset($_GET['location_id']) || !is_numeric($_GET['location_id'])) {
    exit('Invalid location');
}

$location_id = (int) $_GET['location_id'];
$company_id  = $_SESSION['company_id'];

// ===============================
// SAFETY CHECK (LOCATION IN USE)
// ===============================
$checkStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM staffs 
    WHERE location_id = ? AND company_id = ?
");
$checkStmt->execute([$location_id, $company_id]);

if ($checkStmt->fetchColumn() > 0) {
    echo "<script>
        alert('This location cannot be deleted because staff are assigned to it.');
        window.history.back();
    </script>";
    exit;
}

// ===============================
// DELETE LOCATION
// ===============================
$stmt = $pdo->prepare("
    DELETE FROM locations 
    WHERE id = ? AND company_id = ?
");
$stmt->execute([$location_id, $company_id]);

// ===============================
// REDIRECT BACK
// ===============================
echo "<script>
    alert('Location deleted successfully!');
    window.location.href='locations.php';
</script>";
    