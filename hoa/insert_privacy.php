<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


/*
|--------------------------------------------------------------------------
| SESSION DATA
|--------------------------------------------------------------------------
*/

$company_id = (int)($_SESSION['company_id'] ?? 0);

$user_id = (int)($_SESSION['user_id'] ?? 0);


if ($company_id <= 0 || $user_id <= 0) {

    echo "<script>
        alert('Invalid company or user session.');
        window.location.href='privacy.php';
    </script>";

    exit;

}


/*
|--------------------------------------------------------------------------
| TIMEZONE
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Africa/Lagos');


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: privacy.php');

    exit;

}


try {


    /*
    |--------------------------------------------------------------------------
    | FETCH USER EMAIL
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $user_id
    ]);


    $userData = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$userData || empty($userData['email'])) {

        throw new Exception(
            'Unable to identify the current user.'
        );

    }



    /*
    |--------------------------------------------------------------------------
    | FETCH STAFF RECORD
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM staffs
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([
        $userData['email']
    ]);


    $staff = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | CREATED BY
    |--------------------------------------------------------------------------
    |
    | Prefer the staff ID because that follows
    | the structure of your existing insert pages.
    |
    */

    if (
        !$staff ||
        empty($staff['id'])
    ) {

        throw new Exception(
            'Staff profile could not be found for the current user.'
        );

    }


    $created_by = (int)$staff['id'];



    /*
    |--------------------------------------------------------------------------
    | PRIVACY POLICY DATA
    |--------------------------------------------------------------------------
    */

    $eyebrow = trim(
        $_POST['eyebrow'] ?? ''
    );


    $title = trim(
        $_POST['title'] ?? ''
    );


    $intro = trim(
        $_POST['intro'] ?? ''
    );


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


    $status = trim(
        $_POST['status'] ?? 'Active'
    );



    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($eyebrow === '') {

        throw new Exception(
            'Please enter the privacy policy eyebrow.'
        );

    }


    if ($title === '') {

        throw new Exception(
            'Please enter the privacy policy title.'
        );

    }


    if ($intro === '') {

        throw new Exception(
            'Please enter the privacy policy introduction.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS
    |--------------------------------------------------------------------------
    */

    $allowed_statuses = [
        'Active',
        'Inactive'
    ];


    if (
        !in_array(
            $status,
            $allowed_statuses,
            true
        )
    ) {

        $status = 'Active';

    }



    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE PRIVACY POLICY
    |--------------------------------------------------------------------------
    |
    | Each company should have only one privacy
    | policy record.
    |
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM privacy_policy
        WHERE company_id = ?
        LIMIT 1
    ");


    $stmt->execute([
        $company_id
    ]);


    $existing_privacy = $stmt->fetchColumn();


    if ($existing_privacy) {

        echo "<script>
            alert('A privacy policy already exists for this company. Please edit the existing privacy policy instead.');
            window.location.href='privacy.php';
        </script>";

        exit;

    }



    /*
    |--------------------------------------------------------------------------
    | TIMESTAMPS
    |--------------------------------------------------------------------------
    */

    $created_at = date(
        'Y-m-d H:i:s'
    );


    $updated_at = $created_at;



    /*
    |--------------------------------------------------------------------------
    | INSERT PRIVACY POLICY
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("

        INSERT INTO privacy_policy (

            company_id,

            eyebrow,
            title,
            intro,

            information_collect_title,
            information_collect_content,

            information_use_title,
            information_use_content,

            security_title,
            security_content,

            sharing_title,
            sharing_content,

            cookies_title,
            cookies_content,

            changes_title,
            changes_content,

            contact_title,
            contact_content,

            status,

            created_by,

            created_at,
            updated_at

        )

        VALUES (

            ?,

            ?,
            ?,
            ?,

            ?,
            ?,

            ?,
            ?,

            ?,
            ?,

            ?,
            ?,

            ?,
            ?,

            ?,
            ?,

            ?,
            ?,

            ?,

            ?,

            ?,
            ?

        )

    ");



    $stmt->execute([

        $company_id,

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

        $created_by,

        $created_at,
        $updated_at

    ]);



    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    echo "<script>

        alert('Privacy policy created successfully.');

        window.location.href = 'privacy.php';

    </script>";

    exit;


} catch (PDOException $e) {


    echo "<script>

        alert(
            'Database error: " .
            addslashes(
                $e->getMessage()
            ) .
            "'
        );

        window.location.href =
            'create_privacy.php';

    </script>";

    exit;


} catch (Exception $e) {


    echo "<script>

        alert(
            '" .
            addslashes(
                $e->getMessage()
            ) .
            "'
        );

        window.location.href =
            'create_privacy.php';

    </script>";

    exit;

}

?>
