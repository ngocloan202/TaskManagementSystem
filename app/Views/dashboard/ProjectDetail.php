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
            <a href="#" class="text-indigo-600 font-bold">Dự án</a>
            <span class="mx-2">></span>
            <span class="font-bold">Xây dựng quản lý website</span>
          </div>

          <!-- Project Details -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <div class="flex items-center text-gray-600 mb-2">
                  <span class="font-semibold text-xl text-black">Xây dựng quản lý website</span>
                </div>
                <div class="text-gray-500">Xây dựng website để quản lý</div>
                <div class="flex items-center mt-2">
                  <span class="font-semibold mr-2">Tiến độ: 68%</span>
                  <div class="w-60 bg-gray-200 rounded-full h-2 mr-4">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 68%"></div>
                  </div>
                  <button class="bg-gray-200 text-indigo-700 px-3 py-1 rounded ml-2">Bảng</button>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <!-- Avatars -->
                <div class="flex -space-x-2">
                  <img src="..." class="w-8 h-8 rounded-full border-2 border-white bg-green-400" />
                  <img src="..." class="w-8 h-8 rounded-full border-2 border-white bg-blue-400" />
                  <img src="..." class="w-8 h-8 rounded-full border-2 border-white bg-purple-400" />
                </div>
                <button id="btnMember" class="flex items-center bg-gray-200 px-3 py-2 rounded hover:bg-gray-300 ml-2">
                  <svg class="w-5 h-5 mr-1" ...></svg> Quản lý thành viên
                </button>
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon" class="size-6 ">
  <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"></path>
</svg>
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

    <script>
      // Toggle dialog tạo task
      document.querySelectorAll('.btn-add-task').forEach(btn => {
        btn.onclick = () => document.getElementById('createTaskDialog').style.display = 'block';
      });
      // Toggle dialog quản lý thành viên
      document.getElementById('btnMember').onclick = () => {
        document.getElementById('memberDialog').style.display = 'block';
      };
    </script>
  </body>
</html> 