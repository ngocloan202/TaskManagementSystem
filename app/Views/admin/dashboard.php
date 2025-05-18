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
                            <h3 class="text-lg font-medium text-gray-700">Tổng số người dùng</h3>
                            <?php
                            $stmt = $connect->prepare("SELECT COUNT(*) as count FROM Users");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $userCount = $result->fetch_assoc()["count"];
                            ?>
                            <p class="text-xl font-bold text-indigo-600 mt-2"><?= $userCount ?></p>
                        </div>
                        
                        <!-- Total Projects -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Tổng số dự án</h3>
                            <?php
                            $stmt = $connect->prepare("SELECT COUNT(*) as count FROM Project");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $projectCount = $result->fetch_assoc()["count"];
                            ?>
                            <p class="text-xl font-bold text-indigo-600 mt-2"><?= $projectCount ?></p>
                        </div>
                        
                        <!-- Total Tasks -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Tổng số công việc</h3>
                            <?php
                            $stmt = $connect->prepare("SELECT COUNT(*) as count FROM Task");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $taskCount = $result->fetch_assoc()["count"];
                            ?>
                            <p class="text-xl font-bold text-indigo-600 mt-2"><?= $taskCount ?></p>
                        </div>
                    </div>
                    
                    <!-- Quản lý hệ thống -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-xl font-semibold mb-6">Quản lý hệ thống</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Quản lý người dùng -->
                            <a href="Users.php" class="block bg-blue-50 hover:bg-blue-100 rounded-lg p-4 transition duration-200">
                                <h3 class="text-lg font-medium text-blue-700 mb-1">Quản lý người dùng</h3>
                                <p class="text-sm text-gray-600">Xem và quản lý tất cả người dùng trong hệ thống</p>
                            </a>
                            
                            <!-- Quản lý dự án -->
                            <a href="projects.php" class="block bg-blue-50 hover:bg-blue-100 rounded-lg p-4 transition duration-200">
                                <h3 class="text-lg font-medium text-blue-700 mb-1">Quản lý dự án</h3>
                                <p class="text-sm text-gray-600">Xem và quản lý tất cả dự án trong hệ thống</p>
                            </a>
                            
                            <!-- Cài đặt hệ thống -->
                            <a href="settings.php" class="block bg-blue-50 hover:bg-blue-100 rounded-lg p-4 transition duration-200">
                                <h3 class="text-lg font-medium text-blue-700 mb-1">Cài đặt hệ thống</h3>
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