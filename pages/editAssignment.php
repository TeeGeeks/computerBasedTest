<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get the assignment ID from the URL
$assignmentId = $_GET['id'];

// Fetch the assignment details
$assignmentQuery = "SELECT * FROM subject_assignments WHERE id = '$assignmentId'";
$assignmentResult = mysqli_query($connection, $assignmentQuery);
$assignment = mysqli_fetch_assoc($assignmentResult);

// Fetch all classes, teachers, and subjects from the database
$classQuery = "SELECT * FROM classes";
$classResult = mysqli_query($connection, $classQuery);

$teacherQuery = "SELECT * FROM teachers";
$teacherResult = mysqli_query($connection, $teacherQuery);

$subjectQuery = "SELECT * FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);

// Fetch all arms from the database
$armQuery = "SELECT * FROM arms";
$armResult = mysqli_query($connection, $armQuery);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = trim($_POST['classId']);
    $armId = trim($_POST['armId']);
    $teacherId = trim($_POST['teacherId']);
    $subjectId = trim($_POST['subjectId']);

    // Update the existing assignment
    $updateQuery = "UPDATE subject_assignments SET class_id = '$classId', arm_id = '$armId', teacher_id = '$teacherId', subject_id = '$subjectId' WHERE id = '$assignmentId'";

    if (mysqli_query($connection, $updateQuery)) {
        // Assignment updated successfully
        showSweetAlert('success', 'Success!', 'Subject assignment updated successfully.', 'manageAssignment.php');
    } else {
        // Error occurred
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editAssignment.php?id=' . $assignmentId);
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
            <div class="col-md-8 col-lg-6 col-12">
                <h4 class="text-center text-primary mb-4">Edit Subject Assignment</h4>
                <form id="editAssignmentForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="mb-3">
                        <label for="classId" class="form-label">Class</label>
                        <select name="classId" id="classId" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php while ($class = mysqli_fetch_assoc($classResult)) { ?>
                                <option value="<?= $class['class_id'] ?>" <?= $class['class_id'] == $assignment['class_id'] ? 'selected' : '' ?>>
                                    <?= $class['class_name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="armId" class="form-label">Arm</label>
                        <select name="armId" id="armId" class="form-control" required>
                            <option value="">Select Arm</option>
                            <?php while ($arm = mysqli_fetch_assoc($armResult)) { ?>
                                <option value="<?= $arm['arm_id'] ?>" <?= ($assignment['arm_id'] == $arm['arm_id']) ? 'selected' : '' ?>><?= $arm['arm_name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="teacherId" class="form-label">Teacher</label>
                        <select name="teacherId" id="teacherId" class="form-control" required>
                            <option value="">Select Teacher</option>
                            <?php while ($teacher = mysqli_fetch_assoc($teacherResult)) { ?>
                                <option value="<?= $teacher['teacher_id'] ?>" <?= $teacher['teacher_id'] == $assignment['teacher_id'] ? 'selected' : '' ?>>
                                    <?= $teacher['surname'] ?> <?= $teacher['other_names'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subjectId" class="form-label">Subject</label>
                        <select name="subjectId" id="subjectId" class="form-control" required>
                            <option value="">Select Subject</option>
                            <?php while ($subject = mysqli_fetch_assoc($subjectResult)) { ?>
                                <option value="<?= $subject['id'] ?>" <?= $subject['id'] == $assignment['subject_id'] ? 'selected' : '' ?>>
                                    <?= $subject['subject_name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Save Changes
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
                Saving...
            `;
            submitBtn.disabled = true;
        }
    </script>
</body>

</html>