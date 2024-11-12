<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming the teacher's ID is stored in the session after login
$teacherId = $_SESSION['staff_id'] ?? ''; // Adjust this according to your session management

// Initialize arrays to hold subjects and classes
$subjects = [];
$classes = [];
$arms = [];

// Fetch all subjects created by the teacher
$subjectQuery = "SELECT id, subject_name FROM subjects ";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row;
    }
}

// Fetch classes created by the teacher (assuming a 'classes' table exists with a 'teacher_id' column)
$classQuery = "SELECT class_id, class_name FROM classes ";
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

// Initialize variables for selected criteria and questions
$selectedClass = $selectedArm = $selectedSubject = $selectedExam = '';
$questions = [];

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

    // Fetch questions based on selected criteria
    $query = "SELECT * FROM questions 
              WHERE class_id = '$selectedClass' 
                AND arm_id = '$selectedArm' 
                AND subject_id = '$selectedSubject' 
                AND exam_id = '$selectedExam'";

    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $questions[] = $row;
        }
    } else {
        showSweetAlert('error', 'Error', 'Failed to fetch questions.', 'manageQuestion.php');
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
            <div class="col-md-12 ">
                <h4 class="text-center mb-4">Add Image To Questions</h4>
                <form id="manageQuestionForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row mb-3">
                        <div class="col-md-3">
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

                        <div class="col-md-3">
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
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Fetch Questions
                        </button>
                    </div>
                </form>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <?php if (!empty($questions)): ?>
                        <div class="table-responsive">
                            <table id="assignmentsTable" class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>S/N</th>
                                        <th>Question</th>
                                        <th>Image</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questions as $index => $question): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td class='text-left'><?php echo strip_tags($question['question_text']); ?></td>
                                            <td>
                                                <?php if (!empty($question['question_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($question['question_image']); ?>" alt="Question Image" width="100">
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="../endpoints/add_img_to_questions.php" method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                    <input type="file" name="question_image" accept="image/*" required>
                                                    <button type="submit" class="btn btn-primary btn-sm mt-2">Upload Image</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>
                    <?php else: ?>
                        <p class="mt-4">No questions found for the selected criteria.</p>
                    <?php endif; ?>
                <?php endif; ?>
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
            const currentPage = 'Add Image To Questions';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>