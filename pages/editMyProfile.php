<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Initialize an array to hold classes
$classes = [];
$arms = [];

// Fetch classes from the database
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);

if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row; // Store each class in the array
    }
}

// Fetch arms from the database
$armQuery = "SELECT arm_id, arm_name FROM arms"; // Adjust this query as per your database schema
$armResult = mysqli_query($connection, $armQuery);

if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row; // Store each arm in the array
    }
}

// Get student ID from URL
$studentId = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch existing student details
$studentDetails = [];
if ($studentId) {
    $studentQuery = "SELECT * FROM students WHERE id = '$studentId'";
    $studentResult = mysqli_query($connection, $studentQuery);

    if ($studentResult && mysqli_num_rows($studentResult) > 0) {
        $studentDetails = mysqli_fetch_assoc($studentResult);
    } else {
        showSweetAlert('error', 'Student Not Found', 'The student record could not be found.', 'manageStudent.php');
        exit;
    }
}

// Update student details if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login credentials
    $username = trim($_POST['username']);
    $password = trim($_POST['password']); // Password (not hashed yet for empty check)



    // Password hashing (update if password is provided)
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : $studentDetails['password'];

    // Handle profile image upload
    $profileImage = '';
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profileImage']['tmp_name'];
        $fileName = $_FILES['profileImage']['name'];
        $fileSize = $_FILES['profileImage']['size'];
        $fileType = $_FILES['profileImage']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Specify the allowed file extensions
        $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg'];

        // Check if the file extension is valid
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Define a new file name to prevent overwriting
            $newFileName = 'student_' . $studentId . '.' . $fileExtension;
            $uploadFileDir = '../uploads/profile_images/';
            $dest_path = $uploadFileDir . $newFileName;

            // Attempt to move the uploaded file to the desired directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Set only the relative path for saving to the database
                $profileImage = 'uploads/profile_images/' . $newFileName; // Remove `..` from the path
            } else {
                showSweetAlert('error', 'Oops...', 'There was an error moving the uploaded file: ' . print_r(error_get_last(), true), "editStudent.php?id=$studentId");
                exit;
            }
        } else {
            showSweetAlert('error', 'Oops...', 'Upload failed. Allowed file types: ' . implode(', ', $allowedfileExtensions), "editStudent.php?id=$studentId");
            exit;
        }
    }

    // Update query for student
    $updateQuery = "UPDATE students SET 
                        username = '$username', 
                        password = '$hashedPassword'"; // Added role field

    // Include profile image path in the query if uploaded
    if ($profileImage) {
        $updateQuery .= ", profile_image = '$profileImage'";
    }

    $updateQuery .= " WHERE id = '$studentId'";

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Student updated successfully.');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', "editStudent.php?id=$studentId");
    }
}

// Close the database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="../path/to/bootstrap.css"> <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="../path/to/custom.css"> <!-- Include your custom styles -->
</head>

<style>
    .form-control {
        border: 1px solid #ced4da;
        /* Border color */
        border-radius: 0.375rem;
        /* Border radius */
        padding: 0.375rem 0.75rem;
        /* Padding */
    }

    .form-control:focus {
        border-color: #80bdff;
        /* Focus border color */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        /* Focus shadow */
    }

    .d-grid .btn {
        border-radius: 0.375rem;
        /* Button corners rounded */
    }

    .form-group {
        position: relative;
        max-width: 300px;
        /* Adjust width as needed */
    }

    .custom-file {
        margin-bottom: 10px;
    }

    .img-preview {
        display: none;
        /* Initially hidden */
        width: 100px;
        /* Set size */
        height: 100px;
        /* Set size */
        border-radius: 50%;
        /* Circular shape */
        object-fit: cover;
        /* Cover the area without distortion */
        border: 2px solid #007bff;
        /* Add border color */
        margin-top: 10px;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10 col-sm-12">
                <h4 class="text-center text-primary mb-4">Edit Student</h4>
                <form id="editStudentForm" action="" method="POST" onsubmit="showSpinner()" enctype="multipart/form-data">

                    <div class="form-group mb-3">
                        <label for="profileImage">Profile Image</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-upload"></i></span>
                            </div>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="profileImage" name="profileImage" accept="image/*">
                                <label class="custom-file-label" for="profileImage">Choose file</label>
                            </div>
                        </div>
                        <div class="mt-2">
                            <img id="profilePreview" class="img-preview" src="" alt="Profile Preview">
                        </div>
                    </div>

                    <h5 class="mt-4">Login Credentials</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control border" id="username" name="username" value="<?php echo htmlspecialchars($studentDetails['username']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password (leave blank to keep unchanged)</label>
                            <input type="password" class="form-control border" id="password" name="password">
                        </div>
                    </div>

                    <div class="">
                        <button type="submit" class="btn btn-primary">Update Student</button>
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
            submitBtn.disabled = true; // Disable the button to prevent multiple submissions
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Student';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
    <script>
        document.getElementById('profileImage').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('profilePreview');

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result; // Set the source of the image
                    preview.style.display = 'block'; // Show the image
                }

                reader.readAsDataURL(file); // Read the file as a data URL
            } else {
                preview.src = ''; // Reset the image source if no file is selected
                preview.style.display = 'none'; // Hide the image if no file is selected
            }
        });
    </script>
</body>

</html>