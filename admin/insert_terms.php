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
        window.location.href='terms.php';
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

    header('Location: terms.php');

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


    if (
        !$userData ||
        empty($userData['email'])
    ) {

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
    | TERMS & CONDITIONS DATA
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


    /*
    |--------------------------------------------------------------------------
    | SECTION 01 - ACCEPTANCE OF TERMS
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
    | SECTION 02 - WEBSITE USE
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
    | SECTION 03 - INTELLECTUAL PROPERTY
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
    | SECTION 04 - WEBSITE INFORMATION
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
    | SECTION 05 - LIABILITY
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
    | SECTION 06 - THIRD PARTY SERVICES
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
    | SECTION 07 - CHANGES TO TERMS
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
    | SECTION 08 - CONTACT
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
        $_POST['status'] ?? 'Active'
    );



    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($eyebrow === '') {

        throw new Exception(
            'Please enter the terms and conditions eyebrow.'
        );

    }


    if ($title === '') {

        throw new Exception(
            'Please enter the terms and conditions title.'
        );

    }


    if ($intro === '') {

        throw new Exception(
            'Please enter the terms and conditions introduction.'
        );

    }


    if ($acceptance_title === '') {

        throw new Exception(
            'Please enter the acceptance section title.'
        );

    }


    if ($acceptance_content === '') {

        throw new Exception(
            'Please enter the acceptance section content.'
        );

    }


    if ($website_use_title === '') {

        throw new Exception(
            'Please enter the website use section title.'
        );

    }


    if ($website_use_content === '') {

        throw new Exception(
            'Please enter the website use section content.'
        );

    }


    if ($intellectual_property_title === '') {

        throw new Exception(
            'Please enter the intellectual property section title.'
        );

    }


    if ($intellectual_property_content === '') {

        throw new Exception(
            'Please enter the intellectual property section content.'
        );

    }


    if ($website_information_title === '') {

        throw new Exception(
            'Please enter the website information section title.'
        );

    }


    if ($website_information_content === '') {

        throw new Exception(
            'Please enter the website information section content.'
        );

    }


    if ($liability_title === '') {

        throw new Exception(
            'Please enter the liability section title.'
        );

    }


    if ($liability_content === '') {

        throw new Exception(
            'Please enter the liability section content.'
        );

    }


    if ($third_party_title === '') {

        throw new Exception(
            'Please enter the third-party section title.'
        );

    }


    if ($third_party_content === '') {

        throw new Exception(
            'Please enter the third-party section content.'
        );

    }


    if ($changes_title === '') {

        throw new Exception(
            'Please enter the changes section title.'
        );

    }


    if ($changes_content === '') {

        throw new Exception(
            'Please enter the changes section content.'
        );

    }


    if ($contact_title === '') {

        throw new Exception(
            'Please enter the contact section title.'
        );

    }


    if ($contact_content === '') {

        throw new Exception(
            'Please enter the contact section content.'
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
    | PREVENT DUPLICATE TERMS & CONDITIONS
    |--------------------------------------------------------------------------
    |
    | Each company should have only one
    | terms and conditions record.
    |
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM terms_conditions
        WHERE company_id = ?
        LIMIT 1
    ");


    $stmt->execute([
        $company_id
    ]);


    $existing_terms = $stmt->fetchColumn();


    if ($existing_terms) {

        echo "<script>

            alert(
                'Terms and Conditions already exist for this company. Please edit the existing Terms and Conditions instead.'
            );

            window.location.href='terms.php';

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
    | INSERT TERMS & CONDITIONS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("

        INSERT INTO terms_conditions (

            company_id,

            eyebrow,
            title,
            intro,

            acceptance_title,
            acceptance_content,

            website_use_title,
            website_use_content,

            intellectual_property_title,
            intellectual_property_content,

            website_information_title,
            website_information_content,

            liability_title,
            liability_content,

            third_party_title,
            third_party_content,

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

        alert(
            'Terms and Conditions created successfully.'
        );

        window.location.href = 'terms-conditions.php';

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
            'create_terms.php';

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
            'create_terms.php';

    </script>";

    exit;

}

?>
