<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$conn = new mysqli("localhost", "root", "", "esp32_db");

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$action = $_GET['action'] ?? '';

// ── GET (called by dashboard & ESP32 to read current state) ──
if ($action === 'get') {
    $sql    = "SELECT state FROM light_control WHERE id = 1 LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(["state" => (int)$row['state']]);
    } else {
        echo json_encode(["state" => 0]);
    }
}

// ── SET (called by dashboard button) ─────────────────────────
elseif ($action === 'set') {
    $input = json_decode(file_get_contents("php://input"), true);
    $state = isset($input['state']) ? (int)$input['state'] : 0;
    $state = $state ? 1 : 0; // sanitize to 0 or 1

    $sql = "UPDATE light_control SET state = $state WHERE id = 1";
    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "state" => $state]);
    } else {
        echo json_encode(["error" => "Failed to update light state"]);
    }
}

else {
    echo json_encode(["error" => "Invalid action"]);
}

$conn->close();
?>