<?php
require_once('../config/db.php');
require_once('../config/validation.php');
require_once('user_data.php');
require_once('../company_data.php');

if (session_status() === PHP_SESSION_NONE) {
    // The included authentication files normally start the session.
    session_start();
}

$company_id = (int)($_SESSION['company_id'] ?? 0);
if ($company_id <= 0) {
    echo "<script>alert('Invalid company session.'); window.location.href='index.php';</script>";
    exit;
}

$stock_id = (int)($_POST['stock_id'] ?? 0);
if ($stock_id <= 0) {
    echo "<script>alert('No stock selected for transfer.'); window.history.back();</script>";
    exit;
}

function transfer_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function transfer_money($value): float {
    if ($value === null || $value === '') return 0.0;
    return is_numeric($value) ? (float)$value : (float)preg_replace('/[^0-9.\-]/', '', (string)$value);
}

// Confirm that this stock row and its product belong to the signed-in company.
$stmt = $pdo->prepare("\n    SELECT s.id AS stock_id, s.product_id, s.company_id, s.location_id,\n           COALESCE(s.quantity, 0) AS parent_quantity,\n           p.name AS product_name, p.barcode, p.cost_price, p.selling_price,\n           p.has_variants, p.identifier_tracking, p.imei_tracking, l.name AS current_location\n    FROM stocks s\n    INNER JOIN products p ON p.id = s.product_id AND p.company_id = s.company_id\n    LEFT JOIN locations l ON l.id = s.location_id AND l.company_id = s.company_id\n    WHERE s.id = ? AND s.company_id = ?\n    LIMIT 1\n");
$stmt->execute([$stock_id, $company_id]);
$stock = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$stock) {
    echo "<script>alert('Stock record not found or you do not have access to it.'); window.history.back();</script>";
    exit;
}

$product_id = (int)$stock['product_id'];
$source_location_id = (int)$stock['location_id'];
$has_variants = (int)$stock['has_variants'] === 1;
$current_location = $stock['current_location'] ?: 'Unassigned';
$product_name = (string)$stock['product_name'];
$barcode = (string)$stock['barcode'];
$parent_cost = transfer_money($stock['cost_price']);
$parent_selling = transfer_money($stock['selling_price']);
$parent_tracking = strtolower(trim((string)($stock['identifier_tracking'] ?? 'none')));
if ($parent_tracking === '' || $parent_tracking === 'none') {
    $parent_tracking = (int)($stock['imei_tracking'] ?? 0) === 1 ? 'imei' : 'none';
}
if (!in_array($parent_tracking, ['none', 'imei', 'serial', 'vin'], true)) $parent_tracking = 'none';
$tracking_mode = $parent_tracking;

// Destination choices are company-scoped and exclude the source location.
$stmtLoc = $pdo->prepare("SELECT id, name FROM locations WHERE company_id = ? AND id <> ? ORDER BY name ASC");
$stmtLoc->execute([$company_id, $source_location_id]);
$locations = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);

$variants = [];
$selected_variant_id = 0;
$selected_variant = null;
$current_stock = max(0, (float)$stock['parent_quantity']);
$cost_price = $parent_cost;
$selling_price = $parent_selling;

if ($has_variants) {
    // Variant balances are location-specific; do not use stocks.quantity for these products.
    $stmtVariants = $pdo->prepare("\n        SELECT v.id AS variant_id, v.variant_name, v.sku, v.barcode,\n               v.cost_price, v.selling_price, v.tracking_policy, COALESCE(vs.quantity, 0) AS available_quantity\n        FROM product_variants v\n        LEFT JOIN product_variant_stocks vs\n          ON vs.variant_id = v.id\n         AND vs.product_id = v.product_id\n         AND vs.company_id = v.company_id\n         AND vs.location_id = ?\n        WHERE v.company_id = ? AND v.product_id = ? AND v.is_active = 1\n        ORDER BY v.is_default DESC, v.variant_name ASC, v.id ASC\n    ");
    $stmtVariants->execute([$source_location_id, $company_id, $product_id]);
    $variants = $stmtVariants->fetchAll(PDO::FETCH_ASSOC);

    $requested_variant_id = (int)($_POST['variant_id'] ?? 0);
    if ($requested_variant_id > 0) {
        foreach ($variants as $variant) {
            if ((int)$variant['variant_id'] === $requested_variant_id) {
                $selected_variant = $variant;
                break;
            }
        }
    }
    if (!$selected_variant && count($variants) === 1) {
        $selected_variant = $variants[0];
    }
    if ($selected_variant) {
        $selected_variant_id = (int)$selected_variant['variant_id'];
        $current_stock = max(0, (float)$selected_variant['available_quantity']);
        $cost_price = $selected_variant['cost_price'] !== null ? transfer_money($selected_variant['cost_price']) : $parent_cost;
        $selling_price = $selected_variant['selling_price'] !== null ? transfer_money($selected_variant['selling_price']) : $parent_selling;
        $variantPolicy = strtolower(trim((string)($selected_variant['tracking_policy'] ?? 'inherit')));
        $tracking_mode = ($variantPolicy !== '' && $variantPolicy !== 'inherit') ? $variantPolicy : $parent_tracking;
    } else {
        $current_stock = 0;
    }
}

// The stock movement table records integer quantities in the supplied schema.
$current_stock = (int)floor($current_stock);
$stock_value = $current_stock * $cost_price;
$sales_value = $current_stock * $selling_price;
$potential_profit = $sales_value - $stock_value;
$variant_payload = [];
foreach ($variants as $variant) {
    $variantCost = $variant['cost_price'] !== null ? transfer_money($variant['cost_price']) : $parent_cost;
    $variantSelling = $variant['selling_price'] !== null ? transfer_money($variant['selling_price']) : $parent_selling;
    $variant_payload[] = [
        'id' => (int)$variant['variant_id'],
        'name' => (string)$variant['variant_name'],
        'sku' => (string)($variant['sku'] ?? ''),
        'barcode' => (string)($variant['barcode'] ?? ''),
        'quantity' => max(0, (int)floor((float)$variant['available_quantity'])),
        'cost' => $variantCost,
        'selling' => $variantSelling,
        'tracking' => (($vp = strtolower(trim((string)($variant['tracking_policy'] ?? 'inherit')))) !== '' && $vp !== 'inherit') ? $vp : $parent_tracking,
        'serials' => [],
    ];
}

// Load only identifiers currently in stock at this source location.
$serialStmt = $pdo->prepare("
    SELECT id, variant_id, identifier_type, identifier_value
    FROM product_serials
    WHERE company_id = ? AND product_id = ? AND location_id = ? AND stock_id = ? AND status = 'in_stock'
    ORDER BY identifier_value ASC, id ASC
");
$serialStmt->execute([$company_id, $product_id, $source_location_id, $stock_id]);
$serialRows = $serialStmt->fetchAll(PDO::FETCH_ASSOC);
$serialMap = [];
foreach ($serialRows as $serialRow) {
    $key = (int)($serialRow['variant_id'] ?? 0);
    $serialMap[$key][] = [
        'id' => (int)$serialRow['id'],
        'type' => (string)$serialRow['identifier_type'],
        'value' => (string)$serialRow['identifier_value'],
    ];
}
if ($has_variants) {
    foreach ($variant_payload as &$vpRow) $vpRow['serials'] = $serialMap[$vpRow['id']] ?? [];
    unset($vpRow);
    if ($selected_variant_id > 0) {
        foreach ($variant_payload as $vpRow) if ($vpRow['id'] === $selected_variant_id) $tracking_mode = $vpRow['tracking'];
    }
    $identifier_payload = [];
} else {
    $identifier_payload = $serialMap[0] ?? [];
}
if (!in_array($tracking_mode, ['none', 'imei', 'serial', 'vin'], true)) $tracking_mode = 'none';
$tracked_available = $has_variants ? (int)count($serialMap[$selected_variant_id] ?? []) : (int)count($identifier_payload);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= transfer_h($company_name ?? 'Company') ?> | Transfer Stock</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root{--tp:#4f46e5;--tp2:#2563eb;--ink:#172033;--muted:#64748b;--line:#e5eaf2;--glass:rgba(255,255,255,.88)}
        *{box-sizing:border-box}body{background:radial-gradient(circle at 8% 8%,rgba(79,70,229,.07),transparent 28%),radial-gradient(circle at 92% 18%,rgba(6,182,212,.07),transparent 25%),#f8fafc}
        .transfer-page{width:100%;max-width:1480px;margin:auto;color:var(--ink)}
        .crumb{display:flex;gap:9px;align-items:center;margin:0 0 18px;font-size:13px;color:var(--muted)}.crumb a{color:inherit;text-decoration:none}.crumb a:hover{color:var(--tp)}.crumb i{font-size:9px}
        .hero{position:relative;overflow:hidden;padding:27px;border:1px solid rgba(148,163,184,.2);border-radius:25px;margin-bottom:22px;background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(248,250,252,.82));box-shadow:0 18px 45px rgba(15,23,42,.06)}.hero:after{content:"";position:absolute;width:230px;height:230px;border-radius:50%;right:-85px;top:-140px;background:rgba(79,70,229,.08)}.hero-inner{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:20px}.hero-main{display:flex;align-items:center;gap:17px}.hero-icon{width:60px;height:60px;flex:0 0 60px;display:grid;place-items:center;border-radius:18px;color:#fff;font-size:24px;background:linear-gradient(135deg,var(--tp),var(--tp2));box-shadow:0 12px 28px rgba(79,70,229,.23)}.eyebrow{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--tp);margin-bottom:5px}.hero h1{font-size:clamp(24px,3vw,34px);line-height:1.15;margin:0;font-weight:850;letter-spacing:-.035em}.hero p{font-size:13px;color:var(--muted);margin:8px 0 0;line-height:1.6;max-width:680px}.status{white-space:nowrap;padding:10px 13px;border:1px solid #e0e7ff;border-radius:13px;background:#fff;font-size:12px;font-weight:750;color:#475569}.status i{color:#16a34a;margin-right:6px}
        .layout{display:grid;grid-template-columns:minmax(0,1fr) 350px;gap:21px;align-items:start}.panel,.intel{border:1px solid rgba(148,163,184,.22);border-radius:22px;background:var(--glass);box-shadow:0 14px 38px rgba(15,23,42,.055);overflow:hidden}.panel-head{padding:20px 23px;border-bottom:1px solid #edf1f6;display:flex;align-items:center;gap:12px}.head-icon,.intel-icon{width:39px;height:39px;display:grid;place-items:center;border-radius:12px;background:#eef2ff;color:var(--tp)}.panel-head h2{font-size:16px;margin:0;font-weight:800}.panel-head p{font-size:11px;color:#94a3b8;margin:3px 0 0}.panel-body{padding:23px}
        .product{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:15px;align-items:center;padding:17px;border:1px solid #e7eaff;border-radius:17px;background:linear-gradient(135deg,#f8faff,#fff);margin-bottom:22px}.overline{font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:5px}.product h3{font-size:20px;margin:0;font-weight:850;overflow-wrap:anywhere}.meta{font-size:11px;color:var(--muted);margin-top:6px}.available{min-width:110px;padding:11px;border:1px solid #d1fae5;border-radius:14px;background:#ecfdf5;text-align:center}.available span{display:block;font-size:9px;font-weight:850;text-transform:uppercase;color:#059669;letter-spacing:.08em}.available strong{display:block;font-size:22px;color:#166534;margin-top:2px}.field-label{display:block;font-size:12px;font-weight:750;color:#475569;margin-bottom:8px}.req{color:#ef4444}.field{width:100%;min-height:49px;border:1px solid #dbe2ea;border-radius:12px;background:#fff;padding:0 13px;color:#1e293b;font-size:13px;font-weight:650;outline:none}.field:focus{border-color:#818cf8;box-shadow:0 0 0 4px rgba(79,70,229,.09)}.variant-block{margin-bottom:20px}.variant-help{font-size:11px;color:#94a3b8;margin:6px 0 0}.flow{display:grid;grid-template-columns:minmax(0,1fr) 45px minmax(0,1fr);gap:13px;align-items:end;margin-bottom:22px}.location-box{height:49px;display:flex;align-items:center;gap:9px;padding:0 13px;border:1px solid var(--line);border-radius:12px;background:#f8fafc;font-size:13px;font-weight:700;color:#334155}.location-box i{color:#64748b}.arrow{height:45px;width:45px;border-radius:13px;display:grid;place-items:center;background:#eef2ff;color:var(--tp);margin-bottom:2px}.quantity-box{padding:17px;border:1px solid #e8edf4;border-radius:17px;background:#fafbfe}.quantity-top{display:flex;justify-content:space-between;gap:10px;align-items:center}.quantity-top .field-label{margin:0}.max-label{font-size:10px;font-weight:750;color:#64748b}.quantity-wrap{position:relative;margin-top:10px}.quantity-wrap input{height:56px;font-size:19px;font-weight:850;padding-right:70px}.suffix{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:10px;font-weight:850;color:#94a3b8;text-transform:uppercase}.progress{height:6px;border-radius:20px;background:#e2e8f0;overflow:hidden;margin-top:12px}.progress span{display:block;height:100%;width:100%;background:linear-gradient(90deg,var(--tp),var(--tp2),#06b6d4);transition:width .2s}.quantity-foot{display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;margin-top:7px}.actions{display:flex;justify-content:space-between;gap:12px;margin-top:22px;padding-top:19px;border-top:1px solid #edf1f6}.btn{min-height:47px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 18px;border-radius:12px;text-decoration:none;font-size:12px;font-weight:850;cursor:pointer}.cancel{border:1px solid #dbe2ea;background:#fff;color:#475569}.submit{border:0;background:linear-gradient(135deg,var(--tp),var(--tp2));color:white;min-width:185px;box-shadow:0 9px 22px rgba(79,70,229,.2)}.submit:disabled{opacity:.45;cursor:not-allowed;box-shadow:none}.side{display:flex;flex-direction:column;gap:14px}.intel{padding:19px}.intel-title{display:flex;align-items:center;gap:10px;margin-bottom:13px}.intel-title h3{font-size:13px;margin:0;font-weight:850}.intel-row{display:flex;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid #f0f3f7}.intel-row:last-child{border:0;padding-bottom:0}.intel-row span{font-size:10px;color:#94a3b8}.intel-row strong{font-size:12px;color:#334155;text-align:right}.intel-row .green{color:#059669}.visual{min-height:175px;display:grid;place-items:center;background:radial-gradient(circle,#eef2ff,white 70%)}.visual-ring{width:95px;height:95px;border:1px solid #c7d2fe;border-radius:50%;display:grid;place-items:center;box-shadow:0 0 0 12px rgba(79,70,229,.035),0 0 0 24px rgba(79,70,229,.025);color:var(--tp);font-size:28px}.notice{display:flex;gap:10px;padding:13px;border:1px solid #d1fae5;background:#f0fdf4;border-radius:13px}.notice i{color:#16a34a;margin-top:2px}.notice p{font-size:10px;line-height:1.6;color:#64748b;margin:0}.empty{padding:12px;border:1px solid #fde68a;background:#fffbeb;border-radius:12px;color:#92400e;font-size:12px;margin-bottom:16px}.hidden{display:none!important}
        .identifier-box{padding:16px;margin:0 0 18px;border:1px solid #dbeafe;border-radius:16px;background:#f8fbff}.identifier-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}.identifier-head strong{white-space:nowrap;font-size:11px;color:#4338ca;background:#eef2ff;padding:7px 9px;border-radius:9px}.identifier-help{font-size:11px;color:#64748b;margin:4px 0 12px}.identifier-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;max-height:240px;overflow:auto}.identifier-option{display:flex;align-items:center;gap:9px;min-width:0;padding:10px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;font-size:12px;color:#334155;overflow-wrap:anywhere}.identifier-option input{accent-color:#4f46e5;width:16px;height:16px;flex:0 0 auto}.identifier-option small{display:block;color:#94a3b8;font-size:9px;text-transform:uppercase;margin-bottom:2px}.identifier-empty{padding:12px;border:1px dashed #cbd5e1;border-radius:10px;color:#64748b;font-size:11px}@media(max-width:600px){.identifier-list{grid-template-columns:1fr}}
        @media(max-width:1050px){.layout{grid-template-columns:1fr}.side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.visual{min-height:190px}}
        @media(max-width:700px){.hero{padding:19px;border-radius:19px}.hero-inner{align-items:flex-start;flex-direction:column}.hero-main{gap:12px}.hero-icon{width:51px;height:51px;flex-basis:51px}.status{width:100%;text-align:center}.panel-head,.panel-body{padding:17px}.product{grid-template-columns:1fr}.available{width:100%}.flow{grid-template-columns:1fr;gap:9px}.arrow{margin:0 auto;transform:rotate(90deg)}.side{grid-template-columns:1fr}.actions{flex-direction:column-reverse}.btn{width:100%}}
    </style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900':darkMode===true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="transfer-page">
                    <nav class="crumb"><a href="index.php">Home</a><i class="fa-solid fa-chevron-right"></i><a href="stocks.php">Inventory</a><i class="fa-solid fa-chevron-right"></i><strong>Transfer Stock</strong></nav>
                    <section class="hero"><div class="hero-inner"><div class="hero-main"><div class="hero-icon"><i class="fa-solid fa-right-left"></i></div><div><div class="eyebrow">Inventory intelligence</div><h1>Transfer Stock</h1><p>Move inventory between company locations with source-location availability and variant-aware stock checks.</p></div></div><div class="status"><i class="fa-solid fa-shield-halved"></i>Controlled inventory transfer</div></div></section>
                    <div class="layout">
                        <section class="panel">
                            <div class="panel-head"><div class="head-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div><h2>Transfer configuration</h2><p>Choose the inventory item, destination and quantity.</p></div></div>
                            <div class="panel-body">
                                <form method="POST" action="process-stock-transfer.php" id="transferForm">
                                    <div class="product"><div><div class="overline">Selected inventory</div><h3><?= transfer_h($product_name) ?></h3><div class="meta" id="productMeta"><?php if ($barcode !== ''): ?><i class="fa-solid fa-barcode"></i> <?= transfer_h($barcode) ?><?php else: ?>Product ID: <?= $product_id ?><?php endif; ?></div></div><div class="available"><span>Available units</span><strong id="availableStock"><?= number_format($current_stock) ?></strong></div></div>
                                    <?php if ($has_variants): ?>
                                        <?php if (!$variants): ?><div class="empty">No active variants were found for this product. Add or activate a variant before transferring stock.</div><?php else: ?>
                                        <div class="variant-block"><label class="field-label" for="variantSelector">Product variant <span class="req">*</span></label><select id="variantSelector" class="field" required><option value="">Select a variant</option><?php foreach ($variants as $variant): ?><option value="<?= (int)$variant['variant_id'] ?>" <?= $selected_variant_id === (int)$variant['variant_id'] ? 'selected' : '' ?>><?= transfer_h($variant['variant_name']) ?><?= !empty($variant['sku']) ? ' · SKU: '.transfer_h($variant['sku']) : '' ?> — <?= number_format(max(0,(int)floor((float)$variant['available_quantity']))) ?> available</option><?php endforeach; ?></select><p class="variant-help">Availability and pricing below update for the selected variant at the current location.</p></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="flow"><div><label class="field-label">Current location</label><div class="location-box"><i class="fa-solid fa-location-dot"></i><?= transfer_h($current_location) ?></div></div><div class="arrow"><i class="fa-solid fa-arrow-right"></i></div><div><label class="field-label" for="to_location_id">Transfer to <span class="req">*</span></label><select name="to_location_id" id="to_location_id" class="field" required><option value="">Select destination</option><?php foreach ($locations as $loc): ?><option value="<?= (int)$loc['id'] ?>"><?= transfer_h($loc['name']) ?></option><?php endforeach; ?></select></div></div>
                                    <section class="identifier-box" id="identifierBox" style="display:none;">
                                        <div class="identifier-head"><div><label class="field-label" id="identifierLabel">Select tracked units</label><p class="identifier-help" id="identifierHelp">Choose the exact units being transferred. Quantity follows your selection.</p></div><strong id="identifierCount">0 selected</strong></div>
                                        <div class="identifier-list" id="identifierList"></div>
                                        <div class="identifier-empty" id="identifierEmpty" style="display:none;">No eligible tracked units are currently recorded at this location.</div>
                                    </section>
                                    <div class="quantity-box"><div class="quantity-top"><label class="field-label" for="quantity">Transfer quantity <span class="req">*</span></label><span class="max-label">Maximum: <b id="maxQuantityText"><?= number_format($current_stock) ?></b></span></div><div class="quantity-wrap"><input class="field" type="number" name="quantity" id="quantity" min="1" max="<?= $current_stock ?>" step="1" value="<?= ($tracking_mode === 'none' && $current_stock > 0) ? $current_stock : '' ?>" required <?= ($current_stock <= 0 && $tracking_mode === 'none') ? 'disabled' : '' ?> <?= $tracking_mode !== 'none' ? 'readonly' : '' ?>><span class="suffix">Units</span></div><div class="progress"><span id="quantityProgress" style="width:100%"></span></div><div class="quantity-foot"><span>1 unit minimum</span><span id="remainingText"><?= number_format($current_stock) ?> units selected</span></div></div>
                                    <input type="hidden" name="stock_id" value="<?= (int)$stock['stock_id'] ?>">
                                    <input type="hidden" name="variant_id" id="variantIdInput" value="<?= $selected_variant_id ?>">
                                    <div class="actions"><a href="stocks.php" class="btn cancel"><i class="fa-solid fa-arrow-left"></i>Cancel</a><button type="submit" class="btn submit" id="submitTransfer" <?= ($current_stock <= 0 || ($has_variants && !$variants) || count($locations) === 0) ? 'disabled' : '' ?>><i class="fa-solid fa-paper-plane"></i>Transfer stock</button></div>
                                </form>
                            </div>
                        </section>
                        <aside class="side">
                            <div class="intel visual"><div class="visual-ring"><i class="fa-solid fa-arrows-left-right"></i></div></div>
                            <div class="intel"><div class="intel-title"><div class="intel-icon"><i class="fa-solid fa-chart-simple"></i></div><h3>Stock intelligence</h3></div>
                                <div class="intel-row"><span>Available units</span><strong class="blue" id="intelQuantity"><?= number_format($current_stock) ?></strong></div>
                                <div class="intel-row"><span>Unit cost</span><strong id="intelCost">₦<?= number_format($cost_price,2) ?></strong></div>
                                <div class="intel-row"><span>Unit selling price</span><strong id="intelSelling">₦<?= number_format($selling_price,2) ?></strong></div>
                                <div class="intel-row"><span>Current capital value</span><strong id="intelCapital">₦<?= number_format($stock_value,2) ?></strong></div>
                                <div class="intel-row"><span>Potential gross profit</span><strong class="green" id="intelProfit">₦<?= number_format($potential_profit,2) ?></strong></div>
                            </div>
                            <div class="intel"><div class="notice"><i class="fa-solid fa-circle-check"></i><p>Only inventory belonging to your company is displayed. Variant availability is read from the selected source location. The transfer processor must validate the same values again when saving.</p></div></div>
                        </aside>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>
<script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
<script defer src="bundle.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const variants = <?= json_encode($variant_payload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    const hasVariants = <?= $has_variants ? 'true' : 'false' ?>;
    const parentMax = <?= (int)$current_stock ?>;
    const parentTracking = <?= json_encode($tracking_mode) ?>;
    const parentSerials = <?= json_encode($identifier_payload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    const sourceLocation = <?= (int)$source_location_id ?>;
    const quantityInput = document.getElementById('quantity');
    const progress = document.getElementById('quantityProgress');
    const remaining = document.getElementById('remainingText');
    const destination = document.getElementById('to_location_id');
    const form = document.getElementById('transferForm');
    const submit = document.getElementById('submitTransfer');
    const selector = document.getElementById('variantSelector');
    const variantIdInput = document.getElementById('variantIdInput');
    const identifierBox = document.getElementById('identifierBox');
    const identifierList = document.getElementById('identifierList');
    const identifierEmpty = document.getElementById('identifierEmpty');
    const identifierCount = document.getElementById('identifierCount');
    const identifierLabel = document.getElementById('identifierLabel');
    const identifierHelp = document.getElementById('identifierHelp');
    let maxQuantity = parentMax;
    let trackingMode = parentTracking;
    let currentSerials = parentSerials;

    const labels = {imei:'IMEI', serial:'Serial Number', vin:'VIN'};
    function money(value) { return '₦' + Number(value || 0).toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    function selectedCount() { return identifierList.querySelectorAll('input[name="serial_ids[]"]:checked').length; }
    function updateQuantityUI() {
        let quantity = parseInt(quantityInput.value, 10) || 0;
        if (trackingMode !== 'none') quantity = selectedCount();
        if (maxQuantity > 0 && quantity > maxQuantity) quantity = maxQuantity;
        if (trackingMode === 'none' && quantity < 1 && quantityInput.value !== '') quantity = 1;
        if (trackingMode !== 'none') quantityInput.value = quantity || '';
        else if (quantityInput.value !== '' && maxQuantity > 0) quantityInput.value = quantity;
        const pct = maxQuantity > 0 ? Math.min(100, Math.max(0, quantity / maxQuantity * 100)) : 0;
        progress.style.width = pct + '%';
        remaining.textContent = quantity.toLocaleString() + ' unit' + (quantity === 1 ? '' : 's') + ' selected';
        identifierCount.textContent = selectedCount() + ' selected';
        identifierList.querySelectorAll('input[name="serial_ids[]"]').forEach(box => { box.disabled = !box.checked && selectedCount() >= maxQuantity; });
        submit.disabled = !destination.value || quantity < 1 || quantity > maxQuantity;
    }
    function renderIdentifiers(serials, mode, quantityLimit = null) {
        trackingMode = mode || 'none';
        currentSerials = serials || [];
        identifierList.innerHTML = '';
        if (trackingMode === 'none') {
            identifierBox.style.display = 'none';
            quantityInput.readOnly = false;
            return;
        }
        identifierBox.style.display = '';
        quantityInput.readOnly = true;
        quantityInput.disabled = false;
        quantityInput.value = '';
        const label = labels[trackingMode] || 'Identifier';
        identifierLabel.textContent = 'Select ' + label + ' units';
        identifierHelp.textContent = 'Select the exact ' + label + '(s) being transferred. Quantity is calculated from your selection.';
        currentSerials.filter(s => s.type === trackingMode).forEach(serial => {
            const row = document.createElement('label');
            row.className = 'identifier-option';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'serial_ids[]';
            checkbox.value = String(serial.id);
            checkbox.addEventListener('change', updateQuantityUI);
            const text = document.createElement('span');
            const small = document.createElement('small');
            small.textContent = label;
            const value = document.createElement('strong');
            value.textContent = serial.value;
            text.appendChild(small); text.appendChild(value);
            row.appendChild(checkbox); row.appendChild(text); identifierList.appendChild(row);
        });
        const count = identifierList.querySelectorAll('input').length;
        identifierEmpty.style.display = count ? 'none' : '';
        maxQuantity = quantityLimit === null ? count : Math.min(count, quantityLimit);
        quantityInput.max = maxQuantity;
        quantityInput.value = '';
        updateQuantityUI();
    }
    function applyVariant(id) {
        const variant = variants.find(item => String(item.id) === String(id));
        if (!variant) {
            maxQuantity = 0; variantIdInput.value = '';
            document.getElementById('availableStock').textContent = '0';
            document.getElementById('maxQuantityText').textContent = '0';
            document.getElementById('intelQuantity').textContent = '0';
            document.getElementById('intelCost').textContent = money(0);
            document.getElementById('intelSelling').textContent = money(0);
            document.getElementById('intelCapital').textContent = money(0);
            document.getElementById('intelProfit').textContent = money(0);
            quantityInput.value = ''; quantityInput.disabled = true;
            renderIdentifiers([], 'none'); updateQuantityUI(); return;
        }
        variantIdInput.value = variant.id;
        const available = variant.tracking === 'none' ? variant.quantity : variant.serials.filter(s => s.type === variant.tracking).length;
        document.getElementById('availableStock').textContent = Number(variant.quantity).toLocaleString();
        document.getElementById('maxQuantityText').textContent = Number(available).toLocaleString();
        document.getElementById('intelQuantity').textContent = Number(variant.quantity).toLocaleString();
        document.getElementById('intelCost').textContent = money(variant.cost);
        document.getElementById('intelSelling').textContent = money(variant.selling);
        document.getElementById('intelCapital').textContent = money(variant.quantity * variant.cost);
        document.getElementById('intelProfit').textContent = money(variant.quantity * (variant.selling - variant.cost));
        quantityInput.disabled = false;
        if (variant.tracking === 'none') {
            maxQuantity = variant.quantity; quantityInput.readOnly = false;
            quantityInput.max = maxQuantity; quantityInput.value = maxQuantity > 0 ? maxQuantity : '';
            renderIdentifiers([], 'none');
        } else {
            renderIdentifiers(variant.serials, variant.tracking, variant.quantity);
            document.getElementById('maxQuantityText').textContent = Number(maxQuantity).toLocaleString();
        }
        updateQuantityUI();
    }
    if (selector) {
        selector.addEventListener('change', () => applyVariant(selector.value));
        if (selector.value) applyVariant(selector.value);
        else applyVariant('');
    } else {
        if (trackingMode !== 'none') {
            const available = parentSerials.filter(s => s.type === trackingMode).length;
            document.getElementById('maxQuantityText').textContent = available.toLocaleString();
            renderIdentifiers(parentSerials, trackingMode, parentMax);
        }
        updateQuantityUI();
    }
    quantityInput.addEventListener('input', updateQuantityUI);
    quantityInput.addEventListener('change', updateQuantityUI);
    destination.addEventListener('change', updateQuantityUI);
    form.addEventListener('submit', function (event) {
        const quantity = parseInt(quantityInput.value, 10) || 0;
        if (hasVariants && (!variantIdInput.value || !selector || !selector.value)) {
            event.preventDefault(); alert('Please select a product variant.'); if (selector) selector.focus(); return;
        }
        if (!destination.value) { event.preventDefault(); alert('Please select a destination location.'); destination.focus(); return; }
        if (parseInt(destination.value, 10) === sourceLocation) { event.preventDefault(); alert('The destination must be different from the current location.'); destination.value = ''; return; }
        if (trackingMode !== 'none' && selectedCount() !== quantity) { event.preventDefault(); alert('Select exactly one tracked identifier for each unit.'); return; }
        if (quantity < 1 || quantity > maxQuantity) { event.preventDefault(); alert('Transfer quantity must be between 1 and ' + maxQuantity + ' units.'); quantityInput.focus(); return; }
        submit.disabled = true;
        submit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing transfer';
    });
});
</script>
</body>
</html>
