<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
      <title>
        <?php echo $app_name; ?> |  Group Chats
      </title>
      <link rel="icon" href="src/images/icons/favicon.png">
      <link rel="stylesheet" href="../fontawesome/css/all.css">
      <link href="style.css" rel="stylesheet">
      
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
                    <div class="sticky px-4 pb-4 pt-4 sm:px-5 sm:pt-5 xl:pb-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-theme-xl font-semibold text-gray-800 dark:text-white/90 sm:text-2xl" >
                                Company Group Chats
                                </h3>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="relative my-2 w-full">
                                <form>
                                <button class="absolute left-4 top-1/2 -translate-y-1/2">
                                    <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" >
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37381C3.04199 5.87712 5.87735 3.04218 9.37533 3.04218C12.8733 3.04218 15.7087 5.87712 15.7087 9.37381C15.7087 12.8705 12.8733 15.7055 9.37533 15.7055C5.87735 15.7055 3.04199 12.8705 3.04199 9.37381ZM9.37533 1.54218C5.04926 1.54218 1.54199 5.04835 1.54199 9.37381C1.54199 13.6993 5.04926 17.2055 9.37533 17.2055C11.2676 17.2055 13.0032 16.5346 14.3572 15.4178L17.1773 18.2381C17.4702 18.531 17.945 18.5311 18.2379 18.2382C18.5308 17.9453 18.5309 17.4704 18.238 17.1775L15.4182 14.3575C16.5367 13.0035 17.2087 11.2671 17.2087 9.37381C17.2087 5.04835 13.7014 1.54218 9.37533 1.54218Z" fill="" />
                                    </svg>
                                </button>
                                <input type="text" placeholder="Search..." class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-[42px] pr-3.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                                </form>
                            </div>
                        </div>
                    </div> 
                     <div class="flex max-h-full flex-col overflow-auto px-4 sm:px-5">
                        <div class="custom-scrollbar max-h-full space-y-1 overflow-auto">
                            <!-- Chat List Item Begins-->
                            <div class="flex cursor-pointer items-center gap-3 rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-white/[0.03]" >
                            <div class="relative h-12 w-full max-w-[48px] rounded-full">
                                <img src="src/images/user/user-18.jpg" alt="profile" class="h-full w-full overflow-hidden rounded-full object-cover object-center" />
                                <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900" ></span>
                            </div>
                            <div class="w-full">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h5 class="text-sm font-medium text-gray-800 dark:text-white/90" >
                                            Kaiya George
                                        </h5>
                                        <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400" >
                                            Project Manager
                                            </p>
                                    </div>
                                    <span class="text-theme-xs text-gray-400"> 15 mins </span>
                                </div>
                            </div>
                            <!-- Chat List Item Ends -->
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
     <!-- footer session begins -->
      <?php include('footer.php'); ?>
      <!-- footer session ends -->
    <!-- ===== Page Wrapper End ===== -->
  <script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script defer src="bundle.js"></script><script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}' crossorigin="anonymous"></script>
</body>

</html>
