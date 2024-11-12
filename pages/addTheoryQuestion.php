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

$subjectQuery = "SELECT id, subject_name FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row;
    }
}

$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row;
    }
}

$examQuery = "SELECT id, exam_title, arm_id, class_id FROM theory_exams WHERE teacher_id = '$teacherId'";
$examResult = mysqli_query($connection, $examQuery);
$exams = [];
if ($examResult) {
    while ($row = mysqli_fetch_assoc($examResult)) {
        $exams[] = $row;
    }
}

$assignedClassesQuery = "SELECT class_id, arm_id FROM subject_assignments WHERE teacher_id = '$teacherId'";
$assignedClassesResult = mysqli_query($connection, $assignedClassesQuery);
if ($assignedClassesResult) {
    while ($row = mysqli_fetch_assoc($assignedClassesResult)) {
        $assignedClasses[] = $row;
    }
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = trim($_POST['exam']);
    $subject_id = trim($_POST['subject']);
    $class_id = trim($_POST['class']);
    $arm_id = trim($_POST['arm']);
    $questionText = trim($_POST['questionText']);

    $examId = mysqli_real_escape_string($connection, $examId);
    $subject_id = mysqli_real_escape_string($connection, $subject_id);
    $class_id = mysqli_real_escape_string($connection, $class_id);
    $arm_id = mysqli_real_escape_string($connection, $arm_id);
    $questionText = mysqli_real_escape_string($connection, $questionText);

    if (empty($examId) || empty($subject_id) || empty($class_id) || empty($arm_id) || empty($questionText)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'addTheoryQuestion.php');
        exit;
    }

    if (!$isAdmin && !isAssignedClassArm($assignedClasses, $class_id, $arm_id)) {
        showSweetAlert('warning', 'Unauthorized', 'You are not assigned to this class and arm.', 'addTheoryQuestion.php');
        exit;
    }

    $insertQuery = "INSERT INTO theory_questions (subject_id, theory_exam_id, class_id, arm_id, question_text, teacher_id) 
                    VALUES ('$subject_id', '$examId', '$class_id', '$arm_id', '$questionText', " . ($isAdmin ? "NULL" : "'$teacherId'") . ")";
    if (mysqli_query($connection, $insertQuery)) {
        showSweetAlert('success', 'Success!', 'Theory question added successfully.', 'addTheoryQuestion.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addTheoryQuestion.php');
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
                <h4 class="text-center mb-4 text-primary">Add Theory Question</h4>
                <form id="addTheoryQuestionForm" action="" method="POST">

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchExamss()">
                                <option value="" disabled selected>Select a subject</option>
                                <?php
                                // Populate the subject options
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

                        <div class="col-md-3">
                            <label for="exam" class="form-label">Exam</label>
                            <select class="form-select" id="exam" name="exam" required>
                                <option value="" disabled selected>Select an exam</option>
                                <?php
                                // Populate the exam options
                                if (!empty($exams)) {
                                    foreach ($exams as $exam) {
                                        echo '<option value="' . htmlspecialchars($exam['id']) . '">' . htmlspecialchars($exam['exam_title']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No exams available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="class" class="form-label">Class</label> <!-- New Class Field -->
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                // Populate the class options
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
                        <div class="col-md-3">
                            <label for="arm" class="form-label">Arm</label> <!-- New Class Field -->
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select a arm</option>
                                <?php
                                // Populate the class options
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
                    </div>


                    <div class="mb-3">
                        <label for="questionText" class="form-label">Question Text</label>
                        <textarea class="form-control" id="questionText" name="questionText" rows="4" required></textarea>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Add Theory Question
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
            const currentPage = 'Add Question';
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
                paste_data_images: true,
                images_upload_url: 'upload_handler.php',
                automatic_uploads: true,
                file_picker_types: 'image media',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });



            // tinymce.init({
            //     selector: selector,
            //     height: height,
            //     plugins: 'advlist autolink lists link image media table charmap paste fullscreen code',
            //     toolbar: 'undo redo | bold italic underline | bullist numlist | link image media | table charmap | fullscreen code',
            //     paste_data_images: true, // Allows pasting images from clipboard
            //     images_upload_url: 'upload_handler.php', // Placeholder for image upload functionality
            //     automatic_uploads: true, // Enables automatic uploads of media
            //     file_picker_types: 'image media', // Allows image and media file picking
            //     setup: function(editor) {
            //         editor.on('change', function() {
            //             editor.save(); // Auto-save content when it's changed
            //         });
            //     }
            // });
        }

        initializeTinyMCE('#questionText', 240);
        initializeTinyMCE('#optionA', 180);
        initializeTinyMCE('#optionB', 180);
        initializeTinyMCE('#optionC', 180);
        initializeTinyMCE('#optionD', 180);
    </script>

</body>

</html>