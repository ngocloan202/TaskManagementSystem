<!-- Modal Profile -->
<div id="profileModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-[9999] hidden">
  <div class="bg-white rounded-2xl shadow-lg w-full max-w-md relative overflow-hidden">
    <!-- Avatar user -->
    <div class="h-28 flex items-center justify-center bg-indigo-200">
      <div class="w-20 h-20 rounded-full border-4 border-black flex items-center justify-center bg-[#EEF0FF] shadow-md">
        <img
          id="profileAvatar"
          src="<?= htmlspecialchars($_SESSION['avatar'] ?? '/public/images/default-avatar.png') ?>"
          alt="Avatar"
          class="object-cover w-full h-full rounded-full"
        />
      </div>
    </div>
    <!-- Nút upload avatar -->
    <form id="avatarForm" enctype="multipart/form-data" class="flex flex-col items-center mt-2">
      <label class="cursor-pointer text-indigo-600 hover:underline text-sm">
        Đổi ảnh đại diện
        <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" />
      </label>
    </form>
    <!-- Nội dung form -->
    <div class="px-6 pb-8 pt-4">
      <h2 class="text-center text-2xl font-semibold text-gray-800 mb-6">Thông tin người dùng</h2>
      <form class="space-y-4">
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="username">Tên người dùng</label>
          <input id="username" type="text" value="<?= htmlspecialchars($_SESSION['fullname'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="email">Email</label>
          <input id="email" type="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="phone">Số điện thoại</label>
          <input id="phone" type="tel" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="projects">Số dự án</label>
          <input id="projects" type="number" value="<?= htmlspecialchars($_SESSION['project_count'] ?? 0) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div class="flex justify-center space-x-4 mt-6">
          <button type="button" class="px-6 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition">Sửa</button>
          <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">Lưu</button>
        </div>
      </form>
    </div>
    <!-- Nút đóng modal -->
    <button id="closeProfileModal" class="absolute top-3 right-3 text-gray-500 hover:text-black text-2xl font-bold">&times;</button>
  </div>
</div>