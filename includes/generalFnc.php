<?php
require_once 'constants.php';

function connection()
{
    // Use constants for connection parameters
    $connection = new mysqli(DB_host, DB_user, DB_pass, DB_name);

    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    // Set charset to UTF-8 (if needed)
    $connection->set_charset("utf8");

    // Return $connection for reuse
    return $connection;
}

function showSweetAlert($icon, $title, $text, $redirect = null)
{
    // Using PHP inside single quotes here would break, so we close and reopen PHP
    echo "<script src='" . BASE_URL . "assets/js/core/sweetalert.js'></script>
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


function showSweetAlert1($icon, $title, $text, $redirect = null)
{
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '$icon',
                    title: '$title',
                    text: '$text',
                }).then(() => {
                    if ('$redirect') {
                        window.location = '$redirect';
                    }
                });
            } else {
                // Fallback to a standard alert if SweetAlert is not working
                alert('$title: $text');
                if ('$redirect') {
                    window.location = '$redirect';
                }
            }
        });
    </script>";
}


function fetchSubjects()
{
    global $connection; // Use the global connection variable
    $subjects = [];

    // SQL query to fetch subjects from the database
    $query = "SELECT id, subject_name FROM subjects"; // Adjust the table name and fields as needed

    // Execute the query
    if ($result = mysqli_query($connection, $query)) {
        // Fetch all rows as an associative array
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = $row; // Add each row to the subjects array
        }
        mysqli_free_result($result); // Free result set
    } else {
        // Handle query error
        echo "Error fetching subjects: " . mysqli_error($connection);
    }

    return $subjects; // Return the array of subjects
}

function fetchClasses()
{
    global $connection; // Use the global connection variable
    $classes = [];

    // SQL query to fetch subjects from the database
    $query = "SELECT * FROM classes"; // Adjust the table name and fields as needed

    // Execute the query
    if ($result = mysqli_query($connection, $query)) {
        // Fetch all rows as an associative array
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row; // Add each row to the subjects array
        }
        mysqli_free_result($result); // Free result set
    } else {
        // Handle query error
        echo "Error fetching classes: " . mysqli_error($connection);
    }

    return $classes; // Return the array of subjects
}

function fetchTeachers()
{
    global $connection; // Use the global connection variable
    $teachers = [];

    // SQL query to fetch subjects from the database
    $query = "SELECT * FROM teachers"; // Adjust the table name and fields as needed

    // Execute the query
    if ($result = mysqli_query($connection, $query)) {
        // Fetch all rows as an associative array
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row; // Add each row to the subjects array
        }
        mysqli_free_result($result); // Free result set
    } else {
        // Handle query error
        echo "Error fetching teachers: " . mysqli_error($connection);
    }

    return $teachers; // Return the array of subjects
}

function fetchStudents()
{
    global $connection; // Use the global connection variable
    $students = [];

    // SQL query to fetch students and sort alphabetically by surname
    $query = "SELECT * FROM students ORDER BY surname ASC"; // Adjust the table name and fields as needed

    // Execute the query
    if ($result = mysqli_query($connection, $query)) {
        // Fetch all rows as an associative array
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row; // Add each row to the students array
        }
        mysqli_free_result($result); // Free result set
    } else {
        // Handle query error
        echo "Error fetching students: " . mysqli_error($connection);
    }

    return $students; // Return the array of students
}


function fetchExams()
{
    global $connection; // Use the global connection variable
    $exams = [];

    // SQL query to fetch subjects from the database
    $query = "SELECT * FROM exams"; // Adjust the table name and fields as needed

    // Execute the query
    if ($result = mysqli_query($connection, $query)) {
        // Fetch all rows as an associative array
        while ($row = mysqli_fetch_assoc($result)) {
            $exams[] = $row; // Add each row to the subjects array
        }
        mysqli_free_result($result); // Free result set
    } else {
        // Handle query error
        echo "Error fetching teachers: " . mysqli_error($connection);
    }

    return $exams; // Return the array of subjects
}


function fetchQuestions()
{
    global $connection; // Use the global connection variable
    $exams = [];

    // SQL query to fetch subjects from the database
    $query = "SELECT * FROM questions"; // Adjust the table name and fields as needed

    // Execute the query
    if ($result = mysqli_query($connection, $query)) {
        // Fetch all rows as an associative array
        while ($row = mysqli_fetch_assoc($result)) {
            $exams[] = $row; // Add each row to the subjects array
        }
        mysqli_free_result($result); // Free result set
    } else {
        // Handle query error
        echo "Error fetching teachers: " . mysqli_error($connection);
    }

    return $exams; // Return the array of subjects
}

function fetchArms()
{
    global $connection; // Use the global connection variable
    $arms = [];

    // SQL query to fetch subjects from the database
    $query = "SELECT * FROM arms"; // Adjust the table name and fields as needed

    // Execute the query
    if ($result = mysqli_query($connection, $query)) {
        // Fetch all rows as an associative array
        while ($row = mysqli_fetch_assoc($result)) {
            $arms[] = $row; // Add each row to the subjects array
        }
        mysqli_free_result($result); // Free result set
    } else {
        // Handle query error
        echo "Error fetching teachers: " . mysqli_error($connection);
    }

    return $arms; // Return the array of subjects
}
