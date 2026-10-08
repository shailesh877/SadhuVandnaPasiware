<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

require_once("RtcTokenBuilder.php");

$channelName = $_REQUEST['channelName'] ?? '';
$uid = intval($_REQUEST['uid'] ?? 0);
$appId = "42eb51e0bc30431cba75efefb9ea15ea";
$appCertificate = "fe7dd7f92b154294ab7f444b771d6ae6";

if (empty($channelName)) {
    echo json_encode(["status" => "error", "message" => "channelName required"]);
    exit;
}

$role = RtcTokenBuilder::RolePublisher;
$expireTimeInSeconds = 3600;
$currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

$token = RtcTokenBuilder::buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);

echo json_encode([
    "status" => "success",
    "token" => $token,
    "appId" => $appId,
    "channelName" => $channelName,
    "uid" => $uid
]);
?>
