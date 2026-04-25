<?php
// send_group_message.php
include 'headers.php';
include 'connection.php';
include 'push_helper.php';

$group_id = intval($_POST['group_id'] ?? 0);
$sender_id = intval($_POST['sender_id'] ?? 0);
$message = $con->real_escape_string($_POST['message'] ?? '');
$attachment = '';
$file_type = null;

// Handle file upload if any
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $target_dir = "../uploads/chat/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        $attachment = "/uploads/chat/" . $new_filename;
        $mime = $_FILES['file']['type'];
        if (strpos($mime, 'video') !== false) $file_type = 'video';
        else if (strpos($mime, 'image') !== false) $file_type = 'image';
        else $file_type = 'document';
    }
}

// Auto-migration check: add file_type if it doesn't exist
$check_col = $con->query("SHOW COLUMNS FROM `tbl_group_messages` LIKE 'file_type'");
if ($check_col->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_group_messages` ADD COLUMN `file_type` VARCHAR(50) DEFAULT NULL");
}

if (!$group_id || !$sender_id || (empty($message) && empty($attachment))) {
    echo json_encode(["status" => "error", "message" => "Group ID, Sender ID, and Message or Attachment are required"]);
    exit;
}

// Auto-migration check: add admins_only if it doesn't exist
$check_adm = $con->query("SHOW COLUMNS FROM `tbl_groups` LIKE 'admins_only'");
if ($check_adm->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_groups` ADD COLUMN `admins_only` TINYINT(1) DEFAULT 0");
}

// Check Group Permissions
$gQ = $con->query("SELECT created_by, admins_only FROM tbl_groups WHERE id = $group_id");
$group = $gQ->fetch_assoc();

if ($group && $group['admins_only'] == 1) {
    // Check if sender is admin or creator
    $is_admin = ($group['created_by'] == $sender_id);
    if (!$is_admin) {
        $mQ = $con->query("SELECT role FROM tbl_group_members WHERE group_id = $group_id AND user_id = $sender_id");
        $member = $mQ->fetch_assoc();
        if ($member && $member['role'] === 'admin') {
            $is_admin = true;
        }
    }

    if (!$is_admin) {
        echo json_encode(["status" => "error", "message" => "Only admins can send messages to this group"]);
        exit;
    }
}

$sql = "INSERT INTO tbl_group_messages (group_id, sender_id, message, attachment, file_type) VALUES ($group_id, $sender_id, '$message', '$attachment', '$file_type')";

if ($con->query($sql)) {
    // Notifications logic
    try {
        // 1. Get Group Info
        $gQ = $con->query("SELECT group_name FROM tbl_groups WHERE id = $group_id LIMIT 1");
        $group_name = ($gQ && $gRow = $gQ->fetch_assoc()) ? $gRow['group_name'] : "Group";

        // 2. Get Sender Info
        $sQ = $con->query("SELECT name FROM tbl_members WHERE id = $sender_id LIMIT 1");
        $sender_name = ($sQ && $sRow = $sQ->fetch_assoc()) ? $sRow['name'] : "Member";

        // 3. Get All Members to notify
        $mQ = $con->query("SELECT user_id FROM tbl_group_members WHERE group_id = $group_id AND user_id != $sender_id");
        
        $notif_title = $group_name;
        $notif_body = "$sender_name: " . ($attachment ? "📷 Sent an attachment" : $message);

        while($mRow = $mQ->fetch_assoc()){
            $target_id = $mRow['user_id'];
            sendExpoPushNotification($con, $target_id, $notif_title, $notif_body, [
                "type" => "group_chat",
                "group_id" => $group_id
            ]);
        }
    } catch (Exception $e) {
        // Log error but don't fail the message send
        file_put_contents("push_log.txt", date('Y-m-d H:i:s') . " | Group Notif Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    echo json_encode(["status" => "success", "message" => "Message sent successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send message: " . $con->error]);
}
?>
