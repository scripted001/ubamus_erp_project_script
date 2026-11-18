<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

if (
    !isset($_POST['product_id']) ||
    !is_numeric($_POST['product_id']) ||
    !isset($_SESSION['company_id'])
) {
    echo "<script>alert('Invalid request'); window.location.href='products.php';</script>";
    exit;
}

$product_id = (int)$_POST['product_id'];
$company_id = (int)$_SESSION['company_id'];

try {
    $pdo->beginTransaction();

    // confirm ownership
    $check = $pdo->prepare("SELECT * FROM products WHERE id=? AND company_id=? LIMIT 1");
    $check->execute([$product_id,$company_id]);
    $product = $check->fetch(PDO::FETCH_ASSOC);

    if(!$product){
        $pdo->rollBack();
        echo "<script>alert('Product not found'); window.location.href='products.php';</script>";
        exit;
    }

    /* ===============================
       CHECK IF PRODUCT HAS HISTORY
    =============================== */

    // sales check
    $salesCheck = $pdo->prepare("SELECT id FROM sale_items WHERE product_id=? LIMIT 1");
    $salesCheck->execute([$product_id]);

    // stock check
    $stockCheck = $pdo->prepare("SELECT id FROM stocks WHERE product_id=? LIMIT 1");
    $stockCheck->execute([$product_id]);

    // production check
    $prodCheck = $pdo->prepare("SELECT id FROM production_raw_materials WHERE product_id=? LIMIT 1");
    $prodCheck->execute([$product_id]);

    if(
        $salesCheck->fetch() ||
        $stockCheck->fetch() ||
        $prodCheck->fetch()
    ){
        // DO NOT DELETE — archive instead
        $pdo->prepare("UPDATE products SET status='archived' WHERE id=?")
            ->execute([$product_id]);

        $pdo->commit();

        echo "<script>
        alert('Product has sales/stock history. It has been archived instead of deleted.');
        window.location.href='products.php';
        </script>";
        exit;
    }

    /* ===============================
       SAFE TO HARD DELETE
    =============================== */

    // delete images
    $img = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id=?");
    $img->execute([$product_id]);
    $imgs = $img->fetchAll(PDO::FETCH_ASSOC);

    foreach($imgs as $im){
        $path = "../uploads/products/".$im['image_path'];
        if(file_exists($path)) unlink($path);
    }

    // delete features & images
    $pdo->prepare("DELETE FROM product_features WHERE product_id=?")->execute([$product_id]);
    $pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$product_id]);

    // delete product
    $pdo->prepare("DELETE FROM products WHERE id=? AND company_id=?")
        ->execute([$product_id,$company_id]);

    $pdo->commit();

    echo "<script>alert('Product deleted permanently'); window.location.href='products.php';</script>";
    exit;

} catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());

    echo "<script>alert('System error'); window.location.href='products.php';</script>";
    exit;
}
?>
