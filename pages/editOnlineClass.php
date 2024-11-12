<?php
include("../config.php");
include_once("../includes/generalFnc.php");
include('../session.php');

require_once '../vendor/autoload.php'; // Ensure the Google API Client Library is loaded

$connection = connection();

$teacherId = $_SESSION['staff_id'] ?? ''; // Get teacher ID from session
$subjects = [];
$classes = [];
$arms = [];

// Check if the logged-in user is an admin or a teacher
$isAdmin = $_SESSION['user_role'] === 'admin'; // Check if the user is an admin

// Initialize Google Client
$client = new Google\Client();
$client->setClientId('716277205590-1immvi4l82fegqls7gn3e7bseoal80rj.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/cbt/pages/createOnlineClass.php');
$client->addScope(Google\Service\Calendar::CALENDAR);

// Check if we already have an access token
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
} else {
    // If we don't have the access token yet
    if (isset($_GET['code'])) {
        // Fetch the access token using the authorization code
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $_SESSION['access_token'] = $token; // Save access token in session
    } else {
        // Redirect to Google OAuth 2.0 authorization page
        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }
}

// Check if token is expired
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        // Refresh the access token
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $_SESSION['access_token'] = $client->getAccessToken(); // Save new access token
    } else {
        // If no refresh token is available, force re-authentication
        unset($_SESSION['access_token']); // Clear the expired token
        $authUrl = $client->createAuthUrl(); // Create a new authorization URL
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }
}

// Fetch subjects
$subjectQuery = "SELECT id, subject_name FROM subjects";
$subjectResult = mysqli_query($connection, $subjectQuery);
if ($subjectResult) {
    while ($row = mysqli_fetch_assoc($subjectResult)) {
        $subjects[] = $row; // Store each subject in the array
    }
}

// Fetch classes
$classQuery = "SELECT class_id, class_name FROM classes";
$classResult = mysqli_query($connection, $classQuery);
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

// Fetch arms
$armQuery = "SELECT arm_id, arm_name FROM arms";
$armResult = mysqli_query($connection, $armQuery);
if ($armResult) {
    while ($row = mysqli_fetch_assoc($armResult)) {
        $arms[] = $row; // Store each arm in the array
    }
}

// Check if an ID is provided to edit
$classId = $_GET['id'] ?? null;
// Initialize formData with default values to avoid undefined index warnings
$formData = [
    'class_id' => '',
    'arm_id' => '',
    'subject_id' => '',
    'class_title' => '',
    'class_description' => '',
    'class_date' => '',
    'class_time' => '',
    'class_duration' => ''
];

// Fetch existing class details for the given class ID if available
if ($classId) {
    $classQuery = "SELECT * FROM online_classes WHERE id = ?";
    $stmt = $connection->prepare($classQuery);
    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $formData = array_merge($formData, $result->fetch_assoc());
    } else {
        showSweetAlert('error', 'Error', 'Class not found.');
        exit;
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateClass'])) {
    // Capture form input values
    $formData = [
        'id' => $classId,
        'class' => $_POST['class'],
        'arm' => $_POST['arm'],
        'subject' => $_POST['subject'],
        'classTitle' => trim($_POST['classTitle']),
        'classDescription' => trim($_POST['classDescription']),
        'classDate' => $_POST['classDate'],
        'classTime' => trim($_POST['classTime']) . ':00',
        'classDuration' => intval($_POST['classDuration'])
    ];

    // Validate class duration
    if ($formData['classDuration'] <= 0) {
        showSweetAlert('error', 'Invalid Duration', 'Class duration must be greater than zero.');
        exit;
    }

    // Prepare start and end date-time for the event
    $startDateTime = $formData['classDate'] . 'T' . $formData['classTime'];
    $endDateTime = date('Y-m-d\TH:i:s', strtotime($startDateTime) + ($formData['classDuration'] * 60));

    // Prepare the event data
    $eventData = [
        'summary' => $formData['classTitle'],
        'description' => $formData['classDescription'],
        'start' => [
            'dateTime' => $startDateTime,
            'timeZone' => 'Africa/Lagos',
        ],
        'end' => [
            'dateTime' => $endDateTime,
            'timeZone' => 'Africa/Lagos',
        ],
        'conferenceData' => [
            'createRequest' => [
                'requestId' => uniqid(),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
            ]
        ],
    ];
    // Create the event in Google Calendar
    try {
        $service = new Google\Service\Calendar($client);
        $event = new Google\Service\Calendar\Event($eventData);
        $calendarId = 'primary';
        $event = $service->events->insert($calendarId, $event, ['conferenceDataVersion' => 1]);
        $classLink = $event->getHangoutLink(); // Get the Google Meet link
    } catch (Exception $e) {
        error_log('Google Calendar API error: ' . $e->getMessage());
        showSweetAlert('error', 'Google API Error', 'Failed to create Google Calendar event: ' . $e->getMessage());
        exit;
    }

    // Escape form inputs before updating in the database
    $classTitle = mysqli_real_escape_string($connection, $formData['classTitle']);
    $classDescription = mysqli_real_escape_string($connection, $formData['classDescription']);
    $classTime = mysqli_real_escape_string($connection, $formData['classTime']);
    $classDate = mysqli_real_escape_string($connection, $formData['classDate']);

    // Determine teacher ID (if admin, leave as NULL)
    // $teacherId = $formData['teacher_id'] ?? "";


    $query = "UPDATE online_classes 
          SET  
              class_id = " . intval($formData['class']) . ", 
              arm_id = " . intval($formData['arm']) . ", 
              subject_id = " . intval($formData['subject']) . ", 
              class_title = '$classTitle', 
              class_description = '$classDescription', 
              class_link = '$classLink', 
              class_date = '$classDate', 
              class_time = '$classTime', 
              class_duration = " . intval($formData['classDuration']) . " 
          WHERE id = " . intval($formData['id']);


    if (mysqli_query($connection, $query)) {
        showSweetAlert('success', 'Class Updated', 'Your online class has been updated successfully!', 'manageOnlineClass.php');
    } else {
        error_log('Database Error: ' . mysqli_error($connection)); // Log database errors
        showSweetAlert('error', 'Error', 'There was an error updating the class. Please try again.');
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
    <title>Computer Based Test</title>
    <!-- Fonts and icons -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <!-- Nucleo Icons -->
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
    <!-- CSS Files -->
    <link id="pagestyle" href="<?php echo BASE_URL; ?>assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/mycss.css">
    <!-- Place the first <script> tag in your HTML's <head> -->
    <script src="https://cdn.tiny.cloud/1/k014ifj5itll2amihajv2u2mwio7wbnpg1g0yg2fmr1f3m8u/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

    <link href="https://fonts.googleapis.com/css2?family=STIX+Two+Math&display=swap" rel="stylesheet">
    <style>
        .math {
            font-family: 'STIX Two Math', serif;
            /* Or whatever your main text font is */
        }
    </style>

</head>
<!-- HTML FORM -->

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
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-10">
                <h4 class="text-center mb-4">Edit Online Class</h4>

                <form id="editClassForm" action="" method="POST" onsubmit="showSpinner()">
                    <div class="row">
                        <!-- Class Dropdown -->
                        <div class="col-md-4 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="" disabled>Select a class</option>
                                <?php
                                if (!empty($classes)) {
                                    foreach ($classes as $class) {
                                        $selected = ($formData['class_id'] == $class['class_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($class['class_id']) . '" ' . $selected . '>' . htmlspecialchars($class['class_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No classes available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Arm Dropdown -->
                        <div class="col-md-4 mb-3">
                            <label for="arm" class="form-label">Arm</label>
                            <select class="form-select" id="arm" name="arm" required>
                                <option value="" disabled>Select an arm</option>
                                <?php
                                if (!empty($arms)) {
                                    foreach ($arms as $arm) {
                                        $selected = ($formData['arm_id'] == $arm['arm_id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($arm['arm_id']) . '" ' . $selected . '>' . htmlspecialchars($arm['arm_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No arms available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Subject Dropdown -->
                        <div class="col-md-4 mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="" disabled>Select a subject</option>
                                <?php
                                if (!empty($subjects)) {
                                    foreach ($subjects as $subject) {
                                        $selected = ($formData['subject_id'] == $subject['id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($subject['id']) . '" ' . $selected . '>' . htmlspecialchars($subject['subject_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No subjects available</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Class Title -->
                        <div class="col-md-3 mb-3">
                            <label for="classTitle" class="form-label">Class Title</label>
                            <input type="text" class="form-control" id="classTitle" name="classTitle" value="<?= htmlspecialchars($formData['class_title']) ?>" required>
                        </div>

                        <!-- Class Date -->
                        <div class="col-md-3 mb-3">
                            <label for="classDate" class="form-label">Class Date</label>
                            <input type="date" class="form-control" id="classDate" name="classDate" value="<?= htmlspecialchars($formData['class_date']) ?>" required>
                        </div>

                        <!-- Class Time -->
                        <div class="col-md-3 mb-3">
                            <label for="classTime" class="form-label">Class Time (HH:MM AM/PM)</label>
                            <input type="time" class="form-control" id="classTime" name="classTime" value="<?= htmlspecialchars($formData['class_time']) ?>" required>
                        </div>

                        <!-- Class Duration -->
                        <div class="col-md-3 mb-3">
                            <label for="classDuration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="classDuration" name="classDuration" value="<?= htmlspecialchars($formData['class_duration']) ?>" required min="1" placeholder="e.g., 30">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Class Description -->
                        <div class="col-md-12 mb-3">
                            <label for="classDescription" class="form-label">Class Description</label>
                            <textarea class="form-control" id="classDescription" name="classDescription" rows="3" required><?= htmlspecialchars($formData['class_description']) ?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="submit" name="updateClass" class="btn btn-primary">Update Class</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        function showSpinner() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            `;
            submitBtn.disabled = true; // Disable the button to prevent multiple submissions
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Edit Online Class';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>