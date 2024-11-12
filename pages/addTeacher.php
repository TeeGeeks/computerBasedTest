<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Add teacher details if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form input
    $teacherSurname = trim($_POST['teacherSurname']);
    $teacherOtherNames = trim($_POST['teacherOtherNames']);
    $teacherQualification = trim($_POST['teacherQualification']);
    $teacherDateOfBirth = trim($_POST['teacherDateOfBirth']);
    $teacherGender = trim($_POST['teacherGender']);
    $teacherEmail = trim($_POST['teacherEmail']);
    $teacherPhone = trim($_POST['teacherPhone']);
    $teacherUsername = trim($_POST['teacherUsername']);
    $teacherPassword = password_hash($_POST['teacherPassword'], PASSWORD_BCRYPT); // Encrypt password
    $role = trim($_POST['role']); // Dynamically set the role

    // Validate email
    if (!filter_var($teacherEmail, FILTER_VALIDATE_EMAIL)) {
        showSweetAlert('error', 'Invalid Email', 'Please enter a valid email address.', 'addTeacher.php');
        exit;
    }

    // Check if the teacher's email or username already exists
    $checkQuery = "SELECT * FROM teachers WHERE email = '$teacherEmail' OR username = '$teacherUsername'";
    $result = mysqli_query($connection, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        // Email or username already exists
        showSweetAlert('error', 'Oops...', 'Email or Username already exists. Please use a different one.', 'addTeacher.php');
    } else {
        // Correctly map values to the corresponding columns
        $query = "INSERT INTO teachers (surname, other_names, qualification, date_of_birth, gender, email, phone_number, username, password, role) 
                  VALUES ('$teacherSurname', '$teacherOtherNames', '$teacherQualification', '$teacherDateOfBirth', '$teacherGender', '$teacherEmail', '$teacherPhone', '$teacherUsername', '$teacherPassword', '$role')";

        if (mysqli_query($connection, $query)) {
            // Teacher added successfully
            showSweetAlert('success', 'Success!', 'Teacher added successfully.', 'manageTeacher.php');
        } else {
            // Error occurred during insert
            showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addTeacher.php');
        }
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
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10 col-sm-12">
                <h4 class="text-center text-primary mb-4">Add New Teacher</h4>
                <form id="addTeacherForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherSurname" class="form-label">Surname</label>
                            <input type="text" class="form-control border" id="teacherSurname" name="teacherSurname" placeholder="Enter surname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherOtherNames" class="form-label">Other Names</label>
                            <input type="text" class="form-control border" id="teacherOtherNames" name="teacherOtherNames" placeholder="Enter other names" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherQualification" class="form-label">Qualification</label>
                            <input type="text" class="form-control border" id="teacherQualification" name="teacherQualification" placeholder="Enter qualification">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherDateOfBirth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control border" id="teacherDateOfBirth" name="teacherDateOfBirth" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherGender" class="form-label">Gender</label>
                            <select class="form-control border" id="teacherGender" name="teacherGender" required>
                                <option value="" disabled selected>Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherPhone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control border" id="teacherPhone" name="teacherPhone" placeholder="Enter phone number">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherEmail" class="form-label">Email</label>
                            <input type="email" class="form-control border" id="teacherEmail" name="teacherEmail" placeholder="Enter email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacherUsername" class="form-label">Username</label>
                            <input type="text" class="form-control border" id="teacherUsername" name="teacherUsername" placeholder="Enter username" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="teacherPassword" class="form-label">Password</label>
                            <input type="password" class="form-control border" id="teacherPassword" name="teacherPassword" placeholder="Enter password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control border" id="role" name="role" value="staff" readonly>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Add Teacher
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
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Teacher';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>