<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sadhu Vandana - Add News</title>
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }


        aside::-webkit-scrollbar {
            display: none;

        }

        aside {
            -ms-overflow-style: none;

            scrollbar-width: none;
            /* Firefox */
        }


        .mob-scroll::-webkit-scrollbar {
            display: none;
        }

        .mob-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
    </style>
</head>

<body class="bg-white min-h-screen flex flex-col">

    <!-- Top Navbar  start -->

    <nav
        class="fixed top-0 left-0 w-full z-50 flex items-center justify-between px-3 py-1 bg-white shadow-sm border-b border-orange-300">
        <div class="flex items-center">
            <img src="WhatsApp_Image_2025-11-01_at_13.21.10_f22aa80c-removebg-preview.png" class="w-1/5" alt="">
        </div>
        <div class="flex items-center gap-4 relative">
            <!-- Message Icon with dropdown -->
            <div class="relative">
                <button id="messageBtn" class="focus:outline-none relative">
                    <i class="fa-regular fa-message text-orange-500 text-lg"></i>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-orange-600 rounded-full"></span>
                </button>
                <!-- Message dropdown -->
                <div id="msgDropdown"
                    class="hidden absolute -right-15 mt-2 w-80 max-w-2xl bg-white border border-orange-200 rounded-lg shadow-2xl z-50">
                    <div class="px-4 py-2 font-bold text-orange-700 border-b border-orange-100">Messages</div>
                    <div class="max-h-72 overflow-y-auto">
                        <!-- Message card 1 -->
                        <button onclick="openChat('Rohit Sharma')"
                            class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-orange-50 transition">
                            <img src="https://randomuser.me/api/portraits/men/45.jpg"
                                class="w-10 h-10 rounded-full border-2 border-orange-200 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-orange-700 truncate">Rohit Sharma</div>
                                <div class="text-xs text-gray-600 truncate">Hi! Can we chat about marriage profile?
                                </div>
                            </div>
                            <div class="text-xs text-gray-400 flex flex-col items-end ml-2">
                                <span>2m ago</span>
                                <span class="inline-block w-2 h-2 rounded-full bg-orange-500 mt-1"></span>
                            </div>
                        </button>
                        <!-- Message card 2 -->
                        <button onclick="openChat('Shilpi Verma')"
                            class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-orange-50">
                            <img src="https://randomuser.me/api/portraits/women/47.jpg"
                                class="w-10 h-10 rounded-full border-2 border-orange-200 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-orange-700 truncate">Shilpi Verma</div>
                                <div class="text-xs text-gray-600 truncate">Thank you for accepting my request!</div>
                            </div>
                            <div class="text-xs text-gray-400 ml-2">12:30pm</div>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Notification & profile code as already is in your design -->
            <a href="notification.html" class="relative">
                <i class="fa-regular fa-bell text-orange-500 text-lg"></i>
                <span class="absolute top-0 right-0 w-2 h-2 bg-orange-600 rounded-full"></span>
            </a>
            <div class="relative">
                <button id="profileBtn" class="focus:outline-none">
                    <img src="https://images.unsplash.com/photo-1526779259212-939e64788e3c?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8ZnJlZSUyMGltYWdlc3xlbnwwfHwwfHx8MA%3D%3D&auto=format&fit=crop&q=60&w=600"
                        alt="Profile" class="w-8 h-8 rounded-full border-2 border-orange-400 ml-2" />
                </button>
                <!-- Dropdown menu -->
                <div id="profileDropdown"
                    class="hidden absolute right-0 mt-2 w-60 bg-white border border-orange-200 rounded-lg shadow-lg z-50">
                    <a href="change-password.html" class="block px-4 py-2 text-orange-700 hover:bg-orange-100">Change
                        Password</a>
                    <a href="logout.html" class="block px-4 py-2 text-orange-700 hover:bg-orange-100">Logout</a>
                    <select class="px-4 py-2 text-orange-700 w-full hover:bg-orange-100 transition">
                        <option value="en">English</option>
                        <option value="hi">Hindi</option>
                        <option value="gu">Gujarati</option>
                    </select>
                </div>
            </div>
        </div>
    </nav>

    <!-- Chat Modal  -->
    <div id="chatModal" class="fixed inset-0 bg-black/30 flex items-end md:items-center justify-center z-[9999] hidden">
        <div
            class="bg-white w-full h-full md:max-w-md md:w-full md:mx-0 md:rounded-xl md:shadow-2xl md:p-4 md:border md:border-orange-200 flex flex-col gap-2 p-0 border-0 rounded-none shadow-none">
            <!-- Header with user info -->
            <div class="flex items-center gap-3 mb-2 border-b border-orange-100 pb-2 px-4 pt-4 md:px-0 md:pt-0">
                <img id="chatAvatar" src="" class="w-11 h-11 rounded-full border-2 border-orange-300" />
                <div class="flex flex-col">
                    <div id="chatName" class="font-bold text-orange-700"></div>
                    <div class="text-xs text-gray-500">Online</div>
                </div>
                <button onclick="closeChat()" class="ml-auto text-orange-400 hover:text-orange-700"><i
                        class="fa fa-times text-lg"></i></button>
            </div>
            <!-- Messages area -->
            <div class="flex-1 overflow-y-auto max-h-[calc(100vh-210px)] min-h-[80px] px-4 md:px-0">
                <div class="mb-2 text-sm text-gray-700 bg-orange-50 px-3 py-2 rounded-lg w-max">Hi! Can we chat about
                    marriage profile?</div>
                <div class="mb-2 text-sm text-white bg-orange-500 px-3 py-2 rounded-lg w-max ml-auto">Yes, of course!
                </div>
            </div>
            <div class="flex gap-1 mt-3 px-4 pb-4 md:px-0 md:pb-0">
                <input type="text" placeholder="Type your message..."
                    class="flex-1 border border-orange-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 ring-orange-200" />
                <button
                    class="bg-orange-500 hover:bg-orange-600 text-white px-4 rounded-lg font-bold transition text-base shadow"><i
                        class="fa fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    <!-- Top Navbar  end -->


    <!-- Main Layout -->
    <div class="flex flex-1 pt-12">

        <!-- side navbar start -->
        <aside
            class="hidden md:flex flex-col justify-between  w-20 fixed top-0 left-0 h-[100vh] py-50 items-center gap-6 border-r-3 border-orange-200 ">
            <a href="index.html" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
                <i class="fa-solid fa-house text-2xl mb-1"></i>
                <span class="text-xs">Home</span>
            </a>
            <a href="profile.html" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
                <i class="fa-solid fa-user text-2xl mb-1"></i>
                <span class="text-xs">Profile</span>
            </a>

            <a href="news.html" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
                <i class="fa-solid fa-newspaper text-2xl mb-1"></i>
                <span class="text-xs">News</span>
            </a>

            <a href="marraige.html" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
                <i class="fa-solid fa-ring text-2xl mb-1"></i>
                <span class="text-xs">Marriage</span>
            </a>



            <a href="temple.html" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
                <i class="fa-solid fa-church text-2xl mb-1"></i>
                <span class="text-xs">Temple Info</span>
            </a>



        </aside>
        <!-- side navbar end -->

        <main class="flex-1 px-2 md:px-10 py-5 bg-white md:ml-20 mb-13 md:mb-0 max-w-8xl overflow-hidden">

            <!-- NEWS FORM -->
            <form
                class="bg-white rounded-2xl shadow-xl border border-orange-200 max-w-xl mx-auto p-8 flex flex-col gap-6 mt-8 transition">
                <h3 class="text-2xl font-bold text-orange-700 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-newspaper text-orange-500"></i>
                    Post Community News
                </h3>
                <!-- Headline -->
                <div>
                    <label class="font-semibold text-orange-700 mb-1 block">Headline</label>
                    <input type="text" name="headline" placeholder="Enter your news headline"
                        class="w-full border-orange-200 rounded-lg px-4 py-3 bg-orange-50 text-lg focus:outline-none focus:ring-2 focus:ring-orange-200 placeholder-gray-400 shadow-sm transition">
                </div>
                <!-- Summary -->
                <div>
                    <label class="font-semibold text-orange-700 mb-1 block">Summary/Details</label>
                    <textarea name="summary" placeholder="Short news summary or details..." rows="3"
                        class="w-full border-orange-200 rounded-lg px-4 py-3 bg-orange-50 text-base resize-none focus:outline-none focus:ring-2 focus:ring-orange-200 placeholder-gray-400 shadow-sm transition"></textarea>
                </div>
                <!-- Featured Image -->
                <div>
                    <label class="font-semibold text-orange-700 mb-1 block">Image (optional)</label>
                    <input type="file" name="image" accept="image/*"
                        class="block w-full bg-orange-50 rounded-lg border border-orange-200 text-gray-700 px-3 py-2 file:bg-orange-100 file:rounded file:border-0 file:font-semibold file:text-orange-700 hover:file:bg-orange-200 focus:outline-none transition" />
                </div>
                <!-- Category + Reporter -->
                <div class="flex gap-4 flex-wrap items-center">
                    <div class="flex-1">
                        <label class="font-semibold text-orange-700 block mb-1">Category</label>
                        <select name="category"
                            class="w-full border-orange-200 rounded-lg px-4 py-3 bg-orange-50 text-base focus:outline-none focus:ring-2 focus:ring-orange-200 transition">
                            <option value="General">General</option>
                            <option value="Event">Event</option>
                            <option value="Announcement">Announcement</option>
                            <option value="Alert">Alert</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="font-semibold text-orange-700 block mb-1">Reporter Name</label>
                        <input type="text" name="reporter" placeholder="Your name"
                            class="w-full border-orange-200 rounded-lg px-4 py-3 bg-orange-50 text-base focus:outline-none focus:ring-2 focus:ring-orange-200 transition">
                    </div>
                </div>
                <!-- Action Button -->
                <button type="submit"
                    class="bg-orange-600 hover:bg-orange-700 text-white font-bold uppercase rounded-xl shadow py-3 px-6 text-lg transition flex items-center gap-2 justify-center">
                    <i class="fa-solid fa-paper-plane"></i> Post News
                </button>
            </form>

        </main>



    </div>

   <!--phone Navbar start -->
  <nav
    class="fixed bottom-0   justify-between left-0 w-full md:hidden bg-orange-500 flex items-center py-2 shadow z-50  whitespace-nowrap">
    <a href="index.html" class="flex flex-col items-center text-white mx-4">
      <i class="fa-solid fa-house text-xl"></i>
      <span class="text-xs mt-1">Home</span>
    </a>
    <a href="profile.html" class="flex flex-col items-center text-white mx-4">
      <i class="fa-solid fa-user text-xl"></i>
      <span class="text-xs mt-1">Profile</span>
    </a>


    <a href="news.html" class="flex flex-col items-center text-white mx-4">
      <i class="fa-solid fa-newspaper text-xl"></i>
      <span class="text-xs mt-1">News</span></a>


    <a href="marraige.html" class="flex flex-col items-center text-white mx-4">
      <i class="fa-solid fa-ring text-xl"></i>
      <span class="text-xs mt-1">Marriage</span>
    </a>
    </a>



    <a href="temple.html" class="flex flex-col items-center text-white mx-4">
      <i class="fa-solid fa-church text-xl"></i>
      <span class="text-xs mt-1">Temple Info</span>
    </a>



  </nav>
  <!--phone Navbar start -->









    <script>
        const btn = document.getElementById('profileBtn');
        const drop = document.getElementById('profileDropdown');
        btn.onclick = () => drop.classList.toggle('hidden');
        document.addEventListener('click', function (e) {
            if (!btn.contains(e.target) && !drop.contains(e.target)) {
                drop.classList.add('hidden');
            }
        });
    </script>
    <script>
        function showLimitedFriends() {
            const all = Array.from(document.querySelectorAll('.friend-block'));
            let count = 5;
            if (window.innerWidth < 640) count = 3;
            else if (window.innerWidth < 1024) count = 8;
            all.forEach((el, i) => {
                el.style.display = (i < count) ? 'flex' : 'none';
            });
            // Remove previous plus button if any
            let plusBtn = document.getElementById('plusSeeAll');
            if (plusBtn) plusBtn.remove();
            if (all.length > count) {

                const btn = document.createElement('a');
                btn.href = "friends.html";
                btn.id = "plusSeeAll";
                btn.className = "flex flex-col items-center justify-center px-3 py-2 rounded-lg bg-orange-200 text-orange-800 font-bold hover:bg-orange-300 transition";
                btn.title = "See all friends";
                btn.innerHTML = `<div class="flex items-center justify-center rounded-full bg-white border border-orange-400 w-10 h-10 text-xl">+${all.length - count}</div>
        <span class="text-xs mt-1 font-bold">See all</span>`;
                document.getElementById('friendRow').appendChild(btn);
            }
        }
        window.addEventListener('resize', showLimitedFriends);
        window.addEventListener('DOMContentLoaded', showLimitedFriends);
    </script>
    <script>
        // Message dropdown toggle
        const msgBtn = document.getElementById('messageBtn');
        const msgDropdown = document.getElementById('msgDropdown');
        msgBtn.onclick = (e) => { e.stopPropagation(); msgDropdown.classList.toggle('hidden'); };
        document.body.addEventListener('click', (e) => {
            if (!msgDropdown.contains(e.target) && !msgBtn.contains(e.target)) msgDropdown.classList.add('hidden');
        });
        // Chat modal logic
        function openChat(name) {
            document.getElementById('chatModal').classList.remove("hidden");
            if (name === "Shilpi Verma") {
                document.getElementById('chatName').textContent = "Shilpi Verma";
                document.getElementById('chatAvatar').src = "https://randomuser.me/api/portraits/women/47.jpg";
            } else {
                document.getElementById('chatName').textContent = "Rohit Sharma";
                document.getElementById('chatAvatar').src = "https://randomuser.me/api/portraits/men/45.jpg";
            }
            msgDropdown.classList.add('hidden');
        }
        function closeChat() { document.getElementById('chatModal').classList.add("hidden"); }
    </script>
</body>

</html>