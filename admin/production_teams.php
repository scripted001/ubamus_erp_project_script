<?php
  include('../config/db.php');
  include('../config/validation.php');
  include('user_data.php');
  include('../app_data.php');
  include('../company_data.php');

  $company_id = $_SESSION['company_id'];

  // fetch location id 
  $stmt = $pdo->prepare("SELECT * FROM locations WHERE company_id = ?");
  $stmt ->execute([$company_id]);


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
          <link href="design.css" rel="stylesheet">
          <link href="main.css" rel="stylesheet">
          <link href="product-table.css" rel="stylesheet">
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
              <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6  overflow-x-auto">
                <div class="grid grid-cols-6 gap-4 md:gap-6">
                  <div class="col-span-12 space-y-6 xl:col-span-7">
                    <!-- Metric Group One -->
                    
                  <!-- Metric Group One -->
                  <!-- ====== Metric Table Start -->
                  <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
          <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-400 dark:text-white/90">We're the Creators!</h3>
          </div>
          <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
          <div style="margin-bottom:15px; display:flex; gap:10px;">
            
            <form action="proceed_team.php" method="POST" class="min-form">
                <div>
                    <input type="text" name ="name" placeholder="Team name"> 
                   <select name="location_id" id="select_tag">
                    <option value="">Select location</option>
        <?php
        $stmt = $pdo->prepare("SELECT id, name FROM locations WHERE company_id = ?");
        $stmt->execute([$_SESSION['company_id']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $location) {
            echo '<option value="'.$location['id'].'">'.htmlspecialchars($location['name']).'</option>';
        }
        ?>
                   </select>
                </div>
                <span id="btn-middle">
                  <button class="team-btn" value="submit">Register</button>
                </span>
               
            </form>
          </div>
          <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
            <div class="table-responsive">
              <table class="product-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Team Name</th>
                  </tr>
                </thead>
                <tbody id="productTable">
                  <?php
                    $count = 1; 
                      $stmt = $pdo->prepare("SELECT * FROM production_team WHERE company_id = ?");
                      $stmt->execute([$company_id]);
                      
                      
                      while($team = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo ' <tr>
                        <td class="text-center text-gray-400 py-4">'.$count++.'</td>
                        <td class="text-center text-gray-400 py-4">'.$team['name'].'</td>
                      </tr>';
                      }
                 
               
                    ?>
                </tbody>
              </table>
            </div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>    

  </body>

  </html>
