<?php
require_once "../../../config/SessionInit.php";

// 1. Kết nối database
require_once __DIR__ . '../../../config/database.php';   

// 2. Kiểm tra session
$userId = (int) $_SESSION['user_id'];

// 3. Lấy thông tin cơ bản của user
$stmt = $connect->prepare("
    SELECT FullName, Email, PhoneNumber, Avatar
    FROM Users
    WHERE UserID = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Không tìm thấy thông tin người dùng.');
}
$user = $result->fetch_assoc();
$stmt->close();

// Đưa ra biến để dễ đổ vào HTML
$fullname     = $user['FullName'];
$email        = $user['Email'];
$phone        = $user['PhoneNumber'];
$avatar       = $user['Avatar'];

// 4. Đếm số dự án user tham gia (tuỳ chọn)
$stmt2 = $connect->prepare("
    SELECT COUNT(*) AS cnt
    FROM ProjectMembers
    WHERE UserID = ?
");
$stmt2->bind_param('i', $userId);
$stmt2->execute();
$res2 = $stmt2->get_result();
$row2 = $res2->fetch_assoc();
$projectCount = $row2['cnt'];
$stmt2->close();

$_SESSION['fullname'] = $fullname;
$_SESSION['email'] = $email;
$_SESSION['phone'] = $phone;
$_SESSION['avatar'] = $avatar;
$_SESSION['project_count'] = $projectCount;
