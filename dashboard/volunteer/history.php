<?php
/**
 * F-Destiny - Volunteer Distribution Log Ledger Component
 * File: history.php
 */

session_start();
require_once '../../middleware/auth.php';
checkRole('volunteer');

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$email = $_SESSION['user'];

/* =====================================================
   GET VOLUNTEER PARAMETERS (PREPARED)
===================================================== */
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$volunteer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$volunteer) {
    die("Identity Node Validation Error.");
}

$volunteer_id = $volunteer['id'];

/* =====================================================
   FULL VOLUNTEER HISTORY (PREPARED)
===================================================== */
$hist_stmt = $conn->prepare("
    SELECT 
        d.*,
        donor.name AS donor_name,
        ngo.name AS ngo_name
    FROM donations d
    LEFT JOIN users donor ON d.donor_id = donor.id
    LEFT JOIN users ngo ON d.ngo_id = ngo.id
    WHERE d.volunteer_id = ?
    ORDER BY d.created_at DESC
");
$hist_stmt->bind_param("i", $volunteer_id);
$hist_stmt->execute();
$result = $hist_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historic Manifest Ledger | F-Destiny Hub</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .animated-bg {
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }
        .blob {
            position: absolute;
            background-image: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(120px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround 20s infinite alternate ease-in-out;
        }
        .blob-1 { width: 700px; height: 700px; bottom: -20%; left: -10%; }

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -40px) scale(1.05); }
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(24px) saturate(180%);
            border-b: 1px solid rgba(241, 245, 249, 0.7);
        }
        .glass-sidebar {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(226, 232, 240, 0.5);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(16px) saturate(130%);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 4px 30px rgba(59, 130, 246, 0.01);
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-1"></div>

<header class="glass-header sticky top-0 z-50 flex justify-between items-center px-6 py-4 shadow-sm">
    <div class="flex items-center gap-2 text-2xl font-black text-blue-600 tracking-tight">
        <span>🚚</span> F-Destiny
    </div>
    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block">
        History Board
    </div>
    <a href="volunteer_index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a> 

</header>

<div class="flex flex-1 relative z-10">

    

    <main class="flex-1 p-4 md:p-8 space-y-6 max-w-7xl mx-auto w-full">
        
        <div class="flex justify-between items-center border-b border-slate-200/40 pb-3">
            <div>
                <h2 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span></span> History Records
                </h2>
                <p class="text-xs text-slate-400 font-medium">Review your history and performance data.</p>
            </div>
            <span class="text-[10px] font-bold px-2.5 py-1 text-slate-500 bg-slate-100 rounded-full border border-slate-200/30">
                <?= $result->num_rows ?> Submitted Records
            </span>
        </div>

        <div class="glass-card rounded-2xl overflow-hidden shadow-sm border border-slate-200/50">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/60 border-b border-slate-200/50 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="p-4">Assigned Resource</th>
                            <th class="p-4">Donor Name</th>
                            <th class="p-4">Target NGO </th>
                            <th class="p-4">Quantity </th>
                            <th class="p-4">Status </th>
                            <th class="p-4">Operation Stage</th>
                            <th class="p-4">Time History</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/70 bg-white/30">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                $status = strtolower($row['status']); 
                                // Clean fallback check configuration to avoid dynamic array warnings
                                $item_display_name = $row['food_name'] ?? $row['item_name'] ?? $row['item'] ?? 'Unknown Asset';
                            ?>
                                <tr class="history-row transition-all duration-200 hover:bg-white/80 text-xs font-medium text-slate-700">
                                    <td class="p-4 font-extrabold text-slate-900">
                                        <?= htmlspecialchars($item_display_name) ?>
                                    </td>
                                    
                                    <td class="p-4 text-slate-600">
                                        <?= htmlspecialchars($row['donor_name'] ?? 'Anonymous Donor') ?>
                                    </td>
                                    
                                    <td class="p-4">
                                        <?php if (!empty($row['ngo_name'])): ?>
                                            <span class="text-slate-700 font-bold"><?= htmlspecialchars($row['ngo_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic text-[11px]">Pending Task</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-4 font-bold text-slate-900">
                                        <?= htmlspecialchars($row['quantity'] ?? '1') ?>
                                    </td>
                                    
                                    <td class="p-4">
                                        <?php
                                        if ($status == 'assigned') {
                                            echo '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 uppercase">Accepted</span>';
                                        } elseif ($status == 'in_transit') {
                                            echo '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200/50 uppercase">In Transit</span>';
                                        } elseif ($status == 'delivered') {
                                            echo '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50 uppercase">Delivered</span>';
                                        } elseif ($status == 'rejected') {
                                            echo '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/50 uppercase">Dropped</span>';
                                        } else {
                                            echo '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-50 text-slate-500 border border-slate-200/50 uppercase">' . htmlspecialchars(strtoupper($status)) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    
                                    <td class="p-4 font-bold">
                                        <?php if ($status == 'assigned'): ?>
                                            <span class="text-amber-500 flex items-center gap-1.5">● <span class="text-[11px] font-medium text-slate-500">Staged</span></span>
                                        <?php elseif ($status == 'in_transit'): ?>
                                            <span class="text-blue-500 flex items-center gap-1.5 animate-pulse">● <span class="text-[11px] font-medium text-slate-500">Moving</span></span>
                                        <?php elseif ($status == 'delivered'): ?>
                                            <span class="text-emerald-500 flex items-center gap-1.5">● <span class="text-[11px] font-medium text-slate-500">Resolved</span></span>
                                        <?php else: ?>
                                            <span class="text-slate-400 flex items-center gap-1.5">● <span class="text-[11px] font-medium text-slate-400">Idle</span></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-4 text-slate-400 text-[11px] font-mono">
                                        <?= !empty($row['created_at']) ? date("Y-m-d H:i", strtotime($row['created_at'])) : 'N/A' ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-400 font-medium">
                                    <div class="text-2xl mb-2"></div>
                                    <p class="text-xs">No records have been created yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".history-row").forEach((row, index) => {
        row.style.opacity = "0";
        row.style.transform = "translateX(-4px)";
        setTimeout(() => {
            row.style.transition = "all .4s ease-out";
            row.style.opacity = "1";
            row.style.transform = "translateX(0)";
        }, index * 25);
    });
});
</script>

</body>
</html>
<?php 
$hist_stmt->close();
$conn->close(); 
?>