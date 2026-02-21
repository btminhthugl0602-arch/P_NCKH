<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// =============================================
// XỬ LÝ CÁC ACTION (POST)
// =============================================
$success_msg = '';
$error_msg   = '';

// --- Tạo tài khoản ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'create_user') {
    $tenTK    = trim($_POST['tenTK'] ?? '');
    $matKhau  = trim($_POST['matKhau'] ?? '');
    $idLoaiTK = (int)($_POST['idLoaiTK'] ?? 0);
    $hoTen    = trim($_POST['hoTen'] ?? '');
    $idDonVi  = (int)($_POST['idDonVi'] ?? 0);
    $msv      = trim($_POST['msv'] ?? '');

    if (empty($tenTK) || empty($matKhau) || $idLoaiTK === 0 || empty($hoTen)) {
        $error_msg = 'Vui lòng điền đầy đủ các trường bắt buộc.';
    } else {
        $chk_sql = "SELECT idTK FROM taikhoan WHERE tenTK = '" . mysqli_real_escape_string($conn, $tenTK) . "' LIMIT 1";
        $chk_res = mysqli_query($conn, $chk_sql);
        if ($chk_res && mysqli_num_rows($chk_res) > 0) {
            $error_msg = 'Tên đăng nhập đã tồn tại trong hệ thống.';
        } else {
            $mat_khau_ma_hoa = password_hash($matKhau, PASSWORD_DEFAULT);
            $ins_tk = "INSERT INTO taikhoan (tenTK, matKhau, idLoaiTK, isActive) VALUES (
                '" . mysqli_real_escape_string($conn, $tenTK) . "',
                '" . mysqli_real_escape_string($conn, $mat_khau_ma_hoa) . "',
                $idLoaiTK, 1)";
            if (mysqli_query($conn, $ins_tk)) {
                $new_id = mysqli_insert_id($conn);
                if ($idLoaiTK == 2) {
                    $ins_gv = "INSERT INTO giangvien (idTK, tenGV, idKhoa) VALUES ($new_id,
                        '" . mysqli_real_escape_string($conn, $hoTen) . "',
                        " . ($idDonVi > 0 ? $idDonVi : 'NULL') . ")";
                    mysqli_query($conn, $ins_gv);
                } elseif ($idLoaiTK == 3) {
                    $lop_r = ($idDonVi > 0) ? mysqli_query($conn, "SELECT idKhoa FROM lop WHERE idLop = $idDonVi LIMIT 1") : false;
                    $lop_row = $lop_r ? mysqli_fetch_assoc($lop_r) : null;
                    $id_khoa_sv = $lop_row ? $lop_row['idKhoa'] : 'NULL';
                    $ins_sv = "INSERT INTO sinhvien (idTK, tenSV, MSV, idLop, idKhoa) VALUES ($new_id,
                        '" . mysqli_real_escape_string($conn, $hoTen) . "',
                        '" . mysqli_real_escape_string($conn, $msv) . "',
                        " . ($idDonVi > 0 ? $idDonVi : 'NULL') . ",
                        " . ($id_khoa_sv !== 'NULL' ? $id_khoa_sv : 'NULL') . ")";
                    mysqli_query($conn, $ins_sv);
                }
                $success_msg = 'Tạo tài khoản <strong>' . htmlspecialchars($tenTK) . '</strong> thành công!';
            } else {
                $error_msg = 'Lỗi khi tạo tài khoản: ' . mysqli_error($conn);
            }
        }
    }
}

// --- Chỉnh sửa tài khoản ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'edit_user') {
    $idTK     = (int)($_POST['edit_idTK'] ?? 0);
    $idLoaiTK = (int)($_POST['edit_idLoaiTK'] ?? 0);
    $isActive = (int)($_POST['edit_isActive'] ?? 1);
    $hoTen    = trim($_POST['edit_hoTen'] ?? '');
    $matKhau  = trim($_POST['edit_matKhau'] ?? '');
    $idDonVi  = (int)($_POST['edit_idDonVi'] ?? 0);
    $msv      = trim($_POST['edit_msv'] ?? '');

    if ($idTK <= 0) {
        $error_msg = 'Tài khoản không hợp lệ.';
    } else {
        $set_parts = ["idLoaiTK = $idLoaiTK", "isActive = $isActive"];
        if (!empty($matKhau)) {
            $new_hash = password_hash($matKhau, PASSWORD_DEFAULT);
            $set_parts[] = "matKhau = '" . mysqli_real_escape_string($conn, $new_hash) . "'";
        }
        $upd_tk = "UPDATE taikhoan SET " . implode(', ', $set_parts) . " WHERE idTK = $idTK";
        mysqli_query($conn, $upd_tk);

        if ($idLoaiTK == 2) {
            $chk = mysqli_query($conn, "SELECT idGV FROM giangvien WHERE idTK = $idTK LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) {
                mysqli_query($conn, "UPDATE giangvien SET tenGV = '" . mysqli_real_escape_string($conn, $hoTen) . "'"
                    . ($idDonVi > 0 ? ", idKhoa = $idDonVi" : '') . " WHERE idTK = $idTK");
            } else {
                mysqli_query($conn, "INSERT INTO giangvien (idTK, tenGV, idKhoa) VALUES ($idTK,
                    '" . mysqli_real_escape_string($conn, $hoTen) . "',
                    " . ($idDonVi > 0 ? $idDonVi : 'NULL') . ")");
            }
        } elseif ($idLoaiTK == 3) {
            $lop_r = ($idDonVi > 0) ? mysqli_query($conn, "SELECT idKhoa FROM lop WHERE idLop = $idDonVi LIMIT 1") : false;
            $lop_row = $lop_r ? mysqli_fetch_assoc($lop_r) : null;
            $id_khoa_sv = $lop_row ? $lop_row['idKhoa'] : 'NULL';
            $chk = mysqli_query($conn, "SELECT idSV FROM sinhvien WHERE idTK = $idTK LIMIT 1");
            if ($chk && mysqli_num_rows($chk) > 0) {
                mysqli_query($conn, "UPDATE sinhvien SET tenSV = '" . mysqli_real_escape_string($conn, $hoTen) . "'"
                    . (!empty($msv) ? ", MSV = '" . mysqli_real_escape_string($conn, $msv) . "'" : '')
                    . ($idDonVi > 0 ? ", idLop = $idDonVi, idKhoa = $id_khoa_sv" : '')
                    . " WHERE idTK = $idTK");
            } else {
                mysqli_query($conn, "INSERT INTO sinhvien (idTK, tenSV, MSV, idLop, idKhoa) VALUES ($idTK,
                    '" . mysqli_real_escape_string($conn, $hoTen) . "',
                    '" . mysqli_real_escape_string($conn, $msv) . "',
                    " . ($idDonVi > 0 ? $idDonVi : 'NULL') . ",
                    $id_khoa_sv)");
            }
        }
        $success_msg = 'Cập nhật tài khoản thành công!';
    }
}

// --- Phân quyền ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'save_permissions') {
    $idTK = (int)($_POST['perm_idTK'] ?? 0);
    $checked_quyens = (isset($_POST['quyen_ids']) && is_array($_POST['quyen_ids'])) ? $_POST['quyen_ids'] : [];

    if ($idTK <= 0) {
        $error_msg = 'Tài khoản không hợp lệ.';
    } else {
        $all_q_res = mysqli_query($conn, "SELECT idQuyen FROM quyen");
        $all_quyens = [];
        while ($r = mysqli_fetch_assoc($all_q_res)) $all_quyens[] = (int)$r['idQuyen'];

        foreach ($all_quyens as $idQuyen) {
            $is_checked = in_array((string)$idQuyen, $checked_quyens);
            $ex_res  = mysqli_query($conn, "SELECT idTK FROM taikhoan_quyen WHERE idTK = $idTK AND idQuyen = $idQuyen LIMIT 1");
            $exists  = ($ex_res && mysqli_num_rows($ex_res) > 0);

            if ($is_checked && !$exists) {
                mysqli_query($conn, "INSERT INTO taikhoan_quyen (idTK, idQuyen, isActive, thoiGianBatDau)
                    VALUES ($idTK, $idQuyen, 1, NOW())");
            } elseif ($is_checked && $exists) {
                mysqli_query($conn, "UPDATE taikhoan_quyen SET isActive = 1, thoiGianKetThuc = NULL
                    WHERE idTK = $idTK AND idQuyen = $idQuyen");
            } elseif (!$is_checked && $exists) {
                mysqli_query($conn, "DELETE FROM taikhoan_quyen WHERE idTK = $idTK AND idQuyen = $idQuyen");
            }
        }
        $success_msg = 'Cập nhật phân quyền thành công!';
    }
}

// =============================================
// TRUY VẤN DỮ LIỆU
// =============================================
$search      = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filter_role = isset($_GET['filter_role']) ? (int)$_GET['filter_role'] : 0;

// ---- Pagination ----
$per_page_u   = 10;
$cur_page_u   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$where_u = "WHERE 1=1";
if (!empty($search))   $where_u .= " AND (tk.tenTK LIKE '%$search%' OR sv.tenSV LIKE '%$search%' OR gv.tenGV LIKE '%$search%')";
if ($filter_role > 0)  $where_u .= " AND tk.idLoaiTK = $filter_role";

$cnt_u_res    = mysqli_query($conn, "SELECT COUNT(*) as c FROM taikhoan tk LEFT JOIN sinhvien sv ON tk.idTK=sv.idTK LEFT JOIN giangvien gv ON tk.idTK=gv.idTK $where_u");
$total_u      = (int)mysqli_fetch_assoc($cnt_u_res)['c'];
$total_pages_u = max(1, ceil($total_u / $per_page_u));
$cur_page_u   = min($cur_page_u, $total_pages_u);
$offset_u     = ($cur_page_u - 1) * $per_page_u;

$sql_users = "
    SELECT
        tk.idTK, tk.tenTK, tk.idLoaiTK, tk.isActive, tk.ngayTao,
        ltk.tenLoaiTK,
        COALESCE(sv.tenSV, gv.tenGV, '') AS hoTen,
        COALESCE(sv.MSV, '')              AS msv,
        COALESCE(lp.tenLop, '')           AS tenLop,
        COALESCE(kh1.tenKhoa, kh2.tenKhoa, '') AS tenKhoa,
        COALESCE(sv.idLop, 0)             AS idLop,
        COALESCE(gv.idKhoa, kh1.idKhoa, 0) AS idKhoa
    FROM taikhoan tk
    LEFT JOIN loaitaikhoan ltk ON tk.idLoaiTK = ltk.idLoaiTK
    LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
    LEFT JOIN lop lp ON sv.idLop = lp.idLop
    LEFT JOIN khoa kh1 ON sv.idKhoa = kh1.idKhoa
    LEFT JOIN khoa kh2 ON gv.idKhoa = kh2.idKhoa
    $where_u ORDER BY tk.idTK ASC
    LIMIT $per_page_u OFFSET $offset_u
";

$result_users = mysqli_query($conn, $sql_users);
$users = [];
while ($row = mysqli_fetch_assoc($result_users)) $users[] = $row;

function user_page_url($page, $search, $filter_role)
{
    $p = ['module' => 'admin', 'action' => 'users', 'page' => $page];
    if (!empty($search))   $p['search']      = $search;
    if ($filter_role > 0)  $p['filter_role'] = $filter_role;
    return '?' . http_build_query($p);
}

$loai_tk_res = mysqli_query($conn, "SELECT * FROM loaitaikhoan ORDER BY idLoaiTK");
$loai_tks = [];
while ($r = mysqli_fetch_assoc($loai_tk_res)) $loai_tks[] = $r;

$khoa_res = mysqli_query($conn, "SELECT * FROM khoa ORDER BY tenKhoa");
$khoas = [];
while ($r = mysqli_fetch_assoc($khoa_res)) $khoas[] = $r;

$lop_res = mysqli_query($conn, "SELECT lp.*, kh.tenKhoa FROM lop lp LEFT JOIN khoa kh ON lp.idKhoa = kh.idKhoa ORDER BY lp.tenLop");
$lops = [];
while ($r = mysqli_fetch_assoc($lop_res)) $lops[] = $r;

$quyen_res = mysqli_query($conn, "SELECT * FROM quyen ORDER BY idQuyen");
$quyens = [];
while ($r = mysqli_fetch_assoc($quyen_res)) $quyens[] = $r;

$quyen_map = [];
$tq_res = mysqli_query($conn, "SELECT idTK, idQuyen FROM taikhoan_quyen");
while ($r = mysqli_fetch_assoc($tq_res)) {
    $quyen_map[(int)$r['idTK']][] = (int)$r['idQuyen'];
}

$data = ['page_title' => 'Quản lý người dùng'];
layout('header', $data);
layout('navbar');
?>

<!-- ======= MODAL TẠO TÀI KHOẢN ======= -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <div class="mb-2">
                            <span class="badge bg-primary fs-6 px-3 py-2">
                                <i class="bi bi-person-plus me-1"></i> Tạo tài khoản mới
                            </span>
                        </div>
                        <p class="text-muted">Điền đầy đủ thông tin bên dưới để tạo tài khoản trong hệ thống.</p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="create_user">
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
                                        placeholder="Ít nhất 6 ký tự" required>
                                    <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePwd('createPassword',this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Họ và tên <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="hoTen" placeholder="Nhập họ và tên..."
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vai trò <span class="text-danger">*</span></label>
                                <select class="form-select" name="idLoaiTK" id="createLoaiTK"
                                    onchange="toggleCreateForm(this.value)" required>
                                    <option value="">-- Chọn vai trò --</option>
                                    <?php foreach ($loai_tks as $ltk): ?>
                                        <option value="<?= $ltk['idLoaiTK'] ?>"><?= htmlspecialchars($ltk['tenLoaiTK']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="create-gv-section" class="row mb-3 d-none">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Khoa</label>
                                <select class="form-select" name="idDonVi">
                                    <option value="">-- Chọn khoa --</option>
                                    <?php foreach ($khoas as $k): ?>
                                        <option value="<?= $k['idKhoa'] ?>"><?= htmlspecialchars($k['tenKhoa']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="create-sv-section" class="d-none">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Mã sinh viên</label>
                                    <input type="text" class="form-control" name="msv" placeholder="VD: SV001">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Lớp</label>
                                    <select class="form-select" name="idDonVi">
                                        <option value="">-- Chọn lớp --</option>
                                        <?php foreach ($lops as $l): ?>
                                            <option value="<?= $l['idLop'] ?>"><?= htmlspecialchars($l['tenLop']) ?>
                                                (<?= htmlspecialchars($l['tenKhoa']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle me-1"></i> Tạo tài khoản
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======= MODAL CHỈNH SỬA ======= -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <div class="mb-2">
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa tài khoản
                            </span>
                        </div>
                        <p class="text-muted">Cập nhật thông tin tài khoản. Bỏ trống mật khẩu nếu không muốn thay đổi.
                        </p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="edit_user">
                        <input type="hidden" name="edit_idTK" id="edit_idTK">
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
                                <label class="form-label fw-semibold">Họ và tên <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="edit_hoTen" id="edit_hoTen" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vai trò <span class="text-danger">*</span></label>
                                <select class="form-select" name="edit_idLoaiTK" id="edit_idLoaiTK"
                                    onchange="toggleEditForm(this.value)">
                                    <?php foreach ($loai_tks as $ltk): ?>
                                        <option value="<?= $ltk['idLoaiTK'] ?>"><?= htmlspecialchars($ltk['tenLoaiTK']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="edit-gv-section" class="row mb-3 d-none">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Khoa</label>
                                <select class="form-select" name="edit_idDonVi" id="edit_idKhoa">
                                    <option value="">-- Chọn khoa --</option>
                                    <?php foreach ($khoas as $k): ?>
                                        <option value="<?= $k['idKhoa'] ?>"><?= htmlspecialchars($k['tenKhoa']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="edit-sv-section" class="d-none">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Mã sinh viên</label>
                                    <input type="text" class="form-control" name="edit_msv" id="edit_msv">
                                </div>
                                <div class="col-md-6">
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
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Trạng thái tài khoản</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="edit_isActive"
                                            id="edit_active1" value="1">
                                        <label class="form-check-label" for="edit_active1"><i
                                                class="bi bi-unlock text-success me-1"></i>Hoạt động</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="edit_isActive"
                                            id="edit_active0" value="0">
                                        <label class="form-check-label" for="edit_active0"><i
                                                class="bi bi-lock text-danger me-1"></i>Bị khóa</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-save me-1"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======= MODAL PHÂN QUYỀN ======= -->
<div class="modal fade" id="permModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <div class="mb-2">
                            <span class="badge bg-info text-dark fs-6 px-3 py-2">
                                <i class="bi bi-shield-lock me-1"></i> Phân quyền tài khoản
                            </span>
                        </div>
                        <p id="perm_user_name" class="fw-semibold text-primary mb-1"></p>
                        <small class="text-muted">Tick chọn quyền muốn cấp, bỏ tick để thu hồi.</small>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="save_permissions">
                        <input type="hidden" name="perm_idTK" id="perm_idTK">
                        <div class="mb-4">
                            <?php foreach ($quyens as $q): ?>
                                <div class="card mb-2 border-0 bg-light rounded-3">
                                    <div class="card-body py-2 px-3 d-flex align-items-start gap-3">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input perm-checkbox" type="checkbox" name="quyen_ids[]"
                                                value="<?= $q['idQuyen'] ?>" id="quyen_<?= $q['idQuyen'] ?>"
                                                data-idquyen="<?= $q['idQuyen'] ?>">
                                        </div>
                                        <div>
                                            <label class="form-check-label fw-semibold mb-0"
                                                for="quyen_<?= $q['idQuyen'] ?>">
                                                <?= htmlspecialchars($q['tenQuyen']) ?>
                                            </label>
                                            <div class="text-muted small"><?= htmlspecialchars($q['moTa'] ?? '') ?></div>
                                            <span
                                                class="badge bg-secondary mt-1"><?= htmlspecialchars($q['maQuyen']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-info text-white px-4">
                                <i class="bi bi-shield-check me-1"></i> Lưu phân quyền
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const quyenMap = <?= json_encode($quyen_map, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- Toast thông báo -->
<?php if (!empty($success_msg)): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
        <div class="toast show align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check-circle me-2"></i><?= $success_msg ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
        <div class="toast show align-items-center text-bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-exclamation-circle me-2"></i><?= $error_msg ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<main class="main">

    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Quản lý người dùng</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo _HOST_URL; ?>">Trang chủ</a></li>
                    <li class="current">Người dùng</li>
                </ol>
            </nav>
        </div>
    </div>

    <section id="instructors" class="instructors section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="fw-bold mb-0">
                    <i class="bi bi-people me-2 text-primary"></i>Danh sách tài khoản
                    <span class="badge bg-primary ms-2"><?= count($users) ?></span>
                </h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus me-1"></i> Tạo tài khoản
                </button>
            </div>

            <!-- Tìm kiếm & lọc -->
            <div class="row mb-4 g-2">
                <div class="col-md-7">
                    <form method="GET" action="" class="d-flex gap-2">
                        <?php if ($filter_role > 0): ?><input type="hidden" name="filter_role"
                                value="<?= $filter_role ?>"><?php endif; ?>
                        <input type="text" class="form-control" name="search"
                            placeholder="Tìm theo tên đăng nhập hoặc họ tên..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        <?php if (!empty($search)): ?>
                            <a href="?<?= $filter_role > 0 ? "filter_role=$filter_role" : '' ?>"
                                class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-md-5">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="?<?= !empty($search) ? "search=" . urlencode($search) : '' ?>"
                            class="btn btn-sm <?= $filter_role == 0 ? 'btn-primary' : 'btn-outline-primary' ?>">Tất
                            cả</a>
                        <?php foreach ($loai_tks as $ltk): ?>
                            <a href="?filter_role=<?= $ltk['idLoaiTK'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                class="btn btn-sm <?= $filter_role == $ltk['idLoaiTK'] ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <?= htmlspecialchars($ltk['tenLoaiTK']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Bảng danh sách -->
            <div class="table-responsive shadow-sm rounded-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Tên đăng nhập</th>
                            <th>Họ và tên</th>
                            <th>Đơn vị / Lớp</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th class="text-center pe-3">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>Không tìm thấy tài khoản nào.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $idx => $u): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                                style="width:36px;height:36px;flex-shrink:0">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                            <span class="fw-semibold"><?= htmlspecialchars($u['tenTK']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($u['hoTen'] ?: '—') ?>
                                        <?php if (!empty($u['msv'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($u['msv']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $don_vi = !empty($u['tenLop']) ? $u['tenLop'] : $u['tenKhoa']; ?>
                                        <?php if (!empty($don_vi)): ?>
                                            <span class="badge bg-light text-dark border"><i
                                                    class="bi bi-building me-1"></i><?= htmlspecialchars($don_vi) ?></span>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $rc = [1 => 'danger', 2 => 'success', 3 => 'primary'][$u['idLoaiTK']] ?? 'secondary';
                                        $ri = [1 => 'bi-shield-fill', 2 => 'bi-person-workspace', 3 => 'bi-mortarboard-fill'][$u['idLoaiTK']] ?? 'bi-person';
                                        ?>
                                        <span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?> border border-<?= $rc ?>">
                                            <i class="bi <?= $ri ?> me-1"></i><?= htmlspecialchars($u['tenLoaiTK'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['isActive']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success"><i
                                                    class="bi bi-check-circle me-1"></i>Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger"><i
                                                    class="bi bi-x-circle me-1"></i>Đã khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?= date('d/m/Y', strtotime($u['ngayTao'])) ?></td>
                                    <td class="text-center pe-3">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-warning"
                                                onclick="openEditModal(<?= $u['idTK'] ?>,'<?= addslashes($u['tenTK']) ?>','<?= addslashes($u['hoTen']) ?>',<?= $u['idLoaiTK'] ?>,<?= $u['isActive'] ?>,<?= $u['idLop'] ?: 0 ?>,<?= $u['idKhoa'] ?: 0 ?>,'<?= addslashes($u['msv']) ?>')"
                                                title="Chỉnh sửa tài khoản">
                                                <i class="bi bi-pencil"></i> Sửa
                                            </button>
                                            <button class="btn btn-sm btn-outline-info"
                                                onclick="openPermModal(<?= $u['idTK'] ?>,'<?= addslashes($u['tenTK']) ?>')"
                                                title="Phân quyền">
                                                <i class="bi bi-shield-lock"></i> Quyền
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages_u > 1): ?>
                <div class="pagination-wrapper mt-4" data-aos="fade-up" data-aos-delay="300">
                    <nav aria-label="Users pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $cur_page_u <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= user_page_url($cur_page_u - 1, $search, $filter_role) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                            $start_p = max(1, $cur_page_u - 2);
                            $end_p   = min($total_pages_u, $cur_page_u + 2);
                            if ($start_p > 1): ?>
                                <li class="page-item"><a class="page-link"
                                        href="<?= user_page_url(1, $search, $filter_role) ?>">1</a></li>
                                <?php if ($start_p > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($p = $start_p; $p <= $end_p; $p++): ?>
                                <li class="page-item <?= $p == $cur_page_u ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= user_page_url($p, $search, $filter_role) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($end_p < $total_pages_u): ?>
                                <?php if ($end_p < $total_pages_u - 1): ?><li class="page-item disabled"><span
                                            class="page-link">…</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link"
                                        href="<?= user_page_url($total_pages_u, $search, $filter_role) ?>"><?= $total_pages_u ?></a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item <?= $cur_page_u >= $total_pages_u ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= user_page_url($cur_page_u + 1, $search, $filter_role) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <p class="text-center text-muted small mt-2">
                        Trang <?= $cur_page_u ?> / <?= $total_pages_u ?> &nbsp;·&nbsp; <?= $total_u ?> tài khoản
                    </p>
                </div>
            <?php endif; ?>

            <?php if (!empty($users)): ?>
                <div class="mt-2 text-muted small">
                    Hiển thị <?= count($users) ?> / <strong><?= $total_u ?></strong> tài khoản
                    <?= !empty($search) ? ' · Tìm kiếm: <strong>' . htmlspecialchars($search) . '</strong>' : '' ?>
                </div>
            <?php endif; ?>

        </div>
    </section>
</main>

<?php layout('footer'); ?>

<script>
    function togglePwd(id, btn) {
        const inp = document.getElementById(id);
        const ic = btn.querySelector('i');
        inp.type = inp.type === 'password' ? 'text' : 'password';
        ic.classList.toggle('bi-eye');
        ic.classList.toggle('bi-eye-slash');
    }

    function toggleCreateForm(val) {
        document.getElementById('create-gv-section').classList.toggle('d-none', val != '2');
        document.getElementById('create-sv-section').classList.toggle('d-none', val != '3');
    }

    function toggleEditForm(val) {
        document.getElementById('edit-gv-section').classList.toggle('d-none', val != '2');
        document.getElementById('edit-sv-section').classList.toggle('d-none', val != '3');
    }

    function openEditModal(idTK, tenTK, hoTen, idLoaiTK, isActive, idLop, idKhoa, msv) {
        document.getElementById('edit_idTK').value = idTK;
        document.getElementById('edit_tenTK_display').value = tenTK;
        document.getElementById('edit_hoTen').value = hoTen;
        document.getElementById('edit_msv').value = msv;
        document.getElementById('editPassword').value = '';
        const selRole = document.getElementById('edit_idLoaiTK');
        for (let o of selRole.options) o.selected = (o.value == idLoaiTK);
        toggleEditForm(idLoaiTK);
        if (idLoaiTK == 3) {
            for (let o of document.getElementById('edit_idLop').options) o.selected = (o.value == idLop);
        } else if (idLoaiTK == 2) {
            for (let o of document.getElementById('edit_idKhoa').options) o.selected = (o.value == idKhoa);
        }
        document.getElementById('edit_active1').checked = (isActive == 1);
        document.getElementById('edit_active0').checked = (isActive == 0);
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }

    function openPermModal(idTK, tenTK) {
        document.getElementById('perm_idTK').value = idTK;
        document.getElementById('perm_user_name').textContent = 'Tài khoản: ' + tenTK;
        const userQuyens = quyenMap[idTK] || [];
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            cb.checked = userQuyens.includes(parseInt(cb.dataset.idquyen));
        });
        new bootstrap.Modal(document.getElementById('permModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toast.show').forEach(t => setTimeout(() => t.classList.remove('show'), 4000));
    });
</script>