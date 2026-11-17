<?php
include('../config/db.php');
$company_id     = $_SESSION['companyid'];
$created_by     = $_SESSION['created_by'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize form inputs
   
    $type           = $_POST['type'] ?? '';
    $name           = trim($_POST['name'] ?? '');
    $reference_id   = trim($_POST['reference_id'] ?? '');
    $address_line1  = trim($_POST['address_line1'] ?? '');
    $address_line2  = trim($_POST['address_line2'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $state          = $_POST['state'] ?? '';
    $lga            = $_POST['lga'] ?? '';
    $status         = 1;
   
  

    // Basic validation
    if (empty($company_id) || empty($created_by) || empty($type) || empty($name) || empty($address_line1) || empty($city) || empty($state) || empty($lga)) {
        echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
        exit;
    }

    if (isset($nigeriaLGAs[$state]) && !in_array($lga, $nigeriaLGAs[$state])) {
        echo "<script>alert('Selected LGA does not match the selected State.'); window.history.back();</script>";
        exit;
    }

    try {
        // Prepare the insert statement
      $created_at = (new DateTime("now", new DateTimeZone('Africa/Lagos')))->format('Y-m-d H:i:s');

$stmt = $pdo->prepare("
    INSERT INTO locations
    (company_id, type, name, created_by, address_line1, address_line2, city, state, lga, status, created_at)
    VALUES
    (:company_id, :type, :name, :created_by, :address_line1, :address_line2, :city, :state, :lga, :status, :created_at)
");

// Bind parameters
$stmt->bindParam(':company_id', $company_id);
$stmt->bindParam(':type', $type);
$stmt->bindParam(':name', $name);
$stmt->bindParam(':created_by', $created_by);
$stmt->bindParam(':address_line1', $address_line1);
$stmt->bindParam(':address_line2', $address_line2);
$stmt->bindParam(':city', $city);
$stmt->bindParam(':state', $state);
$stmt->bindParam(':lga', $lga);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':created_at', $created_at); // Lagos time used here

        // Execute
        $stmt->execute();

        echo "<script>alert('Location created successfully!'); window.location.href='locations.php';</script>";

    } catch (PDOException $e) {
        echo "<script>alert('Error creating location: ". addslashes($e->getMessage()) ."'); window.history.back();</script>";
        exit;
    }

} else {
    header('Location: location.php');
    exit;
}
