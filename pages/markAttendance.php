<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

// Establish connection
$connection = connection();

// Function to save attendance based on admission_no
function markAttendance($admissionNo, $connection)
{
    $date = date('Y-m-d');
    $time = date('H:i:s');

    // Find student by admission_no
    $studentQuery = "SELECT id FROM students WHERE admission_no = '$admissionNo'";
    $studentResult = mysqli_query($connection, $studentQuery);
    $studentData = mysqli_fetch_assoc($studentResult);

    if (!$studentData) {
        return "Student not found.";
    }

    $studentId = $studentData['id'];

    // Check if attendance is already marked
    $attendanceQuery = "SELECT * FROM attendance WHERE student_id = '$studentId' AND date = '$date'";
    $attendanceResult = mysqli_query($connection, $attendanceQuery);

    if (mysqli_num_rows($attendanceResult) > 0) {
        return "Attendance already marked for today.";
    } else {
        // Insert attendance record
        $insertQuery = "INSERT INTO attendance (student_id, date, time_in) VALUES ('$studentId', '$date', '$time')";
        if (mysqli_query($connection, $insertQuery)) {
            return "Attendance marked successfully.";
        } else {
            return "Error marking attendance: " . mysqli_error($connection);
        }
    }
}

// Handle AJAX request for barcode input
if (isset($_POST['barcode'])) {
    $admissionNo = htmlspecialchars($_POST['barcode']);
    echo markAttendance($admissionNo, $connection);
    exit;
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Take Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
        }

        .status-message {
            margin-top: 20px;
            font-size: 1.2em;
            color: green;
        }

        #camera video {
            width: 100%;
            max-width: 640px;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container">
            <h2>Scan Student ID Card</h2>
            <div id="camera" style="width:100%"></div>
            <div id="statusMessage" class="status-message"></div>
        </div>

        <footer style="margin-top: 200px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>
    <?php include("../includes/script.php"); ?>

    <!-- Include QuaggaJS library -->
    <script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2/dist/quagga.min.js"></script>

    <script>
        const statusMessage = document.getElementById("statusMessage");

        // Quagga configuration
        const quaggaConf = {
            inputStream: {
                target: document.querySelector("#camera"),
                type: "LiveStream",
                constraints: {
                    width: {
                        min: 640
                    },
                    height: {
                        min: 480
                    },
                    facingMode: "environment",
                    aspectRatio: {
                        min: 1,
                        max: 2
                    }
                }
            },
            decoder: {
                readers: ['code_128_reader']
            }
        };

        // Initialize Quagga
        Quagga.init(quaggaConf, function(err) {
            if (err) {
                console.error(err);
                statusMessage.innerText = "Failed to initialize scanner.";
                return;
            }
            Quagga.start();
        });

        // Detect barcode
        Quagga.onDetected(function(result) {
            const barcode = result.codeResult.code;

            // Send barcode data to the server
            fetch("take_attendance.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `barcode=${encodeURIComponent(barcode)}`
                })
                .then(response => response.text())
                .then(data => {
                    statusMessage.innerText = data;
                    Quagga.stop(); // Stop scanning after a successful scan
                    setTimeout(() => {
                        Quagga.start();
                    }, 3000); // Restart scanning after 3 seconds
                })
                .catch(error => {
                    console.error("Error:", error);
                    statusMessage.innerText = "Error processing attendance.";
                });
        });
    </script>
</body>

</html>