<?php
/**
 * F-Destiny - Volunteer Real-Time Telemetry Node
 * File: update_location.php (Typically called via AJAX/Background Navigator API)
 */

session_start();

// 1. Enforce Server Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit("Method Not Allowed");
}

// 2. Establish Secure Connection Link
$conn = new mysqli("localhost", "root", "", "f_destiny");
if ($conn->connect_error) {
    http_response_code(500);
    exit("Connection Failed");
}
$conn->set_charset("utf8mb4");

// 3. Authenticate Session Guard
if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    exit("Unauthorized Access");
}

$email = $_SESSION['user'];

// 4. Ingest and Cast Telemetry Coordinates 
$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;

// Validate that coordinates are present and within global geographic bounds
if ($lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {

    // 5. Execute Secure DB Telemetry Injection
    $stmt = $conn->prepare("
        UPDATE users
        SET latitude = ?, longitude = ?
        WHERE email = ?
    ");

    if ($stmt) {
        $stmt->bind_param("dds", $lat, $lng, $email);
        $stmt->execute();
        
        if ($stmt->affected_rows >= 0) {
            echo "success";
        } else {
            http_response_code(500);
            echo "Error updating record";
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo "Statement preparation failed";
    }
} else {
    http_response_code(400); // Bad Request
    echo "Invalid or malformed coordinate data";
}

$conn->close();
?>