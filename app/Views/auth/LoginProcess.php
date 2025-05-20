<?php
include_once "../../../config/SessionInit.php";

include_once "../../../config/database.php";
$message = "";

if (!$connect) {
  die("Could not connect to DB: " . $connect->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = $_POST["password"] ?? "";

  if ($username === "" || $password === "") {
    $message = "Please fill in all fields!";
  } else {
    // Query user (without prepare)
    $sql = "SELECT * FROM Users WHERE Username='$username'";
    $result = $connect->query($sql);

    if (!$result || $result->num_rows === 0) {
      $message = "Invalid username or password!";
    } else {
      $user = $result->fetch_assoc();
      // Check password hash (MD5)
      if (md5($password) === $user["Password"]) {
        // Login successful
        $_SESSION["user_id"] = $user["UserID"];
        $_SESSION["username"] = $user["Username"];
        $_SESSION["role"] = $user["Role"] ?? "USER"; // Default to USER if role not set
        $_SESSION["fullname"] = $user["FullName"];
        $_SESSION["avatar"] = $user["Avatar"] ?? "/public/images/default-avatar.png";
        $_SESSION["last_activity"] = time();
        $_SESSION["success"] = "🎉 Login successful!";

        // Redirect based on role
        if ($_SESSION["role"] === "ADMIN") {
          header("Location: ../admin/dashboard.php");
        } else {
          header("Location: LoginSuccess.php");
        }
        exit();
      } else {
        $message = "Invalid username or password!";
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
