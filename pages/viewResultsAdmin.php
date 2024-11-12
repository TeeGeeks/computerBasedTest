<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming the admin's ID is stored in the session after login
$adminId = $_SESSION['staff_id'] ?? '';

// Initialize arrays to hold subjects and classes
$subjects = [];
$classes = [];
$arms = [];

// Check if the logged-in user is an admin
$isAdmin = $_SESSION['user_role'] === 'admin';

// Fetch all subjects for admin
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
$selectedClass = $selectedSubject = $selectedExam = '';
$results = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClass = trim($_POST['class']);
    $selectedArm = trim($_POST['arm']);  // Include arm
    $selectedSubject = trim($_POST['subject']);
    $selectedExam = trim($_POST['exam']);

    // Validate required fields
    if (empty($selectedClass) || empty($selectedArm) || empty($selectedSubject) || empty($selectedExam)) {
        showSweetAlert('warning', 'Missing Fields', 'Please select class, arm, subject, and exam.', 'viewStudentResults.php');
        exit;
    }

    // Fetch student exam results based on selected criteria
    $query = "
    SELECT 
        ea.student_id,
        SUM(ea.score) AS total_score,
        MAX(ea.created_at) AS attempt_date,
        CONCAT(u.surname, ' ', u.other_names) AS student_name,
        ex.total_marks
    FROM 
        exam_attempts ea
    JOIN 
        students u ON ea.student_id = u.id
    JOIN 
        exams ex ON ea.exam_id = ex.id
    WHERE 
        u.class_id = '$selectedClass'
        AND u.arm_id = '$selectedArm'
        AND ea.exam_id = '$selectedExam'
    GROUP BY 
        ea.student_id
    ";

    $result = mysqli_query($connection, $query);
    if ($result) {
        $results = [];  // Initialize an empty array to store results
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
    } else {
        showSweetAlert('error', 'Error', 'Failed to fetch student results.', 'viewStudentResults.php');
    }
}


// Close the database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Student Results</title>
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
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4">
            <div class="col-md-12 ">
                <h4 class="text-center mb-4">View Student Results</h4>
                <form id="viewResultsForm" action="" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                foreach ($classes as $class) {
                                    $selected = ($selectedClass == $class['class_id']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . $selected . '>' . htmlspecialchars($class['class_name']) . '</option>';
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

                        <div class="col-md-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchExams()">
                                <option value="" disabled selected>Select a subject</option>
                                <?php
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

                        <div class="col-md-3">
                            <label for="exam" class="form-label">Exam</label>
                            <select class="form-select" id="exam" name="exam" required>
                                <option value="" disabled selected>Select an exam</option>
                                <?php
                                if (!empty($selectedClass) && !empty($selectedSubject)) {
                                    $connection = connection();
                                    $examQuery = "SELECT id, exam_title FROM exams WHERE subject_id = '$selectedSubject';";
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

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Fetch Results</button>
                    </div>
                </form>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($results)): ?>
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>S/N</th>
                                    <th>Student Name</th>
                                    <th>Score</th>
                                    <th>Total Marks</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $index => $result): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['total_score']); ?></td>
                                        <td><?php echo htmlspecialchars($result['total_marks']); ?></td>
                                        <td><?php echo htmlspecialchars($result['attempt_date']); ?></td>
                                        <td>
                                            <button class="btn btn-warning action-btn" onclick="extendTime('<?php echo $result['student_id']; ?>', '<?php echo $selectedExam; ?>')">Extend Time</button>
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
        function extendTime(studentId, examId) {
            Swal.fire({
                title: 'Extend Time',
                input: 'number',
                inputLabel: 'Enter minutes to extend',
                inputPlaceholder: 'e.g., 15',
                showCancelButton: true,
                confirmButtonText: 'Extend',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value || value <= 0) {
                        return 'You must enter a valid number of minutes!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const minutes = result.value;

                    fetch(`extendTime.php?studentId=${studentId}&examId=${examId}&minutes=${minutes}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success!', 'Time extended successfully.', 'success');
                                location.reload();
                            } else {
                                Swal.fire('Error!', 'Failed to extend time: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error extending time:', error);
                            Swal.fire('Error!', 'An error occurred while extending time.', 'error');
                        });
                }
            });
        }

        function retakeTest(studentId, examId) {
            Swal.fire({
                title: 'Retake Exam',
                text: "Are you sure you want to allow this student to retake the exam?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, retake the exam!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Call the retake API or function
                    fetch(`retakeTest.php?studentId=${studentId}&examId=${examId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success!', 'The student has been allowed to retake the exam.', 'success');
                                location.reload(); // Reload the page to reflect changes
                            } else {
                                Swal.fire('Error!', 'Failed to initiate retake: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error retaking exam:', error);
                            Swal.fire('Error!', 'An error occurred while initiating the retake.', 'error');
                        });
                }
            });
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'View Students Result';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>