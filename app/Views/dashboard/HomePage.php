<?php
require_once "../../../config/SessionInit.php";
$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);
$currentPage = "dashboard";
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CubeFlow</title>
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
      
      /* Style cho modal container */
      .modal-container {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 50;
        background-color: rgba(0, 0, 0, 0.4);
        align-items: center;
        justify-content: center;
      }
      
      .modal-container.active {
        display: flex;
      }
    </style>
  </head>

<body style="background-color: #d9d9d9">
  <?php if ($flashSuccess): ?>
    <div id="flashModal" class="fixed inset-0 bg-opacity-40 flex items-center justify-center z-50"  style="background-color: rgba(0, 0, 0, 0.4);">
    <div class="bg-white p-6 rounded-xl text-center shadow-xl">
      <svg class="w-12 h-12 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="font-semibold text-gray-800"><?= htmlspecialchars($flashSuccess) ?></p>
    </div>
  </div>
  <script>
    setTimeout(()=> document.getElementById('flashModal').remove(), 2000);
  </script>
  <?php endif; ?>

  <?php if ($flashError): ?>
  <div id="flashModal" class="fixed inset-0 bg-opacity-40 flex items-center justify-center z-50" style="background-color: rgba(0, 0, 0, 0.4);">
    <div class="bg-white p-6 rounded-xl text-center shadow-xl">
      <svg class="w-12 h-12 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
      <p class="font-semibold text-gray-800"><?= htmlspecialchars($flashError) ?></p>
    </div>
  </div>
  <script>
    setTimeout(()=> document.getElementById('flashModal').remove(), 2000);
  </script>
  <?php endif; ?>

    <div class="flex h-screen">
      <?php include "../components/Sidebar.php"; ?>

      <div class="flex-1 flex flex-col">
        <?php include "../components/Header.php"; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6">
          <div class="mb-6 flex items-center text-gray-600">
            <a href="#" class="text-2xl font-bold" style="color: #3c40c6">Tất cả dự án</a>
          </div>
          <div id="projectGrid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- JS sẽ render các project card vào đây -->
            <div class="bg-blue-600 rounded-lg flex items-center justify-center h-64">
              <button id="openProjectModalBtn" type="button" class="w-full h-full">
                <div class="text-center">
                  <div class="w-16 h-16 bg-blue-300 bg-opacity-40 rounded-full flex items-center justify-center mx-auto hover:bg-blue-400">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="currentColor"
                      aria-hidden="true"
                      data-slot="icon"
                      class="text-white size-6"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                  </div>
                  <p class="text-white font-medium mt-4 text-lg">Thêm dự án</p>
                </div>
              </button>
            </div>
          </div>
        </main>
      </div>
    </div>
    <!-- Modal container cho dialog -->
    <div id="projectModalContainer" class="modal-container">
      <?php include "../projects/DialogCreateProject.php"; ?>
    </div>

    <script>
      // Lấy các phần tử DOM
      const openModalBtn = document.getElementById('openProjectModalBtn');
      const modalContainer = document.getElementById('projectModalContainer');
      
      // Tìm nút đóng trong dialog
      const closeModalBtn = modalContainer.querySelector('button');
      
      // Mở modal khi nhấn nút "Thêm dự án"
      openModalBtn.addEventListener('click', function() {
        modalContainer.classList.add('active');
      });
      
      // Đóng modal khi nhấn nút đóng
      closeModalBtn.addEventListener('click', function() {
        modalContainer.classList.remove('active');
      });
      
      // Đóng modal khi nhấn bên ngoài modal
      window.addEventListener('click', function(event) {
        if (event.target === modalContainer) {
          modalContainer.classList.remove('active');
        }
      });
    </script>
    </div>
    <script src="../../../public/js/ProjectList.js"></script>
  </body>
</html>