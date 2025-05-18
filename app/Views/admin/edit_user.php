<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

// Kiểm tra quyền admin
check_role("ADMIN");

// Kiểm tra ID người dùng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID người dùng không hợp lệ";
    header('Location: users.php');
    exit;
}

$userId = (int)$_GET['id'];

// Lấy thông tin người dùng
$stmt = $connect->prepare("SELECT * FROM Users WHERE UserID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Không tìm thấy người dùng";
    header('Location: users.php');
    exit;
}

$user = $result->fetch_assoc();

// Nếu form chưa được submit, sử dụng dữ liệu từ database
$userData = [
    'username' => $user['Username'],
    'email' => $user['Email'],
    'fullname' => $user['FullName'] ?? '',
    'role' => $user['Role']
];

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $userData = [
        'username' => isset($_POST['username']) ? $_POST['username'] : '',
        'email' => isset($_POST['email']) ? $_POST['email'] : '',
        'newPassword' => isset($_POST['new_password']) ? $_POST['new_password'] : '',
        'fullname' => isset($_POST['fullname']) ? $_POST['fullname'] : '',
        'role' => isset($_POST['role']) ? $_POST['role'] : 'USER'
    ];
    
    // Validate dữ liệu
    $errors = [];
    
    // Kiểm tra username
    if (empty($userData['username'])) {
        $errors['username'] = 'Tên người dùng không được để trống';
    } else {
        // Kiểm tra username đã tồn tại chưa (trừ user hiện tại)
        $stmt = $connect->prepare("SELECT UserID FROM Users WHERE Username = ? AND UserID != ?");
        $stmt->bind_param("si", $userData['username'], $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['username'] = 'Tên người dùng đã tồn tại';
        }
    }
    
    // Kiểm tra email
    if (empty($userData['email'])) {
        $errors['email'] = 'Email không được để trống';
    } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ';
    } else {
        // Kiểm tra email đã tồn tại chưa (trừ user hiện tại)
        $stmt = $connect->prepare("SELECT UserID FROM Users WHERE Email = ? AND UserID != ?");
        $stmt->bind_param("si", $userData['email'], $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Email đã tồn tại';
        }
    }
    
    // Kiểm tra mật khẩu mới (nếu được cung cấp)
    if (!empty($userData['newPassword']) && strlen($userData['newPassword']) < 6) {
        $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    }
    
    // Nếu không có lỗi, cập nhật thông tin người dùng
    if (empty($errors)) {
        // Chuẩn bị câu lệnh SQL cơ bản (không thay đổi mật khẩu)
        $sql = "UPDATE Users SET Username = ?, Email = ?, FullName = ?";
        $params = [$userData['username'], $userData['email'], $userData['fullname']];
        $types = "sss";
        
        // Nếu là admin đang sửa người dùng khác, có thể thay đổi quyền
        if ($userId != $_SESSION['user_id']) {
            $sql .= ", Role = ?";
            $params[] = $userData['role'];
            $types .= "s";
        }
        
        // Nếu mật khẩu mới được cung cấp, thêm vào câu lệnh SQL
        if (!empty($userData['newPassword'])) {
            $hashedPassword = password_hash($userData['newPassword'], PASSWORD_DEFAULT);
            $sql .= ", Password = ?";
            $params[] = $hashedPassword;
            $types .= "s";
        }
        
        $sql .= " WHERE UserID = ?";
        $params[] = $userId;
        $types .= "i";
        
        // Cập nhật thông tin người dùng
        $stmt = $connect->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Cập nhật thông tin người dùng thành công';
            header('Location: users.php');
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
    <title>CubeFlow - Chỉnh sửa người dùng</title>
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
                        <a href="users.php" class="text-indigo-600 hover:text-indigo-800 mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                        <h1 class="text-2xl font-semibold text-gray-900">Chỉnh sửa người dùng</h1>
                    </div>
                    
                    <!-- Thông báo lỗi chung -->
                    <?php if (isset($errors['general'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <span class="font-medium"><?= htmlspecialchars($errors['general']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form chỉnh sửa người dùng -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <form method="POST">
                            <!-- Tên người dùng -->
                            <div class="mb-4">
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Tên người dùng <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    value="<?= htmlspecialchars($userData['username']) ?>" 
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
                                    value="<?= htmlspecialchars($userData['email']) ?>" 
                                    required 
                                    class="w-full px-3 py-2 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['email']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Mật khẩu mới (không bắt buộc) -->
                            <div class="mb-4">
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="w-full px-3 py-2 border <?= isset($errors['new_password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                <?php if (isset($errors['new_password'])): ?>
                                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['new_password']) ?></p>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm mt-1">Để trống nếu không muốn thay đổi mật khẩu</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Họ và tên -->
                            <div class="mb-4">
                                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                                <input 
                                    type="text" 
                                    id="fullname" 
                                    name="fullname" 
                                    value="<?= htmlspecialchars($userData['fullname']) ?>" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                            
                            <!-- Quyền (chỉ cho phép thay đổi nếu không phải chính mình) -->
                            <div class="mb-6">
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Quyền</label>
                                <select 
                                    id="role" 
                                    name="role" 
                                    <?= $userId == $_SESSION['user_id'] ? 'disabled' : '' ?>
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 <?= $userId == $_SESSION['user_id'] ? 'bg-gray-100' : '' ?>"
                                >
                                    <option value="USER" <?= $userData['role'] === 'USER' ? 'selected' : '' ?>>User</option>
                                    <option value="ADMIN" <?= $userData['role'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                
                                <?php if ($userId == $_SESSION['user_id']): ?>
                                    <p class="text-gray-500 text-sm mt-1">Bạn không thể thay đổi quyền của chính mình</p>
                                    <!-- Field ẩn để đảm bảo giá trị role vẫn được gửi khi form submit -->
                                    <input type="hidden" name="role" value="<?= htmlspecialchars($userData['role']) ?>">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Nút submit -->
                            <div class="flex justify-end">
                                <a href="users.php" class="mr-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Hủy</a>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 