<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

$teacherId = $_SESSION['staff_id'] ?? '';
$isAdmin = $_SESSION['user_role'] === 'admin';
$insertTeacherId = $isAdmin ? 'NULL' : "'$teacherId'";
$examId = $_GET['id'] ?? ''; // Get exam ID for editing

$subjects = [];
$classes = [];
$arms = [];
$examData = [];

// Fetch exam data if $examId is provided
if ($examId) {
    $examQuery = "SELECT * FROM theory_exams WHERE id = '$examId' LIMIT 1";
    $examResult = mysqli_query($connection, $examQuery);
    if ($examResult && mysqli_num_rows($examResult) > 0) {
        $examData = mysqli_fetch_assoc($examResult);
    }
}

// Fetch subjects, classes, and arms (same as before)
$subjectQuery = "SELECT id, subject_name FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
while ($row = mysqli_fetch_assoc($subjectResult)) {
    $subjects[] = $row;
}

$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
while ($row = mysqli_fetch_assoc($classResult)) {
    $classes[] = $row;
}

$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
while ($row = mysqli_fetch_assoc($armResult)) {
    $arms[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch form data for editing
    $subject = trim($_POST['subject']);
    $class = trim($_POST['class']);
    $arm = trim($_POST['arm']);
    $examTitle = trim($_POST['examTitle']);
    $examDate = trim($_POST['examDate']);
    $examTime = trim($_POST['examTime']);
    $deadlineDate = trim($_POST['deadlineDate']);
    $examDuration = trim($_POST['examDuration']);
    $instructions = trim($_POST['instructions']);

    if (empty($subject) || empty($class) || empty($arm) || empty($examTitle) || empty($examDate) || empty($examTime) || empty($deadlineDate) || empty($examDuration)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'editTheory.php?id=' . $examId);
        exit;
    }

    $updateQuery = "UPDATE theory_exams 
                    SET subject_id = '$subject', 
                        class_id = '$class', 
                        arm_id = '$arm', 
                        exam_title = '$examTitle', 
                        exam_date = '$examDate', 
                        exam_time = '$examTime', 
                        exam_deadline = '$deadlineDate', 
                        exam_duration = '$examDuration', 
                        instructions = '$instructions', 
                        teacher_id = $insertTeacherId 
                    WHERE id = '$examId'";

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Theory exam updated successfully.', 'manageTheory.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editTheory.php?id=' . $examId);
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
                <h4 class="text-center mb-4 text-primary">Edit Theory Exam</h4>
                <form id="editTheoryExamForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled>Select a class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= htmlspecialchars($class['class_id']) ?>" <?= $class['class_id'] == $examData['class_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled>Select an arm</option>
                                <?php foreach ($arms as $arm): ?>
                                    <option value="<?= htmlspecialchars($arm['arm_id']) ?>" <?= $arm['arm_id'] == $examData['arm_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($arm['arm_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled>Select a subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= htmlspecialchars($subject['id']) ?>" <?= $subject['id'] == $examData['subject_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="examTitle" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="examTitle" name="examTitle" value="<?= htmlspecialchars($examData['exam_title']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="examDate" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="examDate" name="examDate" value="<?= htmlspecialchars($examData['exam_date']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="deadlineDate" class="form-label">Exam Deadline Date</label>
                            <input type="date" class="form-control" id="deadlineDate" name="deadlineDate" value="<?= htmlspecialchars($examData['exam_deadline']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="examTime" class="form-label">Exam Time</label>
                            <input type="time" class="form-control" id="examTime" name="examTime" value="<?= htmlspecialchars($examData['exam_time']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="examDuration" class="form-label">Exam Duration (minutes)</label>
                            <input type="number" class="form-control" id="examDuration" name="examDuration" min="1" value="<?= htmlspecialchars($examData['exam_duration']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="instructions" class="form-label">Instructions</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="3"><?= htmlspecialchars($examData['instructions']) ?></textarea>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Update
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
            submitBtn.disabled = true;
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Theory';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>