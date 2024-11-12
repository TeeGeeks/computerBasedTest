<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get arm ID from query parameters and validate it
$armId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$armName = '';
$armError = '';

// Fetch the arm details if ID is provided
if ($armId > 0) {
    // Change `$armQuery` to use the correct column name
    $armQuery = "SELECT * FROM arms WHERE arm_id = '$armId'";
    $armResult = mysqli_query($connection, $armQuery);

    if (mysqli_num_rows($armResult) == 1) {
        $arm = mysqli_fetch_assoc($armResult);
        $armName = $arm['arm_name'];
    } else {
        showSweetAlert('error', 'Oops...', 'Arm not found.', 'manageArm.php');
        exit;
    }
    $updateQuery = "UPDATE arms SET arm_name = '$armName' WHERE arm_id = '$armId'";
}

// Update the arm if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $armName = trim($_POST['armName']);
    $armName = mysqli_real_escape_string($connection, $armName);

    // Check if the arm name already exists
    $checkQuery = "SELECT * FROM arms WHERE arm_name = '$armName' AND arm_id != '$armId'";
    $result = mysqli_query($connection, $checkQuery);

    // Validate required fields
    if (empty($armName)) {
        $armError = "Arm name is required.";
    } elseif (mysqli_num_rows($result) > 0) {
        showSweetAlert('error', 'Oops...', 'Arm name already exists. Please choose a different name.', 'editArm.php?id=' . $armId);
    } else {
        // Update query for the arm
        $updateQuery = "UPDATE arms SET arm_name = '$armName' WHERE id = '$armId'";

        if (mysqli_query($connection, $updateQuery)) {
            showSweetAlert('success', 'Success!', 'Arm updated successfully.', 'manageArm.php');
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
                <h4 class="text-center mb-4">Edit Arm</h4>
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
                            <i class="fa fa-save me-2"></i> Update Arm
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
            const currentPage = 'Edit Arm';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>