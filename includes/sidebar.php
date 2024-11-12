<?php

// Sample user role management (could be stored after login)
$user_role = $_SESSION['user_role'] ?? ''; // 'admin', 'staff', 'student'

?>


<aside
    class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark"
    id="sidenav-main">
    <div class="sidenav-header">
        <i
            class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true"
            id="iconSidenav"></i>
        <a
            class="navbar-brand m-0"
            href="<?php echo BASE_URL; ?>dashboard.php">
            <img
                src="<?php echo BASE_URL; ?>assets/img/logo-ct.png"
                class="navbar-brand-img h-100"
                alt="main_logo" />
            <span class="ms-1 font-weight-bold text-white">Dashboard</span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2" />
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">

            <!-- Admin Links -->
            <?php if ($user_role == 'admin') { ?>
                <!-- Class Management -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Class
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="classDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">class</i>
                        </div>
                        <span class="nav-link-text ms-1">Classes</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="classDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addClass.php">Add Class</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageClass.php">Manage Class</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addArm.php">Add Arm</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageArm.php">Manage Arm</a></li>
                    </ul>
                </li>

                <!-- Subject Management -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Subject
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="subjectDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">menu_book</i>
                        </div>
                        <span class="nav-link-text ms-1">Subjects</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="subjectDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addSubject.php">Add Subject</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageSubject.php">Manage Subjects</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/assignSubject.php">Assign Subject To Teacher</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageAssignment.php">Manage Assignment </a></li>
                    </ul>
                </li>

                <!-- Teacher Management -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Teacher
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="teacherDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">school</i>
                        </div>
                        <span class="nav-link-text ms-1">Teachers</span>
                    </a>

                    <ul class="dropdown-menu" aria-labelledby="teacherDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addTeacher.php">Add Teacher</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageTeacher.php">Manage Teacher</a></li>
                    </ul>
                </li>

                <!-- Student Management -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Student
                    </h6>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?php echo BASE_URL; ?>pages/addStudent.php">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">group_add</i>
                        </div>
                        <span class="nav-link-text ms-1">Add Student</span>
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="teacherDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">manage_accounts</i>
                        </div>
                        <span class="nav-link-text ms-1">Manage Student</span>
                    </a>

                    <ul class="dropdown-menu" aria-labelledby="teacherDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/studentsID.php">Students ID Card</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/allStudentsID.php" target="_blank">All Students ID Card</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/fetchStudentsByClass.php">View All Students By Class</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageStudent.php">All Students</a></li>
                    </ul>
                </li>

                <!-- Exam Management -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Exam
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="examDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">quiz</i>
                        </div>
                        <span class="nav-link-text ms-1">Exams</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="examDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addExam.php">Add Exam</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageExam.php">Manage Exams</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addTheory.php">Add Theory</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageTheory.php">Manage Theory</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/setDateOnlineResult.php">Set Date For Online Result</a></li>
                    </ul>
                </li>


                <!-- Question Management -->

                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="questionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">help</i>
                        </div>
                        <span class="nav-link-text ms-1">Questions</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="questionDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addQuestion.php">Add Objectives Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addTheoryQuestion.php">Add Theory Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/importQuestion.php">Import Objectives Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/importTheoryQuestion.php">Import Theory Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addImageToQuestion.php">Add Image To Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageQuestion.php">Manage Objectives Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageTheoryQuestion.php">Manage Theory Question</a></li>
                    </ul>
                </li>


                <!-- Robotics Management -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Robotics
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="roboticsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">android</i>
                        </div>
                        <span class="nav-link-text ms-1">Robotics</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="roboticsDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addRobotics.php">Add Robotics Content</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageRobotics.php">Manage Robotics Content</a></li>
                    </ul>
                </li>

                <!-- Coding Management -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Coding
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="codingDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">code</i>
                        </div>
                        <span class="nav-link-text ms-1">Coding</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="codingDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addCoding.php">Add Coding Content</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageCoding.php">Manage Coding Content</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewSubmissions.php">View Coding Submissions</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/codingChallenges.php">Coding Challenges</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/codingReports.php">Coding Reports</a></li>
                    </ul>
                </li>


                <!-- E-Learning Management -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        E-Learning
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="elearningDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">school</i>
                        </div>
                        <span class="nav-link-text ms-1">E-Learning</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="elearningDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addAssignment.php">Add Assignments</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageAss.php">Manage Assignments</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewAssignmentSubmission.php">View Assignments Submitted</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/createOnlineClass.php">Create Online Classes </a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageOnlineClass.php">ManageOnline Classes </a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/lessonNotes.php">Lesson Notes</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/schemeOfWork.php">Scheme of Work</a></li>
                    </ul>
                </li>

                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Timetable
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="timetableDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">schedule</i>
                        </div>
                        <span class="nav-link-text ms-1">Timetable</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="timetableDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/setTimetable.php">Set Timetable</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageTimetable.php">Manage Timetable</a></li>
                    </ul>
                </li>

                <!-- Attendance Management -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Attendance
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="attendanceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">check_circle</i>
                        </div>
                        <span class="nav-link-text ms-1">Attendance</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="attendanceDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/markAttendance.php">Mark Attendance</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewAttendance.php">View Attendance</a></li>
                    </ul>
                </li>


                <!-- Reports and Results -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Reports
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="reportsDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">assessment</i>
                        </div>
                        <span class="nav-link-text ms-1">Results</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewResultsAdmin.php">View Results</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewTheory.php">View Theory</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/generateReportAdmin.php">Generate Reports</a></li>
                    </ul>
                </li>

                <!-- Account Pages -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Account
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="accountDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">person</i>
                        </div>
                        <span class="nav-link-text ms-1">Profile</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="accountDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/adminProfile.php">View Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                    </ul>
                </li>
            <?php } ?>

            <!-- Staff Links -->
            <?php if ($user_role == 'staff') {
                $teacher_id = $_SESSION['staff_id']; // Assume the teacher ID is stored in session after login
            ?>


                <!-- Exam Management -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Exam
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="examDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">quiz</i>
                        </div>
                        <span class="nav-link-text ms-1">Exams</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="examDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addExam.php">Add Exam</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageExam.php">Manage Exams</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addTheory.php">Add Theory</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageTheory.php">Manage Theory</a></li>
                    </ul>
                </li>


                <!-- Question Management -->

                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="questionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">help</i>
                        </div>
                        <span class="nav-link-text ms-1">Questions</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="questionDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addQuestion.php">Add Objectives Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addTheoryQuestion.php">Add Theory Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/importQuestion.php">Import Objectives Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/importTheoryQuestion.php">Import Theory Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addImageToQuestion.php">Add Image To Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageQuestion.php">Manage Objectives Question</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageTheoryQuestion.php">Manage Theory Question</a></li>
                    </ul>
                </li>


                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="elearningDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">school</i>
                        </div>
                        <span class="nav-link-text ms-1">E-Learning</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="elearningDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/addAssignment.php">Add Assignments</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageAss.php">Manage Assignments</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewAssignmentSubmission.php">View Assignments Submitted</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/createOnlineClass.php">Create Online Classes </a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/manageOnlineClass.php">Manage Online Classes </a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/lessonNotes.php">Lesson Notes</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/schemeOfWork.php">Scheme of Work</a></li>
                    </ul>
                </li>


                <!-- Reports and Results -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Reports
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="reportsDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">assessment</i>
                        </div>
                        <span class="nav-link-text ms-1">Results</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewStudentResults.php">View Results</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewTheory.php">View Theory</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/generateReport.php">Generate Reports</a></li>
                    </ul>
                </li>

                <!-- Account Pages -->
                <li class="nav-item mt-3">
                    <h6
                        class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Account
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link text-white dropdown-toggle"
                        href="#"
                        id="accountDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div
                            class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">person</i>
                        </div>
                        <span class="nav-link-text ms-1">Profile</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="accountDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/editTeacher.php?id=<?php echo $teacher_id; ?>">Edit Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                    </ul>
                </li>
            <?php } ?>


            <!-- Student Links -->
            <?php if ($user_role == 'student') {
                $student_id = $_SESSION['student_id'];

            ?>

                <!-- Exam Management -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Exam Management
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="examStudentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">quiz</i>
                        </div>
                        <span class="nav-link-text ms-1">Exams</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="examStudentDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewExams.php">View Available Exams</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/selectExam.php">Take Exam</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="elearningDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">school</i>
                        </div>
                        <span class="nav-link-text ms-1">E-Learning</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="elearningDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewAssignment.php">View Assignments</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/submitAssignment.php">Submit Assignments</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewAssResult.php">Assignments Result</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/takeClass.php">Take Classes Online</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/lessonNotesStud.php">Lesson Notes</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/schemeOfWorkStud.php">Scheme of Work</a></li>
                    </ul>
                </li>
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Manage Timetable
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="timetableDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">schedule</i>
                        </div>
                        <span class="nav-link-text ms-1">Timetable</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="timetableDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewTimetable.php" target="_blank">View Timetable</a></li>
                    </ul>
                </li>
                <!-- Results -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Results
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="resultsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">assessment</i>
                        </div>
                        <span class="nav-link-text ms-1">View Results</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="resultsDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewMyResults.php">View Objectives Results</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/viewMyTheoryResults.php">View Theory Results</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/generateStudentReport.php">Generate Reports</a></li>
                    </ul>
                </li>

                <!-- Account Pages -->
                <li class="nav-item mt-3">
                    <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">
                        Account
                    </h6>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-white dropdown-toggle" href="#" id="studentAccountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">person</i>
                        </div>
                        <span class="nav-link-text ms-1">Profile</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="studentAccountDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/idCard.php">ID Card</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/editStudent.php?id=<?php echo $student_id; ?>">Edit Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                    </ul>
                </li>

            <?php } ?>



        </ul>
    </div>
</aside>