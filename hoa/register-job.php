<?php
include('../config/db.php');
include('user_data.php');
include('../company_data.php');

$location_id = $_SESSION['location_id'] ?? null;
$company_id  = $_SESSION['company_id'] ?? null;
$user_id     = $_SESSION['user_id'] ?? null;

// ============================================================
// USER
// ============================================================

$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_email = $user['email'] ?? null;

// ============================================================
// STAFF
// ============================================================

$stmt = $pdo->prepare("SELECT id FROM staffs WHERE email = ?");
$stmt->execute([$user_email]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$staff_id = $staff['id'] ?? null;

// ============================================================
// VALIDATION
// ============================================================

if (!$company_id || !$staff_id) {
    die("Missing company or staff ID. company=" . htmlspecialchars((string)$company_id) . " staff=" . htmlspecialchars((string)$staff_id));
}

// ============================================================
// CUSTOMERS
// ============================================================

$stmt = $pdo->prepare("
    SELECT id, fullname, phone, email
    FROM customers
    WHERE company_id = ?
    AND location_id = ?
    ORDER BY fullname ASC
");
$stmt->execute([$company_id, $location_id]);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// JOB CATEGORIES
// ============================================================

$stmt = $pdo->prepare("
    SELECT id, name
    FROM job_categories
    WHERE company_id = ?
    ORDER BY name ASC
");
$stmt->execute([$company_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    http-equiv="X-UA-Compatible"
    content="ie=edge"
>

<title><?= htmlspecialchars($company_name ?? 'Company'); ?> | Staff Area</title>

<!-- ======================================================
     FONT AWESOME
======================================================= -->
<link rel="stylesheet" href="../fontawesome/css/all.css">

<!-- EXISTING SYSTEM CSS -->
<link href="style.css" rel="stylesheet">
<link href="cart.css" rel="stylesheet">

<style>

    /* =====================================================
       FUTURISTIC LIGHT SERVICE TERMINAL
    ===================================================== */

    :root {
        --terminal-primary: #635bff;
        --terminal-primary-dark: #5148e5;
        --terminal-blue: #2563eb;
        --terminal-cyan: #0891b2;

        --terminal-text: #172033;
        --terminal-muted: #687386;
        --terminal-soft: #94a3b8;

        --terminal-border: rgba(148, 163, 184, .24);
        --terminal-border-strong: rgba(99, 91, 255, .25);

        --terminal-glass: rgba(255, 255, 255, .78);
        --terminal-glass-heavy: rgba(255, 255, 255, .92);

        --terminal-shadow:
            0 20px 60px rgba(15, 23, 42, .07),
            0 4px 18px rgba(99, 91, 255, .04);

        --terminal-radius: 22px;
    }

    /* =====================================================
       GLOBAL
    ===================================================== */

    .job-terminal,
    .job-terminal * {
        box-sizing: border-box;
    }

    .job-terminal {
        position: relative;
        min-height: calc(100vh - 80px);
        padding: 18px 0 45px;
        color: var(--terminal-text);
    }

    .job-terminal::before {
        content: "";
        position: fixed;
        width: 420px;
        height: 420px;
        right: -180px;
        top: 90px;
        border-radius: 50%;
        background: rgba(99, 91, 255, .055);
        filter: blur(3px);
        pointer-events: none;
        z-index: -1;
    }

    .job-terminal::after {
        content: "";
        position: fixed;
        width: 340px;
        height: 340px;
        left: -180px;
        bottom: 40px;
        border-radius: 50%;
        background: rgba(14, 165, 233, .045);
        filter: blur(3px);
        pointer-events: none;
        z-index: -1;
    }

    /* =====================================================
       HERO
    ===================================================== */

    .terminal-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 25px;

        margin-bottom: 20px;
        padding: 24px 26px;

        border: 1px solid var(--terminal-border);
        border-radius: var(--terminal-radius);

        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,.94),
                rgba(248,250,252,.78)
            );

        box-shadow: var(--terminal-shadow);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .terminal-hero::after {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        right: -100px;
        top: -130px;
        border-radius: 50%;
        background: rgba(99,91,255,.08);
        pointer-events: none;
    }

    .hero-left {
        position: relative;
        z-index: 2;
        min-width: 0;
    }

    .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;

        margin-bottom: 8px;

        font-size: 11px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;

        color: var(--terminal-primary);
    }

    .hero-kicker i {
        font-size: 12px;
    }

    .hero-title {
        margin: 0;
        font-size: clamp(23px, 3vw, 34px);
        line-height: 1.08;
        font-weight: 800;
        letter-spacing: -.035em;
        color: #172033;
    }

    .hero-description {
        max-width: 680px;
        margin: 9px 0 0;
        color: var(--terminal-muted);
        font-size: 13px;
        line-height: 1.65;
    }

    .hero-status {
        position: relative;
        z-index: 2;

        display: flex;
        align-items: center;
        gap: 10px;

        flex-shrink: 0;

        padding: 10px 14px;

        border: 1px solid rgba(34,197,94,.18);
        border-radius: 999px;

        background: rgba(240,253,244,.85);

        color: #15803d;

        font-size: 12px;
        font-weight: 700;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 5px rgba(34,197,94,.10);
    }

    /* =====================================================
       PROGRESS
    ===================================================== */

    .terminal-progress {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;

        margin-bottom: 20px;
    }

    .progress-node {
        display: flex;
        align-items: center;
        gap: 10px;

        min-width: 0;
        padding: 12px 14px;

        border: 1px solid var(--terminal-border);
        border-radius: 16px;

        background: rgba(255,255,255,.68);
        box-shadow: 0 8px 25px rgba(15,23,42,.035);

        transition: .2s ease;
    }

    .progress-node:hover {
        transform: translateY(-1px);
        border-color: var(--terminal-border-strong);
    }

    .progress-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;

        display: grid;
        place-items: center;

        border-radius: 11px;

        background: #f1f5f9;
        color: #64748b;

        font-size: 13px;
    }

    .progress-node.active .progress-icon {
        color: white;
        background: linear-gradient(
            135deg,
            var(--terminal-primary),
            #8b5cf6
        );
        box-shadow: 0 8px 20px rgba(99,91,255,.22);
    }

    .progress-copy {
        min-width: 0;
    }

    .progress-copy strong {
        display: block;
        font-size: 11px;
        font-weight: 800;
        color: #334155;
    }

    .progress-copy span {
        display: block;
        margin-top: 2px;
        color: #94a3b8;
        font-size: 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* =====================================================
       MAIN GRID
    ===================================================== */

    .terminal-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 20px;
        align-items: start;
    }

    .terminal-main {
        min-width: 0;
    }

    .terminal-side {
        min-width: 0;
        position: sticky;
        top: 18px;
    }

    /* =====================================================
       GLASS CARDS
    ===================================================== */

    .glass-card {
        border: 1px solid var(--terminal-border);
        border-radius: var(--terminal-radius);

        background: var(--terminal-glass);

        box-shadow: var(--terminal-shadow);

        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .form-card {
        overflow: hidden;
    }

    .section-block {
        padding: 21px;
        border-bottom: 1px solid rgba(148,163,184,.15);
    }

    .section-block:last-child {
        border-bottom: 0;
    }

    .section-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 17px;
    }

    .section-title-wrap {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    .section-icon {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;

        display: grid;
        place-items: center;

        border-radius: 12px;

        color: var(--terminal-primary);

        background:
            linear-gradient(
                135deg,
                rgba(99,91,255,.11),
                rgba(139,92,246,.07)
            );

        border: 1px solid rgba(99,91,255,.12);

        font-size: 14px;
    }

    .section-title {
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        color: #1e293b;
    }

    .section-subtitle {
        margin: 3px 0 0;
        font-size: 11px;
        color: #8a96a8;
        line-height: 1.5;
    }

    .section-badge {
        flex-shrink: 0;

        padding: 6px 9px;
        border-radius: 8px;

        background: #f8fafc;
        border: 1px solid #e2e8f0;

        color: #64748b;

        font-size: 10px;
        font-weight: 700;
    }

    /* =====================================================
       CUSTOMER SWITCH
    ===================================================== */

    .customer-mode {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .mode-option {
        position: relative;
    }

    .mode-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .mode-label {
        display: flex;
        align-items: center;
        gap: 12px;

        cursor: pointer;

        min-height: 62px;
        padding: 12px 14px;

        border: 1px solid #e2e8f0;
        border-radius: 15px;

        background: rgba(255,255,255,.72);

        transition: .2s ease;
    }

    .mode-label:hover {
        border-color: rgba(99,91,255,.3);
        background: #fff;
    }

    .mode-option input:checked + .mode-label {
        border-color: rgba(99,91,255,.42);
        background:
            linear-gradient(
                135deg,
                rgba(99,91,255,.07),
                rgba(139,92,246,.035)
            );
        box-shadow: inset 0 0 0 1px rgba(99,91,255,.05);
    }

    .mode-icon {
        width: 37px;
        height: 37px;

        display: grid;
        place-items: center;

        flex: 0 0 37px;

        border-radius: 11px;
        background: #f1f5f9;
        color: #64748b;
    }

    .mode-option input:checked + .mode-label .mode-icon {
        color: var(--terminal-primary);
        background: rgba(99,91,255,.1);
    }

    .mode-copy strong {
        display: block;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .mode-copy span {
        display: block;
        margin-top: 3px;
        color: #94a3b8;
        font-size: 10px;
    }

    /* =====================================================
       FORM GRID
    ===================================================== */

    .field-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 14px;
    }

    .field {
        min-width: 0;
        grid-column: span 4;
    }

    .field.span-6 {
        grid-column: span 6;
    }

    .field.span-8 {
        grid-column: span 8;
    }

    .field.span-12 {
        grid-column: span 12;
    }

    .field-label {
        display: flex;
        align-items: center;
        gap: 6px;

        margin-bottom: 7px;

        color: #475569;

        font-size: 11px;
        font-weight: 750;
    }

    .field-label i {
        color: #94a3b8;
        font-size: 10px;
    }

    .input-shell {
        position: relative;
    }

    .input-shell > i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);

        color: #94a3b8;
        font-size: 12px;

        pointer-events: none;
    }

    .terminal-input,
    .terminal-select,
    .terminal-textarea {
        width: 100%;

        border: 1px solid #dfe5ed;
        border-radius: 12px;

        background: rgba(255,255,255,.84);

        color: #1e293b;

        outline: none;

        font-family: inherit;
        font-size: 12px;

        transition:
            border-color .2s ease,
            box-shadow .2s ease,
            background .2s ease;
    }

    .terminal-input,
    .terminal-select {
        height: 45px;
        padding: 0 13px;
    }

    .input-shell .terminal-input {
        padding-left: 37px;
    }

    .terminal-textarea {
        min-height: 95px;
        padding: 12px 13px;
        resize: vertical;
    }

    .terminal-input::placeholder,
    .terminal-textarea::placeholder {
        color: #b0bac8;
    }

    .terminal-input:focus,
    .terminal-select:focus,
    .terminal-textarea:focus {
        border-color: rgba(99,91,255,.48);
        background: #fff;

        box-shadow:
            0 0 0 4px rgba(99,91,255,.075),
            0 5px 18px rgba(99,91,255,.035);
    }

    .terminal-input[readonly] {
        background: #f8fafc;
        color: #64748b;
    }

    .terminal-select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 38px;
        cursor: pointer;
    }

    .select-shell {
        position: relative;
    }

    .select-shell::after {
        content: "\f107";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;

        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);

        color: #94a3b8;
        pointer-events: none;
    }

    /* =====================================================
       SERVICE BUILDER
    ===================================================== */

    .service-builder {
        padding: 17px;

        border: 1px solid rgba(99,91,255,.13);
        border-radius: 17px;

        background:
            linear-gradient(
                135deg,
                rgba(248,250,255,.92),
                rgba(255,255,255,.82)
            );
        overflow-x: scroll;
    }

    .builder-grid {
        display: grid;
        grid-template-columns:
            minmax(145px, 1.25fr)
            minmax(180px, 1.55fr)
            minmax(125px, .8fr)
            90px
            125px
            auto;

        gap: 10px;
        align-items: end;
    }

    .builder-field {
        min-width: 0;
    }

    .builder-label {
        display: block;
        margin-bottom: 6px;

        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .price-field .terminal-input {
        font-weight: 800;
        color: #334155;
    }

    .line-total-field .terminal-input {
        font-weight: 800;
        color: var(--terminal-primary);
    }

    .add-service-btn {
        height: 45px;
        padding: 0 16px;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;

        border: 0;
        border-radius: 12px;

        background:
            linear-gradient(
                135deg,
                var(--terminal-primary),
                #7c3aed
            );

        color: #fff;

        font-size: 11px;
        font-weight: 800;

        cursor: pointer;

        box-shadow:
            0 10px 24px rgba(99,91,255,.20);

        transition: .2s ease;
        white-space: nowrap;
    }

    .add-service-btn:hover {
        transform: translateY(-1px);
        box-shadow:
            0 13px 28px rgba(99,91,255,.27);
    }

    .add-service-btn:active {
        transform: translateY(0);
    }

    /* =====================================================
       CART
    ===================================================== */

    .cart-wrapper {
        margin-top: 17px;

        border: 1px solid #e4e9f0;
        border-radius: 16px;

        background: rgba(255,255,255,.72);

        overflow: hidden;
    }

    .cart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;

        padding: 14px 16px;

        border-bottom: 1px solid #edf0f4;
    }

    .cart-title {
        display: flex;
        align-items: center;
        gap: 9px;

        margin: 0;

        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .cart-title i {
        color: var(--terminal-primary);
    }

    .cart-count {
        min-width: 23px;
        height: 23px;

        display: inline-grid;
        place-items: center;

        padding: 0 6px;

        border-radius: 999px;

        background: #f1f5f9;
        color: #64748b;

        font-size: 10px;
        font-weight: 800;
    }

    .cart-scroll {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #cartTable {
        width: 100%;
        min-width: 650px;
        border-collapse: collapse;
    }

    #cartTable th {
        padding: 10px 13px;

        background: #f8fafc;

        color: #94a3b8;

        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;

        border-bottom: 1px solid #edf0f4;
    }

    #cartTable td {
        padding: 12px 13px;

        color: #475569;

        font-size: 11px;

        border-bottom: 1px solid #f0f2f5;

        vertical-align: middle;
    }

    #cartTable tbody tr:last-child td {
        border-bottom: 0;
    }

    #cartTable tbody tr:hover {
        background: rgba(248,250,252,.75);
    }

    .empty-cart {
        text-align: center;
        padding: 30px 15px !important;
        color: #94a3b8 !important;
    }

    .empty-cart i {
        display: block;
        margin-bottom: 8px;
        font-size: 21px;
        color: #cbd5e1;
    }

    .empty-cart strong {
        display: block;
        color: #64748b;
        font-size: 11px;
    }

    .empty-cart span {
        display: block;
        margin-top: 3px;
        font-size: 10px;
    }

    .btn-remove {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 30px;
        height: 30px;

        border: 1px solid #fee2e2;
        border-radius: 9px;

        background: #fff7f7;
        color: #ef4444;

        cursor: pointer;

        transition: .2s ease;
    }

    .btn-remove:hover {
        background: #fee2e2;
        transform: translateY(-1px);
    }

    /* =====================================================
       CART FOOTER / TOTALS
    ===================================================== */

    .cart-summary {
        margin-left: auto;
        width: min(390px, 100%);
        padding: 15px 16px;

        border-top: 1px solid #edf0f4;

        background:
            linear-gradient(
                135deg,
                rgba(248,250,252,.76),
                rgba(255,255,255,.9)
            );
    }

    .summary-row {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 15px;

        padding: 7px 0;

        color: #64748b;
        font-size: 11px;
    }

    .summary-row strong {
        color: #334155;
        font-weight: 800;
    }

    .summary-row.total-row {
        margin-top: 6px;
        padding-top: 12px;
        border-top: 1px dashed #dbe2ea;
    }

    .summary-row.total-row span {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .summary-row.total-row strong {
        color: var(--terminal-primary);
        font-size: 18px;
    }

    .discount-inline {
        width: 90px;
        height: 32px;

        border: 1px solid #dfe5ed;
        border-radius: 8px;

        padding: 0 9px;

        outline: none;
        background: #fff;

        text-align: right;

        font-size: 11px;
        font-weight: 700;
    }

    .discount-inline:focus {
        border-color: rgba(99,91,255,.4);
        box-shadow: 0 0 0 3px rgba(99,91,255,.07);
    }

    /* =====================================================
       PAYMENT
    ===================================================== */

    .payment-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
    }

    .payment-status-wrap {
        position: relative;
    }

    .payment-insight {
        display: flex;
        align-items: flex-start;
        gap: 10px;

        margin-top: 12px;
        padding: 11px 13px;

        border-radius: 12px;

        background: #f8fafc;
        border: 1px solid #e8edf3;

        color: #64748b;

        font-size: 10px;
        line-height: 1.55;
    }

    .payment-insight i {
        margin-top: 1px;
        color: var(--terminal-primary);
    }

    #partialPaymentBox {
        margin-top: 14px;
        padding: 15px;

        border: 1px dashed rgba(99,91,255,.23);
        border-radius: 14px;

        background: rgba(99,91,255,.025);
    }

    /* =====================================================
       SIDE INTELLIGENCE PANEL
    ===================================================== */

    .intelligence-card {
        overflow: hidden;
    }

    .intelligence-head {
        padding: 18px 18px 15px;

        border-bottom: 1px solid #edf0f4;
    }

    .intelligence-kicker {
        display: flex;
        align-items: center;
        gap: 7px;

        color: var(--terminal-primary);

        font-size: 9px;
        font-weight: 900;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .intelligence-kicker i {
        font-size: 10px;
    }

    .intelligence-title {
        margin: 6px 0 0;

        color: #1e293b;

        font-size: 18px;
        font-weight: 850;
        letter-spacing: -.025em;
    }

    .intelligence-body {
        padding: 17px;
    }

    .metric-box {
        padding: 14px;

        border: 1px solid #e7ebf1;
        border-radius: 14px;

        background: rgba(255,255,255,.68);

        margin-bottom: 10px;
    }

    .metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;

        color: #94a3b8;

        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .metric-top i {
        color: #a5b4fc;
    }

    .metric-value {
        margin-top: 6px;

        color: #1e293b;

        font-size: 22px;
        font-weight: 850;
        letter-spacing: -.04em;
    }

    .metric-value.primary {
        color: var(--terminal-primary);
    }

    .customer-preview {
        display: flex;
        align-items: center;
        gap: 10px;

        margin-top: 15px;
        padding: 12px;

        border-radius: 13px;

        background: #f8fafc;
        border: 1px solid #e8edf3;
    }

    .customer-avatar {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;

        display: grid;
        place-items: center;

        border-radius: 11px;

        background: rgba(99,91,255,.09);
        color: var(--terminal-primary);

        font-size: 12px;
    }

    .customer-preview-copy {
        min-width: 0;
    }

    .customer-preview-copy strong {
        display: block;

        color: #475569;
        font-size: 11px;
        font-weight: 800;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .customer-preview-copy span {
        display: block;

        margin-top: 2px;

        color: #94a3b8;
        font-size: 9px;
    }

    .intelligence-tip {
        display: flex;
        align-items: flex-start;
        gap: 10px;

        margin-top: 14px;
        padding: 12px;

        border-radius: 13px;

        background:
            linear-gradient(
                135deg,
                rgba(99,91,255,.065),
                rgba(14,165,233,.035)
            );

        border: 1px solid rgba(99,91,255,.1);

        color: #64748b;

        font-size: 10px;
        line-height: 1.55;
    }

    .intelligence-tip i {
        color: #8b5cf6;
        margin-top: 2px;
    }

    /* =====================================================
       ACTION BAR
    ===================================================== */

    .action-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;

        margin-top: 20px;
        padding: 15px 17px;

        border: 1px solid var(--terminal-border);
        border-radius: 18px;

        background: rgba(255,255,255,.82);

        box-shadow: 0 15px 40px rgba(15,23,42,.055);

        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .action-note {
        display: flex;
        align-items: center;
        gap: 9px;

        color: #8a96a8;
        font-size: 10px;
    }

    .action-note i {
        color: #22c55e;
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .action-btn {
        min-height: 42px;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;

        padding: 0 16px;

        border-radius: 11px;

        font-size: 11px;
        font-weight: 800;

        text-decoration: none;

        cursor: pointer;

        transition: .2s ease;
    }

    .action-btn.cancel {
        border: 1px solid #dfe5ed;
        background: #fff;
        color: #64748b;
    }

    .action-btn.cancel:hover {
        background: #f8fafc;
        color: #334155;
    }

    .action-btn.submit {
        border: 0;

        color: #fff;

        background:
            linear-gradient(
                135deg,
                var(--terminal-primary),
                #7c3aed
            );

        box-shadow:
            0 10px 25px rgba(99,91,255,.20);
    }

    .action-btn.submit:hover {
        transform: translateY(-1px);
        box-shadow:
            0 14px 30px rgba(99,91,255,.27);
    }

    .action-btn.submit:disabled {
        opacity: .6;
        cursor: not-allowed;
        transform: none;
    }

    /* =====================================================
       ALERTS
    ===================================================== */

    .terminal-alert {
        display: flex;
        align-items: flex-start;
        gap: 11px;

        margin-bottom: 15px;
        padding: 13px 15px;

        border-radius: 14px;

        font-size: 11px;
        line-height: 1.5;
    }

    .terminal-alert i {
        margin-top: 1px;
    }

    .terminal-alert.success {
        color: #166534;
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
    }

    .terminal-alert.danger {
        color: #991b1b;
        border: 1px solid #fecaca;
        background: #fef2f2;
    }

    /* =====================================================
       HIDDEN
    ===================================================== */

    .hidden {
        display: none !important;
    }

    /* =====================================================
       MOBILE
    ===================================================== */

    @media (max-width: 1100px) {

        .terminal-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .terminal-side {
            position: static;
        }

        .builder-grid {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }

        .builder-grid .builder-field:nth-child(4),
        .builder-grid .builder-field:nth-child(5) {
            grid-column: span 1;
        }

        .builder-grid .add-service-btn {
            grid-column: span 1;
        }
    }

    @media (max-width: 760px) {

        .job-terminal {
            padding-top: 10px;
        }

        .terminal-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 19px;
            border-radius: 18px;
        }

        .hero-status {
            align-self: flex-start;
        }

        .terminal-progress {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .progress-node {
            padding: 10px;
        }

        .progress-icon {
            width: 31px;
            height: 31px;
            flex-basis: 31px;
        }

        .progress-copy span {
            display: none;
        }

        .section-block {
            padding: 16px;
        }

        .field,
        .field.span-6,
        .field.span-8,
        .field.span-12 {
            grid-column: span 12;
        }

        .customer-mode {
            grid-template-columns: 1fr;
        }

        .builder-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .builder-grid .builder-field {
            grid-column: span 1 !important;
        }

        .builder-grid .builder-field:nth-child(2) {
            grid-column: span 2 !important;
        }

        .builder-grid .add-service-btn {
            grid-column: span 2 !important;
            width: 100%;
        }

        .payment-grid {
            grid-template-columns: 1fr;
        }

        .action-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .action-buttons {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1.35fr;
        }

        .action-btn {
            width: 100%;
        }

        .cart-summary {
            width: 100%;
        }
    }

    @media (max-width: 460px) {

        .terminal-progress {
            grid-template-columns: 1fr 1fr;
            gap: 7px;
        }

        .progress-node {
            min-height: 55px;
        }

        .hero-title {
            font-size: 23px;
        }

        .hero-description {
            font-size: 11px;
        }

        .section-header {
            margin-bottom: 13px;
        }

        .section-badge {
            display: none;
        }

        .builder-grid {
            grid-template-columns: 1fr;
        }

        .builder-grid .builder-field,
        .builder-grid .builder-field:nth-child(2),
        .builder-grid .add-service-btn {
            grid-column: span 1 !important;
        }

        .add-service-btn {
            width: 100%;
        }

        .action-buttons {
            grid-template-columns: 1fr;
        }
    }

    /* =====================================================
       REDUCED MOTION
    ===================================================== */

    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            scroll-behavior: auto !important;
            transition: none !important;
            animation: none !important;
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
    x-init="darkMode = false; localStorage.setItem('darkMode', JSON.stringify(false))"
    :class="{'dark bg-gray-900': darkMode === true}"
>

<div class="flex h-screen overflow-hidden">


<?php include('sidebar.php'); ?>

<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

    <main>

        <?php include('header.php'); ?>

        <div class="mx-auto max-w-(--breakpoint-2xl) p-3 md:p-6">

            <?php if (!empty($_SESSION['job_success'])): ?>

                <div class="terminal-alert success">
                    <i class="fa-solid fa-circle-check"></i>

                    <div>
                        <?= htmlspecialchars($_SESSION['job_success']); ?>
                    </div>
                </div>

                <?php unset($_SESSION['job_success']); ?>

            <?php endif; ?>


            <?php if (!empty($_SESSION['job_error'])): ?>

                <div class="terminal-alert danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <div>
                        <?= htmlspecialchars($_SESSION['job_error']); ?>
                    </div>
                </div>

                <?php unset($_SESSION['job_error']); ?>

            <?php endif; ?>


            <div class="job-terminal">

                <!-- =================================================
                     HERO
                ================================================== -->

                <section class="terminal-hero">

                    <div class="hero-left">

                        <div class="hero-kicker">
                            <i class="fa-solid fa-microchip"></i>
                            Service Operations Terminal
                        </div>

                        <h1 class="hero-title">
                            Register New Job
                        </h1>

                        <p class="hero-description">
                            Build a service order, assign the customer,
                            configure payment and submit the job directly
                            into the operational workflow.
                        </p>

                    </div>

                    <div class="hero-status">
                        <span class="status-dot"></span>
                        Terminal Ready
                    </div>

                </section>


                <!-- =================================================
                     PROCESS INDICATOR
                ================================================== -->

                <section class="terminal-progress">

                    <div class="progress-node active">
                        <div class="progress-icon">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div class="progress-copy">
                            <strong>Customer</strong>
                            <span>Identify customer</span>
                        </div>
                    </div>


                    <div class="progress-node active">
                        <div class="progress-icon">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>

                        <div class="progress-copy">
                            <strong>Services</strong>
                            <span>Build service cart</span>
                        </div>
                    </div>


                    <div class="progress-node active">
                        <div class="progress-icon">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>

                        <div class="progress-copy">
                            <strong>Payment</strong>
                            <span>Configure settlement</span>
                        </div>
                    </div>


                    <div class="progress-node active">
                        <div class="progress-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>

                        <div class="progress-copy">
                            <strong>Register</strong>
                            <span>Submit operational job</span>
                        </div>
                    </div>

                </section>


                <!-- =================================================
                     MAIN LAYOUT
                ================================================== -->

                <div class="terminal-layout">

                    <div class="terminal-main">

                        <form
                            id="jobForm"
                            method="POST"
                            action="create_job.php"
                            class="glass-card form-card"
                        >

                            <!-- =================================================
                                 CUSTOMER
                            ================================================== -->

                            <section class="section-block">

                                <div class="section-header">

                                    <div class="section-title-wrap">

                                        <div class="section-icon">
                                            <i class="fa-solid fa-users"></i>
                                        </div>

                                        <div>
                                            <h2 class="section-title">
                                                Customer Identity
                                            </h2>

                                            <p class="section-subtitle">
                                                Select an existing customer or create a new customer profile.
                                            </p>
                                        </div>

                                    </div>

                                    <span class="section-badge">
                                        STEP 01
                                    </span>

                                </div>


                                <div class="customer-mode">

                                    <div class="mode-option">

                                        <input
                                            type="radio"
                                            name="customer_type"
                                            value="new"
                                            checked
                                            id="newCustomerRadio"
                                        >

                                        <label
                                            for="newCustomerRadio"
                                            class="mode-label"
                                        >

                                            <span class="mode-icon">
                                                <i class="fa-solid fa-user-plus"></i>
                                            </span>

                                            <span class="mode-copy">
                                                <strong>New Customer</strong>
                                                <span>Create customer details for this job</span>
                                            </span>

                                        </label>

                                    </div>


                                    <div class="mode-option">

                                        <input
                                            type="radio"
                                            name="customer_type"
                                            value="existing"
                                            id="existingCustomerRadio"
                                        >

                                        <label
                                            for="existingCustomerRadio"
                                            class="mode-label"
                                        >

                                            <span class="mode-icon">
                                                <i class="fa-solid fa-user-check"></i>
                                            </span>

                                            <span class="mode-copy">
                                                <strong>Existing Customer</strong>
                                                <span>Use a customer already in the system</span>
                                            </span>

                                        </label>

                                    </div>

                                </div>


                                <!-- NEW CUSTOMER -->

                                <div
                                    id="newCustomerFields"
                                    style="margin-top:16px;"
                                >

                                    <div class="field-grid">

                                        <div class="field span-6">

                                            <label class="field-label">
                                                <i class="fa-solid fa-user"></i>
                                                Customer Name
                                            </label>

                                            <div class="input-shell">

                                                <i class="fa-regular fa-id-badge"></i>

                                                <input
                                                    type="text"
                                                    name="customer_name"
                                                    id="customer_name"
                                                    placeholder="e.g. John Doe"
                                                    class="terminal-input"
                                                    autocomplete="name"
                                                >

                                            </div>

                                        </div>


                                        <div class="field span-6">

                                            <label class="field-label">
                                                <i class="fa-solid fa-phone"></i>
                                                Customer Mobile
                                            </label>

                                            <div class="input-shell">

                                                <i class="fa-solid fa-mobile-screen-button"></i>

                                                <input
                                                    type="tel"
                                                    name="customer_phone"
                                                    id="customer_phone"
                                                    placeholder="e.g. 08012345678"
                                                    class="terminal-input"
                                                    autocomplete="tel"
                                                >

                                            </div>

                                        </div>


                                        <div class="field span-6">

                                            <label class="field-label">
                                                <i class="fa-solid fa-envelope"></i>
                                                Customer Email
                                            </label>

                                            <div class="input-shell">

                                                <i class="fa-regular fa-envelope"></i>

                                                <input
                                                    type="email"
                                                    name="customer_email_new"
                                                    id="customer_email_new"
                                                    placeholder="e.g. johndoe@example.com"
                                                    class="terminal-input"
                                                    autocomplete="email"
                                                >

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <!-- EXISTING CUSTOMER -->

                                <div
                                    id="existingCustomerFields"
                                    class="hidden"
                                    style="margin-top:16px;"
                                >

                                    <div class="field-grid">

                                        <div class="field span-12">

                                            <label class="field-label">
                                                <i class="fa-solid fa-address-book"></i>
                                                Select Existing Customer
                                            </label>

                                            <div class="select-shell">

                                                <select
                                                    id="existingCustomerSelect"
                                                    name="existing_customer_id"
                                                    class="terminal-select"
                                                >

                                                    <option value="">
                                                        -- Choose Customer --
                                                    </option>

                                                    <?php foreach ($customers as $cus): ?>

                                                        <option
                                                            value="<?= htmlspecialchars($cus['id']); ?>"
                                                            data-name="<?= htmlspecialchars($cus['fullname'] ?? ''); ?>"
                                                            data-phone="<?= htmlspecialchars($cus['phone'] ?? ''); ?>"
                                                            data-email="<?= htmlspecialchars($cus['email'] ?? ''); ?>"
                                                        >
                                                            <?= htmlspecialchars($cus['fullname'] ?? ''); ?>
                                                            — 
                                                            <?= htmlspecialchars($cus['phone'] ?? ''); ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </div>

                                        </div>


                                        <div class="field span-4">

                                            <label class="field-label">
                                                <i class="fa-solid fa-user"></i>
                                                Name
                                            </label>

                                            <input
                                                type="text"
                                                id="ex_name"
                                                readonly
                                                class="terminal-input"
                                            >

                                        </div>


                                        <div class="field span-4">

                                            <label class="field-label">
                                                <i class="fa-solid fa-phone"></i>
                                                Phone
                                            </label>

                                            <input
                                                type="text"
                                                id="ex_phone"
                                                readonly
                                                class="terminal-input"
                                            >

                                        </div>


                                        <div class="field span-4">

                                            <label class="field-label">
                                                <i class="fa-solid fa-envelope"></i>
                                                Email
                                            </label>

                                            <input
                                                type="email"
                                                id="ex_email"
                                                readonly
                                                class="terminal-input"
                                            >

                                            <input
                                                type="hidden"
                                                name="customer_email"
                                                id="hidden_customer_email"
                                            >

                                        </div>

                                    </div>

                                </div>

                            </section>


                            <!-- =================================================
                                 SERVICE BUILDER
                            ================================================== -->

                            <section class="section-block">

                                <div class="section-header">

                                    <div class="section-title-wrap">

                                        <div class="section-icon">
                                            <i class="fa-solid fa-layer-group"></i>
                                        </div>

                                        <div>
                                            <h2 class="section-title">
                                                Service Builder
                                            </h2>

                                            <p class="section-subtitle">
                                                Select a category, load the service price and add it to the job.
                                            </p>
                                        </div>

                                    </div>

                                    <span class="section-badge">
                                        STEP 02
                                    </span>

                                </div>


                                <div class="service-builder">
                                    <div class="builder-grid">
                                        <!-- CATEGORY -->
                                        <div class="builder-field">
                                            <label class="builder-label">
                                                Category
                                            </label>
                                            <div class="select-shell">
                                                <select id="categorySelect" name="category_id" class="terminal-select">
                                                    <option value="">
                                                        -- Select Category --
                                                    </option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?= htmlspecialchars($cat['id']); ?>">
                                                            <?= htmlspecialchars($cat['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- SERVICE -->
                                        <div class="builder-field">
                                            <label class="builder-label">
                                                Service
                                            </label>
                                            <div class="select-shell">
                                                <select id="serviceSelect" name="service_id" class="terminal-select">

                                                    <option value="">
                                                        -- Select Service --
                                                    </option>

                                                </select>

                                            </div>

                                        </div>


                                        <!-- PRICE -->

                                        <div class="builder-field price-field">

                                            <label class="builder-label">
                                                Service Charge
                                            </label>

                                            <input
                                                type="text"
                                                id="unitPrice"
                                                readonly
                                                placeholder="₦0.00"
                                                class="terminal-input"
                                            >

                                        </div>


                                        <!-- QTY -->

                                        <div class="builder-field">

                                            <label class="builder-label">
                                                Qty
                                            </label>

                                            <input
                                                type="number"
                                                id="qty"
                                                min="1"
                                                value="1"
                                                class="terminal-input"
                                            >

                                        </div>


                                        <!-- TOTAL -->

                                        <div class="builder-field line-total-field">

                                            <label class="builder-label">
                                                Line Total
                                            </label>

                                            <input
                                                type="text"
                                                id="lineTotal"
                                                readonly
                                                placeholder="₦0.00"
                                                class="terminal-input"
                                            >

                                        </div>


                                        <!-- ADD -->
                                         <div class="builder-field line-total-field">

                                        <button
                                            type="button"
                                            id="addToCartBtn"
                                            class="add-service-btn"
                                            style="margin-right: 40px;"
                                        >
                                            <i class="fa-solid fa-cart-plus"></i>
                                            Add Service
                                        </button>

                                                    </div>

                                    </div>

                                </div>


                                <!-- CART -->

                                <div class="cart-wrapper">

                                    <div class="cart-header">

                                        <h3 class="cart-title">
                                            <i class="fa-solid fa-list-check"></i>
                                            Job Services
                                        </h3>

                                        <span
                                            class="cart-count"
                                            id="cartCount"
                                        >
                                            0
                                        </span>

                                    </div>


                                    <div class="cart-scroll">

                                        <table id="cartTable">

                                            <thead>

                                                <tr>
                                                    <th>Service No.</th>
                                                    <th style="text-align:left;">Service</th>
                                                    <th>Qty</th>
                                                    <th>Unit</th>
                                                    <th>Total</th>
                                                    <th></th>
                                                </tr>

                                            </thead>

                                            <tbody>

                                                <tr class="cart-empty-row">

                                                    <td
                                                        colspan="6"
                                                        class="empty-cart"
                                                    >

                                                        <i class="fa-solid fa-cart-shopping"></i>

                                                        <strong>
                                                            No services added yet
                                                        </strong>

                                                        <span>
                                                            Select a service above to begin building this job.
                                                        </span>

                                                    </td>

                                                </tr>

                                            </tbody>

                                        </table>

                                    </div>


                                    <!-- SUMMARY -->

                                    <div class="cart-summary">

                                        <div class="summary-row">

                                            <span>
                                                Subtotal
                                            </span>

                                            <strong id="grandTotal">
                                                0.00
                                            </strong>

                                        </div>


                                        <div class="summary-row">

                                            <span>
                                                Discount
                                            </span>

                                            <input
                                                type="number"
                                                id="discount"
                                                name="discount"
                                                min="0"
                                                max="100"
                                                value="0"
                                                class="discount-inline"
                                                aria-label="Discount percentage"
                                            >

                                        </div>


                                        <div class="summary-row">

                                            <span>
                                                Discount Amount
                                            </span>

                                            <strong id="discountAmount">
                                                0.00
                                            </strong>

                                        </div>


                                        <div class="summary-row total-row">

                                            <span>
                                                Final Total
                                            </span>

                                            <strong id="finalTotal">
                                                0.00
                                            </strong>

                                        </div>

                                    </div>

                                </div>

                            </section>


                            <!-- =================================================
                                 PAYMENT
                            ================================================== -->

                            <section class="section-block">

                                <div class="section-header">

                                    <div class="section-title-wrap">

                                        <div class="section-icon">
                                            <i class="fa-solid fa-wallet"></i>
                                        </div>

                                        <div>
                                            <h2 class="section-title">
                                                Payment Configuration
                                            </h2>

                                            <p class="section-subtitle">
                                                Define how this job will be settled.
                                            </p>
                                        </div>

                                    </div>

                                    <span class="section-badge">
                                        STEP 03
                                    </span>

                                </div>


                                <div class="payment-grid">

                                    <div>

                                        <label class="field-label">
                                            <i class="fa-solid fa-money-check-dollar"></i>
                                            Payment Status
                                        </label>

                                        <div class="select-shell">

                                            <select
                                                id="payment_status"
                                                name="payment_status"
                                                class="terminal-select"
                                            >

                                                <option value="">
                                                    -- Select Payment Status --
                                                </option>

                                                <option value="full">
                                                    Full Payment
                                                </option>

                                                <option value="partial">
                                                    Part Payment
                                                </option>

                                            </select>

                                        </div>

                                    </div>


                                    <div>

                                        <label class="field-label">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                            Amount Paid
                                        </label>

                                        <input
                                            type="number"
                                            id="amount_paid"
                                            name="amount_paid"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="terminal-input"
                                        >

                                    </div>


                                    <div>

                                        <label class="field-label">
                                            <i class="fa-solid fa-scale-balanced"></i>
                                            Balance
                                        </label>

                                        <input
                                            type="text"
                                            id="balance"
                                            name="balance"
                                            readonly
                                            value="0.00"
                                            class="terminal-input"
                                        >

                                    </div>

                                </div>


                                <div class="payment-insight">

                                    <i class="fa-solid fa-circle-info"></i>

                                    <span id="paymentInsightText">
                                        Select a payment status after adding services.
                                        The system will calculate the settlement automatically.
                                    </span>

                                </div>


                                <!-- PART PAYMENT -->

                                <div
                                    id="partialPaymentBox"
                                    style="display:none;"
                                >

                                    <div class="field-grid">

                                        <div class="field span-6">

                                            <label class="field-label">
                                                <i class="fa-regular fa-calendar"></i>
                                                Due Date
                                            </label>

                                            <input
                                                type="date"
                                                id="due_date"
                                                name="due_date"
                                                class="terminal-input"
                                            >

                                        </div>

                                    </div>

                                </div>

                            </section>


                            <!-- =================================================
                                 NOTE
                            ================================================== -->

                            <section class="section-block">

                                <div class="section-header">

                                    <div class="section-title-wrap">

                                        <div class="section-icon">
                                            <i class="fa-solid fa-note-sticky"></i>
                                        </div>

                                        <div>
                                            <h2 class="section-title">
                                                Job Intelligence Note
                                            </h2>

                                            <p class="section-subtitle">
                                                Add any useful operational information for this job.
                                            </p>
                                        </div>

                                    </div>

                                </div>


                                <textarea
                                    name="job_note"
                                    id="job_note"
                                    rows="3"
                                    placeholder="Enter any notes for this job..."
                                    class="terminal-textarea"
                                ></textarea>

                            </section>


                            <!-- =================================================
                                 HIDDEN PAYLOAD
                            ================================================== -->

                            <input
                                type="hidden"
                                name="location_id"
                                value="<?= htmlspecialchars((string)$location_id); ?>"
                            >

                            <input
                                type="hidden"
                                name="user_id"
                                value="<?= htmlspecialchars((string)$user_id); ?>"
                            >

                            <input
                                type="hidden"
                                name="company_id"
                                value="<?= htmlspecialchars((string)$company_id); ?>"
                            >

                            <input
                                type="hidden"
                                name="job_payload"
                                id="job_payload"
                            >

                        </form>


                        <!-- =================================================
                             ACTION BAR
                        ================================================== -->

                        <div class="action-bar">

                            <div class="action-note">

                                <i class="fa-solid fa-shield-check"></i>

                                <span>
                                    Job data will be securely submitted to the operations system.
                                </span>

                            </div>


                            <div class="action-buttons">

                                <a
                                    href="register-job.php"
                                    class="action-btn cancel"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                    Cancel
                                </a>

                                <button
                                    type="submit"
                                    form="jobForm"
                                    id="submitJobBtn"
                                    class="action-btn submit"
                                >
                                    <i class="fa-solid fa-circle-check"></i>
                                    Register Job
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         INTELLIGENCE PANEL
                    ================================================== -->

                    <aside class="terminal-side">

                        <div class="glass-card intelligence-card">

                            <div class="intelligence-head">

                                <div class="intelligence-kicker">
                                    <i class="fa-solid fa-brain"></i>
                                    Live Intelligence
                                </div>

                                <h2 class="intelligence-title">
                                    Job Overview
                                </h2>

                            </div>


                            <div class="intelligence-body">

                                <!-- SERVICES -->

                                <div class="metric-box">

                                    <div class="metric-top">

                                        <span>
                                            Services
                                        </span>

                                        <i class="fa-solid fa-layer-group"></i>

                                    </div>

                                    <div
                                        class="metric-value"
                                        id="sideServiceCount"
                                    >
                                        0
                                    </div>

                                </div>


                                <!-- SUBTOTAL -->

                                <div class="metric-box">

                                    <div class="metric-top">

                                        <span>
                                            Job Value
                                        </span>

                                        <i class="fa-solid fa-chart-line"></i>

                                    </div>

                                    <div
                                        class="metric-value"
                                        id="sideSubtotal"
                                    >
                                        ₦0.00
                                    </div>

                                </div>


                                <!-- FINAL -->

                                <div class="metric-box">

                                    <div class="metric-top">

                                        <span>
                                            Net Payable
                                        </span>

                                        <i class="fa-solid fa-coins"></i>

                                    </div>

                                    <div
                                        class="metric-value primary"
                                        id="sideFinalTotal"
                                    >
                                        ₦0.00
                                    </div>

                                </div>


                                <!-- CUSTOMER -->

                                <div
                                    class="customer-preview"
                                    id="customerPreview"
                                >

                                    <div class="customer-avatar">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                    <div class="customer-preview-copy">

                                        <strong id="previewCustomerName">
                                            No customer selected
                                        </strong>

                                        <span id="previewCustomerMeta">
                                            Customer identity pending
                                        </span>

                                    </div>

                                </div>


                                <!-- PAYMENT -->

                                <div class="customer-preview">

                                    <div class="customer-avatar">
                                        <i class="fa-solid fa-credit-card"></i>
                                    </div>

                                    <div class="customer-preview-copy">

                                        <strong id="previewPaymentStatus">
                                            Payment pending
                                        </strong>

                                        <span id="previewBalance">
                                            Balance: ₦0.00
                                        </span>

                                    </div>

                                </div>


                                <!-- TIP -->

                                <div class="intelligence-tip">

                                    <i class="fa-solid fa-wand-magic-sparkles"></i>

                                    <span id="intelligenceTip">
                                        Add your first service to activate
                                        live job calculations.
                                    </span>

                                </div>

                            </div>

                        </div>

                    </aside>

                </div>

            </div>

        </div>

    </main>

</div>


</div>

<?php include('footer.php'); ?>

<script defer src="bundle.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
       ELEMENTS
    ========================================================= */

    const categorySelect       = document.getElementById('categorySelect');
    const serviceSelect        = document.getElementById('serviceSelect');
    const unitPriceInput       = document.getElementById('unitPrice');
    const qtyInput             = document.getElementById('qty');
    const lineTotalInput       = document.getElementById('lineTotal');
    const addToCartBtn         = document.getElementById('addToCartBtn');

    const cartTableBody        = document.querySelector('#cartTable tbody');
    const cartPayloadInput     = document.getElementById('job_payload');

    const grandTotalEl         = document.getElementById('grandTotal');
    const discountInput        = document.getElementById('discount');
    const discountAmountEl     = document.getElementById('discountAmount');
    const finalTotalEl         = document.getElementById('finalTotal');
    const cartCountEl          = document.getElementById('cartCount');

    const newRadio             = document.getElementById('newCustomerRadio');
    const existingRadio        = document.getElementById('existingCustomerRadio');

    const newFields            = document.getElementById('newCustomerFields');
    const exFields             = document.getElementById('existingCustomerFields');

    const existingSelect       = document.getElementById('existingCustomerSelect');
    const exName               = document.getElementById('ex_name');
    const exPhone              = document.getElementById('ex_phone');
    const exEmail              = document.getElementById('ex_email');
    const hiddenEmail          = document.getElementById('hidden_customer_email');

    const customerName         = document.getElementById('customer_name');
    const customerPhone        = document.getElementById('customer_phone');
    const customerEmailNew     = document.getElementById('customer_email_new');

    const paymentStatus        = document.getElementById('payment_status');
    const partialPaymentBox    = document.getElementById('partialPaymentBox');
    const amountPaid           = document.getElementById('amount_paid');
    const balanceInput         = document.getElementById('balance');
    const dueDate              = document.getElementById('due_date');

    const jobForm              = document.getElementById('jobForm');
    const submitJobBtn         = document.getElementById('submitJobBtn');

    const paymentInsightText   = document.getElementById('paymentInsightText');

    const sideServiceCount     = document.getElementById('sideServiceCount');
    const sideSubtotal         = document.getElementById('sideSubtotal');
    const sideFinalTotal       = document.getElementById('sideFinalTotal');

    const previewCustomerName  = document.getElementById('previewCustomerName');
    const previewCustomerMeta  = document.getElementById('previewCustomerMeta');

    const previewPaymentStatus = document.getElementById('previewPaymentStatus');
    const previewBalance       = document.getElementById('previewBalance');

    const intelligenceTip      = document.getElementById('intelligenceTip');


    /* =========================================================
       STATE
    ========================================================= */

    let cart = [];

    let availableStock = 0;


    /* =========================================================
       HELPERS
    ========================================================= */

    function numberValue(value) {

        const parsed = parseFloat(value);

        return Number.isFinite(parsed) ? parsed : 0;

    }


    function formatNumber(value) {

        return numberValue(value).toFixed(2);

    }


    function formatCurrency(value) {

        return '₦' + numberValue(value).toLocaleString(
            'en-NG',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    }


    function escapeHtml(value) {

        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }


    /* =========================================================
       CUSTOMER MODE
    ========================================================= */

    newRadio.addEventListener('change', function () {

        newFields.classList.remove('hidden');
        exFields.classList.add('hidden');

        existingSelect.value = '';

        exName.value = '';
        exPhone.value = '';
        exEmail.value = '';

        hiddenEmail.value = '';

        updateCustomerPreview();

    });


    existingRadio.addEventListener('change', function () {

        newFields.classList.add('hidden');
        exFields.classList.remove('hidden');

        customerName.value = '';
        customerPhone.value = '';
        customerEmailNew.value = '';

        updateCustomerPreview();

    });


    existingSelect.addEventListener('change', function () {

        const selected =
            this.options[this.selectedIndex];

        const name =
            selected?.getAttribute('data-name') || '';

        const phone =
            selected?.getAttribute('data-phone') || '';

        const email =
            selected?.getAttribute('data-email') || '';

        exName.value = name;
        exPhone.value = phone;
        exEmail.value = email;

        hiddenEmail.value = email;

        updateCustomerPreview();

    });


    customerName.addEventListener(
        'input',
        updateCustomerPreview
    );

    customerPhone.addEventListener(
        'input',
        updateCustomerPreview
    );


    /* =========================================================
       CUSTOMER PREVIEW
    ========================================================= */

    function updateCustomerPreview() {

        let name = '';
        let meta = '';

        if (newRadio.checked) {

            name = customerName.value.trim();

            if (customerPhone.value.trim()) {
                meta = customerPhone.value.trim();
            }

            if (!name) {
                name = 'New customer';
                meta = 'Customer identity pending';
            }

        } else {

            name = exName.value.trim();

            if (exPhone.value.trim()) {
                meta = exPhone.value.trim();
            }

            if (!name) {
                name = 'No customer selected';
                meta = 'Select an existing customer';
            }

        }

        previewCustomerName.textContent = name;
        previewCustomerMeta.textContent = meta;

    }


    /* =========================================================
       CATEGORY → SERVICE
    ========================================================= */

    categorySelect.addEventListener(
        'change',
        function () {

            const catId = this.value;

            serviceSelect.innerHTML =
                '<option value="">-- Select Service --</option>';

            resetServiceInputs();

            if (!catId) {
                return;
            }

            serviceSelect.innerHTML =
                '<option value="">Loading services...</option>';

            serviceSelect.disabled = true;

            fetch(
                `get_service.php?category_id=${encodeURIComponent(catId)}`
            )
            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        'Unable to load services'
                    );
                }

                return response.json();

            })
            .then(data => {

                serviceSelect.innerHTML =
                    '<option value="">-- Select Service --</option>';

                if (!Array.isArray(data) || data.length === 0) {

                    serviceSelect.innerHTML =
                        '<option value="">No services available</option>';

                    return;
                }

                data.forEach(serv => {

                    const option =
                        document.createElement('option');

                    option.value = serv.id;

                    option.textContent =
                        serv.service_name;

                    option.dataset.serviceNumber =
                        serv.service_number || '';

                    serviceSelect.appendChild(option);

                });

            })
            .catch(error => {

                console.error(error);

                serviceSelect.innerHTML =
                    '<option value="">Unable to load services</option>';

            })
            .finally(() => {

                serviceSelect.disabled = false;

            });

        }
    );


    /* =========================================================
       SERVICE → PRICE
    ========================================================= */

    serviceSelect.addEventListener(
        'change',
        function () {

            const serviceId = this.value;

            if (!serviceId) {

                resetServiceInputs();

                return;
            }

            unitPriceInput.value = 'Loading...';

            fetch(
                `get_service_price.php?service_id=${encodeURIComponent(serviceId)}`
            )
            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        'Unable to load service price'
                    );
                }

                return response.json();

            })
            .then(data => {

                const price =
                    numberValue(data.total_cost);

                availableStock =
                    numberValue(data.quantity);

                unitPriceInput.value =
                    formatNumber(price);

                updateLineTotal();

            })
            .catch(error => {

                console.error(error);

                unitPriceInput.value = '';

                availableStock = 0;

                alert(
                    'Unable to load service price.'
                );

            });

        }
    );


    /* =========================================================
       LINE TOTAL
    ========================================================= */

    function updateLineTotal() {

        let qty =
            numberValue(qtyInput.value);

        const price =
            numberValue(unitPriceInput.value);

        if (qty < 1) {

            qty = 1;

            qtyInput.value = 1;

        }


        /*
         * Preserve the original stock validation.
         */

        if (
            availableStock > 0 &&
            qty > availableStock
        ) {

            qty = availableStock;

            qtyInput.value =
                availableStock;

        }


        const total =
            qty * price;

        lineTotalInput.value =
            formatNumber(total);

    }


    qtyInput.addEventListener(
        'input',
        updateLineTotal
    );


    qtyInput.addEventListener(
        'change',
        updateLineTotal
    );


    /* =========================================================
       ADD TO CART
    ========================================================= */

    addToCartBtn.addEventListener(
        'click',
        function () {

            const serviceId =
                serviceSelect.value;

            const selectedOption =
                serviceSelect.options[
                    serviceSelect.selectedIndex
                ];

            const serviceName =
                selectedOption?.text || '';

            const serviceNumber =
                selectedOption?.dataset.serviceNumber || '';

            const qty =
                numberValue(qtyInput.value);

            const unitPrice =
                numberValue(unitPriceInput.value);


            if (!serviceId) {

                alert(
                    'Please select a service.'
                );

                serviceSelect.focus();

                return;
            }


            if (unitPrice <= 0) {

                alert(
                    'This service does not have a valid charge.'
                );

                return;
            }


            if (qty <= 0) {

                alert(
                    'Please enter a valid quantity.'
                );

                qtyInput.focus();

                return;
            }


            const existing =
                cart.find(
                    item =>
                        item.service_id == serviceId
                );


            const existingQty =
                existing
                    ? numberValue(existing.quantity)
                    : 0;


            const requestedQty =
                existingQty + qty;


            if (
                availableStock > 0 &&
                requestedQty > availableStock
            ) {

                alert(
                    `Only ${availableStock} unit(s) available in stock for this service.`
                );

                return;
            }


            if (existing) {

                existing.quantity =
                    requestedQty;

                existing.total_price =
                    existing.quantity *
                    existing.unit_price;

            } else {

                cart.push({

                    service_id: serviceId,

                    service_number:
                        serviceNumber,

                    name:
                        serviceName,

                    quantity:
                        qty,

                    unit_price:
                        unitPrice,

                    total_price:
                        qty * unitPrice

                });

            }


            renderCart();


            serviceSelect.value = '';

            unitPriceInput.value = '';

            qtyInput.value = 1;

            lineTotalInput.value = '';

            availableStock = 0;

        }
    );


    /* =========================================================
       RENDER CART
    ========================================================= */

    function renderCart() {

        cartTableBody.innerHTML = '';

        let grand = 0;


        if (cart.length === 0) {

            cartTableBody.innerHTML = `

                <tr class="cart-empty-row">

                    <td
                        colspan="6"
                        class="empty-cart"
                    >

                        <i class="fa-solid fa-cart-shopping"></i>

                        <strong>
                            No services added yet
                        </strong>

                        <span>
                            Select a service above to begin building this job.
                        </span>

                    </td>

                </tr>

            `;

        }


        cart.forEach(
            (item, index) => {

                grand +=
                    numberValue(
                        item.total_price
                    );


                const tr =
                    document.createElement('tr');


                tr.innerHTML = `

                    <td>
                        ${escapeHtml(item.service_number)}
                    </td>

                    <td>
                        <strong style="color:#334155;">
                            ${escapeHtml(item.name)}
                        </strong>
                    </td>

                    <td>
                        ${numberValue(item.quantity)}
                    </td>

                    <td>
                        ${formatCurrency(item.unit_price)}
                    </td>

                    <td>
                        <strong style="color:#334155;">
                            ${formatCurrency(item.total_price)}
                        </strong>
                    </td>

                    <td>

                        <button
                            type="button"
                            data-idx="${index}"
                            class="btn-remove"
                            aria-label="Remove service"
                            title="Remove service"
                        >

                            <i class="fa-solid fa-trash-can"></i>

                        </button>

                    </td>

                `;

                cartTableBody.appendChild(tr);

            }
        );


        const discountPercent =
            Math.min(
                Math.max(
                    numberValue(
                        discountInput.value
                    ),
                    0
                ),
                100
            );


        discountInput.value =
            discountPercent;


        const discountAmount =
            (
                grand *
                discountPercent
            ) / 100;


        const finalTotal =
            Math.max(
                grand - discountAmount,
                0
            );


        grandTotalEl.textContent =
            formatCurrency(grand);


        discountAmountEl.textContent =
            formatCurrency(discountAmount);


        finalTotalEl.textContent =
            formatCurrency(finalTotal);


        cartCountEl.textContent =
            cart.length;


        /*
         * Preserve backend payload structure.
         */

        cartPayloadInput.value =
            JSON.stringify({

                items: cart,

                grand_total: grand,

                discount_percent:
                    discountPercent,

                discount_amount:
                    discountAmount,

                final_total:
                    finalTotal

            });


        /*
         * Remove buttons
         */

        document
            .querySelectorAll('.btn-remove')
            .forEach(button => {

                button.addEventListener(
                    'click',
                    function () {

                        const index =
                            parseInt(
                                this.dataset.idx,
                                10
                            );

                        if (
                            Number.isInteger(index)
                        ) {

                            cart.splice(
                                index,
                                1
                            );

                            renderCart();

                        }

                    }
                );

            });


        updatePayment();

        updateIntelligence();

    }


    /* =========================================================
       DISCOUNT
    ========================================================= */

    discountInput.addEventListener(
        'input',
        function () {

            let value =
                numberValue(this.value);

            if (value > 100) {
                value = 100;
            }

            if (value < 0) {
                value = 0;
            }

            this.value = value;

            renderCart();

        }
    );


    /* =========================================================
       RESET SERVICE INPUTS
    ========================================================= */

    function resetServiceInputs() {

        unitPriceInput.value = '';

        qtyInput.value = 1;

        lineTotalInput.value = '';

        availableStock = 0;

    }


    /* =========================================================
       TOTAL VALUE
    ========================================================= */

    function getFinalTotal() {

        return numberValue(
            finalTotalEl.textContent
                .replace(/[₦,]/g, '')
        );

    }


    /* =========================================================
       PAYMENT
    ========================================================= */

    paymentStatus.addEventListener(
        'change',
        updatePayment
    );


    amountPaid.addEventListener(
        'input',
        updatePayment
    );


    function updatePayment() {

        const total =
            getFinalTotal();


        if (paymentStatus.value === 'partial') {

            partialPaymentBox.style.display =
                'block';


            let paid =
                numberValue(
                    amountPaid.value
                );


            if (paid > total) {

                paid = total;

                amountPaid.value =
                    total.toFixed(2);

            }


            const balance =
                Math.max(
                    total - paid,
                    0
                );


            balanceInput.value =
                balance.toFixed(2);


            paymentInsightText.textContent =
                `Part payment selected. ₦${balance.toLocaleString(
                    'en-NG',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                )} remains outstanding.`;


        } else if (
            paymentStatus.value === 'full'
        ) {

            partialPaymentBox.style.display =
                'none';


            amountPaid.value =
                total.toFixed(2);

            balanceInput.value =
                '0.00';


            paymentInsightText.textContent =
                'Full payment selected. The customer will be settled against the complete job value.';


        } else {

            partialPaymentBox.style.display =
                'none';


            amountPaid.value = '';

            balanceInput.value =
                total.toFixed(2);


            paymentInsightText.textContent =
                'Select a payment status after adding services. The system will calculate the settlement automatically.';

        }


        updateIntelligence();

    }


    /* =========================================================
       INTELLIGENCE PANEL
    ========================================================= */

    function updateIntelligence() {

        const subtotal =
            numberValue(
                grandTotalEl.textContent
                    .replace(/[₦,]/g, '')
            );


        const finalTotal =
            getFinalTotal();


        const balance =
            numberValue(
                balanceInput.value
            );


        sideServiceCount.textContent =
            cart.length;


        sideSubtotal.textContent =
            formatCurrency(subtotal);


        sideFinalTotal.textContent =
            formatCurrency(finalTotal);


        previewPaymentStatus.textContent =
            paymentStatus.value === 'full'
                ? 'Full payment'
                : paymentStatus.value === 'partial'
                    ? 'Part payment'
                    : 'Payment pending';


        previewBalance.textContent =
            'Balance: ' +
            formatCurrency(
                paymentStatus.value === 'full'
                    ? 0
                    : balance
            );


        if (cart.length === 0) {

            intelligenceTip.textContent =
                'Add your first service to activate live job calculations.';

        } else if (!paymentStatus.value) {

            intelligenceTip.textContent =
                'Services are ready. Configure the payment status before submitting the job.';

        } else if (
            paymentStatus.value === 'partial' &&
            balance > 0
        ) {

            intelligenceTip.textContent =
                'Part payment is active. Make sure a valid due date is supplied before registration.';

        } else {

            intelligenceTip.textContent =
                'Job configuration looks complete. Review the customer, services and payment details before registering.';

        }

    }


    /* =========================================================
       FORM SUBMISSION VALIDATION
    ========================================================= */

    jobForm.addEventListener(
        'submit',
        function (event) {

            /*
             * CART
             */

            if (cart.length === 0) {

                event.preventDefault();

                alert(
                    'Add at least one service before registering the job.'
                );

                return;
            }


            /*
             * PAYMENT STATUS
             */

            if (!paymentStatus.value) {

                event.preventDefault();

                alert(
                    'Please select a payment status.'
                );

                paymentStatus.focus();

                return;
            }


            /*
             * NEW CUSTOMER
             */

            if (newRadio.checked) {

                const name =
                    customerName.value.trim();


                if (!name) {

                    event.preventDefault();

                    alert(
                        'Please enter the customer name.'
                    );

                    customerName.focus();

                    return;
                }

            }


            /*
             * EXISTING CUSTOMER
             */

            if (
                existingRadio.checked &&
                !existingSelect.value
            ) {

                event.preventDefault();

                alert(
                    'Please select an existing customer.'
                );

                existingSelect.focus();

                return;
            }


            /*
             * PART PAYMENT
             */

            if (
                paymentStatus.value === 'partial'
            ) {

                const paid =
                    numberValue(
                        amountPaid.value
                    );

                const finalTotal =
                    getFinalTotal();

                const selectedDueDate =
                    dueDate.value;


                if (finalTotal <= 0) {

                    event.preventDefault();

                    alert(
                        'The final job total must be greater than zero.'
                    );

                    return;
                }


                if (paid <= 0) {

                    event.preventDefault();

                    alert(
                        'Please enter the amount paid.'
                    );

                    amountPaid.focus();

                    return;
                }


                if (paid >= finalTotal) {

                    event.preventDefault();

                    alert(
                        'Amount paid must be less than the final total for part payment.'
                    );

                    amountPaid.focus();

                    return;
                }


                if (!selectedDueDate) {

                    event.preventDefault();

                    alert(
                        'Please select a due date for the outstanding balance.'
                    );

                    dueDate.focus();

                    return;
                }

            }


            /*
             * PREVENT DOUBLE SUBMISSION
             */

            submitJobBtn.disabled = true;

            submitJobBtn.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Registering Job...
            `;

        }
    );


    /* =========================================================
       INITIAL STATE
    ========================================================= */

    renderCart();

    updateCustomerPreview();

    updatePayment();

});

</script>

</body>

</html>
