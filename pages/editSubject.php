<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get subject details by ID
$subject_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($subject_id) {
    $query = "SELECT * FROM subjects WHERE id = '$subject_id'";
    $result = mysqli_query($connection, $query);
    $subject = mysqli_fetch_assoc($result);

    if (!$subject) {
        // Subject not found, redirect back to manage page
        showSweetAlert('error', 'Oops...', 'Subject not found.', 'manageSubject.php');
        exit;
    }
} else {
    // ID is missing, redirect to manage page
    showSweetAlert('error', 'Oops...', 'Invalid subject ID.', 'manageSubject.php');
    exit;
}

// Update subject details if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectName = trim($_POST['subjectName']);

    // Update query for subject
    $updateQuery = "UPDATE subjects SET subject_name='$subjectName' WHERE id='$subject_id'";

    if (mysqli_query($connection, $updateQuery)) {
        // Subject updated successfully
        showSweetAlert('success', 'Success!', 'Subject updated successfully.', 'manageSubject.php');
    } else {
        // Error occurred
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editSubject.php?id=' . $subject_id);
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
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

        <div class="container py-4 d-flex justify-content-center mt-5">
            <div class="col-md-8 col-lg-5 col-12">
                <h4 class="text-center text-primary mb-4">Edit Subject</h4>
                <form id="editSubjectForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="mb-3">
                        <label for="subjectName" class="form-label">Subject Name</label>
                        <input
                            type="text"
                            class="form-control border"
                            id="subjectName"
                            name="subjectName"
                            value="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                            required>
                    </div>
                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Update Subject
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
            const currentPage = 'Edit Subject';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>