<?php

session_start();

include('../config/db.php');


/*
|--------------------------------------------------------------------------
| VALIDATE COMPANY SESSION
|--------------------------------------------------------------------------
*/

$company_id = (int)($_SESSION['company_id'] ?? 0);

if ($company_id <= 0) {

    echo "
        <script>
            alert('Invalid company session.');
            window.location.href = 'terms.php';
        </script>
    ";

    exit;

}


/*
|--------------------------------------------------------------------------
| ONLY ACCEPT POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: terms.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| TERMS AND CONDITIONS ID
|--------------------------------------------------------------------------
*/

$terms_id = (int)($_POST['terms_id'] ?? 0);

if ($terms_id <= 0) {

    echo "
        <script>
            alert('Invalid terms and conditions record.');
            window.location.href = 'terms.php';
        </script>
    ";

    exit;

}


/*
|--------------------------------------------------------------------------
| SANITIZE / COLLECT FORM DATA
|--------------------------------------------------------------------------
*/

$eyebrow = trim($_POST['eyebrow'] ?? '');

$title = trim($_POST['title'] ?? '');

$intro = trim($_POST['intro'] ?? '');


/*
|--------------------------------------------------------------------------
| ACCEPTANCE OF TERMS
|--------------------------------------------------------------------------
*/

$acceptance_title = trim(
    $_POST['acceptance_title'] ?? ''
);

$acceptance_content = trim(
    $_POST['acceptance_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| WEBSITE USE
|--------------------------------------------------------------------------
*/

$website_use_title = trim(
    $_POST['website_use_title'] ?? ''
);

$website_use_content = trim(
    $_POST['website_use_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| INTELLECTUAL PROPERTY
|--------------------------------------------------------------------------
*/

$intellectual_property_title = trim(
    $_POST['intellectual_property_title'] ?? ''
);

$intellectual_property_content = trim(
    $_POST['intellectual_property_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| WEBSITE INFORMATION
|--------------------------------------------------------------------------
*/

$website_information_title = trim(
    $_POST['website_information_title'] ?? ''
);

$website_information_content = trim(
    $_POST['website_information_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| LIABILITY
|--------------------------------------------------------------------------
*/

$liability_title = trim(
    $_POST['liability_title'] ?? ''
);

$liability_content = trim(
    $_POST['liability_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| THIRD PARTY
|--------------------------------------------------------------------------
*/

$third_party_title = trim(
    $_POST['third_party_title'] ?? ''
);

$third_party_content = trim(
    $_POST['third_party_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| CHANGES TO TERMS
|--------------------------------------------------------------------------
*/

$changes_title = trim(
    $_POST['changes_title'] ?? ''
);

$changes_content = trim(
    $_POST['changes_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| CONTACT
|--------------------------------------------------------------------------
*/

$contact_title = trim(
    $_POST['contact_title'] ?? ''
);

$contact_content = trim(
    $_POST['contact_content'] ?? ''
);


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$status = trim(
    $_POST['status'] ?? ''
);


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

$required_fields = [

    'title' => $title,

    'intro' => $intro,

    'acceptance_title' =>
        $acceptance_title,

    'acceptance_content' =>
        $acceptance_content,

    'website_use_title' =>
        $website_use_title,

    'website_use_content' =>
        $website_use_content,

    'intellectual_property_title' =>
        $intellectual_property_title,

    'intellectual_property_content' =>
        $intellectual_property_content,

    'website_information_title' =>
        $website_information_title,

    'website_information_content' =>
        $website_information_content,

    'liability_title' =>
        $liability_title,

    'liability_content' =>
        $liability_content,

    'third_party_title' =>
        $third_party_title,

    'third_party_content' =>
        $third_party_content,

    'changes_title' =>
        $changes_title,

    'changes_content' =>
        $changes_content,

    'contact_title' =>
        $contact_title,

    'contact_content' =>
        $contact_content,

    'status' =>
        $status

];


foreach ($required_fields as $field => $value) {

    if ($value === '') {

        echo "
            <script>

                alert(
                    'Please complete all required terms and conditions fields.'
                );

                window.history.back();

            </script>
        ";

        exit;

    }

}


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/

$allowed_statuses = [

    'Draft',

    'Active',

    'Inactive'

];


if (!in_array(
    $status,
    $allowed_statuses,
    true
)) {

    echo "
        <script>

            alert(
                'Invalid terms and conditions status.'
            );

            window.history.back();

        </script>
    ";

    exit;

}


/*
|--------------------------------------------------------------------------
| CONFIRM TERMS RECORD BELONGS TO COMPANY
|--------------------------------------------------------------------------
*/

$check = $pdo->prepare("

    SELECT id

    FROM terms_conditions

    WHERE id = ?

    AND company_id = ?

    LIMIT 1

");


$check->execute([

    $terms_id,

    $company_id

]);


if (!$check->fetchColumn()) {

    echo "
        <script>

            alert(
                'Terms and conditions record not found.'
            );

            window.location.href = 'terms.php';

        </script>
    ";

    exit;

}


/*
|--------------------------------------------------------------------------
| UPDATE TERMS AND CONDITIONS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("

        UPDATE terms_conditions

        SET

            eyebrow = ?,

            title = ?,

            intro = ?,

            acceptance_title = ?,

            acceptance_content = ?,

            website_use_title = ?,

            website_use_content = ?,

            intellectual_property_title = ?,

            intellectual_property_content = ?,

            website_information_title = ?,

            website_information_content = ?,

            liability_title = ?,

            liability_content = ?,

            third_party_title = ?,

            third_party_content = ?,

            changes_title = ?,

            changes_content = ?,

            contact_title = ?,

            contact_content = ?,

            status = ?,

            updated_at = NOW()

        WHERE id = ?

        AND company_id = ?

    ");


    $stmt->execute([

        $eyebrow,

        $title,

        $intro,

        $acceptance_title,

        $acceptance_content,

        $website_use_title,

        $website_use_content,

        $intellectual_property_title,

        $intellectual_property_content,

        $website_information_title,

        $website_information_content,

        $liability_title,

        $liability_content,

        $third_party_title,

        $third_party_content,

        $changes_title,

        $changes_content,

        $contact_title,

        $contact_content,

        $status,

        $terms_id,

        $company_id

    ]);


    echo "
        <script>

            alert(
                'Terms and conditions updated successfully.'
            );

            window.location.href = 'terms-conditions.php';

        </script>
    ";

    exit;


} catch (PDOException $e) {

    echo "
        <script>

            alert(
                'Unable to update the terms and conditions. Please try again.'
            );

            window.history.back();

        </script>
    ";

    exit;

}

?>
