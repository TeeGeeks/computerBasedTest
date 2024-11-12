<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get assignment ID from query parameter
$assignmentId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$assignmentId) {
    showSweetAlert('error', 'Invalid Request', 'Assignment ID is required.', 'viewAssignment.php');
    exit;
}

// Fetch assignment details
$assignmentQuery = "
    SELECT a.assignment_id, a.assignment_title, a.assignment_description, 
           a.due_date, a.file_path, s.subject_name, c.class_name, ar.arm_name
    FROM assignments a
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.class_id
    JOIN arms ar ON a.arm_id = ar.arm_id
    WHERE a.assignment_id = $assignmentId";

$assignmentResult = mysqli_query($connection, $assignmentQuery);
$assignment = mysqli_fetch_assoc($assignmentResult);

if (!$assignment) {
    showSweetAlert('error', 'Assignment Not Found', 'Could not find the specified assignment.', 'viewAssignment.php');
    exit;
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

    .assignment-image {
        display: flex;
        justify-content: center;
        margin: 20px 0;
    }

    .assignment-image img {
        width: 100%;
        max-width: 300px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .description-box {
        padding: 20px;
        background-color: #f5f5f5;
        border-radius: 10px;
        font-size: 1rem;
        line-height: 1.6;
        margin-top: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }



    footer {
        margin-top: 80px;
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container mt-3">
            <h1><?php echo htmlspecialchars($assignment['assignment_title']); ?></h1>

            <!-- Assignment Details (Class, Due Date, Subject, Arm) -->
            <div class="detail-group">
                <div><strong>Class:</strong> <?php echo htmlspecialchars($assignment['class_name']); ?></div>
                <div><strong>Due Date:</strong> <?php echo htmlspecialchars($assignment['due_date']); ?></div>
            </div>

            <div class="detail-group">
                <div><strong>Subject:</strong> <?php echo htmlspecialchars($assignment['subject_name']); ?></div>
                <div><strong>Arm:</strong> <?php echo htmlspecialchars($assignment['arm_name']); ?></div>
            </div>

            <!-- Assignment Image -->
            <div class="assignment-image">
                <?php if ($assignment['file_path']): ?>
                    <img src="<?php echo htmlspecialchars($assignment['file_path']); ?>" class="img-fluid" alt="Assignment Image">
                <?php else: ?>
                    <div class="bg-secondary text-white p-5 rounded">No Image Available</div>
                <?php endif; ?>
            </div>

            <!-- Assignment Description -->
            <div class="description-box">
                <?php echo nl2br(strip_tags($assignment['assignment_description'])); ?>
            </div>

            <!-- Download and Back Buttons -->
            <div class="text-center mt-4">
                <?php if ($assignment['file_path']): ?>
                    <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="btn btn-primary">Download File</a>
                    <a href="submitAssignment.php" class="btn btn-secondary">Submit Assignment</a>
                <?php endif; ?>
            </div>

        </div>

        <footer>
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Assignment Details'; // Update dynamically
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>