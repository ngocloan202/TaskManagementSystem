<?php
require_once "../../../config/SessionInit.php";
require_once __DIR__ . '/../../../config/database.php';
include __DIR__ . "../../../Controllers/LoadUserData.php";
?>
<!-- Modal Profile -->
<div id="profileModal" style="background-color: rgba(0, 0, 0, 0.4);"
  class="fixed inset-0 bg-opacity-40 flex items-center justify-center z-[9999] hidden">
  <div class="bg-white rounded-2xl shadow-lg w-full max-w-md relative overflow-hidden">
    <!-- Avatar user -->
    <div class="h-28 flex items-center justify-center bg-indigo-200">
      <div class="w-20 h-20 rounded-full border-4 border-black flex items-center justify-center bg-[#EEF0FF] shadow-md">
        <img id="profileAvatar" src="<?= htmlspecialchars(
          $_SESSION["avatar"] ?? "/public/images/default-avatar.png"
        ) ?>" alt="Avatar" class="object-cover w-full h-full rounded-full" />
      </div>
    </div>
    <!-- Nút upload avatar -->
    <form id="avatarForm" action="../../Controllers/UploadAvatar.php" method="POST" enctype="multipart/form-data"
      class="mt-2 text-center">
      <input type="hidden" id="newAvatarPath" value="">
      <label class="cursor-pointer text-indigo-600 hover:underline text-sm">
        Đổi ảnh đại diện
        <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" />
      </label>
    </form>
    <!-- Nội dung form -->
    <div class="px-6 pb-8 pt-4">
      <h2 class="text-center text-2xl font-semibold text-gray-800 mb-6">Thông tin người dùng</h2>
      <form id="profileForm" class="space-y-4" action="../../Controllers/UploadAvatar" method="POST">
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="username">Tên người dùng</label>
          <input id="username" name="fullname" type="text" value="<?= htmlspecialchars(
            $_SESSION["fullname"] ?? ""
          ) ?>" readonly
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= htmlspecialchars($_SESSION["email"] ?? "") ?>"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="phone">Số điện thoại</label>
          <input id="phone" name="phone" type="tel" value="<?= htmlspecialchars($_SESSION["phone"] ?? "") ?>"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div>
          <label class="block text-gray-700 mb-1 font-medium" for="projects">Số dự án</label>
          <input id="projects" name="project_count" type="number" value="<?= htmlspecialchars(
            $_SESSION["project_count"] ?? 0
          ) ?>"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
        </div>
        <div class="flex justify-center space-x-4 mt-6">
          <button id="btnProfileEdit" type="button"
            class="px-6 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition">Sửa
          </button>
          <button id="btnProfileSave" type="submit"
            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition hidden">Lưu
          </button>
          </div>
      </form>
    </div>
    <!-- Nút đóng modal -->
    <button id="closeProfileModal" class="absolute top-3 right-3 text-gray-500 hover:text-red-400 text-2xl font-bold">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
        data-slot="icon" class="size-7 ">
        <path fill-rule="evenodd"
          d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
          clip-rule="evenodd"></path>
      </svg>
    </button>
  </div>
</div>

<script src="../../../public/js/ProfileModal.js"></script>