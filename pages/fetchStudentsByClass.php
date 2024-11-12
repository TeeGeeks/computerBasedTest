<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Initialize arrays to hold classes, arms, and students
$classes = [];
$arms = [];
$students = [];

// Fetch all classes associated with the teacher
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
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

// Initialize variables for selected class and arm
$selectedClass = '';
$selectedArm = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClass = trim($_POST['class']);
    $selectedArm = trim($_POST['arm']);

    // Validate required fields
    if (empty($selectedClass) || empty($selectedArm)) {
        showSweetAlert('warning', 'Missing Field', 'Please select a class and an arm.', 'fetchStudents.php');
        exit;
    }

    // Fetch students based on selected class and arm
    $studentQuery = "SELECT * FROM students WHERE class_id = '$selectedClass' AND arm_id = '$selectedArm' ORDER BY surname ASC";
    $studentResult = mysqli_query($connection, $studentQuery);
    if ($studentResult) {
        while ($row = mysqli_fetch_assoc($studentResult)) {
            $students[] = $row;
        }
    } else {
        showSweetAlert('error', 'Error', 'Failed to fetch students.', 'fetchStudents.php');
    }
}

// Close the database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fetch Students by Class</title>
    <style>
        /* Add your CSS styling here */
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-left {
            text-align: left !important;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 mt-3">
            <div class="col-md-12">
                <h4 class="text-center mb-4 text-primary">Fetch Students by Class</h4>

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4">
                    <!-- Form Section -->
                    <form id="fetchStudentsForm" action="" method="POST" class="w-100 w-lg-50 mb-3 mb-lg-0" onsubmit="showSpinner()">
                        <div class="mb-3">
                            <label for="class" class="form-label fw-bold">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                // Populate the class options
                                if (!empty($classes)) {
                                    foreach ($classes as $class) {
                                        $selected = ($selectedClass == $class['class_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . $selected . '>' . htmlspecialchars($class['class_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No classes available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="arm" class="form-label fw-bold">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select an arm</option>
                                <?php
                                // Populate the arm options
                                if (!empty($arms)) {
                                    foreach ($arms as $arm) {
                                        $selected = ($selectedArm == $arm['arm_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($arm['arm_id']) . '" ' . $selected . '>' . htmlspecialchars($arm['arm_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No arms available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                Fetch Students
                            </button>
                        </div>
                    </form>

                    <!-- Search Section -->
                    <div class="input-group w-100 w-lg-auto" style="max-width: 300px;">
                        <input type="text" id="tableSearch" class="form-control" placeholder="Search Student..." aria-label="Search Students" />
                        <div class="input-group-append">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Result Section -->
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <?php if (!empty($students)): ?>
                        <div class="table-responsive">
                            <table id="assignmentsTable" class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>S/N</th>
                                        <th>Admission No</th>
                                        <th>Surname</th>
                                        <th>Other Names</th>
                                        <th>Email</th>
                                        <th>Phone Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                            <td><?php echo htmlspecialchars($student['surname']); ?></td>
                                            <td><?php echo htmlspecialchars($student['other_names']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="mt-4 text-danger text-center">No students found for the selected class.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>


        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        // Table search functionality
        document.getElementById('tableSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#assignmentsTable tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const found = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(searchValue));
                row.style.display = found ? '' : 'none';
            });
        });

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
            const currentPage = 'Fetch Students by Class';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>