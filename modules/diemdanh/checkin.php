<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_lich = isset($_GET['lich']) ? (int)$_GET['lich'] : 0;
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    $_SESSION['redirect_after_login'] = _HOST_URL . '/?module=diemdanh&action=checkin&lich=' . $id_lich;
    header('Location: ' . _HOST_URL . '/?module=auth&action=login');
    exit;
}
if (!$id_lich) { require_once _PATH_URL . '/modules/errors/404.php'; exit; }

/* ============================================================
   LẤY THÔNG TIN LỊCH TRÌNH
   ============================================================ */
$lich = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT lt.*, sk.tenSK, sk.idSK
     FROM lichtrinh lt
     JOIN sukien sk ON lt.idSK = sk.idSK
     WHERE lt.idLichTrinh = $id_lich LIMIT 1"
));
if (!$lich) { require_once _PATH_URL . '/modules/errors/404.php'; exit; }

$now      = time();
$moTime   = $lich['thoiGianMoDiemDanh']   ? strtotime($lich['thoiGianMoDiemDanh'])   : 0;
$dongTime = $lich['thoiGianDongDiemDanh'] ? strtotime($lich['thoiGianDongDiemDanh']) : 0;
$dangMo   = ($moTime && $dongTime && $now >= $moTime && $now <= $dongTime);

/* ============================================================
   TOKEN 6 SỐ CỐ ĐỊNH SUỐT PHIÊN
   Seed: idLich + thời điểm mở => không đổi cho đến khi BTC đóng/mở lại
   ============================================================ */
$token_6so = '';
if ($moTime) {
    $secret    = 'NCKH_DD_' . $lich['idSK'];
    $raw       = hash_hmac('sha256', $id_lich . '_' . $moTime, $secret);
    $token_6so = str_pad((string)(hexdec(substr($raw, 0, 8)) % 1000000), 6, '0', STR_PAD_LEFT);
}

/* ============================================================
   KIỂM TRA ĐÃ ĐIỂM DANH CHƯA
   ============================================================ */
$daDiemDanh = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM diemdanh WHERE idLichTrinh=$id_lich AND idTK=$user_id LIMIT 1"
));

/* ============================================================
   LẤY THÔNG TIN NGƯỜI DÙNG
   ============================================================ */
$userInfo = mysqli_fetch_assoc(mysqli_query($conn,
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

    // 2. Kiểm tra GPS nếu lịch trình có tọa độ — bắt buộc phải trong bán kính
    $coToaDo = ($lich['viTriLat'] && $lich['viTriLng']);
    if (empty($errors) && $coToaDo) {
        if ($lat == 0 && $lng == 0) {
            $errors[] = 'Không lấy được vị trí GPS. Vui lòng cấp quyền vị trí cho trình duyệt rồi thử lại.';
        } else {
            $R    = 6371000;
            $dLat = deg2rad($lat - $lich['viTriLat']);
            $dLng = deg2rad($lng - $lich['viTriLng']);
            $a    = sin($dLat/2)*sin($dLat/2)
                    + cos(deg2rad($lich['viTriLat']))*cos(deg2rad($lat))
                    * sin($dLng/2)*sin($dLng/2);
            $khoangCach = $R * 2 * atan2(sqrt($a), sqrt(1-$a));
            $banKinh    = $lich['banKinhDiemDanh'] ?: 150;
            if ($khoangCach > $banKinh) {
                $errors[] = sprintf(
                    'Bạn đang cách địa điểm tổ chức %.0f m (giới hạn %d m). Hãy đến gần hơn và thử lại.',
                    $khoangCach, $banKinh
                );
            }
        }
    }

    if (empty($errors)) {
        $nhomInfo = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT n.idnhom FROM nhom n
             JOIN thanhviennhom tv ON n.idnhom = tv.idnhom
             WHERE tv.idtk=$user_id AND n.idSK={$lich['idSK']} AND n.isActive=1 AND tv.trangthai=1
             LIMIT 1"
        ));
        $idNhomInsert = $nhomInfo ? $nhomInfo['idnhom'] : 'NULL';
        $latDb  = ($lat  && $coToaDo) ? $lat  : 'NULL';
        $lngDb  = ($lng  && $coToaDo) ? $lng  : 'NULL';
        $ip     = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
        $method = ($lat && $lng && $coToaDo) ? 'GPS' : 'QR';

        mysqli_query($conn,
            "INSERT INTO diemdanh
                (idLichTrinh, idTK, idNhom, hienDien, phuongThuc, viTriLat, viTriLng, ipDiemDanh, ghiChu)
             VALUES ($id_lich, $user_id, $idNhomInsert, 1, '$method', $latDb, $lngDb, '$ip', 'Tự điểm danh')"
        );
        $daDiemDanh = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM diemdanh WHERE idLichTrinh=$id_lich AND idTK=$user_id LIMIT 1"));
        $result_ok = true;
    }
}

layout('header');
layout('navbar');
?>

<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">
                <i class="bi bi-qr-code-scan me-2"></i>Điểm danh
            </h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
                    <li><a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= $lich['idSK'] ?>"><?= htmlspecialchars($lich['tenSK']) ?></a></li>
                    <li class="current">Điểm danh</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="enroll section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">

            <?php if ($daDiemDanh): ?>
            <!-- ========== ĐÃ ĐIỂM DANH ========== -->
            <div class="enrollment-form-wrapper text-center" data-aos="zoom-in">

                <!-- Icon thành công -->
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
                         style="width:90px;height:90px;background:color-mix(in srgb,#22c55e,transparent 85%)">
                        <i class="bi bi-check-lg" style="font-size:2.8rem;color:#22c55e"></i>
                    </div>
                </div>

                <div class="enrollment-header mb-4">
                    <h2>Điểm danh thành công!</h2>
                    <p>Bạn đã được ghi nhận có mặt trong buổi này.</p>
                </div>

                <!-- Thông tin card -->
                <div class="course-details-card mb-4 text-start">
                    <div class="detail-grid">
                        <div class="detail-row">
                            <span class="detail-label">Họ tên</span>
                            <span class="detail-value fw-semibold"><?= htmlspecialchars($userInfo['tenHienThi'] ?? '') ?></span>
                        </div>
                        <?php if (!empty($userInfo['MSV'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Mã số SV</span>
                            <span class="detail-value"><?= htmlspecialchars($userInfo['MSV']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Hoạt động</span>
                            <span class="detail-value"><?= htmlspecialchars($lich['tenHoatDong']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Thời gian</span>
                            <span class="detail-value">
                                <?= is_array($daDiemDanh) && $daDiemDanh['thoiGianDiemDanh']
                                    ? date('H:i:s — d/m/Y', strtotime($daDiemDanh['thoiGianDiemDanh']))
                                    : date('H:i:s — d/m/Y') ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Trạng thái</span>
                            <span class="detail-value">
                                <span class="badge bg-success px-3 py-1">
                                    <i class="bi bi-person-check me-1"></i>Có mặt
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= $lich['idSK'] ?>"
                   class="btn btn-enroll">
                    <i class="bi bi-arrow-left me-2"></i>Về trang sự kiện
                </a>
            </div>

            <?php elseif (!$dangMo): ?>
            <!-- ========== CHƯA MỞ / ĐÃ ĐÓNG ========== -->
            <div class="enrollment-form-wrapper text-center" data-aos="zoom-in">

                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
                         style="width:90px;height:90px;background:color-mix(in srgb,var(--default-color),transparent 92%)">
                        <i class="bi bi-lock" style="font-size:2.5rem;color:color-mix(in srgb,var(--default-color),transparent 40%)"></i>
                    </div>
                </div>

                <div class="enrollment-header mb-4">
                    <h2>Điểm danh chưa mở</h2>
                    <p>
                        <?php if (!$moTime): ?>
                            Ban tổ chức chưa mở điểm danh cho hoạt động này.
                        <?php else: ?>
                            Cửa sổ điểm danh đã đóng lúc <?= date('H:i, d/m/Y', $dongTime) ?>.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="course-details-card mb-4 text-start">
                    <div class="detail-grid">
                        <div class="detail-row">
                            <span class="detail-label">Hoạt động</span>
                            <span class="detail-value"><?= htmlspecialchars($lich['tenHoatDong']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Sự kiện</span>
                            <span class="detail-value"><?= htmlspecialchars($lich['tenSK']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Thời gian</span>
                            <span class="detail-value"><?= date('H:i, d/m/Y', strtotime($lich['thoiGian'])) ?></span>
                        </div>
                        <?php if ($lich['diaDiem']): ?>
                        <div class="detail-row">
                            <span class="detail-label">Địa điểm</span>
                            <span class="detail-value"><?= htmlspecialchars($lich['diaDiem']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= $lich['idSK'] ?>"
                   class="btn btn-enroll">
                    <i class="bi bi-arrow-left me-2"></i>Quay lại sự kiện
                </a>
            </div>

            <?php else: ?>
            <!-- ========== FORM ĐIỂM DANH ========== -->
            <div class="enrollment-form-wrapper" data-aos="zoom-in">

                <!-- Header -->
                <div class="enrollment-header text-center mb-0">
                    <h2>Xác nhận điểm danh</h2>
                    <p>
                        <strong><?= htmlspecialchars($lich['tenHoatDong']) ?></strong>
                        <span class="text-muted"> — </span><?= htmlspecialchars($lich['tenSK']) ?>
                    </p>
                </div>

                <!-- Banner đếm ngược -->
                <div class="schedule-options mb-4">
                    <div class="form-check d-flex align-items-center justify-content-between gap-2"
                         style="background:color-mix(in srgb,#22c55e,transparent 90%);border-color:color-mix(in srgb,#22c55e,transparent 70%)">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-broadcast" style="color:#16a34a;font-size:1.1rem"></i>
                            <div>
                                <div class="fw-semibold" style="color:#15803d">Điểm danh đang mở</div>
                                <div class="small" style="color:#16a34a">
                                    Đóng lúc <?= date('H:i', $dongTime) ?>
                                    &nbsp;—&nbsp; còn <span id="countdown" class="fw-bold"></span>
                                </div>
                            </div>
                        </div>
                        <span class="badge" style="background:#16a34a;color:#fff;font-size:.75rem">LIVE</span>
                    </div>
                </div>

                <!-- Lỗi -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger d-flex gap-2 align-items-start mb-4">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div><?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
                </div>
                <?php endif; ?>

                <form method="post" action="?module=diemdanh&action=checkin&lich=<?= $id_lich ?>" class="enrollment-form">
                    <input type="hidden" name="lat" id="inputLat" value="0">
                    <input type="hidden" name="lng" id="inputLng" value="0">

                    <!-- Thông tin người dùng -->
                    <div class="course-details-card mb-4">
                        <h4 style="font-size:1rem"><i class="bi bi-person-circle me-2" style="color:var(--accent-color)"></i>Thông tin của bạn</h4>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <span class="detail-label">Họ tên</span>
                                <span class="detail-value fw-semibold"><?= htmlspecialchars($userInfo['tenHienThi'] ?? '') ?></span>
                            </div>
                            <?php if (!empty($userInfo['MSV'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Mã số SV</span>
                                <span class="detail-value"><?= htmlspecialchars($userInfo['MSV']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($lich['diaDiem']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Địa điểm</span>
                                <span class="detail-value"><?= htmlspecialchars($lich['diaDiem']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($lich['viTriLat'] && $lich['viTriLng']): ?>
                            <div class="detail-row">
                                <span class="detail-label">GPS</span>
                                <span class="detail-value" id="gpsStatusRow">
                                    <span class="badge bg-warning text-dark">
                                        <span class="spinner-border spinner-border-sm me-1" style="width:.6rem;height:.6rem"></span>
                                        Đang xác định...
                                    </span>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ô nhập mã 6 số -->
                    <div class="form-group mb-4">
                        <label for="inputToken" class="form-label">
                            <i class="bi bi-123 me-2"></i>Nhập mã 6 số trên màn hình chiếu
                        </label>
                        <input type="text" name="token" id="inputToken"
                               class="form-control text-center fw-bold"
                               style="font-size:2.4rem;letter-spacing:.7em;padding:14px 8px"
                               maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
                               placeholder="——————" autocomplete="off" required
                               value="<?= htmlspecialchars($_POST['token'] ?? '') ?>">
                        <div class="form-text text-center mt-2">
                            <i class="bi bi-shield-check me-1" style="color:var(--accent-color)"></i>
                            Mã cố định suốt phiên — không cần nhập lại khi tải lại trang
                        </div>
                    </div>

                    <!-- Nút submit -->
                    <?php if ($lich['viTriLat'] && $lich['viTriLng']): ?>
                    <button type="submit" id="btnDiemDanh" class="btn btn-enroll w-100" disabled>
                        <span id="btnText">
                            <span class="spinner-border spinner-border-sm me-2"></span>Đang xác định vị trí...
                        </span>
                    </button>
                    <?php else: ?>
                    <button type="submit" class="btn btn-enroll w-100">
                        <i class="bi bi-check-circle me-2"></i>Xác nhận điểm danh
                    </button>
                    <?php endif; ?>

                    <p class="enrollment-note text-center mt-3">
                        <i class="bi bi-person-x me-1"></i>
                        Mỗi người chỉ điểm danh được 1 lần cho mỗi buổi
                    </p>
                </form>

            </div>
            <?php endif; ?>

        </div>
        </div>
    </div>
    </section>

</main>

<?php if ($dangMo && !$daDiemDanh): ?>
<script>
const closeTime = <?= $dongTime ?> * 1000;
function tick() {
    const d = Math.max(0, closeTime - Date.now());
    const el = document.getElementById('countdown');
    if (!el) return;
    if (d <= 0) { el.textContent = 'Hết giờ!'; location.reload(); return; }
    const m = Math.floor(d/60000), s = Math.floor((d%60000)/1000);
    el.textContent = m + ' phút ' + (s<10?'0':'') + s + ' giây';
}
tick(); setInterval(tick, 1000);

<?php if ($lich['viTriLat'] && $lich['viTriLng']): ?>
const btn = document.getElementById('btnDiemDanh');
const btnText = document.getElementById('btnText');
const gpsRow = document.getElementById('gpsStatusRow');

function gpsOK(lat,lng,acc) {
    document.getElementById('inputLat').value = lat.toFixed(7);
    document.getElementById('inputLng').value = lng.toFixed(7);
    btn.disabled = false;
    btnText.innerHTML = '<i class="bi bi-check-circle me-2"></i>Xác nhận điểm danh';
    if (gpsRow) gpsRow.innerHTML = '<span class="badge bg-success"><i class="bi bi-geo-alt-fill me-1"></i>±' + Math.round(acc) + 'm</span>';
}
function gpsErr(msg) {
    if (gpsRow) gpsRow.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>' + msg + '</span>';
}
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        p => gpsOK(p.coords.latitude, p.coords.longitude, p.coords.accuracy),
        () => gpsErr('Không lấy được GPS'),
        { enableHighAccuracy: true, timeout: 12000 }
    );
} else { gpsErr('Không hỗ trợ GPS'); }
<?php endif; ?>

document.getElementById('inputToken').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').slice(0,6);
});
</script>
<?php endif; ?>

<?php layout('footer'); ?>
