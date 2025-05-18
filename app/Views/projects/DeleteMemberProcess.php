<?php
require_once "../../../config/SessionInit.php";
require_once "../../../config/database.php";
require_once "../components/Notification.php";

if (!isset($_SESSION["user_id"])) {
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // AJAX request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.']);
  } else {
    // Regular request
    header("Location: /app/Views/auth/login.php");
  }
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  // Get parameters
  $projectMembersID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $projectID = isset($_GET['projectID']) ? (int)$_GET['projectID'] : 0;
  $currentUserID = $_SESSION["user_id"];

  // Validate parameters
  if ($projectMembersID <= 0 || $projectID <= 0) {
    $errorMsg = "Dữ liệu không hợp lệ! Không thể xóa thành viên.";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
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
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }

  // Check if trying to delete yourself (owners shouldn't delete themselves)
  $targetUserStmt = $connect->prepare("
    SELECT pm.UserID, u.FullName 
    FROM ProjectMembers pm
    JOIN Users u ON u.UserID = pm.UserID
    WHERE pm.ProjectMembersID = ?
  ");
  $targetUserStmt->bind_param('i', $projectMembersID);
  $targetUserStmt->execute();
  $targetUser = $targetUserStmt->get_result()->fetch_assoc();

  if (!$targetUser) {
    $errorMsg = "Không tìm thấy thành viên này!";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }

  $userName = $targetUser['FullName'];
  
  if ($targetUser['UserID'] == $currentUserID) {
    $errorMsg = "Bạn không thể tự xóa mình khỏi dự án! Hãy liên hệ người sở hữu khác để được hỗ trợ.";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }

  // Check if the member exists and belongs to the specified project
  $checkStmt = $connect->prepare("
    SELECT COUNT(*) AS count 
    FROM ProjectMembers 
    WHERE ProjectMembersID = ? AND ProjectID = ?
  ");
  $checkStmt->bind_param('ii', $projectMembersID, $projectID);
  $checkStmt->execute();
  $result = $checkStmt->get_result()->fetch_assoc();

  if ($result['count'] == 0) {
    $errorMsg = "Thành viên không tồn tại hoặc không thuộc dự án này!";
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $errorMsg]);
      exit();
    } else {
      setNotification('error', $errorMsg, true, "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID);
    }
  }

  // Delete the member
  $deleteStmt = $connect->prepare("DELETE FROM ProjectMembers WHERE ProjectMembersID = ?");
  $deleteStmt->bind_param('i', $projectMembersID);
  
  $success = $deleteStmt->execute();
  $successMsg = "Đã xóa {$userName} khỏi dự án thành công!";
  $errorMsg = "Có lỗi xảy ra khi xóa thành viên! Chi tiết: " . $connect->error;
  
  if ($success) {
    setNotification('success', $successMsg);
  } else {
    setNotification('error', $errorMsg);
  }

  // Check if this is an AJAX request
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode([
      'success' => $success,
      'message' => $success ? $successMsg : $errorMsg
    ]);
    exit();
  } else {
    // Regular request - redirect back to the project page
    $returnUrl = "/app/Views/dashboard/ProjectDetail.php?id=" . $projectID . "&memberAction=delete";
    header("Location: " . $returnUrl);
    exit();
  }
}

// If not a GET request, redirect to the dashboard
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
  exit();
} else {
  header("Location: /app/Views/dashboard/index.php");
  exit();
}
?> 