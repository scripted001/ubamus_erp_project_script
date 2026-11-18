<?php
session_start();
include('../config/db.php');

$company_id = $_SESSION['company_id'];

$option = $_POST['search_option'] ?? '';
$value  = $_POST['search_value'] ?? '';

$query = "SELECT * FROM staffs WHERE company_id = ?";
$params = [$company_id];

if($value != ""){

if($option == "fullname"){
$query .= " AND CONCAT(firstname,' ',lastname) LIKE ?";
$params[] = "%$value%";
}

elseif($option == "firstname"){
$query .= " AND firstname LIKE ?";
$params[] = "%$value%";
}

elseif($option == "lastname"){
$query .= " AND lastname LIKE ?";
$params[] = "%$value%";
}

elseif($option == "phone"){
$query .= " AND phone LIKE ?";
$params[] = "%$value%";
}

elseif($option == "email"){
$query .= " AND email LIKE ?";
$params[] = "%$value%";
}

}

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($staffs){

foreach($staffs as $branch){

?>

<div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-100 dark:border-gray-800 p-4 space-y-2">

<div class="table-card">
<span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Fullname:</span>
<?= htmlspecialchars($branch['firstname']) ?> <?= htmlspecialchars($branch['lastname']) ?>
</div>

<div class="table-card">
<span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Address:</span>
<?= htmlspecialchars($branch['address']) ?>
</div>

<div class="table-card">
<span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Phone:</span>
<?= htmlspecialchars($branch['phone']) ?>
</div>

<div class="table-card">
<span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Email:</span>
<?= htmlspecialchars($branch['email']) ?>
</div>

<div class="table-card">
<span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Role:</span>
<?= htmlspecialchars($branch['role']) ?>
</div>

<div class="table-card">
<span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Account Status:</span>
<?= htmlspecialchars($branch['status']) ?>
</div>

<div class="text-right" style="width:100%;height:10%;display:flex;justify-content:space-around">

<form method="POST" action="staff.php">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">View</button>
</form>

<form method="POST" action="suspend-staff.php">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">Suspend</button>
</form>

<form method="POST" action="activate-staff.php">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">Activate</button>
</form>

<form method="POST" action="delete_staff.php">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">Delete</button>
</form>

</div>

</div>

<?php
}

}else{

echo "<div style='padding:15px;text-align:center'>No staff found</div>";

}
?>