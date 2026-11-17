
<?php

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

$companyid = $_SESSION['company_id'] ?? 0;

/*
|--------------------------------------------------------------------------
| FETCH SUPPORT EMAIL DETAILS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT company_support_email, support_email_password
    FROM companies
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$companyid]);

$company = $stmt->fetch(PDO::FETCH_ASSOC);

$company_support_email =
    $company['company_support_email'] ?? '';

$support_email_password =
    $company['support_email_password'] ?? '';

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
        <?= htmlspecialchars($company_name) ?> | Staff Area
    </title>


    <!-- =========================================================
         FONT AWESOME
    ========================================================== -->

    <link
        rel="stylesheet"
        href="../fontawesome/css/all.css"
    >


    <!-- =========================================================
         EXISTING STYLES
    ========================================================== -->

    <link
        href="style.css"
        rel="stylesheet"
    >


    <!-- =========================================================
         PAGE RESPONSIVE STYLES
    ========================================================== -->

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .support-page {
            width: 100%;
            max-width: 1536px;
            margin: 0 auto;
            padding: 24px;
            box-sizing: border-box;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .support-page-header {
            width: 100%;
            margin-bottom: 24px;
        }


        .support-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }


        .support-title-wrapper {
            min-width: 0;
            flex: 1;
        }


        .support-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }


        .dark .support-title {
            color: rgba(255,255,255,.92);
        }


        .support-title-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(59,130,246,.10);
            color: #3b82f6;
            font-size: 19px;
        }


        .support-description {
            margin: 10px 0 0 56px;
            max-width: 760px;
            font-size: 14px;
            line-height: 1.6;
            color: #6b7280;
        }


        .dark .support-description {
            color: #9ca3af;
        }


        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .support-breadcrumb {
            margin-top: 16px;
        }


        .support-breadcrumb ol {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            padding: 0;
            margin: 0;
            list-style: none;
        }


        .support-breadcrumb a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
        }


        .support-breadcrumb a:hover {
            color: #3b82f6;
        }


        /* =====================================================
           MAIN CARD
        ===================================================== */

        .support-card {
            width: 100%;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            overflow: hidden;
            box-sizing: border-box;
        }


        .dark .support-card {
            border-color: #1f2937;
            background: rgba(255,255,255,.03);
        }


        /* =====================================================
           CARD HEADER
        ===================================================== */

        .support-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 24px;
            border-bottom: 1px solid #f1f5f9;
        }


        .dark .support-card-header {
            border-color: #1f2937;
        }


        .support-card-header-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 17px;
        }


        .dark .support-card-header-icon {
            background: #1f2937;
            color: #d1d5db;
        }


        .support-card-heading {
            min-width: 0;
        }


        .support-card-heading h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 650;
            color: #1f2937;
        }


        .dark .support-card-heading h3 {
            color: rgba(255,255,255,.92);
        }


        .support-card-heading p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #9ca3af;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .support-form {
            padding: 24px;
        }


        .support-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }


        .support-field {
            width: 100%;
            min-width: 0;
        }


        .support-field-full {
            grid-column: 1 / -1;
        }


        .support-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }


        .dark .support-label {
            color: #d1d5db;
        }


        .support-label i {
            width: 18px;
            text-align: center;
            color: #6b7280;
        }


        .support-input-wrapper {
            position: relative;
            width: 100%;
        }


        .support-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            color: #9ca3af;
            pointer-events: none;
        }


        .support-input {
            width: 100%;
            height: 48px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            padding: 0 45px 0 42px;
            font-size: 14px;
            color: #1f2937;
            outline: none;
            transition: .2s ease;
        }


        .support-input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59,130,246,.10);
        }


        .support-input[readonly] {
            cursor: not-allowed;
            background: #f9fafb;
        }


        .dark .support-input {
            border-color: #374151;
            background: #111827;
            color: rgba(255,255,255,.90);
        }


        .dark .support-input[readonly] {
            background: #111827;
        }


        .support-password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: 0;
            background: transparent;
            border-radius: 7px;
            cursor: pointer;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }


        .support-password-toggle:hover {
            background: #f3f4f6;
            color: #374151;
        }


        .dark .support-password-toggle:hover {
            background: #1f2937;
            color: #e5e7eb;
        }


        /* =====================================================
           SECURITY NOTICE
        ===================================================== */

        .support-security-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }


        .dark .support-security-notice {
            border-color: #374151;
            background: rgba(17,24,39,.65);
        }


        .support-security-icon {
            width: 34px;
            height: 34px;
            min-width: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(34,197,94,.10);
            color: #22c55e;
        }


        .support-security-text {
            min-width: 0;
        }


        .support-security-text strong {
            display: block;
            margin-bottom: 3px;
            font-size: 13px;
            color: #374151;
        }


        .dark .support-security-text strong {
            color: #e5e7eb;
        }


        .support-security-text span {
            display: block;
            font-size: 12px;
            line-height: 1.5;
            color: #6b7280;
        }


        .dark .support-security-text span {
            color: #9ca3af;
        }


        /* =====================================================
           BUTTON AREA
        ===================================================== */

        .support-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid #f1f5f9;
        }


        .dark .support-actions {
            border-color: #1f2937;
        }


        .support-button {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 10px 17px;
            border-radius: 9px;
            border: 0;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-sizing: border-box;
            transition: .2s ease;
        }


        .support-button-primary {
            color: #ffffff;
            background: #3b82f6;
        }


        .support-button-primary:hover {
            background: #2563eb;
        }


        .support-button-secondary {
            color: #374151;
            background: #ffffff;
            border: 1px solid #d1d5db;
        }


        .support-button-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }


        .dark .support-button-secondary {
            color: #d1d5db;
            background: #1f2937;
            border-color: #374151;
        }


        .dark .support-button-secondary:hover {
            background: #374151;
            color: #ffffff;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .support-page {
                padding: 16px;
            }


            .support-title {
                font-size: 21px;
            }


            .support-title-icon {
                width: 40px;
                height: 40px;
                min-width: 40px;
            }


            .support-description {
                margin-left: 0;
                margin-top: 10px;
            }


            .support-card {
                border-radius: 14px;
            }


            .support-card-header {
                padding: 18px;
            }


            .support-form {
                padding: 18px;
            }


            .support-form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }


            .support-field-full {
                grid-column: auto;
            }


            .support-actions {
                align-items: stretch;
                flex-direction: column;
            }


            .support-button {
                width: 100%;
            }

        }


        /* =====================================================
           SMALL PHONES
        ===================================================== */

        @media (max-width: 480px) {

            .support-page {
                padding: 10px;
            }


            .support-page-header {
                margin-bottom: 16px;
            }


            .support-title {
                font-size: 18px;
                gap: 9px;
            }


            .support-title-icon {
                width: 36px;
                height: 36px;
                min-width: 36px;
                border-radius: 9px;
                font-size: 16px;
            }


            .support-description {
                font-size: 13px;
            }


            .support-breadcrumb {
                margin-top: 12px;
            }


            .support-card-header {
                padding: 16px;
                gap: 10px;
            }


            .support-card-header-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
            }


            .support-card-heading h3 {
                font-size: 15px;
            }


            .support-card-heading p {
                font-size: 12px;
            }


            .support-form {
                padding: 16px;
            }


            .support-input {
                height: 46px;
                font-size: 13px;
            }


            .support-security-notice {
                padding: 12px;
            }

        }

    </style>


    <!-- =========================================================
         EXISTING CLOUDFLARE SCRIPT
    ========================================================== -->

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
                            for(const fF of Object.entries(
                                Object.entries(dataLayer)
                                .reduce(
                                    ((fG,fH)=>({...fG[1],...fH[1]})),
                                    {}
                                )
                            ))
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

                        for(const fJ of[
                            localStorage,
                            sessionStorage
                        ])
                            Object.keys(fJ||{})
                            .filter(
                                (fL)=>fL.startsWith("_zaraz_")
                            )
                            .forEach((fK)=>{
                                try{
                                    fv[fx]["z_"+fK.slice(7)]
                                        =JSON.parse(
                                            fJ.getItem(fK)
                                        )
                                }catch{
                                    fv[fx]["z_"+fK.slice(7)]
                                        =fJ.getItem(fK)
                                }
                            });

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
                new Promise((eD)=>{
                    if(eC){
                        eC.e&&eC.e.forEach((eE)=>{
                            try{
                                const eF=
                                    d.querySelector(
                                        "script[nonce]"
                                    );

                                const eG=
                                    eF?.nonce||
                                    eF?.getAttribute("nonce");

                                const eH=
                                    d.createElement("script");

                                eG&&(eH.nonce=eG);

                                eH.innerHTML=eE;

                                eH.onload=()=>{
                                    d.head.removeChild(eH)
                                };

                                d.head.appendChild(eH)

                            }catch(eI){
                                console.error(
                                    `Error executing script: ${eE}\n${eI}`
                                )
                            }
                        });

                        Promise.allSettled(
                            (eC.f||[]).map(
                                (eJ)=>fetch(
                                    eJ[0],
                                    eJ[1]
                                )
                            )
                        )
                    }

                    eD()
                });

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


<!-- =========================================================
     PAGE WRAPPER
========================================================== -->

<div class="flex h-screen overflow-hidden">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include('sidebar.php'); ?>


    <!-- =====================================================
         CONTENT AREA
    ====================================================== -->

    <div
        class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto"
    >


        <!-- =================================================
             MAIN
        ================================================== -->

        <main>


            <!-- =================================================
                 HEADER
            ================================================== -->

            <?php include('header.php'); ?>


            <!-- =================================================
                 PAGE CONTENT
            ================================================== -->

            <div class="support-page">


                <!-- =================================================
                     PAGE HEADER
                ================================================== -->

                <div class="support-page-header">

                    <div class="support-title-row">

                        <div class="support-title-wrapper">

                            <h1 class="support-title">

                                <span class="support-title-icon">
                                    <i class="fa-solid fa-headset"></i>
                                </span>

                                Support Service Email

                            </h1>


                            <p class="support-description">

                                Configure your support service email
                                credentials to enable reliable communication
                                with your users and clients.

                            </p>


                            <!-- Breadcrumb -->

                            <nav class="support-breadcrumb">

                                <ol>

                                    <li>

                                        <a
                                            href="index.php"
                                            class="text-sm text-gray-500 dark:text-gray-400"
                                        >

                                            <i class="fa-solid fa-house"></i>

                                            <span>
                                                Home
                                            </span>

                                        </a>

                                    </li>


                                    <li>

                                        <i
                                            class="fa-solid fa-chevron-right text-xs text-gray-400"
                                        ></i>

                                    </li>


                                    <li
                                        class="text-sm text-gray-800 dark:text-white/90"
                                    >

                                        Support Service Email

                                    </li>

                                </ol>

                            </nav>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     MAIN CARD
                ================================================== -->

                <div class="support-card">


                    <!-- =================================================
                         CARD HEADER
                    ================================================== -->

                    <div class="support-card-header">

                        <div class="support-card-header-icon">

                            <i class="fa-solid fa-envelope-circle-check"></i>

                        </div>


                        <div class="support-card-heading">

                            <h3>
                                Support Email Credentials
                            </h3>

                            <p>
                                Review your configured support mailbox.
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         FORM
                    ================================================== -->

                    <form
                        method="POST"
                        action="edit_support.php"
                        class="support-form"
                    >


                        <div class="support-form-grid">


                            <!-- =================================================
                                 EMAIL
                            ================================================== -->

                            <div class="support-field">

                                <label
                                    for="company_support_email"
                                    class="support-label"
                                >

                                    <i class="fa-solid fa-at"></i>

                                    Support Email

                                </label>


                                <div class="support-input-wrapper">

                                    <i
                                        class="fa-solid fa-envelope support-input-icon"
                                    ></i>


                                    <input
                                        type="email"
                                        id="company_support_email"
                                        name="company_support_email"
                                        value="<?= htmlspecialchars($company_support_email) ?>"
                                        class="support-input"
                                        readonly
                                    >

                                </div>

                            </div>


                            <!-- =================================================
                                 PASSWORD
                            ================================================== -->

                            <div class="support-field">

                                <label
                                    for="support_email_password"
                                    class="support-label"
                                >

                                    <i class="fa-solid fa-lock"></i>

                                    Support Email Password

                                </label>


                                <div class="support-input-wrapper">

                                    <i
                                        class="fa-solid fa-key support-input-icon"
                                    ></i>


                                    <input
                                        type="password"
                                        id="support_email_password"
                                        name="support_email_password"
                                        value="<?= htmlspecialchars($support_email_password) ?>"
                                        class="support-input"
                                        readonly
                                    >


                                    <button
                                        type="button"
                                        class="support-password-toggle"
                                        id="toggleSupportPassword"
                                        aria-label="Show password"
                                    >

                                        <i
                                            class="fa-solid fa-eye"
                                            id="supportPasswordIcon"
                                        ></i>

                                    </button>

                                </div>

                            </div>


                        </div>


                        <!-- =================================================
                             SECURITY NOTICE
                        ================================================== -->

                        <div class="support-security-notice">

                            <div class="support-security-icon">

                                <i class="fa-solid fa-shield-halved"></i>

                            </div>


                            <div class="support-security-text">

                                <strong>
                                    Support account information
                                </strong>

                                <span>
                                    These credentials are currently
                                    configured for your company's support
                                    communication service.
                                </span>

                            </div>

                        </div>


                        <!-- =================================================
                             HIDDEN COMPANY ID
                        ================================================== -->

                        <input
                            type="hidden"
                            name="company_id"
                            value="<?= htmlspecialchars($_SESSION['company_id']) ?>"
                        >


                        <!-- =================================================
                             ACTIONS
                        ================================================== -->

                        <div class="support-actions">


                            <button
                                type="submit"
                                class="support-button support-button-primary"
                            >

                                <i class="fa-solid fa-pen-to-square"></i>

                                <span>
                                    Edit / Update
                                </span>

                            </button>


                            <a
                                href="categories.php"
                                class="support-button support-button-secondary"
                            >

                                <i class="fa-solid fa-arrow-left"></i>

                                <span>
                                    Cancel
                                </span>

                            </a>


                        </div>


                    </form>

                </div>

            </div>

        </main>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <?php include('footer.php'); ?>


    </div>

</div>


<!-- =========================================================
     EXISTING SCRIPTS
========================================================== -->

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
                "cfSpeedBrain":true,
                "cfCacheStatus":true
            }
        },
        "token":"67f7a278e3374824ae6dd92295d38f77",
        "b":1
    }'
    crossorigin="anonymous"
></script>


<!-- =========================================================
     PASSWORD VISIBILITY
========================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const passwordInput =
            document.getElementById(
                'support_email_password'
            );

        const toggleButton =
            document.getElementById(
                'toggleSupportPassword'
            );

        const passwordIcon =
            document.getElementById(
                'supportPasswordIcon'
            );


        if (
            !passwordInput ||
            !toggleButton ||
            !passwordIcon
        ) {
            return;
        }


        toggleButton.addEventListener(
            'click',
            function () {

                if (
                    passwordInput.type === 'password'
                ) {

                    passwordInput.type = 'text';

                    passwordIcon.classList.remove(
                        'fa-eye'
                    );

                    passwordIcon.classList.add(
                        'fa-eye-slash'
                    );

                    toggleButton.setAttribute(
                        'aria-label',
                        'Hide password'
                    );

                } else {

                    passwordInput.type = 'password';

                    passwordIcon.classList.remove(
                        'fa-eye-slash'
                    );

                    passwordIcon.classList.add(
                        'fa-eye'
                    );

                    toggleButton.setAttribute(
                        'aria-label',
                        'Show password'
                    );

                }

            }
        );

    }
);

</script>


</body>

</html>

