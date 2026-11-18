<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'];
$unit_type = $_POST['unit_type'] ?? '';

if($unit_type != ''){

    $stmt = $pdo->prepare("SELECT * FROM units WHERE company_id = ? AND unit_type = ?");
    $stmt->execute([$company_id, $unit_type]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($units){
        echo '<option value="">-- Select Unit --</option>';
        foreach($units as $unit){
            echo '<option value="'.$unit['id'].'">'.$unit['unit_name'].'</option>';
        }
    } else {
        echo '<option value="">No units found</option>';
    }

} else {
    echo '<option value="">Select Unit Type First</option>';
}