<?php
include("../config.php");
include("../includes/generalFnc.php");

$connection = connection();

$response = ['success' => false];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete the record
    $sql = "DELETE FROM subjects WHERE id = $id";

    if ($connection->query($sql) === TRUE) {
        $response['success'] = true;
    } else {
        $response['error'] = "Error deleting record: " . $connection->error;
    }
}

$connection->close(); // Close the connection

header('Content-Type: application/json');
echo json_encode($response);
