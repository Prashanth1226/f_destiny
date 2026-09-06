<?php

require_once "config/db.php";

/*
|--------------------------------------------------------------------------
| FETCH IMPACT STORIES
|--------------------------------------------------------------------------
*/

$stories = [];

$sql = "
    SELECT
        title,
        description,
        location,
        created_at
    FROM impact_stories
    WHERE status = 'active'
    ORDER BY created_at DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| DEMO IMPACT STATISTICS
|--------------------------------------------------------------------------
*/

$impactStats = [

    [
        "value" => 1250,
        "label" => "Meals Redistributed",
        "icon" => "🍱"
    ],

    [
        "value" => 85,
        "label" => "Food Donors",
        "icon" => "🤝"
    ],

    [
        "value" => 32,
        "label" => "NGO Partners",
        "icon" => "🏢"
    ],

    [
        "value" => 146,
        "label" => "Volunteer Tasks",
        "icon" => "🚚"
    ]

];

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
        Our Impact | F-Destiny
    </title>


    <!-- ==================================================
         TAILWIND CSS
    ================================================== -->

    <script src="https://cdn.tailwindcss.com"></script>


    <!-- ==================================================
         ANIME.JS
    ================================================== -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>


    <!-- ==================================================
         GOOGLE FONT
    ================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- ==================================================
         TAILWIND CONFIG
    ================================================== -->

    <script>

        tailwind.config = {

            theme: {

                extend: {

                    fontFamily: {

                        inter: [
                            "Inter",
                            "sans-serif"
                        ]

                    }

                }

            }

        };

    </script>


    <!-- ==================================================
         PREMIUM GLOSSY CSS
    ================================================== -->

    <style>

        /* =================================================
           GLOBAL
        ================================================= */

        * {
            box-sizing: border-box;
            font-family: "Inter", sans-serif;
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


        /* =================================================
           BACKGROUND ORBS
        ================================================= */

        .orb {

            position: fixed;

            width: 300px;
            height: 300px;

            border-radius: 50%;

            filter: blur(90px);

            opacity: 0.18;

            pointer-events: none;

            z-index: -2;

            animation:
                floatOrb 12s ease-in-out infinite;
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
                transform:
                    translate3d(0, 0, 0);
            }

            50% {
                transform:
                    translate3d(20px, -25px, 0);
            }

        }


        /* =================================================
           PREMIUM GLASS
        ================================================= */

        .glass {

            position: relative;

            background:

                linear-gradient(
                    135deg,
                    rgba(255, 255, 255, 0.80),
                    rgba(255, 255, 255, 0.52)
                );

            backdrop-filter:
                blur(24px)
                saturate(150%);

            -webkit-backdrop-filter:
                blur(24px)
                saturate(150%);

            border:
                1px solid rgba(255, 255, 255, 0.82);

            box-shadow:

                0 25px 70px
                    rgba(20, 83, 45, 0.08),

                inset 0 1px 0
                    rgba(255, 255, 255, 0.95),

                inset 0 -1px 0
                    rgba(255, 255, 255, 0.35);

            overflow: hidden;

            transition:

                transform 0.35s ease,

                border-color 0.35s ease,

                box-shadow 0.35s ease;
        }


        /* =================================================
           GLASS TOP HIGHLIGHT
        ================================================= */

        .glass::before {

            content: "";

            position: absolute;

            top: 0;
            left: 0;
            right: 0;

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


        /* =================================================
           GLASS REFLECTION
        ================================================= */

        .glass::after {

            content: "";

            position: absolute;

            width: 180%;
            height: 100%;

            top: -80%;
            left: -40%;

            background:

                linear-gradient(
                    120deg,
                    transparent 35%,
                    rgba(255,255,255,0.14) 50%,
                    transparent 65%
                );

            transform:
                rotate(-8deg);

            pointer-events: none;

            opacity: 0.55;
        }


        /* =================================================
           NAVBAR
        ================================================= */

        nav {

            background:

                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.86),
                    rgba(255,255,255,0.64)
                ) !important;

            backdrop-filter:
                blur(26px)
                saturate(160%);

            -webkit-backdrop-filter:
                blur(26px)
                saturate(160%);

            border-bottom:
                1px solid
                rgba(255,255,255,0.80) !important;

            box-shadow:

                0 12px 40px
                    rgba(20,83,45,0.07);
        }


        /* =================================================
           NAV LINKS
        ================================================= */

        .nav-link {

            position: relative;

            color: #475569 !important;

            transition:
                color 0.25s ease,
                transform 0.25s ease;
        }


        .nav-link:hover {

            color: #15803d !important;

            transform:
                translateY(-1px);
        }


        .nav-link::after {

            content: "";

            position: absolute;

            left: 0;
            bottom: -7px;

            width: 0;
            height: 2px;

            background:
                linear-gradient(
                    90deg,
                    #22c55e,
                    #10b981
                );

            border-radius: 10px;

            transition:
                width 0.3s ease;
        }


        .nav-link:hover::after {

            width: 100%;
        }


        /* =================================================
           LOGO
        ================================================= */

        nav .text-green-400 {

            color: #16a34a !important;
        }


        /* =================================================
           GRADIENT TEXT
        ================================================= */

        .gradient-text {

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


        /* =================================================
           HEADINGS
        ================================================= */

        h1,
        h2,
        h3 {

            color: #17251c;
        }


        /* =================================================
           TEXT COLORS
        ================================================= */

        .text-slate-300 {

            color: #475569 !important;
        }


        .text-slate-400 {

            color: #64748b !important;
        }


        .text-slate-500 {

            color: #64748b !important;
        }


        .text-white {

            color: #17251c !important;
        }


        .text-green-400 {

            color: #16a34a !important;
        }


        .text-green-300 {

            color: #15803d !important;
        }


        /* =================================================
           GREEN BUTTON
        ================================================= */

        .green-button {

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

                0 12px 30px
                    rgba(34,197,94,0.18),

                inset 0 1px 0
                    rgba(255,255,255,0.35);

            transition:

                transform 0.3s ease,

                box-shadow 0.3s ease;
        }


        .green-button:hover {

            transform:
                translateY(-3px);

            box-shadow:

                0 18px 40px
                    rgba(34,197,94,0.25),

                0 0 30px
                    rgba(34,197,94,0.10);
        }


        /* =================================================
           BUTTON SHINE
        ================================================= */

        .green-button::before {

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
                    rgba(255,255,255,0.30),
                    transparent
                );

            transform:
                skewX(-20deg);

            transition:
                left 0.7s ease;

            pointer-events: none;
        }


        .green-button:hover::before {

            left: 140%;
        }


        /* =================================================
           INTERACTIVE CARDS
           Static glossy hover only.
           NO cursor rotation.
        ================================================= */

        .interactive-card {

            transform-style: flat;

            transition:

                transform 0.35s ease,

                border-color 0.35s ease,

                box-shadow 0.35s ease;
        }


        .interactive-card:hover {

            transform:
                translateY(-7px);

            border-color:
                rgba(34,197,94,0.28);

            box-shadow:

                0 32px 80px
                    rgba(20,83,45,0.12),

                0 0 35px
                    rgba(34,197,94,0.08),

                inset 0 1px 0
                    rgba(255,255,255,1);
        }


        /* =================================================
           JOURNEY CARDS
        ================================================= */

        .journey-card {

            background:

                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.78),
                    rgba(255,255,255,0.50)
                );
        }


        .journey-card:hover {

            transform:
                translateY(-8px);
        }


        /* =================================================
           IMPACT CARDS
        ================================================= */

        .impact-card {

            background:

                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.82),
                    rgba(255,255,255,0.55)
                );
        }


        .impact-card::after {

            content: "";

            position: absolute;

            width: 170px;
            height: 170px;

            border-radius: 50%;

            background:

                radial-gradient(
                    circle,
                    rgba(34,197,94,0.12),
                    transparent 70%
                );

            top: -90px;
            right: -90px;

            pointer-events: none;
        }


        /* =================================================
           STORY CARDS
        ================================================= */

        .story-card {

            background:

                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.80),
                    rgba(255,255,255,0.54)
                );

            transition:

                transform 0.35s ease,

                border-color 0.35s ease,

                box-shadow 0.35s ease;
        }


        .story-card:hover {

            transform:
                translateY(-7px);

            border-color:
                rgba(34,197,94,0.28);

            box-shadow:

                0 30px 70px
                    rgba(20,83,45,0.11);
        }


        /* =================================================
           INNER FEATURE BOXES
        ================================================= */

        .bg-white\/5 {

            background:
                rgba(255,255,255,0.58) !important;

            border:
                1px solid
                rgba(255,255,255,0.70);

            box-shadow:

                inset 0 1px 0
                    rgba(255,255,255,0.85),

                0 8px 25px
                    rgba(20,83,45,0.04);

            transition:

                transform 0.3s ease,

                background 0.3s ease,

                box-shadow 0.3s ease;
        }


        .bg-white\/5:hover {

            transform:
                translateY(-3px);

            background:
                rgba(255,255,255,0.78) !important;

            box-shadow:

                0 12px 30px
                    rgba(20,83,45,0.07);
        }


        /* =================================================
           BORDER OVERRIDES
        ================================================= */

        .border-white\/10 {

            border-color:
                rgba(20,83,45,0.09) !important;
        }


        /* =================================================
           MOBILE MENU
        ================================================= */

        #mobileMenu .glass {

            background:

                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.90),
                    rgba(255,255,255,0.68)
                );

            box-shadow:

                0 20px 50px
                    rgba(20,83,45,0.10);
        }


        /* =================================================
           MOBILE MENU BUTTON
        ================================================= */

        #menuButton {

            color: #17251c;

            transition:
                transform 0.25s ease,
                color 0.25s ease;
        }


        #menuButton:hover {

            color: #16a34a;

            transform:
                scale(1.05);
        }


        /* =================================================
           COUNTERS
        ================================================= */

        .counter {

            color: #15803d;

            text-shadow:
                0 8px 25px
                rgba(34,197,94,0.08);
        }


        /* =================================================
           FOOTER
        ================================================= */

        footer {

            border-color:
                rgba(20,83,45,0.08) !important;
        }


        footer .text-slate-500 {

            color: #64748b !important;
        }


        /* =================================================
           SECTION GLASS
        ================================================= */

        section > .glass {

            box-shadow:

                0 25px 70px
                    rgba(20,83,45,0.08),

                inset 0 1px 0
                    rgba(255,255,255,0.95);
        }


        /* =================================================
           HERO BADGE
        ================================================= */

        #hero .glass {

            background:

                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.82),
                    rgba(255,255,255,0.58)
                );

            color: #15803d !important;
        }


        /* =================================================
           HERO
        ================================================= */

        #hero h1 {

            letter-spacing:
                -0.045em;

            text-shadow:
                0 8px 30px
                rgba(20,83,45,0.06);
        }


        /* =================================================
           IMPACT ICON BOX
        ================================================= */

        .impact-card > .text-4xl {

            position: relative;
            z-index: 2;
        }


        /* =================================================
           GENERAL GLOSS
        ================================================= */

        .glass,
        .interactive-card {

            will-change: transform;
        }


        /* =================================================
           ACCESSIBILITY
        ================================================= */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {

                animation-duration:
                    0.01ms !important;

                animation-iteration-count:
                    1 !important;

                transition-duration:
                    0.01ms !important;

                scroll-behavior:
                    auto !important;
            }

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 768px) {

            .orb {

                width: 220px;
                height: 220px;

                filter: blur(70px);
            }


            .interactive-card:hover {

                transform:
                    translateY(-4px);
            }

        }


        @media (max-width: 480px) {

            #hero h1 {

                font-size:
                    2.5rem;
            }

        }

    </style>

</head>


<body>


    <!-- ======================================================
         BACKGROUND ORBS
    ====================================================== -->

    <div class="orb orb-one"></div>

    <div class="orb orb-two"></div>


    <!-- ======================================================
         NAVBAR
    ====================================================== -->

    <nav
        class="relative z-50 border-b border-white/10 bg-slate-950/60 backdrop-blur-xl"
    >

        <div
            class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between"
        >

            <!-- LOGO -->

            <a
                href="index.php"
                class="text-2xl font-black tracking-tight"
            >

                <span class="text-green-400">
                    F-
                </span>

                Destiny

            </a>


            <!-- DESKTOP MENU -->

            <div
                class="hidden md:flex items-center gap-8 text-sm"
            >

                <a
                    href="index.php"
                    class="nav-link"
                >
                    Home
                </a>


                <a
                    href="learn_more.php"
                    class="nav-link"
                >
                    About
                </a>


                <a
                    href="view_impact.php"
                    class="text-green-500 font-semibold"
                >
                    Impact
                </a>


                <a
                    href="see_partners.php"
                    class="nav-link"
                >
                    Partners
                </a>


                <a
                    href="join_team.php"
                    class="green-button px-5 py-2.5 rounded-full font-semibold"
                >
                    Join Team
                </a>

            </div>


            <!-- MOBILE BUTTON -->

            <button
                id="menuButton"
                class="md:hidden text-2xl"
            >
                ☰
            </button>

        </div>


        <!-- MOBILE MENU -->

        <div
            id="mobileMenu"
            class="hidden px-6 pb-6"
        >

            <div
                class="glass rounded-2xl p-5 space-y-4"
            >

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
                    class="block text-green-500 font-semibold"
                >
                    Impact
                </a>


                <a
                    href="index.php#partners"
                    class="block text-slate-300"
                >
                    Partners
                </a>

            </div>

        </div>

    </nav>


    <!-- ======================================================
         MAIN
    ====================================================== -->

    <main class="relative z-10">


        <!-- ======================================================
             HERO
        ====================================================== -->

        <section
            class="max-w-7xl mx-auto px-6 pt-24 pb-16 text-center"
        >

            <div
                id="hero"
                class="opacity-0"
            >

                <div
                    class="inline-flex items-center gap-2 glass rounded-full px-5 py-2 text-sm text-green-300 mb-7"
                >

                    <span
                        class="w-2 h-2 bg-green-400 rounded-full animate-pulse"
                    ></span>

                    Making every donation count

                </div>


                <h1
                    class="text-5xl md:text-7xl font-black leading-tight tracking-tight"
                >

                    Turning

                    <span class="gradient-text">
                        Surplus
                    </span>

                    <br>

                    Into

                    <span class="gradient-text">
                        Impact
                    </span>

                </h1>


                <p
                    class="max-w-3xl mx-auto mt-7 text-lg md:text-xl text-slate-400 leading-relaxed"
                >

                    F-Destiny connects surplus food donors,
                    NGOs and volunteers to create a structured
                    food redistribution network that helps reduce
                    food waste and strengthen community support.

                </p>


                <div
                    class="mt-9 flex flex-col sm:flex-row justify-center gap-4"
                >

                    <a
                        href="#statistics"
                        class="green-button px-8 py-4 rounded-full font-bold"
                    >
                        Explore Impact
                    </a>


                    <a
                        href="index.php"
                        class="glass px-8 py-4 rounded-full font-semibold text-slate-300 transition"
                    >
                        Back to Home
                    </a>

                </div>

            </div>

        </section>


        <!-- ======================================================
             JOURNEY
        ====================================================== -->

        <section
            class="max-w-6xl mx-auto px-6 py-12"
        >

            <div
                class="glass rounded-[2rem] p-8 md:p-12"
            >

                <div
                    class="text-center mb-12"
                >

                    <p
                        class="text-green-500 text-sm font-bold uppercase tracking-widest"
                    >
                        F-Destiny Network
                    </p>


                    <h2
                        class="text-3xl md:text-4xl font-black mt-3"
                    >

                        One Platform.

                        <span class="gradient-text">
                            Multiple Connections.
                        </span>

                    </h2>

                </div>


                <div
                    class="grid md:grid-cols-3 gap-8"
                >

                    <!-- DONOR -->

                    <div
                        class="interactive-card journey-card text-center glass rounded-3xl p-7"
                    >

                        <div
                            class="text-5xl mb-5"
                        >
                            🍱
                        </div>


                        <h3
                            class="text-xl font-bold"
                        >
                            Food Donors
                        </h3>


                        <p
                            class="text-slate-400 mt-3 leading-relaxed"
                        >

                            Restaurants, businesses and individuals
                            can register usable surplus food rather
                            than allowing it to become waste.

                        </p>

                    </div>


                    <!-- NGO -->

                    <div
                        class="interactive-card journey-card text-center glass rounded-3xl p-7"
                    >

                        <div
                            class="text-5xl mb-5"
                        >
                            🤝
                        </div>


                        <h3
                            class="text-xl font-bold"
                        >
                            NGOs
                        </h3>


                        <p
                            class="text-slate-400 mt-3 leading-relaxed"
                        >

                            NGOs can discover available donations
                            and coordinate their collection through
                            the F-Destiny platform.

                        </p>

                    </div>


                    <!-- VOLUNTEERS -->

                    <div
                        class="interactive-card journey-card text-center glass rounded-3xl p-7"
                    >

                        <div
                            class="text-5xl mb-5"
                        >
                            🚚
                        </div>


                        <h3
                            class="text-xl font-bold"
                        >
                            Volunteers
                        </h3>


                        <p
                            class="text-slate-400 mt-3 leading-relaxed"
                        >

                            Volunteers can support collection and
                            delivery tasks, helping connect available
                            food with organizations.

                        </p>

                    </div>

                </div>

            </div>

        </section>


        <!-- ======================================================
             STATISTICS
        ====================================================== -->

        <section
            id="statistics"
            class="max-w-7xl mx-auto px-6 py-20"
        >

            <div
                class="text-center mb-12"
            >

                <p
                    class="text-green-500 text-sm font-bold uppercase tracking-widest"
                >
                    Impact Dashboard
                </p>


                <h2
                    class="text-4xl md:text-5xl font-black mt-3"
                >

                    Our

                    <span class="gradient-text">
                        Impact
                    </span>

                </h2>


                <p
                    class="max-w-2xl mx-auto text-slate-400 mt-4"
                >

                    Every donation and volunteer contribution
                    represents another opportunity to reduce
                    food waste and support communities.

                </p>

            </div>


            <div
                class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6"
            >

                <?php foreach ($impactStats as $stat): ?>

                    <div
                        class="interactive-card impact-card glass rounded-3xl p-8 text-center"
                    >

                        <div
                            class="text-4xl mb-5"
                        >

                            <?php
                            echo $stat["icon"];
                            ?>

                        </div>


                        <div
                            class="counter text-4xl md:text-5xl font-black"
                            data-target="<?php echo $stat["value"]; ?>"
                        >
                            0
                        </div>


                        <p
                            class="text-slate-400 mt-3"
                        >

                            <?php
                            echo htmlspecialchars(
                                $stat["label"]
                            );
                            ?>

                        </p>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- ======================================================
             WHY IMPACT MATTERS
        ====================================================== -->

        <section
            class="max-w-7xl mx-auto px-6 py-16"
        >

            <div
                class="grid lg:grid-cols-2 gap-10 items-center"
            >

                <!-- LEFT -->

                <div>

                    <p
                        class="text-green-500 text-sm font-bold uppercase tracking-widest"
                    >
                        Why It Matters
                    </p>


                    <h2
                        class="text-4xl md:text-5xl font-black mt-4 leading-tight"
                    >

                        Food should reach

                        <span class="gradient-text">
                            people,
                        </span>

                        not landfills.

                    </h2>


                    <p
                        class="text-slate-400 text-lg leading-relaxed mt-6"
                    >

                        F-Destiny is designed around a simple idea:
                        when usable surplus food exists, technology
                        can help create a connection between the
                        source of that food and organizations that
                        can put it to meaningful use.

                    </p>


                    <p
                        class="text-slate-400 text-lg leading-relaxed mt-5"
                    >

                        By bringing donors, NGOs and volunteers
                        together, F-Destiny creates a digital
                        ecosystem for coordinated food redistribution.

                    </p>

                </div>


                <!-- RIGHT -->

                <div
                    class="glass rounded-3xl p-8"
                >

                    <div class="space-y-5">


                        <div
                            class="p-5 rounded-2xl bg-white/5 flex gap-4"
                        >

                            <div class="text-3xl">
                                ♻️
                            </div>


                            <div>

                                <h3 class="font-bold">
                                    Reduce Food Waste
                                </h3>


                                <p
                                    class="text-sm text-slate-400 mt-1"
                                >
                                    Give usable surplus food another purpose.
                                </p>

                            </div>

                        </div>


                        <div
                            class="p-5 rounded-2xl bg-white/5 flex gap-4"
                        >

                            <div class="text-3xl">
                                🤝
                            </div>


                            <div>

                                <h3 class="font-bold">
                                    Connect Communities
                                </h3>


                                <p
                                    class="text-sm text-slate-400 mt-1"
                                >
                                    Create meaningful connections between donors and NGOs.
                                </p>

                            </div>

                        </div>


                        <div
                            class="p-5 rounded-2xl bg-white/5 flex gap-4"
                        >

                            <div class="text-3xl">
                                🌱
                            </div>


                            <div>

                                <h3 class="font-bold">
                                    Encourage Sustainability
                                </h3>


                                <p
                                    class="text-sm text-slate-400 mt-1"
                                >
                                    Promote responsible use of available food resources.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- ======================================================
             IMPACT STORIES
        ====================================================== -->

        <section
            class="max-w-7xl mx-auto px-6 py-20"
        >

            <div
                class="text-center mb-12"
            >

                <p
                    class="text-green-500 text-sm font-bold uppercase tracking-widest"
                >
                    Community Stories
                </p>


                <h2
                    class="text-4xl md:text-5xl font-black mt-3"
                >

                    Stories of

                    <span class="gradient-text">
                        Impact
                    </span>

                </h2>

            </div>


            <?php if (!empty($stories)): ?>

                <div
                    class="grid md:grid-cols-2 lg:grid-cols-3 gap-6"
                >

                    <?php foreach ($stories as $story): ?>

                        <article
                            class="interactive-card story-card glass rounded-3xl p-7"
                        >

                            <div
                                class="w-14 h-14 rounded-2xl bg-green-500/10 border border-green-400/20 flex items-center justify-center text-2xl"
                            >
                                🌱
                            </div>


                            <h3
                                class="text-xl font-bold mt-6"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $story["title"]
                                );
                                ?>

                            </h3>


                            <p
                                class="text-slate-400 mt-4 leading-relaxed"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $story["description"]
                                );
                                ?>

                            </p>


                            <?php if (!empty($story["location"])): ?>

                                <div
                                    class="border-t border-white/10 mt-6 pt-5 text-sm text-slate-400"
                                >

                                    📍

                                    <?php
                                    echo htmlspecialchars(
                                        $story["location"]
                                    );
                                    ?>

                                </div>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>


                <div
                    class="glass rounded-3xl p-12 text-center"
                >

                    <div class="text-5xl">
                        🌱
                    </div>


                    <h3
                        class="text-2xl font-bold mt-5"
                    >
                        Impact Stories Coming Soon
                    </h3>


                    <p
                        class="text-slate-400 max-w-xl mx-auto mt-3"
                    >

                        Once impact stories are added to the
                        F-Destiny database, they will automatically
                        appear here.

                    </p>

                </div>

            <?php endif; ?>

        </section>


        <!-- ======================================================
             PROCESS
        ====================================================== -->

        <section
            class="max-w-7xl mx-auto px-6 py-16"
        >

            <div
                class="glass rounded-[2rem] p-8 md:p-12"
            >

                <div
                    class="text-center mb-12"
                >

                    <h2
                        class="text-3xl md:text-4xl font-black"
                    >

                        How F-Destiny

                        <span class="gradient-text">
                            Creates Impact
                        </span>

                    </h2>

                </div>


                <div
                    class="grid md:grid-cols-4 gap-6"
                >


                    <div
                        class="bg-white/5 rounded-2xl p-6"
                    >

                        <div
                            class="text-green-500 text-3xl font-black"
                        >
                            01
                        </div>


                        <h3
                            class="font-bold mt-4"
                        >
                            Donate
                        </h3>


                        <p
                            class="text-sm text-slate-400 mt-2"
                        >
                            Donors register available surplus food.
                        </p>

                    </div>


                    <div
                        class="bg-white/5 rounded-2xl p-6"
                    >

                        <div
                            class="text-green-500 text-3xl font-black"
                        >
                            02
                        </div>


                        <h3
                            class="font-bold mt-4"
                        >
                            Discover
                        </h3>


                        <p
                            class="text-sm text-slate-400 mt-2"
                        >
                            NGOs discover suitable available donations.
                        </p>

                    </div>


                    <div
                        class="bg-white/5 rounded-2xl p-6"
                    >

                        <div
                            class="text-green-500 text-3xl font-black"
                        >
                            03
                        </div>


                        <h3
                            class="font-bold mt-4"
                        >
                            Coordinate
                        </h3>


                        <p
                            class="text-sm text-slate-400 mt-2"
                        >
                            Volunteers support collection and movement.
                        </p>

                    </div>


                    <div
                        class="bg-white/5 rounded-2xl p-6"
                    >

                        <div
                            class="text-green-500 text-3xl font-black"
                        >
                            04
                        </div>


                        <h3
                            class="font-bold mt-4"
                        >
                            Impact
                        </h3>


                        <p
                            class="text-sm text-slate-400 mt-2"
                        >
                            Usable food is redirected toward community support.
                        </p>

                    </div>

                </div>

            </div>

        </section>


        <!-- ======================================================
             CTA
        ====================================================== -->

        <section
            class="max-w-5xl mx-auto px-6 py-24 text-center"
        >

            <div
                class="glass rounded-[2rem] p-10 md:p-16"
            >

                <div class="text-5xl">
                    🍱 🤝 🌱
                </div>


                <h2
                    class="text-4xl md:text-5xl font-black mt-6"
                >

                    Be Part of the

                    <span class="gradient-text">
                        Change
                    </span>

                </h2>


                <p
                    class="text-slate-400 max-w-2xl mx-auto mt-5 text-lg"
                >

                    Donors, NGOs and volunteers all play a role
                    in building a better food redistribution network
                    through F-Destiny.

                </p>


                <a
                    href="index.php"
                    class="green-button inline-block px-8 py-4 rounded-full font-bold mt-8"
                >
                    Explore F-Destiny
                </a>

            </div>

        </section>


    </main>


    <!-- ======================================================
         FOOTER
    ====================================================== -->

    <footer
        class="relative z-10 border-t border-white/10"
    >

        <div
            class="max-w-7xl mx-auto px-6 py-8 flex flex-col md:flex-row justify-between gap-3 text-sm text-slate-500"
        >

            <p>
                © <?php echo date("Y"); ?> F-Destiny
            </p>


            <p>
                Turning surplus food into community impact.
            </p>

        </div>

    </footer>


    <!-- ======================================================
         ANIME.JS
    ====================================================== -->

    <script>


        /* ========================================================
           MOBILE MENU
        ======================================================== */

        const menuButton =
            document.getElementById("menuButton");

        const mobileMenu =
            document.getElementById("mobileMenu");


        if (menuButton) {

            menuButton.addEventListener(
                "click",
                () => {

                    mobileMenu.classList.toggle(
                        "hidden"
                    );

                }
            );

        }


        /* ========================================================
           HERO ANIMATION
        ======================================================== */

        anime({

            targets: "#hero",

            opacity: [0, 1],

            translateY: [40, 0],

            duration: 1200,

            easing: "easeOutExpo"

        });


        /* ========================================================
           JOURNEY ANIMATION
        ======================================================== */

        anime({

            targets: ".journey-card",

            opacity: [0, 1],

            translateY: [30, 0],

            delay: anime.stagger(150),

            duration: 900,

            easing: "easeOutExpo"

        });


        /* ========================================================
           NUMBER COUNTERS
        ======================================================== */

        const counters =
            document.querySelectorAll(
                ".counter"
            );


        const counterObserver =
            new IntersectionObserver(

                function(entries) {

                    entries.forEach(

                        function(entry) {

                            if (

                                entry.isIntersecting &&

                                !entry.target.dataset.animated

                            ) {

                                const counter =
                                    entry.target;


                                const target =
                                    parseInt(
                                        counter.dataset.target
                                    );


                                const object = {
                                    value: 0
                                };


                                anime({

                                    targets: object,

                                    value: target,

                                    duration: 1800,

                                    easing: "easeOutExpo",

                                    round: 1,

                                    update: function() {

                                        counter.textContent =
                                            Math.floor(
                                                object.value
                                            ).toLocaleString();

                                    }

                                });


                                counter.dataset.animated =
                                    "true";

                            }

                        }

                    );

                },

                {
                    threshold: 0.5
                }

            );


        counters.forEach(

            function(counter) {

                counterObserver.observe(
                    counter
                );

            }

        );


        /* ========================================================
           STORY CARD REVEAL
        ======================================================== */

        const storyCards =
            document.querySelectorAll(
                ".story-card"
            );


        storyCards.forEach(

            function(card) {

                card.style.opacity = "0";

            }

        );


        const storyObserver =
            new IntersectionObserver(

                function(entries) {

                    entries.forEach(

                        function(entry) {

                            if (
                                entry.isIntersecting
                            ) {

                                anime({

                                    targets:
                                        entry.target,

                                    opacity: [0, 1],

                                    translateY: [30, 0],

                                    duration: 800,

                                    easing:
                                        "easeOutExpo"

                                });


                                storyObserver.unobserve(
                                    entry.target
                                );

                            }

                        }

                    );

                },

                {
                    threshold: 0.15
                }

            );


        storyCards.forEach(

            function(card) {

                storyObserver.observe(
                    card
                );

            }

        );

    </script>


</body>

</html>