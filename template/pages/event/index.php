<?php
/**
 * View: event/index.php
 * Variables: $can_create_event, $event_error, $events, $search,
 *            $filter_cap, $filter_time, $loaicaps, $all_caps, $caps_by_loai,
 *            $templates, $upcoming_events, $cur_page, $total_pages,
 *            $total_count
 */

function event_page_url(int $page, string $search, int $filter_cap, string $filter_time): string
{
    $p = ['module' => 'event', 'action' => 'index', 'page' => $page];
    if (!empty($search))      $p['search']      = $search;
    if ($filter_cap > 0)      $p['filter_cap']  = $filter_cap;
    if (!empty($filter_time)) $p['filter_time'] = $filter_time;
    return '?' . http_build_query($p);
}
?>

<?php if ($can_create_event): ?>
<!-- ── MODAL TẠO SỰ KIỆN ──────────────────────────────────────────── -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="enrollment-form-wrapper">
                    <div class="enrollment-header text-center mb-3 pt-4 px-4 position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                        <h2>Tạo sự kiện mới</h2>
                        <p class="text-muted mb-0">Các trường ngày có thể để trống và cấu hình sau.</p>
                    </div>

                    <form class="enrollment-form px-4 pb-4" id="createEventForm"
                        action="<?= _HOST_URL ?>/?module=event&action=index" method="post" novalidate>
                        <?= csrfInput() ?>

                        <?php if (!empty($event_error)): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                                <div><?= $event_error ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($templates)): ?>
                            <div class="mb-4">
                                <label for="templateSelect" class="form-label fw-semibold">
                                    <i class="bi bi-copy me-1 text-secondary"></i>Sao chép từ sự kiện cũ
                                    <span class="text-muted fw-normal small">(tuỳ chọn)</span>
                                </label>
                                <select class="form-select" id="templateSelect">
                                    <option value="">-- Bắt đầu từ đầu --</option>
                                    <?php foreach ($templates as $tpl): ?>
                                        <option value="<?= (int)$tpl['idSK'] ?>"
                                            data-ten="<?= htmlspecialchars($tpl['tenSK']) ?>"
                                            data-mota="<?= htmlspecialchars($tpl['moTa'] ?? '') ?>"
                                            data-idcap="<?= (int)($tpl['idCap'] ?? 0) ?>"
                                            data-idloaicap="<?= (int)($tpl['idLoaiCap'] ?? 0) ?>">
                                            <?= htmlspecialchars($tpl['tenSK']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Tự động điền tên, mô tả, cấp tổ chức — ngày tháng để trống.</div>
                            </div>
                            <hr class="my-3">
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="tenSK" class="form-label">Tên sự kiện <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tenSK" name="tenSK"
                                placeholder="Nhập tên sự kiện..." required maxlength="300"
                                value="<?= htmlspecialchars($_POST['tenSK'] ?? '') ?>">
                            <div class="invalid-feedback">Vui lòng nhập tên sự kiện.</div>
                            <div id="nameWarning" class="alert alert-warning d-flex align-items-center gap-2 mt-2 py-2 d-none">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                                <div id="nameWarningText"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="moTa" class="form-label">Mô tả sự kiện</label>
                            <textarea class="form-control" id="moTa" name="moTa" rows="3"
                                placeholder="Mục tiêu và nội dung của sự kiện..."><?= htmlspecialchars($_POST['moTa'] ?? '') ?></textarea>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="idLoaiCap" class="form-label">Cấp tổ chức</label>
                                <select class="form-select" id="idLoaiCap" name="idLoaiCap">
                                    <option value="">-- Chọn cấp --</option>
                                    <?php foreach ($loaicaps as $lc): ?>
                                        <option value="<?= (int)$lc['idLoaiCap'] ?>"
                                            <?= (($_POST['idLoaiCap'] ?? '') == $lc['idLoaiCap']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lc['tenLoaiCap']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="idCap" class="form-label">Đơn vị tổ chức</label>
                                <select class="form-select" id="idCap" name="idCap" disabled>
                                    <option value="">-- Chọn cấp trước --</option>
                                </select>
                            </div>
                        </div>

                        <p class="fw-semibold mb-2 text-secondary small text-uppercase">
                            <i class="bi bi-calendar-check me-1"></i>Thời gian đăng ký
                            <span class="fw-normal text-muted">(tuỳ chọn)</span>
                        </p>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="ngayMoDangKy" class="form-label">Mở đăng ký</label>
                                <input type="datetime-local" class="form-control" id="ngayMoDangKy" name="ngayMoDangKy"
                                    value="<?= htmlspecialchars($_POST['ngayMoDangKy'] ?? '') ?>">
                                <div class="invalid-feedback" id="err-ngayMoDangKy"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="ngayDongDangKy" class="form-label">Đóng đăng ký</label>
                                <input type="datetime-local" class="form-control" id="ngayDongDangKy" name="ngayDongDangKy"
                                    value="<?= htmlspecialchars($_POST['ngayDongDangKy'] ?? '') ?>">
                                <div class="invalid-feedback" id="err-ngayDongDangKy"></div>
                            </div>
                        </div>

                        <p class="fw-semibold mb-2 text-secondary small text-uppercase">
                            <i class="bi bi-calendar-event me-1"></i>Thời gian sự kiện
                            <span class="fw-normal text-muted">(tuỳ chọn)</span>
                        </p>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="ngayBatDau" class="form-label">Bắt đầu</label>
                                <input type="datetime-local" class="form-control" id="ngayBatDau" name="ngayBatDau"
                                    value="<?= htmlspecialchars($_POST['ngayBatDau'] ?? '') ?>">
                                <div class="invalid-feedback" id="err-ngayBatDau"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="ngayKetThuc" class="form-label">Kết thúc</label>
                                <input type="datetime-local" class="form-control" id="ngayKetThuc" name="ngayKetThuc"
                                    value="<?= htmlspecialchars($_POST['ngayKetThuc'] ?? '') ?>">
                                <div class="invalid-feedback" id="err-ngayKetThuc"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-2 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <div class="d-flex gap-2">
                                <button type="submit" name="action_type" value="draft" class="btn btn-outline-primary">
                                    <i class="bi bi-save me-1"></i>Lưu nháp
                                </button>
                                <button type="submit" name="action_type" value="publish" class="btn btn-primary btn-enroll">
                                    <i class="bi bi-send me-1"></i>Tạo &amp; Công khai
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<main class="main">
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">
                <?= $can_create_event ? 'Quản lý sự kiện' : 'Sự kiện' ?>
            </h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
                    <li class="current">Events</li>
                </ol>
            </nav>
        </div>
    </div>

    <section id="courses-events" class="courses-events section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row">

                <!-- Danh sách sự kiện -->
                <div class="col-lg-8">

                    <?php if (empty($events)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x" style="font-size:3rem;color:#ccc;"></i>
                            <p class="mt-3 text-muted">
                                Không tìm thấy sự kiện nào<?= !empty($search)
                                    ? ' cho từ khóa "<strong>' . htmlspecialchars($search) . '</strong>"' : '' ?>.
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($events as $event):
                        $ts_now        = time();
                        $ts_batDau     = !empty($event['ngayBatDau'])     ? strtotime($event['ngayBatDau'])     : 0;
                        $ts_ketThuc    = !empty($event['ngayKetThuc'])    ? strtotime($event['ngayKetThuc'])    : 0;
                        $ts_moDangKy   = !empty($event['ngayMoDangKy'])   ? strtotime($event['ngayMoDangKy'])   : 0;
                        $ts_dongDangKy = !empty($event['ngayDongDangKy']) ? strtotime($event['ngayDongDangKy']) : 0;

                        if ($event['isActive'] == 0) {
                            $trangThai = 'Bản nháp'; $badgeClass = 'bg-secondary';
                        } elseif ($ts_moDangKy && $ts_dongDangKy) {
                            if ($ts_now < $ts_moDangKy)          { $trangThai = 'Sắp mở đăng ký';    $badgeClass = 'bg-info text-dark'; }
                            elseif ($ts_now <= $ts_dongDangKy)   { $trangThai = 'Đang mở đăng ký';   $badgeClass = 'bg-success'; }
                            elseif ($ts_batDau === 0 || $ts_now < $ts_batDau) { $trangThai = 'Đã đóng đăng ký'; $badgeClass = 'bg-warning text-dark'; }
                            elseif ($ts_ketThuc === 0 || $ts_now <= $ts_ketThuc) { $trangThai = 'Đang diễn ra'; $badgeClass = 'bg-primary'; }
                            else { $trangThai = 'Đã kết thúc'; $badgeClass = 'bg-secondary'; }
                        } else {
                            $trangThai = 'Chưa cấu hình đăng ký'; $badgeClass = 'bg-light text-dark border';
                        }

                        $ngayBatDau_f  = $ts_batDau  ? date('d/m/Y', $ts_batDau)  : 'N/A';
                        $ngayKetThuc_f = $ts_ketThuc ? date('d/m/Y', $ts_ketThuc) : 'N/A';
                    ?>
                        <article class="event-card" data-aos="fade-up" data-aos-delay="200">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <div class="event-image position-relative">
                                        <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/events-3.webp"
                                            class="img-fluid" alt="Event Image">
                                        <div class="date-badge">
                                            <span class="day"><?= $ts_batDau ? date('d', $ts_batDau) : '--' ?></span>
                                            <span class="month"><?= $ts_batDau ? date('M', $ts_batDau) : '--' ?></span>
                                        </div>
                                        <?php if ($event['isActive'] == 0): ?>
                                            <span class="position-absolute top-0 start-0 m-2 badge bg-secondary opacity-90">
                                                <i class="bi bi-eye-slash me-1"></i>Nháp
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="event-content">
                                        <div class="event-meta">
                                            <span class="time">
                                                <i class="bi bi-clock"></i>
                                                <?= $ngayBatDau_f ?> – <?= $ngayKetThuc_f ?>
                                            </span>
                                            <span class="location">
                                                <i class="bi bi-building"></i>
                                                <?php if (!empty($event['tenCap'])): ?>
                                                    <?= htmlspecialchars($event['tenCap']) ?>
                                                    <?php if (!empty($event['tenLoaiCap'])): ?>
                                                        <small class="text-muted">(<?= htmlspecialchars($event['tenLoaiCap']) ?>)</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa xác định</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <h3 class="event-title">
                                            <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= (int)$event['idSK'] ?>">
                                                <?= htmlspecialchars($event['tenSK']) ?>
                                            </a>
                                        </h3>
                                        <p class="event-description">
                                            <?= htmlspecialchars(mb_substr($event['moTa'] ?? '', 0, 100)) ?>
                                            <?= mb_strlen($event['moTa'] ?? '') > 100 ? '...' : '' ?>
                                        </p>
                                        <div class="event-footer">
                                            <div class="instructor">
                                                <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-8.webp"
                                                    alt="BTC" class="instructor-avatar">
                                                <span><?= htmlspecialchars($event['nguoiTaoTen'] ?? 'BTC') ?></span>
                                            </div>
                                            <span class="badge <?= $badgeClass ?>" style="font-size:.75rem;">
                                                <?= $trangThai ?>
                                            </span>
                                        </div>
                                        <div class="event-actions">
                                            <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= (int)$event['idSK'] ?>"
                                                class="btn btn-primary">Xem sự kiện</a>
                                            <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= (int)$event['idSK'] ?>"
                                                class="btn btn-outline">Chi tiết</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper mt-4">
                            <nav aria-label="Events pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $cur_page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= event_page_url($cur_page - 1, $search, $filter_cap, $filter_time) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php
                                    $start_p = max(1, $cur_page - 2);
                                    $end_p   = min($total_pages, $cur_page + 2);
                                    if ($start_p > 1): ?>
                                        <li class="page-item"><a class="page-link" href="<?= event_page_url(1, $search, $filter_cap, $filter_time) ?>">1</a></li>
                                        <?php if ($start_p > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                    <?php endif;
                                    for ($p = $start_p; $p <= $end_p; $p++): ?>
                                        <li class="page-item <?= $p == $cur_page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= event_page_url($p, $search, $filter_cap, $filter_time) ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor;
                                    if ($end_p < $total_pages): ?>
                                        <?php if ($end_p < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                        <li class="page-item"><a class="page-link" href="<?= event_page_url($total_pages, $search, $filter_cap, $filter_time) ?>"><?= $total_pages ?></a></li>
                                    <?php endif; ?>
                                    <li class="page-item <?= $cur_page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= event_page_url($cur_page + 1, $search, $filter_cap, $filter_time) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <p class="text-center text-muted small mt-2">
                                Trang <?= $cur_page ?> / <?= $total_pages ?> &nbsp;·&nbsp; <?= $total_count ?> sự kiện
                            </p>
                        </div>
                    <?php endif; ?>
                </div><!-- /col-lg-8 -->

                <!-- Sidebar -->
                <div class="col-lg-4">

                    <?php if ($can_create_event): ?>
                        <div class="sidebar-widget newsletter-widget" data-aos="fade-up" data-aos-delay="200">
                            <h4 class="widget-title">Tạo sự kiện mới</h4>
                            <p class="text-muted small mb-3">Nhập tên nhanh hoặc bấm nút để mở form đầy đủ.</p>
                            <div class="newsletter-form">
                                <input type="text" id="quickEventName" placeholder="Tên sự kiện (tuỳ chọn)..."
                                    class="form-control mb-2">
                                <button type="button" id="btnOpenCreateModal" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle me-2"></i>Tạo sự kiện
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="sidebar-widget search-widget" data-aos="fade-up" data-aos-delay="300">
                        <h4 class="widget-title">Tìm kiếm sự kiện</h4>
                        <form action="<?= _HOST_URL ?>" method="get" class="search-form">
                            <input type="hidden" name="module" value="event">
                            <input type="hidden" name="action" value="index">
                            <?php if ($filter_cap): ?><input type="hidden" name="filter_cap" value="<?= $filter_cap ?>"><?php endif; ?>
                            <?php if ($filter_time): ?><input type="hidden" name="filter_time" value="<?= htmlspecialchars($filter_time) ?>"><?php endif; ?>
                            <input type="text" name="search" placeholder="Tìm kiếm sự kiện..." class="form-control"
                                value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
                        </form>
                    </div>

                    <div class="sidebar-widget filter-widget" data-aos="fade-up" data-aos-delay="400">
                        <h4 class="widget-title">Lọc sự kiện</h4>
                        <form action="<?= _HOST_URL ?>" method="get">
                            <input type="hidden" name="module" value="event">
                            <input type="hidden" name="action" value="index">
                            <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                            <div class="filter-content">
                                <div class="filter-group">
                                    <label class="filter-label">Đơn vị tổ chức</label>
                                    <select class="form-select" name="filter_cap">
                                        <option value="">Tất cả</option>
                                        <?php foreach ($all_caps as $cap): ?>
                                            <option value="<?= (int)$cap['idCap'] ?>"
                                                <?= ($filter_cap == (int)$cap['idCap']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cap['tenCap']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Thời gian</label>
                                    <select class="form-select" name="filter_time">
                                        <option value="">Mọi thời điểm</option>
                                        <option value="today"   <?= $filter_time === 'today'   ? 'selected' : '' ?>>Hôm nay</option>
                                        <option value="week"    <?= $filter_time === 'week'    ? 'selected' : '' ?>>Tuần này</option>
                                        <option value="month"   <?= $filter_time === 'month'   ? 'selected' : '' ?>>Tháng này</option>
                                        <option value="quarter" <?= $filter_time === 'quarter' ? 'selected' : '' ?>>3 tháng tới</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary filter-apply-btn flex-fill">Áp dụng</button>
                                    <a href="<?= _HOST_URL ?>/?module=event&action=index"
                                        class="btn btn-outline-secondary flex-fill">Xóa lọc</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="sidebar-widget upcoming-widget" data-aos="fade-up" data-aos-delay="500">
                        <h4 class="widget-title">Sự kiện sắp diễn ra</h4>
                        <div class="upcoming-list">
                            <?php if (empty($upcoming_events)): ?>
                                <p class="text-muted" style="font-size:.9rem;">Không có sự kiện sắp diễn ra.</p>
                            <?php endif; ?>
                            <?php foreach ($upcoming_events as $ue): ?>
                                <div class="upcoming-item">
                                    <div class="upcoming-date">
                                        <span class="day"><?= $ue['ngayBatDau'] ? date('d', strtotime($ue['ngayBatDau'])) : '--' ?></span>
                                        <span class="month"><?= $ue['ngayBatDau'] ? date('M', strtotime($ue['ngayBatDau'])) : '--' ?></span>
                                    </div>
                                    <div class="upcoming-content">
                                        <h5 class="upcoming-title">
                                            <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= (int)$ue['idSK'] ?>">
                                                <?= htmlspecialchars($ue['tenSK']) ?>
                                            </a>
                                        </h5>
                                        <div class="upcoming-meta">
                                            <span class="time">
                                                <i class="bi bi-clock"></i>
                                                <?= $ue['ngayBatDau'] ? date('H:i', strtotime($ue['ngayBatDau'])) : '' ?>
                                            </span>
                                            <?php if (!empty($ue['tenCap'])): ?>
                                                <span class="price"><?= htmlspecialchars($ue['tenCap']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div><!-- /sidebar -->
            </div>
        </div>
    </section>
</main>

<?php if ($can_create_event): ?>
<script>
(function () {
    'use strict';

    const capsByLoai  = <?= json_encode($caps_by_loai, JSON_UNESCAPED_UNICODE) ?>;
    const HOST_URL    = <?= json_encode(_HOST_URL) ?>;
    const modal       = document.getElementById('createEventModal');
    const form        = document.getElementById('createEventForm');
    const tenSKInput  = document.getElementById('tenSK');
    const loaiCapSel  = document.getElementById('idLoaiCap');
    const capSel      = document.getElementById('idCap');
    const nameWarning = document.getElementById('nameWarning');
    const nameWarnTxt = document.getElementById('nameWarningText');
    const templateSel = document.getElementById('templateSelect');
    const quickInput  = document.getElementById('quickEventName');
    const btnOpen     = document.getElementById('btnOpenCreateModal');

    // 1. Cascade Cấp → Đơn vị
    function updateCapOptions(idLoaiCap, selectedIdCap) {
        const opts = capsByLoai[idLoaiCap] || [];
        capSel.innerHTML = opts.length
            ? '<option value="">-- Chọn đơn vị --</option>'
            : '<option value="">-- Không có đơn vị --</option>';
        opts.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.idCap;
            opt.textContent = c.tenCap;
            if (selectedIdCap && parseInt(selectedIdCap) === parseInt(c.idCap)) opt.selected = true;
            capSel.appendChild(opt);
        });
        capSel.disabled = opts.length === 0;
    }

    loaiCapSel.addEventListener('change', function () {
        updateCapOptions(this.value, null);
        checkDuplicateName();
    });

    <?php if (!empty($_POST['idLoaiCap'])): ?>
        updateCapOptions(<?= (int)$_POST['idLoaiCap'] ?>, <?= (int)($_POST['idCap'] ?? 0) ?>);
    <?php endif; ?>

    // 2. AJAX kiểm tra trùng tên
    let nameCheckTimer = null;
    function checkDuplicateName() {
        const ten = tenSKInput ? tenSKInput.value.trim() : '';
        const loaiCap = loaiCapSel ? loaiCapSel.value : '';
        if (nameWarning) nameWarning.classList.add('d-none');
        if (ten.length < 2) return;
        clearTimeout(nameCheckTimer);
        nameCheckTimer = setTimeout(async () => {
            try {
                const url = HOST_URL + '/?module=event&action=check_name_ajax'
                    + '&tenSK=' + encodeURIComponent(ten)
                    + (loaiCap ? '&idLoaiCap=' + encodeURIComponent(loaiCap) : '');
                const data = await (await fetch(url)).json();
                if (data.exists && nameWarning && nameWarnTxt) {
                    const scope = loaiCap ? 'trong cùng cấp tổ chức này' : 'trong hệ thống';
                    nameWarnTxt.innerHTML = `Đã có <strong>${data.count}</strong> sự kiện tên này ${scope}. Bạn vẫn có thể tiếp tục tạo.`;
                    nameWarning.classList.remove('d-none');
                }
            } catch (e) { /* bỏ qua lỗi mạng */ }
        }, 500);
    }

    if (tenSKInput) {
        tenSKInput.addEventListener('blur', checkDuplicateName);
        tenSKInput.addEventListener('input', () => nameWarning?.classList.add('d-none'));
    }

    // 3. Template — pre-fill
    if (templateSel) {
        templateSel.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt?.value) return;
            if (tenSKInput && tenSKInput.value.trim() === '') tenSKInput.value = opt.dataset.ten || '';
            const moTaEl = document.getElementById('moTa');
            if (moTaEl && moTaEl.value.trim() === '') moTaEl.value = opt.dataset.mota || '';
            const idLoaiCap = opt.dataset.idloaicap;
            if (idLoaiCap && loaiCapSel) {
                loaiCapSel.value = idLoaiCap;
                updateCapOptions(idLoaiCap, opt.dataset.idcap);
            }
            checkDuplicateName();
        });
    }

    // 4. Quick Create
    if (btnOpen) {
        btnOpen.addEventListener('click', () => {
            if (tenSKInput && quickInput && tenSKInput.value.trim() === '')
                tenSKInput.value = quickInput.value.trim();
            new bootstrap.Modal(modal).show();
        });
    }

    // 5. Validation ngày phía client
    if (form) {
        form.addEventListener('submit', e => {
            let valid = true;
            const mo   = document.getElementById('ngayMoDangKy');
            const dong = document.getElementById('ngayDongDangKy');
            const bat  = document.getElementById('ngayBatDau');
            const ket  = document.getElementById('ngayKetThuc');
            const errMo  = document.getElementById('err-ngayMoDangKy');
            const errBat = document.getElementById('err-ngayBatDau');

            [mo, dong, bat, ket].forEach(el => el?.classList.remove('is-invalid'));

            if (mo.value && dong.value && new Date(mo.value) >= new Date(dong.value)) {
                mo.classList.add('is-invalid'); dong.classList.add('is-invalid');
                if (errMo) errMo.textContent = 'Ngày mở đăng ký phải trước ngày đóng đăng ký.';
                valid = false;
            }
            if (bat.value && ket.value && new Date(bat.value) >= new Date(ket.value)) {
                bat.classList.add('is-invalid'); ket.classList.add('is-invalid');
                if (errBat) errBat.textContent = 'Ngày bắt đầu phải trước ngày kết thúc.';
                valid = false;
            }
            if (!valid) {
                e.preventDefault();
                form.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        ['ngayMoDangKy','ngayDongDangKy','ngayBatDau','ngayKetThuc'].forEach(id =>
            document.getElementById(id)?.addEventListener('change', function () { this.classList.remove('is-invalid'); })
        );
    }

    // 6. Reopen modal nếu PHP trả lỗi
    <?php if (!empty($event_error)): ?>
        if (modal) new bootstrap.Modal(modal).show();
    <?php endif; ?>
})();
</script>
<?php endif; ?>
