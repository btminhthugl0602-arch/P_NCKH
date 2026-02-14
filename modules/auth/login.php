<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
  <main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
      <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0">Trang đăng nhập</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.html">Home</a></li>
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

              <form class="enrollment-form" data-aos="fade-up" data-aos-delay="300">

                <div class="row mb-4">
                  <div class="col-12">
                    <div class="form-group">
                      <label for="firstName" class="form-label">Tên đăng nhập hoặc email*</label>
                      <input type="text" id="firstName" name="firstName" class="form-control" required="" autocomplete="given-name">
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="form-group">
                      <label for="email" class="form-label">Mật khẩu*</label>
                      <input type="email" id="email" name="email" class="form-control" required="" autocomplete="email">
                    </div>
                <div class="row">
                  <div class="col-12 text-center">
                    <button type="submit" class="btn btn-enroll">
                      Đăng nhập
                    </button>
                    <p class="enrollment-note mt-3">
                      Đăng nhập với tư cách khách
                    </p>
                  </div>
                </div>

              </form>

            </div>
          </div><!-- End Form Column -->
            </div>
          </div><!-- End Benefits Column -->

        </div>

      </div>

    </section><!-- /Enroll Section -->

  </main>