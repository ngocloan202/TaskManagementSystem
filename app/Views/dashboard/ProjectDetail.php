<?php
$title = "Chi tiết dự án | CubeFlow";
$currentPage = "projects";
?>

<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
    </style>
  </head>

  <body style="background-color: #f0f2f5">
    <div class="flex h-screen">
      <!-- Include Sidebar -->
      <?php include_once "../components/Sidebar.php"; ?>

      <!-- Main Content -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Include Header/Topbar -->
        <?php include_once "../components/Header.php"; ?>

        <!-- Main Content - như trong hình -->
        <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
          <!-- Breadcrumb -->
          <div class="mb-6 flex items-center text-gray-600">
            <a href="#" class="text-indigo-600">Dự án</a>
            <span class="mx-2">></span>
            <span>Xây dựng quản lý website</span>
          </div>

          <!-- Project Details -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
              <h1 class="text-2xl font-bold">Xây dựng quản lý website</h1>
              <button class="bg-indigo-600 text-white px-4 py-2 rounded-md">+ Thêm nhiệm vụ</button>
            </div>
            
            <!-- Project Statistics -->
            <div class="grid grid-cols-3 gap-6 mt-6">
              <div class="bg-indigo-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-2">Nhiệm vụ</h3>
                <div class="flex justify-between">
                  <span class="text-3xl font-bold text-indigo-600">12</span>
                  <span class="text-sm text-gray-500">Tổng số</span>
                </div>
              </div>
              <div class="bg-green-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-2">Hoàn thành</h3>
                <div class="flex justify-between">
                  <span class="text-3xl font-bold text-green-600">8</span>
                  <span class="text-sm text-gray-500">66.7%</span>
                </div>
              </div>
              <div class="bg-red-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-2">Trễ hạn</h3>
                <div class="flex justify-between">
                  <span class="text-3xl font-bold text-red-600">2</span>
                  <span class="text-sm text-gray-500">16.7%</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Task Lists -->
          <div class="grid grid-cols-3 gap-6">
            <!-- CẦN LÀM -->
            <div class="bg-white rounded-lg shadow-sm p-4">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-blue-600">CẦN LÀM</h2>
                <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-sm">4</span>
              </div>
              
              <!-- Task Item -->
              <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-md transition-shadow cursor-pointer">
                <h3 class="font-semibold">Viết giao diện Form đăng nhập</h3>
                <div class="flex justify-between items-center mt-3">
                  <span class="bg-purple-100 text-purple-600 px-2 py-1 rounded-full text-xs">Frontend</span>
                  <span class="text-sm text-gray-500">14/05/2025</span>
                </div>
              </div>
              
              <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-md transition-shadow cursor-pointer">
                <h3 class="font-semibold">Thiết kế database</h3>
                <div class="flex justify-between items-center mt-3">
                  <span class="bg-yellow-100 text-yellow-600 px-2 py-1 rounded-full text-xs">Database</span>
                  <span class="text-sm text-gray-500">20/05/2025</span>
                </div>
              </div>
            </div>
            
            <!-- ĐANG LÀM -->
            <div class="bg-white rounded-lg shadow-sm p-4">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-yellow-600">ĐANG LÀM</h2>
                <span class="bg-yellow-100 text-yellow-600 px-2 py-1 rounded text-sm">2</span>
              </div>
              
              <!-- Task Item -->
              <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-md transition-shadow cursor-pointer">
                <h3 class="font-semibold">Xây dựng API endpoints</h3>
                <div class="flex justify-between items-center mt-3">
                  <span class="bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs">Backend</span>
                  <span class="text-sm text-gray-500">10/05/2025</span>
                </div>
              </div>
            </div>
            
            <!-- HOÀN THÀNH -->
            <div class="bg-white rounded-lg shadow-sm p-4">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-green-600">HOÀN THÀNH</h2>
                <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-sm">6</span>
              </div>
              
              <!-- Task Item -->
              <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-md transition-shadow cursor-pointer">
                <h3 class="font-semibold">Wireframing</h3>
                <div class="flex justify-between items-center mt-3">
                  <span class="bg-purple-100 text-purple-600 px-2 py-1 rounded-full text-xs">Design</span>
                  <span class="text-sm text-gray-500">01/05/2025</span>
                </div>
              </div>
              
              <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-md transition-shadow cursor-pointer">
                <h3 class="font-semibold">Research & Planning</h3>
                <div class="flex justify-between items-center mt-3">
                  <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded-full text-xs">Planning</span>
                  <span class="text-sm text-gray-500">28/04/2025</span>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </body>
</html> 