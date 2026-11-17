
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

/*
|--------------------------------------------------------------------------
| SESSION DATA
|--------------------------------------------------------------------------
*/

$company_id  = $_SESSION['company_id'] ?? null;
$location_id = $_SESSION['location_id'] ?? null;
$user_email  = $_SESSION['user_email'] ?? ($_SESSION['email'] ?? null);

if (!$company_id) {
    die('Company session could not be identified.');
}

/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

/*
|--------------------------------------------------------------------------
| SORTING
|--------------------------------------------------------------------------
|
| high = highest performance first
| low  = lowest performance first
|
*/

$sort = $_GET['sort'] ?? 'high';

$orderDirection = ($sort === 'low') ? 'ASC' : 'DESC';

/*
|--------------------------------------------------------------------------
| VALIDATE DATES
|--------------------------------------------------------------------------
*/

$startTimestamp = strtotime($start_date);
$endTimestamp   = strtotime($end_date);

if (!$startTimestamp) {
    $start_date = date('Y-m-01');
}

if (!$endTimestamp) {
    $end_date = date('Y-m-d');
}

if ($start_date > $end_date) {
    $temporary = $start_date;
    $start_date = $end_date;
    $end_date = $temporary;
}

$startDateTime = $start_date . ' 00:00:00';
$endDateTime   = $end_date . ' 23:59:59';

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return '₦' . number_format((float)$value, 2);
}

function medalIcon($rank)
{
    return match ((int)$rank) {
        1 => '<i class="fa-solid fa-trophy"></i>',
        2 => '<i class="fa-solid fa-medal"></i>',
        3 => '<i class="fa-solid fa-award"></i>',
        default => '<i class="fa-solid fa-star"></i>',
    };
}

/*
|--------------------------------------------------------------------------
| COMMON LOCATION FILTER
|--------------------------------------------------------------------------
*/

$locationFilterSales = '';
$locationFilterJobs  = '';

if ($location_id) {
    $locationFilterSales = " AND sa.location_id = :sales_location ";
    $locationFilterJobs  = " AND j.location_id = :jobs_location ";
}

/*
|--------------------------------------------------------------------------
| TOP 5 SALES STAFF
|--------------------------------------------------------------------------
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
        ON sa.user_id = u.id

    INNER JOIN staffs s
        ON LOWER(TRIM(s.email)) = LOWER(TRIM(u.email))

    WHERE sa.company_id = :company_id

    AND sa.created_at BETWEEN :start_date AND :end_date

    $locationFilterSales

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

$salesParams = [
    ':company_id' => $company_id,
    ':start_date' => $startDateTime,
    ':end_date'   => $endDateTime
];

if ($location_id) {
    $salesParams[':sales_location'] = $location_id;
}

$stmt = $pdo->prepare($salesQuery);
$stmt->execute($salesParams);

$topSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| TOP 5 JOB STAFF
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
        ON j.user_id = u.id

    INNER JOIN staffs s
        ON LOWER(TRIM(s.email)) = LOWER(TRIM(u.email))

    WHERE j.company_id = :company_id

    AND j.created_at BETWEEN :start_date AND :end_date

    $locationFilterJobs

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

$jobParams = [
    ':company_id' => $company_id,
    ':start_date' => $startDateTime,
    ':end_date'   => $endDateTime
];

if ($location_id) {
    $jobParams[':jobs_location'] = $location_id;
}

$stmt = $pdo->prepare($jobQuery);
$stmt->execute($jobParams);

$topJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| TOP 5 OVERALL STAFF
|--------------------------------------------------------------------------
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

    LEFT JOIN
    (
        SELECT

            st.id AS staff_id,

            COALESCE(SUM(sa.final_total), 0) AS total_sales,

            COUNT(sa.id) AS num_sales

        FROM sales sa

        INNER JOIN users u
            ON sa.user_id = u.id

        INNER JOIN staffs st
            ON LOWER(TRIM(st.email)) = LOWER(TRIM(u.email))

        WHERE sa.company_id = :company_sales

        AND sa.created_at BETWEEN :sales_start AND :sales_end

        $locationFilterSales

        GROUP BY st.id

    ) sa

        ON s.id = sa.staff_id

    LEFT JOIN
    (
        SELECT

            st.id AS staff_id,

            COALESCE(SUM(j.final_total), 0) AS total_jobs,

            COUNT(j.id) AS num_jobs

        FROM jobs j

        INNER JOIN users u
            ON j.user_id = u.id

        INNER JOIN staffs st
            ON LOWER(TRIM(st.email)) = LOWER(TRIM(u.email))

        WHERE j.company_id = :company_jobs

        AND j.created_at BETWEEN :jobs_start AND :jobs_end

        $locationFilterJobs

        GROUP BY st.id

    ) jb

        ON s.id = jb.staff_id

    WHERE s.company_id = :company_main

    ORDER BY
        overall_score $orderDirection,
        total_sales $orderDirection,
        total_jobs $orderDirection

    LIMIT 5
";

$overallParams = [

    ':company_sales' => $company_id,
    ':sales_start'   => $startDateTime,
    ':sales_end'     => $endDateTime,

    ':company_jobs'  => $company_id,
    ':jobs_start'    => $startDateTime,
    ':jobs_end'      => $endDateTime,

    ':company_main'  => $company_id
];

if ($location_id) {

    $overallParams[':sales_location'] = $location_id;
    $overallParams[':jobs_location']  = $location_id;
}

$stmt = $pdo->prepare($overallQuery);
$stmt->execute($overallParams);

$topOverall = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| MY PERFORMANCE
|--------------------------------------------------------------------------
*/

$myPerformance = [

    'sales_count'   => 0,
    'sales_total'   => 0,

    'jobs_count'    => 0,
    'jobs_total'    => 0,

    'overall_score' => 0

];

if ($user_email) {

    /*
    |--------------------------------------------------------------------------
    | MY SALES
    |--------------------------------------------------------------------------
    */

    $mySalesQuery = "
        SELECT

            COUNT(sa.id) AS sales_count,

            COALESCE(SUM(sa.final_total), 0) AS sales_total

        FROM sales sa

        INNER JOIN users u
            ON sa.user_id = u.id

        WHERE sa.company_id = :company_id

        AND LOWER(TRIM(u.email)) = LOWER(TRIM(:email))

        AND sa.created_at BETWEEN :start_date AND :end_date

        " . ($location_id ? " AND sa.location_id = :location_id " : "") . "
    ";

    $mySalesParams = [

        ':company_id' => $company_id,

        ':email'      => $user_email,

        ':start_date' => $startDateTime,

        ':end_date'   => $endDateTime
    ];

    if ($location_id) {
        $mySalesParams[':location_id'] = $location_id;
    }

    $stmt = $pdo->prepare($mySalesQuery);
    $stmt->execute($mySalesParams);

    $mySales = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | MY JOBS
    |--------------------------------------------------------------------------
    */

    $myJobsQuery = "
        SELECT

            COUNT(j.id) AS jobs_count,

            COALESCE(SUM(j.final_total), 0) AS jobs_total

        FROM jobs j

        INNER JOIN users u
            ON j.user_id = u.id

        WHERE j.company_id = :company_id

        AND LOWER(TRIM(u.email)) = LOWER(TRIM(:email))

        AND j.created_at BETWEEN :start_date AND :end_date

        " . ($location_id ? " AND j.location_id = :location_id " : "") . "
    ";

    $myJobsParams = [

        ':company_id' => $company_id,

        ':email'      => $user_email,

        ':start_date' => $startDateTime,

        ':end_date'   => $endDateTime
    ];

    if ($location_id) {
        $myJobsParams[':location_id'] = $location_id;
    }

    $stmt = $pdo->prepare($myJobsQuery);
    $stmt->execute($myJobsParams);

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
        $myPerformance['sales_total']
        +
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
        $user_email &&
        strtolower(trim($staff['email'])) === strtolower(trim($user_email))
    ) {
        $myRank = $index + 1;
        break;
    }
}

/*
|--------------------------------------------------------------------------
| COMPANY NAME FALLBACK
|--------------------------------------------------------------------------
*/

$displayCompanyName = $company_name ?? 'Company';

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
    name="theme-color"
    content="#080b12"
>

<title>
    <?= e($displayCompanyName); ?> | Staff Awards
</title>

<!-- Font Awesome -->
<link
    rel="stylesheet"
    href="../fontawesome/css/all.css"
>

<!-- Existing Styles -->
<link
    rel="stylesheet"
    href="style.css"
>

<link
    rel="stylesheet"
    href="awards.css"
>

<style>

/* =========================================================
   STAFF AWARDS — FUTURISTIC GLASS SYSTEM
   ========================================================= */

:root {

    --aw-bg: #070a10;

    --aw-surface: rgba(255,255,255,0.055);

    --aw-surface-strong: rgba(255,255,255,0.085);

    --aw-border: rgba(255,255,255,0.12);

    --aw-border-soft: rgba(255,255,255,0.075);

    --aw-text: #f5f7fa;

    --aw-muted: #9ca3af;

    --aw-dim: #6b7280;

    --aw-gold: #d8b56a;

    --aw-gold-soft: rgba(216,181,106,0.15);

    --aw-shadow:
        0 20px 70px rgba(0,0,0,0.35);

    --aw-radius: 22px;
}


/* =========================================================
   PAGE
   ========================================================= */

.awards-page {

    position: relative;

    min-height: calc(100vh - 80px);

    padding: 20px;

    color: var(--aw-text);

    overflow: hidden;
}


/* =========================================================
   ABSTRACT ALGORITHM BACKGROUND
   ========================================================= */

.awards-page::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

    opacity: .28;

    background-image:

        linear-gradient(
            rgba(255,255,255,.025) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(255,255,255,.025) 1px,
            transparent 1px
        );

    background-size: 48px 48px;

    mask-image:
        radial-gradient(
            ellipse at center,
            black 10%,
            transparent 80%
        );
}


/* =========================================================
   HERO
   ========================================================= */

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

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.075),
            rgba(255,255,255,.025)
        );

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

    box-shadow:
        0 0 80px rgba(216,181,106,.08);
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

    box-shadow:
        inset 0 0 30px rgba(255,255,255,.025),
        0 0 50px rgba(216,181,106,.08);
}


/* =========================================================
   FILTER BAR
   ========================================================= */

.awards-filter {

    position: relative;

    z-index: 5;

    display: grid;

    grid-template-columns:
        minmax(180px, 1fr)
        minmax(180px, 1fr)
        minmax(180px, .8fr)
        auto;

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

    box-shadow:
        0 0 0 3px rgba(216,181,106,.08);
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

    box-shadow:
        0 10px 30px rgba(216,181,106,.15);
}


/* =========================================================
   PERFORMANCE SUMMARY
   ========================================================= */

.performance-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

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


/* =========================================================
   SECTION
   ========================================================= */

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


/* =========================================================
   RANKING GRID
   ========================================================= */

.ranking-grid {

    display: grid;

    grid-template-columns:
        repeat(5, minmax(0, 1fr));

    gap: 13px;
}

.staff-card {

    position: relative;

    min-width: 0;

    padding: 18px;

    border: 1px solid var(--aw-border-soft);

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.07),
            rgba(255,255,255,.025)
        );

    backdrop-filter: blur(18px);

    -webkit-backdrop-filter: blur(18px);

    box-shadow:
        0 15px 40px rgba(0,0,0,.18);

    transition:
        transform .2s ease,
        border-color .2s ease,
        background .2s ease;
}

.staff-card:hover {

    transform: translateY(-4px);

    border-color:
        rgba(216,181,106,.28);

    background:
        rgba(255,255,255,.075);
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

    border-color:
        rgba(216,181,106,.4);

    background:
        rgba(216,181,106,.08);
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


/* =========================================================
   EMPTY STATE
   ========================================================= */

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


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1200px) {

    .ranking-grid {

        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }

    .performance-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}


@media (max-width: 900px) {

    .awards-filter {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .filter-submit {

        width: 100%;
    }

    .ranking-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .awards-hero {

        padding: 24px;
    }
}


@media (max-width: 640px) {

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

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

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

}


/* =========================================================
   DATE INPUT
   ========================================================= */

input[type="date"]::-webkit-calendar-picker-indicator {

    opacity: .85;

    cursor: pointer;

    filter: invert(1);
}

input[type="date"] {

    color-scheme: dark;
}


/* =========================================================
   LIGHTER MOBILE TOUCH TARGETS
   ========================================================= */

@media (max-width: 480px) {

    .filter-control {

        min-height: 49px;
    }

    .filter-submit {

        min-height: 49px;
    }

    .performance-grid {

        grid-template-columns: 1fr 1fr;
    }
}

</style>

</head>


<body
    x-data="{
        page: 'ecommerce',
        'loaded': true,
        'darkMode': false,
        'stickyMenu': false,
        'sidebarToggle': false,
        'scrollTop': false
    }"

    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode'));
        $watch(
            'darkMode',
            value => localStorage.setItem(
                'darkMode',
                JSON.stringify(value)
            )
        )
    "

    :class="{'dark bg-gray-900': darkMode === true}"
>


<!-- =========================================================
     PAGE WRAPPER
     ========================================================= -->

<div class="flex h-screen overflow-hidden">

    <?php include('sidebar.php'); ?>


    <!-- CONTENT AREA -->

    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

        <main>

            <?php include('header.php'); ?>


            <div class="awards-page">


                <!-- =================================================
                     HERO
                     ================================================= -->

                <section class="awards-hero">

                    <div class="hero-content">

                        <div class="hero-eyebrow">

                            <i class="fa-solid fa-microchip"></i>

                            Performance Intelligence

                        </div>


                        <h1 class="hero-title">

                            Staff Awards

                        </h1>


                        <p class="hero-description">

                            A performance-driven recognition system
                            that evaluates staff activity across sales,
                            jobs and combined revenue performance.

                        </p>

                    </div>


                    <div class="hero-icon">

                        <i class="fa-solid fa-ranking-star"></i>

                    </div>

                </section>



                <!-- =================================================
                     FILTER
                     ================================================= -->

                <form
                    method="GET"
                    class="awards-filter"
                    id="awardsFilter"
                >


                    <!-- START DATE -->

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="start_date"
                        >

                            Start Date

                        </label>


                        <i
                            class="fa-regular fa-calendar-days filter-icon"
                        ></i>


                        <input
                            type="date"
                            name="start_date"
                            id="start_date"
                            value="<?= e($start_date); ?>"
                            class="filter-control"
                            required
                        >

                    </div>


                    <!-- END DATE -->

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="end_date"
                        >

                            End Date

                        </label>


                        <i
                            class="fa-regular fa-calendar-days filter-icon"
                        ></i>


                        <input
                            type="date"
                            name="end_date"
                            id="end_date"
                            value="<?= e($end_date); ?>"
                            class="filter-control"
                            required
                        >

                    </div>


                    <!-- SORT -->

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="sort"
                        >

                            Ranking Order

                        </label>


                        <i
                            class="fa-solid fa-arrow-down-wide-short filter-icon"
                        ></i>


                        <select
                            name="sort"
                            id="sort"
                            class="filter-control"
                        >

                            <option
                                value="high"
                                <?= $sort === 'high' ? 'selected' : ''; ?>
                            >
                                Highest → Lowest
                            </option>

                            <option
                                value="low"
                                <?= $sort === 'low' ? 'selected' : ''; ?>
                            >
                                Lowest → Highest
                            </option>

                        </select>

                    </div>


                    <!-- BUTTON -->

                    <button
                        type="submit"
                        class="filter-submit"
                    >

                        <i class="fa-solid fa-sliders"></i>

                        Apply Filter

                    </button>

                </form>



                <!-- =================================================
                     MY PERFORMANCE
                     ================================================= -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">

                                <i class="fa-solid fa-user-check"></i>

                                My Performance

                            </h2>

                            <div class="section-subtitle">

                                <?= e($start_date); ?>
                                →
                                <?= e($end_date); ?>

                            </div>

                        </div>


                        <?php if ($myRank): ?>

                            <div class="rank-badge">

                                <i class="fa-solid fa-ranking-star"></i>

                                Top <?= $myRank; ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="performance-grid">


                        <!-- SALES -->

                        <div class="performance-card">

                            <div class="performance-icon">

                                <i class="fa-solid fa-cart-shopping"></i>

                            </div>

                            <div class="performance-label">

                                Sales Revenue

                            </div>

                            <div class="performance-value">

                                <?= money($myPerformance['sales_total']); ?>

                            </div>

                        </div>


                        <!-- SALES COUNT -->

                        <div class="performance-card">

                            <div class="performance-icon">

                                <i class="fa-solid fa-receipt"></i>

                            </div>

                            <div class="performance-label">

                                Sales Completed

                            </div>

                            <div class="performance-value">

                                <?= number_format($myPerformance['sales_count']); ?>

                            </div>

                        </div>


                        <!-- JOBS -->

                        <div class="performance-card">

                            <div class="performance-icon">

                                <i class="fa-solid fa-briefcase"></i>

                            </div>

                            <div class="performance-label">

                                Job Revenue

                            </div>

                            <div class="performance-value">

                                <?= money($myPerformance['jobs_total']); ?>

                            </div>

                        </div>


                        <!-- OVERALL -->

                        <div class="performance-card">

                            <div class="performance-icon">

                                <i class="fa-solid fa-chart-line"></i>

                            </div>

                            <div class="performance-label">

                                Overall Score

                            </div>

                            <div class="performance-value">

                                <?= money($myPerformance['overall_score']); ?>

                            </div>

                        </div>


                    </div>

                </section>



                <!-- =================================================
                     SALES RANKING
                     ================================================= -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">

                                <i class="fa-solid fa-chart-column"></i>

                                Sales Performance

                            </h2>

                            <div class="section-subtitle">

                                Top 5 staff by sales revenue

                            </div>

                        </div>

                    </div>


                    <?php if (!empty($topSales)): ?>

                        <div class="ranking-grid">

                            <?php foreach ($topSales as $index => $staff): ?>

                                <?php

                                $rank = $index + 1;

                                $profile = !empty($staff['profile'])
                                    ? '../' . ltrim($staff['profile'], '/')
                                    : '../assets/images/user.png';

                                ?>

                                <article class="staff-card">


                                    <div
                                        class="rank-number <?= $rank <= 3 ? 'top' : ''; ?>"
                                    >

                                        <?= $rank; ?>

                                    </div>


                                    <div class="staff-avatar">

                                        <img
                                            src="<?= e($profile); ?>"
                                            alt="<?= e($staff['fullname']); ?>"
                                            onerror="this.style.display='none';"
                                        >

                                    </div>


                                    <div class="staff-info">

                                        <h3 class="staff-name">

                                            <?= e($staff['fullname']); ?>

                                        </h3>


                                        <div class="staff-email">

                                            <?= e($staff['email']); ?>

                                        </div>

                                    </div>


                                    <div class="staff-metric">

                                        <div class="metric-label">

                                            Sales Revenue

                                        </div>

                                        <div class="metric-value">

                                            <?= money($staff['total_sales']); ?>

                                        </div>

                                        <div class="metric-count">

                                            <?= number_format($staff['num_sales']); ?>
                                            transaction(s)

                                        </div>

                                    </div>


                                    <?php if ($rank <= 3): ?>

                                        <div class="rank-badge">

                                            <?= medalIcon($rank); ?>

                                            <?= $rank == 1
                                                ? 'Leading Performer'
                                                : ($rank == 2
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

                            <p>
                                No sales performance was recorded
                                for the selected period.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>



                <!-- =================================================
                     JOB RANKING
                     ================================================= -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">

                                <i class="fa-solid fa-screwdriver-wrench"></i>

                                Job Performance

                            </h2>

                            <div class="section-subtitle">

                                Top 5 staff by job revenue

                            </div>

                        </div>

                    </div>


                    <?php if (!empty($topJobs)): ?>

                        <div class="ranking-grid">

                            <?php foreach ($topJobs as $index => $staff): ?>

                                <?php

                                $rank = $index + 1;

                                $profile = !empty($staff['profile'])
                                    ? '../' . ltrim($staff['profile'], '/')
                                    : '../assets/images/user.png';

                                ?>

                                <article class="staff-card">


                                    <div
                                        class="rank-number <?= $rank <= 3 ? 'top' : ''; ?>"
                                    >

                                        <?= $rank; ?>

                                    </div>


                                    <div class="staff-avatar">

                                        <img
                                            src="<?= e($profile); ?>"
                                            alt="<?= e($staff['fullname']); ?>"
                                            onerror="this.style.display='none';"
                                        >

                                    </div>


                                    <div class="staff-info">

                                        <h3 class="staff-name">

                                            <?= e($staff['fullname']); ?>

                                        </h3>


                                        <div class="staff-email">

                                            <?= e($staff['email']); ?>

                                        </div>

                                    </div>


                                    <div class="staff-metric">

                                        <div class="metric-label">

                                            Job Revenue

                                        </div>

                                        <div class="metric-value">

                                            <?= money($staff['total_jobs']); ?>

                                        </div>

                                        <div class="metric-count">

                                            <?= number_format($staff['num_jobs']); ?>
                                            job(s)

                                        </div>

                                    </div>


                                    <?php if ($rank <= 3): ?>

                                        <div class="rank-badge">

                                            <?= medalIcon($rank); ?>

                                            <?= $rank == 1
                                                ? 'Leading Performer'
                                                : ($rank == 2
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

                            <p>
                                No job performance was recorded
                                for the selected period.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>



                <!-- =================================================
                     OVERALL RANKING
                     ================================================= -->

                <section class="awards-section">

                    <div class="section-heading">

                        <div>

                            <h2 class="section-title">

                                <i class="fa-solid fa-crown"></i>

                                Overall Performance

                            </h2>

                            <div class="section-subtitle">

                                Combined sales + job performance

                            </div>

                        </div>

                    </div>


                    <?php if (!empty($topOverall)): ?>

                        <div class="ranking-grid">

                            <?php foreach ($topOverall as $index => $staff): ?>

                                <?php

                                $rank = $index + 1;

                                $profile = !empty($staff['profile'])
                                    ? '../' . ltrim($staff['profile'], '/')
                                    : '../assets/images/user.png';

                                ?>

                                <article class="staff-card">


                                    <div
                                        class="rank-number <?= $rank <= 3 ? 'top' : ''; ?>"
                                    >

                                        <?= $rank; ?>

                                    </div>


                                    <div class="staff-avatar">

                                        <img
                                            src="<?= e($profile); ?>"
                                            alt="<?= e($staff['fullname']); ?>"
                                            onerror="this.style.display='none';"
                                        >

                                    </div>


                                    <div class="staff-info">

                                        <h3 class="staff-name">

                                            <?= e($staff['fullname']); ?>

                                        </h3>


                                        <div class="staff-email">

                                            <?= e($staff['email']); ?>

                                        </div>

                                    </div>


                                    <div class="staff-metric">

                                        <div class="metric-label">

                                            Overall Score

                                        </div>

                                        <div class="metric-value">

                                            <?= money($staff['overall_score']); ?>

                                        </div>

                                        <div class="metric-count">

                                            Sales:
                                            <?= money($staff['total_sales']); ?>

                                            <br>

                                            Jobs:
                                            <?= money($staff['total_jobs']); ?>

                                        </div>

                                    </div>


                                    <?php if ($rank <= 3): ?>

                                        <div class="rank-badge">

                                            <?= medalIcon($rank); ?>

                                            <?= $rank == 1
                                                ? 'Overall Champion'
                                                : ($rank == 2
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

                            <p>
                                No overall performance data was
                                recorded for the selected period.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>


            </div>

        </main>

    </div>

</div>


<!-- FOOTER -->

<?php include('footer.php'); ?>


<!-- EXISTING JAVASCRIPT — NOT MODIFIED -->

<script
    defer
    src="bundle.js"
></script>


</body>

</html>
