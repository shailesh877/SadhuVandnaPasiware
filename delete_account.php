<?php
include("header.php");

// Get logged-in user email
$mobile = $_SESSION['sadhu_user_id'];
// Fetch Email for display
$email = "";
$e_q = mysqli_query($con, "SELECT email FROM tbl_members WHERE mobile='$mobile'");
if($e_row = mysqli_fetch_assoc($e_q)){
    $email = $e_row['email'];
}
if(empty($email)) $email = "user@example.com"; 

$success_msg = "";
$error_msg = "";

// --- Handle Deletion Logic ---
if(isset($_POST['confirm_delete_otp'])) {
    
    $action_type = $_POST['action_type'] ?? ''; // 'full' or 'marriage'
    $entered_otp = trim($_POST['otp_code']);
    
    // 1. Verify OTP
    if(empty($_SESSION['delete_otp']) || empty($_SESSION['delete_otp_expiry'])){
        $error_msg = "OTP Session expired. Please request a new code.";
    } elseif(time() > $_SESSION['delete_otp_expiry']){
        $error_msg = "OTP has expired. Please request a new code.";
    } elseif($entered_otp != $_SESSION['delete_otp']){
        $error_msg = "Invalid OTP. Please enter the correct code sent to your email.";
    } else {
        // OTP Valid - Proceed with Deletion
        
        // Fetch User ID
        $u_q = mysqli_query($con, "SELECT id, profile_photo FROM tbl_members WHERE mobile='$mobile' LIMIT 1");
        
        if(mysqli_num_rows($u_q) > 0){
            $u_row = mysqli_fetch_assoc($u_q);
            $uid_db = $u_row['id'];
            $main_photo = $u_row['profile_photo'];

            if($action_type === 'marriage_only'){
                // --- OPTION A: DELETE MARRIAGE PROFILE ONLY ---
                
                // delete photo
                $mp_q = mysqli_query($con, "SELECT photo FROM tbl_marriage_profiles WHERE user_id='$uid_db'");
                if($mp_q && $mp_row = mysqli_fetch_assoc($mp_q)){
                    if(!empty($mp_row['photo'])){
                        $mp_path = "uploads/photo/" . $mp_row['photo'];
                        if(file_exists($mp_path)) unlink($mp_path);
                    }
                }
                
                // delete profile record
                $del_profile = mysqli_query($con, "DELETE FROM tbl_marriage_profiles WHERE user_id='$uid_db'");
                
                if($del_profile){
                    $success_msg = "Your Marriage Profile has been deleted successfully.";
                    // Clear OTP session
                    unset($_SESSION['delete_otp']);
                    unset($_SESSION['delete_otp_expiry']);
                } else {
                    $error_msg = "Database Error: " . mysqli_error($con);
                }

            } elseif($action_type === 'full_account'){
                // --- OPTION B: DELETE FULL ACCOUNT ---
                // We attempt to delete all related data. We suppress errors for individual tables but track main deletion.

                // 1. Stories
                $s_q = mysqli_query($con, "SELECT media FROM tbl_stories WHERE user_id='$uid_db'");
                if($s_q){
                    while($s = mysqli_fetch_assoc($s_q)){
                        if(!empty($s['media']) && file_exists($s['media'])) unlink($s['media']); // path is stored as full relative path usually
                         elseif(!empty($s['media']) && file_exists('uploads/stories/'.$s['media'])) unlink('uploads/stories/'.$s['media']);
                    }
                }
                mysqli_query($con, "DELETE FROM tbl_stories WHERE user_id='$uid_db'");
                mysqli_query($con, "DELETE FROM tbl_story_views WHERE user_id='$uid_db'"); // My views on others
                // Views on my stories will cascade if DB set up, or we leave them as orphan records usually acceptable, or delete by story_id join (complex)

                // 2. Posts & Media
                $post_q = mysqli_query($con, "SELECT media FROM tbl_posts WHERE user_id='$uid_db'");
                if($post_q){
                    while($post = mysqli_fetch_assoc($post_q)){
                        if(!empty($post['media'])){
                            $file_path = "uploads/posts/" . $post['media'];
                            if(file_exists($file_path)) unlink($file_path);
                        }
                    }
                }
                mysqli_query($con, "DELETE FROM tbl_likes WHERE user_id='$uid_db'");
                mysqli_query($con, "DELETE FROM tbl_comments WHERE user_id='$uid_db'");
                mysqli_query($con, "DELETE FROM tbl_posts WHERE user_id='$uid_db'");

                // 3. Marriage Profile
                $mp_q = mysqli_query($con, "SELECT photo FROM tbl_marriage_profiles WHERE user_id='$uid_db'");
                if($mp_q && $mp_row = mysqli_fetch_assoc($mp_q)){
                    if(!empty($mp_row['photo'])){
                        $mp_path = "uploads/photo/" . $mp_row['photo'];
                        if(file_exists($mp_path)) unlink($mp_path);
                    }
                }
                mysqli_query($con, "DELETE FROM tbl_marriage_profiles WHERE user_id='$uid_db'");

                // 4. Messages/Calls/Typing
                mysqli_query($con, "DELETE FROM tbl_messages WHERE sender_id='$uid_db' OR receiver_id='$uid_db'");
                mysqli_query($con, "DELETE FROM tbl_calls WHERE caller_id='$uid_db' OR receiver_id='$uid_db'");
                mysqli_query($con, "DELETE FROM tbl_typing WHERE profile_id='$uid_db' OR target_profile_id='$uid_db'");

                // 5. Wallet
                mysqli_query($con, "DELETE FROM tbl_wallet WHERE user_id='$uid_db'");

                // 6. Delete Main Member Photo
                if(!empty($main_photo)){
                    $m_path = "uploads/photo/" . $main_photo;
                    if(file_exists($m_path)) unlink($m_path);
                }

                // 7. Delete Member (Main Account)
                $del_member = mysqli_query($con, "DELETE FROM tbl_members WHERE id='$uid_db'");

                if($del_member) {
                    // Logout
                    session_unset();
                    session_destroy();
                    setcookie("sadhu_user_id", "", time() - 3600, "/");
                    setcookie("sadhu_user_name", "", time() - 3600, "/");
                    
                    echo "<script>
                        alert('Your account has been permanently deleted.');
                        window.location.href = 'login';
                    </script>";
                    exit;
                } else {
                    $error_msg = "System Error: Unable to delete account. " . mysqli_error($con);
                }
            } else {
                $error_msg = "Invalid request type.";
            }

        } else {
             $error_msg = "User verification failed. Please re-login.";
        }
    }
}
?>

<main class="flex-1 px-4 md:px-10 py-10 md:ml-20 mb-13 md:mb-0 max-w-7xl mx-auto w-full min-h-[80vh] flex items-center justify-center">
    
    <div class="relative w-full max-w-2xl">
        
        <!-- Background Blur -->
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-red-200 rounded-full mix-blend-multiply filter blur-2xl opacity-60 animate-blob"></div>
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-orange-200 rounded-full mix-blend-multiply filter blur-2xl opacity-60 animate-blob animation-delay-2000"></div>

        <div class="relative bg-white/95 backdrop-blur-xl rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-red-600 to-orange-600 p-6 text-center text-white">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-inner ring-2 ring-white/30">
                   <i class="fa-solid fa-user-shield text-3xl text-white"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Account Management</h1>
                <p class="text-red-100 text-xs mt-1 uppercase tracking-wider font-medium">Secure Deletion Center</p>
            </div>
            
            <div class="p-6 md:p-8">

                <!-- MESSAGES -->
                <?php if($error_msg) { ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r shadow-sm">
                        <div class="flex">
                            <i class="fa-solid fa-circle-exclamation text-red-500 mt-1 mr-3"></i>
                            <p class="text-sm text-red-700 font-medium"><?php echo $error_msg; ?></p>
                        </div>
                    </div>
                <?php } ?>

                <?php if($success_msg) { ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r shadow-sm">
                        <div class="flex">
                            <i class="fa-solid fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <p class="text-sm text-green-700 font-medium"><?php echo $success_msg; ?></p>
                        </div>
                    </div>
                <?php } ?>

                <!-- TABS -->
                <div class="flex mb-6 bg-gray-100 p-1 rounded-xl">
                    <button onclick="switchTab('marriage')" id="tab_marriage" 
                            class="flex-1 py-3 px-4 rounded-lg text-sm font-bold text-gray-600 transition-all duration-300 hover:text-gray-900 border border-transparent">
                        <i class="fa-solid fa-ring mr-2"></i> Delete Marriage Profile
                    </button>
                    <button onclick="switchTab('full')" id="tab_full" 
                            class="flex-1 py-3 px-4 rounded-lg text-sm font-bold text-gray-600 transition-all duration-300 hover:text-red-600 border border-transparent">
                        <i class="fa-solid fa-user-slash mr-2"></i> Delete Full Account
                    </button>
                </div>

                <!-- FORM -->
                <form method="POST" action="" id="deleteForm" class="space-y-6">
                    <input type="hidden" name="action_type" id="action_type" value="marriage_only">

                    <!-- Content Section -->
                    <div id="content_marriage" class="tab-content">
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 mb-4">
                            <h3 class="text-blue-800 font-bold text-lg mb-2">Delete Marriage Profile Only</h3>
                            <p class="text-blue-700 text-sm mb-3">
                                This will <strong>only remove your profile from the Marriage section</strong>. Your main account, posts, and login access will remain active.
                            </p>
                            <ul class="list-disc list-inside text-xs text-blue-600 space-y-1">
                                <li>You will still be able to log in.</li>
                                <li>Your posts and messages will remain.</li>
                                <li>Only your bio-data and marriage photos will be deleted.</li>
                            </ul>
                        </div>
                    </div>

                    <div id="content_full" class="tab-content hidden">
                        <div class="bg-red-50 border border-red-100 rounded-xl p-5 mb-4">
                            <h3 class="text-red-800 font-bold text-lg mb-2">Delete Entire Account</h3>
                            <p class="text-red-700 text-sm mb-3">
                                This is a <strong>PERMANENT</strong> action. Everything associated with your account will be wiped.
                            </p>
                            <ul class="list-disc list-inside text-xs text-red-600 space-y-1">
                                <li><strong>Access:</strong> You will lose access immediately.</li>
                                <li><strong>Data:</strong> Posts, messages, marriage profile, and photos will be deleted.</li>
                                <li><strong>Recovery:</strong> This cannot be undone.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- OTP Section -->
                    <div class="border-t border-gray-100 pt-6">
                        <p class="text-sm font-medium text-gray-700 mb-2">Security Verification</p>
                        
                        <div class="flex gap-2 mb-4">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fa-regular fa-envelope"></i>
                                </span>
                                <input type="text" value="<?= substr($email, 0, 3) . '****@' . explode('@', $email)[1] ?>" disabled 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm">
                            </div>
                            <button type="button" onclick="sendOTP()" id="otpBtn" 
                                    class="bg-gray-800 hover:bg-black text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap min-w-[120px]">
                                Get OTP
                            </button>
                        </div>
                        
                        <div id="otpInputDiv" class="hidden transition-all duration-500 ease-in-out">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input type="number" name="otp_code" placeholder="Enter 6-digit OTP code" id="otpField"
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all">
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex justify-between">
                                <span>Check your email inbox/spam folder.</span>
                                <span id="timer" class="font-bold text-orange-600"></span>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-8 pt-4 border-t border-gray-100">
                        <a href="profile" class="flex items-center justify-center w-full bg-white text-gray-700 font-bold py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm group">
                            Cancel
                        </a>
                        <button type="submit" name="confirm_delete_otp" id="finalBtn" disabled
                                class="flex items-center justify-center w-full bg-red-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm transition-all">
                             Confirm Delete
                        </button>
                    </div>

                </form>
            </div>
            
        </div>
    </div>
</main>

<script>
    let currentTab = 'marriage';
    
    function switchTab(tab){
        currentTab = tab;
        document.getElementById('action_type').value = (tab === 'full') ? 'full_account' : 'marriage_only';
        
        // Hide/Show Contents
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('content_' + tab).classList.remove('hidden');

        // Styles
        const btnMarriage = document.getElementById('tab_marriage');
        const btnFull = document.getElementById('tab_full');

        if(tab === 'marriage'){
            btnMarriage.classList.add('bg-white', 'shadow-sm', 'text-blue-600', 'border-gray-200');
            btnMarriage.classList.remove('text-gray-600', 'border-transparent');
            
            btnFull.classList.remove('bg-white', 'shadow-sm', 'text-red-600', 'border-gray-200');
            btnFull.classList.add('text-gray-600', 'border-transparent');
        } else {
            btnFull.classList.add('bg-white', 'shadow-sm', 'text-red-600', 'border-gray-200');
            btnFull.classList.remove('text-gray-600', 'border-transparent');
            
            btnMarriage.classList.remove('bg-white', 'shadow-sm', 'text-blue-600', 'border-gray-200');
            btnMarriage.classList.add('text-gray-600', 'border-transparent');
        }
    }
    // Init
    switchTab('marriage');

    function sendOTP(){
        const btn = document.getElementById('otpBtn');
        const timerSpan = document.getElementById('timer');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        
        // Clear previous errors
        
        fetch('delete_send_otp.php')
        .then(res => res.text())
        .then(resp => {
            if(resp.trim() === 'sent'){
                document.getElementById('otpInputDiv').classList.remove('hidden');
                document.getElementById('finalBtn').disabled = false;
                btn.innerHTML = 'Sent!';
                startTimer(60);
            } else {
                alert('Error sending OTP: ' + resp);
                btn.disabled = false;
                btn.innerHTML = 'Retry OTP';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Request failed');
            btn.disabled = false;
            btn.innerHTML = 'Retry OTP';
        });
    }

    function startTimer(duration) {
        let timer = duration, minutes, seconds;
        const display = document.getElementById('timer');
        const btn = document.getElementById('otpBtn');
        
        const interval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = "Resend in " + minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(interval);
                btn.disabled = false;
                btn.innerHTML = 'Resend OTP';
                display.textContent = "";
            }
        }, 1000);
    }
</script>