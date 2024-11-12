<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch the student's class and arm based on their session ID
$studentId = $_SESSION['student_id']; // Assuming student_id is stored in session
$studentQuery = "SELECT class_id, arm_id FROM students WHERE id = $studentId LIMIT 1";
$studentResult = mysqli_query($connection, $studentQuery);
$studentData = mysqli_fetch_assoc($studentResult);

$classId = $studentData['class_id'];
$armId = $studentData['arm_id'];

// Fetch all subjects for the dropdown
$subjectsQuery = "SELECT id, subject_name FROM subjects ORDER BY subject_name";
$subjectsResult = mysqli_query($connection, $subjectsQuery);

// Fetch assignments based on selected subject and filter by class and arm
$assignments = [];
if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
    $subjectId = intval($_GET['subject_id']);
    $assignmentsQuery = "
        SELECT a.assignment_id AS assignment_id, a.assignment_title, 
               a.assignment_description, a.due_date, s.subject_name
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        WHERE a.subject_id = $subjectId 
        AND a.class_id = $classId 
        AND a.arm_id = $armId
        ORDER BY a.due_date";
} else {
    $assignmentsQuery = "
        SELECT a.assignment_id AS assignment_id, a.assignment_title, 
               a.assignment_description, a.due_date, s.subject_name
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        WHERE a.class_id = $classId 
        AND a.arm_id = $armId
        ORDER BY a.due_date";
}

$assignmentsResult = mysqli_query($connection, $assignmentsQuery);
?>

<style>
    h4 {
        margin-bottom: 20px;
        color: #333;
    }

    .form-select,
    .btn {
        height: 45px;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .no-assignments {
        text-align: center;
        font-style: italic;
        color: #888;
    }

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
            <h4>View Assignments</h4>

            <!-- Subject Selection -->
            <form action="viewAssignment.php" method="GET" class="mb-4">
                <div class="input-group mb-3" style="max-width: 400px;">
                    <select name="subject_id" class="form-select" aria-label="Select Subject">
                        <option value="">Select Subject</option>
                        <?php while ($subject = mysqli_fetch_assoc($subjectsResult)): ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary mx-2">View Assignments</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='viewAssignment.php'">View All</button>
                </div>
            </form>

            <!-- Display Assignments -->
            <div class="table-responsive">
                <table id="assignmentsTable" class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>S.No.</th>
                            <th>Subject</th> <!-- Subject Column -->
                            <th>Assignment Title</th>
                            <th>Description</th>
                            <th>Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($assignmentsResult) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php while ($assignment = mysqli_fetch_assoc($assignmentsResult)): ?>
                                <tr>
                                    <td><?php echo $counter; ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td> <!-- Show Subject -->
                                    <td><?php echo htmlspecialchars($assignment['assignment_title']); ?></td>
                                    <td><?php echo strip_tags($assignment['assignment_description']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['due_date']); ?></td>
                                    <td>
                                        <a href="viewAssignmentDetails.php?id=<?php echo $assignment['assignment_id']; ?>" class="text-primary">view</a>
                                    </td>
                                </tr>
                                <?php $counter++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>

                            <p class="no-assignments">No assignments found.</p>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'View Assignments'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>