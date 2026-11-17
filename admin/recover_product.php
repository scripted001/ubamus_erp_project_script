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
    !isset($_SESSION['company_id']) ||
    !is_numeric($_SESSION['company_id'])
) {
    echo "<script>
        alert('Invalid recovery request.');
        window.location.href='archieve-products.php';
    </script>";
    exit;
}

$product_id = (int) $_POST['product_id'];
$company_id = (int) $_SESSION['company_id'];

if ($product_id <= 0 || $company_id <= 0) {
    echo "<script>
        alert('Invalid product or company.');
        window.location.href='archieve-products.php';
    </script>";
    exit;
}


// ============================================================
// START TRANSACTION
// ============================================================

try {

    $pdo->beginTransaction();


    // ========================================================
    // 1. GET ARCHIVED PRODUCT
    // ========================================================

    $stmt = $pdo->prepare("
        SELECT *
        FROM deleted_products
        WHERE product_id = ?
        AND company_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {
        throw new RuntimeException(
            'Archived product was not found.'
        );
    }


    // ========================================================
    // 2. CHECK IF PRODUCT ALREADY EXISTS
    // ========================================================

    $stmt = $pdo->prepare("
        SELECT id
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $product_id
    ]);

    if ($stmt->fetchColumn()) {
        throw new RuntimeException(
            'This product already exists in the products table. '
            . 'Recovery stopped to prevent duplication.'
        );
    }


    // ========================================================
    // 3. RESTORE PRODUCT
    // ========================================================

    $restoreProduct = $pdo->prepare("
        INSERT INTO products (
            id,
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
            created_by
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $restoreProduct->execute([
        $product['product_id'],
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
        $product['created_by']
    ]);


    // ========================================================
    // 4. GET ARCHIVED STOCK RECORDS
    // ========================================================

    $stockStmt = $pdo->prepare("
        SELECT *
        FROM deleted_stocks
        WHERE product_id = ?
        AND company_id = ?
        ORDER BY id ASC
    ");

    $stockStmt->execute([
        $product_id,
        $company_id
    ]);

    $stocks = $stockStmt->fetchAll(PDO::FETCH_ASSOC);


    // ========================================================
    // 5. RESTORE STOCK RECORDS
    // ========================================================

    $checkStock = $pdo->prepare("
        SELECT id
        FROM stocks
        WHERE id = ?
        LIMIT 1
    ");

    $restoreStock = $pdo->prepare("
        INSERT INTO stocks (
            id,
            product_id,
            company_id,
            location_id,
            quantity,
            supplier,
            created_at,
            stocked_by
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");


    foreach ($stocks as $stock) {

        $checkStock->execute([
            $stock['stock_id']
        ]);

        if ($checkStock->fetchColumn()) {
            throw new RuntimeException(
                'Stock ID ' .
                (int) $stock['stock_id'] .
                ' already exists in the stocks table. '
                . 'Recovery stopped.'
            );
        }

        $restoreStock->execute([
            $stock['stock_id'],
            $stock['product_id'],
            $stock['company_id'],
            $stock['location_id'],
            $stock['quantity'],
            $stock['supplier'],
            $stock['created_at'],
            $stock['stocked_by']
        ]);
    }


    // ========================================================
    // 6. GET ARCHIVED STOCK-IN RECORDS
    // ========================================================

    $stockInStmt = $pdo->prepare("
        SELECT *
        FROM deleted_stock_in
        WHERE product_id = ?
        AND company_id = ?
        ORDER BY id ASC
    ");

    $stockInStmt->execute([
        $product_id,
        $company_id
    ]);

    $stockIns = $stockInStmt->fetchAll(PDO::FETCH_ASSOC);


    // ========================================================
    // 7. RESTORE STOCK-IN RECORDS
    // ========================================================

    $checkStockIn = $pdo->prepare("
        SELECT id
        FROM stock_in
        WHERE id = ?
        LIMIT 1
    ");

    $restoreStockIn = $pdo->prepare("
        INSERT INTO stock_in (
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
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");


    foreach ($stockIns as $stockIn) {

        $checkStockIn->execute([
            $stockIn['stock_in_id']
        ]);

        if ($checkStockIn->fetchColumn()) {
            throw new RuntimeException(
                'Stock-in ID ' .
                (int) $stockIn['stock_in_id'] .
                ' already exists in the stock_in table. '
                . 'Recovery stopped.'
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


    // ========================================================
    // 8. GET ARCHIVED STOCK MOVEMENTS
    // ========================================================

    $movementStmt = $pdo->prepare("
        SELECT *
        FROM deleted_stock_movement
        WHERE product_id = ?
        AND company_id = ?
        ORDER BY id ASC
    ");

    $movementStmt->execute([
        $product_id,
        $company_id
    ]);

    $movements = $movementStmt->fetchAll(PDO::FETCH_ASSOC);


    // ========================================================
    // 9. RESTORE STOCK MOVEMENTS
    // ========================================================

    $checkMovement = $pdo->prepare("
        SELECT id
        FROM stock_movements
        WHERE id = ?
        LIMIT 1
    ");

    $restoreMovement = $pdo->prepare("
        INSERT INTO stock_movements (
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
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");


    foreach ($movements as $movement) {

        $checkMovement->execute([
            $movement['stock_movement_id']
        ]);

        if ($checkMovement->fetchColumn()) {
            throw new RuntimeException(
                'Stock movement ID ' .
                (int) $movement['stock_movement_id'] .
                ' already exists in the stock_movements table. '
                . 'Recovery stopped.'
            );
        }

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


    // ========================================================
    // 10. GET ARCHIVED PRODUCT IMAGES
    // ========================================================

    /*
     * The physical image files were preserved during deletion.
     *
     * We only restore their database records here.
     */

    $imageStmt = $pdo->prepare("
        SELECT *
        FROM deleted_product_images
        WHERE product_id = ?
        AND company_id = ?
        ORDER BY id ASC
    ");

    $imageStmt->execute([
        $product_id,
        $company_id
    ]);

    $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);


    // ========================================================
    // 11. RESTORE PRODUCT IMAGES
    // ========================================================

    $checkImage = $pdo->prepare("
        SELECT id
        FROM product_images
        WHERE id = ?
        LIMIT 1
    ");

    $restoreImage = $pdo->prepare("
        INSERT INTO product_images (
            id,
            product_id,
            image_path
        )
        VALUES (
            ?, ?, ?
        )
    ");


    foreach ($images as $image) {

        if (
            !isset($image['image_path']) ||
            trim($image['image_path']) === ''
        ) {
            throw new RuntimeException(
                'An archived product image has no image path. '
                . 'Recovery stopped.'
            );
        }


        $checkImage->execute([
            $image['original_image_id']
        ]);

        if ($checkImage->fetchColumn()) {
            throw new RuntimeException(
                'Product image ID ' .
                (int) $image['original_image_id'] .
                ' already exists in product_images. '
                . 'Recovery stopped.'
            );
        }


        $restoreImage->execute([
            $image['original_image_id'],
            $image['product_id'],
            $image['image_path']
        ]);
    }


    // ========================================================
    // 12. DELETE ARCHIVED STOCK MOVEMENTS
    // ========================================================

    $stmt = $pdo->prepare("
        DELETE FROM deleted_stock_movement
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 13. DELETE ARCHIVED STOCK-IN
    // ========================================================

    $stmt = $pdo->prepare("
        DELETE FROM deleted_stock_in
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 14. DELETE ARCHIVED STOCK
    // ========================================================

    $stmt = $pdo->prepare("
        DELETE FROM deleted_stocks
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 15. DELETE ARCHIVED PRODUCT IMAGES
    // ========================================================

    /*
     * Image records have already been restored into
     * product_images.
     *
     * The physical image files are NOT deleted.
     */

    $stmt = $pdo->prepare("
        DELETE FROM deleted_product_images
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 16. DELETE ARCHIVED PRODUCT
    // ========================================================

    $stmt = $pdo->prepare("
        DELETE FROM deleted_products
        WHERE product_id = ?
        AND company_id = ?
    ");

    $stmt->execute([
        $product_id,
        $company_id
    ]);


    // ========================================================
    // 17. COMMIT EVERYTHING
    // ========================================================

    $pdo->commit();


    // ========================================================
    // SUCCESS
    // ========================================================

    echo "<script>

        alert(
            'Product, images, stock, stock-in, and stock movement records were successfully recovered.'
        );

        window.location.href = 'archieve-products.php';

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
        "<h3>Product Recovery Error:</h3>" .

        htmlspecialchars(
            $e->getMessage()
        ) .

        "<br><br><strong>File:</strong> " .

        htmlspecialchars(
            $e->getFile()
        ) .

        "<br><strong>Line:</strong> " .

        (int) $e->getLine()
    );
}

?>
