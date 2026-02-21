<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');
require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Giả định lấy ID Giảng viên từ Session (Thay bằng session thực tế của bạn)
$id_gv = $_SESSION['user_id'] ?? 1; // Để số 1 hoặc 2 để test theo dữ liệu giả lập hôm qua

if ($id_su_kien == 0) {
    die("Không tìm thấy sự kiện.");
}

// 1. Lấy thông tin sự kiện
$sql_sk = "SELECT tenSK FROM sukien WHERE idSK = $id_su_kien";
$sk_info = mysqli_fetch_assoc(mysqli_query($conn, $sql_sk));

// 2. Gom toàn bộ Bài thi được phân công cho Giảng viên này (Cả Độc lập và Tiểu ban)
$sql_tasks = "
    SELECT 
        a.idSanPham, a.idVongThi, a.loaiCham, 
        sp.tensanpham, n.manhom, ttn.tennhom, v.tenVongThi
    FROM (
        -- Lấy từ phân công độc lập (Tab 1)
        SELECT idSanPham, idVongThi, 'Đánh giá độc lập (Sơ loại)' as loaiCham 
        FROM phancong_doclap 
        WHERE idGV = $id_gv
        
        UNION
        
        -- Lấy từ phân công Tiểu ban (Tab 4)
        SELECT tbs.idSanPham, tb.idVongThi, CONCAT('Hội đồng: ', tb.tenTieuBan) as loaiCham 
        FROM tieuban_giangvien tbg
        JOIN tieuban tb ON tbg.idTieuBan = tb.idTieuBan
        JOIN tieuban_sanpham tbs ON tb.idTieuBan = tbs.idTieuBan
        WHERE tbg.idGV = $id_gv AND tb.idSK = $id_su_kien
    ) a
    JOIN sanpham sp ON a.idSanPham = sp.idSanPham
    LEFT JOIN nhom n ON sp.idNhom = n.idnhom
    LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
    JOIN vongthi v ON a.idVongThi = v.idVongThi
    WHERE sp.idSK = $id_su_kien
    ORDER BY a.idVongThi ASC, a.idSanPham DESC
";
$res_tasks = mysqli_query($conn, $sql_tasks);
$ds_tasks = $res_tasks ? mysqli_fetch_all($res_tasks, MYSQLI_ASSOC) : [];

// 3. Lấy trạng thái chấm điểm thực tế của Giảng viên này để hiển thị "Đã chấm" hay "Chưa chấm"
$sql_status = "
    SELECT ctc.idSanPham, pcc.idVongThi, pcc.trangThaiXacNhan 
    FROM chamtieuchi ctc
    JOIN phancongcham pcc ON ctc.idPhanCongCham = pcc.idPhanCongCham
    WHERE pcc.idGV = $id_gv AND pcc.idSK = $id_su_kien
    GROUP BY ctc.idSanPham, pcc.idVongThi, pcc.trangThaiXacNhan
";
$res_status = mysqli_query($conn, $sql_status);
$status_map = [];
if ($res_status) {
    while ($row = mysqli_fetch_assoc($res_status)) {
        // Map theo key: idSanPham_idVongThi
        $status_map[$row['idSanPham'] . '_' . $row['idVongThi']] = $row['trangThaiXacNhan'];
    }
}

layout('header'); layout('navbar');
?>

<main class="main container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-journal-text text-success me-2"></i>Nhiệm vụ Chấm điểm</h2>
            <p class="text-muted mb-0">Sự kiện: <strong><?=htmlspecialchars($sk_info['tenSK'] ?? 'Không xác định')?></strong></p>
        </div>
        <a href="?module=event&action=view&id=<?php echo $id_su_kien; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Về trang sự kiện</a>
    </div>

    <div class="card shadow-sm border-0 border-top border-4 border-success">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%" class="text-center ps-3">STT</th>
                            <th width="30%">Tên Đề tài / Sản phẩm</th>
                            <th width="20%">Vòng thi</th>
                            <th width="20%">Hình thức phân công</th>
                            <th width="12%" class="text-center">Trạng thái</th>
                            <th width="13%" class="text-center pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ds_tasks)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Bạn chưa được phân công chấm bài thi nào trong sự kiện này.
                                </td>
                            </tr>
                        <?php else: $stt = 1; foreach ($ds_tasks as $task): 
                            $key = $task['idSanPham'] . '_' . $task['idVongThi'];
                            $trang_thai = $status_map[$key] ?? 'Chưa chấm';
                            
                            $badge_class = 'bg-secondary';
                            if ($trang_thai == 'Đã xác nhận') $badge_class = 'bg-success';
                            if ($trang_thai == 'Đang chấm') $badge_class = 'bg-warning text-dark';
                        ?>
                            <tr>
                                <td class="text-center fw-bold text-muted ps-3"><?=$stt++?></td>
                                <td>
                                    <div class="fw-bold text-primary mb-1"><?=htmlspecialchars($task['tensanpham'])?></div>
                                    <small class="text-muted"><i class="bi bi-people-fill me-1"></i> <?=htmlspecialchars($task['tennhom'] ?: $task['manhom'])?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border px-2 py-1"><?=htmlspecialchars($task['tenVongThi'])?></span></td>
                                <td class="text-secondary small fw-bold">
                                    <i class="bi <?= strpos($task['loaiCham'], 'Độc lập') !== false ? 'bi-person' : 'bi-diagram-3' ?> me-1"></i> 
                                    <?=htmlspecialchars($task['loaiCham'])?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?=$badge_class?> rounded-pill px-3 py-2"><?=$trang_thai?></span>
                                </td>
                                <td class="text-center pe-3">
                                    <a href="?module=event&action=grade_product&idSP=<?=$task['idSanPham']?>&idVong=<?=$task['idVongThi']?>&idSK=<?=$id_su_kien?>" 
                                       class="btn btn-sm <?= $trang_thai == 'Đã xác nhận' ? 'btn-outline-success' : 'btn-success' ?> rounded-pill px-3 shadow-sm fw-bold">
                                        <?= $trang_thai == 'Đã xác nhận' ? '<i class="bi bi-eye"></i> Xem điểm' : '<i class="bi bi-pencil-square"></i> Chấm bài' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php layout('footer'); ?>