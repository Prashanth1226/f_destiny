<?php
session_start();

// Database Connection Validation
$conn = new mysqli("localhost", "root", "", "f_destiny");
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Ingest and Sanitize Parameters
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Validation Whitelist
$allowed = ['in_transit', 'delivered'];

if ($id > 0 && in_array($status, $allowed)) {
    // Secure Prepared Statement Implementation
    $stmt = $conn->prepare("UPDATE donations SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
}

// Redirect Control flow
header("Location: volunteer_index.php");
exit();