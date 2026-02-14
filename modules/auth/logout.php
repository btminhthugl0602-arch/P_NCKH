<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
// Xóa tất cả session
$_SESSION = [];
session_unset();
session_destroy();

// Chuyển hướng về trang đăng nhập
header("Location: " . _HOST_URL . "?module=auth&action=login");
exit();
