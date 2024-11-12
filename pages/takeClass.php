<?php
include("../config.php");
include("../includes/header.php");
include("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch the student's class and arm based on their session ID
$studentId = $_SESSION['student_id']; // Assuming student_id is stored in session
$studentQuery = "SELECT class_id, arm_id FROM students WHERE id = $studentId LIMIT 1";
$studentResult = mysqli_query($connection, $studentQuery);
$studentData = mysqli_fetch_assoc($studentResult);

$classId = $studentData['class_id'];
$armId = $studentData['arm_id'];

// Fetch online classes based on student's class and arm
$onlineClassesQuery = "
    SELECT oc.id AS class_id, oc.class_title, oc.class_link, oc.class_date, oc.class_time, oc.class_duration, s.subject_name 
    FROM online_classes oc
    JOIN subjects s ON oc.subject_id = s.id
    WHERE oc.class_id = $classId AND oc.arm_id = $armId
    ORDER BY oc.class_title";

$onlineClassesResult = mysqli_query($connection, $onlineClassesQuery);

?>

<style>
    h4 {
        margin-bottom: 20px;
        color: #333;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .no-classes {
        text-align: center;
        font-style: italic;
        color: #888;
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
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <h4>View Online Classes</h4>

            <!-- Display Online Classes -->
            <div class="table-responsive">
                <table id="assignmentsTable" class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>S.No.</th>
                            <th>Subject</th>
                            <th>Class Title</th>
                            <th>Class Date</th>
                            <th>Class Time</th>
                            <th>Class Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($onlineClassesResult) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php while ($onlineClass = mysqli_fetch_assoc($onlineClassesResult)): ?>
                                <tr>
                                    <td><?php echo $counter; ?></td>
                                    <td><?php echo htmlspecialchars($onlineClass['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($onlineClass['class_title']); ?></td>
                                    <td><?php echo strip_tags($onlineClass['class_date']); ?></td>
                                    <td><?php echo strip_tags($onlineClass['class_time']); ?></td>
                                    <td><?php echo strip_tags($onlineClass['class_duration']); ?></td>
                                    <td>
                                        <a href="classRoomStudent.php?online_class_id=<?php echo urlencode($onlineClass['class_id']); ?>" class="text-primary">Join Class</a>
                                    </td>
                                </tr>
                                <?php $counter++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No online classes available.</p>
                        <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'View Online Classes';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>