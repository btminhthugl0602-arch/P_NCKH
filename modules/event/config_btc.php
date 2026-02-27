<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_to_chuc.php';

$id_sk  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_ttk = (int)($_SESSION['user_id'] ?? 0);

// Guard: chỉ BTC / admin mới vào được
if (
    !kiem_tra_quyen_su_kien($conn, $id_ttk, $id_sk, 'cauhinh_sukien')
    && !kiem_tra_quyen_he_thong($conn, $id_ttk, 'tao_su_kien')
) {
    header('Location: ?module=event&action=view&id=' . $id_sk);
    exit;
}

$success_msg = '';
$error_msg   = '';

// ============================================================
// XỬ LÝ POST
// ============================================================
if (isPost()) {
    $data   = filter();
    $action = $data['action'] ?? '';

    if ($action === 'them_vaitro') {
        $id_tk_target = (int)($data['idTK'] ?? 0);
        $id_vaitro    = (int)($data['idVaiTro'] ?? 0);
        if ($id_tk_target > 0 && $id_vaitro > 0) {
            $result = them_vaitro_sukien($conn, $id_ttk, $id_sk, $id_tk_target, $id_vaitro);
            $result['status'] ? ($success_msg = $result['message']) : ($error_msg = $result['message']);
        } else {
            $error_msg = 'Vui lòng chọn đầy đủ người dùng và vai trò.';
        }
    }

    if ($action === 'thu_hoi') {
        $id_tk_target = (int)($data['idTK'] ?? 0);
        $id_vaitro    = (int)($data['idVaiTro'] ?? 0);
        if ($id_tk_target > 0 && $id_vaitro > 0) {
            $result = thu_hoi_vaitro_btc($conn, $id_ttk, $id_sk, $id_tk_target, $id_vaitro);
            $result['status'] ? ($success_msg = $result['message']) : ($error_msg = $result['message']);
        }
    }
}

// ============================================================
// DỮ LIỆU ĐỔ RA GIAO DIỆN
// ============================================================
$sk = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_sk);
if (!$sk) {
    header('Location: ?module=event&action=index');
    exit;
}

$ds_thanh_vien  = lay_danh_sach_thanh_vien_sukien($conn, $id_sk);
$ds_vaitro_gan  = lay_vaitro_btc_co_the_gan($conn);

// Tìm kiếm tài khoản (AJAX) — nhận ?search_user=keyword, trả JSON
if (isset($_GET['search_user'])) {
    header('Content-Type: application/json');
    $kw   = '%' . trim($_GET['search_user']) . '%';
    $stmt = mysqli_prepare(
        $conn,
        "SELECT tk.idTK,
                COALESCE(gv.tenGV, sv.tenSV, tk.tenTK) AS tenHienThi,
                tk.tenTK AS email,
                CASE tk.idLoaiTK WHEN 1 THEN 'Admin' WHEN 2 THEN 'GV' ELSE 'SV' END AS loai
         FROM taikhoan tk
         LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
         LEFT JOIN sinhvien  sv ON tk.idTK = sv.idTK
         WHERE tk.isActive = 1
           AND (COALESCE(gv.tenGV, sv.tenSV, tk.tenTK) LIKE ? OR tk.tenTK LIKE ?)
         LIMIT 15"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $kw, $kw);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    echo json_encode($rows);
    exit;
}

// Nhãn nguonTao thân thiện
function nhan_nguon_tao(string $nguon): string
{
    return match ($nguon) {
        'BTC_THEM'      => '<span class="badge bg-primary">BTC gán</span>',
        'DANG_KY'       => '<span class="badge bg-success">Đăng ký</span>',
        'QUA_NHOM'      => '<span class="badge bg-info text-dark">Qua nhóm</span>',
        'PHANCONG_CHAM' => '<span class="badge bg-warning text-dark">P/c chấm</span>',
        default         => '<span class="badge bg-secondary">' . htmlspecialchars($nguon) . '</span>',
    };
}


layout('header');
layout('navbar');
page('event/config_btc', compact(
    'id_su_kien', 'event', 'btc_list', 'vaitro_list', 'gv_sv_list'
));
layout('footer');
