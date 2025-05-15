<?php
    session_start();
    require __DIR__ . "/../config/database.php";

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=" . urlencode("Vui lòng nhập tài khoản và mật khẩu"));
        exit();
    }

    $sql = $connect->prepare("SELECT username, password FROM users WHERE username = ?");
    $sql->bind_param("s", $username);
    $sql->execute();
    $sql->store_result();

    if ($sql->num_rows == 0) {
        header("Location: login.php?error=" . urlencode("Tài khoản không tồn tại"));
        exit();
    }

    $sql->bind_result($username, $hashed);
    $sql->fetch();

    if (!password_verify($password, $hashed)) {
        header("Location: login.php?error=" . urlencode("Mật khẩu không chính xác"));
        exit();
    }

    $_SESSION['username'] = $username;

    header("Location: HomePage.php");
    exit();
?>