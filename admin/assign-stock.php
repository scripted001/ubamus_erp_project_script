<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');

$company_id = $_SESSION['company_id'];
if (!isset($_POST['stock_id'])) {
    echo "<script>alert('No stock selected for transfer.'); window.history.back();</script>";
    exit;
}

$stock_id = $_POST['stock_id'];

$stmt = $pdo->prepare("
SELECT * 
FROM stocks 
WHERE id = ?
");
$stmt->execute([$stock_id]);
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Further transfer logic will be added later

$product_id = $stocks['product_id'];
$stock_qty = $stocks['quantity'];
$stock_supplier_id = $stocks['supplier'];
$stock_type = $stocks['stock_type'];

/**
 * 7. IMAGES
 */

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
      <title>
        <?php echo $app_name; ?> |  Assign Stock...
      </title>
       <link rel="icon" href="src/images/icons/favicon.png">
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
                    <?php include('header.php'); ?>
                    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                        <div class="grid grid-cols-6 gap-4 md:gap-6">
    <div class="col-span-12 space-y-6 xl:col-span-7">
        <?php
        // Assume $stock_id is received via POST
        if (isset($stock_id)) {
            // Fetch stock and current location
            $stmt = $pdo->prepare("
                SELECT s.id AS stock_id, s.product_id AS product_ids, s.quantity, l.name AS current_location
                FROM stocks s
                LEFT JOIN locations l ON s.location_id = l.id
                WHERE s.id = ?
            ");
            $stmt->execute([$stock_id]);
            $stock = $stmt->fetch(PDO::FETCH_ASSOC);
            $product_id = $stock['product_ids'];


            // Fetch all other locations for the company
            $stmtLoc = $pdo->prepare("SELECT id, name FROM locations WHERE company_id = ? AND id != ?");
            $stmtLoc->execute([$company_id, $stock['stock_id']]);
            $locations = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $products = $stmt->fetch(PDO::FETCH_ASSOC);       
            
            $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
            $stmt->execute([$products['category_id']]);
            $cats = $stmt->fetch(PDO::FETCH_ASSOC); 

            // Fetch all other locations for the company
            $stmtStaf = $pdo->prepare("SELECT id, firstname, lastname, role FROM staffs WHERE company_id = ?");
            $stmtStaf->execute([$company_id]);
            $staffs = $stmtStaf->fetchAll(PDO::FETCH_ASSOC);
            
            // echo $cats['name'];



// Further transfer logic will be added later
/**
 * 4. PRODUCT PRICING + UNIT (FIXED JOIN)
 */
$stmt = $pdo->prepare("
SELECT 
    pu.cost_price,
    pu.selling_price,
    u.unit_name,
    u.unit_type
FROM product_units pu
LEFT JOIN units u ON pu.unit_id = u.id
WHERE pu.product_id = ?
LIMIT 1
");

$stmt->execute([$product_id]);
$productDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];

/**
 * 5. STOCK VALUES
 */
$cost_price = $productDetails['cost_price'] ?? 0;
$selling_price = $productDetails['selling_price'] ?? 0;

$total_stock_value = $total_stock * $selling_price;
$assigned_value = $assigned_stock * $selling_price;
$unassigned_value = $unassigned_stock * $selling_price;

/**
 * 6. FEATURES
 */
$stmt = $pdo->prepare("
SELECT feature_name, feature_value 
FROM product_features 
WHERE product_id = ?
");
$stmt->execute([$product_id]);
$features = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ?>
        <form method="POST" action="process-assign-stock.php" class="bg-white p-6 rounded-lg shadow space-y-4">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Allocate Stock..</h2>

            <!-- Product name of the stock -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Product Name</label>
                <input type="text" readonly
                       value="<?= htmlspecialchars($products['name'] ?? 'N/A') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed">
            </div>
            <!-- Name of the product category -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Current Location</label>
                <input type="text" readonly
                       value="<?= htmlspecialchars($stock['current_location'] ?? 'N/A') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed">
            </div>

            <!-- Unit type of the product category -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Category Unit Type</label>
                <input type="text" readonly
                       value="<?= htmlspecialchars($cats['unit_type'] ?? 'N/A') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed">
            </div>
            <!-- Stock Type of the product category -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Stock Type</label>
                <input type="text" readonly
                       value="<?= htmlspecialchars($cats['stock_type'] ?? 'N/A') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed">
            </div>
            <!-- The cost price of the product unit where the product id -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Cost Price</label>
                <input type="text" readonly
                       value="<?= htmlspecialchars($productDetails['cost_price'] ?? 'N/A') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed">
            </div>
            <!-- The selling price of the product unit where the product id -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Selling Price</label>
                <input type="text" readonly
                       value="<?= htmlspecialchars($productDetails['selling_price'] ?? 'N/A') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed">
            </div>
            <!-- The avaliable quantity of the stock -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Product Quantity</label>
                <input type="text" readonly
                       value="<?= htmlspecialchars($stock['quantity'] ?? 'N/A') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed">
            </div>

            <!-- The Staff to be assigned to -->
            <div>
               
                <label class="block text-gray-700 font-medium mb-1">Assigned To</label>
                <select name="assigned_to" required
                        class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="" selected disabled>Select Staff</option>
                    <?php foreach ($staffs as $staff): ?>
                        <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['firstname']) ?> <?= htmlspecialchars($staff['lastname']) ?> - <?= htmlspecialchars($staff['role']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Quantity</label>
                <input type="number" name="quantity" 
                       placeholder="<?= $stock['quantity'] ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" name="stock_id" value="<?= $stock['stock_id'] ?>">
            <input type="hidden" name="company_id" value="<?= $company_id ?>">

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded font-medium" style="width: 200px;padding: 8px;border-radius: 8px;border-color: none;background-color: #0a0ef9ff;color: #fff;">
                     Proceed
                </button>
            </div>
        </form>
        <?php } else { ?>
            <p class="text-red-500 font-medium">No stock selected for transfer.</p>
        <?php } ?>
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
    </body>
</html>

