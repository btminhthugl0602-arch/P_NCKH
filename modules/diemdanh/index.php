<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_btc  = kiem_tra_quyen_he_thong($conn, $user_id, 'event.manage');

if (!$is_btc) {
    $_SESSION['flash_msg']  = 'Bạn không có quyền truy cập trang này.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . _HOST_URL . '/');
    exit;
}

$id_su_kien = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$id_phien   = isset($_GET['lich']) ? (int)$_GET['lich'] : 0;

/* ============================================================
   XỬ LÝ POST
   ============================================================ */
if ($is_btc && isPost()) {
    $act = $_POST['act'] ?? '';

    // --- Tạo phiên điểm danh mới ---
    if ($act === 'tao_phien') {
        $tenPhien    = trim($_POST['tenPhien']   ?? '');
        $thoiGianBD  = trim($_POST['thoiGian']   ?? '');
        $lat         = floatval($_POST['lat']     ?? 0);
        $lng         = floatval($_POST['lng']     ?? 0);
        $banKinh     = max(50, intval($_POST['banKinh'] ?? 150));
        $idSKPost    = (int)($_POST['idSK']       ?? 0);
        $idVongPost  = (int)($_POST['idVongThi']  ?? 0) ?: null;
        $idLichPost  = (int)($_POST['idLichTrinh'] ?? 0) ?: null;

        if ($tenPhien && $idSKPost) {
            $latDb      = $lat  ? $lat  : 'NULL';
            $lngDb      = $lng  ? $lng  : 'NULL';
            $thoiGianDb = $thoiGianBD ? "'" . mysqli_real_escape_string($conn, $thoiGianBD) . "'" : 'NULL';
            $tenPhienDb = mysqli_real_escape_string($conn, $tenPhien);
            $idVongDb   = $idVongPost  ? $idVongPost  : 'NULL';
            $idLichDb   = $idLichPost  ? $idLichPost  : 'NULL';

            $sql = "INSERT INTO phien_diemdanh
                        (idSK, idVongThi, idLichTrinh, tenPhien, thoiGianBatDau,
                         viTriLat, viTriLng, banKinhDiemDanh)
                    VALUES ($idSKPost, $idVongDb, $idLichDb, '$tenPhienDb', $thoiGianDb,
                            $latDb, $lngDb, $banKinh)";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['flash_msg']  = 'Đã tạo phiên điểm danh thành công!';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_msg']  = 'Lỗi khi tạo phiên: ' . mysqli_error($conn);
                $_SESSION['flash_type'] = 'danger';
            }
        } else {
            $_SESSION['flash_msg']  = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
            $_SESSION['flash_type'] = 'warning';
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- Mở / Đóng cửa sổ điểm danh ---
    if ($act === 'toggle_diemdanh') {
        $idPhienPost = (int)($_POST['idLich']    ?? 0);
        $trangThai   = $_POST['trangThai'] ?? 'mo';
        $thoiLuong   = max(5, min(120, intval($_POST['thoiLuong'] ?? 30)));

        if ($idPhienPost) {
            if ($trangThai === 'mo') {
                $open  = date('Y-m-d H:i:s');
                $close = date('Y-m-d H:i:s', strtotime("+$thoiLuong minutes"));
                $sql   = "UPDATE phien_diemdanh
                          SET thoiGianMo='$open', thoiGianDong='$close'
                          WHERE idPhienDD=$idPhienPost";
                $_SESSION['flash_msg']  = "Đã mở điểm danh trong $thoiLuong phút!";
                $_SESSION['flash_type'] = 'success';
            } else {
                $now = date('Y-m-d H:i:s');
                $sql = "UPDATE phien_diemdanh SET thoiGianDong='$now' WHERE idPhienDD=$idPhienPost";
                $_SESSION['flash_msg']  = 'Đã đóng điểm danh!';
                $_SESSION['flash_type'] = 'success';
            }
            mysqli_query($conn, $sql);
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- Điểm danh thủ công ---
    if ($act === 'diemdanh_tay') {
        $idPhienPost = (int)($_POST['idLich'] ?? 0);
        $idTKPost    = (int)($_POST['idTK']   ?? 0);
        $ghiChu      = mysqli_real_escape_string($conn, trim($_POST['ghiChu'] ?? 'BTC xác nhận'));

        if ($idPhienPost && $idTKPost) {
            $check = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT idDiemDanh FROM diemdanh
                 WHERE idPhienDD=$idPhienPost AND idTK=$idTKPost LIMIT 1"
            ));

            if (!$check) {
                // Lấy idSK từ phiên để tìm nhóm
                $phienInfo = mysqli_fetch_assoc(mysqli_query(
                    $conn,
                    "SELECT idSK FROM phien_diemdanh WHERE idPhienDD=$idPhienPost LIMIT 1"
                ));
                $idSKCheck = $phienInfo['idSK'] ?? 0;

                $nhomInfo = mysqli_fetch_assoc(mysqli_query(
                    $conn,
                    "SELECT n.idnhom FROM nhom n
                     JOIN thanhviennhom tv ON n.idnhom = tv.idnhom
                     WHERE tv.idtk=$idTKPost AND n.idSK=$idSKCheck AND tv.trangthai=1 LIMIT 1"
                ));
                $idNhomInsert = $nhomInfo ? $nhomInfo['idnhom'] : 'NULL';
                $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');

                mysqli_query(
                    $conn,
                    "INSERT INTO diemdanh
                        (idPhienDD, idTK, idNhom, hienDien, phuongThuc, ghiChu, ipDiemDanh)
                     VALUES ($idPhienPost, $idTKPost, $idNhomInsert, 1, 'Manual', '$ghiChu', '$ip')"
                );
                $_SESSION['flash_msg']  = 'Đã điểm danh thủ công thành công!';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_msg']  = 'Tài khoản này đã được điểm danh rồi.';
                $_SESSION['flash_type'] = 'warning';
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- Xóa bản ghi điểm danh ---
    if ($act === 'xoa_diemdanh') {
        $idDD = (int)($_POST['idDiemDanh'] ?? 0);
        if ($idDD) {
            mysqli_query($conn, "DELETE FROM diemdanh WHERE idDiemDanh=$idDD");
            $_SESSION['flash_msg']  = 'Đã xóa bản ghi điểm danh.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

/* ============================================================
   LẤY DỮ LIỆU
   ============================================================ */
$events = mysqli_fetch_all(
    mysqli_query(
        $conn,
        "SELECT sk.idSK, sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, ct.tenCap
     FROM sukien sk
     LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
     WHERE sk.isActive = 1
     ORDER BY sk.ngayBatDau DESC"
    ),
    MYSQLI_ASSOC
);

$event       = null;
$phienList   = [];
$dsDiemDanh  = [];
$dsNguoiDung = [];
$vongthi     = [];

if ($id_su_kien) {
    $event = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT sk.*, ct.tenCap
         FROM sukien sk
         LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
         WHERE sk.idSK = $id_su_kien LIMIT 1"
    ));

    if (!$event) {
        require_once _PATH_URL . '/modules/errors/404.php';
        exit;
    }

    // Danh sách phiên điểm danh của sự kiện
    $phienList = mysqli_fetch_all(
        mysqli_query(
            $conn,
            "SELECT pd.*, v.tenVongThi, lt.tenHoatDong AS tenLichTrinh
         FROM phien_diemdanh pd
         LEFT JOIN vongthi v  ON pd.idVongThi   = v.idVongThi
         LEFT JOIN lichtrinh lt ON pd.idLichTrinh = lt.idLichTrinh
         WHERE pd.idSK = $id_su_kien
         ORDER BY pd.thoiGianBatDau ASC"
        ),
        MYSQLI_ASSOC
    );

    // Danh sách vòng thi để tạo phiên
    $vongthi = mysqli_fetch_all(
        mysqli_query(
            $conn,
            "SELECT idVongThi, tenVongThi FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC"
        ),
        MYSQLI_ASSOC
    );

    // Danh sách lịch trình để gắn phiên (optional)
    $lichTrinhList = mysqli_fetch_all(
        mysqli_query(
            $conn,
            "SELECT idLichTrinh, tenHoatDong, thoiGian FROM lichtrinh WHERE idSK = $id_su_kien ORDER BY thoiGian ASC"
        ),
        MYSQLI_ASSOC
    );

    // Danh sách người dùng để điểm danh thủ công
    $dsNguoiDung = mysqli_fetch_all(
        mysqli_query(
            $conn,
            "SELECT DISTINCT tk.idTK,
                COALESCE(sv.tenSV, gv.tenGV, tk.tenTK) AS tenHienThi,
                COALESCE(sv.MSV, '') AS MSV
         FROM taikhoan tk
         LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
         LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
         JOIN thanhviennhom tvm ON tk.idTK = tvm.idtk AND tvm.trangthai = 1
         JOIN nhom n ON tvm.idnhom = n.idnhom AND n.idSK = $id_su_kien AND n.isActive = 1
         ORDER BY tenHienThi ASC"
        ),
        MYSQLI_ASSOC
    );

    if ($id_phien) {
        $dsDiemDanh = mysqli_fetch_all(
            mysqli_query(
                $conn,
                "SELECT dd.*,
                    COALESCE(sv.tenSV, gv.tenGV, tk.tenTK) AS tenHienThi,
                    COALESCE(sv.MSV, '') AS MSV,
                    ttn.tennhom, n.manhom
             FROM diemdanh dd
             JOIN taikhoan tk ON dd.idTK = tk.idTK
             LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
             LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
             LEFT JOIN nhom n ON dd.idNhom = n.idnhom
             LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
             WHERE dd.idPhienDD = $id_phien
             ORDER BY dd.thoiGianDiemDanh DESC"
            ),
            MYSQLI_ASSOC
        );
    }
}

// Thống kê số điểm danh theo phiên — 1 query thay vì N+1
$statByPhien = [];
if (!empty($phienList)) {
    $phienIds = implode(',', array_column($phienList, 'idPhienDD'));
    $res_stat = mysqli_query(
        $conn,
        "SELECT idPhienDD, COUNT(*) AS tong
         FROM diemdanh
         WHERE idPhienDD IN ($phienIds) AND hienDien = 1
         GROUP BY idPhienDD"
    );
    while ($row = mysqli_fetch_assoc($res_stat)) {
        $statByPhien[$row['idPhienDD']] = (int)$row['tong'];
    }
}


layout('header', $data ?? []);
layout('navbar');
page('diemdanh/index', get_defined_vars());
layout('footer');
