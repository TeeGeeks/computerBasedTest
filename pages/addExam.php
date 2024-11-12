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

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin'; // Check if the user is an admin

$insertTeacherId = $isAdmin ? 'NULL' : "'$teacherId'"; // Set teacher_id to NULL for admin

$subjectQuery = "SELECT id, subject_name FROM subjects";


$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row; // Store each subject in the array
    }
}

// Fetch all classes (same for both admin and teachers)
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

// Fetch all arms (make sure to adjust your query if necessary)
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row; // Store each arm in the array
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch form data
    $subject = trim($_POST['subject']);
    $class = trim($_POST['class']);
    $arm = trim($_POST['arm']); // Get the arm selection
    $examTitle = trim($_POST['examTitle']);
    $examDate = trim($_POST['examDate']);
    $examTime = trim($_POST['examTime']);
    $examLimit = trim($_POST['examLimit']);
    $deadlineDate = trim($_POST['deadlineDate']);
    $examDuration = trim($_POST['examDuration']);
    $markPerQuestion = trim($_POST['markPerQuestion']);
    $totalMarks = trim($_POST['totalMarks']);
    $passMark = trim($_POST['passMark']);
    $instructions = trim($_POST['instructions']);

    // Check if the showResults checkbox is set
    $showResults = isset($_POST['showResults']) ? 1 : 0; // Store 1 if checked, else 0

    // Validate required fields
    if (empty($subject) || empty($class) || empty($arm) || empty($examTitle) || empty($examDate) || empty($examTime) || empty($deadlineDate) || empty($examDuration)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'addExam.php');
        exit;
    }

    // Check for duplicate exams only for teachers, not for admins
    if (!$isAdmin) {
        $checkQuery = "SELECT * FROM exams 
                   WHERE subject_id = '$subject' 
                   AND class_id = '$class' 
                   AND arm_id = '$arm'
                   AND exam_title = '$examTitle' 
                   AND exam_date = '$examDate' 
                   AND teacher_id = '$teacherId'";
        $checkResult = mysqli_query($connection, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            showSweetAlert('error', 'Duplicate Exam', 'An exam with the same title, subject, class, arm, and date already exists.', 'addExam.php');
            exit;
        }
    }

    // Insert new exam, including the showResults option and arm_id
    $insertQuery = "INSERT INTO exams 
        (subject_id, class_id, arm_id, exam_title, exam_date, exam_time, exam_limit, exam_deadline, exam_duration, mark_per_question, total_marks, pass_mark, instructions, teacher_id, show_results)
        VALUES ('$subject', '$class', '$arm', '$examTitle', '$examDate', '$examTime', '$examLimit', '$deadlineDate', '$examDuration', '$markPerQuestion', '$totalMarks', '$passMark', '$instructions', $insertTeacherId, '$showResults')";

    if (mysqli_query($connection, $insertQuery)) {
        showSweetAlert('success', 'Success!', 'Exam added successfully.', 'manageExam.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addExam.php');
    }
}

mysqli_close($connection);
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Exam</title>
    <link rel="stylesheet" href="../path/to/bootstrap.css">
    <link rel="stylesheet" href="../path/to/custom.css">
</head>

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
                <h4 class="text-center mb-4 text-primary">Add Exam</h4>
                <form id="addExamForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                // Populate the class options with class ID
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
                        <div class="col-md-3 mb-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                // Populate the class options with class ID
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

                        <div class="col-md-3 mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <!-- HTML for Subject Dropdown -->
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled selected>Select a subject</option>
                                <?php
                                // Populate the subject options with subject ID
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
                        <div class="col-md-3 mb-3">
                            <label for="examTitle" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="examTitle" name="examTitle" required>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="examDate" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="examDate" name="examDate" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deadlineDate" class="form-label">Exam Deadline Date</label>
                            <input type="date" class="form-control border" id="deadlineDate" name="deadlineDate" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="examTime" class="form-label">Exam Time</label>
                            <input type="time" class="form-control" id="examTime" name="examTime" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="examLimit" class="form-label">Number of Questions</label>
                            <input type="number" class="form-control" id="examLimit" name="examLimit" min="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="examDuration" class="form-label">Exam Duration (minutes)</label>
                            <input type="number" class="form-control" id="examDuration" name="examDuration" min="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="markPerQuestion" class="form-label">Mark Per Question</label>
                            <input type="number" class="form-control" id="markPerQuestion" name="markPerQuestion" min="0.01" step="any">
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-md-5 mb-3 mt-4 ">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showResults" name="showResults" value="1">
                                <label class="form-check-label" for="showResults">
                                    Allow students to view results immediately.
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="totalMarks" class="form-label">Total Marks</label>
                            <input type="number" class="form-control" id="totalMarks" name="totalMarks" min="0.01" step="any">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="passMark" class="form-label">Pass Mark</label>
                            <input type="number" class="form-control" id="passMark" name="passMark" min="0.01" step="any">
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="instructions" class="form-label">Exam Instructions (Optional)</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Save
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
            submitBtn.disabled = true; // Disable the button to prevent multiple submissions
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Exam';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>