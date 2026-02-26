<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
ob_start();
require_once('config.php');
require_once('./modules/functions/db_connect.php');
require_once('./modules/functions/base.php');
require_once('./modules/functions/session.php');

// ============================================================
// SECURITY FIX #1: Whitelist Router — chặn Path Traversal
// ============================================================
$allowed_modules = [
    'home',
    'auth',
    'event',
    'users',
    'groups',
    'diemdanh',
    'profile',
    'thongbao',
    'admin',
    'errors'
];

$module = _MODULES;
$action = _ACTION;

if (!empty($_GET['module'])) {
    if (in_array($_GET['module'], $allowed_modules, true)) {
        $module = $_GET['module'];
    } else {
        // Module không hợp lệ → redirect về trang chủ
        header('Location: ' . _HOST_URL);
        exit;
    }
}

if (!empty($_GET['action'])) {
    if (preg_match('/^[a-z0-9_]+$/', $_GET['action'])) {
        $action = $_GET['action'];
    } else {
        // Action không hợp lệ → redirect về trang chủ
        header('Location: ' . _HOST_URL);
        exit;
    }
}

$path = 'modules/' . $module . '/' . $action . '.php';

if (file_exists($path)) {
    require_once $path;
} else {
    require_once './modules/errors/404.php';
}
