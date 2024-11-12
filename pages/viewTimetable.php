<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch student's class and arm using student_id
$studentId = $_SESSION['student_id'];
$classQuery = "
    SELECT c.class_name, a.arm_name, st.id as timetable_id
    FROM students s
    JOIN classes c ON s.class_id = c.class_id
    JOIN arms a ON s.arm_id = a.arm_id
    JOIN set_timetables st ON st.class_id = c.class_id AND st.arm_id = a.arm_id
    WHERE s.id = $studentId"; // Use normal query without prepare

$classResult = $connection->query($classQuery);

if ($classResult) {
    $class = $classResult->fetch_assoc();
} else {
    echo "<h3>Error fetching class information.</h3>";
    exit;
}

$class_name = $class['class_name'] ?? 'Class not found';
$arm_name = $class['arm_name'] ?? 'Arm not found';
$timetableId = $class['timetable_id'] ?? 0; // Fetch the timetable ID associated with the class and arm

// If no timetable ID is found, show an error or empty timetable
if (!$timetableId) {
    echo "<h3>No timetable available for your class and arm.</h3>";
    exit;
}

// Fetch timetable details
$query = "SELECT * FROM set_timetables WHERE id = $timetableId"; // Use normal query without prepare
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
            $next_time = strtotime("+{$duration} minutes", $current_time);

            if ($next_time > $end_time) {
                $next_time = $end_time;
            }

            $time_slots[] = [
                'start' => $current_time,
                'end' => $next_time,
                'start_formatted' => date('H:i:s', $current_time),
                'end_formatted' => date('H:i:s', $next_time)
            ];

            if ($next_time == $end_time) {
                break;
            }

            if ($i == 1 && in_array('SHORT BREAK', $break_types)) {
                $current_time = strtotime("+20 minutes", $next_time);
                $time_slots[] = [
                    'start' => $next_time,
                    'end' => $current_time,
                    'break' => 'SHORT BREAK',
                    'start_formatted' => date('H:i:s', $next_time),
                    'end_formatted' => date('H:i:s', $current_time)
                ];
            } elseif ($i == ($periods_count - 5) && in_array('RECESS', $break_types)) {
                $current_time = strtotime("+10 minutes", $next_time);
                $time_slots[] = [
                    'start' => $next_time,
                    'end' => $current_time,
                    'break' => 'RECESS',
                    'start_formatted' => date('H:i:s', $next_time),
                    'end_formatted' => date('H:i:s', $current_time)
                ];
            } elseif ($i == ($periods_count - 4) && in_array('LONG BREAK', $break_types)) {
                $current_time = strtotime("+30 minutes", $next_time);
                $time_slots[] = [
                    'start' => $next_time,
                    'end' => $current_time,
                    'break' => 'LONG BREAK',
                    'start_formatted' => date('H:i:s', $next_time),
                    'end_formatted' => date('H:i:s', $current_time)
                ];
            } else {
                $current_time = $next_time;
            }
        }
    }

    $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
    foreach ($days as $day) {
        $periods[$day] = array_fill(0, count($time_slots), ['subject_code' => '']);
    }

    $entryQuery = "SELECT * FROM timetable_entries WHERE timetable_id = $timetableId"; // Use normal query without prepare
    $entriesResult = $connection->query($entryQuery);

    while ($entry = $entriesResult->fetch_assoc()) {
        $day = $entry['day'];
        foreach ($time_slots as $index => $slot) {
            if ($entry['start_time'] == $slot['start_formatted'] && $entry['end_time'] == $slot['end_formatted']) {
                $periods[$day][$index]['subject_code'] = $entry['subject_code'];
                break;
            }
        }
    }
}
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

        .subject-cell {
            text-align: center;
            vertical-align: middle;
            padding: 15px;
            font-size: 14px;
            font-weight: bold;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            height: 60px;
        }
    </style>
</head>

<body>
    <div class="container mt-2">
        <h3 class="text-center mb-2">Timetable for <?php echo htmlspecialchars($class_name) . ' - ' . htmlspecialchars($arm_name); ?></h3>
        <div class="table-responsive">
            <table id="assignmentsTable" class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Time</th>
                        <?php foreach ($periods as $day => $slots): ?>
                            <th><?php echo htmlspecialchars($day); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $index => $slot): ?>
                        <tr>
                            <td style="font-weight:bolder; text-align: center; vertical-align: middle; padding: 10px;">
                                <?php echo htmlspecialchars($slot['start_formatted'] . ' - ' . $slot['end_formatted']); ?>
                            </td>
                            <?php if (isset($slot['break'])): ?>
                                <td class='subject-cell text-danger' colspan="<?php echo count($periods); ?>" style="text-align: center; vertical-align: middle; padding: 15px;">
                                    <?php echo implode('&nbsp;&nbsp;&nbsp;&nbsp;', str_split($slot['break'])); ?>
                                </td>
                            <?php else: ?>
                                <?php foreach ($periods as $day => $slots): ?>
                                    <td class='subject-cell' style="text-align: center; vertical-align: middle; padding: 15px;">
                                        <?php echo htmlspecialchars($slots[$index]['subject_code']); ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>