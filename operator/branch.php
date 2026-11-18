 <?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');

if (isset($_POST['submit'])) {
    // Sanitize input
    $branch_id = $_POST['branch_id'];

    // Step 1: Fetch company data
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
    $stmt->execute([$branch_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    // Assign with fallback defaults
    $company_id     = $company['company_id']           ?? 'Not Found';
    $name          = $company['name']         ?? 'Not Found';
    $address        = $company['address']       ?? 'Not Found';
    $phone          = $company['phone']         ?? 'Not Found';
    $email          = $company['email']         ?? 'Not Found';
    $city      = $company['city']     ?? 'Not Found';
    $state         = $company['state']        ?? 'Not Found';
    $status         = $company['status']        ?? 'Not Found';
    $created_at         = $company['created_at']        ?? 'Not Found';
  
    // Optional: Stop if no data found at all
    if (!$company) {
        echo "No branch found with this ID.";
        exit;
    }

    // Step 2: Fetch related user data (staff)
    try {
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $company_name = $user_data['name'] ?? 'Not Found';
        $logo = $user_data['logo'] ?? 'Not Found';
        

    } catch (PDOException $e) {
        echo "User data fetch error: " . $e->getMessage();
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
    <title> <?php echo $app_name;?> | Company Profile</title>
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
            <div x-data="{ pageName: `<?= htmlspecialchars($name) ?> Profile`}">
              <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName" ></h2>
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
                    <div class="w-20 h-20 overflow-hidden border border-gray-200 rounded-full dark:border-gray-800" >
                      <img src="../<?= htmlspecialchars($logo) ?>" alt="company Logo"  style="width: 100%;"/>
                    </div>
                    <div class="order-3 xl:order-2">
                      <h4 class="mb-2 text-lg font-semibold text-center text-gray-800 dark:text-white/90 xl:text-left" >
                       Branch Name:  <?= htmlspecialchars($name) ?>
                      </h4>
                      <div class="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left" >
                        <p class="text-sm text-gray-500 dark:text-gray-400">Address: <?= htmlspecialchars($address) ?></p>
                        <div class="hidden h-3.5 w-px bg-gray-300 dark:bg-gray-700 xl:block" ></div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Mobile: <?= htmlspecialchars($phone) ?></p>
                      </div>
                    </div>
                  </div>
                  <form action="edit_branch.php" method="POST">
                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($company_id) ?>">
                    <input type="hidden" name="branch_id" value="<?= htmlspecialchars($branch_id) ?>">
                    <button name="submit" @click="isProfileInfoModal = true" class="flex w-full items-center justify-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 lg:inline-flex lg:w-auto" >
                      <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" >
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M15.0911 2.78206C14.2125 1.90338 12.7878 1.90338 11.9092 2.78206L4.57524 10.116C4.26682 10.4244 4.0547 10.8158 3.96468 11.2426L3.31231 14.3352C3.25997 14.5833 3.33653 14.841 3.51583 15.0203C3.69512 15.1996 3.95286 15.2761 4.20096 15.2238L7.29355 14.5714C7.72031 14.4814 8.11172 14.2693 8.42013 13.9609L15.7541 6.62695C16.6327 5.74827 16.6327 4.32365 15.7541 3.44497L15.0911 2.78206ZM12.9698 3.84272C13.2627 3.54982 13.7376 3.54982 14.0305 3.84272L14.6934 4.50563C14.9863 4.79852 14.9863 5.2734 14.6934 5.56629L14.044 6.21573L12.3204 4.49215L12.9698 3.84272ZM11.2597 5.55281L5.6359 11.1766C5.53309 11.2794 5.46238 11.4099 5.43238 11.5522L5.01758 13.5185L6.98394 13.1037C7.1262 13.0737 7.25666 13.003 7.35947 12.9002L12.9833 7.27639L11.2597 5.55281Z" fill="" />
                      </svg>
                      Edit
                    </button>
                  </form>
                </div>
              </div>
              <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6" >
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between" >
                  <div>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6" >
                      Branch Information
                    </h4>
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32" >
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Branch Name:</p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($name) ?> </p>
                      </div>
                      <div> 
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Mother Company: </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($company_name) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Branch Address:  </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($address) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Mobile </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" ><?= htmlspecialchars($phone) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Email </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($email) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" >  State </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($state) ?> </p>
                      </div>
                      <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Current Status </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($status) ?> </p>
                      </div>
                       <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400" > Date Registered </p> <p class="text-sm font-medium text-gray-800 dark:text-white/90" > <?= htmlspecialchars($created_at) ?> </p>
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
