<?php
include("connection.php");
header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['sadhu_user_id']) && !isset($_COOKIE['sadhu_user_id'])){
    echo json_encode([
        "status"=>"error",
        "message"=>"not_logged_in"
    ]);
    exit;
}

/* RESTORE SESSION FROM COOKIE */

if(!isset($_SESSION['sadhu_user_id']) && isset($_COOKIE['sadhu_user_id'])){
    $_SESSION['sadhu_user_id']=$_COOKIE['sadhu_user_id'];
    $_SESSION['sadhu_user_name']=$_COOKIE['sadhu_user_name'] ?? 'Guest';
}

$mobile=$_SESSION['sadhu_user_id'];

$stmt=$con->prepare("SELECT id,name,mobile,email,status,profile_photo FROM tbl_members WHERE mobile=? LIMIT 1");
$stmt->bind_param("s",$mobile);
$stmt->execute();
$res=$stmt->get_result();

if($res->num_rows!=1){
    echo json_encode([
        "status"=>"error",
        "message"=>"user_not_found"
    ]);
    exit;
}

$row=$res->fetch_assoc();

/* BLOCK CHECK */

if($row['status']=="Blocked"){

    session_unset();
    session_destroy();

    setcookie("sadhu_user_id","",time()-3600,"/");
    setcookie("sadhu_user_name","",time()-3600,"/");

    echo json_encode([
        "status"=>"error",
        "message"=>"account_blocked"
    ]);
    exit;
}

/* UPDATE LAST ACTIVE */

$con->query("UPDATE tbl_members SET last_active=NOW() WHERE mobile='$mobile'");

$userData=[
"id"=>$row['id'],
"name"=>$row['name'],
"mobile"=>$row['mobile'],
"email"=>$row['email'],
"profile_photo"=>$row['profile_photo']
];

echo json_encode([
"status"=>"success",
"user"=>$userData
]);
