<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1; 

// ==========================================
// XỬ LÝ FORM SUBMIT (POST REQUESTS)
// ==========================================
if (isPost()) {
    $data = filter(); 
    $action = $data['action'] ?? ''; 
    $current_tab = (int)($data['active_tab'] ?? $active_tab);

    if ($action === 'assign_doclap') {
        $idSP = (int)($data['idSanPham'] ?? 0); 
        $idGV = (int)($data['idGV'] ?? 0); 
        $idVong = (int)($data['idVongThi'] ?? 0);
        if ($idSP > 0 && $idGV > 0 && $idVong > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi) VALUES ($idSP, $idGV, $idVong)");
        }
    } 
    elseif ($action === 'remove_doclap') {
        $idSP = (int)($data['idSanPham'] ?? 0); 
        $idGV = (int)($data['idGV'] ?? 0); 
        $idVong = (int)($data['idVongThi'] ?? 0);
        mysqli_query($conn, "DELETE FROM phancong_doclap WHERE idSanPham = $idSP AND idGV = $idGV AND idVongThi = $idVong");
    }
// Logic: Duyệt & Chốt điểm (Bao gồm điểm tự động và điểm do BTC sửa tay)
    elseif ($action === 'approve_score_manual' || $action === 'reject_score') {
        $idSP = (int)$data['idSanPham'];
        $idVong = (int)$data['idVongThi'];
        $diemTB = isset($data['diemChot']) ? (float)$data['diemChot'] : 0;
        
        // Nếu BTC chủ động bấm "Đánh rớt", thì loại luôn không cần check quy chế
        if ($action === 'reject_score') {
            $chk = mysqli_query($conn, "SELECT 1 FROM sanpham_vongthi WHERE idSanPham = $idSP AND idVongThi = $idVong");
            if (mysqli_num_rows($chk) > 0) {
                mysqli_query($conn, "UPDATE sanpham_vongthi SET diemTrungBinh = $diemTB, trangThai = 'Bị loại' WHERE idSanPham = $idSP AND idVongThi = $idVong");
            } else {
                mysqli_query($conn, "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, diemTrungBinh, trangThai) VALUES ($idSP, $idVong, $diemTB, 'Bị loại')");
            }
            $_SESSION['flash_msg'] = 'Đã đánh rớt bài thi thủ công!';
            $_SESSION['flash_type'] = 'warning';
        } 
        // Nếu BTC bấm "Duyệt & Chốt" -> CẦN CHECK QUY CHẾ VÒNG THI
        else {
            // Bước 1: Lưu điểm tạm thời vào DB để Động cơ (Hàm lay_du_lieu_dong) có thể đọc được
            $chk = mysqli_query($conn, "SELECT 1 FROM sanpham_vongthi WHERE idSanPham = $idSP AND idVongThi = $idVong");
            if (mysqli_num_rows($chk) > 0) {
                mysqli_query($conn, "UPDATE sanpham_vongthi SET diemTrungBinh = $diemTB, trangThai = 'Đang xét' WHERE idSanPham = $idSP AND idVongThi = $idVong");
            } else {
                mysqli_query($conn, "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, diemTrungBinh, trangThai) VALUES ($idSP, $idVong, $diemTB, 'Đang xét')");
            }

            // Bước 2: Gọi Động cơ Quy chế (Loại: VONGTHI)
            require_once _PATH_URL . '/modules/functions/quan_ly_quy_che.php';
            $doi_tuong_check = ['idSanPham' => $idSP, 'idVongThi' => $idVong];
            $qua_vong = xet_duyet_quy_che_su_kien($conn, $id_su_kien, 'VONGTHI', $doi_tuong_check);

            // Bước 3: Quyết định trạng thái cuối cùng
            if ($qua_vong) {
                mysqli_query($conn, "UPDATE sanpham_vongthi SET trangThai = 'Đã duyệt' WHERE idSanPham = $idSP AND idVongThi = $idVong");
                $_SESSION['flash_msg'] = 'Đã chốt điểm và bài thi ĐẠT quy chế qua vòng!';
                $_SESSION['flash_type'] = 'success';
            } else {
                mysqli_query($conn, "UPDATE sanpham_vongthi SET trangThai = 'Bị loại' WHERE idSanPham = $idSP AND idVongThi = $idVong");
                $_SESSION['flash_msg'] = 'HỆ THỐNG CHẶN: Bài thi có điểm trung bình là '.$diemTB.' nhưng KHÔNG THỎA MÃN quy chế qua vòng. Đã tự động chuyển thành Bị loại!';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        $current_tab = 2; 
    }
    // LOGIC: MỜI GIÁM KHẢO THỨ 3 (TRỌNG TÀI)
    elseif ($action === 'add_3rd_judge') {
        $idSP = (int)$data['idSanPham'];
        $idGV = (int)$data['idGV'];
        $idVong = (int)$data['idVongThi'];
        
        if ($idSP > 0 && $idGV > 0 && $idVong > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi) VALUES ($idSP, $idGV, $idVong)");
            // Xóa bỏ trạng thái "Đã duyệt/Loại" cũ để bài thi quay về "Đang chấm..."
            mysqli_query($conn, "DELETE FROM sanpham_vongthi WHERE idSanPham = $idSP AND idVongThi = $idVong");
        }
        $current_tab = 2;
    }

    header("Location: ?module=event&action=config_grading&id=$id_su_kien&tab=$current_tab" . (isset($data['idVongThi']) ? "&vong=".$data['idVongThi'] : "")); 
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU ĐỔ RA GIAO DIỆN
// ==========================================
$vong_list = mysqli_query($conn, "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC");
$vong_array = $vong_list ? mysqli_fetch_all($vong_list, MYSQLI_ASSOC) : [];
$active_vong = isset($_GET['vong']) ? (int)$_GET['vong'] : (!empty($vong_array) ? $vong_array[0]['idVongThi'] : 0);

$res_gv_all = mysqli_query($conn, "SELECT idGV, tenGV FROM giangvien ORDER BY tenGV ASC");
$ds_giangvien = $res_gv_all ? mysqli_fetch_all($res_gv_all, MYSQLI_ASSOC) : [];

$sql_sp = "SELECT sp.idSanPham, sp.tensanpham, sp.TrangThai, sp.idNhom, n.manhom, ttn.tennhom 
           FROM sanpham sp 
           LEFT JOIN nhom n ON sp.idNhom = n.idnhom 
           LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom 
           WHERE sp.idSK = $id_su_kien ORDER BY sp.idSanPham DESC";
$res_sp = mysqli_query($conn, $sql_sp);
$ds_sanpham = $res_sp ? mysqli_fetch_all($res_sp, MYSQLI_ASSOC) : [];

// Lay cac file da nop cho tung san pham
$files_map = [];
if (!empty($ds_sanpham)) {
    $sp_ids = implode(',', array_column($ds_sanpham, 'idSanPham'));
    $res_files = mysqli_query($conn, "SELECT sp.idSanPham, sp.moTataiLieu, sp.idloaitailieu, l.loaitailieu AS tenLoai 
        FROM sanpham sp LEFT JOIN loaitailieu l ON sp.idloaitailieu = l.idtailieu 
        WHERE sp.idSanPham IN ($sp_ids) AND sp.moTataiLieu IS NOT NULL AND sp.moTataiLieu != '' ");
    if ($res_files) {
        while ($fr = mysqli_fetch_assoc($res_files)) {
            $files_map[$fr['idSanPham']][] = $fr;
        }
    }
}

$sql_pc = "SELECT pcd.*, gv.tenGV, v.tenVongThi FROM phancong_doclap pcd JOIN giangvien gv ON pcd.idGV = gv.idGV JOIN vongthi v ON pcd.idVongThi = v.idVongThi WHERE v.idSK = $id_su_kien";
$res_pc = mysqli_query($conn, $sql_pc);
$phancong_map = []; if($res_pc) { while($r=mysqli_fetch_assoc($res_pc)) $phancong_map[$r['idSanPham']][] = $r; }

$sql_svt = "SELECT idSanPham, diemTrungBinh, trangThai FROM sanpham_vongthi WHERE idVongThi = $active_vong";
$res_svt = mysqli_query($conn, $sql_svt);
$trangthai_map = [];
if($res_svt) { while($r = mysqli_fetch_assoc($res_svt)) $trangthai_map[$r['idSanPham']] = $r; }

$sql_tiendo = "
    SELECT sp.idSanPham, sp.tensanpham, n.manhom, ttn.tennhom, IFNULL(pc.tongGK, 0) as tongGiamKhaoPhanCong
    FROM sanpham sp LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
    JOIN (SELECT idSanPham, COUNT(idGV) as tongGK FROM phancong_doclap WHERE idVongThi = $active_vong GROUP BY idSanPham) pc ON sp.idSanPham = pc.idSanPham
    WHERE sp.idSK = $id_su_kien ORDER BY sp.idSanPham DESC";
$res_td = mysqli_query($conn, $sql_tiendo); 
$ds_tiendo = $res_td ? mysqli_fetch_all($res_td, MYSQLI_ASSOC) : [];

$sql_scores = "SELECT ctc.idSanPham, ctc.idPhanCongCham, SUM(ctc.diem) as tongDiem 
               FROM chamtieuchi ctc
               JOIN phancongcham pcc ON ctc.idPhanCongCham = pcc.idPhanCongCham
               WHERE ctc.idSanPham IN (SELECT idSanPham FROM sanpham WHERE idSK = $id_su_kien) 
               AND pcc.idVongThi = $active_vong
               GROUP BY ctc.idSanPham, ctc.idPhanCongCham";
$res_scores = mysqli_query($conn, $sql_scores);
$scores_map = []; if ($res_scores) { while ($row = mysqli_fetch_assoc($res_scores)) $scores_map[$row['idSanPham']][] = (float)$row['tongDiem']; }

$ranking_list = [];
$approved_list = [];

foreach ($ds_tiendo as $sp) {
    $sp_scores = $scores_map[$sp['idSanPham']] ?? [];
    $sp['soNguoiDaCham'] = count($sp_scores);
    $sp['diemTB'] = $sp['soNguoiDaCham'] > 0 ? array_sum($sp_scores) / $sp['soNguoiDaCham'] : 0;
    
    // Cảnh báo khi có độ lệch > 30%
    $sp['isWarning'] = ($sp['soNguoiDaCham'] > 1 && (max($sp_scores) - min($sp_scores)) >= ($sp['diemTB'] * 0.3));
    $sp['chiTietDiem'] = $sp_scores;
    
    $sp['trangThaiDuyet'] = $trangthai_map[$sp['idSanPham']]['trangThai'] ?? 'Chưa duyệt';
    $sp['diemChot'] = $trangthai_map[$sp['idSanPham']]['diemTrungBinh'] ?? 0;

    $ranking_list[] = $sp;
    
    if ($sp['trangThaiDuyet'] === 'Đã duyệt') {
        $approved_list[] = $sp;
    }
}

usort($ranking_list, fn($a, $b) => $b['diemTB'] <=> $a['diemTB']);
usort($approved_list, fn($a, $b) => $b['diemChot'] <=> $a['diemChot']);


layout('header');
layout('navbar');
page('event/config_grading', compact(
    'id_su_kien', 'active_tab', 'event',
    'vongthi_list', 'sanpham_list', 'gv_list',
    'doclap_map', 'tieuban_list', 'phancongcham_list'
));
layout('footer');
