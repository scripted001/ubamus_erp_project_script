<?php
include('../config/db.php');
include('user_data.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===========================
       SOURCE OF TRUTH (OLD EMAIL)
    =========================== */
    $old_email = $_SESSION['user_email'];
    // echo $old_email;

    /* ===========================
       FETCH STAFF
    =========================== */
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE email = ? LIMIT 1");
    $stmt->execute([$old_email]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo "<script>alert('Staff profile not found'); history.back();</script>";
        exit;
    }

    /* ===========================
       FETCH USER
    =========================== */
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$old_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>alert('User account not found'); history.back();</script>";
        exit;
    }

    /* ===========================
       MERGE DATA
    =========================== */
    $firstname = !empty($_POST['firstname']) ? $_POST['firstname'] : $staff['firstname'];
    $lastname  = !empty($_POST['lastname'])  ? $_POST['lastname']  : $staff['lastname'];
    $email     = !empty($_POST['email'])     ? $_POST['email']     : $old_email;
    $phone     = !empty($_POST['phone'])     ? $_POST['phone']     : $staff['phone'];
    $address   = !empty($_POST['address'])   ? $_POST['address']   : $staff['address'];
  


    /* ===========================
       PROFILE IMAGE
    =========================== */
    $profile = $staff['profile'];

    if (!empty($_FILES['profile']['name']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $filename = uniqid('staff_', true) . "." . $ext;
            move_uploaded_file($_FILES['profile']['tmp_name'], "../uploads/staff/$filename");
            $profile = "uploads/staff/$filename";
        }
    }

    /* ===========================
       TRANSACTION
    =========================== */
    $pdo->beginTransaction();
     // 5️⃣ Generate username = firstname + lastname
    $username = $firstname . ' ' . $lastname;

    try {

      /* UPDATE STAFF */
$stmt = $pdo->prepare("
    UPDATE staffs SET
        firstname = ?, lastname = ?, email = ?, phone = ?, address = ?, profile = ?
    WHERE id = ?
");
$stmt->execute([
    $firstname, $lastname, $email, $phone, $address, $profile, $staff['id']
]);

/* UPDATE USER */
  $stmt = $pdo->prepare("
            UPDATE users SET
                username = ?, email = ?, changed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $user['id']]);


        /* UPDATE SESSION EMAIL */
        $_SESSION['user_email'] = $email;

        $pdo->commit();

        echo "<script>alert('Profile updated successfully'); window.location='profile.php';</script>";
       
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Profile update failed'); history.back();</script>";
        exit;
    }
}
?>
