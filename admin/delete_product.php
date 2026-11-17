<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');


// ============================================================
// VALIDATE REQUEST
// ============================================================

if (
    !isset($_POST['product_id']) ||
    !is_numeric($_POST['product_id']) ||
    !isset($_SESSION['company_id'])
) {
    echo "<script>
        alert('Invalid request');
        window.location.href='products.php';
    </script>";
    exit;
}

$product_id = (int) $_POST['product_id'];
$company_id = (int) $_SESSION['company_id'];


// ============================================================
// START TRANSACTION
// ============================================================

try {

    $pdo->beginTransaction();


    // ========================================================
    // 1. GET PRODUCT AND CONFIRM OWNERSHIP
    // ========================================================

    $stmt = $pdo->prepare(" 
        SELECT *
        FROM products
        WHERE id = ?
        AND company_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {

        $pdo->rollBack();

        echo "<script>
            alert('Product not found or you do not have permission to delete it.');
            window.location.href='products.php';
        </script>";

        exit;
    }


    // ========================================================
    // 2. DELETE INFORMATION
    // ========================================================

    $deletedAt = date('Y-m-d H:i:s');

    /*
     * Determine who deleted the product.
     *
     * user_id is preferred.
     * If unavailable, fall back to company_id.
     */

    $deletedBy = isset($_SESSION['user_id'])
        ? (string) $_SESSION['user_id']
        : (string) $company_id;


    // ========================================================
    // 3. ARCHIVE PRODUCT INTO deleted_products
    // ========================================================

    $archiveProduct = $pdo->prepare(" 
        INSERT INTO deleted_products (
            product_id,
            company_id,
            name,
            slug,
            barcode,
            category_id,
            stock_type,
            unit_id,
            cost_price,
            selling_price,
            description,
            created_at,
            created_by,
            deleted_at,
            deleted_by
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $archiveProduct->execute([
        $product['id'],
        $product['company_id'],
        $product['name'],
        $product['slug'],
        $product['barcode'],
        $product['category_id'],
        $product['stock_type'],
        $product['unit_id'],
        $product['cost_price'],
        $product['selling_price'],
        $product['description'],
        $product['created_at'],
        $product['created_by'],
        $deletedAt,
        $deletedBy
    ]);


    // ========================================================
    // 4. ARCHIVE STOCK RECORDS
    // ========================================================

    $stockStmt = $pdo->prepare(" 
        SELECT *
        FROM stocks
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stockStmt->execute([
        $product_id,
        $company_id
    ]);

    $stocks = $stockStmt->fetchAll(PDO::FETCH_ASSOC);


    $archiveStock = $pdo->prepare(" 
        INSERT INTO deleted_stocks (
            stock_id,
            product_id,
            company_id,
            location_id,
            quantity,
            supplier,
            created_at,
            stocked_by,
            deleted_by,
            deleted_at
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");


    foreach ($stocks as $stock) {

        $archiveStock->execute([
            $stock['id'],
            $stock['product_id'],
            $stock['company_id'],
            $stock['location_id'],
            $stock['quantity'],
            $stock['supplier'],
            $stock['created_at'],
            $stock['stocked_by'],
            $deletedBy,
            $deletedAt
        ]);
    }


    // ========================================================
    // 5. ARCHIVE STOCK-IN RECORDS
    // ========================================================

    $stockInStmt = $pdo->prepare(" 
        SELECT *
        FROM stock_in
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stockInStmt->execute([
        $product_id,
        $company_id
    ]);

    $stockIns = $stockInStmt->fetchAll(PDO::FETCH_ASSOC);


    $archiveStockIn = $pdo->prepare(" 
        INSERT INTO deleted_stock_in (
            stock_in_id,
            product_id,
            stock_id,
            company_id,
            location_id,
            transfer_ID,
            quantity,
            type,
            supplier,
            created_at,
            recorded_by,
            deleted_by,
            deleted_at
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");


    foreach ($stockIns as $stockIn) {

        $archiveStockIn->execute([
            $stockIn['id'],
            $stockIn['product_id'],
            $stockIn['stock_id'],
            $stockIn['company_id'],
            $stockIn['location_id'],
            $stockIn['transfer_ID'],
            $stockIn['quantity'],
            $stockIn['type'],
            $stockIn['supplier'],
            $stockIn['created_at'],
            $stockIn['recorded_by'],
            $deletedBy,
            $deletedAt
        ]);
    }


    // ========================================================
    // 6. ARCHIVE STOCK MOVEMENTS
    // ========================================================

    $movementStmt = $pdo->prepare(" 
        SELECT *
        FROM stock_movements
        WHERE product_id = ?
        AND company_id = ?
    ");

    $movementStmt->execute([
        $product_id,
        $company_id
    ]);

    $movements = $movementStmt->fetchAll(PDO::FETCH_ASSOC);


    $archiveMovement = $pdo->prepare(" 
        INSERT INTO deleted_stock_movement (
            stock_movement_id,
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
            created_at,
            deleted_by,
            deleted_at
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");


    foreach ($movements as $movement) {

        $archiveMovement->execute([
            $movement['id'],
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
            $movement['created_at'],
            $deletedBy,
            $deletedAt
        ]);
    }


    // ========================================================
    // 7. ARCHIVE PRODUCT IMAGES
    // ========================================================

    /*
     * Get all product image records BEFORE deleting them.
     *
     * The physical image files are NOT deleted.
     * They remain inside ../uploads/products/
     * so the recovery system can restore them later.
     */

    $imageStmt = $pdo->prepare(" 
        SELECT
            id,
            product_id,
            image_path
        FROM product_images
        WHERE product_id = ?
    ");

    $imageStmt->execute([
        $product_id
    ]);

    $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);


    $archiveImage = $pdo->prepare(" 
        INSERT INTO deleted_product_images (
            original_image_id,
            product_id,
            company_id,
            image_path,
            deleted_by,
            deleted_at
        )
        VALUES (
            ?, ?, ?, ?, ?, ?
        )
    ");


    foreach ($images as $image) {

        if (
            !isset($image['image_path']) ||
            trim($image['image_path']) === ''
        ) {
            continue;
        }

        $archiveImage->execute([
            $image['id'],
            $image['product_id'],
            $company_id,
            $image['image_path'],
            $deletedBy,
            $deletedAt
        ]);
    }


    // ========================================================
    // 8. DELETE STOCK MOVEMENTS
    // ========================================================

    $deleteMovements = $pdo->prepare(" 
        DELETE FROM stock_movements
        WHERE product_id = ?
        AND company_id = ?
    ");

    $deleteMovements->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 9. DELETE STOCK-IN
    // ========================================================

    $deleteStockIn = $pdo->prepare(" 
        DELETE FROM stock_in
        WHERE product_id = ?
        AND company_id = ?
    ");

    $deleteStockIn->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 10. DELETE STOCK
    // ========================================================

    $deleteStocks = $pdo->prepare(" 
        DELETE FROM stocks
        WHERE product_id = ?
        AND company_id = ?
    ");

    $deleteStocks->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 11. DELETE PRODUCT FEATURES
    // ========================================================

    $deleteFeatures = $pdo->prepare(" 
        DELETE FROM product_features
        WHERE product_id = ?
    ");

    $deleteFeatures->execute([
        $product_id
    ]);


    // ========================================================
    // 12. DELETE PRODUCT IMAGES FROM DATABASE
    // ========================================================

    /*
     * The image information has already been archived in
     * deleted_products_images.
     */

    $deleteImages = $pdo->prepare(" 
        DELETE FROM product_images
        WHERE product_id = ?
    ");

    $deleteImages->execute([
        $product_id
    ]);


    // ========================================================
    // 13. DELETE PRODUCT
    // ========================================================

    $deleteProduct = $pdo->prepare(" 
        DELETE FROM products
        WHERE id = ?
        AND company_id = ?
    ");

    $deleteProduct->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 14. COMMIT EVERYTHING
    // ========================================================

    $pdo->commit();


    // ========================================================
    // IMPORTANT: PHYSICAL IMAGE FILES ARE NOT DELETED
    // ========================================================

    /*
     * DO NOT use unlink() here.
     *
     * The physical files remain available at:
     * ../uploads/products/
     *
     * The recovery script will recreate the product_images
     * database records using deleted_products_images.
     */


    // ========================================================
    // SUCCESS
    // ========================================================

    echo "<script>
        alert('Product and all related records were successfully deleted and archived.');
        window.location.href='products.php';
    </script>";

    exit;


} catch (Throwable $e) {


    // ========================================================
    // ROLLBACK EVERYTHING
    // ========================================================

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    // ========================================================
    // ERROR
    // ========================================================

    die(
        "<h3>Error:</h3>" .
        htmlspecialchars($e->getMessage()) .
        "<br><br><strong>File:</strong> " .
        htmlspecialchars($e->getFile()) .
        "<br><strong>Line:</strong> " .
        (int)$e->getLine()
    );
}

?>
