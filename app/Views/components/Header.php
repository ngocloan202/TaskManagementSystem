<?php
/**
 * Header Component
 * 
 * @param string $pageTitle Tiêu đề trang
 */
?>

<!-- Header -->
<header class="bg-[#3C40C6] h-14 flex items-center px-4 shadow-md">
  <!-- Logo and back button section -->
  <div class="flex items-center">
    <!-- Back button -->
    <button class="mr-3 p-1 hover:bg-indigo-500 rounded-md text-white">
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
          d="M15 19l-7-7 7-7"
        />
      </svg>
    </button>
    
    <!-- Logo -->
    <div class="flex items-center">
      <div class="w-8 h-8 rounded-full overflow-hidden mr-2">
        <img
          src="../../images/cubeflow-logo.png"
          alt="CubeFlow Logo"
          class="w-full h-full object-cover"
        />
      </div>
      <span class="text-white text-lg font-semibold">CubeFlow</span>
    </div>
  </div>
  
  <!-- Search Box - Centered -->
  <div class="flex-grow flex justify-center">
    <div class="relative w-full max-w-xl">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 w-5 h-5"
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
        class="pl-10 pr-4 py-1.5 rounded-lg w-full focus:outline-none bg-white text-gray-700"
      />
    </div>
  </div>
  
  <!-- Right side icons -->
  <div class="flex items-center space-x-3">
    <!-- Notifications -->
    <button class="p-1.5 hover:bg-indigo-500 rounded-md text-white">
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
    
    <!-- Profile -->
    <button class="bg-white rounded-full overflow-hidden w-9 h-9 flex items-center justify-center">
      <img
        src="../../images/user-avatar.png"
        alt="User Avatar"
        class="w-9 h-9 object-cover"
      />
    </button>
  </div>
</header> 