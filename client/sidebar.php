<?php
/**
 * Customer dashboard sidebar
 * Preserves the existing session/PDO company-logo lookup and shared Alpine
 * sidebarToggle contract used by the customer dashboard layout.
 */
$customerSidebarUserId = $_SESSION['user_id'] ?? null;
$customerSidebarLogoPath = '';

if ($customerSidebarUserId && isset($pdo) && $pdo instanceof PDO) {
    try {
        $customerSidebarUserStmt = $pdo->prepare(
            "SELECT company_id FROM users WHERE id = ? LIMIT 1"
        );
        $customerSidebarUserStmt->execute([$customerSidebarUserId]);
        $customerSidebarUser = $customerSidebarUserStmt->fetch(PDO::FETCH_ASSOC);

        if ($customerSidebarUser && !empty($customerSidebarUser['company_id'])) {
            $customerSidebarCompanyStmt = $pdo->prepare(
                "SELECT company_logo FROM companies WHERE id = ? LIMIT 1"
            );
            $customerSidebarCompanyStmt->execute([$customerSidebarUser['company_id']]);
            $customerSidebarCompany = $customerSidebarCompanyStmt->fetch(PDO::FETCH_ASSOC);

            if ($customerSidebarCompany && !empty($customerSidebarCompany['company_logo'])) {
                $customerSidebarLogoPath = '../uploads/logos/' . $customerSidebarCompany['company_logo'];
            }
        }
    } catch (Throwable $customerSidebarLogoError) {
        // The sidebar remains usable if the optional company logo cannot be loaded.
        $customerSidebarLogoPath = '';
    }
}
?>

<aside
    id="customer-sidebar"
    :class="sidebarToggle ? 'translate-x-0 lg:w-[286px]' : '-translate-x-full lg:translate-x-0 lg:w-[82px]'"
    class="sidebar customer-sidebar fixed inset-y-0 left-0 z-[99999] flex h-screen w-[286px] flex-col overflow-hidden border-r border-slate-200 bg-white transition-all duration-300 ease-in-out lg:static"
    @click.outside="if (window.innerWidth < 1024) sidebarToggle = false"
    @mouseenter="if (window.innerWidth >= 1024) sidebarToggle = true"
    @mouseleave="if (window.innerWidth >= 1024) sidebarToggle = false"
    aria-label="Customer navigation"
>
    <div class="customer-sidebar-head flex min-h-[86px] shrink-0 items-center border-b border-slate-100 px-5 py-4"
         :class="sidebarToggle ? 'justify-start' : 'justify-center lg:px-2'">
        <a href="index.php" class="customer-sidebar-brand flex min-w-0 items-center gap-3" aria-label="Customer dashboard home">
            <?php if ($customerSidebarLogoPath !== ''): ?>
                <img src="<?= htmlspecialchars($customerSidebarLogoPath, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="Company logo" class="customer-sidebar-logo">
            <?php else: ?>
                <span class="customer-sidebar-logo-fallback" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
            <?php endif; ?>
            <span class="customer-sidebar-brand-copy" :class="sidebarToggle ? 'is-visible' : 'lg:hidden'">
                <strong>Customer Hub</strong>
                <small>Your shopping space</small>
            </span>
        </a>
    </div>

    <div class="customer-sidebar-scroll min-h-0 flex-1 overflow-y-auto overflow-x-hidden px-3 py-5">
        <nav x-data="{ selected: '' }">
            <div class="customer-sidebar-section">
                <span :class="sidebarToggle ? '' : 'lg:hidden'">YOUR WORKSPACE</span>
                <i class="fa-solid fa-ellipsis" :class="sidebarToggle ? 'hidden' : 'lg:block'"></i>
            </div>

            <ul class="customer-sidebar-list">
                <li>
                    <a href="index.php" class="customer-sidebar-link" title="Dashboard">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-house"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../marketplace.php" class="customer-sidebar-link" title="Marketplace">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-store"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Marketplace</span>
                        <span class="customer-sidebar-tag" :class="sidebarToggle ? '' : 'lg:hidden'">Explore</span>
                    </a>
                </li>
                <li>
                    <a href="index.php#customer-cart" class="customer-sidebar-link" title="My Cart">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-bag-shopping"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">My Cart</span>
                    </a>
                </li>
                <li>
                    <a href="messenger.php" class="customer-sidebar-link" title="Messenger">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-comments"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Messenger</span>
                    </a>
                </li>
            </ul>

            <div class="customer-sidebar-section customer-sidebar-section-spaced">
                <span :class="sidebarToggle ? '' : 'lg:hidden'">ORDERS & ACTIVITY</span>
                <i class="fa-solid fa-ellipsis" :class="sidebarToggle ? 'hidden' : 'lg:block'"></i>
            </div>
            <ul class="customer-sidebar-list">
                <li>
                    <div class="customer-sidebar-link customer-sidebar-link-disabled" aria-disabled="true" title="Purchase History — page not yet connected">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Purchase History</span>
                        <span class="customer-sidebar-soon" :class="sidebarToggle ? '' : 'lg:hidden'">Soon</span>
                    </div>
                </li>
                <li>
                    <div class="customer-sidebar-link customer-sidebar-link-disabled" aria-disabled="true" title="RFQs & Quotations — tracking page not yet connected">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-file-signature"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">RFQs &amp; Quotations</span>
                        <span class="customer-sidebar-soon" :class="sidebarToggle ? '' : 'lg:hidden'">Soon</span>
                    </div>
                </li>
                <li>
                    <div class="customer-sidebar-link customer-sidebar-link-disabled" aria-disabled="true" title="Order Tracking — page not yet connected">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-truck-fast"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Order Tracking</span>
                        <span class="customer-sidebar-soon" :class="sidebarToggle ? '' : 'lg:hidden'">Soon</span>
                    </div>
                </li>
                <li>
                    <div class="customer-sidebar-link customer-sidebar-link-disabled" aria-disabled="true" title="Saved Items — page not yet connected">
                        <span class="customer-sidebar-icon"><i class="fa-regular fa-heart"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Saved Items</span>
                        <span class="customer-sidebar-soon" :class="sidebarToggle ? '' : 'lg:hidden'">Soon</span>
                    </div>
                </li>
            </ul>

            <div class="customer-sidebar-section customer-sidebar-section-spaced">
                <span :class="sidebarToggle ? '' : 'lg:hidden'">ACCOUNT</span>
                <i class="fa-solid fa-ellipsis" :class="sidebarToggle ? 'hidden' : 'lg:block'"></i>
            </div>
            <ul class="customer-sidebar-list">
                <li>
                    <a href="profile.php" class="customer-sidebar-link" title="My Profile">
                        <span class="customer-sidebar-icon"><i class="fa-solid fa-circle-user"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">My Profile</span>
                    </a>
                </li>
                <li>
                    <div class="customer-sidebar-link customer-sidebar-link-disabled" aria-disabled="true" title="Help & Support — page not yet connected">
                        <span class="customer-sidebar-icon"><i class="fa-regular fa-circle-question"></i></span>
                        <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Help &amp; Support</span>
                        <span class="customer-sidebar-soon" :class="sidebarToggle ? '' : 'lg:hidden'">Soon</span>
                    </div>
                </li>
            </ul>
        </nav>
    </div>

    <div class="customer-sidebar-footer shrink-0 border-t border-slate-100 p-3">
        <a href="logout.php" class="customer-sidebar-link customer-sidebar-logout" title="Sign out">
            <span class="customer-sidebar-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
            <span class="customer-sidebar-label" :class="sidebarToggle ? '' : 'lg:hidden'">Logout</span>
        </a>
        <div class="customer-sidebar-footnote" :class="sidebarToggle ? '' : 'lg:hidden'">
            <i class="fa-solid fa-shield-heart"></i>
            <span>Your account, your marketplace.</span>
        </div>
    </div>
</aside>

<style>
.customer-sidebar {
    --cs-accent: #bd8a2e;
    --cs-accent-soft: #fff6e5;
    --cs-ink: #243247;
    --cs-muted: #718096;
    background: #fff !important;
    border-color: #e9edf3 !important;
    box-shadow: 12px 0 35px rgba(28, 43, 66, .045);
    color: var(--cs-ink);
}
.customer-sidebar-head { background: linear-gradient(135deg, #fff 0%, #fffdf8 100%); }
.customer-sidebar-brand { text-decoration: none; }
.customer-sidebar-logo { display: block; width: 45px; height: 45px; flex: 0 0 45px; object-fit: contain; object-position: center; border-radius: 13px; }
.customer-sidebar-logo-fallback { display: flex; width: 45px; height: 45px; flex: 0 0 45px; align-items: center; justify-content: center; border-radius: 14px; color: #fff; background: linear-gradient(145deg, #d9ad5a, #a87520); box-shadow: 0 8px 20px rgba(189,138,46,.2); font-size: 18px; }
.customer-sidebar-brand-copy { min-width: 0; display: flex; flex-direction: column; gap: 3px; opacity: 1; white-space: nowrap; }
.customer-sidebar-brand-copy strong { color: #26364c; font-size: 14px; font-weight: 800; letter-spacing: -.02em; }
.customer-sidebar-brand-copy small { color: #8b96a7; font-size: 10px; font-weight: 600; letter-spacing: .035em; }
.customer-sidebar-scroll { scrollbar-width: thin; scrollbar-color: #e5eaf0 transparent; }
.customer-sidebar-scroll::-webkit-scrollbar { width: 5px; }
.customer-sidebar-scroll::-webkit-scrollbar-thumb { border-radius: 20px; background: #e5eaf0; }
.customer-sidebar-section { display: flex; min-height: 29px; align-items: center; justify-content: space-between; margin: 1px 10px 9px; color: #a0aaba; font-size: 9px; font-weight: 800; letter-spacing: .15em; white-space: nowrap; }
.customer-sidebar-section i { color: #c3cad4; font-size: 12px; }
.customer-sidebar-section-spaced { margin-top: 25px; }
.customer-sidebar-list { display: flex; flex-direction: column; gap: 5px; margin: 0; padding: 0; list-style: none; }
.customer-sidebar-link { position: relative; display: flex; width: 100%; min-height: 47px; align-items: center; gap: 11px; padding: 6px 9px; border: 1px solid transparent; border-radius: 13px; color: #627187; background: transparent; text-decoration: none; text-align: left; transition: color .2s ease, background .2s ease, border-color .2s ease, transform .2s ease; }
.customer-sidebar-link:hover { color: #34445a; background: #f8fafc; border-color: #edf1f5; transform: translateX(2px); }
.customer-sidebar-icon { display: flex; width: 34px; height: 34px; flex: 0 0 34px; align-items: center; justify-content: center; border-radius: 10px; color: #78879a; background: #f3f6f9; font-size: 14px; transition: color .2s ease, background .2s ease, box-shadow .2s ease; }
.customer-sidebar-link:hover .customer-sidebar-icon { color: #a87924; background: var(--cs-accent-soft); box-shadow: inset 0 0 0 1px rgba(189,138,46,.08); }
.customer-sidebar-label { min-width: 0; overflow: hidden; color: inherit; font-size: 12px; font-weight: 650; letter-spacing: .005em; text-overflow: ellipsis; white-space: nowrap; }
.customer-sidebar-tag { margin-left: auto; padding: 4px 7px; border-radius: 999px; color: #9a6d1e; background: #fff5df; font-size: 9px; font-weight: 800; }
.customer-sidebar-soon { margin-left: auto; padding: 3px 6px; border: 1px solid #edf0f4; border-radius: 999px; color: #98a3b2; background: #fafbfd; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
.customer-sidebar-link-disabled { cursor: default; opacity: .72; }
.customer-sidebar-link-disabled:hover { color: #627187; background: transparent; border-color: transparent; transform: none; }
.customer-sidebar-link-disabled:hover .customer-sidebar-icon { color: #78879a; background: #f3f6f9; box-shadow: none; }
.customer-sidebar-logout { color: #b64d4d; }
.customer-sidebar-logout .customer-sidebar-icon { color: #c45b5b; background: #fff1f0; }
.customer-sidebar-logout:hover { color: #a63838; background: #fff7f6; border-color: #fde7e5; }
.customer-sidebar-logout:hover .customer-sidebar-icon { color: #b83f3f; background: #ffe9e7; }
.customer-sidebar-footnote { display: flex; align-items: center; gap: 7px; padding: 11px 8px 3px; color: #9aa5b3; font-size: 9px; font-weight: 600; white-space: nowrap; }
.customer-sidebar-footnote i { color: #c39a51; font-size: 11px; }

@media (min-width: 1024px) {
    .customer-sidebar.lg\:w-\[82px\] .customer-sidebar-head { justify-content: center !important; padding-right: 8px; padding-left: 8px; }
    .customer-sidebar.lg\:w-\[82px\] .customer-sidebar-brand { justify-content: center; }
    .customer-sidebar.lg\:w-\[82px\] .customer-sidebar-link { justify-content: center; gap: 0; padding-right: 5px; padding-left: 5px; }
    .customer-sidebar.lg\:w-\[82px\] .customer-sidebar-link:hover { transform: none; }
    .customer-sidebar.lg\:w-\[82px\] .customer-sidebar-icon { width: 40px; height: 40px; flex-basis: 40px; }
    .customer-sidebar.lg\:w-\[286px\] .customer-sidebar-logo { width: auto; max-width: 145px; height: 48px; }
}
@media (max-width: 1023px) {
    .customer-sidebar { width: min(286px, 88vw) !important; z-index: 99999 !important; box-shadow: 20px 0 55px rgba(26,39,58,.16); }
    .customer-sidebar-head { justify-content: flex-start !important; padding-right: 20px; padding-left: 20px; }
    .customer-sidebar-logo { width: auto; max-width: 145px; height: 48px; }
    .customer-sidebar-brand-copy { display: flex !important; }
    .customer-sidebar-link { min-height: 49px; }
}
@media (prefers-reduced-motion: reduce) {
    .customer-sidebar *, .customer-sidebar *::before, .customer-sidebar *::after { transition: none !important; }
}
</style>
