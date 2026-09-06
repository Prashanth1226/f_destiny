<?php

session_start();

require_once '../../middleware/auth.php';
checkRole('volunteer');

require_once '../../config/db.php';

header('Content-Type: application/json');


/* ================= LOGIN CHECK ================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Your session has expired. Please login again."
    ]);

    exit;
}


$volunteer_id = (int)$_SESSION['user_id'];


/* ================= TASK ID ================= */

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid task."
    ]);

    exit;
}


$donation_id = (int)$_GET['id'];


/* ================= TRANSACTION ================= */

$conn->begin_transaction();


try {

    /*
     * Lock donation row.
     */

    $stmt = $conn->prepare("
        SELECT id, status
        FROM donations
        WHERE id = ?
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception(
            "Unable to check the task."
        );
    }


    $stmt->bind_param(
        "i",
        $donation_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $donation = $result->fetch_assoc();

    $stmt->close();


    /* ================= TASK EXISTS ================= */

    if (!$donation) {

        throw new Exception(
            "This task no longer exists."
        );
    }


    /*
     * Your available-task status.
     */

    if (
        strtolower($donation['status'])
        !== 'pending_volunteer'
    ) {

        throw new Exception(
            "This task is no longer available."
        );
    }


    /* ================= EXISTING ASSIGNMENT ================= */

    $check = $conn->prepare("
        SELECT id
        FROM assignments
        WHERE donation_id = ?
        LIMIT 1
    ");

    if (!$check) {
        throw new Exception(
            "Unable to verify assignment."
        );
    }


    $check->bind_param(
        "i",
        $donation_id
    );

    $check->execute();

    $assignmentResult =
        $check->get_result();


    if ($assignmentResult->num_rows > 0) {

        $check->close();

        throw new Exception(
            "Another volunteer has already accepted this task."
        );
    }


    $check->close();


    /* ================= CREATE ASSIGNMENT ================= */

    $assign = $conn->prepare("
        INSERT INTO assignments
        (
            donation_id,
            volunteer_id,
            status
        )
        VALUES
        (?, ?, 'Assigned')
    ");

    if (!$assign) {

        throw new Exception(
            "Unable to create assignment."
        );
    }


    $assign->bind_param(
        "ii",
        $donation_id,
        $volunteer_id
    );


    if (!$assign->execute()) {

        throw new Exception(
            "Unable to assign this task."
        );
    }


    $assign->close();


    /* ================= UPDATE DONATION ================= */

    $update = $conn->prepare("
        UPDATE donations
        SET status = 'Approved'
        WHERE id = ?
    ");


    if (!$update) {

        throw new Exception(
            "Unable to update donation status."
        );
    }


    $update->bind_param(
        "i",
        $donation_id
    );

    $update->execute();

    $update->close();


    /* ================= SUCCESS ================= */

    $conn->commit();


    echo json_encode([
        "success" => true,
        "message" => "Task accepted successfully.",
        "donation_id" => $donation_id
    ]);

    exit;


} catch (Exception $e) {

    $conn->rollback();


    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

    exit;
}
?>