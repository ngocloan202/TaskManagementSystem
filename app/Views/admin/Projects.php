<?php
// Projects.php - Improved data handling
require_once '../../../config/SessionInit.php';
require_once '../../../config/database.php';
check_role("ADMIN");  // Only ADMIN can access

// Prepare query to get project list with creator name and member/task counts
$projectsQuery = "
  SELECT 
    p.ProjectID, 
    p.ProjectName, 
    p.ProjectDescription,
    DATE_FORMAT(p.StartDate, '%d/%m/%Y') AS StartDate,
    DATE_FORMAT(p.EndDate, '%d/%m/%Y')   AS EndDate,
    u.FullName       AS Creator,
    (SELECT COUNT(*) FROM ProjectMembers pm WHERE pm.ProjectID = p.ProjectID) AS MemberCount,
    (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID)             AS TaskCount,
    (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) AS CompletedCount
  FROM Project p
  LEFT JOIN Users u ON p.CreatedBy = u.UserID
  ORDER BY p.ProjectID DESC
";

$projectsResult = $connect->query($projectsQuery);
$projects = [];
if ($projectsResult) {
    while ($row = $projectsResult->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Handle notifications
$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);

// Handle sorting and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Handle sorting
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortDirection = isset($_GET['order']) ? $_GET['order'] : 'desc';

// Map display column names to database column names
$sortMapping = [
    'id' => 'p.ProjectID',
    'name' => 'p.ProjectName',
    'creator' => 'Creator',
    'members' => 'MemberCount',
    'tasks' => 'TaskCount'
];

// Ensure valid sort column
$sortColumnDB = isset($sortMapping[$sortColumn]) ? $sortMapping[$sortColumn] : 'p.ProjectID';
// Ensure valid sort direction
$sortDirectionDB = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';

// Build search query
if (!empty($search)) {
    // Add search conditions to query
    $sql = "
      SELECT 
        p.ProjectID, 
        p.ProjectName, 
        p.ProjectDescription,
        DATE_FORMAT(p.StartDate, '%d/%m/%Y') AS StartDate,
        DATE_FORMAT(p.EndDate, '%d/%m/%Y')   AS EndDate,
        u.FullName       AS Creator,
        (SELECT COUNT(*) FROM ProjectMembers pm WHERE pm.ProjectID = p.ProjectID) AS MemberCount,
        (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID)             AS TaskCount,
        (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) AS CompletedCount
      FROM Project p
      LEFT JOIN Users u ON p.CreatedBy = u.UserID
      WHERE p.ProjectName LIKE '%$search%' OR p.ProjectDescription LIKE '%$search%' OR u.FullName LIKE '%$search%'
      ORDER BY $sortColumnDB $sortDirectionDB
      LIMIT $perPage OFFSET $offset
    ";
    
    // Query to count total projects matching search criteria
    $countQuery = "
      SELECT COUNT(*) as total 
      FROM Project p
      LEFT JOIN Users u ON p.CreatedBy = u.UserID
      WHERE p.ProjectName LIKE '%$search%' OR p.ProjectDescription LIKE '%$search%' OR u.FullName LIKE '%$search%'
    ";
    
    $countResult = $connect->query($countQuery);
    $totalProjects = $countResult->fetch_assoc()['total'];
} else {
    // Sort and paginate initial results
    $sql = "
      SELECT 
        p.ProjectID, 
        p.ProjectName, 
        p.ProjectDescription,
        DATE_FORMAT(p.StartDate, '%d/%m/%Y') AS StartDate,
        DATE_FORMAT(p.EndDate, '%d/%m/%Y')   AS EndDate,
        u.FullName       AS Creator,
        (SELECT COUNT(*) FROM ProjectMembers pm WHERE pm.ProjectID = p.ProjectID) AS MemberCount,
        (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID)             AS TaskCount,
        (SELECT COUNT(*) FROM Task WHERE ProjectID = p.ProjectID AND TaskStatusID = 3) AS CompletedCount
      FROM Project p
      LEFT JOIN Users u ON p.CreatedBy = u.UserID
      ORDER BY $sortColumnDB $sortDirectionDB
      LIMIT $perPage OFFSET $offset
    ";
    
    // Count total projects
    $countQuery = "SELECT COUNT(*) as total FROM Project";
    $countResult = $connect->query($countQuery);
    $totalProjects = $countResult->fetch_assoc()['total'];
}

// Execute query
$result = $connect->query($sql);
$projects = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

$totalPages = ceil($totalProjects / $perPage);
$currentPage = "projects";

// Handle project view event
if (isset($_GET['view']) && isset($_GET['id'])) {
    $projectId = (int)$_GET['id'];
    $adminId = $_SESSION['user_id'] ?? 0;
    $viewTime = date('Y-m-d H:i:s');
    
    // Create reference URL with current parameters (except view and id)
    $refererParams = [];
    foreach ($_GET as $key => $value) {
        if ($key !== 'view' && $key !== 'id') {
            $refererParams[$key] = $value;
        }
    }
    // Store parameters in session to return to correct page state
    $_SESSION['project_view_referer'] = [
        'params' => $refererParams,
        'time' => time()
    ];
    
    // Log project view event
    $logQuery = "INSERT INTO ActivityLog (UserID, ActivityType, RelatedID, ActivityTime, Details) 
                VALUES (?, 'view_project', ?, ?, ?)";
    
    $stmt = $connect->prepare($logQuery);
    if ($stmt) {
        $details = json_encode(['from' => 'admin_panel', 'ip' => $_SERVER['REMOTE_ADDR']]);
        $stmt->bind_param('iiss', $adminId, $projectId, $viewTime, $details);
        $stmt->execute();
    }
    
    // Redirect to project detail page
    header("Location: ../dashboard/ProjectDetail.php?id=" . $projectId);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CubeFlow - Project Management</title>
    <link rel="stylesheet" href="../../../public/css/tailwind.css">
    <link rel="stylesheet" href="../../../public/css/admin.css">
    <style>
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
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Temporarily store these variables to avoid overwriting -->
        <?php 
        $temp_projects = $projects;
        $temp_totalProjects = $totalProjects;
        $temp_totalPages = $totalPages;
        $temp_flashSuccess = $flashSuccess;
        $temp_flashError = $flashError;
        $temp_connect = $connect;
        ?>
        
        <?php include "../components/Sidebar.php"; ?>
        
        <?php 
        // Khôi phục các biến sau khi include
        $projects = $temp_projects;
        $totalProjects = $temp_totalProjects;
        $totalPages = $temp_totalPages;
        $flashSuccess = $temp_flashSuccess;
        $flashError = $temp_flashError;
        $connect = $temp_connect;
        ?>
        
        <div class="flex-1 flex flex-col">
            <?php include "../components/Header.php"; ?>
            
            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-auto">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Project Management</h1>
                    
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
                                    <?php if (count($projects) > 0): ?>
                                        <?php foreach ($projects as $project): ?>
                                            <?php 
                                            // Tính toán phần trăm hoàn thành
                                            $progress = 0;
                                            if ($project['TaskCount'] > 0) {
                                                $progress = round(($project['CompletedCount'] / $project['TaskCount']) * 100);
                                            }
                                            ?>
                                            <tr class="hover-row">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $project['ProjectID'] ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($project['ProjectName']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($project['Creator']) ?></td>
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
                                                    <?= $project['StartDate'] ?> - <?= $project['EndDate'] ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex items-center space-x-2">
                                                        <a href="?view=1&id=<?= $project['ProjectID'] ?>" class="text-indigo-600 hover:text-indigo-900 flex items-center" title="Xem">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                            </svg>
                                                            <span class="ml-1">Xem</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
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
    
    <script src="../../../public/js/admin.js"></script>
    <script>
        // Xử lý đóng cảnh báo sau 3 giây
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý thông báo thành công
            const successAlert = document.getElementById('successAlert');
            if (successAlert) {
                let seconds = 3;
                const countdown = document.getElementById('successCountdown');
                const timer = setInterval(function() {
                    seconds--;
                    countdown.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        successAlert.style.display = 'none';
                    }
                }, 1000);
            }
            
            // Xử lý thông báo lỗi
            const errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                let seconds = 3;
                const countdown = document.getElementById('errorCountdown');
                const timer = setInterval(function() {
                    seconds--;
                    countdown.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        errorAlert.style.display = 'none';
                    }
                }, 1000);
            }
        });
        
        // Xử lý sắp xếp khi click vào tiêu đề cột
        document.addEventListener('DOMContentLoaded', function() {
            const sortableHeaders = document.querySelectorAll('.sortable');
            
            sortableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const sort = this.getAttribute('data-sort');
                    const currentOrder = new URLSearchParams(window.location.search).get('order') || 'desc';
                    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                    
                    // Xây dựng URL mới với tham số sắp xếp
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort', sort);
                    url.searchParams.set('order', newOrder);
                    
                    // Chuyển hướng tới URL mới
                    window.location.href = url.toString();
                });
            });
        });
    </script>
</body>
</html>