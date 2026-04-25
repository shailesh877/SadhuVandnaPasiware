<?php
// send_group_message.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_POST['group_id'] ?? 0);
$sender_id = intval($_POST['sender_id'] ?? 0);
$message = $con->real_escape_string($_POST['message'] ?? '');
$attachment = '';

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
        $attachment = $new_filename;
    }
}

if (!$group_id || !$sender_id || (empty($message) && empty($attachment))) {
    echo json_encode(["status" => "error", "message" => "Group ID, Sender ID, and Message or Attachment are required"]);
    exit;
}

$sql = "INSERT INTO tbl_group_messages (group_id, sender_id, message, attachment) VALUES ($group_id, $sender_id, '$message', '$attachment')";

if ($con->query($sql)) {
    echo json_encode(["status" => "success", "message" => "Message sent successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send message: " . $con->error]);
}
?>
