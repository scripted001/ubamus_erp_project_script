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

date_default_timezone_set('Africa/Lagos');

$date = date('Y-m-d H:i:s');
$user_id = $_SESSION['user_id'] ?? null;


$previous_product_id = $_SESSION['previous_product_id']; 
$previous_company_id = $_SESSION['previous_company_id']; 
$previous_location_id = $_SESSION['previous_location_id']; 
$previous_batch_number = $_SESSION['previous_batch_number']; 
$previous_product_name = $_SESSION['previous_product_name']; 
$previous_report_number = $_SESSION['previous_report_number']; 
$previous_category_id = $_SESSION['previous_category_id']; 
$previous_quantity_produced = $_SESSION['previous_quantity_produced']; 
$previous_unit_cost = $_SESSION['previous_unit_cost']; 
$previous_total_production_cost = $_SESSION['previous_total_production_cost']; 
$previous_profit_margin = $_SESSION['previous_profit_margin']; 
$previous_unit_selling_price = $_SESSION['previous_unit_selling_price']; 
$previous_expenses_breakdown = $_SESSION['previous_expenses_breakdown']; 
$previous_production_status = $_SESSION['previous_production_status']; 
$created_by = $_SESSION['created_by']; 



$company_id = $previous_company_id;

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

        $quantity_produced = floatval($_POST['new_quantity_produced'] ?? 0);
        $unit_cost = floatval($_POST['unit_cost'] ?? 0);
        $profit_margin = floatval($_POST['profit_margin'] ?? 0);
        $unit_selling_price = floatval($_POST['unit_selling_price'] ?? 0);
        $production_status = $_POST['production_status'] ?? '';
        // Arrays
        $raw_materials = $_POST['raw_materials'] ?? [];


        $services = $_POST['services'] ?? [];

        $count = $pdo->query("SELECT COUNT(*) FROM stock_production_stages")->fetchColumn() + 1;

        $report_number = $company_abbr . '-PR-' . date('ymd') . '-' . sprintf('%04d', $count);


    try {
        // Start transaction
        $pdo->beginTransaction();

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

            $new_total_production_cost = $total_materials + $total_services;
            $new_unit_cost = $quantity_produced ? round($new_total_production_cost / $quantity_produced, 2) : 0;
            $new_unit_selling_price = $unit_cost * (1 + $profit_margin/100);

            // present expenses breakdown
            $expenses_breakdown = json_encode([
                'materials' => $total_materials,
                'services' => $total_services
            ]);

            // Keep the original JSON string intact
            $previous_expenses_array = json_decode($previous_expenses_breakdown, true);

            // Extract previous raw materials
            $previous_raw_materials = [];
    
        if(isset($previous_expenses_array['raw_materials']) && is_array($previous_expenses_array['raw_materials']) ){
    
            $previous_raw_materials = $previous_expenses_array['raw_materials'];
        }
    
    
            // Extract previous services
            $previous_services = [];
    
        if(isset($previous_expenses_array['production_services']) && is_array($previous_expenses_array['production_services'])){
            $previous_services = $previous_expenses_array['production_services'];
        }
    
        // If the JSON was empty or invalid
        if (!is_array($previous_expenses_array)) {
            $previous_expenses_array = [
                'materials' => 0,
                'services'  => 0
            ];
        }
    
            $up_unit_expenses_breakdown = json_encode([
                'materials' => ($previous_expenses_array['materials'] ?? 0) + $total_materials,
                'services'  => ($previous_expenses_array['services'] ?? 0) + $total_services
            ]);

            // Fetching datafrom production table to update data.
            $stmt = $pdo->prepare("SELECT * FROM stock_production WHERE batch_number = ? AND report_number = ?");
            $stmt->execute([$previous_batch_number, $previous_report_number]);
            $productionDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            $stock_production_id = $productionDetails['id'];

            if ($productionDetails) {
                    // echo $productionDetails['id'];
                    $up_product_id = $previous_product_id;
                    $up_company_id = $previous_company_id;
                    $up_location_id = $previous_location_id;
                    $up_batch_number = $previous_batch_number;
                    $up_product_name = $previous_product_name;
                    $up_report_number = $report_number;
                    $up_category_id = $previous_category_id;

                    //  product pricing calculations

                    // $up_unit_cost = $previous_unit_cost + $new_unit_cost;
                    // $up_total_production_cost = $previous_total_production_cost + $new_total_production_cost;
                    // $up_profit_margin = $previous_profit_margin + $profit_margin;
                    // $up_unit_selling_price = $previous_unit_selling_price + $new_unit_selling_price;

                    $up_quantity_produced = $previous_quantity_produced + $quantity_produced;

                    // Keep total production cost
                    $up_total_production_cost = $previous_total_production_cost + $new_total_production_cost;

                    // Calculate final unit cost
                    // Cost price remains unchanged
                if ($up_quantity_produced > 0) {
                    $up_unit_cost = round($up_total_production_cost / $up_quantity_produced, 2);
                } else {
                    $up_unit_cost = $previous_unit_cost;
                }

                    // Keep the selected profit margin
                    $up_profit_margin = floatval($profit_margin);



                    /*
                    |--------------------------------------------------------------------------
                    | FINAL SELLING PRICE CALCULATION
                    |--------------------------------------------------------------------------
                    |
                    | Only apply profit when production is completed.
                    |
                    | Example:
                    |
                    | Cost price = 1000
                    | Profit margin = 20%
                    |
                    | Selling price = 1000 + (1000 * 20%)
                    |              = 1200
                    |
                    */


                if ($production_status === "Finished") {
                    $profit_amount = $up_unit_cost * ($up_profit_margin / 100);
                    $up_unit_selling_price = round($up_unit_cost + $profit_amount, 2);
                    $unit_profit = $up_unit_selling_price - $up_unit_cost;
                    $total_profit = $unit_profit * $up_quantity_produced;
                } else {

                    $unit_profit = 0;
                    $total_profit = 0;
                    // Production still running
                    // Keep previous selling price

                    $up_unit_selling_price = $previous_unit_selling_price;
                }

                    // declaring updating data 
                    // echo $up_unit_cost;
                    // echo $up_product_id;     
                    // echo $up_company_id;
                    // echo $up_location_id;
                    // echo $up_batch_number;
                    // echo $up_product_name;
                    // echo $up_report_number;
                    // echo $up_category_id;
                    // echo $up_unit_selling_price;
                    // echo $up_profit_margin;
                    // echo $production_status;
                    // echo $unit_profit;
                    // echo $total_profit; 
                    // echo $up_product_name;
                    // echo $up_quantity_produced;
                    // echo $up_total_production_cost;
                    // echo $up_unit_expenses_breakdown;

                    // navigate to line 106 to line 126 for everything about expenses breakdown  
                    $created_at = $productionDetails['created_at'];
                    $updated_at = $date;

                    // INSERTING INTO STOCK_PRODUCTION_STAGES TABLE
                    $stmt = $pdo->prepare("INSERT INTO  stock_production_stages(stock_production_id, product_id, company_id, location_id, batch_number, report_number, quantity_produced, previous_quantity_produced, unit_cost, previous_unit_cost, total_cost, previous_total_cost, profit_margin, previous_profit_margin, unit_selling_price, previous_unit_selling_price, expenses_break_down, previous_expenses_break_down, status, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $stock_production_id, $up_product_id, $up_company_id, $up_location_id, $up_batch_number, $up_report_number, $quantity_produced, $previous_quantity_produced,   $new_unit_cost, $previous_unit_cost, $new_total_production_cost, $previous_total_production_cost, $profit_margin, $previous_profit_margin, $new_unit_selling_price,   $previous_unit_selling_price, $expenses_breakdown, $previous_expenses_breakdown, $production_status, $created_by, $created_at, $updated_at
                    ]);

                    // UPDATING STOCK_PRODUCTION TABLES


                    $stmt = $pdo->prepare("
                    UPDATE stock_production SET product_id = ?, company_id = ?, location_id = ?, batch_number = ?, product_name = ?, report_number = ?, category_id = ?, quantity_produced = ?, unit_cost = ?, total_cost = ?, profit_margin = ?, unit_selling_price = ?, unit_profit= ?, total_profit= ?, expenses_breakdown = ?, status = ?, created_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                    $up_product_id, $up_company_id, $up_location_id, $up_batch_number, $up_product_name, $previous_report_number, $up_category_id, $up_quantity_produced, $up_unit_cost, $up_total_production_cost, $up_profit_margin, $up_unit_selling_price, $unit_profit, $total_profit, $up_unit_expenses_breakdown, $production_status, $created_by, $stock_production_id
                    ]);


                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_production_stages WHERE stock_production_id = ?");
                    $stmt->execute([$stock_production_id]);
                    $production_stage = $stmt->fetchColumn() + 1;


                    // Insert into Production raw materials
                    $stmt = $pdo->prepare("INSERT INTO production_raw_materials( name, company_id, location_id, production_id, quantity_used, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");

                    foreach ($raw_materials as $rm) {

                    $qty = floatval($rm['qty']);
                    $cost = floatval($rm['unitCost']);
                    $total = $qty * $cost;

                    $stmt->execute([
                        $rm['name'],
                        $company_id,
                        $up_location_id,
                        $stock_production_id,
                        $qty,
                        $cost,
                        $total
                    ]);
                    }

                    // insert into product_services 
                    $stmt = $pdo->prepare("INSERT INTO production_services(production_id, company_id, location_id, service_name, cost) VALUES (?, ?, ?, ?, ?)");
                    foreach ($services as $srv) {
                        $stmt->execute([
                            $stock_production_id,
                            $company_id,
                            $up_location_id,
                            $srv['name'],
                            $srv['cost']
                        ]);
                    }

                // Updating stock if production_status === 'Finished' else head to production.

                if ($production_status === 'Finished') {
                        // Increase finished goods stock

                        // Check if stock record exists
                        $stmt = $pdo->prepare("SELECT id, quantity FROM stocks WHERE company_id = ? AND location_id = ? AND product_id = ?");
                        $stmt->execute([$up_company_id,$up_location_id,$up_product_id]);
                        $stock = $stmt->fetch();

                    if ($stock) {
                        // Increase existing stock
                        $stmt = $pdo->prepare("UPDATE stocks SET quantity = quantity + ? WHERE id = ?");
                        $stmt->execute([$up_quantity_produced,$stock['id']]);

                    } else {

                    $supplier = "Production";
                        // Create stock record
                        $stmt = $pdo->prepare("INSERT INTO stocks(company_id, location_id, product_id, quantity, supplier, created_at, stocked_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$company_id, $up_location_id, $up_product_id, $up_quantity_produced, $supplier, $created_at, $created_by]);

                    }
                }
            } else {
                echo "<script>
                alert('No production record found.');
                window.location.href='relab.php';
                </script>";

                exit();

            }


// SELECT CATEGORIES
$stmt = $pdo->prepare("SELECT name, stock_type, unit_id FROM product_categories WHERE id=? AND company_id=?");
$stmt->execute([$previous_category_id, $company_id]);
$cat = $stmt->fetch();

$category_name = $cat['name'];

$unit_id = $cat['unit_id'];
$stock_type = "Sales Only";

// SELECT PRODUCT BARCODE
$stmt = $pdo->prepare("SELECT barcode FROM products WHERE id=? AND company_id=?");
$stmt->execute([$previous_product_id, $company_id]);
$productD = $stmt->fetch();

$previous_barcode = $productD['barcode'];



// SELECT LOCATION
$stmt = $pdo->prepare("SELECT name FROM locations WHERE id=? AND company_id=?");
$stmt->execute([$up_location_id, $up_company_id]);
$loca = $stmt->fetch();

$location_name = $loca['name'];

// SELECT CREATED BY
$stmt = $pdo->prepare("SELECT username FROM users WHERE id=? AND company_id=?");
$stmt->execute([$user_id, $up_company_id]);
$userd = $stmt->fetch();

$user_fullname = $userd['username'];


$pdo->commit();

$previous_material_cost = $previous_expenses_array['materials'] ?? 0;
$previous_service_cost  = $previous_expenses_array['services'] ?? 0;

$up_total_materials = $previous_material_cost + $total_materials;
$up_total_services  = $previous_service_cost + $total_services;

if ($production_status === "Finished") {

$stmt = $pdo->prepare("
SELECT
    name,
    SUM(quantity_used) AS qty,
    unit_cost AS unitCost
FROM production_raw_materials
WHERE production_id = ?
GROUP BY name, unit_cost
ORDER BY name
");

$stmt->execute([$stock_production_id]);

$raw_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
SELECT
    service_name AS name,
    SUM(cost) AS cost
FROM production_services
WHERE production_id = ?
GROUP BY service_name
ORDER BY service_name
");

$stmt->execute([$stock_production_id]);

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);



    /* -----------------------------
       Merge raw materials
    ------------------------------*/
    $current_raw_materials = $raw_materials;

    $merged_materials = [];
    
    foreach (array_merge($previous_raw_materials, $current_raw_materials) as $rm) {
    
        $name = trim($rm['name']);
    
        if (!isset($merged_materials[$name])) {
            $merged_materials[$name] = [
                'name' => $name,
                'qty' => 0,
                'unitCost' => floatval($rm['unitCost'])
            ];
        }
    
        $merged_materials[$name]['qty'] += floatval($rm['qty']);
    
        // keep latest unit cost if desired
        $merged_materials[$name]['unitCost'] = floatval($rm['unitCost']);
    }
    
    $raw_materials = array_values($merged_materials);


    /* -----------------------------
       Merge services
    ------------------------------*/

    $current_services = $services;

$merged_services = [];

foreach (array_merge($previous_services, $current_services) as $srv) {

    $name = trim($srv['name']);

    if (!isset($merged_services[$name])) {
        $merged_services[$name] = [
            'name' => $name,
            'cost' => 0
        ];
    }

    $merged_services[$name]['cost'] += floatval($srv['cost']);
}

$services = array_values($merged_services);


    /* -----------------------------
       PDF VARIABLES
    ------------------------------*/

    $quantity_produced      = $up_quantity_produced;
    $total_materials        = $up_total_materials;
    $total_services         = $up_total_services;
    $new_total_production_cost = $up_total_production_cost;
    $new_unit_cost          = $up_unit_cost;
    $profit_margin          = $up_profit_margin;
    $new_unit_selling_price = $up_unit_selling_price;

    $pdf_unit_profit        = $up_unit_selling_price - $up_unit_cost;
    $pdf_total_profit       = $pdf_unit_profit * $quantity_produced;
    $pdf_total_revenue      = $quantity_produced * $new_unit_selling_price;

   


} else {

    // Keep current production values

    $quantity_produced      = $quantity_produced;
    $raw_materials          = $raw_materials;
    $services               = $services;
    $total_materials        = $total_materials;
    $total_services         = $total_services;
    $new_total_production_cost = $new_total_production_cost;
    $new_unit_cost          = $new_unit_cost;
    $profit_margin          = $profit_margin;
    $new_unit_selling_price = $new_unit_selling_price;

    $pdf_unit_profit        = $new_unit_selling_price - $new_unit_cost;
    $pdf_total_profit       = $pdf_unit_profit * $quantity_produced;
    $pdf_total_revenue      = $quantity_produced * $new_unit_selling_price;

}


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
    $this->Line(10,$this->GetY(),200,$this->GetY());

    $this->Ln(2);

    $this->SetFont('Arial','I',8);

    $this->Cell(
        0,
        5,
        'Generated by Ubamus ERP | '.date('d M Y H:i').' | Page '.$this->PageNo().'/{nb}',
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
$pdf->Cell(55,$line,$stock_production_id,1,0);

$pdf->SetX($rightX);
$pdf->Cell(35,$line,'Batch No:',1,0);
$pdf->Cell(55,$line,$previous_batch_number,1,1);

$pdf->SetX($leftX);
$pdf->Cell(40,$line,'Production Status:',1,0);
$pdf->Cell(55,$line,$previous_production_status,1,0);

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
$pdf->Cell(150,7,$previous_product_name,1,1);

$pdf->Cell(40,7,'Barcode',1,0);
$pdf->Cell(150,7,$previous_barcode,1,1);

$pdf->Cell(40,7,'Quantity Produced',1,0);
$pdf->Cell(55,7,number_format($quantity_produced,2),1,0);

$pdf->Cell(40,7,'Stock Type',1,0);
$pdf->Cell(55,7,$stock_type,1,1);

$pdf->Cell(40,7,'Unit Cost',1,0);
$pdf->Cell(55,7,'NGN '.number_format($new_unit_cost,2),1,0);

$pdf->Cell(40,7,'Selling Price',1,0);
$pdf->Cell(55,7,'NGN '.number_format($new_unit_selling_price,2),1,1);

$pdf->Cell(40,7,'Unit Profit',1,0);
$pdf->Cell(55,7,'NGN '.number_format($pdf_unit_profit,2),1,0);

$pdf->Cell(40,7,'Total Profit',1,0);
$pdf->Cell(55,7,'NGN '.number_format($pdf_total_profit,2),1,1);

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
$pdf->Cell(35,7,'NGN '.number_format($new_total_production_cost,2),1,0,'R');

$pdf->Cell(60,7,'Profit Margin',1,0);
$pdf->Cell(35,7,number_format($profit_margin,2).' %',1,1,'R');

$pdf->Cell(60,7,'Unit Cost',1,0);
$pdf->Cell(35,7,'NGN '.number_format($new_unit_cost,2),1,0,'R');

$pdf->Cell(60,7,'Selling Price',1,0);
$pdf->Cell(35,7,'NGN '.number_format($new_unit_selling_price,2),1,1,'R');

$pdf->Cell(60,7,'Expected Revenue',1,0);
$pdf->Cell(
35,
7,
'NGN '.number_format($quantity_produced * $new_unit_selling_price,2),
1,
0,
'R'
);

$pdf->Cell(60,7,'Expected Profit',1,0);
$pdf->Cell(
35,
7,
'NGN '.number_format(($quantity_produced * $new_unit_selling_price)-$new_total_production_cost,2),
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
$pdf->Cell(78,8,$rm['name'],1,0);
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
$pdf->Cell(35,7,$previous_batch_number,1,0);

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
"Production ID / Reference No: {$stock_production_id}\n".
"Generated: ".date('d M Y H:i:s')."\n".
"Generated By: {$user_fullname}\n".
"Application: Ubamus ERP\n".
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
 echo "<script>alert('Error processing production: {$msg}'); window.history.back();</script>";
    echo $msg;
 exit();
}











?>