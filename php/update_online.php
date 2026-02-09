<?php
// update_online.php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){ echo "no"; exit; }

$member = $con->query("SELECT id FROM tbl_members WHERE email='".$con->real_escape_string($user_email)."'")->fetch_assoc();
if(!$member){ echo "no"; exit; }

$con->query("UPDATE tbl_members SET last_active=NOW() WHERE id=".$member['id']);
echo "ok";
