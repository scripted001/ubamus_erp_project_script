<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'] ?? null;
$status = "Unfinished";

$search_by = $_GET['search_by'] ?? '';
$search_value = $_GET['search_value'] ?? '';

$sql = "SELECT * FROM stock_production WHERE company_id = :company_id AND status = :status";
$params = ['company_id' => $company_id, 'status' => $status];

if(!empty($search_value)){
    if($search_by === 'batch_number'){
        $sql .= " AND batch_number LIKE :value";
        $params['value'] = "%$search_value%";
    } elseif($search_by === 'product_name'){
        $sql .= " AND product_name = :value";
        $params['value'] = $search_value;
    }
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($categories){
    foreach($categories as $branch){
        echo '<div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-100 dark:border-gray-800 p-4 space-y-2">';
        echo '<div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Product Name:</span> ' . htmlspecialchars($branch['product_name']) . '</div>';
        echo '<div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Batch Number:</span> ' . htmlspecialchars($branch['batch_number']) . '</div>';
        echo '<div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Expenses Breakdown:</span> ' . htmlspecialchars($branch['expenses_breakdown']) . '</div>';
        echo '<div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Quantity Produced:</span> ' . htmlspecialchars($branch['quantity_produced']) . '</div>';
       echo '
       <div class="table-card">
        
       </div>';

        echo '<div class="text-right" style="display: flex; justify-content: space-around; align-items: center;">';
        // View button
        echo '<form method="POST" action="category.php">';
        echo '<input type="hidden" name="category_id" value="'.htmlspecialchars($branch['id']).'">';
        echo '<button name="submit" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-eye fa-1x"></i></button>';
        echo '</form>';

        // Delete button
        echo '<form method="POST" action="delete_category.php">';
        echo '<input type="hidden" name="category_id" value="'.htmlspecialchars($branch['id']).'">';
        echo '<button name="submit" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-trash fa-1x"></i></button>';
        echo '</form>';

        echo '</div></div>';
    }
} else {
    echo '<div class="text-gray-500 dark:text-gray-400 text-center">No categories found.</div>';
}