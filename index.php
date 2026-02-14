<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
ob_start();
require_once('config.php');
require_once('./modules/db_connect.php');
require_once('./modules/base.php');
require_once('./modules/session.php');



$module = _MODULES;
$action = _ACTION;


if (!empty($_GET['module'])) {
    $module = $_GET['module'];
}
if (!empty($_GET['action'])) {
    $action = $_GET['action'];
}

$path = 'modules/' . $module . '/' . $action . '.php';

if (!empty($path)) {
    if (file_exists($path)) {
        require_once $path;
    } else {
        require_once './modules/errors/404.php';
    }
} else {
    require_once './modules/errors/505.php';
}
