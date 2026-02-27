<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// =============================================
// THỐNG KÊ TỔNG HỢP
// =============================================
$now = date('Y-m-d H:i:s');

// Người dùng
$total_users    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM taikhoan"))['c'] ?? 0);
$total_sinhvien = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sinhvien"))['c'] ?? 0);
$total_giangvien = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM giangvien"))['c'] ?? 0);

// Sự kiện
$total_sk = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1"))['c'] ?? 0);

$total_sk_sap_toi = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1 AND ngayBatDau > '$now'"
))['c'] ?? 0);

$total_sk_dang_dien = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1 AND ngayBatDau <= '$now' AND (ngayKetThuc IS NULL OR ngayKetThuc >= '$now')"
))['c'] ?? 0);

$total_sk_ket_thuc = (int)(mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1 AND ngayKetThuc IS NOT NULL AND ngayKetThuc < '$now'"
))['c'] ?? 0);

// Lấy danh sách sự kiện gần đây
$sql_recent_sk = "
    SELECT sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, sk.isActive,
           ct.tenCap, tk.tenTK as nguoiTaoTen
    FROM sukien sk
    LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
    LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
    WHERE sk.isActive = 1
    ORDER BY sk.idSK DESC
    LIMIT 5
";
$result_sk = mysqli_query($conn, $sql_recent_sk);
$recent_sk = [];
while ($row = mysqli_fetch_assoc($result_sk)) $recent_sk[] = $row;

// Tài khoản mới đăng ký
$sql_recent_tk = "
    SELECT tk.idTK, tk.tenTK, tk.idLoaiTK, tk.ngayTao, ltk.tenLoaiTK,
           COALESCE(sv.tenSV, gv.tenGV, '') AS hoTen
    FROM taikhoan tk
    LEFT JOIN loaitaikhoan ltk ON tk.idLoaiTK = ltk.idLoaiTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
    ORDER BY tk.ngayTao DESC
    LIMIT 5
";
$result_tk = mysqli_query($conn, $sql_recent_tk);
$recent_tk = [];
while ($row = mysqli_fetch_assoc($result_tk)) $recent_tk[] = $row;

$data = ['page_title' => 'Dashboard - Thống kê'];

layout('header', $data);
layout('navbar');
page('admin/dashboard', compact('data', 'stats', 'recent_events', 'recent_users'));
layout('footer');
