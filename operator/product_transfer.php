<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
$branchid = $_SESSION['branch_id'];
$companyid = $_SESSION['company_id'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
<meta http-equiv="X-UA-Compatible" content="ie=edge" />
<title><?php echo $app_name; ?> | Product Transfer</title>
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
      <?php include('header.php'); ?>
      <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        <div class="grid grid-cols-6 gap-4 md:gap-6">
          <div class="col-span-12 space-y-6 xl:col-span-7">

            <!-- ====== Product Transfer Form Start ===== -->
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
              <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                <form method="POST" action="transfer_product.php">
                  <div class="flex flex-wrap gap-5">

                    <!-- Product -->
                    <div class="w-full xl:w-1/2">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Product
                      </label>
                      <select name="product_id" required class="w-full h-11 rounded-lg border px-4 py-2.5 dark:bg-dark-900 dark:text-white">
                        <option value="">-- Select Product --</option>
                        <?php
                          $stmt = $pdo->prepare("SELECT * FROM products WHERE company_id = ?");
                          $stmt->execute([$_SESSION['company_id']]);
                          $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          foreach($products as $p){
                              echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['name']).'</option>';
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Quantity -->
                    <div class="w-full xl:w-1/2">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Quantity
                      </label>
                      <input type="number" name="quantity" min="1" required class="w-full h-11 rounded-lg border px-4 py-2.5 dark:bg-dark-900 dark:text-white">
                    </div>

                    <!-- Source -->
                    <div class="w-full xl:w-1/2">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Source Branch/Warehouse
                      </label>
                      <select name="source_id" required class="w-full h-11 rounded-lg border px-4 py-2.5 dark:bg-dark-900 dark:text-white">
                        <option value="">-- Select Source --</option>
                        <?php
                          $stmt = $pdo->prepare("SELECT id, name FROM branches WHERE company_id = ?");
                          $stmt->execute([$_SESSION['company_id']]);
                          $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          foreach($branches as $b){
                              echo '<option value="branch_'.$b['id'].'">'.htmlspecialchars($b['name']).' (Branch)</option>';
                          }

                          $stmt = $pdo->prepare("SELECT id, name FROM warehouses WHERE company_id = ?");
                          $stmt->execute([$_SESSION['company_id']]);
                          $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          foreach($warehouses as $w){
                              echo '<option value="warehouse_'.$w['id'].'">'.htmlspecialchars($w['name']).' (Warehouse)</option>';
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Destination -->
                    <div class="w-full xl:w-1/2">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Destination Branch/Warehouse
                      </label>
                      <select name="destination_id" required class="w-full h-11 rounded-lg border px-4 py-2.5 dark:bg-dark-900 dark:text-white">
                        <option value="">-- Select Destination --</option>
                        <?php
                          foreach($branches as $b){
                              echo '<option value="branch_'.$b['id'].'">'.htmlspecialchars($b['name']).' (Branch)</option>';
                          }
                          foreach($warehouses as $w){
                              echo '<option value="warehouse_'.$w['id'].'">'.htmlspecialchars($w['name']).' (Warehouse)</option>';
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Notes -->
                    <div class="w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Notes / Reason
                      </label>
                      <textarea name="notes" rows="3" class="w-full rounded-lg border px-4 py-2.5 dark:bg-dark-900 dark:text-white"></textarea>
                    </div>

                    <!-- Submit -->
                    <div class="w-full">
                      <button type="submit" class="px-6 py-3 bg-brand-500 text-white rounded-lg hover:bg-brand-600">
                        Transfer Product
                      </button>
                    </div>

                  </div>
                </form>
              </div>
            </div>
            <!-- ====== Product Transfer Form End ===== -->

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
<script defer src="bundle.js"></script>
</body>
</html>
