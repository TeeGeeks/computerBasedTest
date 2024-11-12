<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$teacherId = $_SESSION['staff_id'] ?? null;
$isAdmin = $_SESSION['user_role'] === 'admin';

$subjects = [];
$classes = [];
$arms = [];
$assignedClasses = [];
$exams = [];

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
        $arms[] = $row;
    }
}

// Fetch assigned classes
$assignedClassesQuery = "SELECT class_id, arm_id FROM subject_assignments WHERE teacher_id = '$teacherId'";
$assignedClassesResult = mysqli_query($connection, $assignedClassesQuery);
if ($assignedClassesResult) {
    while ($row = mysqli_fetch_assoc($assignedClassesResult)) {
        $assignedClasses[] = $row;
    }
}

// Fetch the question details if 'id' is passed
$questionId = $_GET['id'] ?? null;
if ($questionId) {
    $questionQuery = "SELECT * FROM theory_questions WHERE id = '$questionId'";
    $questionResult = mysqli_query($connection, $questionQuery);
    $question = mysqli_fetch_assoc($questionResult);
} else {
    header('Location: addTheoryQuestion.php');
    exit;
}

// Check if the teacher is authorized to edit this question
if (!$isAdmin && $question['teacher_id'] !== $teacherId) {
    showSweetAlert('warning', 'Unauthorized', 'You are not authorized to edit this question.', 'addTheoryQuestion.php');
    exit;
}

function isAssignedClassArm($assignedClasses, $class_id, $arm_id)
{
    foreach ($assignedClasses as $assignment) {
        if ($assignment['class_id'] == $class_id && $assignment['arm_id'] == $arm_id) {
            return true;
        }
    }
    return false;
}


$selectedSubjectId = $question['subject_id'];
$examQuery = "SELECT id, exam_title FROM exams WHERE subject_id = '$selectedSubjectId' AND (teacher_id = '$teacherId' OR teacher_id IS NULL)";
$examResult = mysqli_query($connection, $examQuery);
$exams = [];
if ($examResult) {
    while ($row = mysqli_fetch_assoc($examResult)) {
        $exams[] = $row;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = trim($_POST['exam']);
    $subject_id = trim($_POST['subject']);
    $class_id = trim($_POST['class']);
    $arm_id = trim($_POST['arm']);
    $questionText = trim($_POST['questionText']);
    $file = $_FILES['file'] ?? null;

    // Sanitize inputs
    $examId = mysqli_real_escape_string($connection, $examId);
    $subject_id = mysqli_real_escape_string($connection, $subject_id);
    $class_id = mysqli_real_escape_string($connection, $class_id);
    $arm_id = mysqli_real_escape_string($connection, $arm_id);
    $questionText = mysqli_real_escape_string($connection, $questionText);

    // Validate fields
    if (empty($examId) || empty($subject_id) || empty($class_id) || empty($arm_id) || empty($questionText)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'editTheoryQuestion.php?id=' . $questionId);
        exit;
    }

    // Check authorization for non-admins
    if (!$isAdmin && !isAssignedClassArm($assignedClasses, $class_id, $arm_id)) {
        showSweetAlert('warning', 'Unauthorized', 'You are not assigned to this class and arm.', 'editTheoryQuestion.php?id=' . $questionId);
        exit;
    }

    // File upload handling
    $uploadDir = '../uploads/theory_questions/';
    $filePath = $question['file_path']; // Keep current file path if no new file is uploaded

    if ($file && $file['error'] === 0) {
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            showSweetAlert('error', 'Invalid File Type', 'Please upload a valid DOC or PDF file.', 'editTheoryQuestion.php?id=' . $questionId);
            exit;
        }

        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Define initial file path
        $filePath = $uploadDir . basename($file['name']);

        // Check if file already exists
        if (file_exists($filePath)) {
            // Option 1: Rename the new file by appending a unique ID or timestamp
            $filePath = $uploadDir . time() . "_" . basename($file['name']);
        }

        // Upload the file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            showSweetAlert('error', 'Upload Error', 'Failed to upload the file.', 'editTheoryQuestion.php?id=' . $questionId);
            exit;
        }
    }

    // Update the question record in the database
    $updateQuery = "UPDATE theory_questions 
                    SET subject_id = '$subject_id', theory_exam_id = '$examId', class_id = '$class_id', arm_id = '$arm_id', question_text = '$questionText', file_path = '$filePath'
                    WHERE id = '$questionId'";

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Theory question updated successfully.', 'manageTheoryQuestion.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editTheoryQuestion.php?id=' . $questionId);
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Question</title>
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
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>
        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4 text-primary">Edit Theory Question</h4>
                <form id="editTheoryQuestionForm" action="" method="POST" enctype="multipart/form-data">

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchExamss()">
                                <option value="" disabled>Select a subject</option>
                                <?php
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        $selected = ($subject['id'] == $question['subject_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '" ' . $selected . '>' . htmlspecialchars($subject['subject_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No subjects available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="exam" class="form-label">Exam</label>
                            <select class="form-select" id="exam" name="exam" required>
                                <option value="" disabled>Select an exam</option>
                                <?php
                                // Populate the exam options
                                if (!empty($exams)) {
                                    foreach ($exams as $exam) {
                                        $selected = ($exam['id'] == $question['exam_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($exam['id']) . '" ' . $selected . '>' . htmlspecialchars($exam['exam_title']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No exams available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled>Select a class</option>
                                <?php
                                if (!empty($classes)) {
                                    foreach ($classes as $class) {
                                        $selected = ($class['class_id'] == $question['class_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . $selected . '>' . htmlspecialchars($class['class_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No classes available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled>Select an arm</option>
                                <?php
                                if (!empty($arms)) {
                                    foreach ($arms as $arm) {
                                        $selected = ($arm['arm_id'] == $question['arm_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($arm['arm_id']) . '" ' . $selected . '>' . htmlspecialchars($arm['arm_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No arms available</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="questionText" class="form-label">Question Text</label>
                        <textarea class="form-control" id="questionText" name="questionText" rows="4" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">Upload File</label>
                        <input class="form-control" type="file" id="file" name="file">
                        <?php if (!empty($question['file_path'])): ?>
                            <p>Current File: <a href="<?php echo htmlspecialchars($question['file_path']); ?>" target="_blank">View File</a></p>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
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
    <script>
        function fetchExamss() {
            const subjectId = document.getElementById('subject').value;
            const examDropdown = document.getElementById('exam');

            if (!subjectId) return;

            // Clear the current exam options
            examDropdown.innerHTML = '<option value="" disabled selected>Loading...</option>';

            // Make an AJAX request to fetch exams based on selected subject
            fetch(`fetchTheory.php?subjectId=${subjectId}`)
                .then(response => {
                    if (!response.ok) {
                        console.error(`HTTP error! status: ${response.status}`);
                        throw new Error('Error fetching exams');
                    }
                    return response.json();
                })
                .then(data => {
                    // Clear the dropdown again
                    examDropdown.innerHTML = '<option value="" disabled selected>Select an exam</option>';

                    // Populate the dropdown with the fetched exams
                    if (data.length > 0) {
                        data.forEach(exam => {
                            const option = document.createElement('option');
                            option.value = exam.id;
                            option.textContent = exam.title;
                            examDropdown.appendChild(option);
                        });
                    } else {
                        examDropdown.innerHTML = '<option value="" disabled>No exams available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching exams:', error);
                    examDropdown.innerHTML = '<option value="" disabled>Error loading exams</option>';
                });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Theory Question';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>

    <script>
        function initializeTinyMCE(selector, height) {
            tinymce.init({
                selector: selector,
                height: height,
                plugins: 'advlist autolink lists link image media table charmap paste fullscreen code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link image media | table charmap | fullscreen code',
                paste_data_images: true, // Allows pasting images from clipboard
                images_upload_url: 'upload_handler.php', // Placeholder for image upload functionality
                automatic_uploads: true, // Enables automatic uploads of media
                file_picker_types: 'image media', // Allows image and media file picking
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save(); // Auto-save content when it's changed
                    });
                }
            });
        }
        initializeTinyMCE('#questionText', 240);
    </script>

</body>

</html>