<?php
// update_group.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_POST['group_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);
$group_name = $con->real_escape_string($_POST['group_name'] ?? '');

if (!$group_id || !$user_id) {
    echo json_encode(["status" => "error", "message" => "Group ID and User ID are required"]);
    exit;
}

// Check if user is admin (created_by)
$check = $con->query("SELECT created_by, photo FROM tbl_groups WHERE id = $group_id");
$group = $check->fetch_assoc();

if (!$group || $group['created_by'] != $user_id) {
    echo json_encode(["status" => "error", "message" => "Only group admin can update the group details"]);
    exit;
}

$update_parts = [];

if (!empty($group_name)) {
    $update_parts[] = "name = '$group_name'";
}

$description = $con->real_escape_string($_POST['description'] ?? '');
if (isset($_POST['description'])) {
    $update_parts[] = "description = '$description'";
}

// Handle file upload
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $target_dir = "../uploads/photo/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
    $new_filename = 'group_' . $group_id . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        $update_parts[] = "photo = '$new_filename'";
        // Delete old photo if exists
        if ($group['photo'] && file_exists($target_dir . $group['photo'])) {
            unlink($target_dir . $group['photo']);
        }
    }
}

if (count($update_parts) > 0) {
    $sql = "UPDATE tbl_groups SET " . implode(", ", $update_parts) . " WHERE id = $group_id";
    if ($con->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Group updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $con->error]);
    }
} else {
    echo json_encode(["status" => "success", "message" => "No changes provided"]);
}
?>
