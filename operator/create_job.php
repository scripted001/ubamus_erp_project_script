<?php
include('../config/db.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
require '../vendor/fpdf/fpdf.php';

date_default_timezone_set('Africa/Lagos');

$created_at = date('Y-m-d');
// echo $date;

/* =========================
   INVOICE NUMBER
========================= */
$count = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn() + 1;

// Get abbreviation from the active domain
$activeDomain = $_SERVER['HTTP_HOST'] ?? '';
$activeDomain = preg_replace('/^www\./i', '', $activeDomain);

$domainName = explode('.', $activeDomain)[0];
$domainName = preg_replace('/[^a-zA-Z]/', '', $domainName);

$domainAbbreviation = strtoupper(substr($domainName, 0, 4));

// Generate service invoice number
$invoice_number = $domainAbbreviation . '-SERV-' . date('Ymd') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);



    if (empty($_SESSION['user_id'])) {
        exit("<script>alert('Login required'); window.location.href='../views/login.php';</script>");
    }

    $user_id = $_SESSION['user_id'];

        /* =========================
           INPUT
        ========================= */
        $company_id    = $_SESSION['company_id'] ?? null;
        $location_id   = $_SESSION['location_id'] ?? null;
        $category_id    = $_POST['category_id'] ?? null;
        $job_payload  = $_POST['job_payload'] ?? null;
        $customer_type = $_POST['customer_type'] ?? 'new';
        $payment_status = $_POST['payment_status'] ?? 'full';
        $amount_paid    = (float)($_POST['amount_paid'] ?? 0);
        $due_date       = $_POST['due_date'] ?? null;
        $job_note      = trim($_POST['job_note'] ?? '');


        // echo $job_payload;

/* =========================
   VALIDATE CART
========================= */
// -------------------------------
// CART VALIDATION
// -------------------------------
if (empty($job_payload)) {
    echo "<script>alert('Add at least one product to cart.'); window.history.back();</script>";
    exit();
}

$cartData = json_decode($job_payload, true);

if (!$cartData || empty($cartData['items'])) {
    exit("<script>alert('Cart is empty'); window.history.back();</script>");
}


$items = $cartData['items'];

$grand_total = (float)($cartData['grand_total'] ?? 0);
$discount_amount = (float)($cartData['discount_amount'] ?? 0);
$discount_percent = (float)($cartData['discount_percent'] ?? 0);
$final_total = (float)($cartData['final_total'] ?? $grand_total);

 
// print_r ($items);

// $bal = $final_total - $amount_paid;

// echo $bal;
// echo $amount_paid;

// End of Cart Validation

// -------------------------------
// CUSTOMER HANDLING
// -------------------------------
if ($customer_type === 'new') {
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email_new'] ?? '');

        // Customer variable validation
        if (!$customer_name || !$customer_phone) {
            echo "<script>alert('Enter new customer name and phone.'); window.history.back();</script>";
            exit();
        }
        // -------------------------------
        // CHECK IF CUSTOMER ALREADY EXISTS
        // -------------------------------
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
            // Customer does not exist, insert new
            $stmtCust = $pdo->prepare("
                INSERT INTO customers (company_id, location_id, fullname, email, phone, created_at, created_by, status)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, 1)
            ");
                $stmtCust->execute([$company_id, $location_id, $customer_name, $customer_email, $customer_phone, $user_id]);
                $customer_id = $pdo->lastInsertId();
        }
}else {

    // Fetching customer data from db knowing customer_type != new
    $customer_id = $_POST['existing_customer_id'] ?? '';

    // echo $customer_id;

    // Validating customer_id
    if (!$customer_id) {
        echo "<script>alert('Select an existing customer.'); window.history.back();</script>";
        exit();
    }
    // Fetching existing customer data
    
    $stmtCust = $pdo->prepare("SELECT fullname, email, phone FROM customers WHERE id = ? AND company_id = ? AND location_id = ?");
    $stmtCust->execute([$customer_id, $company_id, $location_id]);
    $customer = $stmtCust->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo "<script>alert('Selected customer not found.'); window.history.back();</script>";
        exit();
    }else {

    $customer_name  = $customer['fullname'];
    $customer_email = $customer['email'];
    $customer_phone = $customer['phone'];
    
    }

    

    
}

// End of Customer Handling


    // Items from cart validation begins
    foreach ($items as $item) {



        $service_id = (int)$item['service_id'];
        $qty = (int)$item['quantity'];
        $unit_price = $item['unit_price'];
        // get product_id from service_items
        $stmt = $pdo->prepare("SELECT product_id FROM service_items WHERE service_id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        // Product_id derived...
        $product_id = $service['product_id'] ?? null;

        // echo $unit_price;

        // Product and quantity validation from cart items
        if (!$product_id || !$qty || !$service_id) {
            throw new Exception("Invalid Job item");
        }
            // Select Unit from product Id
            $prod = $pdo->prepare("
                SELECT unit_id FROM products
                WHERE id=? AND company_id=?
            ");
                $prod->execute([$product_id, $company_id]);
                $unit = $prod->fetchColumn();
            
            /* STOCK CHECK */
            $check = $pdo->prepare("
                SELECT quantity, id FROM stocks
                WHERE product_id=? AND company_id=? AND location_id = ?
            ");
                $check->execute([$product_id, $company_id, $location_id]);
                $stock = $check->fetchColumn();
        
                // stock quantity validation for items in cart
        if ($stock < $qty) {
                    throw new Exception("Insufficient stock for product ID $product_id");
        }

        // Unit Validation
        if (!$unit) {
            throw new Exception("Product unit missing for product ID $product_id");
        }
    }

    // Items from cart validation ends
    


    /* =========================
    PAYMENT VALIDATION FIRST
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
    // Payment validation ends


    /* INSERT RENDERED JOB */
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
    
    /* =========================
    START TRANSACTION
 ========================= */
 try {
     $pdo->beginTransaction();

     
    //  <!-- Updating stock and inserting into sales item after sales insert -->
     
         // INSERT ITEMS + UPDATE STOCK
         $stmtItem = $pdo->prepare("INSERT INTO job_items (job_id, service_id, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");
         $stmtStock = $pdo->prepare("UPDATE stocks SET quantity = quantity - ? WHERE product_id = ? AND company_id = ? AND location_id = ?");
     
         foreach ($items as $item) {
             $stmtItem->execute([
                 $job_id,
                 $item['service_id'],
                 $item['quantity'],
                 $item['unit_price'],
                 $item['total_price']
             ]);
     
             $stmtStock->execute([
                 $item['quantity'],
                 $product_id,
                 $company_id,
                 $location_id
             ]);
         }

        //  End of updating stock and inserting into sales_items

        
        //  inserting data into debt 
        /* DEBT */
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
    // echo $payment_status;
    // End of inserting data into debt

    // Fetching Company data form emailing and invoice begins
    $stmt = $pdo->prepare("
    SELECT * FROM companies
    WHERE id=?
    ");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    // echo $company['id'];

    if($payment_status == "full") {
        $payment = "Full Payment";
    }else {
        $payment = "Part Payment";
    }

    $invoice_file = "../invoices/invoice_{$invoice_number}.pdf";
    
    
    $currency = "₦";
    

    // -------------------------------
    // GENERATE PDF INVOICE (Professional Layout)
    // -------------------------------
    class InvoicePDF extends FPDF {
        public $brand;
        public $logo;
    
        // Header in InvoicePDF
        function Header() {
            global $company;
            global $payment;
            global $date;
            global $currency;
        
            // Build local path
            $logoPath = __DIR__ . '/../uploads/logos/' . $company['company_logo'];
        
            // Use Image only if file exists
            if (!empty($company['company_logo']) && file_exists($logoPath)) {
                $this->Image($logoPath, 10, 10, 35); // X, Y, width
            }
        
    
            $this->SetFont('Arial','B',20);
            $this->Cell(0,10,'INVOICE',0,1,'C');
            $this->Ln(5);
        
            // Watermark
            $this->SetFont('Arial','B',45);
            $this->SetTextColor(220,220,220);
    
            // Optional watermark 
            $this->SetFont('Arial','B',50); 
            $this->SetTextColor(200,200,200); 
            $this->Text(30,190,$payment); 
            $this->SetTextColor(0,0,0);
        }
    
             
function Footer() {
    $this->SetY(-25);

    $this->SetFont('Arial', 'I', 8);

    // Width of the centered footer block
    $footerWidth = 150;

    // Calculate left position to center the block
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
    $pdf->SetTextColor(0,0,0);
    
    // -------------------------------
    // TOP SECTION: Seller Info & Buyer Info
    // -------------------------------
    $startY = 55; 
    $leftWidth = 90;
    
    // LEFT COLUMN: Seller Info
    $pdf->SetXY(12, $startY);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell($leftWidth,8,'Seller Information',0,1);
    $pdf->SetFont('Arial','',11);
    
    $pdf->SetX(12);
    $pdf->MultiCell($leftWidth,6,'Company Name: '.$company['company_name'],0,'L');
    
    $pdf->SetX(12);
    $pdf->MultiCell($leftWidth,6,'Address: '.$company['company_office_address'],0,'L');
    
    $pdf->SetX(12);
    $pdf->MultiCell($leftWidth,6,'Mobile: '.$company['company_mobile'],0,'L');
    
    $pdf->SetX(12);
    $pdf->MultiCell($leftWidth,6,'Email: '.$company['company_email'],0,'L');
    
    // RIGHT COLUMN: Receipt + Buyer Info
    $pdf->SetXY(115, $startY);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(80,8,'Receipt Details',0,1);
    
    $pdf->SetFont('Arial','',11);
    $pdf->SetX(115);
    $pdf->Cell(80,6,'Invoice No: '.$invoice_number,0,1);
    
    $pdf->SetX(115);
    $pdf->Cell(80,6,'Date: '.date('d/m/Y', strtotime($created_at)),0,1);
    
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
    
    // -------------------------------
    // ITEMS TABLE (With Serial / Part Number)
    // -------------------------------
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,210,200);
    
    // Column widths
    $w_sn     = 10;
    $w_desc   = 64;   // Increased from 52
    $w_serial = 58;   // Increased from 50
    $w_qty    = 12;   // Reduced from 18
    $w_unit   = 22;   // Reduced from 25
    $w_total  = 24;   // Reduced from 25
    
    $tableWidth = $w_sn + $w_desc + $w_serial + $w_qty + $w_unit + $w_total;
    
    $pdf->SetX((210 - $tableWidth) / 2);

    $tableWidth = $w_sn + $w_desc + $w_serial + $w_qty + $w_unit + $w_total;

    $pdf->SetX((210 - $tableWidth) / 2);
    
    // Table Header
    $pdf->Cell($w_sn,8,'S/N',1,0,'C',true);
    $pdf->Cell($w_desc,8,'Description',1,0,'L',true);
    $pdf->Cell($w_serial,8,'Service No',1,0,'C',true);
    $pdf->Cell($w_qty,8,'Qty',1,0,'C',true);
    $pdf->Cell($w_unit,8,'Unit Price',1,0,'R',true);
    $pdf->Cell($w_total,8,'Total',1,1,'R',true);
    
    // Table Body
    $pdf->SetFont('Arial','',11);
    $subtotal = 0;
    $sn = 1;
    
    foreach($items as $item) {
        $line_total = $item['total_price'];
        $subtotal += $line_total;
    
        $x = $pdf->GetX();
        $y = $pdf->GetY();
    
        // Serial Number (auto)
        $pdf->Cell($w_sn,8,$sn,1,0,'C');
    
        // Description with wrapping
        $pdf->MultiCell($w_desc,8,$item['name'],1);
    
        // Serial / Part Number
        $pdf->SetXY($x + $w_sn + $w_desc, $y);
        $pdf->Cell($w_serial,8,$item['service_number'] ?? '',1,0,'L');
    
        // Quantity
        $pdf->Cell($w_qty,8,$item['quantity'],1,0,'C');
    
        // Unit Price
        $pdf->Cell($w_unit,8,number_format($item['unit_price'],2),1,0,'R');
    
        // Total
        $pdf->Cell($w_total,8,number_format($line_total,2),1,1,'R');
    
        $sn++;
    }
    
    $pdf->Ln(6);
    
    $pdf->Ln(5);

    $labelWidth = $w_sn + $w_desc + $w_serial + $w_qty + $w_unit;
    
    $pdf->SetFont('Arial','',10);
    
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
    
    $pdf->SetFont('Arial','',11);
    

    if($payment_status == 'partial'){
        $pdf->SetX(150);
        $pdf->Cell($w_total,8,'Due Date: '.$due_date,0,1,);
    }
    
    $pdf->Ln(8);
    $pdf->Ln(8);
    
    // -------------------------------
    // SIGNATURE
    // -------------------------------
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,'Customer Signature',0,1);
    
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(30,8,'Sign:',0,0);
    $pdf->Cell(80,8,'','B',1); // underline for signature
    $pdf->Ln(6);
    
    // -------------------------------
    // NOTES
    // -------------------------------
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0,8,'Notes',0,1);
    
    $pdf->SetFont('Arial','',11);
    if(!empty($sale_note)) {
        $pdf->MultiCell(0,6,$sale_note,0,'L');
    } else {
        $pdf->MultiCell(0,6,'Thank you for your purchase! Goods once sold are not returnable.');
    }
    
    // -------------------------------
    // OUTPUT
    // -------------------------------
    $pdf->Output('F', $invoice_file);

    // End of Generating invoice

    // Email company email + Customer Email
    
    $pdo->commit();
    try {

    // -------------------------------
    // EMAIL INVOICE
    // -------------------------------
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@ubamus.com';
    $mail->Password   = 'Zaida@25';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('support@ubamus.com', $company['company_name']);
    $mail->addAddress($customer_email);       // Customer
    $mail->addAddress($company['company_email']); // Company admin
    $mail->Subject = "Invoice $invoice_number";
    $mail->Body = "Dear {$customer_name},

    Thank you for your patronage.

    Please find attached your invoice (Invoice No: {$invoice_number}) for your recent purchase with {$company['company_name']}.

    We greatly appreciate your support and look forward to serving you again.

    Kind regards,
    {$company['company_name']} Team";
        $mail->addAttachment($invoice_file);
        $mail->send();

        } catch (Exception $e) {
                echo "<script>alert('Job rendering completed, but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); 
                window.open('{$invoice_file}', '_blank');
                window.location.href='job-entries.php';</script>";
                 exit();
        }
        echo "<script>alert('Job rendering, completed and email sent.'); 
                window.open('{$invoice_file}', '_blank');
                window.location.href='job-entries.php';</script>";
                 exit();

 } catch (Exception $e) {
     if ($pdo->inTransaction()) $pdo->rollBack();
     $msg = addslashes($e->getMessage());

    //  echo "<script>alert('Error processing sale: {$msg}'); window.history.back();</script>";
        echo $msg;
     exit();
 }
 
          

?>