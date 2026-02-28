<?php
include("header.php");
include("connection.php");

$logged_mobile = $_SESSION['sadhu_user_id'] ?? '';
$logged_user = $con->query("SELECT id FROM tbl_members WHERE mobile='$logged_mobile'")->fetch_assoc();
$logged_id = $logged_user['id'] ?? 0;
?>

<main class="flex-1 px-3 md:px-10 py-15 bg-white md:ml-20 mb-14 md:mb-0">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-orange-700 mb-2">Connect with Members</h1>
        <p class="text-gray-500 mb-6 pb-4">Expand your community by following more people.</p>

        <!-- Search Bar -->
        <div class="mb-8 relative max-w-md">
            <input type="text" id="searchInput" placeholder="Search by name..." 
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-orange-200 focus:outline-none focus:ring-2 focus:ring-orange-500 shadow-sm transition">
            <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-orange-400"></i>
        </div>

        <div id="profilesGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <!-- Loaded via JS -->
        </div>

        <div id="loadMore" class="h-20 flex items-center justify-center mt-10">
            <div class="w-8 h-8 border-4 border-orange-300 border-t-orange-600 rounded-full animate-spin"></div>
        </div>
    </div>
</main>

<script>
let offset = 0;
const limit = 15;
let isLoading = false;
let hasMore = true;
let searchQuery = '';

async function fetchPeople(reset = false) {
    if (isLoading || (!hasMore && !reset)) return;
    isLoading = true;
    
    if(reset) {
        offset = 0;
        hasMore = true;
        document.getElementById('profilesGrid').innerHTML = '';
    }

    document.getElementById('loadMore').classList.remove('hidden');

    try {
        const res = await fetch(`follow_action.php?action=fetch_suggestions&limit=${limit}&offset=${offset}&q=${encodeURIComponent(searchQuery)}`);
        const data = await res.json();
        const grid = document.getElementById('profilesGrid');

        if (data.suggestions.length < limit) {
            hasMore = false;
            document.getElementById('loadMore').classList.add('hidden');
        }

        data.suggestions.forEach(s => {
            const cardHTML = `
                <div id="card-${s.id}" class="bg-white rounded-2xl border border-orange-100 p-5 shadow-sm hover:shadow-md transition-all flex flex-col items-center">
                    <div class="relative mb-4">
                        <a href="user_profile?id=${s.id}">
                            ${s.profile_photo ? 
                                `<img src="${s.profile_photo}" class="w-20 h-20 rounded-full object-cover border-4 border-orange-200">` :
                                `<div class="w-20 h-20 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-3xl border-4 border-orange-200">${s.initials}</div>`
                            }
                        </a>
                        ${s.follows_me ? `<span class="absolute -bottom-1 -right-1 bg-green-500 text-white text-[9px] px-2 py-0.5 rounded-full uppercase font-bold border border-white">Follows You</span>` : ''}
                    </div>
                    
                    <a href="user_profile?id=${s.id}" class="font-bold text-gray-800 text-base text-center line-clamp-1 hover:text-orange-600 transition">${s.name}</a>
                    <p class="text-gray-400 text-xs mb-4">${s.city || 'Sadhu Vandana Member'}</p>
                    
                    <button ${s.i_follow ? 'disabled' : ''} onclick="followUser(${s.id}, this)" class="w-full text-white py-2 rounded-xl text-sm font-bold shadow-sm transition ${s.i_follow ? (s.my_status === 'accepted' ? 'bg-green-500' : 'bg-gray-400') : 'bg-orange-500 hover:bg-orange-600'}">
                        ${s.i_follow ? (s.my_status === 'accepted' ? 'Friends' : 'Requested') : (s.follows_me ? 'Accept Request' : 'Send Request')}
                    </button>
                    ${!s.i_follow ? `
                    <button onclick="cancelSuggestion(${s.id}, ${s.follows_me})" class="mt-2 w-full text-gray-400 text-[10px] uppercase font-bold hover:text-gray-600 transition">
                        ${s.follows_me ? 'Ignore' : 'Cancel'}
                    </button>` : ''}
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', cardHTML);
        });

        offset += data.suggestions.length;
        if(offset === 0 && !hasMore){
             grid.innerHTML = '<div class="col-span-full text-center py-20 text-gray-400 italic font-medium">No one new to suggest right now. Check back later!</div>';
        }

    } catch (e) {
        console.error(e);
    } finally {
        isLoading = false;
    }
}

async function followUser(id, btn) {
    const res = await fetch('follow_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=follow&user_id=${id}`
    });
    const data = await res.json();
    if(data.ok){
        if(data.status === 'requested') {
            btn.innerText = "Requested";
            btn.classList.replace('bg-orange-500', 'bg-gray-400');
            btn.disabled = true;
        } else if(data.status === 'connected') {
            btn.innerText = "Friends";
            btn.classList.replace('bg-orange-500', 'bg-green-500');
            btn.disabled = true;
        }
        
        setTimeout(() => {
            const card = document.getElementById(`card-${id}`);
            if(card) {
                card.style.opacity = "0.5";
                card.classList.add('scale-95');
            }
        }, 300);
    }
}

async function cancelSuggestion(id, followsMe) {
    if(followsMe && confirm('Stop them from following you?')){
         await fetch('follow_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove_follower&user_id=${id}`
        });
    }
    document.getElementById(`card-${id}`).remove();
    // Fetch more if needed
    if(document.getElementById('profilesGrid').children.length < 5){
         fetchPeople();
    }
}

// Initial Load
fetchPeople();

// Search Listener
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchQuery = e.target.value.trim();
    searchTimeout = setTimeout(() => {
        fetchPeople(true);
    }, 500); // Debounce API calls slightly
});

// Infinite Scroll
window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 800) {
        fetchPeople(false);
    }
});
</script>

<script>
// disable right click
document.addEventListener("contextmenu", e => e.preventDefault());

// disable drag
document.addEventListener("dragstart", e => e.preventDefault());

// disable ctrl keys
document.addEventListener("keydown", function(e){

    // Ctrl + S / U / P / C / X / A
    if (
        e.ctrlKey &&
        ['s','u','p','c','x','a'].includes(e.key.toLowerCase())
    ) {
        e.preventDefault();
    }

    // Print Screen
    if (e.key === "PrintScreen") {
        document.body.style.filter = "blur(10px)";
        setTimeout(() => {
            document.body.style.filter = "none";
        }, 2000);
    }

    // F12
    if (e.keyCode === 123) {
        e.preventDefault();
    }
});

// mobile screenshot detection (best possible)
document.addEventListener("visibilitychange", function(){
    if(document.hidden){
        document.body.style.filter = "blur(15px)";
    } else {
        document.body.style.filter = "none";
    }
});

// disable text selection
document.onselectstart = () => false;
</script>

