<?php
include('../config/db.php');
include('user_data.php');
include('../company_data.php');

$location_id = $_SESSION['location_id'];
$company_id = $_SESSION['company_id'] ?? null;
$user_id = $_SESSION['user_id'];


$user_id = $_SESSION['user_id'];

// get user email
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_email = $user['email'] ?? null;

// get staff id from email
$stmt = $pdo->prepare("SELECT id FROM staffs WHERE email = ?");
$stmt->execute([$user_email]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$staff_id = $staff['id'] ?? null;

// echo $company_id;
// echo $location_id;
// echo $user_id;
// echo $staff_id;

if (!$company_id || !$staff_id) {
    die("Missing company or staff ID. company=$company_id staff=$staff_id");
}

?>
<?php if (!empty($_SESSION['sale_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['sale_success']; unset($_SESSION['sale_success']); ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['sale_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['sale_error']; unset($_SESSION['sale_error']); ?></div>
<?php endif; ?>



<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title><?php echo $company_name; ?> |  Staff Area</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <link href="cart.css" rel="stylesheet">
</head>

<body x-data="{ page: 'ecommerce', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
      x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
      :class="{'dark bg-gray-900': darkMode === true}">

<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="grid grid-cols-6 gap-4 md:gap-6">
                    <div class="col-span-12 space-y-6 xl:col-span-7">
    
                        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                                <!-- FORM SUBMITS DIRECTLY TO create_sale.php -->
                                <form id="saleForm" method="POST" action="create_sale.php">

                                    <div class="-mx-2.5 flex flex-wrap gap-y-5">
                                    
<!-- CUSTOMER TYPE -->
<div class="w-full px-2.5">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        Customer Type
    </label>

    <div class="flex items-center gap-6 text-sm">
        <label class="flex items-center gap-2">
            <input type="radio" name="customer_type" value="new" checked id="newCustomerRadio">
            New Customer
        </label>

        <label class="flex items-center gap-2">
            <input type="radio" name="customer_type" value="existing" id="existingCustomerRadio">
            Existing Customer
        </label>
    </div>
</div>
<br>
<!-- NEW CUSTOMER FIELDS -->
<div id="newCustomerFields" class="w-full flex flex-wrap">
    <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer Name</label>
        <input type="text" name="customer_name" id="customer_name" placeholder="e.g. John Doe"
            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" />
    </div>

    <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer Mobile</label>
        <input type="number" name="customer_phone" id="customer_phone" placeholder="e.g. 08012345678"
            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" />
    </div>
<br>
    <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer Email</label>
        <input type="email" name="customer_email_new" id="customer_email_new" placeholder="e.g. johndoe@example.com"
            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" />
    </div>
</div>
<br>

<!-- EXISTING CUSTOMER FIELDS -->
<div id="existingCustomerFields" class="w-full flex flex-wrap hidden">
    <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Select Existing Customer</label>
        <select id="existingCustomerSelect" class="w-full px-4 py-3 text-sm bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11">
            <option value="">-- Choose Customer --</option>
            <?php
            $stmt = $pdo->prepare("SELECT id, fullname, phone, email FROM customers WHERE company_id = ? AND location_id = ?");
            $stmt->execute([$company_id, $location_id]);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($customers as $cus) {
                echo '<option value="'.$cus['id'].'" data-name="'.$cus['fullname'].'" data-phone="'.$cus['phone'].'" data-email="'.$cus['email'].'">'
                        .$cus['fullname'].' — '.$cus['phone'].
                     '</option>';
            }
            ?>
        </select>
    </div>

    <div class="w-full px-2.5 xl:w-1/4">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Name</label>
        <input type="text" id="ex_name" readonly class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-gray-100" />
    </div>

    <div class="w-full px-2.5 xl:w-1/4">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Phone</label>
        <input type="number" id="ex_phone" readonly class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-gray-100" />
    </div>
<br>
    <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
        <input type="email" id="ex_email" readonly class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-gray-100" />
        <!-- Hidden field for submission -->
        <input type="hidden" name="customer_email" id="hidden_customer_email">
    </div>

    <!-- Hidden chosen customer_id -->
    <input type="hidden" name="existing_customer_id" id="customer_id">
</div>
<br>

                                        <!-- Category -->
                                        <div class="w-full px-2.5 xl:w-1/3">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Category</label>
                                            <select id="categorySelect" name="category_id" class="w-full px-4 py-3 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90">
                                                <option value="">-- Select Category --</option>
                                                <?php
                                               $stock_type = "Sales Only";

                                               $stmt = $pdo->prepare("
                                               SELECT * 
                                               FROM product_categories 
                                               WHERE parent_id IS NULL 
                                               AND company_id = ? 
                                               AND stock_type = ?
                                               ");
                                               
                                               $stmt->execute([$company_id, $stock_type]);
                                               $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($categories as $cat) {
                                                    echo '<option value="'.$cat['id'].'">'.$cat['name'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Product -->
                                        <div class="w-full px-2.5 xl:w-1/3">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Product</label>
                                            <select id="productSelect" class="w-full px-4 py-3 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90">
                                                <option value="">-- Select Product --</option>
                                            </select>
                                        </div>

                                        <!-- Unit Price -->
                                        <div class="w-full px-2.5 xl:w-1/6">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Unit Price (₦)</label>
                                            <input type="text" id="unitPrice" readonly class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" />
                                        </div>

                                        <!-- Quantity -->
                                        <div class="w-full px-2.5 xl:w-1/6">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Qty</label>
                                            <input type="number" id="qty" min="1" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" />
                                        </div>

                                        <!-- Line Total -->
                                        <div class="w-full px-2.5 xl:w-1/6">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Line Total (₦)</label>
                                            <input type="text" id="lineTotal" readonly class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" />
                                        </div>
                                        <!-- Add to Cart -->
                                        <div class="w-full px-2.5 xl:w-1/6 flex items-end">
                                            <button type="button" id="addToCartBtn" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 hover:bg-brand-600">Add to Cart</button>
                                        </div>

                                        <!-- Cart Table -->
                                        <div class="w-full px-2.5 cart-table">
                                            <div class="cart-head">
                                                <h3 class="text-sm font-semibold mb-2">Customer Cart</h3>
                                            </div>
                                            <div class="overflow-x-auto table-box">
                                                <table class="w-full text-sm" id="cartTable">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-left">Product</th>
                                                            <th>Qty</th>
                                                            <th>Unit</th>
                                                            <th>Total</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="3" class="text-right font-semibold">Grand Total</td>
                                                            <td id="grandTotal">0.00</td>
                                                            <td></td>
                                                        </tr>
                                                        <!-- DISCOUNT -->
<tr>
    <td colspan="3" class="text-right font-semibold">Discount (%)</td>
    <td>
    <input
    type="number"
    id="discount"
    name="discount"
    min="0"
    max="100"
    value="0"
    placeholder="Discount %"
    class="w-full border rounded px-2 py-1 text-sm"
/>
    </td>
    <td></td>
</tr>

<tr>
    <td colspan="3" class="text-right font-semibold">
        Discount Amount
    </td>
    <td id="discountAmount">0.00</td>
    <td></td>
</tr>

<tr>
    <td colspan="3" class="text-right font-semibold">Final Total</td>
    <td id="finalTotal">0.00</td>
    <td></td>
</tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Hidden payload -->
                                        <input type="hidden" name="location_id" value="<?= htmlspecialchars($location_id) ?>">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($_SESSION['user_id']) ?>">
                                        <input type="hidden" name="company_id" value="<?= htmlspecialchars($company_id) ?>">
                                        <input type="hidden" name="cart_payload" id="cart_payload">
                                        
                                        <!-- Payment status section -->
                                        <!-- PAYMENT STATUS -->
<div class="w-full px-2.5 mt-4">
    <label class="mb-1.5 block text-sm font-medium">
        Payment Status
    </label>

    <select id="payment_status" name="payment_status"
        class="h-11 w-full rounded-lg border px-4 py-2.5 text-sm">
        <option value="">-- Select Payment Status --</option>
        <option value="full">Full Payment</option>
        <option value="partial">Part Payment</option>
    </select>
</div>

<!-- PART PAYMENT BOX -->
<div id="partialPaymentBox" style="display:none;" class="w-full mt-3">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div>
            <label class="text-sm">Amount Paid</label>
            <input type="number" id="amount_paid" name="amount_paid"
                class="h-11 w-full border rounded-lg px-3 text-sm" />
        </div>

        <div>
            <label class="text-sm">Balance</label>
            <input type="text" id="balance" name="balance" readonly
                class="h-11 w-full border rounded-lg px-3 bg-gray-100 text-sm" />
        </div>

        <div>
            <label class="text-sm">Due Date</label>
            <input type="date" id="due_date" name="due_date"
                class="h-11 w-full border rounded-lg px-3 text-sm" />
        </div>

    </div>
</div>
                                        <!-- Sale Note -->
<div class="w-full px-2.5">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Note</label>
    <textarea name="sale_note" id="sale_note" rows="3" placeholder="Enter any notes for this sale..."
        class="h-auto w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:bg-dark-900 dark:text-white/90"></textarea>
</div>
<br>

                                        <!-- Complete Sale -->
                                        <div class="w-full px-2.5">
                                            <div class="flex items-center gap-3 mt-1">
                                                <button type="submit" id="submitSaleBtn" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 hover:bg-brand-600">Complete Sale</button>
                                                <a href="sales.php" class="flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800">Cancel</a>
                                            </div>
                                        </div>

                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script>
// --- عناصر ---
const categorySelect = document.getElementById('categorySelect');
const productSelect = document.getElementById('productSelect');
const unitPriceInput = document.getElementById('unitPrice');
const qtyInput = document.getElementById('qty');
const lineTotalInput = document.getElementById('lineTotal');
const addToCartBtn = document.getElementById('addToCartBtn');
const cartTableBody = document.querySelector('#cartTable tbody');
const grandTotalEl = document.getElementById('grandTotal');
const cartPayloadInput = document.getElementById('cart_payload');
const discountInput = document.getElementById('discount');
const finalTotalEl = document.getElementById('finalTotal');
const discountAmountEl = document.getElementById('discountAmount');

let availableStock = 0;
let cart = [];

function formatNumber(n){ return parseFloat(n || 0).toFixed(2); }

// ---------------- CATEGORY → PRODUCTS ----------------
categorySelect.addEventListener('change', function(){
    const catId = this.value;

    productSelect.innerHTML = '<option value="">-- Select Product --</option>';
    resetInputs();

    if(!catId) return;
    fetch(`get_products.php?category_id=${catId}`)
.then(res => res.json())
.then(data => {
    productSelect.innerHTML =
        '<option value="">-- Select Product --</option>';

    data.forEach(prod => {
        const opt = document.createElement('option');
        opt.value = prod.id;
        opt.textContent = prod.name;
        productSelect.appendChild(opt);
    });
})
.catch(err => {
    console.error(err);
});
});

// ---------------- PRODUCT → PRICE + STOCK ----------------
productSelect.addEventListener('change', function() {
    const productId = this.value;

    if (!productId) {
        resetInputs();
        return;
    }

    fetch(`get_product_price.php?product_id=${productId}`)
    .then(res => res.json())
    .then(data => {
        const price = parseFloat(data.selling_price) || 0;
        availableStock = parseFloat(data.quantity) || 0;

        unitPriceInput.value = formatNumber(price);
        updateLineTotal();
    })
    .catch(() => {
        unitPriceInput.value = 0;
        availableStock = 0;
    });
});

// ---------------- TOTAL ----------------
function updateLineTotal() {

    let qty = parseFloat(qtyInput.value) || 0;

    if (availableStock > 0 && qty > availableStock) {
        alert(`Only ${availableStock} unit(s) available in stock`);
        qtyInput.value = availableStock;
        qty = availableStock;
    }

    const price = parseFloat(unitPriceInput.value) || 0;

    lineTotalInput.value = formatNumber(qty * price);
}

// Stock Quantity Validation

qtyInput.addEventListener('input', updateLineTotal);
discountInput.addEventListener('input', function () {
    if (this.value > 100) {
        this.value = 100;
    }

    if (this.value < 0) {
        this.value = 0;
    }

    renderCart();
});


// ---------------- ADD TO CART ----------------
// ---------------- ADD TO CART ----------------
addToCartBtn.addEventListener('click', function(){

    const productId   = productSelect.value;
    const productText = productSelect.options[productSelect.selectedIndex]?.text || '';
    const qty         = parseFloat(qtyInput.value) || 0;
    const unit_price  = parseFloat(unitPriceInput.value) || 0;

    if(!productId){
        alert('Please select a product');
        return;
    }

    if(qty <= 0){
        alert('Please enter a valid quantity');
        return;
    }

    const existing = cart.find(i => i.product_id == productId);

    // Quantity already in cart
    const existingQty = existing ? existing.quantity : 0;

    // Total requested quantity
    const totalQtyRequested = existingQty + qty;

    if(totalQtyRequested > availableStock){

        alert(
            `Only ${availableStock} unit(s) available in stock.\n` +
            `Already in cart: ${existingQty}\n` +
            `Requested: ${qty}`
        );

        return;
    }

    if(existing){

        existing.quantity = totalQtyRequested;
        existing.total_price = existing.quantity * existing.unit_price;

    }else{

        cart.push({
            product_id: productId,
            name: productText,
            quantity: qty,
            unit_price: unit_price,
            total_price: qty * unit_price
        });

    }

    renderCart();

});
// ---------------- RENDER ----------------
function renderCart(){
    cartTableBody.innerHTML = '';
    let grand = 0;

    cart.forEach((item, idx) => {
        grand += item.total_price;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${formatNumber(item.unit_price)}</td>
            <td>${formatNumber(item.total_price)}</td>
            <td><button data-idx="${idx}" class="btn-remove">Remove</button></td>
        `;
        cartTableBody.appendChild(tr);
    });

    const discountPercent = parseFloat(discountInput.value) || 0;

    const discountAmount = (grand * discountPercent) / 100;

    const finalTotal = Math.max(grand - discountAmount, 0);

    grandTotalEl.textContent = formatNumber(grand);
    finalTotalEl.textContent = formatNumber(finalTotal);

    cartPayloadInput.value = JSON.stringify({
        items: cart,
        grand_total: grand,
        discount_percent: discountPercent,
        discount_amount: discountAmount,
        final_total: finalTotal 
    });

    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.onclick = function () {
            cart.splice(this.dataset.idx, 1);
            renderCart();
        };
    });

   
    discountAmountEl.textContent = formatNumber(discountAmount);

    const paymentStatus = document.getElementById('payment_status');
const amountPaid = document.getElementById('amount_paid');
const balanceInput = document.getElementById('balance');

if (paymentStatus.value === 'partial') {
    const paid = parseFloat(amountPaid.value) || 0;
    balanceInput.value = (finalTotal - paid).toFixed(2);
}

if (paymentStatus.value === 'full') {
    amountPaid.value = finalTotal.toFixed(2);
}
}

// ---------------- HELPERS ----------------
function resetInputs(){
    unitPriceInput.value = '';
    qtyInput.value = 1;
    lineTotalInput.value = '';
    availableStock = 0;
}

// ---------------- SUBMIT ----------------
document.getElementById('saleForm').addEventListener('submit', function(e){

    if(cart.length === 0){
        e.preventDefault();
        alert('Add at least one product');
        return;
    }

    const paymentStatus =
        document.getElementById('payment_status').value;

    if(!paymentStatus){
        e.preventDefault();
        alert('Select payment status');
        return;
    }

    if(newRadio.checked){

        const customerName =
            document.getElementById('customer_name').value.trim();

        if(customerName === ''){
            e.preventDefault();
            alert('Enter customer name');
            return;
        }
    }

    if(existingRadio.checked && !customerIdInput.value){
        e.preventDefault();
        alert('Select an existing customer');
        return;
    }

    if(paymentStatus === 'partial'){

        const amountPaid =
            parseFloat(document.getElementById('amount_paid').value) || 0;

        const finalTotal =
            parseFloat(document.getElementById('finalTotal').textContent) || 0;

        const dueDate =
            document.getElementById('due_date').value;

        if(amountPaid <= 0){
            e.preventDefault();
            alert('Enter amount paid');
            return;
        }

        if(amountPaid >= finalTotal){
            e.preventDefault();
            alert('Amount paid must be less than total for part payment');
            return;
        }

        if(!dueDate){
            e.preventDefault();
            alert('Select a due date');
            return;
        }
    }

});

// CUSTOMER TYPE SWITCH
const newRadio = document.getElementById('newCustomerRadio');
const existingRadio = document.getElementById('existingCustomerRadio');

const newFields = document.getElementById('newCustomerFields');
const exFields = document.getElementById('existingCustomerFields');

const existingSelect = document.getElementById('existingCustomerSelect');
const exName = document.getElementById('ex_name');
const exPhone = document.getElementById('ex_phone');
const exEmail = document.getElementById('ex_email');
const hiddenEmail = document.getElementById('hidden_customer_email');
const customerIdInput = document.getElementById('customer_id');

newRadio.addEventListener('change', () => {
    newFields.classList.remove('hidden');
    exFields.classList.add('hidden');

    customerIdInput.value = "";
    exName.value = "";
    exPhone.value = "";
    exEmail.value = "";
    hiddenEmail.value = "";
});


existingRadio.addEventListener('change', () => {
    newFields.classList.add('hidden');
    exFields.classList.remove('hidden');

    // Clear new inputs
    document.getElementById('customer_name').value = "";
    document.getElementById('customer_phone').value = "";
    document.getElementById('customer_email_new').value = "";
});

// Autofill existing customer
existingSelect.addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];

    const name = selected.getAttribute('data-name') || "";
    const phone = selected.getAttribute('data-phone') || "";
    const email = selected.getAttribute('data-email') || "";
    const id = selected.value;

    exName.value = name;
    exPhone.value = phone;
    exEmail.value = email;
    hiddenEmail.value = email;  // submit
    customerIdInput.value = id;
});

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const paymentStatus = document.getElementById('payment_status');
    const partialBox = document.getElementById('partialPaymentBox');
    const amountPaid = document.getElementById('amount_paid');
    const balanceInput = document.getElementById('balance');
    const grandTotalEl = document.getElementById('grandTotal');

    function getTotal() {
        return parseFloat(finalTotalEl.textContent) || 0;
    }

    paymentStatus.addEventListener('change', function () {
        const total = getTotal();

        if (this.value === 'partial') {
            partialBox.style.display = 'block';   // 👈 FORCE SHOW
            amountPaid.value = '';
            balanceInput.value = total.toFixed(2);
        } 
        else if (this.value === 'full') {
            partialBox.style.display = 'none';    // 👈 FORCE HIDE
            amountPaid.value = total;
            balanceInput.value = 0;
        } 
        else {
            partialBox.style.display = 'none';
        }
    });

    amountPaid.addEventListener('input', function () {
        const total = getTotal();
        const paid = parseFloat(this.value) || 0;

        balanceInput.value = (total - paid).toFixed(2);
    });

});
</script>

</body>
</html>
