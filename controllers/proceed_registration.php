<?php

// ============================================================
// SESSION
// ============================================================

session_start();


// ============================================================
// REQUIRED FILES
// ============================================================

include('../config/db.php');
include('../config/smtp.php');
include('../company_data.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';


// ============================================================
// HELPER FUNCTIONS
// ============================================================

function clean($value)
{
    return trim(strip_tags((string)$value));
}


function normaliseDomain($domain)
{
    $domain = trim($domain);

    if ($domain === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $domain)) {
        $domain = 'https://' . $domain;
    }

    return $domain;
}


function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ============================================================
// REQUEST VALIDATION
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    exit('Invalid request.');

}


// ============================================================
// GET FORM DATA
// ============================================================

$companyName = clean(
    $_POST['company_name'] ?? ''
);

$companySlug = clean(
    $_POST['company_slug'] ?? ''
);

$companyIndustry = clean(
    $_POST['company_industry'] ?? ''
);

$companyDomain = normaliseDomain(
    clean(
        $_POST['company_domain'] ?? ''
    )
);


$companyEmail = clean(
    $_POST['company_email'] ?? ''
);

$companyMobile = clean(
    $_POST['company_mobile'] ?? ''
);

$companyOfficeAddress = clean(
    $_POST['company_office_address'] ?? ''
);

$companyState = clean(
    $_POST['company_state'] ?? ''
);

$companyCity = clean(
    $_POST['company_city'] ?? ''
);

$companyHeadOffice = clean(
    $_POST['company_head_office'] ?? ''
);


$companyCustomerCare = clean(
    $_POST['company_customer_care'] ?? ''
);

$companySupportEmail = clean(
    $_POST['company_support_email'] ?? ''
);

$customerCarePassword = (string)(
    $_POST['customer_care_password'] ?? ''
);

$supportEmailPassword = (string)(
    $_POST['support_email_password'] ?? ''
);


// ============================================================
// VALIDATE REQUIRED FIELDS
// ============================================================

if (
    empty($companyName) ||
    empty($companySlug) ||
    empty($companyIndustry) ||
    empty($companyDomain) ||
    empty($companyEmail) ||
    empty($companyMobile) ||
    empty($companyOfficeAddress) ||
    empty($companyState) ||
    empty($companyCity) ||
    empty($companyHeadOffice) ||
    empty($companyCustomerCare) ||
    empty($companySupportEmail) ||
    empty($customerCarePassword) ||
    empty($supportEmailPassword)
) {

    exit('Please complete all required registration fields.');
    echo $companyName;
    
    

}


// ============================================================
// VALIDATE EMAIL ADDRESSES
// ============================================================

if (!filter_var(
    $companyEmail,
    FILTER_VALIDATE_EMAIL
)) {

    exit('Please enter a valid company email address.');

}


if (!filter_var(
    $companySupportEmail,
    FILTER_VALIDATE_EMAIL
)) {

    exit('Please enter a valid support email address.');

}


// ============================================================
// VALIDATE COMPANY DOMAIN
// ============================================================

if (!filter_var(
    $companyDomain,
    FILTER_VALIDATE_URL
)) {

    exit('Please enter a valid company domain.');

}


// ============================================================
// VALIDATE COMPANY SLUG
// ============================================================

if (!preg_match(
    '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
    $companySlug
)) {

    exit(
        'Company slug can only contain lowercase letters, numbers and hyphens.'
    );

}


// ============================================================
// VALIDATE PASSWORD LENGTH
// ============================================================

if (strlen($customerCarePassword) < 8) {

    exit(
        'Customer-care password must contain at least 8 characters.'
    );

}


if (strlen($supportEmailPassword) < 8) {

    exit(
        'Support email password must contain at least 8 characters.'
    );

}


// ============================================================
// COMPANY ID
// ============================================================

$companyId =
    'REG-' .
    date('YmdHis') .
    '-' .
    strtoupper(
        substr(
            bin2hex(random_bytes(3)),
            0,
            6
        )
    );


// ============================================================
// PASSWORD STATUS
// ============================================================

$carePasswordStatus =
    !empty($customerCarePassword)
        ? 'Provided'
        : 'Not provided';


$supportPasswordStatus =
    !empty($supportEmailPassword)
        ? 'Provided'
        : 'Not provided';


// ============================================================
// COMPANY LOGO VALIDATION
// ============================================================

if (
    !isset($_FILES['company_logo']) ||
    $_FILES['company_logo']['error'] !== UPLOAD_ERR_OK
) {

    exit(
        'Please upload a valid company logo.'
    );

}


$logo = $_FILES['company_logo'];


// Maximum 2MB

$maxLogoSize = 2 * 1024 * 1024;


if ($logo['size'] > $maxLogoSize) {

    exit(
        'Company logo must not exceed 2MB.'
    );

}


// Allowed MIME types

$allowedLogoTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];


$finfo = finfo_open(
    FILEINFO_MIME_TYPE
);


$detectedLogoType = finfo_file(
    $finfo,
    $logo['tmp_name']
);


finfo_close($finfo);


if (!in_array(
    $detectedLogoType,
    $allowedLogoTypes,
    true
)) {

    exit(
        'Invalid company logo format. Please upload PNG, JPG, JPEG or WEBP.'
    );

}


// ============================================================
// COMPANY SMTP DETAILS
// ============================================================

$smtp_username =
    $_SESSION['customercare'] ?? '';

$smtp_password =
    $_SESSION['customer_care_password'] ?? '';


// ------------------------------------------------------------
// CHECK SMTP CREDENTIALS
// ------------------------------------------------------------

if (
    empty($smtp_username) ||
    empty($smtp_password)
) {

    error_log(
        'Company registration: SMTP credentials are missing from session.'
    );

    exit(
        'Email service is currently unavailable. Please try again later.'
    );

}


// ------------------------------------------------------------
// VALIDATE SMTP EMAIL
// ------------------------------------------------------------

if (!filter_var(
    $smtp_username,
    FILTER_VALIDATE_EMAIL
)) {

    error_log(
        'Company registration: Invalid SMTP email address.'
    );

    exit(
        'Email service configuration is invalid.'
    );

}


// ============================================================
// CREATE MAIL INSTANCE
// ============================================================

$mail = new PHPMailer(true);


try {

    // ========================================================
    // SMTP CONFIGURATION
    // ========================================================

    $mail->isSMTP();

    $mail->Host =
        'smtp.hostinger.com';

    $mail->SMTPAuth =
        true;

    $mail->Username =
        $smtp_username;

    $mail->Password =
        $smtp_password;

    $mail->SMTPSecure =
        'ssl';

    $mail->Port =
        465;

    $mail->CharSet =
        'UTF-8';


    // ========================================================
    // SENDER
    // ========================================================

    $mail->setFrom(
        $smtp_username,
        'UBAMUS Company Registration'
    );


    // ========================================================
    // RECIPIENT
    // ========================================================

    // Registration notification goes to the
    // configured customer-care mailbox.

    $mail->addAddress(
        $smtp_username,
        'UBAMUS Registration'
    );


    // ========================================================
    // REPLY TO COMPANY EMAIL
    // ========================================================

    $mail->addReplyTo(
        $companyEmail,
        $companyName
    );


    // ========================================================
    // ATTACH COMPANY LOGO
    // ========================================================

    $mail->addAttachment(
        $logo['tmp_name'],
        $logo['name'],
        PHPMailer::ENCODING_BASE64,
        $detectedLogoType
    );


    // ========================================================
    // EMAIL FORMAT
    // ========================================================

    $mail->isHTML(true);


    // ========================================================
    // SUBJECT
    // ========================================================

    $mail->Subject =
        'New Company Registration — ' .
        $companyName;


    // ========================================================
    // SAFE HTML VARIABLES
    // ========================================================

    $safeCompanyName =
        e($companyName);

    $safeCompanyId =
        e($companyId);

    $safeCompanySlug =
        e($companySlug);

    $safeCompanyIndustry =
        e($companyIndustry);

    $safeCompanyDomain =
        e($companyDomain);

    $safeCompanyEmail =
        e($companyEmail);

    $safeCompanyMobile =
        e($companyMobile);

    $safeCompanyOfficeAddress =
        e($companyOfficeAddress);

    $safeCompanyState =
        e($companyState);

    $safeCompanyCity =
        e($companyCity);

    $safeCompanyHeadOffice =
        nl2br(
            e($companyHeadOffice)
        );

    $safeCompanyCustomerCare =
        e($companyCustomerCare);

    $safeCompanySupportEmail =
        e($companySupportEmail);

    $safeLogoName =
        e($logo['name']);


    // ========================================================
    // HTML EMAIL
    // ========================================================

    $mail->Body = '

<!DOCTYPE html>

<html>

<body style="
    margin:0;
    padding:30px;
    background:#f1f5f9;
    font-family:Arial,sans-serif;
    color:#172033;
">

<div style="
    max-width:720px;
    margin:auto;
    background:#ffffff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    overflow:hidden;
    box-shadow:0 18px 50px rgba(15,23,42,.08);
">


<div style="
    padding:28px 30px;
    background:linear-gradient(
        135deg,
        #4f46e5,
        #7c3aed
    );
    color:#ffffff;
">

<div style="
    font-size:12px;
    letter-spacing:1.5px;
    text-transform:uppercase;
    opacity:.8;
">

UBAMUS COMPANY REGISTRATION

</div>


<h1 style="
    margin:8px 0 0;
    font-size:27px;
">

New Company Registration

</h1>

</div>


<div style="
    padding:30px;
">


<p style="
    font-size:15px;
    line-height:1.7;
    margin-top:0;
">

A new company has successfully submitted a registration request.
Below are the submitted company details.

</p>


<div style="
    margin:20px 0;
    padding:15px 18px;
    background:#eff6ff;
    border:1px solid #bfdbfe;
    border-radius:12px;
    color:#1e3a8a;
    font-size:14px;
">

<strong>Company Logo:</strong>

' . $safeLogoName . '

<br><br>

The company logo has been attached to this email.

</div>


<h2 style="
    font-size:17px;
    margin:28px 0 12px;
">

Company Identity

</h2>


<table style="
    width:100%;
    border-collapse:collapse;
    font-size:14px;
">


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Company Name</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyName . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Company ID</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyId . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Company Slug</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanySlug . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Industry</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyIndustry . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Domain</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyDomain . '

</td>

</tr>


</table>


<h2 style="
    font-size:17px;
    margin:28px 0 12px;
">

Contact & Location

</h2>


<table style="
    width:100%;
    border-collapse:collapse;
    font-size:14px;
">


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Company Email</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<a href="mailto:' . $safeCompanyEmail . '">

' . $safeCompanyEmail . '

</a>

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Mobile</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyMobile . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Office Address</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyOfficeAddress . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>State</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyState . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>City</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyCity . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Head Office Information</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyHeadOffice . '

</td>

</tr>


</table>


<h2 style="
    font-size:17px;
    margin:28px 0 12px;
">

Support Services

</h2>


<table style="
    width:100%;
    border-collapse:collapse;
    font-size:14px;
">


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Customer Care Contact</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . $safeCompanyCustomerCare . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Support Email</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<a href="mailto:' . $safeCompanySupportEmail . '">

' . $safeCompanySupportEmail . '

</a>

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

<strong>Customer Care Password</strong>

</td>

<td style="
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
">

' . e($carePasswordStatus) . '

</td>

</tr>


<tr>

<td style="
    padding:10px 0;
">

<strong>Support Email Password</strong>

</td>

<td style="
    padding:10px 0;
">

' . e($supportPasswordStatus) . '

</td>

</tr>


</table>


<div style="
    margin-top:28px;
    padding:16px 18px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:14px;
    font-size:13px;
    line-height:1.6;
    color:#475569;
">

<strong>Registration Timestamp:</strong>

' . e(
    date(
        'Y-m-d H:i:s'
    )
) . '

(Africa/Lagos)

</div>


<p style="
    margin:28px 0 0;
    font-size:12px;
    color:#94a3b8;
">

This message was generated automatically by the
UBAMUS company registration system.

</p>


</div>

</div>

</body>

</html>

';


    // ========================================================
    // PLAIN TEXT EMAIL VERSION
    // ========================================================

    $mail->AltBody =

        "NEW COMPANY REGISTRATION\n\n" .

        "Company Name: " .
        $companyName . "\n" .

        "Company ID: " .
        $companyId . "\n" .

        "Company Slug: " .
        $companySlug . "\n" .

        "Industry: " .
        $companyIndustry . "\n" .

        "Domain: " .
        $companyDomain . "\n\n" .


        "CONTACT & LOCATION\n\n" .

        "Company Email: " .
        $companyEmail . "\n" .

        "Mobile: " .
        $companyMobile . "\n" .

        "Office Address: " .
        $companyOfficeAddress . "\n" .

        "State: " .
        $companyState . "\n" .

        "City: " .
        $companyCity . "\n" .

        "Head Office: " .
        $companyHeadOffice . "\n\n" .


        "SUPPORT SERVICES\n\n" .

        "Customer Care: " .
        $companyCustomerCare . "\n" .

        "Support Email: " .
        $companySupportEmail . "\n\n" .


        "Company Logo: " .
        $logo['name'] .
        " (attached to this email)\n\n" .


        "Customer Care Password: " .
        $carePasswordStatus . "\n" .

        "Support Email Password: " .
        $supportPasswordStatus . "\n";


    // ========================================================
    // SEND EMAIL
    // ========================================================

    $mail->send();


    // ========================================================
    // SUCCESS
    // ========================================================

    echo '
        <script>

            alert(
                "Company successfully registered and is now under review."
            );

            window.location.href =
                "../index.php";

        </script>
    ';

    exit;


} catch (Exception $e) {

    // Log the real error for debugging

    error_log(
        'Company registration email error: ' .
        $mail->ErrorInfo .
        ' | Exception: ' .
        $e->getMessage()
    );


    // User-friendly error

    echo '
        <script>

            alert(
                "Registration could not be completed because the email notification could not be sent. Please try again."
            );

            window.history.back();

        </script>
    ';

    exit;

}

?>
