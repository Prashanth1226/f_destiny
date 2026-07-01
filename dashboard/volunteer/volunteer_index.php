<?php
/**
 * F-Destiny - Volunteer Platform Core Dashboard Base
 * File: volunteer_index.php
 * Expected Location: /views/volunteer/volunteer_index.php
 */

session_start();

require_once '../../middleware/auth.php';
// Match unified global role checking standard rules
$_SESSION['role'] = $_SESSION['role'] ?? 'volunteer'; 
checkRole('volunteer');

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "f_destiny");
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$email = $_SESSION['user'];

/* =====================================================
   VOLUNTEER ACCOUNT LOOKUP
===================================================== */
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$volunteer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$volunteer) {
    die("Volunteer account breakdown.");
}
$volunteer_id = $volunteer['id'];
$volunteer_name = $volunteer['name'] ?? 'Volunteer';

/* =====================================================
   SECURE ACTION INTERCEPTOR (CLAIM / COMPLETE ROUTER)
===================================================== */
if (isset($_GET['action']) && isset($_GET['id'])) {
    $task_id = intval($_GET['id']);
    
    if ($_GET['action'] === 'claim') {
        $up = $conn->prepare("UPDATE donations SET volunteer_id = ?, status = 'assigned' WHERE id = ? AND status = 'accepted' AND volunteer_id IS NULL");
        $up->bind_param("ii", $volunteer_id, $task_id);
        $up->execute();
        $up->close();
    } elseif ($_GET['action'] === 'complete') {
        $up = $conn->prepare("UPDATE donations SET status = 'delivered' WHERE id = ? AND volunteer_id = ?");
        $up->bind_param("ii", $task_id, $volunteer_id);
        $up->execute();
        $up->close();
    }
    
    // Smooth deterministic routing loop protection header redirect
    header("Location: volunteer_index.php");
    exit();
}

/* =====================================================
   METRIC COMPILATION MATRIX
===================================================== */
$stmt1 = $conn->prepare("SELECT COUNT(*) AS total FROM donations WHERE status = 'accepted' AND volunteer_id IS NULL");
$stmt1->execute();
$available_count = $stmt1->get_result()->fetch_assoc()['total'];
$stmt1->close();

$stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM donations WHERE volunteer_id = ? AND status != 'delivered'");
$stmt2->bind_param("i", $volunteer_id);
$stmt2->execute();
$assigned_count = $stmt2->get_result()->fetch_assoc()['total'];
$stmt2->close();

$stmt3 = $conn->prepare("SELECT COUNT(*) AS total FROM donations WHERE volunteer_id = ? AND status = 'delivered'");
$stmt3->bind_param("i", $volunteer_id);
$stmt3->execute();
$completed_count = $stmt3->get_result()->fetch_assoc()['total'];
$stmt3->close();

/* =====================================================
   GAMIFICATION LEVEL MATRIX PIPELINE
===================================================== */
$points = $completed_count * 20;
if ($points >= 500) {
    $level = "Gold Volunteer";
    $badgeColor = "from-amber-400 to-yellow-600 text-amber-950 border-amber-400/30 shadow-amber-500/10";
    $levelIcon = "🥇";
} elseif ($points >= 200) {
    $level = "Silver Volunteer";
    $badgeColor = "from-slate-300 to-slate-500 text-slate-950 border-slate-300/30 shadow-slate-400/10";
    $levelIcon = "🥈";
} else {
    $level = "Bronze Volunteer";
    $badgeColor = "from-orange-400 to-amber-600 text-orange-950 border-orange-400/30 shadow-orange-500/10";
    $levelIcon = "🥉";
}

$nextLevel = ($points < 200) ? 200 : (($points < 500) ? 500 : max($points, 1));
$progress = min(($points / $nextLevel) * 100, 100);

$completion_rate = 0;
if (($assigned_count + $completed_count) > 0) {
    $completion_rate = round(($completed_count / ($assigned_count + $completed_count)) * 100);
}

// Get recent activities
$recentStmt = $conn->prepare("
    SELECT 
        d.*,
        donor.name AS donor_name,
        ngo.name AS ngo_name
    FROM donations d
    LEFT JOIN users donor ON d.donor_id = donor.id
    LEFT JOIN users ngo ON d.ngo_id = ngo.id
    WHERE d.volunteer_id = ? OR (d.status = 'accepted' AND d.volunteer_id IS NULL)
    ORDER BY d.created_at DESC
    LIMIT 5
");
$recentStmt->bind_param("i", $volunteer_id);
$recentStmt->execute();
$recentActivities = $recentStmt->get_result();
$recentStmt->close();

// Get notification count
$notifStmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$notifStmt->bind_param("i", $volunteer_id);
$notifStmt->execute();
$unreadCount = $notifStmt->get_result()->fetch_assoc()['total'] ?? 0;
$notifStmt->close();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Volunteer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            background: #f0f4f8;
            position: relative;
            overflow-x: hidden;
        }

        /* Premium Background Blobs */
        .bg-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            z-index: 0;
            pointer-events: none;
        }
        .bg-blob-1 {
            width: 600px;
            height: 600px;
            top: -15%;
            left: -10%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, transparent 70%);
            animation: floatBlob 25s infinite alternate ease-in-out;
        }
        .bg-blob-2 {
            width: 700px;
            height: 700px;
            bottom: -15%;
            right: -10%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
            animation: floatBlob 30s infinite alternate-reverse ease-in-out;
        }
        .bg-blob-3 {
            width: 400px;
            height: 400px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(16, 185, 129, 0.05) 0%, transparent 70%);
            animation: floatBlob 35s infinite alternate ease-in-out;
        }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 40px) scale(1.1); }
            100% { transform: translate(-40px, 60px) scale(0.9); }
        }

        /* Glassmorphism Components */
        .glass-header {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(24px) saturate(200%);
            -webkit-backdrop-filter: blur(24px) saturate(200%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.6);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.08);
        }

        .glass-sidebar {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-right: 1px solid rgba(226, 232, 240, 0.5);
        }

        /* Stat Cards */
        .stat-card {
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, currentColor, transparent);
            opacity: 0.2;
        }

        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            line-height: 1;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-number {
            transform: scale(1.05);
        }

        /* Sidebar Links */
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            transition: all 0.2s ease;
            text-decoration: none;
            position: relative;
        }

        .sidebar-link:hover {
            background: rgba(59, 130, 246, 0.06);
            color: #2563eb;
        }

        .sidebar-link.active {
            background: #2563eb;
            color: white;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
        }

        .sidebar-link .link-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar-link .link-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 9px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 12px;
            animation: badgePulse 2s infinite;
        }

        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Activity Feed */
        .activity-item {
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .activity-item:hover {
            background: rgba(255, 255, 255, 0.6);
            transform: translateX(4px);
        }

        .activity-item.pending { border-left-color: #f59e0b; }
        .activity-item.accepted { border-left-color: #3b82f6; }
        .activity-item.assigned { border-left-color: #8b5cf6; }
        .activity-item.delivered { border-left-color: #10b981; }

        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 99px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar .fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #3b82f6, #6366f1, #10b981);
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .progress-bar .fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Hero Section */
        .hero-gradient {
            background: linear-gradient(135deg, #1e40af, #4f46e5, #7c3aed);
            position: relative;
            overflow: hidden;
        }

        .hero-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            animation: floatBlob 20s infinite alternate ease-in-out;
        }

        .hero-gradient::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
            animation: floatBlob 25s infinite alternate-reverse ease-in-out;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.02);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(37, 99, 235, 0.2);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(37, 99, 235, 0.3);
        }

        /* Mobile Bottom Nav */
        .mobile-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(226, 232, 240, 0.6);
        }

        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
            color: #94a3b8;
            transition: all 0.2s ease;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .mobile-nav-item:hover {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.05);
        }

        .mobile-nav-item.active {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.08);
        }

        .mobile-nav-item .icon {
            font-size: 20px;
        }

        .mobile-nav-item .badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #ef4444;
            color: white;
            font-size: 7px;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 8px;
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.6s ease forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-delay-1 { animation-delay: 0.1s; }
        .fade-in-delay-2 { animation-delay: 0.2s; }
        .fade-in-delay-3 { animation-delay: 0.3s; }
        .fade-in-delay-4 { animation-delay: 0.4s; }
        .fade-in-delay-5 { animation-delay: 0.5s; }
    </style>
</head>
<body>

<!-- Background Blobs -->
<div class="bg-blob bg-blob-1"></div>
<div class="bg-blob bg-blob-2"></div>
<div class="bg-blob bg-blob-3"></div>

<!-- Header -->
<header class="glass-header sticky top-0 z-50 px-4 md:px-6 py-3 md:py-4 flex justify-between items-center">
    <div class="flex items-center gap-2 md:gap-3">
        <span class="text-2xl md:text-3xl">🚚</span>
        <span class="text-lg md:text-xl font-black text-blue-600 tracking-tight">F-Destiny</span>
        <span class="hidden md:inline-block text-[10px] font-bold text-slate-400 bg-slate-100/80 px-2.5 py-0.5 rounded-full border border-slate-200/50 uppercase tracking-wider">
            Volunteer
        </span>
    </div>

    <div class="flex items-center gap-2 md:gap-4">
        <!-- Online Status -->
        <div class="hidden md:flex items-center gap-2 text-xs font-medium text-slate-500 bg-white/60 px-3 py-1.5 rounded-full border border-slate-200/50">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse ring-2 ring-emerald-500/20"></span>
            <span>Online</span>
        </div>

        

        <!-- Profile & Logout -->
        <div class="flex items-center gap-1 md:gap-2">
            <a href="volunteer_profile.php" class="hidden md:flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-xl transition-all duration-200 hover:shadow-md">
                 Profile
            </a>
            <a href="../../logout.php" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/50 rounded-xl transition-all duration-200 hover:shadow-md">
                 <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </div>
</header>

<!-- Main Layout -->
<div class="flex flex-1 relative z-10 max-w-7xl mx-auto w-full">

    <!-- Sidebar -->
    <aside class="w-64 glass-sidebar hidden md:block flex-shrink-0 min-h-[calc(100vh-80px)]">
        <nav class="p-4 space-y-1.5 mt-2">
            <a href="volunteer_index.php" class="sidebar-link <?= ($current_page=='volunteer_index.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> Dashboard
            </a>
            <a href="available_tasks.php" class="sidebar-link <?= ($current_page=='available_tasks.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> Available Tasks
                <?php if($available_count > 0): ?>
                    <span class="link-badge"><?= $available_count ?></span>
                <?php endif; ?>
            </a>
            <a href="active_tasks.php" class="sidebar-link <?= ($current_page=='active_tasks.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> Active Tasks
                <?php if($assigned_count > 0): ?>
                    <span class="link-badge" style="background: #f59e0b;"><?= $assigned_count ?></span>
                <?php endif; ?>
            </a>
            <a href="history.php" class="sidebar-link <?= ($current_page=='history.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> History
            </a>
            
            
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-4 md:p-8 space-y-6 md:space-y-8 max-w-full">

        <!-- Hero Section -->
        <section class="hero-gradient rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-3xl">👋</span>
                        <h1 class="text-2xl md:text-3xl font-black tracking-tight">
                            Welcome back, <?= htmlspecialchars($volunteer_name) ?>
                        </h1>
                    </div>
                    <p class="text-sm text-indigo-100 font-medium max-w-2xl opacity-90">
                        Every delivery makes a difference. Track your missions, claim new tasks, and monitor your impact in real-time.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl border border-white/20 flex items-center gap-2">
                        <span class="text-2xl"><?= $levelIcon ?></span>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider opacity-60">Your Rank</div>
                            <div class="text-sm font-black"><?= $level ?></div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl border border-white/20">
                        <div class="text-[10px] font-bold uppercase tracking-wider opacity-60">Points</div>
                        <div class="text-sm font-black">🏆 <?= $points ?></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Cards -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="glass-card stat-card p-5 rounded-2xl fade-in fade-in-delay-1">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Available</h3>
                    <span class="text-lg">📦</span>
                </div>
                <p class="stat-number text-blue-600 mt-1"><?= $available_count ?></p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Tasks to claim</span>
                <div class="mt-2 w-full bg-blue-100 rounded-full h-1">
                    <div class="bg-blue-500 h-1 rounded-full" style="width: <?= min(100, $available_count * 20) ?>%"></div>
                </div>
            </div>

            <div class="glass-card stat-card p-5 rounded-2xl fade-in fade-in-delay-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active</h3>
                    <span class="text-lg">🚚</span>
                </div>
                <p class="stat-number text-amber-500 mt-1"><?= $assigned_count ?></p>
                <span class="text-[10px] text-slate-400 block mt-0.5">In progress</span>
                <div class="mt-2 w-full bg-amber-100 rounded-full h-1">
                    <div class="bg-amber-500 h-1 rounded-full" style="width: <?= min(100, $assigned_count * 20) ?>%"></div>
                </div>
            </div>

            <div class="glass-card stat-card p-5 rounded-2xl fade-in fade-in-delay-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Delivered</h3>
                    <span class="text-lg">✅</span>
                </div>
                <p class="stat-number text-emerald-600 mt-1"><?= $completed_count ?></p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Completed</span>
                <div class="mt-2 w-full bg-emerald-100 rounded-full h-1">
                    <div class="bg-emerald-500 h-1 rounded-full" style="width: <?= min(100, $completed_count * 20) ?>%"></div>
                </div>
            </div>

            <div class="glass-card stat-card p-5 rounded-2xl fade-in fade-in-delay-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Impact</h3>
                    <span class="text-lg">❤️</span>
                </div>
                <p class="stat-number text-violet-600 mt-1"><?= $completed_count * 10 ?></p>
                <span class="text-[10px] text-slate-400 block mt-0.5">People served</span>
                <div class="mt-2 w-full bg-violet-100 rounded-full h-1">
                    <div class="bg-violet-500 h-1 rounded-full" style="width: <?= min(100, ($completed_count * 10) / 10) ?>%"></div>
                </div>
            </div>

            <div class="glass-card stat-card p-5 rounded-2xl col-span-2 md:col-span-1 fade-in fade-in-delay-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Efficiency</h3>
                    <span class="text-lg">⚡</span>
                </div>
                <p class="stat-number text-indigo-600 mt-1"><?= $completion_rate ?>%</p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Success rate</span>
                <div class="mt-2 w-full bg-indigo-100 rounded-full h-1">
                    <div class="bg-indigo-500 h-1 rounded-full" style="width: <?= $completion_rate ?>%"></div>
                </div>
            </div>
        </section>

        <!-- Progress & Activity -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Progress Section -->
            <div class="lg:col-span-1 glass-card rounded-3xl p-6 fade-in fade-in-delay-3">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">🏆 Progress</h2>
                    <span class="text-xs font-bold text-blue-600"><?= $points ?> pts</span>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between text-[10px] font-bold text-slate-400 mb-1">
                        <span>Bronze</span>
                        <span>Silver</span>
                        <span>Gold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="fill" style="width: <?= $progress ?>%"></div>
                    </div>
                    <div class="flex justify-between text-[9px] font-bold text-slate-400 mt-1">
                        <span>0 pts</span>
                        <span><?= $nextLevel ?> pts</span>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between p-2 bg-white/50 rounded-lg">
                        <span class="font-medium text-slate-600">Tasks Completed</span>
                        <span class="font-black text-emerald-600"><?= $completed_count ?></span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-white/50 rounded-lg">
                        <span class="font-medium text-slate-600">Active Missions</span>
                        <span class="font-black text-amber-500"><?= $assigned_count ?></span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-white/50 rounded-lg">
                        <span class="font-medium text-slate-600">Next Rank</span>
                        <span class="font-black text-indigo-600">
                            <?= $points < 200 ? 'Silver' : ($points < 500 ? 'Gold' : '🏆 Elite') ?>
                        </span>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100/50">
                    <p class="text-[10px] font-bold text-blue-700">
                        💡 Tip: Complete <?= max(1, ceil((200 - $points) / 20)) ?> more deliveries to reach the next level!
                    </p>
                </div>
            </div>

            <!-- Recent Activity Feed -->
            <div class="lg:col-span-2 glass-card rounded-3xl p-6 fade-in fade-in-delay-4">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">🔄 Recent Activity</h2>
                        <p class="text-[10px] text-slate-400 mt-0.5">Latest updates from your missions</p>
                    </div>
                    <a href="history.php" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 transition">
                        View all →
                    </a>
                </div>

                <div class="space-y-2 max-h-72 overflow-y-auto pr-2">
                    <?php if ($recentActivities && $recentActivities->num_rows > 0): ?>
                        <?php $displayed = 0; ?>
                        <?php while($activity = $recentActivities->fetch_assoc() && $displayed < 5): 
                            $status = strtolower($activity['status'] ?? 'pending');
                            $statusIcons = [
                                'pending' => '⏳',
                                'accepted' => '📋',
                                'assigned' => '🚚',
                                'in_transit' => '🚛',
                                'delivered' => '✅'
                            ];
                            $statusLabels = [
                                'pending' => 'Available',
                                'accepted' => 'Accepted',
                                'assigned' => 'Assigned',
                                'in_transit' => 'In Transit',
                                'delivered' => 'Delivered'
                            ];
                            $icon = $statusIcons[$status] ?? '📦';
                            $label = $statusLabels[$status] ?? ucfirst($status);
                            $displayed++;
                        ?>
                            <div class="activity-item <?= $status ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="text-lg flex-shrink-0"><?= $icon ?></span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-800 truncate">
                                                <?= htmlspecialchars($activity['food_name'] ?? 'Unknown Item') ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                                <span><?= htmlspecialchars($activity['donor_name'] ?? 'Anonymous') ?></span>
                                                <span>•</span>
                                                <span><?= date('h:i A', strtotime($activity['created_at'] ?? 'now')) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold whitespace-nowrap ml-2
                                        <?= $status === 'delivered' ? 'text-emerald-600' : 
                                           ($status === 'assigned' ? 'text-purple-600' : 
                                           ($status === 'in_transit' ? 'text-amber-600' : 
                                           ($status === 'accepted' ? 'text-blue-600' : 'text-slate-400'))) ?>">
                                        <?= $label ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <span class="text-4xl block mb-3">📭</span>
                            <p class="text-sm font-bold text-slate-600">No recent activity</p>
                            <p class="text-xs text-slate-400">Your missions will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 fade-in fade-in-delay-5">
            <a href="available_tasks.php" class="glass-card p-4 rounded-2xl text-center hover:shadow-lg transition-all group">
                <span class="text-3xl block mb-2 group-hover:scale-110 transition-transform">📦</span>
                <span class="text-xs font-bold text-slate-700 block">Find Tasks</span>
                <span class="text-[9px] text-slate-400">Browse available</span>
            </a>
            <a href="active_task.php" class="glass-card p-4 rounded-2xl text-center hover:shadow-lg transition-all group">
                <span class="text-3xl block mb-2 group-hover:scale-110 transition-transform">🚚</span>
                <span class="text-xs font-bold text-slate-700 block">My Missions</span>
                <span class="text-[9px] text-slate-400">Track progress</span>
            </a>
            <a href="history.php" class="glass-card p-4 rounded-2xl text-center hover:shadow-lg transition-all group">
                <span class="text-3xl block mb-2 group-hover:scale-110 transition-transform">📜</span>
                <span class="text-xs font-bold text-slate-700 block">History</span>
                <span class="text-[9px] text-slate-400">View completed</span>
            </a>
            <a href="volunteer_profile.php" class="glass-card p-4 rounded-2xl text-center hover:shadow-lg transition-all group">
                <span class="text-3xl block mb-2 group-hover:scale-110 transition-transform">👤</span>
                <span class="text-xs font-bold text-slate-700 block">Profile</span>
                <span class="text-[9px] text-slate-400">Manage account</span>
            </a>
        </div>

    </main>
</div>

<!-- Mobile Bottom Navigation -->
<div class="md:hidden fixed bottom-0 left-0 right-0 z-50 mobile-nav px-2 py-1 flex justify-around items-center">
    <a href="volunteer_index.php" class="mobile-nav-item active relative">
        <span class="icon">📊</span>
        <span>Home</span>
    </a>
    <a href="available_tasks.php" class="mobile-nav-item relative">
        <span class="icon">📦</span>
        <span>Available</span>
        <?php if($available_count > 0): ?>
            <span class="badge"><?= $available_count ?></span>
        <?php endif; ?>
    </a>
    <a href="active_task.php" class="mobile-nav-item relative">
        <span class="icon">🚚</span>
        <span>Active</span>
        <?php if($assigned_count > 0): ?>
            <span class="badge"><?= $assigned_count ?></span>
        <?php endif; ?>
    </a>
    <a href="notifications.php" class="mobile-nav-item relative">
        <span class="icon">🔔</span>
        <span>Alerts</span>
        <?php if($unreadCount > 0): ?>
            <span class="badge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <a href="volunteer_profile.php" class="mobile-nav-item">
        <span class="icon">👤</span>
        <span>Profile</span>
    </a>
</div>

<!-- Footer -->
<footer class="text-center py-6 text-[10px] font-bold text-slate-400 mt-8 md:mt-12 mb-16 md:mb-0 relative z-10">
    &copy; <?= date('Y') ?> F-Destiny Hub 
    <span class="mx-2">|</span> 
    Preserving Resources • Protecting Ecosystems ❤️
</footer>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Animate stat numbers
    document.querySelectorAll(".stat-number").forEach(el => {
        const target = parseInt(el.textContent);
        if (target > 0) {
            let current = 0;
            const increment = Math.ceil(target / 25);
            const interval = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(interval);
                }
                el.textContent = current;
            }, 40);
        }
    });

    // Mobile nav active state
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.mobile-nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
        }
    });

    // Auto-refresh every 60 seconds (optional)
    // setInterval(() => location.reload(), 60000);
});
</script>

</body>
</html>