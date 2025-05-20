<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";

check_role("ADMIN");

$user = [
    'username' => '',
    'email' => '',
    'password' => '',
    'fullname' => '',
    'role' => 'USER'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = [
        'username' => isset($_POST['username']) ? $_POST['username'] : '',
        'email' => isset($_POST['email']) ? $_POST['email'] : '',
        'password' => isset($_POST['password']) ? $_POST['password'] : '',
        'fullname' => isset($_POST['fullname']) ? $_POST['fullname'] : '',
        'role' => isset($_POST['role']) ? $_POST['role'] : 'USER'
    ];
    
    $errors = [];
    
    if (empty($user['username'])) {
        $errors['username'] = 'Username cannot be empty';
    } else {
        $stmt = $connect->prepare("SELECT UserID FROM Users WHERE Username = ?");
        $stmt->bind_param("s", $user['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['username'] = 'Username already exists';
        }
    }
    
    if (empty($user['email'])) {
        $errors['email'] = 'Email cannot be empty';
    } elseif (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        $stmt = $connect->prepare("SELECT UserID FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    if (empty($user['password'])) {
        $errors['password'] = 'Password cannot be empty';
    } elseif (strlen($user['password']) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if (empty($errors)) {
        // Hash password using MD5
        $hashedPassword = md5($user['password']);
        
        // Add new user
        $stmt = $connect->prepare("INSERT INTO Users (Username, Email, Password, FullName, Role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $user['username'], $user['email'], $hashedPassword, $user['fullname'], $user['role']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'User added successfully';
            header('Location: Users.php');
            exit;
        } else {
            $errors['general'] = 'An error occurred: ' . $connect->error;
        }
    }
}

$currentPage = "users";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CubeFlow - Add New User</title>
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
                        <h1 class="text-2xl font-semibold text-gray-900">Add New User</h1>
                    </div>
                    
                    <!-- General error notification -->
                    <?php if (isset($errors['general'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <span class="font-medium"><?= htmlspecialchars($errors['general']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add user form -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <form method="POST">
                            <!-- Username -->
                            <div class="mb-4">
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
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
                            
                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
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
                                    <p class="text-gray-500 text-sm mt-1">Password must be at least 6 characters (Note: Password will be hashed using MD5)</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Full name -->
                            <div class="mb-4">
                                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                                <input 
                                    type="text" 
                                    id="fullname" 
                                    name="fullname" 
                                    value="<?= isset($user['fullname']) ? htmlspecialchars($user['fullname']) : '' ?>" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                            
                            <!-- Role -->
                            <div class="mb-6">
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select 
                                    id="role" 
                                    name="role" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="USER" <?= $user['role'] === 'USER' ? 'selected' : '' ?>>User</option>
                                    <option value="ADMIN" <?= $user['role'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            
                            <!-- Submit button -->
                            <div class="flex justify-end">
                                <a href="Users.php" class="mr-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Add User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 