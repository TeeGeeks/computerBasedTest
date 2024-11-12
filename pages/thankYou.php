<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get the attempt ID from the URL (if needed for tracking)
$attempt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

?>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container mt-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-light text-white text-center">
                    <h2>Thank You!</h2>
                </div>
                <div class="card-body">
                    <h3 class="text-center text-success mb-4">A Message from Your Teacher</h3>
                    <div class="text-center mb-4">
                        <p>Thank you for submitting your exam. Your effort and dedication are greatly appreciated!</p>
                        <p>Please check back later to see if your results are available.</p>
                    </div>
                    <div class="text-center mt-4">
                        <a href="../dashboard.php" class="btn btn-primary btn-lg">Go Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>

        <?php include("../includes/footer.php"); ?>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Submission Confirmation'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>