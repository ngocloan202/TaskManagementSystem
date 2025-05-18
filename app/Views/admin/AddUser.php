<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Kiểm tra quyền admin
check_role("ADMIN");

// Khởi tạo biến để lưu thông tin người dùng
$user = [
    'username' => '',
    'email' => '',
    'password' => '',
    'fullname' => '',
    'role' => 'USER'
];

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $user = [
        'username' => isset($_POST['username']) ? $_POST['username'] : '',
        'email' => isset($_POST['email']) ? $_POST['email'] : '',
        'password' => isset($_POST['password']) ? $_POST['password'] : '',
        'fullname' => isset($_POST['fullname']) ? $_POST['fullname'] : '',
        'role' => isset($_POST['role']) ? $_POST['role'] : 'USER'
    ];
    
    // Validate dữ liệu
    $errors = [];
    
    // Kiểm tra username
    if (empty($user['username'])) {
        $errors['username'] = 'Tên người dùng không được để trống';
    } else {
        // Kiểm tra username đã tồn tại chưa
        $stmt = $connect->prepare("SELECT UserID FROM Users WHERE Username = ?");
        $stmt->bind_param("s", $user['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['username'] = 'Tên người dùng đã tồn tại';
        }
    }
    
    // Kiểm tra email
    if (empty($user['email'])) {
        $errors['email'] = 'Email không được để trống';
    } elseif (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ';
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $connect->prepare("SELECT UserID FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Email đã tồn tại';
        }
    }
    
    // Kiểm tra password
    if (empty($user['password'])) {
        $errors['password'] = 'Mật khẩu không được để trống';
    } elseif (strlen($user['password']) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
    }
    
    // Nếu không có lỗi, thêm người dùng mới
    if (empty($errors)) {
        // Mã hóa mật khẩu bằng MD5
        $hashedPassword = md5($user['password']);
        
        // Thêm người dùng mới
        $stmt = $connect->prepare("INSERT INTO Users (Username, Email, Password, FullName, Role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $user['username'], $user['email'], $hashedPassword, $user['fullname'], $user['role']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Thêm người dùng thành công';
            header('Location: Users.php');
            exit;
        } else {
            $errors['general'] = 'Có lỗi xảy ra: ' . $connect->error;
        }
    }
}

$currentPage = "users";
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CubeFlow - Thêm người dùng mới</title>
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
            <main class="flex-1 p-6 overflow-auto">
                <div class="max-w-3xl mx-auto">
                    <div class="flex items-center mb-6">
                        <a href="Users.php" class="text-indigo-600 hover:text-indigo-800 mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                        <h1 class="text-2xl font-semibold text-gray-900">Thêm người dùng mới</h1>
                    </div>
                    
                    <!-- Thông báo lỗi chung -->
                    <?php if (isset($errors['general'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <span class="font-medium"><?= htmlspecialchars($errors['general']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form thêm người dùng -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <form method="POST">
                            <!-- Tên người dùng -->
                            <div class="mb-4">
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Tên người dùng <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    value="<?= isset($user['username']) ? htmlspecialchars($user['username']) : '' ?>" 
                                    required 
                                    class="w-full px-3 py-2 border <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                <?php if (isset($errors['username'])): ?>
                                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['username']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?= isset($user['email']) ? htmlspecialchars($user['email']) : '' ?>" 
                                    required 
                                    class="w-full px-3 py-2 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['email']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Mật khẩu -->
                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu <span class="text-red-500">*</span></label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    class="w-full px-3 py-2 border <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                <?php if (isset($errors['password'])): ?>
                                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['password']) ?></p>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm mt-1">Mật khẩu phải có ít nhất 6 ký tự (Lưu ý: Mật khẩu sẽ được mã hóa bằng MD5)</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Họ và tên -->
                            <div class="mb-4">
                                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                                <input 
                                    type="text" 
                                    id="fullname" 
                                    name="fullname" 
                                    value="<?= isset($user['fullname']) ? htmlspecialchars($user['fullname']) : '' ?>" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                            
                            <!-- Quyền -->
                            <div class="mb-6">
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Quyền</label>
                                <select 
                                    id="role" 
                                    name="role" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="USER" <?= isset($user['role']) && $user['role'] === 'USER' ? 'selected' : '' ?>>User</option>
                                    <option value="ADMIN" <?= isset($user['role']) && $user['role'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            
                            <!-- Nút submit -->
                            <div class="flex justify-end">
                                <a href="Users.php" class="mr-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Hủy</a>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Thêm người dùng</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 