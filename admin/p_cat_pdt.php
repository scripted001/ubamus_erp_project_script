<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');



if (isset($_POST['submit'])) {
    // Sanitize input
    $categoryID = $_POST['category_id'];

    // Step 1: Fetch category data
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->execute([$categoryID]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    // Optional: Stop if no data found at all
    if (!$category) {
        echo "No category found with this ID.";
        exit;
    }

    // Assign values with fallback defaults
    $catID       = $category['id']         ?? 'Not Found';
    $name        = $category['name']         ?? 'Not Found';
    $slug        = $category['slug']         ?? 'Not Found';
    $parentID    = $category['parent_id']    ?? null;
    $unit_type       = $category['unit_type']        ?? 'Not Found';
    $stock_type       = $category['stock_type']        ?? 'Not Found';
    $description = $category['description']  ?? 'Not Found';
    $icon        = $category['icon']         ?? 'Not Found';
    $status      = $category['status']       ?? 'Not Found';
    $created_at  = $category['created_at']   ?? 'Not Found';

    // ORIGINAL created_by (user id)
    $created_by_id = $category['created_by'] ?? null;

    
// <option value="0">-- Select Stock Type --</option>
// <option value="01">Sales Only</option>
// <option value="10">Service Only </option>
// <option value="100">Production Only</option>
// <option value="1001">Internal Use (Non-Commercial)</option>


   

    /* ---------------------------------------
       STEP 1: GET USER EMAIL FROM USERS TABLE
    ----------------------------------------*/
    $fullname = 'Not Found';

    if ($created_by_id) {

        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$created_by_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            $email = $user['email'];

            /* ---------------------------------------
               STEP 2: MATCH EMAIL IN STAFFS TABLE
            ----------------------------------------*/

            $stmt = $pdo->prepare("SELECT firstname, lastname FROM staffs WHERE email = ?");
            $stmt->execute([$email]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($staff) {
                $fullname = $staff['firstname'] . ' ' . $staff['lastname'];
            }
        }
    }

    // Replace created_by with fullname
    $created_by = $fullname;


    // Step 2: Check for parent category if exists
    if (!empty($parentID)) {
        $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
        $stmt->execute([$parentID]);
        $parentCategory = $stmt->fetch(PDO::FETCH_ASSOC);
        $parentName = $parentCategory['name'] ?? 'Parent ID not found';
    } else {
        $parentName = 'This is a Parent Category';
    }


    // Step 3: Fetch company details
    try {
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$_SESSION['company_id']]);
        $companyData = $stmt->fetch(PDO::FETCH_ASSOC);

        $companyName    = $companyData['name']    ?? 'Not Found';
        $companyAddress = $companyData['address'] ?? 'Not Found';

    } catch (PDOException $e) {
        echo "Company data fetch error: " . $e->getMessage();
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>  <?php echo $app_name;?> | <?= htmlspecialchars($name) ?> </title>
    <link rel="icon" href="src/images/icons/favicon.png">
      <link rel="stylesheet" href="../fontawesome/css/all.css">
      <link href="style.css" rel="stylesheet">
      <link href="modal.css" rel="stylesheet">
      <script data-cfasync="false" nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86">try{(function(w,d){!function(fv,fw,fx,fy){if(fv.zaraz)console.error("zaraz is loaded twice");else{fv[fx]=fv[fx]||{};fv[fx].executed=[];fv.zaraz={deferred:[],listeners:[]};fv.zaraz._v="5858";fv.zaraz._n="ff1f9a24-18a4-4614-8917-7c27c5c96a86";fv.zaraz.q=[];fv.zaraz._f=function(fz){return async function(){var fA=Array.prototype.slice.call(arguments);fv.zaraz.q.push({m:fz,a:fA})}};for(const fB of["track","set","debug"])fv.zaraz[fB]=fv.zaraz._f(fB);fv.zaraz.init=()=>{var fC=fw.getElementsByTagName(fy)[0],fD=fw.createElement(fy),fE=fw.getElementsByTagName("title")[0];fE&&(fv[fx].t=fw.getElementsByTagName("title")[0].text);fv[fx].x=Math.random();fv[fx].w=fv.screen.width;fv[fx].h=fv.screen.height;fv[fx].j=fv.innerHeight;fv[fx].e=fv.innerWidth;fv[fx].l=fv.location.href;fv[fx].r=fw.referrer;fv[fx].k=fv.screen.colorDepth;fv[fx].n=fw.characterSet;fv[fx].o=(new Date).getTimezoneOffset();if(fv.dataLayer)for(const fF of Object.entries(Object.entries(dataLayer).reduce(((fG,fH)=>({...fG[1],...fH[1]})),{})))zaraz.set(fF[0],fF[1],{scope:"page"});fv[fx].q=[];for(;fv.zaraz.q.length;){const fI=fv.zaraz.q.shift();fv[fx].q.push(fI)}fD.defer=!0;for(const fJ of[localStorage,sessionStorage])Object.keys(fJ||{}).filter((fL=>fL.startsWith("_zaraz_"))).forEach((fK=>{try{fv[fx]["z_"+fK.slice(7)]=JSON.parse(fJ.getItem(fK))}catch{fv[fx]["z_"+fK.slice(7)]=fJ.getItem(fK)}}));fD.referrerPolicy="origin";fD.src="cdn-cgi/zaraz/sd0d9.js?z="+btoa(encodeURIComponent(JSON.stringify(fv[fx])));fC.parentNode.insertBefore(fD,fC)};["complete","interactive"].includes(fw.readyState)?zaraz.init():fv.addEventListener("DOMContentLoaded",zaraz.init)}}(w,d,"zarazData","script");window.zaraz._p=async eC=>new Promise((eD=>{if(eC){eC.e&&eC.e.forEach((eE=>{try{const eF=d.querySelector("script[nonce]"),eG=eF?.nonce||eF?.getAttribute("nonce"),eH=d.createElement("script");eG&&(eH.nonce=eG);eH.innerHTML=eE;eH.onload=()=>{d.head.removeChild(eH)};d.head.appendChild(eH)}catch(eI){console.error(`Error executing script: ${eE}\n`,eI)}}));Promise.allSettled((eC.f||[]).map((eJ=>fetch(eJ[0],eJ[1]))))}eD()}));zaraz._p({"e":["(function(w,d){})(window,document)"]});})(window,document)}catch(e){throw fetch("/cdn-cgi/zaraz/t"),e;};</script></head>
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
          <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
            <!-- Breadcrumb Start -->
            <div x-data="{ pageName: ``}">
              <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
               
              <h3>Product Category Profile</h3><i class="p-talk">
                  Here! we profile every product according to her categories...
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

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6" >
              <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-7" >
                
              </h3>
              <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6" >
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between" >
                  <div class="flex flex-col items-center w-full gap-6 xl:flex-row" >
                    <div class="order-3 xl:order-2">
                      <h4 class="mb-2 text-lg font-semibold text-center text-gray-800 dark:text-white/90 xl:text-left" >
                        <?= htmlspecialchars($name) ?>
                      </h4>
                      
                    </div>
                  </div>
                
                </div>
              </div>
              
              <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6" >
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between" >
                  <div>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6" >
                      Category Details
                    </h4>
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32" >
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Category Name:</p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($name) ?></p>
                      </div>
                      <div> 
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Parent Category: </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($parentName) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Stock Type: </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($stock_type) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Product Unit Type: </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($unit_type) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Description </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($description) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" >  Created By </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($created_by) ?> </p>
                      </div>
                       <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" >  Date Created </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($created_at) ?> </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
        </main>
        
        <!-- ===== Main Content End ===== -->
        
      </div>
      <!-- ===== Content Area End ===== -->
    </div>
    

    <!-- ===== Page Wrapper End ===== -->
   <!-- footer session begins -->
      <?php include('footer.php'); ?>
      <!-- footer session ends -->
      
  <script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script defer src="bundle.js"></script><script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"95aebaa31b75b6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}' crossorigin="anonymous"></script>
</body>

</html>
