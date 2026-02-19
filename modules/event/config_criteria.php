<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_bo_tieu_chi.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected_set_id = isset($_GET['set']) ? (int)$_GET['set'] : 0;

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$bo_list = _select_info($conn, 'botieuchi', [], [
    'ORDER BY' => ['idBoTieuChi', 'DESC', '', '']
]);

$bo_current = null;
if ($selected_set_id > 0) {
    $bo_current = truy_van_mot_ban_ghi($conn, 'botieuchi', 'idBoTieuChi', $selected_set_id);
}

$tieuchi_list = _select_info($conn, 'tieuchi', [], [
    'ORDER BY' => ['idTieuChi', 'DESC', '', '']
]);

$vongthi_list = _select_info($conn, 'vongthi', [], [
    'WHERE' => ['idSK', '=', $id_su_kien, ''],
    'ORDER BY' => ['thuTu', 'ASC', '', '']
]);

if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    if ($action === 'create_set') {
        $ten = $data['tenBoTieuChi'] ?? '';
        $moTa = $data['moTa'] ?? '';
        if (!empty($ten)) {
            $result = tao_bo_tieu_chi($conn, $user_id, $id_su_kien, $ten, $moTa);
            if ($result['status']) {
                header("Location: ?module=event&action=config_criteria&id=$id_su_kien&set=" . $result['idBoTieuChi'] . "#criteriaModal");
                exit;
            }
        }
    }

    if ($action === 'create_criteria') {
        $noiDung = $data['noiDungTieuChi'] ?? '';
        $diemToiDa = $data['diemToiDa'] ?? '10';
        $result = tao_tieu_chi($conn, $user_id, $noiDung, $diemToiDa);
    }

    if ($action === 'attach_criteria') {
        $idBo = (int)($data['idBoTieuChi'] ?? 0);
        $idTieuChi = (int)($data['idTieuChi'] ?? 0);
        $tyTrong = $data['tyTrong'] ?? '1.00';
        $result = them_tieu_chi_vao_bo($conn, $user_id, $idBo, $idTieuChi, $tyTrong);
    }

    if ($action === 'assign_round') {
        $idVong = (int)($data['idVongThi'] ?? 0);
        $idBo = (int)($data['idBoTieuChiAssign'] ?? 0);
        $result = gan_bo_tieu_chi_vao_vong($conn, $user_id, $id_su_kien, $idVong, $idBo);
    }

    header("Location: ?module=event&action=config_criteria&id=$id_su_kien&set=$selected_set_id#criteriaModal");
    exit;
}

layout('header');
layout('navbar');
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
            <h2 class="fw-bold mb-1">Cấu hình Bộ tiêu chí & Chấm điểm</h2>
            <p class="text-muted mb-0">Tạo bộ tiêu chí và gán tiêu chí theo quy trình 3 bước.</p>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Bộ tiêu chí hiện có</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#setModal">
                    Thêm bộ tiêu chí
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Tên bộ</th>
                            <th style="width: 160px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bo_list)): ?>
                            <?php foreach ($bo_list as $row) : ?>
                                <tr>
                                    <td><?php echo $row['idBoTieuChi']; ?></td>
                                    <td><?php echo htmlspecialchars($row['tenBoTieuChi']); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="?module=event&action=config_criteria&id=<?php echo $id_su_kien; ?>&set=<?php echo $row['idBoTieuChi']; ?>#criteriaModal">
                                            Cấu hình tiêu chí
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Chưa có bộ tiêu chí nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal tạo bộ tiêu chí -->
<div class="modal fade" id="setModal" tabindex="-1" aria-labelledby="setModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create_set">
                <div class="modal-header">
                    <h5 class="modal-title" id="setModalLabel">Tạo bộ tiêu chí mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label">Tên bộ tiêu chí</label>
                    <input type="text" name="tenBoTieuChi" class="form-control mb-2" required>

                    <label class="form-label">Mô tả</label>
                    <textarea name="moTa" class="form-control" rows="3"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal cấu hình tiêu chí -->
<div class="modal fade" id="criteriaModal" tabindex="-1" aria-labelledby="criteriaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="criteriaModalLabel">
                    Cấu hình tiêu chí
                    <?php if ($bo_current): ?>
                        - <?php echo htmlspecialchars($bo_current['tenBoTieuChi']); ?>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <?php if ($bo_current): ?>
                    <div class="mb-4">
                        <h6 class="fw-semibold">Bước 1: Tạo tiêu chí</h6>
                        <form method="post" class="mb-3">
                            <input type="hidden" name="action" value="create_criteria">
                            <input type="text" name="noiDungTieuChi" class="form-control mb-2" placeholder="Nội dung tiêu chí" required>
                            <input type="number" step="0.01" name="diemToiDa" class="form-control mb-2" value="10" required>
                            <button class="btn btn-success">Tạo tiêu chí</button>
                        </form>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-semibold">Bước 2: Gán tiêu chí vào bộ</h6>
                        <form method="post" class="mb-3">
                            <input type="hidden" name="action" value="attach_criteria">
                            <input type="hidden" name="idBoTieuChi" value="<?php echo (int)$bo_current['idBoTieuChi']; ?>">

                            <select name="idTieuChi" class="form-select mb-2">
                                <?php foreach ($tieuchi_list as $row) : ?>
                                    <option value="<?php echo $row['idTieuChi']; ?>">
                                        <?php echo $row['noiDungTieuChi']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="number" step="0.01" name="tyTrong" class="form-control mb-2" value="1.00">
                            <button class="btn btn-warning">Gán tiêu chí</button>
                        </form>
                    </div>

                    <div class="mb-2">
                        <h6 class="fw-semibold">Bước 3: Gán bộ tiêu chí cho vòng thi</h6>
                        <form method="post">
                            <input type="hidden" name="action" value="assign_round">
                            <input type="hidden" name="idBoTieuChiAssign" value="<?php echo (int)$bo_current['idBoTieuChi']; ?>">

                            <select name="idVongThi" class="form-select mb-2">
                                <?php foreach ($vongthi_list as $row) : ?>
                                    <option value="<?php echo $row['idVongThi']; ?>">
                                        <?php echo $row['tenVongThi']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button class="btn btn-dark">Xác nhận gán</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">Vui lòng chọn một bộ tiêu chí trư���c khi cấu hình.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Lấy đúng ID của modal trong file config_criteria.php
    const criteriaModal = document.getElementById('criteriaModal');

    // Hàm kiểm tra và hiển thị modal
    function showModalIfHashMatch() {
        if (window.location.hash === '#criteriaModal' && criteriaModal && typeof bootstrap !== 'undefined') {
            // Dùng getOrCreateInstance để tránh tạo ra rác bộ nhớ
            const modal = bootstrap.Modal.getOrCreateInstance(criteriaModal);
            modal.show();
        }
    }

    // Kích hoạt khi trang vừa tải xong (Lần click đầu tiên)
    showModalIfHashMatch();

    // Lắng nghe sự kiện thay đổi hash (Lần click thứ hai trở đi khi không reload)
    window.addEventListener('hashchange', showModalIfHashMatch);

    // Dọn dẹp URL khi người dùng đóng modal
    if (criteriaModal) {
        criteriaModal.addEventListener('hidden.bs.modal', function () {
            if (window.location.hash === '#criteriaModal') {
                // Xóa hash #criteriaModal khỏi URL để lần sau click trình duyệt nhận diện là url có sự thay đổi
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        });
    }
});
</script>

<?php layout('footer'); ?>