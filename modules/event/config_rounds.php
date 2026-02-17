<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Tạo vòng thi
if (isPost()) {
    $data = filter();
    $ten = $data['tenVongThi'] ?? '';
    $moTa = $data['moTa'] ?? '';
    $thuTu = (int)($data['thuTu'] ?? 1);
    $ngayBatDau = $data['ngayBatDau'] ?? null;
    $ngayKetThuc = $data['ngayKetThuc'] ?? null;

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

    header("Location: ?module=event&action=config_rounds&id=$id_su_kien");
    exit;
}

// Danh sách vòng thi
$sql_vongthi = "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC";
$vongthi_list = mysqli_query($conn, $sql_vongthi);

layout('header');
layout('navbar');
?>

<main class="main container py-4">
    <h2>Cấu hình Vòng thi</h2>

    <h4>Tạo vòng thi</h4>
    <form method="post">
        <input type="text" name="tenVongThi" class="form-control mb-2" placeholder="Tên vòng thi" required>
        <textarea name="moTa" class="form-control mb-2" placeholder="Mô tả"></textarea>
        <input type="number" name="thuTu" class="form-control mb-2" placeholder="Thứ tự" value="1">
        <input type="datetime-local" name="ngayBatDau" class="form-control mb-2">
        <input type="datetime-local" name="ngayKetThuc" class="form-control mb-2">
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