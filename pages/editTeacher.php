<?php
include("../config.php");
include("../includes/header.php");
include('../session.php');
include_once("../includes/generalFnc.php");

// Database connection
$connection = connection();

// Get teacher details by ID
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : null; // Ensure the ID is treated as an integer

if ($teacher_id) {
    $query = "SELECT * FROM teachers WHERE teacher_id = '$teacher_id'";
    $result = mysqli_query($connection, $query);
    $teacher = mysqli_fetch_assoc($result);

    if (!$teacher) {
        // Teacher not found, redirect back to manage page
        showSweetAlert('error', 'Oops...', 'Teacher not found.', 'manageTeacher.php');
        exit;
    }
} else {
    // ID is missing, redirect to manage page
    showSweetAlert('error', 'Oops...', 'Invalid teacher ID.', 'manageTeacher.php');
    exit;
}

// Update teacher details if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherSurname = mysqli_real_escape_string($connection, trim($_POST['teacherSurname']));
    $teacherOtherNames = mysqli_real_escape_string($connection, trim($_POST['teacherOtherNames']));
    $teacherQualification = mysqli_real_escape_string($connection, trim($_POST['teacherQualification']));
    $teacherDateOfBirth = mysqli_real_escape_string($connection, trim($_POST['teacherDateOfBirth']));
    $teacherGender = mysqli_real_escape_string($connection, trim($_POST['teacherGender']));
    $teacherEmail = mysqli_real_escape_string($connection, trim($_POST['teacherEmail']));
    $teacherPhone = mysqli_real_escape_string($connection, trim($_POST['teacherPhone']));
    $teacherUsername = mysqli_real_escape_string($connection, trim($_POST['teacherUsername']));

    // Encrypt password if provided
    $teacherPassword = !empty($_POST['teacherPassword']) ? password_hash(trim($_POST['teacherPassword']), PASSWORD_BCRYPT) : null;
    $role = 'staff'; // Role is set to staff

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
            $newFileName = 'teacher_' . $teacher_id . '.' . $fileExtension;
            $uploadFileDir = '../uploads/profile_images/';
            $dest_path = $uploadFileDir . $newFileName;

            // Attempt to move the uploaded file to the desired directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {

                // Set only the relative path for saving to the database
                $profileImage = 'uploads/profile_images/' . $newFileName; // Remove `..` from the path
            } else {
                showSweetAlert('error', 'Oops...', 'There was an error moving the uploaded file: ' . print_r(error_get_last(), true), 'editTeacher.php?id=' . $teacher_id);
                exit;
            }
        } else {
            showSweetAlert('error', 'Oops...', 'Upload failed. Allowed file types: ' . implode(', ', $allowedfileExtensions), 'editTeacher.php?id=' . $teacher_id);
            exit;
        }
    }

    // Prepare the update query for the teacher
    $updateQuery = "UPDATE teachers SET 
        surname='$teacherSurname', 
        other_names='$teacherOtherNames', 
        qualification='$teacherQualification', 
        date_of_birth='$teacherDateOfBirth', 
        gender='$teacherGender', 
        email='$teacherEmail', 
        phone_number='$teacherPhone', 
        username='$teacherUsername', 
        role='$role'";

    // Include password update if provided
    if ($teacherPassword) {
        $updateQuery .= ", password='$teacherPassword'";
    }

    // Include profile image path in the query if uploaded
    if ($profileImage) {
        $updateQuery .= ", profile_image='$profileImage'";
    }

    $updateQuery .= " WHERE teacher_id='$teacher_id'";

    // Execute the update query
    if (mysqli_query($connection, $updateQuery)) {
        // Teacher updated successfully
        showSweetAlert('success', 'Success!', 'Teacher updated successfully.', 'manageTeacher.php');
    } else {
        // Error occurred
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again. Error: ' . mysqli_error($connection), 'editTeacher.php?id=' . $teacher_id);
    }
}

$defaultImage = BASE_URL . 'assets/img/drake.jpg'; // Path to your default image
$currentProfileImage = !empty($teacher['profile_image']) ? BASE_URL . $teacher['profile_image'] : $defaultImage;

mysqli_close($connection);
?>



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
                <h4 class="text-center text-primary mb-4">Edit Teacher</h4>
                <form id="editTeacherForm" action="" method="POST" onsubmit="showSpinner()" enctype="multipart/form-data">
                    <div class="container ">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="teacherSurname" class="form-label">Surname</label>
                                <input type="text" class="form-control border rounded" id="teacherSurname" name="teacherSurname" value="<?php echo htmlspecialchars($teacher['surname']); ?>" placeholder="Enter surname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="teacherOtherNames" class="form-label">Other Names</label>
                                <input type="text" class="form-control border rounded" id="teacherOtherNames" name="teacherOtherNames" value="<?php echo htmlspecialchars($teacher['other_names']); ?>" placeholder="Enter other names" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="teacherQualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control border rounded" id="teacherQualification" name="teacherQualification" value="<?php echo htmlspecialchars($teacher['qualification']); ?>" placeholder="Enter qualification" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="teacherDateOfBirth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control border rounded" id="teacherDateOfBirth" name="teacherDateOfBirth" value="<?php echo htmlspecialchars($teacher['date_of_birth']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="teacherGender" class="form-label">Gender</label>
                                <select class="form-control border rounded" id="teacherGender" name="teacherGender" required>
                                    <option value="" disabled>Select gender</option>
                                    <option value="male" <?php echo $teacher['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $teacher['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $teacher['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="teacherPhone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control border rounded" id="teacherPhone" name="teacherPhone" value="<?php echo htmlspecialchars($teacher['phone_number']); ?>" placeholder="Enter phone number">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="teacherEmail" class="form-label">Email</label>
                                <input type="email" class="form-control border rounded" id="teacherEmail" name="teacherEmail" value="<?php echo htmlspecialchars($teacher['email']); ?>" placeholder="Enter email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="teacherUsername" class="form-label">Username</label>
                                <input type="text" class="form-control border rounded" id="teacherUsername" name="teacherUsername" value="<?php echo htmlspecialchars($teacher['username']); ?>" placeholder="Enter username" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="teacherPassword" class="form-label">Password</label>
                                <input type="password" class="form-control border rounded" id="teacherPassword" name="teacherPassword" placeholder="Enter new password (leave blank to keep current)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control border rounded" id="role" name="role" value="staff" readonly>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="profileImage" class="form-label">Profile Image</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/*" required>
                                <label class="input-group-text" for="profileImage">Choose file</label>
                            </div>
                        </div>
                        <div class="mb-3 text-center">
                            <label> Profile Image:</label><br>
                            <img src="<?php echo $currentProfileImage; ?>" alt=" Profile Image" id="profilePreview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;" />
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                                <i class="fa fa-save me-2"></i> Update Teacher
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

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
            const currentPage = 'Edit Teacher';
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