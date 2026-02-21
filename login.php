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
<script type="text/javascript">
  var configuration = {
    widgetId: "3662736a6146383834383833",
    tokenAuth: "495236TgKMhDKHXV6996e94bP1",
    exposeMethods: true,
    captchaRenderId: "msg91-captcha", 
    success: (data) => {
        console.log('success response', data);
    },
    failure: (error) => {
        console.log('failure reason', error);
    },
};
</script>
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
    
    <!-- STEP 0: NAME & CASTE -->
    <div id="stepDetails" class="group transition-all flex flex-col gap-4">
        <!-- Name Field -->
        <!-- First Name -->
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">First Name <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                    <i class="fa-solid fa-user"></i>
                </span>
                <input type="text" id="first_name" placeholder="First Name" 
                       class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm" 
                       pattern="^[a-zA-Z\s.]+$" title="Please enter a valid name (letters only)" required />
            </div>
        </div>
        <!-- Middle Name -->
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Middle Name <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                    <i class="fa-solid fa-user-tag"></i>
                </span>
                <input type="text" id="middle_name" placeholder="Middle Name" 
                       class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm" 
                       pattern="^[a-zA-Z\s.]+$" title="Please enter a valid name (letters only)" required />
            </div>
        </div>
        <!-- Last Name -->
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Last Name <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                    <i class="fa-solid fa-user-group"></i>
                </span>
                <input type="text" id="last_name" placeholder="Last Name" 
                       class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm" 
                       pattern="^[a-zA-Z\s.]+$" title="Please enter a valid name (letters only)" required />
            </div>
        </div>

        <!-- Caste Field -->
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Caste (Samaj) <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                    <i class="fa-solid fa-users"></i>
                </span>
                <select id="caste" class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all shadow-sm appearance-none" required>
                    <option value="" disabled selected>Select Caste</option>
                    <option value="Kapdi">Kapdi</option>
                    <option value="Deshani">Deshani</option>
                    <option value="Dudhrejia">Dudhrejia</option>
                    <option value="Danidhariya">Danidhariya</option>
                    <option value="Gondaliya">Gondaliya</option>
                    <option value="Mesvaniya">Mesvaniya</option>
                    <option value="Ramkabir">Ramkabir</option>
                    <option value="Ramsnehi">Ramsnehi</option>
                    <option value="Vaghani">Vaghani</option>
                    <option value="Chapbai">Chapbai</option>
                    <option value="Parabiya">Parabiya</option>
                    <option value="Hariyani">Hariyani</option>
                    <option value="Sarpadadiya">Sarpadadiya</option>
                    <option value="Ramdevputra">Ramdevputra</option>
                    <option value="Ravibhan">Ravibhan</option>
                    <option value="Baroliya">Baroliya</option> 
                    <option value="Other">Other</option>
                </select>
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
            </div>
        </div>
    </div>

    <!-- STEP 1: MOBILE -->
    <div id="stepEmail" class="group transition-all">
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Mobile Number <span class="text-red-500">*</span></label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                <i class="fa-solid fa-mobile-screen"></i>
            </span>
            <input type="tel" id="mobile" placeholder="Enter 10-digit Mobile Number" maxlength="10" 
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
            <span class="text-xs text-green-600 font-medium"><i class="fa-solid fa-paper-plane mr-1"></i> OTP Sent to mobile</span>
            <button type="button" id="resendBtn" class="text-xs text-orange-600 hover:text-orange-800 font-semibold underline disabled:opacity-50 disabled:cursor-not-allowed">Resend OTP</button>
        </div>
    </div>

    <div id="msg91-captcha" class="mb-4"></div>
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

    function showMsg(msg, type='error') {
        const color = type === 'success' ? 'text-green-600' : 'text-red-500';
        $('#loginMsg').removeClass('text-red-500 text-green-600').addClass(color).html(msg).fadeIn();
        if(type === 'success') setTimeout(() => $('#loginMsg').fadeOut(), 3000);
    }

    $('#loginForm').submit(function(e){
        e.preventDefault();
        
        let firstName = $('#first_name').val().trim();
        let middleName = $('#middle_name').val().trim();
        let lastName = $('#last_name').val().trim();
        // Combine for backend storage (First + Middle + Last)
        let name = firstName + " " + middleName + " " + lastName;

        let caste = $('#caste').val();
        let mobile = $('#mobile').val().trim();
        let btn = $('#submitBtn');

        const nameRegex = /^[a-zA-Z\s.]+$/;
        if(!firstName || !nameRegex.test(firstName)){ showMsg('Invalid First Name (Letters only)'); return; }
        if(!middleName || !nameRegex.test(middleName)){ showMsg('Invalid Middle Name (Letters only)'); return; }
        if(!lastName || !nameRegex.test(lastName)){ showMsg('Invalid Last Name (Letters only)'); return; }
        if(!caste){ showMsg('Please select your caste'); return; }
        if(caste === 'Other'){ showMsg('Only users from authorized castes are allowed in this community.'); return; }
        if(!mobile){ showMsg('Please enter your mobile number'); return; }
        if(!validateMobile(mobile)){ showMsg('Invalid mobile number (10 digits required)'); return; }

        // Check Eligibility with Backend
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Checking...');
        
        $.post('login_send_otp.php', {mobile: mobile, name: name, caste: caste}, function(checkRes){
            let check = checkRes.trim();
            
            if(check === 'allowed'){
                 // --- MSG91 Headless Send ---
                 // User provided configuration uses '91' + mobile in examples
                 // window.sendOtp(identifier, success, failure)
                 
                 window.sendOtp('91' + mobile, function(data){
                     console.log('OTP Sent:', data);
                     showMsg('OTP Sent Successfully', 'success');
                     
                     // Helper: Hide Get Code Btn, Show OTP Input
                     $('#submitBtn').hide();
                     
                     // Remove existing OTP div if any
                     $('#otpDiv').remove();
                     
                     // Append OTP Input Form
                     let otpHtml = `
                        <div id="otpDiv" class="mt-4 animate-fade-in-up">
                            <input type="text" id="enteredOtp" placeholder="Enter 6-digit OTP" maxlength="6"
                                   class="w-full border-2 border-gray-200 p-3 rounded-xl text-center font-bold tracking-[0.5em] text-xl focus:border-orange-500 outline-none transition-colors">
                            <button type="button" id="verifyOtpBtn" 
                                    class="mt-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl py-3 w-full shadow-lg transition-transform active:scale-95">
                                Verify OTP
                            </button>
                            <p class="text-xs text-center mt-2 text-gray-400 cursor-pointer hover:text-orange-500" onclick="window.retryOtp('11')">Resend OTP</p>
                        </div>
                     `;
                     
                     $(otpHtml).insertAfter('#submitBtn');
                     
                     // Verify Click Handler
                     $('#verifyOtpBtn').click(function(){
                         let otpVal = $('#enteredOtp').val().trim();
                         if(!otpVal) { showMsg('Enter OTP'); return; }
                         
                         $(this).html('<i class="fa-solid fa-spinner fa-spin"></i> Verifying...');
                         
                         window.verifyOtp(otpVal, function(vData){
                             console.log('Verified:', vData);
                             showMsg('Verified! Logging in...', 'success');
                             
                             // Use FETCH as requested, sending JSON payload
                             fetch('login_verify_otp.php', {
                                 method: 'POST',
                                 headers: {
                                     'Content-Type': 'application/json'
                                 },
                                 body: JSON.stringify({
                                     access_token: vData.message, // Token from MSG91
                                     mobile: mobile,
                                     name: name,
                                     caste: caste,
                                     via_widget: true
                                 })
                             })
                             .then(response => response.text())
                             .then(res => {
                                res = res.trim();
                                if(res.includes('success_login')){
                                    showMsg('Login Successful! Redirecting...', 'success');
                                    setTimeout(() => window.location.href = 'index.php', 1000);
                                } else if(res.includes('success_register')) {
                                    showMsg('Account Created! Redirecting...', 'success');
                                    setTimeout(() => window.location.href = 'profile.php', 1000); 
                                } else {
                                    showMsg('Login failed: ' + res);
                                    $('#verifyOtpBtn').html('Verify OTP');
                                }
                             })
                             .catch(err => {
                                 console.error("Fetch Error:", err);
                                 showMsg('Connection Error during Login');
                                 $('#verifyOtpBtn').html('Verify OTP');
                             });
                             
                         }, function(err){
                             console.error(err);
                             showMsg('Invalid OTP. Please try again.');
                             $('#verifyOtpBtn').html('Verify OTP');
                         });
                     });

                 }, function(err){
                     console.error('SendOTP Error:', err);
                     // Alert the exact error so user sees "IPBlocked"
                     alert('OTP Failed: ' + (err.message || JSON.stringify(err)));
                     showMsg('Failed to send OTP.');
                     btn.prop('disabled', false).html('Get Login Code <i class="fa-solid fa-arrow-right"></i>');
                 });

            } else if(check === 'invalid_caste'){
                showMsg('Only users from authorized castes are allowed.');
                btn.prop('disabled', false).html('Get Login Code <i class="fa-solid fa-arrow-right"></i>');
            } else if(check === 'limit_exceeded'){
                 showMsg('Daily login limit exceeded.'); 
                 btn.prop('disabled', false).html('Get Login Code <i class="fa-solid fa-arrow-right"></i>');
            } else {
                 showMsg(check);
                 btn.prop('disabled', false).html('Get Login Code <i class="fa-solid fa-arrow-right"></i>');
            }
        }).fail(function(){
            showMsg('Connection error.');
            btn.prop('disabled', false).html('Get Login Code <i class="fa-solid fa-arrow-right"></i>');
        });
    });

    function validateMobile(mobile) {
        const re = /^[0-9]{10}$/;
        return re.test(mobile);
    }
});
</script>
<script type="text/javascript" onload="initSendOTP(configuration)" src="https://verify.msg91.com/otp-provider.js"></script>
</body>
</html>
