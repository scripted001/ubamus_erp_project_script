<!-- ===== Header Start ===== -->

<header
    x-data="{ menuToggle: false }"
    class="sticky top-0 z-40 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900"
>
    <div class="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">

        <!-- =========================================================
             HEADER LEFT / MOBILE CONTROLS
        ========================================================== -->

        <div
            class="flex w-full items-center justify-between gap-2
                   border-b border-gray-200 px-3 py-3
                   sm:gap-4
                   lg:justify-normal
                   lg:border-b-0
                   lg:px-0
                   lg:py-4
                   dark:border-gray-800"
        >

            <!-- =====================================================
                 SIDEBAR TOGGLE
            ====================================================== -->

            <button
                type="button"
                :class="sidebarToggle
                    ? 'lg:bg-transparent dark:lg:bg-transparent bg-gray-100 dark:bg-gray-800'
                    : ''"
                class="relative z-50 flex h-10 w-10 shrink-0
                       items-center justify-center rounded-lg
                       border-gray-200 text-gray-500
                       lg:h-11 lg:w-11 lg:border
                       dark:border-gray-800 dark:text-gray-400"
                @click.stop="sidebarToggle = !sidebarToggle"
                aria-label="Toggle sidebar"
            >

                <!-- DESKTOP HAMBURGER -->

                <svg
                    class="hidden fill-current lg:block"
                    width="16"
                    height="12"
                    viewBox="0 0 16 12"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                        fill=""
                    />
                </svg>

                <!-- MOBILE HAMBURGER -->

                <svg
                    :class="sidebarToggle ? 'hidden' : 'block lg:hidden'"
                    class="fill-current lg:hidden"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M3.25 6C3.25 5.58579 3.58579 5.25 4 5.25L20 5.25C20.4142 5.25 20.75 5.58579 20.75 6C20.75 6.41421 20.75 6.75 20 6.75L4 6.75C3.58579 6.75 3.25 6.41422 3.25 6ZM3.25 18C3.25 17.5858 3.58579 17.25 4 17.25L20 17.25C20.4142 17.25 20.75 17.5858 20.75 18C20.75 18.4142 20.4142 18.75 20.75 18.75L4 18.75C3.58579 18.75 3.25 18.4142 3.25 18ZM4 11.25C3.58579 11.25 3.25 11.58579 3.25 12C3.25 12.4142 3.58579 12.75 4 12.75L12 12.75C12.4142 12.75 12.75 12.4142 12.75 12C12.75 11.5858 12.4142 11.25 12 11.25L4 11.25Z"
                        fill=""
                    />
                </svg>

                <!-- MOBILE CLOSE -->

                <svg
                    :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"
                    class="fill-current"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 18.0721 16.7176L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                        fill=""
                    />
                </svg>

            </button>


            <!-- =====================================================
                 USER / COMPANY DATA
            ====================================================== -->

            <?php

            // Assuming user ID is stored in session
            $userId = $_SESSION['user_id'];

            // Get company_id from the users table
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user) {

                $companyId = $user['company_id'];
                $useremail = $user['email'];

                // Get company logo
                $stmtCompany = $pdo->prepare(
                    "SELECT company_logo FROM companies WHERE id = ?"
                );

                $stmtCompany->execute([$companyId]);

                $company = $stmtCompany->fetch(PDO::FETCH_ASSOC);

                $logoPath = '../uploads/logos/' . $company['company_logo'];

                // Get staff profile
                $stmtCompany = $pdo->prepare(
                    "SELECT * FROM staffs WHERE email = ?"
                );

                $stmtCompany->execute([$useremail]);

                $staff = $stmtCompany->fetch(PDO::FETCH_ASSOC);

                $profilePath = $staff['profile'];
            }

            ?>


            <!-- =====================================================
                 MOBILE COMPANY LOGO
            ====================================================== -->

           

            <!-- =====================================================
                 MOBILE APPLICATION MENU
            ====================================================== -->

            <button
                type="button"
                class="z-50 flex h-10 w-10 shrink-0
                       items-center justify-center rounded-lg
                       text-gray-700
                       transition-all duration-200
                       hover:bg-gray-100
                       hover:text-[#b98d32]
                       lg:hidden
                       dark:text-gray-400
                       dark:hover:bg-gray-800"
                :class="menuToggle
                    ? 'bg-gray-100 text-[#b98d32] dark:bg-gray-800'
                    : ''"
                @click.stop="menuToggle = !menuToggle"
                aria-label="Toggle application menu"
            >

                <svg
                    class="fill-current"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M5.99902 10.4951C6.82745 10.4951 7.49902 11.1667 7.49902 11.9951V12.0051C7.49902 12.8335 6.82745 13.5051 5.99902 13.5051C5.1706 13.5051 4.49902 12.8335 4.49902 12.0051V11.9951C4.49902 11.1667 5.1706 10.4951 5.99902 10.4951ZM17.999 10.4951C18.8275 10.4951 19.499 11.1667 19.499 11.9951V12.0051C19.499 12.8335 18.8275 13.5051 17.999 13.5051C17.1706 13.5051 16.499 12.8335 16.499 12.0051V11.9951C16.499 11.1667 17.1706 10.4951 17.999 10.4951ZM13.499 11.9951C13.499 11.1667 12.8275 10.4951 11.999 10.4951C11.1706 10.4951 10.499 11.1667 10.499 11.9951V12.0051C10.499 12.8335 11.1706 13.5051 11.999 13.5051C12.8275 13.5051 13.499 12.8335 13.499 12.0051V11.9951Z"
                        fill=""
                    />
                </svg>

            </button>

        </div>


        <!-- =========================================================
             RIGHT HEADER AREA
        ========================================================== -->

        <div
            :class="menuToggle ? 'flex' : 'hidden'"
            class="w-full items-center justify-between gap-4
                   border-b border-gray-200
                   px-4 py-3
                   lg:flex lg:justify-end
                   lg:border-b-0
                   lg:px-0
                   lg:py-0
                   dark:border-gray-800"
        >

            <div class="2xsm:gap-3 flex items-center gap-2">
            </div>


            <!-- =====================================================
                 USER AREA
            ====================================================== -->

            <div
                class="relative"
                x-data="{ dropdownOpen: false }"
                @click.outside="dropdownOpen = false"
            >

                <!-- USER TRIGGER -->

                <button
                    type="button"
                    class="flex w-full items-center
                           text-left
                           text-gray-700
                           dark:text-gray-400"
                    @click.prevent="dropdownOpen = !dropdownOpen"
                    :aria-expanded="dropdownOpen"
                    aria-label="Open user menu"
                >

                    <!-- PROFILE IMAGE -->

                    <span
                        class="mr-3 flex h-11 w-11 shrink-0
                               items-center justify-center
                               overflow-hidden rounded-full
                               border-2 border-transparent
                               bg-gray-100
                               transition-all duration-200
                               hover:border-[#d6a84f]
                               hover:shadow-[0_0_14px_rgba(214,168,79,0.30)]
                               dark:bg-gray-800"
                    >

                        <?php if (!empty($profilePath)): ?>

                            <img
                                src="../<?php echo htmlspecialchars($profilePath, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="User profile"
                                class="h-full w-full object-cover"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            />

                            <span
                                class="hidden h-full w-full items-center justify-center
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                <i class="fa-solid fa-user"></i>
                            </span>

                        <?php else: ?>

                            <span
                                class="flex h-full w-full items-center justify-center
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                <i class="fa-solid fa-user"></i>
                            </span>

                        <?php endif; ?>

                    </span>


                    <!-- USERNAME -->

                    <span
                        class="text-theme-sm mr-1 hidden
                               max-w-[150px]
                               truncate
                               font-medium
                               sm:block"
                    >
                        <?php echo htmlspecialchars(
                            $_SESSION['username'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </span>


                    <!-- CHEVRON -->

                    <svg
                        :class="dropdownOpen ? 'rotate-180' : ''"
                        class="shrink-0 stroke-gray-500
                               transition-transform duration-200
                               dark:stroke-gray-400"
                        width="18"
                        height="20"
                        viewBox="0 0 18 20"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M4.3125 8.65625L9 13.3437L13.6875 8.65625"
                            stroke=""
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                </button>


                <!-- =================================================
                     USER DROPDOWN
                ================================================== -->

                <div
                    x-show="dropdownOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                    class="absolute right-0 mt-3
                           flex w-[260px]
                           max-w-[calc(100vw-24px)]
                           flex-col
                           overflow-hidden
                           rounded-2xl
                           border border-gray-200
                           bg-white/95
                           p-3
                           shadow-[0_15px_45px_rgba(0,0,0,0.12)]
                           backdrop-blur-xl
                           dark:border-gray-800
                           dark:bg-gray-900/95"
                >

                    <!-- GOLD ACCENT -->

                    <div
                        class="absolute left-0 top-0 h-full w-[3px]
                               bg-gradient-to-b
                               from-[#d6a84f]
                               via-[#f4d27b]
                               to-[#d6a84f]"
                    ></div>


                    <!-- USER INFORMATION -->

                    <div class="px-3 py-1 pl-4">

                        <span
                            class="text-theme-sm block
                                   truncate
                                   font-medium
                                   text-gray-700
                                   dark:text-gray-300"
                        >
                            <?php echo htmlspecialchars(
                                $_SESSION['username'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </span>

                        <?php if (!empty($useremail)): ?>

                            <span
                                class="mt-0.5 block truncate
                                       text-xs text-gray-500
                                       dark:text-gray-500"
                            >
                                <?php echo htmlspecialchars(
                                    $useremail,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </span>

                        <?php endif; ?>

                    </div>


                    <!-- PROFILE MENU -->

                    <ul
                        class="mt-3 flex flex-col gap-1
                               border-b border-gray-200
                               pt-2 pb-3
                               dark:border-gray-800"
                    >

                        <li>

                            <a
                                href="profile.php"
                                @click="dropdownOpen = false"
                                class="group flex items-center gap-3
                                       rounded-xl px-3 py-2
                                       text-theme-sm font-medium
                                       text-gray-700
                                       transition-all duration-200
                                       hover:bg-[#d6a84f]/10
                                       hover:text-[#a77c27]
                                       dark:text-gray-400
                                       dark:hover:bg-white/5
                                       dark:hover:text-[#f4d27b]"
                            >

                                <span
                                    class="flex h-9 w-9 shrink-0
                                           items-center justify-center
                                           rounded-lg
                                           border border-gray-200
                                           bg-gray-50
                                           transition-all duration-200
                                           group-hover:border-[#d6a84f]/40
                                           group-hover:bg-[#d6a84f]/10
                                           dark:border-gray-800
                                           dark:bg-gray-800"
                                >

                                    <svg
                                        class="fill-gray-500
                                               transition-colors
                                               group-hover:fill-[#b98d32]
                                               dark:fill-gray-400
                                               dark:group-hover:fill-[#f4d27b]"
                                        width="20"
                                        height="20"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            clip-rule="evenodd"
                                            d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.522 22 12 22C6.47715 22 2 17.522 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25ZM8.48126 9.26784C8.48126 7.32499 10.0563 5.75 11.9991 5.75C13.9419 5.75 15.5169 7.32499 15.5169 9.26784C15.5169 11.2107 13.9419 12.7857 11.9991 12.7857C10.0563 12.7857 8.48126 11.2107 8.48126 9.26784Z"
                                            fill=""
                                        />
                                    </svg>

                                </span>

                                <span>
                                    View profile
                                </span>

                            </a>

                        </li>

                    </ul>


                    <!-- LOGOUT -->

                    <a
                        href="logout.php"
                        class="mt-1"
                    >

                        <button
                            type="button"
                            class="group flex w-full items-center gap-3
                                   rounded-xl px-3 py-2
                                   text-theme-sm
                                   font-medium
                                   text-gray-700
                                   transition-all duration-200
                                   hover:bg-red-50
                                   hover:text-red-600
                                   dark:text-gray-400
                                   dark:hover:bg-red-500/10
                                   dark:hover:text-red-400"
                        >

                            <span
                                class="flex h-9 w-9 shrink-0
                                       items-center justify-center
                                       rounded-lg
                                       border border-gray-200
                                       bg-gray-50
                                       transition-all duration-200
                                       group-hover:border-red-200
                                       group-hover:bg-red-50
                                       dark:border-gray-800
                                       dark:bg-gray-800
                                       dark:group-hover:border-red-500/20
                                       dark:group-hover:bg-red-500/10"
                            >

                                <svg
                                    class="fill-gray-500
                                           transition-colors
                                           group-hover:fill-red-500
                                           dark:fill-gray-400
                                           dark:group-hover:fill-red-400"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M15.1007 19.247C14.6865 19.247 14.3507 18.9112 14.3507 18.497L14.3507 14.245H12.8507V18.497C12.8507 19.7396 13.8581 20.747 15.1007 20.747H18.5007C19.7434 20.747 20.7507 19.7392 20.7507 18.497L20.7507 5.49609C20.7507 4.25345 19.7433 3.24609 18.5007 3.24609H15.1007C13.8581 3.24609 12.8507 4.25345 12.8507 5.49609V9.74501L14.3507 9.74501V5.49609C14.3507 5.08188 14.6865 4.74609 15.1007 4.74609L18.5007 4.74609C18.9149 4.74609 19.2507 5.08188 19.2507 5.49609L19.2507 18.497C19.2507 18.9112 18.9149 19.247 18.5007 19.247H15.1007ZM3.25073 11.9984C3.25073 12.2144 3.34204 12.4091 3.48817 12.546L8.09483 17.1556C8.38763 17.4485 8.86251 17.4487 9.15549 17.1559C9.44848 16.8631 9.44863 16.3882 9.15583 16.0952L5.81116 12.7484L16.0007 12.7484C16.4149 12.7484 16.7507 12.4127 16.7507 11.9984C16.7507 11.5842 16.4149 11.2484 16.0007 11.2484L5.81528 11.2484L9.15585 7.90554C9.44864 7.61255 9.44847 7.13767 9.15547 6.84488C8.86248 6.55209 8.3876 6.55226 8.09481 6.84525L3.52309 11.4202C3.35673 11.5577 3.25073 11.7657 3.25073 11.9984Z"
                                        fill=""
                                    />
                                </svg>

                            </span>

                            <span>
                                Sign out
                            </span>

                        </button>

                    </a>

                </div>

            </div>

        </div>

    </div>

</header>


<!-- ================================================================
     HEADER / SIDEBAR STACKING FIX
================================================================ -->

<style>

    /*
     * =============================================================
     * HEADER LAYER
     * =============================================================
     */

    header {
        position: sticky;
        top: 0;
        z-index: 40 !important;
    }


    /*
     * =============================================================
     * MOBILE HEADER
     * =============================================================
     */

    @media (max-width: 1023px) {

        header {
            z-index: 40 !important;
        }

    }


    /*
     * =============================================================
     * FUTURISTIC SIDEBAR
     * =============================================================
     */

    .futuristic-sidebar {
        z-index: 9999 !important;
    }


    /*
     * =============================================================
     * MOBILE DROPDOWN SAFETY
     * =============================================================
     */

    @media (max-width: 420px) {

        header .absolute.right-0 {
            right: -4px;
            width: min(260px, calc(100vw - 24px));
        }

    }


    /*
     * =============================================================
     * FUTURISTIC HEADER ENHANCEMENTS
     * =============================================================
     */

    @media (min-width: 1024px) {

        header {
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

    }


    /*
     * =============================================================
     * REDUCED MOTION
     * =============================================================
     */

    @media (prefers-reduced-motion: reduce) {

        header *,
        header *::before,
        header *::after {
            transition-duration: 0.01ms !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
        }

    }

</style>

<!-- ===== Header End ===== -->