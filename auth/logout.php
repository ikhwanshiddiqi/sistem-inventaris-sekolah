<?php
session_start();
require_once '../config/functions.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout', 'User logout dari sistem');
}

session_destroy();

// arah ke login di folder yang sama (auth)
header("Location: login.php");
exit;
?>
