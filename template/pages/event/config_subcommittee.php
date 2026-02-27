
<style>
    .nav-tabs .nav-link { font-weight: 500; color: #6c757d; font-size: 1.05rem; padding: 12px 20px; border: none; border-bottom: 3px solid transparent;}
    .nav-tabs .nav-link.active { color: #0d6efd !important; font-weight: bold; border-bottom: 3px solid #0d6efd; background: transparent; }
    
    .tb-overview-card { border: 1px solid #eaeaea; border-radius: 12px; padding: 20px 25px; display: flex; align-items: center; gap: 20px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.02);}
    .tb-overview-icon { width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: white; flex-shrink: 0;}
    
    .tb-detail-card { border: 1px solid #eaeaea; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 25px; overflow: hidden; border-left: 5px solid #0d6efd;}
    .tb-detail-header { padding: 20px 25px; border-bottom: 1px solid #f5f5f5; display: flex; justify-content: space-between; align-items: flex-start; background-color: #fafbfc;}
    .tb-detail-body { padding: 25px;}
    .btn-circle-icon { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; border: none; color: white; transition: all 0.2s;}
    .btn-circle-icon.edit { background-color: #0d6efd; }
    .btn-circle-icon.delete { background-color: #dc3545; }
    .sp-list-item { padding: 12px 0; border-bottom: 1px dashed #f0f0f0; display: flex; justify-content: space-between; align-items: center;}
    .btn-remove-sp { background: none; border: none; color: #dc3545; padding: 0; display: flex; align-items: center;}

    /* ÉP KIỂU TIÊU ĐỀ BẢNG PHÂN CÔNG GIÁM KHẢO */
    .table-subcommittee-header th { background-color: #0d6efd !important; color: #ffffff !important; padding: 15px !important; font-weight: 600 !important; border: 1px solid #dee2e6 !important; text-align: center; }
    .table-bordered-custom { border: 1px solid #dee2e6 !important; }
    .table-bordered-custom td { border: 1px solid #dee2e6 !important; padding: 12px !important; }
    .pill-badge { border: 1px solid #e0e0e0; border-radius: 50rem; padding: 6px 18px; color: #495057; background: #f8f9fa; display: inline-flex; align-items: center; font-size: 0.85rem; font-weight: 500;}
</style>

<main class="main container py-4" style="min-height: 80vh;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0"><i class="bi bi-diagram-3 text-warning me-2"></i>Quản lý Tiểu ban & Hội đồng</h2>
        <a href="?module=event&action=view&id=<?php echo $id_su_kien; ?>" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Về trang sự kiện</a>
    </div>

    <ul class="nav nav-tabs mb-4 border-bottom">
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 1 ? 'active' : ''; ?>" href="?module=event&action=config_subcommittee&id=<?php echo $id_su_kien; ?>&tab=1">
                <i class="bi bi-box-seam me-1"></i> Quản lý Tiểu ban (Báo cáo)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 2 ? 'active' : ''; ?>" href="?module=event&action=config_subcommittee&id=<?php echo $id_su_kien; ?>&tab=2">
                <i class="bi bi-people me-1"></i> Phân công Ban Giám Khảo
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <?php if (empty($tieuban_list)): ?> 
            <div class="text-center py-5 text-muted bg-white rounded-3 border dashed border-2 shadow-sm">
                <i class="bi bi-box-seam fs-1 d-block mb-3 text-secondary"></i>
                <h5 class="fw-bold">Chưa có tiểu ban nào</h5>
                <p>Hãy khởi tạo tiểu ban để phân công phòng thi.</p>
                <?php if ($active_tab == 1): ?><button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalCreateTB">Tạo ngay</button><?php endif; ?>
            </div> 
        <?php else: ?>
            
            <?php if ($active_tab == 1): ?>
                <div class="row mb-4">
                    <div class="col-md-4"><div class="tb-overview-card"><div class="tb-overview-icon bg-primary shadow-sm"><i class="bi bi-collection"></i></div><div><h3 class="fw-bold text-dark m-0"><?php echo count($tieuban_list); ?></h3><span class="text-muted small">Tiểu ban đã tạo</span></div></div></div>
                    <div class="col-md-4"><div class="tb-overview-card"><div class="tb-overview-icon bg-success shadow-sm"><i class="bi bi-file-earmark-text"></i></div><div><h3 class="fw-bold text-dark m-0"><?php echo count($assigned_sp_ids); ?></h3><span class="text-muted small">Bài thi đã xếp phòng</span></div></div></div>
                    <div class="col-md-4"><div class="tb-overview-card"><div class="tb-overview-icon bg-warning shadow-sm"><i class="bi bi-clock-history text-dark"></i></div><div><h3 class="fw-bold text-dark m-0"><?php echo count($unassigned_sps); ?></h3><span class="text-muted small">Bài thi chờ xếp</span></div></div></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-dark m-0 d-flex align-items-center"><i class="bi bi-people-fill me-2 fs-3 text-primary"></i> Danh sách Tiểu ban báo cáo</h4>
                    <button class="btn btn-primary rounded-2 px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateTB"><i class="bi bi-plus-lg me-1"></i> Tạo tiểu ban mới</button>
                </div>

                <div class="row">
                    <?php foreach ($tieuban_list as $tb): 
                        $tb_id = $tb['idTieuBan']; 
                        $sps = $sp_tb_map[$tb_id] ?? []; 
                        $gvs = $gv_tb_map[$tb_id] ?? []; 
                    ?>
                    <div class="col-12">
                        <div class="tb-detail-card shadow-sm">
                            <div class="tb-detail-header">
                                <div>
                                    <h5 class="fw-bold text-primary m-0"><?=htmlspecialchars($tb['tenTieuBan'] ?? '')?></h5>
                                    <p class="text-muted small m-0 mt-1">
                                        <?=$tb['ngayBaoCao'] ? date('d/m/Y', strtotime($tb['ngayBaoCao'])) : '...'?> | Phòng: <?=htmlspecialchars($tb['diaDiem'] ?: '...')?> | 
                                        Tiêu chí: <span class="fw-bold text-info"><?=htmlspecialchars($tb['tenBoTieuChi'] ?: 'Dùng chung theo Vòng')?></span>
                                    </p>
                                </div>
                                <div class="d-flex gap-2"><button class="btn-circle-icon edit" data-bs-toggle="modal" data-bs-target="#mEditTB_<?=$tb_id?>"><i class="bi bi-pencil-fill"></i></button><form method="post" class="m-0"><input type="hidden" name="action" value="delete_tb"><input type="hidden" name="idTieuBan" value="<?=$tb_id?>"><button type="submit" class="btn-circle-icon delete" onclick="return confirm('Xóa tiểu ban này?');"><i class="bi bi-trash-fill"></i></button></form></div>
                            </div>
                            <div class="tb-detail-body">
                                <div class="mb-4">
                                    <h6 class="fw-bold text-dark mb-3">Hội đồng Ban giám khảo:</h6>
                                    <div class="d-flex flex-wrap gap-2"><?php if(empty($gvs)): ?><span class="text-muted small fst-italic">Chưa có thành viên nào.</span><?php else: foreach($gvs as $gv): ?><span class="pill-badge border text-secondary"><?=htmlspecialchars($gv['tenGV'] ?? '')?></span><?php endforeach; endif; ?></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3"><h6 class="fw-bold text-dark m-0">Bài báo cáo (<?=count($sps)?>):</h6><button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#mSp_<?=$tb_id?>"><i class="bi bi-plus-lg me-1"></i> Thêm bài</button></div>
                                <?php if(empty($sps)): ?><p class="text-muted small fst-italic text-center bg-light p-3 rounded border">Trống.</p><?php else: ?>
                                    <div class="pe-2">
                                        <?php foreach($sps as $sp): ?>
                                            <div class="sp-list-item">
                                                <span class="text-dark">
                                                    <span class="badge bg-secondary me-2"><?=htmlspecialchars($sp['tennhom'] ?: $sp['manhom'] ?: 'Cá nhân')?></span>
                                                    <strong><?=htmlspecialchars($sp['tensanpham'])?></strong>
                                                </span>
                                                <form method="post" class="m-0">
                                                    <input type="hidden" name="action" value="remove_sp_tb">
                                                    <input type="hidden" name="idTieuBan" value="<?=$tb_id?>">
                                                    <input type="hidden" name="idSanPham" value="<?=$sp['idSanPham']?>">
                                                    <button type="submit" class="btn-remove-sp" title="Rút bài"><i class="bi bi-x-circle-fill fs-5"></i></button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($active_tab == 2): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-dark m-0 d-flex align-items-center"><i class="bi bi-person-lines-fill me-2 fs-3 text-dark"></i> Phân công Ban Giám Khảo</h4>
                </div>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered-custom table-hover align-middle mb-0">
                                <thead class="table-subcommittee-header">
                                    <tr>
                                        <th width="25%">Tiểu ban</th>
                                        <th width="15%">Ngày</th>
                                        <th width="15%">Phòng</th>
                                        <th width="30%">Ban giám khảo</th>
                                        <th width="15%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tieuban_list as $tb): 
                                        $tb_id = $tb['idTieuBan']; 
                                        $gvs = $gv_tb_map[$tb_id] ?? []; 
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark text-center"><?=htmlspecialchars($tb['tenTieuBan'] ?? '')?></td>
                                        <td class="text-center"><?=$tb['ngayBaoCao'] ? date('d/m/Y', strtotime($tb['ngayBaoCao'])) : '...'?></td>
                                        <td class="text-center"><?=htmlspecialchars($tb['diaDiem'] ?: '...')?></td>
                                        <td>
                                            <?php if(empty($gvs)): ?><span class="text-muted small">Chưa có</span><?php else: ?>
                                                <div class="d-flex flex-wrap gap-2"><?php foreach($gvs as $gv): ?><span class="pill-badge shadow-sm"><?=htmlspecialchars($gv['tenGV'] ?? '')?></span><?php endforeach; ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#mGv_<?=$tb_id?>">
                                                <i class="bi bi-person-plus-fill me-1"></i> Phân công
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php foreach ($tieuban_list as $tb): 
    $tb_id = $tb['idTieuBan']; 
    $gvs = $gv_tb_map[$tb_id] ?? []; 
    $assigned_gv_ids = array_column($gvs, 'idGV'); 
    $available_gvs = array_filter($ds_giangvien, fn($g) => !in_array($g['idGV'], $assigned_gv_ids));
?>

<div class="modal fade" id="mEditTB_<?=$tb_id?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <input type="hidden" name="action" value="edit_tb">
                <input type="hidden" name="idTieuBan" value="<?=$tb_id?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Sửa: <?=htmlspecialchars($tb['tenTieuBan'] ?? '')?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="fw-bold">Tên tiểu ban</label>
                        <input type="text" name="tenTieuBan" class="form-control" value="<?=htmlspecialchars($tb['tenTieuBan'] ?? '')?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Bộ tiêu chí áp dụng riêng (Tùy chọn)</label>
                        <select name="idBoTieuChi" class="form-select">
                            <option value="">-- Dùng chung tiêu chí của Vòng thi --</option>
                            <?php foreach($botieuchi_list as $btc): ?>
                                <option value="<?=$btc['idBoTieuChi']?>" <?= ($tb['idBoTieuChi'] == $btc['idBoTieuChi']) ? 'selected' : '' ?>>
                                    <?=htmlspecialchars($btc['tenBoTieuChi'] ?? '')?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted fst-italic text-primary d-block mt-1">Đang chọn: <?=htmlspecialchars($tb['tenBoTieuChi'] ?? 'Dùng chung theo Vòng')?></small>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="fw-bold">Ngày BC</label>
                            <input type="date" name="ngayBaoCao" class="form-control" value="<?=htmlspecialchars($tb['ngayBaoCao'] ?? '')?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="fw-bold">Địa điểm</label>
                            <input type="text" name="diaDiem" class="form-control" value="<?=htmlspecialchars($tb['diaDiem'] ?? '')?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mSp_<?=$tb_id?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="post">
                <input type="hidden" name="action" value="add_sp_tb">
                <input type="hidden" name="idTieuBan" value="<?=$tb_id?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thêm bài vào <?=htmlspecialchars($tb['tenTieuBan'] ?? '')?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="fw-bold mb-2">Chọn bài (Đã duyệt)</label>
                    <select name="idSanPham" class="form-select" required>
                        <option value="">-- Chọn --</option>
                        <?php foreach($unassigned_sps as $sp): ?>
                            <option value="<?=$sp['idSanPham']?>">
                                <?=htmlspecialchars(($sp['tennhom'] ?: $sp['manhom'] ?: 'Cá nhân') . ' - ' . $sp['tensanpham'])?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mGv_<?=$tb_id?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Hội đồng: <?=htmlspecialchars($tb['tenTieuBan'] ?? '')?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form method="post" class="d-flex gap-2 mb-4 p-3 bg-white border rounded shadow-sm">
                    <input type="hidden" name="action" value="add_gv_tb">
                    <input type="hidden" name="active_tab" value="2">
                    <input type="hidden" name="idTieuBan" value="<?=$tb_id?>">
                    <select name="idGV" class="form-select form-select-sm" required>
                        <option value="">-- Thêm Giám khảo --</option>
                        <?php foreach($available_gvs as $gv): ?>
                            <option value="<?=$gv['idGV']?>"><?=htmlspecialchars($gv['tenGV'] ?? '')?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm text-nowrap"><i class="bi bi-plus-lg"></i> Thêm</button>
                </form>
                <h6 class="fw-bold mb-2 text-dark">Danh sách hiện tại:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php if(empty($gvs)): ?>
                        <span class="text-muted small">Chưa có ai.</span>
                    <?php else: foreach($gvs as $gv): ?>
                        <span class="badge bg-white text-dark border p-2 shadow-sm d-flex align-items-center">
                            <?=htmlspecialchars($gv['tenGV'] ?? '')?>
                            <form method="post" class="m-0 ms-2">
                                <input type="hidden" name="action" value="remove_gv_tb">
                                <input type="hidden" name="active_tab" value="2">
                                <input type="hidden" name="idTieuBan" value="<?=$tb_id?>">
                                <input type="hidden" name="idGV" value="<?=$gv['idGV']?>">
                                <button type="submit" class="btn-close" style="font-size: 0.6rem;"></button>
                            </form>
                        </span>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="modalCreateTB">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <input type="hidden" name="action" value="create_tb">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Khởi tạo Tiểu ban</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="fw-bold">Tên tiểu ban *</label>
                        <input type="text" name="tenTieuBan" class="form-control" placeholder="Tiểu ban AI" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Thuộc vòng *</label>
                        <select name="idVongThi" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach($vong_array as $v):?>
                                <option value="<?=$v['idVongThi']?>"><?=htmlspecialchars($v['tenVongThi'] ?? '')?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Bộ tiêu chí áp dụng riêng (Tùy chọn)</label>
                        <select name="idBoTieuChi" class="form-select">
                            <option value="">-- Dùng chung tiêu chí của Vòng thi --</option>
                            <?php foreach($botieuchi_list as $btc): ?>
                                <option value="<?=$btc['idBoTieuChi']?>"><?=htmlspecialchars($btc['tenBoTieuChi'] ?? '')?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted fst-italic">* Nếu để trống, tiểu ban sẽ dùng bộ tiêu chí mặc định của Vòng.</small>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="fw-bold">Ngày BC</label>
                            <input type="date" name="ngayBaoCao" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="fw-bold">Địa điểm</label>
                            <input type="text" name="diaDiem" class="form-control" placeholder="Phòng...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Khởi tạo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layout('footer'); ?>
