<?php
  include('../config/db.php');
  include('../config/validation.php');
  include('user_data.php');
  include('../app_data.php');
  include('../company_data.php');

  $company_id = $_SESSION['company_id'];
  $user_id = $_SESSION['user_id'];

//   echo $user_id;
//   Fetch user from user dashboard
$stmt = $pdo->prepare("SELECT `email` FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// fetching staff data using user_email
$stmt = $pdo->prepare("SELECT id FROM staffs WHERE email = ?");
$stmt->execute([$userData['email']]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// echo $staff['id'];



  date_default_timezone_set('Africa/Lagos');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

    
    $location_id      = $_POST['location_id'] ?? [];
    $name      = $_POST['name'] ?? [];
    $created_by = $staff['id'];
    $created_at = date('Y-m-d H:i:s');

    // Insert staff into database
    $stmt = $pdo->prepare("
        INSERT INTO production_team 
        (company_id, location_id, name, created_by, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $company_id, $location_id, $name, $created_by, $created_at
    ]);
    echo "<script>alert('Team created successfully.'); window.location.href='production_teams.php';</script>";
    exit;

} catch (Exception $e) {
    echo "<script>alert('Failed to create staff: " . addslashes($e->getMessage()) . "'); window.location.href='production_teams.php';</script>";
    exit;
}
}
