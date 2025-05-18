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

// Xử lý thông báo
$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);

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
        $_SESSION['error'] = "Không thể xóa dự án có nhiệm vụ! Vui lòng xóa tất cả nhiệm vụ trước.";
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
                $_SESSION['success'] = "Xóa dự án thành công!";
            } else {
                $_SESSION['error'] = "Lỗi khi xóa dự án: " . $connect->error;
            }
        } else {
            $_SESSION['error'] = "Lỗi khi xóa thành viên dự án: " . $connect->error;
        }
    }
    
    // Chuyển hướng để tránh gửi lại form khi refresh
    header("Location: Projects.php");
    exit();
}

// Xử lý sắp xếp và phân trang
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Xử lý sắp xếp
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortDirection = isset($_GET['order']) ? $_GET['order'] : 'desc';

// Ánh xạ tên cột hiển thị với tên cột trong database
$sortMapping = [
    'id' => 'p.ProjectID',
    'name' => 'p.ProjectName',
    'creator' => 'u.FullName',
    'members' => 'MemberCount',
    'tasks' => 'TaskCount'
];

// Đảm bảo cột sắp xếp hợp lệ
$sortColumnDB = isset($sortMapping[$sortColumn]) ? $sortMapping[$sortColumn] : 'p.ProjectID';
// Đảm bảo hướng sắp xếp hợp lệ
$sortDirectionDB = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';

// Xây dựng truy vấn cơ sở
$baseQuery = "SELECT p.*, u.Username, u.FullName, 
             (SELECT COUNT(*) FROM ProjectMembers WHERE ProjectID = p.ProjectID) as MemberCount,
             (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID) as TaskCount,
             (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) as CompletedTaskCount,
             (CASE 
                WHEN (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID) > 0 
                THEN ROUND(((SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) / 
                            (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID)) * 100)
                ELSE 0
             END) as Progress
             FROM Project p
             LEFT JOIN Users u ON p.CreatedBy = u.UserID";

// Đếm tổng số lượng dự án
$countQuery = "SELECT COUNT(*) as total FROM Project p LEFT JOIN Users u ON p.CreatedBy = u.UserID WHERE 1=1";

// Tìm kiếm và lọc
$whereConditions = [];
$params = [];
$types = "";

// Xử lý tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $whereConditions[] = "(p.ProjectName LIKE ? OR p.ProjectDescription LIKE ? OR u.FullName LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

// Hoàn thiện câu truy vấn
if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $baseQuery .= $whereClause;
    $countQuery .= $whereClause;
}

// Lấy tổng số dự án
$stmt = $connect->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalProjects = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalProjects / $perPage);

// Sắp xếp và phân trang
$baseQuery .= " ORDER BY $sortColumnDB $sortDirectionDB LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

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

$currentPage = "projects";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CubeFlow - Quản lý dự án</title>
    <link rel="stylesheet" href="../../../public/css/tailwind.css">
    <link rel="stylesheet" href="../../../public/css/admin.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include "../components/Sidebar.php"; ?>
        
        <div class="flex-1 flex flex-col">
            <?php include "../components/Header.php"; ?>
            
            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-auto">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Quản lý dự án</h1>
                    
                    <!-- Thông báo -->
                    <?php if ($flashSuccess): ?>
                        <div id="successAlert" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium"><?= htmlspecialchars($flashSuccess) ?></span>
                            </div>
                            <div class="text-sm text-green-700">
                                Đóng sau <span id="successCountdown" class="font-medium">3</span>s
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($flashError): ?>
                        <div id="errorAlert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium"><?= htmlspecialchars($flashError) ?></span>
                            </div>
                            <div class="text-sm text-red-700">
                                Đóng sau <span id="errorCountdown" class="font-medium">3</span>s
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tìm kiếm và thêm dự án mới -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 flex-wrap">
                            <!-- Tìm kiếm -->
                            <form class="flex-1 flex items-center gap-2 min-w-0" method="GET">
                                <div class="relative flex-1 min-w-0">
                                    <input type="text" name="search" placeholder="Tìm kiếm dự án..." value="<?= htmlspecialchars($search) ?>"
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
                            
                            <!-- Thêm dự án mới -->
                            <a href="AddProject.php" class="h-10 bg-green-600 text-white px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center whitespace-nowrap text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Thêm dự án mới
                            </a>
                        </div>
                        
                        <!-- Bảng dự án -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200" id="projectTable">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="id">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="name">Tên dự án</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="creator">Người tạo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiến độ</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="members">Thành viên</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php $rowNumber = $offset + 1; // Khởi tạo biến đánh số thứ tự ?>
                                    <?php foreach ($projects as $project): ?>
                                        <?php 
                                        // Tính toán phần trăm hoàn thành
                                        $progress = 0;
                                        if ($project['TaskCount'] > 0) {
                                            $progress = round(($project['CompletedTaskCount'] / $project['TaskCount']) * 100);
                                        }
                                        ?>
                                        <tr class="hover-row">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $project['ProjectID'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($project['ProjectName']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($project['FullName']) ?></td>
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $project['MemberCount'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php 
                                                $startDate = date('d/m/Y', strtotime($project['StartDate']));
                                                $endDate = date('d/m/Y', strtotime($project['EndDate']));
                                                echo "$startDate - $endDate"; 
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="ViewProject.php?id=<?= $project['ProjectID'] ?>" class="text-indigo-600 hover:text-indigo-900 flex items-center" title="Xem">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="ml-1">Xem</span>
                                                    </a>
                                                    <a href="EditProject.php?id=<?= $project['ProjectID'] ?>" class="text-indigo-600 hover:text-indigo-900 flex items-center" title="Sửa">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                        <span class="ml-1">Sửa</span>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-red-600 hover:text-red-900 flex items-center" onclick="showDeleteConfirm(<?= $project['ProjectID'] ?>, '<?= htmlspecialchars($project['ProjectName']) ?>')" title="Xóa">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="ml-1">Xóa</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($projects) === 0): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Không tìm thấy dự án nào</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Phân trang -->
                        <?php if ($totalPages > 1): ?>
                            <div class="flex justify-between items-center mt-6">
                                <div class="text-sm text-gray-700">
                                    Hiển thị <?= $offset + 1 ?> đến <?= min($offset + $perPage, $totalProjects) ?> của <?= $totalProjects ?> dự án
                                </div>
                                <div class="flex space-x-1">
                                    <?php 
                                    // Xây dựng chuỗi query từ các tham số
                                    $queryParams = [];
                                    if (!empty($search)) $queryParams[] = 'search=' . urlencode($search);
                                    if (!empty($sortColumn)) $queryParams[] = 'sort=' . urlencode($sortColumn);
                                    if (!empty($sortDirection)) $queryParams[] = 'order=' . urlencode($sortDirection);
                                    $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                                    ?>
                                    
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?= $page - 1 ?><?= $queryString ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Trước</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <a href="?page=<?= $i ?><?= $queryString ?>" class="px-4 py-2 <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' ?> rounded-md">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?= $page + 1 ?><?= $queryString ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Tiếp</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal xác nhận xóa dự án -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"
    style="background-color: rgba(0, 0, 0, 0.4);">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="mb-4 text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Xác nhận xóa dự án</h3>
                <p class="text-gray-600" id="deleteConfirmText">Bạn có chắc chắn muốn xóa dự án này không?</p>
            </div>
            <div class="flex justify-center space-x-3">
                <button id="cancelDelete" class="px-4 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-200 transition-colors duration-200 focus:outline-none">
                    Hủy
                </button>
                <a id="confirmDelete" href="#" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200 focus:outline-none">
                    Xóa
                </a>
            </div>
        </div>
    </div>
    
    <script src="../../../public/js/admin.js"></script>
</body>
</html>