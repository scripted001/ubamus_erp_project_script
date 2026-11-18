<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


$companyid = $_SESSION['company_id'];
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
                          <form method="POST" action="relab.php" id="production_form">
  <div class="grid grid-cols-6 gap-4 md:gap-6">
    <div class="col-span-12 space-y-6 xl:col-span-7 bg-white dark:bg-dark-900 p-6 rounded-2xl shadow-lg">
      <h2 class="text-2xl text-gray-500 dark:text-white thin-txt" >Pending Production??</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          
        </div> 
       <!-- stock production stages -->
<div>
<br>
  <select name="production_id" 
    class="w-full px-4 py-3 border rounded-lg dark:bg-dark-900 dark:text-white">
    <option value="">-- Select Batch Number  --</option>
    <?php
        $status = "In Progress";
        $stmt = $pdo->prepare("SELECT * FROM stock_production WHERE company_id = ? AND status = ?");
        $stmt->execute([$companyid, $status]);
        while($production = $stmt->fetch(PDO::FETCH_ASSOC)){
            echo '<option value="'.$production['id'].'">'.$production['product_name'].' ('.$production['batch_number'].')</option>
            ';
        }


    ?>
    

  </select>

</div>


      <!-- 6. Submit -->
      <div class="mt-6">
        <button type="submit" name="submit" class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg w-full text-center font-semibold" style="width: 340px;padding: 8px;border-radius: 8px;background-color: #6839ebff;color: #fff;">Next</button>
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
let rawMaterialsCount = 0;
let servicesCount = 0;

// Recalculate totals and unit price
function recalcTotals() {
  let totalMaterials = 0;
  document.querySelectorAll('.rm_total').forEach(el => totalMaterials += parseFloat(el.value||0));
  document.getElementById('total_materials_cost').innerText = totalMaterials.toFixed(2);

  let totalServices = 0;
  document.querySelectorAll('.srv_cost').forEach(el => totalServices += parseFloat(el.value||0));
  document.getElementById('total_services_cost').innerText = totalServices.toFixed(2);

  let totalProduction = totalMaterials + totalServices;
  document.getElementById('total_production_cost').innerText = totalProduction.toFixed(2);

  let qty = parseFloat(document.getElementById('quantity_produced').value || 1);
  let unitCost = totalProduction / qty;
  document.getElementById('unit_cost').value = unitCost.toFixed(2); // Show unit cost price

  let margin = parseFloat(document.getElementById('profit_margin').value || 0)/100;
  let unitSellingPrice = unitCost * (1 + margin);
  document.getElementById('unit_selling_price').value = unitSellingPrice.toFixed(2);

}

// Auto-generate batch number
function generateBatchNumber() {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  const random = Math.floor(Math.random() * 900 + 100);
  const batchNumber = `BATCH-${yyyy}${mm}${dd}-${random}`;
  document.getElementById('batch_number').value = batchNumber;
}

// Generate batch number on page load
window.addEventListener('DOMContentLoaded', generateBatchNumber);

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
</script>

    </body>

</html>
