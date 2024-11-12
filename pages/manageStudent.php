<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$students = fetchStudents(); // Fetch student records (implement fetchStudents function accordingly)

?>

<link rel="stylesheet" type="text/css" href="css/mycss.css">

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <h4 class="mt-4 text-primary">Student List</h4>
            <a href="addStudent.php" class="btn btn-primary mb-3">Add New Student</a>

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
                            <th>Admission No</th>
                            <th>Surname</th>
                            <th>Other Names</th>
                            <th>Class</th>
                            <th>Arm</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($students as $student) {
                            $id = $student['id'];
                            $class_id = $student['class_id'];
                            $arm_id = $student['arm_id'];

                            $class_id_safe = intval($class_id);
                            $classname_query = "SELECT class_name FROM classes WHERE class_id = '$class_id_safe'";
                            $result = $connection->query($classname_query);

                            $class_name = '';
                            if ($result && $row = $result->fetch_assoc()) {
                                $class_name = $row['class_name'];
                            }

                            $arm_id_safe = intval($arm_id);
                            $armname_query = "SELECT arm_name FROM arms WHERE arm_id = '$arm_id_safe'";
                            $result = $connection->query($armname_query);

                            $arm_name = '';
                            if ($result && $row = $result->fetch_assoc()) {
                                $arm_name = $row['arm_name'];
                            }

                            echo "<tr>";
                            echo "<td>{$counter}</td>";
                            echo "<td>" . htmlspecialchars($student['admission_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($student['surname']) . "</td>";
                            echo "<td>" . htmlspecialchars($student['other_names']) . "</td>";
                            echo "<td>" . htmlspecialchars($class_name) . "</td>";
                            echo "<td>" . htmlspecialchars($arm_name) . "</td>";
                            echo "<td>
                                    <div class='btn-group' role='group'>
                                        <a href='editStudent.php?id={$id}' class='btn btn-info btn-sm' data-bs-toggle='tooltip' title='Edit Student'>Update</a>
                                        <button class='btn btn-danger btn-md delete-btn' data-id='{$id}' data-bs-toggle='tooltip' title='Delete Student'>
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

        <footer style="margin-top: 120px;">
            <?php include("../includes/footer.php"); ?>
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
                            fetch(`../endpoints/delete_student.php?id=${id}`, {
                                    method: 'GET'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Deleted!',
                                            'Student record has been deleted.',
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
            const currentPage = 'Manage Student';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>


</html>