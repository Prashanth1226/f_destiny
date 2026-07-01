<?php

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "f_destiny"
);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}