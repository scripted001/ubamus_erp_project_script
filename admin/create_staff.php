
<?php


include('../config/db.php');

date_default_timezone_set('Africa/Lagos');

/*
|--------------------------------------------------------------------------
| RESPONSE HELPERS
|--------------------------------------------------------------------------
*/

function staffRedirect(string $message, string $destination = 'new_staff.php'): void
{
    $messageJson = json_encode(
        $message,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );

    $destinationJson = json_encode(
        $destination,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );

    echo "<script>
        alert($messageJson);
        window.location.href = $destinationJson;
    </script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| AUTHENTICATED SESSION
|--------------------------------------------------------------------------
*/

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$company_id = (int)($_SESSION['company_id'] ?? 0);

/*
 * This is the authoritative branch assignment.
 * Do not obtain it from POST.
 */
$location_id = (int)($_SESSION['location_id'] ?? 0);

if ($user_id <= 0 || $company_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    staffRedirect(
        'Your active company or branch session is missing. Please log in again.',
        'login.php'
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: new_staff.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| VERIFY AUTHENTICATED USER
|--------------------------------------------------------------------------
*/

try {

    $userStmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $userStmt->execute([$user_id]);

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        staffRedirect('Your user account could not be verified.', 'login.php');
    }

    /*
     * If the users table has company_id, verify that the authenticated
     * user belongs to the company in the active session.
     */
    if (
        isset($user['company_id']) &&
        (int)$user['company_id'] !== $company_id
    ) {
        http_response_code(403);
        staffRedirect('Your account does not belong to the active company.');
    }

    $created_by = $user['email'] ?? '';

    if ($created_by === '') {
        staffRedirect('Your account email could not be verified.');
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY COMPANY
    |--------------------------------------------------------------------------
    */

    $companyStmt = $pdo->prepare("
        SELECT id, company_industry
        FROM companies
        WHERE id = ?
        LIMIT 1
    ");

    $companyStmt->execute([$company_id]);

    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        staffRedirect('The active company could not be verified.');
    }

    $company_industry = $company['company_industry'] ?? null;

    /*
    |--------------------------------------------------------------------------
    | VERIFY ACTIVE BRANCH
    |--------------------------------------------------------------------------
    */

    $locationStmt = $pdo->prepare("
        SELECT id, name
        FROM locations
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");

    $locationStmt->execute([
        $location_id,
        $company_id
    ]);

    $location = $locationStmt->fetch(PDO::FETCH_ASSOC);

    if (!$location) {
        http_response_code(403);
        staffRedirect(
            'Your active branch is invalid or does not belong to this company.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SANITIZE FORM INPUT
    |--------------------------------------------------------------------------
    */

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $phone     = trim($_POST['phone'] ?? '');
    $role      = trim($_POST['role'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    $refree1name  = trim($_POST['refree1name'] ?? '');
    $refree1phone = trim($_POST['refree1phone'] ?? '');

    $refree2name  = trim($_POST['refree2name'] ?? '');
    $refree2phone = trim($_POST['refree2phone'] ?? '');

    /*
     * Never accept these values from POST:
     *
     * $_POST['company_id']
     * $_POST['branch_id']
     * $_POST['location_id']
     * $_POST['status']
     *
     * The company and branch are obtained from the authenticated session.
     */

    $status = 'active';

    /*
    |--------------------------------------------------------------------------
    | REQUIRED FIELD VALIDATION
    |--------------------------------------------------------------------------
    */

    $requiredFields = [
        'First name'          => $firstname,
        'Last name'           => $lastname,
        'Email address'       => $email,
        'Mobile number'       => $phone,
        'Staff role'           => $role,
        'Address'              => $address,
        'Primary referee name' => $refree1name,
        'Primary referee phone'=> $refree1phone,
        'Secondary referee name' => $refree2name,
        'Secondary referee phone'=> $refree2phone
    ];

    foreach ($requiredFields as $fieldName => $fieldValue) {
        if ($fieldValue === '') {
            staffRedirect($fieldName . ' is required.');
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        staffRedirect('Please enter a valid email address.');
    }

    if (
        strlen($firstname) > 100 ||
        strlen($lastname) > 100 ||
        strlen($email) > 190 ||
        strlen($phone) > 30 ||
        strlen($address) > 500 ||
        strlen($refree1name) > 150 ||
        strlen($refree1phone) > 30 ||
        strlen($refree2name) > 150 ||
        strlen($refree2phone) > 30
    ) {
        staffRedirect('One or more fields exceed the permitted length.');
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE VALIDATION
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [
        'Cashier',
        'Branch Manager',
        'Head of Administrator',
        'Operation Manager'
    ];

    $productionIndustries = [
        'Production Company',
        'Warehousing / Supermarkets'
    ];

    if (
        in_array(
            $company_industry,
            $productionIndustries,
            true
        )
    ) {
        $allowedRoles[] = 'Production Manager';
    }

    if (!in_array($role, $allowedRoles, true)) {
        staffRedirect('The selected staff role is not permitted for this company.');
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL UNIQUENESS
    |--------------------------------------------------------------------------
    */

    $staffCheck = $pdo->prepare("
        SELECT id
        FROM staffs
        WHERE email = ?
        LIMIT 1
    ");

    $staffCheck->execute([$email]);

    if ($staffCheck->fetch(PDO::FETCH_ASSOC)) {
        staffRedirect('This email address already exists in the staff records.');
    }

    $userCheck = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $userCheck->execute([$email]);

    if ($userCheck->fetch(PDO::FETCH_ASSOC)) {
        staffRedirect('This email address is already registered to a user account.');
    }

    /*
    |--------------------------------------------------------------------------
    | PROFILE IMAGE VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_FILES['profile']) ||
        !is_array($_FILES['profile']) ||
        $_FILES['profile']['error'] !== UPLOAD_ERR_OK
    ) {
        staffRedirect('A valid staff profile picture is required.');
    }

    $profileFile = $_FILES['profile'];

    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    if ((int)$profileFile['size'] <= 0) {
        staffRedirect('The uploaded profile picture is empty.');
    }

    if ((int)$profileFile['size'] > $maxFileSize) {
        staffRedirect('Profile pictures must not exceed 5 MB.');
    }

    if (!is_uploaded_file($profileFile['tmp_name'])) {
        staffRedirect('The uploaded profile picture could not be verified.');
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException(
            'File information validation is unavailable on this server.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mimeType = $finfo->file($profileFile['tmp_name']);

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        staffRedirect('Invalid profile image. Allowed formats are JPG, PNG and WebP.');
    }

    $imageInfo = getimagesize($profileFile['tmp_name']);

    if ($imageInfo === false) {
        staffRedirect('The uploaded file is not a valid image.');
    }

    $extension = $allowedMimeTypes[$mimeType];

    /*
    |--------------------------------------------------------------------------
    | PASSWORD
    |--------------------------------------------------------------------------
    | Retaining your original password rule: the staff member's phone number
    | is used as the initial password.
    */

    $password = password_hash($phone, PASSWORD_DEFAULT);

    if ($password === false) {
        throw new RuntimeException('Could not securely generate the staff password.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD DIRECTORY
    |--------------------------------------------------------------------------
    */

    $uploadDir = __DIR__ . '/../uploads/staff/';

    if (
        !is_dir($uploadDir) &&
        !mkdir($uploadDir, 0755, true) &&
        !is_dir($uploadDir)
    ) {
        throw new RuntimeException('Could not create the staff upload directory.');
    }

    if (!is_writable($uploadDir)) {
        throw new RuntimeException('The staff upload directory is not writable.');
    }

    $safeName = 'staff_' . bin2hex(random_bytes(16)) . '.' . $extension;

    $destination = $uploadDir . $safeName;

    $profilePath = 'uploads/staff/' . $safeName;

    /*
    |--------------------------------------------------------------------------
    | CREATE STAFF RECORD
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();

    if (!move_uploaded_file($profileFile['tmp_name'], $destination)) {
        throw new RuntimeException('The profile picture could not be uploaded.');
    }

    $currentTime = date('Y-m-d H:i:s');

    $insertStmt = $pdo->prepare("
        INSERT INTO staffs
        (
            firstname,
            lastname,
            email,
            phone,
            role,
            address,
            profile,
            refree1name,
            refree1phone,
            refree2name,
            refree2phone,
            location_id,
            company_id,
            created_at,
            created_by,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insertStmt->execute([
        $firstname,
        $lastname,
        $email,
        $phone,
        $role,
        $address,
        $profilePath,
        $refree1name,
        $refree1phone,
        $refree2name,
        $refree2phone,

        // Authoritative session branch, not submitted form data.
        $location_id,

        // Authoritative session company, not submitted form data.
        $company_id,

        $currentTime,
        $created_by,
        $status
    ]);

    $newStaffId = (int)$pdo->lastInsertId();

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | SESSION INFORMATION
    |--------------------------------------------------------------------------
    | Do not overwrite the logged-in manager's email, company_id or status.
    | The creator must remain logged in as themselves.
    */

 
      $_SESSION['staff_username'] = $firstname . ' ' . $lastname;
        $_SESSION['email'] = $email;
        $_SESSION['staff_role'] = $role;
        $_SESSION['company_id'] = $company_id;
        $_SESSION['staff_location_id'] = $location_id;
        $_SESSION['status'] = $status;

    staffRedirect(
        'Staff member created successfully and assigned to ' . $location['name'] . '.',
        'user_staffs.php'
    );

} catch (Throwable $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
     * Remove the uploaded image if the database operation failed.
     */
    if (isset($destination) && is_file($destination)) {
        @unlink($destination);
    }

    error_log(
        'Create staff error: ' . $e->getMessage()
    );

    staffRedirect(
        'Failed to create staff. Please check the submitted information and try again.'
    );
}


?>