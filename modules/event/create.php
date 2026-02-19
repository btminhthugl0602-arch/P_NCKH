<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

global $conn;

if (!isset($_SESSION['user_id'])) {
    die('Bạn phải đăng nhập');
}

$userId = (int)$_SESSION['user_id'];
$idSK   = isset($_GET['idSK']) ? (int)$_GET['idSK'] : 0;

if ($idSK <= 0) {
    die('Sự kiện không hợp lệ');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tennhom    = mysqli_real_escape_string($conn, $_POST['tennhom']);
    $mota       = mysqli_real_escape_string($conn, $_POST['mota']);
    $soluong    = (int)$_POST['soluong'];
    $idTieuBan  = (int)$_POST['idTieuBan'];   // ✅ lấy đúng name

    // 1️⃣ Tạo nhóm
    $sql = "INSERT INTO nhom (idSK, idTieuban) VALUES ($idSK, $tieubanId)";
    mysqli_query($conn, $sql);

    $idNhom = mysqli_insert_id($conn);

    // 2️⃣ Thêm thông tin nhóm
    $sql2 = "
        INSERT INTO thongtinnhom (idnhom, tennhom, mota, soluongtoida, dangtuyen)
        VALUES ($idNhom, '$tennhom', '$mota', $soluong, 1)
    ";
    mysqli_query($conn, $sql2);

    // 3️⃣ Thêm người tạo làm trưởng nhóm
    $sql3 = "
        INSERT INTO thanhviennhom (idnhom, idtk, idvaitronhom, trangthai, ngaythamgia)
        VALUES ($idNhom, $userId, 1, 1, NOW())
    ";
    mysqli_query($conn, $sql3);

    echo "
<script>
    alert('Tạo nhóm thành công!');
    window.location.href = '" . _HOST_URL . "?module=event&action=view&id=$idSK&tab=my-groups';
</script>
";
exit();
}
// Lấy danh sách tiểu ban theo sự kiện
$sqlTB = "SELECT idTieuBan, tenTieuBan 
          FROM tieuban 
          WHERE idSK = $idSK";
$resultTB = mysqli_query($conn, $sqlTB);
$tieubans = mysqli_fetch_all($resultTB, MYSQLI_ASSOC);
?>

<<?php
layout('header');
layout('navbar');
?>

<section class="py-5" style="min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">

                <div class="card shadow-lg border-0 rounded-4 p-4">
                    
                    <h2 class="text-center mb-4 fw-bold">
                        Tạo nhóm mới
                    </h2>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Tên nhóm</label>
                            <input type="text" name="tennhom" 
                                   class="form-control form-control-lg" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="mota" 
                                      class="form-control form-control-lg"
                                      rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số lượng tối đa</label>
                            <input type="number" name="soluong"
                                   class="form-control form-control-lg"
                                   min="1" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Tiểu ban</label>
                            <select name="idTieuBan"
                                    class="form-control form-control-lg"
                                    required>
                                <option value="">-- Chọn tiểu ban --</option>
                                <?php foreach ($tieubans as $tb): ?>
                                    <option value="<?= $tb['idTieuBan'] ?>">
                                        <?= htmlspecialchars($tb['tenTieuBan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit"
                                    class="btn btn-primary btn-lg rounded-pill">
                                Tạo nhóm
                            </button>
                        </div>

                    </form>

                </div>

            </div>
        </div>
    </div>
</section>

<?php layout('footer'); ?>