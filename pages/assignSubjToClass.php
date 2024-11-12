<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch all classes from the database
$classQuery = "SELECT * FROM classes";
$classResult = mysqli_query($connection, $classQuery);

// Fetch all subjects from the database
$subjectQuery = "SELECT * FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);

// Fetch all arms from the database
$armQuery = "SELECT * FROM arms";
$armResult = mysqli_query($connection, $armQuery);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = trim($_POST['classId']);
    $armId = trim($_POST['armId']);
    $subjectId = trim($_POST['subjectId']);

    // Check if the assignment already exists
    $checkQuery = "SELECT * FROM subject_assignments WHERE class_id = '$classId' AND arm_id = '$armId' AND subject_id = '$subjectId'";
    $result = mysqli_query($connection, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        // Assignment already exists
        showSweetAlert('error', 'Oops...', 'This subject has already been assigned to this class and arm.', 'assignSubjToClass.php');
    } else {
        // Insert the new assignment
        $query = "INSERT INTO subject_assignments (class_id, arm_id, subject_id) VALUES ('$classId', '$armId', '$subjectId')";

        if (mysqli_query($connection, $query)) {
            // Assignment added successfully
            showSweetAlert('success', 'Success!', 'Subject assigned successfully.', 'manageAssignment.php');
        } else {
            // Error occurred
            showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'assignSubjToClass.php');
        }
    }

    mysqli_close($connection);
}
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
            <div class="col-md-8 col-lg-5 col-sm-9">
                <h4 class="text-center mb-4">Assign Subject to Class</h4>
                <form id="assignSubjectForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="mb-3">
                        <label for="classId" class="form-label">Class</label>
                        <select name="classId" id="classId" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php while ($class = mysqli_fetch_assoc($classResult)) { ?>
                                <option value="<?= $class['class_id'] ?>"><?= $class['class_name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="armId" class="form-label">Arm</label>
                        <select name="armId" id="armId" class="form-control" required>
                            <option value="">Select Arm</option>
                            <?php while ($arm = mysqli_fetch_assoc($armResult)) { ?>
                                <option value="<?= $arm['arm_id'] ?>"><?= $arm['arm_name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subjectId" class="form-label">Subject</label>
                        <select name="subjectId" id="subjectId" class="form-control" required>
                            <option value="">Select Subject</option>
                            <?php while ($subject = mysqli_fetch_assoc($subjectResult)) { ?>
                                <option value="<?= $subject['id'] ?>"><?= $subject['subject_name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer style="margin-top: 80px;">
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
</body>