<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');


if (!isset($_GET['location_id']) || !is_numeric($_GET['location_id'])) {
    exit('Invalid location');
}

$location_id = (int) $_GET['location_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM locations
    WHERE id = ? AND company_id = ?
    LIMIT 1
");

$stmt->execute([
    $location_id,
    $_SESSION['company_id']
]);

$location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    exit('Location not found');
}

$saved_state = $location['state'] ?? '';
$saved_lga   = $location['lga'] ?? '';


// ============================================================
// LOCATION TYPES
// ============================================================

$types = [
    'branch',
    'store',
    'office',
    'showroom',
    'kiosk',
    'pop_up_store',
    'franchise',
    'head_office',
    'warehouse',
    'cold_room',
    'distribution_center',
    'transit_hub',
    'cross_dock',
    'fulfillment_center',
    'depot',
    'shipping_terminal',
    'logistics_hub',
    'storage_yard',
    'factory',
    'production_plant',
    'laboratory',
    'assembly_plant',
    'farm',
    'greenhouse',
    'workshop',
    'mill',
    'foundry',
    'refinery',
    'supplier',
    'vendor_site',
    'drop_off_point',
    'supplier_warehouse',
    'partner_hub',
    'collection_center',
    'dock',
    'port_terminal',
    'airport_hub',
    'truck_yard',
    'vehicle_pool',
    'drone_depot',
    'virtual',
    'cloud_storage',
    'digital_pool',
    'research_center',
    'training_center',
    'exhibition_hall',
    'event_space',
    'service_center',
    'data_center',
    'call_center',
    'archive_room',
    'hospital_ward',
    'clinic',
    'pharmacy',
    'laboratory_site',
    'retail_market',
    'community_center',
    'warehouse_rack_zone',
    'locker_station',
    'cold_storage_unit',
    'packing_station',
    'quality_control_station'
];

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
    <?php echo htmlspecialchars($company_name); ?> | Edit Location
</title>

<link rel="stylesheet" href="../fontawesome/css/all.css">

<link href="style.css" rel="stylesheet">

<style>

    /* =========================================================
       LOCATION EDIT PAGE
    ========================================================= */

    .location-edit-wrapper {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
    }

    .location-edit-shell {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.05);
    }

    .dark .location-edit-shell {
        background: #111827;
        border-color: #1f2937;
    }


    /* =========================================================
       ABSTRACT LOCATION DESIGN
    ========================================================= */

    .location-hero {
        position: relative;
        overflow: hidden;
        min-height: 190px;
        padding: 32px;
        display: flex;
        align-items: center;
        isolation: isolate;
        background:
            linear-gradient(
                135deg,
                rgba(2, 53, 110, 0.08),
                rgba(255, 255, 255, 0.95)
            );
        border-bottom: 1px solid #e5e7eb;
    }

    .dark .location-hero {
        background:
            linear-gradient(
                135deg,
                rgba(30, 64, 175, 0.15),
                rgba(17, 24, 39, 0.98)
            );
        border-bottom-color: #1f2937;
    }

    .location-hero-content {
        position: relative;
        z-index: 5;
        max-width: 700px;
    }

    .location-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        padding: 6px 11px;
        border-radius: 999px;
        background: rgba(2, 53, 110, 0.08);
        color: #02356e;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .dark .location-eyebrow {
        background: rgba(96, 165, 250, 0.12);
        color: #93c5fd;
    }

    .location-hero h1 {
        margin: 0;
        color: #111827;
        font-size: clamp(1.6rem, 3vw, 2.25rem);
        line-height: 1.15;
        font-weight: 800;
    }

    .dark .location-hero h1 {
        color: #f9fafb;
    }

    .location-hero p {
        margin: 10px 0 0;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.6;
        max-width: 600px;
    }

    .dark .location-hero p {
        color: #9ca3af;
    }


    /* Abstract circles */

    .location-orb {
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        z-index: 1;
        filter: blur(1px);
    }

    .location-orb.one {
        width: 230px;
        height: 230px;
        right: -60px;
        top: -90px;
        border: 1px solid rgba(2, 53, 110, 0.18);
        background: rgba(2, 53, 110, 0.035);
    }

    .location-orb.two {
        width: 150px;
        height: 150px;
        right: 120px;
        bottom: -100px;
        border: 1px solid rgba(2, 53, 110, 0.15);
        background: rgba(2, 53, 110, 0.025);
    }

    .location-orb.three {
        width: 75px;
        height: 75px;
        right: 80px;
        top: 65px;
        border: 1px solid rgba(2, 53, 110, 0.2);
        background: rgba(2, 53, 110, 0.035);
    }

    .dark .location-orb.one,
    .dark .location-orb.two,
    .dark .location-orb.three {
        border-color: rgba(96, 165, 250, 0.15);
        background: rgba(96, 165, 250, 0.025);
    }


    /* Location pin abstraction */

    .location-graphic {
        position: absolute;
        right: 55px;
        top: 50%;
        transform: translateY(-50%);
        width: 145px;
        height: 145px;
        z-index: 2;
        opacity: 0.9;
    }

    .location-pin-ring {
        position: absolute;
        inset: 10px;
        border: 1px solid rgba(2, 53, 110, 0.18);
        border-radius: 50%;
    }

    .location-pin-ring::before,
    .location-pin-ring::after {
        content: "";
        position: absolute;
        inset: 20px;
        border: 1px dashed rgba(2, 53, 110, 0.18);
        border-radius: 50%;
    }

    .location-pin-ring::after {
        inset: 42px;
        border-style: solid;
    }

    .location-pin {
        position: absolute;
        width: 46px;
        height: 46px;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -60%) rotate(-45deg);
        border-radius: 50% 50% 50% 0;
        background: rgba(2, 53, 110, 0.08);
        border: 2px solid rgba(2, 53, 110, 0.35);
    }

    .location-pin::after {
        content: "";
        position: absolute;
        width: 12px;
        height: 12px;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        background: #02356e;
    }

    .dark .location-pin {
        background: rgba(96, 165, 250, 0.08);
        border-color: rgba(96, 165, 250, 0.3);
    }

    .dark .location-pin::after {
        background: #93c5fd;
    }


    /* =========================================================
       FORM AREA
    ========================================================= */

    .location-form-area {
        padding: 28px;
    }

    .location-form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }

    .location-form-header h2 {
        margin: 0;
        color: #111827;
        font-size: 18px;
        font-weight: 700;
    }

    .dark .location-form-header h2 {
        color: #f9fafb;
    }

    .location-id-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 11px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #6b7280;
        font-size: 12px;
        font-weight: 600;
    }

    .dark .location-id-badge {
        background: #1f2937;
        color: #9ca3af;
    }


    .location-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .location-field {
        min-width: 0;
    }

    .location-field.full {
        grid-column: 1 / -1;
    }

    .location-label {
        display: block;
        margin-bottom: 7px;
        color: #374151;
        font-size: 13px;
        font-weight: 600;
    }

    .dark .location-label {
        color: #d1d5db;
    }

    .location-input,
    .location-select {
        width: 100%;
        min-height: 46px;
        box-sizing: border-box;
        padding: 11px 14px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        color: #111827;
        font-size: 14px;
        outline: none;
        transition:
            border-color 0.2s ease,
            box-shadow 0.2s ease,
            background 0.2s ease;
    }

    .location-input:focus,
    .location-select:focus {
        border-color: #02356e;
        box-shadow: 0 0 0 3px rgba(2, 53, 110, 0.10);
    }

    .dark .location-input,
    .dark .location-select {
        border-color: #374151;
        background: #111827;
        color: #f9fafb;
    }

    .dark .location-input:focus,
    .dark .location-select:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.10);
    }


    /* =========================================================
       FORM ACTIONS
    ========================================================= */

    .location-actions {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding-top: 8px;
        margin-top: 4px;
        border-top: 1px solid #e5e7eb;
    }

    .dark .location-actions {
        border-top-color: #1f2937;
    }

    .location-back-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 10px 16px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        color: #374151;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s ease;
    }

    .location-back-btn:hover {
        background: #f9fafb;
    }

    .dark .location-back-btn {
        border-color: #374151;
        background: #111827;
        color: #d1d5db;
    }

    .location-update-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 44px;
        padding: 10px 18px;
        border: 0;
        border-radius: 10px;
        background: #02356e;
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease,
            background 0.2s ease;
    }

    .location-update-btn:hover {
        background: #034a98;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(2, 53, 110, 0.18);
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 900px) {

        .location-graphic {
            right: 25px;
            transform: translateY(-50%) scale(0.85);
        }

        .location-hero-content {
            max-width: 65%;
        }

    }


    @media (max-width: 767px) {

        .location-hero {
            min-height: 175px;
            padding: 24px 20px;
        }

        .location-hero-content {
            max-width: 100%;
            padding-right: 5px;
        }

        .location-hero h1 {
            font-size: 1.55rem;
        }

        .location-hero p {
            font-size: 13px;
            max-width: 90%;
        }

        .location-graphic {
            opacity: 0.25;
            right: -20px;
            transform: translateY(-50%) scale(0.9);
        }

        .location-orb.one {
            right: -100px;
        }

        .location-form-area {
            padding: 20px 16px;
        }

        .location-form-header {
            align-items: flex-start;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .location-form-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .location-field.full {
            grid-column: auto;
        }

        .location-actions {
            grid-column: auto;
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .location-back-btn,
        .location-update-btn {
            width: 100%;
        }

    }


    @media (max-width: 480px) {

        .location-hero {
            padding: 20px 16px;
        }

        .location-eyebrow {
            font-size: 10px;
        }

        .location-hero h1 {
            font-size: 1.4rem;
        }

        .location-hero p {
            font-size: 12px;
            line-height: 1.5;
        }

        .location-form-area {
            padding: 18px 14px;
        }

        .location-id-badge {
            font-size: 11px;
        }

        .location-input,
        .location-select {
            min-height: 44px;
            font-size: 13px;
        }

    }

</style>


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
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>

<!-- =========================================================
     PAGE WRAPPER
========================================================= -->

<div class="flex h-screen overflow-hidden">

<!-- Sidebar -->

<?php include('sidebar.php'); ?>


<!-- Content Area -->

<div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">


    <!-- Header -->

    <?php include('header.php'); ?>


    <!-- Main -->

    <main>

        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

            <div class="location-edit-wrapper">


                <!-- =================================================
                     LOCATION EDIT CARD
                ================================================= -->

                <div class="location-edit-shell">


                    <!-- =================================================
                         ABSTRACT LOCATION HERO
                    ================================================= -->

                    <div class="location-hero">


                        <!-- Abstract shapes -->

                        <div class="location-orb one"></div>
                        <div class="location-orb two"></div>
                        <div class="location-orb three"></div>


                        <!-- Location graphic -->

                        <div class="location-graphic">

                            <div class="location-pin-ring"></div>

                            <div class="location-pin"></div>

                        </div>


                        <!-- Hero text -->

                        <div class="location-hero-content">

                            <div class="location-eyebrow">

                                <i class="fa-solid fa-location-dot"></i>

                                Location Management

                            </div>

                            <h1>
                                Edit Location
                            </h1>

                            <p>
                                Update the information, address and classification
                                of this business location. Changes will apply to
                                the selected location within your company.
                            </p>

                        </div>


                    </div>


                    <!-- =================================================
                         FORM
                    ================================================= -->

                    <div class="location-form-area">


                        <div class="location-form-header">

                            <div>

                                <h2>
                                    Location Information
                                </h2>

                            </div>

                            <div class="location-id-badge">

                                <i class="fa-solid fa-hashtag"></i>

                                Location ID:
                                <?= htmlspecialchars($location['id']) ?>

                            </div>

                        </div>


                        <form
                            method="POST"
                            action="proceed-location-edit.php"
                        >

                            <div class="location-form-grid">


                                <!-- Hidden ID -->

                                <input
                                    type="hidden"
                                    name="location_id"
                                    value="<?= htmlspecialchars($location['id']) ?>"
                                >


                                <!-- =================================================
                                     LOCATION TYPE
                                ================================================= -->

                                <div class="location-field">

                                    <label
                                        for="type"
                                        class="location-label"
                                    >
                                        Location Type
                                    </label>

                                    <select
                                        id="type"
                                        name="type"
                                        required
                                        class="location-select"
                                    >

                                        <option value="">
                                            -- Select Location Type --
                                        </option>

                                        <?php foreach ($types as $type): ?>

                                            <option
                                                value="<?= htmlspecialchars($type) ?>"
                                                <?= ($location['type'] === $type) ? 'selected' : '' ?>
                                            >
                                                <?= htmlspecialchars(
                                                    ucwords(str_replace('_', ' ', $type))
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>


                                <!-- =================================================
                                     LOCATION NAME
                                ================================================= -->

                                <div class="location-field">

                                    <label
                                        for="name"
                                        class="location-label"
                                    >
                                        Location Name
                                    </label>

                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        required
                                        value="<?= htmlspecialchars($location['name'] ?? '') ?>"
                                        class="location-input"
                                        placeholder="Enter location name"
                                    >

                                </div>


                                <!-- =================================================
                                     ADDRESS 1
                                ================================================= -->

                                <div class="location-field">

                                    <label
                                        for="address_line1"
                                        class="location-label"
                                    >
                                        Address 1
                                    </label>

                                    <input
                                        type="text"
                                        id="address_line1"
                                        name="address_line1"
                                        required
                                        value="<?= htmlspecialchars($location['address_line1'] ?? '') ?>"
                                        class="location-input"
                                        placeholder="Primary address"
                                    >

                                </div>


                                <!-- =================================================
                                     ADDRESS 2
                                ================================================= -->

                                <div class="location-field">

                                    <label
                                        for="address_line2"
                                        class="location-label"
                                    >
                                        Address 2
                                    </label>

                                    <input
                                        type="text"
                                        id="address_line2"
                                        name="address_line2"
                                        value="<?= htmlspecialchars($location['address_line2'] ?? '') ?>"
                                        class="location-input"
                                        placeholder="Additional address information"
                                    >

                                </div>


                                <!-- =================================================
                                     STATE
                                ================================================= -->

                                <div class="location-field">

                                    <label
                                        for="state"
                                        class="location-label"
                                    >
                                        State
                                    </label>

                                    <select
                                        id="state"
                                        name="state"
                                        required
                                        class="location-select"
                                    >

                                        <option value="">
                                            -- Select State --
                                        </option>

                                    </select>

                                </div>


                                <!-- =================================================
                                     LGA
                                ================================================= -->

                                <div class="location-field">

                                    <label
                                        for="lga"
                                        class="location-label"
                                    >
                                        Local Government Area
                                    </label>

                                    <select
                                        id="lga"
                                        name="lga"
                                        required
                                        class="location-select"
                                    >

                                        <option value="">
                                            -- Select LGA --
                                        </option>

                                    </select>

                                </div>


                                <!-- =================================================
                                     CITY / TOWN
                                ================================================= -->

                                <div class="location-field">

                                    <label
                                        for="city"
                                        class="location-label"
                                    >
                                        City / Town
                                    </label>

                                    <input
                                        type="text"
                                        id="city"
                                        name="city"
                                        required
                                        value="<?= htmlspecialchars($location['city'] ?? '') ?>"
                                        class="location-input"
                                        placeholder="Enter city or town"
                                    >

                                </div>


                                <!-- =================================================
                                     ACTIONS
                                ================================================= -->

                                <div class="location-actions">


                                    <a
                                        href="locations.php"
                                        class="location-back-btn"
                                    >
                                        <i class="fa-solid fa-arrow-left"></i>
                                        Back to Locations
                                    </a>


                                    <button
                                        type="submit"
                                        class="location-update-btn"
                                    >
                                        <i class="fa-solid fa-location-dot"></i>
                                        Update Location
                                    </button>


                                </div>


                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </main>


    <!-- Footer -->

    <?php include('footer.php'); ?>


</div>


</div>

<!-- =========================================================
     BUNDLE
========================================================= -->

<script defer src="bundle.js"></script>

<!-- =========================================================
     NIGERIA STATES + LGAs
========================================================= -->

<script>

const nigeriaLGAs = {

  "Abia": ["Aba North","Aba South","Arochukwu","Bende","Isiala Ngwa North","Isiala Ngwa South","Ikwuano","Isuikwuato","Obi Ngwa","Ohafia","Osisioma","Ugwunagbo","Ukwa East","Ukwa West","Umuahia North","Umuahia South","Umu Nneochi"],

  "Adamawa": ["Demsa","Fufore","Ganye","Gayuk","Gombi","Grie","Hong","Jada","Lamurde","Madagali","Maiha","Mayo-Belwa","Michika","Mubi North","Mubi South","Numan","Shelleng","Song","Toungo","Yola North","Yola South"],

  "Akwa Ibom": ["Abak","Eastern Obolo","Eket","Esit Eket","Essien Udim","Etim Ekpo","Etinan","Ibeno","Ibesikpo Asutan","Ibiono-Ibom","Ika","Ikono","Ikot Abasi","Ikot Ekpene","Ini","Itu","Mbo","Mkpat-Enin","Nsit-Atai","Nsit-Ibom","Nsit-Ubium","Obot Akara","Okobo","Onna","Oron","Oruk Anam","Udung-Uko","Ukanafun","Uruan","Urue-Offong/Oruko","Uyo"],

  "Anambra": ["Aguata","Anambra East","Anambra West","Anaocha","Awka North","Awka South","Ayamelum","Dunukofia","Ekwusigo","Idemili North","Idemili South","Ihiala","Njikoka","Nnewi North","Nnewi South","Ogbaru","Onitsha North","Onitsha South","Orumba North","Orumba South","Oyi"],

  "Bauchi": ["Alkaleri","Bauchi","Bogoro","Damban","Darazo","Dass","Gamawa","Ganjuwa","Giade","Itas/Gadau","Jama'are","Katagum","Kirfi","Misau","Ningi","Shira","Tafawa Balewa","Toro","Warji","Zaki"],

  "Bayelsa": ["Brass","Ekeremor","Kolokuma/Opokuma","Nembe","Ogbia","Sagbama","Southern Ijaw","Yenagoa"],

  "Benue": ["Ado","Agatu","Apa","Buruku","Gboko","Guma","Gwer East","Gwer West","Katsina-Ala","Konshisha","Kwande","Logo","Makurdi","Obi","Ogbadibo","Ohimini","Oju","Okpokwu","Oturkpo","Tarka","Ukum","Ushongo","Vandeikya"],

  "Borno": ["Abadam","Askira/Uba","Bama","Bayo","Biu","Chibok","Damboa","Dikwa","Gubio","Guzamala","Gwoza","Hawul","Jere","Kaga","Kala/Balge","Konduga","Kukawa","Kwaya Kusar","Mafa","Magumeri","Maiduguri","Marte","Mobbar","Monguno","Ngala","Nganzai","Shani"],

  "Cross River": ["Abi","Akamkpa","Akpabuyo","Bakassi","Bekwarra","Biase","Boki","Calabar Municipal","Calabar South","Etung","Ikom","Obanliku","Obubra","Obudu","Odukpani","Ogoja","Yakurr","Yala"],

  "Delta": ["Aniocha North","Aniocha South","Bomadi","Burutu","Ethiope East","Ethiope West","Ika North East","Ika South","Isoko North","Isoko South","Ndokwa East","Ndokwa West","Okpe","Oshimili North","Oshimili South","Patani","Sapele","Udu","Ughelli North","Ughelli South","Ukwuani","Uvwie","Warri North","Warri South","Warri South West"],

  "Ebonyi": ["Abakaliki","Afikpo North","Afikpo South","Ebonyi","Ezza North","Ezza South","Ikwo","Ishielu","Ivo","Izzi","Ohaukwu","Onicha"],

  "Edo": ["Akoko-Edo","Egor","Esan Central","Esan North-East","Esan South-East","Esan West","Etsako Central","Etsako East","Etsako West","Igueben","Ikpoba-Okha","Orhionmwon","Oredo","Ovia North-East","Ovia South-West","Owan East","Owan West","Uhunmwonde"],

  "Ekiti": ["Ado-Ekiti","Efon","Ekiti East","Ekiti South-West","Ekiti West","Emure","Gbonyin","Ido-Osi","Ijero","Ikere","Ikole","Ilejemeje","Irepodun/Ifelodun","Ise-Orun","Moba","Oye"],

  "Enugu": ["Aninri","Awgu","Enugu East","Enugu North","Enugu South","Ezeagu","Igbo-Etiti","Igbo-Eze North","Igbo-Eze South","Isi-Uzo","Nkanu East","Nkanu West","Nsukka","Oji River","Udenu","Udi","Uzo-Uwani"],

  "FCT": ["Abaji","Bwari","Gwagwalada","Kuje","Kwali","Municipal Area Council"],

  "Gombe": ["Akko","Balanga","Billiri","Dukku","Funakaye","Gombe","Kaltungo","Kwami","Nafada","Shongom","Yamaltu/Deba"],

  "Imo": ["Aboh-Mbaise","Ahiazu-Mbaise","Ehime-Mbano","Ezinihitte","Ideato North","Ideato South","Ihitte/Uboma","Ikeduru","Isiala Mbano","Isu","Mbaitoli","Ngor Okpala","Njaba","Nkwerre","Nwangele","Obowo","Oguta","Ohaji/Egbema","Okigwe","Orlu","Orsu","Oru East","Oru West","Owerri Municipal","Owerri North","Owerri West"],

  "Jigawa": ["Auyo","Babura","Biriniwa","Birnin Kudu","Buji","Dutse","Gagarawa","Garki","Gumel","Guri","Gwaram","Hadejia","Jahun","Kafin Hausa","Kazaure","Kiri Kasama","Kiyawa","Maigatari","Malam Madori","Miga","Ringim","Roni","Sule Tankarkar","Taura","Yankwashi"],

  "Kaduna": ["Birnin Gwari","Chikun","Giwa","Igabi","Ikara","Jaba","Jema'a","Kachia","Kaduna North","Kaduna South","Kagarko","Kajuru","Kaura","Kauru","Kubau","Kudan","Lere","Makarfi","Sabon Gari","Sanga","Soba","Zangon Kataf","Zaria"],

  "Kano": ["Ajingi","Albasu","Bagwai","Bebeji","Bichi","Bunkure","Dala","Dambatta","Dawakin Kudu","Dawakin Tofa","Doguwa","Fagge","Gabasawa","Garko","Garun Mallam","Gaya","Gezawa","Gwale","Gwarzo","Kabo","Kano Municipal","Karaye","Kibiya","Kiru","Kumbotso","Kunchi","Kura","Madobi","Makoda","Minjibir","Nasarawa","Rano","Rimin Gado","Rogo","Shanono","Sumaila","Takai","Tarauni","Tofa","Tsanyawa","Tudun Wada","Ungogo","Warawa","Wudil"],

  "Katsina": ["Bakori","Batagarawa","Batsari","Baure","Bindawa","Charanchi","Dan Musa","Dandume","Danja","Daura","Dutsi","Dutsin-Ma","Faskari","Funtua","Ingawa","Jibia","Kafur","Kaita","Kankara","Kankia","Katsina","Kurfi","Kusada","Mai'Adua","Malumfashi","Mani","Mashi","Matazu","Musawa","Rimi","Sabuwa","Safana","Sandamu","Zango"],

  "Kebbi": ["Aleiro","Arewa Dandi","Argungu","Augie","Bagudo","Birnin Kebbi","Bunza","Dandi","Fakai","Gwandu","Jega","Kalgo","Koko/Besse","Maiyama","Ngaski","Sakaba","Shanga","Suru","Wasagu/Danko","Yauri","Zuru"],

  "Kogi": ["Adavi","Ajaokuta","Ankpa","Bassa","Dekina","Ibaji","Idah","Igalamela-Odolu","Ijumu","Kabba/Bunu","Kogi","Lokoja","Mopa-Muro","Ofu","Ogori/Magongo","Okehi","Okene","Olamaboro","Omala","Yagba East","Yagba West"],

  "Kwara": ["Asa","Baruten","Edu","Ekiti","Ifelodun","Ilorin East","Ilorin South","Ilorin West","Irepodun","Isin","Kaiama","Moro","Offa","Oke Ero","Oyun","Pategi"],

  "Lagos": ["Agege","Ajeromi-Ifelodun","Alimosho","Amuwo-Odofin","Apapa","Badagry","Epe","Eti-Osa","Ibeju-Lekki","Ifako-Ijaiye","Ikeja","Ikorodu","Kosofe","Lagos Island","Lagos Mainland","Mushin","Ojo","Oshodi-Isolo","Shomolu","Surulere"],

  "Nasarawa": ["Akwanga","Awe","Doma","Karu","Keana","Keffi","Kokona","Lafia","Nasarawa","Nasarawa Egon","Obi","Toto","Wamba"],

  "Niger": ["Agaie","Agwara","Bida","Borgu","Bosso","Chanchaga","Edati","Gbako","Gurara","Katcha","Kontagora","Lapai","Lavun","Magama","Mariga","Mashegu","Mokwa","Muya","Pailoro","Rafi","Rijau","Shiroro","Suleja","Tafa","Wushishi"],

  "Ogun": ["Abeokuta North","Abeokuta South","Ado-Odo/Ota","Ewekoro","Ifo","Ijebu East","Ijebu North","Ijebu North East","Ijebu Ode","Ikenne","Imeko-Afon","Ipokia","Obafemi-Owode","Odeda","Odogbolu","Ogun Waterside","Remo North","Shagamu"],

  "Ondo": ["Akoko North-East","Akoko North-West","Akoko South-East","Akoko South-West","Akure North","Akure South","Ese-Odo","Idanre","Ifedore","Ilaje","Ile-Oluji/Okeigbo","Irele","Odigbo","Okitipupa","Ondo East","Ondo West","Ose","Owo"],

  "Osun": ["Atakumosa East","Atakumosa West","Ayedaade","Ayedire","Boluwaduro","Boripe","Ede North","Ede South","Egbedore","Ejigbo","Ife Central","Ife East","Ife North","Ife South","Ifedayo","Ifelodun","Ila","Ilesa East","Ilesa West","Irepodun","Irewole","Isokan","Iwo","Obokun","Odo-Otin","Ola Oluwa","Olorunda","Oriade","Orolu","Osogbo"],

  "Oyo": ["Afijio","Akinyele","Atiba","Atisbo","Egbeda","Ibadan North","Ibadan North-East","Ibadan North-West","Ibadan South-East","Ibadan South-West","Ibarapa Central","Ibarapa East","Ibarapa North","Ido","Irepo","Iseyin","Itesiwaju","Iwajowa","Kajola","Lagelu","Ogbomosho North","Ogbomosho South","Ogo Oluwa","Olorunsogo","Oluyole","Ona Ara","Orelope","Ori Ire","Oyo East","Oyo West","Saki East","Saki West","Surulere"],

  "Plateau": ["Barkin Ladi","Bassa","Bokkos","Jos East","Jos North","Jos South","Kanam","Kanke","Langtang North","Langtang South","Mangu","Mikang","Pankshin","Qua'an Pan","Riyom","Shendam","Wase"],

  "Rivers": ["Abua/Odual","Ahoada East","Ahoada West","Akuku-Toru","Andoni","Asari-Toru","Bonny","Degema","Eleme","Emohua","Etche","Gokana","Ikwerre","Khana","Obio/Akpor","Ogba/Egbema/Ndoni","Ogu/Bolo","Okrika","Omuma","Opobo/Nkoro","Oyigbo","Port Harcourt","Tai"],

  "Sokoto": ["Binji","Bodinga","Dange-Shuni","Gada","Goronyo","Gudu","Gwadabawa","Illela","Isa","Kebbe","Kware","Rabah","Sabon Birni","Shagari","Silame","Sokoto North","Sokoto South","Tambuwal","Tangaza","Tureta","Wamako","Wurno","Yabo"],

  "Taraba": ["Ardo Kola","Bali","Donga","Gashaka","Gassol","Ibi","Jalingo","Karim Lamido","Kumi","Lau","Sardauna","Takum","Ussa","Wukari","Yorro","Zing"],

  "Yobe": ["Bade","Bursari","Damaturu","Fika","Fune","Geidam","Gujba","Gulani","Jakusko","Karasuwa","Machina","Nangere","Nguru","Potiskum","Tarmuwa","Yunusari","Yusufari"],

  "Zamfara": ["Anka","Bakura","Birnin Magaji/Kiyaw","Bukkuyum","Bungudu","Gummi","Gusau","Kaura Namoda","Maradun","Maru","Shinkafi","Talata Mafara","Zurmi"]

};


// ============================================================
// POPULATE STATE + LGA
// ============================================================

document.addEventListener("DOMContentLoaded", function () {

    const stateSelect = document.getElementById("state");
    const lgaSelect = document.getElementById("lga");

    const savedState = <?= json_encode($saved_state) ?>;
    const savedLga = <?= json_encode($saved_lga) ?>;


    // --------------------------------------------------------
    // Populate States
    // --------------------------------------------------------

    Object.keys(nigeriaLGAs).forEach(function (state) {

        const option = document.createElement("option");

        option.value = state;
        option.textContent = state;

        if (state === savedState) {
            option.selected = true;
        }

        stateSelect.appendChild(option);

    });


    // --------------------------------------------------------
    // Populate LGAs
    // --------------------------------------------------------

    function populateLGAs(state, selectedLga = "") {

        lgaSelect.innerHTML =
            '<option value="">-- Select LGA --</option>';

        if (!state || !nigeriaLGAs[state]) {
            return;
        }

        nigeriaLGAs[state].forEach(function (lga) {

            const option = document.createElement("option");

            option.value = lga;
            option.textContent = lga;

            if (lga === selectedLga) {
                option.selected = true;
            }

            lgaSelect.appendChild(option);

        });

    }


    // --------------------------------------------------------
    // Initial LGA population for existing location
    // --------------------------------------------------------

    if (savedState) {

        populateLGAs(
            savedState,
            savedLga
        );

    }


    // --------------------------------------------------------
    // State change
    // --------------------------------------------------------

    stateSelect.addEventListener("change", function () {

        populateLGAs(
            this.value
        );

    });

});

</script>

</body>
</html>
