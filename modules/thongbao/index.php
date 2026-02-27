<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');

// Yêu cầu đăng nhập
if (empty($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header('Location: ' . _HOST_URL . '/?module=auth&action=login');
    exit;
}

require_once _PATH_URL . '/modules/functions/quan_ly_thong_bao.php';

// FIX: dùng user_id (idTK cũ luôn trả 0)
$id_tk = (int)$_SESSION['user_id'];

// ==================== XỬ LÝ HÀNH ĐỘNG ====================

if (isset($_GET['doc']) && is_numeric($_GET['doc'])) {
    danh_dau_da_doc($conn, $id_tk, (int)$_GET['doc']);
    $tb_r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT idSK FROM thongbao WHERE idThongBao=" . (int)$_GET['doc'] . " LIMIT 1"
    ));
    $redirect = ($tb_r && $tb_r['idSK'])
        ? _HOST_URL . '/?module=event&action=view&id=' . (int)$tb_r['idSK']
        : _HOST_URL . '/?module=thongbao&action=index';
    header("Location: $redirect");
    exit;
}

if (isset($_GET['doc_tat_ca'])) {
    danh_dau_tat_ca_da_doc($conn, $id_tk);
    header('Location: ' . _HOST_URL . '/?module=thongbao&action=index');
    exit;
}

// ==================== QUERY DỮ LIỆU ====================

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$filter_loai = $_GET['loai'] ?? '';
$where_loai  = '';
if ($filter_loai) {
    $filter_loai_safe = mysqli_real_escape_string($conn, $filter_loai);
    $where_loai = "AND tb.loaiThongBao = '$filter_loai_safe'";
}

$sql = "
    SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao,
           tb.ngayGui, tb.idSK, sk.tenSK,
           tbn.daDoc, tbn.thoiGianDoc,
           tk_gui.tenTK as nguoiGui
    FROM thongbao_nguoinhan tbn
    JOIN thongbao tb ON tbn.idThongBao = tb.idThongBao
    LEFT JOIN sukien sk ON tb.idSK = sk.idSK
    LEFT JOIN taikhoan tk_gui ON tb.nguoiGui = tk_gui.idTK
    WHERE tbn.idTK = $id_tk $where_loai
    ORDER BY tb.ngayGui DESC
    LIMIT $per_page OFFSET $offset
";
$result      = mysqli_query($conn, $sql);
$ds_tb       = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
$so_chua_doc = dem_chua_doc($conn, $id_tk);

$total_r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM thongbao_nguoinhan tbn
     JOIN thongbao tb ON tbn.idThongBao = tb.idThongBao
     WHERE tbn.idTK = $id_tk $where_loai"
));
$total_pages = max(1, (int)ceil(($total_r['c'] ?? 0) / $per_page));

$loai_list_r = mysqli_query($conn,
    "SELECT DISTINCT tb.loaiThongBao FROM thongbao_nguoinhan tbn
     JOIN thongbao tb ON tbn.idThongBao = tb.idThongBao
     WHERE tbn.idTK = $id_tk AND tb.loaiThongBao IS NOT NULL AND tb.loaiThongBao != ''
     ORDER BY tb.loaiThongBao"
);
$loai_list = $loai_list_r ? array_column(mysqli_fetch_all($loai_list_r, MYSQLI_ASSOC), 'loaiThongBao') : [];

$icon_map = [
    'Nhóm'      => ['bi-people-fill',    'text-success',   'bg-success'],
    'Kết quả'   => ['bi-trophy-fill',    'text-warning',   'bg-warning'],
    'Chấm điểm' => ['bi-pencil-square',  'text-primary',   'bg-primary'],
    'Nhắc nhở'  => ['bi-alarm-fill',     'text-info',      'bg-info'],
    'Hệ thống'  => ['bi-gear-fill',      'text-secondary', 'bg-secondary'],
    'Chung'     => ['bi-megaphone-fill', 'text-primary',   'bg-primary'],
];

// ==================== VIEW ====================
layout('header', ['page_title' => 'Thông báo của tôi']);
layout('navbar');
page('thongbao/index', [
    'ds_tb'       => $ds_tb,
    'so_chua_doc' => $so_chua_doc,
    'filter_loai' => $filter_loai,
    'loai_list'   => $loai_list,
    'icon_map'    => $icon_map,
    'total_r'     => $total_r,
    'total_pages' => $total_pages,
    'page'        => $page,
    'id_tk'       => $id_tk,
    'conn'        => $conn,
]);
layout('footer');
