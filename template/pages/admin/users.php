
// Hiện flash sau POST qua toast.php trong footer
if (!empty($success_msg)) {
    $_SESSION['flash_msg']  = $success_msg;
    $_SESSION['flash_type'] = 'success';
    $redirect_url = '?module=admin&action=users&tab=' . $active_tab . (!empty($search) ? '&search=' . urlencode($search) : '') . ($filter_role > 0 ? '&filter_role=' . $filter_role : '');
    header('Location: ' . $redirect_url);
    exit;
}
if (!empty($error_msg)) {
    $_SESSION['flash_msg']  = $error_msg;
    $_SESSION['flash_type'] = 'danger';
    $redirect_url = '?module=admin&action=users&tab=' . $active_tab . (!empty($search) ? '&search=' . urlencode($search) : '') . ($filter_role > 0 ? '&filter_role=' . $filter_role : '');
    header('Location: ' . $redirect_url);
    exit;
}

function render_quyen_accordion(array $grouped, string $prefix, string $colorClass, bool $allOpen = false): void
{
    $i = 0;
    foreach ($grouped as $nhom => $qs) {
        $i++;
        $colId = $prefix . '_g' . $i;
        $show  = $allOpen ? 'show' : '';
?>
        <div class="accordion-item border rounded-3 mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $show ? '' : 'collapsed' ?> py-2 rounded-3" type="button"
                    data-bs-toggle="collapse" data-bs-target="#<?= $colId ?>">
                    <i class="bi bi-folder me-2 text-<?= $colorClass ?>"></i><?= htmlspecialchars($nhom) ?>
                    <span
                        class="badge bg-<?= $colorClass ?>-subtle text-<?= $colorClass ?> border border-<?= $colorClass ?> ms-2"><?= count($qs) ?></span>
                </button>
            </h2>
            <div id="<?= $colId ?>" class="accordion-collapse collapse <?= $show ?>">
                <div class="accordion-body pt-1">
                    <?php foreach ($qs as $q): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input <?= $prefix ?>-cb" type="checkbox"
                                name="<?= strpos($prefix, 'role') === false ? 'quyen_ids' : 'role_quyen_ids' ?>[]"
                                value="<?= $q['idQuyen'] ?>" id="<?= $prefix ?>_<?= $q['idQuyen'] ?>"
                                data-idquyen="<?= $q['idQuyen'] ?>">
                            <label class="form-check-label" for="<?= $prefix ?>_<?= $q['idQuyen'] ?>">
                                <span class="fw-semibold"><?= htmlspecialchars($q['tenQuyen']) ?></span>
                                <span class="badge bg-secondary ms-1"><?= htmlspecialchars($q['maQuyen_code']) ?></span>
                                <?php if (!empty($q['moTa'])): ?><div class="text-muted small"><?= htmlspecialchars($q['moTa']) ?>
                                    </div><?php endif; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
<?php
    }
}
?>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-primary fs-6 px-3 py-2 d-inline-block mb-2"><i
                                class="bi bi-person-plus me-1"></i>Tạo tài khoản mới</span>
                        <p class="text-muted small mb-0">Điền đầy đủ thông tin để tạo tài khoản trong hệ thống.</p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="create_user">

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-key me-1"></i>Thông tin
                            đăng nhập</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tên đăng nhập <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="tenTK" placeholder="VD: sv_nguyen..."
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mật khẩu <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="matKhau" id="createPassword"
                                        placeholder="Nhập mật khẩu..." required>
                                    <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePwd('createPassword',this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Loại tài khoản <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" name="idLoaiTK" id="createLoaiTK"
                                    onchange="toggleCreateForm(this.value)" required>
                                    <option value="">-- Chọn loại --</option>
                                    <?php foreach ($loai_tks as $ltk): ?>
                                        <option value="<?= $ltk['idLoaiTK'] ?>"><?= htmlspecialchars($ltk['tenLoaiTK']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Trạng thái</label>
                                <div class="d-flex gap-3 mt-2">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="isActive"
                                            value="1" checked><label class="form-check-label"><i
                                                class="bi bi-unlock text-success me-1"></i>Hoạt động</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="isActive"
                                            value="0"><label class="form-check-label"><i
                                                class="bi bi-lock text-danger me-1"></i>Khóa</label></div>
                                </div>
                            </div>
                        </div>

                        <div id="create-gv-section" class="d-none">
                            <h6 class="fw-bold text-success mb-3 border-bottom pb-2"><i
                                    class="bi bi-person-workspace me-1"></i>Hồ sơ giảng viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Họ và tên <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control gv-hoten" name="hoTen_gv"
                                        placeholder="Nhập họ và tên...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Giới tính</label>
                                    <div class="d-flex gap-2 mt-2">
                                        <div class="form-check"><input class="form-check-input" type="radio"
                                                name="gioiTinh" value="1"><label class="form-check-label">Nam</label>
                                        </div>
                                        <div class="form-check"><input class="form-check-input" type="radio"
                                                name="gioiTinh" value="0" checked><label
                                                class="form-check-label">Nữ</label></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Khoa</label>
                                    <select class="form-select" name="idDonVi_gv" id="create-idKhoa">
                                        <option value="">-- Chọn --</option>
                                        <?php foreach ($khoas as $k): ?>
                                            <option value="<?= $k['idKhoa'] ?>"><?= htmlspecialchars($k['tenKhoa']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="create-sv-section" class="d-none">
                            <h6 class="fw-bold text-info mb-3 border-bottom pb-2"><i
                                    class="bi bi-mortarboard me-1"></i>Hồ sơ sinh viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Họ và tên <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control sv-hoten" name="hoTen_sv"
                                        placeholder="Nhập họ và tên...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Mã sinh viên</label>
                                    <input type="text" class="form-control" name="msv" placeholder="VD: SV001">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">GPA</label>
                                    <input type="number" step="0.01" min="0" max="4" class="form-control" name="gpa"
                                        placeholder="0.00">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ĐRL</label>
                                    <input type="number" min="0" max="100" class="form-control" name="drl"
                                        placeholder="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lớp</label>
                                <select class="form-select" name="edit_idDonVi_sv" id="edit_idLop">
                                    <option value="">-- Chọn lớp --</option>
                                    <?php foreach ($lops as $l): ?>
                                        <option value="<?= $l['idLop'] ?>"><?= htmlspecialchars($l['tenLop']) ?>
                                            (<?= htmlspecialchars($l['tenKhoa']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php if (!empty($ht_grouped)): ?>
                            <h6 class="fw-bold text-warning mb-3 border-bottom pb-2"><i
                                    class="bi bi-shield-lock me-1"></i>Quyền hệ thống <small
                                    class="text-muted fw-normal">(tuỳ chọn)</small></h6>
                            <div class="accordion accordion-flush" id="createPermAcc">
                                <?php render_quyen_accordion($ht_grouped, 'cq', 'warning', false); ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between pt-3 mt-2 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary px-4"><i
                                    class="bi bi-check-circle me-1"></i>Tạo tài khoản</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2 d-inline-block mb-2"><i
                                class="bi bi-pencil-square me-1"></i>Chỉnh sửa tài khoản</span>
                        <p class="text-muted small mb-0">Bỏ trống mật khẩu nếu không muốn thay đổi.</p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="edit_user">
                        <input type="hidden" name="edit_idTK" id="edit_idTK">

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-key me-1"></i>Thông tin
                            đăng nhập</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tên đăng nhập</label>
                                <input type="text" class="form-control bg-light" id="edit_tenTK_display" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mật khẩu mới <small class="text-muted">(bỏ trống =
                                        không đổi)</small></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="edit_matKhau" id="editPassword"
                                        placeholder="Mật khẩu mới...">
                                    <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePwd('editPassword',this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Loại tài khoản</label>
                                <select class="form-select" name="edit_idLoaiTK" id="edit_idLoaiTK"
                                    onchange="toggleEditForm(this.value)">
                                    <?php foreach ($loai_tks as $ltk): ?>
                                        <option value="<?= $ltk['idLoaiTK'] ?>"><?= htmlspecialchars($ltk['tenLoaiTK']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Trạng thái</label>
                                <div class="d-flex gap-3 mt-2">
                                    <div class="form-check"><input class="form-check-input" type="radio"
                                            name="edit_isActive" id="edit_active1" value="1"><label
                                            class="form-check-label" for="edit_active1"><i
                                                class="bi bi-unlock text-success me-1"></i>Hoạt động</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio"
                                            name="edit_isActive" id="edit_active0" value="0"><label
                                            class="form-check-label" for="edit_active0"><i
                                                class="bi bi-lock text-danger me-1"></i>Khóa</label></div>
                                </div>
                            </div>
                        </div>

                        <div id="edit-gv-section" class="d-none">
                            <h6 class="fw-bold text-success mb-3 border-bottom pb-2"><i
                                    class="bi bi-person-workspace me-1"></i>Hồ sơ giảng viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Họ và tên</label>
                                    <input type="text" class="form-control" name="edit_hoTen_gv" id="edit_hoTen_gv">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Giới tính</label>
                                    <div class="d-flex gap-2 mt-2">
                                        <div class="form-check"><input class="form-check-input" type="radio"
                                                name="edit_gioiTinh" id="edit_gt1" value="1"><label
                                                class="form-check-label" for="edit_gt1">Nam</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio"
                                                name="edit_gioiTinh" id="edit_gt0" value="0"><label
                                                class="form-check-label" for="edit_gt0">Nữ</label></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Khoa</label>
                                    <select class="form-select" name="edit_idDonVi" id="edit_idKhoa">
                                        <option value="">-- Chọn --</option>
                                        <?php foreach ($khoas as $k): ?>
                                            <option value="<?= $k['idKhoa'] ?>"><?= htmlspecialchars($k['tenKhoa']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="edit-sv-section" class="d-none">
                            <h6 class="fw-bold text-info mb-3 border-bottom pb-2"><i
                                    class="bi bi-mortarboard me-1"></i>Hồ sơ sinh viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Họ và tên</label>
                                    <input type="text" class="form-control" name="edit_hoTen_sv" id="edit_hoTen_sv">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Mã sinh viên</label>
                                    <input type="text" class="form-control" name="edit_msv" id="edit_msv">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">GPA</label>
                                    <input type="number" step="0.01" min="0" max="4" class="form-control"
                                        name="edit_gpa" id="edit_gpa">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ĐRL</label>
                                    <input type="number" min="0" max="100" class="form-control" name="edit_drl"
                                        id="edit_drl">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lớp</label>
                                <select class="form-select" name="edit_idDonVi" id="edit_idLop">
                                    <option value="">-- Chọn lớp --</option>
                                    <?php foreach ($lops as $l): ?>
                                        <option value="<?= $l['idLop'] ?>"><?= htmlspecialchars($l['tenLop']) ?>
                                            (<?= htmlspecialchars($l['tenKhoa']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 mt-2 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-warning px-4"><i class="bi bi-save me-1"></i>Lưu thay
                                đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL PHÂN QUYỀN HỆ THỐNG THEO TÀI KHOẢN
     ============================================= -->
<div class="modal fade" id="permModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-3 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-info text-dark fs-6 px-3 py-2 d-inline-block mb-2">
                            <i class="bi bi-shield-lock me-1"></i>Phân quyền hệ thống
                        </span>
                        <p class="text-muted small mb-0">
                            Tài khoản: <strong id="permModalUserName"></strong>
                        </p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="" id="permForm">
                        <input type="hidden" name="action_type" value="save_system_permissions">
                        <input type="hidden" name="perm_idTK" id="perm_idTK">

                        <?php if (!empty($ht_grouped)): ?>
                            <div class="accordion" id="permAccordion">
                                <?php render_quyen_accordion($ht_grouped, 'perm', 'warning', true); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary">Chưa có quyền hệ thống nào được cấu hình.</div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCheckAllPerm">
                                    <i class="bi bi-check-all me-1"></i>Chọn tất cả
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUncheckAllPerm">
                                    <i class="bi bi-x-lg me-1"></i>Bỏ chọn tất cả
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-info text-white px-4">
                                    <i class="bi bi-save me-1"></i>Lưu quyền
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL TẠO VAI TRÒ MẪU MỚI
     ============================================= -->
<div class="modal fade" id="createGlobalRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-3 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-success fs-6 px-3 py-2 d-inline-block mb-2">
                            <i class="bi bi-person-badge me-1"></i>Tạo vai trò mẫu
                        </span>
                        <p class="text-muted small mb-0">Vai trò mẫu sẽ được sao chép vào mỗi sự kiện khi tạo.</p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="create_global_role">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên vai trò <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="g_tenVaiTro"
                                placeholder="VD: Giám khảo, Thư ký..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Mô tả</label>
                            <textarea class="form-control" name="g_moTaVaiTro" rows="2"
                                placeholder="Mô tả nhiệm vụ của vai trò..."></textarea>
                        </div>

                        <?php if (!empty($sk_grouped)): ?>
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                                <i class="bi bi-key me-1"></i>Quyền trong sự kiện
                            </h6>
                            <div class="accordion" id="createRolePermAcc">
                                <?php render_quyen_accordion($sk_grouped, 'crole', 'primary', true); ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between pt-3 mt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-plus-circle me-1"></i>Tạo vai trò
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL SỬA THÔNG TIN VAI TRÒ MẪU
     ============================================= -->
<div class="modal fade" id="editGlobalRoleInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-3 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2 d-inline-block mb-2">
                            <i class="bi bi-pencil-square me-1"></i>Sửa thông tin vai trò
                        </span>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="edit_global_role_info">
                        <input type="hidden" name="g_edit_idVaiTro" id="g_edit_idVaiTro">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên vai trò <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="g_edit_tenVaiTro" id="g_edit_tenVaiTro"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Mô tả</label>
                            <textarea class="form-control" name="g_edit_moTaVaiTro" id="g_edit_moTaVaiTro"
                                rows="2"></textarea>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-save me-1"></i>Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL SỬA QUYỀN VAI TRÒ MẪU
     ============================================= -->
<div class="modal fade" id="editGlobalRolePermModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-3 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-primary fs-6 px-3 py-2 d-inline-block mb-2">
                            <i class="bi bi-key me-1"></i>Phân quyền vai trò
                        </span>
                        <p class="text-muted small mb-0">Vai trò: <strong id="editRolePermName"></strong></p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="save_global_role_permissions">
                        <input type="hidden" name="g_perm_idVaiTro" id="g_perm_idVaiTro">

                        <?php if (!empty($sk_grouped)): ?>
                            <div class="accordion" id="editRolePermAcc">
                                <?php render_quyen_accordion($sk_grouped, 'erole', 'primary', true); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary">Chưa có quyền sự kiện nào.</div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCheckAllRolePerm">
                                    <i class="bi bi-check-all me-1"></i>Chọn tất cả
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    id="btnUncheckAllRolePerm">
                                    <i class="bi bi-x-lg me-1"></i>Bỏ chọn tất cả
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i>Lưu quyền
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<main class="main">
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Quản lý người dùng</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Trang chủ</a></li>
                    <li class="current">Người dùng</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <ul class="nav nav-tabs mb-4 border-bottom">
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab == 1 ? 'active' : '' ?>"
                        href="?module=admin&action=users&tab=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $filter_role > 0 ? '&filter_role=' . $filter_role : '' ?>">
                        <i class="bi bi-people me-1"></i>Tài khoản <span
                            class="badge bg-primary ms-1"><?= $total_u ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab == 2 ? 'active' : '' ?>" href="?module=admin&action=users&tab=2">
                        <i class="bi bi-person-badge me-1"></i>Vai trò mẫu <span
                            class="badge bg-success ms-1"><?= count($global_roles) ?></span>
                    </a>
                </li>
            </ul>

            <?php if ($active_tab == 1): ?>
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h3 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Danh sách tài khoản</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-person-plus me-1"></i>Tạo tài khoản
                    </button>
                </div>

                <div class="table-responsive shadow-sm rounded-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ và tên</th>
                                <th>Đơn vị / Lớp</th>
                                <th>Loại TK</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th class="text-center pe-3">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $idx => $u): ?>
                                <?php
                                $rc      = [1 => 'danger', 2 => 'success', 3 => 'primary'][$u['idLoaiTK']] ?? 'secondary';
                                $ri      = [1 => 'bi-shield-fill', 2 => 'bi-person-workspace', 3 => 'bi-mortarboard-fill'][$u['idLoaiTK']] ?? 'bi-person';
                                $don_vi  = !empty($u['tenLop']) ? $u['tenLop'] : $u['tenKhoa'];
                                $u_json  = htmlspecialchars(json_encode($u, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $offset + $idx + 1 ?></td>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($u['tenTK']) ?></span></td>
                                    <td>
                                        <?= htmlspecialchars($u['hoTen'] ?: '—') ?>
                                        <?php if (!empty($u['msv'])): ?><div class="text-muted small">
                                                <?= htmlspecialchars($u['msv']) ?></div><?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($don_vi ?: '—') ?></td>
                                    <td><span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?> border border-<?= $rc ?>"><i
                                                class="bi <?= $ri ?> me-1"></i><?= htmlspecialchars($u['tenLoaiTK'] ?? '—') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['isActive']): ?><span
                                                class="badge bg-success-subtle text-success border border-success"><i
                                                    class="bi bi-check-circle me-1"></i>Hoạt động</span>
                                        <?php else: ?><span class="badge bg-danger-subtle text-danger border border-danger"><i
                                                    class="bi bi-x-circle me-1"></i>Đã khóa</span><?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?= date('d/m/Y', strtotime($u['ngayTao'])) ?></td>
                                    <td class="text-center pe-3">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-warning"
                                                onclick='openEditModal(<?= $u_json ?>)' title="Chỉnh sửa"><i
                                                    class="bi bi-pencil"></i> Sửa</button>
                                            <button class="btn btn-sm btn-outline-info"
                                                onclick="openPermModal(<?= $u['idTK'] ?>,'<?= addslashes($u['tenTK']) ?>')"
                                                title="Phân quyền hệ thống"><i class="bi bi-shield-lock"></i> Quyền</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <!-- ===== TAB 2: VAI TRÒ MẪU ===== -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h3 class="fw-bold mb-0">
                        <i class="bi bi-person-badge me-2 text-success"></i>Vai trò mẫu
                    </h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createGlobalRoleModal">
                        <i class="bi bi-plus-circle me-1"></i>Tạo vai trò mẫu
                    </button>
                </div>

                <div class="alert alert-info d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <div>Vai trò mẫu sẽ được <strong>sao chép tự động</strong> vào mỗi sự kiện khi tạo. Quyền gắn vào vai
                        trò mẫu là quyền <strong>trong sự kiện</strong> (phamVi = SU_KIEN).</div>
                </div>

                <?php if (empty($global_roles)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-person-badge" style="font-size:3rem;color:#ccc;"></i>
                        <p class="mt-3 text-muted">Chưa có vai trò mẫu nào. Hãy tạo mới!</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($global_roles as $role):
                            $isUsed    = in_array((int)$role['idvatro'], $global_role_used);
                            $roleQuyen = $global_role_quyen_map[(int)$role['idvatro']] ?? [];
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0 fw-bold">
                                                <i class="bi bi-person-badge me-1 text-success"></i>
                                                <?= htmlspecialchars($role['tenvaitro']) ?>
                                            </h5>
                                            <?php if ($isUsed): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-link-45deg"></i> Đang dùng
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($role['mota'])): ?>
                                            <p class="text-muted small mb-2"><?= htmlspecialchars($role['mota']) ?></p>
                                        <?php endif; ?>
                                        <p class="mb-3">
                                            <span class="badge bg-primary-subtle text-primary border border-primary">
                                                <?= count($roleQuyen) ?> quyền
                                            </span>
                                        </p>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button class="btn btn-sm btn-outline-warning"
                                                onclick="openEditGlobalRoleInfoModal(<?= (int)$role['idvatro'] ?>,'<?= addslashes($role['tenvaitro']) ?>','<?= addslashes($role['mota'] ?? '') ?>')">
                                                <i class="bi bi-pencil me-1"></i>Sửa
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="openEditGlobalRolePermModal(<?= (int)$role['idvatro'] ?>,'<?= addslashes($role['tenvaitro']) ?>')">
                                                <i class="bi bi-key me-1"></i>Quyền
                                            </button>
                                            <?php if (!$isUsed): ?>
                                                <form method="POST" action="" class="d-inline"
                                                    id="formXoaRole-<?= (int)$role['idvatro'] ?>">
                                                    <input type="hidden" name="action_type" value="delete_global_role">
                                                    <input type="hidden" name="g_del_idVaiTro" value="<?= (int)$role['idvatro'] ?>">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="showConfirm({
                                            title      : 'Xác nhận xóa vai trò',
                                            message    : 'Thao tác này không thể hoàn tác. Bạn có chắc chắn muốn xóa vai trò này?',
                                            type       : 'danger',
                                            confirmText: 'Xóa',
                                            onConfirm  : () => document.getElementById('formXoaRole-<?= (int)$role['idvatro'] ?>').submit()
                                        })">
                                                        <i class="bi bi-trash me-1"></i>Xóa
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-danger" disabled>
                                                    <i class="bi bi-trash me-1"></i>Xóa
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php layout('footer'); ?>

<script>
    const quyenMap = <?= json_encode($quyen_map, JSON_UNESCAPED_UNICODE) ?>;
    const globalRoleQuyenMap = <?= json_encode($global_role_quyen_map, JSON_UNESCAPED_UNICODE) ?>;

    function togglePwd(id, btn) {
        const inp = document.getElementById(id);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').classList.toggle('bi-eye');
        btn.querySelector('i').classList.toggle('bi-eye-slash');
    }

    function toggleCreateForm(val) {
        document.getElementById('create-gv-section').classList.toggle('d-none', val != '2');
        document.getElementById('create-sv-section').classList.toggle('d-none', val != '3');
    }

    function toggleEditForm(val) {
        document.getElementById('edit-gv-section').classList.toggle('d-none', val != '2');
        document.getElementById('edit-sv-section').classList.toggle('d-none', val != '3');
    }

    function openEditModal(u) {
        document.getElementById('edit_idTK').value = u.idTK;
        document.getElementById('edit_tenTK_display').value = u.tenTK;
        document.getElementById('editPassword').value = '';

        // FIX LỖI ĐỔ DỮ LIỆU JS VÀO FORM HỌ TÊN
        document.getElementById('edit_hoTen_gv').value = u.hoTen;
        document.getElementById('edit_hoTen_sv').value = u.hoTen;

        document.getElementById('edit_msv').value = u.msv;
        document.getElementById('edit_gpa').value = u.gpa;
        document.getElementById('edit_drl').value = u.drl;

        const selRole = document.getElementById('edit_idLoaiTK');
        for (let o of selRole.options) o.selected = (o.value == u.idLoaiTK);
        toggleEditForm(u.idLoaiTK);

        document.getElementById('edit_gt1').checked = (u.gioiTinh == 1);
        document.getElementById('edit_gt0').checked = (u.gioiTinh != 1);

        if (u.idLoaiTK == 2) {
            for (let o of document.getElementById('edit_idKhoa').options) o.selected = (o.value == u.idKhoa);
        } else if (u.idLoaiTK == 3) {
            for (let o of document.getElementById('edit_idLop').options) o.selected = (o.value == u.idLop);
        }

        document.getElementById('edit_active1').checked = (u.isActive == 1);
        document.getElementById('edit_active0').checked = (u.isActive != 1);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal')).show();
    }

    // ── openPermModal: Mở modal phân quyền hệ thống cho 1 tài khoản ──────────
    function openPermModal(idTK, tenTK) {
        // Gán tiêu đề + hidden field
        document.getElementById('perm_idTK').value = idTK;
        document.getElementById('permModalUserName').textContent = tenTK;

        // Reset tất cả checkbox về unchecked
        document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = false);

        // Tick các quyền tài khoản đang có
        const myQuyen = quyenMap[idTK] || [];
        myQuyen.forEach(function(idQ) {
            const cb = document.getElementById('perm_' + idQ);
            if (cb) cb.checked = true;
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('permModal')).show();
    }

    // ── Chọn tất cả / Bỏ chọn tất cả trong permModal ─────────────────────────
    document.getElementById('btnCheckAllPerm')?.addEventListener('click', function() {
        document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = true);
    });
    document.getElementById('btnUncheckAllPerm')?.addEventListener('click', function() {
        document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = false);
    });

    // ── openEditGlobalRoleInfoModal: Sửa thông tin vai trò mẫu ───────────────
    function openEditGlobalRoleInfoModal(idVaiTro, tenVaiTro, moTa) {
        document.getElementById('g_edit_idVaiTro').value = idVaiTro;
        document.getElementById('g_edit_tenVaiTro').value = tenVaiTro;
        document.getElementById('g_edit_moTaVaiTro').value = moTa || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editGlobalRoleInfoModal')).show();
    }

    // ── openEditGlobalRolePermModal: Sửa quyền vai trò mẫu ──────────────────
    function openEditGlobalRolePermModal(idVaiTro, tenVaiTro) {
        document.getElementById('g_perm_idVaiTro').value = idVaiTro;
        document.getElementById('editRolePermName').textContent = tenVaiTro;

        // Reset tất cả checkbox role
        document.querySelectorAll('.erole-cb').forEach(cb => cb.checked = false);

        // Tick quyền vai trò đang có
        const myQuyen = globalRoleQuyenMap[idVaiTro] || [];
        myQuyen.forEach(function(idQ) {
            const cb = document.getElementById('erole_' + idQ);
            if (cb) cb.checked = true;
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editGlobalRolePermModal')).show();
    }

    // ── Chọn tất cả / Bỏ chọn tất cả trong editGlobalRolePermModal ──────────
    document.getElementById('btnCheckAllRolePerm')?.addEventListener('click', function() {
        document.querySelectorAll('.erole-cb').forEach(cb => cb.checked = true);
    });
    document.getElementById('btnUncheckAllRolePerm')?.addEventListener('click', function() {
        document.querySelectorAll('.erole-cb').forEach(cb => cb.checked = false);
    });
</script>
