<?php
require_once "../../../config/SessionInit.php";
require_once __DIR__ . "../../../config/database.php";

$userId = (int) $_SESSION["user_id"];

$statement = $connect->prepare("
    SELECT FullName, Email, PhoneNumber, Avatar
    FROM Users
    WHERE UserID = ?
");
$statement->bind_param("i", $userId);
$statement->execute();
$result = $statement->get_result();
if ($result->num_rows === 0) {
  die("Không tìm thấy thông tin người dùng.");
}
$user = $result->fetch_assoc();
$statement->close();

// Đưa ra biến để dễ đổ vào HTML
$fullname = $user["FullName"];
$email = $user["Email"];
$phone = $user["PhoneNumber"];
$avatar = $user["Avatar"];

// 4. Đếm số dự án user tham gia (tuỳ chọn)
$statement2 = $connect->prepare("
    SELECT COUNT(*) AS cnt
    FROM ProjectMembers
    WHERE UserID = ?
");
$statement2->bind_param("i", $userId);
$statement2->execute();
$res2 = $statement2->get_result();
$row2 = $res2->fetch_assoc();
$projectCount = $row2["cnt"];
$statement2->close();

$_SESSION["fullname"] = $fullname;
$_SESSION["email"] = $email;
$_SESSION["phone"] = $phone;
$_SESSION["avatar"] = $avatar;
$_SESSION["project_count"] = $projectCount;
