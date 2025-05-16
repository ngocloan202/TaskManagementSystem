<!-- Header -->
<header class="bg-[#3C40C6] h-14 flex items-center px-6 shadow-md">
  <!-- Logo and back button section -->
  <div class="flex items-center space-x-4">
    <!-- Back button -->
    <button class="p-2 hover:bg-indigo-500 rounded-md text-white">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M15 19l-7-7 7-7"
        />
      </svg>
    </button>
  
  <!-- Search Box - Centered -->
  <div class="flex-grow flex justify-center">
    <div class="relative w-full max-w-xl">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-600 w-5 h-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input
        type="text"
        placeholder="Tìm kiếm"
        class="pl-10 pr-4 py-2 rounded-lg w-full focus:outline-none bg-white text-gray-700"
      />
    </div>
  </div>
  </div>
  
  <!-- Right side icons -->
  <div class="ml-auto flex items-center space-x-4">
    <!-- Notifications -->
    <button class="p-2 hover:bg-indigo-500 rounded-md text-white">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-5 h-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.916V4a2 2 0 10-4 0v1.084A6 6 0 004 11v3.159c0 .538-.214 1.055-.595 1.436L2 17h5m4 0v1a3 3 0 006 0v-1m-6 0h6"
        />
      </svg>
    </button>
    
<!-- Avatar/Profile button with dropdown -->
<div class="relative inline-block text-left">
  <button id="profileBtn" class="flex items-center focus:outline-none">
    <img
      src="/public/images/default-avatar.png"
      alt="Avatar"
      class="w-9 h-9 object-cover rounded-full border-2 border-white"
    />
  </button>

  <!-- Dropdown with arrow -->
  <div id="profileDropdown"
       class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-lg z-50 hidden">

    <!-- Nội dung dropdown -->
    <div class="py-3 px-5 text-gray-800 text-base space-y-2">
      <!-- Tên người dùng -->
      <div class="font-semibold text-[16px]">
        <?= "Xin chào, " . htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']) ?>
      </div>

      <!-- Thông tin cá nhân -->
      <a href="#" id="openProfile" class="flex items-center gap-2 hover:bg-indigo-100 px-3 py-2 rounded-md transition">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="size-6">
  <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 0 1 .67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 1 1-.671-1.34l.041-.022ZM12 9a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"></path>
</svg>
        Thông tin cá nhân
      </a>

      <!-- Đăng xuất -->
      <a href="../auth/logout.php"
         class="flex items-center gap-2 text-red-600 hover:bg-red-100 px-3 py-2 rounded-md transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24"
             stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
        </svg>
        Đăng xuất
      </a>
    </div>
  </div>
</div>

  </div>
</header> 

<script>
  document.getElementById('profileBtn').addEventListener('click', e => {
    e.stopPropagation();
    document.getElementById('profileDropdown').classList.toggle('hidden');
  });

  document.addEventListener('click', () => {
    document.getElementById('profileDropdown').classList.add('hidden');
  });
</script>