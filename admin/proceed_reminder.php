<?php
include('../config/db.php');
include('../config/smtp.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
require '../vendor/fpdf/fpdf.php';



        /* =========================
           INPUT
        ========================= */
        $company_id    = $_POST['company_id'] ?? null;
        $customer_email   = $_POST['customer_email'] ?? null;
        $subject  = $_POST['subject'] ?? null;
        $body = $_POST['body'] ?? 'new';

        // Fetching Company data form emailing and invoice begins
        $stmt = $pdo->prepare("
        SELECT * FROM companies
        WHERE id=?
        ");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
       

  
if (empty($body)) {
    echo "<script>alert('Missing email body.'); window.history.back();</script>";
    exit();
}



    try {

    // -------------------------------
    // EMAIL INVOICE
    // -------------------------------
     
    $smtp_username = $_SESSION['customercare'];      // e.g. support@ubamus.com
    $smtp_password = $_SESSION['customer_care_password'];      // e.g. stored in your DB

    // -------------------------------
    // EMAIL INVOICE
    // -------------------------------
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_username;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom($smtp_username, $company['company_name']);
    $mail->addAddress($customer_email);       // Customer
    $mail->addAddress($company['company_email']); // Company admin
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->send();


        
} catch (Exception $e) {
                echo "<script>alert('Message sent but email not working. Mailer Error: {$mail->ErrorInfo}'); 
                window.open('{$invoice_file}', '_blank');
                window.location.href='customer-debts.php';</script>";
                 exit();
}
       
echo "<script>
        alert('Email successfully sent to proposed customer...');
        window.location.href='customer-debts.php';
    </script>";
        
exit;
          








?>