
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');


// ============================================================
// AUTHENTICATED USER
// ============================================================

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "<script>
        alert('Unauthorized access.');
        history.back();
    </script>";
    exit;
}


// ============================================================
// GET COMPANY ID FROM USERS TABLE
// ============================================================

$stmt = $pdo->prepare("
    SELECT company_id
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['company_id'])) {
    echo "<script>
        alert('Company account not found.');
        history.back();
    </script>";
    exit;
}

$company_id = (int)$user['company_id'];


// ============================================================
// FETCH COMPANY
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$company_id]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo "<script>
        alert('Company information not found.');
        history.back();
    </script>";
    exit;
}


// ============================================================
// ONLY PROCESS POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: company-information.php');
    exit;
}


// ============================================================
// GET FORM DATA
// ============================================================

$company_name = trim(
    $_POST['company_name'] ?? ''
);

$company_slug = trim(
    $_POST['company_slug'] ?? ''
);

$company_domain = trim(
    $_POST['company_domain'] ?? ''
);

$company_industry = trim(
    $_POST['company_industry'] ?? ''
);

$company_customer_care = trim(
    $_POST['company_customer_care'] ?? ''
);

$company_support_email = trim(
    $_POST['company_support_email'] ?? ''
);

$company_email = trim(
    $_POST['company_email'] ?? ''
);

$company_mobile = trim(
    $_POST['company_mobile'] ?? ''
);

$status = trim(
    $_POST['status'] ?? ''
);


// ============================================================
// PASSWORDS
// ============================================================

$customer_care_password = trim(
    $_POST['customer_care_password'] ?? ''
);

$support_email_password = trim(
    $_POST['support_email_password'] ?? ''
);


// ============================================================
// VALIDATION
// ============================================================

if ($company_name === '') {

    echo "<script>
        alert('Company name is required.');
        history.back();
    </script>";
    exit;
}


if ($company_industry === '') {

    echo "<script>
        alert('Company industry is required.');
        history.back();
    </script>";
    exit;
}


if ($company_email === '') {

    echo "<script>
        alert('Company email is required.');
        history.back();
    </script>";
    exit;
}


if ($company_mobile === '') {

    echo "<script>
        alert('Company mobile number is required.');
        history.back();
    </script>";
    exit;
}


// ============================================================
// KEEP EXISTING LOGO
// ============================================================

$company_logo = $company['company_logo'] ?? '';


// ============================================================
// NEW LOGO
// ============================================================

if (
    isset($_FILES['company_logo']) &&
    $_FILES['company_logo']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if (
        $_FILES['company_logo']['error'] !==
        UPLOAD_ERR_OK
    ) {

        echo "<script>
            alert('Company logo upload failed.');
            history.back();
        </script>";
        exit;
    }


    $allowed_extensions = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];


    $original_name = $_FILES['company_logo']['name'];

    $temporary_file = $_FILES['company_logo']['tmp_name'];

    $file_size = $_FILES['company_logo']['size'];


    $extension = strtolower(
        pathinfo(
            $original_name,
            PATHINFO_EXTENSION
        )
    );


    // --------------------------------------------------------
    // SIZE
    // --------------------------------------------------------

    if ($file_size > 5 * 1024 * 1024) {

        echo "<script>
            alert('Company logo must not exceed 5MB.');
            history.back();
        </script>";
        exit;
    }


    // --------------------------------------------------------
    // EXTENSION
    // --------------------------------------------------------

    if (
        !in_array(
            $extension,
            $allowed_extensions,
            true
        )
    ) {

        echo "<script>
            alert('Invalid logo format. Use JPG, JPEG, PNG or WEBP.');
            history.back();
        </script>";
        exit;
    }


    // --------------------------------------------------------
    // VERIFY IMAGE
    // --------------------------------------------------------

    if (
        getimagesize($temporary_file) === false
    ) {

        echo "<script>
            alert('The uploaded logo is not a valid image.');
            history.back();
        </script>";
        exit;
    }


    // --------------------------------------------------------
    // UPLOAD DIRECTORY
    // --------------------------------------------------------

    $upload_directory =
        '../uploads/logos/';


    if (!is_dir($upload_directory)) {

        if (
            !mkdir(
                $upload_directory,
                0755,
                true
            )
        ) {

            echo "<script>
                alert('Unable to create company logo directory.');
                history.back();
            </script>";
            exit;
        }
    }


    // --------------------------------------------------------
    // UNIQUE FILE NAME
    // --------------------------------------------------------

    $new_file_name =
        'company_' .
        $company_id .
        '_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $extension;


    $upload_path =
        $upload_directory .
        $new_file_name;


    // --------------------------------------------------------
    // MOVE FILE
    // --------------------------------------------------------

    if (
        !move_uploaded_file(
            $temporary_file,
            $upload_path
        )
    ) {

        echo "<script>
            alert('Unable to save the company logo.');
            history.back();
        </script>";
        exit;
    }


    $company_logo = $new_file_name;
}


// ============================================================
// KEEP EXISTING PASSWORDS IF BLANK
// ============================================================

if ($customer_care_password === '') {

    $customer_care_password =
        $company['customer_care_password'] ?? '';
}


if ($support_email_password === '') {

    $support_email_password =
        $company['support_email_password'] ?? '';
}


// ============================================================
// UPDATE COMPANY
// ============================================================

try {

    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        UPDATE companies
        SET
            company_name = ?,
            company_slug = ?,
            company_logo = ?,
            company_domain = ?,
            company_industry = ?,
            company_customer_care = ?,
            customer_care_password = ?,
            company_support_email = ?,
            support_email_password = ?,
            company_email = ?,
            company_mobile = ?,
            status = ?
        WHERE id = ?
    ");


    $stmt->execute([

        $company_name,

        $company_slug,

        $company_logo,

        $company_domain !== ''
            ? $company_domain
            : null,

        $company_industry,

        $company_customer_care,

        $customer_care_password,

        $company_support_email,

        $support_email_password,

        $company_email,

        $company_mobile,

        $status,

        $company_id

    ]);


    $pdo->commit();


    // ========================================================
    // SUCCESS
    // ========================================================

    echo "<script>
        alert('Company information updated successfully.');
        window.location='company-information.php';
    </script>";

    exit;


} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    echo "<script>
        alert(" .
        json_encode(
            'Company update failed: ' . $e->getMessage()
        ) .
        ");
        history.back();
    </script>";

    exit;
}

?>