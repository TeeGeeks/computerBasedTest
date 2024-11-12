<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch class ID from the URL
$classId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables for the form
$className = '';

// Fetch the existing class data if the ID is valid
if ($classId > 0) {
    $query = "SELECT class_name FROM classes WHERE class_id = $classId";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        $classData = mysqli_fetch_assoc($result);
        $className = $classData['class_name'];
    } else {
        showSweetAlert('error', 'Error!', 'Class not found.', 'manageClass.php'); // Redirect to manage class page
        exit;
    }
}

// Handle form submission for updating the class
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = trim($_POST['className']);

    // Check if the class name already exists
    $checkQuery = "SELECT * FROM classes WHERE class_name = '$className' AND class_id != $classId";
    $result = mysqli_query($connection, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        // Class already exists
        showSweetAlert('error', 'Oops...', 'Class name already exists. Please enter a different name.', 'editClass.php?id=' . $classId);
    } else {
        // Update the class in the database
        $updateQuery = "UPDATE classes SET class_name = '$className' WHERE class_id = $classId";

        if (mysqli_query($connection, $updateQuery)) {
            // Class updated successfully
            showSweetAlert('success', 'Success!', 'Class updated successfully.', 'manageClass.php'); // Redirect to manage class page
        } else {
            // Error occurred
            showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editClass.php?id=' . $classId); // Redirect to edit page
        }
    }

    mysqli_close($connection);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class</title>
    <link rel="stylesheet" type="text/css" href="css/mycss.css">
</head>

<style>
    .form-control {
        border: 1px solid #ced4da;
        /* Border color */
        border-radius: 0.375rem;
        /* Border radius */
        padding: 0.375rem 0.75rem;
        /* Padding */
    }

    .form-control:focus {
        border-color: #80bdff;
        /* Focus border color */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        /* Focus shadow */
    }

    .d-grid .btn {
        border-radius: 0.375rem;
        /* Button corners rounded */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-8 col-lg-5 col-12">
                <h4 class="text-center text-primary mb-4">Edit Class</h4>
                <form id="editClassForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="mb-3">
                        <label for="className" class="form-label">Class Name</label>
                        <input
                            type="text"
                            class="form-control border"
                            id="className"
                            name="className"
                            value="<?php echo htmlspecialchars($className); ?>"
                            required>
                    </div>
                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i>Update
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <footer style="margin-top: 200px;">
            <?php include("../includes/footer.php"); ?>
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
            submitBtn.disabled = true; // Disable the button to prevent multiple submissions
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Class';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>