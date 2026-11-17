<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

$type = $_POST['type'] ?? '';

/* =========================
   CHECK EMAIL
========================= */
if ($type === 'check_email') {
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo json_encode([
            'status' => 'found',
            'message' => 'Email exists'
        ]);
    } else {
        echo json_encode([
            'status' => 'not_found',
            'message' => 'Email not found'
        ]);
    }
    exit;
}

/* =========================
   CHECK PASSWORD + STATUS
========================= */
if ($type === 'check_password') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("
        SELECT password, status
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'status' => 'invalid',
            'message' => 'Email not found'
        ]);
        exit;
    }

    if ($user['status'] !== 'active') {
        echo json_encode([
            'status' => 'suspended',
            'message' => 'Account suspended'
        ]);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'status' => 'invalid',
            'message' => 'Incorrect password'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'valid',
        'message' => 'Credentials verified'
    ]);
    exit;
}

/* =========================
   FALLBACK
========================= */
echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request'
]);
exit;
