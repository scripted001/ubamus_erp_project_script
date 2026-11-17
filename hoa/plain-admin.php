<?php
    include('../config/db.php');
    include('../config/validation.php');
    include('user_data.php');
    include('../app_data.php');
    include('../company_data.php');


    $company_id = $_SESSION['company_id'];
    $user_email = $_SESSION['user_email'];

    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <title>
             <?php echo $company_name; ?> |  Staff Area
        </title>
            <link rel="stylesheet" href="../fontawesome/css/all.css">
            <link href="style.css" rel="stylesheet"><script data-cfasync="false" nonce="ff1f9a24-18a4-4614-8917-7c27c5c96a86">try{(function(w,d){!function(fv,fw,fx,fy){if(fv.zaraz)console.error("zaraz is loaded twice");else{fv[fx]=fv[fx]||{};fv[fx].executed=[];fv.zaraz={deferred:[],listeners:[]};fv.zaraz._v="5858";fv.zaraz._n="ff1f9a24-18a4-4614-8917-7c27c5c96a86";fv.zaraz.q=[];fv.zaraz._f=function(fz){return async function(){var fA=Array.prototype.slice.call(arguments);fv.zaraz.q.push({m:fz,a:fA})}};for(const fB of["track","set","debug"])fv.zaraz[fB]=fv.zaraz._f(fB);fv.zaraz.init=()=>{var fC=fw.getElementsByTagName(fy)[0],fD=fw.createElement(fy),fE=fw.getElementsByTagName("title")[0];fE&&(fv[fx].t=fw.getElementsByTagName("title")[0].text);fv[fx].x=Math.random();fv[fx].w=fv.screen.width;fv[fx].h=fv.screen.height;fv[fx].j=fv.innerHeight;fv[fx].e=fv.innerWidth;fv[fx].l=fv.location.href;fv[fx].r=fw.referrer;fv[fx].k=fv.screen.colorDepth;fv[fx].n=fw.characterSet;fv[fx].o=(new Date).getTimezoneOffset();if(fv.dataLayer)for(const fF of Object.entries(Object.entries(dataLayer).reduce(((fG,fH)=>({...fG[1],...fH[1]})),{})))zaraz.set(fF[0],fF[1],{scope:"page"});fv[fx].q=[];for(;fv.zaraz.q.length;){const fI=fv.zaraz.q.shift();fv[fx].q.push(fI)}fD.defer=!0;for(const fJ of[localStorage,sessionStorage])Object.keys(fJ||{}).filter((fL=>fL.startsWith("_zaraz_"))).forEach((fK=>{try{fv[fx]["z_"+fK.slice(7)]=JSON.parse(fJ.getItem(fK))}catch{fv[fx]["z_"+fK.slice(7)]=fJ.getItem(fK)}}));fD.referrerPolicy="origin";fD.src="cdn-cgi/zaraz/sd0d9.js?z="+btoa(encodeURIComponent(JSON.stringify(fv[fx])));fC.parentNode.insertBefore(fD,fC)};["complete","interactive"].includes(fw.readyState)?zaraz.init():fv.addEventListener("DOMContentLoaded",zaraz.init)}}(w,d,"zarazData","script");window.zaraz._p=async eC=>new Promise((eD=>{if(eC){eC.e&&eC.e.forEach((eE=>{try{const eF=d.querySelector("script[nonce]"),eG=eF?.nonce||eF?.getAttribute("nonce"),eH=d.createElement("script");eG&&(eH.nonce=eG);eH.innerHTML=eE;eH.onload=()=>{d.head.removeChild(eH)};d.head.appendChild(eH)}catch(eI){console.error(`Error executing script: ${eE}\n`,eI)}}));Promise.allSettled((eC.f||[]).map((eJ=>fetch(eJ[0],eJ[1]))))}eD()}));zaraz._p({"e":["(function(w,d){})(window,document)"]});})(window,document)}catch(e){throw fetch("/cdn-cgi/zaraz/t"),e;};</script></head>

        <body x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }" x-init=" darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{'dark bg-gray-900': darkMode === true}">

        <!-- ===== Page Wrapper Start ===== -->
            <div class="flex h-screen overflow-hidden">
                <?php include('sidebar.php');?>
                <!-- ===== Content Area Start ===== -->
                <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
                    <!-- ===== Main Content Start ===== -->
                    <main>
                        <?php include('header.php'); ?>
                        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                            <div class="col-span-12 space-y-6 xl:col-span-7">

                            </div>
                        </div>
                    </main>
                    <!-- ===== Main Content End ===== -->
                </div>
                <!-- ===== Content Area End ===== -->  
            </div>
            <!-- footer session begins -->
            <?php include('footer.php'); ?>
            <!-- footer session ends -->
            <!-- ===== Page Wrapper End ===== -->
            <script data-cfasync="false" src="cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script defer src="bundle.js"></script><script defer src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" integrity="sha512-ZpsOmlRQV6y907TI0dKBHq9Md29nnaEIPlkf84rnaERnq6zvWvPUqr2ft8M1aS28oN72PdrCzSjY4U6VaAw1EQ==" data-cf-beacon='{"rayId":"95aeba634cdfb6e3","version":"2025.6.2","r":1,"serverTiming":{"name":{"cfExtPri":true,"cfEdge":true,"cfOrigin":true,"cfL4":true,"cfSpeedBrain":true,"cfCacheStatus":true}},"token":"67f7a278e3374824ae6dd92295d38f77","b":1}' crossorigin="anonymous"></script>
    <script>
document.addEventListener('DOMContentLoaded', () => {

    const filters = {
        from: document.getElementById('filterFrom'),
        to: document.getElementById('filterTo'),
        customer: document.getElementById('filterCustomer'),
        user: document.getElementById('filterUser'),
        location: document.getElementById('filterLocation'),
        payment: document.getElementById('filterPayment')
    };

    const rows = document.querySelectorAll('#jobsTableBody tr');
    const cards = document.querySelectorAll('#jobsCards > div');

    function applyFilters() {
        let total = 0;
        let count = 0;

        [...rows, ...cards].forEach(el => {
            const job = JSON.parse(el.dataset.job);
            let visible = true;

            if (filters.customer.value && job.customer_name !== filters.customer.value) visible = false;
            if (filters.user.value && job.user_name !== filters.user.value) visible = false;
            if (filters.location.value && job.location_name !== filters.location.value) visible = false;
            if (filters.payment.value && job.payment_method !== filters.payment.value) visible = false;

            if (filters.from.value && job.job_date < filters.from.value) visible = false;
            if (filters.to.value && job.job_date > filters.to.value) visible = false;

            el.style.display = visible ? '' : 'none';

            if (visible) {
                total += parseFloat(job.grand_total);
                count++;
            }
        });

        document.getElementById('totalJobs').innerText = count;
        document.getElementById('grandTotal').innerText = total.toFixed(2);
    }

    Object.values(filters).forEach(f => f.addEventListener('change', applyFilters));
    applyFilters();
});

function confirmDelete(id) {
    if (!confirm('Are you sure you want to delete this job?')) return;

    window.location.href = 'delete_job.php?id=' + id;
}
</script>

        </body>
    </html>

