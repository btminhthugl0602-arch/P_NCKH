<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/base.php';
require_once _PATH_URL . '/modules/functions/quan_ly_to_chuc.php';

$id_sk  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_ttk = (int)($_SESSION['user_id'] ?? 0);

// Guard: chỉ BTC / admin mới vào được
if (
    !kiem_tra_quyen_su_kien($conn, $id_ttk, $id_sk, 'cauhinh_sukien')
    && !kiem_tra_quyen_he_thong($conn, $id_ttk, 'tao_su_kien')
) {
    header('Location: ?module=event&action=view&id=' . $id_sk);
    exit;
}

$success_msg = '';
$error_msg   = '';

// ============================================================
// XỬ LÝ POST
// ============================================================
if (isPost()) {
    $data   = filter();
    $action = $data['action'] ?? '';

    if ($action === 'them_vaitro') {
        $id_tk_target = (int)($data['idTK'] ?? 0);
        $id_vaitro    = (int)($data['idVaiTro'] ?? 0);
        if ($id_tk_target > 0 && $id_vaitro > 0) {
            $result = them_vaitro_sukien($conn, $id_ttk, $id_sk, $id_tk_target, $id_vaitro);
            $result['status'] ? ($success_msg = $result['message']) : ($error_msg = $result['message']);
        } else {
            $error_msg = 'Vui lòng chọn đầy đủ người dùng và vai trò.';
        }
    }

    if ($action === 'thu_hoi') {
        $id_tk_target = (int)($data['idTK'] ?? 0);
        $id_vaitro    = (int)($data['idVaiTro'] ?? 0);
        if ($id_tk_target > 0 && $id_vaitro > 0) {
            $result = thu_hoi_vaitro_btc($conn, $id_ttk, $id_sk, $id_tk_target, $id_vaitro);
            $result['status'] ? ($success_msg = $result['message']) : ($error_msg = $result['message']);
        }
    }
}

// ============================================================
// DỮ LIỆU ĐỔ RA GIAO DIỆN
// ============================================================
$sk = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_sk);
if (!$sk) {
    header('Location: ?module=event&action=index');
    exit;
}

$ds_thanh_vien  = lay_danh_sach_thanh_vien_sukien($conn, $id_sk);
$ds_vaitro_gan  = lay_vaitro_btc_co_the_gan($conn);

// Tìm kiếm tài khoản (AJAX) — nhận ?search_user=keyword, trả JSON
if (isset($_GET['search_user'])) {
    header('Content-Type: application/json');
    $kw   = '%' . trim($_GET['search_user']) . '%';
    $stmt = mysqli_prepare(
        $conn,
        "SELECT tk.idTK,
                COALESCE(gv.tenGV, sv.tenSV, tk.tenTK) AS tenHienThi,
                tk.tenTK AS email,
                CASE tk.idLoaiTK WHEN 1 THEN 'Admin' WHEN 2 THEN 'GV' ELSE 'SV' END AS loai
         FROM taikhoan tk
         LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
         LEFT JOIN sinhvien  sv ON tk.idTK = sv.idTK
         WHERE tk.isActive = 1
           AND (COALESCE(gv.tenGV, sv.tenSV, tk.tenTK) LIKE ? OR tk.tenTK LIKE ?)
         LIMIT 15"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $kw, $kw);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    echo json_encode($rows);
    exit;
}

// Nhãn nguonTao thân thiện
function nhan_nguon_tao(string $nguon): string
{
    return match ($nguon) {
        'BTC_THEM'      => '<span class="badge bg-primary">BTC gán</span>',
        'DANG_KY'       => '<span class="badge bg-success">Đăng ký</span>',
        'QUA_NHOM'      => '<span class="badge bg-info text-dark">Qua nhóm</span>',
        'PHANCONG_CHAM' => '<span class="badge bg-warning text-dark">P/c chấm</span>',
        default         => '<span class="badge bg-secondary">' . htmlspecialchars($nguon) . '</span>',
    };
}

layout('header');
layout('navbar');
?>

<main class="main container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>Quản lý thành viên sự kiện</h2>
            <small class="text-muted"><?php echo htmlspecialchars($sk['tenSK']); ?></small>
        </div>
        <a href="?module=event&action=view&id=<?php echo $id_sk; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại sự kiện
        </a>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ===================== FORM THÊM ===================== -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Gán vai trò thủ công</h6>
                </div>
                <div class="card-body">
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="action" value="them_vaitro">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tìm người dùng <span class="text-danger">*</span></label>
                            <input type="text" id="searchUser" class="form-control" placeholder="Nhập tên hoặc email…" autocomplete="off">
                            <div id="searchResults" class="list-group mt-1 shadow-sm" style="position:absolute;z-index:1000;width:calc(100% - 3rem);display:none;"></div>
                            <input type="hidden" name="idTK" id="selectedIdTK">
                            <div id="selectedUserDisplay" class="mt-2 text-success fw-semibold small"></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Vai trò <span class="text-danger">*</span></label>
                            <select name="idVaiTro" class="form-select" required>
                                <option value="">-- Chọn vai trò --</option>
                                <?php foreach ($ds_vaitro_gan as $vt): ?>
                                    <option value="<?php echo $vt['idvatro']; ?>">
                                        <?php echo htmlspecialchars($vt['tenvaitro']); ?>
                                        <?php if ($vt['mota']): ?> — <small><?php echo htmlspecialchars($vt['mota']); ?></small><?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Chỉ hiển thị vai trò BTC được phép gán thủ công.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i> Gán vai trò
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===================== DANH SÁCH ===================== -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-list-ul me-2 text-primary"></i>
                        Danh sách thành viên (<?php echo count($ds_thanh_vien); ?>)
                    </h6>
                    <small class="text-muted">Tất cả vai trò active trong sự kiện</small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ds_thanh_vien)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-people display-4 d-block mb-2 opacity-25"></i>
                            Chưa có thành viên nào.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tên</th>
                                        <th>Vai trò</th>
                                        <th>Nguồn</th>
                                        <th>Ngày cấp</th>
                                        <th class="text-center">Thu hồi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ds_thanh_vien as $tv): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($tv['tenHienThi']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                                                    <?php echo htmlspecialchars($tv['tenvaitro']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo nhan_nguon_tao($tv['nguonTao']); ?></td>
                                            <td class="text-muted small">
                                                <?php echo $tv['ngayCap'] ? date('d/m/Y', strtotime($tv['ngayCap'])) : '—'; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($tv['nguonTao'] === 'BTC_THEM' && (int)$tv['idTK'] !== $id_ttk): ?>
                                                    <form method="post" id="formThuHoi_<?php echo $tv['idRecord']; ?>">
                                                        <input type="hidden" name="action" value="thu_hoi">
                                                        <input type="hidden" name="idTK" value="<?php echo $tv['idTK']; ?>">
                                                        <input type="hidden" name="idVaiTro" value="<?php echo $tv['idVaiTro']; ?>">
                                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill"
                                                            onclick="showConfirm({
                                                                title: 'Thu hồi vai trò',
                                                                message: 'Thu hồi vai trò <strong><?php echo htmlspecialchars($tv['tenvaitro']); ?></strong> của <strong><?php echo htmlspecialchars($tv['tenHienThi']); ?></strong>?',
                                                                type: 'danger',
                                                                confirmText: 'Thu hồi',
                                                                onConfirm: () => document.getElementById('formThuHoi_<?php echo $tv['idRecord']; ?>').submit()
                                                            })">
                                                            <i class="bi bi-person-dash-fill"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($tv['nguonTao'] !== 'BTC_THEM'): ?>
                                                    <span class="text-muted small fst-italic">Tự động</span>
                                                <?php else: ?>
                                                    <span class="text-muted small fst-italic">(Bạn)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</main>

<script>
    // ============================================================
    // Tìm kiếm người dùng live (AJAX — không cần library ngoài)
    // ============================================================
    (function() {
        const searchInput = document.getElementById('searchUser');
        const resultsDiv = document.getElementById('searchResults');
        const hiddenIdTK = document.getElementById('selectedIdTK');
        const displayDiv = document.getElementById('selectedUserDisplay');
        let debounceTimer;

        searchInput.addEventListener('input', function() {
            const kw = this.value.trim();
            hiddenIdTK.value = '';
            displayDiv.textContent = '';
            clearTimeout(debounceTimer);
            if (kw.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch('?module=event&action=config_btc&id=<?php echo $id_sk; ?>&search_user=' + encodeURIComponent(kw))
                    .then(r => r.json())
                    .then(data => {
                        resultsDiv.innerHTML = '';
                        if (!data.length) {
                            resultsDiv.innerHTML = '<div class="list-group-item text-muted small">Không tìm thấy kết quả.</div>';
                        } else {
                            data.forEach(u => {
                                const item = document.createElement('button');
                                item.type = 'button';
                                item.className = 'list-group-item list-group-item-action py-2';
                                item.innerHTML = `<strong>${u.tenHienThi}</strong> <small class="text-muted">(${u.loai}) — ${u.email}</small>`;
                                item.addEventListener('click', () => {
                                    hiddenIdTK.value = u.idTK;
                                    searchInput.value = u.tenHienThi;
                                    displayDiv.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Đã chọn: <strong>${u.tenHienThi}</strong>`;
                                    resultsDiv.style.display = 'none';
                                });
                                resultsDiv.appendChild(item);
                            });
                        }
                        resultsDiv.style.display = 'block';
                    });
            }, 300);
        });

        // Đóng khi click ra ngoài
        document.addEventListener('click', e => {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    })();
</script>

<?php layout('footer'); ?>