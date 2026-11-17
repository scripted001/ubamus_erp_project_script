<?php
declare(strict_types=1);

/**
 * BRANCH MANAGER — RESTOCK TERMINAL
 *
 * Branch scope is resolved from the authenticated users database record.
 * The browser never chooses or submits a company/location identity.
 * The matching processor is process_restock.php and requires the
 * restock_csrf_token field generated below.
 */

date_default_timezone_set('Africa/Lagos');


require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/validation.php';
require_once __DIR__ . '/user_data.php';
require_once __DIR__ . '/../company_data.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    exit('The database connection is unavailable.');
}

/* --------------------------------------------------------------------------
   Authenticate the active Branch Manager from the database
--------------------------------------------------------------------------- */
$authenticatedUserId = (int)($_SESSION['user_id'] ?? ($user_id ?? 0));
if ($authenticatedUserId <= 0) {
    header('Location: ../login.php');
    exit;
}

$userStmt = $pdo->prepare('SELECT id, role, status, company_id, location_id FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$authenticatedUserId]);
$branchUser = $userStmt->fetch(PDO::FETCH_ASSOC);

$role = strtolower(trim((string)($branchUser['role'] ?? '')));
$status = strtolower(trim((string)($branchUser['status'] ?? '')));
if (!$branchUser || $role !== 'branch manager' || !in_array($status, ['active', '1', 'enabled'], true)) {
    http_response_code(403);
    exit('Access denied. This page is restricted to active Branch Managers.');
}

$company_id = (int)$branchUser['company_id'];
$location_id = (int)$branchUser['location_id'];
if ($company_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Your account does not have a valid assigned branch. Please contact your administrator.');
}

$locationStmt = $pdo->prepare('SELECT id, name FROM locations WHERE id = ? AND company_id = ? LIMIT 1');
$locationStmt->execute([$location_id, $company_id]);
$assignedLocation = $locationStmt->fetch(PDO::FETCH_ASSOC);
if (!$assignedLocation) {
    http_response_code(403);
    exit('Your assigned branch could not be verified.');
}
$branchName = (string)$assignedLocation['name'];

/* --------------------------------------------------------------------------
   CSRF token shared with process_restock.php
--------------------------------------------------------------------------- */
if (empty($_SESSION['restock_csrf_token']) || !is_string($_SESSION['restock_csrf_token'])) {
    $_SESSION['restock_csrf_token'] = bin2hex(random_bytes(32));
}
$restockCsrfToken = $_SESSION['restock_csrf_token'];

/* --------------------------------------------------------------------------
   Load only a stock record belonging to this exact company and branch
--------------------------------------------------------------------------- */
$stockIdRaw = $_POST['stock_id'] ?? $_GET['stock_id'] ?? null;
$stock_id = filter_var($stockIdRaw, FILTER_VALIDATE_INT);
if ($stock_id === false || $stock_id === null || $stock_id <= 0) {
    http_response_code(400);
    exit('Invalid stock selected. Return to your stock list and select a valid item.');
}

$stockStmt = $pdo->prepare("
    SELECT
        s.id AS stock_id,
        s.product_id,
        s.company_id,
        s.location_id,
        s.quantity,
        s.supplier,
        p.name AS product_name,
        p.cost_price,
        COALESCE(NULLIF(p.identifier_tracking, ''), 'none') AS identifier_tracking,
        COALESCE(p.imei_tracking, 0) AS imei_tracking,
        LOWER(TRIM(COALESCE(pc.stock_type, ''))) AS product_stock_type
    FROM stocks s
    INNER JOIN products p
        ON p.id = s.product_id
       AND p.company_id = s.company_id
    LEFT JOIN product_categories pc
        ON pc.id = p.category_id
    WHERE s.id = ?
      AND s.company_id = ?
      AND s.location_id = ?
    LIMIT 1
");
$stockStmt->execute([(int)$stock_id, $company_id, $location_id]);
$stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

if (!$stock) {
    http_response_code(404);
    exit('Stock record not found or you do not have access to this branch stock.');
}

$product_id = (int)$stock['product_id']; // Display/context only; never submitted as authority.
$product_name = (string)$stock['product_name'];
$current_cost_price = (float)$stock['cost_price'];
$current_stock_quantity = (int)$stock['quantity'];

$tracking_type = strtolower(trim((string)$stock['identifier_tracking']));
if (!in_array($tracking_type, ['none', 'imei', 'serial', 'vin'], true)) {
    $tracking_type = 'none';
}
if ($tracking_type === 'none' && (int)$stock['imei_tracking'] === 1) {
    $tracking_type = 'imei'; // Legacy product compatibility.
}
if ($tracking_type !== 'none' && strtolower(trim((string)$stock['product_stock_type'])) !== 'sales only') {
    $tracking_type = 'none';
}
$tracking_label = [
    'imei' => 'IMEI',
    'serial' => 'Serial Number',
    'vin' => 'VIN',
][$tracking_type] ?? '';

$companyDisplayName = (string)($company_name ?? 'Company');
$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $e($companyDisplayName) ?> | Restock Terminal</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root{--rp:#6839eb;--rp-dark:#5227c9;--blue:#2563eb;--green:#059669;--orange:#d97706;--ink:#172033;--heading:#0f172a;--muted:#64748b;--line:rgba(148,163,184,.24);--radius:24px;--shadow:0 22px 65px rgba(15,23,42,.09)}
        .restock-page{width:100%;min-height:100%;padding:8px 0 40px;position:relative}
        .restock-page:before,.restock-page:after{content:"";position:fixed;border-radius:50%;pointer-events:none;z-index:0}
        .restock-page:before{width:420px;height:420px;top:80px;right:-180px;background:radial-gradient(circle,rgba(104,57,235,.10),transparent 68%)}
        .restock-page:after{width:360px;height:360px;bottom:-160px;left:-140px;background:radial-gradient(circle,rgba(37,99,235,.07),transparent 68%)}
        .restock-terminal{width:100%;max-width:1450px;margin:0 auto;position:relative;z-index:2;overflow:hidden;border-radius:var(--radius);border:1px solid rgba(255,255,255,.9);background:linear-gradient(145deg,rgba(255,255,255,.91),rgba(248,250,252,.82));backdrop-filter:blur(25px);-webkit-backdrop-filter:blur(25px);box-shadow:var(--shadow)}
        .restock-terminal:before{content:"";position:absolute;width:340px;height:340px;top:-190px;right:-110px;border-radius:50%;background:radial-gradient(circle,rgba(104,57,235,.12),transparent 70%);pointer-events:none}
        .restock-header,.restock-breadcrumb,.restock-content{position:relative;z-index:1}
        .restock-header{padding:27px 30px 23px;border-bottom:1px solid rgba(148,163,184,.15)}
        .restock-header-row{display:flex;align-items:center;justify-content:space-between;gap:22px}
        .restock-heading{display:flex;align-items:center;gap:15px;min-width:0}
        .restock-heading-icon{width:56px;height:56px;flex:0 0 56px;display:grid;place-items:center;border-radius:17px;color:var(--rp);background:linear-gradient(145deg,rgba(104,57,235,.15),rgba(104,57,235,.045));border:1px solid rgba(104,57,235,.15);box-shadow:0 12px 30px rgba(104,57,235,.1);font-size:22px}
        .restock-title{margin:0;color:var(--heading);font-size:clamp(1.35rem,2.3vw,1.9rem);line-height:1.15;font-weight:850;letter-spacing:-.045em}
        .restock-subtitle{margin:6px 0 0;color:var(--muted);font-size:.82rem;line-height:1.5}
        .branch-line{margin-top:8px;color:#475569;font-size:.76rem}.branch-line strong{color:var(--rp);font-weight:800}
        .restock-system-status{display:inline-flex;align-items:center;gap:9px;padding:10px 14px;border-radius:999px;background:rgba(5,150,105,.07);border:1px solid rgba(5,150,105,.14);color:#047857;font-size:.72rem;font-weight:800;white-space:nowrap}
        .restock-status-dot{width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 5px rgba(16,185,129,.09)}
        .restock-breadcrumb{padding:13px 30px;background:rgba(248,250,252,.58);border-bottom:1px solid rgba(148,163,184,.12)}
        .restock-breadcrumb ol{display:flex;align-items:center;flex-wrap:wrap;gap:9px;margin:0;padding:0;list-style:none}
        .restock-breadcrumb a{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:.76rem}.restock-breadcrumb a:hover{color:var(--rp)}
        .restock-breadcrumb-current{color:#1e293b;font-size:.76rem;font-weight:750}
        .restock-content{padding:29px}
        .product-hero{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(220px,.7fr);gap:18px;margin-bottom:20px}
        .product-card,.cost-card{position:relative;overflow:hidden;padding:22px;border-radius:20px;border:1px solid rgba(148,163,184,.2);box-shadow:0 10px 30px rgba(15,23,42,.035)}
        .product-card{background:linear-gradient(135deg,rgba(104,57,235,.085),rgba(255,255,255,.75));border-color:rgba(104,57,235,.13)}
        .product-card:after{content:"";position:absolute;width:170px;height:170px;right:-90px;top:-90px;border-radius:50%;background:rgba(104,57,235,.07)}
        .product-card-top{display:flex;align-items:center;gap:14px}.product-icon{width:48px;height:48px;flex:0 0 48px;display:grid;place-items:center;border-radius:15px;background:rgba(255,255,255,.75);border:1px solid rgba(104,57,235,.12);color:var(--rp);font-size:19px}
        .product-label,.stock-stat-label,.cost-label,.projection-label{color:var(--muted);font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
        .product-label{margin:0 0 4px}.product-name{margin:0;color:var(--heading);font-size:clamp(1.05rem,2vw,1.35rem);font-weight:850;line-height:1.25;overflow-wrap:anywhere}
        .cost-card{display:flex;flex-direction:column;justify-content:center;background:rgba(255,255,255,.72)}.cost-label{display:flex;align-items:center;gap:7px}.cost-label i{color:var(--rp)}.cost-value{margin-top:7px;color:var(--heading);font-size:clamp(1.25rem,2.5vw,1.65rem);font-weight:850;letter-spacing:-.035em}
        .stock-flow{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:23px}
        .stock-stat{min-width:0;min-height:130px;padding:18px;border-radius:18px;background:rgba(255,255,255,.75);border:1px solid rgba(148,163,184,.2);box-shadow:0 9px 25px rgba(15,23,42,.035);transition:transform .2s,box-shadow .2s}
        .stock-stat:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(15,23,42,.07)}.stock-stat-icon{width:35px;height:35px;display:grid;place-items:center;border-radius:11px;margin-bottom:12px;font-size:13px}
        .stock-stat.current .stock-stat-icon{background:rgba(37,99,235,.09);color:#2563eb}.stock-stat.incoming .stock-stat-icon{background:rgba(245,158,11,.1);color:#d97706}.stock-stat.projected .stock-stat-icon{background:rgba(5,150,105,.1);color:#059669}
        .stock-stat-value{margin-top:5px;color:var(--heading);font-size:1.35rem;font-weight:850;letter-spacing:-.035em}.stock-stat-description{margin-top:4px;color:#94a3b8;font-size:.67rem}
        .restock-form-panel{padding:25px;border-radius:22px;background:rgba(255,255,255,.75);border:1px solid rgba(148,163,184,.2);box-shadow:0 14px 38px rgba(15,23,42,.04)}
        .form-panel-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding-bottom:19px;margin-bottom:21px;border-bottom:1px solid rgba(148,163,184,.16)}
        .form-panel-title{display:flex;align-items:center;gap:11px;margin:0;color:var(--heading);font-size:1rem;font-weight:850}.form-panel-title-icon{width:34px;height:34px;display:grid;place-items:center;border-radius:10px;color:var(--rp);background:rgba(104,57,235,.1);font-size:13px}
        .form-panel-caption{margin:5px 0 0;color:var(--muted);font-size:.72rem;line-height:1.55}.secure-badge{display:inline-flex;align-items:center;gap:6px;padding:8px 10px;border-radius:9px;background:rgba(5,150,105,.07);color:#047857;font-size:.64rem;font-weight:800;white-space:nowrap}
        .restock-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:19px}.restock-field{min-width:0}.restock-field.full{grid-column:1/-1}
        .restock-label{display:flex;align-items:center;gap:7px;margin-bottom:8px;color:#334155;font-size:.75rem;font-weight:800}.restock-label i{width:15px;color:var(--rp);text-align:center;font-size:11px}
        .restock-control{width:100%;min-height:48px;padding:11px 13px;border-radius:12px;border:1px solid rgba(148,163,184,.32);background:rgba(255,255,255,.85);color:var(--ink);outline:none;font-family:inherit;font-size:.83rem;font-weight:550;transition:border-color .2s,box-shadow .2s,background .2s;box-sizing:border-box}
        .restock-control::placeholder{color:#94a3b8}.restock-control:hover{border-color:rgba(104,57,235,.28)}.restock-control:focus{border-color:rgba(104,57,235,.6);background:#fff;box-shadow:0 0 0 4px rgba(104,57,235,.08)}.restock-control.readonly{background:rgba(241,245,249,.75);color:#334155;cursor:not-allowed}.restock-control.stock-input{font-weight:850;color:#1d4ed8}
        .quantity-wrapper{position:relative}.quantity-wrapper .restock-control{padding-right:55px;font-size:1rem;font-weight:850}.quantity-suffix{position:absolute;top:50%;right:14px;transform:translateY(-50%);color:#94a3b8;font-size:.67rem;font-weight:800;pointer-events:none}
        .stock-projection{margin-top:2px;padding:16px;border-radius:15px;background:linear-gradient(135deg,rgba(5,150,105,.07),rgba(255,255,255,.7));border:1px solid rgba(5,150,105,.13)}.projection-row{display:flex;align-items:center;justify-content:space-between;gap:13px}.projection-side{min-width:0}.projection-label{display:block;margin-bottom:4px;font-size:.62rem}.projection-number{color:#047857;font-size:1.2rem;font-weight:850}.projection-arrow{width:33px;height:33px;flex:0 0 33px;display:grid;place-items:center;border-radius:50%;background:rgba(5,150,105,.09);color:#059669;font-size:11px}
        .restock-actions{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:22px;padding-top:20px;border-top:1px solid rgba(148,163,184,.16)}.action-note{display:flex;align-items:center;gap:8px;color:#94a3b8;font-size:.68rem;line-height:1.45}.action-note i{color:var(--rp)}.action-buttons{display:flex;align-items:center;gap:10px}
        .restock-btn{min-height:45px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 18px;border:1px solid transparent;border-radius:11px;font-size:.75rem;font-weight:850;text-decoration:none;cursor:pointer;transition:transform .2s,box-shadow .2s}.restock-btn:hover{transform:translateY(-2px)}.restock-btn-primary{color:#fff;background:linear-gradient(135deg,#6839eb,#5427c8);box-shadow:0 10px 25px rgba(104,57,235,.2)}.restock-btn-primary:hover{box-shadow:0 15px 30px rgba(104,57,235,.28)}.restock-btn-secondary{color:#475569;background:rgba(255,255,255,.85);border-color:rgba(148,163,184,.3)}
        .upload-help{margin-top:7px;color:#94a3b8;font-size:.68rem;line-height:1.5}.identifier-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin-top:13px}.identifier-error{display:none;margin-top:10px;color:#dc2626;font-size:.78rem}.restock-btn:focus-visible,.restock-control:focus-visible{outline:3px solid rgba(104,57,235,.2);outline-offset:2px}
        @media(max-width:1000px){.restock-content{padding:23px}.restock-header,.restock-breadcrumb{padding-left:23px;padding-right:23px}}
        @media(max-width:768px){.restock-page{padding:5px 0 25px}.restock-terminal{border-radius:18px}.restock-header{padding:19px 16px}.restock-header-row{align-items:flex-start}.restock-heading{gap:11px}.restock-heading-icon{width:45px;height:45px;flex-basis:45px;border-radius:13px;font-size:17px}.restock-title{font-size:1.17rem}.restock-subtitle{font-size:.7rem}.restock-system-status{display:none}.restock-breadcrumb{padding:10px 16px}.restock-content{padding:17px 13px 21px}.product-hero{grid-template-columns:1fr;gap:11px;margin-bottom:14px}.product-card,.cost-card{padding:16px}.stock-flow{grid-template-columns:1fr;gap:9px;margin-bottom:14px}.stock-stat{min-height:0;padding:14px;display:grid;grid-template-columns:38px 1fr;column-gap:11px}.stock-stat-icon{grid-row:span 2;margin:0;width:37px;height:37px}.stock-stat-label{align-self:end}.stock-stat-value{margin-top:2px}.stock-stat-description{grid-column:2;margin-top:1px}.restock-form-panel{padding:17px 14px}.form-panel-heading{flex-direction:column;gap:10px;padding-bottom:15px;margin-bottom:17px}.secure-badge{align-self:flex-start}.restock-form-grid{grid-template-columns:1fr;gap:16px}.restock-field.full{grid-column:auto}.identifier-grid{grid-template-columns:1fr}.restock-actions{flex-direction:column;align-items:stretch;gap:13px}.action-note{justify-content:center;text-align:center}.action-buttons{width:100%;display:grid;grid-template-columns:1fr 1fr}.restock-btn{width:100%}}
        @media(max-width:480px){.restock-header{padding:16px 12px}.restock-breadcrumb{padding:9px 12px}.restock-content{padding:13px 10px 18px}.restock-heading-icon{width:41px;height:41px;flex-basis:41px}.restock-title{font-size:1.06rem}.restock-subtitle{font-size:.66rem}.product-card,.cost-card{border-radius:15px;padding:14px}.product-name{font-size:1rem}.stock-stat{border-radius:14px}.restock-form-panel{border-radius:16px;padding:14px 11px}.restock-control{min-height:45px;padding:10px 11px;font-size:.79rem}.restock-label{font-size:.7rem}.projection-row{gap:7px}.projection-number{font-size:1rem}.projection-arrow{width:28px;height:28px;flex-basis:28px}.action-buttons{grid-template-columns:1fr}.restock-btn{min-height:44px}}
        .dark .restock-terminal{background:rgba(17,24,39,.9);border-color:rgba(255,255,255,.06)}.dark .restock-title,.dark .product-name,.dark .cost-value,.dark .stock-stat-value,.dark .form-panel-title{color:#f8fafc}.dark .restock-subtitle,.dark .branch-line{color:#94a3b8}.dark .branch-line strong{color:#c4b5fd}.dark .restock-breadcrumb{background:rgba(15,23,42,.5);border-color:rgba(255,255,255,.05)}.dark .restock-breadcrumb-current{color:#e2e8f0}.dark .product-card{background:linear-gradient(135deg,rgba(104,57,235,.15),rgba(15,23,42,.5));border-color:rgba(104,57,235,.2)}.dark .cost-card,.dark .stock-stat,.dark .restock-form-panel{background:rgba(15,23,42,.72);border-color:rgba(71,85,105,.5)}.dark .restock-label{color:#cbd5e1}.dark .restock-control{background:rgba(15,23,42,.8);border-color:rgba(71,85,105,.65);color:#f8fafc}.dark .restock-control.readonly{background:rgba(30,41,59,.7);color:#e2e8f0}.dark .restock-control:focus{background:rgba(15,23,42,.96)}.dark .restock-btn-secondary{background:rgba(15,23,42,.75);color:#cbd5e1;border-color:rgba(71,85,105,.6)}.dark .stock-projection{background:linear-gradient(135deg,rgba(5,150,105,.13),rgba(15,23,42,.5))}
    </style>
</head>
<body
    x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') ?? 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include __DIR__ . '/header.php'; ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="restock-page">
                    <section class="restock-terminal">
                        <header class="restock-header">
                            <div class="restock-header-row">
                                <div class="restock-heading">
                                    <div class="restock-heading-icon"><i class="fas fa-boxes-stacked"></i></div>
                                    <div>
                                        <h1 class="restock-title">Restock Terminal</h1>
                                        <p class="restock-subtitle">Replenish inventory and synchronize your stock levels in real time.</p>
                                        <p class="branch-line"><i class="fas fa-location-dot"></i> Assigned Branch: <strong><?= $e($branchName) ?></strong></p>
                                    </div>
                                </div>
                                <div class="restock-system-status"><span class="restock-status-dot"></span> Inventory System Active</div>
                            </div>
                        </header>

                        <div class="restock-breadcrumb">
                            <nav aria-label="Breadcrumb"><ol>
                                <li><a href="index.php"><i class="fas fa-house"></i> Home</a></li>
                                <li><i class="fas fa-chevron-right" style="font-size:8px;color:#94a3b8"></i></li>
                                <li class="restock-breadcrumb-current">Restock Inventory</li>
                            </ol></nav>
                        </div>

                        <div class="restock-content">
                            <div class="product-hero">
                                <div class="product-card"><div class="product-card-top">
                                    <div class="product-icon"><i class="fas fa-box-open"></i></div>
                                    <div><p class="product-label">Target Product</p><h2 class="product-name"><?= $e($product_name) ?></h2></div>
                                </div></div>
                                <div class="cost-card">
                                    <div class="cost-label"><i class="fas fa-naira-sign"></i> Current Unit Cost</div>
                                    <div class="cost-value">₦<?= number_format($current_cost_price, 2) ?></div>
                                </div>
                            </div>

                            <div class="stock-flow">
                                <div class="stock-stat current"><div class="stock-stat-icon"><i class="fas fa-layer-group"></i></div><div class="stock-stat-label">Current Stock</div><div class="stock-stat-value" id="currentStockDisplay"><?= number_format($current_stock_quantity) ?></div><div class="stock-stat-description">Available before restock</div></div>
                                <div class="stock-stat incoming"><div class="stock-stat-icon"><i class="fas fa-arrow-trend-up"></i></div><div class="stock-stat-label">Incoming Quantity</div><div class="stock-stat-value" id="incomingDisplay">0</div><div class="stock-stat-description">Units being added</div></div>
                                <div class="stock-stat projected"><div class="stock-stat-icon"><i class="fas fa-chart-line"></i></div><div class="stock-stat-label">Projected Stock</div><div class="stock-stat-value" id="projectedDisplay"><?= number_format($current_stock_quantity) ?></div><div class="stock-stat-description">Expected inventory after restock</div></div>
                            </div>

                            <div class="restock-form-panel">
                                <div class="form-panel-heading">
                                    <div><h3 class="form-panel-title"><span class="form-panel-title-icon"><i class="fas fa-sliders"></i></span> Inventory Adjustment</h3><p class="form-panel-caption">Enter the quantity you are receiving into the selected stock record.</p></div>
                                    <div class="secure-badge"><i class="fas fa-shield-halved"></i> Secure Inventory Action</div>
                                </div>

                                <form id="restockForm" method="POST" action="process_restock.php" enctype="multipart/form-data">
                                    <input type="hidden" name="stock_id" value="<?= (int)$stock_id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $e($restockCsrfToken) ?>">

                                    <div class="restock-form-grid">
                                        <div class="restock-field">
                                            <label class="restock-label"><i class="fas fa-box"></i> Product Name</label>
                                            <input type="text" value="<?= $e($product_name) ?>" readonly class="restock-control readonly">
                                        </div>
                                        <div class="restock-field">
                                            <label class="restock-label"><i class="fas fa-money-bill-wave"></i> Current Unit Cost</label>
                                            <input type="text" value="₦<?= number_format($current_cost_price, 2) ?>" readonly class="restock-control readonly">
                                        </div>
                                        <div class="restock-field">
                                            <label class="restock-label" for="current_stock"><i class="fas fa-cubes"></i> Current Stock Quantity</label>
                                            <input type="text" id="current_stock" value="<?= number_format($current_stock_quantity) ?>" readonly class="restock-control readonly stock-input">
                                        </div>
                                        <div class="restock-field">
                                            <label class="restock-label" for="add_quantity"><i class="fas fa-plus-circle"></i> Add Quantity</label>
                                            <div class="quantity-wrapper">
                                                <input type="number" id="add_quantity" name="add_quantity" min="1" max="500" step="1" inputmode="numeric" placeholder="Enter quantity to add" class="restock-control" required>
                                                <span class="quantity-suffix">UNITS</span>
                                            </div>
                                            <p class="form-panel-caption">Enter a whole number between 1 and 500.</p>
                                        </div>

                                        <?php if ($tracking_type !== 'none'): ?>
                                        <div class="restock-field full" id="restockIdentifiersPanel">
                                            <label class="restock-label" for="restock_identifier_1"><i class="fas fa-barcode"></i> <?= $e($tracking_label) ?> values for incoming units</label>
                                            <p class="form-panel-caption">Enter one unique <?= $e($tracking_label) ?> for every unit being added. The number of fields will match the restock quantity.</p>
                                            <div id="restockIdentifiers" class="identifier-grid"><p class="form-panel-caption">Enter the quantity above to generate identifier fields.</p></div>
                                            <div id="restockIdentifierError" class="identifier-error" role="alert"></div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="restock-field full">
                                            <div class="stock-projection"><div class="projection-row">
                                                <div class="projection-side"><span class="projection-label">Current</span><span class="projection-number" id="projectionCurrent"><?= number_format($current_stock_quantity) ?></span></div>
                                                <div class="projection-arrow"><i class="fas fa-arrow-right"></i></div>
                                                <div class="projection-side"><span class="projection-label">Incoming</span><span class="projection-number" id="projectionIncoming">+0</span></div>
                                                <div class="projection-arrow"><i class="fas fa-equals"></i></div>
                                                <div class="projection-side"><span class="projection-label">After Restock</span><span class="projection-number" id="projectionTotal"><?= number_format($current_stock_quantity) ?></span></div>
                                            </div></div>
                                        </div>

                                        <div class="restock-field full">
                                            <label class="restock-label" for="stock_invoice"><i class="fas fa-file-invoice"></i> Stock Invoice <span style="color:#94a3b8;font-size:11px;font-weight:500">(Optional)</span></label>
                                            <input type="file" name="stock_invoice" id="stock_invoice" class="restock-control" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                            <p class="upload-help">Attach the supplier's invoice for this stock entry. PDF, JPG or PNG; maximum 20 MB.</p>
                                        </div>

                                        <div class="restock-field full">
                                            <div class="restock-actions">
                                                <div class="action-note"><i class="fas fa-circle-info"></i> Stock quantity will be updated after submission.</div>
                                                <div class="action-buttons">
                                                    <a href="stocks.php" class="restock-btn restock-btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                                                    <button type="submit" id="restockSubmit" class="restock-btn restock-btn-primary"><i class="fas fa-boxes-stacked"></i> Restock Inventory</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
<script defer src="bundle.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentStockInput = document.getElementById('current_stock');
    const addQuantityInput = document.getElementById('add_quantity');
    const currentStockDisplay = document.getElementById('currentStockDisplay');
    const incomingDisplay = document.getElementById('incomingDisplay');
    const projectedDisplay = document.getElementById('projectedDisplay');
    const projectionCurrent = document.getElementById('projectionCurrent');
    const projectionIncoming = document.getElementById('projectionIncoming');
    const projectionTotal = document.getElementById('projectionTotal');
    const originalStock = <?= (int)$current_stock_quantity ?>;
    const trackingType = <?= json_encode($tracking_type, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const trackingLabel = <?= json_encode($tracking_label, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const identifierContainer = document.getElementById('restockIdentifiers');
    const identifierError = document.getElementById('restockIdentifierError');
    const restockForm = document.getElementById('restockForm');
    const submitButton = document.getElementById('restockSubmit');

    const formatNumber = number => Number(number).toLocaleString();

    function updateStockProjection() {
        const raw = Number.parseInt(addQuantityInput.value, 10);
        const addQuantity = Number.isFinite(raw) && raw > 0 ? raw : 0;
        const projectedStock = originalStock + addQuantity;

        // Current stock remains the original database quantity; never show the projection here.
        currentStockInput.value = formatNumber(originalStock);
        currentStockDisplay.textContent = formatNumber(originalStock);
        incomingDisplay.textContent = formatNumber(addQuantity);
        projectedDisplay.textContent = formatNumber(projectedStock);
        projectionCurrent.textContent = formatNumber(originalStock);
        projectionIncoming.textContent = '+' + formatNumber(addQuantity);
        projectionTotal.textContent = formatNumber(projectedStock);
    }

    function buildIdentifierFields() {
        if (!identifierContainer || trackingType === 'none') return;
        const parsed = Number.parseInt(addQuantityInput.value, 10);
        const quantity = Number.isFinite(parsed) ? Math.max(0, Math.min(500, parsed)) : 0;
        identifierContainer.replaceChildren();

        if (quantity === 0) {
            const note = document.createElement('p');
            note.className = 'form-panel-caption';
            note.textContent = 'Enter the quantity above to generate identifier fields.';
            identifierContainer.appendChild(note);
            return;
        }

        for (let i = 0; i < quantity; i++) {
            const field = document.createElement('div');
            field.className = 'restock-field';
            const label = document.createElement('label');
            label.className = 'restock-label';
            label.htmlFor = 'restock_identifier_' + (i + 1);
            label.textContent = trackingLabel + ' #' + (i + 1);
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'identifiers[]';
            input.id = 'restock_identifier_' + (i + 1);
            input.className = 'restock-control';
            input.required = true;
            input.autocomplete = 'off';
            input.maxLength = trackingType === 'vin' ? 17 : (trackingType === 'imei' ? 15 : 100);
            input.placeholder = trackingType === 'vin' ? '17-character VIN' : (trackingType === 'imei' ? '15-digit IMEI' : 'Enter serial number');

            if (trackingType === 'vin') {
                input.minLength = 17;
                input.pattern = '[A-HJ-NPR-Z0-9]{17}';
                input.style.textTransform = 'uppercase';
                input.addEventListener('input', () => {
                    input.value = input.value.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, '').slice(0, 17);
                });
            } else if (trackingType === 'imei') {
                input.minLength = 15;
                input.inputMode = 'numeric';
                input.pattern = '[0-9]{15}';
            }
            field.append(label, input);
            identifierContainer.appendChild(field);
        }
    }

    addQuantityInput.addEventListener('input', updateStockProjection);
    addQuantityInput.addEventListener('input', buildIdentifierFields);

    restockForm.addEventListener('submit', function (event) {
        const quantity = Number(addQuantityInput.value);
        if (!Number.isInteger(quantity) || quantity < 1 || quantity > 500) {
            event.preventDefault();
            alert('Please enter a whole quantity between 1 and 500.');
            addQuantityInput.focus();
            return;
        }

        if (identifierContainer) {
            const inputs = identifierContainer.querySelectorAll('input[name="identifiers[]"]');
            const values = Array.from(inputs, input => input.value.trim());
            const normalized = values.map(value => value.toUpperCase());
            if (values.length !== quantity || values.some(value => !value)) {
                event.preventDefault();
                identifierError.textContent = 'Enter one identifier for each unit being restocked.';
                identifierError.style.display = 'block';
                return;
            }
            if (new Set(normalized).size !== normalized.length) {
                event.preventDefault();
                identifierError.textContent = 'Duplicate identifiers were entered. Each unit must have a different identifier.';
                identifierError.style.display = 'block';
                return;
            }
            identifierError.style.display = 'none';
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Restock...';
        }
    });

    updateStockProjection();
});
</script>
</body>
</html>
