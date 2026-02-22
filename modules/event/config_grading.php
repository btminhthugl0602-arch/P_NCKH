<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1; 

// ==========================================
// XỬ LÝ FORM SUBMIT (POST REQUESTS)
// ==========================================
if (isPost()) {
    $data = filter(); 
    $action = $data['action'] ?? ''; 
    $current_tab = (int)($data['active_tab'] ?? $active_tab);

    if ($action === 'assign_doclap') {
        $idSP = (int)($data['idSanPham'] ?? 0); 
        $idGV = (int)($data['idGV'] ?? 0); 
        $idVong = (int)($data['idVongThi'] ?? 0);
        if ($idSP > 0 && $idGV > 0 && $idVong > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi) VALUES ($idSP, $idGV, $idVong)");
        }
    } 
    elseif ($action === 'remove_doclap') {
        $idSP = (int)($data['idSanPham'] ?? 0); 
        $idGV = (int)($data['idGV'] ?? 0); 
        $idVong = (int)($data['idVongThi'] ?? 0);
        mysqli_query($conn, "DELETE FROM phancong_doclap WHERE idSanPham = $idSP AND idGV = $idGV AND idVongThi = $idVong");
    }
    // Logic: Duyệt & Chốt điểm (Bao gồm điểm tự động và điểm do BTC sửa tay)
    elseif ($action === 'approve_score_manual' || $action === 'reject_score') {
        $idSP = (int)$data['idSanPham'];
        $idVong = (int)$data['idVongThi'];
        $diemTB = isset($data['diemChot']) ? (float)$data['diemChot'] : 0;
        $trangThai = ($action === 'reject_score') ? 'Bị loại' : 'Đã duyệt';

        $chk = mysqli_query($conn, "SELECT 1 FROM sanpham_vongthi WHERE idSanPham = $idSP AND idVongThi = $idVong");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($conn, "UPDATE sanpham_vongthi SET diemTrungBinh = $diemTB, trangThai = '$trangThai' WHERE idSanPham = $idSP AND idVongThi = $idVong");
        } else {
            mysqli_query($conn, "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, diemTrungBinh, trangThai) VALUES ($idSP, $idVong, $diemTB, '$trangThai')");
        }
        $current_tab = 2; 
    }
    // LOGIC: MỜI GIÁM KHẢO THỨ 3 (TRỌNG TÀI)
    elseif ($action === 'add_3rd_judge') {
        $idSP = (int)$data['idSanPham'];
        $idGV = (int)$data['idGV'];
        $idVong = (int)$data['idVongThi'];
        
        if ($idSP > 0 && $idGV > 0 && $idVong > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi) VALUES ($idSP, $idGV, $idVong)");
            // Xóa bỏ trạng thái "Đã duyệt/Loại" cũ để bài thi quay về "Đang chấm..."
            mysqli_query($conn, "DELETE FROM sanpham_vongthi WHERE idSanPham = $idSP AND idVongThi = $idVong");
        }
        $current_tab = 2;
    }

    header("Location: ?module=event&action=config_grading&id=$id_su_kien&tab=$current_tab" . (isset($data['idVongThi']) ? "&vong=".$data['idVongThi'] : "")); 
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU ĐỔ RA GIAO DIỆN
// ==========================================
$vong_list = mysqli_query($conn, "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC");
$vong_array = $vong_list ? mysqli_fetch_all($vong_list, MYSQLI_ASSOC) : [];
$active_vong = isset($_GET['vong']) ? (int)$_GET['vong'] : (!empty($vong_array) ? $vong_array[0]['idVongThi'] : 0);

$res_gv_all = mysqli_query($conn, "SELECT idGV, tenGV FROM giangvien ORDER BY tenGV ASC");
$ds_giangvien = $res_gv_all ? mysqli_fetch_all($res_gv_all, MYSQLI_ASSOC) : [];

$sql_sp = "SELECT sp.idSanPham, sp.tensanpham, sp.TrangThai, sp.idNhom, n.manhom, ttn.tennhom 
           FROM sanpham sp 
           LEFT JOIN nhom n ON sp.idNhom = n.idnhom 
           LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom 
           WHERE sp.idSK = $id_su_kien ORDER BY sp.idSanPham DESC";
$res_sp = mysqli_query($conn, $sql_sp);
$ds_sanpham = $res_sp ? mysqli_fetch_all($res_sp, MYSQLI_ASSOC) : [];

// Lay cac file da nop cho tung san pham
$files_map = [];
if (!empty($ds_sanpham)) {
    $sp_ids = implode(',', array_column($ds_sanpham, 'idSanPham'));
    $res_files = mysqli_query($conn, "SELECT sp.idSanPham, sp.moTataiLieu, sp.idloaitailieu, l.loaitailieu AS tenLoai 
        FROM sanpham sp LEFT JOIN loaitailieu l ON sp.idloaitailieu = l.idtailieu 
        WHERE sp.idSanPham IN ($sp_ids) AND sp.moTataiLieu IS NOT NULL AND sp.moTataiLieu != '' ");
    if ($res_files) {
        while ($fr = mysqli_fetch_assoc($res_files)) {
            $files_map[$fr['idSanPham']][] = $fr;
        }
    }
}

$sql_pc = "SELECT pcd.*, gv.tenGV, v.tenVongThi FROM phancong_doclap pcd JOIN giangvien gv ON pcd.idGV = gv.idGV JOIN vongthi v ON pcd.idVongThi = v.idVongThi WHERE v.idSK = $id_su_kien";
$res_pc = mysqli_query($conn, $sql_pc);
$phancong_map = []; if($res_pc) { while($r=mysqli_fetch_assoc($res_pc)) $phancong_map[$r['idSanPham']][] = $r; }

$sql_svt = "SELECT idSanPham, diemTrungBinh, trangThai FROM sanpham_vongthi WHERE idVongThi = $active_vong";
$res_svt = mysqli_query($conn, $sql_svt);
$trangthai_map = [];
if($res_svt) { while($r = mysqli_fetch_assoc($res_svt)) $trangthai_map[$r['idSanPham']] = $r; }

$sql_tiendo = "
    SELECT sp.idSanPham, sp.tensanpham, n.manhom, ttn.tennhom, IFNULL(pc.tongGK, 0) as tongGiamKhaoPhanCong
    FROM sanpham sp LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
    JOIN (SELECT idSanPham, COUNT(idGV) as tongGK FROM phancong_doclap WHERE idVongThi = $active_vong GROUP BY idSanPham) pc ON sp.idSanPham = pc.idSanPham
    WHERE sp.idSK = $id_su_kien ORDER BY sp.idSanPham DESC";
$res_td = mysqli_query($conn, $sql_tiendo); 
$ds_tiendo = $res_td ? mysqli_fetch_all($res_td, MYSQLI_ASSOC) : [];

$sql_scores = "SELECT ctc.idSanPham, ctc.idPhanCongCham, SUM(ctc.diem) as tongDiem 
               FROM chamtieuchi ctc
               JOIN phancongcham pcc ON ctc.idPhanCongCham = pcc.idPhanCongCham
               WHERE ctc.idSanPham IN (SELECT idSanPham FROM sanpham WHERE idSK = $id_su_kien) 
               AND pcc.idVongThi = $active_vong
               GROUP BY ctc.idSanPham, ctc.idPhanCongCham";
$res_scores = mysqli_query($conn, $sql_scores);
$scores_map = []; if ($res_scores) { while ($row = mysqli_fetch_assoc($res_scores)) $scores_map[$row['idSanPham']][] = (float)$row['tongDiem']; }

$ranking_list = [];
$approved_list = [];

foreach ($ds_tiendo as $sp) {
    $sp_scores = $scores_map[$sp['idSanPham']] ?? [];
    $sp['soNguoiDaCham'] = count($sp_scores);
    $sp['diemTB'] = $sp['soNguoiDaCham'] > 0 ? array_sum($sp_scores) / $sp['soNguoiDaCham'] : 0;
    
    // Cảnh báo khi có độ lệch > 30%
    $sp['isWarning'] = ($sp['soNguoiDaCham'] > 1 && (max($sp_scores) - min($sp_scores)) >= ($sp['diemTB'] * 0.3));
    $sp['chiTietDiem'] = $sp_scores;
    
    $sp['trangThaiDuyet'] = $trangthai_map[$sp['idSanPham']]['trangThai'] ?? 'Chưa duyệt';
    $sp['diemChot'] = $trangthai_map[$sp['idSanPham']]['diemTrungBinh'] ?? 0;

    $ranking_list[] = $sp;
    
    if ($sp['trangThaiDuyet'] === 'Đã duyệt') {
        $approved_list[] = $sp;
    }
}

usort($ranking_list, fn($a, $b) => $b['diemTB'] <=> $a['diemTB']);
usort($approved_list, fn($a, $b) => $b['diemChot'] <=> $a['diemChot']);

layout('header'); layout('navbar');
?>

<style>
    .nav-tabs .nav-link { font-weight: 500; color: #495057; font-size: 1.05rem; padding: 12px 20px;}
    .nav-tabs .nav-link.active { color: #0d6efd !important; font-weight: 600; border-bottom: 3px solid #0d6efd; background: transparent; }
    .badge-gk { background-color: #f8f9fa; color: #495057; border: 1px solid #dee2e6; padding: 6px 12px; border-radius: 50rem; display: inline-flex; align-items: center; gap: 5px; margin: 2px;}
    .btn-action { border-radius: 50rem; padding: 5px 15px; font-size: 0.85rem; font-weight: 500;}
</style>

<main class="main container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Phân Công & Chấm Điểm</h2>
        <a href="?module=event&action=view&id=<?php echo $id_su_kien; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại sự kiện</a>
    </div>

    <ul class="nav nav-tabs mb-4 border-bottom">
        <li class="nav-item"><a class="nav-link <?php echo $active_tab == 1 ? 'active' : ''; ?>" href="?module=event&action=config_grading&id=<?php echo $id_su_kien; ?>&tab=1">1. Phân công độc lập</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $active_tab == 2 ? 'active' : ''; ?>" href="?module=event&action=config_grading&id=<?php echo $id_su_kien; ?>&tab=2">2. Tiến độ & Duyệt điểm</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $active_tab == 3 ? 'active' : ''; ?>" href="?module=event&action=config_grading&id=<?php echo $id_su_kien; ?>&tab=3">3. Xếp hạng bài thi</a></li>
    </ul>

    <div class="tab-content">
        
        <?php if ($active_tab == 1): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pt-3 pb-2 border-0">
                <h5 class="text-primary fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Phân công chấm bài thi đã nộp (Đánh giá độc lập)</h5>
                <p class="text-muted small mb-0">Việc phân công tại đây dùng cho vòng sơ loại chấm bài online. Các bài vượt qua sơ loại mới được đưa vào Tiểu ban.</p>
            </div>
            <div class="card-body p-0 mt-3">
                <table class="table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="35%" class="ps-4">Tên Bài thi / Sản phẩm</th>
                            <th width="30%">Giám khảo đã phân công</th>
                            <th width="15%" class="text-center">Trạng thái</th>
                            <th width="20%" class="text-center pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ds_sanpham)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-5">Chưa có bài nộp nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ds_sanpham as $sp): 
                                $assigned_gv_ids = !empty($phancong_map[$sp['idSanPham']]) ? array_column($phancong_map[$sp['idSanPham']], 'idGV') : [];
                            ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($sp['tensanpham']); ?></div>
                                        <small class="text-secondary"><i class="bi bi-people-fill me-1"></i> Nhóm: <?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($assigned_gv_ids)): foreach ($phancong_map[$sp['idSanPham']] as $pc): ?>
                                            <span class="badge-gk">
                                                <i class="bi bi-person text-primary"></i> <?php echo htmlspecialchars($pc['tenGV']); ?>
                                                <form method="post" class="d-inline ms-1 m-0 p-0">
                                                    <input type="hidden" name="action" value="remove_doclap"><input type="hidden" name="active_tab" value="1">
                                                    <input type="hidden" name="idSanPham" value="<?php echo $sp['idSanPham']; ?>">
                                                    <input type="hidden" name="idGV" value="<?php echo $pc['idGV']; ?>"><input type="hidden" name="idVongThi" value="<?php echo $pc['idVongThi']; ?>">
                                                    <button type="submit" class="btn-close ms-1" style="font-size:0.5rem;"></button>
                                                </form>
                                            </span>
                                        <?php endforeach; else: echo "<span class='text-muted small'>---</span>"; endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($assigned_gv_ids)): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">Đã phân công</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill">Chờ phân công</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#mAssign_<?php echo $sp['idSanPham']; ?>">
                                            <i class="bi bi-person-plus-fill"></i> Phân công
                                        </button>
                                        <button class="btn btn-outline-primary btn-action ms-1" data-bs-toggle="modal" data-bs-target="#mViewSP_<?php echo $sp['idSanPham']; ?>">
                                            <i class="bi bi-eye"></i> Xem
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php foreach ($ds_sanpham as $sp): 
            $assigned_gv_ids = !empty($phancong_map[$sp['idSanPham']]) ? array_column($phancong_map[$sp['idSanPham']], 'idGV') : [];
            $available_gvs = array_filter($ds_giangvien, fn($gv) => !in_array($gv['idGV'], $assigned_gv_ids));
        ?>
        <div class="modal fade" id="mAssign_<?php echo $sp['idSanPham']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post">
                        <input type="hidden" name="action" value="assign_doclap"><input type="hidden" name="active_tab" value="1"><input type="hidden" name="idSanPham" value="<?php echo $sp['idSanPham']; ?>">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Phân công người chấm</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="bg-light p-3 rounded mb-4 border text-start">
                                <div class="mb-2"><strong>Nhóm:</strong> <span class="text-primary"><?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?></span></div>
                                <div><strong>Đề tài:</strong> <?php echo htmlspecialchars($sp['tensanpham']); ?></div>
                            </div>
                            <div class="mb-3 text-start">
                                <label class="fw-bold mb-2">Chọn vòng thi</label>
                                <select name="idVongThi" class="form-select" required>
                                    <option value="">-- Chọn vòng thi --</option>
                                    <?php foreach($vong_array as $v): ?><option value="<?=$v['idVongThi']?>"><?=$v['tenVongThi']?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="text-start">
                                <label class="fw-bold mb-2">Chọn giảng viên</label>
                                <select name="idGV" class="form-select" required>
                                    <option value="">-- Chọn giảng viên --</option>
                                    <?php foreach($available_gvs as $gv): ?><option value="<?=$gv['idGV']?>"><?=$gv['tenGV']?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Lưu phân công</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php // ===== MODALS XEM SẢN PHẨM ===== 
        foreach ($ds_sanpham as $sp):
            $sp_files = $files_map[$sp['idSanPham']] ?? [];
        ?>
        <div class="modal fade" id="mViewSP_<?php echo $sp['idSanPham']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;">
                        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Bài nộp của nhóm</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="bg-light rounded p-3 mb-4 border">
                            <div class="mb-1"><strong>Nhóm:</strong> <span class="text-primary"><?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?></span></div>
                            <div class="mb-1"><strong>Đề tài:</strong> <?php echo htmlspecialchars($sp['tensanpham']); ?></div>
                            <div><strong>Trạng thái:</strong>
                                <span class="badge ms-1 <?php echo $sp['TrangThai']=='Đã duyệt' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo htmlspecialchars($sp['TrangThai'] ?? 'Chờ duyệt'); ?>
                                </span>
                            </div>
                        </div>
                        <?php if (empty($sp_files)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Nhóm chưa nộp tài liệu nào.
                            </div>
                        <?php else: ?>
                            <h6 class="fw-bold mb-3 text-secondary text-uppercase small">Tài liệu đã nộp</h6>
                            <?php 
                            $loai_info = [
                                1 => ['icon'=>'bi-file-earmark-text', 'color'=>'text-primary', 'label'=>'Báo cáo tóm tắt'],
                                2 => ['icon'=>'bi-file-earmark-richtext', 'color'=>'text-info', 'label'=>'Báo cáo toàn văn'],
                                3 => ['icon'=>'bi-github', 'color'=>'text-dark', 'label'=>'Source Code'],
                            ];
                            foreach ($sp_files as $file): 
                                $li = $loai_info[$file['idloaitailieu']] ?? ['icon'=>'bi-file-earmark', 'color'=>'text-secondary', 'label'=>$file['tenLoai']];
                                $file_url = (strpos($file['moTataiLieu'],'http')===0) 
                                    ? $file['moTataiLieu'] 
                                    : _HOST_URL . '/' . $file['moTataiLieu'];
                            ?>
                            <div class="d-flex align-items-center gap-3 p-3 mb-2 border rounded bg-white">
                                <i class="bi <?php echo $li['icon']; ?> <?php echo $li['color']; ?> fs-4"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small"><?php echo htmlspecialchars($li['label']); ?></div>
                                    <div class="text-muted small text-truncate"><?php echo htmlspecialchars(basename($file['moTataiLieu'])); ?></div>
                                </div>
                                <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-download me-1"></i>Tải xuống
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($active_tab == 2): ?>
        <div>
            <div class="mb-4 d-flex align-items-center bg-white p-2 rounded shadow-sm border" style="width: fit-content;">
                <span class="fw-bold text-muted ms-2 me-3"><i class="bi bi-funnel-fill me-1"></i> Xem điểm vòng:</span>
                <?php if (empty($vong_array)): ?>
                    <span class="text-muted fst-italic">Chưa có cấu hình vòng thi</span>
                <?php else: ?>
                    <?php foreach($vong_array as $v): ?>
                        <a href="?module=event&action=config_grading&id=<?=$id_su_kien?>&tab=2&vong=<?=$v['idVongThi']?>" 
                           class="btn btn-sm <?= $active_vong == $v['idVongThi'] ? 'btn-primary' : 'btn-light text-dark' ?> me-2 rounded-pill px-3">
                            <?=$v['tenVongThi']?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pt-3 pb-2 d-flex justify-content-between align-items-center border-0">
                    <h5 class="fw-bold text-dark m-0">Quản lý Tiến độ chấm</h5>
                    <span class="small text-muted"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Lệch > 30% giữa các GK</span>
                </div>
                <div class="card-body p-0 mt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="15%" class="ps-4">Nhóm</th>
                                    <th width="25%">Đề tài</th>
                                    <th width="15%" class="text-center">Tiến độ</th>
                                    <th width="12%" class="text-center">Điểm TB</th>
                                    <th width="10%" class="text-center">Phân tích</th>
                                    <th width="23%" class="text-center pe-4">Xét duyệt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $modals_html = ''; 
                                if (empty($ranking_list)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">Chưa có bài thi nào phân công ở vòng này.</td></tr>
                                <?php else: foreach ($ranking_list as $sp): 
                                    $percent = $sp['tongGiamKhaoPhanCong'] > 0 ? round(($sp['soNguoiDaCham']/$sp['tongGiamKhaoPhanCong'])*100) : 0; 
                                ?>
                                    <tr>
                                        <td class="ps-4 text-secondary fw-bold"><?=htmlspecialchars($sp['tennhom'] ?: $sp['manhom'])?></td>
                                        <td class="text-dark fw-bold"><?=htmlspecialchars($sp['tensanpham'])?></td>
                                        <td class="text-center">
                                            <div class="fw-bold mb-1 text-primary"><?=$sp['soNguoiDaCham']?> / <?=$sp['tongGiamKhaoPhanCong']?> GK</div>
                                            <div class="progress" style="height: 6px;"><div class="progress-bar <?= $percent == 100 ? 'bg-success' : 'bg-info' ?>" style="width: <?=$percent?>%;"></div></div>
                                        </td>
                                        <td class="text-center fw-bold fs-5 <?= $sp['isWarning'] ? 'text-warning' : 'text-danger' ?>">
                                            <?=$sp['isWarning'] ? '<i class="bi bi-exclamation-triangle-fill me-1" title="Lệch điểm cao"></i>' : ''?>
                                            <?=$sp['diemTB'] > 0 ? number_format($sp['diemTB'], 1) : '-'?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($sp['soNguoiDaCham'] > 0): ?>
                                                <button class="btn btn-sm btn-light text-primary border rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#mDetail_<?=$sp['idSanPham']?>">Chi tiết</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light text-muted border rounded-pill px-3" disabled>Chi tiết</button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-4">
                                            <?php if ($sp['trangThaiDuyet'] === 'Chưa duyệt' && $sp['tongGiamKhaoPhanCong'] > 0 && $sp['soNguoiDaCham'] == $sp['tongGiamKhaoPhanCong']): ?>
                                                <?php if ($sp['isWarning']): ?>
                                                    <button type="button" class="btn btn-warning text-dark btn-sm rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#mDetail_<?=$sp['idSanPham']?>" title="Cần xem xét chênh lệch điểm">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Quyết định
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#mDetail_<?=$sp['idSanPham']?>" title="Vào chi tiết để chốt điểm">
                                                        <i class="bi bi-check2-circle me-1"></i> Quyết định
                                                    </button>
                                                <?php endif; ?>
                                            <?php elseif ($sp['trangThaiDuyet'] === 'Đã duyệt'): ?>
                                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Đã duyệt (<?=number_format($sp['diemChot'],1)?>)</span>
                                            <?php elseif ($sp['trangThaiDuyet'] === 'Bị loại'): ?>
                                                <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i> Đã loại</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">Đang chấm...</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <?php 
                                    if ($sp['soNguoiDaCham'] > 0) {
                                        $idSP_modal = $sp['idSanPham'];
                                        
                                        $sql_tc_real = "
                                            SELECT 
                                                tc.idTieuChi, 
                                                tc.noiDungTieuChi as ten, 
                                                btt.diemToiDa as max, 
                                                ctc.diem, 
                                                ctc.idPhanCongCham
                                            FROM chamtieuchi ctc
                                            JOIN tieuchi tc ON ctc.idTieuChi = tc.idTieuChi
                                            JOIN phancongcham pcc ON ctc.idPhanCongCham = pcc.idPhanCongCham
                                            LEFT JOIN botieuchi_tieuchi btt ON tc.idTieuChi = btt.idTieuChi AND btt.idBoTieuChi = pcc.idBoTieuChi
                                            WHERE ctc.idSanPham = $idSP_modal AND pcc.idVongThi = $active_vong
                                            ORDER BY tc.idTieuChi, ctc.idPhanCongCham
                                        ";
                                        $res_tc_real = mysqli_query($conn, $sql_tc_real);
                                        $temp_tc_map = [];

                                        if ($res_tc_real) {
                                            while($r = mysqli_fetch_assoc($res_tc_real)) {
                                                $idTC = $r['idTieuChi'];
                                                if(!isset($temp_tc_map[$idTC])) {
                                                    $temp_tc_map[$idTC] = [
                                                        'ten' => $r['ten'],
                                                        'max' => $r['max'] ?? 10,
                                                        'diem' => []
                                                    ];
                                                }
                                                $temp_tc_map[$idTC]['diem'][] = (float)$r['diem'];
                                            }
                                        }
                                        $tieu_chi_list = array_values($temp_tc_map);
                                        $tong_tc = count($tieu_chi_list);
                                        
                                        $so_tieu_chi_canh_bao = 0;
                                        $max_lech_percent = 0;
                                        $ten_tc_lech_nhat = '';
                                        
                                        $assigned_gv_ids_modal = !empty($phancong_map[$sp['idSanPham']]) ? array_column($phancong_map[$sp['idSanPham']], 'idGV') : [];
                                        $available_gvs_for_this = array_filter($ds_giangvien, fn($gv) => !in_array($gv['idGV'], $assigned_gv_ids_modal));

                                        ob_start(); 
                                    ?>
                                        <div class="modal fade" id="mDetail_<?=$sp['idSanPham']?>" tabindex="-1">
                                            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                                <div class="modal-content border-0 shadow-lg">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-bar-chart-steps me-2"></i>Phân tích điểm - <?=htmlspecialchars($sp['tensanpham'])?></h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4 bg-light">
                                                        
                                                        <div class="mb-4 text-start">
                                                            <div class="fs-6 mb-1"><strong>Nhóm:</strong> <span class="text-secondary"><?=htmlspecialchars($sp['tennhom'] ?: $sp['manhom'])?></span></div>
                                                            <div class="fs-6 mb-1"><strong>Đề tài:</strong> <span class="text-primary"><?=htmlspecialchars($sp['tensanpham'])?></span></div>
                                                            <div class="fs-6 mb-3"><strong>Số người chấm:</strong> <span class="badge bg-secondary rounded-pill"><?=$sp['soNguoiDaCham']?></span></div>
                                                        </div>

                                                        <?php if ($tong_tc > 0): ?>
                                                            <h6 class="fw-bold mb-3 text-start"><i class="bi bi-table me-2"></i>Bảng điểm chi tiết</h6>
                                                            <div class="table-responsive shadow-sm bg-white rounded mb-4 border">
                                                                <table class="table table-bordered table-hover align-middle mb-0 text-center">
                                                                    <thead class="bg-primary text-white">
                                                                        <tr>
                                                                            <th width="35%" class="text-start ps-3">Tiêu chí</th>
                                                                            <?php for($i=1; $i<=$sp['soNguoiDaCham']; $i++): ?>
                                                                                <th>Giám khảo <?=$i?></th>
                                                                            <?php endfor; ?>
                                                                            <th width="8%">TB</th>
                                                                            <th width="8%">Độ lệch</th>
                                                                            <th width="10%">Cảnh báo</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php 
                                                                            $tong_tb = 0;
                                                                            $tong_gk = array_fill(0, $sp['soNguoiDaCham'], 0);
                                                                            
                                                                            foreach($tieu_chi_list as $tc): 
                                                                                $diem_count = count($tc['diem']);
                                                                                $tb_tc = $diem_count > 0 ? array_sum($tc['diem']) / $diem_count : 0;
                                                                                $tong_tb += $tb_tc;
                                                                                
                                                                                $lech = $diem_count > 0 ? (max($tc['diem']) - min($tc['diem'])) : 0;
                                                                                $lech_percent = $tb_tc > 0 ? ($lech / $tb_tc) * 100 : ($lech > 0 ? 100 : 0);
                                                                                
                                                                                if($lech_percent > $max_lech_percent) {
                                                                                    $max_lech_percent = $lech_percent;
                                                                                    $ten_tc_lech_nhat = $tc['ten'];
                                                                                }
                                                                                
                                                                                $is_tc_warning = $lech_percent > 30; // Ngưỡng lệch 30%
                                                                                if($is_tc_warning) $so_tieu_chi_canh_bao++;
                                                                        ?>
                                                                        <tr>
                                                                            <td class="text-start ps-3 fw-bold text-dark"><?=$tc['ten']?> <span class="text-muted fw-normal">(/<?=$tc['max']?>)</span></td>
                                                                            <?php foreach($tc['diem'] as $idx => $diem): 
                                                                                if (isset($tong_gk[$idx])) {
                                                                                    $tong_gk[$idx] += $diem;
                                                                                }
                                                                            ?>
                                                                                <td><?=$diem?></td>
                                                                            <?php endforeach; ?>
                                                                            
                                                                            <?php for($k=$diem_count; $k<$sp['soNguoiDaCham']; $k++): ?>
                                                                                <td>-</td>
                                                                            <?php endfor; ?>
                                                                            
                                                                            <td class="fw-bold fs-6"><?=number_format($tb_tc, 2)?></td>
                                                                            <td><?=number_format($lech_percent, 1)?>%</td>
                                                                            <td class="<?= $is_tc_warning ? 'bg-warning bg-opacity-10' : '' ?>">
                                                                                <?php if($is_tc_warning): ?>
                                                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill"><i class="bi bi-exclamation-triangle-fill me-1"></i> Lệch cao</span>
                                                                                <?php else: ?>
                                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill"><i class="bi bi-check-lg me-1"></i> OK</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                    <tfoot class="table-light fw-bold fs-5 text-primary">
                                                                        <tr>
                                                                            <td class="text-center text-uppercase">TỔNG ĐIỂM</td>
                                                                            <?php foreach($tong_gk as $tong): ?>
                                                                                <td><?=$tong?></td>
                                                                            <?php endforeach; ?>
                                                                            <td class="text-danger"><?=number_format($tong_tb, 1)?></td>
                                                                            <td colspan="2"></td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>

                                                            <h6 class="fw-bold mb-3 mt-4 text-start"><i class="bi bi-clipboard-data me-2"></i>Kiểm định thống kê (Inter-Rater Reliability)</h6>
                                                            <div class="row g-3 mb-4">
                                                                <div class="col-md-4">
                                                                    <div class="card border-0 shadow-sm bg-white h-100">
                                                                        <div class="card-body text-start">
                                                                            <p class="text-muted small mb-1">Mức độ đồng thuận (Tổng thể):</p>
                                                                            <h3 class="fw-bold <?= $so_tieu_chi_canh_bao > 0 ? 'text-warning' : 'text-success' ?>">
                                                                                <?= number_format($tong_tc > 0 ? (100 - ($so_tieu_chi_canh_bao / $tong_tc * 100)) : 0, 1) ?>%
                                                                            </h3>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="card border-0 shadow-sm bg-white h-100">
                                                                        <div class="card-body text-start">
                                                                            <p class="text-muted small mb-1">Tiêu chí có vấn đề (Lệch cao):</p>
                                                                            <h3 class="fw-bold text-danger"><?=$so_tieu_chi_canh_bao?>/<?=$tong_tc?></h3>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="card border-0 shadow-sm bg-white h-100">
                                                                        <div class="card-body text-start">
                                                                            <p class="text-muted small mb-1">Độ lệch cao nhất:</p>
                                                                            <h5 class="fw-bold text-danger mb-0"><?=number_format($max_lech_percent, 1)?>%</h5>
                                                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 100%;" title="<?=$ten_tc_lech_nhat?>">(<?=$ten_tc_lech_nhat?>)</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="card border-0 shadow-sm text-start <?= $so_tieu_chi_canh_bao > 0 ? 'bg-warning bg-opacity-10 border-start border-4 border-warning' : 'bg-success bg-opacity-10 border-start border-4 border-success' ?>">
                                                                <div class="card-body p-4">
                                                                    <h6 class="fw-bold text-dark mb-2">Kết luận:</h6>
                                                                    <?php if($so_tieu_chi_canh_bao > 0): ?>
                                                                        <p class="text-danger fw-bold mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>CÓ SỰ KHÁC BIỆT: <?=$so_tieu_chi_canh_bao?>/<?=$tong_tc?> tiêu chí có độ lệch cao. Cần xem xét lại!</p>
                                                                        <h6 class="fw-bold text-dark mb-2">Khuyến nghị:</h6>
                                                                        <ul class="mb-0 text-muted">
                                                                            <li>Xem xét lại điểm số của các tiêu chí có độ lệch cao (được đánh dấu đỏ).</li>
                                                                            <li>Yêu cầu Hội đồng / Người chấm giải thích lý do cho điểm.</li>
                                                                            <li>Cân nhắc mời Giám khảo thứ 3 (nếu hiện tại chỉ có 2) để phúc khảo nhằm đưa ra quyết định công bằng.</li>
                                                                        </ul>
                                                                    <?php else: ?>
                                                                        <p class="text-success fw-bold mb-0"><i class="bi bi-check-circle-fill me-2"></i>ĐỒNG THUẬN CAO: Điểm số giữa các giám khảo rất sát nhau. Kết quả đánh giá đáng tin cậy.</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <?php if ($sp['trangThaiDuyet'] === 'Chưa duyệt' && $sp['soNguoiDaCham'] == $sp['tongGiamKhaoPhanCong']): ?>
                                                            <div class="card border-0 shadow-sm mt-4 bg-primary bg-opacity-10 border-start border-4 border-primary">
                                                                <div class="card-body p-4 text-start">
                                                                    <h6 class="fw-bold text-primary mb-3">Quyết định điểm chốt của Hội đồng</h6>
                                                                    <form method="post" class="d-flex align-items-center gap-3 flex-wrap">
                                                                        <input type="hidden" name="active_tab" value="2">
                                                                        <input type="hidden" name="idSanPham" value="<?=$sp['idSanPham']?>">
                                                                        <input type="hidden" name="idVongThi" value="<?=$active_vong?>">
                                                                        
                                                                        <div class="input-group" style="width: 200px;">
                                                                            <span class="input-group-text bg-white fw-bold text-dark">Điểm chốt:</span>
                                                                            <input type="number" step="0.01" name="diemChot" class="form-control fw-bold text-danger fs-5 text-center" value="<?=number_format($sp['diemTB'], 2, '.', '')?>" required>
                                                                        </div>
                                                                        
                                                                        <button type="submit" name="action" value="approve_score_manual" class="btn btn-primary fw-bold px-3"><i class="bi bi-check-circle me-1"></i>Duyệt & Chốt</button>
                                                                        <button type="submit" name="action" value="reject_score" class="btn btn-outline-danger bg-white fw-bold px-3"><i class="bi bi-x-circle me-1"></i>Đánh rớt</button>
                                                                    </form>
                                                                    <small class="text-muted mt-2 mb-4 d-block">* Điểm gợi ý hiện tại là trung bình cộng của <?=$sp['soNguoiDaCham']?> giám khảo. BTC có thể chốt trực tiếp hoặc sửa lại theo quyết định cuối cùng.</small>

                                                                    <?php if ($sp['isWarning']): ?>
                                                                        <hr class="my-4 border-primary opacity-25">
                                                                        <h6 class="fw-bold text-warning mb-2"><i class="bi bi-shield-exclamation me-2"></i>Mời thêm Trọng tài (Phúc khảo)</h6>
                                                                        <div class="bg-white p-3 rounded border border-warning shadow-sm">
                                                                            <p class="small text-muted mb-3">
                                                                                Bài thi đang có sự chênh lệch điểm lớn giữa các giám khảo. Để đảm bảo tính khách quan và công bằng, Ban tổ chức nên phân công thêm một Giám khảo thứ 3 (Trọng tài) để tham gia chấm phúc khảo trước khi đưa ra quyết định cuối cùng.
                                                                            </p>
                                                                            <form method="post" class="d-flex align-items-center gap-2 flex-wrap">
                                                                                <input type="hidden" name="active_tab" value="2">
                                                                                <input type="hidden" name="idSanPham" value="<?=$sp['idSanPham']?>">
                                                                                <input type="hidden" name="idVongThi" value="<?=$active_vong?>">
                                                                                
                                                                                <select name="idGV" class="form-select border-warning" style="max-width: 300px;" required>
                                                                                    <option value="">-- Chọn Giám khảo bổ sung --</option>
                                                                                    <?php foreach($available_gvs_for_this as $gv): ?>
                                                                                        <option value="<?=$gv['idGV']?>"><?=htmlspecialchars($gv['tenGV'])?></option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                                <button type="submit" name="action" value="add_3rd_judge" class="btn btn-warning text-dark fw-bold px-4 shadow-sm" onclick="if(!this.form.idGV.value){alert('Vui lòng chọn Trọng tài!'); return false;} return confirm('Hệ thống sẽ cập nhật lại tiến độ chấm của bài thi. Bạn có chắc chắn muốn mời thêm giám khảo này?');">
                                                                                    <i class="bi bi-person-plus-fill me-1"></i> Gửi lời mời Trọng tài
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                </div>
                                                            </div>
                                                            <?php endif; ?>

                                                        <?php else: ?>
                                                            <div class="alert alert-info text-center py-4">
                                                                Dữ liệu chấm điểm chi tiết của bài này chưa được đồng bộ đầy đủ.
                                                            </div>
                                                        <?php endif; ?>

                                                    </div>
                                                    <div class="modal-footer border-0 bg-light">
                                                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php 
                                        $modals_html .= ob_get_clean(); 
                                    } 
                                    ?>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?= $modals_html ?>
            
        </div>
        <?php endif; ?>

        <?php if ($active_tab == 3): ?>
        <div>
            <div class="mb-4 d-flex align-items-center bg-white p-2 rounded shadow-sm border" style="width: fit-content;">
                <span class="fw-bold text-muted ms-2 me-3"><i class="bi bi-trophy-fill text-warning me-1"></i> Bảng vàng vòng:</span>
                <?php if (!empty($vong_array)): foreach($vong_array as $v): ?>
                    <a href="?module=event&action=config_grading&id=<?=$id_su_kien?>&tab=3&vong=<?=$v['idVongThi']?>" 
                       class="btn btn-sm <?= $active_vong == $v['idVongThi'] ? 'btn-warning fw-bold' : 'btn-light text-dark' ?> me-2 rounded-pill px-3">
                        <?=$v['tenVongThi']?>
                    </a>
                <?php endforeach; endif; ?>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pt-3 pb-2 border-0">
                    <h5 class="fw-bold text-dark m-0">Xếp hạng các bài thi Đạt chuẩn</h5>
                    <p class="text-muted small m-0">Danh sách này chỉ hiển thị những bài thi đã được BTC "Duyệt" kết quả ở Tab 2. Sẵn sàng phân vào Tiểu ban (Vòng sau).</p>
                </div>
                <div class="card-body p-0 mt-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-warning text-dark">
                            <tr>
                                <th width="10%" class="text-center py-3">Hạng</th>
                                <th width="20%">Nhóm</th>
                                <th width="45%">Đề tài</th>
                                <th width="25%" class="text-center pe-4">Điểm Tổng Kết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approved_list)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted bg-white"><i class="bi bi-inbox fs-1 d-block mb-3"></i> Chưa có kết quả xếp hạng. Vui lòng duyệt điểm ở Tab 2 trước.</td></tr>
                            <?php else: $rank = 1; foreach ($approved_list as $sp): 
                                $is_top3 = $rank <= 3;
                            ?>
                                <tr class="<?= $is_top3 ? 'bg-warning bg-opacity-10' : '' ?>">
                                    <td class="text-center">
                                        <?php if ($rank == 1): ?> <i class="bi bi-trophy-fill text-warning fs-4"></i>
                                        <?php elseif ($rank == 2): ?> <i class="bi bi-trophy-fill text-secondary fs-4"></i>
                                        <?php elseif ($rank == 3): ?> <i class="bi bi-trophy-fill text-danger fs-4"></i>
                                        <?php else: ?> <span class="fw-bold text-muted fs-5">#<?=$rank?></span> <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-dark"><?=htmlspecialchars($sp['tennhom'] ?: $sp['manhom'])?></td>
                                    <td class="fw-bold text-primary"><?=htmlspecialchars($sp['tensanpham'])?></td>
                                    <td class="text-center pe-4 fw-bold fs-4 text-danger"><?=number_format($sp['diemChot'], 1)?></td>
                                </tr>
                            <?php $rank++; endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>
<?php layout('footer'); ?>