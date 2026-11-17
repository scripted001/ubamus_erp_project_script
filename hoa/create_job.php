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
    echo "<script>alert('Add at least one service to the job.'); window.history.back();</script>";
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
   COMPANY AND ACTIVE LOCATION DATA
========================= */
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? LIMIT 1");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* The invoice identifies the active service location for this job. */
$location = [];
if (!empty($location_id) && !empty($company_id)) {
    $stmtLocation = $pdo->prepare("
        SELECT *
        FROM locations
        WHERE id = ? AND company_id = ?
        LIMIT 1
    ");
    $stmtLocation->execute([$location_id, $company_id]);
    $location = $stmtLocation->fetch(PDO::FETCH_ASSOC) ?: [];
}

$locationParts = [];
foreach ([
    $location['location_name'] ?? ($location['name'] ?? ''),
    $location['location_type'] ?? ($location['type'] ?? ''),
    $location['address_line1'] ?? '',
    $location['city'] ?? '',
    $location['state'] ?? ''
] as $part) {
    $part = trim((string)$part);
    if ($part !== '' && !in_array($part, $locationParts, true)) {
        $locationParts[] = $part;
    }
}
$seller_location_address = $locationParts
    ? implode(', ', $locationParts)
    : 'Active service location address not configured';

$payment = ($payment_status === 'full') ? 'PAID IN FULL' : 'PART PAYMENT - BALANCE OUTSTANDING';

/* Save the PDF using an absolute filesystem path; keep a browser-relative URL separately. */
$invoice_dir = __DIR__ . '/../invoices';
if (!is_dir($invoice_dir) && !mkdir($invoice_dir, 0775, true) && !is_dir($invoice_dir)) {
    throw new RuntimeException('The invoice directory could not be created.');
}
$invoice_file = $invoice_dir . "/invoice_{$invoice_number}.pdf";
$invoice_relative = "../invoices/invoice_{$invoice_number}.pdf";

function jobInvoiceText($value): string {
    $value = (string)$value;
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        if ($converted !== false) return $converted;
    }
    return preg_replace('/[^\\x20-\\x7E\\xA0-\\xFF]/', '', $value) ?? '';
}

function jobInvoiceMoney($amount): string {
    return 'NGN ' . number_format((float)$amount, 2);
}

class JobServiceInvoicePDF extends FPDF {
    function Header() {
        global $company, $invoice_number, $created_at, $payment;

        $navy = [20, 35, 60];
        $teal = [18, 120, 110];

        // Premium branded masthead.
        $this->SetFillColor($navy[0], $navy[1], $navy[2]);
        $this->Rect(0, 0, 210, 40, 'F');
        $this->SetFillColor($teal[0], $teal[1], $teal[2]);
        $this->Rect(0, 40, 210, 2, 'F');

        $logoPath = __DIR__ . '/../uploads/logos/' . ($company['company_logo'] ?? '');
        if (!empty($company['company_logo']) && is_file($logoPath)) {
            $this->Image($logoPath, 12, 8, 25, 21);
            $textX = 43;
            $companyWidth = 103;
        } else {
            $textX = 13;
            $companyWidth = 128;
        }

        $this->SetTextColor(255, 255, 255);
        $this->SetXY($textX, 8);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell($companyWidth, 8, jobInvoiceText($company['company_name'] ?? 'SERVICE PROVIDER'), 0, 1, 'L');

        $this->SetX($textX);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(210, 222, 232);
        $this->Cell($companyWidth, 5, 'PROFESSIONAL SERVICE DELIVERY', 0, 1, 'L');

        $this->SetXY(145, 8);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(53, 8, 'SERVICE INVOICE', 0, 1, 'R');
        $this->SetX(145);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(210, 222, 232);
        $this->Cell(53, 5, 'SERVICE RENDERING RECORD', 0, 1, 'R');

        $issued = date('d M Y, h:i A', strtotime($created_at));
        $this->SetY(45);
        $this->SetTextColor(75, 88, 102);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(110, 5, 'INVOICE NO.  ' . jobInvoiceText($invoice_number), 0, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell(80, 5, 'ISSUED  ' . jobInvoiceText($issued), 0, 1, 'R');

        $this->SetY(53);
        $this->SetFillColor(232, 243, 241);
        $this->SetTextColor(18, 92, 84);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(190, 8, '  PAYMENT STATUS: ' . jobInvoiceText($payment), 0, 1, 'L', true);
        $this->SetTextColor(35, 45, 55);
        $this->SetY(64);
    }

    function Footer() {
        $this->SetY(-28);
        $this->SetDrawColor(218, 225, 230);
        $this->Line(10, $this->GetY(), 200, $this->GetY());

        $this->SetY(-25);
        $this->SetFont('Arial', 'B', 7);
        $this->SetTextColor(18, 92, 84);
        $this->Cell(0, 4, 'OFFICIAL STAMP IS REQUIRED FOR VALIDATION', 0, 1, 'C');

        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(75, 85, 95);
        $this->MultiCell(
            190,
            3.4,
            'This invoice is valid for official purposes only when bearing the authorised stamp of the issuing company. ' .
            'Any copy presented without the official company stamp must not be accepted or processed.',
            0,
            'C'
        );

        $this->SetY(-7);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(130, 140, 150);
        $this->Cell(0, 4, 'Thank you for entrusting us with your service requirements.  |  Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SectionHeading($label) {
        $this->Ln(3);
        $this->SetFillColor(20, 35, 60);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(190, 7, '  ' . jobInvoiceText($label), 0, 1, 'L', true);
        $this->SetTextColor(35, 45, 55);
    }

    function NbLines($width, $text): int {
        $cw = $this->CurrentFont['cw'];
        if ($width == 0) {
            $width = $this->w - $this->rMargin - $this->x;
        }
        $maxWidth = ($width - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $text = str_replace("\r", '', (string)$text);
        $length = strlen($text);
        if ($length > 0 && $text[$length - 1] === "\n") $length--;

        $separator = -1;
        $i = 0;
        $j = 0;
        $lineWidth = 0;
        $lines = 1;
        while ($i < $length) {
            $char = $text[$i];
            if ($char === "\n") {
                $i++;
                $separator = -1;
                $j = $i;
                $lineWidth = 0;
                $lines++;
                continue;
            }
            if ($char === ' ') $separator = $i;
            $lineWidth += $cw[$char] ?? 500;
            if ($lineWidth > $maxWidth) {
                if ($separator === -1) {
                    if ($i === $j) $i++;
                } else {
                    $i = $separator + 1;
                }
                $separator = -1;
                $j = $i;
                $lineWidth = 0;
                $lines++;
            } else {
                $i++;
            }
        }
        return $lines;
    }

    function ServiceTableHeader($columns, $headers) {
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(232, 238, 242);
        $this->SetTextColor(35, 48, 60);
        $this->SetDrawColor(218, 225, 230);
        foreach ($headers as $index => $heading) {
            $align = in_array($index, [1, 2], true) ? 'L' : 'C';
            $this->Cell($columns[$index], 8, $heading, 1, 0, $align, true);
        }
        $this->Ln();
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(45, 55, 65);
    }
}

$pdf = new JobServiceInvoicePDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

/* Issuing office and customer panels */
$pdf->SectionHeading('SERVICE PROVIDER & CUSTOMER DETAILS');
$panelY = $pdf->GetY() + 2;
$panelHeight = 35;

$pdf->SetFillColor(247, 249, 252);
$pdf->SetDrawColor(224, 230, 236);
$pdf->Rect(10, $panelY, 92, $panelHeight, 'DF');
$pdf->Rect(108, $panelY, 92, $panelHeight, 'DF');

$pdf->SetXY(14, $panelY + 3);
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetTextColor(18, 120, 110);
$pdf->Cell(84, 4, 'ISSUED BY', 0, 1, 'L');
$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(20, 35, 60);
$pdf->MultiCell(84, 4.2, jobInvoiceText($company['company_name'] ?? 'Service Provider'), 0, 'L');
$pdf->SetX(14);
$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(70, 80, 90);
$pdf->MultiCell(84, 3.8, jobInvoiceText($seller_location_address), 0, 'L');
$pdf->SetX(14);
$pdf->Cell(84, 3.8, 'Phone: ' . jobInvoiceText($company['company_mobile'] ?? ''), 0, 1, 'L');
$pdf->SetX(14);
$pdf->Cell(84, 3.8, 'Email: ' . jobInvoiceText($company['company_email'] ?? ''), 0, 1, 'L');

$pdf->SetXY(112, $panelY + 3);
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetTextColor(18, 120, 110);
$pdf->Cell(84, 4, 'BILLED TO', 0, 1, 'L');
$pdf->SetX(112);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(20, 35, 60);
$pdf->MultiCell(84, 4.2, jobInvoiceText($customer_name), 0, 'L');
$pdf->SetX(112);
$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(70, 80, 90);
$pdf->Cell(84, 3.8, 'Phone: ' . jobInvoiceText($customer_phone), 0, 1, 'L');
$pdf->SetX(112);
$pdf->MultiCell(84, 3.8, 'Email: ' . jobInvoiceText($customer_email), 0, 'L');

$pdf->SetY($panelY + $panelHeight + 3);
$pdf->SectionHeading('SERVICE DETAILS');

$columns = [10, 58, 34, 14, 36, 38];
$headers = ['#', 'SERVICE DESCRIPTION', 'SERVICE REF.', 'QTY', 'UNIT PRICE', 'LINE TOTAL'];
$pdf->ServiceTableHeader($columns, $headers);

$sn = 1;
foreach ($items as $item) {
    $description = jobInvoiceText($item['name'] ?? 'Service');
    $serviceRef = jobInvoiceText($item['service_number'] ?? '-');
    $qty = (int)($item['quantity'] ?? 0);
    $unitPrice = (float)($item['unit_price'] ?? 0);
    $lineTotal = (float)($item['total_price'] ?? ($qty * $unitPrice));

    $pdf->SetFont('Arial', '', 8);
    $descriptionLines = $pdf->NbLines($columns[1] - 4, $description);
    $referenceLines = $pdf->NbLines($columns[2] - 4, $serviceRef);
    $rowHeight = max(9, max($descriptionLines, $referenceLines) * 4.2 + 3);

    if ($pdf->GetY() + $rowHeight > 263) {
        $pdf->AddPage();
        $pdf->SectionHeading('SERVICE DETAILS - CONTINUED');
        $pdf->ServiceTableHeader($columns, $headers);
    }

    $x = 10;
    $y = $pdf->GetY();
    $rowFill = ($sn % 2 === 0) ? [247, 250, 251] : [255, 255, 255];
    $pdf->SetFillColor($rowFill[0], $rowFill[1], $rowFill[2]);
    $pdf->SetDrawColor(218, 225, 230);

    $cursorX = $x;
    foreach ($columns as $columnWidth) {
        $pdf->Rect($cursorX, $y, $columnWidth, $rowHeight, 'DF');
        $cursorX += $columnWidth;
    }

    $pdf->SetTextColor(45, 55, 65);
    $pdf->SetXY($x + 1, $y);
    $pdf->Cell($columns[0] - 2, $rowHeight, (string)$sn, 0, 0, 'C');

    $pdf->SetXY($x + $columns[0] + 2, $y + 1.5);
    $pdf->MultiCell($columns[1] - 4, 4.2, $description, 0, 'L');

    $pdf->SetXY($x + $columns[0] + $columns[1] + 2, $y + 1.5);
    $pdf->MultiCell($columns[2] - 4, 4.2, $serviceRef, 0, 'L');

    $valueX = $x + $columns[0] + $columns[1] + $columns[2];
    $pdf->SetXY($valueX, $y);
    $pdf->Cell($columns[3], $rowHeight, (string)$qty, 0, 0, 'C');
    $pdf->Cell($columns[4], $rowHeight, number_format($unitPrice, 2), 0, 0, 'R');
    $pdf->Cell($columns[5], $rowHeight, number_format($lineTotal, 2), 0, 0, 'R');
    $pdf->SetY($y + $rowHeight);
    $sn++;
}

/* Financial summary */
if ($pdf->GetY() + 48 > 263) {
    $pdf->AddPage();
}
$pdf->Ln(4);
$labelWidth = 152;
$valueWidth = 38;
$pdf->SetX(10);
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(80, 90, 100);
$pdf->Cell($labelWidth, 6, 'Service Subtotal', 0, 0, 'R');
$pdf->Cell($valueWidth, 6, jobInvoiceMoney($grand_total), 0, 1, 'R');
$pdf->SetX(10);
$pdf->Cell($labelWidth, 6, 'Discount (' . number_format($discount_percent, 2) . '%)', 0, 0, 'R');
$pdf->Cell($valueWidth, 6, '- ' . jobInvoiceMoney($discount_amount), 0, 1, 'R');

$pdf->SetX(10);
$pdf->SetFillColor(20, 35, 60);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($labelWidth, 10, 'TOTAL SERVICE CHARGES', 0, 0, 'R', true);
$pdf->Cell($valueWidth, 10, jobInvoiceMoney($final_total), 0, 1, 'R', true);

$pdf->SetTextColor(60, 70, 80);
$pdf->SetFont('Arial', '', 8);
$pdf->SetX(10);
$pdf->Cell($labelWidth, 6, 'Amount Received', 0, 0, 'R');
$pdf->Cell($valueWidth, 6, jobInvoiceMoney($amount_paid), 0, 1, 'R');
$pdf->SetX(10);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor($balance > 0 ? 170 : 18, $balance > 0 ? 80 : 92, $balance > 0 ? 55 : 84);
$pdf->Cell($labelWidth, 7, 'OUTSTANDING BALANCE', 0, 0, 'R');
$pdf->Cell($valueWidth, 7, jobInvoiceMoney($balance), 0, 1, 'R');

if ($payment_status === 'partial' && !empty($due_date)) {
    $pdf->SetX(10);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(80, 90, 100);
    $pdf->Cell(190, 6, 'Payment due date: ' . jobInvoiceText($due_date), 0, 1, 'R');
}

/* Service scope / notes */
if (!empty($job_note)) {
    if ($pdf->GetY() + 22 > 263) $pdf->AddPage();
    $pdf->SectionHeading('SERVICE SCOPE / NOTES');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(65, 75, 85);
    $pdf->MultiCell(190, 4.8, jobInvoiceText($job_note), 0, 'L');
}

/* Signatures and the physical company-stamp area */
if ($pdf->GetY() + 42 > 263) {
    $pdf->AddPage();
}
$pdf->Ln(5);
$boxY = $pdf->GetY();
$boxWidth = 60;
$boxHeight = 22;
$gap = 5;
$boxXs = [10, 10 + $boxWidth + $gap, 10 + ($boxWidth + $gap) * 2];
$boxTitles = ['SERVICE PROVIDER', 'OFFICIAL COMPANY STAMP', 'CUSTOMER ACKNOWLEDGEMENT'];
$boxHints = ['Authorised signature / date', 'Affix authorised stamp here', 'Name / signature / date'];

foreach ($boxXs as $index => $boxX) {
    $pdf->SetDrawColor($index === 1 ? 18 : 190, $index === 1 ? 120 : 200, $index === 1 ? 110 : 208);
    $pdf->SetLineWidth($index === 1 ? 0.6 : 0.3);
    $pdf->Rect($boxX, $boxY, $boxWidth, $boxHeight);
    $pdf->SetXY($boxX + 1, $boxY + 2);
    $pdf->SetFont('Arial', 'B', 6.5);
    $pdf->SetTextColor($index === 1 ? 18 : 55, $index === 1 ? 92 : 65, $index === 1 ? 84 : 75);
    $pdf->Cell($boxWidth - 2, 4, $boxTitles[$index], 0, 1, 'C');
    $pdf->SetXY($boxX + 2, $boxY + 12);
    $pdf->SetFont('Arial', 'I', 6.5);
    $pdf->SetTextColor(125, 135, 145);
    $pdf->MultiCell($boxWidth - 4, 3.5, $boxHints[$index], 0, 'C');
}
$pdf->SetY($boxY + $boxHeight + 2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->SetTextColor(18, 92, 84);
$pdf->Cell(190, 5, 'IMPORTANT: THIS INVOICE MUST BEAR THE OFFICIAL COMPANY STAMP TO BE ACCEPTED.', 0, 1, 'C');

$pdf->Output('F', $invoice_file);

/* ==========================================================================
   DISPATCH NOTIFICATIONS (SMS & EMAIL)
   ========================================================================== */

// 1. Send SMS Notification
$sms_company_name = $company['company_name'] ?? 'Our Store';
$sms_text = "Hello {$customer_name}, thank you for choosing our services. Service Invoice #{$invoice_number}. Total: NGN " . number_format($final_total, 2) . ", Paid: NGN " . number_format($amount_paid, 2) . ", Bal: NGN " . number_format($balance, 2) . ". - {$sms_company_name}";

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
    $mail->Body    = "Dear {$customer_name},\n\nThank you for choosing our services.\n\nPlease find attached your service-rendering invoice (Invoice No: {$invoice_number}) for the services provided by {$company['company_name']}. Please note that the invoice is valid for official purposes only when it bears the authorised company stamp.\n\nKind regards,\n{$company['company_name']} Team";
    $mail->addAttachment($invoice_file);
    $mail->send();

} catch (Exception $e) {
    $invoiceUrlJs = json_encode($invoice_relative, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    echo "<script>alert('Service job completed, but the invoice email could not be delivered.'); window.open({$invoiceUrlJs}, '_blank'); window.location.href='job-entries.php';</script>";
    exit();
}

$invoiceUrlJs = json_encode($invoice_relative, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
echo "<script>alert('Service job completed. The service invoice SMS and email have been sent successfully.'); window.open({$invoiceUrlJs}, '_blank'); window.location.href='job-entries.php';</script>";
exit();
?>