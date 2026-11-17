<?php
include('../config/db.php');
include('user_data.php');

$sale_id = $_GET['sale_id'] ?? 0;
if(!$sale_id) exit('Invalid sale ID');

$stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

$stmt_items = $pdo->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE sale_id = ?");
$stmt_items->execute([$sale_id]);
$items = $stmt_items->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?= $sale['id'] ?></title>
</head>
<body>
<h2>Invoice #<?= $sale['id'] ?></h2>
<p><strong>Customer:</strong> <?= $sale['customer_name'] ?> | <strong>Phone:</strong> <?= $sale['customer_phone'] ?></p>
<p><strong>Notes:</strong> <?= htmlspecialchars($sale['notes']) ?></p>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
    </thead>
    <tbody>
        <?php foreach($items as $i): ?>
            <tr>
                <td><?= htmlspecialchars($i['name']) ?></td>
                <td><?= $i['quantity'] ?></td>
                <td><?= number_format($i['unit_price'],2) ?></td>
                <td><?= number_format($i['total_price'],2) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" align="right"><strong>Grand Total</strong></td>
            <td><?= number_format($sale['total_amount'],2) ?></td>
        </tr>
    </tfoot>
</table>
</body>
</html>
