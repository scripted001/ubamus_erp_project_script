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
                        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">Company Registered Vendors</h3>
                      </div>
                      <div style="margin-bottom:15px;margin-left: 20px; display:flex; gap:10px; flex-wrap:wrap;">

<select id="search_option" style="padding:6px;border:1px solid #ddd;border-radius:6px;width: 150px;">
<option value="business_name">Business Name</option>
<option value="mobile">Mobile</option>
<option value="email">Email</option>

</select>

<input 
type="text" 
id="search_value" 
placeholder="Search..."
style="padding:6px;border:1px solid #ddd;border-radius:6px;width: 550px;"
>

</div>
                      <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                          <!-- Table Four -->
                         
                        </div>
                      <?php
   $stmt = $pdo->prepare("SELECT * FROM vendors WHERE company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
?>
<div class="w-full px-4 py-6">
  <div class="overflow-hidden rounded-lg shadow ring-1 ring-gray-200 dark:ring-gray-800">
    <!-- Desktop Table -->
    

    <!-- Mobile Cards -->
    <div id="staffResults" class="md:hidden space-y-4">
      <?php
      $stmt->execute(); // re-execute for second loop
      while ($branch = $stmt->fetch(PDO::FETCH_ASSOC)):
      ?>
      <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-100 dark:border-gray-800 p-4 space-y-2">
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Business Name:</span> <?= htmlspecialchars($branch['firstname']) ?> <?= htmlspecialchars($branch['business_name']) ?></div>
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Office Address:</span> <?= htmlspecialchars($branch['office_address']) ?></div>
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Mobile:</span> <?= htmlspecialchars($branch['mobile']) ?></div>
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Description:</span><?= htmlspecialchars($branch['description']) ?></div>
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Vendor Status:</span> <?= htmlspecialchars($branch['status']) ?></div>
        <div class="text-right" style="width: 100%; height: 10%;display: flex;justify-content: space-around">
          
          <form method="POST" action="suspend-vendor.php">
            <input type="hidden" name="vendor_id" value="<?= htmlspecialchars($branch['id']) ?>">
            <input type="hidden" name="vendor_email" value="<?= htmlspecialchars($branch['email']) ?>">

            <button name="submit" class="text-red-500 hover:text-red-700">
              Suspend
            </button>
          </form>
          <form method="POST" action="activate-vendor.php">
            <input type="hidden" name="vendor_id" value="<?= htmlspecialchars($branch['id']) ?>">
            <input type="hidden" name="vendor_email" value="<?= htmlspecialchars($branch['email']) ?>">

            <button name="submit" class="text-red-500 hover:text-red-700">
              Activate
            </button>
          </form>
          <form method="POST" action="delete_vendor.php">
            <input type="hidden" name="vendor_id" value="<?= htmlspecialchars($branch['id']) ?>">
            <input type="hidden" name="vendor_email" value="<?= htmlspecialchars($branch['email']) ?>">
            <button name="submit" class="text-red-500 hover:text-red-700">
              Delete
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>

$(document).ready(function(){

function searchStaff(){

var option = $("#search_option").val();
var value  = $("#search_value").val();

$.ajax({
url: "ajax_search_staff.php",
method: "POST",
data:{
search_option:option,
search_value:value
},
success:function(data){
$("#staffResults").html(data);
}
});

}

$("#search_value").keyup(function(){
searchStaff();
});

$("#search_option").change(function(){
searchStaff();
});

});

</script>
  </body>

  </html>
