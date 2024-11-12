<?php
include("../config.php");
include('../session.php');
include_once("../includes/generalFnc.php");

$connection = connection();

// Check if a subject ID is provided
if (isset($_GET['subjectId']) && !empty($_GET['subjectId'])) {
    $subjectId = $_GET['subjectId'];

    // Assuming the teacher's ID is stored in the session after login
    $teacherId = $_SESSION['staff_id'] ?? '';

    // Initialize the array to store exams
    $exams = [];

    // Check if the user role is not admin
    if ($_SESSION['user_role'] !== 'admin') {
        // Fetch exams for the specific subject and teacher
        $examQuery = "SELECT id, exam_title 
                      FROM theory_exams 
                      WHERE subject_id = '$subjectId' 
                      AND teacher_id = '$teacherId'";
    } else {
        // If admin, fetch all exams for the subject
        $examQuery = "SELECT id, exam_title 
                      FROM theory_exams 
                      WHERE subject_id = '$subjectId'";
    }

    $examResult = mysqli_query($connection, $examQuery);

    if ($examResult) {
        while ($row = mysqli_fetch_assoc($examResult)) {
            $exams[] = ['id' => $row['id'], 'title' => $row['exam_title']]; // Store each exam as an array
        }
    }

    // Return the exams as a JSON response
    header('Content-Type: application/json');
    echo json_encode($exams);
}

// Close the database connection
mysqli_close($connection);
