<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$companyid = (int)($_SESSION['company_id'] ?? 0);
if ($companyid <= 0) {
    echo "<script>alert('Invalid company session.'); window.location.href='index.php';</script>";
    exit;
}

/* Fetch categories belonging to the active company only. */
$stmt = $pdo->prepare("SELECT id, company_id, name, description, stock_type, unit_type, unit_id FROM product_categories WHERE company_id = ? ORDER BY name ASC");
$stmt->execute([$companyid]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCategories = count($categories);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($company_name ?? 'Company', ENT_QUOTES, 'UTF-8'); ?> | Categories</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">
    <style>
        :root{--ci-navy:#062b57;--ci-blue:#0b5cab;--ci-blue-light:#eaf4ff;--ci-border:rgba(15,55,95,.11);--ci-text:#172033;--ci-muted:#718096;--ci-bg:#f5f8fc;--ci-white:rgba(255,255,255,.82);--ci-shadow:0 18px 55px rgba(20,51,84,.08)}
        *{box-sizing:border-box}body{background:radial-gradient(circle at 10% 0%,rgba(24,119,242,.07),transparent 30%),radial-gradient(circle at 90% 10%,rgba(0,183,255,.06),transparent 28%),#f5f8fc}
        .ci-page{width:100%;max-width:1600px;margin:0 auto}
        .ci-hero{position:relative;overflow:hidden;margin-bottom:22px;padding:25px;border:1px solid rgba(255,255,255,.8);border-radius:24px;background:linear-gradient(135deg,rgba(255,255,255,.91),rgba(242,248,255,.72));backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);box-shadow:var(--ci-shadow)}
        .ci-hero:before{content:"";position:absolute;width:220px;height:220px;right:-70px;top:-110px;border-radius:50%;background:rgba(11,92,171,.09);filter:blur(5px)}
        .ci-hero-content{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:20px}.ci-title-wrap{display:flex;align-items:center;gap:15px;min-width:0}
        .ci-icon{width:54px;height:54px;flex:0 0 54px;display:flex;align-items:center;justify-content:center;border-radius:17px;background:linear-gradient(135deg,#07366a,#1683df);color:#fff;font-size:21px;box-shadow:0 12px 28px rgba(7,54,106,.22)}
        .ci-title{margin:0;color:var(--ci-text);font-size:clamp(21px,2.3vw,30px);font-weight:800;letter-spacing:-.5px}.ci-subtitle{margin:4px 0 0;color:var(--ci-muted);font-size:13px;line-height:1.5}
        .ci-counter{display:flex;align-items:center;gap:10px;padding:11px 15px;border:1px solid var(--ci-border);border-radius:14px;background:rgba(255,255,255,.72);white-space:nowrap}.ci-counter i{color:var(--ci-blue)}.ci-counter strong{color:var(--ci-text);font-size:14px}
        .ci-breadcrumb{display:flex;align-items:center;gap:8px;margin-bottom:18px;padding-left:4px;color:var(--ci-muted);font-size:13px}.ci-breadcrumb a{color:var(--ci-blue);text-decoration:none;font-weight:600}.ci-breadcrumb i{font-size:9px;opacity:.55}
        .ci-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}.ci-stat{position:relative;overflow:hidden;min-height:105px;padding:18px;border:1px solid var(--ci-border);border-radius:19px;background:rgba(255,255,255,.74);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);box-shadow:0 10px 35px rgba(20,51,84,.055)}.ci-stat:after{content:"";position:absolute;right:-28px;bottom:-35px;width:100px;height:100px;border-radius:50%;background:rgba(11,92,171,.05)}
        .ci-stat-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.ci-stat-label{color:var(--ci-muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px}.ci-stat-icon{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:var(--ci-blue-light);color:var(--ci-blue)}.ci-stat-value{margin-top:12px;color:var(--ci-text);font-size:25px;font-weight:800}
        .ci-panel{overflow:hidden;border:1px solid var(--ci-border);border-radius:24px;background:rgba(255,255,255,.78);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);box-shadow:var(--ci-shadow)}
        .ci-panel-head{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;padding:20px 22px;border-bottom:1px solid var(--ci-border);background:rgba(255,255,255,.5)}.ci-panel-title{display:flex;align-items:center;gap:11px;min-width:0}.ci-panel-title-icon{width:38px;height:38px;flex:0 0 38px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:#eef6ff;color:var(--ci-blue)}.ci-panel-title h3{margin:0;color:var(--ci-text);font-size:16px;font-weight:800}.ci-panel-title span{display:block;margin-top:2px;color:var(--ci-muted);font-size:11px}
        .ci-panel-heading-actions{display:flex;align-items:center;justify-content:flex-end;flex:0 0 auto}.ci-new-category-btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:43px;padding:0 17px;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:linear-gradient(135deg,#07366a,#0b6dbd);color:#fff;font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap;box-shadow:0 8px 20px rgba(7,54,106,.18);transition:transform .2s ease,box-shadow .2s ease}.ci-new-category-btn:hover{color:#fff;transform:translateY(-2px);box-shadow:0 11px 25px rgba(7,54,106,.25)}.ci-new-category-btn:focus-visible{outline:3px solid rgba(11,92,171,.25);outline-offset:3px}
        .ci-search{display:flex;align-items:center;gap:10px;padding:17px 20px;border-bottom:1px solid var(--ci-border)}.ci-search-select,.ci-search-input{height:43px;border:1px solid #dbe4ee;border-radius:12px;outline:none;background:rgba(255,255,255,.8);color:var(--ci-text);font-size:13px;transition:.2s ease}.ci-search-select{width:150px;padding:0 12px}.ci-search-input{flex:1;min-width:0;padding:0 14px}.ci-search-select:focus,.ci-search-input:focus{border-color:#70aee7;box-shadow:0 0 0 4px rgba(11,92,171,.08)}.ci-search-btn{height:43px;min-width:105px;padding:0 18px;border:0;border-radius:12px;background:linear-gradient(135deg,#07366a,#0b6dbd);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 8px 20px rgba(7,54,106,.18);transition:.2s ease}.ci-search-btn:hover{transform:translateY(-1px);box-shadow:0 11px 25px rgba(7,54,106,.24)}
        .ci-table-wrap{width:100%;overflow-x:auto}.ci-table{width:100%;min-width:850px;border-collapse:collapse}.ci-table th{padding:14px 18px;border-bottom:1px solid var(--ci-border);background:rgba(247,250,253,.8);color:#66768a;font-size:10px;font-weight:800;text-align:left;text-transform:uppercase;letter-spacing:.65px;white-space:nowrap}.ci-table td{padding:16px 18px;border-bottom:1px solid rgba(15,55,95,.07);color:#374151;font-size:13px;vertical-align:middle}.ci-table tbody tr{transition:.2s ease}.ci-table tbody tr:hover{background:rgba(238,246,255,.48)}
        .ci-category-name{display:flex;align-items:center;gap:11px;color:var(--ci-text);font-weight:750}.ci-category-avatar{width:36px;height:36px;flex:0 0 36px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:linear-gradient(135deg,#eaf4ff,#dceeff);color:var(--ci-blue);font-size:13px}.ci-id{color:#6b7280;font-family:monospace;font-size:12px}.ci-description{max-width:270px;color:#697789;line-height:1.45}.ci-pill{display:inline-flex;align-items:center;padding:6px 9px;border-radius:999px;background:#edf6ff;color:#075ca8;font-size:10px;font-weight:750;white-space:nowrap}.ci-actions{display:flex;align-items:center;gap:7px}.ci-action{width:35px;height:35px;display:flex;align-items:center;justify-content:center;border:1px solid #dfe7ef;border-radius:10px;background:rgba(255,255,255,.8);cursor:pointer;transition:.2s ease}.ci-action-view{color:#1266ad}.ci-action-delete{color:#d44747}.ci-action:hover{transform:translateY(-2px);box-shadow:0 7px 17px rgba(20,51,84,.1)}
        .ci-mobile{display:none;padding:14px}.ci-card{position:relative;margin-bottom:12px;padding:17px;border:1px solid rgba(15,55,95,.09);border-radius:18px;background:rgba(255,255,255,.82);box-shadow:0 8px 25px rgba(20,51,84,.055)}.ci-card:last-child{margin-bottom:0}.ci-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:14px}.ci-card-title{display:flex;align-items:center;gap:10px;min-width:0}.ci-card-title-icon{width:39px;height:39px;flex:0 0 39px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:#edf6ff;color:var(--ci-blue)}.ci-card-title strong{display:block;overflow:hidden;color:var(--ci-text);font-size:14px;font-weight:800;text-overflow:ellipsis;white-space:nowrap}.ci-card-title small{display:block;margin-top:3px;color:#8995a5;font-family:monospace;font-size:10px}.ci-card-row{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;padding:9px 0;border-top:1px solid rgba(15,55,95,.065)}.ci-card-label{flex:0 0 92px;color:#8a96a6;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.45px}.ci-card-value{flex:1;color:#344154;font-size:12px;line-height:1.5;text-align:right;word-break:break-word}.ci-card-actions{display:flex;gap:8px;margin-top:13px;padding-top:13px;border-top:1px solid rgba(15,55,95,.07)}.ci-mobile-action{flex:1;height:40px;display:flex;align-items:center;justify-content:center;gap:7px;border:1px solid #dce5ee;border-radius:11px;background:#fff;font-size:12px;font-weight:700;cursor:pointer}.ci-mobile-action.view{color:#1266ad}.ci-mobile-action.delete{color:#d44747}
        .ci-empty{padding:55px 20px;text-align:center}.ci-empty-icon{width:60px;height:60px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;border-radius:18px;background:#edf5fd;color:var(--ci-blue);font-size:22px}.ci-empty h4{margin:0;color:var(--ci-text);font-size:16px;font-weight:800}.ci-empty p{margin:6px auto 0;max-width:390px;color:var(--ci-muted);font-size:12px;line-height:1.6}
        @media(max-width:900px){.ci-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.ci-stat:last-child{grid-column:1/-1}}
        @media(max-width:767px){.ci-hero{padding:18px;border-radius:19px}.ci-hero-content{align-items:flex-start}.ci-title-wrap{align-items:flex-start}.ci-icon{width:45px;height:45px;flex-basis:45px;border-radius:13px;font-size:17px}.ci-title{font-size:20px}.ci-subtitle{font-size:11px}.ci-counter{display:none}.ci-breadcrumb{margin-bottom:13px}.ci-stats{grid-template-columns:1fr 1fr;gap:10px}.ci-stat{min-height:92px;padding:14px;border-radius:15px}.ci-stat:last-child{grid-column:auto}.ci-stat-label{font-size:9px}.ci-stat-value{font-size:20px;margin-top:9px}.ci-stat-icon{width:29px;height:29px;border-radius:9px;font-size:11px}.ci-panel{border-radius:19px}.ci-panel-head{align-items:stretch;flex-direction:column;gap:14px;padding:16px}.ci-panel-heading-actions,.ci-new-category-btn{width:100%}.ci-search{flex-wrap:wrap;padding:13px}.ci-search-select{width:100%}.ci-search-input{width:calc(100% - 115px);flex:1}.ci-search-btn{min-width:105px}.ci-table-wrap{display:none}.ci-mobile{display:block}}
        @media(max-width:420px){.ci-stats{grid-template-columns:1fr}.ci-stat:last-child{grid-column:auto}.ci-search-input{width:100%;flex-basis:100%}.ci-search-btn{width:100%}.ci-card{padding:14px}}
        @media(max-width:360px){.ci-new-category-btn{padding:0 12px;font-size:12px}}
    </style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode === true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="ci-page">
                    <section class="ci-hero"><div class="ci-hero-content"><div class="ci-title-wrap"><div class="ci-icon"><i class="fa-solid fa-layer-group"></i></div><div><h1 class="ci-title">Product Categories</h1><p class="ci-subtitle">Organize your products, units and inventory classification.</p></div></div><div class="ci-counter"><i class="fa-solid fa-database"></i><strong><?= number_format($totalCategories) ?> Categories</strong></div></div></section>
                    <div class="ci-breadcrumb"><a href="index.php">Home</a><i class="fa-solid fa-chevron-right"></i><span>Categories</span></div>
                    <div class="ci-stats">
                        <div class="ci-stat"><div class="ci-stat-top"><span class="ci-stat-label">Total Categories</span><div class="ci-stat-icon"><i class="fa-solid fa-layer-group"></i></div></div><div class="ci-stat-value" id="kpiTotalCategories" data-kpi="total"><?= number_format($totalCategories) ?></div></div>
                        <div class="ci-stat"><div class="ci-stat-top"><span class="ci-stat-label">Classification</span><div class="ci-stat-icon"><i class="fa-solid fa-tags"></i></div></div><div class="ci-stat-value" id="kpiClassification">Product</div></div>
                        <div class="ci-stat"><div class="ci-stat-top"><span class="ci-stat-label">System Status</span><div class="ci-stat-icon"><i class="fa-solid fa-signal"></i></div></div><div class="ci-stat-value" id="kpiSystemStatus">Active</div></div>
                    </div>
                    <section class="ci-panel">
                        <div class="ci-panel-head">
                            <div class="ci-panel-title"><div class="ci-panel-title-icon"><i class="fa-solid fa-diagram-project"></i></div><div><h3>Category Directory</h3><span>Manage your product classification structure</span></div></div>
                            <div class="ci-panel-heading-actions"><a href="new_category.php" class="ci-new-category-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>New Category</span></a></div>
                        </div>
                        <div class="ci-search">
                            <select id="search_by" class="ci-search-select"><option value="name">Search by Title</option><option value="id">Search by Tracking ID</option></select>
                            <input type="text" id="search_value" class="ci-search-input" placeholder="Search categories..." autocomplete="off">
                            <button type="button" id="search_btn" class="ci-search-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                        </div>
                        <div class="ci-table-wrap" id="categoryTable">
                            <?php if (!empty($categories)): ?>
                            <table class="ci-table"><thead><tr><th>Category</th><th>Tracking ID</th><th>Description</th><th>Stock Type</th><th>Unit Type</th><th>Actions</th></tr></thead><tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr><td><div class="ci-category-name"><div class="ci-category-avatar"><i class="fa-solid fa-layer-group"></i></div><span><?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></div></td>
                                <td><span class="ci-id">#<?= htmlspecialchars((string)($category['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><div class="ci-description"><?= !empty($category['description']) ? htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8') : 'No description provided.' ?></div></td>
                                <td><span class="ci-pill"><?= htmlspecialchars($category['stock_type'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?></span></td><td><span class="ci-pill"><?= htmlspecialchars($category['unit_type'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><div class="ci-actions"><form method="POST" action="p_cat_pdt.php"><input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$category['id'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" name="submit" class="ci-action ci-action-view" title="View Products"><i class="fa-solid fa-eye"></i></button></form><form method="POST" action="delete_category.php" onsubmit="return confirm('Are you sure you want to delete this category?');"><input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$category['id'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" name="submit" class="ci-action ci-action-delete" title="Delete Category"><i class="fa-solid fa-trash"></i></button></form></div></td></tr>
                            <?php endforeach; ?>
                            </tbody></table>
                            <?php else: ?><div class="ci-empty"><div class="ci-empty-icon"><i class="fa-solid fa-layer-group"></i></div><h4>No Categories Found</h4><p>You haven't created any product categories yet. Create your first category to begin organizing your inventory.</p></div><?php endif; ?>
                        </div>
                        <div class="ci-mobile" id="categoryCards">
                            <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <div class="ci-card"><div class="ci-card-head"><div class="ci-card-title"><div class="ci-card-title-icon"><i class="fa-solid fa-layer-group"></i></div><div><strong><?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small>#<?= htmlspecialchars((string)($category['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></div></div></div>
                                <div class="ci-card-row"><span class="ci-card-label">Description</span><span class="ci-card-value"><?= !empty($category['description']) ? htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8') : 'No description provided.' ?></span></div>
                                <div class="ci-card-row"><span class="ci-card-label">Stock Type</span><span class="ci-card-value"><span class="ci-pill"><?= htmlspecialchars($category['stock_type'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?></span></span></div>
                                <div class="ci-card-row"><span class="ci-card-label">Unit Type</span><span class="ci-card-value"><span class="ci-pill"><?= htmlspecialchars($category['unit_type'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?></span></span></div>
                                <div class="ci-card-actions"><form method="POST" action="p_cat_pdt.php" style="flex:1"><input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$category['id'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" name="submit" class="ci-mobile-action view" style="width:100%"><i class="fa-solid fa-eye"></i> View Products</button></form><form method="POST" action="delete_category.php" onsubmit="return confirm('Are you sure you want to delete this category?');" style="flex:1"><input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$category['id'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" name="submit" class="ci-mobile-action delete" style="width:100%"><i class="fa-solid fa-trash"></i> Delete</button></form></div></div>
                            <?php endforeach; ?>
                            <?php else: ?><div class="ci-empty"><div class="ci-empty-icon"><i class="fa-solid fa-layer-group"></i></div><h4>No Categories Found</h4><p>No product categories have been created yet.</p></div><?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>
<script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
<script defer src="bundle.js"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfExtPri":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}' crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const searchBy = document.getElementById('search_by');
    const searchInput = document.getElementById('search_value');
    const searchButton = document.getElementById('search_btn');
    const tableContainer = document.getElementById('categoryTable');
    const mobileContainer = document.getElementById('categoryCards');
    const totalKPI = document.getElementById('kpiTotalCategories');
    const classificationKPI = document.getElementById('kpiClassification');
    const statusKPI = document.getElementById('kpiSystemStatus');
    const heroCounter = document.querySelector('.ci-counter strong');
    if (!searchBy || !searchInput || !searchButton || !tableContainer || !mobileContainer) return;

    const categories = <?= json_encode(array_values($categories), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const originalTableHTML = tableContainer.innerHTML;
    const originalMobileHTML = mobileContainer.innerHTML;
    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
        });
    }
    function displayValue(value, fallback) {
        const text = String(value ?? '').trim();
        return text ? escapeHTML(text) : fallback;
    }
    function emptyState(message) {
        return `<div class="ci-empty"><div class="ci-empty-icon"><i class="fa-solid fa-layer-group"></i></div><h4>No Categories Found</h4><p>${escapeHTML(message)}</p></div>`;
    }
    function renderDesktop(data) {
        if (!data.length) { tableContainer.innerHTML = emptyState('No categories match your search.'); return; }
        const rows = data.map(category => {
            const id = escapeHTML(category.id);
            const name = displayValue(category.name, 'Unnamed category');
            const description = displayValue(category.description, 'No description provided.');
            const stockType = displayValue(category.stock_type, 'Not specified');
            const unitType = displayValue(category.unit_type, 'Not specified');
            return `<tr><td><div class="ci-category-name"><div class="ci-category-avatar"><i class="fa-solid fa-layer-group"></i></div><span>${name}</span></div></td><td><span class="ci-id">#${id}</span></td><td><div class="ci-description">${description}</div></td><td><span class="ci-pill">${stockType}</span></td><td><span class="ci-pill">${unitType}</span></td><td><div class="ci-actions"><form method="POST" action="p_cat_pdt.php"><input type="hidden" name="category_id" value="${id}"><button type="submit" name="submit" class="ci-action ci-action-view" title="View Products"><i class="fa-solid fa-eye"></i></button></form><form method="POST" action="delete_category.php" onsubmit="return confirm('Are you sure you want to delete this category?');"><input type="hidden" name="category_id" value="${id}"><button type="submit" name="submit" class="ci-action ci-action-delete" title="Delete Category"><i class="fa-solid fa-trash"></i></button></form></div></td></tr>`;
        }).join('');
        tableContainer.innerHTML = `<table class="ci-table"><thead><tr><th>Category</th><th>Tracking ID</th><th>Description</th><th>Stock Type</th><th>Unit Type</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;
    }
    function renderMobile(data) {
        if (!data.length) { mobileContainer.innerHTML = emptyState('No categories match your search.'); return; }
        mobileContainer.innerHTML = data.map(category => {
            const id = escapeHTML(category.id);
            const name = displayValue(category.name, 'Unnamed category');
            const description = displayValue(category.description, 'No description provided.');
            const stockType = displayValue(category.stock_type, 'Not specified');
            const unitType = displayValue(category.unit_type, 'Not specified');
            return `<div class="ci-card"><div class="ci-card-head"><div class="ci-card-title"><div class="ci-card-title-icon"><i class="fa-solid fa-layer-group"></i></div><div><strong>${name}</strong><small>#${id}</small></div></div></div><div class="ci-card-row"><span class="ci-card-label">Description</span><span class="ci-card-value">${description}</span></div><div class="ci-card-row"><span class="ci-card-label">Stock Type</span><span class="ci-card-value"><span class="ci-pill">${stockType}</span></span></div><div class="ci-card-row"><span class="ci-card-label">Unit Type</span><span class="ci-card-value"><span class="ci-pill">${unitType}</span></span></div><div class="ci-card-actions"><form method="POST" action="p_cat_pdt.php" style="flex:1"><input type="hidden" name="category_id" value="${id}"><button type="submit" name="submit" class="ci-mobile-action view" style="width:100%"><i class="fa-solid fa-eye"></i> View Products</button></form><form method="POST" action="delete_category.php" style="flex:1" onsubmit="return confirm('Are you sure you want to delete this category?');"><input type="hidden" name="category_id" value="${id}"><button type="submit" name="submit" class="ci-mobile-action delete" style="width:100%"><i class="fa-solid fa-trash"></i> Delete</button></form></div></div>`;
        }).join('');
    }
    function performSearch() {
        const type = searchBy.value;
        const query = searchInput.value.trim().toLowerCase();
        const filtered = categories.filter(category => {
            if (!query) return true;
            if (type === 'name') return String(category.name ?? '').toLowerCase().includes(query);
            if (type === 'id') return String(category.id ?? '').toLowerCase().includes(query);
            return true;
        });
        renderDesktop(filtered);
        renderMobile(filtered);
        if (totalKPI) totalKPI.textContent = filtered.length.toLocaleString();
        if (classificationKPI) {
            const stockTypes = new Set(filtered.map(category => String(category.stock_type ?? '').trim().toLowerCase()).filter(Boolean));
            classificationKPI.textContent = stockTypes.size.toLocaleString() + ' Stock Types';
        }
        if (statusKPI) statusKPI.textContent = query ? (filtered.length ? 'Results Found' : 'No Results') : 'Active';
        if (heroCounter) heroCounter.textContent = filtered.length.toLocaleString() + ' Categories';
    }
    searchButton.addEventListener('click', function (event) { event.preventDefault(); performSearch(); });
    searchInput.addEventListener('input', performSearch);
    searchBy.addEventListener('change', performSearch);
    searchInput.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); performSearch(); } });
    if (categories.length) {
        performSearch();
    } else {
        if (totalKPI) totalKPI.textContent = '0';
        if (classificationKPI) classificationKPI.textContent = '0 Stock Types';
        if (statusKPI) statusKPI.textContent = 'Active';
        if (heroCounter) heroCounter.textContent = '0 Categories';
        tableContainer.innerHTML = originalTableHTML;
        mobileContainer.innerHTML = originalMobileHTML;
    }
});
</script>
</body>
</html>
