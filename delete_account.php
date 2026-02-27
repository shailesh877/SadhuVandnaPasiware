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

// Check if user has a marriage profile
$has_marriage = false;
$check_m = mysqli_query($con, "SELECT id FROM tbl_marriage_profiles WHERE user_id=(SELECT id FROM tbl_members WHERE mobile='$mobile' LIMIT 1)");
if($check_m && mysqli_num_rows($check_m) > 0) {
    $has_marriage = true;
}

$success_msg = "";
$error_msg = "";

// --- Handle Deletion Logic ---
if(isset($_POST['otp_verified']) && $_POST['otp_verified'] == '1') {
    $action_type = $_POST['action_type'] ?? ''; // 'full_account' or 'marriage_only'
    
    // Fetch User ID
    $u_q = mysqli_query($con, "SELECT id, profile_photo, cover_photo FROM tbl_members WHERE mobile='$mobile' LIMIT 1");
    
    if($u_row = mysqli_fetch_assoc($u_q)){
        $uid_db = $u_row['id'];
        $main_photo = $u_row['profile_photo'];

        if($action_type === 'marriage_only'){
            // --- OPTION A: DELETE MARRIAGE PROFILE ONLY ---
            $mp_q = mysqli_query($con, "SELECT photo FROM tbl_marriage_profiles WHERE user_id='$uid_db'");
            if($mp_row = mysqli_fetch_assoc($mp_q)){
                if(!empty($mp_row['photo'])){
                    $mp_path = "uploads/photo/" . $mp_row['photo'];
                    if(file_exists($mp_path)) unlink($mp_path);
                }
            }
            $del_profile = mysqli_query($con, "DELETE FROM tbl_marriage_profiles WHERE user_id='$uid_db'");
            if($del_profile){
                $success_msg = "Your Marriage Profile has been deleted successfully.";
            } else {
                $error_msg = "Database Error: " . mysqli_error($con);
            }

        } elseif($action_type === 'full_account'){
            // --- OPTION B: DELETE FULL ACCOUNT ---
            // Delete Stories
            $s_q = mysqli_query($con, "SELECT media FROM tbl_stories WHERE user_id='$uid_db'");
            while($s = mysqli_fetch_assoc($s_q)){
                if(!empty($s['media']) && file_exists('uploads/stories/'.$s['media'])) unlink('uploads/stories/'.$s['media']);
            }
            mysqli_query($con, "DELETE FROM tbl_stories WHERE user_id='$uid_db'");

            // Delete Posts
            $post_q = mysqli_query($con, "SELECT media FROM tbl_posts WHERE user_id='$uid_db'");
            while($post = mysqli_fetch_assoc($post_q)){
                if(!empty($post['media']) && file_exists("uploads/posts/".$post['media'])) unlink("uploads/posts/".$post['media']);
            }
            mysqli_query($con, "DELETE FROM tbl_posts WHERE user_id='$uid_db'");
            mysqli_query($con, "DELETE FROM tbl_likes WHERE user_id='$uid_db'");
            mysqli_query($con, "DELETE FROM tbl_comments WHERE user_id='$uid_db'");

            // Delete Marriage Profile
            $mp_q = mysqli_query($con, "SELECT photo FROM tbl_marriage_profiles WHERE user_id='$uid_db'");
            if($mp_row = mysqli_fetch_assoc($mp_q)){
                if(!empty($mp_row['photo']) && file_exists("uploads/photo/".$mp_row['photo'])) unlink("uploads/photo/".$mp_row['photo']);
            }
            mysqli_query($con, "DELETE FROM tbl_marriage_profiles WHERE user_id='$uid_db'");

            // Delete Messages/Messages
            mysqli_query($con, "DELETE FROM tbl_messages WHERE sender_id='$uid_db' OR receiver_id='$uid_db'");

            // Delete Profile Photos
            if(!empty($main_photo) && file_exists("uploads/photo/".$main_photo)) unlink("uploads/photo/".$main_photo);
            if(!empty($u_row['cover_photo']) && file_exists("uploads/photo/".$u_row['cover_photo'])) unlink("uploads/photo/".$u_row['cover_photo']);

            // Delete Main Account
            $del_member = mysqli_query($con, "DELETE FROM tbl_members WHERE id='$uid_db'");

            if($del_member) {
                session_unset();
                session_destroy();
                setcookie("sadhu_user_id", "", time() - 3600, "/");
                echo "<script>alert('Account deleted.'); window.location.href='login';</script>";
                exit;
            } else {
                $error_msg = "System Error: Unable to delete account.";
            }
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
                    <?php if($has_marriage): ?>
                    <button onclick="switchTab('marriage')" id="tab_marriage" 
                            class="flex-1 py-3 px-4 rounded-lg text-sm font-bold text-gray-600 transition-all duration-300 hover:text-gray-900 border border-transparent">
                        <i class="fa-solid fa-ring mr-2"></i> Delete Marriage Profile
                    </button>
                    <?php endif; ?>
                    <button onclick="switchTab('full')" id="tab_full" 
                            class="flex-1 py-3 px-4 rounded-lg text-sm font-bold text-gray-600 transition-all duration-300 hover:text-red-600 border border-transparent">
                        <i class="fa-solid fa-user-slash mr-2"></i> Delete Full Account
                    </button>
                </div>

                <!-- FORM -->
                <form method="POST" action="" id="deleteForm" class="space-y-6">
                    <input type="hidden" name="action_type" id="action_type" value="<?= $has_marriage ? 'marriage_only' : 'full_account' ?>">

                    <!-- Content Section -->
                    <?php if($has_marriage): ?>
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
                    <?php endif; ?>

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
                        
                        <div class="flex flex-col gap-3 mb-4">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <i class="fa-regular fa-envelope text-xs"></i>
                                    </span>
                                    <input type="text" value="<?= substr($email, 0, 3) . '****@' . explode('@', $email)[1] ?>" disabled 
                                           class="block w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-xs">
                                </div>
                                <div class="relative flex-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <i class="fa-solid fa-mobile-screen text-xs"></i>
                                    </span>
                                    <input type="text" value="<?= substr($mobile, 0, 2) . '******' . substr($mobile, -2) ?>" disabled 
                                           class="block w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-xs">
                                </div>
                            </div>
                            <!-- MSG91 CAPTCHA Container -->
                            <div id="msg91-captcha" class="mb-4 mt-2 bg-gray-50 p-2 rounded-xl border border-gray-100 flex justify-center"></div>

                            <button type="button" onclick="sendOTP()" id="otpBtn" 
                                    class="bg-gray-800 hover:bg-black text-white px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-lg active:scale-95">
                                <i class="fa-solid fa-paper-plane mr-2"></i> Send OTP (Email & SMS)
                            </button>
                        </div>
                        
                        <!-- NEW: Inline OTP Input (Appears after sending) -->
                        <div id="otpEntrySection" class="hidden animate-fade-in">
                            <div class="relative mb-3">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fa-solid fa-key text-xs"></i>
                                </span>
                                <input type="number" id="enteredOtp" placeholder="Enter 6-digit OTP" 
                                       class="block w-full pl-8 pr-3 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all font-mono text-center text-lg tracking-widest">
                            </div>
                            <button type="button" onclick="verifyMyOtp()" id="verifyBtn" 
                                    class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-xl shadow-md transition-all active:scale-95">
                                Verify OTP
                            </button>
                        </div>

                        <div id="otpInputDiv" class="hidden">
                             <div class="bg-green-50 text-green-700 p-4 rounded-xl text-sm font-bold flex items-center gap-3 border border-green-100">
                                <i class="fa-solid fa-circle-check text-lg"></i> 
                                <span>Verification Successful! You can now delete the profile.</span>
                             </div>
                             <input type="hidden" name="otp_verified" value="1">
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
    switchTab('<?= $has_marriage ? 'marriage' : 'full' ?>');

    function sendOTP(){
        const btn = document.getElementById('otpBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        
        fetch('delete_send_otp.php?check_only=1')
        .then(res => res.text())
        .then(resp => {
            if(resp.trim() === 'allowed'){
                if(typeof window.sendOtp === 'function'){
                    window.sendOtp('91' + '<?= $mobile ?>', function(data){
                        console.log('OTP Sent:', data);
                        btn.innerHTML = 'OTP Sent';
                        document.getElementById('otpEntrySection').classList.remove('hidden');
                    }, function(err){
                        alert('Error: ' + (err.message || 'Failed to send'));
                        btn.disabled = false;
                        btn.innerHTML = 'Retry Send';
                    });
                } else {
                    alert('Service not loaded');
                    btn.disabled = false;
                }
            } else {
                alert('Session Error');
                btn.disabled = false;
            }
        });
    }

    function verifyMyOtp(){
        let otpVal = document.getElementById('enteredOtp').value.trim();
        if(!otpVal) { alert('Please enter OTP'); return; }

        const vBtn = document.getElementById('verifyBtn');
        vBtn.disabled = true;
        vBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';

        window.verifyOtp(otpVal, function(data){
            document.getElementById('otpEntrySection').classList.add('hidden');
            document.getElementById('otpBtn').classList.add('hidden');
            document.getElementById('otpInputDiv').classList.remove('hidden');
            document.getElementById('finalBtn').disabled = false;
        }, function(err){
            alert('Invalid OTP. Please try again.');
            vBtn.disabled = false;
            vBtn.innerHTML = 'Verify OTP';
        });
    }
</script>

<script type="text/javascript">
  var configuration = {
    widgetId: "3662736a6146383834383833",
    tokenAuth: "495236TgKMhDKHXV6996e94bP1",
    exposeMethods: true,
    captchaRenderId: "msg91-captcha", 
    success: (data) => { console.log('success response', data); },
    failure: (error) => { console.log('failure reason', error); },
  };
</script>
<script type="text/javascript" onload="initSendOTP(configuration)" src="https://verify.msg91.com/otp-provider.js"></script>
