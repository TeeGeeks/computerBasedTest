<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

if (isset($_GET['studentId']) && isset($_GET['examId'])) {
    $studentId = intval($_GET['studentId']);
    $examId = intval($_GET['examId']);

    // Start a transaction
    mysqli_begin_transaction($connection);

    try {
        // Check for existing exam attempt
        $attemptCheckQuery = "SELECT attempt_id FROM exam_attempts WHERE student_id = ? AND exam_id = ?";
        $stmt = mysqli_prepare($connection, $attemptCheckQuery);
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $examId);
        mysqli_stmt_execute($stmt);
        $attemptResult = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($attemptResult) > 0) {
            $row = mysqli_fetch_assoc($attemptResult);
            $attemptId = $row['attempt_id'];

            // Clear previous exam answers for the attempt
            $clearAnswersQuery = "DELETE FROM exam_answers WHERE attempt_id = ?";
            $stmt = mysqli_prepare($connection, $clearAnswersQuery);
            mysqli_stmt_bind_param($stmt, "i", $attemptId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to clear previous exam answers: ' . mysqli_error($connection));
            }

            // Delete the exam attempt record
            $deleteAttemptQuery = "DELETE FROM exam_attempts WHERE attempt_id = ?";
            $stmt = mysqli_prepare($connection, $deleteAttemptQuery);
            mysqli_stmt_bind_param($stmt, "i", $attemptId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete exam attempt: ' . mysqli_error($connection));
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No active exam found for retake.']);
            exit();
        }

        // Commit the transaction
        mysqli_commit($connection);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($connection);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}
