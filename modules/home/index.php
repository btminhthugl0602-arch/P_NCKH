<?php
if (!defined('_AUTHEN')) {
  die('Truy cập không hợp lệ');
}

require_once _PATH_URL . '/modules/functions/quan_ly_su_kien.php';
require_once _PATH_URL . '/modules/functions/quan_ly_thong_bao.php';

// =============================================
// QUERY DỮ LIỆU
// =============================================
$now     = date('Y-m-d H:i:s');
$id_user = $_SESSION['user_id'] ?? 0;

// Thống kê hero (tái sử dụng query pattern từ dashboard)
$total_sk   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sukien WHERE isActive = 1"))['c'] ?? 0);
$total_nhom = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM nhom"))['c'] ?? 0);
$total_sv   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sinhvien"))['c'] ?? 0);
$total_gv   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM giangvien"))['c'] ?? 0);

// Danh sách sự kiện — tái sử dụng query từ modules/event/index.php
$sql_sk = "SELECT sk.*, ct.tenCap, tk.tenTK as nguoiTaoTen
           FROM sukien sk
           LEFT JOIN cap_tochuc ct ON sk.idCap = ct.idCap
           LEFT JOIN taikhoan tk ON sk.nguoiTao = tk.idTK
           WHERE sk.isActive = 1
           ORDER BY
               CASE
                   WHEN sk.ngayMoDangKy IS NOT NULL
                        AND sk.ngayDongDangKy IS NOT NULL
                        AND CURDATE() BETWEEN sk.ngayMoDangKy AND sk.ngayDongDangKy THEN 1
                   WHEN sk.ngayMoDangKy IS NOT NULL
                        AND CURDATE() < sk.ngayMoDangKy THEN 2
                   WHEN sk.ngayDongDangKy IS NOT NULL
                        AND CURDATE() > sk.ngayDongDangKy THEN 3
                   ELSE 4
               END,
               sk.idSK DESC
           LIMIT 6";

$result_sk = mysqli_query($conn, $sql_sk);
$events    = [];
if ($result_sk && mysqli_num_rows($result_sk) > 0) {
  while ($row = mysqli_fetch_assoc($result_sk)) {
    $events[] = $row;
  }
}

// Thông báo — công khai + riêng nếu đã đăng nhập
if ($id_user > 0) {
  $sql_tb = "SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao,
                      tb.ngayGui, tb.isPublic, tb.idSK, sk.tenSK,
                      COALESCE(tbn.daDoc, 1) as daDoc
               FROM thongbao tb
               LEFT JOIN sukien sk ON tb.idSK = sk.idSK
               LEFT JOIN thongbao_nguoinhan tbn
                      ON tb.idThongBao = tbn.idThongBao AND tbn.idTK = $id_user
               WHERE tb.isPublic = 1 OR tbn.idTK = $id_user
               ORDER BY tb.ngayGui DESC
               LIMIT 8";
} else {
  $sql_tb = "SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao,
                      tb.ngayGui, tb.isPublic, tb.idSK, sk.tenSK,
                      1 as daDoc
               FROM thongbao tb
               LEFT JOIN sukien sk ON tb.idSK = sk.idSK
               WHERE tb.isPublic = 1
               ORDER BY tb.ngayGui DESC
               LIMIT 8";
}
$result_tb    = mysqli_query($conn, $sql_tb);
$ds_thong_bao = [];
$so_chua_doc  = 0;
if ($result_tb) {
  while ($row = mysqli_fetch_assoc($result_tb)) {
    if (!$row['daDoc']) $so_chua_doc++;
    $ds_thong_bao[] = $row;
  }
}

// Ảnh xoay vòng sự kiện
$event_images = [
  _HOST_URL_TEMPLATES . '/assets/img/education/events-3.webp',
  _HOST_URL_TEMPLATES . '/assets/img/education/events-5.webp',
  _HOST_URL_TEMPLATES . '/assets/img/education/events-7.webp',
  _HOST_URL_TEMPLATES . '/assets/img/education/campus-4.webp',
  _HOST_URL_TEMPLATES . '/assets/img/education/campus-8.webp',
  _HOST_URL_TEMPLATES . '/assets/img/education/activities-3.webp',
];

$months_vi = ['', 'T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];

layout('header');
layout('navbar');
?>
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
                    data-purecounter-end="<?php echo $total_sv + $total_gv; ?>"
                    data-purecounter-duration="2"></span>
                  <span class="label">Người dùng</span>
                </div>
                <div class="stat-item">
                  <span class="number purecounter" data-purecounter-start="0"
                    data-purecounter-end="<?php echo $total_sk; ?>"
                    data-purecounter-duration="2"></span>
                  <span class="label">Sự kiện</span>
                </div>
                <div class="stat-item">
                  <span class="number purecounter" data-purecounter-start="0"
                    data-purecounter-end="<?php echo $total_nhom; ?>"
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
                <img src="<?php echo _HOST_URL_TEMPLATES; ?>/assets/img/education/showcase-4.webp"
                  alt="Sự kiện NCKH" class="img-fluid">
              </div>
              <div class="floating-cards">
                <div class="course-card" data-aos="fade-up" data-aos-delay="300">
                  <div class="card-icon"></div>
                  <div class="card-content">
                    <h6>Hội nghị nghiên cứu khoa học</h6>
                  </div>
                </div>
                <div class="course-card" data-aos="fade-up" data-aos-delay="400">
                  <div class="card-icon"></div>
                  <div class="card-content">
                    <h6>Hội thảo khoa học</h6>
                  </div>
                </div>
                <div class="course-card" data-aos="fade-up" data-aos-delay="500">
                  <div class="card-icon"></div>
                  <div class="card-content">
                    <h6>Tranh biện</h6>
                  </div>
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

        <!-- ===== CỘT TRÁI: SỰ KIỆN (course-card grid) ===== -->
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

                  // Logic trạng thái — giống modules/event/index.php
                  $ts_now        = time();
                  $ts_moDangKy   = !empty($event['ngayMoDangKy'])   ? strtotime($event['ngayMoDangKy'])   : 0;
                  $ts_dongDangKy = !empty($event['ngayDongDangKy']) ? strtotime($event['ngayDongDangKy']) : 0;
                  $ts_batDau     = !empty($event['ngayBatDau'])     ? strtotime($event['ngayBatDau'])     : 0;
                  $ts_ketThuc    = !empty($event['ngayKetThuc'])    ? strtotime($event['ngayKetThuc'])    : 0;

                  // Badge dùng class của template: featured / new / popular / certificate
                  if ($ts_moDangKy > 0 && $ts_dongDangKy > 0) {
                    if ($ts_now < $ts_moDangKy) {
                      $trangThai  = 'Sắp mở đăng ký';
                      $badgeClass = 'popular';
                    } elseif ($ts_now >= $ts_moDangKy && $ts_now <= $ts_dongDangKy) {
                      $trangThai  = 'Đang mở đăng ký';
                      $badgeClass = 'new';
                    } elseif ($ts_now > $ts_dongDangKy && $ts_batDau > 0 && $ts_now < $ts_batDau) {
                      $trangThai  = 'Đã đóng đăng ký';
                      $badgeClass = 'certificate';
                    } elseif ($ts_batDau > 0 && $ts_now >= $ts_batDau && ($ts_ketThuc == 0 || $ts_now <= $ts_ketThuc)) {
                      $trangThai  = 'Đang diễn ra';
                      $badgeClass = 'featured';
                    } else {
                      $trangThai  = 'Đã kết thúc';
                      $badgeClass = 'certificate';
                    }
                  } else {
                    $trangThai  = 'Chưa cấu hình';
                    $badgeClass = 'popular';
                  }
                ?>
                  <div class="col-md-6" data-aos="fade-up"
                    data-aos-delay="<?php echo 200 + ($i % 3) * 100; ?>">
                    <div class="course-card">
                      <div class="course-image">
                        <img src="<?php echo $event_images[$i % count($event_images)]; ?>"
                          alt="<?php echo htmlspecialchars($event['tenSK']); ?>">
                        <div class="badge <?php echo $badgeClass; ?>"><?php echo $trangThai; ?></div>
                      </div>
                      <div class="course-content">
                        <div class="course-meta">
                          <?php if (!empty($event['tenCap'])): ?>
                            <span class="level"><?php echo htmlspecialchars($event['tenCap']); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($ngayBatDau)): ?>
                            <span class="duration">
                              <i class="bi bi-calendar3 me-1"></i><?php echo $ngayBatDau; ?>
                            </span>
                          <?php endif; ?>
                        </div>

                        <h3>
                          <a
                            href="<?php echo _HOST_URL; ?>/?module=event&action=view&id=<?php echo (int)$event['idSK']; ?>">
                            <?php echo htmlspecialchars($event['tenSK']); ?>
                          </a>
                        </h3>

                        <?php if (!empty($event['moTa'])): ?>
                          <p><?php
                              echo htmlspecialchars(substr($event['moTa'], 0, 100));
                              echo strlen($event['moTa']) > 100 ? '...' : '';
                              ?></p>
                        <?php endif; ?>

                        <div class="instructor">
                          <div class="instructor-info">
                            <h6><?php echo htmlspecialchars($event['nguoiTaoTen'] ?? 'BTC'); ?></h6>
                            <span>Ban tổ chức</span>
                          </div>
                        </div>

                        <div class="course-stats">
                          <div class="rating">
                            <i class="bi bi-clock"></i>
                            <span>
                              <?php if (!empty($ngayBatDau) && !empty($ngayKetThuc)): ?>
                                <?php echo $ngayBatDau; ?> — <?php echo $ngayKetThuc; ?>
                              <?php elseif (!empty($ngayBatDau)): ?>
                                Từ <?php echo $ngayBatDau; ?>
                              <?php else: ?>
                                Chưa xác định thời gian
                              <?php endif; ?>
                            </span>
                          </div>
                        </div>

                        <a href="<?php echo _HOST_URL; ?>/?module=event&action=view&id=<?php echo (int)$event['idSK']; ?>"
                          class="btn-course">Xem sự kiện</a>
                      </div>
                    </div>
                  </div><!-- End Event Card -->
                <?php endforeach; ?>
              </div><!-- /row gy-4 -->
            </div><!-- /featured-courses wrapper -->

            <div class="more-courses text-center mt-4" data-aos="fade-up" data-aos-delay="500">
              <a href="<?php echo _HOST_URL; ?>/?module=event" class="btn-more">Xem tất cả sự kiện</a>
            </div>

          <?php endif; ?>
        </div><!-- /col-lg-8 -->


        <!-- ===== CỘT PHẢI: THÔNG BÁO SIDEBAR ===== -->
        <div class="col-lg-4">
          <div class="sidebar-widget upcoming-widget" data-aos="fade-up" data-aos-delay="200">

            <h4 class="widget-title d-flex align-items-center justify-content-between">
              <span><i class="bi bi-bell me-2"></i>Thông báo</span>
              <?php if ($so_chua_doc > 0): ?>
                <span class="badge bg-danger rounded-pill"
                  style="font-size:.72rem;"><?php echo $so_chua_doc; ?> mới</span>
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
                    'Kết quả'  => 'bi-trophy',
                    'Nhắc nhở' => 'bi-alarm',
                  ][$tb['loaiThongBao']] ?? 'bi-info-circle';
                ?>
                  <div
                    class="upcoming-item <?php echo $chua_doc ? 'border-start border-primary border-3 ps-2' : ''; ?>">
                    <div class="upcoming-date">
                      <span class="day"><?php echo date('d', strtotime($tb['ngayGui'])); ?></span>
                      <span
                        class="month"><?php echo $months_vi[(int)date('m', strtotime($tb['ngayGui']))]; ?></span>
                    </div>
                    <div class="upcoming-content">
                      <h5 class="upcoming-title">
                        <a href="<?php echo $link_tb; ?>">
                          <?php if ($chua_doc): ?><strong><?php endif; ?>
                            <?php echo htmlspecialchars($tb['tieuDe']); ?>
                            <?php if ($chua_doc): ?></strong><?php endif; ?>
                        </a>
                      </h5>
                      <div class="upcoming-meta">
                        <?php if (!empty($tb['loaiThongBao'])): ?>
                          <span class="time">
                            <i class="bi <?php echo $loai_icon; ?>"></i>
                            <?php echo htmlspecialchars($tb['loaiThongBao']); ?>
                          </span>
                        <?php endif; ?>
                        <?php if (!empty($tb['tenSK'])): ?>
                          <span class="price" style="font-size:.73rem;color:#888;">
                            <?php echo htmlspecialchars(mb_strimwidth($tb['tenSK'], 0, 26, '…')); ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div><!-- /upcoming-list -->
            <?php endif; ?>

          </div><!-- /Thông báo Widget -->
        </div><!-- /col-lg-4 -->

      </div><!-- /row -->
    </div>
  </section><!-- /Sự kiện + Thông báo -->

</main>

<?php layout('footer'); ?>