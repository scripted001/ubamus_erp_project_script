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
   COMPANY DETAILS AND PENDING CUSTOMER SESSION
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
$pending = $_SESSION['customer_pending'] ?? null;

if (
    !is_array($pending)
    || (int) ($pending['company_id'] ?? 0) !== $company_id
    || (int) ($pending['customer_id'] ?? 0) <= 0
    || (int) ($pending['account_id'] ?? 0) <= 0
    || !filter_var((string) ($pending['email'] ?? ''), FILTER_VALIDATE_EMAIL)
    || !in_array((string) ($pending['purpose'] ?? ''), ['register', 'login'], true)
) {
    unset($_SESSION['customer_pending'], $_SESSION['customer_auth_mode']);
    ca_flash('info', 'Please sign in or create an account to request a verification code.');
    header('Location: customer_auth.php?account=login');
    exit;
}

/* ---------------------------------------------------------
   OTP DELIVERY HELPER
--------------------------------------------------------- */
function ca_verify_send_otp(
    PDO $pdo,
    array $pending,
    string $companyName,
    array $company
): bool {
    $accountId = (int) $pending['account_id'];
    $companyId = (int) $pending['company_id'];

    // Remove previous codes so that only the newest code can be used.
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
        (int) $pending['customer_id'],
        $accountId,
        (string) $pending['email'],
        password_hash($otp, PASSWORD_DEFAULT),
        (string) $pending['purpose'],
    ]);

    // Use the same SMTP credentials loaded by config/smtp.php.
    $smtpUsername = trim((string) ($_SESSION['supportmail'] ?? ''));
    $smtpPassword = (string) ($_SESSION['support_email_password'] ?? '');

    if (
        !filter_var($smtpUsername, FILTER_VALIDATE_EMAIL)
        || $smtpPassword === ''
    ) {
        error_log('Marketplace customer OTP resend: SMTP credentials are unavailable in the session.');
        return false;
    }

    $subject = $pending['purpose'] === 'register'
        ? 'Verify your customer account'
        : 'Your customer sign-in code';

    $email = (string) $pending['email'];
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
        error_log('Marketplace customer OTP resend email: ' . $exception->getMessage());
        return false;
    }
}

/* ---------------------------------------------------------
   HANDLE OTP SUBMISSIONS AND RESEND REQUESTS
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ca_check_csrf()) {
        ca_flash('error', 'Your security token expired. Please try again.');
        header('Location: customer_verify_otp.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'resend_otp') {
        $lastSentAt = (int) ($_SESSION['customer_otp_last_sent_at'] ?? 0);
        $now = time();

        // One resend is allowed every 60 seconds, with a maximum of five per hour.
        if ($lastSentAt > 0 && ($now - $lastSentAt) < 60) {
            $remaining = 60 - ($now - $lastSentAt);
            ca_flash('error', "Please wait {$remaining} seconds before requesting another code.");
            header('Location: customer_verify_otp.php');
            exit;
        }

        $resendWindowStart = (int) ($_SESSION['customer_otp_resend_window'] ?? 0);
        $resendCount = (int) ($_SESSION['customer_otp_resend_count'] ?? 0);

        if ($resendWindowStart === 0 || ($now - $resendWindowStart) >= 3600) {
            $resendWindowStart = $now;
            $resendCount = 0;
        }

        if ($resendCount >= 5) {
            ca_flash('error', 'You have reached the resend limit. Please try again in an hour.');
            header('Location: customer_verify_otp.php');
            exit;
        }

        try {
            $sent = ca_verify_send_otp($pdo, $pending, $companyName, $company);

            $_SESSION['customer_otp_last_sent_at'] = $now;
            $_SESSION['customer_otp_resend_window'] = $resendWindowStart;
            $_SESSION['customer_otp_resend_count'] = $resendCount + 1;

            ca_flash(
                $sent ? 'success' : 'error',
                $sent
                    ? 'A fresh verification code has been sent. Any previous code is now invalid.'
                    : 'A new code was created, but the server could not deliver the email. Please contact the company if this continues.'
            );
        } catch (Throwable $exception) {
            error_log('Marketplace customer OTP resend: ' . $exception->getMessage());
            ca_flash('error', 'A new verification code could not be generated. Please try again.');
        }

        header('Location: customer_verify_otp.php');
        exit;
    }

    if ($action === 'verify_otp') {
        $otp = trim((string) ($_POST['otp'] ?? ''));

        if (!preg_match('/^\d{6}$/', $otp)) {
            ca_flash('error', 'Enter the six-digit verification code.');
            header('Location: customer_verify_otp.php');
            exit;
        }

        $query = $pdo->prepare(
            'SELECT id, otp_hash, attempts
             FROM marketplace_customer_otps
             WHERE company_id = ?
                AND customer_id = ?
                AND account_id = ?
                AND email = ?
                AND purpose = ?
                AND expires_at > NOW()
             ORDER BY id DESC
             LIMIT 1'
        );
        $query->execute([
            $company_id,
            (int) $pending['customer_id'],
            (int) $pending['account_id'],
            (string) $pending['email'],
            (string) $pending['purpose'],
        ]);
        $otpRow = $query->fetch(PDO::FETCH_ASSOC);

        if (!$otpRow) {
            ca_flash('error', 'Your verification code has expired or is no longer valid. Request a fresh code below.');
            header('Location: customer_verify_otp.php');
            exit;
        }

        if ((int) $otpRow['attempts'] >= 5) {
            ca_flash('error', 'Too many incorrect attempts. Request a fresh verification code.');
            header('Location: customer_verify_otp.php');
            exit;
        }

        if (!password_verify($otp, (string) $otpRow['otp_hash'])) {
            $updateAttempts = $pdo->prepare(
                'UPDATE marketplace_customer_otps
                 SET attempts = attempts + 1
                 WHERE id = ?'
            );
            $updateAttempts->execute([(int) $otpRow['id']]);

            ca_flash('error', 'The verification code is incorrect. Please check it and try again.');
            header('Location: customer_verify_otp.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Consume all codes for this account to prevent reuse.
            $deleteCodes = $pdo->prepare(
                'DELETE FROM marketplace_customer_otps
                 WHERE company_id = ? AND account_id = ?'
            );
            $deleteCodes->execute([$company_id, (int) $pending['account_id']]);

            if ($pending['purpose'] === 'register') {
                $activateAccount = $pdo->prepare(
                    "UPDATE customer_accounts
                     SET account_status = 'active', activated_at = NOW(), updated_at = NOW()
                     WHERE id = ? AND company_id = ?"
                );
                $activateAccount->execute([(int) $pending['account_id'], $company_id]);

                // Activate the matching users record created during registration.
                $activateUser = $pdo->prepare(
                    "UPDATE users
                     SET status = 'active', changed_at = ?
                     WHERE company_id = ? AND email = ? AND role = 'customer'"
                );
                $activateUser->execute([
                    date('Y-m-d H:i:s'),
                    $company_id,
                    (string) $pending['email'],
                ]);
            }

            $updateLogin = $pdo->prepare(
                'UPDATE customer_accounts
                 SET last_login_at = NOW(), updated_at = NOW()
                 WHERE id = ? AND company_id = ?'
            );
            $updateLogin->execute([(int) $pending['account_id'], $company_id]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Marketplace customer OTP verification: ' . $exception->getMessage());
            ca_flash('error', 'Verification could not be completed. Please try again.');
            header('Location: customer_verify_otp.php');
            exit;
        }

        // Load the active customer-role users row before establishing the
        // authenticated session. The application user_id must always be users.id.
        $customerUserLookup = $pdo->prepare(
            "SELECT id, username, location_id
             FROM users
             WHERE company_id = ?
               AND LOWER(email) = LOWER(?)
               AND LOWER(role) = 'customer'
               AND LOWER(status) = 'active'
             ORDER BY id DESC
             LIMIT 1"
        );
        $customerUserLookup->execute([$company_id, (string) $pending['email']]);
        $customerUser = $customerUserLookup->fetch(PDO::FETCH_ASSOC);

        if (!$customerUser) {
            error_log(
                'Marketplace customer OTP verification: no active customer users row found for account '
                . (int) $pending['account_id']
            );
            ca_flash(
                'error',
                'Your email was verified, but your customer login profile could not be found. Please contact the company.'
            );
            header('Location: customer_auth.php?account=login');
            exit;
        }

        $customerQuery = $pdo->prepare(
            'SELECT fullname, email, location_id
             FROM customers
             WHERE id = ? AND company_id = ?
             LIMIT 1'
        );
        $customerQuery->execute([(int) $pending['customer_id'], $company_id]);
        $customer = $customerQuery->fetch(PDO::FETCH_ASSOC) ?: [];

        // Regenerate the session ID without clearing existing marketplace
        // session data. In particular, marketplace_cart remains intact.
        session_regenerate_id(true);

        // Standard application identity: always the matching users.id.
        $_SESSION['user_id'] = (int) $customerUser['id'];
        $_SESSION['username'] = (string) (
            ($customerUser['username'] ?? '') ?: ($customer['fullname'] ?? 'Customer')
        );
        $_SESSION['role'] = 'customer';
        $_SESSION['company_id'] = $company_id;
        $_SESSION['location_id'] = (int) (
            ($customerUser['location_id'] ?? 0) ?: ($customer['location_id'] ?? 0)
        );

        // Marketplace-specific customer identity remains available to the
        // marketplace/customer dashboard independently of the cart contents.
        $_SESSION['marketplace_customer_id'] = (int) $pending['customer_id'];
        $_SESSION['marketplace_company_id'] = $company_id;
        $_SESSION['marketplace_customer_name'] = (string) ($customer['fullname'] ?? 'Customer');
        $_SESSION['marketplace_customer_email'] = (string) (
            ($customer['email'] ?? '') ?: $pending['email']
        );
        $_SESSION['customer_account_id'] = (int) $pending['account_id'];

        unset(
            $_SESSION['customer_pending'],
            $_SESSION['customer_auth_mode'],
            $_SESSION['customer_pending_user_id'],
            $_SESSION['customer_otp_last_sent_at'],
            $_SESSION['customer_otp_resend_window'],
            $_SESSION['customer_otp_resend_count']
        );

        header('Location: client/index.php');
        exit;
    }
}

/* ---------------------------------------------------------
   DISPLAY THE SEPARATE OTP PAGE
--------------------------------------------------------- */
$flash = ca_take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your account | <?= ca_h($companyName) ?></title>

    <style>
        * { box-sizing: border-box; }
        body {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            background: #f4f6f8;
            color: #1b2935;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .verify-card {
            width: 100%;
            max-width: 460px;
            padding: 30px;
            border: 1px solid #e5eaf0;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 50px #1a2a3810;
        }
        .muted { color: #718096; font-size: 13px; line-height: 1.6; }
        label { display: block; margin: 18px 0 7px; font-size: 12px; font-weight: 700; }
        input {
            width: 100%;
            padding: 15px;
            border: 1px solid #dce3e9;
            border-radius: 9px;
            font-size: 22px;
            letter-spacing: 8px;
            text-align: center;
        }
        .btn {
            width: 100%;
            margin-top: 16px;
            padding: 13px;
            border: 0;
            border-radius: 9px;
            background: #b58b45;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
        }
        .btn.secondary { background: #172936; }
        .alert { margin: 14px 0; padding: 12px; border-radius: 9px; background: #eef6f1; }
        .alert.error { background: #fff0f0; color: #a52d2d; }
        .alert.info { background: #edf4fb; color: #24567d; }
        .actions { margin-top: 18px; }
        .back-link { display: inline-block; margin-top: 16px; color: #8f6b31; text-decoration: none; }
        button:disabled { opacity: .6; cursor: not-allowed; }
    </style>
</head>
<body>
    <main class="verify-card">
        <a class="back-link" href="marketplace.php">&larr; Back to marketplace</a>

        <h1><?= ca_h($companyName) ?></h1>
        <h2>Verify your email</h2>
        <p class="muted">
            Enter the six-digit code sent to
            <strong><?= ca_h($pending['email']) ?></strong>.
            The code remains valid for 10 minutes.
        </p>

        <?php if ($flash): ?>
            <div class="alert <?= ca_h($flash['type'] ?? 'info') ?>">
                <?= ca_h($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="customer_verify_otp.php">
            <input type="hidden" name="csrf_token" value="<?= ca_h(ca_csrf()) ?>">
            <input type="hidden" name="action" value="verify_otp">

            <label for="otp">Verification code</label>
            <input
                id="otp"
                name="otp"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                minlength="6"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="000000"
                required
                autofocus
            >

            <button class="btn" type="submit">Verify and continue</button>
        </form>

        <div class="actions">
            <p class="muted">
                If your code has expired or did not arrive, request a fresh one.
                A new code automatically invalidates the previous code.
            </p>

            <form method="post" action="customer_verify_otp.php">
                <input type="hidden" name="csrf_token" value="<?= ca_h(ca_csrf()) ?>">
                <input type="hidden" name="action" value="resend_otp">
                <button class="btn secondary" type="submit">Resend OTP</button>
            </form>
        </div>
    </main>
</body>
</html>
