
<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$company_id = $_SESSION['company_id'] ?? null;

function unitEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$units = [];

if ($company_id !== null && $company_id !== '') {
    $stmt = $pdo->prepare("
        SELECT id, unit_name, unit_type, created_by, created_at
        FROM units
        WHERE company_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$company_id]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalUnits = count($units);
$unitTypes = [];

foreach ($units as $unit) {
    $type = trim((string)($unit['unit_type'] ?? ''));
    if ($type !== '') {
        $unitTypes[$type] = true;
    }
}
$totalTypes = count($unitTypes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Units Management | Oricore Industrial Exchange</title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="main.css">

    <style>
        :root {
            --unit-primary: #315efb;
            --unit-primary-dark: #2448d8;
            --unit-primary-soft: #edf2ff;
            --unit-text: #172033;
            --unit-muted: #718096;
            --unit-border: #e7ebf2;
            --unit-surface: #ffffff;
            --unit-background: #f5f7fb;
            --unit-green: #16875d;
            --unit-shadow: 0 12px 35px rgba(26, 42, 75, .055);
        }

        .units-page {
            min-height: 100%;
            padding: 24px;
            background: var(--unit-background);
            color: var(--unit-text);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI",
                         Roboto, Arial, sans-serif;
        }

        .units-page * {
            box-sizing: border-box;
        }

        .units-shell {
            max-width: 1600px;
            margin: 0 auto;
        }

        .units-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .units-heading-copy {
            min-width: 0;
        }

        .units-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: var(--unit-primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.7px;
            text-transform: uppercase;
        }

        .units-eyebrow-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--unit-primary);
            box-shadow: 0 0 0 4px rgba(49, 94, 251, .10);
        }

        .units-title {
            margin: 0;
            color: var(--unit-text);
            font-size: clamp(25px, 3vw, 34px);
            font-weight: 800;
            letter-spacing: -1.1px;
            line-height: 1.2;
        }

        .units-subtitle {
            max-width: 650px;
            margin: 10px 0 0;
            color: var(--unit-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .units-heading-icon {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border: 1px solid #dfe7ff;
            border-radius: 18px;
            background: linear-gradient(145deg, #ffffff, #edf2ff);
            color: var(--unit-primary);
            box-shadow: 0 8px 24px rgba(49, 94, 251, .09);
            font-size: 23px;
        }

        .units-heading-actions {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .unit-register-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 46px;
            padding: 0 17px;
            border: 1px solid var(--unit-primary);
            border-radius: 12px;
            background: linear-gradient(135deg, var(--unit-primary), var(--unit-primary-dark));
            color: #fff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 750;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 8px 18px rgba(49, 94, 251, .18);
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .unit-register-button:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 11px 24px rgba(49, 94, 251, .25);
        }

        .unit-register-button:focus-visible {
            outline: 3px solid rgba(49, 94, 251, .25);
            outline-offset: 3px;
        }

        .units-overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .units-stat {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
            padding: 20px;
            overflow: hidden;
            border: 1px solid var(--unit-border);
            border-radius: 17px;
            background: var(--unit-surface);
            box-shadow: var(--unit-shadow);
        }

        .units-stat::after {
            position: absolute;
            right: -20px;
            bottom: -35px;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background: rgba(49, 94, 251, .035);
            content: "";
            pointer-events: none;
        }

        .units-stat-icon {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 47px;
            height: 47px;
            border-radius: 14px;
            background: var(--unit-primary-soft);
            color: var(--unit-primary);
            font-size: 19px;
        }

        .units-stat-icon.green {
            background: #e9f8f0;
            color: #16875d;
        }

        .units-stat-icon.amber {
            background: #fff5df;
            color: #b77912;
        }

        .units-stat-label {
            margin-bottom: 6px;
            color: var(--unit-muted);
            font-size: 12px;
            font-weight: 600;
        }

        .units-stat-value {
            color: var(--unit-text);
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.6px;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .units-panel {
            overflow: hidden;
            border: 1px solid var(--unit-border);
            border-radius: 19px;
            background: var(--unit-surface);
            box-shadow: var(--unit-shadow);
        }

        .units-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 22px 24px;
            border-bottom: 1px solid var(--unit-border);
        }

        .units-panel-title {
            margin: 0;
            color: var(--unit-text);
            font-size: 17px;
            font-weight: 800;
        }

        .units-panel-description {
            margin: 6px 0 0;
            color: var(--unit-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .units-record-count {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border: 1px solid #e1e8ff;
            border-radius: 10px;
            background: #f4f6ff;
            color: var(--unit-primary);
            font-size: 12px;
            font-weight: 800;
        }

        .units-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 18px 24px;
            border-bottom: 1px solid var(--unit-border);
        }

        .units-search {
            position: relative;
            width: min(100%, 430px);
        }

        .units-search > i {
            position: absolute;
            top: 50%;
            left: 15px;
            color: #8b96aa;
            font-size: 14px;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .units-search input {
            width: 100%;
            height: 45px;
            padding: 0 42px 0 42px;
            border: 1px solid #e0e6ef;
            border-radius: 12px;
            outline: none;
            background: #fbfcfe;
            color: var(--unit-text);
            font-family: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .units-search input:focus {
            border-color: var(--unit-primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(49, 94, 251, .09);
        }

        .units-search input::placeholder {
            color: #9aa4b5;
        }

        .units-search-clear {
            position: absolute;
            top: 50%;
            right: 9px;
            display: none;
            align-items: center;
            justify-content: center;
            width: 27px;
            height: 27px;
            border: 0;
            border-radius: 8px;
            background: #eef1f6;
            color: #647084;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .units-search-clear.visible {
            display: flex;
        }

        .units-results-label {
            color: var(--unit-muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .units-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .units-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .units-table thead {
            background: #f8faff;
        }

        .units-table th {
            padding: 15px 24px;
            border-bottom: 1px solid var(--unit-border);
            color: #7b879b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .units-table td {
            padding: 17px 24px;
            border-bottom: 1px solid #edf0f5;
            color: #435069;
            font-size: 13px;
            vertical-align: middle;
        }

        .units-table tbody tr {
            transition: background .18s ease;
        }

        .units-table tbody tr:hover {
            background: #fafbff;
        }

        .units-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .units-id {
            color: #8994a8;
            font-size: 12px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .units-name-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 190px;
        }

        .units-name-icon {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 39px;
            height: 39px;
            border: 1px solid #e4eaff;
            border-radius: 12px;
            background: #f1f4ff;
            color: var(--unit-primary);
            font-size: 15px;
        }

        .units-name {
            color: #1c2940;
            font-size: 13px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .units-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            max-width: 220px;
            padding: 7px 10px;
            border: 1px solid #e7ebf2;
            border-radius: 9px;
            background: #f8f9fc;
            color: #58667c;
            font-size: 11px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .units-type-badge i {
            color: #8391a8;
        }

        .units-creator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #59667b;
            font-size: 12px;
        }

        .units-creator-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            border-radius: 9px;
            background: #eef2f8;
            color: #64738b;
            font-size: 11px;
        }

        .units-date {
            color: #68758b;
            font-size: 12px;
            white-space: nowrap;
        }

        .units-date i {
            margin-right: 7px;
            color: #9aa5b7;
        }

        .units-mobile-list {
            display: none;
            padding: 14px;
        }

        .unit-mobile-card {
            margin-bottom: 12px;
            padding: 16px;
            border: 1px solid var(--unit-border);
            border-radius: 15px;
            background: #fff;
        }

        .unit-mobile-card:last-child {
            margin-bottom: 0;
        }

        .unit-mobile-top {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 15px;
        }

        .unit-mobile-heading {
            min-width: 0;
            flex: 1;
        }

        .unit-mobile-name {
            margin: 1px 0 6px;
            color: var(--unit-text);
            font-size: 14px;
            font-weight: 800;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .unit-mobile-id {
            color: #8a95a8;
            font-size: 11px;
            font-weight: 700;
        }

        .unit-mobile-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid #edf0f5;
        }

        .unit-mobile-detail {
            min-width: 0;
        }

        .unit-mobile-detail-label {
            margin-bottom: 6px;
            color: #8a95a8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .6px;
            text-transform: uppercase;
        }

        .unit-mobile-detail-value {
            color: #435069;
            font-size: 12px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .units-empty {
            padding: 58px 24px;
            text-align: center;
        }

        .units-empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 66px;
            height: 66px;
            margin: 0 auto 17px;
            border: 1px solid #e4eaff;
            border-radius: 21px;
            background: #f1f4ff;
            color: var(--unit-primary);
            font-size: 24px;
        }

        .units-empty-title {
            margin: 0 0 8px;
            color: var(--unit-text);
            font-size: 16px;
            font-weight: 800;
        }

        .units-empty-text {
            max-width: 380px;
            margin: 0 auto;
            color: var(--unit-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .units-panel-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 15px 24px;
            border-top: 1px solid var(--unit-border);
            background: #fcfdff;
            color: #8490a3;
            font-size: 11px;
        }

        .units-footer-secure {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .units-footer-secure i {
            color: var(--unit-green);
        }

        .units-loading {
            padding: 24px;
            color: var(--unit-muted);
            font-size: 13px;
            text-align: center;
        }

        [x-cloak] {
            display: none !important;
        }

        @media (max-width: 1100px) {
            .units-page {
                padding: 20px;
            }

            .units-table th,
            .units-table td {
                padding-right: 16px;
                padding-left: 16px;
            }

            .units-panel-header,
            .units-toolbar {
                padding-right: 20px;
                padding-left: 20px;
            }
        }

        @media (max-width: 800px) {
            .units-overview {
                gap: 12px;
            }

            .units-stat {
                padding: 15px;
                gap: 11px;
            }

            .units-stat-icon {
                width: 41px;
                height: 41px;
            }

            .units-stat-value {
                font-size: 22px;
            }

            .units-table-wrap {
                display: none;
            }

            .units-mobile-list {
                display: block;
            }
        }

        @media (max-width: 600px) {
            .units-heading-actions {
                align-self: stretch;
                justify-content: space-between;
                width: 100%;
            }

            .unit-register-button {
                min-height: 43px;
                padding: 0 13px;
                font-size: 12px;
            }

            .units-page {
                padding: 15px 12px 25px;
            }

            .units-heading {
                gap: 12px;
                margin-bottom: 19px;
            }

            .units-title {
                font-size: 25px;
                letter-spacing: -.7px;
            }

            .units-subtitle {
                margin-top: 8px;
                font-size: 12px;
            }

            .units-heading-icon {
                width: 46px;
                height: 46px;
                border-radius: 14px;
                font-size: 18px;
            }

            .units-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 17px;
            }

            .units-stat {
                padding: 13px;
                border-radius: 14px;
            }

            .units-stat:last-child {
                grid-column: 1 / -1;
            }

            .units-stat-icon {
                width: 38px;
                height: 38px;
                border-radius: 11px;
                font-size: 15px;
            }

            .units-stat-label {
                font-size: 11px;
            }

            .units-stat-value {
                font-size: 21px;
            }

            .units-panel {
                border-radius: 15px;
            }

            .units-panel-header {
                align-items: flex-start;
                padding: 17px 15px;
            }

            .units-panel-title {
                font-size: 15px;
            }

            .units-panel-description {
                font-size: 11px;
            }

            .units-record-count {
                padding: 7px 9px;
                font-size: 10px;
            }

            .units-toolbar {
                align-items: flex-start;
                flex-direction: column;
                padding: 14px 15px;
            }

            .units-search {
                width: 100%;
            }

            .units-search input {
                height: 43px;
                font-size: 12px;
            }

            .units-results-label {
                font-size: 11px;
            }

            .units-mobile-list {
                padding: 10px;
            }

            .unit-mobile-card {
                padding: 14px;
            }

            .units-panel-footer {
                align-items: flex-start;
                flex-direction: column;
                padding: 13px 15px;
                font-size: 10px;
            }
        }

        @media (max-width: 360px) {
            .units-heading {
                flex-direction: column;
                align-items: stretch;
            }

            .units-heading-actions {
                align-self: stretch;
            }

            .units-page {
                padding-right: 9px;
                padding-left: 9px;
            }

            .units-title {
                font-size: 22px;
            }

            .units-heading-icon {
                width: 40px;
                height: 40px;
            }

            .units-stat {
                padding: 10px;
                gap: 8px;
            }

            .units-stat-icon {
                width: 33px;
                height: 33px;
            }

            .units-stat-value {
                font-size: 19px;
            }

            .units-panel-header {
                gap: 8px;
                padding-right: 11px;
                padding-left: 11px;
            }
        }

        /* Respect the existing Alpine dark-mode setting without changing Alpine. */
        body.dark .units-page,
        .dark .units-page {
            --unit-text: #edf2fb;
            --unit-muted: #a0acc0;
            --unit-border: #303b50;
            --unit-surface: #182235;
            --unit-background: #111827;
            --unit-shadow: 0 12px 35px rgba(0, 0, 0, .12);
        }

        body.dark .units-title,
        .dark .units-title,
        body.dark .units-stat-value,
        .dark .units-stat-value,
        body.dark .units-panel-title,
        .dark .units-panel-title,
        body.dark .units-name,
        .dark .units-name,
        body.dark .units-empty-title,
        .dark .units-empty-title {
            color: var(--unit-text);
        }

        body.dark .units-stat,
        .dark .units-stat,
        body.dark .units-panel,
        .dark .units-panel,
        body.dark .unit-mobile-card,
        .dark .unit-mobile-card {
            background: var(--unit-surface);
            border-color: var(--unit-border);
        }

        body.dark .units-table thead,
        .dark .units-table thead,
        body.dark .units-panel-footer,
        .dark .units-panel-footer {
            background: #1d293d;
        }

        body.dark .units-table td,
        .dark .units-table td {
            border-color: var(--unit-border);
            color: #c1ccdc;
        }

        body.dark .units-table tbody tr:hover,
        .dark .units-table tbody tr:hover {
            background: #202d42;
        }

        body.dark .units-search input,
        .dark .units-search input {
            border-color: var(--unit-border);
            background: #111b2b;
            color: #edf2fb;
        }

        body.dark .units-type-badge,
        .dark .units-type-badge {
            border-color: var(--unit-border);
            background: #202c40;
            color: #c4cee0;
        }

        body.dark .unit-mobile-detail-value,
        .dark .unit-mobile-detail-value,
        body.dark .units-creator,
        .dark .units-creator {
            color: #c1ccdc;
        }


        /* =========================================================
           RESPONSIVE LAYOUT SAFEGUARDS — DESKTOP, TABLET & MOBILE
           Keep the content inside the available space beside sidebar.
        ========================================================= */
        html,
        body {
            width: 100%;
            max-width: 100%;
            min-height: 100%;
        }

        body {
            overflow-x: hidden;
        }

        body > .flex.h-screen {
            width: 100%;
            min-width: 0;
            max-width: 100%;
        }

        body > .flex.h-screen > .relative.flex.flex-1 {
            min-width: 0;
            width: 0;
            max-width: 100%;
        }

        .units-page,
        .units-shell,
        .units-panel,
        .units-panel-header,
        .units-toolbar,
        .units-table-wrap {
            min-width: 0;
            max-width: 100%;
        }

        .units-page {
            width: 100%;
            overflow-x: hidden;
        }

        .units-heading-copy {
            flex: 1 1 360px;
        }

        .units-heading-actions {
            flex: 0 0 auto;
            max-width: 100%;
        }

        .units-table {
            min-width: 650px;
        }

        .units-toolbar {
            flex-wrap: wrap;
        }

        .units-search {
            min-width: 0;
            max-width: 100%;
        }

        @media (max-width: 1000px) {
            .units-heading {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .units-heading-copy {
                flex: 1 1 100%;
            }

            .units-heading-actions {
                width: 100%;
                justify-content: space-between;
            }

            .units-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .units-stat:last-child {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 800px) {
            .units-page {
                padding: 18px 15px 28px;
            }

            .units-heading {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }

            .units-heading-copy {
                width: 100%;
                flex: 1 1 auto;
            }

            .units-heading-actions {
                display: flex;
                width: 100%;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .unit-register-button {
                flex: 0 1 auto;
                min-width: 0;
                min-height: 44px;
                padding: 0 16px;
            }

            .units-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .units-table-wrap {
                display: none !important;
            }

            .units-mobile-list {
                display: block !important;
            }
        }

        @media (max-width: 520px) {
            .units-page {
                padding: 14px 10px 24px;
            }

            .units-eyebrow {
                font-size: 9px;
                letter-spacing: 1px;
                flex-wrap: wrap;
            }

            .units-title {
                font-size: clamp(22px, 6vw, 27px);
                overflow-wrap: anywhere;
            }

            .units-subtitle {
                font-size: 12px;
                line-height: 1.6;
            }

            .units-heading-actions {
                gap: 8px;
            }

            .unit-register-button {
                flex: 1 1 auto;
                min-height: 42px;
                padding: 0 11px;
                gap: 7px;
                font-size: 12px;
            }

            .units-heading-icon {
                width: 43px;
                height: 43px;
                flex: 0 0 43px;
            }

            .units-overview {
                grid-template-columns: minmax(0, 1fr);
                gap: 10px;
            }

            .units-stat,
            .units-stat:last-child {
                grid-column: auto;
                padding: 13px;
            }

            .units-panel-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 15px 13px;
            }

            .units-panel-header > *,
            .units-toolbar > * {
                min-width: 0;
                max-width: 100%;
            }

            .units-toolbar {
                padding: 13px;
            }

            .units-search {
                width: 100%;
            }

            .units-results-label {
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .units-mobile-list {
                padding: 10px;
            }

            .unit-mobile-card {
                padding: 12px;
            }

            .unit-mobile-details {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 340px) {
            .units-page {
                padding-right: 8px;
                padding-left: 8px;
            }

            .unit-register-button span {
                overflow-wrap: anywhere;
            }

            .units-heading-icon {
                width: 39px;
                height: 39px;
                flex-basis: 39px;
            }
        }
    </style>
</head>

<body
    x-data="{ page:'ecommerce', loaded:true, darkMode:false, stickyMenu:false, sidebarToggle:false, scrollTop:false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{ 'dark bg-gray-900': darkMode === true }"
>
    <div class="flex h-screen overflow-hidden">
        <?php include('sidebar.php'); ?>

        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
            <?php include('header.php'); ?>

            <main class="units-page">
                <div class="units-shell">

                    <section class="units-heading">
                        <div class="units-heading-copy">
                            <div class="units-eyebrow">
                                <span class="units-eyebrow-dot"></span>
                                Master Data / Inventory Intelligence
                            </div>

                            <h1 class="units-title">Units Management</h1>

                            <p class="units-subtitle">
                                Manage the measurement units used across your company's
                                products, inventory, transactions and operational records.
                            </p>
                        </div>

                        <div class="units-heading-actions">
                            <a href="new_unit.php" class="unit-register-button">
                                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                <span>Register Unit</span>
                            </a>

                            <div class="units-heading-icon" aria-hidden="true">
                                <i class="fas fa-ruler-combined"></i>
                            </div>
                        </div>
                    </section>

                    <section class="units-overview">
                        <div class="units-stat">
                            <div class="units-stat-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
                                <div class="units-stat-label">Total Units</div>
                                <div class="units-stat-value" id="unitsTotal">
                                    <?= number_format($totalUnits) ?>
                                </div>
                            </div>
                        </div>

                        <div class="units-stat">
                            <div class="units-stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="units-stat-label">Available Records</div>
                                <div class="units-stat-value" id="unitsAvailable">
                                    <?= number_format($totalUnits) ?>
                                </div>
                            </div>
                        </div>

                        <div class="units-stat">
                            <div class="units-stat-icon amber">
                                <i class="fas fa-shapes"></i>
                            </div>
                            <div>
                                <div class="units-stat-label">Unit Types</div>
                                <div class="units-stat-value" id="unitsTypeCount">
                                    <?= number_format($totalTypes) ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="units-panel">
                        <div class="units-panel-header">
                            <div>
                                <h2 class="units-panel-title">Unit Registry</h2>
                                <p class="units-panel-description">
                                    Measurement units registered under your company.
                                </p>
                            </div>

                            <div class="units-record-count">
                                <i class="fas fa-database"></i>
                                <span id="unitsCountBadge">
                                    <?= number_format($totalUnits) ?> records
                                </span>
                            </div>
                        </div>

                        <div class="units-toolbar">
                            <div class="units-search">
                                <i class="fas fa-search"></i>
                                <input
                                    type="search"
                                    id="unitsSearch"
                                    placeholder="Search by unit name, type or ID..."
                                    autocomplete="off"
                                    aria-label="Search units"
                                >
                                <button
                                    type="button"
                                    class="units-search-clear"
                                    id="unitsSearchClear"
                                    aria-label="Clear search"
                                    title="Clear search"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="units-results-label" id="unitsResultsLabel">
                                Showing <?= number_format($totalUnits) ?> records
                            </div>
                        </div>

                        <div class="units-table-wrap" id="unitsDesktopView">
                            <table class="units-table">
                                <thead>
                                    <tr>
                                        <th>Tracking ID</th>
                                        <th>Unit Name</th>
                                        <th>Unit Type</th>
                                        <th>Created By</th>
                                        <th>Date Created</th>
                                    </tr>
                                </thead>

                                <tbody id="unitsTableBody">
                                    <?php if (!empty($units)): ?>
                                        <?php foreach ($units as $unit): ?>
                                            <?php
                                                $unitId = $unit['id'] ?? '';
                                                $unitName = trim((string)($unit['unit_name'] ?? ''));
                                                $unitType = trim((string)($unit['unit_type'] ?? ''));
                                                $createdBy = trim((string)($unit['created_by'] ?? ''));
                                                $createdAt = $unit['created_at'] ?? '';
                                                $formattedDate = '—';

                                                if ($createdAt !== '' && $createdAt !== null) {
                                                    $timestamp = strtotime((string)$createdAt);
                                                    if ($timestamp !== false) {
                                                        $formattedDate = date('M d, Y', $timestamp);
                                                    }
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="units-id">#<?= unitEscape($unitId) ?></span>
                                                </td>

                                                <td>
                                                    <div class="units-name-cell">
                                                        <div class="units-name-icon">
                                                            <i class="fas fa-ruler-combined"></i>
                                                        </div>
                                                        <div class="units-name">
                                                            <?= unitEscape($unitName !== '' ? $unitName : 'Unnamed unit') ?>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <span class="units-type-badge">
                                                        <i class="fas fa-tag"></i>
                                                        <?= unitEscape($unitType !== '' ? $unitType : 'Not specified') ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span class="units-creator">
                                                        <span class="units-creator-avatar">
                                                            <i class="fas fa-user"></i>
                                                        </span>
                                                        <?= unitEscape($createdBy !== '' ? $createdBy : '—') ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span class="units-date">
                                                        <i class="far fa-calendar-alt"></i>
                                                        <?= unitEscape($formattedDate) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5">
                                                <div class="units-empty">
                                                    <div class="units-empty-icon">
                                                        <i class="fas fa-ruler-combined"></i>
                                                    </div>
                                                    <h3 class="units-empty-title">No units registered yet</h3>
                                                    <p class="units-empty-text">
                                                        Your company's measurement units will appear here
                                                        once records have been added to the units registry.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="units-mobile-list" id="unitsMobileView">
                            <?php if (!empty($units)): ?>
                                <?php foreach ($units as $unit): ?>
                                    <?php
                                        $unitId = $unit['id'] ?? '';
                                        $unitName = trim((string)($unit['unit_name'] ?? ''));
                                        $unitType = trim((string)($unit['unit_type'] ?? ''));
                                        $createdBy = trim((string)($unit['created_by'] ?? ''));
                                        $createdAt = $unit['created_at'] ?? '';
                                        $formattedDate = '—';

                                        if ($createdAt !== '' && $createdAt !== null) {
                                            $timestamp = strtotime((string)$createdAt);
                                            if ($timestamp !== false) {
                                                $formattedDate = date('M d, Y', $timestamp);
                                            }
                                        }
                                    ?>
                                    <article class="unit-mobile-card">
                                        <div class="unit-mobile-top">
                                            <div class="units-name-icon">
                                                <i class="fas fa-ruler-combined"></i>
                                            </div>
                                            <div class="unit-mobile-heading">
                                                <h3 class="unit-mobile-name">
                                                    <?= unitEscape($unitName !== '' ? $unitName : 'Unnamed unit') ?>
                                                </h3>
                                                <div class="unit-mobile-id">
                                                    Record #<?= unitEscape($unitId) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="unit-mobile-details">
                                            <div class="unit-mobile-detail">
                                                <div class="unit-mobile-detail-label">Unit Type</div>
                                                <div class="unit-mobile-detail-value">
                                                    <?= unitEscape($unitType !== '' ? $unitType : 'Not specified') ?>
                                                </div>
                                            </div>

                                            <div class="unit-mobile-detail">
                                                <div class="unit-mobile-detail-label">Created By</div>
                                                <div class="unit-mobile-detail-value">
                                                    <?= unitEscape($createdBy !== '' ? $createdBy : '—') ?>
                                                </div>
                                            </div>

                                            <div class="unit-mobile-detail">
                                                <div class="unit-mobile-detail-label">Date Created</div>
                                                <div class="unit-mobile-detail-value">
                                                    <?= unitEscape($formattedDate) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="units-empty">
                                    <div class="units-empty-icon">
                                        <i class="fas fa-ruler-combined"></i>
                                    </div>
                                    <h3 class="units-empty-title">No units registered yet</h3>
                                    <p class="units-empty-text">
                                        Your company's measurement units will appear here
                                        once records have been added to the units registry.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="units-loading" id="unitsLoading" hidden>
                            <i class="fas fa-circle-notch fa-spin"></i>
                            Searching units...
                        </div>

                        <div class="units-panel-footer">
                            <span id="unitsFooterText">
                                Unit registry · <?= number_format($totalUnits) ?> records
                            </span>
                            <span class="units-footer-secure">
                                <i class="fas fa-shield-alt"></i>
                                Company-scoped records
                            </span>
                        </div>
                    </section>
                </div>
            </main>
   </div>


</div>

            <?php include('footer.php'); ?>
    
            
    <script src="bundle.js"></script>

    <script>
    (() => {
        const searchInput = document.getElementById('unitsSearch');
        const clearButton = document.getElementById('unitsSearchClear');
        const tableBody = document.getElementById('unitsTableBody');
        const mobileView = document.getElementById('unitsMobileView');
        const loading = document.getElementById('unitsLoading');
        const resultsLabel = document.getElementById('unitsResultsLabel');
        const countBadge = document.getElementById('unitsCountBadge');
        const availableStat = document.getElementById('unitsAvailable');
        const footerText = document.getElementById('unitsFooterText');

        if (!searchInput || !tableBody || !mobileView) return;

        let searchTimer = null;
        let requestController = null;

        function escapeHTML(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[character]);
        }

        function showEmpty(message, description) {
            const html = `
                <div class="units-empty">
                    <div class="units-empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="units-empty-title">${escapeHTML(message)}</h3>
                    <p class="units-empty-text">${escapeHTML(description)}</p>
                </div>`;

            tableBody.innerHTML = `<tr><td colspan="5">${html}</td></tr>`;
            mobileView.innerHTML = html;
        }

        function renderUnits(units, total, filtered) {
            if (!Array.isArray(units)) units = [];

            if (units.length === 0) {
                showEmpty(
                    filtered ? 'No matching units found' : 'No units registered yet',
                    filtered
                        ? 'Try a different search term or clear the search field.'
                        : 'Your company’s measurement units will appear here when records are added.'
                );
            } else {
                let tableHTML = '';
                let mobileHTML = '';

                units.forEach(unit => {
                    const id = escapeHTML(unit.id);
                    const name = escapeHTML(unit.unit_name || 'Unnamed unit');
                    const type = escapeHTML(unit.unit_type || 'Not specified');
                    const creator = escapeHTML(unit.created_by || '—');
                    const date = escapeHTML(unit.formatted_date || '—');

                    tableHTML += `
                        <tr>
                            <td><span class="units-id">#${id}</span></td>
                            <td>
                                <div class="units-name-cell">
                                    <div class="units-name-icon">
                                        <i class="fas fa-ruler-combined"></i>
                                    </div>
                                    <div class="units-name">${name}</div>
                                </div>
                            </td>
                            <td>
                                <span class="units-type-badge">
                                    <i class="fas fa-tag"></i>${type}
                                </span>
                            </td>
                            <td>
                                <span class="units-creator">
                                    <span class="units-creator-avatar">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    ${creator}
                                </span>
                            </td>
                            <td>
                                <span class="units-date">
                                    <i class="far fa-calendar-alt"></i>${date}
                                </span>
                            </td>
                        </tr>`;

                    mobileHTML += `
                        <article class="unit-mobile-card">
                            <div class="unit-mobile-top">
                                <div class="units-name-icon">
                                    <i class="fas fa-ruler-combined"></i>
                                </div>
                                <div class="unit-mobile-heading">
                                    <h3 class="unit-mobile-name">${name}</h3>
                                    <div class="unit-mobile-id">Record #${id}</div>
                                </div>
                            </div>
                            <div class="unit-mobile-details">
                                <div class="unit-mobile-detail">
                                    <div class="unit-mobile-detail-label">Unit Type</div>
                                    <div class="unit-mobile-detail-value">${type}</div>
                                </div>
                                <div class="unit-mobile-detail">
                                    <div class="unit-mobile-detail-label">Created By</div>
                                    <div class="unit-mobile-detail-value">${creator}</div>
                                </div>
                                <div class="unit-mobile-detail">
                                    <div class="unit-mobile-detail-label">Date Created</div>
                                    <div class="unit-mobile-detail-value">${date}</div>
                                </div>
                            </div>
                        </article>`;
                });

                tableBody.innerHTML = tableHTML;
                mobileView.innerHTML = mobileHTML;
            }

            const shown = units.length;
            const totalNumber = Number(total) || 0;
            const countText = filtered
                ? `${shown} matching ${shown === 1 ? 'record' : 'records'}`
                : `${totalNumber.toLocaleString()} ${totalNumber === 1 ? 'record' : 'records'}`;

            resultsLabel.textContent = `Showing ${shown.toLocaleString()} of ${totalNumber.toLocaleString()} records`;
            countBadge.textContent = countText;
            availableStat.textContent = shown.toLocaleString();
            footerText.textContent = `Unit registry · ${totalNumber.toLocaleString()} total records`;
        }

        async function searchUnits() {
            const query = searchInput.value.trim();
            clearButton.classList.toggle('visible', query.length > 0);

            if (requestController) requestController.abort();
            requestController = new AbortController();

            loading.hidden = false;

            try {
                const response = await fetch(
                    'search_units.php?q=' + encodeURIComponent(query),
                    {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        signal: requestController.signal,
                        cache: 'no-store'
                    }
                );

                if (!response.ok) {
                    throw new Error('Unable to load unit records.');
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Unable to search units.');
                }

                renderUnits(result.units, result.total, query.length > 0);
            } catch (error) {
                if (error.name === 'AbortError') return;

                showEmpty(
                    'Unable to load units',
                    'Please refresh the page or try your search again.'
                );
                resultsLabel.textContent = 'Search unavailable';
            } finally {
                loading.hidden = true;
            }
        }

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(searchUnits, 250);
        });

        clearButton.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.focus();
            searchUnits();
        });
    })();
    </script>
</body>
</html>
