<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');


$connection = connection();
// Initialize variables
$armName = '';
$armError = '';

// Add new arm if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $armName = trim($_POST['armName']);

    // Escape input to prevent SQL injection
    $armName = mysqli_real_escape_string($connection, $armName);

    // Check if the arm already exists
    $checkQuery = "SELECT * FROM arms WHERE arm_name = '$armName'";
    $result = mysqli_query($connection, $checkQuery);

    // Validate required fields
    if (empty($armName)) {
        $armError = "Arm name is required.";
    } elseif (mysqli_num_rows($result) > 0) {
        showSweetAlert('error', 'Oops...', 'Arm already exists. Please choose a different name.', 'addArm.php');
    } else {
        // Insert query for the new arm
        $insertQuery = "INSERT INTO arms (arm_name) VALUES ('$armName')";

        if (mysqli_query($connection, $insertQuery)) {
            showSweetAlert('success', 'Success!', 'Arm added successfully.', 'manageArm.php');
            exit;
        } else {
            $armError = "Error: " . mysqli_error($connection);
        }
    }
}

// Close the database connection
mysqli_close($connection);
?>

<style>
    .form-control {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .d-grid .btn {
        border-radius: 0.375rem;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-8 col-lg-5 col-12">
                <h4 class="text-center mb-4">Add New Arm</h4>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="armName" class="form-label">Arm Name</label>
                        <input type="text" class="form-control" id="armName" name="armName" value="<?php echo htmlspecialchars($armName); ?>" required>
                        <?php if ($armError): ?>
                            <div class="text-danger"><?php echo $armError; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Add Arm
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
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Arm';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>