<?php
include("../config.php");
include('../session.php');

// Set default timezone
date_default_timezone_set('Africa/Lagos');

$connection = connection();

// Get the online_class_id from the query string
$onlineClassId = intval($_GET['online_class_id'] ?? 0);

// Fetch comments associated with the online_class_id
$commentsQuery = "
    SELECT c.comment, s.username AS student_name 
    FROM comments c
    JOIN students s ON c.student_id = s.id
    WHERE c.online_class_id = $onlineClassId";

$commentsResult = mysqli_query($connection, $commentsQuery);

// Display comments
if (mysqli_num_rows($commentsResult) > 0) {
    while ($comment = mysqli_fetch_assoc($commentsResult)) {
        echo "<div class='comment-item'>";
        echo "<strong>" . htmlspecialchars($comment['student_name']) . ":</strong> ";
        echo htmlspecialchars($comment['comment']);
        echo "</div>";
    }
} else {
    echo "<p>No comments yet.</p>";
}

// Delete comments once the class has expired
$current_time = time();
$expiration_time = strtotime("+1 hour", $expiration_time); // Adjust according to your class duration
if ($current_time > $expiration_time) {
    $deleteQuery = "DELETE FROM comments WHERE online_class_id = $onlineClassId";
    mysqli_query($connection, $deleteQuery);
}
