<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
  header("Location: /app/Views/auth/login.php");
  exit();
}

$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);
$currentPage = "dashboard";

// Get user role
$userRole = $_SESSION["role"] ?? "USER";
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CubeFlow - <?= $userRole === "ADMIN" ? "Admin Dashboard" : "Dashboard" ?></title>
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
        <main class="flex-1 p-6">
          <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-semibold text-gray-900 mb-6">
              <?= $userRole === "ADMIN" ? "Admin Dashboard" : "Dashboard" ?>
            </h1>
            
            <?php if ($userRole === "ADMIN"): ?>
              <!-- Admin specific content -->
              <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Quản lý hệ thống</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <a href="../admin/users.php" class="p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <h3 class="font-semibold text-indigo-700">Quản lý người dùng</h3>
                    <p class="text-sm text-gray-600">Xem và quản lý tất cả người dùng trong hệ thống</p>
                  </a>
                  <a href="../admin/projects.php" class="p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <h3 class="font-semibold text-indigo-700">Quản lý dự án</h3>
                    <p class="text-sm text-gray-600">Xem và quản lý tất cả dự án trong hệ thống</p>
                  </a>
                  <a href="../admin/settings.php" class="p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <h3 class="font-semibold text-indigo-700">Cài đặt hệ thống</h3>
                    <p class="text-sm text-gray-600">Cấu hình và quản lý cài đặt hệ thống</p>
                  </a>
                </div>
              </div>
            <?php endif; ?>

            <!-- Common content for all users -->
            <div class="bg-white rounded-lg shadow p-6">
              <h2 class="text-xl font-semibold mb-4">Dự án của bạn</h2>
              <!-- Project list will be loaded here -->
            </div>
          </div>
        </main>
      </div>
    </div>
</body>
</html>