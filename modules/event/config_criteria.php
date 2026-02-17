<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ======================
// Xử lý form
// ======================
if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    if ($action === 'create_set') {
        $ten = $data['tenBoTieuChi'] ?? '';
        $moTa = $data['moTa'] ?? '';
        if (!empty($ten)) {
            mysqli_query($conn, "INSERT INTO botieuchi (tenBoTieuChi, moTa)
                                 VALUES ('" . chuan_hoa_chuoi_sql($conn, $ten) . "',
                                         '" . chuan_hoa_chuoi_sql($conn, $moTa) . "')");
        }
    }

    if ($action === 'create_criteria') {
        $noiDung = $data['noiDungTieuChi'] ?? '';
        $diemToiDa = $data['diemToiDa'] ?? '10';
        if (!empty($noiDung)) {
            mysqli_query($conn, "INSERT INTO tieuchi (noiDungTieuChi, diemToiDa)
                                 VALUES ('" . chuan_hoa_chuoi_sql($conn, $noiDung) . "',
                                         '" . chuan_hoa_chuoi_sql($conn, $diemToiDa) . "')");
        }
    }

    if ($action === 'attach_criteria') {
        $idBo = (int)($data['idBoTieuChi'] ?? 0);
        $idTieuChi = (int)($data['idTieuChi'] ?? 0);
        $tyTrong = $data['tyTrong'] ?? '1.00';

        if ($idBo && $idTieuChi) {
            mysqli_query($conn, "REPLACE INTO botieuchi_tieuchi (idBoTieuChi, idTieuChi, tyTrong)
                                 VALUES ($idBo, $idTieuChi, '" . chuan_hoa_chuoi_sql($conn, $tyTrong) . "')");
        }
    }

    if ($action === 'assign_round') {
        $idVong = (int)($data['idVongThi'] ?? 0);
        $idBo = (int)($data['idBoTieuChiAssign'] ?? 0);

        if ($idVong && $idBo) {
            mysqli_query($conn, "REPLACE INTO cauhinh_tieuchi_sk (idSK, idVongThi, idBoTieuChi)
                                 VALUES ($id_su_kien, $idVong, $idBo)");
        }
    }

    header("Location: ?module=event&action=config_criteria&id=$id_su_kien");
    exit;
}

// ======================
// Dữ liệu
// ======================
$bo_list = mysqli_query($conn, "SELECT * FROM botieuchi ORDER BY idBoTieuChi DESC");
$tieuchi_list = mysqli_query($conn, "SELECT * FROM tieuchi ORDER BY idTieuChi DESC");
$vong_list = mysqli_query($conn, "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC");

layout('header');
layout('navbar');
?>

<main class="main container py-4">
    <h2>Cấu hình Bộ tiêu chí & Chấm điểm</h2>

    <h4>Tạo bộ tiêu chí</h4>
    <form method="post">
        <input type="hidden" name="action" value="create_set">
        <input type="text" name="tenBoTieuChi" class="form-control mb-2" placeholder="Tên bộ tiêu chí" required>
        <textarea name="moTa" class="form-control mb-2" placeholder="Mô tả"></textarea>
        <button class="btn btn-primary">Tạo bộ tiêu chí</button>
    </form>

    <hr>

    <h4>Tạo tiêu chí</h4>
    <form method="post">
        <input type="hidden" name="action" value="create_criteria">
        <input type="text" name="noiDungTieuChi" class="form-control mb-2" placeholder="Nội dung tiêu chí" required>
        <input type="number" step="0.01" name="diemToiDa" class="form-control mb-2" value="10">
        <button class="btn btn-success">Tạo tiêu chí</button>
    </form>

    <hr>

    <h4>Gán tiêu chí vào bộ</h4>
    <form method="post">
        <input type="hidden" name="action" value="attach_criteria">
        <select name="idBoTieuChi" class="form-select mb-2">
            <?php while ($row = mysqli_fetch_assoc($bo_list)) : ?>
                <option value="<?php echo $row['idBoTieuChi']; ?>">
                    <?php echo $row['tenBoTieuChi']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="idTieuChi" class="form-select mb-2">
            <?php mysqli_data_seek($tieuchi_list, 0); while ($row = mysqli_fetch_assoc($tieuchi_list)) : ?>
                <option value="<?php echo $row['idTieuChi']; ?>">
                    <?php echo $row['noiDungTieuChi']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="number" step="0.01" name="tyTrong" class="form-control mb-2" value="1.00">
        <button class="btn btn-warning">Gán tiêu chí</button>
    </form>

    <hr>

    <h4>Gán bộ tiêu chí cho vòng thi</h4>
    <form method="post">
        <input type="hidden" name="action" value="assign_round">
        <select name="idVongThi" class="form-select mb-2">
            <?php while ($row = mysqli_fetch_assoc($vong_list)) : ?>
                <option value="<?php echo $row['idVongThi']; ?>">
                    <?php echo $row['tenVongThi']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="idBoTieuChiAssign" class="form-select mb-2">
            <?php mysqli_data_seek($bo_list, 0); while ($row = mysqli_fetch_assoc($bo_list)) : ?>
                <option value="<?php echo $row['idBoTieuChi']; ?>">
                    <?php echo $row['tenBoTieuChi']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button class="btn btn-dark">Gán bộ tiêu chí</button>
    </form>
</main>

<?php layout('footer'); ?>