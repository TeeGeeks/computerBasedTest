<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$teacherId = $_SESSION['staff_id'] ?? null;
$isAdmin = $_SESSION['user_role'] === 'admin';

$subjects = [];
$classes = [];

// Fetch subjects based on role
$subjectQuery = "SELECT * FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row;
    }
}

// Fetch classes
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

// Fetch arms
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
$arms = [];
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = trim($_POST['subject']);
    $class_id = trim($_POST['class']);
    $arm_id = trim($_POST['arm']);
    $noteTitle = trim($_POST['noteTitle']);
    $noteContent = trim($_POST['noteContent']);
    $attachment = $_FILES['noteAttachment'];
    $attachmentPath = '';

    if (empty($subject_id) || empty($class_id) || empty($noteTitle) || empty($noteContent)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'addLessonNote.php');
        exit;
    }

    // Check if the teacher is allowed to add notes for the subject
    if (!$isAdmin) {
        $assignmentCheckQuery = "SELECT * FROM subject_assignments WHERE teacher_id = '$teacherId' AND subject_id = '$subject_id'";
        $assignmentCheckResult = mysqli_query($connection, $assignmentCheckQuery);

        if (mysqli_num_rows($assignmentCheckResult) === 0) {
            showSweetAlert('warning', 'Unauthorized', 'You are not assigned to this subject.', 'addLessonNote.php');
            exit;
        }
    }

    // Check for duplicates before inserting
    $duplicateCheckQuery = "
        SELECT COUNT(*) as count 
        FROM lesson_notes 
        WHERE subject_id = '$subject_id' 
        AND class_id = '$class_id' 
        AND arm_id = '$arm_id' 
        AND note_title = '$noteTitle'";

    $duplicateResult = mysqli_query($connection, $duplicateCheckQuery);
    $duplicateData = mysqli_fetch_assoc($duplicateResult);

    if ($duplicateData['count'] > 0) {
        showSweetAlert('error', 'Duplicate Entry', 'A lesson note with this title already exists for the selected subject, class, and arm.', 'addLessonNote.php');
        exit;
    }

    // Handle file upload if provided
    if ($attachment['name']) {
        $uploadDir = '../uploads/notes/';
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($attachment['type'], $allowedTypes)) {
            showSweetAlert('warning', 'Invalid File Type', 'Only PDF, JPG, and PNG files are allowed.', 'addLessonNote.php');
            exit;
        }
        $attachmentPath = $uploadDir . uniqid() . '-' . basename($attachment['name']);
        if (!move_uploaded_file($attachment['tmp_name'], $attachmentPath)) {
            showSweetAlert('error', 'Upload Error', 'Failed to upload the file. Please try again.', 'addLessonNote.php');
            exit;
        }
    }

    // Construct the insert query
    if ($isAdmin) {
        $insertQuery = "INSERT INTO lesson_notes (subject_id, class_id, arm_id, note_title, note_content, attachment_path) 
                        VALUES ('$subject_id', '$class_id', '$arm_id', '$noteTitle', '$noteContent', '$attachmentPath')";
    } else {
        $insertQuery = "INSERT INTO lesson_notes (subject_id, class_id, arm_id, note_title, note_content, teacher_id, attachment_path) 
                        VALUES ('$subject_id', '$class_id', '$arm_id', '$noteTitle', '$noteContent', '$teacherId', '$attachmentPath')";
    }

    // Execute the query and handle success or error
    if (mysqli_query($connection, $insertQuery)) {
        showSweetAlert('success', 'Success!', 'Lesson note added successfully.', 'addLessonNote.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addLessonNote.php');
    }
}


mysqli_close($connection);
?>





<style>
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
</style>

<!-- TinyMCE Script -->
<!-- <script src="https://cdn.tiny.cloud/1/k014ifj5itll2amihajv2u2mwio7wbnpg1g0yg2fmr1f3m8u/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#questionText',
        plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
        toolbar_mode: 'floating',
        setup: function(editor) {
            editor.on('change', function() {
                editor.save(); // This saves the content back to the <textarea>
            });
        }
    });
</script> -->

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4">Add Lesson Note</h4>
                <form id="addLessonNoteForm" action="" method="POST" enctype="multipart/form-data" onsubmit="showSpinner()">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled selected>Select a subject</option>
                                <?php foreach ($subjects as $subject) {
                                    echo '<option value="' . htmlspecialchars($subject['id']) . '">' . htmlspecialchars($subject['subject_name']) . '</option>';
                                } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php foreach ($classes as $class) {
                                    echo '<option value="' . htmlspecialchars($class['class_id']) . '">' . htmlspecialchars($class['class_name']) . '</option>';
                                } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select an arm</option>
                                <?php foreach ($arms as $arm) {
                                    echo '<option value="' . htmlspecialchars($arm['arm_id']) . '">' . htmlspecialchars($arm['arm_name']) . '</option>';
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="noteTitle" class="form-label">Lesson Note Title</label>
                        <input type="text" class="form-control" id="noteTitle" name="noteTitle" required>
                    </div>

                    <div class="mb-3">
                        <label for="noteContent" class="form-label">Content</label>
                        <textarea class="form-control" id="noteContent" name="noteContent" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="noteAttachment" class="form-label">Upload Attachment</label>
                        <input type="file" class="form-control" id="noteAttachment" name="noteAttachment" accept=".pdf, image/*">
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Add Lesson Note</button>
                    </div>
                </form>
            </div>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        function showSpinner() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            `;
            submitBtn.disabled = true;
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

        initializeTinyMCE('#noteContent', 240);
    </script>

    <script>
        function showSpinner() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`;
            submitBtn.disabled = true;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Lesson Note';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>