<?php
include("connection.php");
header('Content-Type: application/json');
session_start();

$mobile = trim($_POST['mobile'] ?? '');
$name = trim($_POST['name'] ?? '');
$caste = trim($_POST['caste'] ?? '');

if(empty($mobile) || empty($name) || empty($caste)){
    echo json_encode(["status"=>"error","message"=>"missing_fields"]);
    exit;
}

$allowed_castes = [
"Kapdi","Deshani","Dudhrejia","Danidhariya","Gondaliya","Mesvaniya",
"Ramkabir","Ramsnehi","Vaghani","Chapbai","Parabiya","Hariyani",
"Sarpadadiya","Ramdevputra","Ravibhan","Baroliya"
];

if(!in_array($caste,$allowed_castes)){
    echo json_encode(["status"=>"error","message"=>"invalid_caste"]);
    exit;
}

if(!preg_match('/^[0-9]{10}$/',$mobile)){
    echo json_encode(["status"=>"error","message"=>"invalid_mobile"]);
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$today=date('Y-m-d');

$stmt=$con->prepare("SELECT COUNT(*) FROM tbl_otp_attempts WHERE mobile=? AND DATE(sent_time)=?");
$stmt->bind_param("ss",$mobile,$today);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if($count>=2){
    echo json_encode(["status"=>"error","message"=>"limit_exceeded"]);
    exit;
}

$now=date('Y-m-d H:i:s');
$ins=$con->prepare("INSERT INTO tbl_otp_attempts (mobile,sent_time) VALUES (?,?)");
$ins->bind_param("ss",$mobile,$now);
$ins->execute();

$_SESSION['login_mobile']=$mobile;
$_SESSION['login_name']=$name;
$_SESSION['login_caste']=$caste;

echo json_encode([
"status"=>"success",
"message"=>"otp_allowed"
]);
