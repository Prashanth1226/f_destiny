<?php
session_start();

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------- LOGIN CHECK ----------------
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'donor') {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: dashboard/donor/index.php");
    exit();
}

// ---------------- USER FETCH ----------------
$email = $_SESSION['user'];

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();

if (!$donor) {
    die("User not found");
}

$donor_id = $donor['id'];

// ---------------- FORM DATA ----------------
$food_type = $_POST['food_name'];
$quantity = $_POST['quantity'];
$expiry = $_POST['expiry'];
$address = $_POST['location'];
$description = $_POST['description'];

// ---------------- INSERT ----------------
$stmt = $conn->prepare("
    INSERT INTO donations 
    (donor_id, food_type, quantity, expiry, address, description, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$status = "Pending";

$stmt->bind_param(
    "issssss",
    $donor_id,
    $food_type,
    $quantity,
    $expiry,
    $address,
    $description,
    $status
);

if ($stmt->execute()) {

    $_SESSION['success_msg'] = "Donation submitted successfully!";

} else {

    $_SESSION['error_msg'] = "Donation failed. Try again!";
}

// ALWAYS redirect back
header("Location: dashboard/donor/index.php");
exit();
?>