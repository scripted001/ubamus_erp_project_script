<?php
include('../config/db.php');
include('user_data.php');

$branchid = $_SESSION['branch_id'];
$companyid = $_SESSION['company_id'];
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
    <title>Ubamus Global | New Sale</title>
    <link rel="icon" href="src/images/icons/favicon.png">
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

                                        <!-- Customer Name -->
                                        <div class="w-full px-2.5 xl:w-1/2">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer Name</label>
                                            <input type="text" name="customer_name" id="customer_name" placeholder="e.g. John Doe" required
                                                   class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />
                                        </div>

                                        <!-- Customer Mobile -->
                                        <div class="w-full px-2.5 xl:w-1/2">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer Mobile</label>
                                            <input type="text" name="customer_phone" id="customer_phone" placeholder="e.g. 08012345678" required
                                                   class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30" />
                                        </div>

                                        <!-- Category -->
                                        <div class="w-full px-2.5 xl:w-1/3">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Category</label>
                                            <select id="categorySelect" name="category_id" class="w-full px-4 py-3 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90">
                                                <option value="">-- Select Category --</option>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE parent_id IS NULL AND company_id = ?");
                                                $stmt->execute([$companyid]);
                                                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($categories as $cat) {
                                                    echo '<option value="'.$cat['id'].'">'.$cat['name'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Subcategory -->
                                        <div class="w-full px-2.5 xl:w-1/3">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Subcategory</label>
                                            <select id="subcategorySelect" class="w-full px-4 py-3 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90">
                                                <option value="">-- Select Subcategory --</option>
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
                                            <input type="number" id="qty" min="1" value="1" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" />
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
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Hidden payload -->
                                        <input type="hidden" name="branch_id" value="<?= htmlspecialchars($branchid) ?>">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($_SESSION['user_id']) ?>">
                                        <input type="hidden" name="company_id" value="<?= htmlspecialchars($companyid) ?>">
                                        <input type="hidden" name="cart_payload" id="cart_payload">

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
// --- Elements ---
const categorySelect = document.getElementById('categorySelect');
const subcategorySelect = document.getElementById('subcategorySelect');
const productSelect = document.getElementById('productSelect');
const unitPriceInput = document.getElementById('unitPrice');
const qtyInput = document.getElementById('qty');
const lineTotalInput = document.getElementById('lineTotal');
const addToCartBtn = document.getElementById('addToCartBtn');
const cartTableBody = document.querySelector('#cartTable tbody');
const grandTotalEl = document.getElementById('grandTotal');
const cartPayloadInput = document.getElementById('cart_payload');

let availableStock = 0;
let cart = [];

// --- Helpers ---
function formatNumber(n){ return parseFloat(n || 0).toFixed(2); }

function updateLineTotal(){
    let qty = parseFloat(qtyInput.value) || 0;
    let price = parseFloat(unitPriceInput.value) || 0;
    if(availableStock && qty > availableStock){
        alert(`Quantity exceeds stock: ${availableStock}`);
        qtyInput.value = availableStock;
        qty = availableStock;
    }
    lineTotalInput.value = formatNumber(qty * price);
}

// --- Category change ---
categorySelect.addEventListener('change', function(){
    const catId = this.value;
    subcategorySelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
    productSelect.innerHTML = '<option value="">-- Select Product --</option>';
    unitPriceInput.value = '';
    lineTotalInput.value = '';
    qtyInput.value = 1;
    availableStock = 0;

    if(!catId) return;

    // Fetch subcategories
    fetch(`get_subcategories.php?category_id=${catId}`)
    .then(res => res.json())
    .then(data => {
        data.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = sub.name;
            subcategorySelect.appendChild(opt);
        });
    });

    // Fetch products under category
    fetch(`get_products.php?category_id=${catId}`)
    .then(res => res.json())
    .then(data => {
        data.forEach(prod => {
            const opt = document.createElement('option');
            opt.value = prod.id;
            opt.textContent = prod.name;
            productSelect.appendChild(opt);
        });
    });
});

// --- Subcategory change ---
subcategorySelect.addEventListener('change', function(){
    const subId = this.value;
    productSelect.innerHTML = '<option value="">-- Select Product --</option>';
    unitPriceInput.value = '';
    lineTotalInput.value = '';
    qtyInput.value = 1;
    availableStock = 0;

    if(!subId) return;

    fetch(`get_products.php?subcategory_id=${subId}`)
    .then(res => res.json())
    .then(data => {
        data.forEach(prod => {
            const opt = document.createElement('option');
            opt.value = prod.id;
            opt.textContent = prod.name;
            productSelect.appendChild(opt);
        });
    });
});

// --- Product change ---
productSelect.addEventListener('change', function(){
    const productId = this.value;
    if(!productId){
        unitPriceInput.value = '';
        lineTotalInput.value = '';
        availableStock = 0;
        return;
    }

    fetch(`get_product_price.php?product_id=${productId}`)
    .then(res => res.json())
    .then(data => {
        unitPriceInput.value = formatNumber(data.selling_price);
        availableStock = parseFloat(data.quantity) || 0;
        updateLineTotal();
    });
});

// --- Quantity change ---
qtyInput.addEventListener('input', updateLineTotal);

// --- Add to cart ---
addToCartBtn.addEventListener('click', function(){
    const productId = productSelect.value;
    const productText = productSelect.options[productSelect.selectedIndex]?.text || '';
    const qty = parseFloat(qtyInput.value) || 0;
    const unit_price = parseFloat(unitPriceInput.value) || 0;

    if(!productId){ alert('Select a product'); return; }
    if(qty <= 0){ alert('Enter valid quantity'); return; }
    if(availableStock && qty > availableStock){ alert('Quantity exceeds available stock'); return; }

    const existing = cart.find(i => i.product_id == productId);
    if(existing){
        let newQty = existing.quantity + qty;
        if(newQty > availableStock){ alert(`Total quantity exceeds stock: ${availableStock}`); return; }
        existing.quantity = newQty;
        existing.total_price = existing.quantity * existing.unit_price;
    } else {
        cart.push({product_id: productId, name: productText, quantity: qty, unit_price: unit_price, total_price: qty * unit_price});
    }

    renderCart();
    qtyInput.value = 1;
    lineTotalInput.value = '';
});

// --- Render cart ---
function renderCart(){
    cartTableBody.innerHTML = '';
    let grand = 0;

    cart.forEach((item, idx) => {
        grand += parseFloat(item.total_price);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-left">${item.name}</td>
            <td>${item.quantity}</td>
            <td>${formatNumber(item.unit_price)}</td>
            <td>${formatNumber(item.total_price)}</td>
            <td><button class="btn-remove" data-idx="${idx}">Remove</button></td>
        `;
        cartTableBody.appendChild(tr);
    });

    grandTotalEl.textContent = formatNumber(grand);
    cartPayloadInput.value = JSON.stringify(cart);
}

// --- Remove cart item (event delegation) ---
cartTableBody.addEventListener('click', function(e){
    if(e.target.classList.contains('btn-remove')){
        const idx = parseInt(e.target.dataset.idx);
        cart.splice(idx, 1);
        renderCart();
    }
});

// --- Auto-add last product on submit ---
document.getElementById('saleForm').addEventListener('submit', function(e){
    const productId = productSelect.value;
    const qty = parseFloat(qtyInput.value) || 0;
    const unit_price = parseFloat(unitPriceInput.value) || 0;

    if(productId && qty > 0){
        const existing = cart.find(i => i.product_id == productId);
        if(existing){
            let newQty = existing.quantity + qty;
            if(newQty > availableStock){ alert(`Total quantity exceeds stock: ${availableStock}`); e.preventDefault(); return; }
            existing.quantity = newQty;
            existing.total_price = existing.quantity * existing.unit_price;
        } else {
            if(qty > availableStock){ alert(`Quantity exceeds stock: ${availableStock}`); e.preventDefault(); return; }
            cart.push({product_id: productId, name: productSelect.options[productSelect.selectedIndex]?.text || '', quantity: qty, unit_price: unit_price, total_price: qty * unit_price});
        }
        renderCart();
    }

    if(cart.length === 0){
        e.preventDefault();
        alert('Please add at least one product to proceed.');
        return;
    }
});
</script>

</body>
</html>
