<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


// ============================================================
// SESSION
// ============================================================

$company_id = (int)($_SESSION['company_id'] ?? 0);



// ============================================================
// FILTER INPUT
// ============================================================

$search      = trim($_POST['search'] ?? '');
$from        = $_POST['from'] ?? '';
$to          = $_POST['to'] ?? '';
$location_id = $_POST['location_id'] ?? '';



// ============================================================
// QUERY ENGINE
// ============================================================

$sql = "

SELECT

    e.*,

    l.name AS location_name,
    l.type AS location_type,
    l.city AS location_city,
    l.state AS location_state

FROM expenses e

LEFT JOIN locations l
    ON l.id = e.location_id

WHERE e.company_id = ?

";


$params = [
    $company_id
];




// ============================================================
// SMART SEARCH
// ============================================================

if($search !== ''){


    $sql .= "

    AND (

        e.expense_title LIKE ?
        OR e.staff_responsible LIKE ?
        OR e.description LIKE ?
        OR e.created_by LIKE ?
        OR e.type LIKE ?
        OR e.approved_by LIKE ?

    )

    ";


    $keyword = "%{$search}%";


    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

}




// ============================================================
// DATE FILTER
// ============================================================


if($from !== ''){

    $sql .= " AND DATE(e.created_at) >= ? ";

    $params[] = $from;

}



if($to !== ''){

    $sql .= " AND DATE(e.created_at) <= ? ";

    $params[] = $to;

}




// ============================================================
// LOCATION FILTER
// ============================================================


if($location_id !== ''){


    $sql .= "

    AND e.location_id = ?

    ";


    $params[] = $location_id;


}




$sql .= "

ORDER BY e.created_at DESC

";




$stmt = $pdo->prepare($sql);

$stmt->execute($params);


$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);






// ============================================================
// EMPTY STATE
// ============================================================

if(empty($expenses)):

?>


<div class="expense-empty">

    <i class="fa-solid fa-receipt"></i>

    <strong>
        No expenses found
    </strong>

    <p class="mt-1 text-sm">
        Try changing your search filters.
    </p>

</div>


<?php

exit;

endif;






// ============================================================
// RESULT CARDS
// ============================================================


foreach($expenses as $exp):


$expenseType = strtolower(
    trim($exp['type'] ?? '')
);


$isCapital = $expenseType === 'capital';



$receiptFile = trim(
    $exp['receipt'] ?? ''
);


$receiptPath = '../uploads/receipts/'.$receiptFile;



?>



<div class="expense-card">



    <!-- MAIN CONTENT -->

    <div class="expense-main">



        <h4 class="expense-title">


            <?= htmlspecialchars(
                $exp['expense_title'] ?? 'Untitled Expense'
            ) ?>


        </h4>





        <div class="expense-details">



            <!-- TYPE -->


            <span class="expense-type 
            <?= $isCapital 
                ? 'expense-type-capital'
                : 'expense-type-operational'
            ?>">


                <?= htmlspecialchars(
                    $exp['type'] ?? 'Expense'
                ) ?>


            </span>







            <!-- AMOUNT -->


            <span class="expense-detail">


                <b>Amount:</b>


                <span class="expense-amount">


                    ₦<?= number_format(
                        (float)($exp['amount'] ?? 0),
                        2
                    ) ?>


                </span>


            </span>








            <!-- LOCATION -->


            <span class="expense-detail">


                <b>Location:</b>


                <?= htmlspecialchars(
                    $exp['location_name'] ?? 'N/A'
                ) ?>


                <?php if(!empty($exp['location_type'])): ?>

                    (<?= htmlspecialchars(
                        $exp['location_type']
                    ) ?>)

                <?php endif; ?>



                <?php if(
                    !empty($exp['location_city']) ||
                    !empty($exp['location_state'])
                ): ?>

                    —

                    <?= htmlspecialchars(
                        $exp['location_city'] ?? ''
                    ) ?>,

                    <?= htmlspecialchars(
                        $exp['location_state'] ?? ''
                    ) ?>


                <?php endif; ?>


            </span>







            <!-- STAFF -->


            <span class="expense-detail">

                <b>By:</b>


                <?= htmlspecialchars(
                    $exp['staff_responsible'] ?? 'N/A'
                ) ?>


            </span>








            <!-- DESCRIPTION -->


            <?php if(!empty($exp['description'])): ?>


            <span class="expense-detail">

                <b>Description:</b>


                <?= htmlspecialchars(
                    $exp['description']
                ) ?>


            </span>


            <?php endif; ?>








            <!-- DATE -->


            <span class="expense-detail">


                <b>Registered:</b>


                <?= htmlspecialchars(
                    $exp['created_at'] ?? ''
                ) ?>


            </span>








            <!-- CREATOR -->


            <span class="expense-detail">


                <b>Registered by:</b>


                <?= htmlspecialchars(
                    $exp['created_by'] ?? ''
                ) ?>


            </span>









            <!-- ACTION BUTTONS -->


            <div class="expense-detail"
                 style="width:100%;display:flex;gap:10px;flex-wrap:wrap;">





            <!-- DELETE -->


            <form
                method="POST"
                action="delete-expenses.php"
                onsubmit="return confirm('Delete this expense?');"
            >


                <input
                    type="hidden"
                    name="expense_id"
                    value="<?= htmlspecialchars($exp['id']) ?>"
                >


                <button
                    type="submit"
                    style="
                    background:#262626;
                    color:white;
                    padding:8px 15px;
                    border-radius:8px;
                    font-size:13px;
                    font-weight:700;
                    "
                >

                    <i class="fa-solid fa-trash"></i>
                    Delete

                </button>


            </form>








            <!-- APPROVAL -->


            <?php if(
                empty($exp['approved_by']) ||
                trim($exp['approved_by']) === 'Not Approved!'
            ): ?>



            <form
                method="POST"
                action="approve_expense.php"
            >


                <input
                    type="hidden"
                    name="expense_id"
                    value="<?= htmlspecialchars($exp['id']) ?>"
                >



                <button
                    type="submit"
                    style="
                    background:#15803d;
                    color:white;
                    padding:8px 15px;
                    border-radius:8px;
                    font-size:13px;
                    font-weight:700;
                    "
                >

                    <i class="fa-solid fa-check"></i>
                    Approve

                </button>


            </form>




            <?php endif; ?>



            </div>









            <!-- APPROVAL STATUS -->


            <div class="expense-approval">


            <?php if(
                empty($exp['approved_by']) ||
                trim($exp['approved_by']) === 'Not Approved!'
            ): ?>


                <span class="expense-approval-not">

                    <i class="fa-solid fa-clock"></i>

                    Not approved yet!

                </span>



            <?php else: ?>


                <span class="expense-approval-yes">


                    <i class="fa-solid fa-circle-check"></i>


                    Approved by:

                    <?= htmlspecialchars(
                        $exp['approved_by']
                    ) ?>


                </span>



            <?php endif; ?>


            </div>




        </div>


    </div>








    <!-- RECEIPT -->


    <div class="expense-action">


    <?php if(
        !empty($receiptFile)
        &&
        file_exists($receiptPath)
    ): ?>


        <a

            href="<?= htmlspecialchars($receiptPath) ?>"

            target="_blank"

            rel="noopener noreferrer"

            class="expense-receipt-button"

        >


            <i class="fa-solid fa-eye"></i>

            View Receipt


        </a>



    <?php else: ?>


        <span class="expense-no-receipt">

            <i class="fa-regular fa-file-circle-xmark"></i>

            No receipt

        </span>



    <?php endif; ?>


    </div>




</div>




<?php endforeach; ?>