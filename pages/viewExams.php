<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Initialize an array to hold available exams
$exams = [];

// Fetch the class of the logged-in student
$classQuery = "SELECT class_id FROM students WHERE id = '" . $_SESSION['student_id'] . "'";
$classResult = mysqli_query($connection, $classQuery);

if ($classResult && mysqli_num_rows($classResult) > 0) {
    $classRow = mysqli_fetch_assoc($classResult);
    $studentClass = $classRow['class_id'];

    $currentDate = date('Y-m-d'); // Get the current date

    // Fetch regular exams based on the student's class and date
    $examQuery = "
        SELECT 
            exams.id AS id,
            exams.exam_title,
            exams.exam_date,
            exams.exam_time,
            subjects.subject_name,
            'Objectives' AS exam_type
        FROM exams 
        INNER JOIN subjects ON exams.subject_id = subjects.id 
        WHERE exams.class_id = '$studentClass' 
        AND exams.exam_date >= '$currentDate'
    ";

    // Fetch theory exams based on the student's class and date
    $theoryExamQuery = "
        SELECT 
            theory_exams.id AS id,
            theory_exams.exam_title,
            theory_exams.exam_date,
            theory_exams.exam_time,
            subjects.subject_name,
            'theory' AS exam_type
        FROM theory_exams 
        INNER JOIN subjects ON theory_exams.subject_id = subjects.id 
        WHERE theory_exams.class_id = '$studentClass' 
        AND theory_exams.exam_date >= '$currentDate'
    ";

    // Execute both queries
    $examResult = mysqli_query($connection, $examQuery);
    $theoryExamResult = mysqli_query($connection, $theoryExamQuery);

    // Process regular exams
    if ($examResult) {
        while ($row = mysqli_fetch_assoc($examResult)) {
            // Add status logic for regular exams
            $examId = $row['id'];
            $attemptQuery = "
                SELECT attempt_id, extended_time, start_time, end_time 
                FROM exam_attempts 
                WHERE exam_id = '$examId' 
                AND student_id = '" . $_SESSION['student_id'] . "'
            ";
            $attemptResult = mysqli_query($connection, $attemptQuery);

            // Add the attempt status to the exam data
            if (mysqli_num_rows($attemptResult) > 0) {
                $attempt = mysqli_fetch_assoc($attemptResult);
                if ($attempt['end_time'] === NULL) {
                    // Exam is in progress
                    if ($attempt['extended_time'] > 0) {
                        $row['status'] = 'In Progress (Time Extended)';
                        $row['button_disabled'] = false;
                        $row['button_text'] = 'Resume Exam';
                    } else {
                        $row['status'] = 'In Progress';
                        $row['button_disabled'] = false;
                        $row['button_text'] = 'Resume Exam';
                    }
                } else {
                    // Exam has been submitted
                    if ($attempt['extended_time'] > 0) {
                        $row['status'] = 'Submitted (Time Extended)';
                        $row['button_disabled'] = false;
                        $row['button_text'] = 'Review Exam';
                    } else {
                        $row['status'] = 'Attempted';
                        $row['button_disabled'] = true;
                        $row['button_text'] = 'Already Attempted';
                    }
                }
            } else {
                // Exam not yet attempted
                $row['status'] = 'Not Attempted';
                $row['button_disabled'] = false;
                $row['button_text'] = 'Take Exam';
            }

            // Add regular exam to the exams array
            $exams[] = $row;
        }
    }

    // Process theory exams
    if ($theoryExamResult) {
        while ($row = mysqli_fetch_assoc($theoryExamResult)) {
            // Add status logic for theory exams
            $examId = $row['id'];
            $attemptQuery = "
                SELECT attempt_id, extended_time, start_time, end_time 
                FROM exam_attempts 
                WHERE exam_id = '$examId' 
                AND student_id = '" . $_SESSION['student_id'] . "'
            ";
            $attemptResult = mysqli_query($connection, $attemptQuery);

            // Add the attempt status to the exam data
            if (mysqli_num_rows($attemptResult) > 0) {
                $attempt = mysqli_fetch_assoc($attemptResult);
                if ($attempt['end_time'] === NULL) {
                    // Theory exam is in progress
                    if ($attempt['extended_time'] > 0) {
                        $row['status'] = 'In Progress (Time Extended)';
                        $row['button_disabled'] = false;
                        $row['button_text'] = 'Resume Exam';
                    } else {
                        $row['status'] = 'In Progress';
                        $row['button_disabled'] = false;
                        $row['button_text'] = 'Resume Exam';
                    }
                } else {
                    // Theory exam has been submitted
                    if ($attempt['extended_time'] > 0) {
                        $row['status'] = 'Submitted (Time Extended)';
                        $row['button_disabled'] = false;
                        $row['button_text'] = 'Review Exam';
                    } else {
                        $row['status'] = 'Attempted';
                        $row['button_disabled'] = true;
                        $row['button_text'] = 'Already Attempted';
                    }
                }
            } else {
                // Theory exam not yet attempted
                $row['status'] = 'Not Attempted';
                $row['button_disabled'] = false;
                $row['button_text'] = 'Take Exam';
            }

            // Add theory exam to the exams array
            $exams[] = $row;
        }
    }

    // If no exams were found, show a message
    if (empty($exams)) {
        echo "No exams found for your class.";
    }
} else {
    // Handle error fetching student class
    echo "Error fetching class information: " . mysqli_error($connection);
}

// Close the database connection
mysqli_close($connection);
?>




<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4">
            <h4 class="text-center mb-4">Available Exams</h4>
            <?php if (!empty($exams)): ?>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Exam Type</th>
                                <th>Exam Title</th>
                                <th>Subject</th>
                                <th>Exam Date</th>
                                <th>Exam Time</th>
                                <th>Status</th> <!-- Status Column -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td class="text-primary"><?php echo htmlspecialchars(ucfirst($exam['exam_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_date']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_time']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['status']); ?></td> <!-- Display status -->
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="alert alert-info text-center">No exams available at this time.</div>
            <?php endif; ?>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php"); ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Available Exam(s)'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>