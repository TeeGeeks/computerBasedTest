<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$studentId = $_SESSION['student_id'] ?? null;
$subjects = [];

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
        // $subjectQuery = "
        //     SELECT s.id, s.subject_name 
        //     FROM subject_assignments sa
        //     JOIN subjects s ON sa.subject_id = s.id
        //     WHERE sa.class_id = '$classId' 
        //       AND sa.arm_id = '$armId'
        // ";
        $subjectQuery = "SELECT * FROM subjects";
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

// Handle form submission for assignment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploadAssignment'])) {
    $assignmentId = trim($_POST['assignment']);
    $subjectId = trim($_POST['subject']);
    $studentComments = trim($_POST['studentComments']); // Capture comments

    $studentComments = mysqli_real_escape_string($connection, $studentComments);

    // Check if the student has already submitted this assignment
    $checkSubmissionQuery = "SELECT * FROM assignment_submissions 
                             WHERE student_id = '$studentId' 
                             AND assignment_id = '$assignmentId' 
                             AND subject_id = '$subjectId'";

    $checkResult = mysqli_query($connection, $checkSubmissionQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        showSweetAlert('error', 'Error!', 'You have already submitted this assignment. You cannot submit it again.');
    } else {
        // File upload logic
        $uploadDirectory = "../uploads/assignments/student_submissions/";
        $fileName = $_FILES['assignmentFile']['name'];
        $fileTmpName = $_FILES['assignmentFile']['tmp_name'];
        $fileSize = $_FILES['assignmentFile']['size'];
        $fileError = $_FILES['assignmentFile']['error'];
        $fileType = $_FILES['assignmentFile']['type'];

        // Validate if a file is uploaded
        $filePath = null;

        if (!empty($fileName)) {
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png', 'txt'];

            if (in_array(strtolower($fileExt), $allowed)) {
                if ($fileError === 0) {
                    if ($fileSize < 5000000) {
                        $newFileName = uniqid('', true) . "." . $fileExt;
                        $fileDestination = $uploadDirectory . $newFileName;

                        if (move_uploaded_file($fileTmpName, $fileDestination)) {
                            $filePath = $fileDestination;

                            // Insert submission record with comments
                            $insertQuery = "INSERT INTO assignment_submissions (student_id, assignment_id, subject_id, file_path, student_comments) 
                                            VALUES ('$studentId', '$assignmentId', '$subjectId', '$filePath', '$studentComments')";
                            if (mysqli_query($connection, $insertQuery)) {
                                showSweetAlert('success', 'Success!', 'Assignment submitted successfully!', 'submitAssignment.php');
                            } else {
                                showSweetAlert('error', 'Error!', 'Something went wrong. Please try again.');
                            }
                        } else {
                            showSweetAlert('error', 'Error!', 'There was an error uploading the file.');
                        }
                    } else {
                        showSweetAlert('error', 'Error!', 'File size exceeds the limit of 5MB.');
                    }
                } else {
                    showSweetAlert('error', 'Error!', 'There was an error with the file upload.');
                }
            } else {
                showSweetAlert('error', 'Error!', 'Invalid file type. Allowed types: pdf, doc, docx, jpg, png, txt.');
            }
        } else {
            showSweetAlert('error', 'Error!', 'Please select a file to upload.');
        }
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
                <h4 class="text-center mb-4">Submit Assignment</h4>
                <form action="submitAssignment.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="studentId" value="<?php echo htmlspecialchars($studentId); ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required onchange="fetchAssignments()">
                                <option value="" disabled selected>Select a subject</option>
                                <?php
                                // Populate the subject options
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '">' . htmlspecialchars($subject['subject_name']) . '</option>';
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
                                <option value="" disabled selected>Select an assignment</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="studentComments" class="form-label">Additional Comments</label>
                        <textarea class="form-control" id="studentComments" name="studentComments" rows="4"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="assignmentFile" class="form-label">Assignment File</label>
                        <input type="file" class="form-control" id="assignmentFile" name="assignmentFile">
                    </div>

                    <div class="text-right">
                        <button type="submit" name="uploadAssignment" class="btn btn-primary">Submit Assignment</button>
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

        // Initialize the editor
        initializeTinyMCE('#studentComments', 300);
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
        // JavaScript to handle fetching assignments when a subject is selected
        function fetchAssignments() {
            const subjectId = document.getElementById('subject').value;
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
                        });
                    } else {
                        alert('No assignments found for the selected subject.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching assignments:', error);
                });
        }
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Assignment'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>