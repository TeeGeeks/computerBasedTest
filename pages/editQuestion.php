<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming the teacher's ID is stored in the session after login
$teacherId = $_SESSION['staff_id'] ?? '';
$isAdmin = $_SESSION['user_role'] === 'admin';

// Fetch teacher's assigned classes and arms if the user is not an admin
$assignedClassesAndArms = [];
if (!$isAdmin && !empty($teacherId)) {
    $assignedQuery = "SELECT class_id, arm_id FROM subject_assignments WHERE teacher_id = '$teacherId'";
    $assignedResult = mysqli_query($connection, $assignedQuery);

    while ($row = mysqli_fetch_assoc($assignedResult)) {
        $assignedClassesAndArms[] = ['class_id' => $row['class_id'], 'arm_id' => $row['arm_id']];
    }
}

// Get the question ID from the GET parameter
$questionId = $_GET['id'] ?? '';
if (empty($questionId)) {
    showSweetAlert('error', 'Invalid Request', 'No question ID provided.', 'manageQuestion.php');
    exit;
}

// Fetch the existing question details
$questionQuery = "SELECT * FROM questions WHERE id = '$questionId'";
$questionResult = mysqli_query($connection, $questionQuery);
if (!$questionResult || mysqli_num_rows($questionResult) === 0) {
    showSweetAlert('error', 'Not Found', 'Question not found.', 'manageQuestion.php');
    exit;
}
$question = mysqli_fetch_assoc($questionResult);

// Validate if the teacher has permission to edit this question's class and arm
$hasPermission = $isAdmin;
if (!$isAdmin) {
    foreach ($assignedClassesAndArms as $assigned) {
        if ($assigned['class_id'] == $question['class_id'] && $assigned['arm_id'] == $question['arm_id']) {
            $hasPermission = true;
            break;
        }
    }
}

if (!$hasPermission) {
    showSweetAlert('error', 'Unauthorized', 'You do not have permission to edit this question.', 'manageQuestion.php');
    exit;
}

// Fetch subjects, classes, arms, and exams
$subjects = [];
$classes = [];
$arms = [];

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

// Fetch exams related to the subject of the existing question
$selectedSubjectId = $question['subject_id'];
$examQuery = "SELECT id, exam_title FROM exams WHERE subject_id = '$selectedSubjectId' AND (teacher_id = '$teacherId' OR teacher_id IS NULL)";
$examResult = mysqli_query($connection, $examQuery);
$exams = [];
if ($examResult) {
    while ($row = mysqli_fetch_assoc($examResult)) {
        $exams[] = $row;
    }
}

// Handle form submission for updating the question
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = mysqli_real_escape_string($connection, trim($_POST['exam']));
    $subject_id = mysqli_real_escape_string($connection, trim($_POST['subject']));
    $class_id = mysqli_real_escape_string($connection, trim($_POST['class']));
    $arm_id = mysqli_real_escape_string($connection, trim($_POST['arm']));
    $questionText = mysqli_real_escape_string($connection, trim($_POST['questionText']));
    $optionA = mysqli_real_escape_string($connection, trim($_POST['optionA']));
    $optionB = mysqli_real_escape_string($connection, trim($_POST['optionB']));
    $optionC = mysqli_real_escape_string($connection, trim($_POST['optionC']));
    $optionD = mysqli_real_escape_string($connection, trim($_POST['optionD']));
    $correctAnswer = mysqli_real_escape_string($connection, trim($_POST['correctAnswer']));
    $image = $_FILES['questionImage'];
    $imagePath = $question['question_image'];

    // Validate required fields
    if (empty($examId) || empty($subject_id) || empty($class_id) || empty($questionText) || empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD) || empty($correctAnswer)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'editQuestion.php?id=' . $questionId);
        exit;
    }

    if (empty($arm_id) || $arm_id == 0) {
        showSweetAlert('warning', 'Invalid Arm', 'Please select a valid arm.', 'editQuestion.php?id=' . $questionId);
        exit;
    }

    // Handle image upload
    if ($image['name']) {
        $uploadDir = '../uploads/questions/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($image['type'], $allowedTypes)) {
            showSweetAlert('warning', 'Invalid File Type', 'Only JPG, PNG, and GIF files are allowed.', 'editQuestion.php?id=' . $questionId);
            exit;
        }

        $newImagePath = $uploadDir . uniqid() . '-' . basename($image['name']);
        if (!move_uploaded_file($image['tmp_name'], $newImagePath)) {
            showSweetAlert('error', 'Upload Error', 'Failed to upload the image. Please try again.', 'editQuestion.php?id=' . $questionId);
            exit;
        }

        if (!empty($imagePath) && file_exists($imagePath)) {
            unlink($imagePath);
        }
        $imagePath = $newImagePath;
    }

    // Check if the exam matches the selected class and arm
    $examClassQuery = "SELECT class_id, arm_id FROM exams WHERE id = '$examId'";
    $examClassResult = mysqli_query($connection, $examClassQuery);
    $examClassRow = mysqli_fetch_assoc($examClassResult);

    if ($examClassRow['class_id'] != $class_id || $examClassRow['arm_id'] != $arm_id) {
        showSweetAlert('warning', 'Class or Arm Mismatch', 'The selected exam does not belong to the selected class or arm.', 'editQuestion.php?id=' . $questionId);
        exit;
    }

    // Prevent duplicate questions in the same exam
    $duplicateQuery = "SELECT COUNT(*) as duplicate_count FROM questions WHERE exam_id = '$examId' AND question_text = '$questionText' AND id != '$questionId'";
    $duplicateResult = mysqli_query($connection, $duplicateQuery);
    $duplicateRow = mysqli_fetch_assoc($duplicateResult);

    if ($duplicateRow['duplicate_count'] > 0) {
        showSweetAlert('warning', 'Duplicate Question', 'This question already exists for the selected exam.', 'editQuestion.php?id=' . $questionId);
        exit;
    }

    // Update query for the question
    $updateQuery = "UPDATE questions 
        SET subject_id = '$subject_id', 
            class_id = '$class_id', 
            arm_id = '$arm_id', 
            question_text = '$questionText', 
            option_a = '$optionA', 
            option_b = '$optionB', 
            option_c = '$optionC', 
            option_d = '$optionD', 
            correct_answer = '$correctAnswer', 
            question_image = '$imagePath' 
        WHERE id = '$questionId'";

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Question updated successfully.', 'manageQuestion.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editQuestion.php?id=' . $questionId);
    }
}
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
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4 text-primary">Edit Question</h4>
                <form id="editQuestionForm" action="" method="POST" enctype="multipart/form-data" onsubmit="showSpinner()">

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchExamss()">
                                <option value="" disabled>Select a subject</option>
                                <?php
                                // Populate the subject options
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
                            <label for="class" class="form-label">Class</label> <!-- New Class Field -->
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled>Select a class</option>
                                <?php
                                // Populate the class options
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
                            <label for="arm" class="form-label">Arm</label> <!-- New Arm Field -->
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled>Select an arm</option>
                                <?php
                                // Populate the arm options
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
                        <label for="questionImage" class="form-label">Upload Question Image</label>
                        <input type="file" class="form-control" id="questionImage" name="questionImage" accept="image/*">
                        <?php if (!empty($question['question_image']) && file_exists($question['question_image'])): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($question['question_image']); ?>" alt="Question Image" style="max-width: 200px; height: auto;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="optionA" class="form-label">Option A</label>
                            <textarea class="form-control" id="optionA" name="optionA" rows="2" required><?php echo htmlspecialchars($question['option_a']); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="optionB" class="form-label">Option B</label>
                            <textarea class="form-control" id="optionB" name="optionB" rows="2" required><?php echo htmlspecialchars($question['option_b']); ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="optionC" class="form-label">Option C</label>
                            <textarea class="form-control" id="optionC" name="optionC" rows="2" required><?php echo htmlspecialchars($question['option_c']); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="optionD" class="form-label">Option D</label>
                            <textarea class="form-control" id="optionD" name="optionD" rows="2" required><?php echo htmlspecialchars($question['option_d']); ?></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="correctAnswer" class="form-label">Correct Answer</label>
                        <select class="form-select" id="correctAnswer" name="correctAnswer" required>
                            <option value="" disabled>Select the correct answer</option>
                            <option value="A" <?php echo ($question['correct_answer'] == 'A') ? 'selected' : ''; ?>>Option A</option>
                            <option value="B" <?php echo ($question['correct_answer'] == 'B') ? 'selected' : ''; ?>>Option B</option>
                            <option value="C" <?php echo ($question['correct_answer'] == 'C') ? 'selected' : ''; ?>>Option C</option>
                            <option value="D" <?php echo ($question['correct_answer'] == 'D') ? 'selected' : ''; ?>>Option D</option>
                        </select>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Update Question
                        </button>
                        <a href="manageQuestion.php" class="btn btn-secondary">Cancel</a>
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
            const currentPage = 'Edit Question';
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
        initializeTinyMCE('#optionA', 180);
        initializeTinyMCE('#optionB', 180);
        initializeTinyMCE('#optionC', 180);
        initializeTinyMCE('#optionD', 180);
    </script>

</body>

</html>
<?php
// Close the database connection
mysqli_close($connection);
?>