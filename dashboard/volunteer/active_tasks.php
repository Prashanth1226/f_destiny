<?php
/**
 * F-Destiny - Volunteer Ongoing Distribution Matrix
 * File: active_manifest.php
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
    die("Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$email = $_SESSION['user'];

/* =====================================================
   VOLUNTEER DETAILS (PREPARED)
===================================================== */
$v_stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$v_stmt->bind_param("s", $email);
$v_stmt->execute();
$volunteer = $v_stmt->get_result()->fetch_assoc();
$v_stmt->close();

if (!$volunteer) {
    die("Volunteer account not found.");
}

$volunteer_id = $volunteer['id'];

/* =====================================================
   FETCH ACTIVE DELIVERIES WITH ARCHITECTURAL FALLBACK
===================================================== */
// Try the advanced relational join query first
$query = "
    SELECT d.*, 
           u.name AS donor_name, u.phone AS donor_phone,
           r.name AS receiver_name, r.phone AS receiver_phone, r.address AS destination_address
    FROM donations d
    LEFT JOIN users u ON d.donor_id = u.id
    LEFT JOIN users r ON d.receiver_id = r.id
    WHERE d.volunteer_id = ?
    AND d.status = 'assigned'
    ORDER BY d.id DESC
";

$stmt = $conn->prepare($query);

// Fallback mechanism: If the advanced columns do not exist in your database layout yet
if ($stmt === false) {
    // This fallback ensures the code won't crash even if donor_id/receiver_id are missing
    $fallback_query = "
        SELECT *
        FROM donations
        WHERE volunteer_id = ?
        AND status = 'assigned'
        ORDER BY id DESC
    ";
    $stmt = $conn->prepare($fallback_query);
    
    if ($stmt === false) {
        // If even the basic table layout fails, print out the actual raw SQL error description
        die("Database Engine Matrix Failure: " . htmlspecialchars($conn->error));
    }
}

$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$active_res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Manifest | F-Destiny Hub</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        .glass-header {
            background: rgba(255, 255, 255, .85);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
        .glass-sidebar {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(226, 232, 240, 0.6);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.7);
        }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex flex-col">

<header class="glass-header sticky top-0 z-50 flex justify-between items-center px-6 py-4 shadow-sm">
    <div class="flex items-center gap-2 text-2xl font-black text-blue-600 tracking-tight">
        <span>🚚</span> F-Destiny
    </div>
    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block">
        <h1>Real-Time Tracking Dashboard</h1>
    </div>
    <a href="volunteer_index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a> 
    
</header>

<div class="flex flex-1">

    

    <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full space-y-6">

        <div class="border-b border-slate-200/60 pb-4">
            <h1 class="text-xl font-black text-slate-900 tracking-tight">
                Active Deliveries
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage deliveries, verify destinations, and update tracking records.</p>
        </div>

        <div class="space-y-4">
            <?php if ($active_res->num_rows === 0): ?>
                <div class="glass-card p-12 rounded-2xl text-center text-xs font-bold text-slate-400">
                    <span class="text-3xl block mb-2">🎯</span>
                    No active deliveries assigned to your tracking id.<br>
                    <span class="font-normal text-slate-400/80 block mt-1">Check the Available Tasks section to accept open assignments.</span>
                </div>
            <?php else: ?>
                <?php 
                $serial_num = 1; // Initialize serial number counter
                while ($row = $active_res->fetch_assoc()): 
                    $item_display_name = $row['food_name'] ?? $row['item_name'] ?? 'Unspecified Asset Allocation';
                ?>
                    <div class="glass-card p-6 rounded-2xl shadow-sm border border-slate-200/60 hover:border-blue-300/60 transition-all duration-300">
                        
                        <div class="flex flex-wrap justify-between items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black bg-slate-900 text-white px-2.5 py-1 rounded-lg">
                                    <?= $serial_num++ ?>
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">
                                    Assigned: <?= isset($row['created_at']) ? htmlspecialchars(date("M d, H:i", strtotime($row['created_at']))) : date("M d, H:i") ?>
                                </span>
                            </div>
                            <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/40 animate-pulse">
                                ⚡ <?= htmlspecialchars(str_replace('_', ' ', $row['status'])) ?>
                            </span>
                        </div>

                        <div class="grid md:grid-cols-3 gap-6 text-xs">
                            
                            <div class="space-y-2">
                                <h3 class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Cargo Description</h3>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3">
                                    <p class="font-black text-slate-900 text-sm"><?= htmlspecialchars($item_display_name) ?></p>
                                    <p class="text-slate-500 mt-1 font-medium">Quantity: <span class="text-blue-600 font-bold"><?= htmlspecialchars($row['quantity'] ?? '1') ?></span></p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <h3 class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Point of Origin (Donor)</h3>
                                <div class="space-y-1">
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($row['donor_name'] ?? 'System Pool Contributor') ?></p>
                                    <p class="text-slate-500 leading-relaxed"><span class="text-slate-400">📍</span> <?= htmlspecialchars($row['address'] ?? 'Primary Hub Address') ?></p>
                                    <?php if(!empty($row['donor_phone'])): ?>
                                        <p class="text-blue-600 font-semibold mt-1">📞 <?= htmlspecialchars($row['donor_phone']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <h3 class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Destination Vector (Recipient)</h3>
                                <div class="space-y-1">
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($row['receiver_name'] ?? 'Assigned Community Distribution Point') ?></p>
                                    <p class="text-slate-500 leading-relaxed"><span class="text-slate-400">🎯</span> <?= htmlspecialchars($row['destination_address'] ?? ($row['address'] . ' Delivery Zone')) ?></p>
                                    <?php if(!empty($row['receiver_phone'])): ?>
                                        <p class="text-indigo-600 font-semibold mt-1">📞 <?= htmlspecialchars($row['receiver_phone']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-3">
                            <div class="flex gap-2 w-full sm:w-auto">
                                
                                
                            </div>
                            
                            <a href="volunteer_index.php?action=complete&id=<?= (int)$row['id'] ?>" 
                               class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-extrabold px-6 py-2.5 rounded-xl transition shadow-md shadow-emerald-600/10 w-full sm:w-auto text-center block">
                                Mark as Delivered
                            </a>
                        </div>

                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<footer class="text-center py-6 text-xs font-bold text-slate-400 mt-auto border-t border-slate-100">
    &copy; <?= date('Y') ?> F-Destiny Hub <span class="mx-1.5">|</span> Distribution Control Center
</footer>

</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>