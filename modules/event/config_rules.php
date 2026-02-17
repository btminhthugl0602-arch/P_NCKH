<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$loai_quy_che = isset($_GET['loai']) ? $_GET['loai'] : 'THAMGIA';

// Lấy danh sách thuộc tính theo loại
$sql_thuoctinh = "SELECT * FROM thuoctinh_kiemtra WHERE loaiApDung = '" . mysqli_real_escape_string($conn, $loai_quy_che) . "'";
$thuoctinh_list = mysqli_query($conn, $sql_thuoctinh);

// Toán tử so sánh
$sql_compare = "SELECT * FROM toantu WHERE loaiToanTu = 'compare'";
$toantu_compare = mysqli_query($conn, $sql_compare);

// Toán tử logic
$sql_logic = "SELECT * FROM toantu WHERE loaiToanTu = 'logic'";
$toantu_logic = mysqli_query($conn, $sql_logic);

// Danh sách quy chế
$sql_quyche = "SELECT * FROM quyche WHERE loaiQuyChe = '" . mysqli_real_escape_string($conn, $loai_quy_che) . "'";
$quyche_list = mysqli_query($conn, $sql_quyche);

// Danh sách điều kiện để tạo tổ hợp
$sql_dieukien = "SELECT * FROM dieukien";
$dieukien_list = mysqli_query($conn, $sql_dieukien);

// ======================
// Xử lý form
// ======================
if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    // Tạo quy chế
    if ($action === 'create_rule') {
        $ten = $data['tenQuyChe'] ?? '';
        $mota = $data['moTa'] ?? '';

        if (!empty($ten)) {
            $sql = "INSERT INTO quyche (tenQuyChe, moTa, loaiQuyChe) 
                    VALUES ('" . chuan_hoa_chuoi_sql($conn, $ten) . "', 
                            '" . chuan_hoa_chuoi_sql($conn, $mota) . "', 
                            '" . chuan_hoa_chuoi_sql($conn, $loai_quy_che) . "')";
            mysqli_query($conn, $sql);
        }
    }

    // Tạo điều kiện đơn
    if ($action === 'create_condition') {
        $ten = $data['tenDieuKien'] ?? '';
        $mota = $data['moTaDieuKien'] ?? '';
        $idThuocTinh = (int)($data['idThuocTinh'] ?? 0);
        $idToanTu = (int)($data['idToanTu'] ?? 0);
        $giaTri = $data['giaTriSoSanh'] ?? '';

        if (!empty($ten) && $idThuocTinh > 0 && $idToanTu > 0) {
            mysqli_query($conn, "INSERT INTO dieukien (loaiDieuKien, tenDieuKien, moTa)
                                 VALUES ('DON', '" . chuan_hoa_chuoi_sql($conn, $ten) . "', '" . chuan_hoa_chuoi_sql($conn, $mota) . "')");

            $idDieuKien = mysqli_insert_id($conn);

            mysqli_query($conn, "INSERT INTO dieukien_don (idDieuKien, idThuocTinhKiemTra, idToanTu, giaTriSoSanh)
                                 VALUES ($idDieuKien, $idThuocTinh, $idToanTu, '" . chuan_hoa_chuoi_sql($conn, $giaTri) . "')");
        }
    }

    // Tạo tổ hợp điều kiện
    if ($action === 'create_group') {
        $idTrai = (int)($data['idDieuKienTrai'] ?? 0);
        $idPhai = (int)($data['idDieuKienPhai'] ?? 0);
        $idToanTu = (int)($data['idToanTuLogic'] ?? 0);
        $ten = $data['tenToHop'] ?? 'Tổ hợp điều kiện';

        if ($idTrai && $idPhai && $idToanTu) {
            mysqli_query($conn, "INSERT INTO dieukien (loaiDieuKien, tenDieuKien, moTa)
                                 VALUES ('TOHOP', '" . chuan_hoa_chuoi_sql($conn, $ten) . "', NULL)");
            $idDieuKien = mysqli_insert_id($conn);

            mysqli_query($conn, "INSERT INTO tohop_dieukien (idDieuKien, idDieuKienTrai, idDieuKienPhai, idToanTu)
                                 VALUES ($idDieuKien, $idTrai, $idPhai, $idToanTu)");
        }
    }

    // Gán điều kiện cuối cho quy chế
    if ($action === 'attach_condition') {
        $idQuyChe = (int)($data['idQuyChe'] ?? 0);
        $idDieuKienCuoi = (int)($data['idDieuKienCuoi'] ?? 0);

        if ($idQuyChe && $idDieuKienCuoi) {
            mysqli_query($conn, "REPLACE INTO quyche_dieukien (idQuyChe, idDieuKienCuoi)
                                 VALUES ($idQuyChe, $idDieuKienCuoi)");
        }
    }

    header("Location: ?module=event&action=config_rules&id=$id_su_kien&loai=$loai_quy_che");
    exit;
}

layout('header');
layout('navbar');
?>

<main class="main container py-4">
    <h2>Cấu hình Quy chế & Điều kiện</h2>

    <div class="mb-3">
        <label>Loại quy chế</label>
        <select class="form-select" onchange="location.href='?module=event&action=config_rules&id=<?php echo $id_su_kien ?>&loai='+this.value;">
            <option value="THAMGIA" <?php echo $loai_quy_che=='THAMGIA'?'selected':''; ?>>THAMGIA</option>
            <option value="VONGTHI" <?php echo $loai_quy_che=='VONGTHI'?'selected':''; ?>>VONGTHI</option>
            <option value="SANPHAM" <?php echo $loai_quy_che=='SANPHAM'?'selected':''; ?>>SANPHAM</option>
            <option value="GIAITHUONG" <?php echo $loai_quy_che=='GIAITHUONG'?'selected':''; ?>>GIAITHUONG</option>
        </select>
    </div>

    <hr>

    <h4>Tạo Quy chế</h4>
    <form method="post">
        <input type="hidden" name="action" value="create_rule">
        <input type="text" name="tenQuyChe" class="form-control mb-2" placeholder="Tên quy chế">
        <textarea name="moTa" class="form-control mb-2" placeholder="Mô tả"></textarea>
        <button class="btn btn-primary">Tạo quy chế</button>
    </form>

    <hr>

    <h4>Tạo Điều kiện đơn</h4>
    <form method="post">
        <input type="hidden" name="action" value="create_condition">
        <input type="text" name="tenDieuKien" class="form-control mb-2" placeholder="Tên điều kiện">
        <textarea name="moTaDieuKien" class="form-control mb-2" placeholder="Mô tả điều kiện"></textarea>

        <select name="idThuocTinh" class="form-select mb-2">
            <?php while ($row = mysqli_fetch_assoc($thuoctinh_list)) : ?>
                <option value="<?php echo $row['idThuocTinhKiemTra']; ?>">
                    <?php echo $row['tenThuocTinh']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="idToanTu" class="form-select mb-2">
            <?php while ($row = mysqli_fetch_assoc($toantu_compare)) : ?>
                <option value="<?php echo $row['idToanTu']; ?>">
                    <?php echo $row['kyHieu']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="text" name="giaTriSoSanh" class="form-control mb-2" placeholder="Giá trị so sánh">
        <button class="btn btn-success">Tạo điều kiện</button>
    </form>

    <hr>

    <h4>Tạo Tổ hợp điều kiện</h4>
    <form method="post">
        <input type="hidden" name="action" value="create_group">
        <input type="text" name="tenToHop" class="form-control mb-2" placeholder="Tên tổ hợp (tuỳ chọn)">
        <select name="idDieuKienTrai" class="form-select mb-2">
            <?php mysqli_data_seek($dieukien_list, 0); while ($row = mysqli_fetch_assoc($dieukien_list)) : ?>
                <option value="<?php echo $row['idDieuKien']; ?>">
                    <?php echo $row['tenDieuKien']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="idDieuKienPhai" class="form-select mb-2">
            <?php mysqli_data_seek($dieukien_list, 0); while ($row = mysqli_fetch_assoc($dieukien_list)) : ?>
                <option value="<?php echo $row['idDieuKien']; ?>">
                    <?php echo $row['tenDieuKien']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="idToanTuLogic" class="form-select mb-2">
            <?php while ($row = mysqli_fetch_assoc($toantu_logic)) : ?>
                <option value="<?php echo $row['idToanTu']; ?>">
                    <?php echo $row['kyHieu']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-warning">Tạo tổ hợp</button>
    </form>

    <hr>

    <h4>Gán điều kiện cuối cho Quy chế</h4>
    <form method="post">
        <input type="hidden" name="action" value="attach_condition">
        <select name="idQuyChe" class="form-select mb-2">
            <?php while ($row = mysqli_fetch_assoc($quyche_list)) : ?>
                <option value="<?php echo $row['idQuyChe']; ?>">
                    <?php echo $row['tenQuyChe']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="idDieuKienCuoi" class="form-select mb-2">
            <?php mysqli_data_seek($dieukien_list, 0); while ($row = mysqli_fetch_assoc($dieukien_list)) : ?>
                <option value="<?php echo $row['idDieuKien']; ?>">
                    <?php echo $row['tenDieuKien']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-dark">Gán điều kiện cuối</button>
    </form>

</main>

<?php layout('footer'); ?>