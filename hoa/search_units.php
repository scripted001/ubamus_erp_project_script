
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

include('../config/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $company_id = $_SESSION['company_id'] ?? null;
    $search = trim($_GET['q'] ?? '');

    if ($company_id === null || $company_id === '') {
        echo json_encode([
            'success' => true,
            'units' => [],
            'total' => 0
        ]);
        exit;
    }

    // Total units belonging to this company, independent of search.
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM units
        WHERE company_id = ?
    ");
    $countStmt->execute([$company_id]);
    $total = (int) $countStmt->fetchColumn();

    $sql = "
        SELECT id, unit_name, unit_type, created_by, created_at
        FROM units
        WHERE company_id = ?
    ";

    $params = [$company_id];

    if ($search !== '') {
        $sql .= "
            AND (
                unit_name LIKE ?
                OR unit_type LIKE ?
                OR CAST(id AS CHAR) LIKE ?
            )
        ";

        $searchTerm = '%' . $search . '%';

        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $units = [];

    foreach ($rows as $row) {
        $formattedDate = '—';

        if (!empty($row['created_at'])) {
            $timestamp = strtotime((string) $row['created_at']);

            if ($timestamp !== false) {
                $formattedDate = date('M d, Y', $timestamp);
            }
        }

        $units[] = [
            'id' => $row['id'],
            'unit_name' => $row['unit_name'] ?? '',
            'unit_type' => $row['unit_type'] ?? '',
            'created_by' => $row['created_by'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'formatted_date' => $formattedDate
        ];
    }

    echo json_encode([
        'success' => true,
        'units' => $units,
        'total' => $total
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving units.'
    ]);
}
