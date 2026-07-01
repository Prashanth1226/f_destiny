<?php
session_start();

require_once '../../middleware/auth.php';
checkRole('donor');

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* Logged-in donor */
$email = $_SESSION['user'];

$stmt = $conn->prepare("
    SELECT id, name, email, phone
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Donor account not found.");
}

$donorId = (int)$user['id'];

/* Donation Details */
$stmt = $conn->prepare("
SELECT

d.*,

donor.name AS donor_name,
donor.email AS donor_email,
donor.phone AS donor_phone,

ngo.name AS ngo_name,
ngo.email AS ngo_email,
ngo.phone AS ngo_phone,

volunteer.name AS volunteer_name,
volunteer.email AS volunteer_email,
volunteer.phone AS volunteer_phone

FROM donations d

LEFT JOIN users donor
ON donor.id=d.donor_id

LEFT JOIN users ngo
ON ngo.id=d.ngo_id

LEFT JOIN users volunteer
ON volunteer.id=d.volunteer_id

WHERE d.donor_id=?

ORDER BY d.created_at DESC
");

$stmt->bind_param("i", $donorId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Active Tracking Registry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* DEEP FLOATING GLOW CANVAS */
        .animated-bg {
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }

        .blob {
            position: absolute;
            background-image: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, transparent 65%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
            animation: floatEngine 22s infinite alternate ease-in-out;
        }
        .blob-sub1 { width: 600px; height: 600px; top: -15%; right: -10%; animation-duration: 18s; }
        .blob-sub2 { width: 500px; height: 500px; bottom: 5%; left: -5%; background-image: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%); animation-duration: 25s; }

        @keyframes floatEngine {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-40px, 50px) scale(1.08); }
            100% { transform: translate(30px, -30px) scale(0.95); }
        }

        /* PREMIUM REFINED GLASS ELEMENTS */
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(16px) saturate(120%);
            -webkit-backdrop-filter: blur(16px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 4px 24px -4px rgba(15, 23, 42, 0.02), inset 0 1px 0 rgba(255, 255, 255, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-panel:hover {
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 16px 32px -6px rgba(15, 23, 42, 0.05);
        }

        .stakeholder-card {
            background: rgba(255, 255, 255, 0.45);
            border: 1px solid rgba(241, 245, 249, 0.9);
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .stakeholder-card:hover {
            background: #ffffff;
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-sub1"></div>
<div class="blob blob-sub2"></div>

<header class="glass-nav sticky top-0 z-50 border-b border-slate-200/60 shadow-sm px-6 py-3.5 flex justify-between items-center">
    <div class="flex items-center gap-3 select-none shrink-0">
        <span class="text-2xl drop-shadow-sm transform transition-transform duration-300 hover:rotate-12 inline-block">🍱</span>
        <span class="text-xl font-black text-emerald-600 tracking-tight uppercase bg-clip-text">F-Destiny</span>
    </div>
    
    <div class="hidden md:block text-center px-4">
        <h1 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest font-mono">Donations Interface</h1>
        <p class="text-sm text-slate-700 font-semibold mt-0.5">Donation Details  </p>
    </div>

    <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 shrink-0">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a>
</header>

<div class="flex flex-1 max-w-7xl w-full mx-auto p-4 md:p-6 gap-6 relative z-10">
    
    <main class="flex-1 space-y-6 min-w-0">
        
        <div class="glass-panel p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Donation Details</h2>
                <p class="text-xs text-slate-500 mt-0.5">Live Distribution Coordination and Operational Monitoring Records</p>
            </div>
            <div class="flex items-center gap-2 text-xs bg-emerald-50 text-emerald-700 border border-emerald-100 px-3 py-1.5 rounded-xl font-bold self-start sm:self-auto">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Ongoing Process Records
            </div>
        </div>

        <?php if($result->num_rows > 0): ?>
            <div class="grid gap-5">
            <?php while($row = $result->fetch_assoc()): ?>
                <?php
                switch(strtolower($row['status'])){
                    case 'accepted':
                        $badgeBg = "bg-blue-50/80 text-blue-700 border-blue-200/60";
                        $indicatorDot = "bg-blue-500";
                        break;
                    case 'assigned':
                        $badgeBg = "bg-purple-50/80 text-purple-700 border-purple-200/60";
                        $indicatorDot = "bg-purple-500";
                        break;
                    case 'in_transit':
                        $badgeBg = "bg-amber-50/80 text-amber-700 border-amber-200/60";
                        $indicatorDot = "bg-amber-500";
                        break;
                    case 'pending':
                    default:
                        $badgeBg = "bg-orange-50/80 text-orange-700 border-orange-200/60";
                        $indicatorDot = "bg-orange-500";
                        break;
                }
                ?>
                
                <div class="glass-panel p-5 md:p-6 rounded-2xl relative overflow-hidden group <?= strtolower($row['status']) === 'delivered' ? 'opacity-60 grayscale-[0.5] bg-slate-100/50' : '' ?>">

                    <div class="absolute left-0 top-0 bottom-0 w-1 class pointer-events-none transition-colors duration-300 <?= strpos($badgeBg, 'blue') !== false ? 'bg-blue-500' : (strpos($badgeBg, 'purple') !== false ? 'bg-purple-500' : (strpos($badgeBg, 'amber') !== false ? 'bg-amber-500' : 'bg-orange-500')) ?>"></div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-200/60">

                        <div class="flex items-center gap-2.5">

                            <span class="text-xl">📦</span>

                            <div>

                                <h2 class="text-base font-bold text-slate-900 <?= strtolower($row['status']) === 'delivered' ? 'text-slate-500' : 'group-hover:text-emerald-700' ?> transition duration-150">

                                    <?= htmlspecialchars($row['food_name']) ?>

                                </h2>

                                <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">

                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>

                                    Uploaded: <?= date("M d, Y • h:i A", strtotime($row['created_at'])) ?>

                                </p>

                            </div>

                        </div>

                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border <?= $badgeBg ?> self-start sm:self-auto shadow-sm">

                            <span class="w-1.5 h-1.5 rounded-full <?= $indicatorDot ?>"></span>

                            <?= str_replace('_', ' ', $row['status']) ?>

                        </span>

                    </div>

                    <div class="grid md:grid-cols-3 gap-4 my-5">
                        
                        <div class="stakeholder-card p-3.5 rounded-xl <?= strtolower($row['status']) === 'delivered' ? 'opacity-60' : '' ?>">

                            <div class="flex items-center gap-2 mb-2">

                                <div class="w-6 h-6 rounded-lg bg-emerald-50 flex items-center justify-center text-xs">🧑‍🌾</div>

                                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700"> Donor</h3>

                            </div>

                            <div class="space-y-1 text-xs">

                                <p class="text-slate-500 font-medium">Name: <span class="text-slate-800 font-semibold"><?= htmlspecialchars($row['donor_name'] ?? 'N/A') ?></span></p>

                                <p class="text-slate-500 font-medium truncate">Email: <span class="text-slate-800 font-normal"><?= htmlspecialchars($row['donor_email'] ?? 'N/A') ?></span></p>

                            </div>

                        </div>

                        <div class="stakeholder-card p-3.5 rounded-xl <?= strtolower($row['status']) === 'delivered' ? 'opacity-60' : '' ?>">

                            <div class="flex items-center gap-2 mb-2">

                                <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center text-xs">🏢</div>

                                <h3 class="text-xs font-bold uppercase tracking-wider text-blue-700">Allocated NGO</h3>

                            </div>

                            <div class="space-y-1 text-xs">

                                <p class="text-slate-500 font-medium">Name: <span class="text-slate-800 font-semibold"><?= htmlspecialchars($row['ngo_name'] ?? 'Unassigned Slot') ?></span></p>

                                <p class="text-slate-500 font-medium truncate">Email: <span class="text-slate-700 font-normal"><?= htmlspecialchars($row['ngo_email'] ?? '-') ?></span></p>

                            </div>

                        </div>

                        <div class="stakeholder-card p-3.5 rounded-xl <?= strtolower($row['status']) === 'delivered' ? 'opacity-60' : '' ?>">

                            <div class="flex items-center gap-2 mb-2">

                                <div class="w-6 h-6 rounded-lg bg-purple-50 flex items-center justify-center text-xs">🚴</div>

                                <h3 class="text-xs font-bold uppercase tracking-wider text-purple-700"> Volunteer</h3>

                            </div>

                            <div class="space-y-1 text-xs">

                                <p class="text-slate-500 font-medium">Name: <span class="text-slate-800 font-semibold"><?= htmlspecialchars($row['volunteer_name'] ?? 'Awaiting Courier') ?></span></p>

                                <p class="text-slate-500 font-medium truncate">Email: <span class="text-slate-700 font-normal"><?= htmlspecialchars($row['volunteer_email'] ?? '-') ?></span></p>

                            </div>

                        </div>

                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-3.5 bg-slate-50/70 border border-slate-100 rounded-xl text-xs font-medium text-slate-600 mb-4 <?= strtolower($row['status']) === 'delivered' ? 'opacity-50' : '' ?>">

                        <div>

                            <span class="text-slate-400 font-normal block text-[10px] uppercase tracking-wider mb-0.5">Quantity</span>

                            <span class="text-slate-900 font-semibold text-sm"><?= htmlspecialchars($row['quantity']) ?></span>

                        </div>

                        <div>

                            <span class="text-slate-400 font-normal block text-[10px] uppercase tracking-wider mb-0.5"> Expiry Date </span>

                            <span class="text-rose-600 font-semibold text-sm <?= strtolower($row['status']) === 'delivered' ? 'text-slate-500' : '' ?>"><?= htmlspecialchars($row['expiry']) ?></span>

                        </div>

                        <div class="col-span-2 sm:col-span-1">

                            <span class="text-slate-400 font-normal block text-[10px] uppercase tracking-wider mb-0.5"> Address</span>

                            <span class="text-slate-900 font-medium truncate block max-w-xs <?= strtolower($row['status']) === 'delivered' ? 'text-slate-500' : '' ?>" title="<?= htmlspecialchars($row['address']) ?>"><?= htmlspecialchars($row['address']) ?></span>

                        </div>

                    </div>

                    <?php if(!empty($row['description'])): ?>

                    <div class="text-xs <?= strtolower($row['status']) === 'delivered' ? 'opacity-50' : '' ?>">

                        <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-semibold mb-1">Food Information</span>

                        <div class="bg-white/80 p-3 rounded-xl border border-slate-200/40 text-slate-700 leading-relaxed font-medium">

                            <?= nl2br(htmlspecialchars($row['description'])) ?>

                        </div>

                    </div>

                    <?php endif; ?>

                </div>
            <?php endwhile; ?>
            </div>

        <?php else: ?>
            <div class="glass-panel p-12 rounded-2xl text-center max-w-xl mx-auto border border-dashed border-slate-300">
                <div class="w-16 h-16 bg-slate-100 text-2xl flex items-center justify-center rounded-full mx-auto mb-4">🗂️</div>
                <h2 class="text-base font-bold text-slate-800">No Resource Distribution Activities in Progress</h2>
                <p class="text-xs text-slate-400 max-w-xs mx-auto mt-1">All distribution assignments have either been completed or are awaiting processing..</p>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>