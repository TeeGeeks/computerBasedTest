<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

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

// Add new assignment if form is submitted
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

    // Validate required fields
    if (empty($class_id) || empty($arm_id) || empty($subject_id) || empty($assignmentTitle) || empty($assignmentDescription) || empty($dueDate)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'addAssignment.php');
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
            showSweetAlert('error', 'Unauthorized', 'You are not authorized to create an assignment for this class, arm, and subject.', 'addAssignment.php');
            exit;
        }
    }

    // File upload logic
    $uploadDirectory = "../uploads/assignments/"; // Set upload directory
    $fileName = $_FILES['assignmentFile']['name'];
    $fileTmpName = $_FILES['assignmentFile']['tmp_name'];
    $fileSize = $_FILES['assignmentFile']['size'];
    $fileError = $_FILES['assignmentFile']['error'];
    $fileType = $_FILES['assignmentFile']['type'];

    // Validate if a file is uploaded
    $filePath = null; // Default file path is null

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
                        $filePath = $fileDestination; // Store the file path
                    } else {
                        showSweetAlert('error', 'File Upload Failed', 'There was an error uploading the file.', 'addAssignment.php');
                        exit;
                    }
                } else {
                    showSweetAlert('error', 'File Too Large', 'File size exceeds the limit of 5MB.', 'addAssignment.php');
                    exit;
                }
            } else {
                showSweetAlert('error', 'File Error', 'There was an error with the file upload.', 'addAssignment.php');
                exit;
            }
        } else {
            showSweetAlert('error', 'Invalid File Type', 'Allowed file types: pdf, doc, docx, jpg, png, txt.', 'addAssignment.php');
            exit;
        }
    }


    // Update query for the assignment
    if ($isAdmin) {
        // Admin can update the assignment without teacher_id
        // Insert query for the new assignment
        $insertQuery = "INSERT INTO assignments (class_id, arm_id, subject_id, assignment_title, assignment_description, due_date, file_path) 
                    VALUES ('$class_id', '$arm_id', '$subject_id', '$assignmentTitle', '$assignmentDescription', '$dueDate', '$filePath')";
    } else {
        // Teacher should include their ID in the update
        // Insert query for the new assignment
        $insertQuery = "INSERT INTO assignments (class_id, arm_id, subject_id, assignment_title, assignment_description, due_date, file_path, teacher_id) 
     VALUES ('$class_id', '$arm_id', '$subject_id', '$assignmentTitle', '$assignmentDescription', '$dueDate', '$filePath', '$teacherId')";
    }
    if (mysqli_query($connection, $insertQuery)) {
        showSweetAlert('success', 'Success!', 'Assignment added successfully.', 'manageAss.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addAssignment.php');
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
        /* You can adjust this based on the desired max width */
        margin-top: 10px;
    }

    #filePreview {
        width: 100%;
        height: auto;
        display: block;
        border: 2px solid #ced4da;
        /* Border around the image */
        border-radius: 10px;
        /* Rounded corners */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Subtle shadow for better visibility */
        object-fit: contain;
        /* Ensures the image is contained without distortion */
        padding: 5px;
        /* Adds padding inside the border */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4">Add Assignment</h4>
                <!-- Ensure 'action' is correct and the form uses enctype for file uploads -->
                <form action="addAssignment.php" method="POST" enctype="multipart/form-data">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                // Populate the class options
                                if (!empty($classes)) {
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class['class_id']) . '">' . htmlspecialchars($class['class_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No classes available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select an arm</option>
                                <?php
                                // Populate the arm options
                                if (!empty($arms)) {
                                    foreach ($arms as $arm) {
                                        echo '<option value="' . htmlspecialchars($arm['arm_id']) . '">' . htmlspecialchars($arm['arm_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No arms available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled selected>Select a subject</option>
                                <?php
                                // Populate the subject options correctly
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '">' . htmlspecialchars($subject['subject_name']) . '</option>';  // Correct field name
                                    }
                                } else {
                                    echo '<option value="" disabled>No subjects available</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="assignmentTitle" class="form-label">Assignment Title</label>
                            <input type="text" class="form-control" id="assignmentTitle" name="assignmentTitle" required>
                        </div>


                        <div class="mb-3 col-md-6">
                            <label for="dueDate" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="dueDate" name="dueDate" required>
                        </div>

                    </div>



                    <div class="mb-3">
                        <label for="assignmentDescription" class="form-label">Assignment Description</label>
                        <textarea class="form-control" id="assignmentDescription" rows="4" name="assignmentDescription"></textarea>
                    </div>

                    <div class="mb-3 ">
                        <label for="assignmentFile" class="form-label">Assignment Attachment / File</label>
                        <input type="file" class="form-control" id="assignmentFile" name="assignmentFile" accept="image/*" onchange="previewFile()">
                        <div id="previewContainer" class="mt-3">
                            <img id="filePreview" src="#" alt="File Preview" style="display: none;">
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Add Assignment
                        </button>
                    </div>
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