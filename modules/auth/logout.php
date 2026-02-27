<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

session_destroy();
header('Location: ' . _HOST_URL . '?module=auth&action=login');
exit();
