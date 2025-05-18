<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Kiểm tra quyền admin
check_role("ADMIN");

$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);
$currentPage = "admin_dashboard";

// Xác định tab hiện tại
$activeTab = $_GET['tab'] ?? 'overview';

// Lấy danh sách người dùng nếu đang ở tab users
$users = [];
if ($activeTab == 'users') {
    $usersQuery = "SELECT * FROM Users ORDER BY UserID DESC LIMIT 10";
    $usersResult = $connect->query($usersQuery);
    if ($usersResult) {
        while ($row = $usersResult->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// Lấy danh sách dự án nếu đang ở tab projects
$projects = [];
if ($activeTab == 'projects') {
    $projectsQuery = "SELECT p.*, u.Username, u.FullName, 
                     (SELECT COUNT(*) FROM ProjectMembers WHERE ProjectID = p.ProjectID) as MemberCount,
                     (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID) as TaskCount,
                     (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) as CompletedTaskCount
                     FROM Project p
                     LEFT JOIN Users u ON p.CreatedBy = u.UserID
                     ORDER BY p.ProjectID DESC
                     LIMIT 10";
    $projectsResult = $connect->query($projectsQuery);
    if ($projectsResult) {
        while ($row = $projectsResult->fetch_assoc()) {
            $projects[] = $row;
        }
    }
}

// Đếm tổng số dự án, người dùng và công việc
$totalProjectsQuery = "SELECT COUNT(*) as total FROM Project";
$totalUsersQuery = "SELECT COUNT(*) as total FROM Users";
$totalTasksQuery = "SELECT COUNT(*) as total FROM Task";

$totalProjectsResult = $connect->query($totalProjectsQuery);
$totalUsersResult = $connect->query($totalUsersQuery);
$totalTasksResult = $connect->query($totalTasksQuery);

$totalProjects = $totalProjectsResult ? $totalProjectsResult->fetch_assoc()['total'] : 0;
$totalUsers = $totalUsersResult ? $totalUsersResult->fetch_assoc()['total'] : 0;
$totalTasks = $totalTasksResult ? $totalTasksResult->fetch_assoc()['total'] : 0;
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
        
        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-value {
            height: 100%;
            background-color: #3b82f6;
            border-radius: 4px;
        }
        
        .tab-active {
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Thông báo flash từ session -->
    <?php if ($flashSuccess): ?>
        <div id="flash-success" class="hidden"><?= $flashSuccess ?></div>
    <?php endif; ?>
    
    <?php if ($flashError): ?>
        <div id="flash-error" class="hidden"><?= $flashError ?></div>
    <?php endif; ?>
    
    <div class="flex h-screen">
        <?php include "../components/Sidebar.php"; ?>
        
        <div class="flex-1 flex flex-col">
            <?php include "../components/Header.php"; ?>
            
            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-auto">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Admin Dashboard</h1>
                    
                    <!-- Admin Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Total Users -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Tổng số người dùng</h3>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">
                                <?= number_format($totalUsers) ?>
                            </p>
                        </div>
                        
                        <!-- Total Projects -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Tổng số dự án</h3>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">
                                <?= number_format($totalProjects) ?>
                            </p>
                        </div>
                        
                        <!-- Total Tasks -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-700">Tổng số công việc</h3>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">
                                <?= number_format($totalTasks) ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="bg-white rounded-lg shadow mb-6">
                        <div class="border-b border-gray-200">
                            <nav class="flex">
                                <a href="?tab=overview" class="px-6 py-4 text-sm font-medium <?= $activeTab == 'overview' ? 'tab-active' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                    Tổng quan
                                </a>
                                <a href="?tab=users" class="px-6 py-4 text-sm font-medium <?= $activeTab == 'users' ? 'tab-active' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                    Quản lý người dùng
                                </a>
                                <a href="?tab=projects" class="px-6 py-4 text-sm font-medium <?= $activeTab == 'projects' ? 'tab-active' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                    Quản lý dự án
                                </a>
                            </nav>
                        </div>
                        
                        <div class="p-6">
                            <!-- Tab Content -->
                            <?php if ($activeTab == 'overview'): ?>
                                <!-- Quản lý hệ thống -->
                                <h2 class="text-xl font-semibold mb-6">Quản lý hệ thống</h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Quản lý người dùng -->
                                    <a href="?tab=users" class="management-card block bg-blue-50 rounded-lg p-4 transition duration-200">
                                        <h3 class="text-lg font-medium text-blue-700 mb-1">Quản lý người dùng</h3>
                                        <p class="text-sm text-gray-600">Xem và quản lý tất cả người dùng trong hệ thống</p>
                                    </a>
                                    
                                    <!-- Quản lý dự án -->
                                    <a href="?tab=projects" class="management-card block bg-blue-50 rounded-lg p-4 transition duration-200">
                                        <h3 class="text-lg font-medium text-blue-700 mb-1">Quản lý dự án</h3>
                                        <p class="text-sm text-gray-600">Xem và quản lý tất cả dự án trong hệ thống</p>
                                    </a>
                                    
                                    <!-- Cài đặt hệ thống -->
                                    <a href="settings.php" class="management-card block bg-blue-50 rounded-lg p-4 transition duration-200">
                                        <h3 class="text-lg font-medium text-blue-700 mb-1">Cài đặt hệ thống</h3>
                                        <p class="text-sm text-gray-600">Cấu hình và quản lý cài đặt hệ thống</p>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Tab Users -->
                            <?php if ($activeTab == 'users'): ?>
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 flex-wrap">
                                    <!-- Tìm kiếm -->
                                    <form class="flex-1 flex items-center gap-2 min-w-0" method="GET">
                                        <input type="hidden" name="tab" value="users">
                                        <div class="relative flex-1 min-w-0">
                                            <input type="text" name="search" placeholder="Tìm kiếm người dùng..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                                                class="pl-10 pr-4 py-2 rounded-lg w-full min-w-0 focus:outline-none border border-gray-300 focus:border-indigo-500">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <button type="submit" class="ml-2 h-10 bg-indigo-600 text-white px-4 rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center justify-center text-sm font-medium">
                                            Tìm kiếm
                                        </button>
                                    </form>
                                    
                                    <!-- Thêm người dùng mới -->
                                    <a href="AddUser.php" class="h-10 bg-green-600 text-white px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center whitespace-nowrap text-sm font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Thêm người dùng mới
                                    </a>
                                </div>
                                
                                <!-- Bảng người dùng -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full bg-white" id="userTable">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên người dùng</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Họ và tên</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quyền</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (count($users) > 0): ?>
                                                <?php foreach ($users as $user): ?>
                                                    <tr class="hover-row">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $user['UserID'] ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($user['Username']) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['Email']) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['FullName']) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['Role'] === 'ADMIN' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' ?>">
                                                                <?= $user['Role'] ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <a href="EditUser.php?id=<?= $user['UserID'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Sửa</a>
                                                            <?php if ($user['UserID'] != $_SESSION['user_id']): ?>
                                                                <a href="#" onclick="confirmDelete(<?= $user['UserID'] ?>, '<?= htmlspecialchars($user['Username']) ?>')" class="text-red-600 hover:text-red-900">Xóa</a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Không tìm thấy người dùng nào</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Xem tất cả link -->
                                <div class="mt-4 text-right">
                                    <a href="Users.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        Xem tất cả người dùng
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Tab Projects -->
                            <?php if ($activeTab == 'projects'): ?>
                                <div class="flex justify-between items-center mb-6">
                                    <div class="w-1/2">
                                        <form action="" method="GET" class="flex">
                                            <input type="hidden" name="tab" value="projects">
                                            <div class="relative flex-1">
                                                <input type="text" name="search" placeholder="Tìm kiếm dự án..." 
                                                    class="w-full p-2 pl-10 border border-gray-300 rounded-l-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700 transition-colors">Tìm kiếm</button>
                                        </form>
                                    </div>
                                    
                                    <a href="AddProject.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Thêm dự án mới
                                    </a>
                                </div>
                                
                                <!-- Bảng dự án -->
                                <?php if (count($projects) > 0): ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên dự án</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người tạo</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiến độ</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thành viên</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($projects as $project): ?>
                                                    <?php 
                                                    // Tính toán phần trăm hoàn thành
                                                    $progress = 0;
                                                    if ($project['TaskCount'] > 0) {
                                                        $progress = round(($project['CompletedTaskCount'] / $project['TaskCount']) * 100);
                                                    }
                                                    ?>
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            <?= $project['ProjectID'] ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($project['ProjectName']) ?></div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($project['FullName']) ?></div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="flex items-center">
                                                                <div class="w-24 mr-2">
                                                                    <div class="progress-bar">
                                                                        <div class="progress-value" style="width: <?= $progress ?>%"></div>
                                                                    </div>
                                                                </div>
                                                                <span class="text-sm text-gray-500"><?= $progress ?>%</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500"><?= $project['MemberCount'] ?></div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500">
                                                                <?php 
                                                                $startDate = date('d/m/Y', strtotime($project['StartDate']));
                                                                $endDate = date('d/m/Y', strtotime($project['EndDate']));
                                                                echo "$startDate - $endDate"; 
                                                                ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <a href="ViewProject.php?id=<?= $project['ProjectID'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Xem</a>
                                                            <a href="EditProject.php?id=<?= $project['ProjectID'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Sửa</a>
                                                            <a href="#" onclick="confirmDeleteProject(<?= $project['ProjectID'] ?>, '<?= htmlspecialchars($project['ProjectName']) ?>')" class="text-red-600 hover:text-red-900">Xóa</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-6">
                                        <div class="text-gray-500 mb-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-xl">Không tìm thấy dự án nào</p>
                                        </div>
                                        <a href="AddProject.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Tạo dự án mới
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Xem tất cả link -->
                                <div class="mt-4 text-right">
                                    <a href="Projects.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        Xem tất cả dự án
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hover effect for management cards
            const cards = document.querySelectorAll('.management-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.classList.add('bg-blue-100');
                });
                card.addEventListener('mouseleave', () => {
                    card.classList.remove('bg-blue-100');
                });
            });
            
            // Show flash messages
            const flashSuccess = document.getElementById('flash-success');
            const flashError = document.getElementById('flash-error');
            
            if (flashSuccess && flashSuccess.textContent.trim() !== '') {
                showNotification(flashSuccess.textContent, 'success');
            }
            
            if (flashError && flashError.textContent.trim() !== '') {
                showNotification(flashError.textContent, 'error');
            }
            
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
                    type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 
                    'bg-red-100 border-l-4 border-red-500 text-red-700'
                }`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.classList.add('opacity-0', 'translate-x-full');
                    notification.style.transition = 'opacity 0.5s, transform 0.5s';
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }, 5000);
            }
        });
        
        // Xác nhận xóa người dùng
        function confirmDelete(userId, username) {
            if (confirm(`Bạn có chắc chắn muốn xóa người dùng "${username}" không?`)) {
                window.location.href = `Users.php?action=delete&id=${userId}`;
            }
        }
        
        // Xác nhận xóa dự án
        function confirmDeleteProject(projectId, projectName) {
            if (confirm(`Bạn có chắc chắn muốn xóa dự án "${projectName}" không?`)) {
                window.location.href = `Projects.php?action=delete&id=${projectId}`;
            }
        }
    </script>
</body>
</html> 