<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');
require '../vendor/autoload.php'; // Include PhpSpreadsheet autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;

$connection = connection();
$teacherId = $_SESSION['staff_id'] ?? ''; // Assuming teacher's ID is in the session
$isAdmin = $_SESSION['user_role'] === 'admin';

// Initialize arrays for subjects, classes, arms, and exams
$subjects = [];
$classes = [];
$arms = [];
$exams = [];

// Fetch subjects from the database
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

// Initialize variables for selected criteria and questions
$selectedClass = $selectedArm = $selectedSubject = $selectedExam = '';
$questions = [];

// Handle file upload and import process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $examId = intval($_POST['exam']);
    $subject_id = intval($_POST['subject']);
    $class_id = intval($_POST['class']);
    $arm_id = intval($_POST['arm']);
    $file = $_FILES['file'];

    // Validate required fields
    if (empty($examId) || empty($subject_id) || empty($class_id) || empty($arm_id) || empty($file['name'])) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'importQuestion.php');
        exit;
    }

    // Check if the teacher is allowed to import questions for the selected class and arm
    if (!$isAdmin) {
        $assignmentQuery = "SELECT COUNT(*) as count 
                        FROM subject_assignments 
                        WHERE teacher_id = '$teacherId' 
                        AND class_id = '$class_id' 
                        AND arm_id = '$arm_id'";
        $assignmentResult = mysqli_query($connection, $assignmentQuery);
        $assignmentData = mysqli_fetch_assoc($assignmentResult);

        if ($assignmentData['count'] == 0) {
            showSweetAlert('error', 'Unauthorized Access', 'You are not assigned to this class and arm.', 'importQuestion.php');
            exit;
        }
    }


    // Validate file type
    $allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
        'application/csv', // .csv
        'text/plain', // .csv
    ];

    if (!in_array($file['type'], $allowedTypes)) {
        showSweetAlert('error', 'Invalid File Type', 'Please upload a valid Excel or CSV file.', 'importQuestion.php');
        exit;
    }

    // Fetch the question limit for the selected exam
    $examQuery = "SELECT exam_limit FROM exams WHERE id = '$examId'";
    $examResult = mysqli_query($connection, $examQuery);
    $examData = mysqli_fetch_assoc($examResult);
    $questionLimit = $examData['exam_limit'];

    // Count the existing questions for the exam
    $countQuery = "SELECT COUNT(*) as current_count FROM questions WHERE exam_id = '$examId'";
    $countResult = mysqli_query($connection, $countQuery);
    $countData = mysqli_fetch_assoc($countResult);
    $currentCount = $countData['current_count'];

    // Maximum questions that can still be imported
    $maxQuestionsToImport = $questionLimit - $currentCount;

    $questionsInserted = 0; // Counter for successfully inserted questions
    $duplicateQuestions = []; // Array to track duplicate questions from the file
    $duplicateQuestionsCount = []; // Associative array to track duplicate questions from the file
    $existingQuestions = []; // Array to track existing questions in the database

    // Process CSV file
    if ($file['type'] === 'text/csv' || $file['type'] === 'application/csv' || $file['type'] === 'text/plain') {
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            $isHeader = true; // Flag to skip header row
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }

                // Extract question data and sanitize inputs
                $questionText = mysqli_real_escape_string($connection, trim($data[0]));
                $optionA = mysqli_real_escape_string($connection, trim($data[1]));
                $optionB = mysqli_real_escape_string($connection, trim($data[2]));
                $optionC = mysqli_real_escape_string($connection, trim($data[3]));
                $optionD = mysqli_real_escape_string($connection, trim($data[4]));
                $correctAnswer = mysqli_real_escape_string($connection, trim($data[5]));

                // Validate data
                if (empty($questionText) || empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD) || empty($correctAnswer)) {
                    continue; // Skip if any required field is empty
                }

                // Skip if maximum question limit is reached
                if ($questionsInserted >= $maxQuestionsToImport) {
                    showSweetAlert('warning', 'Question Limit Reached', 'Cannot import more questions than the limit set for this exam.', 'manageQuestion.php');
                    exit;
                }

                // Check for duplicate question in the database
                $duplicateQuery = "SELECT COUNT(*) as duplicate_count FROM questions WHERE exam_id = '$examId' AND question_text = '$questionText'";
                $duplicateResult = mysqli_query($connection, $duplicateQuery);
                $duplicateRow = mysqli_fetch_assoc($duplicateResult);

                if ($duplicateRow['duplicate_count'] > 0) {
                    $existingQuestions[] = $questionText; // Add to existing questions array
                    continue; // Skip duplicate questions
                }

                // Insert the new question (if admin, teacher ID is NULL)
                $insertQuery = "INSERT INTO questions (subject_id, exam_id, class_id, arm_id, question_text, option_a, option_b, option_c, option_d, correct_answer, teacher_id) 
                    VALUES ('$subject_id', '$examId', '$class_id', '$arm_id', '$questionText', '$optionA', '$optionB', '$optionC', '$optionD', '$correctAnswer', " . ($isAdmin ? "NULL" : "'$teacherId'") . ")";

                if (mysqli_query($connection, $insertQuery)) {
                    $questionsInserted++;
                    $duplicateQuestions[] = $questionText; // Add to duplicate questions array
                }
            }
            fclose($handle);
        }
    } else {
        // Process Excel file (similar logic as CSV, using PhpSpreadsheet)
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheetData as $index => $row) {
            if ($index === 1) continue; // Skip header row

            // Extract question data and sanitize inputs
            $questionText = mysqli_real_escape_string($connection, trim($row['A']));
            $optionA = mysqli_real_escape_string($connection, trim($row['B']));
            $optionB = mysqli_real_escape_string($connection, trim($row['C']));
            $optionC = mysqli_real_escape_string($connection, trim($row['D']));
            $optionD = mysqli_real_escape_string($connection, trim($row['E']));
            $correctAnswer = mysqli_real_escape_string($connection, trim($row['F']));

            // Validate data and other checks as in the CSV processing
            if (empty($questionText) || empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD) || empty($correctAnswer)) {
                continue; // Skip if any required field is empty
            }

            // Skip if maximum question limit is reached
            if ($questionsInserted >= $maxQuestionsToImport) {
                showSweetAlert('warning', 'Question Limit Reached', 'Cannot import more questions than the limit set for this exam.', 'manageQuestion.php');
                exit;
            }

            // Check for duplicate question in the database
            $duplicateQuery = "SELECT COUNT(*) as duplicate_count FROM questions WHERE exam_id = '$examId' AND question_text = '$questionText'";
            $duplicateResult = mysqli_query($connection, $duplicateQuery);
            $duplicateRow = mysqli_fetch_assoc($duplicateResult);

            if ($duplicateRow['duplicate_count'] > 0) {
                $existingQuestions[] = $questionText; // Add to existing questions array
                continue; // Skip duplicate questions
            }

            // Insert the new question (if admin, teacher ID is NULL)
            $insertQuery = "INSERT INTO questions (subject_id, exam_id, class_id, arm_id, question_text, option_a, option_b, option_c, option_d, correct_answer, teacher_id) 
                VALUES ('$subject_id', '$examId', '$class_id', '$arm_id', '$questionText', '$optionA', '$optionB', '$optionC', '$optionD', '$correctAnswer', " . ($isAdmin ? "NULL" : "'$teacherId'") . ")";

            if (mysqli_query($connection, $insertQuery)) {
                $questionsInserted++;
                $duplicateQuestions[] = $questionText; // Add to duplicate questions array
            }
        }
    }

    // Show success message after processing the file
    showSweetAlert('success', 'Import Complete', "$questionsInserted questions successfully imported.", 'manageQuestion.php');
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png" />
    <title>Import Objectives Questions</title>
    <!-- Fonts and icons -->
    <!-- <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" /> -->
    <!-- Nucleo Icons -->
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/core/fontawsomeicon.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/material-icons.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
    <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" /> -->
    <!-- CSS Files -->
    <link id="pagestyle" href="<?php echo BASE_URL; ?>assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/mycss.css">
    <!-- Place the first <script> tag in your HTML's <head> -->
    <script src="<?php echo BASE_URL; ?>assets/js/tinymce/tinymce/tinymce.min.js">
    </script>
    <!-- <script src="https://cdn.tiny.cloud/1/k014ifj5itll2amihajv2u2mwio7wbnpg1g0yg2fmr1f3m8u/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script> -->

    <!-- <link href="https://fonts.googleapis.com/css2?family=STIX+Two+Math&display=swap" rel="stylesheet"> -->

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
                <h4 class="text-center mb-4 text-primary">Import Objectives Questions</h4>
                <form action="importQuestion.php" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required onchange="fetchExams()">
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                // Populate the class options
                                if (!empty($classes)) {
                                    foreach ($classes as $class) {
                                        $selected = ($selectedClass == $class['class_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . $selected . '>' . htmlspecialchars($class['class_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No classes available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
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

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchExams()">
                                <option value="" disabled selected>Select a subject</option>
                                <?php
                                // Populate the subject options
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        $selected = ($selectedSubject == $subject['id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '" ' . $selected . '>' . htmlspecialchars($subject['subject_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No subjects available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="exam" class="form-label">Exam</label>
                            <select class="form-select" id="exam" name="exam" required>
                                <option value="" disabled selected>Select an exam</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">Upload Questions (CSV/Excel)</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xls,.xlsx,.csv" required>
                        <small class="text-muted">Upload a CSV or Excel file with questions.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Questions</button>
                    <a href="?download=template" class="btn btn-secondary">Download Template</a>
                </form>
            </div>
        </div>
    </main>

    <script>
        function fetchExams() {
            const classId = document.getElementById('class').value;
            const subjectId = document.getElementById('subject').value;
            const examDropdown = document.getElementById('exam');

            if (!classId || !subjectId) {
                examDropdown.innerHTML = '<option value="" disabled selected>Select an exam</option>';
                return;
            }

            // Clear the current exam options
            examDropdown.innerHTML = '<option value="" disabled selected>Loading...</option>';

            // Make an AJAX request to fetch exams based on selected class and subject
            fetch(`fetchExams.php?classId=${classId}&subjectId=${subjectId}`)
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

    <?php include("../includes/script.php"); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Import Questions';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>