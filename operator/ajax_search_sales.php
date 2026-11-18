<?php
include('../config/db.php');



// echo "<pre>";
// print_r($_POST);
// echo "</pre>";

$option = $_POST['search_option'] ?? '';
$value  = trim($_POST['search_value'] ?? '');
$start_date = $_POST['start_date'] ?? '';
$end_date   = $_POST['end_date'] ?? '';

$query = "SELECT * FROM sales WHERE 1";
$params = [];

// Search by invoice or customer
if ($value != "") {

    if ($option == "invoice") {
        $query .= " AND invoice_number LIKE ?";
        $params[] = "%$value%";
    }

    if ($option == "customer") {
        $query .= " AND customer_name LIKE ?";
        $params[] = "%$value%";
    }
}

// Filter by date
if (!empty($start_date) && !empty($end_date)) {

    $query .= " AND created_at BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;

} elseif (!empty($start_date)) {

    $query .= " AND created_at >= ?";
    $params[] = $start_date;

} elseif (!empty($end_date)) {

    $query .= " AND created_at <= ?";
    $params[] = $end_date;

}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($sales) {

    foreach ($sales as $sale) {

        // Location
        $location_id = $sale['location_id'];

        $stmt = $pdo->prepare("
            SELECT name
            FROM locations
            WHERE id = ?
        ");
        $stmt->execute([$location_id]);
        $location_name = $stmt->fetch(PDO::FETCH_ASSOC);
        $loc_name = $location_name['name'] ?? '';

        // Seller Email
        $seller_id = $sale['user_id'];

        $stmt = $pdo->prepare("
            SELECT email
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$seller_id]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
        $seller_email = $seller['email'] ?? '';

        // Seller Name
        $stmt = $pdo->prepare("
            SELECT firstname, lastname
            FROM staffs
            WHERE email = ?
        ");
        $stmt->execute([$seller_email]);
        $seller_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $seller_fullname = ($seller_data['firstname'] ?? '') . " " . ($seller_data['lastname'] ?? '');

        // Due Date
        if ($sale['due_date'] == 0 || empty($sale['due_date'])) {
            $due_date = "00:00:00";
        } else {
            $due_date = date("M d, Y h:i A", strtotime($sale['due_date']));
        }

        echo "<tr class='border-t border-gray-100 dark:border-gray-800'>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>{$sale['invoice_number']}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>{$sale['customer_name']}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>{$loc_name}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>{$seller_fullname}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>₦{$sale['grand_total']}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>{$sale['discount_percent']}%</p></td>

<td class='px-6 py-3.5'><p class='font-medium text-success-600 text-theme-sm dark:text-white/90'>₦{$sale['discount_amount']}</p></td>

<td class='px-6 py-3.5'><p class='font-medium text-success-600 text-theme-sm dark:text-white/90'>₦{$sale['final_total']}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>{$sale['payment_status']}</p></td>

<td class='px-6 py-3.5'><p class='text-success-600 text-theme-sm dark:text-gray-400'>₦{$sale['amount_paid']}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>₦{$sale['balance']}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>{$due_date}</p></td>

<td class='px-6 py-3.5'><p class='text-gray-500 text-theme-sm dark:text-gray-400'>"
. date('M d, Y h:i A', strtotime($sale['created_at'])) .
"</p></td>

<td class='px-6 py-3.5'>
<a href='../invoices/invoice_{$sale['invoice_number']}.pdf'
target='_blank'
class='text-blue-600 hover:underline text-theme-sm'>
View
</a>
</td>

</tr>";
    }

} else {

    echo "<tr>
        <td colspan='14' class='px-6 py-4 text-center text-gray-500 dark:text-gray-400'>
            No results found
        </td>
    </tr>";
}
?>