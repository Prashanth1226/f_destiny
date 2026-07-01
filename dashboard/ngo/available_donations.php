<?php
session_start();

require_once '../../middleware/auth.php';
checkRole('ngo');

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "f_destiny");
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$email = $_SESSION['user'];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDateTime(?string $value): string
{
    return $value ? date("d M Y, h:i A", strtotime($value)) : '-';
}

function getNgoId(mysqli $conn, string $email): int
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        die($conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $ngo = $result->fetch_assoc();
    $stmt->close();

    if (!$ngo) {
        die("Access Denied");
    }

    return (int) $ngo['id'];
}

$ngoId = getNgoId($conn,$email);

$highlight_id = isset($_GET['highlight']) ? (int) $_GET['highlight'] : 0;
$notif_id = isset($_GET['notif']) ? (int) $_GET['notif'] : 0;
$donation_taken = false;
$toast = null;

if ($highlight_id > 0) {
    $checkStmt = $conn->prepare("SELECT status FROM donations WHERE id = ? LIMIT 1");
    if ($checkStmt) {
        $checkStmt->bind_param("i", $highlight_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $donation = $checkResult->fetch_assoc();
            if (strtolower($donation['status']) !== 'pending') {
                $donation_taken = true;
            }
        } else {
            $donation_taken = true;
        }

        $checkStmt->close();
    }
}

if ($notif_id > 0) {
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
          AND user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $notif_id, $ngoId);
        $stmt->execute();
        $stmt->close();
    }
}

if (isset($_GET['accept'])) {

    $id = (int) $_GET['accept'];

    $check = $conn->prepare("SELECT status FROM donations WHERE id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$row || $row['status'] !== 'pending') {
        header("Location: available_donations.php?error=Already processed");
        exit();
    }

    $update = $conn->prepare("
    UPDATE donations
    SET
        status='accepted',
        ngo_id=?
    WHERE
        id=?
        AND status='pending'
    ");

    $update->bind_param("ii",$ngoId,$id);
    $update->execute();
    $update->close();

    header("Location: available_donations.php?msg=accepted");
    exit();
}

if (isset($_GET['reject'])) {
    $id = (int) $_GET['reject'];

    $check = $conn->prepare("SELECT status FROM donations WHERE id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $checkResult = $check->get_result();
    $row = $checkResult->fetch_assoc();
    $check->close();

    if (!$row || strtolower($row['status']) !== 'pending') {
        header("Location: available_donations.php?toast_type=error&toast_title=Already Processed&toast_message=" . urlencode("This donation is no longer pending."));
        exit();
    }

    $rejectStmt = $conn->prepare("
        UPDATE donations
        SET status = 'rejected'
        WHERE id = ? AND LOWER(status) = 'pending'
    ");
    if ($rejectStmt) {
        $rejectStmt->bind_param("i", $id);
        $rejectStmt->execute();

        if ($rejectStmt->affected_rows > 0) {
            header("Location: available_donations.php?toast_type=warning&toast_title=Donation Rejected&toast_message=" . urlencode("The donation has been rejected."));
        } else {
            header("Location: available_donations.php?toast_type=error&toast_title=Update Failed&toast_message=" . urlencode("The donation could not be updated."));
        }

        $rejectStmt->close();
        exit();
    }
}

if (isset($_GET['toast_type'], $_GET['toast_title'], $_GET['toast_message'])) {
    $toast = [
        'type' => $_GET['toast_type'],
        'title' => $_GET['toast_title'],
        'message' => $_GET['toast_message']
    ];
}

$donationsStmt = $conn->prepare("
SELECT
    d.*,

    u.name  AS donor_name,
    u.email AS donor_email,
    u.phone AS donor_phone

FROM donations d

LEFT JOIN users u
ON u.id = d.donor_id

WHERE d.status='pending'

ORDER BY d.created_at DESC
");

if (!$donationsStmt) {
    die($conn->error);
}

$donationsStmt->execute();
$donations = $donationsStmt->get_result();

$acceptedResult = $conn->query("SELECT COUNT(*) as total FROM donations WHERE LOWER(status) = 'accepted'");
$acceptedRow = $acceptedResult ? $acceptedResult->fetch_assoc() : ['total' => 0];
$total_accepted_count = $acceptedRow['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Available Donations</title>
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
            background-image: radial-gradient(circle, rgba(147, 51, 234, 0.12) 0%, transparent 70%);
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

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 30px) scale(1.1); }
            100% { transform: translate(-30px, 60px) scale(0.95); }
        }

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
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .toast-wrap {
            animation: toastIn .35s ease-out;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(-12px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
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
        <h1>Available Donations board</h1>
    </div>

    <a href="ngo_index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-purple-700 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a>
</header>

<main class="flex-1 max-w-7xl w-full mx-auto p-4 md:p-8 space-y-6 relative z-10">

    <?php if ($toast): ?>
        <div class="toast-wrap mx-auto max-w-2xl rounded-3xl border overflow-hidden shadow-2xl backdrop-blur-xl
            <?= $toast['type'] === 'success' ? 'bg-emerald-50/95 border-emerald-200' : ($toast['type'] === 'warning' ? 'bg-amber-50/95 border-amber-200' : 'bg-rose-50/95 border-rose-200') ?>">
            <div class="p-5 flex items-start gap-4">
                <div class="shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center
                    <?= $toast['type'] === 'success' ? 'bg-emerald-500 text-white' : ($toast['type'] === 'warning' ? 'bg-amber-500 text-white' : 'bg-rose-500 text-white') ?>">
                    <?php if ($toast['type'] === 'success'): ?>✓<?php elseif ($toast['type'] === 'warning'): ?>!<?php else: ?>×<?php endif; ?>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-extrabold text-slate-900"><?= e($toast['title']) ?></h3>
                    <p class="mt-1 text-sm text-slate-600"><?= e($toast['message']) ?></p>
                </div>
                <a href="available_donations.php" class="text-slate-400 hover:text-slate-700 text-lg leading-none">×</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($donation_taken): ?>
        <div class="mb-5 p-4 rounded-2xl border border-red-300 bg-red-50 text-red-700 font-semibold text-center shadow-sm">
            ⚠️ The donation has already been accepted by another NGO.
        </div>
    <?php endif; ?>

    <div class="glass-card rounded-3xl border border-white/80 overflow-hidden shadow-xl">
        <div class="p-6 border-b border-purple-100/60 bg-white/40 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 w-full">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Available Donations</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pending items awaiting your verification response.</p>
            </div>

            <div class="sm:ml-auto inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold text-purple-700 bg-purple-500/10 border border-purple-400/30 backdrop-blur-md shadow-sm self-end sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-purple-500 shadow-[0_0_8px_rgba(147,51,234,0.6)] animate-pulse"></span>
                <span>Total Donations Accepted:</span>
                <span class="font-black text-sm ml-0.5 text-purple-800"><?= (int) $total_accepted_count ?></span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-purple-50/50 text-[11px] font-bold uppercase tracking-wider text-purple-900/60 border-b border-purple-100/50">
                        <th class="p-4 pl-6">S.No</th>
                        <th class="p-4">Food</th>
                        <th class="p-4">Quantity</th>
                        <th class="p-4">Description</th>
                        <th class="p-4">Address</th>
                        <th class="p-4">Expiry Date</th>
                        <th class="p-4">Donor Name</th>
                        <th class="p-4">Phone</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 pr-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-100/40 text-sm font-medium text-slate-700">
                <?php if ($donations && $donations->num_rows > 0): ?>
                    <?php $serial_num = 1; ?>
                    <?php while ($row = $donations->fetch_assoc()): ?>
                        <?php
                        $badge_css = "bg-amber-500/10 text-amber-700 border-amber-500/20 animate-pulse";
                        $icon = "⏳";
                        $is_highlight = ($row['id'] == $highlight_id);
                        $row_class = $is_highlight ? 'bg-purple-100 animate-pulse border-l-4 border-purple-600' : 'hover:bg-white/50';
                        ?>
                        <tr class="<?= $row_class ?> transition-all duration-300">
                            <td class="p-4 pl-6 text-xs font-bold text-slate-400"><?= $serial_num++ ?></td>
                            <td class="p-4 font-bold text-slate-900"><?= e($row['food_name']) ?></td>
                            <td class="p-4 text-xs font-semibold"><span class="px-2 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-200"><?= e($row['quantity']) ?></span></td>
                            <td class="p-4 text-xs text-slate-500 max-w-xs truncate" title="<?= e($row['description']) ?>"><?= e($row['description']) ?></td>
                            <td class="p-4 text-xs text-slate-600 max-w-xs truncate" title="<?= e($row['address']) ?>"><?= e($row['address']) ?></td>
                            <td class="p-4 text-xs font-bold text-slate-700"><?= formatDateTime($row['expiry']) ?></td>
                            <td class="p-4 text-xs font-semibold text-slate-800"><?= e($row['donor_name'] ?? 'N/A') ?></td>
                            <td class="p-4 text-xs font-bold text-purple-600">
                                <?php if (!empty($row['donor_phone'])): ?>
                                    <a href="tel:<?= e($row['donor_phone']) ?>">
                                        <?= e($row['donor_phone']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-400">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs border rounded-full font-bold <?= $badge_css ?>">
                                    <span><?= $icon ?></span>
                                    <span class="uppercase tracking-wide text-[10px]">Pending</span>
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="inline-flex gap-2 justify-end w-full">
                                    <a href="available_donations.php?accept=<?= (int) $row['id'] ?>"
                                       class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl">
                                        ✓ Accept
                                    </a>

                                    <a href="available_donations.php?reject=<?= (int) $row['id'] ?>"
                                       class="bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl">
                                        ✖ Reject
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="p-12 text-center text-slate-400 font-medium">
                            No fresh or pending donation records found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
window.onload = function() {
    const el = document.querySelector('.bg-purple-100');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};
</script>
</body>
</html>
<?php
$donationsStmt->close();
$conn->close();
?>