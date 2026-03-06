<?php
// PREMIUM message.php UI (clean + Facebook/Instagram style)
include("connection.php");
include("header.php");
//session_start();
date_default_timezone_set('Asia/Kolkata');

$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_mobile) { echo "<div class='text-center text-red-500 mt-10'>Please login.</div>"; exit; }

$member = $con->query("SELECT id FROM tbl_members WHERE mobile='".$con->real_escape_string($user_mobile)."'")->fetch_assoc();
$my_profile_id = $member['id']; 

$receiver_id = intval($_GET['receiver_id'] ?? 0);
if(!$receiver_id){ echo "<div class='text-center text-red-500 mt-10'>No chat target.</div>"; exit; }

/* ---------------------------------------------------
   CONNECTION CHECK (MUTUAL FOLLOW)
--------------------------------------------------- */
$check_conn = $con->query("
    SELECT id FROM tbl_followers 
    WHERE follower_id = $my_profile_id AND following_id = $receiver_id AND status = 'accepted'
");
$check_conn_back = $con->query("
    SELECT id FROM tbl_followers 
    WHERE follower_id = $receiver_id AND following_id = $my_profile_id AND status = 'accepted'
");

if($check_conn->num_rows == 0 || $check_conn_back->num_rows == 0){
    echo "<div class='text-center p-10 mt-10 box shadow rounded-xl bg-white max-w-md mx-auto'>
            <h2 class='text-xl font-bold text-red-500'>Friends Required</h2>
            <p class='text-gray-500 mt-2 text-sm'>You must be 'Friends' to chat in the community.</p>
            <a href='user_profile.php?id=$receiver_id' class='text-orange-600 underline mt-4 inline-block font-bold hover:text-orange-700 transition'>Go to Profile</a>
          </div>";
    exit;
}

$rc = $con->query("SELECT name as full_name, profile_photo as photo, (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(last_active) < 25) AS is_online 
                   FROM tbl_members 
                   WHERE id='$receiver_id' LIMIT 1")->fetch_assoc();

$receiver_name = $rc['full_name'] ?? 'User';
$receiver_photo = !empty($rc['photo']) ? "uploads/photo/".$rc['photo'] : "https://via.placeholder.com/150";
$is_online = (!empty($rc['is_online']) && $rc['is_online']);

// Simplified Block Status
$block_q1 = $con->query("SELECT id FROM tbl_blocked_users WHERE blocker_id='$my_profile_id' AND blocked_id='$receiver_id' AND chat_platform='community'");
$i_blocked_them = ($block_q1->num_rows > 0);

$block_q2 = $con->query("SELECT id FROM tbl_blocked_users WHERE blocker_id='$receiver_id' AND blocked_id='$my_profile_id' AND chat_platform='community'");
$they_blocked_me = ($block_q2->num_rows > 0);
?>
<!-- enoji library  -->
 <script src="https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js"></script>
 <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.20.0.js"></script>

<!-- enoji library  -->
<style>
/* TOP BAR */
.chat-topbar {
    background: #fff7f0;
    border-radius: 18px,18px,0,0;
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    /* position: sticky; */
    top: 10px;
    /* z-index: 50; */
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
}

/* CHAT BOX */
#chatBox {
    height: 64vh;
    width: 100%;
    overflow-y: auto;
    background: linear-gradient(to bottom, #fff7f0, #ffffff);
    border-radius: 18px,18px,0,0;
    padding: 16px;
    box-shadow: inset 0 0 8px rgba(0,0,0,0.05);
}
#chatBox::-webkit-scrollbar { width: 0px; }
#chatBox::-webkit-scrollbar-thumb {
    background: #f1b27b;
    border-radius: 20px;
}

/* INPUT */
.chat-input-box {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    position: relative; /* For emoji picker positioning */
}
.chat-input-field {
    flex: 1;
    border: 1px solid #f4c59c;
    border-radius: 22px;
    padding: 10px 16px;
    font-size: 15px;
    outline: none;
    background: #fff8f1;
}
.chat-input-btn {
    background: #ff7a1a;
    color: white;
    border-radius: 22px;
    padding: 10px 20px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(255,100,40,0.3);
}

#previewBox img,
#previewBox video{
    background:#000;
}

    /* ... existing css ... */
    #emojiPicker {
    position: absolute;
    bottom: 60px;
    left: 0;
    z-index: 100;
    /* hidden by default via class, but base styles here */
}

/* CALL MODAL ANIM */
@keyframes pulse-ring {
    0% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 82, 82, 0); }
    100% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(255, 82, 82, 0); }
}
.ringing-btn { animation: pulse-ring 2s infinite; }
/* AGORA VIDEO FIX */
.agora_video_player {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}
</style>

<main class="flex-1 px-2 md:px-10  bg-white md:ml-20  md:mb-0">
  <section class="w-full py-6 px-4">

    <!-- TOP BAR -->
    <div class="chat-topbar mt-10 w-full">
        <a href="view_marriage_profile.php?id=<?php echo $receiver_id; ?>">
            <img src="<?php echo $receiver_photo; ?>" 
                 class="w-12 h-12 rounded-full border-2 border-orange-500 object-cover secure-image"
                 draggable="false"
    oncontextmenu="return false;">
        </a>

        <div class="flex-1 min-w-0">
            <div class="text-[17px] font-bold text-orange-700 truncate">
              <?php echo htmlspecialchars($receiver_name); ?>
            </div>

            <div id="statusLine" class="text-xs text-gray-500">
              <?php echo $is_online ? 'Online' : 'Last seen: --'; ?>
            </div>
        </div>

        <div id="typingIndicator" 
             class="text-sm text-gray-500 hidden italic whitespace-nowrap">
          typing...
        </div>

        <!-- CALL BUTTONS -->
        <!-- CALL BUTTONS & MENU -->
        <div class="flex gap-1 ml-auto text-orange-600 items-center relative">
            <button onclick="initiateCall('audio')" class="p-3 hover:bg-orange-100 rounded-full transition" title="Audio Call">
                <i class="fa-solid fa-phone fa-lg"></i>
            </button>
            <button onclick="initiateCall('video')" class="p-3 hover:bg-orange-100 rounded-full transition" title="Video Call">
                <i class="fa-solid fa-video fa-lg"></i>
            </button>
            
            <!-- MENU -->
            <div class="relative">
                <button id="menuBtn" class="p-3 hover:bg-orange-100 rounded-full transition h-10 w-10 flex items-center justify-center">
                     <i class="fa-solid fa-ellipsis-vertical fa-lg"></i>
                </button>
                
                <div id="chatMenuDropdown" class="hidden absolute top-12 right-0 bg-white shadow-xl rounded-lg border w-40 z-50 overflow-hidden animate-fade-in-down">
                     <div onclick="toggleBlock()" class="px-4 py-3 hover:bg-red-50 text-red-600 cursor-pointer flex items-center gap-3 border-b">
                         <i class="fa-solid fa-ban"></i> 
                         <span class="font-medium text-sm"><?php echo $i_blocked_them ? 'Unblock User' : 'Block User'; ?></span>
                     </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- BLOCK ALERT -->
    <?php if($they_blocked_me): ?>
        <div class="bg-red-100 text-red-700 p-3 text-center mt-2 rounded">
            You have been blocked by this user. You cannot message or call them.
        </div>
        <style> #chatForm, .fa-phone, .fa-video { display:none !important; } </style>
    <?php elseif($i_blocked_them): ?>
        <div id="myBlockMsg" class="bg-gray-100 text-gray-700 p-3 text-center mt-2 rounded flex justify-between items-center">
            <span>You blocked this user.</span>
            <button onclick="confirmUnblock()" class="text-blue-500 underline text-sm">Unblock</button>
        </div>
        <style> #chatForm, .fa-phone, .fa-video { display:none !important; } </style>
    <?php endif; ?>

    <!-- CHAT BOX -->
    <div id="chatBox" class="mt-0 w-full flex-1">
        <div class="text-center text-gray-400">Loading...</div>
    </div>

<!-- PREVIEW -->
<div id="previewBox" class="hidden mb-18 md:mb-18 relative max-w-xs">
    <span id="removePreview"
          class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center cursor-pointer">
        ✕
    </span>

    <img id="imgPreview"
         class="hidden max-h-60 rounded-lg shadow border">

    <video id="videoPreview"
           class="hidden max-h-60 rounded-lg shadow border"
           controls></video>
</div>


    <!-- INPUT -->
        <form id="chatForm" class="chat-input-box w-full mb-6 md:mb-6" enctype="multipart/form-data">
            <button id="emojiBtn" type="button" class="text-2xl p-2 hover:bg-gray-100 rounded-full transition">😊</button>
<div id="emojiPicker" class="hidden"></div>

                <input type="text" 
                             id="messageInput" 
                             name="message" 
                             placeholder="Type a message..." 
                             class="chat-input-field w-full" 
                             autocomplete="off" />

                <input type="file" id="attachment" name="attachment" accept="image/*,video/*" style="display:none;" />
                <label for="attachment" title="Attach image/video" style="cursor:pointer; margin-right:6px; margin-top:12px;">
                    <span class="text-orange-500 justify-center items-center top-"><i class="fa-solid fa-paperclip fa-xl "></i></span>
                        <!-- <img src="images/attach_icon.png" alt="attach" style="width:26px;height:26px;object-fit:contain;"> -->
                </label>

                <button type="submit" class="chat-input-btn">
                    Send
                </button>
        </form>

  </section>
</main>

<!-- AUDIO ELEMENTS -->
<audio id="ringtoneAudio" loop src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"></audio>
<audio id="callingAudio" loop src="https://assets.mixkit.co/active_storage/sfx/1359/1359-preview.mp3"></audio>

<!-- INCOMING CALL MODAL -->
<div id="incomingCallModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-6 text-center shadow-2xl w-80 animate-bounce-slow">
        <div class="mb-4 relative inline-block">
            <img id="incCallImg" src="" class="w-24 h-24 rounded-full border-4 border-orange-500 object-cover mx-auto">
            <div class="absolute inset-0 rounded-full border-4 border-orange-400 animate-ping opacity-75"></div>
        </div>
        <h3 class="text-xl font-bold text-gray-800" id="incCallName">Name</h3>
        <p class="text-gray-500 mb-6" id="incCallType">Incoming Video Call...</p>
        <div class="flex justify-center gap-6">
            <button onclick="rejectIncomingCall()" class="w-14 h-14 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 shadow-lg transition transform hover:scale-110">
                <i class="fa-solid fa-phone-slash fa-xl"></i>
            </button>
            <button onclick="acceptIncomingCall()" class="w-14 h-14 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 shadow-lg transition transform hover:scale-110 ringing-btn">
                <i class="fa-solid fa-phone fa-xl"></i>
            </button>
        </div>
    </div>
</div>

<!-- ACTIVE CALL MODAL -->
<div id="callModal" class="fixed inset-0 z-[60] bg-gray-900 hidden flex flex-col">
    <!-- Main Video (Remote) -->
    <div class="flex-1 relative bg-black flex items-center justify-center overflow-hidden" id="remoteVideoContainer">
        <div class="text-white absolute z-10 top-1/2 text-center" id="callStatusText">Connecting...</div>
        <div id="callTimer" class="absolute top-4 left-4 text-white text-lg font-mono bg-black bg-opacity-50 px-3 py-1 rounded hidden z-50">00:00</div>
        
        <!-- Agora appends video here -->
        <div id="remoteVideo" class="w-full h-full absolute inset-0"></div>
        
        <!-- My Video (PIP) -->
        <div id="localVideoContainer" class="absolute bottom-24 right-4 w-32 h-48 bg-gray-800 rounded-xl border-2 border-white overflow-hidden shadow-xl transition-all duration-300 z-40">
             <div id="localVideo" class="w-full h-full"></div>
        </div>

        <!-- Audio Call Placeholder (Overlay) -->
        <div id="audioPlaceholder" class="hidden absolute inset-0 flex flex-col items-center justify-center z-30 bg-gray-800">
            <div class="relative mb-6">
                 <img id="audioCallImg" src="<?php echo $receiver_photo; ?>" class="w-40 h-40 rounded-full border-4 border-gray-600 object-cover shadow-2xl">
                 <div class="absolute inset-0 rounded-full border-4 border-orange-500 animate-ping opacity-30"></div>
            </div>
            <h3 class="text-white text-2xl font-bold tracking-wide"><?php echo htmlspecialchars($receiver_name); ?></h3>
            <p class="text-gray-400 mt-2 text-lg">Audio Call</p>
        </div>
    </div>
    
    <!-- Controls -->
    <div class="h-24 bg-gray-900 flex items-center justify-center gap-6 pb-4 safe-area-bottom z-50">
        <!-- Switch Camera (Video only) -->
        <button onclick="switchCamera()" id="btnSwitchCamera" class="hidden p-4 rounded-full bg-gray-700 text-white hover:bg-gray-600 transition shadow-lg w-12 h-12 flex items-center justify-center">
            <i class="fa-solid fa-camera-rotate"></i>
        </button>

        <button onclick="toggleVideo()" id="btnVideoToggle" class="p-4 rounded-full bg-gray-700 text-white hover:bg-gray-600 transition shadow-lg w-14 h-14 flex items-center justify-center">
            <i class="fa-solid fa-video" id="iconVideo"></i>
        </button>
        
        <button onclick="endCall()" class="p-5 rounded-full bg-red-600 text-white hover:bg-red-700 shadow-xl transform hover:scale-105 transition w-16 h-16 flex items-center justify-center">
            <i class="fa-solid fa-phone-slash fa-xl"></i>
        </button>
        
        <button onclick="toggleAudio()" id="btnAudioToggle" class="p-4 rounded-full bg-gray-700 text-white hover:bg-gray-600 transition shadow-lg w-14 h-14 flex items-center justify-center">
            <i class="fa-solid fa-microphone" id="iconAudio"></i>
        </button>
    </div>
</div>


<script>
const myProfile = parseInt(<?php echo json_encode($my_profile_id); ?>);
const receiverProfile = parseInt(<?php echo json_encode($receiver_id); ?>);
const chatPlatform = 'community';
const POLL = 2500;
let typingTimer = null;
let isTyping = false;

// AGORA CONFIG
// -----------------------------------------------------------
// PLEASE REPLACE THE APP ID BELOW WITH YOUR OWN FROM AGORA CONSOLE
const AGORA_APP_ID = "f73e45e4817540ceaaf195805919f08e"; 
// If your project is in "Secure Mode", you need a Temp Token. 
// Generate one in Agora Console for the channel name shown in browser console, and paste it below.
// If in "Testing Mode", leave this as null.
const AGORA_TOKEN = null; 
// -----------------------------------------------------------

let client = AgoraRTC.createClient({ mode: "rtc", codec: "h264" });
let localTracks = {
    video: null,
    audio: null
};
let remoteUsers = {};
let activeCallId = null; 
let incomingCallData = null;
let callTimerInterval = null;
let callSeconds = 0;

// Channel Name: Deterministic (min_max)
const channelName = "call_" + (myProfile < receiverProfile ? myProfile + "_" + receiverProfile : receiverProfile + "_" + myProfile);
console.log("Agora Channel Name:", channelName); // Use this name if generating a Temp Token

// EVENTS
client.on("user-published", async (user, mediaType) => {
    await client.subscribe(user, mediaType);
    console.log("subscribe success");

    if (mediaType === "video") {
        const remoteVideoTrack = user.videoTrack;
        remoteVideoTrack.play("remoteVideo");
        document.getElementById('callStatusText').innerText = "";
        document.getElementById('callingAudio').pause();
        startCallTimer();
    }

    if (mediaType === "audio") {
        const remoteAudioTrack = user.audioTrack;
        remoteAudioTrack.play();
        // If audio only, we might want to start timer here too if video never comes
        if(!user.hasVideo){
             document.getElementById('callStatusText').innerText = "";
             document.getElementById('callingAudio').pause();
             startCallTimer();
        }
    }
});

client.on("user-unpublished", (user, mediaType) => {
    console.log("unpublished " + mediaType);
    if(mediaType === 'video'){
         // maybe show avatar?
    }
});

client.on("user-left", (user) => {
    console.log("user left");
    // End call if it's 1-on-1
    endCall();
});


// INITIATE CALL
async function initiateCall(type){
    if(AGORA_APP_ID === "YOUR_AGORA_APP_ID_HERE"){
        alert("Please configure Agora App ID in the code first!");
        return;
    }

    // Check for HTTPS
    if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
        alert("Video calling requires a secure connection (HTTPS) or localhost.");
        return;
    }

    document.getElementById('callModal').classList.remove('hidden');
    document.getElementById('callStatusText').innerText = "Calling...";
    document.getElementById('callingAudio').play();
    
    // 1. Join Agora Channel
    try {
        console.log("Attempting to join Agora channel:", channelName);
        await client.join(AGORA_APP_ID, channelName, AGORA_TOKEN, myProfile);
        console.log("Joined Agora successfully as UID:", myProfile);
        
        // 2. Create Local Tracks
        try {
            console.log("Creating local tracks for type:", type);
            if(type === 'video'){
                 [localTracks.audio, localTracks.video] = await AgoraRTC.createMicrophoneAndCameraTracks();
            } else {
                 localTracks.audio = await AgoraRTC.createMicrophoneAudioTrack();
            }
            console.log("Tracks created successfully.");
        } catch(mediaErr){
            console.error("Media Error:", mediaErr);
            alert("Camera/Microphone access failed. Please ensure you have granted permissions.");
            closeCallModal();
            return;
        }
        
        // 3. Play Local
        if(localTracks.video){
            localTracks.video.play("localVideo");
            await client.publish([localTracks.audio, localTracks.video]);
        } else if(localTracks.audio){
            await client.publish([localTracks.audio]);
        }
        console.log("Tracks published successfully.");
        
        updateCallUI(type);

    } catch (error) {
        console.error("Agora Join Error:", error);
        if(error.message.includes("404")){
             alert("Agora Error: Call Room not found (404).");
        } else if(error.message.includes("token") || error.message.includes("permission")){
             alert("Agora Error: Authentication failed. If you have an App Certificate active, you must use a token.");
        } else {
             alert("Failed to join call: " + error.message);
        }
        closeCallModal();
        return;
    }
    
    // 4. Register in DB
    try {
        const fd = new FormData();
        fd.append('caller_id', myProfile);
        fd.append('receiver_id', receiverProfile);
        fd.append('type', type);
        fd.append('peer_id', "agora_v1"); // Dummy value for compatibility
        fd.append('platform', 'community'); // Fix: Pass platform
        
        const res = await fetch('initiate_call.php', { method:'POST', body:fd });
        const text = await res.text();
        if(text.trim() === 'error'){
            alert("Could not connect call.");
            closeCallModal();
        } else {
            activeCallId = text.trim();
        }
    } catch(e) { console.error(e); closeCallModal(); }
}

// INCOMING CALL HANDLING
function showIncomingCall(data){
    incomingCallData = data;
    document.getElementById('incomingCallModal').classList.remove('hidden');
    document.getElementById('incCallName').innerText = data.caller_name;
    document.getElementById('incCallImg').src = data.caller_photo;
    document.getElementById('incCallType').innerText = "Incoming " + data.type + " Call...";
    document.getElementById('ringtoneAudio').play();
}

async function acceptIncomingCall(){
    if(!incomingCallData) return;
    if(AGORA_APP_ID === "YOUR_AGORA_APP_ID_HERE"){
        alert("Please configure Agora App ID!");
        return;
    }

    document.getElementById('ringtoneAudio').pause();
    document.getElementById('incomingCallModal').classList.add('hidden');
    
    // Show active modal
    document.getElementById('callModal').classList.remove('hidden');
    document.getElementById('callStatusText').innerText = "Connecting...";
    
    // Update DB to 'accepted'
    await updateCallStatus(incomingCallData.call_id, 'accepted');
    activeCallId = incomingCallData.call_id;

    // Join Agora
    const isVideo = (incomingCallData.type === 'video');
    try {
        await client.join(AGORA_APP_ID, channelName, AGORA_TOKEN, myProfile);
        
        if(isVideo){
             try {
                [localTracks.audio, localTracks.video] = await AgoraRTC.createMicrophoneAndCameraTracks();
                localTracks.video.play("localVideo");
                await client.publish([localTracks.audio, localTracks.video]);
             } catch(err){
                alert("Media access failed: " + err);
                closeCallModal();
                return;
             }
        } else {
             try {
                localTracks.audio = await AgoraRTC.createMicrophoneAudioTrack();
                await client.publish([localTracks.audio]);
             } catch(err){
                alert("Mic access failed: " + err);
                closeCallModal();
                return;
             }
        }
        updateCallUI(incomingCallData.type);
        
    } catch (error) {
        console.error(error);
        alert("Failed to join call: " + error);
        closeCallModal();
    }
}

async function rejectIncomingCall(){
    document.getElementById('ringtoneAudio').pause();
    document.getElementById('incomingCallModal').classList.add('hidden');
    if(incomingCallData){
        await updateCallStatus(incomingCallData.call_id, 'rejected');
        incomingCallData = null;
    }
}

async function updateCallStatus(id, status){
    const fd = new FormData();
    fd.append('call_id', id);
    fd.append('status', status);
    await fetch('update_call_status.php', { method:'POST', body:fd });
}


// UTILS
function startCallTimer(){
    if(callTimerInterval) return; // FIX: Don't restart if already running
    stopCallTimer();
    callSeconds = 0;
    const timerEl = document.getElementById('callTimer');
    timerEl.classList.remove('hidden');
    timerEl.innerText = "00:00";
    
    callTimerInterval = setInterval(() => {
        callSeconds++;
        const m = Math.floor(callSeconds / 60).toString().padStart(2, '0');
        const s = (callSeconds % 60).toString().padStart(2, '0');
        timerEl.innerText = `${m}:${s}`;
    }, 1000);
}

function stopCallTimer(){
    if(callTimerInterval) clearInterval(callTimerInterval);
    callTimerInterval = null;
    callSeconds = 0;
    document.getElementById('callTimer').classList.add('hidden');
}

async function endCall(){
    // Leave Agora
    try {
        if(localTracks.audio) { localTracks.audio.close(); localTracks.audio = null; }
        if(localTracks.video) { localTracks.video.close(); localTracks.video = null; }
        await client.leave();
    } catch(e) { console.error(e); }

    if(activeCallId) await updateCallStatus(activeCallId, 'ended');
    closeCallModal();
}

function closeCallModal(){
    stopCallTimer();
    document.getElementById('callModal').classList.add('hidden');
    
    document.getElementById('localVideo').innerHTML = "";
    document.getElementById('remoteVideo').innerHTML = "";
    
    const ring = document.getElementById('ringtoneAudio');
    if (ring) { ring.pause(); ring.currentTime = 0; }
    const callAudio = document.getElementById('callingAudio');
    if (callAudio) { callAudio.pause(); callAudio.currentTime = 0; }
    
    activeCallId = null;
    incomingCallData = null;
}

function toggleVideo(){
    if(localTracks.video){
        const isEnabled = localTracks.video.enabled; // current state
        localTracks.video.setEnabled(!isEnabled);
        
        const btn = document.getElementById('btnVideoToggle');
        const icon = document.getElementById('iconVideo');
        
        if(!isEnabled){ // NOW enabled
             btn.classList.remove('bg-red-500');
             icon.className = 'fa-solid fa-video';
        } else {
             btn.classList.add('bg-red-500');
             icon.className = 'fa-solid fa-video-slash';
        }
    }
}

function toggleAudio(){
    if(localTracks.audio){
        const isEnabled = localTracks.audio.enabled;
        localTracks.audio.setEnabled(!isEnabled);
        
        const btn = document.getElementById('btnAudioToggle');
        const icon = document.getElementById('iconAudio');
        
        if(!isEnabled){ 
             btn.classList.remove('bg-red-500');
             icon.className = 'fa-solid fa-microphone';
        } else {
             btn.classList.add('bg-red-500');
             icon.className = 'fa-solid fa-microphone-slash';
        }
    }
}

let currentCamIndex = 0;
async function switchCamera(){
    if(!localTracks.video) return;
    try {
        const cams = await AgoraRTC.getCameras();
        if(cams.length < 2) {
            // alert("No other camera found");
            return;
        }
        currentCamIndex = (currentCamIndex + 1) % cams.length;
        await localTracks.video.setDevice(cams[currentCamIndex].deviceId);
    } catch(e){
        console.error(e);
    }
}

function updateCallUI(type){
    const audioPlace = document.getElementById('audioPlaceholder');
    const remoteVid = document.getElementById('remoteVideo');
    const localCont = document.getElementById('localVideoContainer');
    const btnSwitch = document.getElementById('btnSwitchCamera');
    
    if(type === 'audio'){
        audioPlace.classList.remove('hidden');
        remoteVid.classList.add('invisible'); 
        localCont.classList.add('hidden');
        document.getElementById('btnVideoToggle').classList.add('opacity-50', 'pointer-events-none');
        btnSwitch.classList.add('hidden');
    } else {
        audioPlace.classList.add('hidden');
        remoteVid.classList.remove('invisible');
        localCont.classList.remove('hidden');
        document.getElementById('btnVideoToggle').classList.remove('opacity-50', 'pointer-events-none');
        btnSwitch.classList.remove('hidden');
    }
}


async function loadChat(){
    try{
        // if any video inside chat is currently playing, skip refresh to avoid interrupting playback
        const box = document.getElementById('chatBox');
        const videos = box.querySelectorAll('video');
        for(const v of videos){
            if(!v.paused && !v.ended && v.readyState > 2){
                // skip this refresh
                return;
            }
        }

        const res = await fetch(`fetch_chat.php?receiver_id=${receiverProfile}&my_profile_id=${myProfile}`);
        const html = await res.text();
        const bottom = (box.scrollTop + box.clientHeight + 50) >= box.scrollHeight;
        // only update DOM when content actually changed to preserve media state
        if(box.innerHTML !== html){
            box.innerHTML = html;
            if(bottom) box.scrollTop = box.scrollHeight;
        }
        fetchStatus();
    }catch(e){ console.error(e); }
}

// SEND handled later (with preview support)

// DELETE
chatBox.addEventListener('click', async e => {
    if(e.target.classList.contains('delete-btn')){
        if(!confirm('Delete this message?')) return;
        const btn = e.target;
        const id = btn.dataset.id;
        
        try {
            const res = await fetch('delete_chat.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:`message_id=${id}&my_profile_id=${myProfile}`
            });
            const text = await res.text();
            if(text.trim() === 'ok'){
                // remove from DOM
                const msgDiv = btn.closest('[data-msg-id]');
                if(msgDiv) msgDiv.remove();
            } else {
                alert('Could not delete message');
            }
        } catch(err){
            console.error(err);
        }
    }
});

// TYPING
messageInput.addEventListener('input', () => {
    if(!isTyping) startTyping();
    clearTimeout(typingTimer);
    typingTimer = setTimeout(stopTyping, 1500);
});

async function startTyping(){
    isTyping = true;
    await fetch('update_typing.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`profile_id=${myProfile}&target_profile_id=${receiverProfile}&is_typing=1`
    });
}
async function stopTyping(){
    isTyping = false;
    await fetch('update_typing.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`profile_id=${myProfile}&target_profile_id=${receiverProfile}&is_typing=0`
    });
}

async function fetchStatus(){
    try{
        const res = await fetch(`fetch_status.php?profile_id=${receiverProfile}&my_profile_id=${myProfile}&platform=community`);
        const j = await res.json();
        
        // Online/Typing status
        statusLine.textContent = j.online ? 'Online' : (j.last_active ? 'Last seen: '+j.last_active : 'Last seen: --');
        typingIndicator.classList.toggle('hidden', !j.is_typing);
        
        // CALL HANDLING
        const incMod = document.getElementById('incomingCallModal');
        if(j.incoming_call){
            // If already in call or ringing, ignore or handle multi-call (ignoring for now)
            if(incMod.classList.contains('hidden') && document.getElementById('callModal').classList.contains('hidden')){
                showIncomingCall(j.incoming_call);
            }
        } else {
            if (incMod && !incMod.classList.contains('hidden')) {
                incMod.classList.add('hidden');
                const ring = document.getElementById('ringtoneAudio');
                if (ring) { ring.pause(); ring.currentTime = 0; }
                incomingCallData = null;
            }
        }
        
        if(j.call_update){
            const s = j.call_update.status;
            if(activeCallId == j.call_update.call_id){
                 if(s === 'rejected' || s === 'ended'){
                     // alert(s === 'rejected' ? 'Call Rejected' : 'Call Ended');
                     endCall();
                 }
                 if(s === 'accepted'){
                     document.getElementById('callingAudio').pause();
                 }
            }
        }

        // Update SEEN status for my messages
        if(j.max_seen_id > 0){
            const myMsgs = document.querySelectorAll('.sent-msg');
            myMsgs.forEach(m => {
                const mid = parseInt(m.getAttribute('data-msg-id') || 0);
                if(mid <= j.max_seen_id){
                    const span = m.querySelector('.msg-seen-status');
                    if(span && span.innerText.trim() === ""){
                        span.innerText = "Seen";
                    }
                }
            });
        }
    } catch(e) {
        console.error("Status Fetch Error:", e);
    }
    
    // CALL STATUS CHECKS (Merged into fetchStatus)
    // response will contain 'incoming_call' or 'call_update'
}

// Separate function to handle the JSON data from fetchStatus()
// We need to override fetchStatus slightly or just handle logic in the existing loop.
// Since I can't easily rewrite the internal body of fetchStatus in this specific tool call cleanly without large replacement, 
// I will create a new function `handleCallStatus(data)` and call it from the updated fetchStatus or just rely on the existing fetchStatus loop if I edit it.

// Let's EDIT the `fetchStatus` function body to handle the call data.

// load only new messages (append)
async function loadNewMessages(){
    try{
        const box = document.getElementById('chatBox');
        // skip if a video is playing
        const videos = box.querySelectorAll('video');
        for(const v of videos){
            if(!v.paused && !v.ended && v.readyState > 2) return;
        }

        const lastId = window.lastMessageId || 0;
        const res = await fetch(`fetch_chat.php?receiver_id=${receiverProfile}&my_profile_id=${myProfile}&last_id=${lastId}&platform=${chatPlatform}`);
        const ct = res.headers.get('content-type') || '';
        if(ct.includes('application/json')){
            const arr = await res.json();
            if(Array.isArray(arr) && arr.length){
                const bottom = (box.scrollTop + box.clientHeight + 50) >= box.scrollHeight;
                for(const m of arr){
                    box.insertAdjacentHTML('beforeend', m.html);
                    window.lastMessageId = Math.max(window.lastMessageId||0, m.id);
                }
                if(bottom) box.scrollTop = box.scrollHeight;
            }
        } else {
            // fallback: do full refresh
            await loadChat();
            // recompute last id
            const msgs = box.querySelectorAll('[data-msg-id]');
            let maxId = 0; msgs.forEach(m => { const id = parseInt(m.getAttribute('data-msg-id')||0); if(id>maxId) maxId=id; });
            window.lastMessageId = maxId;
        }
    }catch(e){ console.error(e); }
}

// initial full load
async function initialFullLoad(){
    await loadChat();
    // compute lastMessageId
    const box = document.getElementById('chatBox');
    const msgs = box.querySelectorAll('[data-msg-id]');
    let maxId = 0;
    msgs.forEach(m => { const id = parseInt(m.getAttribute('data-msg-id')||0); if(id>maxId) maxId=id; });
    window.lastMessageId = maxId;
    
    // Auto-accept call if coming from global modal
    const urlParams = new URLSearchParams(window.location.search);
    const acceptId = urlParams.get('accept_call_id');
    if(acceptId){
        console.log("Auto-accepting call ID: " + acceptId);
        // Wait for Peer to be ready
        let retryCount = 0;
        const checkPeer = setInterval(() => {
            if(myPeerId || retryCount > 10){
                clearInterval(checkPeer);
                if(myPeerId){
                    // Mock incomingCallData and accept
                    incomingCallData = {
                        call_id: acceptId,
                        caller_name: "Incoming...", // fetchStatus will update it
                        type: 'video', // default to video, fetchStatus will refine
                        peer_id: '' // fetchStatus will provide this via polling
                    };
                    // We need to wait for fetchStatus to get the peer_id before we can accept media
                    // Or we just wait for the first fetchStatus to trigger the modal/accept
                }
            }
            retryCount++;
        }, 500);
    }

    // start polling after initial load
    startPolling();
}

initialFullLoad();

async function startPolling(){
    setInterval(loadNewMessages, POLL);
    setInterval(fetchStatus, 2000);
    setInterval(() => fetch('update_online.php',{method:'POST'}), 10000);
}

const attachmentInput = document.getElementById('attachment');
const previewBox = document.getElementById('previewBox');
const imgPreview = document.getElementById('imgPreview');
const videoPreview = document.getElementById('videoPreview');
const removePreview = document.getElementById('removePreview');
let currentPreviewUrl = null;

function clearPreview(){
    if(currentPreviewUrl){
        try{ URL.revokeObjectURL(currentPreviewUrl); }catch(e){}
        currentPreviewUrl = null;
    }
    attachmentInput.value = '';
    imgPreview.src = '';
    videoPreview.src = '';
    previewBox.classList.add('hidden');
}

attachmentInput.addEventListener('change', function () {

    const file = this.files[0];
    if (!file) return;

    previewBox.classList.remove('hidden');
    imgPreview.classList.add('hidden');
    videoPreview.classList.add('hidden');

    const url = URL.createObjectURL(file);
    currentPreviewUrl = url;

    if (file.type.startsWith('image/')) {
        imgPreview.src = url;
        imgPreview.classList.remove('hidden');
    }
    else if (file.type.startsWith('video/')) {
        videoPreview.src = url;
        videoPreview.classList.remove('hidden');
    }
});

// remove preview
removePreview.addEventListener('click', () => {
    clearPreview();
});

// ensure preview cleared after send
chatForm.addEventListener('submit', async e => {
    e.preventDefault();
    const txt = messageInput.value.trim();
    const file = attachmentInput.files[0];

    if(!txt && !file) return;

    const fd = new FormData();
    fd.append('message', txt);
    fd.append('receiver_id', receiverProfile);
    fd.append('my_profile_id', myProfile);
    if(file) fd.append('attachment', file);

    try{
        const res = await fetch('send_chat.php', { method: 'POST', body: fd });
        // server returns plain text 'ok' on success
        const text = await res.text();
        if(res.ok && text.trim() === 'ok'){
            messageInput.value = '';
            stopTyping();
            clearPreview();
            // append new messages (server will return any messages after lastMessageId)
            await loadNewMessages();
        } else {
            // still clear preview locally and fallback to append
            messageInput.value = '';
            stopTyping();
            clearPreview();
            await loadNewMessages();
        }
    }catch(err){
        console.error(err);
    }
});

// emoji js 
// Emoji button toggle
const emojiBtn = document.getElementById('emojiBtn');
const emojiPicker = document.getElementById('emojiPicker');
let pickerInitialized = false;

emojiBtn.addEventListener('click', async (e) => {
    e.stopPropagation(); // prevent document click
    
    // Lazy load picker
    if (!pickerInitialized) {
        const picker = new EmojiMart.Picker({
            onEmojiSelect: e => {
                const input = document.getElementById('messageInput');
                const start = input.selectionStart;
                const end = input.selectionEnd;
                const text = input.value;
                const emoji = e.native;
                
                input.value = text.slice(0, start) + emoji + text.slice(end);
                input.focus();
                input.selectionStart = input.selectionEnd = start + emoji.length;
            },
            theme: 'light', // explicitly set theme just in case
            previewPosition: 'none' // save space
        });
        emojiPicker.appendChild(picker);
        pickerInitialized = true;
    }

    emojiPicker.classList.toggle('hidden');
});

// Close picker when clicking outside
document.addEventListener('click', (e) => {
    if (!emojiPicker.contains(e.target) && !emojiBtn.contains(e.target)) {
        emojiPicker.classList.add('hidden');
    }
    
    // Close chat menu
    const menuBtn = document.getElementById('menuBtn');
    const menu = document.getElementById('chatMenuDropdown');
    if(menuBtn && menu){
        if(menuBtn.contains(e.target)){
            menu.classList.toggle('hidden');
        } else if(!menu.contains(e.target)){
            menu.classList.add('hidden');
        }
    }
});


</script>
<script>
// Block/Unblock Logic
async function toggleBlock(){
    // If I blocked them, ask to unblock
    if(<?php echo $i_blocked_them ? 'true' : 'false'; ?>){
       confirmUnblock();
       return;
    }
    
    if(<?php echo $they_blocked_me ? 'true' : 'false'; ?>) {
        alert("You cannot perform this action."); 
        return; 
    }
    
    if(!confirm("Are you sure you want to BLOCK this user? You will not be able to message or call them.")) return;
    
    const fd = new FormData();
    fd.append('my_id', <?php echo $my_profile_id; ?>);
    fd.append('target_id', <?php echo $receiver_id; ?>);
    fd.append('platform', 'community');
    fd.append('action', 'block');
    
    try{
        const res = await fetch('block_user.php', { method:'POST', body:fd });
        const text = await res.text();
        if(text.trim() === 'blocked'){
            location.reload();
        } else {
            alert("Error blocking user.");
        }
    }catch(e){console.error(e);}
}

async function confirmUnblock(){
    if(!confirm("Unblock this user?")) return;
    
    const fd = new FormData();
    fd.append('my_id', <?php echo $my_profile_id; ?>);
    fd.append('target_id', <?php echo $receiver_id; ?>);
    fd.append('platform', 'community');
    fd.append('action', 'unblock');
    
    try{
        const res = await fetch('block_user.php', { method:'POST', body:fd });
        const text = await res.text();
        if(text.trim() === 'unblocked'){
            location.reload();
        } else {
            alert("Error unblocking user.");
        }
    }catch(e){console.error(e);}
}
</script>
