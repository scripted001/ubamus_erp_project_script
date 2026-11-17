
<?php

session_start();

include('../config/db.php');


// ============================================================
// COMPANY
// ============================================================

$company_id = $_SESSION['company_id'] ?? null;

if (!$company_id) {

    echo '
    <div class="vendor-empty">

        <div class="vendor-empty-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="vendor-empty-title">
            Session expired
        </h3>

        <p class="vendor-empty-text">
            Please refresh the page and try again.
        </p>

    </div>';

    exit;
}


// ============================================================
// SEARCH PARAMETERS
// ============================================================

$option = $_POST['search_option'] ?? 'business_name';

$value = trim($_POST['search_value'] ?? '');


// ============================================================
// BASE QUERY
// ============================================================

$query = "
    SELECT *
    FROM vendors
    WHERE company_id = ?
";

$params = [$company_id];


// ============================================================
// SEARCH FILTER
// ============================================================

if ($value !== '') {

    if ($option === 'business_name') {

        $query .= "
            AND business_name LIKE ?
        ";

        $params[] = '%' . $value . '%';

    }

    elseif ($option === 'mobile') {

        $query .= "
            AND mobile LIKE ?
        ";

        $params[] = '%' . $value . '%';

    }

    elseif ($option === 'email') {

        $query .= "
            AND email LIKE ?
        ";

        $params[] = '%' . $value . '%';

    }

}


// ============================================================
// ORDER
// ============================================================

$query .= "
    ORDER BY id DESC
";


// ============================================================
// EXECUTE
// ============================================================

$stmt = $pdo->prepare($query);

$stmt->execute($params);

$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// RESULTS
// ============================================================

if (!empty($vendors)):

?>

<div class="vendor-results-grid">

<?php

foreach ($vendors as $branch):


    // ========================================================
    // VENDOR NAME
    // ========================================================

    $fullName = trim(
        ($branch['firstname'] ?? '') .
        ' ' .
        ($branch['business_name'] ?? '')
    );


    if ($fullName === '') {

        $fullName = 'Unnamed Vendor';

    }


    // ========================================================
    // SAFE VALUES
    // ========================================================

    $email = $branch['email'] ?? 'No email';

    $officeAddress = $branch['office_address'] ?? 'Not provided';

    $mobile = $branch['mobile'] ?? 'Not provided';

    $description = $branch['description'] ?? 'No description';

    $status = $branch['status'] ?? 'Unknown';

?>

<!-- ============================================================
     VENDOR CARD
============================================================ -->

<div class="vendor-card">


    <!-- ========================================================
         CARD TOP
    ========================================================= -->

    <div class="vendor-card-top">


        <div class="vendor-identity">


            <!-- VENDOR ICON -->

            <div class="vendor-avatar">

                <i class="fa-solid fa-building"></i>

            </div>


            <!-- VENDOR IDENTITY -->

            <div class="min-w-0">

                <div class="vendor-name">

                    <?= htmlspecialchars(
                        $fullName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>


                <div class="vendor-email">

                    <?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            </div>


        </div>


        <!-- STATUS -->

        <div class="vendor-badge">

            <i class="fa-solid fa-circle"></i>

            <?= htmlspecialchars(
                $status,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>


    </div>


    <!-- ========================================================
         INFORMATION GRID
    ========================================================= -->

    <div class="vendor-info-grid">


        <!-- OFFICE ADDRESS -->

        <div class="vendor-info-item">

            <div class="vendor-info-label">

                <i class="fa-solid fa-location-dot"></i>

                Office Address

            </div>


            <div class="vendor-info-value">

                <?= htmlspecialchars(
                    $officeAddress,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        </div>


        <!-- MOBILE -->

        <div class="vendor-info-item">

            <div class="vendor-info-label">

                <i class="fa-solid fa-phone"></i>

                Mobile

            </div>


            <div class="vendor-info-value">

                <?= htmlspecialchars(
                    $mobile,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        </div>


        <!-- DESCRIPTION -->

        <div class="vendor-info-item">

            <div class="vendor-info-label">

                <i class="fa-solid fa-align-left"></i>

                Description

            </div>


            <div class="vendor-info-value">

                <?= htmlspecialchars(
                    $description,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        </div>


        <!-- EMAIL -->

        <div class="vendor-info-item">

            <div class="vendor-info-label">

                <i class="fa-solid fa-envelope"></i>

                Email

            </div>


            <div class="vendor-info-value">

                <?= htmlspecialchars(
                    $email,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        </div>


    </div>


    <!-- ========================================================
         ACTIONS
    ========================================================= -->

    <div class="vendor-actions">


        <!-- ====================================================
             SUSPEND
        ===================================================== -->

        <form
            method="POST"
            action="suspend-vendor.php"
        >

            <input
                type="hidden"
                name="vendor_id"
                value="<?= htmlspecialchars(
                    $branch['id'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <input
                type="hidden"
                name="vendor_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                class="vendor-action-btn vendor-action-suspend"
            >

                <i class="fa-solid fa-pause"></i>

                Suspend

            </button>

        </form>


        <!-- ====================================================
             ACTIVATE
        ===================================================== -->

        <form
            method="POST"
            action="activate-vendor.php"
        >

            <input
                type="hidden"
                name="vendor_id"
                value="<?= htmlspecialchars(
                    $branch['id'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <input
                type="hidden"
                name="vendor_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                class="vendor-action-btn vendor-action-activate"
            >

                <i class="fa-solid fa-power-off"></i>

                Activate

            </button>

        </form>


        <!-- ====================================================
             DELETE
        ===================================================== -->

        <form
            method="POST"
            action="delete_vendor.php"
        >

            <input
                type="hidden"
                name="vendor_id"
                value="<?= htmlspecialchars(
                    $branch['id'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <input
                type="hidden"
                name="vendor_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                class="vendor-action-btn vendor-action-delete"
            >

                <i class="fa-solid fa-trash-can"></i>

                Delete

            </button>

        </form>


    </div>


</div>

<?php

endforeach;

?>

</div>

<?php

else:

?>

<!-- ============================================================
     EMPTY SEARCH RESULT
============================================================ -->

<div class="vendor-empty">

    <div class="vendor-empty-icon">

        <i class="fa-solid fa-truck-fast"></i>

    </div>


    <h3 class="vendor-empty-title">

        No vendors found

    </h3>


    <p class="vendor-empty-text">

        No vendors match your current search criteria.

    </p>

</div>

<?php

endif;

?>
