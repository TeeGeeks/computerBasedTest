<?php
// Database connection
include('generalFnc.php');
$conn = connection();

// Sample user role management
$user_role = $_SESSION['user_role'] ?? ''; // 'admin', 'staff', 'student'


// Sample user role management
$user_role = $_SESSION['user_role'] ?? ''; // 'admin', 'staff', 'student'

if ($user_role == 'admin') {
    // Fetch total counts for admin dashboard
    $total_students_query = "SELECT COUNT(*) as total_students FROM students";
    $total_students_result = mysqli_query($conn, $total_students_query);
    $total_students = mysqli_fetch_assoc($total_students_result)['total_students'];

    $total_teachers_query = "SELECT COUNT(*) as total_teachers FROM teachers";
    $total_teachers_result = mysqli_query($conn, $total_teachers_query);
    $total_teachers = mysqli_fetch_assoc($total_teachers_result)['total_teachers'];

    $total_exams_query = "SELECT COUNT(*) as total_exams FROM exams";
    $total_exams_result = mysqli_query($conn, $total_exams_query);
    $total_exams = mysqli_fetch_assoc($total_exams_result)['total_exams'];

    // Fetch upcoming exams with class and arm
    $upcoming_exams_query = "
        SELECT e.exam_title, e.exam_date, e.exam_time, c.class_name, a.arm_name
        FROM exams e
        JOIN classes c ON e.class_id = c.class_id
        JOIN arms a ON e.arm_id = a.arm_id
        WHERE e.exam_date >= CURDATE()
        ORDER BY e.exam_date, e.exam_time
    ";
    $upcoming_exams_result = mysqli_query($conn, $upcoming_exams_query);

    // Prepare to display the upcoming exams data
    if ($upcoming_exams_result) {
        $upcoming_exams = [];
        while ($row = mysqli_fetch_assoc($upcoming_exams_result)) {
            $upcoming_exams[] = $row;
        }
    }
}



$teacher_id = $_SESSION['staff_id'] ?? ''; // Assume the teacher ID is stored in session after login

if ($user_role == 'staff') {
    // Fetch teacher data
    $teacher_query = "SELECT surname, other_names, qualification, email, phone_number, profile_image FROM teachers WHERE teacher_id = $teacher_id";
    $teacher_result = mysqli_query($conn, $teacher_query);
    $teacher = mysqli_fetch_assoc($teacher_result);

    // Fetch upcoming exams with class names directly
    $exam_query = "
        SELECT e.exam_title, e.exam_date, e.exam_time, c.class_name
        FROM exams e
        JOIN classes c ON e.class_id = c.class_id
        WHERE e.teacher_id = $teacher_id AND e.exam_date >= CURDATE()
        ORDER BY e.exam_date, e.exam_time
    ";
    $exams_result = mysqli_query($conn, $exam_query) ?? "";
}

// Define default image
$defaultImage = BASE_URL . 'assets/img/drake.jpg'; // Path to your default image
$profileImage = !empty($teacher['profile_image']) ? BASE_URL . $teacher['profile_image'] : $defaultImage;


$student_id = $_SESSION['student_id'] ?? ''; // Assume the student ID is stored in session after login

if ($user_role == 'student') {
    // Fetch student data
    $student_query = "SELECT surname, other_names, email, phone_number, class_id, arm_id, profile_image FROM students WHERE id = $student_id";
    $student_result = mysqli_query($conn, $student_query);
    $student = mysqli_fetch_assoc($student_result);

    // Fetch student's class name
    $class_query = "SELECT class_name FROM classes WHERE class_id = " . $student['class_id'];
    $class_result = mysqli_query($conn, $class_query);
    $class = mysqli_fetch_assoc($class_result);

    // Fetch student's class name
    $arm_query = "SELECT arm_name FROM arms WHERE arm_id = " . $student['arm_id'];
    $arm_result = mysqli_query($conn, $arm_query);
    $arm = mysqli_fetch_assoc($arm_result);

    // Fetch upcoming exams for the student's class and arm
    $exam_query = "
SELECT e.exam_title, e.exam_date, e.exam_time, c.class_name, a.arm_name
FROM exams e
JOIN classes c ON e.class_id = c.class_id
JOIN arms a ON e.arm_id = a.arm_id  
WHERE e.class_id = " . $student['class_id'] . " 
AND e.arm_id = " . $student['arm_id'] . " 
AND e.exam_date >= CURDATE()
ORDER BY e.exam_date, e.exam_time
";

    $exams_result = mysqli_query($conn, $exam_query);

    // Define default image
    $defaultImage1 = BASE_URL . 'assets/img/drake.jpg'; // Path to your default image
    $profileImage1 = !empty($student['profile_image']) ? BASE_URL . $student['profile_image'] : $defaultImage1;
}
?>



<?php if ($user_role == 'admin') { ?>
    <div class="row">

        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-header p-3 pt-2">
                    <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                        <i class="material-icons opacity-10">people</i>
                    </div>
                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize">Total Students</p>
                        <h4 class="mb-0"><?php echo $total_students; ?></h4>
                    </div>
                </div>
                <hr class="dark horizontal my-0" />
                <div class="card-footer p-3">
                    <p class="mb-0">
                        <a href="<?php echo BASE_URL; ?>/pages/manageStudent.php" class="text-primary text-sm">View All Students</a>
                    </p>
                    <p class="mb-0">
                        <span class="text-success text-sm font-weight-bolder">+5% </span>from last month
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-header p-3 pt-2 ">
                    <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                        <i class="material-icons opacity-10">person</i>
                    </div>

                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize">Total Teachers</p>
                        <h4 class="mb-0"><?php echo $total_teachers; ?></h4>
                    </div>
                </div>
                <hr class="dark horizontal my-0" />
                <div class="card-footer p-3">
                    <p class="mb-0">
                        <a href="<?php echo BASE_URL; ?>/pages/manageTeacher.php" class="text-primary text-sm">View All Teachers</a>
                    </p>
                    <p class="mb-0">
                        <span class="text-warning text-sm">Consider recruiting more teachers.</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Card for Total Exams -->
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card shadow">
                <div class="card-header p-3 pt-2 ">
                    <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                        <i class="material-icons opacity-10">assignment</i>
                    </div>
                    <div class="text-end pt-1 ms-4">
                        <p class="text-sm mb-0 text-capitalize">Total Exams</p>
                        <h4 class="mb-0"><?php echo $total_exams; ?></h4>
                    </div>
                </div>
                <hr class="dark horizontal my-0" />
                <div class="card-footer p-3">
                    <p class="mb-0">
                        Upcoming exams
                    </p>
                    <p class="mb-0">
                        <span class="text-success text-sm">Next exam on: </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Card for Total Assignments -->
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card shadow">
                <div class="card-header p-3 pt-2 ">
                    <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                        <i class="material-icons opacity-10">weekend</i>
                    </div>
                    <div class="text-end pt-1 ms-4">
                        <p class="text-sm mb-0 text-capitalize">Total Assignments</p>
                        <h4 class="mb-0">10</h4>
                    </div>
                </div>
                <hr class="dark horizontal my-0" />
                <div class="card-footer p-3">
                    <p class="mb-0">
                        <a href="#" class="text-primary text-sm">View All Assignments</a>
                    </p>
                    <p class="mb-0">
                        <span class="text-success text-sm font-weight-bolder">+5% </span>from yesterday
                    </p>

                </div>
            </div>
        </div>
    </div>
    <div style="margin-top: 250px;">

    </div>
    <!-- <div class="row mt-4">
        <div class="col-lg-4 col-md-6 mt-4 mb-4">
            <div class="card z-index-2">
                <div
                    class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                    <div
                        class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                        <div class="chart">
                            <canvas
                                id="chart-bars"
                                class="chart-canvas"
                                height="170"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-0">Website Views</h6>
                    <p class="text-sm">Last Campaign Performance</p>
                    <hr class="dark horizontal" />
                    <div class="d-flex">
                        <i class="material-icons text-sm my-auto me-1">schedule</i>
                        <p class="mb-0 text-sm">campaign sent 2 days ago</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mt-4 mb-4">
            <div class="card z-index-2">
                <div
                    class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                    <div
                        class="bg-gradient-success shadow-success border-radius-lg py-3 pe-1">
                        <div class="chart">
                            <canvas
                                id="chart-line"
                                class="chart-canvas"
                                height="170"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-0">Daily Sales</h6>
                    <p class="text-sm">
                        (<span class="font-weight-bolder">+15%</span>) increase in
                        today sales.
                    </p>
                    <hr class="dark horizontal" />
                    <div class="d-flex">
                        <i class="material-icons text-sm my-auto me-1">schedule</i>
                        <p class="mb-0 text-sm">updated 4 min ago</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mt-4 mb-3">
            <div class="card z-index-2">
                <div
                    class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                    <div
                        class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                        <div class="chart">
                            <canvas
                                id="chart-line-tasks"
                                class="chart-canvas"
                                height="170"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-0">Completed Tasks</h6>
                    <p class="text-sm">Last Campaign Performance</p>
                    <hr class="dark horizontal" />
                    <div class="d-flex">
                        <i class="material-icons text-sm my-auto me-1">schedule</i>
                        <p class="mb-0 text-sm">just updated</p>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
<?php } ?>


<?php if ($user_role == 'staff') { ?>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .profile-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }

        .card-title {
            font-weight: bold;
            font-size: 1.5rem;
        }
    </style>
    <div class="container py-2">
        <div class="bg-light text-center">
            <h3>Teacher Dashboard</h3>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <div class="profile-card text-center">
                    <img src="<?php echo $profileImage; ?>" alt="Profile Picture" class="profile-img mb-3">
                    <h2 class="card-title"><?php echo htmlspecialchars($teacher['surname'] . ' ' . $teacher['other_names']); ?></h2>
                    <p class="text-muted">Qualification: <?php echo htmlspecialchars($teacher['qualification']); ?></p>
                    <p class="text-secondary">Email: <?php echo htmlspecialchars($teacher['email']); ?></p>
                    <p class="text-secondary">Phone: <?php echo htmlspecialchars($teacher['phone_number']); ?></p>

                    <div class="d-flex justify-content-center mt-4">
                        <a href="<?php echo BASE_URL; ?>pages/editTeacher.php?id=<?php echo $teacher_id; ?>" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subjects Assigned to Teacher -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h4>Subjects Assigned</h4>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>S.No.</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Arm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            // Query to fetch subjects assigned to the teacher
                            $subjects_query = "
                    SELECT s.subject_name, c.class_name, a.arm_name 
                    FROM subject_assignments sa
                    JOIN subjects s ON sa.subject_id = s.id
                    JOIN classes c ON sa.class_id = c.class_id
                    JOIN arms a ON sa.arm_id = a.arm_id
                    WHERE sa.teacher_id = ?";
                            $stmt = $conn->prepare($subjects_query);
                            $stmt->bind_param("i", $teacher_id);
                            $stmt->execute();
                            $subjects_result = $stmt->get_result();

                            if ($subjects_result->num_rows > 0):
                                while ($subject = $subjects_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($counter++); ?></td>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['arm_name']); ?></td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <p class="text-center">No subjects assigned.</p>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Upcoming Exams Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h4>Upcoming Exams</h4>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>S.No.</th>
                                <th>Exam Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($exams_result) > 0): $counter = 1; ?>
                                <?php while ($exam = mysqli_fetch_assoc($exams_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($counter++); ?></td>
                                        <td><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['exam_date']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['exam_time']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['class_name']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center">No upcoming exams found.</p>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
<?php } ?>

<?php if ($user_role == 'student') { ?>

    <style>
        body {
            background-color: #f4f6f9;
        }

        .dashboard-container {
            padding: 20px;
        }

        .student-card {
            border: 1px solid #dcdcdc;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .student-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
        }

        .card-header {
            font-weight: bold;
            font-size: 1.6rem;
            color: #333;
        }

        .info-text {
            font-size: 1.1rem;
            color: #555;
        }

        .exam-table {
            margin-top: 20px;
        }

        .exam-table thead {
            background-color: #007bff;
            color: #fff;
        }

        .btn-view-results {
            background-color: #28a745;
            color: #fff;
            border-radius: 5px;
            padding: 10px 20px;
            text-decoration: none;
        }

        .btn-view-results:hover {
            background-color: #218838;
        }
    </style>

    <div class="container dashboard-container">
        <div class="text-center bg-light text-white py-1">
            <h3>Student Dashboard</h3>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="student-card text-center">
                    <img src="<?php echo $profileImage1; ?>" alt="Profile Picture" class="student-img mb-3">
                    <h2 class="card-header"><?php echo htmlspecialchars($student['surname'] . ' ' . $student['other_names']); ?></h2>
                    <!-- Fetch class_name from $class array -->
                    <p class="info-text">Class: <?php echo htmlspecialchars($class['class_name']); ?><?php echo htmlspecialchars($arm['arm_name']); ?></p>
                    <p class="info-text">Email: <?php echo htmlspecialchars($student['email']); ?></p>
                    <p class="info-text">Phone: <?php echo htmlspecialchars($student['phone_number']); ?></p>

                    <div class="d-flex justify-content-center mt-4">
                        <a href="<?php echo BASE_URL; ?>pages/editStudent.php?id=<?php echo $student_id; ?>" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h4>Upcoming Exams</h4>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Exam Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($exams_result) > 0): ?>
                                <?php while ($exam = mysqli_fetch_assoc($exams_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['exam_date']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['exam_time']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['class_name']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center">No upcoming exams found.</p>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


<?php } ?>