<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

$timetableId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables for subject form
$day = '';
$start_time = '';
$end_time = '';
$subject_code = '';
$edit_id = null; // For holding the ID of the entry being edited

// Function to display SweetAlert

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day'], $_POST['start_time'], $_POST['end_time'], $_POST['subject_code'])) {
    $day = $_POST['day'];
    $start_time = $_POST['start_time'] . ':00';
    $end_time = $_POST['end_time'] . ':00';
    $subject_code = $_POST['subject_code'];
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null; // Get the ID if editing

    // Check if an entry already exists for the same day and time (except for the current edit)
    $checkQuery = "SELECT * FROM timetable_entries WHERE timetable_id = ? AND day = ? AND start_time = ? AND end_time = ? AND id != ?";
    $checkStmt = $connection->prepare($checkQuery);
    $checkStmt->bind_param("isssi", $timetableId, $day, $start_time, $end_time, $edit_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        showSweetAlert('warning', 'Duplicate Entry', 'This time slot already has a subject assigned.', null);
    } else {
        // Update the subject code in timetable_entries or insert a new entry if edit_id is null
        if ($edit_id) {
            // Update existing entry
            $updateQuery = "UPDATE timetable_entries SET subject_code = ? WHERE id = ?";
            $stmt = $connection->prepare($updateQuery);
            $stmt->bind_param("si", $subject_code, $edit_id);
            $success = $stmt->execute();
            $stmt->close();
        } else {
            // Insert new entry
            $insertQuery = "INSERT INTO timetable_entries (timetable_id, day, start_time, end_time, subject_code) 
                            VALUES (?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($insertQuery);
            $stmt->bind_param("issss", $timetableId, $day, $start_time, $end_time, $subject_code);
            $success = $stmt->execute();
            $stmt->close();
        }

        showSweetAlert(
            $success ? 'success' : 'error',
            $success ? 'Success!' : 'Error!',
            $success ? 'Subject saved successfully.' : 'Failed to save subject. Please try again.',
            $_SERVER['REQUEST_URI']
        ); // Redirect to the same page to refresh
    }
}

// Fetch class and arm names based on timetable ID
$classQuery = "
    SELECT c.class_name, a.arm_name 
    FROM set_timetables st
    JOIN classes c ON st.class_id = c.class_id
    JOIN arms a ON st.arm_id = a.arm_id
    WHERE st.id = $timetableId
";
$classResult = mysqli_query($connection, $classQuery);
$class = mysqli_fetch_assoc($classResult);
$class_name = $class['class_name'] ?? 'Class not found';
$arm_name = $class['arm_name'] ?? 'Arm not found';

// Fetch timetable details
$query = "SELECT * FROM set_timetables WHERE id = $timetableId";
$result = $connection->query($query);
$time_slots = [];
$periods = [];
if ($row = $result->fetch_assoc()) {
    $start_time = strtotime($row['start_time']);
    $end_time = strtotime($row['end_time']);
    $duration = $row['duration'];
    $periods_count = $row['periods'];
    $break_types = explode(',', $row['break_types']);

    $current_time = $start_time;


    if ($periods_count > 0) {
        for ($i = 0; $i < $periods_count; $i++) {
            // Calculate the next time based on duration
            $next_time = strtotime("+{$duration} minutes", $current_time);

            // If the calculated end time exceeds the timetable end time, adjust it
            if ($next_time > $end_time) {
                $next_time = $end_time; // Set the last period end time to the timetable end time
            }

            // Add the time slot to the array
            $time_slots[] = [
                'start' => $current_time,
                'end' => $next_time,
                'start_formatted' => date('H:i:s', $current_time),
                'end_formatted' => date('H:i:s', $next_time)
            ];

            // Break if we've reached the end time
            if ($next_time == $end_time) {
                break;
            }

            // Check for breaks and increment current_time as needed
            if ($i == 1 && in_array('SHORT BREAK', $break_types)) { // First break after first period
                $current_time = strtotime("+20 minutes", $next_time);
                $time_slots[] = [
                    'start' => $next_time,
                    'end' => $current_time,
                    'break' => 'SHORT BREAK',
                    'start_formatted' => date('H:i:s', $next_time),
                    'end_formatted' => date('H:i:s', $current_time)
                ];
            } elseif ($i == ($periods_count - 5) && in_array('RECESS', $break_types)) { // Second break before last period
                $current_time = strtotime("+10 minutes", $next_time);
                $time_slots[] = [
                    'start' => $next_time,
                    'end' => $current_time,
                    'break' => 'RECESS',
                    'start_formatted' => date('H:i:s', $next_time),
                    'end_formatted' => date('H:i:s', $current_time)
                ];
            } elseif ($i == ($periods_count - 4) && in_array('LONG BREAK', $break_types)) { // Last break before final period
                $current_time = strtotime("+30 minutes", $next_time);
                $time_slots[] = [
                    'start' => $next_time,
                    'end' => $current_time,
                    'break' => 'LONG BREAK',
                    'start_formatted' => date('H:i:s', $next_time),
                    'end_formatted' => date('H:i:s', $current_time)
                ];
            } else {
                // Move to the next time slot if no break is added
                $current_time = $next_time;
            }
        }
    }


    // Initialize periods array with days
    $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
    foreach ($days as $day) {
        $periods[$day] = array_fill(0, count($time_slots), ['subject_code' => '']);
    }

    // Fetch timetable entries and populate periods array
    $entryQuery = "SELECT * FROM timetable_entries WHERE timetable_id = $timetableId";
    $entriesResult = $connection->query($entryQuery);
    while ($entry = $entriesResult->fetch_assoc()) {
        $day = $entry['day'];
        foreach ($time_slots as $index => $slot) {
            if ($entry['start_time'] == $slot['start_formatted'] && $entry['end_time'] == $slot['end_formatted']) {
                $periods[$day][$index]['subject_code'] = $entry['subject_code'];
                $periods[$day][$index]['edit_id'] = $entry['id']; // Store the ID for editing
                break;
            }
        }
    }
}

// At this point, $time_slots and $periods are ready to be used in your HTML output.
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Timetable</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: Arial, sans-serif;
        }

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

        #modal,
        #modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #modal {
            background: white;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
        }

        .subject-cell {
            text-align: center;
            /* Center text horizontally */
            vertical-align: middle;
            /* Center text vertically */
            padding: 15px;
            /* Add padding around the content */
            font-size: 14px;
            /* Set font size (adjust as needed) */
            font-weight: bold;
            /* Make text bold */
            border: 1px solid #ddd;
            /* Add a light border (optional) */
            background-color: #f9f9f9;
            /* Set a light background color */
            height: 60px;
            /* Set a specific height for the cell */
        }
    </style>
</head>

<body>
    <div id="modal-overlay" onclick="closeModal()"></div>
    <div id="modal">
        <h4>Add/Edit Subject</h4>
        <form id="edit-form" method="POST">
            <input type="hidden" name="day" id="modal-day" />
            <input type="hidden" name="start_time" id="modal-start" />
            <input type="hidden" name="end_time" id="modal-end" />
            <input type="hidden" name="edit_id" id="modal-edit-id" />
            <div class="mb-3">
                <label for="subject_code" class="form-label">Subject Code</label>
                <input type="text" class="form-control" name="subject_code" id="modal-subject" required />
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        </form>
    </div>

    <div class="container mt-2">
        <h3 class="text-center mb-2">Timetable for <?php echo $class_name . ' - ' . $arm_name; ?></h3>
        <div class="table-responsive">
            <table id="assignmentsTable" class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Time</th>
                        <?php foreach ($periods as $day => $slots): ?>
                            <th><?php echo $day; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $index => $slot): ?>
                        <tr>
                            <td class="" style="font-weight:bolder; text-align: center; vertical-align: middle; padding: 10px;">
                                <?php echo $slot['start_formatted'] . ' - ' . $slot['end_formatted']; ?>
                            </td>
                            <?php if (isset($slot['break'])): ?>
                                <td class='subject-cell text-danger' colspan="<?php echo count($periods); ?>"
                                    onclick='openModal(this)'
                                    style="text-align: center; vertical-align: middle; padding: 15px;">
                                    <?php
                                    // Create a spaced-out string with larger gaps
                                    $spacedBreak = implode('&nbsp;&nbsp;&nbsp;&nbsp;', str_split($slot['break'])); // Add multiple non-breaking spaces
                                    echo $spacedBreak; // Display the break name with increased spacing
                                    ?>
                                </td>
                            <?php else: ?>
                                <?php foreach ($periods as $day => $slots): ?>
                                    <td class='subject-cell'
                                        data-day='<?php echo $day; ?>'
                                        data-start-time='<?php echo $slot['start_formatted']; ?>'
                                        data-end-time='<?php echo $slot['end_formatted']; ?>'
                                        onclick='openModal(this)'
                                        style="text-align: center; vertical-align: middle; padding: 15px;">
                                        <?php echo $slots[$index]['subject_code']; ?>
                                        <input type="hidden" value="<?php echo $slots[$index]['edit_id'] ?? ''; ?>" />
                                    </td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>


            </table>
        </div>
    </div>

    <script>
        function openModal(cell) {
            document.getElementById('modal-day').value = cell.getAttribute('data-day');
            document.getElementById('modal-start').value = cell.getAttribute('data-start-time');
            document.getElementById('modal-end').value = cell.getAttribute('data-end-time');
            document.getElementById('modal-subject').value = cell.innerText.trim(); // Set the current subject code
            document.getElementById('modal-edit-id').value = cell.querySelector('input[type="hidden"]').value; // Get edit ID
            document.getElementById('modal').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        }
    </script>
</body>

</html>