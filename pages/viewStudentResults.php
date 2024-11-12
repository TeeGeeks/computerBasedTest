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

// Initialize variables for selected criteria and results
$selectedClass = $selectedSubject = $selectedExam = '';
$results = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClass = trim($_POST['class']);
    $selectedSubject = trim($_POST['subject']);
    $selectedExam = trim($_POST['exam']);

    // Validate required fields
    if (empty($selectedClass) || empty($selectedSubject) || empty($selectedExam)) {
        showSweetAlert('warning', 'Missing Fields', 'Please select class, subject, and exam.', 'viewStudentResults.php');
        exit;
    }

    // Fetch student exam results based on selected criteria
    $query = "
    SELECT 
        ea.student_id,                              -- Select the student's ID from the exam_attempts table
        SUM(ea.score) AS total_score,               -- Calculate the total score for each student by summing their scores
        MAX(ea.created_at) AS attempt_date,         -- Get the date of the latest attempt by each student
        CONCAT(u.surname, ' ', u.other_names) AS student_name,  -- Combine surname and other names for the full name of the student
        ex.total_marks                               -- Fetch the total marks for the exam
    FROM 
        exam_attempts ea                            -- From the exam_attempts table (aliased as ea)
    JOIN 
        students u ON ea.student_id = u.id         -- Join with the students table (aliased as u) to get student details
    JOIN 
        exams ex ON ea.exam_id = ex.id             -- Join with the exams table (aliased as ex) to get exam details
    WHERE 
        u.class_id = '$selectedClass'               -- Filter results to only include students from the selected class
        AND ea.exam_id = '$selectedExam'            -- Filter results to only include the specified exam
        AND ex.teacher_id = '$teacherId'            -- Filter results to only include exams taught by the specified teacher
    GROUP BY 
        ea.student_id                               -- Group the results by student ID to aggregate scores
    ";



    $result = mysqli_query($connection, $query);
    if ($result) {
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
        <div class="container py-4">
            <div class="col-md-12">
                <h4 class="text-center mb-4">View Student Results</h4>
                <form id="viewResultsForm" action="" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-4">
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

                        <div class="col-md-4">
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

                        <div class="col-md-4">
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
                        <button type="submit" class="btn btn-primary">Fetch Results</button>
                    </div>
                </form>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($results)):
                ?>
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>S/N</th>
                                    <th>Student Name</th>
                                    <th>Score</th>
                                    <th>Total Marks</th> <!-- New column for total marks -->
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
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
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