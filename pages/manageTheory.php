<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch theory exams based on the user's role
function fetchTheoryExamsByRole($teacherId, $isAdmin)
{
    global $connection; // Use the global connection variable

    // If the user is an admin, fetch all theory exams; otherwise, fetch only the teacher's theory exams
    if ($isAdmin) {
        $query = "SELECT id, class_id, arm_id, subject_id, exam_title, exam_date, exam_time, exam_duration FROM theory_exams"; // Fetch all theory exams for admin
    } else {
        $query = "SELECT id, class_id, arm_id, subject_id, exam_title, exam_date, exam_time, exam_duration FROM theory_exams WHERE teacher_id = '$teacherId'"; // Fetch exams for the specific teacher
    }

    $result = mysqli_query($connection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC); // Return all exams as an associative array
}

$teacherId = $_SESSION['staff_id'] ?? ''; // Get the teacher's ID from the session
$isAdmin = $_SESSION['user_role'] === 'admin'; // Check if the user is an admin
$exams = fetchTheoryExamsByRole($teacherId, $isAdmin); // Fetch theory exams based on the user's role
$subjects = fetchSubjects(); // Fetch all subjects
$classes = fetchClasses(); // Fetch all classes
$arms = fetchArms(); // Fetch all arms

// Helper function to get the subject name by subject ID
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
        if ($arm['arm_id'] == $arm_id) { // Use arm_id here
            return $arm['arm_name']; // Return the arm name if the ID matches
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
            <h4 class="mt-4 text-primary">Theory Exam List</h4>
            <a href="addTheory.php" class="btn btn-primary mb-3">Add New Theory Exam</a>
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
                            <th>Exam Title</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Duration (mins)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $counter = 1; // Initialize counter
                        foreach ($exams as $exam) {
                            $id = $exam['id'] ?? 'N/A'; // Exam ID
                            $class_id = $exam['class_id'] ?? 'N/A'; // Use default if not set
                            $arm_id = $exam['arm_id'] ?? 'N/A'; // Use default if not set
                            $exam_title = $exam['exam_title'] ?? 'N/A'; // Exam title
                            $subject_id = $exam['subject_id'] ?? 'N/A'; // Subject ID
                            $date = $exam['exam_date'] ?? 'N/A'; // Exam date
                            $examTime = $exam['exam_time'] ?? 'N/A'; // Exam time
                            $examDuration = $exam['exam_duration'] ?? 'N/A'; // Exam duration

                            // Get the subject name by subject_id
                            $subject_name = getSubjectNameById($subjects, $subject_id);
                            // Get the class name by class_id
                            $class_name = getClassNameById($classes, $class_id);
                            $arm_name = getArmNameById($arms, $arm_id);
                        ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($class_name); ?></td>
                                <td><?php echo htmlspecialchars($arm_name); ?></td>
                                <td><?php echo htmlspecialchars($exam_title); ?></td>
                                <td><?php echo htmlspecialchars($subject_name); ?></td>
                                <td><?php echo htmlspecialchars($date); ?></td>
                                <td><?php echo htmlspecialchars($examDuration); ?></td>
                                <td>
                                    <a href="editTheory.php?id=<?php echo $id; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <button class='btn btn-danger btn-md delete-btn' data-id='{$id}' data-bs-toggle='tooltip' title='Delete Exam'>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
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
                            // Fetch request to delete the exam
                            fetch(`../endpoints/delete_theory_exam.php?id=${id}`, {
                                    method: 'GET'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Deleted!',
                                            'Exam record has been deleted.',
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