<?php
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    /* ===============================
    INCLUDES
    ================================ */
    include('../config/db.php');
    include('../config/validation.php');
    include('user_data.php');
    include('../app_data.php');
    include('../company_data.php');
    
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';
    require '../PHPMailer/src/Exception.php';
    require '../vendor/fpdf/fpdf.php';
    
    date_default_timezone_set('Africa/Lagos');
    
    /* ===============================
    VALIDATION
    ================================ */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        exit('Invalid request');
    }
    
    if (!isset($_SESSION['company_id'], $_SESSION['user_id'])) {
        exit('Unauthorized');
    }
    
    /* ===============================
    FORM DATA
    ================================ */
    $company_id   = $_SESSION['company_id'];
    $created_by  = $_SESSION['user_email'];
    
    $customer_id    = $_POST['customer_id'];
    $staff_id       = $_POST['staff_id'];
    $location_id    = $_POST['location_id'];
    $job_name       = trim($_POST['job_name']);
    $payment_method = $_POST['payment_method'];
    $invoice_note   = trim($_POST['invoice_note'] ?? '');
    $job_date       = date('Y-m-d');
    
    $catalogue_ids = $_POST['job_catalogue_id'] ?? [];
    $category      = $_POST['category'] ?? [];
    $quantities    = $_POST['quantity'] ?? [];
    $unit_prices   = $_POST['unit_price'] ?? [];
    
    /* ===============================
    CALCULATE TOTAL
    ================================ */
    $grand_total = 0;
    for ($i = 0; $i < count($catalogue_ids); $i++) {
        $grand_total += floatval($quantities[$i]) * floatval($unit_prices[$i]);
    }
    
    $status = 'completed';
    $invoice_number = 'UBM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    /* ===============================
    BEGIN TRANSACTION
    ================================ */
    try {
    
        $pdo->beginTransaction();
    
        /* ===============================
        INSERT JOB
        ================================ */
        $stmt = $pdo->prepare("
            INSERT INTO jobs (
                customer_id, company_id, staff_id, location_id,
                job_date, job_name, payment_method, status,
                grand_total, invoice_number, invoice_note,
                created_by, created_at, updated_at
            ) VALUES (
                :customer_id, :company_id, :staff_id, :location_id,
                :job_date, :job_name, :payment_method, :status,
                :grand_total, :invoice_number, :invoice_note,
                :created_by, NOW(), NOW()
            )
        ");
    
        $stmt->execute([
            ':customer_id'    => $customer_id,
            ':company_id'     => $company_id,
            ':staff_id'       => $staff_id,
            ':location_id'    => $location_id,
            ':job_date'       => $job_date,
            ':job_name'       => $job_name,
            ':payment_method' => $payment_method,
            ':status'         => $status,
            ':grand_total'    => $grand_total,
            ':invoice_number' => $invoice_number,
            ':invoice_note'   => $invoice_note,
            ':created_by'     => $created_by
        ]);
    
        $job_id = $pdo->lastInsertId();
    
        /* ===============================
        PREPARE STATEMENTS
        ================================ */
        $itemStmt = $pdo->prepare("
            INSERT INTO job_items (
                job_id, job_catalogue_id, category,
                quantity, unit_price, subtotal
            ) VALUES (
                :job_id, :catalogue_id, :category,
                :quantity, :unit_price, :subtotal
            )
        ");
    
        $deductMaterialStmt = $pdo->prepare("
            UPDATE job_catalogue
            SET `material-quantity` = `material-quantity` - :used_qty
            WHERE id = :catalogue_id
             AND `material-quantity` >= :check_qty
        ");
    
    
        $items = [];
    
        /* ===============================
        INSERT JOB ITEMS + DEDUCT MATERIALS
        ================================ */
        for ($i = 0; $i < count($catalogue_ids); $i++) {
    
            $qty      = floatval($quantities[$i]);
            $price    = floatval($unit_prices[$i]);
            $subtotal = $qty * $price;
            $catalogue_id = $catalogue_ids[$i];
    
            $itemStmt->execute([
                ':job_id'       => $job_id,
                ':catalogue_id' => $catalogue_id,
                ':category'     => $category[$i],
                ':quantity'     => $qty,
                ':unit_price'   => $price,
                ':subtotal'     => $subtotal
            ]);
            $deductMaterialStmt->execute([
                ':used_qty'     => $qty,
                ':check_qty'    => $qty,
                ':catalogue_id' => $catalogue_id
            ]);
            
    
            if ($deductMaterialStmt->rowCount() === 0) {
                throw new Exception("Insufficient material stock for item ID {$catalogue_id}");
            }
    
            $items[] = [
                'category'     => $category[$i],
                'quantity'     => $qty,
                'unit_price'   => $price,
                'total_price'  => $subtotal
            ];
        }
    
        /* ===============================
        COMMIT TRANSACTION
        ================================ */
        $pdo->commit();
    
    
    /* ===============================
    FETCH CUSTOMER & COMPANY
    ================================ */
    $customer = $pdo->prepare("SELECT * FROM customers WHERE id=?");
    $customer->execute([$customer_id]);
    $customer = $customer->fetch();

    $company = $pdo->prepare("SELECT * FROM companies WHERE id=?");
    $company->execute([$company_id]);
    $company = $company->fetch();

    /* ===============================
    BRAND COLOR FROM LOGO
    ================================ */
    function dominantColor($path){
        if (!file_exists($path)) return [245,190,66];
        $img = imagecreatefromstring(file_get_contents($path));
        $r=$g=$b=$c=0;
        for($x=0;$x<imagesx($img);$x+=10){
            for($y=0;$y<imagesy($img);$y+=10){
                $rgb=imagecolorat($img,$x,$y);
                $r+=($rgb>>16)&255;
                $g+=($rgb>>8)&255;
                $b+=$rgb&255;
                $c++;
            }
        }
        return [intval($r/$c),intval($g/$c),intval($b/$c)];
    }

    $logoPath = "../uploads/logos/".$company['company_logo'];
    $brand = dominantColor($logoPath);

    
    /* ===============================
    PDF
    ================================ */
    class InvoicePDF extends FPDF {
        public $brand;
        public $logo;

        function Header(){
            if(file_exists($this->logo)){
                $this->Image($this->logo,10,10,35);
            }
            $this->SetFont('Arial','B',18);
            $this->Cell(0,10,'INVOICE',0,1,'R');
            $this->Ln(10);

            $this->SetFont('Arial','B',50);
            $this->SetTextColor(200,200,200);
            $this->Text(30,190,'PAID');
            $this->SetTextColor(0,0,0);
        }
    }

  
   /* ===============================
CREATE PDF
================================ */

$pdf = new InvoicePDF();
$pdf->brand = $brand;
$pdf->logo  = $logoPath;
$pdf->AddPage();

$pdf->Ln(30); // Push content below logo

$pdf->SetFont('Arial','',11);
$pdf->SetTextColor(0,0,0);

/* ===============================
SELLER INFORMATION (LEFT WELL ALIGNED)
================================ */

// Start properly below logo
$startY = 55; // push further down if logo is big
$pdf->SetXY(12, $startY);

// Left column width (safe distance from buyer side)
$leftWidth = 90;

$pdf->SetFont('Arial','B',14);
$pdf->Cell($leftWidth,8,'Seller Information',0,1);

$pdf->SetFont('Arial','',11);

// Company Name
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Company Name: '.$company['company_name'],0,'L');

// Address (wraps automatically)
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Address: '.$company['company_office_address'],0,'L');

// Mobile
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Mobile: '.$company['company_mobile'],0,'L');

// Email
$pdf->SetX(12);
$pdf->MultiCell($leftWidth,6,'Email: '.$company['company_email'],0,'L');


/* ===============================
RECEIPT DETAILS
================================ */


// Move to right side of page
$pdf->SetXY(115, $startY); // (X, Y) adjust if needed

$pdf->SetFont('Arial','B',14);
$pdf->Cell(80,8,'Receipt Details',0,1);

$pdf->SetFont('Arial','',11);
$pdf->SetX(120);
$pdf->Cell(80,6,'Receipt No: '.$invoice_number,0,1);

$pdf->SetX(120);
$pdf->Cell(80,6,'Date: '.date('d/m/Y'),0,1);

$pdf->Ln(4);

// Buyer Info
$pdf->SetX(120);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(80,8,'Buyer Information',0,1);

$pdf->SetFont('Arial','',11);
$pdf->SetX(120);
$pdf->Cell(80,6,'Customer Name: '.$customer['fullname'],0,1);

$pdf->SetX(120);
$pdf->Cell(80,6,'Phone: '.$customer['phone'],0,1);

// move cursor back down before table
$pdf->Ln(15);

/* ===============================
ITEMS TABLE HEADER
================================ */
$pdf->SetFillColor(220,210,200);
$pdf->SetFont('Arial','B',11);

$pdf->Cell(80,10,'Item Description',1,0,'L',true);
$pdf->Cell(25,10,'Quantity',1,0,'C',true);
$pdf->Cell(40,10,'Unit Price (NGN)',1,0,'R',true);
$pdf->Cell(45,10,'Total (NGN)',1,1,'R',true);

/* ===============================
ITEMS ROWS
================================ */
$pdf->SetFont('Arial','',11);

$subtotal = 0;

foreach($items as $i){

    $line_total = $i['total_price'];
    $subtotal += $line_total;

    // Use job_name as the description
    $pdf->Cell(80,9,$job_name,1); 
    $pdf->Cell(25,9,$i['quantity'],1,0,'C');
    $pdf->Cell(40,9,'NGN' .number_format($i['unit_price']),1,0,'R');
    $pdf->Cell(45,9,'NGN' .number_format($line_total),1,1,'R');
}

$pdf->Ln(6);

/* ===============================
TOTALS SECTION
================================ */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(145,8,'Subtotal:',0,0,'R');
$pdf->Cell(45,8,'NGN' .number_format($subtotal),0,1,'R');

$pdf->SetFont('Arial','B',13);
$pdf->Cell(145,10,'Grand Total Paid:',0,0,'R');
$pdf->Cell(45,10,'NGN' .number_format($subtotal),0,1,'R');

$pdf->Ln(8);

/* ===============================
PAYMENT METHOD
================================ */
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,8,'Payment Method',0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,$payment_method,0,1);

$pdf->Ln(6);

/* ===============================
SIGNATURE SECTION
================================ */

$pdf->Ln(10); // space from payment section

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Customer Signature',0,1);

// draw signature line
$pdf->SetFont('Arial','',11);
$pdf->Cell(30,8,'Sign:',0,0);
$pdf->Cell(80,8,'', 'B',1); // underline space for signature
$pdf->Ln(6);

/* ===============================
NOTES
================================ */
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,8,'Notes',0,1);

$pdf->SetFont('Arial','',11);

if(!empty($invoice_note)){
    $pdf->MultiCell(0,6,$invoice_note);
} else {
    $pdf->MultiCell(0,6,'Thank you for your purchase! Goods once sold are not returnable.');
}


/* ===============================
   SAVE INVOICE FOR RECORDS
================================ */
$invoicePath = "../invoices/$invoice_number.pdf";
$pdf->Output('F', $invoicePath); // Save PDF on server

/* ===============================
   EMAIL INVOICE
================================ */
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'support@ubamus.com';
        $mail->Password   = 'Zaida@25';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

$mail->setFrom('support@ubamus.com', $company['company_name']);
$mail->addAddress($customer['email']);
$mail->addAddress($company['admin_email']);
$mail->Subject = "Invoice $invoice_number";
$mail->Body = "Please find your invoice attached.";
$mail->addAttachment($invoicePath);
$mail->send();

/* ===============================
   OPEN INVOICE IN BROWSER FOR ADMIN
================================ */
echo "<script>
    window.open('$invoicePath', '_blank'); // open PDF in new tab
    alert('Invoice generated! It will open in a new tab for printing.');
    window.location.href='job-entries.php?id=$job_id'; // redirect after alert
</script>";
exit;

} catch (Exception $e) {
    
        $pdo->rollBack();
    
        echo "<script>
            alert('Job creation failed: {$e->getMessage()}');
            window.history.back();
        </script>";
        exit;
}


    ?>