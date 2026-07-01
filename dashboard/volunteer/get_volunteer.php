<?php

$conn = new mysqli("localhost","root","","f_destiny");

$result = $conn->query("
SELECT name, latitude, longitude
FROM users
WHERE role='volunteer'
AND latitude IS NOT NULL
AND longitude IS NOT NULL
");

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
?>