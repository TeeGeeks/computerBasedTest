<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

// Set default timezone
date_default_timezone_set('Africa/Lagos');

$connection = connection();

// Get the online_class_id from the query string
$onlineClassId = intval($_GET['online_class_id'] ?? 0);

// Fetch class details based on online_class_id
$classQuery = "
    SELECT oc.class_title, oc.class_link, oc.class_date, oc.class_time, oc.class_duration, 
           s.subject_name, oc.is_active 
    FROM online_classes oc
    JOIN subjects s ON oc.subject_id = s.id
    WHERE oc.id = $onlineClassId";

$classResult = mysqli_query($connection, $classQuery);
$classData = mysqli_fetch_assoc($classResult);

// Check if class exists
if (!$classData) {
    showSweetAlert('error', 'Error', 'Class not found.', 'takeClass.php');
    exit;
}

// Get current date and time
$currentDate = date('Y-m-d');
$currentTime = date('h:i:s A');
$expirationTime = strtotime($classData['class_date'] . ' ' . $classData['class_time']) + ($classData['class_duration'] * 60);
$hasExpired = time() > $expirationTime;

if ($hasExpired) {
    $updateQuery = "UPDATE online_classes SET is_active = 0 WHERE id = $onlineClassId";
    mysqli_query($connection, $updateQuery);
    showSweetAlert('info', 'Class Expired', 'The class has expired and is no longer accessible.', "takeClass.php");
    exit;
}

// Handle comments submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = mysqli_real_escape_string($connection, $_POST['comment']);

    // Check if the comment already exists for the student in this online class
    $checkCommentQuery = "
        SELECT COUNT(*) AS count 
        FROM comments 
        WHERE online_class_id = $onlineClassId 
          AND student_id = {$_SESSION['student_id']} 
          AND comment = '$comment'";

    $checkCommentResult = mysqli_query($connection, $checkCommentQuery);
    $checkCommentData = mysqli_fetch_assoc($checkCommentResult);

    // If no duplicate comment is found, insert the new comment
    if ($checkCommentData['count'] == 0) {
        $insertCommentQuery = "INSERT INTO comments (online_class_id, student_id, comment) VALUES ($onlineClassId, {$_SESSION['student_id']}, '$comment')";
        mysqli_query($connection, $insertCommentQuery);

        // Clear any existing comment in the session
        unset($_SESSION['comment_input']);
    } else {
        // Store the duplicate comment in session for repopulation
        $_SESSION['comment_input'] = $comment;
        showSweetAlert('info', 'Duplicate comment', 'Duplicate comment!');
        header("Location: " . $_SERVER['PHP_SELF'] . "?online_class_id=$onlineClassId"); // Redirect back
        exit;
    }
}

// Fetch comments associated with the online_class_id
$commentsQuery = "
    SELECT c.comment, s.username AS student_name, c.created_at 
    FROM comments c
    JOIN students s ON c.student_id = s.id
    WHERE c.online_class_id = $onlineClassId
    ORDER BY c.created_at DESC";

$commentsResult = mysqli_query($connection, $commentsQuery);



// Fetch resources (lesson notes)
$resourcesQuery = "SELECT * FROM lesson_notes WHERE class_id = $onlineClassId";
$resourcesResult = mysqli_query($connection, $resourcesQuery);
?>





<style>
    .comment-input {
        width: 100%;
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 10px;
        font-size: 16px;
        margin-top: 10px;
    }

    .comment-item {
        margin-top: 10px;
        padding: 5px;
        border-bottom: 1px solid #ccc;
    }

    .form-control,
    .form-select {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .resources-container,
    .qa-container,
    .notepad-container {
        margin-top: 20px;
        padding: 15px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        background: #f9f9f9;
    }

    .resources-container ul {
        list-style: none;
        padding: 0;
    }

    .resources-container ul li {
        padding: 8px 0;
    }

    .resources-container ul li a {
        color: #007bff;
        text-decoration: none;
    }

    #comments-container {
        margin-top: 20px;
        padding: 15px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        background-color: #f9f9f9;
    }

    .comments-title {
        font-size: 20px;
        margin-bottom: 15px;
        color: #333;
        font-weight: bold;
    }

    .comment-item {
        margin-bottom: 10px;
        padding: 10px;
        border-left: 4px solid #007bff;
        /* Left border for comment highlight */
        background-color: #ffffff;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .comment-item strong {
        color: #007bff;
        /* Strong name color */
    }

    .comment-text {
        margin: 5px 0;
        font-size: 16px;
        color: #555;
    }

    .comment-time {
        font-size: 12px;
        color: #999;
    }

    .no-comments {
        font-style: italic;
        color: #888;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <div class="resources-container">
                <h4><?php echo htmlspecialchars($classData['class_title']); ?></h4>
                <p>
                    Scheduled Date: <strong><?php echo date('F j, Y', strtotime($classData['class_date'])); ?></strong><br>
                    Time: <strong><?php echo date('h:i A', strtotime($classData['class_time'])); ?></strong><br>
                    Duration: <strong><?php echo htmlspecialchars($classData['class_duration']); ?> minutes</strong>
                </p>

                <?php if ($classData['is_active']): ?>
                    <?php if (!$hasExpired): ?>
                        <button class="btn btn-primary mb-3" onclick="openPopup('<?php echo htmlspecialchars($classData['class_link']); ?>')">Join Class</button>
                    <?php else: ?>
                        <p class="alert alert-warning">The class has expired. You cannot join the class.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="qa-box mt-3">
                <h5>Comments</h5>
                <form method="POST">
                    <textarea class="form-control" id="comment-input" name="comment" placeholder="Type your comment here..." required></textarea>
                    <button type="submit" class="btn btn-primary mt-3">Submit Comment</button>
                </form>

                <div id="comments-container">
                    <h5 class="comments-title">Comments</h5>
                    <?php if (mysqli_num_rows($commentsResult) > 0): ?>
                        <?php while ($comment = mysqli_fetch_assoc($commentsResult)): ?>
                            <div class="comment-item">
                                <strong><?php echo htmlspecialchars($comment['student_name']); ?>:</strong>
                                <p class="comment-text"><?php echo strip_tags($comment['comment']); ?></p>
                                <span class="comment-time"><?php echo date('F j, Y, g:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-comments">No comments yet.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <footer>
            <?php include("../includes/footer.php") ?>
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
                paste_data_images: true, // Allows pasting images from clipboard
                images_upload_url: 'upload_handler.php', // Placeholder for image upload functionality
                automatic_uploads: true, // Enables automatic uploads of media
                file_picker_types: 'image media', // Allows image and media file picking
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save(); // Auto-save content when it's changed
                    });
                }
            });
        }

        initializeTinyMCE('#comment-input', 240);
    </script>
    <script>
        function openPopup(url) {
            window.open(url, 'Google Meet', 'width=800,height=600');
        }



        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(form);

                fetch('load_qa.php?online_class_id=<?php echo $onlineClassId; ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('comments-container').innerHTML = data;
                        form.reset(); // Clear the input field
                    });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Class Room';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>

<?php
// Clear the comment input session variable after displaying the form
unset($_SESSION['comment_input']);
?>