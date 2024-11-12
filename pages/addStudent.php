<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Initialize an array to hold classes
$classes = [];

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

// Add student details if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admissionNo = trim($_POST['admissionNo']);
    $surname = trim($_POST['surname']);
    $otherNames = trim($_POST['otherNames']);
    $dateOfBirth = trim($_POST['dateOfBirth']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $address = trim($_POST['address']);
    $class_id = trim($_POST['class']);
    $arm_id = trim($_POST['arm']); // Capture arm from form input
    $role = 'student'; // Set the role as a string 'student'

    // Parent details
    $parentName = trim($_POST['parentName']);
    $parentEmail = trim($_POST['parentEmail']);
    $parentPhone = trim($_POST['parentPhone']);

    // Login credentials
    $username = trim($_POST['username']);
    $password = trim($_POST['password']); // Password (not hashed yet for empty check)

    // Validate required fields
    if (empty($surname) || empty($otherNames) || empty($email) || empty($username) || empty($password)) {
        showSweetAlert('warning', 'Missing Fields', 'Please fill in all required fields.', 'addStudent.php');
        exit; // Stop script execution
    }

    // Password hashing
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert query for student
    $insertQuery = "INSERT INTO students (admission_no, surname, other_names, date_of_birth, gender, email, phone_number, address, class_id, arm_id, parent_name, parent_email, parent_phone, username, password, role) 
                    VALUES ('$admissionNo', '$surname', '$otherNames', '$dateOfBirth', '$gender', '$email', '$phoneNumber', '$address', '$class_id', '$arm_id', '$parentName', '$parentEmail', '$parentPhone', '$username', '$hashedPassword', '$role')"; // Added arm_id and role to the query

    if (mysqli_query($connection, $insertQuery)) {
        showSweetAlert('success', 'Success!', 'Student added successfully.', 'manageStudent.php');
    } else {
        showSweetAlert('error', 'Oops...', 'Something went wrong. Please try again.', 'addStudent.php');
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
        /* Adjust border color */
        border-radius: 0.375rem;
        /* Adjust border radius */
        padding: 0.375rem 0.75rem;
        /* Adjust padding */
    }

    .form-control:focus {
        border-color: #80bdff;
        /* Adjust focus border color */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        /* Add focus shadow */
    }

    .form-select {
        border: 1px solid #ced4da;
        /* Ensure select box has border */
    }

    .form-select:focus {
        border-color: #80bdff;
        /* Adjust focus border color */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        /* Add focus shadow */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10 col-sm-12">
                <h4 class="text-center text-primary mb-4">Add Student</h4>
                <form id="addStudentForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="admissionNo" class="form-label">Admission No</label>
                            <input type="text" class="form-control border" id="admissionNo" name="admissionNo">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="surname" class="form-label">Surname</label>
                            <input type="text" class="form-control border" id="surname" name="surname" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="otherNames" class="form-label">Other Names</label>
                            <input type="text" class="form-control border" id="otherNames" name="otherNames" required>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label for="dateOfBirth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control border" id="dateOfBirth" name="dateOfBirth" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control border" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="phoneNumber" class="form-label">Phone Number</label>
                            <input type="text" class="form-control border" id="phoneNumber" name="phoneNumber">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control border" id="address" name="address">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-control" id="class" name="class" required>
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
                        <div class="col-md-3 mb-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-control" id="arm" name="arm" required>
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

                    </div>

                    <h5 class="mt-4">Parent's Information</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="parentName" class="form-label">Parent's Name</label>
                            <input type="text" class="form-control border" id="parentName" name="parentName">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parentEmail" class="form-label">Parent's Email</label>
                            <input type="email" class="form-control border" id="parentEmail" name="parentEmail">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="parentPhone" class="form-label">Parent's Phone</label>
                            <input type="text" class="form-control border" id="parentPhone" name="parentPhone">
                        </div>
                    </div>
                    <h5 class="mt-4">Login Credentials</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control border" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control border" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" id="submitBtn" name="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i> Add Student
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
            submitBtn.disabled = true; // Disable the button to prevent multiple submissions
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Add Student';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>