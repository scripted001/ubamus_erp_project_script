<?php

session_start();

include('config/db.php');
include('config/smtp.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request.');
}

// --------------------------------------------------
// GET FORM DATA
// --------------------------------------------------

$company_id    = trim($_POST['company_id'] ?? '');
$fullname      = trim($_POST['fullname'] ?? '');
$email         = trim($_POST['email'] ?? '');
$business_name = trim($_POST['business_name'] ?? '');
$subject       = trim($_POST['subject'] ?? '');
$message       = trim($_POST['message'] ?? '');


// --------------------------------------------------
// VALIDATION
// --------------------------------------------------

if (
    empty($company_id) ||
    empty($fullname) ||
    empty($email) ||
    empty($subject) ||
    empty($message)
) {
    exit('Please complete all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Please enter a valid email address.');
}

if (strlen($fullname) > 150) {
    exit('Full name is too long.');
}

if (strlen($business_name) > 200) {
    exit('Business name is too long.');
}

if (strlen($subject) > 200) {
    exit('Subject is too long.');
}

if (strlen($message) > 5000) {
    exit('Message is too long.');
}


// --------------------------------------------------
// COMPANY SMTP DETAILS
// --------------------------------------------------

$smtp_username = $_SESSION['customercare'] ?? '';
$smtp_password = $_SESSION['customer_care_password'] ?? '';


// Make sure SMTP credentials exist
if (empty($smtp_username) || empty($smtp_password)) {
    error_log('Contact form: SMTP credentials are missing from session.');

    exit('Email service is currently unavailable. Please try again later.');
}


// Validate SMTP email
if (!filter_var($smtp_username, FILTER_VALIDATE_EMAIL)) {
    error_log('Contact form: Invalid customer-care SMTP email.');

    exit('Email service configuration is invalid.');
}


// --------------------------------------------------
// SEND EMAIL
// --------------------------------------------------

$mail = new PHPMailer(true);

try {

    // SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_username;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;


    // --------------------------------------------------
    // SENDER
    // --------------------------------------------------

    $mail->setFrom(
        $smtp_username,
        'Website Contact Form'
    );


    // --------------------------------------------------
    // SEND TO COMPANY CUSTOMER CARE
    // --------------------------------------------------

    $mail->addAddress(
        $smtp_username,
        'Customer Care'
    );


    // --------------------------------------------------
    // REPLY DIRECTLY TO THE PERSON WHO CONTACTED YOU
    // --------------------------------------------------

    $mail->addReplyTo(
        $email,
        $fullname
    );


    // --------------------------------------------------
    // EMAIL CONTENT
    // --------------------------------------------------

    $mail->isHTML(true);

    $mail->Subject = 'Contact Form: ' . $subject;


    // Escape values before putting them into HTML
    $safeName = htmlspecialchars(
        $fullname,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeEmail = htmlspecialchars(
        $email,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeBusiness = htmlspecialchars(
        $business_name,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeSubject = htmlspecialchars(
        $subject,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeMessage = nl2br(
        htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
    );


    // --------------------------------------------------
    // HTML EMAIL
    // --------------------------------------------------

    $mail->Body = '

    <div style="
        font-family: Arial, Helvetica, sans-serif;
        max-width: 700px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    ">

        <div style="
            background: #0f172a;
            color: #ffffff;
            padding: 25px;
        ">
            <h2 style="margin:0;">
                New Contact Form Message
            </h2>

            <p style="
                margin:8px 0 0;
                color:#cbd5e1;
            ">
                A visitor has submitted a message through your website.
            </p>
        </div>


        <div style="padding:25px;">

            <table
                width="100%"
                cellpadding="8"
                cellspacing="0"
                style="border-collapse:collapse;"
            >

                <tr>
                    <td style="font-weight:bold;width:150px;">
                        Full Name
                    </td>

                    <td>
                        ' . $safeName . '
                    </td>
                </tr>


                <tr>
                    <td style="font-weight:bold;">
                        Email
                    </td>

                    <td>
                        <a href="mailto:' . $safeEmail . '">
                            ' . $safeEmail . '
                        </a>
                    </td>
                </tr>


                <tr>
                    <td style="font-weight:bold;">
                        Business
                    </td>

                    <td>
                        ' . ($safeBusiness ?: 'Not provided') . '
                    </td>
                </tr>


                <tr>
                    <td style="font-weight:bold;">
                        Subject
                    </td>

                    <td>
                        ' . $safeSubject . '
                    </td>
                </tr>

            </table>


            <hr style="
                border:none;
                border-top:1px solid #e5e7eb;
                margin:25px 0;
            ">


            <h3 style="margin-bottom:10px;">
                Message
            </h3>


            <div style="
                background:#f8fafc;
                padding:20px;
                border-radius:6px;
                line-height:1.7;
            ">
                ' . $safeMessage . '
            </div>

        </div>


        <div style="
            background:#f8fafc;
            padding:15px 25px;
            color:#64748b;
            font-size:13px;
        ">
            This message was submitted through the website contact form.
        </div>

    </div>

    ';


    // --------------------------------------------------
    // PLAIN TEXT VERSION
    // --------------------------------------------------

    $mail->AltBody =
        "New Contact Form Message\n\n" .
        "Full Name: {$fullname}\n" .
        "Email: {$email}\n" .
        "Business: " . ($business_name ?: 'Not provided') . "\n" .
        "Subject: {$subject}\n\n" .
        "Message:\n" .
        $message;


    // --------------------------------------------------
    // SEND
    // --------------------------------------------------

    $mail->send();


    // --------------------------------------------------
    // SUCCESS
    // --------------------------------------------------

    echo '
        <script>
            alert("Your message has been sent successfully.");
            window.history.back();
        </script>
    ';

    exit;


} catch (Exception $e) {

    // Log actual PHPMailer error
    error_log(
        'Contact form email error: ' .
        $mail->ErrorInfo
    );


    // User-friendly message
    echo '
        <script>
            alert("Your message could not be sent at this time. Please try again later.");
            window.history.back();
        </script>
    ';

    exit;
}
?>