<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
global $conn;

require_once _PATH_URL . '/modules/functions/quan_ly_nhom.php';
require_once _PATH_URL . '/modules/functions/base.php';

$idNhom = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idNhom <= 0) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// ================== LẤY THÔNG TIN NHÓM + SỰ KIỆN ==================
$resNhom = mysqli_query($conn, "
    SELECT n.idnhom, n.idSK, n.idChuNhom, t.tennhom, t.mota, t.soluongtoida, t.dangtuyen,
           sk.tenSK, sk.moTa AS moTaSK, sk.ngayBatDau, sk.ngayKetThuc, sk.ngayMoDangKy, sk.ngayDongDangKy,
           COALESCE(sv_t.tenSV, gv_t.tenGV, tk_t.tenTK, '') AS tenNhomTruong
    FROM nhom n
    LEFT JOIN thongtinnhom t ON n.idnhom = t.idnhom
    LEFT JOIN sukien sk ON n.idSK = sk.idSK
    LEFT JOIN taikhoan tk_t ON n.idChuNhom = tk_t.idTK
    LEFT JOIN sinhvien sv_t ON tk_t.idTK = sv_t.idTK
    LEFT JOIN giangvien gv_t ON tk_t.idTK = gv_t.idTK
    WHERE n.idnhom = $idNhom AND n.isActive = 1
    LIMIT 1
");
if (!$resNhom || mysqli_num_rows($resNhom) == 0) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}
$nhom = mysqli_fetch_assoc($resNhom);
$idSK = (int)$nhom['idSK'];

// ================== LẤY LOẠI TÀI KHOẢN ==================
$userLoaiTK = 0;
if ($userId > 0) {
    $u = truy_van_mot_ban_ghi($conn, 'TAIKHOAN', 'idTK', $userId);
    $userLoaiTK = $u ? (int)$u['idLoaiTK'] : 0;
}
$isSinhVien = ($userId > 0 && $userLoaiTK === 3);

// ================== XÁC ĐỊNH THÀNH VIÊN / TRƯỞNG NHÓM ==================
$isMember = false;
$isTruong = false;
$memberRoleId = 0;

if ($userId > 0) {
    $resMember = mysqli_query(
        $conn,
        "SELECT idvaitronhom 
         FROM thanhviennhom 
         WHERE idnhom=$idNhom AND idtk=$userId AND trangthai=1 
         LIMIT 1"
    );
    if ($resMember && mysqli_num_rows($resMember) > 0) {
        $isMember = true;
        $memberRow = mysqli_fetch_assoc($resMember);
        $memberRoleId = (int)$memberRow['idvaitronhom'];
        if ($memberRoleId === 1) $isTruong = true;
    }
}

// Đồng bộ theo cột nhom.idChuNhom
if ($userId > 0 && (int)$nhom['idChuNhom'] === $userId) {
    $isMember = true;
    $isTruong = true;
}

// ================== HELPER: kiểm tra nhóm đã full chưa ==================
function nhom_da_full(mysqli $conn, int $idNhom, int $soLuongToiDa): bool
{
    if ($soLuongToiDa <= 0) return false;
    $res = mysqli_query(
        $conn,
        "SELECT COUNT(DISTINCT idtk) AS c 
         FROM thanhviennhom 
         WHERE idnhom=$idNhom AND trangthai=1 AND idvaitronhom != 3"
    );
    $c = ($res && ($row = mysqli_fetch_assoc($res))) ? (int)$row['c'] : 0;
    return $c >= $soLuongToiDa;
}

function require_truong_nhom(bool $isTruong)
{
    if (!$isTruong) {
        $_SESSION['flash_msg']  = 'Bạn không có quyền thực hiện thao tác này (chỉ trưởng nhóm).';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// ================== XỬ LÝ DUYỆT YÊU CẦU ==================
if (isset($_POST['duyet_yeucau'])) {
    require_truong_nhom($isTruong);

    $idYeuCau  = (int)($_POST['idYeuCau'] ?? 0);
    $trangThai = (int)($_POST['trangThai'] ?? 2);

    if ($trangThai === 1) {
        $soLuongToiDa = (int)($nhom['soluongtoida'] ?? 0);
        if (nhom_da_full($conn, $idNhom, $soLuongToiDa)) {
            $_SESSION['flash_msg']  = 'Nhóm đã đủ thành viên, không thể duyệt thêm.';
            $_SESSION['flash_type'] = 'warning';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    $result = duyet_yeu_cau_nhom($conn, $userId, $idYeuCau, $trangThai);
    if (is_array($result) && isset($result['status']) && !$result['status']) {
        $_SESSION['flash_msg']  = $result['message'] ?? 'Không xử lý được yêu cầu.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $_SESSION['flash_msg']  = 'Đã xử lý yêu cầu.';
        $_SESSION['flash_type'] = 'success';
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== XỬ LÝ XÓA THÀNH VIÊN ==================
if (isset($_POST['xoa_thanh_vien'])) {
    require_truong_nhom($isTruong);

    $idTKXoa = (int)($_POST['idTK'] ?? 0);
    if ($idTKXoa <= 0) {
        $_SESSION['flash_msg']  = 'Thiếu thông tin thành viên.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if ($idTKXoa === $userId) {
        $_SESSION['flash_msg']  = 'Bạn không thể tự xóa chính mình bằng chức năng này.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $result = roi_nhom($conn, $userId, $idNhom, $idTKXoa);
    if (is_array($result) && isset($result['status']) && !$result['status']) {
        $_SESSION['flash_msg']  = $result['message'] ?? 'Không thể xóa thành viên.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $_SESSION['flash_msg']  = 'Đã xóa thành viên khỏi nhóm.';
        $_SESSION['flash_type'] = 'success';
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== XỬ LÝ RỜI NHÓM ==================
if (isset($_POST['roi_nhom'])) {
    if (!$isMember) {
        $_SESSION['flash_msg']  = 'Bạn không phải thành viên nhóm.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if ($isTruong) {
        $_SESSION['flash_msg']  = 'Trưởng nhóm không thể rời nhóm. Hãy nhượng quyền trước.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $result = roi_nhom($conn, $userId, $idNhom, $userId);
    if (is_array($result) && isset($result['status']) && $result['status']) {
        $_SESSION['flash_msg']  = 'Bạn đã rời nhóm thành công.';
        $_SESSION['flash_type'] = 'success';
        header("Location: " . _HOST_URL . "?module=event&action=view&id=" . $idSK);
        exit();
    } else {
        $_SESSION['flash_msg']  = (is_array($result) ? ($result['message'] ?? '') : '') ?: 'Không thể rời nhóm.';
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// ================== XỬ LÝ NHƯỢNG QUYỀN TRƯỞNG NHÓM ==================
if (isset($_POST['nhuong_quyen'])) {
    require_truong_nhom($isTruong);

    $idTKMoi = (int)($_POST['idTKMoi'] ?? 0);
    if ($idTKMoi <= 0 || $idTKMoi === $userId) {
        $_SESSION['flash_msg']  = 'Người nhận quyền không hợp lệ.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $checkTV = mysqli_query(
        $conn,
        "SELECT idvaitronhom 
         FROM thanhviennhom 
         WHERE idnhom=$idNhom AND idtk=$idTKMoi AND trangthai=1 
         LIMIT 1"
    );
    if (!$checkTV || mysqli_num_rows($checkTV) == 0) {
        $_SESSION['flash_msg']  = 'Người được chọn không phải thành viên nhóm.';
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $tvRow = mysqli_fetch_assoc($checkTV);
    if ((int)$tvRow['idvaitronhom'] === 3) {
        $_SESSION['flash_msg']  = 'Không thể nhượng quyền cho Giảng viên hướng dẫn.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    mysqli_query($conn, "UPDATE thanhviennhom SET idvaitronhom=2 WHERE idnhom=$idNhom AND idtk=$userId AND trangthai=1");
    mysqli_query($conn, "UPDATE thanhviennhom SET idvaitronhom=1 WHERE idnhom=$idNhom AND idtk=$idTKMoi AND trangthai=1");
    mysqli_query($conn, "UPDATE nhom SET idChuNhom=$idTKMoi WHERE idnhom=$idNhom");

    $_SESSION['flash_msg']  = 'Đã nhượng quyền trưởng nhóm thành công.';
    $_SESSION['flash_type'] = 'success';
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== XỬ LÝ CẬP NHẬT NHÓM ==================
if (isset($_POST['cap_nhat_nhom'])) {
    require_truong_nhom($isTruong);

    $tenNhomMoi  = mysqli_real_escape_string($conn, trim($_POST['tennhom'] ?? ''));
    $motaMoi     = mysqli_real_escape_string($conn, trim($_POST['mota'] ?? ''));
    $dangTuyen   = isset($_POST['dangtuyen']) ? 1 : 0;

    if ($tenNhomMoi === '') {
        $_SESSION['flash_msg']  = 'Tên nhóm không được để trống.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    mysqli_query($conn, "UPDATE thongtinnhom SET tennhom='$tenNhomMoi', mota='$motaMoi', dangtuyen=$dangTuyen WHERE idnhom=$idNhom");

    $_SESSION['flash_msg']  = 'Đã cập nhật thông tin nhóm thành công.';
    $_SESSION['flash_type'] = 'success';
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== AJAX: MỜI THÀNH VIÊN / GVHD ==================
if (isset($_POST['ajax_action']) && $userId > 0) {
    header('Content-Type: application/json; charset=utf-8');
    $ajax_action = $_POST['ajax_action'];

    if (!$isTruong) {
        echo json_encode(['status' => false, 'message' => 'Không có quyền thực hiện'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($ajax_action === 'moi_thanh_vien') {
        $idTKMoi = (int)($_POST['idSV'] ?? 0);
        $loiNhan = trim($_POST['loiNhan'] ?? '');
        $result = gui_yeu_cau_nhom($conn, $idNhom, $idTKMoi, 0, $loiNhan);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($ajax_action === 'moi_gvhd') {
        $idTKMoi = (int)($_POST['idGV'] ?? 0);
        $loiNhan = trim($_POST['loiNhan'] ?? '');
        $result = gui_yeu_cau_nhom($conn, $idNhom, $idTKMoi, 0, $loiNhan);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit();
    }

    echo json_encode(['status' => false, 'message' => 'Hành động không hợp lệ'], JSON_UNESCAPED_UNICODE);
    exit();
}

// ================== XỬ LÝ NỘP BÀI ==================
if (isset($_POST['nop_bai'])) {
    if (!$isMember) {
        $_SESSION['flash_msg']  = 'Bạn không phải thành viên nhóm nên không thể nộp bài.';
        $_SESSION['flash_type'] = 'warning';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $tenDeTai   = mysqli_real_escape_string($conn, trim($_POST['tenDeTai'] ?? ''));
    $idChuDeRaw = (int)($_POST['idChuDe'] ?? 0);
    $chuDeSQL   = $idChuDeRaw > 0 ? $idChuDeRaw : 'NULL';

    $uploadDir = _PATH_URL . '/uploads/sanpham/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowed = ['pdf', 'doc', 'docx', 'zip', 'rar', 'pptx'];
    $uploaded = [];

    if (!empty($_FILES['files']['name'][0])) {
        $fileCount = count($_FILES['files']['name']);
        $docIndex = 1;
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($_FILES['files']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;
            if ($_FILES['files']['size'][$i] > 20 * 1024 * 1024) continue;

            if (in_array($ext, ['zip', 'rar'])) {
                $idLoai = 3;
            } else {
                $idLoai = $docIndex <= 1 ? 1 : 2;
                $docIndex++;
            }

            $originalName = pathinfo($_FILES['files']['name'][$i], PATHINFO_FILENAME);
            $safeOriginal = preg_replace('/[^a-zA-Z0-9_\-\.\x{0080}-\x{FFFF}]/u', '_', $originalName);
            // Fix: Added index $i to prevent file overwrite when uploading multiple files simultaneously
            $newName = $safeOriginal . '_' . time() . '_' . $i . '.' . $ext;

            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $uploadDir . $newName)) {
                $uploaded[$idLoai] = mysqli_real_escape_string($conn, 'uploads/sanpham/' . $newName);
            }
        }
    }

    if ($tenDeTai !== '') {
        mysqli_query($conn, "UPDATE sanpham SET tensanpham='$tenDeTai', idChuDeSK=$chuDeSQL WHERE idNhom=$idNhom AND idSK=$idSK");

        foreach ($uploaded as $idLoai => $filePath) {
            $check = mysqli_query($conn, "SELECT idSanPham FROM sanpham WHERE idNhom=$idNhom AND idSK=$idSK AND idloaitailieu=$idLoai LIMIT 1");
            if ($check && mysqli_num_rows($check) > 0) {
                $row = mysqli_fetch_assoc($check);
                mysqli_query($conn, "UPDATE sanpham SET moTataiLieu='$filePath' WHERE idSanPham=" . (int)$row['idSanPham']);
            } else {
                mysqli_query(
                    $conn,
                    "INSERT INTO sanpham (idNhom,idSK,idChuDeSK,idloaitailieu,moTataiLieu,TrangThai,isActive,tensanpham)
                     VALUES ($idNhom,$idSK,$chuDeSQL,$idLoai,'$filePath','Chờ duyệt',1,'$tenDeTai')"
                );
            }
        }

        $_SESSION['flash_msg']  = 'nop_bai_thanh_cong';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_msg']  = 'Tên đề tài không được để trống.';
        $_SESSION['flash_type'] = 'warning';
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ================== LẤY DỮ LIỆU HIỂN THỊ ==================
$resTV = mysqli_query($conn, "
    SELECT tv.idtk, tv.idvaitronhom,
           COALESCE(sv.tenSV, gv2.tenGV, tk.tenTK) AS tenTV,
           vn.tenvaitronhom AS vaiTro, tk.idLoaiTK
    FROM thanhviennhom tv
    JOIN taikhoan tk ON tv.idtk = tk.idTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
    LEFT JOIN vaitronhom vn ON tv.idvaitronhom = vn.id
    WHERE tv.idnhom = $idNhom AND tv.trangthai = 1
    ORDER BY tv.idvaitronhom ASC
");
$danhSachTV = $resTV ? mysqli_fetch_all($resTV, MYSQLI_ASSOC) : [];
$soThanhVien = count(array_filter($danhSachTV, fn($tv) => (int)$tv['idvaitronhom'] != 3));

$resGVHD = mysqli_query($conn, "
    SELECT COALESCE(gv2.tenGV, tk.tenTK) AS tenGVHD, tv.idtk
    FROM thanhviennhom tv
    JOIN taikhoan tk ON tv.idtk = tk.idTK
    LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
    WHERE tv.idnhom = $idNhom AND tv.idvaitronhom = 3 AND tv.trangthai = 1
    LIMIT 1
");
$gvhd = ($resGVHD && mysqli_num_rows($resGVHD) > 0) ? mysqli_fetch_assoc($resGVHD) : null;

$resYC = mysqli_query($conn, "
    SELECT yc.idYeuCau, yc.ChieuMoi, yc.loiNhan, yc.ngayGui,
           COALESCE(sv.tenSV, gv2.tenGV, tk.tenTK) AS tenNguoiGui,
           tk.idLoaiTK
    FROM yeucau_thamgia yc
    JOIN taikhoan tk ON yc.idTK = tk.idTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv2 ON tk.idTK = gv2.idTK
    WHERE yc.idNhom = $idNhom AND yc.trangThai = 0
    ORDER BY yc.ngayGui DESC
");
$yeuCauCho = $resYC ? mysqli_fetch_all($resYC, MYSQLI_ASSOC) : [];
$soYC = count($yeuCauCho);

$resPGV = mysqli_query($conn, "
    SELECT yc.idYeuCau FROM yeucau_thamgia yc
    JOIN taikhoan tk ON yc.idTK = tk.idTK
    WHERE yc.idNhom = $idNhom AND yc.trangThai = 0 AND tk.idLoaiTK = 2
    LIMIT 1
");
$pendingGVHD = ($resPGV && mysqli_num_rows($resPGV) > 0);

$resSV = mysqli_query($conn, "SELECT sv.idTK, sv.tenSV, sv.MSV, l.tenLop FROM sinhvien sv JOIN taikhoan tk ON sv.idTK=tk.idTK LEFT JOIN lop l ON sv.idLop=l.idLop WHERE tk.isActive=1 ORDER BY sv.tenSV");
$sv_list = $resSV ? mysqli_fetch_all($resSV, MYSQLI_ASSOC) : [];
$dsMaTK = array_column($danhSachTV, 'idtk');

$resGV = mysqli_query($conn, "SELECT gv.idTK, gv.tenGV, k.tenKhoa FROM giangvien gv JOIN taikhoan tk ON gv.idTK=tk.idTK LEFT JOIN khoa k ON gv.idKhoa=k.idKhoa WHERE tk.isActive=1 ORDER BY gv.tenGV");
$gv_list = $resGV ? mysqli_fetch_all($resGV, MYSQLI_ASSOC) : [];

$resCD = mysqli_query($conn, "SELECT cs.idChuDeSK, c.tenChuDe FROM chude_sukien cs JOIN chude c ON cs.idchude=c.idChuDe WHERE cs.idSK=$idSK AND cs.isActive=1");
$chude_list = $resCD ? mysqli_fetch_all($resCD, MYSQLI_ASSOC) : [];

$resSP = mysqli_query($conn, "SELECT sp.*, l.loaitailieu AS tenLoaiTL FROM sanpham sp LEFT JOIN loaitailieu l ON sp.idloaitailieu=l.idtailieu WHERE sp.idNhom=$idNhom AND sp.idSK=$idSK ORDER BY sp.idloaitailieu ASC");
$spRows = $resSP ? mysqli_fetch_all($resSP, MYSQLI_ASSOC) : [];
$sanPhamTheoLoai = [];
foreach ($spRows as $row) {
    $sanPhamTheoLoai[$row['idloaitailieu']] = $row;
}
$sanPham = !empty($spRows) ? $spRows[0] : null;

$flashMsg  = $_SESSION['flash_msg']  ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$data = ['page_title' => 'Chi tiết nhóm: ' . htmlspecialchars($nhom['tennhom'] ?? '')];

layout('header', $data);
layout('navbar');
page('event/chitiethom', compact(
    'id_su_kien', 'id_nhom', 'nhom', 'thongtin_nhom',
    'thanh_vien', 'sanpham_list', 'yeucau_list',
    'quyen_btc', 'user_id'
));
layout('footer');
