<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming the user's role and ID is stored in the session
$userId = $_SESSION['staff_id'] ?? '';
$userRole = $_SESSION['user_role'] ?? ''; // Assuming role is stored in session

// Initialize arrays to hold assignment submissions
$submissions = [];

if ($userRole === 'admin') {
    // Admin can see all submissions
    $submissionQuery = "
        SELECT asub.*, 
               CONCAT(s.surname, ' ', s.other_names) AS student_name, 
               c.class_name, 
               a.arm_id, 
               ar.arm_name, 
               a.assignment_title 
        FROM assignment_submissions asub
        JOIN students s ON asub.student_id = s.id
        JOIN classes c ON s.class_id = c.class_id  -- Update here
        JOIN assignments a ON asub.assignment_id = a.assignment_id
        JOIN arms ar ON a.arm_id = ar.arm_id";
} else {
    // Teacher can only see their own submissions
    $submissionQuery = "
        SELECT asub.*, 
               CONCAT(s.surname, ' ', s.other_names) AS student_name, 
               c.class_name, 
               a.arm_id, 
               ar.arm_name, 
               a.assignment_title 
        FROM assignment_submissions asub
        JOIN students s ON asub.student_id = s.id
        JOIN classes c ON s.class_id = c.class_id  -- Update here
        JOIN assignments a ON asub.assignment_id = a.assignment_id
        JOIN arms ar ON a.arm_id = ar.arm_id 
        WHERE a.teacher_id = '$userId'";
}



$submissionResult = mysqli_query($connection, $submissionQuery);
if ($submissionResult) {
    while ($row = mysqli_fetch_assoc($submissionResult)) {
        $submissions[] = $row;
    }
} else {
    showSweetAlert('error', 'Error', 'Failed to fetch submissions.', 'viewAssignmentSubmission.php');
}

// Close the database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Assignment Submissions</title>
    <style>
        /* Add your CSS styling here */
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

        .action-btn {
            margin-right: 5px;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>
        <div class="container py-4">
            <h4 class="text-center mb-4">Assignment Submissions</h4>
            <div class="input-group mb-3" style="max-width: 300px;">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search..." />
                <div class="input-group-append">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>
            <?php if (!empty($submissions)): ?>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>S/N</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Arm</th>
                                <th>Assignment Title</th>
                                <th>Attachment</th>
                                <th>Submission Date</th>
                                <th>Action</th> <!-- New Action Column -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $index => $submission): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['arm_name']); ?></td> <!-- Updated to display arm name -->
                                    <td><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">View File</a>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($submission['created_at'])); ?></td>
                                    <td>
                                        <a href="markSubmission.php?submission_id=<?php echo htmlspecialchars($submission['submission_id']); ?>" class="btn btn-primary">View for Marking</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>


                </div>
            <?php else: ?>
                <p class="mt-4">No submissions found.</p>
            <?php endif; ?>
        </div>
        </div>

        <footer style="margin-top: 120px;">
            <?php include("../includes/footer.php"); ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        // Table search functionality
        document.getElementById('tableSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#basic-datatables tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const found = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(searchValue));
                row.style.display = found ? '' : 'none';
            });
        });

        // Tooltips initialization
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Assignment Submission'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>