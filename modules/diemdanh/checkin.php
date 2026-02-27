<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_phien = isset($_GET['lich']) ? (int)$_GET['lich'] : 0;
$user_id  = (int)($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    $_SESSION['redirect_after_login'] = _HOST_URL . '/?module=diemdanh&action=checkin&lich=' . $id_phien;
    header('Location: ' . _HOST_URL . '/?module=auth&action=login');
    exit;
}
if (!$id_phien) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

/* ============================================================
   LẤY THÔNG TIN PHIÊN ĐIỂM DANH
   ============================================================ */
$phien = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT pd.*, sk.tenSK, sk.idSK
     FROM phien_diemdanh pd
     JOIN sukien sk ON pd.idSK = sk.idSK
     WHERE pd.idPhienDD = $id_phien LIMIT 1"
));
if (!$phien) {
    require_once _PATH_URL . '/modules/errors/404.php';
    exit;
}

$now      = time();
$moTime   = $phien['thoiGianMo']   ? strtotime($phien['thoiGianMo'])   : 0;
$dongTime = $phien['thoiGianDong'] ? strtotime($phien['thoiGianDong']) : 0;
$dangMo   = ($moTime && $dongTime && $now >= $moTime && $now <= $dongTime);

/* ============================================================
   TOKEN 6 SỐ CỐ ĐỊNH SUỐT PHIÊN
   Seed: idPhienDD + thoiGianMo => không đổi cho đến khi BTC đóng/mở lại
   ============================================================ */
$token_6so = '';
if ($moTime) {
    $secret    = 'NCKH_DD_' . $phien['idSK'];
    $raw       = hash_hmac('sha256', $id_phien . '_' . $moTime, $secret);
    $token_6so = str_pad((string)(hexdec(substr($raw, 0, 8)) % 1000000), 6, '0', STR_PAD_LEFT);
}

/* ============================================================
   KIỂM TRA ĐÃ ĐIỂM DANH CHƯA
   ============================================================ */
$daDiemDanh = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT * FROM diemdanh WHERE idPhienDD=$id_phien AND idTK=$user_id LIMIT 1"
));

/* ============================================================
   LẤY THÔNG TIN NGƯỜI DÙNG
   ============================================================ */
$userInfo = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COALESCE(sv.tenSV, gv.tenGV, tk.tenTK) AS tenHienThi,
            COALESCE(sv.MSV, '') AS MSV
     FROM taikhoan tk
     LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
     LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
     WHERE tk.idTK = $user_id LIMIT 1"
));

/* ============================================================
   XỬ LÝ POST: Ghi nhận điểm danh
   ============================================================ */
$errors    = [];
$result_ok = false;

if (isPost() && !$daDiemDanh && $dangMo) {
    $lat       = floatval($_POST['lat']   ?? 0);
    $lng       = floatval($_POST['lng']   ?? 0);
    $token_gui = trim($_POST['token']     ?? '');

    // 1. Kiểm tra mã 6 số
    if ($token_gui !== $token_6so) {
        $errors[] = 'Mã xác nhận không đúng. Vui lòng kiểm tra lại mã trên màn hình chiếu.';
    }

    // 2. Kiểm tra GPS nếu phiên có tọa độ — bắt buộc phải trong bán kính
    $coToaDo = ($phien['viTriLat'] && $phien['viTriLng']);
    if (empty($errors) && $coToaDo) {
        if ($lat == 0 && $lng == 0) {
            $errors[] = 'Không lấy được vị trí GPS. Vui lòng cấp quyền vị trí cho trình duyệt rồi thử lại.';
        } else {
            $R    = 6371000;
            $dLat = deg2rad($lat - $phien['viTriLat']);
            $dLng = deg2rad($lng - $phien['viTriLng']);
            $a    = sin($dLat / 2) * sin($dLat / 2)
                + cos(deg2rad($phien['viTriLat'])) * cos(deg2rad($lat))
                * sin($dLng / 2) * sin($dLng / 2);
            $khoangCach = $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
            $banKinh    = $phien['banKinhDiemDanh'] ?: 150;
            if ($khoangCach > $banKinh) {
                $errors[] = sprintf(
                    'Bạn đang cách địa điểm tổ chức %.0f m (giới hạn %d m). Hãy đến gần hơn và thử lại.',
                    $khoangCach,
                    $banKinh
                );
            }
        }
    }

    if (empty($errors)) {
        $nhomInfo = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT n.idnhom FROM nhom n
             JOIN thanhviennhom tv ON n.idnhom = tv.idnhom
             WHERE tv.idtk=$user_id AND n.idSK={$phien['idSK']} AND n.isActive=1 AND tv.trangthai=1
             LIMIT 1"
        ));
        $idNhomInsert = $nhomInfo ? $nhomInfo['idnhom'] : 'NULL';
        $latDb  = ($lat  && $coToaDo) ? $lat  : 'NULL';
        $lngDb  = ($lng  && $coToaDo) ? $lng  : 'NULL';
        $ip     = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
        $method = ($lat && $lng && $coToaDo) ? 'GPS' : 'QR';

        mysqli_query(
            $conn,
            "INSERT INTO diemdanh
                (idPhienDD, idTK, idNhom, hienDien, phuongThuc, viTriLat, viTriLng, ipDiemDanh, ghiChu)
             VALUES ($id_phien, $user_id, $idNhomInsert, 1, '$method', $latDb, $lngDb, '$ip', 'Tự điểm danh')"
        );
        $daDiemDanh = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT * FROM diemdanh WHERE idPhienDD=$id_phien AND idTK=$user_id LIMIT 1"
        ));
        $result_ok = true;
    }
}


layout('header', $data ?? []);
layout('navbar');
page('diemdanh/checkin', get_defined_vars());
layout('footer');
