<?php
session_start();

require_once '../../middleware/auth.php';
checkRole('volunteer');

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("DB ERROR: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$email = $_SESSION['user'];

/* ================================
   GET VOLUNTEER ID
================================ */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Volunteer not found");
}

$volunteer_id = $user['id'];

if(isset($_GET['accept'])){

    $donationId=(int)$_GET['accept'];

    $stmt=$conn->prepare("
    UPDATE donations

    SET

        volunteer_id=?,
        status='assigned'

    WHERE

        id=?
        AND status='pending_volunteer'
    ");

    $stmt->execute();

    if($stmt->affected_rows > 0){

        $stmt->close();

        header("Location: available_tasks.php?success=accepted");

    }else{

        $stmt->close();

        header("Location: available_tasks.php?error=already_taken");

    }

    exit();
}

if(isset($_GET['start'])){

    $id=(int)$_GET['start'];

    $stmt=$conn->prepare("
    UPDATE donations

    SET status='in_transit'

    WHERE

    id=?

    AND volunteer_id=?
    ");

    $stmt->bind_param("ii",$id,$volunteer_id);

    $stmt->execute();

    $stmt->close();

    header("Location: active_task.php");

    exit();
}

if(isset($_GET['complete'])){

    $id=(int)$_GET['complete'];

    $stmt=$conn->prepare("
    UPDATE donations

    SET status='completed'

    WHERE

    id=?

    AND volunteer_id=?
    ");

    $stmt->bind_param("ii",$id,$volunteer_id);

    $stmt->execute();

    $stmt->close();

    header("Location: volunteer_index.php");

    exit();
}

/* ================================
   FIXED TASK QUERY (IMPORTANT)
================================ */
$task_stmt = $conn->prepare("
SELECT

    d.*,

    donor.name AS donor_name,
    donor.email AS donor_email,
    donor.phone AS donor_phone,

    ngo.name AS ngo_name,
    ngo.email AS ngo_email,
    ngo.phone AS ngo_phone

FROM donations d

LEFT JOIN users donor
ON donor.id = d.donor_id

LEFT JOIN users ngo
ON ngo.id = d.ngo_id

WHERE d.status = 'pending_volunteer'

ORDER BY d.created_at DESC
");

if (!$task_stmt) {
    die("Query Failed: " . $conn->error);
}

$task_stmt->execute();
$tasks = $task_stmt->get_result();

$available_count = $tasks->num_rows;
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Tasks Pool | F-Destiny Hub</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .animated-bg {
            background-color: #fcfaff;
            position: relative;
            overflow-x: hidden;
        }
        .blob {
            position: absolute;
            background-image: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(100px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround 25s infinite alternate ease-in-out;
        }
        .blob-1 { width: 600px; height: 600px; top: -10%; left: -5%; }

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 30px) scale(1.05); }
            100% { transform: translate(-20px, 50px) scale(0.95); }
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(24px) saturate(180%);
            border-b: 1px solid rgba(241, 245, 249, 0.6);
        }
        .glass-sidebar {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(226, 232, 240, 0.4);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(16px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 30px rgba(59, 130, 246, 0.01), inset 0 1px 1px rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-1"></div>

<header class="glass-header sticky top-0 z-50 flex justify-between items-center px-6 py-4 shadow-sm relative">
    <div class="flex items-center gap-2 text-2xl font-black text-blue-600 tracking-tight">
        <span>🚚</span> F-Destiny
    </div>
    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block">
        <h1>Available Tasks Board</h1>
    </div>

    <a href="volunteer_index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a> 
    
</header>

<div class="flex flex-1 relative z-10">

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'claimed_success'): ?>
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4 text-sm font-semibold">
            Task successfully assigned to you!
        </div>
    <?php endif; ?>

    

    <main class="flex-1 p-4 md:p-8 space-y-6 max-w-7xl mx-auto w-full">
        
        <div class="flex justify-between items-center border-b border-slate-200/40 pb-3">
            <div>
                <h2 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span></span> Distribution Records
                </h2>
                <p class="text-xs text-slate-400 font-medium">Claim available tasks below.</p>
            </div>
            <span class="text-[10px] font-bold px-2.5 py-1 text-blue-700 bg-blue-50 rounded-full border border-blue-200/40">
                <?= $available_count ?> Available Requests
            </span>

            
        </div>

        <?php if ($available_count > 0): ?>
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php while ($row = $tasks->fetch_assoc()): ?>
                    <div class="dashboard-card glass-card rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <h3 class="font-extrabold text-sm text-slate-900 tracking-tight">
                                        <?= htmlspecialchars($row['food_name'] ?? 'Unspecified Asset') ?>
                                    </h3>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Assignment History</p>
                                </div>
                                <span class="bg-blue-500/10 text-blue-700 text-[10px] px-2 py-0.5 rounded-full font-black border border-blue-500/20 uppercase tracking-wide">
                                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                </span>
                            </div>
                            
                            <div class="border-t border-slate-100 my-3.5"></div>
                            
                            <div class="space-y-2.5 text-xs">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100">🍱</span>
                                    <div>
                                        <p class="text-[9px] uppercase font-bold tracking-wider text-slate-400">Quantity </p>
                                        <p class="font-bold text-slate-700 mt-0.5"><?= htmlspecialchars($row['quantity'] ?? '1') ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100">👤</span>
                                    <div>
                                        <p class="text-[9px] uppercase font-bold tracking-wider text-slate-400">Donor Name</p>
                                        <p class="font-bold text-slate-700 mt-0.5"><?= htmlspecialchars($row['donor_name'] ?? 'Anonymous') ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100 mt-0.5">📍</span>
                                    <div>
                                        <p class="text-[9px] uppercase font-bold tracking-wider text-slate-400">Pickup Address</p>
                                        <p class="font-medium text-slate-600 leading-relaxed mt-0.5 text-[11px]"><?= htmlspecialchars($row['address'] ?? 'On File / Dynamic Location') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-5">
                            <a href="claim_task.php?id=<?= (int)$row['id'] ?>" class="block w-full text-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-2.5 rounded-xl text-xs font-bold transition shadow-md shadow-indigo-600/10">
                                Accept Request
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="glass-card rounded-2xl p-8 text-center max-w-sm mx-auto">
                <div class="text-3xl mb-2"></div>
                <h3 class="text-sm font-extrabold text-slate-700 tracking-tight">No Active Tasks</h3>
                <p class="text-slate-400 text-xs mt-0.5">No delivery tasks are available right now.</p>
            </div>
        <?php endif; ?>

        

    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".dashboard-card").forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(12px)";
        setTimeout(() => {
            card.style.transition = "all .6s cubic-bezier(0.16, 1, 0.3, 1)";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
        }, index * 40);
    });
});
</script>

</body>
</html>
<?php 
$task_stmt->close();
$conn->close(); 
?>