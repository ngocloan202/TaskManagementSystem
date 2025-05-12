<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thông tin người dùng</title>
    <link rel="stylesheet" href="../../../public/css/tailwind.css" />
  </head>
  <body class="min-h-screen bg-indigo-50 flex items-center justify-center p-4">
    <!-- Wrapper chính -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg relative overflow-hidden">
      <!-- Nền tím nhạt và logo -->
      <div class="h-32 bg-indigo-200 flex items-center justify-center">
        <div
          class="w-24 h-24 bg-[#EEF0FF] rounded-full border-[#7C64F0] flex items-center justify-center shadow-md"
        >
          <img
            src="../../images/cubeflow-logo.png"
            alt="Cube Flow"
            class="object-contain w-full h-full rounded-full"
          />
        </div>
      </div>

      <!-- Nội dung form -->
      <div class="px-6 pb-8 pt-4">
        <h2 class="text-center text-2xl font-semibold text-gray-800 mb-6">Thông tin người dùng</h2>
        <form class="space-y-4">
          <!-- Tên người dùng -->
          <div>
            <label class="block text-gray-700 mb-1 font-medium" for="username">Tên người dùng</label>
            <input
              id="username"
              type="text"
              placeholder="Nhập tên của bạn"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none"
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-gray-700 mb-1 font-medium" for="email">Email</label>
            <input
              id="email"
              type="email"
              placeholder="example@domain.com"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none"
            />
          </div>

          <!-- Số điện thoại -->
          <div>
            <label class="block text-gray-700 mb-1 font-medium" for="phone">Số điện thoại</label>
            <input
              id="phone"
              type="tel"
              placeholder="09xx xxx xxx"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none"
            />
          </div>

          <!-- Số dự án -->
          <div>
            <label class="block text-gray-700 mb-1 font-medium" for="projects">Số dự án</label>
            <input
              id="projects"
              type="number"
              placeholder="0"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-300 focus:outline-none"
            />
          </div>

          <!-- Button group -->
          <div class="flex justify-center space-x-4 mt-6">
            <button
              type="button"
              class="px-6 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition"
            >
              Sửa
            </button>
            <button
              type="submit"
              class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition"
            >
              Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
