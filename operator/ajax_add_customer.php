<?php
include('../config/db.php');

$firstname = $_POST['firstname'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$branch_id = $_POST['branch_id'] ?? '';
$company_id = $_POST['company_id'] ?? '';

if(!$firstname || !$phone){ echo json_encode(['success'=>false, 'error'=>'Missing data']); exit(); }

try{
    $stmt = $pdo->prepare("INSERT INTO customers (firstname,email,phone,branch_id,company_id,created_at,status) VALUES (?,?,?,?,?,NOW(),1)");
    $stmt->execute([$firstname,$email,$phone,$branch_id,$company_id]);
    $id = $pdo->lastInsertId();
    echo json_encode(['success'=>true,'id'=>$id]);
}catch(Exception $e){
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>
