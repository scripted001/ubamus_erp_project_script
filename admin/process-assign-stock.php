<?php
require '../config/db.php'; // PDO connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

session_start();
date_default_timezone_set('Africa/Lagos');
$date = date("m/d/y");
$time = date("h:s:i");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stock_id       = $_POST['stock_id'] ?? null;
    $assigned_to = $_POST['assigned_to'] ?? null;
    $quantity       = $_POST['quantity'] ?? null;
    $assigned_by     = $_SESSION['user_id'];
    $company_id = $_SESSION['company_id'];
    

    if (!$stock_id || !$assigned_to || !$quantity || !$assigned_by) {
        echo "<script>alert('All required fields must be provided.'); window.history.back();</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch assigned by user_id
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$assigned_by]);
        $ass = $stmt->fetch(PDO::FETCH_ASSOC);
        $assemail = $ass['email'];

        // Fetch assigned by 
        $stmt = $pdo->prepare("SELECT * FROM staffs WHERE email = ?");
        $stmt->execute([$assemail]);
        $assigned = $stmt->fetch(PDO::FETCH_ASSOC);
        $byfullname = $assigned['firstname']; $assigned['lastname'];

        // Fetch current stock
        $stmt = $pdo->prepare("SELECT * FROM stocks WHERE id = ?");
        $stmt->execute([$stock_id]);
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch stock product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$stock['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);



        if (!$stock) throw new Exception('Stock record not found.');

        if ($quantity > $stock['quantity']) throw new Exception('Cannot bite more than one chews when max is '.$stock['quantity'] .' aright?? .');

        // fetching staff email 
        $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
        $stmt->execute([$assigned_to]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        $staffloc = $staff['location_id'];

        
        // fetching assignedby full name from staff database 
        $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
        $stmt->execute([$assigned_by]);
        $staffas = $stmt->fetch(PDO::FETCH_ASSOC);
        $asfullname = $staffas["firstname"]; $staffas["lastname"];
        // echo $asfullname;

        // fetching assignedby full name from staff database 
        $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
        $stmt->execute([$assigned_to]);
        $staffto = $stmt->fetch(PDO::FETCH_ASSOC);
        $tofullname = $staffto['firstname']; $staffto['lastname'];
        // echo $tofullname;

        // Subtract quantity from current stock and update stock quantity
        $new_qty = $stock['quantity'] - $quantity;
        $stmtUpdate = $pdo->prepare("UPDATE stocks SET quantity = ?, last_updated = ? WHERE id = ?");
        $stmtUpdate->execute([$new_qty, date('Y-m-d H:i:s'), $stock_id]);

        // insert into assigned stocks database
        $stmtInsert = $pdo->prepare("
        INSERT INTO stocks_assigned (stock_id, company_id, location_id, assigned_by, assigned_to, quantity, date, time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([$stock_id, $company_id, $staffloc, $byfullname, $assigned_to, $quantity, $date, $time]);
       

        $pdo->commit();
        // Send email
        // Fetch company
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Send email
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'support@ubamus.com';
            $mail->Password   = 'Zaida@25';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;
        
            $mail->setFrom('support@ubamus.com', $company['company_name']);
        
            // Correct recipients
            $mail->addAddress($company['company_email'], $company['company_name']);
            $mail->addAddress($staff['email'], $tofullname);
            $mail->addAddress($assemail, $byfullname);
        
            $mail->isHTML(true);
            $mail->Subject = 'Stock Internal Transfer Between Staff';
        
            $mail->Body = "
                <p>Dear {$company['company_name']},</p>
                <p>The stock below has been assigned successfully.</p>
                <p>
                <b>Product:</b> ".htmlspecialchars($product['name'])."<br>
                <b>Quantity:</b> ".htmlspecialchars($quantity)."<br>
                <b>Assigned by:</b> ".htmlspecialchars($byfullname)."<br>
                <b>Assigned to:</b> ".htmlspecialchars($tofullname)."<br>
                </p>
                <p>Regards,<br>{$company['company_name']}</p>
            ";
        
            $mail->send();
        
        } catch (Exception $e) {
            echo "<script>alert('Transaction successful, but email failed: {$mail->ErrorInfo}'); window.location.href='ass_stocks.php';</script>";
            exit;
        }


       
        echo "<script>alert('Smooooth!! Transaction Completed..'); window.location.href='ass_stocks.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: {$e->getMessage()}'); window.history.back();</script>";
    }

} else {
    echo "<script>alert('Unknown request!'); window.history.back();</script>";
}
