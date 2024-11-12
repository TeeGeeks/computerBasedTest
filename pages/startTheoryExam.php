<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$theory_exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($theory_exam_id <= 0) {
    showSweetAlert('error', 'Invalid Exam ID', 'The theory exam ID provided is not valid.');
    exit();
}

$student_id = $_SESSION['student_id'];
$attemptCheckQuery = "
    SELECT * FROM theory_exam_attempts  
    WHERE theory_exam_id = '$theory_exam_id' AND student_id = '$student_id'
";
$attemptCheckResult = mysqli_query($connection, $attemptCheckQuery);
$attempt = mysqli_fetch_assoc($attemptCheckResult);

$end_time = null;
$theoryExam = null;

if ($attempt) {
    $extended_time = $attempt['extended_time'] * 60;
    $end_time = time() + $extended_time;
} else {
    $theoryExamQuery = "
        SELECT exam_duration, exam_title
        FROM theory_exams
        WHERE id = '$theory_exam_id'
    ";
    $theoryExamResult = mysqli_query($connection, $theoryExamQuery);
    $theoryExam = mysqli_fetch_assoc($theoryExamResult);

    if (!$theoryExam) {
        showSweetAlert('error', 'Exam Not Found', 'The theory exam could not be found.');
        exit();
    }

    $durationInSeconds = $theoryExam['exam_duration'] * 60;
    $end_time = time() + $durationInSeconds;

    $insertAttemptQuery = "
        INSERT INTO theory_exam_attempts (theory_exam_id, student_id, start_time, end_time)
        VALUES ('$theory_exam_id', '$student_id', NOW(), FROM_UNIXTIME($end_time))
    ";
    if (!mysqli_query($connection, $insertAttemptQuery)) {
        showSweetAlert('error', 'Error Starting Exam', 'There was an issue starting the exam attempt.', 'selectExam.php');
        exit();
    }
}

$questionsQuery = "
    SELECT id, question_text 
    FROM theory_questions 
    WHERE theory_exam_id = '$theory_exam_id'";
$questionsResult = mysqli_query($connection, $questionsQuery);

$questions = [];
while ($row = mysqli_fetch_assoc($questionsResult)) {
    $questions[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'];

    foreach ($questions as $question) {
        $answer = isset($answers[$question['id']]) ? $answers[$question['id']] : '';

        $query = "
            INSERT INTO theory_exam_answers (student_id, theory_exam_id, question_id, answer_text)
            VALUES ('$student_id', '$theory_exam_id', '{$question['id']}', '$answer')
            ON DUPLICATE KEY UPDATE answer_text = '$answer'
        ";

        if (!mysqli_query($connection, $query)) {
            showSweetAlert('error', 'Error Saving Answer', 'There was an issue saving your answer.', 'selectExam.php');
            exit();
        }
    }

    showSweetAlert('success', 'Submission Successful', 'Your answers have been saved successfully!', 'thankYou.php');
    exit();
}
?>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <div class="text-center my-4">
                <h4>Time Remaining: <span id="timer">00:00</span></h4>
                <p id="extended-time-notice" class="text-danger" style="display: none;">You have extended time.</p>
            </div>

            <?php if ($theoryExam): ?>
                <h2 class="text-primary">Theory Exam: <?php echo htmlspecialchars($theoryExam['exam_title']); ?></h2>
            <?php else: ?>
                <h2 class="text-danger">Exam Not Found</h2>
            <?php endif; ?>

            <div class="card mt-3 p-4">
                <h4 class="text-dark">Questions</h4>
                <form method="post" action="">
                    <?php foreach ($questions as $question): ?>
                        <div class="mb-3">
                            <label for="questionText" class="form-label">Question Text</label>
                            <textarea class="form-control" id="questionText" name="questionText" rows="4" readonly><?php echo htmlspecialchars($question['question_text']); ?></textarea>

                            <textarea class="form-control" name="answers[<?php echo $question['id']; ?>]" id="questionAnswer" rows="4" placeholder="Type your answer here..."></textarea>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Submit Answers</button>
                </form>
            </div>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php"); ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let endTime = <?php echo $end_time; ?> * 1000;
            const timerElement = document.getElementById('timer');
            const formElement = document.querySelector('form');

            function updateTimer() {
                let now = new Date().getTime();
                let timeLeft = endTime - now;

                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    timerElement.innerText = "Time's up!";
                    alert('Time is up! Your answers will be automatically submitted.');
                    formElement.submit(); // Automatically submit the form
                } else {
                    let minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    let seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    timerElement.innerText = `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
                }
            }

            let countdown = setInterval(updateTimer, 1000);

            <?php if ($attempt && $attempt['extended_time'] > 0): ?>
                document.getElementById('extended-time-notice').style.display = 'block';
            <?php endif; ?>
        });
    </script>

    <script>
        function initializeTinyMCE(selector, height) {
            tinymce.init({
                selector: selector,
                height: height,
                plugins: 'advlist autolink lists link image media table charmap paste fullscreen code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link image media | table charmap | fullscreen code',
                paste_data_images: true,
                images_upload_url: 'upload_handler.php',
                automatic_uploads: true,
                file_picker_types: 'image media',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });
        }

        initializeTinyMCE('#questionText', 240);
        initializeTinyMCE('#questionAnswer', 240);
    </script>
</body>