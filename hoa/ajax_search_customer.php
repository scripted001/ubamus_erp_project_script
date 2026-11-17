<?php

include('../config/db.php');

$company_id = $_SESSION['company_id'] ?? null;

$option = $_POST['search_option'] ?? '';
$value  = trim($_POST['search_value'] ?? '');


// ============================================================
// VALIDATE COMPANY
// ============================================================

if (!$company_id) {

    echo '
    <div class="customer-empty">

        <div class="customer-empty-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <strong>
            Unable to load customers
        </strong>

        <span>
            Your company session could not be identified.
        </span>

    </div>';

    exit;
}


// ============================================================
// BASE QUERY
// ============================================================

$query = "
    SELECT *
    FROM customers
    WHERE company_id = ?
";

$params = [$company_id];


// ============================================================
// SEARCH FILTER
// ============================================================

if ($value !== '') {

    if ($option === 'fullname') {

        $query .= " AND fullname LIKE ?";
        $params[] = "%{$value}%";

    }

    elseif ($option === 'phone') {

        $query .= " AND phone LIKE ?";
        $params[] = "%{$value}%";

    }

    elseif ($option === 'email') {

        $query .= " AND email LIKE ?";
        $params[] = "%{$value}%";

    }

}


// ============================================================
// ORDER
// ============================================================

$query .= " ORDER BY fullname ASC";


// ============================================================
// EXECUTE
// ============================================================

$stmt = $pdo->prepare($query);

$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ============================================================
// NO RESULTS
// ============================================================

if (!$customers) {

    echo '
    <div class="customer-empty">

        <div class="customer-empty-icon">
            <i class="fa-solid fa-users-slash"></i>
        </div>

        <strong>
            No customers found
        </strong>

        <span>
            No registered customer matches your search.
        </span>

    </div>';

    exit;
}


// ============================================================
// CUSTOMER RESULTS
// EXACT SAME UI AS MAIN PAGE
// ============================================================

foreach ($customers as $branch):

?>

<!-- =========================================================
     CUSTOMER CARD
     ========================================================= -->

<div class="customer-card">


    <!-- =====================================================
         CUSTOMER HEADER
         ===================================================== -->

    <div class="customer-card-top">


        <div class="customer-avatar">

            <i class="fa-solid fa-user"></i>

        </div>


        <div class="customer-card-name">

            <strong>
                <?= htmlspecialchars(
                    $branch['fullname'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

            <span>
                Registered Customer
            </span>

        </div>


    </div>


    <!-- =====================================================
         CUSTOMER INFORMATION
         ===================================================== -->

    <div class="customer-info">


        <!-- FULLNAME -->

        <div class="customer-row">

            <span class="customer-label">

                <i class="fa-solid fa-user"></i>

                Fullname

            </span>

            <span class="customer-value">

                <?= htmlspecialchars(
                    $branch['fullname'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>

        </div>


        <!-- EMAIL -->

        <div class="customer-row">

            <span class="customer-label">

                <i class="fa-solid fa-envelope"></i>

                Email

            </span>

            <span class="customer-value">

                <?= htmlspecialchars(
                    $branch['email'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>

        </div>


        <!-- MOBILE -->

        <div class="customer-row">

            <span class="customer-label">

                <i class="fa-solid fa-phone"></i>

                Mobile

            </span>

            <span class="customer-value">

                <?= htmlspecialchars(
                    $branch['phone'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>

        </div>


    </div>


    <!-- =====================================================
         ACTIONS
         ===================================================== -->

    <div class="customer-actions">


        <form
            method="POST"
            action="delete_customer.php"
            class="customer-delete-form"
            onsubmit="return confirm('Do you want to proceed deleting this customer?');"
        >


            <input
                type="hidden"
                name="staff_id"
                value="<?= htmlspecialchars(
                    $branch['id'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <input
                type="hidden"
                name="staff_email"
                value="<?= htmlspecialchars(
                    $branch['email'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <button
                type="submit"
                name="submit"
                value="submit"
                class="customer-delete-button"
            >

                <i class="fa-solid fa-trash"></i>

                <span>
                    Delete Customer
                </span>

            </button>


        </form>


    </div>


</div>


<?php

endforeach;

?>