<?php
include('../config/db.php');
include('../app_data.php');
include('../company_data.php');

/* =========================================================
   ACTIVE PRIVACY POLICY + TERMS & CONDITIONS
========================================================= */

$legal_company_id = (int)($company_id ?? 0);

$privacyPolicy = null;
$termsConditions = null;

try {

    if ($legal_company_id > 0) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM privacy_policy
            WHERE company_id = ?
            AND LOWER(status) = 'active'
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([$legal_company_id]);

        $privacyPolicy =
            $stmt->fetch(PDO::FETCH_ASSOC) ?: null;


        $stmt = $pdo->prepare("
            SELECT *
            FROM terms_conditions
            WHERE company_id = ?
            AND LOWER(status) = 'active'
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([$legal_company_id]);

        $termsConditions =
            $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    }

} catch (PDOException $e) {

    $privacyPolicy = null;
    $termsConditions = null;

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - <?= htmlspecialchars($company_name ?? 'Company'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        *{
            box-sizing:border-box;
            margin:0;
            padding:0
        }

        :root{
            --bg:#f4f7fb;
            --panel:rgba(255,255,255,.86);
            --line:rgba(15,23,42,.09);
            --line-strong:rgba(15,23,42,.16);
            --text:#172033;
            --muted:#667085;
            --soft:#8a94a6;
            --primary:#4f46e5;
            --primary-2:#7c3aed;
            --cyan:#0891b2;
            --green:#10b981;
            --danger:#e11d48;
            --shadow:0 24px 70px rgba(15,23,42,.10);
            --radius:26px;
        }

        html{
            scroll-behavior:smooth
        }

        body{
            min-height:100vh;
            color:var(--text);
            font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;

            background:
                radial-gradient(circle at 8% 5%,rgba(79,70,229,.10),transparent 28%),
                radial-gradient(circle at 92% 8%,rgba(8,145,178,.09),transparent 25%),
                radial-gradient(circle at 78% 92%,rgba(124,58,237,.08),transparent 28%),
                linear-gradient(135deg,#f8fafc 0%,#eef2f7 100%);

            overflow-x:hidden;
        }

        body:before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            z-index:0;

            background-image:
                linear-gradient(rgba(15,23,42,.025) 1px,transparent 1px),
                linear-gradient(90deg,rgba(15,23,42,.025) 1px,transparent 1px);

            background-size:32px 32px;

            mask-image:linear-gradient(
                to bottom,
                rgba(0,0,0,.7),
                transparent 85%
            );
        }

        button,
        input,
        select,
        textarea{
            font:inherit;
        }

        button{
            border:0;
        }

        .orb{
            position:fixed;
            border-radius:50%;
            filter:blur(5px);
            pointer-events:none;
            z-index:0;
        }

        .orb.a{
            width:360px;
            height:360px;
            left:-190px;
            top:18%;
            background:rgba(79,70,229,.08);
        }

        .orb.b{
            width:300px;
            height:300px;
            right:-150px;
            bottom:10%;
            background:rgba(8,145,178,.07);
        }

        .app{
            position:relative;
            z-index:1;
            min-height:100vh;

            display:grid;
            grid-template-columns:390px minmax(0,1fr);
        }

        /* =========================================================
           BRAND PANEL
        ========================================================= */

        .brand-panel{
            min-height:100vh;
            padding:38px 32px;

            position:sticky;
            top:0;

            background:rgba(255,255,255,.74);

            border-right:1px solid var(--line);

            box-shadow:8px 0 40px rgba(15,23,42,.035);

            backdrop-filter:blur(24px);
            -webkit-backdrop-filter:blur(24px);

            display:flex;
            flex-direction:column;
            justify-content:space-between;

            overflow:hidden;
        }

        .brand-panel:after{
            content:"";
            position:absolute;

            width:320px;
            height:320px;

            border-radius:50%;

            right:-180px;
            top:100px;

            background:rgba(79,70,229,.07);

            filter:blur(30px);
        }

        .brand{
            display:flex;
            align-items:center;
            gap:13px;

            position:relative;
            z-index:1;
        }

        .brand-mark{
            width:48px;
            height:48px;

            border-radius:15px;

            display:grid;
            place-items:center;

            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--primary-2)
                );

            color:#fff;

            box-shadow:
                0 13px 30px rgba(79,70,229,.22);

            font-size:18px;
        }

        .brand-copy strong{
            display:block;

            font-family:Manrope,sans-serif;

            font-size:16px;

            letter-spacing:-.3px;

            color:#182033;
        }

        .brand-copy span{
            display:block;

            color:#8a94a6;

            font-size:10px;

            margin-top:3px;
        }

        .hero{
            position:relative;
            z-index:1;

            margin-top:80px;
        }

        .eyebrow{
            display:inline-flex;
            align-items:center;
            gap:8px;

            padding:8px 11px;

            border:1px solid rgba(79,70,229,.16);

            background:rgba(79,70,229,.07);

            color:#5146b8;

            border-radius:999px;

            font-size:10px;

            font-weight:800;

            text-transform:uppercase;

            letter-spacing:.08em;
        }

        .hero h1{
            font-family:Manrope,sans-serif;

            font-size:43px;

            line-height:1.04;

            letter-spacing:-2px;

            margin-top:22px;

            color:#172033;
        }

        .hero h1 span{
            background:
                linear-gradient(
                    90deg,
                    #4f46e5,
                    #7c3aed 55%,
                    #0891b2
                );

            -webkit-background-clip:text;
            background-clip:text;

            color:transparent;
        }

        .hero p{
            color:#687386;

            line-height:1.75;

            font-size:13px;

            margin-top:20px;

            max-width:320px;
        }

        .signal{
            margin-top:30px;

            padding:15px;

            border:1px solid var(--line);

            border-radius:18px;

            background:rgba(255,255,255,.76);

            display:flex;

            gap:13px;

            align-items:flex-start;

            box-shadow:
                0 12px 30px rgba(15,23,42,.035);
        }

        .signal i{
            color:var(--green);

            margin-top:2px;
        }

        .signal strong{
            font-size:11px;

            color:#263047;
        }

        .signal span{
            display:block;

            color:#7b8699;

            font-size:10px;

            line-height:1.55;

            margin-top:4px;
        }

        .side-footer{
            position:relative;
            z-index:1;

            color:#8993a5;

            font-size:10px;

            line-height:1.6;
        }

        .side-footer .status{
            display:flex;
            align-items:center;

            gap:8px;

            margin-bottom:8px;

            color:#556176;
        }

        .dot{
            width:7px;
            height:7px;

            border-radius:50%;

            background:var(--green);

            box-shadow:
                0 0 0 5px rgba(16,185,129,.10);
        }

        /* =========================================================
           WORKSPACE
        ========================================================= */

        .workspace{
            padding:34px 42px 65px;

            min-width:0;
        }

        .workspace-inner{
            max-width:1020px;

            margin:0 auto;
        }

        .topbar{
            display:flex;

            align-items:center;

            justify-content:space-between;

            gap:18px;

            margin-bottom:20px;
        }

        .topbar h2{
            font-family:Manrope,sans-serif;

            font-size:25px;

            letter-spacing:-.8px;

            color:#182033;
        }

        .topbar p{
            color:var(--muted);

            font-size:11px;

            margin-top:5px;
        }

        .secure{
            display:flex;

            align-items:center;

            gap:8px;

            padding:9px 12px;

            border:1px solid var(--line);

            border-radius:999px;

            background:rgba(255,255,255,.76);

            color:#667085;

            font-size:10px;

            white-space:nowrap;

            box-shadow:
                0 8px 24px rgba(15,23,42,.035);
        }

        .glass{
            background:var(--panel);

            border:1px solid rgba(255,255,255,.9);

            box-shadow:var(--shadow);

            backdrop-filter:blur(24px);
            -webkit-backdrop-filter:blur(24px);

            border-radius:var(--radius);
        }

        /* =========================================================
           PROGRESS
        ========================================================= */

        .progress-card{
            padding:17px 20px;

            margin-bottom:12px;
        }

        .progress-head{
            display:flex;

            align-items:center;

            justify-content:space-between;

            margin-bottom:10px;
        }

        .progress-head span:first-child{
            font-size:10px;

            color:#7a8598;

            text-transform:uppercase;

            letter-spacing:.08em;

            font-weight:800;
        }

        #progressPercent{
            font-size:11px;

            color:#5146b8;

            font-weight:800;
        }

        .progress-track{
            height:8px;

            border-radius:999px;

            background:#e9edf3;

            overflow:hidden;
        }

        .progress-bar{
            height:100%;

            width:33.33%;

            border-radius:inherit;

            background:
                linear-gradient(
                    90deg,
                    var(--primary),
                    var(--primary-2),
                    var(--cyan)
                );

            box-shadow:
                0 0 16px rgba(79,70,229,.20);

            transition:width .35s ease;
        }

        /* =========================================================
           STEPS
        ========================================================= */

        .steps{
            display:grid;

            grid-template-columns:
                repeat(3,1fr);

            gap:10px;

            margin-bottom:12px;
        }

        .step{
            min-width:0;

            padding:12px 14px;

            border:1px solid var(--line);

            border-radius:17px;

            background:rgba(255,255,255,.68);

            display:flex;

            align-items:center;

            gap:11px;

            cursor:pointer;

            transition:.22s ease;

            box-shadow:
                0 6px 20px rgba(15,23,42,.025);
        }

        .step:hover{
            transform:translateY(-2px);

            border-color:var(--line-strong);

            background:#fff;
        }

        .step-number{
            width:35px;
            height:35px;

            border-radius:11px;

            display:grid;
            place-items:center;

            background:#f0f2f6;

            color:#7b8699;

            font-weight:800;

            font-size:11px;

            flex:0 0 auto;
        }

        .step-text{
            min-width:0;
        }

        .step-text strong{
            display:block;

            font-size:11px;

            color:#263047;
        }

        .step-text span{
            display:block;

            color:#8a94a6;

            font-size:9px;

            margin-top:3px;

            white-space:nowrap;

            overflow:hidden;

            text-overflow:ellipsis;
        }

        .step.active{
            border-color:rgba(79,70,229,.25);

            background:
                linear-gradient(
                    135deg,
                    rgba(79,70,229,.075),
                    rgba(124,58,237,.035)
                );
        }

        .step.active .step-number{
            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--primary-2)
                );

            color:#fff;

            box-shadow:
                0 8px 18px rgba(79,70,229,.20);
        }

        .step.complete .step-number{
            background:rgba(16,185,129,.10);

            color:#059669;

            border:1px solid rgba(16,185,129,.18);
        }

        /* =========================================================
           FORM
        ========================================================= */

        .form-section{
            display:none;

            padding:28px;
        }

        .form-section.active{
            display:block;

            animation:in .32s ease;
        }

        @keyframes in{
            from{
                opacity:0;
                transform:translateY(8px)
            }

            to{
                opacity:1;
                transform:none
            }
        }

        .section-top{
            display:flex;

            align-items:flex-start;

            justify-content:space-between;

            gap:15px;

            padding-bottom:21px;

            margin-bottom:24px;

            border-bottom:1px solid var(--line);
        }

        .section-title{
            display:flex;

            gap:13px;
        }

        .section-icon{
            width:44px;
            height:44px;

            border-radius:14px;

            display:grid;
            place-items:center;

            background:rgba(79,70,229,.08);

            border:1px solid rgba(79,70,229,.12);

            color:#5b52c7;
        }

        .section-title h3{
            font-family:Manrope,sans-serif;

            font-size:18px;

            letter-spacing:-.4px;

            color:#202a3f;
        }

        .section-title p{
            color:#7d8799;

            font-size:10px;

            line-height:1.6;

            margin-top:5px;
        }

        .step-chip{
            font-size:9px;

            font-weight:800;

            letter-spacing:.08em;

            color:#7b8699;

            padding:7px 10px;

            border-radius:999px;

            background:#f6f7f9;

            border:1px solid var(--line);

            white-space:nowrap;
        }

        .form-grid{
            display:grid;

            grid-template-columns:
                repeat(2,minmax(0,1fr));

            gap:18px;
        }

        .field.full{
            grid-column:1/-1;
        }

        .field label{
            display:flex;

            align-items:center;

            justify-content:space-between;

            gap:8px;

            margin-bottom:8px;

            color:#4b566b;

            font-size:10px;

            font-weight:800;
        }

        .required{
            color:var(--danger);

            font-size:11px;
        }

        .input-wrap{
            position:relative;
        }

        .input-icon{
            position:absolute;

            left:14px;

            top:50%;

            transform:translateY(-50%);

            color:#9aa4b4;

            font-size:12px;

            z-index:2;

            pointer-events:none;
        }

        .field input,
        .field select,
        .field textarea{
            width:100%;

            outline:none;

            border:1px solid #dfe4eb;

            border-radius:14px;

            color:#263047;

            background:rgba(255,255,255,.92);

            transition:.2s ease;

            box-shadow:
                0 3px 10px rgba(15,23,42,.025);
        }

        .field input,
        .field select{
            height:48px;

            padding:0 14px 0 40px;
        }

        .field textarea{
            min-height:110px;

            padding:14px;

            resize:vertical;
        }

        .field input::placeholder,
        .field textarea::placeholder{
            color:#a7afbc;
        }

        .field input:hover,
        .field select:hover,
        .field textarea:hover{
            border-color:#c7ced8;

            background:#fff;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus{
            border-color:rgba(79,70,229,.55);

            background:#fff;

            box-shadow:
                0 0 0 4px rgba(79,70,229,.08),
                0 6px 18px rgba(15,23,42,.04);
        }

        .field input.is-valid,
        .field select.is-valid,
        .field textarea.is-valid{
            border-color:rgba(16,185,129,.50);
        }

        .field input.is-invalid,
        .field select.is-invalid,
        .field textarea.is-invalid{
            border-color:rgba(225,29,72,.55);

            box-shadow:
                0 0 0 3px rgba(225,29,72,.06);
        }

        .field-hint{
            display:flex;

            align-items:center;

            gap:6px;

            color:#8a94a6;

            font-size:9px;

            margin-top:6px;
        }

        .field-hint i{
            font-size:8px;
        }

        .slug-preview{
            margin-top:7px;

            color:#8a94a6;

            font-size:9px;
        }

        .slug-preview b{
            color:#5146b8;

            font-weight:700;
        }

        /* =========================================================
           UPLOAD
        ========================================================= */

        .upload{
            border:1px dashed rgba(79,70,229,.27);

            border-radius:18px;

            padding:15px;

            background:rgba(79,70,229,.025);

            transition:.2s ease;
        }

        .upload.dragging{
            border-color:var(--cyan);

            background:rgba(8,145,178,.06);

            transform:scale(1.01);
        }

        .upload-main{
            display:flex;

            align-items:center;

            gap:12px;
        }

        .upload-icon{
            width:44px;
            height:44px;

            border-radius:13px;

            display:grid;
            place-items:center;

            background:rgba(79,70,229,.08);

            color:#5b52c7;
        }

        .upload-main strong{
            font-size:10px;

            color:#39445a;
        }

        .upload-main span{
            display:block;

            color:#8a94a6;

            font-size:9px;

            margin-top:4px;
        }

        .upload input{
            margin-top:13px;

            width:100%;

            font-size:9px;

            color:#687386;
        }

        .upload-preview{
            display:none;

            align-items:center;

            gap:10px;

            margin-top:12px;

            padding-top:12px;

            border-top:1px solid var(--line);
        }

        .upload-preview img{
            width:52px;
            height:52px;

            object-fit:cover;

            border-radius:12px;

            border:1px solid #e1e6ed;

            background:#fff;
        }

        .upload-preview span{
            font-size:9px;

            color:#687386;

            word-break:break-all;
        }

        /* =========================================================
           INFO
        ========================================================= */

        .info{
            display:flex;

            gap:11px;

            margin-top:20px;

            padding:13px 14px;

            border-radius:15px;

            border:1px solid var(--line);

            background:rgba(255,255,255,.58);

            color:#7b8699;

            font-size:9px;

            line-height:1.7;
        }

        .info i{
            color:#5b52c7;

            margin-top:2px;
        }

        .info strong{
            color:#465167;
        }

        /* =========================================================
           PASSWORD
        ========================================================= */

        .password-meter{
            margin-top:8px;

            height:5px;

            border-radius:999px;

            background:#e9edf3;

            overflow:hidden;
        }

        .password-meter span{
            display:block;

            height:100%;

            width:0;

            transition:.25s;
        }

        .password-label{
            font-size:9px;

            color:#8a94a6;

            margin-top:5px;
        }

        /* =========================================================
           SUMMARY
        ========================================================= */

        .summary{
            display:grid;

            grid-template-columns:1fr 1fr;

            gap:10px;

            margin-top:20px;
        }

        .summary-item{
            padding:11px 12px;

            border:1px solid var(--line);

            border-radius:13px;

            background:rgba(248,250,252,.72);
        }

        .summary-item small{
            display:block;

            color:#929bac;

            font-size:8px;

            text-transform:uppercase;

            letter-spacing:.07em;
        }

        .summary-item b{
            display:block;

            color:#39445a;

            font-size:10px;

            margin-top:4px;

            overflow:hidden;

            text-overflow:ellipsis;

            white-space:nowrap;
        }

        /* =========================================================
           ACTIONS
        ========================================================= */

        .form-actions{
            display:flex;

            align-items:center;

            justify-content:space-between;

            gap:12px;

            margin-top:26px;

            padding-top:20px;

            border-top:1px solid var(--line);
        }

        .actions{
            display:flex;

            gap:9px;
        }

        .btn{
            height:45px;

            padding:0 17px;

            border-radius:13px;

            display:inline-flex;

            align-items:center;

            justify-content:center;

            gap:8px;

            cursor:pointer;

            font-size:10px;

            font-weight:800;

            transition:.22s ease;
        }

        .btn:hover{
            transform:translateY(-2px);
        }

        .btn:disabled{
            cursor:not-allowed;

            opacity:.65;

            transform:none;
        }

        .btn-back{
            background:#fff;

            border:1px solid #dfe4eb;

            color:#687386;

            box-shadow:
                0 5px 16px rgba(15,23,42,.04);
        }

        .btn-next{
            color:#fff;

            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--primary-2)
                );

            box-shadow:
                0 12px 28px rgba(79,70,229,.18);
        }

        .btn-submit{
            color:#06382a;

            background:
                linear-gradient(
                    135deg,
                    #6ee7b7,
                    #34d399
                );

            box-shadow:
                0 12px 28px rgba(16,185,129,.14);
        }

        .btn.loading i{
            animation:spin .8s linear infinite;
        }

        @keyframes spin{
            to{
                transform:rotate(360deg)
            }
        }

        /* =========================================================
           TOAST
        ========================================================= */

        .toast{
            position:fixed;

            right:22px;
            bottom:22px;

            z-index:50;

            display:flex;

            align-items:center;

            gap:10px;

            max-width:360px;

            padding:13px 15px;

            border:1px solid #dfe4eb;

            border-radius:15px;

            background:rgba(255,255,255,.94);

            backdrop-filter:blur(20px);

            box-shadow:
                0 20px 50px rgba(15,23,42,.14);

            opacity:0;

            transform:translateY(15px);

            pointer-events:none;

            transition:.25s ease;

            font-size:10px;

            color:#4b566b;
        }

        .toast.show{
            opacity:1;

            transform:none;
        }

        .toast i{
            color:var(--danger);
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media(max-width:1050px){

            .app{
                grid-template-columns:315px 1fr;
            }

            .workspace{
                padding:28px 24px 55px;
            }

            .brand-panel{
                padding:32px 25px;
            }

            .hero{
                margin-top:65px;
            }

            .hero h1{
                font-size:37px;
            }
        }

        @media(max-width:820px){

            .app{
                display:block;
            }

            .brand-panel{
                position:relative;

                min-height:auto;

                padding:25px 20px 23px;

                border-right:0;

                border-bottom:1px solid var(--line);
            }

            .hero{
                margin-top:35px;
            }

            .hero h1{
                font-size:35px;

                max-width:650px;
            }

            .hero p{
                max-width:680px;
            }

            .signal{
                max-width:680px;
            }

            .side-footer{
                margin-top:25px;
            }

            .workspace{
                padding:20px 16px 45px;
            }

            .steps{
                grid-template-columns:1fr;
            }

            .step-text span{
                display:block;
            }
        }

        @media(max-width:600px){

            .brand-panel{
                padding:20px 15px;
            }

            .brand-mark{
                width:43px;
                height:43px;

                border-radius:13px;
            }

            .hero{
                margin-top:28px;
            }

            .hero h1{
                font-size:30px;

                letter-spacing:-1.3px;
            }

            .hero p{
                font-size:12px;

                line-height:1.65;
            }

            .secure{
                display:none;
            }

            .workspace{
                padding:13px 10px 35px;
            }

            .topbar{
                align-items:flex-start;

                margin-bottom:14px;
            }

            .topbar h2{
                font-size:21px;
            }

            .topbar p{
                font-size:10px;
            }

            .progress-card{
                padding:15px;

                margin-bottom:10px;
            }

            .form-section{
                padding:20px 15px;
            }

            .form-grid{
                grid-template-columns:1fr;

                gap:15px;
            }

            .field.full{
                grid-column:auto;
            }

            .section-top{
                flex-direction:column;

                margin-bottom:20px;
            }

            .section-title h3{
                font-size:17px;
            }

            .form-actions{
                flex-direction:column-reverse;

                align-items:stretch;
            }

            .actions{
                width:100%;
            }

            .actions .btn{
                flex:1;
            }

            .summary{
                grid-template-columns:1fr;
            }

            .toast{
                left:12px;

                right:12px;

                bottom:12px;

                max-width:none;
            }
        }

        @media(max-width:380px){

            .step{
                padding:11px;
            }

            .form-section{
                padding:17px 12px;
            }

            .hero h1{
                font-size:27px;
            }

            .btn{
                padding:0 13px;
            }
        }

        @media(prefers-reduced-motion:reduce){

            *,
            *:before,
            *:after{
                animation-duration:.01ms!important;

                transition-duration:.01ms!important;

                scroll-behavior:auto!important;
            }
        }
    

        /* =========================================================
           LEGAL AGREEMENT
        ========================================================= */

        .legal-agreement{
            margin-top:22px;
            padding:18px;
            border:1px solid var(--line);
            border-radius:18px;
            background:rgba(255,255,255,.72);
            box-shadow:0 12px 30px rgba(15,23,42,.035);
        }

        .legal-agreement-head{
            display:flex;
            gap:12px;
            align-items:flex-start;
        }

        .legal-agreement-icon{
            width:40px;
            height:40px;
            flex:0 0 40px;
            display:grid;
            place-items:center;
            border-radius:13px;
            color:var(--primary);
            background:rgba(79,70,229,.08);
            border:1px solid rgba(79,70,229,.12);
        }

        .legal-agreement-copy{
            min-width:0;
        }

        .legal-agreement-copy strong{
            display:block;
            font-size:12px;
            color:var(--text);
        }

        .legal-agreement-copy p{
            margin-top:4px;
            font-size:11px;
            line-height:1.65;
            color:var(--muted);
        }

        .legal-actions{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:14px;
        }

        .legal-open-btn{
            min-height:38px;
            padding:0 13px;
            border-radius:10px;
            border:1px solid rgba(79,70,229,.14);
            background:rgba(79,70,229,.05);
            color:#4338ca;
            cursor:pointer;
            font-size:10px;
            font-weight:800;
        }

        .legal-check{
            display:flex;
            align-items:flex-start;
            gap:11px;
            margin-top:15px;
            padding-top:15px;
            border-top:1px solid var(--line);
            cursor:pointer;
        }

        .legal-check input{
            width:18px;
            height:18px;
            margin-top:1px;
            accent-color:var(--primary);
            flex:0 0 18px;
        }

        .legal-check span{
            color:var(--muted);
            font-size:11px;
            line-height:1.65;
        }

        .legal-check a{
            color:#4338ca;
            font-weight:800;
            text-decoration:none;
        }

        .legal-modal{
            position:fixed;
            inset:0;
            z-index:9999;
            display:none;
            align-items:center;
            justify-content:center;
            padding:22px;
            background:rgba(15,23,42,.58);
            backdrop-filter:blur(8px);
        }

        .legal-modal.active{
            display:flex;
        }

        .legal-modal-card{
            width:min(900px,100%);
            max-height:min(88vh,900px);
            display:flex;
            flex-direction:column;
            overflow:hidden;
            border-radius:24px;
            background:#fff;
            box-shadow:0 30px 90px rgba(15,23,42,.35);
        }

        .legal-modal-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:15px;
            padding:19px 21px;
            border-bottom:1px solid var(--line);
        }

        .legal-modal-top strong{
            font-family:Manrope,sans-serif;
            font-size:16px;
        }

        .legal-modal-close{
            width:38px;
            height:38px;
            border-radius:11px;
            cursor:pointer;
            color:var(--muted);
            background:#f8fafc;
            border:1px solid var(--line);
        }

        .legal-modal-body{
            overflow:auto;
            padding:24px;
        }

        .legal-hero{
            padding-bottom:20px;
            margin-bottom:20px;
            border-bottom:1px solid var(--line);
        }

        .legal-hero .eyebrow{
            font-size:9px;
        }

        .legal-hero h2{
            margin-top:12px;
            font-family:Manrope,sans-serif;
            font-size:28px;
            letter-spacing:-1px;
        }

        .legal-hero p,
        .legal-content{
            margin-top:10px;
            color:var(--muted);
            font-size:13px;
            line-height:1.8;
            white-space:normal;
        }

        .legal-section{
            padding:18px 0;
            border-bottom:1px solid var(--line);
        }

        .legal-section:last-child{
            border-bottom:0;
        }

        .legal-section h3{
            display:flex;
            align-items:center;
            gap:9px;
            font-size:14px;
            color:var(--text);
        }

        .legal-section h3 i{
            color:var(--primary);
        }

        @media (max-width:700px){
            .legal-modal{
                padding:10px;
            }

            .legal-modal-card{
                max-height:94vh;
                border-radius:18px;
            }

            .legal-modal-body{
                padding:17px;
            }

            .legal-hero h2{
                font-size:23px;
            }
        }


/* =========================================================
   LEGAL AGREEMENT — LOCKED UNTIL BOTH POLICIES ARE READ
========================================================= */
#legal_agreement:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

#legal_agreement:disabled + span {
    opacity: 0.65;
}
</style>
</head>

<body>

<div class="orb a"></div>
<div class="orb b"></div>

<div class="app">

    <!-- =========================================================
         BRAND PANEL
    ========================================================== -->

    <aside class="brand-panel">

        <div>

            <div class="brand">

                <div class="brand-mark">
                    <i class="fa-solid fa-cube"></i>
                </div>

                <div class="brand-copy">

                    <strong>
                        <?= htmlspecialchars($company_name ?? 'Ubamus'); ?>
                    </strong>

                    <span>
                        Business operations platform
                    </span>

                </div>

            </div>

            <div class="hero">

                <div class="eyebrow">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    Company onboarding
                </div>

                <h1>
                    Build the
                    <span>identity</span>
                    behind your business.
                </h1>

                <p>
                    A guided registration workspace designed to capture
                    all the information your company needs before entering
                    the operational platform.
                </p>

                <div class="signal">

                    <i class="fa-solid fa-circle-check"></i>

                    <div>

                        <strong>
                            Complete registration
                        </strong>

                        <span>
                            Every field is required so your company profile
                            is fully configured from the beginning.
                        </span>

                    </div>

                </div>

            </div>

        </div>

        <div class="side-footer">

            <div class="status">
                <span class="dot"></span>
                Registration workspace ready
            </div>

            All required company information is submitted securely
            to the existing registration endpoint.

        </div>

    </aside>


    <!-- =========================================================
         WORKSPACE
    ========================================================== -->

    <main class="workspace">

        <div class="workspace-inner">

            <div class="topbar">

                <div>

                    <h2>
                        Create your company
                    </h2>

                    <p>
                        Complete all three stages to establish your business profile.
                    </p>

                </div>

                <div class="secure">

                    <i class="fa-solid fa-shield-halved"></i>

                    All fields required

                </div>

            </div>


            <!-- =================================================
                 PROGRESS
            ================================================== -->

            <div class="glass progress-card">

                <div class="progress-head">

                    <span>
                        Registration progress
                    </span>

                    <span id="progressPercent">
                        33%
                    </span>

                </div>

                <div class="progress-track">

                    <div
                        class="progress-bar"
                        id="progressBar">
                    </div>

                </div>

            </div>


            <!-- =================================================
                 STEPS
            ================================================== -->

            <div class="steps">

                <div
                    class="step active"
                    data-step="1">

                    <div class="step-number">
                        1
                    </div>

                    <div class="step-text">

                        <strong>
                            Company
                        </strong>

                        <span>
                            Business identity
                        </span>

                    </div>

                </div>


                <div
                    class="step"
                    data-step="2">

                    <div class="step-number">
                        2
                    </div>

                    <div class="step-text">

                        <strong>
                            Contact
                        </strong>

                        <span>
                            Location & communication
                        </span>

                    </div>

                </div>


                <div
                    class="step"
                    data-step="3">

                    <div class="step-number">
                        3
                    </div>

                    <div class="step-text">

                        <strong>
                            Support
                        </strong>

                        <span>
                            Customer-care services
                        </span>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 REGISTRATION FORM
            ================================================== -->

            <form
                action="proceed_registration.php"
                method="POST"
                enctype="multipart/form-data"
                id="registrationForm"
                class="glass"
                novalidate>


                <!-- =================================================
                     STEP 1
                ================================================== -->

                <section
                    class="form-section active"
                    data-section="1">

                    <div class="section-top">

                        <div class="section-title">

                            <div class="section-icon">
                                <i class="fa-solid fa-building"></i>
                            </div>

                            <div>

                                <h3>
                                    Company identity
                                </h3>

                                <p>
                                    Provide the official identity and digital
                                    presence of your business.
                                </p>

                            </div>

                        </div>

                        <div class="step-chip">
                            STEP 01
                        </div>

                    </div>


                    <div class="form-grid">


                        <!-- COMPANY NAME -->

                        <div class="field full">

                            <label for="company_name">

                                Company name

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-building input-icon"></i>

                                <input
                                    type="text"
                                    id="company_name"
                                    name="company_name"
                                    placeholder="Enter your registered company name"
                                    autocomplete="organization"
                                    minlength="2"
                                    maxlength="255"
                                    required>

                            </div>

                            <div class="field-hint">

                                <i class="fa-solid fa-lightbulb"></i>

                                Use the official business name.

                            </div>

                        </div>


                        <!-- SLUG -->

                        <div class="field">

                            <label for="company_slug">

                                Company slug

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-link input-icon"></i>

                                <input
                                    type="text"
                                    id="company_slug"
                                    name="company_slug"
                                    placeholder="your-company"
                                    autocomplete="off"
                                    minlength="2"
                                    maxlength="255"
                                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                    required>

                            </div>

                            <div class="slug-preview">

                                Digital identity:

                                <b id="slugPreview">
                                    your-company
                                </b>

                            </div>

                        </div>


                        <!-- INDUSTRY -->

                        <div class="field">

                            <label for="company_industry">

                                Industry

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-industry input-icon"></i>

                                <select
                                    id="company_industry"
                                    name="company_industry"
                                    required>

                                    <option value="">
                                        Select industry
                                    </option>

                                    <option value="Agriculture">
                                        Agriculture
                                    </option>

                                    <option value="Construction">
                                        Construction
                                    </option>

                                    <option value="Education">
                                        Education
                                    </option>

                                    <option value="Energy">
                                        Energy
                                    </option>

                                    <option value="Engineering">
                                        Engineering
                                    </option>

                                    <option value="Finance">
                                        Finance
                                    </option>

                                    <option value="Healthcare">
                                        Healthcare
                                    </option>

                                    <option value="Hospitality">
                                        Hospitality
                                    </option>

                                    <option value="Information Technology">
                                        Information Technology
                                    </option>

                                    <option value="Manufacturing">
                                        Manufacturing
                                    </option>

                                    <option value="Mining">
                                        Mining
                                    </option>

                                    <option value="Oil and Gas">
                                        Oil and Gas
                                    </option>

                                    <option value="Professional Services">
                                        Professional Services
                                    </option>

                                    <option value="Real Estate">
                                        Real Estate
                                    </option>

                                    <option value="Retail">
                                        Retail
                                    </option>

                                    <option value="Transportation">
                                        Transportation
                                    </option>

                                    <option value="Other">
                                        Other
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- DOMAIN -->

                        <div class="field">

                            <label for="company_domain">

                                Company domain

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-globe input-icon"></i>

                                <input
                                    type="url"
                                    id="company_domain"
                                    name="company_domain"
                                    placeholder="https://example.com"
                                    autocomplete="url"
                                    maxlength="255"
                                    required>

                            </div>

                            <div class="field-hint">

                                <i class="fa-solid fa-circle-info"></i>

                                Enter the company's official website/domain.

                            </div>

                        </div>


                        <!-- LOGO -->

                        <div class="field">

                            <label for="company_logo">

                                Company logo

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div
                                class="upload"
                                id="uploadBox">

                                <div class="upload-main">

                                    <div class="upload-icon">

                                        <i class="fa-solid fa-cloud-arrow-up"></i>

                                    </div>

                                    <div>

                                        <strong>
                                            Drop logo here or choose a file
                                        </strong>

                                        <span>
                                            PNG, JPG, JPEG or WEBP — max 2MB
                                        </span>

                                    </div>

                                </div>

                                <input
                                    type="file"
                                    id="company_logo"
                                    name="company_logo"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    required>

                                <div
                                    class="upload-preview"
                                    id="uploadPreview">

                                    <img
                                        id="previewImage"
                                        src=""
                                        alt="Logo preview">

                                    <span
                                        id="previewName">
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="info">

                        <i class="fa-solid fa-circle-info"></i>

                        <div>

                            <strong>
                                Complete company identity:
                            </strong>

                            All fields in this section are required.
                            The company cannot continue until every
                            required value has been supplied.

                        </div>

                    </div>


                    <div class="form-actions">

                        <div></div>

                        <div class="actions">

                            <button
                                type="button"
                                class="btn btn-next next-btn">

                                Continue

                                <i class="fa-solid fa-arrow-right"></i>

                            </button>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     STEP 2
                ================================================== -->

                <section
                    class="form-section"
                    data-section="2">

                    <div class="section-top">

                        <div class="section-title">

                            <div class="section-icon">

                                <i class="fa-solid fa-location-dot"></i>

                            </div>

                            <div>

                                <h3>
                                    Contact & location
                                </h3>

                                <p>
                                    Define where the company operates and
                                    how it can be contacted.
                                </p>

                            </div>

                        </div>

                        <div class="step-chip">
                            STEP 02
                        </div>

                    </div>


                    <div class="form-grid">


                        <!-- COMPANY EMAIL -->

                        <div class="field">

                            <label for="company_email">

                                Company email

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-envelope input-icon"></i>

                                <input
                                    type="email"
                                    id="company_email"
                                    name="company_email"
                                    placeholder="company@example.com"
                                    autocomplete="email"
                                    maxlength="255"
                                    required>

                            </div>

                        </div>


                        <!-- MOBILE -->

                        <div class="field">

                            <label for="company_mobile">

                                Company mobile

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-phone input-icon"></i>

                                <input
                                    type="tel"
                                    id="company_mobile"
                                    name="company_mobile"
                                    placeholder="+234 800 000 0000"
                                    autocomplete="tel"
                                    minlength="7"
                                    maxlength="50"
                                    required>

                            </div>

                        </div>


                        <!-- OFFICE ADDRESS -->

                        <div class="field full">

                            <label for="company_office_address">

                                Office address

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-location-crosshairs input-icon"></i>

                                <input
                                    type="text"
                                    id="company_office_address"
                                    name="company_office_address"
                                    placeholder="Enter your company office address"
                                    autocomplete="street-address"
                                    minlength="5"
                                    maxlength="255"
                                    required>

                            </div>

                        </div>


                        <!-- STATE -->

                        <div class="field">

                            <label for="stateSelect">

                                State

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-map input-icon"></i>

                                <select
                                    id="stateSelect"
                                    name="company_state"
                                    required>

                                    <option value="">
                                        Select Nigerian state
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- CITY -->

                        <div class="field">

                            <label for="citySelect">

                                City

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-city input-icon"></i>

                                <select
                                    id="citySelect"
                                    name="company_city"
                                    required
                                    disabled>

                                    <option value="">
                                        Select state first
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- HEAD OFFICE -->

                        <div class="field full">

                            <label for="company_head_office">

                                Head office information

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <textarea
                                id="company_head_office"
                                name="company_head_office"
                                placeholder="Enter head office details, landmark or additional address information..."
                                minlength="5"
                                maxlength="2000"
                                required></textarea>

                            <div class="field-hint">

                                <i class="fa-solid fa-circle-info"></i>

                                Provide useful information about the company's head office.

                            </div>

                        </div>

                    </div>


                    <div class="info">

                        <i class="fa-solid fa-location-dot"></i>

                        <div>

                            <strong>
                                Location intelligence:
                            </strong>

                            State and city are both required.
                            Selecting a state automatically loads its
                            available cities.

                        </div>

                    </div>


                    <div class="form-actions">

                        <div class="actions">

                            <button
                                type="button"
                                class="btn btn-back prev-btn">

                                <i class="fa-solid fa-arrow-left"></i>

                                Back

                            </button>

                        </div>

                        <div class="actions">

                            <button
                                type="button"
                                class="btn btn-next next-btn">

                                Continue

                                <i class="fa-solid fa-arrow-right"></i>

                            </button>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     STEP 3
                ================================================== -->

                <section
                    class="form-section"
                    data-section="3">

                    <div class="section-top">

                        <div class="section-title">

                            <div class="section-icon">

                                <i class="fa-solid fa-headset"></i>

                            </div>

                            <div>

                                <h3>
                                    Support services
                                </h3>

                                <p>
                                    Configure the customer-care and
                                    support communication channels.
                                </p>

                            </div>

                        </div>

                        <div class="step-chip">
                            STEP 03
                        </div>

                    </div>


                    <div class="form-grid">


                        <!-- CUSTOMER CARE -->

                        <div class="field">

                            <label for="company_customer_care">

                                Customer-care contact

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-headset input-icon"></i>

                                <input
                                    type="text"
                                    id="company_customer_care"
                                    name="company_customer_care"
                                    placeholder="Phone or customer-care contact"
                                    minlength="3"
                                    maxlength="255"
                                    required>

                            </div>

                        </div>


                        <!-- SUPPORT EMAIL -->

                        <div class="field">

                            <label for="company_support_email">

                                Support email

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-life-ring input-icon"></i>

                                <input
                                    type="email"
                                    id="company_support_email"
                                    name="company_support_email"
                                    placeholder="support@example.com"
                                    autocomplete="email"
                                    maxlength="255"
                                    required>

                            </div>

                        </div>


                        <!-- CUSTOMER CARE PASSWORD -->

                        <div class="field">

                            <label for="customer_care_password">

                                Customer-care password

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-key input-icon"></i>

                                <input
                                    type="password"
                                    id="customer_care_password"
                                    name="customer_care_password"
                                    placeholder="Enter customer-care password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    maxlength="255"
                                    required>

                            </div>

                            <div class="password-meter">

                                <span id="careMeter"></span>

                            </div>

                            <div
                                class="password-label"
                                id="carePasswordLabel">

                                Minimum 8 characters required

                            </div>

                        </div>


                        <!-- SUPPORT PASSWORD -->

                        <div class="field">

                            <label for="support_email_password">

                                Support email password

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <div class="input-wrap">

                                <i class="fa-solid fa-envelope-circle-check input-icon"></i>

                                <input
                                    type="password"
                                    id="support_email_password"
                                    name="support_email_password"
                                    placeholder="Enter support email password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    maxlength="255"
                                    required>

                            </div>

                            <div class="field-hint">

                                <i class="fa-solid fa-lock"></i>

                                Required for the configured support mailbox.

                            </div>

                        </div>

                    </div>


                    <!-- SUMMARY -->

                    <div
                        class="summary"
                        id="companySummary">

                        <div class="summary-item">

                            <small>
                                Company
                            </small>

                            <b id="sumName">
                                Not provided
                            </b>

                        </div>


                        <div class="summary-item">

                            <small>
                                Industry
                            </small>

                            <b id="sumIndustry">
                                Not provided
                            </b>

                        </div>


                        <div class="summary-item">

                            <small>
                                Location
                            </small>

                            <b id="sumLocation">
                                Not provided
                            </b>

                        </div>


                        <div class="summary-item">

                            <small>
                                Primary email
                            </small>

                            <b id="sumEmail">
                                Not provided
                            </b>

                        </div>

                    </div>


                    <div class="info">

                        <i class="fa-solid fa-shield-halved"></i>

                        <div>

                            <strong>
                                Final verification:
                            </strong>

                            Every registration field must be completed
                            before the company can be created.

                        </div>

                    </div>



                    <!-- =================================================
                         LEGAL AGREEMENT
                    ================================================== -->

                    <div class="legal-agreement">

                        <div class="legal-agreement-head">

                            <div class="legal-agreement-icon">
                                <i class="fa-solid fa-file-shield"></i>
                            </div>

                            <div class="legal-agreement-copy">

                                <strong>
                                    Review our legal policies
                                </strong>

                                <p>
                                    Please open and read both the Privacy Policy
                                    and Terms &amp; Conditions before agreeing
                                    and completing your registration.
                                </p>

                            </div>

                        </div>


                        <div class="legal-actions">

                            <button
                                type="button"
                                class="legal-open-btn"
                                id="openPrivacyPolicy">

                                <i class="fa-solid fa-shield-halved"></i>

                                Read Privacy Policy

                            </button>


                            <button
                                type="button"
                                class="legal-open-btn"
                                id="openTermsConditions">

                                <i class="fa-solid fa-file-contract"></i>

                                Read Terms &amp; Conditions

                            </button>

                        </div>


                        <label class="legal-check">

                            <input
                                type="checkbox"
                                id="legal_agreement"
                                name="legal_agreement"
                                value="1"
                                required
                                disabled
                                aria-disabled="true"
                            >

                            <input type="hidden" id="privacy_read" name="privacy_read" value="0">
                            <input type="hidden" id="terms_read" name="terms_read" value="0">

                            <span>

                                I confirm that I have read and agree to the
                                <a
                                    href="#"
                                    class="legal-link-trigger"
                                    data-legal="privacy"
                                >
                                    Privacy Policy
                                </a>
                                and
                                <a
                                    href="#"
                                    class="legal-link-trigger"
                                    data-legal="terms"
                                >
                                    Terms &amp; Conditions
                                </a>.

                            </span>

                        </label>

                    </div>


                    <div class="form-actions">

                        <div class="actions">

                            <button
                                type="button"
                                class="btn btn-back prev-btn">

                                <i class="fa-solid fa-arrow-left"></i>

                                Back

                            </button>

                        </div>

                        <div class="actions">

                            <button
                                type="submit"
                                class="btn btn-submit"
                                id="submitButton"
                                name="submit">

                                <i class="fa-solid fa-check"></i>

                                Create company

                            </button>

                        </div>

                    </div>

                </section>

            </form>

        </div>

    </main>

</div>



<!-- =============================================================
     PRIVACY POLICY MODAL
============================================================== -->

<div
    class="legal-modal"
    id="privacyPolicyModal"
    aria-hidden="true"
>

    <div
        class="legal-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="privacyPolicyModalTitle"
    >

        <div class="legal-modal-top">

            <strong id="privacyPolicyModalTitle">
                Privacy Policy
            </strong>

            <button
                type="button"
                class="legal-modal-close"
                data-close-legal="privacy"
                aria-label="Close Privacy Policy"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <div class="legal-modal-body">

            <div class="legal-hero">

                <div class="eyebrow">
                    <?= htmlspecialchars($privacyPolicy['eyebrow'] ?? 'Your Privacy', ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <h2>
                    <?= htmlspecialchars($privacyPolicy['title'] ?? 'Privacy Policy', ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <p>
                    <?= nl2br(htmlspecialchars($privacyPolicy['intro'] ?? 'Please review how information is collected, used, protected and managed before continuing.', ENT_QUOTES, 'UTF-8')); ?>
                </p>

            </div>


            <div class="legal-section">
                <h3><i class="fa-solid fa-database"></i><?= htmlspecialchars($privacyPolicy['information_collect_title'] ?? 'Information We Collect', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($privacyPolicy['information_collect_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-gears"></i><?= htmlspecialchars($privacyPolicy['information_use_title'] ?? 'How We Use Your Information', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($privacyPolicy['information_use_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-lock"></i><?= htmlspecialchars($privacyPolicy['security_title'] ?? 'Information Security', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($privacyPolicy['security_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-share-nodes"></i><?= htmlspecialchars($privacyPolicy['sharing_title'] ?? 'Sharing of Information', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($privacyPolicy['sharing_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-cookie-bite"></i><?= htmlspecialchars($privacyPolicy['cookies_title'] ?? 'Cookies and Website Technologies', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($privacyPolicy['cookies_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-rotate"></i><?= htmlspecialchars($privacyPolicy['changes_title'] ?? 'Changes to This Policy', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($privacyPolicy['changes_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-envelope"></i><?= htmlspecialchars($privacyPolicy['contact_title'] ?? 'Contact Us', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($privacyPolicy['contact_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

        </div>

    </div>

</div>


<!-- =============================================================
     TERMS & CONDITIONS MODAL
============================================================== -->

<div
    class="legal-modal"
    id="termsConditionsModal"
    aria-hidden="true"
>

    <div
        class="legal-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="termsConditionsModalTitle"
    >

        <div class="legal-modal-top">

            <strong id="termsConditionsModalTitle">
                Terms &amp; Conditions
            </strong>

            <button
                type="button"
                class="legal-modal-close"
                data-close-legal="terms"
                aria-label="Close Terms and Conditions"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <div class="legal-modal-body">

            <div class="legal-hero">

                <div class="eyebrow">
                    <?= htmlspecialchars($termsConditions['eyebrow'] ?? 'Website Terms', ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <h2>
                    <?= htmlspecialchars($termsConditions['title'] ?? 'Terms & Conditions', ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <p>
                    <?= nl2br(htmlspecialchars($termsConditions['intro'] ?? 'Please review these Terms and Conditions before continuing with your registration.', ENT_QUOTES, 'UTF-8')); ?>
                </p>

            </div>


            <div class="legal-section">
                <h3><i class="fa-solid fa-check"></i><?= htmlspecialchars($termsConditions['acceptance_title'] ?? 'Acceptance of Terms', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['acceptance_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-display"></i><?= htmlspecialchars($termsConditions['website_use_title'] ?? 'Use of This Website', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['website_use_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-copyright"></i><?= htmlspecialchars($termsConditions['intellectual_property_title'] ?? 'Intellectual Property', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['intellectual_property_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-circle-info"></i><?= htmlspecialchars($termsConditions['website_information_title'] ?? 'Website Information', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['website_information_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-triangle-exclamation"></i><?= htmlspecialchars($termsConditions['liability_title'] ?? 'Limitation of Liability', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['liability_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-link"></i><?= htmlspecialchars($termsConditions['third_party_title'] ?? 'Third-Party Services', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['third_party_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-pen-to-square"></i><?= htmlspecialchars($termsConditions['changes_title'] ?? 'Changes to These Terms', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['changes_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

            <div class="legal-section">
                <h3><i class="fa-solid fa-envelope"></i><?= htmlspecialchars($termsConditions['contact_title'] ?? 'Contact', ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="legal-content"><?= nl2br(htmlspecialchars($termsConditions['contact_content'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>

        </div>

    </div>

</div>


<!-- =============================================================
     TOAST
============================================================== -->

<div
    class="toast"
    id="toast">

    <i class="fa-solid fa-circle-exclamation"></i>

    <span id="toastText"></span>

</div>


<script src="assets/plugins/controls.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    'use strict';

    /* =========================================================
       CORE ELEMENTS
    ========================================================== */

    const form = document.getElementById('registrationForm');

    if (!form) {
        console.error('Registration form was not found.');
        return;
    }

    const sections = Array.from(
        document.querySelectorAll('.form-section')
    );

    const steps = Array.from(
        document.querySelectorAll('.step')
    );

    const nextButtons = Array.from(
        document.querySelectorAll('.next-btn')
    );

    const prevButtons = Array.from(
        document.querySelectorAll('.prev-btn')
    );

    const progressBar =
        document.getElementById('progressBar');

    const progressPercent =
        document.getElementById('progressPercent');

    const submitButton =
        document.getElementById('submitButton');


    const legalAgreement =
        document.getElementById('legal_agreement');

    const privacyPolicyModal =
        document.getElementById('privacyPolicyModal');

    const termsConditionsModal =
        document.getElementById('termsConditionsModal');

    const toastElement =
        document.getElementById('toast');

    const toastText =
        document.getElementById('toastText');

    let currentStep = 1;


    /* =========================================================
       LEGAL READING REQUIREMENTS
    ========================================================== */
    let privacyHasBeenRead = false;
    let termsHasBeenRead = false;
    const LEGAL_BOTTOM_TOLERANCE = 24;

    const privacyReadInput =
        document.getElementById('privacy_read');

    const termsReadInput =
        document.getElementById('terms_read');

    function updateLegalAgreementState() {
        const canAgree =
            privacyHasBeenRead &&
            termsHasBeenRead;

        if (legalAgreement) {
            legalAgreement.disabled = !canAgree;
            legalAgreement.setAttribute(
                'aria-disabled',
                canAgree ? 'false' : 'true'
            );

            if (!canAgree) {
                legalAgreement.checked = false;
            }
        }
    }

    function markLegalDocumentRead(type) {
        if (type === 'privacy') {
            privacyHasBeenRead = true;
            if (privacyReadInput) privacyReadInput.value = '1';
        } else if (type === 'terms') {
            termsHasBeenRead = true;
            if (termsReadInput) termsReadInput.value = '1';
        }
        updateLegalAgreementState();
    }

    function checkLegalDocumentReachedBottom(type) {
        const modal =
            type === 'privacy'
                ? privacyPolicyModal
                : termsConditionsModal;

        if (!modal) return;

        const body = modal.querySelector('.legal-modal-body');
        if (!body) return;

        const reachedBottom =
            body.scrollTop + body.clientHeight >=
            body.scrollHeight - LEGAL_BOTTOM_TOLERANCE;

        if (reachedBottom) {
            markLegalDocumentRead(type);
        }
    }

    updateLegalAgreementState();


    /* =========================================================
       NIGERIA STATES + CITIES
       ---------------------------------------------------------
       Complete built-in Nigerian location dataset.
       No dependency on $locations_js or app_data.php.
    ========================================================== */

    const nigeriaLocations = {

        "Abia": [
            "Aba",
            "Arochukwu",
            "Bende",
            "Ikwuano",
            "Isiala Ngwa North",
            "Isiala Ngwa South",
            "Isuikwuato",
            "Obi Ngwa",
            "Ohafia",
            "Osisioma",
            "Ugwunagbo",
            "Ukwa East",
            "Ukwa West",
            "Umuahia",
            "Umuahia South",
            "Umunneochi"
        ],

        "Adamawa": [
            "Demsa",
            "Fufore",
            "Ganye",
            "Girei",
            "Gombi",
            "Guyuk",
            "Hong",
            "Jada",
            "Jimeta",
            "Lamurde",
            "Madagali",
            "Maiha",
            "Mayo-Belwa",
            "Michika",
            "Mubi North",
            "Mubi South",
            "Numan",
            "Shelleng",
            "Song",
            "Toungo",
            "Yola",
            "Yola North",
            "Yola South"
        ],

        "Akwa Ibom": [
            "Abak",
            "Eastern Obolo",
            "Eket",
            "Esit Eket",
            "Essien Udim",
            "Etim Ekpo",
            "Etinan",
            "Ibeno",
            "Ibiono Ibom",
            "Ika",
            "Ikono",
            "Ikot Abasi",
            "Ikot Ekpene",
            "Ini",
            "Itu",
            "Mbo",
            "Mkpat-Enin",
            "Nsit-Atai",
            "Nsit-Ibom",
            "Nsit-Ubium",
            "Obot Akara",
            "Okobo",
            "Onna",
            "Oron",
            "Oruk Anam",
            "Udung Uko",
            "Ukanafun",
            "Uruan",
            "Urue-Offong/Oruko",
            "Uyo"
        ],

        "Anambra": [
            "Aguata",
            "Awka",
            "Awka North",
            "Awka South",
            "Anambra East",
            "Anambra West",
            "Anaocha",
            "Ayamelum",
            "Dunukofia",
            "Ekwusigo",
            "Idemili North",
            "Idemili South",
            "Ihiala",
            "Njikoka",
            "Nnewi",
            "Nnewi North",
            "Nnewi South",
            "Ogbaru",
            "Onitsha",
            "Onitsha North",
            "Onitsha South",
            "Orumba North",
            "Orumba South",
            "Oyi"
        ],

        "Bauchi": [
            "Bauchi",
            "Bogoro",
            "Damban",
            "Darazo",
            "Dass",
            "Gamawa",
            "Ganjuwa",
            "Giade",
            "Itas/Gadau",
            "Jama'are",
            "Katagum",
            "Kirfi",
            "Misau",
            "Ningi",
            "Shira",
            "Tafawa Balewa",
            "Toro",
            "Warji",
            "Zaki"
        ],

        "Bayelsa": [
            "Brass",
            "Ekeremor",
            "Kolokuma/Opokuma",
            "Nembe",
            "Ogbia",
            "Sagbama",
            "Southern Ijaw",
            "Yenagoa"
        ],

        "Benue": [
            "Ado",
            "Agatu",
            "Apa",
            "Buruku",
            "Gboko",
            "Guma",
            "Gwer East",
            "Gwer West",
            "Katsina-Ala",
            "Konshisha",
            "Kwande",
            "Logo",
            "Makurdi",
            "Obi",
            "Ogbadibo",
            "Ohimini",
            "Oju",
            "Okpokwu",
            "Oturkpo",
            "Tarka",
            "Ukum",
            "Ushongo",
            "Vandeikya"
        ],

        "Borno": [
            "Abadam",
            "Askira/Uba",
            "Bama",
            "Bayo",
            "Biu",
            "Chibok",
            "Damboa",
            "Dikwa",
            "Gubio",
            "Guzamala",
            "Gwoza",
            "Hawul",
            "Jere",
            "Kaga",
            "Kala/Balge",
            "Konduga",
            "Kukawa",
            "Kwaya Kusar",
            "Mafa",
            "Magumeri",
            "Maiduguri",
            "Marte",
            "Mobbar",
            "Monguno",
            "Ngala",
            "Nganzai",
            "Shani"
        ],

        "Cross River": [
            "Abi",
            "Akamkpa",
            "Akpabuyo",
            "Bakassi",
            "Bekwarra",
            "Biase",
            "Calabar",
            "Calabar Municipal",
            "Calabar South",
            "Etung",
            "Ikom",
            "Obanliku",
            "Obubra",
            "Obudu",
            "Odukpani",
            "Ogoja",
            "Yakurr",
            "Yala"
        ],

        "Delta": [
            "Aniocha North",
            "Aniocha South",
            "Bomadi",
            "Burutu",
            "Ethiope East",
            "Ethiope West",
            "Ika North East",
            "Ika South",
            "Isoko North",
            "Isoko South",
            "Ndokwa East",
            "Ndokwa West",
            "Okpe",
            "Oshimili North",
            "Oshimili South",
            "Patani",
            "Sapele",
            "Udu",
            "Ughelli North",
            "Ughelli South",
            "Ukwuani",
            "Uvwie",
            "Warri",
            "Warri North",
            "Warri South",
            "Warri South West"
        ],

        "Ebonyi": [
            "Abakaliki",
            "Afikpo North",
            "Afikpo South",
            "Ebonyi",
            "Ezza North",
            "Ezza South",
            "Ikwo",
            "Ishielu",
            "Ivo",
            "Izzi",
            "Ohaukwu",
            "Onicha"
        ],

        "Edo": [
            "Akoko-Edo",
            "Egor",
            "Esan Central",
            "Esan North-East",
            "Esan South-East",
            "Esan West",
            "Etsako Central",
            "Etsako East",
            "Etsako West",
            "Igueben",
            "Ikpoba-Okha",
            "Oredo",
            "Orhionmwon",
            "Ovia North-East",
            "Ovia South-West",
            "Owan East",
            "Owan West",
            "Uhunmwonde",
            "Benin City"
        ],

        "Ekiti": [
            "Ado-Ekiti",
            "Efon",
            "Ekiti East",
            "Ekiti South-West",
            "Ekiti West",
            "Emure",
            "Gbonyin",
            "Ido-Osi",
            "Ijero",
            "Ikere",
            "Ikole",
            "Ilejemeje",
            "Irepodun/Ifelodun",
            "Ise/Orun",
            "Moba",
            "Oye"
        ],

        "Enugu": [
            "Aninri",
            "Awgu",
            "Enugu",
            "Enugu East",
            "Enugu North",
            "Enugu South",
            "Ezeagu",
            "Igbo-Etiti",
            "Igbo-Eze North",
            "Igbo-Eze South",
            "Isi-Uzo",
            "Nkanu East",
            "Nkanu West",
            "Nsukka",
            "Oji River",
            "Udenu",
            "Udi",
            "Uzo-Uwani"
        ],

        "Gombe": [
            "Akko",
            "Balanga",
            "Billiri",
            "Dukku",
            "Funakaye",
            "Gombe",
            "Kaltungo",
            "Kwami",
            "Nafada",
            "Shongom",
            "Yamaltu/Deba"
        ],

        "Imo": [
            "Ahiazu Mbaise",
            "Ehime Mbano",
            "Ezinihitte",
            "Ideato North",
            "Ideato South",
            "Ihitte/Uboma",
            "Ikeduru",
            "Isiala Mbano",
            "Isu",
            "Mbaitoli",
            "Ngor Okpala",
            "Njaba",
            "Nkwerre",
            "Nwangele",
            "Obowo",
            "Oguta",
            "Ohaji/Egbema",
            "Okigwe",
            "Orlu",
            "Orsu",
            "Oru East",
            "Oru West",
            "Owerri",
            "Owerri Municipal",
            "Owerri North",
            "Owerri West",
            "Unuimo"
        ],

        "Jigawa": [
            "Auyo",
            "Babura",
            "Biriniwa",
            "Birnin Kudu",
            "Buji",
            "Dutse",
            "Gagarawa",
            "Garki",
            "Gumel",
            "Guri",
            "Gwaram",
            "Gwiwa",
            "Hadejia",
            "Jahun",
            "Kafin Hausa",
            "Kaugama",
            "Kazaure",
            "Kiri Kasama",
            "Kiyawa",
            "Maigatari",
            "Malam Madori",
            "Miga",
            "Ringim",
            "Roni",
            "Sule Tankarkar",
            "Taura",
            "Yankwashi"
        ],

        "Kaduna": [
            "Birnin Gwari",
            "Chikun",
            "Giwa",
            "Igabi",
            "Ikara",
            "Jaba",
            "Jema'a",
            "Kachia",
            "Kaduna",
            "Kaduna North",
            "Kaduna South",
            "Kagarko",
            "Kajuru",
            "Kaura",
            "Kauru",
            "Kubau",
            "Kudan",
            "Lere",
            "Makarfi",
            "Sabon Gari",
            "Sanga",
            "Soba",
            "Zangon Kataf",
            "Zaria"
        ],

        "Kano": [
            "Ajingi",
            "Albasu",
            "Bagwai",
            "Bebeji",
            "Bichi",
            "Bunkure",
            "Dala",
            "Dambatta",
            "Dawakin Kudu",
            "Dawakin Tofa",
            "Doguwa",
            "Fagge",
            "Gabasawa",
            "Garko",
            "Garun Mallam",
            "Gaya",
            "Gezawa",
            "Gwale",
            "Gwarzo",
            "Kabo",
            "Kano Municipal",
            "Karaye",
            "Kibiya",
            "Kiru",
            "Kumbotso",
            "Kunchi",
            "Kura",
            "Madobi",
            "Makoda",
            "Minjibir",
            "Nasarawa",
            "Rano",
            "Rimin Gado",
            "Rogo",
            "Shanono",
            "Sumaila",
            "Takai",
            "Tarauni",
            "Tofa",
            "Tsanyawa",
            "Tudun Wada",
            "Ungogo",
            "Warawa",
            "Wudil"
        ],

        "Katsina": [
            "Bakori",
            "Batagarawa",
            "Batsari",
            "Baure",
            "Bindawa",
            "Charanchi",
            "Dan Musa",
            "Dandume",
            "Danja",
            "Daura",
            "Dutsi",
            "Dutsin-Ma",
            "Faskari",
            "Funtua",
            "Ingawa",
            "Jibia",
            "Kafur",
            "Kaita",
            "Kankara",
            "Kankia",
            "Katsina",
            "Kurfi",
            "Kusada",
            "Mai'Adua",
            "Malumfashi",
            "Mani",
            "Mashi",
            "Matazu",
            "Musawa",
            "Rimi",
            "Sabuwa",
            "Safana",
            "Sandamu",
            "Zango"
        ],

        "Kebbi": [
            "Aleiro",
            "Arewa Dandi",
            "Argungu",
            "Augie",
            "Bagudo",
            "Birnin Kebbi",
            "Bunza",
            "Dandi",
            "Dankowasagu",
            "Danko/Wasagu",
            "Fakai",
            "Gwandu",
            "Jega",
            "Kalgo",
            "Koko/Besse",
            "Maiyama",
            "Ngaski",
            "Sakaba",
            "Shanga",
            "Suru",
            "Wasagu/Danko",
            "Yauri",
            "Zuru"
        ],

        "Kogi": [
            "Adavi",
            "Ajaokuta",
            "Ankpa",
            "Bassa",
            "Dekina",
            "Ibaji",
            "Idah",
            "Igalamela-Odolu",
            "Ijumu",
            "Kabba/Bunu",
            "Kogi",
            "Koton Karfe",
            "Lokoja",
            "Mopa-Muro",
            "Ofu",
            "Ogori/Magongo",
            "Okehi",
            "Okene",
            "Olamaboro",
            "Omala",
            "Yagba East",
            "Yagba West"
        ],

        "Kwara": [
            "Asa",
            "Baruten",
            "Edu",
            "Ekiti",
            "Ifelodun",
            "Ilorin",
            "Ilorin East",
            "Ilorin South",
            "Ilorin West",
            "Irepodun",
            "Isin",
            "Kaiama",
            "Moro",
            "Offa",
            "Oke Ero",
            "Oyun",
            "Pategi"
        ],

        "Lagos": [
            "Agege",
            "Ajeromi-Ifelodun",
            "Alimosho",
            "Amuwo-Odofin",
            "Apapa",
            "Badagry",
            "Epe",
            "Eti-Osa",
            "Ibeju-Lekki",
            "Ifako-Ijaiye",
            "Ikeja",
            "Ikorodu",
            "Kosofe",
            "Lagos Island",
            "Lagos Mainland",
            "Mushin",
            "Ojo",
            "Oshodi-Isolo",
            "Shomolu",
            "Surulere"
        ],

        "Nasarawa": [
            "Akwanga",
            "Awe",
            "Doma",
            "Karu",
            "Keana",
            "Keffi",
            "Kokona",
            "Lafia",
            "Nasarawa",
            "Nasarawa Eggon",
            "Obi",
            "Toto",
            "Wamba"
        ],

        "Niger": [
            "Agaie",
            "Agwara",
            "Bida",
            "Borgu",
            "Bosso",
            "Chanchaga",
            "Edati",
            "Gbako",
            "Gurara",
            "Katcha",
            "Kontagora",
            "Lapai",
            "Lavun",
            "Magama",
            "Mariga",
            "Mashegu",
            "Mokwa",
            "Munya",
            "Paikoro",
            "Rafi",
            "Rijau",
            "Shiroro",
            "Suleja",
            "Tafa",
            "Wushishi",
            "Minna"
        ],

        "Ogun": [
            "Abeokuta",
            "Abeokuta North",
            "Abeokuta South",
            "Ado-Odo/Ota",
            "Egbado North",
            "Egbado South",
            "Ijebu East",
            "Ijebu North",
            "Ijebu North East",
            "Ijebu Ode",
            "Ikenne",
            "Imeko Afon",
            "Ipokia",
            "Obafemi Owode",
            "Odeda",
            "Odogbolu",
            "Ogun Waterside",
            "Remo North",
            "Sagamu"
        ],

        "Ondo": [
            "Akoko North-East",
            "Akoko North-West",
            "Akoko South-East",
            "Akoko South-West",
            "Akure",
            "Akure North",
            "Akure South",
            "Ese Odo",
            "Idanre",
            "Ifedore",
            "Ilaje",
            "Ile Oluji/Okeigbo",
            "Irele",
            "Odigbo",
            "Okitipupa",
            "Ondo East",
            "Ondo West",
            "Ose",
            "Owo"
        ],

        "Osun": [
            "Atakumosa East",
            "Atakumosa West",
            "Atakunmosa East",
            "Ayedaade",
            "Ayedire",
            "Boluwaduro",
            "Boripe",
            "Ede",
            "Ede North",
            "Ede South",
            "Egbedore",
            "Ejigbo",
            "Ife Central",
            "Ife East",
            "Ife North",
            "Ife South",
            "Ifedayo",
            "Ifelodun",
            "Ila",
            "Ilesa East",
            "Ilesa West",
            "Irepodun",
            "Irewole",
            "Isokan",
            "Iwo",
            "Obokun",
            "Odo Otin",
            "Ola Oluwa",
            "Olorunda",
            "Oriade",
            "Orolu",
            "Osogbo"
        ],

        "Oyo": [
            "Afijio",
            "Akinyele",
            "Atiba",
            "Atisbo",
            "Egbeda",
            "Ibadan",
            "Ibadan North",
            "Ibadan North-East",
            "Ibadan North-West",
            "Ibadan South-East",
            "Ibadan South-West",
            "Ibarapa Central",
            "Ibarapa East",
            "Ibarapa North",
            "Ido",
            "Irepo",
            "Iseyin",
            "Itesiwaju",
            "Iwajowa",
            "Kajola",
            "Lagelu",
            "Ogbomosho",
            "Ogbomosho North",
            "Ogbomosho South",
            "Ogo Oluwa",
            "Olorunsogo",
            "Oluyole",
            "Ona Ara",
            "Orelope",
            "Ori Ire",
            "Oyo",
            "Oyo East",
            "Oyo West",
            "Saki",
            "Saki East",
            "Saki West",
            "Surulere"
        ],

        "Plateau": [
            "Barkin Ladi",
            "Bassa",
            "Bokkos",
            "Jos",
            "Jos East",
            "Jos North",
            "Jos South",
            "Kanam",
            "Kanke",
            "Langtang North",
            "Langtang South",
            "Mangu",
            "Mikang",
            "Pankshin",
            "Qua'an Pan",
            "Riyom",
            "Shendam",
            "Wase"
        ],

        "Rivers": [
            "Abua/Odual",
            "Ahoada East",
            "Ahoada West",
            "Akuku-Toru",
            "Andoni",
            "Asari-Toru",
            "Bonny",
            "Degema",
            "Eleme",
            "Emohua",
            "Etche",
            "Gokana",
            "Ikwerre",
            "Khana",
            "Obio/Akpor",
            "Ogba/Egbema/Ndoni",
            "Ogu/Bolo",
            "Okrika",
            "Omuma",
            "Opobo/Nkoro",
            "Oyigbo",
            "Port Harcourt",
            "Tai"
        ],

        "Sokoto": [
            "Binji",
            "Bodinga",
            "Dange Shuni",
            "Gada",
            "Goronyo",
            "Gudu",
            "Gwadabawa",
            "Illela",
            "Isa",
            "Kebbe",
            "Kware",
            "Rabah",
            "Sabon Birni",
            "Shagari",
            "Silame",
            "Sokoto North",
            "Sokoto South",
            "Tambuwal",
            "Tangaza",
            "Tureta",
            "Wamakko",
            "Wurno",
            "Yabo"
        ],

        "Taraba": [
            "Ardo Kola",
            "Bali",
            "Donga",
            "Gashaka",
            "Gassol",
            "Ibi",
            "Jalingo",
            "Karim Lamido",
            "Kumi",
            "Lau",
            "Sardauna",
            "Takum",
            "Ussa",
            "Wukari",
            "Yorro",
            "Zing"
        ],

        "Yobe": [
            "Bade",
            "Bursari",
            "Damaturu",
            "Fika",
            "Fune",
            "Geidam",
            "Gujba",
            "Gulani",
            "Jakusko",
            "Karasuwa",
            "Machina",
            "Nangere",
            "Nguru",
            "Potiskum",
            "Tarmuwa",
            "Yunusari",
            "Yusufari"
        ],

        "Zamfara": [
            "Anka",
            "Bakura",
            "Birnin Magaji/Kiyaw",
            "Bukkuyum",
            "Bungudu",
            "Chafe",
            "Gummi",
            "Gusau",
            "Kaura Namoda",
            "Maradun",
            "Maru",
            "Shinkafi",
            "Talata Mafara",
            "Tsafe",
            "Zurmi"
        ],

        "Federal Capital Territory": [
            "Abaji",
            "Bwari",
            "Gwagwalada",
            "Kuje",
            "Kwali",
            "Municipal Area Council",
            "Abuja"
        ]

    };


    /* =========================================================
       STATE / CITY ELEMENTS
    ========================================================== */

    const stateSelect =
        document.getElementById('stateSelect');

    const citySelect =
        document.getElementById('citySelect');


    /* =========================================================
       LOAD NIGERIAN STATES
    ========================================================== */

    function loadStates() {

        if (!stateSelect) {
            console.error('State select element not found.');
            return;
        }

        stateSelect.innerHTML =
            '<option value="">Select Nigerian state</option>';

        Object.keys(nigeriaLocations)
            .sort(function (a, b) {
                return a.localeCompare(b);
            })
            .forEach(function (state) {

                const option =
                    document.createElement('option');

                option.value = state;
                option.textContent = state;

                stateSelect.appendChild(option);

            });

        console.log(
            'Nigeria states loaded:',
            Object.keys(nigeriaLocations).length
        );
    }


    /* =========================================================
       LOAD CITIES WHEN STATE CHANGES
    ========================================================== */

    function loadCities(state) {

        if (!citySelect) {
            return;
        }

        citySelect.innerHTML = '';

        if (!state) {

            citySelect.disabled = true;

            citySelect.innerHTML =
                '<option value="">Select state first</option>';

            citySelect.classList.remove('is-valid');
            citySelect.classList.remove('is-invalid');

            updateSummary();

            return;
        }

        const cities =
            nigeriaLocations[state] || [];

        citySelect.disabled = false;

        const placeholder =
            document.createElement('option');

        placeholder.value = '';
        placeholder.textContent =
            cities.length
                ? 'Select city'
                : 'No cities available';

        citySelect.appendChild(placeholder);


        cities.forEach(function (city) {

            const option =
                document.createElement('option');

            option.value = city;
            option.textContent = city;

            citySelect.appendChild(option);

        });


        updateSummary();

        if (stateSelect) {
            validateField(stateSelect);
        }

    }


    /* =========================================================
       STATE CHANGE EVENT
    ========================================================== */

    if (stateSelect) {

        stateSelect.addEventListener(
            'change',
            function () {

                loadCities(
                    stateSelect.value
                );

                stateSelect.classList.remove(
                    'is-invalid'
                );

                if (stateSelect.value) {

                    stateSelect.classList.add(
                        'is-valid'
                    );

                }

                updateSummary();

            }
        );

    }


    /* =========================================================
       CITY CHANGE EVENT
    ========================================================== */

    if (citySelect) {

        citySelect.addEventListener(
            'change',
            function () {

                if (citySelect.value) {

                    citySelect.classList.remove(
                        'is-invalid'
                    );

                    citySelect.classList.add(
                        'is-valid'
                    );

                } else {

                    citySelect.classList.remove(
                        'is-valid'
                    );

                }

                updateSummary();

            }
        );

    }


    /* =========================================================
       INITIALIZE LOCATIONS
    ========================================================== */

    loadStates();

    if (citySelect) {

        citySelect.disabled = true;

        citySelect.innerHTML =
            '<option value="">Select state first</option>';

    }

    /* =========================================================
       COMPANY ELEMENTS
    ========================================================== */

    const companyName =
        document.getElementById('company_name');

    const companySlug =
        document.getElementById('company_slug');

    const slugPreview =
        document.getElementById('slugPreview');

    let slugManuallyEdited = false;


    /* =========================================================
       TOAST
    ========================================================== */

    function toast(message) {

        if (!toastElement || !toastText) {
            alert(message);
            return;
        }

        toastText.textContent = message;

        toastElement.classList.add('show');

        clearTimeout(window.__registrationToastTimer);

        window.__registrationToastTimer =
            setTimeout(function () {

                toastElement.classList.remove('show');

            }, 3500);
    }


    /* =========================================================
       SLUGIFY
    ========================================================== */

    function slugify(value) {

        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

    }


    function syncSlug() {

        if (!companySlug || !slugPreview) {
            return;
        }

        const value =
            companySlug.value.trim() ||
            'your-company';

        slugPreview.textContent = value;

    }


    /* =========================================================
       COMPANY NAME → SLUG
    ========================================================== */

    if (companyName) {

        companyName.addEventListener(
            'input',
            function () {

                if (
                    companySlug &&
                    !slugManuallyEdited
                ) {

                    companySlug.value =
                        slugify(companyName.value);

                }

                syncSlug();
                updateSummary();

            }
        );

    }


    if (companySlug) {

        companySlug.addEventListener(
            'input',
            function () {

                slugManuallyEdited = true;

                companySlug.value =
                    companySlug.value
                        .toLowerCase()
                        .replace(/[^a-z0-9-]/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-+|-+$/g, '');

                syncSlug();

            }
        );

    }


    /* =========================================================
       LOAD NIGERIAN STATES
    ========================================================== */

    function loadStates() {

        if (!stateSelect) {
            return;
        }

        /*
         * Remove existing dynamically generated states while
         * preserving the first placeholder.
         */
        stateSelect.innerHTML =
            '<option value="">Select Nigerian state</option>';

        let states = [];

        try {

            if (
                nigeriaLocations &&
                typeof nigeriaLocations === 'object'
            ) {

                states =
                    Object.keys(nigeriaLocations)
                        .sort(function (a, b) {
                            return a.localeCompare(b);
                        });

            }

        } catch (error) {

            console.error(
                'Unable to read Nigerian states:',
                error
            );

        }


        states.forEach(function (state) {

            const option =
                document.createElement('option');

            option.value = state;
            option.textContent = state;

            stateSelect.appendChild(option);

        });

    }


    /* =========================================================
       LOAD CITIES
    ========================================================== */

    function loadCities(state) {

        if (!citySelect) {
            return;
        }

        citySelect.innerHTML = '';

        citySelect.disabled = !state;

        const placeholder =
            document.createElement('option');

        placeholder.value = '';

        placeholder.textContent =
            state
                ? 'Select city'
                : 'Select state first';

        citySelect.appendChild(placeholder);


        if (!state) {

            citySelect.classList.remove('is-valid');
            citySelect.classList.remove('is-invalid');

            updateSummary();

            return;
        }


        let cities = [];

        try {

            if (
                nigeriaLocations &&
                typeof nigeriaLocations === 'object' &&
                Array.isArray(nigeriaLocations[state])
            ) {

                cities =
                    nigeriaLocations[state];

            }

        } catch (error) {

            console.error(
                'Unable to load cities:',
                error
            );

        }


        cities.forEach(function (city) {

            const option =
                document.createElement('option');

            option.value = city;
            option.textContent = city;

            citySelect.appendChild(option);

        });


        updateSummary();

        validateField(stateSelect);

    }


    if (stateSelect) {

        stateSelect.addEventListener(
            'change',
            function () {

                loadCities(
                    stateSelect.value
                );

            }
        );

    }


    /* =========================================================
       FIELD VALIDATION
    ========================================================== */

    function validateField(field) {

        if (!field) {
            return false;
        }


        /*
         * Disabled fields are considered valid because they are
         * intentionally unavailable.
         */
        if (field.disabled) {

            field.classList.remove('is-invalid');

            return true;

        }


        /* -----------------------------------------------------
           FILE VALIDATION
        ----------------------------------------------------- */

        if (field.type === 'file') {

            if (
                !field.files ||
                field.files.length === 0
            ) {

                field.classList.add('is-invalid');
                field.classList.remove('is-valid');

                return false;

            }


            const file =
                field.files[0];


            const allowedTypes = [
                'image/png',
                'image/jpeg',
                'image/jpg',
                'image/webp'
            ];


            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {

                field.classList.add('is-invalid');
                field.classList.remove('is-valid');

                return false;

            }


            if (
                file.size >
                2 * 1024 * 1024
            ) {

                field.classList.add('is-invalid');
                field.classList.remove('is-valid');

                return false;

            }


            field.classList.remove('is-invalid');
            field.classList.add('is-valid');

            return true;

        }


        /* -----------------------------------------------------
           NORMAL FIELD VALIDATION
        ----------------------------------------------------- */

        if (
            field.type !== 'password' &&
            field.tagName !== 'SELECT'
        ) {

            field.value =
                field.value.trim();

        }


        /*
         * Required fields.
         */
        if (
            field.hasAttribute('required') &&
            !String(field.value || '').trim()
        ) {

            field.classList.add('is-invalid');
            field.classList.remove('is-valid');

            return false;

        }


        /*
         * HTML5 constraints:
         * email, URL, minlength, maxlength, pattern etc.
         */
        if (!field.checkValidity()) {

            field.classList.add('is-invalid');
            field.classList.remove('is-valid');

            return false;

        }


        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        return true;

    }


    /* =========================================================
       VALIDATE SECTION
    ========================================================== */

    function validateSection(stepNumber) {

        const section =
            sections.find(function (section) {

                return Number(
                    section.dataset.section
                ) === Number(stepNumber);

            });


        if (!section) {
            return true;
        }


        const fields =
            Array.from(
                section.querySelectorAll(
                    'input, select, textarea'
                )
            );


        for (const field of fields) {

            if (field.disabled) {
                continue;
            }


            if (!validateField(field)) {

                let message =
                    'Please complete this field correctly.';


                if (
                    field.type === 'file'
                ) {

                    if (
                        !field.files ||
                        !field.files.length
                    ) {

                        message =
                            'Please select your company logo.';

                    } else {

                        const file =
                            field.files[0];

                        if (
                            ![
                                'image/png',
                                'image/jpeg',
                                'image/jpg',
                                'image/webp'
                            ].includes(file.type)
                        ) {

                            message =
                                'Logo must be PNG, JPG, JPEG or WEBP.';

                        } else if (
                            file.size >
                            2 * 1024 * 1024
                        ) {

                            message =
                                'Company logo must not exceed 2MB.';

                        }

                    }

                }


                else if (
                    field.id === 'company_domain'
                ) {

                    message =
                        'Please enter a valid company domain, for example https://example.com.';

                }


                else if (
                    field.type === 'email'
                ) {

                    message =
                        'Please enter a valid email address.';

                }


                else if (
                    field.id ===
                        'customer_care_password' ||
                    field.id ===
                        'support_email_password'
                ) {

                    message =
                        'Password must contain at least 8 characters.';

                }


                else if (
                    field.id === 'stateSelect'
                ) {

                    message =
                        'Please select your company state.';

                }


                else if (
                    field.id === 'citySelect'
                ) {

                    message =
                        'Please select your company city.';

                }


                toast(message);


                /*
                 * Focus the invalid field where possible.
                 */
                try {

                    field.focus();

                } catch (error) {}


                /*
                 * Show native validation message for normal
                 * form controls.
                 */
                if (
                    field.type !== 'file' &&
                    typeof field.reportValidity ===
                        'function'
                ) {

                    field.reportValidity();

                }


                return false;

            }

        }


        return true;

    }


    /* =========================================================
       VALIDATE ALL PREVIOUS STEPS
    ========================================================== */

    function validateBeforeStep(targetStep) {

        const target =
            Number(targetStep);


        for (
            let stepNumber = 1;
            stepNumber < target;
            stepNumber++
        ) {

            if (
                !validateSection(stepNumber)
            ) {

                showStep(stepNumber);

                return false;

            }

        }


        return true;

    }


    /* =========================================================
       SHOW STEP
    ========================================================== */

    function showStep(stepNumber) {

        if (!sections.length) {
            return;
        }


        let step =
            Number(stepNumber);


        if (
            Number.isNaN(step) ||
            step < 1
        ) {

            step = 1;

        }


        if (
            step > sections.length
        ) {

            step = sections.length;

        }


        currentStep = step;


        /* -----------------------------------------------------
           SHOW/HIDE SECTIONS
        ----------------------------------------------------- */

        sections.forEach(function (section) {

            const sectionNumber =
                Number(
                    section.dataset.section
                );


            section.classList.toggle(
                'active',
                sectionNumber === currentStep
            );

        });


        /* -----------------------------------------------------
           UPDATE STEP INDICATORS
        ----------------------------------------------------- */

        steps.forEach(function (stepElement) {

            const number =
                Number(
                    stepElement.dataset.step
                );


            stepElement.classList.toggle(
                'active',
                number === currentStep
            );


            stepElement.classList.toggle(
                'complete',
                number < currentStep
            );


            const numberElement =
                stepElement.querySelector(
                    '.step-number'
                );


            if (!numberElement) {
                return;
            }


            if (number < currentStep) {

                numberElement.innerHTML =
                    '<i class="fa-solid fa-check"></i>';

            } else {

                numberElement.textContent =
                    number;

            }

        });


        /* -----------------------------------------------------
           PROGRESS
        ----------------------------------------------------- */

        const percentage =
            Math.round(
                (
                    currentStep /
                    sections.length
                ) * 100
            );


        if (progressBar) {

            progressBar.style.width =
                percentage + '%';

        }


        if (progressPercent) {

            progressPercent.textContent =
                percentage + '%';

        }


        updateSummary();


        /*
         * Scroll to the form rather than forcing the entire page
         * to the very top.
         */
        const formTop =
            form.getBoundingClientRect().top +
            window.scrollY -
            20;


        window.scrollTo({

            top: Math.max(0, formTop),

            behavior:
                window.matchMedia(
                    '(prefers-reduced-motion: reduce)'
                ).matches
                    ? 'auto'
                    : 'smooth'

        });

    }


    /* =========================================================
       NEXT BUTTONS
    ========================================================== */

    nextButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function (event) {

                event.preventDefault();


                /*
                 * Prevent double-click navigation.
                 */
                if (
                    button.dataset.processing === 'true'
                ) {

                    return;

                }


                button.dataset.processing =
                    'true';


                try {

                    /*
                     * Validate the currently visible step.
                     */
                    if (
                        !validateSection(
                            currentStep
                        )
                    ) {

                        return;

                    }


                    /*
                     * Move forward.
                     */
                    if (
                        currentStep <
                        sections.length
                    ) {

                        showStep(
                            currentStep + 1
                        );

                    }

                } finally {

                    button.dataset.processing =
                        'false';

                }

            }
        );

    });


    /* =========================================================
       PREVIOUS BUTTONS
    ========================================================== */

    prevButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function (event) {

                event.preventDefault();


                if (
                    currentStep > 1
                ) {

                    showStep(
                        currentStep - 1
                    );

                }

            }
        );

    });


    /* =========================================================
       STEP NAVIGATION
    ========================================================== */

    steps.forEach(function (stepElement) {

        stepElement.addEventListener(
            'click',
            function () {

                const requestedStep =
                    Number(
                        stepElement.dataset.step
                    );


                if (
                    Number.isNaN(
                        requestedStep
                    )
                ) {

                    return;

                }


                /*
                 * Already on this step.
                 */
                if (
                    requestedStep ===
                    currentStep
                ) {

                    return;

                }


                /*
                 * Going backwards is always allowed.
                 */
                if (
                    requestedStep <
                    currentStep
                ) {

                    showStep(
                        requestedStep
                    );

                    return;

                }


                /*
                 * Going forwards requires all preceding
                 * sections to be valid.
                 */
                if (
                    requestedStep >
                    currentStep
                ) {

                    if (
                        validateBeforeStep(
                            requestedStep
                        )
                    ) {

                        showStep(
                            requestedStep
                        );

                    }

                }

            }
        );

    });


    /* =========================================================
       SUMMARY
    ========================================================== */

    function updateSummary() {

        const nameElement =
            document.getElementById(
                'company_name'
            );

        const industryElement =
            document.getElementById(
                'company_industry'
            );

        const stateElement =
            document.getElementById(
                'stateSelect'
            );

        const cityElement =
            document.getElementById(
                'citySelect'
            );

        const emailElement =
            document.getElementById(
                'company_email'
            );


        const name =
            nameElement
                ? nameElement.value.trim()
                : '';


        const industry =
            industryElement
                ? industryElement.value
                : '';


        const state =
            stateElement
                ? stateElement.value
                : '';


        const city =
            cityElement
                ? cityElement.value
                : '';


        const email =
            emailElement
                ? emailElement.value.trim()
                : '';


        const sumName =
            document.getElementById(
                'sumName'
            );

        const sumIndustry =
            document.getElementById(
                'sumIndustry'
            );

        const sumLocation =
            document.getElementById(
                'sumLocation'
            );

        const sumEmail =
            document.getElementById(
                'sumEmail'
            );


        if (sumName) {

            sumName.textContent =
                name || 'Not provided';

        }


        if (sumIndustry) {

            sumIndustry.textContent =
                industry || 'Not provided';

        }


        if (sumLocation) {

            if (state && city) {

                sumLocation.textContent =
                    city + ', ' + state;

            }

            else if (state) {

                sumLocation.textContent =
                    state;

            }

            else {

                sumLocation.textContent =
                    'Not provided';

            }

        }


        if (sumEmail) {

            sumEmail.textContent =
                email || 'Not provided';

        }

    }


    /* =========================================================
       SUMMARY LISTENERS
    ========================================================== */

    const summaryFieldIds = [

        'company_name',
        'company_industry',
        'company_email',
        'company_mobile',
        'company_office_address',
        'company_head_office',
        'company_customer_care',
        'company_support_email',
        'stateSelect',
        'citySelect'

    ];


    summaryFieldIds.forEach(function (id) {

        const element =
            document.getElementById(id);


        if (!element) {
            return;
        }


        element.addEventListener(
            'input',
            updateSummary
        );


        element.addEventListener(
            'change',
            updateSummary
        );

    });


    /* =========================================================
       LOGO UPLOAD
    ========================================================== */

    const logoInput =
        document.getElementById(
            'company_logo'
        );

    const uploadBox =
        document.getElementById(
            'uploadBox'
        );

    const uploadPreview =
        document.getElementById(
            'uploadPreview'
        );

    const previewImage =
        document.getElementById(
            'previewImage'
        );

    const previewName =
        document.getElementById(
            'previewName'
        );


    function previewLogo(file) {

        if (!file) {
            return;
        }


        const allowedTypes = [

            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp'

        ];


        if (
            !allowedTypes.includes(
                file.type
            )
        ) {

            toast(
                'Please select a PNG, JPG, JPEG or WEBP logo.'
            );


            if (logoInput) {
                logoInput.value = '';
            }


            if (uploadPreview) {

                uploadPreview.style.display =
                    'none';

            }


            return;

        }


        if (
            file.size >
            2 * 1024 * 1024
        ) {

            toast(
                'Company logo must not exceed 2MB.'
            );


            if (logoInput) {
                logoInput.value = '';
            }


            if (uploadPreview) {

                uploadPreview.style.display =
                    'none';

            }


            return;

        }


        const reader =
            new FileReader();


        reader.onload =
            function (event) {

                if (previewImage) {

                    previewImage.src =
                        event.target.result;

                }


                if (previewName) {

                    previewName.textContent =
                        file.name;

                }


                if (uploadPreview) {

                    uploadPreview.style.display =
                        'flex';

                }


                if (logoInput) {

                    validateField(
                        logoInput
                    );

                }

            };


        reader.onerror =
            function () {

                toast(
                    'Unable to preview the selected logo.'
                );

            };


        reader.readAsDataURL(file);

    }


    if (logoInput) {

        logoInput.addEventListener(
            'change',
            function () {

                previewLogo(
                    logoInput.files &&
                    logoInput.files.length
                        ? logoInput.files[0]
                        : null
                );

            }
        );

    }


    /* =========================================================
       DRAG & DROP
    ========================================================== */

    if (uploadBox) {

        ['dragenter', 'dragover']
            .forEach(function (eventName) {

                uploadBox.addEventListener(
                    eventName,
                    function (event) {

                        event.preventDefault();
                        event.stopPropagation();

                        uploadBox.classList.add(
                            'dragging'
                        );

                    }
                );

            });


        ['dragleave', 'drop']
            .forEach(function (eventName) {

                uploadBox.addEventListener(
                    eventName,
                    function (event) {

                        event.preventDefault();
                        event.stopPropagation();

                        uploadBox.classList.remove(
                            'dragging'
                        );

                    }
                );

            });


        uploadBox.addEventListener(
            'drop',
            function (event) {

                const files =
                    event.dataTransfer &&
                    event.dataTransfer.files;


                if (
                    !files ||
                    !files.length
                ) {

                    return;

                }


                /*
                 * Try assigning the dropped file to the
                 * actual input.
                 */
                try {

                    if (logoInput) {

                        const dataTransfer =
                            new DataTransfer();

                        dataTransfer.items.add(
                            files[0]
                        );

                        logoInput.files =
                            dataTransfer.files;

                    }

                } catch (error) {

                    console.warn(
                        'Could not assign dropped file:',
                        error
                    );

                }


                previewLogo(
                    files[0]
                );

            }
        );

    }


    /* =========================================================
       PASSWORD STRENGTH
    ========================================================== */

    function passwordStrength(value) {

        let score = 0;


        if (value.length >= 8) {
            score++;
        }


        if (/[A-Z]/.test(value)) {
            score++;
        }


        if (/[a-z]/.test(value)) {
            score++;
        }


        if (/[0-9]/.test(value)) {
            score++;
        }


        if (/[^A-Za-z0-9]/.test(value)) {
            score++;
        }


        return score;

    }


    const carePassword =
        document.getElementById(
            'customer_care_password'
        );

    const careMeter =
        document.getElementById(
            'careMeter'
        );

    const carePasswordLabel =
        document.getElementById(
            'carePasswordLabel'
        );


    if (carePassword) {

        carePassword.addEventListener(
            'input',
            function () {

                const value =
                    carePassword.value;

                const score =
                    passwordStrength(
                        value
                    );


                if (careMeter) {

                    careMeter.style.width =
                        (score * 20) + '%';

                }


                if (!carePasswordLabel) {
                    return;
                }


                if (!value) {

                    carePasswordLabel.textContent =
                        'Minimum 8 characters required';

                }

                else if (score <= 2) {

                    carePasswordLabel.textContent =
                        'Basic password — improve it';

                }

                else if (score <= 3) {

                    carePasswordLabel.textContent =
                        'Moderate password';

                }

                else {

                    carePasswordLabel.textContent =
                        'Strong password';

                }

            }
        );

    }


    /* =========================================================
       SUPPORT PASSWORD LIVE VALIDATION
    ========================================================== */

    const supportPassword =
        document.getElementById(
            'support_email_password'
        );


    if (supportPassword) {

        supportPassword.addEventListener(
            'input',
            function () {

                if (
                    supportPassword.value.length >= 8
                ) {

                    supportPassword.classList.remove(
                        'is-invalid'
                    );

                    supportPassword.classList.add(
                        'is-valid'
                    );

                }

                else {

                    supportPassword.classList.remove(
                        'is-valid'
                    );

                    if (
                        supportPassword.value.length > 0
                    ) {

                        supportPassword.classList.add(
                            'is-invalid'
                        );

                    }

                }

            }
        );

    }


    /* =========================================================
       LIVE FIELD VALIDATION
    ========================================================== */

    const allFields =
        Array.from(
            form.querySelectorAll(
                'input, select, textarea'
            )
        );


    allFields.forEach(function (field) {

        field.addEventListener(
            'blur',
            function () {

                if (
                    field.hasAttribute(
                        'required'
                    )
                ) {

                    validateField(
                        field
                    );

                }

            }
        );


        field.addEventListener(
            'input',
            function () {

                if (
                    field.classList.contains(
                        'is-invalid'
                    )
                ) {

                    validateField(
                        field
                    );

                }

            }
        );


        field.addEventListener(
            'change',
            function () {

                if (
                    field.classList.contains(
                        'is-invalid'
                    )
                ) {

                    validateField(
                        field
                    );

                }

            }
        );

    });


    /* =========================================================
       LEGAL POLICY / TERMS MODALS
    ========================================================== */

    function openLegalModal(type) {

        const modal =
            type === 'privacy'
                ? privacyPolicyModal
                : termsConditionsModal;

        if (!modal) {
            return;
        }

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');

        const body = modal.querySelector('.legal-modal-body');

        if (body) {
            const alreadyRead =
                type === 'privacy'
                    ? privacyHasBeenRead
                    : termsHasBeenRead;

            if (!alreadyRead) {
                body.scrollTop = 0;
            }

            requestAnimationFrame(function () {
                checkLegalDocumentReachedBottom(type);
            });
        }

        document.body.style.overflow = 'hidden';

    }


    function closeLegalModal(type) {

        const modal =
            type === 'privacy'
                ? privacyPolicyModal
                : termsConditionsModal;

        if (!modal) {
            return;
        }

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');

        if (
            !document.querySelector(
                '.legal-modal.active'
            )
        ) {
            document.body.style.overflow = '';
        }

    }


    const openPrivacyPolicy =
        document.getElementById(
            'openPrivacyPolicy'
        );

    const openTermsConditions =
        document.getElementById(
            'openTermsConditions'
        );


    if (openPrivacyPolicy) {

        openPrivacyPolicy.addEventListener(
            'click',
            function () {

                openLegalModal('privacy');

            }
        );

    }


    if (openTermsConditions) {

        openTermsConditions.addEventListener(
            'click',
            function () {

                openLegalModal('terms');

            }
        );

    }


    document.querySelectorAll(
        '.legal-link-trigger'
    ).forEach(function (link) {

        link.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                openLegalModal(
                    link.dataset.legal
                );

            }
        );

    });


    document.querySelectorAll(
        '[data-close-legal]'
    ).forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                closeLegalModal(
                    button.dataset.closeLegal
                );

            }
        );

    });


    [privacyPolicyModal, termsConditionsModal]
        .forEach(function (modal) {

            if (!modal) {
                return;
            }

            modal.addEventListener(
                'click',
                function (event) {

                    if (
                        event.target === modal
                    ) {

                        modal.classList.remove(
                            'active'
                        );

                        modal.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                        document.body.style.overflow =
                            '';

                    }

                }
            );

        });


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
            ) {

                closeLegalModal('privacy');
                closeLegalModal('terms');

            }

        }
    );


    /* =========================================================
       DETECT READING TO THE END OF EACH LEGAL DOCUMENT
    ========================================================== */
    const privacyModalBody =
        privacyPolicyModal
            ? privacyPolicyModal.querySelector('.legal-modal-body')
            : null;

    const termsModalBody =
        termsConditionsModal
            ? termsConditionsModal.querySelector('.legal-modal-body')
            : null;

    if (privacyModalBody) {
        privacyModalBody.addEventListener(
            'scroll',
            function () {
                checkLegalDocumentReachedBottom('privacy');
            },
            { passive: true }
        );
    }

    if (termsModalBody) {
        termsModalBody.addEventListener(
            'scroll',
            function () {
                checkLegalDocumentReachedBottom('terms');
            },
            { passive: true }
        );
    }


    /* =========================================================
       FINAL FORM SUBMISSION
    ========================================================== */

    form.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();


            /*
             * Make sure every section is validated.
             */
            let firstInvalid = null;


            for (
                let i = 0;
                i < allFields.length;
                i++
            ) {

                const field =
                    allFields[i];


                if (field.disabled) {
                    continue;
                }


                if (
                    !validateField(
                        field
                    )
                ) {

                    firstInvalid =
                        field;

                    break;

                }

            }


            /* -------------------------------------------------
               INVALID FIELD FOUND
            ------------------------------------------------- */

            if (firstInvalid) {

                const invalidSection =
                    firstInvalid.closest(
                        '.form-section'
                    );


                if (invalidSection) {

                    const sectionNumber =
                        Number(
                            invalidSection.dataset.section
                        );


                    showStep(
                        sectionNumber
                    );

                }


                setTimeout(
                    function () {

                        try {

                            firstInvalid.focus();

                        } catch (error) {}


                        if (
                            firstInvalid.type !==
                                'file' &&
                            typeof firstInvalid.reportValidity ===
                                'function'
                        ) {

                            firstInvalid.reportValidity();

                        }


                        toast(
                            'Please complete all required fields before creating the company.'
                        );

                    },
                    150
                );


                return;

            }


            /* -------------------------------------------------
               FINAL PASSWORD CHECK
            ------------------------------------------------- */

            const carePasswordValue =
                carePassword
                    ? carePassword.value
                    : '';


            const supportPasswordValue =
                supportPassword
                    ? supportPassword.value
                    : '';


            if (
                carePasswordValue.length < 8
            ) {

                showStep(3);


                toast(
                    'Customer-care password must contain at least 8 characters.'
                );


                if (carePassword) {

                    carePassword.focus();

                }


                return;

            }


            if (
                supportPasswordValue.length < 8
            ) {

                showStep(3);


                toast(
                    'Support email password must contain at least 8 characters.'
                );


                if (supportPassword) {

                    supportPassword.focus();

                }


                return;

            }


            /* -------------------------------------------------
               LEGAL AGREEMENT CHECK
            ------------------------------------------------- */

            if (
                !privacyHasBeenRead ||
                !termsHasBeenRead
            ) {

                showStep(3);

                toast(
                    'Please open and read both the Privacy Policy and Terms & Conditions to the end before agreeing.'
                );

                return;
            }

            if (
                !legalAgreement ||
                legalAgreement.disabled ||
                !legalAgreement.checked
            ) {

                showStep(3);

                toast(
                    'You must agree to the Privacy Policy and Terms & Conditions before creating the company.'
                );

                if (legalAgreement && !legalAgreement.disabled) {
                    legalAgreement.focus();
                }

                return;
            }


            /* -------------------------------------------------
               EVERYTHING PASSED
            ------------------------------------------------- */

            if (submitButton) {

                submitButton.disabled =
                    true;

                submitButton.classList.add(
                    'loading'
                );

                submitButton.innerHTML =
                    '<i class="fa-solid fa-spinner"></i> Creating company...';

            }


            /*
             * IMPORTANT:
             * Use the native HTML form submission.
             *
             * This bypasses this submit event and sends the
             * multipart/form-data request normally to:
             *
             * proceed_registration.php
             */
            HTMLFormElement.prototype.submit.call(
                form
            );

        }
    );


    /* =========================================================
       INITIALIZATION
    ========================================================== */

    try {

        loadStates();

    } catch (error) {

        console.error(
            'State initialization failed:',
            error
        );

    }


    try {

        syncSlug();

        updateSummary();

        showStep(1);

    } catch (error) {

        console.error(
            'Registration wizard initialization failed:',
            error
        );

        /*
         * Last-resort recovery:
         * Make sure Step 1 is visible even if a non-critical
         * initialization component fails.
         */
        sections.forEach(function (section) {

            section.classList.toggle(
                'active',
                Number(section.dataset.section) === 1
            );

        });

    }

});
</script>

</body>
</html>