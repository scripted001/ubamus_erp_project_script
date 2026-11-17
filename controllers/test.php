<?php
session_start();
require_once '../config/db.php';

$password = "Emmanuel10!!";
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo $hashed;

?>