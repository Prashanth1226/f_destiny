<?php
session_start();

require_once '../../middleware/auth.php';
checkRole('ngo');

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

/* ---------------- DATABASE CONNECTION ---------------- */
$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* ---------------- HELPERS ---------------- */

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDateTime(?string $value): string {
    return $value ? date("d M Y, h:i A", strtotime($value)) : '-';
}

/* ---------------- GET NGO ID ---------------- */

function getNgoId(mysqli $conn, string $email): int
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user) {
        die("NGO account not found.");
    }

    return (int)$user['id'];
}

/* ---------------- FETCH ACCEPTED DONATIONS ---------------- */

function fetchAcceptedDonationRecords(mysqli $conn, int $ngoId)
{
    $stmt = $conn->prepare("
        SELECT

        d.*,

        u.name AS donor_name,
        u.email AS donor_email,
        u.phone AS donor_phone

        FROM donations d

        LEFT JOIN users u
        ON u.id=d.donor_id

        WHERE

        d.ngo_id=?

        AND (d.status='accepted' OR d.status='pending_volunteer')

        ORDER BY 
            CASE 
                WHEN d.status='accepted' THEN 0 
                ELSE 1 
            END,
            d.created_at DESC
    ");

    $stmt->bind_param("i", $ngoId);
    $stmt->execute();

    return $stmt->get_result();
}

/* ---------------- MAIN LOGIC ---------------- */

$email = $_SESSION['user'];

$ngoId = getNgoId($conn, $email);

/* =========================================================
   SEND ACCEPTED DONATION → VOLUNTEER POOL
========================================================= */

if (isset($_GET['send'])) {

    $donation_id = (int)$_GET['send'];

    if ($donation_id <= 0) {
        header("Location: accepted_donations.php?msg=send_failed");
        exit();
    }

    try {

        $conn->begin_transaction();

        /* -----------------------------------------
           Move donation into volunteer pool
        ----------------------------------------- */

        $stmt = $conn->prepare("
            UPDATE donations
            SET
                status = 'pending_volunteer',
                volunteer_id = NULL
            WHERE id = ?
              AND ngo_id = ?
              AND status = 'accepted'
              AND expiry IS NOT NULL
              AND expiry > NOW()
        ");

        if (!$stmt) {
            throw new Exception(
                "Send donation query failed: " . $conn->error
            );
        }

        $stmt->bind_param(
            "ii",
            $donation_id,
            $ngoId
        );

        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();

            throw new Exception(
                "Donation could not be sent to the volunteer pool. It may already be sent or expired."
            );
        }

        $stmt->close();


        /* -----------------------------------------
           Get all volunteers
        ----------------------------------------- */

        $volunteerStmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE role = 'volunteer'
        ");

        if (!$volunteerStmt) {
            throw new Exception(
                "Volunteer query failed: " . $conn->error
            );
        }

        $volunteerStmt->execute();

        $volunteers = $volunteerStmt
            ->get_result();


        /* -----------------------------------------
           Create notification for every volunteer
        ----------------------------------------- */

        $notificationStmt = $conn->prepare("
            INSERT INTO notifications
                (
                    user_id,
                    donation_id,
                    message,
                    is_read,
                    created_at
                )
            VALUES
                (?, ?, ?, 0, NOW())
        ");

        if (!$notificationStmt) {
            $volunteerStmt->close();

            throw new Exception(
                "Notification query failed: " . $conn->error
            );
        }

        $message =
            "A new food donation is available for pickup.";

        while ($volunteer = $volunteers->fetch_assoc()) {

            $volunteer_id = (int)$volunteer['id'];

            $notificationStmt->bind_param(
                "iis",
                $volunteer_id,
                $donation_id,
                $message
            );

            $notificationStmt->execute();
        }

        $notificationStmt->close();
        $volunteerStmt->close();


        $conn->commit();

        header(
            "Location: accepted_donations.php?msg=sent_to_volunteers"
        );

        exit();

    } catch (Throwable $e) {

        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }

        header(
            "Location: accepted_donations.php?msg=send_failed"
        );

        exit();
    }
}

$accepted = fetchAcceptedDonationRecords($conn, $ngoId);
$totalAccepted = $accepted ? $accepted->num_rows : 0;

// Count accepted (not sent to volunteer) donations
$acceptedCount = 0;
$sentToVolunteerCount = 0;
if ($accepted) {
    $accepted->data_seek(0);
    while ($row = $accepted->fetch_assoc()) {
        if ($row['status'] === 'accepted') {
            $acceptedCount++;
        } else {
            $sentToVolunteerCount++;
        }
    }
    $accepted->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Donation Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        .animated-bg {
            background-color: #f6f3fa;
            position: relative;
            overflow-x: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround infinite alternate ease-in-out;
        }

        .blob-1 {
            width: 600px;
            height: 600px;
            top: -10%;
            left: -10%;
            background-image: radial-gradient(circle, rgba(147, 51, 234, 0.15) 0%, transparent 70%);
            animation-duration: 20s;
        }

        .blob-2 {
            width: 700px;
            height: 700px;
            bottom: -10%;
            right: -10%;
            background-image: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 70%);
            animation-duration: 25s;
        }

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 40px) scale(1.08); }
            100% { transform: translate(-20px, 50px) scale(0.95); }
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(30px) saturate(190%);
            -webkit-backdrop-filter: blur(30px) saturate(190%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(24px) saturate(140%);
            -webkit-backdrop-filter: blur(24px) saturate(140%);
            border: 1px solid rgba(255, 255, 255, 0.45);
            box-shadow:
                0 10px 40px -10px rgba(147, 51, 234, 0.04),
                inset 0 1px 2px rgba(255, 255, 255, 0.5);
        }

        .glass-panel-header {
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.2));
            backdrop-filter: blur(10px);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid;
        }

        .status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-accepted {
            background: #f0fdf4;
            color: #166534;
            border-color: #86efac;
        }

        .status-accepted .dot {
            background: #22c55e;
        }

        .status-pending_volunteer {
            background: #f1f5f9;
            color: #64748b;
            border-color: #cbd5e1;
        }

        .status-pending_volunteer .dot {
            background: #94a3b8;
        }

        .row-sent {
            opacity: 0.6;
            background: rgba(241, 245, 249, 0.5);
        }

        .row-sent:hover {
            opacity: 0.8;
            background: rgba(241, 245, 249, 0.8);
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
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col pb-12">

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

<main class="flex-1 max-w-7xl w-full mx-auto p-4 md:p-8 space-y-12 relative z-10">

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="fade-in p-4 rounded-xl border text-sm font-medium flex items-center gap-2
            <?= $_GET['msg'] === 'sent_to_volunteers' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>">
            <span class="text-lg"><?= $_GET['msg'] === 'sent_to_volunteers' ? '✅' : '❌' ?></span>
            <?= $_GET['msg'] === 'sent_to_volunteers' ? 'Donation sent to volunteer  successfully!' : 'Failed to send donation to volunteers. Please try again.' ?>
        </div>
    <?php endif; ?>

    <section class="glass-card rounded-3xl overflow-hidden shadow-2xl transition-all duration-300">
        <div class="p-6 glass-panel-header border-b border-white/40 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 w-full">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Donation Management</h2>
                <p class="text-xs font-medium text-slate-500 mt-1">Manage accepted donations and track those sent to volunteers.</p>
            </div>

            <div class="sm:ml-auto flex flex-wrap gap-3">
                <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl text-xs font-bold text-purple-700 bg-purple-500/10 border border-purple-500/20 shadow-inner">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)] animate-pulse"></span>
                    <span>Accepted:</span>
                    <span class="font-extrabold text-base text-purple-800"><?= $acceptedCount ?></span>
                </div>
                <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100/80 border border-slate-200/50 shadow-inner">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <span>Sent to Volunteer:</span>
                    <span class="font-extrabold text-base text-slate-700"><?= $sentToVolunteerCount ?></span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-r from-purple-50/40 to-transparent text-[11px] uppercase font-bold text-slate-500 border-b border-purple-100/40 tracking-wider">
                        <th class="py-4 px-6">S.No</th>
                        <th class="py-4 px-4">Food Item</th>
                        <th class="py-4 px-4">Qty</th>
                        <th class="py-4 px-4">Pickup Address</th>
                        <th class="py-4 px-4">Expiry Time</th>
                        <th class="py-4 px-4">Donor</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/30 text-sm">
                    <?php if ($accepted && $accepted->num_rows > 0): ?>
                        <?php $serial = 1; ?>
                        <?php while ($row = $accepted->fetch_assoc()): 
                            $isSentToVolunteer = $row['status'] === 'pending_volunteer';
                        ?>
                            <tr class="hover:bg-white/60 transition-all duration-200 group <?= $isSentToVolunteer ? 'row-sent' : '' ?>">
                                <td class="py-4 px-6 text-slate-400 font-medium"><?= $serial++ ?></td>
                                <td class="py-4 px-4 font-bold <?= $isSentToVolunteer ? 'text-slate-500' : 'text-slate-900 group-hover:text-purple-700' ?> transition-colors">
                                    <?= e($row['food_name']) ?>
                                    <?php if ($isSentToVolunteer): ?>
                                        <span class="ml-2 text-[10px] font-normal text-slate-400">(Sent)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="bg-white/70 px-2 py-1 rounded-md border border-slate-200/40 font-semibold <?= $isSentToVolunteer ? 'text-slate-400' : 'text-slate-700' ?>">
                                        <?= e($row['quantity']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 max-w-xs truncate <?= $isSentToVolunteer ? 'text-slate-400' : 'text-slate-600' ?>" title="<?= e($row['address']) ?>">
                                    <?= e($row['address']) ?>
                                </td>
                                <td class="py-4 px-4 <?= $isSentToVolunteer ? 'text-slate-400' : 'text-slate-600' ?> font-medium">
                                    <?= formatDateTime($row['expiry']) ?>
                                </td>
                                <td class="py-4 px-4 <?= $isSentToVolunteer ? 'text-slate-400' : 'text-slate-700' ?> font-semibold">
                                    <?= e($row['donor_name']) ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="status-badge <?= $isSentToVolunteer ? 'status-pending_volunteer' : 'status-accepted' ?>">
                                        <span class="dot"></span>
                                        <?= $isSentToVolunteer ? 'Sent to Volunteer' : 'Accepted' ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <?php if (!$isSentToVolunteer): ?>
                                        <a href="accepted_donations.php?send=<?= (int)$row['id']; ?>"
                                           class="inline-block bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-lg shadow-blue-600/20 hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5">
                                            🚀 Send to Volunteer
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-block px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200/50 cursor-not-allowed">
                                            ✅ Already Sent
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-14 px-6 text-slate-400 font-medium">
                                <div class="text-3xl mb-3">📭</div>
                                <p class="font-bold text-slate-600">No accepted donations found</p>
                                <p class="text-xs mt-1">Donations that you accept will appear here.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
// Auto-hide messages after 5 seconds
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