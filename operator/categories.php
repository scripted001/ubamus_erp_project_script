<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

$company_id = $_SESSION['company_id'] ?? null;
$stock_type = 'Service Only';

$categories = [];
$totalCategories = 0;
$activeCategories = 0;
$totalDescriptions = 0;

if ($company_id !== null && $company_id !== '') {
    $stmt = $pdo->prepare("
        SELECT id, name, description, stock_type
        FROM product_categories
        WHERE company_id = ?
          AND stock_type = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$company_id, $stock_type]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as $category) {
        $name = trim((string)($category['name'] ?? ''));
        $description = trim((string)($category['description'] ?? ''));

        if ($name !== '') {
            $activeCategories++;
        }

        if ($description !== '') {
            $totalDescriptions++;
        }
    }

    $totalCategories = count($categories);
}

function service_category_escape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$categoryJson = json_encode(
    $categories,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($categoryJson === false) {
    $categoryJson = '[]';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#f8fafc">
    <title><?= service_category_escape($company_name ?? 'Company'); ?> | Service Categories</title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">

    <style>
        :root {
            --sc-primary:#4f46e5;
            --sc-secondary:#7c3aed;
            --sc-cyan:#0891b2;
            --sc-green:#059669;
            --sc-ink:#111827;
            --sc-muted:#64748b;
            --sc-border:rgba(148,163,184,.22);
            --sc-surface:rgba(255,255,255,.91);
            --sc-soft:#f8fafc;
            --sc-shadow:0 18px 48px rgba(15,23,42,.055);
        }
        * { box-sizing:border-box; }
        .service-category-page { width:100%; max-width:1440px; margin:0 auto; min-width:0; color:var(--sc-ink); }
        .service-category-hero,.service-category-panel,.service-category-stat {
            border:1px solid var(--sc-border); background:var(--sc-surface);
            backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px); box-shadow:var(--sc-shadow);
        }
        .service-category-hero {
            position:relative; overflow:hidden; padding:clamp(20px,3vw,30px);
            margin-bottom:18px; border-radius:24px;
        }
        .service-category-hero:before,.service-category-panel:before {
            content:""; position:absolute; top:0; left:0; right:0; height:2px;
            background:linear-gradient(90deg,transparent,var(--sc-primary),var(--sc-secondary),var(--sc-cyan),transparent);
        }
        .service-category-hero:after {
            content:""; position:absolute; width:260px; height:260px; right:-110px; top:-155px;
            border-radius:50%; background:radial-gradient(circle,rgba(99,102,241,.12),transparent 70%); pointer-events:none;
        }
        .service-category-hero-content { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .service-category-title { display:flex; align-items:center; gap:15px; min-width:0; }
        .service-category-title-icon {
            width:56px; height:56px; flex:0 0 56px; display:grid; place-items:center; border-radius:17px;
            color:#fff; font-size:21px; background:linear-gradient(135deg,var(--sc-primary),var(--sc-secondary));
            box-shadow:0 12px 28px rgba(79,70,229,.22);
        }
        .service-category-title h1 { margin:0; color:var(--sc-ink); font-size:clamp(19px,2vw,24px); line-height:1.2; font-weight:850; letter-spacing:-.04em; }
        .service-category-title p { margin:7px 0 0; color:var(--sc-muted); font-size:12px; line-height:1.6; }
        .service-category-mode { display:inline-flex; align-items:center; gap:8px; padding:9px 12px; border:1px solid rgba(16,185,129,.2); border-radius:999px; color:#047857; background:rgba(16,185,129,.065); font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
        .service-category-mode-dot { width:7px; height:7px; flex:0 0 7px; border-radius:50%; background:#10b981; box-shadow:0 0 0 4px rgba(16,185,129,.1); }
        .service-category-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
        .service-category-stat { position:relative; overflow:hidden; min-width:0; padding:18px; border-radius:19px; }
        .service-category-stat:after { content:""; position:absolute; width:85px; height:85px; right:-38px; top:-38px; border-radius:50%; background:rgba(99,102,241,.055); pointer-events:none; }
        .service-category-stat-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .service-category-stat-icon { width:38px; height:38px; display:grid; place-items:center; border-radius:12px; color:var(--sc-primary); background:rgba(99,102,241,.08); }
        .service-category-stat:nth-child(2) .service-category-stat-icon { color:var(--sc-cyan); background:rgba(8,145,178,.08); }
        .service-category-stat:nth-child(3) .service-category-stat-icon { color:var(--sc-green); background:rgba(5,150,105,.08); }
        .service-category-stat-label { color:var(--sc-muted); font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .service-category-stat-value { display:block; margin-top:12px; color:var(--sc-ink); font-size:clamp(23px,2.3vw,28px); line-height:1; font-weight:850; letter-spacing:-.04em; font-variant-numeric:tabular-nums; }
        .service-category-stat-note { display:block; margin-top:8px; color:#94a3b8; font-size:10px; line-height:1.5; }
        .service-category-panel { position:relative; overflow:hidden; border-radius:24px; }
        .service-category-panel:before { background:linear-gradient(90deg,transparent,rgba(99,102,241,.65),transparent); }
        .service-category-panel-header { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:22px 24px; border-bottom:1px solid var(--sc-border); }
        .service-category-panel-title { display:flex; align-items:center; gap:11px; min-width:0; }
        .service-category-panel-title-icon { width:38px; height:38px; flex:0 0 38px; display:grid; place-items:center; border-radius:11px; color:var(--sc-secondary); background:rgba(124,58,237,.08); }
        .service-category-panel-title h2 { margin:0; color:var(--sc-ink); font-size:15px; font-weight:800; }
        .service-category-panel-title p { margin:4px 0 0; color:var(--sc-muted); font-size:10px; line-height:1.5; }
        .service-category-toolbar { display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:8px; min-width:0; }
        .service-category-search { display:flex; align-items:center; gap:8px; width:min(100%,510px); }
        .service-category-select-wrap { width:135px; flex:0 0 135px; }
        .service-category-input-wrap { position:relative; flex:1 1 220px; min-width:0; }
        .service-category-search-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px; pointer-events:none; }
        .service-category-select,.service-category-input {
            width:100%; height:43px; border:1px solid rgba(148,163,184,.25); border-radius:12px; outline:none;
            color:var(--sc-ink); background:rgba(255,255,255,.8); font:inherit; font-size:12px; transition:border-color .18s,box-shadow .18s;
        }
        .service-category-select { padding:0 10px; }
        .service-category-input { padding:0 13px 0 36px; }
        .service-category-select:focus,.service-category-input:focus { border-color:rgba(99,102,241,.65); box-shadow:0 0 0 4px rgba(99,102,241,.09); }
        .service-category-button { height:43px; min-width:43px; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:0 13px; border:1px solid transparent; border-radius:12px; cursor:pointer; font-size:11px; font-weight:800; transition:transform .18s,background .18s; }
        .service-category-button:active { transform:scale(.97); }
        .service-category-button-primary { color:white; background:linear-gradient(135deg,var(--sc-primary),var(--sc-secondary)); box-shadow:0 8px 20px rgba(79,70,229,.18); }
        .service-category-button-secondary { color:#475569; background:rgba(148,163,184,.09); border-color:var(--sc-border); }
        .service-category-button:disabled { opacity:.6; cursor:wait; }
        .service-category-results-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:12px 24px; border-bottom:1px solid rgba(148,163,184,.12); }
        .service-category-results-text { color:var(--sc-muted); font-size:11px; }
        .service-category-results-text strong { color:var(--sc-ink); font-weight:800; }
        .service-category-filter-status { display:inline-flex; align-items:center; gap:6px; color:#64748b; font-size:10px; }
        .service-category-filter-status i { color:var(--sc-green); }
        .service-category-table-wrapper { width:100%; overflow:auto; }
        .service-category-table { width:100%; border-collapse:collapse; min-width:650px; }
        .service-category-table th { padding:14px 20px; text-align:left; color:#94a3b8; background:rgba(248,250,252,.78); border-bottom:1px solid var(--sc-border); font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; white-space:nowrap; }
        .service-category-table td { padding:15px 20px; color:#475569; border-bottom:1px solid rgba(148,163,184,.11); font-size:12px; vertical-align:middle; }
        .service-category-table tbody tr { transition:background .16s; }
        .service-category-table tbody tr:hover { background:rgba(99,102,241,.035); }
        .category-name-cell { display:flex; align-items:center; gap:11px; min-width:200px; }
        .category-name-icon,.service-mobile-name-icon { width:35px; height:35px; flex:0 0 35px; display:grid; place-items:center; border-radius:10px; color:var(--sc-primary); background:rgba(99,102,241,.075); }
        .category-name { color:var(--sc-ink); font-weight:750; overflow-wrap:anywhere; }
        .category-description { max-width:390px; color:var(--sc-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .category-id { display:inline-flex; align-items:center; padding:5px 8px; border-radius:7px; color:#64748b; background:rgba(148,163,184,.09); font-family:monospace; font-size:10px; white-space:nowrap; }
        .category-type { display:inline-flex; align-items:center; gap:6px; padding:6px 9px; border:1px solid rgba(16,185,129,.16); border-radius:999px; color:#047857; background:rgba(16,185,129,.06); font-size:9px; font-weight:800; white-space:nowrap; }
        .category-type-dot { width:5px; height:5px; flex:0 0 5px; border-radius:50%; background:#10b981; }
        .service-category-mobile { display:none; padding:14px; }
        .service-category-mobile-card { padding:16px; margin-bottom:11px; border:1px solid rgba(148,163,184,.18); border-radius:17px; background:rgba(255,255,255,.7); box-shadow:0 8px 25px rgba(15,23,42,.035); }
        .service-category-mobile-card:last-child { margin-bottom:0; }
        .service-mobile-top { display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:13px; margin-bottom:10px; border-bottom:1px solid rgba(148,163,184,.13); }
        .service-mobile-name { display:flex; align-items:center; gap:10px; min-width:0; }
        .service-mobile-name > div:last-child { min-width:0; }
        .service-mobile-name strong { display:block; color:var(--sc-ink); font-size:13px; line-height:1.45; overflow-wrap:anywhere; }
        .service-mobile-id { display:block; margin-top:3px; color:#94a3b8; font-family:monospace; font-size:9px; }
        .service-mobile-row { display:grid; grid-template-columns:minmax(86px,105px) minmax(0,1fr); gap:12px; padding:8px 0; }
        .service-mobile-label { color:#94a3b8; font-size:10px; font-weight:750; }
        .service-mobile-value { color:#475569; font-size:11px; line-height:1.55; overflow-wrap:anywhere; }
        .service-category-empty { padding:48px 20px; text-align:center; }
        .service-category-empty-icon { width:58px; height:58px; margin:0 auto 14px; display:grid; place-items:center; border-radius:18px; color:var(--sc-primary); background:rgba(99,102,241,.08); font-size:21px; }
        .service-category-empty h3 { margin:0; color:var(--sc-ink); font-size:15px; font-weight:800; }
        .service-category-empty p { max-width:420px; margin:7px auto 0; color:var(--sc-muted); font-size:11px; line-height:1.6; }
        .service-category-empty-action { margin-top:16px; }
        .service-category-footer { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; padding:13px 24px; border-top:1px solid rgba(148,163,184,.12); color:#94a3b8; font-size:10px; }
        .service-category-footer strong { color:var(--sc-muted); }
        [hidden] { display:none !important; }

        @media (max-width:1100px) {
            .service-category-panel-header { align-items:flex-start; flex-direction:column; }
            .service-category-toolbar,.service-category-search { width:100%; }
            .service-category-toolbar { justify-content:stretch; }
            .service-category-search { max-width:none; }
        }
        @media (max-width:800px) {
            .service-category-stats { grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
            .service-category-stat { padding:15px; }
            .service-category-table-wrapper { display:none; }
            .service-category-mobile { display:block; }
            .service-category-results-bar,.service-category-footer { padding-left:16px; padding-right:16px; }
        }
        @media (max-width:600px) {
            .service-category-hero { padding:19px; border-radius:19px; }
            .service-category-hero-content { align-items:flex-start; flex-direction:column; gap:15px; }
            .service-category-title { align-items:flex-start; gap:12px; }
            .service-category-title-icon { width:47px; height:47px; flex-basis:47px; border-radius:14px; font-size:18px; }
            .service-category-title p { font-size:11px; }
            .service-category-mode { font-size:9px; }
            .service-category-stats { grid-template-columns:1fr; gap:9px; }
            .service-category-stat { padding:14px 16px; }
            .service-category-stat-top { justify-content:flex-start; }
            .service-category-stat-label { margin-left:auto; }
            .service-category-stat-value { margin-top:8px; font-size:24px; }
            .service-category-stat-note { margin-top:5px; }
            .service-category-panel { border-radius:19px; }
            .service-category-panel-header { padding:17px; gap:15px; }
            .service-category-search { display:grid; grid-template-columns:minmax(0,112px) minmax(0,1fr) 43px; gap:6px; }
            .service-category-select-wrap { width:auto; }
            .service-category-select,.service-category-input { height:42px; font-size:11px; }
            .service-category-button { height:42px; }
            .service-category-button-secondary { min-width:42px; padding:0 10px; }
            .service-category-button-secondary .button-text { display:none; }
            .service-category-mobile { padding:11px; }
            .service-category-mobile-card { padding:14px; }
            .service-mobile-row { grid-template-columns:84px minmax(0,1fr); gap:8px; }
            .service-category-results-bar { align-items:flex-start; flex-direction:column; }
            .service-category-footer { align-items:flex-start; flex-direction:column; }
        }
        @media (prefers-reduced-motion:reduce) {
            *,*::before,*::after { scroll-behavior:auto !important; transition:none !important; animation:none !important; }
        }
        .dark .service-category-hero,.dark .service-category-panel,.dark .service-category-stat {
            border-color:rgba(148,163,184,.14);
            background:linear-gradient(145deg,rgba(15,23,42,.94),rgba(15,23,42,.78));
            box-shadow:0 18px 50px rgba(0,0,0,.16);
        }
        .dark .service-category-title h1,.dark .service-category-panel-title h2,.dark .service-category-stat-value,.dark .category-name,.dark .service-mobile-name strong,.dark .service-category-results-text strong,.dark .service-category-empty h3 { color:#f8fafc; }
        .dark .service-category-title p,.dark .service-category-panel-title p,.dark .service-category-results-text,.dark .service-category-filter-status,.dark .service-category-empty p { color:#94a3b8; }
        .dark .service-category-select,.dark .service-category-input { color:#f8fafc; background:rgba(15,23,42,.78); border-color:rgba(148,163,184,.18); }
        .dark .service-category-select option { color:#111827; }
        .dark .service-category-table th { background:rgba(15,23,42,.62); border-color:rgba(148,163,184,.1); }
        .dark .service-category-table td { color:#cbd5e1; border-color:rgba(148,163,184,.09); }
        .dark .service-category-table tbody tr:hover { background:rgba(99,102,241,.055); }
        .dark .category-description,.dark .service-mobile-value { color:#94a3b8; }
        .dark .service-category-mobile-card { background:rgba(15,23,42,.7); border-color:rgba(148,163,184,.14); }
        .dark .service-category-button-secondary { color:#cbd5e1; background:rgba(148,163,184,.09); }
    </style>
</head>

<body
    x-data="{ page: 'ecommerce', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>

            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="service-category-page">

                    <section class="service-category-hero">
                        <div class="service-category-hero-content">
                            <div class="service-category-title">
                                <div class="service-category-title-icon"><i class="fa-solid fa-layer-group"></i></div>
                                <div>
                                    <h1>Service Category Command Center</h1>
                                    <p>Search, inspect and monitor categories configured for service operations.</p>
                                </div>
                            </div>
                            <div class="service-category-mode"><span class="service-category-mode-dot"></span>Service Mode Active</div>
                        </div>
                    </section>

                    <section class="service-category-stats" aria-label="Filtered category statistics">
                        <div class="service-category-stat">
                            <div class="service-category-stat-top">
                                <div class="service-category-stat-icon"><i class="fa-solid fa-folder-tree"></i></div>
                                <span class="service-category-stat-label">Inventory</span>
                            </div>
                            <strong class="service-category-stat-value" id="statTotal"><?= number_format($totalCategories); ?></strong>
                            <span class="service-category-stat-note" id="statTotalNote">All registered service categories</span>
                        </div>
                        <div class="service-category-stat">
                            <div class="service-category-stat-top">
                                <div class="service-category-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                                <span class="service-category-stat-label">Operational</span>
                            </div>
                            <strong class="service-category-stat-value" id="statActive"><?= number_format($activeCategories); ?></strong>
                            <span class="service-category-stat-note">Matching categories with a valid name</span>
                        </div>
                        <div class="service-category-stat">
                            <div class="service-category-stat-top">
                                <div class="service-category-stat-icon"><i class="fa-solid fa-align-left"></i></div>
                                <span class="service-category-stat-label">Documented</span>
                            </div>
                            <strong class="service-category-stat-value" id="statDescriptions"><?= number_format($totalDescriptions); ?></strong>
                            <span class="service-category-stat-note">Matching categories with descriptions</span>
                        </div>
                    </section>

                    <section class="service-category-panel">
                        <div class="service-category-panel-header">
                            <div class="service-category-panel-title">
                                <div class="service-category-panel-title-icon"><i class="fa-solid fa-database"></i></div>
                                <div>
                                    <h2>Service Category Registry</h2>
                                    <p>Every search updates the statistics and both layouts together.</p>
                                </div>
                            </div>

                            <div class="service-category-toolbar">
                                <div class="service-category-search">
                                    <div class="service-category-select-wrap">
                                        <select id="search_by" class="service-category-select" aria-label="Search field">
                                            <option value="all">All fields</option>
                                            <option value="name">Title</option>
                                            <option value="id">Tracking ID</option>
                                            <option value="description">Description</option>
                                            <option value="stock_type">Classification</option>
                                        </select>
                                    </div>
                                    <div class="service-category-input-wrap">
                                        <i class="fa-solid fa-magnifying-glass service-category-search-icon"></i>
                                        <input type="search" id="search_value" class="service-category-input" placeholder="Search service categories..." autocomplete="off" aria-label="Search service categories">
                                    </div>
                                    <button type="button" id="search_btn" class="service-category-button service-category-button-primary" aria-label="Apply search" title="Apply search">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                                <button type="button" id="reset_search" class="service-category-button service-category-button-secondary" title="Clear search and show all categories">
                                    <i class="fa-solid fa-rotate-left"></i><span class="button-text">Reset</span>
                                </button>
                            </div>
                        </div>

                        <div class="service-category-results-bar" aria-live="polite">
                            <div class="service-category-results-text" id="resultsText">
                                Showing <strong><?= number_format($totalCategories); ?></strong> of <strong><?= number_format($totalCategories); ?></strong> categories
                            </div>
                            <div class="service-category-filter-status" id="filterStatus">
                                <i class="fa-solid fa-circle-check"></i><span>Showing all categories</span>
                            </div>
                        </div>

                        <div id="categoryResults">
                            <?php if ($totalCategories > 0): ?>
                                <div class="service-category-table-wrapper" id="categoryTableWrapper">
                                    <table class="service-category-table">
                                        <thead>
                                            <tr>
                                                <th>Service Category</th>
                                                <th>Tracking ID</th>
                                                <th>Description</th>
                                                <th>Classification</th>
                                            </tr>
                                        </thead>
                                        <tbody id="categoryTableBody">
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td>
                                                    <div class="category-name-cell">
                                                        <div class="category-name-icon"><i class="fa-solid fa-tag"></i></div>
                                                        <span class="category-name"><?= service_category_escape($category['name'] ?? ''); ?></span>
                                                    </div>
                                                </td>
                                                <td><span class="category-id">#<?= service_category_escape($category['id'] ?? ''); ?></span></td>
                                                <td><div class="category-description" title="<?= service_category_escape($category['description'] ?? ''); ?>"><?= trim((string)($category['description'] ?? '')) !== '' ? service_category_escape($category['description']) : 'No description provided'; ?></div></td>
                                                <td><span class="category-type"><span class="category-type-dot"></span><?= service_category_escape($category['stock_type'] ?? 'Service Only'); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="service-category-mobile" id="categoryCards">
                                <?php foreach ($categories as $category): ?>
                                    <article class="service-category-mobile-card">
                                        <div class="service-mobile-top">
                                            <div class="service-mobile-name">
                                                <div class="service-mobile-name-icon"><i class="fa-solid fa-tag"></i></div>
                                                <div>
                                                    <strong><?= service_category_escape($category['name'] ?? ''); ?></strong>
                                                    <span class="service-mobile-id">ID #<?= service_category_escape($category['id'] ?? ''); ?></span>
                                                </div>
                                            </div>
                                            <span class="category-type"><span class="category-type-dot"></span>Service</span>
                                        </div>
                                        <div class="service-mobile-row"><span class="service-mobile-label">Description</span><span class="service-mobile-value"><?= trim((string)($category['description'] ?? '')) !== '' ? service_category_escape($category['description']) : 'No description provided'; ?></span></div>
                                        <div class="service-mobile-row"><span class="service-mobile-label">Classification</span><span class="service-mobile-value"><?= service_category_escape($category['stock_type'] ?? 'Service Only'); ?></span></div>
                                    </article>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="service-category-empty" id="initialEmptyState">
                                    <div class="service-category-empty-icon"><i class="fa-solid fa-layer-group"></i></div>
                                    <h3>No Service Categories Found</h3>
                                    <p>There are currently no categories configured for Service Only operations.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="service-category-footer">
                            <span>Dataset scope: <strong>Service Only</strong></span>
                            <span id="footerCount"><?= number_format($totalCategories); ?> records loaded</span>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const categories = <?= $categoryJson ?>;
    const searchBy = document.getElementById('search_by');
    const searchValue = document.getElementById('search_value');
    const searchButton = document.getElementById('search_btn');
    const resetButton = document.getElementById('reset_search');
    const tableBody = document.getElementById('categoryTableBody');
    const cardsContainer = document.getElementById('categoryCards');
    const resultsText = document.getElementById('resultsText');
    const filterStatus = document.getElementById('filterStatus');
    const footerCount = document.getElementById('footerCount');
    const statTotal = document.getElementById('statTotal');
    const statActive = document.getElementById('statActive');
    const statDescriptions = document.getElementById('statDescriptions');
    const categoryResults = document.getElementById('categoryResults');
    const initialCount = categories.length;

    const numberFormat = new Intl.NumberFormat();
    let searchTimer = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[char];
        });
    }

    function normalize(value) {
        return String(value ?? '').normalize('NFKC').toLocaleLowerCase().trim();
    }

    function getFieldValue(category, field) {
        if (field === 'id') return String(category.id ?? '');
        if (field === 'name') return String(category.name ?? '');
        if (field === 'description') return String(category.description ?? '');
        if (field === 'stock_type') return String(category.stock_type ?? '');
        return [
            category.name,
            category.id,
            category.description,
            category.stock_type
        ].join(' ');
    }

    function updateStats(filtered) {
        const total = filtered.length;
        const active = filtered.filter(category => String(category.name ?? '').trim() !== '').length;
        const documented = filtered.filter(category => String(category.description ?? '').trim() !== '').length;

        statTotal.textContent = numberFormat.format(total);
        statActive.textContent = numberFormat.format(active);
        statDescriptions.textContent = numberFormat.format(documented);

        resultsText.innerHTML = 'Showing <strong>' + numberFormat.format(total) +
            '</strong> of <strong>' + numberFormat.format(initialCount) + '</strong> categories';

        footerCount.textContent = numberFormat.format(total) + ' of ' +
            numberFormat.format(initialCount) + ' records shown';

        const hasFilter = searchValue.value.trim() !== '' || searchBy.value !== 'all';
        filterStatus.innerHTML = hasFilter
            ? '<i class="fa-solid fa-filter"></i><span>Filtered results</span>'
            : '<i class="fa-solid fa-circle-check"></i><span>Showing all categories</span>';
    }

    function desktopEmptyMarkup() {
        return '<tr><td colspan="4"><div class="service-category-empty">' +
            '<div class="service-category-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>' +
            '<h3>No matching categories</h3><p>Try another keyword or reset the search to see all service categories.</p>' +
            '<button type="button" class="service-category-button service-category-button-secondary service-category-empty-action js-empty-reset">' +
            '<i class="fa-solid fa-rotate-left"></i> Clear search</button></div></td></tr>';
    }

    function mobileEmptyMarkup() {
        return '<div class="service-category-empty">' +
            '<div class="service-category-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>' +
            '<h3>No matching categories</h3><p>Try another keyword or reset the search to see all service categories.</p>' +
            '<button type="button" class="service-category-button service-category-button-secondary service-category-empty-action js-empty-reset">' +
            '<i class="fa-solid fa-rotate-left"></i> Clear search</button></div>';
    }

    function renderDesktop(filtered) {
        if (!tableBody) return;

        if (!filtered.length) {
            tableBody.innerHTML = desktopEmptyMarkup();
            return;
        }

        tableBody.innerHTML = filtered.map(category => {
            const name = escapeHtml(category.name || '');
            const id = escapeHtml(category.id ?? '');
            const rawDescription = String(category.description ?? '').trim();
            const description = escapeHtml(rawDescription || 'No description provided');
            const stockType = escapeHtml(category.stock_type || 'Service Only');

            return '<tr>' +
                '<td><div class="category-name-cell"><div class="category-name-icon"><i class="fa-solid fa-tag"></i></div><span class="category-name">' + name + '</span></div></td>' +
                '<td><span class="category-id">#' + id + '</span></td>' +
                '<td><div class="category-description" title="' + escapeHtml(rawDescription) + '">' + description + '</div></td>' +
                '<td><span class="category-type"><span class="category-type-dot"></span>' + stockType + '</span></td>' +
                '</tr>';
        }).join('');
    }

    function renderMobile(filtered) {
        if (!cardsContainer) return;

        if (!filtered.length) {
            cardsContainer.innerHTML = mobileEmptyMarkup();
            return;
        }

        cardsContainer.innerHTML = filtered.map(category => {
            const name = escapeHtml(category.name || '');
            const id = escapeHtml(category.id ?? '');
            const rawDescription = String(category.description ?? '').trim();
            const description = escapeHtml(rawDescription || 'No description provided');
            const stockType = escapeHtml(category.stock_type || 'Service Only');

            return '<article class="service-category-mobile-card">' +
                '<div class="service-mobile-top">' +
                    '<div class="service-mobile-name"><div class="service-mobile-name-icon"><i class="fa-solid fa-tag"></i></div>' +
                    '<div><strong>' + name + '</strong><span class="service-mobile-id">ID #' + id + '</span></div></div>' +
                    '<span class="category-type"><span class="category-type-dot"></span>Service</span>' +
                '</div>' +
                '<div class="service-mobile-row"><span class="service-mobile-label">Description</span><span class="service-mobile-value">' + description + '</span></div>' +
                '<div class="service-mobile-row"><span class="service-mobile-label">Classification</span><span class="service-mobile-value">' + stockType + '</span></div>' +
                '</article>';
        }).join('');
    }

    function renderNoDataset() {
        if (categoryResults) {
            categoryResults.innerHTML = '<div class="service-category-empty">' +
                '<div class="service-category-empty-icon"><i class="fa-solid fa-layer-group"></i></div>' +
                '<h3>No Service Categories Found</h3><p>There are currently no categories configured for Service Only operations.</p></div>';
        }
    }

    function performSearch() {
        const field = searchBy.value;
        const keyword = normalize(searchValue.value);
        const filtered = categories.filter(category => {
            if (!keyword) return true;
            return normalize(getFieldValue(category, field)).includes(keyword);
        });

        updateStats(filtered);

        if (initialCount === 0) {
            renderNoDataset();
            return;
        }

        renderDesktop(filtered);
        renderMobile(filtered);
    }

    function setLoading(isLoading) {
        if (!searchButton) return;
        searchButton.disabled = isLoading;
        searchButton.innerHTML = isLoading
            ? '<i class="fa-solid fa-spinner fa-spin"></i>'
            : '<i class="fa-solid fa-magnifying-glass"></i>';
    }

    function runSearch() {
        setLoading(true);
        // Keep rendering synchronous and predictable; restore button immediately after both views and KPIs update.
        try {
            performSearch();
        } finally {
            setLoading(false);
        }
    }

    searchButton?.addEventListener('click', runSearch);
    searchValue?.addEventListener('input', function () {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(runSearch, 160);
    });
    searchValue?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            runSearch();
        }
        if (event.key === 'Escape') {
            searchValue.value = '';
            searchBy.value = 'all';
            window.clearTimeout(searchTimer);
            runSearch();
        }
    });
    searchBy?.addEventListener('change', runSearch);
    resetButton?.addEventListener('click', function () {
        searchValue.value = '';
        searchBy.value = 'all';
        window.clearTimeout(searchTimer);
        runSearch();
        searchValue.focus();
    });

    categoryResults?.addEventListener('click', function (event) {
        if (event.target.closest('.js-empty-reset')) {
            searchValue.value = '';
            searchBy.value = 'all';
            runSearch();
        }
    });

    // Normalize the initial dashboard state and ensure all visible figures share one dataset.
    if (initialCount === 0) {
        statTotal.textContent = '0';
        statActive.textContent = '0';
        statDescriptions.textContent = '0';
        resultsText.innerHTML = 'Showing <strong>0</strong> of <strong>0</strong> categories';
        footerCount.textContent = '0 records loaded';
    } else {
        performSearch();
    }
});
</script>
</body>
</html>
