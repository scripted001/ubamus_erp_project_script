<?php
  include('../config/db.php');
  include('../config/validation.php');
 
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
                        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">Company Registered Customers</h3>
                      </div>
                      <div style="margin-bottom:15px;margin-left: 20px; display:flex; gap:10px; flex-wrap:wrap;">

<select id="search_option" style="padding:6px;border:1px solid #ddd;border-radius:6px;width: 150px;">
<option value="fullname">Fullname</option>
<option value="phone">Mobile</option>
<option value="email">Email</option>
</select>

<input 
type="text" 
id="search_value" 
placeholder="Search customer..."
style="padding:6px;border:1px solid #ddd;border-radius:6px;width: 550px;"
>

</div>
                      <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                          <!-- Table Four -->
                          <!-- <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="flex flex-col gap-5 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
                              
                              <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <form>
                                  <div class="relative">
                                    <span class="absolute -translate-y-1/2 pointer-events-none top-1/2 left-4">
                                      <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37381C3.04199 5.87712 5.87735 3.04218 9.37533 3.04218C12.8733 3.04218 15.7087 5.87712 15.7087 9.37381C15.7087 12.8705 12.8733 15.7055 9.37533 15.7055C5.87735 15.7055 3.04199 12.8705 3.04199 9.37381ZM9.37533 1.54218C5.04926 1.54218 1.54199 5.04835 1.54199 9.37381C1.54199 13.6993 5.04926 17.2055 9.37533 17.2055C11.2676 17.2055 13.0032 16.5346 14.3572 15.4178L17.1773 18.2381C17.4702 18.531 17.945 18.5311 18.2379 18.2382C18.5308 17.9453 18.5309 17.4704 18.238 17.1775L15.4182 14.3575C16.5367 13.0035 17.2087 11.2671 17.2087 9.37381C17.2087 5.04835 13.7014 1.54218 9.37533 1.54218Z" fill=""/>
                                      </svg>
                                    </span>
                                    <input type="text" placeholder="Search..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-[42px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[300px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"/>
                                  </div>
                                </form>
                              <div>
                              <button class="text-theme-sm shadow-theme-xs inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                <svg class="stroke-current fill-white dark:fill-gray-800" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M2.29004 5.90393H17.7067" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                  <path d="M17.7075 14.0961H2.29085" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                  <path d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z" fill="" stroke="" stroke-width="1.5" />
                                  <path d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z" fill="" stroke="" stroke-width="1.5" />
                                </svg> 
                                Filter
                              </button>
                            </div>
                          </div> -->
                        </div>
                      <?php
   $stmt = $pdo->prepare("SELECT * FROM customers WHERE company_id = ?");
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
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Fullname:</span> <?= htmlspecialchars($branch['fullname']) ?></div>
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Email:</span><?= htmlspecialchars($branch['email']) ?></div>
        <div class="table-card"><span class="font-semibold text-gray-600 dark:text-gray-400 subjects">Mobile:</span> <?= htmlspecialchars($branch['phone']) ?></div>

        
<div class="text-right" style="width:100%;height:10%;display:flex;justify-content:space-around">

<!-- <form method="POST" action="staff.php">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">View</button>
</form>

<form method="POST" action="suspend-staff.php">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">Suspend</button>
</form>

<form method="POST" action="activate-staff.php">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">Activate</button>
</form> -->

<form method="POST" action="delete_customer.php"
onsubmit="return confirm('Do you want to proceed deleting this customer?');">
<input type="hidden" name="staff_id" value="<?= htmlspecialchars($branch['id']) ?>">
<input type="hidden" name="staff_email" value="<?= htmlspecialchars($branch['email']) ?>">
<button name="submit" class="text-red-500 hover:text-red-700">Delete</button>
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
url: "ajax_search_customer.php",
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
