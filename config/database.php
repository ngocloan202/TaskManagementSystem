<?php
header("Content-Type: text/html; charset=utf-8");
$servername = "localhost";
$username = "root";
$password = "vertrigo";
$dbname = "TaskManagementSystem";
$connect = mysqli_connect($servername, $username, $password, $dbname);
mysqli_set_charset($connect, "utf8");
if ($connect->connect_error) {
  die("Connection failed: " . $connect->connect_error);
  exit();
}
function clean_input($data) {
  global $connect;
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $connect->real_escape_string($data);
}

function check_session() {
  if (!isset($_SESSION['user_id'])) {
      header("Location: /app/Views/auth/login.php");
      exit();
  }
  
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
      session_unset();
      session_destroy();
      header("Location: /app/Views/auth/login.php?error=" . urlencode("Phiên làm việc đã hết hạn, vui lòng đăng nhập lại"));
      exit();
  }
  
  $_SESSION['last_activity'] = time();
}
?>