<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin';
$teacherId = $_SESSION['staff_id'] ?? ''; // Adjust this according to your session management

// Fetch subjects and classes
$subjects = [];
$classes = [];
$arms = [];


$subjectQuery = "SELECT id, subject_name FROM subjects";

$subjectResult = mysqli_query($connection, $subjectQuery);
while ($row = mysqli_fetch_assoc($subjectResult)) {
    $subjects[] = $row;
}

// Fetch classes
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
while ($row = mysqli_fetch_assoc($classResult)) {
    $classes[] = $row;
}

// Fetch arms
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
while ($row = mysqli_fetch_assoc($armResult)) {
    $arms[] = $row;
}

// Handle form submission to fetch scheme of work
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClass = $_POST['class'] ?? '';
    $selectedArm = $_POST['arm'] ?? '';
    $selectedSubject = $_POST['subject'] ?? '';

    // Fetch scheme of work based on selected options
    $schemeQuery = "
        SELECT sow.*, s.subject_name, c.class_name, a.arm_name 
        FROM scheme_of_work sow 
        LEFT JOIN subjects s ON sow.subject_id = s.id 
        LEFT JOIN classes c ON sow.class_id = c.class_id 
        LEFT JOIN arms a ON sow.arm_id = a.arm_id
        WHERE sow.class_id = '$selectedClass' 
        AND sow.arm_id = '$selectedArm' 
        AND sow.subject_id = '$selectedSubject'";

    if (!$isAdmin) {
        $schemeQuery .= " AND sow.teacher_id = '$teacherId'";
    }

    $result = mysqli_query($connection, $schemeQuery);
    while ($row = mysqli_fetch_assoc($result)) {
        $schemeOfWork[] = $row;
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png" />
    <title>Scheme Of Work</title>
    <!-- Fonts and icons -->
    <!-- <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" /> -->
    <!-- Nucleo Icons -->
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/core/fontawsomeicon.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/material-icons.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
    <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" /> -->
    <!-- CSS Files -->
    <link id="pagestyle" href="<?php echo BASE_URL; ?>assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/mycss.css">
    <!-- Place the first <script> tag in your HTML's <head> -->
    <script src="<?php echo BASE_URL; ?>assets/js/tinymce/tinymce/tinymce.min.js">
    </script>
    <!-- <script src="https://cdn.tiny.cloud/1/k014ifj5itll2amihajv2u2mwio7wbnpg1g0yg2fmr1f3m8u/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script> -->

    <!-- <link href="https://fonts.googleapis.com/css2?family=STIX+Two+Math&display=swap" rel="stylesheet"> -->

    <style>
        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        .d-grid .btn {
            border-radius: 0.375rem;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>
        <div class="container py-4">
            <h4 class="text-center text-primary mb-4">Manage Scheme of Work</h4>
            <a href="addSchemeOfWork.php" class="btn btn-warning mb-5">Add New Scheme of Work</a>
            <form id="manageSchemeOfWorkForm" action="" method="POST">
                <div class="row mb-3">
                    <!-- Class Dropdown -->
                    <div class="col-md-4">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class" required>
                            <option value="" disabled selected>Select a class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= htmlspecialchars($class['class_id']) ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Arm Dropdown -->
                    <div class="col-md-4">
                        <label for="arm" class="form-label">Arm</label>
                        <select class="form-select" id="arm" name="arm" required>
                            <option value="" disabled selected>Select an arm</option>
                            <?php foreach ($arms as $arm): ?>
                                <option value="<?= htmlspecialchars($arm['arm_id']) ?>"><?= htmlspecialchars($arm['arm_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Subject Dropdown -->
                    <div class="col-md-4">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="" disabled selected>Select a subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject['id']) ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Fetch Scheme Of Work Notes</button>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($schemeOfWork)): ?>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>S/N</th>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Arm</th>
                                <th>Attachment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schemeOfWork as $index => $scheme): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($scheme['scheme_title']); ?></td>
                                    <td><?php echo htmlspecialchars($scheme['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($scheme['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($scheme['arm_name']); ?></td>
                                    <td>
                                        <?php if (!empty($scheme['attachment_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($scheme['attachment_path']); ?>" download>Download</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="editSchemeOfWork.php?id=<?php echo $scheme['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $scheme['id']; ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <p class="mt-4 text-primary">No scheme of work found for the selected options.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
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
                            fetch(`../endpoints/delete_scheme_of_work.php?id=${id}`, {
                                    method: 'GET'
                                }).then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Deleted!', 'Scheme of work has been deleted.', 'success')
                                            .then(() => location.reload());
                                    } else {
                                        Swal.fire('Error!', 'Failed to delete the scheme of work.', 'error');
                                    }
                                }).catch(error => {
                                    Swal.fire('Error!', 'Server request failed.', 'error');
                                });
                        }
                    });
                });
            });
        });
    </script>

    <?php include("../includes/script.php"); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Manage Scheme of Work';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>