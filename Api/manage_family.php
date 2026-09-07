<?php
include 'headers.php';
include 'connection.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? '';

if(!$user_id && $action != 'delete'){ // Delete might pass id and user_id via POST or GET, need consistency.
   // For fetching, user_id is required.
}

if ($action == 'fetch') {
    if(!$user_id){
         echo json_encode(["status" => "error", "message" => "User ID required"]);
         exit;
    }
    $query = "SELECT * FROM tbl_family_members WHERE user_id='$user_id' ORDER BY id DESC";
    $result = $con->query($query);
    $members = [];
    while($row = $result->fetch_assoc()){
        $members[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $members]);

} elseif ($action == 'add') {
    $name = $_POST['name'] ?? '';
    $relation = $_POST['relation'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    
    $height = $_POST['height'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $education = $_POST['education'] ?? '';
    $income = $_POST['income'] ?? '';
    $caste = $_POST['caste'] ?? '';
    $kuldevi = $_POST['kuldevi'] ?? '';
    
    $photo_name = '';
    if (isset($_FILES['photo'])) {
        $target_dir = "../uploads/family/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_name = $new_filename;
        }
    }

    $stmt = $con->prepare("INSERT INTO tbl_family_members (user_id, name, relation, gender, occupation, photo, dob, marital_status, height, weight, education, income, caste, kuldevi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssssssss", $user_id, $name, $relation, $gender, $occupation, $photo_name, $dob, $marital_status, $height, $weight, $education, $income, $caste, $kuldevi);
    
    if($stmt->execute()){
         echo json_encode(["status" => "success", "message" => "Member added"]);
    } else {
         echo json_encode(["status" => "error", "message" => "Failed to add member"]);
    }


} elseif ($action == 'update') {
    $id = $_POST['id'] ?? '';
    if(!$id){ echo json_encode(["status" => "error", "message" => "ID required"]); exit; }

    $name = $_POST['name'] ?? '';
    $relation = $_POST['relation'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';

    $height = $_POST['height'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $education = $_POST['education'] ?? '';
    $income = $_POST['income'] ?? '';
    $caste = $_POST['caste'] ?? '';
    $kuldevi = $_POST['kuldevi'] ?? '';
    
    // Check for new photo
    $photo_sql = "";
    $types = "ssssssssssss"; // 12 strings
    $params = [$name, $relation, $gender, $occupation, $dob, $marital_status, $height, $weight, $education, $income, $caste, $kuldevi];

    if (isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
        $target_dir = "../uploads/family/";
        $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_sql = ", photo=?";
            $types .= "s";
            $params[] = $new_filename;
            
            // Delete old photo
            $old = $con->query("SELECT photo FROM tbl_family_members WHERE id='$id'")->fetch_assoc();
            if($old['photo'] && file_exists("../uploads/family/".$old['photo'])){
                unlink("../uploads/family/".$old['photo']);
            }
        }
    }
    
    // Add ID to params at the end
    $types .= "i";
    $params[] = $id;

    $stmt = $con->prepare("UPDATE tbl_family_members SET name=?, relation=?, gender=?, occupation=?, dob=?, marital_status=?, height=?, weight=?, education=?, income=?, caste=?, kuldevi=? $photo_sql WHERE id=?");
    
    // Bind dynamic params
    $stmt->bind_param($types, ...$params);

    if($stmt->execute()){
         echo json_encode(["status" => "success", "message" => "Member updated"]);
    } else {
         echo json_encode(["status" => "error", "message" => "Failed to update member"]);
    }

} elseif ($action == 'delete') {

    $id = $_POST['id'] ?? '';
    // Optional: verify ownership with user_id if needed, but ID is usually unique primary key
    if(!$id){
        echo json_encode(["status" => "error", "message" => "ID required"]);
        exit;
    }
    
    // Get photo to delete
    $res = $con->query("SELECT photo FROM tbl_family_members WHERE id='$id'");
    if($res->num_rows > 0){
        $row = $res->fetch_assoc();
        if($row['photo'] && file_exists("../uploads/family/".$row['photo'])){
            unlink("../uploads/family/".$row['photo']);
        }
    }

    if($con->query("DELETE FROM tbl_family_members WHERE id='$id'")){
        echo json_encode(["status" => "success", "message" => "Member deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Delete failed"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}
?>
