<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch the result visibility date from settings
$visibility_query = "SELECT DATE(result_visibility_date) as result_visibility_date FROM settings WHERE id = 1"; // Extract only the date part
$visibility_result = mysqli_query($connection, $visibility_query);

if ($visibility_result) {
    $row = mysqli_fetch_assoc($visibility_result);
    $result_visibility_date = $row['result_visibility_date'] ?? ''; // Now it's in 'Y-m-d' format
} else {
    die("Error fetching result visibility date: " . mysqli_error($connection)); // Error handling
}

// Check if the current date is on or after the result visibility date
$current_date = date('Y-m-d'); // Get current date in 'Y-m-d' format

// Initialize the results variable
$results = [];

if ($current_date >= $result_visibility_date) {
    // Fetch the student's results only if the visibility date has passed or is today
    $student_id = mysqli_real_escape_string($connection, $_SESSION['student_id']); // Escaping student_id for security

    $query = "
        SELECT 
            subjects.subject_name,
            exams.exam_title,
            exam_attempts.score,
            exams.total_marks,
            exams.pass_mark
        FROM 
            exam_attempts
        JOIN 
            exams ON exams.id = exam_attempts.exam_id
        JOIN 
            subjects ON subjects.id = exams.subject_id
        WHERE 
            exam_attempts.student_id = '$student_id'
        ORDER BY 
            exam_attempts.created_at DESC
    ";

    $result = mysqli_query($connection, $query);

    if ($result) {
        $results = mysqli_fetch_all($result, MYSQLI_ASSOC); // Fetch all results for easier processing
    } else {
        die("Error fetching results: " . mysqli_error($connection)); // Error handling for query failure
    }
} else {
    // Results are not available yet
    $results = [];
}
?>


<style>
    .text-center {
        text-align: center;
    }

    .text-muted {
        color: #6c757d;
        /* Bootstrap muted color */
        font-size: 16px;
        /* Adjust size as necessary */
    }

    .result-date {
        font-weight: bold;
        /* Makes the date stand out */
        color: red;
        /* Darker color for emphasis */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container mt-5">
            <div class="card-header bg-gradient-light text-white text-center">
                <h2 class="m-3">Results</h2>
            </div>

            <?php if ($current_date < date('Y-m-d', strtotime($result_visibility_date))): ?>
                <div class="text-center">
                    <p class="text-muted text-danger">Results will be available from <span class="result-date"><?php echo htmlspecialchars(date('F j, Y', strtotime($result_visibility_date))); ?></span>.</p>
                </div>

            <?php elseif (count($results) > 0): ?>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th scope="col">Subject</th>
                                <th scope="col">Exam Title</th>
                                <th scope="col">Score</th>
                                <th scope="col">Pass Mark</th>
                                <th scope="col">Total Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['exam_title']); ?></td>
                                    <td><?php echo number_format((float)$row['score'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['pass_mark']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_marks']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">No results found. Please complete your exams to see your results.</p>
                </div>
            <?php endif; ?>

        </div>

        <?php include("../includes/footer.php"); ?>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'My Results'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>