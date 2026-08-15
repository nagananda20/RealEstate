<?php
session_start();
header("Content-Type: application/json");
require_once "../../config/database.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); echo json_encode(["success"=>false,"message"=>"Only POST is allowed"]); exit; }
$data=json_decode(file_get_contents("php://input"),true) ?: $_POST;
$email=trim($data["email"]??""); $password=$data["password"]??"";
if (!filter_var($email,FILTER_VALIDATE_EMAIL)||$password===""){http_response_code(422);echo json_encode(["success"=>false,"message"=>"Email and password are required"]);exit;}
$stmt=$pdo->prepare("SELECT id,name,email,password,role,status FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); $u=$stmt->fetch();
if(!$u||!password_verify($password,$u["password"])){http_response_code(401);echo json_encode(["success"=>false,"message"=>"Invalid credentials"]);exit;}
if($u["status"]!=="active"){http_response_code(403);echo json_encode(["success"=>false,"message"=>"Account is not active"]);exit;}
session_regenerate_id(true); $_SESSION["user_id"]=(int)$u["id"]; $_SESSION["user_name"]=$u["name"]; $_SESSION["user_email"]=$u["email"]; $_SESSION["user_role"]=$u["role"]; $_SESSION["role"]=$u["role"]; $_SESSION["name"]=$u["name"];
echo json_encode(["success"=>true,"message"=>"Login successful","user"=>["id"=>(int)$u["id"],"name"=>$u["name"],"email"=>$u["email"],"role"=>$u["role"]]]);
?>