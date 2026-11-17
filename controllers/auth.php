
<?php

/**
 * Main authentication controller.
 *
 * Staff and marketplace customers authenticate through the users table.
 *
 * Marketplace session data, including marketplace_cart, is preserved
 * when the user logs in.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection unavailable.');
}

/**
 * Redirect helper.
 */
function auth_redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Only accept POST requests.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_redirect('../index.php');
}

/**
 * Get login details.
 */
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Email and password are required.';
    auth_redirect('../index.php');
}

try {

    /**
     * STEP 1: RESOLVE COMPANY FROM THE EXISTING DOMAIN SESSION.
     */
    $domain = trim((string) ($_SESSION['domain'] ?? ''));

    if ($domain === '') {
        $_SESSION['error'] = 'Company not recognised.';
        auth_redirect('../index.php');
    }

    $stmtCheck = $pdo->prepare("
        SELECT id
        FROM companies
        WHERE company_domain = ?
        LIMIT 1
    ");

    $stmtCheck->execute([$domain]);

    $company = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        $_SESSION['error'] = 'Company not recognised.';
        auth_redirect('../index.php');
    }

    $company_id = (int) $company['id'];

    /**
     * STEP 2: FETCH USER FROM USERS TABLE.
     *
     * This applies to both staff and customers.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            username,
            email,
            password,
            role,
            status,
            company_id,
            location_id
        FROM users
        WHERE company_id = ?
          AND LOWER(email) = LOWER(?)
        LIMIT 1
    ");

    $stmt->execute([$company_id, $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'Email not found.';
        auth_redirect('../index.php');
    }

    /**
     * STEP 3: CHECK ACCOUNT STATUS.
     */
    if (strtolower((string) $user['status']) !== 'active') {
        $_SESSION['error'] = 'Your account is suspended.';
        auth_redirect('../index.php');
    }

    /**
     * STEP 4: VERIFY PASSWORD.
     */
    if (!password_verify($password, (string) $user['password'])) {
        $_SESSION['error'] = 'Incorrect password.';
        auth_redirect('../index.php');
    }

    /**
     * STEP 5: PRESERVE THE EXISTING SESSION.
     *
     * Regenerating the session ID does not clear existing session data.
     *
     * Therefore, marketplace_cart and other marketplace session
     * information remain available after login.
     *
     * Do not unset marketplace_cart.
     */
    session_regenerate_id(true);

    /**
     * STEP 6: SET THE NORMAL APPLICATION USER SESSION.
     */
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['company_id'] = $company_id;
    $_SESSION['location_id'] = (int) ($user['location_id'] ?? 0);

    /**
     * STEP 7: CUSTOMER LOGIN.
     *
     * Customer authentication has already succeeded through users.
     *
     * Keep the actual users.id in user_id.
     *
     * Existing marketplace session information remains untouched,
     * including marketplace_cart when available.
     *
     * Customer identity/profile information will be retrieved
     * separately inside client/index.php.
     */
    if (strtolower((string) $user['role']) === 'customer') {

        auth_redirect('../client/index.php');

    }

    /**
     * STEP 8: EXISTING STAFF REDIRECTS.
     */
    switch ((string) $user['role']) {

        case 'Branch Manager':
            auth_redirect('../admin/index.php');

        case 'Head of Administrator':
            auth_redirect('../hoa/index.php');

        case 'Cashier':
            auth_redirect('../staff/index.php');

        case 'Production Manager':
            auth_redirect('../production/index.php');

        case 'Operation Manager':
            auth_redirect('../operator/index.php');

        case 'Support':
            auth_redirect('../company/index.php');

        default:

            unset(
                $_SESSION['user_id'],
                $_SESSION['username'],
                $_SESSION['role'],
                $_SESSION['company_id'],
                $_SESSION['location_id']
            );

            $_SESSION['error'] = 'Unrecognized user role.';

            auth_redirect('../index.php');
    }

} catch (PDOException $e) {

    error_log('Authentication error: ' . $e->getMessage());

    http_response_code(500);

    exit('A login error occurred. Please try again later.');
}
