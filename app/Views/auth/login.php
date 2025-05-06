<?php
session_start();

require __DIR__ . '/../../../config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    $error = 'Please enter both username and password.';
  } else {
    $stmt = $mysqli->prepare(
      'SELECT id, password_hash FROM users WHERE username = ?'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
      $stmt->bind_result($id, $hash);
      $stmt->fetch();

      if (password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $u;
        header('Location: dashboard.php');
        exit();
      }
    }

    $error = 'Invalid credentials.';
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Cube Flow – Login</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="min-h-screen bg-indigo-50 flex items-center justify-center">
  <div class="relative bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">
    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
      <img src="asset/images/logo.png"
           class="w-24 h-24 rounded-full border-4 border-white"
           alt="Logo">
    </div>

    <h2 class="mt-16 text-center text-2xl font-bold text-indigo-600 mb-6">
      Cube Flow
    </h2>

    <?php if ($error): ?>
      <div class="mb-4 text-red-600 text-center">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <!-- username -->
      <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-indigo-600">
          <!-- user icon SVG -->
        </span>
        <input name="username" type="text" placeholder="Tài khoản" required
               class="w-full pl-10 py-2 rounded-lg bg-indigo-100 placeholder-indigo-400
                      focus:outline-none focus:ring-2 focus:ring-indigo-300">
      </div>

      <!-- password -->
      <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-indigo-600">
          <!-- lock icon SVG -->
        </span>
        <input name="password" type="password" placeholder="Mật khẩu" required
               class="w-full pl-10 py-2 rounded-lg bg-indigo-100 placeholder-indigo-400
                      focus:outline-none focus:ring-2 focus:ring-indigo-300">
      </div>

      <!-- submit -->
      <button type="submit"
              class="w-full py-2 bg-indigo-500 hover:bg-indigo-600
                     text-white font-semibold rounded-lg">
        Đăng nhập
      </button>
    </form>

    <p class="mt-4 text-center text-sm text-indigo-500">
      <a href="#" class="hover:underline">Quên mật khẩu?</a>
    </p>
    <p class="mt-2 text-center text-sm text-indigo-500">
      Bạn chưa có tài khoản?
      <a href="#" class="font-medium text-indigo-600 hover:underline">
        Đăng ký tại đây
      </a>
    </p>
  </div>
</body>
</html>