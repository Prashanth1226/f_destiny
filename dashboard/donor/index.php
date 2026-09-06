<?php
session_start();
require_once '../../middleware/auth.php';

checkRole('donor');

$success_msg = "";
$error_msg = "";

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ---------------- DB CONNECTION ---------------- */
$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

/* ---------------- GET USER ---------------- */
$email = $_SESSION['user'];

$user_stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$result = $user_stmt->get_result();

if (!$result || $result->num_rows == 0) {
    die("User not found");
}

$donor = $result->fetch_assoc();
$donor_id = $donor['id'];
$donor_name = $donor['name'];

/* ---------------- HANDLE AJAX DONATION SUBMIT ---------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {

    $food_name = trim($_POST['food_name']);
    $quantity = trim($_POST['quantity']);
    $description = trim($_POST['description']);
    $address = trim($_POST['location']);
    $expiry = $_POST['expiry'];

    $insert_stmt = $conn->prepare("
        INSERT INTO donations
        (donor_id, food_name, quantity, description, address, expiry, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $insert_stmt->bind_param(
        "isssss",
        $donor_id,
        $food_name,
        $quantity,
        $description,
        $address,
        $expiry
    );

    if ($insert_stmt->execute()) {

        $donation_id = $insert_stmt->insert_id;
        $log_msg = "Donation created";

        $log_stmt = $conn->prepare("
            INSERT INTO activity_logs
            (donation_id, user_id, role, action, message)
            VALUES (?, ?, 'donor', 'created', ?)
        ");

        $log_stmt->bind_param(
            "iis",
            $donation_id,
            $donor_id,
            $log_msg
        );

        $log_stmt->execute();

        $message = "A new donation " . $food_name . " was uploaded by donor " . $donor_name . ".";

        $ngoResult = $conn->query(
            "SELECT user_id FROM user_roles WHERE role='ngo'"
        );

        $notify_stmt = $conn->prepare("
            INSERT INTO notifications
            (user_id, role, message, donation_id)
            VALUES (?, 'ngo', ?, ?)
        ");

        while ($ngo = $ngoResult->fetch_assoc()) {
            $ngo_id = $ngo['user_id'];
            $notify_stmt->bind_param(
                "isi",
                $ngo_id,
                $message,
                $donation_id
            );
            $notify_stmt->execute();
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Donation submitted successfully!'
        ]);

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $conn->error
        ]);
    }

    exit();
}

/* ---------------- ANALYTICS DATA ---------------- */
function getCount($conn, $d_id, $status = null) {
    if ($status === null) {
        $st = $conn->prepare("SELECT COUNT(*) AS total FROM donations WHERE donor_id = ?");
        $st->bind_param("i", $d_id);
    } else {
        $st = $conn->prepare("SELECT COUNT(*) AS total FROM donations WHERE donor_id = ? AND status = ?");
        $st->bind_param("is", $d_id, $status);
    }
    $st->execute();
    return $st->get_result()->fetch_assoc()['total'];
}

$totalCount     = getCount($conn, $donor_id);
$pendingCount   = getCount($conn, $donor_id, 'pending');
$acceptedCount  = getCount($conn, $donor_id, 'accepted');
$assignedCount  = getCount($conn, $donor_id, 'assigned');
$deliveredCount = getCount($conn, $donor_id, 'delivered');
$rejectedCount  = getCount($conn, $donor_id, 'rejected');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Premium Donor Operations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* REALISTIC DRIFTING BLUR BACKDROP BLOB ENGINE */
        .animated-bg {
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }

        .blob {
            position: absolute;
            background-image: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround 25s infinite alternate ease-in-out;
        }
        
        .blob-1 { width: 500px; height: 500px; top: -10%; left: -10%; animation-duration: 20s; }
        .blob-2 { width: 600px; height: 600px; bottom: 10%; right: -5%; background-image: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, transparent 70%); animation-duration: 28s; animation-delay: -5s; }
        .blob-3 { width: 450px; height: 450px; top: 40%; left: 30%; background-image: radial-gradient(circle, rgba(139, 92, 246, 0.08) 0%, transparent 70%); animation-duration: 22s; animation-delay: -2s; }

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 30px) scale(1.1); }
            100% { transform: translate(-30px, 60px) scale(0.95); }
        }

        /* ULTRACLEAN REAL GLASSMORPHISM */
        .glass-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(16px) saturate(120%);
            -webkit-backdrop-filter: blur(16px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 4px 24px -4px rgba(15, 23, 42, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.75);
            box-shadow: 0 16px 32px -8px rgba(15, 23, 42, 0.06);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.2s ease;
        }
        .glass-input:focus {
            background: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            outline: none;
        }

        /* PREMIUM ACTIVE STATE GLOW FOR NAV ITEMS */
        .sidebar-link-active {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 14px -2px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<div id="runtimeNotificationContainer" class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] flex flex-col gap-3 pointer-events-none"></div>

<?php if (isset($_SESSION['success_msg']) || isset($_SESSION['error_msg'])): ?>
    <?php 
        $msg = $_SESSION['success_msg'] ?? $_SESSION['error_msg'];
        $isSuccess = isset($_SESSION['success_msg']);
    ?>
    <div id="toastNotification" class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl transition-all duration-500 transform translate-y-0 bg-slate-900/95 backdrop-blur-xl text-white font-medium text-sm border border-white/10">
        <span class="flex items-center gap-2.5">
            <span class="h-2 w-2 rounded-full <?= $isSuccess ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]' : 'bg-rose-400 shadow-[0_0_8px_rgba(251,113,133,0.5)]' ?>"></span>
            <?= htmlspecialchars($msg) ?>
        </span>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('toastNotification');
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, -15px) scale(0.95)';
                setTimeout(() => toast.remove(), 400);
            }
        }, 4000);
    </script>
    <?php unset($_SESSION['success_msg'], $_SESSION['error_msg']); ?>
<?php endif; ?>

<header class="glass-header sticky top-0 z-50 border-b border-slate-200/60 shadow-sm px-6 py-3.5 flex justify-between items-center">
    <div class="flex items-center gap-2 text-2xl font-black text-emerald-600 tracking-tight">
        <span class="scale-110 inline-block">🍱</span> F-Destiny
    </div>
    
    <div class="hidden md:block text-center">
        <h1 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Donor Dashboard</h1>
        <p class="text-sm text-slate-600 mt-0.5 font-medium">Donor: <span class="text-slate-900 font-bold"><?= htmlspecialchars($donor_name) ?></span></p>
    </div>

    <div class="flex items-center gap-2">
        <a href="donor_profile.php" class="px-3.5 py-2 text-sm font-bold text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 backdrop-blur-md transition rounded-lg shadow-sm">Profile</a>
        <a href="/f_destiny/logout.php" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-2 rounded-xl text-sm font-semibold border border-rose-100 transition duration-200">Logout</a>
    </div>
</header>

<div class="flex flex-col lg:flex-row flex-1 w-full mx-auto p-4 md:p-6 gap-6 relative z-10 max-w-[1600px]">
    
    <aside class="w-full lg:w-64 shrink-0 space-y-1">
        <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Operational Segments</div>
        
        <div class="flex flex-col sm:flex-row lg:flex-col gap-1 sm:flex-wrap lg:flex-nowrap">
            <a  class="sidebar-link-active flex-1 sm:flex-initial flex items-center gap-3 px-4 py-2.5 rounded-xl text-white font-medium text-sm transition">
                <svg class="w-4 h-4 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V16zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V16z"/></svg>
                MENU 
            </a>

            <a href="donation_details.php" class="flex-1 sm:flex-initial flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-600 hover:bg-white hover:text-emerald-600 border border-transparent hover:border-slate-200/50 transition duration-150 font-medium text-sm">
                <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Donation Details
            </a>
            
            <a href="donation_map.php" class="flex-1 sm:flex-initial flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-600 hover:bg-white hover:text-emerald-600 border border-transparent hover:border-slate-200/50 transition duration-150 font-medium text-sm">
                <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.244a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Donation Map
            </a>
            
            <a href="history.php" class="flex-1 sm:flex-initial flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-600 hover:bg-white hover:text-emerald-600 border border-transparent hover:border-slate-200/50 transition duration-150 font-medium text-sm">
                <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                History 
            </a>
        </div>
    </aside>

    <main class="flex-1 space-y-6 min-w-0">
        
        <div class="glass-card p-6 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">
                    Welcome 👋, <?= htmlspecialchars($donor['name'] ?? 'Donor'); ?>(Donor)
                </h2>
                <p class="text-xs text-slate-500 mt-1 max-w-xl font-medium">
                    Instantly share delivery information with connected community kitchens and transport partners across the city.
                </p>
            </div>
            
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold text-emerald-700 bg-emerald-500/10 border border-emerald-400/30 backdrop-blur-md shadow-sm self-start sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(147,51,234,0.6)] animate-pulse"></span>
                <span>Active </span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="glass-card p-4 rounded-xl text-center">
                <h3 class="text-2xl font-black text-slate-900"><?= $totalCount ?></h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Total Donations</p>
            </div>
            <div class="glass-card p-4 rounded-xl text-center border-b-2 border-b-amber-500">
                <h3 class="text-2xl font-black text-amber-500"><?= $pendingCount ?></h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Pending Donations</p>
            </div>
            <div class="glass-card p-4 rounded-xl text-center border-b-2 border-b-blue-500">
                <h3 class="text-2xl font-black text-blue-500"><?= $acceptedCount ?></h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">NGO's Accepted</p>
            </div>
            <div class="glass-card p-4 rounded-xl text-center border-b-2 border-b-purple-500">
                <h3 class="text-2xl font-black text-purple-500"><?= $assignedCount ?></h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">In Transit</p>
            </div>
            <div class="glass-card p-4 rounded-xl text-center border-b-2 border-b-emerald-500">
                <h3 class="text-2xl font-black text-emerald-600"><?= $deliveredCount ?></h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Delivered</p>
            </div>
            <div class="glass-card p-4 rounded-xl text-center border-b-2 border-b-rose-500">
                <h3 class="text-2xl font-black text-rose-500"><?= $rejectedCount ?></h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Rejected</p>
            </div>
        </div>

        <div class="glass-card p-6 md:p-8 rounded-2xl max-w-3xl relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-emerald-500 to-emerald-600"></div>
            
            <div class="mb-6">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight">Donation Table</h3>
                <p class="text-xs text-slate-500 mt-0.5">Your submission immediately sends updates to all regional partner organizations.</p>
            </div>

            <form id="donationForm" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Food Name</label>
                        <input type="text" name="food_name" placeholder="Name of the food item" required 
                            class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Quantity</label>
                        <input type="text" name="quantity" placeholder="e.g., 10 kgs / 50 packages" required 
                            class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Details</label>
                    <textarea name="description" placeholder="Food Information..." rows="3"
                        class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium resize-none"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Pickup Location</label>
                        <input type="text" name="location" placeholder="Street, landmark or area..." required 
                            class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Expiry Date</label>
                        <input type="date" name="expiry" required 
                            class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600">
                    </div>
                </div>

                <div class="pt-4">
                    <button id="submitBtn" type="submit"
                        class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-md shadow-emerald-600/10 hover:shadow-xl hover:shadow-emerald-500/20 transform hover:-translate-y-0.5 transition duration-200 text-sm tracking-wide uppercase">
                        Submit Donation
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<script>
document.getElementById("donationForm").addEventListener("submit", function(e){

    e.preventDefault();

    const form = this;
    const btn = document.getElementById("submitBtn");
    const container = document.getElementById("runtimeNotificationContainer");

    btn.disabled = true;
    btn.innerHTML = "Submitting...";

    let formData = new FormData(form);
    formData.append("ajax", "1");

    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        const dynamicToast = document.createElement("div");
        dynamicToast.className = "flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-2xl transition-all duration-500 transform translate-y-4 opacity-0 bg-slate-900/95 backdrop-blur-xl text-white font-medium text-sm border border-white/10 pointer-events-auto";
        
        if(data.status === "success"){
            dynamicToast.innerHTML = `<span class="flex items-center gap-2.5"><span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]"></span>${data.message}</span>`;
            form.reset();
        } else {
            dynamicToast.innerHTML = `<span class="flex items-center gap-2.5"><span class="h-2 w-2 rounded-full bg-rose-400 shadow-[0_0_8px_rgba(251,113,133,0.5)]"></span>Error: ${data.message}</span>`;
        }

        container.appendChild(dynamicToast);
        
        setTimeout(() => {
            dynamicToast.classList.remove('translate-y-4', 'opacity-0');
        }, 50);

        setTimeout(() => {
            dynamicToast.classList.add('opacity-0', '-translate-y-2');
            setTimeout(() => dynamicToast.remove(), 500);
        }, 4000);

        btn.disabled = false;
        btn.innerHTML = "Submit Donation";

    })
    .catch(err => {
        const fallbackToast = document.createElement("div");
        fallbackToast.className = "flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-2xl transition-all duration-500 transform bg-slate-900/95 backdrop-blur-xl text-white font-medium text-sm border border-white/10 pointer-events-auto";
        fallbackToast.innerHTML = `<span class="flex items-center gap-2.5"><span class="h-2 w-2 rounded-full bg-rose-400"></span>Something went wrong.</span>`;
        container.appendChild(fallbackToast);
        
        setTimeout(() => {
            fallbackToast.classList.add('opacity-0');
            setTimeout(() => fallbackToast.remove(), 500);
        }, 4000);

        btn.disabled = false;
        btn.innerHTML = "Submit Donation";
    });
});
</script>

</body>
</html>