<?php
    header("Content-Type: text/html; charset=utf-8");
    $servername = "localhost";
    $username = "root";
    $password = "vertrigo";
    $dbname = "TaskManagementSystem";
    $connect = mysqli_connect($servername, $username, $password, $dbname);
    mysqli_set_charset($connect, "utf8");
    if ($connect -> connect_error) {
        die("Connection failed: " . $connect -> connect_error);
        exit();
    }
?>
