<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$teacherId = $_SESSION['staff_id'] ?? ''; // Get teacher ID from session
$isAdmin = $_SESSION['user_role'] === 'admin'; // Check if the user is an admin

// Fetch classes based on user role
if ($isAdmin) {
    // Admin can see all classes
    $classes = fetchOnlineClasses(); // This should return all classes
} else {
    // Teachers can only see their classes
    $classes = fetchTeacherOnlineClasses($teacherId); // Implement this function to fetch classes for the teacher
}

function fetchOnlineClasses()
{
    global $connection; // Use the global connection variable

    $query = "SELECT * FROM online_classes"; // Adjust the query as necessary
    $result = mysqli_query($connection, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($connection));
    }

    $classes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row;
    }

    return $classes;
}


function fetchTeacherOnlineClasses($teacherId)
{
    global $connection; // Use the global connection variable

    $query = "SELECT * FROM online_classes WHERE teacher_id = ?";
    $stmt = mysqli_prepare($connection, $query);

    // Bind the parameter (teacherId) to the prepared statement
    mysqli_stmt_bind_param($stmt, 's', $teacherId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die("Query failed: " . mysqli_error($connection));
    }

    $classes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $classes[] = $row;
    }

    mysqli_stmt_close($stmt); // Close the prepared statement
    return $classes;
}


?>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container-fluid">
            <h4 class="mt-4 ">Online Classes List</h4>
            <a href="createOnlineClass.php" class="btn btn-primary mb-3">Add New Online Class</a>
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
                            <th>Class Title</th>
                            <th>Class Description</th>
                            <th>Class Link</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $counter = 1; // Initialize counter
                        foreach ($classes as $class) {
                            $id = $class['id']; // Assuming you have the 'id' in your $class array

                            echo "<tr>";
                            echo "<td>{$counter}</td>"; // Serial number
                            echo "<td>" . htmlspecialchars($class['class_title']) . "</td>"; // Class Title
                            echo "<td>" . htmlspecialchars($class['class_description']) . "</td>"; // Class Description
                            echo "<td>
                                    <a href='classRoom.php?online_class_id=" . urlencode($id) . "' >Join Class</a>
                                </td>"; // Class Link
                            echo "<td>
                                    <div class='btn-group' role='group'>
                                        <a href='editOnlineClass.php?id={$id}' class='btn btn-info btn-sm' data-toggle='tooltip' title='Edit Class'>
                                            Update
                                        </a>
                                        <button class='btn btn-danger btn-md delete-btn' data-id='{$id}' data-bs-toggle='tooltip' title='Delete Class'>
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
                            // Fetch request to delete the class
                            fetch(`../endpoints/delete_online_class.php?id=${id}`, {
                                    method: 'GET'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Deleted!',
                                            'Class record has been deleted.',
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Manage Online Classes'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>