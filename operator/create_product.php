<?php
include('../config/db.php');

require '../vendor/fpdf/fpdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

date_default_timezone_set('Africa/Lagos');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $company_id    = $_POST['company_id'];
    $name          = trim($_POST['name']);
    $category_id   = $_POST['category_id'] ?? null;
    $description   = trim($_POST['description']);
    $cost_price    = $_POST['cost_price'];
    $selling_price = $_POST['selling_price'];
    $created_by    = $_SESSION['user_id'] ?? null;

    if (!$category_id) {
        echo "<script>alert('Category is required'); window.history.back();</script>";
        exit;
    }

    // GET CATEGORY
    $stmt = $pdo->prepare("
        SELECT name, unit_id, unit_type, stock_type 
        FROM product_categories 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$category_id, $company_id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        echo "<script>alert('Invalid category selected'); window.history.back();</script>";
        exit;
    }

    $unit_id    = $cat['unit_id'];
    $unit_type  = $cat['unit_type'];
    $stock_type = $cat['stock_type'];
    $category   = $cat['name'];

    // IMAGE UPLOAD
    if (isset($_FILES['featured_img']) && $_FILES['featured_img']['error'] === 0) {

        $file_tmp  = $_FILES['featured_img']['tmp_name'];
        $file_name = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '', $_FILES['featured_img']['name']);

        $target_dir = '../uploads/products/';

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file_path = $target_dir . $file_name;

        if (!move_uploaded_file($file_tmp, $file_path)) {
            echo "<script>alert('Image upload failed'); window.history.back();</script>";
            exit;
        }

    } else {
        echo "<script>alert('Product image required'); window.history.back();</script>";
        exit;
    }

    // SLUG
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND company_id = ?");
    $stmt->execute([$slug, $company_id]);

    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time();
    }

    // BARCODE
    $barcode = 'PRD' . rand(100000, 999999);

    try {

        $pdo->beginTransaction();

        // INSERT PRODUCT
        $stmt = $pdo->prepare("
            INSERT INTO products
            (company_id, name, slug, barcode, category_id, stock_type, unit_id, cost_price, selling_price, description, created_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        $stmt->execute([
            $company_id,
            $name,
            $slug,
            $barcode,
            $category_id,
            $stock_type,
            $unit_id,
            $cost_price,
            $selling_price,
            $description,
            $created_by
        ]);

        $product_id = $pdo->lastInsertId();

        // SAVE IMAGE
        $stmt = $pdo->prepare("
            INSERT INTO product_images 
            (product_id, image_path, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$product_id, $file_name]);

        // UNIT
        $stmt = $pdo->prepare("SELECT unit_name FROM units WHERE id = ?");
        $stmt->execute([$unit_id]);
        $unit_name = $stmt->fetchColumn() ?: 'N/A';

        // COMPANY
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        // CREATOR (USER + STAFF NAME)
        $stmt = $pdo->prepare("
            SELECT u.email, s.firstname, s.lastname
            FROM users u
            LEFT JOIN staffs s ON u.email = s.email
            WHERE u.id = ?
        ");
        $stmt->execute([$created_by]);
        $creator = $stmt->fetch(PDO::FETCH_ASSOC);

        $creator_email = $creator['email'] ?? '';
        $creator_name  = trim(($creator['firstname'] ?? '') . ' ' . ($creator['lastname'] ?? ''));

        if ($creator_name == '') {
            $creator_name = $creator_email;
        }

        // PRODUCT IMAGE PATH
        $product_image = $file_name;

        $pdo->commit();

        // CREATE DIRECTORY
        $catalogue_dir = '../uploads/products/catalogues/';
        if (!is_dir($catalogue_dir)) {
            mkdir($catalogue_dir, 0755, true);
        }

        $catalogue_file = $catalogue_dir . $slug . '.pdf';

        class ProductCataloguePDF extends FPDF {

            public $company;

            function Header() {

                if (!empty($this->company['company_logo'])) {

                    $logo = __DIR__ . '/../uploads/logos/' . $this->company['company_logo'];

                    if (file_exists($logo)) {
                        $this->Image($logo, 10, 10, 30);
                    }
                }

                $this->SetFont('Arial','B',18);
                $this->Cell(0,10,'PRODUCT CATALOGUE',0,1,'C');
                $this->Ln(5);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->Cell(0,10,'Ubamus Global | Product Catalogue | Page '.$this->PageNo(),0,0,'C');
            }
        }

        $pdf = new ProductCataloguePDF();
        $pdf->company = $company;

        $pdf->AddPage();

        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,strtoupper($name),0,1,'C');

        $pdf->Ln(5);

        // ================================
        // PRODUCT FEATURED IMAGE SECTION
        // ================================
        $imgPath = '../uploads/products/' . $product_image;
        
        $pdf->Ln(5);
        
        // LABEL
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,'PRODUCT FEATURED IMAGE',0,1,'C');
        $pdf->Ln(3);
        
        if (file_exists($imgPath)) {
        
            // Get page width
            $pageWidth = $pdf->GetPageWidth();
        
            // Max image width (safe margin)
            $maxWidth = 60;
        
            // Center image
            $x = ($pageWidth - $maxWidth) / 2;
        
            // Draw image with fixed safe size
            $pdf->Image($imgPath, $x, $pdf->GetY(), $maxWidth);
        
            // Move cursor AFTER image (important)
            $pdf->Ln(55);
        
        } else {
        
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0,8,'No product image available',0,1,'C');
            $pdf->Ln(5);
        }

        // PRODUCT INFO
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(0,8,'PRODUCT INFORMATION',0,1);

        $pdf->SetFont('Arial','',11);

        $pdf->Cell(50,8,'Category:',0,0);
        $pdf->Cell(0,8,$category,0,1);

        $pdf->Cell(50,8,'Unit:',0,0);
        $pdf->Cell(0,8,$unit_name,0,1);

        $pdf->Cell(50,8,'Stock Type:',0,0);
        $pdf->Cell(0,8,$stock_type,0,1);

        $pdf->Cell(50,8,'Barcode:',0,0);
        $pdf->Cell(0,8,$barcode,0,1);

        $pdf->Cell(50,8,'Cost Price:',0,0);
        $pdf->Cell(0,8,number_format($cost_price,2),0,1);

        $pdf->Cell(50,8,'Selling Price:',0,0);
        $pdf->Cell(0,8,number_format($selling_price,2),0,1);

        $pdf->Ln(5);

        // DESCRIPTION
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(0,8,'DESCRIPTION',0,1);

        $pdf->SetFont('Arial','',11);
        $pdf->MultiCell(0,6,$description ?: 'No description provided');

        $pdf->Ln(5);

        // CREATOR
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(0,8,'CREATED BY',0,1);

        $pdf->SetFont('Arial','',11);
        $pdf->Cell(50,8,'Name:',0,0);
        $pdf->Cell(0,8,$creator_name,0,1);

        $pdf->Cell(50,8,'Email:',0,0);
        $pdf->Cell(0,8,$creator_email,0,1);

        $pdf->Ln(5);

        // COMPANY
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(0,8,'COMPANY',0,1);

        $pdf->SetFont('Arial','',11);

        $pdf->Cell(50,8,'Name:',0,0);
        $pdf->Cell(0,8,$company['company_name'],0,1);

        $pdf->Cell(50,8,'Email:',0,0);
        $pdf->Cell(0,8,$company['company_email'],0,1);

        $pdf->Cell(50,8,'Phone:',0,0);
        $pdf->Cell(0,8,$company['company_mobile'],0,1);

        $pdf->MultiCell(0,6,'Address: '.$company['company_office_address']);

        // SAVE PDF
        $pdf->Output('F', $catalogue_file);

        // ================================
// SEND EMAIL WITH CATALOGUE PDF
// ================================


$mail = new PHPMailer(true);

try {

    // SMTP CONFIG (EDIT THIS)
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@ubamus.com';
    $mail->Password   = 'Zaida@25';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('support@ubamus.com', $company['company_name']);
    $mail->addAddress($company['company_email']); // Company admin

    $mail->Subject = "New Product Created - {$name}";

    $mail->Body = "
        Hello {$company['company_name']},

        A new product has been created in your system.

        Product Name: {$name}
        Category: {$category}
        Barcode: {$barcode}

        Please find the attached product catalogue PDF.

        Regards,
        Ubamus Global System
    ";

    // ATTACH PDF
    $mail->addAttachment($catalogue_file);

    $mail->send();

} catch (Exception $e) {
    // Do NOT break product creation if email fails
    error_log("Email failed: " . $mail->ErrorInfo);
}

        echo "<script>
        alert('Product created successfully + Catalogue generated');
        window.location.href='products.php';
    </script>";
    exit;

} catch (PDOException $e) {

    $pdo->rollBack();

    echo "<script>
        alert('Error: ".addslashes($e->getMessage())."');
        window.history.back();
    </script>";
    exit;
}
}
?>