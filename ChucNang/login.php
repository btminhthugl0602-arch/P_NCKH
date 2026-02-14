<?php
// 1. Bảo mật: Chặn truy cập trực tiếp
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

$tb_dang_nhap = "";

if (isset($_GET['guest']) && $_GET['guest'] == '1') {
    $_SESSION['user_id'] = 0;
    $_SESSION['user_name'] = 'Khách';
    $_SESSION['role'] = 'guest';
    header("Location: " . _HOST_URL . "?module=dashboard&action=index");
    exit();
}

if (isset($_POST['btn_dang_nhap'])) {
    $ten_tk = isset($_POST['tendangnhap']) ? chuan_hoa_chuoi_sql($conn, $_POST['tendangnhap']) : "";
    $mat_khau = isset($_POST['matkhau']) ? $_POST['matkhau'] : "";

    if ($ten_tk == "" || $mat_khau == "") {
        $tb_dang_nhap = "Vui lòng nhập đầy đủ thông tin!!";
    } else {
        $row = truy_van_mot_ban_ghi($conn, 'taikhoan', 'tenTK', $ten_tk);

        if ($row) {
            if ($mat_khau == $row['matKhau']) {
                if ($row['isActive'] == 0) {
                    $tb_dang_nhap = "Tài khoản của bạn đã bị khóa.";
                } else {
                    $_SESSION['user_id'] = $row['idTK'];
                    $_SESSION['user_name'] = $row['tenTK'];
                    $_SESSION['role'] = $row['idLoaiTK'];
                    $_SESSION['login_success'] = true;

                    if ($_SESSION['role'] == 1) {
                        header("Location: " . _HOST_URL . "?module=admin&action=dashboard"); 
                    } else {
                        header("Location: " . _HOST_URL . "?module=dashboard&action=index");
                    }
                    exit();
                }
            } else {
                $tb_dang_nhap = "Mật khẩu không chính xác";
            }
        } else {
            $tb_dang_nhap = "Tên đăng nhập không tồn tại";
        }
    }
}
?>