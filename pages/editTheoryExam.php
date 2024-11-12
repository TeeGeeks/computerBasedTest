<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

$examId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacherId = $_SESSION['staff_id'] ?? '';
$role = $_SESSION['user_role'];
$subjects = [];
$classes = [];
$arms = [];

// Fetch subjects
$subjectQuery = "SELECT id, subject_name FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row;
    }
}

// Fetch classes
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

// Fetch arms
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row; // Store each arm in the array
    }
}

// Fetch existing exam details
$examQuery = "SELECT * FROM theory_exam WHERE id = '$examId'";
$examResult = mysqli_query($connection, $examQuery);
$examDetails = mysqli_fetch_assoc($examResult);

if (!$examDetails) {
    showSweetAlert('error', 'Exam Not Found', 'The specified exam could not be found.', 'manageExams.php');
    exit;
}

// Check if user is allowed to edit this exam
if ($role !== 'admin' && $examDetails['teacher_id'] !== $teacherId) {
    showSweetAlert('error', 'Unauthorized Access', 'You are not authorized to edit this exam.', 'manageExams.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data to prevent SQL injection
    $subject = mysqli_real_escape_string($connection, trim($_POST['subject']));
    $class = mysqli_real_escape_string($connection, trim($_POST['class']));
    $arm = mysqli_real_escape_string($connection, trim($_POST['arm']));
    $examTitle = mysqli_real_escape_string($connection, trim($_POST['examTitle']));
    $examDate = mysqli_real_escape_string($connection, trim($_POST['examDate']));
    $examTime = mysqli_real_escape_string($connection, trim($_POST['examTime']));
    $examDeadline = mysqli_real_escape_string($connection, trim($_POST['examDeadline']));
    $examDuration = mysqli_real_escape_string($connection, trim($_POST['examDuration']));
    $instructions = mysqli_real_escape_string($connection, trim($_POST['instructions']));

    // Validate required fields
    if (empty($subject) || empty($class) || empty($arm) || empty($examTitle) || empty($examDate) || empty($examTime) || empty($examDeadline) || empty($examDuration)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'editExam.php?id=' . $examId);
        exit;
    }

    // Check for duplicate exams
    $checkQuery = "SELECT * FROM theory_exam 
                   WHERE subject_id = '$subject' 
                   AND class_id = '$class' 
                   AND arm_id = '$arm' 
                   AND exam_title = '$examTitle' 
                   AND exam_date = '$examDate' 
                   AND id != '$examId'";
    $checkResult = mysqli_query($connection, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        showSweetAlert('error', 'Duplicate Exam', 'An exam with the same title, subject, class, arm, and date already exists.', 'editExam.php?id=' . $examId);
        exit;
    }

    // Determine the correct `teacher_id`:
    // - If admin is editing, keep the original teacher_id.
    // - If the admin creates or edits their own exam, set teacher_id to NULL.
    if ($role === 'admin') {
        if ($examDetails['teacher_id'] != null) {
            // Retain teacher_id if it's not null (i.e., a teacher's exam)
            $teacherId = $examDetails['teacher_id'];
        } else {
            // Set teacher_id to NULL if it's an admin's own exam
            $teacherId = 'NULL';
        }
    } else {
        // Teachers retain their own teacher_id
        $teacherId = $_SESSION['staff_id'];
    }

    // Update query with the appropriate `teacher_id`
    $updateQuery = "UPDATE theory_exam SET 
                    subject_id = '$subject', 
                    class_id = '$class', 
                    arm_id = '$arm', 
                    exam_title = '$examTitle', 
                    exam_date = '$examDate', 
                    exam_time = '$examTime', 
                    exam_deadline = '$examDeadline', 
                    exam_duration = '$examDuration', 
                    instructions = '$instructions',
                    teacher_id = " . ($teacherId === 'NULL' ? 'NULL' : "'$teacherId'") . "
                WHERE id = '$examId'";

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Exam updated successfully.', 'manageExams.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editExam.php?id=' . $examId);
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

    .d-grid .btn {
        border-radius: 0.375rem;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4 text-primary">Edit Exam</h4>
                <form id="editExamForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled>Select a class</option>
                                <?php foreach ($classes as $class) { ?>
                                    <option value="<?= $class['class_id'] ?>" <?= $examDetails['class_id'] == $class['class_id'] ? 'selected' : '' ?>>
                                        <?= $class['class_name'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled>Select a arm</option>
                                <?php foreach ($arms as $arm) { ?>
                                    <option value="<?= $arm['arm_id'] ?>" <?= $examDetails['arm_id'] == $arm['arm_id'] ? 'selected' : '' ?>>
                                        <?= $arm['arm_name'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled>Select a subject</option>
                                <?php
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        $selected = ($subject['id'] == $examDetails['subject_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '" ' . $selected . '>' . htmlspecialchars($subject['subject_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="examTitle" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="examTitle" name="examTitle" value="<?= htmlspecialchars($examDetails['exam_title']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="examDate" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="examDate" name="examDate" value="<?= $examDetails['exam_date'] ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="examTime" class="form-label">Exam Time</label>
                            <input type="time" class="form-control" id="examTime" name="examTime" value="<?= $examDetails['exam_time'] ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="examDuration" class="form-label">Duration (hrs)</label>
                            <input type="number" class="form-control" id="examDuration" name="examDuration" value="<?= $examDetails['exam_duration'] ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="instructions" class="form-label">Instructions</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="3"><?= htmlspecialchars($examDetails['instructions']) ?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
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
            const currentPage = 'Edit Exam';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>