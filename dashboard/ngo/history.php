<?php
session_start();
require_once '../../middleware/auth.php';
checkRole('ngo');

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

$email = $_SESSION['user'];

// Secured via Prepared Statement 
$userStmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$ngo = $userStmt->get_result()->fetch_assoc();

if (!$ngo) {
    die("Access Denied: Invalid User Node.");
}

$ngo_id = $ngo['id'];
$ngo_name = $ngo['name'];

/* FETCH ONLY COMPLETED/DELIVERED DONATIONS FOR HISTORY */
$stmt = $conn->prepare("
    SELECT
        d.*,
        donor.name AS donor_name,
        volunteer.name AS volunteer_name
    FROM donations d
    LEFT JOIN users donor ON d.donor_id = donor.id
    LEFT JOIN users volunteer ON d.volunteer_id = volunteer.id
    WHERE d.ngo_id = ?
    AND d.status = 'delivered'
    ORDER BY d.created_at DESC
");
$stmt->bind_param("i", $ngo_id);
$stmt->execute();
$result = $stmt->get_result();

$total_managed = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | NGO Donation History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* PREMIUM DRIFTING BLUR ENGINE */
        .animated-bg {
            background-color: #fcfaff;
            position: relative;
            overflow-x: hidden;
        }

        .blob {
            position: absolute;
            background-image: radial-gradient(circle, rgba(147, 51, 234, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround 25s infinite alternate ease-in-out;
        }
        .blob-1 { width: 550px; height: 550px; top: -10%; left: -5%; animation-duration: 22s; }
        .blob-2 { width: 650px; height: 650px; bottom: -5%; right: -5%; background-image: radial-gradient(circle, rgba(168, 85, 247, 0.1) 0%, transparent 70%); animation-duration: 30s; }

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 30px) scale(1.1); }
            100% { transform: translate(-30px, 60px) scale(0.95); }
        }

        /* MASTERGLASS PLATES (SKINNY/HAIRLINE THIN BORDERS) */
        .glass-header {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(16px) saturate(120%);
            -webkit-backdrop-filter: blur(16px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 30px rgba(147, 51, 234, 0.01), inset 0 1px 1px rgba(255, 255, 255, 0.3);
        }

        /* History entry styling - subtle gray/ash tone for completed items */
        .history-row {
            opacity: 0.85;
            transition: all 0.3s ease;
        }

        .history-row:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.5);
        }

        .history-badge {
            background: rgba(100, 116, 139, 0.1);
            color: #475569;
            border-color: rgba(100, 116, 139, 0.2);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<header class="glass-header sticky top-0 z-50 border-b border-purple-100 shadow-sm px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2 text-2xl font-black text-purple-600 tracking-tight">
        <span>🍱</span> F-Destiny
    </div>
    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block">
        <h1>History Archive</h1>
    </div>
    <a href="ngo_index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-purple-700 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a>   
</header>

<main class="flex-1 max-w-7xl w-full mx-auto p-4 md:p-8 space-y-6 relative z-10">

    <div class="glass-card p-6 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">
                📜 Donation History Archive
            </h2>
            <p class="text-xs text-slate-500 mt-1 max-w-xl font-medium">
                Completed donation records that have been successfully delivered. 
                <span class="text-purple-600 font-bold"><?= htmlspecialchars($ngo_name) ?></span>
            </p>
        </div>
        
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold text-slate-600 bg-slate-100/80 border border-slate-200/50 backdrop-blur-md shadow-sm self-start sm:self-auto">
            <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse"></span>
            <span>Completed Deliveries:</span>
            <span class="font-black text-sm ml-0.5 text-emerald-700"><?= $total_managed ?></span>
        </div>
    </div>

    <div class="glass-card rounded-3xl border border-white/80 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/50">
                        <th class="p-4 pl-6">#</th>
                        <th class="p-4">Food Item</th>
                        <th class="p-4">Quantity</th>
                        <th class="p-4">Donor</th>
                        <th class="p-4">Volunteer</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 pr-6">Completed On</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/60 text-sm font-medium text-slate-700">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php 
                    $serial_num = 1;
                    while($row = $result->fetch_assoc()): 
                        $status = strtolower($row['status']);
                    ?>
                    <tr class="history-row hover:bg-white/50 transition-colors duration-150">
                        <td class="p-4 pl-6 text-xs font-bold text-slate-400"><?= $serial_num++ ?></td>
                        <td class="p-4 font-bold text-slate-700"><?= htmlspecialchars($row['food_name']) ?></td>
                        <td class="p-4 text-xs font-semibold">
                            <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 border border-slate-200">
                                <?= htmlspecialchars($row['quantity']) ?>
                            </span>
                        </td>
                        <td class="p-4 text-xs font-semibold text-slate-700"><?= htmlspecialchars($row['donor_name']) ?></td>
                        <td class="p-4 text-xs font-semibold text-slate-600">
                            <?= $row['volunteer_name'] ? htmlspecialchars($row['volunteer_name']) : '<span class="text-slate-400 italic font-normal">Not Assigned</span>' ?>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs border rounded-full font-bold bg-emerald-50/80 text-emerald-700 border-emerald-200/50">
                                <span>✅</span>
                                <span class="uppercase tracking-wide text-[10px]">Delivered</span>
                            </span>
                        </td>
                        <td class="p-4 pr-6 text-xs font-semibold text-slate-400">
                            <div class="flex flex-col">
                                <span><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                                <span class="text-[10px] text-slate-400 font-normal"><?= date('h:i A', strtotime($row['created_at'])) ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="p-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="text-5xl">📭</div>
                                <h3 class="text-base font-bold text-slate-600">No Completed Donations Yet</h3>
                                <p class="text-xs text-slate-400 max-w-sm">
                                    Your completed donation history will appear here once donations are successfully delivered.
                                </p>
                                <div class="mt-2 flex items-center gap-2 text-xs text-slate-400">
                                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                    <span>Waiting for first delivery</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Statistics -->
    <?php if ($result && $result->num_rows > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-card p-4 rounded-2xl text-center">
            <div class="text-2xl font-black text-emerald-600"><?= $total_managed ?></div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Deliveries</div>
        </div>
        <div class="glass-card p-4 rounded-2xl text-center">
            <div class="text-2xl font-black text-purple-600"><?= date('M Y') ?></div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Month</div>
        </div>
        <div class="glass-card p-4 rounded-2xl text-center">
            <div class="text-2xl font-black text-slate-600">✅</div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">All Completed</div>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
// Auto-hide any flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.fade-in');
    messages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => {
                msg.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>

</body>
</html>