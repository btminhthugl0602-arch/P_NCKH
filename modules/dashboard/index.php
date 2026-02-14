<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
  header("Location: " . _HOST_URL . "?module=auth&action=login");
  exit();
}
$active_page = 'dashboard';
$data = [
  'page_title' => 'Dashboard'
];
// Include header
layout('header', $data);
?>
