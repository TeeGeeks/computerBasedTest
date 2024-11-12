<?php
include("config.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green</title>
  <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png" />
  <!-- Bootstrap CSS -->
  <link href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="<?php echo BASE_URL; ?>assets/css/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* Custom Styles */
    body {
      font-family: Arial, sans-serif;
    }

    .dropdown-submenu {
      position: relative;
    }

    .dropdown-submenu .dropdown-menu {
      top: 0;
      left: 100%;
      margin-top: -6px;
    }

    .nav-link {
      margin: 0 15px;
      /* Adds space between nav links */
      padding: 10px 15px;
      /* Adds padding for a more clickable area */
    }

    .dropdown-menu {
      margin: 0;
      /* Removes default margin */
    }

    .dropdown-item {
      padding: 10px 20px;
      /* Adds padding for dropdown items */
    }

    .dropdown-item:hover {
      background-color: #f8f9fa;
      /* Optional: change background color on hover */
    }
  </style>

</head>

<body>

  <header id="header" class="header sticky-top">

    <!-- Top Bar -->
    <div class="topbar d-flex align-items-center" style="background-color: #E63472; color: white; padding: 10px 0;">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-envelope me-2"></i><a href="mailto:contact@example.com" class="text-white text-decoration-none">contact@example.com</a>
          <i class="bi bi-phone ms-4 me-2"></i><span>+1 5589 55488 55</span>
        </div>
        <div class="social-links d-none d-md-flex align-items-center">
          <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
          <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
          <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
          <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
    </div>
    <!-- End Top Bar -->

    <!-- Branding and Navbar -->
    <div class="branding d-flex align-items-center" style="background-color: #f8f9fa; padding: 15px 0;">
      <div class="container d-flex justify-content-between align-items-center">
        <a href="#" class="logo d-flex align-items-center text-decoration-none">
          <h1 class="m-0" style="color: #e63472;">GREEN</h1>
        </a>

        <nav id="navmenu" class="navbar navbar-expand-lg">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
              <li class="nav-item">
                <a class="nav-link" href="#hero">Home</a>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  About
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <li><a class="dropdown-item" href="#about">About Us</a></li>
                  <li><a class="dropdown-item" href="#team">Our Team</a></li>
                </ul>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#portfolio">Portfolio</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#services">Services</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#contact">Contact Us</a>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="signinDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Sign In
                </a>
                <ul class="dropdown-menu" aria-labelledby="signinDropdown">
                  <li><a class="dropdown-item" href="admin/adminLogin.php">Admin</a></li>
                  <li><a class="dropdown-item" href="staff/staffLogin.php">Staff</a></li>
                  <li><a class="dropdown-item" href="student/studentLogin.php">Student</a></li>
                </ul>
              </li>
            </ul>
          </div>
        </nav>


      </div>
    </div>
    <!-- End Branding and Navbar -->
  </header>

  <!-- Add your hero section or content here -->
  <section id="hero" class="hero d-flex align-items-center" style="height: 60vh; background-size: cover;">
    <div class="container text-center text-light">
      <h2>Temporibus autem quibusdam</h2>
      <p>Beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut fugit.</p>
      <a href="#" class="btn btn-success">Get Started</a>
    </div>
  </section>

  <footer id="footer" class="footer bg-dark text-light py-4">
    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12">
          <a href="#" class="logo d-flex align-items-center text-white text-decoration-none">
            <h3 class="m-0">GREEN</h3>
          </a>
          <p class="mt-3">Cras fermentum odio eu feugiat lide par naso tierra. Justo eget nada terra videa magna derita valies darta donna mare fermentum iaculis eu non diam phasellus.</p>
          <div class="mt-4">
            <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
            <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
            <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
            <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-6">
          <h4>Useful Links</h4>
          <ul class="list-unstyled">
            <li><a href="#" class="text-light text-decoration-none">Home</a></li>
            <li><a href="#" class="text-light text-decoration-none">About us</a></li>
            <li><a href="#" class="text-light text-decoration-none">Services</a></li>
            <li><a href="#" class="text-light text-decoration-none">Terms of service</a></li>
            <li><a href="#" class="text-light text-decoration-none">Privacy policy</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-6">
          <h4>Our Services</h4>
          <ul class="list-unstyled">
            <li><a href="#" class="text-light text-decoration-none">Web Design</a></li>
            <li><a href="#" class="text-light text-decoration-none">Web Development</a></li>
            <li><a href="#" class="text-light text-decoration-none">Product Management</a></li>
            <li><a href="#" class="text-light text-decoration-none">Marketing</a></li>
            <li><a href="#" class="text-light text-decoration-none">Graphic Design</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12">
          <h4>Contact Us</h4>
          <p>A108 Adam Street</p>
          <p>New York, NY 535022</p>
          <p>United States</p>
          <p><strong>Phone:</strong> +1 5589 55488 55</p>
          <p><strong>Email:</strong> info@example.com</p>
        </div>
      </div>
    </div>

    <div class="container text-center mt-4">
      <p>&copy; <strong>GREEN</strong> All Rights Reserved</p>
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/" class="text-light text-decoration-none">BootstrapMade</a>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="<?php echo BASE_URL; ?>assets/js/core/popper.min.js"></script>
  <script src="<?php echo BASE_URL; ?>assets/js/core/bootstrap.min.js"></script>

</body>

</html>