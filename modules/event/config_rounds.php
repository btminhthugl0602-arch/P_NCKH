<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$event_message = '';
$event_error = '';

// Lấy chi tiết sự kiện
$event = $id_su_kien > 0 ? btc_lay_chi_tiet_su_kien($conn, $id_su_kien) : null;

// Lấy danh sách cấp tổ chức
$sql_cap = "SELECT * FROM cap_tochuc ORDER BY tenCap";
$result_cap = mysqli_query($conn, $sql_cap);
$caps = [];
if ($result_cap) {
    while ($row = mysqli_fetch_assoc($result_cap)) {
        $caps[] = $row;
    }
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

            // Load lại dữ liệu sau update
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
            $sql = "INSERT INTO vongthi (idSK, tenVongThi, moTa, thuTu, ngayBatDau, ngayKetThuc)
                    VALUES ($id_su_kien,
                            '" . chuan_hoa_chuoi_sql($conn, $ten) . "',
                            '" . chuan_hoa_chuoi_sql($conn, $moTa) . "',
                            $thuTu,
                            " . ($ngayBatDau ? "'" . chuan_hoa_chuoi_sql($conn, $ngayBatDau) . "'" : "NULL") . ",
                            " . ($ngayKetThuc ? "'" . chuan_hoa_chuoi_sql($conn, $ngayKetThuc) . "'" : "NULL") . ")";
            mysqli_query($conn, $sql);
        }

        header("Location: ?module=event&action=config_basic&id=$id_su_kien");
        exit;
    }
}

// Danh sách vòng thi
$sql_vongthi = "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC";
$vongthi_list = mysqli_query($conn, $sql_vongthi);

layout('header');
layout('navbar');

function format_datetime_local($datetime) {
    if (empty($datetime)) return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}
?>

<main class="main container py-4">
    <h2>Cấu hình cơ bản (bao gồm vòng thi)</h2>

    <h4>Chỉnh sửa sự kiện</h4>
    <form method="post">
        <input type="hidden" name="action" value="update_event">
        <input type="text" name="tenSK" class="form-control mb-2" placeholder="Tên sự kiện" value="<?php echo htmlspecialchars($event['tenSK'] ?? ''); ?>" required>
        <textarea name="moTa" class="form-control mb-2" placeholder="Mô tả"><?php echo htmlspecialchars($event['moTa'] ?? ''); ?></textarea>

        <select name="idCap" class="form-select mb-2" required>
            <option value="">-- Chọn cấp tổ chức --</option>
            <?php foreach ($caps as $cap): ?>
                <option value="<?php echo (int)$cap['idCap']; ?>" <?php echo (!empty($event['idCap']) && $event['idCap'] == $cap['idCap']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cap['tenCap']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Ngày mở đăng ký</label>
        <input type="datetime-local" name="ngayMoDangKy" class="form-control mb-2" value="<?php echo format_datetime_local($event['ngayMoDangKy'] ?? ''); ?>" required>

        <label>Ngày đóng đăng ký</label>
        <input type="datetime-local" name="ngayDongDangKy" class="form-control mb-2" value="<?php echo format_datetime_local($event['ngayDongDangKy'] ?? ''); ?>" required>

        <label>Ngày bắt đầu</label>
        <input type="datetime-local" name="ngayBatDau" class="form-control mb-2" value="<?php echo format_datetime_local($event['ngayBatDau'] ?? ''); ?>" required>

        <label>Ngày kết thúc</label>
        <input type="datetime-local" name="ngayKetThuc" class="form-control mb-2" value="<?php echo format_datetime_local($event['ngayKetThuc'] ?? ''); ?>" required>

        <select name="isActive" class="form-select mb-2">
            <option value="1" <?php echo (!empty($event['isActive']) && $event['isActive'] == 1) ? 'selected' : ''; ?>>Kích hoạt</option>
            <option value="0" <?php echo (isset($event['isActive']) && (int)$event['isActive'] === 0) ? 'selected' : ''; ?>>Ẩn</option>
        </select>

        <?php if (!empty($event_error)): ?>
            <div class="alert alert-danger"><?php echo $event_error; ?></div>
        <?php endif; ?>
        <?php if (!empty($event_message)): ?>
            <div class="alert alert-success"><?php echo $event_message; ?></div>
        <?php endif; ?>

        <button class="btn btn-primary">Lưu cấu hình</button>
    </form>

    <hr>

    <h4>Tạo vòng thi</h4>
    <form method="post">
        <input type="hidden" name="action" value="create_round">
        <input type="text" name="tenVongThi" class="form-control mb-2" placeholder="Tên vòng thi" required>
        <textarea name="moTaVong" class="form-control mb-2" placeholder="Mô tả"></textarea>
        <input type="number" name="thuTu" class="form-control mb-2" placeholder="Thứ tự" value="1">
        <input type="datetime-local" name="ngayBatDauVong" class="form-control mb-2">
        <input type="datetime-local" name="ngayKetThucVong" class="form-control mb-2">
        <button class="btn btn-primary">Tạo vòng thi</button>
    </form>

    <hr>

    <h4>Danh sách vòng thi</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên vòng</th>
                <th>Thứ tự</th>
                <th>Ngày bắt đầu</th>
                <th>Ngày kết thúc</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($vongthi_list && mysqli_num_rows($vongthi_list) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($vongthi_list)): ?>
                    <tr>
                        <td><?php echo $row['idVongThi']; ?></td>
                        <td><?php echo htmlspecialchars($row['tenVongThi']); ?></td>
                        <td><?php echo $row['thuTu']; ?></td>
                        <td><?php echo $row['ngayBatDau']; ?></td>
                        <td><?php echo $row['ngayKetThuc']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Chưa có vòng thi nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<?php layout('footer'); ?>