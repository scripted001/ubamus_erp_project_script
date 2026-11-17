<?php
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $product_id  = $_POST['product_id'];
        $company_id  = $_SESSION['company_id']; // secure from session

        // Fetch existing product
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND company_id = ?");
        $stmt->execute([$product_id, $company_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception("Product not found.");
        }

        // Fetch existing product
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Sanitize new inputs
        $name         = !empty($_POST['name']) ? $_POST['name'] : $existing['name'];
        $category_id  = !empty($_POST['category_id']) ? $_POST['category_id'] : $existing['category_id'];
        $cost_price  = !empty($_POST['cost_price']) ? $_POST['cost_price'] : $prod['cost_price'];
        $selling_price  = !empty($_POST['selling_price']) ? $_POST['selling_price'] : $prod['selling_price'];

        echo $selling_price;
    

       
        // Update database (fixed SQL)
        $update = $pdo->prepare("
            UPDATE products 
            SET name = ?, 
                category_id = ?,
                cost_price = ?,
                selling_price = ?
            WHERE id = ? AND company_id = ?
        ");

        $update->execute([
            $name,
            $category_id,
            $cost_price,
            $selling_price,
            $product_id,
            $company_id
        ]);

        
        echo "<script>alert('Product updated successfully.'); window.location.href = 'products.php';</script>";
        exit;

    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        echo "<script>alert('An error occurred while updating the product. Please try again.');</script>";
    }
}
?>
