<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------- CHECK LOGIN ---------------- */
function checkLogin() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['roles'])) {
        header("Location: /f_destiny/index.php");
        exit();
    }
}

/* ---------------- ROLE PROTECTION (FIXED) ---------------- */
function checkRole($requiredRole) {

    checkLogin();

    $requiredRole = strtolower($requiredRole);

    $roles = array_map('strtolower', $_SESSION['roles']);

    // If user DOES NOT have required role
    if (!in_array($requiredRole, $roles)) {

        // redirect to their primary role (first available)
        $primaryRole = $roles[0] ?? null;

        switch ($primaryRole) {

            case "donor":
                header("Location: /f_destiny/dashboard/donor/index.php");
                exit();

            case "ngo":
                header("Location: /f_destiny/dashboard/ngo/ngo_index.php");
                exit();

            case "volunteer":
                header("Location: /f_destiny/dashboard/volunteer/volunteer_index.php");
                exit();

            default:
                header("Location: /f_destiny/index.php");
                exit();
        }
    }
}
?>