<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';

// ============================================================
// HELPER NỘI BỘ LUỒNG 3 (DRY — tránh lặp code assign/revoke role)
// ============================================================

/**
 * Đồng bộ role chấm điểm vào taikhoan_vaitro_sukien.
 * Dùng chung cho assign_doclap và add_gv_tb.
 *
 * @param string $ten_vaitro  'GV_CHAM_DOCLAP' hoặc 'GV_CHAM_TIEUHAN'
 * @param int    $idGV        ID giảng viên
 * @param int    $idSK        ID sự kiện
 * @param int    $id_nguoi_cap  idTK người thực hiện (BTC)
 */
function _dong_bo_role_cham($conn, string $ten_vaitro, int $idGV, int $idSK, int $id_nguoi_cap): void
{
    $res_tk   = mysqli_query($conn, "SELECT idTK FROM giangvien WHERE idGV = $idGV LIMIT 1");
    $res_role = mysqli_query($conn, "SELECT idvatro FROM vaitro WHERE tenvaitro = '$ten_vaitro' LIMIT 1");
    if (!$res_tk || !$res_role || mysqli_num_rows($res_tk) === 0 || mysqli_num_rows($res_role) === 0) return;

    $idTK  = (int)mysqli_fetch_assoc($res_tk)['idTK'];
    $idVT  = (int)mysqli_fetch_assoc($res_role)['idvatro'];

    $chk = mysqli_query(
        $conn,
        "SELECT 1 FROM taikhoan_vaitro_sukien
         WHERE idTK = $idTK AND idSK = $idSK AND idVaiTro = $idVT AND isActive = 1 LIMIT 1"
    );
    if ($chk && mysqli_num_rows($chk) === 0) {
        mysqli_query(
            $conn,
            "INSERT INTO taikhoan_vaitro_sukien (idTK, idSK, idVaiTro, nguonTao, idNguoiCap, ngayCap, isActive)
             VALUES ($idTK, $idSK, $idVT, 'PHANCONG_CHAM', $id_nguoi_cap, NOW(), 1)"
        );
    }
}

/**
 * Thu hồi role chấm điểm nếu GV không còn phân công nào trong SK.
 * Dùng chung cho remove_doclap và remove_gv_tb.
 *
 * @param string $ten_vaitro   'GV_CHAM_DOCLAP' hoặc 'GV_CHAM_TIEUHAN'
 * @param int    $idGV
 * @param int    $idSK
 * @param string $sql_dem_con  SQL đếm số phân công còn lại trong SK
 */
function _thu_hoi_role_cham_neu_het($conn, string $ten_vaitro, int $idGV, int $idSK, string $sql_dem_con): void
{
    $res_tk   = mysqli_query($conn, "SELECT idTK FROM giangvien WHERE idGV = $idGV LIMIT 1");
    $res_role = mysqli_query($conn, "SELECT idvatro FROM vaitro WHERE tenvaitro = '$ten_vaitro' LIMIT 1");
    if (!$res_tk || !$res_role || mysqli_num_rows($res_tk) === 0 || mysqli_num_rows($res_role) === 0) return;

    $idTK  = (int)mysqli_fetch_assoc($res_tk)['idTK'];
    $idVT  = (int)mysqli_fetch_assoc($res_role)['idvatro'];

    $res_cnt = mysqli_query($conn, $sql_dem_con);
    $cnt     = ($res_cnt) ? (int)mysqli_fetch_assoc($res_cnt)['cnt'] : 1;

    if ($cnt === 0) {
        mysqli_query(
            $conn,
            "UPDATE taikhoan_vaitro_sukien SET isActive = 0
             WHERE idTK = $idTK AND idSK = $idSK AND idVaiTro = $idVT"
        );
    }
}


$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1; // Mặc định là Tab 1

// ==========================================
// AUTO-MIGRATION: Tự động cập nhật CSDL
// ==========================================
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tieuban_giangvien (
    idTieuBan INT NOT NULL, 
    idGV INT NOT NULL, 
    vaiTro VARCHAR(50) DEFAULT 'Thành viên', 
    PRIMARY KEY (idTieuBan, idGV)
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tieuban_sanpham (
    idTieuBan INT NOT NULL, 
    idSanPham INT NOT NULL, 
    PRIMARY KEY (idTieuBan, idSanPham)
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS canhbaodiem (
    idCanhBao INT AUTO_INCREMENT PRIMARY KEY, 
    idSanPham INT NOT NULL, 
    idVongThi INT NOT NULL, 
    doLech DECIMAL(5,2) NOT NULL, 
    trangThai VARCHAR(50) DEFAULT 'Chờ xử lý', 
    thoiGian DATETIME DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS phancong_doclap (
    idSanPham INT NOT NULL,
    idGV INT NOT NULL,
    idVongThi INT NOT NULL,
    PRIMARY KEY (idSanPham, idGV, idVongThi)
)");

$chk = mysqli_query($conn, "SHOW COLUMNS FROM tieuban LIKE 'idVongThi'");
if (mysqli_num_rows($chk) == 0) mysqli_query($conn, "ALTER TABLE tieuban ADD COLUMN idVongThi INT NULL AFTER idSK");

$chk2 = mysqli_query($conn, "SHOW COLUMNS FROM tieuban LIKE 'ngayBaoCao'");
if (mysqli_num_rows($chk2) == 0) {
    mysqli_query($conn, "ALTER TABLE tieuban ADD COLUMN ngayBaoCao DATE NULL");
    mysqli_query($conn, "ALTER TABLE tieuban ADD COLUMN diaDiem VARCHAR(100) NULL");
}

// [LUỒNG 3] Migration guard: đảm bảo 2 role chấm tồn tại trong DB
// Dùng INSERT IGNORE với id cố định (5,6) — idempotent, an toàn khi chạy lại nhiều lần
mysqli_query($conn, "INSERT IGNORE INTO vaitro (idvatro, tenvaitro, mota, btcCoTheGan) VALUES (5, 'GV_CHAM_DOCLAP', 'Chấm độc lập theo sản phẩm được phân công', 0)");
mysqli_query($conn, "INSERT IGNORE INTO vaitro (idvatro, tenvaitro, mota, btcCoTheGan) VALUES (6, 'GV_CHAM_TIEUHAN', 'Chấm trong tiểu ban/hội đồng', 0)");
mysqli_query($conn, "INSERT IGNORE INTO vaitro_quyen (idVaiTro, idQuyen) VALUES (5,28),(5,29),(6,28),(6,29)");


// ==========================================
// XỬ LÝ FORM SUBMIT (POST REQUESTS)
// ==========================================
if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';
    $current_tab = (int)($data['active_tab'] ?? $active_tab);

    if ($action === 'assign_doclap') {
        $idSP   = (int)($data['idSanPham'] ?? 0);
        $idGV   = (int)($data['idGV'] ?? 0);
        $idVong = (int)($data['idVongThi'] ?? 0);
        if ($idSP > 0 && $idGV > 0 && $idVong > 0) {
            mysqli_begin_transaction($conn);
            try {
                mysqli_query($conn, "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi) VALUES ($idSP, $idGV, $idVong)");
                $res_sk = mysqli_query($conn, "SELECT idSK FROM vongthi WHERE idVongThi = $idVong LIMIT 1");
                if ($res_sk && mysqli_num_rows($res_sk) > 0) {
                    $idSK_cur = (int)mysqli_fetch_assoc($res_sk)['idSK'];
                    _dong_bo_role_cham($conn, 'GV_CHAM_DOCLAP', $idGV, $idSK_cur, $id_su_kien);
                }
                mysqli_commit($conn);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("assign_doclap error: " . $e->getMessage());
            }
        }
    } elseif ($action === 'remove_doclap') {
        $idSP   = (int)($data['idSanPham'] ?? 0);
        $idGV   = (int)($data['idGV'] ?? 0);
        $idVong = (int)($data['idVongThi'] ?? 0);
        if ($idGV > 0 && $idVong > 0) {
            mysqli_begin_transaction($conn);
            try {
                mysqli_query($conn, "DELETE FROM phancong_doclap WHERE idSanPham = $idSP AND idGV = $idGV AND idVongThi = $idVong");
                $res_sk = mysqli_query($conn, "SELECT idSK FROM vongthi WHERE idVongThi = $idVong LIMIT 1");
                if ($res_sk && mysqli_num_rows($res_sk) > 0) {
                    $idSK_cur  = (int)mysqli_fetch_assoc($res_sk)['idSK'];
                    $sql_dem   = "SELECT COUNT(*) AS cnt FROM phancong_doclap pcd
                                  JOIN vongthi v ON pcd.idVongThi = v.idVongThi
                                  WHERE pcd.idGV = $idGV AND v.idSK = $idSK_cur";
                    // Chỉ thu hồi GV_CHAM_DOCLAP, không đụng GV_CHAM_TIEUHAN
                    _thu_hoi_role_cham_neu_het($conn, 'GV_CHAM_DOCLAP', $idGV, $idSK_cur, $sql_dem);
                }
                mysqli_commit($conn);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("remove_doclap error: " . $e->getMessage());
            }
        }
    } elseif ($action === 'create_tb') {
        $tenTB = trim($data['tenTieuBan'] ?? '');
        $idVong = (int)($data['idVongThi'] ?? 0);
        $ngayBaoCao = !empty($data['ngayBaoCao']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['ngayBaoCao']) . "'" : "NULL";
        $diaDiem = !empty($data['diaDiem']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['diaDiem']) . "'" : "NULL";

        if (!empty($tenTB) && $idVong > 0) {
            mysqli_query($conn, "INSERT INTO tieuban (idSK, idVongThi, tenTieuBan, ngayBaoCao, diaDiem) VALUES ($id_su_kien, $idVong, '" . chuan_hoa_chuoi_sql($conn, $tenTB) . "', $ngayBaoCao, $diaDiem)");
        }
    } elseif ($action === 'edit_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $tenTB = trim($data['tenTieuBan'] ?? '');
        $ngayBaoCao = !empty($data['ngayBaoCao']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['ngayBaoCao']) . "'" : "NULL";
        $diaDiem = !empty($data['diaDiem']) ? "'" . chuan_hoa_chuoi_sql($conn, $data['diaDiem']) . "'" : "NULL";

        if ($idTB > 0 && !empty($tenTB)) {
            mysqli_query($conn, "UPDATE tieuban SET tenTieuBan = '" . chuan_hoa_chuoi_sql($conn, $tenTB) . "', ngayBaoCao = $ngayBaoCao, diaDiem = $diaDiem WHERE idTieuBan = $idTB");
        }
    } elseif ($action === 'delete_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        if ($idTB > 0) {
            mysqli_query($conn, "DELETE FROM tieuban WHERE idTieuBan = $idTB");
            mysqli_query($conn, "DELETE FROM tieuban_giangvien WHERE idTieuBan = $idTB");
            mysqli_query($conn, "DELETE FROM tieuban_sanpham WHERE idTieuBan = $idTB");
        }
    } elseif ($action === 'add_gv_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idGV = (int)($data['idGV'] ?? 0);
        if ($idTB > 0 && $idGV > 0) {
            mysqli_begin_transaction($conn);
            try {
                mysqli_query($conn, "INSERT IGNORE INTO tieuban_giangvien (idTieuBan, idGV) VALUES ($idTB, $idGV)");
                $res_sk = mysqli_query($conn, "SELECT idSK FROM tieuban WHERE idTieuBan = $idTB LIMIT 1");
                if ($res_sk && mysqli_num_rows($res_sk) > 0) {
                    $idSK_cur = (int)mysqli_fetch_assoc($res_sk)['idSK'];
                    _dong_bo_role_cham($conn, 'GV_CHAM_TIEUHAN', $idGV, $idSK_cur, $id_su_kien);
                }
                mysqli_commit($conn);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("add_gv_tb error: " . $e->getMessage());
            }
        }
    } elseif ($action === 'remove_gv_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idGV = (int)($data['idGV'] ?? 0);
        if ($idTB > 0 && $idGV > 0) {
            mysqli_begin_transaction($conn);
            try {
                mysqli_query($conn, "DELETE FROM tieuban_giangvien WHERE idTieuBan = $idTB AND idGV = $idGV");
                $res_sk = mysqli_query($conn, "SELECT idSK FROM tieuban WHERE idTieuBan = $idTB LIMIT 1");
                if ($res_sk && mysqli_num_rows($res_sk) > 0) {
                    $idSK_cur = (int)mysqli_fetch_assoc($res_sk)['idSK'];
                    $sql_dem  = "SELECT COUNT(*) AS cnt FROM tieuban_giangvien tbg
                                 JOIN tieuban tb ON tbg.idTieuBan = tb.idTieuBan
                                 WHERE tbg.idGV = $idGV AND tb.idSK = $idSK_cur";
                    // Chỉ thu hồi GV_CHAM_TIEUHAN, không đụng GV_CHAM_DOCLAP
                    _thu_hoi_role_cham_neu_het($conn, 'GV_CHAM_TIEUHAN', $idGV, $idSK_cur, $sql_dem);
                }
                mysqli_commit($conn);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("remove_gv_tb error: " . $e->getMessage());
            }
        }
    } elseif ($action === 'add_sp_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idSP = (int)($data['idSanPham'] ?? 0);
        if ($idTB > 0 && $idSP > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO tieuban_sanpham (idTieuBan, idSanPham) VALUES ($idTB, $idSP)");
        }
    } elseif ($action === 'remove_sp_tb') {
        $idTB = (int)($data['idTieuBan'] ?? 0);
        $idSP = (int)($data['idSanPham'] ?? 0);
        mysqli_query($conn, "DELETE FROM tieuban_sanpham WHERE idTieuBan = $idTB AND idSanPham = $idSP");
    }

    header("Location: ?module=event&action=config_assign&id=$id_su_kien&tab=$current_tab");
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU ĐỔ RA GIAO DIỆN
// ==========================================
$vong_list = mysqli_query($conn, "SELECT * FROM vongthi WHERE idSK = $id_su_kien ORDER BY thuTu ASC");
$vong_array = $vong_list ? mysqli_fetch_all($vong_list, MYSQLI_ASSOC) : [];

// Lấy Vòng thi đang chọn để xem Tiến độ (Mặc định là vòng đầu tiên)
$active_vong = isset($_GET['vong']) ? (int)$_GET['vong'] : 0;
if ($active_vong == 0 && !empty($vong_array)) {
    $active_vong = $vong_array[0]['idVongThi'];
}

$res_gv_all = mysqli_query($conn, "SELECT idGV, tenGV FROM giangvien ORDER BY tenGV ASC");
$ds_giangvien = $res_gv_all ? mysqli_fetch_all($res_gv_all, MYSQLI_ASSOC) : [];

$sql_sp = "SELECT sp.idSanPham, sp.tensanpham, n.manhom, ttn.tennhom, sv.tenSV as nhomTruong 
           FROM sanpham sp 
           LEFT JOIN nhom n ON sp.idNhom = n.idnhom 
           LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom 
           LEFT JOIN sinhvien sv ON n.idChuNhom = sv.idTK 
           WHERE sp.idSK = $id_su_kien ORDER BY sp.idSanPham DESC";
$res_sp = mysqli_query($conn, $sql_sp);
$ds_sanpham = $res_sp ? mysqli_fetch_all($res_sp, MYSQLI_ASSOC) : [];

// --- Dữ liệu Tab 1 (Phân công độc lập) ---
$sql_pc = "SELECT pcd.*, gv.tenGV, v.tenVongThi FROM phancong_doclap pcd JOIN giangvien gv ON pcd.idGV = gv.idGV JOIN vongthi v ON pcd.idVongThi = v.idVongThi WHERE v.idSK = $id_su_kien";
$res_pc = mysqli_query($conn, $sql_pc);
$ds_phancong = $res_pc ? mysqli_fetch_all($res_pc, MYSQLI_ASSOC) : [];
$phancong_map = [];
foreach ($ds_phancong as $pc) {
    $phancong_map[$pc['idSanPham']][] = $pc;
}

// --- Dữ liệu Tab 2 (TÍNH TOÁN ĐIỂM CHUẨN XÁC) ---
// ĐÃ SỬA: CHỈ đếm số Giám khảo được phân công ở Tab 1 (phancong_doclap) theo Vòng đang chọn. Tách biệt hoàn toàn với Tiểu ban.
$sql_tiendo = "
    SELECT 
        sp.idSanPham, 
        sp.tensanpham, 
        n.manhom, 
        ttn.tennhom,
        IFNULL(pc.tongGiamKhaoPhanCong, 0) as tongGiamKhaoPhanCong
    FROM sanpham sp
    LEFT JOIN nhom n ON sp.idNhom = n.idnhom
    LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
    JOIN (
        SELECT idSanPham, COUNT(idGV) as tongGiamKhaoPhanCong
        FROM phancong_doclap
        WHERE idVongThi = $active_vong
        GROUP BY idSanPham
    ) pc ON sp.idSanPham = pc.idSanPham
    WHERE sp.idSK = $id_su_kien
    ORDER BY sp.idSanPham DESC
";
$res_td = mysqli_query($conn, $sql_tiendo);
$ds_tiendo = $res_td ? mysqli_fetch_all($res_td, MYSQLI_ASSOC) : [];

// Lấy điểm (Chỉ lấy điểm của Vòng đang chọn - Cần bảng chamtieuchi có liên kết vòng thi hoặc qua bảng trung gian, tạm lấy theo idSanPham)
$sql_scores = "SELECT idSanPham, idPhanCongCham, SUM(diem) as tongDiem 
               FROM chamtieuchi 
               WHERE idSanPham IN (SELECT idSanPham FROM sanpham WHERE idSK = $id_su_kien) 
               GROUP BY idSanPham, idPhanCongCham";
$res_scores = mysqli_query($conn, $sql_scores);
$scores_map = [];
if ($res_scores) {
    while ($row = mysqli_fetch_assoc($res_scores)) {
        $scores_map[$row['idSanPham']][] = (float)$row['tongDiem'];
    }
}

// Xử lý xếp hạng
$ranking_list = [];
foreach ($ds_tiendo as $sp) {
    $idSP = $sp['idSanPham'];
    $sp_scores = $scores_map[$idSP] ?? [];

    $soNguoiDaCham = count($sp_scores);
    $diemTB = 0;
    $doLech = 0;
    $isWarning = false;

    if ($soNguoiDaCham > 0) {
        $diemTB = array_sum($sp_scores) / $soNguoiDaCham;
        $maxDiem = max($sp_scores);
        $minDiem = min($sp_scores);
        $doLech = $maxDiem - $minDiem;

        if ($soNguoiDaCham > 1 && $doLech >= ($diemTB * 0.3)) {
            $isWarning = true;
        }
    }

    $sp['soNguoiDaCham'] = $soNguoiDaCham;
    $sp['diemTB'] = $diemTB;
    $sp['isWarning'] = $isWarning;
    $sp['chiTietDiem'] = $sp_scores;

    $ranking_list[] = $sp;
}
usort($ranking_list, function ($a, $b) {
    return $b['diemTB'] <=> $a['diemTB'];
});

// --- Dữ liệu Tab 3 & 4 (Quản lý Tiểu ban) ---
$sql_tb = "SELECT tb.*, v.tenVongThi FROM tieuban tb LEFT JOIN vongthi v ON tb.idVongThi = v.idVongThi WHERE tb.idSK = $id_su_kien ORDER BY tb.idTieuBan ASC";
$res_tb = mysqli_query($conn, $sql_tb);
$tieuban_list = $res_tb ? mysqli_fetch_all($res_tb, MYSQLI_ASSOC) : [];

$sql_gv_tb = "SELECT tbg.*, gv.tenGV FROM tieuban_giangvien tbg JOIN giangvien gv ON tbg.idGV = gv.idGV WHERE tbg.idTieuBan IN (SELECT idTieuBan FROM tieuban WHERE idSK = $id_su_kien)";
$res_gv_tb = mysqli_query($conn, $sql_gv_tb);
$gv_tb_map = [];
if ($res_gv_tb) {
    while ($r = mysqli_fetch_assoc($res_gv_tb)) {
        $gv_tb_map[$r['idTieuBan']][] = $r;
    }
}

$sql_sp_tb = "SELECT tbs.*, sp.tensanpham, n.manhom, ttn.tennhom FROM tieuban_sanpham tbs JOIN sanpham sp ON tbs.idSanPham = sp.idSanPham LEFT JOIN nhom n ON sp.idNhom = n.idnhom LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom WHERE tbs.idTieuBan IN (SELECT idTieuBan FROM tieuban WHERE idSK = $id_su_kien)";
$res_sp_tb = mysqli_query($conn, $sql_sp_tb);
$sp_tb_map = [];
$assigned_sp_ids = [];
if ($res_sp_tb) {
    while ($r = mysqli_fetch_assoc($res_sp_tb)) {
        $sp_tb_map[$r['idTieuBan']][] = $r;
        $assigned_sp_ids[] = $r['idSanPham'];
    }
}

$total_tb = count($tieuban_list);
$unassigned_sps = [];
foreach ($ds_sanpham as $sp) {
    if (!in_array($sp['idSanPham'], $assigned_sp_ids)) $unassigned_sps[] = $sp;
}


layout('header');
layout('navbar');
page('event/config_assign', compact(
    'id_su_kien', 'event', 'active_tab',
    'vongthi_list', 'gv_list', 'sanpham_list',
    'phancong_map', 'tieuban_list', 'bo_tc_list'
));
layout('footer');
