<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();
$student_id = mysqli_real_escape_string($connection, $_SESSION['student_id']);
// Retrieve the student_id from the URL
if ($student_id) {

    // Fetch student information based on student_id
    $studentQuery = "SELECT * FROM students WHERE id = '$student_id'";
    $studentResult = mysqli_query($connection, $studentQuery);
    $studentData = mysqli_fetch_assoc($studentResult);

    if (!$studentData) {
        echo "Student not found.";
        exit;
    }

    // Generate a unique ID code or use admission_no as the ID
    $generatedID = $studentData['admission_no'];
    mysqli_close($connection);
} else {
    echo "No student ID provided.";
    exit;
}

// Define default image
$defaultImage1 = BASE_URL . 'assets/img/default.jpg';
$profileImage1 = !empty($studentData['profile_image']) ? BASE_URL . $studentData['profile_image'] : $defaultImage1;
?>

<style>
    body {
        background-color: #f0f4f8;
        font-family: Arial, sans-serif;
    }

    .id-card-container {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .id-card {
        max-width: 350px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .id-card-header,
    .id-card-footer {
        background-color: #004085;
        color: #ffffff;
        padding: 10px;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }

    .profile-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .barcode-container {
        padding: 8px;
        text-align: center;
        width: 160px;
        height: 60px;
        overflow: hidden;
    }

    .student-info p {
        margin: 0;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .id-card-container {
            margin-top: 20px;
            flex-direction: column;
            align-items: center;
        }

        .id-card {
            width: 100%;
            max-width: 300px;
        }

        .profile-photo {
            width: 80px;
            height: 100px;
        }

        .barcode-container {
            width: 100%;
            text-align: center;
        }
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container d-flex justify-content-center align-items-center vh-100" style="margin-top:-50px">
            <div class="id-card-container">
                <!-- Front of ID Card -->
                <div class="card id-card">
                    <div class="id-card-header">GINYARD International Co.</div>
                    <div class="card-body d-flex">
                        <div class="profile-photo me-3" style="width: 100px; height: 130px;">
                            <img src="<?php echo $profileImage1; ?>" alt="Profile Picture">
                        </div>
                        <div class="student-info">
                            <h5 class="text-uppercase mb-1 fw-bold">Student ID Card</h5>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($studentData['surname'] . ' ' . $studentData['other_names']); ?></p>
                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($generatedID); ?></p>
                            <p><strong>Class:</strong> 11/2024</p>
                            <p><strong>DOB:</strong><?php echo htmlspecialchars($studentData['date_of_birth']); ?></p>
                            <div class="barcode-container text-right mt-2">
                                <img src="https://barcode.tec-it.com/barcode.ashx?data=<?php echo urlencode($generatedID); ?>&code=Code128&dpi=80&translate-2d=0" alt="Barcode">
                            </div>
                        </div>
                    </div>
                    <div class="id-card-footer">GINYARD International Co.</div>
                </div>

                <!-- Back of ID Card -->
                <div class="card id-card">
                    <div class="id-card-header">GINYARD International Co.</div>
                    <div class="card-body">
                        <h5 class="text-uppercase mb-1 fw-bold">Student Information</h5>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($studentData['phone_number']); ?></p>
                        <p><strong>Address:</strong><?php echo htmlspecialchars($studentData['date_of_birth']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($studentData['email']); ?></p>
                        <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($studentData['parent_phone']); ?></p>
                        <p><strong>Policy:</strong> This card is property of GINYARD Intl. and must be returned upon request.</p>
                    </div>
                    <div class="id-card-footer">GINYARD International Co.</div>
                </div>
            </div>
        </div>

        <footer style="margin-top: 200px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>
    <?php include("../includes/script.php"); ?>
    <!-- <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Student ID';
            updateBreadcrumbs(['Pages', currentPage]);
        });

        function generateBarcode() {
            const studentId = "<?php echo $generatedID; ?>";
            const barcodeImg = document.getElementById('barcodeImg');
            const apiUrl = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(studentId)}&code=Code128&dpi=80&translate-2d=0`;
            barcodeImg.src = apiUrl;
        }

        window.onload = generateBarcode;
    </script> -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = `<?php echo htmlspecialchars($studentData['surname'] . ' ' . $studentData['other_names']); ?> - ID Card`;
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>

</body>