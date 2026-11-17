<?php
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if($user == true){
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['company_id'] = $user['company_id'];
    $_SESSION['location_id'] = $user['location_id'];
    $_SESSION['user_email'] = $user['email'];

}else{
    header('location: ../index.php');
    exit();
}


?>