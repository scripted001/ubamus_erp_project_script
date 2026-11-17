
<?php
include('../config/db.php');
include('../config/validation.php');
include('../company_data.php');

$company_id  = (int)($_SESSION['company_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);
$user_email  = $_SESSION['user_email'] ?? '';

$jobs = [];
$error_message = null;

if ($company_id <= 0) {
    $error_message = 'Invalid company session.';
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT
                s.*,
                (
                    SELECT si.image_path
                    FROM service_image si
                    WHERE si.service_id = s.id
                      AND si.company_id = s.company_id
                    ORDER BY si.id ASC
                    LIMIT 1
                ) AS service_image
            FROM services s
            WHERE s.company_id = ?
            ORDER BY s.id ASC
        ");
        $stmt->execute([$company_id]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $jobs = [];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="content-type" content="text/html;charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= htmlspecialchars($company_name) ?> | Services</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">

<style>
*{box-sizing:border-box}
html,body{width:100%;max-width:100%;margin:0;padding:0;overflow-x:hidden}
body{min-width:0}

.services-page{width:100%;padding:20px}

.services-hero{
position:relative;width:100%;padding:24px;margin-bottom:20px;
overflow:hidden;border-radius:20px;
background:linear-gradient(135deg,#6839eb 0%,#4c1d95 48%,#111827 100%);
color:#fff;box-shadow:0 14px 35px rgba(76,29,149,.22)
}
.services-hero:before,.services-hero:after{
content:"";position:absolute;border-radius:50%;pointer-events:none
}
.services-hero:before{
width:180px;height:180px;right:-70px;top:-80px;
background:rgba(255,255,255,.08)
}
.services-hero:after{
width:120px;height:120px;right:80px;bottom:-90px;
background:rgba(255,255,255,.05)
}
.services-hero-content{
position:relative;z-index:2;display:flex;align-items:center;
justify-content:space-between;gap:20px
}
.services-hero-left{min-width:0}
.services-hero-icon{
width:50px;height:50px;margin-bottom:12px;display:flex;
align-items:center;justify-content:center;border-radius:14px;
background:rgba(255,255,255,.14);
border:1px solid rgba(255,255,255,.18);
font-size:21px;backdrop-filter:blur(10px)
}
.services-hero h1{
margin:0;font-size:27px;font-weight:750;line-height:1.2;
letter-spacing:-.4px
}
.services-hero p{
margin:7px 0 0;color:rgba(255,255,255,.76);font-size:14px
}
.service-count-box{
flex:0 0 auto;min-width:125px;padding:16px;text-align:center;
border-radius:15px;background:rgba(255,255,255,.10);
border:1px solid rgba(255,255,255,.15);
backdrop-filter:blur(10px)
}
.service-count-box i{
display:block;margin-bottom:7px;font-size:17px;
color:rgba(255,255,255,.75)
}
.service-count-number{
display:block;font-size:25px;line-height:1;font-weight:800
}
.service-count-label{
display:block;margin-top:5px;font-size:11px;
color:rgba(255,255,255,.70);text-transform:uppercase;
letter-spacing:.7px
}

.services-container{
width:100%;padding:22px;border-radius:18px;background:#fff;
border:1px solid #e5e7eb;box-shadow:0 8px 28px rgba(0,0,0,.05)
}
.dark .services-container{
background:#111827;border-color:#374151
}

.services-section-header{
display:flex;align-items:center;justify-content:space-between;
gap:15px;margin-bottom:18px;flex-wrap:wrap
}
.services-section-title{
display:flex;align-items:center;gap:10px;min-width:0
}
.services-section-title-icon{
width:38px;height:38px;flex:0 0 38px;display:flex;
align-items:center;justify-content:center;border-radius:10px;
background:rgba(104,57,235,.10);color:#6839eb
}
.services-section-title h2{
margin:0;font-size:18px;font-weight:700;color:#1f2937
}
.dark .services-section-title h2{color:#fff}
.services-section-title p{
margin:2px 0 0;font-size:12px;color:#9ca3af
}

.services-controls{
display:flex;align-items:center;gap:9px;width:100%;
margin-bottom:18px
}
.services-search{position:relative;flex:1 1 auto;min-width:0}
.services-search-icon{
position:absolute;left:13px;top:50%;transform:translateY(-50%);
color:#9ca3af;font-size:13px;pointer-events:none
}
.services-search-input{
width:100%;height:42px;padding:0 38px;
border:1px solid #e5e7eb;border-radius:10px;outline:none;
background:#f8fafc;color:#1f2937;font-size:12px;
transition:all .18s ease
}
.services-search-input:focus{
border-color:#6839eb;background:#fff;
box-shadow:0 0 0 3px rgba(104,57,235,.08)
}
.services-search-clear{
position:absolute;right:10px;top:50%;transform:translateY(-50%);
width:25px;height:25px;display:none;align-items:center;
justify-content:center;border:0;border-radius:7px;
background:transparent;color:#9ca3af;cursor:pointer;font-size:11px
}
.services-search-clear:hover{background:#e5e7eb;color:#374151}

.services-filter{position:relative;flex:0 0 190px}
.services-filter-icon{
position:absolute;left:13px;top:50%;transform:translateY(-50%);
color:#6839eb;font-size:12px;pointer-events:none;z-index:2
}
.services-filter-select{
width:100%;height:42px;padding:0 32px 0 36px;
border:1px solid #e5e7eb;border-radius:10px;outline:none;
background:#f8fafc;color:#374151;font-size:12px;
font-weight:600;cursor:pointer;appearance:none
}
.services-filter-select:focus{
border-color:#6839eb;background:#fff;
box-shadow:0 0 0 3px rgba(104,57,235,.08)
}
.services-filter-arrow{
position:absolute;right:13px;top:50%;transform:translateY(-50%);
color:#9ca3af;font-size:10px;pointer-events:none
}
.dark .services-search-input,.dark .services-filter-select{
background:#1f2937;border-color:#374151;color:#fff
}
.dark .services-search-input:focus,.dark .services-filter-select:focus{
background:#111827
}

.services-result-info{
display:flex;align-items:center;justify-content:space-between;
gap:10px;margin:-5px 0 13px;min-height:20px
}
.services-result-text{color:#9ca3af;font-size:11px}
.services-result-text strong{color:#6839eb;font-weight:750}
.services-reset{
display:none;align-items:center;gap:5px;border:0;
background:transparent;color:#6839eb;font-size:11px;
font-weight:700;cursor:pointer
}
.services-reset:hover{text-decoration:underline}

.desktop-services{
width:100%;overflow-x:auto;border:1px solid #e5e7eb;
border-radius:13px
}
.dark .desktop-services{border-color:#374151}
.services-table{
width:100%;min-width:850px;border-collapse:collapse
}
.services-table th{
padding:14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;
color:#6b7280;font-size:11px;font-weight:750;text-align:left;
text-transform:uppercase;letter-spacing:.5px;white-space:nowrap
}
.dark .services-table th{
background:#1f2937;border-color:#374151;color:#9ca3af
}
.services-table th i{margin-right:5px;color:#6839eb}
.services-table td{
padding:14px;border-bottom:1px solid #edf0f3;
color:#374151;font-size:13px;vertical-align:middle
}
.dark .services-table td{border-color:#374151;color:#d1d5db}
.services-table tr:last-child td{border-bottom:0}
.services-table tbody tr:hover{background:#faf9ff}
.dark .services-table tbody tr:hover{background:#1f2937}

.desktop-service-name{
display:flex;align-items:center;gap:9px;
font-weight:650;color:#111827
}
.dark .desktop-service-name{color:#fff}
.desktop-service-icon{
width:32px;height:32px;flex:0 0 32px;
display:flex;align-items:center;justify-content:center;
border-radius:9px;background:rgba(104,57,235,.10);
color:#6839eb
}
.service-image-thumb{
display:block;width:42px;height:42px;flex:0 0 42px;
object-fit:cover;border-radius:10px;
border:1px solid #e5e7eb;background:#f3f4f6
}
.desktop-service-image{
width:38px;height:38px;flex-basis:38px;border-radius:9px
}
.dark .service-image-thumb{border-color:#374151}
.service-code{
display:inline-flex;align-items:center;gap:5px;padding:6px 9px;
border-radius:7px;background:#f3f4f6;color:#4b5563;
font-family:monospace;font-size:11px;font-weight:600;
white-space:nowrap
}
.dark .service-code{background:#1f2937;color:#d1d5db}
.table-money{font-weight:650;white-space:nowrap}
.table-total{color:#6839eb;font-weight:800;white-space:nowrap}

.mobile-services{display:none}
.mobile-service-card{
position:relative;width:100%;margin-bottom:13px;padding:16px;
border-radius:15px;background:#fff;border:1px solid #e5e7eb;
box-shadow:0 5px 18px rgba(0,0,0,.04);overflow:hidden
}
.mobile-service-card:last-child{margin-bottom:0}
.dark .mobile-service-card{
background:#111827;border-color:#374151
}
.mobile-service-card:before{
content:"";position:absolute;top:0;left:0;width:4px;
height:100%;background:#6839eb
}
.mobile-card-top{
display:flex;align-items:flex-start;justify-content:space-between;
gap:12px
}
.mobile-service-identity{
display:flex;align-items:center;gap:11px;min-width:0
}
.mobile-service-icon{
width:43px;height:43px;flex:0 0 43px;
display:flex;align-items:center;justify-content:center;
border-radius:12px;
background:linear-gradient(135deg,rgba(104,57,235,.14),rgba(104,57,235,.05));
color:#6839eb;font-size:17px
}
.mobile-service-name{min-width:0}
.mobile-service-name h3{
margin:0;overflow:hidden;color:#111827;font-size:15px;
font-weight:750;line-height:1.35;text-overflow:ellipsis;
white-space:nowrap
}
.dark .mobile-service-name h3{color:#fff}
.mobile-service-name p{
margin:3px 0 0;color:#9ca3af;font-size:11px
}
.mobile-index{
display:flex;align-items:center;justify-content:center;
width:28px;height:28px;flex:0 0 28px;border-radius:8px;
background:#f3f4f6;color:#6b7280;font-size:11px;font-weight:700
}
.dark .mobile-index{background:#1f2937;color:#9ca3af}

.mobile-code{
display:flex;align-items:center;gap:7px;margin-top:14px;
padding:9px 10px;border-radius:9px;background:#f8fafc;
color:#64748b;font-family:monospace;font-size:11px;overflow:hidden
}
.dark .mobile-code{background:#1f2937;color:#cbd5e1}
.mobile-code i{color:#6839eb}
.mobile-code span{
overflow:hidden;text-overflow:ellipsis;white-space:nowrap
}

.mobile-cost-grid{
display:grid;grid-template-columns:repeat(3,minmax(0,1fr));
gap:7px;margin-top:12px
}
.mobile-cost{
min-width:0;padding:10px 7px;border-radius:9px;
background:#f8fafc
}
.dark .mobile-cost{background:#1f2937}
.mobile-cost-label{
display:flex;align-items:center;gap:4px;margin-bottom:5px;
color:#9ca3af;font-size:9px;font-weight:700;
text-transform:uppercase;letter-spacing:.2px
}
.mobile-cost-label i{color:#6839eb}
.mobile-cost-value{
display:block;overflow:hidden;color:#374151;font-size:12px;
font-weight:750;text-overflow:ellipsis;white-space:nowrap
}
.dark .mobile-cost-value{color:#fff}
.mobile-cost.total{background:rgba(104,57,235,.08)}
.mobile-cost.total .mobile-cost-value{color:#6839eb}

.mobile-card-footer{
display:flex;align-items:center;justify-content:flex-start;
gap:10px;margin-top:13px;padding-top:12px;
border-top:1px solid #edf0f3
}
.dark .mobile-card-footer{border-color:#374151}
.mobile-status{
display:inline-flex;align-items:center;gap:6px;
color:#6b7280;font-size:10px;font-weight:650
}
.mobile-status-dot{
width:7px;height:7px;border-radius:50%;background:#22c55e;
box-shadow:0 0 0 3px rgba(34,197,94,.12)
}

.empty-services{
padding:55px 20px;text-align:center
}
.empty-services-icon,.filtered-empty-icon{
width:65px;height:65px;margin:0 auto 15px;
display:flex;align-items:center;justify-content:center;
border-radius:18px;background:rgba(104,57,235,.08);
color:#6839eb;font-size:25px
}
.empty-services h3,.filtered-empty h3{
margin:0;color:#374151;font-size:16px;font-weight:700
}
.dark .empty-services h3,.dark .filtered-empty h3{color:#fff}
.empty-services p,.filtered-empty p{
max-width:400px;margin:6px auto 0;color:#9ca3af;
font-size:13px;line-height:1.5
}
.filtered-empty{
display:none;padding:45px 20px;text-align:center
}
.filtered-empty-icon{
width:58px;height:58px;margin-bottom:13px;font-size:22px
}
.filtered-empty h3{font-size:15px}
.filtered-empty p{font-size:12px}
.services-error{
padding:14px 16px;margin-bottom:15px;border-radius:10px;
background:#fef2f2;border:1px solid #fecaca;
color:#b91c1c;font-size:13px
}

@media(max-width:900px){
.services-page{padding:15px}
.services-hero{padding:20px}
.services-hero h1{font-size:23px}
.services-container{padding:18px}
}
@media(max-width:700px){
.services-page{padding:10px}
.services-hero{padding:17px;margin-bottom:12px;border-radius:15px}
.services-hero-content{align-items:flex-start}
.services-hero-icon{
width:42px;height:42px;margin-bottom:9px;
border-radius:11px;font-size:17px
}
.services-hero h1{font-size:20px}
.services-hero p{
max-width:220px;font-size:11px;line-height:1.5
}
.service-count-box{
min-width:82px;padding:11px 8px;border-radius:11px
}
.service-count-box i{font-size:14px}
.service-count-number{font-size:20px}
.service-count-label{font-size:8px}
.services-container{padding:13px;border-radius:13px}
.services-section-header{
align-items:stretch;margin-bottom:13px;flex-direction:column
}
.services-section-title-icon{
width:34px;height:34px;flex-basis:34px
}
.services-section-title h2{font-size:15px}
.services-section-title p{font-size:10px}
.services-controls{
flex-direction:column;align-items:stretch;gap:8px;margin-bottom:13px
}
.services-filter{flex:0 0 auto;width:100%}
.services-search-input,.services-filter-select{height:40px}
.services-result-info{margin-top:-1px;margin-bottom:11px}
.desktop-services{display:none}
.mobile-services{display:block}
}
@media(max-width:430px){
.services-hero-content{gap:10px}
.services-hero h1{font-size:18px}
.services-hero p{max-width:180px}
.service-count-box{min-width:70px}
.service-count-number{font-size:18px}
.mobile-service-card{padding:14px}
.mobile-service-icon{
width:39px;height:39px;flex-basis:39px
}
.mobile-service-name h3{font-size:14px}
.mobile-cost{padding:9px 5px}
.mobile-cost-label{font-size:8px}
.mobile-cost-value{font-size:10px}
}
@media(max-width:350px){
.services-page{padding:7px}
.services-hero{padding:14px}
.service-count-box{min-width:62px}
.service-count-label{letter-spacing:0}
.mobile-cost-grid{gap:4px}
.mobile-cost-value{font-size:9px}
}
</style>
</head>

<body
x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}"
x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
:class="{'dark bg-gray-900': darkMode === true}">

<div class="flex h-screen overflow-hidden">
<?php include('sidebar.php'); ?>

<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
<main>
<?php include('header.php'); ?>

<div class="services-page">

<?php if ($error_message !== null): ?>
<div class="services-error">
<i class="fa-solid fa-triangle-exclamation"></i>
Unable to load registered services.
</div>
<?php endif; ?>

<section class="services-hero">
<div class="services-hero-content">
<div class="services-hero-left">
<div class="services-hero-icon">
<i class="fa-solid fa-layer-group"></i>
</div>
<h1>Registered Services</h1>
<p>View and monitor your company's registered service catalogue.</p>
</div>

<div class="service-count-box">
<i class="fa-solid fa-cubes"></i>
<span class="service-count-number" id="serviceCount"><?= count($jobs) ?></span>
<span class="service-count-label">Services</span>
</div>
</div>
</section>

<section class="services-container">

<div class="services-section-header">
<div class="services-section-title">
<div class="services-section-title-icon">
<i class="fa-solid fa-list-check"></i>
</div>
<div>
<h2>Service Catalogue</h2>
<p>Registered services for your company</p>
</div>
</div>
</div>

<?php if (!empty($jobs)): ?>

<div class="services-controls">

<div class="services-search">
<i class="fa-solid fa-magnifying-glass services-search-icon"></i>
<input
type="search"
id="serviceSearch"
class="services-search-input"
placeholder="Search service by name..."
autocomplete="off"
>
<button
type="button"
id="serviceSearchClear"
class="services-search-clear"
title="Clear search"
>
<i class="fa-solid fa-xmark"></i>
</button>
</div>

<div class="services-filter">
<i class="fa-solid fa-filter services-filter-icon"></i>
<select id="serviceFilter" class="services-filter-select">
<option value="all">All Services</option>
<option value="az">Name: A → Z</option>
<option value="za">Name: Z → A</option>
<option value="low">Lowest Cost</option>
<option value="high">Highest Cost</option>
</select>
<i class="fa-solid fa-chevron-down services-filter-arrow"></i>
</div>

</div>

<div class="services-result-info">
<span class="services-result-text" id="serviceResultText">
Showing <strong><?= count($jobs) ?></strong> services
</span>
<button type="button" id="serviceReset" class="services-reset">
<i class="fa-solid fa-rotate-left"></i> Reset
</button>
</div>

<div class="desktop-services">
<table class="services-table">
<thead>
<tr>
<th><i class="fa-solid fa-hashtag"></i>#</th>
<th><i class="fa-solid fa-screwdriver-wrench"></i>Service</th>
<th><i class="fa-solid fa-barcode"></i>Service Number</th>
<th><i class="fa-solid fa-boxes-stacked"></i>Material Cost</th>
<th><i class="fa-solid fa-user-gear"></i>Extra Skill</th>
<th><i class="fa-solid fa-money-bill-wave"></i>Service Charge</th>
</tr>
</thead>

<tbody id="desktopServicesBody">
<?php foreach ($jobs as $index => $job): ?>
<tr
class="service-row"
data-service-name="<?= htmlspecialchars(strtolower($job['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
data-service-cost="<?= (float)($job['total_cost'] ?? 0) ?>"
>
<td class="service-index-cell"><?= $index + 1 ?></td>

<td>
<div class="desktop-service-name">
<?php if (!empty($job['service_image'])): ?>
<img
class="service-image-thumb desktop-service-image"
src="<?= htmlspecialchars($job['service_image'], ENT_QUOTES, 'UTF-8') ?>"
alt="<?= htmlspecialchars($job['service_name'] ?? 'Service image', ENT_QUOTES, 'UTF-8') ?>"
loading="lazy"
>
<?php else: ?>
<span class="desktop-service-icon">
<i class="fa-solid fa-wrench"></i>
</span>
<?php endif; ?>

<span><?= htmlspecialchars($job['service_name'] ?? '') ?></span>
</div>
</td>

<td>
<span class="service-code">
<i class="fa-solid fa-hashtag"></i>
<?= htmlspecialchars($job['service_number'] ?? '') ?>
</span>
</td>

<td>
<span class="table-money">
<?= number_format((float)($job['materials_cost'] ?? 0), 2) ?>
</span>
</td>

<td>
<span class="table-money">
<?= number_format((float)($job['external_skill_cost'] ?? 0), 2) ?>
</span>
</td>

<td>
<span class="table-total">
<?= number_format((float)($job['total_cost'] ?? 0), 2) ?>
</span>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="mobile-services" id="mobileServicesContainer">

<?php foreach ($jobs as $index => $job): ?>

<article
class="mobile-service-card service-card"
data-service-name="<?= htmlspecialchars(strtolower($job['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
data-service-cost="<?= (float)($job['total_cost'] ?? 0) ?>"
>

<div class="mobile-card-top">

<div class="mobile-service-identity">

<?php if (!empty($job['service_image'])): ?>
<img
class="service-image-thumb"
src="<?= htmlspecialchars($job['service_image'], ENT_QUOTES, 'UTF-8') ?>"
alt="<?= htmlspecialchars($job['service_name'] ?? 'Service image', ENT_QUOTES, 'UTF-8') ?>"
loading="lazy"
>
<?php else: ?>
<div class="mobile-service-icon">
<i class="fa-solid fa-screwdriver-wrench"></i>
</div>
<?php endif; ?>

<div class="mobile-service-name">
<h3 title="<?= htmlspecialchars($job['service_name'] ?? '') ?>">
<?= htmlspecialchars($job['service_name'] ?? '') ?>
</h3>
<p>
<i class="fa-solid fa-circle-check"></i>
Registered Service
</p>
</div>

</div>

<span class="mobile-index">#<?= $index + 1 ?></span>

</div>

<div class="mobile-code">
<i class="fa-solid fa-barcode"></i>
<span><?= htmlspecialchars($job['service_number'] ?? '') ?></span>
</div>

<div class="mobile-cost-grid">

<div class="mobile-cost">
<span class="mobile-cost-label">
<i class="fa-solid fa-boxes-stacked"></i>
Material
</span>
<span class="mobile-cost-value">
<?= number_format((float)($job['materials_cost'] ?? 0), 2) ?>
</span>
</div>

<div class="mobile-cost">
<span class="mobile-cost-label">
<i class="fa-solid fa-user-gear"></i>
Extra Skill
</span>
<span class="mobile-cost-value">
<?= number_format((float)($job['external_skill_cost'] ?? 0), 2) ?>
</span>
</div>

<div class="mobile-cost total">
<span class="mobile-cost-label">
<i class="fa-solid fa-coins"></i>
Total
</span>
<span class="mobile-cost-value">
<?= number_format((float)($job['total_cost'] ?? 0), 2) ?>
</span>
</div>

</div>

<div class="mobile-card-footer">
<span class="mobile-status">
<span class="mobile-status-dot"></span>
Active Service
</span>
</div>

</article>

<?php endforeach; ?>

</div>

<div class="filtered-empty" id="filteredEmpty">
<div class="filtered-empty-icon">
<i class="fa-solid fa-magnifying-glass"></i>
</div>
<h3>No Matching Services</h3>
<p>Try another service name or change the filter.</p>
</div>

<?php else: ?>

<div class="empty-services">
<div class="empty-services-icon">
<i class="fa-solid fa-folder-open"></i>
</div>
<h3>No Registered Services</h3>
<p>
Your service catalogue is currently empty.
Registered services added by your company's authorised administrators will appear here.
</p>
</div>

<?php endif; ?>

</section>
</div>
</main>

<?php include('footer.php'); ?>
</div>
</div>

<script defer src="bundle.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('serviceSearch');
    const searchClear = document.getElementById('serviceSearchClear');
    const filterSelect = document.getElementById('serviceFilter');
    const resultText = document.getElementById('serviceResultText');
    const serviceCount = document.getElementById('serviceCount');
    const resetButton = document.getElementById('serviceReset');
    const filteredEmpty = document.getElementById('filteredEmpty');
    const desktopBody = document.getElementById('desktopServicesBody');
    const mobileContainer = document.getElementById('mobileServicesContainer');

    if (!searchInput || !filterSelect) return;

    const desktopRows = desktopBody
        ? Array.from(desktopBody.querySelectorAll('.service-row'))
        : [];

    const mobileCards = mobileContainer
        ? Array.from(mobileContainer.querySelectorAll('.service-card'))
        : [];

    const totalServices = desktopRows.length;

    const services = desktopRows.map(function (row, index) {
        return {
            desktop: row,
            mobile: mobileCards[index] || null,
            name: (row.dataset.serviceName || '').toLowerCase(),
            cost: parseFloat(row.dataset.serviceCost || 0),
            originalIndex: index
        };
    });

    function applyServiceFilter() {

        const searchTerm = searchInput.value.trim().toLowerCase();
        const filterValue = filterSelect.value;

        let filtered = services.filter(function (item) {
            return item.name.includes(searchTerm);
        });

        switch (filterValue) {
            case 'az':
                filtered.sort((a, b) =>
                    a.name.localeCompare(b.name, undefined, {sensitivity: 'base'})
                );
                break;

            case 'za':
                filtered.sort((a, b) =>
                    b.name.localeCompare(a.name, undefined, {sensitivity: 'base'})
                );
                break;

            case 'low':
                filtered.sort((a, b) => a.cost - b.cost);
                break;

            case 'high':
                filtered.sort((a, b) => b.cost - a.cost);
                break;

            default:
                filtered.sort((a, b) => a.originalIndex - b.originalIndex);
        }

        services.forEach(function (item) {
            if (item.desktop) item.desktop.style.display = 'none';
            if (item.mobile) item.mobile.style.display = 'none';
        });

        if (desktopBody) {
            filtered.forEach(function (item, index) {
                desktopBody.appendChild(item.desktop);
                item.desktop.style.display = '';

                const cell = item.desktop.querySelector('.service-index-cell');
                if (cell) cell.textContent = index + 1;
            });
        }

        if (mobileContainer) {
            filtered.forEach(function (item, index) {
                if (!item.mobile) return;

                mobileContainer.appendChild(item.mobile);
                item.mobile.style.display = '';

                const number = item.mobile.querySelector('.mobile-index');
                if (number) number.textContent = '#' + (index + 1);
            });
        }

        if (serviceCount) {
            serviceCount.textContent = filtered.length;
        }

        if (resultText) {
            resultText.innerHTML =
                (searchTerm !== '' || filterValue !== 'all')
                ? 'Showing <strong>' + filtered.length +
                  '</strong> of <strong>' + totalServices + '</strong> services'
                : 'Showing <strong>' + totalServices + '</strong> services';
        }

        if (searchClear) {
            searchClear.style.display = searchTerm !== '' ? 'flex' : 'none';
        }

        if (resetButton) {
            resetButton.style.display =
                (searchTerm !== '' || filterValue !== 'all')
                ? 'inline-flex'
                : 'none';
        }

        const noResults = filtered.length === 0;

        const tableWrapper = desktopBody
            ? desktopBody.closest('.desktop-services')
            : null;

        if (tableWrapper) {
            tableWrapper.style.display =
                noResults && window.innerWidth > 700 ? 'none' : '';
        }

        if (mobileContainer) {
            mobileContainer.style.display =
                noResults
                ? (window.innerWidth <= 700 ? 'none' : '')
                : (window.innerWidth <= 700 ? 'block' : '');
        }

        if (filteredEmpty) {
            filteredEmpty.style.display = noResults ? 'block' : 'none';
        }
    }

    searchInput.addEventListener('input', applyServiceFilter);
    filterSelect.addEventListener('change', applyServiceFilter);

    if (searchClear) {
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            searchInput.focus();
            applyServiceFilter();
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            searchInput.value = '';
            filterSelect.value = 'all';
            applyServiceFilter();
        });
    }

    window.addEventListener('resize', applyServiceFilter);

    applyServiceFilter();
});
</script>

</body>
</html>
