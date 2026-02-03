<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Sadhu Vandana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Roboto', sans-serif; }
    .gradient-bg { background: linear-gradient(110deg, #ffedd5 10%, #fffbe1 90%); }
    .glassmin { background: rgba(255,255,255,0.95); box-shadow: 0 6px 40px rgba(251,146,60,.15); }
    .fade-in { animation: fadeIn .4s ease; }
    @keyframes fadeIn { from{opacity:0; transform:translateY(-4px)} to{opacity:1; transform:translateY(0)} }
  </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center px-2">

<div class="glassmin rounded-xl border border-orange-200 w-full max-w-sm p-5">

<form id="registerForm"
      action="registration_code.php"
      method="post"
      enctype="multipart/form-data"
      class="flex flex-col gap-3">

<h2 class="text-lg font-bold text-orange-800 flex items-center gap-2 justify-center mb-1">
  <i class="fa-solid fa-user-plus"></i> Create New Account
</h2>

<!-- PROFILE PHOTO -->
<div class="flex flex-col items-center gap-2">
  <div class="w-24 h-24 rounded-full border-2 border-dashed border-orange-300 flex items-center justify-center overflow-hidden">
    <img id="photoPreview" class="hidden w-full h-full object-cover">
    <i id="photoIcon" class="fa-solid fa-camera text-orange-400 text-2xl"></i>
  </div>
  <label class="cursor-pointer text-xs text-orange-700 font-semibold">
    Upload Profile Photo *
    <input type="file" name="photo" accept="image/*" required hidden id="photoInput">
  </label>
</div>

<!-- NAME -->
<input required type="text" name="name" placeholder="Full Name"
class="border rounded-lg px-3 py-2 focus:ring-2 ring-orange-200"/>

<!-- EMAIL + OTP -->
<div class="flex gap-2">
  <input required type="email" name="email" id="email" placeholder="Email Address"
  class="border rounded-lg px-3 py-2 flex-1 focus:ring-2 ring-orange-200"/>
  <button type="button" id="sendOtpBtn"
  class="bg-orange-500 text-white px-3 py-2 rounded-lg text-xs font-bold">
  Send OTP
  </button>
</div>

<!-- OTP -->
<div id="otpSection" class="hidden flex gap-2 fade-in">
  <input type="text" id="otp" placeholder="Enter OTP"
  class="border rounded-lg px-3 py-2 flex-1 focus:ring-2 ring-orange-200"/>
  <button type="button" id="verifyOtpBtn"
  class="bg-green-500 text-white px-3 py-2 rounded-lg text-xs font-bold">
  Verify
  </button>
</div>

<div id="messageBox" class="hidden text-sm text-center font-semibold py-2 rounded-lg"></div>

<!-- MORE FIELDS -->
<div id="moreFields" class="hidden flex flex-col gap-3">

<input required type="text" name="phone" placeholder="Mobile Number"
class="border rounded-lg px-3 py-2 focus:ring-2 ring-orange-200"/>

<input required type="text" name="city" placeholder="City"
class="border rounded-lg px-3 py-2 focus:ring-2 ring-orange-200"/>

<input required type="text" name="cast" placeholder="Community / Cast"
class="border rounded-lg px-3 py-2 focus:ring-2 ring-orange-200"/>

<!-- DOB with placeholder trick -->
<div class="relative">
  <input required type="text" id="dobText" placeholder="Date of Birth"
  class="border rounded-lg px-3 py-2 w-full focus:ring-2 ring-orange-200"
  onfocus="this.type='date'; this.showPicker && this.showPicker();"
  onblur="if(!this.value)this.type='text';"
  name="dob">
</div>

<select required name="gender"
class="border rounded-lg px-3 py-2 focus:ring-2 ring-orange-200">
<option value="">Select Gender</option>
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>

<input required type="password" name="password" placeholder="Create Password"
class="border rounded-lg px-3 py-2 focus:ring-2 ring-orange-200"/>

<input required type="password" name="confirm_password" placeholder="Confirm Password"
class="border rounded-lg px-3 py-2 focus:ring-2 ring-orange-200"/>

<button type="submit"
class="bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg py-2 mt-2">
Register
</button>

</div>

<p class="text-xs text-center text-gray-600 mt-2">
Already have an account?
<a href="login" class="text-orange-700 font-bold underline">Login</a>
</p>

</form>
</div>

<script>
let otpVerified=false;

// Photo preview
$('#photoInput').on('change',function(){
  const file=this.files[0];
  if(file){
    $('#photoPreview').attr('src',URL.createObjectURL(file)).removeClass('hidden');
    $('#photoIcon').addClass('hidden');
  }
});

// Message
function showMessage(text,type='info'){
  const box=$('#messageBox');
  box.removeClass().addClass('text-sm text-center font-semibold py-2 rounded-lg fade-in');
  if(type==='success')box.addClass('bg-green-100 text-green-700');
  else if(type==='error')box.addClass('bg-red-100 text-red-700');
  else box.addClass('bg-orange-100 text-orange-700');
  box.text(text).removeClass('hidden');
}

// OTP flow (same as your logic)
$('#sendOtpBtn').click(()=>{
  let email=$('#email').val().trim();
  if(!email)return showMessage('Enter email first','error');
  $.post('send_otp.php',{email},res=>{
    if(res.trim()==='sent'){
      showMessage('OTP sent to email','success');
      $('#otpSection').removeClass('hidden');
      $('#sendOtpBtn').hide();
    }else showMessage('OTP failed','error');
  });
});

$('#verifyOtpBtn').click(()=>{
  let otp=$('#otp').val().trim();
  if(!otp)return showMessage('Enter OTP','error');
  $.post('verify_otp.php',{otp},res=>{
    if(res.trim()==='verified'){
      otpVerified=true;
      showMessage('OTP Verified','success');
      $('#otpSection').hide();
      $('#moreFields').removeClass('hidden');
    }else showMessage('Invalid OTP','error');
  });
});

$('#registerForm').submit(e=>{
  if(!otpVerified){
    e.preventDefault();
    showMessage('Verify email first','error');
  }
});
</script>

</body>
</html>