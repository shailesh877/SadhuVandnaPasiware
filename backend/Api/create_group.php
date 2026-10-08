<?php
include 'headers.php';
include 'connection.php';

$user_id = intval($_POST['user_id'] ?? 0);
$group_name = $con->real_escape_string($_POST['group_name'] ?? '');
$description = $con->real_escape_string($_POST['description'] ?? '');
$platform = $con->real_escape_string($_POST['platform'] ?? 'community');
$member_ids = $_POST['member_ids'] ?? ''; // expects comma separated user IDs

if (!$user_id || empty($group_name)) {
    echo json_encode(["status" => "error", "message" => "User ID and Group Name are required"]);
    exit;
}

// Ensure the creator is in the member list
$members = array_filter(explode(',', $member_ids));
if (!in_array($user_id, $members)) {
    $members[] = $user_id;
}

// Handle file upload
$photo_filename = '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $target_dir = "../uploads/photo/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
    $new_filename = 'group_init_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        $photo_filename = $new_filename;
    }
}

// 1. Insert into tbl_groups
$sql = "INSERT INTO tbl_groups (name, description, created_by, platform, photo) VALUES ('$group_name', '$description', $user_id, '$platform', '$photo_filename')";
if ($con->query($sql)) {
    $group_id = $con->insert_id;

    // 2. Insert into tbl_group_members
    foreach ($members as $mem_id) {
        $m_id = intval($mem_id);
        if ($m_id > 0) {
            $role = ($m_id === $user_id) ? 'admin' : 'member';
            $con->query("INSERT INTO tbl_group_members (group_id, user_id, role) VALUES ($group_id, $m_id, '$role')");
        }
    }

    echo json_encode(["status" => "success", "message" => "Group created successfully", "group_id" => $group_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create group: " . $con->error]);
}
?>
