<?php
session_start();
include('../config/db.php');

$user_id = $_SESSION['user_id'] ?? null;

$stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$company_id = $user['company_id'];

$option = $_POST['search_option'] ?? '';
$value  = $_POST['search_value'] ?? '';

$query = "
SELECT invoice_number, customer_name, customer_email, customer_phone, total_amount, created_at
FROM sales
WHERE YEARWEEK(created_at,1) = YEARWEEK(CURDATE(),1)
AND company_id = ?
";

$params = [$company_id];

if(!empty($value)){

    if($option == "invoice"){
        $query .= " AND invoice_number LIKE ?";
        $params[] = "%$value%";
    }

    if($option == "customer"){
        $query .= " AND customer_name LIKE ?";
        $params[] = "%$value%";
    }

    if($option == "date"){
        $query .= " AND DATE(created_at) = ?";
        $params[] = $value;
    }

}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($sales){

foreach($sales as $sale){

$pdf_file = "../invoices/invoice_{$sale['invoice_number']}.pdf";

echo "<tr class='border-t border-gray-100 dark:border-gray-800'>

<td class='px-6 py-3.5'>{$sale['invoice_number']}</td>
<td class='px-6 py-3.5'>{$sale['customer_name']}</td>
<td class='px-6 py-3.5'>{$sale['customer_email']}</td>
<td class='px-6 py-3.5'>{$sale['customer_phone']}</td>
<td class='px-6 py-3.5'>₦".number_format($sale['total_amount'],2)."</td>
<td class='px-6 py-3.5'>".date('M d, Y h:i A', strtotime($sale['created_at']))."</td>

<td class='px-6 py-3.5'>";

if(file_exists($pdf_file)){
    echo "<a href='$pdf_file' target='_blank'>View</a>";
}else{
    echo "Not found";
}

echo "</td></tr>";

}

}else{

echo "<tr>
<td colspan='7' style='text-align:center'>No results found</td>
</tr>";

}
?>