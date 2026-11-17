<?php
    include('../config/db.php');
    include('../config/validation.php');
    include('user_data.php');
    include('../app_data.php');
    include('../company_data.php');


    $company_id = $_SESSION['company_id'];
    $user_email = $_SESSION['user_email'];

   
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

<?php
// ====== Job Statistics Function ======
function jobStats(PDO $pdo, $company_id, $dateCondition) {
    $sql = "
        SELECT
            COUNT(DISTINCT j.id) AS jobs_count,
            COALESCE(SUM(ji.unit_price * ji.quantity), 0) AS income,
            COALESCE(SUM(jc.`material-quantity` * ji.quantity), 0) AS cost,
            COALESCE(SUM(ji.unit_price * ji.quantity) - SUM(jc.`material-quantity` * ji.quantity), 0) AS profit,
            (
                CASE WHEN SUM(ji.unit_price * ji.quantity) = 0 THEN 0
                ELSE (SUM(ji.unit_price * ji.quantity) - SUM(jc.`material-quantity` * ji.quantity)) / SUM(ji.unit_price * ji.quantity) * 100
                END
            ) AS profit_margin
        FROM jobs j
        INNER JOIN job_items ji ON ji.job_id = j.id
        INNER JOIN job_catalogue jc ON jc.id = ji.job_catalogue_id
        WHERE j.company_id = :company_id
        AND j.status = 'completed'
        $dateCondition
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['company_id' => $company_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ====== Date Conditions ======
$todayCondition = "AND DATE(j.job_date) = CURDATE()";
$yesterdayCondition = "AND DATE(j.job_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$weeklyCondition = "AND j.job_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND j.job_date <= CURDATE()";
$monthlyCondition = "AND YEAR(j.job_date) = YEAR(CURDATE()) AND MONTH(j.job_date) = MONTH(CURDATE())";
$annualCondition = "AND YEAR(j.job_date) = YEAR(CURDATE())";

// ====== Get Stats ======
$todayStats = jobStats($pdo, $company_id, $todayCondition);
$yesterdayStats = jobStats($pdo, $company_id, $yesterdayCondition);
$weeklyStats = jobStats($pdo, $company_id, $weeklyCondition);
$monthlyStats = jobStats($pdo, $company_id, $monthlyCondition);
$annualStats = jobStats($pdo, $company_id, $annualCondition);

// ====== Trend Helpers ======
function delta($current, $previous) {
    if ($previous == 0 && $current > 0) return 100;
    if ($previous == 0) return 0;
    return (($current - $previous) / $previous) * 100;
}
function trendClass($value) { return $value >= 0 ? 'text-green-600' : 'text-red-600'; }
function trendArrow($value) { return $value >= 0 ? '▲' : '▼'; }

// ====== Today Trends ======
$todayIncomeChange = delta($todayStats['income'], $yesterdayStats['income']);
$todayProfitChange = delta($todayStats['profit'], $yesterdayStats['profit']);

// ====== Weekly, Monthly, Yearly Trends ======
$weeklyChange = delta($weeklyStats['profit'], $monthlyStats['profit'] / 4);
$monthlyChange = delta($monthlyStats['profit'], $annualStats['profit'] / 12);
$annualChange = delta($annualStats['profit'], $annualStats['profit']); // placeholder

// ====== Forecasted Profits ======
// Weekly
$dayOfWeek = date('N'); // 1 (Mon) to 7 (Sun)
$weeklyDailyAverageProfit = $weeklyStats['profit'] / max($dayOfWeek,1);
$forecastedWeeklyProfit = $weeklyStats['profit'] + $weeklyDailyAverageProfit * (7 - $dayOfWeek);

// Monthly
$daysElapsed = date('j'); 
$daysInMonth = date('t'); 
$monthlyDailyAverageProfit = $monthlyStats['profit'] / max($daysElapsed,1);
$forecastedMonthlyProfit = $monthlyStats['profit'] + $monthlyDailyAverageProfit * ($daysInMonth - $daysElapsed);

// Yearly
$dayOfYear = date('z') + 1; // 1 to 365/366
$daysInYear = date('L') ? 366 : 365;
$yearlyDailyAverageProfit = $annualStats['profit'] / max($dayOfYear,1);
$forecastedYearlyProfit = $annualStats['profit'] + $yearlyDailyAverageProfit * ($daysInYear - $dayOfYear);

// ====== Inventory Predictions ======
$inventory = $pdo->query("
    SELECT id, title AS name, `material-quantity` AS quantity
    FROM job_catalogue
    WHERE company_id = $company_id
")->fetchAll(PDO::FETCH_ASSOC);

$inventoryPredictions = [];
foreach ($inventory as $item) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(ji.quantity),0) AS total_used
        FROM job_items ji
        INNER JOIN jobs j ON j.id = ji.job_id
        WHERE ji.job_catalogue_id = :catalogue_id
        AND j.company_id = :company_id
        AND j.status = 'completed'
    ");
    $stmt->execute(['catalogue_id' => $item['id'], 'company_id' => $company_id]);
    $totalUsed = $stmt->fetchColumn();

    $daysSinceFirstUsage = 30;
    $dailyUsage = $totalUsed / max($daysSinceFirstUsage,1);
    $daysLeft = $dailyUsage > 0 ? $item['quantity'] / $dailyUsage : '∞';
    $inventoryPredictions[] = "{$item['name']} → " . (is_numeric($daysLeft) ? ceil($daysLeft) : '∞') . " days left";
}

// ====== Cost Alerts ======
$costThreshold = 0.1; // 10%
$materials = $pdo->query("
    SELECT title AS name, charge AS unit_price
    FROM job_catalogue
    WHERE company_id = $company_id
")->fetchAll(PDO::FETCH_ASSOC);

$costAlerts = [];
foreach($materials as $m){
    if($m['unit_price'] > 0 && $m['unit_price'] * $costThreshold > 0){
        $costAlerts[] = "⚠️ {$m['name']} cost alert: high charge detected";
    }
}

// ====== Profit Sensitivity ======
$profitDropPercent = $monthlyStats['profit'] > 0
    ? ($monthlyStats['cost'] * $costThreshold / $monthlyStats['profit']) * 100
    : 0;

?>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

    <!-- Today Profit -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Today Profit</p>
        <h3 class="mt-2 font-bold text-blue-600">₦<?= number_format($todayStats['profit'],2) ?></h3>
        <p class="mt-1 text-xs <?= trendClass($todayProfitChange) ?>">
            <?= trendArrow($todayProfitChange) ?> <?= number_format(abs($todayProfitChange),1) ?>% vs yesterday
        </p>
    </div>

    <!-- Weekly Performance -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Weekly Performance</p>
        <h3 class="mt-2 font-bold text-indigo-600">₦<?= number_format($weeklyStats['profit'],2) ?></h3>
        <p class="mt-1 text-xs <?= trendClass($weeklyChange) ?>">
            <?= trendArrow($weeklyChange) ?> vs monthly avg
        </p>
    </div>

    <!-- Monthly Performance -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Monthly Performance</p>
        <h3 class="mt-2 font-bold text-green-600">₦<?= number_format($monthlyStats['profit'],2) ?></h3>
        <p class="mt-1 text-xs <?= trendClass($monthlyChange) ?>">
            <?= trendArrow($monthlyChange) ?> vs annual avg
        </p>
    </div>

    <!-- Annual Performance -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Yearly Performance</p>
        <h3 class="mt-2 font-bold text-orange-600">₦<?= number_format($annualStats['profit'],2) ?></h3>
        <p class="mt-1 text-xs text-gray-400">Comparison with last year TBD</p>
    </div>

    <!-- Forecasted Weekly Profit -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Forecasted Profit This Week</p>
        <h3 class="mt-2 font-bold text-blue-600">₦<?= number_format($forecastedWeeklyProfit,2) ?></h3>
        <p class="mt-1 text-xs text-gray-400">Based on your daily average so far this week</p>
    </div>

    <!-- Forecasted Monthly Profit -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Forecasted Profit This Month</p>
        <h3 class="mt-2 font-bold text-green-600">₦<?= number_format($forecastedMonthlyProfit,2) ?></h3>
        <p class="mt-1 text-xs text-gray-400">Based on your daily average so far this month</p>
    </div>

    <!-- Forecasted Yearly Profit -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Forecasted Profit This Year</p>
        <h3 class="mt-2 font-bold text-orange-600">₦<?= number_format($forecastedYearlyProfit,2) ?></h3>
        <p class="mt-1 text-xs text-gray-400">Based on your daily average so far this year</p>
    </div>

    <!-- Inventory Prediction -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Inventory Depletion</p>
        <ul class="mt-1 text-xs text-gray-400 list-disc list-inside">
            <?php foreach($inventoryPredictions as $inv) echo "<li>$inv</li>"; ?>
        </ul>
    </div>

    <!-- Cost Alerts -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Cost Alerts</p>
        <ul class="mt-1 text-xs text-red-600 list-disc list-inside">
            <?php if($costAlerts) foreach($costAlerts as $alert) echo "<li>$alert</li>"; 
                  else echo "<li>No major cost increase</li>"; ?>
        </ul>
    </div>

    <!-- Profit Sensitivity -->
    <div class="rounded-2xl bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">Profit Sensitivity (10% cost rise)</p>
        <h3 class="mt-2 font-bold text-orange-600"><?= number_format($profitDropPercent,2) ?>% drop</h3>
        <p class="mt-1 text-xs text-gray-400">If material costs increase by 10%</p>
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

