<?php
// Kết nối đến cơ sở dữ liệu và khởi tạo phiên
require_once '../../../config/database.php';
require_once '../../../config/SessionInit.php';

// Khởi tạo kết nối
$connect = $GLOBALS['connect'];

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

// Xử lý xóa dự án
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $projectId = $_GET['id'];
    
    // Kiểm tra xem dự án có tồn tại và có task không
    $checkTasksQuery = "SELECT COUNT(*) as task_count FROM Task WHERE ProjectID = ?";
    $stmt = $connect->prepare($checkTasksQuery);
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $taskResult = $stmt->get_result();
    $taskData = $taskResult->fetch_assoc();
    
    if ($taskData['task_count'] > 0) {
        // Dự án có task, không thể xóa
        $_SESSION['error_message'] = "Không thể xóa dự án có nhiệm vụ! Vui lòng xóa tất cả nhiệm vụ trước.";
    } else {
        // Xóa thành viên dự án trước
        $deleteMembers = "DELETE FROM ProjectMembers WHERE ProjectID = ?";
        $stmt = $connect->prepare($deleteMembers);
        $stmt->bind_param("i", $projectId);
        
        if ($stmt->execute()) {
            // Sau đó xóa dự án
            $deleteProject = "DELETE FROM Project WHERE ProjectID = ?";
            $stmt = $connect->prepare($deleteProject);
            $stmt->bind_param("i", $projectId);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Xóa dự án thành công!";
            } else {
                $_SESSION['error_message'] = "Lỗi khi xóa dự án: " . $connect->error;
            }
        } else {
            $_SESSION['error_message'] = "Lỗi khi xóa thành viên dự án: " . $connect->error;
        }
    }
    
    // Chuyển hướng để tránh gửi lại form khi refresh
    header("Location: Projects.php");
    exit();
}

// Xây dựng truy vấn cơ sở
$baseQuery = "SELECT p.*, u.Username, u.FullName, 
             (SELECT COUNT(*) FROM ProjectMembers WHERE ProjectID = p.ProjectID) as MemberCount,
             (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID) as TaskCount,
             (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) as CompletedTaskCount
             FROM Project p
             LEFT JOIN Users u ON p.CreatedBy = u.UserID";

// Tìm kiếm và lọc
$whereConditions = [];
$params = [];
$types = "";

// Xử lý tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $whereConditions[] = "(p.ProjectName LIKE ? OR p.ProjectDescription LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// Hoàn thiện câu truy vấn
if (!empty($whereConditions)) {
    $baseQuery .= " WHERE " . implode(" AND ", $whereConditions);
}

// Sắp xếp theo ID dự án (mới nhất trước)
$baseQuery .= " ORDER BY p.ProjectID DESC";

// Chuẩn bị và thực thi truy vấn
$stmt = $connect->prepare($baseQuery);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$projects = [];

while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

// Đếm tổng số dự án
$totalProjectsQuery = "SELECT COUNT(*) as total FROM Project";
$totalResult = $connect->query($totalProjectsQuery);
$totalProjects = $totalResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý dự án - Admin</title>
    <link rel="stylesheet" href="/public/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #10b981;
            --danger-color: #ef4444;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f5f9;
        }
        
        .header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar {
            background-color: var(--primary-color);
            color: white;
        }
        
        .active-link {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid white;
        }
        
        .search-input {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 0.5rem;
        }
        
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        
        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1e40af;
        }
        
        .btn-success {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-danger {
            color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #fee2e2;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-value {
            height: 100%;
            background-color: var(--secondary-color);
            border-radius: 4px;
        }
        
        .icon-button {
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
        }
        
        .icon-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .edit-icon {
            color: var(--secondary-color);
        }
        
        .delete-icon {
            color: var(--danger-color);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="header flex items-center justify-between p-4">
        <div class="flex items-center space-x-4">
            <a href="#" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                    <span class="text-primary-color font-bold">C</span>
                </div>
                <span class="font-bold text-xl">CubeFlow</span>
            </a>
            
            <button class="text-gray-300 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M15.707 4.293a1 1 0 010 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 011.414-1.414L10 8.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        
        <div class="w-1/3 mx-auto">
            <div class="relative">
                <input type="text" placeholder="Tìm kiếm" class="search-input w-full py-1 px-3 pl-8">
                <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
            </div>
        </div>
        
        <div class="flex items-center space-x-4">
            <button class="text-gray-300 hover:text-white relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </button>
            
            <div class="avatar flex items-center justify-center text-white font-bold">
                A
            </div>
        </div>
    </header>
    
    <div class="flex flex-1">
        <!-- Sidebar -->
        <aside class="sidebar w-64 flex-shrink-0">
            <nav class="flex flex-col p-4">
                <a href="dashboard.php" class="flex items-center space-x-2 p-3 rounded-md text-gray-300 hover:text-white hover:bg-opacity-20 hover:bg-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                
                <a href="Users.php" class="flex items-center space-x-2 p-3 rounded-md text-gray-300 hover:text-white hover:bg-opacity-20 hover:bg-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Quản lý người dùng</span>
                </a>
                
                <a href="Projects.php" class="flex items-center space-x-2 p-3 rounded-md text-white bg-opacity-20 bg-white active-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <span>Quản lý dự án</span>
                </a>
                
                <a href="#" class="flex items-center space-x-2 p-3 rounded-md text-gray-300 hover:text-white hover:bg-opacity-20 hover:bg-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Cài đặt hệ thống</span>
                </a>
                
                <div class="mt-auto pt-8">
                    <a href="#" class="flex items-center space-x-2 p-3 rounded-md text-gray-300 hover:text-white hover:bg-opacity-20 hover:bg-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Trợ giúp</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main content -->
        <main class="flex-1 p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Quản lý dự án</h1>
            
            <!-- Thông báo -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $_SESSION['success_message']; ?></p>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $_SESSION['error_message']; ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Form tìm kiếm -->
            <div class="flex justify-between items-center mb-6">
                <div class="w-1/2">
                    <form action="" method="GET" class="flex">
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
                        <button type="submit" class="btn btn-primary rounded-l-none">Tìm kiếm</button>
                    </form>
                </div>
                
                <a href="AddProject.php" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Thêm dự án mới
                </a>
            </div>
            
            <!-- Bảng dự án -->
            <div class="card p-6">
                <?php if (count($projects) > 0): ?>
                    <div class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên dự án</th>
                                    <th>Người tạo</th>
                                    <th>Tiến độ</th>
                                    <th>Thành viên</th>
                                    <th>Thời gian</th>
                                    <th class="text-right">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <?php 
                                    // Tính toán phần trăm hoàn thành
                                    $progress = 0;
                                    if ($project['TaskCount'] > 0) {
                                        $progress = round(($project['CompletedTaskCount'] / $project['TaskCount']) * 100);
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td><?php echo $project['ProjectID']; ?></td>
                                        <td class="font-medium"><?php echo htmlspecialchars($project['ProjectName']); ?></td>
                                        <td><?php echo htmlspecialchars($project['FullName']); ?></td>
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-24 mr-2">
                                                    <div class="progress-bar">
                                                        <div class="progress-value" style="width: <?php echo $progress; ?>%"></div>
                                                    </div>
                                                </div>
                                                <span><?php echo $progress; ?>%</span>
                                            </div>
                                        </td>
                                        <td><?php echo $project['MemberCount']; ?></td>
                                        <td>
                                            <?php 
                                            $startDate = date('d/m/Y', strtotime($project['StartDate']));
                                            $endDate = date('d/m/Y', strtotime($project['EndDate']));
                                            echo "$startDate - $endDate"; 
                                            ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex justify-end space-x-2">
                                                <a href="ViewProject.php?id=<?php echo $project['ProjectID']; ?>" class="icon-button" title="Xem chi tiết">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 edit-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <a href="EditProject.php?id=<?php echo $project['ProjectID']; ?>" class="icon-button" title="Chỉnh sửa">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 edit-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </a>
                                                <button onclick="confirmDelete(<?php echo $project['ProjectID']; ?>, '<?php echo htmlspecialchars($project['ProjectName']); ?>')" class="icon-button" title="Xóa">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 delete-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
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
                        <p class="text-gray-500">Hãy thử thay đổi tiêu chí tìm kiếm hoặc tạo dự án mới</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete(projectId, projectName) {
            if (confirm(`Bạn có chắc chắn muốn xóa dự án "${projectName}" không?`)) {
                window.location.href = `Projects.php?action=delete&id=${projectId}`;
            }
        }

        // Hiển thị thông báo trong 5 giây rồi ẩn đi
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
                alerts.forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        });
    </script>
</body>
</html>