<?php
if (!defined('_AUTHEN')) { die('Truy cập không hợp lệ'); }

// Đọc và xóa flash session
$_toast_msg  = $_SESSION['flash_msg']  ?? '';
$_toast_type = $_SESSION['flash_type'] ?? '';
// Bỏ qua flash đặc biệt — nop_bai_thanh_cong xử lý riêng
if ($_toast_msg === 'nop_bai_thanh_cong') {
    $_toast_msg = '';
}
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>

<!-- Toast Container — dùng chung toàn site -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"
     style="z-index: 1100">
</div>

<script>
// ─── showToast(message, type) ────────────────────────────────────────────────
// type: 'success' | 'danger' | 'warning' | 'info'
// Tự ẩn: success/info = 4s, warning/danger = 6s
function showToast(message, type) {
    type = type || 'info';

    const iconMap = {
        success : 'bi-check-circle-fill',
        danger  : 'bi-exclamation-circle-fill',
        warning : 'bi-exclamation-triangle-fill',
        info    : 'bi-info-circle-fill'
    };
    const delayMap = {
        success : 4000,
        info    : 4000,
        warning : 6000,
        danger  : 6000
    };

    const id    = 'toast-' + Date.now();
    const icon  = iconMap[type]  || iconMap.info;
    const delay = delayMap[type] || 4000;

    const html = `
        <div id="${id}" class="toast align-items-center text-bg-${type} border-0"
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icon}"></i>
                    <span>${message}</span>
                </div>
                <button type="button"
                        class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>
        </div>`;

    const container = document.getElementById('toastContainer');
    container.insertAdjacentHTML('beforeend', html);

    const toastEl = document.getElementById(id);
    const bsToast = new bootstrap.Toast(toastEl, { delay: delay });
    bsToast.show();

    // Dọn DOM sau khi ẩn xong
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// Tự động hiện toast từ flash session PHP
<?php if (!empty($_toast_msg)): ?>
document.addEventListener('DOMContentLoaded', function () {
    showToast(
        <?= json_encode(htmlspecialchars($_toast_msg), JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($_toast_type) ?>
    );
});
<?php endif; ?>
</script>
