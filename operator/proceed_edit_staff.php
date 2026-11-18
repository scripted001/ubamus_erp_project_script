<?php
include('../config/db.php');


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $staff_id = $_POST['staff_id'];

    // Fetch existing staff data
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
    $stmt->execute([$staff_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

  
    
    


    if (!$existing) {
        die("Staff not found.");
    }

    // Use submitted data OR fallback
    $company_id     = $existing['company_id'];
    $location_id      = $_POST['location_id']      ?: $existing['location_id'];
    $firstname      = $_POST['firstname']      ?: $existing['firstname'];
    $email      = $_POST['email']      ?: $existing['email'];
    $lastname       = $_POST['lastname']       ?: $existing['lastname'];
    $phone          = $_POST['phone']          ?: $existing['phone'];
    $role           = $_POST['role']           ?: $existing['role'];
    $address        = $_POST['address']        ?: $existing['address'];
    $refree1name    = $_POST['refree1name']    ?: $existing['refree1name'];
    $refree1phone   = $_POST['refree1phone']   ?: $existing['refree1phone'];
    $refree2name    = $_POST['refree2name']    ?: $existing['refree2name'];
    $refree2phone   = $_POST['refree2phone']   ?: $existing['refree2phone'];

    
    // Profile picture
    $profile = $existing['profile'];
    $username = $firstname.' '.$lastname;


    if (!empty($_FILES['profile']['name']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {

        $ext = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('staff_') . "." . $ext;
        $upload_path = "../uploads/staff/" . $new_filename;

        if (move_uploaded_file($_FILES['profile']['tmp_name'], $upload_path)) {
            $profile = $new_filename; // Save filename only
        }
    }

    // Update staff
    $stmt = $pdo->prepare("UPDATE staffs SET 
        company_id = ?, 
        location_id = ?, 
        firstname = ?, 
        lastname = ?, 
        phone = ?, 
        role = ?, 
        address = ?, 
        profile = ?, 
        refree1name = ?, 
        refree1phone = ?, 
        refree2name = ?, 
        refree2phone = ?
        WHERE id = ?");

    $stmt->execute([
        $company_id, $location_id, $firstname, $lastname,
        $phone, $role, $address, $profile, $refree1name,
        $refree1phone, $refree2name, $refree2phone,
        $staff_id
    ]);


    /* UPDATE USER */
  $stmt = $pdo->prepare("
            UPDATE users SET
                username = ?, changed_at = NOW()
            WHERE email = ?
        ");
        $stmt->execute([$username, $email]);



    echo "<script>alert('Staff member edited successfully.'); window.location.href = 'staffs.php';</script>";
    exit;
}
?>
