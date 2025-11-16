<?php
session_start();
require_once '../config/functions.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout', 'User logout dari sistem');
}

session_unset();
session_destroy();

// arahkan ke login menggunakan path absolut berdasarkan lokasi script
$script = $_SERVER['SCRIPT_NAME']; // misal: 
$base = dirname($script);           // -> /sistem-inventaris-sekolah-main/auth
$login_url = $base . '/login.php';  // -> /sistem-inventaris-sekolah-main/auth/login.php

header("Location: $login_url");
exit;
