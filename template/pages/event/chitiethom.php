
<style>
/* ===== MODAL STYLES ===== */
.modal-header-grad { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border-radius:12px 12px 0 0; padding:16px 20px; }
.modal-header-grad .btn-close { filter:brightness(0) invert(1); }
.modal-content { border-radius:12px; border:none; }
.modal-nav .nav-link { color:#555; font-weight:500; padding:10px 18px; border:none; border-bottom:3px solid transparent; background:none; border-radius:0; }
.modal-nav .nav-link.active { color:#4f46e5; border-bottom-color:#4f46e5; background:none; }
.modal-nav .nav-link:hover { color:#4f46e5; }

/* ===== MEMBER CHIPS ===== */
.member-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.member-chip.truong { background:#ede9fe; color:#5b21b6; }
.member-chip.thanh-vien { background:#f1f3fb; color:#3b4a85; }
.member-chip.gvhd-chip { background:#ecfdf5; color:#065f46; }

/* ===== MEMBER ROW ===== */
.member-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f3f4f6; }
.member-row:last-child { border-bottom:none; }

/* ===== REQUEST ROW ===== */
.req-row { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; margin-bottom:10px; }
.req-row .req-meta { font-size:12px; color:#9ca3af; margin-top:4px; }

/* ===== INFO ALERT — replaced by Bootstrap alert-info ===== */

/* GVHD indicators */
.gvhd-row { background:#4f46e5; color:#fff; border-radius:8px; padding:7px 14px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; margin-bottom:10px; }
.gvhd-pending { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:13px; color:#1e40af; display:flex; align-items:center; gap:8px; margin-bottom:10px; }

/* ===== UPLOAD DROPZONE ===== */
.upload-dropzone { border:2px dashed #c5cef8; border-radius:12px; padding:36px 20px; text-align:center; background:#f8f9ff; transition:background .2s, border-color .2s; cursor:pointer; }
.upload-dropzone:hover, .upload-dropzone.dragover { background:#eef0fd; border-color:#4f46e5; }

/* ===== SUBMITTED FILES ===== */
.submitted-file-row { display:flex; align-items:center; gap:10px; padding:8px 12px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:6px; font-size:13px; }
.submitted-file-row .file-label { flex:1; min-width:0; }

/* ===== SUCCESS POPUP ===== */
@keyframes popIn { from { transform:scale(.7); opacity:0; } to { transform:scale(1); opacity:1; } }

/* ===== GROUP HERO ===== */
.group-stats-row { display:flex; gap:16px; flex-wrap:wrap; margin-top:12px; }
.group-stat { display:flex; align-items:center; gap:6px; font-size:14px; color:#555; }
.group-stat i { color:#4f46e5; }
</style>

<main class="main">

    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0"><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
                    <li><a href="<?= _HOST_URL ?>?module=event&action=view&id=<?= $idSK ?>"><?= htmlspecialchars($nhom['tenSK'] ?? '') ?></a></li>
                    <li class="current"><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="course-details section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row">
                <div class="col-lg-8">

                    <div class="course-hero" data-aos="fade-up" data-aos-delay="200">
                        <div class="hero-content">
                            <div class="course-badge">
                                <span class="category">Nhóm thi</span>
                                <span class="level"><?= $nhom['dangtuyen'] ? 'Đang tuyển' : 'Đã đủ thành viên' ?></span>
                            </div>
                            <h1><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></h1>
                            <p class="course-subtitle"><?= nl2br(htmlspecialchars($nhom['mota'] ?? 'Chưa có mô tả.')) ?></p>

                            <div class="instructor-card">
                                <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-8.webp" alt="Event" class="instructor-image" style="object-fit:cover;">
                                <div class="instructor-details">
                                    <h5><?= htmlspecialchars($nhom['tenSK'] ?? '') ?></h5>
                                    <span>Nhóm trưởng: <?= htmlspecialchars($nhom['tenNhomTruong'] ?? '') ?></span>
                                    <?php if ($gvhd): ?>
                                        <div class="mt-1">
                                            <span class="badge" style="background:#4f46e5;color:#fff;font-size:11px;">
                                                <i class="bi bi-person-workspace me-1"></i>GVHD: <?= htmlspecialchars($gvhd['tenGVHD']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="group-stats-row">
                                <div class="group-stat"><i class="bi bi-people-fill"></i> <?= $soThanhVien ?>/<?= $nhom['soluongtoida'] ?> thành viên</div>
                                <?php if ($sanPham): ?>
                                    <div class="group-stat"><i class="bi bi-journal-text"></i> <?= htmlspecialchars($sanPham['tensanpham']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="hero-image">
                            <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/courses-3.webp" alt="Nhóm" class="img-fluid">
                        </div>
                    </div>

                    <div class="course-nav-tabs" data-aos="fade-up" data-aos-delay="300">
                        <ul class="nav nav-tabs" id="GroupDetailTabs" role="tablist">
                            <?php if ($isTruong): ?>
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-quanly" type="button">
                                    <i class="bi bi-gear"></i> Quản lý
                                    <?php if ($soYC > 0): ?><span class="badge bg-danger ms-1"><?= $soYC ?></span><?php endif; ?>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-moi" type="button">
                                    <i class="bi bi-person-plus"></i> Mời tham gia
                                </button>
                            </li>
                            <?php else: ?>
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-thanhvien" type="button">
                                    <i class="bi bi-people"></i> Thành viên
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if ($isMember): ?>
                            <li class="nav-item">
                                <button class="nav-link <?= !$isTruong ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-nopbai" type="button">
                                    <i class="bi bi-cloud-upload"></i> Nộp bài
                                    <?php if ($sanPham): ?><span class="badge bg-success ms-1">1</span><?php endif; ?>
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>

                        <div class="tab-content" id="GroupDetailContent">

                            <?php if ($isTruong): ?>
                            <div class="tab-pane fade show active" id="tab-quanly" role="tabpanel">
                                <div class="pt-3">
                                    <ul class="nav modal-nav border-bottom mb-3" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#subtab-tv" type="button">
                                                <i class="bi bi-people me-1"></i>Thành viên (<?= $soThanhVien ?>)
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-yeucau" type="button">
                                                <i class="bi bi-inbox me-1"></i>Yêu cầu tham gia
                                                <?php if ($soYC > 0): ?><span class="badge bg-danger ms-1"><?= $soYC ?></span><?php endif; ?>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-caidat" type="button">
                                                <i class="bi bi-sliders me-1"></i>Cài đặt nhóm
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="subtab-tv">
                                            <p class="text-muted small mb-3">Danh sách thành viên đang hoạt động trong nhóm.</p>
                                            <?php if (empty($danhSachTV)): ?>
                                                <p class="text-muted">Chưa có thành viên.</p>
                                            <?php else: ?>
                                                <?php foreach ($danhSachTV as $tv): ?>
                                                    <div class="member-row">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php
                                                            $chipClass = match((int)$tv['idvaitronhom']) { 1=>'truong', 3=>'gvhd-chip', default=>'thanh-vien' };
                                                            $icon = match((int)$tv['idvaitronhom']) { 1=>'shield-fill-check', 3=>'person-workspace', default=>'person' };
                                                            ?>
                                                            <span class="member-chip <?= $chipClass ?>">
                                                                <i class="bi bi-<?= $icon ?>"></i>
                                                                <?= htmlspecialchars($tv['tenTV']) ?>
                                                            </span>
                                                            <span class="text-muted small">(<?= htmlspecialchars($tv['vaiTro'] ?? '—') ?>)</span>
                                                        </div>
                                                        <?php if ($tv['idvaitronhom'] != 1): ?>
                                                            <form method="POST" id="formXoaTV-<?= $tv['idtk'] ?>">
                                                                <input type="hidden" name="xoa_thanh_vien" value="1">
                                                                <input type="hidden" name="idTK" value="<?= $tv['idtk'] ?>">
                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                        onclick="showConfirm({
                                                                            title: 'Xác nhận xóa thành viên',
                                                                            message: 'Xác nhận xóa thành viên này?',
                                                                            type: 'danger',
                                                                            confirmText: 'Xóa',
                                                                            onConfirm: () => document.getElementById('formXoaTV-<?= $tv['idtk'] ?>').submit()
                                                                        })">
                                                                    <i class="bi bi-x-circle"></i> Xóa
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="tab-pane fade" id="subtab-yeucau">
                                            <?php if (empty($yeuCauCho)): ?>
                                                <div class="text-center py-4 text-muted">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                    Không có yêu cầu nào đang chờ duyệt.
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted small mb-3">Duyệt hoặc từ chối các yêu cầu tham gia nhóm.</p>
                                                <?php foreach ($yeuCauCho as $yc): ?>
                                                    <div class="req-row">
                                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                                            <div>
                                                                <strong><?= htmlspecialchars($yc['tenNguoiGui']) ?></strong>
                                                                <span class="badge bg-<?= $yc['ChieuMoi'] == 1 ? 'info text-dark' : 'secondary' ?> ms-2 small">
                                                                    <?= $yc['ChieuMoi'] == 1 ? 'Xin tham gia' : 'Được nhóm mời' ?>
                                                                </span>
                                                                <?php if (!empty($yc['loiNhan'])): ?>
                                                                    <div class="text-muted small mt-1 fst-italic">"<?= htmlspecialchars($yc['loiNhan']) ?>"</div>
                                                                <?php endif; ?>
                                                                <div class="req-meta"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($yc['ngayGui'])) ?></div>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <form method="POST">
                                                                    <input type="hidden" name="duyet_yeucau" value="1">
                                                                    <input type="hidden" name="idYeuCau" value="<?= $yc['idYeuCau'] ?>">
                                                                    <input type="hidden" name="trangThai" value="1">
                                                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Duyệt</button>
                                                                </form>
                                                                <form method="POST">
                                                                    <input type="hidden" name="duyet_yeucau" value="1">
                                                                    <input type="hidden" name="idYeuCau" value="<?= $yc['idYeuCau'] ?>">
                                                                    <input type="hidden" name="trangThai" value="2">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Từ chối</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="tab-pane fade" id="subtab-caidat">
                                            <form method="POST" class="pt-2">
                                                <input type="hidden" name="cap_nhat_nhom" value="1">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tên nhóm</label>
                                                    <input type="text" name="tennhom" class="form-control" value="<?= htmlspecialchars($nhom['tennhom'] ?? '') ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Mô tả</label>
                                                    <textarea name="mota" class="form-control" rows="3"><?= htmlspecialchars($nhom['mota']) ?></textarea>
                                                </div>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="dt-nhom" name="dangtuyen" value="1" <?= $nhom['dangtuyen'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="dt-nhom">Đang tuyển thành viên (Công khai)</label>
                                                </div>
                                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu cài đặt</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php else: ?>
                            <div class="tab-pane fade show active" id="tab-thanhvien" role="tabpanel">
                                <div class="pt-3">
                                    <p class="text-muted small mb-3">Danh sách thành viên trong nhóm.</p>
                                    <?php foreach ($danhSachTV as $tv): ?>
                                        <div class="member-row">
                                            <?php
                                            $chipClass = match((int)$tv['idvaitronhom']) { 1=>'truong', 3=>'gvhd-chip', default=>'thanh-vien' };
                                            $icon = match((int)$tv['idvaitronhom']) { 1=>'shield-fill-check', 3=>'person-workspace', default=>'person' };
                                            ?>
                                            <span class="member-chip <?= $chipClass ?>">
                                                <i class="bi bi-<?= $icon ?>"></i>
                                                <?= htmlspecialchars($tv['tenTV']) ?>
                                            </span>
                                            <span class="text-muted small"><?= htmlspecialchars($tv['vaiTro'] ?? '—') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($isTruong): ?>
                            <div class="tab-pane fade" id="tab-moi" role="tabpanel">
                                <div class="pt-3">
                                    <ul class="nav modal-nav border-bottom mb-3" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#subtab-moiSV" type="button">
                                                <i class="bi bi-person-plus me-1"></i>Mời sinh viên
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-moiGV" type="button">
                                                <i class="bi bi-person-badge me-1"></i>Mời GVHD
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="subtab-moiSV">
                                            <div class="alert alert-info d-flex align-items-start gap-2 mt-2">
                                                <i class="bi bi-info-circle-fill"></i>
                                                Sinh viên được mời sẽ nhận thông báo và có thể chấp nhận hoặc từ chối.
                                            </div>
                                            <div id="sv-invite-result" class="mb-3 d-none"></div>
                                            <div id="sv-invite-form">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Chọn sinh viên <span class="text-danger">*</span></label>
                                                    <input type="text" id="sv-search-inline" class="form-control mb-2" placeholder="Tìm theo tên hoặc mã SV...">
                                                    <select class="form-select" id="sv-select-inline" size="6" style="height:auto">
                                                        <?php foreach ($sv_list as $sv):
                                                            if (in_array($sv['idTK'], $dsMaTK)) continue; ?>
                                                            <option value="<?= $sv['idTK'] ?>"
                                                                data-search="<?= strtolower($sv['tenSV'] . ' ' . $sv['MSV']) ?>">
                                                                <?= htmlspecialchars($sv['tenSV']) ?> (<?= htmlspecialchars($sv['MSV']) ?>)
                                                                <?= !empty($sv['tenLop']) ? ' — ' . htmlspecialchars($sv['tenLop']) : '' ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Lời nhắn</label>
                                                    <textarea id="sv-loinhan-inline" class="form-control" rows="3" placeholder="Lời nhắn kèm theo lời mời..."></textarea>
                                                </div>
                                                <button type="button" class="btn btn-primary" onclick="submitMoiSVInline()">
                                                    <i class="bi bi-send me-1"></i>Gửi lời mời
                                                </button>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="subtab-moiGV">
                                            <?php if ($gvhd): ?>
                                                <div class="alert alert-success mt-2">
                                                    <i class="bi bi-check-circle-fill me-2"></i>
                                                    Nhóm đã có GVHD: <strong><?= htmlspecialchars($gvhd['tenGVHD']) ?></strong>
                                                </div>
                                            <?php elseif ($pendingGVHD): ?>
                                                <div class="alert alert-info mt-2">
                                                    <i class="bi bi-clock-fill me-2"></i>
                                                    Đang chờ GVHD xác nhận lời mời.
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-info d-flex align-items-start gap-2 mt-2">
                                                    <i class="bi bi-info-circle-fill"></i>
                                                    Bạn chỉ gửi được 1 lời mời GVHD tại một thời điểm.
                                                </div>
                                                <div id="gv-invite-result" class="mb-3 d-none"></div>
                                                <div id="gv-invite-form">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Chọn Giảng viên <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="gv-select-inline">
                                                            <option value="">-- Chọn giảng viên --</option>
                                                            <?php foreach ($gv_list as $gv): ?>
                                                                <option value="<?= $gv['idTK'] ?>">
                                                                    <?= htmlspecialchars($gv['tenGV']) ?>
                                                                    <?= !empty($gv['tenKhoa']) ? ' — ' . htmlspecialchars($gv['tenKhoa']) : '' ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Lời nhắn gửi GVHD</label>
                                                        <textarea id="gv-loinhan-inline" class="form-control" rows="4" placeholder="Giới thiệu về đề tài và lý do mời GVHD..."></textarea>
                                                    </div>
                                                    <button type="button" class="btn btn-warning text-white" onclick="submitMoiGVInline()">
                                                        <i class="bi bi-send me-1"></i>Gửi lời mời GVHD
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($isMember): ?>
                            <div class="tab-pane fade <?= !$isTruong ? 'show active' : '' ?>" id="tab-nopbai" role="tabpanel">
                                <div class="pt-3">
                                    <ul class="nav modal-nav border-bottom mb-3" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#subtab-nopbai" type="button">
                                                <i class="bi bi-cloud-upload me-1"></i><?= $sanPham ? 'Cập nhật bài nộp' : 'Nộp bài' ?>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subtab-tatcabainop" type="button">
                                                <i class="bi bi-folder2-open me-1"></i>Tất cả bài nộp
                                                <?php if (!empty($sanPhamTheoLoai)): ?><span class="badge bg-success ms-1"><?= count($sanPhamTheoLoai) ?></span><?php endif; ?>
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="subtab-nopbai">
                                            <form method="POST" enctype="multipart/form-data" class="pt-2">
                                                <input type="hidden" name="nop_bai" value="1">

                                                <?php if ($sanPham): ?>
                                                    <div class="alert alert-info py-2 mb-3">
                                                        <strong>Trạng thái:</strong>
                                                        <span class="badge ms-1 bg-<?= $sanPham['TrangThai']=='Đã duyệt'?'success':'warning text-dark' ?>">
                                                            <?= htmlspecialchars($sanPham['TrangThai']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tên đề tài <span class="text-danger">*</span></label>
                                                    <input type="text" name="tenDeTai" class="form-control" required
                                                        placeholder="Nhập tên đề tài nghiên cứu..."
                                                        value="<?= htmlspecialchars($sanPham['tensanpham'] ?? '') ?>">
                                                </div>

                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Chủ đề nghiên cứu</label>
                                                    <select name="idChuDe" class="form-select">
                                                        <option value="0">-- Chọn chủ đề --</option>
                                                        <?php foreach ($chude_list as $cd): ?>
                                                            <option value="<?= $cd['idChuDeSK'] ?>"
                                                                <?= (($sanPham['idChuDeSK'] ?? 0)==$cd['idChuDeSK'])?'selected':'' ?>>
                                                                <?= htmlspecialchars($cd['tenChuDe']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <hr class="my-3">
                                                <label class="form-label fw-semibold mb-2">
                                                    <i class="bi bi-paperclip me-1"></i>Tệp bài nộp
                                                </label>

                                                <?php if (!empty($sanPhamTheoLoai)):
                                                    $loaiLabel=[1=>['icon'=>'file-earmark-text','label'=>'Báo cáo tóm tắt','color'=>'primary'],
                                                                2=>['icon'=>'file-earmark-richtext','label'=>'Báo cáo toàn văn','color'=>'info'],
                                                                3=>['icon'=>'github','label'=>'Source Code','color'=>'dark']];
                                                    ?>
                                                    <div class="mb-3 p-3 rounded border bg-light">
                                                        <p class="fw-semibold small text-muted mb-2">
                                                            <i class="bi bi-check2-circle text-success me-1"></i>Tập tin đã nộp:
                                                        </p>
                                                        <?php foreach ($loaiLabel as $idLoai => $meta):
                                                            $sp = $sanPhamTheoLoai[$idLoai] ?? null;
                                                            if (!$sp) continue; ?>
                                                            <div class="submitted-file-row">
                                                                <i class="bi bi-<?= $meta['icon'] ?> text-<?= $meta['color'] ?> fs-5"></i>
                                                                <a href="<?= strpos($sp['moTataiLieu'],'http')===0
                                                                    ?htmlspecialchars($sp['moTataiLieu'])
                                                                    :_HOST_URL.'/'.htmlspecialchars($sp['moTataiLieu']) ?>"
                                                                    target="_blank" class="file-label small text-truncate">
                                                                    <?= htmlspecialchars(basename($sp['moTataiLieu'])) ?>
                                                                </a>
                                                                <span class="badge bg-success">Đã nộp</span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Upload mới sẽ thay thế file cũ.</p>
                                                    </div>
                                                <?php endif; ?>

                                                <div id="dropzone-main"
                                                    class="upload-dropzone"
                                                    onclick="document.getElementById('fileInput-main').click()">
                                                    <i class="bi bi-cloud-arrow-up fs-1 text-primary d-block mb-2"></i>
                                                    <p class="fw-semibold mb-1">
                                                        Kéo thả file vào đây hoặc <span class="text-primary text-decoration-underline">chọn file</span>
                                                    </p>
                                                    <p class="text-muted small mb-1">PDF, DOC, DOCX (báo cáo) · ZIP, RAR (source code)</p>
                                                    <p class="text-muted small mb-0">Tối đa 20MB/file</p>
                                                    <input type="file" name="files[]" id="fileInput-main"
                                                        multiple accept=".pdf,.doc,.docx,.zip,.rar,.pptx"
                                                        class="d-none"
                                                        onchange="handleFileSelect(this)">
                                                </div>

                                                <div id="fileList-main" class="mt-3 d-none">
                                                    <p class="fw-semibold small text-muted mb-2"><i class="bi bi-list-ul me-1"></i>Tập tin sẽ nộp:</p>
                                                    <div id="fileItems-main"></div>
                                                </div>

                                                <div class="mt-4">
                                                    <button type="submit" class="btn btn-success px-4">
                                                        <i class="bi bi-cloud-check me-1"></i><?= $sanPham?'Cập nhật bài nộp':'Nộp bài' ?>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="tab-pane fade" id="subtab-tatcabainop">
                                            <?php if (empty($sanPhamTheoLoai)): ?>
                                                <div class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                    Nhóm chưa nộp bài nào.
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-2">
                                                    <p class="text-muted small mb-3">Các tài liệu đã được nộp bởi nhóm.</p>
                                                    <?php
                                                    $loaiInfo = [
                                                        1 => ['icon'=>'file-earmark-text','label'=>'Báo cáo tóm tắt','color'=>'primary'],
                                                        2 => ['icon'=>'file-earmark-richtext','label'=>'Báo cáo toàn văn','color'=>'info'],
                                                        3 => ['icon'=>'github','label'=>'Source Code','color'=>'dark'],
                                                    ];
                                                    foreach ($loaiInfo as $idLoai => $info):
                                                        $sp = $sanPhamTheoLoai[$idLoai] ?? null;
                                                        if (!$sp) continue; ?>
                                                        <div class="p-3 border rounded mb-3">
                                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                                <i class="bi bi-<?= $info['icon'] ?> text-<?= $info['color'] ?> fs-4"></i>
                                                                <div>
                                                                    <span class="badge ms-1 bg-<?= $sp['TrangThai']=='Đã duyệt'?'success':'warning text-dark' ?>">
                                                                        <?= htmlspecialchars($sp['TrangThai']) ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <p class="mb-1 fw-semibold small text-muted"><?= htmlspecialchars(basename($sp['moTataiLieu'])) ?></p>
                                                            <?php if (!empty($sp['tensanpham'])): ?>
                                                                <p class="mb-1 fw-semibold"><?= htmlspecialchars($sp['tensanpham']) ?></p>
                                                            <?php endif; ?>
                                                            <a href="<?= strpos($sp['moTataiLieu'],'http')===0
                                                                ?htmlspecialchars($sp['moTataiLieu'])
                                                                :_HOST_URL.'/'.htmlspecialchars($sp['moTataiLieu']) ?>"
                                                                target="_blank" class="btn btn-sm btn-outline-<?= $info['color'] ?> mt-1">
                                                                <i class="bi bi-download me-1"></i>Tải xuống
                                                            </a>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="course-details-card" data-aos="fade-up" data-aos-delay="300">
                        <h4>Thông tin nhóm</h4>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <span class="detail-label">Sự kiện</span>
                                <span class="detail-value">
                                    <a href="<?= _HOST_URL ?>?module=event&action=view&id=<?= $idSK ?>">
                                        <?= htmlspecialchars($nhom['tenSK'] ?? '') ?>
                                    </a>
                                </span>
                            </div>
                            <div class="detail-row"><span class="detail-label">Nhóm trưởng</span><span class="detail-value"><?= htmlspecialchars($nhom['tenNhomTruong'] ?? '') ?></span></div>
                            <div class="detail-row"><span class="detail-label">Thành viên</span><span class="detail-value"><?= $soThanhVien ?>/<?= $nhom['soluongtoida'] ?></span></div>
                            <div class="detail-row">
                                <span class="detail-label">GVHD</span>
                                <span class="detail-value">
                                    <?php if ($gvhd): ?>
                                        <span class="badge" style="background:#4f46e5"><?= htmlspecialchars($gvhd['tenGVHD']) ?></span>
                                    <?php elseif ($pendingGVHD): ?>
                                        <span class="badge bg-warning text-dark">Đang chờ xác nhận</span>
                                    <?php else: ?>
                                        <span class="text-danger small">Chưa có GVHD</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Trạng thái</span>
                                <span class="detail-value">
                                    <?php if ($nhom['dangtuyen']): ?>
                                        <span class="badge bg-success">Đang tuyển</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã đủ thành viên</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($sanPham): ?>
                            <div class="detail-row">
                                <span class="detail-label">Đề tài</span>
                                <span class="detail-value"><?= htmlspecialchars($sanPham['tensanpham']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Trạng thái bài</span>
                                <span class="detail-value">
                                    <span class="badge bg-<?= $sanPham['TrangThai']=='Đã duyệt'?'success':'warning text-dark' ?>">
                                        <?= htmlspecialchars($sanPham['TrangThai']) ?>
                                    </span>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="course-details-card mt-4" data-aos="fade-up" data-aos-delay="400">
                        <h4>Thành viên nhóm</h4>
                        <?php foreach ($danhSachTV as $tv):
                            $chipClass = match((int)$tv['idvaitronhom']) { 1=>'truong', 3=>'gvhd-chip', default=>'thanh-vien' };
                            $icon = match((int)$tv['idvaitronhom']) { 1=>'shield-fill-check', 3=>'person-workspace', default=>'person' };
                            ?>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="member-chip <?= $chipClass ?>" style="font-size:12px">
                                    <i class="bi bi-<?= $icon ?>"></i>
                                    <?= htmlspecialchars($tv['tenTV']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($isMember): ?>
                    <div class="course-details-card mt-4" data-aos="fade-up" data-aos-delay="500">
                        <h4>Hành động</h4>
                        <div class="d-flex flex-column gap-2">
                            <?php if ($isTruong): ?>
                            <?php $svTrongNhom = array_filter($danhSachTV, fn($tv) => $tv['idvaitronhom'] == 2); ?>
                            <?php if (!empty($svTrongNhom)): ?>
                            <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#nhuongQuyenModal">
                                <i class="bi bi-arrow-left-right me-2"></i>Nhượng quyền trưởng nhóm
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary w-100" disabled title="Cần có thành viên khác để nhượng quyền">
                                <i class="bi bi-arrow-left-right me-2"></i>Nhượng quyền trưởng nhóm
                            </button>
                            <?php endif; ?>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#roiNhomModal">
                                <i class="bi bi-box-arrow-left me-2"></i>Rời nhóm
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
</main>

<?php layout('footer'); ?>

<?php if ($isMember && !$isTruong): ?>
<div class="modal fade" id="roiNhomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-grad d-flex justify-content-between align-items-center">
                <h5 class="modal-title mb-0"><i class="bi bi-box-arrow-left me-2"></i>Rời nhóm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-0">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        Bạn có chắc muốn rời khỏi nhóm <strong><?= htmlspecialchars($nhom['tennhom'] ?? '') ?></strong>?<br>
                        <span class="small text-muted">Sau khi rời, bạn cần được mời lại hoặc xin vào nhóm mới để tham gia sự kiện.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <form method="POST">
                    <input type="hidden" name="roi_nhom" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-box-arrow-left me-1"></i>Xác nhận rời nhóm
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isTruong): ?>
<?php $svTrongNhom = array_filter($danhSachTV, fn($tv) => $tv['idvaitronhom'] == 2); ?>
<?php if (!empty($svTrongNhom)): ?>
<div class="modal fade" id="nhuongQuyenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-grad d-flex justify-content-between align-items-center">
                <h5 class="modal-title mb-0"><i class="bi bi-arrow-left-right me-2"></i>Nhượng quyền trưởng nhóm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="nhuong_quyen" value="1">
                <div class="modal-body p-4">
                    <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            Sau khi nhượng quyền, bạn sẽ trở thành thành viên thường.<br>
                            <span class="small">Hành động này không thể hoàn tác.</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn thành viên nhận quyền <span class="text-danger">*</span></label>
                        <select name="idTKMoi" class="form-select" required>
                            <option value="">-- Chọn thành viên --</option>
                            <?php foreach ($svTrongNhom as $tv): ?>
                                <option value="<?= $tv['idtk'] ?>">
                                    <?= htmlspecialchars($tv['tenTV']) ?>
                                    <?= !empty($tv['vaiTro']) ? ' (' . htmlspecialchars($tv['vaiTro']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="bi bi-arrow-left-right me-1"></i>Nhượng quyền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
const AJAX_URL = window.location.href;

document.getElementById('sv-search-inline')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    const sel = document.getElementById('sv-select-inline');
    if (!sel) return;
    Array.from(sel.options).forEach(opt => {
        opt.style.display = !q || opt.dataset.search?.includes(q) ? '' : 'none';
    });
});

function submitMoiSVInline() {
    const select   = document.getElementById('sv-select-inline');
    const loinhan  = document.getElementById('sv-loinhan-inline');
    const resultEl = document.getElementById('sv-invite-result');
    const formEl   = document.getElementById('sv-invite-form');

    if (!select || !select.value) {
        showInlineResult(resultEl, false, 'Vui lòng chọn sinh viên cần mời.');
        return;
    }

    const btn = formEl.querySelector('button[type="button"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';

    const body = new URLSearchParams({
        ajax_action: 'moi_thanh_vien',
        idSV: select.value,
        loiNhan: loinhan?.value || ''
    });

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                const tenSV = select.options[select.selectedIndex]?.text || '';
                formEl.classList.add('d-none');
                showInlineResult(resultEl, true,
                    `<i class="bi bi-check-circle-fill me-2"></i>
                     <strong>Đã gửi lời mời!</strong><br>
                     <span class="small text-muted">Lời mời đã được gửi tới <strong>${escHtml(tenSV)}</strong>.</span>
                     <br><button class="btn btn-sm btn-outline-primary mt-2" onclick="resetMoiSV()">Mời người khác</button>`
                );
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
                showInlineResult(resultEl, false, data.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
            showInlineResult(resultEl, false, 'Lỗi kết nối.');
        });
}

function resetMoiSV() {
    const formEl = document.getElementById('sv-invite-form');
    const resultEl = document.getElementById('sv-invite-result');
    formEl.classList.remove('d-none');
    resultEl.classList.add('d-none');
    document.getElementById('sv-select-inline').value = '';
    document.getElementById('sv-loinhan-inline').value = '';
    const btn = formEl.querySelector('button[type="button"]');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời';
}

function submitMoiGVInline() {
    const select   = document.getElementById('gv-select-inline');
    const loinhan  = document.getElementById('gv-loinhan-inline');
    const resultEl = document.getElementById('gv-invite-result');
    const formEl   = document.getElementById('gv-invite-form');

    if (!select || !select.value) {
        showInlineResult(resultEl, false, 'Vui lòng chọn giảng viên cần mời.');
        return;
    }

    const btn = formEl.querySelector('button[type="button"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';

    const body = new URLSearchParams({
        ajax_action: 'moi_gvhd',
        idGV: select.value,
        loiNhan: loinhan?.value || ''
    });

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                const tenGV = select.options[select.selectedIndex]?.text || '';
                formEl.classList.add('d-none');
                showInlineResult(resultEl, true,
                    `<i class="bi bi-check-circle-fill me-2"></i>
                     <strong>Đã gửi lời mời GVHD!</strong><br>
                     <span class="small text-muted">Lời mời đã được gửi tới GV <strong>${escHtml(tenGV)}</strong>.</span>`
                );
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời GVHD';
                showInlineResult(resultEl, false, data.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Gửi lời mời GVHD';
            showInlineResult(resultEl, false, 'Lỗi kết nối.');
        });
}

function showInlineResult(el, success, html) {
    if (!el) return;
    el.classList.remove('d-none');
    el.innerHTML = `<div class="alert alert-${success ? 'success' : 'warning'} py-3 mb-0">${html}</div>`;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

let fileStore = [];

function handleFileSelect(input) {
    if (input.files.length) {
        Array.from(input.files).forEach(newFile => {
            const exists = fileStore.some(f => f.name === newFile.name && f.size === newFile.size);
            if (!exists) fileStore.push(newFile);
        });
        renderFileList();
    }
}

function removeFile(index) {
    fileStore.splice(index, 1);
    renderFileList();
}

function renderFileList() {
    const listEl  = document.getElementById('fileList-main');
    const itemsEl = document.getElementById('fileItems-main');
    if (!listEl || !itemsEl) return;

    if (!fileStore.length) { listEl.classList.add('d-none'); itemsEl.innerHTML = ''; return; }
    listEl.classList.remove('d-none');
    itemsEl.innerHTML = '';

    fileStore.forEach((file, index) => {
        const ext = file.name.split('.').pop().toLowerCase();
        let iconCls = ['zip','rar'].includes(ext) ? 'bi-file-earmark-zip text-warning'
            : ext==='pdf' ? 'bi-file-earmark-pdf text-danger'
            : ['doc','docx'].includes(ext) ? 'bi-file-earmark-word text-primary'
            : 'bi-file-earmark text-secondary';
        const sz = file.size < 1048576 ? (file.size/1024).toFixed(1)+' KB' : (file.size/1048576).toFixed(1)+' MB';
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 p-2 mb-2 border rounded bg-white';
        div.innerHTML = `<i class="bi ${iconCls} fs-5 flex-shrink-0"></i>
            <span class="flex-grow-1 text-truncate small">${escHtml(file.name)}</span>
            <span class="text-muted small">${sz}</span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeFile(${index})"><i class="bi bi-x"></i></button>`;
        itemsEl.appendChild(div);
    });
    syncToInput();
}

function syncToInput() {
    const input = document.getElementById('fileInput-main');
    if (!input) return;
    try {
        const dt = new DataTransfer();
        fileStore.forEach(f => dt.items.add(f));
        input.files = dt.files;
    } catch(e) {}
}

const dropzone = document.getElementById('dropzone-main');
if (dropzone) {
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault(); dropzone.classList.remove('dragover');
        Array.from(e.dataTransfer.files).forEach(newFile => {
            const exists = fileStore.some(f => f.name===newFile.name && f.size===newFile.size);
            if (!exists) fileStore.push(newFile);
        });
        renderFileList();
    });
}

<?php if (($flashMsg ?? '') === 'nop_bai_thanh_cong'): ?>
document.addEventListener('DOMContentLoaded', () => { showSubmitSuccessPopup(); });
<?php endif; ?>

function showSubmitSuccessPopup() {
    const overlay = document.createElement('div');
    overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-50';
    overlay.style.zIndex = '9999';
    overlay.innerHTML = `
        <div class="bg-white rounded-4 p-5 text-center shadow-lg" style="max-width:420px;width:90%;animation:popIn .35s cubic-bezier(.34,1.56,.64,1)">
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 bg-success"
                style="width:80px;height:80px">
                <i class="bi bi-check-lg text-white" style="font-size:2.5rem"></i>
            </div>
            <h4 class="fw-bold mb-2">Nộp bài thành công!</h4>
            <p class="text-muted mb-4">Bài nộp của bạn đã được ghi nhận và đang chờ duyệt.</p>
            <button class="btn btn-success px-4 me-2" onclick="switchToAllSubmissions()">
                <i class="bi bi-folder2-open me-2"></i>Xem bài nộp
            </button>
            <button class="btn btn-light px-4" onclick="this.closest('.position-fixed').remove()">Đóng</button>
        </div>`;
    document.body.appendChild(overlay);
    setTimeout(() => { if (overlay.parentNode) overlay.remove(); }, 8000);
}

function switchToAllSubmissions() {
    document.querySelector('.position-fixed')?.remove();
    const tabEl = document.querySelector('[data-bs-target="#subtab-tatcabainop"]');
    if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
}
</script>
