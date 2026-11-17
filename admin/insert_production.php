<?php
include('../config/db.php');
include('../config/smtp.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('display_startup_errors',1);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
require '../vendor/fpdf/fpdf.php';



$company_id = $_SESSION['company_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$company_id || !$user_id) {
    die("Company or User not set in session.");
}

// Get company name
$stmt = $pdo->prepare("SELECT company_name FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company_name = $stmt->fetchColumn();

if (!$company_name) {
    die("Company not found.");
}

// Create abbreviation from company name
$words = preg_split('/\s+/', trim($company_name));
$company_abbr = '';

foreach ($words as $word) {
    $company_abbr .= strtoupper($word[0]);
}

// If it's a single word, use the first 3 letters instead
if (count($words) == 1) {
    $company_abbr = strtoupper(substr($company_name, 0, 3));
}

// Collect form data
$location_id = $_POST['location_id'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$product_name = $_POST['product_name'] ?? '';
$dept = $_POST['dept'] ?? '';
$batch_number = $_POST['batch_number'] ?? '';
$quantity_produced = floatval($_POST['quantity_produced'] ?? 0);
$description = $_POST['description'] ?? '';
$production_status = $_POST['production_status'] ?? '';
$profit_margin = floatval($_POST['profit_margin'] ?? 0);

// Generate report number
$count = $pdo->query("SELECT COUNT(*) FROM stock_production")->fetchColumn() + 1;

$report_number = $company_abbr . '-PR-' . date('ymd') . '-' . sprintf('%04d', $count);



// Arrays
$raw_materials = $_POST['raw_materials'] ?? [];
$services = $_POST['services'] ?? [];



if (!$product_name || !$quantity_produced || !$location_id) {
    die("Missing required production data.");
}

$stmt = $pdo->prepare("
    SELECT id
    FROM products
    WHERE company_id = ?
      AND name = ?
");
$stmt->execute([$company_id, $product_name]);

// if ($stmt->fetch()) {
//     throw new Exception("A product with this name already exists.");
// }



try {
    // Start transaction
    $pdo->beginTransaction();



    $created_at = date('Y-M-D H:i:s');

    // -----------------------
    // 1. Create Product
    // -----------------------
    $slug_product = strtolower(str_replace(' ', '-', $product_name));
    $barcode = 'BC-PROD-'.time().rand(100,999); // Simple barcode

    // Calculate total material cost
    $total_materials = 0;
    foreach ($raw_materials as $rm) {
        $total_materials += floatval($rm['qty'] ?? 0) * floatval($rm['unitCost'] ?? 0);
    }

    // Calculate total services cost
    $total_services = 0;
    foreach ($services as $srv) {
        $total_services += floatval($srv['cost'] ?? 0);
    }

    $total_production_cost = $total_materials + $total_services;
    $unit_cost = $quantity_produced ? round($total_production_cost / $quantity_produced, 2) : 0;
    $unit_selling_price = $unit_cost * (1 + $profit_margin/100);

    // SELECT CATEGORIES
    $stmt = $pdo->prepare("SELECT name, stock_type, unit_id FROM product_categories WHERE id=? AND company_id=?");
    $stmt->execute([$category_id, $company_id]);
    $cat = $stmt->fetch();

    $category_name = $cat['name'];

    $unit_id = $cat['unit_id'];
    $stock_type = $dept;

    // SELECT LOCATION
    $stmt = $pdo->prepare("SELECT name FROM locations WHERE id=? AND company_id=?");
    $stmt->execute([$location_id, $company_id]);
    $loca = $stmt->fetch();

    $location_name = $loca['name'];

    // SELECT CREATED BY
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id=? AND company_id=?");
    $stmt->execute([$user_id, $company_id]);
    $userd = $stmt->fetch();

    $user_fullname = $userd['username'];



    $stmt = $pdo->prepare("INSERT INTO products
        (company_id, name, slug, barcode, category_id, stock_type, unit_id, cost_price, selling_price, description, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->execute([
        $company_id,
        $product_name,
        $slug_product,
        $barcode,
        $category_id,
        $stock_type,
        $unit_id,
        $unit_cost,
        $unit_selling_price,
        $description,
        $user_id
    ]);
    $product_id = $pdo->lastInsertId();
 
    // -----------------------
    // 2. Insert into Stock Production
    // -----------------------
    $expenses_breakdown = json_encode([
        'materials' => $total_materials,
        'services' => $total_services
    ]);

    $updated_at = $created_at;
    $stmt = $pdo->prepare("INSERT INTO stock_production
    (
        product_id,
        company_id,
        location_id,
        batch_number,
        product_name,
        report_number,
        category_id,
        quantity_produced,
        description,
        unit_cost,
        total_cost,
        profit_margin,
        unit_selling_price,
        expenses_breakdown,
        status,
        created_by,
        created_at,
        updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([
    $product_id,
    $company_id,
    $location_id,
    $batch_number,
    $product_name,
    $report_number,
    $category_id,
    $quantity_produced,
    $description,
    $unit_cost,
    $total_production_cost,
    $profit_margin,
    $unit_selling_price,
    $expenses_breakdown,
    $production_status,
    $user_id
]);


$production_id = $pdo->lastInsertId();




// Insert into Production raw materials
$stmt = $pdo->prepare("
    INSERT INTO production_raw_materials
    (
        name,
        company_id,
        location_id,
        production_id,
        quantity_used,
        unit_cost,
        total_cost
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($raw_materials as $rm) {

    $qty = floatval($rm['qty']);
    $cost = floatval($rm['unitCost']);
    $total = $qty * $cost;

    $stmt->execute([
        $rm['name'],
        $company_id,
        $location_id,
        $production_id,
        $qty,
        $cost,
        $total
    ]);
}

// insert into product_services 
$stmt = $pdo->prepare("
    INSERT INTO production_services
    (
        production_id,
        company_id,
        location_id,
        service_name,
        cost
    )
    VALUES (?, ?, ?, ?, ?)
");

foreach ($services as $srv) {

    $stmt->execute([
        $production_id,
        $company_id,
        $location_id,
        $srv['name'],
        $srv['cost']
    ]);
}

// Updating stock if production_status === 'Finished' else head to production.

if ($production_status === 'Finished') {
    // Increase finished goods stock

    // Check if stock record exists
    $stmt = $pdo->prepare("
        SELECT id, quantity
        FROM stocks
        WHERE company_id = ?
        AND location_id = ?
        AND product_id = ?
    ");

    $stmt->execute([
        $company_id,
        $location_id,
        $product_id
    ]);

    $stock = $stmt->fetch();

    if ($stock) {

        // Increase existing stock
        $stmt = $pdo->prepare("
            UPDATE stocks
            SET quantity = quantity + ?
            WHERE id = ?
        ");

        $stmt->execute([
            $quantity_produced,
            $stock['id']
        ]);

    } else {

        $supplier = "Production";
        // Create stock record
        $stmt = $pdo->prepare("
            INSERT INTO stocks
            (
                company_id,
                location_id,
                product_id,
                quantity,
                supplier,
                created_at,
                stocked_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $company_id,
            $location_id,
            $product_id,
            $quantity_produced,
            $supplier,
            $created_at,
            $user_id
        ]);

    }

   
}

$production_stage = 1;

// Fetching Company data form emailing and invoice begins
$stmt = $pdo->prepare("
SELECT * FROM companies
WHERE id=?
");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);



$pdo->commit();

    
    $currency = "₦";
    

    class ProductionPDF extends FPDF
{
    public $status = '';
    public $company = [];
    public $reportTitle = 'PRODUCTION REPORT';
    protected $angle = 0;

    function Header()
    {
        // Logo
        $logoPath = __DIR__ . '/../uploads/logos/' . $this->company['company_logo'];

        if (!empty($this->company['company_logo']) && file_exists($logoPath)) {
            $this->Image($logoPath, 12, 10, 28);
        }

        // Company Name
        $this->SetXY(45,10);
        $this->SetFont('Arial','B',18);
        $this->Cell(150,8,strtoupper($this->company['company_name']),0,1,'C');

        // Address
        $this->SetFont('Arial','',10);
        $this->SetX(45);
        $this->Cell(
            150,
            5,
            $this->company['company_office_address'],
            0,
            1,
            'C'
        );

        // Contact
        $this->SetX(45);
        $this->Cell(
            150,
            5,
            $this->company['company_mobile'].' | '.$this->company['company_email'],
            0,
            1,
            'C'
        );

        // Divider
        $this->Ln(3);
        $this->SetDrawColor(180,180,180);   
        $this->Line(10,35,200,35);

        // Report Title
        $this->Ln(4);
        $this->SetFont('Arial','B',20);
        $this->Cell(0,10,$this->reportTitle,0,1,'C');

        // Watermark
        $this->Watermark();

        $this->Ln(3);
    }


function Footer()
{
    $this->SetY(-15);

    $this->SetDrawColor(180,180,180);
    $this->Line(10, $this->GetY(), 200, $this->GetY());

    $this->Ln(2);

    $this->SetFont('Arial','I',8);

    $footerCompanyName = $this->company['company_name'] ?? 'Company';

    $this->Cell(
        0,
        5,
        'Generated by ' . $footerCompanyName . ' ERP | ' .
        date('d M Y H:i') . ' | Page ' .
        $this->PageNo() . '/{nb}',
        0,
        0,
        'C'
    );
}


    function Watermark()
    {
        if(empty($this->status))
            return;

        $this->SetTextColor(230,230,230);
        $this->SetFont('Arial','B',45);

        $this->Rotate(45,60,190);
        $this->Text(30,190,strtoupper($this->status));
        $this->Rotate(0);

        $this->SetTextColor(0,0,0);
    }

    function Rotate($angle,$x=-1,$y=-1)
    {
        if($x==-1)
            $x=$this->x;

        if($y==-1)
            $y=$this->y;

        if($this->angle!=0)
            $this->_out('Q');

        $this->angle=$angle;

        if($angle!=0)
        {
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);

            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;

            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c,$s,-$s,$c,$cx,$cy,-$cx,-$cy
            ));
        }
    }

    function _endpage()
    {
        if($this->angle!=0)
        {
            $this->angle=0;
            $this->_out('Q');
        }

        parent::_endpage();
    }
}



    $pdf = new ProductionPDF('P','mm','A4');

    $pdf->company = $company;
    $pdf->status = $production_status;

    $pdf->AliasNbPages();
    $pdf->AddPage();

    
    $pdf->SetFont('Arial','',11);
    $pdf->SetTextColor(0,0,0);
    
    // -------------------------------
    // TOP SECTION: Seller Info & Buyer Info
    // -------------------------------
    // =====================================================
    // PRODUCTION INFORMATION
    // =====================================================
    
    $pdf->Ln(3);
    
    $pdf->SetFillColor(235,240,245);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(190,8,'PRODUCTION INFORMATION',1,1,'L',true);
    
    $pdf->SetFont('Arial','',10);
    
    $leftX = 10;
    $rightX = 110;
    
    $line = 7;
    
    // LEFT COLUMN
    $pdf->SetX($leftX);
    $pdf->Cell(40,$line,'Reference No:',1,0);
    $pdf->Cell(55,$line,$production_id,1,0);
    
    $pdf->SetX($rightX);
    $pdf->Cell(35,$line,'Batch No:',1,0);
    $pdf->Cell(55,$line,$batch_number,1,1);
    
    $pdf->SetX($leftX);
    $pdf->Cell(40,$line,'Status:',1,0);
    $pdf->Cell(55,$line,$production_status,1,0);
    
    $pdf->SetX($rightX);
    $pdf->Cell(35,$line,'Date:',1,0);
    $pdf->Cell(55,$line,date('d M Y H:i A'),1,1);
    
    $pdf->SetX($leftX);
    $pdf->Cell(40,$line,'Prepared By:',1,0);
    $pdf->Cell(55,$line,$user_fullname,1,0);
    
    $pdf->SetX($rightX);
    $pdf->Cell(35,$line,'Location:',1,0);
    $pdf->Cell(55,$line,$location_name,1,1);
    
    $pdf->SetX($leftX);
    $pdf->Cell(40,$line,'Category:',1,0);
    $pdf->Cell(55,$line,$category_name,1,0);
    
    $pdf->SetX($rightX);
    $pdf->Cell(35,$line,'Production Stage:',1,0);
    $pdf->Cell(55,$line,$production_stage,1,1);
    
    $pdf->Ln(6);

    // =====================================================
// PRODUCT INFORMATION
// =====================================================

$pdf->SetFillColor(235,240,245);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(190,8,'PRODUCT INFORMATION',1,1,'L',true);

$pdf->SetFont('Arial','',10);

$pdf->Cell(40,7,'Product Name',1,0);
$pdf->Cell(150,7,$product_name,1,1);

$pdf->Cell(40,7,'Barcode',1,0);
$pdf->Cell(150,7,$barcode,1,1);

$pdf->Cell(40,7,'Quantity Produced',1,0);
$pdf->Cell(55,7,number_format($quantity_produced,2),1,0);

$pdf->Cell(40,7,'Stock Type',1,0);
$pdf->Cell(55,7,$stock_type,1,1);

$pdf->Cell(40,7,'Unit Cost',1,0);
$pdf->Cell(55,7,'NGN '.number_format($unit_cost,2),1,0);

$pdf->Cell(40,7,'Selling Price',1,0);
$pdf->Cell(55,7,'NGN '.number_format($unit_selling_price,2),1,1);

$pdf->Cell(40,7,'Profit Margin',1,0);
$pdf->Cell(55,7,number_format($profit_margin,2).' %',1,0);

$pdf->Cell(40,7,'Expected Revenue',1,0);
$pdf->Cell(
    55,
    7,
    'NGN '.number_format($quantity_produced * $unit_selling_price,2),
    1,
    1
);

$pdf->Ln(6);

$pdf->SetFillColor(235,240,245);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(190,8,'PRODUCTION COST ANALYSIS',1,1,'L',true);

$pdf->SetFont('Arial','',10);

$pdf->Cell(60,7,'Raw Materials Cost',1,0);
$pdf->Cell(35,7,'NGN '.number_format($total_materials,2),1,0,'R');

$pdf->Cell(60,7,'Production Services',1,0);
$pdf->Cell(35,7,'NGN '.number_format($total_services,2),1,1,'R');

$pdf->Cell(60,7,'Total Production Cost',1,0);
$pdf->Cell(35,7,'NGN '.number_format($total_production_cost,2),1,0,'R');

$pdf->Cell(60,7,'Profit Margin',1,0);
$pdf->Cell(35,7,number_format($profit_margin,2).' %',1,1,'R');

$pdf->Cell(60,7,'Unit Cost',1,0);
$pdf->Cell(35,7,'NGN '.number_format($unit_cost,2),1,0,'R');

$pdf->Cell(60,7,'Selling Price',1,0);
$pdf->Cell(35,7,'NGN '.number_format($unit_selling_price,2),1,1,'R');

$pdf->Cell(60,7,'Expected Revenue',1,0);
$pdf->Cell(
    35,
    7,
    'NGN '.number_format($quantity_produced * $unit_selling_price,2),
    1,
    0,
    'R'
);

$pdf->Cell(60,7,'Expected Profit',1,0);
$pdf->Cell(
    35,
    7,
    'NGN '.number_format(($quantity_produced * $unit_selling_price)-$total_production_cost,2),
    1,
    1,
    'R'
);

$pdf->Ln(6);

$pdf->SetFillColor(235,240,245);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(190,8,'RAW MATERIALS',1,1,'L',true);

$pdf->SetFont('Arial','B',10);

$pdf->Cell(12,8,'#',1,0,'C',true);
$pdf->Cell(78,8,'Material',1,0,'C',true);
$pdf->Cell(25,8,'Qty',1,0,'C',true);
$pdf->Cell(35,8,'Unit Cost',1,0,'C',true);
$pdf->Cell(40,8,'Total',1,1,'C',true);

$pdf->SetFont('Arial','',10);

$sn=1;

foreach($raw_materials as $rm){

    $pdf->Cell(12,8,$sn++,1,0,'C');
    $pdf->Cell(78,8,$rm['name'],1);
    $pdf->Cell(25,8,$rm['qty'],1,0,'C');
    $pdf->Cell(35,8,'NGN '.number_format($rm['unitCost'],2),1,0,'R');
    $pdf->Cell(
        40,
        8,
        'NGN '.number_format($rm['qty']*$rm['unitCost'],2),
        1,
        1,
        'R'
    );

}

$pdf->Ln(8);

$pdf->SetFillColor(235,240,245);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(190,8,'PRODUCTION SERVICES',1,1,'L',true);

$pdf->SetFont('Arial','B',10);

$pdf->Cell(12,8,'#',1,0,'C',true);
$pdf->Cell(138,8,'Service',1,0,'C',true);
$pdf->Cell(40,8,'Cost',1,1,'C',true);

$pdf->SetFont('Arial','',10);

$sn=1;

foreach($services as $srv){

    $pdf->Cell(12,8,$sn++,1,0,'C');
    $pdf->Cell(138,8,$srv['name'],1);
    $pdf->Cell(40,8,'NGN '.number_format($srv['cost'],2),1,1,'R');

}
 
$pdf->Ln(8);

$pdf->SetFillColor(235,240,245);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(190,8,'PRODUCTION DESCRIPTION',1,1,'L',true);

$pdf->SetFont('Arial','',10);

$pdf->MultiCell(
    190,
    7,
    empty($description)
        ? 'No production description was provided.'
        : $description,
    1
);

// =====================================================
// INVENTORY SUMMARY
// =====================================================

$pdf->Ln(8);

$pdf->SetFillColor(235,240,245);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(190,8,'INVENTORY SUMMARY',1,1,'L',true);

$pdf->SetFont('Arial','',10);

$pdf->Cell(60,7,'Quantity Produced',1,0);
$pdf->Cell(35,7,number_format($quantity_produced,2),1,0,'C');

$pdf->Cell(60,7,'Production Status',1,0);
$pdf->Cell(35,7,$production_status,1,1,'C');

$pdf->Cell(60,7,'Batch Number',1,0);
$pdf->Cell(35,7,$batch_number,1,0);

$pdf->Cell(60,7,'Stock Type',1,0);
$pdf->Cell(35,7,$stock_type,1,1);

$pdf->Ln(8);

// =====================================================
// PRODUCTION NOTES
// =====================================================

$pdf->SetFillColor(235,240,245);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(190,8,'PRODUCTION NOTES',1,1,'L',true);

$pdf->SetFont('Arial','I',10);

// Draw a large notes box (about 60mm high)
$boxHeight = 60;

// Save current position
$x = $pdf->GetX();
$y = $pdf->GetY();

// Draw the outer rectangle
$pdf->Rect($x, $y, 190, $boxHeight);

// Print only a short instruction at the top
$pdf->MultiCell(
    190,
    6,
    "Feel free to write any production notes, observations, recommendations, or comments below.",
    0,
    'L'
);

// Move cursor to the bottom of the box
$pdf->SetXY($x, $y + $boxHeight);

$pdf->Ln(8);
$pdf->Ln(10);


// =====================================================
// APPROVALS
// =====================================================

$pdf->SetFont('Arial','B',12);

$pdf->Cell(60,7,'Prepared By',0,0,'C');
$pdf->Cell(70,7,'Reviewed By',0,0,'C');
$pdf->Cell(60,7,'Approved By',0,1,'C');

$pdf->Ln(15);

$pdf->Cell(60,0,'',1,0,'C');
$pdf->Cell(70,0,'',1,0,'C');
$pdf->Cell(60,0,'',1,1,'C');

$pdf->Ln(3);

$pdf->SetFont('Arial','',10);

$pdf->Cell(60,6,$user_fullname,0,0,'C');
$pdf->Cell(70,6,'Production Manager',0,0,'C');
$pdf->Cell(60,6,'General Manager',0,1,'C');

$pdf->Ln(10);


// =====================================================
// SYSTEM INFORMATION
// =====================================================

$pdf->SetFillColor(245,245,245);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(190,8,'SYSTEM INFORMATION',1,1,'L',true);

$pdf->SetFont('Arial','',9);


$pdf->MultiCell(
    190,
    6,
    "Production ID: {$production_id}\n".
    "Generated: ".date('d M Y H:i:s')."\n".
    "Generated By: {$user_fullname}\n".
    "Application: {$company_name} ERP\n".
    "Document: Official Production Report",
    1
);



$reportDir = __DIR__ . '/../production_reports';

if (!is_dir($reportDir)) {
    mkdir($reportDir, 0777, true);
}

$report_file = $reportDir . "/Production_Report_{$report_number}.pdf";
$pdf->Output('F', $report_file);

    

    try {

    // -------------------------------
    // EMAIL REPORT
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

    $mail->addAddress($company['company_email']); // Company admin
    $mail->Subject = "Production Report $report_number";
    $mail->Body = "Dear {$company_name},

    Successful production...

    Please find attached your Production Report (Report No: {$report_number}) for your recent production with {$company['company_name']}.

    We greatly appreciate your support and look forward to serving you again.

    Kind regards,
    {$company['company_name']} Team";
        $mail->addAttachment($report_file);
        $mail->send();


        } catch (Exception $e) {
            echo "<script>
            alert('Production successful, but unable to send email. Please check your internet connection.');
            window.location.href='production_overview.php';
        </script>";
        exit();
        }
        echo "<script>
        alert('Production successful, report generated and sent to customer');
        window.location.href='production_overview.php';
    </script>";
    exit;


 } catch (Exception $e) {
     if ($pdo->inTransaction()) $pdo->rollBack();
     $msg = addslashes($e->getMessage());
    //  echo "<script>alert('Error processing production: {$msg}'); window.history.back();</script>";
        echo $msg;
     exit();
 }
?>
