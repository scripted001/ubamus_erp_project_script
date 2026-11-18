<?php
  include('../config/db.php');
  include('../config/validation.php');
  include('user_data.php');
  include('../app_data.php');
  include('../company_data.php');

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
          <link href="style.css" rel="stylesheet">
          <link href="main.css" rel="stylesheet">
    </head>
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
                    <!-- Metric Group One -->
                    
                  <!-- Metric Group One -->
                  <!-- ====== Metric Table Start -->
                  <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                      <div class="px-5 py-4 sm:px-6 sm:py-5">
                        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">Product Unit Management</h3>
                      </div>
                      <?php
$company_id = $_SESSION['company_id']; // or use your actual source

$stmt = $pdo->prepare("SELECT * FROM units WHERE company_id = ?");
$stmt->execute([$company_id]);
?>
<div class="w-full px-4 py-6">
  <div class="overflow-hidden rounded-lg shadow ring-1 ring-gray-200 dark:ring-gray-800">
    <!-- Desktop Table -->
    
    
<br>
<div class="flex gap-2 mb-4" style="margin-left: 20px;">
  <select id="search_by" class="border flex-1 px-3 py-2 rounded">
    <option value="unit_type">Type:</option>
    <option value="unit_name">Name</option>
  </select>

  <input 
    type="text" 
    id="search_value" 
    placeholder="Search..." 
    class="border px-3 py-2 rounded flex-1"
  />

  <button id="search_btn" class="bg-blue-600 text-white px-4 gap-2 py-2 rounded" style="width: 130px;background-color: #02356e;color: #fff;border: none;border-radius: 8px;">Search
  </button>
</div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-4" id="categoryCards">
      <?php
      $stmt->execute(); // re-execute for second loop
      while ($branch = $stmt->fetch(PDO::FETCH_ASSOC)):
      ?>
      <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-100 dark:border-gray-800 p-4 space-y-2">
        <div class="table-card">
  <span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Type:</span>
  <?= htmlspecialchars($branch['unit_type'] ?? '') ?>
</div>

<div class="table-card">
  <span class="font-semibold text-gray-600 dark:text-gray-400 subjects"> Unit Name:</span>
  <?= htmlspecialchars((string)($branch['unit_name'] ?? '')) ?>
</div>



<div class="text-right"style="width: 100%;height: 10%;display: flex;justify-content: flex-end;align-items: center;">
          <form method="POST" action="delete_category.php">
            <input type="hidden" name="category_id" value="<?= htmlspecialchars($branch['id']) ?>">
            <button name="submit" class="text-red-500 hover:text-red-700">
              <i class="fa-solid fa-trash fa-1x"></i>
            </button>
          </form>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>


                      </div>
                      <!-- Table Four -->
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
function loadCategories(){
    const search_by = document.getElementById('search_by').value;
    const search_value = document.getElementById('search_value').value;

    fetch(`search_units.php?search_by=${encodeURIComponent(search_by)}&search_value=${encodeURIComponent(search_value)}`)
    .then(res => res.text())
    .then(html => {
        document.getElementById('categoryCards').innerHTML = html;
    })
    .catch(err => console.error(err));
}

// Trigger search on button click
document.getElementById('search_btn').addEventListener('click', loadCategories);

// Trigger search on Enter key
document.getElementById('search_value').addEventListener('input', function(e){
    if(e.key === 'input') loadCategories();
});
</script>
  </body>

  </html>
