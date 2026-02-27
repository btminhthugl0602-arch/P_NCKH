<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Trang đăng nhập</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?= _HOST_URL ?>">Home</a></li>
                    <li class="current">Đăng nhập</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Enroll Section -->
    <section id="enroll" class="enroll section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="enrollment-form-wrapper">

                        <div class="enrollment-header text-center mb-5" data-aos="fade-up" data-aos-delay="200">
                            <h2>Hệ thống quản lí sự kiện</h2>
                            <p>Điền tên đăng nhập hoặc email và mật khẩu để đăng nhập</p>
                        </div>

                        <!-- Thông báo lỗi -->
                        <?php if ($tb_dang_nhap !== ''): ?>
                        <div class="alert alert-<?= $error_class ?> alert-dismissible fade show mb-4" role="alert"
                            data-aos="fade-up" data-aos-delay="250">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($tb_dang_nhap) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <form class="enrollment-form" action="" method="POST" data-aos="fade-up" data-aos-delay="300">
                            <?= csrfInput() ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="tenTK" class="form-label">Tên đăng nhập hoặc email</label>
                                        <input type="text" id="tenTK" name="tenTK" class="form-control" required
                                            autocomplete="username"
                                            value="<?= htmlspecialchars($_POST['tenTK'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="form-group">
                                        <label for="matKhau" class="form-label">Mật khẩu</label>
                                        <input type="password" id="matKhau" name="matKhau" class="form-control" required
                                            autocomplete="current-password">
                                    </div>
                                </div>

                                <div class="col-12 text-center mt-3">
                                    <button type="submit" name="btn_dang_nhap" class="btn btn-enroll">
                                        Đăng nhập
                                    </button>
                                </div>

                                <div class="col-12 text-center mt-3">
                                    <a href="<?= _HOST_URL ?>/?module=auth&action=login&guest=1">
                                        Đăng nhập với tư cách khách.
                                    </a>
                                </div>
                            </div>
                        </form>

                    </div>
                </div><!-- End Form Column -->
            </div>
        </div>
    </section><!-- /Enroll Section -->

</main>
