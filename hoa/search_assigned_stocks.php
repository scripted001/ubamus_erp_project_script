<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'];

$search_value = trim($_GET['search_value'] ?? '');

$sql = "
SELECT 
    sa.quantity,
    sa.date,
    sa.time,

    p.name AS product_name,

    pu.cost_price,
    pu.selling_price,

    CONCAT(stf.firstname, ' ', stf.lastname) AS assigned_to_name,
    CONCAT(stf2.firstname, ' ', stf2.lastname) AS assigned_by_name

FROM stocks_assigned sa

LEFT JOIN stocks s ON sa.stock_id = s.id
LEFT JOIN products p ON s.product_id = p.id
LEFT JOIN product_units pu ON pu.product_id = p.id

LEFT JOIN staffs stf ON sa.assigned_to = stf.id
LEFT JOIN staffs stf2 ON sa.assigned_by = stf2.id

WHERE sa.company_id = ?
";

$params = [$company_id];

// ===============================
// SMART SEARCH (PRODUCT + STAFF + DATE)
// ===============================
if (!empty($search_value)) {

    $sql .= " AND (
        p.name LIKE ?
        OR stf.firstname LIKE ?
        OR stf.lastname LIKE ?
        OR sa.date LIKE ?
    )";

    $params[] = "%$search_value%";
    $params[] = "%$search_value%";
    $params[] = "%$search_value%";
    $params[] = "%$search_value%";
}

$sql .= " ORDER BY sa.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p style='padding:10px;color:#999;'>No results found</p>";
    exit;
}

foreach ($rows as $row) {

    $qty = $row['quantity'];
    $cost = $row['cost_price'];
    $sell = $row['selling_price'];

    $total_cost = $qty * $cost;
    $total_sell = $qty * $sell;

    echo "
   
        <div class='stock-card flex-1'>

        <h3 class='text-el'><b>Product Name:</b> {$row['product_name']}</h3>

        <p class='text-el'><b>Staff Assigned to:</b> {$row['assigned_to_name']}</p>
        <p class='text-el'><b>Assigned By:</b> {$row['assigned_by_name']}</p>

        <p class='text-el'><b>Qty:</b> {$qty}</p>
        <br>
        <hr>
        <br>

        <p class='text-el'><b>Cost:</b> ₦$cost</p>
        <p class='text-el'><b>Sell:</b> ₦$sell</p>

        <p class='text-el'><b>Total Cost:</b> ₦$total_cost</p>
        <p class='text-el'><b>Total Value:</b> ₦$total_sell</p>

        <p class='text-el' style='font-size:12px;opacity:0.7;'>
            <b>Date/Time:</b>{$row['date']} {$row['time']}
        </p>
        

        </div>

    ";
}
?>  