<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get student ID from session
$studentId = $_SESSION['student_id'] ?? null;

// Fetch assignment results for the student
$query = "
    SELECT asub.*, 
           CONCAT(s.surname, ' ', s.other_names) AS student_name, 
           a.assignment_title, 
           a.due_date, 
           s.class_id,
           c.class_name,
           ar.arm_name
    FROM assignment_submissions asub
    JOIN students s ON asub.student_id = s.id
    JOIN assignments a ON asub.assignment_id = a.assignment_id
    JOIN classes c ON s.class_id = c.class_id
    JOIN arms ar ON a.arm_id = ar.arm_id
    WHERE asub.student_id = $studentId
    ORDER BY a.due_date DESC";

$result = mysqli_query($connection, $query);
$assignments = mysqli_fetch_all($result, MYSQLI_ASSOC);

if (!$assignments) {
    echo "<p>No assignments found.</p>";
}

function formatDate($date)
{
    return date('d-m-Y', strtotime($date)); // Nigerian date format (DD-MM-YYYY)
}
?>

<style>
    .container {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px;
    }

    h1 {
        font-size: 2.5rem;
        font-weight: bold;
        text-align: center;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
    }

    .btn-edit {
        color: #E91E63;
        text-decoration: none;
        padding: 5px 10px;
        border: 1px solid #E91E63;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .btn-edit:hover {
        background-color: #E91E63;
        color: white;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container mt-2">
            <h1>Your Assignment Results</h1>
            <?php if (!empty($assignments)): ?>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Title</th>
                                <th>Marks</th>
                                <th>Teacher's Comments</th>
                                <th>Submission Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['assignment_title']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['marks']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($assignment['teacher_comments'])); ?></td>
                                    <td><?php echo formatDate($assignment['created_at']); ?></td>
                                    <td><?php echo formatDate($assignment['due_date']); ?></td>
                                    <td>
                                        <?php
                                        if ($assignment['marks'] === null) {
                                            echo "Not Marked";
                                        } else {
                                            echo "Marked";
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        // Check if the assignment is not marked and not due
                                        if (is_null($assignment['marks']) && strtotime($assignment['due_date']) > time()): ?>
                                            <a href="editSubmission.php?submission_id=<?php echo $assignment['submission_id']; ?>" class="btn-edit">Edit</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No assignments found.</p>
            <?php endif; ?>
        </div>

        <footer class="mt-5">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
</body>