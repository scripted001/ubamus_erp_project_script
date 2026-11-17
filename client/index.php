<?php
/**
 * CUSTOMER HOME PAGE
 * Replaces the staff stock dashboard with a customer-facing catalogue and
 * session-cart view. Shared layout files and Alpine assets are not modified.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/validation.php';
require_once __DIR__ . '/user_data.php';
require_once __DIR__ . '/../company_data.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}

/* Use the company already resolved by the application's existing bootstrap. */
$company_id = isset($company_id) ? (int)$company_id : 0;
if ($company_id <= 0) {
    $company_id = (int)($_SESSION['marketplace_company_id'] ?? $_SESSION['company_id'] ?? 0);
}
if ($company_id <= 0) {
    http_response_code(403);
    exit('Your customer account is not linked to a company. Please sign in again.');
}

if (!isset($_SESSION['marketplace_cart']) || !is_array($_SESSION['marketplace_cart'])) {
    $_SESSION['marketplace_cart'] = [];
}

function customer_home_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function customer_home_money($value): string {
    return '₦' . number_format((float)$value, 2);
}
function customer_home_table_exists(PDO $pdo, string $table): bool {
    $allowed = ['marketplace_service_settings', 'product_images', 'service_image', 'marketplace_orders', 'marketplace_order_items', 'marketplace_carts', 'marketplace_cart_items'];
    if (!in_array($table, $allowed, true)) return false;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        try {
            $pdo->query("SELECT 1 FROM `{$table}` LIMIT 0");
            return true;
        } catch (Throwable $ignored) {
            return false;
        }
    }
}
function customer_home_image_url($stored, string $kind = 'product'): string {
    $stored = trim(str_replace('\\', '/', (string)$stored));
    if ($stored === '') return '';
    if (preg_match('~^https?://~i', $stored)) return $stored;

    if (strpos($stored, '../uploads/') === 0 || strpos($stored, 'uploads/') === 0) {
        $candidates = [$stored];
    } else {
        $name = basename($stored);
        $folders = $kind === 'service'
            ? ['../uploads/services/', 'uploads/services/', 'uploads/service_images/', 'uploads/']
            : ($kind === 'logo'
                ? ['../uploads/logos/', 'uploads/logos/', '../uploads/']
                : ['uploads/products/', 'uploads/product_images/', 'uploads/product/', '../uploads/products/', '../uploads/product_images/', '../uploads/product/', '../hoa/uploads/products/', 'uploads/']);
        $candidates = [];
        foreach ($folders as $folder) $candidates[] = $folder . $name;
        $candidates[] = $name;
    }

    foreach ($candidates as $candidate) {
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
        $full = strpos($candidate, '../') === 0
            ? dirname(__DIR__) . DIRECTORY_SEPARATOR . substr($relative, 3)
            : __DIR__ . DIRECTORY_SEPARATOR . $relative;
        if (is_file($full)) return $candidate;
    }
    return $candidates[0] ?? '';
}
function customer_home_excerpt($value, int $limit = 115): string {
    $value = (string)$value;
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $limit, '…', 'UTF-8');
    }
    return strlen($value) > $limit ? substr($value, 0, max(0, $limit - 3)) . '...' : $value;
}
function customer_home_csrf(): string {
    if (empty($_SESSION['customer_home_csrf'])) {
        $_SESSION['customer_home_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['customer_home_csrf'];
}
function customer_home_redirect(): void {
    header('Location: index.php');
    exit;
}
function customer_home_product(PDO $pdo, int $companyId, int $productId): ?array {
    $stmt = $pdo->prepare("
        SELECT p.id,p.name,p.description,p.selling_price,p.category_id,p.barcode,
               COALESCE(pc.name,'Products') AS category_name,
               COALESCE(u.unit_name,'') AS unit_name,
               COALESCE(st.total_stock,0) AS total_stock,
               img.image_path
        FROM products p
        LEFT JOIN product_categories pc ON pc.id=p.category_id AND pc.company_id=p.company_id
        LEFT JOIN units u ON u.id=p.unit_id AND u.company_id=p.company_id
        LEFT JOIN (
            SELECT product_id,company_id,SUM(COALESCE(quantity,0)) AS total_stock
            FROM stocks GROUP BY product_id,company_id
        ) st ON st.product_id=p.id AND st.company_id=p.company_id
        LEFT JOIN (
            SELECT product_id,MIN(id) AS first_image_id
            FROM product_images GROUP BY product_id
        ) fi ON fi.product_id=p.id
        LEFT JOIN product_images img ON img.id=fi.first_image_id
        WHERE p.id=? AND p.company_id=? LIMIT 1
    ");
    $stmt->execute([$productId, $companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
function customer_home_service(PDO $pdo, int $companyId, int $serviceId): ?array {
    $stmt = $pdo->prepare("
        SELECT s.id,s.service_name,s.description,s.category_id,
               COALESCE(jc.name,'Professional Services') AS category_name
        FROM services s
        LEFT JOIN job_categories jc ON jc.id=s.category_id AND jc.company_id=s.company_id
        WHERE s.id=? AND s.company_id=? LIMIT 1
    ");
    $stmt->execute([$serviceId, $companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
function customer_home_service_pricing(PDO $pdo, int $companyId, int $serviceId, bool $hasSettings): array {
    if ($hasSettings) {
        $stmt = $pdo->prepare("SELECT pricing_type,fixed_price,is_available FROM marketplace_service_settings WHERE company_id=? AND service_id=? LIMIT 1");
        $stmt->execute([$companyId, $serviceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'pricing_type' => ($row['pricing_type'] === 'fixed' ? 'fixed' : 'quotation'),
                'fixed_price' => max(0, (float)$row['fixed_price']),
                'is_available' => (int)$row['is_available']
            ];
        }
    }
    return ['pricing_type' => 'quotation', 'fixed_price' => 0, 'is_available' => 1];
}

$companyStmt = $pdo->prepare("SELECT company_name,company_logo FROM companies WHERE id=? LIMIT 1");
$companyStmt->execute([$company_id]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    http_response_code(404);
    exit('Customer company could not be found.');
}
$company_name = (string)$company['company_name'];
$company_logo_url = trim((string)($company['company_logo'] ?? '')) !== ''
    ? customer_home_image_url(basename((string)$company['company_logo']), 'logo')
    : '';
$customer_name = trim((string)($_SESSION['marketplace_customer_name'] ?? $_SESSION['username'] ?? 'Customer'));
$csrf = customer_home_csrf();
if (empty($_SESSION['customer_home_rfq_token'])) {
    $_SESSION['customer_home_rfq_token'] = bin2hex(random_bytes(32));
}
$rfqToken = (string)$_SESSION['customer_home_rfq_token'];
$lastSalesRequest = $_SESSION['marketplace_last_sales_request'] ?? null;
unset($_SESSION['marketplace_last_sales_request']);
$hasServiceSettings = customer_home_table_exists($pdo, 'marketplace_service_settings');
$hasProductImages = customer_home_table_exists($pdo, 'product_images');
$hasServiceImages = customer_home_table_exists($pdo, 'service_image');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = (string)($_POST['csrf_token'] ?? '');
    if ($sent === '' || !hash_equals($csrf, $sent)) {
        $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'Your session security token expired. Please try again.'];
        customer_home_redirect();
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'add_to_cart') {
        $type = (string)($_POST['item_type'] ?? '');
        $id = (int)($_POST['item_id'] ?? 0);
        $quantity = max(1, min(99, (int)($_POST['quantity'] ?? 1)));

        if ($type === 'product') {
            $product = customer_home_product($pdo, $company_id, $id);
            $stock = $product ? max(0, (int)$product['total_stock']) : 0;
            if (!$product || $stock < 1) {
                $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'This product is currently unavailable.'];
            } else {
                $key = 'product-' . $id;
                $old = (int)($_SESSION['marketplace_cart'][$key]['quantity'] ?? 0);
                $_SESSION['marketplace_cart'][$key] = [
                    'type'=>'product', 'id'=>$id, 'quantity'=>min($stock, $old + $quantity)
                ];
                $_SESSION['customer_home_flash'] = ['type'=>'success','message'=>'Product added to your cart.'];
            }
        } elseif ($type === 'service') {
            $service = customer_home_service($pdo, $company_id, $id);
            $pricing = $service ? customer_home_service_pricing($pdo, $company_id, $id, $hasServiceSettings) : null;
            if (!$service || !$pricing['is_available']) {
                $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'This service is currently unavailable.'];
            } else {
                $key = 'service-' . bin2hex(random_bytes(8));
                $_SESSION['marketplace_cart'][$key] = [
                    'type'=>'service', 'id'=>$id, 'quantity'=>$quantity,
                    'line_key'=>bin2hex(random_bytes(16)), 'service_note'=>'',
                    'required_work'=>'', 'preferred_date'=>'', 'service_location'=>''
                ];
                $_SESSION['customer_home_flash'] = ['type'=>'success','message'=>'Service added to your cart.'];
            }
        }
    } elseif ($action === 'update_cart') {
        $key = (string)($_POST['cart_key'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        if (isset($_SESSION['marketplace_cart'][$key])) {
            if ($quantity <= 0) {
                unset($_SESSION['marketplace_cart'][$key]);
            } else {
                $entry = $_SESSION['marketplace_cart'][$key];
                $quantity = min(99, $quantity);
                if (($entry['type'] ?? '') === 'product') {
                    $product = customer_home_product($pdo, $company_id, (int)($entry['id'] ?? 0));
                    $quantity = $product ? min($quantity, max(0, (int)$product['total_stock'])) : 0;
                }
                if ($quantity < 1) unset($_SESSION['marketplace_cart'][$key]);
                else $_SESSION['marketplace_cart'][$key]['quantity'] = $quantity;
            }
        }
    } elseif ($action === 'remove_cart_item') {
        unset($_SESSION['marketplace_cart'][(string)($_POST['cart_key'] ?? '')]);
        $_SESSION['customer_home_flash'] = ['type'=>'success','message'=>'Item removed from your cart.'];
    } elseif ($action === 'save_service_note') {
        $key = (string)($_POST['cart_key'] ?? '');
        if (isset($_SESSION['marketplace_cart'][$key]) && ($_SESSION['marketplace_cart'][$key]['type'] ?? '') === 'service') {
            $_SESSION['marketplace_cart'][$key]['required_work'] = substr(trim((string)($_POST['required_work'] ?? '')), 0, 2000);
            $_SESSION['marketplace_cart'][$key]['service_note'] = substr(trim((string)($_POST['service_note'] ?? '')), 0, 2000);
            $date = (string)($_POST['preferred_date'] ?? '');
            $_SESSION['marketplace_cart'][$key]['preferred_date'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
            $_SESSION['marketplace_cart'][$key]['service_location'] = substr(trim((string)($_POST['service_location'] ?? '')), 0, 500);
            $_SESSION['customer_home_flash'] = ['type'=>'success','message'=>'Service request details saved.'];
        }
    } elseif ($action === 'submit_sales_request') {
        $submittedToken = (string)($_POST['rfq_token'] ?? '');
        if ($submittedToken === '' || !hash_equals($rfqToken, $submittedToken)) {
            $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'This sales request form has already been submitted or expired. Please review your cart and try again.'];
            customer_home_redirect();
        }

        $customerId = (int)($_SESSION['marketplace_customer_id'] ?? 0);
        $sessionCompanyId = (int)($_SESSION['marketplace_company_id'] ?? 0);
        if ($customerId <= 0 || $sessionCompanyId !== $company_id) {
            $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'Please sign in to your customer account before sending a sales request. Your cart has been kept.'];
            header('Location: ../customer_auth.php?account=login');
            exit;
        }

        if (!customer_home_table_exists($pdo, 'marketplace_orders')
            || !customer_home_table_exists($pdo, 'marketplace_order_items')) {
            $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'Sales request storage is not available. Please contact the company administrator.'];
            customer_home_redirect();
        }

        if (empty($_SESSION['marketplace_cart']) || !is_array($_SESSION['marketplace_cart'])) {
            $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'Your cart is empty. Add products or services before sending a request.'];
            customer_home_redirect();
        }

        // Confirm that the signed-in customer account is still active and belongs to this company.
        $customerStmt = $pdo->prepare("
            SELECT ca.id AS account_id, ca.customer_id, c.fullname, c.email, c.phone, c.location_id
            FROM customer_accounts ca
            INNER JOIN customers c
                ON c.id = ca.customer_id AND c.company_id = ca.company_id
            WHERE ca.company_id = ?
              AND ca.customer_id = ?
              AND ca.account_status = 'active'
            LIMIT 1
        ");
        $customerStmt->execute([$company_id, $customerId]);
        $customerAccount = $customerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customerAccount) {
            $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'Your customer session could not be verified. Please sign in again. Your cart has been kept.'];
            header('Location: ../customer_auth.php?account=login');
            exit;
        }

        $customerNote = trim((string)($_POST['customer_note'] ?? ''));
        $customerNote = substr($customerNote, 0, 5000);
        $validatedItems = [];
        $productSubtotalForRequest = 0.0;
        $serviceSubtotalForRequest = 0.0;
        $hasProducts = false;
        $hasServices = false;

        foreach ($_SESSION['marketplace_cart'] as $entry) {
            if (!is_array($entry) || empty($entry['type']) || empty($entry['id'])) {
                continue;
            }

            $type = (string)$entry['type'];
            $itemId = (int)$entry['id'];
            $quantity = max(1, min(99, (int)($entry['quantity'] ?? 1)));

            if ($type === 'product') {
                $product = customer_home_product($pdo, $company_id, $itemId);
                if (!$product) {
                    continue;
                }

                $availableStock = max(0, (int)$product['total_stock']);
                if ($availableStock < $quantity) {
                    $_SESSION['customer_home_flash'] = [
                        'type'=>'error',
                        'message'=>'Available stock for "' . $product['name'] . '" has changed. Please update your cart quantity before submitting.'
                    ];
                    customer_home_redirect();
                }

                $unitPrice = max(0, (float)$product['selling_price']);
                $lineTotal = $unitPrice * $quantity;
                $productSubtotalForRequest += $lineTotal;
                $hasProducts = true;

                $validatedItems[] = [
                    'type'=>'product',
                    'id'=>(int)$product['id'],
                    'name'=>(string)$product['name'],
                    'quantity'=>$quantity,
                    'unit_price'=>$unitPrice,
                    'line_total'=>$lineTotal,
                    'pricing_type'=>'fixed',
                    'quotation_status'=>'requested',
                    'service_note'=>null,
                    'required_work'=>null,
                    'preferred_date'=>null,
                    'service_location'=>null
                ];
            } elseif ($type === 'service') {
                $service = customer_home_service($pdo, $company_id, $itemId);
                if (!$service) {
                    continue;
                }

                $pricing = customer_home_service_pricing($pdo, $company_id, $itemId, $hasServiceSettings);
                if (empty($pricing['is_available'])) {
                    $_SESSION['customer_home_flash'] = [
                        'type'=>'error',
                        'message'=>'The service "' . $service['service_name'] . '" is no longer available. Please remove it from your cart.'
                    ];
                    customer_home_redirect();
                }

                $unitPrice = ($pricing['pricing_type'] === 'fixed')
                    ? max(0, (float)$pricing['fixed_price'])
                    : 0.0;
                $lineTotal = $unitPrice * $quantity;
                $serviceSubtotalForRequest += $lineTotal;
                $hasServices = true;

                $preferredDate = trim((string)($entry['preferred_date'] ?? ''));
                if ($preferredDate !== '') {
                    $dateObject = DateTime::createFromFormat('Y-m-d', $preferredDate);
                    if (!$dateObject || $dateObject->format('Y-m-d') !== $preferredDate) {
                        $preferredDate = '';
                    }
                }

                $validatedItems[] = [
                    'type'=>'service',
                    'id'=>(int)$service['id'],
                    'name'=>(string)$service['service_name'],
                    'quantity'=>$quantity,
                    'unit_price'=>$unitPrice,
                    'line_total'=>$lineTotal,
                    'pricing_type'=>$pricing['pricing_type'],
                    'quotation_status'=>'requested',
                    'service_note'=>substr(trim((string)($entry['service_note'] ?? '')), 0, 2000),
                    'required_work'=>substr(trim((string)($entry['required_work'] ?? '')), 0, 2000),
                    'preferred_date'=>$preferredDate !== '' ? $preferredDate : null,
                    'service_location'=>substr(trim((string)($entry['service_location'] ?? '')), 0, 500)
                ];
            }
        }

        if (!$validatedItems) {
            $_SESSION['customer_home_flash'] = ['type'=>'error','message'=>'No valid products or services were found in your cart. Please refresh the catalogue and try again.'];
            customer_home_redirect();
        }

        $companyLocationStmt = $pdo->prepare("SELECT location_id FROM companies WHERE id=? LIMIT 1");
        $companyLocationStmt->execute([$company_id]);
        $companyLocationId = (int)$companyLocationStmt->fetchColumn();
        $requestLocationId = (int)($customerAccount['location_id'] ?? 0);
        if ($requestLocationId <= 0) {
            $requestLocationId = $companyLocationId;
        }

        $requestType = ($hasProducts && $hasServices)
            ? 'Products and services'
            : ($hasProducts ? 'Products' : 'Services');

        /*
         * Hand off the validated cart to rfq.php through the server-side session.
         * Only an opaque one-time token is sent by the browser; item prices and
         * customer identity are not trusted from hidden form fields.
         * rfq.php will own the database transaction and clear the cart only
         * after the RFQ and its item rows have been committed successfully.
         */
        $transferToken = bin2hex(random_bytes(32));
        $_SESSION['marketplace_rfq_handoff'] = [
            'token' => $transferToken,
            'created_at' => time(),
            'company_id' => $company_id,
            'customer_id' => $customerId,
            'customer_account_id' => (int)$customerAccount['account_id'],
            'customer_name' => (string)($customerAccount['fullname'] ?? ''),
            'customer_email' => (string)($customerAccount['email'] ?? ''),
            'customer_phone' => (string)($customerAccount['phone'] ?? ''),
            'location_id' => $requestLocationId,
            'customer_note' => $customerNote,
            'request_type' => $requestType,
            'items' => $validatedItems,
            'product_subtotal' => $productSubtotalForRequest,
            'service_subtotal' => $serviceSubtotalForRequest,
            'estimated_total' => $productSubtotalForRequest + $serviceSubtotalForRequest
        ];

        // Do not rotate the RFQ token or clear either cart here. rfq.php does
        // both only after its database transaction succeeds.
        $handoffCsrf = customer_home_csrf();
        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Sending your sales request…</title>
            <style>
                *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f5f8fc;color:#172033;font-family:Arial,sans-serif}
                .handoff{width:min(100%,440px);padding:30px;border:1px solid #e4eaf2;border-radius:20px;background:#fff;box-shadow:0 18px 55px rgba(25,45,75,.09);text-align:center}
                .spinner{width:38px;height:38px;margin:0 auto 18px;border:4px solid #e7edf5;border-top-color:#2563eb;border-radius:50%;animation:spin .8s linear infinite}
                h1{margin:0 0 8px;font-size:20px}p{margin:0;color:#64748b;font-size:14px;line-height:1.6}@keyframes spin{to{transform:rotate(360deg)}}
                button{margin-top:18px;padding:11px 18px;border:0;border-radius:10px;background:#1d4ed8;color:#fff;font-weight:700;cursor:pointer}
            </style>
        </head>
        <body>
            <main class="handoff">
                <div class="spinner" aria-hidden="true"></div>
                <h1>Sending your sales request</h1>
                <p>Your cart is being securely transferred to the RFQ processing page. Please wait…</p>
                <form id="rfqHandoffForm" method="post" action="rfq.php">
                    <input type="hidden" name="action" value="process_rfq_handoff">
                    <input type="hidden" name="csrf_token" value="<?= customer_home_h($handoffCsrf); ?>">
                    <input type="hidden" name="handoff_token" value="<?= customer_home_h($transferToken); ?>">
                    <noscript><button type="submit">Continue to send request</button></noscript>
                </form>
            </main>
            <script>document.getElementById('rfqHandoffForm').submit();</script>
        </body>
        </html>
        <?php
        exit;
    } elseif ($action === 'clear_cart') {
        $_SESSION['marketplace_cart'] = [];
        $_SESSION['customer_home_flash'] = ['type'=>'success','message'=>'Your cart has been cleared.'];
    }
    customer_home_redirect();
}

$search = trim((string)($_GET['q'] ?? ''));
$like = '%' . $search . '%';

$productSql = "
    SELECT p.id,p.name,p.description,p.selling_price,p.category_id,
           COALESCE(pc.name,'Products') AS category_name,
           COALESCE(u.unit_name,'') AS unit_name,
           COALESCE(st.total_stock,0) AS total_stock,
           img.image_path
    FROM products p
    LEFT JOIN product_categories pc ON pc.id=p.category_id AND pc.company_id=p.company_id
    LEFT JOIN units u ON u.id=p.unit_id AND u.company_id=p.company_id
    LEFT JOIN (
        SELECT product_id,company_id,SUM(COALESCE(quantity,0)) AS total_stock
        FROM stocks GROUP BY product_id,company_id
    ) st ON st.product_id=p.id AND st.company_id=p.company_id
    LEFT JOIN (
        SELECT product_id,MIN(id) AS first_image_id FROM product_images GROUP BY product_id
    ) fi ON fi.product_id=p.id
    LEFT JOIN product_images img ON img.id=fi.first_image_id
    WHERE p.company_id=?
";
$productParams = [$company_id];
if ($search !== '') {
    $productSql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.barcode LIKE ?)";
    array_push($productParams, $like, $like, $like);
}
$productSql .= " ORDER BY p.id DESC LIMIT 60";
$productStmt = $pdo->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

$servicePriceSelect = $hasServiceSettings
    ? "COALESCE(mss.pricing_type,'quotation') AS pricing_type,COALESCE(mss.fixed_price,0) AS fixed_price,COALESCE(mss.is_available,1) AS is_available"
    : "'quotation' AS pricing_type,0 AS fixed_price,1 AS is_available";
$serviceJoin = $hasServiceSettings
    ? "LEFT JOIN marketplace_service_settings mss ON mss.service_id=s.id AND mss.company_id=s.company_id"
    : "";
$serviceAvailability = $hasServiceSettings ? "COALESCE(mss.is_available,1)=1" : "1=1";
$serviceSql = "
    SELECT s.id,s.service_name,s.description,s.category_id,
           COALESCE(jc.name,'Professional Services') AS category_name,
           {$servicePriceSelect}
    FROM services s
    LEFT JOIN job_categories jc ON jc.id=s.category_id AND jc.company_id=s.company_id
    {$serviceJoin}
    WHERE s.company_id=? AND {$serviceAvailability}
";
$serviceParams = [$company_id];
if ($search !== '') {
    $serviceSql .= " AND (s.service_name LIKE ? OR s.description LIKE ?)";
    array_push($serviceParams, $like, $like);
}
$serviceSql .= " ORDER BY s.id DESC LIMIT 60";
$serviceStmt = $pdo->prepare($serviceSql);
$serviceStmt->execute($serviceParams);
$services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

$productImageMap = [];
if ($hasProductImages && $products) {
    $ids = array_values(array_unique(array_map('intval', array_column($products, 'id'))));
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $imgStmt = $pdo->prepare("SELECT product_id,image_path FROM product_images WHERE product_id IN ($marks) ORDER BY id ASC");
    $imgStmt->execute($ids);
    foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $pid = (int)$img['product_id'];
        if (!isset($productImageMap[$pid])) $productImageMap[$pid] = customer_home_image_url($img['image_path']);
    }
}
$serviceImageMap = [];
if ($hasServiceImages && $services) {
    $ids = array_values(array_unique(array_map('intval', array_column($services, 'id'))));
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $imgStmt = $pdo->prepare("SELECT service_id,image_path FROM service_image WHERE company_id=? AND service_id IN ($marks) ORDER BY id ASC");
    $imgStmt->execute(array_merge([$company_id], $ids));
    foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $sid = (int)$img['service_id'];
        if (!isset($serviceImageMap[$sid])) $serviceImageMap[$sid] = customer_home_image_url($img['image_path'], 'service');
    }
}

$cartItems = [];
$cartQuantity = 0;
$productSubtotal = 0.0;
$serviceSubtotal = 0.0;
$quotationCount = 0;
foreach ($_SESSION['marketplace_cart'] as $key => $entry) {
    if (!is_array($entry) || empty($entry['id'])) continue;
    $type = (string)($entry['type'] ?? '');
    $quantity = max(1, min(99, (int)($entry['quantity'] ?? 1)));
    $id = (int)$entry['id'];
    if ($type === 'product') {
        $product = customer_home_product($pdo, $company_id, $id);
        if (!$product) continue;
        $price = (float)$product['selling_price'];
        $lineTotal = $price * $quantity;
        $cartItems[] = [
            'key'=>(string)$key, 'type'=>'product', 'id'=>$id,
            'name'=>$product['name'], 'image'=>customer_home_image_url($product['image_path'] ?? ''),
            'price'=>$price, 'quantity'=>$quantity, 'line_total'=>$lineTotal,
            'stock'=>(int)$product['total_stock'], 'pricing_type'=>'fixed'
        ];
        $productSubtotal += $lineTotal;
    } elseif ($type === 'service') {
        $service = customer_home_service($pdo, $company_id, $id);
        if (!$service) continue;
        $pricing = customer_home_service_pricing($pdo, $company_id, $id, $hasServiceSettings);
        $price = (float)$pricing['fixed_price'];
        $isQuote = $pricing['pricing_type'] !== 'fixed';
        $lineTotal = $isQuote ? 0 : $price * $quantity;
        $cartItems[] = [
            'key'=>(string)$key, 'type'=>'service', 'id'=>$id,
            'name'=>$service['service_name'], 'image'=>$serviceImageMap[$id] ?? '',
            'price'=>$price, 'quantity'=>$quantity, 'line_total'=>$lineTotal,
            'stock'=>99, 'pricing_type'=>$pricing['pricing_type'],
            'required_work'=>(string)($entry['required_work'] ?? ''),
            'service_note'=>(string)($entry['service_note'] ?? ''),
            'preferred_date'=>(string)($entry['preferred_date'] ?? ''),
            'service_location'=>(string)($entry['service_location'] ?? '')
        ];
        if ($isQuote) $quotationCount++;
        else $serviceSubtotal += $lineTotal;
    }
    $cartQuantity += $quantity;
}
$cartLineCount = count($cartItems);
$cartTotal = $productSubtotal + $serviceSubtotal;
$flash = $_SESSION['customer_home_flash'] ?? null;
unset($_SESSION['customer_home_flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= customer_home_h($company_name); ?> | Customer Home</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <style>
        /* All page-specific styling is namespaced to avoid changing shared layout styles. */
        .customer-home{max-width:1500px;margin:0 auto;padding:22px 24px 42px;color:#263445}
        .customer-home *{box-sizing:border-box}
        .customer-home .ch-welcome{background:#fff;border:1px solid #e7ebef;border-radius:15px;padding:22px 25px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:18px}
        .customer-home .ch-brand{display:flex;align-items:center;gap:12px;margin-bottom:9px}
        .customer-home .ch-brand img{width:42px;height:42px;object-fit:contain;border-radius:9px}
        .customer-home .ch-kicker{font-size:10px;font-weight:800;letter-spacing:.12em;color:#8a6a38;text-transform:uppercase}
        .customer-home .ch-welcome h1{font-size:clamp(21px,2.4vw,29px);line-height:1.2;margin:0 0 7px;font-weight:800;color:#182534}
        .customer-home .ch-welcome p{font-size:13px;line-height:1.6;color:#687583;margin:0;max-width:720px}
        .customer-home .ch-cart-pill{white-space:nowrap;border-radius:30px;padding:10px 15px;background:#f7f3eb;color:#72572e;font-size:12px;font-weight:800}
        .customer-home .ch-alert{padding:12px 15px;border-radius:9px;margin:0 0 17px;font-size:13px}
        .customer-home .ch-alert.success{background:#edf8f1;color:#246b40;border:1px solid #cdebd6}
        .customer-home .ch-alert.error{background:#fff1f0;color:#a33b35;border:1px solid #f2d0cd}
        .customer-home .ch-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:23px 0 13px}
        .customer-home .ch-heading h2{font-size:18px;font-weight:800;color:#1e2a37;margin:0 0 4px}
        .customer-home .ch-heading p{font-size:12px;color:#77828d;margin:0}
        .customer-home .ch-card{background:#fff;border:1px solid #e7ebef;border-radius:13px;overflow:hidden;min-width:0;display:flex;flex-direction:column}
        .customer-home .ch-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
        .customer-home .ch-image{height:175px;background:#f1f3f5;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#aab3bd}
        .customer-home .ch-image img{width:100%;height:100%;object-fit:cover}
        .customer-home .ch-image i{font-size:35px}
        .customer-home .ch-card-body{padding:14px;display:flex;flex-direction:column;flex:1}
        .customer-home .ch-tag{font-size:10px;color:#8a6a38;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
        .customer-home .ch-card h3{font-size:14px;font-weight:800;color:#243241;line-height:1.4;margin:7px 0}
        .customer-home .ch-description{font-size:11px;color:#74808c;line-height:1.6;margin:0 0 13px}
        .customer-home .ch-price{font-size:15px;font-weight:900;color:#172534;margin-top:auto}
        .customer-home .ch-sub{font-size:10px;color:#84909b;margin-top:3px}
        .customer-home .ch-card-foot{display:flex;align-items:center;justify-content:space-between;gap:9px;margin-top:13px}
        .customer-home .ch-stock{font-size:10px;color:#687583}
        .customer-home .ch-add{display:flex;align-items:center;gap:6px}
        .customer-home .ch-add input{width:48px;height:34px;border:1px solid #dfe4e8;border-radius:7px;text-align:center;font-size:12px}
        .customer-home .ch-button{border:0;border-radius:7px;padding:9px 13px;background:#263b50;color:#fff;font-size:11px;font-weight:800;cursor:pointer}
        .customer-home .ch-button:hover{background:#172a3c}
        .customer-home .ch-button.secondary{background:#fff;color:#52606e;border:1px solid #dfe4e8}
        .customer-home .ch-button.danger{background:#fff;color:#a33b35;border:1px solid #f0d4d2}
        .customer-home .ch-cart{background:#fff;border:1px solid #e7ebef;border-radius:13px;overflow:hidden}
        .customer-home .ch-cart-head{padding:14px 17px;border-bottom:1px solid #edf0f2;display:flex;align-items:center;justify-content:space-between;gap:10px}
        .customer-home .ch-cart-head strong{font-size:14px}
        .customer-home .ch-cart-head span{font-size:11px;color:#7c8792}
        .customer-home .ch-cart-row{display:grid;grid-template-columns:62px minmax(0,1fr) auto;gap:13px;padding:15px 17px;border-bottom:1px solid #eff1f3;align-items:start}
        .customer-home .ch-thumb{width:62px;height:62px;border-radius:9px;background:#f0f2f4;overflow:hidden;display:grid;place-items:center;color:#aab3bd}
        .customer-home .ch-thumb img{width:100%;height:100%;object-fit:cover}
        .customer-home .ch-item-name{font-size:13px;font-weight:800;color:#263445;margin:2px 0 5px}
        .customer-home .ch-meta{font-size:10px;color:#77828d;line-height:1.5}
        .customer-home .ch-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:9px}
        .customer-home .ch-actions input{width:55px;height:31px;border:1px solid #dfe4e8;border-radius:6px;text-align:center}
        .customer-home .ch-service-details{grid-column:2/4;background:#fafbfc;border:1px solid #edf0f2;border-radius:9px;padding:12px}
        .customer-home .ch-service-details label{display:block;font-size:10px;color:#687583;margin:7px 0 4px}
        .customer-home .ch-service-details input,.customer-home .ch-service-details textarea{width:100%;border:1px solid #dfe4e8;border-radius:6px;padding:8px;font:inherit;font-size:11px;background:#fff}
        .customer-home .ch-service-details textarea{min-height:55px;resize:vertical}
        .customer-home .ch-line-total{font-size:12px;font-weight:900;white-space:nowrap;color:#263445}
        .customer-home .ch-summary{padding:16px 18px;background:#fafbfc}
        .customer-home .ch-sumline{display:flex;justify-content:space-between;gap:12px;font-size:12px;color:#65717d;margin:7px 0}
        .customer-home .ch-total{border-top:1px solid #e5e9ec;padding-top:12px;margin-top:12px;font-size:15px;font-weight:900;color:#1e2a37}
        .customer-home .ch-note{font-size:10px;color:#87919b;line-height:1.6;margin:12px 0 0}
        .customer-home .ch-empty{padding:30px 18px;text-align:center;color:#7d8994;font-size:12px}
        .customer-home .ch-empty i{display:block;font-size:28px;margin-bottom:10px;color:#b7c0c8}
        .customer-home .ch-search{display:flex;gap:9px;margin-bottom:15px}
        .customer-home .ch-search input{flex:1;min-width:0;border:1px solid #dfe4e8;border-radius:8px;padding:10px 12px;font-size:12px;background:#fff}
        .customer-home .ch-empty-grid{background:#fff;border:1px dashed #d9e0e5;border-radius:12px;padding:27px;text-align:center;color:#7b8792;font-size:12px;grid-column:1/-1}
        @media(max-width:1050px){.customer-home .ch-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:700px){.customer-home{padding:14px 12px 35px}.customer-home .ch-welcome{padding:18px;align-items:flex-start;flex-direction:column}.customer-home .ch-grid{gap:10px}.customer-home .ch-image{height:140px}.customer-home .ch-card-body{padding:11px}.customer-home .ch-card-foot{align-items:flex-start;flex-direction:column}.customer-home .ch-add{width:100%}.customer-home .ch-add input{flex:0 0 48px}.customer-home .ch-add button{flex:1}.customer-home .ch-cart-row{grid-template-columns:48px minmax(0,1fr)}.customer-home .ch-thumb{width:48px;height:48px}.customer-home .ch-line-total{grid-column:2}.customer-home .ch-service-details{grid-column:1/-1}}
        @media(max-width:430px){.customer-home .ch-grid{grid-template-columns:1fr}.customer-home .ch-image{height:180px}.customer-home .ch-card-foot{flex-direction:row}.customer-home .ch-add{width:auto}.customer-home .ch-add button{flex:initial}.customer-home .ch-search{flex-direction:column}}
    </style>
</head>
<body x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }" x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode === true}">
    <div class="flex h-screen overflow-hidden">
        <?php include('sidebar.php'); ?>
        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
            <main>
                <?php include('header.php'); ?>
                <div class="customer-home">
                    <section class="ch-welcome">
                        <div>
                            <div class="ch-brand">
                                <?php if ($company_logo_url !== ''): ?><img src="<?= customer_home_h($company_logo_url); ?>" alt="<?= customer_home_h($company_name); ?>"><?php endif; ?>
                                <span class="ch-kicker"><?= customer_home_h($company_name); ?> · CUSTOMER HOME</span>
                            </div>
                            <h1>Welcome, <?= customer_home_h($customer_name); ?></h1>
                            <p>Pick up where you left off. Your saved cart stays with your session, and you can continue browsing this company's products and services below.</p>
                        </div>
                        <div class="ch-cart-pill"><i class="fa-solid fa-bag-shopping"></i> <?= (int)$cartLineCount; ?> cart line(s) · <?= (int)$cartQuantity; ?> item(s)</div>
                    </section>

                    <?php if (is_array($flash) && !empty($flash['message'])): ?>
                        <div class="ch-alert <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error'; ?>"><?= customer_home_h($flash['message']); ?></div>
                    <?php endif; ?>

                    <?php if (is_array($lastSalesRequest) && !empty($lastSalesRequest['number'])): ?>
                        <section class="ch-cart" style="margin-bottom:18px;padding:17px">
                            <strong style="display:block;color:#246b40;font-size:14px"><i class="fa-solid fa-circle-check"></i> Sales request received</strong>
                            <p style="font-size:12px;color:#65717d;margin:8px 0">Reference: <strong><?= customer_home_h($lastSalesRequest['number']); ?></strong></p>
                            <p style="font-size:11px;color:#87919b;margin:0">Request type: <?= customer_home_h($lastSalesRequest['type'] ?? 'Sales request'); ?> · <?= (int)($lastSalesRequest['item_count'] ?? 0); ?> line(s). Keep this reference for follow-up with the company.</p>
                        </section>
                    <?php endif; ?>

                    <?php if ($cartItems): ?>
                        <section id="customer-cart">
                            <div class="ch-heading">
                                <div><h2>Your cart</h2><p>These are the items carried over from your marketplace session.</p></div>
                                <span class="ch-cart-pill"><?= (int)$cartQuantity; ?> item(s)</span>
                            </div>
                            <div class="ch-cart">
                                <div class="ch-cart-head"><strong><i class="fa-solid fa-cart-shopping"></i> Cart items</strong><span><?= (int)$cartLineCount; ?> line(s)</span></div>
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="ch-cart-row">
                                        <div class="ch-thumb"><?php if ($item['image'] !== ''): ?><img src="<?= customer_home_h($item['image']); ?>" alt=""><?php else: ?><i class="fa-solid <?= $item['type'] === 'service' ? 'fa-screwdriver-wrench' : 'fa-box'; ?>"></i><?php endif; ?></div>
                                        <div>
                                            <div class="ch-item-name"><?= customer_home_h($item['name']); ?></div>
                                            <div class="ch-meta"><?= $item['type'] === 'product' ? 'Product' : 'Service'; ?> · <?php if ($item['type'] === 'service' && $item['pricing_type'] === 'quotation'): ?>Request a quotation<?php else: ?><?= customer_home_money($item['price']); ?> each<?php endif; ?></div>
                                            <div class="ch-actions">
                                                <form method="post" class="ch-actions">
                                                    <input type="hidden" name="csrf_token" value="<?= customer_home_h($csrf); ?>">
                                                    <input type="hidden" name="action" value="update_cart">
                                                    <input type="hidden" name="cart_key" value="<?= customer_home_h($item['key']); ?>">
                                                    <input type="number" name="quantity" min="0" max="<?= $item['type'] === 'product' ? max(1, (int)$item['stock']) : 99; ?>" value="<?= (int)$item['quantity']; ?>" aria-label="Quantity">
                                                    <button class="ch-button secondary" type="submit">Update</button>
                                                </form>
                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= customer_home_h($csrf); ?>">
                                                    <input type="hidden" name="action" value="remove_cart_item">
                                                    <input type="hidden" name="cart_key" value="<?= customer_home_h($item['key']); ?>">
                                                    <button class="ch-button danger" type="submit">Remove</button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="ch-line-total"><?= ($item['type'] === 'service' && $item['pricing_type'] === 'quotation') ? 'Quote' : customer_home_money($item['line_total']); ?></div>
                                        <?php if ($item['type'] === 'service'): ?>
                                            <form class="ch-service-details" method="post">
                                                <input type="hidden" name="csrf_token" value="<?= customer_home_h($csrf); ?>">
                                                <input type="hidden" name="action" value="save_service_note">
                                                <input type="hidden" name="cart_key" value="<?= customer_home_h($item['key']); ?>">
                                                <label>Work required</label>
                                                <textarea name="required_work" placeholder="Describe what you need done"><?= customer_home_h($item['required_work']); ?></textarea>
                                                <label>Additional note</label>
                                                <textarea name="service_note" placeholder="Add any other instructions"><?= customer_home_h($item['service_note']); ?></textarea>
                                                <label>Preferred date</label>
                                                <input type="date" name="preferred_date" value="<?= customer_home_h($item['preferred_date']); ?>">
                                                <label>Service location</label>
                                                <input type="text" name="service_location" value="<?= customer_home_h($item['service_location']); ?>" placeholder="Where should the service be carried out?">
                                                <div style="margin-top:9px"><button class="ch-button secondary" type="submit">Save service details</button></div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="ch-summary">
                                    <div class="ch-sumline"><span>Products subtotal</span><strong><?= customer_home_money($productSubtotal); ?></strong></div>
                                    <div class="ch-sumline"><span>Fixed-price services</span><strong><?= customer_home_money($serviceSubtotal); ?></strong></div>
                                    <div class="ch-sumline"><span>Services requiring quotation</span><strong><?= (int)$quotationCount; ?></strong></div>
                                    <div class="ch-sumline ch-total"><span>Current total</span><strong><?= customer_home_money($cartTotal); ?></strong></div>
                                    <p class="ch-note">This is an estimated cart value. Sending a sales request does not confirm an order, reserve stock, or require payment. The company will review your request and respond with availability, final pricing or a quotation.</p>
                                    <form method="post" style="margin-top:16px;padding:14px;background:#fff;border:1px solid #e7ebef;border-radius:10px">
                                        <input type="hidden" name="csrf_token" value="<?= customer_home_h($csrf); ?>">
                                        <input type="hidden" name="rfq_token" value="<?= customer_home_h($rfqToken); ?>">
                                        <input type="hidden" name="action" value="submit_sales_request">
                                        <label for="customerSalesNote" style="display:block;font-size:11px;font-weight:800;color:#536170;margin-bottom:6px">Message to the sales team (optional)</label>
                                        <textarea id="customerSalesNote" name="customer_note" maxlength="5000" rows="3" placeholder="Add delivery details, specifications, quantities or any other request..." style="width:100%;border:1px solid #dfe4e8;border-radius:7px;padding:10px;font:inherit;font-size:12px;resize:vertical"></textarea>
                                        <button type="submit" class="ch-button" style="margin-top:12px;width:100%;padding:12px" onclick="return confirm('Send this cart to the company as a sales request / RFQ?')">
                                            <i class="fa-solid fa-paper-plane"></i> Send sales request / RFQ
                                        </button>
                                    </form>
                                    <form method="post" style="margin-top:12px">
                                        <input type="hidden" name="csrf_token" value="<?= customer_home_h($csrf); ?>">
                                        <input type="hidden" name="action" value="clear_cart">
                                        <button type="submit" class="ch-button danger" onclick="return confirm('Clear all items from your cart?')">Clear cart</button>
                                    </form>
                                </div>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="ch-heading">
                            <div><h2>Explore the marketplace</h2><p>Your cart is empty. Browse the products and services published by this business.</p></div>
                        </section>
                    <?php endif; ?>

                    <section id="customer-marketplace">
                        <div class="ch-heading">
                            <div><h2><?= $cartItems ? 'Continue browsing' : 'Available products and services'; ?></h2><p>Choose an item to add it to your session cart.</p></div>
                        </div>
                        <form method="get" class="ch-search">
                            <input type="search" name="q" value="<?= customer_home_h($search); ?>" placeholder="Search products and services...">
                            <button type="submit" class="ch-button">Search</button>
                            <?php if ($search !== ''): ?><a class="ch-button secondary" href="index.php" style="text-decoration:none">Clear</a><?php endif; ?>
                        </form>

                        <div class="ch-heading"><div><h2>Products</h2><p><?= count($products); ?> product(s) available in this catalogue view.</p></div></div>
                        <div class="ch-grid">
                            <?php if (!$products): ?><div class="ch-empty-grid">No matching products are currently listed.</div><?php endif; ?>
                            <?php foreach ($products as $product):
                                $pid = (int)$product['id'];
                                $image = $productImageMap[$pid] ?? customer_home_image_url($product['image_path'] ?? '');
                                $stock = max(0, (int)$product['total_stock']);
                            ?>
                                <article class="ch-card">
                                    <div class="ch-image"><?php if ($image !== ''): ?><img src="<?= customer_home_h($image); ?>" alt="<?= customer_home_h($product['name']); ?>"><?php else: ?><i class="fa-solid fa-box-open"></i><?php endif; ?></div>
                                    <div class="ch-card-body">
                                        <span class="ch-tag"><?= customer_home_h($product['category_name']); ?></span>
                                        <h3><?= customer_home_h($product['name']); ?></h3>
                                        <p class="ch-description"><?= customer_home_h(customer_home_excerpt($product['description'], 115)); ?></p>
                                        <div class="ch-price"><?= customer_home_money($product['selling_price']); ?></div>
                                        <div class="ch-sub"><?= $stock > 0 ? $stock . ' in stock' : 'Currently out of stock'; ?><?= $product['unit_name'] !== '' ? ' · ' . customer_home_h($product['unit_name']) : ''; ?></div>
                                        <div class="ch-card-foot">
                                            <span class="ch-stock"><?= $stock > 0 ? 'Available' : 'Unavailable'; ?></span>
                                            <form method="post" class="ch-add">
                                                <input type="hidden" name="csrf_token" value="<?= customer_home_h($csrf); ?>">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="item_type" value="product">
                                                <input type="hidden" name="item_id" value="<?= $pid; ?>">
                                                <input type="number" name="quantity" min="1" max="<?= max(1, $stock); ?>" value="1" aria-label="Quantity" <?= $stock < 1 ? 'disabled' : ''; ?>>
                                                <button type="submit" class="ch-button" <?= $stock < 1 ? 'disabled style="opacity:.45;cursor:not-allowed"' : ''; ?>>Add to cart</button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="ch-heading"><div><h2>Services</h2><p><?= count($services); ?> service(s) available in this catalogue view.</p></div></div>
                        <div class="ch-grid">
                            <?php if (!$services): ?><div class="ch-empty-grid">No matching services are currently listed.</div><?php endif; ?>
                            <?php foreach ($services as $service):
                                $sid = (int)$service['id'];
                                $serviceImage = $serviceImageMap[$sid] ?? '';
                                $fixed = $service['pricing_type'] === 'fixed';
                            ?>
                                <article class="ch-card">
                                    <div class="ch-image"><?php if ($serviceImage !== ''): ?><img src="<?= customer_home_h($serviceImage); ?>" alt="<?= customer_home_h($service['service_name']); ?>"><?php else: ?><i class="fa-solid fa-screwdriver-wrench"></i><?php endif; ?></div>
                                    <div class="ch-card-body">
                                        <span class="ch-tag"><?= customer_home_h($service['category_name']); ?></span>
                                        <h3><?= customer_home_h($service['service_name']); ?></h3>
                                        <p class="ch-description"><?= customer_home_h(customer_home_excerpt($service['description'], 115)); ?></p>
                                        <div class="ch-price"><?= $fixed ? customer_home_money($service['fixed_price']) : 'Request a quote'; ?></div>
                                        <div class="ch-sub"><?= $fixed ? 'Fixed service price' : 'Price confirmed after reviewing your request'; ?></div>
                                        <div class="ch-card-foot">
                                            <span class="ch-stock">Available service</span>
                                            <form method="post" class="ch-add">
                                                <input type="hidden" name="csrf_token" value="<?= customer_home_h($csrf); ?>">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="item_type" value="service">
                                                <input type="hidden" name="item_id" value="<?= $sid; ?>">
                                                <input type="number" name="quantity" min="1" max="99" value="1" aria-label="Quantity">
                                                <button type="submit" class="ch-button">Add service</button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <script defer src="bundle.js"></script>
</body>
</html>
