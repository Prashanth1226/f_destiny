<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$login_error = null;
$signup_error = null;
$signup_success = null;

/* ================= LOGIN PROCESSING ================= */
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $selectedRole = strtolower(trim($_POST['role']));

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows != 1) {
        $login_error = "User not found.";
    } else {
        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password'])) {
            $login_error = "Invalid password.";
        } else {
            $user_id = $user['id'];
            $roles = [];

            $roleStmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id = ?");
            $roleStmt->bind_param("i", $user_id);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();

            while ($row = $roleResult->fetch_assoc()) {
                $roles[] = strtolower(trim($row['role']));
            }

            if (!in_array($selectedRole, $roles)) {
                $login_error = "You do not have access to the selected role.";
            } else {
                $_SESSION['user'] = $email;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['roles'] = $roles;
                $_SESSION['role'] = $selectedRole;

                $redirects = [
                    "ngo" => "/f_destiny/dashboard/ngo/ngo_index.php",
                    "donor" => "/f_destiny/dashboard/donor/index.php",
                    "volunteer" => "/f_destiny/dashboard/volunteer/volunteer_index.php"
                ];

                header("Location: " . $redirects[$selectedRole]);
                exit();
            }
        }
    }
}

/* ================= SIGNUP PROCESSING ================= */
if (isset($_POST['signup'])) {
    $name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $defaultRoles = ['donor', 'ngo', 'volunteer'];

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        $signup_error = "Account already exists!";
    } elseif ($password !== $confirm_password) {
        $signup_error = "Passwords do not match!";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insertUser = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $insertUser->bind_param("ssss", $name, $email, $phone, $hashedPassword);

        if ($insertUser->execute()) {
            $user_id = $insertUser->insert_id;
            $roleInsert = $conn->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");

            foreach ($defaultRoles as $role) {
                $cleanRole = strtolower(trim($role));
                $roleInsert->bind_param("is", $user_id, $cleanRole);
                $roleInsert->execute();
            }
            $signup_success = "Signup successful with all roles!";
        } else {
            $signup_error = "Signup failed!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | End Food Waste & Fight Hunger</title>

    <meta name="description" content="F-Destiny connects food donors, volunteers, and NGOs directly to optimize resource allocation and build a hunger-free community.">
    <meta name="keywords" content="food donation, hunger relief, food waste, ngo, volunteer, tracking">

    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost/f_destiny/index.php">
    <meta property="og:title" content="F-Destiny — Connecting Donors, NGOs & Volunteers">
    <meta property="og:description" content="Eliminate surplus food waste in real-time. Join our dynamic platform as a donor, transport volunteer, or partner non-profit.">
    <meta property="og:image" content="http://localhost/f_destiny/assets/images/hero1.jpg">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="F-Destiny — End Food Waste & Fight Hunger">
    <meta name="twitter:description" content="Eliminate surplus food waste in real-time. Join our dynamic platform as a donor, transport volunteer, or partner non-profit.">
    <meta name="twitter:image" content="http://localhost/f_destiny/assets/images/hero1.jpg">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* DYNAMIC FLUID ANIMATED BACKDROP METRIC */
        .animated-bg {
            background: linear-gradient(-45deg, #f8fafc, #f0fdf4, #eff6ff, #f5f3ff);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .glass-modal {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(226, 232, 240, 0.9);
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 0.98);
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
            outline: none;
        }

        .slide {
            transition: opacity 1.2s ease-in-out;
            opacity: 0;
            position: absolute;
            inset: 0;
        }
        .slide.active { opacity: 1; z-index: 10; }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased">

<?php if ($login_error || $signup_error || $signup_success): ?>
    <?php 
        $msg = $login_error ?? $signup_error ?? $signup_success;
        $isSuccess = !empty($signup_success);
    ?>
    <div id="toastNotification" class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-6 py-3.5 rounded-xl shadow-xl transition-all duration-300 transform translate-y-0 <?= $isSuccess ? 'bg-emerald-600' : 'bg-rose-600' ?> text-white font-semibold text-sm">
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('toastNotification');
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, -20px)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    </script>
<?php endif; ?>

<header class="glass-header sticky top-0 z-50 border-b border-slate-200/60 shadow-sm px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2 text-2xl font-black text-emerald-600 tracking-tight">
        <span>🍱</span> F-Destiny
    </div>
    
    <div class="hidden md:block text-center max-w-xl">
        <h1 class="text-sm font-bold text-slate-800">Welcome to F-Destiny</h1>
        <p class="text-xs text-slate-500 mt-0.5">Connecting food donors with NGOs and volunteers to eliminate food waste and fight hunger</p>
    </div>

    <div class="flex gap-2">
        <button onclick="openModal('loginModal')" class="px-3.5 py-2 text-sm font-bold text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 backdrop-blur-md transition rounded-lg shadow-sm">Login</button>
        <button onclick="openModal('signupModal')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2 rounded-xl text-sm font-semibold shadow-md shadow-emerald-600/10 transition">Signup</button>
    </div>
</header>

<section class="relative h-[550px] bg-slate-900 overflow-hidden w-4/5 mx-auto rounded-xl">
    <div class="slide active">
        <img src="assets/images/hero1.jpg" class="w-full h-full object-cover opacity-70" alt="Stop Waste">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-50 via-transparent to-transparent/20 z-20"></div>
        <div class="absolute inset-0 flex items-start justify-center text-center pt-20 p-6 z-30">
            <div class="max-w-3xl">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight drop-shadow-md">Stop Food Waste</h2>
                <p class="text-base md:text-lg text-slate-100 font-medium leading-relaxed drop-shadow-sm">Millions of people sleep hungry while tons of food are wasted every day. F-Destiny bridges this gap by connecting donors, NGOs, and volunteers seamlessly.</p>
            </div>
        </div>
    </div>
    
    <div class="slide">
        <img src="assets/images/hero2.jpg" class="w-full h-full object-cover opacity-70" alt="Connect">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-50 via-transparent to-transparent/20 z-20"></div>
        <div class="absolute inset-0 flex items-start justify-center text-center pt-20 p-6 z-30">
            <div class="max-w-3xl">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight drop-shadow-md">Connect Communities</h2>
                <p class="text-base md:text-lg text-slate-100 font-medium leading-relaxed drop-shadow-sm">Donors, NGOs, and volunteers work together within a unified grid system to ensure that surplus food reaches the people who need it most.</p>
            </div>
        </div>
    </div>

    <div class="slide">
        <img src="assets/images/hero3.jpg" class="w-full h-full object-cover opacity-70" alt="Tracking">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-50 via-transparent to-transparent/20 z-20"></div>
        <div class="absolute inset-0 flex items-start justify-center text-center pt-20 p-6 z-30">
            <div class="max-w-3xl">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight drop-shadow-md">Real-Time Food Tracking</h2>
                <p class="text-base md:text-lg text-slate-100 font-medium leading-relaxed drop-shadow-sm">Monitor real-time item donation logs, dispatch updates, logistical milestones, and delivery tracking information using one clean web portal.</p>
            </div>
        </div>
    </div>

    <div class="slide">
        <img src="assets/images/hero4.jpg" class="w-full h-full object-cover opacity-70" alt="Future">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-50 via-transparent to-transparent/20 z-20"></div>
        <div class="absolute inset-0 flex items-start justify-center text-center pt-20 p-6 z-30">
            <div class="max-w-3xl">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight drop-shadow-md">Building a Hunger-Free Future</h2>
                <p class="text-base md:text-lg text-slate-100 font-medium leading-relaxed drop-shadow-sm">Every surplus meal safely shared helps convert a global problem into an immediate community answer. Together we can create a sustainable society.</p>
            </div>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-6 relative z-40 -mt-48 pb-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="glass-card bg-white p-6 rounded-xl shadow-md border border-slate-200/80 min-h-[180px] flex flex-col justify-between hover:translate-y-[-4px] transition duration-300">
            <h3 class="font-bold text-slate-900 text-base mb-2">Eliminate Food Waste</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Save perfectly good surplus meals from local operations before expiration timelines hit.</p>
            <a href="#" class="text-xs font-semibold text-emerald-600 mt-4 hover:underline inline-block">Learn more</a>
        </div>
        
        <div class="glass-card bg-white p-6 rounded-xl shadow-md border border-slate-200/80 min-h-[180px] flex flex-col justify-between hover:translate-y-[-4px] transition duration-300">
            <h3 class="font-bold text-slate-900 text-base mb-2">Connect Donors & NGOs</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Seamless routing system linking business supply lines directly into regional kitchens.</p>
            <a href="#" class="text-xs font-semibold text-emerald-600 mt-4 hover:underline inline-block">See partners</a>
        </div>

        <div class="glass-card bg-white p-6 rounded-xl shadow-md border border-slate-200/80 min-h-[180px] flex flex-col justify-between hover:translate-y-[-4px] transition duration-300">
            <h3 class="font-bold text-slate-900 text-base mb-2">Support Volunteers</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Claim pickup requests instantly inside your local neighborhood coordinates.</p>
            <a href="#" class="text-xs font-semibold text-emerald-600 mt-4 hover:underline inline-block">Join team</a>
        </div>

        <div class="glass-card bg-white p-6 rounded-xl shadow-md border border-slate-200/80 min-h-[180px] flex flex-col justify-between hover:translate-y-[-4px] transition duration-300">
            <h3 class="font-bold text-slate-900 text-base mb-2">Save Human Lives</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Divert critical nourishment resources straight to centers hosting vulnerable groups.</p>
            <a href="#" class="text-xs font-semibold text-emerald-600 mt-4 hover:underline inline-block">View impact</a>
        </div>

        <div class="glass-card bg-white p-6 rounded-xl shadow-md border border-slate-200/80 min-h-[180px] flex flex-col justify-between hover:translate-y-[-4px] transition duration-300">
            <h3 class="font-bold text-slate-900 text-base mb-2">Hunger-Free Society</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Tracking structural changes built safely across high-density city networks.</p>
            <a href="#" class="text-xs font-semibold text-emerald-600 mt-4 hover:underline inline-block">Our metrics</a>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-6 py-16">
    <h2 class="text-2xl md:text-3xl font-extrabold text-slate-800 text-center mb-10">Why F-Destiny?</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 text-center">
        <div class="glass-card p-5 rounded-2xl font-bold text-slate-700 flex items-center justify-center">Eliminate Food Waste</div>
        <div class="glass-card p-5 rounded-2xl font-bold text-slate-700 flex items-center justify-center">Connect Donors & NGOs</div>
        <div class="glass-card p-5 rounded-2xl font-bold text-slate-700 flex items-center justify-center">Support Volunteers</div>
        <div class="glass-card p-5 rounded-2xl font-bold text-slate-700 flex items-center justify-center">Save Human Lives</div>
        <div class="glass-card p-5 rounded-2xl font-bold text-slate-700 flex items-center justify-center">Build Hunger-Free Society</div>
    </div>
</section>

<section class="max-w-6xl mx-auto px-6 space-y-10 pb-16">
    <div class="glass-card p-6 md:p-8 rounded-3xl flex flex-col md:flex-row items-center gap-6 md:gap-8">
        <img src="assets/images/donor.jpg" class="w-32 h-32 md:w-40 md:h-40 rounded-2xl object-cover shadow-md border-2 border-white bg-slate-100 shrink-0" alt="Food Donors">
        <div>
            <h3 class="text-xl md:text-2xl font-bold text-emerald-600 mb-2">Food Donors</h3>
            <p class="text-slate-600 leading-relaxed text-sm md:text-base">Restaurants, hotels, event organizers, and individuals can donate surplus food through F-Destiny. Instead of letting food go to waste, donors can quickly create donation requests and help provide meals to people in need.</p>
        </div>
    </div>

    <div class="glass-card p-6 md:p-8 rounded-3xl flex flex-col md:flex-row-reverse items-center gap-6 md:gap-8">
        <img src="assets/images/volunteer.jpg" class="w-32 h-32 md:w-40 md:h-40 rounded-2xl object-cover shadow-md border-2 border-white bg-slate-100 shrink-0" alt="Volunteers">
        <div class="md:text-right">
            <h3 class="text-xl md:text-2xl font-bold text-blue-600 mb-2">Volunteers</h3>
            <p class="text-slate-600 leading-relaxed text-sm md:text-base">Volunteers act as the bridge between donors and NGOs. They collect food from donation points, verify its condition, and ensure safe transportation to distribution centers and needy communities.</p>
        </div>
    </div>

    <div class="glass-card p-6 md:p-8 rounded-3xl flex flex-col md:flex-row items-center gap-6 md:gap-8">
        <img src="assets/images/donation.jpg" class="w-32 h-32 md:w-40 md:h-40 rounded-2xl object-cover shadow-md border-2 border-white bg-slate-100 shrink-0" alt="Smart System">
        <div>
            <h3 class="text-xl md:text-2xl font-bold text-purple-600 mb-2">Smart Food Donation System</h3>
            <p class="text-slate-600 leading-relaxed text-sm md:text-base">The platform provides real-time donation tracking, food details, pickup scheduling, and distribution monitoring. Every donation can be tracked cleanly all the way from the initial donor down to the final beneficiary.</p>
        </div>
    </div>

    <div class="glass-card p-6 md:p-8 rounded-3xl flex flex-col md:flex-row-reverse items-center gap-6 md:gap-8">
        <img src="assets/images/ngo.jpg" class="w-32 h-32 md:w-40 md:h-40 rounded-2xl object-cover shadow-md border-2 border-white bg-slate-100 shrink-0" alt="NGO Partners">
        <div class="md:text-right">
            <h3 class="text-xl md:text-2xl font-bold text-rose-600 mb-2">NGOs & Distribution Centers</h3>
            <p class="text-slate-600 leading-relaxed text-sm md:text-base">NGOs receive donated food and distribute it fairly among homeless people, orphanages, shelters, and underprivileged communities. Their role ensures that donated food reaches those who need it most.</p>
        </div>
    </div>
</section>

<section class="bg-emerald-950 text-white p-8 md:p-12 text-center relative z-20 shadow-inner">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-2xl md:text-3xl font-extrabold mb-8">How F-Destiny Works</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-slate-900/40 p-5 rounded-2xl border border-emerald-800/60 backdrop-blur-sm">
                <h3 class="font-bold text-emerald-400 text-lg mb-1">1. Donate</h3>
                <p class="text-sm text-emerald-100/70">Donors submit live food descriptions details through the system portal.</p>
            </div>
            <div class="bg-slate-900/40 p-5 rounded-2xl border border-emerald-800/60 backdrop-blur-sm">
                <h3 class="font-bold text-emerald-400 text-lg mb-1">2. Verify</h3>
                <p class="text-sm text-emerald-100/70">Volunteers check fresh quality indicators and prepare storage packages.</p>
            </div>
            <div class="bg-slate-900/40 p-5 rounded-2xl border border-emerald-800/60 backdrop-blur-sm">
                <h3 class="font-bold text-emerald-400 text-lg mb-1">3. Deliver</h3>
                <p class="text-sm text-emerald-100/70">Logistics pipelines securely forward packages along targeted paths.</p>
            </div>
            <div class="bg-slate-900/40 p-5 rounded-2xl border border-emerald-800/60 backdrop-blur-sm">
                <h3 class="font-bold text-emerald-400 text-lg mb-1">4. Distribute</h3>
                <p class="text-sm text-emerald-100/70">NGO partners assign inventory portions immediately to local shelters.</p>
            </div>
        </div>
    </div>
</section>

<section id="about" class="max-w-3xl mx-auto px-6 py-16 text-center">
    <h2 class="text-2xl md:text-3xl font-black text-emerald-600 mb-4">About F-Destiny</h2>
    <p class="text-slate-600 leading-relaxed font-medium">
        F-Destiny is a food donation and distribution platform designed to reduce food waste and fight hunger by connecting <strong class="text-slate-800 font-semibold">donors, NGOs, and volunteers</strong> in real-time.
    </p>
    <p class="text-slate-600 leading-relaxed font-medium mt-4">
        Every day, tons of food is wasted while many go hungry. Our mission is to bridge this gap by enabling instant donation tracking, smart delivery routing, and verified NGO distribution networks. Surplus food becomes a life-saving resource instead of waste.
    </p>
</section>

<section class="py-12 px-6 text-center">
    <h2 class="text-xl font-bold mb-6 text-slate-700">Quick Actions</h2>
    <div class="flex flex-wrap justify-center gap-4">
        <a href="#about" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold text-sm transition shadow-sm">About F-Destiny</a>
        <a href="#contact" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-xl font-semibold text-sm transition shadow-sm">Contact Us</a>
    </div>
</section>

<section id="contact" class="max-w-6xl mx-auto px-6 py-12 grid md:grid-cols-2 gap-8">
    <div class="glass-card p-6 rounded-2xl">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Contact Info</h3>
        <ul class="space-y-3 text-slate-600 font-medium text-sm">
            <li class="flex items-center gap-2"><span>📧</span> Email: nunnakapulaprashanth1226@gmail.com</li>
            <li class="flex items-center gap-2"><span>📞</span> Phone: +91 xxxxx xxxxx</li>
            <li class="flex items-center gap-2"><span>📍</span> Location: Hyderabad, Telangana, India</li>
        </ul>
    </div>
    <div class="glass-card p-6 rounded-2xl flex flex-col justify-center text-center">
        <h3 class="text-lg font-bold text-purple-600 mb-3">Connect With Us</h3>
        <div class="flex justify-center flex-wrap gap-6 text-sm font-bold text-slate-500">
            <a href="#" class="hover:text-blue-600 transition">Facebook</a>
            <a href="#" class="hover:text-pink-600 transition">Instagram</a>
            <a href="#" class="hover:text-blue-500 transition">LinkedIn</a>
            <a href="#" class="hover:text-black transition">GitHub</a>
        </div>
    </div>
</section>

<section class="bg-emerald-600 text-white py-12 px-6 shadow-lg">
    <div class="max-w-6xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        <div><h2 class="text-3xl md:text-4xl font-black">1000+</h2><p class="text-sm text-emerald-100 mt-1">Meals Donated</p></div>
        <div><h2 class="text-3xl md:text-4xl font-black">100+</h2><p class="text-sm text-emerald-100 mt-1">Active Donors</p></div>
        <div><h2 class="text-3xl md:text-4xl font-black">50+</h2><p class="text-sm text-emerald-100 mt-1">Volunteers</p></div>
        <div><h2 class="text-3xl md:text-4xl font-black">25+</h2><p class="text-sm text-emerald-100 mt-1">Partner NGOs</p></div>
    </div>
</section>

<footer class="bg-slate-900 text-slate-400 text-sm border-t border-slate-800 relative z-20">
    <div class="max-w-7xl mx-auto px-6 py-12 grid sm:grid-cols-2 md:grid-cols-4 gap-10">
        <div>
            <h3 class="text-xl font-bold text-emerald-400 mb-3">🍱 F-Destiny</h3>
            <p class="text-xs text-slate-400 leading-relaxed">F-Destiny is a smart food donation and distribution platform designed to reduce food waste and fight hunger by connecting donors, NGOs, and volunteers through a unified digital ecosystem.</p>
        </div>
        <div>
            <h4 class="font-bold text-slate-200 mb-3 text-xs uppercase tracking-wider">Our Mission</h4>
            <ul class="space-y-1.5 text-xs text-slate-400">
                <li>• Reduce food waste</li>
                <li>• Feed needy communities</li>
                <li>• Empower local NGOs</li>
                <li>• Support civic volunteers</li>
                <li>• Build hunger-free networks</li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-slate-200 mb-3 text-xs uppercase tracking-wider">Platform Features</h4>
            <ul class="space-y-1.5 text-xs text-slate-400">
                <li>• Food Donation Tracking</li>
                <li>• Live Location Monitoring</li>
                <li>• NGO Order Coordination</li>
                <li>• Volunteer Assignment</li>
                <li>• Real-time Data Analytics</li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-slate-200 mb-3 text-xs uppercase tracking-wider">Support Desks</h4>
            <ul class="space-y-1.5 text-xs text-slate-400">
                <li>📧 support@fdestiny.org</li>
                <li>📞 +91 XXXXX XXXXX</li>
                <li>📍 Hyderabad, Telangana, India</li>
                <li>🌐 www.fdestiny.org</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-800/80 max-w-7xl mx-auto px-6 py-8 text-center space-y-2">
        <h3 class="text-lg font-bold text-emerald-400">Together We Can End Hunger</h3>
        <p class="text-xs text-slate-400 max-w-3xl mx-auto leading-relaxed">Every meal donated through F-Destiny represents hope, compassion, and a commitment to social responsibility. By bringing together food donors, volunteers, and NGOs, we ensure that surplus food reaches people who need it instead of being wasted.</p>
        <div class="pt-4 text-xs text-slate-500">
            <p>© 2026 F-Destiny. All Rights Reserved.</p>
            <p class="text-emerald-500 font-semibold mt-1">Built with ❤️ to Reduce Food Waste and Fight Hunger</p>
        </div>
    </div>
</footer>

<div id="loginModal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-md flex items-center justify-center z-[80] p-4">
    <div class="glass-modal w-full max-w-md rounded-2xl p-6 shadow-2xl relative">
        <h3 class="text-xl font-bold text-slate-900 mb-1">Log In</h3>
        <p class="text-xs text-slate-500 mb-4">Fill the details to login</p>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Email Address</label>
                <input type="email" name="email" required autocomplete="email" class="glass-input w-full px-3 py-2.5 rounded-xl text-sm font-medium">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Secure Password</label>
                <div class="relative">
                    <input type="password" id="loginPassword" name="password" required class="glass-input w-full px-3 py-2.5 rounded-xl text-sm font-medium pr-10">
                    <button type="button" onclick="togglePasswordVisibility('loginPassword')" class="absolute right-3 top-3 text-slate-400 text-xs">👁️</button>
                </div>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Select Role</label>
                <select name="role" required class="glass-input w-full px-3 py-2.5 rounded-xl text-sm font-medium bg-white">
                    <option value="">Select Role</option>
                    <option value="donor">Donor</option>
                    <option value="ngo">NGO</option>
                    <option value="volunteer">Volunteer</option>
                </select>
            </div>
            <button type="submit" name="login" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl shadow-md transition mt-2 text-sm">Login</button>
        </form>
        <button onclick="closeModal('loginModal')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-sm">✕</button>
    </div>
</div>

<div id="signupModal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-md flex items-center justify-center z-[80] p-4">
    <div class="glass-modal w-full max-w-md rounded-2xl p-6 shadow-2xl relative">
        <h3 class="text-xl font-bold text-slate-900 mb-1">Signup</h3>
        <p class="text-xs text-slate-500 mb-4">Gives immediate unified access across all profile segments.</p>
        
        <form method="POST" class="space-y-3">
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Full Identity Name</label>
                <input type="text" name="fullname" required class="glass-input w-full px-3 py-2 rounded-xl text-sm font-medium">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Primary Email</label>
                <input type="email" name="email" required autocomplete="email" class="glass-input w-full px-3 py-2 rounded-xl text-sm font-medium">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Mobile Contact No</label>
                <input type="tel" name="phone" required autocomplete="tel" class="glass-input w-full px-3 py-2 rounded-xl text-sm font-medium">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Password</label>
                <input type="password" name="password" required class="glass-input w-full px-3 py-2 rounded-xl text-sm font-medium">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" required class="glass-input w-full px-3 py-2 rounded-xl text-sm font-medium">
            </div>
            <button type="submit" name="signup" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-xl shadow-md transition mt-4 text-sm">Create Account</button>
        </form>
        <button onclick="closeModal('signupModal')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-sm">✕</button>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
}

function togglePasswordVisibility(fieldId) {
    const target = document.getElementById(fieldId);
    target.type = (target.type === "password") ? "text" : "password";
}

/* HERO SLIDER CONTROLLER */
const slideElements = document.querySelectorAll('.slide');
let activeSlideIndex = 0;

setInterval(() => {
    slideElements[activeSlideIndex].classList.remove('active');
    activeSlideIndex = (activeSlideIndex + 1) % slideElements.length;
    slideElements[activeSlideIndex].classList.add('active');
}, 4000);
</script>
</body>
</html>