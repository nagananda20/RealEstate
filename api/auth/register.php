<?php
header("Content-Type: application/json");
require_once "../../config/database.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); echo json_encode(["success"=>false,"message"=>"Only POST is allowed"]); exit; }
$data=json_decode(file_get_contents("php://input"),true) ?: $_POST;
$name=trim($data["name"]??""); $email=trim($data["email"]??""); $phone=trim($data["phone"]??""); $password=$data["password"]??"";
if($name===""||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<6){http_response_code(422);echo json_encode(["success"=>false,"message"=>"Name, valid email and password of at least 6 characters are required"]);exit;}
$stmt=$pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); if($stmt->fetch()){http_response_code(409);echo json_encode(["success"=>false,"message"=>"Email already exists"]);exit;}
$stmt=$pdo->prepare("INSERT INTO users(name,email,phone,password,role,status) VALUES(?,?,?,?, 'user','active')"); $stmt->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]);
http_response_code(201); echo json_encode(["success"=>true,"message"=>"Registration successful","user_id"=>(int)$pdo->lastInsertId()]);
?>