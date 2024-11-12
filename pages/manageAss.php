<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$teacherId = $_SESSION['staff_id'] ?? ''; // Get teacher ID from session
$isAdmin = $_SESSION['user_role'] === 'admin'; // Check if the user is an admin
// Fetch assignments based on the user's role
function fetchAssignmentsByRole($teacherId, $isAdmin)
{
    global $connection; // Use the global connection variable

    // If the user is an admin, fetch all assignments; otherwise, fetch only the teacher's assignments
    if ($isAdmin) {
        $query = "SELECT a.assignment_id, a.assignment_title, a.assignment_description, a.file_path, a.due_date, c.class_name, ar.arm_name, s.subject_name
                  FROM assignments a
                  JOIN classes c ON a.class_id = c.class_id
                  JOIN arms ar ON a.arm_id = ar.arm_id
                  JOIN subjects s ON a.subject_id = s.id";
    } else {
        $query = "SELECT a.assignment_id, a.assignment_title, a.assignment_description, a.file_path, a.due_date, c.class_name, ar.arm_name, s.subject_name
                  FROM assignments a
                  JOIN classes c ON a.class_id = c.class_id
                  JOIN arms ar ON a.arm_id = ar.arm_id
                  JOIN subjects s ON a.subject_id = s.id
                  WHERE a.teacher_id = '$teacherId'";
    }


    $result = mysqli_query($connection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC); // Return all assignments as an associative array
}
$assignments = fetchAssignmentsByRole($teacherId, $isAdmin); // Fetch assignments based on the user's role
$subjects = fetchSubjects(); // Fetch all subjects
$classes = fetchClasses(); // Fetch all classes
$arms = fetchArms(); // Fetch all classes

// Fetch all subjects and classes
$subjects = fetchSubjects(); // Fetch all subjects
$classes = fetchClasses(); // Fetch all classes

// Helper functions to get subject and class names by ID
function getSubjectNameById($subjects, $subjectId)
{
    foreach ($subjects as $subject) {
        if ($subject['id'] == $subjectId) {
            return $subject['subject_name']; // Return the subject name if the ID matches
        }
    }
    return 'Unknown'; // Return a default value if no match is found
}

function getClassNameById($classes, $class_id)
{
    foreach ($classes as $class) {
        if ($class['class_id'] == $class_id) {
            return $class['class_name']; // Return the class name if the ID matches
        }
    }
    return 'Unknown'; // Return a default value if no match is found
}

function getArmNameById($arms, $arm_id)
{
    foreach ($arms as $arm) {
        if ($arm['arm_id'] == $arm_id) {
            return $arm['arm_name']; // Return the class name if the ID matches
        }
    }
    return 'Unknown'; // Return a default value if no match is found
}
?>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container-fluid">
            <h4 class="mt-4">Assignment List</h4>
            <a href="addAssignment.php" class="btn btn-primary mb-3">Add New Assignment</a>
            <div class="input-group mb-3" style="max-width: 300px;">
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
                            <th>Class</th>
                            <th>Arm</th>
                            <th>Subject</th>
                            <th>Assignment Title</th>
                            <th>Due Date</th>
                            <th>File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $counter = 1; // Initialize counter
                        foreach ($assignments as $assignment) {
                            $id = $assignment['assignment_id'] ?? 'N/A'; // Assignment ID
                            $title = $assignment['assignment_title'] ?? 'N/A'; // Assignment Title
                            $description = strip_tags($assignment['assignment_description']) ?? 'N/A'; // Description
                            $due_date = $assignment['due_date'] ?? 'N/A'; // Due Date
                            $file = $assignment['file_path'] ?? 'N/A'; // File Path
                            $class_name = $assignment['class_name'] ?? 'N/A'; // Class Name
                            $arm_name = $assignment['arm_name'] ?? 'N/A'; // Class Name
                            $subject_name = $assignment['subject_name'] ?? 'N/A'; // Subject Name

                            echo "<tr>";
                            echo "<td>{$counter}</td>"; // Serial number
                            echo "<td>{$class_name}</td>"; // Class Name
                            echo "<td>{$arm_name}</td>"; // Class Name
                            echo "<td>{$subject_name}</td>"; // Subject Name
                            echo "<td>{$title}</td>"; // Assignment Title
                            echo "<td>{$due_date}</td>"; // Due Date
                            echo "<td><a href='../uploads/{$file}' target='_blank'>Download</a></td>"; // File Link
                            echo "<td>
                                <div class='btn-group' role='group'>
                                   <a href='editAss.php?id={$id}' class='btn btn-info btn-sm' data-toggle='tooltip' title='Edit Assignment'>
                                        Update
                                    </a>
                                    <button class='btn btn-danger btn-md delete-btn' data-id='{$id}' data-bs-toggle='tooltip' title='Delete Assignment'>
                                        Delete
                                    </button>
                                </div>
                              </td>";
                            echo "</tr>";

                            $counter++; // Increment counter
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
            const rows = document.querySelectorAll('#assignmentsTable tbody tr'); // Fixed table ID
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
                            fetch(`../endpoints/delete_ass.php?id=${id}`, {
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
    </script>
</body>

</html>