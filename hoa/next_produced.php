<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


$companyid = $_SESSION['company_id'];
?>
<!doctype html>
<html lang="en">
<head>

    <meta charset="UTF-8" />

    <meta
        http-equiv="content-type"
        content="text/html;charset=utf-8"
    />

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    />

    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    />

    <title>
        <?php echo htmlspecialchars($company_name); ?> | Staff Area
    </title>

    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">

    <style>

        /* =========================================================
           PRODUCTION TERMINAL
           Presentation only - PHP/database logic untouched
        ========================================================= */

        :root {
            --production-purple: #6839eb;
            --production-purple-light: #8b5cf6;
            --production-purple-soft: rgba(104, 57, 235, .10);
            --production-border: rgba(15, 23, 42, .08);
            --production-text: #111827;
            --production-muted: #64748b;
            --production-bg: #f6f7fb;
            --production-card: rgba(255, 255, 255, .88);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background:
                radial-gradient(
                    circle at 85% 10%,
                    rgba(104, 57, 235, .08),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 10% 90%,
                    rgba(139, 92, 246, .06),
                    transparent 30%
                ),
                var(--production-bg);
        }

        /* =========================================================
           MAIN CONTAINER
        ========================================================= */

        .production-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 18px 0 40px;
        }

        /* =========================================================
           HERO / HEADER
        ========================================================= */

        .production-hero {
            position: relative;
            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

            margin-bottom: 20px;
            padding: 24px 26px;

            border: 1px solid rgba(104, 57, 235, .12);
            border-radius: 22px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.96),
                    rgba(248,247,255,.90)
                );

            box-shadow:
                0 15px 40px rgba(15, 23, 42, .07),
                inset 0 1px 0 rgba(255,255,255,.8);
        }

        .production-hero::before {
            content: "";
            position: absolute;

            width: 180px;
            height: 180px;

            right: -70px;
            top: -90px;

            border-radius: 50%;

            background: rgba(104, 57, 235, .08);
            filter: blur(4px);
        }

        .production-hero-left {
            position: relative;
            z-index: 1;
        }

        .production-eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 7px;

            color: var(--production-purple);

            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .production-eyebrow-dot {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: var(--production-purple);

            box-shadow:
                0 0 0 5px rgba(104,57,235,.10),
                0 0 18px rgba(104,57,235,.35);
        }

        .production-title {
            margin: 0;

            color: #111827;

            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .production-subtitle {
            margin: 8px 0 0;

            max-width: 650px;

            color: var(--production-muted);

            font-size: 14px;
            line-height: 1.6;
        }

        .production-status-badge {
            position: relative;
            z-index: 1;

            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 10px 14px;

            border: 1px solid rgba(104,57,235,.14);
            border-radius: 999px;

            background: rgba(104,57,235,.07);

            color: var(--production-purple);

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .production-status-badge i {
            font-size: 9px;
        }

        /* =========================================================
           MAIN CARD
        ========================================================= */

        .production-card {
            position: relative;

            padding: clamp(18px, 3vw, 32px);

            border: 1px solid var(--production-border);
            border-radius: 24px;

            background: var(--production-card);

            box-shadow:
                0 20px 50px rgba(15, 23, 42, .07),
                inset 0 1px 0 rgba(255,255,255,.85);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        /* =========================================================
           SECTION LABEL
        ========================================================= */

        .production-section-label {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 18px;
        }

        .production-section-icon {
            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex: 0 0 42px;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    rgba(104,57,235,.13),
                    rgba(139,92,246,.06)
                );

            color: var(--production-purple);

            border: 1px solid rgba(104,57,235,.10);
        }

        .production-section-icon i {
            font-size: 17px;
        }

        .production-section-title {
            margin: 0;

            color: #111827;

            font-size: 16px;
            font-weight: 800;
        }

        .production-section-description {
            margin: 3px 0 0;

            color: var(--production-muted);

            font-size: 12px;
        }

        /* =========================================================
           SELECT WRAPPER
        ========================================================= */

        .production-field {
            width: 100%;
        }

        .production-label {
            display: block;

            margin-bottom: 8px;

            color: #334155;

            font-size: 12px;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .production-select-wrapper {
            position: relative;
        }

        .production-select-wrapper::before {
            content: "\f1b2";

            position: absolute;
            left: 17px;
            top: 50%;

            transform: translateY(-50%);

            z-index: 2;

            color: var(--production-purple);

            font-family: "Font Awesome 6 Free";
            font-weight: 900;

            pointer-events: none;
        }

        .production-select-wrapper::after {
            content: "\f107";

            position: absolute;
            right: 17px;
            top: 50%;

            transform: translateY(-50%);

            color: #64748b;

            font-family: "Font Awesome 6 Free";
            font-weight: 900;

            pointer-events: none;
        }

        .production-select {
            width: 100%;
            min-height: 58px;

            padding: 0 48px 0 47px;

            appearance: none;
            -webkit-appearance: none;

            border: 1px solid rgba(15,23,42,.10);
            border-radius: 15px;

            background:
                linear-gradient(
                    180deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.96)
                );

            color: #111827;

            font-size: 14px;
            font-weight: 600;

            outline: none;

            cursor: pointer;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }

        .production-select:hover {
            border-color: rgba(104,57,235,.25);
        }

        .production-select:focus {
            border-color: rgba(104,57,235,.55);

            box-shadow:
                0 0 0 4px rgba(104,57,235,.10),
                0 12px 25px rgba(104,57,235,.06);
        }

        .production-select option {
            color: #111827;
            background: #fff;
        }

        /* =========================================================
           BOTTOM ACTION AREA
        ========================================================= */

        .production-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;

            margin-top: 26px;
            padding-top: 22px;

            border-top: 1px solid rgba(15,23,42,.07);
        }

        .production-action-info {
            display: flex;
            align-items: flex-start;
            gap: 10px;

            color: #64748b;

            font-size: 12px;
            line-height: 1.5;
        }

        .production-action-info i {
            margin-top: 2px;

            color: var(--production-purple);
        }

        .production-next {
            position: relative;

            min-width: 180px;
            min-height: 50px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            padding: 12px 22px;

            border: 0;
            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    #6839eb,
                    #7c4df1
                );

            color: #fff;

            font-size: 13px;
            font-weight: 800;
            letter-spacing: .01em;

            cursor: pointer;

            box-shadow:
                0 10px 24px rgba(104,57,235,.22);

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                filter .2s ease;
        }

        .production-next:hover {
            transform: translateY(-2px);

            box-shadow:
                0 15px 30px rgba(104,57,235,.28);

            filter: brightness(1.03);
        }

        .production-next:active {
            transform: translateY(0);
        }

        .production-next i {
            font-size: 12px;
            transition: transform .2s ease;
        }

        .production-next:hover i {
            transform: translateX(3px);
        }

        /* =========================================================
           RESPONSIVE TABLET
        ========================================================= */

        @media (max-width: 900px) {

            .production-page {
                padding: 14px 0 30px;
            }

            .production-hero {
                padding: 21px;
            }

            .production-card {
                border-radius: 20px;
            }

        }

        /* =========================================================
           RESPONSIVE MOBILE
        ========================================================= */

        @media (max-width: 640px) {

            .production-page {
                width: 100%;
                padding: 8px 0 25px;
            }

            .production-hero {
                display: block;

                margin-bottom: 12px;
                padding: 19px;

                border-radius: 18px;
            }

            .production-title {
                font-size: 25px;
            }

            .production-subtitle {
                font-size: 12px;
                line-height: 1.55;
            }

            .production-status-badge {
                margin-top: 15px;
            }

            .production-card {
                padding: 18px 14px;

                border-radius: 18px;
            }

            .production-section-label {
                margin-bottom: 15px;
            }

            .production-section-icon {
                width: 38px;
                height: 38px;
                flex-basis: 38px;

                border-radius: 11px;
            }

            .production-section-title {
                font-size: 15px;
            }

            .production-select {
                min-height: 55px;

                padding-left: 44px;
                padding-right: 44px;

                font-size: 13px;
            }

            .production-action {
                display: block;

                margin-top: 20px;
                padding-top: 18px;
            }

            .production-action-info {
                margin-bottom: 15px;
            }

            .production-next {
                width: 100%;
                min-height: 52px;
            }

        }

        /* =========================================================
           VERY SMALL PHONES
        ========================================================= */

        @media (max-width: 380px) {

            .production-card {
                padding: 15px 11px;
            }

            .production-hero {
                padding: 16px;
            }

            .production-title {
                font-size: 22px;
            }

            .production-select {
                font-size: 12px;
            }

        }

    </style>

    <script data-cfasync="false" nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86">
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
                                Object.entries(dataLayer).reduce(
                                    ((fG,fH)=>({...fG[1],...fH[1]})),
                                    {}
                                )
                            ))
                                zaraz.set(fF[0],fF[1],{scope:"page"});

                        fv[fx].q=[];

                        for(;fv.zaraz.q.length;){
                            const fI=fv.zaraz.q.shift();
                            fv[fx].q.push(fI)
                        }

                        fD.defer=!0;

                        for(const fJ of[localStorage,sessionStorage])
                            Object.keys(fJ||{})
                                .filter((fL=>fL.startsWith("_zaraz_")))
                                .forEach((fK=>{
                                    try{
                                        fv[fx]["z_"+fK.slice(7)]=JSON.parse(
                                            fJ.getItem(fK)
                                        )
                                    }catch{
                                        fv[fx]["z_"+fK.slice(7)]=fJ.getItem(fK)
                                    }
                                }));

                        fD.referrerPolicy="origin";
                        fD.src="cdn-cgi/zaraz/sd0d9.js?z="+
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
            }(w,d,"zarazData","script");

            window.zaraz._p=async eC=>new Promise((eD=>{
                if(eC){
                    eC.e&&eC.e.forEach((eE=>{
                        try{
                            const eF=d.querySelector("script[nonce]"),
                                eG=eF?.nonce||eF?.getAttribute("nonce"),
                                eH=d.createElement("script");

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
                "e":["(function(w,d){})(window,document)"]
            });

        }catch(e){
            throw fetch("/cdn-cgi/zaraz/t"),e;
        };
    </script>

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
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "

    :class="{'dark bg-gray-900': darkMode === true}"
>

    <!-- =========================================================
         PAGE WRAPPER
    ========================================================== -->

    <div class="flex h-screen overflow-hidden">

        <?php include('sidebar.php'); ?>

        <!-- =====================================================
             CONTENT AREA
        ====================================================== -->

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">

            <main>

                <?php include('header.php'); ?>

                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                    <div class="production-page">

                        <!-- =================================================
                             FUTURISTIC HERO
                        ================================================== -->

                        <section class="production-hero">

                            <div class="production-hero-left">

                                <div class="production-eyebrow">
                                    <span class="production-eyebrow-dot"></span>
                                    Production Intelligence
                                </div>

                                <h1 class="production-title">
                                    Pending Production
                                </h1>

                                <p class="production-subtitle">
                                    Select a completed production batch to
                                    continue the production workflow.
                                </p>

                            </div>

                            <div class="production-status-badge">
                                <i class="fa-solid fa-circle"></i>
                                Production Ready
                            </div>

                        </section>


                        <!-- =================================================
                             FORM
                        ================================================== -->

                        <form
                            method="POST"
                            action="prerelab.php"
                            id="production_form"
                        >

                            <div class="production-card">

                                <!-- =========================================
                                     SECTION HEADER
                                ========================================== -->

                                <div class="production-section-label">

                                    <div class="production-section-icon">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                    </div>

                                    <div>

                                        <h2 class="production-section-title">
                                            Select Production Product
                                        </h2>

                                        <p class="production-section-description">
                                            Choose a finished production item to continue.
                                        </p>

                                    </div>

                                </div>


                                <!-- =========================================
                                     PRODUCT SELECT
                                ========================================== -->

                                <div class="production-field">

                                    <label
                                        for="production_id"
                                        class="production-label"
                                    >
                                        Production Product
                                    </label>

                                    <div class="production-select-wrapper">

                                        <select
                                            name="production_id"
                                            id="production_id"
                                            class="production-select"
                                        >

                                            <option value="">
                                                -- Select Products --
                                            </option>

                                            <?php
                                            $dept = "Production Only";
                                            $status = "Finished";

                                            $stmt = $pdo->prepare("
                                                SELECT p.*
                                                FROM products p
                                                INNER JOIN product_categories pc
                                                    ON p.category_id = pc.id
                                                INNER JOIN stock_production st
                                                    ON p.id = product_id
                                                WHERE p.company_id = ?
                                                  AND pc.dept = ? AND st.status = ?
                                                ORDER BY p.name ASC
                                            ");

                                            $stmt->execute([$companyid, $dept, $status]);

                                            while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="'.$product['id'].'">'.$product['name'].' : '.$product['barcode'].'</option>';
                                            }
                                            ?>

                                        </select>

                                    </div>

                                </div>


                                <!-- =========================================
                                     ACTION
                                ========================================== -->

                                <div class="production-action">

                                    <div class="production-action-info">

                                        <i class="fa-solid fa-circle-info"></i>

                                        <span>
                                            Select a finished product to proceed
                                            to the next production stage.
                                        </span>

                                    </div>

                                    <button
                                        type="submit"
                                        name="submit"
                                        class="production-next"
                                    >

                                        <span>Continue</span>

                                        <i class="fa-solid fa-arrow-right"></i>

                                    </button>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            </main>

        </div>

    </div>


    <!-- =========================================================
         FOOTER
    ========================================================== -->

    <?php include('footer.php'); ?>


    <!-- =========================================================
         EXISTING BUNDLE
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
        data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}'
        crossorigin="anonymous"
    ></script>

</body>
</html>