<?php
require_once "../../../config/SessionInit.php";
if (!isset($_SESSION['user_id'])) {
    header('Location: /app/Views/auth/login.php');
    exit;
}
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
    <div class="flex h-screen">
      <?php include "../components/Sidebar.php"; ?>

      <div class="flex-1 flex flex-col">
        <?php include "../components/Header.php"; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6">
          <div class="mb-6 flex items-center text-gray-600">
            <a href="#" class="text-2xl font-bold" style="color: #3c40c6">Tất cả dự án</a>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
  </body>
</html>