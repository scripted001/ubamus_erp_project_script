<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');



$user_id = $_SESSION['user_id'] ?? null; // get logged-in user ID

$company_id = null;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $company_id = $row['company_id'];
    } else {
        die("User not found in database.");
    }
} else {
    die("User not logged in.");
}

/* --------------------------------
    TOTAL SALES THIS MONTH
----------------------------------- */
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) AS total_sales_this_month
    FROM sales
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE)
      AND YEAR(created_at) = YEAR(CURRENT_DATE)
      AND company_id = ?
");
$stmt->execute([$company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_this_month = $row['total_sales_this_month'] ?? 0;

/* --------------------------------
    TOTAL SALES LAST MONTH
----------------------------------- */
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) AS total_sales_last_month
    FROM sales
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
      AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
      AND company_id = ?
");
$stmt->execute([$company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_last_month = $row['total_sales_last_month'] ?? 0;

/* --------------------------------
    PERCENTAGE CHANGE (MONTH vs LAST MONTH)
----------------------------------- */
$percentage_change = 0;
if ($total_last_month > 0) {
    $percentage_change = (($total_this_month - $total_last_month) / $total_last_month) * 100;
}

/* --------------------------------
    AVERAGE SALE VOLUME THIS MONTH
----------------------------------- */
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS invoice_count_this_month
    FROM sales
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE)
      AND YEAR(created_at) = YEAR(CURRENT_DATE)
      AND company_id = ?
");
$stmt->execute([$company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$invoice_count_this_month = $row['invoice_count_this_month'] ?? 0;

$average_sale_this_month = $invoice_count_this_month > 0 
    ? ($total_this_month / $invoice_count_this_month)
    : 0;

/* --------------------------------
    BEST SELLING PRODUCT THIS MONTH
----------------------------------- */
$stmt = $pdo->prepare("
    SELECT si.product_id, SUM(si.quantity) AS total_qty
    FROM sale_items si
    INNER JOIN sales s ON si.sale_id = s.id
    WHERE MONTH(s.created_at) = MONTH(CURRENT_DATE)
      AND YEAR(s.created_at) = YEAR(CURRENT_DATE)
      AND s.company_id = ?
    GROUP BY si.product_id
    ORDER BY total_qty DESC
    LIMIT 1
");
$stmt->execute([$company_id]);
$best = $stmt->fetch(PDO::FETCH_ASSOC);

$best_product_id  = $best['product_id'] ?? null;
$best_product_qty = $best['total_qty'] ?? 0;

$best_product_name = "N/A";
if ($best_product_id) {
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$best_product_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    $best_product_name = $p['name'] ?? 'Unknown Product';
}

/* --------------------------------
    AVERAGE SALE VOLUME LAST MONTH
----------------------------------- */
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) AS total_last_month, COUNT(*) AS invoice_count_last_month
    FROM sales
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
      AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
      AND company_id = ?
");
$stmt->execute([$company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$best_product_qty_last_month = 0; // initialize to avoid undefined variable

if ($best_product_id) {
    $stmt = $pdo->prepare("
        SELECT SUM(si.quantity) AS total_qty_last_month
        FROM sale_items si
        INNER JOIN sales s ON si.sale_id = s.id
        WHERE MONTH(s.created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
          AND YEAR(s.created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
          AND s.company_id = ?
          AND si.product_id = ?
    ");
    $stmt->execute([$company_id, $best_product_id]);
    $last_month_best = $stmt->fetch(PDO::FETCH_ASSOC);

    $best_product_qty_last_month = $last_month_best['total_qty_last_month'] ?? 0;
}


$total_last_month_sales   = $row['total_last_month'] ?? 0;
$invoice_count_last_month = $row['invoice_count_last_month'] ?? 0;

$average_sale_last_month = $invoice_count_last_month > 0 
    ? ($total_last_month_sales / $invoice_count_last_month)
    : 0;

/* --------------------------------
    PERCENTAGE CHANGE FOR AVERAGE SALE
----------------------------------- */
$average_sale_this_month_change = 0;
if ($average_sale_last_month > 0) {
    $average_sale_this_month_change = (($average_sale_this_month - $average_sale_last_month) / $average_sale_last_month) * 100;
}

/* --------------------------------
    BEST PRODUCT CHANGE
----------------------------------- */
$best_product_change = 0; 
if ($best_product_qty_last_month > 0) {
    $best_product_change = (($best_product_qty - $best_product_qty_last_month) / $best_product_qty_last_month) * 100;
}

/* ===========================
    TOTAL PROFIT THIS MONTH
============================ */
$stmt = $pdo->prepare("
    SELECT 
        SUM((si.unit_price - p.cost_price) * si.quantity) AS total_profit_month
    FROM sale_items si
    INNER JOIN sales s ON si.sale_id = s.id
    INNER JOIN products p ON si.product_id = p.id
    WHERE MONTH(s.created_at) = MONTH(CURDATE())
      AND YEAR(s.created_at) = YEAR(CURDATE())
      AND s.company_id = ?
");
$stmt->execute([$company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_profit_month = $row['total_profit_month'] ?? 0;

/* --------------------------------
    TOTAL PROFIT LAST MONTH
----------------------------------- */
$stmt = $pdo->prepare("
    SELECT 
        SUM((si.unit_price - p.cost_price) * si.quantity) AS total_profit_last_month
    FROM sale_items si
    INNER JOIN sales s ON si.sale_id = s.id
    INNER JOIN products p ON si.product_id = p.id
    WHERE MONTH(s.created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)
      AND YEAR(s.created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)
      AND s.company_id = ?
");
$stmt->execute([$company_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_profit_last_month = $row['total_profit_last_month'] ?? 0;


$profit_month_change = 0;
if ($total_profit_last_month > 0) {
    $profit_month_change = (($total_profit_month - $total_profit_last_month) / $total_profit_last_month) * 100;
}




 /* ===========================
   DAILY: Last 24 Hours
=========================== */
$stmt_daily = $pdo->prepare("
    SELECT 
        HOUR(created_at) AS hour_label,
        SUM(total_amount) AS total
    FROM sales
    WHERE created_at >= CURDATE()
      AND created_at < CURDATE() + INTERVAL 1 DAY
      AND company_id = ?
    GROUP BY hour_label
    ORDER BY hour_label
");
$stmt_daily->execute([$company_id]);
$raw_daily = $stmt_daily->fetchAll(PDO::FETCH_KEY_PAIR);
$daily_labels = [];
$daily_values = [];

for ($hour = 0; $hour < 24; $hour++) {
    $label = date("g A", strtotime("$hour:00"));

    $daily_labels[] = $label;
    $daily_values[] = isset($raw_daily[$hour]) ? (float)$raw_daily[$hour] : 0;
}

/* ===========================
   WEEKLY: Last 7 Days
=========================== */
$stmt_weekly = $pdo->prepare("
    SELECT 
        DATE(created_at) AS day_label,
        SUM(total_amount) AS total
    FROM sales
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
      AND company_id = ?
    GROUP BY day_label
");
$stmt_weekly->execute([$company_id]);
$raw_weekly = $stmt_weekly->fetchAll(PDO::FETCH_KEY_PAIR);

$weekly_labels = [];
$weekly_values = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i day"));
    $weekly_labels[] = date("D", strtotime($date));
    $weekly_values[] = isset($raw_weekly[$date]) ? (float)$raw_weekly[$date] : 0;
}


/* ===========================
   MONTHLY: January – December
=========================== */
$currentYear = date("Y");

$stmt_monthly = $pdo->prepare("
    SELECT 
        MONTH(created_at) AS month_num,
        SUM(total_amount) AS total
    FROM sales
    WHERE YEAR(created_at) = ?
      AND company_id = ?
    GROUP BY month_num
");
$stmt_monthly->execute([$currentYear, $company_id]);
$raw_monthly = $stmt_monthly->fetchAll(PDO::FETCH_KEY_PAIR);

$monthly_labels = [];
$monthly_values = [];

for ($m = 1; $m <= 12; $m++) {
    $monthly_labels[] = date("M", mktime(0, 0, 0, $m, 1));
    $monthly_values[] = isset($raw_monthly[$m]) ? (float)$raw_monthly[$m] : 0;
}

/* ===========================
   YEARLY: From 2025 Onwards
=========================== */
$stmt_year_range = $pdo->prepare("
    SELECT 
        MIN(YEAR(created_at)) AS start_year,
        MAX(YEAR(created_at)) AS end_year
    FROM sales
    WHERE company_id = ?
");
$stmt_year_range->execute([$company_id]);
$range = $stmt_year_range->fetch(PDO::FETCH_ASSOC);

$startYear = max(2025, (int)$range['start_year']);
$endYear   = (int)$range['end_year'];

$stmt_yearly = $pdo->prepare("
    SELECT 
        YEAR(created_at) AS year_label,
        SUM(total_amount) AS total
    FROM sales
    WHERE company_id = ?
    GROUP BY year_label
");
$stmt_yearly->execute([$company_id]);
$raw_yearly = $stmt_yearly->fetchAll(PDO::FETCH_KEY_PAIR);

$yearly_labels = [];
$yearly_values = [];

for ($y = $startYear; $y <= $endYear; $y++) {
    $yearly_labels[] = (string)$y;
    $yearly_values[] = isset($raw_yearly[$y]) ? (float)$raw_yearly[$y] : 0;
}


// Fetch this month's sales (company-wide)
$stmt = $pdo->prepare("
    SELECT invoice_number, customer_name, customer_email, customer_phone, total_amount, created_at
    FROM sales
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE)
      AND YEAR(created_at) = YEAR(CURRENT_DATE)
      AND company_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$company_id]);
$today_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// most patronizing customer of the month

// Assuming $pdo is your PDO connection and $company_id is set
$stmt = $pdo->prepare("
    SELECT customer_name, customer_email, customer_phone, SUM(total_amount) AS total_spent
    FROM sales
    WHERE company_id = ?
      AND MONTH(created_at) = MONTH(CURRENT_DATE)
      AND YEAR(created_at) = YEAR(CURRENT_DATE)
    GROUP BY customer_name, customer_email, customer_phone
    ORDER BY total_spent DESC
    LIMIT 1
");
$stmt->execute([$company_id]);
$top_customer = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("
    SELECT st.firstname, st.lastname, SUM(sa.total_amount) AS total_sales, COUNT(sa.id) AS total_orders
    FROM sales sa
    JOIN users u ON sa.user_id = u.id
    JOIN staffs st ON u.email = st.email
    WHERE sa.company_id = ?
      AND MONTH(sa.created_at) = MONTH(CURRENT_DATE)
      AND YEAR(sa.created_at) = YEAR(CURRENT_DATE)
    GROUP BY st.id, st.firstname, st.lastname
    ORDER BY total_sales DESC
    LIMIT 1
");
$stmt->execute([$company_id]);
$top_cashier = $stmt->fetch(PDO::FETCH_ASSOC);

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
            <?php
              include('header.php');
            ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
              <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12">
    <!-- Metric Group Two -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-3 xl:grid-cols-3">

        <!-- Metric Item Start: Total Sales This Month -->
        <div style="border-radius: 1rem; border: 1px solid #e5e7eb; background-color: #ffffff; padding: 1.25rem; max-width: 300px; font-family: Arial, sans-serif;">
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.5rem 0;">
                Total Sales This Month
            </p>

            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <p style="font-size: 1.0rem; font-weight: bold; color: #111827; margin: 0;">
                        ₦<?= number_format($total_this_month, 2); ?>
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 500;  
                        color: <?= $percentage_change >= 0 ? '#16a34a' : '#dc2626'; ?>;  
                        background-color: <?= $percentage_change >= 0 ? 'rgba(5,150,105,0.1)' : 'rgba(220,38,38,0.1)'; ?>;">
                        <?= ($percentage_change >= 0 ? '+' : '') . number_format($percentage_change, 1); ?>%
                    </span>

                    <span style="font-size: 0.75rem; color: #6b7280;">
                        Vs Last Month
                    </span>
                </div>
            </div>
        </div>
        <!-- Metric Item End -->


        <!-- Metric Item Start: Average Sales Volume -->
        <div style="border-radius: 1rem; border: 1px solid #e5e7eb; background-color: #ffffff; padding: 1.25rem; max-width: 300px; font-family: Arial, sans-serif;">
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.5rem 0;">
                Average Sale Volume (This Month)
            </p>

            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <p style="font-size: 1.0rem; font-weight: bold; color: #111827; margin: 0;">
                        ₦<?= number_format($average_sale_this_month, 2); ?>
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 500;
                        color: <?= $average_sale_this_month_change >= 0 ? '#16a34a' : '#dc2626'; ?>;
                        background-color: <?= $average_sale_this_month_change >= 0 ? 'rgba(5,150,105,0.1)' : 'rgba(220,38,38,0.1)'; ?>;">
                        <?= ($average_sale_this_month_change >= 0 ? '+' : '') . number_format($average_sale_this_month_change, 2); ?>%
                    </span>

                    <span style="font-size: 0.75rem; color: #6b7280;">
                        Vs Last Month
                    </span>
                </div>
            </div>
        </div>

        <!-- Metric Item End -->

        <!-- Metric Item Start: Best-Selling Product -->
        <div style="border-radius: 1rem; border: 1px solid #e5e7eb; background-color: #ffffff; padding: 1.25rem; max-width: 300px; font-family: Arial, sans-serif;">
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.5rem 0;">
                Best Selling Product (This Month)
            </p>

            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <p style="font-size: 1.0rem; font-weight: bold; color: #111827; margin: 0;">
                        <?= htmlspecialchars($best_product_name); ?> (<?= $best_product_qty; ?> sold)
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 500;
                        color: <?= ($best_product_change ?? 0) >= 0 ? '#16a34a' : '#dc2626'; ?>;
                        background-color: <?= ($best_product_change ?? 0) >= 0 ? 'rgba(5,150,105,0.1)' : 'rgba(220,38,38,0.1)'; ?>;">
                        <?= (($best_product_change ?? 0) >= 0 ? '+' : '') . number_format($best_product_change ?? 0, 1); ?>%
                    </span>

                    <span style="font-size: 0.75rem; color: #6b7280;">
                        Vs Last Month
                    </span>
                </div>
            </div>
        </div>
        <!-- Metric Item End -->

        <div style="border-radius: 1rem; border: 1px solid #e5e7eb; background-color: #ffffff; padding: 1.25rem; max-width: 300px; font-family: Arial, sans-serif;">
    <p style="font-size: 0.875rem; color: #6b7280; margin: 0 0 0.5rem 0;">
        Total Sales Profit This Month
    </p>

    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <p style="font-size: 1.0rem; font-weight: bold; color: #111827; margin: 0;">
                ₦<?= number_format($total_profit_month, 2); ?>
            </p>
            <span style="font-size: 0.75rem; color: #6b7280;">
                This Month
            </span>
        </div>

        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
            <span style="display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 500;
                color: <?= $profit_month_change >= 0 ? '#16a34a' : '#dc2626'; ?>;
                background-color: <?= $profit_month_change >= 0 ? 'rgba(5,150,105,0.1)' : 'rgba(220,38,38,0.1)'; ?>;">
                <?= ($profit_month_change >= 0 ? '+' : '') . number_format($profit_month_change, 1); ?>%
            </span>

            <span style="font-size: 0.75rem; color: #6b7280;">
                Vs Last Month
            </span>
        </div>
    </div>
</div>


    </div>
</div>

                <!-- Metric Group Two -->
       

              <div class="col-span-12">
                <div x-data="chartHandler()" class="rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                  <div class="flex flex-wrap items-start justify-between gap-5">
                    <div>
                      <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                        Analytics
                      </h3>
                      <span class="block text-theme-sm text-gray-500 dark:text-gray-400">
                        Bar chart displaying sales performance
                      </span>
                    </div>

                    <div class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                      <button @click="showDaily()" :class="active=='daily' ? activeClass : inactiveClass" class="rounded-md px-3 py-2 text-theme-sm font-medium">Daily</button>

                      <button @click="showWeekly()" :class="active=='weekly' ? activeClass : inactiveClass" class="rounded-md px-3 py-2 text-theme-sm font-medium">Weekly</button>

                      <button @click="showMonthly()" :class="active=='monthly' ? activeClass : inactiveClass" class="rounded-md px-3 py-2 text-theme-sm font-medium">Monthly</button>

                      <button @click="showYearly()" :class="active=='yearly' ? activeClass : inactiveClass" class="rounded-md px-3 py-2 text-theme-sm font-medium">Annually</button>
                    </div>
                  </div>
                  <div class="custom-scrollbar max-w-full overflow-x-auto">
                    <canvas id="salesChart" height="330"></canvas>
                  </div>
                </div>
              </div>
                <div class="col-span-12">
                <div class="col-span-12 space-y-6 xl:col-span-7">
                  <!-- Metric Item Start: Most Patronizing Customer -->
                  <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                      <!-- Trophy Icon for Top Customer -->
                      <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24">
                        <path d="M12 2L15 8H21L16.5 12L18 18L12 14.5L6 18L7.5 12L3 8H9L12 2Z"/>
                      </svg>
                    </div>
                    <div class="mt-5 flex items-end justify-between">
                      <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Top Customer</span>
                          <?php if ($top_customer): ?>
                            <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                              <?= htmlspecialchars($top_customer['customer_name']) ?>
                            </h4>
                            <p class="text-gray-500 dark:text-gray-400">Spent: ₦<?= number_format($top_customer['total_spent'], 2) ?></p>
                            <p class="text-gray-400 text-sm"><?= htmlspecialchars($top_customer['customer_email']) ?> | <?= htmlspecialchars($top_customer['customer_phone']) ?></p>
                            <?php else: ?>
                            <p class="text-gray-500 dark:text-gray-400">No sales this month</p>
                          <?php endif; ?>
                      </div>
                      <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                        <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12">
                          <path d="M6 0L7.09 4.26H11.18L7.73 6.9L8.82 11.18L6 8.54L3.18 11.18L4.27 6.9L0.82 4.26H4.91L6 0Z"/>
                        </svg>
                        Top
                      </span>
                    </div>
                  </div>
                  <!-- Metric Item End -->
                  <!-- ====== Chart One Start -->
                  <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <!-- Top Cashier Metric -->
                    <?php if ($top_cashier): ?>
                    <div class="bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                      <div class="flex items-center justify-between">
                        <div>
                          <span class="text-sm text-gray-500 dark:text-gray-400">Top Cashier</span>
                          <h4 class="mt-1 text-title-sm font-bold text-gray-800 dark:text-white/90">
                            <?= htmlspecialchars($top_cashier['firstname'] . ' ' . $top_cashier['lastname']) ?>
                          </h4>
                          <p class="text-gray-500 dark:text-gray-400">Total Sales: ₦<?= number_format($top_cashier['total_sales'], 2) ?></p>
                          <p class="text-gray-400 text-sm">Orders Processed: <?= $top_cashier['total_orders'] ?></p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-800">
                          <svg class="fill-green-600 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24">
                            <path d="M12 2L15 8H21L16.5 12L18 18L12 14.5L6 18L7.5 12L3 8H9L12 2Z"/>
                          </svg>
                        </div>
                      </div>
                      <?php else: ?>
                      <p class="text-gray-500 dark:text-gray-400">No sales this month</p>
                    <?php endif; ?>
                    </div>
                    
                  </div>
                  <!-- ====== Chart One End -->
                </div>          
              </div>
             <div class="col-span-12">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
        
        <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    This Month Sales
                </h3>
                <div style="display:flex; gap:10px;">

<select id="search_option" class="border px-3 py-2">
<option value="invoice">Invoice Number</option>
<option value="customer">Customer Name</option>
<option value="date">Date</option>
</select>

<input 
type="text" 
id="search_value" 
placeholder="Search..." 
class="border px-3 py-2"
/>

</div>

</div>
            </div>
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full">
                <thead>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <th class="px-6 py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Invoice No.</p>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Email</p>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Phone</p>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Total</p>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Date</p>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Action</p>
                        </th>
                    </tr>
                </thead>

                <tbody id="salesTable">
                    <?php if (count($today_sales) > 0): ?>
                        <?php foreach ($today_sales as $sale): ?>
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                    <?= htmlspecialchars($sale['invoice_number']) ?>
                                </p>
                            </td>

                            <td class="px-6 py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                    <?= htmlspecialchars($sale['customer_name']) ?>
                                </p>
                            </td>

                            <td class="px-6 py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                    <?= htmlspecialchars($sale['customer_email']) ?>
                                </p>
                            </td>

                            <td class="px-6 py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                    <?= htmlspecialchars($sale['customer_phone']) ?>
                                </p>
                            </td>

                            <td class="px-6 py-3.5">
                                <p class="text-theme-sm text-success-600">
                                    ₦<?= number_format($sale['total_amount'], 2) ?>
                                </p>
                            </td>

                            <td class="px-6 py-3.5">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                    <?= date("M d, Y h:i A", strtotime($sale['created_at'])) ?>
                                </p>
                            </td>

                           <td class="px-6 py-3.5">
    <?php 
       $pdf_file = "../invoices/invoice_{$sale['invoice_number']}.pdf";
        if(file_exists($pdf_file)): 
    ?>
        <a href="<?= $pdf_file ?>" target="_blank" class="text-blue-600 hover:underline text-theme-sm">
            View
        </a>
    <?php else: ?>
        <span class="text-gray-400 text-theme-sm">Not found</span>
    <?php endif; ?>
</td>

                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No sales recorded this month.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
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
<script src="https://cdn.jsdelivr.net/npm/alpinejs" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function chartHandler() {
    return {
        active: 'daily',

        activeClass: "shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800",
        inactiveClass: "text-gray-500 dark:text-gray-400",

        chart: null,

        datasets: {
            daily: {
                labels: <?php echo json_encode($daily_labels); ?>,
                values: <?php echo json_encode($daily_values); ?>
            },
            weekly: {
                labels: <?php echo json_encode($weekly_labels); ?>,
                values: <?php echo json_encode($weekly_values); ?>
            },
            monthly: {
                labels: <?php echo json_encode($monthly_labels); ?>,
                values: <?php echo json_encode($monthly_values); ?>
            },
            yearly: {
                labels: <?php echo json_encode($yearly_labels); ?>,
                values: <?php echo json_encode($yearly_values); ?>
            }
        },

        init() {
            this.renderChart('daily');
        },

        renderChart(type) {
            if (this.chart) this.chart.destroy();

            this.chart = new Chart(document.getElementById('salesChart'), {
                type: 'bar',
                data: {
                    labels: this.datasets[type].labels,
                    datasets: [{
                        label: "Sales (₦)",
                        data: this.datasets[type].values,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        showDaily()  { this.active = 'daily';  this.renderChart('daily'); },
        showWeekly() { this.active = 'weekly'; this.renderChart('weekly'); },
        showMonthly(){ this.active = 'monthly';this.renderChart('monthly'); },
        showYearly() { this.active = 'yearly'; this.renderChart('yearly'); }
    }
}
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

$(document).ready(function(){

function fetchMonthlySales(){

var option = $("#search_option").val();
var value  = $("#search_value").val();

$.ajax({
url: "ajax_monthly_sales_search.php",
method: "POST",
data:{
search_option: option,
search_value: value
},
success:function(data){
$("#salesTable").html(data);
}
});

}

$("#search_value").keyup(function(){
fetchMonthlySales();
});

$("#search_option").change(function(){
fetchMonthlySales();
});

});

</script>


</body>

</html>
