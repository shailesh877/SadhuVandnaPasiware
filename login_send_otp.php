<?php
session_start();

if (isset($_POST['mobile']) && isset($_POST['name']) && isset($_POST['caste'])) {
    $mobile = trim($_POST['mobile']);
    $name = trim($_POST['name']);
    $caste = trim($_POST['caste']);

    // Allowed Castes List
    $allowed_castes = [
        "Kapdi", "Deshani", "Dudhrejia", "Danidhariya", "Gondaliya", "Mesvaniya", 
        "Ramkabir", "Ramsnehi", "Vaghani", "Chapbai", "Parabiya", "Hariyani", 
        "Sarpadadiya", "Ramdevputra", "Ravibhan", "Baroliya"
    ];

    // Validate Caste
    if (!in_array($caste, $allowed_castes)) {
        echo "invalid_caste";
        exit;
    }

    // Basic Mobile Validation
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        echo "invalid_mobile";
        exit;
    }
    
    // --- Rate Limiting (Max 2 OTPs per day - DB Based) ---
    include("connection.php");
    date_default_timezone_set('Asia/Kolkata');
    $today = date('Y-m-d');
    
    // Create table if not exists (One-time setup)
    $createTable = "CREATE TABLE IF NOT EXISTS tbl_otp_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mobile VARCHAR(15) NOT NULL,
        sent_time DATETIME NOT NULL
    )";
    mysqli_query($con, $createTable);

    // Count attempts for this mobile today
    $stmt = $con->prepare("SELECT COUNT(*) FROM tbl_otp_attempts WHERE mobile=? AND DATE(sent_time)=?");
    $stmt->bind_param("ss", $mobile, $today);
    $stmt->execute();
    $stmt->bind_result($attempt_count);
    $stmt->fetch();
    $stmt->close();

    if ($attempt_count >= 2) {
        echo "limit_exceeded";
        exit;
    }

    // Insert attempt
    $now = date('Y-m-d H:i:s');
    $ins = $con->prepare("INSERT INTO tbl_otp_attempts (mobile, sent_time) VALUES (?, ?)");
    $ins->bind_param("ss", $mobile, $now);
    $ins->execute();
    $ins->close();

    // Generate OTP (For session fallback)
    $otp = rand(100000, 999999);
    $_SESSION['login_otp'] = $otp;
    $_SESSION['login_mobile'] = $mobile;
    $_SESSION['login_name'] = $name;
    $_SESSION['login_caste'] = $caste;
    $_SESSION['login_otp_expiry'] = time() + 300;

    // Everything is validated, allow the JS widget to take over
    echo "allowed";

} else {
    echo "missing_mobile";
}
?>