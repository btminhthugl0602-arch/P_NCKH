<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

// Lấy idGV thực tế từ idTK trong session
$id_user = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$res_gv_me = mysqli_query($conn, "SELECT idGV FROM giangvien WHERE idTK = $id_user LIMIT 1");
$id_gv = ($res_gv_me && mysqli_num_rows($res_gv_me) > 0) ? (int)mysqli_fetch_assoc($res_gv_me)['idGV'] : 0;

$id_sp = isset($_GET['idSP']) ? (int)$_GET['idSP'] : 0;
$id_vong = isset($_GET['idVong']) ? (int)$_GET['idVong'] : 0;
$id_sk = isset($_GET['idSK']) ? (int)$_GET['idSK'] : 0;

if ($id_sp == 0 || $id_vong == 0) {
    die("Thiếu thông tin bài thi hoặc vòng thi!");
}

// ==========================================
// [LUỒNG 3] CHECK QUYỀN TẦNG 2 — PHANCONG_CHAM
// Tầng 1: kiem_tra_quyen_su_kien(..., 'nhap_diem') — check GV có role chấm trong SK không
// Tầng 2: kiem_tra_duoc_cham_bai()               — check GV được phân công bài CỤ THỂ này không
// Cả 2 tầng phải pass mới được vào trang chấm điểm.
// ==========================================
if ($id_gv <= 0) {
    // Không tìm thấy GV tương ứng session → redirect ngay
    $_SESSION['flash_msg']  = 'Bạn không có tư cách giảng viên trong hệ thống.';
    $_SESSION['flash_type'] = 'danger';
    header("Location: ?module=event&action=view&id=$id_sk");
    exit();
}

if (!kiem_tra_duoc_cham_bai($conn, $id_gv, $id_sp, $id_vong)) {
    $_SESSION['flash_msg']  = 'Bạn không được phân công chấm bài này.';
    $_SESSION['flash_type'] = 'danger';
    header("Location: ?module=event&action=view&id=$id_sk");
    exit();
}

// ==========================================
// XỬ LÝ LƯU ĐIỂM (KHI GIẢNG VIÊN BẤM SUBMIT)
// ==========================================
if (isPost()) {
    $data = filter();
    $diem_array = $_POST['diem'] ?? [];
    $nhanxet_array = $_POST['nhanXet'] ?? [];
    $action = $data['action'] ?? 'draft'; // 'draft' (Lưu nháp) hoặc 'submit' (Chốt điểm)

    $trangThaiXacNhan = ($action === 'submit') ? 'Đã xác nhận' : 'Đang chấm';

    // Lấy thời gian hiện tại để lưu vào DB tránh lỗi Field 'ngayXacNhan' doesn't have a default value
    $ngayXacNhan = date('Y-m-d H:i:s');

    // 1. Kiểm tra xem đã có phiếu chấm trong bảng phancongcham chưa
    // Cần biết idBoTieuChi đang dùng cho vòng này
    $sql_btc = "SELECT idBoTieuChi FROM cauhinh_tieuchi_sk WHERE idSK = $id_sk AND idVongThi = $id_vong LIMIT 1";
    $res_btc = mysqli_query($conn, $sql_btc);
    $id_bo_tieu_chi = ($res_btc && mysqli_num_rows($res_btc) > 0) ? mysqli_fetch_assoc($res_btc)['idBoTieuChi'] : 1;

    $chk_pcc = mysqli_query($conn, "SELECT idPhanCongCham FROM phancongcham WHERE idGV = $id_gv AND idVongThi = $id_vong");

    if (mysqli_num_rows($chk_pcc) > 0) {
        $id_pcc = mysqli_fetch_assoc($chk_pcc)['idPhanCongCham'];
        // Đã bổ sung cập nhật ngayXacNhan
        mysqli_query($conn, "UPDATE phancongcham SET trangThaiXacNhan = '$trangThaiXacNhan', ngayXacNhan = '$ngayXacNhan' WHERE idPhanCongCham = $id_pcc");
    } else {
        // Đã bổ sung ngayXacNhan vào câu lệnh INSERT
        mysqli_query($conn, "INSERT INTO phancongcham (idGV, idSK, idVongThi, idBoTieuChi, trangThaiXacNhan, ngayXacNhan) 
                             VALUES ($id_gv, $id_sk, $id_vong, $id_bo_tieu_chi, '$trangThaiXacNhan', '$ngayXacNhan')");
        $id_pcc = mysqli_insert_id($conn);
    }

    // 2. Lưu điểm chi tiết vào bảng chamtieuchi
    // Xóa điểm cũ của SP này do GK này chấm (để insert lại đồ mới)
    mysqli_query($conn, "DELETE FROM chamtieuchi WHERE idPhanCongCham = $id_pcc AND idSanPham = $id_sp");

    foreach ($diem_array as $idTieuChi => $diem) {
        $diem_val = (float)$diem;
        $nhanxet_val = isset($nhanxet_array[$idTieuChi]) ? chuan_hoa_chuoi_sql($conn, $nhanxet_array[$idTieuChi]) : '';

        mysqli_query($conn, "INSERT INTO chamtieuchi (idPhanCongCham, idSanPham, idTieuChi, diem, nhanXet) 
                             VALUES ($id_pcc, $id_sp, $idTieuChi, $diem_val, '$nhanxet_val')");
    }

    $msg = ($action === 'submit') ? "Đã chốt điểm thành công!" : "Đã lưu nháp điểm!";
    $flash_type = ($action === 'submit') ? 'success' : 'info';
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_type'] = $flash_type;
    header("Location: ?module=event&action=grade_product&idSP=$id_sp&idVong=$id_vong&idSK=$id_sk");
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ FORM CHẤM
// ==========================================
// 1. Thông tin bài thi
$sql_sp = "SELECT sp.tensanpham, n.manhom, ttn.tennhom FROM sanpham sp LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom WHERE sp.idSanPham = $id_sp";
$sp_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_sp));

// 1b. Lấy các file sản phẩm đã nộp
$sql_files = "SELECT sp2.idSanPham, sp2.moTataiLieu, sp2.idloaitailieu, l.loaitailieu AS tenLoai
    FROM sanpham sp2
    LEFT JOIN loaitailieu l ON sp2.idloaitailieu = l.idtailieu
    WHERE sp2.idNhom = (SELECT idNhom FROM sanpham WHERE idSanPham = $id_sp LIMIT 1)
      AND sp2.idSK = $id_sk
      AND sp2.moTataiLieu IS NOT NULL AND sp2.moTataiLieu != ''
    ORDER BY sp2.idloaitailieu ASC";
$res_files = mysqli_query($conn, $sql_files);
$sp_files = $res_files ? mysqli_fetch_all($res_files, MYSQLI_ASSOC) : [];

// 2. Lấy danh sách tiêu chí dựa trên cấu hình của Vòng thi đó
$sql_tc = "
    SELECT tc.idTieuChi, tc.noiDungTieuChi, btt.diemToiDa 
    FROM cauhinh_tieuchi_sk cts
    JOIN botieuchi_tieuchi btt ON cts.idBoTieuChi = btt.idBoTieuChi
    JOIN tieuchi tc ON btt.idTieuChi = tc.idTieuChi
    WHERE cts.idSK = $id_sk AND cts.idVongThi = $id_vong
";
$res_tc = mysqli_query($conn, $sql_tc);
$ds_tieuchi = $res_tc ? mysqli_fetch_all($res_tc, MYSQLI_ASSOC) : [];

// 3. Lấy điểm cũ nếu Giám khảo đã từng lưu nháp
$sql_old_scores = "
    SELECT ctc.idTieuChi, ctc.diem, ctc.nhanXet, pcc.trangThaiXacNhan
    FROM chamtieuchi ctc
    JOIN phancongcham pcc ON ctc.idPhanCongCham = pcc.idPhanCongCham
    WHERE pcc.idGV = $id_gv AND pcc.idVongThi = $id_vong AND ctc.idSanPham = $id_sp
";
$res_old = mysqli_query($conn, $sql_old_scores);
$diem_cu = [];
$trang_thai_cham = 'Chưa chấm';

if ($res_old && mysqli_num_rows($res_old) > 0) {
    while ($row = mysqli_fetch_assoc($res_old)) {
        $diem_cu[$row['idTieuChi']] = [
            'diem' => $row['diem'],
            'nhanXet' => $row['nhanXet']
        ];
        $trang_thai_cham = $row['trangThaiXacNhan'];
    }
}

$is_locked = ($trang_thai_cham === 'Đã xác nhận'); // Nếu đã chốt điểm thì khóa form không cho sửa nữa

layout('header');
layout('navbar');
?>

<main class="main container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Chấm Điểm Bài Thi</h2>
        <a href="?module=event&action=my_grading_tasks&id=<?php echo $id_sk; ?>" class="btn btn-outline-secondary"><i
                class="bi bi-arrow-left"></i> Danh sách bài được phân công</a>
    </div>

    <div class="card shadow-sm border-0 mb-4 border-start border-4 border-primary">
        <div class="card-body">
            <h4 class="text-primary fw-bold mb-1"><?= htmlspecialchars($sp_info['tensanpham']) ?></h4>
            <div class="text-muted"><i class="bi bi-people-fill me-1"></i> Nhóm thực hiện:
                <strong><?= htmlspecialchars($sp_info['tennhom'] ?: $sp_info['manhom']) ?></strong>
            </div>

            <div class="mt-3">
                <span
                    class="badge <?= $is_locked ? 'bg-success' : ($trang_thai_cham == 'Đang chấm' ? 'bg-warning text-dark' : 'bg-secondary') ?> px-3 py-2 fs-6">
                    <i class="bi <?= $is_locked ? 'bi-check-circle-fill' : 'bi-clock-fill' ?> me-1"></i> Trạng thái:
                    <?= $trang_thai_cham ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ===== PHẦN SẢN PHẨM ĐÃ NỘP ===== -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white pt-3 pb-2 border-0 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-folder2-open text-warning me-2"></i>Tài liệu / Sản phẩm
                đã nộp</h5>
            <span class="badge bg-<?php echo empty($sp_files) ? 'secondary' : 'success'; ?> rounded-pill">
                <?php echo count($sp_files); ?> tập tin
            </span>
        </div>
        <div class="card-body bg-light">
            <?php if (empty($sp_files)): ?>
                <div class="alert alert-warning mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <span>Nhóm chưa nộp tài liệu nào. Vui lòng liên hệ nhóm để kiểm tra.</span>
                </div>
            <?php else: ?>
                <?php
                $loai_info = [
                    1 => ['icon' => 'bi-file-earmark-text',      'color' => 'primary', 'label' => 'Báo cáo tóm tắt'],
                    2 => ['icon' => 'bi-file-earmark-richtext',   'color' => 'info',    'label' => 'Báo cáo toàn văn'],
                    3 => ['icon' => 'bi-github',                  'color' => 'dark',    'label' => 'Source Code / Mã nguồn'],
                ];
                foreach ($sp_files as $file):
                    $li = $loai_info[$file['idloaitailieu']] ?? ['icon' => 'bi-file-earmark', 'color' => 'secondary', 'label' => ($file['tenLoai'] ?: 'Tệp đính kèm')];
                    $file_url = (strpos($file['moTataiLieu'], 'http') === 0)
                        ? $file['moTataiLieu']
                        : _HOST_URL . '/' . ltrim($file['moTataiLieu'], '/');
                    $ext = strtolower(pathinfo($file['moTataiLieu'], PATHINFO_EXTENSION));
                    $is_pdf = ($ext === 'pdf');
                ?>
                    <div class="border rounded-3 bg-white mb-3 overflow-hidden">
                        <!-- File header -->
                        <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:44px;height:44px;background:var(--bs-<?php echo $li['color']; ?>-bg-subtle,#f0f0f0);">
                                <i class="bi <?php echo $li['icon']; ?> text-<?php echo $li['color']; ?> fs-5"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold text-<?php echo $li['color']; ?> small"><?php echo $li['label']; ?></div>
                                <div class="text-muted small text-truncate">
                                    <?php echo htmlspecialchars(basename($file['moTataiLieu'])); ?></div>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <?php if ($is_pdf): ?>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#preview_<?php echo $file['idSanPham'] . '_' . $file['idloaitailieu']; ?>">
                                        <i class="bi bi-eye me-1"></i>Xem trước
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank"
                                    class="btn btn-sm btn-<?php echo $li['color']; ?> rounded-pill px-3">
                                    <i class="bi bi-download me-1"></i>Tải xuống
                                </a>
                            </div>
                        </div>
                        <!-- PDF preview (collapsible) -->
                        <?php if ($is_pdf): ?>
                            <div class="collapse" id="preview_<?php echo $file['idSanPham'] . '_' . $file['idloaitailieu']; ?>">
                                <div class="p-2 bg-light border-top">
                                    <iframe src="<?php echo htmlspecialchars($file_url); ?>#toolbar=1&navpanes=0" width="100%"
                                        height="600" class="rounded border-0" class="d-block">
                                    </iframe>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== BẢNG CHẤM ĐIỂM ===== -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white pt-3 pb-2 border-0 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-ui-checks me-2"></i>Bảng đánh giá theo Tiêu chí</h5>
        </div>
        <div class="card-body bg-light">
            <?php if (empty($ds_tieuchi)): ?>
                <div class="alert alert-warning">Ban tổ chức chưa cấu hình bộ tiêu chí cho vòng thi này. Bạn chưa thể chấm
                    điểm!</div>
            <?php else: ?>
                <form method="post" id="formChamDiem">
                    <div class="table-responsive bg-white shadow-sm rounded border mb-4">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="bg-primary text-white text-center">
                                <tr>
                                    <th width="5%">STT</th>
                                    <th width="40%" class="text-start">Nội dung tiêu chí</th>
                                    <th width="15%">Điểm tối đa</th>
                                    <th width="15%">Điểm đánh giá</th>
                                    <th width="25%">Nhận xét / Góp ý</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stt = 1;
                                $tong_diem_max = 0;
                                $tong_diem_cham = 0;
                                foreach ($ds_tieuchi as $tc):
                                    $idTC = $tc['idTieuChi'];
                                    $diemMax = (float)$tc['diemToiDa'];
                                    $tong_diem_max += $diemMax;

                                    $diem_dat = isset($diem_cu[$idTC]) ? (float)$diem_cu[$idTC]['diem'] : '';
                                    $nhan_xet = isset($diem_cu[$idTC]) ? htmlspecialchars($diem_cu[$idTC]['nhanXet']) : '';
                                    if ($diem_dat !== '') $tong_diem_cham += $diem_dat;
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $stt++ ?></td>
                                        <td class="text-start fw-bold text-dark"><?= $tc['noiDungTieuChi'] ?></td>
                                        <td class="text-center fw-bold text-secondary"><?= $diemMax ?></td>
                                        <td>
                                            <input type="number" step="0.1" min="0" max="<?= $diemMax ?>" name="diem[<?= $idTC ?>]"
                                                class="form-control text-center fw-bold text-primary diem-input"
                                                value="<?= $diem_dat ?>" required <?= $is_locked ? 'disabled' : '' ?>>
                                        </td>
                                        <td>
                                            <input type="text" name="nhanXet[<?= $idTC ?>]" class="form-control"
                                                placeholder="Nhập góp ý..." value="<?= $nhan_xet ?>"
                                                <?= $is_locked ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold text-uppercase">TỔNG CỘNG:</td>
                                    <td class="text-center fw-bold fs-5"><?= $tong_diem_max ?></td>
                                    <td class="text-center fw-bold fs-5 text-danger" id="tongDiemCham"><?= $tong_diem_cham ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if (!$is_locked): ?>
                        <div class="d-flex justify-content-end gap-3 mt-4">
                            <button type="submit" name="action" value="draft"
                                class="btn btn-outline-primary fw-bold px-4 rounded-pill">
                                <i class="bi bi-save me-1"></i> Lưu nháp (Chấm tiếp sau)
                            </button>
                            <button type="button" id="btnChotDiem" class="btn btn-success fw-bold px-5 rounded-pill">
                                <i class="bi bi-send-check-fill me-1"></i> Chốt Điểm Lên Hệ Thống
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success text-center fw-bold"><i class="bi bi-shield-lock-fill me-2"></i>Bạn đã
                            chốt điểm cho bài thi này. Kết quả đã được gửi lên Hội đồng.</div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    // Tự động tính tổng điểm khi Giám khảo gõ vào ô điểm
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.diem-input');
        const tongDiemEl = document.getElementById('tongDiemCham');

        inputs.forEach(input => {
            input.addEventListener('input', function() {
                let sum = 0;
                inputs.forEach(inp => {
                    let val = parseFloat(inp.value);
                    if (!isNaN(val)) sum += val;
                });
                tongDiemEl.textContent = sum.toFixed(1);
            });
        });
    });

    document.getElementById('btnChotDiem')?.addEventListener('click', function() {
        showConfirm({
            title: 'Xác nhận chốt điểm',
            message: 'Sau khi chốt điểm bạn sẽ không thể sửa lại được. Bạn có chắc chắn với kết quả này?',
            type: 'warning',
            confirmText: 'Chốt điểm',
            onConfirm: function() {
                const form = document.querySelector('form[id]') || document.querySelector('form');
                // Set action value to submit then submit
                const hiddenAction = document.createElement('input');
                hiddenAction.type = 'hidden';
                hiddenAction.name = 'action';
                hiddenAction.value = 'submit';
                form.appendChild(hiddenAction);
                form.submit();
            }
        });
    });
</script>

<?php layout('footer'); ?>