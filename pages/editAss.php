<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get the assignment ID from the query string or set a default
$assignmentId = $_GET['id'] ?? null;

if (!$assignmentId) {
    showSweetAlert('error', 'Invalid Request', 'Assignment ID is required.', 'manageAss.php');
    exit;
}

// Initialize arrays to hold classes, arms, and subjects
$classes = [];
$arms = [];
$subjects = [];

// Fetch all classes
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

// Fetch all arms
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row;
    }
}

// Fetch assignment details to pre-fill the form
$assignmentQuery = "SELECT * FROM assignments WHERE assignment_id = '$assignmentId'";
$assignmentResult = mysqli_query($connection, $assignmentQuery);
if (!$assignmentResult || mysqli_num_rows($assignmentResult) == 0) {
    showSweetAlert('error', 'Assignment Not Found', 'Could not find the specified assignment.', 'manageAss.php');
    exit;
}
$assignment = mysqli_fetch_assoc($assignmentResult);

// Assuming the teacher's ID is stored in the session after login
$teacherId = $_SESSION['staff_id'] ?? null; // Use null for admin if needed

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin'; // Assume you have a 'role' field in the session

// Fetch subjects based on role
if ($isAdmin) {
    // Fetch all subjects if the user is an admin
    $subjectQuery = "SELECT id, subject_name FROM subjects";
} else {
    // Fetch only subjects assigned to the logged-in teacher
    $subjectQuery = "
                    SELECT s.id, s.subject_name 
                    FROM subjects s
                    JOIN subject_assignments sa ON s.id = sa.subject_id 
                    JOIN arms a ON a.arm_id = sa.arm_id
                    WHERE sa.teacher_id = '$teacherId'";
}

$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row;
    }
}

// Update assignment if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = trim($_POST['class']);
    $arm_id = trim($_POST['arm']);
    $subject_id = trim($_POST['subject']);
    $assignmentTitle = trim($_POST['assignmentTitle']);
    $assignmentDescription = trim($_POST['assignmentDescription']);
    $dueDate = trim($_POST['dueDate']);

    // Escape inputs to prevent SQL injection
    $class_id = mysqli_real_escape_string($connection, $class_id);
    $arm_id = mysqli_real_escape_string($connection, $arm_id);
    $subject_id = mysqli_real_escape_string($connection, $subject_id);
    $assignmentTitle = mysqli_real_escape_string($connection, $assignmentTitle);
    $assignmentDescription = mysqli_real_escape_string($connection, $assignmentDescription);
    $dueDate = mysqli_real_escape_string($connection, $dueDate);

    // File upload logic (same as before)
    $uploadDirectory = "../uploads/assignments/"; // Set upload directory
    $fileName = $_FILES['assignmentFile']['name'];
    $fileTmpName = $_FILES['assignmentFile']['tmp_name'];
    $fileSize = $_FILES['assignmentFile']['size'];
    $fileError = $_FILES['assignmentFile']['error'];
    $fileType = $_FILES['assignmentFile']['type'];

    // Validate if a file is uploaded
    $filePath = $assignment['file_path']; // Default file path to the existing file path

    if (!empty($fileName)) {
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png', 'txt']; // Define allowed file types

        if (in_array(strtolower($fileExt), $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 5000000) { // Limit file size to 5MB
                    $newFileName = uniqid('', true) . "." . $fileExt;
                    $fileDestination = $uploadDirectory . $newFileName;

                    // Move file to the uploads directory
                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        $filePath = $fileDestination; // Store the new file path
                    } else {
                        showSweetAlert('error', 'File Upload Failed', 'There was an error uploading the file.', 'editAssignment.php?id=' . $assignmentId);
                        exit;
                    }
                } else {
                    showSweetAlert('error', 'File Too Large', 'File size exceeds the limit of 5MB.', 'editAssignment.php?id=' . $assignmentId);
                    exit;
                }
            } else {
                showSweetAlert('error', 'File Error', 'There was an error with the file upload.', 'editAssignment.php?id=' . $assignmentId);
                exit;
            }
        } else {
            showSweetAlert('error', 'Invalid File Type', 'Allowed file types: pdf, doc, docx, jpg, png, txt.', 'editAssignment.php?id=' . $assignmentId);
            exit;
        }
    }

    // Validate required fields
    if (empty($class_id) || empty($arm_id) || empty($subject_id) || empty($assignmentTitle) || empty($assignmentDescription) || empty($dueDate)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'editAssignment.php?id=' . $assignmentId);
        exit;
    }

    // Check if the teacher is assigned to the selected subject for the specified class and arm
    if (!$isAdmin) {
        $assignmentCheckQuery = "
            SELECT 1 FROM subject_assignments 
            WHERE teacher_id = '$teacherId' 
              AND subject_id = '$subject_id' 
              AND class_id = '$class_id' 
              AND arm_id = '$arm_id'";

        $assignmentCheckResult = mysqli_query($connection, $assignmentCheckQuery);
        if (!$assignmentCheckResult || mysqli_num_rows($assignmentCheckResult) == 0) {
            showSweetAlert('error', 'Unauthorized', 'You are not authorized to edit an assignment for this class, arm, and subject.', 'manageAss.php');
            exit;
        }
    }


    // Update query for the assignment
    if ($isAdmin) {
        // Admin can update the assignment without teacher_id
        $updateQuery = "UPDATE assignments 
                        SET class_id = '$class_id', arm_id = '$arm_id', subject_id = '$subject_id', 
                            assignment_title = '$assignmentTitle', assignment_description = '$assignmentDescription', 
                            due_date = '$dueDate', file_path = '$filePath' 
                        WHERE assignment_id = '$assignmentId'";
    } else {
        // Teacher should include their ID in the update
        $updateQuery = "UPDATE assignments 
                        SET class_id = '$class_id', arm_id = '$arm_id', subject_id = '$subject_id', 
                            assignment_title = '$assignmentTitle', assignment_description = '$assignmentDescription', 
                            due_date = '$dueDate', file_path = '$filePath', teacher_id = '$teacherId' 
                        WHERE assignment_id = '$assignmentId'";
    }

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Assignment updated successfully.', 'manageAss.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editAssignment.php?id=' . $assignmentId);
    }
}

// Close the database connection
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

    #previewContainer {
        position: relative;
        max-width: 300px;
        margin-top: 10px;
    }

    #filePreview {
        width: 100%;
        height: auto;
        display: block;
        border: 2px solid #ced4da;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        object-fit: contain;
        padding: 5px;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-8 col-lg-5 col-12">
                <h4 class="text-center mb-4">Edit Assignment</h4>
                <form action="editAss.php?id=<?php echo htmlspecialchars($assignmentId); ?>" method="POST" enctype="multipart/form-data">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled>Select a class</option>
                                <?php
                                // Populate the class options with the selected class
                                foreach ($classes as $class) {
                                    $selected = $assignment['class_id'] == $class['class_id'] ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . $selected . '>' . htmlspecialchars($class['class_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled>Select an arm</option>
                                <?php
                                // Populate the arm options with the selected arm
                                foreach ($arms as $arm) {
                                    $selected = $assignment['arm_id'] == $arm['arm_id'] ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($arm['arm_id']) . '" ' . $selected . '>' . htmlspecialchars($arm['arm_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled>Select a subject</option>
                                <?php
                                // Populate the subject options with the selected subject
                                foreach ($subjects as $subject) {
                                    $selected = $assignment['subject_id'] == $subject['id'] ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($subject['id']) . '" ' . $selected . '>' . htmlspecialchars($subject['subject_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="assignmentTitle" class="form-label">Assignment Title</label>
                            <input type="text" class="form-control" id="assignmentTitle" name="assignmentTitle" value="<?php echo htmlspecialchars($assignment['assignment_title']); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="dueDate" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="dueDate" name="dueDate" value="<?php echo htmlspecialchars($assignment['due_date']); ?>" required>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label for="assignmentDescription" class="form-label">Assignment Description</label>
                        <textarea class="form-control" id="assignmentDescription" name="assignmentDescription" rows="5" required><?php echo htmlspecialchars($assignment['assignment_description']); ?></textarea>
                    </div>



                    <div class="mb-3">
                        <label for="assignmentFile" class="form-label">Assignment File</label>
                        <?php if (!empty($assignment['file_path'])): ?>
                            <p>Current file: <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank">View file</a></p>
                        <?php endif; ?>
                        <input class="form-control" type="file" id="assignmentFile" name="assignmentFile">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Assignment</button>
                </form>

            </div>
        </div>
        <?php include("../includes/footer.php"); ?>
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

        initializeTinyMCE('#assignmentDescription', 240);
    </script>

    <script>
        function previewFile() {
            const fileInput = document.getElementById('assignmentFile');
            const filePreview = document.getElementById('filePreview');

            const file = fileInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    filePreview.src = event.target.result;
                    filePreview.style.display = 'block'; // Show the image
                }
                reader.readAsDataURL(file); // Convert file to base64 string
            } else {
                filePreview.style.display = 'none'; // Hide if no file is selected
            }
        }
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Assignment'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>