<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming the teacher's ID is stored in the session after login
$teacherId = $_SESSION['staff_id'] ?? null; // Use null for admin if needed

// Initialize arrays to hold subjects, classes, and arms
$subjects = [];
$classes = [];
$arms = [];

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin';

// Handle form submission for adding a new break type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['breakTypeForm'])) {
    $new_type = mysqli_real_escape_string($connection, $_POST['newBreakType']);
    $sql = "INSERT INTO break_types (type_name) VALUES ('$new_type')";
    if (mysqli_query($connection, $sql)) {
        showSweetAlert('success', 'Success', 'Break type added successfully', 'setTimetable.php');
    } else {
        showSweetAlert('error', 'Error', 'Error creating break type: ' . mysqli_error($connection), 'setTimetable.php');
    }
}

$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row;
    }
}

$sql = "SELECT * FROM break_types";
$result = mysqli_query($connection, $sql);
$break_types = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle timetable form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['breakTypeForm'])) {
    $class = mysqli_real_escape_string($connection, $_POST['class']);
    $arm = mysqli_real_escape_string($connection, $_POST['arm']);
    $periods = mysqli_real_escape_string($connection, $_POST['periods']);
    $startTime = mysqli_real_escape_string($connection, $_POST['startTime']);
    $endTime = mysqli_real_escape_string($connection, $_POST['endTime']);
    $duration = mysqli_real_escape_string($connection, $_POST['duration']);

    // Concatenate break types into a comma-separated string
    $breakTypes = isset($_POST['breakType']) ? implode(',', array_map(function ($type) use ($connection) {
        return mysqli_real_escape_string($connection, $type);
    }, $_POST['breakType'])) : '';

    // Insert timetable data into the set_timetables table, including break types
    $timetableSql = "INSERT INTO set_timetables (class_id, arm_id, periods, start_time, end_time, duration, break_types)
                     VALUES ('$class', '$arm', '$periods', '$startTime', '$endTime', '$duration', '$breakTypes')";

    if (mysqli_query($connection, $timetableSql)) {
        showSweetAlert('success', 'Success', 'Timetable set successfully', 'manageTimetable.php');
    } else {
        showSweetAlert('error', 'Error', 'Error creating timetable: ' . mysqli_error($connection), 'setTimetable.php');
    }
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png" />
    <title>Set Timetable</title>
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

        .input-group-text {
            background-color: #f8f9fa;
            /* Light background for the button */
            border: 1px solid #ced4da;
            /* Match the dropdown border */
            border-radius: 0.375rem 0 0 0.375rem;
            /* Rounded edges on the left */
            cursor: pointer;
            /* Pointer cursor */
        }

        .input-group-text:hover {
            background-color: #e2e6ea;
            /* Change background on hover */
        }

        .input-group .form-select {
            border-radius: 0 0.375rem 0.375rem 0;
            /* Rounded edges on the right */
        }


        .overlay {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        /* Overlay Content */
        .overlay-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            max-width: 500px;
        }

        /* Close button */
        .closebtn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .closebtn:hover {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>

</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>
        <div class="container py-4">
            <div class="col-md-10">
                <h4 class="text-center text-primary mb-4">Set Class Timetable</h4>
                <form id="addTimetableForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled selected>Select a class</option>
                                <?php
                                if (!empty($classes)) {
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class['class_id']) . '">' . htmlspecialchars($class['class_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No classes available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled selected>Select an arm</option>
                                <?php
                                if (!empty($arms)) {
                                    foreach ($arms as $arm) {
                                        echo '<option value="' . htmlspecialchars($arm['arm_id']) . '">' . htmlspecialchars($arm['arm_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No arms available</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="periods" class="form-label">Number of Periods</label>
                            <input type="number" id="periods" name="periods" class="form-control" min="1" max="10" required>
                        </div>

                        <div class="col-md-4">
                            <label for="startTime" class="form-label">Start Time</label>
                            <input type="time" id="startTime" name="startTime" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="endTime" class="form-label">End Time</label>
                            <input type="time" id="endTime" name="endTime" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Period Duration (Minutes)</label>
                            <input type="number" id="duration" name="duration" class="form-control" min="10" max="60" required>
                        </div>
                        <div class="col-md-6">
                            <label for="breakType" class="form-label">Break Type</label>
                            <a href="#" class="input-group-text" onclick="openOverlay(); return false;">
                                <i class="fa fa-plus mx-2"></i> Add Break Types
                            </a>
                            <div class="input-group">
                                <select class="form-select" id="breakType" name="breakType[]" multiple required>
                                    <option value="" disabled selected>Select one or more</option>
                                    <?php foreach ($break_types as $type): ?>
                                        <option value="<?= htmlspecialchars($type['type_name']) ?>">
                                            <?= htmlspecialchars($type['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary">Submit</button>
                </form>

                <?php include("../includes/footer.php"); ?>
            </div>
        </div>

        <!-- Break Type Form Overlay -->
        <div id="breakTypeOverlay" class="overlay">
            <div class="overlay-content bg-white rounded shadow">
                <span class="closebtn" onclick="closeOverlay()">&times;</span>
                <h2 class="text-center mb-4">Add Break Type</h2>
                <form action="setTimeTable.php" id="breakTypeForm" method="POST">
                    <div class="mb-3">
                        <label for="newBreakType" class="form-label">Break Type</label>
                        <input type="text" id="newBreakType" name="newBreakType" class="form-control" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary" name="breakTypeForm">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        function showSpinner() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Loading...';
            submitBtn.disabled = true;
        }
    </script>

    <script>
        function openOverlay() {
            document.getElementById("breakTypeOverlay").style.display = "block";
        }

        function closeOverlay() {
            document.getElementById("breakTypeOverlay").style.display = "none";
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Set Timetable';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>