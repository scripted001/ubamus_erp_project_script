<?php
/**
 * Shared helpers for the customer marketplace area.
 * Keep this file in the same directory as customer_auth.php and customer_dashboard.php.
 */
declare(strict_types=1);

date_default_timezone_set('Africa/Lagos');


require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/company_data.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection unavailable.');
}

$company_id = isset($company_id) ? (int) $company_id : 0;

if ($company_id <= 0) {
    http_response_code(404);
    exit('Company marketplace could not be identified.');
}

/** Escape values before placing them in HTML. */
function ca_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Create or return the customer-area CSRF token. */
function ca_csrf(): string
{
    if (empty($_SESSION['customer_area_csrf'])) {
        $_SESSION['customer_area_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['customer_area_csrf'];
}

/** Validate a submitted customer-area CSRF token. */
function ca_check_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['customer_area_csrf'])
        && hash_equals(
            (string) $_SESSION['customer_area_csrf'],
            (string) $_POST['csrf_token']
        );
}

/** Store a one-time message for the next page request. */
function ca_flash(string $type, string $message): void
{
    $_SESSION['customer_area_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/** Retrieve and clear the one-time message. */
function ca_take_flash(): ?array
{
    $flash = $_SESSION['customer_area_flash'] ?? null;
    unset($_SESSION['customer_area_flash']);

    return is_array($flash) ? $flash : null;
}

/** Return the signed-in customer account for this company, if valid. */
function ca_account(PDO $pdo, int $companyId): ?array
{
    $customerId = (int) ($_SESSION['marketplace_customer_id'] ?? 0);
    $sessionCompanyId = (int) ($_SESSION['marketplace_company_id'] ?? 0);

    if ($customerId <= 0 || $sessionCompanyId !== $companyId) {
        return null;
    }

    $query = $pdo->prepare(
        "SELECT
            ca.id AS account_id,
            ca.customer_id,
            ca.login_email,
            c.fullname,
            c.email,
            c.phone,
            c.location_id
         FROM customer_accounts ca
         INNER JOIN customers c
            ON c.id = ca.customer_id
            AND c.company_id = ca.company_id
         WHERE ca.company_id = ?
            AND ca.customer_id = ?
            AND ca.account_status = 'active'
         LIMIT 1"
    );

    $query->execute([$companyId, $customerId]);
    $account = $query->fetch(PDO::FETCH_ASSOC);

    return $account ?: null;
}

/** Render the common customer-area page header and styles. */
function ca_layout_start(string $title, string $company): void
{
    $flash = ca_take_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= ca_h($title) ?> | <?= ca_h($company) ?></title>

        <link rel="stylesheet" href="fontawesome/css/all.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            :root {
                --navy: #172936;
                --gold: #b58b45;
                --ink: #1d2935;
                --muted: #718096;
                --line: #e8edf2;
                --paper: #f5f7f9;
                --white: #fff;
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                background: var(--paper);
                color: var(--ink);
                font-family: Inter, "Segoe UI", Arial, sans-serif;
            }

            a { color: inherit; text-decoration: none; }
            button, input, textarea, select { font: inherit; }

            .top {
                position: sticky;
                top: 0;
                z-index: 5;
                display: flex;
                height: 70px;
                align-items: center;
                justify-content: space-between;
                padding: 0 5%;
                border-bottom: 1px solid var(--line);
                background: #fff;
            }

            .brand { color: var(--navy); font-weight: 900; }
            .brand span { color: var(--gold); }

            .wrap {
                max-width: 1180px;
                margin: 28px auto;
                padding: 0 20px;
            }

            .layout {
                display: grid;
                grid-template-columns: 245px minmax(0, 1fr);
                gap: 22px;
            }

            .side, .panel, .stat {
                border: 1px solid var(--line);
                border-radius: 16px;
                background: #fff;
            }

            .side { height: max-content; padding: 18px; }

            .side a {
                display: flex;
                gap: 12px;
                padding: 13px 12px;
                border-radius: 10px;
                color: #526171;
                font-size: 14px;
            }

            .side a.active, .side a:hover {
                background: #f5f1e9;
                color: #8d692f;
            }

            .panel { margin-bottom: 18px; padding: 24px; }

            .grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
            }

            .stat { padding: 18px; }
            .stat b { display: block; margin-top: 8px; font-size: 25px; }
            .muted { color: var(--muted); font-size: 13px; }

            .btn {
                display: inline-block;
                padding: 11px 16px;
                border: 0;
                border-radius: 9px;
                background: var(--navy);
                color: #fff;
                cursor: pointer;
            }

            .btn.gold { background: var(--gold); }
            .btn.light { background: #f0f2f4; color: var(--ink); }

            .field { margin: 12px 0; }
            .field label {
                display: block;
                margin-bottom: 6px;
                font-size: 12px;
                font-weight: 700;
            }

            .field input, .field textarea, .field select {
                width: 100%;
                padding: 12px;
                border: 1px solid var(--line);
                border-radius: 9px;
                background: #fff;
            }

            .alert {
                margin: 12px 0;
                padding: 12px 15px;
                border-radius: 9px;
                background: #eef6f1;
            }

            .alert.error { background: #fff0f0; color: #a52d2d; }
            .alert.info { background: #edf4fb; color: #24567d; }

            .table { width: 100%; border-collapse: collapse; }
            .table th, .table td {
                padding: 12px;
                border-bottom: 1px solid var(--line);
                text-align: left;
                vertical-align: top;
                font-size: 13px;
            }

            .chatbox {
                height: 390px;
                overflow: auto;
                padding: 15px;
                border-radius: 12px;
                background: #f7f8fa;
            }

            .msg {
                max-width: 78%;
                margin: 9px 0;
                padding: 11px 13px;
                border: 1px solid var(--line);
                border-radius: 12px;
                background: #fff;
            }

            .msg.mine { margin-left: auto; background: #f8f0e2; }
            .small { color: var(--muted); font-size: 11px; }

            @media (max-width: 760px) {
                .layout { grid-template-columns: 1fr; }
                .side { display: flex; gap: 4px; overflow: auto; }
                .side a { white-space: nowrap; }
                .grid { grid-template-columns: 1fr; }
                .top { padding: 0 4%; }
                .wrap { padding: 0 12px; }
                .panel { padding: 16px; }
                .table { min-width: 650px; }
                .tablewrap { overflow: auto; }
            }
        </style>
    </head>
    <body>
        <header class="top">
            <a class="brand" href="marketplace.php">
                <?= ca_h($company) ?> <span>Customer</span>
            </a>
            <a href="marketplace.php" class="muted">
                <i class="fa-solid fa-store"></i> Marketplace
            </a>
        </header>

        <?php if ($flash): ?>
            <div class="wrap">
                <div class="alert <?= ca_h($flash['type'] ?? 'info') ?>">
                    <?= ca_h($flash['message'] ?? '') ?>
                </div>
            </div>
        <?php endif; ?>
    <?php
}

/** Render the customer-area navigation. */
function ca_nav(string $page): void
{
    ?>
    <nav class="side">
        <a class="<?= $page === 'home' ? 'active' : '' ?>" href="customer_dashboard.php">
            <i class="fa-solid fa-gauge"></i> Overview
        </a>

        <a class="<?= $page === 'messenger' ? 'active' : '' ?>" href="customer_dashboard.php?page=messenger">
            <i class="fa-regular fa-comments"></i> Messenger
        </a>

        <a class="<?= $page === 'history' ? 'active' : '' ?>" href="customer_dashboard.php?page=history">
            <i class="fa-solid fa-clock-rotate-left"></i> Purchase history
        </a>

        <a class="<?= $page === 'cart' ? 'active' : '' ?>" href="customer_dashboard.php?page=cart">
            <i class="fa-solid fa-cart-shopping"></i> My cart
        </a>

        <a href="marketplace.php">
            <i class="fa-solid fa-bag-shopping"></i> Continue shopping
        </a>

        <form method="post" action="customer_auth.php" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= ca_h(ca_csrf()) ?>">
            <input type="hidden" name="action" value="logout">
            <button class="btn light" style="width: 100%; margin-top: 8px; text-align: left;">
                <i class="fa-solid fa-right-from-bracket"></i> Sign out
            </button>
        </form>
    </nav>
    <?php
}
