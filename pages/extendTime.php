<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

if (isset($_GET['studentId']) && isset($_GET['examId']) && isset($_GET['minutes'])) {
    $studentId = intval($_GET['studentId']);
    $examId = intval($_GET['examId']);
    $minutes = intval($_GET['minutes']);

    // Start a transaction
    mysqli_begin_transaction($connection);

    try {
        // Check for existing exam attempt
        $attemptCheckQuery = "SELECT attempt_id, extended_time, end_time FROM exam_attempts WHERE student_id = ? AND exam_id = ?";
        $stmt = mysqli_prepare($connection, $attemptCheckQuery);
        mysqli_stmt_bind_param($stmt, "ii", $studentId, $examId);
        mysqli_stmt_execute($stmt);
        $attemptResult = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($attemptResult) > 0) {
            $row = mysqli_fetch_assoc($attemptResult);
            $attemptId = $row['attempt_id'];
            $extendedTime = $row['extended_time'];
            $endTime = $row['end_time'];

            // Clear previous exam answers for the attempt
            $clearAnswersQuery = "DELETE FROM exam_answers WHERE attempt_id = ?";
            $stmt = mysqli_prepare($connection, $clearAnswersQuery);
            mysqli_stmt_bind_param($stmt, "i", $attemptId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to clear previous exam answers: ' . mysqli_error($connection));
            }

            // Clear exam attempt data, except for the extended time
            $clearAttemptQuery = "UPDATE exam_attempts SET score = 0, total_questions = 0, answered_questions = 0 WHERE attempt_id = ?";
            $stmt = mysqli_prepare($connection, $clearAttemptQuery);
            mysqli_stmt_bind_param($stmt, "i", $attemptId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to clear exam attempt data: ' . mysqli_error($connection));
            }

            // Update the extended time
            $newExtendedTime = $extendedTime + $minutes;
            $updateQuery = "UPDATE exam_attempts SET extended_time = ?, time_extended = 1 WHERE attempt_id = ?";
            $stmt = mysqli_prepare($connection, $updateQuery);
            mysqli_stmt_bind_param($stmt, "ii", $newExtendedTime, $attemptId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update extended time: ' . mysqli_error($connection));
            }

            // Update the end_time based on the additional minutes
            // $newEndTime = date('Y-m-d H:i:s', strtotime($endTime) + ($minutes * 60));
            // $updateEndTimeQuery = "UPDATE exam_attempts SET end_time = ? WHERE attempt_id = ?";
            // $stmt = mysqli_prepare($connection, $updateEndTimeQuery);
            // mysqli_stmt_bind_param($stmt, "si", $newEndTime, $attemptId);
            // if (!mysqli_stmt_execute($stmt)) {
            //     throw new Exception('Failed to update end time: ' . mysqli_error($connection));
            // }
            // Update the end_time based on the current time and the additional minutes
            $newEndTime = date('Y-m-d H:i:s', time() + ($minutes * 60));
            $updateEndTimeQuery = "UPDATE exam_attempts SET end_time = ? WHERE attempt_id = ?";
            $stmt = mysqli_prepare($connection, $updateEndTimeQuery);
            mysqli_stmt_bind_param($stmt, "si", $newEndTime, $attemptId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update end time: ' . mysqli_error($connection));
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No active exam found.']);
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
