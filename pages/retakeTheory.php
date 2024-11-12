<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Debug: Check if parameters are passed in the URL
var_dump($_GET);

if (isset($_GET['student_id']) && isset($_GET['exam_id'])) {
    $studentId = intval($_GET['student_id']);
    $examId = intval($_GET['exam_id']);

    // Start a transaction
    mysqli_begin_transaction($connection);

    try {
        // Check for existing exam attempt using student_id and theory_exam_id
        $attemptCheckQuery = "SELECT attempt_id FROM theory_exam_attempts WHERE student_id = $studentId AND theory_exam_id = $examId";
        $attemptResult = mysqli_query($connection, $attemptCheckQuery);

        // If there is an existing attempt, allow retake
        if (mysqli_num_rows($attemptResult) > 0) {
            $row = mysqli_fetch_assoc($attemptResult);
            $attemptId = $row['attempt_id'];  // Use the correct field for attempt ID

            // Clear previous exam answers for the student
            $clearAnswersQuery = "DELETE FROM theory_exam_answers WHERE theory_exam_id = $examId AND student_id = $studentId"; // Delete based on exam and student
            if (!mysqli_query($connection, $clearAnswersQuery)) {
                throw new Exception('Failed to clear previous exam answers: ' . mysqli_error($connection));
            }

            // Delete the exam attempt record
            $deleteAttemptQuery = "DELETE FROM theory_exam_attempts WHERE attempt_id = $attemptId";  // Use the attempt_id column to delete the attempt
            if (!mysqli_query($connection, $deleteAttemptQuery)) {
                throw new Exception('Failed to reset exam attempt for retake: ' . mysqli_error($connection));
            }

            // Call SweetAlert to show success message
            showSweetAlert('success', 'Success', 'Exam retake initiated successfully.', 'viewTheory.php');
        } else {
            // Call SweetAlert to show failure message
            showSweetAlert('error', 'Error', 'No active exam attempt found for this student.', 'viewTheory.php');
        }

        // Commit the transaction
        mysqli_commit($connection);
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($connection);
        // Call SweetAlert to show error message
        showSweetAlert('error', 'Error', $e->getMessage(), redirect: 'viewTheory.php');
    }
} else {
    // Call SweetAlert for invalid parameters
    showSweetAlert('error', 'Error', 'Invalid parameters', 'viewTheory.php');
}
