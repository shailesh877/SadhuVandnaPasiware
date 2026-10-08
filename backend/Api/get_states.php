<?php
include 'headers.php';
include 'connection.php';

$query = "SELECT id, state_name as name FROM tbl_states ORDER BY state_name ASC";
$result = $con->query($query);

$states = [];
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $states[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $states]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $con->error]);
}
?>
