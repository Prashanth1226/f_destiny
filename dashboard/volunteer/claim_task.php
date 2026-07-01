<?php
/**
 * F-Destiny - Volunteer Task Assignment Processor
 * File: claim_task.php (FINAL FIXED VERSION)
 */

session_start();
require_once '../../middleware/auth.php';
checkRole('volunteer');

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

/* ---------------- Database Connection ---------------- */

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* ---------------- Volunteer Details ---------------- */

$email = $_SESSION['user'];

$v_stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ?
    LIMIT 1
");

$v_stmt->bind_param("s", $email);
$v_stmt->execute();

$volunteer = $v_stmt->get_result()->fetch_assoc();
$v_stmt->close();

if (!$volunteer) {
    die("Volunteer account not found.");
}

$volunteer_id = (int)$volunteer['id'];

/* ---------------- Claim Task ---------------- */

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: available_tasks.php");
    exit();
}

$task_id = (int)$_GET['id'];

$conn->begin_transaction();

try {

    /**
     * 🔥 KEY FIX:
     * - We FIRST lock the row using SELECT FOR UPDATE
     * - Prevents 2 volunteers claiming same task at same time
     */

    $lock_stmt = $conn->prepare("
        SELECT id, status, volunteer_id
        FROM donations
        WHERE id = ?
        FOR UPDATE
    ");

    $lock_stmt->bind_param("i", $task_id);
    $lock_stmt->execute();
    $task = $lock_stmt->get_result()->fetch_assoc();
    $lock_stmt->close();

    if (!$task) {
        throw new Exception("Task not found");
    }

    if ($task['status'] !== 'pending_volunteer' || !empty($task['volunteer_id'])) {
        throw new Exception("Task already assigned");
    }

    /**
     * FINAL ASSIGNMENT STEP
     */
    $claim_stmt = $conn->prepare("
        UPDATE donations
        SET volunteer_id = ?,
            status = 'assigned',
            volunteer_assigned_at = NOW()
        WHERE id = ?
    ");

    $claim_stmt->bind_param("ii", $volunteer_id, $task_id);
    $claim_stmt->execute();

    if ($claim_stmt->affected_rows === 0) {
        throw new Exception("Failed to assign task");
    }

    $claim_stmt->close();

    $conn->commit();

    header("Location: active_manifest.php?msg=claimed_success");
    exit();

} catch (Exception $e) {

    $conn->rollback();

    header("Location: available_tasks.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>