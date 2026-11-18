<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


$company_id = $_SESSION['company_id'];
$user_email = $_SESSION['user_email'];

// echo $company_id;
// Set timezone
date_default_timezone_set('Africa/Lagos');

// Handle date filters
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to'] ?? date('Y-m-d');

// === Fetch expenses filtered by date AND company ===
$stmt = $pdo->prepare("
    SELECT 
        e.*,
        l.name AS location_name,
        l.type AS location_type,
        l.city AS location_city,
        l.state AS location_state
    FROM expenses e
    LEFT JOIN locations l 
        ON l.id = e.location_id
    WHERE e.company_id = ?
      AND DATE(e.created_at) BETWEEN ? AND ?
    ORDER BY e.created_at DESC
");

$stmt->execute([$company_id, $from, $to]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status = 1;
// === Fetch expenses filtered by approval ===

$stmtex = $pdo->prepare("
    SELECT 
        e.*,
        l.name AS location_name,
        l.type AS location_type,
        l.city AS location_city,
        l.state AS location_state
    FROM expenses e
    LEFT JOIN locations l 
        ON l.id = e.location_id
    WHERE e.company_id = ?
      AND DATE(e.created_at) BETWEEN ? AND ? AND e.stat = ?
    ORDER BY e.created_at DESC
");

$stmtex->execute([$company_id, $from, $to, $status]);
$expenses_appr = $stmtex->fetchAll(PDO::FETCH_ASSOC);

// === Analytics ===
$total_expenses = 0;
$expense_count = count($expenses);

foreach ($expenses as $exp) {
    $total_expenses += floatval($exp['amount']);
}
$approved_expenses = 0;
foreach ($expenses_appr as $expr) {
    $approved_expenses += floatval($expr['amount']);
}


// ... your existing PHP code above

// === Prepare chart data (aggregate by date) ===
$stmtChart = $pdo->prepare("
    SELECT DATE(created_at) AS day, SUM(amount) AS total
    FROM expenses
    WHERE company_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
$stmtChart->execute([$company_id, $from, $to]);
$chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC);



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

    <!-- ================= FILTER & ANALYTICS SECTION ================= -->
    <div class="col-span-12 space-y-4">

    <div class="flex gap-3 mb-4">

<select id="searchType" class="border px-3 py-2 rounded-lg">
<option value="title">Expense Title</option>
<option value="date">Date</option>
<option value="amount">Amount</option>
<option value="staff">Staff Responsible</option>
<option value="location">Location</option>
</select>

<input 
type="text"
id="searchInput"
placeholder="Search expenses..."
class="border px-3 py-2 rounded-lg w-full"
/>

</div>

        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow">
                <span class="text-gray-500 dark:text-gray-400 font-semibold">Approved Expenses</span>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white/90">₦<?= number_format($approved_expenses) ?></h3>
            </div>
            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow">
                <span class="text-gray-500 dark:text-gray-400 font-semibold">Number of Expenses</span>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white/90"><?= $expense_count ?></h3>
            </div>
            <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow">
                <span class="text-gray-500 dark:text-gray-400 font-semibold">Total Expenses</span>
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white/90">₦<?= $total_expenses ?></h3>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="mt-6 p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow">
            <canvas id="expensesChart" class="w-full h-64"></canvas>
        </div>
    </div>
    <!-- ================= FILTER & ANALYTICS SECTION END ================= -->

   <div class="mx-auto max-w-full p-4 md:p-6">
    <!-- ====== Metric Table Start ====== -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">All Expenses</h3>
            <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($expenses) ?> Records</span>
        </div>

        <div class="p-4 md:p-6 space-y-4">
        <div id="expensesResults">
            <?php foreach($expenses as $exp): ?>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-md border border-gray-100 dark:border-gray-800 p-5 flex flex-col md:flex-row md:justify-between md:items-center hover:shadow-lg transition-all duration-300">

                    <!-- Left Column: Info -->
                    <div class="flex-1 space-y-2">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= htmlspecialchars($exp['expense_title']) ?></h4>

                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $exp['type']=='Capital' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                <?= htmlspecialchars($exp['type']) ?>
                            </span>
                            <span><b>Amount:</b> <span class="text-green-600 font-semibold">₦<?= number_format($exp['amount']) ?></span></span>
                            <span><b>Location:</b> <?= htmlspecialchars($exp['location_name']) ?> (<?= htmlspecialchars($exp['location_type']) ?>) — <?= htmlspecialchars($exp['location_city']) ?>, <?= htmlspecialchars($exp['location_state']) ?></span>
                            <span><b>By:</b> <?= htmlspecialchars($exp['staff_responsible']) ?></span>
                            <span><b>Description:</b> <?= htmlspecialchars($exp['description']) ?></span>
                            <span><b>Registered:</b> <?= htmlspecialchars($exp['created_at']) ?></span>
                            <span><b>Registered by:</b> <?= htmlspecialchars($exp['created_by']) ?></span>
                            <span style="width: 100px;height: 30px;display: flex;"><form method="POST" action="delete-expenses.php" onsubmit="return confirm('Delete this expense?');">
    <input type="hidden" name="expense_id" value="<?= $exp['id'] ?>">
    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-lg" style="background-color: #262626;color: #fff;width: 100px;">
        Delete
    </button>
</form>

    </span>

                           <div>
<?php if (empty($exp['approved_by']) || trim($exp['approved_by']) == 'Not Approved!'): ?>
    <form method="POST" action="approve_expense.php" class="inline-block ml-2" style="width: 80px;height: 30px;display: flex;justify-content: center;align-items: center;background-color: #000;border-radius: 5px;">
        <input type="hidden" name="expense_id" value="<?= htmlspecialchars($exp['id']) ?>">
        <button type="submit" 
                class="px-3 py-1 rounded-lg bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition">Approve
        </button>
    </form>
<?php else: ?>
    <b>Approved by:</b> <?= htmlspecialchars($exp['approved_by']) ?>
<?php endif; ?>
</div>
</div>



                        </div>
                    </div>

                    <!-- Right Column: Actions -->
                    <div class="mt-4 md:mt-0 flex flex-col gap-2 items-start md:items-end">
                        <?php if (!empty($exp['receipt']) && file_exists('../uploads/receipts/'.$exp['receipt'])): ?>
                            <a href="../uploads/receipts/<?= $exp['receipt'] ?>" target="_blank" 
                               class="inline-block px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition" style="color: #a69f9f;">
                               <i class="fa-solid fa-eye mr-1"></i> View Receipt
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400 text-sm">No receipt</span>
                        <?php endif; ?>

                  


                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- ====== Metric Table ends ====== -->
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
  
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const searchInput = document.getElementById("searchInput");
const searchType = document.getElementById("searchType");
const results = document.getElementById("expensesResults");

searchInput.addEventListener("keyup", function(){

    let query = searchInput.value;
    let type = searchType.value;

    fetch(`search-expenses.php?search=${query}&type=${type}`)
    .then(res => res.text())
    .then(data => {

        results.innerHTML = data;

    });

});
</script>
<script>
// Automatic submit on date selection
document.getElementById('fromDate').addEventListener('change', () => {
    if(document.getElementById('fromDate').value && document.getElementById('toDate').value) {
        document.querySelector('form').submit();
    }
});
document.getElementById('toDate').addEventListener('change', () => {
    if(document.getElementById('fromDate').value && document.getElementById('toDate').value) {
        document.querySelector('form').submit();
    }
});

const ctx = document.getElementById('expensesChart').getContext('2d');

// Only aggregated data, not all rows
const chartData = <?= json_encode($chartData) ?>;
const labels = chartData.map(d => d.day);
const data = chartData.map(d => parseFloat(d.total));

new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Total Expenses (₦)',
            data,
            backgroundColor: 'rgba(59, 130, 246, 0.6)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

</script>

    </body>
</html>
