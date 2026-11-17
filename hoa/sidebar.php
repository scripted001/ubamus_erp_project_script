
<!-- ===== FUTURISTIC SIDEBAR START ===== -->

<?php

// ============================================================
// COMPANY LOGO
// ============================================================

$userId = $_SESSION['user_id'] ?? null;

$logoPath = '';

if ($userId) {

    $stmtUser = $pdo->prepare("
        SELECT company_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmtUser->execute([$userId]);

    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['company_id'])) {

        $companyId = $user['company_id'];

        $stmtCompany = $pdo->prepare("
            SELECT company_logo
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");

        $stmtCompany->execute([$companyId]);

        $company = $stmtCompany->fetch(PDO::FETCH_ASSOC);

        if ($company && !empty($company['company_logo'])) {

            $logoPath = '../uploads/logos/' . $company['company_logo'];

        }

    }

}

?>

<!-- ============================================================
     FUTURISTIC SIDEBAR
============================================================ -->

<aside

    :class="
        sidebarToggle
            ? 'translate-x-0 lg:w-[290px]'
            : '-translate-x-full lg:translate-x-0 lg:w-[90px]'
    "

    class="
        sidebar
        futuristic-sidebar
        fixed
        top-0
        left-0
        z-[99999]

        flex
        h-screen
        w-[290px]
        flex-col
        overflow-hidden

        border-r
        border-gray-200
        bg-white

        transition-all
        duration-300
        ease-in-out

        dark:border-gray-800
        dark:bg-black

        lg:static
    "

    @click.outside="sidebarToggle = false"

    @mouseenter="
        if (window.innerWidth >= 1024) {
            sidebarToggle = true
        }
    "

    @mouseleave="
        if (window.innerWidth >= 1024) {
            sidebarToggle = false
        }
"

>

    <!-- ========================================================
         SIDEBAR HEADER
    ========================================================= -->

    <div

        :class="
            sidebarToggle
                ? 'justify-start'
                : 'justify-center'
        "

        class="
            sidebar-header
            relative
            flex
            min-h-[86px]
            items-center
            gap-3
            border-b
            border-gray-200/70
            px-5
            py-5

            dark:border-gray-800/70
        "

    >

        <!-- Ambient Glow -->

        <div class="sidebar-header-glow"></div>


        <!-- ====================================================
             SINGLE COMPANY LOGO
        ===================================================== -->

        <a

            href="index.php"

            id="sidebar-logo"

            class="
                relative
                z-10
                flex
                min-w-0
                items-center
                justify-center
            "

        >

            <?php if (!empty($logoPath)): ?>

                <img

                    src="<?php echo htmlspecialchars($logoPath); ?>"

                    alt="Company Logo"

                    class="sidebar-company-logo"

                />

            <?php else: ?>

                <span class="sidebar-fallback-logo">

                    <i class="fa-solid fa-building"></i>

                </span>

            <?php endif; ?>

        </a>

    </div>


    <!-- ========================================================
         SIDEBAR SCROLL AREA
    ========================================================= -->

    <div

        class="
            sidebar-scroll
            flex
            flex-1
            flex-col
            overflow-y-auto
            overflow-x-hidden
            px-3
            py-5
        "

    >

        <!-- ====================================================
             MENU
        ===================================================== -->

        <nav x-data="{selected: $persist('Dashboard')}">


            <!-- ==================================================
                 MENU LABEL
            ================================================== -->

            <div class="sidebar-section-heading">

                <span

                    class="menu-group-title"

                    :class="
                        sidebarToggle
                            ? ''
                            : 'lg:hidden'
                    "

                >

                    <i class="fa-solid fa-grid-2"></i>

                    MAIN MENU

                </span>


                <span

                    :class="
                        sidebarToggle
                            ? 'hidden'
                            : 'lg:flex'
                    "

                    class="sidebar-collapsed-dots"

                >

                    <i class="fa-solid fa-ellipsis"></i>

                </span>

            </div>


            <!-- ==================================================
                 MENU ITEMS
            ================================================== -->

            <ul class="sidebar-menu-list">


                <!-- ==================================================
                     OVERVIEW
                ================================================== -->

                <li>

                    <a
                        href="index.php"
                        class="menu-item futuristic-menu-item"
                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-gauge-high"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Overview

                        </span>

                    </a>

                </li>


                <!-- ==================================================
                     MESSENGER
                ================================================== -->

                <li>

                    <a
                        href="messenger.php"
                        class="menu-item futuristic-menu-item"
                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-comments"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Messenger

                        </span>

                    </a>

                </li>


                <!-- ==================================================
                     COMPANY PROFILE
                ================================================== -->

                <li>

                    <a
                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'Company Profile'
                                    ? ''
                                    : 'Company Profile'
                            )
                        "

                        class="menu-item futuristic-menu-item group"
                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-building"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Company Profile

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'Company Profile'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'Company Profile'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="company-overview.php">

                                    <i class="fa-solid fa-chart-pie"></i>

                                    <span>Overview</span>

                                </a>

                            </li>


                            <li>

                                <a href="company-information.php">

                                    <i class="fa-solid fa-circle-info"></i>

                                    <span>Company Information</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     CUSTOMERS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'Customers'
                                    ? ''
                                    : 'Customers'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-users"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Customers

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'Customers'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'Customers'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="existing_customers.php">

                                    <i class="fa-solid fa-address-book"></i>

                                    <span>All Customers</span>

                                </a>

                            </li>


                            <li>

                                <a href="customer-award.php">

                                    <i class="fa-solid fa-award"></i>

                                    <span>Awards</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     DEBTS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'Debts'
                                    ? ''
                                    : 'Debts'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-file-invoice-dollar"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Debts

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'Debts'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'Debts'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="customer-debts.php">

                                    <i class="fa-solid fa-list"></i>

                                    <span>All Debts</span>

                                </a>

                            </li>


                            <li>

                                <a href="paid_debt.php">

                                    <i class="fa-solid fa-circle-check"></i>

                                    <span>Paid Debts</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     EXPENSES
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'Expenses'
                                    ? ''
                                    : 'Expenses'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-money-bill-transfer"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Expenses

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'Expenses'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'Expenses'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="expenses.php">

                                    <i class="fa-solid fa-wallet"></i>

                                    <span>All Expenses</span>

                                </a>

                            </li>


                            <li>

                                <a href="record-expenses.php">

                                    <i class="fa-solid fa-plus-circle"></i>

                                    <span>Record Expense</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     EMAIL SETTINGS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'Email'
                                    ? ''
                                    : 'Email'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-envelope"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Email Settings

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'Email'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'Email'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="customer_care.php">

                                    <i class="fa-solid fa-headset"></i>

                                    <span>Customer Care Mail</span>

                                </a>

                            </li>


                            <li>

                                <a href="support.php">

                                    <i class="fa-solid fa-life-ring"></i>

                                    <span>Support Mail</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     LOCATIONS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'locations'
                                    ? ''
                                    : 'locations'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-location-dot"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Locations

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'locations'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'locations'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="location.php">

                                    <i class="fa-solid fa-plus"></i>

                                    <span>New Location</span>

                                </a>

                            </li>


                            <li>

                                <a href="locations.php">

                                    <i class="fa-solid fa-map"></i>

                                    <span>Existing Locations</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     LPMS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === '(LPMS)'
                                    ? ''
                                    : '(LPMS)'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-layer-group"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            LPMS

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === '(LPMS)'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === '(LPMS)'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="home.php">

                                    <i class="fa-solid fa-house"></i>

                                    <span>Home</span>

                                </a>

                            </li>


                            <li>

                                <a href="about.php">

                                    <i class="fa-solid fa-circle-info"></i>

                                    <span>About</span>

                                </a>

                            </li>
   <?php if (isset($_SERVER['HTTP_HOST']) && strtolower($_SERVER['HTTP_HOST']) === 'ubamus.com'): ?>

    <li>
        <a href="privacy.php">
            <i class="fa-solid fa-user-shield"></i>
            <span>Privacy Policy</span>
        </a>
    </li>

    <li>
        <a href="terms-conditions.php">
            <i class="fa-solid fa-file-contract"></i>
            <span>Terms &amp; Conditions</span>
        </a>
    </li>

<?php endif; ?>
                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     PRODUCTS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'Product'
                                    ? ''
                                    : 'Product'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-box-open"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Products

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'Product'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'Product'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">


                            <li class="submenu-label">

                                <i class="fa-solid fa-tags"></i>

                                Category Management

                            </li>


                            <li>

                                <a href="new_unit.php">

                                    <i class="fa-solid fa-ruler-combined"></i>

                                    <span>Create Unit</span>

                                </a>

                            </li>
                            <li>

                                <a href="units.php">

                                    <i class="fa-solid fa-ruler-combined"></i>

                                    <span>All Units</span>

                                </a>

                            </li>


                            <li>

                                <a href="new_category.php">

                                    <i class="fa-solid fa-folder-plus"></i>

                                    <span>New Category</span>

                                </a>

                            </li>


                            <li>

                                <a href="categories.php">

                                    <i class="fa-solid fa-folder-tree"></i>

                                    <span>Existing Categories</span>

                                </a>

                            </li>


                            <li class="submenu-divider"></li>


                            <li class="submenu-label">

                                <i class="fa-solid fa-boxes-stacked"></i>

                                Product Management

                            </li>


                            <li>

                                <a href="new_product.php">

                                    <i class="fa-solid fa-plus"></i>

                                    <span>Register Product</span>

                                </a>

                            </li>


                            <li>

                                <a href="products.php">

                                    <i class="fa-solid fa-boxes-stacked"></i>

                                    <span>Existing Products</span>

                                </a>

                            </li>

                              <!-- ==================================================
                 IMEI / SERIAL NUMBER TRACKING
            ================================================== -->

            <li>
                <a href="tracked-products.php">
                    <i class="fa-solid fa-barcode"></i>
                    <span>IMEI / Serial Tracking</span>
                </a>
            </li>
                            <li>

                                <a href="archieve-products.php">

                                    <i class="fa-solid fa-box-archive"></i>

                                    <span>Archive</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     SALES
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'sales'
                                    ? ''
                                    : 'sales'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-cash-register"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Sales

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'sales'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'sales'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="new_sales.php">

                                    <i class="fa-solid fa-cart-plus"></i>

                                    <span>Create Sale</span>

                                </a>

                            </li>


                            <li>

                                <a href="sales.php">

                                    <i class="fa-solid fa-calendar-day"></i>

                                    <span>Daily</span>

                                </a>

                            </li>


                            <li>

                                <a href="sales_w.php">

                                    <i class="fa-solid fa-calendar-week"></i>

                                    <span>Weekly</span>

                                </a>

                            </li>


                            <li>

                                <a href="sales_m.php">

                                    <i class="fa-solid fa-calendar"></i>

                                    <span>Monthly</span>

                                </a>

                            </li>


                            <li>

                                <a href="sales_a.php">

                                    <i class="fa-solid fa-chart-line"></i>

                                    <span>Annually</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     SERVICES
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'service'
                                    ? ''
                                    : 'service'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-briefcase"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Services

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'service'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'service'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">


                            <li>

                                <a href="service-categories.php">

                                    <i class="fa-solid fa-folder"></i>

                                    <span>Categories</span>

                                </a>

                            </li>


                            <li>

                                <a href="new-service.php">

                                    <i class="fa-solid fa-plus-circle"></i>

                                    <span>New Service</span>

                                </a>

                            </li>


                            <li>

                                <a href="existing-service.php">

                                    <i class="fa-solid fa-list-check"></i>

                                    <span>Existing Services</span>

                                </a>

                            </li>


                            <li>

                                <a href="register-job.php">

                                    <i class="fa-solid fa-play"></i>

                                    <span>Render Service</span>

                                </a>

                            </li>


                            <li class="submenu-divider"></li>


                            <li>

                                <a href="job-entries.php">

                                    <i class="fa-solid fa-calendar-day"></i>

                                    <span>Daily Rendered</span>

                                </a>

                            </li>


                            <li>

                                <a href="job-entries_w.php">

                                    <i class="fa-solid fa-calendar-week"></i>

                                    <span>Weekly Rendered</span>

                                </a>

                            </li>


                            <li>

                                <a href="job-entries_m.php">

                                    <i class="fa-solid fa-calendar"></i>

                                    <span>Monthly Rendered</span>

                                </a>

                            </li>


                            <li>

                                <a href="job-entries_a.php">

                                    <i class="fa-solid fa-chart-column"></i>

                                    <span>Annually Rendered</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     STAFFS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'staffs'
                                    ? ''
                                    : 'staffs'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-user-group"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Staffs

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'staffs'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'staffs'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="staffs.php">

                                    <i class="fa-solid fa-users"></i>

                                    <span>All Staff</span>

                                </a>

                            </li>


                            <li>

                                <a href="staff_awards.php">

                                    <i class="fa-solid fa-award"></i>

                                    <span>Awards</span>

                                </a>

                            </li>


                            <li>

                                <a href="new_staff.php">

                                    <i class="fa-solid fa-user-plus"></i>

                                    <span>Register Staff</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     STOCKS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'stocks'
                                    ? ''
                                    : 'stocks'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-boxes-stacked"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Stocks

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'stocks'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'stocks'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="archieved_stocks.php">

                                    <i class="fa-solid fa-box-archive"></i>

                                    <span>Archived</span>

                                </a>

                            </li>


                            <li>

                                <a href="stock-movement.php">

                                    <i class="fa-solid fa-right-left"></i>

                                    <span>Distributions</span>

                                </a>

                            </li>


                            <li>

                                <a href="stock_transaction.php">

                                    <i class="fa-solid fa-clock-rotate-left"></i>

                                    <span>History</span>

                                </a>

                            </li>


                            <li>

                                <a href="stocks.php">

                                    <i class="fa-solid fa-chart-pie"></i>

                                    <span>Stock Overview</span>

                                </a>

                            </li>


                            <li>

                                <a href="stock-in.php">

                                    <i class="fa-solid fa-boxes-packing"></i>

                                    <span>New Stock</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     INVENTORY
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'inventory'
                                    ? ''
                                    : 'inventory'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-warehouse"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Inventory

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'inventory'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'inventory'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="production_overview.php">

                                    <i class="fa-solid fa-chart-pie"></i>

                                    <span>Overview</span>

                                </a>

                            </li>


                            <li>

                                <a href="production.php">

                                    <i class="fa-solid fa-industry"></i>

                                    <span>Facility</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     VENDORS
                ================================================== -->

                <li>

                    <a

                        href="#"

                        @click.prevent="
                            selected = (
                                selected === 'vendors'
                                    ? ''
                                    : 'vendors'
                            )
                        "

                        class="menu-item futuristic-menu-item group"

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-truck-fast"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Vendors

                        </span>


                        <i

                            class="fa-solid fa-chevron-down submenu-chevron"

                            :class="[

                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden',

                                selected === 'vendors'
                                    ? 'rotate-180'
                                    : ''

                            ]"

                        ></i>

                    </a>


                    <div

                        :class="
                            selected === 'vendors'
                                ? 'block'
                                : 'hidden'
                        "

                        class="submenu-wrapper"

                    >

                        <ul class="menu-dropdown futuristic-dropdown">

                            <li>

                                <a href="existing_suppliers.php">

                                    <i class="fa-solid fa-address-book"></i>

                                    <span>All Vendors</span>

                                </a>

                            </li>


                            <li>

                                <a href="new_suppliers.php">

                                    <i class="fa-solid fa-user-plus"></i>

                                    <span>Register Vendor</span>

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>


                <!-- ==================================================
                     PROFILE
                ================================================== -->

                <li class="sidebar-profile-item">

                    <a

                        href="profile.php"

                        class="
                            menu-item
                            futuristic-menu-item
                            profile-menu-item
                        "

                    >

                        <span class="menu-icon">

                            <i class="fa-solid fa-circle-user"></i>

                        </span>


                        <span

                            class="menu-item-text"

                            :class="
                                sidebarToggle
                                    ? ''
                                    : 'lg:hidden'
                            "

                        >

                            Profile

                        </span>

                    </a>

                </li>


            </ul>

        </nav>

    </div>

</aside>


<!-- ================================================================
     FUTURISTIC SIDEBAR CSS
================================================================ -->

<style>

/* ============================================================
   SIDEBAR BASE
============================================================ */

.futuristic-sidebar {

    --sidebar-accent: #d6a84f;
    --sidebar-accent-light: #f4d27b;

    --sidebar-dark: #07090d;
    --sidebar-dark-2: #0d1118;

    --sidebar-border: rgba(148,163,184,.16);

}


/* ============================================================
   DARK MODE
============================================================ */

.dark .futuristic-sidebar {

    background:

        radial-gradient(
            circle at 15% 0%,
            rgba(214,168,79,.09),
            transparent 28%
        ),

        linear-gradient(
            180deg,
            #07090d 0%,
            #090c12 48%,
            #06080c 100%
        ) !important;

    border-color:
        rgba(255,255,255,.08) !important;

    box-shadow:
        14px 0 50px rgba(0,0,0,.25);

}


/* ============================================================
   HEADER
============================================================ */

.sidebar-header {

    position: relative;

    flex-shrink: 0;

    transition:
        justify-content .25s ease;

}


.sidebar-header-glow {

    position: absolute;

    width: 100px;
    height: 100px;

    top: -65px;
    left: 15px;

    border-radius: 999px;

    background:
        rgba(214,168,79,.13);

    filter:
        blur(35px);

    pointer-events:
        none;

}


/* ============================================================
   SINGLE COMPANY LOGO
============================================================ */

.sidebar-company-logo {

    display: block;

    width: 43px !important;
    height: 43px !important;

    max-width: none !important;
    max-height: none !important;

    object-fit: contain;

    object-position: center;

    border-radius: 10px;

    transition:

        width .3s ease,
        height .3s ease,
        max-width .3s ease,
        border-radius .3s ease,
        transform .3s ease;

}


/* ============================================================
   EXPANDED LOGO
============================================================ */

@media (min-width: 1024px) {

    .futuristic-sidebar.w-\[290px\]
    .sidebar-company-logo {

        width: auto !important;

        max-width: 155px !important;

        height: 48px !important;

        border-radius: 0;

        object-fit: contain;

        object-position: left center;

    }

}


/* ============================================================
   FALLBACK LOGO
============================================================ */

.sidebar-fallback-logo {

    width: 43px;
    height: 43px;

    border-radius: 13px;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #fff;

    background:

        linear-gradient(
            135deg,
            #d6a84f,
            #87651f
        );

    box-shadow:

        0 10px 30px
        rgba(214,168,79,.18);

    font-size: 17px;

    flex-shrink: 0;

}


/* ============================================================
   SCROLLBAR
============================================================ */

.sidebar-scroll {

    scrollbar-width:
        thin;

    scrollbar-color:
        rgba(148,163,184,.22)
        transparent;

}


.sidebar-scroll::-webkit-scrollbar {

    width: 5px;

}


.sidebar-scroll::-webkit-scrollbar-track {

    background:
        transparent;

}


.sidebar-scroll::-webkit-scrollbar-thumb {

    background:
        rgba(148,163,184,.18);

    border-radius:
        20px;

}


.sidebar-scroll::-webkit-scrollbar-thumb:hover {

    background:
        rgba(214,168,79,.45);

}


/* ============================================================
   SECTION TITLE
============================================================ */

.sidebar-section-heading {

    display:
        flex;

    align-items:
        center;

    min-height:
        30px;

    margin:
        2px 9px 10px;

    color:
        #94a3b8;

    font-size:
        10px;

    font-weight:
        700;

    letter-spacing:
        .16em;

}


.sidebar-section-heading i {

    margin-right:
        7px;

    font-size:
        9px;

    color:
        var(--sidebar-accent);

}


.sidebar-collapsed-dots {

    align-items:
        center;

    justify-content:
        center;

    width:
        100%;

    color:
        #64748b;

}


/* ============================================================
   MENU LIST
============================================================ */

.sidebar-menu-list {

    display:
        flex;

    flex-direction:
        column;

    gap:
        5px;

    padding:
        0;

    margin:
        0;

    list-style:
        none;

}


/* ============================================================
   MENU ITEM
============================================================ */

.futuristic-menu-item {

    position:
        relative;

    display:
        flex;

    align-items:
        center;

    min-height:
        48px;

    width:
        100%;

    padding:
        7px 10px;

    border-radius:
        13px;

    color:
        #64748b;

    text-decoration:
        none;

    transition:

        background .22s ease,
        color .22s ease,
        transform .22s ease,
        box-shadow .22s ease;

}


.dark .futuristic-menu-item {

    color:
        #94a3b8;

}


.futuristic-menu-item::before {

    content:
        "";

    position:
        absolute;

    left:
        0;

    top:
        50%;

    width:
        3px;

    height:
        0;

    transform:
        translateY(-50%)
        scaleY(0);

    border-radius:
        999px;

    background:
        var(--sidebar-accent);

    transition:

        height .22s ease,
        transform .22s ease;

}


.futuristic-menu-item:hover {

    color:
        #111827;

    background:
        rgba(15,23,42,.055);

    transform:
        translateX(2px);

}


.dark .futuristic-menu-item:hover {

    color:
        #fff;

    background:

        linear-gradient(
            90deg,
            rgba(214,168,79,.10),
            rgba(255,255,255,.025)
        );

    box-shadow:

        inset 0 0 0 1px
        rgba(214,168,79,.06);

}


.futuristic-menu-item:hover::before {

    height:
        22px;

    transform:
        translateY(-50%)
        scaleY(1);

}


/* ============================================================
   ICON CONTAINER
============================================================ */

.menu-icon {

    flex:
        0 0 36px;

    width:
        36px;

    height:
        36px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        10px;

    font-size:
        15px;

    color:
        #64748b;

    background:
        rgba(148,163,184,.08);

    transition:

        color .22s ease,
        background .22s ease,
        box-shadow .22s ease,
        transform .22s ease;

}


.dark .menu-icon {

    color:
        #94a3b8;

    background:
        rgba(255,255,255,.045);

}


.futuristic-menu-item:hover .menu-icon {

    color:
        #a87920;

    background:
        rgba(214,168,79,.12);

    box-shadow:
        0 0 0 1px
        rgba(214,168,79,.08);

    transform:
        scale(1.04);

}


.dark .futuristic-menu-item:hover .menu-icon {

    color:
        var(--sidebar-accent-light);

    background:
        rgba(214,168,79,.11);

    box-shadow:
        0 0 20px
        rgba(214,168,79,.08);

}


/* ============================================================
   TEXT
============================================================ */

.menu-item-text {

    margin-left:
        11px;

    white-space:
        nowrap;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    font-size:
        13px;

    font-weight:
        600;

    letter-spacing:
        .01em;

    transition:
        color .22s ease;

}


/* ============================================================
   CHEVRON
============================================================ */

.submenu-chevron {

    margin-left:
        auto;

    font-size:
        9px;

    color:
        #94a3b8;

    transition:

        transform .25s ease,
        color .2s ease;

}


.futuristic-menu-item:hover
.submenu-chevron {

    color:
        var(--sidebar-accent);

}


/* ============================================================
   SUBMENU
============================================================ */

.submenu-wrapper {

    margin-top:
        2px;

}


.futuristic-dropdown {

    position:
        relative;

    display:
        flex;

    flex-direction:
        column;

    gap:
        2px;

    margin:
        3px 0 7px 18px;

    padding:
        6px 5px 6px 17px;

    border-left:
        1px solid
        rgba(148,163,184,.16);

    list-style:
        none;

}


.dark .futuristic-dropdown {

    border-left-color:
        rgba(214,168,79,.13);

}


.futuristic-dropdown li a {

    position:
        relative;

    display:
        flex;

    align-items:
        center;

    min-height:
        38px;

    padding:
        6px 9px;

    border-radius:
        9px;

    color:
        #64748b;

    font-size:
        12px;

    font-weight:
        500;

    text-decoration:
        none;

    transition:

        background .2s ease,
        color .2s ease,
        padding-left .2s ease;

}


.dark .futuristic-dropdown li a {

    color:
        #7f8da3;

}


.futuristic-dropdown li a i {

    width:
        19px;

    margin-right:
        7px;

    text-align:
        center;

    font-size:
        10px;

    color:
        #94a3b8;

    transition:

        color .2s ease,
        transform .2s ease;

}


.futuristic-dropdown li a:hover {

    color:
        #111827;

    background:
        rgba(15,23,42,.05);

    padding-left:
        13px;

}


.dark .futuristic-dropdown li a:hover {

    color:
        #f8fafc;

    background:

        linear-gradient(
            90deg,
            rgba(214,168,79,.10),
            rgba(214,168,79,.025)
        );

}


.futuristic-dropdown li a:hover i {

    color:
        var(--sidebar-accent);

    transform:
        translateX(2px);

}


/* ============================================================
   SUBMENU LABELS
============================================================ */

.futuristic-dropdown .submenu-label {

    display:
        flex;

    align-items:
        center;

    padding:
        9px 8px 5px;

    color:
        #94a3b8;

    font-size:
        9px;

    font-weight:
        700;

    letter-spacing:
        .09em;

    text-transform:
        uppercase;

}


.futuristic-dropdown .submenu-label i {

    width:
        17px;

    margin-right:
        7px;

    color:
        var(--sidebar-accent);

    font-size:
        9px;

}


.submenu-divider {

    height:
        1px;

    margin:
        6px 8px;

    background:

        linear-gradient(
            90deg,
            rgba(148,163,184,.15),
            transparent
        );

}


/* ============================================================
   PROFILE SEPARATOR
============================================================ */

.sidebar-profile-item {

    margin-top:
        8px;

    padding-top:
        9px;

    border-top:
        1px solid
        rgba(148,163,184,.12);

}


.dark .sidebar-profile-item {

    border-top-color:
        rgba(255,255,255,.07);

}


.profile-menu-item .menu-icon {

    background:

        linear-gradient(
            135deg,
            rgba(214,168,79,.13),
            rgba(214,168,79,.04)
        );

    color:
        var(--sidebar-accent);

}


/* ============================================================
   COLLAPSED DESKTOP SIDEBAR
============================================================ */

@media (min-width: 1024px) {


    /* -----------------------------------------
       CENTER COLLAPSED SIDEBAR
    ----------------------------------------- */

    .sidebar.w-\[90px\]
    .sidebar-header {

        justify-content:
            center !important;

        padding-left:
            10px;

        padding-right:
            10px;

    }


    .sidebar.w-\[90px\]
    #sidebar-logo {

        width:
            43px;

        justify-content:
            center;

    }


    /* -----------------------------------------
       COLLAPSED MENU
    ----------------------------------------- */

    .sidebar.w-\[90px\]
    .futuristic-menu-item {

        justify-content:
            center;

        padding-left:
            7px;

        padding-right:
            7px;

    }


    .sidebar.w-\[90px\]
    .menu-icon {

        width:
            40px;

        height:
            40px;

    }


    .sidebar.w-\[90px\]
    .futuristic-menu-item:hover {

        transform:
            none;

    }


    /* -----------------------------------------
       HIDE SUBMENUS WHEN COLLAPSED
    ----------------------------------------- */

    .sidebar.w-\[90px\]
    .submenu-wrapper {

        display:
            none !important;

    }


    /* -----------------------------------------
       EXPANDED SIDEBAR
    ----------------------------------------- */

    .sidebar.w-\[290px\]
    .sidebar-header {

        justify-content:
            flex-start !important;

    }


    .sidebar.w-\[290px\]
    #sidebar-logo {

        justify-content:
            flex-start;

    }

}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 1023px) {


    .futuristic-sidebar {

        width:
            min(290px, 88vw) !important;

        /*
         * IMPORTANT:
         * The sidebar is simply above the header.
         * No overlay/backdrop is created.
         */

        z-index:
            99999 !important;

        box-shadow:
            20px 0 60px
            rgba(0,0,0,.22);

    }


    .sidebar-company-logo {

        width:
            auto !important;

        max-width:
            145px !important;

        height:
            48px !important;

        border-radius:
            0;

        object-fit:
            contain;

        object-position:
            left center;

    }


    .sidebar-header {

        justify-content:
            flex-start !important;

        padding-left:
            20px;

        padding-right:
            20px;

    }


    .sidebar-scroll {

        padding-bottom:
            30px;

    }


    .futuristic-menu-item {

        min-height:
            50px;

    }


    .menu-icon {

        width:
            38px;

        height:
            38px;

    }


    .menu-item-text {

        font-size:
            13px;

    }

}


/* ============================================================
   REDUCED MOTION
============================================================ */

@media (prefers-reduced-motion: reduce) {

    .futuristic-sidebar *,
    .futuristic-sidebar *::before,
    .futuristic-sidebar *::after {

        transition:
            none !important;

    }

}

</style>

<!-- ===== FUTURISTIC SIDEBAR END ===== -->
