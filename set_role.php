<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['role']) || !isset($_GET['redirect'])) {
    header("Location: role_switch.php");
    exit();
}

$role = strtolower(trim($_GET['role']));
$redirect = $_GET['redirect'];

$_SESSION['role'] = $role;

header("Location: " . $redirect);
exit();