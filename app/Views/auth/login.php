<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cube Flow - Đăng nhập</title>
  <link href="../../css/tailwind.css" rel="stylesheet">
  <style>
    body {
      background-color: #EEF0FF;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .login-container {
      max-width: 400px;
      width: 90%;
    }

    .login-btn {
      background-color: #3B82F6;
      transition: all 0.3s ease;
    }

    .login-btn:hover {
      background-color: #2563EB;
    }

    .input-field {
      background-color: #B4BFEF;
      border: none;
      color: #333;
    }

    .input-field::placeholder {
      color: #4B5563;
    }

    .logo-circle {
      background-color: #EEF0FF;
      width: 100px;
      height: 100px;
      margin-top: -50px;
      border: 2px solid #E5E7FF;
    }
  </style>
</head>

<body class="h-screen flex items-center justify-center">
  <div class="login-container">
    <!-- Logo Circle -->
    <div class="flex justify-center">
      <div class="logo-circle rounded-full flex items-center justify-center shadow-md">
        <div class="text-center">
          <img src="../../images/logo.png" alt="Cube Flow" class="w-12 h-12 mx-auto">
          <span class="text-indigo-600 text-sm font-medium">Cube Flow</span>
        </div>
      </div>
    </div>

    <!-- Login Form Card -->
    <div class="bg-white rounded-lg shadow-lg p-8 mt-4">
      <h1 class="text-indigo-500 text-center text-2xl font-semibold mb-8">Cube Flow</h1>

      <form>
        <!-- Username Field -->
        <div class="mb-4">
          <div class="input-field rounded-md flex items-center px-3 py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <input type="text" placeholder="Tài khoản" class="bg-transparent w-full focus:outline-none">
          </div>
        </div>

        <!-- Password Field -->
        <div class="mb-6">
          <div class="input-field rounded-md flex items-center px-3 py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 mr-2" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <input type="password" placeholder="Mật khẩu" class="bg-transparent w-full focus:outline-none">
          </div>
        </div>

        <!-- Login Button -->
        <button type="submit" class="login-btn w-full py-2 rounded-md text-white font-medium">
          Đăng nhập
        </button>
      </form>

      <!-- Forgot Password -->
      <div class="text-center mt-4">
        <a href="#" class="text-indigo-600 text-sm hover:underline">Quên mật khẩu?</a>
      </div>

      <!-- Register Link -->
      <div class="text-center mt-4 text-sm text-gray-600">
        Bạn chưa có tài khoản?
        <a href="#" class="text-indigo-600 font-medium hover:underline">Đăng ký tại đây</a>
      </div>
    </div>
  </div>
</body>
</html>