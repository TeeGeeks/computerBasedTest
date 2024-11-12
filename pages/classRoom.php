<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

// Set the default time zone to Nigeria (WAT, UTC+1)
date_default_timezone_set('Africa/Lagos');

$connection = connection();

// Get the class_id from the query string
$classId = intval($_GET['online_class_id'] ?? 0);

// Fetch class details based on class_id
$classQuery = "
    SELECT oc.class_title, oc.class_link, oc.class_date, oc.class_time, oc.class_duration, 
           s.subject_name, oc.is_active, oc.teacher_id, oc.arm_id, oc.subject_id 
    FROM online_classes oc
    JOIN subjects s ON oc.subject_id = s.id
    WHERE oc.id = $classId";

$classResult = mysqli_query($connection, $classQuery);
$classData = mysqli_fetch_assoc($classResult);

// Check if class exists
if (!$classData) {
    showSweetAlert('error', 'Error', 'Class not found.', 'viewOnlineClasses.php');
    exit;
}

// Check user role
$userRole = $_SESSION['user_role'];
$isAuthorizedToStart = in_array($userRole, ['staff', 'admin']);

// Get current date and time in Nigerian time
$currentDate = date('Y-m-d');
$currentTime = date('h:i:s A'); // Change to 12-hour format with AM/PM
$canStartClass = ($currentDate === $classData['class_date'] && $currentTime >= date('h:i:s A', strtotime($classData['class_time'])));

// Determine expiration time and format correctly
$expirationTime = strtotime($classData['class_date'] . ' ' . $classData['class_time']) + ($classData['class_duration'] * 60);
$hasExpired = time() > $expirationTime;

// Check if class has expired first
if ($hasExpired) {
    // Update class status to inactive
    $updateQuery = "UPDATE online_classes SET is_active = 0 WHERE id = $classId";
    mysqli_query($connection, $updateQuery);
    showSweetAlert('info', 'Class Expired', 'The class has expired and is no longer accessible.', "manageOnlineClass.php");
    exit;
}

// Notify about class expiration (5-minute warning)
if ($expirationTime - time() < 300 && $expirationTime - time() > 0) { // Notify 5 minutes before expiration
    echo "<script>alert('Your class will end in " . intval(($expirationTime - time()) / 60) . " minutes.');</script>";
}

// Handle class starting action for authorized roles only
if ($isAuthorizedToStart && !$classData['is_active'] && $canStartClass) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_class'])) {
        // Update the class to set it as active
        $updateQuery = "UPDATE online_classes SET is_active = 1 WHERE id = $classId";
        mysqli_query($connection, $updateQuery);
        showSweetAlert('success', 'Success', 'Class has started!', "classRoom.php?online_class_id=$classId");
    }
}

// Handle lesson note upload and save to database
// Handle lesson note upload and save to database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['lesson_note'])) {
    // Get the title and content from POST data
    $noteTitle = mysqli_real_escape_string($connection, $_POST['noteTitle']);
    $noteContent = mysqli_real_escape_string($connection, $_POST['noteContent']);
    $attachment = $_FILES['lesson_note'];

    // Handle file upload if provided
    if ($attachment['name']) {
        $uploadDir = '../uploads/notes/';
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        // Check if the uploaded file type is allowed
        if (!in_array($attachment['type'], $allowedTypes)) {
            showSweetAlert('warning', 'Invalid File Type', 'Only PDF, JPG, and PNG files are allowed.', 'addLessonNote.php');
            exit;
        }

        // Create a unique file name for the uploaded file
        $attachmentPath = $uploadDir . uniqid() . '-' . basename($attachment['name']);

        // Move uploaded file to the designated directory
        if (move_uploaded_file($attachment['tmp_name'], $attachmentPath)) {
            // Check for duplicates before inserting
            $duplicateCheckQuery = "
                SELECT COUNT(*) as count 
                FROM lesson_notes 
                WHERE class_id = $classId 
                AND arm_id = {$classData['arm_id']} 
                AND subject_id = {$classData['subject_id']} 
                AND note_title = '$noteTitle'";

            $duplicateResult = mysqli_query($connection, $duplicateCheckQuery);
            $duplicateData = mysqli_fetch_assoc($duplicateResult);

            if ($duplicateData['count'] > 0) {
                showSweetAlert('error', 'Duplicate Entry', 'A lesson note with this title already exists for the selected class, arm, and subject.');
                exit;
            }

            // Prepare the insert values
            $teacherId = null; // Initialize teacherId as NULL

            // Check user role and set teacher_id accordingly
            if ($userRole === 'admin') {
                // Admin can have a NULL teacher_id
                $teacherId = 'NULL';
            } elseif (!empty($classData['teacher_id'])) {
                // If user is a teacher and teacher_id is available, use that
                $teacherId = intval($classData['teacher_id']);
            }

            // Prepare the insert query
            $insertNoteQuery = "INSERT INTO lesson_notes 
                    (class_id, arm_id, subject_id, teacher_id, note_title, note_content, attachment_path) 
                    VALUES 
                    ($classId, {$classData['arm_id']}, {$classData['subject_id']}, $teacherId, 
                    '$noteTitle', '$noteContent', '$attachmentPath')";

            // Execute the insert query
            if (!mysqli_query($connection, $insertNoteQuery)) {
                // Print the error for debugging
                echo "Error: " . mysqli_error($connection);
                exit;
            } else {
                showSweetAlert('success', 'Success', 'Lesson note uploaded successfully.');
            }
        } else {
            showSweetAlert('error', 'Upload Error', 'Failed to upload the file. Please try again.');
            exit;
        }
    } else {
        showSweetAlert('error', 'No File', 'No file was uploaded. Please select a file to upload.');
        exit;
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = mysqli_real_escape_string($connection, $_POST['comment']);

    // Insert the new comment into the database
    $insertCommentQuery = "INSERT INTO comments (online_class_id, student_id, comment) VALUES ($classId, {$_SESSION['student_id']}, '$comment')";
    if (mysqli_query($connection, $insertCommentQuery)) {
        showSweetAlert('success', 'Success', 'Comment added successfully!');
    } else {
        showSweetAlert('error', 'Error', 'Failed to add comment.');
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = mysqli_real_escape_string($connection, $_POST['comment']);

    // Insert the new comment into the database
    $insertCommentQuery = "INSERT INTO comments (online_class_id, student_id, comment) VALUES ($classId, {$_SESSION['student_id']}, '$comment')";
    if (mysqli_query($connection, $insertCommentQuery)) {
        showSweetAlert('success', 'Success', 'Comment added successfully!');
    } else {
        showSweetAlert('error', 'Error', 'Failed to add comment.');
    }
}

// Handle reply submission (update existing comment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply']) && isset($_POST['reply_to'])) {
    $reply = mysqli_real_escape_string($connection, $_POST['reply']);
    $replyTo = intval($_POST['reply_to']); // Original comment ID

    // Update the existing comment with the reply
    // Here we assume that the reply will be appended to the existing comment
    $updateReplyQuery = "UPDATE comments SET reply = '$reply' WHERE id = $replyTo";
    if (mysqli_query($connection, $updateReplyQuery)) {
        showSweetAlert('success', 'Success', 'Reply added successfully!');
    } else {
        showSweetAlert('error', 'Error', 'Failed to add reply.');
    }
}

// Fetch comments along with replies
$commentsQuery = "
    SELECT c.id, c.comment, s.username AS student_name, c.created_at, c.reply_to 
    FROM comments c
    JOIN students s ON c.student_id = s.id
    WHERE c.online_class_id = $classId
    ORDER BY c.created_at DESC";

$commentsResult = mysqli_query($connection, $commentsQuery);


?>





<style>
    body {
        background-color: #f0f2f5;
        font-family: Arial, sans-serif;
    }

    .container {
        margin: 30px auto;
        max-width: 1200px;
        background: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h4 {
        font-size: 24px;
        margin-bottom: 15px;
        color: #333;
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

    .qa-box,
    .notepad-container textarea {
        width: 100%;
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 10px;
        resize: none;
        font-size: 16px;
    }

    .question-input,
    #postQuestion {
        margin-top: 10px;
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

    .d-grid .btn {
        border-radius: 0.375rem;
    }

    .comments-section {
        margin-top: 20px;
        padding: 15px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        background: #f9f9f9;
    }

    .comment {
        margin-bottom: 15px;
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .comment:last-child {
        border-bottom: none;
        /* Remove border for the last comment */
    }

    .comment strong {
        color: #007bff;
        /* Student name color */
    }

    .comment p {
        margin: 5px 0;
        /* Space between comment text */
        color: #333;
        /* Comment text color */
    }

    .comment small {
        color: #6c757d;
        /* Muted color for timestamp */
    }

    .reply-form {
        margin-top: 10px;
    }

    .reply {
        margin-left: 30px;
        /* Indentation for replies */
        padding: 8px 10px;
        border: 1px solid #007bff;
        border-radius: 5px;
        background: #e9f7fe;
        /* Light blue background for replies */
    }

    .reply strong {
        color: #0056b3;
        /* Replying teacher's name color */
    }

    .reply p {
        margin: 5px 0;
        /* Space for reply text */
        color: #333;
        /* Reply text color */
    }

    textarea {
        resize: none;
        /* Disable resizing of textarea */
    }

    textarea:focus {
        border-color: #007bff;
        /* Change border color on focus */
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
        /* Add shadow on focus */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <h4 class=""><?php echo htmlspecialchars($classData['class_title']); ?></h4>
            <p class="text-muted ">
                Scheduled Date: <strong><?php echo date('F j, Y', strtotime($classData['class_date'])); ?></strong><br>
                Time: <strong><?php echo date('h:i A', strtotime($classData['class_time'])); ?></strong><br>
                Duration: <strong><?php echo htmlspecialchars($classData['class_duration']); ?> minutes</strong>
            </p>

            <!-- Show Start Class button only for authorized users and if class has not started and time has reached -->
            <?php if ($isAuthorizedToStart && !$classData['is_active'] && $canStartClass): ?>
                <form method="POST">
                    <button type="submit" name="start_class" class="btn btn-info">Start Class</button>
                </form>
            <?php elseif (!$canStartClass): ?>
                <p class="alert alert-warning">You can start the class at the scheduled time: <?php echo date('h:i A', strtotime($classData['class_time'])); ?></p>
            <?php endif; ?>

            <!-- Show Join Class button if class is active and has not expired -->
            <?php if ($classData['is_active']): ?>
                <?php if (!$hasExpired): ?>
                    <button class="btn btn-primary mb-3" onclick="openPopup('<?php echo htmlspecialchars($classData['class_link']); ?>')">Join Class</button>
                <?php else: ?>
                    <p class="alert alert-warning">The class has expired. You cannot join the class.</p>
                <?php endif; ?>
            <?php endif; ?>


            <!-- Lesson Note Upload Section -->
            <div class="resources-container">
                <form method="POST" enctype="multipart/form-data">
                    <h5>Upload Lesson Note</h5>
                    <div class="mb-3">
                        <label for="noteTitle" class="form-label">Lesson Note Title</label>
                        <input type="text" class="form-control" id="noteTitle" name="noteTitle" required>
                    </div>

                    <div class="mb-3">
                        <label for="noteContent" class="form-label">Content</label>
                        <textarea class="form-control" id="noteContent" name="noteContent" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="lesson_note" class="form-label">Upload Attachment</label>
                        <input type="file" class="form-control" id="lesson_note" name="lesson_note" accept=".pdf, image/*">
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-success" id="submitBtn">Upload</button>
                    </div>

                </form>
            </div>

            <div class="comments-section">
                <!-- Comment Submission Form -->
                <form method="POST" class="comment-form mb-3">
                    <textarea name="comment" class="form-control" placeholder="Type your comment here..." required></textarea>
                    <button type="submit" class="btn btn-primary mt-2">Submit Comment</button>
                </form>

                <?php while ($comment = mysqli_fetch_assoc($commentsResult)): ?>
                    <div class="comment">
                        <strong><?php echo htmlspecialchars($comment['student_name']); ?></strong>
                        <p><?php echo strip_tags($comment['comment']); ?></p>
                        <small class="text-muted"><?php echo date('h:i A', strtotime($comment['created_at'])); ?></small>

                        <!-- Reply Button -->
                        <button class="btn btn-link reply-button mt-2" onclick="toggleReplyForm(<?php echo $comment['id']; ?>)">Reply</button>

                        <!-- Reply Form (Initially hidden) -->
                        <form method="POST" class="reply-form mt-3" id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                            <input type="hidden" name="reply_to" value="<?php echo $comment['id']; ?>">
                            <textarea name="reply" class="form-control" placeholder="Type your reply here..." required></textarea>
                            <button class="mt-2 btn btn-info" type="submit">Submit Reply</button>
                        </form>

                        <!-- Show replies if exist -->
                        <?php
                        $replyQuery = "SELECT c.comment, c.reply, s.username AS teacher_name FROM comments c JOIN students s ON c.student_id = s.id WHERE c.reply_to = {$comment['id']}";
                        $replyResult = mysqli_query($connection, $replyQuery);
                        while ($reply = mysqli_fetch_assoc($replyResult)): ?>
                            <div class="reply">
                                <strong><?php echo htmlspecialchars($reply['teacher_name']); ?></strong>
                                <p><?php echo htmlspecialchars($reply['reply']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endwhile; ?>
            </div>




        </div>

        <footer>
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script>
        // Function to toggle the reply form visibility
        function toggleReplyForm(commentId) {
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            if (replyForm.style.display === "none" || replyForm.style.display === "") {
                replyForm.style.display = "block"; // Show the form
            } else {
                replyForm.style.display = "none"; // Hide the form
            }
        }
    </script>
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

        initializeTinyMCE('#reply', 240);
        initializeTinyMCE('#noteContent', 240);
    </script>
    <script>
        // Q&A functionality
        document.getElementById('postQuestion').addEventListener('click', function() {
            const questionInput = document.getElementById('questionInput');
            const qaBox = document.getElementById('qaBox');
            const question = questionInput.value.trim();

            if (question) {
                const questionElement = document.createElement('div');
                questionElement.innerHTML = `<strong>You:</strong> ${question}`;
                questionElement.classList.add('question');
                qaBox.appendChild(questionElement);
                questionInput.value = ''; // Clear input field
                qaBox.scrollTop = qaBox.scrollHeight; // Auto scroll to the bottom

                // Save question to the database using AJAX
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send(`comment=${encodeURIComponent(question)}`);
            }
        });
    </script>
    <script>
        function openPopup(url) {
            window.open(url, 'Google Meet', 'width=800,height=600');
        }

        // Q&A functionality
        document.getElementById('postQuestion').addEventListener('click', function() {
            const questionInput = document.getElementById('questionInput');
            const qaBox = document.getElementById('qaBox');
            const question = questionInput.value.trim();

            if (question) {
                const questionElement = document.createElement('div');
                questionElement.textContent = `Q: ${question}`;
                questionElement.classList.add('question');
                qaBox.appendChild(questionElement);
                questionInput.value = ''; // Clear input field
                qaBox.scrollTop = qaBox.scrollHeight; // Auto scroll to the bottom
            }
        });

        // Q&A functionality
        document.getElementById('postQuestion').addEventListener('click', function() {
            const questionInput = document.getElementById('questionInput');
            const qaBox = document.getElementById('qaBox');
            const question = questionInput.value.trim();

            if (question) {
                const questionElement = document.createElement('div');
                questionElement.innerHTML = `<strong>You:</strong> ${question}`;
                questionElement.classList.add('question');
                qaBox.appendChild(questionElement);
                questionInput.value = ''; // Clear input field
                qaBox.scrollTop = qaBox.scrollHeight; // Auto scroll to the bottom

                // Save question to the database using AJAX
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send(`question=${encodeURIComponent(question)}`);
            }
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