<?php
session_start(); // Start session
include("./config.php");

// Check if user is logged in as admin, staff, or student
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['student_id'])) {
    header('Location: index.php'); // Redirect to login page if not logged in
    exit();
}

include("./includes/header.php");
?>


<body class="g-sidenav-show bg-gray-200">
    <?php
    include("./includes/sidebar.php");
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("./includes/headernav.php") ?>
        <div class="container-fluid py-4">
            <?php include("./includes/dashboardmain.php") ?>
            <?php include("./includes/footer.php") ?>
        </div>
    </main>
    <?php include("./includes/script.php") ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = '<?php echo basename($_SERVER["SCRIPT_NAME"], ".php"); ?>';
            updateBreadcrumbs(['Pages', currentPage.charAt(0).toUpperCase() + currentPage.slice(1)]);
        });
    </script>
</body>

</html>