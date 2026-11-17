<?php

include('../config/db.php');
include('user_data.php'); // session info
date_default_timezone_set('Africa/Lagos');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request');
if (!isset($_SESSION['company_id'], $_SESSION['user_id'])) exit('Unauthorized');

if (!isset($_POST['location_id']) || !is_numeric($_POST['location_id'])) {
    exit('Invalid location');
}

$location_id = (int) $_POST['location_id'];
$company_id  = $_SESSION['company_id'];

// ===============================
// FETCH EXISTING LOCATION
// ===============================
$stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ? AND company_id = ?");
$stmt->execute([$location_id, $company_id]);
$location = $stmt->fetch();

if (!$location) exit('Location not found');

// ===============================
// COLLECT DATA, USE EXISTING IF EMPTY
// ===============================
$allowed_types = [
    'branch','store','office','showroom','kiosk','pop_up_store','franchise',
    'head_office','warehouse','cold_room','distribution_center','transit_hub',
    'cross_dock','fulfillment_center','depot','shipping_terminal','logistics_hub',
    'storage_yard','factory','production_plant','laboratory','assembly_plant',
    'farm','greenhouse','workshop','mill','foundry','refinery','supplier',
    'vendor_site','drop_off_point','supplier_warehouse','partner_hub',
    'collection_center','dock','port_terminal','airport_hub','truck_yard',
    'vehicle_pool','drone_depot','virtual','cloud_storage','digital_pool',
    'research_center','training_center','exhibition_hall','event_space',
    'service_center','data_center','call_center','archive_room','hospital_ward',
    'clinic','pharmacy','laboratory_site','retail_market','community_center',
    'warehouse_rack_zone','locker_station','cold_storage_unit','packing_station',
    'quality_control_station'
];

$type           = $_POST['type'] ?? $location['type'];
$name           = trim($_POST['name'] ?: $location['name']);
$address_line1  = trim($_POST['address_line1'] ?: $location['address_line1']);
$address_line2  = trim($_POST['address_line2'] ?: $location['address_line2']);
$city           = trim($_POST['city'] ?: $location['city']);
$state          = trim($_POST['state'] ?: $location['state']);
$lga            = trim($_POST['lga'] ?: $location['lga']);

// ===============================
// VALIDATION
// ===============================
if (!in_array($type, $allowed_types, true)) exit('Invalid location type');

// ===============================
// UPDATE QUERY
// ===============================
$updateStmt = $pdo->prepare("
    UPDATE locations SET
        type = :type,
        name = :name,
        address_line1 = :address_line1,
        address_line2 = :address_line2,
        city = :city,
        state = :state,
        lga = :lga
    WHERE id = :location_id AND company_id = :company_id
");

$updateStmt->execute([
    ':type'          => $type,
    ':name'          => $name,
    ':address_line1' => $address_line1,
    ':address_line2' => $address_line2,
    ':city'          => $city,
    ':state'         => $state,
    ':lga'           => $lga,
    ':location_id'   => $location_id,
    ':company_id'    => $company_id
]);

echo "<script>
    alert('Location updated successfully!');
    window.location.href = 'locations.php';
</script>";
