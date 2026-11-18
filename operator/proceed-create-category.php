<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php'); 
$location_id = $_SESSION['location_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Invalid request');window.location.href='job-categories.php';</script>";
    exit;
}

/* ============================
   GET FORM VALUES
============================ */
$name        = trim($_POST['name'] ?? '');
$location        = $_POST['location_id'];
$company_id  = $_SESSION['company_id'] ?? null;

$created_by  = $_SESSION['user_email'] ?? 'admin';
$new_loc = $location;



if ($new_loc <= 0) {
    echo "<script>alert('Location not defined!');window.location.href='service-categories.php';</script>";
    exit;

}

/* ============================
   VALIDATION
============================ */
if (empty($name)) {
    echo "<script>alert('Category name is required');history.back();</script>";
    exit;
}

if (!$company_id || !$location_id) {
    echo "<script>alert('Session expired. Login again');window.location.href='logout.php';</script>";
    exit;
}




try {

    /* ============================
       CHECK IF CATEGORY EXISTS
    ============================ */
    $check = $pdo->prepare("
        SELECT id FROM job_categories 
        WHERE name=? AND company_id=? AND location_id=?
        LIMIT 1
    ");
    $check->execute([$name,$company_id,$location_id]);

    if($check->fetch()){
        echo "<script>alert('Category already exists');history.back();</script>";
        exit;
    }

    /* ============================
       INSERT CATEGORY
    ============================ */
    $stmt = $pdo->prepare("
        INSERT INTO job_categories
        (name, company_id, location_id, created_by, date)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $name,
        $company_id,
        $new_loc,
        $created_by
    ]);

    echo "<script>
        alert('Category created successfully');
        window.location.href='service-categories.php';
    </script>";
    exit;

} catch (Exception $e) {

    error_log($e->getMessage());

    echo "<script>
        alert('System error. Try again');
        history.back();
    </script>";
    exit;
}
?>
