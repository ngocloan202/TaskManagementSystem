<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";
require_once "../components/Notification.php";

if (!isset($_SESSION["user_id"])) {
  header('Content-Type: application/json');
  echo json_encode(['status' => 'error', 'message' => 'Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.']);
  exit();
}

// Handle both POST and GET for backward compatibility
if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
  // Get parameters from POST or GET
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $projectMembersID = isset($_POST['projectMemberId']) ? (int)$_POST['projectMemberId'] : 0;
    $projectID = isset($_POST['projectId']) ? (int)$_POST['projectId'] : 0;
  } else {
    $projectMembersID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $projectID = isset($_GET['projectID']) ? (int)$_GET['projectID'] : 0;
  }
  
  $currentUserID = $_SESSION["user_id"];

  // Validate parameters
  if ($projectMembersID <= 0 || $projectID <= 0) {
    $errorMsg = "Dữ liệu không hợp lệ! Không thể xóa thành viên.";
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    exit();
  }

  // Check if the current user is a project owner (only owners can delete members)
  $ownerCheckStmt = $connect->prepare("
    SELECT COUNT(*) AS isOwner 
    FROM ProjectMembers 
    WHERE ProjectID = ? AND UserID = ? AND RoleInProject = 'người sở hữu'
  ");
  $ownerCheckStmt->bind_param('ii', $projectID, $currentUserID);
  $ownerCheckStmt->execute();
  $ownerResult = $ownerCheckStmt->get_result()->fetch_assoc();

  if ($ownerResult['isOwner'] == 0) {
    $errorMsg = "Bạn không có quyền xóa thành viên! Chỉ người sở hữu dự án mới có thể thực hiện thao tác này.";
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    exit();
  }

  // Check if trying to delete yourself (owners shouldn't delete themselves)
  $targetUserStmt = $connect->prepare("
    SELECT pm.UserID, u.Username as FullName 
    FROM ProjectMembers pm
    JOIN Users u ON u.ID = pm.UserID
    WHERE pm.ID = ?
  ");
  $targetUserStmt->bind_param('i', $projectMembersID);
  $targetUserStmt->execute();
  $targetUser = $targetUserStmt->get_result()->fetch_assoc();

  if (!$targetUser) {
    $errorMsg = "Không tìm thấy thành viên này!";
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    exit();
  }

  $userName = $targetUser['FullName'];
  
  if ($targetUser['UserID'] == $currentUserID) {
    $errorMsg = "Bạn không thể tự xóa mình khỏi dự án! Hãy liên hệ người sở hữu khác để được hỗ trợ.";
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    exit();
  }

  // Check if the member exists and belongs to the specified project
  $checkStmt = $connect->prepare("
    SELECT COUNT(*) AS count 
    FROM ProjectMembers 
    WHERE ID = ? AND ProjectID = ?
  ");
  $checkStmt->bind_param('ii', $projectMembersID, $projectID);
  $checkStmt->execute();
  $result = $checkStmt->get_result()->fetch_assoc();

  if ($result['count'] == 0) {
    $errorMsg = "Thành viên không tồn tại hoặc không thuộc dự án này!";
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    exit();
  }

  // Delete the member
  $deleteStmt = $connect->prepare("DELETE FROM ProjectMembers WHERE ID = ?");
  $deleteStmt->bind_param('i', $projectMembersID);
  
  $success = $deleteStmt->execute();
  $successMsg = "Đã xóa {$userName} khỏi dự án thành công!";
  $errorMsg = "Có lỗi xảy ra khi xóa thành viên! Chi tiết: " . $connect->error;
  
  // Return JSON response
  header('Content-Type: application/json');
  if ($success) {
    echo json_encode([
      'status' => 'success',
      'message' => $successMsg
    ]);
  } else {
    echo json_encode([
      'status' => 'error',
      'message' => $errorMsg
    ]);
  }
  exit();
}

// If not a POST or GET request
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.']);
exit();
?> 