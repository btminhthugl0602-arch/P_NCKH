function format_datetime_local($datetime) {
    if (empty($datetime)) return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}
?>

<main class="main container py-4">
    <div class="d-flex justify-content-end mb-3">
        <a class="btn btn-primary"
           href="<?php echo _HOST_URL; ?>/?module=event&action=view&id=<?php echo (int)$id_su_kien; ?>">
            Quay về sự kiện
        </a>
    </div>
<div class="mx-auto" style="max-width: 60%;">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Cấu hình cơ bản</h2>
        <p class="text-muted mb-0">Thiết lập thông tin sự kiện và vòng thi.</p>
    </div>

    <!-- Thông tin sự kiện -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">1. Thông tin sự kiện</h5>
            <small class="text-muted">Cập nhật nội dung và thời gian sự kiện</small>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="update_event">

                <div class="col-md-6">
                    <label class="form-label">Tên sự kiện</label>
                    <input type="text" name="tenSK" class="form-control" value="<?php echo htmlspecialchars($event['tenSK'] ?? ''); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Cấp tổ chức</label>
                    <select name="idCap" class="form-select" required>
                        <option value="">-- Chọn cấp tổ chức --</option>
                        <?php foreach ($caps as $cap): ?>
                            <option value="<?php echo (int)$cap['idCap']; ?>" <?php echo (!empty($event['idCap']) && $event['idCap'] == $cap['idCap']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cap['tenCap']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="moTa" class="form-control" rows="3"><?php echo htmlspecialchars($event['moTa'] ?? ''); ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày mở đăng ký</label>
                    <input type="datetime-local" name="ngayMoDangKy" class="form-control" value="<?php echo format_datetime_local($event['ngayMoDangKy'] ?? ''); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày đóng đăng ký</label>
                    <input type="datetime-local" name="ngayDongDangKy" class="form-control" value="<?php echo format_datetime_local($event['ngayDongDangKy'] ?? ''); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày bắt đầu</label>
                    <input type="datetime-local" name="ngayBatDau" class="form-control" value="<?php echo format_datetime_local($event['ngayBatDau'] ?? ''); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày kết thúc</label>
                    <input type="datetime-local" name="ngayKetThuc" class="form-control" value="<?php echo format_datetime_local($event['ngayKetThuc'] ?? ''); ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Trạng thái</label>
                    <select name="isActive" class="form-select">
                        <option value="1" <?php echo (!empty($event['isActive']) && $event['isActive'] == 1) ? 'selected' : ''; ?>>Kích hoạt</option>
                        <option value="0" <?php echo (isset($event['isActive']) && (int)$event['isActive'] === 0) ? 'selected' : ''; ?>>Ẩn</option>
                    </select>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Lưu cấu hình</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tạo vòng thi -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">2. Tạo vòng thi</h5>
            <small class="text-muted">Thiết lập thứ tự và thời gian cho từng vòng</small>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="create_round">

                <div class="col-md-6">
                    <label class="form-label">Tên vòng thi</label>
                    <input type="text" name="tenVongThi" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Thứ tự</label>
                    <input type="number" name="thuTu" class="form-control" value="1">
                </div>

                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="moTaVong" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày bắt đầu</label>
                    <input type="datetime-local" name="ngayBatDauVong" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày kết thúc</label>
                    <input type="datetime-local" name="ngayKetThucVong" class="form-control">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Tạo vòng thi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách vòng thi -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">3. Danh sách vòng thi</h5>
            <small class="text-muted">Theo thứ tự đã thiết lập</small>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Tên vòng</th>
                        <th style="width: 80px;">Thứ tự</th>
                        <th style="width: 180px;">Bắt đầu</th>
                        <th style="width: 180px;">Kết thúc</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vongthi_list)): ?>
                        <?php foreach ($vongthi_list as $row): ?>
                            <tr>
                                <td><?php echo $row['idVongThi']; ?></td>
                                <td><?php echo htmlspecialchars($row['tenVongThi']); ?></td>
                                <td><?php echo $row['thuTu']; ?></td>
                                <td><?php echo $row['ngayBatDau']; ?></td>
                                <td><?php echo $row['ngayKetThuc']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Chưa có vòng thi nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>

