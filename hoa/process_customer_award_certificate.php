<?php
include('../config/db.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
require '../vendor/fpdf/fpdf.php';

$created_at = $_SESSION['date'];



// echo $date;

/* =========================
   INVOICE NUMBER
========================= */
$count = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn() + 1;

$certificate_number = 'UBM-CERT-CUSTOMER-' . date('Ymd') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);



    if (empty($_SESSION['user_id'])) {
        exit("<script>alert('Login required'); window.location.href='../views/login.php';</script>");
    }

    $user_id = $_SESSION['user_id'];

        /* =========================
           INPUT
        ========================= */
        $company_id    = $_SESSION['company_id'] ?? null;
        $location_id   = $_SESSION['location_id'] ?? null;
        $customer_id  = $_POST['customer_id'] ?? null;
       

?>