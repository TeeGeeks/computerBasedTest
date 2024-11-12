<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Teacher's ID from the session
$teacherId = $_SESSION['staff_id'] ?? '';

// Initialize arrays to hold subjects and classes
$subjects = [];
$classes = [];

// Check if the logged-in user is an admin or a teacher
$isAdmin = ($_SESSION['user_role'] === 'admin');

// Fetch subjects for selection
$subjectQuery = "SELECT id, subject_name FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row;
    }
}

// Fetch classes for selection
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

// Initialize variables for selected criteria and results
$selectedClass = $selectedSubject = $selectedExam = '';
$results = [];

// Handle form submission for filtering exams
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClass = mysqli_real_escape_string($connection, trim($_POST['class']));
    $selectedSubject = mysqli_real_escape_string($connection, trim($_POST['subject']));
    $selectedExam = mysqli_real_escape_string($connection, trim($_POST['exam']));

    // Validate required fields
    if (empty($selectedClass) || empty($selectedSubject) || empty($selectedExam)) {
        showSweetAlert('warning', 'Missing Fields', 'Please select class, subject, and exam.', 'viewStudentResults.php');
        exit;
    }

    // Check if the teacher is assigned to the selected class and subject
    if (!$isAdmin) {
        $assignmentCheckQuery = "
            SELECT 1 
            FROM subject_assignments 
            WHERE teacher_id = '$teacherId' AND class_id = '$selectedClass' AND subject_id = '$selectedSubject'
        ";
        $assignmentCheckResult = mysqli_query($connection, $assignmentCheckQuery);
        if (!$assignmentCheckResult || mysqli_num_rows($assignmentCheckResult) === 0) {
            showSweetAlert('error', 'Unauthorized Access', 'You are not assigned to this class or subject.', 'viewStudentResults.php');
            exit;
        }
    }

    // Build the base query
    $query = "
    SELECT 
        ea.student_id,
        MAX(ea.start_time) AS start_time,
        MAX(ea.end_time) AS end_time,
        CONCAT(u.surname, ' ', u.other_names) AS student_name,
        ea.score  -- Fetch the score here
    FROM 
        theory_exam_attempts ea
    JOIN 
        students u ON ea.student_id = u.id
    JOIN 
        theory_exams ex ON ea.theory_exam_id = ex.id
    WHERE 
        u.class_id = '$selectedClass' 
        AND ex.subject_id = '$selectedSubject'
        AND ea.theory_exam_id = '$selectedExam'";

    // Add the teacher filter if the user is a teacher
    if (!$isAdmin) {
        $query .= " AND ex.teacher_id = '$teacherId'";  // Filter by teacher ID if not admin
    }

    // Complete the query by grouping by student_id
    $query .= " GROUP BY ea.student_id";

    // Execute the query
    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
    } else {
        showSweetAlert('error', 'Error', 'Failed to fetch student exam attempts.', 'viewStudentResults.php');
    }
}

mysqli_close($connection);
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Theory Exam Results</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4">
            <div class="col-md-12 ">
                <h4 class="text-center mb-4">View Theory Exam Results</h4>
                <form id="viewResultsForm" action="" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= htmlspecialchars($class['class_id']) ?>" <?= $selectedClass == $class['class_id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchExams()">
                                <option value="" disabled selected>Select a subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= htmlspecialchars($subject['id']) ?>" <?= $selectedSubject == $subject['id'] ? 'selected' : '' ?>><?= htmlspecialchars($subject['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="exam" class="form-label">Exam</label>
                            <select class="form-select" id="exam" name="exam" required>
                                <option value="" disabled selected>Select an exam</option>
                                <?php
                                if (!empty($selectedClass) && !empty($selectedSubject)) {
                                    $connection = connection();
                                    $examQuery = "SELECT id, exam_title FROM exams WHERE teacher_id = '$teacherId'";
                                    $examResult = mysqli_query($connection, $examQuery);
                                    if ($examResult) {
                                        while ($exam = mysqli_fetch_assoc($examResult)) {
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
                    <button type="submit" class="btn btn-primary">Fetch Results</button>
                </form>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($results)): ?>
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Student Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Score</th> <!-- Added Score column -->
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $index => $result): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($result['student_name']) ?></td>
                                        <td><?= htmlspecialchars($result['start_time']) ?></td>
                                        <td><?= htmlspecialchars($result['end_time']) ?></td>
                                        <td>
                                            <!-- Check if score exists and display it, otherwise show "N/A" -->
                                            <?= isset($result['score']) && $result['score'] !== null ? htmlspecialchars($result['score']) : 'N/A' ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-info" onclick="viewSubmission('<?= $result['student_id'] ?>', '<?= $selectedExam ?>')">View Submission</button>
                                            <button class="btn btn-success action-btn" onclick="retakeTest('<?php echo $result['student_id']; ?>', '<?php echo $selectedExam; ?>')">Retake Test</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <p class="mt-4">No results found for the selected criteria.</p>
                <?php endif; ?>

            </div>
        </div>
        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
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
        function viewSubmission(studentId, examId) {
            window.location.href = `viewTheorySubmission.php?student_id=${studentId}&theory_exam_id=${examId}`;
        }

        function retakeTest(studentId, examId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to allow this student to retake the test?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, allow retake'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `retakeTheory.php?student_id=${studentId}&exam_id=${examId}`;
                }
            });
        }
    </script>
</body>

</html>