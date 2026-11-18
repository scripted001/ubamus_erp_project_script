<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


$companyid = $_SESSION['company_id'];



$stmt = $pdo->prepare("SELECT company_name FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company_name = $stmt->fetchColumn();

$words = preg_split('/\s+/', trim($company_name));
$company_abbr = '';

foreach ($words as $word) {
    $company_abbr .= strtoupper($word[0]);
}

// If it's a single word, use the first 3 letters
if (count($words) == 1) {
    $company_abbr = strtoupper(substr($company_name, 0, 3));
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
      <title>
        <?php echo $company_name; ?> |  Staff Area
      </title>
       
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
                        <?php
                        include('header.php');
                        ?>
                        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                          <form method="POST" action="insert_production.php" id="production_form">
  <div class="grid grid-cols-6 gap-4 md:gap-6">
    <div class="col-span-12 space-y-6 xl:col-span-7 bg-white dark:bg-dark-900 p-6 rounded-2xl shadow-lg">
      <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Production Dashboard</h2>

      <!-- 1. Select or Create Batch -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          
        </div> 
       <!-- stock location -->
<div>
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
    Select Location
  </label>

  <select name="location_id" id="location_id"
    class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white">
    <option value="">-- Select Location --</option>

    <?php
    // Fetch locations strictly by company_id only
    $stmt = $pdo->prepare("
        SELECT id, name, type
        FROM locations
        WHERE company_id = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$companyid]);

    while ($row = $stmt->fetch()) {
        echo '<option value="'.$row['id'].'">'
            . htmlspecialchars($row['name'])
            . ' ('.htmlspecialchars($row['type']).')'
            . '</option>';
    }
    ?>
  </select>
</div>
<!-- Product Category -->
<div>
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
    Select Category
  </label>

  <select name="category_id" id="category_id"
    class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white">
    <option value="">-- Select category --</option>

    <?php
    $stk_type = "Production Only";
    // Fetch locations strictly by company_id only
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM product_categories
        WHERE company_id = ? AND stock_type = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$companyid, $stk_type]);

    while ($row = $stmt->fetch()) {
        echo '<option value="'.$row['id'].'">'
            . htmlspecialchars($row['name'])
            . ' </option>';
    }
    ?>
  </select>
</div>



        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">New Product Name</label>
          <input type="text" name="product_name" id="new_product_name" placeholder="Enter new product name" class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white" />
          <p class="text-gray-500 text-sm mt-1">Leave blank to select an existing batch/product.</p>
        </div>
      </div>

      <!-- 2. Batch Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Batch Number</label>
          <input type="text" name="batch_number" id="batch_number" placeholder="Auto-generated" readonly
                 class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Quantity Produced</label>
          <input type="number" name="quantity_produced" id="quantity_produced" placeholder="Enter quantity" class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Product Description</label>
          <textarea  name="description" class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white"></textarea>
        </div>
      </div>

      <!-- 3. Raw Materials -->
      <div class="mt-6">
        <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-2">Raw Materials</h3>
        <table class="w-full text-left border rounded-lg">
          <thead class="bg-gray-100 dark:bg-dark-800">
            <tr>
              <th class="p-2">Material</th>
              <th class="p-2">Quantity Used</th>
              <th class="p-2">Unit Cost</th>
              <th class="p-2">Total Cost</th>
              <th class="p-2">Actions</th>
            </tr>
          </thead>
          <tbody id="raw_materials_body">
            <!-- JS will populate -->
          </tbody>
        </table>
        <button type="button" id="add_raw_material" class="mt-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg" style="width: 240px;padding: 8px;border-radius: 8px;background-color: #6839ebff;color: #fff;">+ Add Raw Material</button>
      </div>

      <!-- 4. Services -->
      <div class="mt-6">
        <h3 class="text-lg font-medium text-gray-800 dark:text-white mb-2">Production Services</h3>
        <table class="w-full text-left border rounded-lg">
          <thead class="bg-gray-100 dark:bg-dark-800">
            <tr>
              <th class="p-2">Service</th>
              <th class="p-2">Cost</th>
              <th class="p-2">Actions</th>
            </tr>
          </thead>
          <tbody id="services_body">
            <!-- JS will populate -->
          </tbody>
        </table>
        <button type="button" id="add_service" class="mt-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg" style="width: 240px;padding: 8px;border-radius: 8px;background-color: #6839ebff;color: #fff;">+ Add Service</button>
      </div>

      <!-- 5. Summary -->
      <div class="mt-6 bg-gray-50 dark:bg-dark-800 p-4 rounded-lg space-y-2">
        <p>Total Raw Materials Cost: <span id="total_materials_cost">0</span></p>
        <p>Total Services Cost: <span id="total_services_cost">0</span></p>
        <p>Total Production Cost: <span id="total_production_cost">0</span></p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
         <div>
            <label>Unit Cost Price</label>
            <input type="text" name="unit_cost" id="unit_cost" class="w-full px-4 py-2 border rounded-lg dark:bg-dark-900 dark:text-white" readonly />
        </div>  
        <div>
            <label>Profit Margin (%)</label>
            <input type="number" name="profit_margin" id="profit_margin" value="20" class="w-full px-4 py-2 border rounded-lg dark:bg-dark-900 dark:text-white" />
          </div>
          <div>
            <label>Unit Selling Price</label>
            <input type="text" name="unit_selling_price" id="unit_selling_price" class="w-full px-4 py-2 border rounded-lg dark:bg-dark-900 dark:text-white" readonly />
          </div>
        </div>
      </div>

      <!-- Production Status -->
<div>
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
    Production Status
  </label>

  <select name="production_status" id="production_status" class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white">
    <option value="">-- Select Production Status --</option>
    <option value="In Progress">In Progress</option>
    <option value="Finished">Finished</option>
    <option value="Cancelled">Cancelled</option>
  </select>
</div>
      <!-- 6. Submit -->
      <div class="mt-6">
        <button type="submit" name="finalize_production" class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg w-full text-center font-semibold" style="width: 340px;padding: 8px;border-radius: 8px;background-color: #6839ebff;color: #fff;">Render Production</button>
      </div>
    </div>
  </div>
</form>

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
        <script>
const COMPANY_ABBR = <?= json_encode($company_abbr) ?>;
</script>
 <script>
let rawMaterialsCount = 0;
let servicesCount = 0;

// Recalculate totals and unit price
function recalcTotals() {

    let totalMaterials = 0;
    document.querySelectorAll('.rm_total').forEach(el => {
        totalMaterials += parseFloat(el.value || 0);
    });

    document.getElementById('total_materials_cost').innerText = totalMaterials.toFixed(2);

    let totalServices = 0;
    document.querySelectorAll('.srv_cost').forEach(el => {
        totalServices += parseFloat(el.value || 0);
    });

    document.getElementById('total_services_cost').innerText = totalServices.toFixed(2);

    let totalProduction = totalMaterials + totalServices;
    document.getElementById('total_production_cost').innerText = totalProduction.toFixed(2);

    let qty = parseFloat(document.getElementById('quantity_produced').value || 1);

    if (qty <= 0) qty = 1;

    let unitCost = totalProduction / qty;

    document.getElementById('unit_cost').value = unitCost.toFixed(2);

    const status = document.getElementById('production_status').value;

    let margin = parseFloat(document.getElementById('profit_margin').value || 0);

    // Ignore profit margin while production is in progress
    if (status === "In Progress") {
        margin = 0;
    }

    let unitSellingPrice = unitCost * (1 + (margin / 100));

    document.getElementById('unit_selling_price').value = unitSellingPrice.toFixed(2);

}

function handleProductionStatus() {

    const status = document.getElementById('production_status').value;
    const marginInput = document.getElementById('profit_margin');

    if (status === "In Progress") {

        marginInput.value = 0;
        marginInput.readOnly = true;
        marginInput.style.backgroundColor = "#f3f4f6";
        marginInput.style.cursor = "not-allowed";

    } else {

        marginInput.readOnly = false;
        marginInput.style.backgroundColor = "";
        marginInput.style.cursor = "";
    }

    recalcTotals();

}


// Auto-generate service code
function generateBatchNumber() {
    const today = new Date();
    const yy = String(today.getFullYear()).slice(-2);
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const random = Math.floor(Math.random() * 900 + 100);

    const batchNumber = `${COMPANY_ABBR}-B${yy}${mm}${dd}-${random}`;

    document.getElementById('batch_number').value = batchNumber;
}

// Generate batch number on page load
window.addEventListener('DOMContentLoaded', () => {

    generateBatchNumber();
    handleProductionStatus();

});

// Add a new raw material row
function addRawMaterial() {
  const tbody = document.getElementById('raw_materials_body');
  const index = tbody.children.length;
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><input type="text" name="raw_materials[${index}][name]" class="w-full px-2 py-1 border rounded" placeholder="Material Name"></td>
    <td><input type="number" name="raw_materials[${index}][qty]" class="w-full px-2 py-1 border rm_qty" value="0"></td>
    <td><input type="number" name="raw_materials[${index}][unitCost]" class="w-full px-2 py-1 border rm_unit" value="0"></td>
    <td><input type="number" class="w-full px-2 py-1 border rm_total" value="0" readonly></td>
    <td><button type="button" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded remove-btn" style="width: 140px;padding: 8px;border-radius: 8px;background-color: #e03a3aff;color: #fff;">Remove</button></td>
  `;
  tbody.appendChild(row);

  row.querySelectorAll('input').forEach(inp => {
    inp.addEventListener('input', () => {
      const tr = inp.closest('tr');
      const qty = parseFloat(tr.querySelector('.rm_qty').value || 0);
      const unit = parseFloat(tr.querySelector('.rm_unit').value || 0);
      tr.querySelector('.rm_total').value = (qty*unit).toFixed(2);
      recalcTotals();
    });
  });

  row.querySelector('.remove-btn').addEventListener('click', () => {
    row.remove();
    recalcTotals();
    // Re-index names after removal
    Array.from(tbody.children).forEach((r, i) => {
      r.querySelector('input[name*="[name]"]').name = `raw_materials[${i}][name]`;
      r.querySelector('input[name*="[qty]"]').name = `raw_materials[${i}][qty]`;
      r.querySelector('input[name*="[unitCost]"]').name = `raw_materials[${i}][unitCost]`;
    });
  });
}

// Add a new service row
function addService() {
  const tbody = document.getElementById('services_body');
  const index = tbody.children.length;
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><input type="text" name="services[${index}][name]" class="w-full px-2 py-1 border srv_name" placeholder="Service Name"></td>
    <td><input type="number" name="services[${index}][cost]" class="w-full px-2 py-1 border srv_cost" value="0"></td>
    <td><button type="button" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded remove-btn" style="width: 140px;padding: 8px;border-radius: 8px;background-color: #e03a3aff;color: #fff;">Remove</button></td>
  `;
  tbody.appendChild(row);

  row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', recalcTotals));
  row.querySelector('.remove-btn').addEventListener('click', () => {
    row.remove();
    recalcTotals();
    // Re-index names after removal
    Array.from(tbody.children).forEach((r, i) => {
      r.querySelector('input[name*="[name]"]').name = `services[${i}][name]`;
      r.querySelector('input[name*="[cost]"]').name = `services[${i}][cost]`;
    });
  });
}

// Event listeners for adding rows and recalculating totals
document.getElementById('add_raw_material').addEventListener('click', addRawMaterial);

document.getElementById('add_service').addEventListener('click', addService);

document.getElementById('profit_margin').addEventListener('input', recalcTotals);

document.getElementById('quantity_produced').addEventListener('input', recalcTotals);

document.getElementById('production_status').addEventListener('change', handleProductionStatus);

document.getElementById('production_form').addEventListener('submit', function(e) {

    const status = document.getElementById('production_status').value;
    const margin = document.getElementById('profit_margin').value.trim();

    if (status === "Finished") {

        if (margin === "" || parseFloat(margin) <= 0) {

            alert("Please enter a valid profit margin before completing production.");

            document.getElementById('profit_margin').focus();

            e.preventDefault();

            return;
        }
    }

});
</script>


    </body>

</html>
