<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Cube Flow</title>

    <link rel="stylesheet" href="../../../public/css/tailwind.css">
    <style>
        body {
            background-color: #E8E9FE;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

    </style>
</head>
<body>
    <div id="registerFormContainer" class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md relative">
        <!-- Logo at the top -->
        <div class="absolute -top-16 left-1/2 transform -translate-x-1/2">
            <div class="bg-white rounded-full p-3 shadow-md">
                <img src="/images/logo.png" alt="Cube Flow Logo" class="w-16 h-16">
            </div>
        </div>
        
        <h2 class="text-center text-3xl font-bold text-blue-500 mt-8 mb-6">Cube Flow</h2>
        
        <?php if (isset($_SESSION['register_errors']['general'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <p><?php echo $_SESSION['register_errors']['general']; ?></p>
            </div>
        <?php endif; ?>
        
        <form action="/register/process" method="POST">
            <div class="mb-4">
                <div class="bg-blue-100 rounded-lg p-3 flex items-center <?php echo isset($_SESSION['register_errors']['email']) ? 'border border-red-500' : ''; ?>">
                    <span class="text-gray-600 mr-2">✉️</span>
                    <input type="email" id="email" name="email" placeholder="Email" 
                        class="bg-transparent w-full focus:outline-none"
                        value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>"
                        required>
                </div>
                <?php if (isset($_SESSION['register_errors']['email'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $_SESSION['register_errors']['email']; ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <div class="bg-blue-100 rounded-lg p-3 flex items-center <?php echo isset($_SESSION['register_errors']['username']) ? 'border border-red-500' : ''; ?>">
                    <span class="text-gray-600 mr-2">👤</span>
                    <input type="text" id="username" name="username" placeholder="Tên người dùng" 
                        class="bg-transparent w-full focus:outline-none"
                        value="<?php echo isset($_SESSION['form_data']['username']) ? htmlspecialchars($_SESSION['form_data']['username']) : ''; ?>"
                        required>
                </div>
                <?php if (isset($_SESSION['register_errors']['username'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $_SESSION['register_errors']['username']; ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <div class="bg-blue-100 rounded-lg p-3 flex items-center <?php echo isset($_SESSION['register_errors']['password']) ? 'border border-red-500' : ''; ?>">
                    <span class="text-gray-600 mr-2">🔑</span>
                    <input type="password" id="password" name="password" placeholder="Mật khẩu" 
                        class="bg-transparent w-full focus:outline-none" required>
                </div>
                <?php if (isset($_SESSION['register_errors']['password'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $_SESSION['register_errors']['password']; ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <div class="bg-blue-100 rounded-lg p-3 flex items-center <?php echo isset($_SESSION['register_errors']['confirm_password']) ? 'border border-red-500' : ''; ?>">
                    <span class="text-gray-600 mr-2">🔒</span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" 
                        class="bg-transparent w-full focus:outline-none" required>
                </div>
                <?php if (isset($_SESSION['register_errors']['confirm_password'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $_SESSION['register_errors']['confirm_password']; ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                Đăng ký
            </button>
            
            <p class="text-center mt-4">
                Bạn có tài khoản? <a href="/login" class="text-blue-500 hover:underline">Đăng nhập tại đây</a>
            </p>
        </form>
    </div>

    <?php
    // Clear session variables after displaying them
    if (isset($_SESSION['register_errors'])) {
        unset($_SESSION['register_errors']);
    }
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }
    ?>
</body>
</html>