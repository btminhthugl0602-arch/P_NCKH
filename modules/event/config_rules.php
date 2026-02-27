<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_quy_che.php';

$id_su_kien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$loai_quy_che = isset($_GET['loai']) ? $_GET['loai'] : 'THAMGIA';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

// ======================
// HÀM ĐỆ QUY XỬ LÝ CÂY LOGIC (TỪ UI -> DB)
// ======================
function parseRuleNode($conn, $node, $user_id, $id_su_kien) {
    if ($node['type'] === 'rule') {
        $tenDK = "DK_DON_" . uniqid();
        $idTT = (int)$node['idThuocTinh'];
        $idToanTu = (int)$node['idToanTu'];
        $giaTri = $node['giaTri'];
        
        $result = tao_dieu_kien_don($conn, $user_id, $id_su_kien, $tenDK, $idTT, $idToanTu, $giaTri);
        return $result['status'] ? $result['idDieuKien'] : null;
    } 
    elseif ($node['type'] === 'group') {
        $children = $node['children'] ?? [];
        if (empty($children)) return null;
        if (count($children) === 1) return parseRuleNode($conn, $children[0], $user_id, $id_su_kien);

        $logicId = (int)$node['logic'];
        $leftId = parseRuleNode($conn, $children[0], $user_id, $id_su_kien);

        for ($i = 1; $i < count($children); $i++) {
            $rightId = parseRuleNode($conn, $children[$i], $user_id, $id_su_kien   );
            if ($leftId && $rightId) {
                $tenToHop = "TOHOP_" . uniqid();
                $resToHop = tao_to_hop_dieu_kien($conn, $user_id, $id_su_kien, $leftId, $logicId, $rightId, $tenToHop);
                if ($resToHop['status']) {
                    $leftId = $resToHop['idDieuKien']; 
                } else {
                    return null; 
                }
            }
        }
        return $leftId;
    }
    return null;
}

// ======================
// HÀM ĐỆ QUY DỊCH NGƯỢC CÂY LOGIC (Từ DB -> UI AJAX)
// ======================
function fetchAST($conn, $idDieuKien) {
    $dk = truy_van_mot_ban_ghi($conn, 'dieukien', 'idDieuKien', $idDieuKien);
    if (!$dk) return null;

    if ($dk['loaiDieuKien'] == 'DON') {
        $don = truy_van_mot_ban_ghi($conn, 'dieukien_don', 'idDieuKien', $idDieuKien);
        return [
            'type' => 'rule',
            'idThuocTinh' => $don['idThuocTinhKiemTra'],
            'idToanTu' => $don['idToanTu'],
            'giaTri' => $don['giaTriSoSanh']
        ];
    } else if ($dk['loaiDieuKien'] == 'TOHOP') {
        $tohop = truy_van_mot_ban_ghi($conn, 'tohop_dieukien', 'idDieuKien', $idDieuKien);
        return [
            'type' => 'group',
            'logic' => $tohop['idToanTu'],
            'children' => [
                fetchAST($conn, $tohop['idDieuKienTrai']),
                fetchAST($conn, $tohop['idDieuKienPhai'])
            ]
        ];
    }
    return null;
}

// ======================
// XỬ LÝ AJAX LẤY CHI TIẾT QUY CHẾ ĐỂ XEM
// ======================
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_rule') {
    header('Content-Type: application/json');
    $idQuyChe = (int)($_GET['idQuyChe'] ?? 0);
    
    $qc = truy_van_mot_ban_ghi($conn, 'quyche', 'idQuyChe', $idQuyChe);
    if ($qc) {
        $dk = truy_van_mot_ban_ghi($conn, 'quyche_dieukien', 'idQuyChe', $idQuyChe);
        $tree = $dk ? fetchAST($conn, $dk['idDieuKienCuoi']) : null;
        echo json_encode(['master' => $qc, 'tree' => $tree]);
    } else {
        echo json_encode(['error' => 'Không tìm thấy quy chế']);
    }
    exit;
}

// ======================
// XỬ LÝ FORM SUBMIT (THÊM / XÓA)
// ======================
if (isPost()) {
    $data = filter();
    $action = $data['action'] ?? '';

    // LƯU QUY CHẾ MỚI
    if ($action === 'save_rule_tree') {
        $tenQuyChe = trim($data['tenQuyChe'] ?? '');
        $moTa = trim($data['moTa'] ?? '');
        $rulesJson = $_POST['rules_json'] ?? '';

        if (!empty($tenQuyChe) && !empty($rulesJson)) {
            $resQuyChe = tao_quy_che($conn, $user_id, $id_su_kien, $tenQuyChe, $loai_quy_che, $moTa);
            if ($resQuyChe['status']) {
                $idQuyChe = $resQuyChe['idQuyChe'];
                $treeData = json_decode($rulesJson, true);
                if ($treeData) {
                    $idDieuKienRoot = parseRuleNode($conn, $treeData, $user_id, $id_su_kien);
                    if ($idDieuKienRoot) {
                        gan_dieu_kien_cho_quy_che($conn, $user_id, $id_su_kien ,$idQuyChe, $idDieuKienRoot);
                    }
                }
            } else {
                die($resQuyChe['message']);
            }
        }
        header("Location: ?module=event&action=config_rules&id=$id_su_kien&loai=$loai_quy_che");
        exit;
    }
    
    // XÓA QUY CHẾ
    if ($action === 'delete_rule') {
        $id_delete = (int)($data['id_delete'] ?? 0);
        if ($id_delete > 0) {
            // Xóa bản ghi cầu nối trước để tránh lỗi Khóa ngoại
            mysqli_query($conn, "DELETE FROM quyche_dieukien WHERE idQuyChe = $id_delete");
            // Xóa quy chế
            mysqli_query($conn, "DELETE FROM quyche WHERE idQuyChe = $id_delete");
        }
        header("Location: ?module=event&action=config_rules&id=$id_su_kien&loai=$loai_quy_che");
        exit;
    }
}

// ======================
// DỮ LIỆU GIAO DIỆN
// ======================
$thuocTinhArr = _select_info($conn, 'thuoctinh_kiemtra', ['*'], ['WHERE' => ['loaiApDung', '=', $loai_quy_che, '']]) ?: [];
$compareArr = _select_info($conn, 'toantu', ['*'], ['WHERE' => ['loaiToanTu', '=', 'compare', '']]) ?: [];
$logicArr = _select_info($conn, 'toantu', ['*'], ['WHERE' => ['loaiToanTu', '=', 'logic', '']]) ?: [];

$sql_quyche = "SELECT q.*, dk.idDieuKienCuoi FROM quyche q 
               LEFT JOIN quyche_dieukien dk ON q.idQuyChe = dk.idQuyChe
               WHERE q.idSK = $id_su_kien AND q.loaiQuyChe = '" . chuan_hoa_chuoi_sql($conn, $loai_quy_che) . "' ORDER BY q.idQuyChe DESC";
$res_quyche = mysqli_query($conn, $sql_quyche);
$quyche_list = $res_quyche ? mysqli_fetch_all($res_quyche, MYSQLI_ASSOC) : [];


layout('header');
layout('navbar');
page('event/config_rules', compact(
    'id_su_kien', 'loai_quy_che', 'quyche_list',
    'thuoctinh_list', 'toantu_list', 'event'
));
layout('footer');
