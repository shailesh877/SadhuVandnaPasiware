<?php
include("connection.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if(!isset($_GET['job_id'])){
    die("Invalid request");
}

$job_id = intval($_GET['job_id']);
$job = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM tbl_jobs_education WHERE id='$job_id'"));

if(!$job){
    die("Job not found");
}

$success = "";
$error = "";

/* ---------- FILE UPLOAD FOLDER ---------- */
$upload_dir = "uploads/applications/";
if(!is_dir($upload_dir)){
    mkdir($upload_dir, 0777, true);
}

if(isset($_POST['apply'])){

    $name      = htmlspecialchars($_POST['name']);
    $phone     = htmlspecialchars($_POST['phone']);
    $email     = htmlspecialchars($_POST['email']);
    $education = htmlspecialchars($_POST['education']);

    /* FILES */
    $photo   = $_FILES['photo'];
    $aadhaar = $_FILES['aadhaar'];
    $resume  = $_FILES['resume'];

    $photo_path   = $upload_dir . time() . "_" . $photo['name'];
    $aadhaar_path = $upload_dir . time() . "_" . $aadhaar['name'];
    $resume_path  = $upload_dir . time() . "_" . $resume['name'];

    move_uploaded_file($photo['tmp_name'], $photo_path);
    move_uploaded_file($aadhaar['tmp_name'], $aadhaar_path);
    move_uploaded_file($resume['tmp_name'], $resume_path);

    /* ---------- PHPMailer CONFIG ---------- */
    $mail = new PHPMailer(true);

    try {

        // ✅ SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;

        // ✅ TUMHARA GMAIL
        $mail->Username   = 'info@sadhuvandna.co.in'; 
        $mail->Password   = 'Info$%^&*756'; // 🔴 App Password dalo

        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;


        // ✅ FROM
        $mail->setFrom('info@sadhuvandna.co.in', 'Sadhu Vandna Job Portal');

        // ✅ ADMIN MAIL
        $mail->addAddress('info@sadhuvandna.co.in');

        // ✅ USER CONFIRMATION MAIL
        $mail->addReplyTo($email, $name);

        // ✅ ATTACH FILES
        $mail->addAttachment($photo_path);
        $mail->addAttachment($aadhaar_path);
        $mail->addAttachment($resume_path);

        // ✅ MAIL CONTENT
        $mail->isHTML(true);
        $mail->Subject = "New Job Application - " . $job['title'];

        $mail->Body = "
        <h3>New Job Application Received</h3>
        <p><strong>Job:</strong> {$job['title']}</p>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Education:</strong> $education</p>
        <hr>
        <p>Photo, Aadhaar & Resume attached.</p>
        ";

        // ✅ SEND TO ADMIN
        $mail->send();

        // ✅ SEND CONFIRMATION TO USER
        $mail->clearAddresses();
        $mail->clearAttachments();

        $mail->addAddress($email);
        $mail->Subject = "Application Submitted - " . $job['title'];
        $mail->Body = "
        <h3>Hello $name,</h3>
        <p>Your application for <b>{$job['title']}</b> has been successfully submitted.</p>
        <p>We will contact you soon.</p>
        <br><p>Thank you.</p>
        ";

        $mail->send();

        $success = "✅ Application sent successfully!";

    } catch (Exception $e) {
        $error = "❌ Mail Error: {$mail->ErrorInfo}";
    }
}
?>



<!DOCTYPE html>
<html>
<head>
  <title>Apply For Job</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body class="bg-[#faf9f7]">

<section class="max-w-xl mx-auto px-4 py-10">

  <div class="bg-white border border-orange-300 rounded-2xl shadow-xl p-8">

    <h2 class="text-2xl font-extrabold text-orange-700 mb-2">
      Apply For Job
    </h2>

    <p class="text-gray-700 font-semibold mb-6">
      <?= htmlspecialchars($job['title']) ?>
    </p>

    <?php if($success){ ?>
      <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4"><?= $success ?></div>
    <?php } ?>

    <?php if($error){ ?>
      <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?= $error ?></div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">

      <div>
        <label class="text-sm font-semibold">Full Name</label>
        <input type="text" name="name" required class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 ring-orange-300"/>
      </div>

      <div>
        <label class="text-sm font-semibold">Phone</label>
        <input type="text" name="phone" required class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 ring-orange-300"/>
      </div>

      <div>
        <label class="text-sm font-semibold">Email</label>
        <input type="email" name="email" required class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 ring-orange-300"/>
      </div>

      <div>
        <label class="text-sm font-semibold">Education</label>
        <input type="text" name="education" required class="w-full mt-1 border rounded-lg px-4 py-2"/>
      </div>

      

      <div>
        <label class="text-sm font-semibold">Photo</label>
        <input type="file" name="photo" required class="w-full mt-1"/>
      </div>

      <div>
        <label class="text-sm font-semibold">Aadhaar</label>
        <input type="file" name="aadhaar" required class="w-full mt-1"/>
      </div>

      <div>
        <label class="text-sm font-semibold">Resume</label>
        <input type="file" name="resume" required class="w-full mt-1"/>
      </div>

      <button name="apply"
        class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 rounded-lg font-bold shadow-md transition">
        <i class="fa fa-paper-plane mr-1"></i> Submit Application
      </button>

    </form>

  </div>
</section>

</body>
</html>
