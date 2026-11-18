<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');


  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Get the staff ID to update
         // Get submitted values, fallback to existing values if empty 
        $staffid     = $_POST['staffid'];

        
    // Step 1: Fetch company data
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
    $stmt->execute([$staffid]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    // Assign with fallback defaults
    $firstname     = $staff['firstname']           ?? 'Not Found';
    $profile          = $staff['profile']         ?? 'Not Found';
    
  
    // Optional: Stop if no data found at all
    if (!$staff) {
        echo "Staff ID not defined.";
        exit;
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
      <title>
        <?php echo $app_name;?> |  Chat with Staffs
      </title>
      <link rel="icon" href="src/images/icons/favicon.png">
      <link rel="stylesheet" href="../fontawesome/css/all.css">
      <link href="style.css" rel="stylesheet">
      <link href="dm.css" rel="stylesheet">
      
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
          
<!-- Chat Sidebar End -->

                <!-- Chat Box Start -->
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-2"/>
  <!-- ====== Chat Box Start -->
    <!-- Chat box head session begins -->
  <div class="sticky flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800 xl:px-6" >
    <div class="flex items-center gap-3">
      <div class="relative h-12 w-full max-w-[48px] rounded-full">
        <img src="../<?= htmlspecialchars($profile) ?> " alt="profile" class="h-full w-full overflow-hidden rounded-full object-cover object-center" />
        <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full border-[1.5px] border-white bg-success-500 dark:border-gray-900" ></span>
      </div>
      <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400">
        Chat with <?= htmlspecialchars($firstname) ?> 
      </h5>
    </div>
    <div class="flex items-center gap-3">
     
      <div x-data="{openDropDown: false}" class="relative -mb-1.5">
        <button @click="openDropDown = !openDropDown" :class="openDropDown ? 'text-gray-800 dark:text-white/90' : 'text-gray-700 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white/90'" >
          <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" >
            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.2441 6C10.2441 5.0335 11.0276 4.25 11.9941 4.25H12.0041C12.9706 4.25 13.7541 5.0335 13.7541 6C13.7541 6.9665 12.9706 7.75 12.0041 7.75H11.9941C11.0276 7.75 10.2441 6.9665 10.2441 6ZM10.2441 18C10.2441 17.0335 11.0276 16.25 11.9941 16.25H12.0041C12.9706 16.25 13.7541 17.0335 13.7541 18C13.7541 18.9665 12.9706 19.75 12.0041 19.75H11.9941C11.0276 19.75 10.2441 18.9665 10.2441 18ZM11.9941 10.25C11.0276 10.25 10.2441 11.0335 10.2441 12C10.2441 12.9665 11.0276 13.75 11.9941 13.75H12.0041C12.9706 13.75 13.7541 12.9665 13.7541 12C13.7541 11.0335 12.9706 10.25 12.0041 10.25H11.9941Z" fill="" />
          </svg>
        </button>
        <div x-show="openDropDown" @click.outside="openDropDown = false" class="absolute right-0 top-full z-40 w-40 space-y-1 rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark" >
          <a href="staff_chat.php" class="flex w-full rounded-lg px-3 py-2 text-left text-theme-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300" >
            Staffs Chat
          </a>
          <a href="staffs.php" class="flex w-full rounded-lg px-3 py-2 text-left text-theme-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300" >
            Staff List
          </a>
        </div>
      </div>
    </div>
  </div>
  <!-- chat box head sesion ends -->

  <div class="custom-scrollbar max-h-full flex-1 space-y-6 overflow-auto p-5 xl:space-y-8 xl:p-6" >
    <!-- sender bar begins -->
    <!-- <div class="max-w-[350px]">
      <div class="flex items-start gap-4">
        <div class="h-10 w-full max-w-10 rounded-full">
          <img src="src/images/user/user-17.jpg" alt="profile"  class="h-full w-full overflow-hidden rounded-full object-cover object-center" />
        </div>
        <div>
          <div class="rounded-lg rounded-tl-sm bg-gray-100 px-3 py-2 dark:bg-white/5" >
            <p class="text-sm text-gray-800 dark:text-white/90">
              I want to make an appointment tomorrow from 2:00 to 5:00pm?
            </p>
          </div>
          <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">Lindsey, 2 hours ago</p>
        </div>
      </div>
    </div> -->
    <!-- sender bar ends -->

    <!-- reciever bar begins -->
    <!-- <div class="ml-auto max-w-[350px] text-right">
      <div class="ml-auto max-w-max rounded-lg rounded-tr-sm bg-brand-500 px-3 py-2 dark:bg-brand-500" >
        <p class="text-sm text-white dark:text-white/90">
          If don’t like something, I’ll stay away from it.
        </p>
      </div>
      <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">2 hours ago</p>
    </div> -->

    <!-- reciever bar ends -->

      

    
<!-- image insertion bar begins for sender -->
    <!-- <div class="max-w-[350px]">
      <div class="flex items-start gap-4">
        <div class="h-10 w-full max-w-10 rounded-full">
          <img
            src="src/images/user/user-17.jpg"
            alt="profile"
            class="h-full w-full overflow-hidden rounded-full object-cover object-center"
          />
        </div>

        <div>
          <div class="mb-2 w-full max-w-[270px] overflow-hidden rounded-lg">
            <img src="src/images/chat/chat.jpg" alt="chat" />
          </div>
          <div
            class="max-w-max rounded-lg rounded-tl-sm bg-gray-100 px-3 py-2 dark:bg-white/5"
          >
            <p class="text-sm text-gray-800 dark:text-white/90">
              Please preview the image
            </p>
          </div>

          <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
            Lindsey, 2 hours ago
          </p>
        </div>
      </div>
    </div>
  </div> -->
<!-- image insertion bar for sender ends -->
  <div class="sticky bottom-0 border-t border-gray-200 p-3 dark:border-gray-800" style="position: fixed;width: 80%;background-color: #fff;" >
    <form class="flex items-center justify-between" method="POST" action="proceed_chat.php">
      <div class="relative w-full">
        <button class="absolute left-1 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90 sm:left-3" >
          <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" >
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM3.5 12C3.5 7.30558 7.30558 3.5 12 3.5C16.6944 3.5 20.5 7.30558 20.5 12C20.5 16.6944 16.6944 20.5 12 20.5C7.30558 20.5 3.5 16.6944 3.5 12ZM10.0001 9.23256C10.0001 8.5422 9.44042 7.98256 8.75007 7.98256C8.05971 7.98256 7.50007 8.5422 7.50007 9.23256V9.23266C7.50007 9.92301 8.05971 10.4827 8.75007 10.4827C9.44042 10.4827 10.0001 9.92301 10.0001 9.23266V9.23256ZM15.2499 7.98256C15.9403 7.98256 16.4999 8.5422 16.4999 9.23256V9.23266C16.4999 9.92301 15.9403 10.4827 15.2499 10.4827C14.5596 10.4827 13.9999 9.92301 13.9999 9.23266V9.23256C13.9999 8.5422 14.5596 7.98256 15.2499 7.98256ZM9.23014 13.7116C8.97215 13.3876 8.5003 13.334 8.17625 13.592C7.8522 13.85 7.79865 14.3219 8.05665 14.6459C8.97846 15.8037 10.4026 16.5481 12 16.5481C13.5975 16.5481 15.0216 15.8037 15.9434 14.6459C16.2014 14.3219 16.1479 13.85 15.8238 13.592C15.4998 13.334 15.0279 13.3876 14.7699 13.7116C14.1205 14.5274 13.1213 15.0481 12 15.0481C10.8788 15.0481 9.87961 14.5274 9.23014 13.7116Z" fill="" />
          </svg>
        </button>

        <input type="hidden" name="sender_id" value="$_SESSION['user_id']">
        <input type="hidden" name="reciever_id" value=".'$staffid'.">
        <input type="text" placeholder="Type a message" name="message" class="h-9 w-full border-none bg-transparent pl-12 pr-5 text-sm text-gray-800 outline-hidden placeholder:text-gray-400 focus:border-0 focus:ring-0 dark:text-white/90" />
      </div>

      <div class="flex items-center">
        <button class="mr-2 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90" >
          <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" >
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.9522 14.4422C12.9522 14.452 12.9524 14.4618 12.9527 14.4714V16.1442C12.9527 16.6699 12.5265 17.0961 12.0008 17.0961C11.475 17.0961 11.0488 16.6699 11.0488 16.1442V6.15388C11.0488 5.73966 10.7131 5.40388 10.2988 5.40388C9.88463 5.40388 9.54885 5.73966 9.54885 6.15388V16.1442C9.54885 17.4984 10.6466 18.5961 12.0008 18.5961C13.355 18.5961 14.4527 17.4983 14.4527 16.1442V6.15388C14.4527 6.14308 14.4525 6.13235 14.452 6.12166C14.4347 3.84237 12.5817 2 10.2983 2C8.00416 2 6.14441 3.85976 6.14441 6.15388V14.4422C6.14441 14.4492 6.1445 14.4561 6.14469 14.463V16.1442C6.14469 19.3783 8.76643 22 12.0005 22C15.2346 22 17.8563 19.3783 17.8563 16.1442V9.55775C17.8563 9.14354 17.5205 8.80775 17.1063 8.80775C16.6921 8.80775 16.3563 9.14354 16.3563 9.55775V16.1442C16.3563 18.5498 14.4062 20.5 12.0005 20.5C9.59485 20.5 7.64469 18.5498 7.64469 16.1442V9.55775C7.64469 9.55083 7.6446 9.54393 7.64441 9.53706L7.64441 6.15388C7.64441 4.68818 8.83259 3.5 10.2983 3.5C11.764 3.5 12.9522 4.68818 12.9522 6.15388L12.9522 14.4422Z" fill="" />
          </svg>
        </button>

        <button class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90" >
          <svg class="stroke-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" >
            <rect x="7" y="2.75" width="10" height="12.5" rx="5" stroke="" stroke-width="1.5" />
            <path d="M20 10.25C20 14.6683 16.4183 18.25 12 18.25C7.58172 18.25 4 14.6683 4 10.25" stroke="" stroke-width="1.5" stroke-linecap="round" />
            <path d="M10 21.25H14" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M12 18.25L12 21.25" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M12 7.5L12 10.5" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M14.5 8.25L14.5 9.75" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M9.5 8.25L9.5 9.75" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>

        <button class="ml-3 flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 text-white hover:bg-brand-600 xl:ml-5" >
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" >
            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.98481 2.44399C3.11333 1.57147 1.15325 3.46979 1.96543 5.36824L3.82086 9.70527C3.90146 9.89367 3.90146 10.1069 3.82086 10.2953L1.96543 14.6323C1.15326 16.5307 3.11332 18.4291 4.98481 17.5565L16.8184 12.0395C18.5508 11.2319 18.5508 8.76865 16.8184 7.961L4.98481 2.44399ZM3.34453 4.77824C3.0738 4.14543 3.72716 3.51266 4.35099 3.80349L16.1846 9.32051C16.762 9.58973 16.762 10.4108 16.1846 10.68L4.35098 16.197C3.72716 16.4879 3.0738 15.8551 3.34453 15.2223L5.19996 10.8853C5.21944 10.8397 5.23735 10.7937 5.2537 10.7473L9.11784 10.7473C9.53206 10.7473 9.86784 10.4115 9.86784 9.99726C9.86784 9.58304 9.53206 9.24726 9.11784 9.24726L5.25157 9.24726C5.2358 9.20287 5.2186 9.15885 5.19996 9.11528L3.34453 4.77824Z" fill="white" />
          </svg>
        </button>
      </div>
    </form>
  </div>
  <!-- ====== Chat Box End -->
</div>
<!-- Chat Box End -->
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
