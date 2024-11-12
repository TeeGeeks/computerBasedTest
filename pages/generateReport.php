<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming the teacher's ID is stored in the session after login
$teacherId = $_SESSION['staff_id'] ?? '';

// Initialize arrays to hold subjects and classes
$subjects = [];
$classes = [];
$arms = [];

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin';


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

// Fetch all arms (make sure to adjust your query if necessary)
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row; // Store each arm in the array
    }
}

// Initialize variables for selected criteria and results
$selectedClass = $selectedArm = $selectedSubject = $selectedExam = '';
$results = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClass = trim($_POST['class']);
    $selectedArm = trim($_POST['arm']);
    $selectedSubject = trim($_POST['subject']);
    $selectedExam = trim($_POST['exam']);

    // Validate required fields
    if (empty($selectedClass) || empty($selectedArm) ||  empty($selectedSubject) || empty($selectedExam)) {
        showSweetAlert('warning', 'Missing Fields', 'Please select class, arm, subject, and exam.', 'manageQuestion.php');
        exit;
    }

    // Redirect to viewReport.php with selected class, subject, and exam as query parameters
    header("Location: report.php?class=$selectedClass&arm=$selectedArm&subject=$selectedSubject&exam=$selectedExam");
    exit;
}

// Close the database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Questions</title>
    <style>
        /* Add your CSS styling here */
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .action-btn {
            margin-right: 5px;
        }

        .text-left {
            text-align: left !important;
            /* Align text to the left */
        }
    </style>

    <!-- Include any additional CSS or JS here -->
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>
        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-8 col-lg-5 col-sm-9">
                <h4 class="text-center mb-4">Generate Report</h4>
                <form id="viewResultsForm" action="" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-12 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                // Populate the class options
                                foreach ($classes as $class) {
                                    $selected = ($selectedClass == $class['class_id']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . $selected . '>' . htmlspecialchars($class['class_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required onchange="fetchExams()">
                                <option value="" disabled selected>Select a arm</option>
                                <?php
                                // Populate the class options
                                if (!empty($arms)) {
                                    foreach ($arms as $arm) {
                                        $selected = ($selectedArm == $class['arm_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($arm['arm_id']) . '" ' . $selected . '>' . htmlspecialchars($arm['arm_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No arms available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
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

                        <div class="col-md-12 mb-3">
                            <label for="exam" class="form-label">Exam</label>
                            <select class="form-select" id="exam" name="exam" required>
                                <option value="" disabled selected>Select an exam</option>
                                <?php
                                // Populate the exam options based on selected class and subject
                                if (!empty($selectedClass) && !empty($selectedSubject)) {
                                    // Re-establish connection to fetch exams
                                    $connection = connection();
                                    $examQuery = "SELECT id, exam_title FROM exams WHERE teacher_id = '$teacherId';";
                                    $examResult = mysqli_query($connection, $examQuery);
                                    if ($examResult) {
                                        while ($exam = mysqli_fetch_assoc($examResult)) {
                                            // Print exam_title before the <option>
                                            echo '<p>' . htmlspecialchars($exam['exam_title']) . '</p>';
                                            $selected = ($selectedExam == $exam['id']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($exam['id']) . '" ' . $selected . '>' . htmlspecialchars($exam['exam_title']) . '</option>';
                                        }
                                    } else {
                                        echo '<option value="" disabled>Error loading exams</option>';
                                    }
                                    mysqli_close($connection);
                                } else {
                                    echo '<option value="" disabled>No exams available</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>


            </div>
        </div>
        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            const currentPage = 'Generate Report';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>