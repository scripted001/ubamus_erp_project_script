<?php
include('../config/db.php');
include('../config/smtp.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


// PHPMailer namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

date_default_timezone_set('Africa/Lagos');
$createdAt = date('Y-m-d H:i:s');
$changedAt = $createdAt;
// Staff session data
$staff_location_id = $_SESSION['staff_location_id'] ?? null;
$companyid = $_SESSION['company_id'] ?? null;
$username = $_SESSION['staff_username'] ?? null;
$email = $_SESSION['email'] ?? null;
$password = md5($createdAt);
$role = $_SESSION['staff_role'] ?? null;
$status = 'active'; // explicitly set

if (!$staff_location_id || !$companyid || !$username || !$email || !$password || !$role) {
    echo "<script>alert('Staff session data missing.'); window.location.href='new_staff.php';</script>";
   
    exit;
}
 
// Hash password for storage
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$createdBy = $_SESSION['email'] ?? null; // who created this staff

try {
    // Fetch company info
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$companyid]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo "<script>alert('Company not found.'); window.location.href='new_staff.php';</script>";
        exit;
    }
    $company_name = $company['company_name'];
    $company_email = $company['company_email'];
    $company_logo = $company['company_logo'];
    $company_domain = $company['company_domain'];

    // Prevent duplicate user
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND company_id = ?");
    $checkStmt->execute([$email, $companyid]);

    if ($checkStmt->rowCount() > 0) {
        echo "<script>alert('User already exists in dashboard.'); window.location.href='new_staff.php';</script>";
        exit;
    }

    

    // Insert new user into users table
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (username, email, password, role, company_id, location_id, status, created_at, created_by, changed_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $username,
        $email,
        $hashedPassword,
        $role,
        $companyid,
        $staff_location_id,
        $status,
        $createdAt,
        $createdBy,
        $changedAt
    ]);

    // Send welcome email
    $mail = new PHPMailer(true);
    try {
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
        $mail->addAddress($email, $username);

        $mail->isHTML(true);
        $mail->Subject = 'Your Admin Account Credentials';
        $mail->Body = '
            <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($company_name) . ' - Staff Account Created</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; }
                .header { text-align: center; padding-bottom: 20px; }
                .header img { max-width: 150px; margin-bottom: 10px; }
                .header h2 { color: #1d4ed8; }
                .content { color: #555555; line-height: 1.6; }
                .button-container { text-align: center; margin: 30px 0; }
                .btn { background-color: #1d4ed8; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .footer { font-size: 12px; color: #999999; text-align: center; margin-top: 20px; }
                @media only screen and (max-width: 600px) {
                    .container { padding: 15px; }
                    .btn { width: 100%; display: block; padding: 12px 0; }
                }
            </style>
            </head>
            <body>
            <div class="container">
            <div class="header">                
            <img src="https://' . htmlspecialchars($company_domain) . '/uploads/logos/' . htmlspecialchars($company_logo) . '" alt="Company Logo">
            <h2>Welcome to ' . htmlspecialchars($company_name) . '!</h2>
        </div>
        <div class="content">
            <p>Hi <b>' . htmlspecialchars($username) . '</b>,</p>
            <p>Welcome to the team! We are thrilled to have you join ' . htmlspecialchars($company_name) . '.</p>
            <p>Below are your login credentials:</p>
            <p><b>Username:</b> ' . htmlspecialchars($email) . '<br>
            <b>Temporary Password:</b> ' . htmlspecialchars($password) . '</p>
            <p>Please login and change your password immediately.</p>
            <div class="button-container">
                <a href="https://' . htmlspecialchars($company_domain) . '" class="btn" style="color: #fff;">Login to Your Account</a>
            </div>
            <br><br>
            <p>Warm regards,<br>The <b>' . htmlspecialchars($company_name) . '</b> Team</p>
        </div>
        <div class="footer">&copy; ' . date('Y') . ' ' . htmlspecialchars($company_name) . '. All rights reserved.</div>
            </div>
            </body>
            </html>';

        $mail->send();
        // echo 'Sent and email done';
        echo "<script>alert('Staff successfully created and email sent.'); window.location.href='staffs.php';</script>";

    } catch (Exception $e) {
        // echo 'sent but email not done';
        echo "<script>alert('Staff created but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='staffs.php';</script>";
    }

} catch (Exception $e) {
    // echo 'not working at all';
    echo "<script>alert('Error inserting user: " . addslashes($e->getMessage()) . "'); window.location.href='new_staff.php';</script>";
}
?>
