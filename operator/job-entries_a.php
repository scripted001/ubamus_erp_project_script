<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

$company_id  = (int)($_SESSION['company_id'] ?? 0);
$user_id     = (int)($_SESSION['user_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);

/*
 * SECURITY SCOPE:
 * This dashboard is restricted to the logged-in user and the active location.
 * Never accept user_id or location_id scope from browser-submitted filters.
 */
if ($company_id <= 0 || $user_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Access denied: a valid company, user, and active location session is required.');
}

function jobs_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/*
 * Load company-owned lookup values. Jobs are company-scoped below; the
 * location filter is applied in the browser so users can compare locations.
 */
$customerStmt = $pdo->prepare("SELECT id, fullname FROM customers WHERE company_id = ? ORDER BY fullname ASC");
$customerStmt->execute([$company_id]);
$customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);

$userStmt = $pdo->prepare("
    SELECT id, username
    FROM users
    WHERE company_id = ? AND id = ?
    ORDER BY username ASC
");
$userStmt->execute([$company_id, $user_id]);
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

$locationStmt = $pdo->prepare("
    SELECT id, name
    FROM locations
    WHERE company_id = ? AND id = ?
    ORDER BY name ASC
");
$locationStmt->execute([$company_id, $location_id]);
$locations = $locationStmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * Fetch only this active user's jobs for this active location.
 * Date filtering remains client-side so the date range, KPIs, chart, and export
 * all operate on the same securely scoped dataset. Joins remain company-safe.
 */
$jobsStmt = $pdo->prepare("
    SELECT
        j.*,
        c.fullname AS customer_name,
        u.username AS user_name,
        l.name AS location_name
    FROM jobs j
    LEFT JOIN customers c ON c.id = j.customer_id AND c.company_id = j.company_id
    LEFT JOIN users u ON u.id = j.user_id AND u.company_id = j.company_id
    LEFT JOIN locations l ON l.id = j.location_id AND l.company_id = j.company_id
    WHERE j.company_id = ?
      AND j.user_id = ?
      AND j.location_id = ?
    ORDER BY j.created_at DESC, j.id DESC
");
$jobsStmt->execute([$company_id, $user_id, $location_id]);
$jobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$yearStart = date('Y-01-01');
$initialFrom = $yearStart;
$initialTo = $today;
$analysisMode = 'annual';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= jobs_h($company_name ?? 'Company') ?> | Annual Jobs Intelligence</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <style>
        .jobs-page{width:100%;max-width:1600px;margin:0 auto}
        .jobs-hero{position:relative;overflow:hidden;border-radius:22px;padding:26px;margin-bottom:18px;color:#fff;background:radial-gradient(circle at 88% 10%,rgba(192,153,57,.2),transparent 32%),linear-gradient(130deg,#111827,#1c293d 62%,#111827);box-shadow:0 16px 42px rgba(15,23,42,.13)}
        .jobs-hero:after{content:"";position:absolute;width:190px;height:190px;right:-65px;bottom:-110px;border:1px solid #ffffff18;border-radius:50%;box-shadow:0 0 0 28px #ffffff08,0 0 0 56px #ffffff05}
        .jobs-hero-content{position:relative;z-index:1}
        .jobs-eyebrow{display:flex;align-items:center;gap:8px;color:#d9bd72;font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
        .jobs-title{margin:8px 0 0;font-size:clamp(25px,3vw,35px);font-weight:850;letter-spacing:-.035em;color:#fff}
        .jobs-subtitle{margin:7px 0 0;max-width:680px;color:#c3cad5;font-size:13px;line-height:1.6}
        .jobs-hero-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
        .hero-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid #ffffff1b;border-radius:999px;background:#ffffff0b;color:#e8edf5;font-size:10px;font-weight:700}
        .jobs-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:12px}
        .jobs-stat{display:flex;align-items:center;gap:12px;min-width:0;padding:16px;border:1px solid #e7eaf0;border-radius:17px;background:#fff;box-shadow:0 7px 24px #0f172a0a}
        .jobs-stat-icon{display:flex;align-items:center;justify-content:center;width:42px;height:42px;min-width:42px;border-radius:13px;background:#f6f1e5;color:#836001;font-size:16px}
        .jobs-stat-label{color:#7a8494;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.055em}
        .jobs-stat-value{margin-top:4px;color:#111827;font-size:clamp(17px,2vw,23px);font-weight:850;letter-spacing:-.025em;overflow-wrap:anywhere}
        .jobs-stat-note{margin-top:3px;color:#9aa3b1;font-size:10px}
        .jobs-insight-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(260px,.65fr);gap:12px;margin-bottom:18px}
        .jobs-panel{background:#fff;border:1px solid #e7eaf0;border-radius:18px;padding:17px;box-shadow:0 7px 24px #0f172a0a;min-width:0}
        .panel-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;color:#172033;font-size:13px;font-weight:850}
        .panel-title small{color:#8993a3;font-size:10px;font-weight:600}
        .chart-bars{height:145px;display:grid;grid-template-columns:repeat(7,minmax(0,1fr));align-items:end;gap:10px;padding:5px 2px 0}
        .chart-day{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:7px;height:100%;min-width:0}
        .chart-value{max-width:100%;color:#788396;font-size:9px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .chart-track{display:flex;align-items:flex-end;justify-content:center;width:100%;height:100%;min-height:6px}
        .chart-bar{width:min(28px,70%);min-height:3px;border-radius:7px 7px 3px 3px;background:linear-gradient(180deg,#c9a64b,#836001);transition:height .25s ease}
        .chart-label{color:#8993a3;font-size:9px;font-weight:700}
        .collection-line{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:13px 0 7px;color:#647084;font-size:11px}
        .progress{height:8px;overflow:hidden;border-radius:99px;background:#edf0f4}
        .progress span{display:block;height:100%;width:0;background:linear-gradient(90deg,#a17b20,#d7b85f);border-radius:99px;transition:width .25s ease}
        .insight-list{display:grid;gap:12px}
        .insight-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-bottom:10px;border-bottom:1px solid #eef0f4}
        .insight-row:last-child{border-bottom:0;padding-bottom:0}
        .insight-name{color:#7b8596;font-size:11px}
        .insight-value{color:#172033;font-size:13px;font-weight:850;text-align:right}
        .jobs-filter-panel{background:#fff;border:1px solid #e7eaf0;border-radius:19px;padding:17px;margin-bottom:17px;box-shadow:0 7px 24px #0f172a0a}
        .jobs-filter-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
        .jobs-filter-heading{display:flex;align-items:center;gap:9px;color:#172033;font-size:13px;font-weight:850}
        .jobs-filter-heading i{color:#836001}
        .jobs-filter-count{display:none;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#836001;color:white;font-size:10px;font-weight:800}
        .jobs-filter-count.active{display:inline-flex}
        .jobs-clear,.jobs-action{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid #e0e4ea;border-radius:10px;background:#fff;color:#596579;padding:9px 11px;font-size:11px;font-weight:800;cursor:pointer;text-decoration:none}
        .jobs-clear:hover,.jobs-action:hover{border-color:#b89a4b;color:#836001}
        .jobs-actions{display:flex;gap:8px;flex-wrap:wrap}
        .jobs-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
        .jobs-field{min-width:0}
        .jobs-field label{display:block;margin-bottom:6px;color:#697386;font-size:10px;font-weight:800}
        .jobs-input-wrap{position:relative}
        .jobs-input-wrap>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ba3af;pointer-events:none;font-size:12px}
        .jobs-control{width:100%;min-height:42px;border:1px solid #dfe3e9;border-radius:11px;background:#fafbfc;color:#1f2937;padding:9px 11px;outline:none;font-size:12px}
        .jobs-input-wrap .jobs-control{padding-left:34px}
        .jobs-control:focus{border-color:#a17b20;background:#fff;box-shadow:0 0 0 3px #83600116}
        .jobs-results-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:11px}
        .jobs-results-title{color:#172033;font-size:14px;font-weight:850}
        .jobs-results-meta{color:#8b95a5;font-size:10px;font-weight:650}
        .jobs-table-shell{background:#fff;border:1px solid #e7eaf0;border-radius:19px;overflow:hidden;box-shadow:0 8px 26px #0f172a0a}
        .jobs-table-scroll{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .jobs-table{width:100%;min-width:1060px;border-collapse:collapse}
        .jobs-table thead{background:#f8f9fb}
        .jobs-table th{padding:13px 14px;text-align:left;color:#778195;font-size:9px;text-transform:uppercase;letter-spacing:.07em;font-weight:850;white-space:nowrap;border-bottom:1px solid #e9ecf1}
        .jobs-table td{padding:13px 14px;border-bottom:1px solid #eef0f4;color:#374151;font-size:11px;vertical-align:middle}
        .jobs-table tbody tr:hover{background:#fcfaf5}
        .jobs-table tbody tr:last-child td{border-bottom:0}
        .invoice-number{font-weight:850;color:#172033;white-space:nowrap}
        .invoice-number i{color:#836001;margin-right:5px}
        .customer-cell{display:flex;align-items:center;gap:8px;min-width:145px}
        .customer-avatar{width:30px;height:30px;min-width:30px;display:flex;align-items:center;justify-content:center;border-radius:9px;background:#f4efe2;color:#836001;font-size:11px;font-weight:850}
        .customer-name{color:#303846;font-weight:750}
        .money{font-weight:800;white-space:nowrap}
        .balance-positive{color:#c2410c!important}.balance-clear{color:#15803d!important}
        .date-cell{white-space:nowrap;color:#687386}
        .payment-status{display:inline-flex;align-items:center;gap:5px;padding:6px 8px;border-radius:999px;font-size:9px;font-weight:850;white-space:nowrap}
        .payment-paid{background:#eaf8ef;color:#15803d}.payment-partial{background:#fff7df;color:#a16207}.payment-pending,.payment-unpaid{background:#fff0f0;color:#dc2626}.payment-default{background:#eef2f7;color:#64748b}
        .invoice-btn{width:33px;height:33px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;background:#836001;color:#fff!important}
        .jobs-mobile-list{display:none;gap:12px}
        .job-card{background:#fff;border:1px solid #e7eaf0;border-radius:17px;padding:15px;box-shadow:0 7px 23px #0f172a09}
        .job-card-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding-bottom:12px;border-bottom:1px solid #edf0f4}
        .job-card-invoice{font-size:13px;font-weight:850;color:#172033}
        .job-card-invoice i{color:#836001;margin-right:5px}
        .job-card-customer{margin-top:5px;color:#7a8494;font-size:10px}
        .job-card-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:12px}
        .job-card-item{min-width:0;padding:10px;border-radius:10px;background:#f8f9fb}
        .job-card-label{display:block;color:#8993a3;font-size:8px;font-weight:850;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
        .job-card-value{display:block;color:#263041;font-size:11px;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .job-card-footer{display:flex;gap:8px;margin-top:12px}
        .mobile-invoice-btn{flex:1;min-height:38px;display:inline-flex;justify-content:center;align-items:center;gap:7px;border-radius:10px;background:#836001;color:#fff!important;text-decoration:none;font-size:10px;font-weight:850}
        .jobs-empty{display:none;padding:46px 20px;text-align:center;background:#fff;border:1px solid #e7eaf0;border-radius:19px}
        .jobs-empty-icon{width:54px;height:54px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;border-radius:16px;background:#f6f3eb;color:#836001;font-size:20px}
        .jobs-empty h3{margin:0;color:#273142;font-size:15px;font-weight:850}.jobs-empty p{margin:6px auto 0;color:#8993a3;font-size:11px;max-width:340px}
        .jobs-footer-note{margin-top:12px;color:#9aa3b1;font-size:10px;text-align:right}
        .dark .jobs-stat,.dark .jobs-panel,.dark .jobs-filter-panel,.dark .jobs-table-shell,.dark .job-card,.dark .jobs-empty{background:#111827;border-color:#263143}
        .dark .jobs-stat-label,.dark .panel-title,.dark .jobs-filter-heading,.dark .jobs-results-title,.dark .jobs-field label,.dark .insight-value,.dark .invoice-number,.dark .customer-name,.dark .job-card-invoice,.dark .job-card-value{color:#e2e8f0}
        .dark .jobs-stat-value{color:#f8fafc}.dark .jobs-control{background:#182131;border-color:#303c4f;color:#f8fafc}.dark .jobs-table thead,.dark .job-card-item{background:#182131}.dark .jobs-table th,.dark .jobs-table td,.dark .insight-row{border-color:#263143}.dark .jobs-table td{color:#cbd5e1}.dark .jobs-table tbody tr:hover{background:#172131}.dark .jobs-clear,.dark .jobs-action{background:#182131;border-color:#303c4f;color:#cbd5e1}.dark .progress{background:#263143}
        @media(max-width:1100px){.jobs-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.jobs-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:767px){.jobs-hero{padding:20px;border-radius:17px}.jobs-stat-grid{gap:9px}.jobs-stat{padding:12px;gap:9px}.jobs-stat-icon{width:35px;height:35px;min-width:35px}.jobs-stat-value{font-size:16px}.jobs-insight-grid{grid-template-columns:1fr}.jobs-filter-panel{padding:14px}.jobs-filter-grid{grid-template-columns:1fr}.jobs-table-shell{display:none}.jobs-mobile-list{display:grid}.jobs-results-header{align-items:flex-start}.jobs-actions .jobs-action{padding:8px}.chart-bars{gap:5px}}
        @media(max-width:390px){.jobs-stat-grid{grid-template-columns:1fr}.jobs-hero-meta{gap:6px}}
        @media print{body{background:#fff!important}.jobs-hero{background:#fff!important;color:#111!important;box-shadow:none;border:1px solid #ddd}.jobs-title,.jobs-subtitle{color:#111!important}.jobs-filter-panel,.jobs-actions,.invoice-btn,.mobile-invoice-btn{display:none!important}.jobs-stat-grid{grid-template-columns:repeat(4,1fr)}.jobs-table-shell{display:block!important;box-shadow:none}.jobs-table{min-width:0}.jobs-table th,.jobs-table td{padding:6px;font-size:8px}.jobs-mobile-list{display:none!important}}
    .chart-bars{grid-template-columns:repeat(12,minmax(0,1fr));gap:7px}\n        @media(max-width:767px){.chart-bars{overflow-x:auto;min-width:0}.chart-label{overflow:hidden;max-width:100%}}\n    </style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}"
      x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
      :class="{'dark bg-gray-900': darkMode === true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="jobs-page">
                    <section class="jobs-hero">
                        <div class="jobs-hero-content">
                            <div class="jobs-eyebrow"><i class="fa-solid fa-chart-line"></i> Operations Intelligence</div>
                            <h1 class="jobs-title">Annual Jobs Dashboard</h1>
                            <p class="jobs-subtitle">A focused annual view of job activity, billing, collections and outstanding balances for the active location. Review monthly performance across the selected year.</p>
                            <div class="jobs-hero-meta">
                                <span class="hero-chip"><i class="fa-regular fa-calendar"></i> <span id="periodLabel">This year</span></span>
                                <span class="hero-chip"><i class="fa-solid fa-database"></i> <span id="recordChip"><?= count($jobs) ?> loaded records</span></span>
                                <span class="hero-chip"><i class="fa-solid fa-clock"></i> Updated <?= jobs_h(date('d M Y, g:i A')) ?></span>
                            </div>
                        </div>
                    </section>

                    <div class="jobs-stat-grid">
                        <div class="jobs-stat"><div class="jobs-stat-icon"><i class="fa-solid fa-briefcase"></i></div><div><div class="jobs-stat-label">Jobs in View</div><div class="jobs-stat-value" id="totalJobs">0</div><div class="jobs-stat-note" id="jobsShare">Matching records</div></div></div>
                        <div class="jobs-stat"><div class="jobs-stat-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div><div><div class="jobs-stat-label">Grand Total</div><div class="jobs-stat-value" id="grandTotal">₦0.00</div><div class="jobs-stat-note">Total value of filtered jobs</div></div></div>
                        <div class="jobs-stat"><div class="jobs-stat-icon"><i class="fa-solid fa-circle-check"></i></div><div><div class="jobs-stat-label">Collected</div><div class="jobs-stat-value" id="collectedTotal">₦0.00</div><div class="jobs-stat-note" id="collectionNote">0% collection rate</div></div></div>
                        <div class="jobs-stat"><div class="jobs-stat-icon"><i class="fa-solid fa-wallet"></i></div><div><div class="jobs-stat-label">Outstanding</div><div class="jobs-stat-value" id="outstandingTotal">₦0.00</div><div class="jobs-stat-note" id="outstandingNote">Unpaid balances</div></div></div>
                    </div>

                    <div class="jobs-insight-grid">
                        <section class="jobs-panel">
                            <div class="panel-title"><span><i class="fa-solid fa-chart-column" style="color:#836001;margin-right:7px"></i> Job value by month</span><small id="chartRange">Selected period</small></div>
                            <div class="chart-bars" id="jobsChart" aria-label="Job value by month chart"></div>
                            <div class="collection-line"><span>Collection rate</span><strong id="collectionRateText">0%</strong></div>
                            <div class="progress"><span id="collectionProgress"></span></div>
                        </section>
                        <section class="jobs-panel">
                            <div class="panel-title"><span><i class="fa-solid fa-lightbulb" style="color:#836001;margin-right:7px"></i> Operational snapshot</span><small>Filtered records</small></div>
                            <div class="insight-list">
                                <div class="insight-row"><span class="insight-name">Average job value</span><span class="insight-value" id="averageValue">₦0.00</span></div>
                                <div class="insight-row"><span class="insight-name">Fully paid jobs</span><span class="insight-value" id="paidJobs">0</span></div>
                                <div class="insight-row"><span class="insight-name">Part-paid jobs</span><span class="insight-value" id="partialJobs">0</span></div>
                                <div class="insight-row"><span class="insight-name">Overdue balance</span><span class="insight-value" id="overdueTotal">₦0.00</span></div>
                                <div class="insight-row"><span class="insight-name">Top customer by job value</span><span class="insight-value" id="topCustomer">—</span></div>
                            </div>
                        </section>
                    </div>

                    <section class="jobs-filter-panel">
                        <div class="jobs-filter-top">
                            <div class="jobs-filter-heading"><i class="fa-solid fa-sliders"></i><span>Smart Filters</span><span id="activeFilterCount" class="jobs-filter-count">0</span></div>
                            <div class="jobs-actions">
                                <button type="button" id="exportCsv" class="jobs-action"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                                <button type="button" id="printReport" class="jobs-action"><i class="fa-solid fa-print"></i> Print</button>
                                <button type="button" id="clearFilters" class="jobs-clear"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                            </div>
                        </div>
                        <div class="jobs-filter-grid">
                            <div class="jobs-field"><label for="filterSearch">Search records</label><div class="jobs-input-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="filterSearch" class="jobs-control" placeholder="Invoice, customer, staff..." autocomplete="off"></div></div>
                            <div class="jobs-field"><label for="filterFrom">Date from</label><div class="jobs-input-wrap"><i class="fa-regular fa-calendar"></i><input type="date" id="filterFrom" class="jobs-control" value="<?= jobs_h($initialFrom) ?>"></div></div>
                            <div class="jobs-field"><label for="filterTo">Date to</label><div class="jobs-input-wrap"><i class="fa-regular fa-calendar-check"></i><input type="date" id="filterTo" class="jobs-control" value="<?= jobs_h($initialTo) ?>"></div></div>
                            <div class="jobs-field"><label for="filterCustomer">Customer</label><div class="jobs-input-wrap"><i class="fa-solid fa-user"></i><select id="filterCustomer" class="jobs-control"><option value="">All customers</option><?php foreach($customers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= jobs_h($c['fullname']) ?></option><?php endforeach; ?></select></div></div>
                            <div class="jobs-field"><label for="filterLocation">Location</label><div class="jobs-input-wrap"><i class="fa-solid fa-location-dot"></i><select id="filterLocation" class="jobs-control"><?php foreach($locations as $l): ?><option value="<?= (int)$l['id'] ?>" selected><?= jobs_h($l['name']) ?></option><?php endforeach; ?></select></div></div>
                            <div class="jobs-field"><label for="filterStaff">Created by / staff</label><div class="jobs-input-wrap"><i class="fa-solid fa-user-tie"></i><select id="filterStaff" class="jobs-control"><?php foreach($users as $u): ?><option value="<?= (int)$u['id'] ?>" selected><?= jobs_h($u['username']) ?></option><?php endforeach; ?></select></div></div>
                            <div class="jobs-field"><label for="filterPayment">Payment status</label><div class="jobs-input-wrap"><i class="fa-solid fa-money-bill-wave"></i><select id="filterPayment" class="jobs-control"><option value="">All payment statuses</option><option value="paid">Paid</option><option value="partial">Partially paid</option><option value="unpaid">Unpaid</option><option value="pending">Pending</option><option value="overdue">Overdue balance</option></select></div></div>
                            <div class="jobs-field"><label for="serviceFilter">Sort records</label><div class="jobs-input-wrap"><i class="fa-solid fa-arrow-down-wide-short"></i><select id="serviceFilter" class="jobs-control"><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="az">Customer: A → Z</option><option value="za">Customer: Z → A</option><option value="low">Lowest total</option><option value="high">Highest total</option><option value="balance_high">Highest balance</option><option value="balance_low">Lowest balance</option></select></div></div>
                        </div>
                    </section>

                    <div class="jobs-results-header">
                        <div><div class="jobs-results-title">Job Records</div><div class="jobs-results-meta" id="resultsSummary">Showing matching records</div></div>
                        <div class="jobs-results-meta"><i class="fa-solid fa-arrows-up-down"></i> Sort and filter instantly</div>
                    </div>

                    <div id="jobsTableShell" class="jobs-table-shell">
                        <div class="jobs-table-scroll"><table class="jobs-table">
                            <thead><tr><th>Invoice</th><th>Customer</th><th>Location</th><th>Created by</th><th>Total</th><th>Deposited</th><th>Balance</th><th>Due date</th><th>Rendered</th><th>Payment</th><th>Invoice</th></tr></thead>
                            <tbody id="jobsTableBody">
                            <?php foreach($jobs as $job):
                                $jobJson = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                $status = strtolower(trim((string)($job['payment_status'] ?? '')));
                                $paymentClass = 'payment-default';
                                if (strpos($status, 'partial') !== false) $paymentClass = 'payment-partial';
                                elseif (strpos($status, 'unpaid') !== false || strpos($status, 'pending') !== false) $paymentClass = 'payment-unpaid';
                                elseif ($status === 'paid' || $status === 'fully paid') $paymentClass = 'payment-paid';
                                $balance = (float)($job['balance'] ?? 0);
                                $customerName = $job['customer_name'] ?: 'Walk-in Customer';
                                $initial = strtoupper(substr(trim($customerName), 0, 1));
                                $invoice = (string)($job['invoice_number'] ?? '');
                            ?>
                                <tr class="job-row" data-job="<?= jobs_h($jobJson) ?>">
                                    <td><span class="invoice-number"><i class="fa-solid fa-receipt"></i><?= jobs_h($invoice ?: '—') ?></span></td>
                                    <td><div class="customer-cell"><div class="customer-avatar"><?= jobs_h($initial ?: '?') ?></div><span class="customer-name"><?= jobs_h($customerName) ?></span></div></td>
                                    <td><?= jobs_h($job['location_name'] ?: '—') ?></td><td><?= jobs_h($job['user_name'] ?: '—') ?></td>
                                    <td><span class="money"><?= '₦'.number_format((float)($job['final_total'] ?? 0),2) ?></span></td>
                                    <td><span class="money"><?= '₦'.number_format((float)($job['amount_paid'] ?? 0),2) ?></span></td>
                                    <td><span class="money <?= $balance > 0 ? 'balance-positive' : 'balance-clear' ?>"><?= '₦'.number_format($balance,2) ?></span></td>
                                    <td><span class="date-cell"><?= jobs_h($job['due_date'] ?: '—') ?></span></td>
                                    <td><span class="date-cell"><?= jobs_h($job['created_at'] ?? '') ?></span></td>
                                    <td><span class="payment-status <?= $paymentClass ?>"><i class="fa-solid fa-circle"></i><?= jobs_h($job['payment_status'] ?: 'Unknown') ?></span></td>
                                    <td><?php if ($invoice !== ''): ?><a href="../invoices/invoice_<?= rawurlencode($invoice) ?>.pdf" target="_blank" rel="noopener" class="invoice-btn" title="View invoice"><i class="fa-solid fa-file-pdf"></i></a><?php else: ?>—<?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    </div>

                    <div id="jobsCards" class="jobs-mobile-list">
                    <?php foreach($jobs as $job):
                        $jobJson = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        $status = strtolower(trim((string)($job['payment_status'] ?? '')));
                        $paymentClass = 'payment-default';
                        if (strpos($status, 'partial') !== false) $paymentClass = 'payment-partial';
                        elseif (strpos($status, 'unpaid') !== false || strpos($status, 'pending') !== false) $paymentClass = 'payment-unpaid';
                        elseif ($status === 'paid' || $status === 'fully paid') $paymentClass = 'payment-paid';
                        $balance = (float)($job['balance'] ?? 0);
                        $customerName = $job['customer_name'] ?: 'Walk-in Customer';
                        $invoice = (string)($job['invoice_number'] ?? '');
                    ?>
                        <article class="job-card job-mobile-item" data-job="<?= jobs_h($jobJson) ?>">
                            <div class="job-card-top"><div><div class="job-card-invoice"><i class="fa-solid fa-receipt"></i><?= jobs_h($invoice ?: 'No invoice number') ?></div><div class="job-card-customer"><i class="fa-solid fa-user"></i> <?= jobs_h($customerName) ?></div></div><span class="payment-status <?= $paymentClass ?>"><?= jobs_h($job['payment_status'] ?: 'Unknown') ?></span></div>
                            <div class="job-card-grid">
                                <div class="job-card-item"><span class="job-card-label">Total amount</span><span class="job-card-value"><?= '₦'.number_format((float)($job['final_total'] ?? 0),2) ?></span></div>
                                <div class="job-card-item"><span class="job-card-label">Deposited</span><span class="job-card-value"><?= '₦'.number_format((float)($job['amount_paid'] ?? 0),2) ?></span></div>
                                <div class="job-card-item"><span class="job-card-label">Balance</span><span class="job-card-value <?= $balance > 0 ? 'balance-positive' : 'balance-clear' ?>"><?= '₦'.number_format($balance,2) ?></span></div>
                                <div class="job-card-item"><span class="job-card-label">Due date</span><span class="job-card-value"><?= jobs_h($job['due_date'] ?: '—') ?></span></div>
                                <div class="job-card-item"><span class="job-card-label">Location</span><span class="job-card-value"><?= jobs_h($job['location_name'] ?: '—') ?></span></div>
                                <div class="job-card-item"><span class="job-card-label">Created by</span><span class="job-card-value"><?= jobs_h($job['user_name'] ?: '—') ?></span></div>
                                <div class="job-card-item"><span class="job-card-label">Rendered</span><span class="job-card-value"><?= jobs_h($job['created_at'] ?? '') ?></span></div>
                            </div>
                            <?php if ($invoice !== ''): ?><div class="job-card-footer"><a href="../invoices/invoice_<?= rawurlencode($invoice) ?>.pdf" target="_blank" rel="noopener" class="mobile-invoice-btn"><i class="fa-solid fa-file-pdf"></i> View invoice</a></div><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                    </div>

                    <div id="jobsEmpty" class="jobs-empty"><div class="jobs-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div><h3>No jobs found</h3><p>No job records match the current filters. Try widening the date range or clearing one or more filters.</p></div>
                    <div class="jobs-footer-note">Amounts are calculated only from job records belonging to your active user account and active location.</div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = id => document.getElementById(id);
    const searchInput = $('filterSearch'), fromInput = $('filterFrom'), toInput = $('filterTo');
    const customerInput = $('filterCustomer'), locationInput = $('filterLocation');
    const staffInput = $('filterStaff'), paymentInput = $('filterPayment'), sortInput = $('serviceFilter');
    const clearButton = $('clearFilters'), tableBody = $('jobsTableBody'), cardsContainer = $('jobsCards');
    const tableItems = Array.from(tableBody.querySelectorAll('.job-row'));
    const mobileItems = Array.from(cardsContainer.querySelectorAll('.job-mobile-item'));
    const allJobs = tableItems.map(el => getJob(el));
    const money = value => '₦' + (Number.isFinite(Number(value)) ? Number(value) : 0).toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2});
    const num = value => { const n = parseFloat(value); return Number.isFinite(n) ? n : 0; };
    function getJob(element) { try { return JSON.parse(element.dataset.job || '{}'); } catch (e) { return {}; } }
    function datePart(value) { return String(value || '').substring(0, 10); }
    function timestamp(value) { const v = String(value || '').replace(' ', 'T'); const n = Date.parse(v); return Number.isNaN(n) ? 0 : n; }
    function statusOf(job) {
        const status = String(job.payment_status || '').toLowerCase().trim();
        if (status.includes('partial')) return 'partial';
        if (status.includes('unpaid')) return 'unpaid';
        if (status.includes('pending')) return 'pending';
        if (status === 'paid' || status === 'fully paid') return 'paid';
        if (num(job.balance) > 0) return 'unpaid';
        return status || 'unknown';
    }
    function matches(job) {
        const q = searchInput.value.trim().toLowerCase();
        const customerId = customerInput.value;
        const locationId = locationInput.value;
        const staffId = staffInput.value;
        const payment = paymentInput.value;
        const d = datePart(job.created_at);
        const customerName = String(job.customer_name || 'Walk-in Customer').toLowerCase();
        const haystack = [job.invoice_number, customerName, job.location_name, job.user_name, job.payment_status].join(' ').toLowerCase();
        if (q && !haystack.includes(q)) return false;
        if (customerId && String(job.customer_id || '') !== customerId) return false;
        if (locationId && String(job.location_id || '') !== locationId) return false;
        if (staffId && String(job.user_id || '') !== staffId) return false;
        if (fromInput.value && d < fromInput.value) return false;
        if (toInput.value && d > toInput.value) return false;
        if (payment === 'overdue') {
            if (!(num(job.balance) > 0 && job.due_date && String(job.due_date).substring(0,10) < new Date().toISOString().substring(0,10))) return false;
        } else if (payment && statusOf(job) !== payment) return false;
        return true;
    }
    function sort(items) {
        const type = sortInput.value;
        return items.sort((a,b) => {
            const A = getJob(a), B = getJob(b);
            const ca = String(A.customer_name || '').toLowerCase(), cb = String(B.customer_name || '').toLowerCase();
            switch(type) {
                case 'oldest': return timestamp(A.created_at) - timestamp(B.created_at);
                case 'az': return ca.localeCompare(cb);
                case 'za': return cb.localeCompare(ca);
                case 'low': return num(A.final_total) - num(B.final_total);
                case 'high': return num(B.final_total) - num(A.final_total);
                case 'balance_high': return num(B.balance) - num(A.balance);
                case 'balance_low': return num(A.balance) - num(B.balance);
                default: return timestamp(B.created_at) - timestamp(A.created_at);
            }
        });
    }
    function updateFilterCount() {
        const count = [searchInput.value.trim(), fromInput.value, toInput.value, customerInput.value, locationInput.value, staffInput.value, paymentInput.value].filter(Boolean).length;
        $('activeFilterCount').textContent = count;
        $('activeFilterCount').classList.toggle('active', count > 0);
    }
    function renderChart(jobs) {
        const container = $('jobsChart');
        let start = fromInput.value ? new Date(fromInput.value + 'T12:00:00') : new Date();
        let end = toInput.value ? new Date(toInput.value + 'T12:00:00') : new Date();
        if (end < start) { const temp = start; start = end; end = temp; }
        const firstYear = start.getFullYear(), lastYear = end.getFullYear();
        const year = firstYear === lastYear ? firstYear : end.getFullYear();
        const buckets = Array.from({length:12}, (_, i) => ({
            key: year + '-' + String(i+1).padStart(2,'0'),
            label: new Date(year, i, 1, 12).toLocaleDateString('en-NG', {month:'short'}),
            total:0
        }));
        const map = new Map(buckets.map(b => [b.key,b]));
        jobs.forEach(job => {
            const d = datePart(job.created_at);
            const key = d.substring(0,7);
            const b = map.get(key);
            if (d.substring(0,4) === String(year) && b) b.total += num(job.final_total);
        });
        const max = Math.max(1, ...buckets.map(b => b.total));
        container.innerHTML = '';
        buckets.forEach(bucket => {
            const col = document.createElement('div'); col.className = 'chart-day';
            const value = document.createElement('div'); value.className = 'chart-value';
            value.textContent = bucket.total >= 1000000 ? '₦' + (bucket.total/1000000).toFixed(1) + 'm' : bucket.total >= 1000 ? '₦' + (bucket.total/1000).toFixed(0) + 'k' : bucket.total ? '₦' + bucket.total.toFixed(0) : '';
            value.title = money(bucket.total);
            const track = document.createElement('div'); track.className = 'chart-track';
            const bar = document.createElement('div'); bar.className = 'chart-bar';
            bar.style.height = (bucket.total ? Math.max(5, bucket.total/max*100) : 2) + '%';
            bar.title = bucket.key + ': ' + money(bucket.total);
            track.appendChild(bar);
            const label = document.createElement('div'); label.className = 'chart-label'; label.textContent = bucket.label;
            col.append(value, track, label); container.appendChild(col);
        });
        $('chartRange').textContent = String(year);
    }
    function applyFilters() {
        const filtered = allJobs.filter(matches);
        const filteredIds = new Set(filtered.map(j => String(j.id)));
        const filteredTable = sort(tableItems.filter(el => filteredIds.has(String(getJob(el).id))));
        const filteredMobile = sort(mobileItems.filter(el => filteredIds.has(String(getJob(el).id))));
        tableBody.innerHTML = ''; filteredTable.forEach(el => tableBody.appendChild(el));
        cardsContainer.innerHTML = ''; filteredMobile.forEach(el => cardsContainer.appendChild(el));
        tableItems.forEach(el => el.style.display = filteredIds.has(String(getJob(el).id)) ? '' : 'none');
        mobileItems.forEach(el => el.style.display = filteredIds.has(String(getJob(el).id)) ? '' : 'none');
        const count = filtered.length;
        const total = filtered.reduce((s,j) => s + num(j.final_total), 0);
        const collected = filtered.reduce((s,j) => s + num(j.amount_paid), 0);
        const outstanding = filtered.reduce((s,j) => s + Math.max(0,num(j.balance)), 0);
        const overdue = filtered.reduce((s,j) => {
            const due = datePart(j.due_date);
            return s + (num(j.balance) > 0 && due && due < new Date().toISOString().substring(0,10) ? num(j.balance) : 0);
        }, 0);
        const paid = filtered.filter(j => statusOf(j) === 'paid' || (num(j.final_total) > 0 && num(j.balance) <= 0 && num(j.amount_paid) >= num(j.final_total))).length;
        const partial = filtered.filter(j => statusOf(j) === 'partial').length;
        const rate = total > 0 ? Math.max(0,Math.min(100,collected/total*100)) : 0;
        const average = count ? total/count : 0;
        const customerTotals = new Map();
        filtered.forEach(j => { const name = j.customer_name || 'Walk-in Customer'; customerTotals.set(name,(customerTotals.get(name)||0)+num(j.final_total)); });
        const top = [...customerTotals.entries()].sort((a,b)=>b[1]-a[1])[0];
        $('totalJobs').textContent = count.toLocaleString('en-NG');
        $('grandTotal').textContent = money(total);
        $('collectedTotal').textContent = money(collected);
        $('outstandingTotal').textContent = money(outstanding);
        $('collectionNote').textContent = rate.toFixed(1) + '% of job value collected';
        $('collectionRateText').textContent = rate.toFixed(1) + '%';
        $('collectionProgress').style.width = rate + '%';
        $('averageValue').textContent = money(average);
        $('paidJobs').textContent = paid.toLocaleString('en-NG');
        $('partialJobs').textContent = partial.toLocaleString('en-NG');
        $('overdueTotal').textContent = money(overdue);
        $('topCustomer').textContent = top ? top[0] + ' · ' + money(top[1]) : '—';
        $('jobsShare').textContent = count + ' of ' + allJobs.length + ' loaded jobs';
        $('recordChip').textContent = count + ' matching records';
        $('resultsSummary').textContent = 'Showing ' + count + ' of ' + allJobs.length + ' records for your user and active location';
        const from = fromInput.value, to = toInput.value;
        $('periodLabel').textContent = from && to ? (from === to ? from : from + ' to ' + to) : from ? 'From ' + from : to ? 'Up to ' + to : 'All dates';
        $('jobsEmpty').style.display = count ? 'none' : 'block';
        updateFilterCount();
        renderChart(filtered);
    }
    [searchInput,fromInput,toInput,customerInput,locationInput,staffInput,paymentInput,sortInput].forEach(el => {
        el.addEventListener(el === searchInput ? 'input' : 'change', applyFilters);
    });
    clearButton.addEventListener('click', function() {
        searchInput.value = ''; fromInput.value = <?= json_encode($initialFrom) ?>; toInput.value = <?= json_encode($initialTo) ?>;
        customerInput.value = ''; locationInput.value = ''; staffInput.value = '';
        paymentInput.value = ''; sortInput.value = 'newest';
        applyFilters();
    });
    $('printReport').addEventListener('click', () => window.print());
    $('exportCsv').addEventListener('click', function() {
        const visible = allJobs.filter(matches);
        const headers = ['Invoice','Customer','Location','Created By','Total','Amount Paid','Balance','Due Date','Created At','Payment Status'];
        const rows = visible.map(j => [j.invoice_number,j.customer_name || 'Walk-in Customer',j.location_name,j.user_name,j.final_total,j.amount_paid,j.balance,j.due_date,j.created_at,j.payment_status]);
        const csvCell = v => '"' + String(v ?? '').replace(/"/g,'""') + '"';
        const csv = [headers,...rows].map(row => row.map(csvCell).join(',')).join('\r\n');
        const blob = new Blob(['\ufeff' + csv], {type:'text/csv;charset=utf-8;'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'annual-jobs-report-' + new Date().toISOString().slice(0,10) + '.csv';
        document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
    });
    applyFilters();
});
</script>
</body>
</html>
