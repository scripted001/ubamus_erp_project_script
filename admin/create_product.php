<?php

declare(strict_types=1);

date_default_timezone_set('Africa/Lagos');

/*
|--------------------------------------------------------------------------
| INCLUDES
|--------------------------------------------------------------------------
*/

include('../config/db.php');
include('../config/smtp.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

require '../vendor/fpdf/fpdf.php';

use PHPMailer\PHPMailer\PHPMailer;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function productError(string $message): void
{
    echo '<script>
        alert(' . json_encode($message) . ');
        window.history.back();
    </script>';
    exit;
}

function generateSlug(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug);
    $slug = trim((string)$slug, '-');

    return $slug !== '' ? $slug : 'product';
}

function getActiveDomain(): string
{
    $domain = trim((string)(
        $_GET['domain']
        ?? $_POST['domain']
        ?? $_SESSION['domain']
        ?? $_SERVER['HTTP_HOST']
        ?? ''
    ));

    if ($domain === '') {
        return 'example.com';
    }

    $domain = preg_replace('#^https?://#i', '', $domain);
    $domain = preg_replace('#^www\.#i', '', (string)$domain);
    $domain = explode('/', (string)$domain)[0];
    $domain = explode(':', (string)$domain)[0];

    return trim((string)$domain);
}

function getDomainAbbreviation(string $domain): string
{
    $name = preg_replace('/\.[^.]+$/', '', $domain);
    $name = strtoupper((string)preg_replace('/[^a-zA-Z]/', '', (string)$name));
    $abbr = substr($name, 0, 3);

    return $abbr !== '' ? $abbr : 'PRO';
}

function generateUniqueBarcode(PDO $pdo, string $prefix, int $companyId): string
{
    for ($i = 0; $i < 20; $i++) {
        $barcode = $prefix . random_int(100000, 999999);

        $stmt = $pdo->prepare("
            SELECT 1
            FROM products
            WHERE company_id = ?
              AND barcode = ?
            LIMIT 1
        ");
        $stmt->execute([$companyId, $barcode]);

        if (!$stmt->fetchColumn()) {
            return $barcode;
        }
    }

    throw new RuntimeException('Unable to generate a unique product barcode.');
}


/*
|--------------------------------------------------------------------------
| ONLY PROCESS POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| AUTHENTICATED COMPANY / USER
|--------------------------------------------------------------------------
|
| Do not trust company_id submitted by the browser. The active session
| determines which company owns this product.
|
*/

$company_id = (int)($_SESSION['company_id'] ?? 0);
$created_by = (int)($_SESSION['user_id'] ?? 0);

if ($company_id <= 0 || $created_by <= 0) {
    productError('Your session has expired. Please log in again.');
}


/*
|--------------------------------------------------------------------------
| COLLECT AND VALIDATE FORM DATA
|--------------------------------------------------------------------------
*/

$name = trim((string)($_POST['name'] ?? ''));
$category_id = (int)($_POST['category_id'] ?? 0);
$description = trim((string)($_POST['description'] ?? ''));

$costPriceRaw = trim((string)($_POST['cost_price'] ?? ''));
$sellingPriceRaw = trim((string)($_POST['selling_price'] ?? ''));

$identifier_tracking_raw = (string)($_POST['identifier_tracking'] ?? (($_POST['imei_tracking'] ?? '0') === '1' ? 'imei' : 'none'));

if ($name === '') {
    productError('Product name is required.');
}

if ($category_id <= 0) {
    productError('Category is required.');
}

if (
    $costPriceRaw === ''
    || !is_numeric($costPriceRaw)
    || !is_finite((float)$costPriceRaw)
) {
    productError('Enter a valid cost price.');
}

if (
    $sellingPriceRaw === ''
    || !is_numeric($sellingPriceRaw)
    || !is_finite((float)$sellingPriceRaw)
) {
    productError('Enter a valid selling price.');
}

$cost_price = (float)$costPriceRaw;
$selling_price = (float)$sellingPriceRaw;

if ($cost_price < 0 || $selling_price < 0) {
    productError('Product prices cannot be negative.');
}

if (!in_array($identifier_tracking_raw, ['none', 'imei', 'serial', 'vin'], true)) {
    productError('Invalid product identifier selection. Choose None, IMEI Number, Serial Number or VIN.');
}

$identifier_tracking = $identifier_tracking_raw;
$imei_tracking = ($identifier_tracking === 'imei') ? 1 : 0;


/*
|--------------------------------------------------------------------------
| LOAD COMPANY FROM AUTHENTICATED SESSION
|--------------------------------------------------------------------------
*/

try {
    $companyStmt = $pdo->prepare("
        SELECT
            id,
            company_name,
            company_logo,
            company_domain,
            company_office_address,
            company_email,
            company_mobile,
            company_customer_care,
            customer_care_password,
            company_support_email,
            support_email_password,
            status
        FROM companies
        WHERE id = ?
        LIMIT 1
    ");
    $companyStmt->execute([$company_id]);
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        productError('Your company account could not be found.');
    }

    $company_name = trim((string)($company['company_name'] ?? ''));
    if ($company_name === '') {
        $company_name = 'Company';
    }
} catch (Throwable $e) {
    error_log('Company lookup failed: ' . $e->getMessage());
    productError('Unable to load company information.');
}


/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE PRODUCT NAME
|--------------------------------------------------------------------------
*/

try {
    $duplicateStmt = $pdo->prepare("
        SELECT id
        FROM products
        WHERE company_id = ?
          AND LOWER(TRIM(name)) = LOWER(TRIM(?))
        LIMIT 1
    ");
    $duplicateStmt->execute([$company_id, $name]);

    if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
        productError('This product name already exists in your company. Please use a different product name.');
    }
} catch (Throwable $e) {
    error_log('Duplicate product check failed: ' . $e->getMessage());
    productError('Unable to verify the product name. Please try again.');
}


/*
|--------------------------------------------------------------------------
| LOAD AND VERIFY CATEGORY
|--------------------------------------------------------------------------
|
| IMEI, serial and VIN tracking are permitted only for Sales Only categories.
| The submitted toggle is not trusted without this server-side check.
|
*/

try {
    $stmt = $pdo->prepare("
        SELECT
            name,
            unit_id,
            unit_type,
            stock_type
        FROM product_categories
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");
    $stmt->execute([$category_id, $company_id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        productError('Invalid category selected.');
    }

    $unit_id = $cat['unit_id'];
    $unit_type = $cat['unit_type'];
    $stock_type = (string)$cat['stock_type'];
    $category = (string)$cat['name'];

    $normalizedStockType = strtolower(trim($stock_type));

    if ($identifier_tracking !== 'none' && $normalizedStockType !== 'sales only') {
        productError('IMEI, serial number or VIN tracking is only available for physical products in a Sales Only category.');
    }

    // Services cannot use individual unit identifiers.
    if ($normalizedStockType !== 'sales only') {
        $identifier_tracking = 'none';
        $imei_tracking = 0;
    }
} catch (Throwable $e) {
    error_log('Category lookup failed: ' . $e->getMessage());
    productError('Unable to load the selected category.');
}


/*
|--------------------------------------------------------------------------
| VALIDATE AND STAGE PRODUCT MEDIA
|--------------------------------------------------------------------------
| The featured image remains mandatory for compatibility with existing
| product pages. Additional images/videos are optional and are saved in
| product_media after the product record has been created.
*/

$target_dir = '../uploads/products/';
if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true) && !is_dir($target_dir)) {
    productError('Unable to create product upload directory.');
}

$uploadedFilePaths = [];
$file_path = '';
$file_name = '';

$featuredUpload = $_FILES['featured_img'] ?? null;
if (
    !is_array($featuredUpload)
    || (int)($featuredUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
) {
    productError('Product image is required.');
}

$featuredTmp = (string)($featuredUpload['tmp_name'] ?? '');
if ($featuredTmp === '' || !is_uploaded_file($featuredTmp)) {
    productError('Invalid image upload.');
}

$featuredInfo = @getimagesize($featuredTmp);
$featuredMime = (string)($featuredInfo['mime'] ?? '');
$featuredAllowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif'
];

if ($featuredInfo === false || !isset($featuredAllowed[$featuredMime])) {
    productError('The featured image must be a valid JPG, PNG or GIF file.');
}

if ((int)($featuredUpload['size'] ?? 0) > 10 * 1024 * 1024) {
    productError('The featured image must not exceed 10 MB.');
}

$file_name = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $featuredAllowed[$featuredMime];
$file_path = $target_dir . $file_name;

if (!move_uploaded_file($featuredTmp, $file_path)) {
    productError('Product image upload failed.');
}
$uploadedFilePaths[] = $file_path;

/* Normalize the optional multiple-file input. */
$additionalUploads = [];
if (isset($_FILES['product_media']) && is_array($_FILES['product_media']['name'] ?? null)) {
    $mediaInput = $_FILES['product_media'];
    $mediaCount = count($mediaInput['name']);

    if ($mediaCount > 20) {
        @unlink($file_path);
        productError('You can upload a maximum of 20 additional media files at once.');
    }

    for ($i = 0; $i < $mediaCount; $i++) {
        $error = (int)($mediaInput['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            foreach ($uploadedFilePaths as $savedPath) {
                @unlink($savedPath);
            }
            productError('One of the additional media files could not be uploaded.');
        }

        $tmp = (string)($mediaInput['tmp_name'][$i] ?? '');
        $size = (int)($mediaInput['size'][$i] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            foreach ($uploadedFilePaths as $savedPath) {
                @unlink($savedPath);
            }
            productError('An additional media file is invalid.');
        }

        if ($size <= 0 || $size > 100 * 1024 * 1024) {
            foreach ($uploadedFilePaths as $savedPath) {
                @unlink($savedPath);
            }
            productError('Each additional media file must be smaller than 100 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $mediaAllowed = [
            'image/jpeg'       => ['ext' => 'jpg',  'type' => 'image'],
            'image/png'        => ['ext' => 'png',  'type' => 'image'],
            'image/gif'        => ['ext' => 'gif',  'type' => 'image'],
            'image/webp'       => ['ext' => 'webp', 'type' => 'image'],
            'video/mp4'        => ['ext' => 'mp4',  'type' => 'video'],
            'video/webm'       => ['ext' => 'webm', 'type' => 'video'],
            'video/quicktime'  => ['ext' => 'mov',  'type' => 'video']
        ];

        if (!isset($mediaAllowed[$mime])) {
            foreach ($uploadedFilePaths as $savedPath) {
                @unlink($savedPath);
            }
            productError('Additional media must be JPG, PNG, GIF, WEBP, MP4, WEBM or MOV.');
        }

        if ($mediaAllowed[$mime]['type'] === 'image' && @getimagesize($tmp) === false) {
            foreach ($uploadedFilePaths as $savedPath) {
                @unlink($savedPath);
            }
            productError('An uploaded image is not a valid image file.');
        }

        $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $mediaAllowed[$mime]['ext'];
        $storedPath = $target_dir . $storedName;

        if (!move_uploaded_file($tmp, $storedPath)) {
            foreach ($uploadedFilePaths as $savedPath) {
                @unlink($savedPath);
            }
            productError('Unable to save one of the additional media files.');
        }

        $uploadedFilePaths[] = $storedPath;
        $additionalUploads[] = [
            'media_path' => $storedName,
            'media_type' => $mediaAllowed[$mime]['type'],
            'mime_type' => $mime,
            'file_size' => $size,
            'media_name' => substr(basename((string)($mediaInput['name'][$i] ?? $storedName)), 0, 255)
        ];
    }
}


/*
|--------------------------------------------------------------------------
| GENERATE PRODUCT SLUG
|--------------------------------------------------------------------------
*/

$slug = generateSlug($name);
$baseSlug = $slug;
$counter = 1;

try {
    while (true) {
        $slugStmt = $pdo->prepare("
            SELECT id
            FROM products
            WHERE company_id = ?
              AND slug = ?
            LIMIT 1
        ");
        $slugStmt->execute([$company_id, $slug]);

        if (!$slugStmt->fetch(PDO::FETCH_ASSOC)) {
            break;
        }

        $counter++;
        $slug = $baseSlug . '-' . $counter;

        if ($counter > 1000) {
            throw new RuntimeException('Unable to generate a unique product URL.');
        }
    }
} catch (Throwable $e) {
    foreach ($uploadedFilePaths as $savedPath) {
        @unlink($savedPath);
    }
    error_log('Product slug generation failed: ' . $e->getMessage());
    productError('Unable to generate a unique product URL.');
}


/*
|--------------------------------------------------------------------------
| GENERATE BARCODE
|--------------------------------------------------------------------------
*/

try {
    $barcode = generateUniqueBarcode(
        $pdo,
        getDomainAbbreviation(getActiveDomain()),
        $company_id
    );
} catch (Throwable $e) {
    foreach ($uploadedFilePaths as $savedPath) {
        @unlink($savedPath);
    }
    error_log('Barcode generation failed: ' . $e->getMessage());
    productError('Unable to generate a unique product barcode.');
}


/*
|--------------------------------------------------------------------------
| CREATE PRODUCT AND SAVE PRODUCT MEDIA RECORDS
|--------------------------------------------------------------------------
*/

$transactionStarted = false;
$product_id = 0;

try {
    $pdo->beginTransaction();
    $transactionStarted = true;

    // Final duplicate check inside the transaction.
    $duplicateStmt = $pdo->prepare("
        SELECT id
        FROM products
        WHERE company_id = ?
          AND LOWER(TRIM(name)) = LOWER(TRIM(?))
        LIMIT 1
    ");
    $duplicateStmt->execute([$company_id, $name]);

    if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('This product name already exists in your company.');
    }

    /*
     * IMPORTANT:
     * Identifier tracking is a product-level setting.
     * Individual IMEIs, serial numbers and VINs are registered later
     * during stock-in for each physical unit received.
     */
    $stmt = $pdo->prepare("
        INSERT INTO products
        (
            company_id,
            name,
            slug,
            barcode,
            category_id,
            stock_type,
            unit_id,
            cost_price,
            selling_price,
            description,
            imei_tracking,
            identifier_tracking,
            created_at,
            created_by
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?
        )
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
        $imei_tracking,
        $identifier_tracking,
        $created_by
    ]);

    $product_id = (int)$pdo->lastInsertId();

    $imageStmt = $pdo->prepare("
        INSERT INTO product_images
        (
            product_id,
            image_path,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            NOW()
        )
    ");
    $imageStmt->execute([$product_id, $file_name]);

    // Keep the featured image in the legacy table and also register it
    // in the unified media table for the upgraded product-media interface.
    $mediaStmt = $pdo->prepare("
        INSERT INTO product_media
        (
            company_id,
            product_id,
            media_type,
            media_path,
            thumbnail_path,
            media_name,
            mime_type,
            file_size,
            display_order,
            is_primary,
            legacy_image_id,
            created_by,
            created_at,
            updated_at
        )
        VALUES (?, ?, 'image', ?, NULL, ?, ?, ?, 0, 1, NULL, ?, NOW(), NOW())
    ");
    $mediaStmt->execute([
        $company_id,
        $product_id,
        $file_name,
        $name . ' - Featured image',
        $featuredMime,
        (int)($featuredUpload['size'] ?? 0),
        $created_by
    ]);

    // Save optional additional images and videos.
    if ($additionalUploads !== []) {
        $additionalMediaStmt = $pdo->prepare("
            INSERT INTO product_media
            (
                company_id,
                product_id,
                media_type,
                media_path,
                thumbnail_path,
                media_name,
                mime_type,
                file_size,
                display_order,
                is_primary,
                legacy_image_id,
                created_by,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, 0, NULL, ?, NOW(), NOW())
        ");

        $displayOrder = 1;
        foreach ($additionalUploads as $media) {
            $additionalMediaStmt->execute([
                $company_id,
                $product_id,
                $media['media_type'],
                $media['media_path'],
                $media['media_name'],
                $media['mime_type'],
                $media['file_size'],
                $displayOrder++,
                $created_by
            ]);
        }
    }

    $pdo->commit();
    $transactionStarted = false;

} catch (PDOException $e) {
    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ($uploadedFilePaths as $savedPath) {
        @unlink($savedPath);
    }

    if (
        stripos($e->getMessage(), 'Duplicate entry') !== false
        || stripos($e->getMessage(), 'unique_company_product_name') !== false
    ) {
        productError('This product name already exists in your company.');
    }

    error_log('Product database error: ' . $e->getMessage());
    productError('Product could not be created. Please try again.');

} catch (Throwable $e) {
    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ($uploadedFilePaths as $savedPath) {
        @unlink($savedPath);
    }

    error_log(
        'Product creation error: '
        . $e->getMessage()
        . ' | File: ' . $e->getFile()
        . ' | Line: ' . $e->getLine()
    );

    productError($e->getMessage());
}


/*
|--------------------------------------------------------------------------
| GET UNIT INFORMATION
|--------------------------------------------------------------------------
*/

try {
    $stmt = $pdo->prepare("
        SELECT unit_name
        FROM units
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");
    $stmt->execute([$unit_id, $company_id]);
    $unit_name = $stmt->fetchColumn() ?: 'N/A';
} catch (Throwable $e) {
    $unit_name = 'N/A';
    error_log('Unit lookup failed: ' . $e->getMessage());
}


/*
|--------------------------------------------------------------------------
| GET CREATOR INFORMATION
|--------------------------------------------------------------------------
*/

try {
    $stmt = $pdo->prepare("
        SELECT
            u.email,
            s.firstname,
            s.lastname
        FROM users u
        LEFT JOIN staffs s
            ON u.email = s.email
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$created_by]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $creator_email = (string)($creator['email'] ?? '');
    $creator_name = trim(
        (string)($creator['firstname'] ?? '') . ' '
        . (string)($creator['lastname'] ?? '')
    );

    if ($creator_name === '') {
        $creator_name = $creator_email;
    }
} catch (Throwable $e) {
    $creator_email = '';
    $creator_name = '';
    error_log('Creator lookup failed: ' . $e->getMessage());
}


/*
|--------------------------------------------------------------------------
| GENERATE PRODUCT CATALOGUE PDF
|--------------------------------------------------------------------------
|
| PDF generation failure does not undo the saved product.
|
*/

$catalogue_file = null;

try {
    $catalogue_dir = '../uploads/products/catalogues/';

    if (!is_dir($catalogue_dir) && !mkdir($catalogue_dir, 0755, true) && !is_dir($catalogue_dir)) {
        throw new RuntimeException('Unable to create catalogue directory.');
    }

    $catalogue_file = $catalogue_dir . $slug . '.pdf';

    class ProductCataloguePDF extends FPDF
    {
        public array $company = [];

        public function Header()
        {
            if (!empty($this->company['company_logo'])) {
                $logo = __DIR__ . '/../uploads/logos/' . $this->company['company_logo'];

                if (file_exists($logo)) {
                    try {
                        $this->Image($logo, 10, 10, 30);
                    } catch (Throwable $e) {
                        error_log('PDF logo error: ' . $e->getMessage());
                    }
                }
            }

            $this->SetFont('Arial', 'B', 18);
            $this->Cell(0, 10, 'PRODUCT CATALOGUE', 0, 1, 'C');
            $this->Ln(5);
        }

        public function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);

            $footerCompanyName = $this->company['company_name'] ?? 'Company';

            $this->Cell(
                0,
                10,
                $footerCompanyName . ' | Product Catalogue | Page ' . $this->PageNo(),
                0,
                0,
                'C'
            );
        }
    }

    $pdf = new ProductCataloguePDF();
    $pdf->company = $company;
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, strtoupper($name), 0, 1, 'C');
    $pdf->Ln(5);

    $imgPath = '../uploads/products/' . $file_name;

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'PRODUCT FEATURED IMAGE', 0, 1, 'C');
    $pdf->Ln(3);

    if (file_exists($imgPath)) {
        try {
            $pageWidth = $pdf->GetPageWidth();
            $maxWidth = 60;
            $x = ($pageWidth - $maxWidth) / 2;

            $pdf->Image($imgPath, $x, $pdf->GetY(), $maxWidth);
            $pdf->Ln(55);
        } catch (Throwable $e) {
            error_log('Product image PDF error: ' . $e->getMessage());
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 8, 'Product image could not be displayed.', 0, 1, 'C');
            $pdf->Ln(5);
        }
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'No product image available', 0, 1, 'C');
        $pdf->Ln(5);
    }

    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'PRODUCT INFORMATION', 0, 1);

    $pdf->SetFont('Arial', '', 11);

    $pdf->Cell(50, 8, 'Category:', 0, 0);
    $pdf->Cell(0, 8, $category, 0, 1);

    $pdf->Cell(50, 8, 'Unit:', 0, 0);
    $pdf->Cell(0, 8, $unit_name, 0, 1);

    $pdf->Cell(50, 8, 'Stock Type:', 0, 0);
    $pdf->Cell(0, 8, $stock_type, 0, 1);

    $pdf->Cell(50, 8, 'Barcode:', 0, 0);
    $pdf->Cell(0, 8, $barcode, 0, 1);

    $pdf->Cell(50, 8, 'Cost Price:', 0, 0);
    $pdf->Cell(0, 8, number_format($cost_price, 2), 0, 1);

    $pdf->Cell(50, 8, 'Selling Price:', 0, 0);
    $pdf->Cell(0, 8, number_format($selling_price, 2), 0, 1);

    $trackingLabels = [
        'none' => 'None (quantity-based)',
        'imei' => 'IMEI Number',
        'serial' => 'Serial Number',
        'vin' => 'Vehicle Identification Number (VIN)'
    ];
    $pdf->Cell(50, 8, 'Identifier Tracking:', 0, 0);
    $pdf->Cell(0, 8, $trackingLabels[$identifier_tracking] ?? 'None', 0, 1);

    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'DESCRIPTION', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 6, $description !== '' ? $description : 'No description provided');

    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'CREATED BY', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(50, 8, 'Name:', 0, 0);
    $pdf->Cell(0, 8, $creator_name, 0, 1);

    $pdf->Cell(50, 8, 'Email:', 0, 0);
    $pdf->Cell(0, 8, $creator_email, 0, 1);

    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 8, 'COMPANY', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(50, 8, 'Name:', 0, 0);
    $pdf->Cell(0, 8, $company_name, 0, 1);

    $pdf->Cell(50, 8, 'Email:', 0, 0);
    $pdf->Cell(0, 8, (string)($company['company_email'] ?? ''), 0, 1);

    $pdf->Cell(50, 8, 'Phone:', 0, 0);
    $pdf->Cell(0, 8, (string)($company['company_mobile'] ?? ''), 0, 1);

    $pdf->MultiCell(0, 6, 'Address: ' . (string)($company['company_office_address'] ?? ''));

    $pdf->Output('F', $catalogue_file);

} catch (Throwable $e) {
    $catalogue_file = null;

    error_log(
        'Product catalogue PDF failed: '
        . $e->getMessage()
        . ' | File: ' . $e->getFile()
        . ' | Line: ' . $e->getLine()
    );
}


/*
|--------------------------------------------------------------------------
| SEND EMAIL WITH CATALOGUE
|--------------------------------------------------------------------------
|
| Email failure does not affect product creation.
|
*/

if ($catalogue_file !== null && file_exists($catalogue_file)) {
    try {
        $smtp_username = (string)($_SESSION['customercare'] ?? '');
        $smtp_password = (string)($_SESSION['customer_care_password'] ?? '');

        if (
            $smtp_username !== ''
            && $smtp_password !== ''
            && !empty($company['company_email'])
        ) {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->Timeout = 10;
            $mail->SMTPKeepAlive = false;

            $mail->setFrom($smtp_username, $company_name);
            $mail->addAddress((string)$company['company_email']);
            $mail->Subject = "New Product Created - {$name}";

            $trackingLabels = [
                'none' => 'None (quantity-based)',
                'imei' => 'IMEI Number',
                'serial' => 'Serial Number',
                'vin' => 'Vehicle Identification Number (VIN)'
            ];
            $trackingText = $trackingLabels[$identifier_tracking] ?? 'None';

            $mail->Body =
                "Hello " . $company_name . ",\n\n"
                . "A new product has been created in your system.\n\n"
                . "Product Name: {$name}\n"
                . "Category: {$category}\n"
                . "Barcode: {$barcode}\n"
                . "Identifier Tracking: {$trackingText}\n\n"
                . "Please find the attached product catalogue PDF.\n\n"
                . "Regards,\n"
                . $company_name . " Global System";

            $mail->addAttachment($catalogue_file);
            $mail->send();
        }
    } catch (Throwable $e) {
        error_log('Product email failed: ' . $e->getMessage());
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

$successMessage = 'Product created successfully.';

if ($catalogue_file !== null) {
    $successMessage .= ' Catalogue generated.';
}

echo '<script>
    alert(' . json_encode($successMessage) . ');
    window.location.href = "products.php";
</script>';

exit;

?>
