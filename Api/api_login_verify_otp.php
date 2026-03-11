<?php
include("connection.php");
header('Content-Type: application/json');
session_start();

$input=json_decode(file_get_contents("php://input"),true);

$mobile=$input['mobile'] ?? '';
$name=$input['name'] ?? '';
$caste=$input['caste'] ?? '';
$widget_token=$input['access_token'] ?? '';

if(empty($mobile)){
    echo json_encode(["status"=>"error","message"=>"missing_mobile"]);
    exit;
}

/* USER CHECK */

$check=$con->prepare("SELECT * FROM tbl_members WHERE mobile=? LIMIT 1");
$check->bind_param("s",$mobile);
$check->execute();
$res=$check->get_result();

if($res->num_rows==1){

$row=$res->fetch_assoc();

if($row['status']=='Blocked'){
echo json_encode(["status"=>"error","message"=>"blocked"]);
exit;
}

$_SESSION['sadhu_user_id']=$row['mobile'];
$_SESSION['sadhu_user_name']=$row['name'];

$userData=[
"id"=>$row['id'],
"name"=>$row['name'],
"mobile"=>$row['mobile'],
"profile_photo"=>$row['profile_photo']
];

echo json_encode([
"status"=>"success_login",
"user"=>$userData
]);

}else{

$email=$mobile."@sadhuvandana.local";

$dob="";
$city="";
$cast=$caste;
$gender="";
$password=password_hash(bin2hex(random_bytes(8)),PASSWORD_BCRYPT);
$photo="";
$date=date("Y-m-d H:i:s");
$status="Pending";

$stmt=$con->prepare("INSERT INTO tbl_members (name,email,mobile,dob,city,cast,gender,password,profile_photo,date,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param("sssssssssss",$name,$email,$mobile,$dob,$city,$cast,$gender,$password,$photo,$date,$status);

if($stmt->execute()){

$_SESSION['sadhu_user_id']=$mobile;
$_SESSION['sadhu_user_name']=$name;

$userData=[
"id"=>$stmt->insert_id,
"name"=>$name,
"mobile"=>$mobile
];

echo json_encode([
"status"=>"success_register",
"user"=>$userData
]);

}else{

echo json_encode([
"status"=>"error",
"message"=>$stmt->error
]);

}
}
