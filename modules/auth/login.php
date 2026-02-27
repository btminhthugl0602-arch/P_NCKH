<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/session.php';
require_once _PATH_URL . '/modules/functions/quan_ly_tai_khoan.php';

// ==================== GUEST LOGIN ====================
if (isset($_GET['guest']) && $_GET['guest'] == '1') {
    $_SESSION['user_id']   = 0;
    $_SESSION['user_name'] = 'Khách';
    $_SESSION['role']      = 'guest';

    $redirect = (isset($_GET['redirect']) && $_GET['redirect'] == 'event')
        ? '?module=event&action=index'
        : '?module=home&action=index';

    header('Location: ' . _HOST_URL . $redirect);
    exit();
}

// ==================== POST LOGIN ====================
$tb_dang_nhap = '';
$error_class  = 'danger';

if (isset($_POST['btn_dang_nhap'])) {
    requireCSRF();

    $result = dang_nhap($conn, $_POST['tenTK'] ?? '', $_POST['matKhau'] ?? '');

    if ($result['status']) {
        header('Location: ' . _HOST_URL . '?module=home&action=index');
        exit();
    }

    $tb_dang_nhap = $result['message'];
}

// ==================== VIEW ====================
layout('header');
layout('navbar');
page('auth/login', [
    'tb_dang_nhap' => $tb_dang_nhap,
    'error_class'  => $error_class,
]);
layout('footer');
