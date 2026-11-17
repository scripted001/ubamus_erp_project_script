<?php
include('../config/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>
        alert('Invalid method.');
        window.location.href='staffs.php';
    </script>";
    exit;
}

if (empty($_POST['staff_id'])) {
    echo "<script>
        alert('Invalid request.');
        window.location.href='staffs.php';
    </script>";
    exit;
}

$staff_id = (int) $_POST['staff_id'];

try {

    // Fetch ONLY the selected staff
    $stmt = $pdo->prepare("
        SELECT id, email, status
        FROM staffs
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo "<script>
            alert('Staff not found.');
            window.location.href='staffs.php';
        </script>";
        exit;
    }

    $staff_email = $staff['email'];

    // Start transaction
    $pdo->beginTransaction();

    // Suspend ONLY the selected staff
    $stmt = $pdo->prepare("
        UPDATE staffs
        SET status = 'suspended'
        WHERE id = ?
    ");
    $stmt->execute([$staff_id]);

    /*
     * Suspend the corresponding login account.
     *
     * Since the staff email identifies the user's login account,
     * update only the matching user.
     */
    if (!empty($staff_email)) {
        $stmt2 = $pdo->prepare("
            UPDATE users
            SET status = 'suspended'
            WHERE email = ?
            LIMIT 1
        ");
        $stmt2->execute([$staff_email]);
    }

    // Commit changes
    $pdo->commit();

    echo "<script>
        alert('Staff has been successfully suspended.');
        window.location.href='staffs.php';
    </script>";
    exit;

} catch (Exception $e) {

    // Roll back if anything fails
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<script>
        alert('Unable to suspend staff. Please try again.');
        window.location.href='staffs.php';
    </script>";
    exit;
}
?>