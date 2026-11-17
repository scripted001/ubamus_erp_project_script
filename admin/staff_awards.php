<?php


include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

date_default_timezone_set('Africa/Lagos');

/*
|--------------------------------------------------------------------------
| SESSION AND BRANCH SECURITY
|--------------------------------------------------------------------------
*/

$company_id = (int)($_SESSION['company_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);
$user_email = trim((string)($_SESSION['user_email'] ?? $_SESSION['email'] ?? ''));

if ($company_id <= 0 || $location_id <= 0) {
    http_response_code(403);
    exit('Your company or branch session could not be identified. Please log in again.');
}

/*
|--------------------------------------------------------------------------
| VERIFY THAT THE ACTIVE BRANCH BELONGS TO THE COMPANY
|--------------------------------------------------------------------------
*/

$locationStmt = $pdo->prepare("
    SELECT id
    FROM locations
    WHERE id = ?
      AND company_id = ?
    LIMIT 1
");
$locationStmt->execute([$location_id, $company_id]);

if (!$locationStmt->fetchColumn()) {
    http_response_code(403);
    exit('The active branch could not be verified.');
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function awardsEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function awardsMoney($value): string
{
    return '₦' . number_format((float)$value, 2);
}

function awardsMedalIcon(int $rank): string
{
    return match ($rank) {
        1 => '<i class="fa-solid fa-trophy"></i>',
        2 => '<i class="fa-solid fa-medal"></i>',
        3 => '<i class="fa-solid fa-award"></i>',
        default => '<i class="fa-solid fa-star"></i>',
    };
}

/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

$start_date = (string)($_GET['start_date'] ?? date('Y-m-01'));
$end_date = (string)($_GET['end_date'] ?? $today);

$isValidDate = static function (string $date): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parts = explode('-', $date);

    return checkdate(
        (int)$parts[1],
        (int)$parts[2],
        (int)$parts[0]
    );
};

if (!$isValidDate($start_date)) {
    $start_date = date('Y-m-01');
}

if (!$isValidDate($end_date)) {
    $end_date = $today;
}

if ($start_date > $end_date) {
    [$start_date, $end_date] = [$end_date, $start_date];
}

$startDateTime = $start_date . ' 00:00:00';
$endDateTime = $end_date . ' 23:59:59';

/*
|--------------------------------------------------------------------------
| RANKING ORDER
|--------------------------------------------------------------------------
*/

$sort = (string)($_GET['sort'] ?? 'high');

if (!in_array($sort, ['high', 'low'], true)) {
    $sort = 'high';
}

$orderDirection = $sort === 'low' ? 'ASC' : 'DESC';

/*
|--------------------------------------------------------------------------
| COMPANY NAME
|--------------------------------------------------------------------------
*/

$displayCompanyName = $company_name ?? 'Company';

/*
|--------------------------------------------------------------------------
| TOP FIVE SALES PERFORMERS
|--------------------------------------------------------------------------
|
| Only sales belonging to the active company and branch are considered.
| A staff member must also belong to that same company and branch.
|
*/

$salesQuery = "
    SELECT
        s.id AS staff_id,
        CONCAT(s.firstname, ' ', s.lastname) AS fullname,
        s.profile,
        s.email,
        s.phone,

        COUNT(sa.id) AS num_sales,
        COALESCE(SUM(sa.final_total), 0) AS total_sales

    FROM sales sa

    INNER JOIN users u
        ON u.id = sa.user_id

    INNER JOIN staffs s
        ON LOWER(TRIM(s.email)) = LOWER(TRIM(u.email))
        AND s.company_id = sa.company_id
        AND s.location_id = sa.location_id

    WHERE sa.company_id = :company_id
      AND sa.location_id = :location_id
      AND sa.created_at BETWEEN :start_date AND :end_date

    GROUP BY
        s.id,
        s.firstname,
        s.lastname,
        s.profile,
        s.email,
        s.phone

    ORDER BY
        total_sales $orderDirection,
        num_sales $orderDirection

    LIMIT 5
";

$stmt = $pdo->prepare($salesQuery);

$stmt->execute([
    ':company_id' => $company_id,
    ':location_id' => $location_id,
    ':start_date' => $startDateTime,
    ':end_date' => $endDateTime
]);

$topSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| TOP FIVE JOB PERFORMERS
|--------------------------------------------------------------------------
*/

$jobQuery = "
    SELECT
        s.id AS staff_id,
        CONCAT(s.firstname, ' ', s.lastname) AS fullname,
        s.profile,
        s.email,
        s.phone,

        COUNT(j.id) AS num_jobs,
        COALESCE(SUM(j.final_total), 0) AS total_jobs

    FROM jobs j

    INNER JOIN users u
        ON u.id = j.user_id

    INNER JOIN staffs s
        ON LOWER(TRIM(s.email)) = LOWER(TRIM(u.email))
        AND s.company_id = j.company_id
        AND s.location_id = j.location_id

    WHERE j.company_id = :company_id
      AND j.location_id = :location_id
      AND j.created_at BETWEEN :start_date AND :end_date

    GROUP BY
        s.id,
        s.firstname,
        s.lastname,
        s.profile,
        s.email,
        s.phone

    ORDER BY
        total_jobs $orderDirection,
        num_jobs $orderDirection

    LIMIT 5
";

$stmt = $pdo->prepare($jobQuery);

$stmt->execute([
    ':company_id' => $company_id,
    ':location_id' => $location_id,
    ':start_date' => $startDateTime,
    ':end_date' => $endDateTime
]);

$topJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| TOP FIVE OVERALL PERFORMERS
|--------------------------------------------------------------------------
|
| Overall score = sales revenue + job revenue.
| The outer staff list is restricted to the active branch.
|
*/

$overallQuery = "
    SELECT
        s.id AS staff_id,
        CONCAT(s.firstname, ' ', s.lastname) AS fullname,
        s.profile,
        s.email,
        s.phone,

        COALESCE(sa.total_sales, 0) AS total_sales,
        COALESCE(sa.num_sales, 0) AS num_sales,

        COALESCE(jb.total_jobs, 0) AS total_jobs,
        COALESCE(jb.num_jobs, 0) AS num_jobs,

        (
            COALESCE(sa.total_sales, 0)
            +
            COALESCE(jb.total_jobs, 0)
        ) AS overall_score

    FROM staffs s

    LEFT JOIN (
        SELECT
            st.id AS staff_id,
            COALESCE(SUM(sa.final_total), 0) AS total_sales,
            COUNT(sa.id) AS num_sales

        FROM sales sa

        INNER JOIN users u
            ON u.id = sa.user_id

        INNER JOIN staffs st
            ON LOWER(TRIM(st.email)) = LOWER(TRIM(u.email))
            AND st.company_id = sa.company_id
            AND st.location_id = sa.location_id

        WHERE sa.company_id = :sales_company
          AND sa.location_id = :sales_location
          AND sa.created_at BETWEEN :sales_start AND :sales_end

        GROUP BY st.id

    ) sa ON sa.staff_id = s.id

    LEFT JOIN (
        SELECT
            st.id AS staff_id,
            COALESCE(SUM(j.final_total), 0) AS total_jobs,
            COUNT(j.id) AS num_jobs

        FROM jobs j

        INNER JOIN users u
            ON u.id = j.user_id

        INNER JOIN staffs st
            ON LOWER(TRIM(st.email)) = LOWER(TRIM(u.email))
            AND st.company_id = j.company_id
            AND st.location_id = j.location_id

        WHERE j.company_id = :jobs_company
          AND j.location_id = :jobs_location
          AND j.created_at BETWEEN :jobs_start AND :jobs_end

        GROUP BY st.id

    ) jb ON jb.staff_id = s.id

    WHERE s.company_id = :main_company
      AND s.location_id = :main_location

    ORDER BY
        overall_score $orderDirection,
        total_sales $orderDirection,
        total_jobs $orderDirection

    LIMIT 5
";

$stmt = $pdo->prepare($overallQuery);

$stmt->execute([
    ':sales_company' => $company_id,
    ':sales_location' => $location_id,
    ':sales_start' => $startDateTime,
    ':sales_end' => $endDateTime,

    ':jobs_company' => $company_id,
    ':jobs_location' => $location_id,
    ':jobs_start' => $startDateTime,
    ':jobs_end' => $endDateTime,

    ':main_company' => $company_id,
    ':main_location' => $location_id
]);

$topOverall = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| MY PERFORMANCE
|--------------------------------------------------------------------------
*/

$myPerformance = [
    'sales_count' => 0,
    'sales_total' => 0,
    'jobs_count' => 0,
    'jobs_total' => 0,
    'overall_score' => 0
];

if ($user_email !== '') {

    /*
    | MY SALES
    */

    $mySalesQuery = "
        SELECT
            COUNT(sa.id) AS sales_count,
            COALESCE(SUM(sa.final_total), 0) AS sales_total

        FROM sales sa

        INNER JOIN users u
            ON u.id = sa.user_id

        WHERE sa.company_id = :company_id
          AND sa.location_id = :location_id
          AND LOWER(TRIM(u.email)) = LOWER(TRIM(:email))
          AND sa.created_at BETWEEN :start_date AND :end_date
    ";

    $stmt = $pdo->prepare($mySalesQuery);

    $stmt->execute([
        ':company_id' => $company_id,
        ':location_id' => $location_id,
        ':email' => $user_email,
        ':start_date' => $startDateTime,
        ':end_date' => $endDateTime
    ]);

    $mySales = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    | MY JOBS
    */

    $myJobsQuery = "
        SELECT
            COUNT(j.id) AS jobs_count,
            COALESCE(SUM(j.final_total), 0) AS jobs_total

        FROM jobs j

        INNER JOIN users u
            ON u.id = j.user_id

        WHERE j.company_id = :company_id
          AND j.location_id = :location_id
          AND LOWER(TRIM(u.email)) = LOWER(TRIM(:email))
          AND j.created_at BETWEEN :start_date AND :end_date
    ";

    $stmt = $pdo->prepare($myJobsQuery);

    $stmt->execute([
        ':company_id' => $company_id,
        ':location_id' => $location_id,
        ':email' => $user_email,
        ':start_date' => $startDateTime,
        ':end_date' => $endDateTime
    ]);

    $myJobs = $stmt->fetch(PDO::FETCH_ASSOC);

    $myPerformance['sales_count'] =
        (int)($mySales['sales_count'] ?? 0);

    $myPerformance['sales_total'] =
        (float)($mySales['sales_total'] ?? 0);

    $myPerformance['jobs_count'] =
        (int)($myJobs['jobs_count'] ?? 0);

    $myPerformance['jobs_total'] =
        (float)($myJobs['jobs_total'] ?? 0);

    $myPerformance['overall_score'] =
        $myPerformance['sales_total'] +
        $myPerformance['jobs_total'];
}

/*
|--------------------------------------------------------------------------
| CURRENT USER RANK
|--------------------------------------------------------------------------
*/

$myRank = null;

foreach ($topOverall as $index => $staff) {
    if (
        $user_email !== '' &&
        strtolower(trim((string)$staff['email'])) ===
        strtolower(trim($user_email))
    ) {
        $myRank = $index + 1;
        break;
    }
}

/*
|--------------------------------------------------------------------------
| PROFILE IMAGE HELPER
|--------------------------------------------------------------------------
*/

function awardsProfileImage($profile): string
{
    if (empty($profile)) {
        return '../assets/images/user.png';
    }

    return '../' . ltrim((string)$profile, '/');
}

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#080b12">

<title><?= awardsEscape($displayCompanyName); ?> | Staff Awards</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="awards.css">

<style>
:root {
    --aw-bg: #070a10;
    --aw-surface: rgba(255,255,255,.055);
    --aw-border: rgba(255,255,255,.12);
    --aw-border-soft: rgba(255,255,255,.075);
    --aw-text: #f5f7fa;
    --aw-muted: #9ca3af;
    --aw-dim: #6b7280;
    --aw-gold: #d8b56a;
    --aw-gold-soft: rgba(216,181,106,.15);
    --aw-shadow: 0 20px 70px rgba(0,0,0,.35);
    --aw-radius: 22px;
}

.awards-page {
    position: relative;
    min-height: calc(100vh - 80px);
    padding: 20px;
    color: var(--aw-text);
    overflow: hidden;
}

.awards-page::before {
    content: "";
    position: fixed;
    inset: 0;
    pointer-events: none;
    opacity: .28;
    background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(ellipse at center, black 10%, transparent 80%);
}

.awards-hero {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    padding: 30px;
    margin-bottom: 22px;
    border: 1px solid var(--aw-border);
    border-radius: var(--aw-radius);
    background: linear-gradient(135deg, rgba(255,255,255,.075), rgba(255,255,255,.025));
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    box-shadow: var(--aw-shadow);
    overflow: hidden;
}

.awards-hero::after {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    right: -100px;
    top: -140px;
    border-radius: 50%;
    border: 1px solid rgba(216,181,106,.16);
    box-shadow: 0 0 80px rgba(216,181,106,.08);
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--aw-gold);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.hero-title {
    margin: 0;
    font-size: clamp(28px, 4vw, 48px);
    line-height: 1.05;
    font-weight: 800;
    letter-spacing: -1.5px;
}

.hero-description {
    max-width: 680px;
    margin-top: 12px;
    color: var(--aw-muted);
    font-size: 14px;
    line-height: 1.7;
}

.hero-icon {
    width: 100px;
    height: 100px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 1px solid var(--aw-border);
    background: rgba(255,255,255,.04);
    color: var(--aw-gold);
    font-size: 38px;
    box-shadow: inset 0 0 30px rgba(255,255,255,.025), 0 0 50px rgba(216,181,106,.08);
}

.awards-filter {
    position: relative;
    z-index: 5;
    display: grid;
    grid-template-columns: minmax(180px,1fr) minmax(180px,1fr) minmax(180px,.8fr) auto;
    gap: 14px;
    padding: 18px;
    margin-bottom: 28px;
    border: 1px solid var(--aw-border);
    border-radius: var(--aw-radius);
    background: rgba(255,255,255,.045);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: var(--aw-shadow);
}

.filter-field {
    position: relative;
}

.filter-label {
    display: block;
    margin-bottom: 7px;
    color: var(--aw-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
}

.filter-control {
    width: 100%;
    min-height: 46px;
    padding: 0 14px 0 42px;
    border: 1px solid var(--aw-border);
    border-radius: 13px;
    outline: none;
    color: var(--aw-text);
    background: rgba(0,0,0,.28);
    font-size: 13px;
    transition: .2s ease;
    color-scheme: dark;
}

.filter-control:focus {
    border-color: rgba(216,181,106,.55);
    box-shadow: 0 0 0 3px rgba(216,181,106,.08);
}

.filter-icon {
    position: absolute;
    left: 15px;
    bottom: 15px;
    color: var(--aw-dim);
    pointer-events: none;
}

.filter-submit {
    align-self: end;
    min-height: 46px;
    padding: 0 22px;
    border: 1px solid rgba(216,181,106,.45);
    border-radius: 13px;
    color: #0a0c10;
    background: var(--aw-gold);
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: .2s ease;
}

.filter-submit:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 10px 30px rgba(216,181,106,.15);
}

.performance-grid {
    display: grid;
    grid-template-columns: repeat(4,minmax(0,1fr));
    gap: 14px;
    margin-bottom: 28px;
}

.performance-card {
    position: relative;
    padding: 20px;
    border: 1px solid var(--aw-border-soft);
    border-radius: 18px;
    background: var(--aw-surface);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    overflow: hidden;
}

.performance-card::after {
    content: "";
    position: absolute;
    width: 100px;
    height: 100px;
    right: -50px;
    bottom: -50px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.05);
}

.performance-icon {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    border-radius: 11px;
    color: var(--aw-gold);
    background: var(--aw-gold-soft);
}

.performance-label {
    color: var(--aw-muted);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.performance-value {
    margin-top: 5px;
    font-size: 21px;
    font-weight: 800;
    color: var(--aw-text);
}

.awards-section {
    margin-bottom: 30px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 19px;
    font-weight: 800;
}

.section-title i {
    color: var(--aw-gold);
    font-size: 16px;
}

.section-subtitle {
    color: var(--aw-dim);
    font-size: 12px;
}

.ranking-grid {
    display: grid;
    grid-template-columns: repeat(5,minmax(0,1fr));
    gap: 13px;
}

.staff-card {
    position: relative;
    min-width: 0;
    padding: 18px;
    border: 1px solid var(--aw-border-soft);
    border-radius: 18px;
    background: linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025));
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    box-shadow: 0 15px 40px rgba(0,0,0,.18);
    transition: transform .2s ease, border-color .2s ease, background .2s ease;
}

.staff-card:hover {
    transform: translateY(-4px);
    border-color: rgba(216,181,106,.28);
    background: rgba(255,255,255,.075);
}

.rank-number {
    position: absolute;
    top: 13px;
    right: 13px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 1px solid var(--aw-border);
    color: var(--aw-muted);
    background: rgba(0,0,0,.2);
    font-size: 11px;
    font-weight: 800;
}

.rank-number.top {
    color: var(--aw-gold);
    border-color: rgba(216,181,106,.4);
    background: rgba(216,181,106,.08);
}

.staff-avatar {
    width: 68px;
    height: 68px;
    margin-bottom: 14px;
    border-radius: 50%;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.05);
}

.staff-avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.staff-name {
    margin: 0;
    color: var(--aw-text);
    font-size: 14px;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.staff-email {
    margin-top: 4px;
    color: var(--aw-dim);
    font-size: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.staff-metric {
    margin-top: 16px;
    padding-top: 13px;
    border-top: 1px solid var(--aw-border-soft);
}

.metric-label {
    color: var(--aw-dim);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .8px;
}

.metric-value {
    margin-top: 3px;
    color: var(--aw-text);
    font-size: 16px;
    font-weight: 800;
}

.metric-count {
    margin-top: 3px;
    color: var(--aw-muted);
    font-size: 10px;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 14px;
    padding: 6px 9px;
    border: 1px solid var(--aw-border);
    border-radius: 999px;
    color: var(--aw-gold);
    background: rgba(216,181,106,.06);
    font-size: 10px;
    font-weight: 700;
}

.empty-state {
    padding: 38px 20px;
    border: 1px dashed var(--aw-border);
    border-radius: 18px;
    color: var(--aw-dim);
    background: rgba(255,255,255,.025);
    text-align: center;
}

.empty-state i {
    margin-bottom: 10px;
    color: var(--aw-muted);
    font-size: 25px;
}

.empty-state p {
    margin: 0;
    font-size: 12px;
}

.branch-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 14px;
    padding: 8px 12px;
    border: 1px solid rgba(216,181,106,.22);
    border-radius: 999px;
    color: var(--aw-gold);
    background: rgba(216,181,106,.07);
    font-size: 11px;
    font-weight: 700;
}

@media (max-width:1200px) {
    .ranking-grid {
        grid-template-columns: repeat(3,minmax(0,1fr));
    }

    .performance-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }
}

@media (max-width:900px) {
    .awards-filter {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .filter-submit {
        width: 100%;
    }

    .ranking-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .awards-hero {
        padding: 24px;
    }
}

@media (max-width:640px) {
    .awards-page {
        padding: 12px;
    }

    .awards-hero {
        padding: 20px;
        flex-direction: column;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .hero-title {
        font-size: 29px;
    }

    .hero-description {
        font-size: 12px;
        line-height: 1.6;
    }

    .hero-icon {
        width: 65px;
        height: 65px;
        font-size: 24px;
    }

    .awards-filter {
        grid-template-columns: 1fr;
        gap: 11px;
        padding: 14px;
        margin-bottom: 18px;
    }

    .filter-submit {
        width: 100%;
    }

    .performance-grid {
        grid-template-columns: repeat(2,minmax(0,1fr));
        gap: 9px;
    }

    .performance-card {
        padding: 15px;
    }

    .performance-value {
        font-size: 16px;
        word-break: break-word;
    }

    .ranking-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .staff-card {
        display: grid;
        grid-template-columns: 58px 1fr auto;
        column-gap: 12px;
        align-items: center;
        padding: 14px;
    }

    .staff-avatar {
        width: 58px;
        height: 58px;
        margin: 0;
    }

    .staff-info {
        min-width: 0;
    }

    .staff-metric {
        grid-column: 2 / 4;
        margin-top: 12px;
    }

    .rank-badge {
        grid-column: 2 / 4;
        justify-self: start;
    }

    .rank-number {
        position: static;
        grid-column: 3;
        grid-row: 1;
        margin-left: auto;
    }

    .section-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 4px;
    }

    .filter-control {
        min-height: 49px;
    }

    .filter-submit {
        min-height: 49px;
    }
}

input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: .85;
    cursor: pointer;
    filter: invert(1);
}

input[type="date"] {
    color-scheme: dark;
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
        darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false');
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>

            <div class="awards-page">

                <!-- HERO -->

                <section class="awards-hero">

                    <div class="hero-content">

                        <div class="hero-eyebrow">
                            <i class="fa-solid fa-microchip"></i>
                            Performance Intelligence
                        </div>

                        <h1 class="hero-title">Staff Awards</h1>

                        <p class="hero-description">
                            A performance-driven recognition system that evaluates staff activity
                            across sales, jobs and combined revenue performance.
                        </p>

                        <div class="branch-indicator">
                            <i class="fa-solid fa-location-dot"></i>
                            Active Branch Performance
                        </div>

                    </div>

                    <div class="hero-icon">
                        <i class="fa-solid fa-ranking-star"></i>
                    </div>

                </section>

                <!-- FILTER -->

                <form method="GET" class="awards-filter" id="awardsFilter">

                    <div class="filter-field">

                        <label class="filter-label" for="start_date">
                            Start Date
                        </label>

                        <i class="fa-regular fa-calendar-days filter-icon"></i>

                        <input
                            type="date"
                            name="start_date"
                            id="start_date"
                            value="<?= awardsEscape($start_date); ?>"
                            class="filter-control"
                            required
                        >

                    </div>

                    <div class="filter-field">

                        <label class="filter-label" for="end_date">
                            End Date
                        </label>

                        <i class="fa-regular fa-calendar-days filter-icon"></i>

                        <input
                            type="date"
                            name="end_date"
                            id="end_date"
                            value="<?= awardsEscape($end_date); ?>"
                            class="filter-control"
                            required
                        >

                    </div>

                    <div class="filter-field">

                        <label class="filter-label" for="sort">
                            Ranking Order
                        </label>

                        <i class="fa-solid fa-arrow-down-wide-short filter-icon"></i>

                        <select name="sort" id="sort" class="filter-control">

                            <option value="high" <?= $sort === 'high' ? 'selected' : ''; ?>>
                                Highest → Lowest
                            </option>

                            <option value="low" <?= $sort === 'low' ? 'selected' : ''; ?>>
                                Lowest → Highest
                            </option>

                        </select>

                    </div>

                    <button type="submit" class="filter-submit">
                        <i class="fa-solid fa-sliders"></i>
                        Apply Filter
                    </button>

                </form>

                <!-- MY PERFORMANCE -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">
                                <i class="fa-solid fa-user-check"></i>
                                My Performance
                            </h2>

                            <div class="section-subtitle">
                                <?= awardsEscape($start_date); ?>
                                →
                                <?= awardsEscape($end_date); ?>
                            </div>

                        </div>

                        <?php if ($myRank): ?>

                            <div class="rank-badge">
                                <i class="fa-solid fa-ranking-star"></i>
                                Branch Top <?= (int)$myRank; ?>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="performance-grid">

                        <div class="performance-card">
                            <div class="performance-icon">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </div>
                            <div class="performance-label">Sales Revenue</div>
                            <div class="performance-value">
                                <?= awardsMoney($myPerformance['sales_total']); ?>
                            </div>
                        </div>

                        <div class="performance-card">
                            <div class="performance-icon">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div class="performance-label">Sales Completed</div>
                            <div class="performance-value">
                                <?= number_format($myPerformance['sales_count']); ?>
                            </div>
                        </div>

                        <div class="performance-card">
                            <div class="performance-icon">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <div class="performance-label">Job Revenue</div>
                            <div class="performance-value">
                                <?= awardsMoney($myPerformance['jobs_total']); ?>
                            </div>
                        </div>

                        <div class="performance-card">
                            <div class="performance-icon">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="performance-label">Overall Score</div>
                            <div class="performance-value">
                                <?= awardsMoney($myPerformance['overall_score']); ?>
                            </div>
                        </div>

                    </div>

                </section>

                <!-- SALES RANKING -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">
                                <i class="fa-solid fa-chart-column"></i>
                                Sales Performance
                            </h2>

                            <div class="section-subtitle">
                                Top 5 staff by sales revenue in this branch
                            </div>

                        </div>

                    </div>

                    <?php if (!empty($topSales)): ?>

                        <div class="ranking-grid">

                            <?php foreach ($topSales as $index => $staff): ?>

                                <?php $rank = $index + 1; ?>

                                <article class="staff-card">

                                    <div class="rank-number <?= $rank <= 3 ? 'top' : ''; ?>">
                                        <?= $rank; ?>
                                    </div>

                                    <div class="staff-avatar">

                                        <img
                                            src="<?= awardsEscape(awardsProfileImage($staff['profile'])); ?>"
                                            alt="<?= awardsEscape($staff['fullname']); ?>"
                                            onerror="this.onerror=null;this.src='../assets/images/user.png';"
                                        >

                                    </div>

                                    <div class="staff-info">

                                        <h3 class="staff-name">
                                            <?= awardsEscape($staff['fullname']); ?>
                                        </h3>

                                        <div class="staff-email">
                                            <?= awardsEscape($staff['email']); ?>
                                        </div>

                                    </div>

                                    <div class="staff-metric">

                                        <div class="metric-label">Sales Revenue</div>

                                        <div class="metric-value">
                                            <?= awardsMoney($staff['total_sales']); ?>
                                        </div>

                                        <div class="metric-count">
                                            <?= number_format((int)$staff['num_sales']); ?>
                                            transaction(s)
                                        </div>

                                    </div>

                                    <?php if ($rank <= 3): ?>

                                        <div class="rank-badge">

                                            <?= awardsMedalIcon($rank); ?>

                                            <?= $rank === 1
                                                ? 'Leading Performer'
                                                : ($rank === 2
                                                    ? 'Elite Performer'
                                                    : 'Outstanding'); ?>

                                        </div>

                                    <?php endif; ?>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            <i class="fa-solid fa-chart-column"></i>
                            <p>No sales performance was recorded for this branch during the selected period.</p>
                        </div>

                    <?php endif; ?>

                </section>

                <!-- JOB RANKING -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                                Job Performance
                            </h2>

                            <div class="section-subtitle">
                                Top 5 staff by job revenue in this branch
                            </div>

                        </div>

                    </div>

                    <?php if (!empty($topJobs)): ?>

                        <div class="ranking-grid">

                            <?php foreach ($topJobs as $index => $staff): ?>

                                <?php $rank = $index + 1; ?>

                                <article class="staff-card">

                                    <div class="rank-number <?= $rank <= 3 ? 'top' : ''; ?>">
                                        <?= $rank; ?>
                                    </div>

                                    <div class="staff-avatar">

                                        <img
                                            src="<?= awardsEscape(awardsProfileImage($staff['profile'])); ?>"
                                            alt="<?= awardsEscape($staff['fullname']); ?>"
                                            onerror="this.onerror=null;this.src='../assets/images/user.png';"
                                        >

                                    </div>

                                    <div class="staff-info">

                                        <h3 class="staff-name">
                                            <?= awardsEscape($staff['fullname']); ?>
                                        </h3>

                                        <div class="staff-email">
                                            <?= awardsEscape($staff['email']); ?>
                                        </div>

                                    </div>

                                    <div class="staff-metric">

                                        <div class="metric-label">Job Revenue</div>

                                        <div class="metric-value">
                                            <?= awardsMoney($staff['total_jobs']); ?>
                                        </div>

                                        <div class="metric-count">
                                            <?= number_format((int)$staff['num_jobs']); ?>
                                            job(s)
                                        </div>

                                    </div>

                                    <?php if ($rank <= 3): ?>

                                        <div class="rank-badge">

                                            <?= awardsMedalIcon($rank); ?>

                                            <?= $rank === 1
                                                ? 'Leading Performer'
                                                : ($rank === 2
                                                    ? 'Elite Performer'
                                                    : 'Outstanding'); ?>

                                        </div>

                                    <?php endif; ?>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            <i class="fa-solid fa-briefcase"></i>
                            <p>No job performance was recorded for this branch during the selected period.</p>
                        </div>

                    <?php endif; ?>

                </section>

                <!-- OVERALL RANKING -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">
                                <i class="fa-solid fa-crown"></i>
                                Overall Performance
                            </h2>

                            <div class="section-subtitle">
                                Combined sales and job performance in this branch
                            </div>

                        </div>

                    </div>

                    <?php if (!empty($topOverall)): ?>

                        <div class="ranking-grid">

                            <?php foreach ($topOverall as $index => $staff): ?>

                                <?php $rank = $index + 1; ?>

                                <article class="staff-card">

                                    <div class="rank-number <?= $rank <= 3 ? 'top' : ''; ?>">
                                        <?= $rank; ?>
                                    </div>

                                    <div class="staff-avatar">

                                        <img
                                            src="<?= awardsEscape(awardsProfileImage($staff['profile'])); ?>"
                                            alt="<?= awardsEscape($staff['fullname']); ?>"
                                            onerror="this.onerror=null;this.src='../assets/images/user.png';"
                                        >

                                    </div>

                                    <div class="staff-info">

                                        <h3 class="staff-name">
                                            <?= awardsEscape($staff['fullname']); ?>
                                        </h3>

                                        <div class="staff-email">
                                            <?= awardsEscape($staff['email']); ?>
                                        </div>

                                    </div>

                                    <div class="staff-metric">

                                        <div class="metric-label">Overall Score</div>

                                        <div class="metric-value">
                                            <?= awardsMoney($staff['overall_score']); ?>
                                        </div>

                                        <div class="metric-count">
                                            Sales: <?= awardsMoney($staff['total_sales']); ?>
                                            <br>
                                            Jobs: <?= awardsMoney($staff['total_jobs']); ?>
                                        </div>

                                    </div>

                                    <?php if ($rank <= 3): ?>

                                        <div class="rank-badge">

                                            <?= awardsMedalIcon($rank); ?>

                                            <?= $rank === 1
                                                ? 'Overall Champion'
                                                : ($rank === 2
                                                    ? 'Elite Performer'
                                                    : 'Outstanding'); ?>

                                        </div>

                                    <?php endif; ?>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            <i class="fa-solid fa-ranking-star"></i>
                            <p>No overall performance data was recorded for this branch during the selected period.</p>
                        </div>

                    <?php endif; ?>

                </section>

            </div>

        </main>

    </div>

</div>

<?php include('footer.php'); ?>

<script defer src="bundle.js"></script>

</body>
</html>