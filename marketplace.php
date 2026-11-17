<?php
/*
|--------------------------------------------------------------------------
| SINGLE-COMPANY CUSTOMER MARKETPLACE
|--------------------------------------------------------------------------
| Uses the existing ERP catalogue:
| products, product_images, product_categories, units, stocks, services,
| job_categories, companies and customers.
|
| The marketplace only manages the catalogue and the session cart.
| Customer registration, login, OTP and final checkout belong to the
| separate customer application. Cart items are handed off in the session.
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Africa/Lagos');


require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/company_data.php';



if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Marketplace database connection is unavailable.');
}

$company_id = isset($company_id) ? (int)$company_id : 0;
if ($company_id <= 0) {
    http_response_code(404);
    exit('This marketplace could not identify its company. Please check company_data.php.');
}

// Cart handoff is handled by this page, then redirected to customer_auth.php.
$marketplaceCartSubmitUrl = 'marketplace.php';

function mp_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mp_money($value)
{
    return '₦' . number_format((float)$value, 2);
}

function mp_table_exists($pdo, $table)
{
    // Check the active database directly. SHOW TABLES can return an empty
    // result for restricted database users even when the table is present.
    $allowed = [
        'marketplace_service_settings',
        'product_images', 'product_media', 'product_features', 'service_image'
    ];
    if (!in_array($table, $allowed, true)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }
    } catch (Exception $e) {
        error_log('Marketplace table check (information_schema) error for ' . $table . ': ' . $e->getMessage());
    }

    // Fallback: test whether the active database user can access the table.
    try {
        $pdo->query("SELECT 1 FROM `" . $table . "` LIMIT 0");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function mp_csrf_token()
{
    if (empty($_SESSION['marketplace_csrf'])) {
        $_SESSION['marketplace_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['marketplace_csrf'];
}

function mp_verify_csrf()
{
    $sent = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    $saved = isset($_SESSION['marketplace_csrf']) ? (string)$_SESSION['marketplace_csrf'] : '';
    return $sent !== '' && $saved !== '' && hash_equals($saved, $sent);
}

function mp_redirect($query = '')
{
    if (strpos(ltrim($query, '?'), 'account=') === 0) {
        header('Location: customer_auth.php?' . ltrim($query, '?'));
        exit;
    }
    $url = 'marketplace.php';
    if ($query !== '') { $url .= '?' . ltrim($query, '?'); }
    header('Location: ' . $url);
    exit;
}

function mp_page_url($pageType, $pageNumber, $search, $view, $category)
{
    $params = [];
    if ($view !== 'all') $params['type'] = $view;
    if ($search !== '') $params['q'] = $search;
    if ($category !== '') $params['category'] = $category;
    $params[$pageType . '_page'] = (int)$pageNumber;
    return 'marketplace.php?' . http_build_query($params);
}

function mp_set_flash($type, $message)
{
    $_SESSION['marketplace_flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function mp_get_flash()
{
    if (empty($_SESSION['marketplace_flash'])) {
        return null;
    }
    $flash = $_SESSION['marketplace_flash'];
    unset($_SESSION['marketplace_flash']);
    return $flash;
}

function mp_cart_count()
{
    $count = 0;
    if (!empty($_SESSION['marketplace_cart']) && is_array($_SESSION['marketplace_cart'])) {
        foreach ($_SESSION['marketplace_cart'] as $item) {
            $count += max(0, (int)(isset($item['quantity']) ? $item['quantity'] : 0));
        }
    }
    return $count;
}

function mp_image_url($storedPath)
{
    $storedPath = trim((string)$storedPath);
    if ($storedPath === '') return '';

    $storedPath = str_replace('\\', '/', $storedPath);
    if (preg_match('~^https?://~i', $storedPath)) return $storedPath;

    // Preserve paths already stored with an uploads prefix (including ../uploads).
    $candidates = [];
    if (strpos($storedPath, '../uploads/') === 0 || strpos($storedPath, 'uploads/') === 0) {
        $candidates[] = $storedPath;
    } else {
        $name = basename($storedPath);
        foreach ([
            // Marketplace and product-management pages may live in different
            // folders (for example /project and /hoa on the same host).
            'uploads/products/', 'uploads/product_images/', 'uploads/product/',
            '../uploads/products/', '../uploads/product_images/', '../uploads/product/',
            '../hoa/uploads/products/', '../hoa/uploads/product_images/', '../hoa/uploads/product/',
            'hoa/uploads/products/', 'hoa/uploads/product_images/', 'hoa/uploads/product/',
            '../uploads/', 'uploads/'
        ] as $folder) {
            $candidates[] = $folder . $name;
        }
        $candidates[] = $name;
    }

    $candidates = array_values(array_unique($candidates));
    foreach ($candidates as $candidate) {
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
        $full = (strpos($candidate, '../') === 0)
            ? dirname(__DIR__) . DIRECTORY_SEPARATOR . substr($relative, 3)
            : __DIR__ . DIRECTORY_SEPARATOR . $relative;
        if (is_file($full)) return $candidate;
    }

    // Keep the most likely public path as a fallback; the browser will report a broken URL
    // rather than silently dropping the gallery entry.
    return $candidates[0] ?? '';
}


/** Public catalogue text must not expose individual VIN, IMEI or serial identifiers. */
function mp_public_product_text($value)
{
    $value = (string)$value;
    $value = preg_replace('/^[^\r\n]*(?:\bVIN\b|vehicle\s+identification\s+number|IMEI|serial\s*(?:number|no\.?|#))\s*[:=#-][^\r\n]*$/im', '', $value);
    $value = preg_replace('/^[^\r\n]*(?:\bVIN\b|vehicle\s+identification\s+number|IMEI|serial\s*(?:number|no\.?|#))\s+[A-Z0-9-]{5,}[^\r\n]*$/im', '', (string)$value);
    return trim((string)$value);
}

function mp_is_private_identifier_label($label)
{
    return (bool)preg_match('/\b(vin|vehicle\s+identification\s+number|imei|serial\s*(?:number|no\.?|#)?)\b/i', (string)$label);
}

function mp_media_url($storedPath)
{
    $storedPath = trim((string)$storedPath);
    if ($storedPath === '') return '';
    $storedPath = str_replace('\\', '/', $storedPath);
    if (preg_match('~^https?://~i', $storedPath)) return $storedPath;

    $candidates = [];
    if (strpos($storedPath, '../uploads/') === 0 || strpos($storedPath, 'uploads/') === 0) {
        $candidates[] = $storedPath;
    } else {
        $name = basename($storedPath);
        foreach ([
            'uploads/products/', '../uploads/products/',
            'uploads/product_media/', '../uploads/product_media/',
            'uploads/products/videos/', '../uploads/products/videos/',
            'uploads/videos/', '../uploads/videos/',
            'uploads/', '../uploads/',
            '../hoa/uploads/products/', '../hoa/uploads/product_media/',
            '../hoa/uploads/videos/'
        ] as $folder) $candidates[] = $folder . $name;
        $candidates[] = $name;
    }

    foreach (array_values(array_unique($candidates)) as $candidate) {
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
        $full = (strpos($candidate, '../') === 0)
            ? dirname(__DIR__) . DIRECTORY_SEPARATOR . substr($relative, 3)
            : __DIR__ . DIRECTORY_SEPARATOR . $relative;
        if (is_file($full)) return $candidate;
    }
    return $candidates[0] ?? '';
}

function mp_get_product_media($pdo, $companyId, $productIds, $legacyImages = [])
{
    $result = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$productIds))));
    if (!$ids) return $result;

    if (mp_table_exists($pdo, 'product_media')) {
        try {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("
                SELECT product_id, media_type, media_path, thumbnail_path, mime_type,
                       display_order, is_primary
                FROM product_media
                WHERE company_id = ? AND product_id IN ($marks)
                  AND media_type IN ('image','video')
                ORDER BY product_id ASC, is_primary DESC, display_order ASC, id ASC
            ");
            $stmt->execute(array_merge([(int)$companyId], $ids));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $type = ($row['media_type'] === 'video') ? 'video' : 'image';
                $src = mp_media_url($row['media_path'] ?? '');
                if ($src === '') continue;
                $pid = (int)$row['product_id'];
                $thumb = mp_media_url($row['thumbnail_path'] ?? '');
                if ($type === 'image' && $thumb === '') $thumb = $src;

                $duplicate = false;
                foreach ($result[$pid] ?? [] as $existing) {
                    if ($type === 'image' && $existing['type'] === 'image' && $existing['src'] === $src) {
                        $duplicate = true;
                        break;
                    }
                }
                if (!$duplicate) $result[$pid][] = [
                    'type' => $type, 'src' => $src, 'thumbnail' => $thumb,
                    'mime' => (string)($row['mime_type'] ?? '')
                ];
            }
        } catch (Exception $e) {
            error_log('Marketplace product media error: ' . $e->getMessage());
        }
    }

    // Merge legacy images after product_media so its primary selection and
    // display order remain authoritative.
    foreach ($legacyImages as $productId => $urls) {
        $pid = (int)$productId;
        foreach ((array)$urls as $url) {
            if ($url === '') continue;
            $duplicate = false;
            foreach ($result[$pid] ?? [] as $existing) {
                if ($existing['type'] === 'image' && $existing['src'] === $url) {
                    $duplicate = true;
                    break;
                }
            }
            if (!$duplicate) $result[$pid][] = [
                'type' => 'image', 'src' => $url, 'thumbnail' => $url, 'mime' => ''
            ];
        }
    }
    return $result;
}

function mp_get_product($pdo, $companyId, $productId)
{
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.company_id,
            p.name,
            p.description,
            p.selling_price,
            p.category_id,
            p.stock_type,
            p.unit_id,
            COALESCE(pc.name, 'Products') AS category_name,
            COALESCE(u.unit_name, '') AS unit_name,
            COALESCE(stock.total_stock, 0) AS total_stock,
            img.image_path
        FROM products p
        LEFT JOIN product_categories pc
            ON pc.id = p.category_id
           AND pc.company_id = p.company_id
        LEFT JOIN units u
            ON u.id = p.unit_id
           AND u.company_id = p.company_id
        LEFT JOIN (
            SELECT product_id, company_id, SUM(COALESCE(quantity, 0)) AS total_stock
            FROM stocks
            GROUP BY product_id, company_id
        ) stock
            ON stock.product_id = p.id
           AND stock.company_id = p.company_id
        LEFT JOIN (
            SELECT pi.product_id, MIN(pi.id) AS first_image_id
            FROM product_images pi
            GROUP BY pi.product_id
        ) first_img
            ON first_img.product_id = p.id
        LEFT JOIN product_images img
            ON img.id = first_img.first_image_id
        WHERE p.id = ?
          AND p.company_id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$productId, (int)$companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function mp_service_image_url($storedPath)
{
    $storedPath = trim((string)$storedPath);
    if ($storedPath === '') return '';
    $storedPath = str_replace('\\', '/', $storedPath);
    if (preg_match('~^https?://~i', $storedPath)) return $storedPath;

    $storedPath = ltrim($storedPath, '/');
    $candidates = [];
    if (strpos($storedPath, '../uploads/') === 0 || strpos($storedPath, 'uploads/') === 0) {
        $candidates[] = $storedPath;
    } else {
        $name = basename($storedPath);
        // Service registration stores paths as ../uploads/services/<file>.
        $candidates[] = '../uploads/services/' . $name;
        $candidates[] = 'uploads/services/' . $name;
        $candidates[] = 'uploads/service_images/' . $name;
        $candidates[] = 'uploads/' . $name;
        $candidates[] = $name;
    }
    foreach ($candidates as $candidate) {
        $base = (strpos($candidate, '../') === 0) ? dirname(__DIR__) : __DIR__;
        $relative = (strpos($candidate, '../') === 0) ? substr($candidate, 3) : $candidate;
        $full = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($full)) return $candidate;
    }
    return $candidates[0] ?? '';
}

function mp_get_product_images($pdo, $productIds)
{
    $result = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$productIds))));
    if (!$ids || !mp_table_exists($pdo, 'product_images')) return $result;

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT product_id, image_path FROM product_images WHERE product_id IN ($placeholders) ORDER BY id ASC");
        $stmt->execute($ids);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = mp_image_url($row['image_path'] ?? '');
            if ($url !== '') $result[(int)$row['product_id']][] = $url;
        }
    } catch (Exception $e) {
        error_log('Marketplace product gallery error: ' . $e->getMessage());
    }
    return $result;
}

function mp_get_product_features($pdo, $productIds)
{
    $result = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$productIds))));
    if (!$ids || !mp_table_exists($pdo, 'product_features')) return $result;

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT product_id, feature_name, feature_value FROM product_features WHERE product_id IN ($placeholders) ORDER BY id ASC");
        $stmt->execute($ids);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productId = (int)$row['product_id'];
            $name = trim((string)$row['feature_name']);
            $value = trim((string)$row['feature_value']);
            if (mp_is_private_identifier_label($name) || mp_is_private_identifier_label($value)) {
                continue;
            }
            $name = mp_public_product_text($name);
            $value = mp_public_product_text($value);
            if ($name !== '' || $value !== '') {
                $result[$productId][] = ['name' => $name, 'value' => $value];
            }
        }
    } catch (Exception $e) {
        error_log('Marketplace product features error: ' . $e->getMessage());
    }
    return $result;
}

function mp_get_optional_service_images($pdo, $serviceIds)
{
    $result = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)$serviceIds))));
    if (!$ids || !mp_table_exists($pdo, 'service_image')) return $result;

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT service_id, image_path FROM service_image WHERE service_id IN ($placeholders) ORDER BY id ASC");
        $stmt->execute($ids);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url = mp_service_image_url($row['image_path'] ?? '');
            if ($url !== '') $result[(int)$row['service_id']][] = $url;
        }
    } catch (Exception $e) {
        // Service image storage is optional in the current ERP database.
        error_log('Marketplace service gallery error: ' . $e->getMessage());
    }
    return $result;
}

function mp_get_service($pdo, $companyId, $serviceId)
{
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.company_id,
            s.service_name,
            s.description,
            s.category_id,
            COALESCE(jc.name, 'Professional Services') AS category_name
        FROM services s
        LEFT JOIN job_categories jc
            ON jc.id = s.category_id
           AND jc.company_id = s.company_id
        WHERE s.id = ?
          AND s.company_id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$serviceId, (int)$companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function mp_service_pricing($pdo, $companyId, $serviceId)
{
    try {
        $stmt = $pdo->prepare("SELECT pricing_type, fixed_price, is_available FROM marketplace_service_settings WHERE company_id = ? AND service_id = ? LIMIT 1");
        $stmt->execute([(int)$companyId, (int)$serviceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return ['pricing_type' => 'quotation', 'fixed_price' => 0.0, 'is_available' => 1];
        return [
            'pricing_type' => $row['pricing_type'] === 'fixed' ? 'fixed' : 'quotation',
            'fixed_price' => max(0, (float)$row['fixed_price']),
            'is_available' => (int)$row['is_available']
        ];
    } catch (Exception $e) {
        return ['pricing_type' => 'quotation', 'fixed_price' => 0.0, 'is_available' => 1];
    }
}

if (!isset($_SESSION['marketplace_cart']) || !is_array($_SESSION['marketplace_cart'])) {
    $_SESSION['marketplace_cart'] = [];
}

$customerId = 0;
$customerName = '';

// Marketplace cart data is held in the PHP session and handed to customer_auth.php.
$companyStmt = $pdo->prepare("
    SELECT company_name, company_logo, company_mobile, company_email,
           company_customer_care, company_domain, location_id
    FROM companies
    WHERE id = ?
    LIMIT 1
");
$companyStmt->execute([$company_id]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    http_response_code(404);
    exit('Company storefront not found.');
}

$companyName = $company['company_name'];
$companyLogo = trim((string)$company['company_logo']);
$logoPath = $companyLogo !== '' ? 'uploads/logos/' . basename($companyLogo) : '';
$defaultLocationId = (int)$company['location_id'];
if ($defaultLocationId <= 0) {
    $locationStmt = $pdo->prepare("
        SELECT id FROM locations
        WHERE company_id = ? AND status = 1
        ORDER BY id ASC LIMIT 1
    ");
    $locationStmt->execute([$company_id]);
    $defaultLocationId = (int)$locationStmt->fetchColumn();
}

$flash = null;

// Availability is checked against the active marketplace company, never globally.
$productAvailabilityStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE company_id = ?");
$productAvailabilityStmt->execute([$company_id]);
$companyHasProducts = ((int)$productAvailabilityStmt->fetchColumn() > 0);
$serviceAvailabilityStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE company_id = ?");
$serviceAvailabilityStmt->execute([$company_id]);
$companyHasServices = ((int)$serviceAvailabilityStmt->fetchColumn() > 0);

/* ----------------------------------------------------------------------
   POST ACTIONS
---------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mp_verify_csrf()) {
        mp_set_flash('error', 'Your session security token expired. Please try again.');
        mp_redirect();
    }

    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

    // Account registration/login and order submission belong to customer_auth.php
    // and the customer application, not to the public marketplace.
    $marketplaceActions = [
        'handoff_cart', 'add_to_cart', 'save_service_note',
        'update_cart', 'remove_cart_item', 'clear_cart'
    ];
    if (!in_array($action, $marketplaceActions, true)) {
        mp_set_flash('error', 'Please continue through the customer account page.');
        header('Location: customer_auth.php?account=login');
        exit;
    }

    if ($action === 'handoff_cart') {
        // The cart already lives in the PHP session. This default handler is used
        // only while the configurable handoff URL points back to this page.
        if (empty($_SESSION['marketplace_cart'])) {
            mp_set_flash('error', 'Your cart is empty. Add an item before continuing.');
            mp_redirect();
        }
        // The marketplace only hands the session cart to the customer account flow.
        // It never performs customer checkout, whether the customer is signed in or not.
        header('Location: customer_auth.php?account=login');
        exit;
    }

    if ($action === 'add_to_cart') {
        $type = isset($_POST['item_type']) ? (string)$_POST['item_type'] : '';
        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $quantity = max(1, min(99, $quantity));

        if ($type === 'product') {
            if (!$companyHasProducts) { mp_set_flash('error', 'This company has not published any products yet.'); mp_redirect(); }
            $product = mp_get_product($pdo, $company_id, $id);
            if (!$product) {
                mp_set_flash('error', 'That product is no longer available.');
                mp_redirect();
            }
            $stock = max(0, (int)$product['total_stock']);
            if ($stock < 1) {
                mp_set_flash('error', 'This product is currently out of stock.');
                mp_redirect();
            }

            $key = 'product-' . $id;
            $existing = isset($_SESSION['marketplace_cart'][$key]['quantity']) ? (int)$_SESSION['marketplace_cart'][$key]['quantity'] : 0;
            $_SESSION['marketplace_cart'][$key] = ['type' => 'product', 'id' => $id, 'quantity' => min($stock, $existing + $quantity)];

            mp_set_flash('success', 'Product added to your cart.');
        } elseif ($type === 'service') {
            if (!$companyHasServices) { mp_set_flash('error', 'This company has not published any services yet.'); mp_redirect(); }
            $service = mp_get_service($pdo, $company_id, $id);
            if (!$service) {
                mp_set_flash('error', 'That service is no longer available.');
                mp_redirect();
            }
            $pricing = mp_service_pricing($pdo, $company_id, $id);
            if (!$pricing['is_available']) { mp_set_flash('error', 'This service is currently unavailable.'); mp_redirect(); }
            $key = 'service-' . bin2hex(random_bytes(8));
            $_SESSION['marketplace_cart'][$key] = [
                'type' => 'service', 'id' => $id, 'quantity' => $quantity,
                'line_key' => bin2hex(random_bytes(16)), 'service_note' => '', 'required_work' => '',
                'preferred_date' => '', 'service_location' => ''
            ];

            mp_set_flash('success', $pricing['pricing_type'] === 'fixed' ? 'Service added to your cart.' : 'Service added. A quotation will be prepared for this request.');
        } else {
            mp_set_flash('error', 'Invalid marketplace item.');
        }
        mp_redirect();
    }

    if ($action === 'save_service_note') {
        $key = isset($_POST['cart_key']) ? (string)$_POST['cart_key'] : '';
        $note = trim((string)($_POST['service_note'] ?? ''));
        if (isset($_SESSION['marketplace_cart'][$key])
            && ($_SESSION['marketplace_cart'][$key]['type'] ?? '') === 'service') {
            $_SESSION['marketplace_cart'][$key]['service_note'] = substr($note, 0, 2000);
            $_SESSION['marketplace_cart'][$key]['required_work'] = substr(trim((string)($_POST['required_work'] ?? '')), 0, 2000);
            $_SESSION['marketplace_cart'][$key]['preferred_date'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['preferred_date'] ?? '')) ? (string)$_POST['preferred_date'] : '';
            $_SESSION['marketplace_cart'][$key]['service_location'] = substr(trim((string)($_POST['service_location'] ?? '')), 0, 500);

            mp_set_flash('success', 'Your individual service request details have been saved.');
        }
        mp_redirect();
    }

    if ($action === 'update_cart') {
        $key = isset($_POST['cart_key']) ? (string)$_POST['cart_key'] : '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        if (isset($_SESSION['marketplace_cart'][$key])) {
            if ($quantity <= 0) {
                unset($_SESSION['marketplace_cart'][$key]);
            } else {
                $quantity = min(99, $quantity);
                $entry = $_SESSION['marketplace_cart'][$key];
                if (isset($entry['type'], $entry['id']) && $entry['type'] === 'product') {
                    $product = mp_get_product($pdo, $company_id, (int)$entry['id']);
                    $quantity = $product
                        ? min($quantity, max(0, (int)$product['total_stock']))
                        : 0;
                }
                if ($quantity <= 0) {
                    unset($_SESSION['marketplace_cart'][$key]);
                } else {
                    $_SESSION['marketplace_cart'][$key]['quantity'] = $quantity;
                }
            }
        }

        mp_redirect();
    }

    if ($action === 'remove_cart_item') {
        $key = isset($_POST['cart_key']) ? (string)$_POST['cart_key'] : '';
        unset($_SESSION['marketplace_cart'][$key]);

        mp_set_flash('success', 'Item removed from your cart.');
        mp_redirect();
    }

    if ($action === 'clear_cart') {
        $_SESSION['marketplace_cart'] = [];

        mp_set_flash('success', 'Your cart has been cleared.');
        mp_redirect();
    }


}

/* ----------------------------------------------------------------------
   CATALOGUE
---------------------------------------------------------------------- */
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');
$view = isset($_GET['type']) ? (string)$_GET['type'] : 'all';
if (!in_array($view, ['all', 'product', 'service'], true)) {
    $view = 'all';
}
$categoryFilter = isset($_GET['category']) ? (string)$_GET['category'] : '';

$productSql = "
    SELECT
        p.id, p.name, p.description, p.selling_price, p.category_id,
        p.stock_type, p.unit_id,
        COALESCE(pc.name, 'Products') AS category_name,
        COALESCE(u.unit_name, '') AS unit_name,
        COALESCE(stock.total_stock, 0) AS total_stock,
        img.image_path
    FROM products p
    LEFT JOIN product_categories pc
        ON pc.id = p.category_id AND pc.company_id = p.company_id
    LEFT JOIN units u
        ON u.id = p.unit_id AND u.company_id = p.company_id
    LEFT JOIN (
        SELECT product_id, company_id, SUM(COALESCE(quantity,0)) AS total_stock
        FROM stocks GROUP BY product_id, company_id
    ) stock
        ON stock.product_id = p.id AND stock.company_id = p.company_id
    LEFT JOIN (
        SELECT product_id, MIN(id) AS first_image_id
        FROM product_images GROUP BY product_id
    ) first_img ON first_img.product_id = p.id
    LEFT JOIN product_images img ON img.id = first_img.first_image_id
    WHERE p.company_id = ?
";
$productParams = [$company_id];
if ($search !== '') {
    $productSql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.barcode LIKE ?)";
    $like = '%' . $search . '%';
    $productParams[] = $like;
    $productParams[] = $like;
    $productParams[] = $like;
}
if ($categoryFilter !== '' && strpos($categoryFilter, 'p:') === 0) {
    $productSql .= " AND p.category_id = ?";
    $productParams[] = (int)substr($categoryFilter, 2);
}
// Paginate products independently from services so each catalogue section
// has its own Next/Previous controls.
$itemsPerPage = 12;
$productPage = max(1, (int)($_GET['product_page'] ?? 1));
$productCountStmt = $pdo->prepare("SELECT COUNT(*) FROM (" . $productSql . ") marketplace_product_count");
$productCountStmt->execute($productParams);
$productTotal = (int)$productCountStmt->fetchColumn();
$productPages = max(1, (int)ceil($productTotal / $itemsPerPage));
$productPage = min($productPage, $productPages);
$productOffset = ($productPage - 1) * $itemsPerPage;
$productSql .= " ORDER BY p.id DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$productOffset;
$productStmt = $pdo->prepare($productSql);
$productStmt->execute($productParams);
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
$productImagesById = mp_get_product_images($pdo, array_column($products, 'id'));
$productMediaById = mp_get_product_media($pdo, $company_id, array_column($products, 'id'), $productImagesById);
$productFeaturesById = mp_get_product_features($pdo, array_column($products, 'id'));

$serviceCountStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE company_id = ?");
$serviceCountStmt->execute([$company_id]);
$companyHasServices = ((int)$serviceCountStmt->fetchColumn() > 0);

$serviceSql = "
    SELECT
        s.id, s.service_name, s.description, s.category_id,
        COALESCE(jc.name, 'Professional Services') AS category_name,
        COALESCE(mss.pricing_type, 'quotation') AS pricing_type,
        COALESCE(mss.fixed_price, 0) AS fixed_price,
        COALESCE(mss.is_available, 1) AS is_available
    FROM services s
    LEFT JOIN marketplace_service_settings mss
        ON mss.service_id = s.id AND mss.company_id = s.company_id
    LEFT JOIN job_categories jc
        ON jc.id = s.category_id AND jc.company_id = s.company_id
    WHERE s.company_id = ? AND COALESCE(mss.is_available, 1) = 1
";
$serviceParams = [$company_id];
if ($search !== '') {
    $serviceSql .= " AND (s.service_name LIKE ? OR s.description LIKE ?)";
    $like = '%' . $search . '%';
    $serviceParams[] = $like;
    $serviceParams[] = $like;
}
if ($categoryFilter !== '' && strpos($categoryFilter, 's:') === 0) {
    $serviceSql .= " AND s.category_id = ?";
    $serviceParams[] = (int)substr($categoryFilter, 2);
}
$servicePage = max(1, (int)($_GET['service_page'] ?? 1));
$serviceCountQuery = $pdo->prepare("SELECT COUNT(*) FROM (" . $serviceSql . ") marketplace_service_count");
$serviceCountQuery->execute($serviceParams);
$serviceTotal = (int)$serviceCountQuery->fetchColumn();
$servicePages = max(1, (int)ceil($serviceTotal / $itemsPerPage));
$servicePage = min($servicePage, $servicePages);
$serviceOffset = ($servicePage - 1) * $itemsPerPage;
$serviceSql .= " ORDER BY s.id DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$serviceOffset;
$serviceStmt = $pdo->prepare($serviceSql);
$serviceStmt->execute($serviceParams);
$services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);
$serviceImagesById = mp_get_optional_service_images($pdo, array_column($services, 'id'));
if (!$companyHasServices && $view === 'service') { $view = 'all'; }

$categories = [];
$categoryStmt = $pdo->prepare("
    SELECT id, name FROM product_categories
    WHERE company_id = ? ORDER BY name ASC
");
$categoryStmt->execute([$company_id]);
foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $cat) {
    $categories[] = [
        'key' => 'p:' . $cat['id'],
        'name' => $cat['name'],
        'type' => 'product'
    ];
}
$serviceCategoryStmt = $pdo->prepare("
    SELECT DISTINCT jc.id, jc.name
    FROM job_categories jc
    INNER JOIN services s
        ON s.category_id = jc.id AND s.company_id = jc.company_id
    WHERE jc.company_id = ?
    ORDER BY jc.name ASC
");
$serviceCategoryStmt->execute([$company_id]);
foreach ($serviceCategoryStmt->fetchAll(PDO::FETCH_ASSOC) as $cat) {
    $categories[] = [
        'key' => 's:' . $cat['id'],
        'name' => $cat['name'],
        'type' => 'service'
    ];
}

/* ----------------------------------------------------------------------
   HYDRATE CART
---------------------------------------------------------------------- */
$cartItems = [];
$cartSubtotal = 0.0;
$cartProductSubtotal = 0.0;
$cartServiceSubtotal = 0.0;
$cartServiceCount = 0;
$cartProductCount = 0;

foreach ($_SESSION['marketplace_cart'] as $key => $entry) {
    if (empty($entry['type']) || empty($entry['id'])) {
        unset($_SESSION['marketplace_cart'][$key]);
        continue;
    }
    $qty = max(1, min(99, (int)$entry['quantity']));

    if ($entry['type'] === 'product') {
        $item = mp_get_product($pdo, $company_id, (int)$entry['id']);
        if (!$item) {
            unset($_SESSION['marketplace_cart'][$key]);
            continue;
        }
        $stock = max(0, (int)$item['total_stock']);
        if ($stock < 1) {
            $qty = 0;
        } else {
            $qty = min($qty, $stock);
        }
        if ($qty < 1) {
            unset($_SESSION['marketplace_cart'][$key]);
            continue;
        }
        $_SESSION['marketplace_cart'][$key]['quantity'] = $qty;
        $price = max(0, (float)$item['selling_price']);
        $lineTotal = $price * $qty;
        $cartSubtotal += $lineTotal;
        $cartProductSubtotal += $lineTotal;
        $cartProductCount += $qty;
        $cartItems[] = [
            'key' => $key,
            'type' => 'product',
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'quantity' => $qty,
            'price' => $price,
            'line_total' => $lineTotal,
            'image' => mp_image_url($item['image_path']),
            'stock' => $stock
        ];
    } elseif ($entry['type'] === 'service') {
        $item = mp_get_service($pdo, $company_id, (int)$entry['id']);
        if (!$item) {
            unset($_SESSION['marketplace_cart'][$key]);
            continue;
        }
        $_SESSION['marketplace_cart'][$key]['quantity'] = $qty;
        $cartServiceCount += $qty;
        $pricing = mp_service_pricing($pdo, $company_id, (int)$item['id']);
        $servicePrice = $pricing['pricing_type'] === 'fixed' ? $pricing['fixed_price'] : 0.0;
        $serviceLineTotal = $servicePrice * $qty;
        $cartSubtotal += $serviceLineTotal;
        $cartServiceSubtotal += $serviceLineTotal;
        $cartItems[] = [
            'key' => $key, 'type' => 'service', 'id' => (int)$item['id'], 'name' => $item['service_name'],
            'quantity' => $qty, 'price' => $servicePrice, 'line_total' => $serviceLineTotal,
            'pricing_type' => $pricing['pricing_type'], 'image' => '', 'stock' => null,
            'service_note' => (string)($entry['service_note'] ?? ''),
            'required_work' => (string)($entry['required_work'] ?? ''),
            'preferred_date' => (string)($entry['preferred_date'] ?? ''),
            'service_location' => (string)($entry['service_location'] ?? '')
        ];
    }
}



$cartTotalCount = $cartProductCount + $cartServiceCount;
$cartHasServices = $cartServiceCount > 0;
// No customer dashboard, account form, order history, or checkout is rendered here.
// Customer account and final order handling belong to the separate customer application.
$accountView = '';
$showAccount = false;
$showCheckout = false;
$showMyOrders = false;
$myOrders = [];
$orderSuccess = false;
$lastOrder = null;

$flash = mp_get_flash();
$csrf = mp_csrf_token();

$displayProducts = ($view === 'all' || $view === 'product');
$displayServices = ($companyHasServices && ($view === 'all' || $view === 'service'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#f7f8fa">
<title><?= mp_h($companyName); ?> | Marketplace</title>
<link rel="stylesheet" href="fontawesome/css/all.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{
    --ink:#17212b;--muted:#718096;--line:#e8edf2;--paper:#f6f8fa;
    --white:#fff;--gold:#b58b45;--gold-dark:#8f6b31;--navy:#142330;
    --green:#16845b;--red:#c24141;--shadow:0 16px 45px rgba(22,34,46,.08);
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,"Segoe UI",Arial,sans-serif}
button,input,textarea,select{font:inherit}
a{text-decoration:none;color:inherit}
button{cursor:pointer}
.mp-topline{height:4px;background:linear-gradient(90deg,#8f6b31,#d6b46d,#8f6b31)}
.mp-header{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:40}
.mp-header-inner{max-width:1440px;margin:auto;padding:15px 4.5%;display:flex;align-items:center;gap:28px}
.mp-brand{display:flex;align-items:center;gap:12px;min-width:220px}
.mp-brand img{width:45px;height:45px;object-fit:contain}
.mp-brand-mark{width:44px;height:44px;border-radius:13px;background:var(--navy);color:#e3c17d;display:grid;place-items:center;font-size:18px}
.mp-brand-name{font-size:15px;font-weight:850;letter-spacing:-.3px;max-width:210px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mp-brand-caption{font-size:10px;color:var(--muted);margin-top:3px;letter-spacing:1.2px;text-transform:uppercase}
.mp-search{flex:1;position:relative;max-width:610px}
.mp-search input{width:100%;height:46px;border:1px solid var(--line);border-radius:12px;background:#f8fafb;padding:0 48px 0 16px;outline:none;font-size:13px}
.mp-search input:focus{border-color:#c9a968;box-shadow:0 0 0 3px rgba(181,139,69,.12)}
.mp-search button{position:absolute;right:5px;top:5px;width:36px;height:36px;border:0;border-radius:9px;background:var(--lightgrey);color:#fff}
.mp-header-actions{display:flex;align-items:center;gap:10px;margin-left:auto}
.mp-account-link,.mp-cart-link{height:43px;display:flex;align-items:center;gap:9px;padding:0 14px;border:1px solid var(--line);border-radius:11px;font-size:12px;font-weight:750;background:#fff}
.mp-cart-link{background:var(--navy);color:#fff;border-color:var(--navy)}
.mp-cart-count{background:#d7b66f;color:#1c2630;width:21px;height:21px;border-radius:50%;display:grid;place-items:center;font-size:10px;font-weight:900}
.mp-hero{max-width:1440px;margin:25px auto 0;padding:0 4.5%}
.mp-hero-panel{position:relative;overflow:hidden;min-height:250px;border-radius:22px;background:linear-gradient(110deg,#101e29 0%,#1b303c 58%,#35434a 100%);padding:44px 5%;display:flex;align-items:center;color:#fff;box-shadow:var(--shadow)}
.mp-hero-panel:before{content:"";position:absolute;width:430px;height:430px;border:1px solid rgba(221,190,128,.19);border-radius:50%;right:7%;top:-115px;box-shadow:0 0 0 35px rgba(255,255,255,.018),0 0 0 75px rgba(255,255,255,.014)}
.mp-hero-panel:after{content:"";position:absolute;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(201,164,92,.25),transparent 68%);right:18%;top:0}
.mp-hero-content{position:relative;z-index:2;max-width:650px}
.mp-eyebrow{font-size:10px;font-weight:850;letter-spacing:2.6px;text-transform:uppercase;color:#e0c17f;display:flex;align-items:center;gap:10px}
.mp-eyebrow:before{content:"";width:28px;height:1px;background:#c9a45c}
.mp-hero h1{font-size:clamp(30px,4vw,48px);line-height:1.08;letter-spacing:-1.8px;margin:17px 0 12px;font-weight:850}
.mp-hero p{font-size:14px;line-height:1.75;color:rgba(255,255,255,.73);max-width:570px;margin:0}
.mp-hero-tags{display:flex;flex-wrap:wrap;gap:9px;margin-top:23px}
.mp-hero-tags span{font-size:10px;padding:8px 12px;border:1px solid rgba(255,255,255,.16);border-radius:99px;color:#f3e6c9;background:rgba(255,255,255,.04)}
.mp-main{max-width:1440px;margin:0 auto;padding:34px 4.5% 70px}
.mp-section-head{display:flex;align-items:end;justify-content:space-between;gap:15px;margin-bottom:19px}
.mp-section-head h2{font-size:21px;margin:0;letter-spacing:-.5px}
.mp-section-head p{margin:6px 0 0;font-size:12px;color:var(--muted)}
.mp-count-label{font-size:11px;color:var(--muted)}
.mp-toolbar{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:23px}
.mp-tabs{display:flex;gap:7px;flex-wrap:wrap}
.mp-tab{border:1px solid var(--line);background:#fff;color:#52606d;border-radius:10px;padding:10px 15px;font-size:11px;font-weight:800}
.mp-tab.active{background:var(--navy);border-color:var(--navy);color:#fff}
.mp-category-select{height:39px;border:1px solid var(--line);border-radius:10px;background:#fff;padding:0 12px;color:#596775;font-size:11px;max-width:220px}
.mp-layout{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:27px;align-items:start}
.mp-catalogue{min-width:0}
.mp-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}
.mp-card{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease;display:flex;flex-direction:column;min-width:0}
.mp-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:#d7c49f}
.mp-card-image{height:185px;background:#f0f3f5;position:relative;display:grid;place-items:center;overflow:hidden}
.mp-card-image img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.mp-card:hover .mp-card-image img{transform:scale(1.045)}
.mp-image-fallback{width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(145deg,#e9edf0,#f8f9fa);color:#a4adb5;font-size:38px}
.mp-image-count{position:absolute;left:12px;bottom:12px;z-index:2;padding:6px 9px;border-radius:20px;background:rgba(24,35,45,.82);color:#fff;font-size:9px;font-weight:800;display:inline-flex;align-items:center;gap:5px;backdrop-filter:blur(5px)}
.mp-item-badge{position:absolute;top:12px;left:12px;border-radius:99px;padding:6px 9px;font-size:9px;font-weight:900;letter-spacing:.4px;background:rgba(255,255,255,.94);color:#53616d}
.mp-stock{position:absolute;right:12px;top:12px;border-radius:99px;padding:6px 9px;font-size:9px;font-weight:800;background:#e7f5ee;color:#16734e}
.mp-stock.out{background:#fcecec;color:#b53b3b}
.mp-card-body{padding:16px;display:flex;flex-direction:column;flex:1}
.mp-category{font-size:9px;letter-spacing:1.1px;text-transform:uppercase;color:var(--gold-dark);font-weight:900}
.mp-card h3{font-size:14px;line-height:1.4;margin:8px 0;color:#1c2934;min-height:39px}
.mp-description{font-size:11px;line-height:1.6;color:#788491;margin:0 0 13px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:35px}
.mp-card-bottom{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:8px}
.mp-price{font-size:16px;font-weight:900;letter-spacing:-.4px;color:#1d2c37}
.mp-price small{display:block;font-size:9px;font-weight:600;color:#8b96a0;letter-spacing:0;margin-top:3px}
.mp-add-form{display:flex;align-items:center;gap:6px}
.mp-qty-input{width:43px;height:35px;border:1px solid var(--line);border-radius:8px;text-align:center;font-size:11px}
.mp-add-btn{height:36px;border:0;border-radius:9px;background:var(--navy);color:#fff;padding:0 12px;font-size:10px;font-weight:850;white-space:nowrap}
.mp-add-btn:hover{background:var(--gold-dark)}
.mp-add-btn:disabled{opacity:.45;cursor:not-allowed}
.mp-quote-label{font-size:10px;color:#8f6b31;font-weight:800}
.mp-cart-panel{position:sticky;top:92px;background:#fff;border:1px solid var(--line);border-radius:17px;box-shadow:0 10px 35px rgba(22,34,46,.05);overflow:hidden}
.mp-cart-head{padding:19px 19px 15px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
.mp-cart-head h3{font-size:15px;margin:0}
.mp-cart-head span{font-size:10px;color:var(--muted)}
.mp-cart-body{padding:15px 18px}
.mp-cart-empty{text-align:center;padding:28px 10px;color:#8b96a0}
.mp-cart-empty i{font-size:27px;color:#c3cbd2;margin-bottom:12px}
.mp-cart-empty strong{display:block;color:#44515d;font-size:12px;margin-bottom:6px}
.mp-cart-empty p{font-size:10px;line-height:1.6;margin:0}
.mp-cart-item{display:grid;grid-template-columns:48px minmax(0,1fr) auto;gap:10px;padding:12px 0;border-bottom:1px solid #f0f2f4}
.mp-cart-thumb{width:48px;height:48px;border-radius:9px;background:#f0f3f5;overflow:hidden;display:grid;place-items:center;color:#a0aab2}
.mp-cart-thumb img{width:100%;height:100%;object-fit:cover}
.mp-cart-item-name{font-size:10px;font-weight:850;line-height:1.4;color:#34424e}
.mp-cart-item-meta{font-size:9px;color:#89939c;margin-top:4px}
.mp-cart-item-price{font-size:10px;font-weight:850;text-align:right;white-space:nowrap}
.mp-cart-controls{display:flex;align-items:center;gap:5px;margin-top:7px}
.mp-cart-controls input{width:39px;height:26px;border:1px solid var(--line);border-radius:6px;text-align:center;font-size:10px}
.mp-mini-btn{border:0;background:none;color:#75818b;font-size:10px;padding:4px}
.mp-mini-btn.remove{color:#bf4b4b}
.mp-cart-summary{padding:15px 18px 18px;background:#fbfcfd;border-top:1px solid var(--line)}
.mp-summary-line{display:flex;justify-content:space-between;gap:12px;font-size:11px;color:#6d7882;margin-bottom:10px}
.mp-summary-line strong{color:#263541}
.mp-summary-total{border-top:1px dashed #dce2e7;padding-top:13px;margin-top:12px;font-size:13px;font-weight:900;color:#1b2b36}
.mp-summary-note{font-size:9px;color:#8a949c;line-height:1.55;margin:12px 0}
.mp-checkout-btn{width:100%;height:45px;border:0;border-radius:10px;background:linear-gradient(110deg,#b58b45,#d0ad69);color:#fff;font-size:11px;font-weight:900;letter-spacing:.3px}
.mp-checkout-btn:hover{filter:brightness(.96)}
.mp-clear-btn{width:100%;margin-top:8px;border:0;background:transparent;color:#929ca5;font-size:10px;padding:7px}
.mp-alert{padding:12px 15px;border-radius:11px;margin-bottom:18px;font-size:11px;font-weight:650}
.mp-alert.success{background:#eaf7f0;color:#176b49;border:1px solid #cdebd9}
.mp-alert.error{background:#fff0f0;color:#a83939;border:1px solid #f5d0d0}
.mp-alert.info{background:#eef5fb;color:#315f84;border:1px solid #d8e8f4}
.mp-empty-state{grid-column:1/-1;padding:55px 20px;background:#fff;border:1px dashed #d9e0e6;border-radius:15px;text-align:center;color:#87929c}
.mp-empty-state i{font-size:30px;color:#c3cbd2;margin-bottom:13px}
.mp-empty-state h3{font-size:15px;color:#46535f;margin:0 0 7px}
.mp-empty-state p{font-size:11px;margin:0}
.mp-auth-wrap{max-width:1050px;margin:30px auto 70px;padding:0 4.5%;display:grid;grid-template-columns:1fr 410px;gap:25px;align-items:stretch}
.mp-auth-info{border-radius:18px;background:linear-gradient(140deg,#142330,#263c48);color:#fff;padding:35px;display:flex;flex-direction:column;justify-content:center;min-height:380px}
.mp-auth-info .mp-eyebrow{color:#e2c27f}
.mp-auth-info h2{font-size:29px;line-height:1.15;letter-spacing:-.8px;margin:18px 0 12px}
.mp-auth-info p{font-size:12px;line-height:1.8;color:rgba(255,255,255,.72);max-width:450px}
.mp-auth-benefits{display:grid;gap:12px;margin-top:22px;font-size:11px;color:#f0e4ca}
.mp-auth-benefits span{display:flex;align-items:center;gap:10px}
.mp-auth-benefits i{color:#d8b66f}
.mp-auth-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:25px;box-shadow:var(--shadow)}
.mp-auth-card h2{font-size:19px;margin:0 0 6px}
.mp-auth-card>p{font-size:11px;color:var(--muted);margin:0 0 19px}
.mp-auth-switch{display:flex;background:#f2f5f7;padding:4px;border-radius:9px;margin-bottom:18px}
.mp-auth-switch a{flex:1;text-align:center;padding:9px 5px;font-size:10px;font-weight:850;border-radius:7px;color:#71808b}
.mp-auth-switch a.active{background:#fff;color:#243440;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.mp-field{margin-bottom:13px}
.mp-field label{display:block;font-size:10px;font-weight:850;color:#52606c;margin-bottom:6px}
.mp-field input,.mp-field textarea{width:100%;height:42px;border:1px solid #dfe5ea;border-radius:9px;padding:0 11px;font-size:11px;outline:none}
.mp-field textarea{height:82px;padding:10px;resize:vertical}
.mp-field input:focus,.mp-field textarea:focus{border-color:#c5a361;box-shadow:0 0 0 3px rgba(181,139,69,.1)}
.mp-auth-submit{width:100%;height:43px;border:0;border-radius:9px;background:var(--navy);color:#fff;font-size:11px;font-weight:900;margin-top:5px}
.mp-auth-back{display:block;text-align:center;margin-top:13px;font-size:10px;color:#788590}
.mp-checkout-wrap{max-width:1000px;margin:30px auto 70px;padding:0 4.5%}
.mp-checkout-card{background:#fff;border:1px solid var(--line);border-radius:17px;padding:24px;box-shadow:var(--shadow)}
.mp-checkout-card h2{font-size:20px;margin:0 0 6px}
.mp-checkout-card>p{font-size:11px;color:var(--muted);margin:0 0 20px}
.mp-order-line{display:flex;justify-content:space-between;gap:15px;padding:12px 0;border-bottom:1px solid #edf0f2;font-size:11px}
.mp-order-line strong{font-size:11px}
.mp-order-total{display:flex;justify-content:space-between;padding:17px 0;font-size:15px;font-weight:900}
.mp-success{max-width:700px;margin:50px auto 80px;padding:45px 25px;text-align:center;background:#fff;border:1px solid var(--line);border-radius:19px;box-shadow:var(--shadow)}
.mp-success-icon{width:65px;height:65px;margin:0 auto 18px;border-radius:50%;background:#e7f6ed;color:#198255;display:grid;place-items:center;font-size:26px}
.mp-success h2{font-size:23px;margin:0 0 10px}
.mp-success p{font-size:12px;color:#75818b;line-height:1.7}
.mp-order-number{display:inline-block;margin:10px 0 20px;padding:10px 15px;border:1px dashed #c6ad7b;border-radius:9px;color:#765a2b;font-weight:900;letter-spacing:1px}
.mp-footer{background:#142330;color:#fff;padding:28px 4.5%}
.mp-footer-inner{max-width:1440px;margin:auto;display:flex;justify-content:space-between;align-items:center;gap:15px}
.mp-footer small{font-size:10px;color:rgba(255,255,255,.6)}
.mp-footer a{font-size:10px;color:#e0c17f}
@media(max-width:1100px){.mp-layout{grid-template-columns:minmax(0,1fr) 300px}.mp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.mp-brand{min-width:180px}}
@media(max-width:760px){
    .mp-header-inner{padding:12px 4%;gap:10px;flex-wrap:wrap}
    .mp-brand{min-width:0;flex:1}.mp-brand-name{max-width:170px;font-size:13px}
    .mp-brand-caption{font-size:8px}.mp-brand img,.mp-brand-mark{width:38px;height:38px}
    .mp-search{order:3;flex-basis:100%;max-width:none}
    .mp-header-actions{margin-left:0}.mp-account-link{width:40px;padding:0;justify-content:center;font-size:0}
    .mp-account-link i{font-size:14px}.mp-cart-link{height:40px;padding:0 10px}
    .mp-hero{margin-top:14px;padding:0 4%}.mp-hero-panel{min-height:225px;padding:30px 24px;border-radius:16px}
    .mp-hero-panel:before{right:-190px}.mp-hero h1{font-size:31px}.mp-hero p{font-size:12px}
    .mp-main{padding:27px 4% 45px}.mp-section-head{align-items:start}.mp-section-head h2{font-size:18px}
    .mp-toolbar{align-items:flex-start;flex-direction:column}.mp-category-select{max-width:none;width:100%}
    .mp-layout{display:flex;flex-direction:column}.mp-catalogue{width:100%}.mp-cart-panel{position:static;width:100%;order:2}
    .mp-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .mp-card{border-radius:12px}.mp-card-image{height:135px}.mp-card-body{padding:11px}
    .mp-card h3{font-size:12px;min-height:34px}.mp-description{font-size:10px;min-height:32px}
    .mp-price{font-size:13px}.mp-add-form{gap:4px}.mp-add-btn{padding:0 8px;font-size:9px}
    .mp-qty-input{width:34px;height:33px}.mp-auth-wrap{grid-template-columns:1fr;padding:0 4%;margin-top:20px}
    .mp-auth-info{min-height:0;padding:25px}.mp-auth-info h2{font-size:24px}
    .mp-auth-card{padding:21px}.mp-checkout-wrap{padding:0 4%}
    .mp-footer-inner{flex-direction:column;text-align:center}
}
@media(max-width:390px){.mp-grid{grid-template-columns:1fr}.mp-card-image{height:185px}.mp-card-body{padding:15px}}

/* Refined light storefront theme */
:root{
    --ink:#202b36;--muted:#74808c;--line:#e7ebef;--paper:#f7f8fa;
    --white:#fff;--gold:#b18a4b;--gold-dark:#87652f;--navy:#263746;--lightgrey: #d3d7db;
    --green:#16845b;--red:#c24141;--shadow:0 12px 34px rgba(31,45,58,.065);
}
body{background:#f7f8fa;color:#202b36}
.mp-topline{height:3px;background:linear-gradient(90deg,#a98242,#e1c58c,#a98242)}
.mp-header{background:rgba(255,255,255,.96);backdrop-filter:blur(14px);box-shadow:0 3px 18px rgba(31,45,58,.035)}
.mp-brand-mark{background:#f3eee4;color:#98733a;border:1px solid #e9dfcd}
.mp-search input{background:#f8f9fb;border-color:#e4e9ee}
.mp-search button,.mp-add-btn{background:#d3d3d3}
.mp-cart-link{background:#8e89c9;color:#000;border-color:#d3d3d3}
.mp-hero-panel{background:linear-gradient(115deg,#fff 0%,#f7f4ed 58%,#f0ede5 100%);color:#263541;border:1px solid #eee8dc;box-shadow:0 14px 38px rgba(31,45,58,.055)}
.mp-hero-panel:before{border-color:rgba(177,138,75,.18);box-shadow:0 0 0 35px rgba(177,138,75,.035),0 0 0 75px rgba(177,138,75,.025)}
.mp-hero-panel:after{background:radial-gradient(circle,rgba(201,164,92,.17),transparent 68%)}
.mp-eyebrow{color:#98733a}.mp-eyebrow:before{background:#b18a4b}
.mp-hero p{color:#687582}
.mp-hero-tags span{border-color:#e7ddc9;color:#806235;background:rgba(255,255,255,.68)}
.mp-tab.active{background:#293b4a;border-color:#293b4a}
.mp-card,.mp-cart-panel,.mp-auth-card,.mp-checkout-card,.mp-success{box-shadow:0 8px 26px rgba(31,45,58,.045)}
.mp-card:hover{box-shadow:0 15px 35px rgba(31,45,58,.09)}
.mp-add-btn:hover{background:#9a7439}
.mp-checkout-btn{background:linear-gradient(110deg,#9d783d,#c5a56a)}
.mp-auth-info{background:linear-gradient(140deg,#f7f4ed,#ece7dc);color:#293844;border:1px solid #e9e1d3}
.mp-auth-info p{color:#687582}.mp-auth-info .mp-eyebrow{color:#98733a}
.mp-auth-benefits{color:#596775}.mp-auth-benefits i{color:#a98242}
.mp-auth-switch a.active{color:#293b4a}
.mp-footer{background:#263746}
.mp-service-note-form{margin-top:10px;display:grid;gap:6px;max-width:260px}
.mp-service-note-form label{font-size:9px;font-weight:850;color:#66727e}
.mp-service-note-form input{width:100%;height:34px;border:1px solid var(--line);border-radius:7px;padding:6px 8px;font-size:9px;margin:3px 0 8px}
.mp-service-note-form textarea{width:100%;min-height:66px;resize:vertical;border:1px solid #e1e6eb;border-radius:8px;padding:8px;font-size:10px;line-height:1.45;color:#33414d;background:#fbfcfd}
.mp-service-note-form textarea:focus{outline:none;border-color:#c4a46a;box-shadow:0 0 0 3px rgba(177,138,75,.1)}
.mp-note-save{justify-self:start;border:0;background:#f1eadc;color:#795c30;border-radius:7px;padding:6px 9px;font-size:9px;font-weight:850}
.mp-note-save:hover{background:#e9ddc6}
@media(max-width:1100px){
    .mp-layout{grid-template-columns:minmax(0,1fr) 300px;gap:18px}
    .mp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .mp-header-inner{gap:16px}
}
@media(max-width:760px){
    .mp-header{position:relative}
    .mp-header-inner{padding:12px 4%;gap:10px}
    .mp-hero{margin-top:14px}
    .mp-hero-panel{min-height:0;padding:27px 22px}
    .mp-hero h1{font-size:clamp(27px,7vw,36px);letter-spacing:-1px}
    .mp-hero p{font-size:12px}
    .mp-main{padding:25px 4% 45px}
    .mp-toolbar{gap:12px}
    .mp-tabs{width:100%;display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr))}
    .mp-tab{text-align:center;padding:10px 7px}
    .mp-layout{display:flex;flex-direction:column;gap:22px}
    .mp-catalogue,.mp-cart-panel{width:100%}
    .mp-cart-panel{position:static;order:2}
    .mp-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .mp-card{border-radius:12px}
    .mp-card-image{height:clamp(112px,34vw,175px)}
    .mp-card-body{padding:11px}
    .mp-card h3{font-size:12px;min-height:34px}
    .mp-description{font-size:10px}
    .mp-card-bottom{align-items:flex-start;flex-direction:column}
    .mp-add-form{width:100%}
    .mp-add-btn{flex:1}
    .mp-service-note-form{max-width:100%;width:100%}
    .mp-cart-item{grid-template-columns:42px minmax(0,1fr) auto;gap:9px}
    .mp-auth-wrap{grid-template-columns:1fr;padding:0 4%;margin-top:20px}
    .mp-auth-info{min-height:0;padding:24px}
    .mp-auth-info h2{font-size:24px}
    .mp-checkout-wrap{padding:0 4%}
    .mp-checkout-card{padding:18px}
    .mp-footer-inner{flex-direction:column;align-items:flex-start}
}
@media(max-width:420px){
    .mp-brand-name{max-width:135px}
    .mp-brand-caption{letter-spacing:.7px}
    .mp-header-actions{gap:6px}
    .mp-account-link,.mp-cart-link{height:38px}
    .mp-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .mp-card-image{height:112px}
    .mp-price{font-size:14px}
    .mp-qty-input{width:38px}
    .mp-add-btn{padding:0 8px;font-size:9px}
    .mp-section-head h2{font-size:17px}
}
@media(prefers-reduced-motion:reduce){
    *,*::before,*::after{scroll-behavior:auto!important;transition:none!important;animation:none!important}
}


/* Persistent, easy-to-reach navigation */
.mp-quick-nav{background:#fff;border-top:1px solid #f0f2f4;border-bottom:1px solid var(--line)}
.mp-quick-nav-inner{max-width:1440px;margin:auto;padding:0 4.5%;display:flex;align-items:center;gap:7px;min-height:48px;overflow-x:auto;scrollbar-width:none}
.mp-quick-nav-inner::-webkit-scrollbar{display:none}
.mp-quick-link{flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border-radius:9px;color:#65717c;font-size:11px;font-weight:800;white-space:nowrap;transition:background .18s,color .18s}
.mp-quick-link i{font-size:11px;color:#a17b3e}
.mp-quick-link:hover,.mp-quick-link:focus-visible{background:#f5f1e9;color:#293b4a;outline:none}
.mp-quick-link.active{background:#f5f1e9;color:#795c30}
.mp-quick-link b{min-width:18px;height:18px;padding:0 5px;border-radius:20px;background:#293b4a;color:#fff;display:grid;place-items:center;font-size:9px}
#catalogue-products,#catalogue-services,#shopping-cart{scroll-margin-top:125px}
@media(max-width:760px){
 .mp-header{position:sticky;top:0}
 .mp-header-inner{padding:10px 4%}
 .mp-quick-nav-inner{padding:0 4%;min-height:45px;gap:4px}
 .mp-quick-link{padding:9px 10px;font-size:10px}
 #catalogue-products,#catalogue-services,#shopping-cart{scroll-margin-top:165px}
}
@media(max-width:380px){.mp-quick-link{gap:6px;padding:8px;font-size:9px}}

/* High-contrast content for dark action controls */
.mp-search button,
.mp-add-btn,
.mp-cart-link,
.mp-tab.active,
.mp-auth-submit {
    color:#fff !important;
}
.mp-search button i,
.mp-search button svg,
.mp-add-btn i,
.mp-add-btn svg,
.mp-cart-link i,
.mp-tab.active i,
.mp-tab.active svg,
.mp-auth-submit i,
.mp-auth-submit svg {
    color:#fff !important;
    fill:currentColor;
}
.mp-cart-link .mp-cart-count {color:#263746 !important;}
.mp-add-btn:disabled {color:rgba(255,255,255,.88) !important;}

/* Clickable catalogue cards and responsive detail popup */
.mp-card-clickable{cursor:pointer}
.mp-card-clickable:focus-visible{outline:3px solid rgba(177,138,75,.35);outline-offset:3px}
.mp-card-clickable .mp-add-form{cursor:default}

.mp-feature-preview{list-style:none;padding:0;margin:0 0 12px;display:grid;gap:4px}
.mp-feature-preview li{font-size:10px;line-height:1.45;color:#75818b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mp-feature-preview strong{color:#485763}
.mp-detail-features-wrap{width:100%;margin:0 0 18px;padding:14px;background:#fbfaf7;border:1px solid #eee8dc;border-radius:12px}
.mp-detail-section-heading{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}
.mp-detail-features-wrap h3{font-size:11px;letter-spacing:.7px;text-transform:uppercase;color:#8a6a37;margin:0}
.mp-detail-section-heading span{font-size:9px;color:#8b949c}
.mp-detail-no-features{font-size:10px;line-height:1.6;color:#87919a;margin:0}
.mp-detail-features{list-style:none;padding:0;margin:0;display:grid;gap:7px}
.mp-gallery-toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:10px;color:#78838d}
.mp-gallery-controls{display:flex;gap:6px}
.mp-gallery-controls button{width:29px;height:27px;border:1px solid #e2e6e9;border-radius:7px;background:#fff;color:#53616d}
.mp-gallery-controls button:disabled{opacity:.35;cursor:not-allowed}

.mp-detail-features li{display:flex;justify-content:space-between;gap:15px;padding:8px 10px;background:#f7f8f9;border-radius:8px;font-size:11px;line-height:1.45}
.mp-detail-features li strong{color:#43515d}
.mp-detail-features li span{text-align:right;color:#687580;white-space:pre-line}
.mp-detail-modal{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:22px}
.mp-detail-modal.is-open{display:flex}
.mp-detail-backdrop{position:absolute;inset:0;background:rgba(14,24,32,.66);backdrop-filter:blur(5px)}
.mp-detail-dialog{position:relative;z-index:1;width:min(960px,100%);max-height:min(88vh,820px);overflow:auto;background:#fff;border-radius:20px;box-shadow:0 30px 100px rgba(0,0,0,.28);animation:mpModalIn .2s ease}
@keyframes mpModalIn{from{opacity:0;transform:translateY(12px) scale(.985)}to{opacity:1;transform:none}}
.mp-detail-close{position:absolute;right:14px;top:14px;z-index:4;width:38px;height:38px;border:1px solid #e8ebee;border-radius:50%;background:rgba(255,255,255,.95);color:#35424d;font-size:16px}
.mp-detail-layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);min-height:450px}
.mp-detail-gallery{padding:25px;background:#f5f6f7;display:flex;flex-direction:column;gap:12px;min-width:0}
.mp-detail-main-image{height:360px;border-radius:14px;background:#eceff1;display:grid;place-items:center;overflow:hidden;position:relative;cursor:zoom-in}
.mp-detail-main-image img{width:100%;height:100%;object-fit:contain;transition:transform .22s ease;transform-origin:center center;will-change:transform}
.mp-detail-main-image.is-video{cursor:default;background:#101820}
.mp-detail-main-image video{width:100%;height:100%;object-fit:contain;background:#101820}
.mp-detail-thumb{position:relative}
.mp-detail-thumb .mp-video-thumb{width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(145deg,#1b2935,#34495a);color:#fff;font-size:20px}
.mp-detail-thumb .mp-video-thumb img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.72}
.mp-detail-thumb .mp-video-thumb i{position:relative;z-index:1;text-shadow:0 2px 8px #000}
.mp-detail-main-image .mp-zoom-tip{position:absolute;right:12px;bottom:12px;padding:7px 10px;border-radius:20px;background:rgba(20,35,48,.78);color:#fff;font-size:10px;pointer-events:none}
.mp-detail-no-image{display:grid;gap:10px;place-items:center;color:#9aa4ad;font-size:12px}
.mp-detail-no-image i{font-size:42px}
.mp-detail-thumbnails{display:flex;gap:9px;overflow-x:auto;padding:2px 1px 7px}
.mp-detail-thumb{flex:0 0 66px;width:66px;height:62px;padding:0;border:2px solid transparent;border-radius:9px;overflow:hidden;background:#fff;cursor:pointer}
.mp-detail-thumb.active{border-color:#b18a4b}
.mp-detail-thumb img{width:100%;height:100%;object-fit:cover}
.mp-detail-content{padding:55px 35px 30px;display:flex;flex-direction:column;align-items:flex-start}
.mp-detail-type{font-size:9px;font-weight:900;letter-spacing:1.6px;color:#98733a}
.mp-detail-category{font-size:10px;color:#89939c;margin-top:8px}
.mp-detail-content h2{font-size:25px;line-height:1.25;letter-spacing:-.6px;margin:12px 0}
.mp-detail-description{font-size:13px;line-height:1.8;color:#6d7882;white-space:pre-line;margin:0 0 20px}
.mp-detail-price-row{border-top:1px solid #edf0f2;border-bottom:1px solid #edf0f2;width:100%;padding:16px 0;display:grid;gap:5px}
.mp-detail-price-row strong{font-size:22px;color:#253541}
.mp-detail-price-row small{font-size:10px;color:#87919a}
.mp-detail-stock{font-size:11px;font-weight:800;margin-top:15px;color:#16734e}
.mp-detail-stock.out{color:#b53b3b}
.mp-detail-actions{display:flex;align-items:end;gap:10px;width:100%;margin-top:20px}
.mp-detail-quantity-label{font-size:10px;color:#68747e;font-weight:800;display:grid;gap:6px}
.mp-detail-quantity-label input{width:75px;height:43px;border:1px solid #dfe5e9;border-radius:9px;padding:0 10px}
.mp-detail-add{height:43px;flex:1;border:0;border-radius:9px;background:#263746;color:#fff;font-size:11px;font-weight:900}
.mp-detail-add:disabled{opacity:.45;cursor:not-allowed}
.mp-detail-hint{font-size:9px;line-height:1.6;color:#9aa3ab;margin-top:18px}
.mp-auth-divider{display:flex;align-items:center;gap:10px;margin:18px 0 14px;color:#9aa3ab;font-size:9px;font-weight:900;letter-spacing:1px}
.mp-auth-divider:before,.mp-auth-divider:after{content:"";height:1px;background:#e9edf0;flex:1}
.mp-google-button{display:flex;justify-content:center;min-height:42px}
.mp-google-button>div{width:100%!important}
.mp-google-note{font-size:9px!important;line-height:1.55;color:#89939c!important;text-align:center;margin:9px 0 0!important}
@media(max-width:700px){
 .mp-detail-modal{padding:10px;align-items:flex-end}
 .mp-detail-dialog{max-height:94vh;border-radius:17px}
 .mp-detail-layout{grid-template-columns:1fr}
 .mp-detail-gallery{padding:15px}
 .mp-detail-main-image{height:245px}
 .mp-detail-content{padding:22px 20px 24px}
 .mp-detail-content h2{font-size:21px;padding-right:28px}
 .mp-detail-description{font-size:12px}
 .mp-detail-close{right:11px;top:11px}
}
@media(prefers-reduced-motion:reduce){.mp-detail-dialog{animation:none}}

</style>
</head>
<body>
<div class="mp-topline"></div>

<header class="mp-header">
    <div class="mp-header-inner">
        <a class="mp-brand" href="marketplace.php">
            <?php if ($logoPath !== '' && is_file(__DIR__ . '/' . $logoPath)): ?>
                <img src="<?= mp_h($logoPath); ?>" alt="<?= mp_h($companyName); ?>">
            <?php else: ?>
                <span class="mp-brand-mark"><i class="fa-solid fa-store"></i></span>
            <?php endif; ?>
            <span>
                <span class="mp-brand-name"><?= mp_h($companyName); ?></span>
                <span class="mp-brand-caption">Official Marketplace</span>
            </span>
        </a>

        <form class="mp-search" method="get" action="marketplace.php">
            <input type="search" name="q" value="<?= mp_h($search); ?>" placeholder="Search products and services...">
            <?php if ($view !== 'all'): ?><input type="hidden" name="type" value="<?= mp_h($view); ?>"><?php endif; ?>
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <div class="mp-header-actions">
            <a class="mp-account-link" href="customer_auth.php?account=login" title="Customer account"><i class="fa-regular fa-user"></i><span>Customer account</span></a>
            <a class="mp-cart-link" href="#shopping-cart"><i class="fa-solid fa-bag-shopping"></i><span>Cart</span><span class="mp-cart-count"><?= (int)$cartTotalCount; ?></span></a>
        </div>
    </div>
    <nav class="mp-quick-nav" aria-label="Marketplace navigation">
        <div class="mp-quick-nav-inner">
            <a class="mp-quick-link" href="index.php"><i class="fa-solid fa-house"></i><span>Home</span></a>
            <a class="mp-quick-link active" href="marketplace.php"><i class="fa-solid fa-store"></i><span>Marketplace</span></a>
            <a class="mp-quick-link" href="marketplace.php?type=product#catalogue-products"><i class="fa-solid fa-box"></i><span>Products</span></a>
            <?php if ($companyHasServices): ?>
                <a class="mp-quick-link" href="marketplace.php?type=service#catalogue-services"><i class="fa-solid fa-briefcase"></i><span>Services</span></a>
            <?php endif; ?>
            <a class="mp-quick-link" href="#shopping-cart"><i class="fa-solid fa-bag-shopping"></i><span>Shopping cart</span><b><?= (int)$cartTotalCount; ?></b></a>
        </div>
    </nav>
</header>

    <section class="mp-hero">
        <div class="mp-hero-panel">
            <div class="mp-hero-content">
                <span class="mp-eyebrow">Discover · Shop · Request</span>
                <h1>Everything you need.<br>All in one place.</h1>
                <p>Explore quality products and professional services from <?= mp_h($companyName); ?>. Browse products and services, then continue to your customer account with your cart.</p>
                <div class="mp-hero-tags">
                    <span><i class="fa-solid fa-box"></i> Products</span>
                    <span><i class="fa-solid fa-briefcase"></i> Services</span>
                    <span><i class="fa-solid fa-shield-halved"></i> Secure customer account</span>
                </div>
            </div>
        </div>
    </section>

    <main class="mp-main">
        <?php if ($flash): ?><div class="mp-alert <?= mp_h($flash['type']); ?>"><?= mp_h($flash['message']); ?></div><?php endif; ?>

        <div class="mp-section-head">
            <div>
                <h2>Explore the marketplace</h2>
                <p>Browse the catalogue and add what you need to your cart.</p>
            </div>
            <span class="mp-count-label"><?= $productTotal + $serviceTotal; ?> catalogue items</span>
        </div>

        <div class="mp-toolbar">
            <div class="mp-tabs">
                <a class="mp-tab <?= $view === 'all' ? 'active' : ''; ?>" href="marketplace.php<?= $search !== '' ? '?q=' . urlencode($search) : ''; ?>">All items</a>
                <a class="mp-tab <?= $view === 'product' ? 'active' : ''; ?>" href="marketplace.php?type=product<?= $search !== '' ? '&q=' . urlencode($search) : ''; ?>">Products</a>
                <?php if ($companyHasServices): ?>
                <a class="mp-tab <?= $view === 'service' ? 'active' : ''; ?>" href="marketplace.php?type=service<?= $search !== '' ? '&q=' . urlencode($search) : ''; ?>">Services</a>
                <?php endif; ?>
            </div>

            <form method="get" action="marketplace.php">
                <?php if ($view !== 'all'): ?><input type="hidden" name="type" value="<?= mp_h($view); ?>"><?php endif; ?>
                <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= mp_h($search); ?>"><?php endif; ?>
                <select class="mp-category-select" name="category" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= mp_h($cat['key']); ?>" <?= $categoryFilter === $cat['key'] ? 'selected' : ''; ?>>
                            <?= mp_h($cat['name']); ?><?= $cat['type'] === 'service' ? ' · Service' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="mp-layout">
            <section class="mp-catalogue" id="marketplace-catalogue">
                <?php if ($displayProducts): ?>
                    <div class="mp-section-head" id="catalogue-products">
                        <div><h2>Products</h2><p>Available products from this company.</p></div>
                        <span class="mp-count-label"><?= $productTotal; ?> products</span>
                    </div>
                    <div class="mp-grid" style="margin-bottom:32px">
                        <?php if (!$products): ?>
                            <div class="mp-empty-state"><i class="fa-solid fa-box-open"></i><h3>No products found</h3><p>Try another search or category.</p></div>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <?php
                                    $image = mp_image_url($product['image_path']);
                                    $stock = max(0, (int)$product['total_stock']);
                                    $price = max(0, (float)$product['selling_price']);
                                ?>
                                <?php
                                    $publicProductName = mp_public_product_text($product['name'] ?? '');
                                    $productMedia = $productMediaById[(int)$product['id']] ?? [];
                                    if (!$productMedia && $image !== '') {
                                        $productMedia = [['type' => 'image', 'src' => $image, 'thumbnail' => $image, 'mime' => '']];
                                    }
                                    $productImages = array_values(array_filter($productMedia, function ($m) { return ($m['type'] ?? '') === 'image'; }));
                                    $productVideos = array_values(array_filter($productMedia, function ($m) { return ($m['type'] ?? '') === 'video'; }));
                                    $productGalleryCount = count($productImages);
                                    $productVideoCount = count($productVideos);
                                    $cardImage = '';
                                    foreach ($productMedia as $mediaItem) {
                                        if (($mediaItem['type'] ?? '') === 'image') { $cardImage = (string)$mediaItem['src']; break; }
                                    }
                                    if ($cardImage === '') $cardImage = $image;
                                    $productFeatures = $productFeaturesById[(int)$product['id']] ?? [];
                                    $productDetail = [
                                        'type' => 'product',
                                        'name' => $publicProductName,
                                        'description' => mp_public_product_text($product['description'] ?? ''),
                                        'category' => (string)$product['category_name'],
                                        'price' => mp_money($price),
                                        'priceNote' => $product['unit_name'] !== '' ? 'Per ' . $product['unit_name'] : 'Product price',
                                        'stock' => $stock,
                                        'media' => $productMedia,
                                        'features' => $productFeatures
                                    ];
                                ?>
                                <article class="mp-card mp-card-clickable" tabindex="0" role="button" aria-haspopup="dialog" aria-label="View details for <?= mp_h($publicProductName); ?>" data-marketplace-detail="<?= mp_h(json_encode($productDetail, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>">
                                    <div class="mp-card-image">
                                        <?php if ($cardImage !== ''): ?>
                                            <img src="<?= mp_h($cardImage); ?>" alt="<?= mp_h($publicProductName); ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                                            <div class="mp-image-fallback" style="display:none"><i class="fa-solid fa-box-open"></i></div>
                                        <?php else: ?>
                                            <div class="mp-image-fallback"><i class="fa-solid fa-box-open"></i></div>
                                        <?php endif; ?>
                                        <span class="mp-item-badge">PRODUCT</span>
                                        <?php if ($productGalleryCount > 0 || $productVideoCount > 0): ?>
                                            <span class="mp-image-count"><i class="fa-regular fa-images"></i>
                                                <?= $productGalleryCount; ?> photo<?= $productGalleryCount === 1 ? '' : 's'; ?>
                                                <?php if ($productVideoCount > 0): ?> · <i class="fa-solid fa-circle-play"></i> <?= $productVideoCount; ?> video<?= $productVideoCount === 1 ? '' : 's'; ?><?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="mp-stock <?= $stock < 1 ? 'out' : ''; ?>"><?= $stock > 0 ? 'In stock · ' . $stock : 'Out of stock'; ?></span>
                                    </div>
                                    <div class="mp-card-body">
                                        <span class="mp-category"><?= mp_h($product['category_name']); ?></span>
                                        <h3><?= mp_h($publicProductName); ?></h3>
                                        <p class="mp-description"><?= mp_h(mp_public_product_text($product['description'] ?? '')); ?></p>
                                        <?php if ($productFeatures): ?>
                                            <ul class="mp-feature-preview">
                                                <?php foreach (array_slice($productFeatures, 0, 2) as $feature): ?>
                                                    <li><strong><?= mp_h($feature['name']); ?>:</strong> <?= mp_h($feature['value']); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <div class="mp-card-bottom">
                                            <div class="mp-price"><?= mp_money($price); ?><small><?= mp_h($product['unit_name'] !== '' ? 'Per ' . $product['unit_name'] : 'Product price'); ?></small></div>
                                            <form class="mp-add-form" method="post">
                                                <input type="hidden" name="csrf_token" value="<?= mp_h($csrf); ?>">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="item_type" value="product">
                                                <input type="hidden" name="item_id" value="<?= (int)$product['id']; ?>">
                                                <input class="mp-qty-input" type="number" name="quantity" min="1" max="<?= max(1, $stock); ?>" value="1" aria-label="Quantity" <?= $stock < 1 ? 'disabled' : ''; ?>>
                                                <button class="mp-add-btn" type="submit" <?= $stock < 1 ? 'disabled' : ''; ?>><i class="fa-solid fa-plus"></i> Add</button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($productPages > 1): ?>
                        <nav class="mp-pagination" aria-label="Product pages">
                            <span>Page <?= $productPage; ?> of <?= $productPages; ?></span>
                            <div>
                                <?php if ($productPage > 1): ?><a href="<?= mp_h(mp_page_url('product', $productPage - 1, $search, $view, $categoryFilter)); ?>"><i class="fa-solid fa-chevron-left"></i> Previous</a><?php endif; ?>
                                <?php if ($productPage < $productPages): ?><a href="<?= mp_h(mp_page_url('product', $productPage + 1, $search, $view, $categoryFilter)); ?>">Next <i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
                            </div>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($displayServices): ?>
                    <div class="mp-section-head" id="catalogue-services">
                        <div><h2>Professional services</h2><p>Add a service to your cart and request a quotation.</p></div>
                        <span class="mp-count-label"><?= $serviceTotal; ?> services</span>
                    </div>
                    <div class="mp-grid">
                        <?php if (!$services): ?>
                            <div class="mp-empty-state"><i class="fa-solid fa-briefcase"></i><h3>No services found</h3><p>Try another search or category.</p></div>
                        <?php else: ?>
                            <?php foreach ($services as $service): ?>
                                <?php
                                    $serviceGallery = $serviceImagesById[(int)$service['id']] ?? [];
                                    $serviceDetail = [
                                        'type' => 'service',
                                        'name' => (string)$service['service_name'],
                                        'description' => (string)($service['description'] ?? ''),
                                        'category' => (string)$service['category_name'],
                                        'price' => $service['pricing_type'] === 'fixed' ? mp_money($service['fixed_price']) : 'Request a quote',
                                        'priceNote' => $service['pricing_type'] === 'fixed' ? 'Fixed service price' : 'Price confirmed by our team',
                                        'available' => (int)$service['is_available'] === 1,
                                        'images' => $serviceGallery
                                    ];
                                ?>
                                <article class="mp-card mp-card-clickable" tabindex="0" role="button" aria-haspopup="dialog" aria-label="View details for <?= mp_h($service['service_name']); ?>" data-marketplace-detail="<?= mp_h(json_encode($serviceDetail, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>">
                                    <div class="mp-card-image">
                                        <?php if (!empty($serviceGallery[0])): ?>
                                            <img src="<?= mp_h($serviceGallery[0]); ?>" alt="<?= mp_h($service['service_name']); ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="mp-image-fallback" style="background:linear-gradient(145deg,#e8e9e7,#f7f6f2);color:#aa946b"><i class="fa-solid fa-briefcase"></i></div>
                                        <?php endif; ?>
                                        <span class="mp-item-badge">SERVICE</span>
                                    </div>
                                    <div class="mp-card-body">
                                        <span class="mp-category"><?= mp_h($service['category_name']); ?></span>
                                        <h3><?= mp_h($service['service_name']); ?></h3>
                                        <p class="mp-description"><?= mp_h($service['description']); ?></p>
                                        <div class="mp-card-bottom">
                                            <div class="mp-price" style="font-size:13px"><?= $service['pricing_type'] === 'fixed' ? mp_money($service['fixed_price']) : 'Request a quote'; ?><small><?= $service['pricing_type'] === 'fixed' ? 'Fixed service price' : 'Price confirmed by our team'; ?></small></div>
                                            <form class="mp-add-form" method="post">
                                                <input type="hidden" name="csrf_token" value="<?= mp_h($csrf); ?>">
                                                <input type="hidden" name="action" value="add_to_cart">
                                                <input type="hidden" name="item_type" value="service">
                                                <input type="hidden" name="item_id" value="<?= (int)$service['id']; ?>">
                                                <input class="mp-qty-input" type="number" name="quantity" min="1" max="99" value="1" aria-label="Quantity">
                                                <button class="mp-add-btn" type="submit"><i class="fa-solid fa-plus"></i> Add</button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($servicePages > 1): ?>
                        <nav class="mp-pagination" aria-label="Service pages">
                            <span>Page <?= $servicePage; ?> of <?= $servicePages; ?></span>
                            <div>
                                <?php if ($servicePage > 1): ?><a href="<?= mp_h(mp_page_url('service', $servicePage - 1, $search, $view, $categoryFilter)); ?>"><i class="fa-solid fa-chevron-left"></i> Previous</a><?php endif; ?>
                                <?php if ($servicePage < $servicePages): ?><a href="<?= mp_h(mp_page_url('service', $servicePage + 1, $search, $view, $categoryFilter)); ?>">Next <i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
                            </div>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <aside class="mp-cart-panel" id="shopping-cart">
                <div class="mp-cart-head">
                    <h3><i class="fa-solid fa-bag-shopping" style="color:#b58b45;margin-right:7px"></i> Your cart</h3>
                    <span><?= (int)$cartTotalCount; ?> item<?= $cartTotalCount === 1 ? '' : 's'; ?></span>
                </div>
                <div class="mp-cart-body">
                    <?php if (!$cartItems): ?>
                        <div class="mp-cart-empty">
                            <i class="fa-solid fa-basket-shopping"></i>
                            <strong>Your cart is waiting</strong>
                            <p>Add products or services and they will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="mp-cart-item">
                                <div class="mp-cart-thumb">
                                    <?php if ($item['image'] !== ''): ?><img src="<?= mp_h($item['image']); ?>" alt=""><?php else: ?><i class="fa-solid <?= $item['type'] === 'service' ? 'fa-briefcase' : 'fa-box'; ?>"></i><?php endif; ?>
                                </div>
                                <div>
                                    <div class="mp-cart-item-name"><?= mp_h($item['name']); ?></div>
                                    <div class="mp-cart-item-meta"><?= $item['type'] === 'service' ? ($item['pricing_type'] === 'fixed' ? 'Service · Fixed price' : 'Service · Quotation') : 'Product'; ?></div>
                                    <form class="mp-cart-controls" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= mp_h($csrf); ?>">
                                        <input type="hidden" name="action" value="update_cart">
                                        <input type="hidden" name="cart_key" value="<?= mp_h($item['key']); ?>">
                                        <input type="number" name="quantity" min="0" max="<?= $item['type'] === 'product' ? (int)$item['stock'] : 99; ?>" value="<?= (int)$item['quantity']; ?>" aria-label="Quantity">
                                        <button class="mp-mini-btn" type="submit" title="Update quantity"><i class="fa-solid fa-rotate"></i></button>
                                    </form>
                                    <?php if ($item['type'] === 'service'): ?>
                                        <form class="mp-service-note-form" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= mp_h($csrf); ?>">
                                            <input type="hidden" name="action" value="save_service_note">
                                            <input type="hidden" name="cart_key" value="<?= mp_h($item['key']); ?>">
                                            <label>Instructions for this service request</label>
                                            <textarea name="required_work" maxlength="2000" placeholder="What work do you need us to carry out?"><?= mp_h($item['required_work']); ?></textarea>
                                            <label>Additional notes (optional)</label>
                                            <textarea name="service_note" maxlength="2000" placeholder="Any other requirements or details..."><?= mp_h($item['service_note']); ?></textarea>
                                            <label>Preferred date (optional)</label>
                                            <input type="date" name="preferred_date" value="<?= mp_h($item['preferred_date']); ?>">
                                            <label>Service location (optional)</label>
                                            <input type="text" name="service_location" maxlength="500" value="<?= mp_h($item['service_location']); ?>" placeholder="Where should the service be delivered?">
                                            <button type="submit" class="mp-note-save"><i class="fa-solid fa-check"></i> Save this request</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="mp-cart-item-price"><?= $item['type'] === 'service' && $item['pricing_type'] !== 'fixed' ? 'Quote' : mp_money($item['line_total']); ?></div>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= mp_h($csrf); ?>">
                                        <input type="hidden" name="action" value="remove_cart_item">
                                        <input type="hidden" name="cart_key" value="<?= mp_h($item['key']); ?>">
                                        <button class="mp-mini-btn remove" type="submit"><i class="fa-regular fa-trash-can"></i> Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($cartItems): ?>
                    <div class="mp-cart-summary">
                        <div class="mp-summary-line"><span>Products subtotal</span><strong><?= mp_money($cartProductSubtotal); ?></strong></div>
                        <?php if ($cartServiceSubtotal > 0): ?><div class="mp-summary-line"><span>Fixed-price services</span><strong><?= mp_money($cartServiceSubtotal); ?></strong></div><?php endif; ?>
                        <div class="mp-summary-line"><span>Services</span><strong><?= $cartServiceCount > 0 ? $cartServiceCount . ' quotation item(s)' : '—'; ?></strong></div>
                        <div class="mp-summary-line mp-summary-total"><span>Current total</span><strong><?= mp_money($cartSubtotal); ?></strong></div>
                        <p class="mp-summary-note">Your cart is kept in this browser session and will be carried to the customer account page when you continue.</p>
                        <form method="post" action="<?= mp_h($marketplaceCartSubmitUrl); ?>">
                            <input type="hidden" name="csrf_token" value="<?= mp_h($csrf); ?>">
                            <input type="hidden" name="action" value="handoff_cart">
                            <input type="hidden" name="marketplace_cart_handoff" value="1">
                            <input type="hidden" name="company_id" value="<?= (int)$company_id; ?>">
                            <button class="mp-checkout-btn" type="submit">Continue with cart <i class="fa-solid fa-arrow-right" style="margin-left:9px"></i></button>
                        </form>
                        <p class="mp-summary-note">Your cart is saved in this browser session, even before sign-in.</p>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= mp_h($csrf); ?>">
                            <input type="hidden" name="action" value="clear_cart">
                            <button class="mp-clear-btn" type="submit">Clear cart</button>
                        </form>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>

<!-- Product / service detail viewer -->
<div class="mp-detail-modal" id="mpDetailModal" aria-hidden="true">
    <div class="mp-detail-backdrop" data-modal-close></div>
    <section class="mp-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="mpDetailTitle">
        <button type="button" class="mp-detail-close" aria-label="Close details" data-modal-close><i class="fa-solid fa-xmark"></i></button>
        <div class="mp-detail-layout">
            <div class="mp-detail-gallery">
                <div class="mp-detail-main-image" id="mpDetailMainImage">
                    <div class="mp-detail-no-image"><i class="fa-solid fa-image"></i><span>No images available</span></div>
                </div>
                <div class="mp-gallery-toolbar">
                    <span id="mpDetailImageCount">No product media</span>
                    <div class="mp-gallery-controls">
                        <button type="button" id="mpDetailPrevImage" aria-label="Previous image" disabled><i class="fa-solid fa-chevron-left"></i></button>
                        <button type="button" id="mpDetailNextImage" aria-label="Next image" disabled><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="mp-detail-thumbnails" id="mpDetailThumbnails"></div>
            </div>
            <div class="mp-detail-content">
                <span class="mp-detail-type" id="mpDetailType">PRODUCT</span>
                <span class="mp-detail-category" id="mpDetailCategory"></span>
                <h2 id="mpDetailTitle"></h2>
                <p class="mp-detail-description" id="mpDetailDescription"></p>
                <section class="mp-detail-features-wrap" id="mpDetailFeaturesWrap" hidden>
                    <div class="mp-detail-section-heading">
                        <h3>Product specifications</h3>
                        <span id="mpDetailFeatureCount"></span>
                    </div>
                    <p class="mp-detail-no-features" id="mpDetailNoFeatures">No specifications have been added for this product yet.</p>
                    <ul class="mp-detail-features" id="mpDetailFeatures"></ul>
                </section>
                <div class="mp-detail-price-row">
                    <strong id="mpDetailPrice"></strong>
                    <small id="mpDetailPriceNote"></small>
                </div>
                <div class="mp-detail-stock" id="mpDetailStock"></div>
                <div class="mp-detail-actions">
                    <label class="mp-detail-quantity-label">Quantity
                        <input type="number" id="mpDetailQuantity" min="1" max="99" value="1">
                    </label>
                    <button type="button" class="mp-detail-add" id="mpDetailAdd"><i class="fa-solid fa-bag-shopping"></i> Add to cart</button>
                </div>
                <p class="mp-detail-hint">Select a thumbnail to switch between photos and videos. Hover over a photo to magnify it. Click outside this window or press Escape to close.</p>
            </div>
        </div>
    </section>
</div>

<footer class="mp-footer">
    <div class="mp-footer-inner">
        <small>© <?= date('Y'); ?> <?= mp_h($companyName); ?>. All rights reserved.</small>
        <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Return to company website</a>
    </div>
</footer>

<script>
(function () {
    'use strict';

    const modal = document.getElementById('mpDetailModal');
    if (modal) {
        const title = document.getElementById('mpDetailTitle');
        const type = document.getElementById('mpDetailType');
        const category = document.getElementById('mpDetailCategory');
        const description = document.getElementById('mpDetailDescription');
        const featuresWrap = document.getElementById('mpDetailFeaturesWrap');
        const featuresList = document.getElementById('mpDetailFeatures');
        const price = document.getElementById('mpDetailPrice');
        const priceNote = document.getElementById('mpDetailPriceNote');
        const stock = document.getElementById('mpDetailStock');
        const mainImage = document.getElementById('mpDetailMainImage');
        const thumbnails = document.getElementById('mpDetailThumbnails');
        const imageCount = document.getElementById('mpDetailImageCount');
        const prevImage = document.getElementById('mpDetailPrevImage');
        const nextImage = document.getElementById('mpDetailNextImage');
        const featureCount = document.getElementById('mpDetailFeatureCount');
        const noFeatures = document.getElementById('mpDetailNoFeatures');
        const quantity = document.getElementById('mpDetailQuantity');
        const addButton = document.getElementById('mpDetailAdd');
        let activeCard = null;
        let activeMedia = [];
        let activeMediaIndex = 0;

        function showMedia(index) {
            if (!activeMedia.length) {
                mainImage.classList.remove('is-video');
                mainImage.replaceChildren();
                const fallback = document.createElement('div');
                fallback.className = 'mp-detail-no-image';
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-photo-film';
                const label = document.createElement('span');
                label.textContent = 'No product photos or videos have been uploaded';
                fallback.append(icon, label);
                mainImage.appendChild(fallback);
                imageCount.textContent = 'No product media';
                prevImage.disabled = true;
                nextImage.disabled = true;
                return;
            }

            activeMediaIndex = (index + activeMedia.length) % activeMedia.length;
            const item = activeMedia[activeMediaIndex];
            const isVideo = item.type === 'video';
            const position = activeMedia.slice(0, activeMediaIndex + 1).filter(m => m.type === item.type).length;
            imageCount.textContent = (isVideo ? 'Video ' : 'Photo ') + position + ' · ' +
                activeMedia.length + ' media item' + (activeMedia.length === 1 ? '' : 's');
            prevImage.disabled = activeMedia.length < 2;
            nextImage.disabled = activeMedia.length < 2;
            mainImage.replaceChildren();
            mainImage.classList.toggle('is-video', isVideo);

            if (isVideo) {
                const video = document.createElement('video');
                video.controls = true;
                video.playsInline = true;
                video.preload = 'metadata';
                if (item.thumbnail) video.poster = item.thumbnail;
                const source = document.createElement('source');
                source.src = item.src;
                if (item.mime) source.type = item.mime;
                video.appendChild(source);
                video.appendChild(document.createTextNode('Your browser does not support HTML5 video.'));
                mainImage.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = item.src;
                img.alt = title.textContent;
                img.draggable = false;
                img.onerror = function () {
                    this.remove();
                    const fallback = document.createElement('div');
                    fallback.className = 'mp-detail-no-image';
                    fallback.textContent = 'This image file could not be found';
                    mainImage.appendChild(fallback);
                };
                mainImage.appendChild(img);
                const tip = document.createElement('span');
                tip.className = 'mp-zoom-tip';
                tip.innerHTML = '<i class="fa-solid fa-magnifying-glass-plus"></i> Hover to zoom';
                mainImage.appendChild(tip);
            }

            thumbnails.querySelectorAll('.mp-detail-thumb').forEach((button, i) => {
                button.classList.toggle('active', i === activeMediaIndex);
            });
        }

        function openDetails(card) {
            let data;
            try { data = JSON.parse(card.getAttribute('data-marketplace-detail') || '{}'); }
            catch (error) { return; }

            activeCard = card;
            activeMedia = Array.isArray(data.media) ? data.media : [];
            if (!activeMedia.length && Array.isArray(data.images)) {
                activeMedia = data.images.map(function (src) {
                    return { type: 'image', src: src, thumbnail: src };
                });
            }
            type.textContent = data.type === 'service' ? 'PROFESSIONAL SERVICE' : 'PRODUCT';
            category.textContent = data.category || '';
            title.textContent = data.name || 'Marketplace item';
            description.textContent = data.description || 'No additional description has been provided.';
            const productFeatures = Array.isArray(data.features) ? data.features : [];
            featuresList.replaceChildren();
            featuresWrap.hidden = data.type !== 'product';
            noFeatures.hidden = productFeatures.length > 0;
            featuresList.hidden = productFeatures.length === 0;
            featureCount.textContent = productFeatures.length ? productFeatures.length + ' listed' : 'Not supplied';
            productFeatures.forEach(function (feature) {
                const li = document.createElement('li');
                const label = document.createElement('strong');
                const value = document.createElement('span');
                label.textContent = feature.name || 'Feature';
                value.textContent = feature.value || '';
                li.append(label, value);
                featuresList.appendChild(li);
            });
            price.textContent = data.price || '';
            priceNote.textContent = data.priceNote || '';
            stock.textContent = '';
            stock.className = 'mp-detail-stock';

            const sourceQty = card.querySelector('input[name="quantity"]');
            const sourceAdd = card.querySelector('button[type="submit"]');
            quantity.value = sourceQty ? sourceQty.value : '1';
            quantity.max = sourceQty ? (sourceQty.max || '99') : '99';

            if (data.type === 'product') {
                const available = Number(data.stock || 0);
                stock.textContent = available > 0 ? 'In stock · ' + available + ' available' : 'Currently out of stock';
                if (available < 1) stock.classList.add('out');
            } else {
                stock.textContent = data.available === false ? 'Currently unavailable' : 'Available for service requests';
                if (data.available === false) stock.classList.add('out');
            }

            thumbnails.replaceChildren();
            activeMedia.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'mp-detail-thumb' + (index === 0 ? ' active' : '');
                button.setAttribute('aria-label', item.type === 'video' ? 'Play product video ' + (index + 1) : 'View product photo ' + (index + 1));
                if (item.type === 'video') {
                    const preview = document.createElement('span');
                    preview.className = 'mp-video-thumb';
                    if (item.thumbnail) {
                        const img = document.createElement('img');
                        img.src = item.thumbnail;
                        img.alt = '';
                        preview.appendChild(img);
                    }
                    const play = document.createElement('i');
                    play.className = 'fa-solid fa-circle-play';
                    preview.appendChild(play);
                    button.appendChild(preview);
                } else {
                    const img = document.createElement('img');
                    img.src = item.thumbnail || item.src;
                    img.alt = '';
                    button.appendChild(img);
                }
                button.addEventListener('click', () => showMedia(index));
                thumbnails.appendChild(button);
            });
            showMedia(0);

            const isUnavailable = (data.type === 'product' && Number(data.stock || 0) < 1) ||
                (data.type === 'service' && data.available === false);
            addButton.disabled = isUnavailable || !sourceAdd || sourceAdd.disabled;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            window.setTimeout(() => document.querySelector('.mp-detail-close')?.focus(), 30);
        }

        prevImage.addEventListener('click', function () {
            showMedia(activeMediaIndex - 1);
        });
        nextImage.addEventListener('click', function () {
            showMedia(activeMediaIndex + 1);
        });

        mainImage.addEventListener('mousemove', function (event) {
            const img = mainImage.querySelector('img');
            if (!img || mainImage.classList.contains('is-video')) return;
            const rect = mainImage.getBoundingClientRect();
            const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
            const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100));
            img.style.transformOrigin = x + '% ' + y + '%';
            img.style.transform = 'scale(1.85)';
        });
        mainImage.addEventListener('mouseleave', function () {
            const img = mainImage.querySelector('img');
            if (img) {
                img.style.transform = '';
                img.style.transformOrigin = 'center center';
            }
        });
        mainImage.addEventListener('click', function () {
            const img = mainImage.querySelector('img');
            if (!img) return;
            img.style.transform = img.style.transform ? '' : 'scale(1.85)';
        });

        function closeDetails() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            activeCard = null;
            const playingVideo = mainImage.querySelector('video');
            if (playingVideo) {
                playingVideo.pause();
                playingVideo.removeAttribute('src');
                playingVideo.querySelectorAll('source').forEach(source => source.removeAttribute('src'));
                playingVideo.load();
            }
            activeMedia = [];
        }

        document.addEventListener('click', function (event) {
            const card = event.target.closest('.mp-card-clickable');
            if (!card) return;
            if (event.target.closest('form,button,a,input,select,textarea,label')) return;
            openDetails(card);
        });

        document.addEventListener('keydown', function (event) {
            const card = event.target.closest && event.target.closest('.mp-card-clickable');
            if (card && (event.key === 'Enter' || event.key === ' ')) {
                if (event.target.closest('form,button,a,input,select,textarea,label')) return;
                event.preventDefault();
                openDetails(card);
            }
            if (event.key === 'Escape' && modal.classList.contains('is-open')) closeDetails();
        });

        modal.querySelectorAll('[data-modal-close]').forEach(button => {
            button.addEventListener('click', closeDetails);
        });

        quantity.addEventListener('input', function () {
            let value = parseInt(quantity.value || '1', 10);
            const max = parseInt(quantity.max || '99', 10);
            if (!Number.isFinite(value)) value = 1;
            quantity.value = String(Math.max(1, Math.min(max, value)));
        });

        addButton.addEventListener('click', function () {
            if (!activeCard) return;
            const sourceQty = activeCard.querySelector('input[name="quantity"]');
            const sourceForm = activeCard.querySelector('form.mp-add-form');
            if (!sourceQty || !sourceForm) return;
            sourceQty.value = quantity.value;
            closeDetails();
            if (sourceForm.requestSubmit) sourceForm.requestSubmit();
            else sourceForm.submit();
        });
    }


})();
</script>
</body>
</html>
