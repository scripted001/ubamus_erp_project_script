<?php

include('../config/db.php');

$company_id = $_SESSION['company_id'];

$option     = $_POST['search_option'] ?? '';
$value      = trim($_POST['search_value'] ?? '');
$start_date = $_POST['start_date'] ?? '';
$end_date   = $_POST['end_date'] ?? '';

/*
|--------------------------------------------------------------------------
| BUILD FILTER
|--------------------------------------------------------------------------
*/

$where  = " WHERE company_id = ? ";
$params = [$company_id];


/*
|--------------------------------------------------------------------------
| SEARCH BY INVOICE / CUSTOMER
|--------------------------------------------------------------------------
*/

if ($value !== '') {

    if ($option === 'invoice') {

        $where .= " AND invoice_number LIKE ?";
        $params[] = "%{$value}%";

    } elseif ($option === 'customer') {

        $where .= " AND customer_name LIKE ?";
        $params[] = "%{$value}%";
    }
}


/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

if (!empty($start_date) && !empty($end_date)) {

    $where .= " AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)";

    $params[] = $start_date;
    $params[] = $end_date;

} elseif (!empty($start_date)) {

    $where .= " AND created_at >= ?";

    $params[] = $start_date;

} elseif (!empty($end_date)) {

    $where .= " AND created_at < DATE_ADD(?, INTERVAL 1 DAY)";

    $params[] = $end_date;
}


/*
|--------------------------------------------------------------------------
| GET FILTERED SALES
|--------------------------------------------------------------------------
*/

$query = "
    SELECT *
    FROM sales
    {$where}
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| KPI CALCULATIONS
|--------------------------------------------------------------------------
|
| IMPORTANT:
| These calculations use the EXACT SAME filters
| as the table.
|
*/


/*
|--------------------------------------------------------------------------
| TOTAL REVENUE
|--------------------------------------------------------------------------
*/

$kpiQuery = "
    SELECT
        COUNT(*) AS transactions,
        COALESCE(SUM(final_total), 0) AS revenue,
        COALESCE(SUM(amount_paid), 0) AS amount_paid,
        COALESCE(SUM(balance), 0) AS balance,
        COALESCE(AVG(final_total), 0) AS average_sale
    FROM sales
    {$where}
";

$stmtKpi = $pdo->prepare($kpiQuery);
$stmtKpi->execute($params);

$kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PROFIT
|--------------------------------------------------------------------------
|
| We calculate profit from sale_items + products.
|
| Profit =
| (Selling Price - Cost Price) × Quantity
|
*/

$profitQuery = "
    SELECT
        COALESCE(
            SUM(
                (si.unit_price - COALESCE(p.cost_price, 0))
                * si.quantity
            ),
            0
        ) AS profit

    FROM sale_items si

    INNER JOIN sales s
        ON s.id = si.sale_id

    LEFT JOIN products p
        ON p.id = si.product_id

    {$where}
";

$stmtProfit = $pdo->prepare($profitQuery);
$stmtProfit->execute($params);

$profitData = $stmtProfit->fetch(PDO::FETCH_ASSOC);

$profit = (float)($profitData['profit'] ?? 0);


/*
|--------------------------------------------------------------------------
| TOP PRODUCT
|--------------------------------------------------------------------------
*/

$topProductQuery = "
    SELECT
        COALESCE(p.name, 'Unknown Product') AS product_name,
        COALESCE(SUM(si.quantity), 0) AS quantity

    FROM sale_items si

    INNER JOIN sales s
        ON s.id = si.sale_id

    LEFT JOIN products p
        ON p.id = si.product_id

    {$where}

    GROUP BY si.product_id, p.name

    ORDER BY quantity DESC

    LIMIT 1
";

$stmtTop = $pdo->prepare($topProductQuery);
$stmtTop->execute($params);

$topProduct = $stmtTop->fetch(PDO::FETCH_ASSOC);

$topProductName = $topProduct['product_name'] ?? 'No product';
$topProductQty  = (float)($topProduct['quantity'] ?? 0);


/*
|--------------------------------------------------------------------------
| BUILD TABLE HTML
|--------------------------------------------------------------------------
*/

$tableHtml = '';

if ($sales) {

    foreach ($sales as $sale) {

        /*
        |--------------------------------------------------------------------------
        | LOCATION
        |--------------------------------------------------------------------------
        */

        $location_id = $sale['location_id'];

        $stmt = $pdo->prepare("
            SELECT name
            FROM locations
            WHERE id = ?
        ");

        $stmt->execute([$location_id]);

        $location_name = $stmt->fetch(PDO::FETCH_ASSOC);

        $loc_name = $location_name['name'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | SELLER EMAIL
        |--------------------------------------------------------------------------
        */

        $seller_id = $sale['user_id'];

        $stmt = $pdo->prepare("
            SELECT email
            FROM users
            WHERE id = ?
        ");

        $stmt->execute([$seller_id]);

        $seller = $stmt->fetch(PDO::FETCH_ASSOC);

        $seller_email = $seller['email'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | SELLER NAME
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT firstname, lastname
            FROM staffs
            WHERE email = ?
        ");

        $stmt->execute([$seller_email]);

        $seller_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $seller_fullname =
            trim(
                ($seller_data['firstname'] ?? '') .
                ' ' .
                ($seller_data['lastname'] ?? '')
            );


        /*
        |--------------------------------------------------------------------------
        | DUE DATE
        |--------------------------------------------------------------------------
        */

        if (
            $sale['due_date'] == 0 ||
            empty($sale['due_date'])
        ) {

            $due_date = "00:00:00";

        } else {

            $due_date = date(
                "M d, Y h:i A",
                strtotime($sale['due_date'])
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE ROW
        |--------------------------------------------------------------------------
        */

        $tableHtml .= "

        <tr class='border-t border-gray-100 dark:border-gray-800'>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    {$sale['invoice_number']}
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    {$sale['customer_name']}
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    {$loc_name}
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    {$seller_fullname}
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    ₦" . number_format((float)$sale['grand_total'], 2) . "
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    {$sale['discount_percent']}%
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='font-medium text-success-600 text-theme-sm dark:text-white/90'>
                    ₦" . number_format((float)$sale['discount_amount'], 2) . "
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='font-medium text-success-600 text-theme-sm dark:text-white/90'>
                    ₦" . number_format((float)$sale['final_total'], 2) . "
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    {$sale['payment_status']}
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-success-600 text-theme-sm dark:text-gray-400'>
                    ₦" . number_format((float)$sale['amount_paid'], 2) . "
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    ₦" . number_format((float)$sale['balance'], 2) . "
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    {$due_date}
                </p>
            </td>

            <td class='px-6 py-3.5'>
                <p class='text-gray-500 text-theme-sm dark:text-gray-400'>
                    " . date(
                        'M d, Y h:i A',
                        strtotime($sale['created_at'])
                    ) . "
                </p>
            </td>

            <td class='px-6 py-3.5'>

                <a
                    href='../invoices/invoice_{$sale['invoice_number']}.pdf'
                    target='_blank'
                    class='text-blue-600 hover:underline text-theme-sm'
                >
                    View
                </a>

            </td>

        </tr>";
    }

} else {

    $tableHtml = "

        <tr>

            <td
                colspan='14'
                class='px-6 py-4 text-center text-gray-500 dark:text-gray-400'
            >
                No results found
            </td>

        </tr>

    ";
}


/*
|--------------------------------------------------------------------------
| RETURN JSON
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json');

echo json_encode([

    'success' => true,

    'table' => $tableHtml,

    'kpis' => [

        'transactions' => (int)($kpi['transactions'] ?? 0),

        'revenue' => (float)($kpi['revenue'] ?? 0),

        'amount_paid' => (float)($kpi['amount_paid'] ?? 0),

        'balance' => (float)($kpi['balance'] ?? 0),

        'average_sale' => (float)($kpi['average_sale'] ?? 0),

        'profit' => $profit,

        'top_product' => $topProductName,

        'top_product_qty' => $topProductQty

    ]

]);

exit;
?>