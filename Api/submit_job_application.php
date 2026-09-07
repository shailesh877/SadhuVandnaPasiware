<?php
// Enable Error Reporting for debugging (disable in production if needed)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Robust Include Logic
$base_dir = __DIR__;
// 0. Include Connection (Standard Path)
if (!file_exists($base_dir . '/connection.php')) {
    echo json_encode(['status' => 'error', 'message' => 'Connection file not found']);
    exit;
}
include($base_dir . '/connection.php');

// 1. Dynamic Path Resolution for PHPMailer
$possible_paths = [
    $base_dir . '/../php/src/',      // If Api and php are siblings
    $base_dir . '/../src/',          // If Api and src are in root
    $base_dir . '/src/',             // If src is inside Api
    $base_dir . '/../../php/src/'    // If Api is nested deeper
];

$php_src = null;
foreach ($possible_paths as $path) {
    if (file_exists($path . 'PHPMailer.php')) {
        $php_src = $path;
        break;
    }
}

if (!$php_src) {
    echo json_encode(['status' => 'error', 'message' => 'PHPMailer library not found. Checked paths: ' . implode(', ', $possible_paths)]);
    exit;
}

require $php_src . 'Exception.php';
require $php_src . 'PHPMailer.php';
require $php_src . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$job_id = $_POST['job_id'] ?? 0;

if(!$job_id){
    echo json_encode(['status' => 'error', 'message' => 'Job ID required']);
    exit;
}

// Fetch Job Title
$jobQ = $con->query("SELECT title FROM tbl_jobs_education WHERE id='$job_id'");
if($jobQ->num_rows == 0){
    echo json_encode(['status' => 'error', 'message' => 'Job not found']);
    exit;
}
$job_title = $jobQ->fetch_assoc()['title'];

// Upload Folder
$upload_dir = $base_dir . "/../php/uploads/applications/"; 
if(!is_dir($upload_dir)){
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']);
        exit;
    }
}

$name      = htmlspecialchars($_POST['name'] ?? '');
$phone     = htmlspecialchars($_POST['phone'] ?? '');
$email     = htmlspecialchars($_POST['email'] ?? '');
$education = htmlspecialchars($_POST['education'] ?? '');

$attachments = [];
$errors = [];

// Helper to process files
function processFile($fileKey, $prefix, $dir, &$attachments, &$errors){
    if(isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0){
        $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        $filename = time() . "_" . $prefix . "_" . uniqid() . "." . $ext;
        $target = $dir . $filename;
        if(move_uploaded_file($_FILES[$fileKey]['tmp_name'], $target)){
            $attachments[] = $target;
        } else {
            $errors[] = "Failed to upload $fileKey";
        }
    } else {
        $errors[] = "$fileKey is required";
    }
}

processFile('photo', 'photo', $upload_dir, $attachments, $errors);
processFile('aadhaar', 'aadhaar', $upload_dir, $attachments, $errors);
processFile('resume', 'resume', $upload_dir, $attachments, $errors);

if(!empty($errors)){
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit;
}

// Send Email
$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ady10112004@gmail.com'; 
    $mail->Password   = 'loky dacf vmdi hwvi'; // App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('ady10112004@gmail.com', 'Sadhu Vandana Job Portal');
    $mail->addAddress('ady10112004@gmail.com'); // Admin
    $mail->addReplyTo($email, $name);

    // Attachments
    foreach($attachments as $path){
        $mail->addAttachment($path);
    }

    $mail->isHTML(true);
    $mail->Subject = "New Job Application - " . $job_title;
    $mail->Body = "
        <h3>New Job Application Received</h3>
        <p><strong>Job:</strong> $job_title</p>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Education:</strong> $education</p>
        <hr>
        <p>Photo, Aadhaar & Resume attached.</p>
    ";

    $mail->send();

    // Confirmation to User
    $mail->clearAddresses();
    $mail->clearAttachments();
    $mail->addAddress($email);
    $mail->Subject = "Application Submitted - " . $job_title;
    $mail->Body = "
        <h3>Hello $name,</h3>
        <p>Your application for <b>$job_title</b> has been successfully submitted.</p>
        <p>We will contact you soon.</p>
        <br><p>Thank you.</p>
    ";
    
    // Attempt sending confirmation, but don't fail if it fails
    try {
        $mail->send();
    } catch (Exception $e) {
        // Ignore user confirmation error
    }

    echo json_encode(['status' => 'success', 'message' => 'Application sent successfully']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Mail Error: ' . $mail->ErrorInfo]);
}
?>
