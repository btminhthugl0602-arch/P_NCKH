<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';
require_once _PATH_URL . '/modules/functions/quan_ly_vong_thi.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$event_message = '';
$event_error = '';

// Lấy chi tiết sự kiện
$event = $id_su_kien > 0 ? btc_lay_chi_tiet_su_kien($conn, $id_su_kien) : null;

// Lấy danh sách cấp tổ chức
$cap_conditions = [
    'ORDER BY' => ['tenCap', 'ASC', '', '']
];
$caps = _select_info($conn, 'cap_tochuc', [], $cap_conditions);
if (!$caps) {
    $caps = [];
}

// ======================
// Xử lý form
// ======================
if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    // Cập nhật thông tin sự kiện
    if ($action === 'update_event') {
        $tenSK = trim($data['tenSK'] ?? '');
        $moTa = trim($data['moTa'] ?? '');
        $idCap = (int)($data['idCap'] ?? 0);
        $isActive = isset($data['isActive']) ? (int)$data['isActive'] : 1;

        $ngayMoDangKy = !empty($data['ngayMoDangKy']) ? date('Y-m-d H:i:s', strtotime($data['ngayMoDangKy'])) : '';
        $ngayDongDangKy = !empty($data['ngayDongDangKy']) ? date('Y-m-d H:i:s', strtotime($data['ngayDongDangKy'])) : '';
        $ngayBatDau = !empty($data['ngayBatDau']) ? date('Y-m-d H:i:s', strtotime($data['ngayBatDau'])) : '';
        $ngayKetThuc = !empty($data['ngayKetThuc']) ? date('Y-m-d H:i:s', strtotime($data['ngayKetThuc'])) : '';

        $errors = [];

        if ($tenSK === '') {
            $errors[] = 'Tên sự kiện không được để trống.';
        }
        if ($idCap <= 0) {
            $errors[] = 'Vui lòng chọn cấp tổ chức.';
        }
        if (empty($ngayMoDangKy) || empty($ngayDongDangKy) || empty($ngayBatDau) || empty($ngayKetThuc)) {
            $errors[] = 'Vui lòng nhập đầy đủ thời gian.';
        } else {
            $now = strtotime(date('Y-m-d'));
            $mo = strtotime($ngayMoDangKy);
            $dong = strtotime($ngayDongDangKy);
            $bat = strtotime($ngayBatDau);
            $ket = strtotime($ngayKetThuc);

            if ($mo < $now) {
                $errors[] = 'Ngày mở đăng ký phải từ hôm nay trở đi.';
            }
            if ($mo >= $dong) {
                $errors[] = 'Ngày mở đăng ký phải nhỏ hơn ngày đóng đăng ký.';
            }
            if ($bat < $mo) {
                $errors[] = 'Ngày bắt đầu phải lớn hơn hoặc bằng ngày mở đăng ký.';
            }
            if ($ket < $bat) {
                $errors[] = 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_msg']  = implode('<br>', $errors);
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $result = btc_cap_nhat_su_kien(
                $conn,
                isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0,
                $id_su_kien,
                $tenSK,
                $moTa,
                $idCap,
                $ngayMoDangKy,
                $ngayDongDangKy,
                $ngayBatDau,
                $ngayKetThuc,
                $isActive
            );

            if ($result['status']) {
                $_SESSION['flash_msg']  = $result['message'] ?? 'Cập nhật thành công';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_msg']  = $result['message'] ?? 'Cập nhật thất bại';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    // Tạo vòng thi
    if ($action === 'create_round') {
        $ten = $data['tenVongThi'] ?? '';
        $moTa = $data['moTaVong'] ?? '';
        $thuTu = (int)($data['thuTu'] ?? 1);
        $ngayBatDau = $data['ngayBatDauVong'] ?? null;
        $ngayKetThuc = $data['ngayKetThucVong'] ?? null;

        if (!empty($ten) && $id_su_kien > 0) {
            $result = tao_vong_thi(
                $conn,
                isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0,
                $id_su_kien,
                $ten,
                $moTa,
                $thuTu,
                $ngayBatDau,
                $ngayKetThuc
            );

            if ($result['status']) {
                $_SESSION['flash_msg']  = $result['message'] ?? 'Tạo vòng thi thành công';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_msg']  = $result['message'] ?? 'Không thể tạo vòng thi';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        header("Location: ?module=event&action=config_rounds&id=$id_su_kien");
        exit;
    }
}

// Danh sách vòng thi
$vongthi_list = lay_ds_vong_thi($conn, $id_su_kien);
if (!$vongthi_list) {
    $vongthi_list = [];
}


layout('header');
layout('navbar');
page('event/config_rounds', compact('event', 'caps', 'vongthi_list', 'id_su_kien'));
layout('footer');
