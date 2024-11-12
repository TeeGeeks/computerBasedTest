<?php
include("../config.php");
include("../includes/generalFnc.php");

$connection = connection();

function showSweetAlert1($icon, $title, $text, $redirect = null)
{
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '$icon',
                title: '$title',
                text: '$text',
            }).then(() => {
                if ('$redirect') {
                    window.location = '$redirect';
                }
            });
        });
    </script>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = $_POST['subjectId'];
    $subjectName = trim($_POST['subjectName']);

    // Normal query to update subject
    $query = "UPDATE subjects SET subject_name = '$subjectName' WHERE id = '$subjectId'";

    if (mysqli_query($connection, $query)) {
        // Subject added successfully
        showSweetAlert1('success', 'Success!', 'Subject updated successfully.', 'manageSubject.php'); // Redirect to the form page
    } else {
        // Error occurred
        showSweetAlert1('error', 'Oops...', 'Something went wrong. Please try again.', 'manageSubject.php'); // Redirect to the form page
    }



    mysqli_close($connection);
}
