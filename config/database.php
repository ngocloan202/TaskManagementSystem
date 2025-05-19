<?php
header("Content-Type: text/html; charset=utf-8");
$servername = "localhost";
$username = "root";
$password = "vertrigo";
$dbname = "TaskManagementSystem";
$connect = new mysqli($servername, $username, $password, $dbname);
$connect->set_charset("utf8");
if ($connect->connect_error) {
  die("Connection failed: " . $connect->connect_error);
  exit();
}

function check_role($required_role = null)
{
  if (!isset($_SESSION["user_id"])) {
    header("Location: /app/Views/auth/login.php");
    exit();
  }

  if (isset($_SESSION["last_activity"]) && time() - $_SESSION["last_activity"] > 1800) {
    session_unset();
    session_destroy();
    header(
      "Location: /app/Views/auth/login.php?error=" .
        urlencode("Phiên làm việc đã hết hạn, vui lòng đăng nhập lại")
    );
    exit();
  }

  if ($required_role !== null && $_SESSION["role"] !== $required_role) {
    header(
      "Location: /app/Views/dashboard/index.php?error=" .
        urlencode("Bạn không có quyền truy cập trang này")
    );
    exit();
  }

  $_SESSION["last_activity"] = time();
}
?>
