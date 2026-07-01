<?php
session_start();
$conn = new mysqli("localhost", "root", "", "f_destiny");

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit();
}

$email = $_SESSION['user'];

$user = $conn->query("SELECT * FROM users WHERE email='$email'");
$volunteer = $user->fetch_assoc();

$volunteer_id = $volunteer['id'];

if (isset($_GET['id'])) {

    $donation_id = $_GET['id'];
    $volunteer_id = $volunteer['id']; // from session

    $stmt = $conn->prepare("
        UPDATE donations 
        SET volunteer_id = ?, status = 'accepted'
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $volunteer_id, $donation_id);
    $stmt->execute();

    // ADD HISTORY
    $conn->query("
        INSERT INTO activity_logs
        (donation_id, user_id, role, action, message)
        VALUES
        ('$donation_id', '$volunteer_id', 'volunteer', 'assigned',
        'Volunteer accepted delivery task')
    ");
}

header("Location: volunteer_index.php");
exit();
?>