<?php
date_default_timezone_set('Africa/Lagos');

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

/* =========================================================
   ANNUAL SALES INTELLIGENCE — SHARED FILTERS
   Every KPI, comparison, chart and table uses the same base
   filters. Dates are inclusive; end date is exclusive in SQL.
========================================================= */

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    exit('User not logged in.');
}

$stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$userCompany = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userCompany || (int)($userCompany['company_id'] ?? 0) <= 0) {
    http_response_code(403);
    exit('Invalid company.');
}
$company_id = (int)$userCompany['company_id'];

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
    int $userId,
    int $locationId,
    string $searchOption,
    string $searchValue,
    string $paymentStatus,
    string $alias = 's'
): array {
    // Security boundary: every query is fixed to this logged-in user and active location.
    $where = [
        "$alias.company_id = ?",
        "$alias.user_id = ?",
        "$alias.location_id = ?",
        "$alias.created_at >= ?",
        "$alias.created_at < ?"
    ];
    $params = [$companyId, $userId, $locationId, $start, $endExclusive];

    if ($searchValue !== '') {
        if ($searchOption === 'customer') {
            $where[] = "$alias.customer_name LIKE ?";
            $params[] = '%' . $searchValue . '%';
        } elseif ($searchOption === 'both') {
            $where[] = "($alias.invoice_number LIKE ? OR $alias.customer_name LIKE ?)";
            $like = '%' . $searchValue . '%';
            $params[] = $like;
            $params[] = $like;
        } else {
            $where[] = "$alias.invoice_number LIKE ?";
            $params[] = '%' . $searchValue . '%';
        }
    }
    if ($paymentStatus !== '') {
        $where[] = "$alias.payment_status = ?";
        $params[] = $paymentStatus;
    }
    return [$where, $params];
}

/* =========================================================
   PERIOD FILTERS + NON-NEGOTIABLE USER / ACTIVE LOCATION SCOPE
========================================================= */
$analysisMode = 'weekly';
$today = new DateTimeImmutable('today');
$defaultYear = (int)$today->format('Y');
$search_option = $_GET['search_option'] ?? 'invoice';
$allowedSearchOptions = ['invoice', 'customer', 'both'];
if (!in_array($search_option, $allowedSearchOptions, true)) $search_option = 'invoice';
$search_value = trim((string)($_GET['search_value'] ?? ''));
if (function_exists('mb_strlen') && mb_strlen($search_value) > 120) $search_value = mb_substr($search_value, 0, 120);
elseif (strlen($search_value) > 120) $search_value = substr($search_value, 0, 120);
$dateError = '';

// Prefer the application's active location session value. Do not accept location_id from GET.
$activeLocationCandidate = $_SESSION['active_location_id'] ?? $_SESSION['location_id'] ??
    ($active_location_id ?? ($user_location_id ?? ($location_id ?? 0)));
$active_location_id = filter_var($activeLocationCandidate, FILTER_VALIDATE_INT);
$active_location_id = ($active_location_id !== false && $active_location_id > 0) ? (int)$active_location_id : 0;
if ($active_location_id <= 0) {
    http_response_code(403);
    exit('No active location selected. Select an active location and try again.');
}
// Confirm the location exists. All sales queries also bind this exact location ID.
$stmt = $pdo->prepare("SELECT id FROM locations WHERE id = ? LIMIT 1");
$stmt->execute([$active_location_id]);
if (!$stmt->fetchColumn()) {
    http_response_code(403);
    exit('Invalid active location.');
}
$location_id = $active_location_id;
$seller_id = (int)$user_id; // informational only; the query scope is mandatory regardless of filters.

$periodParam = (string)($_GET['period_value'] ?? '');
$periodInputType = 'date';
$periodInputLabel = 'Select date';
$periodTitle = 'Daily';
$periodDescription = 'Review the sales activity recorded today or on a selected date.';
$periodDisplay = '';
$previousDisplay = '';
$chartHeading = 'Hourly revenue';

if ($analysisMode === 'daily') {
    $defaultPeriod = $today->format('Y-m-d');
    if (!salesValidDate($periodParam)) $periodParam = $defaultPeriod;
    $currentStartObj = new DateTimeImmutable($periodParam);
    $currentEndObj = $currentStartObj->modify('+1 day');
    $previousStartObj = $currentStartObj->modify('-1 day');
    $previousEndObj = $currentStartObj;
    $periodInputType = 'date'; $periodInputLabel = 'Select day'; $periodTitle = 'Daily';
    $periodDescription = 'Inspect daily revenue, profit, transaction volume and sales activity.';
    $periodDisplay = $currentStartObj->format('D, M j, Y');
    $previousDisplay = $previousStartObj->format('D, M j, Y');
    $chartHeading = 'Hourly revenue';
} elseif ($analysisMode === 'weekly') {
    $defaultPeriod = $today->format('Y-m-d');
    if (!salesValidDate($periodParam)) $periodParam = $defaultPeriod;
    $anchor = new DateTimeImmutable($periodParam);
    $currentStartObj = $anchor->modify('monday this week');
    $currentEndObj = $currentStartObj->modify('+7 days');
    $previousStartObj = $currentStartObj->modify('-7 days');
    $previousEndObj = $currentStartObj;
    $periodInputType = 'date'; $periodInputLabel = 'Choose a date in the week'; $periodTitle = 'Weekly';
    $periodDescription = 'Review a complete Monday-to-Sunday sales week and compare it with the previous week.';
    $periodDisplay = $currentStartObj->format('M j') . ' – ' . $currentEndObj->modify('-1 day')->format('M j, Y');
    $previousDisplay = $previousStartObj->format('M j') . ' – ' . $previousEndObj->modify('-1 day')->format('M j, Y');
    $chartHeading = 'Daily revenue';
} elseif ($analysisMode === 'monthly') {
    $periodInputType = 'month'; $periodInputLabel = 'Select month'; $periodTitle = 'Monthly';
    $periodDescription = 'Track month-to-date performance or inspect any calendar month.';
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodParam)) $periodParam = $today->format('Y-m');
    $currentStartObj = new DateTimeImmutable($periodParam . '-01');
    $currentEndObj = $currentStartObj->modify('+1 month');
    $previousStartObj = $currentStartObj->modify('-1 month');
    $previousEndObj = $currentStartObj;
    $periodDisplay = $currentStartObj->format('F Y');
    $previousDisplay = $previousStartObj->format('F Y');
    $chartHeading = 'Daily revenue';
} else {
    $periodInputType = 'number'; $periodInputLabel = 'Select year'; $periodTitle = 'Annual';
    $periodDescription = 'See the bigger picture: yearly revenue momentum, profitability and product performance.';
    $yearCandidate = filter_var($_GET['period_value'] ?? $defaultYear, FILTER_VALIDATE_INT);
    if ($yearCandidate === false || $yearCandidate < 2000 || $yearCandidate > 2100) $yearCandidate = $defaultYear;
    $periodParam = (string)(int)$yearCandidate;
    $currentStartObj = new DateTimeImmutable(sprintf('%04d-01-01', (int)$periodParam));
    $currentEndObj = $currentStartObj->modify('+1 year');
    $previousStartObj = $currentStartObj->modify('-1 year');
    $previousEndObj = $currentStartObj;
    $periodDisplay = $periodParam;
    $previousDisplay = (string)((int)$periodParam - 1);
    $chartHeading = 'Monthly revenue';
}
$yearStart = $currentStartObj->format('Y-m-d');
$endExclusive = $currentEndObj->format('Y-m-d');
$previousStart = $previousStartObj->format('Y-m-d');
$previousEndExclusive = $previousEndObj->format('Y-m-d');

$payment_status = trim((string)($_GET['payment_status'] ?? ''));
if (function_exists('mb_strlen') && mb_strlen($payment_status) > 60) $payment_status = mb_substr($payment_status, 0, 60);
elseif (strlen($payment_status) > 60) $payment_status = substr($payment_status, 0, 60);
$stmt = $pdo->prepare("SELECT DISTINCT payment_status FROM sales WHERE company_id = ? AND user_id = ? AND location_id = ? AND payment_status IS NOT NULL AND payment_status <> '' ORDER BY payment_status");
$stmt->execute([$company_id, (int)$user_id, $active_location_id]);
$paymentStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
if ($payment_status !== '' && !in_array($payment_status, $paymentStatuses, true)) $payment_status = '';

[$currentWhere, $currentParams] = salesBuildWhere(
    $yearStart, $endExclusive, $company_id, (int)$user_id, $active_location_id,
    $search_option, $search_value, $payment_status
);
[$previousWhere, $previousParams] = salesBuildWhere(
    $previousStart, $previousEndExclusive, $company_id, (int)$user_id, $active_location_id,
    $search_option, $search_value, $payment_status
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

/* Derived business intelligence for the selected annual view. */
$profit_margin = abs($total_period_sales) > 0.000001
    ? ($total_period_profit / $total_period_sales) * 100 : 0.0;
$previous_profit_margin = abs($total_previous_sales) > 0.000001
    ? ($total_previous_profit / $total_previous_sales) * 100 : 0.0;
$margin_change = $profit_margin - $previous_profit_margin;
$revenueInsight = $revenue_change > 0
    ? 'Revenue is ahead of the previous year.'
    : ($revenue_change < 0 ? 'Revenue is below the previous year.' : 'Revenue is level with the previous year.');


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

/* Chart buckets follow the selected analysis period; all values use the same secured filters. */
$chartLabels = []; $chartValues = []; $chartDates = [];
if ($analysisMode === 'daily') {
    $stmt = $pdo->prepare("SELECT HOUR(s.created_at) AS bucket, COALESCE(SUM(s.final_total),0) AS total FROM sales s WHERE $currentWhereSql GROUP BY HOUR(s.created_at) ORDER BY HOUR(s.created_at)");
    $stmt->execute($currentParams); $rawChart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    for ($i=0; $i<24; $i++) { $chartLabels[] = sprintf('%02d:00', $i); $chartDates[] = $currentStartObj->format('M j, Y') . ' · ' . sprintf('%02d:00', $i); $chartValues[] = isset($rawChart[(string)$i]) ? (float)$rawChart[(string)$i] : 0; }
} elseif ($analysisMode === 'weekly' || $analysisMode === 'monthly') {
    $groupExpr = $analysisMode === 'weekly' ? 'DATE(s.created_at)' : 'DAY(s.created_at)';
    $stmt = $pdo->prepare("SELECT $groupExpr AS bucket, COALESCE(SUM(s.final_total),0) AS total FROM sales s WHERE $currentWhereSql GROUP BY $groupExpr ORDER BY $groupExpr");
    $stmt->execute($currentParams); $rawChart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $days = (int)$currentStartObj->diff($currentEndObj)->days;
    for ($i=0; $i<$days; $i++) {
        $day = $currentStartObj->modify('+' . $i . ' days');
        $key = $analysisMode === 'weekly' ? $day->format('Y-m-d') : (string)(int)$day->format('j');
        $chartLabels[] = $analysisMode === 'weekly' ? $day->format('D') : $day->format('j');
        $chartDates[] = $day->format('D, M j, Y');
        $chartValues[] = isset($rawChart[$key]) ? (float)$rawChart[$key] : 0;
    }
} else {
    $stmt = $pdo->prepare("SELECT MONTH(s.created_at) AS bucket, COALESCE(SUM(s.final_total),0) AS total FROM sales s WHERE $currentWhereSql GROUP BY MONTH(s.created_at) ORDER BY MONTH(s.created_at)");
    $stmt->execute($currentParams); $rawChart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    for ($month=1; $month<=12; $month++) { $bucketDate = $currentStartObj->setDate((int)$currentStartObj->format('Y'), $month, 1); $key=(string)$month; $chartLabels[]=$bucketDate->format('M'); $chartDates[]=$bucketDate->format('F Y'); $chartValues[]=isset($rawChart[$key])?(float)$rawChart[$key]:0; }
}
$bestMonthValue = count($chartValues) ? max($chartValues) : 0;
$bestMonthIndex = ($bestMonthValue > 0) ? array_search($bestMonthValue, $chartValues, true) : false;
$bestMonthLabel = ($bestMonthIndex !== false) ? $chartLabels[$bestMonthIndex] : '—';

$bestMonthValue = count($chartValues) ? max($chartValues) : 0;
$bestMonthIndex = ($bestMonthValue > 0) ? array_search($bestMonthValue, $chartValues, true) : false;
$bestMonthLabel = ($bestMonthIndex !== false) ? $chartLabels[$bestMonthIndex] : '—';


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

$activeFilterCount = (int)($search_value !== '') + (int)($payment_status !== '') + (int)($periodParam !== ($analysisMode === 'annual' ? (string)$defaultYear : ($analysisMode === 'monthly' ? $today->format('Y-m') : $today->format('Y-m-d'))));

$chartLabelsJson = json_encode($chartLabels, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$chartValuesJson = json_encode($chartValues, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$chartDatesJson = json_encode($chartDates, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);


?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= salesH($company_name ?? 'Company'); ?> | Weekly Sales Intelligence</title>
<link rel="stylesheet" href="../fontawesome/css/all.css">
<link href="style.css" rel="stylesheet">
<style>
:root{
 --si-bg:#f4f7fb;--si-surface:#fff;--si-surface-soft:#f8faff;--si-line:#e4eaf3;
 --si-ink:#14213d;--si-muted:#69778d;--si-blue:#2864e8;--si-blue-dark:#1749bb;
 --si-teal:#0f9f8f;--si-amber:#d97706;--si-red:#dc4c4c;
 --si-shadow:0 12px 35px rgba(25,48,89,.055);--si-shadow-hover:0 18px 44px rgba(25,48,89,.10)
}
*{box-sizing:border-box}
body{background:radial-gradient(ellipse at 4% 0%,rgba(48,108,235,.09),transparent 31%),radial-gradient(ellipse at 96% 7%,rgba(21,166,157,.07),transparent 28%),var(--si-bg);color:var(--si-ink)}
.dashboard-page{width:100%;max-width:1680px;margin:0 auto;padding-bottom:28px}
.dashboard-intro{position:relative;isolation:isolate;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:20px;padding:27px 30px;border:1px solid rgba(255,255,255,.9);border-radius:24px;background:linear-gradient(112deg,#fff 0%,#fbfdff 54%,#f1f6ff 100%);box-shadow:var(--si-shadow);overflow:hidden}
.dashboard-intro:after{content:"";position:absolute;z-index:-1;right:-42px;top:-105px;width:270px;height:270px;border:1px solid rgba(40,100,232,.10);border-radius:50%;box-shadow:0 0 0 26px rgba(40,100,232,.025),0 0 0 55px rgba(40,100,232,.02)}
.intro-eyebrow{display:flex;align-items:center;gap:8px;margin-bottom:9px;color:var(--si-blue);font-size:10px;font-weight:850;letter-spacing:1.35px;text-transform:uppercase}
.intro-eyebrow i{font-size:12px}
.dashboard-intro h1{margin:0;color:var(--si-ink);font-size:clamp(25px,2.25vw,35px);font-weight:850;letter-spacing:-1.15px;line-height:1.12}
.dashboard-intro p{max-width:650px;margin:9px 0 0;color:var(--si-muted);font-size:13px;line-height:1.7}
.intro-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.dashboard-date,.period-chip{display:inline-flex;align-items:center;gap:9px;padding:12px 15px;border:1px solid var(--si-line);border-radius:13px;background:rgba(255,255,255,.9);color:#40516b;font-size:11px;font-weight:800;white-space:nowrap}
.dashboard-date i{color:var(--si-blue)}
.period-strip{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 2px 15px;color:var(--si-muted);font-size:11px}
.period-strip>span:first-child{display:flex;align-items:center;gap:8px;font-weight:800;letter-spacing:.7px}
.period-strip>span:first-child i{color:var(--si-blue)}
.period-strip strong{color:var(--si-ink)}
.period-meta{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.period-meta .period-chip{padding:8px 11px;border-radius:10px;font-size:10px}
.dashboard-kpi-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:18px}
.dashboard-kpi{position:relative;min-width:0;padding:20px 19px 17px;border:1px solid var(--si-line);border-radius:19px;background:linear-gradient(150deg,#fff 0%,#fff 62%,#f9fbff 100%);box-shadow:var(--si-shadow);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
.dashboard-kpi:hover{transform:translateY(-3px);box-shadow:var(--si-shadow-hover);border-color:#d4e1f8}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.kpi-label{margin:0;color:#7a879b;font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.72px}
.kpi-value{margin:12px 0 0;color:var(--si-ink);font-size:clamp(18px,1.35vw,25px);font-weight:850;letter-spacing:-.75px;line-height:1.25;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}
.kpi-value.product{font-size:14px;line-height:1.45;letter-spacing:-.1px}
.kpi-icon{display:grid;place-items:center;width:40px;height:40px;flex:0 0 40px;border:1px solid #dce8ff;border-radius:13px;background:linear-gradient(145deg,#f3f7ff,#eaf1ff);color:var(--si-blue);font-size:15px}
.dashboard-kpi:nth-child(2) .kpi-icon{background:#eafaf6;border-color:#d1f3e9;color:#0f9f8f}
.dashboard-kpi:nth-child(3) .kpi-icon{background:#f2edff;border-color:#e7ddff;color:#7655d8}
.dashboard-kpi:nth-child(4) .kpi-icon{background:#fff6e8;border-color:#ffebc7;color:#c27a12}
.dashboard-kpi:nth-child(5) .kpi-icon{background:#eaf7ff;border-color:#d5efff;color:#1676a9}
.kpi-bottom{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:17px;padding-top:13px;border-top:1px solid #edf1f7}
.change{display:inline-flex;gap:5px;align-items:center;padding:6px 8px;border-radius:9px;font-size:10px;font-weight:850;white-space:nowrap;font-variant-numeric:tabular-nums}
.change.positive{color:#087b61;background:#eaf9f3}.change.negative{color:#bd3838;background:#fff0f0}
.kpi-caption{color:#8b97a9;font-size:9px;font-weight:700;text-align:right}
.insight-banner{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:15px;margin-bottom:18px;padding:17px 20px;border:1px solid #dbe8ff;border-radius:17px;background:linear-gradient(105deg,#f0f6ff 0%,#fff 62%,#f2fbfa 100%);box-shadow:0 8px 24px rgba(37,99,235,.035)}
.insight-symbol{display:grid;place-items:center;width:42px;height:42px;border:1px solid #dbe8ff;border-radius:14px;background:#fff;color:var(--si-blue);font-size:16px}
.insight-copy strong{display:block;margin-bottom:4px;color:#1b2c4a;font-size:12px;font-weight:850}
.insight-copy p{margin:0;color:#69778d;font-size:11px;line-height:1.65}
.insight-metric{text-align:right;white-space:nowrap}
.insight-metric strong{display:block;color:var(--si-blue);font-size:19px;font-weight:850;letter-spacing:-.5px}
.insight-metric span{color:#7d8ba0;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.55px}
.dashboard-card{margin-bottom:18px;border:1px solid var(--si-line);border-radius:21px;background:rgba(255,255,255,.97);box-shadow:var(--si-shadow);overflow:hidden}
.analytics-card{padding:24px}
.card-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
.card-heading h2,.sales-title h2{margin:0;color:var(--si-ink);font-size:17px;font-weight:850;letter-spacing:-.35px}
.card-heading p,.sales-title p{margin:6px 0 0;color:var(--si-muted);font-size:11px;line-height:1.6}
.live-pill{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border:1px solid #ccefe3;border-radius:30px;background:#f0fbf6;color:#087b61;font-size:9px;font-weight:850;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
.live-pill span{width:6px;height:6px;border-radius:50%;background:#0fa779;box-shadow:0 0 0 4px rgba(15,167,121,.10)}
.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:17px}
.summary-item{padding:13px 15px;border:1px solid #e9eef6;border-radius:13px;background:linear-gradient(140deg,#fbfcff,#fff)}
.summary-label{display:block;margin-bottom:6px;color:#8995a8;font-size:9px;font-weight:850;text-transform:uppercase;letter-spacing:.55px}
.summary-value{color:#253653;font-size:14px;font-weight:850;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
.chart-container{height:335px;position:relative}
#salesChart{width:100%!important;height:100%!important}
.sales-header{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;padding:23px 24px;border-bottom:1px solid #edf1f7;background:linear-gradient(120deg,#fff,#fbfcff)}
.sales-title{min-width:190px;padding-top:2px}
.sales-filters{display:grid;grid-template-columns:repeat(3,minmax(145px,1fr));gap:9px;width:min(100%,800px)}
.sales-filter-control{width:100%;min-width:0;height:42px;padding:0 12px;border:1px solid #dfe6f0;border-radius:11px;background:#fff;color:#34445d;font-size:11px;font-weight:650;outline:none;transition:border-color .18s,box-shadow .18s}
.sales-filter-control:hover{border-color:#c5d4eb}
.sales-filter-control:focus{border-color:#8ab1ff;box-shadow:0 0 0 3px rgba(40,100,232,.10)}
.filter-actions{display:flex;gap:8px;grid-column:1/-1;align-items:center;flex-wrap:wrap}
.filter-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:41px;padding:0 15px;border:1px solid var(--si-blue);border-radius:11px;background:linear-gradient(135deg,#3476f6,#245ce0);color:#fff;font-size:11px;font-weight:850;cursor:pointer;transition:transform .15s,box-shadow .15s}
.filter-btn:hover{transform:translateY(-1px);box-shadow:0 7px 16px rgba(40,100,232,.18)}
.filter-btn.secondary{border-color:#dfe6f0;background:#fff;color:#52617a}
.filter-btn.secondary:hover{background:#f6f8fc;box-shadow:none}
.filter-btn.tertiary{border-color:#dbe7fb;background:#f2f7ff;color:#2458b8}
.filter-btn.tertiary:hover{background:#e8f1ff;box-shadow:none}
.filter-status{padding:12px 23px;border-bottom:1px solid #edf1f7;background:#fcfdff;color:#758198;font-size:10px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.filter-status strong{color:#34445d}
.filter-status-left{display:flex;align-items:center;gap:9px}
.count-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#10a77a}
.sales-table-wrapper{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.sales-table{width:100%;min-width:1250px;border-collapse:separate;border-spacing:0}
.sales-table thead th{position:sticky;top:0;z-index:1;padding:14px 13px;border-bottom:1px solid #e5ebf4;background:#f7f9fd;color:#758198;font-size:9px;font-weight:850;text-transform:uppercase;letter-spacing:.55px;text-align:left;white-space:nowrap}
.sales-table thead th:first-child,.sales-table tbody td:first-child{padding-left:23px}
.sales-table tbody td{padding:14px 13px;border-bottom:1px solid #f0f3f8;color:#56647a;font-size:11px;vertical-align:middle;white-space:nowrap}
.sales-table tbody tr{transition:background .15s}
.sales-table tbody tr:hover{background:#f6f9ff}.sales-table tbody tr:last-child td{border-bottom:0}
.invoice-number{color:#2864e8;font-weight:850}.customer-name,.amount-main{color:#233451;font-weight:800}.amount-success{color:#087b61;font-weight:850}.amount-warning{color:#b66b08;font-weight:850}
.payment-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border:1px solid #e5eaf2;border-radius:30px;background:#f7f9fc;color:#68758a;font-size:9px;font-weight:850;text-transform:capitalize}
.payment-badge:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
.invoice-view-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 10px;border:1px solid #dce8ff;border-radius:9px;background:#f0f5ff;color:#2864e8;font-size:10px;font-weight:850;text-decoration:none;transition:all .15s}
.invoice-view-btn:hover{border-color:#2864e8;background:#2864e8;color:#fff}
.sales-empty{padding:58px 20px!important;text-align:center;color:#98a4b7!important;white-space:normal!important;line-height:1.8}
.sales-empty strong{color:#34445d}
.notice{margin-bottom:15px;padding:12px 15px;border:1px solid #fed7aa;border-radius:12px;background:#fff7ed;color:#9a3412;font-size:12px}
.table-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:13px 22px;border-top:1px solid #edf1f7;background:#fcfdff;color:#8390a4;font-size:10px}
.table-footer strong{color:#34445d}
@media(max-width:1450px){.dashboard-kpi-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.sales-header{flex-direction:column}.sales-filters{width:100%;max-width:none}.sales-title{width:100%}}
@media(max-width:1000px){.dashboard-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sales-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){
 .dashboard-page{padding-bottom:12px}.dashboard-intro{align-items:flex-start;flex-direction:column;padding:21px 18px;border-radius:19px;gap:17px}
 .dashboard-intro:after{right:-130px;top:-125px}.dashboard-intro h1{font-size:27px}.dashboard-intro p{font-size:12px}
 .intro-actions{width:100%}.dashboard-date{width:100%;justify-content:center}
 .period-strip{align-items:flex-start;flex-direction:column}.period-meta{width:100%;justify-content:space-between}
 .dashboard-kpi-grid{grid-template-columns:1fr;gap:10px}.dashboard-kpi{padding:17px;border-radius:16px}
 .kpi-value{font-size:23px}.kpi-value.product{font-size:14px}
 .insight-banner{grid-template-columns:auto 1fr;gap:12px;padding:15px}.insight-metric{grid-column:2;text-align:left}
 .analytics-card{padding:18px 13px}.card-heading{flex-direction:column}.summary-grid{grid-template-columns:1fr 1fr;gap:8px}
 .summary-item{padding:12px}.summary-value{font-size:12px}.chart-container{height:270px}
 .sales-header{padding:18px 13px}.sales-filters{grid-template-columns:1fr}.filter-actions{flex-direction:column;align-items:stretch}.filter-btn{width:100%}
 .filter-status{padding:11px 14px}.sales-table thead th:first-child,.sales-table tbody td:first-child{padding-left:14px}
 .table-footer{padding:12px 14px}
}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important;scroll-behavior:auto!important}}
@media print{body{background:#fff!important}.dashboard-intro,.dashboard-kpi,.dashboard-card{box-shadow:none!important}.sales-filters,.intro-actions,.filter-status,.table-footer,.live-pill{display:none!important}.dashboard-page{max-width:none}.sales-table{min-width:0;font-size:8px}.sales-table th,.sales-table td{padding:6px!important}}
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
                            <div class="intro-eyebrow"><i class="fa-solid fa-sparkles"></i> BUSINESS PERFORMANCE / <?= salesH(strtoupper($periodTitle)); ?> VIEW</div>
                            <h1><?= salesH($periodTitle); ?> Sales Intelligence</h1>
                            <p><?= salesH($periodDescription); ?></p>
                        </div>
                        <div class="intro-actions">
                            <div class="dashboard-date"><i class="fa-solid fa-calendar-days"></i><?= salesH($periodTitle); ?> Sales Report</div>
                        </div>
                    </section>

                    <?php if ($dateError !== ''): ?><div class="notice"><?= salesH($dateError); ?></div><?php endif; ?>

                    <div class="period-strip">
                        <span><i class="fa-solid fa-filter"></i> ACTIVE REPORT PERIOD</span>
                        <div class="period-meta">
                            <span class="period-chip"><i class="fa-regular fa-calendar"></i> <?= salesH($periodDisplay); ?></span>
                            <span class="period-chip"><i class="fa-solid fa-arrow-right-arrow-left"></i> Compared with <?= salesH($previousDisplay); ?></span>
                        </div>
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

                    <section class="insight-banner" aria-label="Annual business insight">
                        <div class="insight-symbol"><i class="fa-solid fa-lightbulb"></i></div>
                        <div class="insight-copy">
                            <strong><?= salesH($revenueInsight); ?></strong>
                            <p>
                                <?= $invoice_count_period > 0
                                    ? 'You recorded ' . number_format($invoice_count_period) . ' matching transaction(s), with an average transaction value of ₦' . number_format($average_sale_period, 2) . '.'
                                    : 'No transactions matched the selected year and filters. Try changing the year or clearing some filters.'; ?>
                                <?php if ($bestMonthIndex !== false): ?> Peak revenue bucket: <b><?= salesH($bestMonthLabel); ?></b> at ₦<?= number_format($bestMonthValue, 2); ?>.<?php endif; ?>
                            </p>
                        </div>
                        <div class="insight-metric">
                            <strong><?= number_format($profit_margin, 1); ?>%</strong>
                            <span>Profit margin</span>
                        </div>
                    </section>

                    <section class="dashboard-card analytics-card">
                        <div class="card-heading">
                            <div><h2>Revenue Analytics</h2><p><?= salesH($chartHeading); ?> for the selected period, using the active filters.</p></div>
                            <div class="live-pill"><span></span> Filter-synced report</div>
                        </div>
                        <div class="summary-grid">
                            <div class="summary-item"><span class="summary-label">Filtered Revenue</span><span class="summary-value">₦<?= number_format($total_period_sales,2); ?></span></div>
                            <div class="summary-item"><span class="summary-label">Matching Transactions</span><span class="summary-value"><?= number_format($invoice_count_period); ?></span></div>
                            <div class="summary-item"><span class="summary-label">Filtered Profit</span><span class="summary-value">₦<?= number_format($total_period_profit,2); ?></span></div>
                            <div class="summary-item"><span class="summary-label">Profit Margin</span><span class="summary-value"><?= number_format($profit_margin,1); ?>%</span></div>
                        </div>
                        <div class="chart-container"><canvas id="salesChart"></canvas></div>
                    </section>

                    <section class="dashboard-card">
                        <div class="sales-header">
                            <div class="sales-title"><h2>Sales Report</h2><p><?= salesH($periodTitle); ?> filters update every KPI, comparison, chart and row together.</p></div>
                            <form id="salesFilterForm" class="sales-filters" method="get" action="">
                                <select name="search_option" id="search_option" class="sales-filter-control" aria-label="Search field">
                                    <option value="invoice" <?= $search_option === 'invoice'?'selected':''; ?>>Invoice number</option>
                                    <option value="customer" <?= $search_option === 'customer'?'selected':''; ?>>Customer name</option>
                                    <option value="both" <?= $search_option === 'both'?'selected':''; ?>>Invoice or customer</option>
                                </select>
                                <input type="search" name="search_value" id="search_value" class="sales-filter-control" placeholder="Search invoice or customer..." value="<?= salesH($search_value); ?>" maxlength="120">
                                <input type="<?= salesH($periodInputType); ?>" name="period_value" id="period_value" class="sales-filter-control" value="<?= salesH($periodParam); ?>" <?= $analysisMode === 'annual' ? 'min="2000" max="2100" step="1"' : ''; ?> aria-label="<?= salesH($periodInputLabel); ?>" required>
                                <select name="payment_status" id="payment_status" class="sales-filter-control" aria-label="Filter by payment status">
                                    <option value="">All payment statuses</option>
                                    <?php foreach ($paymentStatuses as $status): ?>
                                    <option value="<?= salesH($status); ?>" <?= $payment_status === $status?'selected':''; ?>><?= salesH(ucfirst((string)$status)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="filter-actions">
                                    <button type="submit" id="apply_sales_filters" class="filter-btn"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                                    <button type="button" id="reset_sales_filters" class="filter-btn secondary"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                                    <button type="button" id="export_sales_csv" class="filter-btn tertiary"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                                    <button type="button" id="print_sales_report" class="filter-btn tertiary"><i class="fa-solid fa-print"></i> Print Report</button>
                                </div>
                            </form>
                        </div>
                        <div class="filter-status">
                            <span class="filter-status-left"><span class="count-dot"></span><span><strong><?= number_format(count($salesRows)); ?></strong> matching transactions shown</span></span>
                            <span><?= $activeFilterCount ? '<strong>'.(int)$activeFilterCount.' filter group(s) active</strong>' : 'Active user/location scope enforced'; ?></span>
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
                                    <tr><td colspan="14" class="sales-empty"><i class="fa-solid fa-magnifying-glass" style="display:block;font-size:24px;margin-bottom:12px;color:#2563eb"></i><strong>No matching sales found</strong><br><span>Try another year or remove one or more filters.</span></td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <span>Report scope: <strong><?= salesH($periodDisplay); ?></strong> · Active location: <strong><?= (int)$active_location_id; ?></strong> · Currency: <strong>NGN (₦)</strong></span>
                            <span><strong><?= number_format(count($salesRows)); ?></strong> record(s) in current report</span>
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
                                return dates[items[0].dataIndex] || '';
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
    const periodInput = document.getElementById('period_value');
    const reset = document.getElementById('reset_sales_filters');
    const search = document.getElementById('search_value');

    if (form) {
        form.addEventListener('submit', function(event){
            if (periodInput && periodInput.type === 'number' && (!periodInput.value || Number(periodInput.value) < 2000 || Number(periodInput.value) > 2100 || !Number.isInteger(Number(periodInput.value)))) {
                event.preventDefault(); alert('Please enter a valid year between 2000 and 2100.'); periodInput.focus();
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

    /* Vanilla JS only: leaves Alpine.js state, directives and bundle untouched. */
    const exportButton = document.getElementById('export_sales_csv');
    const printButton = document.getElementById('print_sales_report');

    if (exportButton) {
        exportButton.addEventListener('click', function(){
            const table = document.querySelector('.sales-table');
            if (!table) return;
            const rows = Array.from(table.querySelectorAll('tr'));
            const csvRows = rows.map(function(row){
                const cells = Array.from(row.querySelectorAll('th,td'));
                return cells.slice(0, -1).map(function(cell){
                    const value = (cell.innerText || cell.textContent || '').replace(/\s+/g, ' ').trim();
                    return '"' + value.replace(/"/g, '""') + '"';
                }).join(',');
            }).filter(function(row){ return row.length > 0; });
            const blob = new Blob(['\uFEFF' + csvRows.join('\r\n')], {type:'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'weekly-sales-report-' + (periodInput ? periodInput.value : 'report') + '.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        });
    }
    if (printButton) printButton.addEventListener('click', function(){ window.print(); });
});
</script>
</body>
</html>
