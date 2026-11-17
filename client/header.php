<?php
/**
 * Customer marketplace header
 * Keeps the existing session, user/company lookup, profile lookup,
 * Alpine sidebar toggle, profile destination and logout destination.
 * Requires the parent page to have started the session and created $pdo.
 */

$customerHeaderUserId = $_SESSION['user_id'] ?? null;
$customerHeaderLogoPath = '';
$customerHeaderProfilePath = '';
$customerHeaderEmail = '';
$customerHeaderUsername = trim((string)($_SESSION['username'] ?? 'Customer'));

if ($customerHeaderUserId && isset($pdo) && $pdo instanceof PDO) {
    $customerHeaderUserStmt = $pdo->prepare(
        'SELECT company_id, email FROM users WHERE id = ? LIMIT 1'
    );
    $customerHeaderUserStmt->execute([$customerHeaderUserId]);
    $customerHeaderUser = $customerHeaderUserStmt->fetch(PDO::FETCH_ASSOC);

    if ($customerHeaderUser) {
        $customerHeaderCompanyId = $customerHeaderUser['company_id'] ?? null;
        $customerHeaderEmail = (string)($customerHeaderUser['email'] ?? '');

        if ($customerHeaderCompanyId) {
            $customerHeaderCompanyStmt = $pdo->prepare(
                'SELECT company_logo FROM companies WHERE id = ? LIMIT 1'
            );
            $customerHeaderCompanyStmt->execute([$customerHeaderCompanyId]);
            $customerHeaderCompany = $customerHeaderCompanyStmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($customerHeaderCompany['company_logo'])) {
                $customerHeaderLogoPath = '../uploads/logos/' . ltrim(
                    (string)$customerHeaderCompany['company_logo'],
                    '/\\'
                );
            }
        }

        // Preserve the existing profile-image source and lookup behaviour.
        if ($customerHeaderEmail !== '') {
            $customerHeaderStaffStmt = $pdo->prepare(
                'SELECT profile FROM staffs WHERE email = ? LIMIT 1'
            );
            $customerHeaderStaffStmt->execute([$customerHeaderEmail]);
            $customerHeaderStaff = $customerHeaderStaffStmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($customerHeaderStaff['profile'])) {
                $customerHeaderProfilePath = (string)$customerHeaderStaff['profile'];
            }
        }
    }
}

$customerHeaderInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($customerHeaderUsername, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($customerHeaderUsername, 0, 1));
?>

<!-- ===== CUSTOMER MARKETPLACE HEADER START ===== -->
<header
    x-data="{ menuToggle: false, customerDropdownOpen: false }"
    class="customer-market-header sticky top-0 z-40 w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-xl"
    @keydown.escape.window="menuToggle = false; customerDropdownOpen = false"
>
    <div class="customer-market-header-inner flex min-h-[76px] w-full items-center justify-between gap-4 px-3 sm:px-5 lg:px-7">

        <!-- LEFT: SIDEBAR CONTROL + MOBILE BRAND -->
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
            <button
                type="button"
                @click.stop="sidebarToggle = !sidebarToggle"
                :aria-expanded="sidebarToggle.toString()"
                aria-label="Toggle navigation sidebar"
                class="customer-header-icon-button group"
            >
                <svg class="hidden lg:block" width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 6H20M4 12H20M4 18H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <svg class="lg:hidden" width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7H20M4 12H20M4 17H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>

            <a href="index.php" class="customer-header-brand min-w-0 items-center gap-3" aria-label="Marketplace dashboard home">
                <?php if ($customerHeaderLogoPath !== ''): ?>
                    <span class="customer-header-brand-logo">
                        <img src="<?php echo htmlspecialchars($customerHeaderLogoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Company logo">
                    </span>
                <?php else: ?>
                    <span class="customer-header-brand-fallback"><i class="fa-solid fa-store"></i></span>
                <?php endif; ?>
                
            </a>
        </div>

        <!-- CENTRE: QUICK NAVIGATION (DESKTOP) -->
        <nav class="customer-header-quicknav hidden items-center gap-1 xl:flex" aria-label="Customer quick navigation">
            <a href="index.php" class="customer-header-navlink" aria-label="Marketplace">
                <i class="fa-solid fa-store"></i><span>Marketplace</span>
            </a>
            <a href="index.php#customer-cart" class="customer-header-navlink" aria-label="My cart">
                <i class="fa-solid fa-cart-shopping"></i><span>My Cart</span>
            </a>
            <a href="messenger.php" class="customer-header-navlink" aria-label="Messenger">
                <i class="fa-solid fa-comments"></i><span>Messenger</span>
            </a>
        </nav>

        <!-- RIGHT: MOBILE QUICK MENU + PROFILE -->
        <div class="flex flex-shrink-0 items-center gap-2 sm:gap-3">
            <button
                type="button"
                @click.stop="menuToggle = !menuToggle"
                :aria-expanded="menuToggle.toString()"
                aria-label="Open customer shortcuts"
                class="customer-header-icon-button xl:hidden"
            >
                <i class="fa-solid fa-ellipsis text-[17px]" aria-hidden="true"></i>
            </button>

            <div class="relative" @click.outside="customerDropdownOpen = false">
                <button
                    type="button"
                    @click.stop="customerDropdownOpen = !customerDropdownOpen"
                    :aria-expanded="customerDropdownOpen.toString()"
                    aria-haspopup="true"
                    class="customer-header-profile-button"
                >
                    <span class="customer-header-avatar">
                        <?php if ($customerHeaderProfilePath !== ''): ?>
                            <img
                                src="../<?php echo htmlspecialchars(ltrim($customerHeaderProfilePath, '/\\'), ENT_QUOTES, 'UTF-8'); ?>"
                                alt="Profile photo"
                            >
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($customerHeaderInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="hidden min-w-0 text-left sm:block">
                        <span class="block max-w-[145px] truncate text-[12px] font-bold text-slate-800">
                            <?php echo htmlspecialchars($customerHeaderUsername, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="mt-0.5 block text-[10px] font-medium text-slate-500">My account</span>
                    </span>
                    <i class="fa-solid fa-chevron-down customer-header-chevron" :class="customerDropdownOpen ? 'is-open' : ''" aria-hidden="true"></i>
                </button>

                <div
                    x-cloak
                    x-show="customerDropdownOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-2 scale-[.98]"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-[.98]"
                    class="customer-header-dropdown"
                    role="menu"
                >
                    <div class="customer-header-dropdown-intro">
                        <span class="block truncate text-[13px] font-extrabold text-slate-900"><?php echo htmlspecialchars($customerHeaderUsername, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($customerHeaderEmail !== ''): ?>
                            <span class="mt-1 block break-all text-[11px] text-slate-500"><?php echo htmlspecialchars($customerHeaderEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                            <span class="mt-1 block text-[11px] text-slate-500">Welcome back</span>
                        <?php endif; ?>
                    </div>
                    <div class="customer-header-dropdown-links">
                        <a href="profile.php" role="menuitem"><i class="fa-regular fa-user"></i><span>My Profile</span><i class="fa-solid fa-arrow-up-right-from-square ml-auto"></i></a>
                        <a href="index.php" role="menuitem"><i class="fa-solid fa-store"></i><span>Marketplace</span></a>
                        <a href="messenger.php" role="menuitem"><i class="fa-regular fa-message"></i><span>Messenger</span></a>
                    </div>
                    <div class="customer-header-dropdown-footer">
                        <a href="logout.php" role="menuitem"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Sign out</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBILE / TABLET QUICK LINKS -->
    <div
        x-cloak
        x-show="menuToggle"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="customer-header-mobile-links xl:hidden"
    >
        <a href="index.php"><i class="fa-solid fa-store"></i><span>Marketplace</span></a>
        <a href="index.php#customer-cart"><i class="fa-solid fa-cart-shopping"></i><span>My Cart</span></a>
        <a href="messenger.php"><i class="fa-solid fa-comments"></i><span>Messenger</span></a>
        <a href="profile.php"><i class="fa-regular fa-user"></i><span>My Profile</span></a>
    </div>
</header>
<!-- ===== CUSTOMER MARKETPLACE HEADER END ===== -->

<style>
    [x-cloak] { display: none !important; }

    .customer-market-header {
        position: sticky;
        top: 0;
        z-index: 40 !important;
        color: #172033;
        box-shadow: 0 5px 24px rgba(15, 23, 42, .035);
    }

    /* Keep the customer sidebar above the header and its menus. */
    .futuristic-sidebar { z-index: 99999 !important; }

    .customer-market-header-inner { min-height: 76px; }
    .customer-header-brand { display: inline-flex; text-decoration: none; }
    .customer-header-brand-logo,
    .customer-header-brand-fallback {
        display: inline-flex;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #f0e4c9;
        border-radius: 13px;
        background: #fffaf0;
    }
    .customer-header-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
    .customer-header-brand-fallback {
        color: #9a6b19;
        background: linear-gradient(145deg, #fff8e8, #f8e8c5);
        font-size: 17px;
    }

    .customer-header-icon-button {
        display: inline-flex;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        border: 1px solid #e8edf3;
        border-radius: 13px;
        background: #fff;
        color: #536174;
        cursor: pointer;
        transition: border-color .2s ease, background .2s ease, color .2s ease, transform .2s ease;
    }
    .customer-header-icon-button:hover {
        border-color: #ead7ad;
        background: #fffaf0;
        color: #946817;
        transform: translateY(-1px);
    }

    .customer-header-quicknav { padding: 5px; border: 1px solid #edf0f4; border-radius: 14px; background: #f9fafb; }
    .customer-header-navlink {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        border-radius: 10px;
        color: #647084;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: color .2s ease, background .2s ease;
    }
    .customer-header-navlink i { color: #a77a2c; font-size: 12px; }
    .customer-header-navlink:hover { background: #fff; color: #172033; }

    .customer-header-profile-button {
        display: inline-flex;
        min-height: 48px;
        align-items: center;
        gap: 10px;
        padding: 3px 5px 3px 7px;
        border: 1px solid transparent;
        border-radius: 15px;
        background: transparent;
        cursor: pointer;
        transition: border-color .2s ease, background .2s ease;
    }
    .customer-header-profile-button:hover,
    .customer-header-profile-button[aria-expanded="true"] { border-color: #edf0f4; background: #fafbfc; }
    .customer-header-avatar {
        display: inline-flex;
        width: 39px;
        height: 39px;
        flex: 0 0 39px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 2px solid #fff;
        border-radius: 13px;
        background: linear-gradient(145deg, #f8e9c9, #e9c77e);
        box-shadow: 0 0 0 1px #ead9b8;
        color: #755014;
        font-size: 14px;
        font-weight: 800;
    }
    .customer-header-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .customer-header-chevron { margin-left: 2px; color: #8993a2; font-size: 10px; transition: transform .2s ease; }
    .customer-header-chevron.is-open { transform: rotate(180deg); }

    .customer-header-dropdown {
        position: absolute;
        top: calc(100% + 13px);
        right: 0;
        z-index: 100;
        width: min(300px, calc(100vw - 24px));
        overflow: hidden;
        border: 1px solid #e9edf2;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 20px 55px rgba(15, 23, 42, .14);
    }
    .customer-header-dropdown-intro { padding: 18px 18px 15px; border-bottom: 1px solid #f0f2f5; background: linear-gradient(135deg, #fffdf8, #fff); }
    .customer-header-dropdown-links { display: flex; flex-direction: column; gap: 3px; padding: 9px; }
    .customer-header-dropdown-links a,
    .customer-header-dropdown-footer a {
        display: flex;
        min-height: 42px;
        align-items: center;
        gap: 11px;
        padding: 0 11px;
        border-radius: 11px;
        color: #536174;
        font-size: 12px;
        font-weight: 650;
        text-decoration: none;
        transition: color .18s ease, background .18s ease;
    }
    .customer-header-dropdown-links a > i:first-child { width: 17px; color: #a77a2c; text-align: center; }
    .customer-header-dropdown-links a > i:last-child:not(:first-child) { color: #aab2be; font-size: 10px; }
    .customer-header-dropdown-links a:hover { background: #fff8e9; color: #755014; }
    .customer-header-dropdown-footer { padding: 8px 9px 10px; border-top: 1px solid #f0f2f5; }
    .customer-header-dropdown-footer a { color: #b04444; }
    .customer-header-dropdown-footer a:hover { background: #fff1f1; }

    .customer-header-mobile-links {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        padding: 0 14px 14px;
        border-top: 1px solid #f0f2f5;
        background: #fff;
    }
    .customer-header-mobile-links a {
        display: flex;
        min-height: 43px;
        align-items: center;
        gap: 9px;
        padding: 0 11px;
        border: 1px solid #edf0f4;
        border-radius: 12px;
        background: #fbfcfd;
        color: #536174;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }
    .customer-header-mobile-links a i { width: 16px; color: #a77a2c; text-align: center; }
    .customer-header-mobile-links a:hover { border-color: #ead7ad; background: #fffaf0; color: #755014; }

    @media (max-width: 639px) {
        .customer-market-header-inner { min-height: 66px; gap: 8px; }
        .customer-header-icon-button { width: 39px; height: 39px; flex-basis: 39px; border-radius: 12px; }
        .customer-header-brand-logo, .customer-header-brand-fallback { width: 38px; height: 38px; flex-basis: 38px; border-radius: 12px; }
        .customer-header-profile-button { min-height: 43px; gap: 0; padding: 2px; }
        .customer-header-avatar { width: 37px; height: 37px; flex-basis: 37px; }
        .customer-header-chevron { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .customer-market-header *, .customer-market-header *::before, .customer-market-header *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
    }
</style>
