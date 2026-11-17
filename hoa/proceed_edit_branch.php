<?php
include('../config/db.php');
$date = date('d/m/y-h:s:i');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the branch ID to update
        $branch_id = $_POST['branch_id'] ?? '';

        if (empty($branch_id)) {
            echo "<script>alert('Branch ID is required'); window.history.back();</script>";
            exit;
        }

        // Fetch existing branch data
        $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$branch_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo "<script>alert('Branch not found'); window.history.back();</script>";
            exit;
        }

        // Get submitted values, fallback to existing values if empty
        $name     = !empty($_POST['name']) ? $_POST['name'] : $existing['name'];
        $address  = !empty($_POST['address']) ? $_POST['address'] : $existing['address'];
        $phone    = !empty($_POST['phone']) ? $_POST['phone'] : $existing['phone'];
        $email    = !empty($_POST['email']) ? $_POST['email'] : $existing['email'];
        $city     = !empty($_POST['city']) ? $_POST['city'] : $existing['city'];
        $state    = !empty($_POST['state']) ? $_POST['state'] : $existing['state'];
        $status   = !empty($_POST['status']) ? $_POST['status'] : $existing['status'];

        // Update branch in database
        $updateStmt = $pdo->prepare("UPDATE branches SET 
            name = :name, 
            address = :address, 
            phone = :phone, 
            email = :email, 
            city = :city, 
            state = :state, 
            status = :status
            WHERE id = :branch_id");

        $updateStmt->execute([
            ':name' => $name,
            ':address' => $address,
            ':phone' => $phone,
            ':email' => $email,
            ':city' => $city,
            ':state' => $state,
            ':status' => $status,
            ':branch_id' => $branch_id
        ]);

        echo "<script>alert('Branch updated successfully!'); window.location.href = 'branches.php';</script>";
        
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        echo "<script>alert('An error occurred while updating the branch. Please try again.');</script>";
    }
}
?>
