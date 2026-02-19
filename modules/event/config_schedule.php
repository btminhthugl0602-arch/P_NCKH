<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

layout('header');
layout('navbar');
?>

<main class="main container py-4">
    <h2>Lập lịch tổ chức</h2>
    <p>Module này sẽ dùng bảng <code>lichtrinh</code> để tạo hoạt động và thời gian theo sự kiện.</p>
    <p>Bạn có thể hoàn thiện form tại đây sau.</p>
</main>

<?php layout('footer'); ?>