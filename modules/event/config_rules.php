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
?>

<style>
    .condition-tag { font-size: 1.1rem; padding: 10px 15px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s;}
    .condition-tag:hover { opacity: 0.8; transform: translateY(-2px); }
    .formula-box { background: #212529; color: #fff; min-height: 60px; border-radius: 8px; padding: 15px; font-family: monospace; font-size: 1.2rem; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;}
    .token-item { background: #495057; padding: 5px 12px; border-radius: 5px; cursor: pointer; }
    .token-item.var { background: #0d6efd; }
    .token-item.op-and { background: #dc3545; }
    .token-item.op-or { background: #ffc107; color: #000; }
    .translation-box { font-size: 1.15rem; line-height: 1.8; padding: 15px; background: #e9ecef; border-left: 5px solid #0dcaf0; border-radius: 5px; }
</style>

<main class="main container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Cấu hình Quy chế & Tổ hợp Logic</h2>
        <a href="?module=event&action=view&id=<?php echo $id_su_kien; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại sự kiện
        </a>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?php echo $loai_quy_che == 'THAMGIA' ? 'active fw-bold' : ''; ?>" href="?module=event&action=config_rules&id=<?php echo $id_su_kien; ?>&loai=THAMGIA">ĐK Tham Gia</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $loai_quy_che == 'VONGTHI' ? 'active fw-bold' : ''; ?>" href="?module=event&action=config_rules&id=<?php echo $id_su_kien; ?>&loai=VONGTHI">ĐK Qua Vòng</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $loai_quy_che == 'SANPHAM' ? 'active fw-bold' : ''; ?>" href="?module=event&action=config_rules&id=<?php echo $id_su_kien; ?>&loai=SANPHAM">ĐK Sản Phẩm</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $loai_quy_che == 'GIAITHUONG' ? 'active fw-bold' : ''; ?>" href="?module=event&action=config_rules&id=<?php echo $id_su_kien; ?>&loai=GIAITHUONG">ĐK Giải Thưởng</a></li>
    </ul>

    <form method="post" id="formRules" onsubmit="return validateBeforeSubmit()">
        <input type="hidden" name="action" value="save_rule_tree">
        <input type="hidden" name="rules_json" id="rules_json" value="">
        
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Tên quy chế <span class="text-danger">*</span></label>
                <input type="text" name="tenQuyChe" id="tenQuyChe" class="form-control" placeholder="Vd: Điều kiện đạt Giải Nhất" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Mô tả chi tiết</label>
                <input type="text" name="moTa" id="moTa" class="form-control" placeholder="Mô tả nhóm đối tượng áp dụng...">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4 h-100">
                    <div class="card-header bg-dark text-white fw-bold">Bước 1: Tạo các Điều kiện đơn</div>
                    <div class="card-body bg-light">
                        <div class="mb-2">
                            <select id="tmp_ThuocTinh" class="form-select mb-2">
                                <option value="">-- Chọn thuộc tính --</option>
                                <?php foreach($thuocTinhArr as $tt): ?>
                                    <option value="<?php echo $tt['idThuocTinhKiemTra']; ?>"><?php echo htmlspecialchars($tt['tenThuocTinh']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="tmp_ToanTu" class="form-select mb-2">
                                <?php foreach($compareArr as $op): ?>
                                    <option value="<?php echo $op['idToanTu']; ?>"><?php echo htmlspecialchars($op['kyHieu'] . ' (' . $op['moTa'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" id="tmp_GiaTri" class="form-control mb-3" placeholder="Nhập giá trị so sánh...">
                            <button type="button" class="btn btn-primary w-100" onclick="addVariable()">
                                <i class="bi bi-plus-circle me-1"></i> Tạo thành Biến
                            </button>
                        </div>
                        
                        <hr>
                        <h6 class="text-muted fw-bold small">DANH SÁCH BIẾN ĐÃ TẠO:</h6>
                        <div id="variableList" class="d-flex flex-column gap-2 mt-2">
                            <div class="text-muted fst-italic small" id="emptyVarMsg">Chưa có điều kiện nào được tạo.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-success text-white fw-bold">Bước 2: Ghép nối & Phiên dịch</div>
                    <div class="card-body">
                        
                        <h6 class="fw-bold text-secondary">Thêm vào công thức:</h6>
                        <div class="d-flex gap-2 flex-wrap mb-3 p-3 bg-light rounded border">
                            <div id="variableButtons" class="d-flex gap-2 border-end pe-3"></div>
                            
                            <button type="button" class="btn btn-danger text-white fw-bold" onclick="addToken('AND')">VÀ</button>
                            <button type="button" class="btn btn-warning text-dark fw-bold" onclick="addToken('OR')">HOẶC</button>
                            <button type="button" class="btn btn-secondary fw-bold" onclick="addToken('LPAREN')">(</button>
                            <button type="button" class="btn btn-secondary fw-bold" onclick="addToken('RPAREN')">)</button>
                            
                            <button type="button" class="btn btn-outline-dark ms-auto" onclick="popToken()">
                                <i class="bi bi-backspace-fill me-1"></i> Xóa lùi
                            </button>
                        </div>

                        <h6 class="fw-bold text-secondary">Công thức Logic:</h6>
                        <div class="formula-box mb-4" id="formulaDisplay">
                            <span class="text-muted" style="font-size: 1rem;">Hãy bấm các nút phía trên để tạo công thức...</span>
                        </div>

                        <h6 class="fw-bold text-info"><i class="bi bi-robot me-2"></i>Hệ thống hiểu là:</h6>
                        <div class="translation-box" id="translationDisplay">
                            Chưa có dữ liệu phiên dịch...
                        </div>
                        
                        <div id="errorAlert" class="alert alert-danger mt-3" style="display:none;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Công thức chưa hoàn thiện, bị thiếu biến hoặc sai cú pháp ngoặc đơn!
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-success px-5 btn-lg" id="btnSubmit" disabled>
                                <i class="bi bi-save me-1"></i> Tạo Quy Chế
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <hr>
    <h4 class="mb-3">Các Quy Chế <?php echo htmlspecialchars($loai_quy_che); ?> đã tạo</h4>
    <div class="row">
        <?php if (!empty($quyche_list)): ?>
            <?php foreach ($quyche_list as $qc): ?>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 border-0 shadow-sm border-start border-4 border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title text-success mb-0"><?php echo htmlspecialchars($qc['tenQuyChe']); ?></h5>
                                
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-info me-1" onclick="viewRuleDetails(<?php echo $qc['idQuyChe']; ?>)">
                                        <i class="bi bi-eye"></i> Chi tiết
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa Quy chế này? Thao tác không thể hoàn tác.');">
                                        <input type="hidden" name="action" value="delete_rule">
                                        <input type="hidden" name="id_delete" value="<?php echo $qc['idQuyChe']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($qc['moTa'] ?: 'Không có mô tả'); ?></p>
                            <span class="badge bg-secondary"><i class="bi bi-braces me-1"></i>Cây ID: <?php echo $qc['idDieuKienCuoi'] ?: 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info">Chưa có quy chế nào.</div></div>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="modalViewRule" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-eye-fill me-2"></i>Chi tiết cấu hình Logic</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 id="viewRuleTitle" class="text-primary mb-1">Tên quy chế</h4>
                <p id="viewRuleDesc" class="text-muted mb-4">Mô tả quy chế</p>
                
                <h6 class="fw-bold text-secondary border-bottom pb-2">Hệ thống phân tích cấu trúc như sau:</h6>
                <div id="viewRuleContent" class="translation-box mt-3" style="line-height: 2;">
                    <div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Đang tải dữ liệu...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng cửa sổ</button>
            </div>
        </div>
    </div>
</div>

<script>
    const thuocTinhList = <?php echo json_encode($thuocTinhArr); ?>;
    const compareList = <?php echo json_encode($compareArr); ?>;
    const logicList = <?php echo json_encode($logicArr); ?>;
    const logicIdAnd = logicList.find(l => l.kyHieu.toUpperCase() === 'AND')?.idToanTu || 6;
    const logicIdOr = logicList.find(l => l.kyHieu.toUpperCase() === 'OR')?.idToanTu || 7;

    let conditionsMap = {}; 
    let tokens = []; 
    let nextCharCode = 65; 

    // --- CÁC HÀM XỬ LÝ FORM TẠO MỚI ---
    function addVariable() {
        const selTT = document.getElementById('tmp_ThuocTinh');
        const selOp = document.getElementById('tmp_ToanTu');
        const inputVal = document.getElementById('tmp_GiaTri');

        if (!selTT.value || !inputVal.value.trim()) {
            alert("Vui lòng chọn Thuộc tính và nhập Giá trị!");
            return;
        }

        const ttText = selTT.options[selTT.selectedIndex].text;
        const opText = selOp.options[selOp.selectedIndex].text.split(' ')[0]; 
        const valText = inputVal.value.trim();

        const key = String.fromCharCode(nextCharCode++); 
        const textDisplay = `${ttText} ${opText} ${valText}`;

        conditionsMap[key] = {
            idThuocTinh: selTT.value,
            idToanTu: selOp.value,
            giaTri: valText,
            text: textDisplay
        };

        document.getElementById('emptyVarMsg').style.display = 'none';
        const varList = document.getElementById('variableList');
        varList.innerHTML += `<div class="p-2 border rounded bg-white"><span class="badge bg-primary fs-6 me-2">${key}</span> ${textDisplay}</div>`;

        const btnBox = document.getElementById('variableButtons');
        btnBox.innerHTML += `<button type="button" class="btn btn-primary fw-bold" onclick="addToken('${key}')">${key}</button>`;

        inputVal.value = '';
    }

    function addToken(type) { tokens.push(type); updateInterface(); }
    function popToken() { tokens.pop(); updateInterface(); }

    function updateInterface() {
        const fBox = document.getElementById('formulaDisplay');
        const tBox = document.getElementById('translationDisplay');
        const errBox = document.getElementById('errorAlert');
        const btnSubmit = document.getElementById('btnSubmit');

        if (tokens.length === 0) {
            fBox.innerHTML = '<span class="text-muted" style="font-size: 1rem;">Hãy bấm các nút phía trên để tạo công thức...</span>';
            tBox.innerHTML = 'Chưa có dữ liệu phiên dịch...';
            errBox.style.display = 'none';
            btnSubmit.disabled = true;
            return;
        }

        let fHtml = ''; let tHtml = '';

        tokens.forEach(t => {
            if (conditionsMap[t]) {
                fHtml += `<div class="token-item var">${t}</div>`;
                tHtml += `<span class="badge bg-primary fs-6 mx-1">${conditionsMap[t].text}</span>`;
            } else if (t === 'AND') {
                fHtml += `<div class="token-item op-and">VÀ</div>`;
                tHtml += `<strong class="text-danger mx-2">VÀ</strong>`;
            } else if (t === 'OR') {
                fHtml += `<div class="token-item op-or">HOẶC</div>`;
                tHtml += `<strong class="text-warning mx-2">HOẶC</strong>`;
            } else if (t === 'LPAREN') {
                fHtml += `<div class="token-item fs-5">(</div>`;
                tHtml += `<span class="fs-4 fw-bold text-secondary me-1">(</span>`;
            } else if (t === 'RPAREN') {
                fHtml += `<div class="token-item fs-5">)</div>`;
                tHtml += `<span class="fs-4 fw-bold text-secondary ms-1">)</span>`;
            }
        });

        fBox.innerHTML = fHtml; tBox.innerHTML = tHtml;

        const ast = buildAstFromTokens();
        if (ast) {
            errBox.style.display = 'none';
            btnSubmit.disabled = false;
            document.getElementById('rules_json').value = JSON.stringify(ast);
        } else {
            errBox.style.display = 'block';
            btnSubmit.disabled = true;
        }
    }

    function buildAstFromTokens() {
        if (tokens.length === 0) return null;
        let pos = 0;
        function parseExpression() {
            let left = parseTerm();
            if (!left) return null;
            while (pos < tokens.length && tokens[pos] === 'OR') {
                pos++; let right = parseTerm();
                if (!right) return null;
                left = { type: 'group', logic: logicIdOr, children: [left, right] };
            }
            return left;
        }
        function parseTerm() {
            let left = parseFactor();
            if (!left) return null;
            while (pos < tokens.length && tokens[pos] === 'AND') {
                pos++; let right = parseFactor();
                if (!right) return null;
                left = { type: 'group', logic: logicIdAnd, children: [left, right] };
            }
            return left;
        }
        function parseFactor() {
            if (pos >= tokens.length) return null;
            let current = tokens[pos];
            if (current === 'LPAREN') {
                pos++; let node = parseExpression();
                if (pos < tokens.length && tokens[pos] === 'RPAREN') {
                    pos++; return node;
                }
                return null;
            } else if (conditionsMap[current]) {
                let cond = conditionsMap[current]; pos++;
                return { type: 'rule', idThuocTinh: cond.idThuocTinh, idToanTu: cond.idToanTu, giaTri: cond.giaTri };
            }
            return null;
        }
        let ast = parseExpression();
        if (pos < tokens.length) return null; 
        return ast;
    }
    function validateBeforeSubmit() { return !document.getElementById('btnSubmit').disabled; }


    // --- HÀM XỬ LÝ XEM CHI TIẾT (VIEW DETAILS) ---
    function viewRuleDetails(idQuyChe) {
        // Mở Modal ngay lập tức hiển thị Loading
        const viewModal = new bootstrap.Modal(document.getElementById('modalViewRule'));
        document.getElementById('viewRuleTitle').innerText = "Đang tải dữ liệu...";
        document.getElementById('viewRuleDesc').innerText = "";
        document.getElementById('viewRuleContent').innerHTML = '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Đang dịch logic...</div>';
        viewModal.show();

        // Gọi AJAX lấy Tree logic
        fetch(`?module=event&action=config_rules&id=<?php echo $id_su_kien; ?>&loai=<?php echo $loai_quy_che; ?>&ajax_action=get_rule&idQuyChe=${idQuyChe}`)
        .then(res => res.json())
        .then(data => {
            if(data && data.master) {
                document.getElementById('viewRuleTitle').innerText = data.master.tenQuyChe;
                document.getElementById('viewRuleDesc').innerText = data.master.moTa || 'Không có mô tả chi tiết.';
                
                if (data.tree) {
                    const htmlContent = parseAstToHtmlString(data.tree);
                    document.getElementById('viewRuleContent').innerHTML = htmlContent;
                } else {
                    document.getElementById('viewRuleContent').innerHTML = '<span class="text-muted">Quy chế này không có bất kỳ điều kiện nào (Quy chế rỗng).</span>';
                }
            } else {
                document.getElementById('viewRuleContent').innerHTML = '<span class="text-danger">Đã xảy ra lỗi khi đọc dữ liệu.</span>';
            }
        });
    }

    // Hàm đệ quy dịch từ JSON Tree ra chuỗi HTML trực quan
    function parseAstToHtmlString(node) {
        if (!node) return '';
        
        if (node.type === 'rule') {
            const ttObj = thuocTinhList.find(t => t.idThuocTinhKiemTra == node.idThuocTinh);
            const opObj = compareList.find(o => o.idToanTu == node.idToanTu);
            
            const ttText = ttObj ? ttObj.tenThuocTinh : 'Thuộc tính';
            const opText = opObj ? opObj.kyHieu : '?';
            
            return `<span class="badge bg-primary px-3 py-2 fs-6 mb-1 mx-1" style="white-space: normal;">${ttText} ${opText} ${node.giaTri}</span>`;
        } 
        else if (node.type === 'group') {
            const logicStr = (node.logic == logicIdAnd) 
                ? '<strong class="text-danger mx-2 fs-5">VÀ</strong>' 
                : '<strong class="text-warning mx-2 fs-5">HOẶC</strong>';
            
            const leftStr = parseAstToHtmlString(node.children[0]);
            const rightStr = parseAstToHtmlString(node.children[1]);
            
            return `<span class="fw-bold text-secondary fs-4">(</span> ${leftStr} ${logicStr} ${rightStr} <span class="fw-bold text-secondary fs-4">)</span>`;
        }
        return '';
    }
</script>

<?php layout('footer'); ?>