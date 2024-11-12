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
$arms = [];

// Fetch subjects based on role
$subjectQuery = "SELECT id, subject_name FROM subjects";
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

// Fetch lesson note details for editing
$noteId = $_GET['id'] ?? null;
$lessonNote = null;

if ($noteId) {
    $noteQuery = "SELECT * FROM lesson_notes WHERE id = '$noteId'";
    $noteResult = mysqli_query($connection, $noteQuery);
    if ($noteResult && mysqli_num_rows($noteResult) > 0) {
        $lessonNote = mysqli_fetch_assoc($noteResult);
    } else {
        showSweetAlert('error', 'Note Not Found', 'The lesson note does not exist.', 'manageLessonNotes.php');
        exit;
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
    $attachmentPath = $lessonNote['attachment_path']; // Keep existing attachment unless a new one is uploaded

    if (empty($subject_id) || empty($class_id) || empty($noteTitle) || empty($noteContent)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'editLessonNote.php?id=' . $noteId);
        exit;
    }

    // Check if the teacher is allowed to edit notes for the subject
    if (!$isAdmin) {
        $assignmentCheckQuery = "SELECT * FROM subject_assignments WHERE teacher_id = '$teacherId' AND subject_id = '$subject_id'";
        $assignmentCheckResult = mysqli_query($connection, $assignmentCheckQuery);
        if (mysqli_num_rows($assignmentCheckResult) === 0) {
            showSweetAlert('warning', 'Unauthorized', 'You are not assigned to this subject.', 'editLessonNote.php?id=' . $noteId);
            exit;
        }
    }

    // Check for duplicates before updating
    $duplicateCheckQuery = "
        SELECT COUNT(*) as count 
        FROM lesson_notes 
        WHERE subject_id = '$subject_id' 
        AND class_id = '$class_id' 
        AND arm_id = '$arm_id' 
        AND note_title = '$noteTitle' 
        AND id != '$noteId'";  // Exclude the current note being edited

    $duplicateResult = mysqli_query($connection, $duplicateCheckQuery);
    $duplicateData = mysqli_fetch_assoc($duplicateResult);

    if ($duplicateData['count'] > 0) {
        showSweetAlert('error', 'Duplicate Entry', 'A lesson note with this title already exists for the selected subject, class, and arm.', 'editLessonNote.php?id=' . $noteId);
        exit;
    }

    // Handle file upload if provided
    if ($attachment['name']) {
        $uploadDir = '../uploads/notes/';
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($attachment['type'], $allowedTypes)) {
            showSweetAlert('warning', 'Invalid File Type', 'Only PDF, JPG, and PNG files are allowed.', 'editLessonNote.php?id=' . $noteId);
            exit;
        }
        $attachmentPath = $uploadDir . uniqid() . '-' . basename($attachment['name']);
        if (!move_uploaded_file($attachment['tmp_name'], $attachmentPath)) {
            showSweetAlert('error', 'Upload Error', 'Failed to upload the file. Please try again.', 'editLessonNote.php?id=' . $noteId);
            exit;
        }
    }

    // Construct the update query
    $updateQuery = "UPDATE lesson_notes SET 
                    subject_id = '$subject_id', 
                    class_id = '$class_id', 
                    arm_id = '$arm_id', 
                    note_title = '$noteTitle', 
                    note_content = '$noteContent', 
                    attachment_path = '$attachmentPath' 
                    WHERE id = '$noteId'";

    // Execute the query and handle success or error
    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Lesson note updated successfully.', 'lessonNotes.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editLessonNote.php?id=' . $noteId);
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

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4">Edit Lesson Note</h4>
                <form id="editLessonNoteForm" action="" method="POST" enctype="multipart/form-data" onsubmit="showSpinner()">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled>Select a subject</option>
                                <?php foreach ($subjects as $subject) {
                                    echo '<option value="' . htmlspecialchars($subject['id']) . '" ' . ($lessonNote['subject_id'] == $subject['id'] ? 'selected' : '') . '>' . htmlspecialchars($subject['subject_name']) . '</option>';
                                } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled>Select a class</option>
                                <?php foreach ($classes as $class) {
                                    echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . ($lessonNote['class_id'] == $class['class_id'] ? 'selected' : '') . '>' . htmlspecialchars($class['class_name']) . '</option>';
                                } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled>Select an arm</option>
                                <?php foreach ($arms as $arm) {
                                    echo '<option value="' . htmlspecialchars($arm['arm_id']) . '" ' . ($lessonNote['arm_id'] == $arm['arm_id'] ? 'selected' : '') . '>' . htmlspecialchars($arm['arm_name']) . '</option>';
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="noteTitle" class="form-label">Lesson Note Title</label>
                        <input type="text" class="form-control" id="noteTitle" name="noteTitle" value="<?php echo htmlspecialchars($lessonNote['note_title']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="noteContent" class="form-label">Content</label>
                        <textarea class="form-control" id="noteContent" name="noteContent" rows="4" required><?php echo htmlspecialchars($lessonNote['note_content']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="noteAttachment" class="form-label">Upload Attachment (Optional)</label>
                        <input type="file" class="form-control" id="noteAttachment" name="noteAttachment" accept=".pdf, image/*">
                        <?php if (!empty($lessonNote['attachment_path'])): ?>
                            <small>Current Attachment: <a href="<?php echo htmlspecialchars($lessonNote['attachment_path']); ?>" target="_blank">View</a></small>
                        <?php endif; ?>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Update Lesson Note</button>
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
                paste_data_images: true,
                images_upload_url: 'upload_handler.php',
                automatic_uploads: true,
                file_picker_types: 'image media',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });
        }

        initializeTinyMCE('#noteContent', 240);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Lesson Note';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>