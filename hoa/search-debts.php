<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


$company_id = $_SESSION['company_id'];
$location_id = $_SESSION['location_id'];

$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
SELECT 
    c.*,
    l.name AS location_name,
    l.type AS location_type,
    l.city AS location_city,
    l.state AS location_state
FROM customer_debts c
LEFT JOIN locations l ON l.id = c.location_id
WHERE c.company_id = ?
";

$params = [$company_id];

if(!empty($type) && !empty($search)){

    switch($type){

        case "invoice_number":
            $sql .= " AND c.invoice_number LIKE ?";
            $params[] = "%$search%";
        break;

        case "debt_type":
            $sql .= " AND c.debt_type = ?";
            $params[] = $search;
        break;

    }

}

$sql .= " ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($expenses as $exp){
?>



<?php } ?>