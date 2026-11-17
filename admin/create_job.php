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

date_default_timezone_set('Africa/Lagos');

/* ==========================================================================
   SMS API CONFIGURATION (Termii Integration)
   Replace with your Termii API Key and Sender ID
   ========================================================================== */
define('TERMII_API_KEY', 'YOUR_TERMII_API_KEY_HERE'); 
define('TERMII_SENDER_ID', 'N-ALERT'); // Use 'N-ALERT' or your registered Sender ID

/**
 * Sends SMS via Termii REST API
 */
function sendSMS($to_phone, $message) {
    // Sanitize phone number (Convert local 080... format to international 23480...)
    $formatted_phone = preg_replace('/[^0-9]/', '', $to_phone);
    if (substr($formatted_phone, 0, 1) === '0') {
        $formatted_phone = '234' . substr($formatted_phone, 1);
    }

    $payload = [
        "api_key" => TERMII_API_KEY,
        "to"      => $formatted_phone,
        "from"    => TERMII_SENDER_ID,
        "sms"     => $message,
        "type"    => "plain",
        "channel" => "generic"
    ];

    $ch = curl_init('https://api.ng.termii.com/api/sms/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

$created_at = date("Y-m-d H:i:s");
$date = date("Y-m-d H:i:s");

/* =========================
   INVOICE NUMBER
========================= */
$count = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn() + 1;

$domain = preg_replace('/^www\./', '', trim($active_domain ?? ''));
$domain = preg_replace('/\.[^.]+$/', '', $domain);
$domain_abbreviation = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $domain), 0, 4));

if (empty($domain_abbreviation)) {
    $domain_abbreviation = 'JOB';
}

$invoice_number = $domain_abbreviation . '-SERV-' . date('Ymd') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

if (empty($_SESSION['user_id'])) {
    exit("<script>alert('Login required'); window.location.href='../views/login.php';</script>");
}

$user_id = $_SESSION['user_id'];

/* =========================
   INPUT
========================= */
$company_id     = $_SESSION['company_id'] ?? null;
$location_id    = $_SESSION['location_id'] ?? null;
$category_id    = $_POST['category_id'] ?? null;
$job_payload    = $_POST['job_payload'] ?? null;
$customer_type  = $_POST['customer_type'] ?? 'new';
$payment_status = $_POST['payment_status'] ?? 'full';
$amount_paid    = (float)($_POST['amount_paid'] ?? 0);
$due_date       = $_POST['due_date'] ?? null;
$job_note       = trim($_POST['job_note'] ?? '');

/* =========================
   VALIDATE CART
========================= */
if (empty($job_payload)) {
    echo "<script>alert('Add at least one product to cart.'); window.history.back();</script>";
    exit();
}

$cartData = json_decode($job_payload, true);

if (!$cartData || empty($cartData['items'])) {
    exit("<script>alert('Cart is empty'); window.history.back();</script>");
}

$items = $cartData['items'];
$grand_total     = (float)($cartData['grand_total'] ?? 0);
$discount_amount  = (float)($cartData['discount_amount'] ?? 0);
$discount_percent = (float)($cartData['discount_percent'] ?? 0);
$final_total      = (float)($cartData['final_total'] ?? $grand_total);

/* =========================
   CUSTOMER HANDLING
========================= */
if ($customer_type === 'new') {
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email_new'] ?? '');

    if (!$customer_name || !$customer_phone) {
        echo "<script>alert('Enter new customer name and phone.'); window.history.back();</script>";
        exit();
    }

    $stmtCheck = $pdo->prepare("
        SELECT id FROM customers 
        WHERE company_id = ? AND email = ? AND status = 1
        LIMIT 1
    ");
    $stmtCheck->execute([$company_id, $customer_email]);
    $existingCustomer = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingCustomer) {
        echo "<script>alert('Customer already exist!'); window.history.back();</script>";
        exit();
    } else {
        $stmtCust = $pdo->prepare("
            INSERT INTO customers (company_id, location_id, fullname, email, phone, created_at, created_by, status)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, 1)
        ");
        $stmtCust->execute([$company_id, $location_id, $customer_name, $customer_email, $customer_phone, $user_id]);
        $customer_id = $pdo->lastInsertId();
    }
} else {
    $customer_id = $_POST['existing_customer_id'] ?? '';

    if (!$customer_id) {
        echo "<script>alert('Select an existing customer.'); window.history.back();</script>";
        exit();
    }

    $stmtCust = $pdo->prepare("SELECT fullname, email, phone FROM customers WHERE id = ? AND company_id = ? AND location_id = ?");
    $stmtCust->execute([$customer_id, $company_id, $location_id]);
    $customer = $stmtCust->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo "<script>alert('Selected customer not found.'); window.history.back();</script>";
        exit();
    } else {
        $customer_name  = $customer['fullname'];
        $customer_email = $customer['email'];
        $customer_phone = $customer['phone'];
    }
}

/* =========================
   CART & STOCK VALIDATION
========================= */
foreach ($items as $item) {
    $service_id = (int)$item['service_id'];
    $qty        = (int)$item['quantity'];
    $unit_price = $item['unit_price'];

    $stmt = $pdo->prepare("SELECT product_id FROM service_items WHERE service_id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    $product_id = $service['product_id'] ?? null;

    if (!$product_id || !$qty || !$service_id) {
        exit("<script>alert('Invalid Job item detected'); window.history.back();</script>");
    }

    $prod = $pdo->prepare("
        SELECT unit_id FROM products
        WHERE id=? AND company_id=?
    ");
    $prod->execute([$product_id, $company_id]);
    $unit = $prod->fetchColumn();

    $check = $pdo->prepare("
        SELECT quantity FROM stocks
        WHERE product_id=? AND company_id=? AND location_id = ?
    ");
    $check->execute([$product_id, $company_id, $location_id]);
    $stock = $check->fetchColumn();

    if ($stock < $qty) {
        exit("<script>alert('Insufficient stock for product ID $product_id'); window.history.back();</script>");
    }

    if (!$unit) {
        exit("<script>alert('Product unit missing for product ID $product_id'); window.history.back();</script>");
    }
}

/* =========================
   PAYMENT VALIDATION
========================= */
if ($payment_status === 'full') {
    $amount_paid = $final_total;
    $balance = 0;
} else {
    if ($amount_paid <= 0) {
        exit("<script>alert('Enter valid amount'); window.history.back();</script>");
    }
    if ($amount_paid >= $final_total) {
        exit("<script>alert('Partial payment must be less than total'); window.history.back();</script>");
    }
    $balance = $final_total - $amount_paid;
}

/* =========================
   DATABASE TRANSACTION
========================= */
try {
    $pdo->beginTransaction();

    // Insert Job
    $stmt = $pdo->prepare("
        INSERT INTO jobs (
            company_id, location_id, customer_id, user_id, invoice_number, customer_name, customer_phone, customer_email, grand_total, discount_percent, discount_amount, final_total, notes, payment_status, amount_paid, balance, due_date, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $company_id,
        $location_id,
        $customer_id,
        $user_id,
        $invoice_number,
        $customer_name,
        $customer_phone,
        $customer_email,
        $grand_total,
        $discount_percent,
        $discount_amount,
        $final_total,
        $job_note,
        $payment_status,
        $amount_paid,
        $balance,
        $due_date,
        $created_at
    ]);

    $job_id = $pdo->lastInsertId();

    // Insert Items and Update Stocks
    $stmtItem  = $pdo->prepare("INSERT INTO job_items (job_id, service_id, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE stocks SET quantity = quantity - ? WHERE product_id = ? AND company_id = ? AND location_id = ?");

    foreach ($items as $item) {
        $stmtItem->execute([
            $job_id,
            $item['service_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['total_price']
        ]);

        // Get product ID for stock reduction
        $stmtP = $pdo->prepare("SELECT product_id FROM service_items WHERE service_id = ?");
        $stmtP->execute([$item['service_id']]);
        $p_id = $stmtP->fetchColumn();

        $stmtStock->execute([
            $item['quantity'],
            $p_id,
            $company_id,
            $location_id
        ]);
    }

    // Insert Debts if partial
    if ($payment_status === 'partial' && $balance > 0) {
        $pdo->prepare("
            INSERT INTO customer_debts
            (transaction_id, invoice_number, debt_by, customer_id, company_id, location_id, debt_type, total_amount, amount_paid, balance, due_date, status, created_at)
            VALUES (?,?,?,?,?,?,'Jobs',?,?,?,?,'Unpaid',NOW())
        ")->execute([
            $job_id,
            $invoice_number,
            $user_id,
            $customer_id,
            $company_id,
            $location_id,
            $final_total,
            $amount_paid,
            $balance,
            $due_date
        ]);
    }

    // Commit Transaction
    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = addslashes($e->getMessage());
    exit("<script>alert('Error processing transaction: {$msg}'); window.history.back();</script>");
}

/* =========================
   COMPANY DATA
========================= */
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id=?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

$payment = ($payment_status == "full") ? "Full Payment" : "Part Payment";
$invoice_file = "../invoices/invoice_{$invoice_number}.pdf";

/* =========================
   GENERATE PDF INVOICE
========================= */
class InvoicePDF extends FPDF {
    function Header() {
        global $company, $payment;
        $logoPath = __DIR__ . '/../uploads/logos/' . ($company['company_logo'] ?? '');

        if (!empty($company['company_logo']) && file_exists($logoPath)) {
            $this->Image($logoPath, 10, 10, 35);
        }

        $this->SetFont('Arial','B',20);
        $this->Cell(0,10,'INVOICE',0,1,'C');
        $this->Ln(5);

        $this->SetFont('Arial','B',50); 
        $this->SetTextColor(200,200,200); 
        $this->Text(30,190,$payment); 
        $this->SetTextColor(0,0,0);
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        $footerWidth = 150;
        $pageWidth = $this->GetPageWidth();
        $leftMargin = ($pageWidth - $footerWidth) / 2;
        $this->SetX($leftMargin);
        $this->MultiCell(
            $footerWidth,
            4,
            "Your patronage is greatly appreciated.\n\n" .
            "IMPORTANT: The cashier is required to affix the official company stamp " .
            "to this invoice before presenting it to the customer. An invoice is " .
            "considered duly validated only when it bears the authorized company stamp.",
            0,
            'C'
        );
    }
}

$pdf = new InvoicePDF('P','mm','A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',11);

$startY = 55; 
$leftWidth = 90;

// Seller Info
$pdf->SetXY(12, $startY);
$pdf->SetFont('Arial','B',14);
$pdf->Cell($leftWidth,8,'Seller Information',0,1);
$pdf->SetFont('Arial','',11);
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Company Name: '.($company['company_name'] ?? ''),0,'L');
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Address: '.($company['company_office_address'] ?? ''),0,'L');
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Mobile: '.($company['company_mobile'] ?? ''),0,'L');
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Email: '.($company['company_email'] ?? ''),0,'L');

// Buyer Info
$pdf->SetXY(115, $startY);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(80,8,'Receipt Details',0,1);
$pdf->SetFont('Arial','',11);
$pdf->SetX(115);
$pdf->Cell(80,6,'Invoice No: '.$invoice_number,0,1);
$pdf->SetX(115);
$pdf->Cell(80,6,'Date: '.$created_at,0,1);
$pdf->Ln(4);
$pdf->SetX(115);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(80,8,'Buyer Information',0,1);
$pdf->SetFont('Arial','',11);
$pdf->SetX(115);
$pdf->Cell(80,6,'Name: '.$customer_name,0,1);
$pdf->SetX(115);
$pdf->Cell(80,6,'Email: '.$customer_email,0,1);
$pdf->SetX(115);
$pdf->Cell(80,6,'Phone: '.$customer_phone,0,1);

$pdf->Ln(15);

// Items Table
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,210,200);

$w_sn = 10; $w_desc = 62; $w_serial = 60; $w_qty = 15; $w_unit = 22; $w_total = 25;
$tableWidth = $w_sn + $w_desc + $w_serial + $w_qty + $w_unit + $w_total;
$pdf->SetX((214 - $tableWidth) / 2);

$pdf->Cell($w_sn,8,'S/N',1,0,'C',true);
$pdf->Cell($w_desc,8,'Description',1,0,'L',true);
$pdf->Cell($w_serial,8,'Service No',1,0,'C',true);
$pdf->Cell($w_qty,8,'Qty',1,0,'C',true);
$pdf->Cell($w_unit,8,'Unit Price',1,0,'R',true);
$pdf->Cell($w_total,8,'Total',1,1,'R',true);

$pdf->SetFont('Arial','',11);
$sn = 1;

foreach($items as $item) {
    $line_total = $item['total_price'];
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $pdf->Cell($w_sn,8,$sn,1,0,'C');
    $pdf->MultiCell($w_desc,8,$item['name'],1);
    $pdf->SetXY($x + $w_sn + $w_desc, $y);
    $pdf->Cell($w_serial,8,$item['service_number'] ?? '',1,0,'C');
    $pdf->Cell($w_qty,8,$item['quantity'],1,0,'C');
    $pdf->Cell($w_unit,8,number_format($item['unit_price'],2),1,0,'R');
    $pdf->Cell($w_total,8,number_format($line_total,2),1,1,'R');
    $sn++;
}

$pdf->Ln(5);
$labelWidth = $w_sn + $w_desc + $w_serial + $w_qty + $w_unit;

$pdf->SetFont('Arial','',11);
$pdf->Cell($labelWidth,8,'Subtotal:',0,0,'R');
$pdf->Cell($w_total,8,number_format($grand_total,2),0,1,'R');
$pdf->Cell($labelWidth,8,'Discount ('.number_format($discount_percent,2).' %):',0,0,'R');
$pdf->Cell($w_total,8,'-'.number_format($discount_amount,2),0,1,'R');

$pdf->SetFont('Arial','B',11);
$pdf->Cell($labelWidth,8,'Net Total:',0,0,'R');
$pdf->Cell($w_total,8,number_format($final_total,2),0,1,'R');

$pdf->SetFont('Arial','',11);
$pdf->Cell($labelWidth,8,'Amount Paid:',0,0,'R');
$pdf->Cell($w_total,8,number_format($amount_paid,2),0,1,'R');
$pdf->Cell($labelWidth,8,'Balance Due:',0,0,'R');
$pdf->Cell($w_total,8,number_format($balance,2),0,1,'R');

if($payment_status == 'partial'){
    $pdf->SetX(150);
    $pdf->Cell($w_total,8,'Due Date: '.$due_date,0,1);
}

$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Customer Signature',0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(30,8,'Sign:',0,0);
$pdf->Cell(80,8,'','B',1);
$pdf->Ln(6);

$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,8,'Notes',0,1);
$pdf->SetFont('Arial','',11);
if(!empty($job_note)) {
    $pdf->MultiCell(0,6,$job_note,0,'L');
} else {
    $pdf->MultiCell(0,6,'Thank you for your purchase! Goods once sold are not returnable.');
}

$pdf->Output('F', $invoice_file);

/* ==========================================================================
   DISPATCH NOTIFICATIONS (SMS & EMAIL)
   ========================================================================== */

// 1. Send SMS Notification
$sms_company_name = $company['company_name'] ?? 'Our Store';
$sms_text = "Hello {$customer_name}, thanks for your payment. Invoice #{$invoice_number}. Total: NGN " . number_format($final_total, 2) . ", Paid: NGN " . number_format($amount_paid, 2) . ", Bal: NGN " . number_format($balance, 2) . ". - {$sms_company_name}";

if (!empty($customer_phone)) {
    sendSMS($customer_phone, $sms_text);
}

// 2. Send Email Notification
try {
    $smtp_username = $_SESSION['customercare'] ?? '';
    $smtp_password = $_SESSION['customer_care_password'] ?? '';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_username;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom($smtp_username, $company['company_name'] ?? 'Company');
    if (!empty($customer_email)) {
        $mail->addAddress($customer_email);
    }
    if (!empty($company['company_email'])) {
        $mail->addAddress($company['company_email']);
    }

    $mail->Subject = "Invoice $invoice_number";
    $mail->Body    = "Dear {$customer_name},\n\nThank you for your patronage.\n\nPlease find attached your invoice (Invoice No: {$invoice_number}) for your recent purchase with {$company['company_name']}.\n\nKind regards,\n{$company['company_name']} Team";
    $mail->addAttachment($invoice_file);
    $mail->send();

} catch (Exception $e) {
    echo "<script>alert('Job completed and SMS sent, but email could not be delivered. Mailer Error: {$mail->ErrorInfo}'); 
            window.open('{$invoice_file}', '_blank');
            window.location.href='job-entries.php';</script>";
    exit();
}

echo "<script>alert('Job rendering completed, Invoice SMS and Email sent successfully.'); 
        window.open('{$invoice_file}', '_blank');
        window.location.href='job-entries.php';</script>";
exit();
?>