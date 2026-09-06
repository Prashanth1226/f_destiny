<?php
require_once "config/db.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>About F-Destiny | Food Redistribution Platform</title>


    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>


    <!-- Tailwind Configuration -->
    <script>

        tailwind.config = {

            theme: {

                extend: {

                    fontFamily: {

                        sans: [
                            "Inter",
                            "ui-sans-serif",
                            "system-ui",
                            "sans-serif"
                        ]

                    },

                    animation: {

                        "float":
                            "float 6s ease-in-out infinite",

                        "float-slow":
                            "float 9s ease-in-out infinite",

                        "pulse-slow":
                            "pulse 4s ease-in-out infinite"

                    },

                    keyframes: {

                        float: {

                            "0%, 100%": {
                                transform: "translateY(0px)"
                            },

                            "50%": {
                                transform: "translateY(-15px)"
                            }

                        }

                    }

                }

            }

        }

    </script>


    <style>

        html {
            scroll-behavior: smooth;
        }


        body {
            background: #f5faf8;
        }


        /* Glass effect */

        .glass {

            background:
                rgba(255, 255, 255, 0.65);

            backdrop-filter:
                blur(20px);

            -webkit-backdrop-filter:
                blur(20px);

            border:
                1px solid
                rgba(255, 255, 255, 0.75);

        }


        /* Glossy card */

        .glossy-card {

            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,0.88),
                    rgba(255,255,255,0.58)
                );

            backdrop-filter:
                blur(20px);

            -webkit-backdrop-filter:
                blur(20px);

            border:
                1px solid
                rgba(255,255,255,0.85);

            box-shadow:
                0 20px 60px
                rgba(15, 118, 87, 0.08);

        }


        /* Glow */

        .green-glow {

            box-shadow:
                0 0 80px
                rgba(16,185,129,0.16);

        }


        /* Text gradient */

        .gradient-text {

            background:
                linear-gradient(
                    135deg,
                    #047857,
                    #10b981,
                    #34d399
                );

            -webkit-background-clip: text;

            background-clip: text;

            color: transparent;

        }


        /* Gradient border */

        .gradient-border {

            position: relative;

        }


        .gradient-border::before {

            content: "";

            position: absolute;

            inset: 0;

            border-radius: inherit;

            padding: 1px;

            background:
                linear-gradient(
                    135deg,
                    rgba(16,185,129,0.45),
                    transparent,
                    rgba(52,211,153,0.25)
                );

            -webkit-mask:
                linear-gradient(#fff 0 0)
                content-box,
                linear-gradient(#fff 0 0);

            -webkit-mask-composite:
                xor;

            mask-composite: exclude;

            pointer-events: none;

        }


        /* Hide scrollbar */

        ::-webkit-scrollbar {

            width: 8px;

        }


        ::-webkit-scrollbar-track {

            background: #f1f5f3;

        }


        ::-webkit-scrollbar-thumb {

            background: #10b981;

            border-radius: 10px;

        }


        /* Mobile menu */

        #mobileMenu {

            transition:
                max-height 0.35s ease,
                opacity 0.35s ease;

        }

    </style>

</head>


<body class="text-slate-800">


<!-- =========================================================
     BACKGROUND GLOW
========================================================= -->

<div class="fixed inset-0 -z-10 overflow-hidden">

    <div
        class="absolute -top-40 -left-40
               w-96 h-96
               rounded-full
               bg-emerald-300/20
               blur-3xl"
    ></div>


    <div
        class="absolute top-1/3 -right-40
               w-96 h-96
               rounded-full
               bg-green-300/15
               blur-3xl"
    ></div>


    <div
        class="absolute bottom-0 left-1/3
               w-80 h-80
               rounded-full
               bg-teal-200/20
               blur-3xl"
    ></div>

</div>



<!-- =========================================================
     NAVBAR
========================================================= -->

<header
    class="sticky top-0 z-50
           border-b border-white/60
           bg-white/70
           backdrop-blur-xl"
>

    <nav
        class="max-w-7xl mx-auto
               px-5 sm:px-8 lg:px-10
               h-20
               flex items-center
               justify-between"
    >


        <!-- LOGO -->

        <a
            href="index.php"
            class="flex items-center gap-3"
        >

            <div
                class="w-11 h-11
                       rounded-2xl
                       bg-gradient-to-br
                       from-emerald-500
                       to-green-700
                       flex items-center
                       justify-center
                       text-xl
                       shadow-lg
                       shadow-emerald-500/20"
            >

                🌱

            </div>


            <div>

                <div
                    class="text-xl
                           font-extrabold
                           tracking-tight
                           text-slate-900"
                >

                    F-Destiny

                </div>


                <div
                    class="text-[10px]
                           uppercase
                           tracking-[0.2em]
                           text-emerald-600
                           font-bold"
                >

                    Food • People • Future

                </div>

            </div>

        </a>



        <!-- DESKTOP NAVIGATION -->

        <div
            class="hidden md:flex
                   items-center
                   gap-2"
        >

            <a
                href="index.php"
                class="px-4 py-2
                       rounded-xl
                       text-sm
                       font-semibold
                       text-slate-600
                       hover:text-emerald-700
                       hover:bg-emerald-50
                       transition"
            >

                Home

            </a>


            <a
                href="#about"
                class="px-4 py-2
                       rounded-xl
                       text-sm
                       font-semibold
                       text-emerald-700
                       bg-emerald-50"
            >

                About

            </a>


            <a
                href="#how-it-works"
                class="px-4 py-2
                       rounded-xl
                       text-sm
                       font-semibold
                       text-slate-600
                       hover:text-emerald-700
                       hover:bg-emerald-50
                       transition"
            >

                How It Works

            </a>


            <a
                href="#impact"
                class="px-4 py-2
                       rounded-xl
                       text-sm
                       font-semibold
                       text-slate-600
                       hover:text-emerald-700
                       hover:bg-emerald-50
                       transition"
            >

                Impact

            </a>


            <a
                href="index.php"
                class="ml-2
                       px-5 py-2.5
                       rounded-xl
                       text-sm
                       font-bold
                       text-white
                       bg-gradient-to-r
                       from-emerald-600
                       to-green-600
                       shadow-lg
                       shadow-emerald-600/20
                       hover:-translate-y-0.5
                       transition"
            >

                Back to Home

            </a>

        </div>



        <!-- MOBILE BUTTON -->

        <button
            onclick="toggleMenu()"
            class="md:hidden
                   w-11 h-11
                   rounded-xl
                   bg-emerald-50
                   text-emerald-700
                   flex items-center
                   justify-center
                   text-xl"
        >

            ☰

        </button>

    </nav>



    <!-- MOBILE MENU -->

    <div
        id="mobileMenu"
        class="md:hidden
               max-h-0
               opacity-0
               overflow-hidden"
    >

        <div
            class="px-5 pb-5
                   flex flex-col gap-2"
        >

            <a
                href="index.php"
                class="p-3 rounded-xl
                       hover:bg-emerald-50"
            >

                Home

            </a>


            <a
                href="#about"
                class="p-3 rounded-xl
                       hover:bg-emerald-50"
            >

                About

            </a>


            <a
                href="#how-it-works"
                class="p-3 rounded-xl
                       hover:bg-emerald-50"
            >

                How It Works

            </a>


            <a
                href="#impact"
                class="p-3 rounded-xl
                       hover:bg-emerald-50"
            >

                Impact

            </a>


            <a
                href="index.php"
                class="p-3 rounded-xl
                       bg-emerald-600
                       text-white
                       text-center
                       font-semibold"
            >

                Back to Home

            </a>

        </div>

    </div>

</header>



<!-- =========================================================
     HERO
========================================================= -->

<section
    id="about"
    class="relative
           pt-16 sm:pt-24
           pb-20
           px-5"
>

    <div
        class="max-w-7xl
               mx-auto
               grid
               lg:grid-cols-2
               gap-14
               items-center"
    >


        <!-- LEFT -->

        <div>

            <div
                class="inline-flex
                       items-center
                       gap-2
                       px-4 py-2
                       rounded-full
                       bg-emerald-50
                       border
                       border-emerald-100
                       text-emerald-700
                       text-xs
                       font-bold
                       uppercase
                       tracking-wider
                       mb-7"
            >

                <span
                    class="w-2 h-2
                           rounded-full
                           bg-emerald-500
                           animate-pulse"
                ></span>

                Smart Food Redistribution Platform

            </div>


            <h1
                class="text-5xl
                       sm:text-6xl
                       lg:text-7xl
                       font-black
                       tracking-tight
                       leading-[0.98]
                       text-slate-900"
            >

                Turning

                <span class="gradient-text">
                    surplus
                </span>

                into

                <span class="gradient-text">
                    impact.
                </span>

            </h1>


            <p
                class="mt-7
                       text-lg
                       leading-8
                       text-slate-500
                       max-w-2xl"
            >

                F-Destiny is a technology-driven food
                redistribution ecosystem that connects
                donors, NGOs and volunteers to help move
                surplus food where it can create meaningful
                value.

            </p>


            <!-- HERO BUTTONS -->

            <div
                class="mt-9
                       flex flex-wrap
                       gap-4"
            >

                <a
                    href="#how-it-works"
                    class="px-6 py-3.5
                           rounded-2xl
                           bg-gradient-to-r
                           from-emerald-600
                           to-green-600
                           text-white
                           font-bold
                           shadow-xl
                           shadow-emerald-600/20
                           hover:-translate-y-1
                           transition"
                >

                    Explore How It Works →

                </a>


                <a
                    href="#impact"
                    class="px-6 py-3.5
                           rounded-2xl
                           bg-white/80
                           border
                           border-slate-200
                           text-slate-700
                           font-bold
                           hover:bg-white
                           hover:-translate-y-1
                           transition"
                >

                    Our Impact

                </a>

            </div>


            <!-- MINI TRUST -->

            <div
                class="mt-10
                       flex flex-wrap
                       items-center
                       gap-6
                       text-sm
                       text-slate-500"
            >

                <div class="flex items-center gap-2">

                    <span
                        class="text-emerald-600"
                    >
                        ✓
                    </span>

                    Donor Network

                </div>


                <div class="flex items-center gap-2">

                    <span
                        class="text-emerald-600"
                    >
                        ✓
                    </span>

                    NGO Collaboration

                </div>


                <div class="flex items-center gap-2">

                    <span
                        class="text-emerald-600"
                    >
                        ✓
                    </span>

                    Volunteer Support

                </div>

            </div>

        </div>



        <!-- RIGHT VISUAL -->

        <div
            class="relative
                   min-h-[500px]
                   flex items-center
                   justify-center"
        >


            <!-- MAIN GLASS PANEL -->

            <div
                class="relative
                       w-full
                       max-w-lg
                       rounded-[2rem]
                       glossy-card
                       green-glow
                       p-7
                       animate-float"
            >

                <!-- TOP -->

                <div
                    class="flex
                           items-center
                           justify-between
                           mb-7"
                >

                    <div>

                        <p
                            class="text-xs
                                   uppercase
                                   tracking-widest
                                   text-slate-400
                                   font-bold"
                        >

                            F-Destiny Network

                        </p>

                        <h3
                            class="text-2xl
                                   font-extrabold
                                   mt-1"
                        >

                            Food Journey

                        </h3>

                    </div>


                    <div
                        class="w-12 h-12
                               rounded-2xl
                               bg-emerald-50
                               flex items-center
                               justify-center
                               text-2xl"
                    >

                        🍱

                    </div>

                </div>


                <!-- FLOW -->

                <div class="space-y-4">


                    <div
                        class="flex
                               items-center
                               gap-4
                               p-4
                               rounded-2xl
                               bg-white/70
                               border
                               border-white"
                    >

                        <div
                            class="w-12 h-12
                                   rounded-xl
                                   bg-orange-50
                                   flex items-center
                                   justify-center
                                   text-xl"
                        >

                            🏪

                        </div>


                        <div class="flex-1">

                            <p
                                class="font-bold"
                            >

                                Food Donor

                            </p>

                            <p
                                class="text-xs
                                       text-slate-400
                                       mt-1"
                            >

                                Surplus food available

                            </p>

                        </div>


                        <span
                            class="text-emerald-500
                                   font-bold"
                        >

                            ✓

                        </span>

                    </div>



                    <div
                        class="flex
                               justify-center"
                    >

                        <div
                            class="h-7
                                   border-l-2
                                   border-dashed
                                   border-emerald-300"
                        ></div>

                    </div>



                    <div
                        class="flex
                               items-center
                               gap-4
                               p-4
                               rounded-2xl
                               bg-white/70
                               border
                               border-white"
                    >

                        <div
                            class="w-12 h-12
                                   rounded-xl
                                   bg-emerald-50
                                   flex items-center
                                   justify-center
                                   text-xl"
                        >

                            🤝

                        </div>


                        <div class="flex-1">

                            <p
                                class="font-bold"
                            >

                                NGO Partner

                            </p>

                            <p
                                class="text-xs
                                       text-slate-400
                                       mt-1"
                            >

                                Donation accepted

                            </p>

                        </div>


                        <span
                            class="text-emerald-500
                                   font-bold"
                        >

                            ✓

                        </span>

                    </div>



                    <div
                        class="flex
                               justify-center"
                    >

                        <div
                            class="h-7
                                   border-l-2
                                   border-dashed
                                   border-emerald-300"
                        ></div>

                    </div>



                    <div
                        class="flex
                               items-center
                               gap-4
                               p-4
                               rounded-2xl
                               bg-white/70
                               border
                               border-white"
                    >

                        <div
                            class="w-12 h-12
                                   rounded-xl
                                   bg-blue-50
                                   flex items-center
                                   justify-center
                                   text-xl"
                        >

                            🚚

                        </div>


                        <div class="flex-1">

                            <p
                                class="font-bold"
                            >

                                Volunteer

                            </p>

                            <p
                                class="text-xs
                                       text-slate-400
                                       mt-1"
                            >

                                Collection & delivery

                            </p>

                        </div>


                        <span
                            class="text-emerald-500
                                   font-bold"
                        >

                            ✓

                        </span>

                    </div>

                </div>


                <!-- BOTTOM -->

                <div
                    class="mt-6
                           p-4
                           rounded-2xl
                           bg-gradient-to-r
                           from-emerald-50
                           to-green-50
                           border
                           border-emerald-100"
                >

                    <div
                        class="flex
                               items-center
                               justify-between"
                    >

                        <div>

                            <p
                                class="text-xs
                                       text-slate-400"
                            >

                                Network Status

                            </p>

                            <p
                                class="font-bold
                                       text-emerald-700
                                       mt-1"
                            >

                                ● Active & Connected

                            </p>

                        </div>


                        <div
                            class="text-2xl"
                        >

                            🌍

                        </div>

                    </div>

                </div>

            </div>


            <!-- FLOATING CARD -->

            <div
                class="absolute
                       -top-5
                       -right-3
                       sm:right-0
                       glass
                       rounded-2xl
                       px-5 py-4
                       shadow-xl
                       animate-float-slow"
            >

                <p
                    class="text-xs
                           text-slate-400"
                >

                    Mission

                </p>

                <p
                    class="font-bold
                           text-emerald-700"
                >

                    Reduce Food Waste ♻️

                </p>

            </div>


            <!-- FLOATING CARD -->

            <div
                class="absolute
                       -bottom-5
                       -left-2
                       sm:left-0
                       glass
                       rounded-2xl
                       px-5 py-4
                       shadow-xl"
            >

                <p
                    class="text-xs
                           text-slate-400"
                >

                    Powered by

                </p>

                <p
                    class="font-bold"
                >

                    People + Technology

                </p>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     WHAT WE DO
========================================================= -->

<section
    class="py-24
           px-5
           bg-white/40"
>

    <div class="max-w-7xl mx-auto">


        <div
            class="max-w-2xl
                   mx-auto
                   text-center
                   mb-14"
        >

            <span
                class="text-emerald-600
                       text-sm
                       font-bold
                       uppercase
                       tracking-widest"
            >

                What We Do

            </span>


            <h2
                class="mt-3
                       text-4xl
                       sm:text-5xl
                       font-black
                       tracking-tight"
            >

                One platform.

                <span class="gradient-text">
                    Multiple possibilities.
                </span>

            </h2>


            <p
                class="mt-5
                       text-slate-500
                       leading-7"
            >

                F-Destiny creates a digital bridge between
                the people who have surplus food and the
                organizations and volunteers who can help
                redistribute it.

            </p>

        </div>



        <div
            class="grid
                   sm:grid-cols-2
                   lg:grid-cols-4
                   gap-6"
        >


            <!-- CARD 1 -->

            <div
                class="glossy-card
                       gradient-border
                       rounded-3xl
                       p-7
                       hover:-translate-y-2
                       transition
                       duration-300"
            >

                <div
                    class="w-14 h-14
                           rounded-2xl
                           bg-orange-50
                           flex items-center
                           justify-center
                           text-2xl
                           mb-6"
                >

                    🍱

                </div>


                <h3
                    class="text-xl
                           font-extrabold
                           mb-3"
                >

                    Reduce Waste

                </h3>


                <p
                    class="text-sm
                           text-slate-500
                           leading-7"
                >

                    Help redirect usable surplus food
                    instead of allowing it to become
                    unnecessary waste.

                </p>

            </div>



            <!-- CARD 2 -->

            <div
                class="glossy-card
                       gradient-border
                       rounded-3xl
                       p-7
                       hover:-translate-y-2
                       transition
                       duration-300"
            >

                <div
                    class="w-14 h-14
                           rounded-2xl
                           bg-emerald-50
                           flex items-center
                           justify-center
                           text-2xl
                           mb-6"
                >

                    🤝

                </div>


                <h3
                    class="text-xl
                           font-extrabold
                           mb-3"
                >

                    Connect People

                </h3>


                <p
                    class="text-sm
                           text-slate-500
                           leading-7"
                >

                    Connect donors, NGOs and volunteers
                    through a centralized digital platform.

                </p>

            </div>



            <!-- CARD 3 -->

            <div
                class="glossy-card
                       gradient-border
                       rounded-3xl
                       p-7
                       hover:-translate-y-2
                       transition
                       duration-300"
            >

                <div
                    class="w-14 h-14
                           rounded-2xl
                           bg-blue-50
                           flex items-center
                           justify-center
                           text-2xl
                           mb-6"
                >

                    🚚

                </div>


                <h3
                    class="text-xl
                           font-extrabold
                           mb-3"
                >

                    Enable Delivery

                </h3>


                <p
                    class="text-sm
                           text-slate-500
                           leading-7"
                >

                    Volunteers can support the movement
                    of food between donors and partner
                    organizations.

                </p>

            </div>



            <!-- CARD 4 -->

            <div
                class="glossy-card
                       gradient-border
                       rounded-3xl
                       p-7
                       hover:-translate-y-2
                       transition
                       duration-300"
            >

                <div
                    class="w-14 h-14
                           rounded-2xl
                           bg-purple-50
                           flex items-center
                           justify-center
                           text-2xl
                           mb-6"
                >

                    📊

                </div>


                <h3
                    class="text-xl
                           font-extrabold
                           mb-3"
                >

                    Track Progress

                </h3>


                <p
                    class="text-sm
                           text-slate-500
                           leading-7"
                >

                    Organize donation activity and
                    provide better visibility into the
                    redistribution process.

                </p>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section
    id="how-it-works"
    class="py-24
           px-5"
>

    <div class="max-w-7xl mx-auto">


        <div
            class="text-center
                   max-w-2xl
                   mx-auto
                   mb-16"
        >

            <span
                class="text-emerald-600
                       text-sm
                       font-bold
                       uppercase
                       tracking-widest"
            >

                How It Works

            </span>


            <h2
                class="mt-3
                       text-4xl
                       sm:text-5xl
                       font-black"
            >

                From surplus

                <span class="gradient-text">
                    to support.
                </span>

            </h2>

        </div>



        <div
            class="grid
                   md:grid-cols-3
                   gap-8
                   relative"
        >


            <!-- STEP 1 -->

            <div class="relative">

                <div
                    class="glossy-card
                           rounded-3xl
                           p-8
                           h-full"
                >

                    <div
                        class="flex
                               items-center
                               justify-between
                               mb-7"
                    >

                        <span
                            class="w-12 h-12
                                   rounded-2xl
                                   bg-emerald-600
                                   text-white
                                   flex items-center
                                   justify-center
                                   font-black"
                        >

                            01

                        </span>


                        <span
                            class="text-3xl"
                        >

                            🏪

                        </span>

                    </div>


                    <h3
                        class="text-2xl
                               font-black
                               mb-3"
                    >

                        Donate

                    </h3>


                    <p
                        class="text-slate-500
                               leading-7"
                    >

                        Donors register available surplus
                        food and provide details about
                        quantity, location and availability.

                    </p>

                </div>

            </div>



            <!-- STEP 2 -->

            <div class="relative">

                <div
                    class="glossy-card
                           rounded-3xl
                           p-8
                           h-full"
                >

                    <div
                        class="flex
                               items-center
                               justify-between
                               mb-7"
                    >

                        <span
                            class="w-12 h-12
                                   rounded-2xl
                                   bg-emerald-600
                                   text-white
                                   flex items-center
                                   justify-center
                                   font-black"
                        >

                            02

                        </span>


                        <span
                            class="text-3xl"
                        >

                            🏢

                        </span>

                    </div>


                    <h3
                        class="text-2xl
                               font-black
                               mb-3"
                    >

                        Connect

                    </h3>


                    <p
                        class="text-slate-500
                               leading-7"
                    >

                        NGOs can discover suitable
                        donations and coordinate the
                        next stage of the process.

                    </p>

                </div>

            </div>



            <!-- STEP 3 -->

            <div class="relative">

                <div
                    class="glossy-card
                           rounded-3xl
                           p-8
                           h-full"
                >

                    <div
                        class="flex
                               items-center
                               justify-between
                               mb-7"
                    >

                        <span
                            class="w-12 h-12
                                   rounded-2xl
                                   bg-emerald-600
                                   text-white
                                   flex items-center
                                   justify-center
                                   font-black"
                        >

                            03

                        </span>


                        <span
                            class="text-3xl"
                        >

                            🚚

                        </span>

                    </div>


                    <h3
                        class="text-2xl
                               font-black
                               mb-3"
                    >

                        Redistribute

                    </h3>


                    <p
                        class="text-slate-500
                               leading-7"
                    >

                        Volunteers can support collection
                        and delivery so the food reaches
                        its intended destination.

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     MISSION / VISION
========================================================= -->

<section
    class="py-24
           px-5
           bg-white/40"
>

    <div
        class="max-w-7xl
               mx-auto
               grid
               lg:grid-cols-2
               gap-7"
    >


        <!-- MISSION -->

        <div
            class="relative
                   overflow-hidden
                   rounded-[2rem]
                   p-9 sm:p-12
                   text-white
                   bg-gradient-to-br
                   from-emerald-700
                   via-emerald-600
                   to-green-500
                   shadow-2xl
                   shadow-emerald-600/20"
        >

            <div
                class="absolute
                       -right-16
                       -bottom-16
                       text-[180px]
                       opacity-10"
            >

                ♻️

            </div>


            <div
                class="relative z-10"
            >

                <div
                    class="w-14 h-14
                           rounded-2xl
                           bg-white/15
                           backdrop-blur
                           flex items-center
                           justify-center
                           text-2xl
                           mb-7"
                >

                    🎯

                </div>


                <h2
                    class="text-3xl
                           sm:text-4xl
                           font-black
                           mb-5"
                >

                    Our Mission

                </h2>


                <p
                    class="text-emerald-50
                           leading-8"
                >

                    To create a connected digital ecosystem
                    that helps reduce avoidable food waste
                    while making collaboration between
                    donors, NGOs and volunteers simpler,
                    faster and more transparent.

                </p>

            </div>

        </div>



        <!-- VISION -->

        <div
            class="relative
                   overflow-hidden
                   rounded-[2rem]
                   p-9 sm:p-12
                   glossy-card
                   gradient-border"
        >

            <div
                class="absolute
                       -right-10
                       -bottom-10
                       text-[150px]
                       opacity-5"
            >

                🌍

            </div>


            <div
                class="relative z-10"
            >

                <div
                    class="w-14 h-14
                           rounded-2xl
                           bg-emerald-50
                           flex items-center
                           justify-center
                           text-2xl
                           mb-7"
                >

                    🔭

                </div>


                <h2
                    class="text-3xl
                           sm:text-4xl
                           font-black
                           mb-5"
                >

                    Our Vision

                </h2>


                <p
                    class="text-slate-500
                           leading-8"
                >

                    A future where surplus food is treated
                    as a valuable resource and technology
                    makes responsible redistribution
                    accessible to communities everywhere.

                </p>

            </div>

        </div>


    </div>

</section>



<!-- =========================================================
     IMPACT
========================================================= -->

<section
    id="impact"
    class="py-24
           px-5"
>

    <div class="max-w-7xl mx-auto">


        <div
            class="text-center
                   max-w-2xl
                   mx-auto
                   mb-14"
        >

            <span
                class="text-emerald-600
                       text-sm
                       font-bold
                       uppercase
                       tracking-widest"
            >

                Our Impact

            </span>


            <h2
                class="mt-3
                       text-4xl
                       sm:text-5xl
                       font-black"
            >

                Making every

                <span class="gradient-text">
                    connection count.
                </span>

            </h2>


            <p
                class="mt-5
                       text-slate-500
                       leading-7"
            >

                F-Destiny focuses on measurable social,
                environmental and community-level impact.

            </p>

        </div>



        <!-- IMPACT CARDS -->

        <div
            class="grid
                   sm:grid-cols-2
                   lg:grid-cols-4
                   gap-5"
        >


            <div
                class="glossy-card
                       rounded-3xl
                       p-7
                       text-center
                       hover:-translate-y-2
                       transition"
            >

                <div class="text-4xl mb-5">
                    ♻️
                </div>

                <div
                    class="text-3xl
                           font-black
                           gradient-text"
                >

                    Less Waste

                </div>

                <p
                    class="text-sm
                           text-slate-500
                           mt-2"
                >

                    Help reduce avoidable food waste.

                </p>

            </div>



            <div
                class="glossy-card
                       rounded-3xl
                       p-7
                       text-center
                       hover:-translate-y-2
                       transition"
            >

                <div class="text-4xl mb-5">
                    ❤️
                </div>

                <div
                    class="text-3xl
                           font-black
                           gradient-text"
                >

                    More Support

                </div>

                <p
                    class="text-sm
                           text-slate-500
                           mt-2"
                >

                    Strengthen community assistance.

                </p>

            </div>



            <div
                class="glossy-card
                       rounded-3xl
                       p-7
                       text-center
                       hover:-translate-y-2
                       transition"
            >

                <div class="text-4xl mb-5">
                    🤝
                </div>

                <div
                    class="text-3xl
                           font-black
                           gradient-text"
                >

                    Stronger Network

                </div>

                <p
                    class="text-sm
                           text-slate-500
                           mt-2"
                >

                    Connect donors and organizations.

                </p>

            </div>



            <div
                class="glossy-card
                       rounded-3xl
                       p-7
                       text-center
                       hover:-translate-y-2
                       transition"
            >

                <div class="text-4xl mb-5">
                    🌱
                </div>

                <div
                    class="text-3xl
                           font-black
                           gradient-text"
                >

                    Sustainable

                </div>

                <p
                    class="text-sm
                           text-slate-500
                           mt-2"
                >

                    Encourage responsible communities.

                </p>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     WHY F-DESTINY
========================================================= -->

<section
    class="py-24
           px-5
           bg-white/40"
>

    <div
        class="max-w-7xl
               mx-auto
               grid
               lg:grid-cols-2
               gap-16
               items-center"
    >


        <!-- LEFT -->

        <div>

            <span
                class="text-emerald-600
                       text-sm
                       font-bold
                       uppercase
                       tracking-widest"
            >

                Why F-Destiny?

            </span>


            <h2
                class="mt-3
                       text-4xl
                       sm:text-5xl
                       font-black
                       leading-tight"
            >

                Technology with a

                <span class="gradient-text">
                    human purpose.
                </span>

            </h2>


            <p
                class="mt-6
                       text-slate-500
                       leading-8"
            >

                Food redistribution involves more than
                finding available food. It requires
                coordination, communication and timely
                action. F-Destiny brings these elements
                together in one platform.

            </p>


            <div
                class="mt-8
                       space-y-5"
            >

                <div class="flex gap-4">

                    <div
                        class="w-10 h-10
                               shrink-0
                               rounded-xl
                               bg-emerald-100
                               flex items-center
                               justify-center"
                    >

                        ✓

                    </div>


                    <div>

                        <h4
                            class="font-bold
                                   text-lg"
                        >

                            Centralized Platform

                        </h4>

                        <p
                            class="text-sm
                                   text-slate-500
                                   mt-1"
                        >

                            Keep donation-related
                            activities organized in
                            one digital ecosystem.

                        </p>

                    </div>

                </div>



                <div class="flex gap-4">

                    <div
                        class="w-10 h-10
                               shrink-0
                               rounded-xl
                               bg-emerald-100
                               flex items-center
                               justify-center"
                    >

                        ✓

                    </div>


                    <div>

                        <h4
                            class="font-bold
                                   text-lg"
                        >

                            Role-Based Experience

                        </h4>

                        <p
                            class="text-sm
                                   text-slate-500
                                   mt-1"
                        >

                            Donors, NGOs and volunteers
                            can interact according to
                            their responsibilities.

                        </p>

                    </div>

                </div>



                <div class="flex gap-4">

                    <div
                        class="w-10 h-10
                               shrink-0
                               rounded-xl
                               bg-emerald-100
                               flex items-center
                               justify-center"
                    >

                        ✓

                    </div>


                    <div>

                        <h4
                            class="font-bold
                                   text-lg"
                        >

                            Community Driven

                        </h4>

                        <p
                            class="text-sm
                                   text-slate-500
                                   mt-1"
                        >

                            The platform depends on
                            collaboration between people
                            and organizations.

                        </p>

                    </div>

                </div>

            </div>

        </div>



        <!-- RIGHT -->

        <div
            class="relative"
        >

            <div
                class="glossy-card
                       rounded-[2rem]
                       p-7
                       green-glow"
            >

                <div
                    class="flex
                           items-center
                           justify-between
                           pb-5
                           border-b
                           border-slate-100"
                >

                    <div>

                        <p
                            class="text-xs
                                   text-slate-400
                                   uppercase
                                   tracking-wider
                                   font-bold"
                        >

                            F-Destiny Ecosystem

                        </p>

                        <h3
                            class="text-2xl
                                   font-black
                                   mt-1"
                        >

                            Connected Network

                        </h3>

                    </div>


                    <span
                        class="w-3 h-3
                               rounded-full
                               bg-emerald-500
                               shadow-lg
                               shadow-emerald-500/40"
                    ></span>

                </div>



                <div
                    class="py-7
                           space-y-4"
                >

                    <div
                        class="flex
                               items-center
                               justify-between
                               p-4
                               rounded-2xl
                               bg-slate-50"
                    >

                        <div
                            class="flex
                                   items-center
                                   gap-3"
                        >

                            <span class="text-xl">
                                🏪
                            </span>

                            <span
                                class="font-semibold"
                            >

                                Donors

                            </span>

                        </div>


                        <span
                            class="text-emerald-600
                                   font-bold"
                        >

                            Connected

                        </span>

                    </div>



                    <div
                        class="flex
                               items-center
                               justify-between
                               p-4
                               rounded-2xl
                               bg-slate-50"
                    >

                        <div
                            class="flex
                                   items-center
                                   gap-3"
                        >

                            <span class="text-xl">
                                🏢
                            </span>

                            <span
                                class="font-semibold"
                            >

                                NGOs

                            </span>

                        </div>


                        <span
                            class="text-emerald-600
                                   font-bold"
                        >

                            Connected

                        </span>

                    </div>



                    <div
                        class="flex
                               items-center
                               justify-between
                               p-4
                               rounded-2xl
                               bg-slate-50"
                    >

                        <div
                            class="flex
                                   items-center
                                   gap-3"
                        >

                            <span class="text-xl">
                                🚚
                            </span>

                            <span
                                class="font-semibold"
                            >

                                Volunteers

                            </span>

                        </div>


                        <span
                            class="text-emerald-600
                                   font-bold"
                        >

                            Connected

                        </span>

                    </div>

                </div>



                <div
                    class="rounded-2xl
                           p-5
                           bg-gradient-to-r
                           from-emerald-600
                           to-green-600
                           text-white"
                >

                    <div
                        class="flex
                               items-center
                               justify-between"
                    >

                        <div>

                            <p
                                class="text-xs
                                       text-emerald-100"
                            >

                                Ecosystem

                            </p>

                            <p
                                class="font-bold
                                       mt-1"
                            >

                                People working together

                            </p>

                        </div>


                        <span class="text-2xl">
                            🌍
                        </span>

                    </div>

                </div>

            </div>

        </div>


    </div>

</section>



<!-- =========================================================
     FINAL CTA
========================================================= -->

<section
    class="px-5
           py-20"
>

    <div
        class="max-w-6xl
               mx-auto
               relative
               overflow-hidden
               rounded-[2.5rem]
               px-7
               py-16
               sm:px-14
               text-center
               bg-gradient-to-br
               from-slate-900
               via-slate-800
               to-emerald-950
               shadow-2xl"
    >

        <!-- Decorative circles -->

        <div
            class="absolute
                   -top-32
                   -left-20
                   w-72 h-72
                   rounded-full
                   bg-emerald-500/20
                   blur-3xl"
        ></div>


        <div
            class="absolute
                   -bottom-32
                   -right-20
                   w-72 h-72
                   rounded-full
                   bg-green-400/10
                   blur-3xl"
        ></div>


        <div class="relative z-10">


            <div
                class="inline-flex
                       px-4 py-2
                       rounded-full
                       bg-white/10
                       border
                       border-white/10
                       text-emerald-300
                       text-xs
                       font-bold
                       uppercase
                       tracking-widest"
            >

                Be Part of the Change

            </div>


            <h2
                class="mt-6
                       text-4xl
                       sm:text-5xl
                       font-black
                       text-white
                       tracking-tight"
            >

                Together, we can make

                <span class="text-emerald-400">
                    surplus matter.
                </span>

            </h2>


            <p
                class="max-w-2xl
                       mx-auto
                       mt-5
                       text-slate-300
                       leading-7"
            >

                Whether you are a donor, NGO or volunteer,
                your participation can help strengthen
                the F-Destiny food redistribution network.

            </p>


            <div
                class="mt-9
                       flex
                       flex-wrap
                       justify-center
                       gap-4"
            >

                <a
                    href="index.php"
                    class="px-7 py-3.5
                           rounded-2xl
                           bg-emerald-500
                           text-white
                           font-bold
                           shadow-xl
                           shadow-emerald-500/20
                           hover:bg-emerald-400
                           hover:-translate-y-1
                           transition"
                >

                    Explore F-Destiny →

                </a>


                <a
                    href="#about"
                    class="px-7 py-3.5
                           rounded-2xl
                           bg-white/10
                           border
                           border-white/15
                           text-white
                           font-bold
                           hover:bg-white/15
                           transition"
                >

                    Back to Top ↑

                </a>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer
    class="border-t
           border-slate-200/70
           bg-white/60"
>

    <div
        class="max-w-7xl
               mx-auto
               px-5
               py-10
               flex
               flex-col
               md:flex-row
               items-center
               justify-between
               gap-5"
    >

        <div
            class="flex
                   items-center
                   gap-3"
        >

            <div
                class="w-10 h-10
                       rounded-xl
                       bg-emerald-600
                       flex items-center
                       justify-center
                       text-white"
            >

                🌱

            </div>


            <div>

                <p
                    class="font-extrabold"
                >

                    F-Destiny

                </p>

                <p
                    class="text-xs
                           text-slate-400"
                >

                    Food • People • Future

                </p>

            </div>

        </div>


        <p
            class="text-sm
                   text-slate-400
                   text-center"
        >

            © <?php echo date("Y"); ?>
            F-Destiny.
            Building a more connected and sustainable future.

        </p>


        <a
            href="index.php"
            class="text-sm
                   font-bold
                   text-emerald-600
                   hover:text-emerald-700"
        >

            Back Home →

        </a>

    </div>

</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

function toggleMenu() {

    const menu =
        document.getElementById("mobileMenu");

    if (menu.classList.contains("max-h-0")) {

        menu.classList.remove(
            "max-h-0",
            "opacity-0"
        );

        menu.classList.add(
            "max-h-96",
            "opacity-100"
        );

    } else {

        menu.classList.remove(
            "max-h-96",
            "opacity-100"
        );

        menu.classList.add(
            "max-h-0",
            "opacity-0"
        );

    }

}

</script>


</body>

</html>