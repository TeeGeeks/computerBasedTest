<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');

$connection = connection();

// Assuming admin_id is stored in the session after login
$adminId = $_SESSION['admin_id'] ?? ''; // Adjust based on your session management

// Fetch admin details from the database
$adminQuery = "SELECT * FROM admins WHERE admin_id = '$adminId'";
$adminResult = mysqli_query($connection, $adminQuery);
$adminData = mysqli_fetch_assoc($adminResult);

// Handle case where no admin data is found
if (!$adminData) {
    showSweetAlert('error', 'Error', 'Admin profile not found.', 'dashboard.php');
    exit;
}

$defaultImage = BASE_URL . 'assets/img/drake.jpg'; // Path to your default image
$profileImage = !empty($adminData['profile_image']) ? BASE_URL . $adminData['profile_image'] : $defaultImage;

// Close the database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Profile</title>
    <style>
        /* Add your custom CSS styling here */
        body {
            background-color: #f8f9fa;
            /* Light background for contrast */
        }

        .profile-container {
            max-width: 800px;
            /* Increased max-width for more space */
            width: 65%;
            /* Set width to 90% for responsiveness */
            margin: 0 auto;
            /* Centered container */
            padding: 30px;
            /* White background for the profile card */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            /* Enhanced shadow for depth */
            border-radius: 12px;
            /* Slightly more rounded corners */
            text-align: center;
            /* Centered text for a cohesive look */
        }

        .profile-img {
            border-radius: 50%;
            /* Circular image */
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid #007bff;
            /* Border to enhance image */
        }

        .profile-info {
            text-align: center;
            /* Left-align the labels and text for readability */
            margin-top: 20px;
        }

        .profile-info label {
            font-weight: bold;
            /* Bold labels for clarity */
            display: block;
            margin-bottom: 5px;
            color: #343a40;
            /* Darker color for better contrast */
        }

        .profile-info p {
            margin-bottom: 15px;
            font-size: 16px;
            /* Standard font size */
            color: #495057;
            /* Slightly lighter text for better readability */
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4 d-flex justify-content-center mt-3">
            <div class="profile-container">
                <h2 class="text-center mb-4">Admin Profile</h2>

                <!-- Display profile image -->
                <img src="<?php echo $profileImage; ?>" alt="Admin Profile Picture" class="profile-img mb-4">

                <div class="profile-info">
                    <p><?php echo htmlspecialchars($adminData['full_name']); ?></p>
                    <p><?php echo htmlspecialchars($adminData['email']); ?></p>
                    <p>Phone Number: <?php echo htmlspecialchars($adminData['phone_number']); ?></p>
                    <p>Username: <?php echo htmlspecialchars($adminData['username']); ?></p>
                </div>

                <div class="text-center mt-4">
                    <a href="editAdminProfile.php" class="btn btn-primary">Edit Profile</a>
                </div>
            </div>
        </div>


        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Admin Profile';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>