<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$conn = connection();

// Initialize variables
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$theory_exam_id = isset($_GET['theory_exam_id']) ? (int)$_GET['theory_exam_id'] : 0;
$exam_info = [];
$student_name = "";

// Check user role from session (assuming a session variable holds this)
$user_role = $_SESSION['user_role']; // E.g., 'admin', 'teacher'
$teacherId = $_SESSION['staff_id'] ?? null;

// Check if the form is submitted to update the marks
if (isset($_POST['submit_mark'])) {
    // Loop through all the scores provided in the form and update the database
    foreach ($_POST as $key => $value) {
        // Only process score inputs
        if (strpos($key, 'score_') === 0) {
            $question_id = str_replace('score_', '', $key); // Extract question ID from the field name
            $score = (int)$value;

            // Validate the score (you can add any additional validation here)
            if ($score >= 0 && $score <= 100) {
                // Update the score in the `theory_exam_attempts` table
                $update_query = "
                    UPDATE theory_exam_attempts
                    SET score = $score
                    WHERE theory_exam_id = $theory_exam_id AND student_id = $student_id
                ";
                $update_result = mysqli_query($conn, $update_query);

                if (!$update_result) {
                    showSweetAlert('error', 'Error Updating Score', 'There was an issue updating the score.');
                } else {
                    showSweetAlert('success', 'Score Updated', 'The score has been updated successfully.');
                }
            }
        }
    }
}


if ($student_id > 0 && $theory_exam_id > 0) {
    // Fetch student name
    $student_query = "SELECT surname, other_names FROM students WHERE id = $student_id";
    $student_result = mysqli_query($conn, $student_query);
    if ($student_result && mysqli_num_rows($student_result) > 0) {
        $student_data = mysqli_fetch_assoc($student_result);
        $student_name = $student_data['surname'] . ' ' . $student_data['other_names'];
    }

    // Fetch exam information
    $exam_query = "
    SELECT te.exam_date, te.exam_time, te.exam_duration, te.exam_title, s.subject_name AS subject_name, s.id AS subject_id
    FROM theory_exam_attempts tea
    JOIN theory_exams te ON tea.theory_exam_id = te.id
    JOIN subjects s ON te.subject_id = s.id
    WHERE tea.theory_exam_id = $theory_exam_id AND tea.student_id = $student_id
    ";
    $exam_result = mysqli_query($conn, $exam_query);

    if ($exam_result && mysqli_num_rows($exam_result) > 0) {
        $exam_info = mysqli_fetch_assoc($exam_result);

        // Check if the user is a teacher and verify subject assignment
        if ($user_role === 'staff') {
            $subject_id = $exam_info['subject_id'];
            $assignment_query = "SELECT * FROM subject_assignments WHERE teacher_id = $teacherId AND subject_id = $subject_id";
            $assignment_result = mysqli_query($conn, $assignment_query);

            if (!$assignment_result || mysqli_num_rows($assignment_result) == 0) {
                showSweetAlert('error', 'Unauthorized Access', 'You are not authorized to view this submission.');
                exit();
            }
        }
    } else {
        showSweetAlert('error', 'Exam Not Found', 'No exam data found for the provided student and exam ID.');
        exit();
    }

    // SQL query to fetch questions and answers
    $query = "
        SELECT q.id AS question_id, q.question_text, tea.answer_text, tea.created_at, tea.updated_at 
        FROM theory_questions q
        LEFT JOIN theory_exam_answers tea 
        ON q.id = tea.question_id AND tea.student_id = $student_id AND tea.theory_exam_id = $theory_exam_id
        WHERE q.theory_exam_id = $theory_exam_id
    ";

    // Execute the query
    $result = mysqli_query($conn, $query);

    // Fetch results if available
    if ($result && mysqli_num_rows($result) > 0) {
        $answers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $answers[] = $row;
        }
    }
} else {
    showSweetAlert('error', 'Invalid Input', 'Invalid student ID or exam ID.');
    exit();
}


?>

<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f7fc;
        color: #333;
    }

    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: #fff;
        margin: 20px 0;
        padding: 20px;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: scale(1.02);
    }

    .card-title {
        font-size: 1.5em;
        font-weight: bold;
        margin-bottom: 10px;
        color: #007bff;
    }

    .card-text {
        font-size: 1.1em;
        margin-bottom: 10px;
    }

    h2 {
        color: #333;
        font-size: 2em;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .btn {
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
    }

    .student-info {
        background-color: #f7f7f7;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .student-info h4 {
        font-size: 1.4em;
        margin-bottom: 10px;
        color: #555;
    }

    .answer-textarea {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin-bottom: 10px;
        resize: vertical;
        font-size: 1em;
        color: #333;
    }

    .answer-textarea:focus {
        outline: none;
        border-color: #007bff;
    }

    footer {
        text-align: center;
        padding: 10px;
        background-color: #f1f1f1;
        margin-top: 40px;
    }

    footer p {
        margin: 0;
        font-size: 1em;
        color: #555;
    }

    .form-control {
        border: 1px solid #ced4da;
        /* Adjust border color */
        border-radius: 0.375rem;
        /* Adjust border radius */
        padding: 0.375rem 0.75rem;
        /* Adjust padding */
    }

    .form-control:focus {
        border-color: #80bdff;
        /* Adjust focus border color */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        /* Add focus shadow */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <h2>Student Theory Exam Answers</h2>

            <?php if (isset($error_message)) : ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php elseif (!empty($exam_info)) : ?>
                <div class="student-info">
                    <h4>Student: <?php echo htmlspecialchars($student_name); ?></h4>
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($exam_info['subject_name']); ?></p>
                    <p><strong>Exam Date:</strong> <?php echo htmlspecialchars($exam_info['exam_date']); ?></p>
                    <p><strong>Exam Time:</strong> <?php echo htmlspecialchars($exam_info['exam_time']); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($exam_info['exam_duration']); ?> minutes</p>
                </div>

                <hr>

                <?php if (count($answers) > 0) : ?>
                    <?php foreach ($answers as $answer) : ?>
                        <div class="card">
                            <label for="questionText" class="mb-3 text-bolder text-primary">Questions: </label>
                            <textarea class="answer-textarea" id="questionText1" name="questionText" rows="4">
                                <?php echo htmlspecialchars($answer['question_text']); ?>
                            </textarea>
                            <label for="questionText" class="my-3 text-bolder text-primary">Answers: </label>
                            <textarea class="answer-textarea" id="questionText" name="questionText" rows="4">
                                <?php echo "\n\n" . htmlspecialchars($answer['answer_text']); ?>
                            </textarea>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No answers found for this student and exam.</p>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Add the Mark Form here -->
            <form method="POST" action="">
                <label for="score_<?php echo $answer['question_id']; ?>" class="my-3 text-bolder text-primary">Mark for Answer: </label>

                <?php
                // Fetch the current score for the student and exam for this question
                $question_id = $answer['question_id'];
                $score_query = "
        SELECT score 
        FROM theory_exam_attempts 
        WHERE theory_exam_id = $theory_exam_id AND student_id = $student_id 
    ";
                $score_result = mysqli_query($conn, $score_query);
                $current_score = 0;

                if ($score_result && mysqli_num_rows($score_result) > 0) {
                    // Fetch the current score from the result
                    $current_score = mysqli_fetch_assoc($score_result)['score'];
                }

                // Populate the score input with the current score, if available
                ?>

                <input type="number"
                    id="score_<?php echo $answer['question_id']; ?>"
                    name="score_<?php echo $answer['question_id']; ?>"
                    class="form-control"
                    min="0"
                    max="100"
                    placeholder="Enter score"
                    value="<?php echo $current_score; ?>"
                    required>

                <!-- Hidden Inputs to Pass Exam and Student ID -->
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                <input type="hidden" name="theory_exam_id" value="<?php echo $theory_exam_id; ?>">

                <button type="submit" name="submit_mark" class="btn btn-primary mt-3">Submit Mark</button>
            </form>


        </div>

        <footer>
            <?php include("../includes/footer.php"); ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

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
        initializeTinyMCE('#questionText1', 240);
    </script>
</body>

</html>

<?php
// Close the database connection
mysqli_close($conn);
?>