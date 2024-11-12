<?php
include("../config.php");
include("../includes/header.php");
include('../session.php');
include_once("../includes/generalFnc.php");

// Database connection
$connection = connection();

// Assuming the teacher's ID is stored in the session after login
$teacherId = $_SESSION['staff_id'] ?? '';

// Fetch all questions created by the logged-in teacher that don't have images
$query = "
    SELECT q.id, q.question_text, q.question_image, e.exam_title
    FROM questions q
    INNER JOIN exams e ON q.exam_id = e.id
    WHERE q.teacher_id = '$teacherId' AND (q.question_image IS NULL OR q.question_image = '')
";

$result = mysqli_query($connection, $query);
$questions = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $questions[] = $row;
    }
}

// Handle form submission (image upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id'])) {
    $questionId = $_POST['question_id'];
    $image = $_FILES['question_image'];

    if (!empty($image['name'])) {
        // Upload directory and allowed types
        $uploadDir = '../uploads/questions/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        // Validate file type
        if (in_array($image['type'], $allowedTypes)) {
            // Generate unique file name
            $imagePath = $uploadDir . uniqid() . '-' . basename($image['name']);
            if (move_uploaded_file($image['tmp_name'], $imagePath)) {
                // Update question with image path
                $updateQuery = "UPDATE questions SET question_image = '$imagePath' WHERE id = '$questionId'";
                if (mysqli_query($connection, $updateQuery)) {
                    showSweetAlert('success', 'Image Added', 'Image has been successfully added to the question.', '../pages/addImageToQuestion.php');
                } else {
                    showSweetAlert('error', 'Update Failed', 'Failed to update question with image.', '../pages/addImageToQuestion.php');
                }
            } else {
                showSweetAlert('error', 'Upload Error', 'Failed to upload the image.', '../pages/addImageToQuestion.phpp');
            }
        } else {
            showSweetAlert('warning', 'Invalid File Type', 'Only JPG, PNG, and GIF files are allowed.', '../pages/addImageToQuestion.php');
        }
    } else {
        showSweetAlert('warning', 'No Image', 'Please select an image to upload.', '../pages/addImageToQuestion.php');
    }
}

mysqli_close($connection);
