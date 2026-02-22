<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

header('Content-Type: application/json');

$id_lich = isset($_GET['lich']) ? (int)$_GET['lich'] : 0;
if (!$id_lich) {
    echo json_encode(['count' => 0]);
    exit;
}

$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM diemdanh WHERE idLichTrinh=$id_lich AND hienDien=1"
));
echo json_encode(['count' => (int)($r['c'] ?? 0)]);
