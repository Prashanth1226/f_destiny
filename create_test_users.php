<?php

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$ngoPass = password_hash("123456", PASSWORD_DEFAULT);
$volPass = password_hash("123456", PASSWORD_DEFAULT);

$conn->query("DELETE FROM users WHERE email='ngo@test.com'");
$conn->query("DELETE FROM users WHERE email='vol@test.com'");

$conn->query("
INSERT INTO users (name,email,password,role)
VALUES ('NGO User','ngo@test.com','$ngoPass','ngo')
");

$conn->query("
INSERT INTO users (name,email,password,role)
VALUES ('Volunteer User','vol@test.com','$volPass','volunteer')
");

echo "NGO and Volunteer accounts created successfully!";
?>