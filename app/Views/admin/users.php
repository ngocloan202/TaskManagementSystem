<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

check_role("ADMIN");

$flashSuccess = $_SESSION["success"] ?? null;
$flashError = $_SESSION["error"] ?? null;
unset($_SESSION["success"], $_SESSION["error"]);

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $userId = $_GET['id'];
    // Don't allow deleting own account
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = "Không thể xóa tài khoản đang đăng nhập";
        header("Location: Users.php");
        exit;
    }
    
    $stmt = $connect->prepare("DELETE FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Đã xóa người dùng thành công";
    } else {
        $_SESSION['error'] = "Không thể xóa người dùng: " . $connect->error;
    }
    header("Location: Users.php");
    exit;
}

if (isset($_POST['change_role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];
    
    // Don't allow changing own role
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = "Không thể thay đổi quyền của tài khoản đang đăng nhập";
        header("Location: Users.php");
        exit;
    }
    
    $stmt = $connect->prepare("UPDATE Users SET Role = ? WHERE UserID = ?");
    $stmt->bind_param("si", $newRole, $userId);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Đã thay đổi quyền người dùng thành công";
    } else {
        $_SESSION['error'] = "Không thể thay đổi quyền người dùng: " . $connect->error;
    }
    header("Location: Users.php");
    exit;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortDirection = isset($_GET['order']) ? $_GET['order'] : 'asc';

$sortMapping = [
    'id' => 'UserID',
    'username' => 'Username',
    'email' => 'Email',
    'fullname' => 'FullName',
    'role' => 'Role'
];

$sortColumnDB = isset($sortMapping[$sortColumn]) ? $sortMapping[$sortColumn] : 'UserID';
$sortDirectionDB = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';

$countQuery = "SELECT COUNT(*) as total FROM Users WHERE 1=1";
$usersQuery = "SELECT * FROM Users WHERE 1=1";

if (!empty($search)) {
    $searchTerm = "%$search%";
    $countQuery .= " AND (Username LIKE ? OR Email LIKE ? OR FullName LIKE ?)";
    $usersQuery .= " AND (Username LIKE ? OR Email LIKE ? OR FullName LIKE ?)";
}

// Use backticks to avoid errors with special column names
$usersQuery .= " ORDER BY `$sortColumnDB` $sortDirectionDB LIMIT ? OFFSET ?";

// Get total users
$stmt = $connect->prepare($countQuery);
if (!empty($search)) {
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}
$stmt->execute();
$totalUsers = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $perPage);

$stmt = $connect->prepare($usersQuery);
if ($stmt === false) {
    // Xử lý lỗi khi chuẩn bị câu truy vấn
    $_SESSION['error'] = "Lỗi SQL: " . $connect->error;
    header("Location: Users.php");
    exit;
}

if (!empty($search)) {
    $stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $perPage, $offset);
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$users = $stmt->get_result();

$currentPage = "users";
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CubeFlow - Quản lý người dùng</title>
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
                    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Quản lý người dùng</h1>
                    
                    <!-- Notification -->
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
                    
                    <!-- Search and add new user -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 flex-wrap">
                            <!-- Search -->
                            <form class="flex-1 flex items-center gap-2 min-w-0" method="GET">
                                <div class="relative flex-1 min-w-0">
                                    <input type="text" name="search" placeholder="Tìm kiếm người dùng..." value="<?= htmlspecialchars($search) ?>"
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
                            
                            <!-- Add new user -->
                            <a href="AddUser.php" class="h-10 bg-green-600 text-white px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center whitespace-nowrap text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Thêm người dùng mới
                            </a>
                        </div>
                        
                        <!-- User table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200" id="userTable">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="id">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="username">Tên người dùng</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="email">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="fullname">Họ và tên</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="role">Quyền</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php $rowNumber = $offset + 1; // Khởi tạo biến đánh số thứ tự ?>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <tr class="hover-row">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $rowNumber++ ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['Username']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['Email']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($user['FullName'] ?? 'N/A') ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($user['UserID'] != $_SESSION['user_id']): ?>
                                                    <form method="post" class="inline-block">
                                                        <input type="hidden" name="user_id" value="<?= $user['UserID'] ?>">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <select name="new_role" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md p-1 <?= $user['Role'] === 'ADMIN' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700' ?>">
                                                            <option value="USER" <?= $user['Role'] === 'USER' ? 'selected' : '' ?>>User</option>
                                                            <option value="ADMIN" <?= $user['Role'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                                                        </select>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['Role'] === 'ADMIN' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                                                        <?= $user['Role'] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="EditUser.php?id=<?= $user['UserID'] ?>" class="text-indigo-600 hover:text-indigo-900 flex items-center" title="Sửa">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                        <span class="ml-1">Sửa</span>
                                                    </a>
                                                    <?php if ($user['UserID'] != $_SESSION['user_id']): ?>
                                                        <a href="javascript:void(0)" class="text-red-600 hover:text-red-900 flex items-center" onclick="showDeleteConfirm(<?= $user['UserID'] ?>, '<?= htmlspecialchars($user['Username']) ?>')" title="Xóa">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                            </svg>
                                                            <span class="ml-1">Xóa</span>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if ($users->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Không tìm thấy người dùng nào</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="flex justify-between items-center mt-6">
                                <div class="text-sm text-gray-700">
                                    Hiển thị <?= $offset + 1 ?> đến <?= min($offset + $perPage, $totalUsers) ?> của <?= $totalUsers ?> người dùng
                                </div>
                                <div class="flex space-x-1">
                                    <?php 
                                    // Build query string from parameters
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

    <!-- Modal add new user -->
    <div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Thêm người dùng mới</h3>
                <button id="closeAddModal" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="addUserForm" method="post" action="add_user_process.php">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Tên người dùng</label>
                    <input type="text" id="username" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                    <input type="text" id="fullname" name="fullname" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Quyền</label>
                    <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="USER">User</option>
                        <option value="ADMIN">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <a href="Users.php" class="mr-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Hủy</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Thêm</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal confirm user deletion -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"
    style="background-color: rgba(0, 0, 0, 0.4);">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="mb-4 text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Xác nhận xóa người dùng</h3>
                <p class="text-gray-600" id="deleteConfirmText">Bạn có chắc chắn muốn xóa người dùng này không?</p>
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
    
    <script>
        // Global variable to store timeout and interval
        let deleteModalTimeout;
        let countdownInterval;
        let successAlertTimeout;
        let errorAlertTimeout;
        let successCountdownInterval;
        let errorCountdownInterval;

        // Handle add user modal and confirm deletion
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('addUserBtn');
            const addModal = document.getElementById('addUserModal');
            const closeAddModal = document.getElementById('closeAddModal');
            const cancelAdd = document.getElementById('cancelAdd');
            
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    addModal.classList.remove('hidden');
                });
            }
            
            if (closeAddModal) {
                closeAddModal.addEventListener('click', function() {
                    addModal.classList.add('hidden');
                });
            }
            
            if (cancelAdd) {
                cancelAdd.addEventListener('click', function() {
                    addModal.classList.add('hidden');
                });
            }
            
            const deleteModal = document.getElementById('deleteConfirmModal');
            const cancelDelete = document.getElementById('cancelDelete');
            const confirmDelete = document.getElementById('confirmDelete');
            
            if (cancelDelete) {
                cancelDelete.addEventListener('click', function() {
                    clearTimeout(deleteModalTimeout);
                    clearInterval(countdownInterval);
                    deleteModal.classList.add('hidden');
                });
            }
            
            deleteModal.addEventListener('mouseenter', function() {
                clearTimeout(deleteModalTimeout);
                clearInterval(countdownInterval);
            });
            
            if (confirmDelete) {
                confirmDelete.addEventListener('click', function() {
                    clearTimeout(deleteModalTimeout);
                    clearInterval(countdownInterval);
                });
            }
            
            // Handle success notification auto close
            const successAlert = document.getElementById('successAlert');
            if (successAlert) {
                const successCountdown = document.getElementById('successCountdown');
                let secondsLeftSuccess = 3;
                
                successCountdownInterval = setInterval(function() {
                    secondsLeftSuccess -= 1;
                    successCountdown.textContent = secondsLeftSuccess;
                    
                    if (secondsLeftSuccess <= 0) {
                        clearInterval(successCountdownInterval);
                    }
                }, 1000);
                
                successAlertTimeout = setTimeout(function() {
                    successAlert.style.display = 'none';
                    clearInterval(successCountdownInterval);
                }, 3000);
                
                // Temporarily pause countdown when mouse enters notification
                successAlert.addEventListener('mouseenter', function() {
                    clearTimeout(successAlertTimeout);
                    clearInterval(successCountdownInterval);
                    successCountdown.textContent = "dừng";
                });
                
                // Resume countdown when mouse leaves notification
                successAlert.addEventListener('mouseleave', function() {
                    if (successCountdown.textContent === "dừng") {
                        secondsLeftSuccess = 3;
                        successCountdown.textContent = secondsLeftSuccess;
                        
                        successCountdownInterval = setInterval(function() {
                            secondsLeftSuccess -= 1;
                            successCountdown.textContent = secondsLeftSuccess;
                            
                            if (secondsLeftSuccess <= 0) {
                                clearInterval(successCountdownInterval);
                            }
                        }, 1000);
                        
                        successAlertTimeout = setTimeout(function() {
                            successAlert.style.display = 'none';
                            clearInterval(successCountdownInterval);
                        }, 3000);
                    }
                });
            }
            
            const errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                const errorCountdown = document.getElementById('errorCountdown');
                let secondsLeftError = 3;
                
                errorCountdownInterval = setInterval(function() {
                    secondsLeftError -= 1;
                    errorCountdown.textContent = secondsLeftError;
                    
                    if (secondsLeftError <= 0) {
                        clearInterval(errorCountdownInterval);
                    }
                }, 1000);
                
                errorAlertTimeout = setTimeout(function() {
                    errorAlert.style.display = 'none';
                    clearInterval(errorCountdownInterval);
                }, 3000);
                
                errorAlert.addEventListener('mouseenter', function() {
                    clearTimeout(errorAlertTimeout);
                    clearInterval(errorCountdownInterval);
                    errorCountdown.textContent = "dừng";
                });
                
                errorAlert.addEventListener('mouseleave', function() {
                    if (errorCountdown.textContent === "dừng") {
                        secondsLeftError = 3;
                        errorCountdown.textContent = secondsLeftError;
                        
                        errorCountdownInterval = setInterval(function() {
                            secondsLeftError -= 1;
                            errorCountdown.textContent = secondsLeftError;
                            
                            if (secondsLeftError <= 0) {
                                clearInterval(errorCountdownInterval);
                            }
                        }, 1000);
                        
                        errorAlertTimeout = setTimeout(function() {
                            errorAlert.style.display = 'none';
                            clearInterval(errorCountdownInterval);
                        }, 3000);
                    }
                });
            }
        });

        // Function to show delete confirmation modal
        function showDeleteConfirm(userId, username) {
            const modal = document.getElementById('deleteConfirmModal');
            const confirmText = document.getElementById('deleteConfirmText');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            
            if (deleteModalTimeout) {
                clearTimeout(deleteModalTimeout);
            }
            
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            
            confirmText.textContent = `Bạn có chắc chắn muốn xóa người dùng "${username}" không?`;
            confirmDeleteBtn.href = `Users.php?action=delete&id=${userId}`;

            modal.classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('userTable');
            const headers = table.querySelectorAll('th.sortable');
            
            let currentSort = {
                column: 'id',
                direction: 'asc'
            };
            
            headers.forEach(header => {
                header.addEventListener('click', function() {
                    const column = this.getAttribute('data-sort');
                    
                    // Determine sorting direction
                    const direction = 
                        column === currentSort.column && currentSort.direction === 'asc' ? 'desc' : 'asc';
                    
                    headers.forEach(h => {
                        h.classList.remove('asc', 'desc');
                    });
                    this.classList.add(direction);
                    
                    currentSort.column = column;
                    currentSort.direction = direction;
                    
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('sort', column);
                    urlParams.set('order', direction);
                    
                    window.location.search = urlParams.toString();
                });
            });
            
            // Mark sorted column
            const urlParams = new URLSearchParams(window.location.search);
            const sortColumn = urlParams.get('sort') || 'id';
            const sortDirection = urlParams.get('order') || 'asc';
            
            const sortHeader = document.querySelector(`th[data-sort="${sortColumn}"]`);
            if (sortHeader) {
                sortHeader.classList.add(sortDirection);
                currentSort.column = sortColumn;
                currentSort.direction = sortDirection;
            }
        });
    </script>
</body>
</html>
