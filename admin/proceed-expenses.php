<?php
include('../config/db.php');
include('../config/smtp.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect POST data
    $company_id = $_SESSION['company_id'] ?? '';
    $created_by = $_SESSION['user_email'] ?? '';
    $approved_by = $_POST['approved_by'] ?? 'Pending';
    $expense_title = $_POST['expense_title'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $description = $_POST['description'] ?? '';
    // $location_id = $_POST['location_id'] ?? '';
    $location_id = $_SESSION['location_id'];
    $staff_responsible = $_POST['staff_responsible'] ?? '';

    // Validate required fields
    if (!$expense_title || !$amount || !$location_id || !$staff_responsible || !$created_by) {
        echo "<script>alert('Please Check all fields'); window.history.back();</script>";
        exit;
    }

    // Handle optional receipt upload
    $receipt_file_name = null;
    $uploadDir = '../uploads/receipts/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['receipt']['tmp_name'];
        $fileName = $_FILES['receipt']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','pdf'];

        if (!in_array($fileExt, $allowed)) {
            echo "<script>alert('Invalid receipt file type. Only JPG, JPEG, PNG, PDF allowed.'); window.history.back();</script>";
            exit;
        }

        $receipt_file_name = md5(time() . $fileName) . '.' . $fileExt;
        $destPath = $uploadDir . $receipt_file_name;

        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            echo "<script>alert('Failed to upload receipt. Check folder permissions.'); window.history.back();</script>";
            exit;
        }
    }

    // Insert into expenses table
    try {
        $stmt = $pdo->prepare("
            INSERT INTO expenses 
            (company_id, expense_title, amount, location_id, staff_responsible, created_by, description, receipt, created_at, approved_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $company_id,
            $expense_title,
            $amount,
            $location_id,
            $staff_responsible,
            $created_by,
            $description,
            $receipt_file_name,
            date('Y-m-d H:i:s'),
            $approved_by
        ]);

        $expense_id = $pdo->lastInsertId();

        // Fetch company email
        $stmt2 = $pdo->prepare("SELECT company_email, company_name FROM companies WHERE id = ?");
        $stmt2->execute([$company_id]);
        $company = $stmt2->fetch();

        if ($company) {
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
                $mail->addAddress($company['company_email']);

                $mail->isHTML(true);
                $mail->Subject = "New Expense Submitted {$company['company_name']}";

                // Fetch location name for email
                $location_name = "Unknown Location";
                $stmtLoc = $pdo->prepare("SELECT name, type, city, state FROM locations WHERE id = ?");
                $stmtLoc->execute([$location_id]);
                $location = $stmtLoc->fetch();
                $type = $location['type'];

                if ($location) {
                    $location_name = ucfirst($location['name']) . " ({$location['type']}) — " 
                                     . ucfirst($location['city']) . ", " . ucfirst($location['state']);
                }

                // Get staff fullname from created_by (users.id -> users.email -> staffs.email)
                $submitted_by = "Unknown User";

            
                    $stmtStaff = $pdo->prepare("SELECT firstname, lastname FROM staffs WHERE id = ? LIMIT 1");
                    $stmtStaff->execute([$staff_responsible]);
                    $staff = $stmtStaff->fetch(PDO::FETCH_ASSOC);

                    $staff_fullname = trim($staff['firstname'] . ' ' . $staff['lastname']);

                    if ($staff) {
                        $submitted_by = $staff_fullname;
                    }
               
                
                // echo $submitted_by;
                // Email body
                $body  = "<h3>New Expense Submitted</h3>";
                $body .= "<p><strong>Title:</strong> $expense_title</p>";
                $body .= "<p><strong>Type:</strong> $type</p>";
                $body .= "<p><strong>Amount:</strong> $amount</p>";
                $body .= "<p><strong>Location:</strong> $location_name</p>";
                $body .= "<p><strong>Staff Responsible:</strong> $submitted_by</p>";
                $body .= "<p><strong>Description:</strong> $description</p>";
                $body .= "<p><strong>Submitted By:</strong> $created_by</p>";
                $body .= "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . " (Lagos Time)</p>";

                $mail->Body = $body;

                // Attach receipt if uploaded
                if ($receipt_file_name) {
                    $mail->addAttachment($destPath);
                }

                $mail->send();

            } catch (Exception $e) {
                echo "<script>alert('Expense created but email failed: {$mail->ErrorInfo}'); window.location.href='expenses.php';</script>";
                exit;
            }
        }

        // Success alert + redirect
        echo "<script>alert('Expense created successfully!'); window.location.href='expenses.php';</script>";
        exit;

    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }

} else {
    echo "<script>alert('Invalid request'); window.location.href='expenses.php';</script>";
    exit;
}
