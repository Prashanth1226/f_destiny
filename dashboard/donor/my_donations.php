<?php
session_start();
require_once '../../middleware/auth.php';
checkRole('donor');

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$pageTitle = "My Donations";

$email = $conn->real_escape_string($_SESSION['user']);

$user = $conn->query("SELECT * FROM users WHERE email='$email'");
if (!$user || $user->num_rows == 0) {
    die("User not found");
}

$donor = $user->fetch_assoc();
$donor_id = $donor['id'];

$result = $conn->query("
    SELECT
        d.*,

        ngo.name AS ngo_name,
        ngo.email AS ngo_email,

        volunteer.name AS volunteer_name,
        volunteer.email AS volunteer_email

    FROM donations d

    LEFT JOIN users ngo
        ON d.ngo_id = ngo.id

    LEFT JOIN users volunteer
        ON d.volunteer_id = volunteer.id

    WHERE d.donor_id='$donor_id'

    ORDER BY d.id DESC
");
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | <?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        .bright-bg {
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }

        /* Vibrant glowing ambient backdrops for enhanced clarity */
        .glow-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
            mix-blend-mode: multiply;
        }
        .glow-1 { 
            width: 700px; 
            height: 700px; 
            top: -10%; 
            right: -5%; 
            background: radial-gradient(circle, rgba(52, 211, 153, 0.25) 0%, transparent 70%); 
        }
        .glow-2 { 
            width: 600px; 
            height: 600px; 
            bottom: -5%; 
            left: -5%; 
            background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, transparent 70%); 
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(24px) saturate(200%);
            -webkit-backdrop-filter: blur(24px) saturate(200%);
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px) saturate(150%);
            -webkit-backdrop-filter: blur(16px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 10px 30px -10px rgba(148, 163, 184, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body class="bright-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="glow-blob glow-1"></div>
<div class="glow-blob glow-2"></div>

<header class="glass-nav sticky top-0 z-50 border-b border-slate-200/80 shadow-sm px-6 py-3.5 flex justify-between items-center">
    <div class="flex items-center gap-3 select-none shrink-0">
        <span class="text-2xl drop-shadow-sm transform transition-transform duration-300 hover:rotate-12 inline-block">🍱</span>
        <span class="text-xl font-black text-emerald-600 tracking-tight uppercase bg-clip-text">F-Destiny</span>
    </div>
    
    <div class="hidden md:block text-center px-4">
        <h1 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest font-mono">Donations Interface</h1>
        <p class="text-sm text-slate-700 font-bold mt-0.5"><?= htmlspecialchars($pageTitle) ?></p>
    </div>

    <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 shrink-0">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a>
</header>

<div class="flex-1 max-w-7xl w-full mx-auto p-4 md:p-6 space-y-6 relative z-10">

    <div class="glass-panel p-6 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-md">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Your Donations</h2>
            <p class="text-xs font-medium text-slate-500 mt-1">Comprehensive audit ledger and real-time transit telemetry logs for your submitted items.</p>
        </div>
        <div class="flex items-center gap-2 text-xs bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-3 py-1.5 rounded-xl font-bold self-start sm:self-auto shadow-sm">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Tracked Live
        </div>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden border border-slate-200/80 shadow-2xl shadow-slate-200/50">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/90 text-[11px] font-bold text-slate-500 uppercase tracking-widest font-mono">
                        <th class="p-4 text-center w-16">S.No</th>
                        <th class="p-4">Food Item</th>
                        <th class="p-4"> Quantity</th>
                        <th class="p-4">Expiry Date</th>
                        <th class="p-4"> Address</th>
                        <th class="p-4">Assigned NGO</th>
                        <th class="p-4">Assigned Volunteer</th>
                        <th class="p-4"> Status</th>
                        <th class="p-4">Dispatched Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-600 bg-white/70">
                    <?php 
                    if ($result && $result->num_rows > 0) { 
                        $serialNumber = 1;
                        $hasActiveRecords = false;
                        
                        while($row = $result->fetch_assoc()) { 
                            // Skip delivered records
                            if (strtolower($row['status'] ?? '') === 'delivered') {
                                continue;
                            }
                            
                            $hasActiveRecords = true;
                            $status = strtolower($row['status'] ?? 'pending');
                            
                            switch($status) {
                                case 'accepted':
                                    $badge = "bg-blue-50 text-blue-700 border-blue-200";
                                    $dot = "bg-blue-500";
                                    $icon = "";
                                    $statusText = "Accepted By NGO";
                                    break;
                                case 'assigned':
                                    $badge = "bg-purple-50 text-purple-700 border-purple-200";
                                    $dot = "bg-purple-500";
                                    $icon = "";
                                    $statusText = "Courier Assigned";
                                    break;
                                case 'in_transit':
                                    $badge = "bg-amber-50 text-amber-700 border-amber-200";
                                    $dot = "bg-amber-500";
                                    $icon = "";
                                    $statusText = "In Transit";
                                    break;
                                case 'delivered':
                                    // This case won't be reached due to continue above
                                    $badge = "bg-emerald-50 text-emerald-700 border-emerald-200";
                                    $dot = "bg-emerald-500";
                                    $icon = "";
                                    $statusText = "Delivered Safe";
                                    break;
                                case 'rejected':
                                    $badge = "bg-rose-50 text-rose-700 border-rose-200";
                                    $dot = "bg-rose-500";
                                    $icon = "";
                                    $statusText = "Rejected";
                                    break;
                                default:
                                    $badge = "bg-orange-50 text-orange-700 border-orange-200";
                                    $dot = "bg-orange-500";
                                    $icon = "";
                                    $statusText = "Pending Review";
                            }
                    ?>
                            <tr class="hover:bg-white transition duration-150 group">
                                <td class="p-4 text-center font-mono text-slate-400 font-bold text-[12px]">
                                    <?= $serialNumber++ ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base"></span>
                                        <span class="font-bold text-slate-900 group-hover:text-emerald-600 transition duration-150"><?= htmlspecialchars($row['food_name']) ?></span>
                                    </div>
                                </td>
                                <td class="p-4 font-bold text-slate-800">
                                    <?= htmlspecialchars($row['quantity']) ?>
                                </td>
                                <td class="p-4">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-rose-50 border border-rose-100 text-rose-600 font-bold text-[11px]">
                                        <?= htmlspecialchars($row['expiry'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <p class="max-w-xs truncate text-slate-500 font-medium" title="<?= htmlspecialchars($row['address'] ?? 'N/A') ?>">
                                        <?= htmlspecialchars($row['address'] ?? 'N/A') ?>
                                    </p>
                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    <?php if(!empty($row['ngo_name'])): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span><?= htmlspecialchars($row['ngo_name']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 font-normal italic">Awaiting NGO Take</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    <?php if(!empty($row['volunteer_name'])): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span><?= htmlspecialchars($row['volunteer_name']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 font-normal italic">Not Dispatched</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wider shadow-sm <?= $badge ?>">
                                        <span class="w-1 h-1 rounded-full <?= $dot ?>"></span>
                                        <span class="mr-0.5"><?= $icon ?></span>
                                        <span><?= $statusText ?></span>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400 font-medium whitespace-nowrap">
                                    <?= !empty($row['created_at']) ? date("M d, Y • h:i A", strtotime($row['created_at'])) : 'N/A' ?>
                                </td>
                            </tr>
                        <?php } 
                        
                        // Show message if no active records found
                        if (!$hasActiveRecords) { ?>
                            <tr>
                                <td colspan="9" class="p-12 text-center">
                                    <div class="w-12 h-12 bg-emerald-50 text-xl flex items-center justify-center rounded-full mx-auto mb-3 border border-emerald-100">✅</div>
                                    <h3 class="text-sm font-bold text-emerald-700">All Donations Delivered!</h3>
                                    <p class="text-xs text-slate-400 max-w-xs mx-auto mt-0.5">All donations have been successfully delivered. No active dispatches at the moment.</p>
                                </td>
                            </tr>
                        <?php }
                        
                    } else { ?>
                        <tr>
                            <td colspan="9" class="p-12 text-center">
                                <div class="w-12 h-12 bg-slate-50 text-xl flex items-center justify-center rounded-full mx-auto mb-3 border border-slate-100">🗂️</div>
                                <h3 class="text-sm font-bold text-slate-700">No Historical Dispatches Found</h3>
                                <p class="text-xs text-slate-400 max-w-xs mx-auto mt-0.5">Your donation registry is completely empty. Hit the dashboard to create a record.</p>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>