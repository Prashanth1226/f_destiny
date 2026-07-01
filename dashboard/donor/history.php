<?php
session_start();
$conn = new mysqli("localhost", "root", "", "f_destiny");

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

$email = $_SESSION['user'];

$user = $conn->query("SELECT * FROM users WHERE email='$email'");
$donor = $user->fetch_assoc();

$donor_id = $donor['id'];

/* GET DELIVERED DONOR HISTORY ONLY */
$result = $conn->query("
    SELECT
        d.*,
        ngo.name AS ngo_name,
        v.name AS volunteer_name
    FROM donations d

    LEFT JOIN users ngo ON d.ngo_id = ngo.id
    LEFT JOIN users v ON d.volunteer_id = v.id

    WHERE d.donor_id = '$donor_id' 
      AND d.status = 'delivered'

    ORDER BY d.created_at DESC
");

$pageTitle = "Donation History";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glossy-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .glossy-header {
            background: rgba(16, 185, 129, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen text-slate-800 font-sans antialiased">

    <header class="glass-nav sticky top-0 z-50 border-b border-slate-200/60 shadow-sm px-6 py-3.5 flex justify-between items-center">
        <div class="flex items-center gap-3 select-none shrink-0">
            <span class="text-2xl drop-shadow-sm transform transition-transform duration-300 hover:rotate-12 inline-block">🍱</span>
            <span class="text-xl font-black text-emerald-600 tracking-tight uppercase bg-clip-text">F-Destiny</span>
        </div>
        
        <div class="hidden md:block text-center px-4">
            <p class="text-sm text-slate-700 font-semibold mt-0.5">Donor  Status</p>
        </div>

        <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 shrink-0">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 my-4">
        
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900">Your Donation History</h2>
                <p class="text-sm text-slate-500 mt-0.5">Real-time confirmations and supply line tracking records.</p>
            </div>
            <div class="flex items-center gap-2 self-start bg-emerald-50 border border-emerald-100 text-emerald-800 px-4 py-2 rounded-xl text-xs font-bold tracking-wide">
                🎉 Total Delivered: <span class="bg-emerald-600 text-white font-black px-2 py-0.5 rounded-md ml-1"><?= $result->num_rows ?></span>
            </div>
        </div>

        <div class="glossy-card border border-slate-200 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200 text-xs font-bold tracking-wider text-slate-500">
                            <th class="p-4 pl-6">Food Details</th>
                            <th class="p-4">Quantity </th>
                            <th class="p-4">Dropoff Address</th>
                            <th class="p-4">Assigned NGO</th>
                            <th class="p-4">Assgined Volunteer</th>
                            <th class="p-4"> Status</th>
                            <th class="p-4 pr-6 text-right">Date of Donation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-700">
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-400 font-semibold">
                                    <div class="text-3xl mb-2"></div>
                                    No delivered donation records logged under this profile yet.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while($row = $result->fetch_assoc()) { ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="p-4 pl-6">
                                    <div class="flex items-center gap-3">
                                        <span class="font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($row['food_name']) ?></span>
                                    </div>
                                </td>

                                <td class="p-4 text-slate-600 font-semibold font-mono">
                                    <?= htmlspecialchars($row['quantity']) ?> 
                                </td>

                                <td class="p-4 max-w-xs">
                                    <p class="truncate text-slate-600 font-normal" title="<?= htmlspecialchars($row['address'] ?? 'N/A') ?>">
                                        <?= htmlspecialchars($row['address'] ?? 'N/A') ?>
                                    </p>
                                </td>

                                <td class="p-4">
                                    <?php if($row['ngo_name']): ?>
                                        <div class="flex items-center gap-1.5 text-slate-900 font-semibold">
                                            <span class="text-xs"></span> <?= htmlspecialchars($row['ngo_name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex text-xs px-2.5 py-1 rounded-md bg-slate-100 text-slate-400 font-bold tracking-wide border border-slate-200/50">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4">
                                    <?php if($row['volunteer_name']): ?>
                                        <div class="flex items-center gap-1.5 text-slate-700">
                                            <span class="text-xs"></span> <?= htmlspecialchars($row['volunteer_name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs font-normal italic">Unassigned</span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Delivered
                                    </span>
                                </td>

                                <td class="p-4 pr-6 text-right text-xs font-mono font-bold text-slate-500">
                                    <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>