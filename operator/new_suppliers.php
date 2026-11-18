<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
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
              <div class="grid grid-cols-6 gap-4 md:gap-6">
              <!-- Breadcrumb Start -->
            <div x-data="{ pageName: ``}">
              <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
               
              <h3>Register Vendor</h3><i class="p-talk">
                  Aaaayah! Product Vendors thanks for coming onboard.....
                  </i>
                  <br><br>
                <nav>
                  <ol class="flex items-center gap-1.5">
                    <li> 
                      <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="index.php" >Home <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg" > <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" /></svg></a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName" ></li>
                  </ol>
                </nav>
              </div>
            </div>
            <!-- Breadcrumb End -->
                 <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                        <form method="POST" action="register_vendor.php">
  <div class="-mx-2.5 flex flex-wrap gap-y-5">
    
    <!-- Business Name -->
    <div class="w-full px-2.5 xl:w-1/2">
      <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        Business Name
      </label>
     
      <input type="text" name="business_name" placeholder="e.g. Male Fashion" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required/>
    </div>

    
    <!--  Office Address-->
    <div class="w-full px-2.5 xl:w-1/2">
      <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        Office Address
      </label>
     
      <input type="text" name="office_address" placeholder="" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required/>
    </div>

    <!--  Email Address-->
    <div class="w-full px-2.5 xl:w-1/2">
      <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        Email Address
      </label>
     
      <input type="email" name="email" placeholder="mail@example.com" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required/>
    </div>

    <!--  Mobile-->
    <div class="w-full px-2.5 xl:w-1/2">
      <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        Mobile
      </label>
     
      <input type="number" name="mobile" placeholder="" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" required/>
    </div>
    
    <!-- location begins -->
    <div class="w-full px-2.5 xl:w-1/2">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        Location
    </label>

    <select name="location_id" required  class="w-full px-4 py-3 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" >
        <option value="">-- Select Location --</option>

        <?php
            // Fetch all locations belonging to this company
            $stmt = $pdo->prepare("SELECT id, name, city, state, type FROM locations WHERE company_id = ?");
            $stmt->execute([$company_id]);
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($locations as $loc) {
                $id = $loc['id'];
                $name = ucfirst($loc['name']);
                $city = ucfirst($loc['city']);
                $state = ucfirst($loc['state']);
                $type = ucfirst($loc['type']);

                echo "<option value='$id'>$name ($type) — $city, $state</option>";
            }
        ?>
    </select>
    </div>
    <!-- location ends -->

    <!-- Description -->
    <div class="w-full px-2.5">
      <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        Description
      </label>
      <textarea name="description" placeholder="Brief description of this Vendor" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"></textarea>
    </div>

    <!-- Hidden Fields -->
    <input type="hidden" name="company_id" value="<?= htmlspecialchars($_SESSION['company_id']) ?>">

    <!-- Submit Buttons -->
    <div class="w-full px-2.5">
      <div class="flex items-center gap-3 mt-1">
        <button type="submit" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 hover:bg-brand-600">
          Register
        </button>
        <a href="categories.php" class="flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
          Cancel
        </a>
      </div>
    </div>

  </div>
</form>


                    </div>
                  </div>
                </div>
                <!-- ====== Metric Table Start -->
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

  <!-- Script for the unit reflection -->
  <script>
document.getElementById('unitTypeSelect').addEventListener('change', function () {
    let unitType = this.value;

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "fetch_units_by_type.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
        if (this.status === 200) {
            document.getElementById('unitSelect').innerHTML = this.responseText;
        }
    };

    xhr.send("unit_type=" + encodeURIComponent(unitType));
});
</script>
</body>

</html>
