<?php

include('../config/db.php');
session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

/* ==========================
   COMPANY ID
========================== */

$stmt = $pdo->prepare("
    SELECT company_id
    FROM users
    WHERE id = ?
");

$stmt->execute([$user_id]);

$company_id = $stmt->fetchColumn();

if (!$company_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Company not found'
    ]);
    exit;
}

/* ==========================
   FILTERS
========================== */

$invoice      = trim($_POST['invoice'] ?? '');
$customer     = trim($_POST['customer'] ?? '');
$date_from    = trim($_POST['date_from'] ?? '');
$date_to      = trim($_POST['date_to'] ?? '');
$cashier      = trim($_POST['cashier'] ?? '');
$amount_min   = trim($_POST['amount_min'] ?? '');
$amount_max   = trim($_POST['amount_max'] ?? '');

/* ==========================
   DYNAMIC WHERE CLAUSE
========================== */

$where = ["s.company_id = ?"];
$params = [$company_id];

if (!empty($invoice)) {
    $where[] = "s.invoice_number LIKE ?";
    $params[] = "%{$invoice}%";
}

if (!empty($customer)) {
    $where[] = "s.customer_name LIKE ?";
    $params[] = "%{$customer}%";
}

if (!empty($cashier)) {
    $where[] = "s.user_id = ?";
    $params[] = $cashier;
}

if (!empty($date_from)) {
    $where[] = "DATE(s.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "DATE(s.created_at) <= ?";
    $params[] = $date_to;
}

if (!empty($amount_min)) {
    $where[] = "s.final_total >= ?";
    $params[] = $amount_min;
}

if (!empty($amount_max)) {
    $where[] = "s.final_total <= ?";
    $params[] = $amount_max;
}

$whereSql = implode(' AND ', $where);

