<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Sadhu Vandana</title>
<link rel="icon" href="images/logo.png">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Roboto', sans-serif; }
  .gradient-bg { background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); }
  /* Glass effect only for Desktop */
  @media (min-width: 768px) {
    .glassmin { background: rgba(255,255,255,0.95) !important; box-shadow: 0 10px 40px -10px rgba(251,146,60,0.2); }
  }
  
  .modal-bg { background: rgba(0,0,0,0.5); }
  /* Animation for inputs */
  .slide-down { animation: slideDown 0.3s ease-out forwards; }
  @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body class="gradient-bg min-h-screen block md:flex md:justify-center md:items-center p-0 md:px-4 md:py-6 relative overflow-x-hidden">

<!-- Decorative Blobs (Hidden on Mobile) -->
<div class="hidden md:block absolute top-0 left-0 w-64 h-64 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 -translate-x-1/2 -translate-y-1/2"></div>
<div class="hidden md:block absolute bottom-0 right-0 w-64 h-64 bg-red-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 translate-x-1/2 translate-y-1/2"></div>

<div class="glassmin bg-white md:rounded-2xl rounded-none border-0 md:border border-white/50 w-full min-h-screen md:min-h-auto md:max-w-md p-6 md:p-8 flex flex-col justify-center md:justify-start gap-6 relative z-10 transition-all duration-300">
  
  <div class="text-center">
      <div class="w-16 h-16 bg-orange-100/50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-orange-200">
        <i class="fa-solid fa-users-viewfinder text-3xl text-orange-600"></i>
      </div>
      <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
      <p class="text-sm text-gray-500 mt-1">Enter your email to login or create account</p>
  </div>

  <form id="loginForm" class="flex flex-col gap-4">
    
    <!-- STEP 1: EMAIL -->
    <div id="stepEmail" class="group transition-all">
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Email Address</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                <i class="fa-regular fa-envelope"></i>
            </span>
            <input type="email" id="email" placeholder="name@example.com" 
                   class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm" required />
        </div>
    </div>

    <!-- STEP 2: OTP (Hidden initially) -->
    <div id="stepOtp" class="hidden slide-down group transition-all">
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Verification Code</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                <i class="fa-solid fa-key"></i>
            </span>
            <input type="text" id="otp" placeholder="Enter 6-digit OTP" maxlength="6"
                   class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm font-mono tracking-widest text-lg" />
        </div>
        <div class="flex justify-between items-center mt-2">
            <span class="text-xs text-green-600 font-medium"><i class="fa-solid fa-paper-plane mr-1"></i> OTP Sent to email</span>
            <button type="button" id="resendBtn" class="text-xs text-orange-600 hover:text-orange-800 font-semibold underline disabled:opacity-50 disabled:cursor-not-allowed">Resend OTP</button>
        </div>
    </div>

    <button type="submit" id="submitBtn" 
            class="mt-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold rounded-xl py-3.5 w-full shadow-lg hover:shadow-orange-500/30 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 flex justify-center items-center gap-2">
        <span>Get Login Code</span> <i class="fa-solid fa-arrow-right"></i>
    </button>
  </form>
  
  <div id="loginMsg" class="text-center text-sm font-medium min-h-[20px] transition-all"></div>

  <div class="text-center text-xs text-gray-400 mt-4 border-t pt-4">
    <p>By continuing, you agree to our <a href="child_safety" class="text-gray-600 hover:text-orange-600 underline">Child Safety</a> & <a href="https://sadhuvandna.co.in/policy" class="text-gray-600 hover:text-orange-600 underline">Privacy Policy</a></p>
  </div>
</div>

<script>
$(document).ready(function(){
    let isOtpSent = false;
    let timerInterval;

    function showMsg(msg, type='error') {
        const color = type === 'success' ? 'text-green-600' : 'text-red-500';
        $('#loginMsg').removeClass('text-red-500 text-green-600').addClass(color).html(msg).fadeIn();
        // Clear message after 3 seconds if success
        if(type === 'success') setTimeout(() => $('#loginMsg').fadeOut(), 3000);
    }

    function startTimer(duration) {
        let timer = duration, minutes, seconds;
        $('#resendBtn').prop('disabled', true);
        
        clearInterval(timerInterval);
        timerInterval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#resendBtn').text("Resend in " + minutes + ":" + seconds);

            if (--timer < 0) {
                clearInterval(timerInterval);
                $('#resendBtn').prop('disabled', false).text("Resend OTP");
            }
        }, 1000);
    }

    $('#loginForm').submit(function(e){
        e.preventDefault();
        
        let email = $('#email').val().trim();
        let otp = $('#otp').val().trim();
        let btn = $('#submitBtn');

        if(!isOtpSent) {
            // --- STATE 1: SEND OTP ---
            if(!email){ showMsg('Please enter your email address'); return; }
            if(!validateEmail(email)){ showMsg('Invalid email format'); return; }

            // UI Loading
            btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Sending OTP...');
            $('#email').prop('readonly', true).addClass('opacity-70 cursor-not-allowed');

            $.post('login_send_otp.php', {email: email}, function(response){
                btn.prop('disabled', false).html('Get Login Code <i class="fa-solid fa-arrow-right"></i>');
                
                if(response.trim() === 'sent'){
                    // Success
                    isOtpSent = true;
                    $('#stepOtp').removeClass('hidden');
                    $('#otp').focus();
                    btn.html('Verify & Login <i class="fa-solid fa-right-to-bracket"></i>');
                    showMsg('OTP sent successfully!', 'success');
                    startTimer(60); // 1 minute timer
                } else if(response.trim() === 'invalid_email') {
                    showMsg('Please enter a valid email address');
                    $('#email').prop('readonly', false).removeClass('opacity-70 cursor-not-allowed').focus();
                } else {
                    showMsg('Failed to send OTP. Please try again.');
                    $('#email').prop('readonly', false).removeClass('opacity-70 cursor-not-allowed');
                }
            }).fail(function(){
                btn.prop('disabled', false).html('Get Login Code <i class="fa-solid fa-arrow-right"></i>');
                $('#email').prop('readonly', false).removeClass('opacity-70 cursor-not-allowed');
                showMsg('Connection error. Please check internet.');
            });

        } else {
            // --- STATE 2: VERIFY OTP ---
            if(!otp){ showMsg('Please enter the 6-digit OTP'); return; }
            if(otp.length !== 6 || isNaN(otp)){ showMsg('OTP must be 6 digits'); return; }

            // UI Loading
            btn.prop('disabled', true).html('<i class="fa-solid fa-circle-notch fa-spin"></i> Verifying...');

            $.post('login_verify_otp.php', {otp: otp}, function(response){
                let res = response.trim();
                if(res.includes('success_login')){
                    showMsg('Login Successful! Redirecting...', 'success');
                    setTimeout(() => window.location.href = 'index.php', 1000);
                } else if(res.includes('success_register')) {
                    showMsg('Account Created! Redirecting...', 'success');
                    setTimeout(() => window.location.href = 'profile.php', 1000); // Redirect to profile to fill details
                } else if(res === 'invalid_otp') {
                    showMsg('Invalid OTP. Please try again.');
                    btn.prop('disabled', false).html('Verify & Login <i class="fa-solid fa-right-to-bracket"></i>');
                } else if(res === 'expired_otp') {
                    showMsg('OTP Expired. Please resend.');
                    btn.prop('disabled', false).html('Verify & Login <i class="fa-solid fa-right-to-bracket"></i>');
                } else if(res === 'blocked') {
                    showMsg('Your account is blocked. Contact support Team.');
                    btn.prop('disabled', false).html('Verify & Login <i class="fa-solid fa-right-to-bracket"></i>');
                } else {
                    showMsg('Login failed: ' + res);
                    btn.prop('disabled', false).html('Verify & Login <i class="fa-solid fa-right-to-bracket"></i>');
                }
            }).fail(function(){
                showMsg('Connection error.');
                btn.prop('disabled', false).html('Verify & Login <i class="fa-solid fa-right-to-bracket"></i>');
            });
        }
    });

    // Resend Logic
    $('#resendBtn').click(function(){
        let email = $('#email').val().trim();
        if(!email){ showMsg('Enter email first'); return; }
        
        $(this).prop('disabled', true).text('Sending...');
        
        $.post('login_send_otp.php', {email: email}, function(response){
            if(response.trim() === 'sent'){
                showMsg('OTP Resent!', 'success');
                startTimer(60);
            } else {
                showMsg('Failed to send OTP');
                $('#resendBtn').prop('disabled', false).text('Resend OTP');
            }
        });
    });

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
</script>
</body>
</html>
