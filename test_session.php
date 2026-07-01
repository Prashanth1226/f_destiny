<?php
session_start();

$_SESSION['test'] = 'working';

echo "Session set!";