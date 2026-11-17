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
        <?php echo $app_name; ?> |  Chat with Staffs
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
