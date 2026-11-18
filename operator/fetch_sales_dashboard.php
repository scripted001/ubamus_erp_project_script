<?php
include('../config/db.php');
// fetch_sales_dashboard.php
// Returns aggregated sales dashboard data in JSON format

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
    
// Optional filters
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : null;
$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : null;

// Base query conditions
$where = "WHERE 1=1";
$params = [];

if ($company_id) {
    $where .= " AND company_id = :company_id";
    $params[':company_id'] = $company_id;
}

if ($location_id) {
    $where .= " AND location_id = :location_id";
    $params[':location_id'] = $location_id;
}

try {

    // Total sales revenue
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total_revenue
        FROM sales
        $where
    ");
    $stmt->execute($params);
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

    // Total number of sales
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_sales
        FROM sales
        $where
    ");
    $stmt->execute($params);
    $total_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

    // Recent sales
    $stmt = $pdo->prepare("
        SELECT id, customer_id, total_amount, created_at
        FROM sales
        $where
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top customers by spending
    $stmt = $pdo->prepare("
        SELECT customer_id, SUM(total_amount) as total_spent
        FROM sales
        $where
        GROUP BY customer_id
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $stmt->execute($params);
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "total_revenue" => $total_revenue,
            "total_sales" => $total_sales,
            "recent_sales" => $recent_sales,
            "top_customers" => $top_customers
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
