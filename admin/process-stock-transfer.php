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

session_start();
date_default_timezone_set('Africa/Lagos');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stock_id       = $_POST['stock_id'] ?? null;
    $to_location_id = $_POST['to_location_id'] ?? null;
    $trans_quantity       = $_POST['quantity'] ?? null;
    $company_id     = $_POST['company_id'] ?? null;
    $created_by     = $_POST['created_by'] ?? $_SESSION['user_id'];
    // transfer ID
    $transfer_ID = 'TRF-UBM-STK' . rand(100000, 999999);

    if (!$stock_id || !$to_location_id || !$trans_quantity || !$company_id) {
        echo "<script>alert('All required fields must be provided.'); window.history.back();</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch current stock
        $stmt = $pdo->prepare("SELECT * FROM stocks WHERE id = ? AND company_id = ?");
        $stmt->execute([$stock_id, $company_id]);
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $company_id = $stock['company_id'];
            $location_id = $stock['location_id'];
            $supplier = $stock['supplier'];
            $stocked_by = $stock['stocked_by'];
  


        if (!$stock) throw new Exception('Stock record not found.');

        if ($trans_quantity > $stock['quantity']) throw new Exception('Transfer quantity cannot exceed current stock quantity.');

        $from_location_id = $stock['location_id'];
        $product_id       = $stock['product_id'];

        // Subtract quantity from current stock and update balance quantity
        $new_qty = $stock['quantity'] - $trans_quantity;
        $stmtUpdate = $pdo->prepare("UPDATE stocks SET quantity = ?, created_at = ? WHERE id = ? AND location_id = ?");
        $stmtUpdate->execute([$new_qty, date('Y-m-d H:i:s'), $stock_id, $from_location_id]);

        // validating if stock exist in designated location 
        $stmtDest = $pdo->prepare("SELECT * FROM stocks WHERE product_id = ? AND location_id = ? AND company_id = ?");
        $stmtDest->execute([$product_id, $to_location_id, $company_id]);
        $destStock = $stmtDest->fetch(PDO::FETCH_ASSOC);

        if ($destStock) {
            $des_Stock_ID = $destStock['id'];
            $stmtAdd = $pdo->prepare("UPDATE stocks SET quantity = quantity + ?, created_at = ? WHERE id = ?");
            $stmtAdd->execute([$trans_quantity, date('Y-m-d H:i:s'), $destStock['id']]);

        } else {
            $stmtInsert = $pdo->prepare("
                INSERT INTO stocks (product_id, company_id, location_id, quantity, supplier, created_at, stocked_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$product_id, $company_id, $to_location_id, $trans_quantity, $supplier, date('Y-m-d H:i:s'), $created_by]);
            $des_Stock_ID = $pdo->lastInsertId();


        }

        // Log stock movement
        $stmtMove = $pdo->prepare("
            INSERT INTO stock_movements (company_id, product_id, stock_id, from_location_id, to_location_id, transfer_ID, quantity, movement_type, reference, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtMove->execute([$company_id, $product_id, $des_Stock_ID, $from_location_id, $to_location_id, $transfer_ID, $trans_quantity, 'Stock Transfer', 'Transfer Destination', $created_by, date('Y-m-d H:i:s')]);

        
        // Log stock movement
        $stmtMove = $pdo->prepare("
            INSERT INTO stock_movements (company_id, product_id, stock_id, from_location_id, to_location_id, transfer_ID, quantity, movement_type, reference, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtMove->execute([$company_id, $product_id, $stock_id, $from_location_id, $from_location_id, $transfer_ID, $new_qty, 'Stock Transfer', 'Inventory Restocked (via Transfer)', $created_by, date('Y-m-d H:i:s')]);

        
        $Outtype = "Transfer Destination";
        // Record stock-in history
        $stmt = $pdo->prepare("
            INSERT INTO stock_in
            (
                product_id,
                stock_id,
                company_id,
                location_id,
                transfer_ID,
                quantity,
                type,
                supplier,
                created_at,
                recorded_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
    
        $stmt->execute([
            $product_id,
            $des_Stock_ID,
            $company_id,
            $location_id,
            $transfer_ID,
            $trans_quantity,
            $Outtype,
            $supplier,
            $stocked_by
        ]);

        $Lefttype = "Inventory Restocked (via Transfer)";
        // Record stock-in history
        $stmt = $pdo->prepare("
            INSERT INTO stock_in
            (
                product_id,
                stock_id,
                company_id,
                location_id,
                transfer_ID,
                quantity,
                type,
                supplier,
                created_at,
                recorded_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
    
        $stmt->execute([
            $product_id,
            $stock_id,
            $company_id,
            $location_id,
            $transfer_ID,
            $new_qty,
            $Lefttype,
            $supplier,
            $stocked_by
        ]);


        $pdo->commit();

        // Fetch names for email
        $stmtProduct = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmtProduct->execute([$product_id]);
        $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

        $stmtFromLocation = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
        $stmtFromLocation->execute([$from_location_id]);
        $fromLocation = $stmtFromLocation->fetch(PDO::FETCH_ASSOC);

        $stmtToLocation = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
        $stmtToLocation->execute([$to_location_id]);
        $toLocation = $stmtToLocation->fetch(PDO::FETCH_ASSOC);

        $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmtUser->execute([$created_by]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $stmtCompany = $pdo->prepare("SELECT company_email, company_name FROM companies WHERE id = ?");
        $stmtCompany->execute([$company_id]);
        $company = $stmtCompany->fetch(PDO::FETCH_ASSOC);

        // Send email
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
                $mail->addAddress($company['company_email'], $company['company_name']);

                $mail->isHTML(true);
                $mail->Subject = 'Stock Transfer Notification';
                $mail->Body = "
                    <p>Dear {$company['company_name']},</p>
                    <p>A stock transfer has been completed successfully.</p>
                    <p>
                    <b>Product:</b> ".htmlspecialchars($product['name'])."<br>
                    <b>Quantity Transferred:</b> ".htmlspecialchars($quantity)."<br>
                    <b>From Location:</b> ".htmlspecialchars($fromLocation['name'])."<br>
                    <b>To Location:</b> ".htmlspecialchars($toLocation['name'])."<br>
                    <b>Transferred By:</b> ".htmlspecialchars($user['username'])."
                    </p>
                    <p>Thank you,<br>Yours Faithfully, {$customer_name}</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>alert('Stock transferred successfully, but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='stocks.php';</script>";
                exit;
            }
        }

        echo "<script>alert('Stock transferred successfully!'); window.location.href='stocks.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: {$e->getMessage()}'); window.history.back();</script>";
    }

} else {
    echo "<script>alert('Invalid request'); window.history.back();</script>";
}
