<?php
$title = "Chi tiết nhiệm vụ | CubeFlow";
$currentPage = "projects";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="../../../public/css/tailwind.css" />
  <style>
      .menuItem {
        margin-bottom: 2rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        width: 100%;
      }
      .menuItem:last-child {
        margin-bottom: 5px;
      }
    .custom-textarea {
      width: 100%;
      height: 200px; /* Điều chỉnh chiều cao theo ý muốn */
      border: 1px solid #e5e7eb; /* Màu viền */
      border-radius: 0.5rem; /* Bo góc */
      padding: 1rem; /* Khoảng cách bên trong */
    }
  </style>
</head>
<body style="background-color: #f0f2f5;">
  <div class="flex h-screen">
    <!-- Include Sidebar -->
    <?php include_once "../components/Sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Include Header/Topbar -->
      <?php include_once "../components/Header.php"; ?>
      
      <!-- Task Details Content -->
      <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
        <!-- Breadcrumb -->
        <div class="flex items-center mb-6 text-gray-600">
          <a href="#" class="text-indigo-600">Dự án</a>
          <span class="mx-2">></span>
          <span>Xây dựng quản lý website</span>
        </div>

        <!-- Task Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <button class="text-indigo-600 flex items-center">
              <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Quay lại
            </button>
            <div class="space-x-2">
              <button class="bg-red-600 hover:bg-orange-200 text-white px-4 py-2 rounded-md font-semibold">Xóa</button>
              <button class="bg-indigo-600 hover:bg-[#2970FF] text-white px-4 py-2 rounded-md font-semibold">Thay đổi</button>
            </div>
          </div>
          
          <h1 class="text-2xl font-bold">Viết giao diện Form đăng nhập</h1>
          <div class="text-gray-600 mt-2">trong danh sách <a href="#" class="text-indigo-600 font-bold">CẦN LÀM</a></div>
          
          <!-- Task Info -->
          <div class="mt-6 grid grid-cols-2 gap-6">
            <div>
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z"></path>
                  </svg>
                  <span>Tag</span>
                </div>
                <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-sm">Frontend</span>
              </div>
              
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                  </svg>
                  <span>Độ ưu tiên</span>
                </div>
                <div class="relative">
                  <select class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option>Chọn</option>
                    <option>Khẩn cấp</option>
                    <option>Cao</option>
                    <option>Trung bình</option>
                    <option>Thấp</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                  </svg>
                  <span>Ngày</span>
                </div>
                <input type="date" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <span class="mx-2">-</span>
                <input type="date" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
              </div>
            </div>
            
            <div>
              <div class="flex items-center mb-4">
                <div class="flex items-center text-gray-600 mr-8">
                  <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                  </svg>
                  <span>Thành viên</span>
                </div>
                <button class="flex items-center justify-center w-8 h-8 bg-gray-200 hover:bg-gray-300 rounded-full">
                  <img src="../../images/user-avatar.png" alt="User" class="w-8 h-8 rounded-full">
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Task Description -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <h2 class="text-xl font-bold mb-4">Mô tả nhiệm vụ</h2>
          <textarea class="custom-textarea">
          </textarea>
        </div>
        
        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-bold mb-4">Hoạt động gần đây</h2>
          <div class="space-y-4">
            <div class="flex items-start">
              <div>
                <p class="text-gray-500 text-sm">Hiện không có hoạt động nào</p>
              </div>
            </div>
            
          </div>
        </div>
      </main>
    </div>
  </div>
</body>
</html>