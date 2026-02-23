<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_tai_khoan.php';

$success_msg = '';
$error_msg   = '';
$active_tab  = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;
// Alias: tab 2 cũ (vaitro_sukien theo SK) không còn dùng
// Tab 2 mới = Vai trò mẫu (vaitro + vaitro_quyen)

// =============================================
// XỬ LÝ POST
// =============================================
if (isPost()) {
    $data        = filter();
    $action_type = $data['action_type'] ?? '';

    // ---- 1. TẠO TÀI KHOẢN ----
    if ($action_type === 'create_user') {
        $tenTK    = trim($_POST['tenTK'] ?? '');
        $matKhau  = trim($_POST['matKhau'] ?? '');
        $idLoaiTK = (int)($_POST['idLoaiTK'] ?? 0);
        $hoTen    = trim($_POST['hoTen'] ?? '');
        $idDonVi  = (int)($_POST['idDonVi'] ?? 0);
        $msv      = trim($_POST['msv'] ?? '');
        $gpa      = trim($_POST['gpa'] ?? '');
        $drl      = trim($_POST['drl'] ?? '');
        $gioiTinh = (int)($_POST['gioiTinh'] ?? 0);
        $isActive = (int)($_POST['isActive'] ?? 1);

        if (empty($tenTK) || empty($matKhau) || $idLoaiTK === 0) {
            $error_msg = 'Vui lòng điền đầy đủ các trường bắt buộc.';
        } elseif (kiem_tra_ton_tai_ban_ghi($conn, 'taikhoan', 'tenTK', $tenTK)) {
            $error_msg = 'Tên đăng nhập đã tồn tại trong hệ thống.';
        } else {
            mysqli_begin_transaction($conn);
            try {
                // Lưu mật khẩu PLAINTEXT (login.php dùng == so sánh trực tiếp)
                $ok = _insert_info(
                    $conn,
                    'taikhoan',
                    ['tenTK', 'matKhau', 'idLoaiTK', 'isActive'],
                    [$tenTK, $matKhau, $idLoaiTK, $isActive]
                );
                if (!$ok) throw new Exception('Lỗi khi tạo tài khoản: ' . mysqli_error($conn));

                $new_id = mysqli_insert_id($conn);

                if ($idLoaiTK == 2 && !empty($hoTen)) {
                    _insert_info(
                        $conn,
                        'giangvien',
                        ['idTK', 'tenGV', 'idKhoa', 'gioiTinh'],
                        [$new_id, $hoTen, $idDonVi > 0 ? $idDonVi : null, $gioiTinh]
                    );
                } elseif ($idLoaiTK == 3 && !empty($hoTen)) {
                    $idKhoaSV = null;
                    if ($idDonVi > 0) {
                        $lop = truy_van_mot_ban_ghi($conn, 'lop', 'idLop', $idDonVi);
                        $idKhoaSV = $lop ? $lop['idKhoa'] : null;
                    }
                    _insert_info(
                        $conn,
                        'sinhvien',
                        ['idTK', 'tenSV', 'MSV', 'GPA', 'DRL', 'idLop', 'idKhoa'],
                        [
                            $new_id,
                            $hoTen,
                            $msv,
                            is_numeric($gpa) ? (float)$gpa : 0.00,
                            is_numeric($drl) ? (int)$drl : 0,
                            $idDonVi > 0 ? $idDonVi : null,
                            $idKhoaSV
                        ]
                    );
                }

                // Gán quyền hệ thống nếu có chọn
                if (!empty($_POST['quyen_ids']) && is_array($_POST['quyen_ids'])) {
                    $ht_res = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE phamVi='HE_THONG'");
                    $ht_ids = array_column(mysqli_fetch_all($ht_res, MYSQLI_ASSOC), 'idQuyen');
                    foreach ($_POST['quyen_ids'] as $qid) {
                        $qid = (int)$qid;
                        if (in_array($qid, $ht_ids)) {
                            _insert_info(
                                $conn,
                                'taikhoan_quyen',
                                ['idTK', 'idQuyen', 'isActive', 'thoiGianBatDau'],
                                [$new_id, $qid, 1, date('Y-m-d H:i:s')]
                            );
                        }
                    }
                }

                mysqli_commit($conn);
                $success_msg = 'Tạo tài khoản <strong>' . htmlspecialchars($tenTK) . '</strong> thành công!';
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error_msg = $e->getMessage();
            }
        }
    }

    // ---- 2. CẬP NHẬT TÀI KHOẢN ----
    elseif ($action_type === 'edit_user') {
        $idTK     = (int)($_POST['edit_idTK'] ?? 0);
        $idLoaiTK = (int)($_POST['edit_idLoaiTK'] ?? 0);
        $isActive = (int)($_POST['edit_isActive'] ?? 1);
        $hoTen    = trim($_POST['edit_hoTen'] ?? '');
        $matKhau  = trim($_POST['edit_matKhau'] ?? '');
        $idDonVi  = (int)($_POST['edit_idDonVi'] ?? 0);
        $msv      = trim($_POST['edit_msv'] ?? '');
        $gpa      = trim($_POST['edit_gpa'] ?? '');
        $drl      = trim($_POST['edit_drl'] ?? '');
        $gioiTinh = (int)($_POST['edit_gioiTinh'] ?? 0);

        if ($idTK <= 0) {
            $error_msg = 'Tài khoản không hợp lệ.';
        } else {
            $fields = ['idLoaiTK', 'isActive'];
            $values = [$idLoaiTK, $isActive];
            if (!empty($matKhau)) {
                // Lưu PLAINTEXT - login.php dùng == so sánh trực tiếp
                $fields[] = 'matKhau';
                $values[] = $matKhau;
            }
            _update_info($conn, 'taikhoan', $fields, $values, ['idTK' => ['=', $idTK, '']]);

            if ($idLoaiTK == 2) {
                $chk = truy_van_mot_ban_ghi($conn, 'giangvien', 'idTK', $idTK);
                if ($chk) {
                    $gv_f = ['tenGV', 'gioiTinh'];
                    $gv_v = [$hoTen, $gioiTinh];
                    if ($idDonVi > 0) {
                        $gv_f[] = 'idKhoa';
                        $gv_v[] = $idDonVi;
                    }
                    _update_info($conn, 'giangvien', $gv_f, $gv_v, ['idTK' => ['=', $idTK, '']]);
                } else {
                    _insert_info(
                        $conn,
                        'giangvien',
                        ['idTK', 'tenGV', 'idKhoa', 'gioiTinh'],
                        [$idTK, $hoTen, $idDonVi > 0 ? $idDonVi : null, $gioiTinh]
                    );
                }
            } elseif ($idLoaiTK == 3) {
                $idKhoaSV = null;
                if ($idDonVi > 0) {
                    $lop      = truy_van_mot_ban_ghi($conn, 'lop', 'idLop', $idDonVi);
                    $idKhoaSV = $lop ? $lop['idKhoa'] : null;
                }
                $chk = truy_van_mot_ban_ghi($conn, 'sinhvien', 'idTK', $idTK);
                if ($chk) {
                    $sv_f = ['tenSV'];
                    $sv_v = [$hoTen];
                    if (!empty($msv)) {
                        $sv_f[] = 'MSV';
                        $sv_v[] = $msv;
                    }
                    if (is_numeric($gpa)) {
                        $sv_f[] = 'GPA';
                        $sv_v[] = (float)$gpa;
                    }
                    if (is_numeric($drl)) {
                        $sv_f[] = 'DRL';
                        $sv_v[] = (int)$drl;
                    }
                    if ($idDonVi > 0) {
                        $sv_f[] = 'idLop';
                        $sv_v[] = $idDonVi;
                        $sv_f[] = 'idKhoa';
                        $sv_v[] = $idKhoaSV;
                    }
                    _update_info($conn, 'sinhvien', $sv_f, $sv_v, ['idTK' => ['=', $idTK, '']]);
                } else {
                    _insert_info(
                        $conn,
                        'sinhvien',
                        ['idTK', 'tenSV', 'MSV', 'GPA', 'DRL', 'idLop', 'idKhoa'],
                        [
                            $idTK,
                            $hoTen,
                            $msv,
                            is_numeric($gpa) ? (float)$gpa : 0,
                            is_numeric($drl) ? (int)$drl : 0,
                            $idDonVi > 0 ? $idDonVi : null,
                            $idKhoaSV
                        ]
                    );
                }
            }
            $success_msg = 'Cập nhật tài khoản thành công!';
        }
    }

    // ---- 3. PHÂN QUYỀN HỆ THỐNG ----
    elseif ($action_type === 'save_system_permissions') {
        $idTK    = (int)($_POST['perm_idTK'] ?? 0);
        $checked = (isset($_POST['quyen_ids']) && is_array($_POST['quyen_ids']))
            ? array_map('intval', $_POST['quyen_ids']) : [];

        if ($idTK <= 0) {
            $error_msg = 'Tài khoản không hợp lệ.';
        } else {
            $ht_res = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE phamVi='HE_THONG'");
            $ht_ids = array_column(mysqli_fetch_all($ht_res, MYSQLI_ASSOC), 'idQuyen');

            foreach ($ht_ids as $idQ) {
                $ex     = mysqli_query($conn, "SELECT idTK FROM taikhoan_quyen WHERE idTK=$idTK AND idQuyen=$idQ LIMIT 1");
                $exists = ($ex && mysqli_num_rows($ex) > 0);
                if (in_array($idQ, $checked) && !$exists) {
                    _insert_info(
                        $conn,
                        'taikhoan_quyen',
                        ['idTK', 'idQuyen', 'isActive', 'thoiGianBatDau'],
                        [$idTK, $idQ, 1, date('Y-m-d H:i:s')]
                    );
                } elseif (!in_array($idQ, $checked) && $exists) {
                    _delete_info($conn, 'taikhoan_quyen', [
                        'idTK'    => ['=', $idTK, 'AND'],
                        'idQuyen' => ['=', $idQ,  '']
                    ]);
                }
            }
            $success_msg = 'Cập nhật quyền hệ thống thành công!';
        }
    }

    // ---- 4. TẠO VAI TRÒ MẪU (vaitro - áp dụng chung mọi sự kiện) ----
    elseif ($action_type === 'create_global_role') {
        $tenVT  = trim($_POST['g_tenVaiTro'] ?? '');
        $moTaVT = trim($_POST['g_moTaVaiTro'] ?? '');
        $quyenSK = (isset($_POST['g_quyen_ids']) && is_array($_POST['g_quyen_ids']))
            ? array_map('intval', $_POST['g_quyen_ids']) : [];

        if (empty($tenVT)) {
            $error_msg = 'Vui lòng nhập tên vai trò.';
        } else {
            $ok = _insert_info(
                $conn,
                'vaitro',
                ['tenvaitro', 'mota'],
                [$tenVT, $moTaVT ?: null]
            );
            if ($ok) {
                $newId  = mysqli_insert_id($conn);
                $sk_res = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE phamVi='SU_KIEN'");
                $sk_ids = array_column(mysqli_fetch_all($sk_res, MYSQLI_ASSOC), 'idQuyen');
                foreach ($quyenSK as $qid) {
                    if (in_array($qid, $sk_ids)) {
                        _insert_info($conn, 'vaitro_quyen', ['idVaiTro', 'idQuyen'], [$newId, $qid]);
                    }
                }
                $success_msg = 'Tạo vai trò mẫu <strong>' . htmlspecialchars($tenVT) . '</strong> thành công!';
                $active_tab  = 2;
            } else {
                $error_msg = 'Lỗi khi tạo vai trò.';
            }
        }
    }

    // ---- 5. CẬP NHẬT THÔNG TIN VAI TRÒ MẪU ----
    elseif ($action_type === 'edit_global_role_info') {
        $idVaiTro = (int)($_POST['g_edit_idVaiTro'] ?? 0);
        $tenVT    = trim($_POST['g_edit_tenVaiTro'] ?? '');
        $moTaVT   = trim($_POST['g_edit_moTaVaiTro'] ?? '');

        if ($idVaiTro <= 0 || empty($tenVT)) {
            $error_msg = 'Dữ liệu không hợp lệ.';
        } else {
            _update_info(
                $conn,
                'vaitro',
                ['tenvaitro', 'mota'],
                [$tenVT, $moTaVT ?: null],
                ['idvatro' => ['=', $idVaiTro, '']]
            );
            $success_msg = 'Cập nhật vai trò thành công!';
            $active_tab  = 2;
        }
    }

    // ---- 6. CẬP NHẬT QUYỀN VAI TRÒ MẪU ----
    elseif ($action_type === 'save_global_role_permissions') {
        $idVaiTro = (int)($_POST['g_perm_idVaiTro'] ?? 0);
        $checked  = (isset($_POST['g_perm_quyen_ids']) && is_array($_POST['g_perm_quyen_ids']))
            ? array_map('intval', $_POST['g_perm_quyen_ids']) : [];

        if ($idVaiTro <= 0) {
            $error_msg = 'Vai trò không hợp lệ.';
        } else {
            $sk_res = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE phamVi='SU_KIEN'");
            $sk_ids = array_column(mysqli_fetch_all($sk_res, MYSQLI_ASSOC), 'idQuyen');

            foreach ($sk_ids as $idQ) {
                $ex     = mysqli_query($conn, "SELECT idVaiTro FROM vaitro_quyen WHERE idVaiTro=$idVaiTro AND idQuyen=$idQ LIMIT 1");
                $exists = ($ex && mysqli_num_rows($ex) > 0);
                if (in_array($idQ, $checked) && !$exists) {
                    _insert_info($conn, 'vaitro_quyen', ['idVaiTro', 'idQuyen'], [$idVaiTro, $idQ]);
                } elseif (!in_array($idQ, $checked) && $exists) {
                    _delete_info($conn, 'vaitro_quyen', [
                        'idVaiTro' => ['=', $idVaiTro, 'AND'],
                        'idQuyen'  => ['=', $idQ, '']
                    ]);
                }
            }
            $success_msg = 'Cập nhật quyền vai trò thành công!';
            $active_tab  = 2;
        }
    }

    // ---- 7. XÓA VAI TRÒ MẪU ----
    elseif ($action_type === 'delete_global_role') {
        $idVaiTro = (int)($_POST['g_del_idVaiTro'] ?? 0);
        if ($idVaiTro <= 0) {
            $error_msg = 'Vai trò không hợp lệ.';
        } else {
            // Kiểm tra có vaitro_sukien nào dùng role gốc này không
            $used = mysqli_query($conn, "SELECT idVaiTroSK FROM vaitro_sukien WHERE idVaiTroGoc=$idVaiTro LIMIT 1");
            if ($used && mysqli_num_rows($used) > 0) {
                $error_msg = 'Vai trò đang được sử dụng ở một số sự kiện, không thể xóa.';
            } else {
                _delete_info($conn, 'vaitro_quyen', ['idVaiTro' => ['=', $idVaiTro, '']]);
                _delete_info($conn, 'vaitro',       ['idvatro'  => ['=', $idVaiTro, '']]);
                $success_msg = 'Đã xóa vai trò thành công!';
                $active_tab  = 2;
            }
        }
    }
}

// =============================================
// TRUY VẤN DỮ LIỆU
// =============================================
$search      = isset($_GET['search']) ? chuan_hoa_chuoi_sql($conn, trim($_GET['search'])) : '';
$filter_role = isset($_GET['filter_role']) ? (int)$_GET['filter_role'] : 0;

// Phân trang
$per_page   = 10;
$cur_page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$where_u    = "WHERE 1=1";
if (!empty($search))   $where_u .= " AND (tk.tenTK LIKE '%$search%' OR COALESCE(sv.tenSV,gv.tenGV,'') LIKE '%$search%')";
if ($filter_role > 0)  $where_u .= " AND tk.idLoaiTK = $filter_role";

$cnt_res     = mysqli_query($conn, "SELECT COUNT(*) c FROM taikhoan tk LEFT JOIN sinhvien sv ON tk.idTK=sv.idTK LEFT JOIN giangvien gv ON tk.idTK=gv.idTK $where_u");
$total_u     = (int)mysqli_fetch_assoc($cnt_res)['c'];
$total_pages = max(1, ceil($total_u / $per_page));
$cur_page    = min($cur_page, $total_pages);
$offset      = ($cur_page - 1) * $per_page;

$sql_users = "
    SELECT tk.idTK, tk.tenTK, tk.idLoaiTK, tk.isActive, tk.ngayTao,
           ltk.tenLoaiTK,
           COALESCE(sv.tenSV, gv.tenGV, '') AS hoTen,
           COALESCE(sv.MSV, '')             AS msv,
           COALESCE(sv.GPA, 0)              AS gpa,
           COALESCE(sv.DRL, 0)              AS drl,
           COALESCE(gv.gioiTinh, 0)         AS gioiTinh,
           COALESCE(lp.tenLop,'')           AS tenLop,
           COALESCE(kh1.tenKhoa, kh2.tenKhoa,'') AS tenKhoa,
           COALESCE(sv.idLop, 0)            AS idLop,
           COALESCE(gv.idKhoa, sv.idKhoa, 0) AS idKhoa
    FROM taikhoan tk
    LEFT JOIN loaitaikhoan ltk ON tk.idLoaiTK = ltk.idLoaiTK
    LEFT JOIN sinhvien sv  ON tk.idTK = sv.idTK
    LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
    LEFT JOIN lop lp       ON sv.idLop  = lp.idLop
    LEFT JOIN khoa kh1     ON sv.idKhoa = kh1.idKhoa
    LEFT JOIN khoa kh2     ON gv.idKhoa = kh2.idKhoa
    $where_u ORDER BY tk.idTK ASC LIMIT $per_page OFFSET $offset
";
$users = mysqli_fetch_all(mysqli_query($conn, $sql_users), MYSQLI_ASSOC);

// Danh sách phụ
$loai_tks = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM loaitaikhoan ORDER BY idLoaiTK"), MYSQLI_ASSOC);
$khoas    = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM khoa ORDER BY tenKhoa"), MYSQLI_ASSOC);
$lops     = mysqli_fetch_all(mysqli_query($conn, "SELECT lp.*, kh.tenKhoa FROM lop lp LEFT JOIN khoa kh ON lp.idKhoa=kh.idKhoa ORDER BY lp.tenLop"), MYSQLI_ASSOC);
$sukiens  = mysqli_fetch_all(mysqli_query($conn, "SELECT idSK, tenSK FROM sukien WHERE isActive=1 ORDER BY tenSK"), MYSQLI_ASSOC);

// Quyền theo phamVi, nhóm theo nhom_quyen
$quyens_ht = mysqli_fetch_all(mysqli_query($conn, "SELECT q.*, nq.tenNhom FROM quyen q LEFT JOIN nhom_quyen nq ON q.idNhomQuyen=nq.idNhomQuyen WHERE q.phamVi='HE_THONG' ORDER BY nq.thuTu, q.idQuyen"), MYSQLI_ASSOC);
$quyens_sk = mysqli_fetch_all(mysqli_query($conn, "SELECT q.*, nq.tenNhom FROM quyen q LEFT JOIN nhom_quyen nq ON q.idNhomQuyen=nq.idNhomQuyen WHERE q.phamVi='SU_KIEN'  ORDER BY nq.thuTu, q.idQuyen"), MYSQLI_ASSOC);

// Map quyền tài khoản (HE_THONG)
$quyen_map = [];
$tq_res = mysqli_query($conn, "SELECT idTK, idQuyen FROM taikhoan_quyen WHERE isActive=1");
while ($r = mysqli_fetch_assoc($tq_res)) $quyen_map[(int)$r['idTK']][] = (int)$r['idQuyen'];

// === VAI TRÒ MẪU (bảng vaitro - áp dụng chung mọi sự kiện) ===
// idvatro ↔ vaitro_quyen.idVaiTro — đây là vai trò gốc
$global_roles = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM vaitro ORDER BY idvatro"), MYSQLI_ASSOC);

// Map quyền của từng vai trò mẫu (idvatro => [idQuyen])
$global_role_quyen_map = [];
$grq_res = mysqli_query($conn, "SELECT idVaiTro, idQuyen FROM vaitro_quyen");
while ($r = mysqli_fetch_assoc($grq_res)) $global_role_quyen_map[(int)$r['idVaiTro']][] = (int)$r['idQuyen'];

// Vai trò mẫu nào đang được mirror vào vaitro_sukien (không cho xóa)
$global_role_used = [];
$gru_res = mysqli_query($conn, "SELECT DISTINCT idVaiTroGoc FROM vaitro_sukien WHERE idVaiTroGoc IS NOT NULL");
while ($r = mysqli_fetch_assoc($gru_res)) if ($r['idVaiTroGoc']) $global_role_used[] = (int)$r['idVaiTroGoc'];

// Nhóm quyền theo tenNhom
function group_quyen(array $quyens): array
{
    $out = [];
    foreach ($quyens as $q) {
        $out[$q['tenNhom'] ?? 'Khác'][] = $q;
    }
    return $out;
}

function user_page_url(int $page, string $search, int $filter_role, int $tab = 1): string
{
    $p = ['module' => 'admin', 'action' => 'users', 'tab' => $tab, 'page' => $page];
    if (!empty($search))   $p['search']      = $search;
    if ($filter_role > 0)  $p['filter_role'] = $filter_role;
    return '?' . http_build_query($p);
}

$ht_grouped = group_quyen($quyens_ht);
$sk_grouped = group_quyen($quyens_sk);

layout('header', ['page_title' => 'Quản lý người dùng']);
layout('navbar');
?>

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

<!-- =============================================
     HELPER MACRO: Render accordion quyền
============================================= -->
<?php
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

<!-- =============================================
     MODAL: TẠO TÀI KHOẢN
============================================= -->
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

                        <!-- Giảng viên -->
                        <div id="create-gv-section" class="d-none">
                            <h6 class="fw-bold text-success mb-3 border-bottom pb-2"><i
                                    class="bi bi-person-workspace me-1"></i>Hồ sơ giảng viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Họ và tên <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control gv-hoten" name="hoTen"
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
                                    <select class="form-select" name="idDonVi" id="create-idKhoa">
                                        <option value="">-- Chọn --</option>
                                        <?php foreach ($khoas as $k): ?>
                                            <option value="<?= $k['idKhoa'] ?>"><?= htmlspecialchars($k['tenKhoa']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Sinh viên -->
                        <div id="create-sv-section" class="d-none">
                            <h6 class="fw-bold text-info mb-3 border-bottom pb-2"><i
                                    class="bi bi-mortarboard me-1"></i>Hồ sơ sinh viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Họ và tên <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control sv-hoten" name="hoTen"
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
                                <select class="form-select" name="idDonVi">
                                    <option value="">-- Chọn lớp --</option>
                                    <?php foreach ($lops as $l): ?>
                                        <option value="<?= $l['idLop'] ?>"><?= htmlspecialchars($l['tenLop']) ?>
                                            (<?= htmlspecialchars($l['tenKhoa']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Quyền hệ thống -->
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

<!-- =============================================
     MODAL: CHỈNH SỬA TÀI KHOẢN
============================================= -->
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

                        <!-- Giảng viên -->
                        <div id="edit-gv-section" class="d-none">
                            <h6 class="fw-bold text-success mb-3 border-bottom pb-2"><i
                                    class="bi bi-person-workspace me-1"></i>Hồ sơ giảng viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Họ và tên</label>
                                    <input type="text" class="form-control" name="edit_hoTen" id="edit_hoTen">
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

                        <!-- Sinh viên -->
                        <div id="edit-sv-section" class="d-none">
                            <h6 class="fw-bold text-info mb-3 border-bottom pb-2"><i
                                    class="bi bi-mortarboard me-1"></i>Hồ sơ sinh viên</h6>
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Họ và tên</label>
                                    <input type="text" class="form-control" name="edit_hoTen" id="edit_hoTen_sv">
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
     MODAL: PHÂN QUYỀN HỆ THỐNG
============================================= -->
<div class="modal fade" id="permModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-3 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-info text-dark fs-6 px-3 py-2 d-inline-block mb-2"><i
                                class="bi bi-shield-lock me-1"></i>Phân quyền hệ thống</span>
                        <p id="perm_user_name" class="fw-semibold text-primary mb-1"></p>
                        <small class="text-muted">Tick chọn để cấp quyền, bỏ tick để thu hồi.</small>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="save_system_permissions">
                        <input type="hidden" name="perm_idTK" id="perm_idTK">
                        <?php if (empty($ht_grouped)): ?>
                            <p class="text-center text-muted py-3"><i class="bi bi-info-circle me-1"></i>Chưa có quyền hệ
                                thống nào.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="permAcc">
                                <?php render_quyen_accordion($ht_grouped, 'pq', 'info', true); ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-info text-white px-4"><i
                                    class="bi bi-shield-check me-1"></i>Lưu phân quyền</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL: TẠO VAI TRÒ MẪU
============================================= -->
<div class="modal fade" id="createGlobalRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-success fs-6 px-3 py-2 d-inline-block mb-2"><i
                                class="bi bi-person-badge me-1"></i>Tạo vai trò mẫu</span>
                        <p class="text-muted small mb-0">Vai trò này sẽ áp dụng chung cho mọi sự kiện khi được gán.</p>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="create_global_role">
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Tên vai trò <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="g_tenVaiTro"
                                    placeholder="VD: Giám khảo..." required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Mô tả</label>
                                <input type="text" class="form-control" name="g_moTaVaiTro"
                                    placeholder="Mô tả ngắn về vai trò...">
                            </div>
                        </div>
                        <h6 class="fw-bold text-success mb-3 border-bottom pb-2"><i
                                class="bi bi-check2-square me-1"></i>Quyền trong sự kiện</h6>
                        <?php if (empty($sk_grouped)): ?>
                            <p class="text-muted small">Chưa có quyền sự kiện nào.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="createGlobalRolePermAcc">
                                <?php render_quyen_accordion($sk_grouped, 'gcrq', 'success', false); ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-success px-4"><i class="bi bi-plus-circle me-1"></i>Tạo
                                vai trò</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL: SỬA THÔNG TIN VAI TRÒ MẪU
============================================= -->
<div class="modal fade" id="editGlobalRoleInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-4 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-secondary fs-6 px-3 py-2 d-inline-block mb-2"><i
                                class="bi bi-pencil-square me-1"></i>Sửa tên vai trò</span>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="edit_global_role_info">
                        <input type="hidden" name="g_edit_idVaiTro" id="g_edit_idVaiTro">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên vai trò <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="g_edit_tenVaiTro" id="g_edit_tenVaiTro"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mô tả</label>
                            <input type="text" class="form-control" name="g_edit_moTaVaiTro" id="g_edit_moTaVaiTro">
                        </div>
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-secondary px-4"><i
                                    class="bi bi-save me-1"></i>Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL: CHỈNH SỬA QUYỀN VAI TRÒ MẪU
============================================= -->
<div class="modal fade" id="editGlobalRolePermModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-3 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2 d-inline-block mb-2"><i
                                class="bi bi-shield-half me-1"></i>Phân quyền vai trò mẫu</span>
                        <p id="g_perm_role_name" class="fw-semibold text-primary mb-1"></p>
                        <small class="text-muted">Quyền này áp dụng chung cho mọi sự kiện khi tài khoản được gán vai trò
                            này.</small>
                    </div>
                    <form class="enrollment-form px-4 pb-4" method="POST" action="">
                        <input type="hidden" name="action_type" value="save_global_role_permissions">
                        <input type="hidden" name="g_perm_idVaiTro" id="g_perm_idVaiTro">
                        <?php if (empty($sk_grouped)): ?>
                            <p class="text-center text-muted py-3"><i class="bi bi-info-circle me-1"></i>Chưa có quyền sự
                                kiện nào.</p>
                        <?php else: ?>
                            <div class="accordion accordion-flush" id="editGlobalRolePermAcc">
                                <?php render_quyen_accordion($sk_grouped, 'gprq', 'warning', true); ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-warning px-4"><i class="bi bi-save me-1"></i>Lưu
                                quyền</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MAIN CONTENT
============================================= -->
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

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-4 border-bottom">
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab == 1 ? 'active' : '' ?>"
                        href="?module=admin&action=users&tab=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $filter_role > 0 ? '&filter_role=' . $filter_role : '' ?>">
                        <i class="bi bi-people me-1"></i>Tài khoản
                        <span class="badge bg-primary ms-1"><?= $total_u ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab == 2 ? 'active' : '' ?>" href="?module=admin&action=users&tab=2">
                        <i class="bi bi-person-badge me-1"></i>Vai trò mẫu
                        <span class="badge bg-success ms-1"><?= count($global_roles) ?></span>
                    </a>
                </li>
            </ul>

            <?php if ($active_tab == 1): ?>
                <!-- ==================== TAB 1: TÀI KHOẢN ==================== -->

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h3 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Danh sách tài khoản</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-person-plus me-1"></i>Tạo tài khoản
                    </button>
                </div>

                <!-- Tìm kiếm & lọc -->
                <div class="row mb-4 g-2">
                    <div class="col-md-7">
                        <form method="GET" class="d-flex gap-2">
                            <input type="hidden" name="module" value="admin">
                            <input type="hidden" name="action" value="users">
                            <input type="hidden" name="tab" value="1">
                            <?php if ($filter_role > 0): ?><input type="hidden" name="filter_role"
                                    value="<?= $filter_role ?>"><?php endif; ?>
                            <input type="text" class="form-control" name="search"
                                placeholder="Tìm theo tên đăng nhập hoặc họ tên..."
                                value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                            <?php if (!empty($search)): ?>
                                <a href="?module=admin&action=users&tab=1<?= $filter_role > 0 ? '&filter_role=' . $filter_role : '' ?>"
                                    class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-5 d-flex gap-2 flex-wrap align-items-start">
                        <a href="?module=admin&action=users&tab=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                            class="btn btn-sm <?= $filter_role == 0 ? 'btn-primary' : 'btn-outline-primary' ?>">Tất cả</a>
                        <?php foreach ($loai_tks as $ltk): ?>
                            <a href="?module=admin&action=users&tab=1&filter_role=<?= $ltk['idLoaiTK'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                class="btn btn-sm <?= $filter_role == $ltk['idLoaiTK'] ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <?= htmlspecialchars($ltk['tenLoaiTK']) ?>
                            </a>
                        <?php endforeach; ?>
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
                                <th>Loại TK</th>
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
                                    <?php
                                    $rc      = [1 => 'danger', 2 => 'success', 3 => 'primary'][$u['idLoaiTK']] ?? 'secondary';
                                    $ri      = [1 => 'bi-shield-fill', 2 => 'bi-person-workspace', 3 => 'bi-mortarboard-fill'][$u['idLoaiTK']] ?? 'bi-person';
                                    $don_vi  = !empty($u['tenLop']) ? $u['tenLop'] : $u['tenKhoa'];
                                    $u_json  = htmlspecialchars(json_encode($u, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                    ?>
                                    <tr>
                                        <td class="ps-3 text-muted"><?= $offset + $idx + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                                    style="width:36px;height:36px;flex-shrink:0">
                                                    <i class="bi bi-person text-primary"></i>
                                                </div>
                                                <span class="fw-semibold"><?= htmlspecialchars($u['tenTK']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($u['hoTen'] ?: '—') ?>
                                            <?php if (!empty($u['msv'])): ?><div class="text-muted small">
                                                    <?= htmlspecialchars($u['msv']) ?></div><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($don_vi)): ?>
                                                <span class="badge bg-light text-dark border"><i
                                                        class="bi bi-building me-1"></i><?= htmlspecialchars($don_vi) ?></span>
                                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                        </td>
                                        <td>
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
                                                    onclick='openEditModal(<?= $u_json ?>)' title="Chỉnh sửa">
                                                    <i class="bi bi-pencil"></i> Sửa
                                                </button>
                                                <button class="btn btn-sm btn-outline-info"
                                                    onclick="openPermModal(<?= $u['idTK'] ?>,'<?= addslashes($u['tenTK']) ?>')"
                                                    title="Phân quyền hệ thống">
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

                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $cur_page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= user_page_url($cur_page - 1, $search, $filter_role) ?>"><i
                                        class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php
                            $sp = max(1, $cur_page - 2);
                            $ep = min($total_pages, $cur_page + 2);
                            if ($sp > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . user_page_url(1, $search, $filter_role) . '">1</a></li>';
                                if ($sp > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                            for ($p = $sp; $p <= $ep; $p++) echo '<li class="page-item ' . ($p == $cur_page ? 'active' : '') . '"><a class="page-link" href="' . user_page_url($p, $search, $filter_role) . '">' . $p . '</a></li>';
                            if ($ep < $total_pages) {
                                if ($ep < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="' . user_page_url($total_pages, $search, $filter_role) . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            <li class="page-item <?= $cur_page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= user_page_url($cur_page + 1, $search, $filter_role) ?>"><i
                                        class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                    <p class="text-center text-muted small">Trang <?= $cur_page ?>/<?= $total_pages ?> · <?= $total_u ?> tài
                        khoản</p>
                <?php endif; ?>
                <?php if (!empty($users)): ?>
                    <div class="mt-2 text-muted small">
                        Hiển thị <?= count($users) ?> / <strong><?= $total_u ?></strong> tài khoản
                        <?= !empty($search) ? ' · Tìm: <strong>' . htmlspecialchars($search) . '</strong>' : '' ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ==================== TAB 2: VAI TRÒ MẪU ==================== -->

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h3 class="fw-bold mb-1"><i class="bi bi-person-badge me-2 text-success"></i>Vai trò mẫu hệ thống
                        </h3>
                        <p class="text-muted small mb-0">Định nghĩa các vai trò và quyền hạn áp dụng chung. Khi tài khoản
                            được gán vai trò này vào một sự kiện, họ chỉ có quyền đó tại sự kiện đó.</p>
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createGlobalRoleModal">
                        <i class="bi bi-plus-circle me-1"></i>Thêm vai trò mới
                    </button>
                </div>

                <?php if (empty($global_roles)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>Chưa có vai trò mẫu nào.
                    </div>
                <?php else: ?>
                    <div class="table-responsive shadow-sm rounded-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Tên vai trò</th>
                                    <th>Mô tả</th>
                                    <th>Số quyền</th>
                                    <th>Sự kiện đang dùng</th>
                                    <th class="text-center pe-3">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($global_roles as $idx => $vt): ?>
                                    <?php
                                    $vt_id  = (int)$vt['idvatro'];
                                    $q_cnt  = count($global_role_quyen_map[$vt_id] ?? []);
                                    // Đếm số sự kiện đang dùng vai trò này
                                    $sk_cnt_res = mysqli_query($conn, "SELECT COUNT(*) c FROM vaitro_sukien WHERE idVaiTroGoc=$vt_id AND isActive=1");
                                    $sk_cnt = (int)mysqli_fetch_assoc($sk_cnt_res)['c'];
                                    $is_used = in_array($vt_id, $global_role_used);
                                    ?>
                                    <tr>
                                        <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <span class="fw-semibold"><?= htmlspecialchars($vt['tenvaitro']) ?></span>
                                        </td>
                                        <td class="text-muted small"><?= htmlspecialchars($vt['mota'] ?? '—') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $q_cnt > 0 ? 'success' : 'secondary' ?>"><?= $q_cnt ?>
                                                quyền</span>
                                        </td>
                                        <td>
                                            <?php if ($sk_cnt > 0): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary">
                                                    <i class="bi bi-calendar-event me-1"></i><?= $sk_cnt ?> sự kiện
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">Chưa dùng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    onclick="openEditGlobalRoleInfoModal(<?= $vt_id ?>,'<?= addslashes($vt['tenvaitro']) ?>','<?= addslashes($vt['mota'] ?? '') ?>')"
                                                    title="Sửa tên/mô tả">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning"
                                                    onclick="openEditGlobalRolePermModal(<?= $vt_id ?>,'<?= addslashes($vt['tenvaitro']) ?>')"
                                                    title="Chỉnh sửa quyền">
                                                    <i class="bi bi-shield-half"></i> Quyền
                                                </button>
                                                <?php if (!$is_used): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Xóa vai trò \"
                                                        <?= addslashes($vt['tenvaitro']) ?>\"?')">
                                                        <input type="hidden" name="action_type" value="delete_global_role">
                                                        <input type="hidden" name="g_del_idVaiTro" value="<?= $vt_id ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                                                class="bi bi-trash"></i></button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-danger" disabled
                                                        title="Đang được dùng ở <?= $sk_cnt ?> sự kiện"><i
                                                            class="bi bi-trash"></i></button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Ghi chú nghiệp vụ -->
                    <div class="alert alert-info border-0 mt-4 d-flex gap-2" role="alert">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div class="small">
                            <strong>Cách hoạt động:</strong> Khi tài khoản được gán vai trò này vào một sự kiện (qua trang quản
                            lý sự kiện), hệ thống tạo bản sao vai trò <code>vaitro_sukien</code> kế thừa quyền từ đây. Tài khoản
                            đó chỉ có các quyền này tại sự kiện được gán — không ảnh hưởng sự kiện khác.
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; /* end tabs */ ?>

        </div>
    </section>
</main>

<?php layout('footer'); ?>

<script>
    const quyenMap = <?= json_encode($quyen_map,            JSON_UNESCAPED_UNICODE) ?>;
    const globalRoleQuyenMap = <?= json_encode($global_role_quyen_map, JSON_UNESCAPED_UNICODE) ?>;

    // Toggle hiện/ẩn mật khẩu
    function togglePwd(id, btn) {
        const inp = document.getElementById(id);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').classList.toggle('bi-eye');
        btn.querySelector('i').classList.toggle('bi-eye-slash');
    }

    // Toggle form tạo tài khoản
    function toggleCreateForm(val) {
        document.getElementById('create-gv-section').classList.toggle('d-none', val != '2');
        document.getElementById('create-sv-section').classList.toggle('d-none', val != '3');
    }

    // Toggle form sửa tài khoản
    function toggleEditForm(val) {
        document.getElementById('edit-gv-section').classList.toggle('d-none', val != '2');
        document.getElementById('edit-sv-section').classList.toggle('d-none', val != '3');
    }

    // Mở modal sửa tài khoản
    function openEditModal(u) {
        document.getElementById('edit_idTK').value = u.idTK;
        document.getElementById('edit_tenTK_display').value = u.tenTK;
        document.getElementById('editPassword').value = '';
        document.getElementById('edit_hoTen').value = u.hoTen;
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

    // Mở modal phân quyền hệ thống
    function openPermModal(idTK, tenTK) {
        document.getElementById('perm_idTK').value = idTK;
        document.getElementById('perm_user_name').textContent = 'Tài khoản: ' + tenTK;
        const userQuyens = quyenMap[idTK] || [];
        document.querySelectorAll('.pq-cb').forEach(cb => {
            cb.checked = userQuyens.includes(parseInt(cb.dataset.idquyen));
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('permModal')).show();
    }

    // Mở modal sửa tên/mô tả vai trò mẫu
    function openEditGlobalRoleInfoModal(idVaiTro, ten, mota) {
        document.getElementById('g_edit_idVaiTro').value = idVaiTro;
        document.getElementById('g_edit_tenVaiTro').value = ten;
        document.getElementById('g_edit_moTaVaiTro').value = mota;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editGlobalRoleInfoModal')).show();
    }

    // Mở modal chỉnh sửa quyền vai trò mẫu
    function openEditGlobalRolePermModal(idVaiTro, tenVaiTro) {
        document.getElementById('g_perm_idVaiTro').value = idVaiTro;
        document.getElementById('g_perm_role_name').textContent = 'Vai trò: ' + tenVaiTro;
        const roleQuyens = globalRoleQuyenMap[idVaiTro] || [];
        document.querySelectorAll('.gprq-cb').forEach(cb => {
            cb.checked = roleQuyens.includes(parseInt(cb.dataset.idquyen));
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editGlobalRolePermModal')).show();
    }

    // Auto-dismiss toast
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.toast.show').forEach(t => {
            setTimeout(() => bootstrap.Toast.getOrCreateInstance(t).hide(), 4000);
        });
    });
</script>