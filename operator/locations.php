<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


$companyid = $_SESSION['company_id'];
$user_id = $_SESSION['user_id'];

// Get user email
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_email = $user['email'] ?? null;

// Fetch locations with optional search
$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT l.*, u.email AS created_by_email
        FROM locations l
        LEFT JOIN users u ON l.created_by = u.id
        WHERE l.company_id = ? 
          AND l.name LIKE ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$companyid, '%' . $search . '%']);
} else {
    $stmt = $pdo->prepare("
        SELECT l.*, u.email AS created_by_email
        FROM locations l
        LEFT JOIN users u ON l.created_by = u.id
        WHERE l.company_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$companyid]);
}

$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo $company_name; ?> |  Staff Area</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
</head>

<body x-data="{ darkMode: false, sidebarToggle: false }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode}">
<div class="flex h-screen overflow-hidden">
    
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content Area -->
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <?php include('header.php'); ?>

        <!-- Page Content -->
        <main class="flex-1 p-4 md:p-6">
            <div class="flex flex-col gap-4 md:gap-6 w-full">

                <!-- Page Header + Search -->
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 md:gap-0 w-full">
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Locations</h2>
                    <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3 w-full md:w-auto">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search by location name..." class="w-full sm:w-64 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:text-white"/>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600" style="width: 100px;background-color: #02356e;color: #fff;border: none;border-radius: 8px;">Search</button>
                    </form>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 mt-4">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Type</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Created By</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Address</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
                        <?php if (count($locations) > 0): ?>
                            <?php foreach ($locations as $location): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-3"><?php echo htmlspecialchars($location['name']); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($location['type']); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($location['created_by_email'] ?? 'System'); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($location['address_line1']); ?></td>
                                <td class="px-6 py-3">
                                    <span class="<?php echo ($location['status'] == 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-2 py-1 rounded-full text-xs font-semibold">
                                        <?php echo ($location['status'] == 1) ? 'Active' : 'Suspended'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <form action="location-details.php" method="GET">
                                        <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium">View Data</button>
                                    </form>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <form action="location-details.php" method="GET">
                                        <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium">View Data</button>
                                    </form>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <form action="location-details.php" method="GET">
                                        <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium">View Data</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No locations found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden flex flex-col gap-4 mt-4">
                    <?php if (count($locations) > 0): ?>
                        <?php foreach ($locations as $location): ?>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800 shadow-sm w-full">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($location['name']); ?></h3>
                                <span class="<?php echo ($location['status'] == 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-2 py-1 rounded-full text-xs font-semibold">
                                    <?php echo ($location['status'] == 1) ? 'Active' : 'Suspended'; ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Type:</strong> <?php echo htmlspecialchars($location['type']); ?></p>
                            <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Created By:</strong> <?php echo htmlspecialchars($location['created_by_email'] ?? 'System'); ?></p>
                            <p class="text-sm text-gray-600 dark:text-gray-300"><strong>Address:</strong> <?php echo htmlspecialchars($location['address_line1']); ?></p>
                            <div class="mt-3 text-right">
                                <form action="location-details.php" method="GET">
                                    <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium">View Details</button>
                                </form>
                            </div>
                            <div class="mt-3 text-right">
                                <form action="location-edit.php" method="GET">
                                    <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium">edit</button>
                                </form>
                            </div>
                            <div class="mt-3 text-right">
                                <form action="location-delete.php" method="GET" onsubmit="return confirm('Are you sure you want to delete this location? This action cannot be undone.');">
                                    <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium">delete</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 mt-4">No locations found.</p>
                    <?php endif; ?>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <?php include('footer.php'); ?>
    </div>
</div>

<script defer src="bundle.js"></script>
</body>
</html>
