<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get the attempt ID from the URL
$attempt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($attempt_id <= 0) {
    echo "Invalid attempt ID.";
    exit();
}

// Fetch the exam attempt details including score, total questions, and exam details (mark per question)
$query = "
    SELECT exam_attempts.score, exam_attempts.total_questions, exams.exam_title, exams.mark_per_question, exams.total_marks
    FROM exam_attempts
    JOIN exams ON exams.id = exam_attempts.exam_id
    WHERE exam_attempts.attempt_id = '$attempt_id'
";

$result = mysqli_query($connection, $query);
$attempt = mysqli_fetch_assoc($result);

if (!$attempt) {
    echo "Exam attempt not found.";
    exit();
}

// Calculate the total possible score based on mark_per_question and total_questions
$total_possible_score = $attempt['mark_per_question'] * $attempt['total_questions'];

// Format the score to an integer
$score = (int)$attempt['score'];

// Ensure the score is within the total marks
if ($score > $attempt['total_marks']) {
    $score = $attempt['total_marks'];
}

// Display the exam results
?>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container mt-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-light text-white text-center">
                    <h2>Exam Results</h2>
                </div>
                <div class="card-body">
                    <h3 class="text-center text-info mb-4"><?php echo $attempt['exam_title']; ?></h3>
                    <div class="row justify-content-center">
                        <div class="col-md-6 text-center">
                            <div class="result-box p-4 rounded">
                                <h4>Score</h4>
                                <p class="display-4"><?php echo $score; ?> / <?php echo $total_possible_score; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="../dashboard.php" class="btn btn-primary btn-lg">Go Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>

        <?php include("../includes/footer.php"); ?>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Result'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>