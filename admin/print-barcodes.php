<?php
include('../config/db.php');

$barcode = $_GET['barcode'] ?? '';
if (!$barcode) die('No barcode');

$stmt = $pdo->prepare("
    SELECT si.barcode, si.barcode_image, p.name
    FROM stock_in si
    JOIN products p ON p.id = si.product_id
    WHERE si.barcode = ?
");
$stmt->execute([$barcode]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) die('Barcode not found');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Barcode</title>
    <style>
        body { font-family: Arial; }
        .label {
            width: 30%;
            border: 1px dashed #ccc;
            padding: 10px;
            margin: 5px;
            text-align: center;
            float: left;
        }
        img { width: 220px; }
        @media print {
            .label { page-break-inside: avoid; }
        }
    </style>
</head>
<body onload="window.print()">

<?php for ($i = 0; $i < 12; $i++): ?>
<div class="label">
    <strong><?= htmlspecialchars($item['name']) ?></strong><br>
    <img src="<?= htmlspecialchars($item['barcode_image']) ?>"><br>
    <small><?= htmlspecialchars($item['barcode']) ?></small>
</div>
<?php endfor; ?>

</body>
</html>
