<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$conn = new mysqli("localhost","root","","esp32_control");

if($conn->connect_error){
    http_response_code(500);
    echo json_encode(["error"=>"DB failed"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ===== GET LIGHT STATE =====
if($method==="GET" && $action==="get"){
    $res = $conn->query("SELECT state FROM lights WHERE id=1");
    $row = $res->fetch_assoc();

    echo json_encode([
        "state" => (int)($row['state'] ?? 0)
    ]);
    exit;
}

// ===== SET LIGHT =====
if($method==="POST" && $action==="set"){
    $body = json_decode(file_get_contents("php://input"), true);

    if(!isset($body['state'])){
        http_response_code(400);
        echo json_encode(["error"=>"missing state"]);
        exit;
    }

    $state = (int)(bool)$body['state'];

    $stmt = $conn->prepare("UPDATE lights SET state=? WHERE id=1");
    $stmt->bind_param("i",$state);
    $stmt->execute();

    echo json_encode(["ok"=>true,"state"=>$state]);
    exit;
}

echo json_encode(["error"=>"invalid request"]);