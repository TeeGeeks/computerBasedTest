<?php
include("../config.php");
include_once("../includes/generalFnc.php");

$connection = connection();

// Retrieve the student ID from the query string
$studentId = $_GET['student_id'] ?? null;

// Check if student ID is provided
if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student ID is missing.']);
    exit;
}

// Retrieve the subject ID from the query string
$subjectId = $_GET['subject_id'] ?? null;

// Check if the subject ID is provided
if (!$subjectId) {
    echo json_encode(['success' => false, 'message' => 'Subject ID is missing.']);
    exit;
}

// Fetch class_id and arm_id for the provided student ID
$classArmQuery = "SELECT class_id, arm_id FROM students WHERE id = '$studentId'";
$classArmResult = mysqli_query($connection, $classArmQuery);

if ($classArmResult && mysqli_num_rows($classArmResult) > 0) {
    $classArmRow = mysqli_fetch_assoc($classArmResult);
    $classId = $classArmRow['class_id'];
    $armId = $classArmRow['arm_id'];

    // Debugging output
    error_log("Class ID: $classId, Arm ID: $armId, Subject ID: $subjectId");

    // Fetch assignments based on subject_id, class_id, and arm_id
    $assignmentQuery = "
        SELECT assignment_id, assignment_title 
        FROM assignments 
        WHERE subject_id = '$subjectId' 
          AND class_id = '$classId' 
          AND arm_id = '$armId'
    ";

    $assignmentResult = mysqli_query($connection, $assignmentQuery);

    if ($assignmentResult && mysqli_num_rows($assignmentResult) > 0) {
        $assignments = [];

        while ($row = mysqli_fetch_assoc($assignmentResult)) {
            $assignments[] = [
                'assignment_id' => $row['assignment_id'],
                'assignment_title' => $row['assignment_title']
            ];
        }

        // Return the assignments in JSON format
        echo json_encode(['success' => true, 'assignments' => $assignments]);
    } else {
        // Handle case where no assignments are found
        echo json_encode(['success' => false, 'message' => 'No assignments found for the selected subject.']);
    }
} else {
    // Handle case where class or arm is not found
    echo json_encode(['success' => false, 'message' => 'Student class or arm not found.']);
}

// Close the database connection
mysqli_close($connection);
