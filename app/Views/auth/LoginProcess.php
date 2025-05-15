<?php
session_start();
include_once "../../../config/database.php";

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== session_id()) {
    header("Location: login.php?error=" . urlencode("Lỗi bảo mật, vui lòng thử lại"));
    exit();
}

$username = trim($_POST["username"] ?? "");
$password = trim($_POST["password"] ?? "");

if (empty($username) || empty($password)) {
    header("Location: login.php?error=" . urlencode("Vui lòng nhập tài khoản và mật khẩu"));
    exit();
}

$sql = $connect->prepare("SELECT * FROM users WHERE username = ?");
$sql->bind_param("s", $username);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows == 0) {
    error_log("Failed login attempt for username: $username from IP: " . $_SERVER['REMOTE_ADDR']);
    
    header("Location: login.php?error=" . urlencode("Tên đăng nhập hoặc mật khẩu không chính xác"));
    exit();
}

$user = $result->fetch_assoc();

$password_type = $user['password_type'] ?? 'md5'; 

if ($password_type === 'bcrypt') {
    if (password_verify($password, $user['password'])) {
        login_success($user);
    } else {
        login_failed($username);
    }
} else {
    if (md5($password) === $user['password']) {
        $secure_hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $connect->prepare("UPDATE users SET password = ?, password_type = 'bcrypt' WHERE username = ?");
        $update->bind_param("ss", $secure_hash, $username);
        $update->execute();
        
        login_success($user);
    } else {
        login_failed($username);
    }
}

function login_success($user) {
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['last_activity'] = time();
    
    error_log("Successful login for user: {$user['username']} from IP: " . $_SERVER['REMOTE_ADDR']);
    
    header("Location: ../dashboard/HomePage.php");
    exit();
}

function login_failed($username) {
    error_log("Failed login attempt (wrong password) for username: $username from IP: " . $_SERVER['REMOTE_ADDR']);
    
    header("Location: login.php?error=" . urlencode("Tên đăng nhập hoặc mật khẩu không chính xác"));
    exit();
}
?>