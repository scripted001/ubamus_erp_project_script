<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'];
$user_email = $_SESSION['user_email'];
$approved_by = "Not Approved!";


// echo $companyid;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
      <title>
      <?php echo $company_name; ?> |  Location Details
      </title>
      <link rel="icon" type="image/png" href="https://ubamus.com/hoa/src/images/icons/favicon.png">
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
                          <div class="grid grid-cols-6 gap-4 md:gap-6">
                              <div class="col-span-12 space-y-6 xl:col-span-7">
                                <div class="grid grid-cols-6 gap-4 md:gap-6">
  <div class="col-span-12 space-y-6 xl:col-span-7">
    <?php
// Get location ID from GET
$location_id = $_GET['location_id'] ?? null;
if (!$location_id) {
    echo "<p class='text-red-500'>No location selected.</p>";
    return;
}

// Fetch location info
$stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
$stmt->execute([$location_id]);
$location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    echo "<p class='text-red-500'>Location not found.</p>";
    return;
}

// =====================
// TOTAL STAFF
// =====================
$stmt = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE location_id = ?");
$stmt->execute([$location_id]);
$totalStaff = (int)$stmt->fetchColumn();

// =====================
// TOTAL STOCK QTY & VALUE
// =====================
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(s.quantity), 0) AS total_qty,
        COALESCE(SUM(s.quantity * p.selling_price), 0) AS total_value
    FROM stocks s
    INNER JOIN products p ON p.id = s.product_id
    WHERE s.location_id = ?
");
$stmt->execute([$location_id]);
$stockData = $stmt->fetch(PDO::FETCH_ASSOC);

$totalStock = (int)($stockData['total_qty'] ?? 0);
$totalStockValue = (float)($stockData['total_value'] ?? 0);

// =====================
// TOTAL SALES VALUE
// =====================
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(si.total_price), 0)
    FROM sales s
    INNER JOIN sale_items si ON si.sale_id = s.id
    WHERE s.location_id = ?
");
$stmt->execute([$location_id]);
$totalSales = (float)$stmt->fetchColumn();
?>

    <!-- Location Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Staff</p>
        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo $totalStaff; ?></h4>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Stock</p>
        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo $totalStock; ?></h4>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Stock Value</p>
        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo number_format($totalStockValue, 2); ?></h4>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Sales Value</p>
        <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo number_format($totalSales, 2); ?></h4>
      </div>
    </div>

    <!-- Tabs: Staff / Stocks / Sales -->
    <div class="mt-6">
      <div class="flex gap-4 border-b border-gray-200 dark:border-gray-700">
        <button class="tab-button px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border-b-2 border-blue-600 focus:outline-none" data-tab="staff">Staff</button>
        <button class="tab-button px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 focus:outline-none" data-tab="stocks">Stocks</button>
        <button class="tab-button px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 focus:outline-none" data-tab="sales">Sales</button>
      </div>

      <div id="staff" class="tab-content mt-4">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM staffs WHERE location_id = ?");
        $stmt->execute([$location_id]);
        $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Name</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Email</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Phone</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Role</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
              <?php foreach ($staffs as $staff): ?>
              <tr>
                <td class="px-6 py-3"><?php echo htmlspecialchars($staff['firstname'] . ' ' . $staff['lastname']); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars($staff['email']); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars($staff['phone']); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars($staff['role']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div id="stocks" class="tab-content mt-4 hidden">
     <?php
  $stmt = $pdo->prepare("
    SELECT s.*, p.name AS product_name, p.selling_price
    FROM stocks s
    LEFT JOIN products p ON s.product_id = p.id
    WHERE s.location_id = ?
  ");
  $stmt->execute([$location_id]);
  $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
  ?>
        <div class="overflow-x-scroll border rounded-xl">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Product</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Quantity</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Selling Price</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Total Value</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
              <?php foreach ($stocks as $stock): ?>
              <tr>
                <td class="px-6 py-3"><?php echo htmlspecialchars($stock['product_name']); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars($stock['quantity']); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars(number_format($stock['selling_price'],2)); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars(number_format($stock['quantity'] * $stock['selling_price'],2)); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div id="sales" class="tab-content mt-4 hidden">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE location_id = ?");
        $stmt->execute([$location_id]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Customer</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Total</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Date</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
              <?php foreach ($sales as $sale): ?>
              <tr>
                <td class="px-6 py-3"><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                <td class="px-6 py-3"><?php echo number_format($sale['final_total'],2); ?></td>
                <td class="px-6 py-3"><?php echo htmlspecialchars($sale['created_at']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
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

    <!-- JS for Tabs -->
    <script>
      const buttons = document.querySelectorAll('.tab-button');
      const contents = document.querySelectorAll('.tab-content');

      buttons.forEach(btn => {
        btn.addEventListener('click', () => {
          contents.forEach(c => c.classList.add('hidden'));
          buttons.forEach(b => b.classList.remove('border-b-2','border-blue-600'));
          document.getElementById(btn.dataset.tab).classList.remove('hidden');
          btn.classList.add('border-b-2','border-blue-600');
        });
      });
    </script>
  </div>
</div>

        </body>
</html>

