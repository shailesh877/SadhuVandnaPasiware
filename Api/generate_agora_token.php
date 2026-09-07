<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

class RtcTokenBuilder {
    const RolePublisher = 1;
    const RoleSubscriber = 2;

    public static function buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs) {
        $token = new AccessToken($appID, $appCertificate, $channelName, (string)$uid);
        $PrivilegeKJoinChannel = 1;
        $token->addPrivilege($PrivilegeKJoinChannel, $privilegeExpireTs);
        return $token->build();
    }
}

class AccessToken {
    public $appID;
    public $appCertificate;
    public $channelName;
    public $userAccount;
    public $message;

    public function __construct($appID, $appCertificate, $channelName, $userAccount) {
        $this->appID = $appID;
        $this->appCertificate = $appCertificate;
        $this->channelName = $channelName;
        $this->userAccount = $userAccount;
        $this->message = new Message();
    }

    public function addPrivilege($key, $expireTimestamp) {
        $this->message->privileges[$key] = $expireTimestamp;
    }

    public function build() {
        if (strlen($this->appID) !== 32 || strlen($this->appCertificate) !== 32) {
            return "";
        }
        $msg = $this->message->pack();
        $val = pack('C*', 0, 0, 6) . pack('a*', $this->appID) . pack('V', time()) . pack('V', rand(1, 99999999)) . pack('v', strlen($msg)) . $msg;
        $sig = hash_hmac('sha256', $val, $this->appCertificate, true);
        $crc_channel = pack('V', hexdec(hash('crc32b', $this->channelName)));
        $crc_uid = pack('V', hexdec(hash('crc32b', $this->userAccount)));
        $content = pack('v', strlen($sig)) . $sig . $crc_channel . $crc_uid . pack('v', strlen($msg)) . $msg;
        return "006" . $this->appID . base64_encode($content);
    }
}

class Message {
    public $salt;
    public $ts;
    public $privileges;

    public function __construct() {
        $this->salt = rand(1, 99999999);
        $this->ts = time() + (24 * 3600);
        $this->privileges = array();
    }

    public function pack() {
        $val = pack('V', $this->salt) . pack('V', $this->ts) . pack('v', count($this->privileges));
        foreach ($this->privileges as $key => $value) {
            $val .= pack('v', $key) . pack('V', $value);
        }
        return $val;
    }
}

$channelName = $_REQUEST['channelName'] ?? '';
$uid = intval($_REQUEST['uid'] ?? 0);
$appId = "42eb51e0bc30431cba75efefb9ea15ea";
$appCertificate = "d1a0f552498a410ebea1ce34934de9bf";

if (empty($channelName)) {
    echo json_encode(["status" => "error", "message" => "channelName required"]);
    exit;
}

$expireTime = 86400; // 24 hours
$currentTime = time();
$privilegeExpireTime = $currentTime + $expireTime;

$token = RtcTokenBuilder::buildTokenWithUid($appId, $appCertificate, $channelName, $uid, RtcTokenBuilder::RolePublisher, $privilegeExpireTime);

echo json_encode([
    "status" => "success",
    "token" => $token,
    "appId" => $appId,
    "channelName" => $channelName,
    "uid" => $uid
]);
?>
