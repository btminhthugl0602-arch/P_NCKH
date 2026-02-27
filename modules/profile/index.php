<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// Yêu cầu đăng nhập
if (empty($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header('Location: ' . _HOST_URL . '/?module=auth&action=login');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$success_msg = '';
$error_msg   = '';

// ==================== XỬ LÝ ĐỔI MẬT KHẨU ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_pw  = trim($_POST['old_password'] ?? '');
    $new_pw  = trim($_POST['new_password'] ?? '');
    $conf_pw = trim($_POST['confirm_password'] ?? '');

    $tk_r = mysqli_query($conn, "SELECT matKhau FROM taikhoan WHERE idTK = $user_id LIMIT 1");
    $tk   = mysqli_fetch_assoc($tk_r);

    if (empty($old_pw) || empty($new_pw) || empty($conf_pw)) {
        $error_msg = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($old_pw !== $tk['matKhau'] && !password_verify($old_pw, $tk['matKhau'])) {
        $error_msg = 'Mật khẩu cũ không chính xác.';
    } elseif ($new_pw !== $conf_pw) {
        $error_msg = 'Mật khẩu mới không khớp.';
    } elseif (strlen($new_pw) < 6) {
        $error_msg = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } else {
        $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE taikhoan SET matKhau = '" . mysqli_real_escape_string($conn, $new_hash) . "' WHERE idTK = $user_id");
        $_SESSION['flash_msg']  = 'Đổi mật khẩu thành công!';
        $_SESSION['flash_type'] = 'success';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// ==================== LẤY THÔNG TIN TÀI KHOẢN ====================
$sql_tk = "
    SELECT tk.idTK, tk.tenTK, tk.idLoaiTK, tk.isActive, tk.ngayTao,
           ltk.tenLoaiTK,
           COALESCE(sv.tenSV, gv.tenGV, '') AS hoTen,
           sv.MSV, sv.GPA, sv.DRL,
           COALESCE(lp.tenLop, '') AS tenLop,
           COALESCE(kh1.tenKhoa, kh2.tenKhoa, '') AS tenKhoa,
           gv.gioiTinh
    FROM taikhoan tk
    LEFT JOIN loaitaikhoan ltk ON tk.idLoaiTK = ltk.idLoaiTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
    LEFT JOIN lop lp ON sv.idLop = lp.idLop
    LEFT JOIN khoa kh1 ON sv.idKhoa = kh1.idKhoa
    LEFT JOIN khoa kh2 ON gv.idKhoa = kh2.idKhoa
    WHERE tk.idTK = $user_id
    LIMIT 1
";
$tk_res  = mysqli_query($conn, $sql_tk);
$profile = mysqli_fetch_assoc($tk_res);

if (!$profile) {
    header('Location: ' . _HOST_URL);
    exit();
}

// Lấy quyền hệ thống
$q_res  = mysqli_query($conn, "
    SELECT q.tenQuyen, q.maQuyen
    FROM taikhoan_quyen tq
    JOIN quyen q ON tq.idQuyen = q.idQuyen
    WHERE tq.idTK = $user_id
");
$quyens = [];
while ($r = mysqli_fetch_assoc($q_res)) $quyens[] = $r;

// Lấy sự kiện đã tham gia
$sk_res = mysqli_query($conn, "
    SELECT DISTINCT sk.idSK, sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, ct.tenCap
    FROM thanhviennhom tvn
    JOIN nhom nh ON tvn.idnhom = nh.idnhom
    JOIN sukien sk ON nh.idSK = sk.idSK
    LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
    WHERE tvn.idtk = $user_id AND sk.isActive = 1
    ORDER BY sk.ngayBatDau DESC
    LIMIT 20
");
$my_sks = [];
if ($sk_res) while ($r = mysqli_fetch_assoc($sk_res)) $my_sks[] = $r;

// Lấy chứng nhận
$cn_res = mysqli_query($conn, "
    SELECT cn.*, sk.tenSK
    FROM chungnhan cn
    LEFT JOIN sukien sk ON cn.idSK = sk.idSK
    WHERE cn.idTK = $user_id
    ORDER BY cn.ngayCap DESC
");
$chungnhans = [];
if ($cn_res) while ($r = mysqli_fetch_assoc($cn_res)) $chungnhans[] = $r;

// lichsu_dangnhap không tồn tại trong schema — bỏ qua
$login_logs = [];

// ==================== VIEW ====================
layout('header', ['page_title' => 'Thông tin cá nhân - ' . $profile['tenTK']]);
layout('navbar');
page('profile/index', [
    'profile'     => $profile,
    'quyens'      => $quyens,
    'my_sks'      => $my_sks,
    'chungnhans'  => $chungnhans,
    'login_logs'  => $login_logs,
    'error_msg'   => $error_msg,
    'success_msg' => $success_msg,
]);
layout('footer');
