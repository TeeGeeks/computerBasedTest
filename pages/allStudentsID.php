<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Fetch all students' information from the database
$studentsQuery = "SELECT * FROM students";
$studentsResult = mysqli_query($connection, $studentsQuery);

// Define default image
$defaultImage = BASE_URL . 'assets/img/default.jpg';
?>
<style>
    body {
        background-color: #f0f4f8;
        font-family: Arial, sans-serif;
    }

    .id-card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        /* Center-align cards */
    }

    .card-set {
        display: flex;
        gap: 10px;
        width: 100%;
        /* Make each card-set occupy roughly half the width */
        max-width: 800px;
        /* Limit width to ensure responsiveness */
    }

    .id-card {
        width: 100%;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        overflow: hidden;
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
        width: 100px;
        height: 130px;
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

    #barcodeImg {
        max-width: 100%;
        max-height: 100%;
    }

    .student-info p {
        margin: 0;
    }

    /* Adjust layout for smaller screens */
    @media (max-width: 768px) {
        .card-set {
            width: 100%;
        }
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg mt-2">
        <div class="container ">
            <div class="id-card-container">
                <?php while ($studentData = mysqli_fetch_assoc($studentsResult)) : ?>
                    <?php
                    $profileImage = !empty($studentData['profile_image']) ? BASE_URL . $studentData['profile_image'] : $defaultImage;
                    $generatedID = $studentData['admission_no'];
                    ?>

                    <!-- Card Set for each student -->
                    <div class="card-set">
                        <!-- Front of ID Card -->
                        <div class="card id-card">
                            <div class="id-card-header">GINYARD International Co.</div>
                            <div class="card-body d-flex">
                                <div class="profile-photo me-3">
                                    <img src="<?php echo $profileImage; ?>" alt="Profile Picture">
                                </div>
                                <div class="student-info">
                                    <h5 class="text-uppercase mb-1 fw-bold">Student ID Card</h5>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($studentData['surname'] . ' ' . $studentData['other_names']); ?></p>
                                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($generatedID); ?></p>
                                    <p><strong>Class:</strong> 11/2024</p>
                                    <p><strong>DOB:</strong> <?php echo htmlspecialchars($studentData['date_of_birth']); ?></p>
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
                                <p><strong>Address:</strong> N0 1 Apete</p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($studentData['email']); ?></p>
                                <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($studentData['parent_phone']); ?></p>
                                <p><strong>Policy:</strong> This card is property of GINYARD Intl. and must be returned upon request.</p>
                            </div>
                            <div class="id-card-footer">GINYARD International Co.</div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <footer style="margin-top: 50px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>
    <?php include("../includes/script.php"); ?>
</body>

<?php mysqli_close($connection); ?>