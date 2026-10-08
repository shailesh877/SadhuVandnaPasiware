<?php
include 'headers.php';
include 'connection.php';

$action = $_GET['action'] ?? 'fetch_frames';

if ($action === 'fetch_frames') {
    $frames = [];
    $stmt = $con->prepare("SELECT id, title, frame_image FROM tbl_festival_frames ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $frames[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $frames
    ]);
}
?>
