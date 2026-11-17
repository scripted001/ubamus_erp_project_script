
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../config/db.php');
include('user_data.php');

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

date_default_timezone_set('Africa/Lagos');

// =====================================================
// HELPER
// =====================================================

function vendorRedirect(string $message, string $url = 'existing_suppliers.php'): void
{
    echo '<script>';
    echo 'alert(' . json_encode($message) . ');';
    echo 'window.location.href = ' . json_encode($url) . ';';
    echo '</script>';
    exit;
}

function vendorPost(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

// =====================================================
// 1. REQUEST VALIDATION
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request.');
}

$company_id  = (int)($_SESSION['company_id'] ?? 0);
$user_id     = (int)($_SESSION['user_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);

if ($company_id <= 0 || $user_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Unauthorized access.');
}

// =====================================================
// 2. COLLECT FORM DATA
// =====================================================

$business_name  = vendorPost('business_name');
$office_address = vendorPost('office_address');
$email          = vendorPost('email');
$mobile         = vendorPost('mobile');
$description    = vendorPost('description');

// The form's "location" field describes the vendor's
// physical location. It is NOT the Ubamus location_id.
//
// The existing vendors table has no vendor physical
// location column, so this value is not inserted.

if (
    $business_name === '' ||
    $office_address === '' ||
    $email === '' ||
    $mobile === ''
) {
    vendorRedirect('Please complete all required vendor fields.', 'new_suppliers.php');
}

// =====================================================
// 3. FIELD LENGTH VALIDATION
// =====================================================

$fields = [
    'Business name'   => [$business_name, 100],
    'Office address'  => [$office_address, 100],
    'Email'           => [$email, 100],
    'Mobile'          => [$mobile, 100],
    'Description'     => [$description, 255]
];

foreach ($fields as $label => [$value, $maximum]) {
    if (strlen($value) > $maximum) {
        vendorRedirect(
            $label . ' cannot exceed ' . $maximum . ' characters.',
            'new_suppliers.php'
        );
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    vendorRedirect('Please enter a valid email address.', 'new_suppliers.php');
}

// =====================================================
// 4. VERIFY ASSIGNED BRANCH
// =====================================================

$locationStmt = $pdo->prepare("
    SELECT id, name
    FROM locations
    WHERE id = :location_id
      AND company_id = :company_id
    LIMIT 1
");

$locationStmt->execute([
    ':location_id' => $location_id,
    ':company_id'  => $company_id
]);

$branch = $locationStmt->fetch(PDO::FETCH_ASSOC);

if (!$branch) {
    http_response_code(403);
    exit('Access denied. Your assigned branch could not be verified.');
}

$branch_name = $branch['name'];

// =====================================================
// 5. VERIFY LOGGED-IN USER
// =====================================================

$userStmt = $pdo->prepare("
    SELECT email, username
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStmt->execute([$user_id]);

$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    exit('User account could not be verified.');
}

$created_by = $user['email'] ?? 'Unknown';
$added_by_username = $user['username'] ?? 'Unknown';

// =====================================================
// 6. CHECK DUPLICATE VENDOR
// =====================================================
//
// Duplicate checking is restricted to this company
// and this manager's assigned location.
//

$duplicateStmt = $pdo->prepare("
    SELECT id
    FROM vendors
    WHERE company_id = :company_id
      AND location_id = :location_id
      AND business_name = :business_name
      AND office_address = :office_address
    LIMIT 1
");

$duplicateStmt->execute([
    ':company_id'     => $company_id,
    ':location_id'    => $location_id,
    ':business_name'  => $business_name,
    ':office_address' => $office_address
]);

if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
    vendorRedirect(
        'This vendor already exists in your branch.',
        'existing_suppliers.php'
    );
}

// =====================================================
// 7. INSERT VENDOR
// =====================================================

$created_at = date('Y-m-d H:i:s');
$status = 'active';

try {

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO vendors (
            company_id,
            location_id,
            business_name,
            office_address,
            email,
            mobile,
            description,
            created_by,
            created_at,
            status
        ) VALUES (
            :company_id,
            :location_id,
            :business_name,
            :office_address,
            :email,
            :mobile,
            :description,
            :created_by,
            :created_at,
            :status
        )
    ");

    $insertStmt->execute([
        ':company_id'     => $company_id,
        ':location_id'    => $location_id,
        ':business_name'  => $business_name,
        ':office_address' => $office_address,
        ':email'          => $email,
        ':mobile'         => $mobile,
        ':description'    => $description,
        ':created_by'     => $created_by,
        ':created_at'     => $created_at,
        ':status'         => $status
    ]);

    $vendor_id = (int)$pdo->lastInsertId();

    $pdo->commit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Vendor registration error: ' . $e->getMessage());

    vendorRedirect(
        'Vendor registration failed. Please try again.',
        'new_suppliers.php'
    );
}

// =====================================================
// 8. FETCH COMPANY DETAILS
// =====================================================

$companyStmt = $pdo->prepare("
    SELECT company_name, company_email
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$companyStmt->execute([$company_id]);

$company = $companyStmt->fetch(PDO::FETCH_ASSOC);

$company_name  = $company['company_name'] ?? 'Your Company';
$company_email = $company['company_email'] ?? '';

// =====================================================
// 9. EMAIL NOTIFICATION
// =====================================================

$mailSent = false;

// Configure these securely in your server environment.
$smtpHost     = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
$smtpPort     = (int)(getenv('SMTP_PORT') ?: 587);
$smtpUsername = getenv('SMTP_USERNAME') ?: '';
$smtpPassword = getenv('SMTP_PASSWORD') ?: '';

if (
    filter_var($company_email, FILTER_VALIDATE_EMAIL) &&
    $smtpUsername !== '' &&
    $smtpPassword !== ''
) {

    try {

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpUsername, $company_name);
        $mail->addAddress($company_email);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $safe = static function ($value): string {
            return htmlspecialchars(
                (string)$value,
                ENT_QUOTES,
                'UTF-8'
            );
        };

        $mail->Subject = 'New Vendor Registered: ' . $business_name;

        $mail->Body = "
            <h2>New Vendor Registered</h2>

            <p><strong>Company:</strong> {$safe($company_name)}</p>
            <p><strong>Vendor Business Name:</strong> {$safe($business_name)}</p>
            <p><strong>Email:</strong> {$safe($email)}</p>
            <p><strong>Phone:</strong> {$safe($mobile)}</p>
            <p><strong>Office Address:</strong> {$safe($office_address)}</p>
            <p><strong>Description:</strong> {$safe($description)}</p>
            <p><strong>Date Added:</strong> {$safe($created_at)}</p>
            <p><strong>Added By:</strong> {$safe($added_by_username)}</p>
            <p><strong>Ubamus Branch:</strong> {$safe($branch_name)}</p>
        ";

        $mail->AltBody =
            "New vendor registered: {$business_name}\n" .
            "Email: {$email}\n" .
            "Phone: {$mobile}\n" .
            "Ubamus Branch: {$branch_name}";

        $mail->send();

        $mailSent = true;

    } catch (Exception $e) {

        error_log(
            'Vendor notification email error: ' .
            ($mail->ErrorInfo ?? $e->getMessage())
        );

    } catch (Throwable $e) {

        error_log('Vendor notification error: ' . $e->getMessage());
    }

} else {

    error_log('Vendor email skipped: SMTP or company email configuration missing.');
}

// =====================================================
// 10. FINAL RESPONSE
// =====================================================

if ($mailSent) {
    vendorRedirect(
        'Vendor registered successfully and notification email sent.',
        'existing_suppliers.php'
    );
}

vendorRedirect(
    'Vendor registered successfully, but notification email could not be sent.',
    'existing_suppliers.php'
);

?>
