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

// ── PUSH (called by ESP32 every 10s) ─────────────────────────
if ($action === 'push') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        // JSON body
        $input = json_decode(file_get_contents("php://input"), true);
        $temp  = isset($input['temp'])        ? (float)$input['temp']        : null;
        $hum   = isset($input['hum'])         ? (float)$input['hum']         : null;
        // also accept "temperature"/"humidity" keys from JSON just in case
        if ($temp === null && isset($input['temperature'])) $temp = (float)$input['temperature'];
        if ($hum  === null && isset($input['humidity']))    $hum  = (float)$input['humidity'];
    } else {
        // Form-encoded: ESP32 sends temperature=xx&humidity=xx
        $temp = isset($_POST['temperature']) ? (float)$_POST['temperature'] : null;
        $hum  = isset($_POST['humidity'])    ? (float)$_POST['humidity']    : null;
    }

    if ($temp === null || $hum === null) {
        echo json_encode(["error" => "Missing temp/hum"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO sensor_readings (temperature, humidity) VALUES (?, ?)");
    $stmt->bind_param("dd", $temp, $hum);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Insert failed"]);
    }

    $stmt->close();
}

// ── LATEST (called by dashboard every 3s) ────────────────────
elseif ($action === 'latest') {
    $sql    = "SELECT temperature, humidity, created_at FROM sensor_readings ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            "temperature" => (float)$row['temperature'],
            "humidity"    => (float)$row['humidity'],
            "created_at"  => $row['created_at']
        ]);
    } else {
        echo json_encode(["temperature" => null, "humidity" => null]);
    }
}

else {
    echo json_encode(["error" => "Invalid action"]);
}

$conn->close();
?>