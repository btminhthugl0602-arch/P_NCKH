<?php
if (!defined('_AUTHEN')) {
    header('Content-Type: application/json');
    echo json_encode(['exists' => false]);
    exit;
}
header('Content-Type: application/json');
header('Cache-Control: no-store');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(['exists' => false]);
    exit;
}

$tenSK   = isset($_GET['tenSK'])   ? trim($_GET['tenSK'])   : '';
$idLoaiCap = isset($_GET['idLoaiCap']) ? (int)$_GET['idLoaiCap'] : 0;

if ($tenSK === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$tenSK_safe = mysqli_real_escape_string($conn, $tenSK);

// Tìm tên trùng — nếu có idLoaiCap thì lọc theo cấp
if ($idLoaiCap > 0) {
    $sql = "SELECT COUNT(*) as c FROM sukien sk
            LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
            WHERE sk.tenSK = '$tenSK_safe'
              AND ct.idLoaiCap = $idLoaiCap";
} else {
    $sql = "SELECT COUNT(*) as c FROM sukien WHERE tenSK = '$tenSK_safe'";
}

$res   = mysqli_query($conn, $sql);
$count = $res ? (int)mysqli_fetch_assoc($res)['c'] : 0;

echo json_encode(['exists' => $count > 0, 'count' => $count]);
