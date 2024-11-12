<?php

session_start(); // Start session

// Check if user is logged in as admin, staff, or student
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['student_id'])) {
    header('Location: ' . BASE_URL . '/index.php'); // Redirect to login page if not logged in
    exit();
}
