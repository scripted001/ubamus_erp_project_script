<?php
include('../config/db.php');

$barcode = $_GET['barcode'] ?? '';

$stmt = $pdo->prepare("
SELECT p.name, s.selling_price, l.name AS location
FROM stock_in s
JOIN products p ON p.id = s.product_id
JOIN locations l ON l.id = s.location_id
WHERE s.barcode=?
");
$stmt->execute([$barcode]);
$data = $stmt->fetch();

if (!$data) die("Not found");

echo "<h2>{$data['name']}</h2>";
echo "<p>₦{$data['selling_price']}</p>";
echo "<p>Location: {$data['location']}</p>";
