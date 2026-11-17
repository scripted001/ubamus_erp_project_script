
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED COMPANY
|--------------------------------------------------------------------------
*/

$companyid = (int) ($_SESSION['company_id'] ?? 0);

if ($companyid <= 0) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET CURRENT CUSTOMER SERVICE EMAIL DATA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        company_customer_care,
        customer_care_password
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$companyid]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header("Location: customer_service_email.php");
    exit;
}

$company_customer_care = $company['company_customer_care'] ?? '';
$customer_care_password = $company['customer_care_password'] ?? '';

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
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >

    <title>
        <?= htmlspecialchars($company_name ?? 'Company'); ?> | Staff Area
    </title>

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >

    <link
        href="style.css"
        rel="stylesheet"
    >

    <script
        data-cfasync="false"
        nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86"
    >
        try {
            (function(w,d){
                !function(fv,fw,fx,fy){
                    if(fv.zaraz)
                        console.error("zaraz is loaded twice");
                    else {
                        fv[fx]=fv[fx]||{};
                        fv[fx].executed=[];
                        fv.zaraz={
                            deferred:[],
                            listeners:[]
                        };
                        fv.zaraz._v="5858";
                        fv.zaraz._n="ff1f9a24-18a4-4614-8917-7c27c5c96a86";
                        fv.zaraz.q=[];

                        fv.zaraz._f=function(fz){
                            return async function(){
                                var fA=Array.prototype.slice.call(arguments);
                                fv.zaraz.q.push({
                                    m:fz,
                                    a:fA
                                })
                            }
                        };

                        for(const fB of["track","set","debug"])
                            fv.zaraz[fB]=fv.zaraz._f(fB);

                        fv.zaraz.init=()=>{
                            var fC=fw.getElementsByTagName(fy)[0],
                                fD=fw.createElement(fy),
                                fE=fw.getElementsByTagName("title")[0];

                            fE&&(fv[fx].t=fw.getElementsByTagName("title")[0].text);
                            fv[fx].x=Math.random();
                            fv[fx].w=fv.screen.width;
                            fv[fx].h=fv.screen.height;
                            fv[fx].j=fv.innerHeight;
                            fv[fx].e=fv.innerWidth;
                            fv[fx].l=fv.location.href;
                            fv[fx].r=fw.referrer;
                            fv[fx].k=fv.screen.colorDepth;
                            fv[fx].n=fw.characterSet;
                            fv[fx].o=(new Date).getTimezoneOffset();

                            if(fv.dataLayer)
                                for(
                                    const fF of Object.entries(
                                        Object.entries(dataLayer).reduce(
                                            ((fG,fH)=>({...fG[1],...fH[1]})),
                                            {}
                                        )
                                    )
                                )
                                    zaraz.set(
                                        fF[0],
                                        fF[1],
                                        {scope:"page"}
                                    );

                            fv[fx].q=[];

                            for(;fv.zaraz.q.length;){
                                const fI=fv.zaraz.q.shift();
                                fv[fx].q.push(fI)
                            }

                            fD.defer=!0;

                            for(
                                const fJ of[localStorage,sessionStorage]
                            )
                                Object.keys(fJ||{})
                                    .filter(
                                        (fL=>fL.startsWith("_zaraz_"))
                                    )
                                    .forEach((fK=>{
                                        try{
                                            fv[fx]["z_"+fK.slice(7)]
                                                =JSON.parse(
                                                    fJ.getItem(fK)
                                                )
                                        }catch{
                                            fv[fx]["z_"+fK.slice(7)]
                                                =fJ.getItem(fK)
                                        }
                                    }));

                            fD.referrerPolicy="origin";

                            fD.src=
                                "cdn-cgi/zaraz/sd0d9.js?z="+
                                btoa(
                                    encodeURIComponent(
                                        JSON.stringify(fv[fx])
                                    )
                                );

                            fC.parentNode.insertBefore(fD,fC)
                        };

                        ["complete","interactive"].includes(fw.readyState)
                            ?zaraz.init()
                            :fv.addEventListener(
                                "DOMContentLoaded",
                                zaraz.init
                            )
                    }
                }(
                    w,
                    d,
                    "zarazData",
                    "script"
                );

                window.zaraz._p=async eC=>
                    new Promise((eD=>{
                        if(eC){
                            eC.e&&eC.e.forEach((eE=>{
                                try{
                                    const eF=
                                        d.querySelector("script[nonce]");

                                    const eG=
                                        eF?.nonce||
                                        eF?.getAttribute("nonce");

                                    const eH=
                                        d.createElement(fy);

                                    eG&&(eH.nonce=eG);

                                    eH.innerHTML=eE;

                                    eH.onload=()=>{
                                        d.head.removeChild(eH)
                                    };

                                    d.head.appendChild(eH)

                                }catch(eI){
                                    console.error(
                                        `Error executing script: ${eE}\n`,
                                        eI
                                    )
                                }
                            }));

                            Promise.allSettled(
                                (eC.f||[]).map(
                                    (eJ=>fetch(eJ[0],eJ[1]))
                                )
                            )
                        }

                        eD()
                    }));

                zaraz._p({
                    "e":[
                        "(function(w,d){})(window,document)"
                    ]
                });

            })(window,document)
        }catch(e){
            throw fetch("/cdn-cgi/zaraz/t"),e;
        };
    </script>

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
        darkMode = JSON.parse(localStorage.getItem('darkMode'));
        $watch(
            'darkMode',
            value => localStorage.setItem(
                'darkMode',
                JSON.stringify(value)
            )
        )
    "
    :class="{
        'dark bg-gray-900': darkMode === true
    }"
>

    <!-- ===== Page Wrapper Start ===== -->

    <div class="flex h-screen overflow-hidden">

        <?php include('sidebar.php'); ?>

        <!-- ===== Content Area Start ===== -->

        <div
            class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
        >

            <!-- ===== Main Content Start ===== -->

            <main>

                <?php include('header.php'); ?>

                <div
                    class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6"
                >

                    <div class="grid grid-cols-1 gap-4 md:gap-6">

                        <!-- ===== Breadcrumb Start ===== -->

                        <div x-data="{ pageName: '' }">

                            <div
                                class="flex flex-wrap items-center justify-between gap-3 mb-6"
                            >

                                <div class="min-w-0">

                                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                                        Edit Customer Service Email Data
                                    </h3>

                                    <i class="p-talk block mt-2">
                                        Configure your customer support email
                                        credentials to enable reliable
                                        communication with your customers.
                                    </i>

                                </div>

                                <nav class="w-full">

                                    <ol
                                        class="flex flex-wrap items-center gap-1.5"
                                    >

                                        <li>

                                            <a
                                                class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                                                href="index.php"
                                            >

                                                Home

                                                <svg
                                                    class="stroke-current"
                                                    width="17"
                                                    height="16"
                                                    viewBox="0 0 17 16"
                                                    fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >

                                                    <path
                                                        d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                                                        stroke=""
                                                        stroke-width="1.2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />

                                                </svg>

                                            </a>

                                        </li>

                                        <li
                                            class="text-sm text-gray-800 dark:text-white/90"
                                            x-text="pageName"
                                        ></li>

                                    </ol>

                                </nav>

                            </div>

                        </div>

                        <!-- ===== Breadcrumb End ===== -->


                        <!-- ===== Form Card Start ===== -->

                        <div
                            class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
                        >

                            <div
                                class="border-t border-gray-100 p-4 sm:p-6 dark:border-gray-800"
                            >

                                <form
                                    method="POST"
                                    action="proceed_edit_customer_care.php"
                                >

                                    <div
                                        class="grid grid-cols-1 gap-5 xl:grid-cols-2"
                                    >

                                        <!-- Email -->

                                        <div class="w-full">

                                            <label
                                                for="company_customer_care"
                                                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                                            >
                                                New Email
                                            </label>

                                            <input
                                                type="email"
                                                id="company_customer_care"
                                                name="company_customer_care"
                                                value="<?= htmlspecialchars(
                                                    $company_customer_care,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>"
                                                autocomplete="email"
                                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                                required
                                            >

                                        </div>


                                        <!-- Password -->

                                        <div class="w-full">

                                            <label
                                                for="customer_care_password"
                                                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                                            >
                                                New Password
                                            </label>

                                            <input
                                                type="text"
                                                id="customer_care_password"
                                                name="customer_care_password"
                                                value="<?= htmlspecialchars(
                                                    $customer_care_password,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>"
                                                autocomplete="off"
                                                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                                required
                                            >

                                        </div>


                                        <!-- Hidden Company ID -->

                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?= $companyid; ?>"
                                        >


                                        <!-- Buttons -->

                                        <div
                                            class="w-full xl:col-span-2"
                                        >

                                            <div
                                                class="flex flex-col gap-3 sm:flex-row sm:items-center"
                                            >

                                                <button
                                                    type="submit"
                                                    class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white transition hover:bg-brand-600 focus:outline-none focus:ring-3 focus:ring-brand-500/20"
                                                >

                                                    <i class="fa-solid fa-paper-plane"></i>

                                                    Update

                                                </button>


                                                <a
                                                    href="customer_service_email.php"
                                                    class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                                                >

                                                    <i class="fa-solid fa-arrow-left"></i>

                                                    Cancel

                                                </a>

                                            </div>

                                        </div>

                                    </div>

                                </form>

                            </div>

                        </div>

                        <!-- ===== Form Card End ===== -->

                    </div>

                </div>

            </main>

            <!-- ===== Main Content End ===== -->

        </div>

        <!-- ===== Content Area End ===== -->

    </div>

    <!-- ===== Page Wrapper End ===== -->


    <!-- ===== Footer Start ===== -->

    <?php include('footer.php'); ?>

    <!-- ===== Footer End ===== -->


    <script
        data-cfasync="false"
        src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"
    ></script>

    <script
        defer
        src="bundle.js"
    ></script>

    <script
        defer
        src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015"
        integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ=="
        data-cf-beacon='{
            "rayId":"95aeba634cdfb6e3",
            "version":"2025.6.2",
            "r":1,
            "serverTiming":{
                "name":{
                    "cfExtPri":true,
                    "cfEdge":true,
                    "cfOrigin":true,
                    "cfL4":true,
                    "cfSpeedBrain":true,
                    "cfCacheStatus":true
                }
            },
            "token":"67f7a278e3374824ae6dd92295d38f77",
            "b":1
        }'
        crossorigin="anonymous"
    ></script>

</body>

</html>

