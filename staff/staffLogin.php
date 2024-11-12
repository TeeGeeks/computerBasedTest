<?php
session_start(); // Start a session
include("../config.php"); // Include database configuration
include("../includes/generalFnc.php");

$conn = connection();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_or_username = $_POST['email_or_username'];
    $password = $_POST['password'];

    // Basic SQL query to find the user in the database
    $query = "SELECT * FROM teachers WHERE email='$email_or_username' OR username = '$email_or_username'";
    $result = $conn->query($query);

    // Check if the user exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if password matches
        if (password_verify($password, $row['password'])) {
            $_SESSION['staff_id'] = $row['teacher_id']; // Store user ID in session
            $_SESSION['staff_email'] = $row['email']; // Store user email in session
            $_SESSION['user_role'] = $row['role'];

            // Redirect to staff dashboard
            showSweetAlert('success', 'Login Successful', 'Welcome to your dashboard!', '../dashboard.php');
        } else {
            showSweetAlert('error', 'Login Failed', 'Invalid password. Please try again.');
        }
    } else {
        showSweetAlert('error', 'Login Failed', 'No account found with that email.');
    }
}
?>


<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png" />
    <title>Computer Based Test</title>
    <!-- Fonts and icons -->
    <!-- <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" /> -->
    <!-- Nucleo Icons -->
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <!-- <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script> -->
    <script src="<?php echo BASE_URL; ?>assets/js/core/fontawsomeicon.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/material-icons.css" />

    <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" /> -->
    <!-- CSS Files -->
    <link id="pagestyle" href="<?php echo BASE_URL; ?>assets/css/material-dashboard.css" rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/mycss.css">
    <!-- Place the first <script> tag in your HTML's <head> -->
    <script src="<?php echo BASE_URL; ?>assets/js/tinymce/tinymce/tinymce.min.js">
    </script>
    <!-- <script src="https://cdn.tiny.cloud/1/k014ifj5itll2amihajv2u2mwio7wbnpg1g0yg2fmr1f3m8u/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script> -->

    <!-- <link href="https://fonts.googleapis.com/css2?family=STIX+Two+Math&display=swap" rel="stylesheet"> -->
    <style>
        .math {
            font-family: 'STIX Two Math', serif;
            /* Or whatever your main text font is */
        }
    </style>

</head>

<body class="bg-gray-200">
    <main class="main-content mt-0">
        <div class="page-header min-vh-100" style="background-image: url('<?php echo BASE_URL; ?>assets/img/bg.jpg');">
            <span class="mask bg-gradient-dark opacity-6"></span>
            <div class="container my-auto">
                <div class="row">
                    <div class="col-lg-4 col-md-8 col-12 mx-auto">
                        <div class="card z-index-0 fadeIn3 fadeInBottom">
                            <div class="card-header p-0 mt-2 mx-3 z-index-2">
                                <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                                    <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Sign in</h4>
                                    <div class="row mt-3">
                                        <div class="col-2 text-center ms-auto">
                                            <a class="btn btn-link px-3" href="javascript:;">
                                                <i class="fa fa-facebook text-white text-lg"></i>
                                            </a>
                                        </div>
                                        <div class="col-2 text-center px-1">
                                            <a class="btn btn-link px-3" href="javascript:;">
                                                <i class="fa fa-github text-white text-lg"></i>
                                            </a>
                                        </div>
                                        <div class="col-2 text-center me-auto">
                                            <a class="btn btn-link px-3" href="javascript:;">
                                                <i class="fa fa-google text-white text-lg"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" role="form" class="text-start">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Email or Username</label>
                                        <input type="text" name="email_or_username" class="form-control" required />
                                    </div>

                                    <div class="input-group input-group-outline mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required />
                                    </div>
                                    <div class="form-check form-switch d-flex align-items-center mb-3">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" checked />
                                        <label class="form-check-label mb-0 ms-3" for="rememberMe">Remember me</label>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn bg-gradient-primary w-100 my-4 mb-2">Sign in</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include("../includes/script.php"); ?>
</body>

</html>