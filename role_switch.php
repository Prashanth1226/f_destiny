<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$roles = $_SESSION['roles'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Switch Role</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f6fa;
            text-align: center;
            padding-top: 80px;
        }

        .box {
            background: white;
            width: 400px;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .btn {
            display: block;
            margin: 15px 0;
            padding: 12px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Welcome 👋</h2>
    <p>Select your role</p>

    <?php foreach ($roles as $role): ?>

        <?php
        $role = strtolower(trim($role));

        if ($role == "donor") {
            $link = "/f_destiny/dashboard/donor/index.php";
        }
        elseif ($role == "ngo") {
            $link = "/f_destiny/dashboard/ngo/ngo_index.php";
        }
        elseif ($role == "volunteer") {
            $link = "/f_destiny/dashboard/volunteer/volunteer_index.php";
        } else {
            continue;
        }
        ?>

        <a class="btn" href="set_role.php?role=<?php echo $role; ?>&redirect=<?php echo urlencode($link); ?>">
            Continue as <?php echo strtoupper($role); ?>
        </a>

    <?php endforeach; ?>

</div>

</body>
</html>