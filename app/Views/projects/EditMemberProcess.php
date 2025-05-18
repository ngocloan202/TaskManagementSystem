<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";
require_once "../components/Notification.php";

if (!isset($_SESSION["user_id"])) {
  header("Content-Type: application/json");
  echo json_encode([
    "status" => "error",
    "message" => "Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.",
  ]);
  exit();
}

function clean_input($data)
{
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data
  $projectMembersID = isset($_POST["projectMemberId"]) ? (int) $_POST["projectMemberId"] : 0;
  $projectID = isset($_POST["projectId"]) ? (int) $_POST["projectId"] : 0;
  $roleId = isset($_POST["roleId"]) ? (int) $_POST["roleId"] : 2; // Default to member (2)
  $currentUserID = $_SESSION["user_id"];

  // Convert roleId to RoleInProject string
  $roleInProject = $roleId == 1 ? "người sở hữu" : "thành viên";

  // Validate form data
  if ($projectMembersID <= 0 || $projectID <= 0) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Dữ liệu không hợp lệ! Vui lòng thử lại."]);
    exit();
  }

  // Check if current user has permission to edit (must be owner or editing themselves)
  $checkPermissionStmt = $connect->prepare("
    SELECT pm.UserID, pm.RoleInProject, u.Username as FullName,
           (SELECT COUNT(*) FROM ProjectMembers 
            WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu') AS isCurrentUserOwner
    FROM ProjectMembers pm
    JOIN Users u ON u.ID = pm.UserID
    WHERE pm.ID = ?
  ");
  $checkPermissionStmt->bind_param("iii", $projectID, $currentUserID, $projectMembersID);
  $checkPermissionStmt->execute();
  $permissionResult = $checkPermissionStmt->get_result()->fetch_assoc();

  if (!$permissionResult) {
    header("Content-Type: application/json");
    echo json_encode([
      "status" => "error",
      "message" => "Thành viên không tồn tại trong dự án này!",
    ]);
    exit();
  }

  $userName = $permissionResult["FullName"];
  $userID = $permissionResult["UserID"]; // Keep the same user, only change role

  // Check if current user is the owner or is editing themselves
  $isOwner = $permissionResult["isCurrentUserOwner"] > 0;
  $isEditingSelf = $permissionResult["UserID"] == $currentUserID;

  // If not owner, they can only edit themselves and can't change role
  if (!$isOwner) {
    if (!$isEditingSelf) {
      header("Content-Type: application/json");
      echo json_encode([
        "status" => "error",
        "message" => "Bạn không có quyền chỉnh sửa thông tin của thành viên khác!",
      ]);
      exit();
    }
    // Preserve original role if not an owner
    $originalRole = $permissionResult["RoleInProject"];
    $roleInProject = $originalRole;
  }

  // Update the member information
  $updateStmt = $connect->prepare("
    UPDATE ProjectMembers 
    SET RoleInProject = ?
    WHERE ID = ?
  ");
  $updateStmt->bind_param("si", $roleInProject, $projectMembersID);

  $success = $updateStmt->execute();
  $successMsg = "Đã cập nhật thông tin thành viên {$userName} thành công!";
  $errorMsg = "Có lỗi xảy ra khi cập nhật thông tin thành viên! Chi tiết: " . $connect->error;

  // Return JSON response
  header("Content-Type: application/json");
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
header("Content-Type: application/json");
echo json_encode(["status" => "error", "message" => "Phương thức không hợp lệ."]);
exit();
?> 