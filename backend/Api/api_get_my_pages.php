<?php
include 'headers.php';
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$parent_userid = $data['user_id'] ?? $_POST['user_id'] ?? '';

if (!$parent_userid) {
    echo json_encode(["status" => "error", "message" => "user_id is required"]);
    exit;
}

// Fetch all pages created by this user
$stmt = $con->prepare("SELECT m.id, m.name, m.category, m.profile_photo, d.website_url 
                       FROM tbl_members m
                       LEFT JOIN tbl_business_details d ON m.id = d.user_id
                       WHERE m.parent_userid = ? AND m.is_business = 1");
$stmt->bind_param("i", $parent_userid);
$stmt->execute();
$res = $stmt->get_result();

$pages = [];
while ($row = $res->fetch_assoc()) {
    $pages[] = [
        "page_id" => $row['id'],
        "name" => $row['name'],
        "category" => $row['category'],
        "photo" => $row['profile_photo'],
        "website" => $row['website_url']
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $pages
]);
?>
