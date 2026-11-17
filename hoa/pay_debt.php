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

$date = date("Y:m:d H:s:i");

$userID = $_SESSION['user_id'];

// Fetching user data
$stmt = $pdo->prepare(" SELECT * FROM users WHERE id=?");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user['username'];

// echo $user_name;

        $company_id = $_SESSION['company_id'];
        /* =========================
           INPUT
        ========================= */
        $debt_id    = $_POST['debt_id'] ?? null;
        $sale_note = "Our deepest gratitude to you our esteemed customer for your patriotic patronage, we hope to maintain this beautiful business relationship that we have...";

        // echo $debt_id;

        // Fetching debt data form customr's debt table
        $stmt = $pdo->prepare(" SELECT * FROM customer_debts WHERE id=? ");
        $stmt->execute([$debt_id]);
        $debt = $stmt->fetch(PDO::FETCH_ASSOC);
            $transaction_id = $debt['transaction_id'];
            $debt_type = $debt['debt_type'];

      

        if($debt_type == "Sales") {
            // sales transaction begins

            $count = $pdo->query("SELECT COUNT(*) FROM paid_dept")->fetchColumn() + 1;

            $invoice_number = 'UBM-SALES-' . date('Ymd') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            // Fetching sales data form sales table
            $stmt = $pdo->prepare("SELECT * FROM sales WHERE id=?");
            $stmt->execute([$transaction_id]);
            $salesDetails = $stmt->fetch(PDO::FETCH_ASSOC);
                $sales_id = $salesDetails['id'];
            
                //  sales data
               
                $customer_id = $salesDetails['customer_id'];
                $company_id = $salesDetails['company_id'];
                $location_id = $salesDetails['location_id'];
                $previous_invoice_number = $salesDetails['invoice_number'];
                $user_id = $salesDetails['user_id'];
                $customer_name = $salesDetails['customer_name'];
                $customer_phone = $salesDetails['customer_phone'];
                $customer_email = $salesDetails['customer_email'];
                $grand_total = $salesDetails['grand_total'];
                $discount_percent = $salesDetails['discount_percent'];
                $discount_amount = $salesDetails['discount_amount'];
                $final_total = $salesDetails['final_total'];
               
                $previous_payment_status = $salesDetails['payment_status'];
                $previous_amount_paid = $salesDetails['amount_paid'];
                $previous_balance = $salesDetails['balance'];
                $due_date = $salesDetails['due_date'];

                $payment_status = "full";
                $balance = 0;
                $amount_paid = $previous_balance + $previous_amount_paid;

                // Updating data for paid_debt_table 
                $pdtransaction_id = $transaction_id;
                $pdbalance = $balance;
                $pdstatus = "Paid";
                $pdprevious_invoice_number = $previous_invoice_number;
                $pdpaid_invoice_number = $invoice_number;
                $pdcreated_at = $date;
                $pdcreated_by = $user_name;

                // Fetching existing customer data
                $stmtCust = $pdo->prepare("SELECT fullname, email, phone FROM customers WHERE id = ?");
                $stmtCust->execute([$customer_id]);
                $customer = $stmtCust->fetch(PDO::FETCH_ASSOC);
        
                if (!$customer) {
                    echo "<script>alert('Selected customer not found.'); window.history.back();</script>";
                    exit();
                }else {
                    $customer_name  = $customer['fullname'];
                    $customer_email = $customer['email'];
                    $customer_phone = $customer['phone'];
                }
            
                $stmt = $pdo->prepare("
                    SELECT
                        si.id,
                        si.product_id,
                        si.quantity,
                        si.unit_price,
                        si.total_price,
                        p.name AS name
                    FROM sale_items si
                    LEFT JOIN products p
                        ON si.product_id = p.id
                    WHERE si.sale_id = ?
                    ORDER BY si.id DESC
                ");

                $stmt->execute([$transaction_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            
                $pdo->prepare("
                    INSERT INTO paid_dept
                    (company_id, location_id, pdtransaction_id, pdbalance, pdprevious_invoice_number, pdpaid_invoice_number, pdcreated_at, pdstatus, pdcreated_by)
                    VALUES (?,?,?,?,?,?,?,?,?)
                ")->execute([
                    $company_id,
                    $location_id,
                    $pdtransaction_id,
                    $pdbalance,
                    $pdprevious_invoice_number,
                    $pdpaid_invoice_number,
                    $pdcreated_at,
                    $pdstatus,
                    $pdcreated_by
                ]);


                //  Deleting customer debt
                $update = $pdo->prepare("DELETE FROM customer_debts WHERE id = ?");
                $update->execute([$debt_id]);
                // // echo $debt_type;


                /* =========================
    START TRANSACTION
 ========================= */
 try {
     $pdo->beginTransaction();

     $update = $pdo->prepare("DELETE FROM customer_debts WHERE id = ?");
     $update->execute([$debt_id]);
     
     echo "Rows deleted: " . $update->rowCount();
    

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
    
     $stmt = $pdo->prepare("
            UPDATE sales SET
                balance = ?, amount_paid = ?, payment_status = ?
            WHERE id = ?
        ");
        $stmt->execute([$balance, $amount_paid, $payment_status, $sales_id]);


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
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Your patronage is greatly appreciated.',0,0,'C');
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
    $pdf->Cell(80,6,'Date: '.$date,0,1);
    
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
    $w_sn = 10;
    $w_desc = 60;
    $w_serial = 50;
    $w_qty = 20;
    $w_unit = 25;
    $w_total = 25;
    
    // Table Header
    $pdf->Cell($w_sn,8,'S/N',1,0,'C',true);
    $pdf->Cell($w_desc,8,'Description',1,0,'L',true);
    $pdf->Cell($w_serial,8,'Serial / Part No',1,0,'C',true);
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
        $pdf->Cell($w_serial,8,$item['serial_number'] ?? '',1,0,'C');
    
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
                echo "<script>alert('Debt paid, but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); 
                window.open('{$invoice_file}', '_blank');
                window.location.href='paid_debt.php';</script>";
                 exit();
        }
        echo "<script>
        alert('Dept paid successfully, invoice generated and sent to customer');
        window.location.href='paid_debt.php';
    </script>";
    exit;



 } catch (Exception $e) {
     if ($pdo->inTransaction()) $pdo->rollBack();
     $msg = addslashes($e->getMessage());
     echo "<script>alert('Error processing sale: {$msg}'); window.history.back();</script>";
        echo $msg;
     exit();
 }



                // end of sales transaction

        }else{
            
            // jobs transaction begins

            $count = $pdo->query("SELECT COUNT(*) FROM paid_dept")->fetchColumn() + 1;

            $invoice_number = 'UBM-SERV-' . date('Ymd') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            // Fetching sales data form sales table
            $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id=?");
            $stmt->execute([$transaction_id]);
            $jobDetails = $stmt->fetch(PDO::FETCH_ASSOC);
                $job_id = $jobDetails['id'];
            
                //  sales data
               
                $customer_id = $jobDetails['customer_id'];
                $company_id = $jobDetails['company_id'];
                $location_id = $jobDetails['location_id'];
                $previous_invoice_number = $jobDetails['invoice_number'];
                $user_id = $jobDetails['user_id'];
                $customer_name = $jobDetails['customer_name'];
                $customer_phone = $jobDetails['customer_phone'];
                $customer_email = $jobDetails['customer_email'];
                $grand_total = $jobDetails['grand_total'];
                $discount_percent = $jobDetails['discount_percent'];
                $discount_amount = $jobDetails['discount_amount'];
                $final_total = $jobDetails['final_total'];
               
                $previous_payment_status = $jobDetails['payment_status'];
                $previous_amount_paid = $jobDetails['amount_paid'];
                $previous_balance = $jobDetails['balance'];
                $due_date = $jobDetails['due_date'];

                $payment_status = "full";
                $balance = 0;
                $amount_paid = $previous_balance + $previous_amount_paid;

                // Updating data for paid_debt_table 
                $pdtransaction_id = $transaction_id;
                $pdbalance = $balance;
                $pdstatus = "Paid";
                $pdprevious_invoice_number = $previous_invoice_number;
                $pdpaid_invoice_number = $invoice_number;
                $pdcreated_at = $date;
                $pdcreated_by = $user_name;

                // Fetching existing customer data
                $stmtCust = $pdo->prepare("SELECT fullname, email, phone FROM customers WHERE id = ?");
                $stmtCust->execute([$customer_id]);
                $customer = $stmtCust->fetch(PDO::FETCH_ASSOC);
        
                if (!$customer) {
                    echo "<script>alert('Selected customer not found.'); window.history.back();</script>";
                    exit();
                }else {
                    $customer_name  = $customer['fullname'];
                    $customer_email = $customer['email'];
                    $customer_phone = $customer['phone'];
                }
            
                $stmt = $pdo->prepare("
                    SELECT
                        ji.id,
                        ji.service_id,
                        ji.quantity,
                        ji.unit_cost,
                        ji.total_cost,
                        s.service_name AS name,
                        s.service_number AS service_number
                    FROM job_items ji
                    LEFT JOIN services s
                        ON ji.service_id = s.id
                    WHERE ji.job_id = ?
                    ORDER BY ji.id DESC
                ");

                $stmt->execute([$transaction_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            
                $pdo->prepare("
                    INSERT INTO paid_dept
                    (company_id, location_id, pdtransaction_id, pdbalance, pdprevious_invoice_number, pdpaid_invoice_number, pdcreated_at, pdstatus, pdcreated_by)
                    VALUES (?,?,?,?,?,?,?,?,?)
                ")->execute([
                    $company_id,
                    $location_id,
                    $pdtransaction_id,
                    $pdbalance,
                    $pdprevious_invoice_number,
                    $pdpaid_invoice_number,
                    $pdcreated_at,
                    $pdstatus,
                    $pdcreated_by
                ]);

                /* =========================
    START TRANSACTION
 ========================= */
 try {
     $pdo->beginTransaction();

     $update = $pdo->prepare("DELETE FROM customer_debts WHERE id = ?");
    $update->execute([$debt_id]);

    echo "Rows deleted: " . $update->rowCount();
    


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
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Your patronage is greatly appreciated.',0,0,'C');
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
    $pdf->Cell(80,6,'Date: '.date('d/m/Y', strtotime($date)),0,1);
    
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

    $w_sn = 10;
    $w_desc = 62;
    $w_serial = 60;
    $w_qty = 15;
    $w_unit = 22;
    $w_total = 25;

    $tableWidth = $w_sn + $w_desc + $w_serial + $w_qty + $w_unit + $w_total;

    $pdf->SetX((214 - $tableWidth) / 2);
    
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
        $line_total = $item['total_cost'];
        $subtotal += $line_total;
    
        $x = $pdf->GetX();
        $y = $pdf->GetY();
    
        // Serial Number (auto)
        $pdf->Cell($w_sn,8,$sn,1,0,'C');
    
        // Description with wrapping
        $pdf->MultiCell($w_desc,8,$item['name'],1);
    
        // Serial / Part Number
        $pdf->SetXY($x + $w_sn + $w_desc, $y);
        $pdf->Cell($w_serial,8,$item['service_number'] ?? '',1,0,'C');
    
        // Quantity
        $pdf->Cell($w_qty,8,$item['quantity'],1,0,'C');
    
        // Unit Price
        $pdf->Cell($w_unit,8,number_format($item['unit_cost'],2),1,0,'R');
    
        // Total
        $pdf->Cell($w_total,8,number_format($line_total,2),1,1,'R');
    
        $sn++;
    }
    
    $pdf->Ln(6);
    
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
                window.location.href='paid_debt.php';</script>";
                 exit();
        }
        echo "<script>alert('Job rendering, completed and email sent.'); 
                window.open('{$invoice_file}', '_blank');
                window.location.href='paid_debt.php';</script>";
                 exit();

 } catch (Exception $e) {
     if ($pdo->inTransaction()) $pdo->rollBack();
     $msg = addslashes($e->getMessage());

     echo "<script>alert('Error processing sale: {$msg}'); window.history.back();</script>";
        echo $msg;
     exit();
 }
 


        }



    
 
          

?>