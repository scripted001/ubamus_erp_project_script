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
            window.location.href = 'privacy.php';
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

    header('Location: privacy.php');
    exit;

}


/*
|--------------------------------------------------------------------------
| PRIVACY POLICY ID
|--------------------------------------------------------------------------
*/

$privacy_id = (int)($_POST['privacy_id'] ?? 0);

if ($privacy_id <= 0) {

    echo "
        <script>
            alert('Invalid privacy policy.');
            window.location.href = 'privacy.php';
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

$information_collect_title = trim(
    $_POST['information_collect_title'] ?? ''
);

$information_collect_content = trim(
    $_POST['information_collect_content'] ?? ''
);

$information_use_title = trim(
    $_POST['information_use_title'] ?? ''
);

$information_use_content = trim(
    $_POST['information_use_content'] ?? ''
);

$security_title = trim(
    $_POST['security_title'] ?? ''
);

$security_content = trim(
    $_POST['security_content'] ?? ''
);

$sharing_title = trim(
    $_POST['sharing_title'] ?? ''
);

$sharing_content = trim(
    $_POST['sharing_content'] ?? ''
);

$cookies_title = trim(
    $_POST['cookies_title'] ?? ''
);

$cookies_content = trim(
    $_POST['cookies_content'] ?? ''
);

$changes_title = trim(
    $_POST['changes_title'] ?? ''
);

$changes_content = trim(
    $_POST['changes_content'] ?? ''
);

$contact_title = trim(
    $_POST['contact_title'] ?? ''
);

$contact_content = trim(
    $_POST['contact_content'] ?? ''
);

$status = trim($_POST['status'] ?? '');


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

$required_fields = [

    'title' => $title,

    'intro' => $intro,

    'information_collect_title' =>
        $information_collect_title,

    'information_collect_content' =>
        $information_collect_content,

    'information_use_title' =>
        $information_use_title,

    'information_use_content' =>
        $information_use_content,

    'security_title' =>
        $security_title,

    'security_content' =>
        $security_content,

    'sharing_title' =>
        $sharing_title,

    'sharing_content' =>
        $sharing_content,

    'cookies_title' =>
        $cookies_title,

    'cookies_content' =>
        $cookies_content,

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
                alert('Please complete all required privacy policy fields.');
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
            alert('Invalid privacy policy status.');
            window.history.back();
        </script>
    ";

    exit;

}


/*
|--------------------------------------------------------------------------
| CONFIRM PRIVACY POLICY BELONGS TO COMPANY
|--------------------------------------------------------------------------
*/

$check = $pdo->prepare("
    SELECT id
    FROM privacy_policy
    WHERE id = ?
    AND company_id = ?
    LIMIT 1
");

$check->execute([
    $privacy_id,
    $company_id
]);


if (!$check->fetchColumn()) {

    echo "
        <script>
            alert('Privacy policy not found.');
            window.location.href = 'privacy.php';
        </script>
    ";

    exit;

}


/*
|--------------------------------------------------------------------------
| UPDATE PRIVACY POLICY
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("

        UPDATE privacy_policy

        SET

            eyebrow = ?,

            title = ?,

            intro = ?,

            information_collect_title = ?,

            information_collect_content = ?,

            information_use_title = ?,

            information_use_content = ?,

            security_title = ?,

            security_content = ?,

            sharing_title = ?,

            sharing_content = ?,

            cookies_title = ?,

            cookies_content = ?,

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

        $information_collect_title,

        $information_collect_content,

        $information_use_title,

        $information_use_content,

        $security_title,

        $security_content,

        $sharing_title,

        $sharing_content,

        $cookies_title,

        $cookies_content,

        $changes_title,

        $changes_content,

        $contact_title,

        $contact_content,

        $status,

        $privacy_id,

        $company_id

    ]);


    echo "
        <script>

            alert('Privacy policy updated successfully.');

            window.location.href = 'privacy.php';

        </script>
    ";

    exit;


} catch (PDOException $e) {

    echo "
        <script>

            alert(
                'Unable to update the privacy policy. Please try again.'
            );

            window.history.back();

        </script>
    ";

    exit;

}