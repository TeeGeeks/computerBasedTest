<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Get class, arm, subject, and exam from URL parameters
$selectedClass = $_GET['class'] ?? '';
$selectedArm = $_GET['arm'] ?? '';
$selectedSubject = $_GET['subject'] ?? '';
$selectedExam = $_GET['exam'] ?? '';

// Redirect if no valid class, arm, subject, or exam is selected
if (empty($selectedClass) || empty($selectedArm) || empty($selectedSubject) || empty($selectedExam)) {
    showSweetAlert('warning', 'Invalid Selection', 'Please select valid class, arm, subject, and exam.', 'generateReport.php');
    exit;
}

// Fetch class, arm, subject, and exam details
$classQuery = "SELECT class_name FROM classes WHERE class_id = '$selectedClass'";
$armQuery = "SELECT arm_name FROM arms WHERE arm_id = '$selectedArm'";  // Updated to fetch from the arms table
$subjectQuery = "SELECT subject_name FROM subjects WHERE id = '$selectedSubject'";
$examQuery = "SELECT exam_title FROM exams WHERE id = '$selectedExam'";

$classResult = mysqli_query($connection, $classQuery);
$armResult = mysqli_query($connection, $armQuery);  // Fetch arm details
$subjectResult = mysqli_query($connection, $subjectQuery);
$examResult = mysqli_query($connection, $examQuery);

$className = mysqli_fetch_assoc($classResult)['class_name'] ?? 'Unknown Class';
$armName = mysqli_fetch_assoc($armResult)['arm_name'] ?? 'Unknown Arm';  // Fetch arm name
$subjectName = mysqli_fetch_assoc($subjectResult)['subject_name'] ?? 'Unknown Subject';
$examName = mysqli_fetch_assoc($examResult)['exam_title'] ?? 'Unknown Exam';

// Fetch student exam results based on selected criteria
$query = "
    SELECT 
        ea.student_id,
        SUM(ea.score) AS total_score,
        MAX(ea.created_at) AS attempt_date,
        CONCAT(u.surname, ' ', u.other_names) AS student_name,
        ex.total_marks
    FROM 
        exam_attempts ea
    JOIN 
        students u ON ea.student_id = u.id
    JOIN 
        exams ex ON ea.exam_id = ex.id
    WHERE 
        u.class_id = '$selectedClass'
        AND u.arm_id = '$selectedArm'  /* Include arm filter */
        AND ea.exam_id = '$selectedExam'
    GROUP BY 
        ea.student_id
";

$results = [];
$result = mysqli_query($connection, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}

// Close the database connection
mysqli_close($connection);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Report</title>
    <style>
        .container {
            margin: 30px auto;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .btn {
            margin-right: 10px;
            padding: 10px 15px;
        }

        h3 {
            text-align: center;
            margin-bottom: 30px;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        @media print {
            .actions {
                display: none;
                /* Hide the actions section when printing */
            }

            .headernav {
                display: none;
            }
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>
        <div class="container py-4 mt-3">
            <div class="col-md-12">
                <h3>Exam Report: <?php echo htmlspecialchars($className) . " - " . htmlspecialchars($examName); ?></h3>

                <!-- Actions for print and export -->
                <div class="actions">
                    <button class="btn btn-primary" onclick="window.print();">Print Report</button>
                    <button class="btn btn-secondary" id="exportButton">Export to CSV</button>
                </div>

                <!-- Results table -->
                <?php if (!empty($results)): ?>
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>S/N</th> <!-- Serial Number Header -->
                                    <th>Student Name</th>
                                    <th>Total Score</th>
                                    <th>Total Marks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Initialize the serial number counter
                                $sn = 1;
                                foreach ($results as $result): ?>
                                    <tr>
                                        <td><?php echo $sn++; ?></td> <!-- Display S/N and increment -->
                                        <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['total_score']); ?></td>
                                        <td><?php echo htmlspecialchars($result['total_marks']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No results available for the selected exam.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include("../includes/script.php"); ?>

    <script>
        // CSV export function
        document.getElementById('exportButton').addEventListener('click', function() {
            const table = document.getElementById('assignmentsTable'); // Make sure to target the correct table
            let csvContent = '';

            // Fetch table rows and cells
            const rows = table.querySelectorAll('tr');
            rows.forEach((row) => {
                const cols = row.querySelectorAll('th, td');
                const rowData = Array.from(cols).map(col => `"${col.textContent.trim()}"`).join(',');
                csvContent += rowData + "\n";
            });

            // Create a Blob and download
            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.download = 'exam_report.csv';
            link.click();
            URL.revokeObjectURL(url);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Exam Report';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>

</body>


</html>