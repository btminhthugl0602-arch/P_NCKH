<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1; // Mặc định là Tab 1

// ==========================================
// AUTO-MIGRATION: Tự động cập nhật CSDL
// ==========================================
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tieuban_giangvien (
    idTieuBan INT NOT NULL, 
    idGV INT NOT NULL, 
    vaiTro VARCHAR(50) DEFAULT 'Thành viên', 
    PRIMARY KEY (idTieuBan, idGV)
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tieuban_sanpham (
    idTieuBan INT NOT NULL, 
    idSanPham INT NOT NULL, 
    PRIMARY KEY (idTieuBan, idSanPham)
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS canhbaodiem (
    idCanhBao INT AUTO_INCREMENT PRIMARY KEY, 
    idSanPham INT NOT NULL, 
    idVongThi INT NOT NULL, 
    doLech DECIMAL(5,2) NOT NULL, 
    trangThai VARCHAR(50) DEFAULT 'Chờ xử lý', 
    thoiGian DATETIME DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS phancong_doclap (
    idSanPham INT NOT NULL,
    idGV INT NOT NULL,
    idVongThi INT NOT NULL,
    PRIMARY KEY (idSanPham, idGV, idVongThi)
)");

$chk = mysqli_query($conn, "SHOW COLUMNS FROM tieuban LIKE 'idVongThi'");
if (mysqli_num_rows($chk) == 0) mysqli_query($conn, "ALTER TABLE tieuban ADD COLUMN idVongThi INT NULL AFTER idSK");

$chk2 = mysqli_query($conn, "SHOW COLUMNS FROM tieuban LIKE 'ngayBaoCao'");
if (mysqli_num_rows($chk2) == 0) {
    mysqli_query($conn, "ALTER TABLE tieuban ADD COLUMN ngayBaoCao DATE NULL");
    mysqli_query($conn, "ALTER TABLE tieuban ADD COLUMN diaDiem VARCHAR(100) NULL");
}

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
    } elseif ($action === 'remove_doclap') {
        $idSP = (int)($data['idSanPham'] ?? 0);
        $idGV = (int)($data['idGV'] ?? 0);
        $idVong = (int)($data['idVongThi'] ?? 0);
        mysqli_query($conn, "DELETE FROM phancong_doclap WHERE idSanPham = $idSP AND idGV = $idGV AND idVongThi = $idVong");
    } elseif ($action === 'create_tb') {
        $tenTB = trim($data['tenTieuBan'] ?? '');
        $idVong = (int)($data['idVongThi'] ?? 0);
        $ngayBaoCao = !empty($data['ngayBaoCao']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['ngayBaoCao']) . "'" : "NULL";
        $diaDiem = !empty($data['diaDiem']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['diaDiem']) . "'" : "NULL";

        if (!empty($tenTB) && $idVong > 0) {
            mysqli_query($conn, "INSERT INTO tieuban (idSK, idVongThi, tenTieuBan, ngayBaoCao, diaDiem) VALUES ($id_su_kien, $idVong, '" . chuan_hoa_chuoi_sql($conn, $tenTB) . "', $ngayBaoCao, $diaDiem)");
        }
    } elseif ($action === 'edit_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $tenTB = trim($data['tenTieuBan'] ?? '');
        $ngayBaoCao = !empty($data['ngayBaoCao']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['ngayBaoCao']) . "'" : "NULL";
        $diaDiem = !empty($data['diaDiem']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['diaDiem']) . "'" : "NULL";

        if ($idTB > 0 && !empty($tenTB)) {
            mysqli_query($conn, "UPDATE tieuban SET tenTieuBan = '" . chuan_hoa_chuoi_sql($conn, $tenTB) . "', ngayBaoCao = $ngayBaoCao, diaDiem = $diaDiem WHERE idTieuBan = $idTB");
        }
    } elseif ($action === 'delete_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        if ($idTB > 0) {
            mysqli_query($conn, "DELETE FROM tieuban WHERE idTieuBan = $idTB");
            mysqli_query($conn, "DELETE FROM tieuban_giangvien WHERE idTieuBan = $idTB");
            mysqli_query($conn, "DELETE FROM tieuban_sanpham WHERE idTieuBan = $idTB");
        }
    } elseif ($action === 'add_gv_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idGV = (int)($data['idGV'] ?? 0);
        if ($idTB > 0 && $idGV > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO tieuban_giangvien (idTieuBan, idGV) VALUES ($idTB, $idGV)");
        }
    } elseif ($action === 'remove_gv_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idGV = (int)($data['idGV'] ?? 0);
        mysqli_query($conn, "DELETE FROM tieuban_giangvien WHERE idTieuBan = $idTB AND idGV = $idGV");
    } elseif ($action === 'add_sp_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idSP = (int)($data['idSanPham'] ?? 0);
        if ($idTB > 0 && $idSP > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO tieuban_sanpham (idTieuBan, idSanPham) VALUES ($idTB, $idSP)");
        }
    } elseif ($action === 'remove_sp_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idSP = (int)($data['idSanPham'] ?? 0);
        mysqli_query($conn, "DELETE FROM tieuban_sanpham WHERE idTieuBan = $idTB AND idSanPham = $idSP");
    }

    header("Location: ?module=event&action=config_assign&id=$id_su_kien&tab=$current_tab");
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU ĐỔ RA GIAO DIỆN
// ==========================================
$vong_list = mysqli_query($conn, "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC");
$vong_array = $vong_list ? mysqli_fetch_all($vong_list, MYSQLI_ASSOC) : [];

// Lấy Vòng thi đang chọn để xem Tiến độ (Mặc định là vòng đầu tiên)
$active_vong = isset($_GET['vong']) ? (int)$_GET['vong'] : 0;
if ($active_vong == 0 && !empty($vong_array)) {
    $active_vong = $vong_array[0]['idVongThi'];
}

$res_gv_all = mysqli_query($conn, "SELECT idGV, tenGV FROM giangvien ORDER BY tenGV ASC");
$ds_giangvien = $res_gv_all ? mysqli_fetch_all($res_gv_all, MYSQLI_ASSOC) : [];

$sql_sp = "SELECT sp.idSanPham, sp.tensanpham, n.manhom, ttn.tennhom, sv.tenSV as nhomTruong 
           FROM sanpham sp 
           LEFT JOIN nhom n ON sp.idNhom = n.idnhom 
           LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom 
           LEFT JOIN sinhvien sv ON n.idChuNhom = sv.idTK 
           WHERE sp.idSK = $id_su_kien ORDER BY sp.idSanPham DESC";
$res_sp = mysqli_query($conn, $sql_sp);
$ds_sanpham = $res_sp ? mysqli_fetch_all($res_sp, MYSQLI_ASSOC) : [];

// --- Dữ liệu Tab 1 (Phân công độc lập) ---
$sql_pc = "SELECT pcd.*, gv.tenGV, v.tenVongThi FROM phancong_doclap pcd JOIN giangvien gv ON pcd.idGV = gv.idGV JOIN vongthi v ON pcd.idVongThi = v.idVongThi WHERE v.idSK = $id_su_kien";
$res_pc = mysqli_query($conn, $sql_pc);
$ds_phancong = $res_pc ? mysqli_fetch_all($res_pc, MYSQLI_ASSOC) : [];
$phancong_map = [];
foreach ($ds_phancong as $pc) {
    $phancong_map[$pc['idSanPham']][] = $pc;
}

// --- Dữ liệu Tab 2 (TÍNH TOÁN ĐIỂM CHUẨN XÁC) ---
// ĐÃ SỬA: CHỈ đếm số Giám khảo được phân công ở Tab 1 (phancong_doclap) theo Vòng đang chọn. Tách biệt hoàn toàn với Tiểu ban.
$sql_tiendo = "
    SELECT 
        sp.idSanPham, 
        sp.tensanpham, 
        n.manhom, 
        ttn.tennhom,
        IFNULL(pc.tongGiamKhaoPhanCong, 0) as tongGiamKhaoPhanCong
    FROM sanpham sp
    LEFT JOIN nhom n ON sp.idNhom = n.idnhom
    LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
    JOIN (
        SELECT idSanPham, COUNT(idGV) as tongGiamKhaoPhanCong
        FROM phancong_doclap
        WHERE idVongThi = $active_vong
        GROUP BY idSanPham
    ) pc ON sp.idSanPham = pc.idSanPham
    WHERE sp.idSK = $id_su_kien
    ORDER BY sp.idSanPham DESC
";
$res_td = mysqli_query($conn, $sql_tiendo);
$ds_tiendo = $res_td ? mysqli_fetch_all($res_td, MYSQLI_ASSOC) : [];

// Lấy điểm (Chỉ lấy điểm của Vòng đang chọn - Cần bảng chamtieuchi có liên kết vòng thi hoặc qua bảng trung gian, tạm lấy theo idSanPham)
$sql_scores = "SELECT idSanPham, idPhanCongCham, SUM(diem) as tongDiem 
               FROM chamtieuchi 
               WHERE idSanPham IN (SELECT idSanPham FROM sanpham WHERE idSK = $id_su_kien) 
               GROUP BY idSanPham, idPhanCongCham";
$res_scores = mysqli_query($conn, $sql_scores);
$scores_map = [];
if ($res_scores) {
    while ($row = mysqli_fetch_assoc($res_scores)) {
        $scores_map[$row['idSanPham']][] = (float)$row['tongDiem'];
    }
}

// Xử lý xếp hạng
$ranking_list = [];
foreach ($ds_tiendo as $sp) {
    $idSP = $sp['idSanPham'];
    $sp_scores = $scores_map[$idSP] ?? [];

    $soNguoiDaCham = count($sp_scores);
    $diemTB = 0;
    $doLech = 0;
    $isWarning = false;

    if ($soNguoiDaCham > 0) {
        $diemTB = array_sum($sp_scores) / $soNguoiDaCham;
        $maxDiem = max($sp_scores);
        $minDiem = min($sp_scores);
        $doLech = $maxDiem - $minDiem;

        if ($soNguoiDaCham > 1 && $doLech >= ($diemTB * 0.3)) {
            $isWarning = true;
        }
    }

    $sp['soNguoiDaCham'] = $soNguoiDaCham;
    $sp['diemTB'] = $diemTB;
    $sp['isWarning'] = $isWarning;
    $sp['chiTietDiem'] = $sp_scores;

    $ranking_list[] = $sp;
}
usort($ranking_list, function ($a, $b) {
    return $b['diemTB'] <=> $a['diemTB'];
});

// --- Dữ liệu Tab 3 & 4 (Quản lý Tiểu ban) ---
$sql_tb = "SELECT tb.*, v.tenVongThi FROM tieuban tb LEFT JOIN vongthi v ON tb.idVongThi = v.idVongThi WHERE tb.idSK = $id_su_kien ORDER BY tb.idTieuBan ASC";
$res_tb = mysqli_query($conn, $sql_tb);
$tieuban_list = $res_tb ? mysqli_fetch_all($res_tb, MYSQLI_ASSOC) : [];

$sql_gv_tb = "SELECT tbg.*, gv.tenGV FROM tieuban_giangvien tbg JOIN giangvien gv ON tbg.idGV = gv.idGV WHERE tbg.idTieuBan IN (SELECT idTieuBan FROM tieuban WHERE idSK = $id_su_kien)";
$res_gv_tb = mysqli_query($conn, $sql_gv_tb);
$gv_tb_map = [];
if ($res_gv_tb) {
    while ($r = mysqli_fetch_assoc($res_gv_tb)) {
        $gv_tb_map[$r['idTieuBan']][] = $r;
    }
}

$sql_sp_tb = "SELECT tbs.*, sp.tensanpham, n.manhom, ttn.tennhom FROM tieuban_sanpham tbs JOIN sanpham sp ON tbs.idSanPham = sp.idSanPham LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom WHERE tbs.idTieuBan IN (SELECT idTieuBan FROM tieuban WHERE idSK = $id_su_kien)";
$res_sp_tb = mysqli_query($conn, $sql_sp_tb);
$sp_tb_map = [];
$assigned_sp_ids = [];
if ($res_sp_tb) {
    while ($r = mysqli_fetch_assoc($res_sp_tb)) {
        $sp_tb_map[$r['idTieuBan']][] = $r;
        $assigned_sp_ids[] = $r['idSanPham'];
    }
}

$total_tb = count($tieuban_list);
$unassigned_sps = [];
foreach ($ds_sanpham as $sp) {
    if (!in_array($sp['idSanPham'], $assigned_sp_ids)) $unassigned_sps[] = $sp;
}

layout('header');
layout('navbar');
?>

<style>
    .nav-tabs .nav-link {
        font-weight: 500;
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        font-weight: bold;
        border-bottom: 3px solid #0d6efd;
    }

    .tb-overview-card {
        border-radius: 12px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        padding: 20px;
        background: #fff;
    }

    .tb-overview-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        margin-right: 15px;
    }

    .tb-detail-card {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        background: #fff;
    }

    .tb-detail-header {
        display: flex;
        justify-content: space-between;
        padding: 15px 20px;
        border-bottom: 1px solid #f1f3f5;
    }

    .tb-detail-body {
        padding: 20px;
    }

    .pill-badge {
        background-color: #f1f3f5;
        color: #495057;
        border: 1px solid #e9ecef;
        padding: 6px 15px;
        border-radius: 50rem;
        display: inline-flex;
        align-items: center;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .table-ranking th {
        background-color: #1a56db !important;
        color: white !important;
        font-weight: 600;
        padding: 12px 15px;
        border-bottom: 0;
    }

    .row-top-rank {
        background-color: #fff9e6 !important;
    }
</style>

<main class="main container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Phân Công & Quản Lý Chấm Thi</h2>
        <a href="?module=event&action=view&id=<?php echo $id_su_kien; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại sự kiện
        </a>
    </div>

    <ul class="nav nav-tabs mb-4" id="assignTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 1 ? 'active' : ''; ?>"
                href="?module=event&action=config_assign&id=<?php echo $id_su_kien; ?>&tab=1">
                <i class="bi bi-list-check me-1"></i> Phân công độc lập (Sơ loại)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 2 ? 'active' : ''; ?>"
                href="?module=event&action=config_assign&id=<?php echo $id_su_kien; ?>&tab=2">
                <i class="bi bi-bar-chart-line me-1"></i> Kết quả chấm điểm
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 3 ? 'active' : ''; ?>"
                href="?module=event&action=config_assign&id=<?php echo $id_su_kien; ?>&tab=3">
                <i class="bi bi-diagram-3 me-1"></i> Quản lý Tiểu ban (Báo cáo)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 4 ? 'active' : ''; ?>"
                href="?module=event&action=config_assign&id=<?php echo $id_su_kien; ?>&tab=4">
                <i class="bi bi-people me-1"></i> Phân công Ban Giám Khảo
            </a>
        </li>
    </ul>

    <div class="tab-content">

        <?php if ($active_tab == 1): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom pt-3 pb-3">
                    <h5 class="mb-0 text-primary"><i class="bi bi-card-checklist me-2"></i>Phân công chấm bài thi đã nộp
                        (Đánh giá độc lập)</h5>
                    <small class="text-muted">Việc phân công tại đây dùng cho vòng sơ loại chấm bài online. Các bài vượt qua
                        sơ loại mới được đưa vào Tiểu ban (Tab 3).</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="35%">Tên Bài thi / Sản phẩm</th>
                                    <th width="25%">Giám khảo đã phân công</th>
                                    <th width="15%" class="text-center">Trạng thái</th>
                                    <th width="25%" class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ds_sanpham)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Chưa có bài nộp nào.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ds_sanpham as $sp):
                                        $assigned_gv_ids = [];
                                        $is_assigned = false;
                                        if (!empty($phancong_map[$sp['idSanPham']])) {
                                            $is_assigned = true;
                                            foreach ($phancong_map[$sp['idSanPham']] as $pc) {
                                                $assigned_gv_ids[] = $pc['idGV'];
                                            }
                                        }
                                        $available_gvs = array_filter($ds_giangvien, function ($gv) use ($assigned_gv_ids) {
                                            return !in_array($gv['idGV'], $assigned_gv_ids);
                                        });
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark mb-1">
                                                    <?php echo htmlspecialchars($sp['tensanpham']); ?></div>
                                                <small class="text-muted"><i class="bi bi-people-fill me-1"></i> Nhóm:
                                                    <?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($is_assigned): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($phancong_map[$sp['idSanPham']] as $pc): ?>
                                                            <span
                                                                class="badge bg-light text-dark border d-flex align-items-center py-1 px-2">
                                                                <i class="bi bi-person text-primary me-1"></i>
                                                                <?php echo htmlspecialchars($pc['tenGV']); ?>
                                                                <form method="post" class="d-inline ms-2 m-0 p-0">
                                                                    <input type="hidden" name="action" value="remove_doclap"><input
                                                                        type="hidden" name="active_tab" value="1">
                                                                    <input type="hidden" name="idSanPham"
                                                                        value="<?php echo $sp['idSanPham']; ?>">
                                                                    <input type="hidden" name="idGV"
                                                                        value="<?php echo $pc['idGV']; ?>"><input type="hidden"
                                                                        name="idVongThi" value="<?php echo $pc['idVongThi']; ?>">
                                                                    <button type="submit" class="btn-close" style="font-size: 0.5rem;"
                                                                        title="Xóa"></button>
                                                                </form>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?><span class="text-muted small fst-italic">---</span><?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($is_assigned): ?><span
                                                        class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">Đã
                                                        phân công</span>
                                                <?php else: ?><span
                                                        class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2">Chờ
                                                        phân công</span><?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal"
                                                    data-bs-target="#modalAssign_<?php echo $sp['idSanPham']; ?>"><i
                                                        class="bi bi-person-plus-fill me-1"></i> Phân công</button>
                                                <a href="?module=event&action=view_product_detail&id=<?php echo $sp['idSanPham']; ?>"
                                                    class="btn btn-primary btn-sm rounded-pill px-3 ms-1"><i
                                                        class="bi bi-eye-fill me-1"></i> Xem</a>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="modalAssign_<?php echo $sp['idSanPham']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="assign_doclap"><input
                                                            type="hidden" name="active_tab" value="1"><input type="hidden"
                                                            name="idSanPham" value="<?php echo $sp['idSanPham']; ?>">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title fs-5"><i
                                                                    class="bi bi-person-lines-fill me-2"></i>Phân công người chấm
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-4 text-start">
                                                            <div class="bg-light p-3 rounded mb-4 border">
                                                                <div class="mb-2"><strong>Nhóm:</strong> <span
                                                                        class="text-primary"><?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?></span>
                                                                </div>
                                                                <div><strong>Đề tài:</strong>
                                                                    <?php echo htmlspecialchars($sp['tensanpham']); ?></div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Chọn vòng thi (Thường là sơ loại)
                                                                    <span class="text-danger">*</span></label>
                                                                <select name="idVongThi" class="form-select" required>
                                                                    <option value="">-- Chọn vòng thi --</option>
                                                                    <?php foreach ($vong_array as $v): ?><option
                                                                            value="<?php echo $v['idVongThi']; ?>">
                                                                            <?php echo htmlspecialchars($v['tenVongThi']); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label fw-bold">Chọn giám khảo <span
                                                                        class="text-danger">*</span></label>
                                                                <select name="idGV" class="form-select" required>
                                                                    <option value="">-- Chọn giám khảo --</option>
                                                                    <?php if (empty($available_gvs)): ?><option value="" disabled>Tất
                                                                            cả GK đã được phân công</option>
                                                                        <?php else: foreach ($available_gvs as $gv): ?><option
                                                                                value="<?php echo $gv['idGV']; ?>">
                                                                                <?php echo htmlspecialchars($gv['tenGV']); ?></option>
                                                                    <?php endforeach;
                                                                    endif; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Đóng</button>
                                                            <button type="submit" class="btn btn-primary"><i
                                                                    class="bi bi-check-lg me-1"></i> Lưu phân công</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($active_tab == 2): ?>
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0"><i class="bi bi-bar-chart-line-fill me-2"></i>Kết quả chấm điểm</h4>
                </div>

                <div class="mb-4">
                    <ul class="nav nav-pills bg-white p-2 rounded shadow-sm border d-inline-flex">
                        <li class="nav-item me-2 d-flex align-items-center ps-2 fw-bold text-muted">
                            <i class="bi bi-funnel-fill me-2"></i> Xem điểm theo vòng thi (Chấm độc lập):
                        </li>
                        <?php if (empty($vong_array)): ?>
                            <li class="nav-item"><span class="nav-link text-muted">Chưa cấu hình vòng thi</span></li>
                        <?php else: ?>
                            <?php foreach ($vong_array as $v): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $active_vong == $v['idVongThi'] ? 'active' : ''; ?>"
                                        href="?module=event&action=config_assign&id=<?php echo $id_su_kien; ?>&tab=2&vong=<?php echo $v['idVongThi']; ?>">
                                        <?php echo htmlspecialchars($v['tenVongThi']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white pt-3 pb-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-primary">Bảng xếp hạng điểm</h5>
                        <span class="small text-muted"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> =
                            Lệch > 30% giữa các giám khảo</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-ranking">
                                <thead>
                                    <tr>
                                        <th width="8%" class="text-center">Hạng</th>
                                        <th width="15%">Nhóm</th>
                                        <th width="25%">Đề tài</th>
                                        <th width="15%" class="text-center">Tiến độ chấm</th>
                                        <th width="12%" class="text-center">Điểm TB</th>
                                        <th width="10%" class="text-center">Phân tích</th>
                                        <th width="15%" class="text-center">Trạng thái xét</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ranking_list)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5 bg-white">Chưa có bài thi nào
                                                được phân công chấm ở vòng thi này.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $rank = 1;
                                        foreach ($ranking_list as $sp):
                                            $row_class = ($rank <= 3 && $sp['diemTB'] > 0) ? 'row-top-rank' : 'bg-white';
                                            $tiendo_percent = ($sp['tongGiamKhaoPhanCong'] > 0) ? round(($sp['soNguoiDaCham'] / $sp['tongGiamKhaoPhanCong']) * 100) : 0;
                                        ?>
                                            <tr class="<?php echo $row_class; ?>">
                                                <td class="text-center fw-bold fs-5 text-dark">
                                                    <?php echo $sp['diemTB'] > 0 ? '#' . $rank++ : '-'; ?>
                                                </td>
                                                <td class="text-secondary">
                                                    <?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?></td>
                                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($sp['tensanpham']); ?>
                                                </td>

                                                <td class="text-center">
                                                    <div class="fw-bold mb-1 text-primary">
                                                        <?php echo $sp['soNguoiDaCham']; ?> /
                                                        <?php echo $sp['tongGiamKhaoPhanCong']; ?> GK
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar <?php echo $tiendo_percent == 100 ? 'bg-success' : 'bg-info'; ?>"
                                                            style="width: <?php echo $tiendo_percent; ?>%;"></div>
                                                    </div>
                                                </td>

                                                <td class="text-center fw-bold text-primary fs-5">
                                                    <?php if ($sp['isWarning']): ?>
                                                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"
                                                            title="Điểm có độ lệch cao"></i>
                                                    <?php endif; ?>
                                                    <?php echo $sp['diemTB'] > 0 ? number_format($sp['diemTB'], 1) : '<span class="text-muted fs-6 fw-normal">Chưa có</span>'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($sp['soNguoiDaCham'] > 0): ?>
                                                        <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal"
                                                            data-bs-target="#modalPhanTich_<?php echo $sp['idSanPham']; ?>">
                                                            <i class="bi bi-graph-up me-1"></i> Chi tiết
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>Chi
                                                            tiết</button>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($sp['diemTB'] >= 40): ?>
                                                        <span
                                                            class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-2">Đạt
                                                            yêu cầu</span>
                                                    <?php elseif ($sp['diemTB'] > 0): ?>
                                                        <span
                                                            class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-3 py-2">Loại</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-2">Đang
                                                            chấm</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <?php if ($sp['soNguoiDaCham'] > 0): ?>
                                                <div class="modal fade" id="modalPhanTich_<?php echo $sp['idSanPham']; ?>"
                                                    tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-light">
                                                                <h5 class="modal-title fw-bold text-dark"><i
                                                                        class="bi bi-bar-chart-steps text-primary me-2"></i>Chi tiết
                                                                    phân tích điểm</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body p-4">
                                                                <div class="mb-4 text-center">
                                                                    <h5 class="text-primary">
                                                                        <?php echo htmlspecialchars($sp['tensanpham']); ?></h5>
                                                                    <span class="text-muted">Nhóm:
                                                                        <?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?></span>
                                                                </div>

                                                                <?php if ($sp['isWarning']): ?>
                                                                    <div class="alert alert-warning py-2 mb-4 d-flex align-items-center">
                                                                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                                                        <div>
                                                                            <strong>Phát hiện bất thường:</strong> Điểm số giữa các giám
                                                                            khảo có độ chênh lệch cao.
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <h6 class="fw-bold mb-3 border-bottom pb-2">Điểm của từng Giám khảo:
                                                                </h6>
                                                                <ul class="list-group list-group-flush mb-4">
                                                                    <?php $gk_idx = 1;
                                                                    foreach ($sp['chiTietDiem'] as $diem): ?>
                                                                        <li
                                                                            class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                                            <span>Giám khảo <?php echo $gk_idx++; ?></span>
                                                                            <span
                                                                                class="badge bg-primary rounded-pill fs-6"><?php echo number_format($diem, 1); ?></span>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>

                                                                <div
                                                                    class="d-flex justify-content-between align-items-center bg-light p-3 rounded border">
                                                                    <span class="fw-bold text-secondary">Điểm Trung Bình:</span>
                                                                    <span
                                                                        class="fs-4 fw-bold text-danger"><?php echo number_format($sp['diemTB'], 1); ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Đóng</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($active_tab == 3): ?>
            <div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0"><i class="bi bi-people-fill me-2"></i>Quản lý Tiểu ban báo cáo (Offline/Vòng
                        trong)</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateTB">
                        <i class="bi bi-plus-lg me-1"></i> Tạo tiểu ban mới
                    </button>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="tb-overview-card">
                            <div class="tb-overview-icon bg-primary"><i class="bi bi-collection"></i></div>
                            <div>
                                <h3 class="fw-bold m-0"><?php echo $total_tb; ?></h3><span class="text-muted small">Tiểu ban
                                    đã tạo</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="tb-overview-card">
                            <div class="tb-overview-icon bg-success"><i class="bi bi-file-earmark-text"></i></div>
                            <div>
                                <h3 class="fw-bold m-0"><?php echo count($assigned_sp_ids); ?></h3><span
                                    class="text-muted small">Bài thi đã xếp phòng</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="tb-overview-card">
                            <div class="tb-overview-icon bg-warning text-dark"><i class="bi bi-clock-history"></i></div>
                            <div>
                                <h3 class="fw-bold m-0 text-dark"><?php echo count($unassigned_sps); ?></h3><span
                                    class="text-muted small">Chưa xếp tiểu ban</span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (empty($tieuban_list)): ?>
                    <div class="alert alert-info text-center py-5">Sự kiện này chưa có tiểu ban nào. Hãy tạo tiểu ban để phân
                        công phòng thi!</div>
                <?php else: ?>
                    <?php foreach ($tieuban_list as $tb):
                        $tb_id = $tb['idTieuBan'];
                        $ngay_bc = $tb['ngayBaoCao'] ? date('d/m/Y', strtotime($tb['ngayBaoCao'])) : 'Chưa xếp ngày';
                        $phong = $tb['diaDiem'] ?: 'Chưa xếp phòng';
                        $sps_in_this = $sp_tb_map[$tb_id] ?? [];
                        $gvs_in_this = $gv_tb_map[$tb_id] ?? [];
                    ?>
                        <div class="tb-detail-card">
                            <div class="tb-detail-header align-items-center bg-light">
                                <div>
                                    <h5 class="fw-bold m-0 text-dark"><?php echo htmlspecialchars($tb['tenTieuBan']); ?></h5>
                                    <small class="text-muted"><?php echo $ngay_bc; ?> | Phòng:
                                        <?php echo htmlspecialchars($phong); ?></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary btn-sm rounded-circle" style="width: 32px; height: 32px;"
                                        data-bs-toggle="modal" data-bs-target="#modalEditTB_<?php echo $tb_id; ?>"><i
                                            class="bi bi-pencil-fill"></i></button>
                                    <form method="post" class="m-0" id="formXoaTBAssign-<?php echo $tb_id; ?>">
                                        <input type="hidden" name="action" value="delete_tb">
                                        <input type="hidden" name="active_tab" value="3">
                                        <input type="hidden" name="idTieuBan" value="<?php echo $tb_id; ?>">
                                        <button type="button" class="btn btn-danger btn-sm rounded-circle"
                                            style="width: 32px; height: 32px;" onclick="showConfirm({
                                        title      : 'Xác nhận xóa tiểu ban',
                                        message    : 'Thao tác này không thể hoàn tác.',
                                        type       : 'danger',
                                        confirmText: 'Xóa',
                                        onConfirm  : () => document.getElementById('formXoaTBAssign-<?php echo $tb_id; ?>').submit()
                                    })"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="tb-detail-body">
                                <h6 class="fw-bold text-dark mb-2">Hội đồng Ban giám khảo:</h6>
                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <?php if (empty($gvs_in_this)): ?>
                                        <span class="text-muted small fst-italic">Chưa có giám khảo. (Vào Tab 4 để sắp xếp)</span>
                                    <?php else: ?>
                                        <?php foreach ($gvs_in_this as $gv): ?>
                                            <span
                                                class="pill-badge bg-white shadow-sm border"><?php echo htmlspecialchars($gv['tenGV']); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-dark m-0">Bài báo cáo lọt vào vòng trong
                                        (<?php echo count($sps_in_this); ?>):</h6>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal"
                                        data-bs-target="#modalAssignSP_<?php echo $tb_id; ?>">
                                        <i class="bi bi-plus-lg"></i> Thêm bài
                                    </button>
                                </div>
                                <ul class="mb-0 ps-3">
                                    <?php if (empty($sps_in_this)): ?>
                                        <li class="text-muted small fst-italic list-unstyled ms-n3">Chưa có bài báo cáo nào.</li>
                                    <?php else: ?>
                                        <?php foreach ($sps_in_this as $sp): ?>
                                            <li class="mb-2 pe-3 d-flex justify-content-between align-items-start">
                                                <span><strong
                                                        class="text-secondary"><?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']); ?>:</strong>
                                                    <?php echo htmlspecialchars($sp['tensanpham']); ?></span>
                                                <form method="post" class="m-0 flex-shrink-0 ms-3">
                                                    <input type="hidden" name="action" value="remove_sp_tb"><input type="hidden"
                                                        name="active_tab" value="3">
                                                    <input type="hidden" name="idTieuBan" value="<?php echo $tb_id; ?>"><input type="hidden"
                                                        name="idSanPham" value="<?php echo $sp['idSanPham']; ?>">
                                                    <button type="submit" class="btn btn-link text-danger p-0"
                                                        title="Rút bài khỏi Tiểu ban"><i class="bi bi-x-circle-fill"></i></button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="modal fade" id="modalEditTB_<?php echo $tb_id; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="post">
                                        <input type="hidden" name="action" value="edit_tb"><input type="hidden" name="active_tab"
                                            value="3"><input type="hidden" name="idTieuBan" value="<?php echo $tb_id; ?>">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Cập nhật Tiểu ban</h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3"><label class="form-label fw-bold">Tên tiểu ban <span
                                                        class="text-danger">*</span></label><input type="text" name="tenTieuBan"
                                                    class="form-control" value="<?php echo htmlspecialchars($tb['tenTieuBan']); ?>"
                                                    required></div>
                                            <div class="mb-3"><label class="form-label fw-bold">Ngày báo cáo</label><input
                                                    type="date" name="ngayBaoCao" class="form-control"
                                                    value="<?php echo $tb['ngayBaoCao']; ?>"></div>
                                            <div class="mb-3"><label class="form-label fw-bold">Phòng / Địa điểm</label><input
                                                    type="text" name="diaDiem" class="form-control"
                                                    value="<?php echo htmlspecialchars($tb['diaDiem']); ?>" placeholder="VD: 209C">
                                            </div>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Hủy</button><button type="submit"
                                                class="btn btn-primary">Lưu thay đổi</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="modalAssignSP_<?php echo $tb_id; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="post">
                                        <input type="hidden" name="action" value="add_sp_tb"><input type="hidden" name="active_tab"
                                            value="3"><input type="hidden" name="idTieuBan" value="<?php echo $tb_id; ?>">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Giao bài lọt vào vòng trong cho:
                                                <br><small><?php echo htmlspecialchars($tb['tenTieuBan']); ?></small>
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label fw-bold">Chọn bài thi (Những bài chưa có tiểu ban)</label>
                                            <select name="idSanPham" class="form-select" required>
                                                <option value="">-- Chọn bài thi --</option>
                                                <?php foreach ($unassigned_sps as $sp): ?>
                                                    <option value="<?php echo $sp['idSanPham']; ?>">
                                                        <?php echo htmlspecialchars($sp['tennhom'] ?: $sp['manhom']) . ' - ' . htmlspecialchars($sp['tensanpham']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Đóng</button><button type="submit"
                                                class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Thêm bài vào Hội
                                                đồng</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab == 4): ?>
            <div>
                <h4 class="fw-bold mb-4"><i class="bi bi-person-lines-fill me-2"></i>Phân công Ban Giám Khảo (Hội đồng thi)
                </h4>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th width="20%" class="py-3 ps-4 text-white">Tiểu ban</th>
                                        <th width="15%" class="text-white">Ngày</th>
                                        <th width="15%" class="text-white">Phòng</th>
                                        <th width="35%" class="text-white">Thành viên Hội đồng</th>
                                        <th width="15%" class="text-center text-white">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tieuban_list)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">Chưa có tiểu ban nào để phân
                                                công. Vui lòng tạo ở Tab 3.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tieuban_list as $tb):
                                            $tb_id = $tb['idTieuBan'];
                                            $ngay_bc = $tb['ngayBaoCao'] ? date('d/m/Y', strtotime($tb['ngayBaoCao'])) : '...';
                                            $phong = $tb['diaDiem'] ?: '...';
                                            $gvs_in_this = $gv_tb_map[$tb_id] ?? [];

                                            $assigned_gv_ids = array_column($gvs_in_this, 'idGV');
                                            $available_gvs = array_filter($ds_giangvien, function ($gv) use ($assigned_gv_ids) {
                                                return !in_array($gv['idGV'], $assigned_gv_ids);
                                            });
                                        ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-dark">
                                                    <?php echo htmlspecialchars($tb['tenTieuBan']); ?></td>
                                                <td><?php echo $ngay_bc; ?></td>
                                                <td><?php echo htmlspecialchars($phong); ?></td>
                                                <td>
                                                    <?php if (empty($gvs_in_this)): ?>
                                                        <span class="text-muted small">Chưa có</span>
                                                    <?php else: ?>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <?php foreach ($gvs_in_this as $gv): ?>
                                                                <span class="pill-badge bg-white shadow-sm border text-secondary">
                                                                    <?php echo htmlspecialchars($gv['tenGV']); ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal"
                                                        data-bs-target="#modalAssignGV_<?php echo $tb_id; ?>">
                                                        <i class="bi bi-person-plus-fill me-1"></i> Phân công
                                                    </button>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="modalAssignGV_<?php echo $tb_id; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title">Sắp xếp Hội đồng:
                                                                <br><small><?php echo htmlspecialchars($tb['tenTieuBan']); ?></small>
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body bg-light">
                                                            <form method="post"
                                                                class="d-flex gap-2 mb-4 p-3 bg-white border rounded">
                                                                <input type="hidden" name="action" value="add_gv_tb"><input
                                                                    type="hidden" name="active_tab" value="4"><input type="hidden"
                                                                    name="idTieuBan" value="<?php echo $tb_id; ?>">
                                                                <select name="idGV" class="form-select form-select-sm" required>
                                                                    <option value="">-- Mời giảng viên vào Hội đồng --</option>
                                                                    <?php foreach ($available_gvs as $gv): ?><option
                                                                            value="<?php echo $gv['idGV']; ?>">
                                                                            <?php echo htmlspecialchars($gv['tenGV']); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <button type="submit" class="btn btn-primary btn-sm text-nowrap"><i
                                                                        class="bi bi-plus"></i> Thêm</button>
                                                            </form>

                                                            <h6 class="fw-bold mb-2 text-secondary">Danh sách Hội đồng hiện tại:
                                                            </h6>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <?php if (empty($gvs_in_this)): ?>
                                                                    <span class="text-muted small">Chưa có ai.</span>
                                                                <?php else: ?>
                                                                    <?php foreach ($gvs_in_this as $gv): ?>
                                                                        <span
                                                                            class="badge bg-white text-dark border d-flex align-items-center p-2 shadow-sm">
                                                                            <?php echo htmlspecialchars($gv['tenGV']); ?>
                                                                            <form method="post" class="m-0 ms-2">
                                                                                <input type="hidden" name="action"
                                                                                    value="remove_gv_tb"><input type="hidden"
                                                                                    name="active_tab" value="4">
                                                                                <input type="hidden" name="idTieuBan"
                                                                                    value="<?php echo $tb_id; ?>"><input type="hidden"
                                                                                    name="idGV" value="<?php echo $gv['idGV']; ?>">
                                                                                <button type="submit" class="btn-close"
                                                                                    style="font-size: 0.6rem;"
                                                                                    title="Loại khỏi hội đồng"></button>
                                                                            </form>
                                                                        </span>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0"><button type="button"
                                                                class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<div class="modal fade" id="modalCreateTB" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create_tb"><input type="hidden" name="active_tab" value="3">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tạo Tiểu Ban Mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên tiểu ban <span class="text-danger">*</span></label>
                        <input type="text" name="tenTieuBan" class="form-control"
                            placeholder="Ví dụ: Tiểu ban Công nghệ AI" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Thuộc Vòng thi <span class="text-danger">*</span></label>
                        <select name="idVongThi" class="form-select" required>
                            <option value="">-- Chọn Vòng thi --</option>
                            <?php foreach ($vong_array as $v): ?><option value="<?php echo $v['idVongThi']; ?>">
                                    <?php echo htmlspecialchars($v['tenVongThi']); ?></option><?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Lưu ý: Tiểu ban thường dùng cho Vòng chung kết / Bảo vệ
                            báo cáo offline.</small>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Ngày báo cáo</label>
                            <input type="date" name="ngayBaoCao" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Phòng / Địa điểm</label>
                            <input type="text" name="diaDiem" class="form-control" placeholder="VD: Hội trường K">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Tạo Tiểu
                        Ban</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout('footer'); ?>