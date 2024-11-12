<?php
include("../config.php");
include('../session.php');

$connection = connection();

// Get the subject ID from the POST request
$subjectId = $_POST['subject'] ?? '';

// Fetch exams based on the subject ID and teacher ID
$teacherId = $_SESSION['staff_id'] ?? '';
$examQuery = "SELECT id, exam_title FROM exams WHERE subject_id = '$subjectId' AND teacher_id = '$teacherId'";
$examResult = mysqli_query($connection, $examQuery);

$options = '<option value="" disabled selected>Select an exam</option>';

if ($examResult) {
    while ($row = mysqli_fetch_assoc($examResult)) {
        $options .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['exam_title']) . '</option>';
    }
}

echo $options; // Output the options for the exam dropdown

// Close the database connection
mysqli_close($connection);
