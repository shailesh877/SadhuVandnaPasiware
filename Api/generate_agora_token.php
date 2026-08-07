<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

$channelName = $_REQUEST['channelName'] ?? '';
$uid = intval($_REQUEST['uid'] ?? 0);
$appId = "42eb51e0bc30431cba75efefb9ea15ea";

echo json_encode([
    "status" => "success",
    "token" => "",
    "appId" => $appId,
    "channelName" => $channelName,
    "uid" => $uid
]);
?>
