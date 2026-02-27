<main class="main">

  <!-- Hero Section -->
  <section id="courses-hero" class="courses-hero section light-background">
    <div class="hero-content">
      <div class="container">
        <div class="row align-items-center">

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="hero-text">
              <h1>Nền tảng tổ chức &amp; quản lý sự kiện</h1>
              <p>Hệ thống hỗ trợ quản lý đăng ký, chấm điểm, phân công giảng viên
                và theo dõi tiến độ các cuộc thi, sự kiện nghiên cứu khoa học.</p>

              <div class="hero-stats">
                <div class="stat-item">
                  <span class="number purecounter" data-purecounter-start="0"
                    data-purecounter-end="<?= $total_sv + $total_gv ?>"
                    data-purecounter-duration="2"></span>
                  <span class="label">Người dùng</span>
                </div>
                <div class="stat-item">
                  <span class="number purecounter" data-purecounter-start="0"
                    data-purecounter-end="<?= $total_sk ?>"
                    data-purecounter-duration="2"></span>
                  <span class="label">Sự kiện</span>
                </div>
                <div class="stat-item">
                  <span class="number purecounter" data-purecounter-start="0"
                    data-purecounter-end="<?= $total_nhom ?>"
                    data-purecounter-duration="2"></span>
                  <span class="label">Nhóm tham gia</span>
                </div>
              </div>

              <div class="hero-buttons">
                <a href="#su-kien" class="btn btn-primary">Xem sự kiện</a>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <div class="hero-image">
              <div class="main-image">
                <img src="<?= _HOST_URL_TEMPLATES ?>/assets/img/education/showcase-4.webp"
                  alt="Sự kiện NCKH" class="img-fluid">
              </div>
              <div class="floating-cards">
                <div class="course-card" data-aos="fade-up" data-aos-delay="300">
                  <div class="card-icon"></div>
                  <div class="card-content"><h6>Hội nghị nghiên cứu khoa học</h6></div>
                </div>
                <div class="course-card" data-aos="fade-up" data-aos-delay="400">
                  <div class="card-icon"></div>
                  <div class="card-content"><h6>Hội thảo khoa học</h6></div>
                </div>
                <div class="course-card" data-aos="fade-up" data-aos-delay="500">
                  <div class="card-icon"></div>
                  <div class="card-content"><h6>Tranh biện</h6></div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
    <div class="hero-background">
      <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
      </div>
    </div>
  </section><!-- /Hero Section -->


  <!-- Sự kiện + Thông báo -->
  <section id="su-kien" class="courses-events section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">
      <div class="row">

        <!-- ===== CỘT TRÁI: SỰ KIỆN ===== -->
        <div class="col-lg-8">
          <div class="container section-title mb-4" data-aos="fade-up">
            <h2>Sự kiện</h2>
            <p>Các sự kiện được công khai trên hệ thống</p>
          </div>

          <?php if (empty($events)): ?>
            <div class="alert alert-info" role="alert">
              <i class="bi bi-info-circle me-2"></i>Hiện chưa có sự kiện nào được công bố.
            </div>
          <?php else: ?>
            <div class="featured-courses">
              <div class="row gy-4">
                <?php foreach ($events as $i => $event):
                  $ngayBatDau  = !empty($event['ngayBatDau'])  ? date('d/m/Y', strtotime($event['ngayBatDau']))  : '';
                  $ngayKetThuc = !empty($event['ngayKetThuc']) ? date('d/m/Y', strtotime($event['ngayKetThuc'])) : '';

                  $ts_now        = time();
                  $ts_moDangKy   = !empty($event['ngayMoDangKy'])   ? strtotime($event['ngayMoDangKy'])   : 0;
                  $ts_dongDangKy = !empty($event['ngayDongDangKy']) ? strtotime($event['ngayDongDangKy']) : 0;
                  $ts_batDau     = !empty($event['ngayBatDau'])     ? strtotime($event['ngayBatDau'])     : 0;
                  $ts_ketThuc    = !empty($event['ngayKetThuc'])    ? strtotime($event['ngayKetThuc'])    : 0;

                  if ($ts_moDangKy > 0 && $ts_dongDangKy > 0) {
                    if ($ts_now < $ts_moDangKy) {
                      $trangThai  = 'Sắp mở đăng ký'; $badgeClass = 'popular';
                    } elseif ($ts_now <= $ts_dongDangKy) {
                      $trangThai  = 'Đang mở đăng ký'; $badgeClass = 'new';
                    } elseif ($ts_batDau > 0 && $ts_now < $ts_batDau) {
                      $trangThai  = 'Đã đóng đăng ký'; $badgeClass = 'certificate';
                    } elseif ($ts_batDau > 0 && $ts_now >= $ts_batDau && ($ts_ketThuc == 0 || $ts_now <= $ts_ketThuc)) {
                      $trangThai  = 'Đang diễn ra'; $badgeClass = 'featured';
                    } else {
                      $trangThai  = 'Đã kết thúc'; $badgeClass = 'certificate';
                    }
                  } else {
                    $trangThai  = 'Chưa cấu hình'; $badgeClass = 'popular';
                  }
                ?>
                  <div class="col-md-6" data-aos="fade-up" data-aos-delay="<?= 200 + ($i % 3) * 100 ?>">
                    <div class="course-card">
                      <div class="course-image">
                        <img src="<?= $event_images[$i % count($event_images)] ?>"
                          alt="<?= htmlspecialchars($event['tenSK']) ?>">
                        <div class="badge <?= $badgeClass ?>"><?= $trangThai ?></div>
                      </div>
                      <div class="course-content">
                        <div class="course-meta">
                          <?php if (!empty($event['tenCap'])): ?>
                            <span class="level"><?= htmlspecialchars($event['tenCap']) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($ngayBatDau)): ?>
                            <span class="duration">
                              <i class="bi bi-calendar3 me-1"></i><?= $ngayBatDau ?>
                            </span>
                          <?php endif; ?>
                        </div>

                        <h3>
                          <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= (int)$event['idSK'] ?>">
                            <?= htmlspecialchars($event['tenSK']) ?>
                          </a>
                        </h3>

                        <?php if (!empty($event['moTa'])): ?>
                          <p><?= htmlspecialchars(mb_strimwidth($event['moTa'], 0, 100, '...')) ?></p>
                        <?php endif; ?>

                        <div class="instructor">
                          <div class="instructor-info">
                            <h6><?= htmlspecialchars($event['nguoiTaoTen'] ?? 'BTC') ?></h6>
                            <span>Ban tổ chức</span>
                          </div>
                        </div>

                        <div class="course-stats">
                          <div class="rating">
                            <i class="bi bi-clock"></i>
                            <span>
                              <?php if (!empty($ngayBatDau) && !empty($ngayKetThuc)): ?>
                                <?= $ngayBatDau ?> — <?= $ngayKetThuc ?>
                              <?php elseif (!empty($ngayBatDau)): ?>
                                Từ <?= $ngayBatDau ?>
                              <?php else: ?>
                                Chưa xác định thời gian
                              <?php endif; ?>
                            </span>
                          </div>
                        </div>

                        <a href="<?= _HOST_URL ?>/?module=event&action=view&id=<?= (int)$event['idSK'] ?>"
                          class="btn-course">Xem sự kiện</a>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="more-courses text-center mt-4" data-aos="fade-up" data-aos-delay="500">
              <a href="<?= _HOST_URL ?>/?module=event" class="btn-more">Xem tất cả sự kiện</a>
            </div>
          <?php endif; ?>
        </div><!-- /col-lg-8 -->


        <!-- ===== CỘT PHẢI: THÔNG BÁO SIDEBAR ===== -->
        <div class="col-lg-4">
          <div class="sidebar-widget upcoming-widget" data-aos="fade-up" data-aos-delay="200">

            <h4 class="widget-title d-flex align-items-center justify-content-between">
              <span><i class="bi bi-bell me-2"></i>Thông báo</span>
              <?php if ($so_chua_doc > 0): ?>
                <span class="badge bg-danger rounded-pill" style="font-size:.72rem;">
                  <?= $so_chua_doc ?> mới
                </span>
              <?php endif; ?>
            </h4>

            <?php if (empty($ds_thong_bao)): ?>
              <p class="text-muted small px-1 mb-0">Chưa có thông báo nào.</p>
            <?php else: ?>
              <div class="upcoming-list">
                <?php foreach ($ds_thong_bao as $tb):
                  $chua_doc  = !$tb['daDoc'];
                  $link_tb   = !empty($tb['idSK'])
                    ? _HOST_URL . '/?module=event&action=view&id=' . (int)$tb['idSK']
                    : '#';
                  $loai_icon = [
                    'Chung'     => 'bi-megaphone',
                    'Kết quả'   => 'bi-trophy',
                    'Nhắc nhở'  => 'bi-alarm',
                  ][$tb['loaiThongBao']] ?? 'bi-info-circle';
                ?>
                  <div class="upcoming-item <?= $chua_doc ? 'border-start border-primary border-3 ps-2' : '' ?>">
                    <div class="upcoming-date">
                      <span class="day"><?= date('d', strtotime($tb['ngayGui'])) ?></span>
                      <span class="month"><?= $months_vi[(int)date('m', strtotime($tb['ngayGui']))] ?></span>
                    </div>
                    <div class="upcoming-content">
                      <h5 class="upcoming-title">
                        <a href="<?= $link_tb ?>">
                          <?php if ($chua_doc): ?><strong><?php endif; ?>
                          <?= htmlspecialchars($tb['tieuDe']) ?>
                          <?php if ($chua_doc): ?></strong><?php endif; ?>
                        </a>
                      </h5>
                      <div class="upcoming-meta">
                        <?php if (!empty($tb['loaiThongBao'])): ?>
                          <span class="time">
                            <i class="bi <?= $loai_icon ?>"></i>
                            <?= htmlspecialchars($tb['loaiThongBao']) ?>
                          </span>
                        <?php endif; ?>
                        <?php if (!empty($tb['tenSK'])): ?>
                          <span class="price" style="font-size:.73rem;color:#888;">
                            <?= htmlspecialchars(mb_strimwidth($tb['tenSK'], 0, 26, '…')) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          </div>
        </div><!-- /col-lg-4 -->

      </div>
    </div>
  </section><!-- /Sự kiện + Thông báo -->

</main>
