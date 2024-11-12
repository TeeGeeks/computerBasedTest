<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$studentId = $_SESSION['student_id'] ?? null;
$submissionId = $_GET['submission_id'] ?? null; // Get submission ID from URL
$subjects = [];
$submissionData = [];

// Fetch the existing submission data
if ($submissionId) {
    $submissionQuery = "SELECT * FROM assignment_submissions WHERE submission_id = '$submissionId' AND student_id = '$studentId'";
    $submissionResult = mysqli_query($connection, $submissionQuery);

    if ($submissionResult && mysqli_num_rows($submissionResult) > 0) {
        $submissionData = mysqli_fetch_assoc($submissionResult);
    } else {
        echo "Submission not found.";
        exit;
    }
}

// Fetch subjects based on student's class and arm
if ($studentId) {
    // Query to get the class and arm for the student
    $classArmQuery = "SELECT class_id, arm_id FROM students WHERE id = '$studentId'";
    $classArmResult = mysqli_query($connection, $classArmQuery);

    if ($classArmResult && mysqli_num_rows($classArmResult) > 0) {
        $classArmRow = mysqli_fetch_assoc($classArmResult);
        $classId = $classArmRow['class_id'];
        $armId = $classArmRow['arm_id'];

        // Query to get subjects allocated to the student's class and arm
        $subjectQuery = "
            SELECT s.id, s.subject_name 
            FROM subject_assignments sa
            JOIN subjects s ON sa.subject_id = s.id
            WHERE sa.class_id = '$classId' 
              AND sa.arm_id = '$armId'
        ";
        $subjectResult = mysqli_query($connection, $subjectQuery);

        if ($subjectResult && mysqli_num_rows($subjectResult) > 0) {
            while ($row = mysqli_fetch_assoc($subjectResult)) {
                $subjects[] = $row; // Store each subject in the array
            }
        }
    }
} else {
    echo "Student not logged in.";
}

// Handle form submission for editing assignment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateAssignment'])) {
    $assignmentId = trim($_POST['assignment']);
    $subjectId = trim($_POST['subject']);
    $studentComments = trim($_POST['studentComments']); // Capture comments

    $studentComments = mysqli_real_escape_string($connection, $studentComments);

    // File upload logic
    $uploadDirectory = "../uploads/assignments/student_submissions/";
    $filePath = $submissionData['file_path']; // Retain existing file path unless a new file is uploaded

    if (!empty($_FILES['assignmentFile']['name'])) {
        $fileName = $_FILES['assignmentFile']['name'];
        $fileTmpName = $_FILES['assignmentFile']['tmp_name'];
        $fileSize = $_FILES['assignmentFile']['size'];
        $fileError = $_FILES['assignmentFile']['error'];
        $fileType = $_FILES['assignmentFile']['type'];

        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png', 'txt'];

        if (in_array(strtolower($fileExt), $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 5000000) {
                    $newFileName = uniqid('', true) . "." . $fileExt;
                    $fileDestination = $uploadDirectory . $newFileName;

                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        $filePath = $fileDestination; // Update file path if new file uploaded
                    } else {
                        showSweetAlert('error', 'Error!', 'There was an error uploading the file.');
                        exit;
                    }
                } else {
                    showSweetAlert('error', 'Error!', 'File size exceeds the limit of 5MB.');
                    exit;
                }
            } else {
                showSweetAlert('error', 'Error!', 'There was an error with the file upload.');
                exit;
            }
        } else {
            showSweetAlert('error', 'Error!', 'Invalid file type. Allowed types: pdf, doc, docx, jpg, png, txt.');
            exit;
        }
    }

    // Update submission record with comments and file path
    $updateQuery = "
        UPDATE assignment_submissions 
        SET assignment_id = '$assignmentId', 
            subject_id = '$subjectId', 
            student_comments = '$studentComments', 
            file_path = '$filePath' 
        WHERE submission_id = '$submissionId' AND student_id = '$studentId'
    ";

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Assignment updated successfully!', 'viewAssResult.php');
    } else {
        showSweetAlert('error', 'Error!', 'Something went wrong. Please try again.');
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
            <div class="col-md-10">
                <h4 class="text-center mb-4">Edit Assignment Submission</h4>
                <form action="editSubmission.php?submission_id=<?php echo htmlspecialchars($submissionId); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="studentId" value="<?php echo htmlspecialchars($studentId); ?>">
                    <input type="hidden" name="existingFile" value="<?php echo htmlspecialchars($submissionData['file_path']); ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchAssignments()">
                                <option value="" disabled>Select a subject</option>
                                <?php
                                // Populate the subject options
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '" ' . ($subject['id'] == $submissionData['subject_id'] ? 'selected' : '') . '>' . htmlspecialchars($subject['subject_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No subjects available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="assignment" class="form-label">Assignment</label>
                            <select class="form-select" id="assignment" name="assignment" required>
                                <option value="" disabled>Select an assignment</option>
                                <!-- You may want to fetch assignments based on selected subject -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="studentComments" class="form-label">Additional Comments</label>
                        <textarea class="form-control" id="studentComments" name="studentComments" rows="4"><?php echo htmlspecialchars($submissionData['student_comments']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="assignmentFile" class="form-label">Assignment File</label>
                        <input type="file" class="form-control" id="assignmentFile" name="assignmentFile" onchange="previewFile()">
                    </div>

                    <div id="previewContainer" style="display: <?php echo empty($submissionData['file_path']) ? 'none' : 'block'; ?>;">
                        <h5>Current File:</h5>
                        <img id="filePreview" src="<?php echo htmlspecialchars($submissionData['file_path']); ?>" alt="File Preview">
                    </div>

                    <div class="text-right mt-3">
                        <button type="submit" name="updateAssignment" class="btn btn-primary">Update Assignment</button>
                    </div>
                </form>
            </div>
        </div>
        <?php include("../includes/footer.php"); ?>
    </main>
    <?php include("../includes/script.php"); ?>

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

        // Initialize the editor
        initializeTinyMCE('#studentComments', 300);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Fetch assignments for the selected subject when the page loads
            const initialSubjectId = document.getElementById('subject').value;
            if (initialSubjectId) {
                fetchAssignments(initialSubjectId, '<?php echo htmlspecialchars($submissionData["assignment_id"]); ?>');
            }

            // Update breadcrumb navigation
            const currentPage = 'Edit Assignment'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });

        // JavaScript to handle fetching assignments when a subject is selected
        function fetchAssignments(subjectId, selectedAssignmentId = null) {
            const studentId = document.getElementById('studentId').value; // Get the student ID

            if (!subjectId || !studentId) return; // Ensure both IDs are available

            const assignmentSelect = document.getElementById('assignment');

            // Clear current assignment options
            assignmentSelect.innerHTML = '<option value="" disabled selected>Select an assignment</option>';

            // Make an AJAX request to fetch assignments
            fetch('fetchAssignments.php?subject_id=' + subjectId + '&student_id=' + studentId) // Include student ID
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate the assignment dropdown
                        data.assignments.forEach(assignment => {
                            const option = document.createElement('option');
                            option.value = assignment.assignment_id;
                            option.textContent = assignment.assignment_title;
                            assignmentSelect.appendChild(option);

                            // Set the selected assignment if it matches the passed ID
                            if (assignment.assignment_id === selectedAssignmentId) {
                                option.selected = true;
                            }
                        });
                    } else {
                        alert('No assignments found for the selected subject.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching assignments:', error);
                });
        }

        // Modify this function to call fetchAssignments() when the subject is changed
        document.getElementById('subject').addEventListener('change', function() {
            fetchAssignments(this.value);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Assignment'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>