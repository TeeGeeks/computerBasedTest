<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch all subject assignments, sorted by class name
$assignmentsQuery = "
    SELECT sa.class_id, sa.teacher_id, sa.id AS assignment_id, 
           t.surname, t.other_names, 
           c.class_name, s.subject_name, 
           a.arm_name  -- Fetching arm_name
    FROM subject_assignments sa
    JOIN teachers t ON sa.teacher_id = t.teacher_id
    JOIN classes c ON sa.class_id = c.class_id
    JOIN subjects s ON sa.subject_id = s.id
    JOIN arms a ON sa.arm_id = a.arm_id  -- Join with arms table
    ORDER BY c.class_name, a.arm_name, t.surname  -- Sort by class name, arm name, and teacher name
";

$assignmentsResult = mysqli_query($connection, $assignmentsQuery);
?>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <h4 class="mt-4 text-primary">Manage Subject Assignments</h4>
            <a href="assignSubject.php" class="btn btn-primary mb-3">Assign New Subject</a>
            <div class="input-group mb-3" style="max-width: 300px">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search..." />
                <div class="input-group-append">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>
            <div class="table-responsive">
                <table id="assignmentsTable" class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>S.No.</th>
                            <th>Teacher Name</th>
                            <th>Class</th>
                            <th>Arm</th>
                            <th>Subject</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $counter = 1;
                        while ($assignment = mysqli_fetch_assoc($assignmentsResult)) {
                            $assignmentId = $assignment['assignment_id'];
                            echo "<tr>";
                            echo "<td>{$counter}</td>";
                            echo "<td>{$assignment['surname']} {$assignment['other_names']}</td>";
                            echo "<td>{$assignment['class_name']}</td>";
                            echo "<td>{$assignment['arm_name']}</td>";
                            echo "<td>{$assignment['subject_name']}</td>";
                            echo "<td>
                                <div class='btn-group' role='group'>
                                    <a href='editAssignment.php?id={$assignmentId}' class='btn btn-info btn-sm' data-bs-toggle='tooltip' title='Edit Assignment'>Update</a>
                                    <button class='btn btn-danger btn-sm delete-btn' data-id='{$assignmentId}' data-bs-toggle='tooltip' title='Delete Assignment'>
                                        Delete
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
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        // Tooltips initialization
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Delete functionality with SweetAlert2
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');

                    Swal.fire({
                        title: "Are you sure?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Fetch request to delete the assignment
                            fetch(`../endpoints/delete_assignment.php?id=${id}`, {
                                    method: 'GET'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Deleted!',
                                            'Assignment record has been deleted.',
                                            'success'
                                        ).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire(
                                            'Error!',
                                            'There was a problem deleting the record.',
                                            'error'
                                        );
                                    }
                                }).catch(error => {
                                    Swal.fire(
                                        'Error!',
                                        'There was an issue with the server request.',
                                        'error'
                                    );
                                });
                        }
                    });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Manage Assignments'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>