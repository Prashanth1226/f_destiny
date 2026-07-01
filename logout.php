<?php
session_start();

$_SESSION = [];

session_unset();
session_destroy();

header("Location: /f_destiny/index.php");
exit();
?>