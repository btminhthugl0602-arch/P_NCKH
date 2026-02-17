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
            <h1 class="mb-2 mb-lg-0">Events</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo _HOST_URL ?>">Home</a></li>
                    <li class="current">Events</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Courses Events Section -->
    <section id="courses-events" class="courses-events section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row">

                <div class="col-lg-8">

                    <!-- Event Item -->
                    <article class="event-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="event-image">
                                    <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/events-3.webp"
                                        class="img-fluid" alt="Event Image">
                                    <div class="date-badge">
                                        <span class="day">15</span>
                                        <span class="month">Dec</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="event-content">
                                    <div class="event-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 2:00 PM - 4:00 PM</span>
                                        <span class="location"><i class="bi bi-geo-alt"></i> Online Webinar</span>
                                    </div>
                                    <h3 class="event-title">
                                        <a href="#">Advanced Data Science Techniques Workshop</a>
                                    </h3>
                                    <p class="event-description">Lorem ipsum dolor sit amet, consectetur adipiscing
                                        elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim
                                        ad minim veniam, quis nostrud exercitation.</p>
                                    <div class="event-footer">
                                        <div class="instructor">
                                            <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-8.webp"
                                                alt="Instructor" class="instructor-avatar">
                                            <span>Dr. Sarah Johnson</span>
                                        </div>
                                        <div class="event-price">
                                            <span class="price">$49</span>
                                        </div>
                                    </div>
                                    <div class="event-actions">
                                        <a href="<?php echo _HOST_URL ?>/?module=event&action=view"
                                            class="btn btn-primary">Xem sự kiện</a>
                                        <a href="#" class="btn btn-outline">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article><!-- End Event Item -->

                    <!-- Event Item -->
                    <article class="event-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="event-image">
                                    <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/events-7.webp"
                                        class="img-fluid" alt="Event Image">
                                    <div class="date-badge">
                                        <span class="day">22</span>
                                        <span class="month">Dec</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="event-content">
                                    <div class="event-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 6:00 PM - 8:30 PM</span>
                                        <span class="location"><i class="bi bi-geo-alt"></i> Hybrid Event</span>
                                    </div>
                                    <h3 class="event-title">
                                        <a href="#">Machine Learning for Beginners Masterclass</a>
                                    </h3>
                                    <p class="event-description">Vestibulum ante ipsum primis in faucibus orci luctus et
                                        ultrices posuere cubilia curae. Mauris viverra veniam sit amet lacus cursus, at
                                        tempor nunc dignissim.</p>
                                    <div class="event-footer">
                                        <div class="instructor">
                                            <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-m-12.webp"
                                                alt="Instructor" class="instructor-avatar">
                                            <span>Prof. Michael Chen</span>
                                        </div>
                                        <div class="event-price">
                                            <span class="price free">Free</span>
                                        </div>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#" class="btn btn-primary">Register Now</a>
                                        <a href="#" class="btn btn-outline">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article><!-- End Event Item -->

                    <!-- Event Item -->
                    <article class="event-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="event-image">
                                    <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/education/events-5.webp"
                                        class="img-fluid" alt="Event Image">
                                    <div class="date-badge">
                                        <span class="day">28</span>
                                        <span class="month">Dec</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="event-content">
                                    <div class="event-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 10:00 AM - 12:00 PM</span>
                                        <span class="location"><i class="bi bi-geo-alt"></i> Online Platform</span>
                                    </div>
                                    <h3 class="event-title">
                                        <a href="#">Web Development Career Summit</a>
                                    </h3>
                                    <p class="event-description">Nulla facilisi morbi tempus iaculis urna id volutpat
                                        lacus laoreet. Dignissim convallis aenean et tortor at risus viverra adipiscing
                                        at.</p>
                                    <div class="event-footer">
                                        <div class="instructor">
                                            <img src="<?php echo _HOST_URL_TEMPLATES ?>/assets/img/person/person-f-3.webp"
                                                alt="Instructor" class="instructor-avatar">
                                            <span>Emily Rodriguez</span>
                                        </div>
                                        <div class="event-price">
                                            <span class="price">$75</span>
                                        </div>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#" class="btn btn-primary">Register Now</a>
                                        <a href="#" class="btn btn-outline">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article><!-- End Event Item -->

                    <!-- Pagination -->
                    <nav class="pagination-wrapper" data-aos="fade-up" data-aos-delay="500">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">2</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">3</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>

                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">

                    <!-- Search Widget -->
                    <div class="sidebar-widget search-widget" data-aos="fade-up" data-aos-delay="200">
                        <h4 class="widget-title">Search Events</h4>
                        <form class="search-form">
                            <input type="text" placeholder="Search events..." class="form-control">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div><!-- End Search Widget -->

                    <!-- Filter Widget -->
                    <div class="sidebar-widget filter-widget" data-aos="fade-up" data-aos-delay="300">
                        <h4 class="widget-title">Filter Events</h4>
                        <div class="filter-content">
                            <div class="filter-group">
                                <label class="filter-label">Event Type</label>
                                <select class="form-select">
                                    <option value="">All Types</option>
                                    <option value="webinar">Webinar</option>
                                    <option value="workshop">Workshop</option>
                                    <option value="conference">Conference</option>
                                    <option value="masterclass">Masterclass</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Date Range</label>
                                <select class="form-select">
                                    <option value="">All Dates</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="quarter">Next 3 Months</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Price</label>
                                <select class="form-select">
                                    <option value="">All Prices</option>
                                    <option value="free">Free</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                            <button class="btn btn-primary filter-apply-btn">Apply Filters</button>
                        </div>
                    </div><!-- End Filter Widget -->

                    <!-- Upcoming Events Widget -->
                    <div class="sidebar-widget upcoming-widget" data-aos="fade-up" data-aos-delay="400">
                        <h4 class="widget-title">Upcoming Events</h4>
                        <div class="upcoming-list">

                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    <span class="day">18</span>
                                    <span class="month">Dec</span>
                                </div>
                                <div class="upcoming-content">
                                    <h5 class="upcoming-title">
                                        <a href="#">Python Programming Bootcamp</a>
                                    </h5>
                                    <div class="upcoming-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 3:00 PM</span>
                                        <span class="price">$95</span>
                                    </div>
                                </div>
                            </div>

                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    <span class="day">25</span>
                                    <span class="month">Dec</span>
                                </div>
                                <div class="upcoming-content">
                                    <h5 class="upcoming-title">
                                        <a href="#">Digital Marketing Strategies</a>
                                    </h5>
                                    <div class="upcoming-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 7:00 PM</span>
                                        <span class="price free">Free</span>
                                    </div>
                                </div>
                            </div>

                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    <span class="day">02</span>
                                    <span class="month">Jan</span>
                                </div>
                                <div class="upcoming-content">
                                    <h5 class="upcoming-title">
                                        <a href="#">UX Design Fundamentals</a>
                                    </h5>
                                    <div class="upcoming-meta">
                                        <span class="time"><i class="bi bi-clock"></i> 1:00 PM</span>
                                        <span class="price">$125</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div><!-- End Upcoming Events Widget -->

                    <!-- Newsletter Widget -->
                    <div class="sidebar-widget newsletter-widget" data-aos="fade-up" data-aos-delay="500">
                        <h4 class="widget-title">Stay Updated</h4>
                        <p>Subscribe to our newsletter and never miss important events and course announcements.</p>
                        <form action="forms/newsletter.php" method="post" class="php-email-form newsletter-form">
                            <input type="email" name="email" placeholder="Your email address" required="">
                            <button type="submit">Subscribe</button>
                            <div class="loading">Loading</div>
                            <div class="error-message"></div>
                            <div class="sent-message">Your subscription request has been sent. Thank you!</div>
                        </form>
                    </div><!-- End Newsletter Widget -->

                </div>

            </div>

        </div>

    </section><!-- /Courses Events Section -->

</main>


<?php
layout('footer'); ?>