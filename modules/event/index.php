<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

$user_id   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_role = isset($_SESSION['role'])    ? $_SESSION['role']          : 'guest';

$event_created = (bool)getSessionFlash('event_created');
$event_error   = '';

$can_create_event = ($user_id > 0)
    ? kiem_tra_quyen_he_thong($conn, $user_id, 'tao_su_kien')
    : false;

// ── XỬ LÝ TẠO SỰ KIỆN ──────────────────────────────────────────────
if (isPost() && isset($_POST['action_type']) && $can_create_event) {
    requireCSRF();
    $filterData = filter();

    $action_type = in_array($filterData['action_type'] ?? '', ['draft', 'publish'])
        ? $filterData['action_type'] : 'draft';
    $isActive = ($action_type === 'publish') ? 1 : 0;

    $tenSK = trim($filterData['tenSK'] ?? '');
    $moTa  = trim($filterData['moTa']  ?? '');
    $idCap = !empty($filterData['idCap']) ? (int)$filterData['idCap'] : null;

    $parse_dt = fn($v) => !empty($v) ? date('Y-m-d H:i:s', strtotime($v)) : '';

    $ngayMoDangKy   = $parse_dt($filterData['ngayMoDangKy']   ?? '');
    $ngayDongDangKy = $parse_dt($filterData['ngayDongDangKy'] ?? '');
    $ngayBatDau     = $parse_dt($filterData['ngayBatDau']     ?? '');
    $ngayKetThuc    = $parse_dt($filterData['ngayKetThuc']    ?? '');

    $errors = [];
    if ($tenSK === '') {
        $errors[] = 'Tên sự kiện không được để trống.';
    } elseif (mb_strlen($tenSK) > 300) {
        $errors[] = 'Tên sự kiện không được vượt quá 300 ký tự.';
    }
    if (!empty($ngayMoDangKy) && !empty($ngayDongDangKy)
        && strtotime($ngayMoDangKy) >= strtotime($ngayDongDangKy)) {
        $errors[] = 'Ngày mở đăng ký phải trước ngày đóng đăng ký.';
    }
    if (!empty($ngayBatDau) && !empty($ngayKetThuc)
        && strtotime($ngayBatDau) >= strtotime($ngayKetThuc)) {
        $errors[] = 'Ngày bắt đầu phải trước ngày kết thúc.';
    }

    if (!empty($errors)) {
        $event_error = implode('<br>', $errors);
    } else {
        $result = btc_tao_su_kien(
            $conn, $user_id, $tenSK, $moTa, $idCap,
            $ngayMoDangKy   ?: null,
            $ngayDongDangKy ?: null,
            $ngayBatDau     ?: null,
            $ngayKetThuc    ?: null,
            $isActive
        );
        if ($result['status']) {
            setSessionFlash('event_created', true);
            header('Location: ' . _HOST_URL . '/?module=event&action=view&id=' . $result['idSK']);
            exit;
        }
        $event_error = $result['message'] ?? 'Không tạo được sự kiện.';
    }
}

// ── LỌC + TÌM KIẾM ─────────────────────────────────────────────────
$search      = isset($_GET['search'])      ? trim($_GET['search'])               : '';
$search_safe = $search !== ''              ? chuan_hoa_chuoi_sql($conn, $search) : '';
$filter_cap  = isset($_GET['filter_cap'])  ? (int)$_GET['filter_cap']            : 0;
$filter_time = isset($_GET['filter_time']) ? trim($_GET['filter_time'])           : '';

// ── PAGINATION ──────────────────────────────────────────────────────
$per_page = 8;
$cur_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// ── WHERE CLAUSE — BTC thấy sự kiện ẩn của mình ────────────────────
if ($user_id > 0) {
    $where = "WHERE (sk.isActive = 1
               OR (sk.isActive = 0 AND EXISTS (
                   SELECT 1 FROM taikhoan_vaitro_sukien tvs
                   WHERE tvs.idSK = sk.idSK AND tvs.idTK = $user_id AND tvs.isActive = 1
               )))";
} else {
    $where = "WHERE sk.isActive = 1";
}

if (!empty($search_safe)) {
    $where .= " AND (sk.tenSK LIKE '%$search_safe%' OR sk.moTa LIKE '%$search_safe%')";
}
if ($filter_cap > 0) {
    $where .= " AND sk.idCap = $filter_cap";
}

$valid_times = ['today', 'week', 'month', 'quarter'];
if (in_array($filter_time, $valid_times, true)) {
    $where .= match($filter_time) {
        'today'   => " AND DATE(sk.ngayBatDau) = CURDATE()",
        'week'    => " AND sk.ngayBatDau BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)",
        'month'   => " AND sk.ngayBatDau BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 MONTH)",
        'quarter' => " AND sk.ngayBatDau BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 MONTH)",
    };
}

// ── QUERY ───────────────────────────────────────────────────────────
$cnt_res     = mysqli_query($conn, "SELECT COUNT(*) as c FROM sukien sk $where");
$total_count = (int)mysqli_fetch_assoc($cnt_res)['c'];
$total_pages = max(1, ceil($total_count / $per_page));
$cur_page    = min($cur_page, $total_pages);
$offset      = ($cur_page - 1) * $per_page;

$sql = "SELECT sk.*, ct.tenCap, lc.tenLoaiCap, tk.tenTK as nguoiTaoTen
        FROM sukien sk
        LEFT JOIN cap_tochuc ct ON sk.idCap     = ct.idCap
        LEFT JOIN loaicap lc    ON ct.idLoaiCap = lc.idLoaiCap
        LEFT JOIN taikhoan tk   ON sk.nguoiTao  = tk.idTK
        $where
        ORDER BY
            CASE
                WHEN sk.ngayMoDangKy IS NOT NULL AND sk.ngayDongDangKy IS NOT NULL
                     AND NOW() BETWEEN sk.ngayMoDangKy AND sk.ngayDongDangKy THEN 1
                WHEN sk.ngayMoDangKy IS NOT NULL AND NOW() < sk.ngayMoDangKy  THEN 2
                WHEN sk.ngayDongDangKy IS NOT NULL AND NOW() > sk.ngayDongDangKy THEN 3
                ELSE 4
            END,
            sk.idSK DESC
        LIMIT $per_page OFFSET $offset";

$result = mysqli_query($conn, $sql);
$events = [];
while ($result && $row = mysqli_fetch_assoc($result)) $events[] = $row;

// ── DỮ LIỆU FORM ────────────────────────────────────────────────────
$loaicaps    = [];
$caps_by_loai = [];
$all_caps    = [];

$res_loaicap = mysqli_query($conn, "SELECT * FROM loaicap ORDER BY idLoaiCap");
while ($res_loaicap && $row = mysqli_fetch_assoc($res_loaicap)) $loaicaps[] = $row;

$res_all_cap = mysqli_query($conn, "SELECT * FROM cap_tochuc ORDER BY idLoaiCap, tenCap");
while ($res_all_cap && $row = mysqli_fetch_assoc($res_all_cap)) {
    $all_caps[]                        = $row;
    $caps_by_loai[$row['idLoaiCap']][] = $row;
}

// ── TEMPLATES (sự kiện cũ để sao chép) ─────────────────────────────
$templates = [];
if ($can_create_event && $user_id > 0) {
    $res_tpl = mysqli_query($conn,
        "SELECT sk.idSK, sk.tenSK, sk.moTa, sk.idCap, ct.idLoaiCap
         FROM sukien sk
         LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
         JOIN taikhoan_vaitro_sukien tvs
              ON tvs.idSK = sk.idSK AND tvs.idTK = $user_id AND tvs.isActive = 1
         ORDER BY sk.idSK DESC LIMIT 20"
    );
    while ($res_tpl && $row = mysqli_fetch_assoc($res_tpl)) $templates[] = $row;
}

// ── UPCOMING EVENTS (sidebar) ────────────────────────────────────────
$upcoming_events = [];
$res_upcoming = mysqli_query($conn,
    "SELECT sk.idSK, sk.tenSK, sk.ngayBatDau, ct.tenCap
     FROM sukien sk
     LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
     WHERE sk.isActive = 1 AND sk.ngayBatDau >= NOW()
     ORDER BY sk.ngayBatDau ASC LIMIT 5"
);
while ($res_upcoming && $row = mysqli_fetch_assoc($res_upcoming)) $upcoming_events[] = $row;

// ── LOAD VIEW ────────────────────────────────────────────────────────
$data = ['page_title' => $can_create_event ? 'Quản lý sự kiện' : 'Sự kiện'];
$active_page = 'event';

layout('header', $data);
layout('navbar');
page('event/index', compact(
    'can_create_event', 'event_error', 'event_created',
    'events', 'search', 'filter_cap', 'filter_time',
    'loaicaps', 'all_caps', 'caps_by_loai', 'templates',
    'upcoming_events', 'cur_page', 'total_pages', 'total_count',
    'per_page'
));
layout('footer');
