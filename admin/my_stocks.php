<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'];
$user_id = $_SESSION['user_id'];



// echo $company_id;


// Fetching logged in user email
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userdata = $stmt->fetch(PDO::FETCH_ASSOC);
$useremail = $userdata['email'];
// echo $useremail;
// echo "Welcome";
// echo $user_id;
// selecting logged in user's staff_id
$stmt = $pdo->prepare("SELECT * FROM staffs WHERE email = ?");
$stmt->execute([$useremail]);
$userstaff = $stmt->fetch(PDO::FETCH_ASSOC);
$ustaff_id = $userstaff['id']; 
// echo $ustaff_id;
// ============================
// FETCH ASSIGNED STOCKS
// ============================
$stmt = $pdo->prepare("
SELECT 
    sa.id,
    sa.stock_id,
    sa.company_id,
    sa.location_id,
    sa.quantity,
    sa.date,
    sa.time,

    s.quantity AS stock_quantity,
    p.name AS product_name,

    pu.cost_price,
    pu.selling_price,

    CONCAT(stf.firstname, ' ', stf.lastname) AS assigned_to_name,
    CONCAT(stf2.firstname, ' ', stf2.lastname) AS assigned_by_name,

    CONCAT(sa.date, ' ', sa.time) AS assigned_at

FROM stocks_assigned sa

LEFT JOIN stocks s ON sa.stock_id = s.id
LEFT JOIN products p ON s.product_id = p.id
LEFT JOIN product_units pu ON pu.product_id = p.id

LEFT JOIN staffs stf ON sa.assigned_to = stf.id
LEFT JOIN staffs stf2 ON sa.assigned_by = stf2.id

WHERE sa.company_id = ? AND sa.assigned_to = ?

ORDER BY sa.id DESC
");

$stmt->execute([$company_id, $ustaff_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================
// CALCULATE TOTALS
// ============================
$totalCost = 0;
$totalSelling = 0;

foreach($assignments as $a){
    $qty = $a['quantity'] ?? 0;
    $cost = $a['cost_price'] ?? 0;
    $sell = $a['selling_price'] ?? 0;

    $totalCost += ($qty * $cost);
    $totalSelling += ($qty * $sell);
}

$totalProfit = $totalSelling - $totalCost;
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<title><?= $company_name; ?> | Stock Intelligence</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<link href="main.css" rel="stylesheet">

<style>
/* ================= MODERN DASHBOARD UI ================= */

.kpi-grid{
  display:grid;
  grid-template-columns: repeat(3, 1fr);
  gap:15px;
  margin:20px 0;
}

.kpi-card{
  padding:18px;
  border-radius:8px;
  color: #262626;
  box-shadow:0 10px 30px rgba(0,0,0,0.1);
  backdrop-filter: blur(10px);
}

.kpi-blue{ background:linear-gradient(135deg,#2563eb,#1e40af); }
.kpi-green{ background:linear-gradient(135deg,#10b981,#059669); }
.kpi-purple{ background:linear-gradient(135deg,#8b5cf6,#6d28d9); }
.navy-blue{ background: #351c84; }
.white{ background: #fff; }
.kpi-purple{ background:linear-gradient(135deg,#8b5cf6,#6d28d9); }
.kpi-purple{ background:linear-gradient(135deg,#8b5cf6,#6d28d9); }

.kpi-title{ font-size:12px; opacity:0.9; }
.kpi-value{ font-size:22px; font-weight:700; margin-top:6px; }

.card-grid{
  display:grid;
  grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
  gap:15px;
}

.stock-card{
  background:#fff;
  border-radius:16px;
  padding:16px;
  border:1px solid #eee;
  box-shadow:0 6px 18px rgba(0,0,0,0.05);
}

.dark .stock-card{
  background:#111827;
  border:1px solid #1f2937;
  color:#fff;
}
.text-el {
  width: 100%;
  display: flex;
  justify-content: space-between;
  font-weight: 300;
}
</style>
</head>

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
                  <div class="grid grid-cols-6 gap-4 md:gap-6">
                    <div class="col-span-12 space-y-6 xl:col-span-7">
                      <!-- ================= KPI DASHBOARD ================= -->
                      <div class="kpi-grid">

                        <div class="kpi-card white">
                          <div class="kpi-title">Total Cost Value</div>
                          <div class="kpi-value">₦<?= number_format($totalCost) ?></div>
                        </div>

                        <div class="kpi-card white">
                          <div class="kpi-title">Total Selling Value</div>
                          <div class="kpi-value">₦<?= number_format($totalSelling) ?></div>
                        </div>

                        <div class="kpi-card wite">
                          <div class="kpi-title">Potential Profit</div>
                          <div class="kpi-value">₦<?= number_format($totalProfit) ?></div>
                        </div>

                      </div>

                      <!-- ================= SEARCH ================= -->
                      <div class="flex gap-2 mb-4">
                        <select id="search_by" class="border px-3 py-2 rounded">
                          <option value="firstname">Assigned By (Firstname)</option>
                          <option value="name">Product Name</option>
                          <option value="date">Date</option>
                        </select>

                        <input type="text" id="search_value" class="border px-3 py-2 rounded flex-1" placeholder="Search stock..." />
                        <button id="search_btn" class="px-4 py-2 rounded text-white" style="background:#1d4ed8;"> Search </button>
                      </div>

                      <!-- ================= STOCK CARDS ================= -->
                      <div id="categoryCards" class="flex-1">
                        <?php foreach($assignments as $row): 
                          $qty = $row['quantity'] ?? 0;
                          $cost = $row['cost_price'] ?? 0;
                          $sell = $row['selling_price'] ?? 0;

                          $total_row_cost = $qty * $cost;
                          $total_row_sell = $qty * $sell;
                        ?>

                        <div class="stock-card">
                          <h3 style="font-weight:300;" class="text-el" style="display: flex;justify-content: space-between;"><b>Product Name: </b><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></h3>
                          <p class="text-el"><b>Staff Assigned to:</b> <?= htmlspecialchars($row['assigned_to_name'] ?? '') ?></p>
                          <p class="text-el"><b>Assigned By:</b> <?= htmlspecialchars($row['assigned_by_name'] ?? '') ?></p>
                          <p class="text-el"><b>Qty:</b> <?= $qty ?></p>

                          <hr style="margin:10px 0;">

                          <p class="text-el"><b>Cost:</b> ₦<?= number_format($cost) ?></p>
                          <p class="text-el"><b>Selling:</b> ₦<?= number_format($sell) ?></p>

                          <p class="text-el"><b>Total Cost:</b> ₦<?= number_format($total_row_cost) ?></p>
                          <p class="text-el"><b>Total Value:</b> ₦<?= number_format($total_row_sell) ?></p>

                          <p class="text-el" style="font-size:12px;opacity:0.7;">
                            <b>Date/Time:</b><?= $row['assigned_at'] ?>
                          </p>
                      </div>
                      <br>
                      <?php endforeach; ?>
                     
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
      <script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script defer src="bundle.js"></script><script defer   src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}' crossorigin="anonymous"></script>
      <!-- ================= LIVE AJAX SEARCH ================= -->
      <script>
function loadCategories(){
    const search_by = document.getElementById('search_by').value;
    const search_value = document.getElementById('search_value').value;

    fetch(`search_my_assigned_stocks.php?search_by=${encodeURIComponent(search_by)}&search_value=${encodeURIComponent(search_value)}`)
    .then(res => res.text())
    .then(html => {
        document.getElementById('categoryCards').innerHTML = html;
    })
    .catch(err => console.error(err));
}

// Trigger search on button click
document.getElementById('search_btn').addEventListener('click', loadCategories);

// Trigger search on Enter key
document.getElementById('search_value').addEventListener('input', function(e){
    if(e.key === 'input') loadCategories();
});
</script>
      
  </body>

  </html>
