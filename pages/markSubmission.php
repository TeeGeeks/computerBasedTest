<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get submission ID from query parameter
$submissionId = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : null;

if (!$submissionId) {
    showSweetAlert('error', 'Invalid Request', 'Submission ID is required.', 'viewAssignmentSubmission.php');
    exit;
}

// Initialize variables for handling the form submission
$marksSubmitted = false;
$existingMarks = null; // Variable to hold existing marks
$existingComments = ''; // Variable to hold existing teacher comments

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marks = isset($_POST['marks']) ? intval($_POST['marks']) : null;
    $teacherComments = isset($_POST['teacher_comments']) ? trim($_POST['teacher_comments']) : '';
    $submissionId = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : null;

    // Validate inputs
    if ($marks === null || $marks < 0 || $marks > 100 || empty($teacherComments)) {
        showSweetAlert('error', 'Invalid Input', 'Please enter valid marks and comments.', 'markSubmission.php?submission_id=' . $submissionId);
        exit;
    }

    // Update the submission with marks and comments
    $updateQuery = "
        UPDATE assignment_submissions 
        SET marks = ?, teacher_comments = ? 
        WHERE submission_id = ?";

    $stmt = $connection->prepare($updateQuery);
    $stmt->bind_param("isi", $marks, $teacherComments, $submissionId);

    if ($stmt->execute()) {
        $marksSubmitted = true;
        showSweetAlert('success', 'Submission Marked', 'The submission has been successfully marked.', 'viewAssignmentSubmission.php');
    } else {
        showSweetAlert('error', 'Database Error', 'Failed to update the submission.', 'markSubmission.php?submission_id=' . $submissionId);
    }

    $stmt->close();
}

// Fetch submission details
$submissionQuery = "
    SELECT asub.*, 
           CONCAT(s.surname, ' ', s.other_names) AS student_name, 
           c.class_name, 
           ar.arm_name, 
           a.assignment_title 
    FROM assignment_submissions asub
    JOIN students s ON asub.student_id = s.id
    JOIN classes c ON s.class_id = c.class_id
    JOIN assignments a ON asub.assignment_id = a.assignment_id
    JOIN arms ar ON a.arm_id = ar.arm_id
    WHERE asub.submission_id = $submissionId";

$submissionResult = mysqli_query($connection, $submissionQuery);
$submission = mysqli_fetch_assoc($submissionResult);

if (!$submission) {
    showSweetAlert('error', 'Submission Not Found', 'Could not find the specified submission.', 'viewAssignmentSubmission.php');
    exit;
}

// Check if existing marks and comments are present
$existingMarks = $submission['marks'] ?? null;
$existingComments = $submission['teacher_comments'] ?? '';

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
        margin-bottom: 30px;
    }

    .detail-group {
        display: flex;
        justify-content: space-between;
        font-size: 1.1rem;
        padding: 10px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .detail-group strong {
        color: #34495e;
    }

    .submission-image {
        display: flex;
        justify-content: center;
        margin: 20px 0;
    }

    .submission-image img {
        width: 100%;
        max-width: 300px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .comments-box {
        padding: 20px;
        background-color: #f5f5f5;
        border-radius: 10px;
        font-size: 1rem;
        line-height: 1.6;
        margin-top: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .form-control {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .d-grid .btn {
        border-radius: 0.375rem;
    }

    footer {
        margin-top: 80px;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container mt-2">
            <h1><?php echo htmlspecialchars($submission['assignment_title']); ?></h1>

            <!-- Submission Details (Student Name, Class, Arm) -->
            <div class="detail-group">
                <div><strong>Student:</strong> <?php echo htmlspecialchars($submission['student_name']); ?></div>
                <div><strong>Class:</strong> <?php echo htmlspecialchars($submission['class_name']); ?></div>
            </div>

            <div class="detail-group">
                <div><strong>Arm:</strong> <?php echo htmlspecialchars($submission['arm_name']); ?></div>
                <div><strong>Submission Date:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($submission['created_at']))); ?></div>
            </div>

            <!-- Submission File -->
            <div class="submission-image">
                <?php if ($submission['file_path']): ?>
                    <img src="<?php echo htmlspecialchars($submission['file_path']); ?>" class="img-fluid" alt="Submission File">
                <?php else: ?>
                    <div class="bg-secondary text-white p-5 rounded">No File Available</div>
                <?php endif; ?>
            </div>

            <!-- Comments Box -->
            <div class="comments-box">
                <strong>Assignment Description:</strong>
                <p><?php echo nl2br(strip_tags($submission['student_comments'])); ?></p>
            </div>

            <!-- Marking Section -->
            <div class="container mt-2">
                <div class="text-center">
                    <h2>Marking Submission</h2>
                    <form action="" method="POST">
                        <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission['submission_id']); ?>">

                        <div class="form-group">
                            <label for="marks">Marks:</label>
                            <input type="number" name="marks" id="marks" class="form-control" min="0" max="100"
                                value="<?php echo htmlspecialchars($existingMarks); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="teacher_comments">Teacher's Comments:</label>
                            <textarea name="teacher_comments" id="teacher_comments" class="form-control" rows="4" required><?php echo htmlspecialchars($existingComments); ?></textarea>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">Submit Marks</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <footer>
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Mark Submission'; // Update dynamically
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>