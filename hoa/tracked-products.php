<?php
declare(strict_types=1);

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');

date_default_timezone_set('Africa/Lagos');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* -----------------------------------------------------------
   AUTHENTICATION / COMPANY SCOPE
----------------------------------------------------------- */
$userId = (int)($_SESSION['user_id'] ?? 0);
$companyId = (int)($_SESSION['company_id'] ?? 0);

if ($companyId <= 0 && $userId > 0) {
    $companyStmt = $pdo->prepare('SELECT company_id FROM users WHERE id = ? LIMIT 1');
    $companyStmt->execute([$userId]);
    $companyId = (int)$companyStmt->fetchColumn();
}

if ($userId <= 0 || $companyId <= 0) {
    http_response_code(403);
    exit('Your session could not be verified. Please sign in again.');
}

function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function trackingStatusLabel(string $status): string
{
    return match ($status) {
        'in_stock' => 'In Stock',
        'reserved' => 'Reserved',
        'sold' => 'Sold',
        'returned_pending_inspection' => 'Returned · Inspection',
        'under_repair' => 'Under Repair',
        'damaged' => 'Damaged',
        'written_off' => 'Written Off',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function trackingStatusClass(string $status): string
{
    return match ($status) {
        'in_stock' => 'status-green',
        'sold' => 'status-blue',
        'reserved' => 'status-amber',
        'returned_pending_inspection', 'under_repair' => 'status-violet',
        'damaged', 'written_off' => 'status-red',
        default => 'status-neutral',
    };
}

function trackingDate($value, string $format = 'd M Y, h:i A'): string
{
    if (!$value || $value === '0') return '—';
    $timestamp = strtotime((string)$value);
    return $timestamp ? date($format, $timestamp) : h($value);
}

/* -----------------------------------------------------------
   FILTERS / PAGINATION
----------------------------------------------------------- */
$search = trim((string)($_GET['q'] ?? ''));
$typeFilter = (string)($_GET['type'] ?? 'all');
$statusFilter = (string)($_GET['status'] ?? 'all');
$productFilter = (int)($_GET['product_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$allowedTypes = ['all', 'imei', 'serial', 'vin'];
$allowedStatuses = ['all', 'in_stock', 'reserved', 'sold', 'returned_pending_inspection', 'under_repair', 'damaged', 'written_off'];
if (!in_array($typeFilter, $allowedTypes, true)) $typeFilter = 'all';
if (!in_array($statusFilter, $allowedStatuses, true)) $statusFilter = 'all';

$where = ['ps.company_id = :company_id', "p.identifier_tracking IN ('imei','serial','vin')"];
$params = [':company_id' => $companyId];

if ($search !== '') {
    $where[] = "(COALESCE(NULLIF(ps.identifier_value,''), NULLIF(ps.imei,'')) LIKE :search
        OR p.name LIKE :search_product
        OR p.barcode LIKE :search_barcode
        OR s.invoice_number LIKE :search_invoice
        OR s.customer_name LIKE :search_customer
        OR s.customer_phone LIKE :search_phone
        OR s.customer_email LIKE :search_email)";
    $like = '%' . $search . '%';
    $params[':search'] = $like;
    $params[':search_product'] = $like;
    $params[':search_barcode'] = $like;
    $params[':search_invoice'] = $like;
    $params[':search_customer'] = $like;
    $params[':search_phone'] = $like;
    $params[':search_email'] = $like;
}

if ($typeFilter !== 'all') {
    $where[] = 'ps.identifier_type = :identifier_type';
    $params[':identifier_type'] = $typeFilter;
}
if ($statusFilter !== 'all') {
    $where[] = 'ps.status = :serial_status';
    $params[':serial_status'] = $statusFilter;
}
if ($productFilter > 0) {
    $where[] = 'ps.product_id = :product_id';
    $params[':product_id'] = $productFilter;
}
$whereSql = implode(' AND ', $where);

/* -----------------------------------------------------------
   INTELLIGENCE METRICS
----------------------------------------------------------- */
$metricSql = "SELECT
    COUNT(*) AS total_tracked,
    COALESCE(SUM(CASE WHEN ps.status = 'in_stock' THEN 1 ELSE 0 END), 0) AS in_stock,
    COALESCE(SUM(CASE WHEN ps.status = 'sold' THEN 1 ELSE 0 END), 0) AS sold,
    COALESCE(SUM(CASE WHEN ps.status IN ('reserved','returned_pending_inspection','under_repair','damaged','written_off') THEN 1 ELSE 0 END), 0) AS attention,
    COALESCE(SUM(CASE WHEN ps.identifier_type = 'imei' THEN 1 ELSE 0 END), 0) AS imei_count,
    COALESCE(SUM(CASE WHEN ps.identifier_type = 'serial' THEN 1 ELSE 0 END), 0) AS serial_count,
    COALESCE(SUM(CASE WHEN ps.identifier_type = 'vin' THEN 1 ELSE 0 END), 0) AS vin_count
    FROM product_serials ps
    INNER JOIN products p ON p.id = ps.product_id AND p.company_id = ps.company_id
    LEFT JOIN sale_items si ON si.id = ps.current_sale_item_id AND si.product_id = ps.product_id
    LEFT JOIN sales s ON s.id = si.sale_id AND s.company_id = ps.company_id
    WHERE {$whereSql}";
$metricStmt = $pdo->prepare($metricSql);
$metricStmt->execute($params);
$metrics = $metricStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* -----------------------------------------------------------
   PRODUCT FILTER OPTIONS
----------------------------------------------------------- */
$productStmt = $pdo->prepare("SELECT id, name, identifier_tracking
    FROM products
    WHERE company_id = ? AND identifier_tracking IN ('imei','serial','vin')
    ORDER BY name ASC");
$productStmt->execute([$companyId]);
$trackedProducts = $productStmt->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------------------------------------
   TOTAL MATCHES + TRACKED IDENTIFIER LIST
   Most recent sale snapshot is used as a fallback when the
   current serial row has no current_sale_item_id.
----------------------------------------------------------- */
$countSql = "SELECT COUNT(*)
    FROM product_serials ps
    INNER JOIN products p ON p.id = ps.product_id AND p.company_id = ps.company_id
    LEFT JOIN sale_items si ON si.id = ps.current_sale_item_id AND si.product_id = ps.product_id
    LEFT JOIN sales s ON s.id = si.sale_id AND s.company_id = ps.company_id
    WHERE {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$listSql = "SELECT
    ps.id AS tracking_id,
    ps.product_id,
    ps.identifier_type,
    COALESCE(NULLIF(ps.identifier_value,''), NULLIF(ps.imei,'')) AS identifier_value,
    ps.status,
    ps.location_id,
    ps.stock_id,
    ps.received_at,
    ps.updated_at,
    p.name AS product_name,
    p.barcode,
    p.identifier_tracking,
    l.name AS location_name,
    COALESCE(si.sale_id, last_snapshot.sale_id) AS linked_sale_id,
    COALESCE(s.invoice_number, last_sale.invoice_number) AS invoice_number,
    COALESCE(s.customer_name, last_sale.customer_name) AS customer_name,
    COALESCE(s.customer_phone, last_sale.customer_phone) AS customer_phone,
    COALESCE(s.customer_email, last_sale.customer_email) AS customer_email,
    COALESCE(s.created_at, last_sale.created_at) AS sale_date,
    COALESCE(s.final_total, last_sale.final_total) AS sale_total
    FROM product_serials ps
    INNER JOIN products p ON p.id = ps.product_id AND p.company_id = ps.company_id
    LEFT JOIN locations l ON l.id = ps.location_id AND l.company_id = ps.company_id
    LEFT JOIN sale_items si ON si.id = ps.current_sale_item_id AND si.product_id = ps.product_id
    LEFT JOIN sales s ON s.id = si.sale_id AND s.company_id = ps.company_id
    LEFT JOIN (
        SELECT sis1.serial_id, sis1.sale_id
        FROM sale_item_serials sis1
        INNER JOIN (
            SELECT serial_id, MAX(id) AS max_id
            FROM sale_item_serials
            GROUP BY serial_id
        ) latest ON latest.max_id = sis1.id
    ) last_snapshot ON last_snapshot.serial_id = ps.id
    LEFT JOIN sales last_sale ON last_sale.id = last_snapshot.sale_id AND last_sale.company_id = ps.company_id
    WHERE {$whereSql}
    ORDER BY CASE WHEN ps.status = 'sold' THEN 0 WHEN ps.status = 'in_stock' THEN 1 ELSE 2 END,
             ps.updated_at DESC, ps.id DESC
    LIMIT :limit OFFSET :offset";
$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$records = $listStmt->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------------------------------------
   SELECTED IDENTIFIER DETAIL + EVENT TIMELINE
----------------------------------------------------------- */
$selectedId = (int)($_GET['view'] ?? 0);
$selectedRecord = null;
$events = [];
if ($selectedId > 0) {
    $detailStmt = $pdo->prepare("SELECT
        ps.*, COALESCE(NULLIF(ps.identifier_value,''), NULLIF(ps.imei,'')) AS display_identifier,
        p.name AS product_name, p.barcode,
        l.name AS location_name,
        si.sale_id AS current_sale_id,
        s.invoice_number, s.customer_name, s.customer_phone, s.customer_email,
        s.created_at AS sold_at, s.final_total, s.amount_paid, s.balance, s.payment_status
        FROM product_serials ps
        INNER JOIN products p ON p.id = ps.product_id AND p.company_id = ps.company_id
        LEFT JOIN locations l ON l.id = ps.location_id AND l.company_id = ps.company_id
        LEFT JOIN sale_items si ON si.id = ps.current_sale_item_id AND si.product_id = ps.product_id
        LEFT JOIN (
            SELECT sis1.serial_id, sis1.sale_id
            FROM sale_item_serials sis1
            INNER JOIN (
                SELECT serial_id, MAX(id) AS max_id
                FROM sale_item_serials
                GROUP BY serial_id
            ) latest ON latest.max_id = sis1.id
        ) detail_snapshot ON detail_snapshot.serial_id = ps.id
        LEFT JOIN sales s ON s.id = COALESCE(si.sale_id, detail_snapshot.sale_id) AND s.company_id = ps.company_id
        WHERE ps.id = ? AND ps.company_id = ? AND p.identifier_tracking IN ('imei','serial','vin')
        LIMIT 1");
    $detailStmt->execute([$selectedId, $companyId]);
    $selectedRecord = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($selectedRecord) {
        $eventStmt = $pdo->prepare("SELECT e.*, fl.name AS from_location_name, tl.name AS to_location_name,
            u.username AS performed_by_name, u.email AS performed_by_email
            FROM product_serial_events e
            LEFT JOIN locations fl ON fl.id = e.from_location_id AND fl.company_id = e.company_id
            LEFT JOIN locations tl ON tl.id = e.to_location_id AND tl.company_id = e.company_id
            LEFT JOIN users u ON u.id = e.performed_by AND u.company_id = e.company_id
            WHERE e.serial_id = ? AND e.company_id = ?
            ORDER BY e.created_at DESC, e.id DESC");
        $eventStmt->execute([$selectedId, $companyId]);
        $events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

        // Snapshot history is authoritative for previous sales, including a
        // unit that was returned and later sold again.
        $historyStmt = $pdo->prepare("SELECT sis.identifier_type,
            COALESCE(NULLIF(sis.identifier_value,''), NULLIF(sis.imei,'')) AS identifier_value,
            sis.created_at, s.id AS sale_id, s.invoice_number, s.customer_name,
            s.customer_phone, s.customer_email, s.created_at AS sale_date,
            s.final_total, s.payment_status
            FROM sale_item_serials sis
            INNER JOIN sales s ON s.id = sis.sale_id AND s.company_id = ?
            WHERE sis.serial_id = ?
            ORDER BY sis.id DESC");
        $historyStmt->execute([$companyId, $selectedId]);
        $saleHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $saleHistory = [];
    }
}

function queryUrl(array $changes = []): string
{
    $query = $_GET;
    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') unset($query[$key]);
        else $query[$key] = $value;
    }
    return '?' . http_build_query($query);
}

$pageTitle = isset($app_data) ? (string)$app_data : 'Product Tracking Intelligence';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo h($pageTitle); ?> | Product Tracking Intelligence</title>
    <link rel="icon" href="src/images/icons/favicon.png">
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <style>
        /* This page deliberately stays light while leaving shared sidebar/header intact. */
        .tracking-page{--ink:#172033;--muted:#718096;--line:#e7ecf3;--surface:#fff;--soft:#f5f8fc;--blue:#2563eb;--blue-soft:#eff6ff;color:var(--ink);background:#f5f7fb;min-height:calc(100vh - 1px)}
        .tracking-page *{box-sizing:border-box}
        .tracking-shell{max-width:1600px;margin:0 auto;padding:26px 24px 42px}
        .tracking-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px}
        .tracking-eyebrow{display:inline-flex;align-items:center;gap:8px;color:#2563eb;font-size:11px;font-weight:800;letter-spacing:.13em;text-transform:uppercase}
        .tracking-title{margin:8px 0 7px;font-size:clamp(25px,3vw,34px);line-height:1.15;font-weight:800;letter-spacing:-.035em;color:#172033}
        .tracking-subtitle{margin:0;color:#718096;font-size:14px;line-height:1.65;max-width:720px}
        .tracking-live{display:inline-flex;align-items:center;gap:8px;white-space:nowrap;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;padding:10px 13px;border-radius:999px;font-size:12px;font-weight:700}
        .tracking-live:before{content:'';width:7px;height:7px;background:#3b82f6;border-radius:50%;box-shadow:0 0 0 4px #dbeafe}
        .metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:22px}
        .metric-card{background:#fff;border:1px solid var(--line);border-radius:17px;padding:19px;box-shadow:0 4px 16px rgba(20,39,75,.035);min-width:0}
        .metric-top{display:flex;align-items:center;justify-content:space-between;gap:10px}
        .metric-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#eff6ff;color:#2563eb;font-size:17px}
        .metric-label{font-size:12px;color:#718096;font-weight:650}
        .metric-value{font-size:27px;line-height:1.2;margin-top:13px;font-weight:800;color:#172033;letter-spacing:-.03em}
        .metric-note{font-size:11px;color:#94a3b8;margin-top:5px}
        .tracking-panel{background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 5px 20px rgba(20,39,75,.035);overflow:hidden;margin-bottom:22px}
        .panel-heading{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:15px}
        .panel-heading h2{font-size:16px;font-weight:800;color:#172033;margin:0}
        .panel-heading p{font-size:12px;color:#8290a5;margin:5px 0 0}
        .search-panel{padding:20px 22px 18px;background:linear-gradient(135deg,#fff 0%,#fbfdff 100%)}
        .search-label{display:block;font-size:12px;font-weight:750;color:#334155;margin-bottom:9px}
        .tracking-search-row{display:flex;gap:10px;align-items:stretch}
        .tracking-search{position:relative;flex:1;min-width:0}
        .tracking-search i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#2563eb;font-size:15px;pointer-events:none}
        .tracking-search input{width:100%;height:49px;border:1px solid #d9e2ef;border-radius:12px;padding:0 15px 0 44px;color:#172033;background:#fff;font-size:14px;outline:none;transition:.2s}
        .tracking-search input:focus,.tracking-select:focus{border-color:#7aa7f9;box-shadow:0 0 0 4px rgba(37,99,235,.09)}
        .tracking-btn{height:49px;border:0;border-radius:12px;background:#1e293b;color:#fff;padding:0 21px;display:inline-flex;align-items:center;justify-content:center;gap:9px;font-size:13px;font-weight:750;cursor:pointer;transition:.2s;white-space:nowrap}
        .tracking-btn:hover{background:#2563eb;transform:translateY(-1px)}
        .tracking-btn.secondary{height:42px;background:#fff;border:1px solid #dbe3ed;color:#475569;padding:0 14px}
        .tracking-btn.secondary:hover{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
        .filter-grid{display:grid;grid-template-columns:1.1fr 1.1fr 1.8fr auto;gap:10px;margin-top:15px;align-items:end}
        .filter-field label{display:block;font-size:11px;font-weight:750;color:#64748b;margin-bottom:6px}
        .tracking-select{width:100%;height:41px;border:1px solid #dbe3ed;border-radius:10px;background:#fff;padding:0 11px;color:#334155;font-size:12px;outline:none}
        .filter-actions{display:flex;gap:8px}
        .search-hint{margin-top:11px;color:#94a3b8;font-size:11px;line-height:1.5}
        .result-toolbar{padding:16px 21px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid var(--line)}
        .result-count{font-size:12px;color:#64748b}.result-count strong{color:#172033}
        .type-pills{display:flex;gap:7px;flex-wrap:wrap}.type-pill{border-radius:999px;padding:6px 10px;font-size:10px;font-weight:750;background:#f1f5f9;color:#64748b}
        .table-wrap{width:100%;overflow-x:auto}
        .tracking-table{width:100%;border-collapse:separate;border-spacing:0;min-width:920px}
        .tracking-table th{padding:13px 16px;text-align:left;background:#f8fafc;color:#8290a5;font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;border-bottom:1px solid var(--line);white-space:nowrap}
        .tracking-table td{padding:15px 16px;border-bottom:1px solid #eef2f7;vertical-align:middle;font-size:12px;color:#475569}
        .tracking-table tbody tr:last-child td{border-bottom:0}.tracking-table tbody tr:hover{background:#fbfdff}
        .identifier-main{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#172033;font-weight:800;font-size:12px;letter-spacing:.015em;overflow-wrap:anywhere}
        .identifier-sub{color:#94a3b8;font-size:10px;margin-top:5px}
        .product-main{font-weight:750;color:#263449;font-size:12px}.product-sub{color:#94a3b8;font-size:10px;margin-top:4px}
        .type-tag,.status-tag{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border-radius:999px;font-size:10px;font-weight:800;white-space:nowrap}
        .type-tag{background:#f1f5f9;color:#475569}.type-tag.imei{background:#eef2ff;color:#4f46e5}.type-tag.serial{background:#f0fdfa;color:#0f766e}.type-tag.vin{background:#fff7ed;color:#c2410c}
        .status-green{background:#ecfdf3;color:#15803d}.status-blue{background:#eff6ff;color:#1d4ed8}.status-amber{background:#fffbeb;color:#b45309}.status-violet{background:#f5f3ff;color:#6d28d9}.status-red{background:#fef2f2;color:#b91c1c}.status-neutral{background:#f1f5f9;color:#64748b}
        .sale-main{font-weight:750;color:#334155}.sale-sub{font-size:10px;color:#94a3b8;margin-top:4px}
        .view-link{display:inline-flex;align-items:center;gap:7px;color:#2563eb;font-size:11px;font-weight:800;text-decoration:none;padding:8px 10px;border-radius:9px;background:#eff6ff;white-space:nowrap}.view-link:hover{background:#dbeafe}
        .empty-state{text-align:center;padding:54px 20px;color:#94a3b8}.empty-state i{font-size:30px;color:#cbd5e1;margin-bottom:12px}.empty-state h3{font-size:15px;color:#334155;margin:0 0 6px}.empty-state p{font-size:12px;margin:0}
        .pagination{padding:15px 20px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.pagination-info{font-size:11px;color:#94a3b8}.pagination-links{display:flex;gap:6px;align-items:center}.page-link{min-width:34px;height:34px;padding:0 10px;border:1px solid #e2e8f0;border-radius:9px;display:grid;place-items:center;color:#64748b;text-decoration:none;font-size:11px;font-weight:750;background:#fff}.page-link.active{background:#2563eb;color:#fff;border-color:#2563eb}.page-link.disabled{opacity:.4;pointer-events:none}
        .detail-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.48);z-index:100000;display:flex;align-items:center;justify-content:center;padding:18px;overflow-y:auto}
        .detail-modal{width:min(900px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:20px;box-shadow:0 25px 80px rgba(15,23,42,.25)}
        .detail-head{padding:22px 24px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:14px;align-items:flex-start}.detail-head h2{font-size:19px;font-weight:850;color:#172033;margin:0 0 7px}.detail-head p{font-size:12px;color:#8290a5;margin:0}.detail-close{width:36px;height:36px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;display:grid;place-items:center;text-decoration:none;flex-shrink:0}
        .detail-body{padding:22px 24px}.detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.detail-cell{padding:13px;border:1px solid #edf1f6;border-radius:12px;background:#fbfdff;min-width:0}.detail-cell span{display:block;font-size:10px;color:#94a3b8;font-weight:750;text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px}.detail-cell strong{display:block;font-size:12px;color:#334155;overflow-wrap:anywhere}.detail-section-title{font-size:13px;font-weight:850;color:#172033;margin:24px 0 12px}.timeline{border-left:2px solid #e2e8f0;margin-left:8px;padding-left:19px}.timeline-item{position:relative;padding:0 0 18px}.timeline-item:before{content:'';position:absolute;width:9px;height:9px;border-radius:50%;background:#3b82f6;left:-25px;top:4px;box-shadow:0 0 0 4px #eff6ff}.timeline-item strong{font-size:12px;color:#334155}.timeline-item p{font-size:11px;color:#8290a5;margin:4px 0 0;line-height:1.5}.history-table{width:100%;border-collapse:collapse;min-width:600px}.history-table th,.history-table td{text-align:left;padding:10px;border-bottom:1px solid #edf1f6;font-size:11px}.history-table th{color:#94a3b8;font-size:9px;text-transform:uppercase;letter-spacing:.07em}.history-wrap{overflow-x:auto;border:1px solid #edf1f6;border-radius:12px}
        @media(max-width:1050px){.metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:640px){.tracking-shell{padding:18px 12px 30px}.tracking-hero{display:block}.tracking-live{margin-top:14px}.metric-grid{gap:9px}.metric-card{padding:14px;border-radius:14px}.metric-icon{width:36px;height:36px;font-size:14px}.metric-value{font-size:23px}.metric-label{font-size:11px}.metric-note{font-size:10px}.search-panel{padding:16px 14px}.panel-heading{padding:17px 15px}.tracking-search-row{flex-direction:column}.tracking-btn{width:100%}.filter-grid{grid-template-columns:1fr 1fr}.filter-field.product-filter{grid-column:1/-1}.filter-actions{grid-column:1/-1}.filter-actions .tracking-btn{flex:1}.result-toolbar{padding:14px;align-items:flex-start;flex-direction:column}.detail-backdrop{padding:8px}.detail-modal{max-height:96vh;border-radius:15px}.detail-head,.detail-body{padding:17px}.detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:380px){.metric-grid{grid-template-columns:1fr 1fr}.detail-grid{grid-template-columns:1fr}.filter-grid{grid-template-columns:1fr}.filter-field.product-filter,.filter-actions{grid-column:auto}}
    </style>
</head>
<body x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }" x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode === true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <section class="tracking-page">
                <div class="tracking-shell">
                    <div class="tracking-hero">
                        <div>
                            <div class="tracking-eyebrow"><i class="fa-solid fa-microchip"></i> Inventory intelligence</div>
                            <h1 class="tracking-title">Product Tracking Intelligence</h1>
                            <p class="tracking-subtitle">Trace every individually identified unit across your inventory. Search by IMEI, serial number or VIN to confirm its status, stock location, sale and customer record.</p>
                        </div>
                        <div class="tracking-live">Live company records</div>
                    </div>

                    <div class="metric-grid">
                        <div class="metric-card"><div class="metric-top"><span class="metric-label">Total tracked units</span><span class="metric-icon"><i class="fa-solid fa-barcode"></i></span></div><div class="metric-value"><?php echo number_format((int)($metrics['total_tracked'] ?? 0)); ?></div><div class="metric-note">Matching the current search and filters</div></div>
                        <div class="metric-card"><div class="metric-top"><span class="metric-label">Currently in stock</span><span class="metric-icon" style="background:#ecfdf3;color:#16a34a"><i class="fa-solid fa-boxes-stacked"></i></span></div><div class="metric-value"><?php echo number_format((int)($metrics['in_stock'] ?? 0)); ?></div><div class="metric-note">Matching units currently in stock</div></div>
                        <div class="metric-card"><div class="metric-top"><span class="metric-label">Sold units</span><span class="metric-icon" style="background:#eff6ff;color:#2563eb"><i class="fa-solid fa-receipt"></i></span></div><div class="metric-value"><?php echo number_format((int)($metrics['sold'] ?? 0)); ?></div><div class="metric-note">Matching units with sold status</div></div>
                        <div class="metric-card"><div class="metric-top"><span class="metric-label">Require attention</span><span class="metric-icon" style="background:#fff7ed;color:#ea580c"><i class="fa-solid fa-triangle-exclamation"></i></span></div><div class="metric-value"><?php echo number_format((int)($metrics['attention'] ?? 0)); ?></div><div class="metric-note">Matching units needing operational attention</div></div>
                    </div>

                    <div class="tracking-panel">
                        <div class="panel-heading"><div><h2>Find a product identifier</h2><p>Search using a complete or partial IMEI, serial number or VIN.</p></div><span class="type-pills"><span class="type-pill">IMEI: <?php echo number_format((int)($metrics['imei_count'] ?? 0)); ?></span><span class="type-pill">Serial: <?php echo number_format((int)($metrics['serial_count'] ?? 0)); ?></span><span class="type-pill">VIN: <?php echo number_format((int)($metrics['vin_count'] ?? 0)); ?></span></span></div>
                        <div class="search-panel">
                            <form method="get" action="tracked-products.php">
                                <label class="search-label" for="trackingSearch">Universal identifier search</label>
                                <div class="tracking-search-row">
                                    <div class="tracking-search"><i class="fa-solid fa-magnifying-glass"></i><input id="trackingSearch" type="search" name="q" value="<?php echo h($search); ?>" placeholder="Enter IMEI, serial number, VIN, product, invoice or customer..." autocomplete="off"></div>
                                    <button class="tracking-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Track Product</button>
                                </div>
                                <div class="filter-grid">
                                    <div class="filter-field"><label for="typeFilter">Identifier type</label><select class="tracking-select" id="typeFilter" name="type"><option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All identifiers</option><option value="imei" <?php echo $typeFilter === 'imei' ? 'selected' : ''; ?>>IMEI only</option><option value="serial" <?php echo $typeFilter === 'serial' ? 'selected' : ''; ?>>Serial number only</option><option value="vin" <?php echo $typeFilter === 'vin' ? 'selected' : ''; ?>>VIN only</option></select></div>
                                    <div class="filter-field"><label for="statusFilter">Product status</label><select class="tracking-select" id="statusFilter" name="status"><option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All statuses</option><?php foreach ($allowedStatuses as $st): if ($st === 'all') continue; ?><option value="<?php echo h($st); ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo h(trackingStatusLabel($st)); ?></option><?php endforeach; ?></select></div>
                                    <div class="filter-field product-filter"><label for="productFilter">Specific product</label><select class="tracking-select" id="productFilter" name="product_id"><option value="">All tracked products</option><?php foreach ($trackedProducts as $trackedProduct): ?><option value="<?php echo (int)$trackedProduct['id']; ?>" <?php echo $productFilter === (int)$trackedProduct['id'] ? 'selected' : ''; ?>><?php echo h($trackedProduct['name']); ?> · <?php echo strtoupper(h($trackedProduct['identifier_tracking'])); ?></option><?php endforeach; ?></select></div>
                                    <div class="filter-actions"><button type="submit" class="tracking-btn"><i class="fa-solid fa-sliders"></i> Apply</button><a class="tracking-btn secondary" href="tracked-products.php"><i class="fa-solid fa-rotate-left"></i> Reset</a></div>
                                </div>
                                <p class="search-hint"><i class="fa-regular fa-lightbulb"></i> Tip: you can also search by invoice number, customer name, phone number or email to find tracked units attached to a sale.</p>
                            </form>
                        </div>
                    </div>

                    <div class="tracking-panel">
                        <div class="result-toolbar"><div class="result-count">Showing <strong><?php echo $totalRows ? number_format($offset + 1) . '–' . number_format(min($offset + $perPage, $totalRows)) : '0'; ?></strong> of <strong><?php echo number_format($totalRows); ?></strong> matching tracked units</div><div class="type-pills"><span class="type-pill">Company-wide view</span><span class="type-pill">Newest activity first</span></div></div>
                        <?php if (!$records): ?>
                            <div class="empty-state"><i class="fa-solid fa-barcode"></i><h3>No tracked products found</h3><p><?php echo $search !== '' || $typeFilter !== 'all' || $statusFilter !== 'all' || $productFilter > 0 ? 'Try changing your search or filters.' : 'Products configured for IMEI, serial or VIN tracking will appear here after identifiers are registered during stock-in.'; ?></p></div>
                        <?php else: ?>
                            <div class="table-wrap"><table class="tracking-table"><thead><tr><th>Identifier</th><th>Product</th><th>Type</th><th>Status</th><th>Location</th><th>Sale / Customer</th><th>Last updated</th><th>Details</th></tr></thead><tbody>
                            <?php foreach ($records as $record): $recordStatus = (string)$record['status']; $recordType = (string)$record['identifier_type']; ?>
                                <tr>
                                    <td><div class="identifier-main"><?php echo h($record['identifier_value'] ?: 'Identifier unavailable'); ?></div><div class="identifier-sub">Tracking ID #<?php echo (int)$record['tracking_id']; ?></div></td>
                                    <td><div class="product-main"><?php echo h($record['product_name']); ?></div><div class="product-sub">SKU / Barcode: <?php echo h($record['barcode'] ?: '—'); ?></div></td>
                                    <td><span class="type-tag <?php echo h($recordType); ?>"><i class="fa-solid <?php echo $recordType === 'imei' ? 'fa-mobile-screen' : ($recordType === 'vin' ? 'fa-car' : 'fa-fingerprint'); ?>"></i><?php echo strtoupper(h($recordType)); ?></span></td>
                                    <td><span class="status-tag <?php echo h(trackingStatusClass($recordStatus)); ?>"><?php echo h(trackingStatusLabel($recordStatus)); ?></span></td>
                                    <td><?php echo h($record['location_name'] ?: 'Not assigned'); ?></td>
                                    <td><?php if (!empty($record['invoice_number'])): ?><div class="sale-main"><?php echo h($record['invoice_number']); ?></div><div class="sale-sub"><?php echo h($record['customer_name'] ?: $record['customer_phone'] ?: 'Customer record'); ?></div><?php else: ?><span class="sale-sub">No sale linked</span><?php endif; ?></td>
                                    <td><?php echo h(trackingDate($record['updated_at'] ?? $record['received_at'])); ?></td>
                                    <td><a class="view-link" href="<?php echo h(queryUrl(['view' => (int)$record['tracking_id']])); ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> Inspect</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody></table></div>
                            <?php if ($totalPages > 1): ?><div class="pagination"><div class="pagination-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></div><div class="pagination-links"><a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo h(queryUrl(['page' => max(1, $page - 1), 'view' => null])); ?>"><i class="fa-solid fa-chevron-left"></i></a><?php $startPage = max(1, $page - 2); $endPage = min($totalPages, $page + 2); for ($p = $startPage; $p <= $endPage; $p++): ?><a class="page-link <?php echo $p === $page ? 'active' : ''; ?>" href="<?php echo h(queryUrl(['page' => $p, 'view' => null])); ?>"><?php echo $p; ?></a><?php endfor; ?><a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo h(queryUrl(['page' => min($totalPages, $page + 1), 'view' => null])); ?>"><i class="fa-solid fa-chevron-right"></i></a></div></div><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
<?php include('footer.php'); ?>

<?php if ($selectedRecord): ?>
<div class="detail-backdrop" role="dialog" aria-modal="true" aria-label="Tracked product details" x-data="{open:true}" x-show="open" @keydown.escape.window="open=false; window.location.href='<?php echo h(queryUrl(['view' => null])); ?>'">
    <div class="detail-modal">
        <div class="detail-head"><div><h2>Product Trace Record</h2><p><?php echo h($selectedRecord['product_name']); ?> · Tracking ID #<?php echo (int)$selectedRecord['id']; ?></p></div><a class="detail-close" href="<?php echo h(queryUrl(['view' => null])); ?>" aria-label="Close details"><i class="fa-solid fa-xmark"></i></a></div>
        <div class="detail-body">
            <div class="detail-grid">
                <div class="detail-cell"><span>Identifier</span><strong><?php echo h($selectedRecord['display_identifier'] ?: 'Unavailable'); ?></strong></div>
                <div class="detail-cell"><span>Identifier type</span><strong><?php echo strtoupper(h($selectedRecord['identifier_type'])); ?></strong></div>
                <div class="detail-cell"><span>Current status</span><strong><span class="status-tag <?php echo h(trackingStatusClass((string)$selectedRecord['status'])); ?>"><?php echo h(trackingStatusLabel((string)$selectedRecord['status'])); ?></span></strong></div>
                <div class="detail-cell"><span>Product</span><strong><?php echo h($selectedRecord['product_name']); ?></strong></div>
                <div class="detail-cell"><span>Barcode / SKU</span><strong><?php echo h($selectedRecord['barcode'] ?: '—'); ?></strong></div>
                <div class="detail-cell"><span>Current location</span><strong><?php echo h($selectedRecord['location_name'] ?: 'Not assigned'); ?></strong></div>
                <div class="detail-cell"><span>Received into stock</span><strong><?php echo h(trackingDate($selectedRecord['received_at'])); ?></strong></div>
                <div class="detail-cell"><span>Last updated</span><strong><?php echo h(trackingDate($selectedRecord['updated_at'])); ?></strong></div>
                <div class="detail-cell"><span>Stock record</span><strong><?php echo !empty($selectedRecord['stock_id']) ? '#' . (int)$selectedRecord['stock_id'] : 'Not assigned'; ?></strong></div>
            </div>

            <?php if (!empty($selectedRecord['current_sale_id']) || !empty($selectedRecord['invoice_number'])): ?>
                <h3 class="detail-section-title">Sale information</h3>
                <div class="detail-grid">
                    <div class="detail-cell"><span>Invoice number</span><strong><?php echo h($selectedRecord['invoice_number'] ?: '—'); ?></strong></div>
                    <div class="detail-cell"><span>Customer</span><strong><?php echo h($selectedRecord['customer_name'] ?: '—'); ?></strong></div>
                    <div class="detail-cell"><span>Customer phone</span><strong><?php echo h($selectedRecord['customer_phone'] ?: '—'); ?></strong></div>
                    <div class="detail-cell"><span>Customer email</span><strong><?php echo h($selectedRecord['customer_email'] ?: '—'); ?></strong></div>
                    <div class="detail-cell"><span>Sale date</span><strong><?php echo h(trackingDate($selectedRecord['sold_at'] ?? null)); ?></strong></div>
                    <div class="detail-cell"><span>Payment status</span><strong><?php echo h(ucfirst((string)($selectedRecord['payment_status'] ?? '—'))); ?></strong></div>
                    <div class="detail-cell"><span>Invoice total</span><strong><?php echo isset($selectedRecord['final_total']) ? '₦' . number_format((float)$selectedRecord['final_total'], 2) : '—'; ?></strong></div>
                    <div class="detail-cell"><span>Amount paid</span><strong><?php echo isset($selectedRecord['amount_paid']) ? '₦' . number_format((float)$selectedRecord['amount_paid'], 2) : '—'; ?></strong></div>
                    <div class="detail-cell"><span>Balance</span><strong><?php echo isset($selectedRecord['balance']) ? '₦' . number_format((float)$selectedRecord['balance'], 2) : '—'; ?></strong></div>
                </div>
            <?php endif; ?>

            <h3 class="detail-section-title">Sale history</h3>
            <?php if (!empty($saleHistory)): ?><div class="history-wrap"><table class="history-table"><thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Payment</th><th>Sale total</th></tr></thead><tbody><?php foreach ($saleHistory as $history): ?><tr><td><strong><?php echo h($history['invoice_number']); ?></strong></td><td><?php echo h($history['customer_name'] ?: $history['customer_phone'] ?: '—'); ?></td><td><?php echo h(trackingDate($history['sale_date'])); ?></td><td><?php echo h(ucfirst((string)$history['payment_status'])); ?></td><td><?php echo '₦' . number_format((float)$history['final_total'], 2); ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="tracking-subtitle">No sale snapshot has been recorded for this identifier.</p><?php endif; ?>

            <h3 class="detail-section-title">Movement &amp; activity timeline</h3>
            <?php if ($events): ?><div class="timeline"><?php foreach ($events as $event): ?><div class="timeline-item"><strong><?php echo h(ucwords(str_replace('_', ' ', (string)$event['event_type']))); ?></strong><p><?php echo h(trackingDate($event['created_at'])); ?><?php if (!empty($event['from_location_name']) || !empty($event['to_location_name'])): ?> · <?php echo h($event['from_location_name'] ?: 'Origin'); ?> → <?php echo h($event['to_location_name'] ?: 'Destination'); ?><?php endif; ?><?php if (!empty($event['reference'])): ?> · Ref: <?php echo h($event['reference']); ?><?php endif; ?><?php if (!empty($event['notes'])): ?><br><?php echo h($event['notes']); ?><?php endif; ?><?php if (!empty($event['performed_by_name']) || !empty($event['performed_by_email'])): ?><br>Recorded by: <?php echo h($event['performed_by_name'] ?: $event['performed_by_email']); ?><?php endif; ?></p></div><?php endforeach; ?></div><?php else: ?><p class="tracking-subtitle">No movement events have been recorded for this identifier yet.</p><?php endif; ?>
        </div>
    </div>
</div>
<?php elseif ($selectedId > 0): ?>
<div class="detail-backdrop"><div class="detail-modal"><div class="detail-head"><div><h2>Record not found</h2><p>This identifier record may have been removed or does not belong to your company.</p></div><a class="detail-close" href="<?php echo h(queryUrl(['view' => null])); ?>"><i class="fa-solid fa-xmark"></i></a></div></div></div>
<?php endif; ?>

<script defer src="bundle.js"></script>
</body>
</html>
