<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

// Lấy idGV thực tế từ idTK trong session
$id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$res_gv_me = mysqli_query($conn, "SELECT idGV FROM giangvien WHERE idTK = $id_user LIMIT 1");
$id_gv = ($res_gv_me && mysqli_num_rows($res_gv_me) > 0) ? (int)mysqli_fetch_assoc($res_gv_me)['idGV'] : 0;

$id_sp = isset($_GET['idSP']) ? (int)$_GET['idSP'] : 0;
$id_vong = isset($_GET['idVong']) ? (int)$_GET['idVong'] : 0;
$id_sk = isset($_GET['idSK']) ? (int)$_GET['idSK'] : 0;

if ($id_sp == 0 || $id_vong == 0) {
    die("Thiếu thông tin bài thi hoặc vòng thi!");
}

// ==========================================
// [LUỒNG 3] CHECK QUYỀN TẦNG 2 — PHANCONG_CHAM
// Tầng 1: kiem_tra_quyen_su_kien(..., 'nhap_diem') — check GV có role chấm trong SK không
// Tầng 2: kiem_tra_duoc_cham_bai()               — check GV được phân công bài CỤ THỂ này không
// Cả 2 tầng phải pass mới được vào trang chấm điểm.
// ==========================================
if ($id_gv <= 0) {
    // Không tìm thấy GV tương ứng session → redirect ngay
    $_SESSION['flash_msg']  = 'Bạn không có tư cách giảng viên trong hệ thống.';
    $_SESSION['flash_type'] = 'danger';
    header("Location: ?module=event&action=view&id=$id_sk");
    exit();
}

if (!kiem_tra_duoc_cham_bai($conn, $id_gv, $id_sp, $id_vong)) {
    $_SESSION['flash_msg']  = 'Bạn không được phân công chấm bài này.';
    $_SESSION['flash_type'] = 'danger';
    header("Location: ?module=event&action=view&id=$id_sk");
    exit();
}

// ==========================================
// XỬ LÝ LƯU ĐIỂM (KHI GIẢNG VIÊN BẤM SUBMIT)
// ==========================================
if (isPost()) {
    $data = filter();
    $diem_array = $_POST['diem'] ?? [];
    $nhanxet_array = $_POST['nhanXet'] ?? [];
    $action = $data['action'] ?? 'draft'; // 'draft' (Lưu nháp) hoặc 'submit' (Chốt điểm)

    $trangThaiXacNhan = ($action === 'submit') ? 'Đã xác nhận' : 'Đang chấm';

    // Lấy thời gian hiện tại để lưu vào DB tránh lỗi Field 'ngayXacNhan' doesn't have a default value
    $ngayXacNhan = date('Y-m-d H:i:s');

    // 1. Kiểm tra xem đã có phiếu chấm trong bảng phancongcham chưa
    // Cần biết idBoTieuChi đang dùng cho vòng này
    $sql_btc = "SELECT idBoTieuChi FROM cauhinh_tieuchi_sk WHERE idSK = $id_sk AND idVongThi = $id_vong LIMIT 1";
    $res_btc = mysqli_query($conn, $sql_btc);
    $id_bo_tieu_chi = ($res_btc && mysqli_num_rows($res_btc) > 0) ? mysqli_fetch_assoc($res_btc)['idBoTieuChi'] : 1;

    $chk_pcc = mysqli_query($conn, "SELECT idPhanCongCham FROM phancongcham WHERE idGV = $id_gv AND idVongThi = $id_vong");

    if (mysqli_num_rows($chk_pcc) > 0) {
        $id_pcc = mysqli_fetch_assoc($chk_pcc)['idPhanCongCham'];
        // Đã bổ sung cập nhật ngayXacNhan
        mysqli_query($conn, "UPDATE phancongcham SET trangThaiXacNhan = '$trangThaiXacNhan', ngayXacNhan = '$ngayXacNhan' WHERE idPhanCongCham = $id_pcc");
    } else {
        // Đã bổ sung ngayXacNhan vào câu lệnh INSERT
        mysqli_query($conn, "INSERT INTO phancongcham (idGV, idSK, idVongThi, idBoTieuChi, trangThaiXacNhan, ngayXacNhan) 
                             VALUES ($id_gv, $id_sk, $id_vong, $id_bo_tieu_chi, '$trangThaiXacNhan', '$ngayXacNhan')");
        $id_pcc = mysqli_insert_id($conn);
    }

    // 2. Lưu điểm chi tiết vào bảng chamtieuchi
    // Xóa điểm cũ của SP này do GK này chấm (để insert lại đồ mới)
    mysqli_query($conn, "DELETE FROM chamtieuchi WHERE idPhanCongCham = $id_pcc AND idSanPham = $id_sp");

    foreach ($diem_array as $idTieuChi => $diem) {
        $diem_val = (float)$diem;
        $nhanxet_val = isset($nhanxet_array[$idTieuChi]) ? chuan_hoa_chuoi_sql($conn, $nhanxet_array[$idTieuChi]) : '';

        mysqli_query($conn, "INSERT INTO chamtieuchi (idPhanCongCham, idSanPham, idTieuChi, diem, nhanXet) 
                             VALUES ($id_pcc, $id_sp, $idTieuChi, $diem_val, '$nhanxet_val')");
    }

    $msg = ($action === 'submit') ? "Đã chốt điểm thành công!" : "Đã lưu nháp điểm!";
    $flash_type = ($action === 'submit') ? 'success' : 'info';
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_type'] = $flash_type;
    header("Location: ?module=event&action=grade_product&idSP=$id_sp&idVong=$id_vong&idSK=$id_sk");
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ FORM CHẤM
// ==========================================
// 1. Thông tin bài thi
$sql_sp = "SELECT sp.tensanpham, n.manhom, ttn.tennhom FROM sanpham sp LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom WHERE sp.idSanPham = $id_sp";
$sp_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_sp));

// 1b. Lấy các file sản phẩm đã nộp
$sql_files = "SELECT sp2.idSanPham, sp2.moTataiLieu, sp2.idloaitailieu, l.loaitailieu AS tenLoai
    FROM sanpham sp2
    LEFT JOIN loaitailieu l ON sp2.idloaitailieu = l.idtailieu
    WHERE sp2.idNhom = (SELECT idNhom FROM sanpham WHERE idSanPham = $id_sp LIMIT 1)
      AND sp2.idSK = $id_sk
      AND sp2.moTataiLieu IS NOT NULL AND sp2.moTataiLieu != ''
    ORDER BY sp2.idloaitailieu ASC";
$res_files = mysqli_query($conn, $sql_files);
$sp_files = $res_files ? mysqli_fetch_all($res_files, MYSQLI_ASSOC) : [];

// 2. Lấy danh sách tiêu chí dựa trên cấu hình của Vòng thi đó
$sql_tc = "
    SELECT tc.idTieuChi, tc.noiDungTieuChi, btt.diemToiDa 
    FROM cauhinh_tieuchi_sk cts
    JOIN botieuchi_tieuchi btt ON cts.idBoTieuChi = btt.idBoTieuChi
    JOIN tieuchi tc ON btt.idTieuChi = tc.idTieuChi
    WHERE cts.idSK = $id_sk AND cts.idVongThi = $id_vong
";
$res_tc = mysqli_query($conn, $sql_tc);
$ds_tieuchi = $res_tc ? mysqli_fetch_all($res_tc, MYSQLI_ASSOC) : [];

// 3. Lấy điểm cũ nếu Giám khảo đã từng lưu nháp
$sql_old_scores = "
    SELECT ctc.idTieuChi, ctc.diem, ctc.nhanXet, pcc.trangThaiXacNhan
    FROM chamtieuchi ctc
    JOIN phancongcham pcc ON ctc.idPhanCongCham = pcc.idPhanCongCham
    WHERE pcc.idGV = $id_gv AND pcc.idVongThi = $id_vong AND ctc.idSanPham = $id_sp
";
$res_old = mysqli_query($conn, $sql_old_scores);
$diem_cu = [];
$trang_thai_cham = 'Chưa chấm';

if ($res_old && mysqli_num_rows($res_old) > 0) {
    while ($row = mysqli_fetch_assoc($res_old)) {
        $diem_cu[$row['idTieuChi']] = [
            'diem' => $row['diem'],
            'nhanXet' => $row['nhanXet']
        ];
        $trang_thai_cham = $row['trangThaiXacNhan'];
    }
}

$is_locked = ($trang_thai_cham === 'Đã xác nhận'); // Nếu đã chốt điểm thì khóa form không cho sửa nữa


layout('header');
layout('navbar');
page('event/grade_product', compact(
    'id_su_kien', 'id_san_pham', 'id_vong_thi',
    'sanpham', 'nhom', 'vong', 'tieu_chi_list',
    'diem_map', 'is_submitted'
));
layout('footer');
