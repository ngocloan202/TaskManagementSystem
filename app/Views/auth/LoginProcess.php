<?php
include_once "../../../config/SessionInit.php";

include_once "../../../config/database.php";
$message = "";

if (!$connect) {
  die("Không thể kết nối đến cơ sở dữ liệu: " . $connect->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = $_POST["password"] ?? "";

  if ($username === "" || $password === "") {
    $message = "Vui lòng điền đầy đủ thông tin!";
  } else {
    $sql = "SELECT * FROM Users WHERE Username='$username'";
    $result = $connect->query($sql);

    if (!$result || $result->num_rows === 0) {
      $message = "Tên đăng nhập hoặc mật khẩu không đúng!";
    } else {
      $user = $result->fetch_assoc();
      if (md5($password) === $user["Password"]) {
        $_SESSION["user_id"] = $user["UserID"];
        $_SESSION["username"] = $user["Username"];
        $_SESSION["role"] = $user["Role"] ?? "USER"; // Default to USER if role not set
        $_SESSION["fullname"] = $user["FullName"];
        $_SESSION["avatar"] = $user["Avatar"] ?? "/public/images/default-avatar.png";
        $_SESSION["last_activity"] = time();
        $_SESSION["success"] = "🎉 Đăng nhập thành công!";

        if ($_SESSION["role"] === "ADMIN") {
          header("Location: ../admin/dashboard.php");
        } else {
          header("Location: LoginSuccess.php");
        }
        exit();
      } else {
        $message = "Tên đăng nhập hoặc mật khẩu không đúng!";
      }
    }
  }
}

if ($message !== "") {
  $_SESSION["login_error"] = $message;
  header("Location: login.php");
  exit();
}

?>
