<?php
session_start();

require_once dirname(__DIR__,2) . '/middleware/auth.php';
include_once dirname(__DIR__,2) . '/config/db.php';

checkRole('ngo');

$current_page = basename($_SERVER['PHP_SELF']);

$email = $_SESSION['user'];

/* =========================
   1. GET NGO DETAILS
========================= */
$stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("NGO not found");
}

$ngo = $result->fetch_assoc();
$ngo_id = $ngo['id'];
$ngo_phone = $ngo['phone'];

$stmt->close();

/* =========================
   2. TOTAL DONATIONS
========================= */
$totalDonations = 0;

$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'donations'");

if (mysqli_num_rows($checkTable) > 0) {
    $query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM donations");
    $row = mysqli_fetch_assoc($query);
    $totalDonations = $row['total'];
}

/* =========================
   3. ACCEPTED DONATIONS (by this NGO)
========================= */
$accepted_query = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM donations
    WHERE ngo_id = ? AND status = 'accepted'
");
$accepted_query->bind_param("i", $ngo_id);
$accepted_query->execute();
$accepted_data = $accepted_query->get_result()->fetch_assoc();
$total_accepted = $accepted_data['total'];

/* =========================
   4. PENDING DONATIONS (available for claim)
========================= */
$pending_query = $conn->query("
    SELECT COUNT(*) AS total
    FROM donations
    WHERE (status = 'pending' OR status IS NULL)
      AND expiry IS NOT NULL
      AND expiry > NOW()
");

$pendingCount = 0;

if ($pending_query) {
    $pendingRow = $pending_query->fetch_assoc();
    $pendingCount = (int)($pendingRow['total'] ?? 0);
}

/* =========================
   5. IN TRANSIT DONATIONS
========================= */
$transit_query = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM donations
    WHERE ngo_id = ? AND status = 'in_transit'
");
$transit_query->bind_param("i", $ngo_id);
$transit_query->execute();
$transit_data = $transit_query->get_result()->fetch_assoc();
$totalInTransit = $transit_data['total'];

/* =========================
   6. DELIVERED DONATIONS
========================= */
$delivered_query = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM donations
    WHERE ngo_id = ? AND status = 'delivered'
");
$delivered_query->bind_param("i", $ngo_id);
$delivered_query->execute();
$delivered_data = $delivered_query->get_result()->fetch_assoc();
$totalDelivered = $delivered_data['total'];

/* =========================
   7. WITH VOLUNTEER (pending_volunteer)
========================= */
$volunteer_query = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM donations
    WHERE ngo_id = ? AND status = 'pending_volunteer'
");
$volunteer_query->bind_param("i", $ngo_id);
$volunteer_query->execute();
$volunteer_data = $volunteer_query->get_result()->fetch_assoc();
$totalWithVolunteer = $volunteer_data['total'];

/* =========================
   8. REJECTED DONATIONS
========================= */
$rejected_query = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM donations
    WHERE ngo_id = ? AND status = 'rejected'
");
$rejected_query->bind_param("i", $ngo_id);
$rejected_query->execute();
$rejected_data = $rejected_query->get_result()->fetch_assoc();
$totalRejected = $rejected_data['total'];

/* =========================
   9. UNREAD ACTIVE NOTIFICATIONS
   Only count notifications for donations
   that have NOT expired.
========================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications n
    INNER JOIN donations d
        ON d.id = n.donation_id
    WHERE n.user_id = ?
      AND n.is_read = 0
      AND d.expiry IS NOT NULL
      AND d.expiry > NOW()
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$unreadCount = (int)($row['total'] ?? 0);

$stmt->close();

/* =========================
   10. RECENT ACTIVITY (last 5 donations)
========================= */
$recentStmt = $conn->prepare("
    SELECT 
        d.*,
        donor.name AS donor_name
    FROM donations d
    LEFT JOIN users donor ON d.donor_id = donor.id
    WHERE d.ngo_id = ? OR d.status = 'pending' OR d.status IS NULL
    ORDER BY d.created_at DESC
    LIMIT 5
");
$recentStmt->bind_param("i", $ngo_id);
$recentStmt->execute();
$recentActivities = $recentStmt->get_result();

/* =========================
   11. COMPLETION RATE
========================= */
$completionRate = ($total_accepted + $totalDelivered) > 0 ? 
    round(($totalDelivered / ($total_accepted + $totalDelivered)) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Premium NGO Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }

        /* PREMIUM DRIFTING BLUR ENGINE */
        .animated-bg {
            background-color: #fcfaff;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround 25s infinite alternate ease-in-out;
        }
        .blob-1 { 
            width: 550px; 
            height: 550px; 
            top: -10%; 
            left: -5%; 
            background-image: radial-gradient(circle, rgba(147, 51, 234, 0.12) 0%, transparent 70%);
            animation-duration: 22s; 
        }
        .blob-2 { 
            width: 650px; 
            height: 650px; 
            bottom: -5%; 
            right: -5%; 
            background-image: radial-gradient(circle, rgba(168, 85, 247, 0.1) 0%, transparent 70%); 
            animation-duration: 30s; 
        }
        .blob-3 {
            width: 400px;
            height: 400px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-image: radial-gradient(circle, rgba(16, 185, 129, 0.06) 0%, transparent 70%);
            animation-duration: 35s;
            animation-delay: -5s;
        }

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 30px) scale(1.1); }
            100% { transform: translate(-30px, 60px) scale(0.95); }
        }

        /* MASTERGLASS PLATES */
        .glass-header {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(16px) saturate(120%);
            -webkit-backdrop-filter: blur(16px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 30px rgba(147, 51, 234, 0.02), inset 0 1px 1px rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(147, 51, 234, 0.08);
        }

        /* Stat Cards */
        .stat-card {
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, currentColor, transparent);
            opacity: 0.3;
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
        }

        /* Quick Action Items */
        .quick-action-item {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
        }

        .quick-action-item:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06);
        }

        .quick-action-item .icon {
            font-size: 32px;
            display: block;
            margin-bottom: 8px;
        }

        .quick-action-item .label {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            display: block;
        }

        .quick-action-item .sub-label {
            font-size: 10px;
            color: #94a3b8;
            display: block;
            margin-top: 2px;
        }

        .quick-action-item .badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ef4444;
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            animation: pulse 2s infinite;
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

        .activity-item.pending {
            border-left-color: #f59e0b;
        }

        .activity-item.accepted {
            border-left-color: #3b82f6;
        }

        .activity-item.in_transit {
            border-left-color: #8b5cf6;
        }

        .activity-item.delivered {
            border-left-color: #10b981;
        }

        .activity-item.rejected {
            border-left-color: #ef4444;
        }

        /* Pulse Animation */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Sidebar */
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #64748b;
            position: relative;
        }

        .sidebar-link:hover {
            background: rgba(147, 51, 234, 0.08);
            color: #7c3aed;
        }

        .sidebar-link.active {
            background: #7c3aed;
            color: white;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }

        .sidebar-link .link-icon {
            margin-right: 12px;
            font-size: 18px;
        }

        .sidebar-link .link-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            animation: pulse 2s infinite;
        }

        /* Counter Animation */
        .counter {
            display: inline-block;
            transition: all 0.3s ease;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(147, 51, 234, 0.2);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(147, 51, 234, 0.3);
        }

        /* Progress Bar Animation */
        .progress-bar {
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<!-- Background Blobs -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- Header -->
<header class="glass-header sticky top-0 z-50 border-b border-purple-100/60 shadow-sm px-4 md:px-6 py-3 md:py-4 flex justify-between items-center">
    <!-- Left Section - Logo & Brand -->
    <div class="flex items-center gap-2 md:gap-3">
        <span class="text-2xl md:text-3xl">🍱</span>
        <span class="text-lg md:text-xl font-black text-purple-600 tracking-tight">F-Destiny</span>
        <span class="hidden md:inline-block text-[10px] font-bold text-slate-400 bg-slate-100/80 px-2.5 py-0.5 rounded-full uppercase tracking-wider border border-slate-200/50">
            NGO Portal
        </span>
    </div>

    <!-- Center Section - Page Title (Optional) -->
    <div class="hidden lg:block text-center">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest font-mono">NGO Dashboard</span>
    </div>

    <!-- Right Section - User Info & Actions -->
    <div class="flex items-center gap-2 md:gap-4">
        <!-- Online Status -->
        <div class="hidden md:flex items-center gap-2 text-xs font-medium text-slate-500 bg-white/60 px-3 py-1.5 rounded-full border border-slate-200/50 shadow-sm">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse ring-2 ring-emerald-500/20"></span>
            <span>Online</span>
        </div>

        

        <!-- Profile & Logout Buttons -->
        <div class="flex items-center gap-1 md:gap-2">
            <a href="ngo_profile.php" class="hidden md:flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/20 rounded-xl transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <span></span>
                <span>Profile</span>
            </a>
            
            <a href="/f_destiny/logout.php" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/50 rounded-xl transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <span></span>
                <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </div>
</header>

<!-- Mobile Bottom Navigation (Optional - for better mobile UX) -->
<div class="md:hidden fixed bottom-0 left-0 right-0 z-50 glass-header border-t border-purple-100/60 px-2 py-1 flex justify-around items-center">
    <a href="ngo_index.php" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-purple-600">
        <span class="text-lg">📊</span>
        <span class="text-[8px] font-bold uppercase tracking-wider">Home</span>
    </a>
    <a href="available_donations.php" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-slate-400 hover:text-purple-600 transition">
        <span class="text-lg">📦</span>
        <span class="text-[8px] font-bold uppercase tracking-wider">Available</span>
    </a>
    <a href="notifications.php" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-slate-400 hover:text-purple-600 transition relative">
        <span class="text-lg">🔔</span>
        <?php if(isset($unreadCount) && $unreadCount > 0): ?>
            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-rose-500 text-white text-[8px] font-bold rounded-full flex items-center justify-center animate-pulse">
                <?= $unreadCount ?>
            </span>
        <?php endif; ?>
        <span class="text-[8px] font-bold uppercase tracking-wider">Alerts</span>
    </a>
    <a href="ngo_profile.php" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-slate-400 hover:text-purple-600 transition">
        <span class="text-lg">👤</span>
        <span class="text-[8px] font-bold uppercase tracking-wider">Profile</span>
    </a>
</div>

<style>
/* Header Styles */
.glass-header {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

/* Profile Avatar Hover */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

/* Mobile Bottom Nav Active State */
.mobile-nav-item.active {
    background: rgba(147, 51, 234, 0.1);
    color: #7c3aed;
}

/* Notification Badge Pulse */
@keyframes badgePulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.animate-pulse {
    animation: badgePulse 2s infinite;
}
</style>

<script>
// Add active class to mobile nav items based on current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    
    mobileNavItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
        }
    });
});

// Handle profile click with smooth transition
document.querySelector('.group')?.addEventListener('click', function(e) {
    // Prevent if clicking on buttons inside
    if (e.target.closest('a')) return;
    window.location.href = 'ngo_profile.php';
});
</script>

<!-- Main Layout -->
<div class="flex flex-1 relative z-10 max-w-7xl w-full mx-auto">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-purple-100/50 p-4 hidden md:block">
        <nav class="space-y-1.5 mt-4">
            <a href="ngo_index.php" class="sidebar-link <?= ($current_page=='ngo_index.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> Dashboard
            </a>

            <a href="available_donations.php" class="sidebar-link <?= ($current_page=='available_donations.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> Available Donations
                <?php if($pendingCount > 0): ?>
                    <span class="link-badge"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>

            <a href="accepted_donations.php" class="sidebar-link <?= ($current_page=='accepted_donations.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> Accepted Donations
                <?php if($total_accepted > 0): ?>
                    <span class="link-badge" style="background: #3b82f6;"><?= $total_accepted ?></span>
                <?php endif; ?>
            </a>

            <a href="notifications.php" class="sidebar-link <?= ($current_page=='notifications.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> Notifications
                <?php if($unreadCount > 0): ?>
                    <span class="link-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>

            <a href="history.php" class="sidebar-link <?= ($current_page=='history.php') ? 'active' : '' ?>">
                <span class="link-icon"></span> History
            </a>

           
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-4 md:p-8 space-y-8">

        <!-- Welcome Section -->
        <div class="glass-card p-6 md:p-8 rounded-3xl relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-purple-500 to-purple-600"></div>
            <div class="absolute right-0 top-0 bottom-0 w-64 bg-gradient-to-l from-purple-500/5 to-transparent pointer-events-none"></div>
            
            <div class="relative">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                            Welcome back, <span class="text-purple-600"><?= htmlspecialchars($ngo['name']); ?></span>
                        </h2>
                        <p class="mt-2 text-sm text-slate-500 leading-relaxed max-w-2xl">
                            🚀 Manage food distribution, track donations, and coordinate with volunteers through the F-Destiny platform.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-4 py-2 rounded-xl font-bold shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <?= date('l, F j, Y') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-card stat-card p-6 rounded-2xl text-center text-emerald-600">
                <div class="flex items-center justify-between">
                    <span class="text-3xl">📦</span>
                    <span class="text-[10px] font-bold text-emerald-400 bg-emerald-50 px-2 py-0.5 rounded-full">Available</span>
                </div>
                <div class="mt-3">
                    <span class="text-4xl font-black counter" id="totalAvailable"><?= $pendingCount ?></span>
                    <span class="block text-xs font-medium text-slate-400 mt-1">Donations Available</span>
                </div>
                <div class="mt-2 w-full bg-emerald-100 rounded-full h-1.5">
                    <div class="bg-emerald-500 h-1.5 rounded-full progress-bar" style="width: <?= min(100, $pendingCount * 10) ?>%"></div>
                </div>
            </div>

            <div class="glass-card stat-card p-6 rounded-2xl text-center text-blue-600">
                <div class="flex items-center justify-between">
                    <span class="text-3xl">✅</span>
                    <span class="text-[10px] font-bold text-blue-400 bg-blue-50 px-2 py-0.5 rounded-full">Accepted</span>
                </div>
                <div class="mt-3">
                    <span class="text-4xl font-black counter" id="totalAccepted"><?= $total_accepted ?></span>
                    <span class="block text-xs font-medium text-slate-400 mt-1">Accepted Donations</span>
                </div>
                <div class="mt-2 w-full bg-blue-100 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full progress-bar" style="width: <?= min(100, $total_accepted * 10) ?>%"></div>
                </div>
            </div>

            <div class="glass-card stat-card p-6 rounded-2xl text-center text-purple-600">
                <div class="flex items-center justify-between">
                    <span class="text-3xl">🚚</span>
                    <span class="text-[10px] font-bold text-purple-400 bg-purple-50 px-2 py-0.5 rounded-full">In Transit</span>
                </div>
                <div class="mt-3">
                    <span class="text-4xl font-black counter" id="totalInTransit"><?= $totalInTransit ?></span>
                    <span class="block text-xs font-medium text-slate-400 mt-1">In Transit</span>
                </div>
                <div class="mt-2 w-full bg-purple-100 rounded-full h-1.5">
                    <div class="bg-purple-500 h-1.5 rounded-full progress-bar" style="width: <?= min(100, $totalInTransit * 10) ?>%"></div>
                </div>
            </div>

            <div class="glass-card stat-card p-6 rounded-2xl text-center text-emerald-600">
                <div class="flex items-center justify-between">
                    <span class="text-3xl">🎯</span>
                    <span class="text-[10px] font-bold text-emerald-400 bg-emerald-50 px-2 py-0.5 rounded-full">Delivered</span>
                </div>
                <div class="mt-3">
                    <span class="text-4xl font-black counter" id="totalDelivered"><?= $totalDelivered ?></span>
                    <span class="block text-xs font-medium text-slate-400 mt-1">Successfully Delivered</span>
                </div>
                <div class="mt-2 w-full bg-emerald-100 rounded-full h-1.5">
                    <div class="bg-emerald-500 h-1.5 rounded-full progress-bar" style="width: <?= min(100, $totalDelivered * 10) ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="glass-card p-4 rounded-2xl text-center">
                <div class="text-2xl font-black text-amber-600"><?= $totalWithVolunteer ?></div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">With Volunteers</div>
            </div>
            <div class="glass-card p-4 rounded-2xl text-center">
                <div class="text-2xl font-black text-rose-600"><?= $totalRejected ?></div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rejected</div>
            </div>
            <div class="glass-card p-4 rounded-2xl text-center">
                <div class="text-2xl font-black text-indigo-600"><?= $completionRate ?>%</div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Completion Rate</div>
            </div>
            <div class="glass-card p-4 rounded-2xl text-center">
                <div class="text-2xl font-black text-slate-600"><?= date('M Y') ?></div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Month</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Quick Actions -->
            <div class="lg:col-span-2 glass-card p-6 rounded-3xl">
                <div class="mb-6">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">⚡ Quick Actions</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Streamlined operations for efficient food distribution</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <a href="available_donations.php" class="quick-action-item">
                        <span class="icon">📦</span>
                        <span class="label">Available Donations</span>
                        <span class="sub-label">Claim & manage</span>
                        <?php if($pendingCount > 0): ?>
                            <span class="badge"><?= $pendingCount ?> New</span>
                        <?php endif; ?>
                    </a>

                    <a href="accepted_donations.php" class="quick-action-item">
                        <span class="icon">✅</span>
                        <span class="label">Accepted Donations</span>
                        <span class="sub-label">Process & assign</span>
                        <?php if($total_accepted > 0): ?>
                            <span class="badge" style="background: #3b82f6;"><?= $total_accepted ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="history.php" class="quick-action-item">
                        <span class="icon">📜</span>
                        <span class="label">View History</span>
                        <span class="sub-label">Completed deliveries</span>
                    </a>

                    <a href="notifications.php" class="quick-action-item">
                        <span class="icon">🔔</span>
                        <span class="label">Notifications</span>
                        <span class="sub-label">View updates</span>
                        <?php if($unreadCount > 0): ?>
                            <span class="badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="glass-card p-6 rounded-3xl">
                <div class="mb-6">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">🔄 Recent Activity</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Latest donation updates</p>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
                    <?php if ($recentActivities && $recentActivities->num_rows > 0): ?>
                        <?php while($activity = $recentActivities->fetch_assoc()): 
                            $status = strtolower($activity['status'] ?? 'pending');
                            $statusColors = [
                                'pending' => 'text-amber-600',
                                'accepted' => 'text-blue-600',
                                'in_transit' => 'text-purple-600',
                                'delivered' => 'text-emerald-600',
                                'rejected' => 'text-rose-600'
                            ];
                            $statusLabels = [
                                'pending' => 'Pending',
                                'accepted' => 'Accepted',
                                'in_transit' => 'In Transit',
                                'delivered' => 'Delivered ✅',
                                'rejected' => 'Rejected ❌'
                            ];
                            $color = $statusColors[$status] ?? 'text-slate-600';
                            $label = $statusLabels[$status] ?? ucfirst($status);
                        ?>
                            <div class="activity-item <?= $status ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="text-lg flex-shrink-0">
                                            <?= $status === 'delivered' ? '✅' : ($status === 'in_transit' ? '🚚' : ($status === 'accepted' ? '📋' : '⏳')) ?>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-800 truncate">
                                                <?= htmlspecialchars($activity['food_name'] ?? 'Unknown Item') ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                                <span><?= $activity['donor_name'] ?? 'Anonymous' ?></span>
                                                <span>•</span>
                                                <span><?= date('h:i A', strtotime($activity['created_at'] ?? 'now')) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold <?= $color ?> whitespace-nowrap ml-2">
                                        <?= $label ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <span class="text-3xl block mb-2">📭</span>
                            <p class="text-sm font-medium text-slate-600">No recent activity</p>
                            <p class="text-xs text-slate-400">Donations will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Performance & Insights -->
        <div class="glass-card p-6 rounded-3xl">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">📊 Performance Insights</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Real-time metrics and analytics</p>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Updates
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-slate-50/50 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Distribution</span>
                        <span class="text-xs font-bold text-purple-600"><?= $total_accepted + $totalDelivered ?> Total</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-3 rounded-full progress-bar" 
                             style="width: <?= min(100, (($total_accepted + $totalDelivered) / max(1, $pendingCount + $total_accepted + $totalDelivered)) * 100) ?>%">
                        </div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[10px] text-slate-400">Available: <?= $pendingCount ?></span>
                        <span class="text-[10px] text-slate-400">Processed: <?= $total_accepted + $totalDelivered ?></span>
                    </div>
                </div>

                <div class="bg-slate-50/50 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Success Rate</span>
                        <span class="text-xs font-bold text-emerald-600"><?= $completionRate ?>%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-3 rounded-full progress-bar" 
                             style="width: <?= $completionRate ?>%">
                        </div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[10px] text-slate-400">Accepted: <?= $total_accepted ?></span>
                        <span class="text-[10px] text-slate-400">Delivered: <?= $totalDelivered ?></span>
                    </div>
                </div>

                <div class="bg-slate-50/50 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Operations</span>
                        <span class="text-xs font-bold text-blue-600"><?= $totalInTransit + $totalWithVolunteer ?></span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full progress-bar" 
                             style="width: <?= min(100, (($totalInTransit + $totalWithVolunteer) / max(1, $total_accepted + $totalInTransit + $totalWithVolunteer)) * 100) ?>%">
                        </div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[10px] text-slate-400">In Transit: <?= $totalInTransit ?></span>
                        <span class="text-[10px] text-slate-400">With Volunteer: <?= $totalWithVolunteer ?></span>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate counters on load
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        if (target > 0) {
            let current = 0;
            const increment = Math.ceil(target / 30);
            const interval = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(interval);
                }
                counter.textContent = current;
            }, 50);
        }
    });

    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 300);
    });
});
</script>

</body>
</html>