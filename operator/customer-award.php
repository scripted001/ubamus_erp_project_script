<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'] ?? null;
$location_id = $_SESSION['location_id'] ?? null;

// ===== Date Filter =====
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date   = $_GET['end_date'] ?? date('Y-m-d');    // Today

// ===== Helper Functions =====
function medalColor($rank) {
    return match($rank) {
        1 => 'bg-yellow-400 text-black',
        2 => 'bg-gray-300 text-black',
        3 => 'bg-amber-700 text-white',
        default => 'bg-gray-200 text-black',
    };
}

function formatAmount($amount, $fallback = 'No Activity') {
    return is_numeric($amount) && $amount > 0 ? '₦' . number_format($amount, 2) : $fallback;
}

function formatCount($count, $fallback = 'None') {
    return is_numeric($count) && $count > 0 ? $count : $fallback;
}

// ===== TOP 50 CUSTOMERS BY SALES =====
$salesStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,
           SUM(sa.final_total) AS total_sales,
           COUNT(sa.id) AS num_sales
    FROM customers c
    LEFT JOIN sales sa 
        ON c.id = sa.customer_id 
        AND sa.company_id = :company_id_sales
        AND sa.location_id = :location_id_sales
        AND sa.created_at BETWEEN :start_date_sales AND :end_date_sales
    WHERE c.company_id = :company_id_main_sales
      AND c.location_id = :location_id_main_sales
    GROUP BY c.id
    ORDER BY total_sales DESC, num_sales DESC
    LIMIT 50
");
$salesStmt->execute([
    ':company_id_sales' => $company_id,
    ':location_id_sales' => $location_id,
    ':start_date_sales' => $start_date.' 00:00:00',
    ':end_date_sales' => $end_date.' 23:59:59',
    ':company_id_main_sales' => $company_id,
    ':location_id_main_sales' => $location_id
]);
$topSalesCustomers = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// ===== TOP 50 CUSTOMERS BY JOBS =====
$jobStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,
           SUM(j.final_total) AS total_jobs,
           COUNT(j.id) AS num_jobs
    FROM customers c
    LEFT JOIN jobs j
        ON c.id = j.customer_id
        AND j.company_id = :company_id_jobs
        AND j.location_id = :location_id_jobs
        AND j.created_at BETWEEN :start_date_jobs AND :end_date_jobs
    WHERE c.company_id = :company_id_main_jobs
      AND c.location_id = :location_id_main_jobs
    GROUP BY c.id
    ORDER BY total_jobs DESC, num_jobs DESC
    LIMIT 50
");
$jobStmt->execute([
    ':company_id_jobs' => $company_id,
    ':location_id_jobs' => $location_id,
    ':start_date_jobs' => $start_date.' 00:00:00',
    ':end_date_jobs' => $end_date.' 23:59:59',
    ':company_id_main_jobs' => $company_id,
    ':location_id_main_jobs' => $location_id
]);
$topJobCustomers = $jobStmt->fetchAll(PDO::FETCH_ASSOC);

// ===== TOP 50 OVERALL CUSTOMERS =====
$overallStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,
           COALESCE(sales.total_sales,0) AS total_sales,
           COALESCE(jobs.total_jobs,0) AS total_jobs,
           COALESCE(sales.total_sales,0) + COALESCE(jobs.total_jobs,0) AS overall_score
    FROM customers c
    LEFT JOIN (
        SELECT sa.customer_id, SUM(sa.final_total) AS total_sales
        FROM sales sa
        WHERE sa.company_id = :company_id_sales
          AND sa.location_id = :location_id_sales
          AND sa.created_at BETWEEN :start_date_sales AND :end_date_sales
        GROUP BY sa.customer_id
    ) sales ON c.id = sales.customer_id
    LEFT JOIN (
        SELECT j.customer_id, SUM(j.final_total) AS total_jobs
        FROM jobs j
        WHERE j.company_id = :company_id_jobs
          AND j.location_id = :location_id_jobs
          AND j.created_at BETWEEN :start_date_jobs AND :end_date_jobs
        GROUP BY j.customer_id
    ) jobs ON c.id = jobs.customer_id
    WHERE c.company_id = :company_id_main
      AND c.location_id = :location_id_main
    ORDER BY overall_score DESC
    LIMIT 50
");
$overallStmt->execute([
    ':company_id_sales' => $company_id,
    ':location_id_sales' => $location_id,
    ':start_date_sales' => $start_date.' 00:00:00',
    ':end_date_sales' => $end_date.' 23:59:59',
    ':company_id_jobs' => $company_id,
    ':location_id_jobs' => $location_id,
    ':start_date_jobs' => $start_date.' 00:00:00',
    ':end_date_jobs' => $end_date.' 23:59:59',
    ':company_id_main' => $company_id,
    ':location_id_main' => $location_id
]);
$topOverallCustomers = $overallStmt->fetchAll(PDO::FETCH_ASSOC);

// ===== FULL CUSTOMER LIST =====
$fullStmt = $pdo->prepare("
    SELECT c.id AS customer_id, c.fullname, c.email, c.phone,
           COALESCE(sales.total_sales,0) AS total_sales,
           COUNT(sa.id) AS num_sales,
           COALESCE(jobs.total_jobs,0) AS total_jobs,
           COUNT(j.id) AS num_jobs,
           COALESCE(sales.total_sales,0) + COALESCE(jobs.total_jobs,0) AS overall_score
    FROM customers c
    LEFT JOIN sales sa 
        ON c.id = sa.customer_id 
        AND sa.company_id = :company_id1
        AND sa.location_id = :location_id1
        AND sa.created_at BETWEEN :start_date1 AND :end_date1
    LEFT JOIN (
        SELECT sa.customer_id, SUM(sa.final_total) AS total_sales
        FROM sales sa
        WHERE sa.company_id = :company_id2
          AND sa.location_id = :location_id2
          AND sa.created_at BETWEEN :start_date2 AND :end_date2
        GROUP BY sa.customer_id
    ) sales ON c.id = sales.customer_id
    LEFT JOIN jobs j 
        ON c.id = j.customer_id 
        AND j.company_id = :company_id3
        AND j.location_id = :location_id3
        AND j.created_at BETWEEN :start_date3 AND :end_date3
    LEFT JOIN (
        SELECT j.customer_id, SUM(j.final_total) AS total_jobs
        FROM jobs j
        WHERE j.company_id = :company_id4
          AND j.location_id = :location_id4
          AND j.created_at BETWEEN :start_date4 AND :end_date4
        GROUP BY j.customer_id
    ) jobs ON c.id = jobs.customer_id
    WHERE c.company_id = :company_id_main
      AND c.location_id = :location_id_main
    GROUP BY c.id
    ORDER BY overall_score DESC
");
$fullStmt->execute([
    ':company_id1' => $company_id,
    ':location_id1' => $location_id,
    ':start_date1' => $start_date.' 00:00:00',
    ':end_date1' => $end_date.' 23:59:59',
    ':company_id2' => $company_id,
    ':location_id2' => $location_id,
    ':start_date2' => $start_date.' 00:00:00',
    ':end_date2' => $end_date.' 23:59:59',
    ':company_id3' => $company_id,
    ':location_id3' => $location_id,
    ':start_date3' => $start_date.' 00:00:00',
    ':end_date3' => $end_date.' 23:59:59',
    ':company_id4' => $company_id,
    ':location_id4' => $location_id,
    ':start_date4' => $start_date.' 00:00:00',
    ':end_date4' => $end_date.' 23:59:59',
    ':company_id_main' => $company_id,
    ':location_id_main' => $location_id
]);
$allCustomers = $fullStmt->fetchAll(PDO::FETCH_ASSOC);
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
            <link rel="stylesheet" href="awards.css">
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
                            
                <!-- ===== DATE FILTER FORM ===== -->
                <form method="get" class="flex gap-4 mb-6" id="form-bal">
                    <div>
                        <label>Start Date:</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="px-3 py-1 rounded text-black">
                    </div>
                    <div>
                        <label>End Date:</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="px-3 py-1 rounded text-black">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 rounded hover:bg-blue-700" id="filter-btn">Filter</button>
                    </div>
                </form>

                <!-- ===== CUSTOMER LEADERBOARD ===== -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Sales -->
                    <div>
                        <h2 class="text-xl font-bold mb-4">💰 Top Customers by Sales</h2>
                        <?php foreach($topSalesCustomers as $i => $cust): ?>
                            <div class="p-4 mb-4 bg-white/10 rounded flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold"><?= $cust['fullname'] ?></h3>
                                    <p>Total Spent: <?= formatAmount($cust['total_sales'], 'No Sales') ?></p>
                                    <p><?= formatCount($cust['num_sales'], 'No Sales') ?> purchase(s)</p>
                                </div>
                                
                                <div class="px-3 py-1 rounded <?= medalColor($i+1) ?> text-center" style="font-size: 40px;"><?= ['🥇','🥈','🥉'][$i] ?? $i+1 ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Jobs -->
                    <div>
                        <h2 class="text-xl font-bold mb-4">🛠 Top Customers by Jobs</h2>
                        <?php foreach($topJobCustomers as $i => $cust): ?>
                            <div class="p-4 mb-4 bg-blue-900/20 rounded flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold"><?= $cust['fullname'] ?></h3>
                                    <p>Total Job Revenue: <?= formatAmount($cust['total_jobs'], 'No Jobs') ?></p>
                                    <p><?= formatCount($cust['num_jobs'], 'No Jobs') ?> job(s)</p>
                                </div>
                                
                                <div class="px-3 py-1 rounded <?= medalColor($i+1) ?> text-center" style="font-size: 40px;"><?= ['🥇','🥈','🥉'][$i] ?? $i+1 ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Overall -->
                    <div>
                        <h2 class="text-xl font-bold mb-4">👑 Top Customers Overall</h2>
                        <?php foreach($topOverallCustomers as $i => $cust): ?>
                            <div class="p-4 mb-4 bg-purple-800/30 rounded flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold"><?= $cust['fullname'] ?></h3>
                                    <p>Combined Score: <?= formatAmount($cust['overall_score'], 'No Activity') ?></p>
                                </div>
                                
                                <div class="px-3 py-1 rounded <?= medalColor($i+1) ?> text-center" style="font-size: 40px;"><?= ['🥇','🥈','🥉'][$i] ?? $i+1 ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <!-- ===== FULL CUSTOMER TABLE ===== -->
                <div class="mt-10 overflow-x-auto">
                    <h2 class="text-xl font-bold mb-4">📋 All Customers</h2>
                    <table class="min-w-full text-left text-white border border-gray-500">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="px-2 py-1">Rank</th>
                                <th class="px-2 py-1">Full Name</th>
                                <th class="px-2 py-1">Email</th>
                                <th class="px-2 py-1">Phone</th>
                                <th class="px-2 py-1">Total Sales</th>
                                <th class="px-2 py-1">Purchases</th>
                                <th class="px-2 py-1">Total Jobs</th>
                                <th class="px-2 py-1">Jobs</th>
                                <th class="px-2 py-1">Combined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($allCustomers as $rank => $cust): ?>
                            <tr class="<?= $rank % 2 == 0 ? 'bg-gray-800' : 'bg-gray-900' ?>">
                                <td class="px-2 py-1"><?= $rank + 1 ?></td>
                                <td class="px-2 py-1"><?= $cust['fullname'] ?></td>
                                <td class="px-2 py-1"><?= $cust['email'] ?></td>
                                <td class="px-2 py-1"><?= $cust['phone'] ?></td>
                                <td class="px-2 py-1"><?= formatAmount($cust['total_sales'], 'No Sales') ?></td>
                                <td class="px-2 py-1"><?= formatCount($cust['num_sales'], 'No Sales') ?></td>
                                <td class="px-2 py-1"><?= formatAmount($cust['total_jobs'], 'No Jobs') ?></td>
                                <td class="px-2 py-1"><?= formatCount($cust['num_jobs'], 'No Jobs') ?></td>
                                <td class="px-2 py-1"><?= formatAmount($cust['overall_score'], 'No Activity') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
   

        </body>
    </html>

