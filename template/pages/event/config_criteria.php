
<main class="main container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Cấu hình Bộ tiêu chí & Chấm điểm</h2>
        <a href="?module=event&action=view&id=<?php echo $id_su_kien; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại sự kiện
        </a>
    </div>

    <datalist id="criteriaBank">
        <?php while ($tc = mysqli_fetch_assoc($nganhang_tc)) : ?>
            <option value="<?php echo htmlspecialchars($tc['noiDungTieuChi']); ?>">
        <?php endwhile; ?>
    </datalist>

    <div class="card shadow-sm mb-5 border-0">
        <div class="card-header bg-primary text-white" id="formHeader">
            <h5 class="mb-0" id="formTitle"><i class="bi bi-clipboard-plus me-2"></i>Tạo Phiếu Chấm Điểm</h5>
        </div>
        <div class="card-body">
            
            <div class="mb-4 p-3 bg-light rounded border" id="cloneToolBlock">
                <label class="form-label fw-bold text-secondary mb-2"><i class="bi bi-copy me-1"></i> Tải nhanh một Bộ Tiêu Chí đã có trong hệ thống:</label>
                <div class="input-group">
                    <select id="reuseSetDropdown" class="form-select">
                        <option value="">-- Chọn bộ tiêu chí để nhân bản --</option>
                        <?php while ($bo_drop = mysqli_fetch_assoc($bo_dropdown_list)): ?>
                            <option value="<?php echo $bo_drop['idBoTieuChi']; ?>">
                                <?php echo htmlspecialchars($bo_drop['tenBoTieuChi']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" class="btn btn-secondary" onclick="loadExistingSet()">
                        <i class="bi bi-cloud-download me-1"></i> Nhân bản vào Form
                    </button>
                </div>
                <span id="errBoCriteria" class="text-danger small d-none"></span>
            </div>

            <form method="post" id="formCriteria">
                <input type="hidden" name="action" value="save_criteria">
                <input type="hidden" name="edit_id" id="edit_id" value="0">
                
                <div class="row mb-4">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Tên bộ tiêu chí <span class="text-danger">*</span></label>
                        <input type="text" name="tenBoTieuChi" id="tenBoTieuChi" class="form-control" placeholder="Ví dụ: Phiếu chấm Vòng Bán Kết" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Áp dụng cho Vòng thi</label>
                        <select name="idVongThi" id="idVongThi" class="form-select">
                            <option value="0">-- Chưa gán (Lưu lại dùng sau) --</option>
                            <?php mysqli_data_seek($vong_list, 0); while ($row = mysqli_fetch_assoc($vong_list)) : ?>
                                <option value="<?php echo $row['idVongThi']; ?>">
                                    <?php echo htmlspecialchars($row['tenVongThi']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Mô tả ngắn</label>
                        <input type="text" name="moTa" id="moTa" class="form-control" placeholder="Mô tả bộ tiêu chí...">
                    </div>
                </div>

                <hr class="mb-4">

                <h6 class="fw-bold mb-3">Danh sách các tiêu chí (Gõ để tìm kiếm từ Ngân hàng)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="criteriaTable">
                        <thead class="table-light">
                            <tr>
                                <th>Nội dung tiêu chí <span class="text-danger">*</span></th>
                                <th width="160">Điểm tối đa (Tùy chọn)</th>
                                <th width="150">Trọng số (Tỷ trọng) <span class="text-danger">*</span></th>
                                <th width="60" class="text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-outline-success" onclick="addCriteriaRow()">
                        <i class="bi bi-plus-circle me-1"></i>Thêm tiêu chí
                    </button>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2 d-none" id="btnCancelEdit" onclick="resetForm()">
                            Hủy cập nhật
                        </button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSubmit">
                            <i class="bi bi-save me-1"></i>Lưu toàn bộ Phiếu chấm
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <h4 class="mb-3">Bộ tiêu chí đang áp dụng cho Sự kiện này</h4>
    <div class="row">
        <?php if ($bo_list && mysqli_num_rows($bo_list) > 0): ?>
            <?php while ($bo = mysqli_fetch_assoc($bo_list)): ?>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 border-0 shadow-sm border-start border-4 border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title text-primary mb-0"><?php echo htmlspecialchars($bo['tenBoTieuChi']); ?></h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="editSet(<?php echo $bo['idBoTieuChi']; ?>)">
                                    <i class="bi bi-pencil-square me-1"></i> Sửa
                                </button>
                            </div>
                            <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($bo['moTa'] ?: 'Không có mô tả'); ?></p>
                            <?php if ($bo['tenVongThi']): ?>
                                <span class="badge bg-success">Đang áp dụng: <?php echo htmlspecialchars($bo['tenVongThi']); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Chưa gán vòng thi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">Sự kiện này chưa có Bộ tiêu chí nào.</div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        addCriteriaRow();
    });

    function addCriteriaRow(noidung = '', diem = '', tytrong = '1.0') {
        const tbody = document.querySelector('#criteriaTable tbody');
        const tr = document.createElement('tr');
        const diemValue = (diem !== null && diem !== '') ? diem : '';

        tr.innerHTML = `
            <td><input type="text" name="tieuchi_noidung[]" list="criteriaBank" class="form-control" placeholder="Gõ để tìm hoặc nhập mới..." autocomplete="off" required value="${noidung}"></td>
            <td><input type="number" step="0.5" name="tieuchi_diem[]" class="form-control" placeholder="Để trống = NULL" value="${diemValue}"></td>
            <td><input type="number" step="0.1" name="tieuchi_tytrong[]" class="form-control" required value="${tytrong}"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    }

    function removeRow(btn) {
        const tr = btn.closest('tr');
        tr.remove();
    }

    // --- HÀM AJAX: TẢI BỘ TIÊU CHÍ ĐỂ NHÂN BẢN (CLONE) ---
    function loadExistingSet() {
        const select = document.getElementById('reuseSetDropdown');
        const idBo = select.value;
        const tenBoGoc = select.options[select.selectedIndex].text;
        if (!idBo) {
            const errEl = document.getElementById('errBoCriteria');
            errEl.textContent = 'Vui lòng chọn một Bộ tiêu chí!';
            errEl.classList.remove('d-none');
            return;
        }
        document.getElementById('errBoCriteria').classList.add('d-none');

        fetch(`?module=event&action=config_criteria&id=<?php echo $id_su_kien; ?>&ajax_action=get_full_set&idBo=${idBo}`)
            .then(response => response.json())
            .then(data => {
                resetForm(); // Xóa trạng thái edit nếu đang có
                if (data && data.details) {
                    document.getElementById('tenBoTieuChi').value = tenBoGoc.trim() + ' (Bản sao)';
                    document.getElementById('moTa').value = data.master.moTa || '';
                    
                    const tbody = document.querySelector('#criteriaTable tbody');
                    tbody.innerHTML = '';
                    data.details.forEach(item => {
                        addCriteriaRow(item.noiDungTieuChi, item.diemToiDa, item.tyTrong);
                    });
                    document.getElementById('formCriteria').scrollIntoView({ behavior: 'smooth' });
                }
            });
    }

    // --- HÀM AJAX: TẢI BỘ TIÊU CHÍ ĐỂ CHỈNH SỬA (UPDATE) ---
    function editSet(idBo) {
        fetch(`?module=event&action=config_criteria&id=<?php echo $id_su_kien; ?>&ajax_action=get_full_set&idBo=${idBo}`)
            .then(response => response.json())
            .then(data => {
                if(data && data.master) {
                    // Set các giá trị vào Form
                    document.getElementById('edit_id').value = idBo;
                    document.getElementById('tenBoTieuChi').value = data.master.tenBoTieuChi;
                    document.getElementById('moTa').value = data.master.moTa || '';
                    document.getElementById('idVongThi').value = data.master.idVongThi || 0;
                    
                    // Render danh sách tiêu chí con
                    const tbody = document.querySelector('#criteriaTable tbody');
                    tbody.innerHTML = '';
                    if(data.details && data.details.length > 0) {
                        data.details.forEach(item => {
                            addCriteriaRow(item.noiDungTieuChi, item.diemToiDa, item.tyTrong);
                        });
                    } else {
                        addCriteriaRow();
                    }
                    
                    // Đổi giao diện UI sang chế độ Cập nhật
                    document.getElementById('formHeader').classList.replace('bg-primary', 'bg-warning');
                    document.getElementById('formHeader').classList.replace('text-white', 'text-dark');
                    document.getElementById('formTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Cập Nhật Phiếu Chấm Điểm';
                    
                    document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-check-circle me-1"></i>Lưu Cập Nhật';
                    document.getElementById('btnSubmit').classList.replace('btn-primary', 'btn-warning');
                    document.getElementById('btnCancelEdit').classList.remove('d-none');
                    document.getElementById('cloneToolBlock').classList.add('d-none'); // Ẩn tool nhân bản khi đang sửa
                    
                    // Cuộn lên form
                    document.getElementById('formCriteria').scrollIntoView({ behavior: 'smooth' });
                }
            });
    }

    // --- HÀM RESET FORM VỀ TRẠNG THÁI TẠO MỚI ---
    function resetForm() {
        document.getElementById('edit_id').value = '0';
        document.getElementById('tenBoTieuChi').value = '';
        document.getElementById('moTa').value = '';
        document.getElementById('idVongThi').value = '0';
        
        document.querySelector('#criteriaTable tbody').innerHTML = '';
        addCriteriaRow();
        
        // Trả UI về mặc định
        document.getElementById('formHeader').classList.replace('bg-warning', 'bg-primary');
        document.getElementById('formHeader').classList.replace('text-dark', 'text-white');
        document.getElementById('formTitle').innerHTML = '<i class="bi bi-clipboard-plus me-2"></i>Tạo Phiếu Chấm Điểm';
        
        document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-save me-1"></i>Lưu toàn bộ Phiếu chấm';
        document.getElementById('btnSubmit').classList.replace('btn-warning', 'btn-primary');
        document.getElementById('btnCancelEdit').classList.add('d-none');
        document.getElementById('cloneToolBlock').classList.remove('d-none');
    }
</script>

<?php layout('footer'); ?>
