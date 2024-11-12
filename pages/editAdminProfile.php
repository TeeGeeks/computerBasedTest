<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch current admin details based on admin_id
$adminId = $_SESSION['admin_id'] ?? '';
$adminId = mysqli_real_escape_string($connection, $adminId); // Sanitize input to prevent SQL Injection

$adminQuery = "SELECT * FROM admins WHERE admin_id = '$adminId'";
$adminResult = mysqli_query($connection, $adminQuery);
$admin = mysqli_fetch_assoc($adminResult);

// Add admin details if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminEmail = mysqli_real_escape_string($connection, trim($_POST['email']));
    $adminFullName = mysqli_real_escape_string($connection, trim($_POST['full_name']));
    $adminPhoneNumber = mysqli_real_escape_string($connection, trim($_POST['phone_number']));
    $adminUsername = mysqli_real_escape_string($connection, trim($_POST['username']));
    $adminRole = mysqli_real_escape_string($connection, trim($_POST['role'])); // Adjust as needed
    $profileImagePath = $admin['profile_image']; // Default to current image

    // Handle profile image upload
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        $profileImage = $_FILES['profileImage'];

        $targetDirectory = "../uploads/profile_images/"; // Change this to your target directory
        $targetFile = $targetDirectory . basename($profileImage['name']);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check file type and size
        $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
        if (in_array($imageFileType, $allowedTypes) && $profileImage['size'] < 5000000) { // Max size 5MB
            if (move_uploaded_file($profileImage['tmp_name'], $targetFile)) {
                $profileImagePath = 'uploads/profile_images/' . basename($profileImage['name']); // Set the path to the uploaded image
            } else {
                showSweetAlert('error', 'Oops...', 'Error uploading the image. Please try again.', 'editAdminProfile.php?admin_id=' . $adminId);
                exit;
            }
        } else {
            showSweetAlert('error', 'Oops...', 'Invalid image file type or size too large.', 'editAdminProfile.php?admin_id=' . $adminId);
            exit;
        }
    }

    // Update query to modify admin details
    $updateQuery = "UPDATE admins SET 
        email = '$adminEmail', 
        full_name = '$adminFullName', 
        phone_number = '$adminPhoneNumber', 
        username = '$adminUsername', 
        role = '$adminRole', 
        profile_image = '$profileImagePath' 
        WHERE admin_id = '$adminId'";

    if (mysqli_query($connection, $updateQuery)) {
        showSweetAlert('success', 'Success!', 'Profile updated successfully.', 'adminProfile.php'); // Redirect to profile page or any other
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'editAdminProfile.php?admin_id=' . $adminId);
    }

    mysqli_close($connection);
}
?>

<style>
    .form-control {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .d-grid .btn {
        border-radius: 0.375rem;
    }

    .img-preview {
        width: 150px;
        /* Adjust width */
        height: auto;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        margin-top: 10px;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4">Edit Admin Profile</h4>
                <form id="editAdminForm" action="" method="POST" enctype="multipart/form-data" onsubmit="showSpinner()">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control border" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control border" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="text" class="form-control border" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($admin['phone_number']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control border" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="profileImage" class="form-label">Profile Image</label>
                            <input type="file" class="form-control border" id="profileImage" name="profileImage" accept="image/*" onchange="previewImage(event)">
                            <?php if (!empty($admin['profile_image'])): ?>
                                <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($admin['profile_image']); ?>" alt="Profile Image" class="img-preview">
                            <?php else: ?>
                                <p>No image uploaded yet.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control border" id="role" name="role" value="<?php echo htmlspecialchars($admin['role']); ?>" readonly>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Save
                        </button>
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

        function previewImage(event) {
            const imgPreview = document.querySelector('.img-preview');
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                }
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Admin Profile';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>