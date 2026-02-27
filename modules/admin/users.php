<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_tai_khoan.php';

$success_msg = '';
$error_msg   = '';
$active_tab  = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;

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
        // $idDonVi  = (int)($_POST['idDonVi'] ?? 0);
        $msv      = trim($_POST['msv'] ?? '');
        $gpa      = trim($_POST['gpa'] ?? '');
        $drl      = trim($_POST['drl'] ?? '');
        $gioiTinh = (int)($_POST['gioiTinh'] ?? 0);
        $isActive = (int)($_POST['isActive'] ?? 1);
        $hoTen = '';
        $idDonVi = 0;
        if ($idLoaiTK == 2) {
            $hoTen   = trim($_POST['hoTen_gv'] ?? '');
            $idDonVi = (int)($_POST['idDonVi_gv'] ?? 0);
        } elseif ($idLoaiTK == 3) {
            $hoTen   = trim($_POST['hoTen_sv'] ?? '');
            $idDonVi = (int)($_POST['idDonVi_sv'] ?? 0);
        }


        if (empty($tenTK) || empty($matKhau) || $idLoaiTK === 0) {
            $error_msg = 'Vui lòng điền đầy đủ các trường bắt buộc.';
        } elseif (kiem_tra_ton_tai_ban_ghi($conn, 'taikhoan', 'tenTK', $tenTK)) {
            $error_msg = 'Tên đăng nhập đã tồn tại trong hệ thống.';
        } else {
            mysqli_begin_transaction($conn);
            try {
                $ok = _insert_info($conn, 'taikhoan', ['tenTK', 'matKhau', 'idLoaiTK', 'isActive'], [$tenTK, $matKhau, $idLoaiTK, $isActive]);
                if (!$ok) throw new Exception('Lỗi khi tạo tài khoản: ' . mysqli_error($conn));

                $new_id = mysqli_insert_id($conn);

                // Insert Giảng viên
                if ($idLoaiTK == 2 && !empty($hoTen)) {
                    _insert_info(
                        $conn,
                        'giangvien',
                        ['idTK', 'tenGV', 'idKhoa', 'gioiTinh'],
                        [$new_id, $hoTen, $idDonVi > 0 ? $idDonVi : null, $gioiTinh]
                    );
                }
                // Insert Sinh viên
                elseif ($idLoaiTK == 3 && !empty($hoTen)) {
                    $idKhoaSV = null;
                    if ($idDonVi > 0) {
                        $lop = truy_van_mot_ban_ghi($conn, 'lop', 'idLop', $idDonVi);
                        $idKhoaSV = $lop ? $lop['idKhoa'] : null;
                    }
                    _insert_info(
                        $conn,
                        'sinhvien',
                        ['idTK', 'tenSV', 'MSV', 'GPA', 'DRL', 'idLop', 'idKhoa'],
                        [$new_id, $hoTen, $msv, is_numeric($gpa) ? (float)$gpa : 0.00, is_numeric($drl) ? (int)$drl : 0, $idDonVi > 0 ? $idDonVi : null, $idKhoaSV]
                    );
                }

                // Gán quyền hệ thống
                if (!empty($_POST['quyen_ids']) && is_array($_POST['quyen_ids'])) {
                    $ht_res = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE phamVi='HE_THONG'");
                    $ht_ids = array_column(mysqli_fetch_all($ht_res, MYSQLI_ASSOC), 'idQuyen');
                    foreach ($_POST['quyen_ids'] as $qid) {
                        $qid = (int)$qid;
                        if (in_array($qid, $ht_ids)) {
                            _insert_info($conn, 'taikhoan_quyen', ['idTK', 'idQuyen', 'isActive', 'thoiGianBatDau'], [$new_id, $qid, 1, date('Y-m-d H:i:s')]);
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
        $matKhau  = trim($_POST['edit_matKhau'] ?? '');
        $msv      = trim($_POST['edit_msv'] ?? '');
        $gpa      = trim($_POST['edit_gpa'] ?? '');
        $drl      = trim($_POST['edit_drl'] ?? '');
        $gioiTinh = (int)($_POST['edit_gioiTinh'] ?? 0);

        $hoTen = '';
        $idDonVi = 0;
        if ($idLoaiTK == 2) {
            $hoTen   = trim($_POST['edit_hoTen_gv'] ?? '');
            $idDonVi = (int)($_POST['edit_idDonVi_gv'] ?? 0);
        } elseif ($idLoaiTK == 3) {
            $hoTen   = trim($_POST['edit_hoTen_sv'] ?? '');
            $idDonVi = (int)($_POST['edit_idDonVi_sv'] ?? 0);
        }

        if ($idTK <= 0) {
            $error_msg = 'Tài khoản không hợp lệ.';
        } else {
            $fields = ['idLoaiTK', 'isActive'];
            $values = [$idLoaiTK, $isActive];
            if (!empty($matKhau)) {
                $fields[] = 'matKhau';
                $values[] = $matKhau;
            }
            _update_info($conn, 'taikhoan', $fields, $values, ['idTK' => ['=', $idTK, '']]);

            if ($idLoaiTK == 2) {
                $chk = truy_van_mot_ban_ghi($conn, 'giangvien', 'idTK', $idTK);
                if ($chk) {
                    $gv_f = ['tenGV', 'gioiTinh', 'idKhoa'];
                    $gv_v = [$hoTen, $gioiTinh, $idDonVi > 0 ? $idDonVi : null];
                    _update_info($conn, 'giangvien', $gv_f, $gv_v, ['idTK' => ['=', $idTK, '']]);
                } else {
                    _insert_info($conn, 'giangvien', ['idTK', 'tenGV', 'idKhoa', 'gioiTinh'], [$idTK, $hoTen, $idDonVi > 0 ? $idDonVi : null, $gioiTinh]);
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
                        [$idTK, $hoTen, $msv, is_numeric($gpa) ? (float)$gpa : 0, is_numeric($drl) ? (int)$drl : 0, $idDonVi > 0 ? $idDonVi : null, $idKhoaSV]
                    );
                }
            }
            $success_msg = 'Cập nhật tài khoản thành công!';
        }
    }

    // ---- 3. PHÂN QUYỀN HỆ THỐNG ----
    if ($action_type === 'save_system_permissions') {
        $idTK    = (int)($_POST['perm_idTK'] ?? 0);
        $checked = (isset($_POST['quyen_ids']) && is_array($_POST['quyen_ids'])) ? array_map('intval', $_POST['quyen_ids']) : [];

        if ($idTK <= 0) {
            $error_msg = 'Tài khoản không hợp lệ.';
        } else {
            $ht_res = mysqli_query($conn, "SELECT idQuyen FROM quyen WHERE phamVi='HE_THONG'");
            $ht_ids = array_column(mysqli_fetch_all($ht_res, MYSQLI_ASSOC), 'idQuyen');

            foreach ($ht_ids as $idQ) {
                $ex     = mysqli_query($conn, "SELECT idTK FROM taikhoan_quyen WHERE idTK=$idTK AND idQuyen=$idQ LIMIT 1");
                $exists = ($ex && mysqli_num_rows($ex) > 0);
                if (in_array($idQ, $checked) && !$exists) {
                    _insert_info($conn, 'taikhoan_quyen', ['idTK', 'idQuyen', 'isActive', 'thoiGianBatDau'], [$idTK, $idQ, 1, date('Y-m-d H:i:s')]);
                } elseif (!in_array($idQ, $checked) && $exists) {
                    _delete_info($conn, 'taikhoan_quyen', ['idTK' => ['=', $idTK, 'AND'], 'idQuyen' => ['=', $idQ,  '']]);
                }
            }
            $success_msg = 'Cập nhật quyền hệ thống thành công!';
        }
    }

    // ---- 4. TẠO VAI TRÒ MẪU ----
    elseif ($action_type === 'create_global_role') {
        $tenVT   = trim($_POST['g_tenVaiTro'] ?? '');
        $moTaVT  = trim($_POST['g_moTaVaiTro'] ?? '');
        $quyenSK = (isset($_POST['g_quyen_ids']) && is_array($_POST['g_quyen_ids'])) ? array_map('intval', $_POST['g_quyen_ids']) : [];

        if (empty($tenVT)) {
            $error_msg = 'Vui lòng nhập tên vai trò.';
        } else {
            $ok = _insert_info($conn, 'vaitro', ['tenvaitro', 'mota'], [$tenVT, $moTaVT ?: null]);
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
            _update_info($conn, 'vaitro', ['tenvaitro', 'mota'], [$tenVT, $moTaVT ?: null], ['idvatro' => ['=', $idVaiTro, '']]);
            $success_msg = 'Cập nhật vai trò thành công!';
            $active_tab  = 2;
        }
    }

    // ---- 6. CẬP NHẬT QUYỀN VAI TRÒ MẪU ----
    elseif ($action_type === 'save_global_role_permissions') {
        $idVaiTro = (int)($_POST['g_perm_idVaiTro'] ?? 0);
        $checked  = (isset($_POST['g_perm_quyen_ids']) && is_array($_POST['g_perm_quyen_ids'])) ? array_map('intval', $_POST['g_perm_quyen_ids']) : [];

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
                    _delete_info($conn, 'vaitro_quyen', ['idVaiTro' => ['=', $idVaiTro, 'AND'], 'idQuyen'  => ['=', $idQ, '']]);
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
            // Kiến trúc mới: kiểm tra taikhoan_vaitro_sukien thay vì vaitro_sukien
            $used = mysqli_query($conn, "SELECT 1 FROM taikhoan_vaitro_sukien WHERE idVaiTro=$idVaiTro AND isActive=1 LIMIT 1");
            if ($used && mysqli_num_rows($used) > 0) {
                $error_msg = 'Vai trò đang được sử dụng, không thể xóa.';
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

$loai_tks = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM loaitaikhoan ORDER BY idLoaiTK"), MYSQLI_ASSOC);
$khoas    = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM khoa ORDER BY tenKhoa"), MYSQLI_ASSOC);
$lops     = mysqli_fetch_all(mysqli_query($conn, "SELECT lp.*, kh.tenKhoa FROM lop lp LEFT JOIN khoa kh ON lp.idKhoa=kh.idKhoa ORDER BY lp.tenLop"), MYSQLI_ASSOC);
$sukiens  = mysqli_fetch_all(mysqli_query($conn, "SELECT idSK, tenSK FROM sukien WHERE isActive=1 ORDER BY tenSK"), MYSQLI_ASSOC);

$quyens_ht = mysqli_fetch_all(mysqli_query($conn, "SELECT q.*, nq.tenNhom FROM quyen q LEFT JOIN nhom_quyen nq ON q.idNhomQuyen=nq.idNhomQuyen WHERE q.phamVi='HE_THONG' ORDER BY nq.thuTu, q.idQuyen"), MYSQLI_ASSOC);
$quyens_sk = mysqli_fetch_all(mysqli_query($conn, "SELECT q.*, nq.tenNhom FROM quyen q LEFT JOIN nhom_quyen nq ON q.idNhomQuyen=nq.idNhomQuyen WHERE q.phamVi='SU_KIEN'  ORDER BY nq.thuTu, q.idQuyen"), MYSQLI_ASSOC);

$quyen_map = [];
$tq_res = mysqli_query($conn, "SELECT idTK, idQuyen FROM taikhoan_quyen WHERE isActive=1");
while ($r = mysqli_fetch_assoc($tq_res)) $quyen_map[(int)$r['idTK']][] = (int)$r['idQuyen'];

$global_roles = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM vaitro ORDER BY idvatro"), MYSQLI_ASSOC);

$global_role_quyen_map = [];
$grq_res = mysqli_query($conn, "SELECT idVaiTro, idQuyen FROM vaitro_quyen");
while ($r = mysqli_fetch_assoc($grq_res)) $global_role_quyen_map[(int)$r['idVaiTro']][] = (int)$r['idQuyen'];

// Kiến trúc mới: idVaiTro trong taikhoan_vaitro_sukien trỏ thẳng vào vaitro
$global_role_used = [];
$gru_res = mysqli_query($conn, "SELECT DISTINCT idVaiTro FROM taikhoan_vaitro_sukien WHERE isActive=1 AND idVaiTro IS NOT NULL");
while ($r = mysqli_fetch_assoc($gru_res)) if ($r['idVaiTro']) $global_role_used[] = (int)$r['idVaiTro'];

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
page('admin/users', compact(
    'users', 'loai_tk_list', 'khoa_list',
    'filter', 'total_count', 'cur_page', 'total_pages',
    'flash_msg', 'flash_type'
));
layout('footer');
