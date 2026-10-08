<?php
include 'headers.php';
include 'connection.php';

$state_id = intval($_GET['state_id'] ?? 0);

if ($state_id > 0) {
    $query = "SELECT id, district_name as name, state_id FROM tbl_districts WHERE state_id = ? ORDER BY district_name ASC";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $state_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $districts = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $districts[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $districts]);
} else {
    echo json_encode(["status" => "error", "message" => "state_id is required."]);
}
?>
