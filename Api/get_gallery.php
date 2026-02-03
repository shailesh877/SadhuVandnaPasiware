<?php
include 'headers.php';
include 'connection.php';

// Try to select from tbl_gallery
$sql = "SELECT * FROM tbl_gallery ORDER BY id DESC";
$res = $con->query($sql);

if($res){
    $data = [];
    while($row = $res->fetch_assoc()){
        // Adjust column name if needed. Assuming 'image' or 'photo'.
        // If 'image' doesn't exist but 'photo' does, we might need a check.
        // For now, assume 'image' as per standard.
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    // Table not found or error
    echo json_encode(["status" => "error", "message" => "Gallery not found or DB error: " . $con->error]);
}
?>
