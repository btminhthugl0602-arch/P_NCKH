<?php
if (!defined('_AUTHEN')) { die('Truy cập không hợp lệ'); }
?>

<!-- Modal Confirm — dùng chung toàn site -->
<div class="modal fade" id="globalConfirmModal" tabindex="-1"
     aria-labelledby="globalConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="confirmModalHeader">
                <h5 class="modal-title d-flex align-items-center gap-2"
                    id="globalConfirmModalLabel">
                    <i class="bi" id="confirmModalIcon"></i>
                    <span id="confirmModalTitle">Xác nhận</span>
                </h5>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                Bạn có chắc chắn muốn thực hiện thao tác này?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn" id="confirmModalBtn">
                    Xác nhận
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ─── showConfirm(options) ─────────────────────────────────────────────────────
// options = {
//   title      : 'Xác nhận xóa',                     (string, bắt buộc)
//   message    : 'Thao tác không thể hoàn tác.',      (string, bắt buộc)
//   type       : 'danger' | 'warning',                (default: 'danger')
//   confirmText: 'Xóa',                               (default: 'Xác nhận')
//   onConfirm  : () => { form.submit() }              (function, bắt buộc)
// }
function showConfirm(options) {
    const type        = options.type        || 'danger';
    const confirmText = options.confirmText || 'Xác nhận';

    const iconMap = {
        danger  : 'bi-exclamation-triangle-fill',
        warning : 'bi-exclamation-circle-fill'
    };
    const headerBgMap = {
        danger  : 'text-bg-danger',
        warning : 'text-bg-warning'
    };

    // Set nội dung
    document.getElementById('confirmModalTitle').textContent = options.title   || 'Xác nhận';
    document.getElementById('confirmModalBody').textContent  = options.message || '';
    document.getElementById('confirmModalIcon').className    = 'bi ' + (iconMap[type] || iconMap.danger);

    // Set màu header
    const header = document.getElementById('confirmModalHeader');
    header.className = 'modal-header ' + (headerBgMap[type] || headerBgMap.danger);
    if (type === 'danger') {
        header.querySelector('.btn-close').classList.add('btn-close-white');
    } else {
        header.querySelector('.btn-close').classList.remove('btn-close-white');
    }

    // Set nút confirm
    const btn = document.getElementById('confirmModalBtn');
    btn.className  = 'btn btn-' + type;
    btn.textContent = confirmText;

    // Gắn callback — clone để xóa listener cũ
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    newBtn.addEventListener('click', function () {
        bootstrap.Modal.getInstance(
            document.getElementById('globalConfirmModal')
        ).hide();
        options.onConfirm();
    });

    // Show modal
    bootstrap.Modal.getOrCreateInstance(
        document.getElementById('globalConfirmModal')
    ).show();
}
</script>
