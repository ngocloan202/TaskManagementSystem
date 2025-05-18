<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Kiểm tra quyền admin
check_role("ADMIN");

$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);
$currentPage = "admin_dashboard";
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CubeFlow - Admin Dashboard</title>
    <link rel="stylesheet" href="../../../public/css/tailwind.css">
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
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include "../components/Sidebar.php"; ?>
        
        <div class="flex-1 flex flex-col">
            <?php include "../components/Header.php"; ?>
            
            <!-- Main Content -->
            <main class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Admin Dashboard</h1>
                    
                    <!-- Admin Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Total Users -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Tổng số người dùng</h3>
                            <?php
                            $stmt = $connect->prepare("SELECT COUNT(*) as count FROM Users");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $userCount = $result->fetch_assoc()["count"];
                            ?>
                            <p class="text-3xl font-bold text-indigo-600"><?= $userCount ?></p>
                        </div>
                        
                        <!-- Total Projects -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Tổng số dự án</h3>
                            <?php
                            $stmt = $connect->prepare("SELECT COUNT(*) as count FROM Project");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $projectCount = $result->fetch_assoc()["count"];
                            ?>
                            <p class="text-3xl font-bold text-indigo-600"><?= $projectCount ?></p>
                        </div>
                        
                        <!-- Total Tasks -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Tổng số công việc</h3>
                            <?php
                            $stmt = $connect->prepare("SELECT COUNT(*) as count FROM Task");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $taskCount = $result->fetch_assoc()["count"];
                            ?>
                            <p class="text-3xl font-bold text-indigo-600"><?= $taskCount ?></p>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold mb-4">Quản lý hệ thống</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="flex flex-col items-center justify-center bg-white rounded-lg p-6 shadow">
                                <div class="flex items-center justify-center w-14 h-14 bg-indigo-100 rounded-full mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-medium text-gray-900 mb-1">Quản lý người dùng</h3>
                                <p class="text-gray-500 text-center mb-4">Quản lý thông tin và phân quyền người dùng trong hệ thống</p>
                                <a href="Users.php" class="p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                    <span class="text-indigo-600 font-medium">Xem danh sách</span>
                                </a>
                            </div>
                            <a href="projects.php" class="p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                <h3 class="font-semibold text-indigo-700">Quản lý dự án</h3>
                                <p class="text-sm text-gray-600">Xem và quản lý tất cả dự án trong hệ thống</p>
                            </a>
                            <a href="settings.php" class="p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                <h3 class="font-semibold text-indigo-700">Cài đặt hệ thống</h3>
                                <p class="text-sm text-gray-600">Cấu hình và quản lý cài đặt hệ thống</p>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 