<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';
require_once _PATH_URL . '/modules/functions/quan_ly_thong_bao.php';

// ==================== QUERY DỮ LIỆU ====================

$id_user = $_SESSION['user_id'] ?? 0;

// Thống kê hero
$total_sk   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1"))['c'] ?? 0);
$total_nhom = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM nhom"))['c'] ?? 0);
$total_sv   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sinhvien"))['c'] ?? 0);
$total_gv   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM giangvien"))['c'] ?? 0);

// Danh sách sự kiện (6 sự kiện mới nhất, ưu tiên đang mở đăng ký)
$sql_sk = "SELECT sk.*, ct.tenCap, tk.tenTK as nguoiTaoTen
           FROM sukien sk
           LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
           LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
           WHERE sk.isActive = 1
           ORDER BY
               CASE
                   WHEN sk.ngayMoDangKy IS NOT NULL
                        AND sk.ngayDongDangKy IS NOT NULL
                        AND CURDATE() BETWEEN sk.ngayMoDangKy AND sk.ngayDongDangKy THEN 1
                   WHEN sk.ngayMoDangKy IS NOT NULL
                        AND CURDATE() < sk.ngayMoDangKy THEN 2
                   WHEN sk.ngayDongDangKy IS NOT NULL
                        AND CURDATE() > sk.ngayDongDangKy THEN 3
                   ELSE 4
               END,
               sk.idSK DESC
           LIMIT 6";

$result_sk = mysqli_query($conn, $sql_sk);
$events    = [];
if ($result_sk) {
    while ($row = mysqli_fetch_assoc($result_sk)) {
        $events[] = $row;
    }
}

// Thông báo — công khai + riêng nếu đã đăng nhập
if ($id_user > 0) {
    $sql_tb = "SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao,
                      tb.ngayGui, tb.isPublic, tb.idSK, sk.tenSK,
                      COALESCE(tbn.daDoc, 1) as daDoc
               FROM thongbao tb
               LEFT JOIN sukien sk ON tb.idSK = sk.idSK
               LEFT JOIN thongbao_nguoinhan tbn
                      ON tb.idThongBao = tbn.idThongBao AND tbn.idTK = $id_user
               WHERE tb.isPublic = 1 OR tbn.idTK = $id_user
               ORDER BY tb.ngayGui DESC
               LIMIT 8";
} else {
    $sql_tb = "SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao,
                      tb.ngayGui, tb.isPublic, tb.idSK, sk.tenSK,
                      1 as daDoc
               FROM thongbao tb
               LEFT JOIN sukien sk ON tb.idSK = sk.idSK
               WHERE tb.isPublic = 1
               ORDER BY tb.ngayGui DESC
               LIMIT 8";
}

$result_tb    = mysqli_query($conn, $sql_tb);
$ds_thong_bao = [];
$so_chua_doc  = 0;
if ($result_tb) {
    while ($row = mysqli_fetch_assoc($result_tb)) {
        if (!$row['daDoc']) $so_chua_doc++;
        $ds_thong_bao[] = $row;
    }
}

// Ảnh xoay vòng sự kiện
$event_images = [
    _HOST_URL_TEMPLATES . '/assets/img/education/events-3.webp',
    _HOST_URL_TEMPLATES . '/assets/img/education/events-5.webp',
    _HOST_URL_TEMPLATES . '/assets/img/education/events-7.webp',
    _HOST_URL_TEMPLATES . '/assets/img/education/campus-4.webp',
    _HOST_URL_TEMPLATES . '/assets/img/education/campus-8.webp',
    _HOST_URL_TEMPLATES . '/assets/img/education/activities-3.webp',
];

$months_vi = ['', 'T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];

// ==================== VIEW ====================
layout('header');
layout('navbar');
page('home/index', [
    'total_sk'      => $total_sk,
    'total_nhom'    => $total_nhom,
    'total_sv'      => $total_sv,
    'total_gv'      => $total_gv,
    'events'        => $events,
    'ds_thong_bao'  => $ds_thong_bao,
    'so_chua_doc'   => $so_chua_doc,
    'event_images'  => $event_images,
    'months_vi'     => $months_vi,
]);
layout('footer');
