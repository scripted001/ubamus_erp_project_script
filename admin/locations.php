
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
        SELECT 
            l.*,
            u.email AS created_by_email
        FROM locations l
        LEFT JOIN users u ON l.created_by = u.id
        WHERE l.company_id = ?
          AND l.name LIKE ?
        ORDER BY l.created_at DESC
    ");

    $stmt->execute([
        $companyid,
        '%' . $search . '%'
    ]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            u.email AS created_by_email
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

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?php echo htmlspecialchars($company_name); ?> | Staff Area
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>
        /* ============================================================
           LOCATIONS PAGE RESPONSIVE STYLES
        ============================================================ */

        .locations-page {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        .locations-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .locations-title {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        .dark .locations-title {
            color: #fff;
        }

        .locations-heading-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .register-location-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 0 18px;
            border: 1px solid #02356e;
            border-radius: 8px;
            background: linear-gradient(135deg, #034985, #02356e);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(2, 53, 110, .14);
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .register-location-button:hover {
            background: linear-gradient(135deg, #0560a8, #034985);
            box-shadow: 0 7px 16px rgba(2, 53, 110, .22);
            transform: translateY(-1px);
            color: #fff;
        }

        .register-location-button:focus-visible {
            outline: 3px solid rgba(37, 99, 235, .25);
            outline-offset: 3px;
        }

        .dark .register-location-button {
            border-color: #2563eb;
        }

        .location-search-form {
            display: flex;
            align-items: center;
            gap: 10px;
            width: auto;
        }

        .location-search-input {
            width: 280px;
            min-width: 0;
            height: 44px;
            padding: 0 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #1f2937;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .location-search-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .dark .location-search-input {
            background: #111827;
            border-color: #374151;
            color: #fff;
        }

        .location-search-button {
            height: 44px;
            min-width: 100px;
            padding: 0 18px;
            border: 0;
            border-radius: 8px;
            background: #02356e;
            color: #fff;
            cursor: pointer;
            font-weight: 500;
            transition: background .2s;
        }

        .location-search-button:hover {
            background: #034985;
        }

        /* ============================================================
           DESKTOP TABLE
        ============================================================ */

        .locations-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        .dark .locations-table-wrapper {
            border-color: #374151;
            background: #1f2937;
        }

        .locations-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .locations-table th {
            padding: 14px 18px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .dark .locations-table th {
            background: #111827;
            color: #9ca3af;
            border-color: #374151;
        }

        .locations-table td {
            padding: 14px 18px;
            font-size: 14px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .dark .locations-table td {
            color: #e5e7eb;
            border-color: #374151;
        }

        .locations-table tbody tr:hover {
            background: #f9fafb;
        }

        .dark .locations-table tbody tr:hover {
            background: #374151;
        }

        .location-name {
            font-weight: 600;
            color: #111827;
        }

        .dark .location-name {
            color: #fff;
        }

        .location-address {
            max-width: 260px;
            white-space: normal;
            word-break: break-word;
        }

        /* ============================================================
           STATUS
        ============================================================ */

        .location-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .location-status.active {
            background: #dcfce7;
            color: #166534;
        }

        .location-status.suspended {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ============================================================
           ACTIONS
        ============================================================ */

        .location-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .location-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 7px 11px;
            border-radius: 7px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
            transition: all .2s ease;
        }

        .location-action-view {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .location-action-view:hover {
            background: #dbeafe;
        }

        .location-action-edit {
            background: #f3f4f6;
            color: #374151;
        }

        .location-action-edit:hover {
            background: #e5e7eb;
        }

        .location-action-delete {
            background: #fef2f2;
            color: #dc2626;
        }

        .location-action-delete:hover {
            background: #fee2e2;
        }

        .dark .location-action-view {
            background: #172554;
            color: #93c5fd;
        }

        .dark .location-action-edit {
            background: #374151;
            color: #e5e7eb;
        }

        .dark .location-action-delete {
            background: #450a0a;
            color: #fca5a5;
        }

        /* ============================================================
           MOBILE CARDS
        ============================================================ */

        .locations-mobile {
            display: none;
        }

        .location-card {
            width: 100%;
            box-sizing: border-box;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
        }

        .dark .location-card {
            border-color: #374151;
            background: #1f2937;
        }

        .location-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .location-card-name {
            min-width: 0;
            margin: 0;
            font-size: 18px;
            line-height: 1.35;
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        .dark .location-card-name {
            color: #fff;
        }

        .location-card-details {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .location-card-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 14px;
            line-height: 1.45;
        }

        .location-card-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .dark .location-card-label {
            color: #9ca3af;
        }

        .location-card-value {
            color: #374151;
            word-break: break-word;
        }

        .dark .location-card-value {
            color: #e5e7eb;
        }

        .location-card-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 16px;
        }

        .location-card-actions .location-action {
            width: 100%;
            min-width: 0;
        }

        .locations-empty {
            padding: 40px 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .dark .locations-empty {
            color: #9ca3af;
        }

        /* ============================================================
           TABLET
        ============================================================ */

        @media (max-width: 900px) {

            .locations-header {
                align-items: stretch;
            }

            .location-search-form {
                width: 100%;
            }

            .location-search-input {
                flex: 1;
                width: auto;
            }

            .locations-table {
                min-width: 760px;
            }
        }

        /* ============================================================
           MOBILE
        ============================================================ */

        @media (max-width: 767px) {

            .locations-page {
                padding: 0;
            }

            .locations-header {
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
            }

            .locations-heading-actions {
                justify-content: space-between;
                width: 100%;
            }

            .locations-title {
                font-size: 21px;
            }

            .register-location-button {
                min-height: 42px;
                padding: 0 13px;
                font-size: 13px;
            }

            .location-search-form {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 8px;
                width: 100%;
            }

            .location-search-input {
                width: 100%;
                height: 42px;
            }

            .location-search-button {
                height: 42px;
                min-width: 90px;
                padding: 0 14px;
            }

            .locations-table-wrapper {
                display: none;
            }

            .locations-mobile {
                display: flex;
                flex-direction: column;
                gap: 12px;
                width: 100%;
            }

            .location-card-actions {
                grid-template-columns: 1fr;
            }

            .location-card-actions .location-action {
                min-height: 40px;
            }
        }

        /* ============================================================
           VERY SMALL PHONES
        ============================================================ */

        @media (max-width: 380px) {

            .location-search-form {
                grid-template-columns: 1fr;
            }

            .location-search-button {
                width: 100%;
            }

            .location-card {
                padding: 14px;
            }

            .location-card-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

</head>

<body
    x-data="{ darkMode: false, sidebarToggle: false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode}"
>

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content Area -->
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900">

        <!-- Header -->
        <?php include('header.php'); ?>

        <!-- Page Content -->
        <main class="flex-1 p-4 md:p-6">

            <div class="locations-page">

                <!-- =====================================================
                     PAGE HEADER + SEARCH
                ====================================================== -->

                <div class="locations-header">

                    <div class="locations-heading-actions">
                        <h2 class="locations-title">
                            Locations
                        </h2>

                        <a
                            href="location.php"
                            class="register-location-button"
                        >
                            <i class="fa-solid fa-location-dot"></i>
                            <span>Register Location</span>
                        </a>
                    </div>

                    <form
                        method="GET"
                        class="location-search-form"
                    >

                        <input
                            type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            placeholder="Search by location name..."
                            class="location-search-input"
                        >

                        <button
                            type="submit"
                            class="location-search-button"
                        >
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Search</span>
                        </button>

                    </form>

                </div>


                <!-- =====================================================
                     DESKTOP TABLE
                ====================================================== -->

                <div class="locations-table-wrapper">

                    <table class="locations-table">

                        <thead>

                            <tr>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Created By
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    Status
                                </th>

                                <th style="text-align:center;">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (count($locations) > 0): ?>

                            <?php foreach ($locations as $location): ?>

                                <tr>

                                    <!-- Name -->
                                    <td>
                                        <span class="location-name">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </span>
                                    </td>

                                    <!-- Type -->
                                    <td>
                                        <?php echo htmlspecialchars($location['type']); ?>
                                    </td>

                                    <!-- Created By -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $location['created_by_email'] ?? 'Administrator'
                                        );
                                        ?>
                                    </td>

                                    <!-- Address -->
                                    <td class="location-address">
                                        <?php echo htmlspecialchars($location['address_line1']); ?>
                                    </td>

                                    <!-- Status -->
                                    <td>

                                        <?php if ((int)$location['status'] === 1): ?>

                                            <span class="location-status active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="location-status suspended">
                                                Suspended
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- Actions -->
                                    <td>

                                        <div class="location-actions">

                                            <!-- View -->
                                            <form
                                                action="location-details.php"
                                                method="GET"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="location_id"
                                                    value="<?php echo (int)$location['id']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="location-action location-action-view"
                                                >
                                                    <i class="fa-solid fa-eye"></i>
                                                    &nbsp;View
                                                </button>

                                            </form>


                                            <!-- Edit -->
                                            <form
                                                action="location-edit.php"
                                                method="GET"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="location_id"
                                                    value="<?php echo (int)$location['id']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="location-action location-action-edit"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                    &nbsp;Edit
                                                </button>

                                            </form>


                                            <!-- Delete -->
                                            <form
                                                action="location-delete.php"
                                                method="GET"
                                                onsubmit="return confirm('Are you sure you want to delete this location? This action cannot be undone.');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="location_id"
                                                    value="<?php echo (int)$location['id']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="location-action location-action-delete"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                    &nbsp;Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="locations-empty"
                                >
                                    No locations found.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- =====================================================
                     MOBILE CARDS
                ====================================================== -->

                <div class="locations-mobile">

                    <?php if (count($locations) > 0): ?>

                        <?php foreach ($locations as $location): ?>

                            <div class="location-card">

                                <!-- Card Header -->
                                <div class="location-card-header">

                                    <h3 class="location-card-name">
                                        <?php echo htmlspecialchars($location['name']); ?>
                                    </h3>

                                    <?php if ((int)$location['status'] === 1): ?>

                                        <span class="location-status active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="location-status suspended">
                                            Suspended
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- Details -->
                                <div class="location-card-details">

                                    <div class="location-card-row">

                                        <span class="location-card-label">
                                            Type
                                        </span>

                                        <span class="location-card-value">
                                            <?php echo htmlspecialchars($location['type']); ?>
                                        </span>

                                    </div>


                                    <div class="location-card-row">

                                        <span class="location-card-label">
                                            Created By
                                        </span>

                                        <span class="location-card-value">
                                            <?php
                                            echo htmlspecialchars(
                                                $location['created_by_email'] ?? 'Administrator'
                                            );
                                            ?>
                                        </span>

                                    </div>


                                    <div class="location-card-row">

                                        <span class="location-card-label">
                                            Address
                                        </span>

                                        <span class="location-card-value">
                                            <?php echo htmlspecialchars($location['address_line1']); ?>
                                        </span>

                                    </div>

                                    <?php if (!empty($location['address_line2'])): ?>

                                        <div class="location-card-row">

                                            <span class="location-card-label">
                                                Address Line 2
                                            </span>

                                            <span class="location-card-value">
                                                <?php echo htmlspecialchars($location['address_line2']); ?>
                                            </span>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (!empty($location['city'])): ?>

                                        <div class="location-card-row">

                                            <span class="location-card-label">
                                                City
                                            </span>

                                            <span class="location-card-value">
                                                <?php echo htmlspecialchars($location['city']); ?>
                                            </span>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (!empty($location['state'])): ?>

                                        <div class="location-card-row">

                                            <span class="location-card-label">
                                                State
                                            </span>

                                            <span class="location-card-value">
                                                <?php echo htmlspecialchars($location['state']); ?>
                                            </span>

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <!-- Actions -->
                                <div class="location-card-actions">

                                    <!-- View -->
                                    <form
                                        action="location-details.php"
                                        method="GET"
                                    >

                                        <input
                                            type="hidden"
                                            name="location_id"
                                            value="<?php echo (int)$location['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="location-action location-action-view"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                            &nbsp;View Details
                                        </button>

                                    </form>


                                    <!-- Edit -->
                                    <form
                                        action="location-edit.php"
                                        method="GET"
                                    >

                                        <input
                                            type="hidden"
                                            name="location_id"
                                            value="<?php echo (int)$location['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="location-action location-action-edit"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                            &nbsp;Edit
                                        </button>

                                    </form>


                                    <!-- Delete -->
                                    <form
                                        action="location-delete.php"
                                        method="GET"
                                        onsubmit="return confirm('Are you sure you want to delete this location? This action cannot be undone.');"
                                    >

                                        <input
                                            type="hidden"
                                            name="location_id"
                                            value="<?php echo (int)$location['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="location-action location-action-delete"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                            &nbsp;Delete
                                        </button>

                                    </form>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="locations-empty">
                            No locations found.
                        </div>

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

