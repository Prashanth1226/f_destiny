<?php

require_once "config/db.php";

/*
|--------------------------------------------------------------------------
| FETCH PARTNERS
|--------------------------------------------------------------------------
| Partners are taken directly from the users table.
| We do NOT need a separate partners table.
|--------------------------------------------------------------------------
*/

$partners = [];

$sql = "
    SELECT
        u.id,
        u.name,
        u.role,
        u.designation,
        u.bio,
        u.photo,
        u.latitude,
        u.longitude,
        u.created_at,

        /* Donor activity */
        (
            SELECT COUNT(*)
            FROM donations d
            WHERE d.donor_id = u.id
        ) AS donation_count,

        /* Completed donations */
        (
            SELECT COUNT(*)
            FROM donations d
            WHERE d.donor_id = u.id
            AND d.status = 'Delivered'
        ) AS delivered_donations,

        /* Volunteer assignments */
        (
            SELECT COUNT(*)
            FROM assignments a
            WHERE a.volunteer_id = u.id
        ) AS assignment_count,

        /* Completed volunteer deliveries */
        (
            SELECT COUNT(*)
            FROM assignments a
            WHERE a.volunteer_id = u.id
            AND a.status = 'Delivered'
        ) AS completed_assignments

    FROM users u

    WHERE u.role IN ('donor', 'ngo', 'volunteer')

    ORDER BY
        CASE
            WHEN u.role = 'ngo' THEN 1
            WHEN u.role = 'donor' THEN 2
            WHEN u.role = 'volunteer' THEN 3
        END,
        u.created_at DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $partners[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| PARTNER COUNTS
|--------------------------------------------------------------------------
*/

$totalDonors = 0;
$totalNGOs = 0;
$totalVolunteers = 0;

$countSql = "
    SELECT
        role,
        COUNT(*) AS total
    FROM users
    WHERE role IN ('donor', 'ngo', 'volunteer')
    GROUP BY role
";

$countResult = $conn->query($countSql);

if ($countResult) {

    while ($row = $countResult->fetch_assoc()) {

        if ($row['role'] === 'donor') {
            $totalDonors = (int)$row['total'];
        }

        if ($row['role'] === 'ngo') {
            $totalNGOs = (int)$row['total'];
        }

        if ($row['role'] === 'volunteer') {
            $totalVolunteers = (int)$row['total'];
        }

    }
}

$totalPartners = $totalDonors + $totalNGOs + $totalVolunteers;


/*
|--------------------------------------------------------------------------
| GLOBAL IMPACT STATISTICS
|--------------------------------------------------------------------------
*/

$totalDonations = 0;
$totalDelivered = 0;
$totalAssignments = 0;

$impactSql = "
    SELECT

        (
            SELECT COUNT(*)
            FROM donations
        ) AS total_donations,

        (
            SELECT COUNT(*)
            FROM donations
            WHERE status = 'Delivered'
        ) AS total_delivered,

        (
            SELECT COUNT(*)
            FROM assignments
        ) AS total_assignments
";

$impactResult = $conn->query($impactSql);

if ($impactResult) {

    $impact = $impactResult->fetch_assoc();

    $totalDonations = (int)$impact['total_donations'];
    $totalDelivered = (int)$impact['total_delivered'];
    $totalAssignments = (int)$impact['total_assignments'];
}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


function roleLabel($role)
{
    return ucfirst($role);
}


function roleIcon($role)
{
    switch ($role) {

        case 'ngo':
            return '🏢';

        case 'donor':
            return '🤝';

        case 'volunteer':
            return '🚚';

        default:
            return '👤';
    }
}


function roleDescription($role)
{
    switch ($role) {

        case 'ngo':
            return 'Community organization working to distribute food where it is needed most.';

        case 'donor':
            return 'Community contributor helping redirect surplus food to people in need.';

        case 'volunteer':
            return 'Community member supporting pickup, transportation and delivery activities.';

        default:
            return 'F-Destiny community partner.';
    }
}


function roleClass($role)
{
    switch ($role) {

        case 'ngo':
            return 'role-ngo';

        case 'donor':
            return 'role-donor';

        case 'volunteer':
            return 'role-volunteer';

        default:
            return 'role-default';
    }
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

    <title>Our Partners | F-Destiny</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Anime.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <style>

    /* =========================================================
    GLOBAL
    ========================================================= */

    * {
        font-family: "Inter", sans-serif;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        margin: 0;
        color: #17251c;
        overflow-x: hidden;

        background:
            radial-gradient(
                circle at 8% 8%,
                rgba(34, 197, 94, 0.14),
                transparent 28%
            ),
            radial-gradient(
                circle at 92% 12%,
                rgba(16, 185, 129, 0.12),
                transparent 30%
            ),
            radial-gradient(
                circle at 50% 90%,
                rgba(74, 222, 128, 0.08),
                transparent 35%
            ),
            #f7faf7;
    }


    /* =========================================================
    GLOSSY BACKGROUND ORBS
    ========================================================= */

    .orb {
        position: fixed;

        width: 300px;
        height: 300px;

        border-radius: 50%;

        filter: blur(90px);

        opacity: 0.18;

        pointer-events: none;

        z-index: -2;

        animation: floatOrb 12s ease-in-out infinite;
    }

    .orb-one {
        background: #22c55e;

        top: 8%;
        left: -120px;
    }

    .orb-two {
        background: #10b981;

        bottom: 5%;
        right: -120px;

        animation-delay: -5s;
    }

    @keyframes floatOrb {

        0%,
        100% {
            transform: translate3d(0, 0, 0);
        }

        50% {
            transform: translate3d(20px, -25px, 0);
        }
    }


    /* =========================================================
    MAIN GLASS SYSTEM
    Same glossy texture as Metrics
    ========================================================= */

    .glass {
        position: relative;

        background:
            linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.78),
                rgba(255, 255, 255, 0.52)
            );

        backdrop-filter: blur(24px) saturate(150%);
        -webkit-backdrop-filter: blur(24px) saturate(150%);

        border: 1px solid rgba(255, 255, 255, 0.82);

        box-shadow:
            0 25px 70px rgba(20, 83, 45, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.95),
            inset 0 -1px 0 rgba(255, 255, 255, 0.35);

        transition:
            transform 0.35s ease,
            box-shadow 0.35s ease,
            border-color 0.35s ease;
    }


    /* =========================================================
    GLOSSY LIGHT REFLECTION
    ========================================================= */

    .glass::after {
        content: "";

        position: absolute;

        top: 0;
        left: 0;

        width: 100%;
        height: 1px;

        background:
            linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,0.95),
                transparent
            );

        pointer-events: none;
    }


    /* =========================================================
    NAVBAR
    ========================================================= */

    nav.glass {
        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,0.82),
                rgba(255,255,255,0.62)
            );

        backdrop-filter: blur(26px) saturate(160%);
        -webkit-backdrop-filter: blur(26px) saturate(160%);

        border-bottom:
            1px solid rgba(255,255,255,0.75);

        box-shadow:
            0 12px 40px rgba(20,83,45,0.07);
    }


    /* =========================================================
    LINKS
    ========================================================= */

    nav a {
        transition:
            color 0.25s ease,
            transform 0.25s ease;
    }

    nav a:hover {
        transform: translateY(-1px);
    }


    /* =========================================================
    HERO
    ========================================================= */

    .hero-title {
        color: #17251c;

        letter-spacing: -0.045em;

        text-shadow:
            0 8px 30px rgba(20,83,45,0.06);
    }

    .hero-title span {
        background:
            linear-gradient(
            135deg,
            #16a34a,
            #10b981,
            #059669
            );

        -webkit-background-clip: text;
        background-clip: text;

        -webkit-text-fill-color: transparent;
    }


    /* =========================================================
    HERO / SECTION TEXT
    ========================================================= */

    p {
        color: #64748b;
    }

    h1,
    h2,
    h3 {
        color: #17251c;
    }


    /* =========================================================
    STAT CARDS
    ========================================================= */

    .stat-card {
        position: relative;

        overflow: hidden;

        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,0.78),
                rgba(255,255,255,0.52)
            );

        backdrop-filter: blur(24px) saturate(150%);
        -webkit-backdrop-filter: blur(24px) saturate(150%);

        border:
            1px solid rgba(255,255,255,0.82);

        box-shadow:
            0 20px 55px rgba(20,83,45,0.07),
            inset 0 1px 0 rgba(255,255,255,0.95);

        transition:
            transform 0.35s ease,
            box-shadow 0.35s ease;
    }

    .stat-card:hover {
        transform: translateY(-7px);

        box-shadow:
            0 30px 70px rgba(20,83,45,0.12),
            0 0 35px rgba(34,197,94,0.08),
            inset 0 1px 0 rgba(255,255,255,1);
    }

    .stat-card::before {
        content: "";

        position: absolute;

        width: 150px;
        height: 150px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(34,197,94,0.12),
                transparent 70%
            );

        top: -80px;
        right: -80px;

        pointer-events: none;
    }


    /* =========================================================
    PARTNER CARDS
    ========================================================= */

    .partner-card {
        position: relative;

        overflow: hidden;

        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,0.80),
                rgba(255,255,255,0.55)
            );

        backdrop-filter: blur(24px) saturate(150%);
        -webkit-backdrop-filter: blur(24px) saturate(150%);

        border:
            1px solid rgba(255,255,255,0.82);

        box-shadow:
            0 22px 60px rgba(20,83,45,0.08),
            inset 0 1px 0 rgba(255,255,255,0.95);

        transform-style: flat;

        transition:
            transform 0.35s ease,
            box-shadow 0.35s ease,
            border-color 0.35s ease;
    }


    /* NO 3D CURSOR EFFECT */

    .partner-card:hover {
        transform: translateY(-7px);

        border-color:
            rgba(34,197,94,0.28);

        box-shadow:
            0 32px 80px rgba(20,83,45,0.12),
            0 0 35px rgba(34,197,94,0.08),
            inset 0 1px 0 rgba(255,255,255,1);
    }


    /* =========================================================
    PARTNER CARD LIGHT EFFECT
    ========================================================= */

    .partner-card::before {
        content: "";

        position: absolute;

        width: 220px;
        height: 220px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(34,197,94,0.12),
                transparent 70%
            );

        filter: blur(15px);

        top: -120px;
        right: -100px;

        pointer-events: none;
    }


    /* Bottom glossy reflection */

    .partner-card::after {
        content: "";

        position: absolute;

        left: 10%;
        right: 10%;

        bottom: 0;

        height: 1px;

        background:
            linear-gradient(
                90deg,
                transparent,
                rgba(34,197,94,0.25),
                transparent
            );

        pointer-events: none;
    }


    /* =========================================================
    PROFILE IMAGE
    ========================================================= */

    .profile-image {
        width: 70px;
        height: 70px;

        border-radius: 50%;

        object-fit: cover;

        border:
            3px solid rgba(255,255,255,0.9);

        box-shadow:
            0 8px 25px rgba(20,83,45,0.10),
            0 0 0 4px rgba(34,197,94,0.05);

        transition:
            transform 0.3s ease,
            box-shadow 0.3s ease;
    }

    .partner-card:hover .profile-image {
        transform: scale(1.04);

        box-shadow:
            0 10px 30px rgba(20,83,45,0.15),
            0 0 0 5px rgba(34,197,94,0.08);
    }


    /* =========================================================
    PROFILE PLACEHOLDER
    ========================================================= */

    .profile-placeholder {
        width: 70px;
        height: 70px;

        border-radius: 50%;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 30px;

        background:
            linear-gradient(
                135deg,
                rgba(34,197,94,0.16),
                rgba(16,185,129,0.07)
            );

        border:
            1px solid rgba(255,255,255,0.8);

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.9),
            0 8px 25px rgba(20,83,45,0.07);
    }


    /* =========================================================
    ROLE BADGES
    ========================================================= */

    .role-ngo {
        color: #0891b2;

        background:
            rgba(6,182,212,0.09);

        border-color:
            rgba(6,182,212,0.20);

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.75);
    }

    .role-donor {
        color: #15803d;

        background:
            rgba(34,197,94,0.09);

        border-color:
            rgba(34,197,94,0.20);

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.75);
    }

    .role-volunteer {
        color: #7c3aed;

        background:
            rgba(139,92,246,0.09);

        border-color:
            rgba(139,92,246,0.20);

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.75);
    }

    .role-default {
        color: #475569;

        background:
            rgba(148,163,184,0.09);

        border-color:
            rgba(148,163,184,0.20);
    }


    /* =========================================================
    FILTER BUTTONS
    ========================================================= */

    .filter-btn {
        position: relative;

        color: #475569;

        background:
            rgba(255,255,255,0.55);

        border:
            1px solid rgba(255,255,255,0.80) !important;

        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);

        box-shadow:
            0 8px 25px rgba(20,83,45,0.05),
            inset 0 1px 0 rgba(255,255,255,0.9);

        transition:
            transform 0.25s ease,
            background 0.25s ease,
            color 0.25s ease,
            box-shadow 0.25s ease;
    }

    .filter-btn:hover {
        transform: translateY(-2px);

        background:
            rgba(255,255,255,0.82);

        color: #15803d;

        box-shadow:
            0 12px 30px rgba(20,83,45,0.08);
    }

    .filter-btn.active {
        background:
            linear-gradient(
                135deg,
                #22c55e,
                #10b981
            );

        color: white;

        border-color:
            rgba(34,197,94,0.4) !important;

        box-shadow:
            0 12px 30px rgba(34,197,94,0.20),
            inset 0 1px 0 rgba(255,255,255,0.35);
    }


    /* =========================================================
    GLOSSY BUTTONS
    ========================================================= */

    a.bg-green-400 {
        position: relative;

        overflow: hidden;

        background:
            linear-gradient(
                135deg,
                #22c55e,
                #10b981
            ) !important;

        color: white !important;

        box-shadow:
            0 12px 30px rgba(34,197,94,0.18),
            inset 0 1px 0 rgba(255,255,255,0.35);

        transition:
            transform 0.3s ease,
            box-shadow 0.3s ease;
    }

    a.bg-green-400:hover {
        transform: translateY(-3px);

        box-shadow:
            0 18px 40px rgba(34,197,94,0.25),
            0 0 25px rgba(34,197,94,0.10);
    }


    /* =========================================================
    GLOSSY BUTTON SHINE
    ========================================================= */

    a.bg-green-400::before {
        content: "";

        position: absolute;

        top: 0;
        left: -120%;

        width: 80%;
        height: 100%;

        background:
            linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,0.28),
                transparent
            );

        transform: skewX(-20deg);

        transition: left 0.7s ease;

        pointer-events: none;
    }

    a.bg-green-400:hover::before {
        left: 140%;
    }


    /* =========================================================
    SECTION CONTAINERS
    ========================================================= */

    section > .glass {
        box-shadow:
            0 25px 70px rgba(20,83,45,0.08),
            inset 0 1px 0 rgba(255,255,255,0.95);
    }


    /* =========================================================
    DIVIDERS
    ========================================================= */

    .border-white\/5 {
        border-color:
            rgba(20,83,45,0.07) !important;
    }

    .border-white\/10 {
        border-color:
            rgba(20,83,45,0.10) !important;
    }


    /* =========================================================
    TEXT COLOR OVERRIDES
    Keeps existing HTML structure untouched
    ========================================================= */

    .text-slate-950 {
        color: #ffffff !important;
    }

    .text-slate-500 {
        color: #64748b !important;
    }

    .text-slate-400 {
        color: #64748b !important;
    }

    .text-slate-300 {
        color: #475569 !important;
    }

    .text-white {
        color: #17251c !important;
    }


    /* =========================================================
    GREEN TEXT
    ========================================================= */

    .text-green-400 {
        color: #16a34a !important;
    }

    .text-green-300 {
        color: #15803d !important;
    }


    /* =========================================================
    CYAN
    ========================================================= */

    .text-cyan-400 {
        color: #0891b2 !important;
    }


    /* =========================================================
    PURPLE
    ========================================================= */

    .text-purple-400 {
        color: #7c3aed !important;
    }


    /* =========================================================
    YELLOW
    ========================================================= */

    .text-yellow-300 {
        color: #ca8a04 !important;
    }


    /* =========================================================
    MOBILE MENU
    ========================================================= */

    #mobileMenu .glass {
        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,0.88),
                rgba(255,255,255,0.65)
            );

        box-shadow:
            0 20px 50px rgba(20,83,45,0.10);
    }


    /* =========================================================
    FOOTER
    ========================================================= */

    footer {
        border-color:
            rgba(20,83,45,0.08) !important;
    }

    footer .text-slate-500 {
        color: #64748b !important;
    }

    footer a {
        transition:
            color 0.25s ease,
            transform 0.25s ease;
    }

    footer a:hover {
        color: #15803d !important;
    }


    /* =========================================================
    COUNTERS
    ========================================================= */

    .counter {
        text-shadow:
            0 8px 25px rgba(34,197,94,0.08);
    }


    /* =========================================================
    SMOOTH ANIMATION
    ========================================================= */

    .partner-card,
    .stat-card,
    .glass {
        will-change: transform;
    }


    /* =========================================================
    ACCESSIBILITY
    ========================================================= */

    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }

    }


    /* =========================================================
    RESPONSIVE
    ========================================================= */

    @media (max-width: 768px) {

        .hero-title {
            font-size: 2.8rem;
        }

        .orb {
            width: 220px;
            height: 220px;

            filter: blur(70px);
        }

        .partner-card:hover {
            transform: translateY(-4px);
        }

    }


    @media (max-width: 480px) {

        .hero-title {
            font-size: 2.35rem;
        }

        .profile-image,
        .profile-placeholder {
            width: 60px;
            height: 60px;
        }

    }


    /* =========================================================
    IMPORTANT:
    CURSOR GLOW IS INTENTIONALLY NOT STYLED
    ========================================================= */

    /*
    #cursorGlow removed intentionally.
    The page now uses the static glossy/nature background
    instead of a cursor-following effect.
    */

    </style>

</head>


<body>

    <!-- Background -->

    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>

    <div id="cursorGlow"></div>


    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <nav
        class="glass sticky top-0 z-50 border-b border-white/5"
    >

        <div
            class="max-w-7xl mx-auto px-5 py-4 flex items-center justify-between"
        >

            <a
                href="index.php"
                class="text-2xl font-black tracking-tight"
            >

                <span class="text-green-400">
                    F-
                </span>Destiny

            </a>


            <!-- Desktop -->

            <div class="hidden md:flex items-center gap-7 text-sm">

                <a
                    href="index.php"
                    class="text-slate-300 hover:text-white transition"
                >
                    Home
                </a>

                <a
                    href="learn_more.php"
                    class="text-slate-300 hover:text-white transition"
                >
                    About
                </a>

                <a
                    href="view_impact.php"
                    class="text-slate-300 hover:text-white transition"
                >
                    Impact
                </a>

                <a
                    href="see_partners.php"
                    class="text-green-400"
                >
                    Partners
                </a>

                <a
                    href="join_team.php"
                    class="px-5 py-2.5 rounded-full bg-green-400 text-slate-950 font-bold hover:bg-green-300 transition"
                >
                    Join Team
                </a>

            </div>


            <!-- Mobile -->

            <button
                id="menuBtn"
                class="md:hidden text-2xl"
            >
                ☰
            </button>

        </div>


        <div
            id="mobileMenu"
            class="hidden md:hidden px-5 pb-5"
        >

            <div class="glass rounded-2xl p-5 space-y-4">

                <a
                    href="index.php"
                    class="block text-slate-300"
                >
                    Home
                </a>

                <a
                    href="about.php"
                    class="block text-slate-300"
                >
                    About
                </a>

                <a
                    href="impact.php"
                    class="block text-slate-300"
                >
                    Impact
                </a>

                <a
                    href="see_partners.php"
                    class="block text-green-400"
                >
                    Partners
                </a>

                <a
                    href="join_team.php"
                    class="block text-green-400 font-bold"
                >
                    Join Team
                </a>

            </div>

        </div>

    </nav>


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="max-w-7xl mx-auto px-5 pt-24 pb-16">

        <div class="max-w-4xl mx-auto text-center">

            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-green-300 text-sm mb-7"
            >

                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>

                F-Destiny Community Network

            </div>


            <h1
                class="hero-title text-5xl md:text-7xl font-black leading-tight"
            >

                The people behind
                <span class="text-green-400">
                    the impact.
                </span>

            </h1>


            <p
                class="mt-7 text-lg md:text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed"
            >

                F-Destiny brings donors, NGOs and volunteers together
                to move surplus food toward communities that need it.

                Every registered member contributes to the network
                in a different way.

            </p>

        </div>

    </section>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="max-w-7xl mx-auto px-5 pb-16">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">


            <!-- Donors -->

            <div class="glass rounded-3xl p-6 text-center stat-card">

                <div class="text-3xl mb-3">
                    🤝
                </div>

                <div
                    class="counter text-3xl font-black text-green-400"
                    data-value="<?= $totalDonors ?>"
                >
                    0
                </div>

                <p class="text-slate-400 mt-2">
                    Donors
                </p>

            </div>


            <!-- NGOs -->

            <div class="glass rounded-3xl p-6 text-center stat-card">

                <div class="text-3xl mb-3">
                    🏢
                </div>

                <div
                    class="counter text-3xl font-black text-cyan-400"
                    data-value="<?= $totalNGOs ?>"
                >
                    0
                </div>

                <p class="text-slate-400 mt-2">
                    NGOs
                </p>

            </div>


            <!-- Volunteers -->

            <div class="glass rounded-3xl p-6 text-center stat-card">

                <div class="text-3xl mb-3">
                    🚚
                </div>

                <div
                    class="counter text-3xl font-black text-purple-400"
                    data-value="<?= $totalVolunteers ?>"
                >
                    0
                </div>

                <p class="text-slate-400 mt-2">
                    Volunteers
                </p>

            </div>


            <!-- Total -->

            <div class="glass rounded-3xl p-6 text-center stat-card">

                <div class="text-3xl mb-3">
                    🌍
                </div>

                <div
                    class="counter text-3xl font-black text-yellow-300"
                    data-value="<?= $totalPartners ?>"
                >
                    0
                </div>

                <p class="text-slate-400 mt-2">
                    Community Members
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         NETWORK EXPLANATION
    ====================================================== -->

    <section class="max-w-7xl mx-auto px-5 pb-20">

        <div class="glass rounded-[2rem] p-8 md:p-12">

            <div class="grid md:grid-cols-3 gap-8">

                <div>

                    <div class="text-4xl mb-4">
                        🍱
                    </div>

                    <h3 class="text-xl font-bold">
                        Donors contribute
                    </h3>

                    <p class="text-slate-400 mt-3 leading-relaxed">
                        Donors register surplus food and provide the
                        starting point of the redistribution network.
                    </p>

                </div>


                <div>

                    <div class="text-4xl mb-4">
                        🏢
                    </div>

                    <h3 class="text-xl font-bold">
                        NGOs coordinate
                    </h3>

                    <p class="text-slate-400 mt-3 leading-relaxed">
                        NGOs connect available food with communities
                        and people who can benefit from it.
                    </p>

                </div>


                <div>

                    <div class="text-4xl mb-4">
                        🚚
                    </div>

                    <h3 class="text-xl font-bold">
                        Volunteers deliver
                    </h3>

                    <p class="text-slate-400 mt-3 leading-relaxed">
                        Volunteers support pickup and delivery,
                        helping complete the final part of the journey.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         PARTNER DIRECTORY
    ====================================================== -->

    <section
        id="partners"
        class="max-w-7xl mx-auto px-5 pb-24"
    >

        <div
            class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10"
        >

            <div>

                <p
                    class="text-green-400 font-semibold uppercase tracking-widest text-sm"
                >
                    Our Network
                </p>

                <h2
                    class="text-3xl md:text-5xl font-black mt-3"
                >
                    Meet our partners
                </h2>

                <p class="text-slate-400 mt-4 max-w-2xl">

                    These profiles are generated directly from
                    registered F-Destiny community members.

                </p>

            </div>


            <!-- Filters -->

            <div class="flex flex-wrap gap-2">

                <button
                    class="filter-btn active px-4 py-2 rounded-full border border-white/10 text-sm"
                    data-filter="all"
                >
                    All
                </button>

                <button
                    class="filter-btn px-4 py-2 rounded-full border border-white/10 text-sm"
                    data-filter="ngo"
                >
                    NGOs
                </button>

                <button
                    class="filter-btn px-4 py-2 rounded-full border border-white/10 text-sm"
                    data-filter="donor"
                >
                    Donors
                </button>

                <button
                    class="filter-btn px-4 py-2 rounded-full border border-white/10 text-sm"
                    data-filter="volunteer"
                >
                    Volunteers
                </button>

            </div>

        </div>


        <?php if (empty($partners)): ?>

            <!-- Empty State -->

            <div class="glass rounded-3xl p-14 text-center">

                <div class="text-6xl mb-5">
                    🌱
                </div>

                <h3 class="text-2xl font-bold">
                    The network is just getting started
                </h3>

                <p class="text-slate-400 mt-3">
                    Once donors, NGOs and volunteers register,
                    their public profiles will appear here.
                </p>

                <a
                    href="join_team.php"
                    class="inline-block mt-7 px-6 py-3 rounded-full bg-green-400 text-slate-950 font-bold"
                >
                    Join F-Destiny
                </a>

            </div>

        <?php else: ?>


            <!-- Partner Grid -->

            <div
                id="partnerGrid"
                class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6"
            >

                <?php foreach ($partners as $partner): ?>

                    <?php

                    $role = strtolower($partner['role']);

                    $photo = trim((string)$partner['photo']);

                    /*
                     * Only show photo when one exists.
                     * Otherwise display role icon.
                     */

                    ?>

                    <article
                        class="partner-card partner-item glass rounded-3xl p-6"
                        data-role="<?= e($role) ?>"
                    >


                        <!-- Header -->

                        <div class="flex items-start justify-between gap-4">

                            <div class="flex items-center gap-4">

                                <?php if ($photo !== ''): ?>

                                    <img
                                        src="<?= e($photo) ?>"
                                        alt="<?= e($partner['name']) ?>"
                                        class="profile-image"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="profile-placeholder">
                                        <?= roleIcon($role) ?>
                                    </div>

                                <?php endif; ?>


                                <div>

                                    <h3 class="font-bold text-lg">
                                        <?= e($partner['name']) ?>
                                    </h3>

                                    <?php if (!empty($partner['designation'])): ?>

                                        <p class="text-sm text-slate-400">
                                            <?= e($partner['designation']) ?>
                                        </p>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>


                        <!-- Role -->

                        <div class="mt-5">

                            <span
                                class="<?= roleClass($role) ?> inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-semibold"
                            >

                                <?= roleIcon($role) ?>

                                <?= roleLabel($role) ?>

                            </span>

                        </div>


                        <!-- Bio -->

                        <p
                            class="mt-5 text-slate-400 text-sm leading-relaxed min-h-[70px]"
                        >

                            <?php

                            if (!empty($partner['bio'])) {

                                echo e($partner['bio']);

                            } else {

                                echo e(roleDescription($role));

                            }

                            ?>

                        </p>


                        <!-- Location -->

                        <?php

                        if (
                            $partner['latitude'] !== null &&
                            $partner['longitude'] !== null
                        ):

                        ?>

                            <div class="mt-5 text-sm text-slate-400">

                                📍

                                Location available

                            </div>

                        <?php endif; ?>


                        <!-- Activity -->

                        <div
                            class="mt-6 pt-5 border-t border-white/5"
                        >

                            <?php if ($role === 'donor'): ?>

                                <div class="flex justify-between text-sm">

                                    <span class="text-slate-500">
                                        Donations
                                    </span>

                                    <span class="font-bold">
                                        <?= (int)$partner['donation_count'] ?>
                                    </span>

                                </div>

                                <div class="flex justify-between text-sm mt-3">

                                    <span class="text-slate-500">
                                        Delivered
                                    </span>

                                    <span class="font-bold text-green-400">
                                        <?= (int)$partner['delivered_donations'] ?>
                                    </span>

                                </div>


                            <?php elseif ($role === 'volunteer'): ?>


                                <div class="flex justify-between text-sm">

                                    <span class="text-slate-500">
                                        Assignments
                                    </span>

                                    <span class="font-bold">
                                        <?= (int)$partner['assignment_count'] ?>
                                    </span>

                                </div>

                                <div class="flex justify-between text-sm mt-3">

                                    <span class="text-slate-500">
                                        Deliveries
                                    </span>

                                    <span class="font-bold text-purple-400">
                                        <?= (int)$partner['completed_assignments'] ?>
                                    </span>

                                </div>


                            <?php elseif ($role === 'ngo'): ?>


                                <div class="flex justify-between text-sm">

                                    <span class="text-slate-500">
                                        Community role
                                    </span>

                                    <span class="font-bold text-cyan-400">
                                        NGO
                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>


                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         NETWORK IMPACT
    ====================================================== -->

    <section class="max-w-7xl mx-auto px-5 pb-24">

        <div class="glass rounded-[2rem] p-8 md:p-12">

            <div class="grid md:grid-cols-3 gap-8 text-center">


                <div>

                    <div class="text-4xl font-black text-green-400">

                        <span
                            class="counter"
                            data-value="<?= $totalDonations ?>"
                        >
                            0
                        </span>

                    </div>

                    <p class="text-slate-400 mt-2">
                        Food Donations Registered
                    </p>

                </div>


                <div>

                    <div class="text-4xl font-black text-cyan-400">

                        <span
                            class="counter"
                            data-value="<?= $totalDelivered ?>"
                        >
                            0
                        </span>

                    </div>

                    <p class="text-slate-400 mt-2">
                        Donations Delivered
                    </p>

                </div>


                <div>

                    <div class="text-4xl font-black text-purple-400">

                        <span
                            class="counter"
                            data-value="<?= $totalAssignments ?>"
                        >
                            0
                        </span>

                    </div>

                    <p class="text-slate-400 mt-2">
                        Volunteer Assignments
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         CTA
    ====================================================== -->

    <section class="max-w-5xl mx-auto px-5 pb-24">

        <div
            class="glass rounded-[2rem] p-10 md:p-16 text-center"
        >

            <div class="text-5xl mb-5">
                🌍
            </div>

            <h2
                class="text-3xl md:text-5xl font-black"
            >
                Become part of the network.
            </h2>

            <p
                class="text-slate-400 max-w-2xl mx-auto mt-5 leading-relaxed"
            >

                Whether you have food to contribute, a community
                to support, or time to volunteer, F-Destiny gives
                you a place to make a difference.

            </p>

            <div
                class="flex flex-col sm:flex-row justify-center gap-4 mt-8"
            >

                <a
                    href="join_team.php"
                    class="px-7 py-3.5 rounded-full bg-green-400 text-slate-950 font-bold hover:bg-green-300 transition"
                >
                    Join the Team
                </a>

                <a
                    href="impact.php"
                    class="px-7 py-3.5 rounded-full border border-white/10 hover:bg-white/5 transition"
                >
                    View Impact
                </a>

            </div>

        </div>

    </section>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="border-t border-white/5">

        <div
            class="max-w-7xl mx-auto px-5 py-10 flex flex-col md:flex-row justify-between gap-5"
        >

            <div>

                <div class="text-xl font-black">

                    <span class="text-green-400">
                        F-
                    </span>Destiny

                </div>

                <p class="text-slate-500 text-sm mt-2">
                    Connecting food with communities.
                </p>

            </div>


            <div class="flex gap-6 text-sm text-slate-500">

                <a
                    href="index.php"
                    class="hover:text-white"
                >
                    Home
                </a>

                <a
                    href="about.php"
                    class="hover:text-white"
                >
                    About
                </a>

                <a
                    href="impact.php"
                    class="hover:text-white"
                >
                    Impact
                </a>

                <a
                    href="join_team.php"
                    class="hover:text-white"
                >
                    Join
                </a>

            </div>

        </div>

    </footer>


    <!-- =====================================================
         JAVASCRIPT
    ====================================================== -->

    <script>

        /*
        |--------------------------------------------------------------------------
        | MOBILE MENU
        |--------------------------------------------------------------------------
        */

        const menuBtn = document.getElementById('menuBtn');

        const mobileMenu = document.getElementById('mobileMenu');

        if (menuBtn) {

            menuBtn.addEventListener('click', () => {

                mobileMenu.classList.toggle('hidden');

            });

        }


        /*
        |--------------------------------------------------------------------------
        | CURSOR GLOW
        |--------------------------------------------------------------------------
        */

        const cursorGlow =
            document.getElementById('cursorGlow');

        document.addEventListener('mousemove', (event) => {

            cursorGlow.animate(
                {
                    left: `${event.clientX}px`,
                    top: `${event.clientY}px`
                },
                {
                    duration: 500,
                    fill: 'forwards'
                }
            );

        });


        /*
        |--------------------------------------------------------------------------
        | PARTNER CARD 3D EFFECT
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('.partner-card')
            .forEach(card => {

                card.addEventListener('mousemove', (event) => {

                    const rect =
                        card.getBoundingClientRect();

                    const x =
                        event.clientX - rect.left;

                    const y =
                        event.clientY - rect.top;

                    const centerX =
                        rect.width / 2;

                    const centerY =
                        rect.height / 2;

                    const rotateX =
                        ((y - centerY) / centerY) * -4;

                    const rotateY =
                        ((x - centerX) / centerX) * 4;

                    card.style.transform =
                        `perspective(900px)
                         rotateX(${rotateX}deg)
                         rotateY(${rotateY}deg)
                         translateY(-5px)`;

                });


                card.addEventListener('mouseleave', () => {

                    card.style.transform =
                        'perspective(900px) rotateX(0deg) rotateY(0deg) translateY(0)';

                });

            });


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        const filterButtons =
            document.querySelectorAll('.filter-btn');

        const partnerItems =
            document.querySelectorAll('.partner-item');


        filterButtons.forEach(button => {

            button.addEventListener('click', () => {

                const filter =
                    button.dataset.filter;


                filterButtons.forEach(btn => {

                    btn.classList.remove('active');

                });

                button.classList.add('active');


                partnerItems.forEach(card => {

                    const role =
                        card.dataset.role;


                    if (
                        filter === 'all' ||
                        role === filter
                    ) {

                        card.style.display = '';

                        anime({
                            targets: card,
                            opacity: [0, 1],
                            translateY: [15, 0],
                            duration: 400,
                            easing: 'easeOutQuad'
                        });

                    } else {

                        card.style.display = 'none';

                    }

                });

            });

        });


        /*
        |--------------------------------------------------------------------------
        | COUNTERS
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('.counter')
            .forEach(counter => {

                const value =
                    parseInt(counter.dataset.value) || 0;


                anime({
                    targets: counter,
                    innerHTML: [0, value],
                    round: 1,
                    duration: 1400,
                    easing: 'easeOutExpo'
                });

            });


        /*
        |--------------------------------------------------------------------------
        | PAGE ANIMATIONS
        |--------------------------------------------------------------------------
        */

        anime({

            targets: '.hero-title',

            opacity: [0, 1],

            translateY: [35, 0],

            duration: 1000,

            easing: 'easeOutExpo'

        });


        anime({

            targets: '.stat-card',

            opacity: [0, 1],

            translateY: [30, 0],

            delay: anime.stagger(100),

            duration: 700,

            easing: 'easeOutQuad'

        });


        anime({

            targets: '.partner-card',

            opacity: [0, 1],

            translateY: [25, 0],

            delay: anime.stagger(70),

            duration: 700,

            easing: 'easeOutQuad'

        });

    </script>


</body>

</html>