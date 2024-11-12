<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

// Check if exam ID and answers are posted
if (isset($_POST['exam_id']) && isset($_POST['answers'])) {
    $exam_id = (int)$_POST['exam_id'];
    $answers = $_POST['answers'];

    // Fetch correct answers from the database
    $connection = connection();
    $score = 0;
    $totalQuestions = 0;

    foreach ($answers as $question_id => $selected_answer) {
        $questionQuery = "
            SELECT correct_answer 
            FROM questions 
            WHERE id = '$question_id' AND exam_id = '$exam_id'
        ";
        $result = mysqli_query($connection, $questionQuery);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            if ($row) {
                $totalQuestions++; // Added the missing '$' here
                if ($row['correct_answer'] === $selected_answer) {
                    $score++;
                }
            }
        }
    }

    // Return the score to the client
    echo json_encode(['mark' => $score, 'total' => $totalQuestions]);
    mysqli_close($connection);
} else {
    echo json_encode(['error' => 'Invalid submission']);
}
