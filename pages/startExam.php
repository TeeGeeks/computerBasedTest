<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get the exam ID from the URL
$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($exam_id <= 0) {
    echo "Invalid exam ID.";
    exit();
}

// Fetch exam details including duration, mark_per_question, and total_marks
$examQuery = "
    SELECT exam_title, exam_duration, mark_per_question, total_marks, show_results 
    FROM exams 
    WHERE id = '$exam_id'
";
$examResult = mysqli_query($connection, $examQuery);
$exam = mysqli_fetch_assoc($examResult);

if (!$exam) {
    echo "Exam not found.";
    exit();
}

// Check if the student has already attempted the exam
$student_id = $_SESSION['student_id'];
$attemptCheckQuery = "
    SELECT * FROM exam_attempts 
    WHERE exam_id = '$exam_id' AND student_id = '$student_id'
";
$attemptCheckResult = mysqli_query($connection, $attemptCheckQuery);
$attempt = mysqli_fetch_assoc($attemptCheckResult);

// Calculate the end time based on the exam duration and any extended time
$end_time = null;

if ($attempt) {
    // If an attempt exists, set the end time based on extended time only
    $extended_time = $attempt['extended_time'] * 60; // Convert minutes to seconds
    $end_time = time() + $extended_time; // Set end time based on only the extended time
} else {
    // If no attempt exists, calculate end time based on the exam duration
    $durationInSeconds = $exam['exam_duration'] * 60; // Convert minutes to seconds
    $end_time = time() + $durationInSeconds; // Set end time for new takers
}

// Fetch questions for the exam in random order
$questionsQuery = "
    SELECT id, question_text, option_a, option_b, option_c, option_d, correct_answer, question_image 
    FROM questions 
    WHERE exam_id = '$exam_id'
    ORDER BY RAND()"; // Randomize questions
$questionsResult = mysqli_query($connection, $questionsQuery);

// Fetch the total number of questions
$totalQuestions = mysqli_num_rows($questionsResult);
$questions = [];
while ($row = mysqli_fetch_assoc($questionsResult)) {
    $questions[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = json_decode($_POST['answers_json'], true); // Decode the answers JSON

    // Begin transaction to safely save the attempt and answers
    mysqli_begin_transaction($connection);

    try {
        // Check for existing exam attempt
        $attemptCheckQuery = "SELECT attempt_id, score, answered_questions, extended_time, total_questions FROM exam_attempts WHERE student_id = $student_id AND exam_id = $exam_id";
        $attemptResult = mysqli_query($connection, $attemptCheckQuery);

        if (!$attemptResult) {
            throw new Exception('Error checking for existing attempt: ' . mysqli_error($connection));
        }

        if (mysqli_num_rows($attemptResult) > 0) {
            // Update existing attempt
            $row = mysqli_fetch_assoc($attemptResult);
            $attempt_id = $row['attempt_id'];
            $correctAnswers = 0;
            $answeredQuestions = 0;

            foreach ($questions as $question) {
                $selected_answer = isset($answers[$question['id']]) ? $answers[$question['id']] : null;
                $is_correct = ($selected_answer == $question['correct_answer']) ? 1 : 0;

                // Count correct answers and answered questions
                if ($is_correct) {
                    $correctAnswers++;
                }
                if ($selected_answer !== null) {
                    $answeredQuestions++;
                }

                // Update the answer in exam_answers
                $query = "INSERT INTO exam_answers (attempt_id, question_id, selected_answer, correct_answer, is_correct)
                          VALUES ($attempt_id, {$question['id']}, '$selected_answer', '{$question['correct_answer']}', $is_correct)
                          ON DUPLICATE KEY UPDATE 
                          selected_answer = '$selected_answer', 
                          correct_answer = '{$question['correct_answer']}', 
                          is_correct = $is_correct";
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Error updating exam answer: ' . mysqli_error($connection));
                }
            }

            // Calculate the score based on correct answers and mark per question
            $score = $correctAnswers * $exam['mark_per_question'];

            // Ensure score does not exceed total marks
            if ($score > $exam['total_marks']) {
                $score = $exam['total_marks'];
            }

            // Update exam attempt with the final score and answered question count
            $query = "UPDATE exam_attempts SET score = $score, answered_questions = $answeredQuestions WHERE attempt_id = $attempt_id";
            if (!mysqli_query($connection, $query)) {
                throw new Exception('Error updating exam attempt: ' . mysqli_error($connection));
            }
        } else {
            // Insert a new exam attempt if no existing attempt is found
            $query = "INSERT INTO exam_attempts (exam_id, student_id, start_time, end_time, score, total_questions, answered_questions, extended_time)
                      VALUES ($exam_id, $student_id, NOW(), FROM_UNIXTIME($end_time), 0, $totalQuestions, 0, 0)"; // Default extended_time to 0
            if (!mysqli_query($connection, $query)) {
                throw new Exception('Error inserting exam attempt: ' . mysqli_error($connection));
            }
            $attempt_id = mysqli_insert_id($connection); // Get the generated attempt_id

            // Initialize variables for calculating score
            $correctAnswers = 0;
            $answeredQuestions = 0;

            foreach ($questions as $question) {
                $selected_answer = isset($answers[$question['id']]) ? $answers[$question['id']] : null;
                $is_correct = ($selected_answer == $question['correct_answer']) ? 1 : 0;

                // Count correct answers and answered questions
                if ($is_correct) {
                    $correctAnswers++;
                }
                if ($selected_answer !== null) {
                    $answeredQuestions++;
                }

                // Insert the answer into exam_answers
                $query = "INSERT INTO exam_answers (attempt_id, question_id, selected_answer, correct_answer, is_correct)
                          VALUES ($attempt_id, {$question['id']}, '$selected_answer', '{$question['correct_answer']}', $is_correct)";
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Error inserting exam answer: ' . mysqli_error($connection));
                }
            }

            // Calculate the score based on correct answers and mark per question
            $score = $correctAnswers * $exam['mark_per_question'];

            // Ensure score does not exceed total marks
            if ($score > $exam['total_marks']) {
                $score = $exam['total_marks'];
            }

            // Update exam attempt with the final score and answered question count
            $query = "UPDATE exam_attempts 
                        SET score = $score, 
                            answered_questions = $answeredQuestions, 
                            total_questions = $totalQuestions,
                            extended_time = 0
                        WHERE attempt_id = $attempt_id";

            if (!mysqli_query($connection, $query)) {
                throw new Exception('Error updating exam attempt: ' . mysqli_error($connection));
            }
        }

        // After updating, double-check that extended_time is 0 and total_questions is correct
        $checkQuery = "SELECT extended_time, total_questions FROM exam_attempts WHERE attempt_id = $attempt_id";
        $checkResult = mysqli_query($connection, $checkQuery);
        if ($checkResult) {
            $row = mysqli_fetch_assoc($checkResult);
            if ($row['extended_time'] !== 0 || $row['total_questions'] != $totalQuestions) {
                // Forcefully update to reset extended_time to 0 and ensure total_questions is correct
                $resetQuery = "UPDATE exam_attempts 
                       SET extended_time = 0, total_questions = $totalQuestions 
                       WHERE attempt_id = $attempt_id";
                if (!mysqli_query($connection, $resetQuery)) {
                    throw new Exception('Error resetting extended_time or updating total_questions: ' . mysqli_error($connection));
                }
            }
        }

        // Commit the transaction
        mysqli_commit($connection);

        // After the exam is submitted and processed
        $showResults = $exam['show_results']; // Fetch this value from the exam details

        // Redirect to the results page based on the value of show_results
        if ($showResults == 1) {
            header("Location: results.php?id=$attempt_id");
        } else {
            // Redirect elsewhere or show a message if results shouldn't be displayed
            header("Location: thankYou.php");
        }
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($connection);
        showSweetAlert('error', 'Error', $e->getMessage(), null);
    }

    mysqli_close($connection);
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['exam_title']); ?> - Take Exam</title>
    <link rel="stylesheet" href="../path/to/bootstrap.css"> <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="../path/to/custom.css"> <!-- Include custom styles -->

    <style>
        body {
            user-select: none;
            /* Standard */
            -webkit-user-select: none;
            /* Safari */
            -moz-user-select: none;
            /* Firefox */
            /* background-color: #f5f5f5; */
        }

        /* .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .question-header {
            color: #2b2b2b;
            font-weight: bold;
        }

        .options label {
            display: flex;
            margin-right: 10px;
            cursor: pointer;
        } */
        .options label {
            cursor: pointer;
        }

        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination .answered {
            background-color: #28a745;
            color: white;
        }

        .pagination .unanswered {
            background-color: #007bff;
            color: white;
        }

        .pagination .current-page {
            background-color: #ffc107;
            /* Yellow color for current page */
            color: white;
            /* Text color for the current page */
        }

        .question_text {
            font-weight: bolder;
        }

        .question-image img {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            cursor: pointer;
            border-radius: 5px;
            transition: transform 0.3s ease-in-out;
        }

        .question-image img:hover {
            transform: scale(1.5);
            z-index: 10;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .countdown-container {
            background-color: #f8f9fa;
            /* Light background color */
            padding: 20px;
            /* Add some padding */
            border-radius: 10px;
            /* Rounded corners */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Soft shadow for depth */
            transition: background-color 0.3s;
            /* Smooth transition on hover */
        }

        .countdown-container h4 {
            font-size: 24px;
            /* Larger font size */
            color: #333;
            /* Dark text color */
            font-weight: bold;
            /* Bold text */
            letter-spacing: 1px;
            /* Spacing between letters */
        }

        #timer {
            font-size: 36px;
            /* Larger font size for the timer */
            color: #e74c3c;
            /* Red color for urgency */
            font-weight: bold;
            /* Bold text for emphasis */
            padding: 10px 20px;
            /* Add padding around the timer */
            border: 2px solid #e74c3c;
            /* Border around the timer */
            border-radius: 5px;
            /* Rounded corners for the timer */
            background-color: #fff;
            /* White background for contrast */
            transition: transform 0.2s;
            /* Smooth animation on hover */
        }

        /* Optional hover effect */
        .countdown-container:hover {
            background-color: #eaeaea;
            /* Change background color on hover */
        }

        #timer:hover {
            transform: scale(1.1);
            /* Slightly enlarge the timer on hover */
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <!-- <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg"> -->

    <div class="container py-4">
        <h2 class="text-center mb-4"><?php echo htmlspecialchars($exam['exam_title']); ?></h2>

        <!-- Countdown Timer -->
        <!-- <div class="text-center mb-4 countdown-container">
            <h4>Time Remaining: <span id="timer">00:00</span></h4>
        </div> -->
        <div class="text-center mb-4 countdown-container">
            <h4>Time Remaining: <span id="timer">00:00</span></h4>
            <p id="extended-time-notice" class="text-danger" style="display: none;">You have extended time.</p>
        </div>

        <form id="examForm" action="?id=<?php echo $exam_id; ?>" method="post">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <input type="hidden" name="answers_json" id="answers_json" value="">

            <!-- Display Questions -->
            <!-- <div class="mb-4">
                <div class="card"> -->
            <div id="question-container"></div>
            <!-- </div>
            </div> -->

            <!-- Pagination Controls -->
            <div class="pagination">
                <button type="button" id="prev" class="btn btn-secondary" disabled>Previous</button>
                <div id="pagination-buttons"></div>
                <button type="button" id="next" class="btn btn-secondary">Next</button>
                <button type="button" id="submitExam" class="btn btn-success d-none">Submit Exam</button>
            </div>
        </form>
    </div>

    <footer style="margin-top: 80px;">
        <?php include("../includes/footer.php"); ?>
    </footer>
    <!-- </main> -->

    <?php include("../includes/script.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let questions = <?php echo json_encode($questions); ?>;
        let totalQuestions = <?php echo $totalQuestions; ?>;
        let currentQuestionIndex = 0;
        let answers = {};

        // Replace with the actual student ID variable from PHP
        let studentId = <?php echo $student_id; ?>; // Assume student_id is available in PHP

        // Initialize timer with exam duration in seconds
        let initialDuration = <?php echo $exam['exam_duration'] * 60; ?>; // Convert to seconds
        let extendedTime = <?php echo isset($attempt) ? $attempt['extended_time'] * 60 : 0; ?>; // Fetch extended time from PHP

        // Load remaining time from localStorage or set to initial duration
        let timeRemaining = localStorage.getItem(`timeRemaining_${studentId}`) || (extendedTime > 0 ? extendedTime : initialDuration);

        // Load answers from sessionStorage if available
        if (sessionStorage.getItem(`answers_${studentId}`)) {
            answers = JSON.parse(sessionStorage.getItem(`answers_${studentId}`));
        }

        // Prevent copying and highlighting text
        document.addEventListener('copy', function(event) {
            event.preventDefault();
            alert('Copying is not allowed during the exam.');
        });

        document.addEventListener('keydown', function(event) {
            // Prevent Ctrl+C (copy) and Ctrl+X (cut)
            if (event.ctrlKey && (event.key === 'c' || event.key === 'C' || event.key === 'x' || event.key === 'X')) {
                event.preventDefault();
                alert('Copying/Cutting is not allowed during the exam.');
            }
        });

        function loadQuestion(index) {
            let question = questions[index];
            let questionHTML = `
       <div class="mb-4">
    <div class="">
        <div id="question-container">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h3 class="question-header text-danger">QUESTION ${index + 1} of ${totalQuestions}</h3>
                        <hr class="custom-hr" />
                    <h5 class="question_text mb-5">${question.question_text}</h5>
                    ${question.question_image ? `<div class="question-image mb-3"><a data-fancybox="gallery" href="${question.question_image}"><img src="${question.question_image}" alt="Question Image"></a></div>` : ''}
                    <div class="options">
                        <div class="form-check">
                            (A)  <input class="form-check-input" type="radio" name="answer_${question.id}" id="answer_${question.id}_A" value="A" ${answers[question.id] === 'A' ? 'checked' : ''}>
                            <label class="form-check-label" for="answer_${question.id}_A">
                                ${question.option_a}
                            </label>
                        </div>
                        <div class="form-check">
                            (B) <input class="form-check-input" type="radio" name="answer_${question.id}" id="answer_${question.id}_B" value="B" ${answers[question.id] === 'B' ? 'checked' : ''}>
                            <label class="form-check-label" for="answer_${question.id}_B">
                                ${question.option_b}
                            </label>
                        </div>
                        <div class="form-check">
                            (C) <input class="form-check-input" type="radio" name="answer_${question.id}" id="answer_${question.id}_C" value="C" ${answers[question.id] === 'C' ? 'checked' : ''}>
                            <label class="form-check-label" for="answer_${question.id}_C">
                                ${question.option_c}
                            </label>
                        </div>
                        <div class="form-check">
                            (D) <input class="form-check-input" type="radio" name="answer_${question.id}" id="answer_${question.id}_D" value="D" ${answers[question.id] === 'D' ? 'checked' : ''}>
                            <label class="form-check-label" for="answer_${question.id}_D">
                               ${question.option_d}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>`;
            $('#question-container').html(questionHTML);
            saveAnswer(); // Ensure the answer is saved when loading the question
            console.log(`Loaded Question ${question.id}: ${question.question_text}`);
        }

        function updatePagination() {
            let paginationHTML = '';
            for (let i = 0; i < totalQuestions; i++) {
                let className = (i === currentQuestionIndex) ? 'current-page' : (answers[questions[i].id] ? 'answered' : 'unanswered');
                paginationHTML += `<button type="button" class="btn ${className} mx-1" onclick="navigateTo(${i})">${i + 1}</button>`;
            }
            $('#pagination-buttons').html(paginationHTML);
        }

        function navigateTo(index) {
            if (saveAnswer()) {
                currentQuestionIndex = index;
                loadQuestion(index);
                updatePagination();
                toggleButtons();
                console.log(`Navigated to Question ${index + 1}`);
            }
        }

        function toggleButtons() {
            $('#prev').prop('disabled', currentQuestionIndex === 0);
            $('#next').prop('disabled', currentQuestionIndex === totalQuestions - 1);
            $('#submitExam').toggleClass('d-none', currentQuestionIndex !== totalQuestions - 1);
        }

        $('#next').click(function() {
            if (saveAnswer()) {
                if (currentQuestionIndex < totalQuestions - 1) {
                    currentQuestionIndex++;
                    loadQuestion(currentQuestionIndex);
                    updatePagination();
                    toggleButtons();
                    console.log(`Moved to next question: ${currentQuestionIndex + 1}`);
                }
            }
        });

        $('#prev').click(function() {
            if (saveAnswer()) {
                if (currentQuestionIndex > 0) {
                    currentQuestionIndex--;
                    loadQuestion(currentQuestionIndex);
                    updatePagination();
                    toggleButtons();
                    console.log(`Moved to previous question: ${currentQuestionIndex + 1}`);
                }
            }
        });

        window.onbeforeunload = function() {
            return "Are you sure you want to leave? Your progress will not be saved!";
        };

        function disableTabWarning() {
            window.onbeforeunload = null;
        }

        function startTimer() {
            let timerInterval = setInterval(function() {
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    alert('Time is up! Submitting your exam.');
                    submitExam();
                } else {
                    timeRemaining--;
                    localStorage.setItem(`timeRemaining_${studentId}`, timeRemaining); // Store time remaining in localStorage
                    updateTimerDisplay();
                }
            }, 1000);
        }

        function submitExam() {
            let answersJSON = JSON.stringify(answers);
            $('#answers_json').val(answersJSON); // Set the hidden input field with the JSON answers
            disableTabWarning(); // Disable tab leave warning before submission

            document.getElementById('examForm').submit(); // Submit the exam form
            localStorage.removeItem(`timeRemaining_${studentId}`); // Remove the time remaining from localStorage
        }
        let clearTimer; // Timer variable
        const clearTimeLimit = 30 * 60 * 1000; // 30 minutes in milliseconds

        function saveAnswer() {
            let question = questions[currentQuestionIndex];
            let selectedAnswer = $("input[name='answer_" + question.id + "']:checked").val();
            if (selectedAnswer) {
                answers[question.id] = selectedAnswer; // Save selected answer
            } else {
                delete answers[question.id]; // Remove answer if none selected
            }
            sessionStorage.setItem(`answers_${studentId}`, JSON.stringify(answers)); // Store answers with student ID
            console.log(`Saved answer for Question ${question.id}: ${selectedAnswer || 'None'}`);

            // Set a timeout to clear answers after the specified timeframe
            if (!clearTimer) { // Only set the timer if it is not already set
                clearTimer = setTimeout(() => {
                    clearAnswers(); // Call the function to clear answers
                }, clearTimeLimit);
            }

            return true;
        }

        function clearAnswers() {
            sessionStorage.removeItem(`answers_${studentId}`); // Remove the answers from session storage for this student
            clearTimer = null; // Reset the timer variable
            console.log('Answers cleared from session storage after the timeframe.');
        }

        function updateTimerDisplay() {
            let minutes = Math.floor(timeRemaining / 60);
            let seconds = timeRemaining % 60;
            document.getElementById('timer').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        $(document).ready(function() {
            loadQuestion(currentQuestionIndex);
            updatePagination();
            toggleButtons();
            startTimer();

            if (extendedTime > 0) {
                document.getElementById('extended-time-notice').style.display = 'block';
            }
        });

        $('#submitExam').click(function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to change your answers after submitting!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit my exam!'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (saveAnswer()) {
                        submitExam(); // Call the submit function if confirmed
                    }
                }
            });
        });

        function calculateScore() {
            let score = 0;
            questions.forEach(question => {
                if (answers[question.id] === question.correct_answer) {
                    score++;
                }
            });
            return score; // Return the total score
        }
    </script>


</body>

</html>