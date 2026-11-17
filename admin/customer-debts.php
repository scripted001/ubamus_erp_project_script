<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

/* ============================================================
   BRANCH MANAGER AUTHORIZATION
   Scope is taken from the authenticated database user record.
============================================================ */

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    exit('User not logged in.');
}

$authStmt = $pdo->prepare("
    SELECT company_id, location_id, role, status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$authStmt->execute([$user_id]);
$loggedInUser = $authStmt->fetch(PDO::FETCH_ASSOC);

if (
    !$loggedInUser
    || strtolower(trim((string)($loggedInUser['role'] ?? ''))) !== 'branch manager'
    || strtolower(trim((string)($loggedInUser['status'] ?? ''))) !== 'active'
    || (int)($loggedInUser['company_id'] ?? 0) <= 0
    || (int)($loggedInUser['location_id'] ?? 0) <= 0
    || (int)($_SESSION['company_id'] ?? 0) !== (int)$loggedInUser['company_id']
    || (int)($_SESSION['location_id'] ?? 0) !== (int)$loggedInUser['location_id']
) {
    http_response_code(403);
    exit('Access denied. An active Branch Manager account and assigned branch are required.');
}

$company_id = (int)$loggedInUser['company_id'];
$location_id = (int)$loggedInUser['location_id'];
$user_email = $_SESSION['user_email'] ?? null;

$branchCheck = $pdo->prepare("
    SELECT id
    FROM locations
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");
$branchCheck->execute([$location_id, $company_id]);

if (!$branchCheck->fetchColumn()) {
    http_response_code(403);
    exit('Access denied. Your assigned branch is inactive or unavailable.');
}

/* ============================================================
   DATE AND CUSTOMER FILTERS
============================================================ */

$today = date('Y-m-d');
$from = $_GET['from'] ?? $today;
$to = $_GET['to'] ?? $today;

$validDate = static function ($date) {
    if (!is_string($date)) {
        return false;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
};

if (!$validDate($from)) {
    $from = $today;
}

if (!$validDate($to)) {
    $to = $today;
}

if ($from > $to) {
    [$from, $to] = [$to, $from];
}

$startDateTime = $from . ' 00:00:00';
$endDateTime = $to . ' 23:59:59';

$requestedCustomerId = $_GET['customer_id'] ?? '';
$customer_id = (
    is_scalar($requestedCustomerId)
    && ctype_digit((string)$requestedCustomerId)
    && (int)$requestedCustomerId > 0
) ? (int)$requestedCustomerId : null;


/* ============================================================
   FETCH CUSTOMER DEBTS
============================================================ */


$debtSql = "
    SELECT
        d.*,
        c.fullname,
        c.phone,
        c.email,
        l.name AS location_name,
        l.type AS location_type,
        l.city AS location_city,
        l.state AS location_state
    FROM customer_debts d

    LEFT JOIN customers c
        ON c.id = d.customer_id
       AND c.company_id = d.company_id
       AND c.location_id = d.location_id

    LEFT JOIN locations l
        ON l.id = d.location_id
       AND l.company_id = d.company_id

    WHERE d.company_id = ?
      AND d.location_id = ?
      AND d.created_at BETWEEN ? AND ?
";

$debtParams = [
    $company_id,
    $location_id,
    $startDateTime,
    $endDateTime
];

if ($customer_id !== null) {
    $debtSql .= " AND d.customer_id = ?";
    $debtParams[] = $customer_id;
}

$debtSql .= " ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($debtSql);
$stmt->execute($debtParams);

$customer_debts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ============================================================
   ANALYTICS
============================================================ */

$total_amount       = 0;
$total_amount_paid  = 0;
$total_debt_value   = 0;
$total_balance      = 0;

$debt_count = count($customer_debts);

$customers_owing = [];

foreach ($customer_debts as $debt) {

    $total_amount += (float)($debt['total_amount'] ?? 0);

    $total_amount_paid += (float)($debt['amount_paid'] ?? 0);

    $total_balance += (float)($debt['balance'] ?? 0);

    if ((float)($debt['balance'] ?? 0) > 0) {
        $customers_owing[$debt['customer_id']] = true;
    }
}

$total_debt_value = $total_balance;

$total_customers_owing = count($customers_owing);


/* ============================================================
   FETCH CUSTOMERS FOR FILTER
============================================================ */

$stmtCustomers = $pdo->prepare("
    SELECT DISTINCT
        d.customer_id,
        c.fullname

    FROM customer_debts d

    INNER JOIN customers c
        ON c.id = d.customer_id
       AND c.company_id = d.company_id
       AND c.location_id = d.location_id

    WHERE d.company_id = ?
      AND d.location_id = ?
      AND d.customer_id IS NOT NULL
      AND c.fullname IS NOT NULL
      AND c.fullname != ''

    ORDER BY c.fullname ASC
");

$stmtCustomers->execute([$company_id, $location_id]);

$debt_customers = $stmtCustomers->fetchAll(PDO::FETCH_ASSOC);


/* ============================================================
   HELPER
============================================================ */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
<!doctype html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    http-equiv="content-type"
    content="text/html;charset=utf-8"
>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="theme-color"
    content="#f5f7fb"
>

<title>
    <?= e($company_name) ?> | Customer Debt
</title>


<link
    rel="stylesheet"
    href="../fontawesome/css/all.css"
>

<link
    href="style.css"
    rel="stylesheet"
>


<style>

/* ============================================================
   CUSTOMER DEBT — FUTURISTIC LIGHT GLASS UI
============================================================ */

:root {

    --debt-bg: #f4f7fb;
    --debt-surface: rgba(255,255,255,.78);
    --debt-surface-strong: rgba(255,255,255,.92);

    --debt-border: rgba(15,23,42,.09);

    --debt-text: #172033;
    --debt-heading: #0f172a;
    --debt-muted: #64748b;

    --debt-accent: #2563eb;
    --debt-accent-dark: #1d4ed8;

    --debt-shadow:
        0 20px 50px rgba(15,23,42,.08);

    --debt-radius: 22px;
}


/* ============================================================
   PAGE BACKGROUND
============================================================ */

.debt-page {

    position: relative;

    min-height: 100vh;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(37,99,235,.07),
            transparent 28%
        ),
        radial-gradient(
            circle at 90% 20%,
            rgba(14,165,233,.06),
            transparent 28%
        ),
        linear-gradient(
            135deg,
            #f8fafc 0%,
            #f1f5f9 100%
        );

    color: var(--debt-text);
}


/* ============================================================
   ABSTRACT BACKGROUND
============================================================ */

.debt-page::before {

    content: "";

    position: fixed;

    width: 280px;
    height: 280px;

    top: 100px;
    right: -120px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(37,99,235,.10),
            transparent 70%
        );

    pointer-events: none;
}


.debt-page::after {

    content: "";

    position: fixed;

    width: 240px;
    height: 240px;

    bottom: 50px;
    left: -120px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(100,116,139,.09),
            transparent 70%
        );

    pointer-events: none;
}


/* ============================================================
   MAIN WRAPPER
============================================================ */

.debt-container {

    width: 100%;

    max-width: 1500px;

    margin: 0 auto;

    padding: 20px;
}


/* ============================================================
   HERO
============================================================ */

.debt-hero {

    position: relative;

    overflow: hidden;

    margin-bottom: 24px;

    padding: 28px;

    border-radius: 28px;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.90),
            rgba(241,245,249,.72)
        );

    border: 1px solid rgba(255,255,255,.95);

    box-shadow:
        0 25px 70px rgba(15,23,42,.08),
        inset 0 1px 0 rgba(255,255,255,.9);

    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}


.debt-hero-grid {

    position: absolute;

    inset: 0;

    opacity: .35;

    background-image:
        linear-gradient(
            rgba(37,99,235,.07) 1px,
            transparent 1px
        ),
        linear-gradient(
            90deg,
            rgba(37,99,235,.07) 1px,
            transparent 1px
        );

    background-size: 28px 28px;

    mask-image:
        linear-gradient(
            to right,
            transparent,
            black 35%,
            black 70%,
            transparent
        );

    pointer-events: none;
}


.debt-hero-orb {

    position: absolute;

    width: 170px;
    height: 170px;

    right: 40px;
    top: -90px;

    border-radius: 50%;

    border: 1px solid rgba(37,99,235,.12);

    box-shadow:
        0 0 0 25px rgba(37,99,235,.025),
        0 0 0 50px rgba(37,99,235,.02);

    pointer-events: none;
}


.debt-hero-content {

    position: relative;

    z-index: 2;

    max-width: 850px;
}


.debt-eyebrow {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 7px 12px;

    margin-bottom: 12px;

    border-radius: 999px;

    background: rgba(37,99,235,.07);

    border: 1px solid rgba(37,99,235,.10);

    color: #1d4ed8;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.debt-hero h1 {

    margin: 0;

    color: var(--debt-heading);

    font-size: clamp(25px, 4vw, 40px);

    font-weight: 800;

    letter-spacing: -.03em;
}


.debt-hero p {

    margin: 10px 0 0;

    color: #526174;

    font-size: 14px;

    line-height: 1.7;

    max-width: 720px;
}


.debt-hero-meta {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 18px;
}


.debt-meta-chip {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 8px 12px;

    border-radius: 12px;

    background: rgba(255,255,255,.75);

    border: 1px solid rgba(15,23,42,.07);

    color: #475569;

    font-size: 12px;

    font-weight: 600;

    box-shadow: 0 6px 20px rgba(15,23,42,.04);
}


/* ============================================================
   ANALYTICS
============================================================ */

.debt-stats {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 15px;

    margin-bottom: 20px;
}


.debt-stat {

    position: relative;

    overflow: hidden;

    min-width: 0;

    padding: 20px;

    border-radius: 20px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.88),
            rgba(255,255,255,.66)
        );

    border: 1px solid rgba(255,255,255,.95);

    box-shadow: var(--debt-shadow);

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}


.debt-stat:hover {

    transform: translateY(-3px);

    box-shadow:
        0 25px 60px rgba(15,23,42,.11);
}


.debt-stat::after {

    content: "";

    position: absolute;

    width: 80px;
    height: 80px;

    right: -35px;
    bottom: -35px;

    border-radius: 50%;

    border: 1px solid rgba(37,99,235,.08);
}


.debt-stat-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 10px;
}


.debt-stat-label {

    color: #64748b;

    font-size: 12px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .04em;
}


.debt-stat-icon {

    width: 38px;
    height: 38px;

    flex: 0 0 38px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background: rgba(37,99,235,.08);

    color: #2563eb;

    font-size: 15px;
}


.debt-stat-value {

    margin-top: 10px;

    color: #0f172a;

    font-size: clamp(20px, 3vw, 28px);

    font-weight: 800;

    letter-spacing: -.02em;

    word-break: break-word;
}


.debt-stat-sub {

    margin-top: 4px;

    color: #94a3b8;

    font-size: 11px;
}


/* ============================================================
   FILTER GLASS
============================================================ */

.debt-filter {

    position: relative;

    margin-bottom: 20px;

    padding: 18px;

    border-radius: 20px;

    background:
        rgba(255,255,255,.72);

    border: 1px solid rgba(255,255,255,.95);

    box-shadow:
        0 18px 45px rgba(15,23,42,.06);

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}


.debt-filter-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 15px;
}


.debt-filter-title {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #172033;

    font-size: 15px;

    font-weight: 800;
}


.debt-filter-title i {

    color: #2563eb;
}


.debt-filter-period {

    color: #64748b;

    font-size: 12px;
}


.debt-filter-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 12px;

    align-items: end;
}


.debt-field label {

    display: block;

    margin-bottom: 7px;

    color: #475569;

    font-size: 12px;

    font-weight: 700;
}


.debt-input-wrap {

    position: relative;
}


.debt-input-wrap i {

    position: absolute;

    left: 13px;

    top: 50%;

    transform: translateY(-50%);

    color: #64748b;

    pointer-events: none;

    z-index: 2;
}


.debt-input,
.debt-select {

    width: 100%;

    height: 46px;

    padding:
        0 13px 0 40px;

    border-radius: 13px;

    border: 1px solid #dbe2ea;

    background: rgba(255,255,255,.82);

    color: #172033;

    outline: none;

    font-size: 13px;

    font-weight: 600;

    transition: all .2s ease;

    box-sizing: border-box;
}


.debt-select {

    padding-left: 40px;

    appearance: auto;
}


.debt-input:focus,
.debt-select:focus {

    border-color: rgba(37,99,235,.45);

    box-shadow:
        0 0 0 4px rgba(37,99,235,.08);

    background: #fff;
}


.debt-filter-actions {

    display: flex;

    gap: 8px;

    align-items: center;
}


.debt-filter-btn {

    width: 100%;

    height: 46px;

    border: 0;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color: #fff;

    font-size: 13px;

    font-weight: 800;

    cursor: pointer;

    box-shadow:
        0 10px 24px rgba(37,99,235,.20);

    transition: all .2s ease;
}


.debt-filter-btn:hover {

    transform: translateY(-1px);

    box-shadow:
        0 14px 30px rgba(37,99,235,.26);
}


/* ============================================================
   RECORD HEADER
============================================================ */

.debt-record-panel {

    overflow: hidden;

    border-radius: 22px;

    background:
        rgba(255,255,255,.70);

    border: 1px solid rgba(255,255,255,.95);

    box-shadow:
        0 22px 55px rgba(15,23,42,.07);

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}


.debt-record-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    padding: 18px 20px;

    border-bottom: 1px solid rgba(15,23,42,.07);

    background:
        rgba(248,250,252,.72);
}


.debt-record-title {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #0f172a;

    font-size: 16px;

    font-weight: 800;
}


.debt-record-title i {

    color: #2563eb;
}


.debt-record-count {

    padding: 6px 10px;

    border-radius: 999px;

    background: rgba(37,99,235,.07);

    color: #1d4ed8;

    font-size: 11px;

    font-weight: 800;
}


.debt-record-body {

    padding: 16px;
}


/* ============================================================
   DEBT CARD
============================================================ */

.debt-card {

    position: relative;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        auto;

    gap: 20px;

    margin-bottom: 14px;

    padding: 20px;

    border-radius: 20px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.86),
            rgba(248,250,252,.68)
        );

    border: 1px solid rgba(15,23,42,.075);

    box-shadow:
        0 12px 35px rgba(15,23,42,.055);

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;
}


.debt-card:last-child {

    margin-bottom: 0;
}


.debt-card:hover {

    transform: translateY(-2px);

    border-color:
        rgba(37,99,235,.15);

    box-shadow:
        0 20px 45px rgba(15,23,42,.09);
}


.debt-main {

    min-width: 0;
}


/* ============================================================
   DEBT IDENTITY
============================================================ */

.debt-identity {

    display: flex;

    align-items: flex-start;

    gap: 13px;

    margin-bottom: 18px;
}


.debt-avatar {

    width: 46px;
    height: 46px;

    flex: 0 0 46px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 15px;

    background:
        linear-gradient(
            135deg,
            #eff6ff,
            #e0f2fe
        );

    color: #2563eb;

    border: 1px solid rgba(37,99,235,.10);
}


.debt-customer-name {

    color: #0f172a;

    font-size: 17px;

    font-weight: 800;

    line-height: 1.3;
}


.debt-invoice {

    margin-top: 4px;

    color: #64748b;

    font-size: 12px;

    font-weight: 600;
}


.debt-invoice strong {

    color: #334155;
}


/* ============================================================
   INFORMATION GRID
============================================================ */

.debt-info-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 9px;
}


.debt-info-item {

    min-width: 0;

    padding: 10px 12px;

    border-radius: 12px;

    background:
        rgba(248,250,252,.72);

    border: 1px solid rgba(15,23,42,.055);
}


.debt-info-label {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-bottom: 4px;

    color: #94a3b8;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .04em;
}


.debt-info-label i {

    color: #64748b;

    font-size: 10px;
}


.debt-info-value {

    color: #334155;

    font-size: 12px;

    font-weight: 650;

    line-height: 1.45;

    overflow-wrap: anywhere;
}


/* ============================================================
   FINANCIAL SUMMARY
============================================================ */

.debt-financial {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 8px;

    margin-top: 12px;
}


.debt-money {

    padding: 12px;

    border-radius: 13px;

    background:
        rgba(255,255,255,.72);

    border: 1px solid rgba(15,23,42,.065);
}


.debt-money-label {

    color: #64748b;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;
}


.debt-money-value {

    margin-top: 4px;

    color: #172033;

    font-size: 14px;

    font-weight: 800;

    overflow-wrap: anywhere;
}


.debt-money.balance {

    background:
        rgba(254,242,242,.72);

    border-color:
        rgba(239,68,68,.10);
}


.debt-money.balance .debt-money-value {

    color: #b91c1c;
}


/* ============================================================
   FOOTER INFORMATION
============================================================ */

.debt-meta {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 12px;
}


.debt-meta-item {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    max-width: 100%;

    padding: 7px 9px;

    border-radius: 9px;

    background: rgba(241,245,249,.72);

    color: #64748b;

    font-size: 10px;

    line-height: 1.4;
}


.debt-meta-item i {

    color: #64748b;
}


/* ============================================================
   ACTIONS
============================================================ */

.debt-actions {

    display: flex;

    flex-direction: column;

    justify-content: flex-start;

    align-items: stretch;

    gap: 8px;

    min-width: 145px;
}


.debt-action {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    min-height: 40px;

    padding: 8px 12px;

    border-radius: 11px;

    border: 1px solid rgba(15,23,42,.08);

    background: rgba(255,255,255,.82);

    color: #334155;

    font-size: 11px;

    font-weight: 800;

    cursor: pointer;

    text-decoration: none;

    transition: all .2s ease;
}


.debt-action:hover {

    transform: translateY(-1px);

    background: #fff;

    box-shadow:
        0 8px 20px rgba(15,23,42,.08);
}


.debt-action.invoice {

    color: #1d4ed8;

    border-color:
        rgba(37,99,235,.14);

    background:
        rgba(239,246,255,.75);
}


.debt-action.reminder {

    color: #166534;

    border-color:
        rgba(22,101,52,.12);

    background:
        rgba(240,253,244,.78);
}


.debt-action.clear {

    color: #7c3aed;

    border-color:
        rgba(124,58,237,.12);

    background:
        rgba(245,243,255,.78);
}


.debt-action.partial {

    color: #b45309;

    border-color:
        rgba(180,83,9,.12);

    background:
        rgba(255,251,235,.82);
}


/* ============================================================
   EMPTY STATE
============================================================ */

.debt-empty {

    padding: 60px 20px;

    text-align: center;
}


.debt-empty-icon {

    width: 64px;
    height: 64px;

    margin: 0 auto 14px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 20px;

    background: #f1f5f9;

    color: #64748b;

    font-size: 24px;
}


.debt-empty h4 {

    margin: 0;

    color: #334155;

    font-size: 16px;

    font-weight: 800;
}


.debt-empty p {

    margin-top: 6px;

    color: #94a3b8;

    font-size: 12px;
}


/* ============================================================
   DARK MODE — ONLY IF YOUR GLOBAL ALPINE TOGGLES IT
   The page remains light by default.
============================================================ */

.dark .debt-page {

    background:
        #111827;

    color: #e5e7eb;
}


.dark .debt-hero,
.dark .debt-stat,
.dark .debt-filter,
.dark .debt-record-panel,
.dark .debt-card {

    background:
        rgba(17,24,39,.86);

    border-color:
        rgba(255,255,255,.08);
}


.dark .debt-hero h1,
.dark .debt-stat-value,
.dark .debt-record-title,
.dark .debt-customer-name {

    color: #f8fafc;
}


.dark .debt-hero p,
.dark .debt-stat-label,
.dark .debt-filter-period,
.dark .debt-info-value,
.dark .debt-invoice {

    color: #cbd5e1;
}


.dark .debt-input,
.dark .debt-select {

    background: rgba(15,23,42,.85);

    color: #f8fafc;

    border-color: rgba(255,255,255,.10);
}


/* ============================================================
   TABLET
============================================================ */

@media (max-width: 1100px) {

    .debt-stats {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .debt-filter-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .debt-filter-actions {

        grid-column: span 2;
    }

    .debt-actions {

        min-width: 125px;
    }
}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 768px) {

    .debt-container {

        padding: 12px;
    }


    .debt-hero {

        padding: 21px 18px;

        border-radius: 22px;
    }


    .debt-hero h1 {

        font-size: 25px;
    }


    .debt-hero p {

        font-size: 13px;

        line-height: 1.65;
    }


    .debt-hero-orb {

        right: -65px;

        width: 140px;
        height: 140px;
    }


    .debt-stats {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 10px;
    }


    .debt-stat {

        padding: 15px;

        border-radius: 17px;
    }


    .debt-stat-value {

        font-size: 19px;
    }


    .debt-stat-icon {

        width: 32px;
        height: 32px;

        flex-basis: 32px;

        border-radius: 10px;
    }


    .debt-filter {

        padding: 14px;

        border-radius: 18px;
    }


    .debt-filter-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 5px;
    }


    .debt-filter-grid {

        grid-template-columns: 1fr;

        gap: 10px;
    }


    .debt-filter-actions {

        grid-column: auto;
    }


    .debt-record-header {

        padding: 15px;

        align-items: flex-start;

        flex-direction: column;

        gap: 8px;
    }


    .debt-record-body {

        padding: 10px;
    }


    .debt-card {

        display: block;

        padding: 14px;

        border-radius: 17px;
    }


    .debt-identity {

        margin-bottom: 13px;
    }


    .debt-info-grid {

        grid-template-columns: 1fr;
    }


    .debt-financial {

        grid-template-columns: 1fr;
    }


    .debt-actions {

        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        margin-top: 13px;

        min-width: 0;
    }


    .debt-action {

        width: 100%;

        min-height: 42px;

        box-sizing: border-box;
    }


    .debt-meta {

        display: grid;

        grid-template-columns: 1fr;
    }


    .debt-meta-item {

        width: 100%;

        box-sizing: border-box;
    }
}


/* ============================================================
   SMALL PHONES
============================================================ */

@media (max-width: 420px) {

    .debt-container {

        padding: 9px;
    }


    .debt-stats {

        gap: 8px;
    }


    .debt-stat {

        padding: 13px;
    }


    .debt-stat-label {

        font-size: 10px;
    }


    .debt-stat-value {

        font-size: 17px;
    }


    .debt-actions {

        grid-template-columns: 1fr;
    }


    .debt-hero-meta {

        display: grid;

        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body
    x-data="{
        page: 'ecommerce',
        loaded: true,
        darkMode: false,
        stickyMenu: false,
        sidebarToggle: false,
        scrollTop: false
    }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode')) || false;
        $watch('darkMode', value =>
            localStorage.setItem('darkMode', JSON.stringify(value))
        )
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>


<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>


    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>


            <div class="debt-page">

                <div class="debt-container">


                    <!-- ==================================================
                         HERO
                    ================================================== -->

                    <section class="debt-hero">

                        <div class="debt-hero-grid"></div>

                        <div class="debt-hero-orb"></div>

                        <div class="debt-hero-content">

                            <div class="debt-eyebrow">
                                <i class="fa-solid fa-chart-line"></i>
                                Financial Intelligence
                            </div>

                            <h1>
                                Customer Debt Monitor
                            </h1>

                            <p>
                                Track outstanding customer obligations,
                                deposits, balances and repayment activity
                                from one intelligent financial workspace.
                            </p>


                            <div class="debt-hero-meta">

                                <span class="debt-meta-chip">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    <?= e($from) ?>
                                    →
                                    <?= e($to) ?>
                                </span>

                                <span class="debt-meta-chip">
                                    <i class="fa-solid fa-building"></i>
                                    <?= e($company_name) ?>
                                </span>

                                <span class="debt-meta-chip">
                                    <i class="fa-solid fa-user-clock"></i>
                                    <?= $debt_count ?> debt records
                                </span>

                            </div>

                        </div>

                    </section>


                    <!-- ==================================================
                         ANALYTICS
                    ================================================== -->

                    <section class="debt-stats">


                        <!-- TOTAL TRANSACTION -->

                        <div class="debt-stat">

                            <div class="debt-stat-top">

                                <div class="debt-stat-label">
                                    Transaction Value
                                </div>

                                <div class="debt-stat-icon">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </div>

                            </div>

                            <div class="debt-stat-value">
                                ₦<?= number_format($total_amount, 2) ?>
                            </div>

                            <div class="debt-stat-sub">
                                Total transaction amount
                            </div>

                        </div>


                        <!-- DEPOSIT -->

                        <div class="debt-stat">

                            <div class="debt-stat-top">

                                <div class="debt-stat-label">
                                    Deposited
                                </div>

                                <div class="debt-stat-icon">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>

                            </div>

                            <div class="debt-stat-value">
                                ₦<?= number_format($total_amount_paid, 2) ?>
                            </div>

                            <div class="debt-stat-sub">
                                Amount already paid
                            </div>

                        </div>


                        <!-- NUMBER OF DEBTS -->

                        <div class="debt-stat">

                            <div class="debt-stat-top">

                                <div class="debt-stat-label">
                                    Debt Records
                                </div>

                                <div class="debt-stat-icon">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </div>

                            </div>

                            <div class="debt-stat-value">
                                <?= number_format($debt_count) ?>
                            </div>

                            <div class="debt-stat-sub">
                                Records in selected period
                            </div>

                        </div>


                        <!-- OUTSTANDING -->

                        <div class="debt-stat">

                            <div class="debt-stat-top">

                                <div class="debt-stat-label">
                                    Outstanding
                                </div>

                                <div class="debt-stat-icon">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>

                            </div>

                            <div class="debt-stat-value">
                                ₦<?= number_format($total_balance, 2) ?>
                            </div>

                            <div class="debt-stat-sub">
                                <?= $total_customers_owing ?> customer(s) owing
                            </div>

                        </div>

                    </section>


                    <!-- ==================================================
                         FILTER
                    ================================================== -->

                    <section class="debt-filter">

                        <div class="debt-filter-header">

                            <div class="debt-filter-title">

                                <i class="fa-solid fa-sliders"></i>

                                Debt Intelligence Filters

                            </div>

                            <div class="debt-filter-period">

                                Showing
                                <?= e($from) ?>
                                to
                                <?= e($to) ?>

                            </div>

                        </div>


                        <form
                            method="GET"
                            id="dateFilterForm"
                        >

                            <div class="debt-filter-grid">


                                <!-- FROM -->

                                <div class="debt-field">

                                    <label for="fromDate">
                                        Start Date
                                    </label>

                                    <div class="debt-input-wrap">

                                        <i class="fa-regular fa-calendar"></i>

                                        <input
                                            type="date"
                                            name="from"
                                            id="fromDate"
                                            value="<?= e($from) ?>"
                                            class="debt-input"
                                        >

                                    </div>

                                </div>


                                <!-- TO -->

                                <div class="debt-field">

                                    <label for="toDate">
                                        End Date
                                    </label>

                                    <div class="debt-input-wrap">

                                        <i class="fa-regular fa-calendar-check"></i>

                                        <input
                                            type="date"
                                            name="to"
                                            id="toDate"
                                            value="<?= e($to) ?>"
                                            class="debt-input"
                                        >

                                    </div>

                                </div>


                                <!-- CUSTOMER -->

                                <div class="debt-field">

                                    <label for="customer_id">
                                        Customer
                                    </label>

                                    <div class="debt-input-wrap">

                                        <i class="fa-solid fa-user"></i>

                                        <select
                                            name="customer_id"
                                            id="customer_id"
                                            class="debt-select"
                                        >

                                            <option value="">
                                                All Customers
                                            </option>

                                            <?php foreach ($debt_customers as $customer): ?>

                                                <option
                                                    value="<?= e($customer['customer_id']) ?>"
                                                    <?= ($customer_id === (int)$customer['customer_id']) ? 'selected' : '' ?>
                                                >
                                                    <?= e($customer['fullname']) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                </div>


                                <!-- FILTER BUTTON -->

                                <div class="debt-filter-actions">

                                    <button
                                        type="submit"
                                        class="debt-filter-btn"
                                    >

                                        <i class="fa-solid fa-filter"></i>

                                        Apply Filter

                                    </button>

                                </div>

                            </div>

                        </form>

                    </section>


                    <!-- ==================================================
                         DEBT RECORDS
                    ================================================== -->

                    <section class="debt-record-panel">


                        <div class="debt-record-header">

                            <div class="debt-record-title">

                                <i class="fa-solid fa-layer-group"></i>

                                Customer Debt Records

                            </div>

                            <div
                                class="debt-record-count"
                                id="debtRecordCount"
                            >
                                <?= count($customer_debts) ?> Records
                            </div>

                        </div>


                        <div class="debt-record-body">

                            <div id="expensesResults">


                            <?php if (empty($customer_debts)): ?>

                                <div class="debt-empty">

                                    <div class="debt-empty-icon">

                                        <i class="fa-solid fa-circle-check"></i>

                                    </div>

                                    <h4>
                                        No debt records found
                                    </h4>

                                    <p>
                                        There are no customer debt records
                                        within the selected period.
                                    </p>

                                </div>

                            <?php endif; ?>


                            <?php foreach ($customer_debts as $debt): ?>


                                <?php

                                $invoice =
                                    ($debt['invoice_number'] ?? '') . '.pdf';

                                $invoicePath =
                                    '../invoices/invoice_' . $invoice;

                                $soldBy = 'Unknown';

                                if (!empty($debt['debt_by'])) {

                                    $stmtSold = $pdo->prepare("
                                        SELECT username
                                        FROM users
                                        WHERE id = ?
                                          AND company_id = ?
                                          AND location_id = ?
                                        LIMIT 1
                                    ");

                                    $stmtSold->execute([
                                        $debt['debt_by'],
                                        $company_id,
                                        $location_id
                                    ]);

                                    $soldB =
                                        $stmtSold->fetch(PDO::FETCH_ASSOC);

                                    if ($soldB) {
                                        $soldBy =
                                            $soldB['username'];
                                    }
                                }

                                ?>


                                <!-- ==========================================
                                     DEBT CARD
                                =========================================== -->

                                <article
                                    class="debt-card"
                                    data-customer-id="<?= e($debt['customer_id']) ?>"
                                >


                                    <div class="debt-main">


                                        <!-- CUSTOMER IDENTITY -->

                                        <div class="debt-identity">

                                            <div class="debt-avatar">

                                                <i class="fa-solid fa-user"></i>

                                            </div>

                                            <div>

                                                <div class="debt-customer-name">

                                                    <?= e($debt['fullname'] ?: 'Unknown Customer') ?>

                                                </div>

                                                <div class="debt-invoice">

                                                    <strong>
                                                        Invoice:
                                                    </strong>

                                                    <?= e($debt['invoice_number']) ?>

                                                </div>

                                            </div>

                                        </div>


                                        <!-- INFORMATION -->

                                        <div class="debt-info-grid">


                                            <div class="debt-info-item">

                                                <div class="debt-info-label">

                                                    <i class="fa-solid fa-phone"></i>

                                                    Mobile

                                                </div>

                                                <div class="debt-info-value">

                                                    <?= e($debt['phone'] ?: 'Not provided') ?>

                                                </div>

                                            </div>


                                            <div class="debt-info-item">

                                                <div class="debt-info-label">

                                                    <i class="fa-solid fa-envelope"></i>

                                                    Email

                                                </div>

                                                <div class="debt-info-value">

                                                    <?= e($debt['email'] ?: 'Not provided') ?>

                                                </div>

                                            </div>


                                            <div class="debt-info-item">

                                                <div class="debt-info-label">

                                                    <i class="fa-solid fa-receipt"></i>

                                                    Transaction

                                                </div>

                                                <div class="debt-info-value">

                                                    <?= e($debt['debt_type']) ?>

                                                </div>

                                            </div>


                                            <div class="debt-info-item">

                                                <div class="debt-info-label">

                                                    <i class="fa-solid fa-user-tie"></i>

                                                    Attendant

                                                </div>

                                                <div class="debt-info-value">

                                                    <?= e($soldBy) ?>

                                                </div>

                                            </div>


                                            <div class="debt-info-item">

                                                <div class="debt-info-label">

                                                    <i class="fa-solid fa-calendar-xmark"></i>

                                                    Due Date

                                                </div>

                                                <div class="debt-info-value">

                                                    <?= e($debt['due_date']) ?>

                                                </div>

                                            </div>


                                            <div class="debt-info-item">

                                                <div class="debt-info-label">

                                                    <i class="fa-solid fa-building"></i>

                                                    Branch

                                                </div>

                                                <div class="debt-info-value">

                                                    <?= e($debt['location_name']) ?>

                                                    <?php if (!empty($debt['location_type'])): ?>

                                                        · <?= e($debt['location_type']) ?>

                                                    <?php endif; ?>

                                                </div>

                                            </div>


                                        </div>


                                        <!-- FINANCIAL -->

                                        <div class="debt-financial">


                                            <div class="debt-money">

                                                <div class="debt-money-label">

                                                    Transaction

                                                </div>

                                                <div class="debt-money-value">

                                                    ₦<?= number_format(
                                                        (float)$debt['total_amount'],
                                                        2
                                                    ) ?>

                                                </div>

                                            </div>


                                            <div class="debt-money">

                                                <div class="debt-money-label">

                                                    Deposited

                                                </div>

                                                <div class="debt-money-value">

                                                    ₦<?= number_format(
                                                        (float)$debt['amount_paid'],
                                                        2
                                                    ) ?>

                                                </div>

                                            </div>


                                            <div class="debt-money balance">

                                                <div class="debt-money-label">

                                                    Outstanding

                                                </div>

                                                <div class="debt-money-value">

                                                    ₦<?= number_format(
                                                        (float)$debt['balance'],
                                                        2
                                                    ) ?>

                                                </div>

                                            </div>


                                        </div>


                                        <!-- META -->

                                        <div class="debt-meta">


                                            <?php if (!empty($debt['staff_responsible'])): ?>

                                                <div class="debt-meta-item">

                                                    <i class="fa-solid fa-user-check"></i>

                                                    By:
                                                    <?= e($debt['staff_responsible']) ?>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (!empty($debt['description'])): ?>

                                                <div class="debt-meta-item">

                                                    <i class="fa-solid fa-align-left"></i>

                                                    <?= e($debt['description']) ?>

                                                </div>

                                            <?php endif; ?>


                                            <div class="debt-meta-item">

                                                <i class="fa-regular fa-clock"></i>

                                                Registered:
                                                <?= e($debt['created_at']) ?>

                                            </div>


                                            <?php if (!empty($debt['location_city']) || !empty($debt['location_state'])): ?>

                                                <div class="debt-meta-item">

                                                    <i class="fa-solid fa-location-dot"></i>

                                                    <?= e($debt['location_city']) ?>

                                                    <?php if (!empty($debt['location_state'])): ?>

                                                        ,
                                                        <?= e($debt['location_state']) ?>

                                                    <?php endif; ?>

                                                </div>

                                            <?php endif; ?>


                                        </div>

                                    </div>


                                    <!-- ======================================
                                         ACTIONS
                                    ======================================= -->

                                    <div class="debt-actions">


                                        <!-- INVOICE -->

                                        <?php if (
                                            !empty($debt['invoice_number']) &&
                                            file_exists($invoicePath)
                                        ): ?>

                                            <a
                                                href="<?= e($invoicePath) ?>"
                                                target="_blank"
                                                class="debt-action invoice"
                                            >

                                                <i class="fa-solid fa-file-pdf"></i>

                                                View Invoice

                                            </a>

                                        <?php endif; ?>


                                        <!-- REMINDER -->

                                        <form
                                            method="POST"
                                            action="debt_reminder.php"
                                            onsubmit="
                                                return confirm(
                                                    'You wish to send a reminder email to this customer concerning this debt. Do you wish to continue?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="debt_id"
                                                value="<?= e($debt['id']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="customer_name"
                                                value="<?= e($debt['fullname']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="attendant"
                                                value="<?= e($soldBy) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="customer_mobile"
                                                value="<?= e($debt['phone']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="customer_email"
                                                value="<?= e($debt['email']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="debt-action reminder"
                                            >

                                                <i class="fa-solid fa-bell"></i>

                                                Send Reminder

                                            </button>

                                        </form>


                                        <!-- CLEAR BALANCE -->

                                        <form
                                            method="POST"
                                            action="pay_debt.php"
                                            onsubmit="
                                                return confirm(
                                                    'Confirm that this debt has been paid in full.'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="debt_id"
                                                value="<?= e($debt['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="debt-action clear"
                                            >

                                                <i class="fa-solid fa-circle-check"></i>

                                                Clear Balance

                                            </button>

                                        </form>


                                        <!-- PARTIAL PAYMENT -->

                                        <form
                                            method="POST"
                                            action="pay_debt_partially.php"
                                            onsubmit="
                                                return confirm(
                                                    'Would you like to pay part of this debt?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="debt_id"
                                                value="<?= e($debt['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="debt-action partial"
                                            >

                                                <i class="fa-solid fa-coins"></i>

                                                Partial Payment

                                            </button>

                                        </form>


                                    </div>

                                </article>


                            <?php endforeach; ?>


                            </div>

                        </div>

                    </section>


                </div>

            </div>

        </main>

    </div>

</div>


<?php include('footer.php'); ?>


<script defer src="bundle.js"></script>


<script>


/* ============================================================
   DATE FILTER — SUBMIT ONLY AFTER CLICKING APPLY FILTER
============================================================ */

const fromDate = document.getElementById('fromDate');
const toDate = document.getElementById('toDate');
const dateFilterForm = document.getElementById('dateFilterForm');

if (dateFilterForm) {
    dateFilterForm.addEventListener('submit', function (event) {

        if (
            fromDate &&
            toDate &&
            fromDate.value &&
            toDate.value &&
            fromDate.value > toDate.value
        ) {
            event.preventDefault();

            alert('The end date cannot be earlier than the start date.');

            toDate.focus();
        }

    });
}

</script>


</body>

</html>