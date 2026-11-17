<?php
declare(strict_types=1);

require_once __DIR__ . '/customer_area_common.php';

/* ---------------------------------------------------------
   SMTP AND PHPMAILER CONFIGURATION
--------------------------------------------------------- */
require_once __DIR__ . '/config/smtp.php';

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/* ---------------------------------------------------------
   COMPANY DETAILS AND OTP TABLE
--------------------------------------------------------- */
$companyQuery = $pdo->prepare(
    'SELECT company_name, company_email, company_support_email
     FROM companies
     WHERE id = ?
     LIMIT 1'
);
$companyQuery->execute([$company_id]);
$company = $companyQuery->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    http_response_code(404);
    exit('Company not found.');
}

$companyName = (string) $company['company_name'];

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS marketplace_customer_otps (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        company_id INT NOT NULL,
        customer_id INT NOT NULL,
        account_id BIGINT UNSIGNED NOT NULL,
        email VARCHAR(190) NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        purpose ENUM('register', 'login') NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_otp (company_id, account_id, purpose, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

/* ---------------------------------------------------------
   SMALL AUTHENTICATION HELPERS
--------------------------------------------------------- */
function ca_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function ca_mail_from(array $company): string
{
    foreach (['company_email', 'company_support_email'] as $field) {
        $email = trim((string) ($company[$field] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
    }

    $host = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return 'no-reply@' . ($host !== '' ? $host : 'localhost');
}

function ca_send_customer_otp(
    PDO $pdo,
    int $companyId,
    int $customerId,
    int $accountId,
    string $email,
    string $purpose,
    string $companyName,
    array $company
): bool {
    // A new code invalidates every previous code for this account.
    $delete = $pdo->prepare(
        'DELETE FROM marketplace_customer_otps
         WHERE company_id = ? AND account_id = ?'
    );
    $delete->execute([$companyId, $accountId]);

    $otp = (string) random_int(100000, 999999);

    $insert = $pdo->prepare(
        "INSERT INTO marketplace_customer_otps
            (company_id, customer_id, account_id, email, otp_hash, purpose, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))"
    );
    $insert->execute([
        $companyId,
        $customerId,
        $accountId,
        $email,
        password_hash($otp, PASSWORD_DEFAULT),
        $purpose,
    ]);

    /*
     * IMPORTANT: The verification page reads this pending account from the
     * session. Without this assignment, the OTP email arrives successfully,
     * but customer_verify_otp.php redirects the customer back to login.
     */
    $_SESSION['customer_pending'] = [
        'company_id'  => $companyId,
        'customer_id' => $customerId,
        'account_id'  => $accountId,
        'email'       => $email,
        'purpose'     => $purpose,
    ];

    $_SESSION['customer_auth_mode'] = 'verify';
    $_SESSION['customer_otp_last_sent_at'] = time();

    // Reset the resend counter whenever a new login/registration OTP starts.
    $_SESSION['customer_otp_resend_window'] = time();
    $_SESSION['customer_otp_resend_count'] = 0;

    // Use the same SMTP credentials loaded by config/smtp.php.
    $smtpUsername = trim((string) ($_SESSION['supportmail'] ?? ''));
    $smtpPassword = (string) ($_SESSION['support_email_password'] ?? '');

    if (
        !filter_var($smtpUsername, FILTER_VALIDATE_EMAIL)
        || $smtpPassword === ''
    ) {
        error_log('Marketplace customer OTP: SMTP credentials are unavailable in the session.');
        return false;
    }

    $subject = $purpose === 'register'
        ? 'Verify your customer account'
        : 'Your customer sign-in code';

    $safeCompanyName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;
        $mail->SMTPDebug = 0;

        $mail->setFrom($smtpUsername, 'One Time Passcode');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            <div style=\"font-family:Arial,sans-serif;line-height:1.6;color:#24313d;\">
                <h2 style=\"margin-bottom:8px;\">{$safeCompanyName}</h2>
                <p>Your one-time verification code is:</p>
                <p style=\"font-size:30px;font-weight:bold;letter-spacing:6px;margin:18px 0;\">{$safeOtp}</p>
                <p>This code expires in <strong>10 minutes</strong>.</p>
                <p>If you did not request this code, you can safely ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your verification code for {$companyName} is {$otp}. "
            . 'This code expires in 10 minutes. If you did not request it, ignore this email.';

        $mail->send();
        return true;
    } catch (\Throwable $exception) {
        error_log('Marketplace customer OTP email: ' . $exception->getMessage());
        return false;
    }
}

function ca_get_customer_location(PDO $pdo, int $companyId): int
{
    $companyLocation = $pdo->prepare(
        'SELECT location_id FROM companies WHERE id = ? LIMIT 1'
    );
    $companyLocation->execute([$companyId]);
    $locationId = (int) $companyLocation->fetchColumn();

    if ($locationId > 0) {
        $check = $pdo->prepare(
            'SELECT id FROM locations WHERE id = ? AND company_id = ? LIMIT 1'
        );
        $check->execute([$locationId, $companyId]);
        if ($check->fetchColumn()) {
            return $locationId;
        }
    }

    $fallback = $pdo->prepare(
        'SELECT id
         FROM locations
         WHERE company_id = ? AND status = 1
         ORDER BY id ASC
         LIMIT 1'
    );
    $fallback->execute([$companyId]);

    return (int) $fallback->fetchColumn();
}

/* ---------------------------------------------------------
   POST ACTIONS
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ca_check_csrf()) {
        ca_flash('error', 'Your security token expired. Please try again.');
        ca_redirect('customer_auth.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'logout') {
        unset(
            $_SESSION['marketplace_customer_id'],
            $_SESSION['marketplace_company_id'],
            $_SESSION['marketplace_customer_name'],
            $_SESSION['marketplace_customer_email'],
            $_SESSION['marketplace_cart_loaded_account']
        );

        ca_flash('success', 'You have signed out. Your current browser cart remains available.');
        ca_redirect('marketplace.php');
    }

    /* ------------------------- CUSTOMER REGISTRATION ------------------------- */
    if ($action === 'register') {
        $name = trim((string) ($_POST['fullname'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (
            $name === ''
            || $phone === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 8
        ) {
            ca_flash('error', 'Enter your full name, phone number, valid email and a password of at least 8 characters.');
            ca_redirect('customer_auth.php?account=register');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM customer_accounts
             WHERE company_id = ? AND login_email = ?
             LIMIT 1'
        );
        $existing->execute([$company_id, $email]);

        if ($existing->fetchColumn()) {
            ca_flash('error', 'An account already exists with this email. Please sign in.');
            ca_redirect('customer_auth.php?account=login');
        }

        $locationId = ca_get_customer_location($pdo, $company_id);
        if ($locationId <= 0) {
            ca_flash('error', 'This company does not have an active location for customer registration. Please contact the company.');
            ca_redirect('customer_auth.php?account=register');
        }

        try {
            $pdo->beginTransaction();

            $now = date('Y-m-d H:i:s');
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Reuse an existing customer record for this company and email, if present.
            $customerLookup = $pdo->prepare(
                'SELECT id FROM customers
                 WHERE company_id = ? AND email = ?
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $customerLookup->execute([$company_id, $email]);
            $customerId = (int) $customerLookup->fetchColumn();

            if ($customerId > 0) {
                $updateCustomer = $pdo->prepare(
                    'UPDATE customers
                     SET fullname = ?, phone = ?, location_id = ?, status = ?
                     WHERE id = ? AND company_id = ?'
                );
                $updateCustomer->execute([
                    $name,
                    $phone,
                    $locationId,
                    '1',
                    $customerId,
                    $company_id,
                ]);
            } else {
                $insertCustomer = $pdo->prepare(
                    'INSERT INTO customers
                        (company_id, location_id, fullname, email, phone, created_at, time_created, created_by, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertCustomer->execute([
                    $company_id,
                    $locationId,
                    $name,
                    $email,
                    $phone,
                    $now,
                    date('H:i:s'),
                    'Marketplace',
                    '1',
                ]);
                $customerId = (int) $pdo->lastInsertId();
            }

          

            // Create the matching application user record with the customer role.
            $insertUser = $pdo->prepare(
                'INSERT INTO users
                    (username, email, password, role, company_id, location_id, status, created_at, created_by, changed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insertUser->execute([
                $name,
                $email,
                $passwordHash,
                'customer',
                $company_id,
                $locationId,
                'pending',
                $now,
                'Marketplace',
                $now,
            ]);

            $userId = (int) $pdo->lastInsertId();

              $insertAccount = $pdo->prepare(
                "INSERT INTO customer_accounts
                    (company_id, customer_id, user_id, login_email, password_hash, account_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())"
            );
            $insertAccount->execute([
                $company_id,
                $customerId,
                $userId,
                $email,
                $passwordHash,
            ]);
            $accountId = (int) $pdo->lastInsertId();
            $pdo->commit();

            $_SESSION['customer_pending_user_id'] = $userId;
            $sent = ca_send_customer_otp(
                $pdo,
                $company_id,
                $customerId,
                $accountId,
                $email,
                'register',
                $companyName,
                $company
            );

            ca_flash(
                $sent ? 'success' : 'error',
                $sent
                    ? 'Your account has been created. A six-digit verification code has been sent to your email.'
                    : 'Your account has been created but the email server could not send the code. Use Resend OTP on the verification page after mail delivery is configured.'
            );
            ca_redirect('customer_verify_otp.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Marketplace customer registration: ' . $exception->getMessage());
            ca_flash('error', 'Registration could not be completed. Please try again.');
            ca_redirect('customer_auth.php?account=register');
        }
    }

    /* ------------------------------ CUSTOMER LOGIN ------------------------------ */
    if ($action === 'login') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            ca_flash('error', 'Enter a valid email address and password.');
            ca_redirect('customer_auth.php?account=login');
        }

        // Customer sign-in is authenticated from the main users table.
        $query = $pdo->prepare(
            "SELECT id, username, email, password, role, company_id, location_id, status
             FROM users
             WHERE company_id = ?
               AND LOWER(email) = LOWER(?)
               AND LOWER(role) = 'customer'
             ORDER BY id DESC
             LIMIT 1"
        );
        $query->execute([$company_id, $email]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        if (
            !$user
            || strtolower((string) $user['status']) !== 'active'
            || !password_verify($password, (string) $user['password'])
        ) {
            ca_flash('error', 'Email or password is incorrect, or your customer account is not active.');
            ca_redirect('customer_auth.php?account=login');
        }

        // Load the marketplace customer profile where one exists.
        $customerQuery = $pdo->prepare(
            'SELECT id, fullname, email
             FROM customers
             WHERE company_id = ? AND LOWER(email) = LOWER(?)
             ORDER BY id DESC
             LIMIT 1'
        );
        $customerQuery->execute([$company_id, $email]);
        $customer = $customerQuery->fetch(PDO::FETCH_ASSOC);

        // Keep the existing marketplace cart and all other session data intact.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];
        $_SESSION['role'] = 'customer';
        $_SESSION['company_id'] = (int) $user['company_id'];
        $_SESSION['location_id'] = (int) $user['location_id'];

        $_SESSION['marketplace_company_id'] = (int) $user['company_id'];

        if ($customer) {
            $_SESSION['marketplace_customer_id'] = (int) $customer['id'];
            $_SESSION['marketplace_customer_name'] = (string) $customer['fullname'];
            $_SESSION['marketplace_customer_email'] = (string) $customer['email'];
        } else {
            unset(
                $_SESSION['marketplace_customer_id'],
                $_SESSION['marketplace_customer_name'],
                $_SESSION['marketplace_customer_email']
            );
        }

        // Link the account id when this installation has a matching account row.
        $accountQuery = $pdo->prepare(
            'SELECT id
             FROM customer_accounts
             WHERE company_id = ? AND user_id = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        $accountQuery->execute([(int) $user['company_id'], (int) $user['id']]);
        $accountId = (int) $accountQuery->fetchColumn();

        if ($accountId > 0) {
            $_SESSION['customer_account_id'] = $accountId;
        } else {
            unset($_SESSION['customer_account_id']);
        }

        ca_redirect('client/index.php');
    }
}

/* ---------------------------------------------------------
   LOGIN / REGISTRATION PAGE
--------------------------------------------------------- */
$mode = (string) ($_GET['account'] ?? 'login');
if (!in_array($mode, ['login', 'register'], true)) {
    $mode = 'login';
}

$flash = ca_take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer access | <?= ca_h($companyName) ?></title>

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f4f6f8;
            color: #1b2935;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .auth-card {
            max-width: 460px;
            margin: 6vh auto;
            padding: 30px;
            border: 1px solid #e5eaf0;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 50px #1a2a3810;
        }
        .muted { color: #718096; font-size: 13px; }
        .tabs { display: flex; gap: 8px; margin: 20px 0; }
        .tabs a {
            flex: 1;
            padding: 11px;
            border-radius: 9px;
            background: #f2f4f6;
            color: #273746;
            text-align: center;
            text-decoration: none;
        }
        .tabs a.active { background: #182c3a; color: #fff; }
        label {
            display: block;
            margin: 13px 0 6px;
            font-size: 12px;
            font-weight: 700;
        }
        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #dce3e9;
            border-radius: 9px;
        }
        .btn {
            width: 100%;
            margin-top: 18px;
            padding: 13px;
            border: 0;
            border-radius: 9px;
            background: #b58b45;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
        }
        .alert { margin: 12px 0; padding: 12px; border-radius: 9px; background: #eef6f1; }
        .alert.error { background: #fff0f0; color: #a52d2d; }
        a { color: #8f6b31; }
    </style>
</head>
<body>
    <main class="auth-card">
        <a href="marketplace.php">&larr; Back to marketplace</a>
        <h1><?= ca_h($companyName) ?></h1>
        <p class="muted">Your private customer account</p>

        <?php if ($flash): ?>
            <div class="alert <?= ca_h($flash['type'] ?? 'info') ?>">
                <?= ca_h($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <a class="<?= $mode === 'login' ? 'active' : '' ?>" href="customer_auth.php?account=login">Sign in</a>
            <a class="<?= $mode === 'register' ? 'active' : '' ?>" href="customer_auth.php?account=register">Create account</a>
        </div>

        <form method="post" action="customer_auth.php?account=<?= ca_h($mode) ?>">
            <input type="hidden" name="csrf_token" value="<?= ca_h(ca_csrf()) ?>">
            <input type="hidden" name="action" value="<?= $mode === 'register' ? 'register' : 'login' ?>">

            <?php if ($mode === 'register'): ?>
                <label for="fullname">Full name</label>
                <input id="fullname" name="fullname" maxlength="100" autocomplete="name" required>

                <label for="email">Email address</label>
                <input id="email" type="email" name="email" maxlength="190" autocomplete="email" required>

                <label for="phone">Phone number</label>
                <input id="phone" name="phone" maxlength="100" autocomplete="tel" required>

                <label for="password">Password (minimum 8 characters)</label>
                <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>

                <button class="btn" type="submit">Create account and send OTP</button>
            <?php else: ?>
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" maxlength="190" autocomplete="email" required>

                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>

                <button class="btn" type="submit">Sign in</button>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
