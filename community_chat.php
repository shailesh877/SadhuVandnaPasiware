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
 <!-- emoji library  -->
 <script src="https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js"></script>


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
    0% { transform: scale(0.85); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 20px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.85); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
.ringing-btn { animation: pulse-ring 2s infinite; }

@keyframes bounce-slow {
    0%, 100% { transform: translateY(-5%); animation-timing-function: cubic-bezier(0.8,0,1,1); }
    50% { transform: none; animation-timing-function: cubic-bezier(0,0,0.2,1); }
}
.animate-bounce-slow { animation: bounce-slow 2s infinite; }

.safe-area-bottom {
    padding-bottom: calc(2.5rem + env(safe-area-inset-bottom)) !important;
}

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
<div id="incomingCallModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 hidden backdrop-blur-md" style="display: none;">
    <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-8 text-center shadow-[0_0_50px_rgba(0,0,0,0.5)] w-[85%] max-w-sm animate-in fade-in zoom-in duration-300">
        <div class="mb-6 relative inline-block">
            <div class="absolute -inset-4 rounded-full bg-orange-500/20 animate-pulse"></div>
            <img id="incCallImg" src="" class="w-28 h-28 rounded-full border-4 border-orange-500 object-cover mx-auto relative z-10 shadow-2xl">
            <div class="absolute inset-0 rounded-full border-4 border-orange-400 animate-ping opacity-40"></div>
        </div>
        <h3 class="text-2xl font-bold text-white mb-1 tracking-tight" id="incCallName">Name</h3>
        <p class="text-orange-400 font-medium mb-8 tracking-widest text-xs uppercase" id="incCallType">Incoming Video Call...</p>
        <div class="flex justify-center gap-8">
            <button onclick="rejectIncomingCall()" class="w-16 h-16 rounded-full bg-red-500/90 text-white flex items-center justify-center hover:bg-red-600 shadow-[0_8px_20px_rgba(239,68,68,0.4)] transition-all active:scale-90">
                <i class="fa-solid fa-phone-slash fa-xl"></i>
            </button>
            <button onclick="acceptIncomingCall()" class="w-16 h-16 rounded-full bg-green-500/90 text-white flex items-center justify-center hover:bg-green-600 shadow-[0_8px_20px_rgba(34,197,94,0.4)] transition-all active:scale-90 animate-bounce-slow">
                <i class="fa-solid fa-phone fa-xl"></i>
            </button>
        </div>
    </div>
</div>


<!-- ACTIVE CALL MODAL -->
<div id="callModal" class="fixed inset-0 z-[9998] bg-gray-900 hidden flex-col" style="display: none;">
    <!-- Main Video (Remote) -->
    <div class="flex-1 relative bg-black flex items-center justify-center overflow-hidden" id="remoteVideoContainer">
        <div id="callTimer" class="absolute top-4 left-4 text-white text-lg font-mono bg-black bg-opacity-50 px-3 py-1 rounded hidden z-50">00:00</div>
        
        <!-- Video will be appended here -->
        <video id="remoteVideo" class="w-full h-full absolute inset-0 object-cover" autoplay playsinline></video>
        
        <!-- Audio Call Placeholder (Overlay) -->
        <div id="audioPlaceholder" class="hidden absolute inset-0 flex flex-col items-center justify-center z-30 bg-[#1a2235]">
            <div class="relative mb-8">
                 <img id="audioCallImg" src="<?php echo $receiver_photo; ?>" class="w-32 h-32 md:w-44 md:h-44 rounded-full border-4 border-gray-600 object-cover shadow-2xl transition-transform duration-500 hover:scale-105">
                 <div class="absolute inset-0 rounded-full border-4 border-orange-500 animate-ping opacity-20"></div>
            </div>
            <h3 class="text-white text-2xl md:text-3xl font-bold tracking-wide" id="audioCallName"><?php echo htmlspecialchars($receiver_name); ?></h3>
            <p id="audioCallStatusText" class="text-gray-400 mt-3 font-medium tracking-widest text-sm uppercase">Audio Call</p>
        </div>

        <!-- My Video (PIP) -->
        <div id="localVideoContainer" class="absolute bottom-4 right-4 w-28 h-40 md:w-36 md:h-52 rounded-2xl border-2 border-white/30 shadow-2xl z-40 overflow-hidden bg-gray-900 backdrop-blur-md">
            <video id="localVideo" class="w-full h-full object-cover" autoplay playsinline muted></video>
            <div id="localVideoOverlay" class="absolute inset-0 bg-gray-800 flex items-center justify-center hidden">
                <i class="fa-solid fa-video-slash text-white text-xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Controls -->
    <div class="h-28 bg-gray-900 flex items-center justify-center gap-6 pb-10 safe-area-bottom z-50">
        <!-- Switch Camera (Video only) -->
        <button onclick="switchCamera()" id="btnSwitchCamera" class="hidden p-4 rounded-full bg-gray-700 text-white hover:bg-gray-600 transition shadow-lg w-12 h-12 flex items-center justify-center">
            <i class="fa-solid fa-camera-rotate"></i>
        </button>

        <button onclick="toggleVideo()" id="btnVideoToggle" class="p-4 rounded-full bg-gray-700 text-white hover:bg-gray-600 transition shadow-lg w-14 h-14 flex items-center justify-center">
            <i class="fa-solid fa-video" id="iconVideo"></i>
        </button>
        
        <button onclick="endCall()" class="p-5 rounded-full bg-red-600 text-white hover:bg-red-700 shadow-[0_10px_25px_rgba(220,38,38,0.4)] transform hover:scale-105 transition-all active:scale-90 w-16 h-16 flex items-center justify-center">
            <i class="fa-solid fa-phone-slash fa-xl"></i>
        </button>

        
        <button onclick="toggleAudio()" id="btnAudioToggle" class="p-4 rounded-full bg-gray-700 text-white hover:bg-gray-600 transition shadow-lg w-14 h-14 flex items-center justify-center">
            <i class="fa-solid fa-microphone" id="iconAudio"></i>
        </button>
    </div>
</div>


<script>
const myChatProfileId = parseInt(<?php echo json_encode($my_profile_id); ?>);
const receiverChatProfileId = parseInt(<?php echo json_encode($receiver_id); ?>);
const myMemberIdForPeer = parseInt(<?php echo json_encode($my_member_id); ?>);
const PEER_ID = "sadhu_user_" + myMemberIdForPeer;
const TARGET_PEER_ID = "sadhu_user_" + receiverChatProfileId;
const chatPlatform = 'community';
const POLL = 2500;
let typingTimer = null;
let isTyping = false;

let localStream = null;
let currentCall = null;
let activeCallId = null; 
let incomingCallData = null;
let callTimerInterval = null;
let callSeconds = 0;

let lastClosedCallId = null;
let lastClosedCallTime = 0;
function markCallAsClosed(cid) {
    if(cid) { lastClosedCallId = cid; lastClosedCallTime = Date.now(); }
}
function isRecentlyClosed(cid) {
    return (cid == lastClosedCallId && (Date.now() - lastClosedCallTime < 8000));
}

// Local handler for when call is received while on this page
window.handleIncomingPeerCall = function(call) {
    console.log("Chat page handling incoming call...");
    currentCall = call;
    const metadata = call.metadata || {};

    if(metadata.caller_id && metadata.caller_id != receiverChatProfileId) {
         console.log("Call from a different user. Redirecting...");
         if(!window.isRedirectingToCall) {
             window.isRedirectingToCall = true;
             window.location.href = `message.php?receiver_id=${metadata.caller_id}&platform=${metadata.platform || 'marriage'}&type=${metadata.type || 'video'}`;
         }
         return;
    }

    call.on('close', () => {
        console.log("Call canceled by sender before accept");
        rejectIncomingCall();
    });

    
    // Auto-accept if we were waiting for this specific call
    if(window.autoAcceptCallId && metadata.call_id == window.autoAcceptCallId) {
        console.log("Auto-accepting matched incoming call");
        incomingCallData = {
            call_id: metadata.call_id,
            caller_id: metadata.caller_id || receiverChatProfileId,
            caller_name: metadata.caller_name || <?php echo json_encode($receiver_name); ?>,
            caller_photo: metadata.caller_photo || <?php echo json_encode($receiver_photo); ?>,
            type: metadata.type || 'video'
        };
        acceptIncomingCall();
        window.autoAcceptCallId = null;
        return;
    }

    // If from the person we are chatting with
    if(call.peer === TARGET_PEER_ID) {
        console.log("Call is from current chat partner");
        if(document.getElementById('incomingCallModal').classList.contains('hidden') || document.getElementById('incomingCallModal').style.display === 'none') {
            showIncomingCall({
                call_id: metadata.call_id || 0,
                caller_id: receiverChatProfileId,
                caller_name: <?php echo json_encode($receiver_name); ?>,
                caller_photo: <?php echo json_encode($receiver_photo); ?>,
                type: metadata.type || 'video'
            });
        }
    } else {
        console.log("Call is from someone else, showing global modal");
        if(typeof showGlobalIncomingCall === 'function'){
            showGlobalIncomingCall({
                call_id: metadata.call_id || 0,
                caller_id: metadata.caller_id || 0,
                caller_name: metadata.caller_name || 'Someone',
                caller_photo: metadata.caller_photo || 'images/logo.png',
                type: metadata.type || 'video',
                platform: 'community',
                peerCall: call 
            });
        }
    }
};



// Handle PeerJS on Chat Page (Rely on header.peer)
if(window.peer && window.peer.disconnected){
    try { window.peer.reconnect(); } catch(e) {}
}

// Helper: wait for peer to be ready (up to maxMs)
async function waitForPeer(maxMs = 8000) {
    const start = Date.now();
    while (Date.now() - start < maxMs) {
        if (window.peer && !window.peer.destroyed && !window.peer.disconnected) return true;
        await new Promise(r => setTimeout(r, 400));
    }
    return (window.peer && !window.peer.destroyed);
}

// INITIATE CALL
async function initiateCall(type){
    if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
        alert("Calling requires a secure connection (HTTPS)."); return;
    }

    // If peer is null/destroyed, try re-initializing and wait up to 8s
    if(!window.peer || window.peer.destroyed){
        if(typeof initPeer === 'function') initPeer();
        console.log("Waiting for PeerJS to initialize...");
        const ready = await waitForPeer(8000);
        if(!ready) {
            alert("Calling system is not ready. Please try again in a few seconds."); return;
        }
    }

    // If peer is disconnected, reconnect and wait
    if(window.peer && window.peer.disconnected){
        console.log("Peer disconnected, reconnecting before call...");
        try { window.peer.reconnect(); } catch(e) {}
        await waitForPeer(4000);
    }



    const callModal = document.getElementById('callModal');
    if(callModal){
        callModal.classList.remove('hidden');
        callModal.style.setProperty('display', 'flex', 'important');
    }

    document.getElementById('audioPlaceholder').classList.remove('hidden');
    const statusText = document.getElementById('audioCallStatusText');
    statusText.innerText = "Calling...";
    statusText.classList.add('animate-pulse');
    
    document.getElementById('callingAudio').play().catch(e => console.log("Calling audio blocked"));
    
    try {
        const constraints = {
            audio: true,
            video: type === 'video' ? { facingMode: 'user' } : false
        };
        
        if(localStream) {
            localStream.getTracks().forEach(t => t.stop());
        }

        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        const localVideo = document.getElementById('localVideo');
        if(type === 'video'){
            localVideo.srcObject = localStream;
            localVideo.classList.remove('hidden');
            document.getElementById('btnSwitchCamera').classList.remove('hidden');
        } else {
            localVideo.classList.add('hidden');
            document.getElementById('btnSwitchCamera').classList.add('hidden');
        }

        const fd = new FormData();
        fd.append('caller_id', myChatProfileId);
        fd.append('receiver_id', receiverChatProfileId);
        fd.append('type', type);
        fd.append('peer_id', PEER_ID);
        fd.append('platform', 'community');
        
        const res = await fetch('initiate_call.php', { method:'POST', body:fd });
        const text = await res.text();
        if(text.trim() === 'error'){
            alert("Call failed to initialize."); closeCallModal(); return;
        }
        activeCallId = text.trim();

        // Function to attempt PeerJS call
        const makePeerCall = () => {
            console.log("Attempting PeerJS call to:", TARGET_PEER_ID);
            currentCall = window.peer.call(TARGET_PEER_ID, localStream, {
                metadata: {
                    call_id: activeCallId,
                    caller_id: myChatProfileId,
                    caller_name: myMemberName,
                    caller_photo: myMemberPhoto,
                    type: type,
                    platform: 'community'
                }
            });
            
            if(!currentCall) {
                console.error("PeerJS could not create call object.");
                return false;
            }

            handleCall(currentCall);
            return true;
        };

        if(!makePeerCall()){
            throw new Error("Could not start calling. Please refresh.");
        }

        // Retry once after 4 seconds if not connected (Handles receiver moving to chat page)
        setTimeout(() => {
            if(activeCallId && (!currentCall || !currentCall.open)) {
                console.log("Call not open yet, retrying PeerJS call...");
                if(currentCall) currentCall.close();
                makePeerCall();
            }
        }, 4000);

        updateCallUI(type);

        // 🔥 45-Second Ringing Timeout (Caller side)
        if(window.callingTimeout) clearTimeout(window.callingTimeout);
        window.callingTimeout = setTimeout(async () => {
            if(activeCallId) {
                console.log("Call timed out after 45s of ringing.");
                await endCall(); 
                alert("User did not answer.");
            }
        }, 45000);

    } catch (error) {
        console.error("Initiate Call Error:", error);
        alert("Call Error: " + error.message);
        closeCallModal();
    }
}


function handleCall(call) {
    if(!call) return;
    currentCall = call;

    call.on('stream', (remoteStream) => {
        console.log("Received remote stream");
        if(window.callingTimeout) { clearTimeout(window.callingTimeout); window.callingTimeout = null; }
        
        const remoteVid = document.getElementById('remoteVideo');
        if(remoteVid) {
            remoteVid.srcObject = remoteStream;
            remoteVid.onloadedmetadata = () => {
                remoteVid.play().catch(e => console.log("Video play failed:", e));
            };
        }
        
        document.getElementById('callingAudio').pause();
        const placeholder = document.getElementById('audioPlaceholder');
        if(placeholder){
            const isAudioOnly = placeholder.dataset.isAudioOnly === 'true';
            if(!isAudioOnly){
                placeholder.classList.add('hidden');
            } else {
                const sText = document.getElementById('audioCallStatusText');
                if(sText) {
                    sText.innerText = "Connected";
                    sText.classList.remove('animate-pulse');
                }
            }
        }
        
        // Also ping DB that it's accepted to ensure history is correct
        if(activeCallId) updateCallStatus(activeCallId, 'accepted');
        
        startCallTimer();
    });


    const peerConn = call.peerConnection;
    if(peerConn){
        peerConn.oniceconnectionstatechange = () => {
             const state = peerConn.iceConnectionState;
             if(state === 'disconnected' || state === 'closed' || state === 'failed'){
                 endCall();
             }
        };
    }

    call.on('close', () => {
        console.log("Call event: close");
        if(activeCallId) {
             endCall(); 
        } else {
             closeCallModal();
        }
    });

    call.on('error', (err) => {
        console.error("Call Error:", err);
        endCall();
    });

    // Backup
    if(call.peerConnection){
        call.peerConnection.onconnectionstatechange = () => {
             if(call.peerConnection.connectionState === 'disconnected' || call.peerConnection.connectionState === 'failed'){
                 endCall();
             }
        };
    }
}


// INCOMING CALL HANDLING
function showIncomingCall(data){
    incomingCallData = data;
    activeCallId = data.call_id; // Track for sync
    const modal = document.getElementById('incomingCallModal');
    if(modal){
        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
        document.getElementById('incCallName').innerText = data.caller_name;
        document.getElementById('incCallImg').src = data.caller_photo;
        document.getElementById('incCallType').innerText = "Incoming " + (data.type || 'video') + " Call...";
    }

    const ring = document.getElementById('ringtoneAudio');
    if(ring) {
        ring.currentTime = 0;
        ring.play().catch(e => console.log("Autoplay blocked: ringtone hidden"));
    }
}

async function acceptIncomingCall(){
    if(!incomingCallData) return;

    const ring = document.getElementById('ringtoneAudio');
    if(ring) {
        ring.pause();
        ring.currentTime = 0;
    }

    const incModal = document.getElementById('incomingCallModal');
    if(incModal){
        incModal.classList.add('hidden');
        incModal.style.display = 'none';
    }
    
    // Show active modal
    const cModal = document.getElementById('callModal');
    if(cModal){
        cModal.classList.remove('hidden');
        cModal.style.setProperty('display', 'flex', 'important');
    }

    document.getElementById('audioPlaceholder').classList.remove('hidden');
    document.getElementById('audioCallStatusText').innerText = "Connecting...";
    
    await updateCallStatus(incomingCallData.call_id, 'accepted');
    activeCallId = incomingCallData.call_id;

    try {
        const constraints = {
            audio: true,
            video: incomingCallData.type === 'video' ? { facingMode: 'user' } : false
        };
        if(localStream) localStream.getTracks().forEach(t => t.stop());
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        const localVideo = document.getElementById('localVideo');
        if(incomingCallData.type === 'video'){
            localVideo.srcObject = localStream;
            localVideo.classList.remove('hidden');
            document.getElementById('btnSwitchCamera').classList.remove('hidden');
        } else {
            localVideo.classList.add('hidden');
            document.getElementById('btnSwitchCamera').classList.add('hidden');
        }

        if(currentCall) {
            currentCall.answer(localStream);
            handleCall(currentCall);
        }
        updateCallUI(incomingCallData.type);
    } catch (error) {
        console.error("Accept Call Error:", error);
        alert("Call Error: " + error.message);
        closeCallModal();
    }
}

async function rejectIncomingCall(){
    const cid = (incomingCallData ? incomingCallData.call_id : activeCallId);
    if(cid) markCallAsClosed(cid);

    const ring = document.getElementById('ringtoneAudio');
    if(ring) {
        ring.pause();
        ring.currentTime = 0;
    }
    // Also stop global ring just in case
    const gRing = document.getElementById('globalRingtone');
    if(gRing) { gRing.pause(); gRing.currentTime = 0; }

    const incModal = document.getElementById('incomingCallModal');
    if(incModal){
        incModal.classList.add('hidden');
        incModal.style.display = 'none';
    }

    if(incomingCallData){
        await updateCallStatus(incomingCallData.call_id, 'rejected');
        incomingCallData = null;
        activeCallId = null;
    }
    if(currentCall) currentCall.close();
}

async function updateCallStatus(id, status, duration = 0){
    const fd = new FormData();
    fd.append('call_id', id);
    fd.append('status', status);
    if(duration > 0) fd.append('duration', duration);
    await fetch('update_call_status.php', { method:'POST', body:fd });
}


// UTILS
function startCallTimer(){
    if(callTimerInterval) return; 
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
    console.log("Ending call locally...");
    if(currentCall) {
        currentCall.close();
        currentCall = null;
    }
    if(localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }

    const cid = activeCallId || (incomingCallData ? incomingCallData.call_id : null);
    if(cid) markCallAsClosed(cid);

    if(activeCallId) {
        console.log("Saving call duration:", callSeconds);
        await updateCallStatus(activeCallId, 'ended', callSeconds);
        activeCallId = null;
    }
    closeCallModal();
}

function closeCallModal(){
    const modal = document.getElementById('callModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    const rRing = document.getElementById('ringtoneAudio');
    if(rRing){ rRing.pause(); rRing.currentTime = 0; }
    
    const gRing = document.getElementById('globalRingtone');
    if(gRing){ gRing.pause(); gRing.currentTime = 0; }
    
    document.getElementById('callingAudio').pause();
    document.getElementById('callingAudio').currentTime = 0;
    stopCallTimer();
    
    if(localStream){ localStream.getTracks().forEach(t => t.stop()); localStream = null; }
    document.getElementById('localVideo').srcObject = null;
    document.getElementById('remoteVideo').srcObject = null;
    
    // Reset buttons
    const vBtn = document.getElementById('btnVideoToggle');
    const aBtn = document.getElementById('btnAudioToggle');
    if(vBtn){ vBtn.classList.remove('bg-red-500', 'hidden'); vBtn.classList.add('bg-gray-700'); }
    if(aBtn){ aBtn.classList.remove('bg-red-500'); aBtn.classList.add('bg-gray-700'); }
    document.getElementById('iconVideo').className = "fa-solid fa-video";
    document.getElementById('iconAudio').className = "fa-solid fa-microphone";
    const overlay = document.getElementById('localVideoOverlay');
    if(overlay) overlay.classList.add('hidden');

    activeCallId = null;
    incomingCallData = null;
    currentCall = null;
}

function toggleVideo(){
    if(!localStream) return;
    const videoTrack = localStream.getVideoTracks()[0];
    if(videoTrack){
        videoTrack.enabled = !videoTrack.enabled;
        const btn = document.getElementById('btnVideoToggle');
        const icon = document.getElementById('iconVideo');
        const overlay = document.getElementById('localVideoOverlay');
        
        if(videoTrack.enabled){
            btn.classList.remove('bg-red-500');
            btn.classList.add('bg-gray-700');
            icon.className = 'fa-solid fa-video';
            if(overlay) overlay.classList.add('hidden');
        } else {
            btn.classList.remove('bg-gray-700');
            btn.classList.add('bg-red-500');
            icon.className = 'fa-solid fa-video-slash';
            if(overlay) overlay.classList.remove('hidden');
        }
    }
}

function toggleAudio(){
    if(!localStream) return;
    const audioTrack = localStream.getAudioTracks()[0];
    if(audioTrack){
        audioTrack.enabled = !audioTrack.enabled;
        const btn = document.getElementById('btnAudioToggle');
        const icon = document.getElementById('iconAudio');
        
        if(audioTrack.enabled){
            btn.classList.remove('bg-red-500');
            btn.classList.add('bg-gray-700');
            icon.className = 'fa-solid fa-microphone';
        } else {
            btn.classList.remove('bg-gray-700');
            btn.classList.add('bg-red-500');
            icon.className = 'fa-solid fa-microphone-slash';
        }
    }
}

let currentFacingMode = 'user';
async function switchCamera(){
    if(!localStream || !localStream.getVideoTracks().length) return;
    currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
    
    try {
        const newStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: currentFacingMode },
            audio: true
        });
        
        const videoTrack = newStream.getVideoTracks()[0];
        const audioTrack = newStream.getAudioTracks()[0];
        
        // Update local stream
        localStream.getTracks().forEach(t => t.stop());
        localStream = newStream;
        document.getElementById('localVideo').srcObject = localStream;
        
        // Update Peer Connection
        if(currentCall && currentCall.peerConnection){
            const senders = currentCall.peerConnection.getSenders();
            const vSender = senders.find(s => s.track.kind === 'video');
            const aSender = senders.find(s => s.track.kind === 'audio');
            if(vSender) vSender.replaceTrack(videoTrack).catch(e => console.log(e));
            if(aSender) aSender.replaceTrack(audioTrack).catch(e => console.log(e));
        }
    } catch(e) { console.error("Switch Camera Error:", e); }
}

function updateCallUI(type){
    const placeholder = document.getElementById('audioPlaceholder');
    const statusText = document.getElementById('audioCallStatusText');
    const localCont = document.getElementById('localVideoContainer');
    const btnSwitch = document.getElementById('btnSwitchCamera');
    const btnVideo = document.getElementById('btnVideoToggle');
    
    placeholder.dataset.isAudioOnly = (type === 'audio');
    placeholder.classList.remove('hidden');
    
    if(type === 'audio'){
        statusText.innerText = "Audio Call";
        btnVideo.classList.add('hidden');
        btnSwitch.classList.add('hidden');
        localCont.classList.add('hidden');
    } else {
        statusText.innerText = "Calling...";
        btnVideo.classList.remove('hidden');
        btnSwitch.classList.remove('hidden');
        localCont.classList.remove('hidden');
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

        const res = await fetch(`fetch_chat.php?receiver_id=${receiverChatProfileId}&my_profile_id=${myChatProfileId}&platform=community`);
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
                body:`message_id=${id}&my_profile_id=${myChatProfileId}`
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
        body:`profile_id=${myChatProfileId}&target_profile_id=${receiverChatProfileId}&is_typing=1`
    });
}
async function stopTyping(){
    isTyping = false;
    await fetch('update_typing.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`profile_id=${myChatProfileId}&target_profile_id=${receiverChatProfileId}&is_typing=0`
    });
}

async function fetchStatus(){
    try{
        const aId = activeCallId || 0;
        const res = await fetch(`fetch_status.php?profile_id=${receiverChatProfileId}&my_profile_id=${myChatProfileId}&platform=community&active_call_id=${aId}`);
        const j = await res.json();
        
        // Online/Typing status
        statusLine.textContent = j.online ? 'Online' : (j.last_active ? 'Last seen: '+j.last_active : 'Last seen: --');
        typingIndicator.classList.toggle('hidden', !j.is_typing);
        
        // CALL HANDLING
        const incMod = document.getElementById('incomingCallModal');
        if(j.incoming_call && !isRecentlyClosed(j.incoming_call.call_id)){
            if (j.incoming_call.caller_id != receiverChatProfileId) {
                 if(!window.isRedirectingToCall) {
                      window.isRedirectingToCall = true;
                      window.location.href = `message.php?receiver_id=${j.incoming_call.caller_id}&platform=${j.incoming_call.platform || 'marriage'}&type=${j.incoming_call.type||'video'}`;
                 }
                 return;
            }
            if(incMod.classList.contains('hidden') && document.getElementById('callModal').classList.contains('hidden')){
                showIncomingCall(j.incoming_call);
            }
        } else if (j.incoming_call && isRecentlyClosed(j.incoming_call.call_id)) {
             if(incMod) { incMod.classList.add('hidden'); incMod.style.display='none'; }
        } else {
            if (incMod && !incMod.classList.contains('hidden')) {
                incMod.classList.add('hidden');
                
                const rAudio = document.getElementById('ringtoneAudio');
                if(rAudio){ rAudio.pause(); rAudio.currentTime = 0; }
                
                const gAudio = document.getElementById('globalRingtone');
                if(gAudio){ gAudio.pause(); gAudio.currentTime = 0; }
                
                incomingCallData = null;
            }
        }
        
        if(j.active_call_status){
            const s = j.active_call_status;
            if(s === 'ended' || s === 'rejected'){
                console.log("DB sync: Call " + s);
                if(!document.getElementById('callModal').classList.contains('hidden')){
                    endCall();
                } else if (!document.getElementById('incomingCallModal').classList.contains('hidden')) {
                    rejectIncomingCall(); // Just hide locally
                }
            }
            if(s === 'accepted'){
                document.getElementById('callingAudio').pause();
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
        if(e.name !== 'TypeError') console.error("Status Fetch Error:", e);
    }
}

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
        const res = await fetch(`fetch_chat.php?receiver_id=${receiverChatProfileId}&my_profile_id=${myChatProfileId}&last_id=${lastId}&platform=${chatPlatform}`);
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
    const callType = urlParams.get('type') || 'video';
    if(acceptId){
        console.log("Waiting to auto-accept community call ID: " + acceptId);
        window.autoAcceptCallId = acceptId;
        
        // Prep modal
        document.getElementById('callModal').classList.remove('hidden');
        document.getElementById('callModal').style.display = 'flex';
        document.getElementById('audioPlaceholder').classList.remove('hidden');
        document.getElementById('audioCallStatusText').innerText = "Connecting...";
        
        if(callType === 'audio'){
            document.getElementById('localVideo').classList.add('hidden');
            document.getElementById('btnVideoToggle').classList.add('hidden');
        } else {
            document.getElementById('localVideo').classList.remove('hidden');
        }
    }


    // start polling after initial load
    startPolling();
}

initialFullLoad();

async function startPolling(){
    setInterval(loadNewMessages, POLL);
    setInterval(fetchStatus, 1000); // Faster polling
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
    fd.append('receiver_id', receiverChatProfileId);
    fd.append('my_profile_id', myChatProfileId);
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
