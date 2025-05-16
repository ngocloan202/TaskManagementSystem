<?php
session_start();

// chỉ chấp nhận phương thức POST và file upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["avatar"])) {
  $uploadDir = "../../public/images/";

  // Tạo thư mục nếu chưa có
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  // Kiểm tra định dạng file
  $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
  if (!in_array($ext, ["jpg", "jpeg", "png", "gif"])) {
    echo json_encode(["success" => false, "error" => "Định dạng file không được hỗ trợ"]);
    exit();
  }

  // Tạo tên file mới và upload
  $username = $_SESSION["username"] ?? "user";
  $timestamp = date("Ymd_His"); // Format: 20240315_143022
  $newName = "avatar_{$username}_{$timestamp}.{$ext}";
  $target = $uploadDir . $newName;

  if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target)) {
    $relativePath = "/public/images/" . $newName;
    $_SESSION["avatar"] = $relativePath;

    // trả về JSON để JS cập nhật view ngay
    header("Content-Type: application/json");
    echo json_encode(["success" => true, "avatar" => $relativePath]);
    exit();
  } else {
    echo json_encode(["success" => false, "error" => "Không thể lưu file"]);
  }
  exit();
}

// lỗi
echo json_encode(["success" => false, "error" => "Invalid request"]);
