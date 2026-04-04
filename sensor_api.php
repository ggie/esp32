<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$conn = new mysqli('localhost','root','','esp32_control');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ESP32 pushes data here: POST ?action=push  body: {"temp":28.5,"hum":60.2}
if($method==='POST' && $action==='push'){
    $body = json_decode(file_get_contents("php://input"), true);
    $t = floatval($body['temp'] ?? 0);
    $h = floatval($body['hum']  ?? 0);
    $stmt = $conn->prepare("INSERT INTO sensor_readings (temperature,humidity) VALUES (?,?)");
    $stmt->bind_param("dd",$t,$h);
    $stmt->execute();
    echo json_encode(["ok"=>true]);
    exit;
}

// Dashboard fetches latest: GET ?action=latest
if($method==='GET' && $action==='latest'){
    $res = $conn->query("SELECT temperature,humidity,recorded_at FROM sensor_readings ORDER BY id DESC LIMIT 1");
    $row = $res->fetch_assoc();
    echo json_encode($row ?: ["temp"=>0,"hum"=>0]);
    exit;
}

// Dashboard fetches history: GET ?action=history&limit=40
if($method==='GET' && $action==='history'){
    $limit = intval($_GET['limit'] ?? 40);
    $res = $conn->query("SELECT temperature,humidity,recorded_at FROM sensor_readings ORDER BY id DESC LIMIT $limit");
    $rows = [];
    while($r=$res->fetch_assoc()) $rows[]=$r;
    echo json_encode(array_reverse($rows));
    exit;
}

echo json_encode(["error"=>"unknown action"]);