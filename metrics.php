<?php

require_once "config/db.php";

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$totalDonors = 0;
$totalNGOs = 0;
$totalVolunteers = 0;

$totalDonations = 0;
$approvedDonations = 0;
$deliveredDonations = 0;
$pendingDonations = 0;

$totalAssignments = 0;
$completedAssignments = 0;

$totalRequests = 0;
$approvedRequests = 0;


/*
|--------------------------------------------------------------------------
| USER METRICS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        SUM(role = 'donor') AS donors,
        SUM(role = 'ngo') AS ngos,
        SUM(role = 'volunteer') AS volunteers
    FROM users
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $totalDonors = (int)$row['donors'];
    $totalNGOs = (int)$row['ngos'];
    $totalVolunteers = (int)$row['volunteers'];

}


/*
|--------------------------------------------------------------------------
| DONATION METRICS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Approved') AS approved,
        SUM(status = 'Delivered') AS delivered,
        SUM(status = 'Pending') AS pending
    FROM donations
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $totalDonations = (int)$row['total'];
    $approvedDonations = (int)$row['approved'];
    $deliveredDonations = (int)$row['delivered'];
    $pendingDonations = (int)$row['pending'];

}


/*
|--------------------------------------------------------------------------
| VOLUNTEER ASSIGNMENTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Delivered') AS completed
    FROM assignments
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $totalAssignments = (int)$row['total'];
    $completedAssignments = (int)$row['completed'];

}


/*
|--------------------------------------------------------------------------
| NGO REQUESTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Approved') AS approved
    FROM food_requests
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $totalRequests = (int)$row['total'];
    $approvedRequests = (int)$row['approved'];

}


/*
|--------------------------------------------------------------------------
| TOTAL COMMUNITY
|--------------------------------------------------------------------------
*/

$totalPartners =
    $totalDonors +
    $totalNGOs +
    $totalVolunteers;


/*
|--------------------------------------------------------------------------
| DELIVERY RATE
|--------------------------------------------------------------------------
*/

$deliveryRate = 0;

if ($totalDonations > 0) {

    $deliveryRate =
        round(
            ($deliveredDonations / $totalDonations) * 100
        );

}


/*
|--------------------------------------------------------------------------
| VOLUNTEER COMPLETION RATE
|--------------------------------------------------------------------------
*/

$volunteerCompletionRate = 0;

if ($totalAssignments > 0) {

    $volunteerCompletionRate =
        round(
            ($completedAssignments / $totalAssignments) * 100
        );

}


/*
|--------------------------------------------------------------------------
| REQUEST APPROVAL RATE
|--------------------------------------------------------------------------
*/

$requestApprovalRate = 0;

if ($totalRequests > 0) {

    $requestApprovalRate =
        round(
            ($approvedRequests / $totalRequests) * 100
        );

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        F-Destiny | Metrics
    </title>


    <!-- Tailwind -->

    <script src="https://cdn.tailwindcss.com"></script>


    <!-- Anime.js -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            font-family: 'Inter', sans-serif;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            background:

                radial-gradient(
                    circle at 10% 10%,
                    rgba(16,185,129,.12),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 90% 20%,
                    rgba(6,182,212,.10),
                    transparent 30%
                ),

                #f8fafc;

            color: #0f172a;

            overflow-x: hidden;

        }


        /*
        |--------------------------------------------------------------------------
        | GLASS
        |--------------------------------------------------------------------------
        */

        .glass-card {

            background:
                rgba(255,255,255,.68);

            backdrop-filter:
                blur(18px);

            -webkit-backdrop-filter:
                blur(18px);

            border:
                1px solid
                rgba(255,255,255,.75);

            box-shadow:
                0 20px 50px
                rgba(15,23,42,.08);

            transition:
                transform .35s ease,
                box-shadow .35s ease,
                border-color .35s ease;

            transform-style:
                preserve-3d;

        }


        .glass-card:hover {

            box-shadow:
                0 30px 70px
                rgba(15,23,42,.13);

            border-color:
                rgba(16,185,129,.25);

        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .hero-glow {

            position:
                absolute;

            width:
                500px;

            height:
                500px;

            border-radius:
                50%;

            background:
                rgba(16,185,129,.10);

            filter:
                blur(100px);

            pointer-events:
                none;

            z-index:
                -1;

        }


        /*
        |--------------------------------------------------------------------------
        | METRIC ICON
        |--------------------------------------------------------------------------
        */

        .metric-icon {

            width:
                58px;

            height:
                58px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                18px;

            background:
                rgba(16,185,129,.09);

            border:
                1px solid
                rgba(16,185,129,.15);

            color:
                #059669;

        }


        /*
        |--------------------------------------------------------------------------
        | PROGRESS
        |--------------------------------------------------------------------------
        */

        .progress-track {

            width:
                100%;

            height:
                7px;

            border-radius:
                999px;

            background:
                rgba(15,23,42,.07);

            overflow:
                hidden;

        }


        .progress-bar {

            width:
                0%;

            height:
                100%;

            border-radius:
                999px;

            background:
                linear-gradient(
                    90deg,
                    #10b981,
                    #14b8a6
                );

        }


        /*
        |--------------------------------------------------------------------------
        | NAV
        |--------------------------------------------------------------------------
        */

        .nav-glass {

            background:
                rgba(255,255,255,.72);

            backdrop-filter:
                blur(20px);

            -webkit-backdrop-filter:
                blur(20px);

            border-bottom:
                1px solid
                rgba(15,23,42,.06);

        }


        /*
        |--------------------------------------------------------------------------
        | CURSOR LIGHT
        |--------------------------------------------------------------------------
        */

        #cursorGlow {

            position:
                fixed;

            width:
                280px;

            height:
                280px;

            border-radius:
                50%;

            pointer-events:
                none;

            background:
                radial-gradient(
                    circle,
                    rgba(16,185,129,.08),
                    transparent 68%
                );

            transform:
                translate(-50%, -50%);

            z-index:
                1;

        }

    </style>

</head>


<body>


<div id="cursorGlow"></div>


<div
    class="hero-glow"
    style="top:-200px;left:-200px"
></div>


<div
    class="hero-glow"
    style="right:-250px;top:500px"
></div>



<!-- =====================================================
     NAVIGATION
====================================================== -->

<nav
    class="nav-glass sticky top-0 z-50"
>

    <div
        class="max-w-7xl mx-auto px-5 py-4 flex items-center justify-between"
    >

        <a
            href="index.php"
            class="text-2xl font-black tracking-tight"
        >

            <span class="text-emerald-600">
                F-
            </span>

            Destiny

        </a>


        <div
            class="hidden md:flex items-center gap-7 text-sm font-medium"
        >

            <a
                href="index.php"
                class="text-slate-500 hover:text-emerald-600 transition"
            >
                Home
            </a>

            <a
                href="about.php"
                class="text-slate-500 hover:text-emerald-600 transition"
            >
                About
            </a>

            <a
                href="see_partners.php"
                class="text-slate-500 hover:text-emerald-600 transition"
            >
                Partners
            </a>

            <a
                href="impact.php"
                class="text-slate-500 hover:text-emerald-600 transition"
            >
                Impact
            </a>

            <a
                href="metrics.php"
                class="text-emerald-600 font-bold"
            >
                Metrics
            </a>

            <a
                href="join_team.php"
                class="px-5 py-2.5 rounded-full bg-emerald-500 text-white font-semibold hover:bg-emerald-600 transition"
            >
                Join Team
            </a>

        </div>


        <button
            id="menuBtn"
            class="md:hidden text-xl"
        >
            ☰
        </button>

    </div>


    <div
        id="mobileMenu"
        class="hidden md:hidden px-5 pb-5"
    >

        <div
            class="glass-card rounded-2xl p-5 space-y-4"
        >

            <a
                href="index.php"
                class="block"
            >
                Home
            </a>

            <a
                href="about.php"
                class="block"
            >
                About
            </a>

            <a
                href="see_partners.php"
                class="block"
            >
                Partners
            </a>

            <a
                href="impact.php"
                class="block"
            >
                Impact
            </a>

            <a
                href="metrics.php"
                class="block text-emerald-600 font-bold"
            >
                Metrics
            </a>

            <a
                href="join_team.php"
                class="block text-emerald-600 font-bold"
            >
                Join Team
            </a>

        </div>

    </div>

</nav>



<!-- =====================================================
     HERO
====================================================== -->

<main>

<section
    class="max-w-7xl mx-auto px-5 pt-20 pb-14"
>

    <div
        class="max-w-4xl mx-auto text-center"
    >

        <div
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-card text-emerald-600 text-sm font-semibold mb-6"
        >

            <span
                class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"
            ></span>

            Live F-Destiny Network

        </div>


        <h1
            class="text-5xl md:text-7xl font-black tracking-tight text-slate-900"
        >

            F-Destiny

            <span
                class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent"
            >
                Metrics
            </span>

        </h1>


        <p
            class="mt-6 text-lg md:text-xl text-slate-500 max-w-3xl mx-auto leading-relaxed"
        >

            A live snapshot of the people, donations,
            requests and deliveries powering the F-Destiny
            food redistribution network.

        </p>


        <p
            class="mt-4 text-sm text-slate-400"
        >

            Numbers shown here are calculated directly
            from registered activity in the F-Destiny database.

        </p>

    </div>

</section>



<!-- =====================================================
     COMMUNITY METRICS
====================================================== -->

<section
    class="max-w-7xl mx-auto px-5 pb-8"
>

    <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5"
    >


        <!-- DONORS -->

        <div
            class="metric-card glass-card rounded-3xl p-6"
        >

            <div
                class="flex items-start justify-between"
            >

                <div
                    class="metric-icon"
                >

                    🤝

                </div>

                <span
                    class="text-xs font-semibold text-emerald-600 bg-emerald-500/10 px-3 py-1 rounded-full"
                >
                    Donors
                </span>

            </div>


            <div class="mt-7">

                <div
                    class="metric-counter text-4xl font-black text-slate-900"
                    data-value="<?= $totalDonors ?>"
                >
                    0
                </div>

                <h3
                    class="font-bold mt-2"
                >
                    Food Contributors
                </h3>

                <p
                    class="text-sm text-slate-500 mt-2 leading-relaxed"
                >
                    Registered community members
                    contributing surplus food.
                </p>

            </div>

        </div>



        <!-- NGOs -->

        <div
            class="metric-card glass-card rounded-3xl p-6"
        >

            <div
                class="flex items-start justify-between"
            >

                <div
                    class="metric-icon"
                >

                    🏢

                </div>

                <span
                    class="text-xs font-semibold text-cyan-600 bg-cyan-500/10 px-3 py-1 rounded-full"
                >
                    NGOs
                </span>

            </div>


            <div class="mt-7">

                <div
                    class="metric-counter text-4xl font-black text-slate-900"
                    data-value="<?= $totalNGOs ?>"
                >
                    0
                </div>

                <h3
                    class="font-bold mt-2"
                >
                    Community Organizations
                </h3>

                <p
                    class="text-sm text-slate-500 mt-2 leading-relaxed"
                >
                    Organizations connecting available
                    food with community needs.
                </p>

            </div>

        </div>



        <!-- VOLUNTEERS -->

        <div
            class="metric-card glass-card rounded-3xl p-6"
        >

            <div
                class="flex items-start justify-between"
            >

                <div
                    class="metric-icon"
                >

                    🚚

                </div>

                <span
                    class="text-xs font-semibold text-violet-600 bg-violet-500/10 px-3 py-1 rounded-full"
                >
                    Volunteers
                </span>

            </div>


            <div class="mt-7">

                <div
                    class="metric-counter text-4xl font-black text-slate-900"
                    data-value="<?= $totalVolunteers ?>"
                >
                    0
                </div>

                <h3
                    class="font-bold mt-2"
                >
                    Active Network Members
                </h3>

                <p
                    class="text-sm text-slate-500 mt-2 leading-relaxed"
                >
                    People helping move donations
                    through pickup and delivery.
                </p>

            </div>

        </div>



        <!-- COMMUNITY -->

        <div
            class="metric-card glass-card rounded-3xl p-6"
        >

            <div
                class="flex items-start justify-between"
            >

                <div
                    class="metric-icon"
                >

                    🌍

                </div>

                <span
                    class="text-xs font-semibold text-amber-600 bg-amber-500/10 px-3 py-1 rounded-full"
                >
                    Network
                </span>

            </div>


            <div class="mt-7">

                <div
                    class="metric-counter text-4xl font-black text-slate-900"
                    data-value="<?= $totalPartners ?>"
                >
                    0
                </div>

                <h3
                    class="font-bold mt-2"
                >
                    Community Members
                </h3>

                <p
                    class="text-sm text-slate-500 mt-2 leading-relaxed"
                >
                    Combined donor, NGO and volunteer
                    network.
                </p>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     FOOD REDISTRIBUTION METRICS
====================================================== -->

<section
    class="max-w-7xl mx-auto px-5 py-10"
>

    <div
        class="mb-8"
    >

        <p
            class="text-sm uppercase tracking-widest text-emerald-600 font-bold"
        >
            Food Redistribution
        </p>

        <h2
            class="text-3xl md:text-4xl font-black mt-2"
        >
            From surplus to delivery
        </h2>

        <p
            class="text-slate-500 mt-3 max-w-2xl"
        >

            These numbers show how food moves through
            the F-Destiny workflow from donation to delivery.

        </p>

    </div>


    <div
        class="grid md:grid-cols-3 gap-6"
    >


        <!-- TOTAL DONATIONS -->

        <div
            class="glass-card rounded-3xl p-7"
        >

            <div
                class="text-3xl"
            >
                🍱
            </div>

            <div
                class="metric-counter text-4xl font-black mt-5 text-slate-900"
                data-value="<?= $totalDonations ?>"
            >
                0
            </div>

            <h3
                class="font-bold mt-2"
            >
                Donations Registered
            </h3>

            <p
                class="text-sm text-slate-500 mt-3 leading-relaxed"
            >
                Food contributions recorded by
                donors through the platform.
            </p>

        </div>



        <!-- APPROVED -->

        <div
            class="glass-card rounded-3xl p-7"
        >

            <div
                class="text-3xl"
            >
                ✅
            </div>

            <div
                class="metric-counter text-4xl font-black mt-5 text-emerald-600"
                data-value="<?= $approvedDonations ?>"
            >
                0
            </div>

            <h3
                class="font-bold mt-2"
            >
                Approved Donations
            </h3>

            <p
                class="text-sm text-slate-500 mt-3 leading-relaxed"
            >
                Donations that have passed through
                the approval stage.
            </p>

        </div>



        <!-- DELIVERED -->

        <div
            class="glass-card rounded-3xl p-7"
        >

            <div
                class="text-3xl"
            >
                📦
            </div>

            <div
                class="metric-counter text-4xl font-black mt-5 text-teal-600"
                data-value="<?= $deliveredDonations ?>"
            >
                0
            </div>

            <h3
                class="font-bold mt-2"
            >
                Donations Delivered
            </h3>

            <p
                class="text-sm text-slate-500 mt-3 leading-relaxed"
            >
                Donations that completed the
                redistribution journey.
            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     PROGRESS
====================================================== -->

<section
    class="max-w-7xl mx-auto px-5 py-10"
>

    <div
        class="glass-card rounded-[2rem] p-8 md:p-12"
    >

        <div
            class="grid lg:grid-cols-3 gap-10"
        >


            <!-- DELIVERY -->

            <div>

                <div
                    class="flex justify-between items-center mb-3"
                >

                    <h3
                        class="font-bold"
                    >
                        Donation Delivery Rate
                    </h3>

                    <span
                        class="font-black text-emerald-600"
                    >
                        <?= $deliveryRate ?>%
                    </span>

                </div>


                <div class="progress-track">

                    <div
                        class="progress-bar"
                        data-width="<?= $deliveryRate ?>"
                    ></div>

                </div>


                <p
                    class="text-xs text-slate-500 mt-3 leading-relaxed"
                >

                    Percentage of recorded donations
                    that reached the delivered stage.

                </p>

            </div>



            <!-- VOLUNTEER -->

            <div>

                <div
                    class="flex justify-between items-center mb-3"
                >

                    <h3
                        class="font-bold"
                    >
                        Volunteer Completion
                    </h3>

                    <span
                        class="font-black text-violet-600"
                    >
                        <?= $volunteerCompletionRate ?>%
                    </span>

                </div>


                <div class="progress-track">

                    <div
                        class="progress-bar"
                        data-width="<?= $volunteerCompletionRate ?>"
                    ></div>

                </div>


                <p
                    class="text-xs text-slate-500 mt-3 leading-relaxed"
                >

                    Percentage of volunteer assignments
                    completed through delivery.

                </p>

            </div>



            <!-- REQUEST -->

            <div>

                <div
                    class="flex justify-between items-center mb-3"
                >

                    <h3
                        class="font-bold"
                    >
                        NGO Request Approval
                    </h3>

                    <span
                        class="font-black text-cyan-600"
                    >
                        <?= $requestApprovalRate ?>%
                    </span>

                </div>


                <div class="progress-track">

                    <div
                        class="progress-bar"
                        data-width="<?= $requestApprovalRate ?>"
                    ></div>

                </div>


                <p
                    class="text-xs text-slate-500 mt-3 leading-relaxed"
                >

                    Percentage of food requests that
                    received approval.

                </p>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     VOLUNTEER METRICS
====================================================== -->

<section
    class="max-w-7xl mx-auto px-5 py-10"
>

    <div
        class="mb-8"
    >

        <p
            class="text-sm uppercase tracking-widest text-violet-600 font-bold"
        >
            Volunteer Network
        </p>

        <h2
            class="text-3xl md:text-4xl font-black mt-2"
        >
            People who move the mission
        </h2>

    </div>


    <div
        class="grid md:grid-cols-2 gap-6"
    >


        <div
            class="glass-card rounded-3xl p-8"
        >

            <div
                class="flex items-center gap-5"
            >

                <div
                    class="w-14 h-14 rounded-2xl bg-violet-500/10 flex items-center justify-center text-2xl"
                >
                    🚚
                </div>

                <div>

                    <p
                        class="text-sm text-slate-500"
                    >
                        Total assignments
                    </p>

                    <div
                        class="metric-counter text-3xl font-black"
                        data-value="<?= $totalAssignments ?>"
                    >
                        0
                    </div>

                </div>

            </div>


            <p
                class="text-sm text-slate-500 mt-6 leading-relaxed"
            >

                Each assignment represents a volunteer
                taking responsibility for helping move
                a donation through the network.

            </p>

        </div>



        <div
            class="glass-card rounded-3xl p-8"
        >

            <div
                class="flex items-center gap-5"
            >

                <div
                    class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-2xl"
                >
                    📍
                </div>

                <div>

                    <p
                        class="text-sm text-slate-500"
                    >
                        Completed deliveries
                    </p>

                    <div
                        class="metric-counter text-3xl font-black"
                        data-value="<?= $completedAssignments ?>"
                    >
                        0
                    </div>

                </div>

            </div>


            <p
                class="text-sm text-slate-500 mt-6 leading-relaxed"
            >

                Completed assignments indicate successful
                movement of donations through the
                volunteer delivery workflow.

            </p>

        </div>

    </div>

</section>



<!-- =====================================================
     WHY THESE METRICS MATTER
====================================================== -->

<section
    class="max-w-7xl mx-auto px-5 py-16"
>

    <div
        class="glass-card rounded-[2rem] p-8 md:p-12"
    >

        <div
            class="max-w-3xl"
        >

            <p
                class="text-sm uppercase tracking-widest text-emerald-600 font-bold"
            >
                Why Metrics Matter
            </p>

            <h2
                class="text-3xl md:text-5xl font-black mt-3"
            >
                Turning activity into measurable progress.
            </h2>

            <p
                class="text-slate-500 mt-6 leading-relaxed"
            >

                Food redistribution works as a connected
                process. Donors provide surplus food,
                NGOs identify community needs and volunteers
                help complete the final delivery.

            </p>

            <p
                class="text-slate-500 mt-4 leading-relaxed"
            >

                F-Destiny's metrics make that workflow visible.
                Instead of using estimated or fictional impact
                numbers, the platform measures activity that
                actually exists inside the system.

            </p>

        </div>


        <div
            class="grid md:grid-cols-3 gap-5 mt-10"
        >

            <div
                class="p-6 rounded-2xl bg-white/60 border border-slate-200/70"
            >

                <div class="text-2xl">
                    01
                </div>

                <h3
                    class="font-bold mt-4"
                >
                    Connect
                </h3>

                <p
                    class="text-sm text-slate-500 mt-2"
                >
                    Bring donors, NGOs and volunteers
                    into one coordinated network.
                </p>

            </div>


            <div
                class="p-6 rounded-2xl bg-white/60 border border-slate-200/70"
            >

                <div class="text-2xl">
                    02
                </div>

                <h3
                    class="font-bold mt-4"
                >
                    Coordinate
                </h3>

                <p
                    class="text-sm text-slate-500 mt-2"
                >
                    Match food donations with requests
                    and coordinate volunteer assignments.
                </p>

            </div>


            <div
                class="p-6 rounded-2xl bg-white/60 border border-slate-200/70"
            >

                <div class="text-2xl">
                    03
                </div>

                <h3
                    class="font-bold mt-4"
                >
                    Measure
                </h3>

                <p
                    class="text-sm text-slate-500 mt-2"
                >
                    Track donations, requests and
                    completed deliveries.
                </p>

            </div>

        </div>

    </div>

</section>



<!-- =====================================================
     CTA
====================================================== -->

<section
    class="max-w-5xl mx-auto px-5 pb-24"
>

    <div
        class="glass-card rounded-[2rem] p-10 md:p-16 text-center"
    >

        <div
            class="text-5xl"
        >
            🌱
        </div>

        <h2
            class="text-3xl md:text-5xl font-black mt-5"
        >
            Be part of the next metric.
        </h2>

        <p
            class="text-slate-500 max-w-2xl mx-auto mt-5 leading-relaxed"
        >

            Donate surplus food, support your community
            through an NGO, or volunteer your time.
            Every completed action strengthens the network.

        </p>


        <div
            class="flex flex-col sm:flex-row justify-center gap-4 mt-8"
        >

            <a
                href="join_team.php"
                class="px-7 py-3.5 rounded-full bg-emerald-500 text-white font-bold hover:bg-emerald-600 transition"
            >
                Join F-Destiny
            </a>

            <a
                href="see_partners.php"
                class="px-7 py-3.5 rounded-full border border-slate-200 bg-white/60 hover:bg-white transition"
            >
                Explore Partners
            </a>

        </div>

    </div>

</section>

</main>



<!-- =====================================================
     FOOTER
====================================================== -->

<footer
    class="border-t border-slate-200/70 bg-white/40"
>

    <div
        class="max-w-7xl mx-auto px-5 py-10 flex flex-col md:flex-row justify-between gap-5"
    >

        <div>

            <div
                class="text-xl font-black"
            >

                <span class="text-emerald-600">
                    F-
                </span>

                Destiny

            </div>

            <p
                class="text-sm text-slate-500 mt-2"
            >
                Connecting surplus food with communities.
            </p>

        </div>


        <div
            class="flex gap-6 text-sm text-slate-500"
        >

            <a href="index.php">
                Home
            </a>

            <a href="about.php">
                About
            </a>

            <a href="see_partners.php">
                Partners
            </a>

            <a href="impact.php">
                Impact
            </a>

        </div>

    </div>


    <div
        class="text-center text-xs text-slate-400 pb-6"
    >

        © <?= date("Y") ?> F-Destiny.
        All rights reserved.

    </div>

</footer>



<!-- =====================================================
     ANIME.JS
====================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| MOBILE MENU
|--------------------------------------------------------------------------
*/

const menuBtn =
    document.getElementById('menuBtn');

const mobileMenu =
    document.getElementById('mobileMenu');


if (menuBtn) {

    menuBtn.addEventListener(
        'click',
        () => {

            mobileMenu.classList.toggle(
                'hidden'
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| CURSOR GLOW
|--------------------------------------------------------------------------
*/

const cursorGlow =
    document.getElementById(
        'cursorGlow'
    );


document.addEventListener(
    'mousemove',
    (event) => {

        cursorGlow.animate(

            {
                left:
                    `${event.clientX}px`,

                top:
                    `${event.clientY}px`
            },

            {
                duration:
                    500,

                fill:
                    'forwards'
            }

        );

    }
);



/*
|--------------------------------------------------------------------------
| 3D GLASS CARD
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll('.metric-card, .glass-card')
    .forEach(card => {


        card.addEventListener(
            'mousemove',
            (event) => {

                const rect =
                    card.getBoundingClientRect();


                const x =
                    event.clientX -
                    rect.left;


                const y =
                    event.clientY -
                    rect.top;


                const centerX =
                    rect.width / 2;


                const centerY =
                    rect.height / 2;


                const rotateX =
                    ((y - centerY) /
                    centerY) * -2;


                const rotateY =
                    ((x - centerX) /
                    centerX) * 2;


                card.style.transform =
                    `
                    perspective(900px)
                    rotateX(${rotateX}deg)
                    rotateY(${rotateY}deg)
                    translateY(-4px)
                    `;

            }
        );


        card.addEventListener(
            'mouseleave',
            () => {

                card.style.transform =
                    `
                    perspective(900px)
                    rotateX(0deg)
                    rotateY(0deg)
                    translateY(0)
                    `;

            }
        );

    });



/*
|--------------------------------------------------------------------------
| COUNTER ANIMATION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {


        document
            .querySelectorAll(
                '.metric-counter'
            )
            .forEach(counter => {


                const target =
                    parseInt(
                        counter.dataset.value
                    ) || 0;


                anime({

                    targets:
                        counter,

                    innerHTML:
                        [0, target],

                    duration:
                        1800,

                    easing:
                        'easeOutExpo',

                    round:
                        1,

                    update:
                        function() {

                            const value =
                                parseInt(
                                    counter.innerHTML
                                ) || 0;


                            counter.innerHTML =
                                value.toLocaleString();

                        }

                });

            });


        /*
        |--------------------------------------------------------------------------
        | PROGRESS BAR ANIMATION
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '.progress-bar'
            )
            .forEach(bar => {

                const width =
                    bar.dataset.width || 0;


                anime({

                    targets:
                        bar,

                    width:
                        width + '%',

                    duration:
                        1600,

                    delay:
                        300,

                    easing:
                        'easeOutExpo'

                });

            });


        /*
        |--------------------------------------------------------------------------
        | HERO ANIMATION
        |--------------------------------------------------------------------------
        */

        anime({

            targets:
                'h1',

            opacity:
                [0, 1],

            translateY:
                [30, 0],

            duration:
                900,

            easing:
                'easeOutExpo'

        });


        /*
        |--------------------------------------------------------------------------
        | CARD ENTRANCE
        |--------------------------------------------------------------------------
        */

        anime({

            targets:
                '.metric-card',

            opacity:
                [0, 1],

            translateY:
                [25, 0],

            delay:
                anime.stagger(100),

            duration:
                700,

            easing:
                'easeOutQuad'

        });

    });

</script>


</body>

</html>