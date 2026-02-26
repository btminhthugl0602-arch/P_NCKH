<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

$idSK = isset($_GET['idSK']) ? (int)$_GET['idSK'] : 0;
$idSP = isset($_GET['idSP']) ? (int)$_GET['idSP'] : 0;
$idVong = isset($_GET['idVong']) ? (int)$_GET['idVong'] : 0;

// Lấy id Giám khảo từ session người dùng đăng nhập
$user_id = $_SESSION['user_id'] ?? 0;
$idGV = 0;
$res_gv = mysqli_query($conn, "SELECT idGV FROM giangvien WHERE idTK = $user_id LIMIT 1");
if ($res_gv && mysqli_num_rows($res_gv) > 0) {
    $idGV = mysqli_fetch_assoc($res_gv)['idGV'];
}

if ($idSK == 0 || $idSP == 0 || $idVong == 0 || $idGV == 0) {
    die("<div class='alert alert-danger m-4'>Lỗi: Thiếu thông số hoặc bạn không phải là Giám khảo!</div>");
}

// ==============================================================================
// THUẬT TOÁN SMART FALLBACK: TÌM BỘ TIÊU CHÍ CHUẨN XÁC CHO GIÁM KHẢO NÀY
// ==============================================================================
$idBoTieuChi_su_dung = null;
$tenBoTieuChi_su_dung = '';
$nguon_tieu_chi = '';

// ƯU TIÊN 1: Kiểm tra xem GV có chấm bài này trong TIỂU BAN nào không?
$sql_check_tieuban = "
    SELECT tb.idBoTieuChi, tb.tenTieuBan, btc.tenBoTieuChi 
    FROM tieuban tb
    JOIN tieuban_giangvien tbg ON tb.idTieuBan = tbg.idTieuBan
    JOIN tieuban_sanpham tbs ON tb.idTieuBan = tbs.idTieuBan
    LEFT JOIN botieuchi btc ON tb.idBoTieuChi = btc.idBoTieuChi
    WHERE tbg.idGV = $idGV AND tbs.idSanPham = $idSP AND tb.idVongThi = $idVong
    LIMIT 1
";
$res_tb = mysqli_query($conn, $sql_check_tieuban);

if ($res_tb && mysqli_num_rows($res_tb) > 0) {
    $row_tb = mysqli_fetch_assoc($res_tb);
    if (!empty($row_tb['idBoTieuChi'])) {
        $idBoTieuChi_su_dung = $row_tb['idBoTieuChi']; 
        $tenBoTieuChi_su_dung = $row_tb['tenBoTieuChi'];
        $nguon_tieu_chi = "Tiêu chí riêng của " . $row_tb['tenTieuBan'];
    }
}

// ƯU TIÊN 2: Nếu không có Tiểu ban, Lùi về (Fallback) lấy Tiêu chí chung của VÒNG THI
if (empty($idBoTieuChi_su_dung)) {
    $sql_check_vong = "
        SELECT c.idBoTieuChi, btc.tenBoTieuChi 
        FROM cauhinh_tieuchi_sk c
        JOIN botieuchi btc ON c.idBoTieuChi = btc.idBoTieuChi
        WHERE c.idSK = $idSK AND c.idVongThi = $idVong LIMIT 1
    ";
    $res_vong = mysqli_query($conn, $sql_check_vong);
    if ($res_vong && mysqli_num_rows($res_vong) > 0) {
        $row_vong = mysqli_fetch_assoc($res_vong);
        $idBoTieuChi_su_dung = $row_vong['idBoTieuChi'];
        $tenBoTieuChi_su_dung = $row_vong['tenBoTieuChi'];
        $nguon_tieu_chi = "Tiêu chí dùng chung cho Vòng thi";
    }
}

// CHẶN BẢO VỆ: Nếu BTC chưa cấu hình tiêu chí -> Dừng hệ thống
if (empty($idBoTieuChi_su_dung)) {
    layout('header'); layout('navbar');
    echo "<div class='container py-5'><div class='alert alert-danger shadow-sm border-0 border-start border-5 border-danger'>
          <h4 class='fw-bold'>Hệ thống chặn!</h4>
          <p>Ban tổ chức chưa cấu hình Bộ tiêu chí chấm điểm cho vòng thi hoặc tiểu ban này. Vui lòng liên hệ BTC.</p>
          <a href='javascript:history.back()' class='btn btn-outline-danger mt-2'>Quay lại</a>
          </div></div>";
    layout('footer');
    exit;
}

// ==============================================================================
// KHỞI TẠO VÉ CHẤM ĐIỂM (BẢNG phancongcham)
// ==============================================================================
$idPhanCongCham = 0;
$sql_check_pc = "SELECT idPhanCongCham, diemTong, nhanXet FROM phancongcham WHERE idGV = $idGV AND idSanPham = $idSP AND idVongThi = $idVong LIMIT 1";
$res_pc = mysqli_query($conn, $sql_check_pc);

if ($res_pc && mysqli_num_rows($res_pc) > 0) {
    $row_pc = mysqli_fetch_assoc($res_pc);
    $idPhanCongCham = $row_pc['idPhanCongCham'];
    $nhanXetCu = $row_pc['nhanXet'];
} else {
    // Nếu chưa có, tạo vé mới
    mysqli_query($conn, "INSERT INTO phancongcham (idGV, idSanPham, idVongThi, diemTong) VALUES ($idGV, $idSP, $idVong, 0)");
    $idPhanCongCham = mysqli_insert_id($conn);
    $nhanXetCu = '';
}

// ==============================================================================
// XỬ LÝ LƯU ĐIỂM KHI GIÁM KHẢO SUBMIT FORM
// ==============================================================================
if (isPost()) {
    $nhanXet = trim($_POST['nhanXet'] ?? '');
    $diemTong = 0;

    if (isset($_POST['diem_tieuchi']) && is_array($_POST['diem_tieuchi'])) {
        foreach ($_POST['diem_tieuchi'] as $idTC => $diem) {
            $diem = (float)$diem;
            $tyTrong = (float)($_POST['tytrong_tieuchi'][$idTC] ?? 1.0);
            
            // Tính toán điểm có trọng số
            $diemThucTe = $diem * $tyTrong;
            $diemTong += $diemThucTe;

            // Lưu vào bảng chamtieuchi
            $check_ctc = mysqli_query($conn, "SELECT 1 FROM chamtieuchi WHERE idPhanCongCham = $idPhanCongCham AND idTieuChi = $idTC");
            if (mysqli_num_rows($check_ctc) > 0) {
                mysqli_query($conn, "UPDATE chamtieuchi SET diem = $diem WHERE idPhanCongCham = $idPhanCongCham AND idTieuChi = $idTC");
            } else {
                mysqli_query($conn, "INSERT INTO chamtieuchi (idPhanCongCham, idTieuChi, diem) VALUES ($idPhanCongCham, $idTC, $diem)");
            }
        }
    }

    // 1. Cập nhật Điểm tổng và Nhận xét cho Giám khảo
    mysqli_query($conn, "UPDATE phancongcham SET diemTong = $diemTong, nhanXet = '".chuan_hoa_chuoi_sql($conn, $nhanXet)."', thoiGianCham = NOW() WHERE idPhanCongCham = $idPhanCongCham");

    // 2. AUTO-CALCULATE: Tự động tính Lại ĐIỂM TRUNG BÌNH của Sản phẩm ở Vòng này
    $sql_avg = "SELECT AVG(diemTong) as avgDiem FROM phancongcham WHERE idSanPham = $idSP AND idVongThi = $idVong AND diemTong > 0";
    $res_avg = mysqli_query($conn, $sql_avg);
    if ($res_avg && mysqli_num_rows($res_avg) > 0) {
        $avg_score = (float)mysqli_fetch_assoc($res_avg)['avgDiem'];
        
        // Cập nhật vào bảng sanpham_vongthi để BTC xét duyệt
        mysqli_query($conn, "UPDATE sanpham_vongthi SET diemTrungBinh = $avg_score WHERE idSanPham = $idSP AND idVongThi = $idVong");
    }

    $_SESSION['flash_msg'] = "Đã lưu điểm thành công! Tổng điểm của bạn: " . number_format($diemTong, 2);
    $_SESSION['flash_type'] = "success";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// ==============================================================================
// TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ LÊN FORM
// ==============================================================================
$sp_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sp.tensanpham, n.manhom, ttn.tennhom FROM sanpham sp LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom WHERE sp.idSanPham = $idSP"));
$vong_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tenVongThi FROM vongthi WHERE idVongThi = $idVong"));

// Lấy danh sách tiêu chí con và điểm đã chấm (nếu có)
$sql_criteria = "
    SELECT tc.idTieuChi, tc.noiDungTieuChi, bt.diemToiDa, bt.tyTrong, ctc.diem as diemDaCham
    FROM botieuchi_tieuchi bt
    JOIN tieuchi tc ON bt.idTieuChi = tc.idTieuChi
    LEFT JOIN chamtieuchi ctc ON tc.idTieuChi = ctc.idTieuChi AND ctc.idPhanCongCham = $idPhanCongCham
    WHERE bt.idBoTieuChi = $idBoTieuChi_su_dung
";
$criteria_list = mysqli_fetch_all(mysqli_query($conn, $sql_criteria), MYSQLI_ASSOC);

layout('header'); layout('navbar');
?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0"><i class="bi bi-pencil-square text-primary me-2"></i>Đánh giá Bài thi</h2>
        <a href="?module=event&action=my_grading_tasks&id=<?=$idSK?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left"></i> Về danh sách nhiệm vụ
        </a>
    </div>

    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?=$_SESSION['flash_type']?> alert-dismissible fade show shadow-sm border-0 border-start border-5 border-<?=$_SESSION['flash_type']?>">
            <?=$_SESSION['flash_msg']?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body bg-light rounded">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">Thông tin bài thi</h5>
                    <p class="mb-2"><span class="text-muted">Nhóm/Tác giả:</span> <br><span class="fw-bold fs-5 text-primary"><?=htmlspecialchars($sp_info['tennhom'] ?: $sp_info['manhom'] ?: 'Cá nhân')?></span></p>
                    <p class="mb-2"><span class="text-muted">Tên đề tài:</span> <br><strong class="text-dark"><?=htmlspecialchars($sp_info['tensanpham'])?></strong></p>
                    <p class="mb-2"><span class="text-muted">Vòng thi:</span> <br><span class="badge bg-secondary"><?=htmlspecialchars($vong_info['tenVongThi'])?></span></p>
                    
                    <div class="alert alert-info mt-4 border-0 shadow-sm">
                        <i class="bi bi-info-circle-fill me-1"></i> Đang áp dụng:<br>
                        <strong><?=htmlspecialchars($tenBoTieuChi_su_dung)?></strong><br>
                        <small class="fst-italic">(<?=$nguon_tieu_chi?>)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom pt-3 pb-2">
                    <h5 class="fw-bold m-0 text-dark"><i class="bi bi-list-check me-2 text-warning"></i>Phiếu Chấm Điểm</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" id="gradingForm">
                        <div class="table-responsive mb-4">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">STT</th>
                                        <th width="45%">Nội dung tiêu chí</th>
                                        <th width="15%" class="text-center">Trọng số</th>
                                        <th width="15%" class="text-center">Tối đa</th>
                                        <th width="20%" class="text-center">Điểm của bạn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stt = 1; 
                                    $sumMax = 0;
                                    foreach ($criteria_list as $tc): 
                                        $diemToiDa = $tc['diemToiDa'];
                                        $sumMax += ($diemToiDa ? ($diemToiDa * $tc['tyTrong']) : 0);
                                    ?>
                                        <tr>
                                            <td class="text-center fw-bold text-muted"><?=$stt++?></td>
                                            <td><?=htmlspecialchars($tc['noiDungTieuChi'])?></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">x<?=$tc['tyTrong']?></span>
                                                <input type="hidden" name="tytrong_tieuchi[<?=$tc['idTieuChi']?>]" value="<?=$tc['tyTrong']?>">
                                            </td>
                                            <td class="text-center text-secondary"><?= $diemToiDa ?: 'Không giới hạn' ?></td>
                                            <td>
                                                <input type="number" step="0.1" min="0" <?= $diemToiDa ? "max='{$diemToiDa}'" : "" ?> 
                                                       name="diem_tieuchi[<?=$tc['idTieuChi']?>]" 
                                                       class="form-control text-center fw-bold text-primary grading-input" 
                                                       value="<?=$tc['diemDaCham']?>" required>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-bold">Tổng điểm tự động tính:</td>
                                        <td class="text-center">
                                            <h4 class="m-0 fw-bold text-danger" id="liveTotalScore">0.00</h4>
                                            <?php if($sumMax > 0): ?><small class="text-muted">/ <?=number_format($sumMax, 2)?></small><?php endif; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-2">Nhận xét của Ban giám khảo:</label>
                            <textarea name="nhanXet" rows="3" class="form-control" placeholder="Ghi chú điểm mạnh, điểm yếu của bài thi..."><?=htmlspecialchars($nhanXetCu ?? '')?></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                                <i class="bi bi-save me-2"></i> Lưu Điểm & Xác Nhận
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Tính điểm live bằng Javascript cho Giám khảo dễ nhìn
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll('.grading-input');
        const totalDisplay = document.getElementById('liveTotalScore');

        function calculateTotal() {
            let total = 0;
            inputs.forEach(input => {
                let score = parseFloat(input.value) || 0;
                // Lấy tyTrong từ thẻ input hidden ngay phía trên tr đó
                let tr = input.closest('tr');
                let tyTrongInput = tr.querySelector('input[name^="tytrong_tieuchi"]');
                let tyTrong = parseFloat(tyTrongInput.value) || 1;
                
                total += (score * tyTrong);
            });
            totalDisplay.innerText = total.toFixed(2);
        }

        inputs.forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
        
        // Chạy lần đầu khi load form
        calculateTotal();
    });
</script>

<?php layout('footer'); ?>