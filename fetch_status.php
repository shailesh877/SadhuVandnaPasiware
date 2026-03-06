<?php
// fetch_status.php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("connection.php");
date_default_timezone_set('Asia/Kolkata');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header('Content-Type: application/json');

$response = ['online'=>false, 'last_active'=>null, 'is_typing'=>false, 'max_seen_id'=>0];

try {
    // Check connection first
    if(!$con){
        $response['error'] = "Database Connection Missing";
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    $profile_id = intval($_GET['profile_id'] ?? 0);
    $my_profile = intval($_GET['my_profile_id'] ?? 0);
    $platform   = $_GET['platform'] ?? 'marriage';

    if(!$profile_id){ 
        $response['error'] = "Missing profile_id";
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    // 1. Online check
    if($platform === 'community'){
        $res = $con->query("SELECT (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(last_active) < 25) AS online, last_active FROM tbl_members WHERE id='$profile_id' LIMIT 1");
    } else {
        $res = $con->query("SELECT (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(m.last_active) < 25) AS online, m.last_active FROM tbl_members m JOIN tbl_marriage_profiles mp ON mp.user_id=m.id WHERE mp.id='$profile_id' LIMIT 1");
    }

    if($res && $row = $res->fetch_assoc()){
        $response['online'] = !empty($row['online']);
        if(!$response['online'] && !empty($row['last_active'])) {
            $response['last_active'] = date("d M, h:i A", strtotime($row['last_active']));
        }
    }

    // 2. Typing check
    $typ = $con->query("SELECT is_typing FROM tbl_typing WHERE profile_id='$profile_id' AND target_profile_id='$my_profile' AND chat_platform='$platform' LIMIT 1");
    if($typ && $typ->num_rows > 0){
        $r = $typ->fetch_assoc();
        $response['is_typing'] = (!empty($r['is_typing'])) ? true : false;
    }

    // 3. Max Seen ID (Avoid crashes if seen column or platform column is missing)
    $s = $con->query("SELECT MAX(id) as m FROM tbl_messages WHERE sender_id='$my_profile' AND receiver_id='$profile_id' AND chat_platform='$platform' AND seen=1");
    if($s && $row = $s->fetch_assoc()){
        $response['max_seen_id'] = intval($row['m'] ?? 0);
    }

    // 4. Incoming Calls Check (Defensive in case tbl_calls missing)
    try {
        $inc = $con->query("SELECT id, caller_id, type FROM tbl_calls WHERE receiver_id='$my_profile' AND status='ringing' AND chat_platform='$platform' AND created_at > (NOW() - INTERVAL 1 MINUTE) ORDER BY id DESC LIMIT 1");
        if($inc && $inc->num_rows > 0){
            $call = $inc->fetch_assoc();
            $c_info = null;
            if($platform === 'community'){
                $c_res = $con->query("SELECT name as full_name, profile_photo as photo FROM tbl_members WHERE id='".$call['caller_id']."' LIMIT 1");
            } else {
                $c_res = $con->query("SELECT full_name, photo FROM tbl_marriage_profiles WHERE id='".$call['caller_id']."' LIMIT 1");
            }
            
            if($c_res && $c_info = $c_res->fetch_assoc()){
                $response['incoming_call'] = [
                    'call_id' => $call['id'],
                    'caller_id' => $call['caller_id'],
                    'caller_name' => $c_info['full_name'] ?? 'Unknown',
                    'caller_photo' => !empty($c_info['photo']) ? (strpos($c_info['photo'],'http')===0 ? $c_info['photo'] : "uploads/photo/".$c_info['photo']) : "images/logo.png",
                    'type' => $call['type']
                ];
            }
        }
    } catch (Exception $ce) { $response['call_status_error'] = $ce->getMessage(); }

    // 5. Call Status Update (For the caller side)
    try {
        $my_call = $con->query("SELECT id, status FROM tbl_calls WHERE caller_id='$my_profile' AND chat_platform='$platform' AND status IN ('accepted', 'rejected', 'ended') AND created_at > (NOW() - INTERVAL 1 MINUTE) ORDER BY id DESC LIMIT 1");
        if($my_call && $my_call->num_rows > 0){
            $mc = $my_call->fetch_assoc();
            $response['call_update'] = [
                'call_id' => $mc['id'],
                'status' => $mc['status']
            ];
        }
    } catch (Exception $uce) { $response['update_error'] = $uce->getMessage(); }

    } catch (Throwable $t) {
        $response['error'] = $t->getMessage();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

ob_end_clean();
echo json_encode($response);
exit;
