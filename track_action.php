<?php
include 'db.php';

// Allow CORS if needed (for local testing mostly, but good practice)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Get the POST data
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['action'])) {
    $action = $conn->real_escape_string($data['action']);
    
    // Update the count
    $sql = "UPDATE tracking_stats SET count = count + 1 WHERE action_name = '$action'";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Counter updated for $action"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating record: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No action specified"]);
}

$conn->close();
?>
