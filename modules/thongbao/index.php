<?php
if (!defined('_AUTHEN')) die('Truy cập không hợp lệ');

// Yêu cầu đăng nhập
if (empty($_SESSION['user_id']) || $_SESSION['role'] === 'guest') {
    header("Location: " . _HOST_URL . "/?module=auth&action=login"); exit;
}

require_once _PATH_URL . '/modules/functions/quan_ly_thong_bao.php';

$id_tk = (int)($_SESSION['idTK'] ?? 0);

// --- Xử lý hành động ---
if (isset($_GET['doc']) && is_numeric($_GET['doc'])) {
    danh_dau_da_doc($conn, $id_tk, (int)$_GET['doc']);
    // Redirect về trang sự kiện nếu có
    $tb_r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT idSK FROM thongbao WHERE idThongBao=" . (int)$_GET['doc'] . " LIMIT 1"
    ));
    $redirect = ($tb_r && $tb_r['idSK'])
        ? _HOST_URL . "/?module=event&action=view&id=" . (int)$tb_r['idSK']
        : _HOST_URL . "/?module=thongbao&action=index";
    header("Location: $redirect"); exit;
}

if (isset($_GET['doc_tat_ca'])) {
    danh_dau_tat_ca_da_doc($conn, $id_tk);
    header("Location: " . _HOST_URL . "/?module=thongbao&action=index"); exit;
}

// --- Phân trang ---
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

// --- Filter ---
$filter_loai = $_GET['loai'] ?? '';
$where_loai  = '';
if ($filter_loai) {
    $filter_loai_safe = mysqli_real_escape_string($conn, $filter_loai);
    $where_loai = "AND tb.loaiThongBao = '$filter_loai_safe'";
}

// --- Lấy danh sách thông báo riêng ---
$sql = "
    SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao,
           tb.ngayGui, tb.idSK, sk.tenSK,
           tbn.daDoc, tbn.thoiGianDoc,
           tk_gui.tenTK as nguoiGui
    FROM thongbao_nguoinhan tbn
    JOIN thongbao tb ON tbn.idThongBao = tb.idThongBao
    LEFT JOIN sukien sk ON tb.idSK = sk.idSK
    LEFT JOIN taikhoan tk_gui ON tb.nguoiGui = tk_gui.idTK
    WHERE tbn.idTK = $id_tk $where_loai
    ORDER BY tb.ngayGui DESC
    LIMIT $per_page OFFSET $offset
";
$result       = mysqli_query($conn, $sql);
$ds_tb        = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
$so_chua_doc  = dem_chua_doc($conn, $id_tk);

// Tổng để phân trang
$total_r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM thongbao_nguoinhan tbn
     JOIN thongbao tb ON tbn.idThongBao = tb.idThongBao
     WHERE tbn.idTK = $id_tk $where_loai"
));
$total_pages = max(1, (int)ceil(($total_r['c'] ?? 0) / $per_page));

// Các loại thông báo để filter
$loai_list_r = mysqli_query($conn,
    "SELECT DISTINCT tb.loaiThongBao FROM thongbao_nguoinhan tbn
     JOIN thongbao tb ON tbn.idThongBao = tb.idThongBao
     WHERE tbn.idTK = $id_tk AND tb.loaiThongBao IS NOT NULL AND tb.loaiThongBao != ''
     ORDER BY tb.loaiThongBao"
);
$loai_list = $loai_list_r ? array_column(mysqli_fetch_all($loai_list_r, MYSQLI_ASSOC), 'loaiThongBao') : [];

$icon_map = [
    'Nhóm'       => ['bi-people-fill',     'text-success',   'bg-success'],
    'Kết quả'    => ['bi-trophy-fill',     'text-warning',   'bg-warning'],
    'Chấm điểm'  => ['bi-pencil-square',   'text-primary',   'bg-primary'],
    'Nhắc nhở'   => ['bi-alarm-fill',      'text-info',      'bg-info'],
    'Hệ thống'   => ['bi-gear-fill',       'text-secondary', 'bg-secondary'],
    'Chung'      => ['bi-megaphone-fill',  'text-primary',   'bg-primary'],
];

layout('header', ['page_title' => 'Thông báo của tôi']);
layout('navbar');
?>

<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">
                <i class="bi bi-bell-fill me-2"></i>Thông báo của tôi
                <?php if ($so_chua_doc > 0): ?>
                    <span class="badge bg-danger ms-2" style="font-size:.7rem;vertical-align:middle">
                        <?= $so_chua_doc ?> chưa đọc
                    </span>
                <?php endif; ?>
            </h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Trang chủ</a></li>
                    <li class="current">Thông báo</li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="section">
        <div class="container" data-aos="fade-up">
            <div class="row">

                <!-- ===== DANH SÁCH THÔNG BÁO ===== -->
                <div class="col-lg-8">

                    <!-- Toolbar -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="?module=thongbao&action=index"
                               class="btn btn-sm <?= !$filter_loai ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                Tất cả
                            </a>
                            <?php foreach ($loai_list as $loai): ?>
                                <a href="?module=thongbao&action=index&loai=<?= urlencode($loai) ?>"
                                   class="btn btn-sm <?= $filter_loai === $loai ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                    <?= htmlspecialchars($loai) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($so_chua_doc > 0): ?>
                            <a href="?module=thongbao&action=index&doc_tat_ca=1"
                               class="btn btn-sm btn-outline-secondary"
                               onclick="return confirm('Đánh dấu tất cả <?= $so_chua_doc ?> thông báo là đã đọc?')">
                                <i class="bi bi-check2-all me-1"></i>Đánh dấu tất cả đã đọc
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Danh sách -->
                    <?php if (empty($ds_tb)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bell-slash" style="font-size:3rem;opacity:.3"></i>
                            <p class="mt-3 mb-0">
                                <?= $filter_loai ? "Không có thông báo loại \"$filter_loai\"." : 'Bạn chưa có thông báo nào.' ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <?php foreach ($ds_tb as $i => $tb):
                                $chua_doc = !$tb['daDoc'];
                                $loai     = $tb['loaiThongBao'] ?? 'Chung';
                                [$icon, $icon_color, $badge_bg] = $icon_map[$loai] ?? ['bi-bell-fill', 'text-primary', 'bg-primary'];
                                $link_sk  = $tb['idSK']
                                    ? _HOST_URL . "/?module=event&action=view&id=" . (int)$tb['idSK']
                                    : null;
                                $read_url = _HOST_URL . "/?module=thongbao&action=index&doc=" . (int)$tb['idThongBao'];
                            ?>
                            <div class="d-flex align-items-start gap-3 p-3
                                        <?= $chua_doc ? 'bg-primary bg-opacity-5' : 'bg-white' ?>
                                        <?= $i < count($ds_tb) - 1 ? 'border-bottom' : '' ?>"
                                 style="transition:.2s">

                                <!-- Icon loại -->
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                                            <?= $chua_doc ? $badge_bg . ' bg-opacity-15' : 'bg-light' ?>"
                                     style="width:44px;height:44px;min-width:44px">
                                    <i class="bi <?= $icon ?> <?= $icon_color ?>"></i>
                                </div>

                                <!-- Nội dung -->
                                <div class="flex-grow-1 min-w-0">
                                    <div class="<?= $chua_doc ? 'fw-bold' : 'fw-normal' ?> text-dark lh-sm">
                                        <?php if ($link_sk): ?>
                                            <a href="<?= $read_url ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($tb['tieuDe']) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($tb['tieuDe']) ?>
                                        <?php endif; ?>
                                        <?php if ($chua_doc): ?>
                                            <span class="badge bg-primary ms-1" style="font-size:.6rem;vertical-align:middle">Mới</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small mt-1" style="line-height:1.4">
                                        <?= htmlspecialchars(mb_strimwidth($tb['noiDung'], 0, 120, '...')) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                        <span class="text-muted" style="font-size:.75rem">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('H:i · d/m/Y', strtotime($tb['ngayGui'])) ?>
                                        </span>
                                        <?php if ($tb['loaiThongBao']): ?>
                                            <span class="badge rounded-pill <?= $badge_bg ?> bg-opacity-10 <?= $icon_color ?>"
                                                  style="font-size:.65rem;border:1px solid currentColor">
                                                <?= htmlspecialchars($tb['loaiThongBao']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($tb['tenSK']): ?>
                                            <?php if ($link_sk): ?>
                                                <a href="<?= $link_sk ?>" class="text-muted text-decoration-none"
                                                   style="font-size:.72rem">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?= htmlspecialchars(mb_strimwidth($tb['tenSK'], 0, 30, '…')) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size:.72rem">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?= htmlspecialchars(mb_strimwidth($tb['tenSK'], 0, 30, '…')) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Nút action -->
                                <div class="flex-shrink-0 d-flex flex-column align-items-end gap-1">
                                    <?php if ($chua_doc): ?>
                                        <a href="<?= $read_url ?>"
                                           class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0"
                                           style="font-size:.72rem;white-space:nowrap">
                                            <i class="bi bi-check2 me-1"></i>Đánh dấu đọc
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:.7rem">
                                            <i class="bi bi-check2-all me-1"></i>Đã đọc
                                        </span>
                                        <?php if ($tb['thoiGianDoc']): ?>
                                            <span class="text-muted" style="font-size:.65rem">
                                                <?= date('d/m/Y', strtotime($tb['thoiGianDoc'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Phân trang -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?module=thongbao&action=index&page=<?= $page-1 ?>&loai=<?= urlencode($filter_loai) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($p = max(1, $page-2); $p <= min($total_pages, $page+2); $p++): ?>
                                <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?module=thongbao&action=index&page=<?= $p ?>&loai=<?= urlencode($filter_loai) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?module=thongbao&action=index&page=<?= $page+1 ?>&loai=<?= urlencode($filter_loai) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>

                </div><!-- /col-lg-8 -->

                <!-- ===== SIDEBAR THỐNG KÊ ===== -->
                <div class="col-lg-4 mt-4 mt-lg-0">

                    <!-- Tổng quan -->
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Tổng quan</h6>
                            <?php
                            $total_tb = (int)($total_r['c'] ?? 0);
                            $total_da_doc = $total_tb - $so_chua_doc;
                            $pct = $total_tb > 0 ? round($total_da_doc / $total_tb * 100) : 0;
                            ?>
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Đã đọc</span>
                                <span><?= $total_da_doc ?> / <?= $total_tb ?></span>
                            </div>
                            <div class="progress mb-3" style="height:6px">
                                <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                            </div>
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="bg-light rounded-3 p-2">
                                        <div class="fw-bold text-primary fs-5"><?= $total_tb ?></div>
                                        <div class="text-muted" style="font-size:.72rem">Tổng cộng</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded-3 p-2">
                                        <div class="fw-bold text-danger fs-5"><?= $so_chua_doc ?></div>
                                        <div class="text-muted" style="font-size:.72rem">Chưa đọc</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lọc theo loại -->
                    <?php if (!empty($loai_list)): ?>
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-funnel-fill me-2 text-primary"></i>Lọc theo loại</h6>
                            <div class="d-flex flex-column gap-1">
                                <a href="?module=thongbao&action=index"
                                   class="d-flex align-items-center justify-content-between text-decoration-none
                                          p-2 rounded-3 <?= !$filter_loai ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-muted' ?>">
                                    <span><i class="bi bi-list-ul me-2"></i>Tất cả</span>
                                    <span class="badge bg-secondary"><?= $total_tb ?></span>
                                </a>
                                <?php foreach ($loai_list as $loai):
                                    [$ic, $ic_color] = $icon_map[$loai] ?? ['bi-bell', 'text-primary'];
                                    $cnt_r = mysqli_fetch_assoc(mysqli_query($conn,
                                        "SELECT COUNT(*) as c FROM thongbao_nguoinhan tbn
                                         JOIN thongbao tb ON tbn.idThongBao=tb.idThongBao
                                         WHERE tbn.idTK=$id_tk AND tb.loaiThongBao='".mysqli_real_escape_string($conn,$loai)."'"
                                    ));
                                ?>
                                <a href="?module=thongbao&action=index&loai=<?= urlencode($loai) ?>"
                                   class="d-flex align-items-center justify-content-between text-decoration-none
                                          p-2 rounded-3 <?= $filter_loai === $loai ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-muted' ?>">
                                    <span><i class="bi <?= $ic ?> me-2 <?= $ic_color ?>"></i><?= htmlspecialchars($loai) ?></span>
                                    <span class="badge bg-secondary"><?= (int)($cnt_r['c']??0) ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Gợi ý hành động nhanh -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill me-2 text-warning"></i>Hành động nhanh</h6>
                            <div class="d-flex flex-column gap-2">
                                <a href="?module=event&action=index"
                                   class="btn btn-light btn-sm text-start">
                                    <i class="bi bi-calendar-event me-2 text-primary"></i>Xem sự kiện
                                </a>
                                <a href="?module=profile&action=index"
                                   class="btn btn-light btn-sm text-start">
                                    <i class="bi bi-person-circle me-2 text-success"></i>Trang cá nhân
                                </a>
                                <?php if ($so_chua_doc > 0): ?>
                                <a href="?module=thongbao&action=index&doc_tat_ca=1"
                                   class="btn btn-outline-secondary btn-sm text-start"
                                   onclick="return confirm('Đánh dấu tất cả đã đọc?')">
                                    <i class="bi bi-check2-all me-2"></i>Đọc tất cả (<?= $so_chua_doc ?>)
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div><!-- /col-lg-4 -->

            </div>
        </div>
    </section>

</main>

<?php layout('footer'); ?>
