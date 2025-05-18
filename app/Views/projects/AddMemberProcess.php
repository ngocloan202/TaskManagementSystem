<?php
require_once __DIR__ . "/../../config/SessionInit.php";
require_once __DIR__ . "/../../config/database.php";

header("Content-Type: application/json");
session_start();

if (!isset($_SESSION["user_id"])) {
  echo json_encode([
    "status" => "error",
    "message" => "Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.",
  ]);
  exit();
}

if (!function_exists('clean_input')) {
  function clean_input($data)
  {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data
  $projectID = isset($_POST["projectId"]) ? (int) $_POST["projectId"] : 0;
  $userID = isset($_POST["userId"]) ? (int) $_POST["userId"] : 0;
  $roleId = isset($_POST["roleId"]) ? (int) $_POST["roleId"] : 2; // Default to member (2)
  $currentUserID = $_SESSION["user_id"];

  // Convert roleId to RoleInProject string
  $roleInProject = $roleId == 1 ? "người sở hữu" : "thành viên";

  // Validate form data
  if ($projectID <= 0 || $userID <= 0) {
    echo json_encode(["status" => "error", "message" => "Dữ liệu không hợp lệ! Vui lòng thử lại."]);
    exit();
  }

  // Check if current user has permissions to add members (must be an owner)
  $checkOwnerStmt = $connect->prepare("
    SELECT COUNT(*) AS isOwner 
    FROM ProjectMembers 
    WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu'
  ");
  $checkOwnerStmt->bind_param("ii", $projectID, $currentUserID);
  $checkOwnerStmt->execute();
  $ownerResult = $checkOwnerStmt->get_result()->fetch_assoc();

  // If not an owner, only allow adding as a regular member
  if ($ownerResult["isOwner"] == 0) {
    $roleInProject = "thành viên";
  }

  // Check if the user is already a member of the project
  $checkMemberStmt = $connect->prepare("
    SELECT COUNT(*) AS isMember, u.Username as FullName 
    FROM ProjectMembers pm
    JOIN Users u ON u.ID = pm.UserID 
    WHERE pm.ProjectID = ? AND pm.UserID = ?
  ");
  $checkMemberStmt->bind_param("ii", $projectID, $userID);
  $checkMemberStmt->execute();
  $memberResult = $checkMemberStmt->get_result()->fetch_assoc();

  if ($memberResult["isMember"] > 0) {
    echo json_encode([
      "status" => "error",
      "message" => "Người dùng {$memberResult["FullName"]} đã là thành viên của dự án!",
    ]);
    exit();
  }

  // Get user's name for the success message
  $userStmt = $connect->prepare("SELECT Username as FullName FROM Users WHERE ID = ?");
  $userStmt->bind_param("i", $userID);
  $userStmt->execute();
  $userName = $userStmt->get_result()->fetch_assoc()["FullName"] ?? "Người dùng";

  // Add the member to the project
  $currentDateTime = date("Y-m-d H:i:s");
  $addMemberStmt = $connect->prepare("
    INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) 
    VALUES (?, ?, ?, ?)
  ");
  $addMemberStmt->bind_param("iiss", $projectID, $userID, $roleInProject, $currentDateTime);

  $success = $addMemberStmt->execute();
  $successMsg = "Đã thêm {$userName} vào dự án với vai trò {$roleInProject}!";
  $errorMsg = "Có lỗi xảy ra khi thêm thành viên! Chi tiết: " . $connect->error;

  // Return JSON response
  if ($success) {
    echo json_encode([
      "status" => "success",
      "message" => $successMsg,
    ]);
  } else {
    echo json_encode([
      "status" => "error",
      "message" => $errorMsg,
    ]);
  }
  exit();
}

// If not a POST request
if ($success) {
  echo json_encode([
    "status" => "success",
    "message" => $successMsg,
  ]);
} else {
  echo json_encode([
    "status" => "error",
    "message" => $errorMsg,
  ]);
}
exit();
?> 