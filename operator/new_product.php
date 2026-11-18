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
                <div class="grid grid-cols-6 gap-4 md:gap-6">
                  <div class="col-span-12 space-y-6 xl:col-span-7">
                    
                  <!-- ====== Metric Table Start -->
                  <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                      <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                      <form method="POST" action="create_product.php" enctype="multipart/form-data">
    <div class="-mx-2.5 flex flex-wrap gap-y-5">

      <!-- Product Name -->
      <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Product Name
        </label>
        <input type="text" name="name" placeholder="e.g. Men's Leather Jacket" required
          class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
      </div>
      <!-- Category -->
      <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Category
        </label>
        <select name="category_id" id="categorySelect" required
          class="w-full px-4 py-3 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg dark:bg-dark-900 h-11 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90">
          <option value="">-- Select Product Category --</option>
          <?php
            $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            function renderCategoryOptions($categories, $parentId = null, $prefix = '') {
                foreach ($categories as $cat) {
                    if ($cat['parent_id'] == $parentId) {
                        echo '<option value="' . $cat['id'] . '">' . $prefix . $cat['name'] . '</option>';
                        renderCategoryOptions($categories, $cat['id'], $prefix . '— ');
                    }
                }
            }
            renderCategoryOptions($categories);
          ?>
        </select>
      </div>
      <!-- AUTO INFO DISPLAY -->
      <!-- Product Unit Name -->
      <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Unit Name
        </label>
        <input type="text" name="unit_name" id="autoUnit"  placeholder="Product Unit will appear here" readonly
          class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />

          <input type="hidden" name="unit_id" id="autoUnitID">
          
      </div>

      <!-- Product Stock Type -->
      <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Stock Type
        </label>
        <input type="text" name="stock_type" id="autoStockType"  placeholder="Stock type will appear here" readonly
          class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
      </div>
   
      <!-- Cost Price -->
      <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Cost Price (₦)
        </label>
        <input type="number" name="cost_price" step="0.01" min="0" placeholder="e.g. 5000" required
          class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
      </div>

      <!-- Selling Price -->
      <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Selling Price (₦)
        </label>
        <input type="number" name="selling_price" step="0.01" min="0" placeholder="e.g. 6500" required
          class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
      </div>
    
      <!-- Description -->
      <div class="w-full px-2.5">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Product Description
        </label>
        <textarea name="description" rows="3" placeholder="Enter product details, features, etc."
          class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"></textarea>
      </div>

     
      
      <!-- Product Image -->
      <div class="w-full px-2.5 xl:w-1/2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
          Featured Image
        </label>
        <input type="file" name="featured_img" accept="image/*" required
          class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 file:mr-4 file:rounded-full file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
      </div>

      <!-- Hidden Fields -->
      <input type="hidden" name="company_id" value="<?= htmlspecialchars($_SESSION['company_id']) ?>">

      <!-- Submit Button -->
      <div class="w-full px-2.5">
        <div class="flex items-center gap-3 mt-1">
          <button type="submit"
            class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 hover:bg-brand-600">
            Add Product
          </button>
          <a href="products.php"
            class="flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
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
    <script>
document.addEventListener('DOMContentLoaded', function () {

    const categorySelect = document.getElementById('categorySelect');

    if (!categorySelect) return;

    categorySelect.addEventListener('change', function () {

        let id = this.value;
        if (!id) return;

        fetch('fetch_category.php?id=' + id)
        .then(res => res.json())
        .then(data => {

            console.log(data); // 🔥 debug

            document.getElementById('autoUnit').value = data.unit_name || '';
            document.getElementById('autoStockType').value = data.stock_type || '';

            document.getElementById('autoUnitID').value = data.unit_id || '';
            document.getElementById('unit_type').value = data.unit_type || '';
            document.getElementById('stock_type').value = data.stock_type || '';
        })
        .catch(err => console.error('AJAX ERROR:', err));

    });

});
</script>
  </body>

  </html>
