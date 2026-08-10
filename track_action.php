<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

cotswold_require_same_site_request(true);

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['action'])) {
    $action = trim((string)$data['action']);

    if (!preg_match('/^[A-Za-z0-9_.:-]{1,80}$/', $action)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE tracking_stats SET count = count + 1 WHERE action_name = ?");
    $stmt->bind_param("s", $action);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Counter updated for $action"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating record"]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "No action specified"]);
}

$conn->close();
?>
