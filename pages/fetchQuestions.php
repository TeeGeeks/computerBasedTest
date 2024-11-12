<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$exams = fetchExams(); // Fetch all exams

function fetchQuestionsByRole($connection)
{
    // Assuming the teacher's ID is stored in the session after login
    $teacherId = $_SESSION['staff_id'];
    $role = $_SESSION['user_role']; // User role

    if ($role === 'admin') {
        // Fetch all questions if the user is an admin
        $query = "SELECT * FROM questions";
    } else {
        // Fetch questions only for the logged-in teacher
        $query = "SELECT * FROM questions WHERE teacher_id = '$teacherId'";
    }

    $result = mysqli_query($connection, $query);
    $questions = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $questions[] = $row; // Store each question in the array
        }
    }

    return $questions;
}


// Fetch questions based on teacher ID or admin role
$questions = fetchQuestionsByRole($connection); // Pass connection to fetchQuestions

// Helper function to get the exam title by exam ID
function getExamTitleById($exams, $examId)
{
    foreach ($exams as $exam) {
        if ($exam['id'] == $examId) {
            return $exam['exam_title']; // Return the exam title if the ID matches
        }
    }
    return 'Unknown'; // Return a default value if no match is found
}
?>

<style>
    .text-left {
        text-align: left !important;
        /* Align text to the left */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container-fluid">
            <h4 class="mt-4">Manage Questions</h4>
            <a href="addQuestion.php" class="btn btn-primary mb-3">Add New Question</a>
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
                            <th>Exam Title</th>
                            <th>Question Text</th>
                            <th>Options</th>
                            <th>Correct Answer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $counter = 1; // Initialize counter
                        foreach ($questions as $question) {
                            $id = $question['id'] ?? null; // Question ID
                            $exam_id = $question['exam_id'] ?? null; // Exam ID associated with the question
                            $question_text = $question['question_text'] ?? ''; // Question text
                            $options = [
                                $question['option_a'] ?? '',
                                $question['option_b'] ?? '',
                                $question['option_c'] ?? '',
                                $question['option_d'] ?? ''
                            ]; // Options array
                            $correct_answer = $question['correct_answer'] ?? ''; // Correct answer

                            // Get the exam title by exam_id
                            $exam_title = getExamTitleById($exams, $exam_id);

                            echo "<tr>";
                            echo "<td>{$counter}</td>"; // Serial number
                            echo "<td>{$exam_title}</td>"; // Exam Title
                            echo "<td class='text-left'>{$question_text}</td>"; // Question Text

                            // Format options with letters (A, B, C, D)
                            $formatted_options = [];
                            $letters = ['A', 'B', 'C', 'D'];
                            foreach ($options as $key => $option) {
                                if (!empty($option)) {
                                    $formatted_options[] = "({$letters[$key]}) " . htmlspecialchars($option);
                                }
                            }

                            echo "<td class='text-left'>" . implode("<br>", $formatted_options) . "</td>"; // Options
                            echo "<td>{$correct_answer}</td>"; // Correct Answer
                            echo "<td>
                    <div class='btn-group' role='group'>
                        <a href='editQuestion.php?id={$id}' class='btn btn-info btn-sm' data-toggle='tooltip' title='Edit Question'>
                            Update
                        </a>
                        <button class='btn btn-danger btn-md delete-btn' data-id='{$id}' data-bs-toggle='tooltip' title='Delete Question'>
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
            const rows = document.querySelectorAll('#assignmentsTable tbody tr'); // Corrected to the right table ID
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
                            // Fetch request to delete the question
                            fetch(`../endpoints/delete_question.php?id=${id}`, {
                                    method: 'GET'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Deleted!',
                                            'Question has been deleted.',
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

        // Update breadcrumbs
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Manage Questions'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>