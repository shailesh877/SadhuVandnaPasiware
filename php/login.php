<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Sadhu Vandana</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Roboto', sans-serif; }
  .gradient-bg { background: linear-gradient(110deg, #ffedd5 10%, #fffbe1 90%); }
  .glassmin { background: rgba(255,255,255,0.93); box-shadow: 0 3px 40px 0 rgba(251,146,60,.09); }
  .modal-bg { background: rgba(0,0,0,0.5); }
</style>
</head>
<body class="gradient-bg min-h-screen flex justify-center items-center px-4 py-6">

<div class="glassmin rounded-xl border border-orange-200 w-full max-w-md p-6 flex flex-col gap-4">
  <h2 class="text-xl font-bold text-orange-800 text-center flex items-center justify-center gap-2">
    <i class="fa fa-sign-in"></i> Member Login
  </h2>
  <form  action="logcode.php" method="post" id="loginForm" class="flex flex-col gap-3">
    <input required type="text" name="user" placeholder="Email/Mobile No." class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 ring-orange-200 w-full" />
    <input required type="password" name="password" placeholder="Password" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 ring-orange-200 w-full" />
    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg py-2 w-full shadow-md transition">Login</button>
    <div class="flex justify-between text-xs text-gray-600 mt-1">
      <button type="button" id="forgotBtn" class="text-orange-600 font-bold underline hover:text-orange-800">Forgot Password?</button>
      <a href="registration" class="text-orange-600 font-bold underline hover:text-orange-800">Create Account</a>
    </div>
  </form>
  <div id="loginMsg" class="text-center text-sm text-red-600 mt-1 select-none"></div>
</div>

<!-- Forgot Password Modal -->
<div id="forgotModal" class="fixed inset-0 hidden items-center justify-center modal-bg px-4">
  <div class="bg-white rounded-xl p-5 w-full max-w-md relative flex flex-col gap-4">
    <h3 class="text-orange-700 font-bold text-lg text-center">Reset Password</h3>

    <div id="fpMsg" class="text-sm text-red-600 text-center select-none"></div>

    <!-- Step 1: Email -->
    <div id="stepEmail" class="flex flex-col gap-2">
      <input type="email" id="fpEmail" placeholder="Enter registered email" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 ring-orange-200 w-full" />
      <button id="sendFpOtp" class="bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg py-2 w-full flex justify-center items-center gap-2">
        <span>Send OTP</span> <i class="fa fa-paper-plane"></i>
      </button>
    </div>

    <!-- Step 2: OTP -->
    <div id="stepOtp" class="flex flex-col gap-2 hidden">
      <input type="text" id="fpOtp" placeholder="Enter OTP" maxlength="6" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 ring-orange-200 w-full" />
      <button id="verifyFpOtp" class="bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg py-2 w-full flex justify-center items-center gap-2">
        <span>Verify OTP</span> <i class="fa fa-check"></i>
      </button>
    </div>

    <!-- Step 3: Reset Password -->
    <div id="stepReset" class="flex flex-col gap-2 hidden">
      <input type="password" id="newPassword" placeholder="New Password" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 ring-orange-200 w-full" />
      <input type="password" id="confirmNewPassword" placeholder="Confirm Password" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 ring-orange-200 w-full" />
      <button id="resetPasswordBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg py-2 w-full">Reset Password</button>
    </div>

    <button id="closeModal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-xl">&times;</button>
  </div>
</div>

<script>
$(function(){
  function showMsg(selector, msg, color='red') {
    $(selector).text(msg).fadeIn().delay(2500).fadeOut();
  }

  $('#forgotBtn').click(function(){ $('#forgotModal').removeClass('hidden').addClass('flex'); });
  $('#closeModal').click(function(){ 
    $('#forgotModal').addClass('hidden').removeClass('flex'); 
    $('#stepEmail').show(); $('#stepOtp, #stepReset').hide();
    $('#fpEmail,#fpOtp,#newPassword,#confirmNewPassword').val('');
  });

  // Send OTP
  $('#sendFpOtp').click(function(e){
    e.preventDefault();
    let email = $('#fpEmail').val().trim();
    if(!email){ showMsg('#fpMsg','Enter your email'); return; }
    $(this).prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');
    $.post('forgot_send_otp.php',{email}, function(res){
      $('#sendFpOtp').prop('disabled',false).html('Send OTP <i class="fa fa-paper-plane"></i>');
      if(res.trim()=='sent'){ $('#stepEmail').hide(); $('#stepOtp').show(); showMsg('#fpMsg','OTP sent!','green'); }
      else showMsg('#fpMsg',res);
    });
  });

  // Verify OTP
  $('#verifyFpOtp').click(function(e){
    e.preventDefault();
    let otp = $('#fpOtp').val().trim();
    if(!otp){ showMsg('#fpMsg','Enter OTP'); return; }
    $(this).prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Verifying...');
    $.post('forgot_verify_otp.php',{otp}, function(res){
      $('#verifyFpOtp').prop('disabled',false).html('Verify OTP <i class="fa fa-check"></i>');
      if(res.trim()=='verified'){ $('#stepOtp').hide(); $('#stepReset').show(); showMsg('#fpMsg','OTP verified!','green'); }
      else showMsg('#fpMsg','Invalid or expired OTP');
    });
  });

  // Reset Password
  $('#resetPasswordBtn').click(function(e){
    e.preventDefault();
    let pass=$('#newPassword').val(), cpass=$('#confirmNewPassword').val();
    if(!pass||!cpass){ showMsg('#fpMsg','Enter all fields'); return; }
    if(pass!==cpass){ showMsg('#fpMsg','Passwords do not match'); return; }
    $(this).prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Resetting...');
    $.post('forgot_reset_password.php',{password:pass}, function(res){
      $('#resetPasswordBtn').prop('disabled',false).html('Reset Password');
      if(res.trim()=='success'){ showMsg('#fpMsg','Password reset successful!','green'); 
        $('#forgotModal').addClass('hidden').removeClass('flex'); $('#stepEmail').show(); $('#stepReset').hide(); $('#newPassword,#confirmNewPassword').val(''); 
      } else showMsg('#fpMsg','Failed to reset password'); 
    });
  });
});
</script>
</body>
</html>
