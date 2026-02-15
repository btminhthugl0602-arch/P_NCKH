<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
layout('header');
layout('navbar');
?>
<main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
      <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0">Người dùng</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.html">Home</a></li>
            <li class="current">Người dùng</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Instructors Section -->
    <!-- Instructors Section -->
<section id="instructors" class="instructors section">

  <div class="container" data-aos="fade-up" data-aos-delay="100">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold">Danh sách người dùng</h3>
      <a href="#" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Tạo tài khoản
      </a>
    </div>

    <!-- Search -->
    <div class="mb-3">
      <div class="input-group">
        <input type="text" class="form-control" placeholder="Tìm kiếm theo tên...">
        <button class="btn btn-primary">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">

        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>Tên</th>
            <th>Đơn vị</th>
            <th>Số sự kiện</th>
            <th>Vai trò</th>
            <th>Hành động</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>1</td>
            <td>Sarah Johnson</td>
            <td>Web Development</td>
            <td>18</td>
            <td>Giảng viên</td>
            <td>
              <a href="instructor-profile.html" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> View Profile
              </a>
            </td>
          </tr>

          <tr>
            <td>2</td>
            <td>Michael Chen</td>
            <td>Data Science</td>
            <td>24</td>
            <td>Giảng viên</td>
            <td>
              <a href="instructor-profile.html" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> View Profile
              </a>
            </td>
          </tr>

          <tr>
            <td>3</td>
            <td>Amanda Rodriguez</td>
            <td>UX Design</td>
            <td>15</td>
            <td>Sinh viên</td>
            <td>
              <a href="instructor-profile.html" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> View Profile
              </a>
            </td>
          </tr>

          <tr>
            <td>4</td>
            <td>David Thompson</td>
            <td>Digital Marketing</td>
            <td>21</td>
            <td>Giảng viên</td>
            <td>
              <a href="instructor-profile.html" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> View Profile
              </a>
            </td>
          </tr>

        </tbody>

      </table>
    </div>

  </div>

</section>

  </main>
  <?php layout('footer'); ?>