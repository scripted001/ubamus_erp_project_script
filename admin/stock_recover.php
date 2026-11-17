<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

$company_id = $_SESSION['company_id'] ?? 1;
$user_id    = $_SESSION['user_id'] ?? null;

/*
|--------------------------------------------------------------------------
| STOCK RECOVERY
|--------------------------------------------------------------------------
| This script restores:
|   1. deleted_stocks       -> stocks
|   2. deleted_stock_in      -> stock_in
|   3. deleted_stock_movement-> stock_movements
|
| The original primary IDs are restored so the records return to their
| original identity.
|
| The recovery is performed inside ONE database transaction. If any part
| fails, nothing is restored and the archived records remain untouched.
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['deleted_id'])) {
    echo "<script>
        alert('Invalid recovery request.');
        window.location.href='stock_delete.php';
    </script>";
    exit;
}

$deleted_stock_id = (int) $_POST['deleted_id'];

if ($deleted_stock_id <= 0) {
    echo "<script>
        alert('Invalid deleted stock record.');
        window.location.href='stock_delete.php';
    </script>";
    exit;
}

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | 1. GET THE ARCHIVED STOCK
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT *
        FROM deleted_stocks
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        $deleted_stock_id,
        $company_id
    ]);

    $deletedStock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$deletedStock) {
        throw new Exception('The deleted stock record could not be found.');
    }

    $stock_id = (int) $deletedStock['stock_id'];


    /*
    |--------------------------------------------------------------------------
    | 2. VERIFY THAT THE STOCK DOES NOT ALREADY EXIST
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM stocks
        WHERE id = ?
          AND company_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $stock_id,
        $company_id
    ]);

    if ($stmt->fetchColumn()) {
        throw new Exception(
            'This stock already exists in the active stocks table. Recovery was stopped to prevent duplication.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 3. RESTORE STOCK
    |--------------------------------------------------------------------------
    */

    $restoreStock = $pdo->prepare("
        INSERT INTO stocks
        (
            id,
            product_id,
            company_id,
            location_id,
            quantity,
            supplier,
            created_at,
            stocked_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $restoreStock->execute([
        $stock_id,
        $deletedStock['product_id'],
        $deletedStock['company_id'],
        $deletedStock['location_id'],
        $deletedStock['quantity'],
        $deletedStock['supplier'],
        $deletedStock['created_at'],
        $deletedStock['stocked_by']
    ]);


    /*
    |--------------------------------------------------------------------------
    | 4. RESTORE ALL DELETED STOCK-IN RECORDS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT *
        FROM deleted_stock_in
        WHERE stock_id = ?
          AND company_id = ?
        ORDER BY id ASC
        FOR UPDATE
    ");

    $stmt->execute([
        $stock_id,
        $company_id
    ]);

    $deletedStockIns = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $restoreStockIn = $pdo->prepare("
        INSERT INTO stock_in
        (
            id,
            product_id,
            stock_id,
            company_id,
            location_id,
            transfer_ID,
            quantity,
            type,
            supplier,
            created_at,
            recorded_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($deletedStockIns as $stockIn) {

        // Make sure the original stock_in ID is not already in use.
        $check = $pdo->prepare("
            SELECT id
            FROM stock_in
            WHERE id = ?
            LIMIT 1
        ");

        $check->execute([
            $stockIn['stock_in_id']
        ]);

        if ($check->fetchColumn()) {
            throw new Exception(
                'A stock-in record with original ID ' .
                $stockIn['stock_in_id'] .
                ' already exists. Recovery was stopped.'
            );
        }

        $restoreStockIn->execute([
            $stockIn['stock_in_id'],
            $stockIn['product_id'],
            $stockIn['stock_id'],
            $stockIn['company_id'],
            $stockIn['location_id'],
            $stockIn['transfer_ID'],
            $stockIn['quantity'],
            $stockIn['type'],
            $stockIn['supplier'],
            $stockIn['created_at'],
            $stockIn['recorded_by']
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | 5. RESTORE ALL DELETED STOCK MOVEMENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT *
        FROM deleted_stock_movement
        WHERE stock_id = ?
          AND company_id = ?
        ORDER BY id ASC
        FOR UPDATE
    ");

    $stmt->execute([
        $stock_id,
        $company_id
    ]);

    $deletedMovements = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $restoreMovement = $pdo->prepare("
        INSERT INTO stock_movements
        (
            id,
            company_id,
            product_id,
            stock_id,
            from_location_id,
            to_location_id,
            transfer_ID,
            quantity,
            movement_type,
            reference,
            created_by,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($deletedMovements as $movement) {

        // Make sure the original movement ID is not already in use.
        $check = $pdo->prepare("
            SELECT id
            FROM stock_movements
            WHERE id = ?
            LIMIT 1
        ");

        $check->execute([
            $movement['stock_movement_id']
        ]);

        if ($check->fetchColumn()) {
            throw new Exception(
                'A stock movement with original ID ' .
                $movement['stock_movement_id'] .
                ' already exists. Recovery was stopped.'
            );
        }

        /*
         * The archive table currently stores nullable movement fields as
         * "Not Found" because of the existing stock_delete.php.
         * We preserve the archived value exactly here.
         */
        $restoreMovement->execute([
            $movement['stock_movement_id'],
            $movement['company_id'],
            $movement['product_id'],
            $movement['stock_id'],
            $movement['from_location_id'],
            $movement['to_location_id'],
            $movement['transfer_ID'],
            $movement['quantity'],
            $movement['movement_type'],
            $movement['reference'],
            $movement['created_by'],
            $movement['created_at']
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | 6. REMOVE ARCHIVED RECORDS ONLY AFTER EVERYTHING IS RESTORED
    |--------------------------------------------------------------------------
    */

    $deleteMovementArchive = $pdo->prepare("
        DELETE FROM deleted_stock_movement
        WHERE stock_id = ?
          AND company_id = ?
    ");

    $deleteMovementArchive->execute([
        $stock_id,
        $company_id
    ]);


    $deleteStockInArchive = $pdo->prepare("
        DELETE FROM deleted_stock_in
        WHERE stock_id = ?
          AND company_id = ?
    ");

    $deleteStockInArchive->execute([
        $stock_id,
        $company_id
    ]);


    $deleteStockArchive = $pdo->prepare("
        DELETE FROM deleted_stocks
        WHERE id = ?
          AND company_id = ?
    ");

    $deleteStockArchive->execute([
        $deleted_stock_id,
        $company_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | 7. COMMIT
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    $stockInCount = count($deletedStockIns);
    $movementCount = count($deletedMovements);

    echo "<script>
        alert(" . json_encode(
            "Stock successfully recovered!\n\n" .
            "Stock ID: {$stock_id}\n" .
            "Stock-in records restored: {$stockInCount}\n" .
            "Movement records restored: {$movementCount}"
        ) . ");
        window.location.href='stocks.php';
    </script>";

    exit;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK EVERYTHING
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log the actual error server-side.
    error_log(
        'Stock Recovery Error | Company: ' .
        $company_id .
        ' | Deleted Stock ID: ' .
        $deleted_stock_id .
        ' | Error: ' .
        $e->getMessage()
    );

    echo "<script>
        alert(" . json_encode(
            "Stock recovery failed.\n\n" .
            $e->getMessage() .
            "\n\nNo records were changed."
        ) . ");
        window.location.href='stock_delete.php';
    </script>";

    exit;
}
?>
