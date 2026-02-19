<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_quy_che.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected_rule_id = isset($_GET['rule']) ? (int)$_GET['rule'] : 0;
$loai_quy_che = isset($_GET['loai']) ? $_GET['loai'] : 'THAMGIA';

$event_message = '';
$event_error = '';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$quyche_list = _select_info($conn, 'quyche', [], [
    'WHERE' => ['idSK', '=', $id_su_kien, ''],
    'ORDER BY' => ['idQuyChe', 'DESC', '', '']
]);

$rule_current = null;
$current_rule_id = 0;
$active_rule_type = $loai_quy_che;

if ($selected_rule_id > 0) {
    $rule_data = _select_info($conn, 'quyche', [], [
        'WHERE' => [
            'idQuyChe', '=', $selected_rule_id, 'AND',
            'idSK', '=', $id_su_kien, ''
        ],
        'LIMIT' => [1, '', '', '']
    ]);
    if (!empty($rule_data)) {
        $rule_current = $rule_data[0];
        $current_rule_id = (int)$rule_current['idQuyChe'];
        $active_rule_type = $rule_current['loaiQuyChe'] ?? $loai_quy_che;
    }
}

$thuoctinh_list = _select_info($conn, 'thuoctinh_kiemtra', [], [
    'WHERE' => ['loaiApDung', '=', $active_rule_type, '']
]);

$toantu_compare = _select_info($conn, 'toantu', [], [
    'WHERE' => ['loaiToanTu', '=', 'compare', '']
]);

$toantu_logic = _select_info($conn, 'toantu', [], [
    'WHERE' => ['loaiToanTu', '=', 'logic', '']
]);

$dieukien_list = _select_info($conn, 'dieukien', [], [
    'ORDER BY' => ['idDieuKien', 'DESC', '', '']
]);

$attribute_types = [
    'GPA' => 'decimal',
    'DRL' => 'int',
    'diemTrungBinh' => 'decimal',
    'xepLoai' => 'text',
    'trangThai' => 'text',
    'TrangThai' => 'text',
    'idloaitailieu' => 'int',
    'isActive' => 'int',
    'diemTongKet' => 'decimal',
    'xepHang' => 'int',
    'idGiaiThuong' => 'int'
];

function build_condition_text($conn, $idDieuKien) {
    $idDieuKien = (int)$idDieuKien;
    if ($idDieuKien <= 0) return '';

    $dk = truy_van_mot_ban_ghi($conn, 'dieukien', 'idDieuKien', $idDieuKien);
    if (!$dk) return '';

    if ($dk['loaiDieuKien'] === 'DON') {
        return $dk['tenDieuKien'];
    }

    $tohop = truy_van_mot_ban_ghi($conn, 'tohop_dieukien', 'idDieuKien', $idDieuKien);
    if (!$tohop) return $dk['tenDieuKien'];

    $left = build_condition_text($conn, $tohop['idDieuKienTrai']);
    $right = build_condition_text($conn, $tohop['idDieuKienPhai']);

    $op = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', (int)$tohop['idToanTu']);
    $symbol = $op['kyHieu'] ?? 'AND';

    return '(' . $left . ' ' . $symbol . ' ' . $right . ')';
}

$final_condition_text = '';
if ($current_rule_id > 0) {
    $final = truy_van_mot_ban_ghi($conn, 'quyche_dieukien', 'idQuyChe', $current_rule_id);
    if ($final) {
        $final_condition_text = build_condition_text($conn, $final['idDieuKienCuoi']);
    }
}

if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    if ($action === 'create_rule') {
        $ten = $data['tenQuyChe'] ?? '';
        $mota = $data['moTa'] ?? '';
        $loai = $data['loaiQuyChe'] ?? 'THAMGIA';

        if (!empty($ten)) {
            $result = tao_quy_che($conn, $user_id, $id_su_kien, $ten, $loai, $mota);
            if ($result['status']) {
                header("Location: ?module=event&action=config_rules&id=$id_su_kien&loai=$loai&rule=" . $result['idQuyChe']);
                exit;
            }
        }
    }

    if ($action === 'create_condition') {
        $result = tao_dieu_kien_don(
            $conn,
            $user_id,
            $data['tenDieuKien'] ?? '',
            (int)($data['idThuocTinh'] ?? 0),
            (int)($data['idToanTu'] ?? 0),
            $data['giaTriSoSanh'] ?? '',
            $data['moTaDieuKien'] ?? ''
        );
    }

    if ($action === 'create_group') {
        $result = tao_to_hop_dieu_kien(
            $conn,
            $user_id,
            (int)($data['idDieuKienTrai'] ?? 0),
            (int)($data['idToanTuLogic'] ?? 0),
            (int)($data['idDieuKienPhai'] ?? 0),
            $data['tenToHop'] ?? 'Tổ hợp điều kiện'
        );
    }

    if ($action === 'attach_condition') {
        $result = gan_dieu_kien_cho_quy_che(
            $conn,
            $user_id,
            (int)($data['idQuyChe'] ?? 0),
            (int)($data['idDieuKienCuoi'] ?? 0)
        );
    }

    $redirect_rule_id = $current_rule_id ?: $selected_rule_id;
    if ($action === 'attach_condition') {
        $redirect_rule_id = (int)($data['idQuyChe'] ?? $redirect_rule_id);
    }

    header("Location: ?module=event&action=config_rules&id=$id_su_kien&rule=$redirect_rule_id#conditionModal");
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
            <h2 class="fw-bold mb-1">Cấu hình Quy chế & Điều kiện</h2>
            <p class="text-muted mb-0">Tạo quy chế cho sự kiện và cấu hình điều kiện khi cần.</p>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Quy chế của sự kiện</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ruleModal">
                    Thêm quy chế
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Tên quy chế</th>
                            <th style="width: 160px;">Loại</th>
                            <th style="width: 180px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($quyche_list)): ?>
                            <?php foreach ($quyche_list as $row) : ?>
                                <tr>
                                    <td><?php echo $row['idQuyChe']; ?></td>
                                    <td><?php echo htmlspecialchars($row['tenQuyChe']); ?></td>
                                    <td><?php echo htmlspecialchars($row['loaiQuyChe']); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="?module=event&action=config_rules&id=<?php echo $id_su_kien; ?>&rule=<?php echo $row['idQuyChe']; ?>#conditionModal">
                                            Cấu hình điều kiện
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có quy chế nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal tạo quy chế -->
<div class="modal fade" id="ruleModal" tabindex="-1" aria-labelledby="ruleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create_rule">
                <div class="modal-header">
                    <h5 class="modal-title" id="ruleModalLabel">Tạo quy chế mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Tên quy chế</label>
                    <input type="text" name="tenQuyChe" class="form-control mb-2" required>

                    <label class="form-label">Loại quy chế</label>
                    <select name="loaiQuyChe" class="form-select mb-2" required>
                        <option value="THAMGIA">THAMGIA</option>
                        <option value="VONGTHI">VONGTHI</option>
                        <option value="SANPHAM">SANPHAM</option>
                        <option value="GIAITHUONG">GIAITHUONG</option>
                    </select>

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

<!-- Modal cấu hình điều kiện -->
<div class="modal fade" id="conditionModal" tabindex="-1" aria-labelledby="conditionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="conditionModalLabel">
                    Cấu hình điều kiện
                    <?php if ($rule_current): ?>
                        - <?php echo htmlspecialchars($rule_current['tenQuyChe']); ?>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <?php if ($current_rule_id > 0): ?>
                    <div class="mb-4" id="step1">
                        <h6 class="fw-semibold">Bước 1: Tạo điều kiện đơn</h6>
                        <form method="post" class="mb-3" id="singleConditionForm">
                            <input type="hidden" name="action" value="create_condition">
                            <input type="text" name="tenDieuKien" class="form-control mb-2" placeholder="Tên điều kiện" required>
                            <textarea name="moTaDieuKien" class="form-control mb-2" placeholder="Mô tả điều kiện"></textarea>

                            <select name="idThuocTinh" class="form-select mb-2" id="attrSelect" required>
                                <?php foreach ($thuoctinh_list as $row) : 
                                    $field = $row['tenTruongDL'];
                                    $dtype = $attribute_types[$field] ?? 'text';
                                ?>
                                    <option value="<?php echo $row['idThuocTinhKiemTra']; ?>"
                                            data-field="<?php echo htmlspecialchars($field); ?>"
                                            data-type="<?php echo $dtype; ?>">
                                        <?php echo $row['tenThuocTinh']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="idToanTu" class="form-select mb-2" required>
                                <?php foreach ($toantu_compare as $row) : ?>
                                    <option value="<?php echo $row['idToanTu']; ?>">
                                        <?php echo $row['kyHieu']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="text" name="giaTriSoSanh" class="form-control mb-2" id="valueInput" placeholder="Giá trị so sánh" required>
                            <div class="text-muted small mb-2" id="valueHint">Ví dụ: 2.5</div>

                            <button class="btn btn-success">Tạo điều kiện</button>
                        </form>
                        <div class="alert alert-info mb-0">Sau khi tạo điều kiện, bạn có thể nối điều kiện ở Bước 2.</div>
                    </div>

                    <div class="mb-4" id="step2">
                        <h6 class="fw-semibold">Bước 2: Nối điều kiện (cây nhị phân)</h6>
                        <p class="text-muted mb-2">Chọn điều kiện trái, phép nối và điều kiện phải.</p>

                        <form method="post" class="mb-3">
                            <input type="hidden" name="action" value="create_group">
                            <input type="text" name="tenToHop" class="form-control mb-2" placeholder="Tên tổ hợp (tuỳ chọn)">

                            <select name="idDieuKienTrai" class="form-select mb-2" required>
                                <?php foreach ($dieukien_list as $row) : ?>
                                    <option value="<?php echo $row['idDieuKien']; ?>">
                                        <?php echo $row['tenDieuKien']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="idToanTuLogic" class="form-select mb-2" required>
                                <?php foreach ($toantu_logic as $row) : ?>
                                    <option value="<?php echo $row['idToanTu']; ?>">
                                        <?php echo $row['kyHieu']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="idDieuKienPhai" class="form-select mb-2" required>
                                <?php foreach ($dieukien_list as $row) : ?>
                                    <option value="<?php echo $row['idDieuKien']; ?>">
                                        <?php echo $row['tenDieuKien']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button class="btn btn-warning">Tạo tổ hợp</button>
                        </form>

                        <div class="alert alert-secondary mb-0">
                            Mỗi tổ hợp là một node nhị phân: (Điều kiện trái) AND/OR (Điều kiện phải).
                        </div>
                    </div>

                    <div class="mb-2" id="step3">
                        <h6 class="fw-semibold">Bước 3: Xác nhận chuỗi điều kiện</h6>
                        <?php if (!empty($final_condition_text)): ?>
                            <div class="alert alert-success">
                                <strong>Chuỗi điều kiện hiện tại:</strong><br>
                                <?php echo htmlspecialchars($final_condition_text); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Chưa có điều kiện cuối cho quy chế. Hãy tạo tổ hợp và gán điều kiện cuối.
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="attach_condition">
                            <input type="hidden" name="idQuyChe" value="<?php echo (int)$current_rule_id; ?>">

                            <select name="idDieuKienCuoi" class="form-select mb-2">
                                <?php foreach ($dieukien_list as $row) : ?>
                                    <option value="<?php echo $row['idDieuKien']; ?>">
                                        <?php echo $row['tenDieuKien']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button class="btn btn-dark">Xác nhận điều kiện cuối</button>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="alert alert-warning mb-0">Vui lòng chọn một quy chế trước khi cấu hình điều kiện.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const conditionModal = document.getElementById('conditionModal');

    // Hàm kiểm tra và hiển thị modal
    function showModalIfHashMatch() {
        if (window.location.hash === '#conditionModal' && conditionModal && typeof bootstrap !== 'undefined') {
            // Dùng getOrCreateInstance để tránh tạo ra rác bộ nhớ khi mở/đóng nhiều lần
            const modal = bootstrap.Modal.getOrCreateInstance(conditionModal);
            modal.show();
        }
    }

    // Kích hoạt khi trang vừa tải xong (Lần click đầu tiên)
    showModalIfHashMatch();

    // Lắng nghe sự kiện thay đổi hash (Lần click thứ hai trở đi)
    window.addEventListener('hashchange', showModalIfHashMatch);

    // Dọn dẹp URL khi người dùng đóng modal
    if (conditionModal) {
        conditionModal.addEventListener('hidden.bs.modal', function () {
            if (window.location.hash === '#conditionModal') {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        });
    }

    // ==========================================
    // XỬ LÝ LOGIC CHO FORM NHẬP LIỆU (Giữ nguyên)
    // ==========================================
    const attrSelect = document.getElementById('attrSelect');
    const valueInput = document.getElementById('valueInput');
    const valueHint = document.getElementById('valueHint');

    function updateValueInput() {
        if (!attrSelect) return;
        const option = attrSelect.options[attrSelect.selectedIndex];
        const type = option.getAttribute('data-type') || 'text';

        if (type === 'int') {
            valueInput.type = 'number';
            valueInput.step = '1';
            valueHint.textContent = 'Ví dụ: 85';
        } else if (type === 'decimal') {
            valueInput.type = 'number';
            valueInput.step = '0.01';
            valueHint.textContent = 'Ví dụ: 2.5';
        } else {
            valueInput.type = 'text';
            valueInput.removeAttribute('step');
            valueHint.textContent = 'Ví dụ: Đạt';
        }
    }

    if (attrSelect) {
        attrSelect.addEventListener('change', updateValueInput);
        updateValueInput();
    }

    const singleForm = document.getElementById('singleConditionForm');
    if (singleForm) {
        singleForm.addEventListener('submit', function (e) {
            const option = attrSelect.options[attrSelect.selectedIndex];
            const type = option.getAttribute('data-type') || 'text';
            const val = valueInput.value.trim();

            if (type === 'int' && !/^-?\d+$/.test(val)) {
                alert('Giá trị phải là số nguyên.');
                e.preventDefault();
            }

            if (type === 'decimal' && !/^-?\d+(\.\d+)?$/.test(val)) {
                alert('Giá trị phải là số thập phân hợp lệ.');
                e.preventDefault();
            }

            if (type === 'text' && val.length === 0) {
                alert('Giá trị không được để trống.');
                e.preventDefault();
            }
        });
    }
});
</script>

<?php layout('footer'); ?>