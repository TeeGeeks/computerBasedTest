<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

$teacherId = $_SESSION['staff_id'] ?? ''; // Get teacher ID from session
$subjects = [];
$classes = [];
$arms = [];
$assignedClasses = []; // Initialize assignedClasses to avoid the warning

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin'; // Check if the user is an admin

$insertTeacherId = $isAdmin ? 'NULL' : "'$teacherId'"; // Set teacher_id to NULL for admin

// Fetch all subjects
$subjectQuery = "SELECT id, subject_name FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row; // Store each subject in the array
    }
}

// Fetch all classes
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

// Fetch all arms
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row; // Store each arm in the array
    }
}

// Fetch assigned classes and arms for the teacher if they are not an admin
if (!$isAdmin) {
    $assignedClassesQuery = "SELECT class_id, arm_id FROM subject_assignments WHERE teacher_id = '$teacherId'";
    $assignedClassesResult = mysqli_query($connection, $assignedClassesQuery);
    if ($assignedClassesResult) {
        while ($row = mysqli_fetch_assoc($assignedClassesResult)) {
            $assignedClasses[] = $row; // Populate assignedClasses array
        }
    }
}

// Function to check authorization for class and arm assignment
function isAssignedClassArm($assignedClasses, $class_id, $arm_id)
{
    foreach ($assignedClasses as $assignment) {
        if ($assignment['class_id'] == $class_id && $assignment['arm_id'] == $arm_id) {
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize form inputs
    $subject = mysqli_real_escape_string($connection, trim($_POST['subject']));
    $class_id = mysqli_real_escape_string($connection, trim($_POST['class']));
    $arm_id = mysqli_real_escape_string($connection, trim($_POST['arm']));
    $examTitle = mysqli_real_escape_string($connection, trim($_POST['examTitle']));
    $examDate = mysqli_real_escape_string($connection, trim($_POST['examDate']));
    $examTime = mysqli_real_escape_string($connection, trim($_POST['examTime']));
    $deadlineDate = mysqli_real_escape_string($connection, trim($_POST['deadlineDate']));
    $examDuration = mysqli_real_escape_string($connection, trim($_POST['examDuration']));
    $instructions = mysqli_real_escape_string($connection, trim($_POST['instructions']));
    $insertTeacherId = $isAdmin ? 'NULL' : "'$teacherId'";

    // Validate required fields
    if (empty($subject) || empty($class_id) || empty($arm_id) || empty($examTitle) || empty($examDate) || empty($examTime) || empty($deadlineDate) || empty($examDuration)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'addTheory.php');
        exit;
    }

    // Check if the teacher is assigned to this class and arm if they are not an admin
    if (!$isAdmin && !isAssignedClassArm($assignedClasses, $class_id, $arm_id)) {
        // Use the showSweetAlert function from the general functions file
        showSweetAlert1('warning', 'Unauthorized', 'You are not assigned to this class and arm.', 'addTheory.php');

        // Stop script execution
        exit();
    }


    // Check for duplicate exam entry
    if (!$isAdmin) {
        $checkQuery = "SELECT * FROM theory_exams 
                       WHERE subject_id = '$subject' 
                       AND class_id = '$class_id' 
                       AND arm_id = '$arm_id'
                       AND exam_title = '$examTitle' 
                       AND exam_date = '$examDate' 
                       AND teacher_id = '$teacherId'";
        $checkResult = mysqli_query($connection, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            showSweetAlert('error', 'Duplicate Exam', 'An exam with the same title, subject, class, arm, and date already exists.', 'addTheory.php');
            exit;
        }
    }

    // Insert the new theory exam
    $insertQuery = "INSERT INTO theory_exams 
        (subject_id, class_id, arm_id, exam_title, exam_date, exam_time, exam_deadline, exam_duration, instructions, teacher_id)
        VALUES ('$subject', '$class_id', '$arm_id', '$examTitle', '$examDate', '$examTime', '$deadlineDate', '$examDuration', '$instructions', $insertTeacherId)";

    if (mysqli_query($connection, $insertQuery)) {
        showSweetAlert('success', 'Success!', 'Theory exam added successfully.', 'manageTheory.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addTheory.php');
    }
}
mysqli_close($connection);
?>



<style>
    .form-control,
    .form-select {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4 text-primary">Add Theory Exam</h4>
                <form id="addTheoryExamForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                if (!empty($classes)) {
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class['class_id']) . '">' . htmlspecialchars($class['class_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No classes available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select an arm</option>
                                <?php
                                if (!empty($arms)) {
                                    foreach ($arms as $arm) {
                                        echo '<option value="' . htmlspecialchars($arm['arm_id']) . '">' . htmlspecialchars($arm['arm_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No arms available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled selected>Select a subject</option>
                                <?php
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '">' . htmlspecialchars($subject['subject_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No subjects available</option>';
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="examTitle" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="examTitle" name="examTitle" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="examDate" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="examDate" name="examDate" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deadlineDate" class="form-label">Exam Deadline Date</label>
                            <input type="date" class="form-control" id="deadlineDate" name="deadlineDate" required>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="examTime" class="form-label">Exam Time</label>
                            <input type="time" class="form-control" id="examTime" name="examTime" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="examDuration" class="form-label">Exam Duration (minutes)</label>
                            <input type="number" class="form-control" id="examDuration" name="examDuration" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="instructions" class="form-label">Instructions</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="3"></textarea>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
            const currentPage = 'Add Theory';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>