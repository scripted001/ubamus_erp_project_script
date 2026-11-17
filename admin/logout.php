<?php
session_start();
ob_start();

session_destroy();
ob_flush();
header('location: ../index.php');
exit();

?>