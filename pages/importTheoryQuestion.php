<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

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

// Fetch exams
$examQuery = "SELECT * FROM theory_exams";
$examResult = mysqli_query($connection, $examQuery);
if ($examResult) {
    while ($row = mysqli_fetch_assoc($examResult)) {
        $exams[] = $row;
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $examId = intval($_POST['exam']);
    $subjectId = intval($_POST['subject']);
    $classId = intval($_POST['class']);
    $armId = intval($_POST['arm']);
    $file = $_FILES['file'];

    // Validate required fields
    if (empty($examId) || empty($subjectId) || empty($classId) || empty($armId) || empty($file['name'])) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields and upload a file.', 'importTheoryQuestion.php');
        exit;
    }

    // Validate file type (DOC and PDF)
    $allowedTypes = [
        'application/pdf', // PDF
        'application/msword', // DOC
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' // DOCX
    ];

    if (!in_array($file['type'], $allowedTypes)) {
        showSweetAlert('error', 'Invalid File Type', 'Please upload a valid DOC or PDF file.', 'importTheoryQuestion.php');
        exit;
    }

    // Move the file to the uploads directory
    $uploadDir = '../uploads/theory_questions/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
    }

    $filePath = $uploadDir . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Check if the record already exists
        $checkQuery = "SELECT id FROM theory_questions 
                       WHERE theory_exam_id = '$examId' 
                       AND subject_id = '$subjectId' 
                       AND class_id = '$classId' 
                       AND arm_id = '$armId'";

        $checkResult = mysqli_query($connection, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            // Record exists, update the file path
            $updateQuery = "UPDATE theory_questions 
                            SET file_path = '$filePath' 
                            WHERE theory_exam_id = '$examId' 
                            AND subject_id = '$subjectId' 
                            AND class_id = '$classId' 
                            AND arm_id = '$armId'";
            if (mysqli_query($connection, $updateQuery)) {
                showSweetAlert('success', 'Success', 'File uploaded and question updated successfully.', 'manageTheoryQuestion.php');
            } else {
                showSweetAlert('error', 'Database Error', 'Failed to update the question in the database.', 'importTheoryQuestion.php');
            }
        } else {
            // Record does not exist, insert a new row
            $insertQuery = "INSERT INTO theory_questions (theory_exam_id, subject_id, class_id, arm_id, file_path, teacher_id)
                            VALUES ('$examId', '$subjectId', '$classId', '$armId', '$filePath', " . ($isAdmin ? "NULL" : "'$teacherId'") . ")";
            if (mysqli_query($connection, $insertQuery)) {
                showSweetAlert('success', 'Success', 'File uploaded and question added successfully.', 'manageTheoryQuestion.php');
            } else {
                showSweetAlert('error', 'Database Error', 'Failed to add the question to the database.', 'importTheoryQuestion.php');
            }
        }
    } else {
        showSweetAlert('error', 'Upload Error', 'Failed to upload the file.', 'importTheoryQuestion.php');
    }
}
?>


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
                <h4 class="text-center mb-4 text-primary">Import Theory Questions</h4>
                <form action="importTheoryQuestion.php" method="POST" enctype="multipart/form-data">
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
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select an arm</option>
                                <?php
                                // Populate the arm options
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
                        <label for="file" class="form-label">Upload Questions (DOC/PDF)</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".doc,.docx,.pdf" required>
                        <small class="text-muted">Upload a DOC or PDF file with questions.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Questions</button>
                </form>
            </div>
        </div>


        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>


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
            fetch(`fetchTheory.php?classId=${classId}&subjectId=${subjectId}`)
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
                    console.error('Error:', error);
                    examDropdown.innerHTML = '<option value="" disabled>Error loading exams</option>';
                });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Import Theory Questions';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>