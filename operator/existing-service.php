<?php
include('../config/db.php');
include('../config/validation.php');
include('../company_data.php');

$company_id  = $_SESSION['company_id'] ?? 0;
$location_id = $_SESSION['location_id'] ?? 0;
$user_email  = $_SESSION['user_email'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE company_id = ? ORDER BY id ASC");
    $stmt->execute([$company_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $catalogue_error = '';
} catch (Exception $e) {
    $jobs = [];
    $catalogue_error = $e->getMessage();
}

function service_catalogue_escape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
function service_catalogue_money($value) {
    return '₦' . number_format((float)($value ?? 0), 2);
}
function service_catalogue_initial($value) {
    $value = trim((string)$value);
    return $value === '' ? 'S' : strtoupper(substr($value, 0, 1));
}

$totalServices = count($jobs);
$totalMaterialCost = $totalSkillCost = $totalServiceCost = 0;
$highestService = $lowestService = null;
foreach ($jobs as $job) {
    $materialCost = (float)($job['materials_cost'] ?? 0);
    $skillCost = (float)($job['external_skill_cost'] ?? 0);
    $serviceCost = (float)($job['total_cost'] ?? 0);
    $totalMaterialCost += $materialCost;
    $totalSkillCost += $skillCost;
    $totalServiceCost += $serviceCost;
    if ($highestService === null || $serviceCost > (float)($highestService['total_cost'] ?? 0)) $highestService = $job;
    if ($lowestService === null || $serviceCost < (float)($lowestService['total_cost'] ?? 0)) $lowestService = $job;
}
$averageServiceCost = $totalServices ? $totalServiceCost / $totalServices : 0;
$averageMaterialCost = $totalServices ? $totalMaterialCost / $totalServices : 0;
$averageSkillCost = $totalServices ? $totalSkillCost / $totalServices : 0;
$materialPercentage = $totalServiceCost > 0 ? round(($totalMaterialCost / $totalServiceCost) * 100) : 0;
$skillPercentage = $totalServiceCost > 0 ? round(($totalSkillCost / $totalServiceCost) * 100) : 0;
$jobsJson = json_encode(array_values($jobs), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);
if ($jobsJson === false) $jobsJson = '[]';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= service_catalogue_escape($company_name ?? 'Company'); ?> | Service Catalogue</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<style>
.service-catalogue-page{min-height:100vh;padding:22px;background:radial-gradient(circle at 8% 5%,rgba(16,185,129,.12),transparent 28%),radial-gradient(circle at 92% 12%,rgba(59,130,246,.10),transparent 28%),linear-gradient(135deg,#f7fafc,#eef5f3 50%,#f8fafc)}
.dark .service-catalogue-page{background:radial-gradient(circle at 8% 5%,rgba(16,185,129,.12),transparent 28%),radial-gradient(circle at 92% 12%,rgba(59,130,246,.10),transparent 28%),linear-gradient(135deg,#071311,#0b1715 50%,#081110)}
.sc-shell{max-width:1600px;margin:0 auto}
.sc-hero,.sc-kpi,.sc-insight,.sc-panel{border:1px solid rgba(255,255,255,.75);background:rgba(255,255,255,.72);backdrop-filter:blur(18px);box-shadow:0 15px 35px rgba(15,23,42,.06)}
.dark .sc-hero,.dark .sc-kpi,.dark .sc-insight,.dark .sc-panel{background:rgba(15,32,29,.76);border-color:rgba(255,255,255,.07);box-shadow:0 15px 35px rgba(0,0,0,.2)}
.sc-hero{position:relative;overflow:hidden;padding:30px;margin-bottom:20px;border-radius:28px;background:linear-gradient(135deg,rgba(255,255,255,.92),rgba(255,255,255,.58))}
.dark .sc-hero{background:linear-gradient(135deg,rgba(15,32,29,.9),rgba(10,23,21,.72))}
.sc-hero-content{display:flex;align-items:center;justify-content:space-between;gap:25px}
.sc-eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:9px;color:#059669;font-size:11px;font-weight:850;letter-spacing:.15em;text-transform:uppercase}
.sc-title{margin:0;color:#0f172a;font-size:clamp(26px,3vw,40px);line-height:1.05;font-weight:900;letter-spacing:-.04em}
.dark .sc-title{color:#f8fafc}
.sc-subtitle{max-width:720px;margin-top:10px;color:#64748b;font-size:13px;line-height:1.7}
.dark .sc-subtitle{color:#94a3b8}
.sc-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:20px}
.sc-kpi{position:relative;overflow:hidden;min-height:145px;padding:19px;border-radius:21px}
.sc-kpi-top{display:flex;align-items:center;justify-content:space-between}
.sc-kpi-icon,.sc-panel-icon{width:43px;height:43px;display:grid;place-items:center;border-radius:14px;color:#059669;background:rgba(16,185,129,.11);font-size:16px}
.sc-kpi-label{margin-top:14px;color:#64748b;font-size:11px;font-weight:750;text-transform:uppercase;letter-spacing:.05em}
.dark .sc-kpi-label{color:#94a3b8}
.sc-kpi-value{margin-top:4px;color:#0f172a;font-size:clamp(18px,1.8vw,25px);line-height:1.2;font-weight:900;letter-spacing:-.035em;overflow-wrap:anywhere}
.dark .sc-kpi-value{color:#f8fafc}
.sc-kpi-meta{margin-top:7px;color:#94a3b8;font-size:10px}
.sc-intelligence{display:grid;grid-template-columns:1.35fr .85fr .85fr;gap:15px;margin-bottom:20px}
.sc-insight{padding:18px;border-radius:20px}
.sc-insight-head{display:flex;align-items:center;gap:8px;color:#334155;font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.06em}
.dark .sc-insight-head{color:#cbd5e1}
.sc-insight-head i{color:#059669}
.sc-insight-main{margin-top:12px;color:#0f172a;font-size:18px;font-weight:900;overflow-wrap:anywhere}
.dark .sc-insight-main{color:#f8fafc}
.sc-insight-small{margin-top:5px;color:#64748b;font-size:10px;line-height:1.6}
.dark .sc-insight-small{color:#94a3b8}
.sc-progress{height:7px;margin-top:12px;overflow:hidden;border-radius:999px;background:#e2e8f0}
.dark .sc-progress{background:#1e293b}
.sc-progress span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#10b981,#059669);transition:width .2s}
.sc-panel{overflow:hidden;border-radius:25px}
.sc-panel-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:20px 22px;border-bottom:1px solid rgba(148,163,184,.15)}
.sc-panel-title-wrap{display:flex;align-items:center;gap:12px}
.sc-panel-title{color:#0f172a;font-size:15px;font-weight:900}
.dark .sc-panel-title{color:#f8fafc}
.sc-panel-description{margin-top:3px;color:#94a3b8;font-size:10px}
.sc-controls{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.sc-search{position:relative;width:min(100%,350px)}
.sc-search i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px}
.sc-search input{width:100%;height:42px;padding:0 14px 0 38px;border:1px solid #dbe4e8;border-radius:12px;outline:none;background:rgba(248,250,252,.78);color:#0f172a;font-size:11px;transition:.2s}
.dark .sc-search input{border-color:#263a37;background:rgba(15,32,29,.75);color:#f8fafc}
.sc-search input:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.10)}
.sc-action-btn{height:40px;padding:0 13px;border:1px solid #dbe4e8;border-radius:11px;background:#fff;color:#334155;font-size:10px;font-weight:800;cursor:pointer;white-space:nowrap}
.dark .sc-action-btn{background:#12221f;border-color:#263a37;color:#cbd5e1}
.sc-action-btn:hover{border-color:#10b981;color:#047857}
.sc-table-wrapper{overflow-x:auto}
.sc-table{width:100%;min-width:900px;border-collapse:collapse}
.sc-table th{padding:14px 18px;text-align:left;color:#64748b;background:rgba(248,250,252,.7);font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.09em;white-space:nowrap}
.dark .sc-table th{color:#94a3b8;background:rgba(15,32,29,.7)}
.sc-table td{padding:15px 18px;border-top:1px solid rgba(148,163,184,.13);color:#334155;font-size:11px;vertical-align:middle}
.dark .sc-table td{color:#cbd5e1}
.sc-table tbody tr:hover{background:rgba(16,185,129,.045)}
.sc-number{width:32px;height:32px;display:grid;place-items:center;border-radius:10px;background:#f1f5f9;color:#64748b;font-size:10px;font-weight:850}
.dark .sc-number{background:#172522;color:#94a3b8}
.sc-service-cell,.sc-mobile-service{display:flex;align-items:center;gap:11px;min-width:0}
.sc-avatar{width:39px;height:39px;flex-shrink:0;display:grid;place-items:center;border-radius:12px;color:#047857;background:linear-gradient(135deg,rgba(16,185,129,.18),rgba(59,130,246,.12));font-size:13px;font-weight:900}
.dark .sc-avatar{color:#6ee7b7}
.sc-service-name{color:#0f172a;font-weight:850;overflow-wrap:anywhere}
.dark .sc-service-name{color:#f1f5f9}
.sc-service-code{margin-top:3px;color:#94a3b8;font-size:9px;font-family:monospace}
.sc-cost{color:#334155;font-weight:750;white-space:nowrap}
.dark .sc-cost{color:#cbd5e1}
.sc-total,.sc-mobile-total{color:#047857;font-size:12px;font-weight:900;white-space:nowrap}
.dark .sc-total,.dark .sc-mobile-total{color:#6ee7b7}
.sc-cost-label{display:block;margin-top:3px;color:#94a3b8;font-size:8px}
.sc-mobile-list{display:none;padding:14px}
.sc-mobile-card{padding:17px;margin-bottom:12px;border-radius:18px;border:1px solid rgba(148,163,184,.15);background:rgba(255,255,255,.55)}
.dark .sc-mobile-card{background:rgba(15,32,29,.65)}
.sc-mobile-top{display:flex;align-items:center;justify-content:space-between;gap:12px}
.sc-mobile-name{color:#0f172a;font-size:13px;font-weight:900;overflow-wrap:anywhere}
.dark .sc-mobile-name{color:#f8fafc}
.sc-mobile-number{margin-top:3px;color:#94a3b8;font-size:9px;font-family:monospace}
.sc-mobile-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin-top:15px}
.sc-mobile-detail{padding:10px;border-radius:12px;background:rgba(241,245,249,.72);min-width:0}
.dark .sc-mobile-detail{background:rgba(30,41,59,.3)}
.sc-mobile-label{color:#94a3b8;font-size:8px;font-weight:850;text-transform:uppercase;letter-spacing:.06em}
.sc-mobile-value{margin-top:4px;color:#334155;font-size:10px;font-weight:750;overflow-wrap:anywhere}
.dark .sc-mobile-value{color:#cbd5e1}
.sc-empty{padding:55px 25px;text-align:center}
.sc-empty-icon{width:65px;height:65px;display:grid;place-items:center;margin:0 auto 16px;border-radius:20px;color:#059669;background:rgba(16,185,129,.09);font-size:23px}
.sc-empty-title{color:#334155;font-size:15px;font-weight:900}
.dark .sc-empty-title{color:#e2e8f0}
.sc-empty-text{max-width:450px;margin:7px auto 0;color:#94a3b8;font-size:11px;line-height:1.7}
.sc-results{padding:12px 22px;color:#94a3b8;font-size:10px;border-top:1px solid rgba(148,163,184,.12)}
@media(max-width:1100px){.sc-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sc-intelligence{grid-template-columns:1fr 1fr}.sc-insight:first-child{grid-column:1/-1}}
@media(max-width:800px){.service-catalogue-page{padding:13px}.sc-hero{padding:22px;border-radius:21px}.sc-hero-content{align-items:flex-start;flex-direction:column}.sc-panel-header{align-items:flex-start;flex-direction:column}.sc-controls,.sc-search{width:100%}.sc-search{flex:1}.sc-table-wrapper{display:none}.sc-mobile-list{display:block}}
@media(max-width:560px){.sc-kpi-grid{gap:10px}.sc-kpi{min-height:125px;padding:15px}.sc-kpi-value{font-size:19px}.sc-intelligence{grid-template-columns:1fr}.sc-insight:first-child{grid-column:auto}.sc-title{font-size:27px}.sc-panel-header{padding:17px}.sc-controls{display:grid;grid-template-columns:1fr 1fr}.sc-search{grid-column:1/-1}.sc-mobile-details{gap:7px}}
@media(max-width:400px){.sc-kpi-grid{grid-template-columns:1fr}.sc-mobile-details{grid-template-columns:1fr}}
</style>
</head>
<body x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode === true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="service-catalogue-page"><div class="sc-shell">
                <section class="sc-hero"><div class="sc-hero-content"><div>
                    <div class="sc-eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> Service Intelligence Registry</div>
                    <h1 class="sc-title">Service Catalogue</h1>
                    <p class="sc-subtitle">Central intelligence for your registered service offerings. Monitor service classifications, material requirements, external skill costs and total service charges from one operational catalogue.</p>
                </div></div></section>

                <section class="sc-kpi-grid">
                    <div class="sc-kpi"><div class="sc-kpi-top"><div class="sc-kpi-icon"><i class="fa-solid fa-list-check"></i></div><i class="fa-solid fa-database text-gray-300 dark:text-gray-700"></i></div><div class="sc-kpi-label">Registered Services</div><div class="sc-kpi-value" id="kpiServices"><?= number_format($totalServices); ?></div><div class="sc-kpi-meta">Matching catalogue entries</div></div>
                    <div class="sc-kpi"><div class="sc-kpi-top"><div class="sc-kpi-icon"><i class="fa-solid fa-boxes-stacked"></i></div><i class="fa-solid fa-cubes text-gray-300 dark:text-gray-700"></i></div><div class="sc-kpi-label">Material Cost</div><div class="sc-kpi-value" id="kpiMaterials"><?= service_catalogue_money($totalMaterialCost); ?></div><div class="sc-kpi-meta" id="kpiMaterialPercent"><?= $materialPercentage; ?>% of catalogue cost</div></div>
                    <div class="sc-kpi"><div class="sc-kpi-top"><div class="sc-kpi-icon"><i class="fa-solid fa-user-gear"></i></div><i class="fa-solid fa-person-chalkboard text-gray-300 dark:text-gray-700"></i></div><div class="sc-kpi-label">External Skill Cost</div><div class="sc-kpi-value" id="kpiSkills"><?= service_catalogue_money($totalSkillCost); ?></div><div class="sc-kpi-meta" id="kpiSkillPercent"><?= $skillPercentage; ?>% of catalogue cost</div></div>
                    <div class="sc-kpi"><div class="sc-kpi-top"><div class="sc-kpi-icon"><i class="fa-solid fa-naira-sign"></i></div><i class="fa-solid fa-chart-line text-gray-300 dark:text-gray-700"></i></div><div class="sc-kpi-label">Catalogue Value</div><div class="sc-kpi-value" id="kpiTotal"><?= service_catalogue_money($totalServiceCost); ?></div><div class="sc-kpi-meta">Combined service charges</div></div>
                </section>

                <section class="sc-intelligence">
                    <div class="sc-insight"><div class="sc-insight-head"><i class="fa-solid fa-brain"></i> Pricing Intelligence</div><div class="sc-insight-main" id="averageService">Average Service: <?= service_catalogue_money($averageServiceCost); ?></div><div class="sc-insight-small" id="averageCosts">Average material cost: <?= service_catalogue_money($averageMaterialCost); ?> &nbsp; • &nbsp; Average external skill: <?= service_catalogue_money($averageSkillCost); ?></div><div class="sc-progress"><span id="costProgress" style="width:<?= min(100,max(0,$materialPercentage+$skillPercentage)); ?>%"></span></div></div>
                    <div class="sc-insight"><div class="sc-insight-head"><i class="fa-solid fa-arrow-trend-up"></i> Highest Service Value</div><div class="sc-insight-main" id="highestName"><?= $highestService ? service_catalogue_escape($highestService['service_name'] ?? '') : 'No data'; ?></div><div class="sc-insight-small" id="highestCost"><?= $highestService ? service_catalogue_money($highestService['total_cost'] ?? 0) : 'No service registered'; ?></div></div>
                    <div class="sc-insight"><div class="sc-insight-head"><i class="fa-solid fa-arrow-trend-down"></i> Lowest Service Value</div><div class="sc-insight-main" id="lowestName"><?= $lowestService ? service_catalogue_escape($lowestService['service_name'] ?? '') : 'No data'; ?></div><div class="sc-insight-small" id="lowestCost"><?= $lowestService ? service_catalogue_money($lowestService['total_cost'] ?? 0) : 'No service registered'; ?></div></div>
                </section>

                <section class="sc-panel">
                    <div class="sc-panel-header">
                        <div class="sc-panel-title-wrap"><div class="sc-panel-icon"><i class="fa-solid fa-server"></i></div><div><div class="sc-panel-title">Registered Service Catalogue</div><div class="sc-panel-description" id="serviceCountText"><?= number_format($totalServices); ?> service <?= $totalServices === 1 ? 'record' : 'records'; ?> indexed for this company</div></div></div>
                        <div class="sc-controls">
                            <div class="sc-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="serviceSearch" placeholder="Search service, number or cost..." autocomplete="off"></div>
                            <button type="button" class="sc-action-btn" id="resetServiceSearch"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                            <button type="button" class="sc-action-btn" id="exportServices"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                        </div>
                    </div>

                    <?php if (!empty($catalogue_error)): ?>
                        <div class="sc-empty"><div class="sc-empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="sc-empty-title">Catalogue Could Not Be Loaded</div><div class="sc-empty-text">The service records could not be retrieved. Please refresh the page or contact your system administrator.</div></div>
                    <?php endif; ?>

                    <div class="sc-table-wrapper" id="desktopServiceWrapper">
                        <table class="sc-table" id="serviceTable"><thead><tr><th>#</th><th>Service</th><th>Service Number</th><th>Material Cost</th><th>External Skill</th><th>Service Charge</th></tr></thead><tbody id="serviceTableBody">
                        <?php foreach ($jobs as $index => $job): ?>
                            <tr><td><div class="sc-number"><?= $index + 1; ?></div></td><td><div class="sc-service-cell"><div class="sc-avatar"><?= service_catalogue_escape(service_catalogue_initial($job['service_name'] ?? 'S')); ?></div><div><div class="sc-service-name"><?= service_catalogue_escape($job['service_name'] ?? ''); ?></div><div class="sc-service-code">SERVICE CATALOGUE</div></div></div></td><td><span class="sc-service-code"><i class="fa-solid fa-hashtag mr-1"></i><?= service_catalogue_escape($job['service_number'] ?? ''); ?></span></td><td><div class="sc-cost"><?= service_catalogue_money($job['materials_cost'] ?? 0); ?></div><span class="sc-cost-label">Materials</span></td><td><div class="sc-cost"><?= service_catalogue_money($job['external_skill_cost'] ?? 0); ?></div><span class="sc-cost-label">External skill</span></td><td><div class="sc-total"><?= service_catalogue_money($job['total_cost'] ?? 0); ?></div><span class="sc-cost-label">Total service charge</span></td></tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div>
                    <div class="sc-mobile-list" id="mobileServiceList">
                        <?php foreach ($jobs as $index => $job): ?>
                            <div class="sc-mobile-card"><div class="sc-mobile-top"><div class="sc-mobile-service"><div class="sc-avatar"><?= service_catalogue_escape(service_catalogue_initial($job['service_name'] ?? 'S')); ?></div><div><div class="sc-mobile-name"><?= service_catalogue_escape($job['service_name'] ?? ''); ?></div><div class="sc-mobile-number">#<?= service_catalogue_escape($job['service_number'] ?? ''); ?></div></div></div><div class="sc-mobile-total"><?= service_catalogue_money($job['total_cost'] ?? 0); ?></div></div><div class="sc-mobile-details"><div class="sc-mobile-detail"><div class="sc-mobile-label">Material Cost</div><div class="sc-mobile-value"><?= service_catalogue_money($job['materials_cost'] ?? 0); ?></div></div><div class="sc-mobile-detail"><div class="sc-mobile-label">External Skill</div><div class="sc-mobile-value"><?= service_catalogue_money($job['external_skill_cost'] ?? 0); ?></div></div><div class="sc-mobile-detail"><div class="sc-mobile-label">Service Number</div><div class="sc-mobile-value"><?= service_catalogue_escape($job['service_number'] ?? ''); ?></div></div><div class="sc-mobile-detail"><div class="sc-mobile-label">Registry</div><div class="sc-mobile-value">#<?= str_pad((string)($job['id'] ?? $index + 1),4,'0',STR_PAD_LEFT); ?></div></div></div></div>
                        <?php endforeach; ?>
                    </div>
                    <div id="serviceSearchEmpty" class="sc-empty" style="display:none"><div class="sc-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div><div class="sc-empty-title">No Matching Services</div><div class="sc-empty-text">No service matched your current search. Try searching by service name, service number or cost.</div></div>
                    <?php if (empty($jobs) && empty($catalogue_error)): ?><div id="serviceDatabaseEmpty" class="sc-empty"><div class="sc-empty-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div><div class="sc-empty-title">Service Catalogue Is Empty</div><div class="sc-empty-text">There are currently no registered services for this company. Once services are created, their pricing, material and external skill intelligence will appear here.</div></div><?php endif; ?>
                    <div class="sc-results" id="serviceResults" <?= empty($jobs) ? 'style="display:none"' : ''; ?>>Showing <?= number_format($totalServices); ?> of <?= number_format($totalServices); ?> services</div>
                </section>
            </div></div>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script>
(function(){
    'use strict';
    const services = <?= $jobsJson ?>;
    const searchInput = document.getElementById('serviceSearch');
    const resetButton = document.getElementById('resetServiceSearch');
    const exportButton = document.getElementById('exportServices');
    const tableBody = document.getElementById('serviceTableBody');
    const mobileList = document.getElementById('mobileServiceList');
    const tableWrapper = document.getElementById('desktopServiceWrapper');
    const emptyState = document.getElementById('serviceSearchEmpty');
    const databaseEmpty = document.getElementById('serviceDatabaseEmpty');
    const resultsText = document.getElementById('serviceResults');
    const countText = document.getElementById('serviceCountText');

    const money = value => '₦' + Number(value || 0).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});
    const number = value => Number(value || 0).toLocaleString('en-NG');
    const esc = value => String(value == null ? '' : value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const initial = value => { const s=String(value || '').trim(); return esc(s ? s.charAt(0).toUpperCase() : 'S'); };
    const get = (job,key) => job && job[key] != null ? job[key] : '';
    const searchText = job => [
        get(job,'id'),get(job,'service_name'),get(job,'service_number'),
        get(job,'materials_cost'),get(job,'external_skill_cost'),get(job,'total_cost')
    ].join(' ').toLocaleLowerCase();

    function renderDesktop(list){
        if(!tableBody) return;
        tableBody.innerHTML = list.map((job,index) => `<tr>
            <td><div class="sc-number">${index+1}</div></td>
            <td><div class="sc-service-cell"><div class="sc-avatar">${initial(get(job,'service_name'))}</div><div><div class="sc-service-name">${esc(get(job,'service_name'))}</div><div class="sc-service-code">SERVICE CATALOGUE</div></div></div></td>
            <td><span class="sc-service-code"><i class="fa-solid fa-hashtag mr-1"></i>${esc(get(job,'service_number'))}</span></td>
            <td><div class="sc-cost">${money(get(job,'materials_cost'))}</div><span class="sc-cost-label">Materials</span></td>
            <td><div class="sc-cost">${money(get(job,'external_skill_cost'))}</div><span class="sc-cost-label">External skill</span></td>
            <td><div class="sc-total">${money(get(job,'total_cost'))}</div><span class="sc-cost-label">Total service charge</span></td>
        </tr>`).join('');
        if(!list.length) tableBody.innerHTML='<tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8">No matching services</td></tr>';
    }
    function renderMobile(list){
        if(!mobileList) return;
        mobileList.innerHTML = list.map((job,index) => `<div class="sc-mobile-card"><div class="sc-mobile-top"><div class="sc-mobile-service"><div class="sc-avatar">${initial(get(job,'service_name'))}</div><div><div class="sc-mobile-name">${esc(get(job,'service_name'))}</div><div class="sc-mobile-number">#${esc(get(job,'service_number'))}</div></div></div><div class="sc-mobile-total">${money(get(job,'total_cost'))}</div></div><div class="sc-mobile-details"><div class="sc-mobile-detail"><div class="sc-mobile-label">Material Cost</div><div class="sc-mobile-value">${money(get(job,'materials_cost'))}</div></div><div class="sc-mobile-detail"><div class="sc-mobile-label">External Skill</div><div class="sc-mobile-value">${money(get(job,'external_skill_cost'))}</div></div><div class="sc-mobile-detail"><div class="sc-mobile-label">Service Number</div><div class="sc-mobile-value">${esc(get(job,'service_number'))}</div></div><div class="sc-mobile-detail"><div class="sc-mobile-label">Registry</div><div class="sc-mobile-value">#${esc(String(get(job,'id') || index+1).padStart(4,'0'))}</div></div></div></div>`).join('');
    }
    function updateKpis(list){
        let materials=0, skills=0, total=0, highest=null, lowest=null;
        list.forEach(job=>{
            const m=Number(get(job,'materials_cost'))||0, s=Number(get(job,'external_skill_cost'))||0, t=Number(get(job,'total_cost'))||0;
            materials+=m; skills+=s; total+=t;
            if(highest===null || t>(Number(get(highest,'total_cost'))||0)) highest=job;
            if(lowest===null || t<(Number(get(lowest,'total_cost'))||0)) lowest=job;
        });
        const count=list.length, avgTotal=count?total/count:0, avgMaterials=count?materials/count:0, avgSkills=count?skills/count:0;
        const materialPct=total>0?Math.round(materials/total*100):0, skillPct=total>0?Math.round(skills/total*100):0;
        document.getElementById('kpiServices').textContent=number(count);
        document.getElementById('kpiMaterials').textContent=money(materials);
        document.getElementById('kpiSkills').textContent=money(skills);
        document.getElementById('kpiTotal').textContent=money(total);
        document.getElementById('kpiMaterialPercent').textContent=materialPct+'% of catalogue cost';
        document.getElementById('kpiSkillPercent').textContent=skillPct+'% of catalogue cost';
        document.getElementById('averageService').textContent='Average Service: '+money(avgTotal);
        document.getElementById('averageCosts').textContent='Average material cost: '+money(avgMaterials)+' • Average external skill: '+money(avgSkills);
        document.getElementById('costProgress').style.width=Math.min(100,Math.max(0,materialPct+skillPct))+'%';
        document.getElementById('highestName').textContent=highest?String(get(highest,'service_name')||'Unnamed service'):'No data';
        document.getElementById('highestCost').textContent=highest?money(get(highest,'total_cost')):'No service registered';
        document.getElementById('lowestName').textContent=lowest?String(get(lowest,'service_name')||'Unnamed service'):'No data';
        document.getElementById('lowestCost').textContent=lowest?money(get(lowest,'total_cost')):'No service registered';
    }
    function filterServices(){
        const query=(searchInput.value||'').trim().toLocaleLowerCase();
        const filtered=services.filter(job=>!query || searchText(job).includes(query));
        renderDesktop(filtered);
        renderMobile(filtered);
        updateKpis(filtered);
        if(countText) countText.textContent=number(filtered.length)+' service '+(filtered.length===1?'record':'records')+' shown of '+number(services.length)+' total';
        if(resultsText){resultsText.style.display=services.length?'block':'none';resultsText.textContent='Showing '+number(filtered.length)+' of '+number(services.length)+' services';}
        if(emptyState) emptyState.style.display=(services.length>0 && filtered.length===0)?'block':'none';
        if(databaseEmpty) databaseEmpty.style.display=services.length===0?'block':'none';
        if(tableWrapper) tableWrapper.style.display=(window.innerWidth<=800)?'none':'block';
        if(mobileList) mobileList.style.display=(window.innerWidth<=800)?'block':'none';
    }
    function exportCsv(){
        const query=(searchInput.value||'').trim().toLocaleLowerCase();
        const list=services.filter(job=>!query || searchText(job).includes(query));
        const rows=[['ID','Service Name','Service Number','Material Cost','External Skill Cost','Total Service Cost']];
        list.forEach(job=>rows.push([get(job,'id'),get(job,'service_name'),get(job,'service_number'),get(job,'materials_cost'),get(job,'external_skill_cost'),get(job,'total_cost')]));
        const csv=rows.map(row=>row.map(value=>'"'+String(value==null?'':value).replace(/"/g,'""')+'"').join(',')).join('\r\n');
        const blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});
        const url=URL.createObjectURL(blob), link=document.createElement('a');
        link.href=url; link.download='service-catalogue.csv'; document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url);
    }
    if(searchInput) searchInput.addEventListener('input',filterServices);
    if(resetButton) resetButton.addEventListener('click',()=>{if(searchInput)searchInput.value='';filterServices();searchInput&&searchInput.focus();});
    if(exportButton) exportButton.addEventListener('click',exportCsv);
    window.addEventListener('resize',()=>{if(tableWrapper&&mobileList){tableWrapper.style.display=window.innerWidth<=800?'none':'block';mobileList.style.display=window.innerWidth<=800?'block':'none';}});
    filterServices();
})();
</script>
</body>
</html>
