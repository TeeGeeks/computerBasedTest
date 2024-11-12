<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get the student ID from session
$student_id = $_SESSION['student_id'];

// Get the theory exam ID from the URL
$theory_exam_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($theory_exam_id <= 0) {
    showSweetAlert('error', 'Invalid Theory Exam ID', 'The theory exam ID is invalid.', 'selectExam.php');
    exit();
}

// Check if the student has already attempted the theory exam
$attemptCheckQuery = "
    SELECT * FROM theory_exam_attempts 
    WHERE theory_exam_id = '$theory_exam_id' AND student_id = '$student_id'
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
            showSweetAlert('info', 'Theory Exam Continues', 'You can continue your theory exam.', 'startTheoryExam.php?id=' . $theory_exam_id);
            exit();
        } else {
            showSweetAlert('error', 'Extended Time Expired', 'The extended theory exam time has expired.', 'selectExam.php');
            exit();
        }
    } elseif (!is_null($attempt['end_time'])) {
        // Theory exam has already been submitted
        showSweetAlert('error', 'Theory Exam Attempted', 'You have already attempted this theory exam.', 'selectExam.php');
        exit();
    } else {
        // Theory exam is still in progress without extension
        showSweetAlert('info', 'Continue Theory Exam', 'You can continue your theory exam.', 'startTheoryExam.php?id=' . $theory_exam_id);
        exit();
    }
}

// Fetch theory exam details and instructions
$examQuery = "
    SELECT 
        theory_exams.id AS theory_exam_id,
        theory_exams.exam_title,
        theory_exams.exam_date,
        theory_exams.exam_time,
        theory_exams.instructions,
        theory_exams.exam_duration,
        theory_exams.exam_deadline,
        subjects.subject_name
    FROM theory_exams
    INNER JOIN subjects ON theory_exams.subject_id = subjects.id
    WHERE theory_exams.id = '$theory_exam_id'
";

$examResult = mysqli_query($connection, $examQuery);

if (!$examResult || mysqli_num_rows($examResult) === 0) {
    showSweetAlert('error', 'Theory Exam Not Found', 'The theory exam you are trying to access does not exist.', 'selectExam.php');
    exit();
}

$exam = mysqli_fetch_assoc($examResult);

// Get the current date and time in Nigeria
date_default_timezone_set('Africa/Lagos');
$currentDateTime = new DateTime();

// Combine theory exam date and time for comparison
$examDateTime = new DateTime($exam['exam_date'] . ' ' . $exam['exam_time']);
$examDeadlineTime = new DateTime($exam['exam_deadline']); // Using the exam_deadline field

// Check if the theory exam has not started yet
if ($currentDateTime < $examDateTime) {
    showSweetAlert('warning', 'Theory Exam Not Started', 'The theory exam has not started yet. Please come back later.', 'selectTheoryExam.php');
    exit();
}

// Check if the theory exam has already expired
if ($currentDateTime > $examDeadlineTime) {
    showSweetAlert1('error', 'Theory Exam Time Expired', 'The theory exam time has expired. You can no longer take this exam.', 'selectTheoryExam.php');
    exit();
}

// Close the database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png" />
    <title>Take Theory Exam</title>
    <!-- Fonts and icons -->
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/material-icons.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
    <link id="pagestyle" href="<?php echo BASE_URL; ?>assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/mycss.css">
    <script src="<?php echo BASE_URL; ?>assets/js/tinymce/tinymce/tinymce.min.js"></script>
    <style>
        .math {
            font-family: 'STIX Two Math', serif;
        }
    </style>
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
                    <p><strong>Theory Exam Date:</strong> <?php echo htmlspecialchars($exam['exam_date']); ?></p>
                    <p><strong>Theory Exam Time:</strong> <?php echo htmlspecialchars($exam['exam_time']); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($exam['exam_duration']); ?> minutes</p>
                    <hr>

                    <!-- Instruction Section with Styling and Warning -->
                    <div class="alert alert-warning d-flex align-items-center" role="alert" style="background-color: #ffe5b4; color: #d75f00; font-weight: bold; padding: 15px;">
                        <i class="fas fa-exclamation-triangle me-2" style="font-size: 20px;"></i>
                        <strong>Important:</strong> Please do not reload or navigate away from this page while taking the exam. If you do, your exam will be automatically submitted, and you will not be able to continue.
                    </div>

                    <h5>Instructions:</h5>
                    <p><?php echo nl2br(htmlspecialchars($exam['instructions'])); ?></p>
                </div>
            </div>


            <div class="text-center">
                <a href="startTheoryExam.php?id=<?php echo $exam['theory_exam_id']; ?>" class="btn btn-success btn-lg">Start Theory Exam</a>
            </div>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php"); ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script src="<?php echo BASE_URL; ?>assets/js/core/sweetalert.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = '<?php echo htmlspecialchars($exam['exam_title']); ?>';
            updateBreadcrumbs(['Pages', 'Available Theory Exams', currentPage]);
        });
    </script>
</body>

</html>