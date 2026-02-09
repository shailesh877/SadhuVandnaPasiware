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
</style>

<main class="flex-1 px-2 md:px-10  bg-white md:ml-20  md:mb-0">
  <section class="w-full py-6 px-4">

    <!-- TOP BAR -->
    <div class="chat-topbar mt-10 w-full">
        <a href="view_marriage_profile.php?id=<?php echo $receiver_id; ?>">
            <img src="<?php echo $receiver_photo; ?>" 
                 class="w-12 h-12 rounded-full border-2 border-orange-500 object-cover">
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

    <!-- INPUT -->
    <form id="chatForm" class="chat-input-box w-full">
        <input type="text" 
               id="messageInput" 
               name="message" 
               placeholder="Type a message..." 
               class="chat-input-field w-full" 
               autocomplete="off" />
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
        const res = await fetch(`fetch_chat.php?receiver_id=${receiverProfile}&my_profile_id=${myProfile}`);
        const html = await res.text();
        const box = document.getElementById('chatBox');
        const bottom = (box.scrollTop + box.clientHeight + 50) >= box.scrollHeight;
        box.innerHTML = html;
        if(bottom) box.scrollTop = box.scrollHeight;
        fetchStatus();
    }catch(e){ console.error(e); }
}

// SEND
chatForm.addEventListener('submit', async e => {
    e.preventDefault();
    const txt = messageInput.value.trim();
    if(!txt) return;
    await fetch('send_chat.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`message=${encodeURIComponent(txt)}&receiver_id=${receiverProfile}&my_profile_id=${myProfile}`
    });
    messageInput.value = '';
    stopTyping();
    loadChat();
});

// DELETE
chatBox.addEventListener('click', async e => {
    if(e.target.classList.contains('delete-btn')){
        if(!confirm('Delete this message?')) return;
        const id = e.target.dataset.id;
        await fetch('delete_chat.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`message_id=${id}&my_profile_id=${myProfile}`
        });
        loadChat();
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
        statusLine.textContent = j.online ? 'Online' : (j.last_active ? 'Last seen: '+j.last_active : 'Last seen: --');
        typingIndicator.classList.toggle('hidden', !j.is_typing);
    }catch(e){}
}

setInterval(loadChat, POLL);
setInterval(fetchStatus, 2000);
setInterval(() => fetch('update_online.php',{method:'POST'}), 10000);

loadChat();
</script>