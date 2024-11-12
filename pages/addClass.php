<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = trim($_POST['className']);

    // Check if the class already exists
    $checkQuery = "SELECT * FROM classes WHERE class_name = '$className'";
    $result = mysqli_query($connection, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        // Class already exists
        showSweetAlert('error', 'Oops...', 'Class already exists. Please choose a different name.', 'addClass.php');
    } else {
        // Insert query to add class to the database
        $query = "INSERT INTO classes (class_name) VALUES ('$className')";

        if (mysqli_query($connection, $query)) {
            // Class added successfully
            showSweetAlert('success', 'Success!', 'Class added successfully.', 'manageClass.php');
        } else {
            // Error occurred
            showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addClass.php');
        }
    }

    mysqli_close($connection);
}
?>

<style>
    .form-control {
        border: 1px solid #ced4da;
        /* Adjust border color */
        border-radius: 0.375rem;
        /* Adjust border radius */
        padding: 0.375rem 0.75rem;
        /* Adjust padding */
    }

    .form-control:focus {
        border-color: #80bdff;
        /* Adjust focus border color */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        /* Add focus shadow */
    }

    .d-grid .btn {
        border-radius: 0.375rem;
        /* Ensure button corners are rounded */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-5">
            <div class="col-md-8 col-lg-5 col-12">
                <h4 class="text-center text-primary mb-4">Add New Class</h4>
                <form id="addClassForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="mb-3">
                        <label for="className" class="form-label">Class Name</label>
                        <input type="text" class="form-control border" id="className" name="className" placeholder="Enter class name" required>
                    </div>
                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i>Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer style="margin-top: 200px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        function showSpinner() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            `;
            submitBtn.disabled = true;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Class';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>