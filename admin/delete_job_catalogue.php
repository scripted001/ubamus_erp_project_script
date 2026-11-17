<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');

// Ensure ID was passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>
            alert('Invalid job selected.');
            window.location.href='job-catalogs.php';
          </script>";
    exit;
}

$job_id = intval($_GET['id']);
$company_id = $_SESSION['company_id'] ?? 0;

// Safety check: confirm job exists
$checkStmt = $pdo->prepare("SELECT * FROM job_catalogue WHERE id = ? AND company_id = ?");
$checkStmt->execute([$job_id, $company_id]);
$job = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "<script>
            alert('Job not found or you do not have permission to delete it.');
            window.location.href='job-catalogs.php';
          </script>";
    exit;
}

// Delete the job
$stmt = $pdo->prepare("DELETE FROM job_catalogue WHERE id = ? AND company_id = ?");
try {
    $stmt->execute([$job_id, $company_id]);

    echo "<script>
            alert('Job \"". addslashes($job['job_name']) ."\" deleted successfully!');
            window.location.href='job-catalogs.php';
          </script>";
    exit;

} catch (Exception $e) {
    echo "<script>
            alert('Error deleting job: " . addslashes($e->getMessage()) . "');
            window.location.href='job-catalogs.php';
          </script>";
    exit;
}
