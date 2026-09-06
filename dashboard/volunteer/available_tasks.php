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

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ?
    LIMIT 1
");

if (!$stmt) {
    die("Volunteer query failed: " . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$user) {
    die("Volunteer not found");
}

$volunteer_id = (int)$user['id'];


/* =========================================================
   AJAX ACCEPT TASK
========================================================= */

/* =========================================================
   AJAX ACCEPT TASK
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['accept_task']) &&
    $_POST['accept_task'] === '1'
) {

    header('Content-Type: application/json; charset=utf-8');

    $donation_id = (int)($_POST['donation_id'] ?? 0);

    if ($donation_id <= 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid donation ID.'
        ]);

        exit;
    }

    try {

        $conn->begin_transaction();


        /* -----------------------------------------
           LOCK DONATION
        ----------------------------------------- */

        $check = $conn->prepare("
            SELECT
                id,
                volunteer_id,
                status,
                expiry
            FROM donations
            WHERE id = ?
            FOR UPDATE
        ");

        if (!$check) {
            throw new Exception(
                "Database error: " . $conn->error
            );
        }

        $check->bind_param(
            "i",
            $donation_id
        );

        $check->execute();

        $task = $check
            ->get_result()
            ->fetch_assoc();

        $check->close();


        /* -----------------------------------------
           CHECK EXISTENCE
        ----------------------------------------- */

        if (!$task) {

            throw new Exception(
                "Donation not found."
            );
        }


        /* -----------------------------------------
           CHECK STATUS
        ----------------------------------------- */

        if ($task['status'] !== 'pending_volunteer') {

            throw new Exception(
                "This task has already been accepted by another volunteer."
            );
        }


        /* -----------------------------------------
           CHECK IF ALREADY CLAIMED
        ----------------------------------------- */

        if (
            $task['volunteer_id'] !== null &&
            (int)$task['volunteer_id'] !== 0
        ) {

            throw new Exception(
                "This task has already been accepted by another volunteer."
            );
        }


        /* -----------------------------------------
           CHECK EXPIRY
        ----------------------------------------- */

        if (
            empty($task['expiry']) ||
            strtotime($task['expiry']) <= time()
        ) {

            throw new Exception(
                "This donation has expired."
            );
        }


        /* -----------------------------------------
           CLAIM TASK
           NULL volunteer_id → current volunteer
        ----------------------------------------- */

        $update = $conn->prepare("
            UPDATE donations
            SET
                volunteer_id = ?,
                status = 'assigned'
            WHERE id = ?
              AND volunteer_id IS NULL
              AND status = 'pending_volunteer'
              AND expiry IS NOT NULL
              AND expiry > NOW()
        ");

        if (!$update) {

            throw new Exception(
                "Task update failed: " . $conn->error
            );
        }

        $update->bind_param(
            "ii",
            $volunteer_id,
            $donation_id
        );

        $update->execute();


        if ($update->affected_rows !== 1) {

            $update->close();

            throw new Exception(
                "This task was just accepted by another volunteer."
            );
        }

        $update->close();


        /* -----------------------------------------
           MARK THIS VOLUNTEER'S NOTIFICATION READ
        ----------------------------------------- */

        $readNotification = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND donation_id = ?
        ");

        if ($readNotification) {

            $readNotification->bind_param(
                "ii",
                $volunteer_id,
                $donation_id
            );

            $readNotification->execute();

            $readNotification->close();
        }


        /* -----------------------------------------
           COMMIT
        ----------------------------------------- */

        $conn->commit();


        echo json_encode([
            'success' => true,
            'message' => 'Task accepted successfully.',
            'donation_id' => $donation_id
        ]);

        exit;


    } catch (Throwable $e) {

        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

        exit;
    }
}


/* =========================================================
   START TASK
========================================================= */

if (isset($_GET['start'])) {

    $id = (int)$_GET['start'];

    $stmt = $conn->prepare("
        UPDATE donations
        SET status = 'in_transit'
        WHERE id = ?
          AND volunteer_id = ?
          AND status = 'assigned'
    ");

    if (!$stmt) {
        die("Start query failed: " . $conn->error);
    }

    $stmt->bind_param(
        "ii",
        $id,
        $volunteer_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: active_task.php");
    exit;
}


/* =========================================================
   COMPLETE TASK
========================================================= */

if (isset($_GET['complete'])) {

    $id = (int)$_GET['complete'];

    $stmt = $conn->prepare("
        UPDATE donations
        SET status = 'completed'
        WHERE id = ?
          AND volunteer_id = ?
          AND status IN ('assigned', 'in_transit')
    ");

    if (!$stmt) {
        die("Complete query failed: " . $conn->error);
    }

    $stmt->bind_param(
        "ii",
        $id,
        $volunteer_id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: volunteer_index.php");
    exit;
}


/* =========================================================
   GET AVAILABLE TASKS
========================================================= */

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
      AND d.volunteer_id IS NULL
      AND d.expiry IS NOT NULL
      AND d.expiry > NOW()

    ORDER BY d.created_at DESC
");

if (!$task_stmt) {
    die("Task query failed: " . $conn->error);
}

$task_stmt->execute();

$tasks = $task_stmt->get_result();

$available_count = $tasks->num_rows;

/* CLOSE ONLY ONCE */
$task_stmt->close();

?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Tasks  | F-Destiny </title>

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
        @keyframes notificationProgress {

            from {
                transform: scaleX(1);
            }

            to {
                transform: scaleX(0);
            }

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
            <span
                data-available-count
                data-count="<?= $available_count ?>"
                class="text-[10px] font-bold px-2.5 py-1
                    text-blue-700
                    bg-blue-50
                    rounded-full
                    border border-blue-200/40"
            >
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
                        
                        <button
                            type="button"
                            class="
                                bg-emerald-600
                                hover:bg-emerald-700
                                text-white
                                px-5
                                py-2.5
                                rounded-xl
                                font-bold
                                text-sm
                                transition-all
                                duration-200
                            "
                            data-accept-task
                            data-donation-id="<?= (int)$row['id'] ?>"
                        >
                            <span class="btn-icon">✓</span>
                            <span class="btn-text">Accept Request</span>
                        </button>
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
/* =========================================================
   F-DESTINY VOLUNTEER AVAILABLE TASKS
   CLEAN / RELIABLE TASK ACCEPTANCE SYSTEM
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* -----------------------------------------
       Dashboard card entrance animation
    ----------------------------------------- */
    document.querySelectorAll(".dashboard-card").forEach(function (card, index) {

        card.style.opacity = "0";
        card.style.transform = "translateY(12px)";

        setTimeout(function () {

            card.style.transition =
                "all .6s cubic-bezier(0.16, 1, 0.3, 1)";

            card.style.opacity = "1";
            card.style.transform = "translateY(0)";

        }, index * 40);

    });

    /* -----------------------------------------
       Make sure all accept buttons work
    ----------------------------------------- */
    document.querySelectorAll("[data-accept-task]").forEach(function (button) {

        button.addEventListener("click", function () {

            const donationId = this.getAttribute("data-donation-id");

            if (!donationId) {
                showNotification(
                    "error",
                    "Invalid Task",
                    "The donation ID is missing."
                );
                return;
            }

            claimTask(this, donationId);

        });

    });

});


/* =========================================================
   ACCEPT / CLAIM TASK
========================================================= */

async function claimTask(button, donationId) {

    /* Prevent double clicks */
    if (!button || button.disabled) {
        return;
    }

    if (!donationId || parseInt(donationId) <= 0) {

        showNotification(
            "error",
            "Invalid Task",
            "Unable to identify this donation."
        );

        return;
    }

    /* -----------------------------------------
       Find task card
    ----------------------------------------- */
    const card =
        button.closest("[data-task-card]") ||
        button.closest(".dashboard-card") ||
        button.closest(".task-card");

    /* -----------------------------------------
       Find button elements
    ----------------------------------------- */
    const icon =
        button.querySelector(".btn-icon");

    const text =
        button.querySelector(".btn-text");

    const originalHTML =
        button.innerHTML;

    /* -----------------------------------------
       Disable button immediately
    ----------------------------------------- */
    button.disabled = true;

    button.setAttribute(
        "aria-disabled",
        "true"
    );

    button.classList.add(
        "opacity-70",
        "cursor-not-allowed"
    );

    /* -----------------------------------------
       Loading state
    ----------------------------------------- */
    if (icon) {

        icon.innerHTML = `
            <svg
                class="w-4 h-4 animate-spin"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="9"
                    stroke="currentColor"
                    stroke-width="3"
                    opacity=".25"
                ></circle>

                <path
                    d="M21 12a9 9 0 0 0-9-9"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round"
                ></path>
            </svg>
        `;
    }

    if (text) {
        text.textContent = "Accepting...";
    }


    try {

        /* -----------------------------------------
           Prepare request
        ----------------------------------------- */
        const formData = new FormData();

        formData.append(
            "accept_task",
            "1"
        );

        formData.append(
            "donation_id",
            String(donationId)
        );


        /* -----------------------------------------
           Send request to SAME PHP file
        ----------------------------------------- */
        const response = await fetch(
            "available_tasks.php",
            {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                }
            }
        );


        /* -----------------------------------------
           Get server response
        ----------------------------------------- */
        const rawResponse =
            await response.text();

        console.log(
            "Accept task server response:",
            rawResponse
        );


        /* -----------------------------------------
           Check HTTP response
        ----------------------------------------- */
        if (!response.ok) {

            throw new Error(
                "Server error (" +
                response.status +
                "). Please try again."
            );
        }


        /* -----------------------------------------
           Parse JSON safely
        ----------------------------------------- */
        let result;

        try {

            result =
                JSON.parse(
                    rawResponse.trim()
                );

        } catch (jsonError) {

            console.error(
                "Invalid JSON from available_tasks.php:",
                rawResponse
            );

            throw new Error(
                "The server returned an invalid response. Check available_tasks.php for PHP errors."
            );
        }


        /* -----------------------------------------
           SUCCESS
        ----------------------------------------- */
        if (
            result.success === true ||
            result.success === 1 ||
            result.success === "1"
        ) {

            /* Change button */
            if (icon) {
                icon.innerHTML = "✓";
            }

            if (text) {
                text.textContent = "Accepted";
            }

            button.classList.remove(
                "bg-emerald-600",
                "hover:bg-emerald-700",
                "opacity-70"
            );

            button.classList.add(
                "bg-slate-700"
            );

            button.disabled = true;


            /* -----------------------------------------
               Success notification
            ----------------------------------------- */
            showNotification(
                "success",
                "Task Accepted",
                result.message ||
                "The donation has been added to your active tasks."
            );


            /* -----------------------------------------
               Remove card after short delay
            ----------------------------------------- */
            setTimeout(function () {

                if (!card) {

                    location.reload();

                    return;
                }


                card.style.transition =
                    "all .5s cubic-bezier(.16,1,.3,1)";

                card.style.opacity = "0";

                card.style.transform =
                    "translateY(-15px) scale(.97)";


                setTimeout(function () {

                    if (card && card.isConnected) {
                        card.remove();
                    }

                    updateAvailableCount();

                }, 500);

            }, 700);


        }

        /* -----------------------------------------
           SERVER REJECTED TASK
        ----------------------------------------- */
        else {

            throw new Error(
                result.message ||
                "This task could not be accepted."
            );
        }


    } catch (error) {

        console.error(
            "Claim task error:",
            error
        );


        /* -----------------------------------------
           Restore button
        ----------------------------------------- */
        button.disabled = false;

        button.removeAttribute(
            "aria-disabled"
        );

        button.classList.remove(
            "opacity-70",
            "cursor-not-allowed"
        );


        if (icon) {
            icon.innerHTML = "✓";
        }

        if (text) {
            text.textContent = "Accept Request";
        }

        /*
         * If the original button contained
         * more complicated HTML, restore it.
         */
        if (!icon && !text) {
            button.innerHTML = originalHTML;
        }


        /* -----------------------------------------
           Error notification
        ----------------------------------------- */
        showNotification(
            "error",
            "Unable to Accept",
            error.message ||
            "Something went wrong while accepting the task."
        );

    }

}


/* =========================================================
   NOTIFICATION
========================================================= */

function showNotification(
    type,
    title,
    message
) {

    /* Remove existing notification */
    const oldNotification =
        document.getElementById(
            "taskNotification"
        );

    if (oldNotification) {
        oldNotification.remove();
    }


    const success =
        type === "success";


    const notification =
        document.createElement("div");


    notification.id =
        "taskNotification";


    notification.className = `
        fixed
        top-6
        right-6
        z-[99999]
        w-[380px]
        max-w-[calc(100vw-2rem)]
        bg-white/95
        backdrop-blur-2xl
        border
        ${success
            ? "border-emerald-200"
            : "border-red-200"
        }
        rounded-2xl
        shadow-2xl
        p-5
        translate-x-[120%]
        transition-all
        duration-500
    `;


    /*
     * Escape user/server text before putting
     * it into innerHTML.
     */
    const safeTitle =
        escapeHTML(title);

    const safeMessage =
        escapeHTML(message);


    notification.innerHTML = `

        <div class="flex items-start gap-4">

            <div
                class="
                    w-12
                    h-12
                    rounded-2xl
                    flex
                    items-center
                    justify-center
                    text-xl
                    shrink-0
                    ${
                        success
                            ? "bg-emerald-100 text-emerald-600"
                            : "bg-red-100 text-red-600"
                    }
                "
            >
                ${success ? "✓" : "!"}
            </div>

            <div class="flex-1 min-w-0">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                        gap-3
                    "
                >

                    <h3
                        class="
                            font-extrabold
                            text-sm
                            ${
                                success
                                    ? "text-emerald-800"
                                    : "text-red-800"
                            }
                        "
                    >
                        ${safeTitle}
                    </h3>

                    <button
                        type="button"
                        class="
                            text-slate-400
                            hover:text-slate-700
                            text-xl
                            leading-none
                        "
                        onclick="
                            document
                            .getElementById('taskNotification')
                            ?.remove()
                        "
                        aria-label="Close notification"
                    >
                        ×
                    </button>

                </div>

                <p
                    class="
                        text-xs
                        text-slate-500
                        mt-1
                        leading-5
                    "
                >
                    ${safeMessage}
                </p>

            </div>

        </div>
    `;


    document.body.appendChild(
        notification
    );


    /* Slide in */
    requestAnimationFrame(function () {

        notification.classList.remove(
            "translate-x-[120%]"
        );

    });


    /* Auto close */
    setTimeout(function () {

        if (!notification.isConnected) {
            return;
        }

        notification.classList.add(
            "translate-x-[120%]"
        );


        setTimeout(function () {

            if (notification.isConnected) {
                notification.remove();
            }

        }, 500);

    }, 5000);

}


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHTML(value) {

    const div =
        document.createElement("div");

    div.textContent =
        String(value ?? "");

    return div.innerHTML;
}


/* =========================================================
   UPDATE AVAILABLE COUNT
========================================================= */

function updateAvailableCount() {

    /*
     * Look for the count element.
     * Supports several possible elements.
     */
    const countElements =
        document.querySelectorAll(
            "[data-available-count]"
        );


    /* -----------------------------------------
       Update every available-count element
    ----------------------------------------- */
    if (countElements.length > 0) {

        countElements.forEach(function (element) {

            let current =
                parseInt(
                    element.dataset.count ||
                    element.textContent ||
                    "0"
                );

            if (isNaN(current)) {
                current = 1;
            }

            const newCount =
                Math.max(
                    0,
                    current - 1
                );


            element.dataset.count =
                String(newCount);


            /*
             * If this is a badge, just show number.
             */
            if (
                element.classList.contains("link-badge") ||
                element.classList.contains("badge")
            ) {

                element.textContent =
                    newCount;

            }

            /*
             * Otherwise show the normal
             * "X Available Requests" text.
             */
            else {

                element.textContent =
                    newCount +
                    " Available Requests";

            }

        });

    }


    /* -----------------------------------------
       Update visible task count if present
    ----------------------------------------- */
    const countDisplay =
        document.getElementById(
            "availableTaskCount"
        );

    if (countDisplay) {

        let current =
            parseInt(
                countDisplay.textContent ||
                "0"
            );

        if (isNaN(current)) {
            current = 1;
        }

        countDisplay.textContent =
            Math.max(
                0,
                current - 1
            );
    }


    /* -----------------------------------------
       If no tasks remain, reload page
    ----------------------------------------- */
    const remainingCards =
        document.querySelectorAll(
            "[data-task-card]"
        );


    if (remainingCards.length === 0) {

        setTimeout(function () {
            location.reload();
        }, 700);

    }

}
</script>

</body>
</html>
<?php
$conn->close();
?>