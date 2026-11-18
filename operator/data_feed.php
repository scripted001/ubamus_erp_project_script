<?php

// ============================================================================
// OPERATOR / JOB OPERATIONS MANAGER — OPERATIONAL INTELLIGENCE
// ============================================================================
//
// DATA ACCESS RULE:
//
// PERSONAL OPERATOR ACTIVITY:
//     company_id + location_id + user_id
//
// This ensures the operator sees ONLY:
//     • Jobs assigned/created by him
//     • At his assigned location
//     • Within his company
//
// ============================================================================


$operatorUserId = $_SESSION['user_id'] ?? null;

$operatorCompanyId  = 0;
$operatorLocationId = 0;
$operatorName       = 'Operator';


// ============================================================================
// KPI DEFAULTS
// ============================================================================

$todayJobs          = 0;
$todayRevenue       = 0;
$todayCollected     = 0;
$todayOutstanding   = 0;

$weekJobs           = 0;
$weekRevenue        = 0;
$weekCollected      = 0;

$monthJobs          = 0;
$monthRevenue       = 0;
$monthCollected     = 0;
$monthOutstanding   = 0;

$yearJobs           = 0;
$yearRevenue        = 0;
$yearCollected      = 0;

$activeJobs         = 0;
$completedJobs      = 0;
$pendingJobs        = 0;
$overdueJobs        = 0;

$totalOutstanding   = 0;
$averageJobValue    = 0;
$collectionRate     = 0;
$completionRate     = 0;


// ============================================================================
// VERIFY CURRENT USER
// ============================================================================

if ($operatorUserId) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            username,
            role,
            company_id,
            location_id
        FROM users
        WHERE id = ?
          AND status = 'active'
        LIMIT 1
    ");

    $stmt->execute([$operatorUserId]);

    $operator = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($operator) {

        $operatorCompanyId =
            (int)($operator['company_id'] ?? 0);

        $operatorLocationId =
            (int)($operator['location_id'] ?? 0);

        $operatorName =
            $operator['username'] ?: 'Operator';


        // ====================================================================
        // STRICT PERSONAL + LOCATION JOB INTELLIGENCE
        // ====================================================================

        if (
            $operatorCompanyId > 0 &&
            $operatorLocationId > 0
        ) {

            $stmt = $pdo->prepare("
                SELECT

                    /* ========================================================
                       TODAY
                       ======================================================== */

                    COALESCE(SUM(
                        CASE
                            WHEN DATE(created_at) = CURDATE()
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS today_jobs,

                    COALESCE(SUM(
                        CASE
                            WHEN DATE(created_at) = CURDATE()
                            THEN final_total
                            ELSE 0
                        END
                    ), 0) AS today_revenue,

                    COALESCE(SUM(
                        CASE
                            WHEN DATE(created_at) = CURDATE()
                            THEN amount_paid
                            ELSE 0
                        END
                    ), 0) AS today_collected,

                    COALESCE(SUM(
                        CASE
                            WHEN DATE(created_at) = CURDATE()
                            THEN balance
                            ELSE 0
                        END
                    ), 0) AS today_outstanding,


                    /* ========================================================
                       THIS WEEK
                       ======================================================== */

                    COALESCE(SUM(
                        CASE
                            WHEN YEARWEEK(created_at, 1)
                               = YEARWEEK(CURDATE(), 1)
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS week_jobs,

                    COALESCE(SUM(
                        CASE
                            WHEN YEARWEEK(created_at, 1)
                               = YEARWEEK(CURDATE(), 1)
                            THEN final_total
                            ELSE 0
                        END
                    ), 0) AS week_revenue,

                    COALESCE(SUM(
                        CASE
                            WHEN YEARWEEK(created_at, 1)
                               = YEARWEEK(CURDATE(), 1)
                            THEN amount_paid
                            ELSE 0
                        END
                    ), 0) AS week_collected,


                    /* ========================================================
                       THIS MONTH
                       ======================================================== */

                    COALESCE(SUM(
                        CASE
                            WHEN MONTH(created_at) = MONTH(CURDATE())
                             AND YEAR(created_at) = YEAR(CURDATE())
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS month_jobs,

                    COALESCE(SUM(
                        CASE
                            WHEN MONTH(created_at) = MONTH(CURDATE())
                             AND YEAR(created_at) = YEAR(CURDATE())
                            THEN final_total
                            ELSE 0
                        END
                    ), 0) AS month_revenue,

                    COALESCE(SUM(
                        CASE
                            WHEN MONTH(created_at) = MONTH(CURDATE())
                             AND YEAR(created_at) = YEAR(CURDATE())
                            THEN amount_paid
                            ELSE 0
                        END
                    ), 0) AS month_collected,

                    COALESCE(SUM(
                        CASE
                            WHEN MONTH(created_at) = MONTH(CURDATE())
                             AND YEAR(created_at) = YEAR(CURDATE())
                            THEN balance
                            ELSE 0
                        END
                    ), 0) AS month_outstanding,


                    /* ========================================================
                       YEAR TO DATE
                       ======================================================== */

                    COALESCE(SUM(
                        CASE
                            WHEN YEAR(created_at) = YEAR(CURDATE())
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS year_jobs,

                    COALESCE(SUM(
                        CASE
                            WHEN YEAR(created_at) = YEAR(CURDATE())
                            THEN final_total
                            ELSE 0
                        END
                    ), 0) AS year_revenue,

                    COALESCE(SUM(
                        CASE
                            WHEN YEAR(created_at) = YEAR(CURDATE())
                            THEN amount_paid
                            ELSE 0
                        END
                    ), 0) AS year_collected,


                    /* ========================================================
                       OPERATIONAL STATUS
                       ======================================================== */

                    COALESCE(SUM(
                        CASE
                            WHEN LOWER(payment_status) IN
                                 ('pending', 'processing', 'in progress')
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS pending_jobs,

                    COALESCE(SUM(
                        CASE
                            WHEN LOWER(payment_status) = 'paid'
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS completed_jobs,

                    COALESCE(SUM(
                        CASE
                            WHEN balance > 0
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS outstanding_jobs,

                    COALESCE(SUM(
                        CASE
                            WHEN balance > 0
                            THEN balance
                            ELSE 0
                        END
                    ), 0) AS total_outstanding,


                    /* ========================================================
                       OVERDUE JOBS
                       ======================================================== */

                    COALESCE(SUM(
                        CASE
                            WHEN balance > 0
                             AND due_date IS NOT NULL
                             AND due_date <> ''
                             AND DATE(due_date) < CURDATE()
                            THEN 1
                            ELSE 0
                        END
                    ), 0) AS overdue_jobs

                FROM jobs

                WHERE company_id = ?
                  AND location_id = ?
                  AND user_id = ?
            ");


            $stmt->execute([
                $operatorCompanyId,
                $operatorLocationId,
                $operatorUserId
            ]);


            $data = $stmt->fetch(PDO::FETCH_ASSOC);


            // =================================================================
            // NORMALIZE DATA
            // =================================================================

            $todayJobs =
                (int)($data['today_jobs'] ?? 0);

            $todayRevenue =
                (float)($data['today_revenue'] ?? 0);

            $todayCollected =
                (float)($data['today_collected'] ?? 0);

            $todayOutstanding =
                (float)($data['today_outstanding'] ?? 0);


            $weekJobs =
                (int)($data['week_jobs'] ?? 0);

            $weekRevenue =
                (float)($data['week_revenue'] ?? 0);

            $weekCollected =
                (float)($data['week_collected'] ?? 0);


            $monthJobs =
                (int)($data['month_jobs'] ?? 0);

            $monthRevenue =
                (float)($data['month_revenue'] ?? 0);

            $monthCollected =
                (float)($data['month_collected'] ?? 0);

            $monthOutstanding =
                (float)($data['month_outstanding'] ?? 0);


            $yearJobs =
                (int)($data['year_jobs'] ?? 0);

            $yearRevenue =
                (float)($data['year_revenue'] ?? 0);

            $yearCollected =
                (float)($data['year_collected'] ?? 0);


            $pendingJobs =
                (int)($data['pending_jobs'] ?? 0);

            $completedJobs =
                (int)($data['completed_jobs'] ?? 0);

            $overdueJobs =
                (int)($data['overdue_jobs'] ?? 0);

            $totalOutstanding =
                (float)($data['total_outstanding'] ?? 0);


            // =================================================================
            // INTELLIGENCE CALCULATIONS
            // =================================================================

            if ($monthJobs > 0) {

                $averageJobValue =
                    $monthRevenue / $monthJobs;
            }


            if ($monthRevenue > 0) {

                $collectionRate =
                    ($monthCollected / $monthRevenue) * 100;
            }


            $collectionRate =
                min(100, max(0, $collectionRate));


            $totalMonthJobs =
                $monthJobs;


            if ($totalMonthJobs > 0) {

                $completionRate =
                    ($completedJobs / $totalMonthJobs) * 100;
            }


            $completionRate =
                min(100, max(0, $completionRate));
        }
    }
}


// ============================================================================
// FORMAT HELPERS
// ============================================================================

function operator_money($amount)
{
    return '₦' . number_format(
        (float)$amount,
        2
    );
}


function operator_number($number)
{
    return number_format(
        (int)$number
    );
}


function operator_percent($number)
{
    return number_format(
        (float)$number,
        1
    ) . '%';
}


function operator_safe($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<style>

/* ========================================================================
   OPERATOR OPERATIONS INTELLIGENCE
======================================================================== */

.operator-intelligence {
    width: 100%;
    margin-top: 4px;
}


/* ========================================================================
   HEADER
======================================================================== */

.operator-intelligence-header {

    display: flex;

    align-items: flex-end;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 18px;
}


.operator-intelligence-heading {

    display: flex;

    align-items: center;

    gap: 13px;
}


.operator-intelligence-heading-icon {

    width: 44px;
    height: 44px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 14px;

    color: #fff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    box-shadow:
        0 10px 25px rgba(37,99,235,.22);
}


.operator-intelligence-heading h3 {

    margin: 0;

    color: #111827;

    font-size: 16px;

    font-weight: 800;

    letter-spacing: -.02em;
}


.operator-intelligence-heading p {

    margin: 4px 0 0;

    color: #64748b;

    font-size: 11px;
}


/* ========================================================================
   OPERATOR SCOPE BADGE
======================================================================== */

.operator-scope {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 7px 11px;

    border-radius: 999px;

    color: #2563eb;

    background: rgba(37,99,235,.06);

    border: 1px solid rgba(37,99,235,.14);

    font-size: 10px;

    font-weight: 800;

    letter-spacing: .05em;

    text-transform: uppercase;
}


.operator-scope-dot {

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #2563eb;

    box-shadow:
        0 0 0 4px rgba(37,99,235,.10);
}


/* ========================================================================
   GRID
======================================================================== */

.operator-kpi-grid {

    display: grid;

    grid-template-columns:
        repeat(12, minmax(0, 1fr));

    gap: 15px;
}


/* ========================================================================
   CARD
======================================================================== */

.operator-kpi-card {

    position: relative;

    grid-column: span 4;

    min-width: 0;

    overflow: hidden;

    padding: 19px;

    border:
        1px solid rgba(148,163,184,.18);

    border-radius: 21px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.94),
            rgba(248,250,252,.78)
        );

    box-shadow:
        0 13px 35px rgba(15,23,42,.055);

    backdrop-filter: blur(18px);

    -webkit-backdrop-filter: blur(18px);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;
}


.operator-kpi-card:hover {

    transform: translateY(-4px);

    border-color:
        rgba(37,99,235,.25);

    box-shadow:
        0 22px 48px rgba(15,23,42,.10);
}


/* ========================================================================
   FEATURED
======================================================================== */

.operator-kpi-featured {

    grid-column: span 6;

    background:
        linear-gradient(
            135deg,
            rgba(239,246,255,.98),
            rgba(255,255,255,.90)
        );

    border-color:
        rgba(37,99,235,.16);
}


/* ========================================================================
   DECORATIVE GLOW
======================================================================== */

.operator-kpi-card::after {

    content: "";

    position: absolute;

    width: 110px;
    height: 110px;

    right: -58px;
    top: -58px;

    border-radius: 50%;

    background:
        rgba(37,99,235,.055);

    pointer-events: none;
}


/* ========================================================================
   TOP
======================================================================== */

.operator-kpi-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 12px;
}


/* ========================================================================
   ICON
======================================================================== */

.operator-kpi-icon {

    width: 43px;
    height: 43px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 13px;

    font-size: 15px;
}


.operator-kpi-icon.blue {

    color: #2563eb;

    background:
        rgba(37,99,235,.09);
}


.operator-kpi-icon.green {

    color: #059669;

    background:
        rgba(5,150,105,.09);
}


.operator-kpi-icon.purple {

    color: #7c3aed;

    background:
        rgba(124,58,237,.09);
}


.operator-kpi-icon.cyan {

    color: #0891b2;

    background:
        rgba(8,145,178,.09);
}


.operator-kpi-icon.orange {

    color: #d97706;

    background:
        rgba(217,119,6,.09);
}


.operator-kpi-icon.red {

    color: #dc2626;

    background:
        rgba(220,38,38,.09);
}


/* ========================================================================
   PERIOD
======================================================================== */

.operator-kpi-period {

    color: #94a3b8;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: .08em;

    text-transform: uppercase;
}


/* ========================================================================
   LABEL
======================================================================== */

.operator-kpi-label {

    display: block;

    margin-top: 17px;

    color: #64748b;

    font-size: 11px;

    font-weight: 650;
}


/* ========================================================================
   VALUE
======================================================================== */

.operator-kpi-value {

    display: block;

    margin-top: 5px;

    color: #111827;

    font-size: 22px;

    line-height: 1.15;

    font-weight: 850;

    letter-spacing: -.035em;

    word-break: break-word;
}


.operator-kpi-featured .operator-kpi-value {

    font-size: 31px;
}


/* ========================================================================
   META
======================================================================== */

.operator-kpi-meta {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-top: 12px;

    color: #94a3b8;

    font-size: 9px;

    font-weight: 600;
}


/* ========================================================================
   FOOTER
======================================================================== */

.operator-kpi-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;

    margin-top: 15px;

    padding-top: 11px;

    border-top:
        1px solid rgba(148,163,184,.13);
}


.operator-kpi-tag {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 7px;

    border-radius: 7px;

    background:
        rgba(37,99,235,.07);

    color: #2563eb;

    font-size: 9px;

    font-weight: 800;
}


/* ========================================================================
   PROGRESS
======================================================================== */

.operator-progress {

    width: 100%;

    height: 6px;

    overflow: hidden;

    margin-top: 14px;

    border-radius: 20px;

    background:
        rgba(226,232,240,.75);
}


.operator-progress-bar {

    height: 100%;

    border-radius: inherit;

    background:
        linear-gradient(
            90deg,
            #2563eb,
            #4f46e5,
            #7c3aed
        );
}


/* ========================================================================
   ATTENTION CARD
======================================================================== */

.operator-kpi-card.attention {

    border-color:
        rgba(239,68,68,.16);

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.96),
            rgba(254,242,242,.78)
        );
}


.operator-kpi-card.attention::after {

    background:
        rgba(239,68,68,.055);
}


/* ========================================================================
   DARK MODE
======================================================================== */

.dark .operator-intelligence-heading h3 {

    color: #f8fafc;
}


.dark .operator-intelligence-heading p {

    color: #94a3b8;
}


.dark .operator-kpi-card {

    border-color:
        rgba(148,163,184,.13);

    background:
        linear-gradient(
            145deg,
            rgba(30,41,59,.84),
            rgba(15,23,42,.74)
        );

    box-shadow:
        0 18px 45px rgba(0,0,0,.20);
}


.dark .operator-kpi-card:hover {

    border-color:
        rgba(96,165,250,.28);
}


.dark .operator-kpi-featured {

    background:
        linear-gradient(
            135deg,
            rgba(30,58,138,.30),
            rgba(15,23,42,.84)
        );
}


.dark .operator-kpi-label {

    color: #94a3b8;
}


.dark .operator-kpi-value {

    color: #f8fafc;
}


.dark .operator-kpi-footer {

    border-color:
        rgba(148,163,184,.10);
}


.dark .operator-progress {

    background:
        rgba(51,65,85,.65);
}


/* ========================================================================
   RESPONSIVE
======================================================================== */

@media (max-width: 1100px) {

    .operator-kpi-card {

        grid-column: span 6;
    }

    .operator-kpi-featured {

        grid-column: span 6;
    }
}


@media (max-width: 650px) {

    .operator-intelligence-header {

        align-items: flex-start;

        flex-direction: column;
    }

    .operator-kpi-grid {

        grid-template-columns: 1fr;
    }

    .operator-kpi-card,
    .operator-kpi-featured {

        grid-column: span 1;
    }

    .operator-kpi-featured .operator-kpi-value {

        font-size: 27px;
    }
}

</style>


<!-- ========================================================================
     OPERATOR OPERATIONS INTELLIGENCE
======================================================================== -->

<div class="operator-intelligence">


    <!-- ====================================================================
         HEADER
    ===================================================================== -->

    <div class="operator-intelligence-header">

        <div class="operator-intelligence-heading">

            <div class="operator-intelligence-heading-icon">

                <i class="fa-solid fa-gears"></i>

            </div>

            <div>

                <h3>
                    Job Operations Intelligence
                </h3>

                <p>
                    Personal operational performance and job activity
                </p>

            </div>

        </div>


        <div class="operator-scope">

            <span class="operator-scope-dot"></span>

            My Activity • My Location

        </div>

    </div>



    <!-- ====================================================================
         KPI GRID
    ===================================================================== -->

    <div class="operator-kpi-grid">


        <!-- ================================================================
             TODAY JOBS
        ================================================================= -->

        <div class="operator-kpi-card">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon blue">

                    <i class="fa-solid fa-briefcase"></i>

                </div>

                <span class="operator-kpi-period">
                    Today
                </span>

            </div>


            <span class="operator-kpi-label">
                Jobs Handled Today
            </span>

            <strong class="operator-kpi-value">

                <?= operator_number($todayJobs); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-user-gear"></i>

                Jobs under your operation

            </div>


            <div class="operator-kpi-footer">

                <span class="operator-kpi-meta">

                    <i class="fa-regular fa-clock"></i>

                    Daily activity

                </span>

            </div>

        </div>



        <!-- ================================================================
             TODAY JOB VALUE
        ================================================================= -->

        <div class="operator-kpi-card operator-kpi-featured">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon green">

                    <i class="fa-solid fa-naira-sign"></i>

                </div>

                <span class="operator-kpi-period">
                    Today
                </span>

            </div>


            <span class="operator-kpi-label">
                Today's Job Value
            </span>

            <strong class="operator-kpi-value">

                <?= operator_money($todayRevenue); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-chart-line"></i>

                Total value of jobs you handled today

            </div>


            <div class="operator-kpi-footer">

                <span class="operator-kpi-meta">

                    <i class="fa-solid fa-money-bill"></i>

                    Collected

                </span>

                <span class="operator-kpi-tag">

                    <?= operator_money($todayCollected); ?>

                </span>

            </div>

        </div>



        <!-- ================================================================
             TODAY OUTSTANDING
        ================================================================= -->

        <div class="operator-kpi-card attention">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon red">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>

                <span class="operator-kpi-period">
                    Attention
                </span>

            </div>


            <span class="operator-kpi-label">
                Today's Outstanding
            </span>

            <strong class="operator-kpi-value">

                <?= operator_money($todayOutstanding); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-clock"></i>

                Uncollected from today's jobs

            </div>

        </div>



        <!-- ================================================================
             WEEKLY
        ================================================================= -->

        <div class="operator-kpi-card">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon purple">

                    <i class="fa-solid fa-calendar-week"></i>

                </div>

                <span class="operator-kpi-period">
                    This Week
                </span>

            </div>


            <span class="operator-kpi-label">
                Weekly Job Value
            </span>

            <strong class="operator-kpi-value">

                <?= operator_money($weekRevenue); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-briefcase"></i>

                <?= operator_number($weekJobs); ?>
                jobs handled

            </div>


            <div class="operator-kpi-footer">

                <span class="operator-kpi-meta">

                    <i class="fa-solid fa-money-check-dollar"></i>

                    Collected

                </span>

                <span class="operator-kpi-tag">

                    <?= operator_money($weekCollected); ?>

                </span>

            </div>

        </div>



        <!-- ================================================================
             MONTHLY
        ================================================================= -->

        <div class="operator-kpi-card operator-kpi-featured">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon cyan">

                    <i class="fa-solid fa-calendar-days"></i>

                </div>

                <span class="operator-kpi-period">
                    This Month
                </span>

            </div>


            <span class="operator-kpi-label">
                Monthly Operational Value
            </span>

            <strong class="operator-kpi-value">

                <?= operator_money($monthRevenue); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-layer-group"></i>

                <?= operator_number($monthJobs); ?>
                jobs processed this month

            </div>


            <div class="operator-kpi-footer">

                <span class="operator-kpi-meta">

                    <i class="fa-solid fa-money-bill-transfer"></i>

                    Collected

                </span>

                <span class="operator-kpi-tag">

                    <?= operator_money($monthCollected); ?>

                </span>

            </div>

        </div>



        <!-- ================================================================
             AVERAGE JOB VALUE
        ================================================================= -->

        <div class="operator-kpi-card">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon blue">

                    <i class="fa-solid fa-calculator"></i>

                </div>

                <span class="operator-kpi-period">
                    Efficiency
                </span>

            </div>


            <span class="operator-kpi-label">
                Average Job Value
            </span>

            <strong class="operator-kpi-value">

                <?= operator_money($averageJobValue); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-chart-simple"></i>

                Based on monthly operations

            </div>

        </div>



        <!-- ================================================================
             COLLECTION RATE
        ================================================================= -->

        <div class="operator-kpi-card">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon green">

                    <i class="fa-solid fa-percent"></i>

                </div>

                <span class="operator-kpi-period">
                    Performance
                </span>

            </div>


            <span class="operator-kpi-label">
                Collection Efficiency
            </span>

            <strong class="operator-kpi-value">

                <?= operator_percent($collectionRate); ?>

            </strong>


            <div class="operator-progress">

                <div
                    class="operator-progress-bar"
                    style="width: <?= min(100, max(0, $collectionRate)); ?>%;"
                ></div>

            </div>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-bullseye"></i>

                Monthly value collected

            </div>

        </div>



        <!-- ================================================================
             COMPLETION RATE
        ================================================================= -->

        <div class="operator-kpi-card">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon purple">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <span class="operator-kpi-period">
                    Operations
                </span>

            </div>


            <span class="operator-kpi-label">
                Job Completion Rate
            </span>

            <strong class="operator-kpi-value">

                <?= operator_percent($completionRate); ?>

            </strong>


            <div class="operator-progress">

                <div
                    class="operator-progress-bar"
                    style="width: <?= min(100, max(0, $completionRate)); ?>%;"
                ></div>

            </div>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-check-double"></i>

                Completed versus total monthly jobs

            </div>

        </div>



        <!-- ================================================================
             OUTSTANDING BALANCE
        ================================================================= -->

        <div class="operator-kpi-card attention">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon orange">

                    <i class="fa-solid fa-wallet"></i>

                </div>

                <span class="operator-kpi-period">
                    Attention
                </span>

            </div>


            <span class="operator-kpi-label">
                Outstanding Job Value
            </span>

            <strong class="operator-kpi-value">

                <?= operator_money($totalOutstanding); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-file-invoice-dollar"></i>

                Total unpaid balance under your jobs

            </div>

        </div>



        <!-- ================================================================
             OVERDUE
        ================================================================= -->

        <div class="operator-kpi-card attention">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon red">

                    <i class="fa-solid fa-calendar-xmark"></i>

                </div>

                <span class="operator-kpi-period">
                    Critical
                </span>

            </div>


            <span class="operator-kpi-label">
                Overdue Jobs
            </span>

            <strong class="operator-kpi-value">

                <?= operator_number($overdueJobs); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-bell"></i>

                Jobs with unpaid balances past due date

            </div>

        </div>



        <!-- ================================================================
             YEAR TO DATE
        ================================================================= -->

        <div class="operator-kpi-card">

            <div class="operator-kpi-top">

                <div class="operator-kpi-icon orange">

                    <i class="fa-solid fa-calendar-check"></i>

                </div>

                <span class="operator-kpi-period">
                    <?= date('Y'); ?>
                </span>

            </div>


            <span class="operator-kpi-label">
                Annual Operational Value
            </span>

            <strong class="operator-kpi-value">

                <?= operator_money($yearRevenue); ?>

            </strong>


            <div class="operator-kpi-meta">

                <i class="fa-solid fa-chart-column"></i>

                <?= operator_number($yearJobs); ?>
                jobs year-to-date

            </div>


            <div class="operator-kpi-footer">

                <span class="operator-kpi-meta">

                    <i class="fa-solid fa-money-bill"></i>

                    Collected

                </span>

                <span class="operator-kpi-tag">

                    <?= operator_money($yearCollected); ?>

                </span>

            </div>

        </div>


    </div>

</div>