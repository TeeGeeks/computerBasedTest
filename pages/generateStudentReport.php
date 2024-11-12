<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch student ID from session
$studentId = $_SESSION['student_id'] ?? '';

// Initialize variables for subjects and exam scores
$subjectScores = [];

// Check if the student is logged in
if (!empty($studentId)) {
    // Fetch student's class
    $classQuery = "SELECT class_id FROM students WHERE id = '$studentId'";
    $classResult = mysqli_query($connection, $classQuery);

    if ($classResult && mysqli_num_rows($classResult) > 0) {
        $classRow = mysqli_fetch_assoc($classResult);
        $studentClass = $classRow['class_id'];

        // Fetch subjects and exam scores for the student
        $subjectQuery = "
            SELECT 
                subjects.subject_name, 
                exams.exam_title,
                exam_attempts.score,
                exams.total_marks
            FROM subjects
            INNER JOIN exams ON subjects.id = exams.subject_id
            INNER JOIN exam_attempts ON exams.id = exam_attempts.exam_id
            WHERE exam_attempts.student_id = '$studentId' 
            AND exams.class_id = '$studentClass'
        ";

        $subjectResult = mysqli_query($connection, $subjectQuery);
        if ($subjectResult && mysqli_num_rows($subjectResult) > 0) {
            while ($row = mysqli_fetch_assoc($subjectResult)) {
                $subjectScores[] = $row;
            }
        } else {
            // Show SweetAlert if no exam scores are found
            showSweetAlert('info', 'No Exam Scores', 'No exam scores found for your class.', '../dashboard.php');
        }
    } else {
        // Show SweetAlert if class information is not found
        showSweetAlert('error', 'Error', 'Class information not found for the student.', '../dashboard.php');
    }
} else {
    // Show SweetAlert if the student is not logged in
    showSweetAlert('warning', 'Not Logged In', 'You are not logged in.', 'login.php');
}

// Close the database connection
mysqli_close($connection);


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Exam Report</title>
    <style>
        /* Styling for the report page */
        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
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

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="col-md-12">
                <h4 class="text-center mb-4">Your Exam Report</h4>
                <div class="actions">
                    <button class="btn btn-primary" onclick="window.print();">Print Report</button>
                    <button class="btn btn-secondary" id="exportButton">Export to CSV</button>
                </div>
                <?php if (!empty($subjectScores)): ?>
                    <div class="table-responsive">
                        <table id="assignmentsTable" class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>S/N</th> <!-- Serial Number Header -->
                                    <th>Subject</th>
                                    <th>Exam Title</th>
                                    <th>Score</th>
                                    <th>Total Marks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Initialize the serial number counter
                                $sn = 1;
                                foreach ($subjectScores as $score): ?>
                                    <tr>
                                        <td><?php echo $sn++; ?></td> <!-- Display S/N and increment -->
                                        <td><?php echo htmlspecialchars($score['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($score['exam_title']); ?></td>
                                        <td><?php echo htmlspecialchars($score['score']); ?></td>
                                        <td><?php echo htmlspecialchars($score['total_marks']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">No exam scores available at this time.</div>
                <?php endif; ?>
            </div>
        </div>

        <footer style="margin-top: 100px;">
            <?php include("../includes/footer.php"); ?>
        </footer>
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
            link.download = 'student_exam_report.csv';
            link.click();
            URL.revokeObjectURL(url);
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Student Exam Report';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>