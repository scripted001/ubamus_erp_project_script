<?php
date_default_timezone_set('Africa/Lagos');

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

/* =========================================================
   WEEKLY SALES INTELLIGENCE — SHARED FILTERS
   Every KPI, comparison, chart and table uses the same base
   filters. Dates are inclusive; end date is exclusive in SQL.
========================================================= */

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    exit('User not logged in.');
}

/*
 * Branch Manager access is taken from the authenticated database record.
 * The manager cannot choose a company or branch through URL parameters.
 */
$stmt = $pdo->prepare("SELECT company_id, location_id, role, status FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$loggedInUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loggedInUser
    || strtolower(trim((string)($loggedInUser['role'] ?? ''))) !== 'branch manager'
    || strtolower(trim((string)($loggedInUser['status'] ?? ''))) !== 'active'
    || (int)($loggedInUser['company_id'] ?? 0) <= 0
    || (int)($loggedInUser['location_id'] ?? 0) <= 0
    || (int)($_SESSION['company_id'] ?? 0) !== (int)$loggedInUser['company_id']
    || (int)($_SESSION['location_id'] ?? 0) !== (int)$loggedInUser['location_id']) {
    http_response_code(403);
    exit('Access denied. An active Branch Manager account and assigned branch are required.');
}

$company_id = (int)$loggedInUser['company_id'];
$branch_location_id = (int)$loggedInUser['location_id'];

$branchCheck = $pdo->prepare("SELECT id FROM locations WHERE id = ? AND company_id = ? LIMIT 1");
$branchCheck->execute([$branch_location_id, $company_id]);
if (!$branchCheck->fetchColumn()) {
    http_response_code(403);
    exit('Access denied. Your assigned branch is inactive or unavailable.');
}

function salesValidDate($value): bool {
    if (!is_string($value) || $value === '') return false;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    return $date !== false
        && (!is_array($errors) || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        && $date->format('Y-m-d') === $value;
}
function salesH($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function salesPercentChange(float $current, float $previous): float {
    if (abs($previous) > 0.000001) return (($current - $previous) / abs($previous)) * 100;
    return abs($current) > 0.000001 ? 100.0 : 0.0;
}
function salesBuildWhere(
    string $start,
    string $endExclusive,
    int $companyId,
    string $searchOption,
    string $searchValue,
    int $locationId,
    int $sellerId,
    string $paymentStatus,
    string $alias = 's'
): array {
    $where = [
        "$alias.company_id = ?",
        "$alias.created_at >= ?",
        "$alias.created_at < ?"
    ];
    $params = [$companyId, $start, $endExclusive];

    if ($searchValue !== '') {
        if ($searchOption === 'customer') {
            $where[] = "$alias.customer_name LIKE ?";
        } elseif ($searchOption === 'both') {
            $where[] = "($alias.invoice_number LIKE ? OR $alias.customer_name LIKE ?)";
            $like = '%' . $searchValue . '%';
            $params[] = $like;
            $params[] = $like;
        } else {
            $where[] = "$alias.invoice_number LIKE ?";
        }
        if ($searchOption !== 'both') $params[] = '%' . $searchValue . '%';
    }
    // Mandatory branch restriction on every KPI, comparison, chart and report query.
    $where[] = "$alias.location_id = ?";
    $params[] = $locationId;
    if ($sellerId > 0) {
        $where[] = "$alias.user_id = ?";
        $params[] = $sellerId;
    }
    if ($paymentStatus !== '') {
        $where[] = "$alias.payment_status = ?";
        $params[] = $paymentStatus;
    }
    return [$where, $params];
}

/* Weekly filters: select any date; report normalizes to Monday-Sunday. */
$today = new DateTimeImmutable('today');
$defaultDate = $today->modify('monday this week')->format('Y-m-d');
$search_option = $_GET['search_option'] ?? 'invoice';
$allowedSearchOptions = ['invoice', 'customer', 'both'];
if (!in_array($search_option, $allowedSearchOptions, true)) $search_option = 'invoice';

$search_value = trim((string)($_GET['search_value'] ?? ''));
if (function_exists('mb_strlen') && mb_strlen($search_value) > 120) $search_value = mb_substr($search_value, 0, 120);
elseif (strlen($search_value) > 120) $search_value = substr($search_value, 0, 120);

$sales_date = (string)($_GET['week_date'] ?? $defaultDate);
if (!salesValidDate($sales_date)) $sales_date = $defaultDate;
$dateError = '';

/* Location is fixed to this manager's assigned branch; it is not selectable. */
$location_id = $branch_location_id;
$seller_id = filter_var($_GET['seller_id'] ?? 0, FILTER_VALIDATE_INT);
$seller_id = ($seller_id !== false && $seller_id > 0) ? (int)$seller_id : 0;

/* A manually supplied seller ID must have sales in this branch. */
if ($seller_id > 0) {
    $sellerCheck = $pdo->prepare("SELECT 1 FROM sales WHERE company_id = ? AND location_id = ? AND user_id = ? LIMIT 1");
    $sellerCheck->execute([$company_id, $branch_location_id, $seller_id]);
    if (!$sellerCheck->fetchColumn()) $seller_id = 0;
}
$payment_status = trim((string)($_GET['payment_status'] ?? ''));
if (function_exists('mb_strlen') && mb_strlen($payment_status) > 60) $payment_status = mb_substr($payment_status, 0, 60);
elseif (strlen($payment_status) > 60) $payment_status = substr($payment_status, 0, 60);

/* Whitelist payment status values from this branch's sales. */
$stmt = $pdo->prepare("SELECT DISTINCT payment_status FROM sales WHERE company_id = ? AND location_id = ? AND payment_status IS NOT NULL AND payment_status <> '' ORDER BY payment_status");
$stmt->execute([$company_id, $branch_location_id]);
$paymentStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
if ($payment_status !== '' && !in_array($payment_status, $paymentStatuses, true)) $payment_status = '';

/* Seller filter options are limited to users who sold at this branch. */
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.email,
           TRIM(CONCAT(COALESCE(st.firstname,''),' ',COALESCE(st.lastname,''))) AS fullname
    FROM users u
    INNER JOIN sales s ON s.user_id = u.id
        AND s.company_id = ? AND s.location_id = ?
    LEFT JOIN staffs st ON st.email = u.email
    WHERE u.company_id = ?
    ORDER BY fullname, u.email
");
$stmt->execute([$company_id, $branch_location_id, $company_id]);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedDateObj = new DateTimeImmutable($sales_date);
$startObj = $selectedDateObj->modify('monday this week');
$endObj = $startObj->modify('+6 days');
$weekStart = $startObj->format('Y-m-d');
$endExclusive = $startObj->modify('+7 days')->format('Y-m-d');
$previousStartObj = $startObj->modify('-7 days');
$previousEndObj = $startObj->modify('-1 day');
$previousStart = $previousStartObj->format('Y-m-d');
$previousEndExclusive = $startObj->format('Y-m-d');

[$currentWhere, $currentParams] = salesBuildWhere(
    $weekStart, $endExclusive, $company_id, $search_option, $search_value,
    $location_id, $seller_id, $payment_status
);
[$previousWhere, $previousParams] = salesBuildWhere(
    $previousStart, $previousEndExclusive, $company_id, $search_option, $search_value,
    $location_id, $seller_id, $payment_status
);
$currentWhereSql = implode(' AND ', $currentWhere);
$previousWhereSql = implode(' AND ', $previousWhere);

/* Revenue */
$stmt = $pdo->prepare("SELECT COALESCE(SUM(s.final_total),0) FROM sales s WHERE $currentWhereSql");
$stmt->execute($currentParams);
$total_period_sales = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(s.final_total),0) FROM sales s WHERE $previousWhereSql");
$stmt->execute($previousParams);
$total_previous_sales = (float)$stmt->fetchColumn();

/* Profit: preserves the original page's formula. */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM((si.unit_price - p.cost_price) * si.quantity),0)
    FROM sale_items si
    INNER JOIN sales s ON s.id = si.sale_id
    INNER JOIN products p ON p.id = si.product_id AND p.company_id = s.company_id
    WHERE $currentWhereSql
");
$stmt->execute($currentParams);
$total_period_profit = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM((si.unit_price - p.cost_price) * si.quantity),0)
    FROM sale_items si
    INNER JOIN sales s ON s.id = si.sale_id
    INNER JOIN products p ON p.id = si.product_id AND p.company_id = s.company_id
    WHERE $previousWhereSql
");
$stmt->execute($previousParams);
$total_previous_profit = (float)$stmt->fetchColumn();

/* Transaction count and average ticket */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales s WHERE $currentWhereSql");
$stmt->execute($currentParams);
$invoice_count_period = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales s WHERE $previousWhereSql");
$stmt->execute($previousParams);
$invoice_count_previous = (int)$stmt->fetchColumn();

$average_sale_period = $invoice_count_period > 0 ? $total_period_sales / $invoice_count_period : 0;
$average_sale_previous = $invoice_count_previous > 0 ? $total_previous_sales / $invoice_count_previous : 0;

$revenue_change = salesPercentChange($total_period_sales, $total_previous_sales);
$profit_change = salesPercentChange($total_period_profit, $total_previous_profit);
$invoice_change = salesPercentChange((float)$invoice_count_period, (float)$invoice_count_previous);
$average_change = salesPercentChange($average_sale_period, $average_sale_previous);

/* Top product based on quantity sold in the filtered result. */
$stmt = $pdo->prepare("
    SELECT si.product_id, p.name, COALESCE(SUM(si.quantity),0) AS total_qty
    FROM sale_items si
    INNER JOIN sales s ON s.id = si.sale_id
    INNER JOIN products p ON p.id = si.product_id AND p.company_id = s.company_id
    WHERE $currentWhereSql
    GROUP BY si.product_id, p.name
    ORDER BY total_qty DESC, p.name ASC
    LIMIT 1
");
$stmt->execute($currentParams);
$best = $stmt->fetch(PDO::FETCH_ASSOC);
$best_product_id = $best['product_id'] ?? null;
$best_product_name = $best['name'] ?? 'No matching product sales';
$best_product_qty = (float)($best['total_qty'] ?? 0);
$best_product_qty_previous = 0;

if ($best_product_id !== null) {
    $topPreviousWhere = $previousWhere;
    $topPreviousParams = $previousParams;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(si.quantity),0)
        FROM sale_items si
        INNER JOIN sales s ON s.id = si.sale_id
        WHERE si.product_id = ? AND " . implode(' AND ', $topPreviousWhere)
    );
    $stmt->execute(array_merge([(int)$best_product_id], $topPreviousParams));
    $best_product_qty_previous = (float)$stmt->fetchColumn();
}
$best_product_change = salesPercentChange($best_product_qty, $best_product_qty_previous);

/* Chart: daily revenue for all seven days of the selected week. */
$stmt = $pdo->prepare("SELECT DATE(s.created_at) AS sale_day, COALESCE(SUM(s.final_total),0) AS total FROM sales s WHERE $currentWhereSql GROUP BY DATE(s.created_at) ORDER BY DATE(s.created_at)");
$stmt->execute($currentParams);
$rawChart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$chartLabels = [];
$chartValues = [];
$chartDates = [];
for ($day = 0; $day < 7; $day++) {
    $chartDay = $startObj->modify('+' . $day . ' days');
    $chartKey = $chartDay->format('Y-m-d');
    $chartLabels[] = $chartDay->format('D, M j');
    $chartValues[] = isset($rawChart[$chartKey]) ? (float)$rawChart[$chartKey] : 0;
    $chartDates[] = $chartDay->format('l, M j, Y');
}

/* Report table */
$stmt = $pdo->prepare("
    SELECT s.*,
           COALESCE(l.name,'N/A') AS location_name,
           COALESCE(u.email,'') AS seller_email,
           TRIM(CONCAT(COALESCE(st.firstname,''),' ',COALESCE(st.lastname,''))) AS seller_fullname
    FROM sales s
    LEFT JOIN locations l ON l.id = s.location_id
    LEFT JOIN users u ON u.id = s.user_id
    LEFT JOIN staffs st ON st.email = u.email
    WHERE $currentWhereSql
    ORDER BY s.created_at DESC, s.id DESC
");
$stmt->execute($currentParams);
$salesRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeFilterCount = (int)($search_value !== '')
    + (int)($seller_id > 0)
    + (int)($payment_status !== '')
    + (int)($weekStart !== $defaultDate);

$chartLabelsJson = json_encode($chartLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$chartValuesJson = json_encode($chartValues, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$chartDatesJson = json_encode($chartDates, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);

$periodDisplay = $startObj->format('M d, Y') . ' – ' . $endObj->format('M d, Y');
$previousDisplay = $previousStartObj->format('M d, Y') . ' – ' . $previousEndObj->format('M d, Y');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= salesH($company_name ?? 'Company'); ?> | Sales Intelligence</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<style>
:root{--f-bg:#f5f8fc;--f-border:#e5eaf2;--f-text:#101828;--f-muted:#667085;--f-blue:#2563eb;--f-green:#059669;--f-red:#dc2626;--f-shadow:0 18px 50px rgba(15,23,42,.07)}
body{background:radial-gradient(circle at 8% 8%,rgba(37,99,235,.07),transparent 28%),radial-gradient(circle at 92% 12%,rgba(124,58,237,.055),transparent 26%),#f5f8fc;color:var(--f-text)}
.dashboard-page{width:100%;max-width:1650px;margin:0 auto}
.dashboard-intro{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:20px;padding:25px 28px;border:1px solid #fff;border-radius:22px;background:linear-gradient(120deg,#fff,#f8faff);box-shadow:var(--f-shadow)}
.dashboard-intro h1{margin:0;font-size:clamp(23px,2.2vw,33px);font-weight:800;letter-spacing:-.8px}
.dashboard-intro p{margin:7px 0 0;color:var(--f-muted);font-size:13px}
.dashboard-date,.period-chip{display:inline-flex;align-items:center;gap:9px;padding:11px 14px;border:1px solid var(--f-border);border-radius:12px;background:#fff;color:#475467;font-size:11px;font-weight:700}
.dashboard-date i{color:var(--f-blue)}
.period-strip{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 16px;color:var(--f-muted);font-size:11px}
.period-strip strong{color:var(--f-text)}
.dashboard-kpi-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:18px}
.dashboard-kpi{position:relative;min-width:0;padding:20px;border:1px solid var(--f-border);border-radius:18px;background:linear-gradient(145deg,#fff,#f9fbff);box-shadow:var(--f-shadow);transition:.2s}
.dashboard-kpi:hover{transform:translateY(-3px);box-shadow:0 22px 55px rgba(37,99,235,.12)}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.kpi-label{margin:0;color:var(--f-muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.kpi-value{margin:9px 0 0;font-size:clamp(18px,1.5vw,24px);font-weight:800;letter-spacing:-.5px;overflow-wrap:anywhere}
.kpi-value.product{font-size:15px;line-height:1.4}
.kpi-icon{display:grid;place-items:center;width:40px;height:40px;flex:0 0 40px;border:1px solid #dbeafe;border-radius:12px;background:#eff6ff;color:var(--f-blue)}
.kpi-bottom{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:16px;padding-top:12px;border-top:1px solid #edf0f5}
.change{display:inline-flex;gap:4px;align-items:center;padding:5px 8px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap}
.change.positive{color:#047857;background:#ecfdf5}.change.negative{color:#b91c1c;background:#fef2f2}
.kpi-caption{color:#98a2b3;font-size:9px;font-weight:700;text-align:right}
.dashboard-card{border:1px solid var(--f-border);border-radius:20px;background:rgba(255,255,255,.96);box-shadow:var(--f-shadow);overflow:hidden;margin-bottom:18px}
.analytics-card{padding:23px}
.card-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:17px}
.card-heading h2,.sales-title h2{margin:0;font-size:17px;font-weight:800}
.card-heading p,.sales-title p{margin:5px 0 0;color:var(--f-muted);font-size:11px}
.live-pill{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid #d1fae5;border-radius:20px;background:#f0fdf9;color:#047857;font-size:9px;font-weight:800;text-transform:uppercase}
.live-pill span{width:6px;height:6px;border-radius:50%;background:#10b981}
.summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:15px}
.summary-item{padding:12px 14px;border:1px solid #edf0f5;border-radius:12px;background:#fbfcfe}
.summary-label{display:block;margin-bottom:4px;color:#98a2b3;font-size:9px;font-weight:800;text-transform:uppercase}
.summary-value{font-size:13px;font-weight:800;color:#1d2939}
.chart-container{height:340px;position:relative}
#salesChart{width:100%!important;height:100%!important}
.sales-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:22px;border-bottom:1px solid #edf0f5;background:linear-gradient(135deg,#fff,#fbfcff)}
.sales-filters{display:grid;grid-template-columns:repeat(4,minmax(125px,1fr));gap:8px;width:min(100%,850px)}
.sales-filter-control{width:100%;min-width:0;height:40px;padding:0 10px;border:1px solid #dfe5ee;border-radius:10px;background:#fff;color:#344054;font-size:11px;font-weight:600;outline:none}
.sales-filter-control:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(37,99,235,.08)}
.filter-actions{display:flex;gap:8px;grid-column:1/-1}
.filter-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:39px;padding:0 14px;border:1px solid #2563eb;border-radius:10px;background:#2563eb;color:#fff;font-size:11px;font-weight:800;cursor:pointer}
.filter-btn:hover{background:#1d4ed8}
.filter-btn.secondary{border-color:#dfe5ee;background:#fff;color:#475467}
.filter-btn.secondary:hover{background:#f8fafc}
.filter-status{padding:10px 22px;border-bottom:1px solid #edf0f5;color:#667085;font-size:10px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
.filter-status strong{color:#344054}
.sales-table-wrapper{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.sales-table{width:100%;min-width:1250px;border-collapse:separate;border-spacing:0}
.sales-table thead th{padding:13px 14px;border-bottom:1px solid #edf0f5;background:#f8fafc;color:#667085;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;text-align:left;white-space:nowrap}
.sales-table thead th:first-child,.sales-table tbody td:first-child{padding-left:22px}
.sales-table tbody td{padding:14px;border-bottom:1px solid #f1f3f6;color:#475467;font-size:11px;vertical-align:middle;white-space:nowrap}
.sales-table tbody tr:hover{background:#f8fbff}.sales-table tbody tr:last-child td{border-bottom:0}
.invoice-number{color:#2563eb;font-weight:800}.customer-name,.amount-main{color:#1d2939;font-weight:800}.amount-success{color:#047857;font-weight:800}.amount-warning{color:#b45309;font-weight:800}
.payment-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border:1px solid #e5e7eb;border-radius:20px;background:#f8fafc;color:#667085;font-size:9px;font-weight:800;text-transform:capitalize}
.payment-badge:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
.invoice-view-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid #dbeafe;border-radius:8px;background:#eff6ff;color:#2563eb;font-size:10px;font-weight:800;text-decoration:none}
.invoice-view-btn:hover{background:#2563eb;color:#fff}
.sales-empty{padding:55px 20px!important;text-align:center;color:#98a2b3!important}
.notice{margin-bottom:15px;padding:11px 14px;border:1px solid #fed7aa;border-radius:11px;background:#fff7ed;color:#9a3412;font-size:12px}
@media(max-width:1450px){.dashboard-kpi-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.sales-header{flex-direction:column}.sales-filters{width:100%}}
@media(max-width:900px){.dashboard-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sales-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.dashboard-intro{align-items:flex-start;flex-direction:column;padding:19px}.dashboard-date{width:100%;justify-content:center}.dashboard-kpi-grid{grid-template-columns:1fr}.dashboard-kpi{padding:17px}.analytics-card{padding:17px 13px}.card-heading{flex-direction:column}.summary-grid{grid-template-columns:1fr}.chart-container{height:270px}.sales-header{padding:17px 13px}.sales-filters{grid-template-columns:1fr}.filter-actions{flex-direction:column}.filter-btn{width:100%}.filter-status{padding:10px 14px}}
</style>
</head>
<body
    x-data="{ page: 'ecommerce', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
    x-init="darkMode = false"
    :class="{'dark bg-gray-900': false}"
>
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <?php include('header.php'); ?>
        <main>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="dashboard-page">
                    <section class="dashboard-intro">
                        <div>
                            <h1>Sales Intelligence</h1>
                            <p>Weekly view of revenue, profit, products and transactions.</p>
                        </div>
                        <div class="dashboard-date"><i class="fa-solid fa-calendar-week"></i>Weekly Sales Report</div>
                    </section>

                    <?php if ($dateError !== ''): ?><div class="notice"><?= salesH($dateError); ?></div><?php endif; ?>

                    <div class="period-strip">
                        <span><i class="fa-solid fa-filter"></i> ACTIVE REPORT PERIOD</span>
                        <strong><?= salesH($periodDisplay); ?></strong>
                        <span>Comparison: <strong><?= salesH($previousDisplay); ?></strong></span>
                    </div>

                    <section class="dashboard-kpi-grid">
                        <div class="dashboard-kpi">
                            <div class="kpi-top"><div><p class="kpi-label">Revenue</p><p class="kpi-value">₦<?= number_format($total_period_sales,2); ?></p></div><div class="kpi-icon"><i class="fa-solid fa-chart-line"></i></div></div>
                            <div class="kpi-bottom"><span class="change <?= $revenue_change >= 0 ? 'positive':'negative'; ?>"><i class="fa-solid <?= $revenue_change >= 0 ? 'fa-arrow-trend-up':'fa-arrow-trend-down'; ?>"></i><?= $revenue_change >= 0 ? '+':''; ?><?= number_format($revenue_change,1); ?>%</span><span class="kpi-caption">vs prior period</span></div>
                        </div>
                        <div class="dashboard-kpi">
                            <div class="kpi-top"><div><p class="kpi-label">Profit</p><p class="kpi-value">₦<?= number_format($total_period_profit,2); ?></p></div><div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div></div>
                            <div class="kpi-bottom"><span class="change <?= $profit_change >= 0 ? 'positive':'negative'; ?>"><i class="fa-solid <?= $profit_change >= 0 ? 'fa-arrow-trend-up':'fa-arrow-trend-down'; ?>"></i><?= $profit_change >= 0 ? '+':''; ?><?= number_format($profit_change,1); ?>%</span><span class="kpi-caption">vs prior period</span></div>
                        </div>
                        <div class="dashboard-kpi">
                            <div class="kpi-top"><div><p class="kpi-label">Transactions</p><p class="kpi-value"><?= number_format($invoice_count_period); ?></p></div><div class="kpi-icon"><i class="fa-solid fa-receipt"></i></div></div>
                            <div class="kpi-bottom"><span class="change <?= $invoice_change >= 0 ? 'positive':'negative'; ?>"><i class="fa-solid <?= $invoice_change >= 0 ? 'fa-arrow-trend-up':'fa-arrow-trend-down'; ?>"></i><?= $invoice_change >= 0 ? '+':''; ?><?= number_format($invoice_change,1); ?>%</span><span class="kpi-caption">vs prior period</span></div>
                        </div>
                        <div class="dashboard-kpi">
                            <div class="kpi-top"><div><p class="kpi-label">Average Transaction</p><p class="kpi-value">₦<?= number_format($average_sale_period,2); ?></p></div><div class="kpi-icon"><i class="fa-solid fa-wallet"></i></div></div>
                            <div class="kpi-bottom"><span class="change <?= $average_change >= 0 ? 'positive':'negative'; ?>"><i class="fa-solid <?= $average_change >= 0 ? 'fa-arrow-trend-up':'fa-arrow-trend-down'; ?>"></i><?= $average_change >= 0 ? '+':''; ?><?= number_format($average_change,1); ?>%</span><span class="kpi-caption">vs prior period</span></div>
                        </div>
                        <div class="dashboard-kpi">
                            <div class="kpi-top"><div><p class="kpi-label">Top Product</p><p class="kpi-value product"><?= salesH($best_product_name); ?></p></div><div class="kpi-icon"><i class="fa-solid fa-box-open"></i></div></div>
                            <div class="kpi-bottom"><span class="change <?= $best_product_change >= 0 ? 'positive':'negative'; ?>"><i class="fa-solid <?= $best_product_change >= 0 ? 'fa-arrow-trend-up':'fa-arrow-trend-down'; ?>"></i><?= $best_product_change >= 0 ? '+':''; ?><?= number_format($best_product_change,1); ?>%</span><span class="kpi-caption"><?= number_format($best_product_qty); ?> units</span></div>
                        </div>
                    </section>

                    <section class="dashboard-card analytics-card">
                        <div class="card-heading">
                            <div><h2>Revenue Analytics</h2><p>Daily revenue across the selected week using the active filters.</p></div>
                            <div class="live-pill"><span></span> Filter-synced report</div>
                        </div>
                        <div class="summary-grid">
                            <div class="summary-item"><span class="summary-label">Filtered Revenue</span><span class="summary-value">₦<?= number_format($total_period_sales,2); ?></span></div>
                            <div class="summary-item"><span class="summary-label">Matching Transactions</span><span class="summary-value"><?= number_format($invoice_count_period); ?></span></div>
                            <div class="summary-item"><span class="summary-label">Filtered Profit</span><span class="summary-value">₦<?= number_format($total_period_profit,2); ?></span></div>
                        </div>
                        <div class="chart-container"><canvas id="salesChart"></canvas></div>
                    </section>

                    <section class="dashboard-card">
                        <div class="sales-header">
                            <div class="sales-title"><h2>Sales Report</h2><p>Weekly filters update every KPI, comparison, daily chart and row together.</p></div>
                            <form id="salesFilterForm" class="sales-filters" method="get" action="">
                                <select name="search_option" id="search_option" class="sales-filter-control" aria-label="Search field">
                                    <option value="invoice" <?= $search_option === 'invoice'?'selected':''; ?>>Invoice number</option>
                                    <option value="customer" <?= $search_option === 'customer'?'selected':''; ?>>Customer name</option>
                                    <option value="both" <?= $search_option === 'both'?'selected':''; ?>>Invoice or customer</option>
                                </select>
                                <input type="search" name="search_value" id="search_value" class="sales-filter-control" placeholder="Search invoice or customer..." value="<?= salesH($search_value); ?>" maxlength="120">
                                <input type="date" name="week_date" id="week_date" class="sales-filter-control" value="<?= salesH($sales_date); ?>" aria-label="Select any date in the week" required>
                                <select name="seller_id" id="seller_id" class="sales-filter-control" aria-label="Filter by seller">
                                    <option value="0">All sellers</option>
                                    <?php foreach ($sellers as $seller): $sellerName = trim((string)($seller['fullname'] ?? '')); if ($sellerName === '') $sellerName = $seller['email'] ?: 'Unknown seller'; ?>
                                    <option value="<?= (int)$seller['id']; ?>" <?= $seller_id === (int)$seller['id']?'selected':''; ?>><?= salesH($sellerName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="payment_status" id="payment_status" class="sales-filter-control" aria-label="Filter by payment status">
                                    <option value="">All payment statuses</option>
                                    <?php foreach ($paymentStatuses as $status): ?>
                                    <option value="<?= salesH($status); ?>" <?= $payment_status === $status?'selected':''; ?>><?= salesH(ucfirst((string)$status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="filter-actions">
                                    <button type="submit" id="apply_sales_filters" class="filter-btn"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                                    <button type="button" id="reset_sales_filters" class="filter-btn secondary"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                                </div>
                            </form>
                        </div>
                        <div class="filter-status">
                            <span><strong><?= number_format(count($salesRows)); ?></strong> matching transactions shown</span>
                            <span><?= $activeFilterCount ? '<strong>'.(int)$activeFilterCount.' filter group(s) active</strong>' : 'No additional search filters'; ?></span>
                        </div>
                        <div class="sales-table-wrapper">
                            <table class="sales-table">
                                <thead><tr>
                                    <th>Invoice No.</th><th>Customer Name</th><th>Location</th><th>Sold By</th>
                                    <th>Total Amount</th><th>Discount %</th><th>Discount Amount</th><th>Grand Total</th>
                                    <th>Payment Status</th><th>Amount Deposited</th><th>Balance</th><th>Balance Due Date</th><th>Date Sold</th><th>Invoice</th>
                                </tr></thead>
                                <tbody id="salesTable">
                                <?php if ($salesRows): foreach ($salesRows as $sale):
                                    $locName = $sale['location_name'] ?? 'N/A';
                                    $sellerFullname = trim((string)($sale['seller_fullname'] ?? ''));
                                    if ($sellerFullname === '') $sellerFullname = $sale['seller_email'] ?: 'Unknown';
                                    $dueDate = 'N/A';
                                    if (!empty($sale['due_date']) && $sale['due_date'] !== '0000-00-00' && $sale['due_date'] !== '0000-00-00 00:00:00') {
                                        $dueTs = strtotime((string)$sale['due_date']);
                                        if ($dueTs) $dueDate = date('M d, Y h:i A', $dueTs);
                                    }
                                    $invoiceNumber = (string)($sale['invoice_number'] ?? '');
                                    $pdfFile = '../invoices/invoice_' . $invoiceNumber . '.pdf';
                                    $createdTs = strtotime((string)($sale['created_at'] ?? ''));
                                ?>
                                    <tr>
                                        <td><span class="invoice-number"><?= salesH($invoiceNumber); ?></span></td>
                                        <td><span class="customer-name"><?= salesH($sale['customer_name'] ?? 'N/A'); ?></span></td>
                                        <td><?= salesH($locName); ?></td>
                                        <td><?= salesH($sellerFullname); ?></td>
                                        <td><span class="amount-main">₦<?= number_format((float)($sale['grand_total'] ?? 0),2); ?></span></td>
                                        <td><?= salesH($sale['discount_percent'] ?? '0'); ?>%</td>
                                        <td>₦<?= number_format((float)($sale['discount_amount'] ?? 0),2); ?></td>
                                        <td><span class="amount-success">₦<?= number_format((float)($sale['final_total'] ?? 0),2); ?></span></td>
                                        <td><span class="payment-badge"><?= salesH($sale['payment_status'] ?? 'N/A'); ?></span></td>
                                        <td><span class="amount-success">₦<?= number_format((float)($sale['amount_paid'] ?? 0),2); ?></span></td>
                                        <td><span class="amount-warning">₦<?= number_format((float)($sale['balance'] ?? 0),2); ?></span></td>
                                        <td><?= salesH($dueDate); ?></td>
                                        <td><?= $createdTs ? salesH(date('M d, Y h:i A',$createdTs)) : 'N/A'; ?></td>
                                        <td><?php if (is_file($pdfFile)): ?><a href="<?= salesH($pdfFile); ?>" target="_blank" rel="noopener noreferrer" class="invoice-view-btn"><i class="fa-solid fa-file-pdf"></i> View</a><?php else: ?><span style="color:#98a2b3">Not found</span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="14" class="sales-empty"><i class="fa-solid fa-magnifying-glass" style="display:block;font-size:24px;margin-bottom:12px;color:#2563eb"></i><strong>No matching sales found</strong><br><span>Try another week or remove one or more filters.</span></td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </main>
        <?php include('footer.php'); ?>
    </div>
</div>
<script src="bundle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    const labels = <?= $chartLabelsJson ?: '[]'; ?>;
    const values = <?= $chartValuesJson ?: '[]'; ?>;
    const dates = <?= $chartDatesJson ?: '[]'; ?>;

    function initChart(){
        const canvas = document.getElementById('salesChart');
        if (!canvas) return;
        if (typeof Chart === 'undefined') { setTimeout(initChart, 100); return; }
        const previous = Chart.getChart(canvas);
        if (previous) previous.destroy();

        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 340);
        gradient.addColorStop(0, 'rgba(37,99,235,.72)');
        gradient.addColorStop(1, 'rgba(37,99,235,.06)');

        new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{
                label: 'Sales revenue',
                data: values,
                backgroundColor: gradient,
                borderColor: '#93c5fd',
                borderWidth: 1,
                borderRadius: 7,
                borderSkipped: false,
                maxBarThickness: 34
            }]},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {intersect:false, mode:'index'},
                plugins: {
                    legend: {display:false},
                    tooltip: {
                        displayColors:false,
                        backgroundColor:'#101828',
                        padding:12,
                        callbacks: {
                            title: function(items){
                                if (!items.length || !dates[items[0].dataIndex]) return '';
                                return dates[items[0].dataIndex];
                            },
                            label: function(ctx){
                                return ' Revenue: ₦' + Number(ctx.raw || 0).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});
                            }
                        }
                    }
                },
                scales: {
                    x: {grid:{display:false}, border:{display:false}, ticks:{color:'#667085',font:{size:10,weight:'600'},autoSkip:true,maxTicksLimit:16}},
                    y: {beginAtZero:true,border:{display:false},grid:{color:'rgba(148,163,184,.14)'},ticks:{
                        color:'#98a2b3',font:{size:9},
                        callback:function(value){
                            if (value >= 1000000) return '₦' + (value/1000000).toFixed(1) + 'M';
                            if (value >= 1000) return '₦' + (value/1000).toFixed(0) + 'K';
                            return '₦' + value;
                        }
                    }}
                }
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initChart);
    else initChart();
})();

document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('salesFilterForm');
    const salesDate = document.getElementById('week_date');
    const reset = document.getElementById('reset_sales_filters');
    const search = document.getElementById('search_value');

    if (form) {
        form.addEventListener('submit', function(event){
            if (salesDate && !salesDate.value) {
                event.preventDefault();
                alert('Please select a date within the week you want to report.');
                salesDate.focus();
            }
        });
    }
    if (search) {
        search.addEventListener('keydown', function(event){
            if (event.key === 'Enter') {
                event.preventDefault();
                if (form) form.requestSubmit();
            }
        });
    }
    if (reset) {
        reset.addEventListener('click', function(){
            window.location.href = window.location.pathname;
        });
    }
});
</script>
</body>
</html>
