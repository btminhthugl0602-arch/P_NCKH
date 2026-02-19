<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';
require_once _PATH_URL . '/modules/functions/quan_ly_vong_thi.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$event_message = '';
$event_error = '';

// Lấy chi tiết sự kiện
$event = $id_su_kien > 0 ? btc_lay_chi_tiet_su_kien($conn, $id_su_kien) : null;

// Lấy danh sách cấp tổ chức
$cap_conditions = [
    'ORDER BY' => ['tenCap', 'ASC', '', '']
];
$caps = _select_info($conn, 'cap_tochuc', [], $cap_conditions);
if (!$caps) {
    $caps = [];
}

// ======================
// Xử lý form
// ======================
if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    // Cập nhật thông tin sự kiện
    if ($action === 'update_event') {
        $tenSK = trim($data['tenSK'] ?? '');
        $moTa = trim($data['moTa'] ?? '');
        $idCap = (int)($data['idCap'] ?? 0);
        $isActive = isset($data['isActive']) ? (int)$data['isActive'] : 1;

        $ngayMoDangKy = !empty($data['ngayMoDangKy']) ? date('Y-m-d H:i:s', strtotime($data['ngayMoDangKy'])) : '';
        $ngayDongDangKy = !empty($data['ngayDongDangKy']) ? date('Y-m-d H:i:s', strtotime($data['ngayDongDangKy'])) : '';
        $ngayBatDau = !empty($data['ngayBatDau']) ? date('Y-m-d H:i:s', strtotime($data['ngayBatDau'])) : '';
        $ngayKetThuc = !empty($data['ngayKetThuc']) ? date('Y-m-d H:i:s', strtotime($data['ngayKetThuc'])) : '';

        $errors = [];

        if ($tenSK === '') {
            $errors[] = 'Tên sự kiện không được để trống.';
        }
        if ($idCap <= 0) {
            $errors[] = 'Vui lòng chọn cấp tổ chức.';
        }
        if (empty($ngayMoDangKy) || empty($ngayDongDangKy) || empty($ngayBatDau) || empty($ngayKetThuc)) {
            $errors[] = 'Vui lòng nhập đầy đủ thời gian.';
        } else {
            $now = strtotime(date('Y-m-d'));
            $mo = strtotime($ngayMoDangKy);
            $dong = strtotime($ngayDongDangKy);
            $bat = strtotime($ngayBatDau);
            $ket = strtotime($ngayKetThuc);

            if ($mo < $now) {
                $errors[] = 'Ngày mở đăng ký phải từ hôm nay trở đi.';
            }
            if ($mo >= $dong) {
                $errors[] = 'Ngày mở đăng ký phải nhỏ hơn ngày đóng đăng ký.';
            }
            if ($bat < $mo) {
                $errors[] = 'Ngày bắt đầu phải lớn hơn hoặc bằng ngày mở đăng ký.';
            }
            if ($ket < $bat) {
                $errors[] = 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.';
            }
        }

        if (!empty($errors)) {
            $event_error = implode('<br>', $errors);
        } else {
            $result = btc_cap_nhat_su_kien(
                $conn,
                isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0,
                $id_su_kien,
                $tenSK,
                $moTa,
                $idCap,
                $ngayMoDangKy,
                $ngayDongDangKy,
                $ngayBatDau,
                $ngayKetThuc,
                $isActive
            );

            if ($result['status']) {
                $event_message = $result['message'] ?? 'Cập nhật thành công';
            } else {
                $event_error = $result['message'] ?? 'Cập nhật thất bại';
            }

            $event = btc_lay_chi_tiet_su_kien($conn, $id_su_kien);
        }
    }

    // Tạo vòng thi
    if ($action === 'create_round') {
        $ten = $data['tenVongThi'] ?? '';
        $moTa = $data['moTaVong'] ?? '';
        $thuTu = (int)($data['thuTu'] ?? 1);
        $ngayBatDau = $data['ngayBatDauVong'] ?? null;
        $ngayKetThuc = $data['ngayKetThucVong'] ?? null;

        if (!empty($ten) && $id_su_kien > 0) {
            $result = tao_vong_thi(
                $conn,
                isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0,
                $id_su_kien,
                $ten,
                $moTa,
                $thuTu,
                $ngayBatDau,
                $ngayKetThuc
            );

            if ($result['status']) {
                $event_message = $result['message'] ?? 'Tạo vòng thi thành công';
            } else {
                $event_error = $result['message'] ?? 'Không thể tạo vòng thi';
            }
        }

        header("Location: ?module=event&action=config_rounds&id=$id_su_kien");
        exit;
    }
}

// Danh sách vòng thi
$vongthi_list = lay_ds_vong_thi($conn, $id_su_kien);
if (!$vongthi_list) {
    $vongthi_list = [];
}

layout('header');
layout('navbar');

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

    <?php if (!empty($event_error)): ?>
        <div class="alert alert-danger"><?php echo $event_error; ?></div>
    <?php endif; ?>
    <?php if (!empty($event_message)): ?>
        <div class="alert alert-success"><?php echo $event_message; ?></div>
    <?php endif; ?>

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

<?php layout('footer'); ?>