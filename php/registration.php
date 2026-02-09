<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Sadhu Vandana</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Roboto', sans-serif; }
    .gradient-bg { background: linear-gradient(110deg, #ffedd5 10%, #fffbe1 90%); }
    .glassmin { background: rgba(255,255,255,0.92); box-shadow: 0 3px 40px 0 rgba(251,146,60,.09); }
    .fade-in { animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from {opacity:0; transform:translateY(-4px);} to {opacity:1; transform:translateY(0);} }
  </style>
</head>
<body class="gradient-bg min-h-screen flex flex-col justify-center items-center py-0 px-2">

  <div class="glassmin rounded-xl border border-orange-200 mx-auto px-4 py-6 w-full max-w-sm flex flex-col gap-3">
    <form id="registerForm" action="registration_code.php" method="post" class="flex flex-col gap-3" autocomplete="on">
      <h2 class="text-lg font-bold text-orange-800 mb-1 flex items-center gap-2 justify-center">
        <i class="fa fa-user-plus"></i> Create New Account
      </h2>

      <!-- Name -->
      <input required type="text" name="name" placeholder="Full Name"
        class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200" />

      <!-- Email + Send OTP -->
      <div class="flex gap-2 items-center relative">
        <input required type="email" name="email" id="email" placeholder="Email Address"
          class="border rounded-lg px-3 py-2 text-base flex-1 focus:outline-none focus:ring-2 ring-orange-200" />
        <button type="button" id="sendOtpBtn"
          class="bg-orange-500 text-white px-3 py-2 rounded-lg font-bold hover:bg-orange-700 transition text-xs">
          Send OTP
        </button>
      </div>

      <!-- OTP Input -->
      <div id="otpSection" class="hidden flex gap-2 items-center fade-in">
        <input type="text" id="otp" name="otp" placeholder="Enter OTP"
          class="border rounded-lg px-3 py-2 text-base flex-1 focus:outline-none focus:ring-2 ring-orange-200" maxlength="6" />
        <button type="button" id="verifyOtpBtn"
          class="bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg px-3 py-2 text-xs transition">
          Verify OTP
        </button>
      </div>

      <!-- Message box -->
      <div id="messageBox" class="hidden text-sm text-center font-semibold py-2 px-3 rounded-lg fade-in"></div>

      <!-- Hidden fields (enable after OTP verify) -->
      <div id="moreFields" class="hidden flex flex-col gap-3">
        <input type="text" name="phone" placeholder="Mobile Number" required
          class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200" />

        <input type="text" name="city" placeholder="City" required
          class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200" />
        <input type="text" name="cast" placeholder="Cast/Community" required
          class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200" />
        <input type="date" name="dob" placeholder="Date Of Birth" required
          class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200" />

        <select required name="gender"
          class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200">
          <option value="">Select Gender</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>

        <input type="password" name="password" placeholder="Create Password" required
          class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200" />

        <input type="password" name="confirm_password" placeholder="Confirm Password" required
          class="border rounded-lg px-3 py-2 text-base focus:outline-none focus:ring-2 ring-orange-200" />

        <button type="submit"
          class="bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg py-2 text-base w-full shadow-md mt-2 transition">
          Register
        </button>
      </div>

      <div class="text-center text-xs text-gray-600 mt-0.5">
        Already have an account? 
        <a href="login" class="text-orange-700 font-bold underline hover:text-orange-800">Login</a>
      </div>
    </form>

    <div class="mt-2 text-[11px] text-gray-400 text-center select-none">
      By registering, you agree to our <a href="#" class="text-orange-500 underline">Terms & Policy</a>
    </div>
  </div>

<script>
let otpVerified = false;

// DOM message system
function showMessage(text, type = 'info') {
    const box = $('#messageBox');
    box.removeClass('hidden bg-red-100 bg-green-100 bg-orange-100 text-red-700 text-green-700 text-orange-700');
    if (type === 'success') box.addClass('bg-green-100 text-green-700');
    else if (type === 'error') box.addClass('bg-red-100 text-red-700');
    else box.addClass('bg-orange-100 text-orange-700');
    box.text(text).addClass('fade-in');
}

// Send OTP
$('#sendOtpBtn').click(function() {
    let email = $('#email').val().trim();
    if(email === '') { showMessage('Please enter your email first!', 'error'); return; }

    $('#sendOtpBtn').prop('disabled', true).text('Sending...');

    $.post('send_otp.php', { email: email }, function(res) {
        res = res.trim();
        if(res === 'sent') {
            showMessage('OTP sent to your email address ✉️', 'success');
            $('#otpSection').removeClass('hidden');
            $('#sendOtpBtn').addClass('hidden');
        } else {
            showMessage('Failed to send OTP. Try again!', 'error');
            $('#sendOtpBtn').prop('disabled', false).text('Send OTP');
        }
    });
});

// Verify OTP
$('#verifyOtpBtn').click(function() {
    let otp = $('#otp').val().trim();
    if(otp === '') { showMessage('Please enter the OTP!', 'error'); return; }

    $('#verifyOtpBtn').prop('disabled', true).text('Verifying...');

    $.post('verify_otp.php', { otp: otp }, function(res) {
        res = res.trim();
        if(res === 'verified') {
            otpVerified = true;
            showMessage('✅ OTP Verified Successfully!', 'success');
            $('#otpSection').addClass('hidden');
            $('#moreFields').removeClass('hidden');
        } else {
            showMessage('❌ Invalid or expired OTP. Try again!', 'error');
            $('#verifyOtpBtn').prop('disabled', false).text('Verify OTP');
        }
    });
});

// Prevent submit until verified
$('#registerForm').submit(function(e) {
    if(!otpVerified) {
        e.preventDefault();
        showMessage('Please verify your email before submitting!', 'error');
    }
});
</script>

</body>
</html>
