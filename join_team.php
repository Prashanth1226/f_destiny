<?php
session_start();
require_once "config/db.php";

$message = "";
$messageType = "";

$name = "";
$email = "";
$phone = "";
$city = "";
$skills = "";
$reason = "";

if (isset($_POST['join_volunteer'])) {

    $name   = trim($_POST['name'] ?? "");
    $email  = trim($_POST['email'] ?? "");
    $phone  = trim($_POST['phone'] ?? "");
    $city   = trim($_POST['city'] ?? "");
    $skills = trim($_POST['skills'] ?? "");
    $reason = trim($_POST['reason'] ?? "");

    $password = $_POST['password'] ?? "";
    $confirmPassword = $_POST['confirm_password'] ?? "";

    /* ================= VALIDATION ================= */

    if (
        empty($name) ||
        empty($email) ||
        empty($city) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        $message = "Please fill in all required fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        /* ================= CHECK EXISTING USER ================= */

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if (!$check) {

            $message = "Unable to process registration.";
            $messageType = "error";

        } else {

            $check->bind_param("s", $email);
            $check->execute();

            $checkResult = $check->get_result();

            if ($checkResult->num_rows > 0) {

                $message = "An account with this email already exists. Please login instead.";
                $messageType = "error";

                $check->close();

            } else {

                $check->close();

                /* ================= CREATE ACCOUNT ================= */

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                /*
                 * IMPORTANT:
                 * We create this account as VOLUNTEER.
                 * This does NOT modify your existing signup code.
                 */

                $insertUser = $conn->prepare("
                    INSERT INTO users
                    (name, email, phone, password, role, bio)
                    VALUES (?, ?, ?, ?, 'volunteer', ?)
                ");

                if (!$insertUser) {

                    $message = "Unable to create volunteer account.";
                    $messageType = "error";

                } else {

                    $bio = $reason;

                    $insertUser->bind_param(
                        "sssss",
                        $name,
                        $email,
                        $phone,
                        $hashedPassword,
                        $bio
                    );

                    if ($insertUser->execute()) {

                        $userId = $insertUser->insert_id;

                        $insertUser->close();

                        /* ================= ADD VOLUNTEER ROLE ================= */

                        $roleStmt = $conn->prepare("
                            INSERT INTO user_roles (user_id, role)
                            VALUES (?, ?)
                        ");

                        /* ================= ADD ALL THREE ROLES ================= */

                            $roles = ['donor', 'ngo', 'volunteer'];

                            $roleStmt = $conn->prepare("
                                INSERT INTO user_roles (user_id, role)
                                VALUES (?, ?)
                            ");

                            if ($roleStmt) {

                                foreach ($roles as $role) {

                                    $roleStmt->bind_param(
                                        "is",
                                        $userId,
                                        $role
                                    );

                                    $roleStmt->execute();
                                }

                                $roleStmt->close();
                            }

                        /* ================= DIRECT LOGIN ================= */

                        session_regenerate_id(true);

                        $_SESSION['user'] = $email;
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['roles'] = ['volunteer'];
                        $_SESSION['role'] = 'volunteer';

                        /*
                         * Directly open the volunteer dashboard.
                         */

                        header(
                            "Location: /f_destiny/dashboard/volunteer/volunteer_index.php"
                        );

                        exit();

                    } else {

                        $message = "Unable to create your volunteer account.";
                        $messageType = "error";

                        $insertUser->close();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Join the Team | F-Destiny</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Anime.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>

    <script>

        tailwind.config = {

            theme: {

                extend: {

                    fontFamily: {
                        sans: ["Inter", "sans-serif"]
                    },

                    colors: {

                        destiny: {
                            50: "#f0fdf7",
                            100: "#dcfce9",
                            200: "#bbf7d0",
                            500: "#22c55e",
                            600: "#16a34a",
                            700: "#15803d",
                            800: "#166534",
                            900: "#14532d"
                        }

                    }

                }

            }

        }

    </script>

    <style>
        /* =========================================================
    F-DESTINY — PREMIUM NATURE DESIGN SYSTEM
    ========================================================= */

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Inter", sans-serif;
            color: #17251c;
            overflow-x: hidden;

            background:
                radial-gradient(circle at 5% 5%,
                    rgba(34, 197, 94, 0.12),
                    transparent 28%),
                radial-gradient(circle at 95% 15%,
                    rgba(16, 185, 129, 0.10),
                    transparent 28%),
                radial-gradient(circle at 50% 100%,
                    rgba(132, 204, 22, 0.08),
                    transparent 30%),
                #f7faf7;
        }


        /* =========================================================
    GLASS SYSTEM
    ========================================================= */

        .glass {
            background: rgba(255, 255, 255, 0.66);

            backdrop-filter: blur(24px) saturate(150%);
            -webkit-backdrop-filter: blur(24px) saturate(150%);

            border: 1px solid rgba(255, 255, 255, 0.78);

            box-shadow:
                0 25px 70px rgba(20, 83, 45, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.85);

            transition:
                transform 0.4s cubic-bezier(.2, .8, .2, 1),
                box-shadow 0.4s ease,
                border-color 0.4s ease;
        }


        /* =========================================================
    NAVBAR GLASS
    ========================================================= */

        .nav-glass {
            background: rgba(255, 255, 255, 0.72);

            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);

            border-bottom: 1px solid rgba(22, 101, 52, 0.08);

            box-shadow:
                0 8px 30px rgba(20, 83, 45, 0.06);
        }


        /* =========================================================
    HERO
    ========================================================= */

        .hero-gradient {

            position: relative;

            background:
                radial-gradient(circle at 15% 25%,
                    rgba(74, 222, 128, 0.20),
                    transparent 25%),
                radial-gradient(circle at 85% 15%,
                    rgba(52, 211, 153, 0.18),
                    transparent 28%),
                radial-gradient(circle at 70% 90%,
                    rgba(163, 230, 53, 0.12),
                    transparent 30%),
                linear-gradient(135deg,
                    #ecfdf5 0%,
                    #ffffff 45%,
                    #f0fdf4 100%);
        }


        /* =========================================================
    ORGANIC BACKGROUND BLOBS
    ========================================================= */

        .blob {
            position: absolute;

            border-radius: 9999px;

            filter: blur(70px);

            opacity: 0.42;

            pointer-events: none;

            animation:
                floatBlob 10s ease-in-out infinite alternate;
        }

        @keyframes floatBlob {

            from {
                transform: translate3d(0, 0, 0) scale(1);
            }

            to {
                transform: translate3d(20px, -25px, 0) scale(1.08);
            }
        }


        /* =========================================================
    NATURE ORB
    ========================================================= */

        .nature-orb {
            position: absolute;

            width: 260px;
            height: 260px;

            border-radius: 50%;

            background:
                radial-gradient(circle at 35% 30%,
                    rgba(255, 255, 255, .9),
                    rgba(134, 239, 172, .25),
                    rgba(22, 101, 52, .05));

            filter: blur(1px);

            opacity: .55;

            pointer-events: none;
        }


        /* =========================================================
    HERO CARD
    ========================================================= */

        #heroCard {
            transform-style: preserve-3d;

            will-change: transform;

            overflow: visible;
        }

        #heroCard::before {

            content: "";

            position: absolute;

            inset: -1px;

            border-radius: 2rem;

            background:
                linear-gradient(135deg,
                    rgba(255, 255, 255, .85),
                    rgba(134, 239, 172, .15),
                    rgba(255, 255, 255, .7));

            opacity: .6;

            pointer-events: none;

            z-index: -1;
        }


        /* =========================================================
    PREMIUM HERO INNER PANEL
    ========================================================= */

        .hero-panel {

            position: relative;

            overflow: hidden;

            background:
                radial-gradient(circle at 20% 15%,
                    rgba(255, 255, 255, .20),
                    transparent 25%),
                radial-gradient(circle at 85% 80%,
                    rgba(163, 230, 53, .16),
                    transparent 25%),
                linear-gradient(145deg,
                    #166534,
                    #047857 45%,
                    #064e3b);

            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .16),
                0 30px 80px rgba(6, 78, 59, .20);
        }


        /* =========================================================
    GLOSS REFLECTION
    ========================================================= */

        .gloss {

            position: absolute;

            width: 180%;

            height: 80px;

            left: -40%;

            top: 35%;

            transform: rotate(-25deg);

            background:
                linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, .10),
                    transparent);

            pointer-events: none;
        }


        /* =========================================================
    FLOATING GLASS CARD
    ========================================================= */

        .floating-card {

            background: rgba(255, 255, 255, .76);

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);

            border:
                1px solid rgba(255, 255, 255, .85);

            box-shadow:
                0 20px 50px rgba(15, 23, 42, .12);

            animation:
                floatingCard 5s ease-in-out infinite;
        }

        @keyframes floatingCard {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }


        /* =========================================================
    FEATURE CARDS
    ========================================================= */

        .feature-card {

            position: relative;

            overflow: hidden;

            transform-style: preserve-3d;

            will-change: transform;

            transition:
                transform .35s cubic-bezier(.2, .8, .2, 1),
                box-shadow .35s ease,
                border-color .35s ease;
        }

        .feature-card::before {

            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            right: -90px;
            top: -90px;

            border-radius: 50%;

            background:
                radial-gradient(circle,
                    rgba(74, 222, 128, .18),
                    transparent 70%);

            pointer-events: none;
        }

        .feature-card:hover {

            box-shadow:
                0 30px 80px rgba(20, 83, 45, .13);

            border-color:
                rgba(74, 222, 128, .35);
        }


        /* =========================================================
    ICON GLASS
    ========================================================= */

        .icon-glass {

            background:
                linear-gradient(145deg,
                    rgba(220, 252, 231, .90),
                    rgba(187, 247, 208, .55));

            border:
                1px solid rgba(255, 255, 255, .9);

            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .9),
                0 12px 30px rgba(22, 101, 52, .08);
        }


        /* =========================================================
    FORM
    ========================================================= */

        .form-shell {

            position: relative;

            background:
                linear-gradient(145deg,
                    rgba(255, 255, 255, .98),
                    rgba(248, 255, 250, .96));

            border:
                1px solid rgba(255, 255, 255, .9);

            box-shadow:
                0 35px 100px rgba(2, 44, 34, .20),
                inset 0 1px 0 rgba(255, 255, 255, .95);
        }


        /* =========================================================
    FORM INPUTS
    ========================================================= */

        .form-input {

            background:
                rgba(248, 250, 249, .80);

            border:
                1px solid rgba(148, 163, 184, .28);

            transition:
                border-color .25s ease,
                box-shadow .25s ease,
                background .25s ease,
                transform .25s ease;
        }

        .form-input:hover {

            background:
                rgba(255, 255, 255, .95);

            border-color:
                rgba(74, 222, 128, .35);
        }

        .form-input:focus {

            background:
                rgba(255, 255, 255, 1);

            border-color:
                #22c55e;

            box-shadow:
                0 0 0 4px rgba(34, 197, 94, .10),
                0 10px 25px rgba(22, 163, 74, .06);

            outline: none;

            transform:
                translateY(-1px);
        }


        /* =========================================================
    PRIMARY BUTTON
    ========================================================= */

        .primary-btn {

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(135deg,
                    #22c55e,
                    #16a34a);

            box-shadow:
                0 12px 30px rgba(22, 163, 74, .20);

            transition:
                transform .3s ease,
                box-shadow .3s ease,
                filter .3s ease;
        }

        .primary-btn::before {

            content: "";

            position: absolute;

            top: 0;
            left: -120%;

            width: 70%;
            height: 100%;

            background:
                linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, .28),
                    transparent);

            transform:
                skewX(-20deg);

            transition:
                left .7s ease;
        }

        .primary-btn:hover::before {
            left: 140%;
        }

        .primary-btn:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 20px 45px rgba(22, 163, 74, .30);

            filter:
                saturate(1.08);
        }


        /* =========================================================
    STORY CARD
    ========================================================= */

        .story-card {

            position: relative;

            overflow: hidden;

            background:
                radial-gradient(circle at 90% 10%,
                    rgba(134, 239, 172, .16),
                    transparent 25%),
                linear-gradient(135deg,
                    rgba(240, 253, 244, .96),
                    rgba(255, 255, 255, .98));

            border:
                1px solid rgba(187, 247, 208, .7);

            box-shadow:
                0 30px 80px rgba(20, 83, 45, .08);
        }


        /* =========================================================
    DARK APPLICATION SECTION
    ========================================================= */

        .application-section {

            position: relative;

            background:
                radial-gradient(circle at 10% 10%,
                    rgba(34, 197, 94, .18),
                    transparent 25%),
                radial-gradient(circle at 90% 90%,
                    rgba(16, 185, 129, .14),
                    transparent 28%),
                linear-gradient(135deg,
                    #022c22,
                    #052e16 50%,
                    #020617);
        }


        /* =========================================================
    DARK GLASS
    ========================================================= */

        .dark-glass {

            background:
                rgba(255, 255, 255, .07);

            backdrop-filter:
                blur(20px);

            -webkit-backdrop-filter:
                blur(20px);

            border:
                1px solid rgba(255, 255, 255, .10);

            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .08);
        }


        /* =========================================================
    STEP NUMBER
    ========================================================= */

        .step-number {

            background:
                linear-gradient(145deg,
                    rgba(74, 222, 128, .22),
                    rgba(16, 185, 129, .08));

            border:
                1px solid rgba(134, 239, 172, .22);

            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .08);
        }


        /* =========================================================
    SECTION REVEAL
    ========================================================= */

        .reveal {

            opacity: 0;

            transform:
                translateY(35px);
        }


        /* =========================================================
    MOBILE
    ========================================================= */

        @media (max-width: 768px) {

            .glass {
                backdrop-filter: blur(18px);
                -webkit-backdrop-filter: blur(18px);
            }

            #heroCard {
                margin-top: 20px;
            }

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
    </style>

</head>


<body class="text-slate-800">


    <!-- ========================================================= -->
    <!-- NAVBAR -->
    <!-- ========================================================= -->

    <nav class="
        fixed
        top-0
        left-0
        right-0
        z-50
        bg-white/80
        backdrop-blur-xl
        border-b
        border-slate-200/60
    ">

        <div class="
            max-w-7xl
            mx-auto
            px-6
            lg:px-8
            h-20
            flex
            items-center
            justify-between
        ">

            <!-- Logo -->

            <a href="index.php" class="flex items-center gap-3">

                <div class="
                    w-11
                    h-11
                    rounded-2xl
                    bg-gradient-to-br
                    from-green-500
                    to-emerald-700
                    flex
                    items-center
                    justify-center
                    shadow-lg
                    shadow-green-500/20
                ">

                    <i data-lucide="heart-handshake" class="w-6 h-6 text-white"></i>

                </div>

                <div>

                    <div class="
                        font-extrabold
                        text-xl
                        tracking-tight
                        text-slate-900
                    ">
                        F-Destiny
                    </div>

                    <div class="
                        text-[11px]
                        font-medium
                        text-slate-500
                    ">
                        Food • Community • Impact
                    </div>

                </div>

            </a>


            <!-- Desktop Navigation -->

            <div class="
                hidden
                md:flex
                items-center
                gap-8
            ">

                <a href="index.php" class="
                    text-sm
                    font-medium
                    text-slate-600
                    hover:text-green-600
                    transition
                ">
                    Home
                </a>

                <a href="about.php" class="
                    text-sm
                    font-medium
                    text-slate-600
                    hover:text-green-600
                    transition
                ">
                    About
                </a>

                <a href="view_impact.php" class="
                    text-sm
                    font-medium
                    text-slate-600
                    hover:text-green-600
                    transition
                ">
                    Our Impact
                </a>

                <a href="#story" class="
                    text-sm
                    font-medium
                    text-slate-600
                    hover:text-green-600
                    transition
                ">
                    Our Story
                </a>

            </div>


            <!-- CTA -->

            <a href="#application" class="
                hidden
                md:inline-flex
                items-center
                gap-2
                px-5
                py-2.5
                rounded-full
                bg-green-600
                hover:bg-green-700
                text-white
                text-sm
                font-semibold
                transition
                shadow-lg
                shadow-green-600/20
            ">

                Join Us

                <i data-lucide="arrow-right" class="w-4 h-4"></i>

            </a>


            <!-- Mobile Button -->

            <button id="mobileMenuButton" class="md:hidden p-2">

                <i data-lucide="menu" class="w-6 h-6"></i>

            </button>

        </div>


        <!-- Mobile Menu -->

        <div id="mobileMenu" class="
            hidden
            md:hidden
            px-6
            pb-5
            border-t
            border-slate-200
            bg-white
        ">

            <div class="flex flex-col gap-4 pt-5">

                <a href="index.php">
                    Home
                </a>

                <a href="about.php">
                    About
                </a>

                <a href="impact.php">
                    Our Impact
                </a>

                <a href="#story">
                    Our Story
                </a>

                <a href="#application" class="
                    text-green-600
                    font-semibold
                ">
                    Join Us
                </a>

            </div>

        </div>

    </nav>



    <!-- ========================================================= -->
    <!-- HERO -->
    <!-- ========================================================= -->

    <section class="
        hero-gradient
        relative
        overflow-hidden
        pt-36
        pb-24
    ">

        <div class="blob w-72 h-72 bg-green-300 -top-20 -left-20"></div>

        <div class="blob w-96 h-96 bg-emerald-200 -bottom-40 -right-20"></div>


        <div class="
            max-w-7xl
            mx-auto
            px-6
            lg:px-8
            grid
            lg:grid-cols-2
            gap-16
            items-center
        ">

            <!-- Hero text -->

            <div id="heroText">

                <div class="
                    inline-flex
                    items-center
                    gap-2
                    px-4
                    py-2
                    rounded-full
                    bg-green-100
                    text-green-700
                    text-sm
                    font-semibold
                    mb-7
                ">

                    <span class="
                        w-2
                        h-2
                        rounded-full
                        bg-green-500
                        animate-pulse
                    "></span>

                    Volunteers make the difference

                </div>


                <h1 class="
                    text-5xl
                    md:text-6xl
                    lg:text-7xl
                    font-extrabold
                    tracking-tight
                    leading-[1.05]
                    text-slate-950
                ">

                    Don't let good food

                    <span class="
                        block
                        text-green-600
                    ">
                        go to waste.
                    </span>

                </h1>


                <p class="
                    mt-7
                    text-lg
                    md:text-xl
                    leading-8
                    text-slate-600
                    max-w-xl
                ">

                    Join F-Destiny and become part of a community
                    that helps redirect surplus food from donors to
                    NGOs and people who need support.

                </p>


                <div class="
                    mt-9
                    flex
                    flex-col
                    sm:flex-row
                    gap-4
                ">

                    <a href="#application" class="
                        primary-btn
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        px-7
                        py-4
                        rounded-2xl
                        bg-green-600
                        hover:bg-green-700
                        text-white
                        font-bold
                        shadow-xl
                        shadow-green-600/20
                    ">

                        Become a Volunteer

                        <i data-lucide="arrow-right" class="w-5 h-5"></i>

                    </a>


                    <a href="#story" class="
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        px-7
                        py-4
                        rounded-2xl
                        bg-white
                        border
                        border-slate-200
                        hover:border-green-300
                        text-slate-700
                        font-semibold
                        transition
                    ">

                        Why volunteer?

                        <i data-lucide="heart" class="w-5 h-5 text-green-600"></i>

                    </a>

                </div>


                <div class="
                    mt-10
                    flex
                    items-center
                    gap-4
                    text-sm
                    text-slate-500
                ">

                    <div class="flex -space-x-3">

                        <div class="
                            w-10
                            h-10
                            rounded-full
                            bg-green-100
                            border-2
                            border-white
                            flex
                            items-center
                            justify-center
                        ">
                            🤝
                        </div>

                        <div class="
                            w-10
                            h-10
                            rounded-full
                            bg-emerald-100
                            border-2
                            border-white
                            flex
                            items-center
                            justify-center
                        ">
                            ❤️
                        </div>

                        <div class="
                            w-10
                            h-10
                            rounded-full
                            bg-lime-100
                            border-2
                            border-white
                            flex
                            items-center
                            justify-center
                        ">
                            🌱
                        </div>

                    </div>

                    <span>
                        Every contribution can create a meaningful impact.
                    </span>

                </div>

            </div>



            <!-- Hero visual -->

            <div class="
                relative
            ">

                <div class="
                    glass
                    rounded-[2rem]
                    p-5
                    relative
                    overflow-hidden
                " id="heroCard">

                    <div class="
                        h-[430px]
                        rounded-[1.5rem]
                        bg-gradient-to-br
                        from-green-700
                        via-emerald-600
                        to-green-900
                        relative
                        overflow-hidden
                    ">

                        <!-- Decorative circles -->

                        <div class="
                            absolute
                            w-72
                            h-72
                            rounded-full
                            bg-white/10
                            -top-20
                            -right-20
                        "></div>

                        <div class="
                            absolute
                            w-56
                            h-56
                            rounded-full
                            bg-white/10
                            -bottom-20
                            -left-20
                        "></div>


                        <div class="
                            relative
                            z-10
                            h-full
                            flex
                            flex-col
                            justify-center
                            items-center
                            text-center
                            p-10
                        ">

                            <div class="
                                w-24
                                h-24
                                rounded-3xl
                                bg-white/15
                                border
                                border-white/20
                                flex
                                items-center
                                justify-center
                                mb-7
                            ">

                                <i data-lucide="hand-heart" class="
                                    w-12
                                    h-12
                                    text-white
                                "></i>

                            </div>


                            <h2 class="
                                text-3xl
                                font-extrabold
                                text-white
                            ">
                                Small actions.
                            </h2>

                            <h2 class="
                                text-3xl
                                font-extrabold
                                text-green-200
                                mt-1
                            ">
                                Real impact.
                            </h2>


                            <p class="
                                mt-5
                                text-green-50
                                leading-7
                                max-w-sm
                            ">

                                A few hours of your time can help
                                move surplus food closer to someone
                                who needs it.

                            </p>


                            <div class="
                                mt-8
                                flex
                                gap-3
                                flex-wrap
                                justify-center
                            ">

                                <span class="
                                    px-4
                                    py-2
                                    rounded-full
                                    bg-white/10
                                    border
                                    border-white/20
                                    text-white
                                    text-sm
                                ">
                                    Food Recovery
                                </span>

                                <span class="
                                    px-4
                                    py-2
                                    rounded-full
                                    bg-white/10
                                    border
                                    border-white/20
                                    text-white
                                    text-sm
                                ">
                                    Community
                                </span>

                                <span class="
                                    px-4
                                    py-2
                                    rounded-full
                                    bg-white/10
                                    border
                                    border-white/20
                                    text-white
                                    text-sm
                                ">
                                    Sustainability
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Floating card -->

                <div class="
                    glass
                    absolute
                    -bottom-8
                    -left-6
                    md:-left-10
                    rounded-2xl
                    px-5
                    py-4
                    flex
                    items-center
                    gap-3
                ">

                    <div class="
                        w-11
                        h-11
                        rounded-xl
                        bg-green-100
                        flex
                        items-center
                        justify-center
                    ">

                        <i data-lucide="leaf" class="w-5 h-5 text-green-600"></i>

                    </div>

                    <div>

                        <p class="
                            text-xs
                            text-slate-500
                        ">
                            Your role
                        </p>

                        <p class="
                            font-bold
                            text-slate-900
                        ">
                            Create local impact
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>



    <!-- ========================================================= -->
    <!-- REAL WORLD STORY -->
    <!-- ========================================================= -->

    <section id="story" class="py-24 bg-white">

        <div class="
            max-w-7xl
            mx-auto
            px-6
            lg:px-8
        ">

            <div class="
                max-w-3xl
                mx-auto
                text-center
            ">

                <span class="
                    text-green-600
                    font-bold
                    text-sm
                    uppercase
                    tracking-widest
                ">
                    Why this matters
                </span>

                <h2 class="
                    mt-4
                    text-4xl
                    md:text-5xl
                    font-extrabold
                    tracking-tight
                    text-slate-950
                ">
                    One evening can change
                    <span class="text-green-600">
                        the story of a meal.
                    </span>
                </h2>

                <p class="
                    mt-5
                    text-slate-600
                    text-lg
                    leading-8
                ">

                    Every day, restaurants, events and households
                    can have safe surplus food left at the end of
                    service. At the same time, community organizations
                    work to support people who need food assistance.

                </p>

            </div>


            <div class="
                story-card
                mt-16
                rounded-[2rem]
                border
                border-green-100
                p-8
                md:p-12
                shadow-xl
                shadow-green-900/5
            ">

                <div class="
                    grid
                    lg:grid-cols-[auto_1fr]
                    gap-8
                    items-start
                ">

                    <div class="
                        w-16
                        h-16
                        rounded-2xl
                        bg-green-600
                        flex
                        items-center
                        justify-center
                        shadow-lg
                        shadow-green-600/20
                    ">

                        <i data-lucide="quote" class="w-8 h-8 text-white"></i>

                    </div>


                    <div>

                        <h3 class="
                            text-2xl
                            font-bold
                            text-slate-900
                        ">
                            A realistic F-Destiny scenario
                        </h3>


                        <p class="
                            mt-5
                            text-slate-600
                            leading-8
                            text-lg
                        ">

                            Imagine a local community event where
                            meals were prepared for hundreds of
                            attendees. At the end of the event,
                            a significant quantity of safe, untouched
                            food remained.

                        </p>


                        <p class="
                            mt-4
                            text-slate-600
                            leading-8
                            text-lg
                        ">

                            Instead of allowing that food to become
                            waste, a volunteer can help coordinate its
                            collection and delivery to a nearby NGO.
                            What may look like a simple pickup can become
                            a meaningful connection between a food donor
                            and a community organization.

                        </p>


                        <div class="
                            mt-8
                            grid
                            sm:grid-cols-3
                            gap-4
                        ">

                            <div class="
                                bg-white
                                rounded-2xl
                                p-5
                                border
                                border-green-100
                            ">

                                <i data-lucide="utensils" class="w-6 h-6 text-green-600"></i>

                                <p class="
                                    mt-3
                                    font-bold
                                    text-slate-900
                                ">
                                    Surplus Food
                                </p>

                                <p class="
                                    text-sm
                                    text-slate-500
                                    mt-1
                                ">
                                    Safe food remains after an event.
                                </p>

                            </div>


                            <div class="
                                bg-white
                                rounded-2xl
                                p-5
                                border
                                border-green-100
                            ">

                                <i data-lucide="truck" class="w-6 h-6 text-green-600"></i>

                                <p class="
                                    mt-3
                                    font-bold
                                    text-slate-900
                                ">
                                    Volunteer
                                </p>

                                <p class="
                                    text-sm
                                    text-slate-500
                                    mt-1
                                ">
                                    Helps coordinate the movement.
                                </p>

                            </div>


                            <div class="
                                bg-white
                                rounded-2xl
                                p-5
                                border
                                border-green-100
                            ">

                                <i data-lucide="heart-handshake" class="w-6 h-6 text-green-600"></i>

                                <p class="
                                    mt-3
                                    font-bold
                                    text-slate-900
                                ">
                                    Community
                                </p>

                                <p class="
                                    text-sm
                                    text-slate-500
                                    mt-1
                                ">
                                    Food reaches an organization.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>



    <!-- ========================================================= -->
    <!-- VOLUNTEER ROLES -->
    <!-- ========================================================= -->

    <section class="
        py-24
        bg-slate-50
    ">

        <div class="
            max-w-7xl
            mx-auto
            px-6
            lg:px-8
        ">

            <div class="
                text-center
                max-w-3xl
                mx-auto
            ">

                <span class="
                    text-green-600
                    font-bold
                    text-sm
                    uppercase
                    tracking-widest
                ">
                    Find your role
                </span>

                <h2 class="
                    mt-4
                    text-4xl
                    md:text-5xl
                    font-extrabold
                    text-slate-950
                ">
                    There is a place for you
                    <span class="text-green-600">
                        at F-Destiny.
                    </span>
                </h2>

                <p class="
                    mt-5
                    text-slate-600
                    text-lg
                ">

                    You don't need to be an expert.
                    Your time, communication skills and willingness
                    to help can all contribute to the mission.

                </p>

            </div>


            <div class="
                mt-16
                grid
                md:grid-cols-3
                gap-7
            ">

                <!-- Card 1 -->

                <div class="
                    feature-card
                    glass
                    rounded-3xl
                    p-8
                ">

                    <div class="
                        w-14
                        h-14
                        rounded-2xl
                        bg-green-100
                        flex
                        items-center
                        justify-center
                    ">

                        <i data-lucide="truck" class="w-7 h-7 text-green-600"></i>

                    </div>

                    <h3 class="
                        mt-6
                        text-xl
                        font-bold
                    ">
                        Food Collection
                    </h3>

                    <p class="
                        mt-3
                        text-slate-600
                        leading-7
                    ">

                        Help coordinate or support the movement
                        of surplus food from donors toward partner
                        organizations.

                    </p>

                </div>


                <!-- Card 2 -->

                <div class="
                    feature-card
                    glass
                    rounded-3xl
                    p-8
                ">

                    <div class="
                        w-14
                        h-14
                        rounded-2xl
                        bg-emerald-100
                        flex
                        items-center
                        justify-center
                    ">

                        <i data-lucide="users" class="w-7 h-7 text-emerald-600"></i>

                    </div>

                    <h3 class="
                        mt-6
                        text-xl
                        font-bold
                    ">
                        Community Support
                    </h3>

                    <p class="
                        mt-3
                        text-slate-600
                        leading-7
                    ">

                        Work with NGOs and local organizations to
                        help coordinate community-focused initiatives.

                    </p>

                </div>


                <!-- Card 3 -->

                <div class="
                    feature-card
                    glass
                    rounded-3xl
                    p-8
                ">

                    <div class="
                        w-14
                        h-14
                        rounded-2xl
                        bg-lime-100
                        flex
                        items-center
                        justify-center
                    ">

                        <i data-lucide="megaphone" class="w-7 h-7 text-lime-700"></i>

                    </div>

                    <h3 class="
                        mt-6
                        text-xl
                        font-bold
                    ">
                        Awareness
                    </h3>

                    <p class="
                        mt-3
                        text-slate-600
                        leading-7
                    ">

                        Help spread awareness about responsible
                        food use, redistribution and community action.

                    </p>

                </div>

            </div>

        </div>

    </section>



    <!-- ========================================================= -->
    <!-- IMPACT -->
    <!-- ========================================================= -->

    <section class="
        py-24
        bg-white
    ">

        <div class="
            max-w-7xl
            mx-auto
            px-6
            lg:px-8
        ">

            <div class="
                grid
                lg:grid-cols-2
                gap-16
                items-center
            ">

                <div>

                    <span class="
                        text-green-600
                        font-bold
                        text-sm
                        uppercase
                        tracking-widest
                    ">
                        Your contribution
                    </span>

                    <h2 class="
                        mt-4
                        text-4xl
                        md:text-5xl
                        font-extrabold
                        text-slate-950
                        leading-tight
                    ">

                        Volunteering is more
                        than giving time.

                    </h2>

                    <p class="
                        mt-6
                        text-lg
                        text-slate-600
                        leading-8
                    ">

                        It is about helping build a system where
                        usable food has a better chance of reaching
                        people and organizations that can benefit
                        from it.

                    </p>


                    <div class="mt-8 space-y-5">

                        <div class="flex gap-4">

                            <div class="
                                flex-shrink-0
                                w-10
                                h-10
                                rounded-xl
                                bg-green-100
                                flex
                                items-center
                                justify-center
                            ">

                                <i data-lucide="check" class="w-5 h-5 text-green-600"></i>

                            </div>

                            <div>

                                <h4 class="font-bold">
                                    Reduce avoidable food waste
                                </h4>

                                <p class="
                                    text-sm
                                    text-slate-500
                                    mt-1
                                ">
                                    Help make better use of surplus food.
                                </p>

                            </div>

                        </div>


                        <div class="flex gap-4">

                            <div class="
                                flex-shrink-0
                                w-10
                                h-10
                                rounded-xl
                                bg-green-100
                                flex
                                items-center
                                justify-center
                            ">

                                <i data-lucide="check" class="w-5 h-5 text-green-600"></i>

                            </div>

                            <div>

                                <h4 class="font-bold">
                                    Strengthen local connections
                                </h4>

                                <p class="
                                    text-sm
                                    text-slate-500
                                    mt-1
                                ">
                                    Connect donors, NGOs and volunteers.
                                </p>

                            </div>

                        </div>


                        <div class="flex gap-4">

                            <div class="
                                flex-shrink-0
                                w-10
                                h-10
                                rounded-xl
                                bg-green-100
                                flex
                                items-center
                                justify-center
                            ">

                                <i data-lucide="check" class="w-5 h-5 text-green-600"></i>

                            </div>

                            <div>

                                <h4 class="font-bold">
                                    Build a culture of sharing
                                </h4>

                                <p class="
                                    text-sm
                                    text-slate-500
                                    mt-1
                                ">
                                    Encourage sustainable community action.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Impact visual -->

                <div class="
                    grid
                    sm:grid-cols-2
                    gap-5
                ">

                    <div class="
                        feature-card
                        rounded-3xl
                        bg-green-50
                        border
                        border-green-100
                        p-7
                    ">

                        <i data-lucide="utensils" class="
                            w-8
                            h-8
                            text-green-600
                        "></i>

                        <div class="
                            mt-6
                            text-3xl
                            font-extrabold
                            text-slate-900
                        ">
                            Food
                        </div>

                        <p class="
                            mt-2
                            text-slate-600
                        ">
                            Better use of surplus meals.
                        </p>

                    </div>


                    <div class="
                        feature-card
                        rounded-3xl
                        bg-emerald-50
                        border
                        border-emerald-100
                        p-7
                        sm:mt-10
                    ">

                        <i data-lucide="users" class="
                            w-8
                            h-8
                            text-emerald-600
                        "></i>

                        <div class="
                            mt-6
                            text-3xl
                            font-extrabold
                            text-slate-900
                        ">
                            People
                        </div>

                        <p class="
                            mt-2
                            text-slate-600
                        ">
                            Stronger community connections.
                        </p>

                    </div>


                    <div class="
                        feature-card
                        rounded-3xl
                        bg-lime-50
                        border
                        border-lime-100
                        p-7
                    ">

                        <i data-lucide="leaf" class="
                            w-8
                            h-8
                            text-lime-700
                        "></i>

                        <div class="
                            mt-6
                            text-3xl
                            font-extrabold
                            text-slate-900
                        ">
                            Planet
                        </div>

                        <p class="
                            mt-2
                            text-slate-600
                        ">
                            Less unnecessary food waste.
                        </p>

                    </div>


                    <div class="
                        feature-card
                        rounded-3xl
                        bg-teal-50
                        border
                        border-teal-100
                        p-7
                        sm:mt-10
                    ">

                        <i data-lucide="heart" class="
                            w-8
                            h-8
                            text-teal-600
                        "></i>

                        <div class="
                            mt-6
                            text-3xl
                            font-extrabold
                            text-slate-900
                        ">
                            Purpose
                        </div>

                        <p class="
                            mt-2
                            text-slate-600
                        ">
                            Turn time into meaningful action.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>



    <!-- ========================================================= -->
    <!-- APPLICATION -->
    <!-- ========================================================= -->

    <section id="application" class="
        py-24
        bg-slate-950
        relative
        overflow-hidden
    ">

        <div class="
            absolute
            w-96
            h-96
            bg-green-600/20
            rounded-full
            blur-3xl
            -top-40
            -left-40
        "></div>

        <div class="
            absolute
            w-96
            h-96
            bg-emerald-500/10
            rounded-full
            blur-3xl
            -bottom-40
            -right-40
        "></div>


        <div class="
            relative
            max-w-7xl
            mx-auto
            px-6
            lg:px-8
        ">

            <div class="
                grid
                lg:grid-cols-[0.8fr_1.2fr]
                gap-14
                items-start
            ">

                <!-- Left -->

                <div class="
                    text-white
                    lg:sticky
                    lg:top-32
                ">

                    <span class="
                        text-green-400
                        font-bold
                        text-sm
                        uppercase
                        tracking-widest
                    ">
                        Volunteer application
                    </span>

                    <h2 class="
                        mt-5
                        text-4xl
                        md:text-5xl
                        font-extrabold
                        leading-tight
                    ">

                        Ready to be part
                        of the change?

                    </h2>

                    <p class="
                        mt-6
                        text-slate-300
                        leading-8
                        text-lg
                    ">

                        Tell us a little about yourself.
                        Our team can understand where your skills
                        and interests may fit within the F-Destiny
                        volunteer network.

                    </p>


                    <div class="mt-10 space-y-5">

                        <div class="flex gap-4">

                            <div class="
                                w-10
                                h-10
                                rounded-xl
                                bg-white/10
                                flex
                                items-center
                                justify-center
                            ">

                                <i data-lucide="clock" class="w-5 h-5 text-green-400"></i>

                            </div>

                            <div>

                                <h4 class="font-semibold">
                                    Flexible contribution
                                </h4>

                                <p class="
                                    text-sm
                                    text-slate-400
                                    mt-1
                                ">
                                    Contribute according to your availability.
                                </p>

                            </div>

                        </div>


                        <div class="flex gap-4">

                            <div class="
                                w-10
                                h-10
                                rounded-xl
                                bg-white/10
                                flex
                                items-center
                                justify-center
                            ">

                                <i data-lucide="users" class="w-5 h-5 text-green-400"></i>

                            </div>

                            <div>

                                <h4 class="font-semibold">
                                    Work with a community
                                </h4>

                                <p class="
                                    text-sm
                                    text-slate-400
                                    mt-1
                                ">
                                    Collaborate with donors, NGOs and volunteers.
                                </p>

                            </div>

                        </div>


                        <div class="flex gap-4">

                            <div class="
                                w-10
                                h-10
                                rounded-xl
                                bg-white/10
                                flex
                                items-center
                                justify-center
                            ">

                                <i data-lucide="sparkles" class="w-5 h-5 text-green-400"></i>

                            </div>

                            <div>

                                <h4 class="font-semibold">
                                    Learn by doing
                                </h4>

                                <p class="
                                    text-sm
                                    text-slate-400
                                    mt-1
                                ">
                                    Build teamwork and community experience.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- Form -->

                <div class="
                    bg-white
                    rounded-[2rem]
                    p-7
                    md:p-10
                    shadow-2xl
                ">

                    <?php if (!empty($message)): ?>

                        <div class="
                            mb-7
                            p-5
                            rounded-2xl
                            border
                            <?php
                            echo $messageType === "success"
                                ? "bg-green-50 border-green-200 text-green-800"
                                : "bg-red-50 border-red-200 text-red-800";
                            ?>
                        ">

                            <div class="
                                flex
                                gap-3
                                items-start
                            ">

                                <i data-lucide="<?php
                                echo $messageType === "success"
                                    ? "circle-check"
                                    : "circle-alert";
                                ?>" class="w-5 h-5 flex-shrink-0"></i>

                                <p class="font-medium">
                                    <?php
                                    echo htmlspecialchars($message);
                                    ?>
                                </p>

                            </div>

                        </div>

                    <?php endif; ?>


                    <div class="mb-8">

                        <h3 class="
                            text-2xl
                            font-extrabold
                            text-slate-950
                        ">
                            Join the volunteer network
                        </h3>

                        <p class="
                            mt-2
                            text-slate-500
                        ">
                            Fields marked with * are required.
                        </p>

                    </div>


                     <!-- ================= JOIN FORM ================= -->

                <form
                    method="POST"
                    action=""
                    class="space-y-5"
                >

                    <!-- IMPORTANT:
                         This distinguishes the form from
                         your existing signup form.
                    -->

                    <input
                        type="hidden"
                        name="join_volunteer"
                        value="1"
                    >


                    <!-- NAME -->

                    <div>

                        <label
                            class="block
                                   text-sm
                                   font-semibold
                                   text-slate-700
                                   mb-2"
                        >
                            Full Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($name) ?>"
                            required
                            class="input-box
                                   w-full
                                   rounded-xl
                                   px-4 py-3
                                   text-sm"
                            placeholder="Enter your full name"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div>

                        <label
                            class="block
                                   text-sm
                                   font-semibold
                                   text-slate-700
                                   mb-2"
                        >
                            Email Address *
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                            required
                            class="input-box
                                   w-full
                                   rounded-xl
                                   px-4 py-3
                                   text-sm"
                            placeholder="you@example.com"
                        >

                    </div>


                    <!-- PHONE + CITY -->

                    <div class="grid sm:grid-cols-2 gap-5">

                        <div>

                            <label
                                class="block
                                       text-sm
                                       font-semibold
                                       text-slate-700
                                       mb-2"
                            >
                                Phone
                            </label>

                            <input
                                type="tel"
                                name="phone"
                                value="<?= htmlspecialchars($phone) ?>"
                                class="input-box
                                       w-full
                                       rounded-xl
                                       px-4 py-3
                                       text-sm"
                                placeholder="+91 XXXXX XXXXX"
                            >

                        </div>


                        <div>

                            <label
                                class="block
                                       text-sm
                                       font-semibold
                                       text-slate-700
                                       mb-2"
                            >
                                City *
                            </label>

                            <input
                                type="text"
                                name="city"
                                value="<?= htmlspecialchars($city) ?>"
                                required
                                class="input-box
                                       w-full
                                       rounded-xl
                                       px-4 py-3
                                       text-sm"
                                placeholder="Your city"
                            >

                        </div>

                    </div>


                    <!-- SKILLS -->

                    <div>

                        <label
                            class="block
                                   text-sm
                                   font-semibold
                                   text-slate-700
                                   mb-2"
                        >
                            Skills / Interests
                        </label>

                        <input
                            type="text"
                            name="skills"
                            value="<?= htmlspecialchars($skills) ?>"
                            class="input-box
                                   w-full
                                   rounded-xl
                                   px-4 py-3
                                   text-sm"
                            placeholder="Driving, logistics, social media..."
                        >

                    </div>


                    <!-- REASON -->

                    <div>

                        <label
                            class="block
                                   text-sm
                                   font-semibold
                                   text-slate-700
                                   mb-2"
                        >
                            Why do you want to volunteer?
                        </label>

                        <textarea
                            name="reason"
                            rows="3"
                            class="input-box
                                   w-full
                                   rounded-xl
                                   px-4 py-3
                                   text-sm
                                   resize-none"
                            placeholder="Tell us why you want to join..."
                        ><?= htmlspecialchars($reason) ?></textarea>

                    </div>


                    <!-- PASSWORD -->

                    <div>

                        <label
                            class="block
                                   text-sm
                                   font-semibold
                                   text-slate-700
                                   mb-2"
                        >
                            Create Password *
                        </label>

                        <input
                            type="password"
                            name="password"
                            required
                            minlength="6"
                            class="input-box
                                   w-full
                                   rounded-xl
                                   px-4 py-3
                                   text-sm"
                            placeholder="Minimum 6 characters"
                        >

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div>

                        <label
                            class="block
                                   text-sm
                                   font-semibold
                                   text-slate-700
                                   mb-2"
                        >
                            Confirm Password *
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            required
                            minlength="6"
                            class="input-box
                                   w-full
                                   rounded-xl
                                   px-4 py-3
                                   text-sm"
                            placeholder="Re-enter your password"
                        >

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-btn
                            w-full
                            rounded-xl
                            py-4
                            text-slate-500
                            font-bold
                            text-sm"
                    >

                        Join Volunteer Network
                        <span class="ml-2">
                            →
                        </span>

                    </button>


                    <!-- LOGIN LINK -->

                    <p
                        class="text-center
                               text-sm
                               text-slate-500
                               pt-2"
                    >

                        Already have an account?

                        <a
                            href="index.php"
                            class="font-semibold
                                   text-emerald-600
                                   hover:text-emerald-800
                                   transition"
                        >
                            Login here
                        </a>

                    </p>

                </form>


                </div>

            </div>

        </div>

    </section>



    <!-- ========================================================= -->
    <!-- FINAL CTA -->
    <!-- ========================================================= -->

    <section class="
        py-24
        bg-white
    ">

        <div class="
            max-w-5xl
            mx-auto
            px-6
            text-center
        ">

            <div class="
                w-16
                h-16
                mx-auto
                rounded-2xl
                bg-green-100
                flex
                items-center
                justify-center
            ">

                <i data-lucide="heart" class="
                    w-8
                    h-8
                    text-green-600
                "></i>

            </div>


            <h2 class="
                mt-7
                text-4xl
                md:text-5xl
                font-extrabold
                text-slate-950
            ">

                One volunteer can start
                a chain of good.

            </h2>


            <p class="
                mt-5
                text-lg
                text-slate-600
                max-w-2xl
                mx-auto
                leading-8
            ">

                F-Destiny brings technology and people together
                to make food redistribution more organized,
                accessible and community-driven.

            </p>


            <a href="#application" class="
                primary-btn
                mt-8
                inline-flex
                items-center
                gap-2
                px-7
                py-4
                rounded-2xl
                bg-green-600
                hover:bg-green-700
                text-white
                font-bold
                shadow-xl
                shadow-green-600/20
            ">

                Start Your Application

                <i data-lucide="arrow-up" class="w-5 h-5"></i>

            </a>

        </div>

    </section>



    <!-- ========================================================= -->
    <!-- FOOTER -->
    <!-- ========================================================= -->

    <footer class="
        bg-slate-950
        text-white
        pt-16
        pb-8
    ">

        <div class="
            max-w-7xl
            mx-auto
            px-6
            lg:px-8
        ">

            <div class="
                grid
                md:grid-cols-3
                gap-10
                pb-12
                border-b
                border-white/10
            ">

                <div>

                    <div class="
                        flex
                        items-center
                        gap-3
                    ">

                        <div class="
                            w-11
                            h-11
                            rounded-xl
                            bg-green-600
                            flex
                            items-center
                            justify-center
                        ">

                            <i data-lucide="heart-handshake" class="w-6 h-6"></i>

                        </div>

                        <span class="
                            text-xl
                            font-extrabold
                        ">
                            F-Destiny
                        </span>

                    </div>


                    <p class="
                        mt-5
                        text-slate-400
                        leading-7
                        max-w-sm
                    ">

                        Connecting surplus food, volunteers,
                        NGOs and communities to create a more
                        sustainable future.

                    </p>

                </div>


                <div>

                    <h4 class="font-bold">
                        Explore
                    </h4>

                    <div class="
                        mt-5
                        space-y-3
                        text-slate-400
                    ">

                        <a href="index.php" class="block hover:text-white transition">
                            Home
                        </a>

                        <a href="about.php" class="block hover:text-white transition">
                            About F-Destiny
                        </a>

                        <a href="impact.php" class="block hover:text-white transition">
                            Our Impact
                        </a>

                        <a href="#application" class="block hover:text-white transition">
                            Join Team
                        </a>

                    </div>

                </div>


                <div>

                    <h4 class="font-bold">
                        Our Mission
                    </h4>

                    <p class="
                        mt-5
                        text-slate-400
                        leading-7
                    ">

                        Reduce avoidable food waste and help create
                        stronger connections between food donors,
                        NGOs and volunteers.

                    </p>

                </div>

            </div>


            <div class="
                pt-8
                flex
                flex-col
                md:flex-row
                justify-between
                gap-4
                text-sm
                text-slate-500
            ">

                <p>
                    © <?php echo date("Y"); ?> F-Destiny. All rights reserved.
                </p>

                <p>
                    Built for community. Driven by purpose.
                </p>

            </div>

        </div>

    </footer>



    <!-- ========================================================= -->
    <!-- JAVASCRIPT -->
    <!-- ========================================================= -->

    <script>

        /*
        |--------------------------------------------------------------------------
        | Lucide Icons
        |--------------------------------------------------------------------------
        */

        lucide.createIcons();


        /*
        |--------------------------------------------------------------------------
        | Mobile menu
        |--------------------------------------------------------------------------
        */

        const mobileMenuButton =
            document.getElementById("mobileMenuButton");

        const mobileMenu =
            document.getElementById("mobileMenu");

        mobileMenuButton.addEventListener(
            "click",
            function () {

                mobileMenu.classList.toggle("hidden");

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Anime.js page entrance
        |--------------------------------------------------------------------------
        */

        anime({

            targets: "#heroText > *",

            translateY: [30, 0],

            opacity: [0, 1],

            delay: anime.stagger(120),

            duration: 900,

            easing: "easeOutExpo"

        });


        /*
        |--------------------------------------------------------------------------
        | Hero card animation
        |--------------------------------------------------------------------------
        */

        anime({

            targets: "#heroCard",

            translateY: [30, 0],

            opacity: [0, 1],

            duration: 1100,

            easing: "easeOutExpo",

            delay: 250

        });


        /*
        |--------------------------------------------------------------------------
        | Cursor-sensitive hero card
        |--------------------------------------------------------------------------
        */

        const heroCard =
            document.getElementById("heroCard");

        heroCard.addEventListener(
            "mousemove",
            function (event) {

                const rect =
                    heroCard.getBoundingClientRect();

                const x =
                    event.clientX - rect.left;

                const y =
                    event.clientY - rect.top;

                const rotateY =
                    ((x / rect.width) - 0.5) * 8;

                const rotateX =
                    ((y / rect.height) - 0.5) * -8;

                heroCard.style.transform =
                    `
                perspective(1000px)
                rotateX(${rotateX}deg)
                rotateY(${rotateY}deg)
                translateY(-4px)
                `;

            }
        );


        heroCard.addEventListener(
            "mouseleave",
            function () {

                heroCard.style.transform =
                    "perspective(1000px) rotateX(0deg) rotateY(0deg)";

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Feature cards cursor interaction
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(".feature-card")
            .forEach(function (card) {

                card.addEventListener(
                    "mousemove",
                    function (event) {

                        const rect =
                            card.getBoundingClientRect();

                        const x =
                            event.clientX - rect.left;

                        const y =
                            event.clientY - rect.top;

                        const rotateY =
                            ((x / rect.width) - 0.5) * 5;

                        const rotateX =
                            ((y / rect.height) - 0.5) * -5;

                        card.style.transform =
                            `
                        perspective(900px)
                        rotateX(${rotateX}deg)
                        rotateY(${rotateY}deg)
                        translateY(-8px)
                        `;

                    }
                );


                card.addEventListener(
                    "mouseleave",
                    function () {

                        card.style.transform = "";

                    }
                );

            });


        /*
        |--------------------------------------------------------------------------
        | Form focus animation
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(".form-input")
            .forEach(function (input) {

                input.addEventListener(
                    "focus",
                    function () {

                        anime({

                            targets: input,

                            scale: 1.01,

                            duration: 200,

                            easing: "easeOutQuad"

                        });

                    }
                );


                input.addEventListener(
                    "blur",
                    function () {

                        anime({

                            targets: input,

                            scale: 1,

                            duration: 200,

                            easing: "easeOutQuad"

                        });

                    }
                );

            });


        /*
        |--------------------------------------------------------------------------
        | Submit button animation
        |--------------------------------------------------------------------------
        */

        const form =
            document.querySelector("form");

        form.addEventListener(
            "submit",
            function () {

                const button =
                    form.querySelector("button");

                anime({
                    targets: '.glass',
                    translateY: [-8, 0],
                    opacity: [0, 1],
                    duration: 900,
                    easing: 'easeOutExpo'
                });

            }
        );

    </script>


</body>

</html>