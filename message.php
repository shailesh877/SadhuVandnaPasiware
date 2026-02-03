<?php
// PREMIUM message.php UI (clean + Facebook/Instagram style)
include("connection.php");
include("header.php");
//session_start();
date_default_timezone_set('Asia/Kolkata');

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email) { echo "<div class='text-center text-red-500 mt-10'>Please login.</div>"; exit; }

$member = $con->query("SELECT id FROM tbl_members WHERE email='".$con->real_escape_string($user_email)."'")->fetch_assoc();
$member_id = $member['id'];
$me = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$member_id' LIMIT 1")->fetch_assoc();
$my_profile_id = $me['id'] ?? 0;
if(!$my_profile_id){ echo "<div class='text-center text-red-500 mt-10'>Create marriage profile first.</div>"; exit; }

$receiver_id = intval($_GET['receiver_id'] ?? 0);
if(!$receiver_id){ echo "<div class='text-center text-red-500 mt-10'>No chat target.</div>"; exit; }

/* ---------------------------------------------------
   WALLET CONNECTION CHECK
--------------------------------------------------- */
$check = $con->query("
    SELECT id FROM tbl_wallet
    WHERE 
        (
            sender_id = $my_profile_id 
            AND receiver_id = $receiver_id
        )
        OR
        (
            sender_id = $receiver_id
            AND receiver_id = $my_profile_id
        )
        AND status = 'success'
       
    LIMIT 1
");

if($check->num_rows == 0){
    // ❌ No active connection → redirect to payment
    echo "<script>
        alert(' payment is required to chat. Redirecting to payment page.');
        window.location.href = 'payment.php?sender=$my_profile_id&receiver=$receiver_id';
    </script>";
    exit;
}


$rc = $con->query("SELECT full_name, photo, (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(last_active) < 25) AS is_online 
                   FROM tbl_marriage_profiles mp 
                   JOIN tbl_members m ON mp.user_id=m.id 
                   WHERE mp.id='$receiver_id' LIMIT 1")->fetch_assoc();

$receiver_name = $rc['full_name'] ?? 'User';
$receiver_photo = !empty($rc['photo']) ? "uploads/photo/".$rc['photo'] : "https://via.placeholder.com/150";
$is_online = (!empty($rc['is_online']) && $rc['is_online']);
?>
<!-- enoji library  -->
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

#emojiPicker {
    position: absolute;
    bottom: 60px;
    left: 0;
    z-index: 100;
    /* hidden by default via class, but base styles here */
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
    </div>

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


<script>
const myProfile = <?php echo json_encode($my_profile_id); ?>;
const receiverProfile = <?php echo json_encode($receiver_id); ?>;
const POLL = 2500;
let typingTimer = null;
let isTyping = false;

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
        const res = await fetch(`fetch_status.php?profile_id=${receiverProfile}&my_profile_id=${myProfile}`);
        const j = await res.json();
        
        // Online/Typing status
        statusLine.textContent = j.online ? 'Online' : (j.last_active ? 'Last seen: '+j.last_active : 'Last seen: --');
        typingIndicator.classList.toggle('hidden', !j.is_typing);

        // Update SEEN status for my messages
        if(j.max_seen_id > 0){
            // find all my sent messages that are NOT yet marked seen (optimization: or just all)
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
    }catch(e){}
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
        const res = await fetch(`fetch_chat.php?receiver_id=${receiverProfile}&my_profile_id=${myProfile}&last_id=${lastId}`);
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
});


</script>