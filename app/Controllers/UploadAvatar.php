<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["avatar"])) {
  $uploadDir = "../../public/images/";

  // Create directory if it doesn't exist
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }


  $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
  if (!in_array($ext, ["jpg", "jpeg", "png", "gif"])) {
    echo json_encode(["success" => false, "error" => "Định dạng file không được hỗ trợ"]);
    exit();
  }

  // Create new filename and upload
  $username = $_SESSION["username"] ?? "user";
  $timestamp = date("Ymd_His"); // Format: 20240315_143022
  $newName = "avatar_{$username}_{$timestamp}.{$ext}";
  $target = $uploadDir . $newName;

  if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target)) {
    $relativePath = "/public/images/" . $newName;
    $_SESSION["avatar"] = $relativePath;

    // return JSON for JS to update view immediately
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
