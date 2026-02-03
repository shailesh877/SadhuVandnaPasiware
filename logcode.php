<?php
include("connection.php");
session_start();

$user = trim($_POST['user']);
$password = $_POST['password'];

if(empty($user) || empty($password)){
    echo "<script>alert('Please fill all fields'); window.history.back();</script>";
    exit;
}

$stmt = $con->prepare("SELECT * FROM tbl_members WHERE email=? OR mobile=? LIMIT 1");
$stmt->bind_param("ss", $user, $user);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 1){
    $row = $result->fetch_assoc();

    // 🔥 BLOCK CHECK HERE
    if ($row['status'] == 'Blocked') {
        echo "<script>alert('Your account is blocked. Please contact support.'); window.history.back();</script>";
        exit;
    }

    if(password_verify($password, $row['password'])){
        $_SESSION['sadhu_user_id'] = $row['email'];
        $_SESSION['sadhu_user_name'] = $row['name'];

        setcookie('sadhu_user_id', $row['email'], time() + (30*24*60*60), "/");
        setcookie('sadhu_user_name', $row['name'], time() + (30*24*60*60), "/");
?>
<?php
        echo "<script>window.location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('Invalid password'); window.history.back();</script>";
        exit;
    }
}

?>
