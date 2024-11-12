<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming the teacher's ID is stored in the session after login
$teacherId = $_SESSION['staff_id'] ?? null; // Use null for admin if needed

// Initialize arrays to hold subjects, classes, arms, and assigned classes/arms
$subjects = [];
$classes = [];
$arms = [];
$assignedClasses = [];

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin';

// Fetch all subjects
$subjectQuery = "SELECT id, subject_name FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row;
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
        $arms[] = $row;
    }
}

// Fetch exams created by the teacher
$examQuery = "SELECT id, exam_title, arm_id, class_id FROM exams WHERE teacher_id = '$teacherId'";
$examResult = mysqli_query($connection, $examQuery);
$exams = [];
if ($examResult) {
    while ($row = mysqli_fetch_assoc($examResult)) {
        $exams[] = $row;
    }
}

// Fetch classes and arms assigned to the teacher
$assignedClassesQuery = "SELECT class_id, arm_id FROM subject_assignments WHERE teacher_id = '$teacherId'";
$assignedClassesResult = mysqli_query($connection, $assignedClassesQuery);
if ($assignedClassesResult) {
    while ($row = mysqli_fetch_assoc($assignedClassesResult)) {
        $assignedClasses[] = $row;
    }
}

// Function to check if teacher is assigned to the selected class and arm
function isAssignedClassArm($assignedClasses, $class_id, $arm_id)
{
    foreach ($assignedClasses as $assignment) {
        if ($assignment['class_id'] == $class_id && $assignment['arm_id'] == $arm_id) {
            return true;
        }
    }
    return false;
}

// Add new question if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = trim($_POST['exam']);
    $subject_id = trim($_POST['subject']);
    $class_id = trim($_POST['class']);
    $arm_id = trim($_POST['arm']);
    $questionText = trim($_POST['questionText']);
    $optionA = trim($_POST['optionA']);
    $optionB = trim($_POST['optionB']);
    $optionC = trim($_POST['optionC']);
    $optionD = trim($_POST['optionD']);
    $correctAnswer = trim($_POST['correctAnswer']);

    // Escape inputs to prevent SQL injection
    $examId = mysqli_real_escape_string($connection, $examId);
    $subject_id = mysqli_real_escape_string($connection, $subject_id);
    $class_id = mysqli_real_escape_string($connection, $class_id);
    $arm_id = mysqli_real_escape_string($connection, $arm_id);
    $questionText = mysqli_real_escape_string($connection, $questionText);
    $optionA = mysqli_real_escape_string($connection, $optionA);
    $optionB = mysqli_real_escape_string($connection, $optionB);
    $optionC = mysqli_real_escape_string($connection, $optionC);
    $optionD = mysqli_real_escape_string($connection, $optionD);
    $correctAnswer = mysqli_real_escape_string($connection, $correctAnswer);

    // Handle image upload
    $image = $_FILES['questionImage'];
    $imagePath = '';

    // Validate required fields
    if (empty($examId) || empty($subject_id) || empty($class_id) || empty($arm_id) || empty($questionText) || empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD) || empty($correctAnswer)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'addQuestion.php');
        exit;
    }

    // Fetch the class_id and arm_id of the selected exam
    $examClassQuery = "SELECT class_id, arm_id FROM exams WHERE id = '$examId'";
    $examClassResult = mysqli_query($connection, $examClassQuery);
    if (!$examClassResult) {
        showSweetAlert('error', 'Database Error', 'Unable to fetch exam class association.', 'addQuestion.php');
        exit;
    }
    $examClass = mysqli_fetch_assoc($examClassResult);

    // Check if the selected class and arm match the teacher's assignment
    if (!$isAdmin && !isAssignedClassArm($assignedClasses, $class_id, $arm_id)) {
        showSweetAlert('warning', 'Unauthorized', 'You are not assigned to this class and arm.', 'addQuestion.php');
        exit;
    }

    // Check if an image was uploaded
    if ($image['name']) {
        $uploadDir = '../uploads/questions/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($image['type'], $allowedTypes)) {
            showSweetAlert('warning', 'Invalid File Type', 'Only JPG, PNG, and GIF files are allowed.', 'addQuestion.php');
            exit;
        }
        $imagePath = $uploadDir . uniqid() . '-' . basename($image['name']);
        if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
            showSweetAlert('error', 'Upload Error', 'Failed to upload the image. Please try again.', 'addQuestion.php');
            exit;
        }
    }

    // Check the exam limit
    $limitQuery = "SELECT exam_limit FROM exams WHERE id = '$examId'";
    $limitResult = mysqli_query($connection, $limitQuery);
    $exam = mysqli_fetch_assoc($limitResult);
    $examLimit = $exam['exam_limit'];

    // Count existing questions for the selected exam
    $countQuery = "SELECT COUNT(*) as question_count FROM questions WHERE exam_id = '$examId'";
    $countResult = mysqli_query($connection, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    $currentCount = $countRow['question_count'];

    if ($currentCount >= $examLimit) {
        showSweetAlert('warning', 'Limit Reached', 'You have reached the maximum number of questions for this exam.', 'addQuestion.php');
        exit;
    }

    // Check for duplicate question
    $duplicateQuery = "SELECT COUNT(*) as duplicate_count FROM questions WHERE exam_id = '$examId' AND question_text = '$questionText'";
    $duplicateResult = mysqli_query($connection, $duplicateQuery);
    $duplicateRow = mysqli_fetch_assoc($duplicateResult);

    if ($duplicateRow['duplicate_count'] > 0) {
        showSweetAlert('warning', 'Duplicate Question', 'This question already exists for the selected exam.', 'addQuestion.php');
        exit;
    }

    // Insert the new question
    $insertQuery = "INSERT INTO questions (subject_id, exam_id, class_id, arm_id, question_text, option_a, option_b, option_c, option_d, correct_answer, teacher_id, question_image) 
                    VALUES ('$subject_id', '$examId', '$class_id', '$arm_id', '$questionText', '$optionA', '$optionB', '$optionC', '$optionD', '$correctAnswer', " . ($isAdmin ? "NULL" : "'$teacherId'") . ", '$imagePath')";

    if (mysqli_query($connection, $insertQuery)) {
        showSweetAlert('success', 'Success!', 'Question added successfully.', 'addQuestion.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addQuestion.php');
    }
}

// Close the database connection
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

<!-- TinyMCE Script -->
<!-- <script src="https://cdn.tiny.cloud/1/k014ifj5itll2amihajv2u2mwio7wbnpg1g0yg2fmr1f3m8u/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#questionText',
        plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
        toolbar_mode: 'floating',
        setup: function(editor) {
            editor.on('change', function() {
                editor.save(); // This saves the content back to the <textarea>
            });
        }
    });
</script> -->

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4 text-primary">Add Question</h4>
                <form id="addQuestionForm" action="" method="POST" enctype="multipart/form-data" onsubmit="showSpinner()">

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

                    <div class="mb-3">
                        <label for="questionImage" class="form-label">Upload Question Image</label>
                        <input type="file" class="form-control" id="questionImage" name="questionImage" accept="image/*">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="optionA" class="form-label">Option A</label>
                            <textarea class="form-control" id="optionA" name="optionA" rows="2" style="height: 150px;" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="optionB" class="form-label">Option B</label>
                            <textarea class="form-control" id="optionB" name="optionB" rows="2" style="height: 150px;" required></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="optionC" class="form-label">Option C</label>
                            <textarea class="form-control" id="optionC" name="optionC" rows="2" style="height: 150px;" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="optionD" class="form-label">Option D</label>
                            <textarea class="form-control" id="optionD" name="optionD" rows="2" style="height: 150px;" required></textarea>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label for="correctAnswer" class="form-label">Correct Answer</label>
                        <select class="form-select" id="correctAnswer" name="correctAnswer" required>
                            <option value="" disabled selected>Select the correct answer</option>
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                        </select>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Add Question
                        </button>
                    </div>
                </form>

            </div>
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
            fetch(`fetchExams.php?subjectId=${subjectId}`)
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
        }

        initializeTinyMCE('#questionText', 240);
        initializeTinyMCE('#optionA', 180);
        initializeTinyMCE('#optionB', 180);
        initializeTinyMCE('#optionC', 180);
        initializeTinyMCE('#optionD', 180);
    </script>

</body>

</html>