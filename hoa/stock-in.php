<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');
$companyid = (int)($_SESSION['company_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($companyid <= 0 || $user_id <= 0) { http_response_code(403); exit('Your session is invalid. Please sign in again.'); }

if (empty($_SESSION['stock_in_csrf'])) { $_SESSION['stock_in_csrf'] = bin2hex(random_bytes(32)); }
$csrfToken = $_SESSION['stock_in_csrf'];

$stmt = $pdo->prepare("SELECT p.id,p.name,p.stock_type,p.category_id,p.has_variants,
    CASE WHEN COALESCE(NULLIF(p.identifier_tracking,''),'none')='none' AND COALESCE(p.imei_tracking,0)=1 THEN 'imei' ELSE COALESCE(NULLIF(p.identifier_tracking,''),'none') END AS identifier_tracking,
    COALESCE(p.imei_tracking,0) AS imei_tracking,
    (SELECT COALESCE(NULLIF(pm.thumbnail_path,''),pm.media_path)
       FROM product_media pm
      WHERE pm.product_id=p.id AND pm.company_id=p.company_id
        AND pm.variant_id IS NULL AND pm.media_type='image'
      ORDER BY pm.is_primary DESC,pm.display_order ASC,pm.id ASC LIMIT 1) AS image_path,
    (SELECT pi.image_path FROM product_images pi
      WHERE pi.product_id=p.id ORDER BY pi.id ASC LIMIT 1) AS legacy_image_path
    FROM products p INNER JOIN product_categories pc ON pc.id=p.category_id
    WHERE p.company_id=? AND COALESCE(pc.dept,'')!='Production Only' ORDER BY p.name ASC");
$stmt->execute([$companyid]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Match the existing product-media URL convention used by product pages. */
function stock_product_image_url($path): string {
    $path = trim((string)($path ?? ''));
    if ($path === '') return '';
    $clean = str_replace('\\', '/', $path);
    if (strpos($clean, '../') === 0) return $clean;
    $clean = ltrim($clean, '/');
    if (strpos($clean, 'uploads/products/') === 0 || strpos($clean, 'uploads/') === 0) return '../' . $clean;
    if (strpos($clean, 'products/') === 0) return '../uploads/' . $clean;
    return '../uploads/products/' . $clean;
}

$requestedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$requestedVariantId = isset($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;
$selectedProductId = 0;
$selectedVariantId = 0;
$stockInNotice = '';
if ($requestedProductId > 0) {
    foreach ($products as $productRow) {
        if ((int)$productRow['id'] === $requestedProductId) {
            $selectedProductId = $requestedProductId;
            break;
        }
    }
    if ($selectedProductId === 0) {
        $stockInNotice = 'The requested product could not be found in your company or is not eligible for stock-in. Please select a product below.';
        $requestedVariantId = 0;
    }
}

$variantStmt = $pdo->prepare("SELECT v.id,v.product_id,v.sku,v.barcode,v.variant_name,v.tracking_policy,
    (SELECT COALESCE(NULLIF(pm.thumbnail_path,''),pm.media_path)
       FROM product_media pm
      WHERE pm.product_id=v.product_id AND pm.variant_id=v.id
        AND pm.company_id=v.company_id AND pm.media_type='image'
      ORDER BY pm.is_primary DESC,pm.display_order ASC,pm.id ASC LIMIT 1) AS image_path,
    GROUP_CONCAT(CONCAT(a.attribute_name, ': ', av.value_name) ORDER BY a.display_order,a.attribute_name SEPARATOR ' · ') AS attributes
    FROM product_variants v
    LEFT JOIN product_variant_values vv ON vv.variant_id=v.id
    LEFT JOIN product_attributes a ON a.id=vv.attribute_id
    LEFT JOIN product_attribute_values av ON av.id=vv.attribute_value_id
    WHERE v.company_id=? AND v.is_active=1
    GROUP BY v.id,v.product_id,v.sku,v.barcode,v.variant_name,v.tracking_policy
    ORDER BY v.product_id,v.is_default DESC,v.variant_name ASC");
$variantStmt->execute([$companyid]);
$variantsByProduct = [];
foreach ($variantStmt->fetchAll(PDO::FETCH_ASSOC) as $variant) { $variantsByProduct[(string)$variant['product_id']][] = $variant; }
if ($selectedProductId > 0 && $requestedVariantId > 0) {
    foreach (($variantsByProduct[(string)$selectedProductId] ?? []) as $variantRow) {
        if ((int)$variantRow['id'] === $requestedVariantId) {
            $selectedVariantId = $requestedVariantId;
            break;
        }
    }
    if ($selectedVariantId === 0) {
        $stockInNotice = 'The requested variant is not active or does not belong to the selected product. Please choose a valid variant.';
    }
}

foreach ($products as &$productRow) {
    $imagePath = $productRow['image_path'] ?: ($productRow['legacy_image_path'] ?? '');
    $productRow['image_url'] = stock_product_image_url($imagePath);
}
unset($productRow);

$stmt = $pdo->prepare("SELECT id,name FROM locations WHERE company_id=? ORDER BY name ASC");
$stmt->execute([$companyid]); $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT id,business_name FROM vendors WHERE company_id=? ORDER BY business_name ASC");
$stmt->execute([$companyid]); $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
function stock_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= stock_h($company_name) ?> | Stock In</title>
<link rel="stylesheet" href="../fontawesome/css/all.css"><link href="style.css" rel="stylesheet">
<style>
.stock-page{width:100%;max-width:1180px;margin:0 auto}.stock-shell{position:relative;overflow:hidden;border-radius:24px;border:1px solid rgba(148,163,184,.25);background:linear-gradient(145deg,#fff,#f8fafc);box-shadow:0 20px 50px rgba(15,23,42,.08)}.stock-shell:before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at top right,rgba(59,130,246,.09),transparent 34%),radial-gradient(circle at bottom left,rgba(16,185,129,.06),transparent 30%)}.stock-header{position:relative;display:flex;align-items:center;justify-content:space-between;gap:20px;padding:26px 30px;border-bottom:1px solid #e8edf3;background:rgba(255,255,255,.7)}.stock-title-wrap{display:flex;align-items:center;gap:16px}.stock-title-icon{width:54px;height:54px;display:grid;place-items:center;border-radius:16px;background:linear-gradient(135deg,#111827,#334155);color:#fff}.stock-title-icon i{font-size:21px}.stock-title{margin:0;font-size:20px;font-weight:800;color:#111827}.stock-subtitle{margin:5px 0 0;font-size:13px;color:#64748b}.stock-status{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#ecfdf5;color:#059669;border:1px solid #d1fae5;font-size:12px;font-weight:700}.stock-status-dot{width:7px;height:7px;border-radius:50%;background:#10b981}.stock-form-area{position:relative;padding:30px}.stock-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}.stock-field{min-width:0}.stock-label{display:flex;align-items:center;gap:9px;margin-bottom:9px;color:#374151;font-size:13px;font-weight:700}.stock-label>i{width:25px;height:25px;display:grid;place-items:center;border-radius:8px;background:#f1f5f9;color:#475569;font-size:11px}.stock-required{color:#ef4444;font-size:11px}.stock-input-wrap{position:relative}.stock-input-wrap>i{position:absolute;left:15px;top:50%;transform:translateY(-50%);z-index:2;color:#94a3b8;font-size:14px;pointer-events:none}.stock-input,.stock-select{width:100%;min-height:50px;padding:13px 15px 13px 44px;border:1px solid #dbe2ea;border-radius:13px;background:#fff;color:#111827;font-size:14px;outline:none;box-sizing:border-box}.stock-input:focus,.stock-select:focus{border-color:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,.09)}.stock-select{appearance:none;cursor:pointer;padding-right:40px}.stock-select-wrap:after{content:"\f078";position:absolute;right:15px;top:50%;transform:translateY(-50%);font-family:"Font Awesome 6 Free";font-weight:900;color:#94a3b8;pointer-events:none;font-size:11px}.stock-help{margin-top:7px;font-size:11px;color:#94a3b8;line-height:1.5}.stock-selection-notice{display:flex;align-items:flex-start;gap:10px;margin:0 0 18px;padding:12px 14px;border:1px solid #fcd34d;border-radius:12px;background:#fffbeb;color:#92400e;font-size:12px;line-height:1.5}.stock-selection-notice i{margin-top:2px}.product-preview-card{grid-column:1/-1;display:flex;align-items:center;gap:16px;padding:15px;border:1px solid #dbeafe;border-radius:16px;background:linear-gradient(120deg,#eff6ff,#fff)}.product-preview-card[hidden]{display:none}.product-preview-image-wrap{width:88px;height:88px;flex:0 0 88px;display:grid;place-items:center;overflow:hidden;border:1px solid #e2e8f0;border-radius:13px;background:#fff}.product-preview-image-wrap img{width:100%;height:100%;object-fit:contain}.product-preview-placeholder{width:100%;height:100%;display:grid;place-items:center;color:#94a3b8;font-size:28px;background:#f8fafc}.product-preview-copy{display:flex;flex-direction:column;gap:5px;min-width:0}.product-preview-caption{font-size:9px;font-weight:850;letter-spacing:.1em;color:#64748b}.product-preview-copy strong{font-size:15px;color:#172033;overflow-wrap:anywhere}.product-preview-variant{font-size:11px;color:#4f46e5}.variant-panel{display:none;grid-column:1/-1;padding:18px;border:1px solid #c7d2fe;border-radius:16px;background:#eef2ff}.variant-panel.visible{display:block}.variant-summary{margin:10px 0 0;padding:12px 14px;border-radius:11px;background:rgba(255,255,255,.8);color:#475569;font-size:12px;line-height:1.6}.variant-summary strong{color:#1e293b}.imei-panel{display:none;grid-column:1/-1;padding:20px;border:1px solid rgba(59,130,246,.22);border-radius:17px;background:#eff6ff}.imei-panel.is-visible{display:block}.imei-panel-head{display:flex;justify-content:space-between;gap:14px;margin-bottom:15px}.imei-panel-title{margin:0;color:#1e3a8a;font-size:14px;font-weight:800}.imei-panel-description{margin:5px 0 0;color:#64748b;font-size:12px;line-height:1.55}.imei-count{padding:7px 10px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:800;white-space:nowrap}.imei-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.imei-item label{display:block;margin-bottom:6px;color:#334155;font-size:11px;font-weight:800}.imei-item input{width:100%;height:46px;padding:0 13px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;box-sizing:border-box}.imei-item input.invalid{border-color:#ef4444;background:#fff7f7}.imei-empty{padding:16px;border:1px dashed #cbd5e1;border-radius:12px;color:#64748b;font-size:12px;text-align:center}.imei-warning{margin-top:12px;padding:11px 13px;border-radius:10px;background:#fff7ed;color:#9a3412;font-size:11px;line-height:1.5}.stock-submit-area{grid-column:1/-1;margin-top:4px;padding-top:24px;border-top:1px solid #e5e7eb}.stock-submit{width:100%;min-height:54px;display:flex;align-items:center;justify-content:center;gap:11px;border:0;border-radius:14px;background:linear-gradient(135deg,#111827,#1e293b);color:#fff;font-size:14px;font-weight:800;cursor:pointer}.stock-footer-note{text-align:center;margin-top:12px;font-size:11px;color:#94a3b8}.dark .stock-shell{background:#0f172a;border-color:#334155}.dark .stock-title{color:#f8fafc}.dark .stock-subtitle,.dark .stock-label{color:#cbd5e1}.dark .stock-header{background:#111827;border-color:#334155}.dark .stock-input,.dark .stock-select{background:#0f172a;color:#f8fafc;border-color:#475569}.dark .variant-panel,.dark .imei-panel{background:#172554;border-color:#3730a3}.dark .variant-summary{background:#1e293b;color:#cbd5e1}.dark .variant-summary strong,.dark .imei-panel-title{color:#dbeafe}.dark .imei-panel-description,.dark .imei-item label{color:#cbd5e1}.dark .imei-item input{background:#0f172a;color:#fff;border-color:#475569}.dark .stock-submit-area{border-color:#334155}.dark .product-preview-card{background:#172554;border-color:#3730a3}.dark .product-preview-copy strong{color:#f8fafc}.dark .product-preview-placeholder{background:#1e293b}.dark .stock-selection-notice{background:#422006;border-color:#92400e;color:#fde68a}@media(max-width:800px){.stock-form-grid{grid-template-columns:1fr}.stock-header,.stock-form-area{padding:22px}.stock-submit-area,.variant-panel,.imei-panel{grid-column:1}}@media(max-width:600px){.stock-header{align-items:flex-start;flex-direction:column;padding:19px}.stock-form-area{padding:18px 15px}.stock-title{font-size:18px}.imei-grid{grid-template-columns:1fr}.imei-panel-head{flex-direction:column}.stock-shell{border-radius:17px}}
</style>
</head>
<body x-data="{page:'ecommerce',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}" x-init="darkMode=JSON.parse(localStorage.getItem('darkMode')||'false');$watch('darkMode',value=>localStorage.setItem('darkMode',JSON.stringify(value)))" :class="{'dark bg-gray-900':darkMode===true}">
<div class="flex h-screen overflow-hidden"><?php include('sidebar.php'); ?><div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"><main><?php include('header.php'); ?><div class="mx-auto max-w-(--breakpoint-2xl) p-3 sm:p-4 md:p-6"><div class="stock-page"><div class="stock-shell">
<div class="stock-header"><div class="stock-title-wrap"><div class="stock-title-icon"><i class="fa-solid fa-boxes-stacked"></i></div><div><h1 class="stock-title">Add New Stock</h1><p class="stock-subtitle">Receive inventory into the correct location and product variant.</p></div></div><div class="stock-status"><span class="stock-status-dot"></span> Inventory Terminal</div></div>
<div class="stock-form-area"><?php if ($stockInNotice !== ''): ?><div class="stock-selection-notice" role="alert"><i class="fa-solid fa-circle-info"></i><?= stock_h($stockInNotice) ?></div><?php endif; ?><form method="POST" action="create-stock.php" enctype="multipart/form-data" id="stockInForm"><input type="hidden" name="csrf_token" value="<?= stock_h($csrfToken) ?>"><input type="hidden" name="company_id" value="<?= stock_h($companyid) ?>"><input type="hidden" name="recorded_by" value="<?= stock_h($user_id) ?>"><input type="hidden" name="stocked_by" value="<?= stock_h($user_id) ?>">
<div class="stock-form-grid">
<div class="stock-field"><label class="stock-label"><i class="fa-solid fa-cube"></i> Select Product <span class="stock-required">Required</span></label><div class="stock-input-wrap stock-select-wrap"><i class="fa-solid fa-box"></i><select name="product_id" id="stockProduct" required class="stock-select"><option value="">-- Select Product --</option><?php foreach($products as $product): ?><option value="<?= stock_h($product['id']) ?>" data-has-variants="<?= (int)$product['has_variants'] ?>" data-tracking="<?= stock_h($product['identifier_tracking']) ?>" data-stock-type="<?= stock_h($product['stock_type']) ?>" data-image-url="<?= stock_h($product['image_url']) ?>" <?= $selectedProductId === (int)$product['id'] ? 'selected' : '' ?>><?= stock_h($product['stock_type']) ?> : <?= stock_h($product['name']) ?><?= (int)$product['has_variants']===1?' (Variants)':'' ?></option><?php endforeach; ?></select></div><div class="stock-help">Choose the product family you are receiving. Existing products remain available for restocking.</div></div>
<div class="product-preview-card" id="productPreview" aria-live="polite" hidden><div class="product-preview-image-wrap"><img id="productPreviewImage" alt="" hidden><div class="product-preview-placeholder" id="productPreviewPlaceholder"><i class="fa-solid fa-box-open"></i></div></div><div class="product-preview-copy"><span class="product-preview-caption">SELECTED PRODUCT</span><strong id="productPreviewName"></strong><span id="productPreviewVariant" class="product-preview-variant"></span></div></div>
<div class="stock-field"><label class="stock-label"><i class="fa-solid fa-location-dot"></i> Stock Location <span class="stock-required">Required</span></label><div class="stock-input-wrap stock-select-wrap"><i class="fa-solid fa-location-crosshairs"></i><select name="location_id" required class="stock-select"><option value="">-- Select Location --</option><?php foreach($locations as $location): ?><option value="<?= stock_h($location['id']) ?>"><?= stock_h($location['name']) ?></option><?php endforeach; ?></select></div><div class="stock-help">Choose where the received units will be stored.</div></div>
<section class="variant-panel" id="variantPanel"><label class="stock-label"><i class="fa-solid fa-layer-group"></i> Select Product Variant <span class="stock-required">Required</span></label><div class="stock-input-wrap stock-select-wrap"><i class="fa-solid fa-tags"></i><select name="variant_id" id="stockVariant" class="stock-select"><option value="">-- Select Variant --</option></select></div><div class="variant-summary" id="variantSummary">Choose the exact variant you are receiving.</div></section>
<div class="stock-field"><label class="stock-label"><i class="fa-solid fa-layer-group"></i> Quantity <span class="stock-required">Required</span></label><div class="stock-input-wrap"><i class="fa-solid fa-hashtag"></i><input type="number" step="1" min="1" name="quantity" id="stockQuantity" required placeholder="Enter whole-unit quantity" class="stock-input"></div><div class="stock-help" id="quantityHelp">Enter the number of units being added to this location.</div></div>
<div class="stock-field"><label class="stock-label" for="stock_invoice"><i class="fa-solid fa-file-invoice"></i> Stock Invoice <span style="color:#94a3b8;font-size:11px">Optional</span></label><div class="stock-input-wrap"><i class="fa-solid fa-paperclip"></i><input type="file" name="stock_invoice" id="stock_invoice" class="stock-input" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"></div><div class="stock-help">Supplier invoice; PDF, JPG or PNG, maximum 20 MB.</div></div>
<div class="stock-field"><label class="stock-label"><i class="fa-solid fa-truck-field"></i> Vendor / Supplier <span class="stock-required">Required</span></label><div class="stock-input-wrap stock-select-wrap"><i class="fa-solid fa-truck"></i><select name="supplier" required class="stock-select"><option value="">-- Select Product Supplier --</option><?php foreach($vendors as $vendor): ?><option value="<?= stock_h($vendor['id']) ?>"><?= stock_h($vendor['business_name']) ?></option><?php endforeach; ?></select></div><div class="stock-help">Select the supplier for this stock entry.</div></div>
<section class="imei-panel" id="imeiPanel" aria-live="polite" aria-hidden="true"><div class="imei-panel-head"><div><h2 class="imei-panel-title" id="identifierPanelTitle">Device Identifier Registration</h2><p class="imei-panel-description" id="identifierPanelDescription">Enter one unique identifier for each unit being received.</p></div><span class="imei-count" id="imeiCount">0 identifiers required</span></div><div class="imei-grid" id="imeiFields"><div class="imei-empty">Select a tracked product or variant and enter quantity to generate identifier fields.</div></div><div class="imei-warning" id="identifierWarning"><strong>Important:</strong> Every unit must have its own unique identifier.</div></section>
<div class="stock-submit-area"><button type="submit" class="stock-submit"><i class="fa-solid fa-plus"></i><span>Add Stock to Inventory</span><i class="fa-solid fa-arrow-right"></i></button><div class="stock-footer-note"><i class="fa-solid fa-shield-halved"></i> Inventory will be registered under <?= stock_h($company_name) ?></div></div>
</div></form></div></div></div></div></main></div></div>
<?php include('footer.php'); ?><script defer src="bundle.js"></script>
<script>
(function(){'use strict';
const variantsByProduct=<?= json_encode($variantsByProduct, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const initialProductId=<?= (int)$selectedProductId ?>,initialVariantId=<?= (int)$selectedVariantId ?>;
const form=document.getElementById('stockInForm'),product=document.getElementById('stockProduct'),variant=document.getElementById('stockVariant'),variantPanel=document.getElementById('variantPanel'),variantSummary=document.getElementById('variantSummary'),qty=document.getElementById('stockQuantity'),panel=document.getElementById('imeiPanel'),fields=document.getElementById('imeiFields'),count=document.getElementById('imeiCount'),title=document.getElementById('identifierPanelTitle'),description=document.getElementById('identifierPanelDescription'),warning=document.getElementById('identifierWarning'),help=document.getElementById('quantityHelp'),preview=document.getElementById('productPreview'),previewImage=document.getElementById('productPreviewImage'),previewPlaceholder=document.getElementById('productPreviewPlaceholder'),previewName=document.getElementById('productPreviewName'),previewVariant=document.getElementById('productPreviewVariant');
let tracking='none';
function option(){return product.options[product.selectedIndex]||null;}
function selectedVariant(){return (variantsByProduct[String(product.value)]||[]).find(v=>String(v.id)===String(variant.value))||null;}
function resolveTracking(){const p=option();if(!p||!p.value)return 'none';const v=selectedVariant();let mode=v&&v.tracking_policy&&v.tracking_policy!=='inherit'?v.tracking_policy:p.dataset.tracking;if(!['none','imei','serial','vin'].includes(mode))mode='none';return mode;}
function field(i){const wrap=document.createElement('div');wrap.className='imei-item';const label=document.createElement('label');label.htmlFor='identifier_'+i;const kind=tracking==='imei'?'IMEI':tracking==='vin'?'VIN':'Serial Number';label.textContent='Unit '+i+' — '+kind;const input=document.createElement('input');input.type='text';input.name='identifiers[]';input.id='identifier_'+i;input.autocomplete='off';input.required=true;input.maxLength=tracking==='imei'?15:tracking==='vin'?17:100;input.placeholder=tracking==='imei'?'Enter 15-digit IMEI':tracking==='vin'?'Enter 17-character VIN':'Enter unique serial number';if(tracking==='imei'){input.inputMode='numeric';input.minLength=15;input.pattern='[0-9]{15}';}else if(tracking==='vin'){input.minLength=17;input.pattern='[A-HJ-NPR-Z0-9]{17}';input.autocapitalize='characters';}input.addEventListener('input',function(){if(tracking==='imei')this.value=this.value.replace(/\D/g,'').slice(0,15);else if(tracking==='vin')this.value=this.value.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g,'').slice(0,17);else this.value=this.value.slice(0,100);this.classList.remove('invalid');});wrap.append(label,input);return wrap;}
function renderIdentifiers(){tracking=resolveTracking();const n=Math.floor(Number(qty.value)||0);fields.innerHTML='';if(tracking==='none'){panel.classList.remove('is-visible');panel.setAttribute('aria-hidden','true');count.textContent='0 identifiers required';return;}panel.classList.add('is-visible');panel.setAttribute('aria-hidden','false');const label=tracking==='imei'?'IMEI':tracking==='vin'?'VIN':'serial number';title.textContent=tracking==='imei'?'Device IMEI Registration':tracking==='vin'?'Vehicle VIN Registration':'Serial Number Registration';description.textContent=tracking==='imei'?'Enter one valid 15-digit IMEI for every unit.':tracking==='vin'?'Enter one unique 17-character VIN for every vehicle.':'Enter one unique serial number for every unit.';warning.innerHTML='<strong>Important:</strong> Every unit must have its own unique '+label+'. Duplicate identifiers are not allowed.';count.textContent=n+' '+(n===1?'identifier':'identifiers')+' required';help.textContent='This variant requires one '+label+' for each whole unit received.';if(!Number.isInteger(Number(qty.value))||n<1||n>500){const empty=document.createElement('div');empty.className='imei-empty';empty.textContent=n>500?'Maximum 500 tracked units per stock-in operation.':'Enter a whole-number quantity from 1 to 500.';fields.append(empty);return;}for(let i=1;i<=n;i++)fields.append(field(i));}
function renderProductPreview(){const p=option();if(!p||!p.value){preview.hidden=true;return;}const v=selectedVariant();const imageUrl=(v&&v.image_path?resolveImageUrl(v.image_path):'')||p.dataset.imageUrl||'';preview.hidden=false;previewName.textContent=p.textContent.replace(/\s*\(Variants\)\s*$/,'').trim();previewVariant.textContent=v?(v.variant_name+(v.sku?' · SKU: '+v.sku:'')):'';previewImage.onerror=function(){this.hidden=true;previewPlaceholder.hidden=false;};if(imageUrl){previewImage.src=imageUrl;previewImage.alt=previewName.textContent;previewImage.hidden=false;previewPlaceholder.hidden=true;}else{previewImage.removeAttribute('src');previewImage.hidden=true;previewPlaceholder.hidden=false;}}
function resolveImageUrl(path){const clean=String(path||'').replace(/\\/g,'/').trim();if(!clean)return '';if(clean.startsWith('../'))return clean;if(clean.startsWith('uploads/'))return '../'+clean;if(clean.startsWith('products/'))return '../uploads/'+clean;return '../uploads/products/'+clean;}
function renderVariants(){const p=option(),list=variantsByProduct[String(product.value)]||[],has=!!(p&&p.dataset.hasVariants==='1');variant.innerHTML='<option value="">-- Select Variant --</option>';variant.required=has;variant.disabled=!has;variantPanel.classList.toggle('visible',has);if(has){list.forEach(v=>{const o=document.createElement('option');o.value=v.id;const bits=[v.variant_name];if(v.sku)bits.push('SKU: '+v.sku);if(v.attributes)bits.push(v.attributes);o.textContent=bits.join(' — ');o.dataset.trackingPolicy=v.tracking_policy;o.dataset.imagePath=v.image_path||'';variant.append(o);});if(!list.length){const o=document.createElement('option');o.value='';o.textContent='No active variants configured';variant.append(o);}}if(initialProductId&&String(product.value)===String(initialProductId)&&initialVariantId){variant.value=String(initialVariantId);}variantSummary.textContent=has?'Select the exact sellable combination you are receiving. Variant-specific tracking rules will be applied.':'This product uses its parent product settings.';if(initialVariantId&&variant.value===String(initialVariantId)){const v=selectedVariant();if(v){const details=[];if(v.sku)details.push('SKU: '+v.sku);if(v.barcode)details.push('Barcode: '+v.barcode);if(v.attributes)details.push(v.attributes);variantSummary.textContent=details.join(' · ')||v.variant_name;}}renderProductPreview();renderIdentifiers();}
product.addEventListener('change',function(){renderVariants();});variant.addEventListener('change',function(){const v=selectedVariant();variantSummary.innerHTML='';if(v){const details=[];if(v.sku)details.push('SKU: '+v.sku);if(v.barcode)details.push('Barcode: '+v.barcode);if(v.attributes)details.push(v.attributes);variantSummary.textContent=details.join(' · ')||v.variant_name;}renderProductPreview();renderIdentifiers();});qty.addEventListener('input',renderIdentifiers);
form.addEventListener('submit',function(e){if(!product.value){e.preventDefault();alert('Select a product.');return;}if(option().dataset.hasVariants==='1'&&!variant.value){e.preventDefault();alert('Select the exact product variant before receiving stock.');variant.focus();return;}if(!Number.isInteger(Number(qty.value))||Number(qty.value)<1){e.preventDefault();alert('Stock quantity must be a whole number greater than zero.');qty.focus();return;}if(tracking==='none')return;const n=Number(qty.value);if(n>500){e.preventDefault();alert('A maximum of 500 tracked units can be entered at once.');return;}const inputs=[...fields.querySelectorAll('input[name="identifiers[]"]')];if(inputs.length!==n){e.preventDefault();alert('Identifier field count does not match quantity.');return;}const seen=new Set();let first=null;for(const input of inputs){const value=input.value.trim(),normalized=tracking==='vin'?value.toUpperCase():value;const valid=tracking==='imei'?/^[0-9]{15}$/.test(value):tracking==='vin'?/^[A-HJ-NPR-Z0-9]{17}$/.test(normalized):value.length>=1&&value.length<=100&&!/[\x00-\x1F\x7F]/.test(value);if(!valid||seen.has(normalized)){input.classList.add('invalid');if(!first)first=input;}else{seen.add(normalized);input.classList.remove('invalid');}}if(first){e.preventDefault();alert('Enter a valid, unique identifier for every unit.');first.focus();}});
renderVariants();
})();
</script></body></html>
