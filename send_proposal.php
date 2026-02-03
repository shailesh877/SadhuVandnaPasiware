<?php
include("connection.php");
include("header.php");
// session_start();

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email) die("Unauthorized access!");

// ✅ Get current user's marriage profile ID
$sender_profile = $con->query("
    SELECT mp.id AS profile_id
    FROM tbl_marriage_profiles mp
    INNER JOIN tbl_members m ON m.id = mp.user_id
    WHERE m.email='$user_email' LIMIT 1
")->fetch_assoc();

$sender_id = $sender_profile['profile_id'] ?? 0;

if(!$sender_id){
    echo '
    <div class="max-w-lg mx-auto mt-10 p-6 bg-red-50 border border-red-300 rounded-xl shadow-md text-center">
        
        <div class="text-red-600 text-4xl mb-3">
            <i class="fa fa-exclamation-circle"></i>
        </div>

        <h2 class="text-xl font-bold text-red-700 mb-2">
            Marriage Profile Not Found
        </h2>

        <p class="text-red-600 text-sm mb-4">
            You need to create your marriage profile before you can chat or send requests.
        </p>

        <a href="add_marriage_profile.php"
            class="inline-block bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
            Create Marriage Profile
        </a>

        <p class="text-gray-500 text-xs mt-3">
            Redirecting you automatically...
        </p>
    </div>

    <script>
        setTimeout(function(){
            window.location.href = "add_marriage_profile.php";
        }, 10000);
    </script>
    ';
    exit;
}


// ✅ Get receiver profile ID
$receiver_id = intval($_GET['to'] ?? 0);
$profile_id  = intval($_GET['profile_id'] ?? 0);

if(!$receiver_id || !$profile_id) die("Invalid request!");

// ✅ Check if proposal already exists between these profiles
$exists = $con->query("
    SELECT id FROM tbl_proposals
    WHERE sender_id='$sender_id' AND receiver_id='$receiver_id' AND status='pending' LIMIT 1
");

if($exists->num_rows == 0){
    $con->query("
        INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status)
        VALUES ('$sender_id','$receiver_id','$profile_id','pending')
    ");
}

echo "<script>
        alert('Proposal Sent Successfully!');
        window.location='view_marriage_profile.php?id=$profile_id';
      </script>";
?>
