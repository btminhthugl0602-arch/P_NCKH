<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ======================
// Xử lý AJAX: Tải chi tiết Bộ Tiêu Chí (Dùng chung cho Nhân bản & Chỉnh sửa)
// ======================
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_full_set') {
    header('Content-Type: application/json');
    $idBo = (int)($_GET['idBo'] ?? 0);
    
    // Lấy thông tin chung của Bộ
    $sql_master = "SELECT b.tenBoTieuChi, b.moTa, c.idVongThi 
                   FROM botieuchi b 
                   LEFT JOIN cauhinh_tieuchi_sk c ON b.idBoTieuChi = c.idBoTieuChi AND c.idSK = $id_su_kien
                   WHERE b.idBoTieuChi = $idBo LIMIT 1";
    $res_master = mysqli_query($conn, $sql_master);
    $master = $res_master ? mysqli_fetch_assoc($res_master) : null;

    // Lấy danh sách Tiêu chí con
    $sql_details = "SELECT t.noiDungTieuChi, bt.diemToiDa, bt.tyTrong
                    FROM botieuchi_tieuchi bt
                    JOIN tieuchi t ON bt.idTieuChi = t.idTieuChi
                    WHERE bt.idBoTieuChi = $idBo";
    $res_details = mysqli_query($conn, $sql_details);
    $details = [];
    if ($res_details) {
        while($row = mysqli_fetch_assoc($res_details)) {
            $details[] = $row;
        }
    }
    
    echo json_encode(['master' => $master, 'details' => $details]);
    exit;
}

// ======================
// Xử lý form (Backend - Lưu mới hoặc Cập nhật)
// ======================
if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    if ($action === 'save_criteria') {
        $edit_id = (int)($data['edit_id'] ?? 0);
        $tenBo = trim($data['tenBoTieuChi'] ?? '');
        $moTaBo = trim($data['moTa'] ?? '');
        $idVongThi = (int)($data['idVongThi'] ?? 0);
        $idBoTieuChi = 0;

        if (!empty($tenBo)) {
            
            if ($edit_id > 0) {
                // TRƯỜNG HỢP: CẬP NHẬT BỘ CŨ
                $sql_update = "UPDATE botieuchi SET tenBoTieuChi = '" . chuan_hoa_chuoi_sql($conn, $tenBo) . "', moTa = '" . chuan_hoa_chuoi_sql($conn, $moTaBo) . "' WHERE idBoTieuChi = $edit_id";
                mysqli_query($conn, $sql_update);
                $idBoTieuChi = $edit_id;

                // Xóa mapping Vòng thi cũ trong sự kiện này
                mysqli_query($conn, "DELETE FROM cauhinh_tieuchi_sk WHERE idBoTieuChi = $idBoTieuChi AND idSK = $id_su_kien");
                
                // Xóa mapping tiêu chí con cũ (để chèn lại từ mảng mới)
                mysqli_query($conn, "DELETE FROM botieuchi_tieuchi WHERE idBoTieuChi = $idBoTieuChi");
            } else {
                // TRƯỜNG HỢP: TẠO BỘ MỚI
                $sql_bo = "INSERT INTO botieuchi (tenBoTieuChi, moTa) 
                           VALUES ('" . chuan_hoa_chuoi_sql($conn, $tenBo) . "', '" . chuan_hoa_chuoi_sql($conn, $moTaBo) . "')";
                mysqli_query($conn, $sql_bo);
                $idBoTieuChi = mysqli_insert_id($conn);
            }

            // Gán vào Vòng thi mới
            if ($idBoTieuChi > 0 && $idVongThi > 0) {
                mysqli_query($conn, "REPLACE INTO cauhinh_tieuchi_sk (idSK, idVongThi, idBoTieuChi) 
                                     VALUES ($id_su_kien, $idVongThi, $idBoTieuChi)");
            }

            // Xử lý chèn danh sách Tiêu chí con (Dùng chung cho cả Thêm và Sửa)
            if (!empty($_POST['tieuchi_noidung']) && is_array($_POST['tieuchi_noidung'])) {
                foreach ($_POST['tieuchi_noidung'] as $index => $noidung) {
                    $noidung = trim($noidung);
                    if (empty($noidung)) continue;

                    $diem_input = $_POST['tieuchi_diem'][$index] ?? '';
                    $diem_sql = ($diem_input === '') ? "NULL" : floatval($diem_input);
                    $tytrong = floatval($_POST['tieuchi_tytrong'][$index] ?? 1.00);

                    // Tái sử dụng ngân hàng Tiêu chí
                    $sql_check = "SELECT idTieuChi FROM tieuchi WHERE noiDungTieuChi = '" . chuan_hoa_chuoi_sql($conn, $noidung) . "' LIMIT 1";
                    $res_check = mysqli_query($conn, $sql_check);
                    
                    if ($res_check && mysqli_num_rows($res_check) > 0) {
                        $row = mysqli_fetch_assoc($res_check);
                        $idTieuChi = $row['idTieuChi'];
                    } else {
                        $sql_tc = "INSERT INTO tieuchi (noiDungTieuChi) VALUES ('" . chuan_hoa_chuoi_sql($conn, $noidung) . "')";
                        mysqli_query($conn, $sql_tc);
                        $idTieuChi = mysqli_insert_id($conn);
                    }

                    // Chèn vào bảng cấu trúc Bộ
                    if ($idTieuChi > 0) {
                        $sql_map = "INSERT INTO botieuchi_tieuchi (idBoTieuChi, idTieuChi, tyTrong, diemToiDa) 
                                    VALUES ($idBoTieuChi, $idTieuChi, $tytrong, $diem_sql)";
                        mysqli_query($conn, $sql_map);
                    }
                }
            }
        }

        header("Location: ?module=event&action=config_criteria&id=$id_su_kien");
        exit;
    }
}

// ======================
// Dữ liệu cho View
// ======================
$vong_list = mysqli_query($conn, "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC");
$nganhang_tc = mysqli_query($conn, "SELECT DISTINCT noiDungTieuChi FROM tieuchi ORDER BY noiDungTieuChi ASC");
$bo_dropdown_list = mysqli_query($conn, "SELECT idBoTieuChi, tenBoTieuChi FROM botieuchi ORDER BY tenBoTieuChi ASC");

$bo_list = mysqli_query($conn, "SELECT b.*, v.tenVongThi 
                                FROM botieuchi b
                                LEFT JOIN cauhinh_tieuchi_sk c ON b.idBoTieuChi = c.idBoTieuChi AND c.idSK = $id_su_kien
                                LEFT JOIN vongthi v ON c.idVongThi = v.idVongThi
                                WHERE c.idSK = $id_su_kien
                                ORDER BY b.idBoTieuChi DESC");


layout('header');
layout('navbar');
page('event/config_criteria', compact(
    'id_su_kien', 'vongthi_list', 'bo_tieu_chi_list',
    'cauhinh_list', 'all_tieu_chi'
));
layout('footer');
