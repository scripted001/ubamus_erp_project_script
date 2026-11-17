<?php
/*
|--------------------------------------------------------------------------
| ORICORE AUTHENTICATION, SESSION SECURITY & MESSENGER PRESENCE
|--------------------------------------------------------------------------
| FEATURES:
| 1. Secure PHP session initialization.
| 2. 30-minute inactivity timeout.
| 3. Activity-based session refresh.
| 4. Individual browser/session identification.
| 5. Messenger online-presence tracking.
| 6. Suspended-account protection.
| 7. Automatic company information retrieval.
| 8. Session cleanup on logout/expiration.
|
| REQUIREMENTS:
| - $pdo must already be initialized before this file is included.
| - MySQL/MariaDB database.
| - messenger_session_presence table must exist with the
|   columns specified in the accompanying migration.
| - Include this file on authenticated pages.
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Africa/Lagos');

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $isSecure = (
        !empty($_SERVER['HTTPS']) &&
        strtolower($_SERVER['HTTPS']) !== 'off'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/* =========================================================
   CONFIGURATION
========================================================= */

$session_timeout = 1800; // 30 minutes
$now = time();

$login_redirect = '../index.php';

/* =========================================================
   SESSION CLEANUP FUNCTION
========================================================= */

if (!function_exists('oricore_destroy_session')) {

    function oricore_destroy_session($pdo = null, $redirect = true)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if ($redirect) {
                header('Location: ../index.php');
                exit;
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mark this browser session offline in Messenger
        |--------------------------------------------------------------------------
        */

        if ($pdo instanceof PDO && !empty($_SESSION['user_id'])) {

            try {

                $sessionHash = hash('sha256', session_id());

                $stmt = $pdo->prepare("
                    UPDATE messenger_session_presence
                    SET is_online = 0,
                        last_seen = NOW()
                    WHERE session_id = :session_id
                ");

                $stmt->execute([
                    ':session_id' => $sessionHash
                ]);

            } catch (PDOException $e) {

                error_log(
                    'Oricore presence cleanup error: ' . $e->getMessage()
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Destroy PHP session
        |--------------------------------------------------------------------------
        */

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }

        session_destroy();

        if ($redirect) {
            header('Location: ../index.php');
            exit;
        }
    }
}

/* =========================================================
   AUTHENTICATION CHECK
========================================================= */

if (empty($_SESSION['user_id'])) {

    oricore_destroy_session($pdo, true);
}

/* =========================================================
   30-MINUTE INACTIVITY VALIDATION
========================================================= */

/*
| A new session receives its initial activity timestamp.
| Subsequent requests do not automatically reset the timer.
| Actual user interaction refreshes it through the activity
| endpoint handled below.
*/

if (!isset($_SESSION['last_activity'])) {

    $_SESSION['last_activity'] = $now;
}

$last_activity = (int) $_SESSION['last_activity'];

if (($now - $last_activity) >= $session_timeout) {

    oricore_destroy_session($pdo, true);
}

/* =========================================================
   INDIVIDUAL SESSION IDENTIFICATION
========================================================= */

if (empty($_SESSION['oricore_session_initialized'])) {

    session_regenerate_id(true);

    $_SESSION['oricore_session_initialized'] = true;

    /*
    | Reset the session identifier after regeneration.
    */

    $_SESSION['last_activity'] = $now;
}

/*
| Use a hash rather than exposing the actual PHP session ID
| in the Messenger database.
*/

$oricore_session_hash = hash('sha256', session_id());

/* =========================================================
   MESSENGER ACTIVITY ENDPOINT
========================================================= */

/*
| This endpoint is called by the browser only when the user
| actually interacts with the application.
|
| Background polling alone must never reset inactivity.
*/

if (
    isset($_GET['__oricore_activity']) &&
    $_GET['__oricore_activity'] === '1'
) {

    if (($now - (int) $_SESSION['last_activity']) >= $session_timeout) {

        oricore_destroy_session($pdo, false);

        http_response_code(401);
        exit;
    }

    $_SESSION['last_activity'] = $now;

    try {

        $stmt = $pdo->prepare("
            INSERT INTO messenger_session_presence
                (
                    user_id,
                    session_id,
                    is_online,
                    last_activity,
                    last_seen
                )
            VALUES
                (
                    :user_id,
                    :session_id,
                    1,
                    NOW(),
                    NOW()
                )
            ON DUPLICATE KEY UPDATE
                is_online = 1,
                last_activity = NOW(),
                last_seen = NOW()
        ");

        $stmt->execute([
            ':user_id'    => $_SESSION['user_id'],
            ':session_id' => $oricore_session_hash
        ]);

    } catch (PDOException $e) {

        error_log(
            'Oricore Messenger activity error: ' . $e->getMessage()
        );

        http_response_code(500);
        exit;
    }

    http_response_code(204);
    exit;
}

/* =========================================================
   USER ACCOUNT STATUS VALIDATION
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT status
        FROM users
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $_SESSION['user_id']
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        oricore_destroy_session($pdo, true);
    }

    if (strtolower(trim((string) $user['status'])) === 'suspended') {

        oricore_destroy_session($pdo, true);
    }

} catch (PDOException $e) {

    error_log(
        'Oricore authentication validation error: ' . $e->getMessage()
    );

    http_response_code(500);

    exit('Authentication validation failed. Please try again later.');
}

/* =========================================================
   COMPANY VALIDATION
========================================================= */

$domain = trim($_SESSION['domain'] ?? '');

if ($domain === '') {

    oricore_destroy_session($pdo, true);
}

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            company_logo,
            company_name
        FROM companies
        WHERE company_domain = :domain
        LIMIT 1
    ");

    $stmt->execute([
        ':domain' => $domain
    ]);

    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {

        oricore_destroy_session($pdo, true);
    }

    $_SESSION['company_id']   = $company['id'];
    $_SESSION['company_logo'] = $company['company_logo'];
    $_SESSION['company_name'] = $company['company_name'];

} catch (PDOException $e) {

    error_log(
        'Oricore company validation error: ' . $e->getMessage()
    );

    http_response_code(500);

    exit('Company validation failed. Please try again later.');
}

/* =========================================================
   MESSENGER SESSION PRESENCE REGISTRATION
========================================================= */

try {

    $stmt = $pdo->prepare("
        INSERT INTO messenger_session_presence
            (
                user_id,
                session_id,
                is_online,
                last_activity,
                last_seen
            )
        VALUES
            (
                :user_id,
                :session_id,
                1,
                NOW(),
                NOW()
            )
        ON DUPLICATE KEY UPDATE
            is_online = 1,
            last_seen = NOW()
    ");

    $stmt->execute([
        ':user_id'    => $_SESSION['user_id'],
        ':session_id' => $oricore_session_hash
    ]);

} catch (PDOException $e) {

    error_log(
        'Oricore Messenger presence registration error: ' .
        $e->getMessage()
    );
}

/* =========================================================
   CLIENT-SIDE REAL USER ACTIVITY TRACKING
========================================================= */

/*
| Important:
| - Mouse movement
| - Keyboard interaction
| - Touch interaction
| - Scrolling
| - Clicking
|
| These interactions refresh the inactivity timer.
|
| A heartbeat is sent at most once every 20 seconds while
| the user is interacting.
|
| Passive background checks do not refresh inactivity.
*/

if (
    !headers_sent() &&
    !defined('ORICORE_ACTIVITY_SCRIPT_REGISTERED')
) {

    define('ORICORE_ACTIVITY_SCRIPT_REGISTERED', true);

    register_shutdown_function(function () {

        if (http_response_code() >= 300) {
            return;
        }

        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            return;
        }

        if (
            !empty($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false
        ) {
            return;
        }

        echo <<<'HTML'

<script>
(function () {

    'use strict';

    if (window.__oricoreActivityInitialized) {
        return;
    }

    window.__oricoreActivityInitialized = true;

    let lastPing = 0;
    let pending = false;

    const PING_INTERVAL = 20000;

    function refreshActivity() {

        const now = Date.now();

        if (pending || (now - lastPing) < PING_INTERVAL) {
            return;
        }

        pending = true;
        lastPing = now;

        const url = new URL(window.location.href);

        url.searchParams.set('__oricore_activity', '1');

        fetch(url.pathname + url.search, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'X-Oricore-Activity': '1'
            }
        })
        .then(function (response) {

            if (response.status === 401) {

                window.location.href = '../index.php';
            }

        })
        .catch(function () {

            // Network failures do not artificially extend
            // the authenticated session.

        })
        .finally(function () {

            pending = false;

        });
    }

    const events = [
        'mousedown',
        'mousemove',
        'keydown',
        'scroll',
        'touchstart',
        'touchmove',
        'click',
        'pointerdown'
    ];

    events.forEach(function (eventName) {

        document.addEventListener(
            eventName,
            refreshActivity,
            {
                passive: true
            }
        );

    });

    document.addEventListener('visibilitychange', function () {

        if (!document.hidden) {
            refreshActivity();
        }

    });

})();
</script>

HTML;

    });
}

/* =========================================================
   VALIDATION COMPLETE
========================================================= */

/*
| Available session variables:
|
| $_SESSION['user_id']
| $_SESSION['domain']
| $_SESSION['company_id']
| $_SESSION['company_logo']
| $_SESSION['company_name']
|
| Session inactivity limit: 30 minutes.
| Messenger presence is tracked per browser session.
|
*/

?>