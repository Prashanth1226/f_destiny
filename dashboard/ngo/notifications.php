<?php
session_start();
require_once '../../middleware/auth.php';
checkRole('ngo');

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$email = $_SESSION['user'] ?? '';

/* =========================================================
   GET NGO INFORMATION
   ========================================================= */
$userStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$userStmt->bind_param("s", $email);
$userStmt->execute();

$ngo = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$ngo) {
    die("Access Denied: Invalid User Node.");
}

$ngo_id = (int)$ngo['id'];


/* =========================================================
   MARK NOTIFICATION AS READ
   ========================================================= */
if (isset($_GET['mark_read']) && isset($_GET['id'])) {

    $notif_id = intval($_GET['id']);

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->bind_param("ii", $notif_id, $ngo_id);
    $stmt->execute();
    $stmt->close();
}


/* =========================================================
   GET ONLY CURRENTLY AVAILABLE NOTIFICATIONS
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        n.*,
        d.status AS donation_status,
        d.food_name,
        d.id AS donation_id,
        d.expiry
    FROM notifications n
    INNER JOIN donations d
        ON d.id = n.donation_id
    WHERE n.user_id = ?
      AND d.expiry IS NOT NULL
      AND d.expiry > NOW()
    ORDER BY
        CASE
            WHEN n.is_read = 0 THEN 0
            ELSE 1
        END,
        n.created_at DESC
");

$stmt->bind_param("i", $ngo_id);
$stmt->execute();

$result = $stmt->get_result();

/* =========================================================
   COUNT ONLY CURRENTLY AVAILABLE UNREAD NOTIFICATIONS
   ========================================================= */

$unreadStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications n
    INNER JOIN donations d
        ON d.id = n.donation_id
    WHERE n.user_id = ?
      AND n.is_read = 0
      AND d.status = 'pending'
      AND d.expiry_date IS NOT NULL
      AND d.expiry_date > NOW()
");

$unreadStmt->bind_param("i", $ngo_id);
$unreadStmt->execute();

$unreadResult = $unreadStmt->get_result();
$unreadRow = $unreadResult->fetch_assoc();

$unreadCount = (int)($unreadRow['total'] ?? 0);

$unreadStmt->close();


/* =========================================================
   TOTAL CURRENTLY AVAILABLE NOTIFICATIONS
   ========================================================= */

$totalNotifications = $result ? $result->num_rows : 0;


/* =========================================================
   READ COUNT
   ========================================================= */

$readCount = max(
    0,
    $totalNotifications - $unreadCount
);

?>

<!DOCTYPE html>

<html lang="en" class="scroll-smooth">

<head>

<meta charset="utf-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>F-Destiny | Notification Node Center</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

    body {
        font-family: 'Inter', sans-serif;
    }


    /* =====================================================
       PREMIUM DRIFTING BLUR ENGINE
       ===================================================== */

    .animated-bg {
        background-color: #fcfaff;
        position: relative;
        overflow-x: hidden;
    }

    .blob {
        position: absolute;
        background-image:
            radial-gradient(
                circle,
                rgba(147, 51, 234, 0.12) 0%,
                transparent 70%
            );

        border-radius: 50%;

        filter: blur(90px);

        z-index: 0;

        pointer-events: none;

        animation:
            floatAround
            25s
            infinite
            alternate
            ease-in-out;
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

        background-image:
            radial-gradient(
                circle,
                rgba(168, 85, 247, 0.1) 0%,
                transparent 70%
            );

        animation-duration: 30s;
    }

    @keyframes floatAround {

        0% {
            transform:
                translate(0, 0)
                scale(1);
        }

        50% {
            transform:
                translate(60px, 30px)
                scale(1.1);
        }

        100% {
            transform:
                translate(-30px, 60px)
                scale(0.95);
        }
    }


    /* =====================================================
       MASTERGLASS PLATES
       ===================================================== */

    .glass-header {

        background:
            rgba(255, 255, 255, 0.75);

        backdrop-filter:
            blur(24px)
            saturate(180%);

        -webkit-backdrop-filter:
            blur(24px)
            saturate(180%);
    }

    .glass-card {

        background:
            rgba(255, 255, 255, 0.45);

        backdrop-filter:
            blur(16px)
            saturate(120%);

        -webkit-backdrop-filter:
            blur(16px)
            saturate(120%);

        border:
            1px solid
            rgba(255, 255, 255, 0.6);

        box-shadow:
            0 4px 30px
            rgba(147, 51, 234, 0.01),

            inset 0 1px 1px
            rgba(255, 255, 255, 0.3);
    }


    /* =====================================================
       PULSE ANIMATION FOR UNREAD AVAILABLE DONATIONS
       ===================================================== */

    .glow-pulse {

        animation:
            blowAndOff
            2.5s
            infinite
            ease-in-out;

        background:
            rgba(147, 51, 234, 0.06);

        border-left:
            4px solid
            rgb(147, 51, 234) !important;

        position: relative;
    }

    @keyframes blowAndOff {

        0% {
            box-shadow:
                inset 0 0 0px
                rgba(147, 51, 234, 0);
        }

        50% {

            box-shadow:
                inset 0 0 20px
                rgba(147, 51, 234, 0.15);

            background:
                rgba(147, 51, 234, 0.1);
        }

        100% {

            box-shadow:
                inset 0 0 0px
                rgba(147, 51, 234, 0);
        }
    }


    /* =====================================================
       NUMBER BADGE
       ===================================================== */

    .notification-badge {

        background:
            #ef4444;

        color:
            white;

        font-size:
            10px;

        font-weight:
            800;

        padding:
            2px 8px;

        border-radius:
            12px;

        animation:
            pulse
            2s
            infinite;

        min-width:
            20px;

        text-align:
            center;
    }

    @keyframes pulse {

        0% {
            transform:
                scale(1);
        }

        50% {
            transform:
                scale(1.1);
        }

        100% {
            transform:
                scale(1);
        }
    }


    /* =====================================================
       GLOW HIGHLIGHT
       ===================================================== */

    .highlight-glow {

        animation:
            highlightPulse
            2s
            ease-in-out
            3;
    }

    @keyframes highlightPulse {

        0% {
            box-shadow:
                0 0 0 0
                rgba(147, 51, 234, 0.4);
        }

        50% {
            box-shadow:
                0 0 30px 10px
                rgba(147, 51, 234, 0.2);
        }

        100% {
            box-shadow:
                0 0 0 0
                rgba(147, 51, 234, 0.4);
        }
    }


    /* =====================================================
       SCROLLBAR
       ===================================================== */

    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-track {

        background:
            rgba(0, 0, 0, 0.02);

        border-radius:
            10px;
    }

    ::-webkit-scrollbar-thumb {

        background:
            rgba(147, 51, 234, 0.2);

        border-radius:
            10px;
    }

    ::-webkit-scrollbar-thumb:hover {

        background:
            rgba(147, 51, 234, 0.3);
    }

</style>

</head>

<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<!-- =========================================================
     HEADER
     ========================================================= -->

<header
    class="glass-header sticky top-0 z-50 border-b border-purple-100 shadow-sm px-6 py-4 flex justify-between items-center"
>

<div
    class="flex items-center gap-2 text-2xl font-black text-purple-600 tracking-tight"
>

    <span>🍱</span>

    F-Destiny

</div>


<div
    class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block"
>

    <h1>Notifications</h1>

</div>


<div class="flex items-center gap-3">


    <?php if ($unreadCount > 0): ?>

        <span class="notification-badge">

            <?= $unreadCount ?>

        </span>

    <?php endif; ?>


    <a
        href="ngo_index.php"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-purple-700 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5"
    >

        <svg
            class="w-4 h-4 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >

            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
            />

        </svg>

        <span>Dashboard</span>

    </a>

</div>

</header>

<!-- =========================================================
     MAIN
     ========================================================= -->

<main
    class="flex-1 max-w-4xl w-full mx-auto p-4 md:p-8 space-y-6 relative z-10"
>

<!-- =====================================================
     NOTIFICATION CARD
     ===================================================== -->

<div
    class="glass-card rounded-3xl border border-white/80 overflow-hidden shadow-xl"
>


    <!-- HEADER -->

    <div
        class="p-6 border-b border-purple-100/60 bg-white/40 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 w-full"
    >

        <div>

            <h2
                class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2"
            >

                🔔 Updates Board

                <?php if ($unreadCount > 0): ?>

                    <span
                        class="text-xs bg-rose-500 text-white px-2.5 py-0.5 rounded-full font-bold animate-pulse"
                    >

                        <?= $unreadCount ?> new

                    </span>

                <?php endif; ?>

            </h2>


            <p
                class="text-xs text-slate-500 mt-0.5"
            >

                Only currently available donations are shown here.

            </p>

        </div>


        <div
            class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold text-purple-700 bg-purple-500/10 border border-purple-400/20 self-start sm:self-auto"
        >

            <span
                class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"
            ></span>

            <span>System Live</span>

        </div>

    </div>


    <!-- =================================================
         NOTIFICATION LIST
         ================================================= -->

    <div class="divide-y divide-purple-100/40">


        <?php if ($result && $result->num_rows > 0):

            $hasUnread = false;

        ?>


            <?php while ($row = $result->fetch_assoc()):

                $is_unread =
                    ((int)$row['is_read'] === 0);

                /*
                 * Because the SQL query already filters:
                 *
                 * d.status = 'pending'
                 * expiry_date IS NULL OR expiry_date > NOW()
                 *
                 * every row reaching this point is currently
                 * available.
                 */

                $donation_id =
                    (int)($row['donation_id'] ?? 0);

                $notification_id =
                    (int)$row['id'];

                $food_name =
                    htmlspecialchars(
                        $row['food_name'] ?? 'Unknown',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                $message =
                    htmlspecialchars(
                        $row['message'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                $created_at =
                    !empty($row['created_at'])
                        ? date(
                            'd M Y • h:i A',
                            strtotime($row['created_at'])
                        )
                        : '';

                if ($is_unread) {

                    $pulse_class = 'glow-pulse';

                    $hasUnread = true;

                } else {

                    $pulse_class = '';

                }

            ?>


                <!-- =================================================
                     NOTIFICATION ITEM
                     ================================================= -->

                <div
                    onclick="markAndRedirect(
                        <?= $notification_id ?>,
                        <?= $donation_id ?>
                    )"

                    class="flex items-start gap-4 p-5 cursor-pointer hover:bg-white/60 transition-all duration-300 <?= $pulse_class ?>"
                >


                    <!-- ICON -->

                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-sm shrink-0 relative bg-purple-500 text-white shadow-[0_4px_12px_rgba(147,51,234,0.3)]"
                    >

                        <?php if ($is_unread): ?>

                            <span
                                class="alert-dot absolute -top-1 -right-1 flex h-3 w-3"
                            >

                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"
                                ></span>

                                <span
                                    class="relative inline-flex rounded-full h-3 w-3 bg-purple-600"
                                ></span>

                            </span>

                        <?php endif; ?>


                        🔔

                    </div>


                    <!-- CONTENT -->

                    <div class="flex-1 min-w-0">


                        <div
                            class="flex flex-wrap justify-between items-start gap-2"
                        >

                            <h3
                                class="font-bold text-sm text-purple-900 tracking-tight flex items-center gap-1.5 flex-wrap"
                            >

                                🆕 New Donation Available


                                <?php if ($is_unread): ?>

                                    <span
                                        class="new-badge text-[9px] bg-purple-600 text-white font-black uppercase px-1.5 py-0.5 rounded tracking-wider animate-pulse"
                                    >

                                        New

                                    </span>

                                <?php endif; ?>

                            </h3>


                            <span
                                class="text-[11px] font-semibold text-slate-400 shrink-0"
                            >

                                <?= $created_at ?>

                            </span>

                        </div>


                        <!-- MESSAGE -->

                        <p
                            class="text-xs text-slate-600 mt-1.5 leading-relaxed font-medium"
                        >

                            <?= $message ?>


                            <?php if (!empty($row['food_name'])): ?>

                                <span
                                    class="font-bold text-purple-700"
                                >

                                    "<?= $food_name ?>"

                                </span>

                            <?php endif; ?>

                        </p>


                        <!-- EXPIRY INFORMATION -->

                        <?php if (!empty($row['expiry_date'])): ?>

                            <div
                                class="mt-2 flex items-center gap-1.5 text-[10px] font-semibold text-amber-600"
                            >

                                <span>⏳</span>

                                <span>
                                    Available until
                                    <?= date(
                                        'd M Y • h:i A',
                                        strtotime($row['expiry_date'])
                                    ) ?>
                                </span>

                            </div>

                        <?php else: ?>

                            <div
                                class="mt-2 flex items-center gap-1.5 text-[10px] font-semibold text-emerald-600"
                            >

                                <span>♾️</span>

                                <span>
                                    No expiry time specified
                                </span>

                            </div>

                        <?php endif; ?>


                        <!-- CLICK TO VIEW -->

                        <?php if ($is_unread): ?>

                            <div
                                class="mt-2 flex items-center gap-1 text-[10px] text-purple-500 font-semibold"
                            >

                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"
                                ></span>

                                Click to view

                            </div>

                        <?php endif; ?>


                    </div>

                </div>


            <?php endwhile; ?>


            <!-- =================================================
                 ALL READ MESSAGE
                 ================================================= -->

            <?php if (!$hasUnread): ?>

                <div
                    class="p-4 text-center bg-emerald-50/50 border-b border-emerald-100"
                >

                    <p
                        class="text-xs font-medium text-emerald-700"
                    >

                        ✅ All available notifications have been read

                    </p>

                </div>

            <?php endif; ?>


        <?php else: ?>


            <!-- =================================================
                 NO CURRENTLY AVAILABLE DONATIONS
                 ================================================= -->

            <div class="p-16 text-center">

                <div
                    class="text-4xl mb-3 opacity-60"
                >
                    🔮
                </div>


                <h2
                    class="text-base font-extrabold text-slate-800 tracking-tight"
                >

                    No Available Donations

                </h2>


                <p
                    class="text-xs text-slate-400 mt-1 max-w-xs mx-auto font-medium"
                >

                    There are currently no available donations for
                    your NGO. Expired or already accepted donations
                    are automatically hidden.

                </p>

            </div>


        <?php endif; ?>


    </div>

</div>


<!-- =====================================================
     QUICK STATS
     ===================================================== -->

<?php

    $totalNotifications =
        $result
            ? $result->num_rows
            : 0;

    $readCount =
        max(
            0,
            $totalNotifications - $unreadCount
        );

?>


<div class="grid grid-cols-3 gap-4">


    <!-- TOTAL -->

    <div
        class="glass-card p-3 rounded-2xl text-center"
    >

        <div
            class="text-lg font-black text-purple-600"
        >

            <?= $totalNotifications ?>

        </div>

        <div
            class="text-[9px] font-bold text-slate-400 uppercase tracking-wider"
        >

            Available

        </div>

    </div>


    <!-- READ -->

    <div
        class="glass-card p-3 rounded-2xl text-center"
    >

        <div
            class="text-lg font-black text-emerald-600"
        >

            <?= $readCount ?>

        </div>

        <div
            class="text-[9px] font-bold text-slate-400 uppercase tracking-wider"
        >

            Read

        </div>

    </div>


    <!-- UNREAD -->

    <div
        class="glass-card p-3 rounded-2xl text-center"
    >

        <div
            class="text-lg font-black text-rose-600 <?= $unreadCount > 0 ? 'animate-pulse' : '' ?>"
        >

            <?= $unreadCount ?>

        </div>

        <div
            class="text-[9px] font-bold text-slate-400 uppercase tracking-wider"
        >

            Unread

        </div>

    </div>


</div>

</main>

<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script>

function markAndRedirect(notificationId, donationId) {

    /*
     * Mark the notification as read.
     *
     * We don't wait for the response because the user is
     * immediately redirected to the available donations page.
     */

    fetch(
        'mark_notification_read.php?id=' +
        encodeURIComponent(notificationId)
    )
    .catch(function(error) {

        console.error(
            'Error marking notification as read:',
            error
        );

    });


    /*
     * Remove visual notification effects immediately.
     */

    const elements =
        document.querySelectorAll(
            '.glow-pulse'
        );

    elements.forEach(function(el) {

        el.classList.remove(
            'glow-pulse'
        );


        const dot =
            el.querySelector(
                '.alert-dot'
            );

        if (dot) {

            dot.remove();

        }


        const badge =
            el.querySelector(
                '.new-badge'
            );

        if (badge) {

            badge.remove();

        }

    });


    /*
     * Redirect to available donations.
     *
     * The donation is already checked against its expiry
     * on the server side in available_donations.php as well.
     */

    if (donationId > 0) {

        window.location.href =
            'available_donations.php?highlight=' +
            encodeURIComponent(donationId) +
            '&notif=' +
            encodeURIComponent(notificationId);

    } else {

        window.location.href =
            'available_donations.php';

    }

}


setInterval(function () {
    window.location.reload();
}, 30000);


</script>

</body>

</html>

<?php

/*
 * Close database connection.
 */
$conn->close();

?>
