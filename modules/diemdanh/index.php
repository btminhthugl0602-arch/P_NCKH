<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_btc  = kiem_tra_quyen_he_thong($conn, $user_id, 'event.manage');

// Nếu không phải BTC/Admin thì chặn
if (!$is_btc) {
    $_SESSION['flash_msg']  = 'Bạn không có quyền truy cập trang này.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . _HOST_URL . '/');
    exit;
}

$id_su_kien = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$id_lich    = isset($_GET['lich']) ? (int)$_GET['lich'] : 0;

$msg_ok  = '';
$msg_err = '';

/* ============================================================
   XỬ LÝ POST
   ============================================================ */
if ($is_btc && isPost()) {
    $act = $_POST['act'] ?? '';

    // --- Tạo / Cập nhật lịch trình điểm danh ---
    if ($act === 'tao_lich') {
        $tenHD    = trim($_POST['tenHoatDong'] ?? '');
        $thoiGian = trim($_POST['thoiGian']    ?? '');
        $diaDiem  = trim($_POST['diaDiem']     ?? '');
        $lat      = floatval($_POST['lat']     ?? 0);
        $lng      = floatval($_POST['lng']     ?? 0);
        $banKinh  = max(50, intval($_POST['banKinh'] ?? 150));
        $idSKPost = (int)($_POST['idSK']       ?? 0);

        if ($tenHD && $thoiGian && $idSKPost) {
            $latDb   = $lat  ? $lat  : 'NULL';
            $lngDb   = $lng  ? $lng  : 'NULL';
            $diaDiemDb = mysqli_real_escape_string($conn, $diaDiem);
            $tenHDDb   = mysqli_real_escape_string($conn, $tenHD);
            $thoiGianDb = mysqli_real_escape_string($conn, $thoiGian);

            $sql = "INSERT INTO lichtrinh
                        (idSK, tenHoatDong, thoiGian, diaDiem, viTriLat, viTriLng, banKinhDiemDanh)
                    VALUES ($idSKPost, '$tenHDDb', '$thoiGianDb', '$diaDiemDb', $latDb, $lngDb, $banKinh)";
            if (mysqli_query($conn, $sql)) {
                $msg_ok = 'Đã thêm lịch trình thành công!';
            } else {
                $msg_err = 'Lỗi khi thêm lịch trình: ' . mysqli_error($conn);
            }
        } else {
            $msg_err = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
        }
    }

    // --- Mở / Đóng cửa sổ điểm danh ---
    if ($act === 'toggle_diemdanh') {
        $idLichPost  = (int)($_POST['idLich']    ?? 0);
        $trangThai   = $_POST['trangThai'] ?? 'mo';
        $thoiLuong   = max(5, min(120, intval($_POST['thoiLuong'] ?? 30)));

        if ($idLichPost) {
            if ($trangThai === 'mo') {
                $open  = date('Y-m-d H:i:s');
                $close = date('Y-m-d H:i:s', strtotime("+$thoiLuong minutes"));
                $sql   = "UPDATE lichtrinh
                          SET thoiGianMoDiemDanh='$open', thoiGianDongDiemDanh='$close'
                          WHERE idLichTrinh=$idLichPost";
                $msg_ok = "Đã mở điểm danh trong $thoiLuong phút!";
            } else {
                $now = date('Y-m-d H:i:s');
                $sql = "UPDATE lichtrinh SET thoiGianDongDiemDanh='$now' WHERE idLichTrinh=$idLichPost";
                $msg_ok = 'Đã đóng điểm danh!';
            }
            mysqli_query($conn, $sql);
        }
    }

    // --- Điểm danh thủ công ---
    if ($act === 'diemdanh_tay') {
        $idLichPost = (int)($_POST['idLich'] ?? 0);
        $idTKPost   = (int)($_POST['idTK']   ?? 0);
        $ghiChu     = mysqli_real_escape_string($conn, trim($_POST['ghiChu'] ?? 'BTC xác nhận'));

        if ($idLichPost && $idTKPost) {
            $check = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT idDiemDanh FROM diemdanh
                 WHERE idLichTrinh=$idLichPost AND idTK=$idTKPost LIMIT 1"));
            if (!$check) {
                // Lấy idNhom của user trong sự kiện này
                $lichInfo = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT idSK FROM lichtrinh WHERE idLichTrinh=$idLichPost LIMIT 1"));
                $idSKCheck = $lichInfo['idSK'] ?? 0;
                $nhomInfo  = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT n.idnhom FROM nhom n
                     JOIN thanhviennhom tv ON n.idnhom = tv.idnhom
                     WHERE tv.idtk=$idTKPost AND n.idSK=$idSKCheck AND tv.trangthai=1 LIMIT 1"));
                $idNhomInsert = $nhomInfo ? $nhomInfo['idnhom'] : 'NULL';

                mysqli_query($conn,
                    "INSERT INTO diemdanh
                        (idLichTrinh, idTK, idNhom, hienDien, phuongThuc, ghiChu, ipDiemDanh)
                     VALUES ($idLichPost, $idTKPost, $idNhomInsert, 1, 'Manual', '$ghiChu',
                             '" . mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '') . "')");
                $msg_ok = 'Đã điểm danh thủ công thành công!';
            } else {
                $msg_err = 'Tài khoản này đã được điểm danh rồi.';
            }
        }
    }

    // --- Xóa bản ghi điểm danh (sửa sai) ---
    if ($act === 'xoa_diemdanh') {
        $idDD = (int)($_POST['idDiemDanh'] ?? 0);
        if ($idDD) {
            mysqli_query($conn, "DELETE FROM diemdanh WHERE idDiemDanh=$idDD");
            $msg_ok = 'Đã xóa bản ghi điểm danh.';
        }
    }
}

/* ============================================================
   LẤY DỮ LIỆU
   ============================================================ */
$events = mysqli_fetch_all(mysqli_query($conn,
    "SELECT sk.idSK, sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, ct.tenCap
     FROM sukien sk
     LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
     WHERE sk.isActive = 1
     ORDER BY sk.ngayBatDau DESC"),
    MYSQLI_ASSOC
);

$event       = null;
$lichTrinhs  = [];
$dsDiemDanh  = [];
$dsNguoiDung = [];

if ($id_su_kien) {
    $event = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT sk.*, ct.tenCap
         FROM sukien sk
         LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
         WHERE sk.idSK = $id_su_kien LIMIT 1"));

    if (!$event) {
        require_once _PATH_URL . '/modules/errors/404.php';
        exit;
    }

    $lichTrinhs = mysqli_fetch_all(mysqli_query($conn,
        "SELECT lt.*, v.tenVongThi
         FROM lichtrinh lt
         LEFT JOIN vongthi v ON lt.idVongThi = v.idVongThi
         WHERE lt.idSK = $id_su_kien
         ORDER BY lt.thoiGian ASC"),
        MYSQLI_ASSOC
    );

    // Danh sách tất cả user thuộc sự kiện để điểm danh thủ công
    $dsNguoiDung = mysqli_fetch_all(mysqli_query($conn,
        "SELECT DISTINCT tk.idTK,
                COALESCE(sv.tenSV, gv.tenGV, tk.tenTK) AS tenHienThi,
                COALESCE(sv.MSV, '') AS MSV
         FROM taikhoan tk
         LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
         LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
         JOIN thanhviennhom tvm ON tk.idTK = tvm.idtk AND tvm.trangthai = 1
         JOIN nhom n ON tvm.idnhom = n.idnhom AND n.idSK = $id_su_kien AND n.isActive = 1
         ORDER BY tenHienThi ASC"),
        MYSQLI_ASSOC
    );

    if ($id_lich) {
        $dsDiemDanh = mysqli_fetch_all(mysqli_query($conn,
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
             WHERE dd.idLichTrinh = $id_lich
             ORDER BY dd.thoiGianDiemDanh DESC"),
            MYSQLI_ASSOC
        );
    }
}

// Thống kê nhanh số người mỗi lịch trình
$statByLich = [];
foreach ($lichTrinhs as $lt) {
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS tong FROM diemdanh
         WHERE idLichTrinh = {$lt['idLichTrinh']} AND hienDien = 1"));
    $statByLich[$lt['idLichTrinh']] = (int)($r['tong'] ?? 0);
}

layout('header');
layout('navbar');
?>

<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">
                <i class="bi bi-person-check-fill me-2"></i>
                <?php if ($event): ?>Điểm danh — <?= htmlspecialchars($event['tenSK']) ?><?php else: ?>Quản lý Điểm Danh<?php endif; ?>
            </h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
                    <li><a href="<?= _HOST_URL ?>/?module=diemdanh&action=index">Điểm danh</a></li>
                    <?php if ($event): ?><li class="current"><?= htmlspecialchars($event['tenSK']) ?></li><?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>

    <section class="courses-events section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <?php if ($msg_ok): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div><?= htmlspecialchars($msg_ok) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($msg_err): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= htmlspecialchars($msg_err) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!$id_su_kien): ?>
        <!-- ========== TRANG CHỌN SỰ KIỆN ========== -->

        <div class="container section-title mb-4" data-aos="fade-up">
            <h2>Chọn sự kiện</h2>
            <p>Chọn một sự kiện để bắt đầu quản lý điểm danh</p>
        </div>

        <?php if (empty($events)): ?>
        <div class="text-center py-5" data-aos="fade-up">
            <i class="bi bi-calendar-x" style="font-size:4rem;color:var(--accent-color);opacity:.4"></i>
            <h5 class="mt-3 text-muted">Chưa có sự kiện nào đang hoạt động</h5>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($events as $i => $ev):
                $bd = $ev['ngayBatDau'] ? strtotime($ev['ngayBatDau']) : null;
                $kt = $ev['ngayKetThuc'] ? strtotime($ev['ngayKetThuc']) : null;
                $now = time();
                if (!$bd) $trangThai = ['label'=>'Chưa có lịch','cls'=>'bg-secondary'];
                elseif ($bd > $now) $trangThai = ['label'=>'Sắp diễn ra','cls'=>'bg-info text-dark'];
                elseif (!$kt || $kt >= $now) $trangThai = ['label'=>'Đang diễn ra','cls'=>'bg-success'];
                else $trangThai = ['label'=>'Đã kết thúc','cls'=>'bg-secondary'];
            ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
                <div class="event-card h-100">
                    <div class="event-content d-flex flex-column h-100">
                        <!-- Date badge style header -->
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="flex-shrink-0 text-center rounded-3 p-2"
                                 style="background:color-mix(in srgb,var(--accent-color),transparent 88%);min-width:54px">
                                <?php if ($bd): ?>
                                <span class="d-block fw-bold" style="font-size:1.5rem;line-height:1;color:var(--accent-color)"><?= date('d',$bd) ?></span>
                                <span class="d-block text-uppercase" style="font-size:.7rem;color:var(--accent-color)"><?= date('M',$bd) ?></span>
                                <?php else: ?>
                                <i class="bi bi-calendar3" style="font-size:1.8rem;color:var(--accent-color)"></i>
                                <?php endif; ?>
                            </div>
                            <div style="min-width:0">
                                <span class="badge <?= $trangThai['cls'] ?> mb-1"><?= $trangThai['label'] ?></span>
                                <h5 class="event-title mb-0" style="font-size:1rem">
                                    <?= htmlspecialchars($ev['tenSK']) ?>
                                </h5>
                            </div>
                        </div>

                        <div class="event-meta flex-wrap mb-3">
                            <?php if ($ev['tenCap']): ?>
                            <span><i class="bi bi-building"></i> <?= htmlspecialchars($ev['tenCap']) ?></span>
                            <?php endif; ?>
                            <?php if ($bd): ?>
                            <span><i class="bi bi-calendar-range"></i>
                                <?= date('d/m/Y',$bd) ?><?= $kt ? ' – '.date('d/m/Y',$kt) : '' ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="event-actions mt-auto">
                            <a href="?module=diemdanh&action=index&id=<?= $ev['idSK'] ?>"
                               class="btn btn-primary">
                                <i class="bi bi-person-check me-1"></i>Quản lý điểm danh
                            </a>
                            <a href="?module=event&action=view&id=<?= $ev['idSK'] ?>"
                               class="btn btn-outline">
                                <i class="bi bi-eye me-1"></i>Xem
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>


        <?php else: ?>
        <!-- ========== TRANG QUẢN LÝ LỊCH TRÌNH ========== -->

        <!-- Breadcrumb nav -->
        <div class="d-flex align-items-center gap-2 mb-4 flex-wrap" data-aos="fade-up">
            <a href="?module=diemdanh&action=index"
               class="btn btn-outline" style="border-radius:50px;padding:6px 18px;font-size:.9rem">
                <i class="bi bi-arrow-left me-1"></i>Danh sách sự kiện
            </a>
            <a href="?module=event&action=view&id=<?= $id_su_kien ?>"
               class="btn btn-outline" style="border-radius:50px;padding:6px 18px;font-size:.9rem">
                <i class="bi bi-calendar-event me-1"></i>Trang sự kiện
            </a>
            <button class="btn btn-primary ms-auto" style="border-radius:50px;padding:6px 20px;font-size:.9rem"
                    data-bs-toggle="modal" data-bs-target="#modalTaoLich">
                <i class="bi bi-plus-lg me-1"></i>Thêm lịch trình
            </button>
        </div>

        <div class="row g-4">

            <!-- ===== CỘT TRÁI: Danh sách lịch trình ===== -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="course-details-card p-0 overflow-hidden">
                    <div class="px-4 py-3 d-flex align-items-center justify-content-between"
                         style="background:var(--accent-color)">
                        <h5 class="mb-0 fw-bold" style="color:var(--contrast-color)">
                            <i class="bi bi-calendar3 me-2"></i>Lịch trình
                        </h5>
                        <span class="badge" style="background:rgba(255,255,255,.2);color:var(--contrast-color)">
                            <?= count($lichTrinhs) ?> buổi
                        </span>
                    </div>

                    <?php if (empty($lichTrinhs)): ?>
                    <div class="text-center py-5 px-3">
                        <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.3;color:var(--accent-color)"></i>
                        <p class="text-muted mt-3 mb-3">Chưa có lịch trình nào.</p>
                        <button class="btn btn-primary btn-sm" style="border-radius:50px"
                                data-bs-toggle="modal" data-bs-target="#modalTaoLich">
                            <i class="bi bi-plus-lg me-1"></i>Thêm lịch trình
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($lichTrinhs as $lt):
                            $isSelected = ($id_lich == $lt['idLichTrinh']);
                            $soLuong    = $statByLich[$lt['idLichTrinh']] ?? 0;
                            $now2       = time();
                            $moT        = $lt['thoiGianMoDiemDanh']   ? strtotime($lt['thoiGianMoDiemDanh'])   : 0;
                            $dongT      = $lt['thoiGianDongDiemDanh'] ? strtotime($lt['thoiGianDongDiemDanh']) : 0;
                            $moDang     = ($moT && $dongT && $now2 >= $moT && $now2 <= $dongT);
                        ?>
                        <a href="?module=diemdanh&action=index&id=<?= $id_su_kien ?>&lich=<?= $lt['idLichTrinh'] ?>"
                           class="list-group-item list-group-item-action px-4 py-3 <?= $isSelected ? 'active' : '' ?>"
                           style="<?= $isSelected ? 'background:color-mix(in srgb,var(--accent-color),transparent 88%);border-left:3px solid var(--accent-color)' : 'border-left:3px solid transparent' ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div style="min-width:0">
                                    <div class="fw-semibold text-truncate"
                                         style="<?= $isSelected ? 'color:var(--accent-color)' : 'color:var(--heading-color)' ?>">
                                        <?= htmlspecialchars($lt['tenHoatDong']) ?>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-clock me-1"></i><?= date('H:i, d/m', strtotime($lt['thoiGian'])) ?>
                                    </div>
                                    <?php if ($lt['diaDiem']): ?>
                                    <div class="small text-muted">
                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($lt['diaDiem']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <?php if ($moDang): ?>
                                        <span class="badge bg-success">Đang mở</span>
                                    <?php elseif ($moT): ?>
                                        <span class="badge bg-secondary">Đã đóng</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Chờ mở</span>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-people-fill me-1"></i><strong><?= $soLuong ?></strong>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== CỘT PHẢI: Chi tiết ===== -->
            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">

                <?php if (!$id_lich): ?>
                <!-- Chưa chọn lịch trình -->
                <div class="course-details-card text-center py-5">
                    <i class="bi bi-hand-index-thumb" style="font-size:3.5rem;color:var(--accent-color);opacity:.5"></i>
                    <h5 class="mt-3" style="color:var(--heading-color)">Chọn một lịch trình bên trái</h5>
                    <p class="text-muted">để xem chi tiết và điều khiển điểm danh</p>
                </div>

                <?php else:
                    $currentLich = null;
                    foreach ($lichTrinhs as $lt) {
                        if ($lt['idLichTrinh'] == $id_lich) { $currentLich = $lt; break; }
                    }
                    $now      = time();
                    $moTime   = $currentLich['thoiGianMoDiemDanh']   ? strtotime($currentLich['thoiGianMoDiemDanh'])   : 0;
                    $dongTime = $currentLich['thoiGianDongDiemDanh'] ? strtotime($currentLich['thoiGianDongDiemDanh']) : 0;
                    $dangMo   = ($moTime && $dongTime && $now >= $moTime && $now <= $dongTime);

                    // Token 6 số cho lịch này
                    $secret_t   = 'NCKH_DD_' . $id_su_kien;
                    $token_disp = '';
                    if ($moTime) {
                        $raw_t      = hash_hmac('sha256', $id_lich . '_' . $moTime, $secret_t);
                        $token_disp = str_pad((string)(hexdec(substr($raw_t, 0, 8)) % 1000000), 6, '0', STR_PAD_LEFT);
                    }
                ?>

                <!-- Card: Thông tin + điều khiển -->
                <div class="course-details-card mb-4">

                    <!-- Header card với trạng thái -->
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4 pb-3"
                         style="border-bottom:1px solid color-mix(in srgb,var(--default-color),transparent 90%)">
                        <div>
                            <h4 class="fw-bold mb-1" style="color:var(--heading-color)">
                                <?= htmlspecialchars($currentLich['tenHoatDong']) ?>
                            </h4>
                            <div class="d-flex flex-wrap gap-3" style="font-size:.9rem;color:color-mix(in srgb,var(--default-color),transparent 30%)">
                                <span><i class="bi bi-clock me-1" style="color:var(--accent-color)"></i><?= date('H:i, d/m/Y', strtotime($currentLich['thoiGian'])) ?></span>
                                <?php if ($currentLich['diaDiem']): ?>
                                <span><i class="bi bi-geo-alt me-1" style="color:var(--accent-color)"></i><?= htmlspecialchars($currentLich['diaDiem']) ?></span>
                                <?php endif; ?>
                                <?php if ($currentLich['viTriLat']): ?>
                                <span><i class="bi bi-crosshair me-1" style="color:var(--accent-color)"></i>GPS ±<?= $currentLich['banKinhDiemDanh'] ?: 150 ?>m</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($dangMo): ?>
                        <div class="text-end flex-shrink-0">
                            <span class="badge bg-success px-3 py-2 mb-1 d-block">● Đang mở</span>
                            <small class="text-muted">Đóng lúc <?= date('H:i', $dongTime) ?></small><br>
                            <span id="countdown" class="small fw-bold" style="color:var(--accent-color)"></span>
                        </div>
                        <?php elseif ($moTime): ?>
                        <span class="badge bg-secondary px-3 py-2 flex-shrink-0">Đã đóng</span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark px-3 py-2 flex-shrink-0">Chờ mở</span>
                        <?php endif; ?>
                    </div>

                    <!-- Nút điều khiển -->
                    <div class="row g-3 mb-0">
                        <?php if (!$dangMo): ?>
                        <div class="col-sm-6">
                            <button class="btn btn-primary w-100 py-2"
                                    data-bs-toggle="modal" data-bs-target="#modalMoDiemDanh">
                                <i class="bi bi-unlock-fill me-2"></i>Mở điểm danh
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="col-sm-6">
                            <form method="post" action="?module=diemdanh&action=index&id=<?= $id_su_kien ?>&lich=<?= $id_lich ?>">
                                <input type="hidden" name="act" value="toggle_diemdanh">
                                <input type="hidden" name="idLich" value="<?= $id_lich ?>">
                                <input type="hidden" name="trangThai" value="dong">
                                <button type="submit" class="btn w-100 py-2"
                                        style="background:#dc3545;color:#fff;border:none"
                                        onclick="return confirm('Đóng điểm danh ngay bây giờ?')">
                                    <i class="bi bi-lock-fill me-2"></i>Đóng điểm danh ngay
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <div class="col-sm-6">
                            <button class="btn btn-outline w-100 py-2"
                                    data-bs-toggle="modal" data-bs-target="#modalDiemDanhTay">
                                <i class="bi bi-pencil-square me-2"></i>Điểm danh thủ công
                            </button>
                        </div>

                        <?php if ($dangMo): ?>
                        <div class="col-12">
                            <a href="?module=diemdanh&action=qr&lich=<?= $id_lich ?>" target="_blank"
                               class="btn w-100 py-2"
                               style="background:color-mix(in srgb,var(--accent-color),transparent 88%);color:var(--accent-color);border:2px solid var(--accent-color)">
                                <i class="bi bi-qr-code me-2"></i>Chiếu màn hình QR Code
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($dangMo && $token_disp): ?>
                    <!-- Banner mã + link -->
                    <div class="mt-4 p-3 rounded-3"
                         style="background:color-mix(in srgb,var(--accent-color),transparent 92%);border:1px solid color-mix(in srgb,var(--accent-color),transparent 70%)">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div>
                                <div class="small fw-semibold mb-1" style="color:var(--accent-color)">
                                    <i class="bi bi-123 me-1"></i>Mã điểm danh phiên này
                                </div>
                                <span class="fw-bold" style="font-size:2rem;letter-spacing:.3em;color:var(--heading-color)"><?= $token_disp ?></span>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted mb-1">Link cho sinh viên:</div>
                                <a href="<?= _HOST_URL ?>/?module=diemdanh&action=checkin&lich=<?= $id_lich ?>"
                                   target="_blank" class="small" style="color:var(--accent-color);word-break:break-all">
                                    checkin&lich=<?= $id_lich ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Card: Danh sách điểm danh -->
                <div class="course-details-card p-0 overflow-hidden">
                    <div class="px-4 py-3 d-flex align-items-center justify-content-between"
                         style="background:color-mix(in srgb,var(--accent-color),transparent 92%)">
                        <h5 class="mb-0 fw-bold" style="color:var(--heading-color)">
                            <i class="bi bi-person-check me-2" style="color:var(--accent-color)"></i>Đã điểm danh
                        </h5>
                        <span class="badge" style="background:var(--accent-color);color:var(--contrast-color);font-size:.9rem;padding:6px 14px">
                            <?= count($dsDiemDanh) ?> người
                        </span>
                    </div>

                    <?php if (empty($dsDiemDanh)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size:3rem;opacity:.25;color:var(--accent-color)"></i>
                        <p class="text-muted mt-3">Chưa có ai điểm danh cho lịch trình này.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.9rem">
                            <thead style="background:color-mix(in srgb,var(--default-color),transparent 96%)">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">Họ tên</th>
                                    <th class="py-3">Nhóm</th>
                                    <th class="py-3">Thời gian</th>
                                    <th class="py-3">Phương thức</th>
                                    <th class="py-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($dsDiemDanh as $i => $dd):
                                $ptMap = [
                                    'QR'     => ['var(--accent-color)',  'bi-qr-code-scan',   'QR'],
                                    'GPS'    => ['#0891b2', 'bi-geo-alt-fill',   'GPS'],
                                    'Manual' => ['#d97706', 'bi-pencil-square',  'Thủ công'],
                                    'NFC'    => ['#059669', 'bi-phone',          'NFC'],
                                ];
                                $pt = $dd['phuongThuc'] ?? 'QR';
                                [$ptColor, $ptIcon, $ptLabel] = $ptMap[$pt] ?? ['#6b7280', 'bi-check', $pt];
                            ?>
                            <tr style="border-bottom:1px solid color-mix(in srgb,var(--default-color),transparent 94%)">
                                <td class="px-4 text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                             style="width:34px;height:34px;font-size:.8rem;background:color-mix(in srgb,var(--accent-color),transparent 85%);color:var(--accent-color)">
                                            <?= mb_strtoupper(mb_substr($dd['tenHienThi'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold" style="color:var(--heading-color)"><?= htmlspecialchars($dd['tenHienThi']) ?></div>
                                            <?php if ($dd['MSV']): ?>
                                            <div class="text-muted" style="font-size:.78rem"><?= htmlspecialchars($dd['MSV']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($dd['tennhom'] ?? '—') ?></td>
                                <td>
                                    <div style="color:var(--heading-color)"><?= date('H:i:s', strtotime($dd['thoiGianDiemDanh'])) ?></div>
                                    <div class="text-muted" style="font-size:.78rem"><?= date('d/m/Y', strtotime($dd['thoiGianDiemDanh'])) ?></div>
                                </td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill"
                                          style="background:color-mix(in srgb,<?= $ptColor ?>,transparent 88%);color:<?= $ptColor ?>;font-size:.8rem;font-weight:600">
                                        <i class="bi <?= $ptIcon ?>"></i><?= $ptLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <?php if (!empty($dd['viTriLat']) && !empty($dd['viTriLng'])): ?>
                                        <a href="https://maps.google.com/?q=<?= $dd['viTriLat'] ?>,<?= $dd['viTriLng'] ?>"
                                           target="_blank" class="btn btn-sm"
                                           style="border-radius:50%;width:30px;height:30px;padding:0;background:color-mix(in srgb,#0891b2,transparent 88%);color:#0891b2;display:flex;align-items:center;justify-content:center">
                                            <i class="bi bi-map" style="font-size:.75rem"></i>
                                        </a>
                                        <?php endif; ?>
                                        <form method="post" action="?module=diemdanh&action=index&id=<?= $id_su_kien ?>&lich=<?= $id_lich ?>"
                                              onsubmit="return confirm('Xóa bản ghi điểm danh này?')">
                                            <input type="hidden" name="act" value="xoa_diemdanh">
                                            <input type="hidden" name="idDiemDanh" value="<?= $dd['idDiemDanh'] ?>">
                                            <button type="submit" class="btn btn-sm"
                                                    style="border-radius:50%;width:30px;height:30px;padding:0;background:color-mix(in srgb,#dc3545,transparent 88%);color:#dc3545;display:flex;align-items:center;justify-content:center">
                                                <i class="bi bi-trash" style="font-size:.75rem"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <?php endif; // end $id_lich ?>
            </div>
        </div><!-- row -->

        <!-- ========== MODALS ========== -->

        <!-- Modal: Mở điểm danh -->
        <div class="modal fade" id="modalMoDiemDanh" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <form method="post" action="?module=diemdanh&action=index&id=<?= $id_su_kien ?>&lich=<?= $id_lich ?>">
                        <input type="hidden" name="act" value="toggle_diemdanh">
                        <input type="hidden" name="idLich" value="<?= $id_lich ?>">
                        <input type="hidden" name="trangThai" value="mo">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" style="color:var(--heading-color)">
                                <i class="bi bi-unlock-fill me-2" style="color:var(--accent-color)"></i>Mở điểm danh
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body pt-2">
                            <label class="form-label fw-semibold mb-3">Thời lượng mở</label>
                            <div class="text-center mb-2">
                                <span id="rangeVal" class="fw-bold" style="font-size:2.5rem;color:var(--accent-color)">30</span>
                                <span class="text-muted ms-1">phút</span>
                            </div>
                            <input type="range" name="thoiLuong" class="form-range mb-2"
                                   min="5" max="120" step="5" value="30"
                                   oninput="document.getElementById('rangeVal').textContent = this.value">
                            <div class="d-flex justify-content-between text-muted small mb-4">
                                <span>5 phút</span><span>120 phút</span>
                            </div>
                            <div class="p-3 rounded-3 small"
                                 style="background:color-mix(in srgb,var(--accent-color),transparent 92%);color:color-mix(in srgb,var(--default-color),transparent 20%)">
                                <i class="bi bi-info-circle me-1" style="color:var(--accent-color)"></i>
                                Sau khi mở, một mã 6 số sẽ được tạo. Sinh viên nhập mã hoặc quét QR trên màn hình chiếu để điểm danh.
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-unlock me-1"></i>Mở điểm danh
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Tạo lịch trình -->
        <div class="modal fade" id="modalTaoLich" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <form method="post" action="?module=diemdanh&action=index&id=<?= $id_su_kien ?>">
                        <input type="hidden" name="act" value="tao_lich">
                        <input type="hidden" name="idSK" value="<?= $id_su_kien ?>">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" style="color:var(--heading-color)">
                                <i class="bi bi-plus-circle-fill me-2" style="color:var(--accent-color)"></i>Thêm lịch trình điểm danh
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Tên hoạt động <span style="color:#dc3545">*</span></label>
                                    <input type="text" name="tenHoatDong" class="form-control"
                                           placeholder="VD: Khai mạc, Vòng sơ loại buổi sáng..." required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Thời gian diễn ra <span style="color:#dc3545">*</span></label>
                                    <input type="datetime-local" name="thoiGian" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Địa điểm</label>
                                    <input type="text" name="diaDiem" class="form-control" placeholder="VD: Hội trường A">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Tọa độ GPS <span class="text-muted fw-normal">(nếu bắt buộc điểm danh tại chỗ)</span></label>
                                    <div class="row g-2">
                                        <div class="col-5">
                                            <input type="number" name="lat" id="inputLat" class="form-control"
                                                   step="0.0000001" placeholder="Vĩ độ (Lat)">
                                        </div>
                                        <div class="col-5">
                                            <input type="number" name="lng" id="inputLng" class="form-control"
                                                   step="0.0000001" placeholder="Kinh độ (Lng)">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn w-100 h-100"
                                                    style="background:color-mix(in srgb,var(--accent-color),transparent 88%);color:var(--accent-color)"
                                                    onclick="layViTriHienTai()" title="Lấy vị trí hiện tại">
                                                <i class="bi bi-crosshair"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Bán kính GPS (mét)</label>
                                    <input type="number" name="banKinh" class="form-control" value="150" min="50" max="2000">
                                    <div class="form-text">Sinh viên phải đứng trong phạm vi này.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-plus-lg me-1"></i>Thêm lịch trình
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Điểm danh thủ công -->
        <?php if ($id_lich): ?>
        <div class="modal fade" id="modalDiemDanhTay" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <form method="post" action="?module=diemdanh&action=index&id=<?= $id_su_kien ?>&lich=<?= $id_lich ?>">
                        <input type="hidden" name="act" value="diemdanh_tay">
                        <input type="hidden" name="idLich" value="<?= $id_lich ?>">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" style="color:var(--heading-color)">
                                <i class="bi bi-pencil-square me-2" style="color:var(--accent-color)"></i>Điểm danh thủ công
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="p-3 rounded-3 mb-3 small"
                                 style="background:color-mix(in srgb,#f59e0b,transparent 88%);border-left:3px solid #f59e0b">
                                <i class="bi bi-exclamation-triangle-fill me-1" style="color:#f59e0b"></i>
                                Chỉ dùng khi sinh viên gặp sự cố kỹ thuật không thể tự điểm danh.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Chọn sinh viên / người dùng <span style="color:#dc3545">*</span></label>
                                <select name="idTK" class="form-select" required>
                                    <option value="">-- Chọn tài khoản --</option>
                                    <?php foreach ($dsNguoiDung as $nd): ?>
                                    <option value="<?= $nd['idTK'] ?>">
                                        <?= htmlspecialchars($nd['tenHienThi']) ?>
                                        <?= $nd['MSV'] ? ' (' . htmlspecialchars($nd['MSV']) . ')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label fw-semibold">Lý do / Ghi chú</label>
                                <input type="text" name="ghiChu" class="form-control"
                                       value="BTC xác nhận hiện diện" placeholder="VD: Thiết bị không có GPS">
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i>Xác nhận điểm danh
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // end $id_su_kien ?>

    </div>
    </section>

</main>

<?php if ($id_lich && isset($dangMo) && $dangMo && $dongTime): ?>
<script>
const closeTime = <?= $dongTime ?> * 1000;
function tick() {
    const diff = Math.max(0, closeTime - Date.now());
    const el = document.getElementById('countdown');
    if (!el) return;
    if (diff <= 0) { el.textContent = 'Hết giờ!'; setTimeout(() => location.reload(), 1500); return; }
    const m = Math.floor(diff/60000), s = Math.floor((diff%60000)/1000);
    el.textContent = m + 'p ' + (s<10?'0':'') + s + 's';
}
tick(); setInterval(tick, 1000);
</script>
<?php endif; ?>

<script>
function layViTriHienTai() {
    if (!navigator.geolocation) { alert('Trình duyệt không hỗ trợ GPS.'); return; }
    navigator.geolocation.getCurrentPosition(function(p) {
        document.getElementById('inputLat').value = p.coords.latitude.toFixed(7);
        document.getElementById('inputLng').value = p.coords.longitude.toFixed(7);
    }, function() { alert('Không thể lấy vị trí. Hãy nhập thủ công.'); });
}
</script>

<?php layout('footer'); ?>
