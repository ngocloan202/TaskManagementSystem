<?php

header('Content-Type: application/json');

$userId = $_SESSION['user_id']; // hoặc tên key session bạn đang dùng

// Lấy data từ POST
$fullname      = $_POST['fullname']      ?? '';
$email         = $_POST['email']         ?? '';
$phone         = $_POST['phone']         ?? '';
$projectCount  = $_POST['project_count'] ?? 0;
$avatarPath    = $_POST['avatar']        ?? $_SESSION['avatar'];

try {
    // Ví dụ table `users`, cột tương ứng
    $sql = "UPDATE users
            SET fullname       = :fullname,
                email          = :email,
                phone          = :phone,
                project_count  = :project_count,
                avatar         = :avatar
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':fullname'      => $fullname,
      ':email'         => $email,
      ':phone'         => $phone,
      ':project_count' => $projectCount,
      ':avatar'        => $avatarPath,
      ':id'            => $userId
    ]);

    // Cập nhật session ngay lập tức
    $_SESSION['fullname']      = $fullname;
    $_SESSION['email']         = $email;
    $_SESSION['phone']         = $phone;
    $_SESSION['project_count'] = $projectCount;
    $_SESSION['avatar']        = $avatarPath;

    echo json_encode([
      'success'  => true,
      'user'     => [
        'fullname'      => $fullname,
        'email'         => $email,
        'phone'         => $phone,
        'project_count' => $projectCount,
        'avatar'        => $avatarPath
      ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'error'   => $e->getMessage()
    ]);
}
