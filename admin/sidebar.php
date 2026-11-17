<!-- ===== FUTURISTIC SIDEBAR START ===== -->

<?php
// ============================================================
// COMPANY LOGO — unchanged from the original sidebar
// ============================================================

$userId = $_SESSION['user_id'] ?? null;
$logoPath = '';

if ($userId) {
    $stmtUser = $pdo->prepare("SELECT company_id FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['company_id'])) {
        $companyId = $user['company_id'];
        $stmtCompany = $pdo->prepare("SELECT company_logo FROM companies WHERE id = ? LIMIT 1");
        $stmtCompany->execute([$companyId]);
        $company = $stmtCompany->fetch(PDO::FETCH_ASSOC);

        if ($company && !empty($company['company_logo'])) {
            $logoPath = '../uploads/logos/' . $company['company_logo'];
        }
    }
}
?>

<!-- ============================================================
     FUTURISTIC SIDEBAR — BRANCH MANAGER MENU
     UI structure, classes, Alpine behaviour and CSS are preserved.
============================================================ -->

<aside
    :class="sidebarToggle ? 'translate-x-0 lg:w-[290px]' : '-translate-x-full lg:translate-x-0 lg:w-[90px]'"
    class="sidebar futuristic-sidebar fixed top-0 left-0 z-[99999] flex h-screen w-[290px] flex-col overflow-hidden border-r border-gray-200 bg-white transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-black lg:static"
    @click.outside="sidebarToggle = false"
    @mouseenter="if (window.innerWidth >= 1024) { sidebarToggle = true }"
    @mouseleave="if (window.innerWidth >= 1024) { sidebarToggle = false }"
>
    <!-- SIDEBAR HEADER -->
    <div
        :class="sidebarToggle ? 'justify-start' : 'justify-center'"
        class="sidebar-header relative flex min-h-[86px] items-center gap-3 border-b border-gray-200/70 px-5 py-5 dark:border-gray-800/70"
    >
        <div class="sidebar-header-glow"></div>

        <a href="index.php" id="sidebar-logo" class="relative z-10 flex min-w-0 items-center justify-center">
            <?php if (!empty($logoPath)): ?>
                <img src="<?php echo htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Company Logo" class="sidebar-company-logo" />
            <?php else: ?>
                <span class="sidebar-fallback-logo"><i class="fa-solid fa-building"></i></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- SIDEBAR SCROLL AREA -->
    <div class="sidebar-scroll flex flex-1 flex-col overflow-y-auto overflow-x-hidden px-3 py-5">
        <nav x-data="{selected: $persist('Dashboard')}">
            <div class="sidebar-section-heading">
                <span class="menu-group-title" :class="sidebarToggle ? '' : 'lg:hidden'">
                    <i class="fa-solid fa-grid-2"></i> MAIN MENU
                </span>
                <span :class="sidebarToggle ? 'hidden' : 'lg:flex'" class="sidebar-collapsed-dots">
                    <i class="fa-solid fa-ellipsis"></i>
                </span>
            </div>

            <ul class="sidebar-menu-list">
                <!-- BRANCH OVERVIEW -->
                <li>
                    <a href="index.php" class="menu-item futuristic-menu-item">
                        <span class="menu-icon"><i class="fa-solid fa-gauge-high"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Branch Overview</span>
                    </a>
                </li>

                <!-- MESSENGER -->
                <li>
                    <a href="messenger.php" class="menu-item futuristic-menu-item">
                        <span class="menu-icon"><i class="fa-solid fa-comments"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Messenger</span>
                    </a>
                </li>

                <!-- SALES -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'sales' ? '' : 'sales')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-cash-register"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Sales</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'sales' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'sales' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="new_sales.php"><i class="fa-solid fa-cart-plus"></i><span>Create Sale</span></a></li>
                            <li><a href="sales.php"><i class="fa-solid fa-calendar-day"></i><span>Daily Sales</span></a></li>
                            <li><a href="sales_w.php"><i class="fa-solid fa-calendar-week"></i><span>Weekly Sales</span></a></li>
                            <li><a href="sales_m.php"><i class="fa-solid fa-calendar"></i><span>Monthly Sales</span></a></li>
                            <li><a href="sales_a.php"><i class="fa-solid fa-chart-line"></i><span>Annual Sales</span></a></li>
                        </ul>
                    </div>
                </li>

                <!-- CUSTOMERS -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'Customers' ? '' : 'Customers')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-users"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Customers</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'Customers' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'Customers' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="existing_customers.php"><i class="fa-solid fa-address-book"></i><span>All Customers</span></a></li>
                        </ul>
                    </div>
                </li>

                <!-- CUSTOMER DEBTS -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'Debts' ? '' : 'Debts')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Customer Debts</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'Debts' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'Debts' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="customer-debts.php"><i class="fa-solid fa-list"></i><span>Outstanding Debts</span></a></li>
                            <li><a href="paid_debt.php"><i class="fa-solid fa-circle-check"></i><span>Paid Debts</span></a></li>
                        </ul>
                    </div>
                </li>

                <!-- EXPENSES -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'Expenses' ? '' : 'Expenses')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-money-bill-transfer"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Expenses</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'Expenses' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'Expenses' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="expenses.php"><i class="fa-solid fa-wallet"></i><span>Branch Expenses</span></a></li>
                            <li><a href="record-expenses.php"><i class="fa-solid fa-plus-circle"></i><span>Record Expense</span></a></li>
                        </ul>
                    </div>
                </li>

                <!-- PRODUCTS: branch manager can inspect catalogue and tracked units,
                     but does not manage company-wide product definitions. -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'Product' ? '' : 'Product')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-box-open"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Products</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'Product' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'Product' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="products.php"><i class="fa-solid fa-boxes-stacked"></i><span>Product Catalogue</span></a></li>
                            <li><a href="tracked-products.php"><i class="fa-solid fa-barcode"></i><span>IMEI / Serial / VIN Tracking</span></a></li>
                        </ul>
                    </div>
                </li>

                <!-- STOCK CONTROL -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'stocks' ? '' : 'stocks')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Stock Control</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'stocks' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'stocks' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="stocks.php"><i class="fa-solid fa-chart-pie"></i><span>Stock Overview</span></a></li>
                            <li><a href="stock-in.php"><i class="fa-solid fa-boxes-packing"></i><span>Receive Stock</span></a></li>
                            <li><a href="stock-movement.php"><i class="fa-solid fa-right-left"></i><span>Stock Transfers</span></a></li>
                            <li><a href="stock_transaction.php"><i class="fa-solid fa-clock-rotate-left"></i><span>Stock History</span></a></li>
                            
                    </div>
                </li>

                <!-- STAFF SUPERVISION -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'staffs' ? '' : 'staffs')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-user-group"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Staff Supervision</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'staffs' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'staffs' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="staffs.php"><i class="fa-solid fa-users"></i><span>Branch Staff</span></a></li>
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

                <!-- SERVICES: operational oversight, not service catalogue administration -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'service' ? '' : 'service')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-briefcase"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Services</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'service' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'service' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="existing-service.php"><i class="fa-solid fa-list-check"></i><span>Available Services</span></a></li>
                            <li><a href="register-job.php"><i class="fa-solid fa-play"></i><span>Render Service</span></a></li>
                            <li class="submenu-divider"></li>
                            <li><a href="job-entries.php"><i class="fa-solid fa-calendar-day"></i><span>Daily Service Jobs</span></a></li>
                            <li><a href="job-entries_w.php"><i class="fa-solid fa-calendar-week"></i><span>Weekly Service Jobs</span></a></li>
                            <li><a href="job-entries_m.php"><i class="fa-solid fa-calendar"></i><span>Monthly Service Jobs</span></a></li>
                            <li><a href="job-entries_a.php"><i class="fa-solid fa-chart-column"></i><span>Annual Service Jobs</span></a></li>
                        </ul>
                    </div>
                </li>

                <!-- VENDORS: existing vendor records for branch operations -->
                <li>
                    <a href="#" @click.prevent="selected = (selected === 'vendors' ? '' : 'vendors')" class="menu-item futuristic-menu-item group">
                        <span class="menu-icon"><i class="fa-solid fa-truck-fast"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Vendors</span>
                        <i class="fa-solid fa-chevron-down submenu-chevron" :class="[sidebarToggle ? '' : 'lg:hidden', selected === 'vendors' ? 'rotate-180' : '']"></i>
                    </a>
                    <div :class="selected === 'vendors' ? 'block' : 'hidden'" class="submenu-wrapper">
                        <ul class="menu-dropdown futuristic-dropdown">
                            <li><a href="existing_suppliers.php"><i class="fa-solid fa-address-book"></i><span>Vendor Directory</span></a></li>
                             <li>

                                <a href="new_suppliers.php">

                                    <i class="fa-solid fa-user-plus"></i>

                                    <span>Register Vendor</span>

                                </a>

                            </li>
                        </ul>
                    </div>
                </li>

                <!-- PROFILE -->
                <li class="sidebar-profile-item">
                    <a href="profile.php" class="menu-item futuristic-menu-item profile-menu-item">
                        <span class="menu-icon"><i class="fa-solid fa-circle-user"></i></span>
                        <span class="menu-item-text" :class="sidebarToggle ? '' : 'lg:hidden'">Profile</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- ================================================================
     FUTURISTIC SIDEBAR CSS — PRESERVED FROM ORIGINAL DESIGN
================================================================ -->
<style>
.futuristic-sidebar {
    --sidebar-accent: #d6a84f;
    --sidebar-accent-light: #f4d27b;
    --sidebar-dark: #07090d;
    --sidebar-dark-2: #0d1118;
    --sidebar-border: rgba(148,163,184,.16);
}

.dark .futuristic-sidebar {
    background: radial-gradient(circle at 15% 0%, rgba(214,168,79,.09), transparent 28%), linear-gradient(180deg, #07090d 0%, #090c12 48%, #06080c 100%) !important;
    border-color: rgba(255,255,255,.08) !important;
    box-shadow: 14px 0 50px rgba(0,0,0,.25);
}

.sidebar-header { position: relative; flex-shrink: 0; transition: justify-content .25s ease; }
.sidebar-header-glow { position: absolute; width: 100px; height: 100px; top: -65px; left: 15px; border-radius: 999px; background: rgba(214,168,79,.13); filter: blur(35px); pointer-events: none; }
.sidebar-company-logo { display: block; width: 43px !important; height: 43px !important; max-width: none !important; max-height: none !important; object-fit: contain; object-position: center; border-radius: 10px; transition: width .3s ease, height .3s ease, max-width .3s ease, border-radius .3s ease, transform .3s ease; }

@media (min-width: 1024px) {
    .futuristic-sidebar.w-\[290px\] .sidebar-company-logo { width: auto !important; max-width: 155px !important; height: 48px !important; border-radius: 0; object-fit: contain; object-position: left center; }
}

.sidebar-fallback-logo { width: 43px; height: 43px; border-radius: 13px; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(135deg, #d6a84f, #87651f); box-shadow: 0 10px 30px rgba(214,168,79,.18); font-size: 17px; flex-shrink: 0; }
.sidebar-scroll { scrollbar-width: thin; scrollbar-color: rgba(148,163,184,.22) transparent; }
.sidebar-scroll::-webkit-scrollbar { width: 5px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,.18); border-radius: 20px; }
.sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(214,168,79,.45); }

.sidebar-section-heading { display: flex; align-items: center; min-height: 30px; margin: 2px 9px 10px; color: #94a3b8; font-size: 10px; font-weight: 700; letter-spacing: .16em; }
.sidebar-section-heading i { margin-right: 7px; font-size: 9px; color: var(--sidebar-accent); }
.sidebar-collapsed-dots { align-items: center; justify-content: center; width: 100%; color: #64748b; }
.sidebar-menu-list { display: flex; flex-direction: column; gap: 5px; padding: 0; margin: 0; list-style: none; }

.futuristic-menu-item { position: relative; display: flex; align-items: center; min-height: 48px; width: 100%; padding: 7px 10px; border-radius: 13px; color: #64748b; text-decoration: none; transition: background .22s ease, color .22s ease, transform .22s ease, box-shadow .22s ease; }
.dark .futuristic-menu-item { color: #94a3b8; }
.futuristic-menu-item::before { content: ""; position: absolute; left: 0; top: 50%; width: 3px; height: 0; transform: translateY(-50%) scaleY(0); border-radius: 999px; background: var(--sidebar-accent); transition: height .22s ease, transform .22s ease; }
.futuristic-menu-item:hover { color: #111827; background: rgba(15,23,42,.055); transform: translateX(2px); }
.dark .futuristic-menu-item:hover { color: #fff; background: linear-gradient(90deg, rgba(214,168,79,.10), rgba(255,255,255,.025)); box-shadow: inset 0 0 0 1px rgba(214,168,79,.06); }
.futuristic-menu-item:hover::before { height: 22px; transform: translateY(-50%) scaleY(1); }

.menu-icon { flex: 0 0 36px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 15px; color: #64748b; background: rgba(148,163,184,.08); transition: color .22s ease, background .22s ease, box-shadow .22s ease, transform .22s ease; }
.dark .menu-icon { color: #94a3b8; background: rgba(255,255,255,.045); }
.futuristic-menu-item:hover .menu-icon { color: #a87920; background: rgba(214,168,79,.12); box-shadow: 0 0 0 1px rgba(214,168,79,.08); transform: scale(1.04); }
.dark .futuristic-menu-item:hover .menu-icon { color: var(--sidebar-accent-light); background: rgba(214,168,79,.11); box-shadow: 0 0 20px rgba(214,168,79,.08); }
.menu-item-text { margin-left: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 13px; font-weight: 600; letter-spacing: .01em; transition: color .22s ease; }
.submenu-chevron { margin-left: auto; font-size: 9px; color: #94a3b8; transition: transform .25s ease, color .2s ease; }
.futuristic-menu-item:hover .submenu-chevron { color: var(--sidebar-accent); }
.submenu-wrapper { margin-top: 2px; }
.futuristic-dropdown { position: relative; display: flex; flex-direction: column; gap: 2px; margin: 3px 0 7px 18px; padding: 6px 5px 6px 17px; border-left: 1px solid rgba(148,163,184,.16); list-style: none; }
.dark .futuristic-dropdown { border-left-color: rgba(214,168,79,.13); }
.futuristic-dropdown li a { position: relative; display: flex; align-items: center; min-height: 38px; padding: 6px 9px; border-radius: 9px; color: #64748b; font-size: 12px; font-weight: 500; text-decoration: none; transition: background .2s ease, color .2s ease, padding-left .2s ease; }
.dark .futuristic-dropdown li a { color: #7f8da3; }
.futuristic-dropdown li a i { width: 19px; margin-right: 7px; text-align: center; font-size: 10px; color: #94a3b8; transition: color .2s ease, transform .2s ease; }
.futuristic-dropdown li a:hover { color: #111827; background: rgba(15,23,42,.05); padding-left: 13px; }
.dark .futuristic-dropdown li a:hover { color: #f8fafc; background: linear-gradient(90deg, rgba(214,168,79,.10), rgba(214,168,79,.025)); }
.futuristic-dropdown li a:hover i { color: var(--sidebar-accent); transform: translateX(2px); }
.submenu-divider { height: 1px; margin: 6px 8px; background: linear-gradient(90deg, rgba(148,163,184,.15), transparent); }

.sidebar-profile-item { margin-top: 8px; padding-top: 9px; border-top: 1px solid rgba(148,163,184,.12); }
.dark .sidebar-profile-item { border-top-color: rgba(255,255,255,.07); }
.profile-menu-item .menu-icon { background: linear-gradient(135deg, rgba(214,168,79,.13), rgba(214,168,79,.04)); color: var(--sidebar-accent); }

@media (min-width: 1024px) {
    .sidebar.w-\[90px\] .sidebar-header { justify-content: center !important; padding-left: 10px; padding-right: 10px; }
    .sidebar.w-\[90px\] #sidebar-logo { width: 43px; justify-content: center; }
    .sidebar.w-\[90px\] .futuristic-menu-item { justify-content: center; padding-left: 7px; padding-right: 7px; }
    .sidebar.w-\[90px\] .menu-icon { width: 40px; height: 40px; }
    .sidebar.w-\[90px\] .futuristic-menu-item:hover { transform: none; }
    .sidebar.w-\[90px\] .submenu-wrapper { display: none !important; }
    .sidebar.w-\[290px\] .sidebar-header { justify-content: flex-start !important; }
    .sidebar.w-\[290px\] #sidebar-logo { justify-content: flex-start; }
}

@media (max-width: 1023px) {
    .futuristic-sidebar { width: min(290px, 88vw) !important; z-index: 99999 !important; box-shadow: 20px 0 60px rgba(0,0,0,.22); }
    .sidebar-company-logo { width: auto !important; max-width: 145px !important; height: 48px !important; border-radius: 0; object-fit: contain; object-position: left center; }
    .sidebar-header { justify-content: flex-start !important; padding-left: 20px; padding-right: 20px; }
    .sidebar-scroll { padding-bottom: 30px; }
    .futuristic-menu-item { min-height: 50px; }
    .menu-icon { width: 38px; height: 38px; }
    .menu-item-text { font-size: 13px; }
}

@media (prefers-reduced-motion: reduce) {
    .futuristic-sidebar *, .futuristic-sidebar *::before, .futuristic-sidebar *::after { transition: none !important; }
}
</style>

<!-- ===== FUTURISTIC SIDEBAR END ===== -->
