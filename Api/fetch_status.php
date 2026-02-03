<?php
// fetch_status.php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$profile_id = intval($_GET['profile_id'] ?? 0);
$my_profile = intval($_GET['my_profile_id'] ?? 0);
$response = ['online'=>false, 'last_active'=>null, 'is_typing'=>false];

if(!$profile_id){ echo json_encode($response); exit; }

// online check
$res = $con->query("SELECT (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(m.last_active) < 25) AS online, m.last_active FROM tbl_members m JOIN tbl_marriage_profiles mp ON mp.user_id=m.id WHERE mp.id='$profile_id' LIMIT 1");
if($res && $row = $res->fetch_assoc()){
    $response['online'] = !empty($row['online']);
    if(!$response['online'] && !empty($row['last_active'])) $response['last_active'] = date("d M, h:i A", strtotime($row['last_active']));
}

// typing check (is profile_id typing TO me?)
$typ = $con->query("SELECT is_typing FROM tbl_typing WHERE profile_id='$profile_id' AND target_profile_id='$my_profile' LIMIT 1");
if($typ && $typ->num_rows>0){
    $r = $typ->fetch_assoc();
    $response['is_typing'] = (!empty($r['is_typing'])) ? true : false;
}

header('Content-Type: application/json');
echo json_encode($response);
