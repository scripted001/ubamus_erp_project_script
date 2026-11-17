<?php
if (!isset($_GET['codes'])) die("No barcodes");

$codes = explode(',', $_GET['codes']);
?>
<!DOCTYPE html>
<html>
<head>
<title>Print Barcodes</title>
<style>
body { font-family: Arial; }
.label {
  width: 180px;
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
  display: inline-block;
  margin: 10px;
}
img { max-width: 100%; }
@media print {
  button { display: none; }
}
</style>
</head>
<body>

<button onclick="window.print()">Print</button>

<?php foreach ($codes as $c): ?>
<div class="label">
  <img src="../barcodes/<?= $c ?>.png"><br>
  <small><?= $c ?></small>
</div>
<?php endforeach; ?>

</body>
</html>
