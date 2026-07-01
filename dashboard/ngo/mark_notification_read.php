<?php
session_start();
require_once '../../middleware/auth.php';
checkRole('ngo');

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    $email = $_SESSION['user'] ?? '';
    
    // Get NGO ID
    $userStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $ngo = $userStmt->get_result()->fetch_assoc();
    
    if ($ngo) {
        $ngo_id = $ngo['id'];
        
        // Mark notification as read
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $ngo_id);
        $stmt->execute();
        $stmt->close();
        
        echo "success";
    }
}

$conn->close();
?>