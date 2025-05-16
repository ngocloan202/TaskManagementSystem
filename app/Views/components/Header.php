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
      src="<?= htmlspecialchars($_SESSION["avatar"] ?? "/public/images/default-avatar.png") ?>"
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
        <?= "Xin chào, " . htmlspecialchars($_SESSION["fullname"] ?? $_SESSION["username"]) ?>
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
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('profileBtn').addEventListener('click', e => {
    e.stopPropagation();
    document.getElementById('profileDropdown').classList.toggle('hidden');
  });

  document.addEventListener('click', () => {
    document.getElementById('profileDropdown').classList.add('hidden');
  });

  // Mở modal khi click "Thông tin cá nhân"
  document.getElementById('openProfile').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('profileModal').classList.remove('hidden');
  });

  // Đóng modal khi click nút đóng hoặc nền tối
  document.getElementById('closeProfileModal').addEventListener('click', function() {
    document.getElementById('profileModal').classList.add('hidden');
  });
  document.getElementById('profileModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
  });

  document.getElementById('avatarInput').addEventListener('change', function() {
    const form = document.getElementById('avatarForm');
    const data = new FormData(form);

    fetch(form.action, { method: 'POST', body: data })
      .then(res => res.json())
      .then(json => {
        if (json.success) {
          // Chỉ cập nhật ảnh trong modal preview
          document.getElementById('profileAvatar').src = json.avatar;
          // Lưu đường dẫn ảnh mới vào một biến ẩn để sử dụng khi ấn nút Lưu
          document.getElementById('newAvatarPath').value = json.avatar;
        } else {
          alert('Upload thất bại: ' + (json.error || 'Lỗi không xác định'));
        }
      })
      .catch(() => alert('Lỗi mạng!'));
  });

  // Xử lý khi ấn nút Lưu
  document.querySelector('form button[type="submit"]').addEventListener('click', function(e) {
    e.preventDefault();
    const newAvatarPath = document.getElementById('newAvatarPath').value;
    if (newAvatarPath) {
      // Cập nhật ảnh đại diện trên header
      document.querySelector('#profileBtn img').src = newAvatarPath;
      // Xóa đường dẫn tạm
      document.getElementById('newAvatarPath').value = '';
    }
    // TODO: Thêm code lưu các thông tin khác ở đây
  });
});

</script>

<!-- Modal Profile -->
<div id="profileModal" style="background-color: rgba(0, 0, 0, 0.4);" class="fixed inset-0 bg-opacity-40 flex items-center justify-center z-[9999] hidden">
  <div class="bg-white rounded-2xl shadow-lg w-full max-w-md relative overflow-hidden">
    <!-- Avatar user -->
    <div class="h-28 flex items-center justify-center bg-indigo-200">
      <div class="w-20 h-20 rounded-full border-4 border-black flex items-center justify-center bg-[#EEF0FF] shadow-md">
        <img
          id="profileAvatar"
          src="<?= htmlspecialchars($_SESSION["avatar"] ?? "/public/images/default-avatar.png") ?>"
          alt="Avatar"
          class="object-cover w-full h-full rounded-full"
        />
      </div>
    </div>
    <!-- Nút upload avatar -->
    <form id="avatarForm" action="../../Controllers/UploadAvatar.php"
      method="POST" enctype="multipart/form-data" class="mt-2 text-center">
      <input type="hidden" id="newAvatarPath" value="">
      <label class="cursor-pointer text-indigo-600 hover:underline text-sm">
        Đổi ảnh đại diện
        <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden"/>
      </label>
    </form>
    <!-- Nội dung form -->
    <div class="px-6 pb-8 pt-4">
      <h2 class="text-center text-2xl font-semibold text-gray-800 mb-6">Thông tin người dùng</h2>
      <form class="space-y-4">
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="username">Tên người dùng</label>
          <input id="username" type="text" value="<?= htmlspecialchars(
            $_SESSION["fullname"] ?? ""
          ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="email">Email</label>
          <input id="email" type="email" value="<?= htmlspecialchars(
            $_SESSION["email"] ?? ""
          ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="phone">Số điện thoại</label>
          <input id="phone" type="tel" value="<?= htmlspecialchars(
            $_SESSION["phone"] ?? ""
          ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="projects">Số dự án</label>
          <input id="projects" type="number" value="<?= htmlspecialchars(
            $_SESSION["project_count"] ?? 0
          ) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div class="flex justify-center space-x-4 mt-6">
          <button type="button" class="px-6 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition">Sửa</button>
          <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">Lưu</button>
        </div>
      </form>
    </div>
    <!-- Nút đóng modal -->
    <button id="closeProfileModal" class="absolute top-3 right-3 text-gray-500 hover:text-red-600 text-2xl font-bold">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="size-7 ">
  <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd"></path>
</svg>
    </button>
  </div>
</div>