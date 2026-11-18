<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');

$companyid = (int)($_SESSION['company_id'] ?? 0);
if ($companyid <= 0) { exit('Invalid company session.'); }

$companyName = '';
$stmt = $pdo->prepare("SELECT company_name FROM companies WHERE id = ? LIMIT 1");
$stmt->execute([$companyid]);
$companyRow = $stmt->fetch(PDO::FETCH_ASSOC);
if ($companyRow && !empty($companyRow['company_name'])) $companyName = trim($companyRow['company_name']);
$companyAbbreviation = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $companyName), 0, 4));
if ($companyAbbreviation === '') $companyAbbreviation = 'COMP';

/* Always use the authenticated user's active location. */
$activeLocationId = (int)($_SESSION['location_id'] ?? 0);
if ($activeLocationId <= 0) {
    exit('No active location is assigned to this user. Please select or assign a location first.');
}
$stmt = $pdo->prepare("SELECT id, name, type FROM locations WHERE id = ? AND company_id = ? LIMIT 1");
$stmt->execute([$activeLocationId, $companyid]);
$activeLocation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$activeLocation) {
    exit('The active user location is invalid or does not belong to this company.');
}
$locations = [$activeLocation];

$categories = [];
$stmt = $pdo->prepare("SELECT id, name FROM job_categories WHERE company_id = ? ORDER BY name ASC");
$stmt->execute([$companyid]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Load service-only products and aggregate stock only at the active location. */
$materials = [];
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.barcode, p.cost_price, p.description,
           COALESCE(SUM(s.quantity), 0) AS available_quantity
    FROM products p
    LEFT JOIN stocks s
        ON s.product_id = p.id
        AND s.company_id = p.company_id
        AND s.location_id = ?
    WHERE p.company_id = ?
      AND p.stock_type = 'Service Only'
    GROUP BY p.id, p.name, p.barcode, p.cost_price, p.description
    ORDER BY p.name ASC
");
$stmt->execute([$activeLocationId, $companyid]);
$materialRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($materialRows as $row) {
    $materials[] = [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'barcode' => (string)($row['barcode'] ?? ''),
        'description' => (string)($row['description'] ?? ''),
        'cost' => (float)($row['cost_price'] ?? 0),
        'stock' => [(string)$activeLocationId => (float)$row['available_quantity']]
    ];
}

function service_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= service_h($company_name ?? $companyName) ?> | Service Registration</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<style>
:root{--sr-primary:#5b5ce2;--sr-primary2:#7c3aed;--sr-text:#172033;--sr-muted:#718096;--sr-border:rgba(148,163,184,.23);--sr-surface:rgba(255,255,255,.88);--sr-shadow:0 16px 42px rgba(15,23,42,.065)}
*{box-sizing:border-box}html,body{width:100%;max-width:100%;overflow-x:hidden}body{margin:0}
.service-page{width:100%;max-width:1500px;margin:0 auto;padding:20px clamp(12px,2vw,26px) 35px;color:var(--sr-text)}
.sr-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:0 0 22px;padding:22px 24px;border:1px solid var(--sr-border);border-radius:20px;background:linear-gradient(120deg,rgba(238,242,255,.96),rgba(255,255,255,.94) 60%,rgba(245,243,255,.92));box-shadow:var(--sr-shadow)}
.sr-hero-main{display:flex;align-items:center;gap:15px;min-width:0}.sr-hero-icon{width:54px;height:54px;flex:0 0 54px;display:grid;place-items:center;border-radius:16px;color:#fff;background:linear-gradient(135deg,var(--sr-primary),var(--sr-primary2));box-shadow:0 12px 25px rgba(91,92,226,.2);font-size:20px}.sr-hero h1{margin:0;color:#172033;font-size:clamp(21px,2vw,27px);font-weight:850;letter-spacing:-.035em}.sr-hero p{margin:6px 0 0;color:#718096;font-size:12px;line-height:1.5}.sr-hero-status{display:inline-flex;align-items:center;gap:8px;padding:10px 13px;border:1px solid rgba(16,185,129,.2);border-radius:999px;background:rgba(236,253,245,.85);color:#047857;font-size:11px;font-weight:800;white-space:nowrap}.sr-dot{width:7px;height:7px;border-radius:50%;background:#10b981;box-shadow:0 0 0 4px rgba(16,185,129,.1)}
.sr-breadcrumb{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 18px;color:#8490a3;font-size:12px}.sr-breadcrumb a{color:#64748b;text-decoration:none}.sr-breadcrumb a:hover{color:var(--sr-primary)}
.sr-layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:19px;align-items:start}.sr-main{min-width:0}.sr-card{position:relative;margin-bottom:18px;padding:22px;border:1px solid var(--sr-border);border-radius:19px;background:var(--sr-surface);box-shadow:var(--sr-shadow);backdrop-filter:blur(16px)}.sr-card:last-child{margin-bottom:0}.sr-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:19px}.sr-card-title{display:flex;align-items:center;gap:11px;min-width:0}.sr-card-icon{width:39px;height:39px;flex:0 0 39px;display:grid;place-items:center;border:1px solid rgba(99,102,241,.1);border-radius:12px;background:#eef2ff;color:var(--sr-primary)}.sr-card-title h2{margin:0;color:var(--sr-text);font-size:15px;font-weight:850}.sr-card-title p{margin:3px 0 0;color:var(--sr-muted);font-size:11px}.sr-count{padding:6px 10px;border-radius:999px;background:#f1f5f9;color:#64748b;font-size:10px;font-weight:800;white-space:nowrap}
.sr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.sr-field{min-width:0}.sr-field.full{grid-column:1/-1}.sr-label{display:flex;align-items:center;gap:7px;margin-bottom:7px;color:#475569;font-size:11px;font-weight:800}.sr-label i{width:14px;color:var(--sr-primary);text-align:center}.sr-input,.sr-textarea{width:100%;height:45px;padding:0 13px;border:1px solid var(--sr-border);border-radius:12px;background:rgba(255,255,255,.88);color:var(--sr-text);font-size:12px;outline:none;transition:.2s}.sr-textarea{height:auto;min-height:92px;padding:12px 13px;resize:vertical;line-height:1.55}.sr-input:focus,.sr-textarea:focus,.sr-search:focus{border-color:rgba(91,92,226,.55);box-shadow:0 0 0 4px rgba(91,92,226,.08)}.sr-input[readonly]{background:#f8fafc;color:#64748b}.sr-hint{display:block;margin-top:6px;color:#94a3b8;font-size:10px;line-height:1.45}
.sr-picker{position:relative;min-width:0}.sr-search-wrap{position:relative}.sr-search{width:100%;height:45px;padding:0 38px 0 13px;border:1px solid var(--sr-border);border-radius:12px;background:rgba(255,255,255,.9);color:var(--sr-text);font-size:12px;outline:none}.sr-search-wrap>i{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:11px;pointer-events:none}.sr-menu{position:absolute;z-index:50;top:calc(100% + 7px);left:0;right:0;display:none;max-height:265px;overflow:auto;padding:5px;border:1px solid rgba(148,163,184,.25);border-radius:13px;background:#fff;box-shadow:0 18px 38px rgba(15,23,42,.14)}.sr-menu.open{display:block}.sr-option{display:flex;width:100%;align-items:center;justify-content:space-between;gap:10px;padding:10px;border:0;border-radius:9px;background:transparent;color:#243047;text-align:left;cursor:pointer}.sr-option:hover,.sr-option.active{background:#eef2ff}.sr-option strong{display:block;font-size:11px;font-weight:800}.sr-option small{display:block;margin-top:3px;color:#8490a3;font-size:10px}.sr-option .sr-option-meta{flex:0 0 auto;text-align:right;color:#64748b;font-size:10px}.sr-empty{padding:15px 10px;color:#94a3b8;text-align:center;font-size:11px}.sr-selected{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:7px;padding:8px 10px;border-radius:10px;background:#f5f7ff;color:#4f46e5;font-size:10px}.sr-selected button{border:0;background:transparent;color:#64748b;cursor:pointer}
.sr-material-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.sr-material-top p{margin:4px 0 0;color:var(--sr-muted);font-size:11px}.sr-add{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:0 14px;border:0;border-radius:11px;background:linear-gradient(135deg,var(--sr-primary),var(--sr-primary2));color:#fff;font-size:11px;font-weight:800;cursor:pointer;box-shadow:0 9px 20px rgba(91,92,226,.16)}.sr-add:hover{filter:brightness(1.04);transform:translateY(-1px)}.sr-table-wrap{width:100%;overflow-x:auto;border:1px solid var(--sr-border);border-radius:14px;-webkit-overflow-scrolling:touch}.sr-table{width:100%;min-width:770px;border-collapse:collapse}.sr-table th{padding:12px 11px;background:#f8fafc;color:#64748b;text-align:left;font-size:10px;font-weight:850;letter-spacing:.045em;text-transform:uppercase;white-space:nowrap}.sr-table td{padding:10px 11px;border-top:1px solid #edf1f6;vertical-align:middle}.sr-table .sr-search{height:40px;font-size:11px}.sr-table .sr-input{height:40px;padding:0 9px;font-size:11px}.sr-table .sr-menu{min-width:260px}.sr-table .sr-picker{min-width:210px}.sr-stock-line{display:block;margin-top:5px;color:#8793a5;font-size:9px}.sr-stock-line.low{color:#dc6b22}.sr-remove{width:34px;height:34px;border:1px solid rgba(239,68,68,.15);border-radius:10px;background:#fff5f5;color:#dc2626;cursor:pointer}.sr-remove:hover{background:#fee2e2}.sr-empty-row td{padding:25px!important;color:#94a3b8;text-align:center;font-size:11px}.sr-empty-row i{display:block;margin-bottom:7px;font-size:19px;color:#cbd5e1}.sr-service-name{min-width:210px}.sr-table .sr-service-name{min-width:210px}.sr-inline-help{margin-top:5px;color:#94a3b8;font-size:9px}.sr-alert{display:none;margin-top:12px;padding:11px 13px;border:1px solid #fed7aa;border-radius:11px;background:#fff7ed;color:#9a3412;font-size:11px;line-height:1.5}.sr-alert.show{display:block}
.sr-side{position:sticky;top:18px;min-width:0}.sr-summary-hero{padding:20px;border-radius:18px;color:#fff;background:radial-gradient(circle at 100% 0%,rgba(255,255,255,.18),transparent 38%),linear-gradient(135deg,#5657df,#7c3aed);box-shadow:0 18px 38px rgba(79,70,229,.2)}.sr-summary-hero small{display:block;opacity:.78;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.sr-summary-hero strong{display:block;margin-top:8px;font-size:27px;line-height:1.15;font-weight:900;letter-spacing:-.04em;overflow-wrap:anywhere}.sr-summary-hero span{display:block;margin-top:7px;opacity:.78;font-size:10px}.sr-metric-list{margin-top:12px;padding:8px 15px;border:1px solid var(--sr-border);border-radius:16px;background:var(--sr-surface);box-shadow:var(--sr-shadow)}.sr-metric{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid #edf1f6;color:#64748b;font-size:11px}.sr-metric:last-child{border-bottom:0}.sr-metric strong{color:#172033;font-size:12px;font-weight:850;text-align:right}.sr-metric.total{color:#334155;font-weight:850}.sr-metric.total strong{color:var(--sr-primary);font-size:15px}.sr-checklist{margin-top:12px;padding:15px;border:1px solid var(--sr-border);border-radius:15px;background:rgba(255,255,255,.75)}.sr-checklist h3{margin:0 0 10px;font-size:11px;font-weight:850;color:#334155}.sr-check{display:flex;align-items:center;gap:8px;margin:9px 0;color:#64748b;font-size:10px}.sr-check i{color:#10b981}.sr-submit{width:100%;min-height:49px;margin-top:13px;display:flex;align-items:center;justify-content:center;gap:9px;border:0;border-radius:13px;background:linear-gradient(135deg,var(--sr-primary),var(--sr-primary2));color:#fff;font-size:12px;font-weight:850;cursor:pointer;box-shadow:0 12px 24px rgba(91,92,226,.2)}.sr-submit:disabled{opacity:.55;cursor:not-allowed}.sr-footnote{margin:9px 2px 0;color:#94a3b8;font-size:9px;line-height:1.5;text-align:center}
.dark .sr-hero,.dark .sr-card,.dark .sr-metric-list,.dark .sr-checklist{background:#111827;border-color:#374151}.dark .sr-hero h1,.dark .sr-card-title h2,.dark .sr-metric strong,.dark .sr-checklist h3{color:#f8fafc}.dark .sr-hero p,.dark .sr-card-title p,.dark .sr-breadcrumb{color:#9ca3af}.dark .sr-label{color:#d1d5db}.dark .sr-input,.dark .sr-textarea,.dark .sr-search{background:#0f172a;border-color:#374151;color:#fff}.dark .sr-menu{background:#111827;border-color:#374151}.dark .sr-option{color:#e5e7eb}.dark .sr-option:hover{background:#1e293b}.dark .sr-table th{background:#1f2937;color:#cbd5e1}.dark .sr-table td{border-color:#374151}.dark .sr-metric{border-color:#374151;color:#9ca3af}.dark .sr-metric strong{color:#f8fafc}.dark .sr-selected{background:#1e1b4b}.dark .sr-input[readonly]{background:#1f2937}.dark .sr-stock-line{color:#9ca3af}
@media(max-width:1100px){.sr-layout{grid-template-columns:minmax(0,1fr) 275px;gap:14px}.sr-card{padding:18px}}
@media(max-width:850px){.sr-layout{grid-template-columns:1fr}.sr-side{position:static;display:grid;grid-template-columns:1fr 1fr;gap:12px}.sr-summary-hero{grid-row:span 2}.sr-metric-list,.sr-checklist{margin-top:0}.sr-submit{grid-column:1/-1;margin-top:0}.sr-footnote{grid-column:1/-1}}
@media(max-width:620px){.service-page{padding:12px 10px 25px}.sr-hero{align-items:flex-start;padding:17px;border-radius:16px}.sr-hero-status{display:none}.sr-hero-icon{width:44px;height:44px;flex-basis:44px;border-radius:13px}.sr-hero h1{font-size:21px}.sr-card{padding:15px;border-radius:15px}.sr-grid{grid-template-columns:1fr;gap:13px}.sr-field.full{grid-column:auto}.sr-card-head{align-items:flex-start}.sr-material-top{flex-direction:column}.sr-add{width:100%}.sr-side{grid-template-columns:1fr}.sr-summary-hero{grid-row:auto}.sr-submit{grid-column:auto}.sr-footnote{grid-column:auto}.sr-summary-hero strong{font-size:25px}}
@media(max-width:370px){.sr-hero-main{gap:10px}.sr-hero-icon{width:39px;height:39px;flex-basis:39px}.sr-card{padding:12px}.sr-summary-hero{padding:17px}}
</style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900':darkMode===true}">
<div class="flex h-screen overflow-hidden">
<?php include('sidebar.php'); ?>
<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
<?php include('header.php'); ?>
<main>
<div class="service-page">
    <div class="sr-hero"><div class="sr-hero-main"><div class="sr-hero-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div><div><h1>Service Registration</h1><p>Build a service record, allocate materials and capture external skill costs in one workspace.</p></div></div><div class="sr-hero-status"><span class="sr-dot"></span> Registration workspace</div></div>
    <nav class="sr-breadcrumb"><a href="index.php"><i class="fa-solid fa-house"></i> Home</a><i class="fa-solid fa-chevron-right"></i><span>Service Registration</span></nav>
    <form method="POST" action="create_service.php" id="production_form" autocomplete="off">
    <div class="sr-layout">
      <div class="sr-main">
        <section class="sr-card">
          <div class="sr-card-head"><div class="sr-card-title"><div class="sr-card-icon"><i class="fa-solid fa-gears"></i></div><div><h2>Service details</h2><p>Define the service identity and where it will be registered.</p></div></div><span class="sr-count">STEP 01</span></div>
          <div class="sr-grid">
            <div class="sr-field"><label class="sr-label"><i class="fa-solid fa-location-dot"></i> Active user location <span style="color:#ef4444">*</span></label>
              <div class="sr-picker" id="locationPicker"><div class="sr-search-wrap"><input type="text" class="sr-search" id="locationSearch" placeholder="Active user location" autocomplete="off" aria-label="Active user location" aria-expanded="false" readonly><i class="fa-solid fa-lock"></i></div><div class="sr-menu" id="locationMenu"></div><select name="location_id" id="location_id" class="sr-hidden-select" required><option value="<?= $activeLocationId ?>" selected><?= service_h($activeLocation['name']) ?></option></select></div>
              <span class="sr-hint">Automatically assigned from your active user session. Stock is checked only at this location.</span>
            </div>
            <div class="sr-field"><label class="sr-label"><i class="fa-solid fa-layer-group"></i> Service category <span style="color:#ef4444">*</span></label><div class="sr-picker" id="categoryPicker"><div class="sr-search-wrap"><input type="text" class="sr-search" id="categorySearch" placeholder="Search service categories" autocomplete="off" aria-label="Search categories" aria-expanded="false"><i class="fa-solid fa-chevron-down"></i></div><div class="sr-menu" id="categoryMenu"></div><select name="category_id" id="category_id" class="sr-hidden-select"><option value="">Select category</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>"><?= service_h($cat['name']) ?></option><?php endforeach; ?></select></div></div>
            <div class="sr-field"><label class="sr-label" for="new_product_name"><i class="fa-solid fa-tag"></i> Service name <span style="color:#ef4444">*</span></label><input type="text" name="product_name" id="new_product_name" class="sr-input" placeholder="e.g. CCTV installation and configuration" maxlength="255" required></div>
            <div class="sr-field"><label class="sr-label" for="batch_number"><i class="fa-solid fa-hashtag"></i> Service number</label><input type="text" name="service_number" id="batch_number" class="sr-input" readonly required><span class="sr-hint">Automatically generated for this registration.</span></div>
            <div class="sr-field full"><label class="sr-label" for="service_description"><i class="fa-solid fa-align-left"></i> Service description</label><textarea name="description" id="service_description" class="sr-textarea" placeholder="Describe the scope, deliverables or important notes for this service..."></textarea></div>
          </div>
        </section>
        <section class="sr-card">
          <div class="sr-material-top"><div class="sr-card-title"><div class="sr-card-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div><h2>Raw materials</h2><p>Select service-only products and enter the quantity consumed.</p></div></div><button type="button" id="add_raw_material" class="sr-add"><i class="fa-solid fa-plus"></i> Add material</button></div>
          <div class="sr-table-wrap"><table class="sr-table"><thead><tr><th style="width:36%">Material / product</th><th>Quantity used</th><th>Unit cost</th><th>Line total</th><th>Action</th></tr></thead><tbody id="raw_materials_body"><tr class="sr-empty-row"><td colspan="5"><i class="fa-solid fa-box-open"></i>No raw materials added yet. Select “Add material” to begin.</td></tr></tbody></table></div><div class="sr-alert" id="stockAlert" role="alert"></div>
        </section>
        <section class="sr-card">
          <div class="sr-material-top"><div class="sr-card-title"><div class="sr-card-icon"><i class="fa-solid fa-user-gear"></i></div><div><h2>External skills & services</h2><p>Capture outsourced labour, specialist skills and other service fees.</p></div></div><button type="button" id="add_service" class="sr-add"><i class="fa-solid fa-plus"></i> Add external skill</button></div>
          <div class="sr-table-wrap"><table class="sr-table" style="min-width:560px"><thead><tr><th style="width:55%">Skill / service description</th><th>Fee (₦)</th><th>Action</th></tr></thead><tbody id="services_body"><tr class="sr-empty-row"><td colspan="3"><i class="fa-solid fa-toolbox"></i>No external skills added yet.</td></tr></tbody></table></div>
        </section>
      </div>
      <aside class="sr-side">
        <div class="sr-summary-hero"><small>Estimated total service cost</small><strong id="total_production_cost">₦0.00</strong><span>Live estimate · Updates as you add costs</span></div>
        <div class="sr-metric-list"><div class="sr-metric"><span><i class="fa-solid fa-boxes-stacked"></i> Materials cost</span><strong id="total_materials_cost">₦0.00</strong></div><div class="sr-metric"><span><i class="fa-solid fa-handshake"></i> External skills</span><strong id="total_services_cost">₦0.00</strong></div><div class="sr-metric"><span><i class="fa-solid fa-cubes"></i> Materials selected</span><strong id="materials_count">0</strong></div><div class="sr-metric"><span><i class="fa-solid fa-user-gear"></i> External entries</span><strong id="skills_count">0</strong></div><div class="sr-metric total"><span>Total cost</span><strong id="summary_total_cost">₦0.00</strong></div></div>
        <div class="sr-checklist"><h3>Registration checklist</h3><div class="sr-check"><i class="fa-solid fa-circle-check"></i><span>Company identity applied</span></div><div class="sr-check"><i class="fa-solid fa-circle-check"></i><span>Active user location applied</span></div><div class="sr-check"><i class="fa-solid fa-circle-check"></i><span>Live cost calculation</span></div><div class="sr-check"><i class="fa-solid fa-circle-check"></i><span>Backend-compatible form fields</span></div></div>
        <button type="submit" name="finalize_production" class="sr-submit" id="registerService"><i class="fa-solid fa-circle-check"></i> Register service</button><p class="sr-footnote">The final cost is recalculated by the server when the service is saved. Stock availability is indicative and is revalidated by the backend.</p>
      </aside>
    </div>
    </form>
</div>
</main>
</div>
</div>
<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script>
(() => {
'use strict';
const companyAbbreviation = <?= json_encode($companyAbbreviation, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
const activeLocation = <?= json_encode(['id'=>(string)$activeLocation['id'],'name'=>$activeLocation['name'],'type'=>$activeLocation['type']], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const locations = [activeLocation];
const categories = <?= json_encode(array_map(function($r){return ['id'=>(string)$r['id'],'name'=>$r['name']];}, $categories), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const materials = <?= json_encode($materials, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const locationSelect=document.getElementById('location_id');
const categorySelect=document.getElementById('category_id');
const locationSearch=document.getElementById('locationSearch');
const categorySearch=document.getElementById('categorySearch');
const locationMenu=document.getElementById('locationMenu');
const categoryMenu=document.getElementById('categoryMenu');
const materialBody=document.getElementById('raw_materials_body');
const serviceBody=document.getElementById('services_body');
const stockAlert=document.getElementById('stockAlert');
locationSelect.value=activeLocation.id;
locationSearch.value=activeLocation.name+(activeLocation.type?' ('+activeLocation.type+')':'');
let materialIndex=0,serviceIndex=0;
const money=n=>'₦'+Number(n||0).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});
const num=n=>{const v=Number.parseFloat(n);return Number.isFinite(v)?v:0;};
function makeServiceNumber(){const d=new Date();const date=d.getFullYear()+String(d.getMonth()+1).padStart(2,'0')+String(d.getDate()).padStart(2,'0');const rand=String(Math.floor(100000+Math.random()*900000));document.getElementById('batch_number').value=`${companyAbbreviation}-SERV-${date}-${rand}-00`;}
function setMenu(menu,input,open){menu.classList.toggle('open',open);input.setAttribute('aria-expanded',open?'true':'false');}
function drawSimplePicker(data,input,menu,select,kind){
 const render=()=>{const q=input.value.trim().toLocaleLowerCase();const list=data.filter(x=>(x.name+' '+(x.type||'')).toLocaleLowerCase().includes(q));menu.innerHTML='';if(!list.length){menu.innerHTML='<div class="sr-empty">No matching '+kind+'.</div>';return;}list.forEach(item=>{const b=document.createElement('button');b.type='button';b.className='sr-option';const left=document.createElement('span');const strong=document.createElement('strong');strong.textContent=item.name;left.appendChild(strong);if(item.type){const sm=document.createElement('small');sm.textContent=item.type;left.appendChild(sm);}b.appendChild(left);b.addEventListener('click',()=>{select.value=item.id;input.value=item.name+(item.type?' ('+item.type+')':'');setMenu(menu,input,false);if(kind==='location'){refreshMaterialStocks();}});menu.appendChild(b);});};
 input.addEventListener('focus',()=>{render();setMenu(menu,input,true);});input.addEventListener('input',()=>{render();setMenu(menu,input,true);});input.addEventListener('keydown',e=>{if(e.key==='Escape')setMenu(menu,input,false);if(e.key==='Enter'&&menu.querySelector('.sr-option')){e.preventDefault();menu.querySelector('.sr-option').click();}});document.addEventListener('click',e=>{if(!e.target.closest('#'+(kind==='location'?'locationPicker':'categoryPicker')))setMenu(menu,input,false);});
}
drawSimplePicker(locations,locationSearch,locationMenu,locationSelect,'location');
drawSimplePicker(categories,categorySearch,categoryMenu,categorySelect,'category');
function selectedLocation(){return String(activeLocation.id);}
function availableFor(product){return num(product.stock?.[selectedLocation()]||0);}
function emptyRow(tbody,message,cols,icon){if(!tbody.querySelector('tr')){const tr=document.createElement('tr');tr.className='sr-empty-row';const td=document.createElement('td');td.colSpan=cols;const i=document.createElement('i');i.className='fa-solid '+icon;const span=document.createElement('span');span.textContent=message;td.append(i,span);tr.appendChild(td);tbody.appendChild(tr);}}
function removeEmpty(tbody){const e=tbody.querySelector('.sr-empty-row');if(e)e.remove();}
function updateTotals(){let materialsTotal=0,servicesTotal=0,materialCount=0,skillCount=0;materialBody.querySelectorAll('tr:not(.sr-empty-row)').forEach(row=>{const total=num(row.querySelector('.rm_total')?.value);materialsTotal+=total;if(row.querySelector('.rm_product_id')?.value)materialCount++;});serviceBody.querySelectorAll('tr:not(.sr-empty-row)').forEach(row=>{servicesTotal+=num(row.querySelector('.srv_cost')?.value);if((row.querySelector('.srv_name')?.value||'').trim())skillCount++;});const total=materialsTotal+servicesTotal;document.getElementById('total_materials_cost').textContent=money(materialsTotal);document.getElementById('total_services_cost').textContent=money(servicesTotal);document.getElementById('total_production_cost').textContent=money(total);document.getElementById('summary_total_cost').textContent=money(total);document.getElementById('materials_count').textContent=materialCount;document.getElementById('skills_count').textContent=skillCount;}
function updateMaterialRow(row){const id=row.querySelector('.rm_product_id').value;const product=materials.find(p=>String(p.id)===String(id));const qtyInput=row.querySelector('.rm_qty');const unitInput=row.querySelector('.rm_unit');const totalInput=row.querySelector('.rm_total');const stockLine=row.querySelector('.sr-stock-line');if(!product){unitInput.value='0.00';totalInput.value='0.00';stockLine.textContent='Choose a material to view available stock.';stockLine.classList.remove('low');updateTotals();return;}const available=availableFor(product);const qty=num(qtyInput.value);unitInput.value=num(product.cost).toFixed(2);totalInput.value=(num(product.cost)*qty).toFixed(2);stockLine.textContent=`Available at your active location: ${available.toLocaleString('en-NG',{maximumFractionDigits:2})}`;stockLine.classList.toggle('low',available<=0||qty>available);row.dataset.stock=String(available);row.dataset.productName=product.name;updateTotals();validateStock(false);}
function selectMaterial(row,product){const hidden=row.querySelector('.rm_product_id');hidden.innerHTML='';const option=document.createElement('option');option.value=String(product.id);option.textContent=product.name;option.selected=true;hidden.appendChild(option);const input=row.querySelector('.rm_product_search');input.value=product.name;row.querySelector('.sr-menu').classList.remove('open');updateMaterialRow(row);}
function renderMaterialMenu(row,query=''){const menu=row.querySelector('.sr-menu');const q=query.trim().toLocaleLowerCase();const chosen=new Set([...materialBody.querySelectorAll('.rm_product_id')].map(s=>s.value).filter(Boolean));const list=materials.filter(p=>!chosen.has(String(p.id))||String(p.id)===row.querySelector('.rm_product_id').value).filter(p=>(p.name+' '+p.barcode+' '+p.description).toLocaleLowerCase().includes(q));menu.innerHTML='';if(!list.length){const d=document.createElement('div');d.className='sr-empty';d.textContent=materials.length?'No matching materials.':'No Service Only products found for this company.';menu.appendChild(d);return;}list.forEach(p=>{const b=document.createElement('button');b.type='button';b.className='sr-option';const left=document.createElement('span');const strong=document.createElement('strong');strong.textContent=p.name;const small=document.createElement('small');small.textContent=p.barcode?'Code: '+p.barcode:'Service material';left.append(strong,small);const meta=document.createElement('span');meta.className='sr-option-meta';meta.textContent=money(p.cost)+' · Stock '+availableFor(p).toLocaleString('en-NG',{maximumFractionDigits:2});b.append(left,meta);b.addEventListener('click',()=>selectMaterial(row,p));menu.appendChild(b);});}
function addRawMaterial(){removeEmpty(materialBody);const row=document.createElement('tr');const index=materialIndex++;row.innerHTML=`<td><div class="sr-picker"><div class="sr-search-wrap"><input type="text" class="sr-search rm_product_search" placeholder="Search material name or code" autocomplete="off" aria-label="Search raw material" aria-expanded="false"><i class="fa-solid fa-magnifying-glass"></i></div><div class="sr-menu"></div><select class="sr-hidden-select rm_product_id" name="raw_materials[${index}][product_id]"><option value="">Select material</option></select><small class="sr-stock-line">Choose a material to view available stock.</small></div></td><td><input type="number" class="sr-input rm_qty" name="raw_materials[${index}][qty]" value="1" min="0.01" step="any" required></td><td><input type="number" class="sr-input rm_unit" value="0.00" readonly tabindex="-1"></td><td><input type="number" class="sr-input rm_total" value="0.00" readonly tabindex="-1"></td><td><button type="button" class="sr-remove remove-material" title="Remove material" aria-label="Remove material"><i class="fa-solid fa-trash"></i></button></td>`;materialBody.appendChild(row);const input=row.querySelector('.rm_product_search'),menu=row.querySelector('.sr-menu');input.addEventListener('focus',()=>{renderMaterialMenu(row,input.value);setMenu(menu,input,true);});input.addEventListener('input',()=>{row.querySelector('.rm_product_id').value='';updateMaterialRow(row);renderMaterialMenu(row,input.value);setMenu(menu,input,true);});input.addEventListener('keydown',e=>{if(e.key==='Escape')setMenu(menu,input,false);if(e.key==='Enter'&&menu.querySelector('.sr-option')){e.preventDefault();menu.querySelector('.sr-option').click();}});document.addEventListener('click',e=>{if(!e.target.closest('#raw_materials_body tr')||!row.contains(e.target))setMenu(menu,input,false);});row.querySelector('.rm_qty').addEventListener('input',()=>updateMaterialRow(row));row.querySelector('.remove-material').addEventListener('click',()=>{row.remove();reindexMaterials();updateTotals();emptyRow(materialBody,'No raw materials added yet. Select “Add material” to begin.',5,'fa-box-open');validateStock(false);});updateMaterialRow(row);}
function reindexMaterials(){[...materialBody.querySelectorAll('tr:not(.sr-empty-row)')].forEach((row,i)=>{row.querySelector('.rm_product_id').name=`raw_materials[${i}][product_id]`;row.querySelector('.rm_qty').name=`raw_materials[${i}][qty]`;});}
function refreshMaterialStocks(){materialBody.querySelectorAll('tr:not(.sr-empty-row)').forEach(row=>{const id=row.querySelector('.rm_product_id').value;const input=row.querySelector('.rm_product_search');if(id){const p=materials.find(x=>String(x.id)===String(id));if(p)updateMaterialRow(row);}else if(input){renderMaterialMenu(row,input.value);}});validateStock(false);}
function validateStock(show){const locationId=selectedLocation();let errors=[];materialBody.querySelectorAll('tr:not(.sr-empty-row)').forEach(row=>{const id=row.querySelector('.rm_product_id').value;if(!id)return;const product=materials.find(p=>String(p.id)===String(id));if(!product)return;const available=availableFor(product),qty=num(row.querySelector('.rm_qty').value);if(qty>available){errors.push(`${product.name}: requested ${qty}, available ${available}.`);}});if(!locationId&&materialBody.querySelectorAll('tr:not(.sr-empty-row)').length)errors.unshift('No active location is available.');stockAlert.textContent=errors.length?'Stock check: '+errors.join(' '):'';stockAlert.classList.toggle('show',errors.length>0);return errors.length===0;}
function addService(){removeEmpty(serviceBody);const row=document.createElement('tr');const index=serviceIndex++;row.innerHTML=`<td><input type="text" name="services[${index}][name]" class="sr-input srv_name" placeholder="e.g. Electrical installation labour" maxlength="255" required></td><td><input type="number" name="services[${index}][cost]" class="sr-input srv_cost" value="0" min="0" step="any" required></td><td><button type="button" class="sr-remove remove-service" title="Remove external service" aria-label="Remove external service"><i class="fa-solid fa-trash"></i></button></td>`;serviceBody.appendChild(row);row.querySelectorAll('input').forEach(input=>input.addEventListener('input',updateTotals));row.querySelector('.remove-service').addEventListener('click',()=>{row.remove();reindexServices();updateTotals();emptyRow(serviceBody,'No external skills added yet.',3,'fa-toolbox');});row.querySelector('.srv_name').focus();updateTotals();}
function reindexServices(){[...serviceBody.querySelectorAll('tr:not(.sr-empty-row)')].forEach((row,i)=>{row.querySelector('.srv_name').name=`services[${i}][name]`;row.querySelector('.srv_cost').name=`services[${i}][cost]`;});}
document.getElementById('add_raw_material').addEventListener('click',addRawMaterial);document.getElementById('add_service').addEventListener('click',addService);
document.getElementById('production_form').addEventListener('submit',e=>{const locationOk=!!locationSelect.value;const categoryOk=!!categorySelect.value;if(!locationOk||!categoryOk){e.preventDefault();alert('Your active location and a service category are required.');return;}if(!validateStock(true)){e.preventDefault();stockAlert.scrollIntoView({behavior:'smooth',block:'center'});return;}const materialRows=[...materialBody.querySelectorAll('tr:not(.sr-empty-row)')];const missingMaterial=materialRows.some(row=>!row.querySelector('.rm_product_id').value);if(missingMaterial){e.preventDefault();alert('Please select a material for every raw material row, or remove the unused row.');return;}const invalidQty=[...materialBody.querySelectorAll('.rm_qty')].some(i=>num(i.value)<=0);if(invalidQty){e.preventDefault();alert('Every raw material quantity must be greater than zero.');return;}const invalidSkill=[...serviceBody.querySelectorAll('.srv_name')].some((i,idx)=>i.value.trim()!==''&&num(serviceBody.querySelectorAll('.srv_cost')[idx]?.value)<=0);if(invalidSkill){e.preventDefault();alert('Enter a fee greater than zero for each external skill, or remove the unused row.');return;}document.getElementById('registerService').disabled=true;document.getElementById('registerService').innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Registering service...';});
makeServiceNumber();updateTotals();
})();
</script>
</body>
</html>
