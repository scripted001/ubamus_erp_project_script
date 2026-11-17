
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


// ============================================================
// ONLY ALLOW POST REQUEST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo "<script>
        alert('Invalid request.');
        history.back();
    </script>";

    exit;
}


// ============================================================
// GET REQUEST DATA
// ============================================================

$service_id  = (int)($_POST['service_id'] ?? 0);
$company_id  = (int)($_SESSION['company_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);


// ============================================================
// VALIDATE SESSION
// ============================================================

if ($company_id <= 0 || $location_id <= 0) {

    echo "<script>
        alert('Invalid company or location session.');
        history.back();
    </script>";

    exit;
}


// ============================================================
// VALIDATE SERVICE ID
// ============================================================

if ($service_id <= 0) {

    echo "<script>
        alert('Invalid service selected.');
        history.back();
    </script>";

    exit;
}


try {

    // ========================================================
    // START TRANSACTION
    // ========================================================

    $pdo->beginTransaction();


    // ========================================================
    // 1. FIND THE SERVICE
    //
    // IMPORTANT:
    // We use the SESSION company/location instead of trusting
    // company_id and location_id from the hidden form fields.
    // ========================================================

    $findService = $pdo->prepare("
        SELECT
            id,
            company_id,
            location_id,
            service_number,
            service_name
        FROM services
        WHERE id = ?
          AND company_id = ?
          AND location_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $findService->execute([
        $service_id,
        $company_id,
        $location_id
    ]);

    $service = $findService->fetch(PDO::FETCH_ASSOC);


    // ========================================================
    // SERVICE DOES NOT EXIST / DOES NOT BELONG TO COMPANY
    // ========================================================

    if (!$service) {

        $pdo->rollBack();

        echo "<script>
            alert('Service not found or you do not have permission to delete it.');
            history.back();
        </script>";

        exit;
    }


    // ========================================================
    // 2. DELETE RENDERED SERVICE REFERENCES
    //
    // If the service has already been rendered and its ID is
    // stored in service_items, remove those records first.
    //
    // This means the service can be deleted whether it has
    // been rendered before or not.
    // ========================================================

    $deleteServiceItems = $pdo->prepare("
        DELETE FROM service_items
        WHERE service_id = ?
    ");

    $deleteServiceItems->execute([
        $service_id
    ]);


    // ========================================================
    // 3. PERMANENTLY DELETE THE SERVICE
    //
    // NO ARCHIVE
    // NO deleted_jobs
    // NO RECOVERY
    // NO MATERIAL RETURN
    // ========================================================

    $deleteService = $pdo->prepare("
        DELETE FROM services
        WHERE id = ?
          AND company_id = ?
          AND location_id = ?
        LIMIT 1
    ");

    $deleteService->execute([
        $service_id,
        $company_id,
        $location_id
    ]);


    // ========================================================
    // 4. CONFIRM DELETION
    // ========================================================

    if ($deleteService->rowCount() !== 1) {

        $pdo->rollBack();

        echo "<script>
            alert('Service deletion failed.');
            history.back();
        </script>";

        exit;
    }


    // ========================================================
    // 5. COMMIT
    // ========================================================

    $pdo->commit();


    // ========================================================
    // SUCCESS
    // ========================================================

    echo "<script>

        alert(
            'Service \"' +
            " . json_encode($service['service_name']) . " +
            '\" has been permanently deleted.'
        );

        window.location.href = 'existing-service.php';

    </script>";

    exit;


} catch (Throwable $e) {


    // ========================================================
    // ROLLBACK IF TRANSACTION IS ACTIVE
    // ========================================================

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    // ========================================================
    // ERROR
    // ========================================================

    echo "<script>

        alert(
            'Unable to permanently delete this service. ' +
            'Please try again.'
        );

        history.back();

    </script>";

    exit;
}
?>
