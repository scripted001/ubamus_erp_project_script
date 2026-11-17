<?php

require '../config/db.php'; // PDO connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

date_default_timezone_set('Africa/Lagos');

if (!isset($_POST['stock_id'], $_POST['created_by'], $_POST['company_id'])) {
    echo "<script>alert('Missing required parameters.'); window.history.back();</script>";
    exit;
}

$stock_id   = $_POST['stock_id'];
$created_by = $_POST['created_by'];
$company_id = $_POST['company_id'];

try {
    $pdo->beginTransaction();

    // Fetch stock details
    $stmt = $pdo->prepare("
        SELECT s.id, s.product_id, s.quantity, s.location_id, s.status,
               p.name AS product_name,
               l.name AS location_name,
               c.company_name, c.company_email
        FROM stocks s
        LEFT JOIN products p ON s.product_id = p.id
        LEFT JOIN locations l ON s.location_id = l.id
        LEFT JOIN companies c ON s.company_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$stock_id]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock) {
        throw new Exception("Stock record not found.");
    }

   

    $deleted_quantity = $stock['quantity'];
    $product_name  = $stock['product_name'];
    $location_name = $stock['location_name'];
    $company_email = $stock['company_email'];
    $company_name  = $stock['company_name'];

  

// 🔥 HARD DELETE
$delete = $pdo->prepare("DELETE FROM stocks WHERE id = ?");
$delete->execute([$stock_id]);

// Log movement (AUDIT SAFE)
$stmtMovement = $pdo->prepare("
    INSERT INTO stock_movements
    (company_id, product_id, from_location_id, to_location_id, quantity, movement_type, created_by, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmtMovement->execute([
    $company_id,
    $stock['product_id'],
    $stock['location_id'],
    null,              // no destination
    $deleted_quantity,
    'hard_deleted',
    $created_by
]);

$pdo->commit();


    // Get deleter name
    $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id=?");
    $stmtUser->execute([$created_by]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $deleted_by_name = $user['username'] ?? 'Unknown';

    // Send Email
    $mail = new PHPMailer(true);

   
 // --- SEND EMAIL TO COMPANY ---
    $mail = new PHPMailer(true);
   try {
    $mail->SMTPDebug = 2; // remove after fixing
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@ubamus.com';
    $mail->Password   = 'Zaida@25';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('support@ubamus.com', 'Ubamus Global');
    $mail->addAddress($company_email, $company_name);

    $mail->isHTML(true);
    $mail->Subject = "Stock Deleted Notification - $company_name";
    $mail->Body = "
        <p>Dear $company_name,</p>
        <p>The following stock has been deleted:</p>
        <ul>
            <li><b>Product:</b> $product_name</li>
            <li><b>Location:</b> $location_name</li>
            <li><b>Quantity:</b> $deleted_quantity</li>
            <li><b>Deleted By:</b> $deleted_by_name</li>
            <li><b>Time:</b> ".date('Y-m-d H:i:s')."</li>
        </ul>
    ";

    $mail->send();  

        echo "<script>alert('Stock deleted successfully and company notified.'); window.location.href='stocks.php';</script>";

    } catch (Exception $e) {
        echo "<script>alert('Stock deleted but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='stocks.php';</script>";
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<script>alert('Error: {$e->getMessage()}'); window.history.back();</script>";
}
