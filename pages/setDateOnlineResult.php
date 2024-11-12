<?php
include("../config.php");
include("../includes/header.php");
include_once("../includes/generalFnc.php");
include('../session.php');
$connection = connection();

// Initialize the existing result date variable
$existing_result_date = '';

// Fetch the existing result visibility date from the database
$query = "SELECT result_visibility_date FROM settings WHERE id = 1"; // Assuming you have a settings table
$result = mysqli_query($connection, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $existing_result_date = $row['result_visibility_date']; // Store the existing date
}

// Ensure the date is formatted correctly for the input
if ($existing_result_date) {
    $existing_result_date = date('Y-m-d', strtotime($existing_result_date));
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result_date = $_POST['result_date']; // Get the date from the form

    // Check if a date already exists
    if (!empty($existing_result_date)) {
        // Update the existing date
        $query = "UPDATE settings SET result_visibility_date = '$result_date' WHERE id = 1";
        if (mysqli_query($connection, $query)) {
            showSweetAlert('success', 'Date Updated Successfully!', 'The date for viewing results has been updated.', 'setDateOnlineResult.php');
        } else {
            showSweetAlert('error', 'Error Updating Date', 'There was an error updating the date. Please try again.', null);
        }
    } else {
        // Insert a new date
        $query = "INSERT INTO settings (result_visibility_date) VALUES ('$result_date')";
        if (mysqli_query($connection, $query)) {
            showSweetAlert('success', 'Date Set Successfully!', 'The date for viewing results has been set.', 'setDateOnlineResult.php');
        } else {
            showSweetAlert('error', 'Error Setting Date', 'There was an error setting the date. Please try again.', null);
        }
    }
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Exam</title>
    <link rel="stylesheet" href="../path/to/bootstrap.css">
    <link rel="stylesheet" href="../path/to/custom.css">
</head>

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

    .container {
        margin-top: 50px;
        /* Add some space at the top */
    }

    h2 {
        color: #343a40;
        /* Darker color for the heading */
        margin-bottom: 30px;
        /* Space below heading */
        text-align: center;
        /* Center align the heading */
    }

    .card-body {
        background-color: #ffffff;
        /* White background for card */
        padding: 30px;
        /* Padding inside the card */
        border-radius: 8px;
        /* Rounded corners */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Soft shadow */
    }
</style>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>

        <div class="container d-flex justify-content-center">
            <div class="col-md-8 col-lg-5 col-sm-9">
                <h2>Set Result Viewing Date</h2>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="result_date">Result Viewing Date:</label>
                            <input type="date" id="result_date" name="result_date" class="form-control" value="<?php echo $existing_result_date; ?>" required>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" id="submitBtn" name="submit" class="btn btn-md btn-primary">
                                <i class="fa fa-save me-2"></i> Set Date
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <footer style="margin-top: 120px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>

    <?php include("../includes/script.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Set Result Date'; // Update this dynamically based on your context
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>