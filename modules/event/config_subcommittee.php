<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;

// ==========================================
// XỬ LÝ FORM SUBMIT
// ==========================================
if (isPost()) {
    $data = filter(); 
    $action = $data['action'] ?? ''; 
    $current_tab = (int)($data['active_tab'] ?? $active_tab);

    if ($action === 'create_tb') {
        $tenTB = trim($data['tenTieuBan'] ?? ''); 
        $idVong = (int)($data['idVongThi'] ?? 0);
        $idBoTieuChi = (int)($data['idBoTieuChi'] ?? 0);
        
        $ngayBaoCao = !empty($data['ngayBaoCao']) ? "'".chuan_hoa_chuoi_sql($conn, $data['ngayBaoCao'])."'" : "NULL";
        $diaDiem = !empty($data['diaDiem']) ? "'".chuan_hoa_chuoi_sql($conn, $data['diaDiem'])."'" : "NULL";
        $valBoTieuChi = $idBoTieuChi > 0 ? $idBoTieuChi : "NULL";
        
        if (!empty($tenTB) && $idVong > 0) {
            mysqli_query($conn, "INSERT INTO tieuban (idSK, idVongThi, tenTieuBan, ngayBaoCao, diaDiem, idBoTieuChi) VALUES ($id_su_kien, $idVong, '".chuan_hoa_chuoi_sql($conn, $tenTB)."', $ngayBaoCao, $diaDiem, $valBoTieuChi)");
        }
    } 
    elseif ($action === 'edit_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0); 
        $tenTB = trim($data['tenTieuBan'] ?? '');
        $idBoTieuChi = (int)($data['idBoTieuChi'] ?? 0);
        
        $ngayBaoCao = !empty($data['ngayBaoCao']) ? "'".chuan_hoa_chuoi_sql($conn, $data['ngayBaoCao'])."'" : "NULL";
        $diaDiem = !empty($data['diaDiem']) ? "'".chuan_hoa_chuoi_sql($conn, $data['diaDiem'])."'" : "NULL";
        $valBoTieuChi = $idBoTieuChi > 0 ? $idBoTieuChi : "NULL";
        
        if ($idTB > 0 && !empty($tenTB)) {
            mysqli_query($conn, "UPDATE tieuban SET tenTieuBan = '".chuan_hoa_chuoi_sql($conn, $tenTB)."', ngayBaoCao = $ngayBaoCao, diaDiem = $diaDiem, idBoTieuChi = $valBoTieuChi WHERE idTieuBan = $idTB");
        }
    }
    elseif ($action === 'delete_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        if ($idTB > 0) { 
            // BẢO VỆ DỮ LIỆU: Phải xóa bảng con trước khi xóa bảng cha
            mysqli_query($conn, "DELETE FROM tieuban_giangvien WHERE idTieuBan = $idTB"); 
            mysqli_query($conn, "DELETE FROM tieuban_sanpham WHERE idTieuBan = $idTB"); 
            mysqli_query($conn, "DELETE FROM tieuban WHERE idTieuBan = $idTB"); 
        }
    } 
    elseif ($action === 'add_gv_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0); 
        $idGV = (int)($data['idGV'] ?? 0);
        if ($idTB > 0 && $idGV > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO tieuban_giangvien (idTieuBan, idGV) VALUES ($idTB, $idGV)");
        }
    } 
    elseif ($action === 'remove_gv_tb') {
        mysqli_query($conn, "DELETE FROM tieuban_giangvien WHERE idTieuBan = ".(int)$data['idTieuBan']." AND idGV = ".(int)$data['idGV']);
    } 
    elseif ($action === 'add_sp_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0); 
        $idSP = (int)($data['idSanPham'] ?? 0);
        if ($idTB > 0 && $idSP > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO tieuban_sanpham (idTieuBan, idSanPham) VALUES ($idTB, $idSP)");
        }
    } 
    elseif ($action === 'remove_sp_tb') {
        mysqli_query($conn, "DELETE FROM tieuban_sanpham WHERE idTieuBan = ".(int)$data['idTieuBan']." AND idSanPham = ".(int)$data['idSanPham']);
    }
    
    header("Location: ?module=event&action=config_subcommittee&id=$id_su_kien&tab=$current_tab"); 
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU CƠ BẢN
// ==========================================
$vong_list = mysqli_query($conn, "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC");
$vong_array = $vong_list ? mysqli_fetch_all($vong_list, MYSQLI_ASSOC) : [];
$ds_giangvien = mysqli_fetch_all(mysqli_query($conn, "SELECT idGV, tenGV FROM giangvien ORDER BY tenGV ASC"), MYSQLI_ASSOC);

// Lấy danh sách bộ tiêu chí để hiện dropdown
$res_btc_list = mysqli_query($conn, "SELECT idBoTieuChi, tenBoTieuChi FROM botieuchi ORDER BY idBoTieuChi DESC");
$botieuchi_list = $res_btc_list ? mysqli_fetch_all($res_btc_list, MYSQLI_ASSOC) : [];

// Lấy danh sách sản phẩm ĐÃ DUYỆT
$sql_sp_approved = "
    SELECT sp.idSanPham, sp.tensanpham, n.manhom, ttn.tennhom 
    FROM sanpham sp 
    LEFT JOIN nhom n ON sp.idNhom = n.idnhom 
    LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom 
    JOIN sanpham_vongthi svt ON sp.idSanPham = svt.idSanPham
    WHERE sp.idSK = $id_su_kien AND svt.trangThai = 'Đã duyệt'
";
$ds_sanpham = mysqli_fetch_all(mysqli_query($conn, $sql_sp_approved), MYSQLI_ASSOC);

// Lấy danh sách Tiểu ban kèm Tên Vòng & Tên Bộ Tiêu Chí
$sql_tieuban = "
    SELECT tb.*, v.tenVongThi, btc.tenBoTieuChi 
    FROM tieuban tb 
    LEFT JOIN vongthi v ON tb.idVongThi = v.idVongThi 
    LEFT JOIN botieuchi btc ON tb.idBoTieuChi = btc.idBoTieuChi
    WHERE tb.idSK = $id_su_kien 
    ORDER BY tb.idTieuBan ASC
";
$tieuban_list = mysqli_fetch_all(mysqli_query($conn, $sql_tieuban), MYSQLI_ASSOC);

// Tạo Map phân công để tối ưu tốc độ duyệt HTML
$gv_tb_map = []; 
$res_gv = mysqli_query($conn, "SELECT tbg.*, gv.tenGV FROM tieuban_giangvien tbg JOIN giangvien gv ON tbg.idGV = gv.idGV"); 
if ($res_gv) { while($r = mysqli_fetch_assoc($res_gv)) $gv_tb_map[$r['idTieuBan']][] = $r; }

$sp_tb_map = []; 
$assigned_sp_ids = []; 
$res_sp = mysqli_query($conn, "SELECT tbs.*, sp.tensanpham, n.manhom, ttn.tennhom FROM tieuban_sanpham tbs JOIN sanpham sp ON tbs.idSanPham = sp.idSanPham LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom"); 
if ($res_sp) { while($r = mysqli_fetch_assoc($res_sp)) { $sp_tb_map[$r['idTieuBan']][] = $r; $assigned_sp_ids[] = $r['idSanPham']; } }

// Lọc những sản phẩm chưa được xếp vào tiểu ban nào
$unassigned_sps = array_filter($ds_sanpham, fn($sp) => !in_array($sp['idSanPham'], $assigned_sp_ids));


layout('header');
layout('navbar');
page('event/config_subcommittee', compact(
    'id_su_kien', 'vongthi_list', 'tieuban_list',
    'gv_list', 'bo_tieu_chi_list'
));
layout('footer');
