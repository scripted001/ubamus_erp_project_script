
<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../app_data.php');
include('../company_data.php');

$company_id = $_SESSION['company_id'];
$user_email = $_SESSION['user_email'];
$approved_by = "Not Approved!";
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?php echo htmlspecialchars($company_name); ?> | Staff Area</title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>
        /* =========================================================
           SLIDES PAGE
           Responsive + Abstract Advertising Design
        ========================================================= */

        .slides-page {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .slides-header {
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #f7f7fb 100%);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
        }

        .dark .slides-header {
            background: linear-gradient(135deg, #111827 0%, #151122 100%);
            border-color: #374151;
        }

        .slides-header::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            right: -70px;
            top: -90px;
            border: 25px solid rgba(99, 102, 241, 0.08);
        }

        .slides-header::after {
            content: "";
            position: absolute;
            width: 100px;
            height: 100px;
            right: 90px;
            bottom: -55px;
            transform: rotate(45deg);
            border: 18px solid rgba(124, 58, 237, 0.06);
        }

        .slides-header-content {
            position: relative;
            z-index: 2;
        }

        .slides-title {
            margin: 0;
            font-size: clamp(1.35rem, 3vw, 2rem);
            font-weight: 800;
            color: #111827;
            line-height: 1.2;
        }

        .dark .slides-title {
            color: #fff;
        }

        .slides-subtitle {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 0.95rem;
            max-width: 700px;
        }

        .dark .slides-subtitle {
            color: #9ca3af;
        }

        .advert-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 24px;
        }

        .advert-card {
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            padding: 24px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }

        .advert-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border-color: #c7d2fe;
        }

        .dark .advert-card {
            background: #111827;
            border-color: #374151;
        }

        .dark .advert-card:hover {
            border-color: #4b5563;
        }

        /* Abstract background decorations */
        .advert-decoration {
            position: absolute;
            pointer-events: none;
            z-index: 0;
        }

        .advert-decoration.one {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 25px solid rgba(99, 102, 241, 0.06);
            right: -70px;
            top: -70px;
        }

        .advert-decoration.two {
            width: 100px;
            height: 100px;
            border: 16px solid rgba(168, 85, 247, 0.05);
            transform: rotate(45deg);
            right: 30px;
            bottom: -60px;
        }

        .advert-content {
            position: relative;
            z-index: 2;
        }

        .advert-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 22px;
        }

        .advert-number {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #341893;
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(52, 24, 147, 0.18);
        }

        .advert-title {
            flex: 1;
            margin: 0;
            color: #1f2937;
            font-size: 1.15rem;
            font-weight: 750;
        }

        .dark .advert-title {
            color: #fff;
        }

        .advert-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .dark .advert-badge {
            background: #1f2937;
            color: #9ca3af;
        }

        .field-group {
            margin-bottom: 18px;
        }

        .field-label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 650;
        }

        .dark .field-label {
            color: #d1d5db;
        }

        .field-help {
            margin-top: 6px;
            color: #9ca3af;
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .slides-input {
            width: 100%;
            min-height: 44px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #1f2937;
            padding: 10px 13px;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .slides-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.10);
        }

        .dark .slides-input {
            background: #111827;
            border-color: #374151;
            color: #f9fafb;
        }

        .dark .slides-input:focus {
            border-color: #818cf8;
        }

        textarea.slides-input {
            min-height: 110px;
            resize: vertical;
        }

        input[type="file"].slides-input {
            padding: 8px;
        }

        input[type="file"].slides-input::file-selector-button {
            border: none;
            border-radius: 7px;
            background: #341893;
            color: #fff;
            padding: 8px 12px;
            margin-right: 10px;
            cursor: pointer;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .submit-area {
            display: flex;
            justify-content: center;
            padding: 10px 0 20px;
        }

        .save-slides-btn {
            width: 100%;
            max-width: 330px;
            min-height: 48px;
            border: none;
            border-radius: 11px;
            background: #341893;
            color: #fff;
            padding: 12px 20px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(52, 24, 147, 0.20);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .save-slides-btn:hover {
            background: #28116f;
            transform: translateY(-1px);
        }

        .save-slides-btn:active {
            transform: translateY(0);
        }

        /* Desktop: 2 cards per row */
        @media (min-width: 768px) {
            .advert-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .slides-header {
                padding: 28px;
            }

            .advert-card {
                padding: 26px;
            }
        }

        /* Large desktop */
        @media (min-width: 1200px) {
            .advert-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 28px;
            }
        }

        /* Mobile */
        @media (max-width: 640px) {
            .slides-header {
                padding: 18px;
                border-radius: 16px;
                margin-bottom: 16px;
            }

            .advert-card {
                padding: 17px;
                border-radius: 16px;
            }

            .advert-heading {
                align-items: flex-start;
            }

            .advert-number {
                width: 40px;
                height: 40px;
                flex-basis: 40px;
                border-radius: 11px;
            }

            .advert-title {
                font-size: 1rem;
            }

            .advert-badge {
                display: none;
            }

            .field-group {
                margin-bottom: 15px;
            }

            .submit-area {
                padding-top: 0;
            }

            .save-slides-btn {
                max-width: none;
            }
        }
    </style>
</head>

<body
    x-data="{ page: 'ecommerce', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>

    <!-- ===== Page Wrapper Start ===== -->
    <div class="flex h-screen overflow-hidden">

        <?php include('sidebar.php'); ?>

        <!-- ===== Content Area Start ===== -->
        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

            <!-- ===== Main Content Start ===== -->
            <main>

                <?php include('header.php'); ?>

                <div class="mx-auto max-w-(--breakpoint-2xl) p-3 sm:p-4 md:p-6">

                    <div class="slides-page">

                        <!-- Page Header -->
                        <div class="slides-header">
                            <div class="slides-header-content">
                                <h1 class="slides-title">
                                    Advertisement Slides
                                </h1>

                                <p class="slides-subtitle">
                                    Create and manage your company advertising slides.
                                    Upload separate desktop and mobile artwork and add
                                    the text that should appear with each advertisement.
                                </p>
                            </div>
                        </div>

                        <form
                            action="save-slides.php"
                            method="POST"
                            enctype="multipart/form-data"
                        >

                            <!-- Hidden fields -->
                            <input
                                type="hidden"
                                name="company_id"
                                value="<?= htmlspecialchars($company_id); ?>"
                            >

                            <input
                                type="hidden"
                                name="created_by"
                                value="<?= htmlspecialchars($user_email); ?>"
                            >

                            <div class="advert-grid">

                                <?php for ($i = 1; $i <= 4; $i++): ?>

                                    <div class="advert-card">

                                        <!-- Abstract decoration -->
                                        <div class="advert-decoration one"></div>
                                        <div class="advert-decoration two"></div>

                                        <div class="advert-content">

                                            <!-- Advert Heading -->
                                            <div class="advert-heading">

                                                <div class="advert-number">
                                                    <?= $i; ?>
                                                </div>

                                                <h3 class="advert-title">
                                                    Advert <?= $i; ?>
                                                </h3>

                                                <span class="advert-badge">
                                                    <i class="fa-solid fa-layer-group"></i>
                                                    Slide <?= $i; ?>
                                                </span>

                                            </div>

                                            <!-- Slide Order -->
                                            <input
                                                type="hidden"
                                                name="slides[<?= $i ?>][slide_order]"
                                                value="<?= $i ?>"
                                            >

                                            <!-- Desktop Image -->
                                            <div class="field-group">

                                                <label class="field-label">
                                                    <i class="fa-solid fa-desktop mr-1"></i>
                                                    Desktop Background Image
                                                </label>

                                                <input
                                                    type="file"
                                                    name="slides[<?= $i ?>][desktop_image]"
                                                    accept="image/*"
                                                    class="slides-input"
                                                >

                                                <p class="field-help">
                                                    Recommended for desktop and large screens.
                                                    Landscape images are preferred.
                                                </p>

                                            </div>

                                            <!-- Mobile Image -->
                                            <div class="field-group">

                                                <label class="field-label">
                                                    <i class="fa-solid fa-mobile-screen-button mr-1"></i>
                                                    Mobile Background Image
                                                </label>

                                                <input
                                                    type="file"
                                                    name="slides[<?= $i ?>][mobile_image]"
                                                    accept="image/*"
                                                    class="slides-input"
                                                >

                                                <p class="field-help">
                                                    Optional. Use a portrait-oriented image
                                                    for better mobile presentation.
                                                </p>

                                            </div>

                                            <!-- Headline -->
                                            <div class="field-group">

                                                <label class="field-label">
                                                    <i class="fa-solid fa-heading mr-1"></i>
                                                    Headline
                                                </label>

                                                <input
                                                    type="text"
                                                    name="slides[<?= $i ?>][headline]"
                                                    placeholder="Enter advertisement headline"
                                                    class="slides-input"
                                                >

                                            </div>

                                            <!-- Description -->
                                            <div class="field-group">

                                                <label class="field-label">
                                                    <i class="fa-solid fa-align-left mr-1"></i>
                                                    Description
                                                </label>

                                                <textarea
                                                    name="slides[<?= $i ?>][description]"
                                                    rows="3"
                                                    placeholder="Enter a short informative description..."
                                                    class="slides-input"
                                                ></textarea>

                                            </div>

                                            <!-- Status -->
                                            <input
                                                type="hidden"
                                                name="slides[<?= $i ?>][status]"
                                                value="active"
                                            >

                                        </div>

                                    </div>

                                <?php endfor; ?>

                            </div>

                            <!-- Submit -->
                            <div class="submit-area">

                                <button
                                    type="submit"
                                    name="submit"
                                    class="save-slides-btn"
                                >
                                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i>
                                    Save All Slides
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </main>
            <!-- ===== Main Content End ===== -->

        </div>
        <!-- ===== Content Area End ===== -->

    </div>
    <!-- ===== Page Wrapper End ===== -->

    <?php include('footer.php'); ?>

    <script defer src="bundle.js"></script>

</body>
</html>

