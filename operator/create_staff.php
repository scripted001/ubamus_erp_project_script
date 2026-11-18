<?php
include('../config/db.php'); // assumes $pdo is initialized

// Set timezone to Lagos/Abuja
date_default_timezone_set('Africa/Lagos');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "<script>alert('User not logged in.'); window.location.href='login.php';</script>";
    exit;
}

// Fetch current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('User not found.'); window.location.href='login.php';</script>";
    exit;
}

$created_by = $user['email']; // Set created_by
$currentTime = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize input
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $refree1name = trim($_POST['refree1name'] ?? '');
        $refree1phone = trim($_POST['refree1phone'] ?? '');
        $refree2name = trim($_POST['refree2name'] ?? '');
        $refree2phone = trim($_POST['refree2phone'] ?? '');
        $location_id = $_POST['location_id'] ?? null;
        $company_id = $_POST['company_id'] ?? $_SESSION['company_id'] ?? null;
        $status = ($_POST['status'] ?? 1) == 1 ? 'active' : 'suspended';

        // Check for duplicate staff email in company
        $checkStmt = $pdo->prepare("SELECT id FROM staffs WHERE email = ? AND company_id = ?");
        $checkStmt->execute([$email, $company_id]);
        if ($checkStmt->rowCount() > 0) {
            echo "<script>alert('A staff with this email already exists in this company.'); window.location.href='new_staff.php';</script>";
            exit;
        }

        // Password hash
        $password = password_hash($phone, PASSWORD_DEFAULT);

        // Profile picture upload
        $profilePath = null;
        if (isset($_FILES['profile']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/staff/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $tmpName = $_FILES['profile']['tmp_name'];
            $originalName = basename($_FILES['profile']['name']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            // Validate file type
            $allowed = ['jpg','jpeg','png'];
            if (!in_array($ext, $allowed)) {
                throw new Exception('Invalid profile image type. Allowed: jpg, jpeg, png');
            }

            $safeName = uniqid('staff_', true) . '.' . $ext;
            $destination = $uploadDir . $safeName;

            if (move_uploaded_file($tmpName, $destination)) {
                $profilePath = 'uploads/staff/' . $safeName;
            }
        }

        // Insert staff into database
        $stmt = $pdo->prepare("
            INSERT INTO staffs 
            (firstname, lastname, email, phone, role, address, profile, refree1name, refree1phone, refree2name, refree2phone, location_id, company_id, created_at, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $firstname, $lastname, $email, $phone, $role, $address, $profilePath,
            $refree1name, $refree1phone, $refree2name, $refree2phone,
            $location_id, $company_id, $currentTime, $created_by, $status
        ]);

        // Store session info
        $_SESSION['staff_username'] = $firstname . ' ' . $lastname;
        $_SESSION['email'] = $email;
        $_SESSION['staff_role'] = $role;
        $_SESSION['company_id'] = $company_id;
        $_SESSION['staff_location_id'] = $location_id;
        $_SESSION['status'] = $status;

        echo "<script>alert('Staff member created successfully.'); window.location.href='user_staffs.php';</script>";
        exit;

    } catch (Exception $e) {
        echo "<script>alert('Failed to create staff: " . addslashes($e->getMessage()) . "'); window.location.href='new_staff.php';</script>";
        exit;
    }
} else {
    header("Location: new_staff.php");
    exit;
}
?>
