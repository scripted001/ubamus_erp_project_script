<?php
   include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

// Ensure session variables exist
$company_id = $_SESSION['company_id'] ?? null;
$location_id = $_SESSION['location_id'] ?? null;
$user_email = $_SESSION['user_email'] ?? null;



// ===== Date filter =====
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // first day of month
$end_date   = $_GET['end_date'] ?? date('Y-m-d');    // today

// ==========================
// ===== TOP 3 SALES STAFF =====
// ==========================
$salesQuery = "
SELECT 
    s.id AS staff_id,
    CONCAT(s.firstname,' ',s.lastname) AS fullname,
    s.profile,
    s.email,
    s.phone,
    COUNT(sa.id) AS num_sales,
    COALESCE(SUM(sa.total_amount),0) AS total_sales
FROM sales sa
JOIN users u ON sa.user_id = u.id
LEFT JOIN staffs s ON LOWER(TRIM(s.email)) = LOWER(TRIM(u.email))
WHERE sa.company_id = :company_id_sales
AND sa.created_at BETWEEN :start_date_sales AND :end_date_sales
" . ($location_id ? " AND sa.location_id = :sales_location_id " : "") . "
GROUP BY s.id
ORDER BY total_sales DESC, num_sales DESC
LIMIT 3
";

$salesParams = [
    ':company_id_sales' => $company_id,
    ':start_date_sales' => $start_date . ' 00:00:00',
    ':end_date_sales'   => $end_date . ' 23:59:59'
];
if ($location_id) $salesParams[':sales_location_id'] = $location_id;

$stmt = $pdo->prepare($salesQuery);
$stmt->execute($salesParams);
$topSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// ===== TOP 3 JOB STAFF =====
// ==========================
$jobQuery = "
SELECT 
    s.id AS staff_id,
    CONCAT(s.firstname,' ',s.lastname) AS fullname,
    s.profile,
    s.email,
    s.phone,
    COUNT(j.id) AS num_jobs,
    COALESCE(SUM(j.grand_total),0) AS total_jobs
FROM jobs j
JOIN staffs s ON j.staff_id = s.id
WHERE j.company_id = :company_id_jobs
AND j.created_at BETWEEN :start_date_jobs AND :end_date_jobs
" . ($location_id ? " AND j.location_id = :jobs_location_id " : "") . "
GROUP BY s.id
ORDER BY total_jobs DESC, num_jobs DESC
LIMIT 3
";

$jobParams = [
    ':company_id_jobs' => $company_id,
    ':start_date_jobs' => $start_date . ' 00:00:00',
    ':end_date_jobs'   => $end_date . ' 23:59:59'
];
if ($location_id) $jobParams[':jobs_location_id'] = $location_id;

$stmt = $pdo->prepare($jobQuery);
$stmt->execute($jobParams);
$topJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// ===== TOP 3 OVERALL STAFF =====
// ==========================
$overallQuery = "
SELECT s.id AS staff_id,
       CONCAT(s.firstname,' ',s.lastname) AS fullname,
       s.profile,
       s.email,
       s.phone,
       COALESCE(sales.total_sales,0) + COALESCE(jobs.total_jobs,0) AS overall_score
FROM staffs s
LEFT JOIN (
    SELECT s.id AS staff_id, COALESCE(SUM(sa.total_amount),0) AS total_sales
    FROM sales sa
    JOIN users u ON sa.user_id = u.id
    LEFT JOIN staffs s ON LOWER(TRIM(s.email)) = LOWER(TRIM(u.email))
    WHERE sa.company_id = :company_id_overall_sales
    AND sa.created_at BETWEEN :start_date_overall_sales AND :end_date_overall_sales
    " . ($location_id ? " AND sa.location_id = :overall_sales_location_id " : "") . "
    GROUP BY s.id
) sales ON s.id = sales.staff_id
LEFT JOIN (
    SELECT j.staff_id, COALESCE(SUM(j.grand_total),0) AS total_jobs
    FROM jobs j
    WHERE j.company_id = :company_id_overall_jobs
    AND j.created_at BETWEEN :start_date_overall_jobs AND :end_date_overall_jobs
    " . ($location_id ? " AND j.location_id = :overall_jobs_location_id " : "") . "
    GROUP BY j.staff_id
) jobs ON s.id = jobs.staff_id
WHERE s.company_id = :company_id_overall_main
ORDER BY overall_score DESC
LIMIT 3
";

$overallParams = [
    ':company_id_overall_sales' => $company_id,
    ':start_date_overall_sales' => $start_date . ' 00:00:00',
    ':end_date_overall_sales'   => $end_date . ' 23:59:59',

    ':company_id_overall_jobs'  => $company_id,
    ':start_date_overall_jobs'  => $start_date . ' 00:00:00',
    ':end_date_overall_jobs'    => $end_date . ' 23:59:59',

    ':company_id_overall_main'  => $company_id
];

if ($location_id) {
    $overallParams[':overall_sales_location_id'] = $location_id;
    $overallParams[':overall_jobs_location_id']  = $location_id;
}

$stmt = $pdo->prepare($overallQuery);
$stmt->execute($overallParams);
$topOverall = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Medal Color Function =====
function medalColor($rank) {
    return match($rank) {
        1 => 'bg-yellow-400 text-black',
        2 => 'bg-gray-300 text-black',
        3 => 'bg-amber-700 text-white',
        default => 'bg-gray-200 text-black',
    };
}
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
                                <!-- ===== Date Filter Form ===== -->
<form method="GET" class="mb-6 flex items-center space-x-4" id="form-bal">
    <div>
        <label class="text-white text-sm" for="start_date">Select Start Date:</label>
        <input type="date" name="start_date" id="start_date" 
               value="<?= htmlspecialchars($start_date) ?>" 
               class="px-3 py-1 rounded-lg border">
    </div>
    <div>
        <label class="text-white text-sm" for="end_date">Select End Date:</label>
        <input type="date" name="end_date" id="end_date" 
               value="<?= htmlspecialchars($end_date) ?>" 
               class="px-3 py-1 rounded-lg border">
    </div>
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" id="filter-btn">
        Filter
    </button>
</form>

                                <!-- ===== Display Top Staff ===== -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- SALES STAFF -->
    <div class="col-span-1">
        <h2 class="text-2xl font-extrabold mb-4 text-white">💰 Top 3 Sales Staff</h2>
        <?php foreach($topSales as $index => $staff): ?>
        <div class="p-5 mb-5 rounded-2xl shadow-2xl backdrop-blur-lg bg-white/10 border border-white/20 flex items-center space-x-5">
            <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-white">
                <img src="../<?= $staff['profile'] ?>" alt="Profile" class="w-full h-full object-cover">
            </div>
            <div class="flex-1 text-white">
                <h3 class="font-bold"><?= $staff['fullname'] ?></h3>
                <p class="text-gray-300 text-sm">Sales: ₦<?= number_format($staff['total_sales'],2) ?></p>
                <p class="text-gray-400 text-xs"><?= $staff['num_sales'] ?> sale(s)</p>
                <p class="text-gray-300 text-xs"><?= $staff['email'] ?> | <?= $staff['phone'] ?></p>
            </div>
            <div class="px-4 py-2 rounded-full <?= medalColor($index+1) ?> text-center font-bold text-xl">
                <?= ['🥇','🥈','🥉'][$index] ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- JOB STAFF -->
    <div class="col-span-1">
        <h2 class="text-2xl font-extrabold mb-4 text-white">🛠️ Top 3 Job Staff</h2>
        <?php foreach($topJobs as $index => $staff): ?>
        <div class="p-5 mb-5 rounded-2xl shadow-2xl backdrop-blur-lg bg-blue-900/20 border border-blue-400/30 flex items-center space-x-5">
            <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-white">
                <img src="../<?= $staff['profile'] ?>" alt="Profile" class="w-full h-full object-cover img-bx" >
            </div>
            <div class="flex-1 text-white">
                <h3 class="font-bold"><?= $staff['fullname'] ?></h3>
                <p class="text-gray-300 text-sm">Job Revenue: ₦<?= number_format($staff['total_jobs'],2) ?></p>
                <p class="text-gray-400 text-xs"><?= $staff['num_jobs'] ?> job(s)</p>
                <p class="text-gray-300 text-xs"><?= $staff['email'] ?> | <?= $staff['phone'] ?></p>
            </div>
            <div class="px-4 py-2 rounded-full <?= medalColor($index+1) ?> text-center font-bold text-xl">
                <?= ['🥇','🥈','🥉'][$index] ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- OVERALL STAFF -->
    <div class="col-span-1">
        <h2 class="text-2xl font-extrabold mb-4 text-white">👑 Top 3 Overall Staff</h2>
        <?php foreach($topOverall as $index => $staff): ?>
        <div class="p-5 mb-5 rounded-2xl shadow-2xl backdrop-blur-lg bg-purple-800/30 border border-purple-400/40 flex items-center space-x-5">
            <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-white">
                <img src="../<?= $staff['profile'] ?>" alt="Profile" class="w-full h-full object-cover img-bx" >
            </div>
            <div class="flex-1 text-white">
                <h3 class="font-bold"><?= $staff['fullname'] ?></h3>
                <p class="text-gray-300 text-sm">Combined Score: ₦<?= number_format($staff['overall_score'],2) ?></p>
                <p class="text-gray-300 text-xs"><?= $staff['email'] ?> | <?= $staff['phone'] ?></p>
            </div>
            <div class="px-4 py-2 rounded-full <?= medalColor($index+1) ?> text-center font-bold text-xl">
                <?= ['🥇','🥈','🥉'][$index] ?>
            </div>
        </div>
        <?php endforeach; ?>
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

