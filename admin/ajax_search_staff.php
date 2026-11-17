
<?php


include('../config/db.php');


/*
|--------------------------------------------------------------------------
| COMPANY
|--------------------------------------------------------------------------
*/

$company_id = $_SESSION['company_id'] ?? null;
$location_id = $_SESSION['location_id'] ?? null;

if (!$company_id) {

    echo '
    <div class="staff-empty-state col-span-full">

        <div class="staff-empty-icon">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        </div>

        <h4 class="mb-1 text-sm font-semibold text-gray-700 dark:text-white/90">
            Session Expired
        </h4>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            Please refresh the page and try again.
        </p>

    </div>
    ';

    exit;
}


/*
|--------------------------------------------------------------------------
| SEARCH PARAMETERS
|--------------------------------------------------------------------------
*/

$option = $_POST['search_option'] ?? 'fullname';
$value  = trim($_POST['search_value'] ?? '');


/*
|--------------------------------------------------------------------------
| BASE QUERY
|--------------------------------------------------------------------------
*/

$query = "
    SELECT *
    FROM staffs
    WHERE company_id = ? AND location_id = ?
";

$params = [$company_id, $location_id];


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if ($value !== '') {

    switch ($option) {

        case 'firstname':

            $query .= "
                AND firstname LIKE ?
            ";

            $params[] = '%' . $value . '%';

            break;


        case 'lastname':

            $query .= "
                AND lastname LIKE ?
            ";

            $params[] = '%' . $value . '%';

            break;


        case 'phone':

            $query .= "
                AND phone LIKE ?
            ";

            $params[] = '%' . $value . '%';

            break;


        case 'email':

            $query .= "
                AND email LIKE ?
            ";

            $params[] = '%' . $value . '%';

            break;


        case 'fullname':

        default:

            $query .= "
                AND (
                    CONCAT(firstname, ' ', lastname) LIKE ?
                    OR firstname LIKE ?
                    OR lastname LIKE ?
                )
            ";

            $search = '%' . $value . '%';

            $params[] = $search;
            $params[] = $search;
            $params[] = $search;

            break;
    }
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$query .= "
    ORDER BY id DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($query);
$stmt->execute($params);


/*
|--------------------------------------------------------------------------
| CHECK RESULTS
|--------------------------------------------------------------------------
*/

$staff_found = false;


while ($branch = $stmt->fetch(PDO::FETCH_ASSOC)):

    $staff_found = true;

?>

<!-- ============================================================
     STAFF MEMBER CARD
     ============================================================ -->

<div class="staff-member-card">


    <!-- ========================================================
         STAFF HEADER
         ======================================================== -->

    <div class="staff-member-header">


        <!-- Avatar -->

        <div class="staff-avatar">

            <i
                class="fa-solid fa-user"
                aria-hidden="true"
            ></i>

        </div>


        <!-- Name / Role -->

        <div class="staff-member-name-area">

            <h4 class="staff-member-name">

                <?= htmlspecialchars(
                    $branch['firstname'] ?? ''
                ) ?>

                <?= htmlspecialchars(
                    $branch['lastname'] ?? ''
                ) ?>

            </h4>


            <p class="staff-member-role">

                <i
                    class="fa-solid fa-user-shield mr-1"
                    aria-hidden="true"
                ></i>

                <?= htmlspecialchars(
                    $branch['role'] ?? ''
                ) ?>

            </p>

        </div>

    </div>


    <!-- ========================================================
         STAFF INFORMATION
         ======================================================== -->

    <div class="staff-member-info">


        <!-- ====================================================
             ADDRESS
             ==================================================== -->

        <div class="staff-info-row">

            <div class="staff-info-row-icon">

                <i
                    class="fa-solid fa-location-dot"
                    aria-hidden="true"
                ></i>

            </div>


            <div class="staff-info-content">

                <span class="staff-info-label">
                    Address
                </span>

                <span class="staff-info-value">

                    <?= htmlspecialchars(
                        $branch['address'] ?? ''
                    ) ?>

                </span>

            </div>

        </div>


        <!-- ====================================================
             PHONE
             ==================================================== -->

        <div class="staff-info-row">

            <div class="staff-info-row-icon">

                <i
                    class="fa-solid fa-phone"
                    aria-hidden="true"
                ></i>

            </div>


            <div class="staff-info-content">

                <span class="staff-info-label">
                    Mobile
                </span>

                <span class="staff-info-value">

                    <?= htmlspecialchars(
                        $branch['phone'] ?? ''
                    ) ?>

                </span>

            </div>

        </div>


        <!-- ====================================================
             EMAIL
             ==================================================== -->

        <div class="staff-info-row">

            <div class="staff-info-row-icon">

                <i
                    class="fa-solid fa-envelope"
                    aria-hidden="true"
                ></i>

            </div>


            <div class="staff-info-content">

                <span class="staff-info-label">
                    Email
                </span>

                <span class="staff-info-value">

                    <?= htmlspecialchars(
                        $branch['email'] ?? ''
                    ) ?>

                </span>

            </div>

        </div>


        <!-- ====================================================
             STATUS
             ==================================================== -->

        <div class="staff-info-row">

            <div class="staff-info-row-icon">

                <i
                    class="fa-solid fa-circle-check"
                    aria-hidden="true"
                ></i>

            </div>


            <div class="staff-info-content">

                <span class="staff-info-label">
                    Account Status
                </span>


                <span class="staff-info-value">

                    <span class="staff-status">

                        <span class="staff-status-dot"></span>

                        <?= htmlspecialchars(
                            $branch['status'] ?? ''
                        ) ?>

                    </span>

                </span>

            </div>

        </div>


    </div>


    <!-- ========================================================
         ACTION BUTTONS
         ======================================================== -->

    <div class="staff-actions">


        <!-- ====================================================
             VIEW
             ==================================================== -->

        <form
            method="POST"
            action="staff.php"
            class="staff-action-form"
            onclick="return confirm('Are you sure you want to view this staff details?');"
        >

            <input
                type="hidden"
                name="staff_id"
                value="<?= htmlspecialchars(
                    $branch['id'] ?? ''
                ) ?>"
            >

            <input
                type="hidden"
                name="staff_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? ''
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                class="staff-action-button staff-action-view"
                title="View staff details"
            >

                <i
                    class="fa-solid fa-eye"
                    aria-hidden="true"
                ></i>

                <span>
                    View
                </span>

            </button>

        </form>


        <!-- ====================================================
             SUSPEND
             ==================================================== -->

        <form
            method="POST"
            action="suspend-staff.php"
            class="staff-action-form"
            onclick="return confirm('Are you sure you want to suspend this staff account?');"
        >

            <input
                type="hidden"
                name="staff_id"
                value="<?= htmlspecialchars(
                    $branch['id'] ?? ''
                ) ?>"
            >

            <input
                type="hidden"
                name="staff_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? ''
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                class="staff-action-button staff-action-suspend"
                title="Suspend staff account"
            >

                <i
                    class="fa-solid fa-user-lock"
                    aria-hidden="true"
                ></i>

                <span>
                    Suspend
                </span>

            </button>

        </form>


        <!-- ====================================================
             ACTIVATE
             ==================================================== -->

        <form
            method="POST"
            action="activate-staff.php"
            class="staff-action-form"
            onclick="return confirm('Are you sure you want to activate this staff account?');"
        >

            <input
                type="hidden"
                name="staff_id"
                value="<?= htmlspecialchars(
                    $branch['id'] ?? ''
                ) ?>"
            >

            <input
                type="hidden"
                name="staff_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? ''
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                class="staff-action-button staff-action-activate"
                title="Activate staff account"
            >

                <i
                    class="fa-solid fa-user-check"
                    aria-hidden="true"
                ></i>

                <span>
                    Activate
                </span>

            </button>

        </form>


        <!-- ====================================================
             DELETE
             ==================================================== -->

        <form
            method="POST"
            action="delete_staff.php"
            class="staff-action-form"
            onclick="return confirm('Are you sure you want to delete this staff account?');"
        >

            <input
                type="hidden"
                name="staff_id"
                value="<?= htmlspecialchars(
                    $branch['id'] ?? ''
                ) ?>"
            >

            <input
                type="hidden"
                name="staff_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? ''
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                class="staff-action-button staff-action-delete"
                title="Delete staff account"
            >

                <i
                    class="fa-solid fa-trash-can"
                    aria-hidden="true"
                ></i>

                <span>
                    Delete
                </span>

            </button>

        </form>


    </div>


</div>


<?php endwhile; ?>


<?php

/*
|--------------------------------------------------------------------------
| EMPTY SEARCH RESULT
|--------------------------------------------------------------------------
*/

if (!$staff_found):

?>

<div class="staff-empty-state col-span-full">

    <div class="staff-empty-icon">

        <i
            class="fa-solid fa-users-slash"
            aria-hidden="true"
        ></i>

    </div>


    <h4
        class="mb-1 text-sm font-semibold text-gray-700 dark:text-white/90"
    >
        No Staff Found
    </h4>


    <p
        class="text-xs text-gray-500 dark:text-gray-400"
    >
        No staff account matches your search.
    </p>

</div>

<?php endif; ?>
