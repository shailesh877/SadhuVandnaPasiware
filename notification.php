<?php
include("header.php");
?>
     <main class="flex-1 px-2 md:px-10 py-5 bg-white md:ml-20 mb-13 md:mb-0">
    <div class="glass rounded-2xl shadow-2xl p-7 border border-orange-200 min-h-[87vh] overflow-x-auto">
      <!-- Tabs for type -->
      <div class="flex gap-3 border-b border-orange-100 pb-2 mb-7">
        <a class="font-bold px-4 py-2 rounded bg-orange-500 text-white shadow mr-4"><i class="fa-regular fa-bell text-white text-lg"></i> Notifications</a>
     
     
      </div>
      
      <!-- Notification Cards -->
      <div class="flex flex-col gap-5">
        <!-- Card 1: Unread -->
        <div class="flex items-center gap-4 bg-orange-100/80 rounded-xl shadow border border-orange-300 px-4 py-4 relative">
          <i class="fa fa-bullhorn text-orange-500 text-2xl"></i>
          <div class="flex-1">
            <div class="font-bold text-orange-700">Charity event this Sunday at 5pm. All members invited!</div>
            <div class="text-xs text-gray-500 flex gap-2 items-center">
              <i class="fa fa-clock"></i> 2m ago
            </div>
          </div>
          <span class="absolute top-2 right-4 w-2 h-2 bg-orange-600 rounded-full"></span>
        </div>
        
        <!-- Card 2: Read -->
        <div class="flex items-center gap-4 bg-white rounded-xl shadow border border-orange-100 px-4 py-4">
          <i class="fa fa-gift text-orange-500 text-2xl"></i>
          <div class="flex-1">
            <div class="font-bold text-orange-700">Congratulations! Member Amit Jain has got married.</div>
            <div class="text-xs text-gray-500 flex gap-2 items-center">
              <i class="fa fa-clock"></i> Today, 10am
            </div>
          </div>
        </div>

        <!-- Card 3: Marriage -->
        <div class="flex items-center gap-4 bg-white rounded-xl shadow border border-orange-100 px-4 py-4">
          <i class="fa fa-ring text-orange-500 text-2xl"></i>
          <div class="flex-1">
            <div class="font-bold text-orange-700">You have received a new marriage proposal!</div>
            <div class="text-xs text-gray-500 flex gap-2 items-center">
              <i class="fa fa-clock"></i> 1h ago
            </div>
          </div>
        </div>
        
        <!-- Card 4: News -->
        <div class="flex items-center gap-4 bg-white rounded-xl shadow border border-orange-100 px-4 py-4">
          <i class="fa fa-newspaper text-orange-500 text-2xl"></i>
          <div class="flex-1">
            <div class="font-bold text-orange-700">Temple painted and cleaned for Diwali celebrations.</div>
            <div class="text-xs text-gray-500 flex gap-2 items-center">
              <i class="fa fa-clock"></i> Yesterday, 7:00pm
            </div>
          </div>
        </div>
        <!-- More cards as needed -->
      </div>
    </div>
  </main>
  </div>

 