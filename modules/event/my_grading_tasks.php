<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Lấy idGV thực tế từ idTK trong session
$id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$res_gv_me = mysqli_query($conn, "SELECT idGV FROM giangvien WHERE idTK = $id_user LIMIT 1");
$id_gv = ($res_gv_me && mysqli_num_rows($res_gv_me) > 0) ? (int)mysqli_fetch_assoc($res_gv_me)['idGV'] : $id_user;

if ($id_su_kien == 0) {
    die("Không tìm thấy sự kiện.");
}

// 1. Lấy thông tin sự kiện
$sql_sk = "SELECT tenSK FROM sukien WHERE idSK = $id_su_kien";
$sk_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_sk));

// 2. Gom toàn bộ Bài thi được phân công cho Giảng viên này (Cả Độc lập và Tiểu ban)
$sql_tasks = "
    SELECT 
        a.idSanPham, a.idVongThi, a.loaiCham, 
        sp.tensanpham, n.manhom, ttn.tennhom, v.tenVongThi
    FROM (
        -- Lấy từ phân công độc lập (Tab 1)
        SELECT idSanPham, idVongThi, 'Đánh giá độc lập (Sơ loại)' as loaiCham 
        FROM phancong_doclap 
        WHERE idGV = $id_gv
        
        UNION
        
        -- Lấy từ phân công Tiểu ban (Tab 4)
        SELECT tbs.idSanPham, tb.idVongThi, CONCAT('Hội đồng: ', tb.tenTieuBan) as loaiCham 
        FROM tieuban_giangvien tbg
        JOIN tieuban tb ON tbg.idTieuBan = tb.idTieuBan
        JOIN tieuban_sanpham tbs ON tb.idTieuBan = tbs.idTieuBan
        WHERE tbg.idGV = $id_gv AND tb.idSK = $id_su_kien
    ) a
    JOIN sanpham sp ON a.idSanPham = sp.idSanPham
    LEFT JOIN nhom n ON sp.idNhom = n.idnhom
    LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
    JOIN vongthi v ON a.idVongThi = v.idVongThi
    WHERE sp.idSK = $id_su_kien
    ORDER BY a.idVongThi ASC, a.idSanPham DESC
";
$res_tasks = mysqli_query($conn, $sql_tasks);
$ds_tasks = $res_tasks ? mysqli_fetch_all($res_tasks, MYSQLI_ASSOC) : [];

// 3. Lấy trạng thái chấm điểm thực tế của Giảng viên này để hiển thị "Đã chấm" hay "Chưa chấm"
$sql_status = "
    SELECT ctc.idSanPham, pcc.idVongThi, pcc.trangThaiXacNhan 
    FROM chamtieuchi ctc
    JOIN phancongcham pcc ON ctc.idPhanCongCham = pcc.idPhanCongCham
    WHERE pcc.idGV = $id_gv AND pcc.idSK = $id_su_kien
    GROUP BY ctc.idSanPham, pcc.idVongThi, pcc.trangThaiXacNhan
";
$res_status = mysqli_query($conn, $sql_status);
$status_map = [];
if ($res_status) {
    while ($row = mysqli_fetch_assoc($res_status)) {
        // Map theo key: idSanPham_idVongThi
        $status_map[$row['idSanPham'] . '_' . $row['idVongThi']] = $row['trangThaiXacNhan'];
    }
}


layout('header');
layout('navbar');
page('event/my_grading_tasks', compact(
    'id_su_kien', 'sk_info', 'ds_tasks', 'status_map'
));
layout('footer');
