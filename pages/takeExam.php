<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get the student ID from session
$student_id = $_SESSION['student_id'];

// Get the exam ID from the URL
$exam_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($exam_id <= 0) {
    showSweetAlert('error', 'Invalid Exam ID', 'The exam ID is invalid.', 'selectExam.php');
    exit();
}

// Check if the student has already attempted the exam
$attemptCheckQuery = "
    SELECT * FROM exam_attempts 
    WHERE exam_id = '$exam_id' AND student_id = '$student_id'
";
$attemptCheckResult = mysqli_query($connection, $attemptCheckQuery);

if ($attemptCheckResult && mysqli_num_rows($attemptCheckResult) > 0) {
    $attempt = mysqli_fetch_assoc($attemptCheckResult);

    // Set timezone to Africa/Lagos
    date_default_timezone_set('Africa/Lagos');

    // Get current date and time in Nigeria
    $currentDateTime = new DateTime();

    // Check if the student has extended exam time
    if ($attempt['extended_time'] > 0) {
        $examEndTime = new DateTime($attempt['end_time'], new DateTimeZone('Africa/Lagos'));

        // Check if the current time is still before the extended end time
        if ($currentDateTime < $examEndTime) {
            showSweetAlert('info', 'Exam Continues', 'You can continue your exam.', 'startExam.php?id=' . $exam_id);
            exit();
        } else {
            showSweetAlert('error', 'Extended Time Expired', 'The extended exam time has expired.', 'selectExam.php');
            exit();
        }
    } elseif (!is_null($attempt['end_time'])) {
        // Exam has already been submitted
        showSweetAlert('error', 'Exam Attempted', 'You have already attempted this exam.', 'selectExam.php');
        exit();
    } else {
        // Exam is still in progress without extension
        showSweetAlert('info', 'Continue Exam', 'You can continue your exam.', 'startExam.php?id=' . $exam_id);
        exit();
    }
}

// Fetch exam details and instructions
$examQuery = "
    SELECT 
        exams.id AS exam_id,
        exams.exam_title,
        exams.exam_date,
        exams.exam_time,
        exams.instructions,
        exams.exam_duration,
        exams.exam_deadline,
        subjects.subject_name,
        exams.pass_mark,
        exams.total_marks
    FROM exams
    INNER JOIN subjects ON exams.subject_id = subjects.id
    WHERE exams.id = '$exam_id'
";

$examResult = mysqli_query($connection, $examQuery);

if (!$examResult || mysqli_num_rows($examResult) === 0) {
    showSweetAlert('error', 'Exam Not Found', 'The exam you are trying to access does not exist.', 'selectExam.php');
    exit();
}

$exam = mysqli_fetch_assoc($examResult);

// Get the current date and time in Nigeria
date_default_timezone_set('Africa/Lagos');
$currentDateTime = new DateTime();

// Combine exam date and time for comparison
$examDateTime = new DateTime($exam['exam_date'] . ' ' . $exam['exam_time']);
$examDeadlineTime = new DateTime($exam['exam_deadline']); // Using the exam_deadline field

// Check if the exam has not started yet
if ($currentDateTime < $examDateTime) {
    showSweetAlert('warning', 'Exam Not Started', 'The exam has not started yet. Please come back later.', 'selectExam.php');
    exit();
}

// Check if the exam has already expired
if ($currentDateTime > $examDeadlineTime) {
    showSweetAlert('error', 'Exam Time Expired', 'The exam time has expired. You can no longer take this exam.', 'selectExam.php');
    exit();
}

// Close the database connection
mysqli_close($connection);
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['exam_title']); ?> - Take Exam</title>
    <link rel="stylesheet" href="../path/to/bootstrap.css"> <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="../path/to/custom.css"> <!-- Include custom styles -->
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4">
            <h2 class="text-center mb-4"><?php echo htmlspecialchars($exam['exam_title']); ?></h2>

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Subject: <?php echo htmlspecialchars($exam['subject_name']); ?></h4>
                    <p><strong>Exam Date:</strong> <?php echo htmlspecialchars($exam['exam_date']); ?></p>
                    <p><strong>Exam Time:</strong> <?php echo htmlspecialchars($exam['exam_time']); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($exam['exam_duration']); ?> minutes</p>
                    <p><strong>Total Marks:</strong> <?php echo htmlspecialchars($exam['total_marks']); ?></p>
                    <p><strong>Pass Mark:</strong> <?php echo htmlspecialchars($exam['pass_mark']); ?></p>
                    <hr>
                    <h5>Instructions:</h5>
                    <p><?php echo nl2br(htmlspecialchars($exam['instructions'])); ?></p>
                </div>
            </div>

            <div class="text-center">
                <a href="startExam.php?id=<?php echo $exam['exam_id']; ?>" class="btn btn-success btn-lg">Start Exam</a>
            </div>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php"); ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = '<?php echo htmlspecialchars($exam['exam_title']); ?>'; // Current page title
            updateBreadcrumbs(['Pages', 'Available Exams', currentPage]);
        });
    </script>
</body>

</html>