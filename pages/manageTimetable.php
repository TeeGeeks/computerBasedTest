<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch timetable entries
function fetchTimetables()
{
    global $connection;
    $query = "SELECT * FROM set_timetables";
    $result = mysqli_query($connection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$timetables = fetchTimetables(); // Fetch all timetable entries
$classes = fetchClasses(); // Fetch all classes
$arms = fetchArms(); // Fetch all arms

// Helper functions to get class and arm names by IDs
function getClassNameById($classes, $classId)
{
    foreach ($classes as $class) {
        if ($class['class_id'] == $classId) {
            return $class['class_name'];
        }
    }
    return 'Unknown';
}

function getArmNameById($arms, $armId)
{
    foreach ($arms as $arm) {
        if ($arm['arm_id'] == $armId) {
            return $arm['arm_name'];
        }
    }
    return 'Unknown';
}
?>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container-fluid">
            <h4 class="mt-4 text-primary">Manage Timetables</h4>
            <a href="setTimetable.php" class="btn btn-primary mb-3">Set New Timetable</a>

            <div class="table-responsive">
                <table id="assignmentsTable" class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>S.No.</th>
                            <th>Class</th>
                            <th>Arm</th>
                            <th>Periods</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Duration (mins)</th>
                            <th>Break Types</th>
                            <th>Add Timetable</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($timetables as $timetable) {
                            $id = $timetable['id'];
                            $class_id = $timetable['class_id'];
                            $arm_id = $timetable['arm_id'];
                            $periods = $timetable['periods'];
                            $start_time = $timetable['start_time'];
                            $end_time = $timetable['end_time'];
                            $duration = $timetable['duration'];
                            $break_types = $timetable['break_types'];

                            $class_name = getClassNameById($classes, $class_id);
                            $arm_name = getArmNameById($arms, $arm_id);

                            echo "<tr>";
                            echo "<td>{$counter}</td>";
                            echo "<td>{$class_name}</td>";
                            echo "<td>{$arm_name}</td>";
                            echo "<td>{$periods}</td>";
                            echo "<td>{$start_time}</td>";
                            echo "<td>{$end_time}</td>";
                            echo "<td>{$duration}</td>";
                            echo "<td>{$break_types}</td>";
                            echo "<td>
                            <div class='btn-group' role='group'>
                                <a href='addTimetable.php?id={$id}' class='text-primary' data-toggle='tooltip' title='Edit Timetable' target='_blank'>
                                    Add Timetable
                                </a>

                            </div>
                          </td>";
                            echo "<td>
                                <div class='btn-group' role='group'>
                                   <a href='editTimetable.php?id={$id}' class='btn btn-info btn-sm' data-toggle='tooltip' title='Edit Timetable'>
                                        Edit
                                    </a>
                                    <button class='btn btn-danger btn-md delete-btn' data-id='{$id}' data-bs-toggle='tooltip' title='Delete Timetable'>
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

    <script>
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
                            // Fetch request to delete the timetable entry
                            fetch(`../endpoints/delete_timetable.php?id=${id}`, {
                                    method: 'GET'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Deleted!',
                                            'Timetable record has been deleted.',
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
            const currentPage = 'Manage Timetable';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>