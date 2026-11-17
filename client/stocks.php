<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id  = $_SESSION['company_id'] ?? 1;
$user_id     = $_SESSION['user_id'] ?? null;


// ----------------------
// KPI CALCULATIONS
// ----------------------

$totalProductsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.id)
    FROM products p
    INNER JOIN stocks s ON s.product_id = p.id
    WHERE p.company_id = ?
");

$totalProductsStmt->execute([$company_id]);
$totalProducts = $totalProductsStmt->fetchColumn();


// Total Quantity
$totalQuantity = $pdo->prepare("
    SELECT COALESCE(SUM(quantity),0) 
    FROM stocks 
    WHERE company_id=?
");
$totalQuantity->execute([$company_id]);
$totalQuantity = $totalQuantity->fetchColumn();


// Total Stock Value (uses latest product_units price)
$totalStockValue = $pdo->prepare("
    SELECT COALESCE(SUM(s.quantity * p.cost_price),0)
    FROM stocks s
    INNER JOIN products p ON s.product_id = p.id
    
    WHERE s.company_id=?
");
$totalStockValue->execute([$company_id]);
$totalStockValue = $totalStockValue->fetchColumn();


// Total Stock Profit (uses latest product_units price)
$totalStockProfit = $pdo->prepare("
    SELECT COALESCE(SUM((p.selling_price - p.cost_price) * s.quantity),0)
    FROM stocks s
    INNER JOIN products p ON s.product_id = p.id
   
    WHERE s.company_id=?
");
$totalStockProfit->execute([$company_id]);
$totalStockProfit = $totalStockProfit->fetchColumn();


// Total product categories
$totalCategories = $pdo->prepare("
    SELECT COUNT(*) 
    FROM product_categories 
    WHERE company_id=?
");
$totalCategories->execute([$company_id]);
$totalCategories = $totalCategories->fetchColumn();


// ----------------------
// STOCK TABLE (FIXED JOIN LOGIC)
// ----------------------
$stocksStmt = $pdo->prepare("
    SELECT 
        s.id,
        p.name,
        p.barcode,
        s.quantity,
        s.created_at,
        s.location_id,

        p.cost_price,
        p.selling_price,
        p.stock_type,
        u.unit_name,

        l.name AS location_name

    FROM stocks s

    INNER JOIN products p 
        ON s.product_id = p.id

    INNER JOIN locations l 
        ON s.location_id = l.id

    LEFT JOIN units u 
        ON p.unit_id = u.id

    WHERE s.company_id = ?
    ORDER BY p.name ASC
");

$stocksStmt->execute([$company_id]);
$stocks = $stocksStmt->fetchAll();


// ----------------------
// CATEGORY CHART DATA
// ----------------------
$categoriesStmt = $pdo->prepare("
    SELECT 
        c.name AS category,
        COALESCE(SUM(s.quantity),0) AS total
    FROM product_categories c
    LEFT JOIN products p 
        ON p.category_id = c.id 
        AND p.company_id = ?
    LEFT JOIN stocks s 
        ON s.product_id = p.id 
        AND s.company_id = ?
    GROUP BY c.id
");
$categoriesStmt->execute([$company_id, $company_id]);
$categoriesDataArr = $categoriesStmt->fetchAll();

$categoriesLabels = array_column($categoriesDataArr, 'category');
$categoriesData   = array_column($categoriesDataArr, 'total');


// ----------------------
// STOCK TREND (UNCHANGED)
// ----------------------
$trendStmt = $pdo->prepare("
    SELECT 
        DATE(s.created_at) AS date, 
        COALESCE(SUM(s.quantity),0) AS total
    FROM stocks s
    WHERE s.company_id=? 
      AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(s.created_at)
    ORDER BY DATE(s.created_at) DESC
");
$trendStmt->execute([$company_id]);
$trendDataArr = $trendStmt->fetchAll();

$trendLabels = array_column($trendDataArr, 'date');
$trendData   = array_column($trendDataArr, 'total');



?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
      <title>
        <?php echo $company_name; ?> |  Staff Area
      </title>
       
        <link rel="stylesheet" href="../fontawesome/css/all.css">
        <link href="style.css" rel="stylesheet"><script data-cfasync="false" nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86">try{(function(w,d){!function(fv,fw,fx,fy){if(fv.zaraz)console.error("zaraz is loaded twice");else{fv[fx]=fv[fx]||{};fv[fx].executed=[];fv.zaraz={deferred:[],listeners:[]};fv.zaraz._v="5858";fv.zaraz._n="ff1f9a24-18a4-4614-8917-7c27c5c96a86";fv.zaraz.q=[];fv.zaraz._f=function(fz){return async function(){var fA=Array.prototype.slice.call(arguments);fv.zaraz.q.push({m:fz,a:fA})}};for(const fB of["track","set","debug"])fv.zaraz[fB]=fv.zaraz._f(fB);fv.zaraz.init=()=>{var fC=fw.getElementsByTagName(fy)[0],fD=fw.createElement(fy),fE=fw.getElementsByTagName("title")[0];fE&&(fv[fx].t=fw.getElementsByTagName("title")[0].text);fv[fx].x=Math.random();fv[fx].w=fv.screen.width;fv[fx].h=fv.screen.height;fv[fx].j=fv.innerHeight;fv[fx].e=fv.innerWidth;fv[fx].l=fv.location.href;fv[fx].r=fw.referrer;fv[fx].k=fv.screen.colorDepth;fv[fx].n=fw.characterSet;fv[fx].o=(new Date).getTimezoneOffset();if(fv.dataLayer)for(const fF of Object.entries(Object.entries(dataLayer).reduce(((fG,fH)=>({...fG[1],...fH[1]})),{})))zaraz.set(fF[0],fF[1],{scope:"page"});fv[fx].q=[];for(;fv.zaraz.q.length;){const fI=fv.zaraz.q.shift();fv[fx].q.push(fI)}fD.defer=!0;for(const fJ of[localStorage,sessionStorage])Object.keys(fJ||{}).filter((fL=>fL.startsWith("_zaraz_"))).forEach((fK=>{try{fv[fx]["z_"+fK.slice(7)]=JSON.parse(fJ.getItem(fK))}catch{fv[fx]["z_"+fK.slice(7)]=fJ.getItem(fK)}}));fD.referrerPolicy="origin";fD.src="cdn-cgi/zaraz/sd0d9.js?z="+btoa(encodeURIComponent(JSON.stringify(fv[fx])));fC.parentNode.insertBefore(fD,fC)};["complete","interactive"].includes(fw.readyState)?zaraz.init():fv.addEventListener("DOMContentLoaded",zaraz.init)}}(w,d,"zarazData","script");window.zaraz._p=async eC=>new Promise((eD=>{if(eC){eC.e&&eC.e.forEach((eE=>{try{const eF=d.querySelector("script[nonce]"),eG=eF?.nonce||eF?.getAttribute("nonce"),eH=d.createElement("script");eG&&(eH.nonce=eG);eH.innerHTML=eE;eH.onload=()=>{d.head.removeChild(eH)};d.head.appendChild(eH)}catch(eI){console.error(`Error executing script: ${eE}\n`,eI)}}));Promise.allSettled((eC.f||[]).map((eJ=>fetch(eJ[0],eJ[1]))))}eD()}));zaraz._p({"e":["(function(w,d){})(window,document)"]});})(window,document)}catch(e){throw fetch("/cdn-cgi/zaraz/t"),e;};</script></head>

    <body x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }" x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode === true}">

      <!-- ===== Page Wrapper Start ===== -->
        <div class="flex h-screen overflow-hidden">
            <?php include('sidebar.php');?>
            <!-- ===== Content Area Start ===== -->
            <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
                <!-- ===== Main Content Start ===== -->
                <main>
                    <?php include('header.php'); ?>
                    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                     <div class="col-span-12 space-y-6 xl:col-span-7">

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
    <div class="bg-white p-5 rounded-xl shadow-lg transform transition hover:scale-105">
      <p class="text-sm text-gray-400">Total Stock</p>
      <h2 class="text-3xl font-bold text-gray-800" id="totalQuantity">
  <?= number_format($totalQuantity) ?>
</h2>

    </div>
    <div class="bg-white p-5 rounded-xl shadow-lg transform transition hover:scale-105">
      <p class="text-sm text-gray-400">Stock Value (₦)</p>
     <h2 class="text-3xl font-bold text-gray-800" id="totalStockValue">
  <?= number_format($totalStockValue, 2) ?>
</h2>

    </div>
    <div class="bg-white p-5 rounded-xl shadow-lg transform transition hover:scale-105">
      <p class="text-sm text-gray-400">Total Products Stocked</p>
     <h2 class="text-3xl font-bold text-gray-800" id="totalProducts">
  <?= number_format($totalProducts) ?>
</h2>

    </div>
    <div class="bg-white p-5 rounded-xl shadow-lg transform transition hover:scale-105">
      <p class="text-sm text-gray-400">Total Categories</p>
      <h2 class="text-3xl font-bold text-gray-800" id="totalCategories">
  <?= number_format($totalCategories) ?>
</h2>

    </div>
    <div class="bg-white p-5 rounded-xl shadow-lg transform transition hover:scale-105 md:col-span-2">
      <p class="text-sm text-gray-400">Stock Net Profit (₦)</p>
     <h2 class="text-3xl font-bold text-green-600" id="totalStockProfit">
  <?= number_format($totalStockProfit, 2) ?>
</h2>

    </div>
  </div>

  <!-- Filters -->
  <div class="flex flex-col md:flex-row gap-3 mt-4">
    <input type="text" id="searchInput" placeholder="Search product or barcode..." class="border p-2 rounded-lg flex-1">
    <input type="date" id="dateFilter" class="border p-2 rounded-lg">
    <select id="locationFilter" class="border p-2 rounded-lg">
      <option value="">All Locations</option>
      <?php
      $locationsStmt = $pdo->prepare("SELECT id, name FROM locations WHERE company_id=?");
      $locationsStmt->execute([$company_id]);
      foreach($locationsStmt->fetchAll() as $loc){
        echo "<option value='{$loc['name']}'>{$loc['name']}</option>";
      }
      ?>
    </select>
    <input type="number" id="minPrice" placeholder="Min Price" class="border p-2 rounded-lg w-32">
    <input type="number" id="maxPrice" placeholder="Max Price" class="border p-2 rounded-lg w-32">
  </div>

  <!-- Stock Table -->
  <div class="overflow-x-auto rounded-xl shadow-lg bg-white mt-4">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">s/n</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Product</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Barcode</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Stock History</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Unit</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Stock Type</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Quantity</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Location</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Cost Price</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Selling Price</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Total Stock Value (NGN)</th>
<th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Profit (NGN)</th>
<th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Availability</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Date (yyyy-mm-dd) & TIME</th>
          <th class="px-4 py-3 text-left text-gray-500 uppercase text-xs">Action</th>
        </tr>
      </thead>
      <tbody id="stockTableBody">
<?php 
$count = 1;
foreach($stocks as $row): 

  // 🔢 Calculations
  $totalCostValue   = $row['quantity'] * $row['cost_price'];
  $totalSellingValue = $row['quantity'] * $row['selling_price'];
  $profitValue      = ($row['selling_price'] - $row['cost_price']) * $row['quantity'];

  // 🚨 Low stock logic (adjust threshold if needed)
  $lowStockThreshold = 10;
  $isLowStock = $row['quantity'] <= $lowStockThreshold;

?>

<tr class="hover:bg-gray-50 transition">

  <!-- Barcode -->
  <td class="px-4 py-3"><?= htmlspecialchars($count++) ?></td>
  <td class="px-4 py-3"><?= htmlspecialchars($row['name']) ?></td>
  <td class="px-4 py-3"><?= htmlspecialchars($row['barcode']) ?></td>
  <!-- Unit Type -->
  
  <!-- Stock History -->
  <td class="px-4 py-3 flex gap-2">

    <!-- history -->
    <form method="POST" action="stock-history.php" onsubmit="return confirm('Are you certain you want to proceed to view the historical data of your preferred stock?.');">
      <input type="hidden" name="stock_id" value="<?= $row['id'] ?>">
      <button type="submit"
        class="text-white text-sm px-3 py-1 rounded flex items-center gap-1" id="swhite"
        style="width: 100px;padding: 8px;border-radius: 8px;">
        <i class="fa-solid fa-right-left" ></i>
        History
      </button>
    </form>

  </td>


<!-- Unit -->
<td class="px-4 py-3"><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>

<!-- Section -->
<td class="px-4 py-3"><?= htmlspecialchars($row['stock_type'] ?? '-') ?></td>

  <!-- Quantity -->
  <td class="px-4 py-3"><?= htmlspecialchars($row['quantity']) ?></td>

  <!-- Location -->
  <td class="px-4 py-3"><?= htmlspecialchars($row['location_name']) ?></td>

  <!-- Selling Price -->
  <td class="px-4 py-3"><?= number_format($row['cost_price'],2) ?></td>
  <td class="px-4 py-3"><?= number_format($row['selling_price'],2) ?></td>

  <!-- Total Selling Value -->
  <td class="px-4 py-3 font-semibold text-blue-600">
    <?= number_format($totalSellingValue, 2) ?>
  </td>


  <!-- Profit -->
  <td class="px-4 py-3 font-semibold <?= $profitValue >= 0 ? 'text-green-600' : 'text-red-600' ?>">
    <?= number_format($profitValue, 2) ?>
  </td>

  <!-- Stock Status -->
  <td class="px-4 py-3">
    <?php if($isLowStock): ?>
      <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-semibold">
        Low Stock
      </span>
    <?php else: ?>
      <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs font-semibold">
        In Stock
      </span>
    <?php endif; ?>
  </td>


  <!-- Last Updated -->
  <td class="px-4 py-3"><?= htmlspecialchars($row['created_at']) ?></td>

  <!-- ACTIONS -->
  <td class="px-4 py-3 flex gap-2">

    <!-- TRANSFER -->
    <form method="POST" action="transfer-stock.php" onsubmit="return confirm('Are you certain you want to proceed with transferring this stock from its current location?.');">
      <input type="hidden" name="stock_id" value="<?= $row['id'] ?>">
      <button type="submit"
        class="text-white text-sm px-3 py-1 rounded flex items-center gap-1" id="swhite"
        style="width: 100px;padding: 8px;border-radius: 8px;">
        <i class="fa-solid fa-right-left"></i>
        Transfer
      </button>
    </form>

  </td>



</tr>
<?php endforeach; ?>
</tbody>

    </table>
  </div>

  <!-- Charts -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
  <!-- Resizable Charts -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-4 rounded-xl shadow-lg" style="resize:both; overflow:auto; min-height:400px;">
    <canvas id="categoryChart" style="width:100%; height:100%;"></canvas>
  </div>
  <div class="bg-white p-4 rounded-xl shadow-lg" style="resize:both; overflow:auto; min-height:400px;">
    <canvas id="stockTrendChart" style="width:100%; height:100%;"></canvas>
  </div>
</div>
  </div>
</div>

                    </div>
                </main>
                <!-- ===== Main Content End ===== -->
            </div>
            <!-- ===== Content Area End ===== -->  
        </div>
        <!-- footer session begins -->
        <?php include('footer.php'); ?>
        <!-- footer session ends -->
        <!-- ===== Page Wrapper End ===== -->
        <script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script defer src="bundle.js"></script><script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}' crossorigin="anonymous"></script>
<!-- ======================== JS & Animations ======================== -->

<!-- JS Section -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

function animateValue(id, start, end, duration) {

    const obj = document.getElementById(id);
    if (!obj) return;

    let current = start;

    const increment = Math.max(
        Math.ceil((end - start) / 40),
        1
    );

    const timer = setInterval(() => {

        current += increment;

        if (current >= end) {
            current = end;
            clearInterval(timer);
        }

        obj.textContent = Math.round(current).toLocaleString();

    }, 20);

}
animateValue("totalQuantity",0,<?= $totalQuantity ?>,500);
animateValue("totalStockValue",0,<?= $totalStockValue ?>,500);
animateValue("totalProducts",0,<?= $totalProducts ?>,500);
animateValue("totalCategories",0,<?= $totalCategories ?>,500);
animateValue("totalStockProfit",0,<?= $totalStockProfit ?>,500);

  new Chart(document.getElementById('categoryChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($categoriesLabels) ?>,
    datasets: [{
      data: <?= json_encode($categoriesData) ?>,
      backgroundColor: ['#4ade80','#facc15','#f87171','#60a5fa','#a78bfa'],
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false, // allows chart to resize freely
    animation: { animateScale: true }
  }
});

new Chart(document.getElementById('stockTrendChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($trendLabels) ?>,
    datasets: [{
      label: 'Stock Trend',
      data: <?= json_encode($trendData) ?>,
      fill: true,
      backgroundColor: 'rgba(96,165,250,0.2)',
      borderColor: '#3b82f6',
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false, // allows chart to resize freely
    animation: { duration: 1200 }
  }
});

const stockRows = document.querySelectorAll('#stockTableBody tr');

function filterTable() {

    const searchVal   = document.getElementById('searchInput').value.toLowerCase().trim();
    const dateVal     = document.getElementById('dateFilter').value;
    const locationVal = document.getElementById('locationFilter').value.toLowerCase();
    const minPrice    = parseFloat(document.getElementById('minPrice').value) || 0;
    const maxPrice    = parseFloat(document.getElementById('maxPrice').value) || Infinity;

    stockRows.forEach(row => {

        const cells = row.querySelectorAll('td');

        const product     = cells[1]?.textContent.toLowerCase() || '';
        const barcode     = cells[2]?.textContent.toLowerCase() || '';
        const location    = cells[7]?.textContent.toLowerCase() || '';

        const costPrice = parseFloat(
            cells[8]?.textContent.replace(/,/g, '')
        ) || 0;

        const lastUpdated = cells[13]?.textContent.trim() || '';

        let match = true;

        // Search
        if (
            searchVal &&
            !product.includes(searchVal) &&
            !barcode.includes(searchVal)
        ) {
            match = false;
        }

        // Location
        if (
            locationVal &&
            !location.includes(locationVal)
        ) {
            match = false;
        }

        // Price Range
        if (
            costPrice < minPrice ||
            costPrice > maxPrice
        ) {
            match = false;
        }

        // Date
        if (dateVal) {

            const rowDate = lastUpdated.substring(0, 10);

            if (rowDate !== dateVal) {
                match = false;
            }
        }

        row.style.display = match ? '' : 'none';

    });
}

// Live typing filters
document.getElementById('searchInput').addEventListener('input', filterTable);
document.getElementById('minPrice').addEventListener('input', filterTable);
document.getElementById('maxPrice').addEventListener('input', filterTable);

// Dropdown & Date filters
document.getElementById('locationFilter').addEventListener('change', filterTable);
document.getElementById('dateFilter').addEventListener('change', filterTable);
</script>


      </body>
</html>

