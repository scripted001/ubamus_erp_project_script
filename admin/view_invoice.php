<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

$sales_id = $_GET['sales_id'] ?? null;

if (!$sales_id) {
    die("Invalid sales ID");
}
/* =========================
   FETCH SALE (WITH RELATIONS)
========================= */
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.company_id,
        s.location_id,
        s.user_id,

        s.invoice_number,
        s.customer_name,
        s.customer_phone,
        s.customer_email,

        s.total_amount,
        s.amount_paid,
        s.balance,
        s.payment_status,
        s.due_date,
        s.created_at,

        CONCAT(COALESCE(st.firstname,''), ' ', COALESCE(st.lastname,'')) AS staff_name,
        st.email AS staff_email,
        st.phone AS staff_phone,

        l.name AS location_name

    FROM sales s

    LEFT JOIN users u 
        ON u.id = s.user_id

    LEFT JOIN staffs st 
        ON st.email = u.email

    LEFT JOIN locations l 
        ON l.id = s.location_id

    WHERE s.id = ?
    AND s.company_id = ?
    LIMIT 1
");

$stmt->execute([$sales_id, $company_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("Sale not found or access denied");
}

/* =========================
   FETCH ITEMS
========================= */
$stmt = $pdo->prepare("
    SELECT 
        si.quantity,
        si.unit_price,
        (si.quantity * si.unit_price) AS subtotal,
        p.name AS product_name
    FROM sale_items si
    INNER JOIN products p ON p.id = si.product_id
    WHERE si.sale_id = ?
");

$stmt->execute([$sales_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   INVOICE FILE CHECK
========================= */
$invoice_file = "../invoices/invoice_" . $sale['invoice_number'] . ".pdf";
$invoice_exists = file_exists($invoice_file);

/* =========================
   FETCH COMPANY INFO
========================= */
$stmt = $pdo->prepare("
    SELECT 
        company_name,
        company_logo,
        company_email,
        company_mobile,
        company_office_address
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

$logo_path = !empty($company['company_logo']) 
    ? "../uploads/logos/" . $company['company_logo'] 
    : null;
?>

<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($sale['invoice_number']) ?></title>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 30px;
    background: #f8f9fa;
    color: #111;
}

.invoice-box {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    max-width: 950px;
    margin: auto;
    box-shadow: 0 0 12px rgba(0,0,0,0.08);
}

.header {
    display: flex;
    justify-content: space-between;
    border-bottom: 2px solid #eee;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th {
    background: #f1f1f1;
    padding: 10px;
    text-align: left;
}

td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.summary {
    margin-top: 20px;
}

.total {
    font-size: 20px;
    font-weight: bold;
    text-align: right;
    margin-top: 15px;
}

.btns {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    border-radius: 6px;
    color: #fff;
    text-decoration: none;
}

.print { background: #007bff; }
.reminder { background: #dc3545; }
.viewpdf { background: #28a745; }

@media print {
    .btns { display: none; }
}
</style>
</head>

<body>

<div class="invoice-box">
<?php if ($logo_path && file_exists($logo_path)): ?>
            <img src="<?= $logo_path ?>" 
                 style="width:120px; height:120px; object-fit:contain; border-radius:8px;">
        <?php endif; ?>


<div class="header">


    <!-- COMPANY -->
    <div style="display:flex; align-items:center; gap:15px;">
        
        
        <div>
            <h2><?= htmlspecialchars($company['company_name'] ?? 'Company') ?></h2>

            <p style="margin:2px 0;">
                <b>Email:</b> <?= $company['company_email'] ?? 'N/A' ?>
            </p>

            <p style="margin:2px 0;">
                <b>Phone:</b> <?= $company['company_mobile'] ?? 'N/A' ?>
            </p>

            <p style="margin:2px 0;">
                <b>Address:</b> <?= $company['company_office_address'] ?? 'N/A' ?>
            </p>
        </div>

    </div>

    <!-- INVOICE INFO -->
    <div style="text-align:right;">
        <h3>INVOICE</h3>
        <p><b>#:</b> <?= $sale['invoice_number'] ?></p>
        <p><b>Date:</b> <?= $sale['created_at'] ?></p>
    </div>

</div>
<!-- STAFF + LOCATION -->
<div class="summary">
    <p><b>Sold by:</b> <?= $sale['staff_name'] ?> 
    (<?= $sale['staff_email'] ?? 'N/A' ?> | <?= $sale['staff_phone'] ?? 'N/A' ?>)</p>

    <p><b>Location:</b> <?= $sale['location_name'] ?? 'N/A' ?></p>
</div>

<!-- ITEMS -->
<table>
<thead>
<tr>
    <th>Product</th>
    <th>Qty</th>
    <th>Unit Price</th>
    <th>Total</th>
</tr>
</thead>

<tbody>
<?php foreach ($items as $item): ?>
<tr>
    <td><?= $item['product_name'] ?></td>
    <td><?= $item['quantity'] ?></td>
    <td>₦<?= number_format($item['unit_price'], 2) ?></td>
    <td>₦<?= number_format($item['subtotal'], 2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- PAYMENT -->
<div class="summary">

    <p><b>Total:</b> ₦<?= number_format($sale['total_amount'], 2) ?></p>
    <p><b>Deposit Paid:</b> ₦<?= number_format($sale['amount_paid'], 2) ?></p>
    <p><b>Balance:</b> ₦<?= number_format($sale['balance'], 2) ?></p>
    <p><b>Status:</b> <?= strtoupper($sale['payment_status']) ?></p>

    <?php if ($sale['payment_status'] === 'partial'): ?>
        <p><b>Due Date:</b> <?= $sale['due_date'] ?? 'Not set' ?></p>
    <?php endif; ?>

</div>

<!-- TOTAL -->
<div class="total">
    Total: ₦<?= number_format($sale['total_amount'], 2) ?>
</div>

<!-- ACTIONS -->
<div class="btns">

    <button class="btn print" onclick="window.print()">
        Print
    </button>

    <?php if ($invoice_exists): ?>
        <a class="btn viewpdf" href="<?= $invoice_file ?>" target="_blank">
            View PDF Invoice
        </a>
    <?php endif; ?>

    <?php if ($sale['payment_status'] === 'partial'): ?>
        <form method="POST" action="send_debt_reminder.php">
            <input type="hidden" name="sales_id" value="<?= $sale['id'] ?>">
            <button type="submit" class="btn reminder">
                Send Payment Reminder
            </button>
        </form>
    <?php endif; ?>

</div>

</div>

</body>
</html>