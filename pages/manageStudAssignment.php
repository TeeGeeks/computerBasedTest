<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch all classes from the database
$classQuery = "SELECT * FROM classes";
$classResult = mysqli_query($connection, $classQuery);

// Fetch all arms from the database
$armQuery = "SELECT * FROM arms";
$armResult = mysqli_query($connection, $armQuery);

// Fetch all subjects from the database
$subjectQuery = "SELECT * FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);

// Handle form submission
$students = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = trim($_POST['classId']);
    $armId = trim($_POST['armId']);

    // Fetch students based on selected class and arm
    $studentsQuery = "
        SELECT id, surname, other_names
        FROM students
        WHERE class_id = '$classId' AND arm_id = '$armId'
    ";
    $studentsResult = mysqli_query($connection, $studentsQuery);

    // Fetch results
    while ($student = mysqli_fetch_assoc($studentsResult)) {
        $students[] = $student;
    }
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

        <div class="container">
            <h4 class="mt-4">Manage Subject Assignments for Students</h4>
            <a href="assignSubject.php" class="btn btn-primary mb-3">Assign New Subject</a>

            <form method="POST" action="" class="mb-4">
                <div class="container py-4 d-flex justify-content-center mt-3">
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="classId" class="form-label">Class</label>
                                <select name="classId" id="classId" class="form-control" required>
                                    <option value="">Select Class</option>
                                    <?php while ($class = mysqli_fetch_assoc($classResult)) { ?>
                                        <option value="<?= $class['class_id'] ?>"><?= $class['class_name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="armId" class="form-label">Arm</label>
                                <select name="armId" id="armId" class="form-control" required>
                                    <option value="">Select Arm</option>
                                    <?php while ($arm = mysqli_fetch_assoc($armResult)) { ?>
                                        <option value="<?= $arm['arm_id'] ?>"><?= $arm['arm_name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="my-3">
                                <button type="submit" class="btn btn-primary">Fetch Subject</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <?php if (!empty($students)) : ?>
                <div class="table-responsive">
                    <table id="studentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>S.No.</th>
                                <th>Student Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            foreach ($students as $student) {
                                echo "<tr>";
                                echo "<td>{$counter}</td>";
                                echo "<td>{$student['surname']} {$student['other_names']}</td>";
                                echo "<td>
                                    <div class='btn-group' role='group'>
                                        <button class='btn btn-info btn-sm' data-bs-toggle='tooltip' title='Assign Subject'>
                                            Assign Subject
                                        </button>
                                    </div>
                                </td>";
                                echo "</tr>";
                                $counter++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <footer style="margin-top: 120px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Tooltips initialization
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Manage Class Assignments'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>